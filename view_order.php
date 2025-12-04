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
$query = "SELECT o.*, u.full_name as customer_name, u.phone as customer_phone, u.email as customer_email, u.address as customer_address,
          r.full_name as rider_name, r.phone as rider_phone
          FROM orders o 
          LEFT JOIN users u ON o.customer_id = u.user_id 
          LEFT JOIN users r ON o.rider_id = r.user_id 
          WHERE o.order_id = :order_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':order_id', $order_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header('Location: orders.php?error=Order not found');
    exit();
}

$order = $stmt->fetch();

// Get order items
$query = "SELECT oi.*, p.product_name 
          FROM order_items oi 
          JOIN products p ON oi.product_id = p.product_id 
          WHERE oi.order_id = :order_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':order_id', $order_id);
$stmt->execute();
$items = $stmt->fetchAll();

// Get order status history
$query = "SELECT osh.*, u.full_name as changed_by_name 
          FROM order_status_history osh 
          LEFT JOIN users u ON osh.changed_by = u.user_id 
          WHERE osh.order_id = :order_id 
          ORDER BY osh.created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':order_id', $order_id);
$stmt->execute();
$history = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Order - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/js/modern-ui.js" defer></script>
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>
    
    <div class="dashboard-container">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1>Order Details</h1>
                <div>
                    <a href="edit_order.php?id=<?php echo $order_id; ?>" class="btn btn-primary">Edit Order</a>
                    <a href="orders.php" class="btn">← Back</a>
                </div>
            </div>
            
            <div class="order-details-grid">
                <div class="content-section">
                    <h2>Order Information</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Order Number:</label>
                            <strong><?php echo $order['order_number']; ?></strong>
                        </div>
                        <div class="info-item">
                            <label>Order Date:</label>
                            <span><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Status:</label>
                            <span class="badge badge-<?php echo $order['status']; ?>">
                                <?php echo str_replace('_', ' ', ucfirst($order['status'])); ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <label>Payment Method:</label>
                            <span><?php echo str_replace('_', ' ', ucfirst($order['payment_method'])); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Payment Status:</label>
                            <span class="badge badge-<?php echo $order['payment_status']; ?>">
                                <?php echo ucfirst($order['payment_status']); ?>
                            </span>
                        </div>
                        <?php if (!empty($order['payment_proof']) && in_array($order['payment_method'], ['bank_transfer', 'online_payment'])): ?>
                        <div class="info-item full-width">
                            <label>Payment Proof:</label>
                            <div style="margin-top: 10px;">
                                <?php 
                                $file_extension = strtolower(pathinfo($order['payment_proof'], PATHINFO_EXTENSION));
                                if (in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])): 
                                ?>
                                    <a href="../<?php echo $order['payment_proof']; ?>" target="_blank">
                                        <img src="../<?php echo $order['payment_proof']; ?>" 
                                             alt="Payment Proof" 
                                             style="max-width: 300px; max-height: 300px; border: 2px solid #e2e8f0; border-radius: 8px; cursor: pointer;">
                                    </a>
                                <?php else: ?>
                                    <a href="../<?php echo $order['payment_proof']; ?>" target="_blank" class="btn btn-sm btn-primary">
                                                                                <i class="fas fa-file-pdf"></i> View Payment Proof (PDF)
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="content-section">
                    <h2>Customer Information</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Name:</label>
                            <strong><?php echo $order['customer_name']; ?></strong>
                        </div>
                        <div class="info-item">
                            <label>Phone:</label>
                            <span><?php echo $order['customer_phone']; ?></span>
                        </div>
                        <div class="info-item">
                            <label>Email:</label>
                            <span><?php echo $order['customer_email']; ?></span>
                        </div>
                        <div class="info-item full-width">
                            <label>Customer Home Address:</label>
                            <p class="address-text"><strong><?php echo nl2br($order['customer_address']); ?></strong></p>
                        </div>
                        <div class="info-item full-width">
                            <label>Delivery Address:</label>
                            <p class="address-text"><?php echo nl2br($order['delivery_address']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if ($order['rider_id']): ?>
            <div class="content-section">
                <h2>Rider Information</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Rider:</label>
                        <strong><?php echo $order['rider_name']; ?></strong>
                    </div>
                    <div class="info-item">
                        <label>Phone:</label>
                        <span><?php echo $order['rider_phone']; ?></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="content-section">
                <h2>Order Items</h2>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?php echo $item['product_name']; ?></td>
                                <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>₱<?php echo number_format($item['subtotal'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-right"><strong>Delivery Fee:</strong></td>
                                <td><strong>₱<?php echo number_format($order['delivery_fee'], 2); ?></strong></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-right"><strong>Total Amount:</strong></td>
                                <td><strong>₱<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            
            <?php if ($order['notes']): ?>
            <div class="content-section">
                <h2>Order Notes</h2>
                <p><?php echo nl2br($order['notes']); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($order['delivery_proof_image'])): ?>
            <div class="content-section">
                <h2><i class="fas fa-camera"></i> Delivery Proof Photo</h2>
                <p style="color: #10b981; font-weight: 600;"><i class="fas fa-check-circle"></i> Rider uploaded delivery verification photo</p>
                <img src="../<?php echo htmlspecialchars($order['delivery_proof_image']); ?>" 
                     alt="Delivery Proof" 
                     style="max-width: 100%; max-height: 500px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); cursor: pointer;"
                     onclick="window.open(this.src, '_blank')">
                <small style="color: #6b7280; display: block; margin-top: 10px;">Click image to view full size</small>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($order['delivery_proof_image'])): ?>
            <div class="content-section">
                <h2><i class="fas fa-camera"></i> Delivery Proof Photo</h2>
                <p style="color: #10b981; font-weight: 600;"><i class="fas fa-check-circle"></i> Rider uploaded delivery verification photo</p>
                <img src="../<?php echo htmlspecialchars($order['delivery_proof_image']); ?>" 
                     alt="Delivery Proof" 
                     style="max-width: 100%; max-height: 500px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); cursor: pointer;"
                     onclick="window.open(this.src, '_blank')">
                <small style="color: #6b7280; display: block; margin-top: 10px;">Click image to view full size</small>
            </div>
            <?php endif; ?>
            
            <div class="content-section">
                <h2>Status History</h2>
                <div class="timeline">
                    <?php foreach ($history as $record): ?>
                    <div class="timeline-item">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <span class="badge badge-<?php echo $record['status']; ?>">
                                <?php echo str_replace('_', ' ', ucfirst($record['status'])); ?>
                            </span>
                            <?php if ($record['comment']): ?>
                            <p><?php echo $record['comment']; ?></p>
                            <?php endif; ?>
                            <small>By <?php echo $record['changed_by_name'] ?: 'System'; ?> • 
                                <?php echo date('M d, Y H:i', strtotime($record['created_at'])); ?>
                            </small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>
    
    <style>
        .order-details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .info-item {
            padding: 10px 0;
        }
        
        .info-item.full-width {
            grid-column: 1 / -1;
        }
        
        .info-item label {
            display: block;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .address-text {
            background: #f8fafc;
            padding: 12px;
            border-radius: 6px;
            margin-top: 5px;
        }
        
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline-item {
            position: relative;
            padding-bottom: 25px;
        }
        
        .timeline-marker {
            position: absolute;
            left: -30px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #2563eb;
            border: 3px solid white;
            box-shadow: 0 0 0 2px #2563eb;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -24px;
            top: 12px;
            bottom: -25px;
            width: 2px;
            background: #e2e8f0;
        }
        
        .timeline-item:last-child::before {
            display: none;
        }
        
        .timeline-content {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
        }
        
        .timeline-content p {
            margin: 10px 0;
        }
        
        .timeline-content small {
            color: #64748b;
            font-size: 12px;
        }
        
        @media (max-width: 768px) {
            .order-details-grid {
                grid-template-columns: 1fr;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>
