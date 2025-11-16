<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/BaseController.php';

/**
 * Bytebalok Order Controller
 * Handles customer orders from website (PUBLIC API)
 */

class OrderController extends BaseController {
    private $orderModel;
    private $productModel;
    private $paymentModel;
    private $notificationModel;
    
    public function __construct($pdo) {
        parent::__construct($pdo);
        $this->orderModel = new Order($pdo);
        $this->productModel = new Product($pdo);
        $this->paymentModel = new Payment($pdo);
        $this->notificationModel = new Notification($pdo);
    }
    
    /**
     * Create new order (PUBLIC)
     */
    public function create() {
        if ($this->getMethod() !== 'POST') {
            $this->sendError('Method not allowed', 405);
        }
        
        $data = $this->getRequestData();
        // Align validation with UI: email & address are optional
        $this->validateRequired($data, [
            'customer_name', 'customer_phone', 'items', 'payment_method'
        ]);
        
        // Validate items
        if (!is_array($data['items']) || empty($data['items'])) {
            $this->sendError('Items are required', 400);
        }
        
        // Validate payment method (COD disabled for public shop)
        $validMethods = ['qris', 'transfer'];
        if (!in_array($data['payment_method'], $validMethods)) {
            $this->sendError('Invalid payment method', 400);
        }
        
        // Validate email format only when provided
        if (!empty($data['customer_email']) && !filter_var($data['customer_email'], FILTER_VALIDATE_EMAIL)) {
            $this->sendError('Invalid email address', 400);
        }
        
        // Validate products and calculate totals
        $subtotal = 0;
        $validatedItems = [];
        
        foreach ($data['items'] as $item) {
            $this->validateRequired($item, ['product_id', 'quantity']);
            
            $product = $this->productModel->find($item['product_id']);
            if (!$product || !$product['is_active']) {
                $this->sendError("Product not available", 400);
            }
            
            if ($product['stock_quantity'] < $item['quantity']) {
                $this->sendError("Insufficient stock for product: {$product['name']}", 400);
            }
            
            $itemTotal = $product['price'] * $item['quantity'];
            $subtotal += $itemTotal;
            
            $validatedItems[] = [
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $product['price'],
                'total_price' => $itemTotal
            ];
        }
        
        // Calculate totals (respect Shop tax settings)
        $enableTaxShop = $this->getSettingValue('enable_tax_shop', '0') === '1';
        $taxRateShop = floatval($this->getSettingValue('tax_rate_shop', '11'));
        $taxAmount = $enableTaxShop ? round($subtotal * ($taxRateShop / 100), 2) : 0;
        $shippingAmount = $data['shipping_amount'] ?? 0;
        $totalAmount = $subtotal + $taxAmount + $shippingAmount;
        
        // Prepare order data
        $orderData = [
            'customer_name' => $this->sanitizeInput($data['customer_name']),
            'customer_email' => $this->sanitizeInput($data['customer_email'] ?? ''),
            'customer_phone' => $this->sanitizeInput($data['customer_phone']),
            'customer_address' => $this->sanitizeInput($data['customer_address'] ?? ''),
            'subtotal' => $subtotal,
            'discount_amount' => 0,
            'tax_amount' => $taxAmount,
            'shipping_amount' => $shippingAmount,
            'total_amount' => $totalAmount,
            'payment_method' => $data['payment_method'],
            'payment_status' => 'pending',
            'order_status' => 'pending',
            'notes' => $data['notes'] ?? null
        ];
        
        try {
            // Create order with items
            $orderId = $this->orderModel->createWithItems($orderData, $validatedItems);
            
            if ($orderId) {
                // Get order details
                $order = $this->orderModel->findWithItems($orderId);

                if (is_array($validatedItems) && !empty($validatedItems)) {
                    try {
                        foreach ($validatedItems as $item) {
                            $productId = (int)$item['product_id'];
                            $qty = (int)$item['quantity'];
                            if ($productId > 0 && $qty > 0) {
                                $stmt = $this->pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
                                $stmt->execute([$qty, $productId]);

                                $mv = $this->pdo->prepare(
                                    "INSERT INTO stock_movements (product_id, movement_type, quantity, reference_type, reference_id, user_id, notes) 
                                     VALUES (?, 'out', ?, 'order', ?, NULL, ?)"
                                );
                                $mv->execute([
                                    $productId,
                                    abs($qty),
                                    (int)$orderId,
                                    'Shop order ' . ($order['order_number'] ?? $orderId)
                                ]);
                            }
                        }
                    } catch (Exception $e) {
                        error_log('Stock reservation failed for order ' . ($order['order_number'] ?? $orderId) . ': ' . $e->getMessage());
                        // Continue flow: payment and order creation should not fail
                    }
                }
                
                // Create payment record
                $paymentData = [
                    'order_id' => $orderId,
                    'payment_method' => $data['payment_method'],
                    'amount' => $totalAmount
                ];
                
                $paymentId = $this->paymentModel->createPayment($paymentData);
                
                // Generate payment info per method
                if ($data['payment_method'] === 'qris') {
                    $qrData = $this->paymentModel->generateQRIS($orderId, $totalAmount, [
                        'name' => $orderData['customer_name'],
                        'email' => $orderData['customer_email'],
                        'phone' => $orderData['customer_phone']
                    ]);
                    $this->paymentModel->update($paymentId, [
                        'qr_string' => $qrData['qr_string'],
                        'qr_code' => $qrData['qr_code_url'],
                        'expired_at' => $qrData['expired_at']
                    ]);
                    $order['payment'] = array_merge(['id' => $paymentId], $qrData);
                } else if ($data['payment_method'] === 'transfer') {
                    // Use default bank from settings; Payment model will resolve overrides
                    $transferData = $this->paymentModel->generateBankTransfer($orderId, $totalAmount, 'default');
                    // Persist main identifiers for reference
                    $this->paymentModel->update($paymentId, [
                        'transaction_id' => $transferData['transaction_id'] ?? null,
                        'expired_at' => $transferData['expired_at'] ?? null
                    ]);
                    $order['payment'] = array_merge(['id' => $paymentId], $transferData);
                }
                
                // Send notification to staff
                $this->notificationModel->notifyNewOrder(
                    $orderId, 
                    $order['order_number'], 
                    $totalAmount
                );
                
                $this->sendSuccess($order, 'Order created successfully');
            } else {
                $this->sendError('Failed to create order', 500);
            }
        } catch (Exception $e) {
            $this->sendError('Order failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get setting value with fallback
     */
    private function getSettingValue($key, $default = '') {
        try {
            $sql = "SELECT setting_value FROM settings WHERE setting_key = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$key]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && isset($row['setting_value'])) {
                return $row['setting_value'];
            }
        } catch (Exception $e) {
            // ignore and fallback
        }
        return $default;
    }
    
    /**
     * Get order by order number (PUBLIC)
     */
    public function get() {
        $orderNumber = $_GET['order_number'] ?? '';
        $email = $_GET['email'] ?? '';
        
        if (empty($orderNumber) || empty($email)) {
            $this->sendError('Order number and email are required', 400);
        }
        
        $order = $this->orderModel->findByOrderNumber($orderNumber);
        
        if (!$order) {
            $this->sendError('Order not found', 404);
        }
        
        // Verify email matches
        if (strtolower($order['customer_email']) !== strtolower($email)) {
            $this->sendError('Invalid credentials', 403);
        }
        
        // Get order items
        $order['items'] = $this->orderModel->getItems($order['id']);
        
        // Get payment info
        $payment = $this->paymentModel->findByOrderId($order['id']);
        if ($payment) {
            $order['payment'] = $payment;
        }
        
        $this->sendSuccess($order);
    }

    /**
     * Get order by order number ONLY (PUBLIC)
     * For scenarios where email verification is not required.
     */
    public function getByNumber() {
        $orderNumber = $_GET['order_number'] ?? '';

        if (empty($orderNumber)) {
            $this->sendError('Order number is required', 400);
        }

        $order = $this->orderModel->findByOrderNumber($orderNumber);

        if (!$order) {
            $this->sendError('Order not found', 404);
        }

        // Attach order items
        $order['items'] = $this->orderModel->getItems($order['id']);

        // Attach payment info if exists
        $payment = $this->paymentModel->findByOrderId($order['id']);
        if ($payment) {
            $order['payment'] = $payment;
        }

        $this->sendSuccess($order);
    }
    
    /**
     * Get orders by email (PUBLIC)
     */
    public function getByEmail() {
        $email = $_GET['email'] ?? '';
        
        if (empty($email)) {
            $this->sendError('Email is required', 400);
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->sendError('Invalid email address', 400);
        }
        
        $orders = $this->orderModel->findByEmail($email);
        $this->sendSuccess($orders);
    }
    
    /**
     * Check payment status (PUBLIC)
     */
    public function checkPayment() {
        $orderNumber = $_GET['order_number'] ?? '';
        
        if (empty($orderNumber)) {
            $this->sendError('Order number is required', 400);
        }
        
        $order = $this->orderModel->findByOrderNumber($orderNumber);
        
        if (!$order) {
            $this->sendError('Order not found', 404);
        }
        
        $payment = $this->paymentModel->findByOrderId($order['id']);
        
        if (!$payment) {
            $this->sendError('Payment not found', 404);
        }
        
        $this->sendSuccess([
            'order_number' => $order['order_number'],
            'payment_status' => $order['payment_status'],
            'order_status' => $order['order_status'],
            'total_amount' => $order['total_amount'],
            'payment_method' => $payment['payment_method'],
            'paid_at' => $order['paid_at']
        ]);
    }
    
    /**
     * List orders (ADMIN ONLY)
     */
    public function list() {
        $this->checkAuthentication();
        $this->requireAdmin();
        
        $page = intval($_GET['page'] ?? 1);
        $limit = intval($_GET['limit'] ?? 20);
        $status = $_GET['status'] ?? null;
        $paymentStatus = $_GET['payment_status'] ?? null;
        $offset = ($page - 1) * $limit;
        
        $where = [];
        $params = [];
        if ($status) {
            $where[] = "o.order_status = ?";
            $params[] = $status;
        }
        if ($paymentStatus) {
            $where[] = "o.payment_status = ?";
            $params[] = $paymentStatus;
        }
        $whereSql = count($where) ? (" WHERE " . implode(" AND ", $where)) : "";

        // Query with items_count aggregated
        $sql = "SELECT o.*, COALESCE(SUM(oi.quantity), 0) AS items_count
                FROM orders o
                LEFT JOIN order_items oi ON oi.order_id = o.id
                $whereSql
                GROUP BY o.id
                ORDER BY o.created_at DESC
                LIMIT {$limit} OFFSET {$offset}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $orders = $stmt->fetchAll();

        // Total count without pagination (using BaseModel count for consistency)
        $conditions = [];
        if ($status) { $conditions['order_status'] = $status; }
        if ($paymentStatus) { $conditions['payment_status'] = $paymentStatus; }
        $total = $this->orderModel->count($conditions);
        
        $this->sendSuccess([
            'orders' => $orders,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }
    
    /**
     * Update order status (ADMIN ONLY)
     */
    public function updateStatus() {
        $this->checkAuthentication();
        $this->requireRole(['admin', 'manager']);
        
        if ($this->getMethod() !== 'POST') {
            $this->sendError('Method not allowed', 405);
        }
        
        $orderId = intval($_GET['id']);
        if (!$orderId) {
            $this->sendError('Order ID is required', 400);
        }
        
        $data = $this->getRequestData();
        $this->validateRequired($data, ['status']);
        
        $validStatuses = ['pending', 'processing', 'ready', 'completed', 'cancelled'];
        if (!in_array($data['status'], $validStatuses)) {
            $this->sendError('Invalid status', 400);
        }
        
        $order = $this->orderModel->find($orderId);
        if (!$order) {
            $this->sendError('Order not found', 404);
        }
        
        $success = $this->orderModel->updateOrderStatus($orderId, $data['status']);
        
        if ($success) {
            if ($data['status'] === 'cancelled') {
                $this->restoreStockForOrder($orderId, $order['order_number']);
                // Optional: mark payment as cancelled if exists
                try {
                    $payment = $this->paymentModel->findByOrderId($orderId);
                    if ($payment) {
                        $this->paymentModel->updateStatus($payment['id'], 'cancelled', $payment['transaction_id']);
                    }
                } catch (Exception $e) { /* ignore */ }
            }
            // Send notification about status change
            $this->notificationModel->notifyOrderStatusChange(
                $orderId,
                $order['order_number'],
                $data['status']
            );
            
            $this->logAction('update_order_status', 'orders', $orderId, 
                ['order_status' => $order['order_status']], 
                ['order_status' => $data['status']]
            );
            
            $this->sendSuccess(null, 'Order status updated successfully');
        } else {
            $this->sendError('Failed to update order status', 500);
        }
    }

    /**
     * Cancel order (PUBLIC)
     * Allows buyer to cancel pending orders; restores stock.
     */
    public function cancel() {
        if ($this->getMethod() !== 'POST') {
            $this->sendError('Method not allowed', 405);
        }
        $data = $this->getRequestData();
        $this->validateRequired($data, ['order_number']);

        $order = $this->orderModel->findByOrderNumber($data['order_number']);
        if (!$order) { $this->sendError('Order not found', 404); }

        // Only allow cancel if not completed
        $blockedStatuses = ['completed'];
        if (in_array(strtolower($order['order_status']), $blockedStatuses, true)) {
            $this->sendError('Order cannot be cancelled', 400);
        }

        // Update order status to cancelled
        $ok = $this->orderModel->updateOrderStatus($order['id'], 'cancelled');
        if (!$ok) { $this->sendError('Failed to cancel order', 500); }

        // Restore stock
        $this->restoreStockForOrder($order['id'], $order['order_number']);

        // Update payment status if exists
        try {
            $payment = $this->paymentModel->findByOrderId($order['id']);
            if ($payment && strtolower($payment['status']) !== 'success') {
                $this->paymentModel->updateStatus($payment['id'], 'cancelled', $payment['transaction_id']);
            }
        } catch (Exception $e) { /* ignore */ }

        $order['order_status'] = 'cancelled';
        $this->sendSuccess(['order_number' => $order['order_number'], 'order_status' => 'cancelled']);
    }

    /**
     * Restore stock for cancelled order
     */
    private function restoreStockForOrder($orderId, $orderNumber = null) {
        try {
            // Skip if already restored
            $check = $this->pdo->prepare("SELECT COUNT(*) AS c FROM stock_movements WHERE reference_type = 'order_cancel' AND reference_id = ?");
            $check->execute([$orderId]);
            $exists = (int)($check->fetch()['c'] ?? 0);
            if ($exists > 0) return;

            $items = $this->orderModel->getItems($orderId);
            foreach ($items as $it) {
                $pid = (int)$it['product_id'];
                $qty = (int)$it['quantity'];
                if ($pid > 0 && $qty > 0) {
                    $up = $this->pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
                    $up->execute([$qty, $pid]);

                    $ins = $this->pdo->prepare(
                        "INSERT INTO stock_movements (product_id, movement_type, quantity, reference_type, reference_id, user_id, notes)
                         VALUES (?, 'in', ?, 'order_cancel', ?, NULL, ?)"
                    );
                    $ins->execute([$pid, $qty, $orderId, 'Order cancelled ' . ($orderNumber ?? $orderId)]);
                }
            }
        } catch (Exception $e) {
            error_log('Restore stock failed for order ' . ($orderNumber ?? $orderId) . ': ' . $e->getMessage());
        }
    }
    
    /**
     * Get order statistics (ADMIN ONLY)
     */
    public function getStats() {
        $this->checkAuthentication();
        $this->requireAdmin();
        
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        
        $stats = $this->orderModel->getStats($startDate, $endDate);
        $this->sendSuccess($stats);
    }
}

// Handle requests
$orderController = new OrderController($pdo);

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'create':
        $orderController->create();
        break;
    case 'get':
        $orderController->get();
        break;
    case 'get-by-number':
        $orderController->getByNumber();
        break;
    case 'get-by-email':
        $orderController->getByEmail();
        break;
    case 'check-payment':
        $orderController->checkPayment();
        break;
    case 'list':
        $orderController->list();
        break;
    case 'update-status':
        $orderController->updateStatus();
        break;
    case 'cancel':
        $orderController->cancel();
        break;
    case 'stats':
        $orderController->getStats();
        break;
    default:
        $orderController->sendError('Invalid action', 400);
}

