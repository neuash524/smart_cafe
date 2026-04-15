<?php
/**
 * Smart Café Email Sender - FULL WORKING VERSION
 * Uses PHPMailer with Gmail SMTP to send real emails
 */

// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/src/PHPMailer.php';
require_once __DIR__ . '/src/SMTP.php';
require_once __DIR__ . '/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

define('CAFE_NAME', 'Smart Café');
define('SITE_URL', 'http://localhost/smart-cafe');

// Email log file path (for debugging)
define('EMAIL_LOG_FILE', __DIR__ . '/email_log.txt');

// ============================================================
// GMAIL SMTP CONFIGURATION - YOUR CREDENTIALS
// ============================================================
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'neupaneaash@gmail.com');      // YOUR GMAIL
define('SMTP_PASS', 'wbtd elie qkhv flsw');        // YOUR APP PASSWORD
define('SMTP_FROM_EMAIL', 'neupaneaash@gmail.com');
define('SMTP_FROM_NAME', 'Smart Café');

/**
 * Send email using PHPMailer with Gmail SMTP
 */
function sendSmartCafeEmail($to, $subject, $message) {
    // Validate email
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        error_log("[Email] Invalid email: {$to}");
        logEmailToFile($to, $subject, $message, "INVALID_EMAIL");
        return false;
    }
    
    // Log to file first
    logEmailToFile($to, $subject, $message, "ATTEMPT");
    
    try {
        $mail = new PHPMailer(true);
        
        // Enable SMTP debugging (0=off, 1=errors, 2=full)
        $mail->SMTPDebug = 0;  // Set to 2 for testing
        
        // SMTP configuration
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        
        // Sender & Recipient
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);
        $mail->addReplyTo(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        
        // Email content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->AltBody = strip_tags($message); // Plain text fallback
        
        // Send
        $mail->send();
        
        error_log("[Email] SUCCESS sent to {$to} - {$subject}");
        logEmailToFile($to, $subject, $message, "SUCCESS");
        return true;
        
    } catch (Exception $e) {
        error_log("[Email] FAILED to send to {$to}: " . $mail->ErrorInfo);
        logEmailToFile($to, $subject, $message, "FAILED: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Log email to file for debugging
 */
function logEmailToFile($to, $subject, $message, $status) {
    $logEntry = "========================================\n";
    $logEntry .= "Time: " . date('Y-m-d H:i:s') . "\n";
    $logEntry .= "Status: {$status}\n";
    $logEntry .= "To: {$to}\n";
    $logEntry .= "Subject: {$subject}\n";
    $logEntry .= "Message Preview: " . substr(strip_tags($message), 0, 300) . "\n";
    $logEntry .= "========================================\n\n";
    
    file_put_contents(EMAIL_LOG_FILE, $logEntry, FILE_APPEND);
}

/**
 * Send Reservation Confirmation/Cancellation Email
 */
function sendReservationEmail($to, $name, $date, $time, $guests, $table, $status) {
    $dateFormatted = date('l, F j, Y', strtotime($date));
    $timeFormatted = date('g:i A', strtotime($time));
    
    if ($status == 'confirmed') {
        $message = "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <title>Reservation Confirmed</title>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; background: #fff; }
                    .header { background: #8B4513; color: white; padding: 25px; text-align: center; }
                    .header h2 { margin: 0; font-size: 24px; }
                    .content { padding: 30px; }
                    .info-box { background: #f8f3ed; padding: 20px; margin: 20px 0; border-radius: 12px; border-left: 4px solid #10b981; }
                    .info-item { margin-bottom: 12px; }
                    .info-label { font-weight: bold; color: #8B4513; width: 80px; display: inline-block; }
                    .button { background: #8B4513; color: white; padding: 12px 25px; text-decoration: none; border-radius: 8px; display: inline-block; margin-top: 15px; }
                    .footer { text-align: center; padding: 20px; border-top: 1px solid #eee; color: #999; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>☕ Smart Café</h2>
                    </div>
                    <div class='content'>
                        <h2 style='color: #8B4513; margin-top: 0;'>✅ Reservation Confirmed!</h2>
                        <p>Dear <strong>" . htmlspecialchars($name) . "</strong>,</p>
                        <p>Great news! Your reservation at <strong>Smart Café</strong> has been <strong style='color: #10b981;'>confirmed</strong>!</p>
                        <div class='info-box'>
                            <div class='info-item'><span class='info-label'>📅 Date:</span> {$dateFormatted}</div>
                            <div class='info-item'><span class='info-label'>⏰ Time:</span> {$timeFormatted}</div>
                            <div class='info-item'><span class='info-label'>👥 Guests:</span> {$guests}</div>
                            <div class='info-item'><span class='info-label'>🪑 Table:</span> " . htmlspecialchars($table) . "</div>
                        </div>
                        <p>We look forward to serving you! Please arrive on time for your reservation.</p>
                        <p style='text-align: center;'>
                            <a href='" . SITE_URL . "/customer_dashboard.php#menu' class='button' style='background: #8B4513; color: white; padding: 12px 25px; text-decoration: none; border-radius: 8px; display: inline-block;'>
                                Browse Our Menu
                            </a>
                        </p>
                        <p style='margin-top: 25px; font-size: 14px; color: #666;'>
                            Need to make changes? Please contact us at least 2 hours before your reservation time.
                        </p>
                    </div>
                    <div class='footer'>
                        <p>Smart Café | 123 Café Street | +1 234 567 8900</p>
                        <p>&copy; " . date('Y') . " Smart Café. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        $subject = "✅ Reservation Confirmed - Smart Café";
    } 
    elseif ($status == 'cancelled') {
        $message = "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <title>Reservation Cancelled</title>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; }
                    .container { max-width: 600px; margin: 0 auto; }
                    .header { background: #8B4513; color: white; padding: 25px; text-align: center; }
                    .content { padding: 30px; }
                    .info-box { background: #f8f3ed; padding: 20px; margin: 20px 0; border-radius: 12px; border-left: 4px solid #dc2626; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>☕ Smart Café</h2>
                    </div>
                    <div class='content'>
                        <h2 style='color: #8B4513;'>❌ Reservation Cancelled</h2>
                        <p>Dear <strong>" . htmlspecialchars($name) . "</strong>,</p>
                        <p>Your reservation has been <strong style='color: #dc2626;'>cancelled</strong> as requested.</p>
                        <div class='info-box'>
                            <p><strong>📅 Date:</strong> {$dateFormatted}</p>
                            <p><strong>⏰ Time:</strong> {$timeFormatted}</p>
                            <p><strong>👥 Guests:</strong> {$guests}</p>
                            <p><strong>🪑 Table:</strong> " . htmlspecialchars($table) . "</p>
                        </div>
                        <p>If you did not request this cancellation, please contact us immediately.</p>
                        <p style='text-align: center;'>
                            <a href='" . SITE_URL . "/customer_dashboard.php#reserve' class='button' style='background: #8B4513; color: white; padding: 12px 25px; text-decoration: none; border-radius: 8px; display: inline-block;'>
                                Book a New Reservation
                            </a>
                        </p>
                    </div>
                </div>
            </body>
            </html>
        ";
        $subject = "❌ Reservation Cancelled - Smart Café";
    }
    else {
        return false;
    }
    
    return sendSmartCafeEmail($to, $subject, $message);
}

/**
 * Send Order Status Update Email
 */
function sendOrderEmail($to, $name, $orderRef, $items, $total, $status) {
    // Build items HTML
    $itemsHtml = "";
    foreach ($items as $item) {
        $itemsHtml .= "<tr>
                            <td style='padding: 8px; border-bottom: 1px solid #eee;'>" . htmlspecialchars($item['item_name']) . "</td>
                            <td style='padding: 8px; border-bottom: 1px solid #eee; text-align: center;'>" . $item['quantity'] . "</td>
                            <td style='padding: 8px; border-bottom: 1px solid #eee; text-align: right;'>$" . number_format($item['subtotal'], 2) . "</td>
                        </tr>";
    }
    
    $statusText = '';
    $statusColor = '';
    $statusEmoji = '';
    
    switch($status) {
        case 'preparing':
            $statusText = 'Being Prepared';
            $statusColor = '#f59e0b';
            $statusEmoji = '👨‍🍳';
            break;
        case 'ready':
            $statusText = 'Ready for Pickup';
            $statusColor = '#10b981';
            $statusEmoji = '✅';
            break;
        case 'completed':
            $statusText = 'Completed';
            $statusColor = '#10b981';
            $statusEmoji = '🎉';
            break;
        case 'cancelled':
            $statusText = 'Cancelled';
            $statusColor = '#dc2626';
            $statusEmoji = '❌';
            break;
        default:
            $statusText = $status;
            $statusColor = '#6b7280';
            $statusEmoji = '📋';
    }
    
    $message = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Order Update</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; }
                .header { background: #8B4513; color: white; padding: 20px; text-align: center; }
                .content { padding: 30px; }
                .status-box { background: #f8f3ed; padding: 15px; border-radius: 8px; margin: 20px 0; text-align: center; }
                .status { font-size: 24px; font-weight: bold; color: {$statusColor}; }
                table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                th { background: #f0f0f0; padding: 10px; text-align: left; }
                .total { font-size: 18px; font-weight: bold; text-align: right; margin-top: 15px; }
                .button { background: #8B4513; color: white; padding: 12px 25px; text-decoration: none; border-radius: 8px; display: inline-block; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>☕ Smart Café</h2>
                </div>
                <div class='content'>
                    <h2 style='color: #8B4513;'>{$statusEmoji} Order Update</h2>
                    <p>Dear <strong>" . htmlspecialchars($name) . "</strong>,</p>
                    <div class='status-box'>
                        <div class='status'>Status: {$statusText}</div>
                        <p>Order Reference: <strong>{$orderRef}</strong></p>
                    </div>
                    
                    <h3>Order Summary</h3>
                    <table>
                        <tr>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Price</th>
                        </tr>
                        {$itemsHtml}
                    </table>
                    <div class='total'>
                        <strong>Total: $" . number_format($total, 2) . "</strong>
                    </div>
                    
                    <p style='text-align: center; margin-top: 30px;'>
                        <a href='" . SITE_URL . "/customer_dashboard.php' class='button' style='background: #8B4513; color: white; padding: 12px 25px; text-decoration: none; border-radius: 8px; display: inline-block;'>
                            View My Orders
                        </a>
                    </p>
                </div>
            </div>
        </body>
        </html>
    ";
    
    $subject = "{$statusEmoji} Order {$statusText} - {$orderRef}";
    return sendSmartCafeEmail($to, $subject, $message);
}

/**
 * Send Queue Ready Email
 */
function sendQueueEmail($to, $name, $position, $partySize, $waitTime, $status) {
    if ($status == 'ready') {
        $message = "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <title>Your Table is Ready!</title>
                <style>
                    body { font-family: Arial, sans-serif; }
                    .container { max-width: 600px; margin: 0 auto; }
                    .header { background: #8B4513; color: white; padding: 20px; text-align: center; }
                    .content { padding: 30px; text-align: center; }
                    .ready-box { background: #d1fae5; padding: 25px; border-radius: 12px; margin: 20px 0; }
                    .ready-icon { font-size: 48px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>☕ Smart Café</h2>
                    </div>
                    <div class='content'>
                        <div class='ready-box'>
                            <div class='ready-icon'>✅</div>
                            <h2 style='color: #065f46;'>Your Table is Ready!</h2>
                            <p>Dear <strong>" . htmlspecialchars($name) . "</strong>,</p>
                            <p>Your table is now ready for you!</p>
                            <p><strong>Party Size:</strong> {$partySize} people</p>
                            <p>Please proceed to the host station within 10 minutes.</p>
                        </div>
                        <p style='margin-top: 20px;'>Thank you for choosing Smart Café!</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        $subject = "✅ Your Table is Ready - Smart Café";
        return sendSmartCafeEmail($to, $subject, $message);
    }
    return false;
}
?>