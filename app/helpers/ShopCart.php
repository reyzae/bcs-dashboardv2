<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Product.php';

/**
 * ShopCart Helper
 * Simple session-based cart management for public shop
 */
class ShopCart {
    private $pdo;
    private $productModel;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // Ensure cart structure exists
        if (!isset($_SESSION['shop_cart'])) {
            $_SESSION['shop_cart'] = [
                'items' => [], // product_id => [id, name, price, qty, image]
            ];
        }

        // DB connection and model
        $database = new Database();
        $this->pdo = $database->getConnection();
        $this->productModel = new Product($this->pdo);
    }

    /** Add product to cart */
    public function addItem($productId, $qty = 1) {
        $qty = max(1, intval($qty));
        $product = $this->productModel->find($productId);
        if (!$product || intval($product['is_active']) !== 1) {
            return ['success' => false, 'message' => 'Produk tidak tersedia'];
        }

        // Validate stock
        $stock = intval($product['stock_quantity'] ?? 0);
        if ($stock <= 0) {
            return ['success' => false, 'message' => 'Stok habis'];
        }

        $existingQty = $_SESSION['shop_cart']['items'][$productId]['qty'] ?? 0;
        $newQty = min($stock, $existingQty + $qty);

        $_SESSION['shop_cart']['items'][$productId] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => floatval($product['unit_price'] ?? $product['price']),
            'qty' => $newQty,
            'image' => $product['image'] ?? null,
        ];

        return ['success' => true, 'message' => 'Ditambahkan ke keranjang'];
    }

    /** Update item quantity */
    public function updateItem($productId, $qty) {
        $qty = max(0, intval($qty));
        if (!isset($_SESSION['shop_cart']['items'][$productId])) {
            return ['success' => false, 'message' => 'Item tidak ditemukan'];
        }

        if ($qty === 0) {
            unset($_SESSION['shop_cart']['items'][$productId]);
            return ['success' => true, 'message' => 'Item dihapus'];
        }

        // Check stock
        $product = $this->productModel->find($productId);
        $stock = intval($product['stock_quantity'] ?? 0);
        $_SESSION['shop_cart']['items'][$productId]['qty'] = min($stock, $qty);
        return ['success' => true, 'message' => 'Jumlah diperbarui'];
    }

    /** Remove item */
    public function removeItem($productId) {
        if (isset($_SESSION['shop_cart']['items'][$productId])) {
            unset($_SESSION['shop_cart']['items'][$productId]);
        }
        return ['success' => true, 'message' => 'Item dihapus'];
    }

    /** Clear cart */
    public function clear() {
        $_SESSION['shop_cart'] = ['items' => []];
    }

    /** Get items */
    public function getItems() {
        return array_values($_SESSION['shop_cart']['items']);
    }

    /** Calculate subtotal */
    public function getSubtotal() {
        $subtotal = 0.0;
        foreach ($_SESSION['shop_cart']['items'] as $item) {
            $subtotal += floatval($item['price']) * intval($item['qty']);
        }
        return $subtotal;
    }

    /** Simple shipping estimator */
    public function estimateShipping($destination = null) {
        // Flat rate for demo; can be replaced with real logic
        $subtotal = $this->getSubtotal();
        if ($subtotal >= 250000) {
            return 0; // Free shipping threshold
        }
        return 20000; // Flat IDR 20k
    }

    /** Compute totals */
    public function getTotals() {
        $subtotal = $this->getSubtotal();
        $shipping = $this->estimateShipping();
        $discount = 0.0; // Placeholder for coupons
        $tax = 0.0; // Optional tax if needed
        $total = max(0, $subtotal - $discount + $shipping + $tax);
        return compact('subtotal', 'discount', 'tax', 'shipping', 'total');
    }
}