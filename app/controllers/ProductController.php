<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../helpers/ImageUploader.php';

/**
 * Bytebalok Product Controller
 * Handles product management operations
 */

class ProductController extends BaseController {
    private $productModel;
    private $categoryModel;
    
    public function __construct($pdo) {
        parent::__construct($pdo);
        $this->productModel = new Product($pdo);
        $this->categoryModel = new Category($pdo);
    }
    
    /**
     * Get list of products
     */
    public function list() {
        $this->checkAuthentication();
        $this->checkPermission('products.view');
        $page = intval($_GET['page'] ?? 1);
        $limit = intval($_GET['limit'] ?? 20);
        $search = $_GET['search'] ?? '';
        $categoryId = $_GET['category_id'] ?? null;
        $isActiveFilter = $_GET['is_active'] ?? null; // null = All, '1' = Active, '0' = Inactive
        $offset = ($page - 1) * $limit;
        
        // Build conditions
        $conditions = [];
        
        // Category filter
        if ($categoryId) {
            $conditions['category_id'] = $categoryId;
        }
        
        // Status filter (is_active)
        if ($isActiveFilter !== null && $isActiveFilter !== '') {
            $conditions['is_active'] = intval($isActiveFilter);
        }
        // If $isActiveFilter is null or empty string, don't filter by is_active (show all)
        
        if ($search) {
            $products = $this->productModel->search($search, $categoryId, $limit, $isActiveFilter);
        } else {
            $products = $this->productModel->findAllWithCategory($conditions, 'name ASC', $limit, $offset);
        }
        
        $total = $this->productModel->count($conditions);
        
        $this->sendSuccess([
            'products' => $products,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }

    /**
     * Public product listing for Shop (no authentication)
     */
    public function listPublic() {
        $page = intval($_GET['page'] ?? 1);
        $limit = intval($_GET['limit'] ?? 20);
        $search = $_GET['search'] ?? '';
        $categoryId = $_GET['category_id'] ?? null;
        $offset = ($page - 1) * $limit;

        // Only active products for public
        $conditions = ['is_active' => 1];
        if ($categoryId) { $conditions['category_id'] = $categoryId; }

        if ($search) {
            $products = $this->productModel->search($search, $categoryId, $limit, 1);
        } else {
            $products = $this->productModel->findAllWithCategory($conditions, 'name ASC', $limit, $offset);
        }

        $total = $this->productModel->count($conditions);

        // Minimal safe fields for shop
        $safe = array_map(function($p){
            return [
                'id' => $p['id'],
                'name' => $p['name'],
                'price' => (float)($p['price'] ?? 0),
                'image' => $p['image'] ?? null,
                'image_url' => $p['image_url'] ?? null,
                'category_id' => $p['category_id'] ?? null,
                'created_at' => $p['created_at'] ?? null
            ];
        }, $products);

        $this->sendSuccess([
            'products' => $safe,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }
    
    /**
     * Get single product
     */
    public function get() {
        $id = intval($_GET['id']);
        
        if (!$id) {
            $this->sendError('Product ID is required', 400);
        }
        
        $product = $this->productModel->findWithCategory($id);
        
        if (!$product) {
            $this->sendError('Product not found', 404);
        }
        
        $this->sendSuccess($product);
    }
    
    /**
     * Create new product - ENHANCED WITH VALIDATION
     */
    public function create() {
        $this->checkAuthentication();
        $this->checkPermission('products.create');
        
        if ($this->getMethod() !== 'POST') {
            $this->sendError('Method not allowed', 405);
        }
        
        $data = $this->getRequestData();
        
        // Debug: Log received data
        error_log('📦 CREATE Product - Data received: ' . json_encode($data));
        error_log('🖼️ Image field value: ' . ($data['image'] ?? 'NOT SET'));
        
        // Convert unit_price to price for database
        if (isset($data['unit_price']) && !isset($data['price'])) {
            $data['price'] = $data['unit_price'];
            unset($data['unit_price']);
        }
        
        // Enhanced validation
        $this->validate($data, [
            'name' => ['required', 'minLength:2', 'maxLength:200'],
            'sku' => ['required', 'sku'],
            'category_id' => ['required', 'integer'],
            'price' => ['required', 'price'],
            'stock_quantity' => ['stock'],
            'barcode' => ['barcode']
        ]);
        
        try {
            $result = $this->executeWithTransaction(function() use ($data) {
                // Sanitize input
                $data = $this->sanitizeInput($data);
                
                // Debug: Log after sanitize
                error_log('🧹 After sanitize - Image field: ' . ($data['image'] ?? 'NOT SET'));
                
                // Check if SKU already exists
                if ($this->productModel->skuExists($data['sku'])) {
                    throw new Exception('SKU already exists');
                }
                
                // Check if barcode already exists (if provided)
                if (!empty($data['barcode']) && $this->productModel->barcodeExists($data['barcode'])) {
                    throw new Exception('Barcode already exists');
                }
                
                // Validate category exists
                $category = $this->categoryModel->find($data['category_id']);
                if (!$category) {
                    throw new Exception('Category not found');
                }
                
                $productId = $this->productModel->create($data);
                
                if (!$productId) {
                    throw new Exception('Failed to create product');
                }
                
                $this->logAction('create', 'product', $productId, $data);
                
                return ['id' => $productId];
            });
            
            $this->sendSuccess($result, 'Product created successfully');
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 500);
        }
    }
    
    /**
     * Update product - ENHANCED WITH VALIDATION
     */
    public function update() {
        $this->checkAuthentication();
        $this->checkPermission('products.update');
        
        if ($this->getMethod() !== 'POST') {
            $this->sendError('Method not allowed', 405);
        }
        
        $id = intval($_GET['id']);
        if (!$id) {
            $this->sendError('Product ID is required', 400);
        }
        
        $data = $this->getRequestData();
        
        // Debug: Log received data
        error_log('📦 UPDATE Product #' . $id . ' - Data received: ' . json_encode($data));
        error_log('🖼️ Image field value: ' . ($data['image'] ?? 'NOT SET'));
        
        // Convert unit_price to price for database
        if (isset($data['unit_price']) && !isset($data['price'])) {
            $data['price'] = $data['unit_price'];
            unset($data['unit_price']);
        }
        
        // Enhanced validation for provided fields
        $validationRules = [];
        if (isset($data['name'])) $validationRules['name'] = ['minLength:2', 'maxLength:200'];
        if (isset($data['sku'])) $validationRules['sku'] = ['sku'];
        if (isset($data['price'])) $validationRules['price'] = ['price'];
        if (isset($data['stock_quantity'])) $validationRules['stock_quantity'] = ['stock'];
        if (isset($data['barcode'])) $validationRules['barcode'] = ['barcode'];
        if (isset($data['category_id'])) $validationRules['category_id'] = ['integer'];
        
        if (!empty($validationRules)) {
            $this->validate($data, $validationRules);
        }
        
        try {
            $this->executeWithTransaction(function() use ($id, $data) {
                // Check if product exists
                $oldProduct = $this->productModel->find($id);
                if (!$oldProduct) {
                    throw new Exception('Product not found');
                }
                
                // Sanitize input
                $data = $this->sanitizeInput($data);
                
                // Debug: Log after sanitize
                error_log('🧹 After sanitize - Image field: ' . ($data['image'] ?? 'NOT SET'));
                
                // Check if SKU already exists (excluding current product)
                if (isset($data['sku']) && $this->productModel->skuExists($data['sku'], $id)) {
                    throw new Exception('SKU already exists');
                }
                
                // Check if barcode already exists (excluding current product)
                if (!empty($data['barcode']) && $this->productModel->barcodeExists($data['barcode'], $id)) {
                    throw new Exception('Barcode already exists');
                }
                
                $success = $this->productModel->update($id, $data);
                
                if (!$success) {
                    throw new Exception('Failed to update product');
                }
                
                $this->logAction('update', 'product', $id, [
                    'old' => $oldProduct,
                    'new' => $data
                ]);
            });
            
            $this->sendSuccess(null, 'Product updated successfully');
        } catch (Exception $e) {
            $statusCode = $e->getMessage() === 'Product not found' ? 404 : 500;
            $this->sendError($e->getMessage(), $statusCode);
        }
    }
    
    /**
     * Delete product
     */
    public function delete() {
        $this->requireRole(['admin', 'manager']);
        
        if ($this->getMethod() !== 'POST') {
            $this->sendError('Method not allowed', 405);
        }
        
        $id = intval($_GET['id']);
        if (!$id) {
            $this->sendError('Product ID is required', 400);
        }
        
        // Check if product exists
        $product = $this->productModel->find($id);
        if (!$product) {
            $this->sendError('Product not found', 404);
        }
        
        // Soft delete by setting is_active to 0
        $success = $this->productModel->update($id, ['is_active' => 0]);
        
        if ($success) {
            $this->logAction('delete', 'products', $id, $product, ['is_active' => 0]);
            $this->sendSuccess(null, 'Product deleted successfully');
        } else {
            $this->sendError('Failed to delete product', 500);
        }
    }
    
    /**
     * Update product stock
     */
    public function updateStock() {
        if ($this->getMethod() !== 'POST') {
            $this->sendError('Method not allowed', 405);
        }
        
        $data = $this->getRequestData();
        $this->validateRequired($data, ['product_id', 'quantity', 'movement_type']);
        
        $productId = intval($data['product_id']);
        $quantity = intval($data['quantity']);
        $movementType = $data['movement_type'];
        
        // Validate movement type
        $validTypes = ['in', 'out', 'adjustment', 'return'];
        if (!in_array($movementType, $validTypes)) {
            $this->sendError('Invalid movement type', 400);
        }
        
        // Check if product exists
        $product = $this->productModel->find($productId);
        if (!$product) {
            $this->sendError('Product not found', 404);
        }
        
        try {
            $this->productModel->updateStock(
                $productId,
                $quantity,
                $movementType,
                $data['reference_type'] ?? null,
                $data['reference_id'] ?? null,
                $this->user['id'],
                $data['notes'] ?? null
            );
            
            $this->logAction('update_stock', 'products', $productId, 
                ['stock_quantity' => $product['stock_quantity']], 
                ['stock_quantity' => $product['stock_quantity'] + $quantity]
            );
            
            $this->sendSuccess(null, 'Stock updated successfully');
        } catch (Exception $e) {
            $this->sendError('Failed to update stock: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get low stock products
     */
    public function getLowStock() {
        $products = $this->productModel->getLowStock();
        $this->sendSuccess($products);
    }
    
    /**
     * Get product statistics
     */
    public function getStats() {
        $stats = $this->productModel->getStats();
        $this->sendSuccess($stats);
    }
    
    /**
     * Upload product image
     */
    public function uploadImage() {
        $this->checkAuthentication();
        
        if ($this->getMethod() !== 'POST') {
            $this->sendError('Method not allowed', 405);
            return;
        }
        
        // Check if file uploaded
        if (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
            $this->sendError('No image file uploaded', 400);
            return;
        }
        
        try {
            $imageUploader = new ImageUploader('products');
            $result = $imageUploader->upload($_FILES['image']);
            
            if ($result['success']) {
                $this->sendSuccess([
                    'filename' => $result['filename'],
                    'path' => $result['path'],
                    'url' => '../' . $result['path']
                ], 'Image uploaded successfully');
            } else {
                $this->sendError($result['message'], 400);
            }
        } catch (Exception $e) {
            error_log('Image upload error: ' . $e->getMessage());
            $this->sendError('Failed to upload image: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Export products to CSV/Excel
     */
    public function export() {
        $format = $_GET['format'] ?? 'csv';
        $search = $_GET['search'] ?? '';
        $categoryId = $_GET['category_id'] ?? '';
        $status = $_GET['status'] ?? '';
        
        // Build query
        $sql = "SELECT 
                    p.id,
                    p.name,
                    p.sku,
                    p.barcode,
                    c.name as category_name,
                    p.price,
                    p.cost_price,
                    p.stock_quantity,
                    p.min_stock_level,
                    p.unit,
                    p.description,
                    p.is_active,
                    p.created_at,
                    p.updated_at,
                    vp.times_sold,
                    vp.total_quantity_sold,
                    vp.total_revenue,
                    vp.estimated_profit,
                    vp.last_sold_date
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN v_product_performance vp ON vp.id = p.id
                WHERE 1=1";
        
        $params = [];
        
        // Search filter
        if ($search) {
            $sql .= " AND (p.name LIKE ? OR p.sku LIKE ? OR p.barcode LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        // Category filter
        if ($categoryId) {
            $sql .= " AND p.category_id = ?";
            $params[] = $categoryId;
        }
        
        // Status filter
        if ($status !== '') {
            $sql .= " AND p.is_active = ?";
            $params[] = intval($status);
        }
        
        $sql .= " ORDER BY p.name ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Prepare data for export
        $data = [];
        foreach ($products as $product) {
            $data[] = [
                $product['sku'],
                $product['name'],
                $product['category_name'] ?? 'No Category',
                $product['barcode'] ?? '-',
                ExportHelper::formatCurrency($product['price']),
                ExportHelper::formatCurrency($product['cost_price']),
                $product['stock_quantity'],
                $product['unit'] ?? 'pcs',
                $product['min_stock_level'],
                (int)($product['times_sold'] ?? 0),
                (int)($product['total_quantity_sold'] ?? 0),
                ExportHelper::formatCurrency((float)($product['total_revenue'] ?? 0)),
                ExportHelper::formatCurrency((float)($product['estimated_profit'] ?? 0)),
                !empty($product['last_sold_date']) ? ExportHelper::formatDate($product['last_sold_date'], 'd/m/Y') : '-',
                $product['is_active'] ? 'Active' : 'Inactive',
                ExportHelper::formatDate($product['created_at'], 'd/m/Y')
            ];
        }
        
        $headers = [
            'SKU',
            'Product Name',
            'Category',
            'Barcode',
            'Price',
            'Cost Price',
            'Stock Quantity',
            'Unit',
            'Min Stock',
            'Times Sold',
            'Total Quantity Sold',
            'Total Revenue',
            'Estimated Profit',
            'Last Sold Date',
            'Status',
            'Created Date'
        ];
        
        $filename = 'products_' . date('Y-m-d_His') . '.' . $format;
        
        try {
            if ($format === 'excel' || $format === 'xlsx') {
                $filepath = ExportHelper::exportToExcel($data, $filename, $headers, 'Products');
            } else if ($format === 'pdf') {
                $html = ExportHelper::generateHTMLTable($data, $headers, 'Products');
                $filepath = ExportHelper::exportToPDF($html, $filename, 'L');
            } else {
                // Default to CSV
                $filepath = ExportHelper::exportToCSV($data, $filename, $headers);
            }
            
            // Send file to browser
            if (file_exists($filepath)) {
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
                header('Content-Length: ' . filesize($filepath));
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                
                readfile($filepath);
                
                // Clean up - delete file after sending
                unlink($filepath);
                exit;
            } else {
                $this->sendError('Failed to generate export file', 500);
            }
            
        } catch (Exception $e) {
            error_log("Export Error: " . $e->getMessage());
            $this->sendError('Export failed: ' . $e->getMessage(), 500);
        }
    }
}

// Routing
if (!isset($_GET['controller'])) {
    $productController = new ProductController($pdo);
    $action = $_GET['action'] ?? '';
    switch ($action) {
        case 'list':
            $productController->list();
            break;
        case 'create':
            $productController->create();
            break;
        case 'update':
            $productController->update();
            break;
        case 'delete':
            $productController->delete();
            break;
        case 'updateStock':
            $productController->updateStock();
            break;
        case 'getLowStock':
            $productController->getLowStock();
            break;
        case 'getStats':
            $productController->getStats();
            break;
        case 'uploadImage':
            $productController->uploadImage();
            break;
        case 'export':
            $productController->export();
            break;
        default:
            $productController->sendError('Invalid action', 400);
    }
}

