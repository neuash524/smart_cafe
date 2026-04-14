<?php
/**
 * Smart Café API — notifications.php
 * GET    : Fetch notifications for user
 * PATCH  : Mark notification as read / mark all read
 * POST   : Create notification (for testing)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getDBConnection();
    
    // GET - Fetch notifications for user
    if ($method === 'GET') {
        $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        
        if (!$userId) {
            sendResponse(false, 'User ID required');
        }
        
        // First check if user exists
        $userCheck = fetchOne('SELECT user_id, full_name FROM users WHERE user_id = ?', [$userId]);
        if (!$userCheck) {
            sendResponse(false, 'User not found with ID: ' . $userId);
        }
        
        // Check if notifications table exists
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'notifications'");
        if ($tableCheck->rowCount() == 0) {
            sendResponse(false, 'Notifications table does not exist. Please run database schema.');
        }
        
        // Fetch notifications
        $notifications = fetchAll(
            'SELECT notification_id, user_id, notification_type, title, message, is_read, created_at 
             FROM notifications 
             WHERE user_id = ? 
             ORDER BY created_at DESC 
             LIMIT ?',
            [$userId, $limit]
        );
        
        // Convert is_read to proper boolean/int
        foreach ($notifications as &$n) {
            $n['is_read'] = (int)$n['is_read'];
            // Format date for JavaScript
            $n['created_at_formatted'] = date('Y-m-d H:i:s', strtotime($n['created_at']));
        }
        
        sendResponse(true, 'OK', [
            'notifications' => $notifications,
            'user_id' => $userId,
            'count' => count($notifications)
        ]);
    }
    
    // POST - Create notification (for testing/admin use)
    if ($method === 'POST') {
        $b = json_decode(file_get_contents('php://input'), true);
        
        $userId = isset($b['user_id']) ? (int)$b['user_id'] : null;
        $notificationType = sanitizeInput($b['notification_type'] ?? 'general');
        $title = sanitizeInput($b['title'] ?? '');
        $message = sanitizeInput($b['message'] ?? '');
        
        if (!$userId || !$title || !$message) {
            sendResponse(false, 'User ID, title and message are required');
        }
        
        $sql = 'INSERT INTO notifications (user_id, notification_type, title, message, is_read, created_at)
                VALUES (?, ?, ?, ?, 0, NOW())';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $notificationType, $title, $message]);
        $notificationId = $pdo->lastInsertId();
        
        sendResponse(true, 'Notification created', [
            'notification_id' => $notificationId,
            'user_id' => $userId,
            'title' => $title,
            'message' => $message
        ]);
    }
    
    // PATCH - Mark notification as read
    if ($method === 'PATCH') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        $b = json_decode(file_get_contents('php://input'), true);
        
        // Mark all read for a user
        if (isset($b['mark_all_read']) && $b['mark_all_read'] === true) {
            $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
            if (!$userId) {
                sendResponse(false, 'User ID required');
            }
            
            // Check if user has any unread notifications
            $count = fetchOne('SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ? AND is_read = 0', [$userId]);
            
            $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0');
            $stmt->execute([$userId]);
            $affected = $stmt->rowCount();
            
            sendResponse(true, "Marked {$affected} notifications as read", [
                'marked_count' => $affected,
                'user_id' => $userId
            ]);
        }
        
        // Mark single notification as read
        if ($id) {
            // Check if notification exists
            $notif = fetchOne('SELECT notification_id, user_id FROM notifications WHERE notification_id = ?', [$id]);
            if (!$notif) {
                sendResponse(false, 'Notification not found');
            }
            
            $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE notification_id = ?');
            $stmt->execute([$id]);
            
            sendResponse(true, 'Notification marked as read', [
                'notification_id' => $id,
                'user_id' => $notif['user_id']
            ]);
        }
        
        sendResponse(false, 'Invalid request. Provide either id or mark_all_read=true');
    }
    
} catch (Exception $e) {
    error_log("[Notifications] Error: " . $e->getMessage());
    sendResponse(false, 'Server error: ' . $e->getMessage());
}
?>