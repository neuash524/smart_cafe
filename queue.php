<?php
/**
 * Smart Café API — queue.php
 * GET    : Fetch queue
 * POST   : Join queue
 * PATCH  : Update queue entry status (admin approval triggers notification + EMAIL + ACTIVITY LOG)
 * DELETE : Remove from queue
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
    
    // Check if user_id column exists in queue table
    $hasUserIdColumn = false;
    try {
        $columns = $pdo->query("SHOW COLUMNS FROM queue LIKE 'user_id'");
        $hasUserIdColumn = $columns->rowCount() > 0;
    } catch (Exception $e) {
        $hasUserIdColumn = false;
    }
    
    // GET - Fetch queue
    if ($method === 'GET') {
        $status = $_GET['status'] ?? 'waiting';
        
        if ($hasUserIdColumn) {
            $rows = fetchAll(
                'SELECT q.*, 
                        (SELECT table_id FROM reservations 
                         WHERE user_id = q.user_id 
                         AND reservation_date = CURDATE() 
                         AND status = "confirmed" 
                         LIMIT 1) as reserved_table_id
                 FROM queue q 
                 WHERE q.status = ? 
                 ORDER BY q.position ASC',
                [$status]
            );
        } else {
            $rows = fetchAll(
                'SELECT * FROM queue WHERE status = ? ORDER BY position ASC',
                [$status]
            );
        }
        
        // Add table number for reserved tables
        foreach ($rows as &$row) {
            if (isset($row['reserved_table_id']) && $row['reserved_table_id']) {
                $table = fetchOne('SELECT table_number FROM cafe_tables WHERE table_id = ?', [$row['reserved_table_id']]);
                $row['reserved_table_number'] = $table ? $table['table_number'] : null;
            }
        }
        
        sendResponse(true, 'OK', ['queue' => $rows]);
    }
    
    // POST - Join queue (pending until admin approves seating)
    if ($method === 'POST') {
        $b = json_decode(file_get_contents('php://input'), true);
        $customerName = sanitizeInput($b['customer_name'] ?? '');
        $customerPhone = sanitizeInput($b['customer_phone'] ?? '');
        $partySize = isset($b['party_size']) ? (int)$b['party_size'] : null;
        $userId = isset($b['user_id']) && !empty($b['user_id']) ? (int)$b['user_id'] : null;
        
        if (!$customerName || !$partySize) {
            sendResponse(false, 'Name and party size are required');
        }
        
        // Verify user exists if user_id is provided and column exists
        $validUserId = null;
        if ($hasUserIdColumn && $userId !== null) {
            $userCheck = fetchOne('SELECT user_id, full_name FROM users WHERE user_id = ? AND is_active = 1', [$userId]);
            if ($userCheck) {
                $validUserId = $userId;
                // Use the registered name if available
                $customerName = $userCheck['full_name'];
            }
        }
        
        $pdo->beginTransaction();
        
        try {
            // Get next position
            $pos = fetchOne('SELECT COALESCE(MAX(position), 0) + 1 AS next FROM queue WHERE status = "waiting"');
            $nextPos = (int)$pos['next'];
            $waitTime = $nextPos * 15;
            
            // Insert queue entry with status 'waiting' (not seated yet)
            if ($hasUserIdColumn && $validUserId !== null) {
                $sql = 'INSERT INTO queue (user_id, customer_name, customer_phone, party_size, status, position, estimated_wait_time, joined_at)
                        VALUES (?, ?, ?, ?, "waiting", ?, ?, NOW())';
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$validUserId, $customerName, $customerPhone, $partySize, $nextPos, $waitTime]);
            } else {
                $sql = 'INSERT INTO queue (customer_name, customer_phone, party_size, status, position, estimated_wait_time, joined_at)
                        VALUES (?, ?, ?, "waiting", ?, ?, NOW())';
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$customerName, $customerPhone, $partySize, $nextPos, $waitTime]);
            }
            $queueId = $pdo->lastInsertId();
            
            // Log activity for joining queue
            logActivity(
                $validUserId,
                'CREATE',
                'queue',
                $queueId,
                "{$customerName} joined queue - Position #{$nextPos}, Party of {$partySize}"
            );
            
            $pdo->commit();
            
            $entry = fetchOne('SELECT * FROM queue WHERE queue_id = ?', [$queueId]);
            sendResponse(true, "You are #{$nextPos} in queue. We'll notify you when your table is ready.", ['entry' => $entry]);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("[Queue] Error creating queue entry: " . $e->getMessage());
            sendResponse(false, 'Failed to join queue: ' . $e->getMessage());
        }
    }
    
    // PATCH - Update queue status (ADMIN SEATING TRIGGERS NOTIFICATION + EMAIL + ACTIVITY LOG)
    if ($method === 'PATCH') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        $b = json_decode(file_get_contents('php://input'), true);
        $status = sanitizeInput($b['status'] ?? '');
        $allowed = ['waiting', 'called', 'seated', 'cancelled'];
        
        if (!$id || !in_array($status, $allowed)) {
            sendResponse(false, 'Invalid request');
        }
        
        $pdo->beginTransaction();
        
        try {
            $entry = fetchOne('SELECT q.*, u.email as user_email, u.full_name as user_name, u.user_id as user_id 
                               FROM queue q 
                               LEFT JOIN users u ON q.user_id = u.user_id 
                               WHERE q.queue_id = ?', [$id]);
            if (!$entry) {
                sendResponse(false, 'Queue entry not found');
            }
            
            $oldStatus = $entry['status'];
            
            $extra = '';
            if ($status === 'seated') $extra = ', seated_at = NOW()';
            if ($status === 'called') $extra = ', called_at = NOW()';
            
            $pdo->prepare("UPDATE queue SET status = ? $extra WHERE queue_id = ?")
                ->execute([$status, $id]);
            
            // ============================================================
            // ADD ACTIVITY LOGGING HERE
            // ============================================================
            $activityDescription = "Queue entry for {$entry['customer_name']} ";
            if ($status === 'seated') {
                $activityDescription .= "seated (Party of {$entry['party_size']})";
            } else if ($status === 'called') {
                $activityDescription .= "called to table";
            } else if ($status === 'cancelled') {
                $activityDescription .= "removed from queue";
            } else {
                $activityDescription .= "status changed from {$oldStatus} to {$status}";
            }
            
            logActivity(
                null,  // admin user id
                'UPDATE', 
                'queue', 
                $id, 
                $activityDescription
            );
            
            // SEND EMAIL NOTIFICATION when seated
            if ($status === 'seated' && $entry['user_email']) {
                try {
                    require_once __DIR__ . '/email_sender.php';
                    
                    // Check if customer has a reservation for today
                    $reservation = fetchOne(
                        'SELECT t.table_number FROM reservations r 
                         LEFT JOIN cafe_tables t ON r.table_id = t.table_id 
                         WHERE r.user_id = ? AND r.reservation_date = CURDATE() AND r.status = "confirmed" 
                         LIMIT 1',
                        [$entry['user_id']]
                    );
                    
                    sendQueueEmail(
                        $entry['user_email'],
                        $entry['customer_name'],
                        $entry['position'],
                        $entry['party_size'],
                        $entry['estimated_wait_time'],
                        'ready'
                    );
                    error_log("[Email] Queue ready notification sent to {$entry['user_email']}");
                } catch (Exception $e) {
                    error_log("[Email] Failed to send queue email: " . $e->getMessage());
                }
            }
            
            // SEND IN-APP NOTIFICATION when seated
            if ($status === 'seated' && $entry['user_id']) {
                try {
                    // Check if customer has a reservation
                    $reservation = fetchOne(
                        'SELECT t.table_number FROM reservations r 
                         LEFT JOIN cafe_tables t ON r.table_id = t.table_id 
                         WHERE r.user_id = ? AND r.reservation_date = CURDATE() AND r.status = "confirmed" 
                         LIMIT 1',
                        [$entry['user_id']]
                    );
                    
                    $tableInfo = $reservation ? " at your reserved table {$reservation['table_number']}" : "";
                    
                    $title = "✅ Your Table is Ready!";
                    $message = "{$entry['customer_name']}, your table is now ready{$tableInfo}. Please proceed to the host station.";
                    
                    $notifSql = 'INSERT INTO notifications (user_id, notification_type, title, message, is_read, created_at)
                                 VALUES (?, "queue", ?, ?, 0, NOW())';
                    $pdo->prepare($notifSql)->execute([$entry['user_id'], $title, $message]);
                    error_log("[Notification] Seating notification sent to user {$entry['user_id']}");
                } catch (Exception $e) {
                    error_log("[Notification] Failed: " . $e->getMessage());
                }
            }
            
            $pdo->commit();
            
            sendResponse(true, "Queue entry updated to {$status}");
            
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("[Queue] PATCH error: " . $e->getMessage());
            sendResponse(false, 'Failed to update queue: ' . $e->getMessage());
        }
    }
    
    // DELETE - Remove from queue
    if ($method === 'DELETE') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        
        if (!$id) {
            sendResponse(false, 'Queue ID required');
        }
        
        try {
            // Get entry info before deleting for logging
            $entry = fetchOne('SELECT customer_name, party_size FROM queue WHERE queue_id = ?', [$id]);
            
            $pdo->prepare('DELETE FROM queue WHERE queue_id = ?')->execute([$id]);
            
            // Log activity for removal
            if ($entry) {
                logActivity(
                    null,
                    'DELETE',
                    'queue',
                    $id,
                    "Queue entry removed for {$entry['customer_name']} (Party of {$entry['party_size']})"
                );
            }
            
            sendResponse(true, 'Removed from queue');
        } catch (Exception $e) {
            error_log("[Queue] DELETE error: " . $e->getMessage());
            sendResponse(false, 'Failed to remove from queue: ' . $e->getMessage());
        }
    }
    
} catch (Exception $e) {
    error_log("[Queue] General error: " . $e->getMessage());
    sendResponse(false, 'Server error: ' . $e->getMessage());
}
?>