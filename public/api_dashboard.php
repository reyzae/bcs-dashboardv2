<?php
/**
 * Dashboard API Router
 * Routes dashboard-specific API requests to appropriate controllers
 */

// Error reporting based on environment
$isDebug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
if ($isDebug) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
}

// Load bootstrap
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/helpers/SecurityMiddleware.php';

// Set JSON header and CORS headers (align with api.php)
header('Content-Type: application/json');
// Security headers for dashboard API responses
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: frame-ancestors 'self'; base-uri 'self'");
header('Vary: Origin');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, X-Requested-With, Authorization');
header('Access-Control-Allow-Credentials: true');

// CORS Configuration - Production/Development
$isProduction = ($_ENV['APP_ENV'] ?? 'development') === 'production';
if ($isProduction) {
    $allowedOrigin = $_ENV['APP_URL'] ?? '';
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin && parse_url($origin, PHP_URL_HOST) === parse_url($allowedOrigin, PHP_URL_HOST)) {
        header('Access-Control-Allow-Origin: ' . $origin);
    }
    // Enforce origin validation in production
    SecurityMiddleware::validateOrigin();
} else {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if (!empty($origin)) {
        header('Access-Control-Allow-Origin: ' . $origin);
    } else {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if (!empty($host)) {
            header('Access-Control-Allow-Origin: ' . $scheme . $host);
        }
    }
}

// Handle CORS for development
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Apply security middleware
// Production: 100 requests/minute, Development: 1000 requests/minute
$rateLimit = $isProduction ? 100 : 1000;
SecurityMiddleware::rateLimit($rateLimit, 60);
// DISABLED for development - causing 403 errors with localhost:3000
// SecurityMiddleware::validateOrigin();

// Enforce CSRF for mutating requests in production
if ($isProduction) {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
        SecurityMiddleware::checkCsrfToken();
    }
}

// Check authentication
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit();
}

// Get action and method
$action = $_GET['action'] ?? '';
$method = $_GET['method'] ?? '';

try {
    // Load database
    require_once __DIR__ . '/../app/config/database.php';
    
    // Route to appropriate controller
    switch ($action) {
        case 'dashboard':
            require_once __DIR__ . '/../app/controllers/DashboardController.php';
            $controller = new DashboardController($pdo);
            
            switch ($method) {
                case 'stats':
                    $controller->stats();
                    break;
                    
                case 'cashierStats':
                    $controller->cashierStats();
                    break;
                    
                case 'managerStats':
                    $controller->managerStats();
                    break;
                    
                case 'stockStats':
                    $controller->stockStats();
                    break;
                    
                case 'salesChart':
                    $controller->salesChart();
                    break;
                    
                case 'paymentMethods':
                    $controller->paymentMethods();
                    break;
                    
                case 'topProducts':
                    $controller->topProducts();
                    break;
                    
                case 'recentActivity':
                    $controller->recentActivity();
                    break;
                    
                case 'teamPerformance':
                    $controller->teamPerformance();
                    break;
                    
                case 'customerInsights':
                    $controller->customerInsights();
                    break;
                    
                case 'categoryStock':
                    $controller->categoryStock();
                    break;
                    
                case 'salesTrend':
                    $controller->salesChart(); // Same as salesChart but different period
                    break;
                    
                case 'categoryPerformance':
                    // Category sales performance
                    $sql = "SELECT 
                        c.id,
                        c.name,
                        c.color,
                        COUNT(DISTINCT p.id) as product_count,
                        COALESCE(SUM(ti.total_price), 0) as total_revenue,
                        COALESCE(SUM(ti.quantity), 0) as total_quantity
                    FROM categories c
                    LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1
                    LEFT JOIN transaction_items ti ON p.id = ti.product_id
                    LEFT JOIN transactions t ON ti.transaction_id = t.id 
                        AND t.status = 'completed'
                        AND DATE(t.created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                    WHERE c.is_active = 1
                    GROUP BY c.id
                    ORDER BY total_revenue DESC";
                    
                    $stmt = $pdo->query($sql);
                    $categories = $stmt->fetchAll();
                    
                    echo json_encode([
                        'success' => true,
                        'data' => ['categories' => $categories]
                    ]);
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Invalid method'
                    ]);
            }
            break;
            
        case 'categories':
            require_once __DIR__ . '/../app/controllers/CategoryController.php';
            $controller = new CategoryController($pdo);
            
            switch ($method) {
                case 'list':
                    $controller->list();
                    break;
                    
                case 'get':
                    $controller->get();
                    break;
                    
                case 'create':
                    $controller->create();
                    break;
                    
                case 'update':
                    $controller->update();
                    break;
                    
                case 'delete':
                    $controller->delete();
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Invalid method: ' . $method
                    ]);
            }
            break;
            
        case 'products':
            require_once __DIR__ . '/../app/controllers/ProductController.php';
            $controller = new ProductController($pdo);
            
            switch ($method) {
                case 'list':
                    $controller->list();
                    break;
                    
                case 'get':
                    $controller->get();
                    break;
                    
                case 'create':
                    $controller->create();
                    break;
                    
                case 'update':
                    $controller->update();
                    break;
                    
                case 'delete':
                    $controller->delete();
                    break;
                    
                case 'uploadImage':
                    $controller->uploadImage();
                    break;
                    
                case 'search':
                    // Search products by query
                    $query = $_GET['q'] ?? $_GET['search'] ?? '';
                    if (strlen($query) < 2) {
                        echo json_encode([
                            'success' => true,
                            'data' => ['products' => []]
                        ]);
                        break;
                    }
                    
                    $sql = "SELECT p.*, c.name as category_name
                        FROM products p
                        LEFT JOIN categories c ON p.category_id = c.id
                        WHERE (p.name LIKE ? OR p.sku LIKE ? OR p.barcode LIKE ?)
                        AND p.is_active = 1
                        LIMIT 50";
                    
                    $searchTerm = '%' . $query . '%';
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
                    $products = $stmt->fetchAll();
                    
                    echo json_encode([
                        'success' => true,
                        'data' => ['products' => $products]
                    ]);
                    break;
                    
                case 'lowStock':
                    $limit = $_GET['limit'] ?? 100;
                    $sql = "SELECT p.*, c.name as category_name,
                        (p.min_stock_level - p.stock_quantity) as quantity_needed,
                        (p.price * (p.min_stock_level - p.stock_quantity)) as estimated_cost
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE p.stock_quantity <= p.min_stock_level
                    AND p.is_active = 1
                    ORDER BY p.stock_quantity ASC
                    LIMIT ?";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$limit]);
                    $products = $stmt->fetchAll();
                    
                    echo json_encode([
                        'success' => true,
                        'data' => ['products' => $products]
                    ]);
                    break;
                    
                case 'outOfStock':
                    $sql = "SELECT p.*, c.name as category_name
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE p.stock_quantity = 0
                    AND p.is_active = 1
                    ORDER BY p.created_at DESC";
                    
                    $stmt = $pdo->query($sql);
                    $products = $stmt->fetchAll();
                    
                    echo json_encode([
                        'success' => true,
                        'data' => ['products' => $products]
                    ]);
                    break;
                    
                case 'topSelling':
                    $limit = $_GET['limit'] ?? 10;
                    $sql = "SELECT 
                        p.id,
                        p.name,
                        p.sku,
                        c.name as category_name,
                        COUNT(ti.id) as times_sold,
                        SUM(ti.quantity) as total_quantity,
                        SUM(ti.total_price) as total_revenue
                    FROM products p
                    INNER JOIN transaction_items ti ON p.id = ti.product_id
                    INNER JOIN transactions t ON ti.transaction_id = t.id
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE t.status = 'completed'
                    AND DATE(t.created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                    GROUP BY p.id
                    ORDER BY total_quantity DESC
                    LIMIT ?";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$limit]);
                    $products = $stmt->fetchAll();
                    
                    echo json_encode([
                        'success' => true,
                        'data' => ['products' => $products]
                    ]);
                    break;
                    
                case 'search':
                    $query = $_GET['q'] ?? '';
                    if (strlen($query) < 2) {
                        echo json_encode([
                            'success' => true,
                            'data' => ['products' => []]
                        ]);
                        break;
                    }
                    
                    $sql = "SELECT p.*, c.name as category_name
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE (p.name LIKE ? OR p.sku LIKE ? OR p.barcode LIKE ?)
                    AND p.is_active = 1
                    LIMIT 10";
                    
                    $searchTerm = '%' . $query . '%';
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
                    $products = $stmt->fetchAll();
                    
                    echo json_encode([
                        'success' => true,
                        'data' => ['products' => $products]
                    ]);
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Invalid method'
                    ]);
            }
            break;
            
        case 'transactions':
            // Debug logging
            error_log("📊 Transactions API called with method: " . $method);
            error_log("📥 Limit parameter: " . ($_GET['limit'] ?? 'not set'));
            
            try {
                switch ($method) {
                    case 'recent':
                        $limit = $_GET['limit'] ?? 10;
                        $limit = min(max((int)$limit, 1), 100); // Limit between 1-100
                        
                        error_log("✅ Fetching $limit recent transactions");
                        
                        $sql = "SELECT 
                            t.*,
                            c.name as customer_name,
                            c.phone as customer_phone,
                            u.full_name as cashier_name,
                            COALESCE(
                                (SELECT SUM(quantity) FROM transaction_items WHERE transaction_id = t.id), 
                                0
                            ) as items_count
                        FROM transactions t
                        LEFT JOIN customers c ON t.customer_id = c.id
                        LEFT JOIN users u ON t.user_id = u.id
                        WHERE t.status = 'completed'
                        ORDER BY t.created_at DESC
                        LIMIT ?";
                        
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$limit]);
                        $transactions = $stmt->fetchAll();
                        
                        // Debug: log items count for each transaction
                        foreach ($transactions as $transaction) {
                            error_log("📊 Transaction {$transaction['transaction_number']}: {$transaction['items_count']} items");
                        }
                        
                        error_log("✅ Found " . count($transactions) . " transactions");
                        
                        echo json_encode([
                            'success' => true,
                            'data' => ['transactions' => $transactions]
                        ]);
                        break;
                    
                case 'mineRecent':
                    // Get current user's transactions
                    $userId = $_SESSION['user_id'];
                    $limit = $_GET['limit'] ?? 10;
                    
                    $sql = "SELECT 
                        t.*,
                        c.name as customer_name,
                        (SELECT COUNT(*) FROM transaction_items WHERE transaction_id = t.id) as items_count
                    FROM transactions t
                    LEFT JOIN customers c ON t.customer_id = c.id
                    WHERE t.user_id = ?
                    AND t.status = 'completed'
                    ORDER BY t.created_at DESC
                    LIMIT ?";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$userId, $limit]);
                    $transactions = $stmt->fetchAll();
                    
                    echo json_encode([
                        'success' => true,
                        'data' => ['transactions' => $transactions]
                    ]);
                    break;
                    
                default:
                    error_log("❌ Invalid transactions method: " . $method);
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Invalid method: ' . $method
                    ]);
                }
            } catch (Exception $e) {
                error_log("❌ Transactions error: " . $e->getMessage());
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ]);
            }
            break;
            
        case 'stock':
            switch ($method) {
                case 'movements':
                    $type = $_GET['type'] ?? 'all';
                    $today = isset($_GET['today']) && $_GET['today'] === 'true';
                    
                    $sql = "SELECT 
                        sm.*,
                        p.name as product_name,
                        p.sku as product_sku,
                        u.full_name as user_name
                    FROM stock_movements sm
                    LEFT JOIN products p ON sm.product_id = p.id
                    LEFT JOIN users u ON sm.user_id = u.id
                    WHERE 1=1";
                    
                    $params = [];
                    
                    if ($type !== 'all') {
                        $sql .= " AND sm.movement_type = ?";
                        $params[] = $type;
                    }
                    
                    if ($today) {
                        $sql .= " AND DATE(sm.created_at) = CURDATE()";
                    }
                    
                    $sql .= " ORDER BY sm.created_at DESC LIMIT 50";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $movements = $stmt->fetchAll();
                    
                    echo json_encode([
                        'success' => true,
                        'data' => ['movements' => $movements]
                    ]);
                    break;
                    
                case 'adjust':
                    // Handle stock adjustment
                    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                        http_response_code(405);
                        echo json_encode([
                            'success' => false,
                            'message' => 'Method not allowed'
                        ]);
                        break;
                    }
                    
                    $input = json_decode(file_get_contents('php://input'), true);
                    $productId = $input['product_id'] ?? null;
                    $adjustmentType = $input['adjustment_type'] ?? 'adjustment';
                    $quantity = $input['quantity'] ?? 0;
                    $notes = $input['notes'] ?? '';
                    
                    if (!$productId || !$quantity) {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'message' => 'Product ID and quantity are required'
                        ]);
                        break;
                    }
                    
                    // Get current stock
                    $stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = ?");
                    $stmt->execute([$productId]);
                    $currentStock = $stmt->fetchColumn();
                    
                    if ($currentStock === false) {
                        http_response_code(404);
                        echo json_encode([
                            'success' => false,
                            'message' => 'Product not found'
                        ]);
                        break;
                    }
                    
                    // Calculate new stock (subtract for most types, add for 'found')
                    $adjustment = ($adjustmentType === 'found') ? $quantity : -$quantity;
                    $newStock = $currentStock + $adjustment;
                    
                    if ($newStock < 0) {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'message' => 'Insufficient stock for adjustment'
                        ]);
                        break;
                    }
                    
                    // Update stock
                    $stmt = $pdo->prepare("UPDATE products SET stock_quantity = ? WHERE id = ?");
                    $stmt->execute([$newStock, $productId]);
                    
                    // Record movement
                    $stmt = $pdo->prepare("INSERT INTO stock_movements 
                        (product_id, movement_type, quantity, notes, user_id) 
                        VALUES (?, 'adjustment', ?, ?, ?)");
                    $stmt->execute([
                        $productId, 
                        abs($adjustment), 
                        $notes . " (Type: {$adjustmentType})", 
                        $_SESSION['user_id']
                    ]);
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Stock adjusted successfully',
                        'data' => [
                            'old_stock' => $currentStock,
                            'new_stock' => $newStock
                        ]
                    ]);
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Invalid method'
                    ]);
            }
            break;
            
        case 'pos':
            try {
                require_once __DIR__ . '/../app/controllers/PosController.php';
                $controller = new PosController($pdo);
                
                switch ($method) {
                    case 'getProducts':
                        $controller->getProducts();
                        break;
                        
                    case 'getCategories':
                        $controller->getCategories();
                        break;
                        
                    case 'getByBarcode':
                        $controller->getByBarcode();
                        break;
                        
                    case 'searchCustomers':
                        $controller->searchCustomers();
                        break;
                        
                    case 'createTransaction':
                        error_log("🎯 API: Calling createTransaction()");
                        $controller->createTransaction();
                        error_log("✅ API: createTransaction() completed");
                        break;
                        
                    case 'holdTransaction':
                        $controller->holdTransaction();
                        break;
                        
                    case 'getHeldTransactions':
                        $controller->getHeldTransactions();
                        break;
                        
                    case 'resumeTransaction':
                        $controller->resumeTransaction();
                        break;
                    
                case 'getStats':
                    $controller->getStats();
                    break;
                    
                case 'getRecentTransactions':
                    $controller->getRecentTransactions();
                    break;
                    
                case 'cancelHeldTransaction':
                    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                        http_response_code(405);
                        echo json_encode([
                            'success' => false,
                            'message' => 'Method not allowed'
                        ]);
                        break;
                    }
                    
                    $input = json_decode(file_get_contents('php://input'), true);
                    $holdId = $input['hold_id'] ?? null;
                    
                    if (!$holdId) {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'message' => 'Hold ID required'
                        ]);
                        break;
                    }
                    
                    $stmt = $pdo->prepare("DELETE FROM hold_transactions WHERE id = ? AND user_id = ?");
                    $stmt->execute([$holdId, $_SESSION['user_id']]);
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Held transaction cancelled'
                    ]);
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Invalid method: ' . $method
                    ]);
            }
            } catch (Throwable $e) {
                error_log("❌ POS API Error: " . $e->getMessage());
                error_log("File: " . $e->getFile() . ":" . $e->getLine());
                error_log("Trace: " . $e->getTraceAsString());
                
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'POS Error: ' . $e->getMessage(),
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => explode("\n", $e->getTraceAsString()),
                    'type' => get_class($e)
                ], JSON_PRETTY_PRINT);
            }
            break;
            
        case 'notifications':
            switch ($method) {
                case 'count':
                    $userId = $_SESSION['user_id'];
                    $sql = "SELECT COUNT(*) as count 
                        FROM notifications 
                        WHERE (user_id = ? OR user_id IS NULL) 
                        AND is_read = 0";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$userId]);
                    $count = $stmt->fetchColumn();
                    
                    echo json_encode([
                        'success' => true,
                        'data' => ['count' => (int)$count]
                    ]);
                    break;
                    
                case 'list':
                    $userId = $_SESSION['user_id'];
                    $sql = "SELECT * FROM notifications 
                        WHERE (user_id = ? OR user_id IS NULL)
                        ORDER BY created_at DESC
                        LIMIT 20";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$userId]);
                    $notifications = $stmt->fetchAll();
                    
                    // Add time_ago
                    foreach ($notifications as &$notif) {
                        $notif['time_ago'] = timeAgo($notif['created_at']);
                    }
                    
                    echo json_encode([
                        'success' => true,
                        'data' => ['notifications' => $notifications]
                    ]);
                    break;
                    
                case 'markRead':
                    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                        http_response_code(405);
                        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
                        break;
                    }
                    
                    $input = json_decode(file_get_contents('php://input'), true);
                    $notifId = $input['notification_id'] ?? null;
                    
                    if ($notifId) {
                        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
                        $stmt->execute([$notifId]);
                    }
                    
                    echo json_encode(['success' => true]);
                    break;
                    
                case 'markAllRead':
                    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                        http_response_code(405);
                        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
                        break;
                    }
                    
                    $userId = $_SESSION['user_id'];
                    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 
                        WHERE (user_id = ? OR user_id IS NULL) AND is_read = 0");
                    $stmt->execute([$userId]);
                    
                    echo json_encode(['success' => true]);
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Invalid method']);
            }
            break;
            
        case 'settings':
            require_once __DIR__ . '/../app/controllers/SettingsController.php';
            $controller = new SettingsController($pdo);
            
            switch ($method) {
                case 'get':
                    if (isset($_GET['key'])) {
                        $controller->getSetting();
                    } else {
                        $controller->getSettings();
                    }
                    break;
                    
                case 'update':
                    $controller->updateSettings();
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Invalid method']);
            }
            break;
            
        case 'users':
            require_once __DIR__ . '/../app/controllers/AuthController.php';
            $controller = new AuthController($pdo);
            
            // Only admin can manage users
            if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                break;
            }
            
            switch ($method) {
                case 'list':
                    $controller->listUsers();
                    break;
                    
                case 'get':
                    $controller->getUser();
                    break;
                    
                case 'create':
                    $controller->createUser();
                    break;
                    
                case 'update':
                    $controller->updateUser();
                    break;
                    
                case 'delete':
                    $controller->deleteUser();
                    break;
                    
                case 'stats':
                    $controller->getUserStats();
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Invalid method']);
            }
            break;
            
        case 'reports':
            require_once __DIR__ . '/../app/controllers/ReportController.php';
            $controller = new ReportController($pdo);
            
            switch ($method) {
                case 'salesStats':
                    $controller->getSalesStats();
                    break;
                    
                case 'topProducts':
                    $controller->getTopProducts();
                    break;
                    
                case 'salesTrend':
                    $controller->getSalesTrend();
                    break;
                    
                case 'categoryPerformance':
                    $controller->getCategoryPerformance();
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Invalid method']);
            }
            break;
        
        case 'export':
            // Handle export requests
            require_once __DIR__ . '/../app/controllers/ReportController.php';
            
            $type = $_GET['type'] ?? 'sales';
            $format = $_GET['format'] ?? 'excel';
            $dateFrom = $_GET['from'] ?? null;
            $dateTo = $_GET['to'] ?? null;
            
            // Clean old exports (older than 24 hours)
            require_once __DIR__ . '/../app/helpers/ExportHelper.php';
            ExportHelper::cleanOldExports(24);
            
            $reportController = new ReportController($pdo);
            
            try {
                $exportResult = null;
                
                switch ($type) {
                    case 'sales':
                        $exportResult = $reportController->exportSales($format, $dateFrom, $dateTo);
                        break;
                        
                    case 'inventory':
                    case 'stock':
                        $exportResult = $reportController->exportInventory($format);
                        break;
                        
                    case 'customers':
                        $exportResult = $reportController->exportCustomers($format);
                        break;
                        
                    case 'products':
                    case 'product_performance':
                        $exportResult = $reportController->exportProductPerformance($format, $dateFrom, $dateTo);
                        break;
                        
                    default:
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Invalid export type: ' . $type]);
                        exit;
                }
                
                // Handle return value (can be array or string for backward compatibility)
                if (is_array($exportResult)) {
                    $filepath = $exportResult['filepath'];
                    $filename = $exportResult['filename'];
                } else {
                    // Backward compatibility: if string returned, use it as filepath
                    $filepath = $exportResult;
                    $filename = basename($filepath);
                }
                
                // Download file
                if ($filepath && file_exists($filepath)) {
                    // Get actual file extension from filepath
                    $actualExtension = pathinfo($filepath, PATHINFO_EXTENSION);
                    
                    // Determine content type based on actual file extension
                    $contentTypes = [
                        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'xls' => 'application/vnd.ms-excel',
                        'pdf' => 'application/pdf',
                        'csv' => 'text/csv; charset=UTF-8',
                        'html' => 'text/html; charset=UTF-8'
                    ];
                    $contentType = $contentTypes[$actualExtension] ?? 'application/octet-stream';
                    
                    // For CSV, add BOM for Excel compatibility
                    if ($actualExtension === 'csv') {
                        // Read file content and prepend BOM
                        $content = file_get_contents($filepath);
                        if (substr($content, 0, 3) !== "\xEF\xBB\xBF") {
                            $content = "\xEF\xBB\xBF" . $content;
                            file_put_contents($filepath, $content);
                        }
                    }
                    
                    // Clear output buffer
                    if (ob_get_level()) {
                        ob_end_clean();
                    }
                    
                    // Set headers for download with correct filename
                    header('Content-Type: ' . $contentType);
                    header('Content-Disposition: attachment; filename="' . $filename . '"');
                    header('Content-Length: ' . filesize($filepath));
                    header('Cache-Control: must-revalidate');
                    header('Pragma: public');
                    
                    // For CSV, ensure UTF-8 encoding
                    if ($actualExtension === 'csv') {
                        header('Content-Encoding: UTF-8');
                    }
                    
                    // Output file
                    readfile($filepath);
                    
                    // Delete file after download
                    unlink($filepath);
                    exit;
                } else {
                    http_response_code(500);
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Failed to generate export file',
                        'filepath' => $filepath,
                        'export_result' => $exportResult
                    ]);
                }
                
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Export error: ' . $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>

