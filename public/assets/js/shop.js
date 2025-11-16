/**
 * Bytebalok Shop JavaScript
 * Handles customer-facing shop functionality
 */

// Configuration
const API_BASE = '../api.php';  // Use API router instead of direct controller access
const STORAGE_KEY = 'bytebalok_cart';

// Utility Functions
const Utils = {
    formatCurrency: (amount) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(amount);
    },

    formatDate: (date) => {
        return new Date(date).toLocaleDateString('id-ID', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    },

    // Build absolute URL from relative upload paths like "uploads/products/..."
    buildAbsoluteUrl: (path) => {
        if (!path) return null;
        try {
            const trimmed = String(path).trim();
            if (/^https?:\/\//i.test(trimmed)) return trimmed; // already absolute
            if (trimmed.startsWith('/')) return `${window.location.origin}${trimmed}`;
            // Normalize to "/<path>"
            const normalized = trimmed.replace(/^\/+/, '');
            return `${window.location.origin}/${normalized}`;
        } catch (_) {
            return null;
        }
    },

    // Resolve image URL with placeholder fallback
    resolveImageUrl: (path, placeholder = '../assets/img/product-placeholder.jpg') => {
        const abs = Utils.buildAbsoluteUrl(path);
        return abs || placeholder;
    },

    showToast: (message, type = 'info') => {
        const toast = document.getElementById('toast');
        if (!toast) return;

        toast.textContent = message;
        toast.className = `toast toast-${type} show`;

        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    },

    showAdvancedToast: (message, type = 'info', icon = null) => {
        // Create toast if doesn't exist
        let toast = document.getElementById('toast-advanced');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'toast-advanced';
            toast.className = 'toast-advanced';
            document.body.appendChild(toast);
        }

        const icons = {
            success: 'fa-check-circle',
            error: 'fa-times-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };

        toast.innerHTML = `
            <div class="toast-content-advanced">
                <i class="fas ${icon || icons[type]}"></i>
                <span>${message}</span>
            </div>
        `;
        
        toast.className = `toast-advanced toast-${type} show`;
        
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    },

    animateCartIcon: (buttonElement) => {
        // Create flying icon
        const rect = buttonElement.getBoundingClientRect();
        const cartIcon = document.querySelector('.cart-button-clean');
        if (!cartIcon) return;
        
        const cartRect = cartIcon.getBoundingClientRect();
        
        // Create clone
        const clone = document.createElement('div');
        clone.innerHTML = '<i class="fas fa-shopping-cart"></i>';
        clone.style.cssText = `
            position: fixed;
            left: ${rect.left + rect.width / 2}px;
            top: ${rect.top + rect.height / 2}px;
            width: 30px;
            height: 30px;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            pointer-events: none;
            transition: all 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        `;
        
        document.body.appendChild(clone);
        
        // Animate
        setTimeout(() => {
            clone.style.left = `${cartRect.left + cartRect.width / 2}px`;
            clone.style.top = `${cartRect.top + cartRect.height / 2}px`;
            clone.style.transform = 'scale(0.5)';
            clone.style.opacity = '0';
        }, 10);
        
        setTimeout(() => {
            clone.remove();
            // Bounce cart icon
            cartIcon.style.transform = 'scale(1.2)';
            setTimeout(() => {
                cartIcon.style.transform = 'scale(1)';
            }, 200);
        }, 600);
    },

    apiCall: async (endpoint, options = {}) => {
        try {
            const url = API_BASE + endpoint;
            console.log('🌐 API Call:', url);
            
            const response = await fetch(url, options);
            
            // Get response text first to handle empty or invalid JSON
            const text = await response.text();
            console.log('📥 API Response (raw):', text.substring(0, 200));
            
            // Check if response is empty
            if (!text || text.trim() === '') {
                throw new Error('Empty response from server');
            }
            
            // Try to parse as JSON
            let data;
            try {
                data = JSON.parse(text);
            } catch (jsonError) {
                console.error('❌ JSON Parse Error:', jsonError);
                console.error('📄 Response text:', text);
                throw new Error(`Invalid JSON response: ${jsonError.message}. Response: ${text.substring(0, 100)}`);
            }

            if (!response.ok) {
                throw new Error(data.error || data.message || `Request failed with status ${response.status}`);
            }

            return data;
        } catch (error) {
            console.error('❌ API Error:', error);
            console.error('📍 Endpoint:', endpoint);
            throw error;
        }
    }
};

// Ephemeral success badge near the clicked button
function showAddBadge(buttonElement, message = 'Ditambahkan') {
    try {
        const card = buttonElement.closest('.product-card') || document.body;
        const badge = document.createElement('div');
        badge.className = 'added-badge';
        badge.innerHTML = `<i class="fas fa-check"></i> ${message}`;
        card.appendChild(badge);
        const rect = buttonElement.getBoundingClientRect();
        const x = rect.left + window.scrollX - 12;
        const y = rect.top + window.scrollY - 12;
        badge.style.left = x + 'px';
        badge.style.top = y + 'px';
        setTimeout(() => { badge.classList.add('hide'); setTimeout(() => badge.remove(), 400); }, 900);
    } catch (e) {}
}

// Shopping Cart Manager
const ShopCart = {
    getCart: () => {
        const cart = localStorage.getItem(STORAGE_KEY);
        return cart ? JSON.parse(cart) : [];
    },

    saveCart: (cart) => {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(cart));
        ShopCart.updateCartCount();
        // Reflect UI changes when on cart page
        try { ShopCart.updateCartDisplay(); } catch (e) { /* no-op */ }
    },

    addToCart: (product, quantity = 1) => {
        let cart = ShopCart.getCart();
        const existingItem = cart.find(item => item.id === product.id);

        // Normalize and clamp quantity to stock
        const maxQty = (typeof product.stock_quantity === 'number' && product.stock_quantity > 0)
            ? parseInt(product.stock_quantity, 10)
            : Number.MAX_SAFE_INTEGER;
        quantity = Math.max(1, parseInt(quantity, 10) || 1);

        if (existingItem) {
            existingItem.quantity = Math.min((existingItem.quantity || 0) + quantity, maxQty);
            existingItem.stock_quantity = product.stock_quantity;
        } else {
            cart.push({
                id: product.id,
                name: product.name,
                price: product.price,
                image: product.image,
                quantity: Math.min(quantity, maxQty),
                stock_quantity: product.stock_quantity
            });
        }

        ShopCart.saveCart(cart);
        // Immediately reflect changes in UI (e.g., when on cart page)
        try { ShopCart.updateCartDisplay(); } catch (e) { /* noop */ }
    },

    updateQuantity: (productId, quantity) => {
        let cart = ShopCart.getCart();
        const item = cart.find(item => item.id === productId);

        if (item) {
            if (quantity <= 0) {
                cart = cart.filter(item => item.id !== productId);
            } else {
                item.quantity = quantity;
            }
            ShopCart.saveCart(cart);
            // Soft animation feedback on row
            try {
                const row = document.querySelector(`.cart-item[data-id="${productId}"]`);
                if (row) {
                    row.classList.add('pulse');
                    setTimeout(() => row.classList.remove('pulse'), 350);
                }
            } catch (e) {}
        }
    },

    removeFromCart: (productId) => {
        // Animate fade-out then remove
        const row = document.querySelector(`.cart-item[data-id="${productId}"]`);
        if (row) {
            row.classList.add('fade-out');
            setTimeout(() => {
                let cart = ShopCart.getCart();
                cart = cart.filter(item => item.id !== productId);
                ShopCart.saveCart(cart);
                Utils.showToast('Item removed from cart', 'info');
            }, 180);
        } else {
            let cart = ShopCart.getCart();
            cart = cart.filter(item => item.id !== productId);
            ShopCart.saveCart(cart);
            Utils.showToast('Item removed from cart', 'info');
        }
    },

    clearCart: () => {
        localStorage.removeItem(STORAGE_KEY);
        ShopCart.updateCartCount();
    },

    getTotal: () => {
        const cart = ShopCart.getCart();
        return cart.reduce((total, item) => total + (item.price * item.quantity), 0);
    },

    getTax: () => {
        // Compute tax based on public shop settings
        const subtotal = ShopCart.getTotal();
        // Apply discount before tax if promo exists (align with POS behavior)
        let discount = 0;
        if (window.PromoCodeManager && typeof PromoCodeManager.calculateDiscount === 'function') {
            discount = PromoCodeManager.calculateDiscount(subtotal) || 0;
        }
        const taxableAmount = Math.max(0, subtotal - discount);

        if (window.ShopSettings && ShopSettings.enableTaxShop) {
            const rate = parseFloat(ShopSettings.taxRateShop) || 0;
            return taxableAmount * (rate / 100);
        }
        return 0;
    },

    getGrandTotal: () => {
        return ShopCart.getTotal() + ShopCart.getTax();
    },

    updateCartCount: () => {
        const cart = ShopCart.getCart();
        const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
        const cartCountElements = document.querySelectorAll('#cartCount, .cart-count');
        
        cartCountElements.forEach(element => {
            element.textContent = totalItems;
            element.style.display = totalItems > 0 ? 'inline-block' : 'none';
        });
    },

    loadCart: () => {
        const cart = ShopCart.getCart();
        const container = document.getElementById('cartItemsContainer');
        const emptyCart = document.getElementById('cartEmpty');
        const summary = document.getElementById('cartSummary');

        if (!container) return;

        if (cart.length === 0) {
            if (emptyCart) emptyCart.style.display = 'block';
            if (summary) summary.style.display = 'none';
            container.innerHTML = '';
            return;
        }

        if (emptyCart) emptyCart.style.display = 'none';
        if (summary) summary.style.display = 'block';

        container.innerHTML = cart.map(item => `
            <div class="cart-item" data-id="${item.id}">
                <img src="${Utils.resolveImageUrl(item.image, '../assets/img/no-image.svg')}" 
                     alt="${item.name}" 
                     class="cart-item-image">
                
                <div class="cart-item-info">
                    <h3 class="cart-item-name">${item.name}</h3>
                    <p class="cart-item-price">${Utils.formatCurrency(item.price)}</p>
                    
                    <div class="cart-item-quantity">
                    <button class="quantity-btn" aria-label="Kurangi jumlah" onclick="ShopCart.updateQuantity(${item.id}, ${item.quantity - 1})">
                        <i class="fas fa-minus"></i>
                    </button>
                        <input type="number" aria-label="Jumlah" value="${item.quantity}" min="1" max="${item.stock_quantity || item.stock || ''}" 
                               onchange="ShopCart.updateQuantity(${item.id}, parseInt(this.value))">
                        <button class="quantity-btn" aria-label="Tambah jumlah" onclick="ShopCart.updateQuantity(${item.id}, ${item.quantity + 1})">
                        <i class="fas fa-plus"></i>
                    </button>
                    </div>
                </div>
                
                <div class="cart-item-actions">
                    <div class="cart-item-total">${Utils.formatCurrency(item.price * item.quantity)}</div>
                    <button class="remove-item-btn" onclick="ShopCart.removeFromCart(${item.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `).join('');

        ShopCart.updateSummary();
    },

    updateSummary: () => {
        const subtotal = ShopCart.getTotal();
        const tax = ShopCart.getTax();
        const total = ShopCart.getGrandTotal();

        // Update elements that exist
        const updateElement = (id, value) => {
            const element = document.getElementById(id);
            if (element) element.textContent = Utils.formatCurrency(value);
        };

        updateElement('summarySubtotal', subtotal);
        updateElement('summaryTax', tax);
        updateElement('summaryShipping', 0);
        updateElement('summaryTotal', total);

        updateElement('checkoutSubtotal', subtotal);
        updateElement('checkoutTax', tax);
        updateElement('checkoutShipping', 0);
        updateElement('checkoutTotal', total);

        // Update tax labels based on ShopSettings
        const summaryTaxLabel = document.getElementById('summaryTaxLabel');
        const checkoutTaxLabel = document.getElementById('checkoutTaxLabel');
        const labelText = (window.ShopSettings && ShopSettings.enableTaxShop)
            ? `Tax (${parseFloat(ShopSettings.taxRateShop) || 0}%)`
            : 'Tax (Inactive)';
        if (summaryTaxLabel) summaryTaxLabel.textContent = labelText;
        if (checkoutTaxLabel) checkoutTaxLabel.textContent = labelText;
        const summaryTaxRow = summaryTaxLabel ? summaryTaxLabel.closest('.summary-row') : null;
        if (summaryTaxRow) summaryTaxRow.style.display = (window.ShopSettings && ShopSettings.enableTaxShop) || tax > 0 ? 'flex' : 'none';
        const summaryShippingEl = document.getElementById('summaryShipping');
        const summaryShippingRow = summaryShippingEl ? summaryShippingEl.closest('.summary-row') : null;
        if (summaryShippingRow) summaryShippingRow.style.display = 'none';
    },

    updateCartDisplay: () => {
        ShopCart.updateCartCount();
        if (window.location.pathname.includes('cart.php')) {
            ShopCart.loadCart();
        }
    }
};

// Product Catalog
const ShopCatalog = {
    currentCategory: 'all',
    products: [],
    categories: [],

    initialize: async () => {
        await ShopCatalog.loadCategories();
        await ShopCatalog.loadProducts();
        ShopCatalog.setupEventListeners();
    },

    loadCategories: async () => {
        try {
            const response = await Utils.apiCall('?controller=category&action=list');
            ShopCatalog.categories = response.data || [];
            ShopCatalog.renderCategories();
        } catch (error) {
            console.error('Failed to load categories:', error);
        }
    },

    loadProducts: async (categoryId = null) => {
        try {
            // Tampilkan skeleton saat loading
            if (window.SkeletonLoader && typeof SkeletonLoader.show === 'function') {
                SkeletonLoader.show('skeletonGrid');
            }

            // Hanya tampilkan produk aktif untuk pelanggan (fallback jika kosong)
            let endpoint = '?controller=product&action=list&limit=50&is_active=1';
            if (categoryId) {
                endpoint += `&category_id=${categoryId}`;
            }

            const response = await Utils.apiCall(endpoint);
            let products = response.data?.products || response.data || [];

            // Fallback: jika tidak ada produk aktif, tampilkan semua produk
            if (!products || products.length === 0) {
                let fallbackEndpoint = '?controller=product&action=list&limit=50';
                if (categoryId) {
                    fallbackEndpoint += `&category_id=${categoryId}`;
                }
                try {
                    const fallbackRes = await Utils.apiCall(fallbackEndpoint);
                    products = fallbackRes.data?.products || fallbackRes.data || [];
                } catch (fallbackError) {
                    // Abaikan, akan ditangani pada blok catch utama
                    console.warn('Fallback loadProducts error:', fallbackError);
                }
            }

            ShopCatalog.products = products;
            ShopCatalog.renderProducts();
        } catch (error) {
            console.error('Failed to load products:', error);
            const grid = document.getElementById('productsGrid');
            if (grid) {
                grid.innerHTML = '<div class="loading"><p>Gagal memuat produk</p></div>';
            }
        } finally {
            // Sembunyikan skeleton setelah loading
            if (window.SkeletonLoader && typeof SkeletonLoader.hide === 'function') {
                SkeletonLoader.hide('skeletonGrid');
            }
        }
    },

    renderCategories: () => {
        const container = document.getElementById('categoryShowcase');
        if (!container) return;

        if (ShopCatalog.categories.length === 0) {
            container.innerHTML = '';
            return;
        }

        container.innerHTML = ShopCatalog.categories.map(category => `
            <div class="category-card" data-category="${category.id}">
                <div class="category-icon" style="background: ${category.color || 'var(--primary-color)'}20; color: ${category.color || 'var(--primary-color)'};">
                    <i class="${category.icon || 'fas fa-tag'}"></i>
                </div>
                <div class="category-info">
                    <h4 class="category-name">${category.name}</h4>
                    ${category.product_count ? `<span class="category-count">${category.product_count} produk</span>` : ''}
                </div>
            </div>
        `).join('');
    },

    renderProducts: () => {
        const grid = document.getElementById('productsGrid');
        if (!grid) return;

        if (ShopCatalog.products.length === 0) {
            grid.innerHTML = '<div class="empty-state"><i class="fas fa-box-open"></i><p>Produk tidak ditemukan</p></div>';
            return;
        }

        // Update product count
        const productCount = document.getElementById('productCount');
        if (productCount) {
            productCount.textContent = `${ShopCatalog.products.length} produk`;
        }

        grid.innerHTML = ShopCatalog.products.map(product => {
            const isOutOfStock = product.stock_quantity === 0;
            const isLowStock = product.stock_quantity > 0 && product.stock_quantity < 10;
            const stockBadge = isOutOfStock 
                ? '<span class="stock-badge out-of-stock"><i class="fas fa-times-circle"></i> Habis</span>'
                : isLowStock 
                ? `<span class="stock-badge low-stock"><i class="fas fa-exclamation-triangle"></i> Stok Terbatas (${product.stock_quantity})</span>`
                : `<span class="stock-badge in-stock"><i class="fas fa-check-circle"></i> Tersedia</span>`;

            return `
            <div class="product-card ${isOutOfStock ? 'out-of-stock-card' : ''}" data-product-id="${product.id}">
                <div class="product-image-wrapper">
                    <img src="${Utils.resolveImageUrl(product.image, '../assets/img/no-image.svg')}" 
                         alt="${product.name}" 
                         class="product-image"
                         loading="lazy" decoding="async"
                         onerror="this.src='../assets/img/no-image.svg'">
                    ${stockBadge}
                    <div class="product-overlay">
                        <button class="btn-quick-view" onclick="event.stopPropagation(); ShopProduct.showProductModal(${product.id})" title="Quick View">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="product-info">
                    <div class="product-category">${product.category_name || 'Uncategorized'}</div>
                    <h3 class="product-name" onclick="ShopProduct.showProductModal(${product.id})">${product.name}</h3>
                    <div class="product-price">${Utils.formatCurrency(product.price)}</div>
                    ${product.description ? `<p class="product-description-short">${product.description.substring(0, 60)}${product.description.length > 60 ? '...' : ''}</p>` : ''}
                    
                    <div class="product-actions">
                        <div class="quantity-selector-mini" style="display: ${isOutOfStock ? 'none' : 'flex'};">
                            <button class="qty-btn qty-minus" onclick="event.stopPropagation(); ShopCatalog.updateQuantity(${product.id}, -1)">
                                <i class="fas fa-minus"></i>
                            </button>
                            <span class="qty-display" data-qty-id="${product.id}">1</span>
                            <button class="qty-btn qty-plus" onclick="event.stopPropagation(); ShopCatalog.updateQuantity(${product.id}, 1)">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <button type="button" class="btn-add-to-cart ${isOutOfStock ? 'disabled' : ''}" 
                                data-product-id="${product.id}"
                                ${isOutOfStock ? 'disabled title="Produk habis"' : 'title="Tambah ke keranjang"'}
                                onclick="event.preventDefault(); event.stopPropagation(); ShopCatalog.addToCartDirect(${product.id}, (function(){var el=document.querySelector('[data-qty-id=${product.id}]'); return el? (parseInt(el.textContent)||1) : 1;})());">
                            <i class="fas fa-shopping-cart"></i>
                            <span>${isOutOfStock ? 'Habis' : 'Tambah ke Keranjang'}</span>
                        </button>
                    </div>
                </div>
            </div>
        `;
        }).join('');
    },

    addToCartDirect: (productId, quantity = 1) => {
        const product = ShopCatalog.products.find(p => p.id === productId);
        if (!product) return;
        
        if (product.stock_quantity === 0) {
            Utils.showAdvancedToast('Produk sedang habis', 'warning');
            return;
        }
        
        // Get button element for animation (target the actual button)
        const button = document.querySelector(`button.btn-add-to-cart[data-product-id="${productId}"]`);
        if (button) {
            const originalContent = button.innerHTML;
            button.disabled = true;
            ShopCart.addToCart(product, quantity);
            Utils.animateCartIcon(button);
            if (typeof window.showAddBadge === 'function') { window.showAddBadge(button, 'Ditambahkan'); }
            setTimeout(() => { button.disabled = false; button.innerHTML = originalContent; }, 900);
        } else {
            ShopCart.addToCart(product, quantity);
        }
    },

    sortProducts: (sortBy) => {
        const [field, order] = sortBy.split('_');
        
        ShopCatalog.products.sort((a, b) => {
            let aVal, bVal;
            
            switch(field) {
                case 'name':
                    aVal = a.name.toLowerCase();
                    bVal = b.name.toLowerCase();
                    break;
                case 'price':
                    aVal = parseFloat(a.price);
                    bVal = parseFloat(b.price);
                    break;
                case 'stock':
                    aVal = parseInt(a.stock_quantity);
                    bVal = parseInt(b.stock_quantity);
                    break;
                default:
                    return 0;
            }
            
            if (aVal < bVal) return order === 'asc' ? -1 : 1;
            if (aVal > bVal) return order === 'asc' ? 1 : -1;
            return 0;
        });
        
        ShopCatalog.renderProducts();
    },

    searchProducts: (query) => {
        if (!query) {
            ShopCatalog.renderProducts();
            return;
        }

        const filtered = ShopCatalog.products.filter(product =>
            product.name.toLowerCase().includes(query.toLowerCase()) ||
            (product.sku && product.sku.toLowerCase().includes(query.toLowerCase())) ||
            (product.category_name && product.category_name.toLowerCase().includes(query.toLowerCase()))
        );

        // Temporarily store filtered results
        const originalProducts = [...ShopCatalog.products];
        ShopCatalog.products = filtered;
        ShopCatalog.renderProducts();
        ShopCatalog.products = originalProducts; // Restore original
    },

    updateQuantity: (productId, change) => {
        const qtyDisplay = document.querySelector(`[data-qty-id="${productId}"]`);
        
        if (qtyDisplay) {
            let currentQty = parseInt(qtyDisplay.textContent) || 1;
            const product = ShopCatalog.products.find(p => p.id === productId);
            
            if (product) {
                currentQty = Math.max(1, Math.min(product.stock_quantity, currentQty + change));
                qtyDisplay.textContent = currentQty;
                
                // Add pulse animation
                qtyDisplay.classList.add('pulse');
                setTimeout(() => qtyDisplay.classList.remove('pulse'), 300);
                
                // Visual feedback
                const qtyBtns = document.querySelectorAll(`[data-product-id="${productId}"] .qty-btn`);
                qtyBtns.forEach(btn => {
                    btn.style.transform = 'scale(0.9)';
                    setTimeout(() => btn.style.transform = '', 150);
                });
            }
        }
    },

    filterByCategory: (categoryId) => {
        ShopCatalog.currentCategory = categoryId;
        
        // Update active button
        document.querySelectorAll('.category-btn').forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.category === categoryId.toString()) {
                btn.classList.add('active');
            }
        });

        if (categoryId === 'all') {
            ShopCatalog.loadProducts();
        } else {
            ShopCatalog.loadProducts(categoryId);
        }
    },

    quickFilter: (filterType) => {
        // Update active button
        document.querySelectorAll('.quick-filter-btn').forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.filter === filterType) {
                btn.classList.add('active');
            }
        });

        if (filterType === 'all') {
            ShopCatalog.loadProducts();
            return;
        }

        // Quick filters: bestseller, new, promo
        let filtered = [...ShopCatalog.products];
        
        switch(filterType) {
            case 'bestseller':
                // Sort by stock sold or stock quantity (low stock = popular)
                filtered.sort((a, b) => {
                    const aSold = (b.stock_quantity || 0) - (a.original_stock || 100);
                    const bSold = (a.stock_quantity || 0) - (b.original_stock || 100);
                    return bSold - aSold;
                });
                break;
            case 'new':
                // Sort by created date (newest first) or ID
                filtered.sort((a, b) => (b.id || 0) - (a.id || 0));
                break;
            case 'promo':
                // Show products with low stock as "promo" or special
                filtered = filtered.filter(p => p.stock_quantity < 15 || p.price < 30000);
                break;
        }
        
        // Temporarily replace products for display
        const originalProducts = [...ShopCatalog.products];
        ShopCatalog.products = filtered.slice(0, 50);
        ShopCatalog.renderProducts();
        ShopCatalog.products = originalProducts;
    },

    setupEventListeners: () => {
        // Search
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            let debounceTimer;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    ShopCatalog.searchProducts(e.target.value);
                }, 300);
            });
        }

        // Sort
        const sortSelect = document.getElementById('sortSelect');
        if (sortSelect) {
            sortSelect.addEventListener('change', (e) => {
                ShopCatalog.sortProducts(e.target.value);
            });
        }

        // Category toggle
        const categoryToggle = document.getElementById('categoryToggle');
        const categorySection = document.getElementById('categorySection');
        if (categoryToggle && categorySection) {
            categoryToggle.addEventListener('click', () => {
                const isVisible = categorySection.style.display !== 'none';
                categorySection.style.display = isVisible ? 'none' : 'block';
                categoryToggle.classList.toggle('active', !isVisible);
            });
        }

        // Category filter
        const categoryContainer = document.getElementById('categoryShowcase');
        if (categoryContainer) {
            categoryContainer.addEventListener('click', (e) => {
                const btn = e.target.closest('.category-card');
                if (btn) {
                    ShopCatalog.filterByCategory(btn.dataset.category || 'all');
                    categorySection.style.display = 'none';
                    categoryToggle.classList.remove('active');
                }
            });
        }

        // Delegated add-to-cart click from products grid
        const productsGrid = document.getElementById('productsGrid');
        if (productsGrid) {
            productsGrid.addEventListener('click', (e) => {
                const addBtn = e.target.closest('button.btn-add-to-cart');
                if (addBtn && !addBtn.classList.contains('disabled')) {
                    e.preventDefault();
                    e.stopPropagation();
                    const productId = parseInt(addBtn.getAttribute('data-product-id'), 10);
                    const parentCard = addBtn.closest('.product-card');
                    // Prefer numeric input in classic layout
                    let qtyInput = parentCard ? parentCard.querySelector('input[name="qty"]') : null;
                    let qty = qtyInput ? parseInt(qtyInput.value, 10) : NaN;
                    if (!qty || isNaN(qty)) {
                        // Fallback to qty display in modern layout
                        const qtyEl = parentCard ? parentCard.querySelector(`[data-qty-id="${productId}"]`) : document.querySelector(`[data-qty-id="${productId}"]`);
                        const qtyText = (qtyEl && qtyEl.textContent) ? qtyEl.textContent : '1';
                        qty = parseInt(qtyText, 10) || 1;
                    }
                    // Clamp quantity to valid range
                    qty = Math.max(1, qty);
                    const product = ShopCatalog.products.find(p => p.id === productId);
                    if (product && typeof product.stock_quantity === 'number') {
                        qty = Math.min(qty, product.stock_quantity);
                    }
                    ShopCatalog.addToCartDirect(productId, qty);
                }
            });
        }

        // Global fallback: ensure clicks work even if grid listener fails
        if (!window.__BB_ADD_TO_CART_LISTENER_ADDED__) {
            window.__BB_ADD_TO_CART_LISTENER_ADDED__ = true;
            document.addEventListener('click', (e) => {
                const addBtn = e.target.closest('button.btn-add-to-cart');
                if (!addBtn || addBtn.classList.contains('disabled')) return;
                e.preventDefault();
                e.stopPropagation();
                const productId = parseInt(addBtn.getAttribute('data-product-id'), 10);
                const parentCard = addBtn.closest('.product-card');
                let qtyInput = parentCard ? parentCard.querySelector('input[name="qty"]') : null;
                let qty = qtyInput ? parseInt(qtyInput.value, 10) : NaN;
                if (!qty || isNaN(qty)) {
                    const qtyEl = parentCard ? parentCard.querySelector(`[data-qty-id="${productId}"]`) : document.querySelector(`[data-qty-id="${productId}"]`);
                    const qtyText = (qtyEl && qtyEl.textContent) ? qtyEl.textContent : '1';
                    qty = parseInt(qtyText, 10) || 1;
                }
                qty = Math.max(1, qty);
                const product = ShopCatalog.products.find(p => p.id === productId);
                if (product && typeof product.stock_quantity === 'number') {
                    qty = Math.min(qty, product.stock_quantity);
                }
                ShopCatalog.addToCartDirect(productId, qty);
            }, true);
        }
    }
};

// Product Detail Modal
const ShopProduct = {
    currentProduct: null,

    showProductModal: async (productId) => {
        try {
            const response = await Utils.apiCall(`?controller=product&action=get&id=${productId}`);
            ShopProduct.currentProduct = response.data;
            ShopProduct.renderProductModal();
        } catch (error) {
            Utils.showToast('Failed to load product details', 'error');
        }
    },

    renderProductModal: () => {
        const product = ShopProduct.currentProduct;
        if (!product) return;

        // Update modal content
        document.getElementById('modalProductName').textContent = product.name;
        document.getElementById('modalProductNameLarge').textContent = product.name;
        document.getElementById('modalProductCategory').textContent = product.category_name || 'Uncategorized';
        document.getElementById('modalProductPrice').textContent = Utils.formatCurrency(product.price);
        document.getElementById('modalProductStock').textContent = `Stock: ${product.stock_quantity}`;
        document.getElementById('modalProductDescription').textContent = product.description || 'No description available';
        
        const productImage = Utils.resolveImageUrl(product.image, '../assets/img/product-placeholder.jpg');
        document.getElementById('modalProductImage').src = productImage;
        
        // Reset quantity
        document.getElementById('modalQuantity').value = 1;

        // Show modal
        document.getElementById('productModal').classList.add('show');
    },

    closeProductModal: () => {
        document.getElementById('productModal').classList.remove('show');
    },

    setupModalListeners: () => {
        // Close modal buttons
        document.getElementById('closeProductModal')?.addEventListener('click', ShopProduct.closeProductModal);
        
        // Quantity controls
        document.getElementById('decreaseQuantity')?.addEventListener('click', () => {
            const input = document.getElementById('modalQuantity');
            if (input.value > 1) {
                input.value = parseInt(input.value) - 1;
            }
        });

        document.getElementById('increaseQuantity')?.addEventListener('click', () => {
            const input = document.getElementById('modalQuantity');
            const maxStock = ShopProduct.currentProduct?.stock_quantity || 999;
            if (parseInt(input.value) < maxStock) {
                input.value = parseInt(input.value) + 1;
            }
        });

        // Add to cart
        document.getElementById('addToCartBtn')?.addEventListener('click', () => {
            if (!ShopProduct.currentProduct) return;

            const quantity = parseInt(document.getElementById('modalQuantity').value);
            ShopCart.addToCart(ShopProduct.currentProduct, quantity);
            ShopProduct.closeProductModal();
        });

        // Close modal when clicking outside
        document.getElementById('productModal')?.addEventListener('click', (e) => {
            if (e.target.id === 'productModal') {
                ShopProduct.closeProductModal();
            }
        });
    }
};

// Checkout Handler
const ShopCheckout = {
    // Menyimpan data order terakhir untuk keperluan cetak invoice / share
    currentOrderData: null,
    expiryTimer: null,
    statusPollTimer: null,
    initialize: () => {
        // Prefill HANYA jika pengguna mengaktifkan autofill sebelumnya
        try {
            const auto = localStorage.getItem('checkout_autofill');
            if (auto === '1' || auto === 'true') {
                ShopCheckout.prefillSavedCustomer();
            } else {
                ['customer_name','customer_email','customer_phone','customer_address']
                    .forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
            }
        } catch (e) { /* ignore */ }
        ShopCheckout.loadCheckoutItems();
        ShopCheckout.setupCheckoutListeners();
        ShopCart.updateCartCount();
    },

    prefillSavedCustomer: () => {
        const setVal = (id, value) => {
            const el = document.getElementById(id);
            if (el && value) el.value = value;
        };
        try {
            const name = localStorage.getItem('checkout_name');
            const email = localStorage.getItem('checkout_email');
            const phone = localStorage.getItem('checkout_phone');
            const address = localStorage.getItem('checkout_address');
            setVal('customer_name', name);
            setVal('customer_email', email);
            setVal('customer_phone', phone);
            setVal('customer_address', address);
        } catch (e) {
            console.warn('Prefill error', e);
        }
    },

    loadCheckoutItems: () => {
        const cart = ShopCart.getCart();
        const container = document.getElementById('checkoutItems');
        
        if (!container) return;

        if (cart.length === 0) {
            window.location.href = 'cart.php';
            return;
        }

        container.innerHTML = cart.map(item => `
            <div class="order-item" style="display: flex; gap: .75rem; padding: .5rem 0; border: none; border-bottom: 1px solid var(--border-color); border-radius: 0;">
                <div style="flex: 1;">
                    <div style="font-weight: 600;">${item.name}</div>
                    <div style="color: var(--text-light); font-size: 0.875rem;">
                        ${Utils.formatCurrency(item.price)} x ${item.quantity}
                    </div>
                </div>
                <div style="font-weight: 600; color: var(--text-dark);">
                    ${Utils.formatCurrency(item.price * item.quantity)}
                </div>
            </div>
        `).join('');

        ShopCart.updateSummary();
    },

    setupCheckoutListeners: () => {
        const checkoutForm = document.getElementById('checkoutForm');
        if (checkoutForm) {
            checkoutForm.addEventListener('submit', ShopCheckout.processCheckout);
        }

        // Fallback: jika event submit tidak terpicu karena validasi HTML, tampilkan feedback
        const submitBtn = document.getElementById('placeOrderBtn');
        if (submitBtn) {
            submitBtn.addEventListener('click', (ev) => {
                ev.preventDefault();
                const form = document.getElementById('checkoutForm');
                if (!form) return;
                if (!form.checkValidity()) { form.reportValidity(); return; }
                ShopCheckout.processCheckout({ preventDefault: () => {}, target: form });
            });
        }
    },

    // Control visibility of CTAs based on payment status
    setCTAs: (isPaid) => {
        // QRIS CTAs
        const shareQr = document.getElementById('shareWhatsAppBtnQr');
        const trackQr = document.getElementById('trackOrderLinkQr');
        if (trackQr) trackQr.style.display = 'inline-block'; // always visible
        if (shareQr) {
            shareQr.style.display = isPaid ? 'inline-block' : 'none';
            if (isPaid) {
                shareQr.classList.add('cta-bounce');
                setTimeout(() => shareQr.classList.remove('cta-bounce'), 600);
            }
        }

        // Transfer CTAs
        const shareTf = document.getElementById('shareWhatsAppBtnTransfer');
        const trackTf = document.getElementById('trackOrderLinkTf');
        if (trackTf) trackTf.style.display = 'inline-block'; // always visible
        if (shareTf) {
            shareTf.style.display = isPaid ? 'inline-block' : 'none';
            if (isPaid) {
                shareTf.classList.add('cta-bounce');
                setTimeout(() => shareTf.classList.remove('cta-bounce'), 600);
            }
        }
    },

    processCheckout: async (e) => {
        e.preventDefault();

        // Pastikan field wajib terisi dan tampilkan feedback native
        const form = e.target;
        if (form && !form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const cart = ShopCart.getCart();
        if (cart.length === 0) {
            Utils.showToast('Cart is empty', 'error');
            return;
        }

        // Get form data
        const formData = new FormData(e.target);
        const orderData = {
            customer_name: formData.get('customer_name'),
            customer_email: formData.get('customer_email'),
            customer_phone: formData.get('customer_phone'),
            customer_address: formData.get('customer_address'),
            payment_method: formData.get('payment_method'),
            notes: formData.get('notes'),
            items: cart.map(item => ({
                product_id: item.id,
                quantity: item.quantity
            }))
        };

        // Disable button
        const submitBtn = document.getElementById('placeOrderBtn');
        const originalText = submitBtn ? submitBtn.innerHTML : '';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        }

        try {
            const response = await Utils.apiCall('?controller=order&action=create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(orderData)
            });

            if (response.success) {
                ShopCheckout.showPaymentSection(response.data);
                ShopCart.clearCart();

                // Simpan info pelanggan hanya jika autofill diaktifkan
                try {
                    const auto = localStorage.getItem('checkout_autofill');
                    if (auto === '1' || auto === 'true') {
                        localStorage.setItem('checkout_name', orderData.customer_name || '');
                        localStorage.setItem('checkout_email', orderData.customer_email || '');
                        localStorage.setItem('checkout_phone', orderData.customer_phone || '');
                        localStorage.setItem('checkout_address', orderData.customer_address || '');
                    } else {
                        localStorage.removeItem('checkout_name');
                        localStorage.removeItem('checkout_email');
                        localStorage.removeItem('checkout_phone');
                        localStorage.removeItem('checkout_address');
                    }
                } catch (e) {}
            } else {
                Utils.showToast(response.error || 'Order failed', 'error');
            }
        } catch (error) {
            Utils.showToast('Failed to place order: ' + error.message, 'error');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        }
    },

    showPaymentSection: (orderData) => {
        // simpan orderData agar bisa dipakai kembali (print invoice, share WhatsApp)
        ShopCheckout.currentOrderData = orderData;

        // Hide checkout form
        document.getElementById('checkoutFormSection').style.display = 'none';
        
        // Show payment section
        const paymentSection = document.getElementById('paymentSection');
        paymentSection.style.display = 'block';

        const page = document.querySelector('.checkout-page');
        if (page) {
            page.classList.add('order-created', 'order-created-horizontal');
            try { page.appendChild(paymentSection); } catch (e) {}
        }

        // Set order number
        document.getElementById('orderNumber').textContent = orderData.order_number;

        // Show appropriate payment method
        if (orderData.payment_method === 'qris') {
            ShopCheckout.showQRISPayment(orderData);
        } else if (orderData.payment_method === 'transfer') {
            ShopCheckout.showTransferPayment(orderData);
        }

        // Update track order link untuk masing-masing metode
        const trackQr = document.getElementById('trackOrderLinkQr');
        if (trackQr) trackQr.href = `order-status.php?code=${orderData.order_number}`;
        const trackTf = document.getElementById('trackOrderLinkTf');
        if (trackTf) trackTf.href = `order-status.php?code=${orderData.order_number}`;

        // Bind cancel buttons
        const cancelQr = document.getElementById('cancelOrderBtnQr');
        if (cancelQr) cancelQr.onclick = async () => {
            const confirmCancel = window.confirm('Batalkan pesanan ini?');
            if (!confirmCancel) return;
            cancelQr.disabled = true;
            const original = cancelQr.innerHTML;
            cancelQr.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
            try {
                await ShopCheckout.cancelOrder(orderData.order_number, 'buyer_cancel');
            } finally {
                cancelQr.innerHTML = original;
                cancelQr.disabled = false;
            }
        };
        const cancelTf = document.getElementById('cancelOrderBtnTransfer');
        if (cancelTf) cancelTf.onclick = async () => {
            const confirmCancel = window.confirm('Batalkan pesanan ini?');
            if (!confirmCancel) return;
            cancelTf.disabled = true;
            const original = cancelTf.innerHTML;
            cancelTf.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
            try {
                await ShopCheckout.cancelOrder(orderData.order_number, 'buyer_cancel');
            } finally {
                cancelTf.innerHTML = original;
                cancelTf.disabled = false;
            }
        };
    },

    showQRISPayment: (orderData) => {
        const qrisSection = document.getElementById('qrisPayment');
        qrisSection.style.display = 'block';

        // Set QR code image statically to QRIS GoPay asset
        // Requirement: QR on checkout must be fixed and not change
        document.getElementById('qrCodeImage').src = '/assets/img/qris-gopay.svg';

        // Set amount
        document.getElementById('paymentAmount').textContent = Utils.formatCurrency(orderData.total_amount);

        // Start expiry countdown if available
        const expiredAt = orderData.payment?.expired_at || orderData.payment_info?.expired_at;
        if (expiredAt) {
            ShopCheckout.startExpiryCountdown(expiredAt, 'qrExpiry');
        } else {
            const expiryEl = document.getElementById('qrExpiry');
            if (expiryEl) expiryEl.textContent = '-';
        }

        // Bind verification request button (buyer indicates they already paid)
        const manualBtnQr = document.getElementById('manualPaidBtnQr');
        if (manualBtnQr) {
            manualBtnQr.onclick = async () => {
                manualBtnQr.disabled = true;
                const original = manualBtnQr.innerHTML;
                manualBtnQr.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengirim...';
                try {
                    await ShopCheckout.requestVerification(orderData.order_number, 'qr_scanned');
                    const statusEl = document.getElementById('paymentStatus');
                    if (statusEl) {
                        statusEl.innerHTML = `
                            <i class="fas fa-shield-alt" style="color: var(--primary-color);"></i>
                            <p style="color: var(--primary-color);">Sedang diverifikasi oleh kasir/manager...</p>
                        `;
                    }
                    manualBtnQr.innerHTML = '<i class="fas fa-hourglass-half"></i> Sedang diverifikasi';
                } catch (e) {
                    manualBtnQr.innerHTML = original;
                    manualBtnQr.disabled = false;
                    Utils.showToast('Gagal mengirim verifikasi', 'error');
                    return;
                }
            };
        }

        // Bind tombol copy order number & share WhatsApp (QRIS)
        const copyQr = document.getElementById('copyOrderBtnQr');
        if (copyQr) {
            copyQr.onclick = async () => {
                try {
                    await navigator.clipboard.writeText(orderData.order_number);
                    Utils.showAdvancedToast('Order number disalin', 'success', 'copy');
                } catch (e) {
                    Utils.showToast('Gagal menyalin order number', 'error');
                }
            };
        }
        const shareQr = document.getElementById('shareWhatsAppBtnQr');
        if (shareQr) {
            shareQr.onclick = () => {
                const itemsList = (orderData.items || []).map(it => `${it.product_name || it.name} x ${it.quantity}`).join('\n');
                const msg = `Pesanan Baru ByteBalok\nNo: ${orderData.order_number}\nTotal: ${Utils.formatCurrency(orderData.total_amount)}\n${itemsList ? 'Items:\n' + itemsList + '\n' : ''}Cek status: ${window.location.origin}/shop/order-status.php?code=${orderData.order_number}`;
                const url = `https://wa.me/?text=${encodeURIComponent(msg)}`;
                window.open(url, '_blank');
            };
        }

        // Initial CTA gating for QRIS (share hidden until paid)
        ShopCheckout.setCTAs(false);

        // Start checking payment status
        ShopCheckout.checkPaymentStatus(orderData.order_number);
    },

    showTransferPayment: (orderData) => {
        const transferSection = document.getElementById('transferPayment');
        transferSection.style.display = 'block';
        document.getElementById('transferAmount').textContent = Utils.formatCurrency(orderData.total_amount);

        const paymentInfo = orderData.payment || orderData.payment_info || {};

        const setText = (id, value) => {
            const el = document.getElementById(id);
            if (el && value !== undefined && value !== null) {
                el.textContent = value || '-';
            }
        };

        setText('bankName', paymentInfo.bank_name);
        setText('accountNumber', paymentInfo.account_number);
        setText('accountName', paymentInfo.account_name);
        setText('virtualAccount', paymentInfo.virtual_account);
        setText('referenceNumber', paymentInfo.reference_number);
        setText('transferInstructions', paymentInfo.instructions);

        // Optional: countdown untuk TRANSFER jika gateway menyediakan expired_at
        const transferExpiredAt = paymentInfo.expired_at;
        const transferExpiryEl = document.getElementById('transferExpiry');
        if (transferExpiredAt && transferExpiryEl) {
            ShopCheckout.startExpiryCountdown(transferExpiredAt, 'transferExpiry');
        } else if (transferExpiryEl) {
            transferExpiryEl.textContent = '-';
        }

        // Bind verification request button for transfer
        const manualBtnTf = document.getElementById('manualPaidBtnTransfer');
        if (manualBtnTf) {
            manualBtnTf.onclick = async () => {
                manualBtnTf.disabled = true;
                const original = manualBtnTf.innerHTML;
                manualBtnTf.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengirim...';
                try {
                    await ShopCheckout.requestVerification(orderData.order_number, 'transfer_received');
                    const statusElT = document.getElementById('paymentStatusTransfer');
                    if (statusElT) {
                        statusElT.innerHTML = `
                            <i class="fas fa-shield-alt" style="color: var(--primary-color);"></i>
                            <p style="color: var(--primary-color);">Sedang diverifikasi oleh kasir/manager...</p>
                        `;
                    }
                    manualBtnTf.innerHTML = '<i class="fas fa-hourglass-half"></i> Sedang diverifikasi';
                } catch (e) {
                    manualBtnTf.innerHTML = original;
                    manualBtnTf.disabled = false;
                    Utils.showToast('Gagal mengirim verifikasi', 'error');
                    return;
                }
            };
        }

        // Bind tombol copy order number & share WhatsApp (Transfer)
        const copyTf = document.getElementById('copyOrderBtnTransfer');
        if (copyTf) {
            copyTf.onclick = async () => {
                try {
                    await navigator.clipboard.writeText(orderData.order_number);
                    Utils.showAdvancedToast('Order number disalin', 'success', 'copy');
                } catch (e) {
                    Utils.showToast('Gagal menyalin order number', 'error');
                }
            };
        }
        const shareTf = document.getElementById('shareWhatsAppBtnTransfer');
        if (shareTf) {
            shareTf.onclick = () => {
                const itemsList = (orderData.items || []).map(it => `${it.product_name || it.name} x ${it.quantity}`).join('\n');
                const msg = `Pesanan Baru ByteBalok\nNo: ${orderData.order_number}\nTotal: ${Utils.formatCurrency(orderData.total_amount)}\n${itemsList ? 'Items:\n' + itemsList + '\n' : ''}Cek status: ${window.location.origin}/shop/order-status.php?code=${orderData.order_number}`;
                const url = `https://wa.me/?text=${encodeURIComponent(msg)}`;
                window.open(url, '_blank');
            };
        }

        // Mulai cek status pembayaran
        ShopCheckout.checkPaymentStatus(orderData.order_number);

        // Initial CTA gating for Transfer (share hidden until paid)
        ShopCheckout.setCTAs(false);
    },


    simulatePayment: async (orderNumber) => {
        try {
            const response = await Utils.apiCall('?controller=payment&action=simulate', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_number: orderNumber })
            });

            if (response.success) {
                Utils.showToast('Payment successful!', 'success');
                document.getElementById('paymentStatus').innerHTML = `
                    <i class="fas fa-check-circle" style="color: var(--secondary-color);"></i>
                    <p style="color: var(--secondary-color);">Payment Confirmed!</p>
                `;
            }
        } catch (error) {
            Utils.showToast('Payment simulation failed', 'error');
        }
    },

    checkPaymentStatus: (orderNumber) => {
        const checkStatus = async () => {
            try {
                const response = await Utils.apiCall(`?controller=payment&action=check-status&order_number=${orderNumber}`);
                const data = response.data || {};

                // Optional intermediate signals for cashier awareness
                // Show when QR is scanned (if gateway provides flag)
                if ((ShopCheckout.currentOrderData?.payment_method === 'qris') && (data.qr_scanned === true)) {
                    const statusEl = document.getElementById('paymentStatus');
                    if (statusEl) {
                        statusEl.innerHTML = `
                            <i class="fas fa-mobile-alt" style="color: var(--primary-color);"></i>
                            <p style="color: var(--primary-color);">QR telah di-scan, menunggu konfirmasi pembayaran...</p>
                        `;
                    }
                }

                // Show when transfer has been received by bank but not yet confirmed
                if ((ShopCheckout.currentOrderData?.payment_method === 'transfer') && (data.transfer_received === true || data.transfer_status === 'received')) {
                    const statusElT = document.getElementById('paymentStatusTransfer');
                    if (statusElT) {
                        statusElT.innerHTML = `
                            <i class="fas fa-university" style="color: var(--primary-color);"></i>
                            <p style="color: var(--primary-color);">Transfer terdeteksi, menunggu verifikasi sistem...</p>
                        `;
                    }
                }

                if (data && data.order_status === 'cancelled') {
                    if (ShopCheckout.expiryTimer) clearInterval(ShopCheckout.expiryTimer);
                    if (ShopCheckout.statusPollTimer) clearTimeout(ShopCheckout.statusPollTimer);
                    ShopCheckout.showCancelledSection(orderNumber, 'Pesanan dibatalkan');
                    return;
                }

                if (data && data.payment_status === 'paid') {
                    const statusEl = (ShopCheckout.currentOrderData?.payment_method === 'transfer') 
                        ? document.getElementById('paymentStatusTransfer') 
                        : document.getElementById('paymentStatus');
                    if (statusEl) {
                        statusEl.innerHTML = `
                            <i class="fas fa-check-circle" style="color: var(--secondary-color);"></i>
                            <p style="color: var(--secondary-color);">Pembayaran terkonfirmasi!</p>
                        `;
                    }
                    if (ShopCheckout.expiryTimer) clearInterval(ShopCheckout.expiryTimer);
                    if (ShopCheckout.statusPollTimer) clearTimeout(ShopCheckout.statusPollTimer);

                    // Auto redirect ke halaman pelacakan order
                    const ord = ShopCheckout.currentOrderData?.order_number || orderNumber;
                    const url = `order-status.php?code=${ord}`;
                    setTimeout(() => { window.location.href = url; }, 1500);

                    // Enable CTAs (share becomes visible)
                    ShopCheckout.setCTAs(true);
                    return; // Stop checking
                }
            } catch (error) {
                console.error('Failed to check payment status:', error);
            }

            // Check again after 5 seconds
            ShopCheckout.statusPollTimer = setTimeout(checkStatus, 5000);
        };

        checkStatus();
    },

    cancelOrder: async (orderNumber, reason = 'buyer_cancel') => {
        try {
            const res = await Utils.apiCall('?controller=order&action=cancel', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_number: orderNumber, reason })
            });
            if (res && res.success) {
                if (ShopCheckout.expiryTimer) clearInterval(ShopCheckout.expiryTimer);
                if (ShopCheckout.statusPollTimer) clearTimeout(ShopCheckout.statusPollTimer);
                ShopCheckout.showCancelledSection(orderNumber, 'Pesanan dibatalkan oleh pembeli');
                Utils.showAdvancedToast('Pesanan dibatalkan', 'warning', 'ban');
            } else {
                Utils.showToast(res?.error || 'Gagal membatalkan pesanan', 'error');
            }
        } catch (e) {
            Utils.showToast('Gagal membatalkan pesanan: ' + (e?.message || 'Unknown'), 'error');
        }
    },

    showCancelledSection: (orderNumber, message) => {
        const paymentSection = document.getElementById('paymentSection');
        if (paymentSection) paymentSection.style.display = 'none';
        const cancelled = document.getElementById('orderCancelled');
        if (cancelled) cancelled.style.display = 'block';
        const num = document.getElementById('cancelledOrderNumber');
        if (num) num.textContent = orderNumber;
        const reason = document.getElementById('cancelledReason');
        if (reason) reason.textContent = message || 'Pesanan dibatalkan.';
        const track = document.getElementById('trackOrderLinkCancelled');
        if (track) track.href = `order-status.php?code=${orderNumber}`;
    },

    // Manual confirm paid (cashier/user clicks)
    manualConfirmPaid: async (orderNumber) => {
        try {
            const res = await Utils.apiCall('?controller=payment&action=manualUpdate', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_number: orderNumber, confirm_paid: true })
            });

            if (res && res.success) {
                Utils.showAdvancedToast('Pembayaran dikonfirmasi secara manual', 'success', 'check-circle');
                const statusEl = (ShopCheckout.currentOrderData?.payment_method === 'transfer') 
                    ? document.getElementById('paymentStatusTransfer') 
                    : document.getElementById('paymentStatus');
                if (statusEl) {
                    statusEl.innerHTML = `
                        <i class="fas fa-check-circle" style="color: var(--secondary-color);"></i>
                        <p style="color: var(--secondary-color);">Pembayaran terkonfirmasi!</p>
                    `;
                }

                // Stop timers and redirect to tracking
                if (ShopCheckout.expiryTimer) clearInterval(ShopCheckout.expiryTimer);
                if (ShopCheckout.statusPollTimer) clearTimeout(ShopCheckout.statusPollTimer);
                const ord = orderNumber;
                const url = `order-status.php?code=${ord}`;
                setTimeout(() => { window.location.href = url; }, 1200);

                // Enable CTAs (share becomes visible)
                ShopCheckout.setCTAs(true);
            } else {
                Utils.showToast(res?.error || 'Gagal konfirmasi manual', 'error');
            }
        } catch (e) {
            Utils.showToast('Gagal konfirmasi manual: ' + (e?.message || 'Unknown'), 'error');
        }
    },

    // Buyer-side: request manual verification (sets intermediate flag only)
    requestVerification: async (orderNumber, flag = 'qr_scanned') => {
        try {
            const res = await Utils.apiCall('?controller=payment&action=manualUpdate', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_number: orderNumber, flag })
            });
            if (res && res.success) {
                Utils.showAdvancedToast('Permintaan verifikasi dikirim', 'info', 'shield-alt');
                return true;
            }
            throw new Error(res?.message || 'manualUpdate failed');
        } catch (e) {
            throw e;
        }
    },

    // Countdown kedaluwarsa (QRIS/TRANSFER jika tersedia)
    startExpiryCountdown: (expiredAt, elementId) => {
        try {
            if (ShopCheckout.expiryTimer) clearInterval(ShopCheckout.expiryTimer);
            const el = document.getElementById(elementId);
            if (!el || !expiredAt) return;

            const target = new Date(expiredAt).getTime();

            const format = (ms) => {
                const totalSeconds = Math.floor(ms / 1000);
                const h = Math.floor(totalSeconds / 3600);
                const m = Math.floor((totalSeconds % 3600) / 60);
                const s = totalSeconds % 60;
                const hPrefix = h > 0 ? String(h).padStart(2, '0') + ':' : '';
                return `${hPrefix}${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
            };

            const tick = () => {
                const now = Date.now();
                const diff = Math.max(0, target - now);
                if (diff <= 0) {
                    el.textContent = 'QR telah kedaluwarsa';
                    const statusEl = (ShopCheckout.currentOrderData?.payment_method === 'transfer') 
                        ? document.getElementById('paymentStatusTransfer') 
                        : document.getElementById('paymentStatus');
                    if (statusEl) {
                        statusEl.innerHTML = `
                            <i class="fas fa-exclamation-circle" style="color: var(--accent-color);"></i>
                            <p style="color: var(--accent-color);">Kode pembayaran kedaluwarsa. Silakan buat order baru.</p>`;
                    }
                    if (ShopCheckout.statusPollTimer) clearTimeout(ShopCheckout.statusPollTimer);
                    clearInterval(ShopCheckout.expiryTimer);
                    return;
                }
                el.textContent = `QR Code kedaluwarsa dalam ${format(diff)}`;
            };

            tick();
            ShopCheckout.expiryTimer = setInterval(tick, 1000);
        } catch (err) {
            console.warn('Countdown error:', err);
        }
    }
};

// Order Tracking
const ShopOrderTracking = {
    initialize: () => {
        ShopOrderTracking.setupEventListeners();
        ShopCart.updateCartCount();

        // Check URL parameters (order number only)
        const params = new URLSearchParams(window.location.search);
        const orderNumber = params.get('order_number');

        if (orderNumber) {
            ShopOrderTracking.trackOrder(orderNumber);
        }
    },

    setupEventListeners: () => {
        const trackForm = document.getElementById('trackOrderForm');
        if (trackForm) {
            trackForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const orderNumber = document.getElementById('orderNumber').value;
                ShopOrderTracking.trackOrder(orderNumber);
            });
        }

        const trackAnotherBtn = document.getElementById('trackAnotherBtn');
        if (trackAnotherBtn) {
            trackAnotherBtn.addEventListener('click', () => {
                document.getElementById('trackFormSection').style.display = 'block';
                document.getElementById('orderDetailsSection').style.display = 'none';
            });
        }
    },

    trackOrder: async (orderNumber) => {
        try {
            const response = await Utils.apiCall(`?controller=order&action=get-by-number&order_number=${orderNumber}`);
            
            if (response.success) {
                ShopOrderTracking.displayOrderDetails(response.data);
            }
        } catch (error) {
            Utils.showToast('Order tidak ditemukan', 'error');
        }
    },

    displayOrderDetails: (order) => {
        // Hide form, show details
        document.getElementById('trackFormSection').style.display = 'none';
        document.getElementById('orderDetailsSection').style.display = 'block';

        // Set order info
        document.getElementById('detailOrderNumber').textContent = order.order_number;
        document.getElementById('detailOrderDate').textContent = Utils.formatDate(order.created_at);

        // Update timeline
        ShopOrderTracking.updateTimeline(order.order_status);

        // Set payment status
        ShopOrderTracking.setPaymentStatus(order.payment_status);
        document.getElementById('paymentMethod').textContent = order.payment_method.toUpperCase();
        document.getElementById('totalAmount').textContent = Utils.formatCurrency(order.total_amount);
        
        if (order.paid_at) {
            document.getElementById('paidAtInfo').style.display = 'block';
            document.getElementById('paidAt').textContent = Utils.formatDate(order.paid_at);
        }

        // Set customer info
        document.getElementById('customerName').textContent = order.customer_name;
        document.getElementById('customerEmail').textContent = order.customer_email;
        document.getElementById('customerPhone').textContent = order.customer_phone;
        document.getElementById('customerAddress').textContent = order.customer_address;

        // Render order items
        ShopOrderTracking.renderOrderItems(order.items);

        // Set order summary
        document.getElementById('orderSubtotal').textContent = Utils.formatCurrency(order.subtotal);
        document.getElementById('orderTax').textContent = Utils.formatCurrency(order.tax_amount);
        document.getElementById('orderShipping').textContent = Utils.formatCurrency(order.shipping_amount);
        document.getElementById('orderTotal').textContent = Utils.formatCurrency(order.total_amount);
    },

    updateTimeline: (status) => {
        const statuses = ['pending', 'processing', 'ready', 'completed'];
        const currentIndex = statuses.indexOf(status);

        document.querySelectorAll('.timeline-item').forEach((item, index) => {
            if (index <= currentIndex) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });
    },

    setPaymentStatus: (status) => {
        const badge = document.getElementById('paymentStatusBadge');
        badge.className = `status-badge ${status}`;
        badge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
    },

    renderOrderItems: (items) => {
        const container = document.getElementById('orderItemsList');
        if (!container) return;

        container.innerHTML = items.map(item => `
            <div class="order-item">
                <img src="${Utils.resolveImageUrl(item.image, '../assets/img/product-placeholder.jpg')}" 
                     alt="${item.product_name}" 
                     class="order-item-image">
                
                <div class="order-item-info">
                    <h4>${item.product_name}</h4>
                    <p>${Utils.formatCurrency(item.unit_price)} x ${item.quantity}</p>
                </div>
                
                <div style="font-weight: 700; color: var(--primary-color);">
                    ${Utils.formatCurrency(item.total_price)}
                </div>
            </div>
        `).join('');
    }
};

// Track Order Modal (for index page)
const setupTrackOrderModal = () => {
    const modal = document.getElementById('trackOrderModal');
    const openBtns = document.querySelectorAll('#trackOrderBtn, #trackOrderLink');
    const closeBtn = document.getElementById('closeTrackModal');

    openBtns.forEach(btn => {
        btn?.addEventListener('click', (e) => {
            e.preventDefault();
            modal.classList.add('show');
        });
    });

    closeBtn?.addEventListener('click', () => {
        modal.classList.remove('show');
    });

    modal?.addEventListener('click', (e) => {
        if (e.target.id === 'trackOrderModal') {
            modal.classList.remove('show');
        }
    });

    const trackForm = document.getElementById('trackOrderForm');
    trackForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        const orderNumber = document.getElementById('trackOrderNumber').value;
        window.location.href = `order-status.php?code=${orderNumber}`;
    });
};

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    // Reset local cart if PHP session has changed (first visit/new session)
    try {
        const sidMeta = document.querySelector('meta[name="php-session-id"]');
        const currentSid = sidMeta ? sidMeta.content : null;
        const storedSid = localStorage.getItem('bytebalok_cart_session');
        if (currentSid && storedSid !== currentSid) {
            localStorage.removeItem(STORAGE_KEY);
            // Pastikan data pelanggan sebelumnya tidak ikut terisi otomatis
            localStorage.removeItem('checkout_name');
            localStorage.removeItem('checkout_email');
            localStorage.removeItem('checkout_phone');
            localStorage.removeItem('checkout_address');
            localStorage.setItem('bytebalok_cart_session', currentSid);
        }
    } catch (e) { /* ignore */ }

    // Update cart count everywhere
    ShopCart.updateCartCount();

    // Load public shop settings for tax (async)
    if (!window.ShopSettings) {
        window.ShopSettings = {
            enableTaxShop: false,
            taxRateShop: 0,
            async load() {
                try {
                    const res = await Utils.apiCall('?controller=settings&action=get_public_shop');
                    const data = res.data || {};
                    const enabledRaw = data.enable_tax_shop;
                    const enabled = (
                        enabledRaw === true || enabledRaw === 1 ||
                        (typeof enabledRaw === 'string' && enabledRaw.toLowerCase() === '1') ||
                        (typeof enabledRaw === 'string' && enabledRaw.toLowerCase() === 'true')
                    );
                    this.enableTaxShop = enabled;
                    this.taxRateShop = parseFloat(data.tax_rate_shop) || 0;
                } catch (e) {
                    // Defaults already set; no-op
                } finally {
                    // Refresh summaries after settings load
                    ShopCart.updateSummary();
                }
            }
        };
    }
    // Trigger settings load
    if (window.ShopSettings && typeof ShopSettings.load === 'function') {
        ShopSettings.load();
    }

    // Setup track order modal
    setupTrackOrderModal();

    // Setup product modal listeners
    ShopProduct.setupModalListeners();

    // Initialize based on current page
    const path = window.location.pathname || '';
    const onShopPage = (
        path.includes('index.php') ||
        path.endsWith('/shop/') ||
        path.endsWith('/shop')
    );
    if (onShopPage) {
        // Attach listeners early to avoid missing clicks
        try { ShopCatalog.setupEventListeners(); } catch (e) { /* no-op */ }
        ShopCatalog.initialize();
    } else if (window.location.pathname.includes('cart.php')) {
        ShopCart.loadCart();
        
        // Setup checkout button
        const checkoutBtn = document.getElementById('checkoutBtn');
        if (checkoutBtn) {
            checkoutBtn.addEventListener('click', () => {
                if (ShopCart.getCart().length > 0) {
                    window.location.href = 'checkout.php';
                } else {
                    Utils.showToast('Your cart is empty', 'error');
                }
            });
        }
    } else if (window.location.pathname.includes('checkout.php')) {
        // Checkout page initialization is called explicitly from the page
    } else if (window.location.pathname.includes('order-status.php')) {
        // Order tracking initialization is called explicitly from the page
    }
});

// ============================================
// NEW FEATURES - Promo Code Manager
// ============================================
const PromoCodeManager = {
    // Kode promo untuk Kue Balok (in production, this would come from API)
    promoCodes: {
        'KUEBALOK10': { discount: 10, type: 'percentage', description: 'Diskon 10% untuk pelanggan baru' },
        'HEMAT50K': { discount: 50000, type: 'fixed', description: 'Potongan Rp 50.000' },
        'BALOK15': { discount: 15, type: 'percentage', description: 'Diskon 15% promo spesial' },
        'GRATISANTAR': { discount: 25000, type: 'fixed', description: 'Gratis ongkir' }
    },

    appliedPromo: null,

    initialize: () => {
        const discountToggle = document.getElementById('discountToggle');
        const discountForm = document.getElementById('discountForm');
        const applyPromoBtn = document.getElementById('applyPromoBtn');
        const removeDiscountBtn = document.getElementById('removeDiscountBtn');

        if (discountToggle) {
            discountToggle.addEventListener('click', () => {
                const isHidden = discountForm.style.display === 'none';
                discountForm.style.display = isHidden ? 'block' : 'none';
                discountToggle.classList.toggle('active');
            });
        }

        if (applyPromoBtn) {
            applyPromoBtn.addEventListener('click', () => PromoCodeManager.applyPromoCode());
        }

        if (removeDiscountBtn) {
            removeDiscountBtn.addEventListener('click', () => PromoCodeManager.removePromoCode());
        }

        // Allow Enter key to apply promo
        const promoInput = document.getElementById('promoCode');
        if (promoInput) {
            promoInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    PromoCodeManager.applyPromoCode();
                }
            });
        }
    },

    applyPromoCode: () => {
        const promoInput = document.getElementById('promoCode');
        const promoMessage = document.getElementById('promoMessage');
        const code = promoInput.value.trim().toUpperCase();

        if (!code) {
            PromoCodeManager.showPromoMessage('Please enter a promo code', 'error');
            return;
        }

        const promo = PromoCodeManager.promoCodes[code];
        if (!promo) {
            PromoCodeManager.showPromoMessage('Invalid promo code', 'error');
            return;
        }

        PromoCodeManager.appliedPromo = { code, ...promo };
        PromoCodeManager.showPromoMessage(`✓ ${promo.description} applied!`, 'success');
        
        // Update cart display
        ShopCart.updateCartDisplay();
        
        // Show discount row
        const discountRow = document.getElementById('discountRow');
        if (discountRow) {
            discountRow.style.display = 'flex';
        }

        // Hide promo form after successful application
        setTimeout(() => {
            const discountToggle = document.getElementById('discountToggle');
            const discountForm = document.getElementById('discountForm');
            if (discountForm && discountToggle) {
                discountForm.style.display = 'none';
                discountToggle.classList.remove('active');
            }
        }, 1500);
    },

    removePromoCode: () => {
        PromoCodeManager.appliedPromo = null;
        const discountRow = document.getElementById('discountRow');
        const promoInput = document.getElementById('promoCode');
        
        if (discountRow) {
            discountRow.style.display = 'none';
        }
        if (promoInput) {
            promoInput.value = '';
        }

        ShopCart.updateCartDisplay();
        Utils.showToast('Promo code removed', 'info');
    },

    showPromoMessage: (message, type) => {
        const promoMessage = document.getElementById('promoMessage');
        if (promoMessage) {
            promoMessage.textContent = message;
            promoMessage.className = `promo-message ${type}`;
            promoMessage.style.display = 'block';

            if (type === 'success') {
                setTimeout(() => {
                    promoMessage.style.display = 'none';
                }, 3000);
            }
        }
    },

    calculateDiscount: (subtotal) => {
        if (!PromoCodeManager.appliedPromo) return 0;

        const promo = PromoCodeManager.appliedPromo;
        if (promo.type === 'percentage') {
            return subtotal * (promo.discount / 100);
        } else {
            return promo.discount;
        }
    },

    getAppliedPromo: () => PromoCodeManager.appliedPromo
};

// ============================================
// NEW FEATURES - WhatsApp Share Integration
// ============================================
const WhatsAppShare = {
    shareOrder: (orderData) => {
        const message = WhatsAppShare.formatOrderMessage(orderData);
        const encodedMessage = encodeURIComponent(message);
        const whatsappUrl = `https://wa.me/?text=${encodedMessage}`;
        window.open(whatsappUrl, '_blank');
    },

    formatOrderMessage: (orderData) => {
        const orderNumber = orderData.order_number || orderData.orderNumber || '-';
        const total = orderData.total_amount ?? orderData.total ?? 0;
        const paymentMethod = (orderData.payment_method || orderData.paymentMethod || '').toString().toUpperCase();
        const items = Array.isArray(orderData.items) ? orderData.items : [];

        let message = `🛍️ *BYTEBALOK ORDER CONFIRMATION*\n\n`;
        message += `📋 Order Number: *${orderNumber}*\n`;
        message += `📅 Date: ${new Date().toLocaleDateString('id-ID')}\n`;
        message += `💰 Total: *${Utils.formatCurrency(total)}*\n`;
        message += `💳 Payment: ${paymentMethod}\n\n`;

        message += `📦 *Items:*\n`;
        items.forEach((item, index) => {
            const name = item.product_name || item.name || 'Item';
            const qty = item.quantity || 1;
            const unit = item.unit_price ?? item.price ?? 0;
            message += `${index + 1}. ${name} x${qty} - ${Utils.formatCurrency(unit * qty)}\n`;
        });

    const trackUrl = `${window.location.origin}/shop/order-status.php?code=${encodeURIComponent(orderNumber)}`;
        message += `\n✅ Thank you for shopping with Bytebalok!`;
        message += `\n🔗 Track your order: ${trackUrl}`;

        return message;
    },

    shareOrderTracking: (orderNumber, status, total) => {
        let message = `📦 *ORDER STATUS UPDATE*\n\n`;
        message += `Order #${orderNumber}\n`;
        message += `Status: *${status.toUpperCase()}*\n`;
        message += `Total: ${Utils.formatCurrency(total)}\n\n`;
        message += `Track: ${window.location.origin}/shop/order-status.php?code=${orderNumber}`;
        
        const encodedMessage = encodeURIComponent(message);
        const whatsappUrl = `https://wa.me/?text=${encodedMessage}`;
        window.open(whatsappUrl, '_blank');
    }
};

// ============================================
// NEW FEATURES - Print Invoice Functionality
// ============================================
const PrintInvoice = {
    printOrder: (orderData) => {
        const printWindow = window.open('', '_blank');
        const content = PrintInvoice.generateInvoiceHTML(orderData);
        
        printWindow.document.write(content);
        printWindow.document.close();
        
        // Wait for content to load, then print
        setTimeout(() => {
            printWindow.focus();
            printWindow.print();
        }, 500);
    },

    generateInvoiceHTML: (orderData) => {
        return `
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice - ${orderData.orderNumber}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; padding: 40px; color: #000; }
        .invoice-header { display: flex; justify-content: space-between; margin-bottom: 40px; border-bottom: 3px solid #000; padding-bottom: 20px; }
        .logo { font-size: 28px; font-weight: bold; }
        .invoice-details { text-align: right; }
        .invoice-details h2 { font-size: 24px; margin-bottom: 10px; }
        .section { margin-bottom: 30px; }
        .section h3 { font-size: 18px; margin-bottom: 15px; border-bottom: 2px solid #ccc; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f0f0f0; font-weight: bold; }
        .total-row { font-weight: bold; font-size: 16px; background: #f9f9f9; }
        .grand-total { font-size: 18px; background: #e0e0e0; }
        .footer { margin-top: 40px; text-align: center; color: #666; font-size: 12px; border-top: 1px solid #ccc; padding-top: 20px; }
        @media print {
            body { padding: 20px; }
            @page { margin: 20mm; }
        }
    </style>
</head>
<body>
    <div class="invoice-header">
        <div>
            <div class="logo">
                <img src="${window.location.origin}/assets/img/logo.svg" alt="BYTEBALOK" style="height:32px; vertical-align:middle; margin-right:8px;">
                BYTEBALOK
            </div>
            <p>Your Trusted Online Shop</p>
            <p>Email: info@bytebalok.com</p>
            <p>Phone: +62 21 1234 5678</p>
        </div>
        <div class="invoice-details">
            <h2>INVOICE</h2>
            <p><strong>Order #:</strong> ${orderData.orderNumber}</p>
            <p><strong>Date:</strong> ${new Date().toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
            <p><strong>Time:</strong> ${new Date().toLocaleTimeString('id-ID')}</p>
        </div>
    </div>

    <div class="section">
        <h3>👤 Customer Information</h3>
        <p><strong>Name:</strong> ${orderData.customerName || 'N/A'}</p>
        <p><strong>Email:</strong> ${orderData.customerEmail || 'N/A'}</p>
        <p><strong>Phone:</strong> ${orderData.customerPhone || 'N/A'}</p>
        <p><strong>Address:</strong> ${orderData.customerAddress || 'N/A'}</p>
    </div>

    <div class="section">
        <h3>📦 Order Items</h3>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                ${orderData.items.map((item, index) => `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${item.name}</td>
                        <td>${item.quantity}</td>
                        <td>${Utils.formatCurrency(item.price)}</td>
                        <td>${Utils.formatCurrency(item.price * item.quantity)}</td>
                    </tr>
                `).join('')}
                <tr class="total-row">
                    <td colspan="4" style="text-align: right;">Subtotal:</td>
                    <td>${Utils.formatCurrency(orderData.subtotal)}</td>
                </tr>
                <tr>
                    <td colspan="4" style="text-align: right;">${(window.ShopSettings && ShopSettings.enableTaxShop) ? `Tax (${parseFloat(ShopSettings.taxRateShop) || 0}%):` : 'Tax (Inactive):'}</td>
                    <td>${Utils.formatCurrency(orderData.tax)}</td>
                </tr>
                <tr>
                    <td colspan="4" style="text-align: right;">Shipping:</td>
                    <td>${Utils.formatCurrency(orderData.shipping || 0)}</td>
                </tr>
                ${orderData.discount ? `
                    <tr style="color: green;">
                        <td colspan="4" style="text-align: right;">Discount:</td>
                        <td>-${Utils.formatCurrency(orderData.discount)}</td>
                    </tr>
                ` : ''}
                <tr class="grand-total">
                    <td colspan="4" style="text-align: right;">TOTAL:</td>
                    <td>${Utils.formatCurrency(orderData.total)}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <h3>💳 Payment Information</h3>
        <p><strong>Payment Method:</strong> ${orderData.paymentMethod || 'N/A'}</p>
        <p><strong>Payment Status:</strong> ${orderData.paymentStatus || 'Pending'}</p>
    </div>

    <div class="footer">
        <p>Thank you for shopping with Bytebalok!</p>
        <p>For support, please contact us at info@bytebalok.com</p>
        <p>This is a computer-generated invoice and does not require a signature.</p>
    </div>
</body>
</html>
        `;
    }
};

// ============================================
// NEW FEATURES - Skeleton Loading Manager
// ============================================
const SkeletonLoader = {
    show: (containerId = 'skeletonGrid') => {
        const skeleton = document.getElementById(containerId);
        if (skeleton) {
            skeleton.style.display = 'grid';
        }
    },

    hide: (containerId = 'skeletonGrid') => {
        const skeleton = document.getElementById(containerId);
        if (skeleton) {
            skeleton.style.display = 'none';
        }
    }
};

// Update ShopCart to include discount calculation
const originalUpdateCartDisplay = ShopCart.updateCartDisplay;
if (originalUpdateCartDisplay) {
    ShopCart.updateCartDisplay = function() {
        originalUpdateCartDisplay.call(this);
        
        // Add discount calculation
        const discount = PromoCodeManager.calculateDiscount(ShopCart.getTotal());
        if (discount > 0) {
            const discountElement = document.getElementById('summaryDiscount');
            if (discountElement) {
                discountElement.textContent = '-' + Utils.formatCurrency(discount);
            }
            
            // Update total with discount
            const totalElement = document.getElementById('summaryTotal');
            if (totalElement) {
                const newTotal = ShopCart.getTotal() + ShopCart.getTax() - discount;
                totalElement.textContent = Utils.formatCurrency(newTotal);
            }
        }
    };
}

// Update ShopCatalog to use skeleton loading
const originalInitialize = ShopCatalog.initialize;
if (originalInitialize) {
    ShopCatalog.initialize = async function() {
        SkeletonLoader.show('skeletonGrid');
        await originalInitialize.call(this);
        setTimeout(() => SkeletonLoader.hide('skeletonGrid'), 500);
    };
}

// Export for use in other scripts
window.ShopCart = ShopCart;
window.ShopCatalog = ShopCatalog;
window.ShopProduct = ShopProduct;
window.ShopCheckout = ShopCheckout;
window.ShopOrderTracking = ShopOrderTracking;
window.ShopUtils = Utils;
window.PromoCodeManager = PromoCodeManager;
window.WhatsAppShare = WhatsAppShare;
window.PrintInvoice = PrintInvoice;
window.SkeletonLoader = SkeletonLoader;

