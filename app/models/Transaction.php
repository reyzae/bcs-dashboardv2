<?php
require_once __DIR__ . '/BaseModel.php';

/**
 * Bytebalok Transaction Model
 * Handles sales transactions and orders
 */

class Transaction extends BaseModel {
    protected $table = 'transactions';
    protected $fillable = [
        'transaction_number', 'customer_id', 'user_id', 'served_by', 'transaction_type',
        'subtotal', 'discount_amount', 'discount_percentage', 'tax_amount',
        'tax_percentage', 'total_amount', 'payment_method', 'payment_reference',
        'cash_received', 'cash_change',
        'status', 'notes'
    ];
    
    /**
     * Get transaction with customer and user information
     */
    public function findWithDetails($id) {
        $sql = "SELECT t.*, 
                       c.name as customer_name, 
                       c.phone as customer_phone,
                       u.full_name as user_name, 
                       u.username as user_username
                FROM {$this->table} t 
                LEFT JOIN customers c ON t.customer_id = c.id 
                LEFT JOIN users u ON t.user_id = u.id 
                WHERE t.{$this->primaryKey} = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $transaction = $stmt->fetch();
        
        // Add items_count separately to avoid query issues
        if ($transaction) {
            $itemsSql = "SELECT COALESCE(SUM(quantity), 0) as items_count 
                        FROM transaction_items 
                        WHERE transaction_id = ?";
            $itemsStmt = $this->pdo->prepare($itemsSql);
            $itemsStmt->execute([$id]);
            $itemsResult = $itemsStmt->fetch();
            $transaction['items_count'] = $itemsResult['items_count'] ?? 0;
        }
        
        return $transaction;
    }
    
    /**
     * Get all transactions with details
     */
    public function findAllWithDetails($conditions = [], $orderBy = 'created_at DESC', $limit = null, $offset = null) {
        $sql = "SELECT t.*, 
                       c.name as customer_name, 
                       c.phone as customer_phone,
                       u.full_name as user_name, 
                       u.username as user_username
                FROM {$this->table} t 
                LEFT JOIN customers c ON t.customer_id = c.id 
                LEFT JOIN users u ON t.user_id = u.id";
        $params = [];
        
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $field => $value) {
                $whereClause[] = "t.{$field} = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(" AND ", $whereClause);
        }
        
        if ($orderBy) {
            $sql .= " ORDER BY t.{$orderBy}";
        }
        
        if ($limit) {
            $sql .= " LIMIT {$limit}";
            if ($offset) {
                $sql .= " OFFSET {$offset}";
            }
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $transactions = $stmt->fetchAll();
        
        // Add items_count for each transaction separately
        foreach ($transactions as &$transaction) {
            $itemsSql = "SELECT COALESCE(SUM(quantity), 0) as items_count 
                        FROM transaction_items 
                        WHERE transaction_id = ?";
            $itemsStmt = $this->pdo->prepare($itemsSql);
            $itemsStmt->execute([$transaction['id']]);
            $itemsResult = $itemsStmt->fetch();
            $transaction['items_count'] = $itemsResult['items_count'] ?? 0;
        }
        
        return $transactions;
    }
    
    /**
     * Count transactions with conditions
     */
    public function count($conditions = []) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} t";
        $params = [];
        
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $field => $value) {
                $whereClause[] = "t.{$field} = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(" AND ", $whereClause);
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }
    
    /**
     * Get transaction items
     */
    public function getItems($transactionId) {
        $sql = "SELECT ti.*, 
                       p.name as product_name,
                       p.sku as product_sku,
                       c.name as category_name
                FROM transaction_items ti 
                LEFT JOIN products p ON ti.product_id = p.id 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE ti.transaction_id = ? 
                ORDER BY ti.id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$transactionId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Create transaction with items
     */
    public function createWithItems($transactionData, $items) {
        error_log('📝 Transaction::createWithItems() started');
        
        try {
            $this->pdo->beginTransaction();
            error_log('✅ Database transaction started');
            
            // Generate transaction number
            $transactionData['transaction_number'] = $this->generateTransactionNumber();
            error_log('📋 Transaction number generated: ' . $transactionData['transaction_number']);
            
            // Create transaction
            $transactionId = $this->create($transactionData);
            error_log('✅ Transaction record created with ID: ' . $transactionId);
            
            // Create transaction items
            foreach ($items as $index => $item) {
                error_log("📦 Processing item " . ($index + 1) . " - Product ID: {$item['product_id']}, Qty: {$item['quantity']}");
                
                $item['transaction_id'] = $transactionId;
                $this->createTransactionItem($item);
                error_log("✅ Transaction item created for product {$item['product_id']}");
                
                // Update product stock (skip for Shop online transactions)
                $servedBy = strtolower($transactionData['served_by'] ?? '');
                $isShopOnline = ($servedBy === 'system online');
                if (!$isShopOnline) {
                    $this->updateProductStock($item['product_id'], -$item['quantity'], 'out', 'transaction', $transactionId, $transactionData['user_id']);
                }
                error_log("✅ Stock updated for product {$item['product_id']}");
            }
            
            $this->pdo->commit();
            error_log('✅ Database transaction committed successfully');
            
            return $transactionId;
            
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollback();
                error_log('❌ Database transaction rolled back');
            }
            error_log('❌ PDO Error: ' . $e->getMessage());
            error_log('❌ Error Code: ' . $e->getCode());
            error_log('❌ SQL State: ' . ($e->errorInfo[0] ?? 'unknown'));
            throw new Exception('Database error: ' . $e->getMessage(), 0, $e);
            
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollback();
                error_log('❌ Database transaction rolled back');
            }
            error_log('❌ Error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Create transaction item
     */
    private function createTransactionItem($itemData) {
        try {
            // Calculate total_price if not provided
            if (!isset($itemData['total_price'])) {
                $quantity = $itemData['quantity'];
                $unitPrice = $itemData['unit_price'];
                $discountAmount = $itemData['discount_amount'] ?? 0;
                $itemData['total_price'] = ($quantity * $unitPrice) - $discountAmount;
            }
            
            $sql = "INSERT INTO transaction_items (transaction_id, product_id, quantity, unit_price, discount_amount, discount_percentage, total_price) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                $itemData['transaction_id'],
                $itemData['product_id'],
                $itemData['quantity'],
                $itemData['unit_price'],
                $itemData['discount_amount'] ?? 0,
                $itemData['discount_percentage'] ?? 0,
                $itemData['total_price']
            ]);
            
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                error_log("❌ Failed to create transaction item: " . json_encode($errorInfo));
                throw new Exception("Failed to insert transaction item: " . $errorInfo[2]);
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("❌ PDO Error in createTransactionItem: " . $e->getMessage());
            throw new Exception("Database error creating transaction item: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Update product stock
     */
    private function updateProductStock($productId, $quantity, $movementType, $referenceType, $referenceId, $userId) {
        try {
            // Update stock quantity
            $sql = "UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$quantity, $productId]);
            
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                error_log("❌ Failed to update product stock: " . json_encode($errorInfo));
                throw new Exception("Failed to update stock for product {$productId}: " . $errorInfo[2]);
            }
            
            // Record stock movement (quantity harus positif)
            $movementSql = "INSERT INTO stock_movements (product_id, movement_type, quantity, reference_type, reference_id, user_id) 
                           VALUES (?, ?, ?, ?, ?, ?)";
            $movementStmt = $this->pdo->prepare($movementSql);
            $movementResult = $movementStmt->execute([
                $productId, $movementType, abs($quantity), $referenceType, $referenceId, $userId
            ]);
            
            if (!$movementResult) {
                $errorInfo = $movementStmt->errorInfo();
                error_log("❌ Failed to record stock movement: " . json_encode($errorInfo));
                throw new Exception("Failed to record stock movement for product {$productId}: " . $errorInfo[2]);
            }
            
            error_log("✅ Stock updated successfully for product {$productId}: {$quantity}");
        } catch (PDOException $e) {
            error_log("❌ PDO Error in updateProductStock: " . $e->getMessage());
            throw new Exception("Database error updating product stock: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Generate unique transaction number
     */
    private function generateTransactionNumber() {
        $prefix = 'TXN';
        $date = date('Ymd');
        
        // Get last transaction number for today
        $sql = "SELECT transaction_number FROM {$this->table} 
                WHERE transaction_number LIKE ? 
                ORDER BY transaction_number DESC 
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$prefix . $date . '%']);
        $lastTransaction = $stmt->fetch();
        
        if ($lastTransaction) {
            $lastNumber = intval(substr($lastTransaction['transaction_number'], -4));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . $date . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Get sales statistics
     */
    public function getSalesStats($startDate = null, $endDate = null, $type = 'all') {
        // Build source filter
        $shopFilter = "(LOWER(served_by) = 'system online' OR LOWER(notes) LIKE 'order %')";
        $posFilter = "NOT (LOWER(served_by) = 'system online' OR LOWER(notes) LIKE 'order %')";
        $whereSource = '';
        if ($type === 'shop') { $whereSource = " AND $shopFilter"; }
        if ($type === 'pos')  { $whereSource = " AND $posFilter"; }

        // Today's transactions (all statuses) from transactions table
        $sqlTxnTodayCount = "SELECT COUNT(*) as txn_today_count FROM {$this->table} WHERE DATE(created_at) = CURDATE()" . $whereSource;
        $stmtTxnTodayCount = $this->pdo->query($sqlTxnTodayCount);
        $txnTodayCount = $stmtTxnTodayCount->fetch();

        // Today's paid Shop orders that do not yet exist as transactions
        $sqlOrdersMissing = "SELECT COUNT(*) as orders_today_missing
                             FROM orders o
                             WHERE o.payment_status = 'paid'
                               AND o.order_status IN ('processing','ready','completed')
                               AND DATE(o.paid_at) = CURDATE()
                               AND NOT EXISTS (
                                    SELECT 1 FROM {$this->table} t
                                    WHERE (t.payment_reference IS NOT NULL AND t.payment_reference = o.payment_reference)
                                       OR (t.notes LIKE CONCAT('Order ', COALESCE(o.order_number, o.id), '%'))
                               )";
        $ordersMissing = ['orders_today_missing' => 0];
        if ($type === 'all' || $type === 'shop') {
            $stmtOrdersMissing = $this->pdo->query($sqlOrdersMissing);
            $ordersMissing = $stmtOrdersMissing->fetch();
        }

        // Today's revenue (completed only)
        $sqlTodayRevenue = "SELECT COALESCE(SUM(total_amount), 0) as today_revenue FROM {$this->table} WHERE status = 'completed' AND DATE(created_at) = CURDATE()" . $whereSource;
        $stmtTodayRevenue = $this->pdo->query($sqlTodayRevenue);
        $todayRevenue = $stmtTodayRevenue->fetch();
        
        // Get this month stats
        $sqlMonth = "SELECT 
                        COUNT(*) as month_count,
                        COALESCE(SUM(total_amount), 0) as month_revenue
                    FROM {$this->table} 
                    WHERE status = 'completed' 
                    AND YEAR(created_at) = YEAR(CURDATE())
                    AND MONTH(created_at) = MONTH(CURDATE())" . $whereSource;
        $stmtMonth = $this->pdo->query($sqlMonth);
        $monthStats = $stmtMonth->fetch();
        
        // Get pending count
        $sqlPending = "SELECT COUNT(*) as pending_count
                      FROM {$this->table} 
                      WHERE status = 'pending'" . $whereSource;
        $stmtPending = $this->pdo->query($sqlPending);
        $pendingStats = $stmtPending->fetch();
        
        // Combine all stats
        return [
            'today_count' => (int)($txnTodayCount['txn_today_count'] ?? 0) + (int)($ordersMissing['orders_today_missing'] ?? 0),
            'today_revenue' => (float)$todayRevenue['today_revenue'],
            'month_count' => (int)$monthStats['month_count'],
            'month_revenue' => (float)$monthStats['month_revenue'],
            'pending_count' => (int)$pendingStats['pending_count']
        ];
    }
    
    /**
     * Cancel transaction and return stock
     */
    public function cancelTransaction($transactionId, $reason, $userId) {
        try {
            $this->pdo->beginTransaction();
            
            // Get transaction details
            $transaction = $this->find($transactionId);
            if (!$transaction) {
                throw new Exception('Transaction not found');
            }
            
            // Check if already cancelled or refunded
            if (in_array($transaction['status'], ['cancelled', 'refunded'])) {
                throw new Exception('Transaction already ' . $transaction['status']);
            }
            
            // Get transaction items
            $items = $this->getItems($transactionId);
            
            // Return stock for each item
            foreach ($items as $item) {
                // Add stock back
                $sql = "UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$item['quantity'], $item['product_id']]);
                
                // Record stock movement (return)
                $sql = "INSERT INTO stock_movements (product_id, movement_type, quantity, reference_type, reference_id, notes, user_id) 
                        VALUES (?, 'in', ?, 'transaction_cancel', ?, ?, ?)";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    $item['product_id'],
                    abs($item['quantity']),
                    $transactionId,
                    'Stock returned from cancelled transaction',
                    $userId
                ]);
            }
            
            // Update transaction status
            $sql = "UPDATE {$this->table} SET status = 'cancelled', notes = CONCAT(COALESCE(notes, ''), '\n[CANCELLED] ', ?), updated_at = NOW() WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$reason, $transactionId]);
            
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
    
    /**
     * Refund transaction and return stock
     */
    public function refundTransaction($transactionId, $refundAmount, $reason, $userId) {
        try {
            $this->pdo->beginTransaction();
            
            // Get transaction details
            $transaction = $this->find($transactionId);
            if (!$transaction) {
                throw new Exception('Transaction not found');
            }
            
            // Check if already refunded
            if ($transaction['status'] === 'refunded') {
                throw new Exception('Transaction already refunded');
            }
            
            // Validate refund amount
            if ($refundAmount > $transaction['total_amount']) {
                throw new Exception('Refund amount cannot exceed transaction total');
            }
            
            // Get transaction items
            $items = $this->getItems($transactionId);
            
            // Return stock for each item (full refund)
            if ($refundAmount == $transaction['total_amount']) {
                foreach ($items as $item) {
                    // Add stock back
                    $sql = "UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?";
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([$item['quantity'], $item['product_id']]);
                    
                    // Record stock movement (return)
                    $sql = "INSERT INTO stock_movements (product_id, movement_type, quantity, reference_type, reference_id, notes, user_id) 
                            VALUES (?, 'in', ?, 'transaction_refund', ?, ?, ?)";
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([
                        $item['product_id'],
                        abs($item['quantity']),
                        $transactionId,
                        'Stock returned from refunded transaction',
                        $userId
                    ]);
                }
            }
            
            // Update transaction status
            $sql = "UPDATE {$this->table} SET status = 'refunded', notes = CONCAT(COALESCE(notes, ''), '\n[REFUNDED] Amount: ', ?, ' - Reason: ', ?), updated_at = NOW() WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$refundAmount, $reason, $transactionId]);
            
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
    
    /**
     * Get daily sales report
     */
    public function getDailySales($startDate, $endDate) {
        $sql = "SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as transactions,
                    SUM(total_amount) as total_sales
                FROM {$this->table} 
                WHERE status = 'completed' 
                AND DATE(created_at) BETWEEN ? AND ?
                GROUP BY DATE(created_at)
                ORDER BY date";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get top selling products
     */
    public function getTopSellingProducts($limit = 10, $startDate = null, $endDate = null) {
        $sql = "SELECT 
                    p.name as product_name,
                    p.sku,
                    SUM(ti.quantity) as total_quantity,
                    SUM(ti.total_price) as total_sales
                FROM transaction_items ti
                INNER JOIN transactions t ON ti.transaction_id = t.id
                INNER JOIN products p ON ti.product_id = p.id
                WHERE t.status = 'completed'";
        $params = [];
        
        if ($startDate) {
            $sql .= " AND DATE(t.created_at) >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $sql .= " AND DATE(t.created_at) <= ?";
            $params[] = $endDate;
        }
        
        $sql .= " GROUP BY p.id, p.name, p.sku 
                  ORDER BY total_quantity DESC 
                  LIMIT {$limit}";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
