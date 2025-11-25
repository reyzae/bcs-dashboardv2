<?php
/**
 * Enhanced Dashboard Controller
 * Handles all dashboard API requests for different roles
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../models/Customer.php';
require_once __DIR__ . '/../models/User.php';

class DashboardController extends BaseController {
    private $productModel;
    private $transactionModel;
    private $customerModel;
    private $userModel;
    
    public function __construct($pdo) {
        parent::__construct($pdo);
        $this->productModel = new Product($pdo);
        $this->transactionModel = new Transaction($pdo);
        $this->customerModel = new Customer($pdo);
        $this->userModel = new User($pdo);
    }
    
    /**
     * Get general dashboard stats with period filter
     */
    public function stats() {
        try {
            $this->checkAuthentication();
            
            // Get period parameter
            $period = $_GET['period'] ?? 'month';
            $fromDate = $_GET['from'] ?? null;
            $toDate = $_GET['to'] ?? null;
            
            // Calculate date range based on period
            $dateRange = $this->getDateRange($period, $fromDate, $toDate);
            $startDate = $dateRange['start'];
            $endDate = $dateRange['end'];
            
            // Get stats for selected period
            $sql = "SELECT 
                COUNT(*) as today_transactions,
                COALESCE(SUM(total_amount), 0) as today_sales,
                COALESCE(AVG(total_amount), 0) as avg_transaction
            FROM transactions
            WHERE DATE(created_at) >= ? AND DATE(created_at) <= ? AND status = 'completed'";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$startDate, $endDate]);
            $periodStats = $stmt->fetch();
            
            // Get previous period for comparison
            $prevRange = $this->getPreviousPeriodRange($period, $fromDate, $toDate);
            $stmt->execute([$prevRange['start'], $prevRange['end']]);
            $prevStats = $stmt->fetch();
            
            // Calculate change
            $salesChange = 0;
            if ($prevStats['today_sales'] > 0) {
                $salesChange = (($periodStats['today_sales'] - $prevStats['today_sales']) / $prevStats['today_sales']) * 100;
            } elseif ($periodStats['today_sales'] > 0 && $prevStats['today_sales'] == 0) {
                $salesChange = 100; // 100% increase dari 0
            }
            
            // Total customers
            $totalCustomers = $this->customerModel->count(['is_active' => 1]);
            
            // New customers in selected period
            $newCustomersSql = "SELECT COUNT(*) as count FROM customers 
                WHERE created_at >= ? AND created_at <= ? AND is_active = 1";
            $newCustomersStmt = $this->pdo->prepare($newCustomersSql);
            $newCustomersStmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            $newCustomers = $newCustomersStmt->fetch()['count'];
            
            // Low stock count (always current, not period-based)
            $lowStockSql = "SELECT COUNT(*) as count 
                FROM products 
                WHERE stock_quantity < min_stock_level AND is_active = 1";
            $lowStockStmt = $this->pdo->query($lowStockSql);
            $lowStockCount = $lowStockStmt->fetch()['count'];
            
            $data = [
                'today_sales' => (float)$periodStats['today_sales'],
                'today_transactions' => (int)$periodStats['today_transactions'],
                'avg_transaction' => (float)$periodStats['avg_transaction'],
                'total_customers' => $totalCustomers,
                'new_customers' => $newCustomers,
                'low_stock_count' => $lowStockCount,
                'sales_change' => round($salesChange, 2)
            ];
            
            $this->sendSuccess($data);
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 500);
        }
    }
    
    /**
     * Get date range based on period
     */
    private function getDateRange($period, $fromDate = null, $toDate = null) {
        if ($period === 'custom' && $fromDate && $toDate) {
            return [
                'start' => $fromDate,
                'end' => $toDate
            ];
        }
        
        $today = date('Y-m-d');
        
        switch ($period) {
            case 'today':
                return [
                    'start' => $today,
                    'end' => $today
                ];
            case 'week':
                $start = date('Y-m-d', strtotime('monday this week'));
                return [
                    'start' => $start,
                    'end' => $today
                ];
            case 'month':
                $start = date('Y-m-01');
                return [
                    'start' => $start,
                    'end' => $today
                ];
            case 'quarter':
                $month = date('n');
                $quarter = ceil($month / 3);
                $start = date('Y-m-d', mktime(0, 0, 0, ($quarter - 1) * 3 + 1, 1));
                return [
                    'start' => $start,
                    'end' => $today
                ];
            case 'year':
                $start = date('Y-01-01');
                return [
                    'start' => $start,
                    'end' => $today
                ];
            default:
                // Default to month
                $start = date('Y-m-01');
                return [
                    'start' => $start,
                    'end' => $today
                ];
        }
    }
    
    /**
     * Get previous period range for comparison
     */
    private function getPreviousPeriodRange($period, $fromDate = null, $toDate = null) {
        if ($period === 'custom' && $fromDate && $toDate) {
            $days = (strtotime($toDate) - strtotime($fromDate)) / 86400;
            $prevEnd = date('Y-m-d', strtotime($fromDate . ' -1 day'));
            $prevStart = date('Y-m-d', strtotime($prevEnd . " -{$days} days"));
            return [
                'start' => $prevStart,
                'end' => $prevEnd
            ];
        }
        
        switch ($period) {
            case 'today':
                $yesterday = date('Y-m-d', strtotime('-1 day'));
                return ['start' => $yesterday, 'end' => $yesterday];
            case 'week':
                $lastWeekStart = date('Y-m-d', strtotime('monday last week'));
                $lastWeekEnd = date('Y-m-d', strtotime('sunday last week'));
                return ['start' => $lastWeekStart, 'end' => $lastWeekEnd];
            case 'month':
                $lastMonthStart = date('Y-m-01', strtotime('first day of last month'));
                $lastMonthEnd = date('Y-m-t', strtotime('last day of last month'));
                return ['start' => $lastMonthStart, 'end' => $lastMonthEnd];
            case 'quarter':
                $month = date('n');
                $quarter = ceil($month / 3);
                if ($quarter == 1) {
                    $prevQuarter = 4;
                    $year = date('Y') - 1;
                } else {
                    $prevQuarter = $quarter - 1;
                    $year = date('Y');
                }
                $prevStart = date('Y-m-d', mktime(0, 0, 0, ($prevQuarter - 1) * 3 + 1, 1, $year));
                $prevEnd = date('Y-m-t', mktime(0, 0, 0, $prevQuarter * 3, 1, $year));
                return ['start' => $prevStart, 'end' => $prevEnd];
            case 'year':
                $lastYearStart = date('Y-01-01', strtotime('-1 year'));
                $lastYearEnd = date('Y-12-31', strtotime('-1 year'));
                return ['start' => $lastYearStart, 'end' => $lastYearEnd];
            default:
                $lastMonthStart = date('Y-m-01', strtotime('first day of last month'));
                $lastMonthEnd = date('Y-m-t', strtotime('last day of last month'));
                return ['start' => $lastMonthStart, 'end' => $lastMonthEnd];
        }
    }
    
    /**
     * Get cashier-specific stats
     */
    public function cashierStats() {
        try {
            $this->checkAuthentication();
            $this->checkRoles(['cashier','manager','admin']);
            
            $userId = $this->user['id'];
            $today = date('Y-m-d');
            
            $sql = "SELECT 
                COUNT(*) as my_transactions,
                COALESCE(SUM(total_amount), 0) as my_sales_today,
                COALESCE(AVG(total_amount), 0) as my_avg_transaction
            FROM transactions
            WHERE user_id = ? AND DATE(created_at) = ? AND status = 'completed'";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId, $today]);
            $stats = $stmt->fetch();
            
            // Get shift start (last login today)
            $shiftStartSql = "SELECT last_login FROM users WHERE id = ?";
            $shiftStmt = $this->pdo->prepare($shiftStartSql);
            $shiftStmt->execute([$userId]);
            $shiftStart = $shiftStmt->fetch()['last_login'];
            
            $data = [
                'my_sales_today' => (float)$stats['my_sales_today'],
                'my_transactions' => (int)$stats['my_transactions'],
                'my_avg_transaction' => (float)$stats['my_avg_transaction'],
                'shift_start' => $shiftStart
            ];
            
            $this->sendSuccess($data);
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 500);
        }
    }
    
    /**
     * Get manager-specific stats
     */
    public function managerStats() {
        try {
            $this->checkAuthentication();
            
            $today = date('Y-m-d');
            $firstDayOfMonth = date('Y-m-01');
            $lastMonth = date('Y-m-01', strtotime('-1 month'));
            $lastDayOfLastMonth = date('Y-m-t', strtotime('-1 month'));
            
            // Today's revenue
            $sql = "SELECT COALESCE(SUM(total_amount), 0) as revenue
                FROM transactions
                WHERE DATE(created_at) = ? AND status = 'completed'";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$today]);
            $todayResult = $stmt->fetch();
            $todayRevenue = ($todayResult && isset($todayResult['revenue'])) ? (float)$todayResult['revenue'] : 0;
            
            // Total transactions today
            $sql = "SELECT COUNT(*) as count, COUNT(DISTINCT customer_id) as customers
                FROM transactions
                WHERE DATE(created_at) = ? AND status = 'completed'";
            $stmt->execute([$today]);
            $todayTxn = $stmt->fetch();
            
            // Safe access with defaults
            $totalTransactions = ($todayTxn && isset($todayTxn['count'])) ? (int)$todayTxn['count'] : 0;
            $customersToday = ($todayTxn && isset($todayTxn['customers'])) ? (int)$todayTxn['customers'] : 0;
            
            // Total products
            $totalProducts = $this->productModel->count(['is_active' => 1]);
            
            // Low stock count
            $lowStockSql = "SELECT COUNT(*) as count 
                FROM products 
                WHERE stock_quantity < min_stock_level AND is_active = 1";
            $lowStockResult = $this->pdo->query($lowStockSql)->fetch();
            $lowStockCount = ($lowStockResult && isset($lowStockResult['count'])) ? (int)$lowStockResult['count'] : 0;
            
            // Monthly revenue
            $sql = "SELECT COALESCE(SUM(total_amount), 0) as revenue
                FROM transactions
                WHERE created_at >= ? AND status = 'completed'";
            $stmt->execute([$firstDayOfMonth]);
            $monthlyResult = $stmt->fetch();
            $monthlyRevenue = ($monthlyResult && isset($monthlyResult['revenue'])) ? (float)$monthlyResult['revenue'] : 0;
            
            // Last month revenue for comparison
            $stmt->execute([$lastMonth]);
            $lastMonthResult = $stmt->fetch();
            $lastMonthRevenue = ($lastMonthResult && isset($lastMonthResult['revenue'])) ? (float)$lastMonthResult['revenue'] : 0;
            
            // Monthly growth
            $monthlyGrowth = 0;
            if ($lastMonthRevenue > 0) {
                $monthlyGrowth = (($monthlyRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100;
            }
            
            $data = [
                'today_revenue' => (float)$todayRevenue,
                'daily_target' => 5000000, // Could be from settings
                'total_transactions' => $totalTransactions,
                'customers_today' => $customersToday,
                'total_products' => $totalProducts,
                'low_stock_count' => $lowStockCount,
                'monthly_revenue' => (float)$monthlyRevenue,
                'monthly_growth' => round($monthlyGrowth, 2)
            ];
            
            $this->sendSuccess($data);
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 500);
        }
    }
    
    /**
     * Get stock statistics for staff
     */
    public function stockStats() {
        try {
            $this->checkAuthentication();
            
            // Total products
            $totalProducts = $this->productModel->count();
            $activeProducts = $this->productModel->count(['is_active' => 1]);
            
            // Out of stock
            $outOfStockSql = "SELECT COUNT(*) as count 
                FROM products 
                WHERE stock_quantity = 0 AND is_active = 1";
            $outOfStock = $this->pdo->query($outOfStockSql)->fetch()['count'];
            
            // Low stock
            $lowStockSql = "SELECT COUNT(*) as count 
                FROM products 
                WHERE stock_quantity > 0 AND stock_quantity < min_stock_level AND is_active = 1";
            $lowStock = $this->pdo->query($lowStockSql)->fetch()['count'];
            
            // Stock value
            $stockValueSql = "SELECT 
                COALESCE(SUM(stock_quantity * price), 0) as stock_value,
                COALESCE(SUM(stock_quantity), 0) as total_items
                FROM products 
                WHERE is_active = 1";
            $stockValue = $this->pdo->query($stockValueSql)->fetch();
            
            $data = [
                'total_products' => $totalProducts,
                'active_products' => $activeProducts,
                'out_of_stock' => $outOfStock,
                'low_stock' => $lowStock,
                'stock_value' => (float)$stockValue['stock_value'],
                'total_stock_items' => (int)$stockValue['total_items']
            ];
            
            $this->sendSuccess($data);
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 500);
        }
    }
    
    /**
     * Get sales chart data
     */
    public function salesChart() {
        try {
            $this->checkAuthentication();
            
            $days = $_GET['days'] ?? 30;
            
            $sql = "SELECT 
                DATE(created_at) as date,
                COUNT(*) as transactions,
                COALESCE(SUM(total_amount), 0) as sales
            FROM transactions
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            AND status = 'completed'
            GROUP BY DATE(created_at)
            ORDER BY date ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$days]);
            $chartData = $stmt->fetchAll();
            
            $data = [
                'labels' => array_map(function($row) {
                    return date('d M', strtotime($row['date']));
                }, $chartData),
                'sales' => array_map(function($row) {
                    return (float)$row['sales'];
                }, $chartData),
                'transactions' => array_map(function($row) {
                    return (int)$row['transactions'];
                }, $chartData)
            ];
            
            $this->sendSuccess($data);
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 500);
        }
    }
    
    /**
     * Get payment methods breakdown
     */
    public function paymentMethods() {
        try {
            $this->checkAuthentication();
            $this->checkPermission('reports.view');

            $period = $_GET['period'] ?? 'today';
            $from = $_GET['from'] ?? null;
            $to = $_GET['to'] ?? null;

            $where = "status = 'completed' AND payment_method IN ('cash','qris','transfer')";
            $params = [];

            if ($period === 'custom' && $from && $to) {
                $where .= " AND DATE(created_at) BETWEEN ? AND ?";
                $params[] = $from;
                $params[] = $to;
            } elseif (is_numeric($period)) {
                $where .= " AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)";
                $params[] = (int)$period;
            } elseif ($period === 'month') {
                $where .= " AND DATE(created_at) >= DATE_FORMAT(CURDATE(), '%Y-%m-01')";
            } elseif ($period === 'year') {
                $where .= " AND YEAR(created_at) = YEAR(CURDATE())";
            } else { // today
                $where .= " AND DATE(created_at) = CURDATE()";
            }

            $sql = "SELECT 
                payment_method,
                COUNT(*) as count,
                COALESCE(SUM(total_amount), 0) as total
            FROM transactions
            WHERE $where
            GROUP BY payment_method";

            if (!empty($params)) {
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
            } else {
                $stmt = $this->pdo->query($sql);
            }

            $methods = $stmt->fetchAll();

            // Normalize labels to Title Case without Card
            $allowed = ['cash' => 'Cash', 'qris' => 'Qris', 'transfer' => 'Transfer'];
            $labels = [];
            $values = [];
            $counts = [];
            foreach ($methods as $row) {
                $key = strtolower($row['payment_method']);
                if (isset($allowed[$key])) {
                    $labels[] = $allowed[$key];
                    $values[] = (float)$row['total'];
                    $counts[] = (int)$row['count'];
                }
            }

            $this->sendSuccess([
                'labels' => $labels,
                'values' => $values,
                'counts' => $counts
            ]);

        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 500);
        }
    }
    
    /**
     * Get top selling products
     */
    public function topProducts() {
        try {
            $this->checkAuthentication();
            
            $limit = $_GET['limit'] ?? 5;
            
            $sql = "SELECT 
                p.id,
                p.name,
                p.sku,
                c.name as category_name,
                COUNT(ti.id) as times_sold,
                SUM(ti.quantity) as total_quantity,
                SUM(ti.total_price) as total_revenue
            FROM products p
            INNER JOIN transaction_items ti ON p.id = ti.product_id
            INNER JOIN transactions t ON ti.transaction_id = t.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE t.status = 'completed'
            AND DATE(t.created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY p.id
            ORDER BY total_quantity DESC
            LIMIT ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$limit]);
            $products = $stmt->fetchAll();
            
            $this->sendSuccess(['products' => $products]);
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 500);
        }
    }
    
    /**
     * Get recent activity (audit logs)
     */
    public function recentActivity() {
        try {
            $this->checkAuthentication();
            
            $limit = $_GET['limit'] ?? 10;
            
            $sql = "SELECT 
                al.*,
                u.full_name as user_name
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            ORDER BY al.created_at DESC
            LIMIT ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$limit]);
            $activities = $stmt->fetchAll();
            
            // Format activity messages
            foreach ($activities as &$activity) {
                $activity['message'] = $this->formatActivityMessage($activity);
                $activity['time_ago'] = $this->timeAgo($activity['created_at']);
                $activity['type'] = $this->getActivityType($activity['action']);
            }
            
            $this->sendSuccess(['activities' => $activities]);
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 500);
        }
    }
    
    /**
     * Get team performance (for manager)
     */
    public function teamPerformance() {
        try {
            $this->checkAuthentication();
            
            $today = date('Y-m-d');
            
            $sql = "SELECT 
                u.id,
                u.full_name,
                u.role,
                COUNT(t.id) as transactions_count,
                COALESCE(SUM(t.total_amount), 0) as total_sales,
                COALESCE(AVG(t.total_amount), 0) as avg_transaction
            FROM users u
            LEFT JOIN transactions t ON u.id = t.user_id 
                AND DATE(t.created_at) = ? 
                AND t.status = 'completed'
            WHERE u.role IN ('cashier', 'staff', 'manager')
            AND u.is_active = 1
            GROUP BY u.id
            ORDER BY total_sales DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$today]);
            $cashiers = $stmt->fetchAll();
            
            // Calculate performance percentage (relative to best performer)
            $maxSales = $cashiers[0]['total_sales'] ?? 0;
            foreach ($cashiers as &$cashier) {
                $cashier['performance_percent'] = $maxSales > 0 
                    ? round(($cashier['total_sales'] / $maxSales) * 100, 1)
                    : 0;
            }
            
            $this->sendSuccess(['cashiers' => $cashiers]);
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 500);
        }
    }
    
    /**
     * Get customer insights
     */
    public function customerInsights() {
        try {
            $this->checkAuthentication();
            
            $firstDayOfMonth = date('Y-m-01');
            
            // New customers this month
            $newCustomersSql = "SELECT COUNT(*) as count FROM customers 
                WHERE created_at >= ? AND is_active = 1";
            $newCustomersStmt = $this->pdo->prepare($newCustomersSql);
            $newCustomersStmt->execute([$firstDayOfMonth]);
            $newCustomers = $newCustomersStmt->fetch()['count'];
            
            // Repeat customers rate
            $sql = "SELECT 
                COUNT(DISTINCT customer_id) as total_with_purchases,
                COUNT(DISTINCT CASE WHEN purchase_count > 1 THEN customer_id END) as repeat_customers
            FROM (
                SELECT customer_id, COUNT(*) as purchase_count
                FROM transactions
                WHERE customer_id IS NOT NULL
                AND status = 'completed'
                AND created_at >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
                GROUP BY customer_id
            ) as customer_purchases";
            
            $stmt = $this->pdo->query($sql);
            $repeatData = $stmt->fetch();
            
            $repeatRate = 0;
            if ($repeatData['total_with_purchases'] > 0) {
                $repeatRate = ($repeatData['repeat_customers'] / $repeatData['total_with_purchases']) * 100;
            }
            
            // VIP customers
            $vipCount = $this->customerModel->count([
                'customer_type' => 'vip',
                'is_active' => 1
            ]);
            
            $data = [
                'new_customers' => $newCustomers,
                'repeat_rate' => round($repeatRate, 1),
                'vip_count' => $vipCount
            ];
            
            $this->sendSuccess($data);
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 500);
        }
    }
    
    /**
     * Get category stock overview
     */
    public function categoryStock() {
        try {
            $this->checkAuthentication();
            
            $sql = "SELECT 
                c.id,
                c.name,
                c.color,
                c.icon,
                COUNT(p.id) as product_count,
                COALESCE(SUM(p.stock_quantity), 0) as total_stock,
                COALESCE(SUM(p.stock_quantity * p.price), 0) as stock_value,
                COUNT(CASE WHEN p.stock_quantity <= p.min_stock_level THEN 1 END) as low_stock_count
            FROM categories c
            LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1
            WHERE c.is_active = 1
            GROUP BY c.id
            ORDER BY stock_value DESC";
            
            $stmt = $this->pdo->query($sql);
            $categories = $stmt->fetchAll();
            
            $this->sendSuccess(['categories' => $categories]);
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 500);
        }
    }
    
    // Helper methods
    
    private function formatActivityMessage($activity) {
        $user = $activity['user_name'] ?? 'System';
        $action = $activity['action'];
        $table = $activity['table_name'];
        
        $actions = [
            'login' => "{$user} logged in",
            'logout' => "{$user} logged out",
            'create' => "{$user} created a new {$table}",
            'update' => "{$user} updated a {$table}",
            'delete' => "{$user} deleted a {$table}",
            'transaction' => "{$user} completed a transaction",
        ];
        
        return $actions[$action] ?? "{$user} performed {$action} on {$table}";
    }
    
    private function getActivityType($action) {
        $types = [
            'login' => 'info',
            'logout' => 'info',
            'create' => 'success',
            'update' => 'warning',
            'delete' => 'error',
            'transaction' => 'success',
        ];
        
        return $types[$action] ?? 'info';
    }
    
    private function timeAgo($datetime) {
        $timestamp = strtotime($datetime);
        $difference = time() - $timestamp;
        
        if ($difference < 60) {
            return 'Just now';
        } elseif ($difference < 3600) {
            $minutes = floor($difference / 60);
            return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
        } elseif ($difference < 86400) {
            $hours = floor($difference / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } else {
            $days = floor($difference / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        }
    }
}
?>

