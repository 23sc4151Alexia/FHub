<?php
require_once '../config/config.php';
checkRole(['admin']);

$database = new Database();
$db = $database->getConnection();

// Get unassigned orders
$query = "SELECT o.*, u.full_name as customer_name, u.phone as customer_phone, u.address as customer_address
            FROM orders o
            LEFT JOIN users u ON o.customer_id = u.user_id
            WHERE o.rider_id IS NULL AND o.status IN ('pending', 'confirmed', 'processing')
            ORDER BY o.created_at ASC";
$stmt = $db->query($query);
$unassigned_orders = $stmt->fetchAll();

// Get all active riders
$query = "SELECT u.*,
            (SELECT COUNT(*) FROM orders WHERE rider_id = u.user_id AND status = 'out_for_delivery') as active_deliveries
            FROM users u
            WHERE u.role = 'rider' AND u.status = 'active'
            ORDER BY active_deliveries ASC, u.full_name ASC";
$stmt = $db->query($query);
$riders = $stmt->fetchAll();

// Handle bulk assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_orders'])) {
    $assignments = $_POST['assignments'] ?? [];
    $success_count = 0;
    
    foreach ($assignments as $order_id => $rider_id) {
        if (!empty($rider_id) && is_numeric($rider_id)) {
            try {
                // Update order with rider
                $query = "UPDATE orders SET rider_id = :rider_id, status = 'out_for_delivery' WHERE order_id = :order_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':rider_id', $rider_id);
                $stmt->bindParam(':order_id', $order_id);
                $stmt->execute();
                
                // Add to order history
                $query = "INSERT INTO order_status_history (order_id, status, comment, changed_by) 
                            VALUES (:order_id, 'out_for_delivery', 'Assigned to rider', :changed_by)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':order_id', $order_id);
                $stmt->bindParam(':changed_by', $_SESSION['user_id']);
                $stmt->execute();
                
                $success_count++;
            } catch(PDOException $e) {
                // Continue with other assignments
            }
        }
    }
    
    header("Location: assign_riders.php?success=$success_count orders assigned successfully");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Riders - Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/js/modern-ui.js" defer></script>
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>
    
    <div class="dashboard-container">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1>Assign Riders to Orders</h1>
                <a href="riders.php" class="btn">← Back</a>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
            <?php endif; ?>
            
            <?php if (empty($unassigned_orders)): ?>
            <div class="alert alert-success">
                <strong><i class="fas fa-check-circle"></i> All orders are assigned!</strong><br>
                There are no unassigned orders at the moment.
            </div>
            <?php else: ?>
            
            <div class="content-section">
                <h2>Unassigned Orders (<?php echo count($unassigned_orders); ?>)</h2>
                
                <form method="POST" action="">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Phone</th>
                                    <th>Address</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Assign Rider</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($unassigned_orders as $order): ?>
                                <tr>
                                    <td><strong><?php echo $order['order_number']; ?></strong></td>
                                    <td><?php echo $order['customer_name']; ?></td>
                                    <td><a href="tel:<?php echo $order['customer_phone']; ?>" class="phone-link"><i class="fas fa-phone"></i> <?php echo $order['customer_phone']; ?></a></td>
                                    <td><?php echo substr($order['customer_address'] ?? $order['delivery_address'], 0, 40) . '...'; ?></td>
                                    <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td><span class="badge badge-<?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span></td>
                                    <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <select name="assignments[<?php echo $order['order_id']; ?>]" class="rider-select" required>
                                            <option value="">-- Select Rider --</option>
                                            <?php foreach ($riders as $rider): ?>
                                            <option value="<?php echo $rider['user_id']; ?>">
                                                <?php echo $rider['full_name']; ?> 
                                                <?php if ($rider['active_deliveries'] > 0): ?>
                                                    (<?php echo $rider['active_deliveries']; ?> active)
                                                <?php else: ?>
                                                    (Available)
                                                <?php endif; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="assign_orders" class="btn btn-primary btn-lg">Assign Selected Riders</button>
                        <a href="riders.php" class="btn">Cancel</a>
                    </div>
                </form>
            </div>
            
            <?php endif; ?>
            
            <div class="content-section">
                <h2>Available Riders</h2>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Rider</th>
                                <th>Phone</th>
                                <th>Active Deliveries</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($riders as $rider): ?>
                            <tr>
                                <td><strong><?php echo $rider['full_name']; ?></strong></td>
                                <td><a href="tel:<?php echo $rider['phone']; ?>" class="phone-link"><i class="fas fa-phone"></i> <?php echo $rider['phone']; ?></a></td>
                                <td>
                                    <?php if ($rider['active_deliveries'] > 0): ?>
                                    <span style="color: #f59e0b;"><?php echo $rider['active_deliveries']; ?> active</span>
                                    <?php else: ?>
                                    <span style="color: #10b981;">Available</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge badge-<?php echo $rider['status']; ?>"><?php echo ucfirst($rider['status']); ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($riders)): ?>
                            <tr>
                                <td colspan="4" class="text-center">No active riders available. <a href="add_user.php?role=rider">Add a rider</a></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <style>
        .rider-select {
            padding: 8px 12px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            width: 250px;
            transition: all 0.3s;
        }
        
        .rider-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }
    </style>
</body>
</html>
