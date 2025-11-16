/**
 * Bytebalok System - System specific JavaScript
 */

class SystemManager {
    constructor() {
        this.salesChart = null;
        this.init();
    }

    async init() {
        await this.loadSystemData();
        this.initializeCharts();
        this.setupEventListeners();
    }

    async loadSystemData() {
        try {
            // Load overview stats
            await this.loadOverviewStats();
            
            // Load sales chart data
            await this.loadSalesChart();
            
            // Load top products
            await this.loadTopProducts();
            
            // Load recent transactions
            await this.loadRecentTransactions();
            
            // Load low stock products
            await this.loadLowStockProducts();
            
        } catch (error) {
            console.error('Failed to load dashboard data:', error);
            app.showToast('Failed to load dashboard data', 'error');
        }
    }

    async loadOverviewStats() {
        try {
            const response = await app.apiCall('../api.php?controller=dashboard&action=stats');
            
            if (response.success) {
                this.renderStatsCards(response.data || {});
            }
        } catch (error) {
            console.error('Failed to load overview stats:', error);
        }
    }

    renderStatsCards(data) {
        const statsGrid = document.getElementById('statsGrid');
        
        // API mengembalikan struktur flat: today_sales, total_customers, low_stock_count, sales_change
        const stats = [
            {
                title: 'Today Sales',
                value: app.formatCurrency((data.today_sales != null ? data.today_sales : 0)),
                change: null,
                icon: 'fas fa-dollar-sign',
                color: 'success'
            },
            {
                title: 'Sales Change',
                value: app.formatNumber(0),
                change: (data.sales_change != null ? data.sales_change : null),
                icon: 'fas fa-chart-line',
                color: 'primary'
            },
            {
                title: 'Active Customers',
                value: app.formatNumber((data.total_customers != null ? data.total_customers : 0)),
                change: null,
                icon: 'fas fa-users',
                color: 'warning'
            },
            {
                title: 'Low Stock Items',
                value: app.formatNumber((data.low_stock_count != null ? data.low_stock_count : 0)),
                change: null,
                icon: 'fas fa-exclamation-triangle',
                color: 'error'
            }
        ];

        statsGrid.innerHTML = stats.map(stat => `
            <div class="stat-card">
                <div class="stat-header">
                    <h4 class="stat-title">${stat.title}</h4>
                    <div class="stat-icon" style="background: var(--${stat.color}-color);">
                        <i class="${stat.icon}"></i>
                    </div>
                </div>
                <h2 class="stat-value">${stat.value}</h2>
                ${stat.change !== null ? `
                    <div class="stat-change ${stat.change >= 0 ? 'positive' : 'negative'}">
                        <i class="fas fa-arrow-${stat.change >= 0 ? 'up' : 'down'}"></i>
                        <span>${Math.abs(stat.change)}%</span>
                    </div>
                ` : ''}
            </div>
        `).join('');
    }

    async loadSalesChart() {
        try {
            const period = document.getElementById('salesPeriod')?.value || '30days';
            const days = period === '7days' ? 7 : period === '30days' ? 30 : 90;
            const response = await app.apiCall(`../api.php?controller=dashboard&action=salesChart&days=${days}`);
            
            if (response.success) {
                this.updateSalesChart(response.data);
            }
        } catch (error) {
            console.error('Failed to load sales chart:', error);
        }
    }

    initializeCharts() {
        const canvas = document.getElementById('salesChart');
        if (!canvas) {
            console.warn('Sales chart canvas not found');
            return;
        }
        
        try {
            // Destroy existing chart if it exists
            if (this.salesChart) {
                this.salesChart.destroy();
                this.salesChart = null;
            }
            
            // Get all chart instances and destroy those using this canvas
            const existingChart = Chart.getChart(canvas);
            if (existingChart) {
                existingChart.destroy();
            }
            
            // Small delay to ensure canvas is ready
            setTimeout(() => {
                const ctx = canvas.getContext('2d');
                
                this.salesChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: [],
                        datasets: [{
                            label: 'Sales',
                            data: [],
                            borderColor: 'rgb(99, 102, 241)',
                            backgroundColor: 'rgba(99, 102, 241, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return app.formatCurrency(value);
                                }
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    }
                }
            });
            }, 100); // End of setTimeout
            
        } catch (error) {
            console.error('Failed to initialize chart:', error);
        }
    }

    updateSalesChart(data) {
        if (this.salesChart && data) {
            // Controller mengembalikan { labels:[], sales:[], transactions:[] }
            const labels = Array.isArray(data.labels) ? data.labels : [];
            const sales = Array.isArray(data.sales) ? data.sales : [];
            this.salesChart.data.labels = labels;
            this.salesChart.data.datasets[0].data = sales;
            this.salesChart.update();
        }
    }

    async loadTopProducts() {
        try {
            const period = document.getElementById('topProductsPeriod')?.value || '30days';
            const response = await app.apiCall(`../api.php?controller=dashboard&action=topProducts&limit=5`);
            
            if (response.success && response.data && response.data.products) {
                this.renderTopProducts(response.data.products);
            }
        } catch (error) {
            console.error('Failed to load top products:', error);
        }
    }

    renderTopProducts(products) {
        const container = document.getElementById('topProductsList');
        
        if (!products || products.length === 0) {
            container.innerHTML = '<p class="text-gray-500 text-center">No products found</p>';
            return;
        }

        container.innerHTML = products.map((product, index) => {
            const name = Utils.escapeHTML(product.name || product.product_name);
            const sku = Utils.escapeHTML(product.sku);
            const qty = app.formatNumber(product.total_quantity);
            const revenue = app.formatCurrency(product.total_revenue || product.total_sales);
            return `
            <div class="top-product-item">
                <div class="product-rank">${index + 1}</div>
                <div class="product-info">
                    <h4 class="product-name">${name}</h4>
                    <p class="product-sku">${sku}</p>
                </div>
                <div class="product-stats">
                    <div class="product-quantity">${qty} sold</div>
                    <div class="product-sales">${revenue}</div>
                </div>
            </div>
        `;
        }).join('');
    }

    async loadRecentTransactions() {
        try {
            const response = await app.apiCall('../api.php?controller=pos&action=getRecentTransactions&limit=10');
            
            if (response.success && response.data && response.data.transactions) {
                this.renderRecentTransactions(response.data.transactions);
            }
        } catch (error) {
            console.error('Failed to load recent transactions:', error);
        }
    }

    renderRecentTransactions(transactions) {
        const tbody = document.querySelector('#recentTransactionsTable tbody');
        
        if (!transactions || transactions.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" class="text-center text-gray-500">No transactions found</td></tr>';
            return;
        }

        tbody.innerHTML = transactions.map(transaction => {
            const txn = Utils.escapeHTML(transaction.transaction_number);
            const custName = Utils.escapeHTML(transaction.customer_name || 'Walk-in Customer');
            const custPhone = transaction.customer_phone ? Utils.escapeHTML(transaction.customer_phone) : '';
            const cashier = Utils.escapeHTML(transaction.cashier_name || transaction.user_name || '-');
            const itemsCount = transaction.items_count || 0;
            const amount = app.formatCurrency(transaction.total_amount);
            const methodKey = this.normalizePaymentMethod(transaction.payment_method);
            const methodLabel = this.formatPaymentMethod(methodKey);
            const statusKey = Utils.escapeHTML(this.formatStatus(transaction.status));
            const createdDate = app.formatDate(transaction.created_at);
            const createdTime = app.formatDate(transaction.created_at, { hour: '2-digit', minute: '2-digit' });
            return `
            <tr>
                <td>
                    <span class="font-medium">${txn}</span>
                </td>
                <td>
                    <div>
                        <div class="font-medium">${custName}</div>
                        ${custPhone ? `<div class="text-sm text-gray-500">${custPhone}</div>` : ''}
                    </div>
                </td>
                <td>
                    <span class="text-sm">${cashier}</span>
                </td>
                <td class="text-center">
                    <span class="badge">${itemsCount}</span>
                </td>
                <td>
                    <span class="font-semibold">${amount}</span>
                </td>
                <td>
                    <span class="payment-method payment-${methodKey}">
                        ${this.getPaymentMethodIcon(methodKey)} ${methodLabel}
                    </span>
                </td>
                <td>
                    <div>
                        <div class="text-sm">${createdDate}</div>
                        <div class="text-xs text-gray-500">${createdTime}</div>
                    </div>
                </td>
                <td>
                    <span class="status-badge status-${Utils.escapeHTML(transaction.status)}">
                        ${statusKey}
                    </span>
                </td>
                <td>
                    <button class="btn-icon" onclick="viewTransaction(${transaction.id})" title="View Details">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            </tr>
        `;
        }).join('');
    }

    async loadLowStockProducts() {
        try {
            const response = await app.apiCall('../api.php?controller=product&action=getLowStock&limit=10');
            
            if (response && response.success) {
                // API saat ini mengembalikan array langsung: data = [...]
                const products = (response.data && response.data.products)
                    ? response.data.products
                    : (Array.isArray(response.data) ? response.data : []);

                if (products && products.length > 0) {
                    this.renderLowStockProducts(products);
                    const card = document.getElementById('lowStockCard');
                    if (card) card.style.display = 'block';
                }
            }
        } catch (error) {
            console.error('Failed to load low stock products:', error);
        }
    }

    renderLowStockProducts(products) {
        const container = document.getElementById('lowStockList');
        
        if (!products || products.length === 0) {
            container.innerHTML = '<p class="text-gray-500 text-center">No low stock products</p>';
            return;
        }
        
        container.innerHTML = products.map(product => {
            const name = Utils.escapeHTML(product.name);
            const sku = Utils.escapeHTML(product.sku);
            const qty = Utils.escapeHTML(String(product.stock_quantity));
            const unit = Utils.escapeHTML(product.unit);
            const minLevel = Utils.escapeHTML(String(product.min_stock_level));
            return `
            <div class="low-stock-item">
                <div class="product-info">
                    <h4 class="product-name">${name}</h4>
                    <p class="product-sku">${sku}</p>
                </div>
                <div class="stock-info">
                    <div class="stock-quantity ${product.stock_quantity <= 0 ? 'out-of-stock' : 'low-stock'}">
                        ${qty} ${unit}
                    </div>
                    <div class="min-stock">Min: ${minLevel}</div>
                </div>
                <div class="product-actions">
                    <button class="btn btn-sm btn-primary" onclick="restockProduct(${product.id})">
                        <i class="fas fa-plus"></i> Restock
                    </button>
                </div>
            </div>
        `;
        }).join('');
    }

    setupEventListeners() {
        // Sales chart period change
        const salesPeriod = document.getElementById('salesPeriod');
        if (salesPeriod) {
            salesPeriod.addEventListener('change', () => {
                this.loadSalesChart();
            });
        }

        // Top products period change
        const topProductsPeriod = document.getElementById('topProductsPeriod');
        if (topProductsPeriod) {
            topProductsPeriod.addEventListener('change', () => {
                this.loadTopProducts();
            });
        }
    }

    getPaymentMethodIcon(method) {
        const icons = {
            'cash': 'fas fa-money-bill',
            'card': 'fas fa-credit-card',
            'transfer': 'fas fa-university',
            'qris': 'fas fa-qrcode',
            'other': 'fas fa-question'
        };
        return `<i class="${icons[method] || icons.other}"></i>`;
    }

    normalizePaymentMethod(method) {
        const allowed = ['cash','card','transfer','qris','other'];
        const m = String(method || '').toLowerCase();
        return allowed.includes(m) ? m : 'other';
    }

    formatPaymentMethod(method) {
        const methods = {
            'cash': 'Cash',
            'card': 'Card',
            'transfer': 'Transfer',
            'qris': 'QRIS',
            'other': 'Other'
        };
        return methods[method] || 'Unknown';
    }

    formatStatus(status) {
        const statuses = {
            'pending': 'Pending',
            'completed': 'Completed',
            'cancelled': 'Cancelled',
            'refunded': 'Refunded'
        };
        return statuses[status] || status;
    }
}

// Global functions for inline event handlers
function restockProduct(productId) {
    // Implement restock functionality
    app.showToast('Restock functionality coming soon', 'info');
}

function viewTransaction(transactionId) {
    // Redirect to transaction details page
    window.location.href = `transactions.php?id=${transactionId}`;
}

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new SystemManager();
});

// Add CSS for additional dashboard components
const additionalStyles = `
    .chart-controls {
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }

    .chart-controls .form-select {
        width: auto;
        min-width: 120px;
    }

    .top-product-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem 0;
        border-bottom: 1px solid var(--gray-200);
    }

    .top-product-item:last-child {
        border-bottom: none;
    }

    .product-rank {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: var(--primary-color);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.875rem;
    }

    .product-info {
        flex: 1;
    }

    .product-name {
        font-weight: 600;
        color: var(--gray-800);
        margin: 0 0 0.25rem 0;
        font-size: 0.875rem;
    }

    .product-sku {
        color: var(--gray-500);
        margin: 0;
        font-size: 0.75rem;
    }

    .product-stats {
        text-align: right;
    }

    .product-quantity {
        font-weight: 600;
        color: var(--gray-700);
        font-size: 0.875rem;
    }

    .product-sales {
        color: var(--success-color);
        font-weight: 600;
        font-size: 0.875rem;
    }

    .payment-method {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .payment-cash { background: var(--success-color); color: white; }
    .payment-card { background: var(--info-color); color: white; }
    .payment-transfer { background: var(--warning-color); color: white; }
    .payment-qris { background: var(--primary-color); color: white; }
    .payment-other { background: var(--gray-500); color: white; }

    .status-badge {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 500;
        text-transform: uppercase;
    }
    
    .badge {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        background: var(--primary-color);
        color: white;
        font-size: 0.75rem;
        font-weight: 600;
        min-width: 24px;
        text-align: center;
    }
    
    .btn-icon {
        padding: 0.5rem;
        border: none;
        background: transparent;
        color: var(--gray-600);
        cursor: pointer;
        border-radius: 4px;
        transition: all 0.2s;
    }
    
    .btn-icon:hover {
        background: var(--gray-100);
        color: var(--primary-color);
    }
    
    #recentTransactionsTable td {
        vertical-align: middle;
        white-space: nowrap;
    }
    
    #recentTransactionsTable td:nth-child(2) {
        white-space: normal;
        min-width: 150px;
    }

    .status-pending { background: var(--warning-color); color: white; }
    .status-completed { background: var(--success-color); color: white; }
    .status-cancelled { background: var(--error-color); color: white; }
    .status-refunded { background: var(--gray-500); color: white; }

    .low-stock-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        background: var(--gray-50);
        border-radius: var(--border-radius);
        margin-bottom: 0.5rem;
    }

    .low-stock-item:last-child {
        margin-bottom: 0;
    }

    .stock-info {
        text-align: center;
        min-width: 100px;
    }

    .stock-quantity {
        font-weight: 600;
        font-size: 0.875rem;
    }

    .stock-quantity.out-of-stock {
        color: var(--error-color);
    }

    .stock-quantity.low-stock {
        color: var(--warning-color);
    }

    .min-stock {
        font-size: 0.75rem;
        color: var(--gray-500);
    }

    .product-actions {
        margin-left: auto;
    }

    .user-menu-dropdown {
        position: absolute;
        top: 100%;
        right: 0;
        background: white;
        border: 1px solid var(--gray-200);
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-lg);
        min-width: 200px;
        z-index: 1000;
        display: none;
    }

    .user-menu-dropdown.show {
        display: block;
    }

    .user-menu-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        color: var(--gray-700);
        text-decoration: none;
        border: none;
        background: none;
        width: 100%;
        text-align: left;
        cursor: pointer;
        transition: background-color 0.2s ease;
    }

    .user-menu-item:hover {
        background: var(--gray-50);
    }

    .user-menu-divider {
        margin: 0.5rem 0;
        border: none;
        border-top: 1px solid var(--gray-200);
    }
`;

// Inject additional styles
const styleSheet = document.createElement('style');
styleSheet.textContent = additionalStyles;
document.head.appendChild(styleSheet);
