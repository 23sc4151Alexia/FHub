<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FurnHub Installation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .install-container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            max-width: 600px;
            width: 100%;
        }
        
        h1 {
            color: #2563eb;
            margin-bottom: 10px;
            font-size: 32px;
        }
        
        .subtitle {
            color: #64748b;
            margin-bottom: 30px;
        }
        
        .step {
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .step h3 {
            color: #1e293b;
            margin-bottom: 10px;
        }
        
        .btn {
            display: inline-block;
            padding: 14px 28px;
            background-color: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            width: 100%;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #1d4ed8;
        }
        
        .success {
            background-color: #dcfce7;
            color: #166534;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #86efac;
            margin-bottom: 20px;
        }
        
        .error {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #fca5a5;
            margin-bottom: 20px;
        }
        
        code {
            background: #1e293b;
            color: #e2e8f0;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .info-box {
            background: #dbeafe;
            color: #1e40af;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #2563eb;
        }
        
        ol {
            margin-left: 20px;
            line-height: 1.8;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <h1><i class="fas fa-couch"></i> FurnHub Installation</h1>
        <p class="subtitle">Welcome! Let's set up your furniture ordering system.</p>
        
        <?php
        if (isset($_POST['install'])) {
            try {
                // Connect without database first
                $conn = new PDO("mysql:host=localhost", "root", "");
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Read SQL file
                $sql = file_get_contents('database/schema.sql');
                
                // Remove the INSERT users statements (we'll add them manually with proper hashing)
                $sql = preg_replace('/-- Insert Default Admin User.*?(?=-- Insert Sample Categories)/s', '', $sql);
                
                // Split by semicolon and execute each statement
                $statements = array_filter(array_map('trim', explode(';', $sql)));
                
                foreach ($statements as $statement) {
                    if (!empty($statement)) {
                        $conn->exec($statement);
                    }
                }
                
                // Now connect to the new database
                $conn = new PDO("mysql:host=localhost;dbname=furnhub_db", "root", "");
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Generate proper password hashes
                $password = 'admin123';
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert default users with properly hashed passwords
                $users = [
                    ['admin', 'admin@furnhub.com', 'System Administrator', '09123456789', 'admin'],
                    ['owner', 'owner@furnhub.com', 'Shop Owner', '09123456788', 'owner'],
                    ['rider1', 'rider1@furnhub.com', 'John Rider', '09123456787', 'rider']
                ];
                
                $insert_user = $conn->prepare("INSERT INTO users (username, email, password, full_name, phone, role) VALUES (?, ?, ?, ?, ?, ?)");
                
                foreach ($users as $user) {
                    $insert_user->execute([
                        $user[0],
                        $user[1],
                        $hashed_password,
                        $user[2],
                        $user[3],
                        $user[4]
                    ]);
                }
                
                echo '<div class="success">';
                echo '<strong><i class="fas fa-check-circle"></i> Installation Successful!</strong><br>';
                echo 'Database has been created and populated with sample data.<br>';
                echo 'Default users have been created with working passwords.<br>';
                echo 'You can now use the system.';
                echo '</div>';
                
                echo '<div class="info-box">';
                echo '<strong><i class="fas fa-clipboard-list"></i> Important:</strong><br>';
                echo 'For security, please delete this <code>install.php</code> file after installation.';
                echo '</div>';
                
                echo '<a href="login.php" class="btn">Go to Login Page</a>';
                
            } catch(PDOException $e) {
                echo '<div class="error">';
                echo '<strong><i class="fas fa-times-circle"></i> Installation Failed</strong><br>';
                echo 'Error: ' . $e->getMessage();
                echo '</div>';
                
                echo '<form method="POST">';
                echo '<button type="submit" name="install" class="btn">Try Again</button>';
                echo '</form>';
            }
        } else {
            ?>
            
            <div class="info-box">
                <strong><i class="fas fa-info-circle"></i> Before Installation:</strong><br>
                Make sure XAMPP MySQL service is running!
            </div>
            
            <div class="step">
                <h3>What will be installed:</h3>
                <ul style="margin-left: 20px; line-height: 1.8;">
                    <li>Database: <code>furnhub_db</code></li>
                    <li>All required tables</li>
                    <li>Sample products and categories</li>
                    <li>Default user accounts</li>
                </ul>
            </div>
            
            <div class="step">
                <h3>Default Login Credentials:</h3>
                <ul style="margin-left: 20px; line-height: 1.8;">
                    <li><strong>Admin:</strong> <code>admin</code> / <code>admin123</code></li>
                    <li><strong>Owner:</strong> <code>owner</code> / <code>admin123</code></li>
                    <li><strong>Rider:</strong> <code>rider1</code> / <code>admin123</code></li>
                </ul>
            </div>
            
            <form method="POST">
                <button type="submit" name="install" class="btn">Install Now</button>
            </form>
            
            <?php
        }
        ?>
    </div>
</body>
</html>
