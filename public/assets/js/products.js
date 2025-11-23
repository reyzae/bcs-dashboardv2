/**
 * Bytebalok Products Management
 * Complete product CRUD with advanced features
 * Updated to use api_dashboard.php routing
 */

class ProductManager {
    constructor() {
        this.products = [];
        this.categories = [];
        this.currentPage = 1;
        this.itemsPerPage = 10;
        this.searchQuery = '';
        this.categoryFilter = '';
        this.statusFilter = '';
        this.currentProductId = null;
        this.init();
    }

    async init() {
        console.log('🚀 Initializing Product Manager...');
        
        // Load data
        await this.loadCategories();
        await this.loadProducts();
        
        // Setup
        this.setupEventListeners();
        this.setupKeyboardShortcuts();
        
        console.log('✅ Product Manager Ready!');
    }

    async loadCategories() {
        try {
            console.log('📁 Loading categories...');
            const response = await app.apiCall('../api.php?controller=category&action=getActive');
            
            if (response.success) {
                this.categories = response.data || [];
                this.renderCategoryFilters();
                console.log(`✅ Loaded ${this.categories.length} categories`);
            } else {
                throw new Error(response.message || 'Failed to load categories');
            }
        } catch (error) {
            console.error('❌ Failed to load categories:', error);
            app.showToast('Failed to load categories', 'error');
        }
    }

    renderCategoryFilters() {
        const filterSelect = document.getElementById('categoryFilter');
        const modalSelect = document.getElementById('productCategory');
        
        const options = this.categories.map(cat => 
            `<option value="${cat.id}">${cat.name}</option>`
        ).join('');
        
        if (filterSelect) filterSelect.innerHTML = '<option value="">All Categories</option>' + options;
        if (modalSelect) modalSelect.innerHTML = '<option value="">Select category...</option>' + options;
    }

    async loadProducts() {
        const tbody = document.querySelector('#productsTable tbody');
        const loadingRow = document.getElementById('loadingRow');
        
        try {
            console.log('📦 Loading products...');
            
            // Show loading state
            if (loadingRow) {
                loadingRow.style.display = 'table-row';
            }
            
            // Build API URL
            let url = `../api.php?controller=product&action=list&page=${this.currentPage}&limit=${this.itemsPerPage}&t=${Date.now()}`;
            if (this.searchQuery) url += `&search=${encodeURIComponent(this.searchQuery)}`;
            if (this.categoryFilter) url += `&category_id=${this.categoryFilter}`;
            if (this.statusFilter !== '') url += `&is_active=${this.statusFilter}`;

            console.log('📍 API URL:', url);

            const response = await app.apiCall(url);
            
            console.log('📦 Products Response:', response);
            
            // Hide loading row
            if (loadingRow) {
                loadingRow.style.display = 'none';
            }
            
            if (response.success && response.data) {
                this.products = response.data.products || response.data || [];
                
                // Debug: Show first product to check image field
                if (this.products.length > 0) {
                    console.log('🔍 Sample product data:', this.products[0]);
                }
                
                this.renderProducts();
                
                if (response.data.pagination) {
                    this.renderPagination(response.data.pagination);
                }
                
                // Update total count badge
                const totalCount = response.data.pagination?.total || this.products.length;
                const countBadge = document.getElementById('totalProductsCount');
                if (countBadge) {
                    countBadge.textContent = totalCount;
                }
                
                console.log(`✅ Loaded ${this.products.length} products`);
            } else {
                throw new Error(response.message || 'Failed to load products');
            }
        } catch (error) {
            console.error('❌ Failed to load products:', error);
            
            // Hide loading row
            if (loadingRow) {
                loadingRow.style.display = 'none';
            }
            
            // Show error in table
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" style="text-align: center; padding: 3rem;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #ef4444; margin-bottom: 1rem;"></i>
                        <h3 style="color: #374151; margin-bottom: 0.5rem;">Failed to Load Products</h3>
                        <p style="color: #6b7280; margin-bottom: 1rem;">${error.message}</p>
                        <button class="btn btn-primary" onclick="productManager.loadProducts()">
                            <i class="fas fa-redo"></i> Retry
                        </button>
                    </td>
                </tr>
            `;
            
            app.showToast('Failed to load products. Please try again.', 'error');
        }
    }

    renderProducts() {
        const tbody = document.querySelector('#productsTable tbody');
        
        console.log('🎨 RENDER PRODUCTS - Total:', this.products.length);
        if (this.products.length > 0) {
            console.log('🔍 First product sample:', this.products[0]);
            console.log('🖼️ First product image field:', this.products[0].image);
        }
        
        if (this.products.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" style="text-align: center; padding: 4rem 2rem;">
                        <div class="empty-state">
                            <i class="fas fa-box-open" style="font-size: 4rem; color: #d1d5db; margin-bottom: 1.5rem;"></i>
                            <h3 style="color: #6b7280; font-size: 1.25rem; margin-bottom: 0.5rem; font-weight: 600;">No Products Found</h3>
                            <p style="color: #9ca3af; margin-bottom: 1.5rem;">
                                ${this.searchQuery || this.categoryFilter || this.statusFilter !== '' 
                                    ? 'Try adjusting your filters or search query' 
                                    : 'Get started by adding your first product'}
                            </p>
                            ${!this.searchQuery && !this.categoryFilter && this.statusFilter === '' ? `
                                <button class="btn btn-primary" onclick="productManager.showProductModal()">
                                <i class="fas fa-plus"></i> Add Your First Product
                            </button>
                            ` : ''}
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = this.products.map(product => {
            const isLowStock = product.stock_quantity <= product.min_stock_level;
            const stockStatus = isLowStock ? 'low-stock' : 'in-stock';
            const stockColor = isLowStock ? '#ef4444' : '#10b981';
            const isInactive = !product.is_active || product.is_active == 0;
            const rowOpacity = isInactive ? '0.5' : '1';
            const rowBg = isInactive ? '#fef2f2' : 'white';
            
            return `
                <tr class="product-row ${isLowStock ? 'low-stock-row' : ''} ${isInactive ? 'inactive-row' : ''}" 
                    data-id="${product.id}" 
                    style="opacity: ${rowOpacity}; background: ${rowBg}; transition: all 0.3s;">
                    <td>
                        <div class="product-image-cell" style="width: 60px; height: 60px; border-radius: 8px; overflow: hidden; background: #f3f4f6; display: flex; align-items: center; justify-content: center; position: relative;">
                        ${product.image ? 
                                `<img src="../${product.image}" alt="${product.name}" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.style.display='none'; this.parentElement.innerHTML='<i class=\\'fas fa-image\\' style=\\'color: #d1d5db; font-size: 1.5rem;\\'></i>';">` :
                                `<i class="fas fa-image" style="color: #d1d5db; font-size: 1.5rem;"></i>`
                        }
                        ${isInactive ? `<div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(239,68,68,0.2); display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-ban" style="color: #ef4444; font-size: 1.5rem;"></i>
                        </div>` : ''}
                    </div>
                </td>
                <td>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <div>
                                <div style="font-weight: 600; color: ${isInactive ? '#6b7280' : '#1f2937'}; margin-bottom: 0.25rem;">
                                    ${product.name}
                                    ${isInactive ? `<span class="badge" style="background: #fee2e2; color: #991b1b; font-size: 0.625rem; padding: 0.125rem 0.5rem; margin-left: 0.5rem; text-transform: uppercase; font-weight: 700;">
                                        <i class="fas fa-times-circle"></i> INACTIVE
                                    </span>` : ''}
                                </div>
                                ${product.description ? `<div style="font-size: 0.875rem; color: #6b7280;">${product.description.substring(0, 50)}${product.description.length > 50 ? '...' : ''}</div>` : ''}
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge badge-secondary" style="font-family: monospace; font-size: 0.875rem;">
                            ${product.sku}
                        </span>
                </td>
                    <td>
                        <span class="badge badge-info">
                            <i class="fas fa-folder"></i> ${product.category_name || 'Uncategorized'}
                        </span>
                    </td>
                    <td style="text-align: right;">
                        <div style="font-weight: 600; color: ${isInactive ? '#9ca3af' : '#1f2937'};">${app.formatCurrency(product.unit_price)}</div>
                        ${product.cost_price ? `<div style="font-size: 0.75rem; color: #9ca3af;">Cost: ${app.formatCurrency(product.cost_price)}</div>` : ''}
                    </td>
                    <td style="text-align: center;">
                        <div style="display: inline-flex; flex-direction: column; align-items: center; gap: 0.25rem;">
                            <span style="font-weight: 700; font-size: 1.125rem; color: ${isInactive ? '#9ca3af' : stockColor};">
                                ${product.stock_quantity}
                        </span>
                            <span style="font-size: 0.75rem; color: #6b7280;">${product.unit || 'pcs'}</span>
                            ${isLowStock && !isInactive ? `<span class="badge badge-danger" style="font-size: 0.625rem; padding: 0.125rem 0.5rem;">LOW</span>` : ''}
                    </div>
                </td>
                    <td style="text-align: center;">
                        <label class="toggle-switch" title="Toggle Active Status">
                            <input type="checkbox" ${product.is_active ? 'checked' : ''} 
                                   onchange="productManager.toggleStatus(${product.id}, this.checked)">
                            <span class="toggle-slider"></span>
                        </label>
                        ${isInactive ? `<div style="font-size: 0.625rem; color: #ef4444; margin-top: 0.25rem; font-weight: 600;">
                            Hidden from POS
                        </div>` : `<div style="font-size: 0.625rem; color: #10b981; margin-top: 0.25rem; font-weight: 600;">
                            Visible in POS
                        </div>`}
                </td>
                    <td style="text-align: center;">
                        <div class="action-buttons" style="display: flex; gap: 0.5rem; justify-content: center;">
                            <button class="btn btn-sm btn-info" onclick="event.stopPropagation(); productManager.editProduct(${product.id}, this)" title="Edit Product">
                                <i class="fas fa-edit"></i>
                            </button>
                            
                            <button class="btn btn-sm btn-danger" onclick="event.stopPropagation(); productManager.deleteProduct(${product.id}, this)" title="Delete Product">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
            </tr>
            `;
        }).join('');
    }

    renderPagination(pagination) {
        const container = document.getElementById('pagination');
        if (!container || !pagination) return;
        
        const { page, pages, total } = pagination;
        
        if (pages <= 1) {
            container.innerHTML = '';
            return;
        }

        let html = `
            <div class="pagination-info">
                Showing page ${page} of ${pages} (${total} total products)
            </div>
            <div class="pagination-controls">
        `;
        
        // Previous button
        html += `
            <button class="btn btn-sm ${page === 1 ? 'btn-secondary' : 'btn-primary'}" 
                ${page === 1 ? 'disabled' : ''} 
                onclick="productManager.goToPage(${page - 1})">
                <i class="fas fa-chevron-left"></i> Previous
            </button>
        `;
        
        // Page numbers
        for (let i = 1; i <= pages; i++) {
            if (i === 1 || i === pages || (i >= page - 2 && i <= page + 2)) {
            html += `
                    <button class="btn btn-sm ${i === page ? 'btn-primary' : 'btn-secondary'}" 
                        onclick="productManager.goToPage(${i})">
                    ${i}
                </button>
            `;
            } else if (i === page - 3 || i === page + 3) {
                html += `<span style="padding: 0 0.5rem;">...</span>`;
            }
        }
        
        // Next button
        html += `
            <button class="btn btn-sm ${page === pages ? 'btn-secondary' : 'btn-primary'}" 
                ${page === pages ? 'disabled' : ''} 
                onclick="productManager.goToPage(${page + 1})">
                Next <i class="fas fa-chevron-right"></i>
            </button>
        `;
        
        html += `</div>`;
        container.innerHTML = html;
    }

    goToPage(page) {
        this.currentPage = page;
        this.loadProducts();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    setupEventListeners() {
        // Add product button
        document.getElementById('addProductBtn')?.addEventListener('click', () => {
            this.showProductModal();
        });

        // Search input with debounce
        let searchTimeout;
        document.getElementById('searchInput')?.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.searchQuery = e.target.value.trim();
                this.currentPage = 1;
                this.loadProducts();
            }, 500);
        });

        // Category filter
        document.getElementById('categoryFilter')?.addEventListener('change', (e) => {
            this.categoryFilter = e.target.value;
            this.currentPage = 1;
            this.loadProducts();
        });

        // Status filter
        document.getElementById('statusFilter')?.addEventListener('change', (e) => {
            this.statusFilter = e.target.value;
            this.currentPage = 1;
            this.loadProducts();
        });

        // Refresh button
        document.getElementById('refreshBtn')?.addEventListener('click', () => {
            this.loadProducts();
            app.showToast('Products refreshed', 'success');
        });

        // Auto-refresh when tab becomes visible or window gains focus
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                this.loadProducts();
            }
        });
        window.addEventListener('focus', () => {
            this.loadProducts();
        });

        // Form submit
        document.getElementById('productForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveProduct();
        });
        
        // Close inline form button
        document.getElementById('closeInlineFormBtn')?.addEventListener('click', () => {
            const addCard = document.getElementById('addProductCard');
            if (addCard) {
                addCard.style.display = 'none';
                this.resetForm();
            }
        });
        
        // Reset form button
        document.getElementById('resetFormBtn')?.addEventListener('click', () => {
            this.resetForm();
        });
    }

    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl + N: Add new product
            if (e.ctrlKey && e.key === 'n') {
                e.preventDefault();
                this.showProductModal();
            }
            
            // Ctrl + F: Focus search
            if (e.ctrlKey && e.key === 'f') {
                e.preventDefault();
                document.getElementById('searchInput')?.focus();
            }
            
            // F5: Refresh (prevent default browser refresh)
            if (e.key === 'F5') {
                e.preventDefault();
                this.loadProducts();
                app.showToast('Products refreshed', 'success');
            }
            
            // ESC: Close inline form
            if (e.key === 'Escape') {
                const addCard = document.getElementById('addProductCard');
                if (addCard && addCard.style.display !== 'none') {
                this.hideProductModal();
                }
            }
        });
        
        console.log('⌨️ Keyboard shortcuts activated');
    }

    showProductModal(product = null) {
        this.currentProductId = product ? product.id : null;
        
        // Show inline form card
        const addCard = document.getElementById('addProductCard');
        if (addCard) {
            addCard.style.display = 'block';
            // Scroll to form
            setTimeout(() => {
                addCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 100);
        }

        if (product) {
            // Edit mode
            document.getElementById('productName').value = product.name || '';
            document.getElementById('productSku').value = product.sku || '';
            document.getElementById('productCategory').value = product.category_id || '';
            document.getElementById('productBarcode').value = product.barcode || '';
            document.getElementById('productDescription').value = product.description || '';
            document.getElementById('productPrice').value = product.unit_price || 0;
            document.getElementById('productCostPrice').value = product.cost_price || 0;
            document.getElementById('productStock').value = product.stock_quantity || 0;
            document.getElementById('productMinStock').value = product.min_stock_level || 10;
            document.getElementById('productUnit').value = product.unit || 'pcs';
            document.getElementById('productActive').checked = product.is_active == 1;
            
            // Load existing image if available
            if (product.image && window.productImageUpload) {
                window.productImageUpload.loadExistingImage(product.image);
            }
        } else {
            // Add mode
            this.resetForm();
        }
        
        // Focus first input
        setTimeout(() => {
            document.getElementById('productName')?.focus();
        }, 300);
    }
    
    resetForm() {
        document.getElementById('productForm').reset();
        document.getElementById('productActive').checked = true;
        document.getElementById('productMinStock').value = 10;
        document.getElementById('productUnit').value = 'pcs';
        this.currentProductId = null;
        
        // Reset image upload
        if (window.productImageUpload) {
            window.productImageUpload.deleteImage(true, false);
        }
    }

    hideProductModal() {
        const addCard = document.getElementById('addProductCard');
        if (addCard) {
            addCard.style.display = 'none';
        }
        this.resetForm();
    }

    async saveProduct() {
        const data = {
            name: document.getElementById('productName').value.trim(),
            sku: document.getElementById('productSku').value.trim(),
            category_id: document.getElementById('productCategory').value,
            barcode: document.getElementById('productBarcode').value.trim(),
            description: document.getElementById('productDescription').value.trim(),
            unit_price: parseFloat(document.getElementById('productPrice').value),
            cost_price: parseFloat(document.getElementById('productCostPrice').value) || 0,
            stock_quantity: parseInt(document.getElementById('productStock').value),
            min_stock_level: parseInt(document.getElementById('productMinStock').value) || 10,
            unit: document.getElementById('productUnit').value.trim() || 'pcs',
            is_active: document.getElementById('productActive').checked ? 1 : 0
        };

        // Validation
        if (!data.name || !data.sku || !data.category_id) {
            app.showToast('Please fill all required fields', 'error');
            return;
        }

        try {
            console.log('🚀 SAVE PRODUCT - Starting process...');
            console.log('📋 Initial product data:', JSON.stringify(data, null, 2));
            
            // Show loading toast
            app.showToast('Saving product...', 'info');
            
            // Handle image: upload if new file selected, otherwise keep existing path
            if (window.productImageUpload) {
                const existingPath = window.productImageUpload.getImagePath?.() || null;
                const hasSelectedFile = !!window.productImageUpload.selectedFile;
                
                if (hasSelectedFile) {
                    console.log('📤 New image selected - Starting upload...');
                    const imagePath = await window.productImageUpload.uploadImage();
                    console.log('📁 Upload result:', imagePath);
                    if (imagePath) {
                        data.image = imagePath;
                        console.log('✅ Image path set from upload:', data.image);
                    } else {
                        console.warn('⚠️ Upload returned null; fallback to existing path if any');
                        if (existingPath) {
                            data.image = existingPath;
                        }
                    }
                } else if (existingPath) {
                    console.log('ℹ️ No new image; keep existing path');
                    data.image = existingPath;
                } else {
                    console.log('ℹ️ No image to attach');
                }
            }
            
            console.log('📦 Final product data before send:', JSON.stringify(data, null, 2));
            
            let url, method;
            if (this.currentProductId) {
                url = `../api.php?controller=product&action=update&id=${this.currentProductId}`;
                method = 'POST';
            } else {
                url = `../api.php?controller=product&action=create`;
                method = 'POST';
            }

            console.log('📤 Sending product data:', data);
            
            const response = await app.apiCall(url, {
                method: method,
                body: JSON.stringify(data)
            });
            
            console.log('📥 Save product response:', response);

            if (response.success) {
                app.showToast(
                    this.currentProductId ? 'Product updated successfully' : 'Product added successfully', 
                    'success'
                );
                this.hideProductModal();
                await this.loadProducts();
            } else {
                app.showToast(response.message || response.error || 'Failed to save product', 'error');
            }
        } catch (error) {
            console.error('Failed to save product:', error);
            app.showToast('Failed to save product: ' + error.message, 'error');
        }
    }

    async editProduct(id, btn = null) {
        // Preserve original button HTML across try/finally (block scope fix)
        let originalHtml = btn ? btn.innerHTML : '';
        try {
            // Disable button and show spinner to prevent double-click
            if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>'; }

            // Open form immediately with minimal fields; mark currentProductId
            this.currentProductId = id;
            this.showProductModal();

            // Load product details
            const response = await app.apiCall(`../api.php?controller=product&action=get&id=${id}`);
            if (response.success && response.data) {
                this.showProductModal(response.data);
            } else {
                app.showToast('Failed to load product details', 'error');
            }
        } catch (error) {
            console.error('Failed to load product:', error);
            app.showToast('Failed to load product', 'error');
        } finally {
            if (btn) { btn.disabled = false; btn.innerHTML = originalHtml; }
        }
    }

    

    async toggleStatus(id, isActive) {
        try {
            const response = await app.apiCall(`../api.php?controller=product&action=update&id=${id}`, {
                method: 'POST',
                body: JSON.stringify({ is_active: isActive ? 1 : 0 })
            });

            if (response.success) {
                app.showToast(`Product ${isActive ? 'activated' : 'deactivated'}`, 'success');
                await this.loadProducts();
            } else {
                app.showToast(response.message || 'Failed to update status', 'error');
                // Reload to reset toggle
                await this.loadProducts();
            }
        } catch (error) {
            console.error('Failed to toggle status:', error);
            app.showToast('Failed to update status', 'error');
            // Reload to reset toggle
            await this.loadProducts();
        }
    }

    async deleteProduct(id, btn = null) {
        const product = this.products.find(p => p.id === id);
        if (!product) return;
        
        if (!confirm(`Are you sure you want to delete "${product.name}"?\n\nThis action cannot be undone.`)) { return; }
        const confirmText = prompt(`Type the product name to confirm deletion:\n\n${product.name}\n\nAlternatively, type DELETE to proceed.`);
        if (confirmText === null) { app.showToast('Deletion cancelled', 'info'); return; }
        const ok = (confirmText && (confirmText.trim() === product.name || confirmText.trim().toUpperCase() === 'DELETE'));
        if (!ok) { app.showToast('Confirmation text does not match', 'warning'); return; }

        try {
            const originalHtml = btn ? btn.innerHTML : '';
            if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>'; }
            const response = await app.apiCall(`../api.php?controller=product&action=delete&id=${id}`, {
                method: 'POST'
            });

            if (response.success) {
                app.showToast('Product deleted successfully', 'success');
                await this.loadProducts();
            } else {
                app.showToast(response.message || response.error || 'Failed to delete product', 'error');
            }
        } catch (error) {
            console.error('Failed to delete product:', error);
            app.showToast('Failed to delete product', 'error');
        } finally {
            if (btn) { btn.disabled = false; btn.innerHTML = originalHtml; }
        }
    }
}

// Initialize when DOM is ready
let productManager;
if (document.readyState === 'loading') {
document.addEventListener('DOMContentLoaded', () => {
    productManager = new ProductManager();
    // Expose globally for inline onclick handlers
    try { window.productManager = productManager; } catch (e) {}
});
} else {
    productManager = new ProductManager();
    try { window.productManager = productManager; } catch (e) {}
}
