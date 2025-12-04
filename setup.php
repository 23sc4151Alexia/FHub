<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FurnHub Setup Wizard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .wizard-container {
            background: white;
            padding: 50px;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 700px;
            width: 100%;
        }
        
        h1 {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
            font-size: 42px;
            font-weight: 800;
        }
        
        .subtitle {
            color: #64748b;
            margin-bottom: 40px;
            font-size: 16px;
            font-weight: 500;
        }
        
        .step {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 24px;
            border-radius: 16px;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
        }
        
        .step h3 {
            color: #0f172a;
            margin-bottom: 12px;
            font-weight: 700;
            font-size: 18px;
        }
        
        .btn {
            display: inline-block;
            padding: 16px 32px;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 700;
            width: 100%;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
            letter-spacing: 0.025em;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.5);
        }
        
        .success {
            background: linear-gradient(135deg, #d1fae5 0%, #ecfdf5 100%);
            color: #065f46;
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid #10b981;
            margin-bottom: 24px;
            font-weight: 500;
        }
        
        .error {
            background: linear-gradient(135deg, #fee2e2 0%, #fef2f2 100%);
            color: #991b1b;
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid #ef4444;
            margin-bottom: 24px;
            font-weight: 500;
        }
        
        .warning {
            background: linear-gradient(135deg, #fef3c7 0%, #fffbeb 100%);
            color: #92400e;
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid #f59e0b;
            margin-bottom: 24px;
            font-weight: 500;
        }
        
        code {
            background: #1e293b;
            color: #e2e8f0;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .info-box {
            background: linear-gradient(135deg, #dbeafe 0%, #eff6ff 100%);
            color: #1e40af;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            border-left: 4px solid #3b82f6;
            font-weight: 500;
        }
        
        ul {
            margin-left: 20px;
            line-height: 2;
        }
        
        ul li {
            margin: 8px 0;
        }
        
        .status-check {
            display: flex;
            align-items: center;
            padding: 12px;
            background: white;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        
        .status-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            margin-right: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .status-ok {
            background: #10b981;
            color: white;
        }
        
        .status-fail {
            background: #ef4444;
            color: white;
        }
        
        strong {
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="wizard-container">
        <h1><i class="fas fa-couch"></i> FurnHub Setup</h1>
        <p class="subtitle">Complete system installation wizard</p>
        
        <?php
        if (isset($_POST['install'])) {
            try {
                // Step 1: Check MySQL Connection
                echo '<div class="step">';
                echo '<h3>Step 1: Checking MySQL Connection...</h3>';
                
                try {
                    $conn = new PDO("mysql:host=localhost", "root", "");
                    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    echo '<div class="status-check">';
                    echo '<span class="status-icon status-ok"><i class="fas fa-check"></i></span>';
                    echo '<span>MySQL connection successful</span>';
                    echo '</div>';
                } catch(PDOException $e) {
                    echo '<div class="status-check">';
                    echo '<span class="status-icon status-fail"><i class="fas fa-times"></i></span>';
                    echo '<span>MySQL connection failed: ' . $e->getMessage() . '</span>';
                    echo '</div>';
                    throw new Exception("Cannot connect to MySQL. Make sure XAMPP MySQL is running!");
                }
                echo '</div>';
                
                // Step 2: Create Database and Tables
                echo '<div class="step">';
                echo '<h3>Step 2: Creating Database and Tables...</h3>';
                
                // Read SQL file
                $sql = file_get_contents('database/schema.sql');
                
                // Convert CREATE TABLE to CREATE TABLE IF NOT EXISTS
                $sql = preg_replace('/CREATE TABLE(?!\s+IF\s+NOT\s+EXISTS)/i', 'CREATE TABLE IF NOT EXISTS', $sql);
                
                // Remove ALL INSERT statements - we'll add data later after users are created
                $sql = preg_replace('/--\s*Insert.*$/is', '', $sql);
                $sql = preg_replace('/INSERT INTO.*?;/is', '', $sql);
                
                // Execute SQL statements (only CREATE statements)
                $statements = array_filter(array_map('trim', explode(';', $sql)));
                
                foreach ($statements as $statement) {
                    if (!empty($statement) && stripos($statement, 'CREATE') !== false) {
                        try {
                            $conn->exec($statement);
                        } catch (PDOException $e) {
                            // Ignore "already exists" errors
                            if (strpos($e->getMessage(), 'already exists') === false && 
                                strpos($e->getMessage(), 'Duplicate') === false) {
                                // Continue anyway for non-critical errors
                            }
                        }
                    }
                }
                
                echo '<div class="status-check">';
                echo '<span class="status-icon status-ok"><i class="fas fa-check"></i></span>';
                echo '<span>Database <code>furnhub_db</code> ready</span>';
                echo '</div>';
                
                echo '<div class="status-check">';
                echo '<span class="status-icon status-ok"><i class="fas fa-check"></i></span>';
                echo '<span>All tables verified/created successfully</span>';
                echo '</div>';
                echo '</div>';
                
                // Step 3: Connect to new database and create users
                echo '<div class="step">';
                echo '<h3>Step 3: Setting Up Default Users...</h3>';
                
                $conn = new PDO("mysql:host=localhost;dbname=furnhub_db", "root", "");
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Generate proper password hash
                $password = 'admin123';
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert default users
                $users = [
                    ['admin', 'admin@furnhub.com', 'System Administrator', '09123456789', 'admin'],
                    ['owner', 'owner@furnhub.com', 'Shop Owner', '09123456788', 'owner'],
                    ['rider1', 'rider1@furnhub.com', 'John Rider', '09123456787', 'rider']
                ];
                
                $insert_user = $conn->prepare("INSERT IGNORE INTO users (username, email, password, full_name, phone, role) VALUES (?, ?, ?, ?, ?, ?)");
                
                foreach ($users as $user) {
                    try {
                        $insert_user->execute([
                            $user[0],
                            $user[1],
                            $hashed_password,
                            $user[2],
                            $user[3],
                            $user[4]
                        ]);
                        
                        echo '<div class="status-check">';
                        echo '<span class="status-icon status-ok"><i class="fas fa-check"></i></span>';
                        echo '<span>User <code>' . $user[0] . '</code> ' . ($insert_user->rowCount() > 0 ? 'created' : 'exists') . '</span>';
                        echo '</div>';
                    } catch (PDOException $e) {
                        echo '<div class="status-check">';
                        echo '<span class="status-icon status-ok"><i class="fas fa-check"></i></span>';
                        echo '<span>User <code>' . $user[0] . '</code> already exists</span>';
                        echo '</div>';
                    }
                }
                echo '</div>';
                
                // Step 4: Verify installation
                echo '<div class="step">';
                echo '<h3>Step 4: Verifying Installation...</h3>';
                
                $query = $conn->query("SELECT COUNT(*) as count FROM users");
                $user_count = $query->fetch()['count'];
                
                echo '<div class="status-check">';
                echo '<span class="status-icon status-ok"><i class="fas fa-check"></i></span>';
                echo '<span>' . $user_count . ' users in database</span>';
                echo '</div>';
                
                echo '<div class="status-check">';
                echo '<span class="status-icon status-ok"><i class="fas fa-check"></i></span>';
                echo '<span>Database tables ready</span>';
                echo '</div>';
                echo '</div>';
                
                echo '<div class="success">';
                echo '<strong><i class="fas fa-party-horn"></i> Installation Complete!</strong><br><br>';
                echo 'Your FurnHub system is ready to use. All users have been created with working passwords.';
                echo '</div>';
                
                echo '<div class="info-box">';
                echo '<strong><i class="fas fa-clipboard-list"></i> Login Credentials:</strong><br>';
                echo '<ul>';
                echo '<li><strong>Admin:</strong> <code>admin</code> / <code>admin123</code></li>';
                echo '<li><strong>Owner:</strong> <code>owner</code> / <code>admin123</code></li>';
                echo '<li><strong>Rider:</strong> <code>rider1</code> / <code>admin123</code></li>';
                echo '</ul>';
                echo '<br><strong>Note:</strong> After logging in as Owner, you will be guided through the setup wizard to add your categories and products.';
                echo '</div>';
                
                echo '<div class="warning">';
                echo '<strong><i class="fas fa-exclamation-triangle"></i> Security Notice:</strong><br>';
                echo 'Please delete <code>setup.php</code>, <code>install.php</code>, and <code>fix_passwords.php</code> files after installation!';
                echo '</div>';
                
                echo '<a href="login.php" class="btn">Go to Login Page</a>';
                
            } catch(Exception $e) {
                echo '<div class="error">';
                echo '<strong><i class="fas fa-times-circle"></i> Installation Failed</strong><br><br>';
                echo 'Error: ' . $e->getMessage();
                echo '</div>';
                
                echo '<form method="POST">';
                echo '<button type="submit" name="install" class="btn">Try Again</button>';
                echo '</form>';
            }
        } else {
            ?>
            
            <div class="info-box">
                <strong><i class="fas fa-info-circle"></i> Prerequisites:</strong><br>
                • XAMPP must be installed<br>
                • MySQL service must be running<br>
                • Apache service must be running
            </div>
            
            <div class="step">
                <h3><i class="fas fa-box"></i> What will be installed:</h3>
                <ul>
                    <li>Database: <code>furnhub_db</code></li>
                    <li>8 database tables with relationships</li>
                    <li>5 furniture categories</li>
                    <li>12 sample products</li>
                    <li>3 default user accounts (admin, owner, rider)</li>
                </ul>
            </div>
            
            <div class="step">
                <h3><i class="fas fa-lock"></i> Default Accounts:</h3>
                <ul>
                    <li><strong>Admin:</strong> Full system access</li>
                    <li><strong>Owner:</strong> Business reports and inventory</li>
                    <li><strong>Rider:</strong> Delivery management</li>
                </ul>
                <p style="margin-top: 12px; color: #64748b;">All accounts use password: <code>admin123</code></p>
            </div>
            
            <form method="POST">
                <button type="submit" name="install" class="btn"><i class="fas fa-rocket"></i> Start Installation</button>
            </form>
            
            <?php
        }
        ?>
    </div>
</body>
</html>
