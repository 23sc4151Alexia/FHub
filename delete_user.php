<?php
require_once '../config/config.php';
checkRole(['admin']);

$isAjax = isset($_GET['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

if (!isset($_GET['id'])) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'User ID required']);
        exit();
    }
    header('Location: users.php');
    exit();
}

$user_id = (int)$_GET['id'];

// Prevent deleting self
if ($user_id == $_SESSION['user_id']) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'You cannot delete your own account']);
        exit();
    }
    header('Location: users.php?error=You cannot delete your own account');
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    // Get the user's role first
    $role_query = "SELECT role FROM users WHERE user_id = :user_id";
    $stmt = $db->prepare($role_query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch();
    
    if (!$user) {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit();
        }
        header('Location: users.php?error=User not found');
        exit();
    }
    
    $db->beginTransaction();
    
    // If deleting an owner, cascade delete all customers and riders
    if ($user['role'] === 'owner') {
        // Delete rider earnings for riders
        $db->exec("DELETE re FROM rider_earnings re 
                   INNER JOIN users u ON re.rider_id = u.user_id 
                   WHERE u.role = 'rider'");
        
        // Delete cart items for customers
        $db->exec("DELETE c FROM cart c 
                   INNER JOIN users u ON c.customer_id = u.user_id 
                   WHERE u.role = 'customer'");
        
        // Delete order items for all orders
        $db->exec("DELETE oi FROM order_items oi 
                   INNER JOIN orders o ON oi.order_id = o.order_id 
                   INNER JOIN users u ON o.customer_id = u.user_id 
                   WHERE u.role = 'customer'");
        
        // Delete orders for customers
        $db->exec("DELETE o FROM orders o 
                   INNER JOIN users u ON o.customer_id = u.user_id 
                   WHERE u.role = 'customer'");
        
        // Delete all products owned by this owner
        $delete_products = "DELETE FROM products WHERE owner_id = :owner_id";
        $stmt = $db->prepare($delete_products);
        $stmt->bindParam(':owner_id', $user_id);
        $stmt->execute();
        
        // Delete all categories owned by this owner
        $delete_categories = "DELETE FROM categories WHERE owner_id = :owner_id";
        $stmt = $db->prepare($delete_categories);
        $stmt->bindParam(':owner_id', $user_id);
        $stmt->execute();
        
        // Delete all customers and riders
        $db->exec("DELETE FROM users WHERE role IN ('customer', 'rider')");
        
        // Delete the owner
        $delete_owner = "DELETE FROM users WHERE user_id = :user_id";
        $stmt = $db->prepare($delete_owner);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $db->commit();
        
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Owner and all associated customers/riders deleted successfully']);
            exit();
        }
        header('Location: users.php?success=Owner and all associated customers/riders deleted successfully');
        exit();
    }
    
    // For non-owner users, check if they have orders
    $check_query = "SELECT COUNT(*) as count FROM orders WHERE customer_id = :user_id OR rider_id = :user_id";
    $stmt = $db->prepare($check_query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        // Don't delete, just deactivate
        $query = "UPDATE users SET status = 'inactive' WHERE user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $db->commit();
        
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'User deactivated successfully (has order history)']);
            exit();
        }
        header('Location: users.php?success=User deactivated successfully (has order history)');
    } else {
        // Safe to delete - also clean up related data
        // Delete cart items
        $db->prepare("DELETE FROM cart WHERE customer_id = :user_id")->execute([':user_id' => $user_id]);
        
        // Delete rider earnings
        $db->prepare("DELETE FROM rider_earnings WHERE rider_id = :user_id")->execute([':user_id' => $user_id]);
        
        // Delete user
        $query = "DELETE FROM users WHERE user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $db->commit();
        
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
            exit();
        }
        header('Location: users.php?success=User deleted successfully');
    }
} catch (PDOException $e) {
    $db->rollBack();
    
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
        exit();
    }
    header('Location: users.php?error=Failed to delete user: ' . urlencode($e->getMessage()));
}
exit();
?>
