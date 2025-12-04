<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Get current settings with error handling
try {
    // Check if table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'system_settings'");
    
    if ($tableCheck->rowCount() == 0) {
        // Table doesn't exist, create it
        $createTable = "CREATE TABLE IF NOT EXISTS `system_settings` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `system_name` varchar(255) NOT NULL DEFAULT 'FurnHub',
          `logo_text` varchar(255) NOT NULL DEFAULT 'FurnHub',
          `primary_color` varchar(7) NOT NULL DEFAULT '#6366f1',
          `secondary_color` varchar(7) NOT NULL DEFAULT '#764ba2',
          `sidebar_color` varchar(7) NOT NULL DEFAULT '#1e293b',
          `header_color` varchar(7) NOT NULL DEFAULT '#ffffff',
          `theme_mode` enum('light','dark') NOT NULL DEFAULT 'light',
          `contact_email` varchar(255) DEFAULT NULL,
          `contact_phone` varchar(50) DEFAULT NULL,
          `company_address` text DEFAULT NULL,
          `footer_text` varchar(255) DEFAULT '© 2025 FurnHub. All rights reserved.',
          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $pdo->exec($createTable);
        
        // Insert default settings
        $insert = "INSERT INTO system_settings (system_name, primary_color, secondary_color, theme_mode, logo_text, sidebar_color, header_color) 
                   VALUES ('FurnHub', '#6366f1', '#764ba2', 'light', 'FurnHub', '#1e293b', '#ffffff')";
        $pdo->exec($insert);
    }
    
    // Now get the settings
    $query = "SELECT * FROM system_settings LIMIT 1";
    $stmt = $pdo->query($query);
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If still no settings exist, create default
    if (!$settings) {
        $insert = "INSERT INTO system_settings (system_name, primary_color, secondary_color, theme_mode, logo_text, sidebar_color, header_color) 
                   VALUES ('FurnHub', '#6366f1', '#764ba2', 'light', 'FurnHub', '#1e293b', '#ffffff')";
        $pdo->exec($insert);
        $settings = [
            'system_name' => 'FurnHub',
            'primary_color' => '#6366f1',
            'secondary_color' => '#764ba2',
            'theme_mode' => 'light',
            'logo_text' => 'FurnHub',
            'sidebar_color' => '#1e293b',
            'header_color' => '#ffffff',
            'contact_email' => '',
            'contact_phone' => '',
            'company_address' => '',
            'footer_text' => '© 2025 FurnHub. All rights reserved.'
        ];
    }
} catch (Exception $e) {
    // Error occurred, use default settings
    $settings = [
        'system_name' => 'FurnHub',
        'primary_color' => '#6366f1',
        'secondary_color' => '#764ba2',
        'theme_mode' => 'light',
        'logo_text' => 'FurnHub',
        'sidebar_color' => '#1e293b',
        'header_color' => '#ffffff',
        'contact_email' => '',
        'contact_phone' => '',
        'company_address' => '',
        'footer_text' => '© 2025 FurnHub. All rights reserved.'
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - <?php echo htmlspecialchars($settings['system_name']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/js/modern-ui.js" defer></script>
    <style>
        .settings-container {
            max-width: 1000px;
        }
        
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
            margin-top: 24px;
        }
        
        .setting-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
        }
        
        .setting-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }
        
        .setting-card h3 {
            margin-bottom: 16px;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .color-input-group {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        .color-input-group input[type="color"] {
            width: 60px;
            height: 60px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .color-input-group input[type="color"]:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        .color-input-group input[type="text"] {
            flex: 1;
        }
        
        .theme-selector {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 12px;
        }
        
        .theme-option {
            padding: 20px;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
        }
        
        .theme-option:hover {
            border-color: var(--primary-color);
            transform: scale(1.05);
        }
        
        .theme-option.active {
            border-color: var(--primary-color);
            background: var(--primary-light);
            color: white;
        }
        
        .theme-option input[type="radio"] {
            display: none;
        }
        
        .preview-section {
            margin-top: 32px;
            padding: 24px;
            background: var(--light-bg);
            border-radius: 16px;
        }
        
        .preview-header {
            padding: 20px;
            border-radius: 12px;
            color: white;
            margin-bottom: 16px;
            transition: all 0.3s;
        }
        
        .preview-button {
            padding: 12px 24px;
            border-radius: 8px;
            border: none;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .success-message {
            background: var(--success-color);
            color: white;
            padding: 16px 24px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: none;
            animation: slideInRight 0.3s ease-out;
        }
        
        .tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            border-bottom: 2px solid var(--border-color);
        }
        
        .tab {
            padding: 12px 24px;
            cursor: pointer;
            border: none;
            background: none;
            font-weight: 600;
            color: var(--secondary-color);
            transition: all 0.3s;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
        }
        
        .tab:hover {
            color: var(--primary-color);
        }
        
        .tab.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }
    </style>
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>
    
    <div class="dashboard-container">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="main-content">
            <div class="settings-container">
                <div class="page-header">
                    <h1><i class="fas fa-cog"></i> System Settings</h1>
                    <p>Customize your system appearance and branding</p>
                </div>

                <div id="successMessage" class="success-message">
                    <i class="fas fa-check"></i> Settings saved successfully!
                </div>

                <!-- Tabs -->
                <div class="tabs">
                    <button class="tab active" onclick="switchTab('general')">General</button>
                    <button class="tab" onclick="switchTab('colors')">Colors & Theme</button>
                    <button class="tab" onclick="switchTab('branding')">Branding</button>
                    <button class="tab" onclick="switchTab('preview')">Preview</button>
                </div>

                <form id="settingsForm" method="POST" action="update_settings.php">
                    
                    <!-- General Tab -->
                    <div id="general-tab" class="tab-content active">
                        <div class="settings-grid">
                            <div class="setting-card scroll-fade">
                                <h3><i class="fas fa-building"></i> System Name</h3>
                                <p style="color: #64748b; margin-bottom: 16px;">The name displayed throughout the system</p>
                                <input type="text" 
                                       name="system_name" 
                                       value="<?php echo htmlspecialchars($settings['system_name']); ?>" 
                                       placeholder="Enter system name"
                                       required
                                       class="form-control">
                            </div>

                            <div class="setting-card scroll-fade">
                                <h3><i class="fas fa-palette"></i> Logo Text</h3>
                                <p style="color: #64748b; margin-bottom: 16px;">Text or emoji shown in the header</p>
                                <input type="text" 
                                       name="logo_text" 
                                       value="<?php echo htmlspecialchars($settings['logo_text']); ?>" 
                                       placeholder="FurnHub"
                                       required
                                       class="form-control">
                            </div>

                            <div class="setting-card scroll-fade">
                                <h3><i class="fas fa-adjust"></i> Theme Mode</h3>
                                <p style="color: #64748b; margin-bottom: 16px;">Choose your preferred theme</p>
                                <div class="theme-selector">
                                    <label class="theme-option <?php echo $settings['theme_mode'] === 'light' ? 'active' : ''; ?>">
                                        <input type="radio" name="theme_mode" value="light" <?php echo $settings['theme_mode'] === 'light' ? 'checked' : ''; ?>>
                                        <div style="font-size: 32px;"><i class="fas fa-sun"></i></div>
                                        <div style="margin-top: 8px; font-weight: 600;">Light</div>
                                    </label>
                                    <label class="theme-option <?php echo $settings['theme_mode'] === 'dark' ? 'active' : ''; ?>">
                                        <input type="radio" name="theme_mode" value="dark" <?php echo $settings['theme_mode'] === 'dark' ? 'checked' : ''; ?>>
                                        <div style="font-size: 32px;"><i class="fas fa-moon"></i></div>
                                        <div style="margin-top: 8px; font-weight: 600;">Dark</div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Colors Tab -->
                    <div id="colors-tab" class="tab-content">
                        <div class="settings-grid">
                            <div class="setting-card scroll-fade">
                                <h3><i class="fas fa-palette"></i> Primary Color</h3>
                                <p style="color: #64748b; margin-bottom: 16px;">Main brand color used for buttons and accents</p>
                                <div class="color-input-group">
                                    <input type="color" 
                                           id="primary_color" 
                                           name="primary_color" 
                                           value="<?php echo htmlspecialchars($settings['primary_color']); ?>"
                                           onchange="updateColorText('primary_color')">
                                    <input type="text" 
                                           id="primary_color_text" 
                                           value="<?php echo htmlspecialchars($settings['primary_color']); ?>"
                                           onchange="updateColorPicker('primary_color')"
                                           placeholder="#6366f1"
                                           class="form-control">
                                </div>
                            </div>

                            <div class="setting-card scroll-fade">
                                <h3><i class="fas fa-palette"></i> Secondary Color</h3>
                                <p style="color: #64748b; margin-bottom: 16px;">Secondary color for gradients and highlights</p>
                                <div class="color-input-group">
                                    <input type="color" 
                                           id="secondary_color" 
                                           name="secondary_color" 
                                           value="<?php echo htmlspecialchars($settings['secondary_color']); ?>"
                                           onchange="updateColorText('secondary_color')">
                                    <input type="text" 
                                           id="secondary_color_text" 
                                           value="<?php echo htmlspecialchars($settings['secondary_color']); ?>"
                                           onchange="updateColorPicker('secondary_color')"
                                           placeholder="#764ba2"
                                           class="form-control">
                                </div>
                            </div>

                            <div class="setting-card scroll-fade">
                                <h3><i class="fas fa-palette"></i> Sidebar Color</h3>
                                <p style="color: #64748b; margin-bottom: 16px;">Background color of the sidebar</p>
                                <div class="color-input-group">
                                    <input type="color" 
                                           id="sidebar_color" 
                                           name="sidebar_color" 
                                           value="<?php echo htmlspecialchars($settings['sidebar_color']); ?>"
                                           onchange="updateColorText('sidebar_color')">
                                    <input type="text" 
                                           id="sidebar_color_text" 
                                           value="<?php echo htmlspecialchars($settings['sidebar_color']); ?>"
                                           onchange="updateColorPicker('sidebar_color')"
                                           placeholder="#1e293b"
                                           class="form-control">
                                </div>
                            </div>

                            <div class="setting-card scroll-fade">
                                <h3><i class="fas fa-palette"></i> Header Color</h3>
                                <p style="color: #64748b; margin-bottom: 16px;">Background color of the header</p>
                                <div class="color-input-group">
                                    <input type="color" 
                                           id="header_color" 
                                           name="header_color" 
                                           value="<?php echo htmlspecialchars($settings['header_color']); ?>"
                                           onchange="updateColorText('header_color')">
                                    <input type="text" 
                                           id="header_color_text" 
                                           value="<?php echo htmlspecialchars($settings['header_color']); ?>"
                                           onchange="updateColorPicker('header_color')"
                                           placeholder="#ffffff"
                                           class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="setting-card scroll-fade" style="margin-top: 24px;">
                            <h3><i class="fas fa-palette"></i> Quick Color Presets</h3>
                            <p style="color: #64748b; margin-bottom: 16px;">Choose from predefined color schemes</p>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px;">
                                <button type="button" class="btn" style="background: linear-gradient(135deg, #6366f1, #764ba2);" onclick="applyPreset('#6366f1', '#764ba2')">Default Purple</button>
                                <button type="button" class="btn" style="background: linear-gradient(135deg, #3b82f6, #1e40af);" onclick="applyPreset('#3b82f6', '#1e40af')">Ocean Blue</button>
                                <button type="button" class="btn" style="background: linear-gradient(135deg, #10b981, #059669);" onclick="applyPreset('#10b981', '#059669')">Fresh Green</button>
                                <button type="button" class="btn" style="background: linear-gradient(135deg, #f59e0b, #d97706);" onclick="applyPreset('#f59e0b', '#d97706')">Sunset Orange</button>
                                <button type="button" class="btn" style="background: linear-gradient(135deg, #ef4444, #dc2626);" onclick="applyPreset('#ef4444', '#dc2626')">Bold Red</button>
                                <button type="button" class="btn" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);" onclick="applyPreset('#8b5cf6', '#7c3aed')">Royal Purple</button>
                            </div>
                        </div>
                    </div>

                    <!-- Branding Tab -->
                    <div id="branding-tab" class="tab-content">
                        <div class="settings-grid">
                            <div class="setting-card scroll-fade">
                                <h3><i class="fas fa-envelope"></i> Contact Email</h3>
                                <p style="color: #64748b; margin-bottom: 16px;">Support email displayed to users</p>
                                <input type="email" 
                                       name="contact_email" 
                                       value="<?php echo htmlspecialchars($settings['contact_email'] ?? 'support@furnhub.com'); ?>" 
                                       placeholder="support@furnhub.com"
                                       class="form-control">
                            </div>

                            <div class="setting-card scroll-fade">
                                <h3><i class="fas fa-phone"></i> Contact Phone</h3>
                                <p style="color: #64748b; margin-bottom: 16px;">Support phone number</p>
                                <input type="tel" 
                                       name="contact_phone" 
                                       value="<?php echo htmlspecialchars($settings['contact_phone'] ?? ''); ?>" 
                                       placeholder="+1 234 567 890"
                                       class="form-control">
                            </div>

                            <div class="setting-card scroll-fade">
                                <h3><i class="fas fa-building"></i> Company Address</h3>
                                <p style="color: #64748b; margin-bottom: 16px;">Business address</p>
                                <textarea name="company_address" 
                                          class="form-control" 
                                          rows="3"
                                          placeholder="123 Furniture Street, City, Country"><?php echo htmlspecialchars($settings['company_address'] ?? ''); ?></textarea>
                            </div>

                            <div class="setting-card scroll-fade">
                                <h3><i class="fas fa-file-alt"></i> Footer Text</h3>
                                <p style="color: #64748b; margin-bottom: 16px;">Copyright text in footer</p>
                                <input type="text" 
                                       name="footer_text" 
                                       value="<?php echo htmlspecialchars($settings['footer_text'] ?? '© 2025 FurnHub. All rights reserved.'); ?>" 
                                       placeholder="© 2025 FurnHub. All rights reserved."
                                       class="form-control">
                            </div>
                        </div>
                    </div>

                    <!-- Preview Tab -->
                    <div id="preview-tab" class="tab-content">
                        <div class="preview-section scroll-fade">
                            <h3><i class="fas fa-eye"></i> Live Preview</h3>
                            <p style="color: #64748b; margin-bottom: 24px;">See how your changes will look</p>
                            
                            <div id="preview-header" class="preview-header" style="background: <?php echo htmlspecialchars($settings['primary_color']); ?>;">
                                <span id="preview-logo" style="font-size: 24px; font-weight: 700;"><?php echo htmlspecialchars($settings['logo_text']); ?></span>
                            </div>

                            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                                <button type="button" class="preview-button" id="preview-btn" style="background: <?php echo htmlspecialchars($settings['primary_color']); ?>;">
                                    Primary Button
                                </button>
                                <button type="button" class="preview-button" id="preview-btn-secondary" style="background: <?php echo htmlspecialchars($settings['secondary_color']); ?>;">
                                    Secondary Button
                                </button>
                                <div style="padding: 12px 24px; background: <?php echo htmlspecialchars($settings['sidebar_color']); ?>; color: white; border-radius: 8px; font-weight: 600;">
                                    Sidebar Color
                                </div>
                            </div>

                            <div style="margin-top: 24px; padding: 20px; background: white; border-radius: 12px; border-left: 4px solid <?php echo htmlspecialchars($settings['primary_color']); ?>;">
                                <h4 style="color: <?php echo htmlspecialchars($settings['primary_color']); ?>; margin: 0 0 8px 0;">
                                    <span id="preview-system-name"><?php echo htmlspecialchars($settings['system_name']); ?></span>
                                </h4>
                                <p style="margin: 0; color: #64748b;">Your customized system branding</p>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 32px;">
                        <button type="button" class="btn btn-secondary" onclick="resetForm()">
                            Reset Changes
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        // Tab switching
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });

            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');
        }

        // Color picker sync
        function updateColorText(colorName) {
            const picker = document.getElementById(colorName);
            const text = document.getElementById(colorName + '_text');
            text.value = picker.value;
            updatePreview();
        }

        function updateColorPicker(colorName) {
            const text = document.getElementById(colorName + '_text');
            const picker = document.getElementById(colorName);
            picker.value = text.value;
            updatePreview();
        }

        // Apply color preset
        function applyPreset(primary, secondary) {
            document.getElementById('primary_color').value = primary;
            document.getElementById('primary_color_text').value = primary;
            document.getElementById('secondary_color').value = secondary;
            document.getElementById('secondary_color_text').value = secondary;
            updatePreview();
            Toast.success('Color preset applied!');
        }

        // Update preview
        function updatePreview() {
            const primary = document.getElementById('primary_color').value;
            const secondary = document.getElementById('secondary_color').value;
            const sidebar = document.getElementById('sidebar_color').value;
            const systemName = document.querySelector('[name="system_name"]').value;
            const logoText = document.querySelector('[name="logo_text"]').value;

            document.getElementById('preview-header').style.background = primary;
            document.getElementById('preview-btn').style.background = primary;
            document.getElementById('preview-btn-secondary').style.background = secondary;
            document.getElementById('preview-system-name').textContent = systemName;
            document.getElementById('preview-logo').textContent = logoText;
            
            const sidebarPreview = document.querySelector('.preview-section .preview-button').nextElementSibling.nextElementSibling;
            if (sidebarPreview) {
                sidebarPreview.style.background = sidebar;
            }
        }

        // Listen to all input changes for live preview
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('settingsForm');
            form.addEventListener('input', updatePreview);
            
            // Theme selector
            document.querySelectorAll('.theme-option').forEach(option => {
                option.addEventListener('click', function() {
                    document.querySelectorAll('.theme-option').forEach(opt => opt.classList.remove('active'));
                    this.classList.add('active');
                    this.querySelector('input[type="radio"]').checked = true;
                });
            });
        });

        // Form submission
        document.getElementById('settingsForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            setLoading(submitBtn, true);

            const formData = new FormData(this);

            fetch('update_settings.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                setLoading(submitBtn, false);
                if (data.success) {
                    Toast.success('Settings saved successfully! Refreshing...');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    Toast.error(data.message || 'Failed to save settings');
                }
            })
            .catch(error => {
                setLoading(submitBtn, false);
                Toast.error('An error occurred. Please try again.');
                console.error('Error:', error);
            });
        });

        // Reset form
        function resetForm() {
            showConfirmDialog(
                'Are you sure you want to reset all changes? This will reload the page.',
                () => {
                    location.reload();
                }
            );
        }
    </script>
</body>
</html>
