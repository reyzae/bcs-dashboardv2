<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/BaseController.php';

/**
 * Bytebalok Payment Controller
 * Handles payment processing and webhooks
 */

class PaymentController extends BaseController {
    private $paymentModel;
    private $orderModel;
    private $notificationModel;
    private $productModel;
    
    public function __construct($pdo) {
        parent::__construct($pdo);
        $this->paymentModel = new Payment($pdo);
        $this->orderModel = new Order($pdo);
        $this->notificationModel = new Notification($pdo);
        $this->productModel = new Product($pdo);
    }
    
    /**
     * Get payment by order ID (PUBLIC)
     */
    public function getByOrderId() {
        $orderId = intval($_GET['order_id']);
        
        if (!$orderId) {
            $this->sendError('Order ID is required', 400);
        }
        
        $payment = $this->paymentModel->findByOrderId($orderId);
        
        if (!$payment) {
            $this->sendError('Payment not found', 404);
        }
        
        $this->sendSuccess($payment);
    }
    
    /**
     * Simulate payment (for testing - PUBLIC)
     */
    public function simulatePayment() {
        if ($this->getMethod() !== 'POST') {
            $this->sendError('Method not allowed', 405);
        }
        
        $data = $this->getRequestData();
        $this->validateRequired($data, ['order_number']);
        
        $order = $this->orderModel->findByOrderNumber($data['order_number']);
        
        if (!$order) {
            $this->sendError('Order not found', 404);
        }
        
        if ($order['payment_status'] === 'paid') {
            $this->sendError('Order already paid', 400);
        }
        
        $payment = $this->paymentModel->findByOrderId($order['id']);
        
        if (!$payment) {
            $this->sendError('Payment not found', 404);
        }
        
        // Simulate payment success
        $transactionId = 'SIM' . time() . rand(1000, 9999);
        
        try {
            $this->pdo->beginTransaction();
            
            // Update payment status
            $this->paymentModel->updateStatus(
                $payment['id'],
                'success',
                $transactionId,
                ['simulated' => true, 'timestamp' => date('Y-m-d H:i:s')]
            );
            
            // Update order payment status
            $this->orderModel->updatePaymentStatus($order['id'], 'paid', $transactionId);

            // Reduce stock for order items if not already reduced
            $this->reduceOrderStockIfNeeded($order['id'], $order['order_number']);

            // Send notification
            $this->notificationModel->notifyPaymentReceived(
                $order['id'],
                $order['order_number'],
                $order['total_amount']
            );
            
            $this->pdo->commit();
            
            $this->sendSuccess([
                'order_number' => $order['order_number'],
                'transaction_id' => $transactionId,
                'status' => 'success',
                'message' => 'Payment simulated successfully'
            ]);
        } catch (Exception $e) {
            $this->pdo->rollback();
            $this->sendError('Payment simulation failed: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Payment webhook callback (for payment gateway integration)
     */
    public function webhook() {
        if ($this->getMethod() !== 'POST') {
            $this->sendError('Method not allowed', 405);
        }
        
        // Get raw POST data
        $rawData = file_get_contents('php://input');
        $data = json_decode($rawData, true);
        
        // Log webhook data for debugging
        error_log('Payment Webhook: ' . $rawData);
        
        if (!$data) {
            $this->sendError('Invalid webhook data', 400);
        }
        
        // Validate webhook signature (implement based on payment gateway)
        // Example: Midtrans signature validation
        // $this->validateWebhookSignature($data);
        
        try {
            // Extract payment information
            $transactionId = $data['transaction_id'] ?? $data['order_id'] ?? null;
            $status = $data['transaction_status'] ?? $data['status'] ?? null;
            
            if (!$transactionId || !$status) {
                $this->sendError('Missing required webhook data', 400);
            }
            
            // Find payment by transaction ID or order number
            $payment = $this->paymentModel->findByTransactionId($transactionId);
            
            if (!$payment) {
                // Try to find by order number
                $orderNumber = $data['order_number'] ?? null;
                if ($orderNumber) {
                    $order = $this->orderModel->findByOrderNumber($orderNumber);
                    if ($order) {
                        $payment = $this->paymentModel->findByOrderId($order['id']);
                    }
                }
            }
            
            if (!$payment) {
                $this->sendError('Payment not found', 404);
            }
            
            $order = $this->orderModel->find($payment['order_id']);
            
            if (!$order) {
                $this->sendError('Order not found', 404);
            }
            
            $this->pdo->beginTransaction();
            
            // Map payment gateway status to our status
            $paymentStatus = $this->mapPaymentStatus($status);
            
            // Update payment status
            $this->paymentModel->updateStatus(
                $payment['id'],
                $paymentStatus,
                $transactionId,
                $data
            );
            
            // Update order if payment is successful
            if ($paymentStatus === 'success') {
                $this->orderModel->updatePaymentStatus($order['id'], 'paid', $transactionId);

                // Reduce stock for order items if not already reduced
                $this->reduceOrderStockIfNeeded($order['id'], $order['order_number']);

                // Send notification
                $this->notificationModel->notifyPaymentReceived(
                    $order['id'],
                    $order['order_number'],
                    $order['total_amount']
                );
            }
            
            $this->pdo->commit();
            
            // Return success response to payment gateway
            $this->sendSuccess([
                'status' => 'success',
                'message' => 'Webhook processed successfully'
            ]);
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            error_log('Webhook processing error: ' . $e->getMessage());
            $this->sendError('Webhook processing failed', 500);
        }
    }
    
    /**
     * Check payment status (PUBLIC)
     */
    public function checkStatus() {
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
        
        // Extract optional manual flags from callback_data
        $flags = [];
        if (!empty($payment['callback_data'])) {
            try {
                $cb = json_decode($payment['callback_data'], true);
                if (is_array($cb)) {
                    $flags['qr_scanned'] = (bool)($cb['qr_scanned']['value'] ?? $cb['qr_scanned'] ?? false);
                    $flags['transfer_received'] = (bool)($cb['transfer_received']['value'] ?? $cb['transfer_received'] ?? false);
                    $flags['transfer_status'] = $cb['transfer_status'] ?? null;
                }
            } catch (Exception $e) {
                // ignore
            }
        }

        $this->sendSuccess(array_merge([
            'order_number' => $order['order_number'],
            'payment_status' => $order['payment_status'],
            'order_status' => $order['order_status'],
            'payment_method' => $payment['payment_method'],
            'amount' => $payment['amount'],
            'transaction_id' => $payment['transaction_id'],
            'paid_at' => $order['paid_at'],
            'qr_code' => $payment['qr_code'],
            'expired_at' => $payment['expired_at']
        ], $flags));
    }
    
    /**
     * Get payment statistics (ADMIN ONLY)
     */
    public function getStats() {
        $this->checkAuthentication();
        $this->requireAdmin();
        
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        
        $stats = $this->paymentModel->getStats($startDate, $endDate);
        $this->sendSuccess($stats);
    }
    
    /**
     * Map payment gateway status to internal status
     */
    private function mapPaymentStatus($gatewayStatus) {
        $statusMap = [
            'capture' => 'success',
            'settlement' => 'success',
            'success' => 'success',
            'paid' => 'success',
            'pending' => 'pending',
            'deny' => 'failed',
            'cancel' => 'failed',
            'expire' => 'expired',
            'expired' => 'expired',
            'failure' => 'failed'
        ];
        
        return $statusMap[strtolower($gatewayStatus)] ?? 'pending';
    }

    /**
     * Reduce stock for order items exactly once per order
     */
    private function reduceOrderStockIfNeeded($orderId, $orderNumber) {
        try {
            // Check if stock movements already recorded for this order
            $sql = "SELECT COUNT(*) as count FROM stock_movements WHERE reference_type = 'order' AND reference_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$orderId]);
            $exists = (int)($stmt->fetch()['count'] ?? 0);
            if ($exists > 0) {
                // Already reduced, skip
                return;
            }

            // Fetch order items
            $items = $this->orderModel->getItems($orderId);
            if (!is_array($items) || empty($items)) {
                return;
            }

            // Reduce stock for each item (movement_type 'out', quantity negative)
            foreach ($items as $item) {
                $productId = (int)$item['product_id'];
                $quantity = (int)$item['quantity'];
                if ($productId > 0 && $quantity > 0) {
                    $this->productModel->updateStock(
                        $productId,
                        -$quantity,
                        'out',
                        'order',
                        $orderId,
                        null,
                        'Order ' . $orderNumber
                    );
                }
            }
        } catch (Exception $e) {
            // Log but do not fail payment flow
            error_log('Failed reducing stock for order ' . $orderNumber . ': ' . $e->getMessage());
        }
    }
    
    /**
     * Validate webhook signature (implement based on payment gateway)
     */
    private function validateWebhookSignature($data) {
        // Example for Midtrans:
        // $serverKey = $_ENV['MIDTRANS_SERVER_KEY'];
        // $signatureKey = hash('sha512', $data['order_id'] . $data['status_code'] . $data['gross_amount'] . $serverKey);
        // if ($data['signature_key'] !== $signatureKey) {
        //     $this->sendError('Invalid signature', 401);
        // }
        
        // Implement based on your payment gateway requirements
        return true;
    }

    /**
     * Manual update endpoint (PUBLIC)
     * Allows setting intermediate flags or confirming payment received.
     * Expected JSON body: { order_number, flag?, note?, confirm_paid? }
     */
    public function manualUpdate() {
        if ($this->getMethod() !== 'POST') {
            $this->sendError('Method not allowed', 405);
        }

        $data = $this->getRequestData();
        $this->validateRequired($data, ['order_number']);

        $order = $this->orderModel->findByOrderNumber($data['order_number']);
        if (!$order) {
            $this->sendError('Order not found', 404);
        }

        $payment = $this->paymentModel->findByOrderId($order['id']);
        if (!$payment) {
            $this->sendError('Payment not found', 404);
        }

        // Build callback_data with flags
        $existingCb = [];
        if (!empty($payment['callback_data'])) {
            try { $existingCb = json_decode($payment['callback_data'], true) ?: []; } catch (Exception $e) { $existingCb = []; }
        }

        // Accept either `flag` or `requestVerification` from client
        $flag = $data['flag'] ?? ($data['requestVerification'] ?? null); // e.g. 'qr_scanned', 'transfer_received'
        $note = $data['note'] ?? null;
        $now = date('Y-m-d H:i:s');

        if ($flag) {
            $allowed = ['qr_scanned', 'transfer_received'];
            if (!in_array($flag, $allowed, true)) {
                $this->sendError('Unsupported flag', 400);
            }
            $existingCb[$flag] = [ 'value' => true, 'timestamp' => $now ];
            if ($flag === 'transfer_received') {
                $existingCb['transfer_status'] = 'received';
            }
        }

        if ($note) {
            $existingCb['manual_note'] = $note;
            $existingCb['manual_note_at'] = $now;
        }

        $confirmPaid = false;
        $confirmParam = $data['confirm_paid'] ?? null;
        if (is_string($confirmParam)) {
            $confirmPaid = in_array(strtolower($confirmParam), ['1','true','yes','paid'], true);
        } elseif (is_bool($confirmParam)) {
            $confirmPaid = $confirmParam;
        }

        try {
            $this->pdo->beginTransaction();

            if ($confirmPaid) {
                // Only privileged users should confirm payments
                $this->checkAuthentication();
                // Set payment success and update order
                $this->paymentModel->updateStatus($payment['id'], 'success', $payment['transaction_id'], $existingCb);
                $this->orderModel->updatePaymentStatus($order['id'], 'paid', $payment['transaction_id']);

                // Reduce stock exactly once
                $this->reduceOrderStockIfNeeded($order['id'], $order['order_number']);

                // Notify
                $this->notificationModel->notifyPaymentReceived(
                    $order['id'],
                    $order['order_number'],
                    $order['total_amount']
                );
            } else {
                // Update callback_data flags only, keep payment and order status unchanged (enum-safe)
                $this->paymentModel->updateStatus($payment['id'], $payment['status'] ?? 'pending', $payment['transaction_id'], $existingCb);
            }

            $this->pdo->commit();

            $this->sendSuccess([
                'order_number' => $order['order_number'],
                'payment_status' => $confirmPaid ? 'paid' : $order['payment_status'],
                'requested_verification' => (bool)$flag,
                'flags' => $existingCb
            ]);
        } catch (Exception $e) {
            $this->pdo->rollback();
            $this->sendError('Manual update failed: ' . $e->getMessage(), 500);
        }
    }
}

// Handle requests
$paymentController = new PaymentController($pdo);

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get-by-order':
        $paymentController->getByOrderId();
        break;
    case 'simulate':
        $paymentController->simulatePayment();
        break;
    case 'webhook':
        $paymentController->webhook();
        break;
    case 'check-status':
        $paymentController->checkStatus();
        break;
    case 'manual-update':
    case 'manualUpdate':
        $paymentController->manualUpdate();
        break;
    case 'stats':
        $paymentController->getStats();
        break;
    default:
        $paymentController->sendError('Invalid action', 400);
}

