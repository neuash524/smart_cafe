<?php
/**
 * Smart Café API — login.php
 * POST: Authenticate user against MySQL users table
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
$email = sanitizeInput($body['email'] ?? '');
$password = $body['password'] ?? '';

if (!$email || !$password) {
    sendResponse(false, 'Email and password are required.');
}

try {
    $pdo = getDBConnection();
    
    $user = fetchOne(
        'SELECT user_id, email, password_hash, full_name, phone, user_type, is_active
         FROM users WHERE email = ?',
        [$email]
    );
    
    if (!$user || !$user['is_active']) {
        sendResponse(false, 'Invalid email or password.');
    }
    
    if (!verifyPassword($password, $user['password_hash'])) {
        sendResponse(false, 'Invalid email or password.');
    }
    
    // Update last_login
    $pdo->prepare('UPDATE users SET last_login = NOW() WHERE user_id = ?')
        ->execute([$user['user_id']]);
    
    sendResponse(true, 'Login successful', [
        'user' => [
            'user_id' => (int)$user['user_id'],
            'email' => $user['email'],
            'full_name' => $user['full_name'],
            'phone' => $user['phone'],
            'user_type' => $user['user_type']
        ]
    ]);
    
} catch (Exception $e) {
    sendResponse(false, 'Login failed: ' . $e->getMessage());
}
?>