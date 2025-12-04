<?php
require_once '../config/config.php';
checkRole(['admin']);

if (!isset($_GET['id'])) {
    header('Location: orders.php');
    exit();
}

$order_id = sanitize($_GET['id']);

$database = new Database();
$db = $database->getConnection();

// Get order details
$query = "SELECT o.*, u.full_name as customer_name
            FROM orders o 
            LEFT JOIN users u ON o.customer_id = u.user_id 
            WHERE o.order_id = :order_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':order_id', $order_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header('Location: orders.php?error=Order not found');
    exit();
}

$order = $stmt->fetch();

// Get all riders
$riders_query = "SELECT user_id, full_name FROM users WHERE role = 'rider' AND status = 'active'";
$riders = $db->query($riders_query)->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $status = sanitize($_POST['status']);
    $payment_status = sanitize($_POST['payment_status']);
    $rider_id = !empty($_POST['rider_id']) ? (int)$_POST['rider_id'] : null;
    $notes = sanitize($_POST['notes']);
    
    try {
        $db->beginTransaction();
        
        // Update order
        $query = "UPDATE orders SET 
                    status = :status,
                    payment_status = :payment_status,
                    rider_id = :rider_id,
                    notes = :notes,
                    updated_at = NOW()
                    WHERE order_id = :order_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':payment_status', $payment_status);
        $stmt->bindParam(':rider_id', $rider_id);
        $stmt->bindParam(':notes', $notes);
        $stmt->bindParam(':order_id', $order_id);
        $stmt->execute();
        
        // Add to status history if status changed
        if ($status != $order['status']) {
            $query = "INSERT INTO order_status_history (order_id, status, comment, changed_by) 
                        VALUES (:order_id, :status, 'Status updated by admin', :changed_by)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':order_id', $order_id);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':changed_by', $_SESSION['user_id']);
            $stmt->execute();
        }
        
        $db->commit();
        header('Location: view_order.php?id=' . $order_id . '&success=Order updated successfully');
        exit();
        
    } catch (PDOException $e) {
        $db->rollBack();
        $error = "Failed to update order";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Order - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/js/modern-ui.js" defer></script>
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>
    
    <div class="dashboard-container">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1>Edit Order</h1>
                <a href="view_order.php?id=<?php echo $order_id; ?>" class="btn">← Back</a>
            </div>
            
            <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
            <?php endif; ?>
            
            <div class="content-section">
                <h2>Order: <?php echo $order['order_number']; ?></h2>
                <p><strong>Customer:</strong> <?php echo $order['customer_name']; ?></p>
                
                <form action="" method="POST" style="margin-top: 20px;">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="status">Order Status *</label>
                            <select id="status" name="status" required>
                                <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="confirmed" <?php echo $order['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="out_for_delivery" <?php echo $order['status'] == 'out_for_delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
                                <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="payment_status">Payment Status *</label>
                            <select id="payment_status" name="payment_status" required>
                                <option value="pending" <?php echo $order['payment_status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="paid" <?php echo $order['payment_status'] == 'paid' ? 'selected' : ''; ?>>Paid</option>
                                <option value="refunded" <?php echo $order['payment_status'] == 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="rider_id">Assign Rider</label>
                            <select id="rider_id" name="rider_id">
                                <option value="">Not Assigned</option>
                                <?php foreach ($riders as $rider): ?>
                                <option value="<?php echo $rider['user_id']; ?>" 
                                        <?php echo $order['rider_id'] == $rider['user_id'] ? 'selected' : ''; ?>>
                                    <?php echo $rider['full_name']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="notes">Order Notes</label>
                            <textarea id="notes" name="notes" rows="4"><?php echo htmlspecialchars($order['notes']); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Update Order</button>
                        <a href="view_order.php?id=<?php echo $order_id; ?>" class="btn">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
    
    <style>
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-actions {
            margin-top: 30px;
            display: flex;
            gap: 10px;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>
