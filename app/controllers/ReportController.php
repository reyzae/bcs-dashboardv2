<?php
/**
 * Report Controller
 * Handles report generation and exports
 * Bytebalok Dashboard
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Customer.php';
require_once __DIR__ . '/../helpers/ExportHelper.php';
require_once __DIR__ . '/../helpers/PermissionMiddleware.php';

class ReportController {
    private $pdo;
    private $transactionModel;
    private $productModel;
    private $customerModel;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->transactionModel = new Transaction($pdo);
        $this->productModel = new Product($pdo);
        $this->customerModel = new Customer($pdo);
    }
    
    /**
     * Send JSON response
     */
    private function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
    
    /**
     * Get sales statistics
     */
    public function getSalesStats() {
        try {
            if (session_status() === PHP_SESSION_NONE) { session_start(); }
            PermissionMiddleware::checkPermission('reports.view');
            $period = $_GET['period'] ?? 'month';
            $dateFrom = $_GET['date_from'] ?? null;
            $dateTo = $_GET['date_to'] ?? null;
            
            // Determine date range based on period
            if (!$dateFrom || !$dateTo) {
                $dateTo = date('Y-m-d');
                switch ($period) {
                    case 'today':
                        $dateFrom = $dateTo;
                        break;
                    case 'week':
                        $dateFrom = date('Y-m-d', strtotime('-7 days'));
                        break;
                    case 'quarter':
                        $dateFrom = date('Y-m-d', strtotime('-3 months'));
                        break;
                    case 'year':
                        $dateFrom = date('Y-m-d', strtotime('-1 year'));
                        break;
                    case 'month':
                    default:
                        $dateFrom = date('Y-m-01');
                        break;
                }
            }
            
            // Total sales
            $sql = "SELECT 
                        COUNT(*) as total_transactions,
                        COALESCE(SUM(total_amount), 0) as total_sales,
                        COALESCE(AVG(total_amount), 0) as avg_transaction
                    FROM transactions
                    WHERE status = 'completed'
                    AND DATE(created_at) >= :dateFrom
                    AND DATE(created_at) <= :dateTo";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':dateFrom' => $dateFrom, ':dateTo' => $dateTo]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Calculate growth (compare with previous period)
            $daysDiff = (strtotime($dateTo) - strtotime($dateFrom)) / (60 * 60 * 24);
            $prevDateFrom = date('Y-m-d', strtotime($dateFrom . " -$daysDiff days"));
            $prevDateTo = date('Y-m-d', strtotime($dateTo . " -$daysDiff days"));
            
            $sqlPrev = "SELECT COALESCE(SUM(total_amount), 0) as prev_sales
                        FROM transactions
                        WHERE status = 'completed'
                        AND DATE(created_at) >= :dateFrom
                        AND DATE(created_at) <= :dateTo";
            
            $stmtPrev = $this->pdo->prepare($sqlPrev);
            $stmtPrev->execute([':dateFrom' => $prevDateFrom, ':dateTo' => $prevDateTo]);
            $prevSales = $stmtPrev->fetchColumn();
            
            $growthRate = 0;
            if ($prevSales > 0) {
                $growthRate = (($stats['total_sales'] - $prevSales) / $prevSales) * 100;
            }
            
            $this->sendResponse([
                'success' => true,
                'data' => [
                    'total_sales' => (float)$stats['total_sales'],
                    'total_transactions' => (int)$stats['total_transactions'],
                    'avg_transaction' => (float)$stats['avg_transaction'],
                    'growth_rate' => round($growthRate, 2),
                    'period' => $period,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo
                ]
            ]);
        } catch (Exception $e) {
            $this->sendResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Get top selling products
     */
    public function getTopProducts() {
        try {
            if (session_status() === PHP_SESSION_NONE) { session_start(); }
            PermissionMiddleware::checkPermission('reports.view');
            $limit = $_GET['limit'] ?? 10;
            $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
            $dateTo = $_GET['date_to'] ?? date('Y-m-d');
            
            $sql = "SELECT 
                        p.id,
                        p.name,
                        p.sku,
                        c.name as category_name,
                        SUM(ti.quantity) as units_sold,
                        SUM(ti.total_price) as revenue,
                        COUNT(DISTINCT t.id) as times_sold
                    FROM products p
                    INNER JOIN transaction_items ti ON p.id = ti.product_id
                    INNER JOIN transactions t ON ti.transaction_id = t.id
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE t.status = 'completed'
                    AND DATE(t.created_at) >= :dateFrom
                    AND DATE(t.created_at) <= :dateTo
                    GROUP BY p.id
                    ORDER BY units_sold DESC
                    LIMIT :limit";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':dateFrom', $dateFrom);
            $stmt->bindValue(':dateTo', $dateTo);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Add rank and mock growth
            foreach ($products as $index => &$product) {
                $product['rank'] = $index + 1;
                $product['growth'] = rand(-10, 50); // Mock growth percentage
            }
            
            $this->sendResponse([
                'success' => true,
                'data' => ['products' => $products]
            ]);
        } catch (Exception $e) {
            $this->sendResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Get sales trend data for chart
     */
    public function getSalesTrend() {
        try {
            if (session_status() === PHP_SESSION_NONE) { session_start(); }
            PermissionMiddleware::checkPermission('reports.view');
            $period = $_GET['period'] ?? 'month';
            $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
            $dateTo = $_GET['date_to'] ?? date('Y-m-d');
            
            $sql = "SELECT 
                        DATE(created_at) as date,
                        COUNT(*) as transactions,
                        COALESCE(SUM(total_amount), 0) as revenue
                    FROM transactions
                    WHERE status = 'completed'
                    AND DATE(created_at) >= :dateFrom
                    AND DATE(created_at) <= :dateTo
                    GROUP BY DATE(created_at)
                    ORDER BY date ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':dateFrom' => $dateFrom, ':dateTo' => $dateTo]);
            $trends = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format data for chart
            $labels = [];
            $revenues = [];
            $transactionCounts = [];
            
            foreach ($trends as $trend) {
                $labels[] = date('d/m', strtotime($trend['date']));
                $revenues[] = (float)$trend['revenue'];
                $transactionCounts[] = (int)$trend['transactions'];
            }
            
            $this->sendResponse([
                'success' => true,
                'data' => [
                    'labels' => $labels,
                    'revenues' => $revenues,
                    'transactions' => $transactionCounts
                ]
            ]);
        } catch (Exception $e) {
            $this->sendResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Get category performance data
     */
    public function getCategoryPerformance() {
        try {
            if (session_status() === PHP_SESSION_NONE) { session_start(); }
            PermissionMiddleware::checkPermission('reports.view');
            $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
            $dateTo = $_GET['date_to'] ?? date('Y-m-d');
            
            $sql = "SELECT 
                        c.id,
                        c.name,
                        c.color,
                        COUNT(DISTINCT p.id) as product_count,
                        COALESCE(SUM(ti.total_price), 0) as revenue,
                        COALESCE(SUM(ti.quantity), 0) as units_sold
                    FROM categories c
                    LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1
                    LEFT JOIN transaction_items ti ON p.id = ti.product_id
                    LEFT JOIN transactions t ON ti.transaction_id = t.id 
                        AND t.status = 'completed'
                        AND DATE(t.created_at) >= :dateFrom
                        AND DATE(t.created_at) <= :dateTo
                    WHERE c.is_active = 1
                    GROUP BY c.id
                    ORDER BY revenue DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':dateFrom' => $dateFrom, ':dateTo' => $dateTo]);
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format for pie chart
            $labels = [];
            $values = [];
            $colors = [];
            
            foreach ($categories as $category) {
                $labels[] = $category['name'];
                $values[] = (float)$category['revenue'];
                $colors[] = $category['color'] ?? $this->generateColor();
            }
            
            $this->sendResponse([
                'success' => true,
                'data' => [
                    'categories' => $categories,
                    'chart' => [
                        'labels' => $labels,
                        'values' => $values,
                        'colors' => $colors
                    ]
                ]
            ]);
        } catch (Exception $e) {
            $this->sendResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Generate random color for charts
     */
    private function generateColor() {
        $colors = [
            '#667eea', '#764ba2', '#10b981', '#059669', '#3b82f6',
            '#1d4ed8', '#f59e0b', '#d97706', '#ef4444', '#dc2626'
        ];
        return $colors[array_rand($colors)];
    }
    
    /**
     * Export Sales Report
     */
    public function exportSales($format = 'excel', $dateFrom = null, $dateTo = null) {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        PermissionMiddleware::checkPermission('reports.export');
        // Get sales data
        $sql = "SELECT 
                    DATE(t.created_at) as transaction_date,
                    t.transaction_number,
                    COALESCE(c.name, 'Walk-in Customer') as customer_name,
                    t.total_amount,
                    t.payment_method,
                    u.full_name as cashier,
                    t.status
                FROM transactions t
                LEFT JOIN customers c ON t.customer_id = c.id
                LEFT JOIN users u ON t.user_id = u.id
                WHERE 1=1";
        
        $params = [];
        
        if ($dateFrom) {
            $sql .= " AND DATE(t.created_at) >= :dateFrom";
            $params[':dateFrom'] = $dateFrom;
        }
        if ($dateTo) {
            $sql .= " AND DATE(t.created_at) <= :dateTo";
            $params[':dateTo'] = $dateTo;
        }
        
        $sql .= " ORDER BY t.created_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Prepare data for export
        $exportData = [];
        $totalAmount = 0;
        
        foreach ($data as $row) {
            $exportData[] = [
                $row['transaction_date'],
                $row['transaction_number'],
                $row['customer_name'],
                ExportHelper::formatCurrency($row['total_amount']),
                ucfirst($row['payment_method']),
                $row['cashier'],
                ucfirst($row['status'])
            ];
            $totalAmount += $row['total_amount'];
        }
        
        // Add summary row
        $exportData[] = ['', '', 'TOTAL', ExportHelper::formatCurrency($totalAmount), '', '', ''];
        
        $headers = ['Tanggal', 'No. Transaksi', 'Customer', 'Total', 'Pembayaran', 'Kasir', 'Status'];
        
        // Determine filename and export based on format
        if ($format === 'csv') {
            $filename = 'sales_report_' . date('Ymd_His') . '.csv';
            $filepath = ExportHelper::exportToCSV($exportData, $filename, $headers);
        } elseif ($format === 'excel' || $format === 'xlsx') {
            $filename = 'sales_report_' . date('Ymd_His') . '.xlsx';
            $filepath = ExportHelper::exportToExcel($exportData, $filename, $headers, 'Laporan Penjualan');
            // Get actual filename from filepath (may be .csv if fallback occurred)
            $filename = basename($filepath);
        } elseif ($format === 'pdf') {
            $filename = 'sales_report_' . date('Ymd_His') . '.pdf';
            // Generate HTML for PDF
            $title = 'Laporan Penjualan';
            if ($dateFrom && $dateTo) {
                $title .= '<br><small>Periode: ' . $dateFrom . ' s/d ' . $dateTo . '</small>';
            }
            
            $html = ExportHelper::generateHTMLTable($exportData, $headers, $title);
            $html .= '<p><strong>Total Penjualan: ' . ExportHelper::formatCurrency($totalAmount) . '</strong></p>';
            
            $filepath = ExportHelper::exportToPDF($html, $filename, 'L', [
                'title' => 'Laporan Penjualan',
                'author' => 'Bytebalok Dashboard'
            ]);
            // Get actual filename from filepath (may be .html if fallback occurred)
            $filename = basename($filepath);
        } else {
            // Default to CSV
            $filename = 'sales_report_' . date('Ymd_His') . '.csv';
            $filepath = ExportHelper::exportToCSV($exportData, $filename, $headers);
        }
        
        // Return both filepath and actual filename
        return [
            'filepath' => $filepath,
            'filename' => $filename
        ];
    }
    
    /**
     * Export Inventory Report
     */
    public function exportInventory($format = 'excel') {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        PermissionMiddleware::checkPermission('reports.export');
        // Get inventory data
        $sql = "SELECT 
                    p.sku,
                    p.name,
                    c.name as category,
                    p.stock_quantity,
                    p.min_stock_level,
                    p.price,
                    p.cost_price,
                    (p.price - p.cost_price) * p.stock_quantity as potential_profit,
                    CASE 
                        WHEN p.stock_quantity <= p.min_stock_level THEN 'Low Stock'
                        WHEN p.stock_quantity = 0 THEN 'Out of Stock'
                        ELSE 'In Stock'
                    END as stock_status
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.is_active = 1
                ORDER BY p.name";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Prepare data for export
        $exportData = [];
        $totalValue = 0;
        $totalProfit = 0;
        
        foreach ($data as $row) {
            $value = $row['cost_price'] * $row['stock_quantity'];
            $totalValue += $value;
            $totalProfit += $row['potential_profit'];
            
            $exportData[] = [
                $row['sku'],
                $row['name'],
                $row['category'] ?? '-',
                $row['stock_quantity'],
                $row['min_stock_level'],
                ExportHelper::formatCurrency($row['price']),
                ExportHelper::formatCurrency($row['cost_price']),
                ExportHelper::formatCurrency($value),
                $row['stock_status']
            ];
        }
        
        // Add summary
        $exportData[] = ['', '', '', '', '', '', 'Total Nilai', ExportHelper::formatCurrency($totalValue), ''];
        $exportData[] = ['', '', '', '', '', '', 'Potensi Profit', ExportHelper::formatCurrency($totalProfit), ''];
        
        $headers = ['SKU', 'Nama Produk', 'Kategori', 'Stok', 'Min. Stok', 'Harga Jual', 'Harga Beli', 'Nilai Stok', 'Status'];
        
        // Determine filename and export based on format
        if ($format === 'csv') {
            $filename = 'inventory_report_' . date('Ymd_His') . '.csv';
            $filepath = ExportHelper::exportToCSV($exportData, $filename, $headers);
        } elseif ($format === 'excel' || $format === 'xlsx') {
            $filename = 'inventory_report_' . date('Ymd_His') . '.xlsx';
            $filepath = ExportHelper::exportToExcel($exportData, $filename, $headers, 'Laporan Stok');
            // Get actual filename from filepath (may be .csv if fallback occurred)
            $filename = basename($filepath);
        } elseif ($format === 'pdf') {
            $filename = 'inventory_report_' . date('Ymd_His') . '.pdf';
            $html = ExportHelper::generateHTMLTable($exportData, $headers, 'Laporan Inventori');
            $html .= '<p><strong>Total Nilai Stok: ' . ExportHelper::formatCurrency($totalValue) . '</strong></p>';
            $html .= '<p><strong>Potensi Profit: ' . ExportHelper::formatCurrency($totalProfit) . '</strong></p>';
            $filepath = ExportHelper::exportToPDF($html, $filename, 'L', [
                'title' => 'Laporan Inventori'
            ]);
            // Get actual filename from filepath (may be .html if fallback occurred)
            $filename = basename($filepath);
        } else {
            $filename = 'inventory_report_' . date('Ymd_His') . '.csv';
            $filepath = ExportHelper::exportToCSV($exportData, $filename, $headers);
        }
        
        return [
            'filepath' => $filepath,
            'filename' => $filename
        ];
    }
    
    /**
     * Export Customer Report
     */
    public function exportCustomers($format = 'excel') {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        PermissionMiddleware::checkPermission('reports.export');
        // Get customer data with transaction summary
        $sql = "SELECT 
                    c.name,
                    c.email,
                    c.phone,
                    c.address,
                    COUNT(t.id) as total_transactions,
                    COALESCE(SUM(t.total_amount), 0) as total_spent,
                    MAX(t.created_at) as last_transaction
                FROM customers c
                LEFT JOIN transactions t ON c.id = t.customer_id
                GROUP BY c.id
                ORDER BY total_spent DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Prepare data for export
        $exportData = [];
        $totalRevenue = 0;
        
        foreach ($data as $row) {
            $totalRevenue += $row['total_spent'];
            
            $exportData[] = [
                $row['name'],
                $row['email'] ?? '-',
                $row['phone'] ?? '-',
                $row['address'] ?? '-',
                $row['total_transactions'],
                ExportHelper::formatCurrency($row['total_spent']),
                $row['last_transaction'] ? ExportHelper::formatDate($row['last_transaction']) : 'Belum ada'
            ];
        }
        
        // Add summary
        $exportData[] = ['', '', '', 'TOTAL', '', ExportHelper::formatCurrency($totalRevenue), ''];
        
        $headers = ['Nama Customer', 'Email', 'Telepon', 'Alamat', 'Total Transaksi', 'Total Belanja', 'Transaksi Terakhir'];
        
        // Determine filename and export based on format
        if ($format === 'csv') {
            $filename = 'customer_report_' . date('Ymd_His') . '.csv';
            $filepath = ExportHelper::exportToCSV($exportData, $filename, $headers);
        } elseif ($format === 'excel' || $format === 'xlsx') {
            $filename = 'customer_report_' . date('Ymd_His') . '.xlsx';
            $filepath = ExportHelper::exportToExcel($exportData, $filename, $headers, 'Laporan Customer');
            // Get actual filename from filepath (may be .csv if fallback occurred)
            $filename = basename($filepath);
        } elseif ($format === 'pdf') {
            $filename = 'customer_report_' . date('Ymd_His') . '.pdf';
            $html = ExportHelper::generateHTMLTable($exportData, $headers, 'Laporan Customer');
            $html .= '<p><strong>Total Revenue dari Customer: ' . ExportHelper::formatCurrency($totalRevenue) . '</strong></p>';
            $filepath = ExportHelper::exportToPDF($html, $filename, 'L', [
                'title' => 'Laporan Customer'
            ]);
            // Get actual filename from filepath (may be .html if fallback occurred)
            $filename = basename($filepath);
        } else {
            $filename = 'customer_report_' . date('Ymd_His') . '.csv';
            $filepath = ExportHelper::exportToCSV($exportData, $filename, $headers);
        }
        
        return [
            'filepath' => $filepath,
            'filename' => $filename
        ];
    }
    
    /**
     * Export Product Performance Report
     */
    public function exportProductPerformance($format = 'excel', $dateFrom = null, $dateTo = null) {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        PermissionMiddleware::checkPermission('reports.export');
        // Get product sales data
        $sql = "SELECT 
                    p.name as product_name,
                    c.name as category,
                    COUNT(ti.id) as times_sold,
                    SUM(ti.quantity) as total_quantity,
                    SUM(ti.subtotal) as total_revenue,
                    AVG(ti.subtotal / ti.quantity) as avg_price
                FROM transaction_items ti
                JOIN products p ON ti.product_id = p.id
                LEFT JOIN categories c ON p.category_id = c.id
                JOIN transactions t ON ti.transaction_id = t.id
                WHERE 1=1";
        
        $params = [];
        
        if ($dateFrom) {
            $sql .= " AND DATE(t.created_at) >= :dateFrom";
            $params[':dateFrom'] = $dateFrom;
        }
        if ($dateTo) {
            $sql .= " AND DATE(t.created_at) <= :dateTo";
            $params[':dateTo'] = $dateTo;
        }
        
        $sql .= " GROUP BY ti.product_id
                  ORDER BY total_revenue DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Prepare data for export
        $exportData = [];
        $totalRevenue = 0;
        $totalQty = 0;
        
        foreach ($data as $row) {
            $totalRevenue += $row['total_revenue'];
            $totalQty += $row['total_quantity'];
            
            $exportData[] = [
                $row['product_name'],
                $row['category'] ?? '-',
                $row['times_sold'],
                $row['total_quantity'],
                ExportHelper::formatCurrency($row['avg_price']),
                ExportHelper::formatCurrency($row['total_revenue'])
            ];
        }
        
        // Add summary
        $exportData[] = ['TOTAL', '', '', $totalQty, '', ExportHelper::formatCurrency($totalRevenue)];
        
        $headers = ['Nama Produk', 'Kategori', 'Frekuensi Terjual', 'Total Qty', 'Rata-rata Harga', 'Total Revenue'];
        
        // Determine filename and export based on format
        if ($format === 'csv') {
            $filename = 'product_performance_' . date('Ymd_His') . '.csv';
            $filepath = ExportHelper::exportToCSV($exportData, $filename, $headers);
        } elseif ($format === 'excel' || $format === 'xlsx') {
            $filename = 'product_performance_' . date('Ymd_His') . '.xlsx';
            $filepath = ExportHelper::exportToExcel($exportData, $filename, $headers, 'Performa Produk');
            // Get actual filename from filepath (may be .csv if fallback occurred)
            $filename = basename($filepath);
        } elseif ($format === 'pdf') {
            $filename = 'product_performance_' . date('Ymd_His') . '.pdf';
            $title = 'Laporan Performa Produk';
            if ($dateFrom && $dateTo) {
                $title .= '<br><small>Periode: ' . $dateFrom . ' s/d ' . $dateTo . '</small>';
            }
            $html = ExportHelper::generateHTMLTable($exportData, $headers, $title);
            $html .= '<p><strong>Total Revenue: ' . ExportHelper::formatCurrency($totalRevenue) . '</strong></p>';
            $filepath = ExportHelper::exportToPDF($html, $filename, 'L', [
                'title' => 'Laporan Performa Produk'
            ]);
            // Get actual filename from filepath (may be .html if fallback occurred)
            $filename = basename($filepath);
        } else {
            $filename = 'product_performance_' . date('Ymd_His') . '.csv';
            $filepath = ExportHelper::exportToCSV($exportData, $filename, $headers);
        }
        
        return [
            'filepath' => $filepath,
            'filename' => $filename
        ];
    }
}
