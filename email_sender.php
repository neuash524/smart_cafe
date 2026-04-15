<?php
/**
 * Smart Café Email Sender - COMPLETE VERSION
 * Handles all email notifications for reservations, orders, and queue
 */

// Configuration - UPDATE THESE VALUES FOR PRODUCTION
define('CAFE_NAME', 'Smart Café');
define('SITE_URL', 'http://localhost/smart-cafe'); // Change to your actual domain

/**
 * Send email using PHP mail() function
 * For production, consider using PHPMailer with SMTP for better deliverability
 */
function sendSmartCafeEmail($to, $subject, $message) {
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        error_log("[Email] Invalid email address: {$to}");
        return false;
    }
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . CAFE_NAME . " <noreply@" . $_SERVER['HTTP_HOST'] . ">\r\n";
    $headers .= "Reply-To: " . CAFE_NAME . " <info@" . $_SERVER['HTTP_HOST'] . ">\r\n";
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . CAFE_NAME . '</title>
    </head>
    <body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background-color: #f5f5f5;">
        <div style="background: #8B4513; color: white; padding: 20px; text-align: center;">
            <h1 style="margin: 0;">☕ ' . CAFE_NAME . '</h1>
            <p style="margin: 5px 0 0; opacity: 0.9;">Smart Dining Experience</p>
        </div>
        <div style="padding: 30px 20px; background: white; border-bottom: 1px solid #E5D4C1;">
            ' . $message . '
        </div>
        <div style="text-align: center; padding: 20px; color: #999; font-size: 12px;">
            <p>This is an automated email from ' . CAFE_NAME . '. Please do not reply.</p>
            <p>&copy; ' . date('Y') . ' ' . CAFE_NAME . '. All rights reserved.</p>
        </div>
    </body>
    </html>
    ';
    
    $result = mail($to, $subject, $html, $headers);
    error_log("[Email] Sent to {$to} - Subject: {$subject} - Result: " . ($result ? "Success" : "Failed"));
    return $result;
}

/**
 * Send Reservation Email
 * @param string $to Customer email
 * @param string $name Customer name
 * @param string $date Reservation date (Y-m-d)
 * @param string $time Reservation time (H:i:s)
 * @param int $guests Number of guests
 * @param string $table Table number
 * @param string $status confirmed, cancelled, or pending
 */
function sendReservationEmail($to, $name, $date, $time, $guests, $table, $status) {
    $dateFormatted = date('l, F j, Y', strtotime($date));
    $timeFormatted = date('g:i A', strtotime($time));
    
    if ($status == 'confirmed') {
        $message = "
            <h2 style=\"color: #8B4513; margin-top: 0;\">✅ Reservation Confirmed!</h2>
            <p>Dear <strong>" . htmlspecialchars($name) . "</strong>,</p>
            <p>Great news! Your reservation at <strong>" . CAFE_NAME . "</strong> has been <strong style=\"color: #10b981;\">confirmed</strong>!</p>
            <div style=\"background: #f5f5f5; padding: 15px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #10b981;\">
                <p style=\"margin: 5px 0;\"><strong>📅 Date:</strong> $dateFormatted</p>
                <p style=\"margin: 5px 0;\"><strong>⏰ Time:</strong> $timeFormatted</p>
                <p style=\"margin: 5px 0;\"><strong>👥 Guests:</strong> $guests</p>
                <p style=\"margin: 5px 0;\"><strong>🪑 Table:</strong> $table</p>
            </div>
            <p>We look forward to serving you! Please arrive on time for your reservation.</p>
            <p>While you wait, you can <a href=\"" . SITE_URL . "/customer_dashboard.php#menu\" style=\"background: #8B4513; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;\">Browse Our Menu</a></p>
        ";
        $subject = "✅ Reservation Confirmed - " . CAFE_NAME;
    } 
    elseif ($status == 'cancelled') {
        $message = "
            <h2 style=\"color: #8B4513; margin-top: 0;\">❌ Reservation Cancelled</h2>
            <p>Dear <strong>" . htmlspecialchars($name) . "</strong>,</p>
            <p>Your reservation for <strong>$dateFormatted at $timeFormatted</strong> has been <strong style=\"color: #dc2626;\">cancelled</strong>.</p>
            <div style=\"background: #f5f5f5; padding: 15px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #dc2626;\">
                <p style=\"margin: 5px 0;\"><strong>📅 Date:</strong> $dateFormatted</p>
                <p style=\"margin: 5px 0;\"><strong>⏰ Time:</strong> $timeFormatted</p>
                <p style=\"margin: 5px 0;\"><strong>👥 Guests:</strong> $guests</p>
                <p style=\"margin: 5px 0;\"><strong>🪑 Table:</strong> $table</p>
            </div>
            <p>If you didn't request this cancellation, please contact us immediately.</p>
            <p><a href=\"" . SITE_URL . "/customer_dashboard.php#reserve\" style=\"background: #8B4513; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;\">Book a New Reservation</a></p>
        ";
        $subject = "❌ Reservation Cancelled - " . CAFE_NAME;
    }
    else {
        $message = "
            <h2 style=\"color: #8B4513; margin-top: 0;\">📋 Reservation Request Received</h2>
            <p>Dear <strong>" . htmlspecialchars($name) . "</strong>,</p>
            <p>Thank you for choosing " . CAFE_NAME . "! We've received your reservation request.</p>
            <div style=\"background: #f5f5f5; padding: 15px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #f59e0b;\">
                <p style=\"margin: 5px 0;\"><strong>📅 Date:</strong> $dateFormatted</p>
                <p style=\"margin: 5px 0;\"><strong>⏰ Time:</strong> $timeFormatted</p>
                <p style=\"margin: 5px 0;\"><strong>👥 Guests:</strong> $guests</p>
                <p style=\"margin: 5px 0;\"><strong>🪑 Table:</strong> $table</p>
            </div>
            <p>Your reservation is currently <strong style=\"color: #f59e0b;\">pending admin approval</strong>.</p>
            <p>You'll receive another email once your reservation is confirmed.</p>
        ";
        $subject = "📋 Reservation Request Received - " . CAFE_NAME;
    }
    
    return sendSmartCafeEmail($to, $subject, $message);
}

/**
 * Send Order Email
 * @param string $to Customer email
 * @param string $name Customer name
 * @param string $orderRef Order reference number
 * @param array $items Array of order items
 * @param float $total Total amount
 * @param string $status preparing, ready, or completed
 */
function sendOrderEmail($to, $name, $orderRef, $items, $total, $status) {
    $itemsHtml = "<ul style='margin: 0; padding-left: 20px;'>";
    foreach ($items as $item) {
        $itemsHtml .= "<li style='margin: 8px 0;'>{$item['quantity']}× " . htmlspecialchars($item['item_name']) . " - <strong>$" . number_format($item['subtotal'], 2) . "</strong></li>";
    }
    $itemsHtml .= "</ul>";
    
    $totalFormatted = number_format($total, 2);
    
    if ($status == 'preparing') {
        $message = "
            <h2 style=\"color: #8B4513; margin-top: 0;\">👨‍🍳 Order Being Prepared</h2>
            <p>Dear <strong>" . htmlspecialchars($name) . "</strong>,</p>
            <p>Great news! Your order <strong>$orderRef</strong> is now being prepared by our kitchen staff.</p>
            <div style=\"background: #f5f5f5; padding: 15px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #3b82f6;\">
                <h3 style=\"margin-top: 0; color: #8B4513;\">Order Details</h3>
                $itemsHtml
                <p style=\"margin-top: 10px; padding-top: 10px; border-top: 1px solid #ddd;\"><strong>Total:</strong> $$totalFormatted</p>
            </div>
            <p>We'll notify you as soon as your order is ready for pickup.</p>
        ";
        $subject = "👨‍🍳 Order Being Prepared - $orderRef";
    }
    elseif ($status == 'ready') {
        $message = "
            <h2 style=\"color: #8B4513; margin-top: 0;\">✅ Order Ready for Pickup!</h2>
            <p>Dear <strong>" . htmlspecialchars($name) . "</strong>,</p>
            <p>Your order <strong>$orderRef</strong> is <strong style=\"color: #10b981;\">ready for pickup</strong>!</p>
            <div style=\"background: #f5f5f5; padding: 15px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #10b981;\">
                <h3 style=\"margin-top: 0; color: #8B4513;\">Order Details</h3>
                $itemsHtml
                <p style=\"margin-top: 10px; padding-top: 10px; border-top: 1px solid #ddd;\"><strong>Total:</strong> $$totalFormatted</p>
            </div>
            <p>Please come to the counter to collect your order. Thank you for choosing " . CAFE_NAME . "!</p>
        ";
        $subject = "✅ Order Ready for Pickup - $orderRef";
    }
    elseif ($status == 'completed') {
        $message = "
            <h2 style=\"color: #8B4513; margin-top: 0;\">🎉 Order Completed</h2>
            <p>Dear <strong>" . htmlspecialchars($name) . "</strong>,</p>
            <p>Your order <strong>$orderRef</strong> has been marked as <strong style=\"color: #10b981;\">completed</strong>.</p>
            <div style=\"background: #f5f5f5; padding: 15px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #10b981;\">
                <h3 style=\"margin-top: 0; color: #8B4513;\">Order Summary</h3>
                $itemsHtml
                <p style=\"margin-top: 10px; padding-top: 10px; border-top: 1px solid #ddd;\"><strong>Total Paid:</strong> $$totalFormatted</p>
            </div>
            <p>Thank you for dining with us! We hope to see you again soon.</p>
            <p><a href=\"" . SITE_URL . "/customer_dashboard.php#reserve\" style=\"background: #8B4513; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;\">Book Your Next Visit</a></p>
        ";
        $subject = "🎉 Order Completed - Thank You! - $orderRef";
    }
    else {
        return false;
    }
    
    return sendSmartCafeEmail($to, $subject, $message);
}

/**
 * Send Queue Email
 * @param string $to Customer email
 * @param string $name Customer name
 * @param int $position Queue position
 * @param int $partySize Number of people
 * @param int $waitTime Estimated wait time in minutes
 * @param string $status joined or ready
 */
function sendQueueEmail($to, $name, $position, $partySize, $waitTime, $status) {
    if ($status == 'joined') {
        $message = "
            <h2 style=\"color: #8B4513; margin-top: 0;\">⏱️ You're in the Queue!</h2>
            <p>Dear <strong>" . htmlspecialchars($name) . "</strong>,</p>
            <p>You've successfully joined the waiting queue at " . CAFE_NAME . "!</p>
            <div style=\"background: #f5f5f5; padding: 15px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #f59e0b;\">
                <p style=\"margin: 5px 0;\"><strong>📍 Position:</strong> #$position</p>
                <p style=\"margin: 5px 0;\"><strong>⏰ Estimated Wait:</strong> $waitTime minutes</p>
                <p style=\"margin: 5px 0;\"><strong>👥 Party Size:</strong> $partySize</p>
            </div>
            <p>We'll notify you via email and in-app notification when your table is ready!</p>
            <p><a href=\"" . SITE_URL . "/customer_dashboard.php#menu\" style=\"background: #8B4513; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;\">Pre-order While You Wait</a></p>
        ";
        $subject = "⏱️ You're in the Queue - " . CAFE_NAME;
    }
    elseif ($status == 'ready') {
        $message = "
            <h2 style=\"color: #8B4513; margin-top: 0;\">✅ Your Table is Ready!</h2>
            <p>Dear <strong>" . htmlspecialchars($name) . "</strong>,</p>
            <p>Good news! Your table is now <strong style=\"color: #10b981;\">ready</strong> at " . CAFE_NAME . ".</p>
            <div style=\"background: #f5f5f5; padding: 15px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #10b981;\">
                <p style=\"margin: 5px 0;\"><strong>👥 Party Size:</strong> $partySize</p>
            </div>
            <p>Please proceed to the host station. Our staff will assist you.</p>
            <p>If you have any pre-orders, they will be brought to your table shortly.</p>
        ";
        $subject = "✅ Your Table is Ready - " . CAFE_NAME;
    }
    else {
        return false;
    }
    
    return sendSmartCafeEmail($to, $subject, $message);
}
?>