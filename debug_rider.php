<?php
require_once 'config/config.php';

$database = new Database();
$db = $database->getConnection();

echo "=== CHECKING RIDER DELIVERIES DEBUG ===\n\n";

// Check if there are any riders
$stmt = $db->query("SELECT user_id, username, full_name FROM users WHERE role = 'rider'");
$riders = $stmt->fetchAll();
echo "Total Riders: " . count($riders) . "\n";
foreach ($riders as $rider) {
    echo "  - Rider ID: {$rider['user_id']}, Name: {$rider['full_name']}, Username: {$rider['username']}\n";
}

echo "\n";

// Check if there are any orders
$stmt = $db->query("SELECT COUNT(*) as total FROM orders");
$total_orders = $stmt->fetch()['total'];
echo "Total Orders: $total_orders\n\n";

// Check orders with riders
$stmt = $db->query("SELECT order_id, order_number, rider_id, status FROM orders WHERE rider_id IS NOT NULL");
$assigned_orders = $stmt->fetchAll();
echo "Orders with Riders Assigned: " . count($assigned_orders) . "\n";
foreach ($assigned_orders as $order) {
    echo "  - Order #{$order['order_number']}, Rider ID: {$order['rider_id']}, Status: {$order['status']}\n";
}

echo "\n";

// Check unassigned orders
$stmt = $db->query("SELECT order_id, order_number, status FROM orders WHERE rider_id IS NULL");
$unassigned = $stmt->fetchAll();
echo "Unassigned Orders: " . count($unassigned) . "\n";
foreach ($unassigned as $order) {
    echo "  - Order #{$order['order_number']}, Status: {$order['status']}\n";
}

echo "\n";

// Test query from rider perspective
if (!empty($riders)) {
    $test_rider_id = $riders[0]['user_id'];
    echo "Testing query for Rider ID: $test_rider_id\n";
    
    $query = "SELECT o.*, u.full_name as customer_name 
              FROM orders o 
              LEFT JOIN users u ON o.customer_id = u.user_id 
              WHERE o.rider_id = :rider_id
              ORDER BY o.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':rider_id', $test_rider_id);
    $stmt->execute();
    $deliveries = $stmt->fetchAll();
    
    echo "Deliveries for this rider: " . count($deliveries) . "\n";
    foreach ($deliveries as $del) {
        echo "  - Order #{$del['order_number']}, Customer: {$del['customer_name']}, Status: {$del['status']}\n";
    }
}

echo "\n=== DEBUG COMPLETE ===\n";
?>
