<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../helpers/SecurityMiddleware.php';
require_once __DIR__ . '/../helpers/Validator.php';
require_once __DIR__ . '/../helpers/ShopCart.php';

/**
 * ShopController
 * Handles public shop pages: catalog, cart, checkout, order status
 */
class ShopController {
    private $pdo;
    private $productModel;
    private $orderModel;
    private $cart;

    public function __construct() {
        $database = new Database();
        $this->pdo = $database->getConnection();
        $this->productModel = new Product($this->pdo);
        $this->orderModel = new Order($this->pdo);
        $this->cart = new ShopCart();
    }

    /** Catalog listing with basic search/filter */
    public function index() {
        $query = isset($_GET['q']) ? trim($_GET['q']) : '';
        $categoryId = isset($_GET['category']) ? intval($_GET['category']) : null;
        $products = [];
        if ($query !== '') {
            // Tampilkan lebih banyak hasil saat pencarian, hanya produk aktif
            $products = $this->productModel->search($query, $categoryId, 100, 1);
        } else {
            // Hilangkan limit agar semua produk aktif tampil
            $products = $this->productModel->findAllWithCategory(['is_active' => 1], 'name ASC', null, null);
        }

        return [
            'products' => $products,
            'cart_count' => count($this->cart->getItems()),
            'query' => $query
        ];
    }

    /** Cart page: handle POST actions then render */
    public function cart() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            SecurityMiddleware::checkCsrfToken();
            $action = $_POST['action'] ?? '';
            $productId = intval($_POST['product_id'] ?? 0);
            $qty = intval($_POST['qty'] ?? 1);
            $result = ['success' => false, 'message' => 'Aksi tidak valid'];

            switch ($action) {
                case 'add':
                    $result = $this->cart->addItem($productId, $qty);
                    break;
                case 'update':
                    $result = $this->cart->updateItem($productId, $qty);
                    break;
                case 'remove':
                    $result = $this->cart->removeItem($productId);
                    break;
            }

            // For AJAX request, return JSON
            if (SecurityMiddleware::isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode(array_merge($result, [
                    'totals' => $this->cart->getTotals(),
                    'items' => $this->cart->getItems()
                ]));
                exit;
            }
        }

        return [
            'items' => $this->cart->getItems(),
            'totals' => $this->cart->getTotals(),
            'csrf_token' => $_SESSION['csrf_token'] ?? SecurityMiddleware::generateCsrfToken()
        ];
    }

    /** Checkout: validate, create order, and show summary */
    public function checkout() {
        $items = $this->cart->getItems();
        $totals = $this->cart->getTotals();
        $errors = [];
        $orderNumber = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            SecurityMiddleware::checkCsrfToken();
            $data = [
                'customer_name' => SecurityMiddleware::sanitizeInput($_POST['customer_name'] ?? ''),
                'customer_email' => SecurityMiddleware::sanitizeInput($_POST['customer_email'] ?? ''),
                'customer_phone' => SecurityMiddleware::sanitizeInput($_POST['customer_phone'] ?? ''),
                'customer_address' => SecurityMiddleware::sanitizeInput($_POST['customer_address'] ?? ''),
                'payment_method' => SecurityMiddleware::sanitizeInput($_POST['payment_method'] ?? 'qris'),
            ];

            // Validate basic fields
            $validation = Validator::validate($data, [
                'customer_name' => ['required', 'minLength:3'],
                'customer_email' => ['required', 'email'],
                'customer_phone' => ['required', 'phone'],
                'customer_address' => ['required', 'minLength:10'],
                'payment_method' => ['required', 'in:qris,transfer,cash']
            ]);

            if ($validation !== true) {
                $errors = $validation;
            } elseif (empty($items)) {
                $errors = ['cart' => 'Keranjang kosong'];
            } else {
                // Build order data
                $orderData = [
                    'customer_name' => $data['customer_name'],
                    'customer_email' => $data['customer_email'],
                    'customer_phone' => $data['customer_phone'],
                    'customer_address' => $data['customer_address'],
                    'subtotal' => $totals['subtotal'],
                    'discount_amount' => $totals['discount'],
                    'tax_amount' => $totals['tax'],
                    'shipping_amount' => $totals['shipping'],
                    'total_amount' => $totals['total'],
                    'payment_method' => $data['payment_method'],
                    'payment_status' => 'pending',
                    'order_status' => 'pending',
                    'notes' => ''
                ];

                // Prepare order items
                $orderItems = [];
                foreach ($items as $item) {
                    $orderItems[] = [
                        'product_id' => $item['id'],
                        'quantity' => $item['qty'],
                        'unit_price' => $item['price'],
                        'total_price' => $item['price'] * $item['qty']
                    ];
                }

                // Create order with items
                try {
                    $orderId = $this->orderModel->createWithItems($orderData, $orderItems);
                    $order = $this->orderModel->find($orderId);
                    $orderNumber = $order['order_number'] ?? null;

                    // Immediately reduce stock for each ordered item (reservation)
                    // Prevent double deduction by using reference_type 'order'
                    foreach ($orderItems as $oi) {
                        $this->productModel->updateStock(
                            (int)$oi['product_id'],
                            -(int)$oi['quantity'],
                            'out',
                            'order',
                            (int)$orderId,
                            null,
                            'Shop order ' . ($orderNumber ?: $orderId)
                        );
                    }

                    // Clear cart after order creation
                    $this->cart->clear();
                } catch (Exception $e) {
                    $errors = ['order' => 'Gagal membuat pesanan: ' . $e->getMessage()];
                }
            }
        }

        return [
            'items' => $items,
            'totals' => $totals,
            'errors' => $errors,
            'order_number' => $orderNumber,
            'csrf_token' => $_SESSION['csrf_token'] ?? SecurityMiddleware::generateCsrfToken()
        ];
    }

    public function contact() {
        $settings = [
            'company_name' => 'Bytebalok',
            'company_email' => 'info@bytebalok.com',
            'company_phone' => '+62 21 1234 5678',
            'company_address' => 'Jl. Example No. 123, Jakarta'
        ];
        try {
            $result = $this->pdo->query("SHOW TABLES LIKE 'settings'");
            if ($result && $result->rowCount() > 0) {
                $stmt = $this->pdo->prepare("SELECT `key`, `value` FROM settings WHERE `key` IN ('company_name','company_email','company_phone','company_address')");
                $stmt->execute();
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $settings[$row['key']] = $row['value'] ?? $settings[$row['key']];
                }
            }
        } catch (Exception $e) {}
        return [
            'settings' => $settings,
            'cart_count' => count($this->cart->getItems())
        ];
    }

    /** Order status page */
    public function orderStatus() {
        $orderNumber = isset($_GET['code']) ? trim($_GET['code']) : '';
        $order = null;
        if ($orderNumber !== '') {
            $order = $this->orderModel->findByOrderNumber($orderNumber);
            if ($order) {
                $order['items'] = $this->orderModel->getItems($order['id']);
            }
        }
        return [
            'order' => $order,
            'code' => $orderNumber
        ];
    }
}