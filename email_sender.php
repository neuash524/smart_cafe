<?php
/**
 * Smart Café Email Sender - With PHPMailer SMTP Support
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer-master/src/SMTP.php';
require_once __DIR__ . '/PHPMailer-master/src/Exception.php';

define('CAFE_NAME', 'Smart Café');
define('SITE_URL', 'http://localhost/smart_cafe');
define('EMAIL_LOG_FILE', __DIR__ . '/email_log.txt');
define('ADMIN_EMAIL', 'bladin397@gmail.com');

// ============================================================
// SMTP CONFIGURATION - UPDATE THESE VALUES
// ============================================================
define('SMTP_HOST', 'smtp.gmail.com');      // For Gmail
define('SMTP_PORT', 587);                    // 587 for TLS, 465 for SSL
define('SMTP_USER', 'bladin397@gmail.com'); // YOUR Gmail address
define('SMTP_PASS', 'vjvbdksgayrnrxlg');    // Gmail App Password (not your regular password)
define('SMTP_ENCRYPTION', 'tls');            // 'tls' or 'ssl'

function sendSmartCafeEmail($to, $subject, $message) {
    // Validate email
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        logEmailToFile($to, $subject, $message, "INVALID_EMAIL");
        return false;
    }
    
    logEmailToFile($to, $subject, $message, "ATTEMPT");
    
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = SMTP_ENCRYPTION;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';
        
        // Recipients
        $mail->setFrom(SMTP_USER, CAFE_NAME);
        $mail->addAddress($to);
        $mail->addReplyTo(ADMIN_EMAIL, CAFE_NAME);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->AltBody = strip_tags($message);
        
        $mail->send();
        logEmailToFile($to, $subject, $message, "SUCCESS");
        return true;
        
    } catch (Exception $e) {
        logEmailToFile($to, $subject, $message, "SMTP_ERROR: " . $mail->ErrorInfo);
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
body{font-family:Arial,sans-serif;margin:0;padding:0;background:#f5f0ea;}
.container{max-width:600px;margin:20px auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.1);}
.header{background:#8B4513;color:white;padding:25px;text-align:center;}
.header h2{margin:0;font-size:24px;}
.content{padding:30px;}
.info-box{background:#f8f3ed;padding:20px;margin:20px 0;border-radius:12px;border-left:4px solid {$statusColor};}
.info-box p{margin:8px 0;font-size:16px;}
.button{background:#8B4513;color:white;padding:12px 25px;text-decoration:none;border-radius:8px;display:inline-block;margin-top:15px;}
.footer{background:#f8f3ed;padding:15px;text-align:center;font-size:12px;color:#9b8070;}
</style>
</head>
<body>
<div class='container'>
<div class='header'>
<h2>☕ Smart Café</h2>
</div>
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
<div class='footer'>
<p>Smart Café - Skip the Wait, Savor the Moment</p>
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
    
    // Build items list HTML
    $itemsHtml = "";
    foreach ($items as $item) {
        $itemsHtml .= "<li>{$item['quantity']}× " . htmlspecialchars($item['item_name']) . " - \$" . number_format($item['subtotal'], 2) . "</li>";
    }
    
    $message = "<!DOCTYPE html>
<html>
<head><meta charset='UTF-8'><title>Order Update</title>
<style>
body{font-family:Arial,sans-serif;background:#f5f0ea;}
.container{max-width:600px;margin:20px auto;background:#fff;border-radius:16px;overflow:hidden;}
.header{background:#8B4513;color:white;padding:25px;text-align:center;}
.content{padding:30px;}
.items-box{background:#f8f3ed;padding:20px;border-radius:12px;margin:15px 0;}
.button{background:#8B4513;color:white;padding:12px 25px;text-decoration:none;border-radius:8px;display:inline-block;}
</style>
</head>
<body>
<div class='container'>
<div class='header'>
<h2>{$statusEmoji} Order Update</h2>
</div>
<div class='content'>
<p>Dear <strong>" . htmlspecialchars($name) . "</strong>,</p>
<p>Your order <strong>{$orderRef}</strong> is <strong>{$statusText}</strong>.</p>
<div class='items-box'>
<h4>Order Items:</h4>
<ul>{$itemsHtml}</ul>
<p><strong>Total: \$" . number_format($total, 2) . "</strong></p>
</div>
<p><a href='" . SITE_URL . "/customer_dashboard.php' class='button'>View Orders</a></p>
</div>
</div>
</body>
</html>";
    
    $subject = "{$statusEmoji} Order {$statusText} - {$orderRef}";
    return sendSmartCafeEmail($to, $subject, $message);
}

function sendQueueEmail($to, $name, $position, $partySize, $waitTime, $status) {
    $message = "<!DOCTYPE html>
<html>
<head><meta charset='UTF-8'><title>Table Ready</title>
<style>
body{font-family:Arial,sans-serif;background:#f5f0ea;}
.container{max-width:600px;margin:20px auto;background:#fff;border-radius:16px;overflow:hidden;}
.header{background:#8B4513;color:white;padding:25px;text-align:center;}
.content{padding:30px;}
.button{background:#8B4513;color:white;padding:12px 25px;text-decoration:none;border-radius:8px;display:inline-block;}
</style>
</head>
<body>
<div class='container'>
<div class='header'>
<h2>✅ Your Table is Ready!</h2>
</div>
<div class='content'>
<p>Dear <strong>" . htmlspecialchars($name) . "</strong>,</p>
<p>Your table is now ready for <strong>{$partySize} people</strong>!</p>
<p>Please proceed to the host station.</p>
<p><a href='" . SITE_URL . "/customer_dashboard.php' class='button'>View Dashboard</a></p>
</div>
</div>
</body>
</html>";
    
    $subject = "✅ Your Table is Ready - Smart Café";
    return sendSmartCafeEmail($to, $subject, $message);
}
?>