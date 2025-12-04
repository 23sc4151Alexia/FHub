<?php
// Fix password script - Run this once to fix admin, owner, and rider passwords

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Generate correct password hash for "admin123"
    $password = 'admin123';
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    echo "<h2>Fixing User Passwords...</h2>";
    echo "<p>Password: <strong>admin123</strong></p>";
    
    // Update admin password
    $query = "UPDATE users SET password = :password WHERE username = 'admin'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':password', $hashed_password);
    if ($stmt->execute()) {
        echo "<p style='color: green;'><i class='fas fa-check'></i> Admin password updated successfully</p>";
    }
    
    // Update owner password
    $query = "UPDATE users SET password = :password WHERE username = 'owner'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':password', $hashed_password);
    if ($stmt->execute()) {
        echo "<p style='color: green;'><i class='fas fa-check'></i> Owner password updated successfully</p>";
    }
    
    // Update rider password
    $query = "UPDATE users SET password = :password WHERE username = 'rider1'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':password', $hashed_password);
    if ($stmt->execute()) {
        echo "<p style='color: green;'><i class='fas fa-check'></i> Rider password updated successfully</p>";
    }
    
    echo "<hr>";
    echo "<h3 style='color: green;'><i class='fas fa-check-circle'></i> All passwords have been fixed!</h3>";
    echo "<p>You can now login with:</p>";
    echo "<ul>";
    echo "<li><strong>Admin:</strong> admin / admin123</li>";
    echo "<li><strong>Owner:</strong> owner / admin123</li>";
    echo "<li><strong>Rider:</strong> rider1 / admin123</li>";
    echo "</ul>";
    echo "<br>";
    echo "<a href='login.php' style='display: inline-block; padding: 12px 24px; background-color: #2563eb; color: white; text-decoration: none; border-radius: 6px;'>Go to Login</a>";
    echo "<br><br>";
    echo "<p style='color: #dc2626;'><strong>Important:</strong> Delete this file (fix_passwords.php) after running it for security!</p>";
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
