<?php

/**
 * Security Middleware
 * Provides CSRF protection, rate limiting, and other security features
 */
class SecurityMiddleware {
    
    /**
     * Generate CSRF token
     */
    public static function generateCsrfToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate CSRF token
     */
    public static function validateCsrfToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Check CSRF token from request
     */
    public static function checkCsrfToken() {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_COOKIE['csrf_token'] ?? null);

        // If Content-Type is JSON, attempt to read token from body
        if (!$token) {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            if (stripos($contentType, 'application/json') !== false) {
                $raw = file_get_contents('php://input');
                if ($raw) {
                    $json = json_decode($raw, true);
                    if (is_array($json) && isset($json['csrf_token'])) {
                        $token = $json['csrf_token'];
                    }
                }
            }
        }

        if (!$token || !self::validateCsrfToken($token)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Invalid CSRF token'
            ]);
            exit;
        }
    }
    
    /**
     * Rate limiting
     * Prevents abuse by limiting requests per IP
     */
    public static function rateLimit($maxRequests = 60, $timeWindow = 60) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $cacheFile = __DIR__ . '/../../storage/cache/rate_limit_' . md5($ip) . '.json';
        
        // Create cache directory if not exists
        $cacheDir = dirname($cacheFile);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        $now = time();
        $requests = [];
        
        // Load existing requests
        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            if ($data) {
                $requests = $data['requests'] ?? [];
            }
        }
        
        // Remove old requests outside time window
        $requests = array_filter($requests, function($timestamp) use ($now, $timeWindow) {
            return ($now - $timestamp) < $timeWindow;
        });
        
        // Check if limit exceeded
        if (count($requests) >= $maxRequests) {
            http_response_code(429);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => $timeWindow
            ]);
            exit;
        }
        
        // Add current request
        $requests[] = $now;
        
        // Save to cache
        file_put_contents($cacheFile, json_encode(['requests' => $requests]));
    }
    
    /**
     * Sanitize input data
     */
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        
        if (is_string($data)) {
            $value = trim($data);
            // Remove unsafe control characters except newlines and tabs
            $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
            // Limit excessively long strings
            if (strlen($value) > 5000) {
                $value = substr($value, 0, 5000);
            }
            return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }
        
        return $data;
    }
    
    /**
     * Validate request origin (prevent CORS attacks)
     * Enhanced to handle localhost with different ports
     */
    public static function validateOrigin() {
        // Check environment from config
        $isDevelopment = ($_ENV['APP_ENV'] ?? 'development') !== 'production';
        
        if ($isDevelopment) {
            // Allow all localhost/127.0.0.1 requests in development
            $origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '';
            $originHost = parse_url($origin, PHP_URL_HOST);
            
            // Allow localhost and 127.0.0.1 with any port
            if (in_array($originHost, ['localhost', '127.0.0.1', null, ''])) {
                return; // Allow request
            }
        }
        
        // Production validation - use APP_URL from config
        $appUrl = $_ENV['APP_URL'] ?? '';
        $allowedOrigins = [
            'http://localhost',
            'http://127.0.0.1',
            $appUrl, // Use configured APP_URL
            $_SERVER['HTTP_HOST'] ?? ''
        ];
        // Remove empty values
        $allowedOrigins = array_filter($allowedOrigins);
        
        $origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '';
        
        // If no origin header, allow (same-origin requests)
        if (empty($origin)) {
            return;
        }
        
        // Extract host from origin/referer
        $originHost = parse_url($origin, PHP_URL_HOST);
        $currentHost = parse_url($_SERVER['HTTP_HOST'] ?? '', PHP_URL_HOST) ?: ($_SERVER['HTTP_HOST'] ?? '');
        
        // Allow same-origin
        if ($originHost === $currentHost) {
            return;
        }
        
        // Check whitelist
        foreach ($allowedOrigins as $allowed) {
            $allowedHost = parse_url($allowed, PHP_URL_HOST) ?: $allowed;
            if ($originHost === $allowedHost) {
                return;
            }
        }
        
        // Block invalid origin
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Invalid request origin'
        ]);
        exit;
    }
    
    /**
     * Prevent SQL injection in raw queries
     */
    public static function escapeSql($value, $pdo) {
        if (is_array($value)) {
            return array_map(function($v) use ($pdo) {
                return self::escapeSql($v, $pdo);
            }, $value);
        }
        
        if (is_string($value)) {
            return $pdo->quote($value);
        }
        
        return $value;
    }
    
    /**
     * Check if request is AJAX
     */
    public static function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) 
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Validate file upload
     */
    public static function validateFileUpload($file, $allowedTypes = [], $maxSize = 5242880) {
        $errors = [];
        
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $errors[] = 'No file uploaded';
            return $errors;
        }
        
        // Check file size
        if ($file['size'] > $maxSize) {
            $errors[] = 'File size exceeds maximum allowed (' . ($maxSize / 1048576) . 'MB)';
        }
        
        // Check file type
        if (!empty($allowedTypes)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $allowedTypes)) {
                $errors[] = 'Invalid file type. Allowed types: ' . implode(', ', $allowedTypes);
            }
        }
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload error: ' . $file['error'];
        }
        
        return $errors;
    }
    
    /**
     * Generate secure random token
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Hash password securely
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    /**
     * Verify password
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Clean up old cache files
     */
    public static function cleanupCache($maxAge = 3600) {
        $cacheDir = __DIR__ . '/../../storage/cache/';
        if (!is_dir($cacheDir)) {
            return;
        }
        
        $now = time();
        $files = glob($cacheDir . 'rate_limit_*.json');
        
        foreach ($files as $file) {
            if (is_file($file) && ($now - filemtime($file)) > $maxAge) {
                unlink($file);
            }
        }
    }

    /**
     * Clean up expired remember-me tokens
     */
    public static function cleanupRememberTokens() {
        $dir = __DIR__ . '/../../storage/sessions/remember_tokens';
        if (!is_dir($dir)) {
            return;
        }
        $now = time();
        foreach (glob($dir . '/*.json') as $file) {
            if (!is_file($file)) continue;
            $json = @file_get_contents($file);
            $data = $json ? json_decode($json, true) : null;
            $expired = is_array($data) && isset($data['expiry']) && $data['expiry'] < $now;
            // Fallback to file age > 90 days
            $tooOld = ($now - filemtime($file)) > (90 * 86400);
            if ($expired || $tooOld) {
                @unlink($file);
            }
        }
    }
}

