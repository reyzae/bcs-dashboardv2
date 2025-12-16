<?php
/**
 * Bootstrap file for Bytebalok System
 * Loads environment variables and basic configuration
 */

// Load environment variables
if (file_exists(__DIR__ . '/../config.env')) {
    $lines = file(__DIR__ . '/../config.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Set default timezone
date_default_timezone_set('Asia/Jakarta');

// Harden session cookies before starting session
// Use secure/httponly/samesite where possible
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_ENV['APP_URL']) && stripos($_ENV['APP_URL'], 'https://') === 0);

ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', $isHttps ? '1' : '0');

// SECURITY: Gunakan Strict untuk semua environment (lebih aman)
ini_set('session.cookie_samesite', 'Strict');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// SECURITY: Session regeneration untuk mencegah session fixation
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
    $_SESSION['created_at'] = time();
}

// SECURITY: Session timeout (30 menit tidak aktif)
$sessionTimeout = 1800; // 30 menit dalam detik
if (isset($_SESSION['last_activity'])) {
    $inactive = time() - $_SESSION['last_activity'];
    if ($inactive > $sessionTimeout) {
        // Session expired, destroy it
        session_unset();
        session_destroy();
        session_start();
    }
}
$_SESSION['last_activity'] = time();

// Ensure CSRF token exists
require_once __DIR__ . '/../app/helpers/SecurityMiddleware.php';
if (class_exists('SecurityMiddleware')) {
    SecurityMiddleware::generateCsrfToken();
    // Expose CSRF token via cookie for client-side JS fallback
    if (isset($_SESSION['csrf_token'])) {
        $cookieParams = [
            'expires' => time() + 7200,
            'path' => '/',
            'secure' => $isHttps,
            'httponly' => false,
            'samesite' => 'Strict'
        ];
        // setcookie supports array options on PHP 7.3+
        @setcookie('csrf_token', $_SESSION['csrf_token'], $cookieParams);
    }
}

// Error reporting for development
if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Security headers (apply to HTML pages via bootstrap)
$isProduction = ($_ENV['APP_ENV'] ?? 'development') === 'production';
// Content Security Policy: allow required CDNs used by dashboard
$csp = "default-src 'self'; "
    . "img-src 'self' data: blob:; "
    . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; "
    . "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://static.cloudflareinsights.com; "
    . "font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; "
    . "connect-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://static.cloudflareinsights.com; "
    . "frame-ancestors 'self'; "
    . "base-uri 'self'";
header('Content-Security-Policy: ' . $csp);
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Content-Type-Options: nosniff');
if ($isHttps && $isProduction) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
}

// Auto-login via Remember Me cookie if session missing
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {
    $token = $_COOKIE['remember_me'];
    $baseDir = __DIR__ . '/../storage/sessions/remember_tokens';
    $file = $baseDir . '/' . basename($token) . '.json';
    if (is_file($file)) {
        $data = json_decode(@file_get_contents($file), true);
        if (is_array($data) && isset($data['user_id'], $data['expiry']) && $data['expiry'] > time()) {
            // Load user from database to restore full session
            require_once __DIR__ . '/../app/config/database.php';
            require_once __DIR__ . '/../app/models/User.php';
            try {
                $database = new Database();
                $pdo = $database->getConnection();
                $userModel = new User($pdo);
                $user = $userModel->find($data['user_id']);
                if ($user && (!isset($user['is_active']) || $user['is_active'])) {
                    if (session_status() === PHP_SESSION_NONE) {
                        session_start();
                    }
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_role'] = $user['role'] ?? null;
                    $_SESSION['username'] = $user['username'] ?? null;
                    $_SESSION['full_name'] = $user['full_name'] ?? null;
                    $_SESSION['user_name'] = $user['full_name'] ?? null;
                    $_SESSION['user_email'] = $user['email'] ?? null;
                    $_SESSION['last_activity'] = time();
                }
            } catch (Exception $e) {
                // If database fails, ignore remember-me restore
            }
        } else {
            // Expired token: clean up
            @unlink($file);
        }
    }
}

// Periodic cleanup of expired remember-me tokens (lightweight)
if (class_exists('SecurityMiddleware') && rand(1, 100) === 1) {
    SecurityMiddleware::cleanupRememberTokens();
}
