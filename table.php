<?php
/**
 * Smart Café API — tables.php
 * GET    : Fetch tables
 * PATCH  : Update table status
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getDBConnection();
    
    // GET - Fetch all tables
    if ($method === 'GET') {
        $tables = fetchAll('SELECT * FROM cafe_tables ORDER BY table_id');
        sendResponse(true, 'OK', ['tables' => $tables]);
    }
    
    // PATCH - Update table status
    if ($method === 'PATCH') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        $b = json_decode(file_get_contents('php://input'), true);
        $status = sanitizeInput($b['status'] ?? '');
        $allowed = ['available', 'occupied', 'reserved', 'maintenance'];
        
        if (!$id || !in_array($status, $allowed)) {
            sendResponse(false, 'Invalid request. Table ID and valid status required.');
        }
        
        // Check if table exists
        $table = fetchOne('SELECT * FROM cafe_tables WHERE table_id = ?', [$id]);
        if (!$table) {
            sendResponse(false, 'Table not found');
        }
        
        // Update table status
        $pdo->prepare('UPDATE cafe_tables SET status = ? WHERE table_id = ?')
            ->execute([$status, $id]);
        
        // Log the activity
        logActivity(null, 'UPDATE', 'cafe_tables', $id,
                   "Table {$table['table_number']} status changed from {$table['status']} to {$status}");
        
        sendResponse(true, "Table {$table['table_number']} marked as {$status}", [
            'table' => [
                'table_id' => $id,
                'table_number' => $table['table_number'],
                'status' => $status
            ]
        ]);
    }
    
} catch (Exception $e) {
    error_log("[Tables] Error: " . $e->getMessage());
    sendResponse(false, 'Server error: ' . $e->getMessage());
}
?>