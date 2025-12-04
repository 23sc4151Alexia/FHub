<?php
require_once '../config/config.php';
checkRole(['admin']);

if (!isset($_GET['id'])) {
    header('Location: users.php');
    exit();
}

$user_id = (int)$_GET['id'];

$database = new Database();
$db = $database->getConnection();

// Get user details
$query = "SELECT * FROM users WHERE user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header('Location: users.php?error=User not found');
    exit();
}

$user = $stmt->fetch();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $full_name = sanitize($_POST['full_name']);
    $phone = sanitize($_POST['phone']);
    $role = sanitize($_POST['role']);
    $status = sanitize($_POST['status']);
    $new_password = $_POST['password'];
    
    if (empty($username) || empty($email) || empty($full_name) || empty($role)) {
        $error = "Please fill in all required fields";
    } else {
        try {
            // Check if username or email exists for other users
            $check_query = "SELECT user_id FROM users WHERE (username = :username OR email = :email) AND user_id != :user_id";
            $stmt = $db->prepare($check_query);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $error = "Username or email already exists";
            } else {
                // Update user
                if (!empty($new_password)) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $query = "UPDATE users SET 
                                username = :username,
                                email = :email,
                                password = :password,
                                full_name = :full_name,
                                phone = :phone,
                                role = :role,
                                status = :status,
                                updated_at = NOW()
                                WHERE user_id = :user_id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':password', $hashed_password);
                } else {
                    $query = "UPDATE users SET 
                                username = :username,
                                email = :email,
                                full_name = :full_name,
                                phone = :phone,
                                role = :role,
                                status = :status,
                                updated_at = NOW()
                                WHERE user_id = :user_id";
                    $stmt = $db->prepare($query);
                }
                
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':full_name', $full_name);
                $stmt->bindParam(':phone', $phone);
                $stmt->bindParam(':role', $role);
                $stmt->bindParam(':status', $status);
                $stmt->bindParam(':user_id', $user_id);
                
                if ($stmt->execute()) {
                    header('Location: users.php?success=User updated successfully');
                    exit();
                }
            }
        } catch (PDOException $e) {
            $error = "Failed to update user";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/js/modern-ui.js" defer></script>
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>
    
    <div class="dashboard-container">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1>Edit User</h1>
                <a href="users.php" class="btn">← Back</a>
            </div>
            
            <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="content-section">
                <form action="" method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="full_name">Full Name *</label>
                            <input type="text" id="full_name" name="full_name" 
                                    value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="username">Username *</label>
                            <input type="text" id="username" name="username" 
                                    value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" 
                                    value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="text" id="phone" name="phone" 
                                    value="<?php echo htmlspecialchars($user['phone']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="password">New Password (leave blank to keep current)</label>
                            <input type="password" id="password" name="password">
                        </div>
                        
                        <div class="form-group">
                            <label for="role">Role *</label>
                            <select id="role" name="role" required>
                                <option value="customer" <?php echo $user['role'] == 'customer' ? 'selected' : ''; ?>>Customer</option>
                                <option value="rider" <?php echo $user['role'] == 'rider' ? 'selected' : ''; ?>>Rider</option>
                                <option value="owner" <?php echo $user['role'] == 'owner' ? 'selected' : ''; ?>>Owner</option>
                                <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Status *</label>
                            <select id="status" name="status" required>
                                <option value="active" <?php echo $user['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $user['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Update User</button>
                        <a href="users.php" class="btn">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
