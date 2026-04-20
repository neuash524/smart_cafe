<?php
/**
 * Test Email Configuration
 * Run this file to test if emails are working
 */

require_once 'email_sender.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Email Test - Smart Café</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .section { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; }
        input, button { padding: 10px; margin: 5px; }
    </style>
</head>
<body>
    <h1>📧 Smart Café Email Test</h1>
    
    <div class='section'>
        <h2>Test Email Sending</h2>
        <form method='POST'>
            <label>Recipient Email:</label>
            <input type='email' name='test_email' placeholder='your@email.com' required>
            <button type='submit' name='send_test'>Send Test Email</button>
        </form>
    </div>";

if (isset($_POST['send_test']) && isset($_POST['test_email'])) {
    $testEmail = $_POST['test_email'];
    
    echo "<div class='section'>";
    echo "<h3>Test Results for: " . htmlspecialchars($testEmail) . "</h3>";
    
    // Test 1: Basic email
    $testMessage = "<!DOCTYPE html>
    <html>
    <body>
        <h2>Smart Café Email Test</h2>
        <p>This is a test email from your Smart Café system.</p>
        <p>If you received this, your email configuration is working correctly!</p>
        <p>Time sent: " . date('Y-m-d H:i:s') . "</p>
    </body>
    </html>";
    
    $result = sendSmartCafeEmail($testEmail, "🧪 Smart Café Email Test", $testMessage);
    
    if ($result) {
        echo "<p class='success'>✅ Test email sent successfully to " . htmlspecialchars($testEmail) . "</p>";
    } else {
        echo "<p class='error'>❌ Failed to send test email. Check your PHP mail() configuration.</p>";
    }
    
    // Show log file
    if (file_exists('email_log.txt')) {
        echo "<details>";
        echo "<summary>View Email Log (last 10 entries)</summary>";
        $logContent = file_get_contents('email_log.txt');
        $lines = explode("\n", $logContent);
        $lastLines = array_slice($lines, -50);
        echo "<pre style='background:#f0f0f0;padding:10px;overflow:auto;max-height:300px;'>";
        echo htmlspecialchars(implode("\n", $lastLines));
        echo "</pre>";
        echo "</details>";
    }
    
    echo "</div>";
}

echo "
    <div class='section'>
        <h3>Email Configuration Info</h3>
        <ul>
            <li><strong>PHP Version:</strong> " . phpversion() . "</li>
            <li><strong>Mail Function:</strong> " . (function_exists('mail') ? '✅ Available' : '❌ Not available') . "</li>
            <li><strong>Log File:</strong> " . (file_exists('email_log.txt') ? '✅ Exists' : '❌ Not found') . "</li>
        </ul>
    </div>
    
    <div class='section'>
        <h3>Troubleshooting Tips</h3>
        <ul>
            <li>For local development (XAMPP/WAMP), email may not work without SMTP configuration</li>
            <li>For production, configure SMTP settings or use a service like SendGrid, Mailgun, or PHPMailer with Gmail SMTP</li>
            <li>Check your spam/junk folder for test emails</li>
            <li>View email_log.txt for detailed send attempts</li>
        </ul>
    </div>
</body>
</html>";
?>