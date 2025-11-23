<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/BaseController.php';

/**
 * Settings Controller
 * Handles system settings operations
 */
class SettingsController extends BaseController {
    
    public function __construct($pdo) {
        parent::__construct($pdo);
    }
    
    /**
     * Get public settings (without authentication)
     * Used for shop pages
     */
    public function get_public() {
        try {
            // Return only safe, public settings
            $publicSettings = [
                'cod_enabled' => '0', // Default COD disabled
                'cod_min_amount' => '50000',
                'cod_max_amount' => '1000000',
                'tax_enabled' => '0', // Default tax disabled (shop-specific)
                'tax_rate' => '11', // Default 11% (shop-specific)
                'tax_name' => 'Pajak' // Default tax name (shop-specific)
            ];
            
            // Try to get from database if table exists
            if ($this->tableExists('settings')) {
                // Try to get shop-specific tax settings first
                $taxKeys = ['enable_tax_shop', 'tax_rate_shop', 'tax_name_shop'];
                $placeholders = str_repeat('?,', count($taxKeys) - 1) . '?';
                $sql = "SELECT `key`, `value` FROM settings WHERE `key` IN ($placeholders)";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($taxKeys);
                $shopTaxSettings = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Map shop-specific settings to public settings
                foreach ($shopTaxSettings as $setting) {
                    if ($setting['key'] === 'enable_tax_shop') {
                        $publicSettings['tax_enabled'] = $setting['value'];
                    } elseif ($setting['key'] === 'tax_rate_shop') {
                        $publicSettings['tax_rate'] = $setting['value'];
                    } elseif ($setting['key'] === 'tax_name_shop') {
                        $publicSettings['tax_name'] = $setting['value'];
                    }
                }
                
                // Get other settings
                $keys = array_keys($publicSettings);
                $placeholders = str_repeat('?,', count($keys) - 1) . '?';
                $sql = "SELECT `key`, `value` FROM settings WHERE `key` IN ($placeholders)";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($keys);
                $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($settings as $setting) {
                    $publicSettings[$setting['key']] = $setting['value'];
                }
            }
            
            $this->sendSuccess($publicSettings);
            
        } catch (Exception $e) {
            error_log('Public settings error: ' . $e->getMessage());
            // Return default public settings on error
            $this->sendSuccess([
                'cod_enabled' => '0',
                'cod_min_amount' => '50000',
                'cod_max_amount' => '1000000'
            ]);
        }
    }
    
    /**
     * Get all settings
     */
    public function getSettings() {
        try {
            $this->checkAuthentication();
            
            // Check if settings table exists
            if (!$this->tableExists('settings')) {
                error_log('Settings table does not exist');
                // Return default settings instead of error
                $this->sendSuccess($this->getDefaultSettings());
                return;
            }
            
            // Get all settings from database (use correct column names)
            $sql = "SELECT `key`, `value` FROM settings ORDER BY `key` ASC";
            $stmt = $this->pdo->query($sql);
            $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Convert to key-value array
            $data = [];
            foreach ($settings as $setting) {
                $data[$setting['key']] = $setting['value'];
            }
            
            // If no settings found, return defaults
            if (empty($data)) {
                $data = $this->getDefaultSettings();
            }
            
            $this->sendSuccess($data);
            
        } catch (Exception $e) {
            error_log('Settings error: ' . $e->getMessage());
            // Return default settings on error
            $this->sendSuccess($this->getDefaultSettings());
        }
    }
    
    /**
     * Check if table exists
     */
    private function tableExists($tableName) {
        try {
            $result = $this->pdo->query("SHOW TABLES LIKE '{$tableName}'");
            return $result->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Ensure settings table exists with the correct schema
     */
    private function ensureSettingsTable() {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS `settings` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `key` varchar(100) NOT NULL UNIQUE,
                `value` text DEFAULT NULL,
                `type` enum('string','number','boolean','json') NOT NULL DEFAULT 'string',
                `description` text DEFAULT NULL,
                `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                PRIMARY KEY (`id`),
                KEY `idx_key` (`key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            $this->pdo->exec($sql);
        } catch (Exception $e) {
            // Ignore if creation fails; caller will handle
            error_log('ensureSettingsTable error: ' . $e->getMessage());
        }
    }
    
    /**
     * Get default settings as array
     */
    private function getDefaultSettings() {
        return [
            'enable_tax' => '1',
            'tax_rate' => '11',
            // Shop-specific tax settings
            'enable_tax_shop' => '0',
            'tax_rate_shop' => '11',
            'tax_name_shop' => 'Pajak',
            // Bank transfer settings (default values)
            'bank_default' => 'bca',
            'bank_bca_name' => 'Bytebalok',
            'bank_bca_account' => '1234567890',
            'bank_mandiri_name' => 'Bytebalok',
            'bank_mandiri_account' => '1234567890',
            'bank_bni_name' => 'Bytebalok',
            'bank_bni_account' => '1234567890',
            'currency' => 'IDR',
            'timezone' => 'Asia/Jakarta',
            'date_format' => 'd/m/Y',
            'company_name' => 'Bytebalok',
            'company_email' => 'info@bytebalok.com',
            'company_phone' => '+62 21 1234 5678',
            'company_address' => 'Jl. Example No. 123, Jakarta',
            'company_website' => 'https://bytebalok.com',
            'tax_number' => '',
            'enable_barcode_scanner' => '1',
            'auto_print_receipt' => '0',
            'allow_negative_stock' => '0',
            'cart_autosave_interval' => '30',
            'low_stock_threshold' => '10',
            'receipt_header' => 'Terima kasih telah berbelanja!',
            'receipt_footer' => "Terima kasih atas kunjungan Anda!\nSampai jumpa kembali.",
            'receipt_header_text' => 'Terima kasih telah berbelanja!',
            'receipt_footer_text' => "Terima kasih atas kunjungan Anda!\nSampai jumpa kembali.",
            'session_timeout' => '30',
            'force_strong_password' => '1',
            'enable_activity_log' => '1',
            'debug_mode' => '0',
            'performance_monitoring' => '0'
        ];
    }
    
    /**
     * Get single setting by key
     */
    public function getSetting() {
        try {
            $this->checkAuthentication();
            
            $key = $_GET['key'] ?? null;
            if (!$key) {
                $this->sendError('Setting key is required', 400);
                return;
            }
            
            // Check if settings table exists
            if (!$this->tableExists('settings')) {
                error_log('Settings table does not exist, returning default value for: ' . $key);
                $this->sendSuccess(['value' => $this->getDefaultValue($key)]);
                return;
            }
            
            $sql = "SELECT `value` FROM settings WHERE `key` = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$key]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $this->sendSuccess(['value' => $result['value']]);
            } else {
                // Return default value if not found
                $this->sendSuccess(['value' => $this->getDefaultValue($key)]);
            }
            
        } catch (Exception $e) {
            error_log('Settings error for key ' . $key . ': ' . $e->getMessage());
            // Return default value on error
            $this->sendSuccess(['value' => $this->getDefaultValue($key)]);
        }
    }
    
    /**
     * Update settings
     */
    public function updateSettings() {
        if ($this->getMethod() !== 'POST') {
            $this->sendError('Method not allowed', 405);
            return;
        }
        
        try {
            $this->checkAuthentication();
            
            // Only admin can update settings
            if ($this->user['role'] !== 'admin') {
                $this->sendError('Unauthorized. Admin access required.', 403);
                return;
            }
            
            $data = $this->getRequestData();
            
            if (empty($data)) {
                $this->sendError('No settings data provided', 400);
                return;
            }
            
            // Check if settings table exists; attempt to auto-create if missing
            if (!$this->tableExists('settings')) {
                $this->ensureSettingsTable();
            }
            if (!$this->tableExists('settings')) {
                error_log('Settings table does not exist after creation attempt');
                $this->sendError('Settings table not found. Please contact administrator to run database migration.', 500);
                return;
            }
            
            $this->pdo->beginTransaction();
            
            foreach ($data as $key => $value) {
                // Check if setting exists
                $sql = "SELECT id FROM settings WHERE `key` = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$key]);
                $exists = $stmt->fetch();
                
                if ($exists) {
                    // Update existing
                    $sql = "UPDATE settings SET `value` = ?, updated_at = NOW() WHERE `key` = ?";
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([$value, $key]);
                } else {
                    // Insert new
                    $sql = "INSERT INTO settings (`key`, `value`, `type`, `description`, `updated_at`) VALUES (?, ?, 'string', NULL, NOW())";
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([$key, $value]);
                }
                
                // Log the change (optional, skip if audit_logs table doesn't exist)
                try {
                    $this->logAction('update', 'settings', $exists ? $exists['id'] : $this->pdo->lastInsertId(), null, [
                        'key' => $key,
                        'value' => $value
                    ]);
                } catch (Exception $logError) {
                    // Ignore logging errors
                    error_log('Audit log error: ' . $logError->getMessage());
                }
            }
            
            $this->pdo->commit();
            
            $this->sendSuccess(null, 'Settings updated successfully');
            
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log('Settings update error: ' . $e->getMessage());
            $this->sendError('Failed to update settings: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get default value for a setting key
     */
    private function getDefaultValue($key) {
        $defaults = [
            'enable_tax' => '1',
            'tax_rate' => '11',
            // Shop-specific tax defaults
            'enable_tax_shop' => '0',
            'tax_rate_shop' => '11',
            // Bank transfer defaults
            'bank_default' => 'bca',
            'bank_bca_name' => 'Bytebalok',
            'bank_bca_account' => '1234567890',
            'bank_mandiri_name' => 'Bytebalok',
            'bank_mandiri_account' => '1234567890',
            'bank_bni_name' => 'Bytebalok',
            'bank_bni_account' => '1234567890',
            'currency' => 'IDR',
            'timezone' => 'Asia/Jakarta',
            'date_format' => 'd/m/Y',
            'company_name' => 'Bytebalok',
            'company_email' => 'info@bytebalok.com',
            'company_phone' => '+62 21 1234 5678',
            'company_address' => 'Jl. Example No. 123, Jakarta',
            'enable_barcode_scanner' => '1',
            'auto_print_receipt' => '0',
            'allow_negative_stock' => '0',
            'cart_autosave_interval' => '30',
            'low_stock_threshold' => '10',
            'receipt_header' => 'Terima kasih telah berbelanja!',
            'receipt_footer' => "Terima kasih atas kunjungan Anda!\nSampai jumpa kembali.",
            'receipt_header_text' => 'Terima kasih telah berbelanja!',
            'receipt_footer_text' => "Terima kasih atas kunjungan Anda!\nSampai jumpa kembali.",
            'session_timeout' => '30',
            'force_strong_password' => '1',
            'enable_activity_log' => '1',
            'debug_mode' => '0',
            'performance_monitoring' => '0'
        ];
        
        return $defaults[$key] ?? '';
    }

    /**
     * Get public shop settings (no authentication)
     * Returns only keys needed by public shop
     */
    public function get_public_shop() {
        try {
            $data = [
                'enable_tax_shop' => $this->getDefaultValue('enable_tax_shop'),
                'tax_rate_shop' => $this->getDefaultValue('tax_rate_shop')
            ];

            // Attempt to read from DB if table exists
            if ($this->tableExists('settings')) {
                $sql = "SELECT `key`, `value` FROM settings WHERE `key` IN ('enable_tax_shop','tax_rate_shop')";
                $stmt = $this->pdo->query($sql);
                $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($settings as $s) {
                    $data[$s['key']] = $s['value'];
                }
            }

            $this->sendSuccess($data);
        } catch (Exception $e) {
            // Fallback to defaults
            $this->sendSuccess([
                'enable_tax_shop' => $this->getDefaultValue('enable_tax_shop'),
                'tax_rate_shop' => $this->getDefaultValue('tax_rate_shop')
            ]);
        }
    }

    /**
     * Get public bank settings (no authentication)
     * Returns bank account information for public shop
     */
    public function get_public_banks() {
        try {
            $data = $this->getDefaultSettings();

            // Attempt to read from DB if table exists
            if ($this->tableExists('settings')) {
                $bankKeys = [
                    'bank_default', 'bank_bca_name', 'bank_bca_account',
                    'bank_bri_name', 'bank_bri_account', 'bank_blu_bca_name', 'bank_blu_bca_account'
                ];
                $placeholders = str_repeat('?,', count($bankKeys) - 1) . '?';
                $sql = "SELECT `key`, `value` FROM settings WHERE `key` IN ($placeholders)";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($bankKeys);
                $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($settings as $s) {
                    $data[$s['key']] = $s['value'];
                }
            }

            $this->sendSuccess($data);
        } catch (Exception $e) {
            // Fallback to defaults
            $this->sendSuccess($this->getDefaultSettings());
        }
    }
}

// Handle requests
$settingsController = new SettingsController($pdo);

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get':
        if (isset($_GET['key'])) {
            $settingsController->getSetting();
        } else {
            $settingsController->getSettings();
        }
        break;
    case 'update':
        $settingsController->updateSettings();
        break;
    case 'get_public':
        // Public settings endpoint (no auth)
        $settingsController->get_public();
        break;
    case 'get_public_shop':
        // Public shop settings endpoint (no auth)
        $settingsController->get_public_shop();
        break;
    case 'get_public_banks':
        // Public bank settings endpoint (no auth)
        $settingsController->get_public_banks();
        break;
    default:
        $settingsController->sendError('Invalid action', 400);
}

