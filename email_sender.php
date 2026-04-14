<?php
/**
 * Smart Café Email Sender
 * Add this file to your project - no existing code changes needed
 */

// Configuration - UPDATE THESE 2 VALUES
define('CAFE_NAME', 'Smart Café');
define('SITE_URL', 'http://localhost/smart-cafe'); // Change to your domain

/**
 * Simple email sending function
 */
function sendSmartCafeEmail($to, $subject, $message) {
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . CAFE_NAME . " <noreply@" . $_SERVER['HTTP_HOST'] . ">\r\n";
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head><meta charset="UTF-8"></head>
    <body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
        <div style="background: #8B4513; color: white; padding: 20px; text-align: center;">
            <h1>☕ ' . CAFE_NAME . '</h1>
        </div>
        <div style="padding: 20px; border: 1px solid #E5D4C1;">
            ' . $message . '
        </div>
        <div style="text-align: center; padding: 20px; color: #999; font-size: 12px;">
            This is an automated email. Please do not reply.
        </div>
    </body>
    </html>
    ';
    
    return mail($to, $subject, $html, $headers);
}

/**
 * Send Reservation Email
 */
function sendReservationEmail($to, $name, $date, $time, $guests, $table, $status) {
    $dateFormatted = date('l, F j, Y', strtotime($date));
    $timeFormatted = date('g:i A', strtotime($time));
    
    if ($status == 'confirmed') {
        $message = "
            <h2>✅ Reservation Confirmed!</h2>
            <p>Dear <strong>" . htmlspecialchars($name) . "</strong>,</p>
            <p>Your reservation at " . CAFE_NAME . " has been <strong>confirmed</strong>!</p>
            <div style='background: #f5f5f5; padding: 15px; margin: 15px 0;'>
                <p><strong>📅 Date:</strong> $dateFormatted<br>
                <strong>⏰ Time:</strong> $timeFormatted<br>
                <strong>👥 Guests:</strong> $guests<br>
                <strong>🪑 Table:</strong> $table</p>
            </div>
            <p>We look forward to serving you!</p>
            <p><a href='" . SITE_URL . "/customer_dashboard.php#menu' style='background: #8B4513; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Browse Menu</a></p>
        ";
        $subject = "Reservation Confirmed - " . CAFE_NAME;
    } 
    elseif ($status == 'cancelled') {
        $message = "
            <h2>❌ Reservation Cancelled</h2>
            <p>Dear <strong>" . htmlspecialchars($name) . "</strong>,</p>
            <p>Your reservation for $dateFormatted at $timeFormatted has been <strong>cancelled</strong>.</p>
            <p>If you didn't request this, please contact us.</p>
            <p><a href='" . SITE_URL . "/customer_dashboard.php#reserve' style='background: #8B4513; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Book Again</a></p>
        ";
        $subject = "Reservation Cancelled - " . CAFE_NAME;
    }
    else {
        $message = "
            <h2>📋 Reservation Request Received</h2>
            <p>Dear <strong>" . htmlspecialchars($name) . "</strong>,</p>
            <p>We've received your reservation request:</p>
            <div style='background: #f5f5f5; padding: 15px; margin: 15px 0;'>
                <p><strong>📅 Date:</strong> $dateFormatted<br>
                <strong>⏰ Time:</strong> $timeFormatted<br>
                <strong>👥 Guests:</strong> $guests<br>
                <strong>🪑 Table:</strong> $table</p>
            </div>
            <p>Your reservation is <strong>pending confirmation</strong>. You'll receive another email when confirmed.</p>
        ";
        $subject = "Reservation Request Received - " . CAFE_NAME;
    }
    
    return sendSmartCafeEmail($to, $subject, $message);
}

/**
 * Send Order Email
 */
function sendOrderEmail($to, $name, $orderRef, $items, $total, $status) {
    $itemsHtml = "<ul>";
    foreach ($items as $item) {
        $itemsHtml .= "<li>{$item['quantity']}× " . htmlspecialchars($item['item_name']) . " - $" . number_format($item['subtotal'], 2) . "</li>";
    }
    $itemsHtml .= "</ul>";
    
    $totalFormatted = number_format($total, 2);
    
    if ($status == 'confirmed') {
        $message = "
            <h2>✅ Order Confirmed!</h2>
            <p>Dear <strong>" . htmlspecialchars($name) . "</strong>,</p>
            <p>Your order <strong>$orderRef</strong> has been confirmed!</p>
            <div style='background: #f5f5f5; padding: 15px; margin: 15px 0;'>
                <h3>Order Details</h3>
                $itemsHtml
                <p><strong>Total:</strong> $$totalFormatted</p>
            </div>
            <p>You'll receive updates when your order is ready.</p>
        ";
        $subject = "Order Confirmed - $orderRef";
    }
    elseif ($status == 'ready') {
        $message = "
            <h2>✅ Order Ready for Pickup!</h2>
            <p>Dear <strong>" . htmlspecialchars($name) . "</strong>,</p>
            <p>Your order <strong>$orderRef</strong> is ready for pickup!</p>
            <div style='background: #f5f5f5; padding: 15px; margin: 15px 0;'>
                $itemsHtml
                <p><strong>Total:</strong> $$totalFormatted</p>
            </div>
            <p>Please come to the counter to collect your order.</p>
        ";
        $subject = "Order Ready - $orderRef";
    }
    else {
        return false;
    }
    
    return sendSmartCafeEmail($to, $subject, $message);
}

/**
 * Send Queue Email
 */
function sendQueueEmail($to, $name, $position, $partySize, $waitTime, $status) {
    if ($status == 'joined') {
        $message = "
            <h2>⏱️ You're in the Queue!</h2>
            <p>Dear <strong>" . htmlspecialchars($name) . "</strong>,</p>
            <p>You've joined the waiting queue!</p>
            <div style='background: #f5f5f5; padding: 15px; margin: 15px 0;'>
                <p><strong>Position:</strong> #$position<br>
                <strong>Estimated Wait:</strong> $waitTime minutes<br>
                <strong>Party Size:</strong> $partySize</p>
            </div>
            <p>We'll notify you when your table is ready!</p>
            <p><a href='" . SITE_URL . "/index.html#menu' style='background: #8B4513; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Pre-order Now</a></p>
        ";
        $subject = "You're in the Queue - " . CAFE_NAME;
    }
    elseif ($status == 'ready') {
        $message = "
            <h2>✅ Your Table is Ready!</h2>
            <p>Dear <strong>" . htmlspecialchars($name) . "</strong>,</p>
            <p>Your table is now ready!</p>
            <div style='background: #f5f5f5; padding: 15px; margin: 15px 0;'>
                <p><strong>Party Size:</strong> $partySize</p>
            </div>
            <p>Please proceed to the host station.</p>
        ";
        $subject = "Your Table is Ready - " . CAFE_NAME;
    }
    else {
        return false;
    }
    
    return sendSmartCafeEmail($to, $subject, $message);
}
?>