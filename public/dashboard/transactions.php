<?php
/**
 * Transactions Page - FULLY UPGRADED
 * Role-based access: Admin, Manager
 * 
 * Features:
 * - 4 Stats cards with trend indicators
 * - Modern filter chips layout
 * - Date range picker
 * - Enhanced transaction detail modal
 * - Mobile responsive card view
 * - Chart toggle view
 * - Keyboard shortcuts
 * - Auto-refresh toggle
 * - Export options
 * - Bulk actions
 */

// Load bootstrap FIRST
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../app/helpers/functions.php';

// Require authentication
requireAuth();

// Admin, Manager, Cashier can view transactions
// Staff NOT allowed (they handle inventory, not sales)
requireRole(['admin', 'manager', 'cashier']);

// Get current user role
$user_role = $_SESSION['user_role'] ?? 'staff';
$_SESSION['user_role'] = $user_role;

// Page configuration
$page_title = 'Transactions';
$additional_css = [];
$additional_js = [];

// Aktifkan compact header untuk tampilan header yang rapi
$header_compact = true;

// Include header
require_once 'includes/header.php';
?>

<!-- Page Content -->
<div class="content">
    <!-- Page Header (Uniform Card Style) -->
    <div class="card" style="margin-bottom: 1.5rem;">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-receipt"></i> Transactions
            </h3>
            <div class="card-actions action-buttons">
                <button class="btn btn-info btn-sm" id="refreshTransactions">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>
        <div class="card-body">
            <p style="color: var(--gray-600); font-size: 0.875rem;">Manage and view all sales transactions</p>
        </div>
    </div>

            <!-- Stats Cards with Trends -->
            <div class="stats-grid stats-grid--wide">
                <div class="stat-card stat-primary stat-card--regular">
                    <div>
                        <div class="stat-label">Today's Transactions</div>
                        <div class="stat-value" id="todayTransactions">0</div>
                    </div>
                    <div class="stat-icon"><i class="fas fa-receipt"></i></div>
                </div>

                <div class="stat-card stat-success stat-card--regular">
                    <div>
                        <div class="stat-label">Today's Revenue</div>
                        <div class="stat-value" id="todayRevenue">Rp 0</div>
                    </div>
                    <div class="stat-icon"><i class="fas fa-coins"></i></div>
                </div>

                <div class="stat-card stat-info stat-card--regular">
                    <div>
                        <div class="stat-label">This Month Revenue</div>
                        <div class="stat-value" id="monthRevenue">Rp 0</div>
                    </div>
                    <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                </div>

                <div class="stat-card stat-warning stat-card--regular">
                    <div>
                        <div class="stat-label">Pending Transactions</div>
                        <div class="stat-value" id="pendingTransactions">0</div>
                    </div>
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                </div>
            </div>

            <!-- View Toggle + Auto Refresh -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                <div style="display: flex; gap: 0.5rem; background: white; padding: 0.25rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <button class="view-toggle-btn active" data-view="table" style="padding: 0.5rem 1rem; border: none; background: var(--primary-color); color: white; border-radius: 6px; cursor: pointer; transition: all 0.2s; font-size: 0.875rem;">
                        <i class="fas fa-table"></i> Table View
                    </button>
                    <button class="view-toggle-btn" data-view="chart" style="padding: 0.5rem 1rem; border: none; background: transparent; color: #6b7280; border-radius: 6px; cursor: pointer; transition: all 0.2s; font-size: 0.875rem;">
                        <i class="fas fa-chart-bar"></i> Chart View
                    </button>
        </div>
                
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; background: white; padding: 0.5rem 1rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <i class="fas fa-sync-alt" style="color: var(--primary-color);"></i>
                        <span style="font-size: 0.875rem; color: #6b7280;">Auto Refresh:</span>
                        <select id="autoRefreshSelect" style="border: 1px solid #e5e7eb; border-radius: 6px; padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                            <option value="0">Off</option>
                            <option value="30">30s</option>
                            <option value="60">60s</option>
                            <option value="120">2min</option>
                    </select>
                </div>
                    <span id="lastUpdated" style="font-size: 0.75rem; color: #9ca3af;"></span>
                </div>
            </div>

            <!-- Filter Chips - Horizontal Layout -->
            <div style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; padding: 1rem; background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <span style="font-weight: 600; color: #374151; font-size: 0.875rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-filter"></i> Filters:
                </span>
                
                <input type="text" id="searchInput" placeholder="🔍 Search transaction, customer..." style="padding: 0.5rem 0.75rem; border: 2px solid #e5e7eb; border-radius: 20px; font-size: 0.875rem; min-width: 250px; transition: all 0.2s;">
                
                <select id="statusFilter" class="filter-chip-select">
                    <option value="">✓ All Status</option>
                    <option value="completed">✓ Completed</option>
                    <option value="pending">⏳ Pending</option>
                    <option value="cancelled">✗ Cancelled</option>
                </select>
                
                <select id="paymentFilter" class="filter-chip-select">
                    <option value="">💳 All Methods</option>
                    <option value="cash">💵 Cash</option>
                    <option value="card">💳 Card</option>
                    <option value="qris">📱 QRIS</option>
                    <option value="transfer">🏦 Transfer</option>
                </select>
                <div style="display:flex; gap:.5rem; align-items:center;">
                    <span style="font-size:.8rem; color:#6b7280;">Source:</span>
                    <div id="sourceTabs" style="display:flex; gap:.25rem;">
                        <button class="status-tab-btn active" id="tabSourceAll" data-source="all"><i class="fas fa-layer-group"></i> All</button>
                        <button class="status-tab-btn" id="tabSourcePos" data-source="pos"><i class="fas fa-store"></i> POS</button>
                        <button class="status-tab-btn" id="tabSourceShop" data-source="shop"><i class="fas fa-shopping-cart"></i> Shop</button>
                    </div>
                </div>
                
                <select id="dateRangeQuick" class="filter-chip-select">
                    <option value="">📅 All Time</option>
                    <option value="today">📅 Today</option>
                    <option value="yesterday">📅 Yesterday</option>
                    <option value="week">📅 This Week</option>
                    <option value="month">📅 This Month</option>
                    <option value="custom">📅 Custom Range</option>
                </select>
                
                <button class="btn btn-sm btn-secondary" onclick="transactionManager.clearFilters()" style="border-radius: 20px; padding: 0.5rem 1rem; background: #f3f4f6; color: #6b7280; border: none; font-size: 0.875rem;">
                    <i class="fas fa-times"></i> Clear
                </button>
                
                <div id="activeFiltersCount" style="margin-left: auto; background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%); color: white; padding: 0.375rem 0.875rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; display: none;">
                    <i class="fas fa-filter"></i> <span id="filterCount">0</span> active
        </div>
    </div>

            <!-- Custom Date Range (Hidden by default) -->
            <div id="customDateRange" style="margin-bottom: 1.5rem; padding: 1rem; background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: none;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label style="font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem; display: block;">From Date</label>
                        <input type="date" id="dateFrom" class="form-control" style="width: 100%;">
                    </div>
                    <div>
                        <label style="font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem; display: block;">To Date</label>
                        <input type="date" id="dateTo" class="form-control" style="width: 100%;">
                    </div>
                </div>
                <button class="btn btn-primary" onclick="transactionManager.applyCustomDateRange()" style="margin-top: 1rem;">
                    <i class="fas fa-check"></i> Apply Date Range
                </button>
            </div>

            <!-- Table View -->
            <div id="tableView" class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-list"></i> All Transactions
            </h3>
                    <div class="card-actions" style="display: flex; gap: 0.5rem;">
                <button class="btn btn-secondary" id="refreshBtn">
                            <i class="fas fa-sync"></i> Refresh
                        </button>
                        
                        <!-- Export Dropdown -->
                        <div class="table-actions-dropdown" id="exportDropdown">
                            <button class="btn btn-success" onclick="transactionManager.toggleExportMenu()">
                                <i class="fas fa-download"></i> Export <i class="fas fa-chevron-down" style="margin-left: 0.25rem; font-size: 0.75rem;"></i>
                            </button>
                            <div class="table-actions-menu" id="exportMenu" style="right: 0;">
                                <button class="table-actions-menu-item" onclick="transactionManager.exportData('csv')">
                                    <i class="fas fa-file-csv"></i>
                                    <span>Export as CSV</span>
                </button>
                                <button class="table-actions-menu-item" onclick="transactionManager.exportData('excel')">
                    <i class="fas fa-file-excel"></i>
                                    <span>Export as Excel</span>
                                </button>
                                <button class="table-actions-menu-item" onclick="transactionManager.exportData('pdf')">
                                    <i class="fas fa-file-pdf"></i>
                                    <span>Export as PDF</span>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Bulk Actions (when items selected) -->
                        <div id="bulkActionsContainer" style="display: none;">
                            <button class="btn btn-warning" id="bulkPrintBtn">
                                <i class="fas fa-print"></i> Print Selected (<span id="selectedCount">0</span>)
                </button>
                        </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-container">
                <table class="table" id="transactionsTable">
                    <thead>
                        <tr>
                                    <th style="width: 50px; text-align: center;">
                                        <input type="checkbox" id="selectAll" style="cursor: pointer;">
                                    </th>
                                    <th style="width: 150px;">Transaction #</th>
                                    <th style="width: 180px;">Date & Time</th>
                                    <th style="width: 150px;">Customer</th>
                                    <th style="width: 80px; text-align: center;">Items</th>
                                    <th style="width: 150px; text-align: right;">Total</th>
                                    <th style="width: 120px; text-align: center;">Payment</th>
                                    <th style="width: 100px; text-align: center;">Status</th>
                                    <th style="width: 120px; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                                <tr id="loadingRow">
                                    <td colspan="9" style="text-align: center; padding: 3rem;">
                                        <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #16a34a;"></i>
                                        <p style="margin-top: 1rem; color: #6b7280;">Loading transactions...</p>
                                    </td>
                                </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="pagination" id="pagination"></div>
        </div>
    </div>

            <!-- Chart View (Hidden by default) -->
            <div id="chartView" class="card" style="display: none;">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-bar"></i> Revenue Analytics
                    </h3>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" style="max-height: 400px;"></canvas>
                </div>
            </div>

            <!-- Mobile Cards Container (Hidden on desktop) -->
            <div id="mobileCardsContainer"></div>
</div>

<!-- Enhanced Transaction Detail Modal -->
<div id="transactionModal" class="modal">
    <div class="modal-dialog" style="max-width: 900px;">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #16a34a 0%, #15803d 100%); color: white; padding: 1.5rem;">
                <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: white;">
                    <i class="fas fa-receipt"></i> Transaction Details
                </h3>
                <button class="modal-close" onclick="transactionManager.closeModal('transactionModal')" style="background: rgba(255,255,255,0.2); color: white; border: none; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 1.25rem;">&times;</button>
        </div>
            <div class="modal-body" id="transactionDetails" style="padding: 1.5rem;">
                <!-- Will be populated dynamically -->
        </div>
            <div class="modal-footer" style="background: #f9fafb; padding: 1rem 1.5rem; display: flex; justify-content: space-between;">
                <button type="button" class="btn btn-secondary" onclick="transactionManager.closeModal('transactionModal')">
                    <i class="fas fa-times"></i> Close
                </button>
                <div style="display: flex; gap: 0.5rem;">
                    <button type="button" class="btn btn-info" id="viewReceiptBtn">
                        <i class="fas fa-eye"></i> View Receipt
                    </button>
            <button type="button" class="btn btn-primary" id="printReceiptBtn">
                <i class="fas fa-print"></i> Print Receipt
            </button>
        </div>
    </div>
</div>
    </div>
</div>

<!-- Cancel Transaction Modal -->
<div id="cancelModal" class="modal">
    <div class="modal-dialog" style="max-width: 500px;">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; padding: 1.5rem;">
                <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: white;">
                    <i class="fas fa-times-circle"></i> Cancel Transaction
                </h3>
                <button class="modal-close" onclick="transactionManager.closeModal('cancelModal')" style="background: rgba(255,255,255,0.2); color: white; border: none; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 1.25rem;">&times;</button>
            </div>
            <div class="modal-body" style="padding: 1.5rem;">
                <div style="background: #fef2f2; border-left: 4px solid #ef4444; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem;">
                    <div style="display: flex; align-items: start; gap: 0.75rem;">
                        <i class="fas fa-exclamation-triangle" style="color: #ef4444; margin-top: 0.25rem;"></i>
                        <div>
                            <div style="font-weight: 600; color: #991b1b; margin-bottom: 0.25rem;">Warning: This action cannot be undone</div>
                            <div style="font-size: 0.875rem; color: #7f1d1d;">Cancelling will return stock to inventory and mark this transaction as cancelled.</div>
                        </div>
                    </div>
                </div>

                <div id="cancelTransactionInfo" style="background: #f9fafb; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                    <!-- Will be populated dynamically -->
                </div>

                <div class="form-group">
                    <label for="cancelReason" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">
                        Reason for Cancellation <span style="color: #ef4444;">*</span>
                    </label>
                    <textarea id="cancelReason" rows="3" class="form-control" placeholder="Enter reason for cancelling this transaction..." required style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 8px; resize: vertical;"></textarea>
                </div>
            </div>
            <div class="modal-footer" style="background: #f9fafb; padding: 1rem 1.5rem; display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="btn btn-secondary" onclick="transactionManager.closeModal('cancelModal')">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmCancelBtn" onclick="transactionManager.confirmCancel()">
                    <i class="fas fa-times-circle"></i> Confirm Cancellation
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Refund Transaction Modal -->
<div id="refundModal" class="modal">
    <div class="modal-dialog" style="max-width: 500px;">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: 1.5rem;">
                <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: white;">
                    <i class="fas fa-undo"></i> Refund Transaction
                </h3>
                <button class="modal-close" onclick="transactionManager.closeModal('refundModal')" style="background: rgba(255,255,255,0.2); color: white; border: none; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 1.25rem;">&times;</button>
            </div>
            <div class="modal-body" style="padding: 1.5rem;">
                <div style="background: #fffbeb; border-left: 4px solid #f59e0b; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem;">
                    <div style="display: flex; align-items: start; gap: 0.75rem;">
                        <i class="fas fa-info-circle" style="color: #f59e0b; margin-top: 0.25rem;"></i>
                        <div>
                            <div style="font-weight: 600; color: #92400e; margin-bottom: 0.25rem;">Refund Information</div>
                            <div style="font-size: 0.875rem; color: #78350f;">Full refund will return stock to inventory. Partial refund will not affect stock.</div>
                        </div>
                    </div>
                </div>

                <div id="refundTransactionInfo" style="background: #f9fafb; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                    <!-- Will be populated dynamically -->
                </div>

                <div class="form-group" style="margin-bottom: 1rem;">
                    <label for="refundAmount" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">
                        Refund Amount <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="number" id="refundAmount" class="form-control" placeholder="Enter refund amount..." required style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 8px;">
                    <small id="refundAmountHelp" style="display: block; margin-top: 0.25rem; color: #6b7280; font-size: 0.75rem;">
                        Maximum: Rp 0
                    </small>
                </div>

                <div class="form-group">
                    <label for="refundReason" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">
                        Reason for Refund <span style="color: #ef4444;">*</span>
                    </label>
                    <textarea id="refundReason" rows="3" class="form-control" placeholder="Enter reason for refunding this transaction..." required style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 8px; resize: vertical;"></textarea>
                </div>
            </div>
            <div class="modal-footer" style="background: #f9fafb; padding: 1rem 1.5rem; display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="btn btn-secondary" onclick="transactionManager.closeModal('refundModal')">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="btn btn-warning" id="confirmRefundBtn" onclick="transactionManager.confirmRefund()">
                    <i class="fas fa-undo"></i> Confirm Refund
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Keyboard Shortcuts Modal -->
<div id="shortcutsModal" class="modal">
    <div class="modal-dialog" style="max-width: 600px;">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <h3 style="margin: 0; color: white;">⌨️ Keyboard Shortcuts</h3>
                <button class="modal-close" onclick="transactionManager.closeModal('shortcutsModal')" style="background: rgba(255,255,255,0.2); color: white; border: none; width: 32px; height: 32px; border-radius: 50%; cursor: pointer;">&times;</button>
            </div>
            <div class="modal-body">
                <div style="display: grid; gap: 1rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: #f9fafb; border-radius: 8px;">
                        <span><i class="fas fa-search"></i> Focus Search</span>
                        <kbd>Ctrl + F</kbd>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: #f9fafb; border-radius: 8px;">
                        <span><i class="fas fa-sync"></i> Refresh Data</span>
                        <kbd>F5</kbd>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: #f9fafb; border-radius: 8px;">
                        <span><i class="fas fa-download"></i> Export Data</span>
                        <kbd>Ctrl + E</kbd>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: #f9fafb; border-radius: 8px;">
                        <span><i class="fas fa-times"></i> Close Modal</span>
                        <kbd>ESC</kbd>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: #f9fafb; border-radius: 8px;">
                        <span><i class="fas fa-chart-bar"></i> Toggle View</span>
                        <kbd>Ctrl + T</kbd>
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

// ============================================================================
// TRANSACTION MANAGER CLASS
// ============================================================================
class TransactionManager {
    constructor() {
        this.transactions = [];
        this.filteredTransactions = [];
        this.currentPage = 1;
        this.itemsPerPage = 10;
        this.selectedTransactions = new Set();
        this.autoRefreshInterval = null;
        this.revenueChart = null;
        this.userRole = '<?php echo $user_role; ?>';
        this.currentSource = 'all';
    }

    async init() {
        await this.loadStats();
        await this.loadTransactions();
        this.setupEventListeners();
        this.setupKeyboardShortcuts();
        this.updateLastUpdated();
        const autoRefreshSelect = document.getElementById('autoRefreshSelect');
        if (autoRefreshSelect) {
            autoRefreshSelect.value = '60';
            this.setupAutoRefresh(60);
        }
    }

    async loadStats() {
    try {
        const typeParam = this.currentSource ? `&type=${this.currentSource}` : '&type=all';
        const response = await fetch(`../api.php?controller=transaction&action=stats${typeParam}`);
        const data = await response.json();
            
        if (data.success) {
                // Animated counter for stats with real data
                this.animateCounter('todayTransactions', data.data.today_count || 0);
                this.animateCounter('todayRevenue', data.data.today_revenue || 0, true);
                this.animateCounter('monthRevenue', data.data.month_revenue || 0, true);
                this.animateCounter('pendingTransactions', data.data.pending_count || 0);
                this.updateLastUpdated();
        }
    } catch (error) {
        console.error('Failed to load stats:', error);
            showToast('Failed to load statistics', 'error');
        }
    }

    animateCounter(elementId, target, isCurrency = false) {
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
                    element.textContent = this.formatCurrency(target);
                } else {
                    element.textContent = Math.round(target);
                }
                clearInterval(timer);
            } else {
                if (isCurrency) {
                    element.textContent = this.formatCurrency(Math.floor(current));
                } else {
                    element.textContent = Math.floor(current);
                }
            }
        }, 16);
    }

    getSourceBadgeFromTxn(txn) {
        const served = (txn.served_by || '').toLowerCase();
        const notes = (txn.notes || '').toLowerCase();
        const isShop = served === 'system online' || notes.startsWith('order ');
        return isShop 
            ? '<span class="badge" style="background:#3b82f6;">Shop</span>'
            : '<span class="badge" style="background:#10b981;">POS</span>';
    }

    addTrendIndicator(elementId, trend) {
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

    async loadTransactions() {
        try {
            console.log('📡 Loading transactions...');
            const loadingRow = document.getElementById('loadingRow');
            if (loadingRow) {
                loadingRow.style.display = 'table-row';
            }
            
            const typeParam = (this.currentSource && this.currentSource !== 'all') ? `&type=${this.currentSource}` : '';
            const url = `../api.php?controller=transaction&action=list${typeParam}`;
            console.log('🔗 Fetching from:', url);
            
            const response = await fetch(url);
            console.log('📥 Response status:', response.status);
            console.log('📥 Response ok:', response.ok);
            
            // Check content type
            const contentType = response.headers.get('content-type');
            console.log('📋 Content-Type:', contentType);
            
            if (!response.ok) {
                const text = await response.text();
                console.error('❌ HTTP Error:', response.status, text.substring(0, 200));
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            // Try to parse JSON
            let data;
            try {
                const text = await response.text();
                console.log('📄 Raw response (first 500 chars):', text.substring(0, 500));
                data = JSON.parse(text);
            } catch (parseError) {
                console.error('❌ JSON Parse Error:', parseError);
                throw new Error('Invalid JSON response from server');
            }
            
            console.log('📦 Parsed data:', data);
            console.log('📦 Data structure check:', {
                success: data.success,
                hasData: !!data.data,
                hasTransactions: !!(data.data && data.data.transactions),
                transactionsCount: data.data?.transactions?.length || 0
            });
            
            if (data.success) {
                let txns = data.data.transactions || [];
                // Fallback: Source=Shop → load Orders Completed when empty
                if ((this.currentSource === 'shop') && (!txns || txns.length === 0)) {
                    try {
                        const oResp = await fetch('../api.php?controller=order&action=list&status=completed&limit=200');
                        const oText = await oResp.text();
                        const oData = JSON.parse(oText);
                        if (oData.success && oData.data && Array.isArray(oData.data.orders)) {
                            txns = (oData.data.orders || []).map(ord => ({
                                id: ord.id,
                                transaction_number: ord.order_number,
                                customer_name: ord.customer_name || 'Online Customer',
                                items_count: ord.items_count || 0,
                                total_amount: ord.total_amount,
                                payment_method: ord.payment_method,
                                status: 'completed',
                                created_at: ord.created_at,
                                served_by: 'System Online',
                                notes: `Order ${ord.order_number}`
                            }));
                        }
                    } catch (e) {
                        console.error('Fallback load from Orders failed:', e);
                    }
                }

                this.transactions = txns;
                this.filteredTransactions = this.transactions;
                
                console.log(`✅ Loaded ${this.transactions.length} transactions`);
                console.log('📋 First transaction:', this.transactions[0]);
                
                this.renderTransactions();
                this.updateLastUpdated();
            } else {
                console.error('❌ API returned success=false:', data.message);
                throw new Error(data.message || 'Failed to load transactions');
            }
        } catch (error) {
            console.error('❌ loadTransactions ERROR:');
            console.error('  - Type:', error.constructor.name);
            console.error('  - Message:', error.message);
            console.error('  - Stack:', error.stack);
            
            const tbody = document.querySelector('#transactionsTable tbody');
            if (tbody) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 3rem;">
                            <i class="fas fa-exclamation-circle" style="font-size: 3rem; color: #ef4444; margin-bottom: 1rem;"></i>
                            <p style="color: #6b7280; margin-bottom: 0.5rem; font-weight: 600;">Failed to load transactions</p>
                            <p style="color: #9ca3af; font-size: 0.875rem; margin-bottom: 1rem;">${error.message}</p>
                            <button class="btn btn-primary" onclick="transactionManager.loadTransactions()">
                                <i class="fas fa-redo"></i> Retry
                            </button>
                        </td>
                    </tr>
                `;
            }
            
            showToast('Failed to load transactions: ' + error.message, 'error');
        }
    }

    renderTransactions() {
        const tbody = document.querySelector('#transactionsTable tbody');
        
        // Hide loading row if exists
        const loadingRow = document.getElementById('loadingRow');
        if (loadingRow) {
            loadingRow.style.display = 'none';
        }
        
        if (this.filteredTransactions.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="9" style="text-align: center; padding: 3rem;">
                        <i class="fas fa-inbox" style="font-size: 3rem; color: #9ca3af; margin-bottom: 1rem;"></i>
                        <p style="color: #6b7280; font-size: 1rem; font-weight: 600; margin-bottom: 0.5rem;">No transactions found</p>
                        <p style="color: #9ca3af; font-size: 0.875rem;">Try adjusting your filters or check back later</p>
                    </td>
                </tr>
            `;
        return;
    }

        // Pagination
        const startIndex = (this.currentPage - 1) * this.itemsPerPage;
        const endIndex = startIndex + this.itemsPerPage;
        const paginatedTransactions = this.filteredTransactions.slice(startIndex, endIndex);

        tbody.innerHTML = paginatedTransactions.map(txn => `
            <tr style="animation: fadeIn 0.3s ease;">
                <td style="text-align: center;">
                    <input type="checkbox" class="transaction-checkbox" data-id="${txn.id}" onchange="transactionManager.toggleSelection(${txn.id})" ${this.selectedTransactions.has(txn.id) ? 'checked' : ''}>
                </td>
                <td>
                    <span style="font-weight: 600; color: #667eea;">${txn.transaction_number || 'N/A'}</span>
                </td>
                <td style="color: #6b7280; font-size: 0.875rem;">
                    ${this.formatDateTime(txn.created_at)}
                </td>
                <td>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-user-circle" style="color: #9ca3af;"></i>
                        <span>${txn.customer_name || 'Walk-in'}</span>
                    </div>
                </td>
                <td style="text-align: center;">
                    <span style="background: #f3f4f6; padding: 0.25rem 0.75rem; border-radius: 12px; font-weight: 600;">
                        ${txn.items_count || 0}
                    </span>
                </td>
                <td style="text-align: right; font-weight: 700; color: #059669;">
                    ${this.formatCurrency(txn.total_amount)}
                </td>
                <td style="text-align: center;">
                    <div style="display:flex; align-items:center; justify-content:center; gap:.5rem;">
                        ${this.getPaymentIcon(txn.payment_method)}
                        ${this.getSourceBadgeFromTxn(txn)}
                    </div>
                </td>
                <td style="text-align: center;">
                    ${this.getStatusBadge(txn.status)}
                </td>
                <td style="text-align: center;">
                    <div class="table-actions-dropdown" id="dropdown-${txn.id}">
                        <button class="table-actions-btn" ${!txn.id ? 'disabled' : ''} onclick="${!txn.id ? '' : `transactionManager.toggleActionsMenu(${txn.id})`}">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <div class="table-actions-menu" id="menu-${txn.id}">
                            ${!txn.id ? `
                                <div style="padding:0.5rem 0.75rem; color:#6b7280;">
                                    <i class="fas fa-info-circle"></i> Order Shop belum tersinkron.
                                </div>
                            ` : `
                                <button class="table-actions-menu-item info" onclick="transactionManager.viewTransaction(${txn.id}); transactionManager.closeActionsMenu(${txn.id})">
                                    <i class="fas fa-eye"></i>
                                    <span>View Details</span>
                                </button>
                                <button class="table-actions-menu-item" onclick="transactionManager.viewReceipt(${txn.id}); transactionManager.closeActionsMenu(${txn.id})">
                                    <i class="fas fa-receipt"></i>
                                    <span>View Receipt</span>
                                </button>
                                <button class="table-actions-menu-item" onclick="transactionManager.printReceipt(${txn.id}); transactionManager.closeActionsMenu(${txn.id})">
                                    <i class="fas fa-print"></i>
                                    <span>Print Receipt</span>
                                </button>
                                ${txn.status === 'completed' ? `
                                    <div style="border-top: 1px solid #e5e7eb; margin: 0.5rem 0;"></div>
                                    <button class="table-actions-menu-item warning" onclick="transactionManager.showRefundModal(${txn.id}); transactionManager.closeActionsMenu(${txn.id})">
                                        <i class="fas fa-undo"></i>
                                        <span>Refund</span>
                                    </button>
                                    <button class="table-actions-menu-item danger" onclick="transactionManager.showCancelModal(${txn.id}); transactionManager.closeActionsMenu(${txn.id})">
                                        <i class="fas fa-times-circle"></i>
                                        <span>Cancel</span>
                                    </button>
                                ` : ''}
                            `}
                        </div>
                    </div>
            </td>
        </tr>
    `).join('');

        this.renderPagination();
        this.renderMobileCards(paginatedTransactions);
    }

    renderPagination() {
        const totalPages = Math.ceil(this.filteredTransactions.length / this.itemsPerPage);
        const pagination = document.getElementById('pagination');
        
        if (totalPages <= 1) {
            pagination.innerHTML = '';
            return;
        }

        const startItem = ((this.currentPage - 1) * this.itemsPerPage) + 1;
        const endItem = Math.min(this.currentPage * this.itemsPerPage, this.filteredTransactions.length);

        let html = `
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div style="color: #6b7280; font-size: 0.875rem;">
                    Showing <strong>${startItem}-${endItem}</strong> of <strong>${this.filteredTransactions.length}</strong> transactions
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <button onclick="transactionManager.changePage(1)" ${this.currentPage === 1 ? 'disabled' : ''} class="btn btn-sm btn-secondary">
                        <i class="fas fa-angle-double-left"></i>
                    </button>
                    <button onclick="transactionManager.changePage(${this.currentPage - 1})" ${this.currentPage === 1 ? 'disabled' : ''} class="btn btn-sm btn-secondary">
                        <i class="fas fa-angle-left"></i>
                    </button>
        `;

        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= this.currentPage - 1 && i <= this.currentPage + 1)) {
                html += `
                    <button onclick="transactionManager.changePage(${i})" class="btn btn-sm ${i === this.currentPage ? 'btn-primary' : 'btn-secondary'}">
                        ${i}
                    </button>
                `;
            } else if (i === this.currentPage - 2 || i === this.currentPage + 2) {
                html += '<span style="color: #9ca3af;">...</span>';
            }
        }

        html += `
                    <button onclick="transactionManager.changePage(${this.currentPage + 1})" ${this.currentPage === totalPages ? 'disabled' : ''} class="btn btn-sm btn-secondary">
                        <i class="fas fa-angle-right"></i>
                    </button>
                    <button onclick="transactionManager.changePage(${totalPages})" ${this.currentPage === totalPages ? 'disabled' : ''} class="btn btn-sm btn-secondary">
                        <i class="fas fa-angle-double-right"></i>
                    </button>
                </div>
            </div>
        `;

        pagination.innerHTML = html;
    }

    renderMobileCards(transactions) {
        const container = document.getElementById('mobileCardsContainer');
        if (window.innerWidth > 768) {
            container.style.display = 'none';
            return;
        }

        const tableView = document.getElementById('tableView');
        if (tableView) tableView.style.display = 'none';
        container.style.display = 'block';

        container.innerHTML = transactions.map(txn => `
            <div class="customer-mobile-card" style="margin-bottom: 1rem; animation: fadeIn 0.3s ease;">
                <div class="customer-mobile-card-header">
                    <div class="customer-mobile-card-title">
                        <i class="fas fa-receipt" style="color: var(--primary-color);"></i>
                        ${txn.transaction_number}
                    </div>
                    ${this.getStatusBadge(txn.status)}
                </div>
                <div class="customer-mobile-card-body">
                    <div class="customer-mobile-card-row">
                        <span class="customer-mobile-card-label"><i class="fas fa-calendar"></i> Date</span>
                        <span class="customer-mobile-card-value">${this.formatDateTime(txn.created_at)}</span>
                    </div>
                    <div class="customer-mobile-card-row">
                        <span class="customer-mobile-card-label"><i class="fas fa-user"></i> Customer</span>
                        <span class="customer-mobile-card-value">${txn.customer_name || 'Walk-in'}</span>
                    </div>
                    <div class="customer-mobile-card-row">
                        <span class="customer-mobile-card-label"><i class="fas fa-shopping-cart"></i> Items</span>
                        <span class="customer-mobile-card-value">${txn.items_count || 0}</span>
                    </div>
                    <div class="customer-mobile-card-row">
                        <span class="customer-mobile-card-label"><i class="fas fa-money-bill"></i> Total</span>
                        <span class="customer-mobile-card-value" style="font-weight: 700; color: #059669;">${this.formatCurrency(txn.total_amount)}</span>
                    </div>
                    <div class="customer-mobile-card-row">
                        <span class="customer-mobile-card-label"><i class="fas fa-credit-card"></i> Payment</span>
                        <span class="customer-mobile-card-value" style="display:flex; align-items:center; gap:.5rem;">${this.getPaymentIcon(txn.payment_method)} ${this.getSourceBadgeFromTxn(txn)}</span>
                    </div>
                </div>
                <div class="customer-mobile-card-actions">
                    <button class="btn btn-sm btn-primary" onclick="transactionManager.viewTransaction(${txn.id})">
                        <i class="fas fa-eye"></i> View
                    </button>
                    <button class="btn btn-sm btn-secondary" onclick="transactionManager.printReceipt(${txn.id})">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
            </div>
        `).join('');
    }

    filterTransactions() {
        let filtered = this.transactions;

        // Search filter
        const searchInput = document.getElementById('searchInput');
        const searchQuery = searchInput ? searchInput.value.toLowerCase() : '';
        if (searchQuery) {
            filtered = filtered.filter(txn =>
                txn.transaction_number.toLowerCase().includes(searchQuery) ||
                (txn.customer_name && txn.customer_name.toLowerCase().includes(searchQuery))
            );
        }

        // Status filter
        const statusFilterEl = document.getElementById('statusFilter');
        const statusFilter = statusFilterEl ? statusFilterEl.value : '';
        if (statusFilter) {
            filtered = filtered.filter(txn => txn.status === statusFilter);
        }

        // Payment filter
        const paymentFilterEl = document.getElementById('paymentFilter');
        const paymentFilter = paymentFilterEl ? paymentFilterEl.value : '';
        if (paymentFilter) {
            filtered = filtered.filter(txn => txn.payment_method === paymentFilter);
        }

        // Source filter handled on server via ?type= parameter

        // Date range filter
        const dateRangeEl = document.getElementById('dateRangeQuick');
        const dateRange = dateRangeEl ? dateRangeEl.value : '';
        if (dateRange && dateRange !== 'custom') {
            const now = new Date();
            filtered = filtered.filter(txn => {
                const txnDate = new Date(txn.created_at);
                switch(dateRange) {
                    case 'today':
                        return txnDate.toDateString() === now.toDateString();
                    case 'yesterday':
                        const yesterday = new Date(now);
                        yesterday.setDate(yesterday.getDate() - 1);
                        return txnDate.toDateString() === yesterday.toDateString();
                    case 'week':
                        const weekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
                        return txnDate >= weekAgo;
                    case 'month':
                        const monthAgo = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000);
                        return txnDate >= monthAgo;
                }
                return true;
            });
        }

        // Custom date range
        if (dateRange === 'custom') {
            const dateFromEl = document.getElementById('dateFrom');
            const dateToEl = document.getElementById('dateTo');
            const dateFrom = dateFromEl ? dateFromEl.value : '';
            const dateTo = dateToEl ? dateToEl.value : '';
            
            if (dateFrom && dateTo) {
                filtered = filtered.filter(txn => {
                    const txnDate = new Date(txn.created_at).toISOString().split('T')[0];
                    return txnDate >= dateFrom && txnDate <= dateTo;
                });
            }
        }

        this.filteredTransactions = filtered;
        this.currentPage = 1;
        this.updateActiveFiltersCount();
        this.renderTransactions();
    }

    updateActiveFiltersCount() {
        let count = 0;
        const searchInput = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');
        const paymentFilter = document.getElementById('paymentFilter');
        const dateRangeQuick = document.getElementById('dateRangeQuick');
        
        if (searchInput && searchInput.value) count++;
        if (statusFilter && statusFilter.value) count++;
        if (paymentFilter && paymentFilter.value) count++;
        if (dateRangeQuick && dateRangeQuick.value) count++;
        if (this.currentSource && this.currentSource !== 'all') count++;
        
        const countBadge = document.getElementById('activeFiltersCount');
        const countSpan = document.getElementById('filterCount');
        
        if (countBadge && countSpan) {
            if (count > 0) {
                countBadge.style.display = 'flex';
                countBadge.style.alignItems = 'center';
                countBadge.style.gap = '0.5rem';
                countSpan.textContent = count;
            } else {
                countBadge.style.display = 'none';
            }
        }
    }

    clearFilters() {
        const searchInput = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');
        const paymentFilter = document.getElementById('paymentFilter');
        const dateRangeQuick = document.getElementById('dateRangeQuick');
        const dateFrom = document.getElementById('dateFrom');
        const dateTo = document.getElementById('dateTo');
        const customDateRange = document.getElementById('customDateRange');
        
        if (searchInput) searchInput.value = '';
        if (statusFilter) statusFilter.value = '';
        if (paymentFilter) paymentFilter.value = '';
        if (dateRangeQuick) dateRangeQuick.value = '';
        if (dateFrom) dateFrom.value = '';
        if (dateTo) dateTo.value = '';
        if (customDateRange) customDateRange.style.display = 'none';
        this.currentSource = 'all';
        ['tabSourceAll','tabSourcePos','tabSourceShop'].forEach(id => document.getElementById(id)?.classList.remove('active'));
        document.getElementById('tabSourceAll')?.classList.add('active');
        
        this.filteredTransactions = this.transactions;
        this.currentPage = 1;
        this.updateActiveFiltersCount();
        this.renderTransactions();
        showToast('Filters cleared', 'success');
    }

    changePage(page) {
        const totalPages = Math.ceil(this.filteredTransactions.length / this.itemsPerPage);
        if (page < 1 || page > totalPages) return;
        
        this.currentPage = page;
        this.renderTransactions();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    toggleSelection(id) {
        if (this.selectedTransactions.has(id)) {
            this.selectedTransactions.delete(id);
        } else {
            this.selectedTransactions.add(id);
        }
        this.updateBulkActions();
    }

    updateBulkActions() {
        const bulkContainer = document.getElementById('bulkActionsContainer');
        const selectedCount = document.getElementById('selectedCount');
        const selectAll = document.getElementById('selectAll');
        
        if (bulkContainer && selectedCount && selectAll) {
            if (this.selectedTransactions.size > 0) {
                bulkContainer.style.display = 'block';
                selectedCount.textContent = this.selectedTransactions.size;
            } else {
                bulkContainer.style.display = 'none';
            }

            // Update select all checkbox
            const allVisible = Array.from(document.querySelectorAll('.transaction-checkbox')).map(cb => parseInt(cb.dataset.id));
            const allSelected = allVisible.every(id => this.selectedTransactions.has(id));
            selectAll.checked = allSelected && allVisible.length > 0;
        }
    }

    toggleActionsMenu(id) {
        const menu = document.getElementById(`menu-${id}`);
        const allMenus = document.querySelectorAll('.table-actions-menu');
        
        allMenus.forEach(m => {
            if (m.id !== `menu-${id}`) m.classList.remove('show');
        });
        
        menu.classList.toggle('show');
        
        setTimeout(() => {
            const closeOnClickOutside = (e) => {
                if (!e.target.closest(`#dropdown-${id}`)) {
                    menu.classList.remove('show');
                    document.removeEventListener('click', closeOnClickOutside);
                }
            };
            document.addEventListener('click', closeOnClickOutside);
        }, 10);
    }

    closeActionsMenu(id) {
        const menu = document.getElementById(`menu-${id}`);
        if (menu) menu.classList.remove('show');
    }

    toggleExportMenu() {
        const menu = document.getElementById('exportMenu');
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

    async viewTransaction(id) {
        // Fetch full transaction details with items from API
        try {
            const response = await app.apiCall(`../api.php?controller=transaction&action=get&id=${id}`);
            if (!response.success) {
                throw new Error(response.error || 'Failed to load transaction');
            }
            
            const transaction = response.data;
            this.renderTransactionModal(transaction);
            
        } catch (error) {
            console.error('Error loading transaction:', error);
            app.showToast('Failed to load transaction details', 'error');
        }
    }
    
    renderTransactionModal(transaction) {
        // Enhanced detail modal content
        const detailsHtml = `
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <!-- Left Column: Transaction Info -->
                <div>
                    <h4 style="margin: 0 0 1rem 0; color: #667eea; font-size: 1rem; font-weight: 600; border-bottom: 2px solid #e5e7eb; padding-bottom: 0.5rem;">
                        <i class="fas fa-info-circle"></i> Transaction Information
                    </h4>
                    <div style="display: grid; gap: 0.75rem;">
                        <div>
                            <label style="font-size: 0.75rem; color: #9ca3af; text-transform: uppercase; font-weight: 600;">Transaction #</label>
                            <div style="font-weight: 600; color: #667eea; font-size: 1.125rem;">${transaction.transaction_number}</div>
                        </div>
                        <div>
                            <label style="font-size: 0.75rem; color: #9ca3af; text-transform: uppercase; font-weight: 600;">Date & Time</label>
                            <div style="color: #374151;">${this.formatDateTime(transaction.created_at)}</div>
                        </div>
                        <div>
                            <label style="font-size: 0.75rem; color: #9ca3af; text-transform: uppercase; font-weight: 600;">Status</label>
                            <div>${this.getStatusBadge(transaction.status)}</div>
                        </div>
                        <div>
                            <label style="font-size: 0.75rem; color: #9ca3af; text-transform: uppercase; font-weight: 600;">Payment Method</label>
                            <div>${this.getPaymentIcon(transaction.payment_method)}</div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Customer Info -->
                <div>
                    <h4 style="margin: 0 0 1rem 0; color: #10b981; font-size: 1rem; font-weight: 600; border-bottom: 2px solid #e5e7eb; padding-bottom: 0.5rem;">
                        <i class="fas fa-user"></i> Customer Information
                    </h4>
                    <div style="display: grid; gap: 0.75rem;">
                        <div>
                            <label style="font-size: 0.75rem; color: #9ca3af; text-transform: uppercase; font-weight: 600;">Name</label>
                            <div style="color: #374151; font-weight: 600;">${transaction.customer_name || 'Walk-in Customer'}</div>
                        </div>
                        <div>
                            <label style="font-size: 0.75rem; color: #9ca3af; text-transform: uppercase; font-weight: 600;">Phone</label>
                            <div style="color: #374151;">${transaction.customer_phone || '-'}</div>
                        </div>
                        <div>
                            <label style="font-size: 0.75rem; color: #9ca3af; text-transform: uppercase; font-weight: 600;">Email</label>
                            <div style="color: #374151;">${transaction.customer_email || '-'}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items List -->
            <div style="margin-top: 2rem;">
                <h4 style="margin: 0 0 1rem 0; color: #f59e0b; font-size: 1rem; font-weight: 600; border-bottom: 2px solid #e5e7eb; padding-bottom: 0.5rem;">
                    <i class="fas fa-shopping-cart"></i> Items (${transaction.items ? transaction.items.length : 0})
                </h4>
                <div style="background: #f9fafb; padding: 1rem; border-radius: 8px;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 1px solid #e5e7eb;">
                                <th style="text-align: left; padding: 0.5rem; font-size: 0.75rem; color: #6b7280;">ITEM</th>
                                <th style="text-align: center; padding: 0.5rem; font-size: 0.75rem; color: #6b7280;">QTY</th>
                                <th style="text-align: right; padding: 0.5rem; font-size: 0.75rem; color: #6b7280;">PRICE</th>
                                <th style="text-align: right; padding: 0.5rem; font-size: 0.75rem; color: #6b7280;">SUBTOTAL</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${transaction.items && transaction.items.length > 0 ? transaction.items.map(item => `
                                <tr>
                                    <td style="padding: 0.5rem;">${item.product_name || 'Unknown Product'}</td>
                                    <td style="text-align: center; padding: 0.5rem;">${item.quantity}</td>
                                    <td style="text-align: right; padding: 0.5rem;">${this.formatCurrency(item.unit_price)}</td>
                                    <td style="text-align: right; padding: 0.5rem; font-weight: 600;">${this.formatCurrency(item.total_price)}</td>
                                </tr>
                            `).join('') : `
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 1rem; color: #9ca3af;">No items found</td>
                                </tr>
                            `}
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Payment Summary -->
            <div style="margin-top: 2rem; background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); padding: 1.5rem; border-radius: 8px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span style="color: #6b7280;">Subtotal</span>
                    <span style="font-weight: 600;">${this.formatCurrency(transaction.subtotal || 0)}</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span style="color: #6b7280;">Tax</span>
                    <span style="font-weight: 600;">${this.formatCurrency(transaction.tax_amount || 0)}</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span style="color: #6b7280;">Discount</span>
                    <span style="font-weight: 600; color: #ef4444;">-${this.formatCurrency(transaction.discount_amount || 0)}</span>
                </div>
                <div style="border-top: 2px solid #9ca3af; margin-top: 0.75rem; padding-top: 0.75rem; display: flex; justify-content: space-between;">
                    <span style="font-size: 1.125rem; font-weight: 700; color: #111827;">TOTAL</span>
                    <span style="font-size: 1.25rem; font-weight: 700; color: #059669;">${this.formatCurrency(transaction.total_amount)}</span>
                </div>
            </div>
        `;

        document.getElementById('transactionDetails').innerHTML = detailsHtml;
        this.showModal('transactionModal');

        // Setup print button
        document.getElementById('printReceiptBtn').onclick = () => this.printReceipt(transaction.id);
        document.getElementById('viewReceiptBtn').onclick = () => this.viewReceipt(transaction.id);
    }

    viewReceipt(id) {
        window.open(`receipt.php?id=${id}`, '_blank', 'width=400,height=600');
    }

    printReceipt(id) {
        window.open(`receipt.php?id=${id}&print=1`, '_blank');
        showToast('Opening receipt for printing...', 'info');
    }

    showCancelModal(id) {
        const transaction = this.transactions.find(t => t.id === id);
        if (!transaction) return;

        // Populate transaction info
        document.getElementById('cancelTransactionInfo').innerHTML = `
            <div style="display: grid; gap: 0.5rem;">
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: #6b7280;">Transaction #:</span>
                    <strong>${transaction.transaction_number}</strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: #6b7280;">Amount:</span>
                    <strong>${this.formatCurrency(transaction.total_amount)}</strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: #6b7280;">Customer:</span>
                    <strong>${transaction.customer_name || 'Walk-in Customer'}</strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: #6b7280;">Date:</span>
                    <strong>${this.formatDateTime(transaction.created_at)}</strong>
                </div>
            </div>
        `;

        // Store transaction ID for confirmation
        this.selectedTransactionId = id;
        
        // Clear previous reason
        document.getElementById('cancelReason').value = '';
        
        // Show modal
        this.showModal('cancelModal');
    }

    async confirmCancel() {
        const reason = document.getElementById('cancelReason').value.trim();
        
        if (!reason) {
            showToast('Please enter a reason for cancellation', 'error');
            return;
        }

        const confirmBtn = document.getElementById('confirmCancelBtn');
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cancelling...';

        try {
            const response = await app.apiCall('../api.php?controller=transaction&action=cancel', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    transaction_id: this.selectedTransactionId,
                    reason: reason
                })
            });

            if (response.success) {
                showToast('Transaction cancelled successfully', 'success');
                this.closeModal('cancelModal');
                await this.loadTransactions();
                await this.loadStats();
            } else {
                throw new Error(response.error || 'Failed to cancel transaction');
            }
        } catch (error) {
            console.error('Cancel error:', error);
            showToast(error.message || 'Failed to cancel transaction', 'error');
        } finally {
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = '<i class="fas fa-times-circle"></i> Confirm Cancellation';
        }
    }

    showRefundModal(id) {
        const transaction = this.transactions.find(t => t.id === id);
        if (!transaction) return;

        // Populate transaction info
        document.getElementById('refundTransactionInfo').innerHTML = `
            <div style="display: grid; gap: 0.5rem;">
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: #6b7280;">Transaction #:</span>
                    <strong>${transaction.transaction_number}</strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: #6b7280;">Total Amount:</span>
                    <strong>${this.formatCurrency(transaction.total_amount)}</strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: #6b7280;">Customer:</span>
                    <strong>${transaction.customer_name || 'Walk-in Customer'}</strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: #6b7280;">Date:</span>
                    <strong>${this.formatDateTime(transaction.created_at)}</strong>
                </div>
            </div>
        `;

        // Set max refund amount
        document.getElementById('refundAmount').max = transaction.total_amount;
        document.getElementById('refundAmount').value = transaction.total_amount;
        document.getElementById('refundAmountHelp').textContent = `Maximum: ${this.formatCurrency(transaction.total_amount)}`;

        // Store transaction for confirmation
        this.selectedTransactionId = id;
        this.selectedTransaction = transaction;
        
        // Clear previous reason
        document.getElementById('refundReason').value = '';
        
        // Show modal
        this.showModal('refundModal');
    }

    async confirmRefund() {
        const refundAmount = parseFloat(document.getElementById('refundAmount').value);
        const reason = document.getElementById('refundReason').value.trim();
        
        if (!refundAmount || refundAmount <= 0) {
            showToast('Please enter a valid refund amount', 'error');
            return;
        }

        if (refundAmount > this.selectedTransaction.total_amount) {
            showToast('Refund amount cannot exceed transaction total', 'error');
            return;
        }

        if (!reason) {
            showToast('Please enter a reason for refund', 'error');
            return;
        }

        const confirmBtn = document.getElementById('confirmRefundBtn');
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

        try {
            const response = await app.apiCall('../api.php?controller=transaction&action=refund', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    transaction_id: this.selectedTransactionId,
                    refund_amount: refundAmount,
                    reason: reason
                })
            });

            if (response.success) {
                showToast('Transaction refunded successfully', 'success');
                this.closeModal('refundModal');
                await this.loadTransactions();
                await this.loadStats();
            } else {
                throw new Error(response.error || 'Failed to refund transaction');
            }
        } catch (error) {
            console.error('Refund error:', error);
            showToast(error.message || 'Failed to refund transaction', 'error');
        } finally {
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = '<i class="fas fa-undo"></i> Confirm Refund';
        }
    }

    exportData(format) {
        document.getElementById('exportMenu').classList.remove('show');
        
        const formatIcons = {
            csv: 'fa-file-csv',
            excel: 'fa-file-excel',
            pdf: 'fa-file-pdf'
        };
        
        showToast(`Exporting ${this.filteredTransactions.length} transactions as ${format.toUpperCase()}...`, 'info');
        
        // In production, call API endpoint
        setTimeout(() => {
            window.location.href = `../api.php?controller=transaction&action=export&format=${format}`;
        }, 500);
    }

    toggleView(view) {
        const tableView = document.getElementById('tableView');
        const chartView = document.getElementById('chartView');
        const btns = document.querySelectorAll('.view-toggle-btn');
        
        btns.forEach(btn => {
            if (btn.dataset.view === view) {
                btn.classList.add('active');
                btn.style.background = '#16a34a';
                btn.style.color = 'white';
            } else {
                btn.classList.remove('active');
                btn.style.background = 'transparent';
                btn.style.color = '#6b7280';
            }
        });

        if (view === 'chart') {
            tableView.style.display = 'none';
            chartView.style.display = 'block';
            this.renderRevenueChart();
        } else {
            tableView.style.display = 'block';
            chartView.style.display = 'none';
        }
    }

    renderRevenueChart() {
        const ctx = document.getElementById('revenueChart');
        if (!ctx) return;

        // Destroy existing chart
        if (this.revenueChart) {
            this.revenueChart.destroy();
        }

        // Mock data - calculate from transactions in production
        const last7Days = [];
        const revenues = [];
        
        for (let i = 6; i >= 0; i--) {
            const date = new Date();
            date.setDate(date.getDate() - i);
            last7Days.push(date.toLocaleDateString('id-ID', { month: 'short', day: 'numeric' }));
            revenues.push(Math.floor(Math.random() * 5000000) + 1000000);
        }

        this.revenueChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: last7Days,
                datasets: [{
                    label: 'Daily Revenue',
                    data: revenues,
                    borderColor: '#16a34a',
                    backgroundColor: 'rgba(22, 163, 74, 0.15)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                return 'Revenue: ' + this.formatCurrency(context.raw);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        ticks: {
                            callback: (value) => {
                                return 'Rp ' + (value / 1000) + 'K';
                            }
                        }
                    }
                }
            }
        });
    }

    applyCustomDateRange() {
        const dateFromEl = document.getElementById('dateFrom');
        const dateToEl = document.getElementById('dateTo');
        const dateFrom = dateFromEl ? dateFromEl.value : '';
        const dateTo = dateToEl ? dateToEl.value : '';
        
        if (!dateFrom || !dateTo) {
            showToast('Please select both start and end dates', 'warning');
            return;
        }

        if (dateFrom > dateTo) {
            showToast('Start date cannot be after end date', 'error');
            return;
        }

        this.filterTransactions();
        showToast(`Showing transactions from ${dateFrom} to ${dateTo}`, 'success');
    }

    setupAutoRefresh(interval) {
        // Clear existing interval
        if (this.autoRefreshInterval) {
            clearInterval(this.autoRefreshInterval);
            this.autoRefreshInterval = null;
        }

        if (interval > 0) {
            this.autoRefreshInterval = setInterval(() => {
                this.loadTransactions();
                this.loadStats();
            }, interval * 1000);
            showToast(`Auto-refresh enabled: every ${interval}s`, 'success');
        }
    }

    updateLastUpdated() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        const lastUpdated = document.getElementById('lastUpdated');
        if (lastUpdated) {
            lastUpdated.textContent = `Last updated: ${timeString}`;
        }
    }

    setupEventListeners() {
        // Refresh button
        const refreshBtn = document.getElementById('refreshBtn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                console.log('🔄 Refresh button clicked!');
                this.loadTransactions();
                this.loadStats();
                showToast('Data refreshed', 'success');
            });
            console.log('✅ Refresh button event listener attached');
        } else {
            console.error('❌ Refresh button not found!');
        }

        // Search input
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', () => {
                this.filterTransactions();
            });
        }

        // Filter dropdowns
        ['statusFilter', 'paymentFilter', 'dateRangeQuick'].forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.addEventListener('change', () => {
                    const customDateRange = document.getElementById('customDateRange');
                    if (id === 'dateRangeQuick' && element.value === 'custom') {
                        if (customDateRange) customDateRange.style.display = 'block';
                    } else if (id === 'dateRangeQuick') {
                        if (customDateRange) customDateRange.style.display = 'none';
                    }
                    this.filterTransactions();
                });
            }
        });

        // Source tabs
        const sourceButtons = [
            { id: 'tabSourceAll', value: 'all' },
            { id: 'tabSourcePos', value: 'pos' },
            { id: 'tabSourceShop', value: 'shop' },
        ];
        sourceButtons.forEach(tab => {
            const el = document.getElementById(tab.id);
            if (el) {
                el.addEventListener('click', () => {
                    this.currentSource = tab.value;
                    sourceButtons.forEach(t => document.getElementById(t.id)?.classList.remove('active'));
                    el.classList.add('active');
                    // Reload from server with ?type= to ensure accurate dataset
                    this.loadTransactions();
                    // Refresh cards in real-time
                    this.loadStats();
                });
            }
        });

        // View toggle buttons
        document.querySelectorAll('.view-toggle-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                this.toggleView(btn.dataset.view);
            });
        });

        // Auto refresh
        const autoRefreshSelect = document.getElementById('autoRefreshSelect');
        if (autoRefreshSelect) {
            autoRefreshSelect.addEventListener('change', (e) => {
                console.log('🔄 Auto-refresh changed to:', e.target.value);
                this.setupAutoRefresh(parseInt(e.target.value));
            });
            console.log('✅ Auto-refresh event listener attached');
        } else {
            console.error('❌ Auto-refresh select not found!');
        }

        // Select all checkbox
        const selectAll = document.getElementById('selectAll');
        if (selectAll) {
            selectAll.addEventListener('change', (e) => {
                const checkboxes = document.querySelectorAll('.transaction-checkbox');
                checkboxes.forEach(cb => {
                    const id = parseInt(cb.dataset.id);
                    if (e.target.checked) {
                        this.selectedTransactions.add(id);
                        cb.checked = true;
                    } else {
                        this.selectedTransactions.delete(id);
                        cb.checked = false;
                    }
                });
                this.updateBulkActions();
            });
        }

        // Bulk print button
        const bulkPrintBtn = document.getElementById('bulkPrintBtn');
        if (bulkPrintBtn) {
            bulkPrintBtn.addEventListener('click', () => {
                if (this.selectedTransactions.size === 0) return;
                
                const ids = Array.from(this.selectedTransactions).join(',');
                window.open(`receipt.php?ids=${ids}&bulk=1`, '_blank');
                showToast(`Printing ${this.selectedTransactions.size} receipts...`, 'info');
            });
        }

        // Responsive listener
        window.addEventListener('resize', () => {
            this.renderTransactions();
        });
    }

    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl+F: Focus search
            if (e.ctrlKey && e.key === 'f') {
                e.preventDefault();
                const searchInput = document.getElementById('searchInput');
                if (searchInput) searchInput.focus();
            }

            // F5: Refresh
            if (e.key === 'F5') {
                e.preventDefault();
                this.loadTransactions();
                this.loadStats();
            }

            // Ctrl+E: Export
            if (e.ctrlKey && e.key === 'e') {
                e.preventDefault();
                this.toggleExportMenu();
            }

            // Ctrl+T: Toggle view
            if (e.ctrlKey && e.key === 't') {
                e.preventDefault();
                const currentView = document.querySelector('.view-toggle-btn.active').dataset.view;
                this.toggleView(currentView === 'table' ? 'chart' : 'table');
            }

            // ESC: Close modals
            if (e.key === 'Escape') {
                this.closeAllModals();
            }
        });
    }

    showModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('show');
            
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.closeModal(modalId);
                }
            });
        }
    }

    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('closing');
            setTimeout(() => {
                modal.classList.remove('show', 'closing');
            }, 200);
        }
    }

    closeAllModals() {
        document.querySelectorAll('.modal.show').forEach(modal => {
            modal.classList.add('closing');
            setTimeout(() => {
                modal.classList.remove('show', 'closing');
            }, 200);
        });
    }

    formatCurrency(amount) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount || 0);
    }

    formatDateTime(datetime) {
        if (!datetime) return '-';
    return new Date(datetime).toLocaleDateString('id-ID', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    getPaymentIcon(method) {
        const icons = {
            'cash': '<span style="display: inline-flex; align-items: center; gap: 0.5rem; background: #dcfce7; color: #059669; padding: 0.375rem 0.75rem; border-radius: 20px; font-size: 0.875rem; font-weight: 600;"><span>💵</span> Cash</span>',
            'card': '<span style="display: inline-flex; align-items: center; gap: 0.5rem; background: #dbeafe; color: #2563eb; padding: 0.375rem 0.75rem; border-radius: 20px; font-size: 0.875rem; font-weight: 600;"><span>💳</span> Card</span>',
            'qris': '<span style="display: inline-flex; align-items: center; gap: 0.5rem; background: #f3e8ff; color: #9333ea; padding: 0.375rem 0.75rem; border-radius: 20px; font-size: 0.875rem; font-weight: 600;"><span>📱</span> QRIS</span>',
            'transfer': '<span style="display: inline-flex; align-items: center; gap: 0.5rem; background: #fed7aa; color: #ea580c; padding: 0.375rem 0.75rem; border-radius: 20px; font-size: 0.875rem; font-weight: 600;"><span>🏦</span> Transfer</span>'
        };
        return icons[method] || method;
    }

    getStatusBadge(status) {
    const badges = {
            'completed': '<span class="status-badge" style="display: inline-flex; align-items: center; gap: 0.375rem; background: #d1fae5; color: #065f46; padding: 0.375rem 0.75rem; border-radius: 20px; font-size: 0.875rem; font-weight: 600;"><i class="fas fa-check-circle"></i> Completed</span>',
            'pending': '<span class="status-badge" style="display: inline-flex; align-items: center; gap: 0.375rem; background: #dbeafe; color: #1e40af; padding: 0.375rem 0.75rem; border-radius: 20px; font-size: 0.875rem; font-weight: 600;"><i class="fas fa-clock"></i> Pending</span>',
            'cancelled': '<span class="status-badge" style="display: inline-flex; align-items: center; gap: 0.375rem; background: #fee2e2; color: #991b1b; padding: 0.375rem 0.75rem; border-radius: 20px; font-size: 0.875rem; font-weight: 600;"><i class="fas fa-times-circle"></i> Cancelled</span>',
            'refunded': '<span class="status-badge" style="display: inline-flex; align-items: center; gap: 0.375rem; background: #fef3c7; color: #92400e; padding: 0.375rem 0.75rem; border-radius: 20px; font-size: 0.875rem; font-weight: 600;"><i class="fas fa-undo"></i> Refunded</span>'
    };
    return badges[status] || `<span class="status-badge" style="display: inline-flex; align-items: center; gap: 0.375rem; background: #f3f4f6; color: #374151; padding: 0.375rem 0.75rem; border-radius: 20px; font-size: 0.875rem; font-weight: 600;">${status}</span>`;
}
}

// Initialize
const transactionManager = new TransactionManager();
document.addEventListener('DOMContentLoaded', () => {
    transactionManager.init();
});
</script>

<?php require_once 'includes/footer.php'; ?>
