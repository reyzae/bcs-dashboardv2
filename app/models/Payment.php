<?php
require_once __DIR__ . '/BaseModel.php';

/**
 * Bytebalok Payment Model
 * Handles payment transactions and QR code generation
 */

class Payment extends BaseModel {
    protected $table = 'payments';
    protected $fillable = [
        'order_id', 'payment_method', 'amount', 'status', 'transaction_id',
        'qr_code', 'qr_string', 'payment_url', 'expired_at', 'paid_at', 'callback_data'
    ];
    
    /**
     * Create payment record
     */
    public function createPayment($orderData) {
        $paymentData = [
            'order_id' => $orderData['order_id'],
            'payment_method' => $orderData['payment_method'],
            'amount' => $orderData['amount'],
            'status' => 'pending',
            'expired_at' => date('Y-m-d H:i:s', strtotime('+24 hours'))
        ];
        
        return $this->create($paymentData);
    }
    
    /**
     * Generate QRIS code using Payment Gateway Service
     */
    public function generateQRIS($orderId, $amount, $customerData = []) {
        require_once __DIR__ . '/../services/PaymentGatewayService.php';
        
        // 1) Check if static QRIS is enabled via settings/env
        $staticEnabledEnv = strtolower((string)($_ENV['QRIS_STATIC_ENABLED'] ?? getenv('QRIS_STATIC_ENABLED') ?? ''));
        $staticEnabled = in_array($staticEnabledEnv, ['1','true','yes'], true);
        $staticImageUrl = $_ENV['QRIS_STATIC_IMAGE_URL'] ?? getenv('QRIS_STATIC_IMAGE_URL') ?? null;

        try {
            $result = $this->pdo->query("SHOW TABLES LIKE 'settings'");
            if ($result && $result->rowCount() > 0) {
                $sql = "SELECT `key`, `value` FROM settings WHERE `key` IN ('qris_static_enabled','qris_static_image_url')";
                $stmt = $this->pdo->query($sql);
                $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $map = [];
                foreach ($settings as $s) { $map[$s['key']] = $s['value']; }
                if (isset($map['qris_static_enabled'])) {
                    $val = strtolower(trim((string)$map['qris_static_enabled']));
                    $staticEnabled = in_array($val, ['1','true','yes'], true);
                }
                if (isset($map['qris_static_image_url']) && $map['qris_static_image_url']) {
                    $staticImageUrl = $map['qris_static_image_url'];
                }
            }
        } catch (Exception $e) {
            // Ignore and proceed
        }

        if ($staticEnabled && $staticImageUrl) {
            // Serve static QR image without hitting gateway
            return [
                'qr_string' => null,
                'qr_code_url' => $staticImageUrl,
                'payment_url' => null,
                'transaction_id' => null,
                'expired_at' => date('Y-m-d H:i:s', strtotime('+24 hours')),
                'gateway_response' => ['mode' => 'static', 'source' => 'settings']
            ];
        }

        // 2) Fallback to gateway-generated QRIS
        $gateway = $_ENV['PAYMENT_GATEWAY'] ?? 'midtrans';
        $gatewayService = new PaymentGatewayService($gateway);
        $result = $gatewayService->createQRIS($orderId, $amount, $customerData);
        return [
            'qr_string' => $result['qr_string'] ?? null,
            'qr_code_url' => $result['qr_code_url'] ?? null,
            'payment_url' => $result['payment_url'] ?? null,
            'transaction_id' => $result['transaction_id'] ?? null,
            'expired_at' => $result['expired_at'] ?? date('Y-m-d H:i:s', strtotime('+24 hours')),
            'gateway_response' => $result['gateway_response'] ?? null
        ];
    }
    
    /**
     * Generate Bank Transfer payment info
     */
    public function generateBankTransfer($orderId, $amount, $bank = 'bca') {
        require_once __DIR__ . '/../services/PaymentGatewayService.php';
        
        $gateway = $_ENV['PAYMENT_GATEWAY'] ?? 'midtrans';

        // Load bank account settings from DB (if available)
        $bankAccounts = [
            'bca' => [ 'name' => 'Your Business Name', 'account' => '1234567890', 'bank_name' => 'Bank Central Asia (BCA)' ],
            'bri' => [ 'name' => 'Your Business Name', 'account' => '1234567890', 'bank_name' => 'Bank Rakyat Indonesia (BRI)' ],
            'blu_bca' => [ 'name' => 'Your Business Name', 'account' => '1234567890', 'bank_name' => 'BLU BCA' ]
        ];

        try {
            // Check settings table exists
            $result = $this->pdo->query("SHOW TABLES LIKE 'settings'");
            if ($result && $result->rowCount() > 0) {
                // Kolom pada tabel settings adalah `key` dan `value` (kata kunci MySQL), gunakan backtick
                $sql = "SELECT `key`, `value` FROM settings WHERE `key` IN (
                    'bank_default','bank_bca_name','bank_bca_account',
                    'bank_bri_name','bank_bri_account','bank_blu_bca_name','bank_blu_bca_account',
                    'bank_mandiri_name','bank_mandiri_account','bank_bni_name','bank_bni_account'
                )";
                $stmt = $this->pdo->query($sql);
                $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $map = [];
                foreach ($settings as $s) { $map[$s['key']] = $s['value']; }

                // Override bank accounts with settings
                if (!empty($map['bank_bca_name'])) $bankAccounts['bca']['name'] = $map['bank_bca_name'];
                if (!empty($map['bank_bca_account'])) $bankAccounts['bca']['account'] = $map['bank_bca_account'];
                // Prefer new keys, fallback to old
                $bankAccounts['bri']['name'] = $map['bank_bri_name'] ?? $map['bank_mandiri_name'] ?? $bankAccounts['bri']['name'];
                $bankAccounts['bri']['account'] = $map['bank_bri_account'] ?? $map['bank_mandiri_account'] ?? $bankAccounts['bri']['account'];
                $bankAccounts['blu_bca']['name'] = $map['bank_blu_bca_name'] ?? $map['bank_bni_name'] ?? $bankAccounts['blu_bca']['name'];
                $bankAccounts['blu_bca']['account'] = $map['bank_blu_bca_account'] ?? $map['bank_bni_account'] ?? $bankAccounts['blu_bca']['account'];

                // If bank is empty or 'default', use default from settings
                if (!$bank || strtolower($bank) === 'default') {
                    $bank = $map['bank_default'] ?? 'bca';
                    if ($bank === 'mandiri') $bank = 'bri';
                    if ($bank === 'bni') $bank = 'blu_bca';
                }

                // Read toggle to use virtual account (default disabled unless explicitly enabled)
                $useVASetting = isset($map['transfer_use_va']) ? strtolower(trim((string)$map['transfer_use_va'])) : null;
                $useVA = false; // default: disabled per user request
                if ($useVASetting !== null) {
                    $useVA = in_array($useVASetting, ['1','true','yes'], true);
                }
            }
        } catch (Exception $e) {
            // Fallback silently
        }

        // Ensure sensible defaults if settings/env are absent
        if ($bankAccounts['bca']['name'] === 'Your Business Name') {
            $bankAccounts['bca']['name'] = $_ENV['BANK_BCA_NAME'] ?? getenv('BANK_BCA_NAME') ?? 'REYZA WIRAKUSUMA';
        }
        if ($bankAccounts['bca']['account'] === '1234567890') {
            $bankAccounts['bca']['account'] = $_ENV['BANK_BCA_ACCOUNT'] ?? getenv('BANK_BCA_ACCOUNT') ?? '1481899929';
        }

        // Env override for VA if desired
        $envUseVA = $_ENV['TRANSFER_USE_VA'] ?? getenv('TRANSFER_USE_VA');
        if ($envUseVA !== null) {
            $useVA = in_array(strtolower((string)$envUseVA), ['1','true','yes'], true);
        }

        $configOverride = [ 'bank_accounts' => $bankAccounts, 'use_virtual_account' => $useVA ];
        $gatewayService = new PaymentGatewayService($gateway, $configOverride);
        
        $result = $gatewayService->createBankTransfer($orderId, $amount, $bank);
        
        return $result;
    }
    
    /**
     * Generate Card Payment info
     */
    public function generateCardPayment($orderId, $amount, $customerData = []) {
        require_once __DIR__ . '/../services/PaymentGatewayService.php';
        
        $gateway = $_ENV['PAYMENT_GATEWAY'] ?? 'midtrans';
        $gatewayService = new PaymentGatewayService($gateway);
        
        $result = $gatewayService->createCardPayment($orderId, $amount, $customerData);
        
        return $result;
    }
    
    /**
     * Update payment status
     */
    public function updateStatus($paymentId, $status, $transactionId = null, $callbackData = null) {
        $updateData = ['status' => $status];
        
        if ($transactionId) {
            $updateData['transaction_id'] = $transactionId;
        }
        
        if ($callbackData) {
            $updateData['callback_data'] = json_encode($callbackData);
        }
        
        if ($status === 'success') {
            $updateData['paid_at'] = date('Y-m-d H:i:s');
        }
        
        return $this->update($paymentId, $updateData);
    }
    
    /**
     * Get payment by order ID
     */
    public function findByOrderId($orderId) {
        $sql = "SELECT * FROM {$this->table} WHERE order_id = ? ORDER BY created_at DESC LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$orderId]);
        return $stmt->fetch();
    }
    
    /**
     * Get payment by transaction ID
     */
    public function findByTransactionId($transactionId) {
        $sql = "SELECT * FROM {$this->table} WHERE transaction_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$transactionId]);
        return $stmt->fetch();
    }
    
    /**
     * Check expired payments and update status
     */
    public function checkExpiredPayments() {
        $sql = "UPDATE {$this->table} 
                SET status = 'expired' 
                WHERE status = 'pending' 
                AND expired_at < NOW()";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute();
    }
    
    /**
     * Get pending payments
     */
    public function getPendingPayments() {
        return $this->findAll(['status' => 'pending'], 'created_at DESC');
    }
    
    /**
     * Get payment statistics
     */
    public function getStats($startDate = null, $endDate = null) {
        $sql = "SELECT 
                    COUNT(*) as total_payments,
                    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful_payments,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_payments,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_payments,
                    SUM(CASE WHEN status = 'success' THEN amount ELSE 0 END) as total_amount,
                    AVG(CASE WHEN status = 'success' THEN amount ELSE NULL END) as average_amount
                FROM {$this->table}";
        $params = [];
        
        if ($startDate) {
            $sql .= " WHERE DATE(created_at) >= ?";
            $params[] = $startDate;
            if ($endDate) {
                $sql .= " AND DATE(created_at) <= ?";
                $params[] = $endDate;
            }
        } elseif ($endDate) {
            $sql .= " WHERE DATE(created_at) <= ?";
            $params[] = $endDate;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
}

