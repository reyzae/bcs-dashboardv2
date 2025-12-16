<?php
require_once __DIR__ . '/BaseModel.php';

/**
 * Bytebalok Order Model
 * Handles customer orders from website
 */

class Order extends BaseModel
{
    protected $table = 'orders';
    protected $fillable = [
        'order_number',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_address',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'shipping_amount',
        'total_amount',
        'payment_method',
        'payment_status',
        'payment_reference',
        'order_status',
        'notes',
        'paid_at'
    ];

    /**
     * Get order with items
     */
    public function findWithItems($id)
    {
        $order = $this->find($id);
        if (!$order)
            return null;

        $order['items'] = $this->getItems($id);
        return $order;
    }

    /**
     * Get order items
     */
    public function getItems($orderId)
    {
        $sql = "SELECT oi.*, p.name as product_name, p.sku as product_sku, p.image
                FROM order_items oi 
                LEFT JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = ? 
                ORDER BY oi.id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }

    /**
     * Create order with items
     */
    public function createWithItems($orderData, $items)
    {
        $this->pdo->beginTransaction();

        try {
            // Generate order number
            $orderData['order_number'] = $this->generateOrderNumber();

            // Create order
            $orderId = $this->create($orderData);

            // Create order items
            foreach ($items as $item) {
                $item['order_id'] = $orderId;
                $this->createOrderItem($item);
            }

            $this->pdo->commit();
            return $orderId;
        } catch (Exception $e) {
            $this->pdo->rollback();
            throw $e;
        }
    }

    /**
     * Create order item
     */
    private function createOrderItem($itemData)
    {
        $sql = "INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $itemData['order_id'],
            $itemData['product_id'],
            $itemData['quantity'],
            $itemData['unit_price'],
            $itemData['total_price']
        ]);
    }

    /**
     * Generate unique order number
     */
    public function generateOrderNumber()
    {
        $prefix = 'ORD';
        $date = date('Ymd');

        // Get last order number for today
        $sql = "SELECT order_number FROM {$this->table} 
                WHERE order_number LIKE ? 
                ORDER BY order_number DESC 
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$prefix . $date . '%']);
        $lastOrder = $stmt->fetch();

        if ($lastOrder) {
            $lastNumber = intval(substr($lastOrder['order_number'], -4));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $date . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus($orderId, $status, $reference = null)
    {
        $updateData = [
            'payment_status' => $status
        ];

        if ($reference) {
            $updateData['payment_reference'] = $reference;
        }

        if ($status === 'paid') {
            $updateData['paid_at'] = date('Y-m-d H:i:s');
        }

        return $this->update($orderId, $updateData);
    }

    /**
     * Update order status
     */
    public function updateOrderStatus($orderId, $status)
    {
        return $this->update($orderId, ['order_status' => $status]);
    }

    /**
     * Get order by order number
     */
    public function findByOrderNumber($orderNumber)
    {
        $sql = "SELECT * FROM {$this->table} WHERE order_number = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$orderNumber]);
        return $stmt->fetch();
    }

    /**
     * Get orders by email
     */
    public function findByEmail($email)
    {
        $sql = "SELECT * FROM {$this->table} WHERE customer_email = ? ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->fetchAll();
    }

    /**
     * Get pending orders
     */
    public function getPendingOrders()
    {
        return $this->findAll(['order_status' => 'pending'], 'created_at DESC');
    }

    /**
     * Get orders by status
     */
    public function getByStatus($status)
    {
        return $this->findAll(['order_status' => $status], 'created_at DESC');
    }

    /**
     * Get order statistics
     * 
     * @param string|null $startDate Start date for filtering (Y-m-d format)
     * @param string|null $endDate End date for filtering (Y-m-d format)
     * @return array|false Array of statistics or false on failure
     */
    public function getStats($startDate = null, $endDate = null)
    {
        $sql = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as paid_orders,
                    SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                    SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as total_revenue,
                    AVG(CASE WHEN payment_status = 'paid' THEN total_amount ELSE NULL END) as average_order_value
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

