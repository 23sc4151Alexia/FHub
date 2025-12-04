<?php
require_once '../config/config.php';
checkRole(['admin']);

$database = new Database();
$db = $database->getConnection();

// Get categories
$cat_query = "SELECT * FROM categories ORDER BY category_name";
$categories = $db->query($cat_query)->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $category_id = (int)$_POST['category_id'];
    $product_name = sanitize($_POST['product_name']);
    $description = sanitize($_POST['description']);
    $price = (float)$_POST['price'];
    $stock_quantity = (int)$_POST['stock_quantity'];
    $status = sanitize($_POST['status']);
    
    if (empty($product_name) || $price <= 0) {
        $error = "Please fill in all required fields correctly";
    } else {
        try {
            $query = "INSERT INTO products (category_id, product_name, description, price, stock_quantity, status) 
                        VALUES (:category_id, :product_name, :description, :price, :stock_quantity, :status)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':category_id', $category_id);
            $stmt->bindParam(':product_name', $product_name);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':stock_quantity', $stock_quantity);
            $stmt->bindParam(':status', $status);
            
            if ($stmt->execute()) {
                header('Location: products.php?success=Product added successfully');
                exit();
            }
        } catch (PDOException $e) {
            $error = "Failed to add product";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/js/modern-ui.js" defer></script>
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>
    
    <div class="dashboard-container">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1>Add New Product</h1>
                <a href="products.php" class="btn">← Back</a>
            </div>
            
            <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="content-section">
                <form action="" method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="product_name">Product Name *</label>
                            <input type="text" id="product_name" name="product_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="category_id">Category *</label>
                            <select id="category_id" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['category_id']; ?>"><?php echo $cat['category_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="price">Price (₱) *</label>
                            <input type="number" id="price" name="price" step="0.01" min="0" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="stock_quantity">Stock Quantity *</label>
                            <input type="number" id="stock_quantity" name="stock_quantity" min="0" value="0" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Status *</label>
                            <select id="status" name="status" required>
                                <option value="available">Available</option>
                                <option value="out_of_stock">Out of Stock</option>
                                <option value="discontinued">Discontinued</option>
                            </select>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="4"></textarea>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Add Product</button>
                        <a href="products.php" class="btn">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
