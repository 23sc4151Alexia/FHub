<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

try {
    // Create backups directory if it doesn't exist
    $backupDir = '../backups/';
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }

    // Generate filename with timestamp
    $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    $filepath = $backupDir . $filename;

    // Get database credentials
    $host = "localhost";
    $dbname = "furnhub_db";
    $user = "root";
    $pass = "";

    // Build mysqldump command
    $command = "mysqldump --host={$host} --user={$user} --password={$pass} {$dbname} > {$filepath} 2>&1";

    // Execute backup
    exec($command, $output, $return_var);

    if ($return_var === 0 && file_exists($filepath) && filesize($filepath) > 0) {
        // Log activity
        require_once '../includes/system_settings_helper.php';
        logActivity($pdo, $_SESSION['user_id'], $_SESSION['role'], 'backup_create', "Created database backup: {$filename}");

        echo json_encode([
            'success' => true,
            'message' => 'Backup created successfully',
            'filename' => $filename,
            'size' => filesize($filepath)
        ]);
    } else {
        // Fallback: PHP-based backup
        $backup = generatePHPBackup($pdo);
        file_put_contents($filepath, $backup);

        if (file_exists($filepath) && filesize($filepath) > 0) {
            require_once '../includes/system_settings_helper.php';
            logActivity($pdo, $_SESSION['user_id'], $_SESSION['role'], 'backup_create', "Created database backup: {$filename}");

            echo json_encode([
                'success' => true,
                'message' => 'Backup created successfully (PHP method)',
                'filename' => $filename,
                'size' => filesize($filepath)
            ]);
        } else {
            throw new Exception('Failed to create backup file');
        }
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function generatePHPBackup($pdo) {
    $backup = "-- FurnHub Database Backup\n";
    $backup .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
    $backup .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    // Get all tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        // Get CREATE TABLE statement
        $createTable = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
        $backup .= "DROP TABLE IF EXISTS `{$table}`;\n";
        $backup .= $createTable['Create Table'] . ";\n\n";

        // Get table data
        $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($rows)) {
            foreach ($rows as $row) {
                $values = array_map(function($value) use ($pdo) {
                    return $value === null ? 'NULL' : $pdo->quote($value);
                }, array_values($row));
                
                $backup .= "INSERT INTO `{$table}` VALUES (" . implode(', ', $values) . ");\n";
            }
            $backup .= "\n";
        }
    }

    $backup .= "SET FOREIGN_KEY_CHECKS=1;\n";
    
    return $backup;
}
?>
