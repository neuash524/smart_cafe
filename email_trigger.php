<?php
/**
 * Email Trigger - Automatically sends emails when database changes
 * Call this file after any admin action
 * 
 * Usage: email_trigger.php?type=reservation&id=123&status=confirmed
 */

require_once 'config.php';
require_once 'email_sender.php';

$type = $_GET['type'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$status = $_GET['status'] ?? '';

if (!$id) {
    echo "No ID provided";
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Handle Reservation Emails
    if ($type == 'reservation') {
        $stmt = $pdo->prepare("
            SELECT r.*, t.table_number 
            FROM reservations r 
            LEFT JOIN cafe_tables t ON r.table_id = t.table_id 
            WHERE r.reservation_id = ?
        ");
        $stmt->execute([$id]);
        $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($reservation && $reservation['customer_email']) {
            sendReservationEmail(
                $reservation['customer_email'],
                $reservation['customer_name'],
                $reservation['reservation_date'],
                $reservation['reservation_time'],
                $reservation['number_of_guests'],
                $reservation['table_number'],
                $status
            );
            echo "✅ Reservation email sent to: " . $reservation['customer_email'];
        } else {
            echo "❌ No email address found";
        }
    }
    
    // Handle Order Emails
    elseif ($type == 'order') {
        $stmt = $pdo->prepare("
            SELECT o.*, u.email as user_email 
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.user_id 
            WHERE o.order_id = ?
        ");
        $stmt->execute([$id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            // Get order items
            $stmt2 = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
            $stmt2->execute([$id]);
            $items = $stmt2->fetchAll(PDO::FETCH_ASSOC);
            
            $customerEmail = $order['user_email'] ?? $order['customer_email'];
            
            if ($customerEmail) {
                sendOrderEmail(
                    $customerEmail,
                    $order['customer_name'],
                    $order['order_ref'],
                    $items,
                    $order['total_amount'],
                    $status
                );
                echo "✅ Order email sent to: " . $customerEmail;
            } else {
                echo "❌ No email address found";
            }
        }
    }
    
    // Handle Queue Emails
    elseif ($type == 'queue') {
        $stmt = $pdo->prepare("
            SELECT q.*, u.email as user_email 
            FROM queue q 
            LEFT JOIN users u ON q.user_id = u.user_id 
            WHERE q.queue_id = ?
        ");
        $stmt->execute([$id]);
        $queue = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($queue && $queue['user_email']) {
            sendQueueEmail(
                $queue['user_email'],
                $queue['customer_name'],
                $queue['position'],
                $queue['party_size'],
                $queue['estimated_wait_time'],
                $status
            );
            echo "✅ Queue email sent to: " . $queue['user_email'];
        } else {
            echo "❌ No email address found";
        }
    }
    
    else {
        echo "❌ Invalid type. Use: reservation, order, or queue";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>