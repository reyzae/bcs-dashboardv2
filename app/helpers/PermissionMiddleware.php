<?php

/**
 * Permission & Role Management Middleware
 * Checks user permissions and roles for accessing resources
 */
class PermissionMiddleware {
    
    /**
     * Role hierarchy
     */
    const ROLE_ADMIN = 'admin';
    const ROLE_MANAGER = 'manager';
    const ROLE_CASHIER = 'cashier';
    const ROLE_STAFF = 'staff';
    
    /**
     * Permission definitions for each role
     */
    private static $permissions = [
        'admin' => [
            // Full access to everything
            'users.view', 'users.create', 'users.update', 'users.delete',
            'products.view', 'products.create', 'products.update', 'products.delete',
            'customers.view', 'customers.create', 'customers.update', 'customers.delete',
            'transactions.view', 'transactions.create', 'transactions.update', 'transactions.delete',
            'reports.view', 'reports.export',
            'settings.view', 'settings.update',
            'pos.access'
        ],
        'manager' => [
            // Can manage products, customers, view reports
            'products.view', 'products.create', 'products.update', 'products.delete',
            'customers.view', 'customers.create', 'customers.update', 'customers.delete',
            'transactions.view', 'transactions.create', 'transactions.update',
            'reports.view', 'reports.export',
            'pos.access'
        ],
        'cashier' => [
            // Can only use POS and view customers
            'pos.access',
            'customers.view',
            'products.view',
            'transactions.view', 'transactions.create', 'transactions.update'
        ],
        'staff' => [
            'customers.view',
            'products.view'
        ]
    ];
    
    /**
     * Check if user is logged in
     */
    public static function checkAuth() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Unauthorized. Please login first.'
            ]);
            exit;
        }
    }
    
    /**
     * Check if user has specific role
     */
    public static function checkRole($requiredRole) {
        self::checkAuth();
        
        $userRole = $_SESSION['user_role'] ?? '';
        
        if ($userRole !== $requiredRole) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => "Access denied. Required role: $requiredRole"
            ]);
            exit;
        }
    }
    
    /**
     * Check if user has one of multiple roles
     */
    public static function checkRoles(array $requiredRoles) {
        self::checkAuth();
        
        $userRole = $_SESSION['user_role'] ?? '';
        
        if (!in_array($userRole, $requiredRoles)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => "Access denied. Required roles: " . implode(', ', $requiredRoles)
            ]);
            exit;
        }
    }
    
    /**
     * Check if user has specific permission
     */
    public static function checkPermission($permission) {
        self::checkAuth();
        
        $userRole = $_SESSION['user_role'] ?? '';
        
        if (!self::hasPermission($userRole, $permission)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => "Access denied. Required permission: $permission"
            ]);
            exit;
        }
    }
    
    /**
     * Check if user has any of multiple permissions
     */
    public static function checkPermissions(array $permissions, $requireAll = false) {
        self::checkAuth();
        
        $userRole = $_SESSION['user_role'] ?? '';
        
        if ($requireAll) {
            // User must have ALL permissions
            foreach ($permissions as $permission) {
                if (!self::hasPermission($userRole, $permission)) {
                    http_response_code(403);
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => "Access denied. Missing permission: $permission"
                    ]);
                    exit;
                }
            }
        } else {
            // User must have AT LEAST ONE permission
            $hasPermission = false;
            foreach ($permissions as $permission) {
                if (self::hasPermission($userRole, $permission)) {
                    $hasPermission = true;
                    break;
                }
            }
            
            if (!$hasPermission) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => "Access denied. Required one of: " . implode(', ', $permissions)
                ]);
                exit;
            }
        }
    }
    
    /**
     * Check if role has permission (without stopping execution)
     */
    public static function hasPermission($role, $permission) {
        return isset(self::$permissions[$role]) 
            && in_array($permission, self::$permissions[$role]);
    }
    
    /**
     * Get all permissions for a role
     */
    public static function getRolePermissions($role) {
        return self::$permissions[$role] ?? [];
    }
    
    /**
     * Check if user is admin
     */
    public static function isAdmin() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === self::ROLE_ADMIN;
    }
    
    /**
     * Check if user is manager
     */
    public static function isManager() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === self::ROLE_MANAGER;
    }
    
    /**
     * Check if user is cashier
     */
    public static function isCashier() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === self::ROLE_CASHIER;
    }

    public static function isStaff() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === self::ROLE_STAFF;
    }
    
    /**
     * Get current user info
     */
    public static function getCurrentUser() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return [
            'id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? null,
            'role' => $_SESSION['user_role'] ?? null,
            'full_name' => $_SESSION['full_name'] ?? null
        ];
    }
    
    /**
     * Check if user can access specific resource
     */
    public static function canAccess($resource, $action) {
        $permission = "$resource.$action";
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $userRole = $_SESSION['user_role'] ?? '';
        return self::hasPermission($userRole, $permission);
    }
}

