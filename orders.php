<?php
/**
 * Smart Café API — orders.php
 * GET    : Fetch orders
 * POST   : Create order (pending, no notification)
 * PATCH  : Update order status (admin action triggers notification)
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
    
    // GET - Fetch orders
    if ($method === 'GET') {
        $userId = $_GET['user_id'] ?? null;
        $status = $_GET['status'] ?? null;
        
        $sql = 'SELECT o.*, u.email as user_email 
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.user_id
                WHERE 1=1';
        $params = [];
        
        if ($userId) {
            $sql .= ' AND o.user_id = ?';
            $params[] = (int)$userId;
        }
        if ($status) {
            $sql .= ' AND o.status = ?';
            $params[] = $status;
        }
        
        $sql .= ' ORDER BY o.order_id DESC';
        
        $orders = fetchAll($sql, $params);
        
        // Get order items for each order
        foreach ($orders as &$order) {
            $order['items'] = fetchAll(
                'SELECT * FROM order_items WHERE order_id = ?',
                [$order['order_id']]
            );
        }
        
        sendResponse(true, 'OK', ['orders' => $orders]);
    }
    
    // POST - Create order (pending, no notification until admin updates)
    if ($method === 'POST') {
        $b = json_decode(file_get_contents('php://input'), true);
        
        $userId = isset($b['user_id']) && !empty($b['user_id']) ? (int)$b['user_id'] : null;
        $customerName = sanitizeInput($b['customer_name'] ?? '');
        $customerEmail = sanitizeInput($b['customer_email'] ?? '');
        $customerPhone = sanitizeInput($b['customer_phone'] ?? '');
        $orderType = sanitizeInput($b['order_type'] ?? 'pre_order');
        $totalAmount = isset($b['total_amount']) ? (float)$b['total_amount'] : 0;
        $paymentMethod = sanitizeInput($b['payment_method'] ?? 'credit_card');
        $items = $b['items'] ?? [];
        
        if (!$customerName || !$totalAmount || count($items) === 0) {
            sendResponse(false, 'Missing required fields');
        }
        
        $pdo->beginTransaction();
        
        try {
            // Generate order reference
            $orderRef = 'ORD-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Verify user exists if user_id is provided
            $validUserId = null;
            if ($userId !== null) {
                $userCheck = fetchOne('SELECT user_id FROM users WHERE user_id = ? AND is_active = 1', [$userId]);
                if ($userCheck) {
                    $validUserId = $userId;
                }
            }
            
            // Create order with status 'pending' (waiting for admin action)
            $sql = 'INSERT INTO orders 
                    (user_id, customer_name, customer_phone, order_type, status, total_amount, 
                     payment_status, payment_method, order_ref, created_at)
                    VALUES (?, ?, ?, ?, "pending", ?, "paid", ?, ?, NOW())';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$validUserId, $customerName, $customerPhone, $orderType, 
                           $totalAmount, $paymentMethod, $orderRef]);
            $orderId = $pdo->lastInsertId();
            
            // Add order items
            foreach ($items as $item) {
                $itemSql = 'INSERT INTO order_items (order_id, item_id, item_name, quantity, unit_price, subtotal)
                            VALUES (?, ?, ?, ?, ?, ?)';
                $pdo->prepare($itemSql)->execute([
                    $orderId,
                    $item['item_id'] ?? null,
                    $item['item_name'],
                    $item['quantity'],
                    $item['unit_price'],
                    $item['subtotal']
                ]);
            }
            
            // Record payment
            $paymentSql = 'INSERT INTO payments (order_id, amount, payment_method, status, payment_date)
                           VALUES (?, ?, ?, "completed", NOW())';
            $pdo->prepare($paymentSql)->execute([$orderId, $totalAmount, $paymentMethod]);
            
            // NO notification sent to customer - wait for admin to update status
            
            $pdo->commit();
            
            $order = fetchOne('SELECT * FROM orders WHERE order_id = ?', [$orderId]);
            $order['items'] = $items;
            
            sendResponse(true, "Order #{$orderRef} placed successfully! You'll be notified when it's ready.", ['order' => $order]);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("[Order] Error: " . $e->getMessage());
            sendResponse(false, 'Failed to place order: ' . $e->getMessage());
        }
    }
    
    // PATCH - Update order status (ADMIN ACTION TRIGGERS NOTIFICATION)
    if ($method === 'PATCH') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        $b = json_decode(file_get_contents('php://input'), true);
        $status = sanitizeInput($b['status'] ?? '');
        $allowed = ['pending', 'preparing', 'ready', 'completed', 'cancelled'];
        
        if (!$id || !in_array($status, $allowed)) {
            sendResponse(false, 'Invalid request');
        }
        
        $pdo->beginTransaction();
        
        try {
            $order = fetchOne('SELECT o.*, u.email as user_email FROM orders o 
                               LEFT JOIN users u ON o.user_id = u.user_id 
                               WHERE o.order_id = ?', [$id]);
            if (!$order) {
                sendResponse(false, 'Order not found');
            }
            
            $oldStatus = $order['status'];
            
            $pdo->prepare('UPDATE orders SET status = ?, updated_at = NOW() WHERE order_id = ?')
                ->execute([$status, $id]);
            
            // SEND NOTIFICATION ONLY WHEN ADMIN UPDATES ORDER STATUS
            if ($order['user_id'] && $status !== $oldStatus) {
                try {
                    $title = '';
                    $message = '';
                    
                    switch ($status) {
                        case 'preparing':
                            $title = "👨‍🍳 Order Being Prepared";
                            $message = "Your order #{$order['order_ref']} is now being prepared by our kitchen staff.";
                            break;
                        case 'ready':
                            $title = "✅ Order Ready for Pickup!";
                            $message = "Your order #{$order['order_ref']} is ready for pickup. Please come to the counter.";
                            break;
                        case 'completed':
                            $title = "🎉 Order Completed";
                            $message = "Your order #{$order['order_ref']} has been completed. Thank you for dining with us!";
                            break;
                        case 'cancelled':
                            $title = "❌ Order Cancelled";
                            $message = "Your order #{$order['order_ref']} has been cancelled. Please contact the café for details.";
                            break;
                    }
                    
                    if ($title && $message) {
                        $notifSql = 'INSERT INTO notifications (user_id, notification_type, title, message, is_read, created_at)
                                     VALUES (?, "order", ?, ?, 0, NOW())';
                        $pdo->prepare($notifSql)->execute([$order['user_id'], $title, $message]);
                        error_log("[Order] Status update notification sent to user {$order['user_id']}: {$status}");
                    }
                } catch (Exception $e) {
                    error_log("[Order] Failed to send notification: " . $e->getMessage());
                }
            }
            
            $pdo->commit();
            
            sendResponse(true, "Order #{$order['order_ref']} updated to {$status}");
            
        } catch (Exception $e) {
            $pdo->rollBack();
            sendResponse(false, 'Failed to update order: ' . $e->getMessage());
        }
    }
    
} catch (Exception $e) {
    error_log("[Order] General error: " . $e->getMessage());
    sendResponse(false, 'Server error: ' . $e->getMessage());
}
?>