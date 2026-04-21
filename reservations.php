<?php
/**
 * Smart Café API — reservations.php
 * GET    : Fetch reservations
 * POST   : Create reservation (pending approval)
 * PATCH  : Update reservation status (ADMIN ACTION -> triggers email)
 * DELETE : Cancel reservation
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getDBConnection();
    
    // ============================================================
    // GET - Fetch reservations
    // ============================================================
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
    
    // ============================================================
    // POST - Create reservation (pending admin approval)
    // ============================================================
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
           $table = fetchOne('SELECT * FROM cafe_tables WHERE table_id = ?', [$tableId]);
if (!$table || $table['status'] !== 'available') {
    sendResponse(false, 'Table not available. Please select another table.');
}
            
            if ($guests > $table['capacity']) {
                sendResponse(false, "Table only seats {$table['capacity']} guests");
            }
            
            $validUserId = null;
            if ($userId !== null) {
                $userCheck = fetchOne('SELECT user_id FROM users WHERE user_id = ?', [$userId]);
                if ($userCheck) $validUserId = $userId;
            }
            
            $sql = 'INSERT INTO reservations 
                    (user_id, customer_name, customer_email, customer_phone, table_id, 
                     reservation_date, reservation_time, number_of_guests, special_requests, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "pending")';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$validUserId, $customerName, $customerEmail, $customerPhone, $tableId,
                           $reservationDate, $reservationTime, $guests, $specialRequests]);
            $reservationId = $pdo->lastInsertId();
            
            $pdo->prepare('UPDATE cafe_tables SET status = "reserved" WHERE table_id = ?')->execute([$tableId]);
            
            $pdo->commit();
            
            $reservation = fetchOne('SELECT r.*, t.table_number FROM reservations r 
                                     LEFT JOIN cafe_tables t ON r.table_id = t.table_id 
                                     WHERE r.reservation_id = ?', [$reservationId]);
            
            sendResponse(true, "Reservation request submitted! Awaiting admin approval.", ['reservation' => $reservation]);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            sendResponse(false, 'Failed to create reservation: ' . $e->getMessage());
        }
    }
    
    // ============================================================
    // PATCH - Update reservation status (TRIGGERS EMAIL)
    // ============================================================
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
            $reservation = fetchOne('SELECT r.*, t.table_number 
                                     FROM reservations r
                                     LEFT JOIN cafe_tables t ON r.table_id = t.table_id
                                     WHERE r.reservation_id = ?', [$id]);
            if (!$reservation) {
                sendResponse(false, 'Reservation not found');
            }
            
            $oldStatus = $reservation['status'];
            
            $pdo->prepare('UPDATE reservations SET status = ?, updated_at = NOW() WHERE reservation_id = ?')
                ->execute([$status, $id]);
            
            if ($status === 'cancelled' && $reservation['table_id']) {
                $pdo->prepare('UPDATE cafe_tables SET status = "available" WHERE table_id = ?')
                    ->execute([$reservation['table_id']]);
            } else if ($status === 'confirmed' && $reservation['table_id']) {
                $pdo->prepare('UPDATE cafe_tables SET status = "reserved" WHERE table_id = ?')
                    ->execute([$reservation['table_id']]);
            }
            
            // ============================================================
            // SEND EMAIL NOTIFICATION WHEN ADMIN CONFIRMS/CANCELLS
            // ============================================================
            if ($status === 'confirmed' || $status === 'cancelled') {
                $customerEmail = $reservation['customer_email'];
                $customerName = $reservation['customer_name'];
                $tableNumber = $reservation['table_number'] ?? 'TBD';
                
                if ($customerEmail && filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
                    // Include email sender
                    require_once __DIR__ . '/email_sender.php';
                    
                    // Send the email
                    $emailSent = sendReservationEmail(
                        $customerEmail,
                        $customerName,
                        $reservation['reservation_date'],
                        $reservation['reservation_time'],
                        $reservation['number_of_guests'],
                        $tableNumber,
                        $status
                    );
                    
                    if ($emailSent) {
                        error_log("[Reservation] Email sent to {$customerEmail} for reservation #{$id} - Status: {$status}");
                    } else {
                        error_log("[Reservation] Failed to send email to {$customerEmail}");
                    }
                } else {
                    error_log("[Reservation] Invalid email for reservation #{$id}: {$customerEmail}");
                }
            }
            
            // ============================================================
            // SEND IN-APP NOTIFICATION
            // ============================================================
            if (($status === 'confirmed' || $status === 'cancelled') && $reservation['user_id']) {
                try {
                    $title = $status === 'confirmed' ? "✅ Reservation Confirmed!" : "❌ Reservation Cancelled";
                    $message = $status === 'confirmed' 
                        ? "Your reservation for {$reservation['reservation_date']} at {$reservation['reservation_time']} has been confirmed."
                        : "Your reservation for {$reservation['reservation_date']} at {$reservation['reservation_time']} has been cancelled.";
                    
                    $pdo->prepare('INSERT INTO notifications (user_id, notification_type, title, message, is_read, created_at)
                                   VALUES (?, "reservation", ?, ?, 0, NOW())')
                        ->execute([$reservation['user_id'], $title, $message]);
                    error_log("[Reservation] In-app notification sent to user {$reservation['user_id']}");
                } catch (Exception $e) {
                    error_log("[Reservation] In-app notification failed: " . $e->getMessage());
                }
            }
            
            // Log activity
            logActivity(null, 'UPDATE', 'reservations', $id,
                       "Reservation #{$id} status changed from {$oldStatus} to {$status}");
            
            $pdo->commit();
            
            sendResponse(true, "Reservation #{$id} updated to {$status}");
            
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("[Reservation] PATCH error: " . $e->getMessage());
            sendResponse(false, 'Failed to update reservation: ' . $e->getMessage());
        }
    }
    
    // ============================================================
    // DELETE - Cancel reservation
    // ============================================================
    if ($method === 'DELETE') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        
        if (!$id) {
            sendResponse(false, 'Reservation ID required');
        }
        
        try {
            $reservation = fetchOne('SELECT * FROM reservations WHERE reservation_id = ?', [$id]);
            if (!$reservation) {
                sendResponse(false, 'Reservation not found');
            }
            
            $pdo->prepare('DELETE FROM reservations WHERE reservation_id = ?')->execute([$id]);
            
            if ($reservation['table_id']) {
                $pdo->prepare('UPDATE cafe_tables SET status = "available" WHERE table_id = ?')
                    ->execute([$reservation['table_id']]);
            }
            
            sendResponse(true, "Reservation #{$id} cancelled");
            
        } catch (Exception $e) {
            sendResponse(false, 'Failed to cancel reservation: ' . $e->getMessage());
        }
    }
    
} catch (Exception $e) {
    error_log("[Reservation] General error: " . $e->getMessage());
    sendResponse(false, 'Server error: ' . $e->getMessage());
}
?>