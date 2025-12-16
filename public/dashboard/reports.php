<?php
/**
 * Reports & Analytics Page - FULLY UPGRADED
 * Role-based access: Admin, Manager
 * 
 * Features:
 * - Modern page header
 * - 4 Stats cards with trend indicators + animated counter
 * - Modern filter chips horizontal layout
 * - Enhanced charts with loading states
 * - Keyboard shortcuts
 * - Export dropdown menu
 * - Mobile responsive
 * - Empty states
 */

// Load bootstrap FIRST
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../app/helpers/functions.php';

// Require authentication
requireAuth();

// Only admin and manager can view reports
requireRole(['admin', 'manager']);

// Page configuration
$page_title = 'Reports & Analytics';
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
                <i class="fas fa-chart-bar"></i> Reports & Analytics
            </h3>
            <div class="card-actions action-buttons">
                <button class="btn btn-info btn-sm" id="refreshReports">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>
        <div class="card-body">
            <p style="color: #6b7280; font-size: 0.875rem;">Generate comprehensive business reports and insights</p>
        </div>
    </div>

    <!-- Simplified Report Options -->
    <div class="card" style="margin-bottom: 1.5rem;">
        <div class="card-body" style="padding: 1.25rem;">
            <!-- Quick Period Filters -->
            <div style="margin-bottom: 1rem;">
                <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                    <span style="font-size: 13px; font-weight: 600; color: #6b7280; margin-right: 4px;">📅
                        Period:</span>
                    <button class="preset-range-btn" onclick="selectPresetRange('today')">Today</button>
                    <button class="preset-range-btn" onclick="selectPresetRange('week')">This Week</button>
                    <button class="preset-range-btn active" onclick="selectPresetRange('month')">This Month</button>
                    <button class="preset-range-btn" onclick="selectPresetRange('quarter')">This Quarter</button>
                    <button class="preset-range-btn" onclick="selectPresetRange('year')">This Year</button>
                    <button class="preset-range-btn" onclick="toggleCustomDateInputs()" id="customRangeBtn">
                        <i class="fas fa-calendar-alt"></i> Custom
                    </button>
                </div>
            </div>

            <!-- Custom Date Range (Hidden by default) -->
            <div id="customDateInputsRow"
                style="display: none; margin-bottom: 1rem; padding: 12px; background: #f9fafb; border-radius: 8px;">
                <div style="display: grid; grid-template-columns: 1fr auto 1fr auto; gap: 8px; align-items: center;">
                    <input type="date" id="customDateFrom" class="form-control" style="font-size: 13px;">
                    <span style="color: #9ca3af;">to</span>
                    <input type="date" id="customDateTo" class="form-control" style="font-size: 13px;">
                    <button class="btn btn-primary btn-sm" onclick="applyCustomDateRange()">
                        <i class="fas fa-check"></i> Apply
                    </button>
                </div>
            </div>

            <!-- Report Type & Actions -->
            <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
                <select id="reportType"
                    style="padding: 8px 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; flex: 1; min-width: 200px;">
                    <option value="sales">📊 Sales Report</option>
                    <option value="inventory">📦 Inventory Report</option>
                    <option value="customers">👥 Customer Report</option>
                    <option value="products">🏆 Product Performance</option>
                </select>

                <button class="btn btn-primary btn-generate" id="generateReportBtn" onclick="generateReport()">
                    <i class="fas fa-chart-line"></i> Generate
                </button>

                <button class="btn btn-success" onclick="toggleExportMenuEnhanced()">
                    <i class="fas fa-download"></i> Export
                    <i class="fas fa-chevron-down" style="margin-left: 4px; font-size: 11px;"></i>
                </button>

                <!-- Export Menu -->
                <div class="export-menu-enhanced" id="exportMenuEnhanced" style="right: 0;">
                    <button class="export-item" onclick="exportReport('pdf')">
                        <i class="fas fa-file-pdf"></i> <span>PDF Report</span>
                    </button>
                    <button class="export-item" onclick="exportReport('excel')">
                        <i class="fas fa-file-excel"></i> <span>Excel</span>
                    </button>
                    <button class="export-item" onclick="exportReport('csv')">
                        <i class="fas fa-file-csv"></i> <span>CSV</span>
                    </button>
                    <hr style="margin: 4px 0; border: none; border-top: 1px solid #f3f4f6;">
                    <button class="export-item" onclick="exportChartImage('png')">
                        <i class="fas fa-image"></i> <span>PNG Chart</span>
                    </button>
                    <button class="export-item" onclick="printReport()">
                        <i class="fas fa-print"></i> <span>Print</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Progress Bar -->
    <div class="report-progress" id="reportProgress">
        <div class="report-progress-bar" id="reportProgressBar"></div>
    </div>

    <!-- Custom Date Range (Hidden by default) -->
    <div id="customDateRange"
        style="margin-bottom: 1.5rem; padding: 1rem; background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: none;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div>
                <label
                    style="font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem; display: block;">From
                    Date</label>
                <input type="date" id="dateFrom" class="form-control" style="width: 100%;">
            </div>
            <div>
                <label
                    style="font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem; display: block;">To
                    Date</label>
                <input type="date" id="dateTo" class="form-control" style="width: 100%;">
            </div>
        </div>
        <button class="btn btn-primary" onclick="applyCustomDateRange()" style="margin-top: 1rem;">
            <i class="fas fa-check"></i> Apply Date Range
        </button>
    </div>

    <!-- Report Content Container (Dynamic based on reportType) -->
    <div id="reportContent">
        <!-- Sales Report Content (Default) -->
        <div id="salesReportContent">

            <!-- Stats Cards with Trends & Animated Counters -->
            <div class="stats-grid"
                style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <div class="stat-card"
                    style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-left-color: #4c51bf;">
                    <div>
                        <h4 style="font-size: 0.875rem; font-weight: 500; opacity: 0.9; margin-bottom: 0.5rem;">Total
                            Sales</h4>
                        <h2 class="stat-value" id="totalSales" style="font-size: 2rem; font-weight: 700; color: white;">
                            Rp 0</h2>
                        <p class="stat-subtitle" id="salesPeriod"
                            style="color: rgba(255,255,255,0.8); margin-top: 0.5rem; font-size: 0.875rem;">This Month
                        </p>
                    </div>
                    <div class="stat-icon" style="background: rgba(255,255,255,0.2);">
                        <i class="fas fa-coins"></i>
                    </div>
                </div>

                <div class="stat-card"
                    style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border-left-color: #047857;">
                    <div>
                        <h4 style="font-size: 0.875rem; font-weight: 500; opacity: 0.9; margin-bottom: 0.5rem;">Total
                            Transactions</h4>
                        <h2 class="stat-value" id="totalTransactions"
                            style="font-size: 2rem; font-weight: 700; color: white;">0</h2>
                        <p class="stat-subtitle" id="transactionsPeriod"
                            style="color: rgba(255,255,255,0.8); margin-top: 0.5rem; font-size: 0.875rem;">This Month
                        </p>
                    </div>
                    <div class="stat-icon" style="background: rgba(255,255,255,0.2);">
                        <i class="fas fa-receipt"></i>
                    </div>
                </div>

                <div class="stat-card"
                    style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; border-left-color: #1e40af;">
                    <div>
                        <h4 style="font-size: 0.875rem; font-weight: 500; opacity: 0.9; margin-bottom: 0.5rem;">Average
                            Transaction</h4>
                        <h2 class="stat-value" id="avgTransaction"
                            style="font-size: 2rem; font-weight: 700; color: white;">Rp 0</h2>
                        <p class="stat-subtitle"
                            style="color: rgba(255,255,255,0.8); margin-top: 0.5rem; font-size: 0.875rem;">Per
                            Transaction</p>
                    </div>
                    <div class="stat-icon" style="background: rgba(255,255,255,0.2);">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>

                <div class="stat-card"
                    style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; border-left-color: #b45309;">
                    <div>
                        <h4 style="font-size: 0.875rem; font-weight: 500; opacity: 0.9; margin-bottom: 0.5rem;">Growth
                            Rate</h4>
                        <h2 class="stat-value" id="growthRate" style="font-size: 2rem; font-weight: 700; color: white;">
                            0%</h2>
                        <p class="stat-subtitle"
                            style="color: rgba(255,255,255,0.8); margin-top: 0.5rem; font-size: 0.875rem;">vs Previous
                            Period</p>
                    </div>
                    <div class="stat-icon" style="background: rgba(255,255,255,0.2);">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div
                style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <!-- Sales Trend Chart -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-area"></i> Sales Trend
                        </h3>
                        <button class="btn btn-sm btn-secondary" onclick="loadSalesChart()" title="Refresh Chart">
                            <i class="fas fa-sync"></i>
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="salesChartLoading" style="text-align: center; padding: 3rem;">
                            <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #667eea;"></i>
                            <p style="margin-top: 1rem; color: #6b7280;">Loading chart...</p>
                        </div>
                        <canvas id="salesTrendChart" style="max-height: 300px; display: none;"></canvas>
                    </div>
                </div>

                <!-- Category Performance Chart -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-pie"></i> Category Performance
                        </h3>
                        <button class="btn btn-sm btn-secondary" onclick="loadCategoryChart()" title="Refresh Chart">
                            <i class="fas fa-sync"></i>
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="categoryChartLoading" style="text-align: center; padding: 3rem;">
                            <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #667eea;"></i>
                            <p style="margin-top: 1rem; color: #6b7280;">Loading chart...</p>
                        </div>
                        <canvas id="categoryChart" style="max-height: 300px; display: none;"></canvas>
                    </div>
                </div>
            </div>

            <!-- Top Products -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-trophy"></i> Top Performing Products
                    </h3>
                    <button class="btn btn-sm btn-secondary" onclick="loadTopProducts()" title="Refresh Data">
                        <i class="fas fa-sync"></i>
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table class="table" id="topProductsTable">
                            <thead>
                                <tr>
                                    <th style="width: 60px; text-align: center;">Rank</th>
                                    <th>Product</th>
                                    <th style="width: 150px;">Category</th>
                                    <th style="width: 120px; text-align: center;">Units Sold</th>
                                    <th style="width: 150px; text-align: right;">Revenue</th>
                                    <th style="width: 100px; text-align: center;">Growth</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr id="loadingRow">
                                    <td colspan="6" style="text-align: center; padding: 3rem;">
                                        <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #667eea;"></i>
                                        <p style="margin-top: 1rem; color: #6b7280;">Loading top products...</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Sales Report Content -->

        <!-- Inventory Report Content -->
        <div id="inventoryReportContent" style="display: none;">
            <!-- Inventory Stats Cards -->
            <div class="stats-grid"
                style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <div class="stat-card"
                    style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <div>
                        <h4 style="font-size: 0.875rem; font-weight: 500; opacity: 0.9; margin-bottom: 0.5rem;">Total
                            Products</h4>
                        <h2 class="stat-value" id="totalProducts"
                            style="font-size: 2rem; font-weight: 700; color: white;">0</h2>
                        <p class="stat-subtitle"
                            style="color: rgba(255,255,255,0.8); margin-top: 0.5rem; font-size: 0.875rem;">Active
                            Products</p>
                    </div>
                    <div class="stat-icon" style="background: rgba(255,255,255,0.2);">
                        <i class="fas fa-boxes"></i>
                    </div>
                </div>

                <div class="stat-card"
                    style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white;">
                    <div>
                        <h4 style="font-size: 0.875rem; font-weight: 500; opacity: 0.9; margin-bottom: 0.5rem;">Out of
                            Stock</h4>
                        <h2 class="stat-value" id="outOfStock" style="font-size: 2rem; font-weight: 700; color: white;">
                            0</h2>
                        <p class="stat-subtitle"
                            style="color: rgba(255,255,255,0.8); margin-top: 0.5rem; font-size: 0.875rem;">Products</p>
                    </div>
                    <div class="stat-icon" style="background: rgba(255,255,255,0.2);">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>

                <div class="stat-card"
                    style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white;">
                    <div>
                        <h4 style="font-size: 0.875rem; font-weight: 500; opacity: 0.9; margin-bottom: 0.5rem;">Low
                            Stock</h4>
                        <h2 class="stat-value" id="lowStockProducts"
                            style="font-size: 2rem; font-weight: 700; color: white;">0</h2>
                        <p class="stat-subtitle"
                            style="color: rgba(255,255,255,0.8); margin-top: 0.5rem; font-size: 0.875rem;">Below
                            Threshold</p>
                    </div>
                    <div class="stat-icon" style="background: rgba(255,255,255,0.2);">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                </div>

                <div class="stat-card"
                    style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;">
                    <div>
                        <h4 style="font-size: 0.875rem; font-weight: 500; opacity: 0.9; margin-bottom: 0.5rem;">Stock
                            Value</h4>
                        <h2 class="stat-value" id="stockValue" style="font-size: 2rem; font-weight: 700; color: white;">
                            Rp 0</h2>
                        <p class="stat-subtitle"
                            style="color: rgba(255,255,255,0.8); margin-top: 0.5rem; font-size: 0.875rem;">Total
                            Inventory</p>
                    </div>
                    <div class="stat-icon" style="background: rgba(255,255,255,0.2);">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
            </div>

            <!-- Low Stock Alert Table -->
            <div class="card" style="margin-bottom: 2rem;">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-exclamation-triangle text-warning"></i> Low Stock Products
                    </h3>
                    <button class="btn btn-sm btn-secondary" onclick="loadInventoryReport()" title="Refresh Data">
                        <i class="fas fa-sync"></i>
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table class="table" id="lowStockTable">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th style="text-align: center;">Current Stock</th>
                                    <th style="text-align: center;">Min Threshold</th>
                                    <th style="text-align: right;">Price</th>
                                    <th style="text-align: right;">Stock Value</th>
                                </tr>
                            </thead>
                            <tbody id="lowStockTableBody">
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 3rem;">
                                        <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #667eea;"></i>
                                        <p style="margin-top: 1rem; color: #6b7280;">Loading inventory data...</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Inventory Report Content -->

        <!-- Customer Report Content -->
        <div id="customerReportContent" style="display: none;">
            <!-- Customer Stats Cards -->
            <div class="stats-grid"
                style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <div class="stat-card"
                    style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <div>
                        <h4 style="font-size: 0.875rem; font-weight: 500; opacity: 0.9; margin-bottom: 0.5rem;">Total
                            Customers</h4>
                        <h2 class="stat-value" id="totalCustomersReport"
                            style="font-size: 2rem; font-weight: 700; color: white;">0</h2>
                        <p class="stat-subtitle" id="customersPeriod"
                            style="color: rgba(255,255,255,0.8); margin-top: 0.5rem; font-size: 0.875rem;">Active
                            Customers</p>
                    </div>
                    <div class="stat-icon" style="background: rgba(255,255,255,0.2);">
                        <i class="fas fa-users"></i>
                    </div>
                </div>

                <div class="stat-card"
                    style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;">
                    <div>
                        <h4 style="font-size: 0.875rem; font-weight: 500; opacity: 0.9; margin-bottom: 0.5rem;">New
                            Customers</h4>
                        <h2 class="stat-value" id="newCustomersReport"
                            style="font-size: 2rem; font-weight: 700; color: white;">0</h2>
                        <p class="stat-subtitle" id="newCustomersPeriod"
                            style="color: rgba(255,255,255,0.8); margin-top: 0.5rem; font-size: 0.875rem;">This Period
                        </p>
                    </div>
                    <div class="stat-icon" style="background: rgba(255,255,255,0.2);">
                        <i class="fas fa-user-plus"></i>
                    </div>
                </div>

                <div class="stat-card"
                    style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white;">
                    <div>
                        <h4 style="font-size: 0.875rem; font-weight: 500; opacity: 0.9; margin-bottom: 0.5rem;">Total
                            Orders</h4>
                        <h2 class="stat-value" id="totalOrders"
                            style="font-size: 2rem; font-weight: 700; color: white;">0</h2>
                        <p class="stat-subtitle"
                            style="color: rgba(255,255,255,0.8); margin-top: 0.5rem; font-size: 0.875rem;">By Customers
                        </p>
                    </div>
                    <div class="stat-icon" style="background: rgba(255,255,255,0.2);">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                </div>

                <div class="stat-card"
                    style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white;">
                    <div>
                        <h4 style="font-size: 0.875rem; font-weight: 500; opacity: 0.9; margin-bottom: 0.5rem;">Avg
                            Order Value</h4>
                        <h2 class="stat-value" id="avgOrderValue"
                            style="font-size: 2rem; font-weight: 700; color: white;">Rp 0</h2>
                        <p class="stat-subtitle"
                            style="color: rgba(255,255,255,0.8); margin-top: 0.5rem; font-size: 0.875rem;">Per Customer
                        </p>
                    </div>
                    <div class="stat-icon" style="background: rgba(255,255,255,0.2);">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>

            <!-- Top Customers Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-star"></i> Top Customers
                    </h3>
                    <button class="btn btn-sm btn-secondary" onclick="loadCustomerReport()" title="Refresh Data">
                        <i class="fas fa-sync"></i>
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table class="table" id="topCustomersTable">
                            <thead>
                                <tr>
                                    <th style="width: 60px; text-align: center;">Rank</th>
                                    <th>Customer</th>
                                    <th>Phone</th>
                                    <th>Email</th>
                                    <th style="text-align: center;">Total Orders</th>
                                    <th style="text-align: right;">Total Spent</th>
                                </tr>
                            </thead>
                            <tbody id="topCustomersTableBody">
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 3rem;">
                                        <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #667eea;"></i>
                                        <p style="margin-top: 1rem; color: #6b7280;">Loading customer data...</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Customer Report Content -->

        <!-- Product Performance Report Content -->
        <div id="productPerformanceReportContent" style="display: none;">
            <!-- Product Stats Cards -->
            <div class="stats-grid"
                style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <div class="stat-card"
                    style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <div>
                        <h4 style="font-size: 0.875rem; font-weight: 500; opacity: 0.9; margin-bottom: 0.5rem;">Total
                            Products</h4>
                        <h2 class="stat-value" id="totalProductsPerf"
                            style="font-size: 2rem; font-weight: 700; color: white;">0</h2>
                        <p class="stat-subtitle"
                            style="color: rgba(255,255,255,0.8); margin-top: 0.5rem; font-size: 0.875rem;">Active
                            Products</p>
                    </div>
                    <div class="stat-icon" style="background: rgba(255,255,255,0.2);">
                        <i class="fas fa-box"></i>
                    </div>
                </div>

                <div class="stat-card"
                    style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;">
                    <div>
                        <h4 style="font-size: 0.875rem; font-weight: 500; opacity: 0.9; margin-bottom: 0.5rem;">Top
                            Seller</h4>
                        <h2 class="stat-value" style="font-size: 1.5rem; font-weight: 700; color: white;"
                            id="topSellerName">-</h2>
                        <p class="stat-subtitle"
                            style="color: rgba(255,255,255,0.8); margin-top: 0.5rem; font-size: 0.875rem;">Best
                            Performing</p>
                    </div>
                    <div class="stat-icon" style="background: rgba(255,255,255,0.2);">
                        <i class="fas fa-trophy"></i>
                    </div>
                </div>

                <div class="stat-card"
                    style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white;">
                    <div>
                        <h4 style="font-size: 0.875rem; font-weight: 500; opacity: 0.9; margin-bottom: 0.5rem;">Total
                            Revenue</h4>
                        <h2 class="stat-value" id="productsRevenue"
                            style="font-size: 2rem; font-weight: 700; color: white;">Rp 0</h2>
                        <p class="stat-subtitle" id="productsRevenuePeriod"
                            style="color: rgba(255,255,255,0.8); margin-top: 0.5rem; font-size: 0.875rem;">This Period
                        </p>
                    </div>
                    <div class="stat-icon" style="background: rgba(255,255,255,0.2);">
                        <i class="fas fa-coins"></i>
                    </div>
                </div>

                <div class="stat-card"
                    style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white;">
                    <div>
                        <h4 style="font-size: 0.875rem; font-weight: 500; opacity: 0.9; margin-bottom: 0.5rem;">Units
                            Sold</h4>
                        <h2 class="stat-value" id="totalUnitsSold"
                            style="font-size: 2rem; font-weight: 700; color: white;">0</h2>
                        <p class="stat-subtitle" id="unitsSoldPeriod"
                            style="color: rgba(255,255,255,0.8); margin-top: 0.5rem; font-size: 0.875rem;">This Period
                        </p>
                    </div>
                    <div class="stat-icon" style="background: rgba(255,255,255,0.2);">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                </div>
            </div>

            <!-- Top Products Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-trophy"></i> Top Performing Products
                    </h3>
                    <button class="btn btn-sm btn-secondary" onclick="loadProductPerformanceReport()"
                        title="Refresh Data">
                        <i class="fas fa-sync"></i>
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table class="table" id="productPerformanceTable">
                            <thead>
                                <tr>
                                    <th style="width: 60px; text-align: center;">Rank</th>
                                    <th>Product</th>
                                    <th style="width: 150px;">Category</th>
                                    <th style="width: 120px; text-align: center;">Units Sold</th>
                                    <th style="width: 150px; text-align: right;">Revenue</th>
                                    <th style="width: 100px; text-align: center;">Growth</th>
                                </tr>
                            </thead>
                            <tbody id="productPerformanceTableBody">
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 3rem;">
                                        <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #667eea;"></i>
                                        <p style="margin-top: 1rem; color: #6b7280;">Loading product performance data...
                                        </p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Product Performance Report Content -->
    </div>
    <!-- End Report Content Container -->
</div>

<!-- Keyboard Shortcuts Modal -->
<div id="shortcutsModal" class="modal">
    <div class="modal-dialog" style="max-width: 600px;">
        <div class="modal-content">
            <div class="modal-header"
                style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <h3 style="margin: 0; color: white;">⌨️ Keyboard Shortcuts</h3>
                <button class="modal-close" onclick="closeModal('shortcutsModal')"
                    style="background: rgba(255,255,255,0.2); color: white; border: none; width: 32px; height: 32px; border-radius: 50%; cursor: pointer;">&times;</button>
            </div>
            <div class="modal-body">
                <div style="display: grid; gap: 1rem;">
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: #f9fafb; border-radius: 8px;">
                        <span><i class="fas fa-chart-line"></i> Generate Report</span>
                        <kbd>Ctrl + G</kbd>
                    </div>
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: #f9fafb; border-radius: 8px;">
                        <span><i class="fas fa-download"></i> Export Data</span>
                        <kbd>Ctrl + E</kbd>
                    </div>
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: #f9fafb; border-radius: 8px;">
                        <span><i class="fas fa-sync"></i> Refresh Charts</span>
                        <kbd>F5</kbd>
                    </div>
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: #f9fafb; border-radius: 8px;">
                        <span><i class="fas fa-times"></i> Close Modal</span>
                        <kbd>ESC</kbd>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
    // ============================================================================
    // TOAST NOTIFICATION FUNCTION
    // ============================================================================
    function showToast(message, type = 'success') {
        // Create toast container if doesn't exist
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            document.body.appendChild(container);
        }

        // Icon mapping
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };

        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `
        <div class="toast-icon">
            <i class="fas ${icons[type] || icons.info}"></i>
        </div>
        <div class="toast-content">
            <p class="toast-message">${message}</p>
        </div>
        <button class="toast-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;

        container.appendChild(toast);

        // Auto remove after 3 seconds
        setTimeout(() => {
            toast.classList.add('removing');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    let salesChart = null;
    let categoryChart = null;
    let currentPeriod = 'month'; // Default period
    let customDateRange = null;

    document.addEventListener('DOMContentLoaded', () => {
        console.log('✅ DOM loaded, initializing reports...');
        loadReportData(currentPeriod);
        setupEventListeners();
        setupKeyboardShortcuts();
    });

    async function loadReportData(period = 'month', customRange = null) {
        try {
            console.log('📊 Loading report data for period:', period, customRange);

            // Build API URL dengan parameter periode
            let statsUrl = '../api.php?controller=dashboard&action=stats';

            if (customRange && customRange.from && customRange.to) {
                // Custom date range
                statsUrl += `&period=custom&from=${customRange.from}&to=${customRange.to}`;
            } else if (period) {
                // Predefined periods
                statsUrl += `&period=${period}`;
            }

            console.log('🔗 Fetching stats from:', statsUrl);

            const statsResponse = await fetch(statsUrl);
            console.log('📥 Stats response status:', statsResponse.status);

            if (!statsResponse.ok) {
                const text = await statsResponse.text();
                console.error('❌ Stats HTTP Error:', statsResponse.status, text.substring(0, 200));
                throw new Error(`HTTP ${statsResponse.status}: ${statsResponse.statusText}`);
            }

            const statsData = await statsResponse.json();
            console.log('📦 Stats data:', statsData);

            if (statsData.success && statsData.data) {
                console.log('✅ Stats loaded successfully');

                // Update stats berdasarkan periode
                const sales = statsData.data.today_sales || 0;
                const transactions = statsData.data.today_transactions || 0;
                const avgTxn = statsData.data.avg_transaction || 0;
                const growth = statsData.data.sales_change || 0;

                // Animate counters
                animateCounter('totalSales', sales, true);
                animateCounter('totalTransactions', transactions, false);
                animateCounter('avgTransaction', avgTxn, true);
                animateCounter('growthRate', growth, false, true);

                // Update period labels
                updatePeriodLabels(period);
            } else {
                console.warn('⚠️ No data received from stats API');
                console.warn('Response:', statsData);
            }

            // Load charts dengan periode yang sesuai
            console.log('📈 Loading charts...');
            if (customRange && customRange.from && customRange.to) {
                // Use custom date range for chart
                const startDate = customRange.from;
                const endDate = customRange.to;
                await loadSalesChartCustom(startDate, endDate);
            } else {
                const chartDays = getChartDays(period, customRange);
                await loadSalesChart(chartDays);
            }
            await loadCategoryChart(period, customRange);
            await loadTopProducts();

            console.log('✅ All report data loaded');
        } catch (error) {
            console.error('❌ Failed to load report data:');
            console.error('  - Type:', error.constructor.name);
            console.error('  - Message:', error.message);
            console.error('  - Stack:', error.stack);
            showToast('Failed to load report data: ' + error.message, 'error');
        }
    }

    // Helper: Convert period to chart days
    function getChartDays(period, customRange) {
        if (customRange && customRange.from && customRange.to) {
            const days = Math.ceil((new Date(customRange.to) - new Date(customRange.from)) / (1000 * 60 * 60 * 24));
            return Math.min(days, 90); // Max 90 days untuk chart
        }

        const periodMap = {
            'today': 1,
            'week': 7,
            'month': 30,
            'quarter': 90,
            'year': 365
        };

        return periodMap[period] || 30;
    }

    // Update period labels
    function updatePeriodLabels(period) {
        const periodLabels = {
            'today': 'Today',
            'week': 'This Week',
            'month': 'This Month',
            'quarter': 'This Quarter',
            'year': 'This Year',
            'custom': 'Custom Period'
        };

        const label = periodLabels[period] || 'This Month';

        // Update subtitle labels
        const salesPeriod = document.getElementById('salesPeriod');
        if (salesPeriod) salesPeriod.textContent = label;

        const transactionsPeriod = document.getElementById('transactionsPeriod');
        if (transactionsPeriod) transactionsPeriod.textContent = label;
    }

    function animateCounter(elementId, target, isCurrency = false, isPercent = false) {
        const element = document.getElementById(elementId);
        if (!element) return;

        const duration = 1000;
        const start = 0;
        const increment = target / (duration / 16);
        let current = start;

        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                if (isCurrency) {
                    element.textContent = formatCurrency(target);
                } else if (isPercent) {
                    element.textContent = target.toFixed(1) + '%';
                } else {
                    element.textContent = Math.round(target);
                }
                clearInterval(timer);
            } else {
                if (isCurrency) {
                    element.textContent = formatCurrency(Math.floor(current));
                } else if (isPercent) {
                    element.textContent = current.toFixed(1) + '%';
                } else {
                    element.textContent = Math.floor(current);
                }
            }
        }, 16);
    }

    function addTrendIndicator(elementId, trend) {
        const element = document.getElementById(elementId);
        if (!element || trend === 0) return;

        const card = element.closest('.stat-card');
        if (!card) return;

        const isPositive = trend > 0;
        const icon = isPositive ? 'fa-arrow-up' : 'fa-arrow-down';
        const sign = isPositive ? '+' : '';

        const trendHtml = `
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem; font-size: 0.875rem; animation: fadeInUp 0.5s ease 0.3s both;">
            <span style="display: flex; align-items: center; gap: 0.25rem; color: rgba(255,255,255,0.9); font-weight: 600;">
                <i class="fas ${icon}" style="font-size: 0.75rem;"></i>
                ${sign}${Math.abs(trend)}%
            </span>
            <span style="color: rgba(255,255,255,0.7);">vs last period</span>
        </div>
    `;

        let trendContainer = card.querySelector('.trend-indicator');
        if (!trendContainer) {
            trendContainer = document.createElement('div');
            trendContainer.className = 'trend-indicator';
            card.querySelector('div').appendChild(trendContainer);
        }
        trendContainer.innerHTML = trendHtml;
    }

    async function loadSalesChartCustom(startDate, endDate) {
        try {
            console.log('📈 Loading sales chart for custom range:', startDate, 'to', endDate);

            const loadingEl = document.getElementById('salesChartLoading');
            const chartEl = document.getElementById('salesTrendChart');

            if (loadingEl) loadingEl.style.display = 'block';
            if (chartEl) chartEl.style.display = 'none';

            const url = `../api.php?controller=transaction&action=daily-sales&start_date=${startDate}&end_date=${endDate}`;
            console.log('🔗 Fetching chart from:', url);

            const response = await fetch(url);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();
            if (data.success && data.data) {
                if (loadingEl) loadingEl.style.display = 'none';
                if (chartEl) chartEl.style.display = 'block';

                const ctx = document.getElementById('salesTrendChart').getContext('2d');
                if (salesChart) salesChart.destroy();

                salesChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.data.labels || [],
                        datasets: [{
                            label: 'Sales (Rp)',
                            data: data.data.sales || [],
                            borderColor: '#667eea',
                            backgroundColor: 'rgba(102, 126, 234, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: true }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: (value) => 'Rp ' + (value / 1000) + 'K'
                                }
                            }
                        }
                    }
                });
            }
        } catch (error) {
            console.error('Failed to load sales chart:', error);
        }
    }

    async function loadSalesChart(days = 30) {
        try {
            console.log('📈 Loading sales chart for', days, 'days...');

            const loadingEl = document.getElementById('salesChartLoading');
            const chartEl = document.getElementById('salesTrendChart');

            if (loadingEl) loadingEl.style.display = 'block';
            if (chartEl) chartEl.style.display = 'none';

            // Get daily sales for selected period
            const endDate = new Date().toISOString().split('T')[0];
            const startDate = new Date(Date.now() - days * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
            const url = `../api.php?controller=transaction&action=daily-sales&start_date=${startDate}&end_date=${endDate}`;
            console.log('🔗 Fetching chart from:', url);

            const response = await fetch(url);
            console.log('📥 Chart response status:', response.status);

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();
            console.log('📦 Chart data:', data);

            if (data.success && data.data) {
                if (loadingEl) loadingEl.style.display = 'none';
                if (chartEl) chartEl.style.display = 'block';

                const ctx = document.getElementById('salesTrendChart').getContext('2d');

                // Destroy existing chart
                if (salesChart) salesChart.destroy();

                salesChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.data.labels || [],
                        datasets: [{
                            label: 'Sales (Rp)',
                            data: data.data.sales || [],
                            borderColor: '#667eea',
                            backgroundColor: 'rgba(102, 126, 234, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: true }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: (value) => 'Rp ' + (value / 1000) + 'K'
                                }
                            }
                        }
                    }
                });
            } else {
                throw new Error('No chart data received');
            }
        } catch (error) {
            console.error('Failed to load sales chart:', error);
            document.getElementById('salesChartLoading').innerHTML = `
            <i class="fas fa-exclamation-circle" style="font-size: 2rem; color: #ef4444;"></i>
            <p style="margin-top: 1rem; color: #6b7280;">Failed to load chart</p>
            <button class="btn btn-sm btn-primary" onclick="loadSalesChart()" style="margin-top: 0.5rem;">
                <i class="fas fa-redo"></i> Retry
            </button>
        `;
        }
    }

    async function loadCategoryChart(period = null, customRange = null) {
        try {
            console.log('🥧 Loading category chart...', period);

            const loadingEl = document.getElementById('categoryChartLoading');
            const chartEl = document.getElementById('categoryChart');

            if (loadingEl) loadingEl.style.display = 'block';
            if (chartEl) chartEl.style.display = 'none';

            // Note: categoryPerformance API doesn't support period filter yet
            // It shows all categories. Can be enhanced later if needed.
            const url = '/api.php?controller=dashboard&action=categoryStock';
            console.log('🔗 Fetching category from:', url);

            const response = await fetch(url);
            console.log('📥 Category response status:', response.status);

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();
            console.log('📦 Category data:', data);

            if (data.success && data.data && data.data.categories) {
                if (loadingEl) loadingEl.style.display = 'none';
                if (chartEl) chartEl.style.display = 'block';

                const ctx = document.getElementById('categoryChart').getContext('2d');

                // Destroy existing chart
                if (categoryChart) categoryChart.destroy();

                categoryChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: data.data.categories.map(c => c.name),
                        datasets: [{
                            data: data.data.categories.map(c => c.stock_value || 0),
                            backgroundColor: [
                                '#667eea', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right'
                            }
                        }
                    }
                });
            }
        } catch (error) {
            console.error('Failed to load category chart:', error);
            document.getElementById('categoryChartLoading').innerHTML = `
            <i class="fas fa-exclamation-circle" style="font-size: 2rem; color: #ef4444;"></i>
            <p style="margin-top: 1rem; color: #6b7280;">Failed to load chart</p>
            <button class="btn btn-sm btn-primary" onclick="loadCategoryChart()" style="margin-top: 0.5rem;">
                <i class="fas fa-redo"></i> Retry
            </button>
        `;
        }
    }

    async function loadTopProducts() {
        try {
            console.log('🏆 Loading top products...');

            const loadingRow = document.getElementById('loadingRow');
            if (loadingRow) {
                loadingRow.style.display = 'table-row';
            }

            // Fetch real top-selling products
            const url = '../api.php?controller=dashboard&action=topProducts&limit=10';
            console.log('🔗 Fetching products from:', url);

            const response = await fetch(url);
            console.log('📥 Products response status:', response.status);

            if (!response.ok) {
                const text = await response.text();
                console.error('❌ Products HTTP Error:', text.substring(0, 200));
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();
            console.log('📦 Products data:', data);

            const tbody = document.querySelector('#topProductsTable tbody');
            if (loadingRow) {
                loadingRow.style.display = 'none';
            }

            if (data.success && data.data && data.data.products && data.data.products.length > 0) {
                tbody.innerHTML = data.data.products.map((product, index) => `
                <tr style="animation: fadeIn 0.3s ease;">
                    <td style="text-align: center;">
                        <span class="rank-badge ${index < 3 ? 'rank-top' : ''}">${index + 1}</span>
                    </td>
                    <td style="font-weight: 600; color: #374151;">${product.name}</td>
                    <td style="color: #6b7280;">${product.category_name || '-'}</td>
                    <td style="text-align: center;">
                        <span style="background: #f3f4f6; padding: 0.375rem 0.75rem; border-radius: 20px; font-weight: 600;">
                            ${product.total_quantity}
                        </span>
                    </td>
                    <td style="text-align: right; font-weight: 700; color: #059669;">${formatCurrency(product.total_revenue)}</td>
                    <td style="text-align: center;">
                        <span class="badge badge-success" style="padding: 0.375rem 0.75rem; border-radius: 20px; background: #dcfce7; color: #059669; font-size: 0.75rem; font-weight: 600;">
                            <i class="fas fa-arrow-up"></i> ${((product.total_revenue / 1000000) * 10).toFixed(1)}%
                        </span>
                    </td>
                </tr>
            `).join('');
            } else {
                tbody.innerHTML = `
                <tr>
                    <td colspan="6" style="text-align: center; padding: 3rem;">
                        <i class="fas fa-inbox" style="font-size: 3rem; color: #9ca3af; margin-bottom: 1rem;"></i>
                        <p style="color: #6b7280; font-size: 1rem; font-weight: 600; margin-bottom: 0.5rem;">No products found</p>
                        <p style="color: #9ca3af; font-size: 0.875rem;">Try adjusting your filters or check back later</p>
                    </td>
                </tr>
            `;
            }
        } catch (error) {
            console.error('Failed to load top products:', error);
            const tbody = document.querySelector('#topProductsTable tbody');
            tbody.innerHTML = `
            <tr>
                <td colspan="6" style="text-align: center; padding: 3rem;">
                    <i class="fas fa-exclamation-circle" style="font-size: 3rem; color: #ef4444; margin-bottom: 1rem;"></i>
                    <p style="color: #6b7280; margin-bottom: 1rem;">Failed to load products</p>
                    <button class="btn btn-primary" onclick="loadTopProducts()">
                        <i class="fas fa-redo"></i> Retry
                    </button>
                </td>
            </tr>
        `;
        }
    }

    // ============================================================================
    // INVENTORY REPORT FUNCTIONS
    // ============================================================================
    async function loadInventoryReport() {
        try {
            console.log('📦 Loading inventory report...');

            const response = await fetch('/api.php?controller=dashboard&action=stockStats');
            const data = await response.json();

            if (data.success && data.data) {
                // Update stats cards
                animateCounter('totalProducts', data.data.active_products || 0, false);
                animateCounter('outOfStock', data.data.out_of_stock || 0, false);
                animateCounter('lowStockProducts', data.data.low_stock || 0, false);
                animateCounter('stockValue', data.data.stock_value || 0, true);

                // Load low stock products table
                await loadLowStockProductsTable();
            }
        } catch (error) {
            console.error('Failed to load inventory report:', error);
            showToast('Failed to load inventory data', 'error');
        }
    }

    async function loadLowStockProductsTable() {
        try {
            console.log('📦 Loading low stock products table...');

            // Use correct endpoint for low stock products
            const response = await fetch('/api.php?controller=product&action=getLowStock&limit=100');
            console.log('📥 Low stock response status:', response.status);

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            console.log('📦 Low stock data:', data);

            const tbody = document.getElementById('lowStockTableBody');
            if (!tbody) {
                console.error('❌ lowStockTableBody element not found');
                return;
            }

            // Handle both response formats: data.data.products or data.data
            const products = (data.success && data.data)
                ? (data.data.products || data.data || [])
                : [];

            if (products && products.length > 0) {
                console.log('✅ Found', products.length, 'low stock products');
                tbody.innerHTML = products.map(product => `
                <tr>
                    <td style="font-weight: 600; color: #374151;">${product.name || 'Unknown Product'}</td>
                    <td style="color: #6b7280;">${product.category_name || '-'}</td>
                    <td style="text-align: center;">
                        <span style="background: ${product.stock_quantity === 0 ? '#fee2e2' : '#fef3c7'}; color: ${product.stock_quantity === 0 ? '#991b1b' : '#92400e'}; padding: 0.375rem 0.75rem; border-radius: 20px; font-weight: 600;">
                            ${product.stock_quantity || 0}
                        </span>
                    </td>
                    <td style="text-align: center;">
                        <span style="background: #f3f4f6; padding: 0.375rem 0.75rem; border-radius: 20px; font-weight: 600;">
                            ${product.min_stock_level || 0}
                        </span>
                    </td>
                    <td style="text-align: right; font-weight: 600;">${formatCurrency(product.price || 0)}</td>
                    <td style="text-align: right; font-weight: 700; color: #059669;">
                        ${formatCurrency((product.stock_quantity || 0) * (product.price || 0))}
                    </td>
                </tr>
            `).join('');
            } else {
                console.log('ℹ️ No low stock products found');
                tbody.innerHTML = `
                <tr>
                    <td colspan="6" style="text-align: center; padding: 3rem; color: #9ca3af;">
                        <i class="fas fa-check-circle" style="font-size: 2rem; margin-bottom: 1rem; color: #10b981;"></i>
                        <p style="margin: 0;">No low stock products found</p>
                        <p style="margin-top: 0.5rem; font-size: 0.875rem;">All products are above minimum stock level</p>
                    </td>
                </tr>
            `;
            }
        } catch (error) {
            console.error('❌ Failed to load low stock products:', error);
            const tbody = document.getElementById('lowStockTableBody');
            if (tbody) {
                tbody.innerHTML = `
                <tr>
                    <td colspan="6" style="text-align: center; padding: 3rem; color: #ef4444;">
                        <i class="fas fa-exclamation-circle" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                        <p style="margin: 0;">Failed to load low stock products</p>
                        <p style="margin-top: 0.5rem; font-size: 0.875rem; color: #6b7280;">${error.message || 'Unknown error'}</p>
                        <button class="btn btn-sm btn-primary" onclick="loadInventoryReport()" style="margin-top: 1rem;">
                            <i class="fas fa-redo"></i> Retry
                        </button>
                    </td>
                </tr>
            `;
            }
        }
    }

    // ============================================================================
    // CUSTOMER REPORT FUNCTIONS
    // ============================================================================
    async function loadCustomerReport() {
        try {
            console.log('👥 Loading customer report...');

            // Load customer stats
            let statsUrl = '../api.php?controller=dashboard&action=stats';
            if (currentPeriod && currentPeriod !== 'custom') {
                statsUrl += `&period=${currentPeriod}`;
            } else if (customDateRange && customDateRange.from && customDateRange.to) {
                statsUrl += `&period=custom&from=${customDateRange.from}&to=${customDateRange.to}`;
            }

            const statsResponse = await fetch(statsUrl);
            const statsData = await statsResponse.json();

            if (statsData.success && statsData.data) {
                animateCounter('totalCustomersReport', statsData.data.total_customers || 0, false);
                animateCounter('newCustomersReport', statsData.data.new_customers || 0, false);

                // Calculate customer orders
                const totalOrders = statsData.data.today_transactions || 0;
                const avgOrderValue = totalOrders > 0 ? (statsData.data.today_sales || 0) / totalOrders : 0;

                animateCounter('totalOrders', totalOrders, false);
                animateCounter('avgOrderValue', avgOrderValue, true);

                // Update period labels
                updateCustomerPeriodLabels(currentPeriod);
            }

            // Load top customers
            await loadTopCustomers();
        } catch (error) {
            console.error('Failed to load customer report:', error);
            showToast('Failed to load customer data', 'error');
        }
    }

    // Select Preset Range
    function selectPresetRange(range) {
        // Update active state
        document.querySelectorAll('.preset-range-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        event.target.classList.add('active');

        // Hide custom date inputs
        const customRow = document.getElementById('customDateInputsRow');
        if (customRow) {
            customRow.style.display = 'none';
        }

        // Update text
        const rangeLabels = {
            'today': 'Today',
            'yesterday': 'Yesterday',
            'week': 'This Week',
            'month': 'This Month',
            'quarter': 'This Quarter',
            'year': 'This Year'
        };

        const dateRangeText = document.getElementById('dateRangeText');
        if (dateRangeText) {
            dateRangeText.textContent = rangeLabels[range] || 'This Month';
        }

        // Close dropdown if exists
        const dropdown = document.getElementById('dateRangeDropdown');
        if (dropdown) {
            dropdown.classList.remove('active');
        }

        // Update current period and reload
        currentPeriod = range;
        customDateRange = null;

        console.log('📅 Selected preset range:', range);
    }
    function updateCustomerPeriodLabels(period) {
        const periodLabels = {
            'today': 'Today',
            'week': 'This Week',
            'month': 'This Month',
            'quarter': 'This Quarter',
            'year': 'This Year',
            'custom': 'Custom Period'
        };

        const label = periodLabels[period] || 'This Month';
        const customersPeriod = document.getElementById('customersPeriod');
        const newCustomersPeriod = document.getElementById('newCustomersPeriod');

        if (customersPeriod) customersPeriod.textContent = 'Active Customers';
        if (newCustomersPeriod) newCustomersPeriod.textContent = label;
    }

    async function loadTopCustomers() {
        try {
            const response = await fetch('../api.php?controller=customer&action=top&limit=10');
            const data = await response.json();

            const tbody = document.getElementById('topCustomersTableBody');
            if (!tbody) return;

            // API returns data directly (not wrapped in data.data)
            const customers = (data.success && data.data) ? data.data : (data.data || data);

            if (customers && customers.length > 0) {
                tbody.innerHTML = customers.map((customer, index) => `
                <tr>
                    <td style="text-align: center;">
                        <span class="rank-badge ${index < 3 ? 'rank-top' : ''}">${index + 1}</span>
                    </td>
                    <td style="font-weight: 600; color: #374151;">${customer.name || customer.full_name || 'Unknown'}</td>
                    <td style="color: #6b7280;">${customer.phone || '-'}</td>
                    <td style="color: #6b7280;">${customer.email || '-'}</td>
                    <td style="text-align: center;">
                        <span style="background: #f3f4f6; padding: 0.375rem 0.75rem; border-radius: 20px; font-weight: 600;">
                            ${customer.total_orders || 0}
                        </span>
                    </td>
                    <td style="text-align: right; font-weight: 700; color: #059669;">
                        ${formatCurrency(customer.total_spent || 0)}
                    </td>
                </tr>
            `).join('');
            } else {
                tbody.innerHTML = `
                <tr>
                    <td colspan="6" style="text-align: center; padding: 3rem; color: #9ca3af;">
                        <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                        <p>No customer data found</p>
                    </td>
                </tr>
            `;
            }
        } catch (error) {
            console.error('Failed to load top customers:', error);
            const tbody = document.getElementById('topCustomersTableBody');
            if (tbody) {
                tbody.innerHTML = `
                <tr>
                    <td colspan="6" style="text-align: center; padding: 3rem; color: #ef4444;">
                        <i class="fas fa-exclamation-circle" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                        <p>Failed to load customer data</p>
                    </td>
                </tr>
            `;
            }
        }
    }

    // ============================================================================
    // PRODUCT PERFORMANCE REPORT FUNCTIONS
    // ============================================================================
    async function loadProductPerformanceReport() {
        try {
            console.log('🏆 Loading product performance report...');

            // Load product stats
            const statsResponse = await fetch('../api.php?controller=dashboard&action=stats');
            const statsData = await statsResponse.json();

            // Load top products
            const productsResponse = await fetch('../api.php?controller=dashboard&action=topProducts&limit=10');
            const productsData = await productsResponse.json();

            if (productsData.success && productsData.data && productsData.data.products) {
                const products = productsData.data.products;

                // Update stats
                animateCounter('totalProductsPerf', products.length || 0, false);

                if (products.length > 0) {
                    const topProduct = products[0];
                    const topSellerEl = document.getElementById('topSellerName');
                    if (topSellerEl) {
                        topSellerEl.textContent = topProduct.name || '-';
                        topSellerEl.style.fontSize = topProduct.name.length > 20 ? '1.25rem' : '1.5rem';
                    }

                    // Calculate totals
                    const totalRevenue = products.reduce((sum, p) => sum + (parseFloat(p.total_revenue) || 0), 0);
                    const totalUnits = products.reduce((sum, p) => sum + (parseInt(p.total_quantity) || 0), 0);

                    animateCounter('productsRevenue', totalRevenue, true);
                    animateCounter('totalUnitsSold', totalUnits, false);

                    // Update period labels
                    updateProductPeriodLabels(currentPeriod);
                }

                // Render table
                renderProductPerformanceTable(products);
            }
        } catch (error) {
            console.error('Failed to load product performance report:', error);
            showToast('Failed to load product performance data', 'error');
        }
    }

    function updateProductPeriodLabels(period) {
        const periodLabels = {
            'today': 'Today',
            'week': 'This Week',
            'month': 'This Month',
            'quarter': 'This Quarter',
            'year': 'This Year',
            'custom': 'Custom Period'
        };

        const label = periodLabels[period] || 'This Month';
        const productsRevenuePeriod = document.getElementById('productsRevenuePeriod');
        const unitsSoldPeriod = document.getElementById('unitsSoldPeriod');

        if (productsRevenuePeriod) productsRevenuePeriod.textContent = label;
        if (unitsSoldPeriod) unitsSoldPeriod.textContent = label;
    }

    function renderProductPerformanceTable(products) {
        const tbody = document.getElementById('productPerformanceTableBody');
        if (!tbody) return;

        if (products && products.length > 0) {
            tbody.innerHTML = products.map((product, index) => `
            <tr>
                <td style="text-align: center;">
                    <span class="rank-badge ${index < 3 ? 'rank-top' : ''}">${index + 1}</span>
                </td>
                <td style="font-weight: 600; color: #374151;">${product.name}</td>
                <td style="color: #6b7280;">${product.category_name || '-'}</td>
                <td style="text-align: center;">
                    <span style="background: #f3f4f6; padding: 0.375rem 0.75rem; border-radius: 20px; font-weight: 600;">
                        ${product.total_quantity || 0}
                    </span>
                </td>
                <td style="text-align: right; font-weight: 700; color: #059669;">
                    ${formatCurrency(product.total_revenue || 0)}
                </td>
                <td style="text-align: center;">
                    <span class="badge badge-success" style="padding: 0.375rem 0.75rem; border-radius: 20px; background: #dcfce7; color: #059669; font-size: 0.75rem; font-weight: 600;">
                        <i class="fas fa-arrow-up"></i> ${((product.total_revenue / 1000000) * 10).toFixed(1)}%
                    </span>
                </td>
            </tr>
        `).join('');
        } else {
            tbody.innerHTML = `
            <tr>
                <td colspan="6" style="text-align: center; padding: 3rem; color: #9ca3af;">
                    <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                    <p>No product data found</p>
                </td>
            </tr>
        `;
        }
    }

    function setupEventListeners() {
        // Report type selector - switch between report types
        const reportTypeSelect = document.getElementById('reportType');
        if (reportTypeSelect) {
            reportTypeSelect.addEventListener('change', (e) => {
                switchReportType(e.target.value);
            });
            console.log('✅ Report type listener attached');
        }

        // Generate report button
        const generateBtn = document.getElementById('generateReportBtn');
        if (generateBtn) {
            generateBtn.addEventListener('click', generateReport);
            console.log('✅ Generate button listener attached');
        } else {
            console.error('❌ Generate button not found');
        }

        // Date range selector - auto reload when changed (optional: can also require Generate click)
        const dateRangeQuick = document.getElementById('dateRangeQuick');
        if (dateRangeQuick) {
            dateRangeQuick.addEventListener('change', (e) => {
                const period = e.target.value;
                const customDateRangeDiv = document.getElementById('customDateRange');

                if (period === 'custom') {
                    // Show custom date range inputs
                    if (customDateRangeDiv) {
                        customDateRangeDiv.style.display = 'block';
                        // Set default dates (last 30 days)
                        const today = new Date();
                        const lastMonth = new Date();
                        lastMonth.setDate(lastMonth.getDate() - 30);

                        const dateFrom = document.getElementById('dateFrom');
                        const dateTo = document.getElementById('dateTo');
                        if (dateFrom) dateFrom.value = lastMonth.toISOString().split('T')[0];
                        if (dateTo) dateTo.value = today.toISOString().split('T')[0];
                    }
                } else if (period && period !== '') {
                    // Hide custom date range and auto-generate report
                    if (customDateRangeDiv) customDateRangeDiv.style.display = 'none';
                    currentPeriod = period;
                    customDateRange = null;
                    const reportType = reportTypeSelect ? reportTypeSelect.value : 'sales';
                    switchReportType(reportType);
                }
            });
            console.log('✅ Date range listener attached');
        } else {
            console.error('❌ Date range selector not found');
        }
    }

    // Function to switch between report types
    function switchReportType(reportType) {
        console.log('🔄 Switching to report type:', reportType);

        // Hide all report contents
        document.getElementById('salesReportContent').style.display = 'none';
        document.getElementById('inventoryReportContent').style.display = 'none';
        document.getElementById('customerReportContent').style.display = 'none';
        document.getElementById('productPerformanceReportContent').style.display = 'none';

        // Show selected report content
        switch (reportType) {
            case 'sales':
                document.getElementById('salesReportContent').style.display = 'block';
                loadReportData(currentPeriod, customDateRange);
                break;
            case 'inventory':
                document.getElementById('inventoryReportContent').style.display = 'block';
                loadInventoryReport();
                break;
            case 'customers':
                document.getElementById('customerReportContent').style.display = 'block';
                loadCustomerReport();
                break;
            case 'products':
                document.getElementById('productPerformanceReportContent').style.display = 'block';
                loadProductPerformanceReport();
                break;
            default:
                document.getElementById('salesReportContent').style.display = 'block';
                loadReportData(currentPeriod, customDateRange);
        }

        showToast(`Switched to ${reportType} report`, 'success');
    }

    function setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl+G: Generate Report
            if (e.ctrlKey && e.key === 'g') {
                e.preventDefault();
                generateReport();
            }

            // Ctrl+E: Export
            if (e.ctrlKey && e.key === 'e') {
                e.preventDefault();
                toggleExportMenu();
            }

            // F5: Refresh
            if (e.key === 'F5') {
                e.preventDefault();
                loadReportData();
                showToast('Data refreshed', 'success');
            }

            // ESC: Close modal
            if (e.key === 'Escape') {
                closeAllModals();
            }
        });
    }

    function generateReport() {
        const periodSelect = document.getElementById('dateRangeQuick');
        const period = periodSelect ? periodSelect.value : 'month';

        if (!period || period === '') {
            showToast('Please select a period', 'warning');
            return;
        }

        currentPeriod = period;
        customDateRange = null;

        showToast('Generating report...', 'info');
        loadReportData(currentPeriod, customDateRange);
    }

    function applyCustomDateRange() {
        const dateFromEl = document.getElementById('dateFrom');
        const dateToEl = document.getElementById('dateTo');

        const dateFrom = dateFromEl ? dateFromEl.value : '';
        const dateTo = dateToEl ? dateToEl.value : '';

        if (!dateFrom || !dateTo) {
            showToast('Please select both start and end dates', 'warning');
            return;
        }

        if (new Date(dateFrom) > new Date(dateTo)) {
            showToast('Start date cannot be after end date', 'error');
            return;
        }

        currentPeriod = 'custom';
        customDateRange = { from: dateFrom, to: dateTo };

        showToast(`Showing data from ${dateFrom} to ${dateTo}`, 'success');
        loadReportData(currentPeriod, customDateRange);
    }

    function toggleExportMenu() {
        const menu = document.getElementById('exportMenu');
        if (!menu) {
            console.error('❌ Export menu not found');
            return;
        }

        menu.classList.toggle('show');

        setTimeout(() => {
            const closeOnClickOutside = (e) => {
                if (!e.target.closest('#exportDropdown')) {
                    menu.classList.remove('show');
                    document.removeEventListener('click', closeOnClickOutside);
                }
            };
            document.addEventListener('click', closeOnClickOutside);
        }, 10);
    }

    function closeDatePickerOnClickOutside(e) {
        const dropdown = document.getElementById('dateRangeDropdown');
        const btn = document.getElementById('dateRangeBtn');

        if (!dropdown.contains(e.target) && !btn.contains(e.target)) {
            dropdown.classList.remove('active');
            document.removeEventListener('click', closeDatePickerOnClickOutside);
        }
    }

    // Toggle Custom Date Inputs
    function toggleCustomDateInputs() {
        const customRow = document.getElementById('customDateInputsRow');
        const customBtn = document.getElementById('customRangeBtn');

        if (customRow.style.display === 'none') {
            customRow.style.display = 'block';
            customBtn.classList.add('active');

            // Remove active from other buttons
            document.querySelectorAll('.preset-range-btn').forEach(btn => {
                if (btn.id !== 'customRangeBtn') {
                    btn.classList.remove('active');
                }
            });
        } else {
            customRow.style.display = 'none';
            customBtn.classList.remove('active');
        }
    }

    // Select Preset Range
    function selectPresetRange(range) {
        try {
            const reportTypeEl = document.getElementById('reportType');
            const dateRangeEl = document.getElementById('dateRangeQuick');
            const dateFromEl = document.getElementById('dateFrom');
            const dateToEl = document.getElementById('dateTo');
            const exportMenuEl = document.getElementById('exportMenu');

            const reportType = reportTypeEl ? reportTypeEl.value : 'sales';
            const period = dateRangeEl ? dateRangeEl.value : 'month';

            // Close export menu
            if (exportMenuEl) {
                exportMenuEl.classList.remove('show');
            }

            // Calculate date range based on period
            let dateFrom = '';
            let dateTo = '';

            if (customDateRange && customDateRange.from && customDateRange.to) {
                // Use custom date range
                dateFrom = customDateRange.from;
                dateTo = customDateRange.to;
            } else if (period === 'custom') {
                // Get from date inputs
                dateFrom = dateFromEl ? dateFromEl.value : '';
                dateTo = dateToEl ? dateToEl.value : '';

                if (!dateFrom || !dateTo) {
                    showToast('Please select date range before exporting', 'warning');
                    return;
                }
            } else {
                // Calculate date range from period
                const dateRange = getDateRangeFromPeriod(period);
                dateFrom = dateRange.start;
                dateTo = dateRange.end;
            }

            // Map report type to backend expected type
            const reportTypeMap = {
                'sales': 'sales',
                'inventory': 'inventory',
                'customers': 'customers',
                'products': 'products'  // Backend accepts both 'products' and 'product_performance'
            };

            const backendType = reportTypeMap[reportType] || 'sales';

            // Build export URL using legacy dashboard export (supports type + date range)
            // Note: Inventory report doesn't need date range
            let url = `../api_dashboard.php?action=export&type=${backendType}&format=${format}`;
            if (backendType !== 'inventory') {
                // Only add date range for reports that support it
                if (dateFrom) url += `&date_from=${encodeURIComponent(dateFrom)}`;
                if (dateTo) url += `&date_to=${encodeURIComponent(dateTo)}`;
            }

            console.log('📥 Exporting report:', {
                frontendType: reportType,
                backendType: backendType,
                format: format,
                from: dateFrom,
                to: dateTo,
                url: url
            });

            const reportTypeNames = {
                'sales': 'Sales',
                'inventory': 'Inventory',
                'customers': 'Customer',
                'products': 'Product Performance'
            };

            showToast(`Exporting ${reportTypeNames[reportType] || reportType} report as ${format.toUpperCase()}...`, 'info');

            // Create and trigger download
            setTimeout(() => {
                // Use fetch first to handle errors
                fetch(url)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                        }

                        // Get filename from Content-Disposition header or use default
                        const contentDisposition = response.headers.get('Content-Disposition');
                        let filename = `report_${reportType}_${Date.now()}.${format === 'pdf' ? 'pdf' : format === 'excel' || format === 'xlsx' ? 'xlsx' : 'csv'}`;

                        if (contentDisposition) {
                            const filenameMatch = contentDisposition.match(/filename="(.+)"/);
                            if (filenameMatch) {
                                filename = filenameMatch[1];
                            }
                        }

                        return response.blob().then(blob => ({ blob, filename }));
                    })
                    .then(({ blob, filename }) => {
                        // Create download link
                        const link = document.createElement('a');
                        const url = window.URL.createObjectURL(blob);
                        link.href = url;
                        link.download = filename;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        window.URL.revokeObjectURL(url);

                        showToast(`Report exported successfully as ${format.toUpperCase()}!`, 'success');
                    })
                    .catch(error => {
                        console.error('❌ Export error:', error);
                        showToast(`Failed to export report: ${error.message}`, 'error');
                    });
            }, 300);

        } catch (error) {
            console.error('❌ Export error:', error);
            showToast('Failed to export report: ' + error.message, 'error');
        }
    }

    // Helper function to get date range from period
    function getDateRangeFromPeriod(period) {
        const today = new Date();
        const endDate = today.toISOString().split('T')[0];
        let startDate = '';

        switch (period) {
            case 'today':
                startDate = endDate;
                break;
            case 'week':
                const monday = new Date(today);
                monday.setDate(today.getDate() - (today.getDay() === 0 ? 6 : today.getDay() - 1));
                startDate = monday.toISOString().split('T')[0];
                break;
            case 'month':
                startDate = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
                break;
            case 'quarter':
                const quarter = Math.floor(today.getMonth() / 3);
                startDate = new Date(today.getFullYear(), quarter * 3, 1).toISOString().split('T')[0];
                break;
            case 'year':
                startDate = new Date(today.getFullYear(), 0, 1).toISOString().split('T')[0];
                break;
            default:
                // Default to this month
                startDate = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
        }

        return { start: startDate, end: endDate };
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('closing');
            setTimeout(() => {
                modal.classList.remove('show', 'closing');
            }, 200);
        }
    }

    function closeAllModals() {
        document.querySelectorAll('.modal.show').forEach(modal => {
            modal.classList.add('closing');
            setTimeout(() => {
                modal.classList.remove('show', 'closing');
            }, 200);
        });
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

    .rank-badge.rank-top {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        box-shadow: 0 4px 6px rgba(245, 158, 11, 0.3);
    }

    kbd {
        background: #374151;
        color: white;
        padding: 0.375rem 0.75rem;
        border-radius: 6px;
        font-size: 0.875rem;
        font-weight: 600;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
</style>

<?php require_once 'includes/footer.php'; ?>