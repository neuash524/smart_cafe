<?php
/**
 * Smart Café API — register.php
 * POST: Register a new customer user
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Method not allowed');
}

$body = json_decode(file_get_contents('php://input'), true);
$fullName = sanitizeInput($body['full_name'] ?? '');
$email = sanitizeInput($body['email'] ?? '');
$phone = sanitizeInput($body['phone'] ?? '');
$password = $body['password'] ?? '';

if (!$fullName || !$email || !$password) {
    sendResponse(false, 'Full name, email and password are required.');
}

if (!isValidEmail($email)) {
    sendResponse(false, 'Invalid email address.');
}

if (strlen($password) < 6) {
    sendResponse(false, 'Password must be at least 6 characters.');
}

try {
    $pdo = getDBConnection();
    
    // Check if email exists
    $check = $pdo->prepare('SELECT user_id FROM users WHERE email = ?');
    $check->execute([$email]);
    if ($check->fetch()) {
        sendResponse(false, 'An account with this email already exists.');
    }
    
    // Insert new user
    $hash = hashPassword($password);
    $sql = 'INSERT INTO users (email, password_hash, full_name, phone, user_type, is_active, created_at)
            VALUES (?, ?, ?, ?, "customer", 1, NOW())';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email, $hash, $fullName, $phone]);
    $userId = $pdo->lastInsertId();
    
    // Return new user with ID
    $user = fetchOne('SELECT user_id, email, full_name, phone, user_type, created_at FROM users WHERE user_id = ?', [$userId]);
    
    sendResponse(true, 'Account created successfully!', ['user' => $user]);
    
} catch (Exception $e) {
    error_log("[Register] Error: " . $e->getMessage());
    sendResponse(false, 'Registration failed: ' . $e->getMessage());
}
?>