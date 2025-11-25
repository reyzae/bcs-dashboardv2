/**
 * Bytebalok POS - Point of Sale JavaScript
 * Complete implementation with keyboard shortcuts and advanced features
 * Version: 2.0 - Fixed & Enhanced
 */

class POSManager {
    constructor() {
        this.cart = [];
        this.products = [];
        this.categories = [];
        this.selectedCustomer = null;
        this.selectedProduct = null;
        this.paymentMethod = 'cash';
        this.taxRate = 11; // 11% tax rate (PPN Indonesia)
        this.discountRate = 0;
        this.barcodeBuffer = '';
        this.barcodeTimeout = null;
        this.productCardEventsSetup = false; // Flag to prevent duplicate event listeners
        
        this.init();
    }

    async init() {
        console.log('🚀 Initializing POS System...');
        
        // Load cart from localStorage backup
        this.loadCartFromStorage();
        
        // Setup event listeners first (including product card events)
        // Setup product card events immediately - they use document-level delegation
        this.setupProductCardEvents();
        this.setupEventListeners();
        this.setupKeyboardShortcuts();
        this.setupCustomerEventListeners();
        
        // Load data
        await this.loadTaxRate(); // Load tax rate from settings
        await this.loadProducts();
        await this.loadCategories();
        await this.loadQuickStats();
        
        // Update cart display
        this.updateCartDisplay();
        
        // Auto-save cart every 30 seconds
        setInterval(() => this.saveCartToStorage(), 30000);
        
        // Refresh stats every 60 seconds
        setInterval(() => this.loadQuickStats(), 60000);
        
        console.log('✅ POS System Ready!');
        console.log('🔔 Customer sync enabled');
    }
    
    async loadTaxRate() {
        try {
            console.log('📊 Loading tax rate from settings...');
            
            const response = await fetch('../api.php?controller=settings&action=get&key=tax_rate');
            console.log('📥 Tax rate response status:', response.status);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            const data = await response.json();
            console.log('📦 Tax rate data:', data);
            
            if (data.success && data.data && data.data.value) {
                const taxRate = parseFloat(data.data.value) || 11;
                this.taxRate = taxRate;
                
                // Update tax rate input if exists
                const taxInput = document.getElementById('taxInput');
                if (taxInput) {
                    taxInput.value = taxRate;
                }
                
                console.log(`✅ Tax rate loaded: ${taxRate}%`);
                
                // Update cart summary with new tax rate (will be called after cart loads)
            } else {
                console.warn('⚠️ Using default tax rate: 11%');
            }
        } catch (error) {
            console.error('❌ Failed to load tax rate:', error);
            console.warn('⚠️ Using default tax rate: 11%');
            this.taxRate = 11;
        }
    }

    async loadProducts() {
        const grid = document.getElementById('productsGrid');
        
        try {
            console.log('📦 Loading products...');
            console.log('📍 API URL: ../api.php?controller=pos&action=getProducts');
            
            // Show loading state
            grid.innerHTML = `
                <div style="grid-column: 1/-1; text-align: center; padding: 3rem;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 3rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
                    <p style="color: var(--gray-600);">Loading products...</p>
                </div>
            `;
            
            const response = await app.apiCall('../api.php?controller=pos&action=getProducts');
            
            console.log('📦 Products Response:', response);
            
            if (response && response.success && response.data) {
                // Normalize product data types to avoid ID mismatch issues
                const rawProducts = Array.isArray(response.data.products) ? response.data.products : response.data;
                this.products = (rawProducts || []).map(p => ({
                    ...p,
                    // Ensure numeric comparisons work consistently
                    id: (p && p.id !== undefined) ? parseInt(p.id) : ((p && p.product_id !== undefined) ? parseInt(p.product_id) : p.id),
                    stock_quantity: (p && p.stock_quantity !== undefined) ? parseInt(p.stock_quantity) : 0,
                    min_stock_level: (p && p.min_stock_level !== undefined) ? parseInt(p.min_stock_level) : 0,
                    price: (p && p.price !== undefined) ? parseFloat(p.price) : 0
                }));
                this.renderProducts();
                console.log(`✅ Loaded ${this.products.length} products`);
            } else {
                const errorMsg = response?.message || response?.error || 'Failed to load products';
                throw new Error(errorMsg);
            }
        } catch (error) {
            console.error('❌ Failed to load products:', error);
            console.error('❌ Error details:', {
                message: error.message,
                stack: error.stack,
                response: error.response
            });
            
            // Determine error type
            let errorTitle = 'Failed to Load Products';
            let errorMessage = error.message || 'Unknown error occurred';
            let errorHint = '';
            
            if (error.message && error.message.includes('401')) {
                errorTitle = 'Authentication Required';
                errorMessage = 'Please login again';
                errorHint = 'Your session may have expired';
            } else if (error.message && error.message.includes('400')) {
                errorTitle = 'Bad Request';
                errorMessage = 'Invalid request to server';
                errorHint = 'Checking API connection...';
            } else if (error.message && error.message.includes('404')) {
                errorTitle = 'API Not Found';
                errorMessage = 'API endpoint not found';
                errorHint = 'Please check server configuration';
            } else if (error.message && error.message.includes('500')) {
                errorTitle = 'Server Error';
                errorMessage = 'Internal server error';
                errorHint = 'Please contact administrator';
            }
            
            app.showToast(errorTitle + ': ' + errorMessage, 'error');
            
            // Show detailed error in grid
            grid.innerHTML = `
                <div style="grid-column: 1/-1; text-align: center; padding: 3rem;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #ef4444; margin-bottom: 1rem;"></i>
                    <h3 style="color: #374151; margin-bottom: 0.5rem; font-weight: 600;">${errorTitle}</h3>
                    <p style="color: #6b7280; margin-bottom: 0.5rem;">${errorMessage}</p>
                    ${errorHint ? `<p style="color: #9ca3af; font-size: 0.875rem; margin-bottom: 1rem;">${errorHint}</p>` : ''}
                    <button class="btn btn-primary" onclick="posManager.loadProducts()" style="margin-top: 1rem;">
                        <i class="fas fa-redo"></i> Retry
                    </button>
                </div>
            `;
        }
    }

    async loadCategories() {
        try {
            const response = await app.apiCall('../api.php?controller=pos&action=getCategories');
            if (response.success) {
                this.categories = response.data;
                this.renderCategories();
            }
        } catch (error) {
            console.error('Failed to load categories:', error);
        }
    }

    async loadQuickStats() {
        try {
            const response = await app.apiCall('../api.php?controller=pos&action=getStats');
            if (response.success && response.data) {
                const stats = response.data;
                
                // Update today's sales
                const todaySalesEl = document.getElementById('todaySales');
                if (todaySalesEl) {
                    todaySalesEl.textContent = app.formatCurrency(stats.today_sales || 0);
                }
                
                // Update today's transactions count
                const todayTransactionsEl = document.getElementById('todayTransactions');
                if (todayTransactionsEl) {
                    todayTransactionsEl.textContent = stats.today_transactions || 0;
                }
            }
        } catch (error) {
            console.error('Failed to load quick stats:', error);
            // Don't show error toast for stats, it's not critical
        }
    }

    renderProducts(products = this.products) {
        const grid = document.getElementById('productsGrid');
        
        if (!products || products.length === 0) {
            grid.innerHTML = `
                <div style="grid-column: 1/-1; text-align: center; padding: 3rem;">
                    <i class="fas fa-box-open" style="font-size: 3rem; color: var(--gray-400); margin-bottom: 1rem;"></i>
                    <p style="color: var(--gray-500);">No products found</p>
                </div>
            `;
            return;
        }

        // Format currency helper
        const formatPrice = (price) => {
            try {
                if (window.app && typeof window.app.formatCurrency === 'function') {
                    return window.app.formatCurrency(price);
                }
                // Fallback
                return 'Rp ' + new Intl.NumberFormat('id-ID').format(price);
            } catch (e) {
                return 'Rp ' + price.toLocaleString('id-ID');
            }
        };

        grid.innerHTML = products.map(product => `
            <div class="product-card ${product.stock_quantity <= 0 ? 'out-of-stock' : ''}" 
                 data-product-id="${product.id}"
                 style="cursor: pointer;"
                 onclick="console.log('CLICKED Product ID:', ${product.id}); if(window.posManager && window.posManager.handleProductClick){ window.posManager.handleProductClick(${product.id}); } else { console.error('posManager not ready:', window.posManager); alert('System sedang memuat, silakan refresh halaman'); }">
                <div class="product-image">
                    ${product.image ? 
                        `<img src="../${product.image}" alt="${product.name}" onerror="this.style.display='none'; this.parentElement.innerHTML='<i class=\\'fas fa-box\\'></i>';">` : 
                        '<i class="fas fa-box"></i>'
                    }
                </div>
                <h4 class="product-name">${product.name}</h4>
                <p class="product-sku">SKU: ${product.sku}</p>
                <p class="product-price">${formatPrice(product.price)}</p>
                <p class="product-stock ${product.stock_quantity < product.min_stock_level ? 'low-stock' : ''} ${product.stock_quantity <= 0 ? 'out-of-stock' : ''}">
                    Stock: ${product.stock_quantity} ${product.unit || 'pcs'}
                </p>
                ${product.stock_quantity <= 0 ? '<div class="out-of-stock-badge">Out of Stock</div>' : ''}
            </div>
        `).join('');
        
        // Setup click events directly on product cards after rendering (with error handling)
        try {
            setTimeout(() => {
                this.attachProductCardClickEvents();
            }, 100); // Small delay to ensure DOM is ready
        } catch (error) {
            console.error('❌ Error in renderProducts after attachProductCardClickEvents:', error);
        }
    }
    
    // Handler untuk inline onclick (fallback)
    handleProductClick(productId) {
        console.log('🖱️ ========== handleProductClick CALLED ==========');
        console.log('🖱️ Product ID:', productId);
        console.log('🖱️ posManager:', this);
        console.log('🖱️ window.posManager:', window.posManager);
        console.log('🖱️ this.products:', this.products);
        
        const pid = parseInt(productId);
        if (!pid || isNaN(pid)) {
            console.error('❌ Invalid product ID:', productId);
            alert('Invalid product ID: ' + productId);
            return;
        }
        
        // Check if product is out of stock
        const product = this.products.find(p => {
            const id = (p && p.id !== undefined) ? parseInt(p.id) : null;
            const altId = (p && p.product_id !== undefined) ? parseInt(p.product_id) : null;
            return id === pid || altId === pid;
        });
        console.log('🖱️ Found product:', product);
        
        if (!product) {
            console.error('❌ Product not found for ID:', productId);
            alert('Product tidak ditemukan! ID: ' + productId);
            return;
        }
        
        if (product.stock_quantity <= 0) {
            console.log('⚠️ Product is out of stock');
            if (window.app && typeof window.app.showToast === 'function') {
                window.app.showToast('Product is out of stock', 'warning');
            } else {
                alert('Product habis stok');
            }
            return;
        }
        
        console.log('📦 Opening modal for product ID:', productId);
        console.log('📦 Product name:', product.name);
        
        try {
            if (typeof this.openProductModal === 'function') {
                this.openProductModal(productId);
                console.log('✅ Modal opened for product:', productId);
            } else {
                console.error('❌ openProductModal is not a function!');
                console.error('❌ this.openProductModal:', this.openProductModal);
                alert('Error: openProductModal tidak tersedia');
            }
        } catch (error) {
            console.error('❌ Error opening modal:', error);
            console.error('❌ Error stack:', error.stack);
            alert('Error: ' + error.message);
            if (window.app && typeof window.app.showToast === 'function') {
                window.app.showToast('Terjadi kesalahan', 'error');
            }
        }
    }
    
    attachProductCardClickEvents() {
        try {
            console.log('🔧 Starting attachProductCardClickEvents...');
            
            const grid = document.getElementById('productsGrid');
            if (!grid) {
                console.warn('⚠️ Products grid not found for attaching click events');
                return;
            }
            
            const productCards = Array.from(grid.querySelectorAll('.product-card'));
            console.log(`🔧 Found ${productCards.length} product cards to attach events...`);
            
            if (productCards.length === 0) {
                console.warn('⚠️ No product cards found');
                return;
            }
            
            const self = this;
            let attachedCount = 0;
            
            productCards.forEach((card, index) => {
                try {
                    // Attach click event directly (additional to inline onclick)
                    card.addEventListener('click', function(e) {
                        console.log(`🖱️ Product card #${index + 1} clicked via addEventListener:`, card);
                        
                        // Don't handle if out of stock
                        if (card.classList.contains('out-of-stock')) {
                            console.log('⚠️ Product is out of stock');
                            if (window.app && typeof window.app.showToast === 'function') {
                                window.app.showToast('Product is out of stock', 'warning');
                            }
                            return;
                        }
                        
                        // Get product ID
                        const productId = parseInt(card.getAttribute('data-product-id'));
                        if (!productId || isNaN(productId)) {
                            console.error('❌ Invalid product ID:', card.getAttribute('data-product-id'));
                            return;
                        }
                        
                        console.log('📦 Opening modal for product ID:', productId);
                        
                        // Try to open modal
                        const manager = self || window.posManager;
                        if (manager && typeof manager.openProductModal === 'function') {
                            try {
                                manager.openProductModal(productId);
                                console.log('✅ Modal opened for product:', productId);
                            } catch (error) {
                                console.error('❌ Error opening modal:', error);
                                console.error('Error stack:', error.stack);
                                if (window.app && typeof window.app.showToast === 'function') {
                                    window.app.showToast('Terjadi kesalahan', 'error');
                                }
                            }
                        } else {
                            console.error('❌ posManager not available. self:', self, 'window.posManager:', window.posManager);
                        }
                    });
                    attachedCount++;
                } catch (error) {
                    console.error(`❌ Error attaching event to card ${index + 1}:`, error);
                }
            });
            
            console.log(`✅ Click events attached to ${attachedCount} of ${productCards.length} product cards`);
        } catch (error) {
            console.error('❌ Fatal error in attachProductCardClickEvents:', error);
            console.error('Error stack:', error.stack);
        }
    }
    
    setupProductCardEvents() {
        // This method is kept for backward compatibility but now we attach events directly in renderProducts
        // Using document-level delegation as backup
        if (this.productCardEventsSetup) {
            return;
        }
        
        console.log('🔧 Setting up document-level product card click events (backup)...');
        
        const self = this;
        
        // Document-level event delegation as backup
        const clickHandler = function(e) {
            const grid = document.getElementById('productsGrid');
            if (!grid || !grid.contains(e.target)) {
                return;
            }
            
            const productCard = e.target.closest('.product-card');
            if (!productCard) return;
            
            // Only handle if direct event listener didn't work
            const productId = parseInt(productCard.getAttribute('data-product-id'));
            if (!productId || isNaN(productId)) return;
            
            console.log('🖱️ Product card clicked (document delegation):', productId);
            
            if (productCard.classList.contains('out-of-stock')) {
                if (window.app && typeof window.app.showToast === 'function') {
                    window.app.showToast('Product is out of stock', 'warning');
                }
                return;
            }
            
            const manager = self || window.posManager;
            if (manager && typeof manager.openProductModal === 'function') {
                try {
                    manager.openProductModal(productId);
                } catch (error) {
                    console.error('❌ Error opening modal:', error);
                }
            }
        };
        
        document.addEventListener('click', clickHandler, { passive: true, capture: false });
        this._productCardClickHandler = clickHandler;
        this.productCardEventsSetup = true;
        console.log('✅ Document-level click events setup complete (backup)');
    }

    renderCategories() {
        const container = document.querySelector('.category-filters');
        
        const categoryButtons = this.categories.map(category => `
            <button class="category-btn" data-category="${category.id}">
                <i class="fas fa-tag"></i> ${category.name}
            </button>
        `).join('');

        container.innerHTML = `
            <button class="category-btn active" data-category="all">
                <i class="fas fa-list"></i> All
            </button>
            ${categoryButtons}
        `;
    }

    openProductModal(productId) {
        const pid = parseInt(productId);
        const product = this.products.find(p => {
            const id = (p && p.id !== undefined) ? parseInt(p.id) : null;
            const altId = (p && p.product_id !== undefined) ? parseInt(p.product_id) : null;
            return id === pid || altId === pid;
        });
        if (!product) return;

        if (product.stock_quantity <= 0) {
            app.showToast('Product is out of stock', 'warning');
            return;
        }

        this.selectedProduct = product;

        // Populate modal with product data
        document.getElementById('modalProductName').textContent = product.name;
        document.getElementById('modalProductSku').textContent = product.sku;
        document.getElementById('modalProductDescription').textContent = product.description || 'No description available';
        document.getElementById('modalProductPrice').textContent = app.formatCurrency(product.price);
        document.getElementById('modalProductStock').textContent = `${product.stock_quantity} ${product.unit || 'pcs'} available`;
        
        const imgElement = document.getElementById('modalProductImage');
        if (product.image) {
            imgElement.src = '../' + product.image;
            imgElement.onerror = () => {
                imgElement.src = '../assets/img/no-image.svg';
            };
        } else {
            imgElement.src = '../assets/img/no-image.svg';
        }
        
        document.getElementById('modalQuantity').value = 1;
        document.getElementById('modalQuantity').max = product.stock_quantity;

        // Show modal and focus quantity input
        document.getElementById('productModal').classList.add('show');
        setTimeout(() => {
            document.getElementById('modalQuantity').select();
        }, 100);
    }

    closeProductModal() {
        document.getElementById('productModal').classList.remove('show');
        this.selectedProduct = null;
    }

    addToCart(productId = null, quantity = 1) {
        const pid = productId ? parseInt(productId) : null;
        const product = pid !== null ? this.products.find(p => {
            const id = (p && p.id !== undefined) ? parseInt(p.id) : null;
            const altId = (p && p.product_id !== undefined) ? parseInt(p.product_id) : null;
            return id === pid || altId === pid;
        }) : this.selectedProduct;
        if (!product) return;

        if (product.stock_quantity < quantity) {
            app.showToast('Insufficient stock', 'error');
            return;
        }

        const existingItem = this.cart.find(item => item.product_id === product.id);
        
        if (existingItem) {
            const newQuantity = existingItem.quantity + quantity;
            if (newQuantity > product.stock_quantity) {
                app.showToast(`Only ${product.stock_quantity} available`, 'warning');
                return;
            }
            existingItem.quantity = newQuantity;
            existingItem.total_price = existingItem.unit_price * newQuantity;
        } else {
            this.cart.push({
                product_id: product.id,
                name: product.name,
                sku: product.sku,
                unit_price: parseFloat(product.price),
                quantity: quantity,
                // DON'T store image to avoid localStorage quota issues
                // image: product.image,
                unit: product.unit || 'pcs',
                total_price: parseFloat(product.price) * quantity
            });
        }

        this.updateCartDisplay();
        this.saveCartToStorage();
        this.closeProductModal();
        app.showToast(`${product.name} added to cart`, 'success');
        
        // Auto-focus search for next product
        setTimeout(() => {
            document.getElementById('productSearch').focus();
        }, 300);
    }

    removeFromCart(productId) {
        const item = this.cart.find(item => item.product_id === productId);
        if (item) {
            this.cart = this.cart.filter(item => item.product_id !== productId);
            this.updateCartDisplay();
            this.saveCartToStorage();
            app.showToast(`${item.name} removed from cart`, 'info');
        }
    }

    updateCartItemQuantity(productId, quantity) {
        const item = this.cart.find(item => item.product_id === productId);
        if (item) {
            if (quantity <= 0) {
                this.removeFromCart(productId);
            } else {
                const product = this.products.find(p => p.id === productId);
                if (product && quantity > product.stock_quantity) {
                    app.showToast(`Only ${product.stock_quantity} available`, 'warning');
                    item.quantity = product.stock_quantity;
                } else {
                    item.quantity = quantity;
                }
                item.total_price = item.unit_price * item.quantity;
                this.updateCartDisplay();
                this.saveCartToStorage();
            }
        }
    }

    updateCartDisplay() {
        this.renderCartItems();
        this.updateCartSummary();
        this.updateCheckoutButton();
    }

    renderCartItems() {
        const container = document.getElementById('cartItems');
        const countElement = document.getElementById('cartCount');
        
        const totalItems = this.cart.reduce((sum, item) => sum + item.quantity, 0);
        // Support both old and new cart count display
        if (countElement.classList.contains('cart-badge')) {
            countElement.textContent = totalItems; // New design - just number
        } else {
            countElement.textContent = `${totalItems} items`; // Old design
        }

        if (this.cart.length === 0) {
            container.innerHTML = `
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <p>Cart is empty</p>
                    <small>Add products to get started</small>
                </div>
            `;
            return;
        }

        container.innerHTML = this.cart.map(item => {
            // Get product image from products array
            const product = this.products.find(p => p.id === item.product_id);
            const itemImage = product?.image || null;
            
            return `
            <div class="cart-item">
                <div class="cart-item-image">
                    ${itemImage ? 
                        `<img src="../${itemImage}" alt="${item.name}" onerror="this.style.display='none'; this.parentElement.innerHTML='<i class=\\'fas fa-box\\'></i>';">` : 
                        '<i class="fas fa-box"></i>'
                    }
                </div>
                <div class="cart-item-info">
                    <h4 class="cart-item-name">${item.name}</h4>
                    <p class="cart-item-price">${app.formatCurrency(item.unit_price)} / ${item.unit}</p>
                    <p class="cart-item-total">${app.formatCurrency(item.total_price)}</p>
                </div>
                <div class="cart-item-controls">
                    <div class="quantity-controls">
                        <button class="quantity-btn" onclick="posManager.updateCartItemQuantity(${item.product_id}, ${item.quantity - 1})">
                            <i class="fas fa-minus"></i>
                        </button>
                        <input type="number" class="quantity-input" value="${item.quantity}" 
                               min="1"
                               onchange="posManager.updateCartItemQuantity(${item.product_id}, parseInt(this.value) || 1)">
                        <button class="quantity-btn" onclick="posManager.updateCartItemQuantity(${item.product_id}, ${item.quantity + 1})">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <button class="remove-item-btn" onclick="posManager.removeFromCart(${item.product_id})" title="Remove">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
        }).join('');
    }

    updateCartSummary() {
        // Calculate subtotal
        const subtotal = this.cart.reduce((sum, item) => sum + (item.unit_price * item.quantity), 0);
        
        // Calculate discount
        const discount = subtotal * (this.discountRate / 100);
        
        // Calculate tax
        const taxableAmount = subtotal - discount;
        const tax = taxableAmount * (this.taxRate / 100);
        
        // Calculate total
        const total = taxableAmount + tax;

        document.getElementById('cartSubtotal').textContent = app.formatCurrency(subtotal);
        document.getElementById('cartDiscount').textContent = app.formatCurrency(discount);
        document.getElementById('cartTax').textContent = app.formatCurrency(tax);
        document.getElementById('cartTotal').textContent = app.formatCurrency(total);
        
        // Update tax label with current rate
        const taxLabelElements = document.querySelectorAll('.summary-row span');
        taxLabelElements.forEach(el => {
            if (el.textContent.includes('Tax')) {
                el.textContent = `Tax (${this.taxRate}%):`;
            }
        });

        // Update payment amount if cash is selected
        if (this.paymentMethod === 'cash') {
            this.updateChangeAmount();
        }
    }

    updateCheckoutButton() {
        const button = document.getElementById('checkoutBtn');
        const total = this.getCartTotal();
        
        button.disabled = this.cart.length === 0;
        
        // Support both old and new button layouts
        if (button.classList.contains('checkout-btn-new')) {
            button.innerHTML = `
                <i class="fas fa-check-circle"></i>
                <span>${this.cart.length === 0 ? 'Cart is Empty' : 'Complete Payment - ' + app.formatCurrency(total)}</span>
                <small>F12</small>
            `;
        } else {
            button.innerHTML = `
                <i class="fas fa-check-circle"></i>
                <span>Process Payment - ${app.formatCurrency(total)}</span>
            `;
        }
    }

    getCartTotal() {
        const subtotal = this.cart.reduce((sum, item) => sum + (item.unit_price * item.quantity), 0);
        const discount = subtotal * (this.discountRate / 100);
        const taxableAmount = subtotal - discount;
        const tax = taxableAmount * (this.taxRate / 100);
        return taxableAmount + tax;
    }

    setupEventListeners() {
        // Product search
        const searchInput = document.getElementById('productSearch');
        if (searchInput) {
            searchInput.addEventListener('input', this.debounce((e) => {
                this.searchProducts(e.target.value);
            }, 300));
            
            searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.searchProducts(e.target.value);
                }
            });
        }

        // Category filters
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('category-btn') || e.target.closest('.category-btn')) {
                const btn = e.target.classList.contains('category-btn') ? e.target : e.target.closest('.category-btn');
                this.filterByCategory(btn.dataset.category);
            }
        });
        
        // Product card clicks - already setup in init() using document-level delegation

        // Payment methods
        document.addEventListener('click', (e) => {
            // Support both old (.payment-btn) and new (.payment-method-btn) class names
            const paymentBtn = e.target.closest('.payment-btn, .payment-method-btn');
            if (paymentBtn) {
                this.selectPaymentMethod(paymentBtn.dataset.method);
            }
        });

        // Payment amount input
        const paymentAmount = document.getElementById('paymentAmount');
        if (paymentAmount) {
            paymentAmount.addEventListener('input', () => {
                this.updateChangeAmount();
            });
            
            paymentAmount.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (!document.getElementById('checkoutBtn').disabled) {
                        this.processCheckout();
                    }
                }
            });
        }

        // Modal events
        document.getElementById('closeProductModal')?.addEventListener('click', () => {
            this.closeProductModal();
        });

        document.getElementById('addToCartBtn')?.addEventListener('click', () => {
            const quantity = parseInt(document.getElementById('modalQuantity').value) || 1;
            this.addToCart(null, quantity);
        });

        document.getElementById('cancelAddToCart')?.addEventListener('click', () => {
            this.closeProductModal();
        });

        // Quantity controls in modal
        document.getElementById('decreaseQuantity')?.addEventListener('click', () => {
            const input = document.getElementById('modalQuantity');
            if (input.value > 1) {
                input.value = parseInt(input.value) - 1;
            }
        });

        document.getElementById('increaseQuantity')?.addEventListener('click', () => {
            const input = document.getElementById('modalQuantity');
            const max = parseInt(input.max);
            if (parseInt(input.value) < max) {
                input.value = parseInt(input.value) + 1;
            }
        });

        // Cart controls
        document.getElementById('clearCartBtn')?.addEventListener('click', () => {
            this.clearCart();
        });

        document.getElementById('holdTransactionBtn')?.addEventListener('click', () => {
            this.holdCurrentTransaction();
        });

        document.getElementById('checkoutBtn')?.addEventListener('click', () => {
            this.processCheckout();
        });

        // Customer search
        const customerSearch = document.getElementById('customerSearch');
        if (customerSearch) {
            customerSearch.addEventListener('input', this.debounce((e) => {
                this.searchCustomers(e.target.value);
            }, 300));
        }

        // Add customer button
        document.getElementById('addCustomerBtn')?.addEventListener('click', () => {
            this.openCustomerModal();
        });

        // Close modals when clicking outside
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                this.closeProductModal();
                this.closeCustomerModal();
            }
        });
    }

    searchProducts(query) {
        if (!query.trim()) {
            this.renderProducts();
            return;
        }

        const filtered = this.products.filter(product => 
            product.name.toLowerCase().includes(query.toLowerCase()) ||
            product.sku.toLowerCase().includes(query.toLowerCase()) ||
            (product.barcode && product.barcode.toLowerCase().includes(query.toLowerCase()))
        );

        this.renderProducts(filtered);
        
        if (filtered.length === 0) {
            app.showToast(`No products found for "${query}"`, 'info');
        }
    }

    filterByCategory(categoryId) {
        // Update active category button
        document.querySelectorAll('.category-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-category="${categoryId}"]`)?.classList.add('active');

        if (categoryId === 'all') {
            this.renderProducts();
        } else {
            const filtered = this.products.filter(product => product.category_id == categoryId);
            this.renderProducts(filtered);
        }
    }

    selectPaymentMethod(method) {
        this.paymentMethod = method;
        
        // Update active payment button (support both old and new class names)
        document.querySelectorAll('.payment-btn, .payment-method-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-method="${method}"]`)?.classList.add('active');

        // Kontrol input Amount Received mengikuti desain baru (.payment-input-wrapper)
        const paymentInputWrapper = document.querySelector('.payment-input-wrapper');
        const paymentAmountInput = document.getElementById('paymentAmount');
        const changeAmountDiv = document.getElementById('changeAmount');
        const nonCashNote = document.getElementById('nonCashNote');

        if (paymentAmountInput) {
            if (method === 'cash') {
                // Aktifkan input untuk pembayaran tunai
                paymentAmountInput.disabled = false;
                paymentAmountInput.placeholder = '0';
                if (paymentInputWrapper) paymentInputWrapper.style.opacity = '';
                // Tampilkan change dan hitung ulang
                if (changeAmountDiv) changeAmountDiv.style.display = 'block';
                if (nonCashNote) nonCashNote.style.display = 'none';
                this.updateChangeAmount();
                setTimeout(() => {
                    paymentAmountInput.focus();
                }, 100);
            } else {
                // Nonaktifkan input untuk QRIS/Transfer/Card (non-cash)
                paymentAmountInput.disabled = true;
                paymentAmountInput.value = '';
                paymentAmountInput.placeholder = 'Non-cash';
                if (paymentInputWrapper) paymentInputWrapper.style.opacity = '0.7';
                // Sembunyikan change
                if (changeAmountDiv) changeAmountDiv.style.display = 'none';
                if (nonCashNote) nonCashNote.style.display = 'block';
            }
        }
    }

    updateChangeAmount() {
        const paymentAmountInput = document.getElementById('paymentAmount');
        const changeElement = document.getElementById('changeAmount');
        const changeValue = document.getElementById('changeValue');
        
        // Null check - return early if elements don't exist
        if (!paymentAmountInput || !changeElement || !changeValue) {
            return;
        }
        
        const paymentAmount = parseFloat(paymentAmountInput.value) || 0;
        const total = this.getCartTotal();
        const change = paymentAmount - total;

        if (paymentAmount > 0) {
            changeElement.style.display = 'flex';
            changeValue.textContent = app.formatCurrency(Math.max(0, change));
            
            if (change < 0) {
                changeElement.style.background = '#ef4444';
                changeValue.style.color = 'white';
            } else {
                changeElement.style.background = '#10b981';
                changeValue.style.color = 'white';
            }
        } else {
            changeElement.style.display = 'none';
        }
    }

    async processCheckout() {
        if (this.cart.length === 0) {
            app.showToast('Cart is empty', 'warning');
            return;
        }

        const total = this.getCartTotal();
        const paymentAmount = parseFloat(document.getElementById('paymentAmount').value) || 0;

        // Validate payment for cash
        if (this.paymentMethod === 'cash' && paymentAmount < total) {
            app.showToast('Payment amount is insufficient', 'error');
            document.getElementById('paymentAmount').focus();
            return;
        }

        // Confirm checkout
        const confirmMsg = `Process payment of ${app.formatCurrency(total)} via ${this.paymentMethod.toUpperCase()}?`;
        if (!confirm(confirmMsg)) {
            return;
        }

        // Disable checkout button during processing
        const checkoutBtn = document.getElementById('checkoutBtn');
        checkoutBtn.disabled = true;
        checkoutBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Processing...</span>';

        try {
            const transactionData = {
                customer_id: this.selectedCustomer?.id ? parseInt(this.selectedCustomer.id) : null,
                payment_method: this.paymentMethod,
                payment_reference: this.paymentMethod !== 'cash' ? `${this.paymentMethod.toUpperCase()}-${Date.now()}` : null,
                tax_percentage: this.taxRate,
                discount_percentage: this.discountRate,
                // Capture cash values for cash payments
                cash_received: this.paymentMethod === 'cash' ? paymentAmount : null,
                cash_change: this.paymentMethod === 'cash' ? Math.max(0, paymentAmount - total) : null,
                items: this.cart.map(item => ({
                    product_id: parseInt(item.product_id),
                    quantity: parseInt(item.quantity),
                    unit_price: parseFloat(item.unit_price),
                    total_price: parseFloat(item.total_price)
                }))
            };
            
            console.log('📤 Sending transaction data:', transactionData);
            console.log('👤 Selected customer:', this.selectedCustomer);

            const response = await app.apiCall('../api.php?controller=pos&action=createTransaction', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(transactionData)
            });

            if (response.success) {
                // Show success modal instead of simple toast
                this.showTransactionSuccessModal(response.data);
                
                // Clear cart and localStorage
                this.clearCart(true);
                this.resetForm();
                
                // Reload products to update stock and refresh stats
                await this.loadProducts();
                await this.loadQuickStats();
            } else {
                app.showToast(response.message || 'Transaction failed', 'error');
                checkoutBtn.disabled = false;
                this.updateCheckoutButton();
            }
        } catch (error) {
            console.error('Checkout failed:', error);
            app.showToast('Checkout failed: ' + error.message, 'error');
            checkoutBtn.disabled = false;
            this.updateCheckoutButton();
        }
    }

    // Show transaction success modal with receipt preview
    showTransactionSuccessModal(transactionData) {
        const transaction = transactionData.transaction || transactionData;
        const transactionId = transactionData.transaction_id || transaction.id;
        const paymentInfo = transactionData.payment_info || null;
        
        // Calculate amounts
        const total = transaction.total_amount || 0;
        const received = parseFloat(document.getElementById('paymentAmount')?.value || total);
        const change = received - total;
        
        // Build payment info section
        let paymentInfoHTML = '';
        if (paymentInfo && this.paymentMethod !== 'cash') {
            if (this.paymentMethod === 'qris') {
                const qrImage = (paymentInfo && paymentInfo.qr_code_url) ? paymentInfo.qr_code_url : '/assets/img/qris-gopay.svg';
                paymentInfoHTML = `
                    <div style="background: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; border: 2px solid #e5e7eb;">
                        <h3 style="color: #374151; margin-bottom: 1rem; font-size: 18px;">
                            <i class="fas fa-qrcode"></i> QR Code Payment
                        </h3>
                        <div style="text-align: center; margin-bottom: 1rem;">
                            <img src="${Utils.resolveImageUrl(qrImage)}" alt="QR Code" onerror="this.src='/assets/img/qris-gopay.svg'" 
                                 style="max-width: 250px; width: 100%; border: 2px solid #e5e7eb; border-radius: 8px; padding: 1rem; background: white;">
                        </div>
                        <div style="background: #f3f4f6; padding: 1rem; border-radius: 6px; font-size: 14px; color: #6b7280;">
                            <strong>Instruksi:</strong><br>
                            1. Buka aplikasi e-wallet atau mobile banking<br>
                            2. Pilih menu Scan QRIS<br>
                            3. Scan QR code di atas<br>
                            4. Konfirmasi pembayaran
                        </div>
                        ${(paymentInfo && paymentInfo.expired_at) ? `
                        <div style="margin-top: 1rem; text-align: center; font-size: 12px; color: #ef4444;">
                            <i class="fas fa-clock"></i> Expires: ${new Date(paymentInfo.expired_at).toLocaleString('id-ID')}
                        </div>
                        ` : ''}
                    </div>
                `;
            } else if (this.paymentMethod === 'transfer') {
                paymentInfoHTML = `
                    <div style="background: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; border: 2px solid #e5e7eb;">
                        <h3 style="color: #374151; margin-bottom: 1rem; font-size: 18px;">
                            <i class="fas fa-university"></i> Bank Transfer Information
                        </h3>
                        <div style="background: #f3f4f6; padding: 1.5rem; border-radius: 6px;">
                            <div style="margin-bottom: 1rem;">
                                <div style="font-size: 12px; color: #6b7280; margin-bottom: 0.25rem;">Bank Name</div>
                                <div style="font-size: 16px; font-weight: 600; color: #1f2937;">${paymentInfo.bank_name || 'N/A'}</div>
                            </div>
                            <div style="margin-bottom: 1rem;">
                                <div style="font-size: 12px; color: #6b7280; margin-bottom: 0.25rem;">Account Name</div>
                                <div style="font-size: 16px; font-weight: 600; color: #1f2937;">${paymentInfo.account_name || 'N/A'}</div>
                            </div>
                            <div style="margin-bottom: 1rem;">
                                <div style="font-size: 12px; color: #6b7280; margin-bottom: 0.25rem;">Account Number</div>
                                <div style="font-size: 16px; font-weight: 600; color: #1f2937; font-family: monospace;">${paymentInfo.account_number || 'N/A'}</div>
                            </div>
                            ${paymentInfo.virtual_account ? `
                            <div style="margin-bottom: 1rem;">
                                <div style="font-size: 12px; color: #6b7280; margin-bottom: 0.25rem;">Virtual Account</div>
                                <div style="font-size: 16px; font-weight: 600; color: #1f2937; font-family: monospace;">${paymentInfo.virtual_account}</div>
                            </div>
                            ` : ''}
                            <div style="margin-bottom: 1rem;">
                                <div style="font-size: 12px; color: #6b7280; margin-bottom: 0.25rem;">Amount</div>
                                <div style="font-size: 18px; font-weight: 700; color: #10b981;">${app.formatCurrency(paymentInfo.transfer_amount || total)}</div>
                            </div>
                            ${paymentInfo.reference_number ? `
                            <div style="margin-bottom: 1rem;">
                                <div style="font-size: 12px; color: #6b7280; margin-bottom: 0.25rem;">Reference Number</div>
                                <div style="font-size: 14px; color: #6b7280; font-family: monospace;">${paymentInfo.reference_number}</div>
                            </div>
                            ` : ''}
                        </div>
                        ${paymentInfo.expired_at ? `
                        <div style="margin-top: 1rem; text-align: center; font-size: 12px; color: #ef4444;">
                            <i class="fas fa-clock"></i> Expires: ${new Date(paymentInfo.expired_at).toLocaleString('id-ID')}
                        </div>
                        ` : ''}
                    </div>
                `;
            } else if (this.paymentMethod === 'card') {
                paymentInfoHTML = `
                    <div style="background: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; border: 2px solid #e5e7eb;">
                        <h3 style="color: #374151; margin-bottom: 1rem; font-size: 18px;">
                            <i class="fas fa-credit-card"></i> Card Payment
                        </h3>
                        <div style="background: #f3f4f6; padding: 1.5rem; border-radius: 6px;">
                            ${paymentInfo.reference_number ? `
                            <div style="margin-bottom: 1rem;">
                                <div style="font-size: 12px; color: #6b7280; margin-bottom: 0.25rem;">Reference Number</div>
                                <div style="font-size: 16px; font-weight: 600; color: #1f2937; font-family: monospace;">${paymentInfo.reference_number}</div>
                            </div>
                            ` : ''}
                            <div style="font-size: 14px; color: #6b7280;">
                                <strong>Instruksi:</strong><br>
                                1. Swipe atau insert kartu debit/kredit di terminal<br>
                                2. Masukkan PIN (jika diperlukan)<br>
                                3. Tunggu konfirmasi pembayaran<br>
                                4. Ambil struk jika berhasil
                            </div>
                            ${paymentInfo.payment_url ? `
                            <div style="margin-top: 1rem; text-align: center;">
                                <a href="${paymentInfo.payment_url}" target="_blank" 
                                   class="btn btn-primary" style="padding: 0.75rem 1.5rem;">
                                    <i class="fas fa-external-link-alt"></i> Complete Payment Online
                                </a>
                            </div>
                            ` : ''}
                        </div>
                        ${paymentInfo.expired_at ? `
                        <div style="margin-top: 1rem; text-align: center; font-size: 12px; color: #ef4444;">
                            <i class="fas fa-clock"></i> Expires: ${new Date(paymentInfo.expired_at).toLocaleString('id-ID')}
                        </div>
                        ` : ''}
                    </div>
                `;
            }
        }
        
        // Create modal
        const modal = document.createElement('div');
        modal.id = 'transactionSuccessModal';
        modal.className = 'modal show';
        modal.style.cssText = `
            display: flex !important;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.7);
            z-index: 9999;
        `;
        
        modal.innerHTML = `
            <div class="modal-content" style="max-width: 600px; text-align: center; animation: slideUp 0.3s ease;">
                <div style="padding: 2rem;">
                    <!-- Waiting Icon -->
                    <div id="statusIcon" style="margin-bottom: 1.5rem;">
                        <div style="width: 80px; height: 80px; background: #f59e0b; border-radius: 50%; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-spinner fa-spin" style="font-size: 34px; color: white;"></i>
                        </div>
                    </div>
                    
                    <!-- Title -->
                    <h2 id="statusTitle" style="color: #f59e0b; margin-bottom: 0.5rem; font-size: 24px;">
                        <i class="fas fa-hourglass-half"></i> Waiting Payment
                    </h2>
                    <p id="statusSubtitle" style="color: #6b7280; margin-bottom: 2rem;">Menunggu konfirmasi kasir/admin: Payment Accepted</p>
                    
                    <!-- Transaction Info -->
                    <div style="background: #f9fafb; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; text-align: left;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem;">
                            <span style="color: #6b7280;">Transaction ID:</span>
                            <strong style="font-family: monospace;">#${transactionId}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem;">
                            <span style="color: #6b7280;">Date:</span>
                            <strong>${new Date().toLocaleString('id-ID')}</strong>
                        </div>
                        ${this.selectedCustomer ? `
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem;">
                            <span style="color: #6b7280;">Customer:</span>
                            <strong>${this.selectedCustomer.customer_code ? '[' + this.selectedCustomer.customer_code + '] ' : ''}${this.selectedCustomer.name}</strong>
                        </div>
                        ` : ''}
                    </div>
                    
                    <!-- Amount Info -->
                    <div style="background: linear-gradient(135deg, #16a34a 0%, #15803d 100%); padding: 2rem; border-radius: 8px; color: white; margin-bottom: 1.5rem;">
                        <div style="font-size: 14px; opacity: 0.9; margin-bottom: 0.5rem;">TOTAL AMOUNT</div>
                        <div style="font-size: 48px; font-weight: bold; margin-bottom: 1rem;">${app.formatCurrency(total)}</div>
                        
                        <div style="border-top: 1px solid rgba(255,255,255,0.3); padding-top: 1rem; display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; font-size: 14px;">
                            <div>
                                <div style="opacity: 0.8;">Payment Method</div>
                                <div style="font-weight: 600; text-transform: uppercase; margin-top: 0.25rem;">
                                    ${this.paymentMethod === 'cash' ? '💵 ' : 
                                      this.paymentMethod === 'card' ? '💳 ' : 
                                      this.paymentMethod === 'qris' ? '📱 ' : '🔄 '}
                                    ${this.paymentMethod}
                                </div>
                            </div>
                            ${this.paymentMethod === 'cash' ? `
                            <div>
                                <div style="opacity: 0.8;">Change</div>
                                <div style="font-weight: 600; margin-top: 0.25rem;">${app.formatCurrency(change)}</div>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                    
                    <!-- Payment Info Section (for QRIS, Transfer, Card) -->
                    ${paymentInfoHTML}
                    
                    <!-- Action Buttons -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 0.75rem; margin-bottom: 1rem;">
                        <button id="acceptPaymentBtn" class="btn btn-success" style="padding: 1rem; font-size: 14px;">
                            <i class="fas fa-check"></i><br>
                            <span style="font-size: 12px;">Payment Accepted</span>
                        </button>
                        <button onclick="posManager.printReceipt(${transactionId})" class="btn btn-primary" style="padding: 1rem; font-size: 14px;" disabled>
                            <i class="fas fa-print"></i><br>
                            <span style="font-size: 12px;">Print Receipt</span>
                        </button>
                        <button onclick="posManager.startNewTransaction()" class="btn btn-success" style="padding: 1rem; font-size: 14px;" disabled>
                            <i class="fas fa-plus-circle"></i><br>
                            <span style="font-size: 12px;">New Sale</span>
                        </button>
                        <button onclick="posManager.viewTransactionDetails(${transactionId})" class="btn btn-secondary" style="padding: 1rem; font-size: 14px;" disabled>
                            <i class="fas fa-file-invoice"></i><br>
                            <span style="font-size: 12px;">View Details</span>
                        </button>
                    </div>
                    
                    <!-- Close Button -->
                    <button onclick="posManager.closeSuccessModal()" class="btn btn-link" style="color: #6b7280; font-size: 14px;">
                        Close (ESC)
                    </button>
                </div>
            </div>
            
            <style>
                @keyframes slideUp {
                    from {
                        opacity: 0;
                        transform: translateY(30px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
                
                @keyframes scaleIn {
                    0% {
                        transform: scale(0);
                    }
                    50% {
                        transform: scale(1.2);
                    }
                    100% {
                        transform: scale(1);
                    }
                }
            </style>
        `;
        
        document.body.appendChild(modal);
        
        // Close on ESC
        const escHandler = (e) => {
            if (e.key === 'Escape') {
                this.closeSuccessModal();
                document.removeEventListener('keydown', escHandler);
            }
        };
        document.addEventListener('keydown', escHandler);
        
        // Bind accept payment
        const acceptBtn = modal.querySelector('#acceptPaymentBtn');
        const printBtn = modal.querySelector('button.btn.btn-primary');
        const newBtn = modal.querySelectorAll('button.btn.btn-success')[1];
        const viewBtn = modal.querySelector('button.btn.btn-secondary');
        const titleEl = modal.querySelector('#statusTitle');
        const subEl = modal.querySelector('#statusSubtitle');
        const iconEl = modal.querySelector('#statusIcon');
        if (acceptBtn) {
            acceptBtn.addEventListener('click', async ()=>{
                try {
                    acceptBtn.disabled = true;
                    acceptBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><br><span style="font-size:12px;">Processing...</span>';
                    const resp = await app.apiCall('../api.php?controller=pos&action=confirmPayment', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ transaction_id: transactionId })
                    });
                    if (resp && resp.success) {
                        // Update UI to success state
                        if (iconEl) iconEl.innerHTML = '<div style="width:80px;height:80px;background:#10b981;border-radius:50%;margin:0 auto;display:flex;align-items:center;justify-content:center;"><i class="fas fa-check" style="font-size:40px;color:white;"></i></div>';
                        if (titleEl) { titleEl.innerHTML = '<i class="fas fa-check-circle"></i> Transaction Successful!'; titleEl.style.color = '#10b981'; }
                        if (subEl) subEl.textContent = 'Your transaction has been completed';
                        // Enable buttons
                        [printBtn, newBtn, viewBtn].forEach(b => { if (b) b.disabled = false; });
                        // Play success sound
                        posManager.playSuccessSound();
                    } else {
                        acceptBtn.disabled = false;
                        acceptBtn.innerHTML = '<i class="fas fa-check"></i><br><span style="font-size:12px;">Payment Accepted</span>';
                        app.showToast(resp?.message || 'Failed to confirm payment', 'error');
                    }
                } catch (e) {
                    acceptBtn.disabled = false;
                    acceptBtn.innerHTML = '<i class="fas fa-check"></i><br><span style="font-size:12px;">Payment Accepted</span>';
                    app.showToast('Failed to confirm payment', 'error');
                }
            });
        }
    }

    // Print receipt
    printReceipt(transactionId) {
        window.open(`receipt.php?id=${transactionId}`, '_blank', 'width=400,height=600');
        this.closeSuccessModal();
    }

    // Start new transaction
    startNewTransaction() {
        this.closeSuccessModal();
        // Focus back to product search
        setTimeout(() => {
            document.getElementById('productSearch')?.focus();
            app.showToast('Ready for next transaction', 'info');
        }, 300);
    }

    // View transaction details
    viewTransactionDetails(transactionId) {
        this.closeSuccessModal();
        window.location.href = `transactions.php?id=${transactionId}`;
    }

    // Close success modal
    closeSuccessModal() {
        const modal = document.getElementById('transactionSuccessModal');
        if (modal) {
            modal.style.animation = 'fadeOut 0.3s ease';
            setTimeout(() => modal.remove(), 300);
        }
    }

    // Play success sound (optional)
    playSuccessSound() {
        // Optional: Play a pleasant 'ding' sound
        // const audio = new Audio('/assets/sounds/success.mp3');
        // audio.play().catch(() => {}); // Ignore if sound fails
    }

    resetForm() {
        this.cart = [];
        this.selectedCustomer = null;
        this.paymentMethod = 'cash';
        this.discountRate = 0;
        
        // Safely reset form elements (with null checks)
        const paymentAmountInput = document.getElementById('paymentAmount');
        const customerSearchInput = document.getElementById('customerSearch');
        const changeAmountDiv = document.getElementById('changeAmount');
        const selectedCustomerDiv = document.getElementById('selectedCustomer');
        const productSearchInput = document.getElementById('productSearch');
        
        if (paymentAmountInput) paymentAmountInput.value = '';
        if (customerSearchInput) customerSearchInput.value = '';
        if (changeAmountDiv) changeAmountDiv.style.display = 'none';
        if (selectedCustomerDiv) selectedCustomerDiv.style.display = 'none';
        
        this.selectPaymentMethod('cash');
        this.updateCartDisplay();
        
        // Focus back to product search
        if (productSearchInput) {
            setTimeout(() => {
                productSearchInput.focus();
            }, 500);
        }
    }

    async searchCustomers(query) {
        if (!query.trim() || query.length < 2) {
            this.hideCustomerDropdown();
            return;
        }

        try {
            const response = await app.apiCall(`../api.php?controller=pos&action=searchCustomers&q=${encodeURIComponent(query)}`);
            if (response.success && response.data && response.data.length > 0) {
                this.showCustomerDropdown(response.data);
            } else {
                this.hideCustomerDropdown();
            }
        } catch (error) {
            console.error('Customer search failed:', error);
            this.hideCustomerDropdown();
        }
    }

    showCustomerDropdown(customers) {
        // Remove existing dropdown
        this.hideCustomerDropdown();
        
        const searchContainer = document.querySelector('.customer-search');
        const dropdown = document.createElement('div');
        dropdown.className = 'customer-dropdown';
        dropdown.style.cssText = `
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid var(--gray-300);
            border-radius: var(--border-radius);
            max-height: 200px;
            overflow-y: auto;
            box-shadow: var(--shadow-md);
            z-index: 100;
            margin-top: 4px;
        `;
        
        customers.forEach(customer => {
            const item = document.createElement('div');
            item.className = 'customer-dropdown-item';
            item.style.cssText = `
                padding: 0.75rem;
                cursor: pointer;
                border-bottom: 1px solid var(--gray-200);
                transition: background 0.2s;
            `;
            
            // Format customer info dengan code
            const customerCode = customer.customer_code ? `<span style="color: var(--primary); font-weight: 500;">[${customer.customer_code}]</span> ` : '';
            const phoneInfo = customer.phone ? customer.phone : '';
            const emailInfo = customer.email ? (phoneInfo ? ' • ' + customer.email : customer.email) : '';
            const cityInfo = customer.city ? (phoneInfo || emailInfo ? ' • ' + customer.city : customer.city) : '';
            
            item.innerHTML = `
                <div style="font-weight: 600; color: var(--gray-800);">
                    ${customerCode}${customer.name}
                </div>
                <div style="font-size: 0.75rem; color: var(--gray-500);">
                    ${phoneInfo}${emailInfo}${cityInfo}
                </div>
            `;
            item.addEventListener('mouseenter', () => {
                item.style.background = 'var(--gray-100)';
            });
            item.addEventListener('mouseleave', () => {
                item.style.background = 'white';
            });
            item.addEventListener('click', () => {
                this.selectCustomer(customer);
                this.hideCustomerDropdown();
            });
            dropdown.appendChild(item);
        });
        
        searchContainer.style.position = 'relative';
        searchContainer.appendChild(dropdown);
    }

    hideCustomerDropdown() {
        const existing = document.querySelector('.customer-dropdown');
        if (existing) {
            existing.remove();
        }
    }

    selectCustomer(customer) {
        console.log('🔍 Selecting customer:', customer);
        
        // Ensure customer ID is integer
        if (customer && customer.id) {
            customer.id = parseInt(customer.id);
        }
        
        this.selectedCustomer = customer;
        
        const selectedElement = document.getElementById('selectedCustomer');
        
        // Format display dengan customer code
        const displayName = customer.customer_code ? `[${customer.customer_code}] ${customer.name}` : customer.name;
        const displayInfo = customer.phone || customer.email || customer.city || '';
        
        selectedElement.querySelector('.customer-name').textContent = displayName;
        selectedElement.querySelector('.customer-phone').textContent = displayInfo;
        selectedElement.style.display = 'flex';
        
        document.getElementById('customerSearch').value = '';
        
        console.log('✅ Customer selected - ID:', this.selectedCustomer.id, 'Code:', customer.customer_code, 'Name:', this.selectedCustomer.name);
        app.showToast(`Customer selected: ${displayName}`, 'success');
    }

    // Open customer modal
    openCustomerModal() {
        document.getElementById('customerModal')?.classList.add('show');
        setTimeout(() => {
            document.getElementById('customerName')?.focus();
        }, 100);
    }

    // Close customer modal
    closeCustomerModal() {
        document.getElementById('customerModal')?.classList.remove('show');
        document.getElementById('customerForm')?.reset();
    }

    // Keyboard shortcuts
    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ignore if typing in input/textarea (except product search for barcode)
            if ((e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') && 
                e.target.id !== 'productSearch') {
                return;
            }

            // F1 - Focus product search
            if (e.key === 'F1') {
                e.preventDefault();
                document.getElementById('productSearch').focus();
            }

            // F2 - Clear cart
            if (e.key === 'F2') {
                e.preventDefault();
                this.clearCart();
            }

            // F3 - Hold transaction
            if (e.key === 'F3') {
                e.preventDefault();
                this.holdCurrentTransaction();
            }

            // F4 - Cash payment
            if (e.key === 'F4') {
                e.preventDefault();
                this.selectPaymentMethod('cash');
            }

            // F5 - Card payment
            if (e.key === 'F5') {
                e.preventDefault();
                this.selectPaymentMethod('card');
            }

            // F6 - QRIS payment
            if (e.key === 'F6') {
                e.preventDefault();
                this.selectPaymentMethod('qris');
            }

            // F7 - Transfer payment
            if (e.key === 'F7') {
                e.preventDefault();
                this.selectPaymentMethod('transfer');
            }

            // F12 - Process payment
            if (e.key === 'F12') {
                e.preventDefault();
                if (!document.getElementById('checkoutBtn').disabled) {
                    this.processCheckout();
                }
            }

            // ESC - Close modals
            if (e.key === 'Escape') {
                this.closeProductModal();
                this.closeCustomerModal();
                // Close shortcuts modal if open
                const shortcutsModal = document.getElementById('shortcutsModal');
                if (shortcutsModal) {
                    shortcutsModal.classList.remove('show');
                }
            }
        });
    }

    setupCustomerEventListeners() {
        // Listen for customer changes from Customer Management page
        window.addEventListener('storage', (e) => {
            if (e.key === 'customer_changed') {
                console.log('🔔 Customer data updated in another tab');
                const change = JSON.parse(e.newValue || '{}');
                
                // Show notification
                app.showToast(`Customer ${change.action.toLowerCase()}`, 'info');
                
                // If the selected customer was modified/deleted, refresh selection
                if (this.selectedCustomer && this.selectedCustomer.id === change.customerId) {
                    if (change.action === 'Deleted') {
                        this.selectedCustomer = null;
                        this.updateCustomerDisplay();
                        app.showToast('Selected customer was deleted', 'warning');
                    } else if (change.action === 'Updated') {
                        // Optionally refresh customer data
                        app.showToast('Selected customer was updated', 'info');
                    }
                }
            }
            
            // Listen for customer cache updates
            if (e.key === 'customers_cache') {
                console.log('🔄 Customer cache updated');
                // Customer list in search was updated
            }
        });
        
        // Listen for custom customer events
        window.addEventListener('customerCreated', (e) => {
            console.log('🆕 New customer created:', e.detail);
            app.showToast('New customer added to database', 'success');
        });
        
        window.addEventListener('customerUpdated', (e) => {
            console.log('✏️ Customer updated:', e.detail);
            if (this.selectedCustomer && this.selectedCustomer.id === e.detail.customerId) {
                app.showToast('Selected customer was updated', 'info');
            }
        });
        
        window.addEventListener('customerDeleted', (e) => {
            console.log('🗑️ Customer deleted:', e.detail);
            if (this.selectedCustomer && this.selectedCustomer.id === e.detail.customerId) {
                this.selectedCustomer = null;
                this.updateCustomerDisplay();
                app.showToast('Selected customer was removed', 'warning');
            }
        });
    }

    // Hold current transaction
    async holdCurrentTransaction() {
        if (this.cart.length === 0) {
            app.showToast('Cart is empty', 'warning');
            return;
        }

        const notes = prompt('Enter note for this held transaction (optional):');
        if (notes === null) return; // User cancelled

        try {
            const response = await app.apiCall('../api.php?controller=pos&action=holdTransaction', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    cart_data: this.cart,
                    customer_id: this.selectedCustomer?.id || null,
                    notes: notes || `Held at ${new Date().toLocaleTimeString()}`
                })
            });

            if (response.success) {
                app.showToast('✅ Transaction held successfully', 'success');
                this.clearCart(true);
                this.resetForm();
            } else {
                app.showToast(response.message || 'Failed to hold transaction', 'error');
            }
        } catch (error) {
            console.error('Failed to hold transaction:', error);
            app.showToast('Failed to hold transaction', 'error');
        }
    }

    // Save cart to localStorage
    saveCartToStorage() {
        if (this.cart.length > 0) {
            localStorage.setItem('pos_cart_backup', JSON.stringify({
                cart: this.cart,
                customer: this.selectedCustomer,
                payment_method: this.paymentMethod,
                discount_rate: this.discountRate,
                timestamp: new Date().toISOString()
            }));
        } else {
            localStorage.removeItem('pos_cart_backup');
        }
    }

    // Load cart from localStorage
    loadCartFromStorage() {
        const backup = localStorage.getItem('pos_cart_backup');
        if (backup) {
            try {
                const data = JSON.parse(backup);
                const backupTime = new Date(data.timestamp);
                const hoursSinceBackup = (new Date() - backupTime) / (1000 * 60 * 60);
                
                // Only restore if backup is less than 24 hours old
                if (hoursSinceBackup < 24 && data.cart && data.cart.length > 0) {
                    const restore = confirm('Found unsaved cart from previous session. Restore it?');
                    if (restore) {
                        this.cart = data.cart;
                        // Ensure customer ID is integer
                        if (data.customer && data.customer.id) {
                            data.customer.id = parseInt(data.customer.id);
                        }
                        this.selectedCustomer = data.customer;
                        this.paymentMethod = data.payment_method || 'cash';
                        this.discountRate = data.discount_rate || 0;
                        this.selectPaymentMethod(this.paymentMethod);
                        app.showToast('Cart restored from backup', 'info');
                    } else {
                        localStorage.removeItem('pos_cart_backup');
                    }
                } else {
                    localStorage.removeItem('pos_cart_backup');
                }
            } catch (error) {
                console.error('Failed to restore cart:', error);
                localStorage.removeItem('pos_cart_backup');
            }
        }
    }

    // Clear cart
    clearCart(silent = false) {
        if (!silent && this.cart.length === 0) return;
        
        if (silent || confirm('Are you sure you want to clear the cart?')) {
            this.cart = [];
            this.updateCartDisplay();
            localStorage.removeItem('pos_cart_backup');
            if (!silent) {
                app.showToast('Cart cleared', 'info');
            }
        }
    }

    // Utility: Debounce function
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}

// Initialize POS when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.posManager = new POSManager();
    
    // Log keyboard shortcuts
    console.log('%c🎮 POS Keyboard Shortcuts:', 'font-weight: bold; font-size: 14px; color: #16a34a;');
    console.log('F1  - Focus Product Search');
    console.log('F2  - Clear Cart');
    console.log('F3  - Hold Transaction');
    console.log('F4  - Cash Payment');
    console.log('F5  - Card Payment');
    console.log('F6  - QRIS Payment');
    console.log('F7  - Transfer Payment');
    console.log('F12 - Process Payment');
    console.log('ESC - Close Modals');
});
