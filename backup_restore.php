<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Get system settings
require_once '../includes/system_settings_helper.php';
$systemSettings = getSystemSettings();

// Get existing backups
$backupDir = '../backups/';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

$backups = [];
if (is_dir($backupDir)) {
    $files = scandir($backupDir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $backups[] = [
                'name' => $file,
                'size' => filesize($backupDir . $file),
                'date' => filemtime($backupDir . $file)
            ];
        }
    }
    // Sort by date, newest first
    usort($backups, function($a, $b) {
        return $b['date'] - $a['date'];
    });
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup & Restore - <?php echo htmlspecialchars($systemSettings['system_name']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/js/modern-ui.js" defer></script>
    <script src="../assets/js/theme-switcher.js" defer></script>
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>
    
    <div class="dashboard-container">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1><i class="fas fa-database"></i> Backup & Restore</h1>
                <p>Protect your data with automated backups</p>
            </div>

            <!-- Quick Actions -->
            <div class="settings-grid" style="max-width: 1000px;">
                <div class="setting-card scroll-fade">
                    <h3><i class="fas fa-box"></i> Create Backup</h3>
                    <p style="color: #64748b; margin: 16px 0;">Create a full database backup now</p>
                    <button class="btn btn-primary" onclick="createBackup()" id="backupBtn">
                        <i class="fas fa-database"></i> Create Backup Now
                    </button>
                </div>

                <div class="setting-card scroll-fade">
                    <h3><i class="fas fa-upload"></i> Upload Backup</h3>
                    <p style="color: #64748b; margin: 16px 0;">Upload a backup file to restore</p>
                    <form id="uploadForm" enctype="multipart/form-data">
                        <input type="file" name="backup_file" accept=".sql" class="form-control" style="margin-bottom: 12px;">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-upload"></i> Upload & Restore
                        </button>
                    </form>
                </div>

                <div class="setting-card scroll-fade">
                    <h3><i class="fas fa-cog"></i> Auto Backup</h3>
                    <p style="color: #64748b; margin: 16px 0;">Configure automatic backup schedule</p>
                    <select class="form-control" style="margin-bottom: 12px;">
                        <option>Daily at 2:00 AM</option>
                        <option>Weekly on Sunday</option>
                        <option>Monthly on 1st</option>
                        <option>Disabled</option>
                    </select>
                    <button class="btn btn-secondary">
                        Save Schedule
                    </button>
                </div>
            </div>

            <!-- Existing Backups -->
            <div class="content-section scroll-fade" style="margin-top: 32px; max-width: 1000px;">
                <h3 style="margin-bottom: 16px;"><i class="fas fa-clipboard-list"></i> Existing Backups</h3>
                
                <?php if (empty($backups)): ?>
                    <div style="text-align: center; padding: 40px; color: #64748b;">
                        <i class="fas fa-database"></i> No backups found. Create your first backup above!
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Backup Name</th>
                                    <th>Date Created</th>
                                    <th>File Size</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($backups as $backup): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($backup['name']); ?></strong>
                                        </td>
                                        <td>
                                            <?php echo date('M d, Y h:i A', $backup['date']); ?>
                                        </td>
                                        <td>
                                            <?php echo number_format($backup['size'] / 1024, 2); ?> KB
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 8px;">
                                                <button class="btn btn-sm btn-info" onclick="downloadBackup('<?php echo htmlspecialchars($backup['name']); ?>')">
                                                    <i class="fas fa-download"></i> Download
                                                </button>
                                                <button class="btn btn-sm btn-warning" onclick="restoreBackup('<?php echo htmlspecialchars($backup['name']); ?>')">
                                                    <i class="fas fa-sync-alt"></i> Restore
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="deleteBackup('<?php echo htmlspecialchars($backup['name']); ?>')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Backup Info -->
            <div class="content-section scroll-fade" style="margin-top: 24px; max-width: 1000px; background: #fffbeb; border-left: 4px solid #f59e0b;">
                <h3 style="color: #f59e0b; margin-bottom: 12px;"><i class="fas fa-exclamation-triangle"></i> Important Information</h3>
                <ul style="margin: 0; padding-left: 20px; color: #92400e;">
                    <li>Backups include all database tables and data</li>
                    <li>Store backups in a secure location</li>
                    <li>Test restore process regularly</li>
                    <li>Restoring a backup will overwrite current data</li>
                    <li>Always create a backup before major changes</li>
                </ul>
            </div>
        </main>
    </div>

    <script>
        function createBackup() {
            const btn = document.getElementById('backupBtn');
            
            showConfirmDialog(
                'Create a full database backup? This may take a few moments.',
                () => {
                    setLoading(btn, true);
                    
                    fetch('create_backup.php', {
                        method: 'POST'
                    })
                    .then(response => response.json())
                    .then(data => {
                        setLoading(btn, false);
                        if (data.success) {
                            Toast.success('Backup created successfully!');
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            Toast.error(data.message || 'Failed to create backup');
                        }
                    })
                    .catch(error => {
                        setLoading(btn, false);
                        Toast.error('An error occurred');
                        console.error('Error:', error);
                    });
                }
            );
        }

        function downloadBackup(filename) {
            Toast.info('Downloading backup...');
            window.location.href = 'download_backup.php?file=' + encodeURIComponent(filename);
        }

        function restoreBackup(filename) {
            showConfirmDialog(
                'WARNING: Restoring this backup will replace ALL current data! Are you absolutely sure?',
                () => {
                    Toast.info('Restoring backup... Please wait.');
                    
                    fetch('restore_backup.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ filename: filename })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Toast.success('Backup restored successfully!');
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            Toast.error(data.message || 'Failed to restore backup');
                        }
                    })
                    .catch(error => {
                        Toast.error('An error occurred');
                        console.error('Error:', error);
                    });
                }
            );
        }

        function deleteBackup(filename) {
            showConfirmDialog(
                'Delete this backup file? This action cannot be undone.',
                () => {
                    fetch('delete_backup.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ filename: filename })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Toast.success('Backup deleted');
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            Toast.error(data.message || 'Failed to delete backup');
                        }
                    });
                }
            );
        }

        // Upload form
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            
            setLoading(submitBtn, true);
            
            fetch('upload_backup.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                setLoading(submitBtn, false);
                if (data.success) {
                    Toast.success('Backup uploaded successfully!');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    Toast.error(data.message || 'Failed to upload backup');
                }
            })
            .catch(error => {
                setLoading(submitBtn, false);
                Toast.error('An error occurred');
                console.error('Error:', error);
            });
        });
    </script>
</body>
</html>
