<?php
require_once '../config/config.php';
checkRole(['admin']);

$database = new Database();
$db = $database->getConnection();

// Get all products with category info
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? sanitize($_GET['category']) : '';

$query = "SELECT p.*, c.category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.category_id 
            WHERE 1=1";

if (!empty($search)) {
    $query .= " AND p.product_name LIKE :search";
}

if (!empty($category_filter)) {
    $query .= " AND p.category_id = :category";
}

$query .= " ORDER BY p.created_at DESC";

$stmt = $db->prepare($query);

if (!empty($search)) {
    $search_param = "%$search%";
    $stmt->bindParam(':search', $search_param);
}

if (!empty($category_filter)) {
    $stmt->bindParam(':category', $category_filter);
}

$stmt->execute();
$products = $stmt->fetchAll();

// Get all categories
$cat_query = "SELECT * FROM categories ORDER BY category_name";
$categories = $db->query($cat_query)->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/js/modern-ui.js" defer></script>
    <script src="../assets/js/theme-switcher.js" defer></script>
    <script src="../assets/js/ajax-handler.js" defer></script>
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>
    
    <div class="dashboard-container">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1>Manage Products</h1>
                <a href="add_product.php" class="btn btn-primary">+ Add Product</a>
            </div>
            
            <div class="filter-section">
                <form method="GET" class="filter-form">
                    <input type="text" name="search" placeholder="Search products..." 
                            value="<?php echo htmlspecialchars($search); ?>">
                    
                    <select name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['category_id']; ?>" 
                                <?php echo $category_filter == $cat['category_id'] ? 'selected' : ''; ?>>
                            <?php echo $cat['category_name']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="products.php" class="btn">Reset</a>
                </form>
            </div>
            
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?php echo $product['product_name']; ?></td>
                            <td><?php echo $product['category_name']; ?></td>
                            <td>₱<?php echo number_format($product['price'], 2); ?></td>
                            <td><?php echo $product['stock_quantity']; ?></td>
                            <td><span class="badge badge-<?php echo $product['status']; ?>"><?php echo str_replace('_', ' ', ucfirst($product['status'])); ?></span></td>
                            <td>
                                <a href="edit_product.php?id=<?php echo $product['product_id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                <a href="delete_product.php?id=<?php echo $product['product_id']; ?>" 
                                    class="btn btn-sm btn-danger" 
                                    onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="6" class="text-center">No products found</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
