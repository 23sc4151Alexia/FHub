<?php
require_once '../config/config.php';
checkRole(['admin']);

if (!isset($_GET['id'])) {
    header('Location: riders.php');
    exit();
}

$rider_id = (int)$_GET['id'];

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

// Get rider statistics
$query = "SELECT 
          COUNT(*) as total_deliveries,
          SUM(CASE WHEN status = 'out_for_delivery' THEN 1 ELSE 0 END) as active_deliveries,
          SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as completed_deliveries,
          SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_deliveries,
          COALESCE(SUM(CASE WHEN status = 'delivered' THEN delivery_fee ELSE 0 END), 0) as total_earnings
          FROM orders WHERE rider_id = :rider_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':rider_id', $rider_id);
$stmt->execute();
$stats = $stmt->fetch();

// Get recent deliveries
$query = "SELECT o.*, u.full_name as customer_name 
          FROM orders o
          LEFT JOIN users u ON o.customer_id = u.user_id
          WHERE o.rider_id = :rider_id
          ORDER BY o.created_at DESC
          LIMIT 20";
$stmt = $db->prepare($query);
$stmt->bindParam(':rider_id', $rider_id);
$stmt->execute();
$deliveries = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rider Details - Admin</title>
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
                <h1>Rider Details</h1>
                <div>
                    <a href="assign_to_rider.php?rider_id=<?php echo $rider_id; ?>" class="btn btn-success">Assign Order</a>
                    <a href="edit_user.php?id=<?php echo $rider_id; ?>" class="btn btn-primary">Edit Rider</a>
                    <a href="riders.php" class="btn">← Back</a>
                </div>
            </div>
            
            <div class="order-details-grid">
                <div class="content-section">
                    <h2>Rider Information</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Full Name:</label>
                            <strong><?php echo $rider['full_name']; ?></strong>
                        </div>
                        <div class="info-item">
                            <label>Username:</label>
                            <span><?php echo $rider['username']; ?></span>
                        </div>
                        <div class="info-item">
                            <label>Phone:</label>
                            <a href="tel:<?php echo $rider['phone']; ?>" class="phone-link"><i class="fas fa-phone"></i> <?php echo $rider['phone']; ?></a>
                        </div>
                        <div class="info-item">
                            <label>Email:</label>
                            <span><?php echo $rider['email']; ?></span>
                        </div>
                        <div class="info-item">
                            <label>Status:</label>
                            <span class="badge badge-<?php echo $rider['status']; ?>"><?php echo ucfirst($rider['status']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Member Since:</label>
                            <span><?php echo date('M d, Y', strtotime($rider['created_at'])); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-box"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_deliveries']; ?></h3>
                        <p>Total Deliveries</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange"><i class="fas fa-truck"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $stats['active_deliveries']; ?></h3>
                        <p>Active Deliveries</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $stats['completed_deliveries']; ?></h3>
                        <p>Completed</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="fas fa-money-bill-wave"></i></div>
                    <div class="stat-info">
                        <h3>₱<?php echo number_format($stats['total_earnings'], 2); ?></h3>
                        <p>Total Earnings</p>
                    </div>
                </div>
            </div>
            
            <div class="content-section">
                <h2>Delivery History</h2>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Delivery Fee</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($deliveries as $delivery): ?>
                            <tr>
                                <td><strong><?php echo $delivery['order_number']; ?></strong></td>
                                <td><?php echo $delivery['customer_name']; ?></td>
                                <td>₱<?php echo number_format($delivery['total_amount'], 2); ?></td>
                                <td><strong>₱<?php echo number_format($delivery['delivery_fee'], 2); ?></strong></td>
                                <td><span class="badge badge-<?php echo $delivery['status']; ?>"><?php echo str_replace('_', ' ', ucfirst($delivery['status'])); ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($delivery['created_at'])); ?></td>
                                <td>
                                    <a href="view_order.php?id=<?php echo $delivery['order_id']; ?>" class="btn btn-sm btn-primary">View</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($deliveries)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No delivery history</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
