<?php
require_once '../config/config.php';
checkRole(['admin']);

$database = new Database();
$db = $database->getConnection();

// Get date range
$start_date = isset($_GET['start_date']) ? sanitize($_GET['start_date']) : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? sanitize($_GET['end_date']) : date('Y-m-d');

// Summary statistics
$summary_query = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(total_amount) as total_sales,
                    AVG(total_amount) as avg_order_value,
                    SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as paid_amount
                    FROM orders 
                    WHERE DATE(created_at) BETWEEN :start_date AND :end_date";
$stmt = $db->prepare($summary_query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date);
$stmt->execute();
$summary = $stmt->fetch();

// Daily sales
$daily_query = "SELECT DATE(created_at) as date, 
                COUNT(*) as order_count,
                SUM(total_amount) as daily_total
                FROM orders 
                WHERE DATE(created_at) BETWEEN :start_date AND :end_date
                GROUP BY DATE(created_at)
                ORDER BY date DESC";
$stmt = $db->prepare($daily_query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date);
$stmt->execute();
$daily_sales = $stmt->fetchAll();

// Top products
$products_query = "SELECT p.product_name, c.category_name,
                    SUM(oi.quantity) as total_sold,
                    SUM(oi.subtotal) as total_revenue
                    FROM order_items oi
                    JOIN products p ON oi.product_id = p.product_id
                    JOIN categories c ON p.category_id = c.category_id
                    JOIN orders o ON oi.order_id = o.order_id
                    WHERE DATE(o.created_at) BETWEEN :start_date AND :end_date
                    GROUP BY p.product_id
                    ORDER BY total_revenue DESC
                    LIMIT 10";
$stmt = $db->prepare($products_query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date);
$stmt->execute();
$top_products = $stmt->fetchAll();

// Top customers
$customers_query = "SELECT u.full_name, u.email,
                    COUNT(o.order_id) as order_count,
                    SUM(o.total_amount) as total_spent
                    FROM orders o
                    JOIN users u ON o.customer_id = u.user_id
                    WHERE DATE(o.created_at) BETWEEN :start_date AND :end_date
                    GROUP BY o.customer_id
                    ORDER BY total_spent DESC
                    LIMIT 10";
$stmt = $db->prepare($customers_query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date);
$stmt->execute();
$top_customers = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/js/modern-ui.js" defer></script>
    <script src="../assets/js/theme-switcher.js" defer></script>
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>
    
    <div class="dashboard-container">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1>Sales & Analytics Reports</h1>
            </div>
            
            <div class="filter-section">
                <form method="GET" class="filter-form">
                    <label>From:</label>
                    <input type="date" name="start_date" value="<?php echo $start_date; ?>" required>
                    
                    <label>To:</label>
                    <input type="date" name="end_date" value="<?php echo $end_date; ?>" required>
                    
                    <button type="submit" class="btn btn-primary">Generate Report</button>
                </form>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-box"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $summary['total_orders'] ?: 0; ?></h3>
                        <p>Total Orders</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-money-bill-wave"></i></div>
                    <div class="stat-info">
                        <h3>₱<?php echo number_format($summary['total_sales'] ?: 0, 2); ?></h3>
                        <p>Total Sales</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon teal"><i class="fas fa-chart-pie"></i></div>
                    <div class="stat-info">
                        <h3>₱<?php echo number_format($summary['avg_order_value'] ?: 0, 2); ?></h3>
                        <p>Avg Order Value</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-info">
                        <h3>₱<?php echo number_format($summary['paid_amount'] ?: 0, 2); ?></h3>
                        <p>Paid Amount</p>
                    </div>
                </div>
            </div>
            
            <div class="content-section">
                <h2>Daily Sales Breakdown</h2>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Orders</th>
                                <th>Total Sales</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($daily_sales as $day): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($day['date'])); ?></td>
                                <td><?php echo $day['order_count']; ?></td>
                                <td>₱<?php echo number_format($day['daily_total'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($daily_sales)): ?>
                            <tr>
                                <td colspan="3" class="text-center">No sales data for selected period</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="dashboard-grid">
                <div class="content-section">
                    <h2>Top 10 Products</h2>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Sold</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_products as $product): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo $product['product_name']; ?></strong><br>
                                        <small><?php echo $product['category_name']; ?></small>
                                    </td>
                                    <td><?php echo $product['total_sold']; ?></td>
                                    <td>₱<?php echo number_format($product['total_revenue'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="content-section">
                    <h2>Top 10 Customers</h2>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Orders</th>
                                    <th>Total Spent</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_customers as $customer): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo $customer['full_name']; ?></strong><br>
                                        <small><?php echo $customer['email']; ?></small>
                                    </td>
                                    <td><?php echo $customer['order_count']; ?></td>
                                    <td>₱<?php echo number_format($customer['total_spent'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>
