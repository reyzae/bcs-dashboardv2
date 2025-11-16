<?php
/**
 * API Router
 * Routes API requests to appropriate controllers
 */

// Load bootstrap first
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/helpers/SecurityMiddleware.php';

// Clean any previous output to avoid HTML breaking JSON
while (ob_get_level() > 0) { ob_end_clean(); }
ob_start();

// Ensure API responses are JSON-only (avoid HTML errors breaking JSON)
// Force disabling HTML error output even if APP_DEBUG is true
ini_set('display_errors', '0');
ini_set('html_errors', '0');

// Set JSON response header
header('Content-Type: application/json');
// Disable caching for API responses
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
// Security headers for API responses
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: frame-ancestors 'self'; base-uri 'self'");
header('Vary: Origin');

// CORS Configuration - Production Ready
$isProduction = ($_ENV['APP_ENV'] ?? 'development') === 'production';
if ($isProduction) {
    // Production: Restrict CORS to your domain only
    $allowedOrigin = $_ENV['APP_URL'] ?? '';
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin && parse_url($origin, PHP_URL_HOST) === parse_url($allowedOrigin, PHP_URL_HOST)) {
        header('Access-Control-Allow-Origin: ' . $origin);
    }
    // Validate origin strictly in production
    SecurityMiddleware::validateOrigin();
} else {
    // Development: Echo back requesting origin to allow credentials
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if (!empty($origin)) {
        header('Access-Control-Allow-Origin: ' . $origin);
    } else {
        // Same-origin requests without Origin header will still work
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if (!empty($host)) {
            header('Access-Control-Allow-Origin: ' . $scheme . $host);
        }
    }
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
// Enumerate allowed headers explicitly for better preflight behavior
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, X-Requested-With, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Apply security middleware
// Production: 60 requests/minute, Development: 500 requests/minute
$rateLimit = $isProduction ? 60 : 500;
SecurityMiddleware::rateLimit($rateLimit, 60);

// Enforce CSRF for mutating requests in production
if ($isProduction) {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
        SecurityMiddleware::checkCsrfToken();
    }
}

// Clean up old rate limit cache files periodically (1 hour)
if (rand(1, 100) === 1) {
    SecurityMiddleware::cleanupCache(3600);
}

// Initialize database connection
try {
    $database = new Database();
    $pdo = $database->getConnection();
} catch (Exception $e) {
    http_response_code(500);
    $isDebug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
    echo json_encode([
        'success' => false, 
        'error' => 'Database connection failed',
        'message' => $isDebug ? $e->getMessage() : 'Database connection failed. Please contact administrator.',
        'debug_info' => $isDebug ? [
            'error' => $e->getMessage(),
            'config' => [
                'host' => $_ENV['DB_HOST'] ?? 'localhost',
                'dbname' => $_ENV['DB_NAME'] ?? 'bytebalok_dashboard',
                'user' => $_ENV['DB_USER'] ?? 'root'
            ]
        ] : null
    ]);
    exit;
}

// Get the controller and action from query string
$controller = $_GET['controller'] ?? '';
$action = $_GET['action'] ?? '';

// Map of valid controllers
$validControllers = [
    'auth' => __DIR__ . '/../app/controllers/AuthController.php',
    'dashboard' => __DIR__ . '/../app/controllers/DashboardController.php',
    'product' => __DIR__ . '/../app/controllers/ProductController.php',
    'customer' => __DIR__ . '/../app/controllers/CustomerController.php',
    'transaction' => __DIR__ . '/../app/controllers/TransactionController.php',
    'category' => __DIR__ . '/../app/controllers/CategoryController.php',
    'order' => __DIR__ . '/../app/controllers/OrderController.php',
    'payment' => __DIR__ . '/../app/controllers/PaymentController.php',
    'pos' => __DIR__ . '/../app/controllers/PosController.php',
    'settings' => __DIR__ . '/../app/controllers/SettingsController.php',
];

// Check if controller exists
if (!isset($validControllers[$controller])) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Controller not found']);
    exit;
}

// Load and execute controller
$controllerFile = $validControllers[$controller];

if (!file_exists($controllerFile)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Controller file not found']);
    exit;
}

// Set action in GET for controller to process
$_GET['action'] = $action;

// Check if action is provided
if (empty($action)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Action parameter is required']);
    exit;
}

// Include the controller file with error handling
try {
    require_once $controllerFile;
    
    // Determine controller class name from file path
    $controllerClassName = ucfirst($controller) . 'Controller';
    
    // Check if class exists
    if (!class_exists($controllerClassName)) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Controller class not found',
            'message' => "Class '{$controllerClassName}' does not exist in {$controllerFile}"
        ]);
        exit;
    }
    
    // Instantiate controller with database connection
    $controllerInstance = new $controllerClassName($pdo);
    
    // Check if action method exists
    if (!method_exists($controllerInstance, $action)) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Action not found',
            'message' => "Method '{$action}' does not exist in {$controllerClassName}"
        ]);
        exit;
    }
    
    // Call the action method
    $controllerInstance->$action();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Controller execution failed',
        'message' => ($_ENV['APP_DEBUG'] ?? 'false') === 'true' ? $e->getMessage() : 'Internal server error',
        'trace' => ($_ENV['APP_DEBUG'] ?? 'false') === 'true' ? $e->getTraceAsString() : null
    ]);
    exit;
}

