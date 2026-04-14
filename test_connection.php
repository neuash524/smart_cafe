<?php
/**
 * Test Database Connection - UPDATED to show actual MySQL data
 */

require_once 'config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Smart Café Database Test</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .section { background: white; padding: 15px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        pre { background: #f0f0f0; padding: 10px; overflow: auto; max-height: 300px; }
        h2, h3, h4 { color: #8B4513; margin-top: 0; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f0f0f0; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 12px; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-error { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <h1>🔧 Smart Café Database & API Test</h1>";

try {
    $pdo = getDBConnection();
    echo "<div class='section'>";
    echo "<h2>📊 Database Connection</h2>";
    echo "<p class='success'>✅ Database connection successful!</p>";
    echo "<p><strong>Host:</strong> " . DB_HOST . ":" . DB_PORT . "</p>";
    echo "<p><strong>Database:</strong> " . DB_NAME . "</p>";
    echo "</div>";
    
    // Test all tables and show actual data from MySQL
    $tables = ['users', 'cafe_tables', 'reservations', 'queue', 'menu_items', 'orders', 'order_items', 'payments', 'notifications', 'activity_logs'];
    
    echo "<div class='section'>";
    echo "<h2>📋 Table Status (From MySQL Database)</h2>";
    echo "<table>";
    echo "<tr><th>Table Name</th><th>Record Count</th><th>Sample Data</th></tr>";
    
    foreach ($tables as $table) {
        try {
            // Check if table exists
            $check = $pdo->query("SHOW TABLES LIKE '{$table}'");
            if ($check->rowCount() > 0) {
                $result = $pdo->query("SELECT COUNT(*) as count FROM {$table}")->fetch();
                $count = $result['count'];
                
                // Get sample data
                $sample = $pdo->query("SELECT * FROM {$table} LIMIT 3")->fetchAll();
                
                echo "<tr>";
                echo "<td><strong>{$table}</strong></td>";
                echo "<td>{$count} records</td>";
                echo "<td>";
                if ($count > 0) {
                    echo "<details>";
                    echo "<summary>Show sample (first 3 rows)</summary>";
                    echo "<pre style='font-size:11px;'>";
                    print_r($sample);
                    echo "</pre>";
                    echo "</details>";
                } else {
                    echo "<span class='badge badge-warning'>Empty table</span>";
                }
                echo "</td>";
                echo "</tr>";
            } else {
                echo "<tr>";
                echo "<td><strong>{$table}</strong></td>";
                echo "<td colspan='2'><span class='badge badge-error'>❌ Table does not exist</span></td>";
                echo "</tr>";
            }
        } catch (Exception $e) {
            echo "<tr>";
            echo "<td><strong>{$table}</strong></td>";
            echo "<td colspan='2'><span class='badge badge-error'>Error: " . $e->getMessage() . "</span></td>";
            echo "</tr>";
        }
    }
    echo "</table>";
    echo "</div>";
    
    // Show actual cafe tables from MySQL
    echo "<div class='section'>";
    echo "<h2>🪑 Café Tables (From MySQL Database)</h2>";
    $stmt = $pdo->query("SELECT * FROM cafe_tables ORDER BY table_id");
    $tables = $stmt->fetchAll();
    
    if (count($tables) > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Table Number</th><th>Capacity</th><th>Status</th></tr>";
        foreach ($tables as $table) {
            $statusClass = $table['status'] === 'available' ? 'badge-success' : ($table['status'] === 'occupied' ? 'badge-error' : 'badge-warning');
            echo "<tr>";
            echo "<td>{$table['table_id']}</td>";
            echo "<td>{$table['table_number']}</td>";
            echo "<td>{$table['capacity']}</td>";
            echo "<td><span class='badge {$statusClass}'>{$table['status']}</span></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>⚠️ No tables found in database!</p>";
    }
    echo "</div>";
    
    // Show actual reservations from MySQL
    echo "<div class='section'>";
    echo "<h2>📅 Reservations (From MySQL Database)</h2>";
    $stmt = $pdo->query("SELECT * FROM reservations ORDER BY reservation_id DESC LIMIT 10");
    $reservations = $stmt->fetchAll();
    
    if (count($reservations) > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Customer</th><th>Date</th><th>Time</th><th>Guests</th><th>Table</th><th>Status</th></tr>";
        foreach ($reservations as $r) {
            $statusClass = $r['status'] === 'confirmed' ? 'badge-success' : ($r['status'] === 'cancelled' ? 'badge-error' : 'badge-warning');
            echo "<tr>";
            echo "<td>{$r['reservation_id']}</td>";
            echo "<td>{$r['customer_name']}</td>";
            echo "<td>{$r['reservation_date']}</td>";
            echo "<td>{$r['reservation_time']}</td>";
            echo "<td>{$r['number_of_guests']}</td>";
            echo "<td>{$r['table_id']}</td>";
            echo "<td><span class='badge {$statusClass}'>{$r['status']}</span></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>⚠️ No reservations found in database!</p>";
    }
    echo "</div>";
    
    // Show actual queue from MySQL
    echo "<div class='section'>";
    echo "<h2>⏱️ Queue (From MySQL Database)</h2>";
    $stmt = $pdo->query("SELECT * FROM queue WHERE status = 'waiting' ORDER BY position ASC");
    $queue = $stmt->fetchAll();
    
    if (count($queue) > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Customer</th><th>Party Size</th><th>Position</th><th>Wait Time</th><th>Joined</th></tr>";
        foreach ($queue as $q) {
            echo "<tr>";
            echo "<td>{$q['queue_id']}</td>";
            echo "<td>{$q['customer_name']}</td>";
            echo "<td>{$q['party_size']}</td>";
            echo "<td>{$q['position']}</td>";
            echo "<td>{$q['estimated_wait_time']} min</td>";
            echo "<td>{$q['joined_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>⚠️ No waiting customers in queue!</p>";
    }
    echo "</div>";
    
    // Show actual orders from MySQL
    echo "<div class='section'>";
    echo "<h2>🍽️ Orders (From MySQL Database)</h2>";
    $stmt = $pdo->query("SELECT * FROM orders ORDER BY order_id DESC LIMIT 10");
    $orders = $stmt->fetchAll();
    
    if (count($orders) > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Reference</th><th>Customer</th><th>Total</th><th>Status</th><th>Payment</th><th>Created</th></tr>";
        foreach ($orders as $o) {
            $statusClass = $o['status'] === 'completed' ? 'badge-success' : ($o['status'] === 'cancelled' ? 'badge-error' : 'badge-warning');
            echo "<tr>";
            echo "<td>{$o['order_id']}</td>";
            echo "<td>{$o['order_ref']}</td>";
            echo "<td>{$o['customer_name']}</td>";
            echo "<td>\${$o['total_amount']}</td>";
            echo "<td><span class='badge {$statusClass}'>{$o['status']}</span></td>";
            echo "<td>{$o['payment_status']}</td>";
            echo "<td>{$o['created_at']}</td>";
            echo "</tr>";
            
            // Show order items
            $items = $pdo->query("SELECT * FROM order_items WHERE order_id = {$o['order_id']}")->fetchAll();
            if (count($items) > 0) {
                echo "<tr><td colspan='7'><details><summary>Items</summary><ul>";
                foreach ($items as $item) {
                    echo "<li>{$item['quantity']}× {$item['item_name']} - \${$item['subtotal']}</li>";
                }
                echo "</ul></details></td></tr>";
            }
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>⚠️ No orders found in database!</p>";
    }
    echo "</div>";
    
    // Show notifications
    echo "<div class='section'>";
    echo "<h2>🔔 Notifications (From MySQL Database)</h2>";
    $stmt = $pdo->query("SELECT * FROM notifications ORDER BY notification_id DESC LIMIT 5");
    $notifications = $stmt->fetchAll();
    
    if (count($notifications) > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>User</th><th>Type</th><th>Title</th><th>Read</th><th>Created</th></tr>";
        foreach ($notifications as $n) {
            echo "<tr>";
            echo "<td>{$n['notification_id']}</td>";
            echo "<td>{$n['user_id']}</td>";
            echo "<td>{$n['notification_type']}</td>";
            echo "<td>{$n['title']}</td>";
            echo "<td>" . ($n['is_read'] ? '✅' : '❌') . "</td>";
            echo "<td>{$n['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>⚠️ No notifications found in database!</p>";
    }
    echo "</div>";
    
    // API Endpoint Test
    echo "<div class='section'>";
    echo "<h2>🌐 API Endpoint Test</h2>";
    
    // Test POST to reservations.php
    echo "<h3>Testing API Endpoints...</h3>";
    
    $testData = [
        'customer_name' => 'TEST_USER',
        'customer_email' => 'test@example.com',
        'customer_phone' => '+1234567890',
        'table_id' => 1,
        'reservation_date' => date('Y-m-d'),
        'reservation_time' => '12:00',
        'number_of_guests' => 2,
        'special_requests' => 'API Test'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . "/reservations.php");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        $data = json_decode($response, true);
        if ($data && $data['success']) {
            echo "<p class='success'>✅ API Test Successful! reservations.php is working.</p>";
            echo "<details><summary>Response:</summary><pre>" . htmlspecialchars($response) . "</pre></details>";
        } else {
            echo "<p class='error'>❌ API returned error: " . ($data['message'] ?? 'Unknown error') . "</p>";
        }
    } else {
        echo "<p class='error'>❌ API endpoint not reachable (HTTP $httpCode)</p>";
    }
    
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>📝 Summary</h2>";
    
    // Count records
    $usersCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $tablesCount = $pdo->query("SELECT COUNT(*) FROM cafe_tables")->fetchColumn();
    $reservationsCount = $pdo->query("SELECT COUNT(*) FROM reservations")->fetchColumn();
    $queueCount = $pdo->query("SELECT COUNT(*) FROM queue")->fetchColumn();
    $ordersCount = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    
    echo "<ul>";
    echo "<li>👥 Users: <strong>{$usersCount}</strong></li>";
    echo "<li>🪑 Tables: <strong>{$tablesCount}</strong></li>";
    echo "<li>📅 Reservations: <strong>{$reservationsCount}</strong></li>";
    echo "<li>⏱️ Queue: <strong>{$queueCount}</strong></li>";
    echo "<li>🍽️ Orders: <strong>{$ordersCount}</strong></li>";
    echo "</ul>";
    
    if ($reservationsCount == 0 && $ordersCount == 0) {
        echo "<p class='warning'>⚠️ No reservations or orders found. Try making a reservation from the customer page!</p>";
    } else {
        echo "<p class='success'>✅ Database is populated with data!</p>";
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='section'>";
    echo "<p class='error'>❌ Connection failed: " . $e->getMessage() . "</p>";
    echo "<p><strong>Troubleshooting tips:</strong></p>";
    echo "<ul>";
    echo "<li>Make sure MySQL is running</li>";
    echo "<li>Check your config.php credentials</li>";
    echo "<li>Verify database name is 'smart_cafe'</li>";
    echo "<li>Run: mysql -u root -p < database_schema.sql</li>";
    echo "</ul>";
    echo "</div>";
}

echo "</body></html>";
?>