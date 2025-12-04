<?php
require_once '../config/config.php';
checkRole(['admin']);

$database = new Database();
$db = $database->getConnection();

// Get all orders with customer and rider information
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';

$query = "SELECT o.*, u.full_name as customer_name, u.phone as customer_phone, 
            r.full_name as rider_name
            FROM orders o 
            LEFT JOIN users u ON o.customer_id = u.user_id 
            LEFT JOIN users r ON o.rider_id = r.user_id 
            WHERE 1=1";

if (!empty($search)) {
    $query .= " AND (o.order_number LIKE :search OR u.full_name LIKE :search)";
}

if (!empty($status_filter)) {
    $query .= " AND o.status = :status";
}

$query .= " ORDER BY o.created_at DESC";

$stmt = $db->prepare($query);

if (!empty($search)) {
    $search_param = "%$search%";
    $stmt->bindParam(':search', $search_param);
}

if (!empty($status_filter)) {
    $stmt->bindParam(':status', $status_filter);
}

$stmt->execute();
$orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Admin</title>
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
                <h1>Manage Orders</h1>
            </div>
            
            <div class="filter-section">
                <form method="GET" class="filter-form">
                    <input type="text" name="search" placeholder="Search by order # or customer..." 
                            value="<?php echo htmlspecialchars($search); ?>">
                    
                    <select name="status">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="processing" <?php echo $status_filter == 'processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="out_for_delivery" <?php echo $status_filter == 'out_for_delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
                        <option value="delivered" <?php echo $status_filter == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                        <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                    
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="orders.php" class="btn">Reset</a>
                </form>
            </div>
            
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Phone</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Rider</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?php echo $order['order_number']; ?></td>
                            <td><?php echo $order['customer_name']; ?></td>
                            <td><?php echo $order['customer_phone']; ?></td>
                            <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td><span class="badge badge-<?php echo $order['status']; ?>"><?php echo str_replace('_', ' ', ucfirst($order['status'])); ?></span></td>
                            <td><span class="badge badge-<?php echo $order['payment_status']; ?>"><?php echo ucfirst($order['payment_status']); ?></span></td>
                            <td><?php echo $order['rider_name'] ?: 'Not assigned'; ?></td>
                            <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                            <td>
                                <a href="view_order.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm">View</a>
                                <a href="edit_order.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="9" class="text-center">No orders found</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
