<?php
require_once '../config/config.php';
checkRole(['admin']);

$database = new Database();
$db = $database->getConnection();

// Get sales data for the last 6 months (system-wide)
$sales_query = "SELECT 
    DATE_FORMAT(o.created_at, '%Y-%m') as month,
    DATE_FORMAT(o.created_at, '%b %Y') as month_label,
    COUNT(DISTINCT o.order_id) as total_orders,
    SUM(oi.quantity) as total_items,
    SUM(oi.price * oi.quantity) as revenue
    FROM orders o
    INNER JOIN order_items oi ON o.order_id = oi.order_id
    WHERE o.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(o.created_at, '%Y-%m')
    ORDER BY month ASC";
$stmt = $db->prepare($sales_query);
$stmt->execute();
$monthly_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get trending products (most sold in last 30 days) - system-wide
$trending_query = "SELECT 
    p.product_id, p.product_name, p.price, p.image_url, c.category_name, u.full_name as store_name,
    SUM(oi.quantity) as units_sold,
    COUNT(DISTINCT o.order_id) as order_count,
    SUM(oi.price * oi.quantity) as total_revenue
    FROM products p
    LEFT JOIN order_items oi ON p.product_id = oi.product_id
    LEFT JOIN orders o ON oi.order_id = o.order_id AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    LEFT JOIN categories c ON p.category_id = c.category_id
    LEFT JOIN users u ON p.owner_id = u.user_id
    WHERE p.status = 'available'
    GROUP BY p.product_id
    ORDER BY units_sold DESC
    LIMIT 10";
$stmt = $db->prepare($trending_query);
$stmt->execute();
$trending_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get category performance - system-wide
$category_query = "SELECT 
    c.category_name,
    COUNT(DISTINCT p.product_id) as product_count,
    SUM(oi.quantity) as units_sold,
    SUM(oi.price * oi.quantity) as revenue
    FROM categories c
    LEFT JOIN products p ON c.category_id = p.category_id
    LEFT JOIN order_items oi ON p.product_id = oi.product_id
    LEFT JOIN orders o ON oi.order_id = o.order_id AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY c.category_id
    HAVING units_sold > 0
    ORDER BY units_sold DESC
    LIMIT 10";
$stmt = $db->prepare($category_query);
$stmt->execute();
$category_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get products for demand prediction (based on sales velocity) - system-wide
$demand_query = "SELECT 
    p.product_id, p.product_name, p.stock_quantity, p.price, c.category_name, u.full_name as store_name,
    COALESCE(SUM(oi.quantity), 0) as last_month_sales,
    COALESCE(
        (SELECT SUM(oi2.quantity) FROM order_items oi2 
         JOIN orders o2 ON oi2.order_id = o2.order_id 
         WHERE oi2.product_id = p.product_id 
         AND o2.created_at >= DATE_SUB(CURDATE(), INTERVAL 60 DAY)
         AND o2.created_at < DATE_SUB(CURDATE(), INTERVAL 30 DAY)), 0
    ) as prev_month_sales
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    LEFT JOIN users u ON p.owner_id = u.user_id
    LEFT JOIN order_items oi ON p.product_id = oi.product_id
    LEFT JOIN orders o ON oi.order_id = o.order_id AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    WHERE p.status = 'available'
    GROUP BY p.product_id
    HAVING last_month_sales > 0 OR prev_month_sales > 0
    ORDER BY last_month_sales DESC
    LIMIT 20";
$stmt = $db->prepare($demand_query);
$stmt->execute();
$demand_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get store performance
$store_query = "SELECT 
    u.user_id, u.full_name as store_name,
    COUNT(DISTINCT p.product_id) as product_count,
    COALESCE(SUM(oi.quantity), 0) as units_sold,
    COALESCE(SUM(oi.price * oi.quantity), 0) as revenue
    FROM users u
    LEFT JOIN products p ON u.user_id = p.owner_id
    LEFT JOIN order_items oi ON p.product_id = oi.product_id
    LEFT JOIN orders o ON oi.order_id = o.order_id AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    WHERE u.role = 'owner'
    GROUP BY u.user_id
    ORDER BY revenue DESC
    LIMIT 10";
$stmt = $db->prepare($store_query);
$stmt->execute();
$store_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate simple linear forecast for next month
function calculateForecast($monthlyData) {
    if (count($monthlyData) < 2) {
        return ['forecast' => 0, 'trend' => 'stable', 'growth' => 0];
    }
    
    $revenues = array_column($monthlyData, 'revenue');
    $n = count($revenues);
    
    $sum_x = 0;
    $sum_y = 0;
    $sum_xy = 0;
    $sum_xx = 0;
    
    for ($i = 0; $i < $n; $i++) {
        $sum_x += $i + 1;
        $sum_y += (float)$revenues[$i];
        $sum_xy += ($i + 1) * (float)$revenues[$i];
        $sum_xx += ($i + 1) * ($i + 1);
    }
    
    $slope = ($n * $sum_xy - $sum_x * $sum_y) / ($n * $sum_xx - $sum_x * $sum_x);
    $intercept = ($sum_y - $slope * $sum_x) / $n;
    
    $forecast = $intercept + $slope * ($n + 1);
    $forecast = max(0, $forecast);
    
    $lastMonth = end($revenues);
    $growth = $lastMonth > 0 ? (($forecast - $lastMonth) / $lastMonth) * 100 : 0;
    
    $trend = $growth > 5 ? 'up' : ($growth < -5 ? 'down' : 'stable');
    
    return [
        'forecast' => round($forecast, 2),
        'trend' => $trend,
        'growth' => round($growth, 1)
    ];
}

$forecast = calculateForecast($monthly_sales);

function predictProductDemand($lastMonth, $prevMonth) {
    if ($lastMonth == 0 && $prevMonth == 0) return ['predicted' => 0, 'trend' => 'no_data'];
    if ($prevMonth == 0) return ['predicted' => round($lastMonth * 1.1), 'trend' => 'new'];
    
    $growth = ($lastMonth - $prevMonth) / $prevMonth;
    $predicted = $lastMonth * (1 + $growth * 0.5);
    $predicted = max(0, round($predicted));
    
    $trend = $growth > 0.1 ? 'increasing' : ($growth < -0.1 ? 'decreasing' : 'stable');
    
    return ['predicted' => $predicted, 'trend' => $trend];
}

// Get all products for AI description generator
$products_query = "SELECT p.product_id, p.product_name, p.description, c.category_name, u.full_name as store_name
                   FROM products p 
                   LEFT JOIN categories c ON p.category_id = c.category_id 
                   LEFT JOIN users u ON p.owner_id = u.user_id
                   ORDER BY p.product_name";
$stmt = $db->prepare($products_query);
$stmt->execute();
$all_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Analytics - Admin - FurnHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .ai-analytics-container { padding: 20px; }
        .ai-header { display: flex; align-items: center; gap: 15px; margin-bottom: 30px; }
        .ai-header .ai-icon { width: 60px; height: 60px; background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%); border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 28px; color: white; }
        .ai-header h1 { margin: 0; font-size: 28px; font-weight: 700; }
        .ai-header p { margin: 5px 0 0; color: #6b7280; }
        .ai-tabs { display: flex; gap: 10px; margin-bottom: 25px; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px; flex-wrap: wrap; }
        .ai-tab { padding: 12px 24px; background: #f3f4f6; border: none; border-radius: 10px 10px 0 0; font-size: 14px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.3s; color: #6b7280; }
        .ai-tab:hover { background: #e5e7eb; }
        .ai-tab.active { background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%); color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .forecast-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .forecast-card { background: white; border-radius: 16px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
        .forecast-card.highlight { background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%); color: white; }
        .forecast-card h3 { font-size: 14px; font-weight: 600; margin: 0 0 15px; opacity: 0.8; display: flex; align-items: center; gap: 8px; }
        .forecast-card .value { font-size: 32px; font-weight: 800; }
        .forecast-card .trend { display: inline-flex; align-items: center; gap: 5px; font-size: 14px; margin-top: 10px; padding: 5px 12px; border-radius: 20px; }
        .trend-up { background: #dcfce7; color: #16a34a; }
        .trend-down { background: #fee2e2; color: #dc2626; }
        .trend-stable { background: #fef3c7; color: #d97706; }
        .forecast-card.highlight .trend { background: rgba(255,255,255,0.2); color: white; }
        .chart-container { background: white; border-radius: 16px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); margin-bottom: 30px; }
        .chart-container h3 { margin: 0 0 20px; font-size: 18px; font-weight: 700; display: flex; align-items: center; gap: 10px; }
        .chart-container h3 i { color: #8b5cf6; }
        .charts-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .trending-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .trending-card { background: white; border-radius: 16px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); display: flex; gap: 15px; align-items: center; transition: all 0.3s; }
        .trending-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.15); }
        .trending-rank { width: 40px; height: 40px; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: 800; color: white; flex-shrink: 0; }
        .trending-rank.top1 { background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); }
        .trending-rank.top2 { background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%); }
        .trending-rank.top3 { background: linear-gradient(135deg, #d97706 0%, #b45309 100%); }
        .trending-image { width: 60px; height: 60px; border-radius: 10px; object-fit: cover; background: #f3f4f6; flex-shrink: 0; }
        .trending-info { flex: 1; min-width: 0; }
        .trending-info h4 { margin: 0 0 5px; font-size: 14px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .trending-info .category { font-size: 12px; color: #6b7280; }
        .trending-info .store { font-size: 11px; color: #10b981; }
        .trending-stats { text-align: right; }
        .trending-stats .sales { font-size: 18px; font-weight: 700; color: #10b981; }
        .trending-stats .label { font-size: 11px; color: #9ca3af; }
        .demand-table { width: 100%; border-collapse: collapse; background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
        .demand-table th, .demand-table td { padding: 15px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        .demand-table th { background: #f9fafb; font-weight: 600; font-size: 13px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; }
        .demand-table tr:hover { background: #fafafa; }
        .demand-badge { padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .badge-increasing { background: #dcfce7; color: #16a34a; }
        .badge-decreasing { background: #fee2e2; color: #dc2626; }
        .badge-stable { background: #fef3c7; color: #d97706; }
        .badge-new { background: #dbeafe; color: #2563eb; }
        .ai-generator { background: white; border-radius: 16px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
        .generator-form { display: grid; gap: 20px; margin-bottom: 25px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group label { display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 14px; transition: all 0.3s; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #8b5cf6; box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1); }
        .generate-btn { background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%); color: white; border: none; padding: 14px 28px; border-radius: 10px; font-size: 15px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 10px; transition: all 0.3s; }
        .generate-btn:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(139, 92, 246, 0.3); }
        .generate-btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        .generated-result { background: #f9fafb; border-radius: 12px; padding: 20px; margin-top: 20px; display: none; }
        .generated-result.active { display: block; }
        .generated-result h4 { margin: 0 0 15px; font-size: 14px; color: #6b7280; display: flex; align-items: center; gap: 8px; }
        .generated-result h4 i { color: #10b981; }
        .generated-text { background: white; border: 1px solid #e5e7eb; border-radius: 10px; padding: 15px; font-size: 14px; line-height: 1.7; color: #374151; }
        .result-actions { display: flex; gap: 10px; margin-top: 15px; }
        .result-actions button { padding: 10px 20px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .copy-btn { background: #10b981; color: white; border: none; }
        .regenerate-btn { background: white; color: #6b7280; border: 2px solid #e5e7eb; }
        .apply-btn { background: #3b82f6; color: white; border: none; }
        @media (max-width: 992px) { .charts-row { grid-template-columns: 1fr; } }
        @media (max-width: 768px) { .form-row { grid-template-columns: 1fr; } .trending-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>
    
    <div class="dashboard-container">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="main-content">
            <div class="ai-analytics-container">
                <div class="ai-header">
                    <div class="ai-icon"><i class="fas fa-brain"></i></div>
                    <div>
                        <h1>AI Analytics (System-Wide)</h1>
                        <p>Platform-wide insights, demand forecasting, and AI-powered tools</p>
                    </div>
                </div>
                
                <div class="ai-tabs">
                    <button class="ai-tab active" data-tab="forecast"><i class="fas fa-chart-line"></i> Sales Forecast</button>
                    <button class="ai-tab" data-tab="trending"><i class="fas fa-fire"></i> Trending Items</button>
                    <button class="ai-tab" data-tab="stores"><i class="fas fa-store"></i> Store Performance</button>
                    <button class="ai-tab" data-tab="demand"><i class="fas fa-boxes-stacked"></i> Demand Prediction</button>
                    <button class="ai-tab" data-tab="generator"><i class="fas fa-wand-magic-sparkles"></i> AI Description</button>
                </div>
                
                <!-- Sales Forecast Tab -->
                <div class="tab-content active" id="tab-forecast">
                    <div class="forecast-grid">
                        <div class="forecast-card highlight">
                            <h3><i class="fas fa-crystal-ball"></i> Next Month Forecast</h3>
                            <div class="value">₱<?php echo number_format($forecast['forecast'], 0); ?></div>
                            <div class="trend trend-<?php echo $forecast['trend']; ?>">
                                <i class="fas fa-arrow-<?php echo $forecast['trend'] == 'up' ? 'up' : ($forecast['trend'] == 'down' ? 'down' : 'right'); ?>"></i>
                                <?php echo $forecast['growth'] > 0 ? '+' : ''; ?><?php echo $forecast['growth']; ?>%
                            </div>
                        </div>
                        
                        <?php 
                        $lastMonth = end($monthly_sales);
                        $totalRevenue = array_sum(array_column($monthly_sales, 'revenue'));
                        $avgRevenue = count($monthly_sales) > 0 ? $totalRevenue / count($monthly_sales) : 0;
                        $totalOrders = array_sum(array_column($monthly_sales, 'total_orders'));
                        ?>
                        
                        <div class="forecast-card">
                            <h3><i class="fas fa-calendar-check"></i> Last Month</h3>
                            <div class="value">₱<?php echo number_format($lastMonth['revenue'] ?? 0, 0); ?></div>
                            <div class="trend trend-stable"><?php echo $lastMonth['total_orders'] ?? 0; ?> orders</div>
                        </div>
                        
                        <div class="forecast-card">
                            <h3><i class="fas fa-chart-bar"></i> 6-Month Average</h3>
                            <div class="value">₱<?php echo number_format($avgRevenue, 0); ?></div>
                            <div class="trend trend-stable"><?php echo $totalOrders; ?> total orders</div>
                        </div>
                        
                        <div class="forecast-card">
                            <h3><i class="fas fa-coins"></i> 6-Month Total</h3>
                            <div class="value">₱<?php echo number_format($totalRevenue, 0); ?></div>
                            <div class="trend trend-stable">Platform revenue</div>
                        </div>
                    </div>
                    
                    <div class="chart-container">
                        <h3><i class="fas fa-chart-area"></i> Revenue Trend & Forecast</h3>
                        <canvas id="revenueChart" height="100"></canvas>
                    </div>
                    
                    <div class="charts-row">
                        <div class="chart-container">
                            <h3><i class="fas fa-chart-pie"></i> Category Performance</h3>
                            <canvas id="categoryChart" height="150"></canvas>
                        </div>
                        <div class="chart-container">
                            <h3><i class="fas fa-shopping-bag"></i> Orders Trend</h3>
                            <canvas id="ordersChart" height="150"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Trending Items Tab -->
                <div class="tab-content" id="tab-trending">
                    <div class="chart-container">
                        <h3><i class="fas fa-fire"></i> Top Selling Products (Last 30 Days)</h3>
                        <canvas id="trendingChart" height="100"></canvas>
                    </div>
                    
                    <h3 style="margin: 30px 0 20px; font-size: 18px;"><i class="fas fa-trophy" style="color: #f59e0b;"></i> Top 10 Trending Products</h3>
                    <div class="trending-grid">
                        <?php foreach ($trending_products as $index => $product): ?>
                        <div class="trending-card">
                            <div class="trending-rank <?php echo $index < 3 ? 'top' . ($index + 1) : ''; ?>"><?php echo $index + 1; ?></div>
                            <?php if ($product['image_url']): ?>
                            <img src="../<?php echo $product['image_url']; ?>" alt="" class="trending-image">
                            <?php else: ?>
                            <div class="trending-image" style="display: flex; align-items: center; justify-content: center; color: #9ca3af;"><i class="fas fa-couch"></i></div>
                            <?php endif; ?>
                            <div class="trending-info">
                                <h4><?php echo htmlspecialchars($product['product_name']); ?></h4>
                                <div class="category"><?php echo $product['category_name'] ?? 'Uncategorized'; ?></div>
                                <div class="store"><i class="fas fa-store"></i> <?php echo $product['store_name'] ?? 'Unknown Store'; ?></div>
                            </div>
                            <div class="trending-stats">
                                <div class="sales"><?php echo (int)$product['units_sold']; ?></div>
                                <div class="label">units sold</div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Store Performance Tab -->
                <div class="tab-content" id="tab-stores">
                    <div class="chart-container">
                        <h3><i class="fas fa-store"></i> Store Revenue Comparison (Last 30 Days)</h3>
                        <canvas id="storeChart" height="100"></canvas>
                    </div>
                    
                    <div class="chart-container">
                        <h3><i class="fas fa-ranking-star"></i> Store Performance Table</h3>
                        <table class="demand-table">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Store</th>
                                    <th>Products</th>
                                    <th>Units Sold</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($store_performance as $index => $store): ?>
                                <tr>
                                    <td><span class="trending-rank <?php echo $index < 3 ? 'top' . ($index + 1) : ''; ?>" style="width: 30px; height: 30px; font-size: 14px;"><?php echo $index + 1; ?></span></td>
                                    <td><strong><?php echo htmlspecialchars($store['store_name']); ?></strong></td>
                                    <td><?php echo $store['product_count']; ?></td>
                                    <td><?php echo (int)$store['units_sold']; ?></td>
                                    <td><strong style="color: #10b981;">₱<?php echo number_format($store['revenue'], 2); ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Demand Prediction Tab -->
                <div class="tab-content" id="tab-demand">
                    <div class="chart-container">
                        <h3><i class="fas fa-boxes-stacked"></i> Predicted Demand for Next Month</h3>
                        <p style="color: #6b7280; margin-bottom: 20px;">Based on sales velocity and trends from the past 60 days</p>
                        
                        <table class="demand-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Store</th>
                                    <th>Stock</th>
                                    <th>Last Month</th>
                                    <th>Prev Month</th>
                                    <th>Predicted</th>
                                    <th>Trend</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($demand_products as $product): 
                                    $prediction = predictProductDemand($product['last_month_sales'], $product['prev_month_sales']);
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($product['product_name']); ?></strong></td>
                                    <td style="color: #6b7280;"><?php echo $product['store_name'] ?? '-'; ?></td>
                                    <td>
                                        <?php if ($product['stock_quantity'] < $prediction['predicted']): ?>
                                        <span style="color: #dc2626;"><i class="fas fa-exclamation-triangle"></i> <?php echo $product['stock_quantity']; ?></span>
                                        <?php else: ?>
                                        <?php echo $product['stock_quantity']; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo (int)$product['last_month_sales']; ?></td>
                                    <td><?php echo (int)$product['prev_month_sales']; ?></td>
                                    <td><strong><?php echo $prediction['predicted']; ?></strong></td>
                                    <td>
                                        <span class="demand-badge badge-<?php echo $prediction['trend']; ?>">
                                            <i class="fas fa-arrow-<?php echo $prediction['trend'] == 'increasing' ? 'up' : ($prediction['trend'] == 'decreasing' ? 'down' : 'right'); ?>"></i>
                                            <?php echo ucfirst($prediction['trend']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- AI Description Generator Tab -->
                <div class="tab-content" id="tab-generator">
                    <div class="ai-generator">
                        <h3 style="margin: 0 0 10px; font-size: 20px;"><i class="fas fa-wand-magic-sparkles" style="color: #8b5cf6;"></i> AI Product Description Generator</h3>
                        <p style="color: #6b7280; margin-bottom: 25px;">Generate compelling product descriptions instantly</p>
                        
                        <div class="generator-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="gen_product">Select Product (Optional)</label>
                                    <select id="gen_product" onchange="loadProductData()">
                                        <option value="">-- Or enter details below --</option>
                                        <?php foreach ($all_products as $prod): ?>
                                        <option value="<?php echo $prod['product_id']; ?>" 
                                                data-name="<?php echo htmlspecialchars($prod['product_name']); ?>"
                                                data-category="<?php echo htmlspecialchars($prod['category_name'] ?? ''); ?>">
                                            <?php echo htmlspecialchars($prod['product_name']); ?> (<?php echo $prod['store_name'] ?? 'Unknown'; ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="gen_tone">Tone</label>
                                    <select id="gen_tone">
                                        <option value="professional">Professional</option>
                                        <option value="casual">Casual</option>
                                        <option value="luxury">Luxury</option>
                                        <option value="minimalist">Minimalist</option>
                                        <option value="persuasive">Persuasive</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="gen_name">Product Name *</label>
                                    <input type="text" id="gen_name" placeholder="e.g., Modern Leather Sofa">
                                </div>
                                <div class="form-group">
                                    <label for="gen_category">Category</label>
                                    <input type="text" id="gen_category" placeholder="e.g., Living Room">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="gen_features">Key Features (comma separated)</label>
                                <input type="text" id="gen_features" placeholder="e.g., genuine leather, reclining, brown">
                            </div>
                            <button type="button" class="generate-btn" onclick="generateDescription()">
                                <i class="fas fa-sparkles"></i> Generate Description
                            </button>
                        </div>
                        
                        <div class="generated-result" id="generatedResult">
                            <h4><i class="fas fa-check-circle"></i> Generated Description</h4>
                            <div class="generated-text" id="generatedText"></div>
                            <div class="result-actions">
                                <button class="copy-btn" onclick="copyDescription()"><i class="fas fa-copy"></i> Copy</button>
                                <button class="regenerate-btn" onclick="generateDescription()"><i class="fas fa-refresh"></i> Regenerate</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Tab switching
        document.querySelectorAll('.ai-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.ai-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                tab.classList.add('active');
                document.getElementById('tab-' + tab.dataset.tab).classList.add('active');
            });
        });
        
        // Chart Data
        const monthlyData = <?php echo json_encode($monthly_sales); ?>;
        const forecastValue = <?php echo $forecast['forecast']; ?>;
        const categoryData = <?php echo json_encode($category_performance); ?>;
        const trendingData = <?php echo json_encode(array_slice($trending_products, 0, 5)); ?>;
        const storeData = <?php echo json_encode($store_performance); ?>;
        
        const labels = monthlyData.map(d => d.month_label);
        const revenues = monthlyData.map(d => parseFloat(d.revenue));
        const orders = monthlyData.map(d => parseInt(d.total_orders));
        
        const nextMonth = new Date();
        nextMonth.setMonth(nextMonth.getMonth() + 1);
        labels.push(nextMonth.toLocaleString('default', { month: 'short', year: 'numeric' }) + ' (F)');
        revenues.push(forecastValue);
        
        // Revenue Chart
        new Chart(document.getElementById('revenueChart'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Revenue (₱)',
                    data: revenues,
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: revenues.map((_, i) => i === revenues.length - 1 ? '#f59e0b' : '#8b5cf6'),
                    pointRadius: revenues.map((_, i) => i === revenues.length - 1 ? 8 : 5)
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { callback: v => '₱' + v.toLocaleString() } } }
            }
        });
        
        // Category Chart
        new Chart(document.getElementById('categoryChart'), {
            type: 'doughnut',
            data: {
                labels: categoryData.map(c => c.category_name || 'Uncategorized'),
                datasets: [{ data: categoryData.map(c => parseFloat(c.revenue) || 0), backgroundColor: ['#8b5cf6', '#10b981', '#f59e0b', '#3b82f6', '#ec4899', '#6366f1', '#14b8a6', '#f97316'], borderWidth: 0 }]
            },
            options: { responsive: true, plugins: { legend: { position: 'right' } } }
        });
        
        // Orders Chart
        new Chart(document.getElementById('ordersChart'), {
            type: 'bar',
            data: {
                labels: monthlyData.map(d => d.month_label),
                datasets: [{ label: 'Orders', data: orders, backgroundColor: '#10b981', borderRadius: 8 }]
            },
            options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });
        
        // Trending Chart
        new Chart(document.getElementById('trendingChart'), {
            type: 'bar',
            data: {
                labels: trendingData.map(p => p.product_name.substring(0, 20) + (p.product_name.length > 20 ? '...' : '')),
                datasets: [{ label: 'Units Sold', data: trendingData.map(p => parseInt(p.units_sold) || 0), backgroundColor: ['#fbbf24', '#9ca3af', '#d97706', '#10b981', '#3b82f6'], borderRadius: 8 }]
            },
            options: { indexAxis: 'y', responsive: true, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true } } }
        });
        
        // Store Chart
        new Chart(document.getElementById('storeChart'), {
            type: 'bar',
            data: {
                labels: storeData.map(s => s.store_name),
                datasets: [{ label: 'Revenue (₱)', data: storeData.map(s => parseFloat(s.revenue) || 0), backgroundColor: '#8b5cf6', borderRadius: 8 }]
            },
            options: { indexAxis: 'y', responsive: true, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { callback: v => '₱' + v.toLocaleString() } } } }
        });
        
        // AI Generator Functions
        function loadProductData() {
            const select = document.getElementById('gen_product');
            const option = select.options[select.selectedIndex];
            if (option.value) {
                document.getElementById('gen_name').value = option.dataset.name || '';
                document.getElementById('gen_category').value = option.dataset.category || '';
            }
        }
        
        function generateDescription() {
            const name = document.getElementById('gen_name').value.trim();
            const category = document.getElementById('gen_category').value.trim();
            const features = document.getElementById('gen_features').value.trim();
            const tone = document.getElementById('gen_tone').value;
            
            if (!name) { alert('Please enter a product name'); return; }
            
            const btn = document.querySelector('.generate-btn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
            
            setTimeout(() => {
                const featureList = features ? features.split(',').map(f => f.trim()).filter(f => f) : [];
                const tones = {
                    professional: { intro: `Introducing the ${name}, a distinguished addition to your ${category || 'home'}.`, body: `This meticulously crafted piece combines functionality with elegant design.`, closing: `An investment in quality.` },
                    casual: { intro: `Meet your new favorite - the ${name}!`, body: `Designed with you in mind, making everyday living more enjoyable.`, closing: `Get ready to love your space!` },
                    luxury: { intro: `Experience unparalleled luxury with the exquisite ${name}.`, body: `Handcrafted with premium materials and unwavering attention to detail.`, closing: `For those who accept nothing less than extraordinary.` },
                    minimalist: { intro: `${name}. Simple. Elegant. Perfect.`, body: `Form follows function.`, closing: `Less is more.` },
                    persuasive: { intro: `Transform your space with the stunning ${name}!`, body: `Join thousands of satisfied customers.`, closing: `Order now - limited stock!` }
                };
                
                const style = tones[tone];
                let desc = `${style.intro}\n\n${style.body}`;
                if (featureList.length) desc += `\n\nFeatures: ${featureList.join(', ')}.`;
                desc += `\n\n${style.closing}`;
                
                document.getElementById('generatedText').textContent = desc;
                document.getElementById('generatedResult').classList.add('active');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-sparkles"></i> Generate Description';
            }, 1500);
        }
        
        function copyDescription() {
            navigator.clipboard.writeText(document.getElementById('generatedText').textContent).then(() => {
                const btn = document.querySelector('.copy-btn');
                btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
                setTimeout(() => btn.innerHTML = '<i class="fas fa-copy"></i> Copy', 2000);
            });
        }
    </script>
</body>
</html>
