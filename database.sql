-- ========================================
-- BYTEBALOK DASHBOARD - COMPLETE DATABASE
-- Database lengkap dengan semua tabel, views, procedures, dan triggers
-- Versi: Final 1.0
-- Tanggal: 2025-01-26
-- ========================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `wiracent_balok`
--

-- ========================================
-- PART 1: CORE TABLES
-- ========================================

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `email` varchar(100) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','manager','staff','cashier') NOT NULL DEFAULT 'staff',
  `phone` varchar(20) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_username` (`username`),
  KEY `idx_email` (`email`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `categories`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(7) DEFAULT '#007bff',
  `icon` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_name` (`name`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `products`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sku` varchar(50) NOT NULL UNIQUE,
  `barcode` varchar(100) DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `cost_price` decimal(12,2) DEFAULT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `min_stock_level` int(11) NOT NULL DEFAULT 5,
  `reorder_point` int(11) DEFAULT NULL COMMENT 'Auto reorder when stock below this',
  `max_stock_level` int(11) DEFAULT NULL,
  `unit` varchar(20) DEFAULT 'pcs',
  `supplier_info` varchar(255) DEFAULT NULL COMMENT 'Supplier name/contact',
  `last_restock_date` date DEFAULT NULL COMMENT 'Last time restocked',
  `expiry_date` date DEFAULT NULL COMMENT 'For perishable items',
  `image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_name` (`name`),
  KEY `idx_category` (`category_id`),
  KEY `idx_barcode` (`barcode`),
  KEY `idx_active` (`is_active`),
  KEY `idx_product_stock` (`stock_quantity`, `min_stock_level`),
  KEY `idx_product_active_category` (`is_active`, `category_id`),
  CONSTRAINT `products_category_fk` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `stock_movements`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `stock_movements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `movement_type` enum('in','out','adjustment','return') NOT NULL,
  `quantity` int(11) NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_product` (`product_id`),
  KEY `idx_movement_type` (`movement_type`),
  KEY `idx_reference` (`reference_type`, `reference_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_created` (`created_at`),
  KEY `idx_stock_product_date` (`product_id`, `created_at`),
  KEY `idx_stock_type_date` (`movement_type`, `created_at`),
  CONSTRAINT `stock_movements_product_fk` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `stock_movements_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `customers`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_code` varchar(20) NOT NULL UNIQUE,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `total_purchases` int(11) NOT NULL DEFAULT 0 COMMENT 'Total number of purchases',
  `total_spent` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Lifetime value',
  `last_purchase_date` timestamp NULL DEFAULT NULL COMMENT 'Last transaction date',
  `customer_type` enum('walk-in','regular','vip') NOT NULL DEFAULT 'walk-in',
  `notes` text DEFAULT NULL COMMENT 'Admin notes about customer',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_name` (`name`),
  KEY `idx_email` (`email`),
  KEY `idx_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- PART 2: TRANSACTION TABLES
-- ========================================

-- --------------------------------------------------------
-- Table structure for table `transactions`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_number` varchar(20) NOT NULL UNIQUE,
  `customer_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `served_by` varchar(100) DEFAULT NULL COMMENT 'Cashier full name',
  `transaction_type` enum('sale','return','refund','pos') NOT NULL DEFAULT 'sale',
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_method` enum('cash','card','transfer','qris','other') NOT NULL DEFAULT 'cash',
  `cash_received` decimal(12,2) DEFAULT NULL COMMENT 'Cash received for change calculation',
  `cash_change` decimal(12,2) DEFAULT NULL COMMENT 'Change given',
  `payment_reference` varchar(100) DEFAULT NULL,
  `status` enum('pending','completed','cancelled','refunded') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_type` (`transaction_type`),
  KEY `idx_status` (`status`),
  KEY `idx_created` (`created_at`),
  KEY `idx_txn_created_status` (`created_at`, `status`),
  KEY `idx_txn_user_date` (`user_id`, `created_at`),
  KEY `idx_txn_customer_date` (`customer_id`, `created_at`),
  CONSTRAINT `transactions_customer_fk` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `transactions_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `transaction_items`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `transaction_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `total_price` decimal(12,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_transaction` (`transaction_id`),
  KEY `idx_product` (`product_id`),
  CONSTRAINT `transaction_items_transaction_fk` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `transaction_items_product_fk` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `hold_transactions`
-- Untuk fitur POS: simpan transaksi sementara
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `hold_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'Kasir yang hold transaction',
  `customer_id` int(11) DEFAULT NULL,
  `cart_data` json NOT NULL COMMENT 'Cart items dalam JSON format',
  `discount_percentage` decimal(5,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `hold_transactions_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `hold_transactions_customer_fk` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- PART 3: ORDER MANAGEMENT TABLES (Web Orders)
-- ========================================

-- --------------------------------------------------------
-- Table structure for table `orders`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_number` varchar(20) NOT NULL UNIQUE,
  `customer_name` varchar(100) NOT NULL,
  `customer_email` varchar(100) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `customer_address` text NOT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `shipping_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_method` enum('qris','transfer','cod') NOT NULL DEFAULT 'qris',
  `payment_status` enum('pending','paid','failed','expired') NOT NULL DEFAULT 'pending',
  `payment_reference` varchar(100) DEFAULT NULL,
  `order_status` enum('pending','processing','ready','completed','cancelled') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_customer_email` (`customer_email`),
  KEY `idx_customer_phone` (`customer_phone`),
  KEY `idx_payment_status` (`payment_status`),
  KEY `idx_order_status` (`order_status`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `order_items`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `total_price` decimal(12,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_order` (`order_id`),
  KEY `idx_product` (`product_id`),
  CONSTRAINT `order_items_order_fk` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `order_items_product_fk` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `payments`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `status` enum('pending','success','failed','expired') NOT NULL DEFAULT 'pending',
  `transaction_id` varchar(100) DEFAULT NULL,
  `qr_code` text DEFAULT NULL,
  `qr_string` text DEFAULT NULL,
  `payment_url` text DEFAULT NULL,
  `expired_at` timestamp NULL DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `callback_data` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_order` (`order_id`),
  KEY `idx_transaction_id` (`transaction_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `payments_order_fk` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- PART 4: SYSTEM TABLES
-- ========================================

-- --------------------------------------------------------
-- Table structure for table `settings`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(100) NOT NULL UNIQUE,
  `value` text DEFAULT NULL,
  `type` enum('string','number','boolean','json') NOT NULL DEFAULT 'string',
  `description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `audit_logs`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_table` (`table_name`),
  KEY `idx_created` (`created_at`),
  KEY `idx_audit_user_date` (`user_id`, `created_at`),
  KEY `idx_audit_action_date` (`action`, `created_at`),
  CONSTRAINT `audit_logs_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `notifications`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL COMMENT 'NULL = for all users',
  `type` enum('info','success','warning','error','order','payment','system','alert') NOT NULL DEFAULT 'info',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `data` json DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_order` (`order_id`),
  KEY `idx_read` (`is_read`),
  KEY `idx_type` (`type`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `notifications_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `notifications_order_fk` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `user_sessions`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `user_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL UNIQUE,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_token` (`session_token`),
  KEY `idx_activity` (`last_activity`),
  CONSTRAINT `user_sessions_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- PART 5: DATABASE VIEWS
-- ========================================

-- View: Low Stock Products
CREATE OR REPLACE VIEW `v_low_stock_products` AS
SELECT 
    p.id,
    p.sku,
    p.name,
    p.stock_quantity,
    p.min_stock_level,
    p.price,
    c.name as category_name,
    (p.min_stock_level - p.stock_quantity) as quantity_needed,
    (p.price * (p.min_stock_level - p.stock_quantity)) as estimated_cost
FROM products p
LEFT JOIN categories c ON p.category_id = c.id
WHERE p.is_active = 1 
AND p.stock_quantity <= p.min_stock_level
ORDER BY p.stock_quantity ASC;

-- View: Today's Sales Summary
CREATE OR REPLACE VIEW `v_today_sales` AS
SELECT 
    COUNT(*) as total_transactions,
    SUM(t.total_amount) as total_sales,
    SUM(t.subtotal) as subtotal,
    SUM(t.discount_amount) as total_discounts,
    SUM(t.tax_amount) as total_tax,
    AVG(t.total_amount) as average_transaction,
    t.payment_method,
    u.full_name as cashier_name
FROM transactions t
LEFT JOIN users u ON t.user_id = u.id
WHERE DATE(t.created_at) = CURDATE()
AND t.status = 'completed'
GROUP BY t.payment_method, u.full_name;

-- View: Customer Statistics
CREATE OR REPLACE VIEW `v_customer_stats` AS
SELECT 
    c.id,
    c.customer_code,
    c.name,
    c.email,
    c.phone,
    c.customer_type,
    c.total_purchases,
    c.total_spent,
    c.last_purchase_date,
    COUNT(t.id) as verified_purchase_count,
    SUM(t.total_amount) as verified_total_spent,
    MAX(t.created_at) as verified_last_purchase,
    DATEDIFF(CURDATE(), MAX(t.created_at)) as days_since_last_purchase
FROM customers c
LEFT JOIN transactions t ON c.id = t.customer_id AND t.status = 'completed'
WHERE c.is_active = 1
GROUP BY c.id
ORDER BY c.total_spent DESC;

-- View: Product Sales Performance
CREATE OR REPLACE VIEW `v_product_performance` AS
SELECT 
    p.id,
    p.sku,
    p.name,
    p.category_id,
    c.name as category_name,
    p.price,
    p.cost_price,
    p.stock_quantity,
    COUNT(ti.id) as times_sold,
    SUM(ti.quantity) as total_quantity_sold,
    SUM(ti.total_price) as total_revenue,
    SUM(ti.total_price) - (p.cost_price * SUM(ti.quantity)) as estimated_profit,
    MAX(t.created_at) as last_sold_date
FROM products p
LEFT JOIN categories c ON p.category_id = c.id
LEFT JOIN transaction_items ti ON p.id = ti.product_id
LEFT JOIN transactions t ON ti.transaction_id = t.id AND t.status = 'completed'
WHERE p.is_active = 1
GROUP BY p.id
ORDER BY total_quantity_sold DESC;

-- ========================================
-- PART 6: STORED PROCEDURES
-- ========================================

DELIMITER $$

-- Procedure: Get Dashboard Statistics
CREATE PROCEDURE IF NOT EXISTS `sp_get_dashboard_stats`(IN period_days INT)
BEGIN
    SELECT 
        (SELECT COUNT(*) FROM transactions WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL period_days DAY) AND status = 'completed') as total_transactions,
        (SELECT COALESCE(SUM(total_amount), 0) FROM transactions WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL period_days DAY) AND status = 'completed') as total_sales,
        (SELECT COUNT(*) FROM customers WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL period_days DAY)) as new_customers,
        (SELECT COUNT(*) FROM products WHERE stock_quantity <= min_stock_level AND is_active = 1) as low_stock_count,
        (SELECT COALESCE(AVG(total_amount), 0) FROM transactions WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL period_days DAY) AND status = 'completed') as avg_transaction_value;
END$$

-- Procedure: Get Top Selling Products
CREATE PROCEDURE IF NOT EXISTS `sp_get_top_products`(IN period_days INT, IN limit_count INT)
BEGIN
    SELECT 
        p.id,
        p.sku,
        p.name,
        c.name as category_name,
        COUNT(ti.id) as times_sold,
        SUM(ti.quantity) as total_quantity,
        SUM(ti.total_price) as total_revenue
    FROM products p
    INNER JOIN transaction_items ti ON p.id = ti.product_id
    INNER JOIN transactions t ON ti.transaction_id = t.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE t.status = 'completed'
    AND DATE(t.created_at) >= DATE_SUB(CURDATE(), INTERVAL period_days DAY)
    GROUP BY p.id
    ORDER BY total_quantity DESC
    LIMIT limit_count;
END$$

-- Procedure: Process Stock Adjustment
CREATE PROCEDURE IF NOT EXISTS `sp_adjust_stock`(
    IN p_product_id INT,
    IN p_adjustment_qty INT,
    IN p_movement_type ENUM('in','out','adjustment','return'),
    IN p_user_id INT,
    IN p_notes TEXT
)
BEGIN
    DECLARE current_stock INT;
    
    -- Get current stock
    SELECT stock_quantity INTO current_stock FROM products WHERE id = p_product_id;
    
    -- Update product stock
    UPDATE products 
    SET stock_quantity = stock_quantity + p_adjustment_qty,
        last_restock_date = IF(p_movement_type = 'in', CURDATE(), last_restock_date)
    WHERE id = p_product_id;
    
    -- Insert stock movement record
    INSERT INTO stock_movements (product_id, movement_type, quantity, notes, user_id)
    VALUES (p_product_id, p_movement_type, ABS(p_adjustment_qty), p_notes, p_user_id);
    
    SELECT 'Stock adjusted successfully' as message, 
           current_stock + p_adjustment_qty as new_stock;
END$$

DELIMITER ;

-- ========================================
-- PART 7: TRIGGERS
-- ========================================

DELIMITER $$

-- Trigger: Auto update customer stats after transaction
CREATE TRIGGER IF NOT EXISTS `trg_after_transaction_insert` 
AFTER INSERT ON `transactions`
FOR EACH ROW
BEGIN
    IF NEW.customer_id IS NOT NULL AND NEW.status = 'completed' THEN
        UPDATE customers 
        SET 
            total_purchases = total_purchases + 1,
            total_spent = total_spent + NEW.total_amount,
            last_purchase_date = NEW.created_at,
            customer_type = CASE 
                WHEN total_purchases + 1 >= 10 THEN 'vip'
                WHEN total_purchases + 1 >= 3 THEN 'regular'
                ELSE customer_type
            END
        WHERE id = NEW.customer_id;
    END IF;
END$$

-- Trigger: Auto add notification for low stock
CREATE TRIGGER IF NOT EXISTS `trg_low_stock_notification`
AFTER UPDATE ON `products`
FOR EACH ROW
BEGIN
    IF NEW.stock_quantity <= NEW.min_stock_level 
       AND OLD.stock_quantity > OLD.min_stock_level THEN
        INSERT INTO notifications (user_id, type, title, message, link)
        VALUES (
            NULL, 
            'warning', 
            'Low Stock Alert', 
            CONCAT('Product "', NEW.name, '" is running low. Current stock: ', NEW.stock_quantity),
            CONCAT('/dashboard/products.php?id=', NEW.id)
        );
    END IF;
END$$

DELIMITER ;

-- ========================================
-- PART 8: DEFAULT DATA
-- ========================================

-- Insert default admin user (password: password)
INSERT INTO `users` (`username`, `email`, `password`, `full_name`, `role`) VALUES
('admin', 'admin@bytebalok.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin')
ON DUPLICATE KEY UPDATE username=username;

-- Insert default categories
INSERT INTO `categories` (`name`, `description`, `color`, `icon`) VALUES
('Kue Balok Keju', 'Kue balok dengan topping keju premium', '#FFD700', 'fas fa-cheese'),
('Kue Balok Coklat', 'Kue balok dengan topping coklat lezat', '#8B4513', 'fas fa-cookie-bite'),
('Kue Balok Pandan', 'Kue balok dengan aroma pandan harum', '#90EE90', 'fas fa-leaf'),
('Kue Balok Mix', 'Kue balok dengan berbagai topping', '#FF69B4', 'fas fa-ice-cream'),
('Topping & Extra', 'Topping tambahan untuk kue balok', '#FFA500', 'fas fa-plus-circle')
ON DUPLICATE KEY UPDATE name=name;

-- Insert default settings
INSERT INTO `settings` (`key`, `value`, `type`, `description`) VALUES
('company_name', 'Bytebalok', 'string', 'Company name'),
('company_address', 'Jl. Example No. 123, Jakarta', 'string', 'Company address'),
('company_phone', '+62 21 1234 5678', 'string', 'Company phone number'),
('company_email', 'info@bytebalok.com', 'string', 'Company email'),
('company_website', 'https://bytebalok.com', 'string', 'Company website'),
('tax_number', '', 'string', 'Company tax number'),
('enable_tax', '1', 'boolean', 'Enable tax calculation'),
('tax_rate', '11', 'number', 'Default tax rate percentage'),
('currency', 'IDR', 'string', 'Default currency'),
('currency_symbol', 'Rp', 'string', 'Currency symbol'),
('timezone', 'Asia/Jakarta', 'string', 'System timezone'),
('date_format', 'd/m/Y', 'string', 'Date format'),
('time_format', 'H:i:s', 'string', 'Time format'),
('low_stock_threshold', '10', 'number', 'Default low stock threshold'),
('enable_barcode_scanner', '1', 'boolean', 'Enable barcode scanner in POS'),
('auto_print_receipt', '0', 'boolean', 'Auto print receipt after transaction'),
('cart_autosave_interval', '30', 'number', 'Auto save cart interval in seconds'),
('session_timeout', '30', 'number', 'Session timeout in minutes'),
('allow_negative_stock', '0', 'boolean', 'Allow selling when stock is negative'),
('receipt_header', 'Terima kasih telah berbelanja!', 'string', 'Receipt header message'),
('receipt_footer', 'Terima kasih atas kunjungan Anda!\nSampai jumpa kembali.', 'string', 'Receipt footer message'),
('receipt_header_text', 'Terima kasih telah berbelanja!', 'string', 'Receipt header text'),
('receipt_footer_text', 'Terima kasih atas kunjungan Anda!\nSampai jumpa kembali.', 'string', 'Receipt footer text'),
('force_strong_password', '1', 'boolean', 'Force users to use strong passwords'),
('enable_activity_log', '1', 'boolean', 'Enable audit logging'),
('debug_mode', '0', 'boolean', 'Enable debug mode'),
('performance_monitoring', '0', 'boolean', 'Enable performance monitoring'),
('bank_default', 'bca', 'string', 'Default bank for transfer'),
('bank_bca_name', 'REYZA WIRAKUSUMA', 'string', 'BCA account name'),
('bank_bca_account', '1481899929', 'string', 'BCA account number'),
('bank_bri_name', '', 'string', 'BRI account name'),
('bank_bri_account', '', 'string', 'BRI account number'),
('bank_blu_bca_name', '', 'string', 'BLU BCA account name'),
('bank_blu_bca_account', '', 'string', 'BLU BCA account number'),
('transfer_use_va', '0', 'boolean', 'Use virtual account for bank transfers')
ON DUPLICATE KEY UPDATE `key`=`key`;

UPDATE `settings` new JOIN `settings` old ON new.`key` = 'bank_bri_name' AND old.`key` = 'bank_mandiri_name' SET new.`value` = old.`value`;
UPDATE `settings` new JOIN `settings` old ON new.`key` = 'bank_bri_account' AND old.`key` = 'bank_mandiri_account' SET new.`value` = old.`value`;
UPDATE `settings` new JOIN `settings` old ON new.`key` = 'bank_blu_bca_name' AND old.`key` = 'bank_bni_name' SET new.`value` = old.`value`;
UPDATE `settings` new JOIN `settings` old ON new.`key` = 'bank_blu_bca_account' AND old.`key` = 'bank_bni_account' SET new.`value` = old.`value`;
DELETE FROM `settings` WHERE `key` IN ('bank_mandiri_name','bank_mandiri_account','bank_bni_name','bank_bni_account');
UPDATE `settings` SET `value`='bri' WHERE `key`='bank_default' AND `value`='mandiri';
UPDATE `settings` SET `value`='blu_bca' WHERE `key`='bank_default' AND `value`='bni';

-- Insert sample products (optional - for testing)
INSERT INTO `products` (`sku`, `barcode`, `name`, `description`, `category_id`, `price`, `cost_price`, `stock_quantity`, `min_stock_level`, `unit`) VALUES
('KB-KEJ-001', '8991234567890', 'Kue Balok Keju Original', 'Kue balok dengan keju premium melimpah', 1, 25000.00, 15000.00, 50, 10, 'pcs'),
('KB-COK-001', '8991234567891', 'Kue Balok Coklat Premium', 'Kue balok dengan coklat belgium', 2, 28000.00, 17000.00, 45, 10, 'pcs'),
('KB-PAN-001', '8991234567892', 'Kue Balok Pandan Original', 'Kue balok pandan dengan aroma harum', 3, 23000.00, 14000.00, 40, 10, 'pcs'),
('KB-MIX-001', '8991234567893', 'Kue Balok Mix Special', 'Kombinasi keju, coklat, dan pandan', 4, 30000.00, 18000.00, 35, 8, 'pcs')
ON DUPLICATE KEY UPDATE sku=sku;

-- ========================================
-- COMMIT CHANGES
-- ========================================

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- ========================================
-- END OF DATABASE SCHEMA
-- ========================================
