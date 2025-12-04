<?php
require_once '../config/config.php';
checkRole(['admin']);

if (!isset($_GET['id'])) {
    header('Location: products.php');
    exit();
}

$product_id = (int)$_GET['id'];

$database = new Database();
$db = $database->getConnection();

// Get product details
$query = "SELECT * FROM products WHERE product_id = :product_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':product_id', $product_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header('Location: products.php?error=Product not found');
    exit();
}

$product = $stmt->fetch();

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
            $query = "UPDATE products SET 
                        category_id = :category_id,
                        product_name = :product_name,
                        description = :description,
                        price = :price,
                        stock_quantity = :stock_quantity,
                        status = :status,
                        updated_at = NOW()
                        WHERE product_id = :product_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':category_id', $category_id);
            $stmt->bindParam(':product_name', $product_name);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':stock_quantity', $stock_quantity);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':product_id', $product_id);
            
            if ($stmt->execute()) {
                header('Location: products.php?success=Product updated successfully');
                exit();
            }
        } catch (PDOException $e) {
            $error = "Failed to update product";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/js/modern-ui.js" defer></script>
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>
    
    <div class="dashboard-container">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1>Edit Product</h1>
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
                            <input type="text" id="product_name" name="product_name" 
                                value="<?php echo htmlspecialchars($product['product_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="category_id">Category *</label>
                            <select id="category_id" name="category_id" required>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['category_id']; ?>"
                                        <?php echo $product['category_id'] == $cat['category_id'] ? 'selected' : ''; ?>>
                                    <?php echo $cat['category_name']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="price">Price (₱) *</label>
                            <input type="number" id="price" name="price" step="0.01" min="0" 
                                    value="<?php echo $product['price']; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="stock_quantity">Stock Quantity *</label>
                            <input type="number" id="stock_quantity" name="stock_quantity" min="0" 
                                    value="<?php echo $product['stock_quantity']; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Status *</label>
                            <select id="status" name="status" required>
                                <option value="available" <?php echo $product['status'] == 'available' ? 'selected' : ''; ?>>Available</option>
                                <option value="out_of_stock" <?php echo $product['status'] == 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
                                <option value="discontinued" <?php echo $product['status'] == 'discontinued' ? 'selected' : ''; ?>>Discontinued</option>
                            </select>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($product['description']); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Update Product</button>
                        <a href="products.php" class="btn">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
