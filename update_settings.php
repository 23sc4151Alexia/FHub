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
    // Get form data
    $system_name = trim($_POST['system_name'] ?? 'FurnHub');
    $logo_text = trim($_POST['logo_text'] ?? 'FurnHub');
    $primary_color = trim($_POST['primary_color'] ?? '#6366f1');
    $secondary_color = trim($_POST['secondary_color'] ?? '#764ba2');
    $sidebar_color = trim($_POST['sidebar_color'] ?? '#1e293b');
    $header_color = trim($_POST['header_color'] ?? '#ffffff');
    $theme_mode = trim($_POST['theme_mode'] ?? 'light');
    $contact_email = trim($_POST['contact_email'] ?? '');
    $contact_phone = trim($_POST['contact_phone'] ?? '');
    $company_address = trim($_POST['company_address'] ?? '');
    $footer_text = trim($_POST['footer_text'] ?? '');

    // Validate color codes
    if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $primary_color)) {
        throw new Exception('Invalid primary color format');
    }
    if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $secondary_color)) {
        throw new Exception('Invalid secondary color format');
    }

    // Check if settings exist
    $check = "SELECT id FROM system_settings LIMIT 1";
    $stmt = $pdo->query($check);
    $exists = $stmt->fetch();

    if ($exists) {
        // Update existing settings
        $query = "UPDATE system_settings SET 
                  system_name = :system_name,
                  logo_text = :logo_text,
                  primary_color = :primary_color,
                  secondary_color = :secondary_color,
                  sidebar_color = :sidebar_color,
                  header_color = :header_color,
                  theme_mode = :theme_mode,
                  contact_email = :contact_email,
                  contact_phone = :contact_phone,
                  company_address = :company_address,
                  footer_text = :footer_text,
                  updated_at = NOW()
                  WHERE id = :id";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'system_name' => $system_name,
            'logo_text' => $logo_text,
            'primary_color' => $primary_color,
            'secondary_color' => $secondary_color,
            'sidebar_color' => $sidebar_color,
            'header_color' => $header_color,
            'theme_mode' => $theme_mode,
            'contact_email' => $contact_email,
            'contact_phone' => $contact_phone,
            'company_address' => $company_address,
            'footer_text' => $footer_text,
            'id' => $exists['id']
        ]);
    } else {
        // Insert new settings
        $query = "INSERT INTO system_settings 
                  (system_name, logo_text, primary_color, secondary_color, sidebar_color, header_color, theme_mode, contact_email, contact_phone, company_address, footer_text) 
                  VALUES 
                  (:system_name, :logo_text, :primary_color, :secondary_color, :sidebar_color, :header_color, :theme_mode, :contact_email, :contact_phone, :company_address, :footer_text)";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'system_name' => $system_name,
            'logo_text' => $logo_text,
            'primary_color' => $primary_color,
            'secondary_color' => $secondary_color,
            'sidebar_color' => $sidebar_color,
            'header_color' => $header_color,
            'theme_mode' => $theme_mode,
            'contact_email' => $contact_email,
            'contact_phone' => $contact_phone,
            'company_address' => $company_address,
            'footer_text' => $footer_text
        ]);
    }

    // Generate custom CSS file
    $customCSS = ":root {
    --primary-color: {$primary_color};
    --primary-light: {$primary_color}dd;
    --primary-dark: {$primary_color};
    --secondary-color: {$secondary_color};
    --sidebar-bg: {$sidebar_color};
    --header-bg: {$header_color};
}

body.dark-mode {
    --light-bg: #1e293b;
    --dark-text: #f1f5f9;
    --border-color: #334155;
    background: #0f172a;
}

.sidebar {
    background: var(--sidebar-bg) !important;
}

.dashboard-header {
    background: var(--header-bg) !important;
}
";

    // Save custom CSS
    file_put_contents('../assets/css/custom-theme.css', $customCSS);

    echo json_encode([
        'success' => true,
        'message' => 'Settings updated successfully'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
