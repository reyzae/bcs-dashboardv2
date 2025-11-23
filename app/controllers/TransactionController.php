<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Customer.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../helpers/ExportHelper.php';

/**
 * Bytebalok Transaction Controller
 * Handles transaction operations
 */

class TransactionController extends BaseController {
    private $transactionModel;
    private $productModel;
    private $customerModel;
    private $orderModel;
    private $paymentModel;
    
    public function __construct($pdo) {
        parent::__construct($pdo);
        $this->transactionModel = new Transaction($pdo);
        $this->productModel = new Product($pdo);
        $this->customerModel = new Customer($pdo);
        $this->orderModel = new Order($pdo);
        $this->paymentModel = new Payment($pdo);
    }
    
    /**
     * Create new transaction
     */
    public function create() {
        if ($this->getMethod() !== 'POST') {
            $this->sendError('Method not allowed', 405);
        }
        
        $data = $this->getRequestData();
        $this->validateRequired($data, ['items', 'payment_method']);
        
        // Validate items
        if (!is_array($data['items']) || empty($data['items'])) {
            $this->sendError('Items are required', 400);
        }
        
        // Validate payment method
        $validMethods = ['cash', 'card', 'transfer', 'qris', 'other'];
        if (!in_array($data['payment_method'], $validMethods)) {
            $this->sendError('Invalid payment method', 400);
        }
        
        // Validate customer if provided
        if (!empty($data['customer_id'])) {
            $customer = $this->customerModel->find($data['customer_id']);
            if (!$customer) {
                $this->sendError('Customer not found', 400);
            }
        }
        
        // Validate products and calculate totals
        $subtotal = 0;
        $validatedItems = [];
        
        foreach ($data['items'] as $item) {
            $this->validateRequired($item, ['product_id', 'quantity', 'unit_price']);
            
            $product = $this->productModel->find($item['product_id']);
            if (!$product) {
                $this->sendError("Product ID {$item['product_id']} not found", 400);
            }
            
            if ($product['stock_quantity'] < $item['quantity']) {
                $this->sendError("Insufficient stock for product: {$product['name']}", 400);
            }
            
            $itemTotal = $item['unit_price'] * $item['quantity'];
            $subtotal += $itemTotal;
            
            $validatedItems[] = [
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'discount_amount' => $item['discount_amount'] ?? 0,
                'discount_percentage' => $item['discount_percentage'] ?? 0,
                'total_price' => $itemTotal
            ];
        }
        
        // Calculate totals
        $discountAmount = $data['discount_amount'] ?? 0;
        $discountPercentage = $data['discount_percentage'] ?? 0;
        
        if ($discountPercentage > 0) {
            $discountAmount = $subtotal * ($discountPercentage / 100);
        }
        
        $taxableAmount = $subtotal - $discountAmount;
        $taxAmount = $taxableAmount * 0.1; // 10% tax rate
        $totalAmount = $taxableAmount + $taxAmount;
        
        // Prepare transaction data
        $transactionData = [
            'customer_id' => $data['customer_id'] ?? null,
            'user_id' => $this->user['id'],
            'transaction_type' => $data['transaction_type'] ?? 'sale',
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'discount_percentage' => $discountPercentage,
            'tax_amount' => $taxAmount,
            'tax_percentage' => 10,
            'total_amount' => $totalAmount,
            'payment_method' => $data['payment_method'],
            'payment_reference' => $data['payment_reference'] ?? null,
            'status' => 'completed',
            'notes' => $data['notes'] ?? null
        ];
        
        try {
            $transactionId = $this->transactionModel->createWithItems($transactionData, $validatedItems);
            
            if ($transactionId) {
                $this->logAction('create', 'transactions', $transactionId, null, $transactionData);
                
                // Get transaction details for response
                $transaction = $this->transactionModel->findWithDetails($transactionId);
                $this->sendSuccess($transaction, 'Transaction completed successfully');
            } else {
                $this->sendError('Failed to create transaction', 500);
            }
        } catch (Exception $e) {
            $this->sendError('Transaction failed: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get transaction list
     */
    public function list() {
        // Ensure we have current user context for sync user_id
        $this->checkAuthentication();
        
        $page = intval($_GET['page'] ?? 1);
        $limit = intval($_GET['limit'] ?? 20);
        $status = $_GET['status'] ?? null;
        $paymentMethod = $_GET['payment_method'] ?? null;
        $type = $_GET['type'] ?? null;
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        $offset = ($page - 1) * $limit;
        
        $conditions = [];
        if ($status) { $conditions['status'] = $status; }
        if ($paymentMethod) { $conditions['payment_method'] = $paymentMethod; }
        
        $orderBy = 'created_at DESC';
        $this->syncShopTransactions();

        // Fetch all then apply source filter to ensure accurate counts
        $all = $this->transactionModel->findAllWithDetails($conditions, $orderBy, null, null);
        
        // Source filter
        if ($type === 'shop') {
            $all = array_filter($all, function($t){
                $served = strtolower($t['served_by'] ?? '');
                $notes = strtolower($t['notes'] ?? '');
                return $served === 'system online' || (strpos($notes, 'order ') === 0);
            });
        } else if ($type === 'pos') {
            $all = array_filter($all, function($t){
                $served = strtolower($t['served_by'] ?? '');
                $notes = strtolower($t['notes'] ?? '');
                return !($served === 'system online' || (strpos($notes, 'order ') === 0));
            });
        }
        
        $transactions = array_values($all);
        
        // Apply date filter if provided
        if ($startDate || $endDate) {
            $transactions = array_filter($transactions, function($transaction) use ($startDate, $endDate) {
                $transactionDate = date('Y-m-d', strtotime($transaction['created_at']));
                if ($startDate && $transactionDate < $startDate) return false;
                if ($endDate && $transactionDate > $endDate) return false;
                return true;
            });
        }
        
        // Merge missing Shop orders into All view as pseudo-transactions
        if (!$type || $type === 'all') {
            try {
                $sql = "SELECT o.* FROM orders o
                        WHERE o.payment_status = 'paid' AND o.order_status IN ('processing','ready','completed')
                        ORDER BY o.paid_at DESC LIMIT 200";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute();
                $orders = $stmt->fetchAll();
                foreach ($orders as $order) {
                    $exists = false;
                    $ref = $order['payment_reference'] ?? null;
                    if ($ref) {
                        $chk = $this->pdo->prepare("SELECT id FROM transactions WHERE payment_reference = ? LIMIT 1");
                        $chk->execute([$ref]);
                        $exists = (bool)$chk->fetch();
                    }
                    if (!$exists) {
                        $prefix = 'Order ' . ($order['order_number'] ?? $order['id']);
                        $chk2 = $this->pdo->prepare("SELECT id FROM transactions WHERE notes LIKE ? LIMIT 1");
                        $chk2->execute([$prefix . '%']);
                        $exists = (bool)$chk2->fetch();
                    }
                    if ($exists) { continue; }
                    $transactions[] = [
                        'id' => null,
                        'transaction_number' => $order['order_number'] ?? ('ORD' . $order['id']),
                        'customer_name' => $order['customer_name'] ?? 'Online Customer',
                        'items_count' => $order['items_count'] ?? 0,
                        'total_amount' => (float)($order['total_amount'] ?? 0),
                        'payment_method' => $order['payment_method'] ?? null,
                        'status' => 'completed',
                        'created_at' => $order['paid_at'] ?? $order['created_at'] ?? date('Y-m-d H:i:s'),
                        'served_by' => 'System Online',
                        'notes' => 'Order ' . ($order['order_number'] ?? $order['id'])
                    ];
                }
            } catch (Exception $e) { /* ignore */ }
        }

        // Sort by created_at DESC to ensure latest Shop orders appear at top
        usort($transactions, function($a, $b) {
            $da = isset($a['created_at']) ? strtotime($a['created_at']) : 0;
            $db = isset($b['created_at']) ? strtotime($b['created_at']) : 0;
            return $db <=> $da;
        });

        $total = count($transactions);
        
        $this->sendSuccess([
            'transactions' => array_slice($transactions, $offset, $limit),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => max(1, ceil($total / $limit))
            ]
        ]);
    }

    private function syncShopTransactions() {
        try {
            // Find paid orders that do not yet have transactions
            $sql = "SELECT o.* FROM orders o
                    WHERE o.payment_status = 'paid' AND o.order_status IN ('processing','ready','completed')
                    ORDER BY o.paid_at DESC LIMIT 200";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $orders = $stmt->fetchAll();
            if (!$orders) { return; }

            foreach ($orders as $order) {
                $exists = false;
                // Check by payment_reference
                $ref = $order['payment_reference'] ?? null;
                if ($ref) {
                    $chk = $this->pdo->prepare("SELECT id FROM transactions WHERE payment_reference = ? LIMIT 1");
                    $chk->execute([$ref]);
                    $exists = (bool)$chk->fetch();
                }
                // Fallback check by notes prefix
                if (!$exists) {
                    $prefix = 'Order ' . ($order['order_number'] ?? $order['id']);
                    $chk2 = $this->pdo->prepare("SELECT id FROM transactions WHERE notes LIKE ? LIMIT 1");
                    $chk2->execute([$prefix . '%']);
                    $exists = (bool)$chk2->fetch();
                }
                if ($exists) { continue; }

                // Build transaction payload
                $items = $this->orderModel->getItems($order['id']);
                $txItems = [];
                foreach ($items as $it) {
                    $txItems[] = [
                        'product_id' => (int)$it['product_id'],
                        'quantity' => (int)$it['quantity'],
                        'unit_price' => (float)$it['unit_price'],
                        'discount_amount' => 0,
                        'discount_percentage' => 0,
                        'total_price' => (float)($it['total_price'] ?? ($it['quantity'] * $it['unit_price']))
                    ];
                }

                // Resolve customer
                $customerId = null;
                $phone = $order['customer_phone'] ?? '';
                $email = $order['customer_email'] ?? '';
                if (!empty($phone) || !empty($email)) {
                    $cstmt = $this->pdo->prepare("SELECT id FROM customers WHERE (phone = ? AND phone IS NOT NULL) OR (email = ? AND email IS NOT NULL) LIMIT 1");
                    $cstmt->execute([$phone, $email]);
                    $row = $cstmt->fetch();
                    if ($row && isset($row['id'])) { $customerId = (int)$row['id']; }
                }
                if (!$customerId) {
                    $code = $this->customerModel->generateCustomerCode();
                    $customerId = $this->customerModel->create([
                        'customer_code' => $code,
                        'name' => $order['customer_name'] ?: 'Online Customer',
                        'email' => $email ?: null,
                        'phone' => $phone ?: null,
                        'address' => $order['customer_address'] ?? null,
                        'is_active' => 1
                    ]);
                }

                // Payment reference fallback
                $payment = null; 
                try { $payment = $this->paymentModel->findByOrderId($order['id']); } catch (Exception $e) { $payment = null; }
                $paymentRef = $order['payment_reference'] ?? ($payment['transaction_id'] ?? null);

                // Create transaction
                $txData = [
                    'customer_id' => $customerId,
                    'user_id' => $this->getSystemUserId(),
                    'served_by' => 'System Online',
                    'transaction_type' => 'sale',
                    'subtotal' => (float)($order['subtotal'] ?? 0),
                    'discount_amount' => (float)($order['discount_amount'] ?? 0),
                    'discount_percentage' => 0,
                    'tax_amount' => (float)($order['tax_amount'] ?? 0),
                    'tax_percentage' => null,
                    'total_amount' => (float)($order['total_amount'] ?? 0),
                    'payment_method' => $order['payment_method'] ?? null,
                    'payment_reference' => $paymentRef,
                    'cash_received' => null,
                    'cash_change' => null,
                    'status' => 'completed',
                    'notes' => 'Order ' . ($order['order_number'] ?? $order['id'])
                ];
                try { $this->transactionModel->createWithItems($txData, $txItems); } catch (Exception $e) { /* continue */ }
            }
        } catch (Exception $e) { /* ignore sync errors */ }
    }

    public function syncShop() {
        $this->checkAuthentication();
        $this->requireRole(['admin','manager']);
        $this->syncShopTransactions();
        // Count Shop transactions after sync
        $all = $this->transactionModel->findAllWithDetails([], 'created_at DESC', null, null);
        $shops = array_filter($all, function($t){
            $served = strtolower($t['served_by'] ?? '');
            $notes = strtolower($t['notes'] ?? '');
            return $served === 'system online' || (strpos($notes, 'order ') === 0);
        });
        $this->sendSuccess(['shop_transactions' => count($shops)], 'Shop transactions synchronized');
    }

    private function getSystemUserId() {
        if (isset($this->user['id']) && $this->user['id']) { return (int)$this->user['id']; }
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE is_active = 1 AND role IN ('admin','manager') ORDER BY id ASC LIMIT 1");
            $stmt->execute();
            $row = $stmt->fetch();
            if ($row && isset($row['id'])) { return (int)$row['id']; }
        } catch (Exception $e) { /* ignore */ }
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM users ORDER BY id ASC LIMIT 1");
            $stmt->execute();
            $row = $stmt->fetch();
            if ($row && isset($row['id'])) { return (int)$row['id']; }
        } catch (Exception $e) { /* ignore */ }
        return 1;
    }
    
    /**
     * Get single transaction
     */
    public function get() {
        $id = intval($_GET['id']);
        
        if (!$id) {
            $this->sendError('Transaction ID is required', 400);
        }
        
        $transaction = $this->transactionModel->findWithDetails($id);
        
        if (!$transaction) {
            $this->sendError('Transaction not found', 404);
        }
        
        // Get transaction items
        $items = $this->transactionModel->getItems($id);
        $transaction['items'] = $items;
        
        $this->sendSuccess($transaction);
    }
    
    /**
     * Update transaction status
     */
    public function updateStatus() {
        if ($this->getMethod() !== 'POST') {
            $this->sendError('Method not allowed', 405);
        }
        
        $id = intval($_GET['id']);
        if (!$id) {
            $this->sendError('Transaction ID is required', 400);
        }
        
        $data = $this->getRequestData();
        $this->validateRequired($data, ['status']);
        
        $validStatuses = ['pending', 'completed', 'cancelled', 'refunded'];
        if (!in_array($data['status'], $validStatuses)) {
            $this->sendError('Invalid status', 400);
        }
        
        // Check if transaction exists
        $oldTransaction = $this->transactionModel->find($id);
        if (!$oldTransaction) {
            $this->sendError('Transaction not found', 404);
        }
        
        $success = $this->transactionModel->update($id, ['status' => $data['status']]);
        
        if ($success) {
            $this->logAction('update_status', 'transactions', $id, $oldTransaction, ['status' => $data['status']]);
            $this->sendSuccess(null, 'Transaction status updated successfully');
        } else {
            $this->sendError('Failed to update transaction status', 500);
        }
    }
    
    /**
     * Get sales statistics
     */
    public function getStats() {
        $this->checkAuthentication();
        $this->syncShopTransactions();
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        $type = $_GET['type'] ?? 'all';
        $stats = $this->transactionModel->getSalesStats($startDate, $endDate, $type);
        $this->sendSuccess($stats);
    }
    
    /**
     * Get daily sales report
     */
    public function getDailySales() {
        $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        
        $dailySales = $this->transactionModel->getDailySales($startDate, $endDate);
        
        // Format for chart
        $labels = [];
        $sales = [];
        
        foreach ($dailySales as $row) {
            $labels[] = date('d M', strtotime($row['date']));
            $sales[] = (float)$row['total_sales'];
        }
        
        $this->sendSuccess([
            'labels' => $labels,
            'sales' => $sales,
            'raw' => $dailySales
        ]);
    }
    
    /**
     * Get top selling products
     */
    public function getTopProducts() {
        $limit = intval($_GET['limit'] ?? 10);
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        
        $topProducts = $this->transactionModel->getTopSellingProducts($limit, $startDate, $endDate);
        $this->sendSuccess($topProducts);
    }
    
    /**
     * Cancel transaction
     */
    public function cancel() {
        $this->requireRole(['admin', 'manager']);
        
        if ($this->getMethod() !== 'POST') {
            $this->sendError('Method not allowed', 405);
            return;
        }
        
        try {
            $this->checkAuthentication();
            
            $data = $this->getRequestData();
            $transactionId = $data['transaction_id'] ?? null;
            $reason = $data['reason'] ?? 'No reason provided';
            
            if (!$transactionId) {
                $this->sendError('Transaction ID is required', 400);
                return;
            }
            
            // Get transaction to check ownership/permission
            $transaction = $this->transactionModel->find($transactionId);
            if (!$transaction) {
                $this->sendError('Transaction not found', 404);
                return;
            }
            
            // Only allow cancelling today's transactions for managers
            if ($this->user['role'] === 'manager') {
                $transactionDate = date('Y-m-d', strtotime($transaction['created_at']));
                $today = date('Y-m-d');
                
                if ($transactionDate !== $today) {
                    $this->sendError('Managers can only cancel today\'s transactions', 403);
                    return;
                }
            }
            
            // Cancel the transaction
            $success = $this->transactionModel->cancelTransaction(
                $transactionId,
                $reason,
                $this->user['id']
            );
            
            if ($success) {
                $this->logAction('cancel', 'transactions', $transactionId, $transaction, [
                    'reason' => $reason,
                    'cancelled_by' => $this->user['username']
                ]);
                
                $this->sendSuccess(null, 'Transaction cancelled successfully');
            } else {
                $this->sendError('Failed to cancel transaction', 500);
            }
            
        } catch (Exception $e) {
            error_log('Cancel transaction error: ' . $e->getMessage());
            $this->sendError($e->getMessage(), 500);
        }
    }
    
    /**
     * Refund transaction
     */
    public function refund() {
        $this->requireRole(['admin', 'manager']);
        
        if ($this->getMethod() !== 'POST') {
            $this->sendError('Method not allowed', 405);
            return;
        }
        
        try {
            $this->checkAuthentication();
            
            $data = $this->getRequestData();
            $transactionId = $data['transaction_id'] ?? null;
            $refundAmount = $data['refund_amount'] ?? null;
            $reason = $data['reason'] ?? 'No reason provided';
            
            if (!$transactionId) {
                $this->sendError('Transaction ID is required', 400);
                return;
            }
            
            if (!$refundAmount || $refundAmount <= 0) {
                $this->sendError('Valid refund amount is required', 400);
                return;
            }
            
            // Get transaction to validate
            $transaction = $this->transactionModel->find($transactionId);
            if (!$transaction) {
                $this->sendError('Transaction not found', 404);
                return;
            }
            
            // Refund the transaction
            $success = $this->transactionModel->refundTransaction(
                $transactionId,
                $refundAmount,
                $reason,
                $this->user['id']
            );
            
            if ($success) {
                $this->logAction('refund', 'transactions', $transactionId, $transaction, [
                    'refund_amount' => $refundAmount,
                    'reason' => $reason,
                    'refunded_by' => $this->user['username']
                ]);
                
                $this->sendSuccess(null, 'Transaction refunded successfully');
            } else {
                $this->sendError('Failed to refund transaction', 500);
            }
            
        } catch (Exception $e) {
            error_log('Refund transaction error: ' . $e->getMessage());
            $this->sendError($e->getMessage(), 500);
        }
    }
    
    /**
     * Old refund method - deprecated
     */
    public function refundOld() {
        $this->requireRole(['admin', 'manager']);
        
        if ($this->getMethod() !== 'POST') {
            $this->sendError('Method not allowed', 405);
        }
        
        $id = intval($_GET['id']);
        if (!$id) {
            $this->sendError('Transaction ID is required', 400);
        }
        
        $data = $this->getRequestData();
        $this->validateRequired($data, ['reason']);
        
        // Check if transaction exists and is completed
        $transaction = $this->transactionModel->find($id);
        if (!$transaction) {
            $this->sendError('Transaction not found', 404);
        }
        
        if ($transaction['status'] !== 'completed') {
            $this->sendError('Only completed transactions can be refunded', 400);
        }
        
        // Update transaction status to refunded
        $success = $this->transactionModel->update($id, [
            'status' => 'refunded',
            'notes' => $transaction['notes'] . "\nRefunded: " . $data['reason']
        ]);
        
        if ($success) {
            // Restore stock for refunded items
            $items = $this->transactionModel->getItems($id);
            foreach ($items as $item) {
                $this->productModel->updateStock(
                    $item['product_id'],
                    $item['quantity'],
                    'return',
                    'refund',
                    $id,
                    $this->user['id'],
                    'Stock restored due to refund'
                );
            }
            
            $this->logAction('refund', 'transactions', $id, $transaction, ['status' => 'refunded', 'reason' => $data['reason']]);
            $this->sendSuccess(null, 'Transaction refunded successfully');
        } else {
            $this->sendError('Failed to refund transaction', 500);
        }
    }
    
    /**
     * Export transactions to CSV/Excel
     */
    public function export() {
        $format = $_GET['format'] ?? 'csv';
        $status = $_GET['status'] ?? '';
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo = $_GET['date_to'] ?? '';
        
        // Build query
        $sql = "SELECT 
                    t.id,
                    t.invoice_number,
                    t.transaction_date,
                    c.name as customer_name,
                    c.phone as customer_phone,
                    u.full_name as cashier_name,
                    t.total_amount,
                    t.payment_method,
                    t.status,
                    t.notes,
                    t.created_at
                FROM transactions t
                LEFT JOIN customers c ON t.customer_id = c.id
                LEFT JOIN users u ON t.user_id = u.id
                WHERE 1=1";
        
        $params = [];
        
        if ($status) {
            $sql .= " AND t.status = ?";
            $params[] = $status;
        }
        
        if ($dateFrom) {
            $sql .= " AND DATE(t.transaction_date) >= ?";
            $params[] = $dateFrom;
        }
        
        if ($dateTo) {
            $sql .= " AND DATE(t.transaction_date) <= ?";
            $params[] = $dateTo;
        }
        
        $sql .= " ORDER BY t.created_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Prepare data for export
        $data = [];
        foreach ($transactions as $txn) {
            $data[] = [
                $txn['invoice_number'],
                ExportHelper::formatDate($txn['transaction_date'], 'd/m/Y H:i'),
                $txn['customer_name'] ?? 'Walk-in Customer',
                $txn['customer_phone'] ?? '-',
                $txn['cashier_name'] ?? '-',
                ExportHelper::formatCurrency($txn['total_amount']),
                ucfirst($txn['payment_method']),
                ucfirst($txn['status']),
                $txn['notes'] ?? '-',
                ExportHelper::formatDate($txn['created_at'], 'd/m/Y H:i')
            ];
        }
        
        $headers = [
            'Invoice Number',
            'Transaction Date',
            'Customer',
            'Phone',
            'Cashier',
            'Total Amount',
            'Payment Method',
            'Status',
            'Notes',
            'Created At'
        ];
        
        $filename = 'transactions_' . date('Y-m-d_His') . '.' . $format;
        
        try {
            if ($format === 'excel' || $format === 'xlsx') {
                $filepath = ExportHelper::exportToExcel($data, $filename, $headers, 'Transactions');
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
$transactionController = new TransactionController($pdo);

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'create':
        $transactionController->create();
        break;
    case 'list':
        $transactionController->list();
        break;
    case 'sync-shop':
        $transactionController->syncShop();
        break;
    case 'get':
        $transactionController->get();
        break;
    case 'update-status':
        $transactionController->updateStatus();
        break;
    case 'stats':
        $transactionController->getStats();
        break;
    case 'daily-sales':
        $transactionController->getDailySales();
        break;
    case 'top-products':
        $transactionController->getTopProducts();
        break;
    case 'cancel':
        $transactionController->cancel();
        break;
    case 'refund':
        $transactionController->refund();
        break;
    case 'export':
        $transactionController->export();
        break;
    default:
        $transactionController->sendError('Invalid action', 400);
}
