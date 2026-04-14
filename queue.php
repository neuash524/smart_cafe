<?php
/**
 * Smart Café API — queue.php
 * GET    : Fetch queue
 * POST   : Join queue
 * PATCH  : Update queue entry status (admin approval triggers notification)
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
            $userCheck = fetchOne('SELECT user_id FROM users WHERE user_id = ? AND is_active = 1', [$userId]);
            if ($userCheck) {
                $validUserId = $userId;
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
            
            // NO notification to customer until admin seats them
            
            $pdo->commit();
            
            $entry = fetchOne('SELECT * FROM queue WHERE queue_id = ?', [$queueId]);
            sendResponse(true, "You are #{$nextPos} in queue. We'll notify you when your table is ready.", ['entry' => $entry]);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("[Queue] Error creating queue entry: " . $e->getMessage());
            sendResponse(false, 'Failed to join queue: ' . $e->getMessage());
        }
    }
    
    // PATCH - Update queue status (ADMIN SEATING TRIGGERS NOTIFICATION)
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
            $entry = fetchOne('SELECT * FROM queue WHERE queue_id = ?', [$id]);
            if (!$entry) {
                sendResponse(false, 'Queue entry not found');
            }
            
            $extra = '';
            if ($status === 'seated') $extra = ', seated_at = NOW()';
            if ($status === 'called') $extra = ', called_at = NOW()';
            
            $pdo->prepare("UPDATE queue SET status = ? $extra WHERE queue_id = ?")
                ->execute([$status, $id]);
            
            // SEND NOTIFICATION ONLY WHEN ADMIN SEATS THE CUSTOMER
            if ($status === 'seated' && $hasUserIdColumn && isset($entry['user_id']) && $entry['user_id']) {
                try {
                    // Check if customer has a reservation for today
                    $reservation = fetchOne(
                        'SELECT r.*, t.table_number FROM reservations r 
                         LEFT JOIN cafe_tables t ON r.table_id = t.table_id 
                         WHERE r.user_id = ? AND r.reservation_date = CURDATE() AND r.status = "confirmed"',
                        [$entry['user_id']]
                    );
                    
                    $tableInfo = '';
                    if ($reservation && $reservation['table_number']) {
                        $tableInfo = " at your reserved table {$reservation['table_number']}";
                    }
                    
                    $title = "✅ Your Table is Ready!";
                    $message = "{$entry['customer_name']}, your table is now ready{$tableInfo}. Please proceed to the host station.";
                    
                    $notifSql = 'INSERT INTO notifications (user_id, notification_type, title, message, is_read, created_at)
                                 VALUES (?, "queue", ?, ?, 0, NOW())';
                    $pdo->prepare($notifSql)->execute([$entry['user_id'], $title, $message]);
                    error_log("[Queue] Seating notification sent to user {$entry['user_id']}");
                } catch (Exception $e) {
                    error_log("[Queue] Failed to send seating notification: " . $e->getMessage());
                }
            }
            
            $pdo->commit();
            
            sendResponse(true, "Queue entry updated to {$status}");
            
        } catch (Exception $e) {
            $pdo->rollBack();
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
            $pdo->prepare('DELETE FROM queue WHERE queue_id = ?')->execute([$id]);
            sendResponse(true, 'Removed from queue');
        } catch (Exception $e) {
            sendResponse(false, 'Failed to remove from queue: ' . $e->getMessage());
        }
    }
    
} catch (Exception $e) {
    error_log("[Queue] General error: " . $e->getMessage());
    sendResponse(false, 'Server error: ' . $e->getMessage());
}
?>