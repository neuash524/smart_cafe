<?php
// test_email.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>📧 Testing Email System</h1>";

// Check if PHPMailer files exist
$files = [
    'PHPMailer-master/src/PHPMailer.php',
    'PHPMailer-master/src/SMTP.php',
    'PHPMailer-master/src/Exception.php'
];

echo "<h3>Checking PHPMailer Files:</h3>";
foreach ($files as $file) {
    $fullPath = __DIR__ . '/' . $file;
    if (file_exists($fullPath)) {
        echo "<p style='color:green'>✅ Found: " . $file . "</p>";
    } else {
        echo "<p style='color:red'>❌ Missing: " . $file . "</p>";
        echo "<p>Full path tried: " . $fullPath . "</p>";
    }
}

// Include email sender
require_once 'email_sender.php';

echo "<h3>Sending Test Email:</h3>";

// Send to YOUR email address
$testEmail = "bladin397@gmail.com";  // Your email

$result = sendSmartCafeEmail(
    $testEmail,
    "🧪 Smart Café Test - " . date('Y-m-d H:i:s'),
    "<h2 style='color:#8B4513;'>✅ Email Working!</h2>
     <p>This is a test email from your Smart Café system.</p>
     <p><strong>Time sent:</strong> " . date('Y-m-d H:i:s') . "</p>
     <p>Your email notifications for reservations and orders will now work!</p>"
);

if ($result) {
    echo "<p style='color:green;font-size:18px;font-weight:bold;'>✅ SUCCESS! Email sent to {$testEmail}</p>";
    echo "<p>Check your inbox (and spam folder).</p>";
} else {
    echo "<p style='color:red;font-size:18px;font-weight:bold;'>❌ FAILED to send email</p>";
}

// Show log
echo "<h3>📋 Email Log:</h3>";
if (file_exists('email_log.txt')) {
    echo "<pre style='background:#f0f0f0;padding:10px;max-height:300px;overflow:auto;font-size:11px;'>";
    echo htmlspecialchars(file_get_contents('email_log.txt'));
    echo "</pre>";
} else {
    echo "<p>No log file yet. Check after sending.</p>";
}
?>