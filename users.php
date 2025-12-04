<?php
require_once '../config/config.php';
checkRole(['admin']);

$database = new Database();
$db = $database->getConnection();

// Get all users
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? sanitize($_GET['role']) : '';

$query = "SELECT * FROM users WHERE 1=1";

if (!empty($search)) {
    $query .= " AND (full_name LIKE :search OR email LIKE :search OR username LIKE :search)";
}

if (!empty($role_filter)) {
    $query .= " AND role = :role";
}

$query .= " ORDER BY created_at DESC";

$stmt = $db->prepare($query);

if (!empty($search)) {
    $search_param = "%$search%";
    $stmt->bindParam(':search', $search_param);
}

if (!empty($role_filter)) {
    $stmt->bindParam(':role', $role_filter);
}

$stmt->execute();
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin</title>
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
                <h1>Manage Users</h1>
                <a href="add_user.php" class="btn btn-primary">+ Add User</a>
            </div>
            
            <div class="filter-section">
                <form method="GET" class="filter-form">
                    <input type="text" name="search" placeholder="Search users..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    
                    <select name="role">
                        <option value="">All Roles</option>
                        <option value="admin" <?php echo $role_filter == 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="owner" <?php echo $role_filter == 'owner' ? 'selected' : ''; ?>>Owner</option>
                        <option value="rider" <?php echo $role_filter == 'rider' ? 'selected' : ''; ?>>Rider</option>
                        <option value="customer" <?php echo $role_filter == 'customer' ? 'selected' : ''; ?>>Customer</option>
                    </select>
                    
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="users.php" class="btn">Reset</a>
                </form>
            </div>
            
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Full Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['full_name']; ?></td>
                            <td><?php echo $user['username']; ?></td>
                            <td><?php echo $user['email']; ?></td>
                            <td><?php echo $user['phone']; ?></td>
                            <td><span class="badge badge-role-<?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span></td>
                            <td><span class="badge badge-<?php echo $user['status']; ?>"><?php echo ucfirst($user['status']); ?></span></td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <a href="edit_user.php?id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                <?php if ($user['role'] === 'owner'): ?>
                                <a href="delete_user.php?id=<?php echo $user['user_id']; ?>" 
                                   class="btn btn-sm btn-danger" 
                                   onclick="return confirm('WARNING: Deleting this owner will also DELETE ALL customers and riders!\n\nAre you sure you want to proceed?')">Delete</a>
                                <?php else: ?>
                                <a href="delete_user.php?id=<?php echo $user['user_id']; ?>" 
                                   class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                                <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="8" class="text-center">No users found</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
