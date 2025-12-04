<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Address Column - FurnHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
        }
        h2 {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 24px;
        }
        .success {
            background: linear-gradient(135deg, #d1fae5 0%, #ecfdf5 100%);
            color: #065f46;
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid #10b981;
            margin: 20px 0;
            font-weight: 500;
        }
        .error {
            background: linear-gradient(135deg, #fee2e2 0%, #fef2f2 100%);
            color: #991b1b;
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid #ef4444;
            margin: 20px 0;
            font-weight: 500;
        }
        .info {
            background: linear-gradient(135deg, #dbeafe 0%, #eff6ff 100%);
            color: #1e40af;
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid #3b82f6;
            margin: 20px 0;
            font-weight: 500;
        }
        .warning {
            background: linear-gradient(135deg, #fef3c7 0%, #fffbeb 100%);
            color: #92400e;
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid #f59e0b;
            margin: 20px 0;
            font-weight: 500;
        }
        a {
            display: inline-block;
            padding: 14px 28px;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 700;
            margin: 10px 10px 10px 0;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
        }
        a:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.5);
        }
        code {
            background: #1e293b;
            color: #e2e8f0;
            padding: 4px 8px;
            border-radius: 6px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class="fas fa-map-marker-alt"></i> Add Address Column Migration</h2>

<?php
/**
 * Migration Script: Add address column to users table
 * Run this once if you already have an existing database
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<div class='info'><strong>Checking database...</strong></div>";
    
    // Check if column already exists
    $query = "SHOW COLUMNS FROM users LIKE 'address'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "<div class='success'><strong><i class='fas fa-check'></i> Address column already exists in users table.</strong><br>No changes needed.</div>";
    } else {
        // Add address column after phone
        $query = "ALTER TABLE users ADD COLUMN address TEXT AFTER phone";
        $db->exec($query);
        echo "<div class='success'><strong><i class='fas fa-check'></i> Address column added successfully!</strong><br>The 'address' field has been added to the users table.</div>";
    }
    
    echo "<div class='info'><strong>Migration completed successfully!</strong><br>You can now use the system normally.</div>";
    echo "<p><a href='login.php'>Go to Login Page</a> <a href='register.php'>Go to Register</a></p>";
    echo "<div class='warning'><strong><i class='fas fa-exclamation-triangle'></i> SECURITY:</strong> Please delete this file (<code>add_address_column.php</code>) after running it!</div>";
    
} catch (PDOException $e) {
    echo "<div class='error'><strong><i class='fas fa-times-circle'></i> Error:</strong> " . $e->getMessage() . "</div>";
    echo "<p>Make sure your database exists and is properly configured.</p>";
    echo "<p><a href='setup.php'>Run Setup Wizard</a></p>";
}
?>
    </div>
</body>
</html>
