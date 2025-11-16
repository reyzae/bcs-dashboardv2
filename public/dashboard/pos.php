<?php
/**
 * Point of Sale (POS) Interface
 * 
 * Main POS interface for cashier operations including:
 * - Product search and selection
 * - Shopping cart management
 * - Customer selection
 * - Multiple payment methods (Cash, Card, QRIS, Transfer)
 * - Transaction processing
 * - Receipt printing
 * 
 * Access: Admin, Manager, Cashier ONLY
 */

require_once '../bootstrap.php';
require_once '../../app/helpers/functions.php';

// Require authentication and role check - Staff NOT allowed
requireAuth();
requireRole(['admin', 'manager', 'cashier']);

// Get user info
$user_name = $_SESSION['user_name'] ?? 'User';
$user_role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? 'staff';

// Ensure session consistency
if (!isset($_SESSION['user_role'])) {
    $_SESSION['user_role'] = $user_role;
}
?>

<!DOCTYPE html>
<html lang="en" class="pos-page">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Point of Sale - Bytebalok</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../assets/img/favicon.ico">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <link rel="stylesheet" href="../assets/css/pos.css">
    
    <style>
        :root {
            --role-color: <?php 
                echo match($user_role) {
                    'admin' => '#dc3545',
                    'manager' => '#0d6efd',
                    'staff' => '#17a2b8',
                    'cashier' => '#28a745',
                    default => '#6c757d'
                };
            ?>;
            --role-color-dark: <?php 
                echo match($user_role) {
                    'admin' => '#c82333',
                    'manager' => '#0b5ed7',
                    'staff' => '#138496',
                    'cashier' => '#1e7e34',
                    default => '#5a6268'
                };
            ?>;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar Navigation -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="index.php" class="sidebar-logo">
                    <img src="../assets/img/logo.svg" alt="Bytebalok" class="logo-img" style="height:40px; width:auto; object-fit:contain;">
                    <h1>Bytebalok</h1>
                </a>
                <!-- Mobile Close Button -->
                <button class="sidebar-close" id="sidebarClose" aria-label="Close Sidebar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- Role Badge -->
            <div class="sidebar-role-banner">
                <i class="fas fa-<?php 
                    echo match($user_role) {
                        'admin' => 'user-shield',
                        'manager' => 'user-tie',
                        'staff' => 'user',
                        'cashier' => 'cash-register',
                        default => 'user'
                    };
                ?>"></i>
                <?php echo strtoupper($user_role); ?> MODE
            </div>
            
            <nav class="sidebar-nav">
                <?php 
                $menu_items = getMenuByRole();
                $current_page = basename($_SERVER['PHP_SELF']);
                
                foreach ($menu_items as $item):
                    $is_active = ($current_page === $item['url']) ? 'active' : '';
                ?>
                <div class="nav-item">
                    <a href="<?php echo $item['url']; ?>" class="nav-link <?php echo $is_active; ?>">
                        <i class="fas <?php echo $item['icon']; ?>"></i>
                        <span><?php echo $item['label']; ?></span>
                    </a>
                </div>
                <?php endforeach; ?>
                
                <!-- Additional Menu Items -->
                <div class="sidebar-divider"></div>
                
                <div class="nav-item">
                    <a href="help.php" class="nav-link">
                        <i class="fas fa-question-circle"></i>
                        <span>Help</span>
                    </a>
                </div>
            </nav>
        </aside>
        <!-- Overlay for mobile/tablet to close sidebar when clicking outside -->
        <div id="sidebarOverlay" class="sidebar-overlay"></div>

        <!-- Main Content -->
        <main class="main-content pos-main">
            <!-- Compact Header -->
            <header class="pos-header">
                <div class="pos-header-left">
                    <button class="menu-toggle-btn" id="menuToggle" title="Toggle Menu">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="pos-branding">
                        <img src="../assets/img/logo.svg" alt="Bytebalok" class="logo-img" style="height:24px; width:auto; object-fit:contain;">
                        <h1>POS</h1>
                    </div>
                    <div class="header-stats">
                        <div class="stat-badge stat-success">
                            <i class="fas fa-dollar-sign"></i>
                            <div class="stat-info">
                                <small>Today</small>
                                <strong id="todaySales">Rp 0</strong>
                            </div>
                        </div>
                        <div class="stat-badge stat-primary">
                            <i class="fas fa-receipt"></i>
                            <div class="stat-info">
                                <small>Trans</small>
                                <strong id="todayTransactions">0</strong>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="pos-header-right">
                    <div class="user-badge" id="userButton">
                        <div class="user-avatar-sm">
                            <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                        </div>
                        <div class="user-info-sm">
                            <span class="user-name-sm"><?php echo htmlspecialchars($user_name); ?></span>
                            <span class="user-role-sm"><?php echo ucfirst($user_role); ?></span>
                        </div>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    
                    <div class="user-menu-dropdown" id="userMenu" style="display: none;">
                            <a href="settings.php" class="user-menu-item">
                                <i class="fas fa-cog"></i>
                                <span>Settings</span>
                            </a>
                            <hr class="user-menu-divider">
                            <button class="user-menu-item" id="logoutBtn">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Logout</span>
                            </button>
                    </div>
                </div>
            </header>

            <!-- POS Content -->
            <div class="pos-content">
                <!-- Left Panel - Products -->
                <div class="pos-left-panel">
                    <!-- Search and Filters -->
                    <div class="pos-search-section">
                        <div class="search-bar">
                            <i class="fas fa-search"></i>
                            <input 
                                type="text" 
                                id="productSearch" 
                                placeholder="Search products by name, SKU, or barcode..."
                                autofocus
                            >
                            <button class="btn btn-sm btn-primary" id="searchBtn">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                        
                        <div class="category-filters">
                            <button class="category-btn active" data-category="all">
                                <i class="fas fa-list"></i> All
                            </button>
                            <!-- Categories will be dynamically loaded here -->
                        </div>
                    </div>

                    <!-- Products Grid -->
                    <div class="products-grid" id="productsGrid">
                        <div class="loading-spinner">
                            <i class="fas fa-spinner fa-spin"></i>
                            <p>Loading products...</p>
                        </div>
                    </div>
                </div>

                <!-- Right Panel - Cart and Checkout -->
                <div class="pos-right-panel">
                    <!-- Quick Actions Bar -->
                    <div class="quick-actions-bar">
                        <button class="quick-action-btn btn-danger" id="clearCartBtn" title="Clear Cart (F2)">
                            <i class="fas fa-trash-alt"></i>
                            <span>Clear</span>
                        </button>
                        <button class="quick-action-btn btn-warning" id="holdTransactionBtn" title="Hold (F3)">
                            <i class="fas fa-pause-circle"></i>
                            <span>Hold</span>
                        </button>
                    </div>
                    
                    <!-- Cart Section -->
                    <div class="cart-section">
                        <div class="cart-header">
                            <div class="cart-title">
                                <i class="fas fa-shopping-cart"></i>
                                <h3>Shopping Cart</h3>
                            </div>
                            <span class="cart-badge" id="cartCount">0</span>
                        </div>
                        
                        <div class="cart-items" id="cartItems">
                            <div class="empty-cart">
                                <i class="fas fa-shopping-cart"></i>
                                <p>Cart is empty</p>
                                <small>Add products to get started</small>
                            </div>
                        </div>
                        
                        <!-- Cart Summary -->
                        <div class="cart-summary">
                            <div class="summary-row">
                                <span>Subtotal:</span>
                                <span id="cartSubtotal">Rp 0</span>
                            </div>
                            <div class="summary-row">
                                <span>Discount:</span>
                                <span id="cartDiscount">Rp 0</span>
                            </div>
                            <div class="summary-row">
                                <span>Tax (11%):</span>
                                <span id="cartTax">Rp 0</span>
                            </div>
                            <div class="summary-row total">
                                <span><strong>Total:</strong></span>
                                <span id="cartTotal"><strong>Rp 0</strong></span>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Section -->
                    <div class="customer-section-new">
                        <div class="section-header-compact">
                            <i class="fas fa-user-circle"></i>
                            <span>Customer</span>
                        </div>
                        <div class="customer-search-wrapper">
                        <div class="customer-search">
                                <i class="fas fa-search"></i>
                            <input 
                                type="text" 
                                id="customerSearch" 
                                    placeholder="Search customer..."
                            >
                            </div>
                            <button class="add-customer-btn" id="addCustomerBtn" title="Add New Customer">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <div class="selected-customer-card" id="selectedCustomer" style="display: none;">
                            <div class="customer-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="customer-details">
                                <strong class="customer-name"></strong>
                                <span class="customer-phone"></span>
                            </div>
                            <button class="remove-customer-btn" id="removeCustomerBtn" title="Remove">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Discount & Tax Section -->
                    <div class="adjustments-section">
                        <div class="adjustment-row">
                            <div class="adjustment-input-group">
                                <label>
                                    <i class="fas fa-percent"></i>
                                    Discount
                                </label>
                                <input 
                                    type="number" 
                                    id="discountInput" 
                                    placeholder="0" 
                                    min="0" 
                                    max="100"
                                    step="1"
                                    value="0"
                                    onchange="if(window.posManager) { posManager.discountRate = parseFloat(this.value) || 0; posManager.updateCartSummary(); }"
                                >
                                <span class="input-suffix">%</span>
                            </div>
                            <div class="adjustment-input-group">
                                <label>
                                    <i class="fas fa-file-invoice"></i>
                                    Tax
                                </label>
                                <input 
                                    type="number" 
                                    id="taxInput" 
                                    placeholder="11" 
                                    min="0" 
                                    max="100"
                                    step="0.1"
                                    value="11"
                                    onchange="if(window.posManager) { posManager.taxRate = parseFloat(this.value) || 0; posManager.updateCartSummary(); }"
                                >
                                <span class="input-suffix">%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Section -->
                    <div class="payment-section-new">
                        <div class="section-header-compact">
                            <i class="fas fa-wallet"></i>
                            <span>Payment</span>
                        </div>
                        
                        <div class="payment-methods-grid">
                            <button class="payment-method-btn active" data-method="cash" title="Cash (F4)">
                                <i class="fas fa-money-bill-wave"></i>
                                <span>Cash</span>
                                <small>F4</small>
                            </button>
                            <button class="payment-method-btn" data-method="card" title="Card (F5)">
                                <i class="fas fa-credit-card"></i>
                                <span>Card</span>
                                <small>F5</small>
                            </button>
                            <button class="payment-method-btn" data-method="qris" title="QRIS (F6)">
                                <i class="fas fa-qrcode"></i>
                                <span>QRIS</span>
                                <small>F6</small>
                            </button>
                            <button class="payment-method-btn" data-method="transfer" title="Transfer (F7)">
                                <i class="fas fa-university"></i>
                                <span>Transfer</span>
                                <small>F7</small>
                            </button>
                        </div>
                        
                        <div class="payment-input-wrapper">
                            <label class="payment-label">
                                <i class="fas fa-hand-holding-usd"></i>
                                Amount Received
                            </label>
                            <div class="payment-input-group">
                                <span class="currency-prefix">Rp</span>
                            <input 
                                type="number" 
                                id="paymentAmount" 
                                placeholder="0" 
                                min="0"
                                step="1000"
                            >
                            </div>
                            <div id="nonCashNote" class="non-cash-note" style="display: none; font-size: 12px; color: #6b7280; margin-top: 6px;">
                                Nominal mengikuti total. Kolom ini otomatis untuk QRIS/Transfer.
                            </div>
                        </div>
                        
                        <div class="change-display" id="changeAmount" style="display: none;">
                            <div class="change-label">
                                <i class="fas fa-exchange-alt"></i>
                                Change
                            </div>
                            <div id="changeValue" class="change-value">Rp 0</div>
                        </div>
                    </div>

                    <!-- Checkout Button -->
                    <button class="checkout-btn-new" id="checkoutBtn" disabled>
                        <i class="fas fa-check-circle"></i>
                        <span>Complete Payment</span>
                        <small>F12</small>
                    </button>
                </div>
            </div>
        </main>
    </div>

    <!-- Product Modal -->
    <div class="modal" id="productModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalProductName">Product Details</h3>
                <button class="modal-close" id="closeProductModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="product-details">
                    <div class="product-image">
                        <img id="modalProductImage" src="../assets/img/no-image.png" alt="Product Image">
                    </div>
                    <div class="product-info">
                        <p class="product-sku">
                            <strong>SKU:</strong> 
                            <span id="modalProductSku"></span>
                        </p>
                        <p class="product-description" id="modalProductDescription"></p>
                        <p class="product-price">
                            <strong>Price:</strong> 
                            <span id="modalProductPrice"></span>
                        </p>
                        <p class="product-stock">
                            <strong>Stock:</strong> 
                            <span id="modalProductStock"></span>
                        </p>
                    </div>
                </div>
                <div class="quantity-selector">
                    <label for="modalQuantity">
                        <i class="fas fa-shopping-basket"></i>
                        Quantity:
                    </label>
                    <div class="quantity-controls">
                        <button class="quantity-btn" id="decreaseQuantity">
                            <i class="fas fa-minus"></i>
                        </button>
                        <input type="number" id="modalQuantity" value="1" min="1">
                        <button class="quantity-btn" id="increaseQuantity">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancelAddToCart">
                    <i class="fas fa-times"></i>
                    Cancel
                </button>
                <button class="btn btn-primary" id="addToCartBtn">
                    <i class="fas fa-cart-plus"></i>
                    Add to Cart
                </button>
            </div>
        </div>
    </div>

    <!-- Customer Modal -->
    <div class="modal" id="customerModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-user-plus"></i>
                    Add New Customer
                </h3>
                <button class="modal-close" id="closeCustomerModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="customerForm">
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label for="customerName" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                            <i class="fas fa-user"></i>
                            Name *
                        </label>
                        <input 
                            type="text" 
                            id="customerName" 
                            placeholder="Enter customer name"
                            required
                            style="width: 100%; padding: 0.75rem; border: 1px solid var(--gray-300); border-radius: var(--border-radius); font-size: 1rem;"
                        >
                    </div>
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label for="customerPhone" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                            <i class="fas fa-phone"></i>
                            Phone
                        </label>
                        <input 
                            type="tel" 
                            id="customerPhone"
                            placeholder="Enter phone number"
                            style="width: 100%; padding: 0.75rem; border: 1px solid var(--gray-300); border-radius: var(--border-radius); font-size: 1rem;"
                        >
                    </div>
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label for="customerEmail" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                            <i class="fas fa-envelope"></i>
                            Email
                        </label>
                        <input 
                            type="email" 
                            id="customerEmail"
                            placeholder="Enter email address"
                            style="width: 100%; padding: 0.75rem; border: 1px solid var(--gray-300); border-radius: var(--border-radius); font-size: 1rem;"
                        >
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancelCustomer">
                    <i class="fas fa-times"></i>
                    Cancel
                </button>
                <button class="btn btn-primary" id="saveCustomer">
                    <i class="fas fa-save"></i>
                    Save Customer
                </button>
            </div>
        </div>
    </div>
    
    <!-- Keyboard Shortcuts Modal -->
    <div class="modal" id="shortcutsModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-keyboard"></i>
                    Keyboard Shortcuts
                </h3>
                <button class="modal-close" onclick="document.getElementById('shortcutsModal').classList.remove('show')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div style="display: grid; grid-template-columns: auto 1fr; gap: 1rem; align-items: center;">
                    <kbd style="padding: 0.5rem 1rem; background: linear-gradient(to bottom, #fff, #f0f0f0); border: 1px solid #ccc; border-radius: 4px; font-family: monospace; font-weight: 600; box-shadow: 0 2px 0 rgba(0,0,0,0.1);">F1</kbd>
                    <span>Focus Product Search</span>
                    
                    <kbd style="padding: 0.5rem 1rem; background: linear-gradient(to bottom, #fff, #f0f0f0); border: 1px solid #ccc; border-radius: 4px; font-family: monospace; font-weight: 600; box-shadow: 0 2px 0 rgba(0,0,0,0.1);">F2</kbd>
                    <span>Clear Cart</span>
                    
                    <kbd style="padding: 0.5rem 1rem; background: linear-gradient(to bottom, #fff, #f0f0f0); border: 1px solid #ccc; border-radius: 4px; font-family: monospace; font-weight: 600; box-shadow: 0 2px 0 rgba(0,0,0,0.1);">F3</kbd>
                    <span>Hold Transaction</span>
                    
                    <kbd style="padding: 0.5rem 1rem; background: linear-gradient(to bottom, #fff, #f0f0f0); border: 1px solid #ccc; border-radius: 4px; font-family: monospace; font-weight: 600; box-shadow: 0 2px 0 rgba(0,0,0,0.1);">F4</kbd>
                    <span>Cash Payment</span>
                    
                    <kbd style="padding: 0.5rem 1rem; background: linear-gradient(to bottom, #fff, #f0f0f0); border: 1px solid #ccc; border-radius: 4px; font-family: monospace; font-weight: 600; box-shadow: 0 2px 0 rgba(0,0,0,0.1);">F5</kbd>
                    <span>Card Payment</span>
                    
                    <kbd style="padding: 0.5rem 1rem; background: linear-gradient(to bottom, #fff, #f0f0f0); border: 1px solid #ccc; border-radius: 4px; font-family: monospace; font-weight: 600; box-shadow: 0 2px 0 rgba(0,0,0,0.1);">F6</kbd>
                    <span>QRIS Payment</span>
                    
                    <kbd style="padding: 0.5rem 1rem; background: linear-gradient(to bottom, #fff, #f0f0f0); border: 1px solid #ccc; border-radius: 4px; font-family: monospace; font-weight: 600; box-shadow: 0 2px 0 rgba(0,0,0,0.1);">F7</kbd>
                    <span>Transfer Payment</span>
                    
                    <kbd style="padding: 0.5rem 1rem; background: linear-gradient(to bottom, #fff, #f0f0f0); border: 1px solid #ccc; border-radius: 4px; font-family: monospace; font-weight: 600; box-shadow: 0 2px 0 rgba(0,0,0,0.1);">F12</kbd>
                    <span>Process Payment</span>
                    
                    <kbd style="padding: 0.5rem 1rem; background: linear-gradient(to bottom, #fff, #f0f0f0); border: 1px solid #ccc; border-radius: 4px; font-family: monospace; font-weight: 600; box-shadow: 0 2px 0 rgba(0,0,0,0.1);">ESC</kbd>
                    <span>Close Modals</span>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="document.getElementById('shortcutsModal').classList.remove('show')">
                    <i class="fas fa-check"></i>
                    Got it!
                </button>
            </div>
        </div>
    </div>

    <!-- Toast Notification Container -->
    <div id="toast" class="toast"></div>

    <!-- Scripts -->
    <script src="../assets/js/app.js"></script>
    <script src="../assets/js/pos.js"></script>
    
    <script>
        // Additional event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Load tax settings from database
            loadTaxSettings();
            
            // Rely on global app.js + responsive.css for sidebar behavior.
            // Remove any legacy classes in case they were persisted.
            const sidebar = document.getElementById('sidebar');
            if (sidebar) {
                sidebar.classList.remove('collapsed', 'minimized', 'hidden', 'expanded', 'visible');
                sidebar.style.removeProperty('width');
                sidebar.style.removeProperty('transform');
                sidebar.style.removeProperty('display');
            }
            
            // User menu toggle
            const userButton = document.getElementById('userButton');
            const userMenu = document.getElementById('userMenu');
            
            if (userButton && userMenu) {
                userButton.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const isVisible = userMenu.style.display === 'block';
                    userMenu.style.display = isVisible ? 'none' : 'block';
                });
                
                // Close menu when clicking outside
                document.addEventListener('click', function() {
                    userMenu.style.display = 'none';
                });
                
                userMenu.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
            
            // Customer modal handlers
            document.getElementById('closeCustomerModal')?.addEventListener('click', function() {
                document.getElementById('customerModal').classList.remove('show');
            });
            
            document.getElementById('cancelCustomer')?.addEventListener('click', function() {
                document.getElementById('customerModal').classList.remove('show');
            });
            
            document.getElementById('saveCustomer')?.addEventListener('click', async function() {
                const name = document.getElementById('customerName').value.trim();
                const phone = document.getElementById('customerPhone').value.trim();
                const email = document.getElementById('customerEmail').value.trim();
                
                if (!name) {
                    app.showToast('Customer name is required', 'error');
                    return;
                }
                
                try {
                    // Quick add customer (you can create a proper API endpoint later)
                    const customer = { name, phone, email, id: Date.now() };
                    
                    if (window.posManager) {
                        posManager.selectCustomer(customer);
                        document.getElementById('customerModal').classList.remove('show');
                        document.getElementById('customerForm').reset();
                        app.showToast('Customer added successfully', 'success');
                    }
                } catch (error) {
                    console.error('Failed to add customer:', error);
                    app.showToast('Failed to add customer', 'error');
                }
            });
            
            // Remove customer button
            document.getElementById('removeCustomerBtn')?.addEventListener('click', function() {
                if (window.posManager) {
                    posManager.selectedCustomer = null;
                    document.getElementById('selectedCustomer').style.display = 'none';
                    app.showToast('Customer removed', 'info');
                }
            });
        });
        
        // ============================================================================
        // TAX SETTINGS LOADER
        // ============================================================================
        async function loadTaxSettings() {
            try {
                console.log('🔍 Loading tax settings from database...');
                
                const response = await fetch('../api.php?controller=settings&action=get');
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                
                const data = await response.json();
                console.log('📦 Settings loaded:', data);
                
                if (data.success && data.data) {
                    const settings = data.data;
                    const taxInput = document.getElementById('taxInput');
                    const taxInputGroup = taxInput?.closest('.adjustment-input-group');
                    
                    // Check if tax is enabled
                    const taxEnabled = settings.enable_tax === '1';
                    const taxRate = parseFloat(settings.tax_rate) || 11;
                    
                    console.log(`💰 Tax Enabled: ${taxEnabled}, Rate: ${taxRate}%`);
                    
                    if (taxInput) {
                        if (!taxEnabled) {
                            // Tax is DISABLED - Gray out the field
                            taxInput.disabled = true;
                            taxInput.value = 0;
                            taxInput.style.backgroundColor = '#f3f4f6';
                            taxInput.style.color = '#9ca3af';
                            taxInput.style.cursor = 'not-allowed';
                            
                            if (taxInputGroup) {
                                taxInputGroup.style.opacity = '0.5';
                                taxInputGroup.style.pointerEvents = 'none';
                                
                                // Add disabled indicator
                                const label = taxInputGroup.querySelector('label');
                                if (label && !label.querySelector('.tax-disabled-badge')) {
                                    const badge = document.createElement('span');
                                    badge.className = 'tax-disabled-badge';
                                    badge.innerHTML = '<i class="fas fa-ban"></i> Disabled';
                                    badge.style.cssText = 'margin-left: 8px; font-size: 0.75rem; color: #ef4444; font-weight: 600;';
                                    label.appendChild(badge);
                                }
                            }
                            
                            // Update posManager if exists
                            if (window.posManager) {
                                posManager.taxRate = 0;
                                posManager.updateCartSummary();
                            }
                            
                            console.log('🚫 Tax field DISABLED (gray)');
                        } else {
                            // Tax is ENABLED - Set the rate from settings
                            taxInput.disabled = false;
                            taxInput.value = taxRate;
                            taxInput.style.backgroundColor = '';
                            taxInput.style.color = '';
                            taxInput.style.cursor = '';
                            
                            if (taxInputGroup) {
                                taxInputGroup.style.opacity = '1';
                                taxInputGroup.style.pointerEvents = 'auto';
                            }
                            
                            // Update posManager if exists
                            if (window.posManager) {
                                posManager.taxRate = taxRate;
                                posManager.updateCartSummary();
                            }
                            
                            console.log(`✅ Tax field ENABLED with rate: ${taxRate}%`);
                        }
                    }
                    
                    // Update cart summary display
                    updateTaxSummaryDisplay(taxEnabled, taxRate);
                    
                } else {
                    console.warn('⚠️ No settings data, using defaults');
                }
            } catch (error) {
                console.error('❌ Failed to load tax settings:', error);
                console.warn('⚠️ Using default tax settings');
            }
        }
        
        // Update the tax display in cart summary
        function updateTaxSummaryDisplay(taxEnabled, taxRate) {
            const taxSummaryRow = document.querySelector('.summary-row:nth-child(3)');
            
            if (taxSummaryRow) {
                const taxLabel = taxSummaryRow.querySelector('span:first-child');
                
                if (!taxEnabled) {
                    // Show disabled state
                    if (taxLabel) {
                        taxLabel.innerHTML = 'Tax: <span style="color: #ef4444; font-size: 0.75rem; font-weight: 600;">(Disabled)</span>';
                        taxLabel.style.opacity = '0.5';
                    }
                    taxSummaryRow.style.opacity = '0.5';
                } else {
                    // Show enabled state with rate
                    if (taxLabel) {
                        taxLabel.textContent = `Tax (${taxRate}%):`;
                        taxLabel.style.opacity = '1';
                    }
                    taxSummaryRow.style.opacity = '1';
                }
            }
        }
    </script>
    <script>
    (function() {
        'use strict';
        function forceNavigation() {
            const links = document.querySelectorAll('.sidebar a, .sidebar .nav-link, a.nav-link');
            if (links.length === 0) {
                setTimeout(forceNavigation, 300);
                return;
            }
            links.forEach(function(link) {
                const href = link.getAttribute('href');
                if (!href || href === '#' || href.startsWith('javascript:')) {
                    return;
                }
                const newLink = link.cloneNode(true);
                link.parentNode.replaceChild(newLink, link);
                newLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    const target = this.getAttribute('href');
                    const sidebar = document.getElementById('sidebar');
                    if (sidebar) sidebar.classList.remove('open');
                    window.location.href = target;
                    return false;
                }, true);
            });
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(forceNavigation, 100);
            });
        } else {
            setTimeout(forceNavigation, 100);
        }
    })();
    </script>
</body>
</html>
