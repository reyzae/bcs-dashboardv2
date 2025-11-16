<?php
/**
 * Bytebalok Base Controller
 * Abstract base class for all controllers with common functionality
 * Enhanced with validation, logging, security, and error handling
 */

// Load helper classes
require_once __DIR__ . '/../helpers/Validator.php';
require_once __DIR__ . '/../helpers/Logger.php';
require_once __DIR__ . '/../helpers/SecurityMiddleware.php';
require_once __DIR__ . '/../helpers/PermissionMiddleware.php';

abstract class BaseController {
    protected $pdo;
    protected $user;
    protected $logger;
    protected $validator;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->logger = new Logger($pdo);
        $this->validator = new Validator();
        
        // Don't check authentication in constructor for API endpoints
        // Authentication will be checked in individual controller methods
        
        // DISABLED: Custom error/exception handlers can interfere with error responses
        // set_error_handler([$this, 'errorHandler']);
        // set_exception_handler([$this, 'exceptionHandler']);
    }
    
    /**
     * Check if user is authenticated
     */
    protected function checkAuthentication() {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            $this->sendResponse(['error' => 'Unauthorized'], 401);
            exit();
        }
        
        $this->user = [
            'id' => $_SESSION['user_id'],
            'role' => $_SESSION['user_role'] ?? 'staff',
            'username' => $_SESSION['username'] ?? ''
        ];
    }
    
    /**
     * Check if user has required role
     */
    protected function requireRole($requiredRoles) {
        // Delegate to centralized PermissionMiddleware
        if (!is_array($requiredRoles)) {
            $requiredRoles = [$requiredRoles];
        }
        PermissionMiddleware::checkRoles($requiredRoles);
    }
    
    /**
     * Send JSON response (Standardized format)
     */
    public function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Ensure consistent response format
        if (!isset($data['success'])) {
            $data = [
                'success' => $statusCode >= 200 && $statusCode < 300,
                'data' => $data
            ];
        }
        
        // Add timestamp to all responses
        $data['timestamp'] = date('Y-m-d H:i:s');
        
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
        // Log API response (catch any logging errors)
        try {
            $endpoint = $_SERVER['REQUEST_URI'] ?? 'unknown';
            $this->logger->apiResponse($endpoint, $statusCode, $data['success']);
        } catch (Exception $e) {
            error_log('Failed to log API response: ' . $e->getMessage());
        }
        
        exit();
    }
    
    /**
     * Send success response (Standardized)
     */
    public function sendSuccess($data = null, $message = 'Operation successful') {
        $response = [
            'success' => true,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        $this->sendResponse($response, 200);
    }
    
    /**
     * Send error response (Standardized)
     */
    public function sendError($message = 'An error occurred', $statusCode = 400, $errors = null) {
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        // Log error (catch any logging errors to prevent masking the original error)
        try {
            $this->logger->error($message, ['status_code' => $statusCode, 'errors' => $errors]);
        } catch (Exception $e) {
            error_log('Failed to log error: ' . $e->getMessage());
        }
        
        $this->sendResponse($response, $statusCode);
    }
    
    /**
     * Send validation error response
     */
    public function sendValidationError($errors, $message = 'Validation failed') {
        $this->sendError($message, 422, $errors);
    }
    
    /**
     * Validate data using rules (Enhanced)
     */
    protected function validate($data, $rules) {
        $result = Validator::validate($data, $rules);
        
        if ($result !== true) {
            $this->sendValidationError($result);
        }
        
        return true;
    }
    
    /**
     * Validate required fields
     */
    protected function validateRequired($data, $requiredFields) {
        $missing = [];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            $this->sendValidationError(
                array_combine($missing, array_fill(0, count($missing), 'This field is required')),
                'Missing required fields'
            );
        }
    }
    
    /**
     * Sanitize input data
     */
    protected function sanitizeInput($data) {
        return SecurityMiddleware::sanitizeInput($data);
    }
    
    /**
     * Get request method
     */
    protected function getMethod() {
        return $_SERVER['REQUEST_METHOD'];
    }
    
    /**
     * Get request data
     */
    protected function getRequestData() {
        $method = $this->getMethod();
        
        if ($method === 'GET') {
            return $_GET;
        } elseif ($method === 'POST' || $method === 'PUT' || $method === 'PATCH' || $method === 'DELETE') {
            // Check if JSON request
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            if (strpos($contentType, 'application/json') !== false) {
                $input = file_get_contents('php://input');
                error_log('🔍 BaseController::getRequestData() - Raw input: ' . substr($input, 0, 200));
                
                if (empty($input)) {
                    error_log('⚠️  php://input is EMPTY! Checking $_POST...');
                    // Fallback: check $_POST
                    if (!empty($_POST)) {
                        // If $_POST is a string (JSON), decode it
                        if (is_string($_POST)) {
                            error_log('🔄 $_POST is string, attempting to decode');
                            $decoded = json_decode($_POST, true);
                            if ($decoded !== null) {
                                error_log('✅ Successfully decoded $_POST string');
                                return $decoded;
                            }
                        }
                        // If $_POST is already an array, return it
                        if (is_array($_POST)) {
                            error_log('✅ $_POST is array, returning as-is');
                            return $_POST;
                        }
                    }
                    error_log('❌ No valid data in php://input or $_POST');
                    return [];
                }
                
                $decoded = json_decode($input, true);
                if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                    error_log('❌ JSON decode failed: ' . json_last_error_msg());
                    error_log('📨 Raw input was: ' . $input);
                }
                return $decoded ?? [];
            }
            // Fallback to form data
            return $_POST;
        }
        
        return [];
    }
    
    /**
     * Log user action (Enhanced with new Logger)
     */
    protected function logAction($action, $entity, $entityId = null, $details = null) {
        $userId = $this->user['id'] ?? null;
        return $this->logger->audit($action, $entity, $entityId, $details, $userId);
    }
    
    /**
     * Begin database transaction
     */
    protected function beginTransaction() {
        try {
            $this->pdo->beginTransaction();
            $this->logger->debug('Database transaction started');
        } catch (PDOException $e) {
            $this->logger->error('Failed to start transaction: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Commit database transaction
     */
    protected function commitTransaction() {
        try {
            $this->pdo->commit();
            $this->logger->debug('Database transaction committed');
        } catch (PDOException $e) {
            $this->logger->error('Failed to commit transaction: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Rollback database transaction
     */
    protected function rollbackTransaction() {
        try {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
                $this->logger->warning('Database transaction rolled back');
            }
        } catch (PDOException $e) {
            $this->logger->error('Failed to rollback transaction: ' . $e->getMessage());
        }
    }
    
    /**
     * Execute with transaction safety
     */
    protected function executeWithTransaction(callable $callback) {
        try {
            $this->beginTransaction();
            $result = $callback();
            $this->commitTransaction();
            return $result;
        } catch (Exception $e) {
            $this->rollbackTransaction();
            $this->logger->error('Transaction failed: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Custom error handler
     */
    public function errorHandler($errno, $errstr, $errfile, $errline) {
        $this->logger->error("PHP Error [$errno]: $errstr in $errfile:$errline");
        
        // Don't send response if headers already sent or in production
        if (!headers_sent() && getenv('APP_ENV') !== 'production') {
            $this->sendError("Internal error occurred", 500);
        }
        
        return true;
    }
    
    /**
     * Custom exception handler
     */
    public function exceptionHandler($exception) {
        $this->logger->error('Uncaught exception: ' . $exception->getMessage(), [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
        
        if (!headers_sent()) {
            $message = getenv('APP_ENV') === 'production' 
                ? 'An unexpected error occurred' 
                : $exception->getMessage();
            $this->sendError($message, 500);
        }
    }
    
    /**
     * Check permission for current user
     */
    protected function checkPermission($permission) {
        PermissionMiddleware::checkPermission($permission);
    }
    
    /**
     * Check if user has role
     */
    protected function checkRole($role) {
        PermissionMiddleware::checkRole($role);
    }
    
    /**
     * Check if user has any of the roles
     */
    protected function checkRoles(array $roles) {
        PermissionMiddleware::checkRoles($roles);
    }
    
    /**
     * Require admin access
     */
    protected function requireAdmin() {
        $this->checkRole('admin');
    }
}
