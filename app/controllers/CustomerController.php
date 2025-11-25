<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Customer.php';
require_once __DIR__ . '/BaseController.php';

/**
 * Bytebalok Customer Controller
 * Handles customer management operations
 */

class CustomerController extends BaseController {
    private $customerModel;
    
    public function __construct($pdo) {
        parent::__construct($pdo);
        $this->customerModel = new Customer($pdo);
    }
    
    /**
     * Get list of customers
     */
    public function list() {
        $this->checkAuthentication();
        $this->checkPermission('customers.view');
        $page = intval($_GET['page'] ?? 1);
        $limit = intval($_GET['limit'] ?? 20);
        $search = $_GET['search'] ?? '';
        $offset = ($page - 1) * $limit;
        
        if ($search) {
            $customers = $this->customerModel->search($search, $limit);
        } else {
            $customers = $this->customerModel->findAllWithStats([], 'name ASC', $limit, $offset);
        }
        
        $total = $this->customerModel->count();
        
        $this->sendSuccess([
            'customers' => $customers,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }
    
    /**
     * Get single customer
     */
    public function get() {
        $id = intval($_GET['id']);
        
        if (!$id) {
            $this->sendError('Customer ID is required', 400);
        }
        
        $customer = $this->customerModel->findWithStats($id);
        
        if (!$customer) {
            $this->sendError('Customer not found', 404);
        }
        
        $this->sendSuccess($customer);
    }
    
    /**
     * Search customers
     */
    public function search() {
        $query = $_GET['q'] ?? '';
        
        if (empty($query)) {
            $this->sendSuccess([]);
        }
        
        $customers = $this->customerModel->search($query);
        $this->sendSuccess($customers);
    }
    
    /**
     * Get top customers by spending
     */
    public function top() {
        $limit = intval($_GET['limit'] ?? 10);
        $customers = $this->customerModel->getTopCustomers($limit);
        $this->sendSuccess($customers);
    }
    
    /**
     * Create new customer - ENHANCED WITH VALIDATION
     */
    public function create() {
        if ($this->getMethod() !== 'POST') {
            $this->sendError('Method not allowed', 405);
        }
        
        $this->checkAuthentication();
        $this->checkPermission('customers.create');
        
        $data = $this->getRequestData();
        
        // Enhanced validation
        $this->validate($data, [
            'name' => ['required', 'minLength:2', 'maxLength:100'],
            'email' => ['email'],
            'phone' => ['phone'],
        ]);
        
        try {
            $result = $this->executeWithTransaction(function() use ($data) {
                // Sanitize input
                $data = $this->sanitizeInput($data);
                
                // Generate customer code if not provided
                if (empty($data['customer_code'])) {
                    $data['customer_code'] = $this->customerModel->generateCustomerCode();
                }
                
                // Check if customer code already exists
                if ($this->customerModel->codeExists($data['customer_code'])) {
                    throw new Exception('Customer code already exists');
                }
                
                // Check if email already exists (if provided)
                if (!empty($data['email']) && $this->customerModel->emailExists($data['email'])) {
                    throw new Exception('Email already exists');
                }
                
                $customerId = $this->customerModel->create($data);
                
                if (!$customerId) {
                    throw new Exception('Failed to create customer');
                }
                
                $this->logAction('create', 'customer', $customerId, $data);
                
                return ['id' => $customerId];
            });
            
            $this->sendSuccess($result, 'Customer created successfully');
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 500);
        }
    }
    
    /**
     * Update customer - ENHANCED WITH VALIDATION
     */
    public function update() {
        if ($this->getMethod() !== 'POST') {
            $this->sendError('Method not allowed', 405);
        }
        
        $this->checkAuthentication();
        $this->checkPermission('customers.update');
        
        $id = intval($_GET['id']);
        if (!$id) {
            $this->sendError('Customer ID is required', 400);
        }
        
        $data = $this->getRequestData();
        
        // Enhanced validation
        if (isset($data['name'])) {
            $this->validate($data, [
                'name' => ['minLength:2', 'maxLength:100']
            ]);
        }
        if (isset($data['email'])) {
            $this->validate($data, ['email' => ['email']]);
        }
        if (isset($data['phone'])) {
            $this->validate($data, ['phone' => ['phone']]);
        }
        
        try {
            $this->executeWithTransaction(function() use ($id, $data) {
                // Check if customer exists
                $oldCustomer = $this->customerModel->find($id);
                if (!$oldCustomer) {
                    throw new Exception('Customer not found');
                }
                
                // Sanitize input
                $data = $this->sanitizeInput($data);
                
                // Check if customer code already exists (excluding current customer)
                if (isset($data['customer_code']) && $this->customerModel->codeExists($data['customer_code'], $id)) {
                    throw new Exception('Customer code already exists');
                }
                
                // Check if email already exists (excluding current customer)
                if (!empty($data['email']) && $this->customerModel->emailExists($data['email'], $id)) {
                    throw new Exception('Email already exists');
                }
                
                $success = $this->customerModel->update($id, $data);
                
                if (!$success) {
                    throw new Exception('Failed to update customer');
                }
                
                $this->logAction('update', 'customer', $id, [
                    'old' => $oldCustomer,
                    'new' => $data
                ]);
            });
            
            $this->sendSuccess(null, 'Customer updated successfully');
        } catch (Exception $e) {
            $statusCode = $e->getMessage() === 'Customer not found' ? 404 : 500;
            $this->sendError($e->getMessage(), $statusCode);
        }
    }
    
    /**
     * Delete customer
     */
    public function delete() {
        $this->requireRole(['admin', 'manager']);
        
        if ($this->getMethod() !== 'POST') {
            $this->sendError('Method not allowed', 405);
        }
        
        $id = intval($_GET['id']);
        if (!$id) {
            $this->sendError('Customer ID is required', 400);
        }
        
        // Check if customer exists
        $customer = $this->customerModel->find($id);
        if (!$customer) {
            $this->sendError('Customer not found', 404);
        }
        
        // Check if customer has transactions (deactivate instead of error)
        $sql = "SELECT COUNT(*) as count FROM transactions WHERE customer_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        $hasTransactions = ((int)($result['count'] ?? 0)) > 0;
        
        // Soft delete by setting is_active to 0 (always allowed)
        $success = $this->customerModel->update($id, ['is_active' => 0]);
        
        if ($success) {
            $this->logAction('delete', 'customers', $id, $customer, ['is_active' => 0]);
            $msg = $hasTransactions ? 'Customer deactivated (has transactions)' : 'Customer deleted successfully';
            $this->sendSuccess(null, $msg);
        } else {
            $this->sendError('Failed to delete customer', 500);
        }
    }
    
    /**
     * Get customer statistics
     */
    public function getStats() {
        $stats = $this->customerModel->getStats();
        $this->sendSuccess($stats);
    }
    
    /**
     * Get customer transactions
     */
    public function getTransactions() {
        $id = intval($_GET['id']);
        
        if (!$id) {
            $this->sendError('Customer ID is required', 400);
        }
        
        // Check if customer exists
        $customer = $this->customerModel->find($id);
        if (!$customer) {
            $this->sendError('Customer not found', 404);
        }
        
        $page = intval($_GET['page'] ?? 1);
        $limit = intval($_GET['limit'] ?? 20);
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT t.*, u.full_name as user_name 
                FROM transactions t 
                LEFT JOIN users u ON t.user_id = u.id 
                WHERE t.customer_id = ? 
                ORDER BY t.created_at DESC 
                LIMIT ? OFFSET ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id, $limit, $offset]);
        $transactions = $stmt->fetchAll();
        
        $countSql = "SELECT COUNT(*) as count FROM transactions WHERE customer_id = ?";
        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute([$id]);
        $total = $countStmt->fetch()['count'];
        
        $this->sendSuccess([
            'transactions' => $transactions,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }
    
    /**
     * Export customers to CSV/Excel
     */
    public function export() {
        $format = $_GET['format'] ?? 'csv';
        $search = $_GET['search'] ?? '';
        
        // Build query using actual schema columns
        $sql = "SELECT 
                    c.id,
                    c.customer_code,
                    c.name,
                    c.email,
                    c.phone,
                    c.address,
                    c.city,
                    c.postal_code,
                    c.customer_type,
                    c.total_purchases,
                    c.total_spent,
                    c.last_purchase_date,
                    c.is_active,
                    c.created_at,
                    c.updated_at,
                    vs.verified_purchase_count,
                    vs.verified_total_spent,
                    vs.verified_last_purchase,
                    vs.days_since_last_purchase
                FROM customers c
                LEFT JOIN v_customer_stats vs ON vs.id = c.id
                WHERE 1=1";
        
        $params = [];
        
        // Search filter
        if ($search) {
            $sql .= " AND (c.name LIKE ? OR c.customer_code LIKE ? OR c.phone LIKE ? OR c.email LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        $sql .= " ORDER BY c.name ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Prepare data for export
        $data = [];
        foreach ($customers as $customer) {
            $data[] = [
                $customer['customer_code'] ?? '-',
                $customer['name'] ?? '-',
                $customer['email'] ?? '-',
                $customer['phone'] ?? '-',
                $customer['address'] ?? '-',
                $customer['city'] ?? '-',
                $customer['postal_code'] ?? '-',
                strtoupper($customer['customer_type'] ?? 'walk-in'),
                (int)($customer['total_purchases'] ?? 0),
                ExportHelper::formatCurrency((float)($customer['total_spent'] ?? 0)),
                !empty($customer['last_purchase_date']) ? ExportHelper::formatDate($customer['last_purchase_date'], 'd/m/Y') : '-',
                (int)($customer['verified_purchase_count'] ?? 0),
                ExportHelper::formatCurrency((float)($customer['verified_total_spent'] ?? 0)),
                !empty($customer['verified_last_purchase']) ? ExportHelper::formatDate($customer['verified_last_purchase'], 'd/m/Y') : '-',
                (int)($customer['days_since_last_purchase'] ?? 0),
                ($customer['is_active'] ?? 0) ? 'Active' : 'Inactive',
                ExportHelper::formatDate($customer['created_at'] ?? '', 'd/m/Y')
            ];
        }
        
        $headers = [
            'Customer Code',
            'Name',
            'Email',
            'Phone',
            'Address',
            'City',
            'Postal Code',
            'Customer Type',
            'Total Purchases',
            'Total Spent',
            'Last Purchase',
            'Verified Purchases',
            'Verified Total Spent',
            'Verified Last Purchase',
            'Days Since Last Purchase',
            'Status',
            'Created Date'
        ];
        
        $filename = 'customers_' . date('Y-m-d_His') . '.' . $format;
        
        try {
            if ($format === 'excel' || $format === 'xlsx') {
                $filepath = ExportHelper::exportToExcel($data, $filename, $headers, 'Customers');
            } else if ($format === 'pdf') {
                $html = ExportHelper::generateHTMLTable($data, $headers, 'Customers');
                $filepath = ExportHelper::exportToPDF($html, $filename, 'L');
            } else {
                // Default to CSV
                $filepath = ExportHelper::exportToCSV($data, $filename, $headers);
            }
            
            // Send file to browser
            if (file_exists($filepath)) {
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
                header('Content-Length: ' . filesize($filepath));
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                
                readfile($filepath);
                
                // Clean up - delete file after sending
                unlink($filepath);
                exit;
            } else {
                $this->sendError('Failed to generate export file', 500);
            }
            
        } catch (Exception $e) {
            error_log("Export Error: " . $e->getMessage());
            $this->sendError('Export failed: ' . $e->getMessage(), 500);
        }
    }
}

// Handle requests
$customerController = new CustomerController($pdo);

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        $customerController->list();
        break;
    case 'get':
        $customerController->get();
        break;
    case 'search':
        $customerController->search();
        break;
    case 'top':
        $customerController->top();
        break;
    case 'create':
        $customerController->create();
        break;
    case 'update':
        $customerController->update();
        break;
    case 'delete':
        $customerController->delete();
        break;
    case 'stats':
        $customerController->getStats();
        break;
    case 'transactions':
        $customerController->getTransactions();
        break;
    case 'export':
        $customerController->export();
        break;
    default:
        $customerController->sendError('Invalid action', 400);
}
