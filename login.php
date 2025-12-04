<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FurnHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/modern-ui.js" defer></script>
</head>
<body class="login-page">
    <?php
    // Check if database exists
    try {
        $test_conn = new PDO("mysql:host=localhost;dbname=furnhub_db", "root", "");
        $test_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Check if users exist
        $check = $test_conn->query("SELECT COUNT(*) as count FROM users");
        if ($check->fetch()['count'] == 0) {
            header('Location: setup.php');
            exit();
        }
    } catch(PDOException $e) {
        header('Location: setup.php');
        exit();
    }
    ?>
    <div class="login-container">
        <div class="login-box">
            <div class="logo">
                <h1><i class="fas fa-couch"></i> FurnHub</h1>
                <p>Furniture Ordering System</p>
            </div>
            
            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>
            
            <form action="auth/login_process.php" method="POST" class="login-form">
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>
            
            <div class="login-footer">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
            </div>
            
            <!-- <div class="demo-accounts">
                <h4>Demo Accounts:</h4>
                <ul>
                    <li><strong>Admin:</strong> admin / admin123</li>
                    <li><strong>Owner:</strong> owner / admin123</li>
                    <li><strong>Rider:</strong> rider1 / admin123</li>
                </ul>
            </div> -->
        </div>
    </div>
</body>
</html>
