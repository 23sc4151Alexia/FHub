<?php
require_once '../config/config.php';
checkRole(['admin']);

if (!isset($_GET['rider_id'])) {
    header('Location: riders.php');
    exit();
}

$rider_id = (int)$_GET['rider_id'];

$database = new Database();
$db = $database->getConnection();

// Get rider details
$query = "SELECT * FROM users WHERE user_id = :rider_id AND role = 'rider'";
$stmt = $db->prepare($query);
$stmt->bindParam(':rider_id', $rider_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header('Location: riders.php?error=Rider not found');
    exit();
}

$rider = $stmt->fetch();

// Get unassigned orders
$query = "SELECT o.*, u.full_name as customer_name, u.phone as customer_phone, u.address as customer_address
            FROM orders o
            LEFT JOIN users u ON o.customer_id = u.user_id
            WHERE o.rider_id IS NULL AND o.status IN ('pending', 'confirmed', 'processing')
            ORDER BY o.created_at ASC";
$stmt = $db->query($query);
$unassigned_orders = $stmt->fetchAll();

// Handle assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['order_id'])) {
    $order_id = (int)$_POST['order_id'];
    
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
        
        header("Location: rider_details.php?id=$rider_id&success=Order assigned successfully");
        exit();
    } catch(PDOException $e) {
        $error = "Failed to assign order";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Order to Rider - Admin</title>
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
                <h1>Assign Order to <?php echo $rider['full_name']; ?></h1>
                <a href="rider_details.php?id=<?php echo $rider_id; ?>" class="btn">← Back</a>
            </div>
            
            <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="content-section" style="margin-bottom: 20px;">
                <h2>Rider Information</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Name:</label>
                        <strong><?php echo $rider['full_name']; ?></strong>
                    </div>
                    <div class="info-item">
                        <label>Phone:</label>
                        <a href="tel:<?php echo $rider['phone']; ?>" class="phone-link"><i class="fas fa-phone"></i> <?php echo $rider['phone']; ?></a>
                    </div>
                    <div class="info-item">
                        <label>Status:</label>
                        <span class="badge badge-<?php echo $rider['status']; ?>"><?php echo ucfirst($rider['status']); ?></span>
                    </div>
                </div>
            </div>
            
            <?php if (empty($unassigned_orders)): ?>
            <div class="alert alert-success">
                <strong><i class="fas fa-check-circle"></i> All orders are assigned!</strong><br>
                There are no unassigned orders available at the moment.
            </div>
            <?php else: ?>
            
            <div class="content-section">
                <h2>Available Orders for Assignment (<?php echo count($unassigned_orders); ?>)</h2>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Phone</th>
                                <th>Delivery Address</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($unassigned_orders as $order): ?>
                            <tr>
                                <td><strong><?php echo $order['order_number']; ?></strong></td>
                                <td><?php echo $order['customer_name']; ?></td>
                                <td><a href="tel:<?php echo $order['customer_phone']; ?>" class="phone-link"><i class="fas fa-phone"></i> <?php echo $order['customer_phone']; ?></a></td>
                                <td style="max-width: 250px;">
                                    <?php 
                                    $address = $order['customer_address'] ?? $order['delivery_address'];
                                    echo substr($address, 0, 60);
                                    if (strlen($address) > 60) echo '...';
                                    ?>
                                </td>
                                <td><strong>₱<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                <td><span class="badge badge-<?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-success" 
                                                onclick="return confirm('Assign this order to <?php echo $rider['full_name']; ?>?')">
                                            Assign to Rider
                                        </button>
                                    </form>
                                    <a href="view_order.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-primary">View Details</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
