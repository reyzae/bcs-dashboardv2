<?php
require_once __DIR__ . '/BaseModel.php';

/**
 * Bytebalok Product Model
 * Handles product management and inventory
 */

class Product extends BaseModel {
    protected $table = 'products';
    protected $fillable = [
        'sku', 'name', 'description', 'category_id', 'price', 'cost_price',
        'stock_quantity', 'min_stock_level', 'max_stock_level', 'unit',
        'barcode', 'image', 'is_active'
    ];
    
    /**
     * Override find to include price alias
     */
    public function find($id) {
        $sql = "SELECT *, price as unit_price FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Get product with category information
     */
    public function findWithCategory($id) {
        $sql = "SELECT p.*, p.price as unit_price, c.name as category_name, c.color as category_color 
                FROM {$this->table} p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.{$this->primaryKey} = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Get all products with category information
     */
    public function findAllWithCategory($conditions = [], $orderBy = null, $limit = null, $offset = null) {
        $sql = "SELECT p.*, p.price as unit_price, c.name as category_name, c.color as category_color 
                FROM {$this->table} p 
                LEFT JOIN categories c ON p.category_id = c.id";
        $params = [];
        
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $field => $value) {
                $whereClause[] = "p.{$field} = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(" AND ", $whereClause);
        }
        
        // Always sort by is_active DESC (active first), then by name or custom order
        if ($orderBy) {
            // Check if orderBy already contains 'p.' prefix or DESC/ASC, if so use as is
            if (strpos($orderBy, 'p.') !== false || strpos($orderBy, ' ') !== false) {
                $sql .= " ORDER BY {$orderBy}";
            } else {
                $sql .= " ORDER BY p.is_active DESC, p.{$orderBy}";
            }
        } else {
            $sql .= " ORDER BY p.is_active DESC, p.name ASC";
        }
        
        if ($limit) {
            $sql .= " LIMIT {$limit}";
            if ($offset) {
                $sql .= " OFFSET {$offset}";
            }
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Search products by name or SKU
     */
    public function search($query, $categoryId = null, $limit = 20, $isActiveFilter = null) {
        $sql = "SELECT p.*, p.price as unit_price, c.name as category_name, c.color as category_color 
                FROM {$this->table} p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE (p.name LIKE ? OR p.sku LIKE ?)";
        $params = ["%{$query}%", "%{$query}%"];
        
        // Status filter
        if ($isActiveFilter !== null && $isActiveFilter !== '') {
            $sql .= " AND p.is_active = ?";
            $params[] = intval($isActiveFilter);
        }
        // If isActiveFilter is null or empty, show all (active + inactive)
        
        if ($categoryId) {
            $sql .= " AND p.category_id = ?";
            $params[] = $categoryId;
        }
        
        // Active products first, then alphabetical
        $sql .= " ORDER BY p.is_active DESC, p.name ASC LIMIT {$limit}";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get low stock products
     */
    public function getLowStock() {
        $sql = "SELECT p.*, p.price as unit_price, c.name as category_name 
                FROM {$this->table} p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.is_active = 1 AND p.stock_quantity < p.min_stock_level 
                ORDER BY p.stock_quantity ASC, p.name ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Update stock quantity
     */
    public function updateStock($productId, $quantity, $movementType = 'adjustment', $referenceType = null, $referenceId = null, $userId = null, $notes = null) {
        $this->pdo->beginTransaction();
        
        try {
            // Update product stock
            $sql = "UPDATE {$this->table} SET stock_quantity = stock_quantity + ? WHERE {$this->primaryKey} = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$quantity, $productId]);
            
            // Record stock movement
            $movementSql = "INSERT INTO stock_movements (product_id, movement_type, quantity, reference_type, reference_id, user_id, notes) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)";
            $movementStmt = $this->pdo->prepare($movementSql);
            $movementStmt->execute([
                $productId, $movementType, $quantity, $referenceType, $referenceId, $userId, $notes
            ]);
            
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollback();
            throw $e;
        }
    }
    
    /**
     * Get product statistics
     */
    public function getStats() {
        $stats = [];
        
        // Total products
        $stats['total'] = $this->count();
        
        // Active products
        $stats['active'] = $this->count(['is_active' => 1]);
        
        // Low stock products
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE is_active = 1 AND stock_quantity < min_stock_level";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        $stats['low_stock'] = $result['count'];
        
        // Total stock value
        $sql = "SELECT SUM(stock_quantity * cost_price) as total_value FROM {$this->table} WHERE is_active = 1 AND cost_price IS NOT NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        $stats['total_stock_value'] = $result['total_value'] ?? 0;
        
        // Products by category
        $sql = "SELECT c.name as category_name, COUNT(p.id) as count 
                FROM categories c 
                LEFT JOIN {$this->table} p ON c.id = p.category_id AND p.is_active = 1 
                GROUP BY c.id, c.name 
                ORDER BY count DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $stats['by_category'] = $stmt->fetchAll();
        
        return $stats;
    }
    
    /**
     * Check if SKU exists
     */
    public function skuExists($sku, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE sku = ?";
        $params = [$sku];
        
        if ($excludeId) {
            $sql .= " AND {$this->primaryKey} != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }
    
    /**
     * Check if barcode exists
     */
    public function barcodeExists($barcode, $excludeId = null) {
        if (empty($barcode)) return false;
        
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE barcode = ?";
        $params = [$barcode];
        
        if ($excludeId) {
            $sql .= " AND {$this->primaryKey} != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }
}
