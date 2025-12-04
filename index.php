<?php
// Check if database exists first
try {
    $test_conn = new PDO("mysql:host=localhost;dbname=furnhub_db", "root", "");
    $test_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if users table exists and has data
    $check = $test_conn->query("SELECT COUNT(*) as count FROM users");
    if ($check->fetch()['count'] == 0) {
        // Database exists but no users - redirect to setup
        header('Location: setup.php');
        exit();
    }
} catch(PDOException $e) {
    // Database doesn't exist - redirect to setup
    header('Location: setup.php');
    exit();
}

require_once 'config/config.php';

if (isLoggedIn()) {
    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: admin/dashboard.php');
            break;
        case 'owner':
            header('Location: owner/dashboard.php');
            break;
        case 'rider':
            header('Location: rider/dashboard.php');
            break;
        case 'customer':
            header('Location: customer/index.php');
            break;
    }
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get featured products with store name
$query = "SELECT p.*, c.category_name, u.full_name as store_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.category_id 
            LEFT JOIN users u ON p.owner_id = u.user_id
            WHERE p.status = 'available' 
            ORDER BY p.created_at DESC 
            LIMIT 8";
$stmt = $db->query($query);
$featured_products = $stmt->fetchAll();

// Get only owner-created categories (with owner_id) that have products
$cat_query = "SELECT c.*, COUNT(p.product_id) as product_count 
              FROM categories c 
              LEFT JOIN products p ON c.category_id = p.category_id AND p.status = 'available'
              WHERE c.owner_id IS NOT NULL 
              GROUP BY c.category_id 
              ORDER BY c.category_name";
$categories = $db->query($cat_query)->fetchAll();

// Define category icons and colors
$category_styles = [
    'chairs' => ['icon' => 'fa-chair', 'color' => '#6366f1', 'gradient' => 'linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%)'],
    'beds' => ['icon' => 'fa-bed', 'color' => '#ec4899', 'gradient' => 'linear-gradient(135deg, #ec4899 0%, #f472b6 100%)'],
    'tables' => ['icon' => 'fa-table', 'color' => '#14b8a6', 'gradient' => 'linear-gradient(135deg, #14b8a6 0%, #2dd4bf 100%)'],
    'kitchen tables' => ['icon' => 'fa-utensils', 'color' => '#f59e0b', 'gradient' => 'linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%)'],
    'sofas' => ['icon' => 'fa-couch', 'color' => '#3b82f6', 'gradient' => 'linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%)'],
    'wardrobes' => ['icon' => 'fa-door-closed', 'color' => '#8b5cf6', 'gradient' => 'linear-gradient(135deg, #8b5cf6 0%, #a78bfa 100%)'],
    'desks' => ['icon' => 'fa-desktop', 'color' => '#10b981', 'gradient' => 'linear-gradient(135deg, #10b981 0%, #34d399 100%)'],
    'outdoor' => ['icon' => 'fa-umbrella-beach', 'color' => '#06b6d4', 'gradient' => 'linear-gradient(135deg, #06b6d4 0%, #22d3ee 100%)'],
    'default' => ['icon' => 'fa-box', 'color' => '#64748b', 'gradient' => 'linear-gradient(135deg, #64748b 0%, #94a3b8 100%)']
];

function getCategoryStyle($name, $styles) {
    $key = strtolower(trim($name));
    return $styles[$key] ?? $styles['default'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FurnHub - Furniture Shop</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #0f172a;
            min-height: 100vh;
        }
        
        /* Header */
        .main-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            padding: 20px 0;
            background: transparent;
            transition: all 0.3s;
        }
        
        .main-header.scrolled {
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(10px);
            padding: 15px 0;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
        }
        
        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .logo h1 {
            font-size: 28px;
            font-weight: 800;
            color: #5eead4;
            letter-spacing: -0.5px;
        }
        
        .main-nav {
            display: flex;
            align-items: center;
            gap: 32px;
        }
        
        .main-nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            font-size: 15px;
            transition: color 0.3s;
        }
        
        .main-nav a:hover {
            color: #5eead4;
        }
        
        .nav-btn {
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .nav-btn.login {
            background: #1e293b;
            color: white;
            border: 2px solid #334155;
        }
        
        .nav-btn.login:hover {
            background: #334155;
            border-color: #5eead4;
        }
        
        .nav-btn.register {
            background: transparent;
            color: white;
            border: 2px solid #475569;
        }
        
        .nav-btn.register:hover {
            border-color: #5eead4;
            color: #5eead4;
        }
        
        /* Hero Section */
        .hero-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            background: linear-gradient(to right, 
                #7dd3c0 0%, 
                #6bc4b8 15%,
                #5fb8c0 30%,
                #5aafc8 45%,
                #6a9cc8 55%,
                #7a8ac8 65%,
                #8a78c0 75%,
                #9a68b8 85%,
                #a060a8 100%
            );
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: conic-gradient(
                from 0deg at 50% 100%,
                rgba(255, 255, 255, 0) 0deg,
                rgba(255, 255, 255, 0.03) 10deg,
                rgba(255, 255, 255, 0) 20deg,
                rgba(255, 255, 255, 0.03) 30deg,
                rgba(255, 255, 255, 0) 40deg,
                rgba(255, 255, 255, 0.03) 50deg,
                rgba(255, 255, 255, 0) 60deg,
                rgba(255, 255, 255, 0.03) 70deg,
                rgba(255, 255, 255, 0) 80deg,
                rgba(255, 255, 255, 0.03) 90deg,
                rgba(255, 255, 255, 0) 100deg,
                rgba(255, 255, 255, 0.03) 110deg,
                rgba(255, 255, 255, 0) 120deg,
                rgba(255, 255, 255, 0.03) 130deg,
                rgba(255, 255, 255, 0) 140deg,
                rgba(255, 255, 255, 0.03) 150deg,
                rgba(255, 255, 255, 0) 160deg,
                rgba(255, 255, 255, 0.03) 170deg,
                rgba(255, 255, 255, 0) 180deg
            );
            pointer-events: none;
        }
        
        .hero-content {
            text-align: center;
            position: relative;
            z-index: 1;
            padding: 120px 20px 80px;
        }
        
        .hero-content h1 {
            font-size: 64px;
            font-weight: 800;
            color: white;
            margin-bottom: 20px;
            letter-spacing: -2px;
            text-shadow: 0 4px 30px rgba(0, 0, 0, 0.3);
        }
        
        .hero-content p {
            font-size: 20px;
            color: rgba(255, 255, 255, 0.85);
            margin-bottom: 40px;
            font-weight: 400;
        }
        
        .hero-buttons {
            display: flex;
            gap: 16px;
            justify-content: center;
        }
        
        .hero-btn {
            padding: 16px 32px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 16px;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .hero-btn.primary {
            background: #14b8a6;
            color: white;
            box-shadow: 0 4px 20px rgba(20, 184, 166, 0.4);
        }
        
        .hero-btn.primary:hover {
            background: #0d9488;
            transform: translateY(-2px);
            box-shadow: 0 6px 30px rgba(20, 184, 166, 0.5);
        }
        
        .hero-btn.secondary {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
        }
        
        .hero-btn.secondary:hover {
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-2px);
        }
        
        /* Decorative Elements */
        .decoration {
            position: absolute;
            pointer-events: none;
        }
        
        .sparkle {
            position: absolute;
            bottom: 80px;
            right: 80px;
            width: 40px;
            height: 40px;
            opacity: 0.6;
        }
        
        .sparkle::before,
        .sparkle::after {
            content: '';
            position: absolute;
            background: white;
        }
        
        .sparkle::before {
            width: 2px;
            height: 100%;
            left: 50%;
            transform: translateX(-50%);
        }
        
        .sparkle::after {
            width: 100%;
            height: 2px;
            top: 50%;
            transform: translateY(-50%);
        }
        
        /* Dark Section Below Hero */
        .dark-section {
            background: #0f172a;
            padding: 80px 0;
        }
        
        .section-title {
            font-size: 36px;
            font-weight: 700;
            color: white;
            text-align: center;
            margin-bottom: 48px;
        }
        
        /* Categories Grid */
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 24px;
            margin-bottom: 80px;
        }
        
        .category-card {
            background: #1e293b;
            border-radius: 20px;
            padding: 32px 24px;
            text-decoration: none;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid #334155;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .category-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--cat-gradient);
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .category-card:hover {
            transform: translateY(-10px);
            border-color: transparent;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
        }
        
        .category-card:hover::before {
            opacity: 1;
        }
        
        .category-icon {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 32px;
            color: white;
            position: relative;
            z-index: 1;
        }
        
        .category-card h3 {
            font-size: 18px;
            font-weight: 700;
            color: white;
            margin-bottom: 8px;
        }
        
        .category-card p {
            font-size: 13px;
            color: #94a3b8;
            margin-bottom: 12px;
        }
        
        .category-count {
            display: inline-block;
            padding: 6px 14px;
            background: rgba(255, 255, 255, 0.08);
            color: #94a3b8;
            font-size: 12px;
            font-weight: 600;
            border-radius: 20px;
        }
        
        /* Products Grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
        }
        
        .product-card {
            background: #1e293b;
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s;
            border: 1px solid #334155;
        }
        
        .product-card:hover {
            transform: translateY(-8px);
            border-color: #5eead4;
            box-shadow: 0 20px 40px rgba(94, 234, 212, 0.1);
        }
        
        .product-image {
            height: 200px;
            background: linear-gradient(135deg, #334155 0%, #1e293b 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .product-image .no-image {
            font-size: 48px;
            color: #475569;
        }
        
        .product-info {
            padding: 24px;
        }
        
        .category-badge {
            display: inline-block;
            padding: 4px 12px;
            background: rgba(94, 234, 212, 0.15);
            color: #5eead4;
            font-size: 12px;
            font-weight: 600;
            border-radius: 20px;
            margin-bottom: 12px;
        }
        
        .product-info h3 {
            font-size: 18px;
            font-weight: 700;
            color: white;
            margin-bottom: 6px;
        }
        
        .store-name {
            font-size: 13px;
            color: #a78bfa;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .store-name i {
            font-size: 11px;
        }
        
        .product-price {
            font-size: 22px;
            font-weight: 800;
            color: #5eead4;
            margin-bottom: 4px;
        }
        
        .product-stock {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 16px;
        }
        
        .product-btn {
            display: block;
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
            color: white;
            text-align: center;
            text-decoration: none;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s;
        }
        
        .product-btn:hover {
            opacity: 0.9;
            transform: scale(1.02);
        }
        
        /* Footer */
        .main-footer {
            background: #020617;
            padding: 40px 0;
            text-align: center;
            border-top: 1px solid #1e293b;
        }
        
        .main-footer p {
            color: #64748b;
            font-size: 14px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 40px;
            }
            
            .hero-content p {
                font-size: 16px;
            }
            
            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .main-nav {
                display: none;
            }
            
            .sparkle {
                display: none;
            }
        }
    </style>
</head>
<body>
    <header class="main-header" id="mainHeader">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <h1>FurnHub</h1>
                </div>
                <nav class="main-nav">
                    <a href="index.php">Home</a>
                    <a href="customer/products.php">Products</a>
                    <a href="#categories">Categories</a>
                    <a href="login.php" class="nav-btn login">Login</a>
                    <a href="register.php" class="nav-btn register">Register</a>
                </nav>
            </div>
        </div>
    </header>
    
    <section class="hero-section">
        <div class="hero-content">
            <h1>Welcome to FurnHub</h1>
            <p>Your one-stop shop for quality furniture</p>
            <div class="hero-buttons">
                <a href="register.php" class="hero-btn primary">Get Started</a>
                <a href="customer/products.php" class="hero-btn secondary">Browse Products</a>
            </div>
        </div>
        
        <!-- Decorative sparkle -->
        <div class="sparkle"></div>
    </section>
    
    <section class="dark-section" id="categories">
        <div class="container">
            <h2 class="section-title">Shop by Category</h2>
            <div class="categories-grid">
                <?php if (count($categories) > 0): ?>
                    <?php foreach ($categories as $category): 
                        $style = getCategoryStyle($category['category_name'], $category_styles);
                    ?>
                    <a href="customer/products.php?category=<?php echo $category['category_id']; ?>" 
                       class="category-card" 
                       style="--cat-gradient: <?php echo $style['gradient']; ?>">
                        <div class="category-icon" style="background: <?php echo $style['gradient']; ?>">
                            <i class="fas <?php echo $style['icon']; ?>"></i>
                        </div>
                        <h3><?php echo htmlspecialchars($category['category_name']); ?></h3>
                        <p><?php echo htmlspecialchars($category['description'] ?? 'Explore our collection'); ?></p>
                        <span class="category-count"><?php echo $category['product_count']; ?> Products</span>
                    </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #94a3b8;">
                        <i class="fas fa-folder-open" style="font-size: 48px; margin-bottom: 16px; display: block;"></i>
                        <p>No categories available yet. Check back soon!</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <h2 class="section-title">Featured Products</h2>
            <div class="products-grid">
                <?php if (count($featured_products) > 0): ?>
                    <?php foreach ($featured_products as $product): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <?php if ($product['image_url']): ?>
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                            <?php else: ?>
                            <div class="no-image"><i class="fas fa-couch"></i></div>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <span class="category-badge"><?php echo htmlspecialchars($product['category_name'] ?? 'Furniture'); ?></span>
                            <h3><?php echo htmlspecialchars($product['product_name']); ?></h3>
                            <p class="store-name"><i class="fas fa-store"></i> <?php echo htmlspecialchars($product['store_name'] ?? 'FurnHub Store'); ?></p>
                            <p class="product-price">₱<?php echo number_format($product['price'], 2); ?></p>
                            <p class="product-stock">Stock: <?php echo $product['stock_quantity']; ?> available</p>
                            <a href="login.php" class="product-btn">Login to Order</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 60px; color: #94a3b8;">
                        <i class="fas fa-box-open" style="font-size: 64px; margin-bottom: 20px; display: block;"></i>
                        <p style="font-size: 18px;">No products available yet.</p>
                        <p style="font-size: 14px; margin-top: 8px;">Check back soon for amazing furniture!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    
    <footer class="main-footer">
        <div class="container">
            <p>&copy; 2025 FurnHub. All rights reserved.</p>
        </div>
    </footer>
    
    <!-- Furny AI Floating Chat Button -->
    <div id="furnyFloatingChat">
        <!-- Floating Button -->
        <button id="furnyFloatBtn" class="furny-float-btn" onclick="toggleFurnyChat()">
            <div class="furny-icon">
                <i class="fas fa-robot"></i>
            </div>
            <div class="furny-pulse"></div>
        </button>
        
        <!-- Mini Chat Window -->
        <div id="furnyMiniChat" class="furny-mini-chat">
            <div class="furny-chat-header">
                <div class="furny-avatar">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="furny-info">
                    <h4>Furny Assistant</h4>
                    <span class="furny-status"><i class="fas fa-circle"></i> Online</span>
                </div>
                <button class="furny-close" onclick="toggleFurnyChat()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="furnyChatMessages" class="furny-chat-messages">
                <div class="furny-message bot">
                    <div class="message-content">
                        <p>👋 Hi there! I'm Furny, your furniture assistant!</p>
                        <p>I can help you find the perfect furniture. Login or register to chat with me and get personalized recommendations!</p>
                    </div>
                </div>
            </div>
            <div class="furny-chat-input">
                <div class="login-prompt">
                    <p>Login to chat with Furny and get personalized recommendations!</p>
                    <div class="login-buttons">
                        <a href="login.php" class="furny-login-btn">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                        <a href="register.php" class="furny-register-btn">
                            <i class="fas fa-user-plus"></i> Register
                        </a>
                    </div>
                </div>
            </div>
            <div class="furny-powered">
                <a href="login.php">Login for full AI features</a>
            </div>
        </div>
    </div>
    
    <style>
    /* Furny Floating Chat Styles */
    #furnyFloatingChat {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 9999;
        font-family: 'Segoe UI', sans-serif;
    }
    
    .furny-float-btn {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        border: none;
        cursor: pointer;
        box-shadow: 0 4px 20px rgba(99, 102, 241, 0.4);
        position: relative;
        transition: all 0.3s ease;
    }
    
    .furny-float-btn:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 25px rgba(99, 102, 241, 0.5);
    }
    
    .furny-icon {
        color: white;
        font-size: 24px;
    }
    
    .furny-pulse {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        border-radius: 50%;
        background: rgba(99, 102, 241, 0.4);
        animation: furnyPulse 2s infinite;
    }
    
    @keyframes furnyPulse {
        0% { transform: scale(1); opacity: 1; }
        100% { transform: scale(1.5); opacity: 0; }
    }
    
    .furny-mini-chat {
        position: absolute;
        bottom: 70px;
        right: 0;
        width: 350px;
        height: 450px;
        background: #1e1b4b;
        border-radius: 16px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        display: none;
        flex-direction: column;
        overflow: hidden;
        border: 1px solid rgba(99, 102, 241, 0.2);
    }
    
    .furny-mini-chat.active {
        display: flex;
        animation: slideUp 0.3s ease;
    }
    
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .furny-chat-header {
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        padding: 15px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .furny-avatar {
        width: 40px;
        height: 40px;
        background: rgba(255,255,255,0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 18px;
    }
    
    .furny-info {
        flex: 1;
    }
    
    .furny-info h4 {
        color: white;
        margin: 0;
        font-size: 16px;
    }
    
    .furny-status {
        color: rgba(255,255,255,0.8);
        font-size: 12px;
    }
    
    .furny-status i {
        color: #4ade80;
        font-size: 8px;
        margin-right: 4px;
    }
    
    .furny-close {
        background: rgba(255,255,255,0.2);
        border: none;
        color: white;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        cursor: pointer;
        transition: background 0.3s;
    }
    
    .furny-close:hover {
        background: rgba(255,255,255,0.3);
    }
    
    .furny-chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 15px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        background: #0f0d24;
    }
    
    .furny-message {
        max-width: 85%;
    }
    
    .furny-message.bot {
        align-self: flex-start;
    }
    
    .furny-message .message-content {
        background: #1e1b4b;
        padding: 12px 15px;
        border-radius: 15px;
        color: #e2e8f0;
        font-size: 14px;
        line-height: 1.5;
    }
    
    .furny-message .message-content p {
        margin: 0 0 8px 0;
    }
    
    .furny-message .message-content p:last-child {
        margin-bottom: 0;
    }
    
    .furny-chat-input {
        padding: 15px;
        background: #1e1b4b;
        border-top: 1px solid rgba(99, 102, 241, 0.2);
    }
    
    .login-prompt {
        text-align: center;
    }
    
    .login-prompt p {
        color: #94a3b8;
        font-size: 13px;
        margin: 0 0 12px 0;
    }
    
    .login-buttons {
        display: flex;
        gap: 10px;
        justify-content: center;
    }
    
    .furny-login-btn, .furny-register-btn {
        padding: 10px 20px;
        border-radius: 8px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .furny-login-btn {
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        color: white;
    }
    
    .furny-login-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
    }
    
    .furny-register-btn {
        background: rgba(99, 102, 241, 0.1);
        color: #a5b4fc;
        border: 1px solid rgba(99, 102, 241, 0.3);
    }
    
    .furny-register-btn:hover {
        background: rgba(99, 102, 241, 0.2);
    }
    
    .furny-powered {
        padding: 10px;
        text-align: center;
        background: #1e1b4b;
        border-top: 1px solid rgba(99, 102, 241, 0.1);
    }
    
    .furny-powered a {
        color: #818cf8;
        text-decoration: none;
        font-size: 12px;
        transition: color 0.3s;
    }
    
    .furny-powered a:hover {
        color: #a5b4fc;
    }
    
    @media (max-width: 480px) {
        .furny-mini-chat {
            width: calc(100vw - 40px);
            right: -10px;
            height: 400px;
        }
    }
    </style>
    
    <script>
        // Header scroll effect
        window.addEventListener('scroll', function() {
            const header = document.getElementById('mainHeader');
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
        
        // Furny Chat Toggle
        function toggleFurnyChat() {
            const miniChat = document.getElementById('furnyMiniChat');
            const floatBtn = document.getElementById('furnyFloatBtn');
            
            miniChat.classList.toggle('active');
            
            if (miniChat.classList.contains('active')) {
                floatBtn.style.display = 'none';
            } else {
                floatBtn.style.display = 'block';
            }
        }
        
        // Close chat when clicking outside
        document.addEventListener('click', function(e) {
            const chat = document.getElementById('furnyFloatingChat');
            const miniChat = document.getElementById('furnyMiniChat');
            
            if (!chat.contains(e.target) && miniChat.classList.contains('active')) {
                toggleFurnyChat();
            }
        });
    </script>
</body>
</html>
