<?php
require_once '../config/config.php';
checkRole(['admin']);

$isAjax = isset($_GET['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

if (!isset($_GET['id'])) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Product ID required']);
        exit();
    }
    header('Location: products.php');
    exit();
}

$product_id = (int)$_GET['id'];

$database = new Database();
$db = $database->getConnection();

try {
    // Check if product has orders
    $check_query = "SELECT COUNT(*) as count FROM order_items WHERE product_id = :product_id";
    $stmt = $db->prepare($check_query);
    $stmt->bindParam(':product_id', $product_id);
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        // Don't delete, just mark as discontinued
        $query = "UPDATE products SET status = 'discontinued' WHERE product_id = :product_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();
        
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Product marked as discontinued']);
            exit();
        }
        header('Location: products.php?success=Product marked as discontinued');
    } else {
        // Safe to delete
        $query = "DELETE FROM products WHERE product_id = :product_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();
        
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
            exit();
        }
        header('Location: products.php?success=Product deleted successfully');
    }
} catch (PDOException $e) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to delete product']);
        exit();
    }
    header('Location: products.php?error=Failed to delete product');
}
exit();
?>
