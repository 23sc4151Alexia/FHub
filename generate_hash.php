<?php
// Generate correct password hash for admin123
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Password: admin123<br>";
echo "Hash: " . $hash . "<br><br>";

echo "Copy this hash and update your database users table";
?>
