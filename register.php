<?php
require_once 'config/database.php';

// Check if an owner already exists
$database = new Database();
$db = $database->getConnection();
$owner_query = "SELECT COUNT(*) as count FROM users WHERE role = 'owner'";
$owner_result = $db->query($owner_query)->fetch();
$owner_exists = $owner_result['count'] > 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - FurnHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/modern-ui.js" defer></script>
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box register-box">
            <div class="logo">
                <h1><i class="fas fa-couch"></i> FurnHub</h1>
                <p>Create Your Account</p>
            </div>
            
            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$owner_exists): ?>
            <!-- No owner exists - only allow owner registration first -->
            <div class="alert alert-warning" style="background: #fef3c7; border: 1px solid #f59e0b; color: #92400e; padding: 16px; border-radius: 10px; margin-bottom: 20px;">
                <i class="fas fa-exclamation-triangle"></i> <strong>Setup Required:</strong> A Business Owner account must be created first before customers and riders can register.
            </div>
            
            <form action="auth/register_process.php" method="POST" class="login-form">
                <input type="hidden" name="role" value="owner">
                
                <div class="form-group">
                    <label for="full_name">Store/Business Name</label>
                    <input type="text" id="full_name" name="full_name" placeholder="Enter your store name" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="text" id="phone" name="phone" required>
                </div>
                
                <div class="form-group">
                    <label for="address">Business Address</label>
                    <textarea id="address" name="address" rows="3" placeholder="Store Address" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Create Owner Account</button>
            </form>
            <?php else: ?>
            <!-- Owner exists - allow customer/rider registration -->
            <form action="auth/register_process.php" method="POST" class="login-form">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="text" id="phone" name="phone" required>
                </div>
                
                <div class="form-group">
                    <label for="address">Complete Address</label>
                    <textarea id="address" name="address" rows="3" placeholder="House No., Street, Barangay, City" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="role">Register As</label>
                    <select id="role" name="role" required>
                        <option value="customer" selected>Customer</option>
                        <option value="rider">Rider</option>
                    </select>
                    <small style="color: #64748b; font-size: 12px; display: block; margin-top: 5px;">
                        <i class="fas fa-lightbulb"></i> Customers can shop, Riders can deliver orders.
                    </small>
                </div>
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Register</button>
            </form>
            <?php endif; ?>
            
            <div class="login-footer">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>
</body>
</html>
