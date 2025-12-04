# 🛠️ Admin Management System Setup Guide

## 📋 New Admin Features

Your FurnHub admin panel now includes powerful system management features:

### 1. **⚙️ System Settings**
Complete control over your system's appearance and branding.

#### Features:
- **General Settings**
  - System Name customization
  - Logo Text/Emoji configuration
  - Theme Mode (Light/Dark)

- **Colors & Theme**
  - Primary Color picker
  - Secondary Color picker
  - Sidebar Color customization
  - Header Color customization
  - 6 Predefined color presets (Purple, Blue, Green, Orange, Red, Royal Purple)
  - Live preview of changes

- **Branding**
  - Contact Email
  - Contact Phone
  - Company Address
  - Footer Text customization

#### Access:
Navigate to **Admin Dashboard → Settings** (⚙️ icon in sidebar)

---

### 2. **📋 System Activity Logs**
Monitor all user activities and system events.

#### Features:
- Real-time activity tracking
- User action logging
- IP address tracking
- Filter by:
  - Action type (login, logout, create, update, delete)
  - User role (admin, owner, rider, customer)
  - Date range
- Export logs functionality
- Clear old logs (older than 90 days)
- Pagination for large datasets

#### What's Logged:
- User logins/logouts
- Product creation/updates
- Order modifications
- User management actions
- System setting changes
- All CRUD operations

#### Access:
Navigate to **Admin Dashboard → System Logs** (📋 icon in sidebar)

---

### 3. **💾 Backup & Restore**
Protect your data with automated backup system.

#### Features:
- **One-Click Backup**
  - Create full database backup instantly
  - Automatic timestamp naming
  - Fallback PHP-based backup method

- **Backup Management**
  - View all existing backups
  - Download backup files
  - Restore from backup
  - Delete old backups

- **Auto Backup Scheduling**
  - Daily at specific time
  - Weekly schedule
  - Monthly schedule

#### Safety Features:
- Confirmation dialogs for destructive actions
- Backup before restore recommended
- File size and date information
- Secure backup storage

#### Access:
Navigate to **Admin Dashboard → Backup & Restore** (💾 icon in sidebar)

---

## 🚀 Setup Instructions

### Step 1: Create Database Tables

Run these SQL files in your phpMyAdmin:

1. **System Settings Table**
```sql
-- File: database/system_settings_table.sql
CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `system_name` varchar(255) NOT NULL DEFAULT 'FurnHub',
  `logo_text` varchar(255) NOT NULL DEFAULT '🪑 FurnHub',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

2. **Activity Logs Table**
```sql
-- File: database/activity_logs_table.sql
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `role` enum('admin','owner','rider','customer','system') DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `created_at` (`created_at`),
  KEY `action` (`action`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Step 2: Import SQL Files

**Option A: Using phpMyAdmin**
1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Select `furnhub_db` database
3. Click **Import** tab
4. Choose file: `database/system_settings_table.sql`
5. Click **Go**
6. Repeat for `database/activity_logs_table.sql`

**Option B: Using Command Line**
```bash
mysql -u root furnhub_db < database/system_settings_table.sql
mysql -u root furnhub_db < database/activity_logs_table.sql
```

### Step 3: Create Backup Directory

Create a folder for storing backups:
```
C:\xampp\htdocs\furnhub\backups\
```

Or the system will create it automatically on first backup.

### Step 4: Test the Features

1. **Login as Admin**
   - Navigate to http://localhost/furnhub/admin/

2. **Test Settings**
   - Go to Settings page
   - Change system name
   - Pick a color preset
   - Save and see changes reflected

3. **Check System Logs**
   - Go to System Logs page
   - View your login activity
   - Filter by action/role

4. **Create a Backup**
   - Go to Backup & Restore
   - Click "Create Backup Now"
   - Verify backup appears in list

---

## 🎨 Customization Examples

### Change System Name
1. Go to **Settings → General Tab**
2. Change "System Name" from "FurnHub" to "Your Company Name"
3. Click **Save Settings**
4. Refresh page to see changes

### Apply Color Theme
1. Go to **Settings → Colors & Theme Tab**
2. Click on a color preset (e.g., "Ocean Blue")
3. See live preview in Preview tab
4. Click **Save Settings**

### Switch to Dark Mode
1. Go to **Settings → General Tab**
2. Click on 🌙 **Dark** theme option
3. Click **Save Settings**
4. System switches to dark mode

---

## 📊 Usage Tips

### Settings Page
- **Live Preview**: Changes are shown in real-time in Preview tab
- **Color Presets**: Quick way to apply professional color schemes
- **Reset**: Use "Reset Changes" to revert before saving

### System Logs
- **Filter by Date**: Use date picker to find specific day's activities
- **Export**: Download logs for external analysis
- **Clear Old**: Remove logs older than 90 days to save space

### Backup & Restore
- **Regular Backups**: Create backup before major changes
- **Test Restores**: Periodically test backup restore on dev environment
- **Download**: Keep backups in external storage for safety

---

## 🔒 Security Notes

1. **Settings Access**: Only admins can access these pages
2. **Backup Storage**: Backups stored in `/backups/` directory
3. **Activity Logging**: All admin actions are logged
4. **Confirmation Dialogs**: Destructive actions require confirmation

---

## 🐛 Troubleshooting

### Settings Not Saving
- Check database connection
- Verify `system_settings` table exists
- Check browser console for errors

### Backups Failing
- Ensure `/backups/` directory exists and is writable
- Check if mysqldump is in system PATH
- Fallback PHP backup will activate automatically

### Logs Not Showing
- Verify `activity_logs` table exists
- Check if foreign key constraint is properly set
- Ensure logging function is called in code

### Theme Not Applying
- Clear browser cache
- Refresh page after saving settings
- Check if custom CSS file is generated

---

## 📁 File Structure

```
furnhub/
├── admin/
│   ├── settings.php              # Main settings page
│   ├── update_settings.php       # Settings update handler
│   ├── system_logs.php           # Activity logs viewer
│   ├── backup_restore.php        # Backup management
│   └── create_backup.php         # Backup creation script
├── includes/
│   └── system_settings_helper.php # Settings helper functions
├── database/
│   ├── system_settings_table.sql
│   └── activity_logs_table.sql
├── backups/                       # Backup storage (auto-created)
└── assets/
    └── css/
        └── custom-theme.css      # Generated theme CSS
```

---

## 🎯 Next Steps

1. ✅ Import database tables
2. ✅ Login as admin
3. ✅ Customize system settings
4. ✅ Create first backup
5. ✅ Monitor activity logs
6. ✅ Enjoy your enhanced admin panel!

---

## 💡 Advanced Features

### Automatic Logging
The system automatically logs:
- Login attempts (successful and failed)
- Product CRUD operations
- Order status changes
- User management actions
- Setting modifications

### Dynamic Theming
- Changes apply instantly across all pages
- Headers, sidebars, buttons adapt to your colors
- Support for both light and dark modes
- Professional color presets included

### Data Protection
- One-click backup creation
- Safe restore with confirmation
- Backup file management
- Activity audit trail

---

**Your admin panel is now fully equipped with professional system management tools!** 🎉
