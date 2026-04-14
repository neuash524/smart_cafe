<?php
/**
 * Smart Café API — users.php
 * GET    : Fetch all customers
 * PATCH  : Update user status
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getDBConnection();
    
    // GET - Fetch users
    if ($method === 'GET') {
        $search = $_GET['search'] ?? null;
        
        $sql = 'SELECT u.user_id, u.email, u.full_name, u.phone, u.user_type, u.is_active, u.created_at, u.last_login,
                       COUNT(DISTINCT r.reservation_id) AS reservation_count,
                       COUNT(DISTINCT o.order_id) AS order_count,
                       COALESCE(SUM(CASE WHEN o.payment_status = "paid" THEN o.total_amount ELSE 0 END), 0) AS total_spent
                FROM users u
                LEFT JOIN reservations r ON r.user_id = u.user_id
                LEFT JOIN orders o ON o.user_id = u.user_id
                WHERE u.user_type = "customer"';
        $params = [];
        
        if ($search) {
            $sql .= ' AND (u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';
            $like = '%' . $search . '%';
            $params = [$like, $like, $like];
        }
        $sql .= ' GROUP BY u.user_id ORDER BY u.created_at DESC';
        
        $users = fetchAll($sql, $params);
        sendResponse(true, 'OK', ['users' => $users]);
    }
    
    // PATCH - Toggle user status
    if ($method === 'PATCH') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        $b = json_decode(file_get_contents('php://input'), true);
        $isActive = isset($b['is_active']) ? (bool)$b['is_active'] : null;
        
        if (!$id || $isActive === null) {
            sendResponse(false, 'Invalid request');
        }
        
        $pdo->prepare('UPDATE users SET is_active = ? WHERE user_id = ? AND user_type = "customer"')
            ->execute([$isActive ? 1 : 0, $id]);
        
        logActivity(null, 'UPDATE', 'users', $id,
                   "User #{$id} " . ($isActive ? 'activated' : 'deactivated'));
        sendResponse(true, 'User status updated');
    }
    
} catch (Exception $e) {
    sendResponse(false, 'Error: ' . $e->getMessage());
}
?>