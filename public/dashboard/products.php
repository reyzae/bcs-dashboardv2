<?php
/**
 * Products Management Page
 * Role-based access: Admin, Manager, Staff
 */

// Load bootstrap FIRST
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../app/helpers/functions.php';

// Require authentication
requireAuth();

// Admin, Manager, and Staff can manage products
requireRole(['admin', 'manager', 'staff']);

// Page configuration
$page_title = 'Products Management';
$header_compact = true; // compact header: title aligns with icons
$hide_welcome_banner = true; // Hide default welcome banner
$additional_css = [];
$additional_js = ['products.js'];

// Get current user
$current_user = getCurrentUser();

// Include header
require_once 'includes/header.php';
?>

<!-- Breadcrumb Navigation -->
<div class="breadcrumb">
    <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
    <i class="fas fa-chevron-right"></i>
    <span>Products Management</span>
</div>

<!-- Products Management Content -->
<div class="card mb-6 products-header-card">
    <div class="card-header flex-between">
        <div style="flex: 1;">
            <h3 class="card-title">
                <i class="fas fa-box"></i> Products Management
            </h3>
            <!-- Quick Stats -->
            <div class="quick-stats">
                <div class="quick-stat-item stat-total">
                    <i class="fas fa-box"></i>
                    <span><strong id="statsTotal">0</strong> Total</span>
                </div>
                <div class="quick-stat-item stat-active">
                    <i class="fas fa-check-circle"></i>
                    <span><strong id="statsActive">0</strong> Active</span>
                </div>
                <div class="quick-stat-item stat-low">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span><strong id="statsLowStock">0</strong> Low Stock</span>
                </div>
            </div>
        </div>
        <div class="header-actions">
            <button class="btn btn-secondary btn-sm" id="refreshBtn" title="Refresh product list (F5)"
                data-tooltip="Click to reload">
                <i class="fas fa-sync"></i>
                <span class="btn-responsive-text">Refresh</span>
            </button>

            <!-- Export Dropdown -->
            <div class="table-actions-dropdown" id="exportDropdown">
                <button class="btn btn-success btn-sm" onclick="productManager.toggleExportMenu()"
                    title="Export products">
                    <i class="fas fa-download"></i>
                    <span class="btn-responsive-text">Export</span>
                    <i class="fas fa-chevron-down" style="margin-left: 0.25rem; font-size: 0.75rem;"></i>
                </button>
                <div class="table-actions-menu" id="exportMenu" style="right: 0;">
                    <button class="table-actions-menu-item" onclick="productManager.exportData('csv')">
                        <i class="fas fa-file-csv"></i>
                        <span>Export as CSV</span>
                    </button>
                    <button class="table-actions-menu-item" onclick="productManager.exportData('excel')">
                        <i class="fas fa-file-excel"></i>
                        <span>Export as Excel</span>
                    </button>
                    <button class="table-actions-menu-item" onclick="productManager.exportData('pdf')">
                        <i class="fas fa-file-pdf"></i>
                        <span>Export as PDF</span>
                    </button>
                </div>
            </div>

            <button class="btn btn-primary btn-sm" id="addProductBtn" title="Add new product (Ctrl+N)">
                <i class="fas fa-plus"></i>
                <span class="btn-responsive-text">Add Product</span>
            </button>
        </div>
    </div>
    <div class="card-body">
        <p class="text-muted">Manage your inventory, pricing, and stock levels</p>
    </div>
</div>

<!-- Add Product Card (Inline Form) -->
<div class="card" id="addProductCard" style="margin-bottom: 2rem;">
    <div class="card-header flex-between">
        <h3 class="card-title">
            <i class="fas fa-plus-circle"></i> Add Product
        </h3>
        <button class="btn btn-secondary btn-sm" id="closeInlineFormBtn" title="Close">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="card-body" style="padding: 2rem;">
        <div style="display: grid; grid-template-columns: 1fr 320px; gap: 2rem;">
            <!-- Left Column: Form Fields -->
            <div>
                <form id="productForm">
                    <!-- Basic Information Section -->
                    <div style="margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 2px solid #f3f4f6;">
                        <h4 style="margin: 0 0 1.25rem 0; font-size: 1.125rem; font-weight: 600; color: #374151;">
                            <i class="fas fa-info-circle" style="color: var(--primary-color);"></i> Basic Information
                        </h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label for="productName" class="form-label"
                                    style="font-size: 0.875rem; font-weight: 500;">
                                    <i class="fas fa-tag" style="color: var(--primary-color);"></i> Product Name <span
                                        style="color: #ef4444; font-weight: 700;">*</span>
                                </label>
                                <input type="text" id="productName" class="form-input" placeholder="Enter product name"
                                    required>
                            </div>
                            <div class="form-group">
                                <label for="productSku" class="form-label"
                                    style="font-size: 0.875rem; font-weight: 500;">
                                    <i class="fas fa-barcode" style="color: var(--primary-color);"></i> SKU <span
                                        style="color: #ef4444; font-weight: 700;">*</span>
                                </label>
                                <input type="text" id="productSku" class="form-input" placeholder="e.g., PRD-001"
                                    required>
                            </div>
                            <div class="form-group">
                                <label for="productCategory" class="form-label"
                                    style="font-size: 0.875rem; font-weight: 500;">
                                    <i class="fas fa-folder" style="color: var(--primary-color);"></i> Category <span
                                        style="color: #ef4444; font-weight: 700;">*</span>
                                </label>
                                <select id="productCategory" class="form-select" required>
                                    <option value="">Select category...</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="productBarcode" class="form-label"
                                    style="font-size: 0.875rem; font-weight: 500;">
                                    <i class="fas fa-qrcode" style="color: var(--primary-color);"></i> Barcode
                                </label>
                                <input type="text" id="productBarcode" class="form-input"
                                    placeholder="Optional barcode">
                            </div>
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label for="productDescription" class="form-label"
                                    style="font-size: 0.875rem; font-weight: 500;">
                                    <i class="fas fa-align-left" style="color: var(--primary-color);"></i> Description
                                </label>
                                <textarea id="productDescription" class="form-input" rows="2"
                                    placeholder="Product description (optional)" style="resize: vertical;"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Pricing Section -->
                    <div style="margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 2px solid #f3f4f6;">
                        <h4 style="margin: 0 0 1.25rem 0; font-size: 1.125rem; font-weight: 600; color: #374151;">
                            <i class="fas fa-tags" style="color: #10b981;"></i> Pricing
                        </h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label for="productPrice" class="form-label"
                                    style="font-size: 0.875rem; font-weight: 500;">
                                    <i class="fas fa-tag" style="color: #10b981;"></i> Selling Price (Rp) <span
                                        style="color: #ef4444; font-weight: 700;">*</span>
                                </label>
                                <input type="number" id="productPrice" class="form-input" placeholder="0" min="0"
                                    step="100" required>
                            </div>
                            <div class="form-group">
                                <label for="productCostPrice" class="form-label"
                                    style="font-size: 0.875rem; font-weight: 500;">
                                    <i class="fas fa-money-bill" style="color: #10b981;"></i> Cost Price (Rp)
                                </label>
                                <input type="number" id="productCostPrice" class="form-input" placeholder="0" min="0"
                                    step="100">
                            </div>
                        </div>
                    </div>

                    <!-- Inventory Section -->
                    <div style="margin-bottom: 1.5rem;">
                        <h4 style="margin: 0 0 1.25rem 0; font-size: 1.125rem; font-weight: 600; color: #374151;">
                            <i class="fas fa-warehouse" style="color: #f59e0b;"></i> Inventory
                        </h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label for="productStock" class="form-label"
                                    style="font-size: 0.875rem; font-weight: 500;">
                                    <i class="fas fa-cubes" style="color: #f59e0b;"></i> Stock Quantity <span
                                        style="color: #ef4444; font-weight: 700;">*</span>
                                </label>
                                <input type="number" id="productStock" class="form-input" placeholder="0" min="0"
                                    required>
                            </div>
                            <div class="form-group">
                                <label for="productMinStock" class="form-label"
                                    style="font-size: 0.875rem; font-weight: 500;">
                                    <i class="fas fa-exclamation-triangle" style="color: #f59e0b;"></i> Min. Stock Level
                                </label>
                                <input type="number" id="productMinStock" class="form-input" placeholder="3" min="0"
                                    value="3">
                            </div>
                            <div class="form-group">
                                <label for="productUnit" class="form-label"
                                    style="font-size: 0.875rem; font-weight: 500;">
                                    <i class="fas fa-ruler" style="color: #f59e0b;"></i> Unit
                                </label>
                                <input type="text" id="productUnit" class="form-input" placeholder="pcs" value="pcs">
                            </div>
                        </div>
                        <div class="form-group" style="margin-top: 1.25rem;">
                            <label
                                style="font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem; display: block;">
                                <i class="fas fa-power-off" style="color: var(--primary-color);"></i> Product Status
                            </label>
                            <div class="modern-toggle-container"
                                style="background: #f9fafb; padding: 1rem; border-radius: 12px; border: 2px solid #e5e7eb;">
                                <label class="modern-toggle-switch"
                                    style="display: flex; align-items: center; justify-content: space-between; cursor: pointer; user-select: none;">
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <input type="checkbox" id="productActive" checked class="toggle-checkbox">
                                        <div class="toggle-track">
                                            <div class="toggle-thumb"></div>
                                        </div>
                                        <div class="toggle-labels">
                                            <span class="toggle-label-active"
                                                style="font-weight: 600; color: #10b981; display: flex; align-items: center; gap: 0.5rem;">
                                                <i class="fas fa-check-circle"></i>
                                                <span>Active</span>
                                            </span>
                                            <span class="toggle-label-inactive"
                                                style="font-weight: 600; color: #ef4444; display: none; align-items: center; gap: 0.5rem;">
                                                <i class="fas fa-times-circle"></i>
                                                <span>Inactive</span>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="toggle-badge"
                                        style="background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); color: #065f46; padding: 0.375rem 0.875rem; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                                        <i class="fas fa-circle"
                                            style="font-size: 0.5rem; margin-right: 0.25rem; animation: pulse 2s ease-in-out infinite;"></i>
                                        <span class="badge-text">Available</span>
                                    </div>
                                </label>
                                <p
                                    style="margin: 0.75rem 0 0 0; font-size: 0.75rem; color: #6b7280; padding-left: 4rem;">
                                    <i class="fas fa-info-circle"></i>
                                    <span class="toggle-hint">This product will be visible in POS and can be sold</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div
                        style="display: flex; gap: 0.75rem; margin-top: 2rem; padding-top: 1.5rem; border-top: 2px solid #f3f4f6;">
                        <button type="submit" class="btn btn-primary"
                            style="flex: 1; padding: 0.875rem; font-weight: 600; font-size: 1rem; background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%); border: none;">
                            <i class="fas fa-save"></i> Save Product
                        </button>
                        <button type="button" id="resetFormBtn" class="btn btn-secondary"
                            style="padding: 0.875rem 1.5rem; font-weight: 600;">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                    </div>
                </form>
            </div>

            <!-- Right Column: Product Image Upload -->
            <div style="position: sticky; top: 0; align-self: start;">
                <!-- Product Image Upload Section -->
                <div
                    style="background: white; border: 2px solid #e5e7eb; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem;">
                    <h4
                        style="margin: 0 0 1rem 0; font-size: 1rem; font-weight: 700; color: #374151; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-image" style="color: var(--primary-color);"></i> Product Image
                    </h4>

                    <!-- Image Upload Area -->
                    <div id="imageUploadContainer">
                        <!-- Preview Area (when image uploaded) -->
                        <div id="imagePreviewArea" style="display: none;">
                            <div
                                style="position: relative; border-radius: 12px; overflow: hidden; background: #f9fafb; aspect-ratio: 1/1;">
                                <img id="productImagePreview" src="" alt="Product"
                                    style="width: 100%; height: 100%; object-fit: cover;">

                                <!-- Image Actions Overlay -->
                                <div class="image-actions-overlay"
                                    style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; gap: 0.75rem; opacity: 0; transition: opacity 0.3s;">
                                    <button type="button" id="zoomImageBtn" class="image-action-btn"
                                        title="View Full Size">
                                        <i class="fas fa-search-plus"></i>
                                    </button>
                                    <button type="button" id="replaceImageBtn" class="image-action-btn"
                                        title="Replace Image">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                    <button type="button" id="deleteImageBtn" class="image-action-btn"
                                        title="Remove Image">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Image Info -->
                            <div id="imageInfo"
                                style="margin-top: 0.75rem; padding: 0.75rem; background: #f9fafb; border-radius: 8px; font-size: 0.75rem; color: #6b7280;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                                    <span><i class="fas fa-file"></i> <span id="imageFileName">image.jpg</span></span>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span><i class="fas fa-weight"></i> <span id="imageFileSize">0 KB</span></span>
                                    <span><i class="fas fa-expand"></i> <span id="imageDimensions">0x0</span></span>
                                </div>
                            </div>
                        </div>

                        <!-- Drop Zone (when no image) -->
                        <div id="imageDropZone" class="image-drop-zone"
                            style="border: 2px dashed #d1d5db; border-radius: 12px; padding: 2rem 1rem; text-align: center; background: #f9fafb; cursor: pointer; transition: all 0.3s;">
                            <div style="color: #667eea; font-size: 3rem; margin-bottom: 1rem;">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <div style="color: #374151; font-weight: 600; font-size: 0.9375rem; margin-bottom: 0.5rem;">
                                Drop image here or click to browse
                            </div>
                            <div style="color: #9ca3af; font-size: 0.75rem; margin-bottom: 1rem;">
                                Supports: JPG, PNG, WEBP<br>
                                Max size: 5MB
                            </div>
                            <button type="button" id="selectImageBtn" class="btn btn-secondary"
                                style="padding: 0.625rem 1.25rem; font-size: 0.875rem;">
                                <i class="fas fa-folder-open"></i> Choose File
                            </button>
                        </div>

                        <!-- Hidden File Input -->
                        <input type="file" id="productImageInput" accept="image/jpeg,image/jpg,image/png,image/webp"
                            style="display: none;">

                        <!-- Upload Progress -->
                        <div id="uploadProgress" style="display: none; margin-top: 1rem;">
                            <div style="background: #e5e7eb; border-radius: 999px; height: 8px; overflow: hidden;">
                                <div id="uploadProgressBar"
                                    style="background: linear-gradient(90deg, #667eea 0%, #764ba2 100%); height: 100%; width: 0%; transition: width 0.3s;">
                                </div>
                            </div>
                            <div style="margin-top: 0.5rem; text-align: center; font-size: 0.75rem; color: #6b7280;">
                                Uploading... <span id="uploadProgressText">0%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Keyboard Shortcuts (Collapsed) -->
                <div
                    style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border: 2px solid #0ea5e9; border-radius: 12px; padding: 1.5rem;">
                    <h4
                        style="margin: 0 0 1rem 0; font-size: 1rem; font-weight: 700; color: #0369a1; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-keyboard"></i> Keyboard Shortcuts
                    </h4>

                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <!-- Shortcut Card -->
                        <div
                            style="background: white; border: 1px solid #bae6fd; border-radius: 8px; padding: 0.875rem;">
                            <div style="font-weight: 600; color: #374151; margin-bottom: 0.25rem; font-size: 0.875rem;">
                                <kbd style="margin-right: 0.5rem;">Ctrl+N</kbd> Add New
                            </div>
                            <div style="font-size: 0.75rem; color: #6b7280;">
                                Open this form modal
                            </div>
                        </div>

                        <div
                            style="background: white; border: 1px solid #bae6fd; border-radius: 8px; padding: 0.875rem;">
                            <div style="font-weight: 600; color: #374151; margin-bottom: 0.25rem; font-size: 0.875rem;">
                                <kbd style="margin-right: 0.5rem;">F5</kbd> Refresh
                            </div>
                            <div style="font-size: 0.75rem; color: #6b7280;">
                                Reload products list
                            </div>
                        </div>

                        <div
                            style="background: white; border: 1px solid #bae6fd; border-radius: 8px; padding: 0.875rem;">
                            <div style="font-weight: 600; color: #374151; margin-bottom: 0.25rem; font-size: 0.875rem;">
                                <kbd style="margin-right: 0.5rem;">Ctrl+F</kbd> Search
                            </div>
                            <div style="font-size: 0.75rem; color: #6b7280;">
                                Focus search input
                            </div>
                        </div>

                        <div
                            style="background: white; border: 1px solid #bae6fd; border-radius: 8px; padding: 0.875rem;">
                            <div style="font-weight: 600; color: #374151; margin-bottom: 0.25rem; font-size: 0.875rem;">
                                <kbd style="margin-right: 0.5rem;">ESC</kbd> Close
                            </div>
                            <div style="font-size: 0.75rem; color: #6b7280;">
                                Close this form
                            </div>
                        </div>
                    </div>

                    <!-- Pro Tip -->
                    <div
                        style="margin-top: 1rem; padding: 0.875rem; background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-left: 4px solid #f59e0b; border-radius: 6px;">
                        <div style="font-weight: 600; color: #92400e; margin-bottom: 0.25rem; font-size: 0.8125rem;">
                            <i class="fas fa-lightbulb" style="color: #f59e0b;"></i> Pro Tip
                        </div>
                        <div style="font-size: 0.75rem; color: #78350f;">
                            Use <kbd style="font-size: 0.625rem; padding: 0.125rem 0.375rem;">Tab</kbd> to navigate
                            between fields quickly!
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters and Search -->
<div class="card filter-card-sticky" style="margin-bottom: 2rem;">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-filter"></i> Filter Products
        </h3>
    </div>
    <div class="card-body">
        <div class="form-grid"
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
            <div class="form-group">
                <label for="searchInput" class="form-label">Search Products</label>
                <input type="text" id="searchInput" class="form-input" placeholder="Search by name or SKU..."
                    autocomplete="off">
            </div>
            <div class="form-group">
                <label for="categoryFilter" class="form-label">Category</label>
                <select id="categoryFilter" class="form-select">
                    <option value="">All Categories</option>
                </select>
            </div>
            <div class="form-group">
                <label for="statusFilter" class="form-label">Status</label>
                <select id="statusFilter" class="form-select">
                    <option value="">All Status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- Products Table -->
<div class="card">
    <div class="card-header flex-between">
        <h3 class="card-title">
            <i class="fas fa-list"></i> Products List
        </h3>
        <span class="badge badge-primary badge-lg">
            <i class="fas fa-box"></i> <span id="totalProductsCount">0</span> Products
        </span>
    </div>
    <div class="card-body">
        <!-- Desktop Table View -->
        <div class="table-container">
            <table class="table" id="productsTable">
                <thead>
                    <tr>
                        <th style="width: 80px;">Image</th>
                        <th>Name</th>
                        <th style="width: 120px;">SKU</th>
                        <th style="width: 150px;">Category</th>
                        <th style="width: 130px; text-align: right;">Price</th>
                        <th style="width: 100px; text-align: center;">Stock</th>
                        <th style="width: 100px; text-align: center;">Status</th>
                        <th style="width: 180px; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Loading state -->
                    <tr id="loadingRow">
                        <td colspan="8" style="text-align: center; padding: 3rem;">
                            <i class="fas fa-spinner fa-spin"
                                style="font-size: 2rem; color: #667eea; margin-bottom: 1rem;"></i>
                            <p style="color: #6b7280;">Loading products...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Mobile Card View -->
        <div class="mobile-card-view" id="mobileProductsView" style="display: none;">
            <!-- Mobile cards will be rendered here by JavaScript -->
        </div>

        <!-- Pagination -->
        <div class="pagination" id="pagination"></div>
    </div>
</div>

<!-- Modern Toggle CSS -->
<style>
    /* Toggle Checkbox - Hidden */
    .toggle-checkbox {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }

    /* Toggle Track */
    .toggle-track {
        position: relative;
        width: 56px;
        height: 28px;
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        border-radius: 28px;
        transition: all 0.4s cubic-bezier(0.4, 0.0, 0.2, 1);
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
        border: 2px solid rgba(0, 0, 0, 0.05);
    }

    /* Toggle Thumb */
    .toggle-thumb {
        position: absolute;
        height: 22px;
        width: 22px;
        left: 2px;
        top: 1px;
        background: white;
        border-radius: 50%;
        transition: all 0.4s cubic-bezier(0.4, 0.0, 0.2, 1);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }

    /* Checked State */
    .toggle-checkbox:checked+.toggle-track {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }

    .toggle-checkbox:checked+.toggle-track .toggle-thumb {
        transform: translateX(28px);
    }

    /* Hover Effect */
    .modern-toggle-switch:hover .toggle-track {
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.15), 0 0 0 4px rgba(102, 126, 234, 0.1);
    }

    /* Focus State (Accessibility) */
    .toggle-checkbox:focus+.toggle-track {
        outline: 2px solid #667eea;
        outline-offset: 2px;
    }

    /* Pulse Animation for Badge */
    @keyframes pulse {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.5;
        }
    }

    /* Responsive */
    @media (max-width: 640px) {
        .modern-toggle-container {
            padding: 0.75rem !important;
        }

        .toggle-badge {
            padding: 0.25rem 0.5rem !important;
            font-size: 0.65rem !important;
        }
    }

    /* ============================================================================
   IMAGE UPLOAD STYLES
   ============================================================================ */

    /* Drop Zone */
    .image-drop-zone:hover {
        border-color: #667eea !important;
        background: #f0f9ff !important;
    }

    .image-drop-zone.drag-over {
        border-color: #667eea !important;
        background: linear-gradient(135deg, #e0f2fe 0%, #ddd6fe 100%) !important;
        transform: scale(1.02);
    }

    /* Image Preview Hover Effect */
    #imagePreviewArea:hover .image-actions-overlay {
        opacity: 1 !important;
    }

    /* Image Action Buttons */
    .image-action-btn {
        width: 40px;
        height: 40px;
        border: none;
        border-radius: 50%;
        background: white;
        color: #374151;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }

    .image-action-btn:hover {
        transform: scale(1.1);
        color: #667eea;
    }

    .image-action-btn:active {
        transform: scale(0.95);
    }

    #deleteImageBtn:hover {
        color: #ef4444 !important;
    }

    /* Image Zoom Modal */
    .image-zoom-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.9);
        z-index: 10000;
        justify-content: center;
        align-items: center;
        cursor: zoom-out;
    }

    .image-zoom-modal.show {
        display: flex;
    }

    .image-zoom-modal img {
        max-width: 90%;
        max-height: 90%;
        object-fit: contain;
        animation: zoomIn 0.3s ease;
    }

    @keyframes zoomIn {
        from {
            transform: scale(0.5);
            opacity: 0;
        }

        to {
            transform: scale(1);
            opacity: 1;
        }
    }

    /* Upload Animation */
    @keyframes uploadPulse {

        0%,
        100% {
            opacity: 0.6;
        }

        50% {
            opacity: 1;
        }
    }

    #uploadProgress {
        animation: uploadPulse 1.5s infinite;
    }
</style>

<!-- Modern Toggle JavaScript -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const productActiveCheckbox = document.getElementById('productActive');

        if (productActiveCheckbox) {
            // Initial state
            updateToggleUI(productActiveCheckbox.checked);

            // Toggle change handler
            productActiveCheckbox.addEventListener('change', function () {
                const isActive = this.checked;
                updateToggleUI(isActive);

                // Optional: Add haptic feedback (if supported)
                if (navigator.vibrate) {
                    navigator.vibrate(50);
                }
            });
        }

        function updateToggleUI(isActive) {
            const activeLabel = document.querySelector('.toggle-label-active');
            const inactiveLabel = document.querySelector('.toggle-label-inactive');
            const badge = document.querySelector('.toggle-badge');
            const badgeText = badge.querySelector('.badge-text');
            const hint = document.querySelector('.toggle-hint');
            const container = document.querySelector('.modern-toggle-container');

            if (isActive) {
                // Active State
                activeLabel.style.display = 'flex';
                inactiveLabel.style.display = 'none';
                badge.style.background = 'linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%)';
                badge.style.color = '#065f46';
                badgeText.textContent = 'Available';
                hint.textContent = 'This product will be visible in POS and can be sold';
                container.style.borderColor = '#d1fae5';
                container.style.background = '#f0fdf4';
            } else {
                // Inactive State
                activeLabel.style.display = 'none';
                inactiveLabel.style.display = 'flex';
                badge.style.background = 'linear-gradient(135deg, #fee2e2 0%, #fecaca 100%)';
                badge.style.color = '#991b1b';
                badgeText.textContent = 'Disabled';
                hint.textContent = 'This product will be hidden from POS and cannot be sold';
                container.style.borderColor = '#fecaca';
                container.style.background = '#fef2f2';
            }
        }
    });
</script>

<!-- Product Image Upload JavaScript -->
<script src="../assets/js/product-image-upload.js"></script>

<?php require_once 'includes/footer.php'; ?>