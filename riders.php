<?php
require_once '../config/config.php';
checkRole(['admin']);

$database = new Database();
$db = $database->getConnection();

// Get all riders with their statistics
$query = "SELECT u.*,
          (SELECT COUNT(*) FROM orders WHERE rider_id = u.user_id) as total_deliveries,
          (SELECT COUNT(*) FROM orders WHERE rider_id = u.user_id AND status = 'out_for_delivery') as active_deliveries,
          (SELECT COUNT(*) FROM orders WHERE rider_id = u.user_id AND status = 'delivered') as completed_deliveries,
          (SELECT COALESCE(SUM(delivery_fee), 0) FROM orders WHERE rider_id = u.user_id AND status = 'delivered') as total_earnings
          FROM users u
          WHERE u.role = 'rider'
          ORDER BY u.full_name ASC";
$stmt = $db->query($query);
$riders = $stmt->fetchAll();

// Get unassigned deliveries
$query = "SELECT COUNT(*) as count FROM orders WHERE rider_id IS NULL AND status IN ('pending', 'confirmed', 'processing')";
$unassigned = $db->query($query)->fetch()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rider Management - Admin</title>
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
                <h1>Rider Management</h1>
                <a href="add_user.php?role=rider" class="btn btn-primary">+ Add New Rider</a>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
            <?php endif; ?>
            
            <?php if ($unassigned > 0): ?>
            <div class="alert alert-warning">
                <strong><i class="fas fa-exclamation-triangle"></i> Attention:</strong> There are <strong><?php echo $unassigned; ?></strong> unassigned deliveries waiting for rider assignment.
                <a href="assign_riders.php" class="btn btn-sm" style="margin-left: 10px;">Assign Riders</a>
            </div>
            <?php endif; ?>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-biking"></i></div>
                    <div class="stat-info">
                        <h3><?php echo count($riders); ?></h3>
                        <p>Total Riders</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-info">
                        <h3><?php echo count(array_filter($riders, fn($r) => $r['status'] == 'active')); ?></h3>
                        <p>Active Riders</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange"><i class="fas fa-truck"></i></div>
                    <div class="stat-info">
                        <h3><?php echo array_sum(array_column($riders, 'active_deliveries')); ?></h3>
                        <p>Active Deliveries</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="fas fa-box"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $unassigned; ?></h3>
                        <p>Unassigned Orders</p>
                    </div>
                </div>
            </div>
            
            <div class="content-section">
                <h2>All Riders</h2>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Rider Name</th>
                                <th>Username</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Active</th>
                                <th>Completed</th>
                                <th>Total</th>
                                <th>Earnings</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($riders as $rider): ?>
                            <tr>
                                <td><strong><?php echo $rider['full_name']; ?></strong></td>
                                <td><?php echo $rider['username']; ?></td>
                                <td><a href="tel:<?php echo $rider['phone']; ?>" class="phone-link"><i class="fas fa-phone"></i> <?php echo $rider['phone']; ?></a></td>
                                <td><?php echo $rider['email']; ?></td>
                                <td><span class="badge badge-<?php echo $rider['status']; ?>"><?php echo ucfirst($rider['status']); ?></span></td>
                                <td>
                                    <?php if ($rider['active_deliveries'] > 0): ?>
                                    <strong style="color: #f59e0b;"><?php echo $rider['active_deliveries']; ?> active</strong>
                                    <?php else: ?>
                                    <span style="color: #10b981;">Available</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $rider['completed_deliveries']; ?></td>
                                <td><?php echo $rider['total_deliveries']; ?></td>
                                <td><strong>₱<?php echo number_format($rider['total_earnings'], 2); ?></strong></td>
                                <td>
                                    <a href="rider_details.php?id=<?php echo $rider['user_id']; ?>" class="btn btn-sm btn-primary">View</a>
                                    <a href="assign_to_rider.php?rider_id=<?php echo $rider['user_id']; ?>" class="btn btn-sm btn-success">Assign Order</a>
                                    <a href="edit_user.php?id=<?php echo $rider['user_id']; ?>" class="btn btn-sm">Edit</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($riders)): ?>
                            <tr>
                                <td colspan="10" class="text-center">No riders found. <a href="add_user.php?role=rider">Add a rider</a></td>
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
