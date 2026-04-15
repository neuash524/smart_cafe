<?php
/**
 * Smart Café API — reservations.php
 * GET    : Fetch reservations
 * POST   : Create reservation (pending by default)
 * PATCH  : Update reservation status (admin approval triggers notification + EMAIL)
 * DELETE : Cancel reservation
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once 'config.php';

// RESTful endpoints with proper HTTP methods
$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getDBConnection();
    
    // GET - Fetch reservations
    if ($method === 'GET') {
        $userId = $_GET['user_id'] ?? null;
        $date = $_GET['date'] ?? null;
        $status = $_GET['status'] ?? null;
        
        $sql = 'SELECT r.*, t.table_number, t.capacity
                FROM reservations r
                LEFT JOIN cafe_tables t ON r.table_id = t.table_id
                WHERE 1=1';
        $params = [];
        
        if ($userId) {
            $sql .= ' AND r.user_id = ?';
            $params[] = (int)$userId;
        }
        if ($date) {
            $sql .= ' AND r.reservation_date = ?';
            $params[] = $date;
        }
        if ($status) {
            $sql .= ' AND r.status = ?';
            $params[] = $status;
        }
        
        $sql .= ' ORDER BY r.reservation_date DESC, r.reservation_time DESC';
        
        $reservations = fetchAll($sql, $params);
        sendResponse(true, 'OK', ['reservations' => $reservations]);
    }
    
    // POST - Create reservation (always pending until admin approves)
    if ($method === 'POST') {
        $b = json_decode(file_get_contents('php://input'), true);
        
        $userId = isset($b['user_id']) && !empty($b['user_id']) ? (int)$b['user_id'] : null;
        $customerName = sanitizeInput($b['customer_name'] ?? '');
        $customerEmail = sanitizeInput($b['customer_email'] ?? '');
        $customerPhone = sanitizeInput($b['customer_phone'] ?? '');
        $tableId = isset($b['table_id']) ? (int)$b['table_id'] : null;
        $reservationDate = sanitizeInput($b['reservation_date'] ?? '');
        $reservationTime = sanitizeInput($b['reservation_time'] ?? '');
        $guests = isset($b['number_of_guests']) ? (int)$b['number_of_guests'] : null;
        $specialRequests = sanitizeInput($b['special_requests'] ?? '');
        
        if (!$customerName || !$customerEmail || !$reservationDate || !$reservationTime || !$guests || !$tableId) {
            sendResponse(false, 'Missing required fields');
        }
        
        $pdo->beginTransaction();
        
        try {
            // Check if table exists and is available
            $table = fetchOne('SELECT * FROM cafe_tables WHERE table_id = ?', [$tableId]);
            if (!$table) {
                sendResponse(false, 'Table not found');
            }
            
            if ($table['status'] !== 'available') {
                sendResponse(false, 'Table is not available');
            }
            
            if ($guests > $table['capacity']) {
                sendResponse(false, "Table only seats {$table['capacity']} guests");
            }
            
            // Verify user exists in database if user_id is provided
            $validUserId = null;
            if ($userId !== null) {
                $userCheck = fetchOne('SELECT user_id, full_name FROM users WHERE user_id = ? AND is_active = 1', [$userId]);
                if ($userCheck) {
                    $validUserId = $userId;
                    $customerName = $userCheck['full_name'];
                }
            }
            
            // Check for double booking
            $existing = fetchOne(
                'SELECT reservation_id FROM reservations 
                 WHERE table_id = ? AND reservation_date = ? AND reservation_time = ? 
                 AND status IN ("pending", "confirmed")',
                [$tableId, $reservationDate, $reservationTime]
            );
            
            if ($existing) {
                sendResponse(false, 'This table is already booked for that time');
            }
            
            // Create reservation with status 'pending' (waiting for admin approval)
            $sql = 'INSERT INTO reservations 
                    (user_id, customer_name, customer_email, customer_phone, table_id, 
                     reservation_date, reservation_time, number_of_guests, special_requests, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "pending")';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$validUserId, $customerName, $customerEmail, $customerPhone, $tableId,
                           $reservationDate, $reservationTime, $guests, $specialRequests]);
            $reservationId = $pdo->lastInsertId();
            
            // Mark table as reserved
            $pdo->prepare('UPDATE cafe_tables SET status = "reserved" WHERE table_id = ?')
                ->execute([$tableId]);
            
            // NO notification sent to customer here - wait for admin approval
            
            // Log activity
            logActivity($validUserId, 'CREATE', 'reservations', $reservationId, 
                       "Reservation request created for {$customerName} on {$reservationDate} at {$reservationTime} (pending approval)");
            
            $pdo->commit();
            
            // Get the created reservation with table number
            $reservation = fetchOne(
                'SELECT r.*, t.table_number FROM reservations r 
                 LEFT JOIN cafe_tables t ON r.table_id = t.table_id 
                 WHERE r.reservation_id = ?',
                [$reservationId]
            );
            
            sendResponse(true, "Reservation request submitted! Waiting for admin approval.", ['reservation' => $reservation]);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("[Reservation] Error: " . $e->getMessage());
            sendResponse(false, 'Failed to create reservation: ' . $e->getMessage());
        }
    }
    
    // PATCH - Update reservation status (ADMIN APPROVAL TRIGGERS NOTIFICATION + EMAIL)
    if ($method === 'PATCH') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        $b = json_decode(file_get_contents('php://input'), true);
        $status = sanitizeInput($b['status'] ?? '');
        $allowed = ['pending', 'confirmed', 'cancelled', 'completed', 'no_show'];
        
        if (!$id || !in_array($status, $allowed)) {
            sendResponse(false, 'Invalid request');
        }
        
        $pdo->beginTransaction();
        
        try {
            $reservation = fetchOne('SELECT r.*, t.table_number FROM reservations r 
                                     LEFT JOIN cafe_tables t ON r.table_id = t.table_id 
                                     WHERE r.reservation_id = ?', [$id]);
            if (!$reservation) {
                sendResponse(false, 'Reservation not found');
            }
            
            $oldStatus = $reservation['status'];
            
            $pdo->prepare('UPDATE reservations SET status = ?, updated_at = NOW() WHERE reservation_id = ?')
                ->execute([$status, $id]);
            
            // If cancelled, free the table
            if ($status === 'cancelled' && $reservation['table_id']) {
                $pdo->prepare('UPDATE cafe_tables SET status = "available" WHERE table_id = ?')
                    ->execute([$reservation['table_id']]);
            }
            
            // If confirmed, keep table as reserved
            if ($status === 'confirmed' && $oldStatus === 'pending') {
                // Table already reserved from creation, keep it
            }
            
            // ============================================================
            // SEND EMAIL NOTIFICATION WHEN ADMIN APPROVES
            // ============================================================
            if (($status === 'confirmed' || $status === 'cancelled') && $reservation['customer_email']) {
                try {
                    require_once __DIR__ . '/email_sender.php';
                    
                    if ($status === 'confirmed') {
                        sendReservationEmail(
                            $reservation['customer_email'],
                            $reservation['customer_name'],
                            $reservation['reservation_date'],
                            $reservation['reservation_time'],
                            $reservation['number_of_guests'],
                            $reservation['table_number'] ?? 'Reserved Table',
                            'confirmed'
                        );
                        error_log("[Email] Reservation confirmation sent to {$reservation['customer_email']}");
                    } else if ($status === 'cancelled') {
                        sendReservationEmail(
                            $reservation['customer_email'],
                            $reservation['customer_name'],
                            $reservation['reservation_date'],
                            $reservation['reservation_time'],
                            $reservation['number_of_guests'],
                            $reservation['table_number'] ?? 'Reserved Table',
                            'cancelled'
                        );
                        error_log("[Email] Reservation cancellation sent to {$reservation['customer_email']}");
                    }
                } catch (Exception $e) {
                    error_log("[Email] Failed to send reservation email: " . $e->getMessage());
                }
            }
            
            // ============================================================
            // SEND IN-APP NOTIFICATION
            // ============================================================
            if (($status === 'confirmed' || $status === 'cancelled') && $reservation['user_id']) {
                try {
                    $title = $status === 'confirmed' ? "✅ Reservation Confirmed!" : "❌ Reservation Cancelled";
                    $message = $status === 'confirmed' 
                        ? "Your reservation for {$reservation['reservation_date']} at {$reservation['reservation_time']} has been confirmed by the admin."
                        : "Your reservation for {$reservation['reservation_date']} at {$reservation['reservation_time']} has been cancelled by the admin.";
                    
                    $notifSql = 'INSERT INTO notifications (user_id, notification_type, title, message, is_read, created_at)
                                 VALUES (?, "reservation", ?, ?, 0, NOW())';
                    $pdo->prepare($notifSql)->execute([$reservation['user_id'], $title, $message]);
                    error_log("[Notification] Reservation update sent to user {$reservation['user_id']}");
                } catch (Exception $e) {
                    error_log("[Notification] Failed: " . $e->getMessage());
                }
            }
            
            $pdo->commit();
            
            sendResponse(true, "Reservation #{$id} updated to {$status}");
            
        } catch (Exception $e) {
            $pdo->rollBack();
            sendResponse(false, 'Failed to update reservation: ' . $e->getMessage());
        }
    }
    
    // DELETE - Cancel reservation
    if ($method === 'DELETE') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        
        if (!$id) {
            sendResponse(false, 'Reservation ID required');
        }
        
        $pdo->beginTransaction();
        
        try {
            $reservation = fetchOne('SELECT * FROM reservations WHERE reservation_id = ?', [$id]);
            if (!$reservation) {
                sendResponse(false, 'Reservation not found');
            }
            
            $pdo->prepare('DELETE FROM reservations WHERE reservation_id = ?')->execute([$id]);
            
            // Free the table
            if ($reservation['table_id']) {
                $pdo->prepare('UPDATE cafe_tables SET status = "available" WHERE table_id = ?')
                    ->execute([$reservation['table_id']]);
            }
            
            $pdo->commit();
            
            sendResponse(true, "Reservation #{$id} cancelled");
            
        } catch (Exception $e) {
            $pdo->rollBack();
            sendResponse(false, 'Failed to cancel reservation: ' . $e->getMessage());
        }
    }
    
} catch (Exception $e) {
    error_log("[Reservation] General error: " . $e->getMessage());
    sendResponse(false, 'Server error: ' . $e->getMessage());
}
?>