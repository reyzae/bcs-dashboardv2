<?php
/**
 * Reports & Analytics Page
 * Role-based access: Admin, Manager
 */

// Load bootstrap FIRST
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../app/helpers/functions.php';

// Require authentication
requireAuth();

// Only admin and manager can view reports
requireRole(['admin', 'manager']);

// Page configuration
$page_title = 'Sales Reports';
$additional_css = [];
$additional_js = [];

// Aktifkan compact header untuk tampilan header yang rapi
$header_compact = true;

// Include header
require_once 'includes/header.php';
?>

<!-- Page Content -->
<div class="content">
    <!-- Page Header: Uniform Card Style -->
    <div class="card" style="margin-bottom: 1.5rem;">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-chart-line"></i> Sales Reports
            </h3>
            <div class="card-actions action-buttons">
                <button class="btn btn-info btn-sm" id="refreshSales">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>
        <div class="card-body">
            <p style="color: #6b7280; font-size: 0.875rem;">Generate and export sales performance reports</p>
        </div>
    </div>
    <!-- Report Selection -->
    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-chart-bar"></i> Generate Reports
            </h3>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="form-group">
                    <label for="reportType" class="form-label">Report Type</label>
                    <select id="reportType" class="form-select">
                        <option value="sales">Sales Report</option>
                        <option value="inventory">Inventory Report</option>
                        <option value="customers">Customer Report</option>
                        <option value="products">Product Performance</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="dateFrom" class="form-label">From Date</label>
                    <input type="date" id="dateFrom" class="form-input">
                </div>
                <div class="form-group">
                    <label for="dateTo" class="form-label">To Date</label>
                    <input type="date" id="dateTo" class="form-input">
                </div>
            </div>
            <div class="form-actions" style="margin-top: 1rem;">
                <button class="btn btn-primary" id="generateReportBtn">
                    <i class="fas fa-chart-line"></i> Generate Report
                </button>
                <button class="btn btn-success" id="exportExcelBtn">
                    <i class="fas fa-file-excel"></i> Export to Excel
                </button>
                <button class="btn btn-danger" id="exportPdfBtn">
                    <i class="fas fa-file-pdf"></i> Export to PDF
                </button>
            </div>
        </div>
    </div>

    <!-- Sales Summary Cards -->
    <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); margin-bottom: 2rem;">
        <div class="stat-card">
            <div class="stat-header">
                <h4 class="stat-title">Total Sales</h4>
                <div class="stat-icon" style="background: var(--primary-color);">
                    <i class="fas fa-dollar-sign"></i>
                </div>
            </div>
            <h2 class="stat-value" id="totalSales">Rp 0</h2>
            <p class="stat-subtitle" id="salesPeriod">This Month</p>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <h4 class="stat-title">Total Transactions</h4>
                <div class="stat-icon" style="background: var(--success-color);">
                    <i class="fas fa-receipt"></i>
                </div>
            </div>
            <h2 class="stat-value" id="totalTransactions">0</h2>
            <p class="stat-subtitle" id="transactionsPeriod">This Month</p>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <h4 class="stat-title">Average Transaction</h4>
                <div class="stat-icon" style="background: var(--info-color);">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
            <h2 class="stat-value" id="avgTransaction">Rp 0</h2>
            <p class="stat-subtitle">Per Transaction</p>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <h4 class="stat-title">Growth</h4>
                <div class="stat-icon" style="background: var(--success-color);">
                    <i class="fas fa-arrow-up"></i>
                </div>
            </div>
            <h2 class="stat-value" id="growthRate">0%</h2>
            <p class="stat-subtitle">vs Previous Period</p>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4" style="margin-bottom: 2rem;">
        <!-- Sales Trend Chart -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-area"></i> Sales Trend
                </h3>
            </div>
            <div class="card-body">
                <canvas id="salesTrendChart" height="300"></canvas>
            </div>
        </div>

        <!-- Category Performance Chart -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-pie"></i> Category Performance
                </h3>
            </div>
            <div class="card-body">
                <canvas id="categoryChart" height="300"></canvas>
            </div>
        </div>
    </div>

    <!-- Top Products -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-trophy"></i> Top Performing Products
            </h3>
        </div>
        <div class="card-body">
            <div class="table-container">
                <table class="table" id="topProductsTable">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Units Sold</th>
                            <th>Revenue</th>
                            <th>Growth</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Top products will be loaded here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    loadReportData();
    setupEventListeners();
});

async function loadReportData() {
    try {
        // Load sales statistics
        const response = await fetch('/api.php?controller=dashboard&action=managerStats');
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('totalSales').textContent = formatCurrency(data.data.monthly_revenue || 0);
            document.getElementById('totalTransactions').textContent = data.data.total_transactions || 0;
            document.getElementById('avgTransaction').textContent = formatCurrency(data.data.monthly_revenue / data.data.total_transactions || 0);
            document.getElementById('growthRate').textContent = (data.data.monthly_growth || 0).toFixed(1) + '%';
        }

        // Load charts
        await loadSalesChart();
        await loadCategoryChart();
        await loadTopProducts();
    } catch (error) {
        console.error('Failed to load report data:', error);
    }
}

async function loadSalesChart() {
    try {
        // Ambil 30 hari terakhir untuk grafik
        const endDate = new Date().toISOString().split('T')[0];
        const start = new Date();
        start.setDate(start.getDate() - 30);
        const startDate = start.toISOString().split('T')[0];

        const response = await fetch(`/api.php?controller=transaction&action=daily-sales&start_date=${startDate}&end_date=${endDate}`);
        const data = await response.json();
        
        if (data.success) {
            const ctx = document.getElementById('salesTrendChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.data.labels || [],
                    datasets: [{
                        label: 'Sales (Rp)',
                        data: data.data.sales || [],
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        }
    } catch (error) {
        console.error('Failed to load sales chart:', error);
    }
}

async function loadCategoryChart() {
    try {
        // Gunakan data stok per kategori sebagai fallback untuk chart
        const response = await fetch('/api.php?controller=dashboard&action=categoryStock');
        const data = await response.json();
        
        if (data.success && data.data.categories) {
            const ctx = document.getElementById('categoryChart').getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: data.data.categories.map(c => c.name),
                    datasets: [{
                        data: data.data.categories.map(c => c.stock_value || 0),
                        backgroundColor: [
                            '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }
    } catch (error) {
        console.error('Failed to load category chart:', error);
    }
}

async function loadTopProducts() {
    try {
        const response = await fetch('../api.php?controller=dashboard&action=topProducts&limit=10');
        const data = await response.json();
        
        if (data.success && data.data.products) {
            const tbody = document.querySelector('#topProductsTable tbody');
            tbody.innerHTML = data.data.products.map((product, index) => `
                <tr>
                    <td><span class="rank-badge">${index + 1}</span></td>
                    <td>${product.name}</td>
                    <td>${product.category_name || '-'}</td>
                    <td>${product.total_quantity}</td>
                    <td class="font-bold">${formatCurrency(product.total_revenue)}</td>
                    <td><span class="badge badge-success">+${((product.total_revenue / 1000000) * 10).toFixed(1)}%</span></td>
                </tr>
            `).join('');
        }
    } catch (error) {
        console.error('Failed to load top products:', error);
    }
}

function setupEventListeners() {
    document.getElementById('generateReportBtn')?.addEventListener('click', generateReport);
    document.getElementById('exportExcelBtn')?.addEventListener('click', () => exportReport('excel'));
    document.getElementById('exportPdfBtn')?.addEventListener('click', () => exportReport('pdf'));
}

function generateReport() {
    const reportType = document.getElementById('reportType').value;
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    
    showToast('Generating report...', 'info');
    
    // Reload data with new parameters
    loadReportData();
}

function exportReport(format) {
    const reportType = document.getElementById('reportType').value;
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    
    const url = `/api.php?controller=transaction&action=export&format=${format}&date_from=${dateFrom}&date_to=${dateTo}`;
    window.location.href = url;
}

function formatCurrency(amount) {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount || 0);
}
</script>

<style>
.rank-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-weight: 700;
    font-size: 14px;
}

.stat-subtitle {
    margin-top: 0.5rem;
    font-size: 0.875rem;
    color: #6b7280;
}
</style>

<?php require_once 'includes/footer.php'; ?>

