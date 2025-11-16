<?php
/**
 * Payment Gateway Service
 * Handles integration with payment gateways (Midtrans, Xendit, Doku, etc.)
 * 
 * Support for:
 * - QRIS (QR Code Indonesia Standard)
 * - Bank Transfer
 * - Card Payment (Debit/Credit)
 */

class PaymentGatewayService {
    private $gateway;
    private $config;
    private $configOverride;
    
    public function __construct($gateway = 'midtrans', $configOverride = null) {
        $this->gateway = strtolower($gateway);
        $this->configOverride = is_array($configOverride) ? $configOverride : null;
        $this->loadConfig();
    }
    
    /**
     * Load payment gateway configuration
     */
    private function loadConfig() {
        // Load from environment or config file
        $this->config = [
            'gateway' => $this->gateway,
            'server_key' => $_ENV['PAYMENT_SERVER_KEY'] ?? getenv('PAYMENT_SERVER_KEY'),
            'client_key' => $_ENV['PAYMENT_CLIENT_KEY'] ?? getenv('PAYMENT_CLIENT_KEY'),
            'is_production' => ($_ENV['PAYMENT_IS_PRODUCTION'] ?? getenv('PAYMENT_IS_PRODUCTION')) === 'true',
            'merchant_id' => $_ENV['PAYMENT_MERCHANT_ID'] ?? getenv('PAYMENT_MERCHANT_ID'),
            // Bank account info for manual transfer
            'bank_accounts' => [
                'bca' => [
                    'name' => $_ENV['BANK_BCA_NAME'] ?? 'Your Business Name',
                    'account' => $_ENV['BANK_BCA_ACCOUNT'] ?? '1234567890',
                    'bank_name' => 'Bank Central Asia (BCA)'
                ],
                'bri' => [
                    'name' => $_ENV['BANK_MANDIRI_NAME'] ?? 'Your Business Name',
                    'account' => $_ENV['BANK_MANDIRI_ACCOUNT'] ?? '1234567890',
                    'bank_name' => 'Bank Rakyat Indonesia (BRI)'
                ],
                'blu_bca' => [
                    'name' => $_ENV['BANK_BNI_NAME'] ?? 'Your Business Name',
                    'account' => $_ENV['BANK_BNI_ACCOUNT'] ?? '1234567890',
                    'bank_name' => 'BLU BCA'
                ]
            ]
        ];

        // Merge override config from caller (e.g., settings from DB)
        if ($this->configOverride) {
            $this->config = array_replace_recursive($this->config, $this->configOverride);
        }
    }
    
    /**
     * Create QRIS payment
     * 
     * @param string $orderId Order/Transaction ID
     * @param float $amount Payment amount
     * @param array $customerData Customer information
     * @return array QRIS payment data
     */
    public function createQRIS($orderId, $amount, $customerData = []) {
        switch ($this->gateway) {
            case 'midtrans':
                return $this->createMidtransQRIS($orderId, $amount, $customerData);
            case 'xendit':
                return $this->createXenditQRIS($orderId, $amount, $customerData);
            case 'doku':
                return $this->createDokuQRIS($orderId, $amount, $customerData);
            default:
                // Fallback: Generate simple QR code for testing
                return $this->createSimpleQRIS($orderId, $amount);
        }
    }
    
    /**
     * Create Midtrans QRIS payment
     */
    private function createMidtransQRIS($orderId, $amount, $customerData) {
        $serverKey = $this->config['server_key'];
        $isProduction = $this->config['is_production'];
        $baseUrl = $isProduction 
            ? 'https://app.midtrans.com/snap/v1' 
            : 'https://app.sandbox.midtrans.com/snap/v1';
        
        $paymentData = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $amount
            ],
            'customer_details' => [
                'first_name' => $customerData['name'] ?? 'Customer',
                'email' => $customerData['email'] ?? '',
                'phone' => $customerData['phone'] ?? ''
            ],
            'item_details' => [
                [
                    'id' => $orderId,
                    'price' => $amount,
                    'quantity' => 1,
                    'name' => 'Payment for Order #' . $orderId
                ]
            ]
        ];
        
        $ch = curl_init($baseUrl . '/transactions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Basic ' . base64_encode($serverKey . ':')
            ],
            CURLOPT_POSTFIELDS => json_encode($paymentData)
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            
            // Get QR code from Midtrans
            if (isset($result['transaction_token'])) {
                // Get QR code from Snap token
                $qrCodeUrl = $this->getMidtransQRCode($result['transaction_token']);
                
                return [
                    'success' => true,
                    'transaction_id' => $result['transaction_id'] ?? $orderId,
                    'qr_string' => $qrCodeUrl,
                    'qr_code_url' => $qrCodeUrl,
                    'payment_url' => $result['redirect_url'] ?? null,
                    'expired_at' => date('Y-m-d H:i:s', strtotime('+24 hours')),
                    'gateway_response' => $result
                ];
            }
        }
        
        // Fallback if API fails
        return $this->createSimpleQRIS($orderId, $amount);
    }
    
    /**
     * Get Midtrans QR code from token
     */
    private function getMidtransQRCode($token) {
        // Midtrans Snap API provides QR code in response
        // For QRIS, use the qr_string from the response
        $baseUrl = $this->config['is_production'] 
            ? 'https://app.midtrans.com' 
            : 'https://app.sandbox.midtrans.com';
        
        // Generate QR code URL
        $qrString = $token;
        return "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($qrString);
    }
    
    /**
     * Create Xendit QRIS payment
     */
    private function createXenditQRIS($orderId, $amount, $customerData) {
        $secretKey = $this->config['server_key'];
        $baseUrl = $this->config['is_production'] 
            ? 'https://api.xendit.co' 
            : 'https://api.xendit.co'; // Xendit uses same URL for sandbox
        
        $paymentData = [
            'reference_id' => $orderId,
            'currency' => 'IDR',
            'amount' => $amount,
            'channel_code' => 'QRIS',
            'channel_properties' => [
                'success_redirect_url' => $_ENV['PAYMENT_SUCCESS_URL'] ?? 'http://localhost/success',
                'failure_redirect_url' => $_ENV['PAYMENT_FAILURE_URL'] ?? 'http://localhost/failure'
            ],
            'metadata' => [
                'order_id' => $orderId,
                'customer_name' => $customerData['name'] ?? 'Customer'
            ]
        ];
        
        $ch = curl_init($baseUrl . '/qr_codes');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode($secretKey . ':')
            ],
            CURLOPT_POSTFIELDS => json_encode($paymentData)
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 || $httpCode === 201) {
            $result = json_decode($response, true);
            
            return [
                'success' => true,
                'transaction_id' => $result['id'] ?? $orderId,
                'qr_string' => $result['qr_string'] ?? null,
                'qr_code_url' => $result['qr_code_url'] ?? null,
                'payment_url' => $result['actions']['qr_code'] ?? null,
                'expired_at' => isset($result['expires_at']) 
                    ? date('Y-m-d H:i:s', strtotime($result['expires_at'])) 
                    : date('Y-m-d H:i:s', strtotime('+24 hours')),
                'gateway_response' => $result
            ];
        }
        
        // Fallback if API fails
        return $this->createSimpleQRIS($orderId, $amount);
    }
    
    /**
     * Create Doku QRIS payment
     */
    private function createDokuQRIS($orderId, $amount, $customerData) {
        // Doku implementation similar to Midtrans
        // Placeholder for Doku integration
        return $this->createSimpleQRIS($orderId, $amount);
    }
    
    /**
     * Create simple QRIS (fallback/testing)
     */
    private function createSimpleQRIS($orderId, $amount) {
        // Generate QRIS string format
        $qrString = "00020101021126600019ID.CO.QRIS.WWW01189360001220000000000000203UKE5204499953033605406{$amount}5802ID6105123456304";
        $qrString .= substr(hash('sha256', $qrString . $this->config['server_key']), 0, 8);
        
        // Generate QR code URL using free service
        $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($qrString);
        
        return [
            'success' => true,
            'transaction_id' => $orderId . '-' . time(),
            'qr_string' => $qrString,
            'qr_code_url' => $qrCodeUrl,
            'payment_url' => null,
            'expired_at' => date('Y-m-d H:i:s', strtotime('+24 hours')),
            'gateway_response' => ['mode' => 'simple', 'note' => 'Using simple QR code generator']
        ];
    }
    
    /**
     * Create Bank Transfer payment
     * 
     * @param string $orderId Order/Transaction ID
     * @param float $amount Payment amount
     * @param string $bank Preferred bank (bca, bri, blu_bca)
     * @return array Bank transfer information
     */
    public function createBankTransfer($orderId, $amount, $bank = 'bca') {
        $bankInfo = $this->config['bank_accounts'][strtolower($bank)] ?? $this->config['bank_accounts']['bca'];

        // Toggle Virtual Account usage (default true unless explicitly disabled)
        $useVA = $this->config['use_virtual_account'] ?? true;

        if ($useVA) {
            // Generate virtual account number for automatic verification
            $virtualAccount = $this->generateVirtualAccount($orderId, $bank);
            return [
                'success' => true,
                'transaction_id' => $orderId . '-VA-' . time(),
                'bank_name' => $bankInfo['bank_name'],
                'account_name' => $bankInfo['name'],
                'account_number' => $virtualAccount['account_number'],
                'virtual_account' => $virtualAccount['va_number'],
                'reference_number' => $virtualAccount['reference'],
                'transfer_amount' => $amount,
                'instructions' => "Transfer ke rekening {$bankInfo['bank_name']} a/n {$bankInfo['name']}",
                'expired_at' => date('Y-m-d H:i:s', strtotime('+24 hours')),
                'gateway_response' => ['mode' => 'manual', 'bank' => $bank, 'use_virtual_account' => true]
            ];
        }

        // Manual bank transfer (no virtual account)
        $reference = 'PAY-' . $orderId . '-' . time();
        return [
            'success' => true,
            'transaction_id' => $orderId . '-TRF-' . time(),
            'bank_name' => $bankInfo['bank_name'],
            'account_name' => $bankInfo['name'],
            'account_number' => $bankInfo['account'],
            'virtual_account' => null,
            'reference_number' => $reference,
            'transfer_amount' => $amount,
            'instructions' => "Transfer ke rekening {$bankInfo['bank_name']} a/n {$bankInfo['name']} No: {$bankInfo['account']}",
            'expired_at' => date('Y-m-d H:i:s', strtotime('+24 hours')),
            'gateway_response' => ['mode' => 'manual', 'bank' => $bank, 'use_virtual_account' => false]
        ];
    }
    
    /**
     * Generate virtual account number
     */
    private function generateVirtualAccount($orderId, $bank) {
        // Format: Bank Code (3 digits) + Order ID (last 8 digits) + Check Digit (1 digit)
        $bankCodes = [
            'bca' => '014',
            'bri' => '002',
            'blu_bca' => '501'
        ];
        
        $bankCode = $bankCodes[strtolower($bank)] ?? '014';
        $orderSuffix = substr(str_pad($orderId, 8, '0', STR_PAD_LEFT), -8);
        $virtualAccount = $bankCode . $orderSuffix;
        
        // Add check digit
        $checkDigit = $this->calculateCheckDigit($virtualAccount);
        $vaNumber = $virtualAccount . $checkDigit;
        
        return [
            'va_number' => $vaNumber,
            'account_number' => $this->config['bank_accounts'][strtolower($bank)]['account'] ?? $vaNumber,
            'reference' => 'PAY-' . $orderId . '-' . time()
        ];
    }
    
    /**
     * Calculate check digit for virtual account
     */
    private function calculateCheckDigit($number) {
        $sum = 0;
        $weights = [1, 2, 1, 2, 1, 2, 1, 2, 1, 2, 1];
        
        for ($i = 0; $i < strlen($number); $i++) {
            $digit = intval($number[$i]);
            $weighted = $digit * $weights[$i];
            $sum += ($weighted > 9) ? $weighted - 9 : $weighted;
        }
        
        return (10 - ($sum % 10)) % 10;
    }
    
    /**
     * Create Card Payment (Debit/Credit)
     * 
     * @param string $orderId Order/Transaction ID
     * @param float $amount Payment amount
     * @param array $customerData Customer information
     * @return array Card payment data
     */
    public function createCardPayment($orderId, $amount, $customerData = []) {
        switch ($this->gateway) {
            case 'midtrans':
                return $this->createMidtransCard($orderId, $amount, $customerData);
            case 'xendit':
                return $this->createXenditCard($orderId, $amount, $customerData);
            default:
                // Fallback: Return payment reference
                return [
                    'success' => true,
                    'transaction_id' => $orderId . '-CARD-' . time(),
                    'payment_url' => null,
                    'redirect_url' => null,
                    'reference_number' => 'CARD-' . $orderId . '-' . time(),
                    'instructions' => 'Swipe/insert card in payment terminal',
                    'expired_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
                    'gateway_response' => ['mode' => 'manual', 'note' => 'Card payment terminal required']
                ];
        }
    }
    
    /**
     * Create Midtrans Card payment
     */
    private function createMidtransCard($orderId, $amount, $customerData) {
        $serverKey = $this->config['server_key'];
        $isProduction = $this->config['is_production'];
        $baseUrl = $isProduction 
            ? 'https://app.midtrans.com/snap/v1' 
            : 'https://app.sandbox.midtrans.com/snap/v1';
        
        $paymentData = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $amount
            ],
            'credit_card' => [
                'secure' => true,
                'bank' => 'bca', // or 'mandiri', 'bni', etc.
                'installment' => [
                    'required' => false,
                    'terms' => []
                ]
            ],
            'customer_details' => [
                'first_name' => $customerData['name'] ?? 'Customer',
                'email' => $customerData['email'] ?? '',
                'phone' => $customerData['phone'] ?? ''
            ]
        ];
        
        $ch = curl_init($baseUrl . '/transactions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Basic ' . base64_encode($serverKey . ':')
            ],
            CURLOPT_POSTFIELDS => json_encode($paymentData)
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            
            return [
                'success' => true,
                'transaction_id' => $result['transaction_id'] ?? $orderId,
                'payment_url' => $result['redirect_url'] ?? null,
                'redirect_url' => $result['redirect_url'] ?? null,
                'token' => $result['token'] ?? null,
                'reference_number' => $result['transaction_id'] ?? 'CARD-' . $orderId,
                'instructions' => 'Complete payment via card',
                'expired_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
                'gateway_response' => $result
            ];
        }
        
        // Fallback
        return [
            'success' => true,
            'transaction_id' => $orderId . '-CARD-' . time(),
            'payment_url' => null,
            'reference_number' => 'CARD-' . $orderId . '-' . time(),
            'instructions' => 'Card payment terminal required',
            'expired_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
            'gateway_response' => ['mode' => 'manual']
        ];
    }
    
    /**
     * Create Xendit Card payment
     */
    private function createXenditCard($orderId, $amount, $customerData) {
        // Similar to Midtrans but using Xendit API
        // Implementation can be added here
        return [
            'success' => true,
            'transaction_id' => $orderId . '-CARD-' . time(),
            'reference_number' => 'CARD-' . $orderId . '-' . time(),
            'instructions' => 'Complete payment via card',
            'expired_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
            'gateway_response' => ['mode' => 'manual', 'note' => 'Xendit card integration needed']
        ];
    }
    
    /**
     * Verify payment status
     * 
     * @param string $transactionId Transaction ID from gateway
     * @return array Payment status
     */
    public function verifyPayment($transactionId) {
        switch ($this->gateway) {
            case 'midtrans':
                return $this->verifyMidtrans($transactionId);
            case 'xendit':
                return $this->verifyXendit($transactionId);
            default:
                return [
                    'status' => 'unknown',
                    'message' => 'Payment verification not available for this gateway'
                ];
        }
    }
    
    /**
     * Verify Midtrans payment
     */
    private function verifyMidtrans($transactionId) {
        $serverKey = $this->config['server_key'];
        $isProduction = $this->config['is_production'];
        $baseUrl = $isProduction 
            ? 'https://api.midtrans.com' 
            : 'https://api.sandbox.midtrans.com';
        
        $ch = curl_init($baseUrl . '/v2/' . $transactionId . '/status');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Authorization: Basic ' . base64_encode($serverKey . ':')
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            
            return [
                'status' => $this->mapStatus($result['transaction_status'] ?? 'unknown'),
                'transaction_status' => $result['transaction_status'] ?? 'unknown',
                'payment_type' => $result['payment_type'] ?? null,
                'gross_amount' => $result['gross_amount'] ?? null,
                'transaction_time' => $result['transaction_time'] ?? null,
                'raw_response' => $result
            ];
        }
        
        return [
            'status' => 'error',
            'message' => 'Failed to verify payment'
        ];
    }
    
    /**
     * Verify Xendit payment
     */
    private function verifyXendit($transactionId) {
        // Similar implementation for Xendit
        return [
            'status' => 'unknown',
            'message' => 'Xendit verification not implemented'
        ];
    }
    
    /**
     * Map gateway status to internal status
     */
    private function mapStatus($gatewayStatus) {
        $statusMap = [
            'settlement' => 'success',
            'capture' => 'success',
            'success' => 'success',
            'paid' => 'success',
            'pending' => 'pending',
            'deny' => 'failed',
            'cancel' => 'failed',
            'expire' => 'expired',
            'failure' => 'failed'
        ];
        
        return $statusMap[strtolower($gatewayStatus)] ?? 'pending';
    }
    
    /**
     * Get payment instructions based on method
     */
    public function getPaymentInstructions($method, $paymentData) {
        switch (strtolower($method)) {
            case 'qris':
                return [
                    'title' => 'Scan QR Code untuk Pembayaran',
                    'steps' => [
                        'Buka aplikasi e-wallet atau mobile banking Anda',
                        'Pilih menu Scan QRIS',
                        'Scan QR code yang ditampilkan',
                        'Pastikan nominal pembayaran sesuai',
                        'Konfirmasi pembayaran'
                    ],
                    'qr_code' => $paymentData['qr_code_url'] ?? null,
                    'expired_at' => $paymentData['expired_at'] ?? null
                ];
                
            case 'transfer':
                return [
                    'title' => 'Transfer Bank untuk Pembayaran',
                    'steps' => [
                        'Transfer sesuai nominal: ' . number_format($paymentData['transfer_amount'], 0, ',', '.'),
                        'Rekening: ' . $paymentData['bank_name'],
                        'A/N: ' . $paymentData['account_name'],
                        'No. Rekening: ' . $paymentData['account_number'],
                        'Virtual Account: ' . ($paymentData['virtual_account'] ?? '-'),
                        'Referensi: ' . ($paymentData['reference_number'] ?? '-'),
                        'Kirim bukti transfer jika diperlukan'
                    ],
                    'bank_info' => [
                        'bank_name' => $paymentData['bank_name'],
                        'account_name' => $paymentData['account_name'],
                        'account_number' => $paymentData['account_number']
                    ],
                    'expired_at' => $paymentData['expired_at'] ?? null
                ];
                
            case 'card':
                return [
                    'title' => 'Pembayaran dengan Kartu',
                    'steps' => [
                        'Swipe atau insert kartu debit/kredit',
                        'Masukkan PIN (jika diperlukan)',
                        'Tunggu konfirmasi pembayaran',
                        'Ambil struk jika berhasil'
                    ],
                    'reference' => $paymentData['reference_number'] ?? null,
                    'expired_at' => $paymentData['expired_at'] ?? null
                ];
                
            default:
                return [
                    'title' => 'Instruksi Pembayaran',
                    'steps' => ['Silakan hubungi kasir untuk instruksi pembayaran']
                ];
        }
    }
}

