<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../models/Customer.php';

/**
 * Bytebalok POS Controller
 * Handles Point of Sale operations - Complete implementation
 */

class PosController extends BaseController {
    private $productModel;
    private $categoryModel;
    private $transactionModel;
    private $customerModel;
    
    public function __construct($pdo) {
        parent::__construct($pdo);
        $this->productModel = new Product($pdo);
        $this->categoryModel = new Category($pdo);
        $this->transactionModel = new Transaction($pdo);
        $this->customerModel = new Customer($pdo);
    }
    
    /**
     * Get active products for POS
     */
    public function getProducts() {
        try {
            $this->checkAuthentication();
            $this->checkPermission('pos.access');
            
        $categoryId = $_GET['category_id'] ?? null;
        $search = $_GET['search'] ?? '';
        
        if ($search) {
                // Filter hanya produk aktif (parameter ke-4 = 1)
                $products = $this->productModel->search($search, $categoryId, 100, 1);
        } else {
            $conditions = ['is_active' => 1];
                if ($categoryId && $categoryId !== 'all') {
                $conditions['category_id'] = $categoryId;
                }
                $products = $this->productModel->findAllWithCategory($conditions, 'name ASC', 100);
            }

            // Normalize numeric fields to ensure consistent types for frontend
            $normalized = array_map(function($p) {
                // Ensure id exists and is integer
                $p['id'] = isset($p['id']) ? intval($p['id']) : (isset($p['product_id']) ? intval($p['product_id']) : null);
                // Normalize common numeric fields
                if (isset($p['stock_quantity'])) $p['stock_quantity'] = intval($p['stock_quantity']);
                if (isset($p['min_stock_level'])) $p['min_stock_level'] = intval($p['min_stock_level']);
                if (isset($p['max_stock_level'])) $p['max_stock_level'] = intval($p['max_stock_level']);
                if (isset($p['price'])) $p['price'] = floatval($p['price']);
                if (isset($p['unit_price'])) $p['unit_price'] = floatval($p['unit_price']);
                return $p;
            }, $products ?: []);

            // Send in a consistent envelope for flexibility on the frontend
            $this->sendSuccess(['products' => $normalized]);
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 500);
        }
    }
    
    /**
     * Get active categories
     */
    public function getCategories() {
        try {
            $this->checkAuthentication();
            $this->checkPermission('pos.access');
        $categories = $this->categoryModel->getActive();
        $this->sendSuccess($categories);
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 500);
        }
    }
    
    /**
     * Get product by barcode (for barcode scanner)
     */
    public function getByBarcode() {
        try {
            $this->checkAuthentication();
            $this->checkPermission('pos.access');
        $barcode = $_GET['barcode'] ?? '';
        
        if (empty($barcode)) {
            $this->sendError('Barcode is required', 400);
                return;
        }
        
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.barcode = ? AND p.is_active = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$barcode]);
        $product = $stmt->fetch();
        
        if (!$product) {
            $this->sendError('Product not found', 404);
                return;
        }
        
        $this->sendSuccess($product);
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 500);
        }
    }
    
    /**
     * Search customers for autocomplete
     */
    public function searchCustomers() {
        try {
            $this->checkAuthentication();
            $this->checkPermission('pos.access');
            $query = $_GET['q'] ?? '';
            
            if (strlen($query) < 2) {
                $this->sendSuccess([]);
                return;
            }
            
            $customers = $this->customerModel->search($query, 10);
            $this->sendSuccess($customers);
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 500);
        }
    }
    
    /**
     * Create new transaction (checkout) - WITH TRANSACTION SAFETY
     */
    public function createTransaction() {
        try {
            error_log('═══════════════════════════════════════════════════');
            error_log('🚀 PosController::createTransaction() STARTED');
            error_log('═══════════════════════════════════════════════════');
            
            $this->checkAuthentication();
            error_log('✅ Authentication passed - User ID: ' . $this->user['id']);
            
            // DEBUG: Log request info
            error_log('📨 CONTENT-TYPE: ' . ($_SERVER['CONTENT_TYPE'] ?? 'NOT SET'));
            error_log('📨 REQUEST METHOD: ' . ($_SERVER['REQUEST_METHOD'] ?? 'NOT SET'));
            
            $this->checkPermission('transactions.create');
            
            // Get request data (reads php://input once)
            $data = $this->getRequestData();
            
            // DEBUG: Log what we got
            error_log('📨 GOT DATA: ' . (empty($data) ? 'EMPTY' : 'HAS DATA'));
            error_log('📨 DATA JSON: ' . json_encode($data));
            
            // DETAILED DEBUG LOGGING
            error_log('📥 PARSED REQUEST DATA:');
            error_log(json_encode($data, JSON_PRETTY_PRINT));
            error_log('📊 Data analysis:');
            error_log('  - Data is array: ' . (is_array($data) ? 'YES' : 'NO'));
            error_log('  - Data is empty: ' . (empty($data) ? 'YES' : 'NO'));
            error_log('  - Items isset: ' . (isset($data['items']) ? 'YES' : 'NO'));
            error_log('  - Items count: ' . (isset($data['items']) ? count($data['items']) : 'NOT SET'));
            error_log('  - Payment method: ' . ($data['payment_method'] ?? 'NOT SET'));
            error_log('  - Customer ID: ' . ($data['customer_id'] ?? 'null'));
            error_log('  - Tax percentage: ' . ($data['tax_percentage'] ?? 'NOT SET'));
            error_log('  - Discount percentage: ' . ($data['discount_percentage'] ?? 'NOT SET'));
            
            // Validate required fields
            if (!isset($data['items']) || empty($data['items'])) {
                error_log('❌ VALIDATION FAILED: Items are required');
                $this->sendError('Cart items are required', 400);
                return;
            }
            
            if (!isset($data['payment_method']) || empty($data['payment_method'])) {
                error_log('❌ VALIDATION FAILED: Payment method is required');
                $this->sendError('Payment method is required', 400);
                return;
            }
            
            if (!in_array($data['payment_method'], ['cash', 'card', 'qris', 'transfer'])) {
                error_log('❌ VALIDATION FAILED: Invalid payment method: ' . $data['payment_method']);
                $this->sendError('Invalid payment method', 400);
                return;
            }
            
            error_log('✅ Basic validation passed');
            
            // Validate customer_id if provided
            if (!empty($data['customer_id'])) {
                error_log('🔍 Validating customer_id: ' . $data['customer_id']);
                error_log('🔍 Customer ID type: ' . gettype($data['customer_id']));
                
                try {
                    // Ensure customer_id is integer
                    $customerId = (int)$data['customer_id'];
                    error_log('🔍 Converted customer_id to int: ' . $customerId);
                    
                    // Check if customer exists
                    $customer = $this->customerModel->find($customerId);
                    
                    if (!$customer) {
                        error_log('❌ Customer not found in database: ' . $customerId);
                        // Don't fail - just set to null (walk-in customer)
                        $data['customer_id'] = null;
                        error_log('⚠️  Customer not found, proceeding as walk-in customer');
                    } else {
                        // Check if customer is active
                        if (isset($customer['is_active']) && $customer['is_active'] == 0) {
                            error_log('❌ Customer is inactive: ' . $customerId);
                            // Don't fail - just set to null (walk-in customer)
                            $data['customer_id'] = null;
                            error_log('⚠️  Customer is inactive, proceeding as walk-in customer');
                        } else {
                            error_log('✅ Customer validated: ' . $customer['name'] . ' (ID: ' . $customerId . ')');
                            // Update to ensure integer
                            $data['customer_id'] = $customerId;
                        }
                    }
                } catch (Exception $e) {
                    error_log('❌ Error validating customer: ' . $e->getMessage());
                    // Don't fail - just set to null (walk-in customer)
                    $data['customer_id'] = null;
                    error_log('⚠️  Customer validation error, proceeding as walk-in customer');
                }
            } else {
                error_log('ℹ️  No customer selected (walk-in customer)');
                $data['customer_id'] = null;
            }
            
            // Validate stock availability and add product names for all items
            $processedItems = [];
            foreach ($data['items'] as $item) {
                $product = $this->productModel->find($item['product_id']);
                if (!$product) {
                    throw new Exception("Product ID {$item['product_id']} not found");
                }
                
                if ($product['stock_quantity'] < $item['quantity']) {
                    throw new Exception("Insufficient stock for {$product['name']}. Available: {$product['stock_quantity']}, Requested: {$item['quantity']}");
                }
                
                // Add product name to item (snapshot for historical record)
                $item['product_name'] = $product['name'];
                $processedItems[] = $item;
            }
            
            // Calculate totals
            $subtotal = 0;
            foreach ($processedItems as $item) {
                $subtotal += $item['unit_price'] * $item['quantity'];
            }
            
            $discount_percentage = $data['discount_percentage'] ?? 0;
            $discount_amount = $subtotal * ($discount_percentage / 100);
            
            $taxable_amount = $subtotal - $discount_amount;
            $tax_percentage = $data['tax_percentage'] ?? 10;
            $tax_amount = $taxable_amount * ($tax_percentage / 100);
            
            $total_amount = $taxable_amount + $tax_amount;

            // Ensure customer_id is null or integer
            $customerId = isset($data['customer_id']) && !empty($data['customer_id']) ? (int)$data['customer_id'] : null;
            error_log('📝 Preparing transaction with customer_id: ' . ($customerId ?? 'NULL (walk-in)'));

            // Handle cash payment specifics
            $cashReceived = null;
            $cashChange = null;
            if ($data['payment_method'] === 'cash') {
                // Use provided cash_received if available; otherwise compute from UI rules
                if (isset($data['cash_received'])) {
                    $cashReceived = (float)$data['cash_received'];
                    $cashChange = max(0, $cashReceived - $total_amount);
                } else {
                    // As a fallback, keep nulls; UI already validates sufficient payment
                    $cashReceived = null;
                    $cashChange = null;
                }
            }
            
            // Prepare transaction data
            $transactionData = [
                'customer_id' => $customerId,
                'user_id' => $this->user['id'],
                'transaction_type' => 'pos',  // Using 'pos' since enum already has it
                'subtotal' => $subtotal,
                'discount_amount' => $discount_amount,
                'discount_percentage' => $discount_percentage,
                'tax_amount' => $tax_amount,
                'tax_percentage' => $tax_percentage,
                'total_amount' => $total_amount,
                'payment_method' => $data['payment_method'],
                'payment_reference' => $data['payment_reference'] ?? null,
                'cash_received' => $cashReceived,
                'cash_change' => $cashChange,
                'status' => 'pending',
                'notes' => $data['notes'] ?? null
            ];
            
            // Create transaction with items (this already has transaction handling)
            $transactionId = $this->transactionModel->createWithItems($transactionData, $processedItems);
            
            try {
                $this->logAction('create', 'transaction', $transactionId, [
                    'total_amount' => $total_amount,
                    'items_count' => count($data['items']),
                    'payment_method' => $data['payment_method']
                ]);
            } catch (Exception $logError) {
                error_log('Failed to log transaction: ' . $logError->getMessage());
            }
            
            // Get complete transaction details
            $transaction = $this->transactionModel->findWithDetails($transactionId);
            $transaction['items'] = $this->transactionModel->getItems($transactionId);
            
            // Generate payment info for non-cash payments
            $paymentInfo = null;
            if ($data['payment_method'] !== 'cash') {
                require_once __DIR__ . '/../models/Payment.php';
                $paymentModel = new Payment($this->pdo);
                
                $orderId = $transaction['invoice_number'] ?? 'TXN' . $transactionId;
                $customerData = [];
                if ($customerId) {
                    $customer = $this->customerModel->find($customerId);
                    if ($customer) {
                        $customerData = [
                            'name' => $customer['name'],
                            'email' => $customer['email'] ?? '',
                            'phone' => $customer['phone'] ?? ''
                        ];
                    }
                }
                
                try {
                    switch ($data['payment_method']) {
                        case 'qris':
                            $paymentInfo = $paymentModel->generateQRIS($orderId, $total_amount, $customerData);
                            break;
                        case 'transfer':
                            $bank = $_GET['bank'] ?? 'bca'; // Default BCA
                            $paymentInfo = $paymentModel->generateBankTransfer($orderId, $total_amount, $bank);
                            break;
                        case 'card':
                            $paymentInfo = $paymentModel->generateCardPayment($orderId, $total_amount, $customerData);
                            break;
                    }
                    
                    // Store payment info in database if needed
                    if ($paymentInfo) {
                        $paymentRecord = [
                            'order_id' => $transactionId, // Using transaction_id as order_id for POS
                            'payment_method' => $data['payment_method'],
                            'amount' => $total_amount,
                            'status' => 'pending',
                            'transaction_id' => $paymentInfo['transaction_id'] ?? null,
                            'qr_string' => $paymentInfo['qr_string'] ?? null,
                            'qr_code' => $paymentInfo['qr_code_url'] ?? null,
                            'payment_url' => $paymentInfo['payment_url'] ?? null,
                            'expired_at' => $paymentInfo['expired_at'] ?? null
                        ];
                        $paymentModel->createPayment($paymentRecord);
                    }
                } catch (Exception $paymentError) {
                    error_log('⚠️ Payment gateway error (non-critical): ' . $paymentError->getMessage());
                    // Don't fail transaction if payment gateway fails
                }
            }
            
            error_log('✅ Transaction created successfully - ID: ' . $transactionId);
            error_log('💵 Total amount: Rp ' . number_format($total_amount, 0, ',', '.'));
            error_log('📦 Items count: ' . count($processedItems));
            
            $result = [
                'message' => 'Transaction created, awaiting payment',
                'transaction' => $transaction,
                'transaction_id' => $transactionId,
                'payment_info' => $paymentInfo // Include payment info for non-cash payments
            ];
            
            $this->sendSuccess($result);
            
        } catch (Exception $e) {
            error_log('═══════════════════════════════════════════════════');
            error_log('❌ TRANSACTION FAILED!');
            error_log('═══════════════════════════════════════════════════');
            error_log('Error message: ' . $e->getMessage());
            error_log('Error code: ' . $e->getCode());
            error_log('Error file: ' . $e->getFile() . ':' . $e->getLine());
            error_log('Stack trace:');
            error_log($e->getTraceAsString());
            error_log('═══════════════════════════════════════════════════');
            
            try {
                $this->logger->error('POS Transaction failed', [
                    'error' => $e->getMessage(),
                    'user_id' => $this->user['id'] ?? null,
                    'data' => $data ?? [],
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
            } catch (Exception $logError) {
                error_log('Failed to log error: ' . $logError->getMessage());
            }
            
            // Send detailed error in development
            $errorMessage = $e->getMessage();
            $errorDetails = null;
            
            if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
                $errorDetails = [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => explode("\n", $e->getTraceAsString())
                ];
            }
            
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Transaction failed: ' . $errorMessage,
                'error' => $errorMessage,
                'debug' => $errorDetails,
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_PRETTY_PRINT);
            exit;
        }
    }

    /**
     * Confirm payment for a POS transaction
     * POST: { transaction_id }
     */
    public function confirmPayment() {
        try {
            $this->checkAuthentication();
            $this->checkPermission('transactions.update');

            $data = $this->getRequestData();
            $this->validateRequired($data, ['transaction_id']);

            $txnId = (int)$data['transaction_id'];
            $transaction = $this->transactionModel->find($txnId);
            if (!$transaction) { $this->sendError('Transaction not found', 404); return; }

            // Update transaction status to completed
            $sql = "UPDATE transactions SET status = 'completed', updated_at = NOW() WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$txnId]);

            // Update payment record to success if exists
            require_once __DIR__ . '/../models/Payment.php';
            $paymentModel = new Payment($this->pdo);
            $payment = $paymentModel->findByOrderId($txnId);
            if ($payment) {
                $paymentModel->updateStatus($payment['id'], 'success', $payment['transaction_id'] ?? null, ['manual_confirmed' => true]);
            }

            $this->sendSuccess(['transaction_id' => $txnId, 'status' => 'completed']);
        } catch (Exception $e) {
            $this->sendError('Failed to confirm payment: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Hold transaction (save for later)
     */
    public function holdTransaction() {
        try {
            $this->checkAuthentication();
            $this->checkPermission('pos.access');
            
            $data = $this->getRequestData();
            
            $this->validate($data, [
                'cart_data' => ['required']
            ]);
            
            // Save to hold_transactions table
            $sql = "INSERT INTO hold_transactions (user_id, cart_data, customer_id, notes) 
                    VALUES (?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $this->user['id'],
                json_encode($data['cart_data']),
                $data['customer_id'] ?? null,
                $data['notes'] ?? null
            ]);
            
            $holdId = $this->pdo->lastInsertId();
            
            try {
                $this->logAction('create', 'hold_transaction', $holdId);
            } catch (Exception $logError) {
                error_log('Failed to log hold transaction: ' . $logError->getMessage());
            }
            
            $result = [
                'message' => 'Transaction held successfully',
                'hold_id' => $holdId
            ];
            
            $this->sendSuccess($result);
        } catch (Exception $e) {
            $this->sendError('Failed to hold transaction: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get held transactions
     */
    public function getHeldTransactions() {
        try {
            $this->checkAuthentication();
            $this->checkPermission('pos.access');
            
            $sql = "SELECT ht.*, c.name as customer_name 
                    FROM hold_transactions ht 
                    LEFT JOIN customers c ON ht.customer_id = c.id 
                    WHERE ht.user_id = ? 
                    ORDER BY ht.created_at DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$this->user['id']]);
            $holds = $stmt->fetchAll();
            
            // Decode cart data for each hold
            foreach ($holds as &$hold) {
                $hold['cart_data'] = json_decode($hold['cart_data'], true);
            }
            
            $this->sendSuccess($holds);
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 500);
        }
    }
    
    /**
     * Resume held transaction
     */
    public function resumeTransaction() {
        try {
            $this->checkAuthentication();
            $this->checkPermission('pos.access');
            
            $holdId = $_GET['id'] ?? null;
            if (!$holdId) {
                $this->sendError('Hold ID is required', 400);
                return;
            }
            
            $sql = "SELECT * FROM hold_transactions WHERE id = ? AND user_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$holdId, $this->user['id']]);
            $hold = $stmt->fetch();
            
            if (!$hold) {
                $this->sendError('Held transaction not found', 404);
                return;
            }
            
            $hold['cart_data'] = json_decode($hold['cart_data'], true);
            
            // Delete the held transaction
            $deleteSql = "DELETE FROM hold_transactions WHERE id = ?";
            $deleteStmt = $this->pdo->prepare($deleteSql);
            $deleteStmt->execute([$holdId]);
            
            try {
                $this->logAction('resume', 'hold_transaction', $holdId);
            } catch (Exception $logError) {
                error_log('Failed to log resume transaction: ' . $logError->getMessage());
            }
            
            $this->sendSuccess($hold);
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 500);
        }
    }
    
    /**
     * Get POS statistics (today's sales)
     */
    public function getStats() {
        try {
            $this->checkAuthentication();
            $this->checkPermission('pos.access');
            
            $today = date('Y-m-d');
            $stats = $this->transactionModel->getSalesStats($today, $today);
            
            // Backend returns today_revenue and today_count; map to frontend keys
            $todayRevenue = (float)($stats['today_revenue'] ?? 0);
            $todayCount = (int)($stats['today_count'] ?? 0);
            $averageSale = $todayCount > 0 ? ($todayRevenue / $todayCount) : 0;
            
            $response = [
                'today_sales' => $todayRevenue,
                'today_transactions' => $todayCount,
                'average_sale' => $averageSale,
                // Filled below using payment breakdown query
                'cash_sales' => 0,
                'card_sales' => 0,
                'qris_sales' => 0
            ];
            
            // Get transaction count by payment method
            $sql = "SELECT 
                        COUNT(*) as total_transactions,
                        payment_method,
                        SUM(total_amount) as total
                    FROM transactions 
                    WHERE DATE(created_at) = ? AND status = 'completed'
                    GROUP BY payment_method";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$today]);
            $paymentStats = $stmt->fetchAll();
            
            $response['payment_breakdown'] = $paymentStats;
            // Sum sales by payment method for convenience
            foreach ($paymentStats as $p) {
                $method = strtolower($p['payment_method'] ?? '');
                $total = (float)($p['total'] ?? 0);
                if ($method === 'cash') {
                    $response['cash_sales'] += $total;
                } elseif ($method === 'card') {
                    $response['card_sales'] += $total;
                } elseif ($method === 'qris') {
                    $response['qris_sales'] += $total;
                }
            }
            
            $this->sendSuccess($response);
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 500);
        }
    }
    
    /**
     * Get recent transactions
     */
    public function getRecentTransactions() {
        try {
            $this->checkAuthentication();
            $this->checkPermission('pos.access');
            
            $limit = $_GET['limit'] ?? 10;
            $transactions = $this->transactionModel->findAllWithDetails(
                ['transaction_type' => 'pos', 'status' => 'completed'],  // Using 'pos' transaction type
                'created_at DESC',
                $limit
            );
            
            $this->sendSuccess($transactions);
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 500);
        }
    }
}
