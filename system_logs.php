<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Get system settings for page title with error handling
try {
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'system_settings'");
    if ($tableCheck->rowCount() > 0) {
        $query = "SELECT system_name FROM system_settings LIMIT 1";
        $stmt = $pdo->query($query);
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        $system_name = $settings['system_name'] ?? 'FurnHub';
    } else {
        $system_name = 'FurnHub';
    }
} catch (Exception $e) {
    $system_name = 'FurnHub';
}

// Get activity logs with error handling
$logs = [];
$totalLogs = 0;
$totalPages = 1;

try {
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'activity_logs'");
    
    if ($tableCheck->rowCount() > 0) {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        $logQuery = "SELECT al.*, u.full_name, u.email 
                     FROM activity_logs al 
                     LEFT JOIN users u ON al.user_id = u.id 
                     ORDER BY al.created_at DESC 
                     LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($logQuery);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get total count
        $countQuery = "SELECT COUNT(*) FROM activity_logs";
        $totalLogs = $pdo->query($countQuery)->fetchColumn();
        $totalPages = ceil($totalLogs / $perPage);
    }
} catch (Exception $e) {
    // Error occurred, logs will be empty
    $logs = [];
}

// Get system stats with error handling
$stats = [
    'total_users' => 0,
    'total_orders' => 0,
    'total_products' => 0,
    'total_logs' => $totalLogs
];

try {
    $statsQuery = "SELECT 
        (SELECT COUNT(*) FROM users) as total_users,
        (SELECT COUNT(*) FROM orders) as total_orders,
        (SELECT COUNT(*) FROM products) as total_products";
    $statsResult = $pdo->query($statsQuery)->fetch(PDO::FETCH_ASSOC);
    if ($statsResult) {
        $stats = array_merge($stats, $statsResult);
        $stats['total_logs'] = $totalLogs;
    }
} catch (Exception $e) {
    // Keep default stats
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Logs - <?php echo htmlspecialchars($system_name); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
                <h1><i class="fas fa-clipboard-list"></i> System Activity Logs</h1>
                <p>Monitor all system activities and user actions</p>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card scroll-fade">
                    <div class="stat-icon blue"><i class="fas fa-file-alt"></i></div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['total_logs']); ?></h3>
                        <p>Total Logs</p>
                    </div>
                </div>
                <div class="stat-card scroll-fade">
                    <div class="stat-icon green"><i class="fas fa-users"></i></div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['total_users']); ?></h3>
                        <p>Total Users</p>
                    </div>
                </div>
                <div class="stat-card scroll-fade">
                    <div class="stat-icon purple"><i class="fas fa-box"></i></div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['total_orders']); ?></h3>
                        <p>Total Orders</p>
                    </div>
                </div>
                <div class="stat-card scroll-fade">
                    <div class="stat-icon orange"><i class="fas fa-couch"></i></div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['total_products']); ?></h3>
                        <p>Total Products</p>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="content-section scroll-fade" style="margin-top: 24px;">
                <div style="display: flex; gap: 12px; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                        <select id="actionFilter" class="form-control" style="width: auto;" onchange="filterLogs()">
                            <option value="">All Actions</option>
                            <option value="login">Login</option>
                            <option value="logout">Logout</option>
                            <option value="create">Create</option>
                            <option value="update">Update</option>
                            <option value="delete">Delete</option>
                        </select>
                        <select id="roleFilter" class="form-control" style="width: auto;" onchange="filterLogs()">
                            <option value="">All Roles</option>
                            <option value="admin">Admin</option>
                            <option value="owner">Owner</option>
                            <option value="rider">Rider</option>
                            <option value="customer">Customer</option>
                        </select>
                        <input type="date" id="dateFilter" class="form-control" style="width: auto;" onchange="filterLogs()">
                    </div>
                    <div style="display: flex; gap: 12px;">
                        <button class="btn btn-secondary" onclick="clearLogs()">
                            <i class="fas fa-trash"></i> Clear Old Logs
                        </button>
                        <button class="btn btn-primary" onclick="exportLogs()">
                            <i class="fas fa-download"></i> Export Logs
                        </button>
                    </div>
                </div>
            </div>

            <!-- Logs Table -->
            <div class="content-section scroll-fade" style="margin-top: 24px;">
                <h3 style="margin-bottom: 16px;">Recent Activity</h3>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>User</th>
                                <th>Role</th>
                                <th>Action</th>
                                <th>Description</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px; color: #64748b;">
                                        <i class="fas fa-clipboard-list"></i> No activity logs found
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight: 600;">
                                                <?php echo date('M d, Y', strtotime($log['created_at'])); ?>
                                            </div>
                                            <div style="font-size: 12px; color: #64748b;">
                                                <?php echo date('h:i A', strtotime($log['created_at'])); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="font-weight: 600;">
                                                <?php echo htmlspecialchars($log['full_name'] ?? 'System'); ?>
                                            </div>
                                            <div style="font-size: 12px; color: #64748b;">
                                                <?php echo htmlspecialchars($log['email'] ?? 'N/A'); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php
                                            $roleColors = [
                                                'admin' => 'danger',
                                                'owner' => 'warning',
                                                'rider' => 'info',
                                                'customer' => 'success'
                                            ];
                                            $role = $log['role'] ?? 'system';
                                            $badgeClass = $roleColors[$role] ?? 'secondary';
                                            ?>
                                            <span class="badge badge-<?php echo $badgeClass; ?>">
                                                <?php echo ucfirst($role); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-primary">
                                                <?php echo htmlspecialchars($log['action']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($log['description']); ?></td>
                                        <td style="font-family: monospace; font-size: 12px;">
                                            <?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div style="display: flex; justify-content: center; gap: 8px; margin-top: 24px;">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>" 
                               class="btn <?php echo $i === $page ? 'btn-primary' : 'btn-secondary'; ?>"
                               style="padding: 8px 16px;">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        function filterLogs() {
            const action = document.getElementById('actionFilter').value;
            const role = document.getElementById('roleFilter').value;
            const date = document.getElementById('dateFilter').value;
            
            let url = 'system_logs.php?';
            if (action) url += 'action=' + action + '&';
            if (role) url += 'role=' + role + '&';
            if (date) url += 'date=' + date;
            
            window.location.href = url;
        }

        function clearLogs() {
            showConfirmDialog(
                'Are you sure you want to clear old logs? This will delete logs older than 90 days.',
                () => {
                    fetch('clear_old_logs.php', {
                        method: 'POST'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Toast.success(data.message);
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            Toast.error(data.message);
                        }
                    });
                }
            );
        }

        function exportLogs() {
            Toast.info('Preparing logs export...');
            window.location.href = 'export_logs.php';
        }
    </script>
</body>
</html>
