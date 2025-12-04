<?php
require_once '../config/config.php';
checkRole(['admin']);

$database = new Database();
$db = $database->getConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $full_name = sanitize($_POST['full_name']);
    $phone = sanitize($_POST['phone']);
    $role = sanitize($_POST['role']);
    $status = sanitize($_POST['status']);
    
    if (empty($username) || empty($email) || empty($password) || empty($full_name) || empty($role)) {
        $error = "Please fill in all required fields";
    } else if (strlen($password) < 6) {
        $error = "Password must be at least 6 characters";
    } else {
        try {
            // Check if username or email exists
            $check_query = "SELECT user_id FROM users WHERE username = :username OR email = :email";
            $stmt = $db->prepare($check_query);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $error = "Username or email already exists";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $query = "INSERT INTO users (username, email, password, full_name, phone, role, status) 
                            VALUES (:username, :email, :password, :full_name, :phone, :role, :status)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':password', $hashed_password);
                $stmt->bindParam(':full_name', $full_name);
                $stmt->bindParam(':phone', $phone);
                $stmt->bindParam(':role', $role);
                $stmt->bindParam(':status', $status);
                
                if ($stmt->execute()) {
                    header('Location: users.php?success=User added successfully');
                    exit();
                }
            }
        } catch (PDOException $e) {
            $error = "Failed to add user";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/js/modern-ui.js" defer></script>
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>
    
    <div class="dashboard-container">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1>Add New User</h1>
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
                            <input type="text" id="full_name" name="full_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="username">Username *</label>
                            <input type="text" id="username" name="username" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="text" id="phone" name="phone">
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password *</label>
                            <input type="password" id="password" name="password" required minlength="6">
                        </div>
                        
                        <div class="form-group">
                            <label for="role">Role *</label>
                            <select id="role" name="role" required>
                                <option value="customer">Customer</option>
                                <option value="rider">Rider</option>
                                <option value="owner">Owner</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Status *</label>
                            <select id="status" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Add User</button>
                        <a href="users.php" class="btn">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
