<?php
/**
 * Smart Café Email Sender - NO PHPMailer REQUIRED
 * Uses PHP's built-in mail() function
 */

define('CAFE_NAME', 'Smart Café');
define('SITE_URL', 'http://localhost/smart_cafe');
define('EMAIL_LOG_FILE', __DIR__ . '/email_log.txt');
define('ADMIN_EMAIL', 'neupaneaash@gmail.com');

function sendSmartCafeEmail($to, $subject, $message) {
    // Validate email
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        logEmailToFile($to, $subject, $message, "INVALID_EMAIL");
        return false;
    }
    
    logEmailToFile($to, $subject, $message, "ATTEMPT");
    
    // Email headers
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . CAFE_NAME . " <" . ADMIN_EMAIL . ">\r\n";
    $headers .= "Reply-To: " . ADMIN_EMAIL . "\r\n";
    
    // Try to send
    $result = @mail($to, $subject, $message, $headers);
    
    if ($result) {
        logEmailToFile($to, $subject, $message, "SUCCESS");
        return true;
    } else {
        logEmailToFile($to, $subject, $message, "MAIL_FAILED");
        return false;
    }
}

function logEmailToFile($to, $subject, $message, $status) {
    $logEntry = "========================================\n";
    $logEntry .= "Time: " . date('Y-m-d H:i:s') . "\n";
    $logEntry .= "Status: {$status}\n";
    $logEntry .= "To: {$to}\n";
    $logEntry .= "Subject: {$subject}\n";
    $logEntry .= "Message Preview: " . substr(strip_tags($message), 0, 200) . "\n";
    $logEntry .= "========================================\n\n";
    
    file_put_contents(EMAIL_LOG_FILE, $logEntry, FILE_APPEND);
}

function sendReservationEmail($to, $name, $date, $time, $guests, $table, $status) {
    $dateFormatted = date('l, F j, Y', strtotime($date));
    $timeFormatted = date('g:i A', strtotime($time));
    $statusText = ($status == 'confirmed') ? 'Confirmed' : 'Cancelled';
    $statusColor = ($status == 'confirmed') ? '#10b981' : '#dc2626';
    $statusEmoji = ($status == 'confirmed') ? '✅' : '❌';
    
    $message = "<!DOCTYPE html>
<html>
<head>
<meta charset='UTF-8'>
<title>Reservation {$statusText}</title>
<style>
body{font-family:Arial,sans-serif;}
.container{max-width:600px;margin:0 auto;}
.header{background:#8B4513;color:white;padding:20px;text-align:center;}
.content{padding:20px;}
.info-box{background:#f8f3ed;padding:15px;margin:15px 0;border-radius:8px;border-left:4px solid {$statusColor};}
.button{background:#8B4513;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;}
</style>
</head>
<body>
<div class='container'>
<div class='header'><h2>☕ Smart Café</h2></div>
<div class='content'>
<h2 style='color:#8B4513;'>{$statusEmoji} Reservation {$statusText}!</h2>
<p>Dear <strong>" . htmlspecialchars($name) . "</strong>,</p>
<p>Your reservation has been <strong style='color:{$statusColor};'>{$statusText}</strong>.</p>
<div class='info-box'>
<p><strong>📅 Date:</strong> {$dateFormatted}</p>
<p><strong>⏰ Time:</strong> {$timeFormatted}</p>
<p><strong>👥 Guests:</strong> {$guests}</p>
<p><strong>🪑 Table:</strong> " . htmlspecialchars($table) . "</p>
</div>
<p><a href='" . SITE_URL . "/customer_dashboard.php' class='button'>View Dashboard</a></p>
</div>
</div>
</body>
</html>";
    
    $subject = "{$statusEmoji} Reservation {$statusText} - Smart Café";
    return sendSmartCafeEmail($to, $subject, $message);
}

function sendOrderEmail($to, $name, $orderRef, $items, $total, $status) {
    $statusText = $status == 'preparing' ? 'Being Prepared' : ($status == 'ready' ? 'Ready for Pickup' : 'Completed');
    $statusEmoji = $status == 'preparing' ? '👨‍🍳' : ($status == 'ready' ? '✅' : '🎉');
    
    $message = "<!DOCTYPE html>
<html>
<head><meta charset='UTF-8'><title>Order Update</title></head>
<body>
<h2>{$statusEmoji} Order Update</h2>
<p>Dear <strong>" . htmlspecialchars($name) . "</strong>,</p>
<p>Your order <strong>{$orderRef}</strong> is <strong>{$statusText}</strong></p>
<p>Total: <strong>\${$total}</strong></p>
<p><a href='" . SITE_URL . "/customer_dashboard.php'>View Orders</a></p>
</body>
</html>";
    
    $subject = "{$statusEmoji} Order {$statusText} - {$orderRef}";
    return sendSmartCafeEmail($to, $subject, $message);
}

function sendQueueEmail($to, $name, $position, $partySize, $waitTime, $status) {
    $message = "<!DOCTYPE html>
<html>
<head><meta charset='UTF-8'><title>Table Ready</title></head>
<body>
<h2>✅ Your Table is Ready!</h2>
<p>Dear <strong>" . htmlspecialchars($name) . "</strong>,</p>
<p>Your table is now ready for <strong>{$partySize} people</strong>!</p>
<p>Please proceed to the host station.</p>
<p><a href='" . SITE_URL . "/customer_dashboard.php'>View Dashboard</a></p>
</body>
</html>";
    
    $subject = "✅ Your Table is Ready - Smart Café";
    return sendSmartCafeEmail($to, $subject, $message);
}
?>