<?php
require_once '../config/config.php';
checkRole(['admin']);

$database = new Database();
$db = $database->getConnection();

// Get statistics
$stats = [];

// Total orders
$query = "SELECT COUNT(*) as total FROM orders";
$stmt = $db->query($query);
$stats['total_orders'] = $stmt->fetch()['total'];

// Pending orders
$query = "SELECT COUNT(*) as total FROM orders WHERE status = 'pending'";
$stmt = $db->query($query);
$stats['pending_orders'] = $stmt->fetch()['total'];

// Total products
$query = "SELECT COUNT(*) as total FROM products WHERE status = 'available'";
$stmt = $db->query($query);
$stats['total_products'] = $stmt->fetch()['total'];

// Total users
$query = "SELECT COUNT(*) as total FROM users WHERE status = 'active'";
$stmt = $db->query($query);
$stats['total_users'] = $stmt->fetch()['total'];

// Total revenue
$query = "SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE payment_status = 'paid'";
$stmt = $db->query($query);
$stats['total_revenue'] = $stmt->fetch()['total'];

// Recent orders
$query = "SELECT o.*, u.full_name as customer_name 
            FROM orders o 
            LEFT JOIN users u ON o.customer_id = u.user_id 
            ORDER BY o.created_at DESC LIMIT 10";
$stmt = $db->query($query);
$recent_orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - FurnHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/js/modern-ui.js" defer></script>
    <script src="../assets/js/theme-switcher.js" defer></script>
    <script src="../assets/js/ajax-handler.js" defer></script>
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>
    
    <div class="dashboard-container">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1>Admin Dashboard</h1>
                <p>Welcome back, <?php echo $_SESSION['full_name']; ?>!</p>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-box"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_orders']; ?></h3>
                        <p>Total Orders</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon orange"><i class="fas fa-hourglass-half"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $stats['pending_orders']; ?></h3>
                        <p>Pending Orders</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-couch"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_products']; ?></h3>
                        <p>Active Products</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="fas fa-users"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_users']; ?></h3>
                        <p>Total Users</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon teal"><i class="fas fa-money-bill-wave"></i></div>
                    <div class="stat-info">
                        <h3>₱<?php echo number_format($stats['total_revenue'], 2); ?></h3>
                        <p>Total Revenue</p>
                    </div>
                </div>
            </div>
            
            <div class="content-section">
                <h2>Recent Orders</h2>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                            <tr>
                                <td><?php echo $order['order_number']; ?></td>
                                <td><?php echo $order['customer_name']; ?></td>
                                <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td><span class="badge badge-<?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span></td>
                                <td><span class="badge badge-<?php echo $order['payment_status']; ?>"><?php echo ucfirst($order['payment_status']); ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <a href="view_order.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm">View</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recent_orders)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No orders found</td>
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
