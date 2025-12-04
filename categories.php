<?php
require_once '../config/config.php';
checkRole(['admin']);

$database = new Database();
$db = $database->getConnection();

// Handle add category
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $category_name = sanitize($_POST['category_name']);
    $description = sanitize($_POST['description']);
    
    if (!empty($category_name)) {
        $query = "INSERT INTO categories (category_name, description) VALUES (:name, :desc)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':name', $category_name);
        $stmt->bindParam(':desc', $description);
        $stmt->execute();
        header('Location: categories.php?success=Category added successfully');
        exit();
    }
}

// Handle edit category
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit') {
    $category_id = (int)$_POST['category_id'];
    $category_name = sanitize($_POST['category_name']);
    $description = sanitize($_POST['description']);
    
    if (!empty($category_name)) {
        $query = "UPDATE categories SET category_name = :name, description = :desc WHERE category_id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':name', $category_name);
        $stmt->bindParam(':desc', $description);
        $stmt->bindParam(':id', $category_id);
        $stmt->execute();
        header('Location: categories.php?success=Category updated successfully');
        exit();
    }
}

// Handle delete category
if (isset($_GET['delete'])) {
    $category_id = (int)$_GET['delete'];
    $isAjax = isset($_GET['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
    
    // Check if category has products
    $check_query = "SELECT COUNT(*) as count FROM products WHERE category_id = :id";
    $stmt = $db->prepare($check_query);
    $stmt->bindParam(':id', $category_id);
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Cannot delete category with products']);
            exit();
        }
        header('Location: categories.php?error=Cannot delete category with products');
    } else {
        $query = "DELETE FROM categories WHERE category_id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $category_id);
        $stmt->execute();
        
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Category deleted successfully']);
            exit();
        }
        header('Location: categories.php?success=Category deleted successfully');
    }
    exit();
}

// Get all categories with product count
$query = "SELECT c.*, COUNT(p.product_id) as product_count 
            FROM categories c 
            LEFT JOIN products p ON c.category_id = p.category_id 
            GROUP BY c.category_id 
            ORDER BY c.category_name";
$categories = $db->query($query)->fetchAll();

// Get category for editing
$edit_category = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $query = "SELECT * FROM categories WHERE category_id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $edit_id);
    $stmt->execute();
    $edit_category = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/js/modern-ui.js" defer></script>
    <script src="../assets/js/ajax-handler.js" defer></script>
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>
    
    <div class="dashboard-container">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1>Manage Categories</h1>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>
            
            <div class="dashboard-grid">
                <div class="content-section">
                    <h2><?php echo $edit_category ? 'Edit Category' : 'Add New Category'; ?></h2>
                    <form action="" method="POST">
                        <input type="hidden" name="action" value="<?php echo $edit_category ? 'edit' : 'add'; ?>">
                        <?php if ($edit_category): ?>
                        <input type="hidden" name="category_id" value="<?php echo $edit_category['category_id']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="category_name">Category Name *</label>
                            <input type="text" id="category_name" name="category_name" 
                                    value="<?php echo $edit_category ? htmlspecialchars($edit_category['category_name']) : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="3"><?php echo $edit_category ? htmlspecialchars($edit_category['description']) : ''; ?></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <?php echo $edit_category ? 'Update Category' : 'Add Category'; ?>
                            </button>
                            <?php if ($edit_category): ?>
                            <a href="categories.php" class="btn">Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                
                <div class="content-section">
                    <h2>All Categories</h2>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Category Name</th>
                                    <th>Products</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo $cat['category_name']; ?></strong><br>
                                        <small><?php echo substr($cat['description'], 0, 50); ?>...</small>
                                    </td>
                                    <td><?php echo $cat['product_count']; ?></td>
                                    <td>
                                        <a href="categories.php?edit=<?php echo $cat['category_id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                        <a href="categories.php?delete=<?php echo $cat['category_id']; ?>" 
                                            class="btn btn-sm btn-danger" 
                                            onclick="return confirm('Delete this category?')">Delete</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>
