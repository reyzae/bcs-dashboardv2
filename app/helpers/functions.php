<?php
/**
 * Bytebalok Helper Functions
 * Common utility functions used across the application
 */

// ========================================
// ROLE-BASED ACCESS CONTROL
// ========================================

/**
 * Check if user has required role
 * @param string|array $required_roles Single role or array of roles
 * @return bool
 */
function hasRole($required_roles) {
    if (!isset($_SESSION['user_role'])) {
        return false;
    }
    
    $user_role = $_SESSION['user_role'];
    
    if (is_array($required_roles)) {
        return in_array($user_role, $required_roles);
    }
    
    return $user_role === $required_roles;
}

/**
 * Require specific role or redirect
 * @param string|array $required_roles
 * @param string $redirect_url
 */
function requireRole($required_roles, $redirect_url = '../login.php') {
    if (!hasRole($required_roles)) {
        header("Location: $redirect_url");
        exit();
    }
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return hasRole('admin');
}

/**
 * Check if user is manager or admin
 */
function isManager() {
    return hasRole(['admin', 'manager']);
}

/**
 * Check if user is staff (includes all roles)
 */
function isStaff() {
    return hasRole(['admin', 'manager', 'staff', 'cashier']);
}

/**
 * Check if user is cashier
 */
function isCashier() {
    return hasRole('cashier');
}

/**
 * Get menu items based on user role
 */
function getMenuByRole() {
    $role = $_SESSION['user_role'] ?? 'staff';
    
    $menus = [
        'admin' => [
            ['icon' => 'fa-home', 'label' => 'Dashboard', 'url' => 'index.php'],
            ['icon' => 'fa-cash-register', 'label' => 'Point of Sale', 'url' => 'pos.php'],
            ['icon' => 'fa-receipt', 'label' => 'Orders', 'url' => 'orders.php'],
            ['icon' => 'fa-box', 'label' => 'Products', 'url' => 'products.php'],
            ['icon' => 'fa-users', 'label' => 'Customers', 'url' => 'customers.php'],
            ['icon' => 'fa-receipt', 'label' => 'Transactions', 'url' => 'transactions.php'],
            ['icon' => 'fa-chart-bar', 'label' => 'Reports', 'url' => 'reports.php'],
            ['icon' => 'fa-user-shield', 'label' => 'User Management', 'url' => 'users.php'],
            ['icon' => 'fa-cog', 'label' => 'Settings', 'url' => 'settings.php'],
        ],
        'manager' => [
            ['icon' => 'fa-home', 'label' => 'Dashboard', 'url' => 'index.php'],
            ['icon' => 'fa-cash-register', 'label' => 'Point of Sale', 'url' => 'pos.php'],
            ['icon' => 'fa-receipt', 'label' => 'Orders', 'url' => 'orders.php'],
            ['icon' => 'fa-box', 'label' => 'Products', 'url' => 'products.php'],
            ['icon' => 'fa-users', 'label' => 'Customers', 'url' => 'customers.php'],
            ['icon' => 'fa-receipt', 'label' => 'Transactions', 'url' => 'transactions.php'],
            ['icon' => 'fa-chart-bar', 'label' => 'Reports', 'url' => 'reports.php'],
        ],
        'staff' => [
            ['icon' => 'fa-home', 'label' => 'Dashboard', 'url' => 'index.php'],
            ['icon' => 'fa-box', 'label' => 'Products', 'url' => 'products.php'],
        ],
        'cashier' => [
            ['icon' => 'fa-home', 'label' => 'Dashboard', 'url' => 'index.php'],
            ['icon' => 'fa-cash-register', 'label' => 'Point of Sale', 'url' => 'pos.php'],
            ['icon' => 'fa-receipt', 'label' => 'Transactions', 'url' => 'transactions.php'],
        ],
    ];
    
    return $menus[$role] ?? $menus['staff'];
}

/**
 * Check if current user can access a menu item
 */
function canAccessMenu($menu_url) {
    $role = $_SESSION['user_role'] ?? 'staff';
    $menu = getMenuByRole();
    
    foreach ($menu as $item) {
        if ($item['url'] === $menu_url) {
            return true;
        }
    }
    
    return false;
}

// ========================================
// FORMATTING FUNCTIONS
// ========================================

/**
 * Format currency
 * @param float $amount
 * @param string $currency
 * @return string
 */
function formatCurrency($amount, $currency = 'IDR') {
    if ($currency === 'IDR') {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
    return number_format($amount, 2);
}

/**
 * Format date
 * @param string $date
 * @param string $format
 * @return string
 */
function formatDate($date, $format = 'd M Y') {
    if (empty($date)) return '-';
    return date($format, strtotime($date));
}

/**
 * Format datetime
 * @param string $datetime
 * @param string $format
 * @return string
 */
function formatDateTime($datetime, $format = 'd M Y H:i') {
    if (empty($datetime)) return '-';
    return date($format, strtotime($datetime));
}

/**
 * Format time ago
 * @param string $datetime
 * @return string
 */
function timeAgo($datetime) {
    if (empty($datetime)) return '-';
    
    $timestamp = strtotime($datetime);
    $difference = time() - $timestamp;
    
    if ($difference < 60) {
        return 'just now';
    } elseif ($difference < 3600) {
        $minutes = floor($difference / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($difference < 86400) {
        $hours = floor($difference / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($difference < 604800) {
        $days = floor($difference / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return formatDateTime($datetime);
    }
}

/**
 * Format number
 */
function formatNumber($number, $decimals = 0) {
    return number_format($number, $decimals, ',', '.');
}

/**
 * Format percentage
 */
function formatPercentage($number, $decimals = 1) {
    return number_format($number, $decimals) . '%';
}

// ========================================
// STATUS & BADGE FUNCTIONS
// ========================================

/**
 * Get status badge HTML
 * @param string $status
 * @return string
 */
function getStatusBadge($status) {
    $badges = [
        'completed' => '<span class="badge badge-success">Completed</span>',
        'pending' => '<span class="badge badge-warning">Pending</span>',
        'cancelled' => '<span class="badge badge-danger">Cancelled</span>',
        'refunded' => '<span class="badge badge-info">Refunded</span>',
        'active' => '<span class="badge badge-success">Active</span>',
        'inactive' => '<span class="badge badge-secondary">Inactive</span>',
    ];
    
    return $badges[strtolower($status)] ?? '<span class="badge badge-secondary">' . ucfirst($status) . '</span>';
}

/**
 * Get payment method badge
 */
function getPaymentBadge($method) {
    $badges = [
        'cash' => '<span class="badge badge-success"><i class="fas fa-money-bill"></i> Cash</span>',
        'card' => '<span class="badge badge-primary"><i class="fas fa-credit-card"></i> Card</span>',
        'qris' => '<span class="badge badge-info"><i class="fas fa-qrcode"></i> QRIS</span>',
        'transfer' => '<span class="badge badge-warning"><i class="fas fa-exchange-alt"></i> Transfer</span>',
    ];
    
    return $badges[strtolower($method)] ?? '<span class="badge badge-secondary">' . ucfirst($method) . '</span>';
}

/**
 * Get stock status badge
 */
function getStockStatusBadge($stock_qty, $min_level) {
    if ($stock_qty <= 0) {
        return '<span class="badge badge-danger"><i class="fas fa-times-circle"></i> Out of Stock</span>';
    } elseif ($stock_qty <= $min_level) {
        return '<span class="badge badge-warning"><i class="fas fa-exclamation-triangle"></i> Low Stock</span>';
    } else {
        return '<span class="badge badge-success"><i class="fas fa-check-circle"></i> In Stock</span>';
    }
}

/**
 * Get role badge
 */
function getRoleBadge($role) {
    $badges = [
        'admin' => '<span class="badge badge-danger"><i class="fas fa-user-shield"></i> Admin</span>',
        'manager' => '<span class="badge badge-primary"><i class="fas fa-user-tie"></i> Manager</span>',
        'staff' => '<span class="badge badge-info"><i class="fas fa-user"></i> Staff</span>',
        'cashier' => '<span class="badge badge-success"><i class="fas fa-cash-register"></i> Cashier</span>',
    ];
    
    return $badges[strtolower($role)] ?? '<span class="badge badge-secondary">' . ucfirst($role) . '</span>';
}

// ========================================
// VALIDATION FUNCTIONS
// ========================================

/**
 * Sanitize input
 */
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone (Indonesian format)
 */
function isValidPhone($phone) {
    // Remove all non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    // Check if starts with 08 or 628 and has 10-13 digits
    return preg_match('/^(08|628)[0-9]{8,11}$/', $phone);
}

/**
 * Validate barcode
 */
function isValidBarcode($barcode) {
    // Alphanumeric, 6-20 characters
    return preg_match('/^[A-Za-z0-9]{6,20}$/', $barcode);
}

/**
 * Validate SKU
 */
function isValidSKU($sku) {
    // Alphanumeric with dash/underscore, 3-50 characters
    return preg_match('/^[A-Za-z0-9_-]{3,50}$/', $sku);
}

// ========================================
// SESSION FUNCTIONS
// ========================================

/**
 * Set flash message
 */
function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * Get and clear flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    // Basic check
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        return false;
    }

    // Session timeout enforcement
    $defaultTimeoutMinutes = 30;
    $timeoutMinutes = isset($_ENV['SESSION_TIMEOUT']) && is_numeric($_ENV['SESSION_TIMEOUT'])
        ? intval($_ENV['SESSION_TIMEOUT'])
        : $defaultTimeoutMinutes;

    // If settings previously loaded into session, prefer that
    if (isset($_SESSION['session_timeout']) && is_numeric($_SESSION['session_timeout'])) {
        $timeoutMinutes = max(1, intval($_SESSION['session_timeout']));
    }

    $now = time();
    $lastActivity = $_SESSION['last_activity'] ?? $now;

    if (($now - $lastActivity) > ($timeoutMinutes * 60)) {
        // Session expired
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
        return false;
    }

    // Update last activity timestamp for active session
    $_SESSION['last_activity'] = $now;
    return true;
}

/**
 * Require authentication
 */
function requireAuth($redirect_url = '../login.php') {
    if (!isLoggedIn()) {
        header("Location: $redirect_url");
        exit();
    }
}

/**
 * Get current user info
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'full_name' => $_SESSION['full_name'] ?? null,
        'role' => $_SESSION['user_role'] ?? null,
        'email' => $_SESSION['user_email'] ?? null,
    ];
}

// ========================================
// UTILITY FUNCTIONS
// ========================================

/**
 * Generate unique transaction number
 */
function generateTransactionNumber() {
    // Format: TXN-YYYYMMDD-XXXXX (e.g., TXN-20250126-00001)
    $date = date('Ymd');
    $random = str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
    return "TXN-{$date}-{$random}";
}

/**
 * Generate unique customer code
 */
function generateCustomerCode() {
    // Format: CUST-XXXXX (e.g., CUST-00001)
    $random = str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
    return "CUST-{$random}";
}

/**
 * Generate barcode
 */
function generateBarcode($prefix = '') {
    // Format: PREFIX-TIMESTAMP-RANDOM
    $timestamp = time();
    $random = rand(1000, 9999);
    return $prefix ? "{$prefix}-{$timestamp}{$random}" : "{$timestamp}{$random}";
}

/**
 * Calculate discount
 */
function calculateDiscount($amount, $discount_percentage) {
    return $amount * ($discount_percentage / 100);
}

/**
 * Calculate tax
 */
function calculateTax($amount, $tax_percentage = 10) {
    return $amount * ($tax_percentage / 100);
}

/**
 * Calculate total with discount and tax
 */
function calculateTotal($subtotal, $discount_percentage = 0, $tax_percentage = 10) {
    $discount = calculateDiscount($subtotal, $discount_percentage);
    $taxable_amount = $subtotal - $discount;
    $tax = calculateTax($taxable_amount, $tax_percentage);
    
    return [
        'subtotal' => $subtotal,
        'discount_amount' => $discount,
        'discount_percentage' => $discount_percentage,
        'taxable_amount' => $taxable_amount,
        'tax_amount' => $tax,
        'tax_percentage' => $tax_percentage,
        'total' => $taxable_amount + $tax
    ];
}

/**
 * Get file extension
 */
function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Check if file is image
 */
function isImageFile($filename) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    return in_array(getFileExtension($filename), $allowed);
}

/**
 * Generate random string
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string = '';
    for ($i = 0; $i < $length; $i++) {
        $string .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $string;
}

/**
 * Redirect with delay
 */
function redirectTo($url, $delay = 0) {
    if ($delay > 0) {
        header("refresh:$delay;url=$url");
    } else {
        header("Location: $url");
    }
    exit();
}

/**
 * JSON response
 */
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

/**
 * Debug helper
 */
function dd($data) {
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    die();
}

/**
 * Log activity (simple file-based logging)
 */
function logActivity($message, $level = 'INFO') {
    $log_dir = __DIR__ . '/../../storage/logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $log_file = $log_dir . '/activity-' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $user = getCurrentUser();
    $username = $user ? $user['username'] : 'guest';
    
    $log_message = "[$timestamp] [$level] [$username] $message" . PHP_EOL;
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

/**
 * Get dashboard welcome message based on time
 */
function getWelcomeMessage() {
    $hour = date('H');
    $name = $_SESSION['full_name'] ?? 'User';
    
    if ($hour < 12) {
        return "Good Morning, $name!";
    } elseif ($hour < 18) {
        return "Good Afternoon, $name!";
    } else {
        return "Good Evening, $name!";
    }
}

/**
 * Get role-specific dashboard title
 */
function getDashboardTitle() {
    $role = $_SESSION['user_role'] ?? 'staff';
    
    $titles = [
        'admin' => 'Admin Dashboard',
        'manager' => 'Manager Dashboard',
        'staff' => 'Stock Management Dashboard',
        'cashier' => 'Cashier Dashboard'
    ];
    
    return $titles[$role] ?? 'Dashboard';
}

/**
 * Get avatar initials
 */
function getAvatarInitials($name) {
    $words = explode(' ', $name);
    if (count($words) >= 2) {
        return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    }
    return strtoupper(substr($name, 0, 2));
}

/**
 * Truncate text
 */
function truncate($text, $length = 50, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . $suffix;
}

/**
 * Get pagination data
 */
function getPagination($total_items, $per_page = 10, $current_page = 1) {
    $total_pages = ceil($total_items / $per_page);
    $current_page = max(1, min($total_pages, $current_page));
    $offset = ($current_page - 1) * $per_page;
    
    return [
        'total_items' => $total_items,
        'per_page' => $per_page,
        'current_page' => $current_page,
        'total_pages' => $total_pages,
        'offset' => $offset,
        'has_prev' => $current_page > 1,
        'has_next' => $current_page < $total_pages,
        'prev_page' => $current_page - 1,
        'next_page' => $current_page + 1,
    ];
}

// ========================================
// KEYBOARD SHORTCUTS HELPER
// ========================================

/**
 * Get keyboard shortcuts for current role
 */
function getKeyboardShortcuts() {
    $role = $_SESSION['user_role'] ?? 'staff';
    
    if ($role === 'cashier' || $role === 'admin') {
        return [
            'F2' => 'Search Product',
            'F3' => 'Search Customer',
            'F4' => 'Clear Cart',
            'F8' => 'Hold Transaction',
            'F9' => 'Cash Payment',
            'F12' => 'Process Payment',
            'ESC' => 'Close Modal'
        ];
    }
    
    return [];
}

?>
