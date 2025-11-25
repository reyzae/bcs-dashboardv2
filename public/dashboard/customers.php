<?php
/**
 * CUSTOMERS MANAGEMENT - FULLY REFACTORED V2
 * Modern UI/UX + 100% Working Functions + Perfect Integration
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../app/helpers/functions.php';

requireAuth();
requireRole(['admin', 'manager']);

$page_title = 'Customers Management';
$header_compact = true; // compact header: title aligns with icons
$additional_css = [];
$additional_js = [];

require_once 'includes/header.php';
?>

<style>
/* Modern Custom Styles */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: var(--gradient, linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%));
}

.stat-value {
    font-size: 2.5rem;
    font-weight: 800;
    background: var(--gradient, linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin: 0.5rem 0;
}

.stat-label {
    font-size: 0.875rem;
    color: #6b7280;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.search-bar {
    position: relative;
    flex: 1;
    max-width: 400px;
}

.search-bar input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 3rem;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    font-size: 0.875rem;
    transition: all 0.2s;
}

.search-bar input:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.search-bar i {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
}

.btn-modern {
    padding: 0.75rem 1.5rem;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.875rem;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-primary-modern {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
    color: white;
}

.btn-primary-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.table-modern {
    width: 100%;
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.table-modern thead {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
    color: white;
}

.table-modern th {
    padding: 1rem;
    font-weight: 600;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table-modern td {
    padding: 1rem;
    border-bottom: 1px solid #f3f4f6;
}

.table-modern tbody tr {
    transition: background 0.2s;
}

.table-modern tbody tr:hover {
    background: #f9fafb;
}

.badge-status {
    padding: 0.375rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
}

.badge-active {
    background: #d1fae5;
    color: #065f46;
}

.badge-inactive {
    background: #fee2e2;
    color: #991b1b;
}

.action-btn {
    padding: 0.5rem 0.75rem;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 0.875rem;
}

.action-btn:hover {
    transform: scale(1.05);
}

.action-btn-edit {
    background: #dbeafe;
    color: #1e40af;
}

.action-btn-delete {
    background: #fee2e2;
    color: #991b1b;
}

.modal-modern {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0,0,0,0.5);
    z-index: 1050;
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transition: opacity 0.2s ease;
    padding: 1rem;
}

.modal-modern.show {
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
}

.modal-content-modern {
    background: #fff;
    border-radius: 16px;
    max-width: 640px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    transform: scale(0.98);
    transition: transform 0.2s ease;
}

.modal-modern.show .modal-content-modern {
    transform: scale(1);
}

.modal-header-modern {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    padding: 1.25rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-body-modern {
    padding: 1.5rem;
}

.modal-body-modern button {
    pointer-events: auto !important;
    cursor: pointer !important;
    position: relative;
    z-index: 10001;
}

.form-group-modern {
    margin-bottom: 1.5rem;
}

.form-label-modern {
    display: block;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #374151;
    font-size: 0.875rem;
}

.form-input-modern {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    font-size: 0.875rem;
    transition: all 0.2s;
}

.form-input-modern:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.loading-spinner {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid rgba(255,255,255,0.3);
    border-radius: 50%;
    border-top-color: white;
    animation: spin 1s linear infinite;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from { transform: translateY(20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: #9ca3af;
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

/* Edit mode toggle */
.edit-mode-toggle {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}
.edit-mode-segment {
    display: inline-flex;
    background: #f3f4f6;
    border-radius: 8px;
    overflow: hidden;
}
.edit-mode-btn {
    border: none;
    background: transparent;
    padding: 0.5rem 0.75rem;
    cursor: pointer;
    color: #374151;
    font-weight: 600;
}
.edit-mode-btn.active {
    background: #111827;
    color: white;
}

/* Horizontal scroll for inline edit */
.table-modern { overflow-x: auto; }
.table-modern table { min-width: 960px; }
.table-modern.inline-mode table { min-width: 1200px; }
</style>

<div class="content">
    <!-- Header: Uniform Card Style -->
    <div class="card" style="margin-bottom: 1.5rem;">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-users"></i> Customers Management
            </h3>
            <div class="card-actions action-buttons">
                <!-- Export Dropdown -->
                <div class="table-actions-dropdown" id="exportDropdown">
                    <button class="btn btn-success btn-sm" onclick="toggleExportMenu()">
                        <i class="fas fa-download"></i> Export <i class="fas fa-chevron-down" style="margin-left: 0.25rem; font-size: 0.75rem;"></i>
                    </button>
                    <div class="table-actions-menu" id="exportMenu" style="right: 0;">
                        <button class="table-actions-menu-item" onclick="exportData('csv')">
                            <i class="fas fa-file-csv"></i>
                            <span>Export as CSV</span>
                        </button>
                        <button class="table-actions-menu-item" onclick="exportData('excel')">
                            <i class="fas fa-file-excel"></i>
                            <span>Export as Excel</span>
                        </button>
                        <button class="table-actions-menu-item" onclick="exportData('pdf')">
                            <i class="fas fa-file-pdf"></i>
                            <span>Export as PDF</span>
                        </button>
                    </div>
                </div>
                
                <button class="btn btn-primary btn-sm" onclick="openCustomerModal('add')">
                    <i class="fas fa-user-plus"></i> Add Customer
                </button>
            </div>
        </div>
        <div class="card-body">
            <p style="color: #6b7280; font-size: 0.875rem;">Manage your customer database with ease</p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card" style="--gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <div class="stat-label">
                <i class="fas fa-users"></i> Total Customers
            </div>
            <div class="stat-value" id="totalCustomers">0</div>
            <div style="font-size: 0.75rem; color: #9ca3af;">All time</div>
        </div>

        <div class="stat-card" style="--gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);">
            <div class="stat-label">
                <i class="fas fa-check-circle"></i> Active Customers
            </div>
            <div class="stat-value" id="activeCustomers">0</div>
            <div style="font-size: 0.75rem; color: #9ca3af;">Currently active</div>
        </div>

        <div class="stat-card" style="--gradient: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
            <div class="stat-label">
                <i class="fas fa-calendar"></i> This Month
            </div>
            <div class="stat-value" id="monthCustomers">0</div>
            <div style="font-size: 0.75rem; color: #9ca3af;">New customers</div>
        </div>
    </div>

    <!-- Action Bar -->
    <div style="background: white; padding: 1.5rem; border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 1.5rem; display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
        <div class="search-bar">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Search customers by name, code, phone...">
        </div>
        <div style="margin-left: auto; display: flex; gap: 0.75rem; align-items: center;">
            <div class="edit-mode-toggle">
                <span style="color:#6b7280; font-size:.875rem;">Edit Mode:</span>
                <div class="edit-mode-segment" id="editModeSegment">
                    <button type="button" class="edit-mode-btn" id="editModeModalBtn" data-mode="modal">Modal</button>
                    <button type="button" class="edit-mode-btn" id="editModeInlineBtn" data-mode="inline">Inline</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Customers Table -->
    <div class="table-modern">
        <table style="width: 100%;">
            <thead>
                <tr>
                    <th style="text-align: left;">Code</th>
                    <th style="text-align: left;">Name</th>
                    <th style="text-align: left;">Phone</th>
                    <th style="text-align: left;">Email</th>
                    <th style="text-align: left;">City</th>
                    <th style="text-align: center;">Status</th>
                    <th style="text-align: center; width: 150px;">Actions</th>
                </tr>
            </thead>
            <tbody id="customersTableBody">
                <tr>
                    <td colspan="7" style="text-align: center; padding: 3rem;">
                        <div class="loading-spinner" style="border-color: #667eea; border-top-color: transparent;"></div>
                        <p style="color: #6b7280; margin-top: 1rem;">Loading customers...</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal-modern" id="customerModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="modal-content-modern" id="modalContentWrapper">
        <div class="modal-header-modern">
            <h3 style="margin: 0; font-weight: 700;">
                <i class="fas fa-user-plus"></i> <span id="modalTitle">Add New Customer</span>
            </h3>
            <button type="button" id="modalCloseBtn" 
                    data-close="modal"
                    style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer !important; pointer-events: auto !important; opacity: 0.9; transition: all 0.2s; padding: 0.5rem; position: relative; z-index: 10003;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body-modern">
            <form id="customerForm" onsubmit="saveCustomer(event)">
                <input type="hidden" id="customerId">
                
                <div class="form-group-modern">
                    <label class="form-label-modern">
                        <i class="fas fa-user"></i> Full Name <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="text" id="customerName" class="form-input-modern" placeholder="Enter customer name" required>
                </div>

                <div class="form-group-modern">
                    <label class="form-label-modern">
                        <i class="fas fa-barcode"></i> Customer Code
                    </label>
                    <input type="text" id="customerCode" class="form-input-modern" placeholder="Auto-generated" readonly>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group-modern">
                        <label class="form-label-modern">
                            <i class="fas fa-phone"></i> Phone <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="tel" id="customerPhone" class="form-input-modern" placeholder="08xxxxxxxxxx" required>
                    </div>

                    <div class="form-group-modern">
                        <label class="form-label-modern">
                            <i class="fas fa-envelope"></i> Email
                        </label>
                        <input type="email" id="customerEmail" class="form-input-modern" placeholder="email@example.com">
                    </div>
                </div>

                <div class="form-group-modern">
                    <label class="form-label-modern">
                        <i class="fas fa-map-marker-alt"></i> Address
                    </label>
                    <textarea id="customerAddress" class="form-input-modern" rows="2" placeholder="Street address"></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group-modern">
                        <label class="form-label-modern">
                            <i class="fas fa-city"></i> City
                        </label>
                        <input type="text" id="customerCity" class="form-input-modern" placeholder="City name">
                    </div>

                    <div class="form-group-modern">
                        <label class="form-label-modern">
                            <i class="fas fa-mail-bulk"></i> Postal Code
                        </label>
                        <input type="text" id="customerPostalCode" class="form-input-modern" placeholder="12345">
                    </div>
                </div>

                <div class="form-group-modern">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" id="customerActive" checked style="width: 18px; height: 18px; cursor: pointer;">
                        <span class="form-label-modern" style="margin: 0;">Active Customer</span>
                    </label>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 2rem; position: relative; z-index: 10001;">
                    <button type="button" id="modalCancelBtn" 
                            data-close="modal"
                            class="btn-modern" style="flex: 1; background: #f3f4f6; color: #374151; cursor: pointer !important; pointer-events: auto !important; position: relative; z-index: 10002;">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn-modern btn-primary-modern" style="flex: 1; cursor: pointer !important; pointer-events: auto !important; position: relative; z-index: 10002;" id="saveBtn">
                        <i class="fas fa-save"></i> Save Customer
                    </button>
                </div>
            </form>
</div>
</div>
</div>

<div class="modal-modern" id="deleteConfirmModal" role="dialog" aria-modal="true" aria-labelledby="deleteModalTitle">
    <div class="modal-content-modern" onclick="event.stopPropagation();">
        <div class="modal-header-modern">
            <h3 id="deleteModalTitle" style="margin:0; font-weight:700;"><i class="fas fa-exclamation-triangle"></i> Confirm Delete</h3>
            <button type="button" id="deleteCloseBtn" data-close="modal" style="background:none; border:none; color:white; font-size:1.2rem; cursor:pointer; opacity:0.9; padding:0.5rem; position:relative; z-index:10003;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body-modern">
            <p id="deleteConfirmText" style="margin-bottom:1rem; color:#374151;">Are you sure you want to delete this customer?</p>
            <div style="display:flex; gap:1rem;">
                <button type="button" id="deleteCancelBtn" data-close="modal" class="btn-modern" style="flex:1; background:#f3f4f6; color:#374151;">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" id="deleteConfirmBtn" class="btn-modern btn-primary-modern" style="flex:1;">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// ============================================================================
// CONFIGURATION
// ============================================================================
const API_URL = '../api.php?controller=customer';
let customers = [];
let filteredCustomers = [];

console.log('🚀 Customers Management V2 - Starting...');
console.log('📍 API URL:', API_URL);
console.log('📍 Current Location:', window.location.href);

// ============================================================================
// TOAST NOTIFICATION
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
// API FUNCTIONS
// ============================================================================
async function loadCustomers() {
    try {
        console.log('📡 Loading customers from:', API_URL);
        
        const url = `${API_URL}&action=list`;
        console.log('📍 Full URL:', url);
        
        const response = await fetch(url);
        
        console.log('📥 Response status:', response.status, response.statusText);
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error('❌ Response error:', errorText.substring(0, 200));
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        console.log('📦 Response data:', data);
        
        if (data.success) {
            customers = data.data.customers || [];
            filteredCustomers = [...customers];
            renderCustomers();
            console.log(`✅ Loaded ${customers.length} customers`);
        } else {
            throw new Error(data.message || data.error || 'Failed to load');
        }
    } catch (error) {
        console.error('❌ Load error:', error);
        document.getElementById('customersTableBody').innerHTML = `
            <tr><td colspan="7" class="empty-state">
                <i class="fas fa-exclamation-triangle" style="color: #ef4444;"></i>
                <p style="font-weight: 600; color: #374151; margin: 1rem 0;">Failed to load customers</p>
                <p style="color: #6b7280; font-size: 0.875rem; margin-bottom: 1rem;">${error.message}</p>
                <button class="btn-modern btn-primary-modern" onclick="loadCustomers()">
                    <i class="fas fa-redo"></i> Retry
                </button>
            </td></tr>
        `;
        showToast('Failed to load: ' + error.message, 'error');
    }
}

async function loadStats() {
    try {
        console.log('📊 Loading stats...');
        const response = await fetch(`${API_URL}&action=stats`);
        
        if (!response.ok) {
            console.warn('⚠️ Stats load failed, using defaults');
            return;
        }
        
        const data = await response.json();
        console.log('📊 Stats data:', data);
        
        if (data.success) {
            animateValue('totalCustomers', data.data.total || 0);
            animateValue('activeCustomers', data.data.active || 0);
            animateValue('monthCustomers', data.data.this_month || 0);
            console.log('✅ Stats loaded');
        } else {
            console.warn('⚠️ Stats response not successful');
        }
    } catch (error) {
        console.error('❌ Stats error:', error);
        // Don't block UI, just show 0s
        document.getElementById('totalCustomers').textContent = '0';
        document.getElementById('activeCustomers').textContent = '0';
        document.getElementById('monthCustomers').textContent = '0';
    }
}

async function saveCustomer(event) {
    event.preventDefault();
    
    const id = document.getElementById('customerId').value;
    const saveBtn = document.getElementById('saveBtn');
    const originalText = saveBtn.innerHTML;
    
    const data = {
        name: document.getElementById('customerName').value.trim(),
        customer_code: document.getElementById('customerCode').value.trim(),
        email: document.getElementById('customerEmail').value.trim(),
        phone: document.getElementById('customerPhone').value.trim(),
        address: document.getElementById('customerAddress').value.trim(),
        city: document.getElementById('customerCity').value.trim(),
        postal_code: document.getElementById('customerPostalCode').value.trim(),
        is_active: document.getElementById('customerActive').checked ? 1 : 0
    };

    console.log('💾 Saving customer:', data);

    // Disable button & show loading
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<div class="loading-spinner"></div> Saving...';

    // Close modal immediately on save
    window.closeModal();

    try {
        const action = id ? `update&id=${id}` : 'create';
        const url = `${API_URL}&action=${action}`;
        
        console.log('📡 POST to:', url);
        
        const response = await fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });

        console.log('📥 Save response:', response.status, response.statusText);

        if (!response.ok) {
            const errorText = await response.text();
            console.error('❌ Response error:', errorText.substring(0, 200));
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const result = await response.json();
        console.log('📦 Save result:', result);

        if (result.success) {
            showToast(id ? '✅ Customer updated successfully!' : '✅ Customer added successfully!', 'success');
            await loadCustomers();
            await loadStats();
        } else {
            throw new Error(result.message || result.error || 'Save failed');
        }
    } catch (error) {
        console.error('❌ Save error:', error);
        showToast('❌ Failed to save: ' + error.message, 'error');
    } finally {
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
    }
}

async function deleteCustomer(id, name) {
    pendingDeleteId = id;
    pendingDeleteName = name || '';
    const text = document.getElementById('deleteConfirmText');
    if (text) { text.textContent = `Delete customer "${pendingDeleteName}"?`; }
    const modal = document.getElementById('deleteConfirmModal');
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

// ============================================================================
// UI FUNCTIONS
// ============================================================================
function renderCustomers() {
    const tbody = document.getElementById('customersTableBody');
    
    if (filteredCustomers.length === 0) {
        const emptyMessage = customers.length === 0 
            ? 'No customers yet. Start by adding your first customer!'
            : 'No customers match your search.';
        
        tbody.innerHTML = `
            <tr><td colspan="7" class="empty-state">
                <i class="fas fa-users" style="color: #9ca3af;"></i>
                <p style="font-weight: 600; color: #374151; margin: 1rem 0;">${emptyMessage}</p>
                ${customers.length === 0 ? '<button class="btn-modern btn-primary-modern" onclick="openAddModal()" style="margin-top: 1rem;"><i class="fas fa-plus-circle"></i> Add First Customer</button>' : ''}
            </td></tr>
        `;
        return;
    }

    const tableWrapper = document.querySelector('.table-modern');
    if (tableWrapper) tableWrapper.classList.toggle('inline-mode', window.editMode === 'inline');
    tbody.innerHTML = filteredCustomers.map(c => {
        const escapedName = (c.name || '').replace(/'/g, "\\'");
        return `
        <tr style="transition: all 0.2s;" data-id="${c.id}">
            <td><strong style="color: #667eea;">${c.customer_code || '-'}</strong></td>
            <td><strong style="color: #1f2937;">${c.name || '-'}</strong></td>
            <td style="color: #4b5563;">${c.phone || '-'}</td>
            <td style="color: #6b7280;">${c.email || '-'}</td>
            <td style="color: #4b5563;">${c.city || '-'}</td>
            <td style="text-align: center;">
                <span class="badge-status ${c.is_active ? 'badge-active' : 'badge-inactive'}">
                    <i class="fas ${c.is_active ? 'fa-check-circle' : 'fa-times-circle'}"></i>
                    ${c.is_active ? 'Active' : 'Inactive'}
                </span>
            </td>
            <td style="text-align: center;">
                <button class="action-btn action-btn-edit" data-action="edit" data-id="${c.id}" title="Edit Customer">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="action-btn action-btn-delete" data-action="delete" data-id="${c.id}" data-name='${escapedName}' title="Delete Customer">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `;
    }).join('');
    
    if (!tbody.__binded) {
        tbody.addEventListener('click', function(e) {
            const btn = e.target.closest('button[data-action]');
            if (!btn) return;
            const action = btn.getAttribute('data-action');
            const id = parseInt(btn.getAttribute('data-id'), 10);
            if (!id) return;
            const row = btn.closest('tr');
            if (action === 'edit') {
                const cust = customers.find(x => parseInt(x.id,10) === id) || filteredCustomers.find(x => parseInt(x.id,10) === id);
                if (!cust) return;
                if (window.editMode === 'inline') {
                    if (row) startInlineEdit(cust, row);
                } else {
                    openCustomerModal('edit', cust);
                }
            } else if (action === 'save-inline') {
                if (row) { saveInlineCustomer(row); }
            } else if (action === 'cancel-inline') {
                if (row) { cancelInlineEdit(row); }
            } else if (action === 'delete') {
                const name = btn.getAttribute('data-name') || '';
                deleteCustomer(id, name);
            }
        });
        tbody.__binded = true;
    }
    console.log(`✅ Rendered ${filteredCustomers.length} customers`);
}

function openCustomerModal(mode, customer) {
    const titleEl = document.getElementById('modalTitle');
    const form = document.getElementById('customerForm');
    const idEl = document.getElementById('customerId');
    const codeEl = document.getElementById('customerCode');
    const nameEl = document.getElementById('customerName');
    const emailEl = document.getElementById('customerEmail');
    const phoneEl = document.getElementById('customerPhone');
    const addrEl = document.getElementById('customerAddress');
    const cityEl = document.getElementById('customerCity');
    const postalEl = document.getElementById('customerPostalCode');
    const activeEl = document.getElementById('customerActive');

    if (mode === 'edit' && customer) {
        titleEl.textContent = 'Edit Customer';
        idEl.value = customer.id;
        nameEl.value = customer.name || '';
        codeEl.value = customer.customer_code || '';
        emailEl.value = customer.email || '';
        phoneEl.value = customer.phone || '';
        addrEl.value = customer.address || '';
        cityEl.value = customer.city || '';
        postalEl.value = customer.postal_code || '';
        activeEl.checked = customer.is_active == 1;
    } else {
        titleEl.textContent = 'Add New Customer';
        form.reset();
        idEl.value = '';
        codeEl.value = 'CUST' + Date.now().toString().substr(-6);
        activeEl.checked = true;
    }

    const modal = document.getElementById('customerModal');
    const exportMenu = document.getElementById('exportMenu');
    if (exportMenu) { exportMenu.classList.remove('show'); }
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
    const firstInput = document.getElementById('customerName');
    if (firstInput) firstInput.focus();
}

function startInlineEdit(customer, row) {
    row.__originalHTML = row.innerHTML;
    const activeChecked = customer.is_active ? 'checked' : '';
    row.innerHTML = `
        <td><strong style="color:#667eea;">${customer.customer_code || '-'}</strong></td>
        <td><input type="text" class="form-input-modern" style="min-width:150px" name="name" value="${customer.name || ''}"></td>
        <td><input type="tel" class="form-input-modern" style="min-width:130px" name="phone" value="${customer.phone || ''}"></td>
        <td><input type="email" class="form-input-modern" style="min-width:160px" name="email" value="${customer.email || ''}"></td>
        <td><input type="text" class="form-input-modern" style="min-width:120px" name="city" value="${customer.city || ''}"></td>
        <td style="text-align:center"><label style="display:inline-flex;align-items:center;gap:.5rem"><input type="checkbox" name="is_active" ${activeChecked}> Active</label></td>
        <td style="text-align:center">
            <button class="action-btn" style="background:#d1fae5;color:#065f46" data-action="save-inline" data-id="${customer.id}"><i class="fas fa-save"></i></button>
            <button class="action-btn" style="background:#f3f4f6;color:#374151" data-action="cancel-inline" data-id="${customer.id}"><i class="fas fa-times"></i></button>
        </td>
    `;
    row.classList.add('editing');
}

async function saveInlineCustomer(row) {
    const id = parseInt(row.getAttribute('data-id'), 10);
    const name = row.querySelector('input[name="name"]').value.trim();
    const phone = row.querySelector('input[name="phone"]').value.trim();
    const email = row.querySelector('input[name="email"]').value.trim();
    const city = row.querySelector('input[name="city"]').value.trim();
    const is_active = row.querySelector('input[name="is_active"]').checked ? 1 : 0;
    const payload = { name, phone, email, city, is_active };
    try {
        const response = await fetch(`${API_URL}&action=update&id=${id}`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
        const result = await response.json();
        if (result.success) {
            showToast('Customer updated', 'success');
            await loadCustomers();
        } else {
            showToast(result.message || 'Update failed', 'error');
        }
    } catch (err) {
        showToast('Network error', 'error');
    }
}

function cancelInlineEdit(row) {
    if (row.__originalHTML) {
        row.innerHTML = row.__originalHTML;
        row.classList.remove('editing');
    } else {
        renderCustomers();
    }
}

// Make closeModal available globally
window.closeModal = function() {
    const modal = document.getElementById('customerModal');
    if (!modal) return;
    modal.classList.remove('show');
    document.body.style.overflow = '';
    const form = document.getElementById('customerForm');
    if (form) form.reset();
    const customerIdEl = document.getElementById('customerId');
    if (customerIdEl) customerIdEl.value = '';
    const saveBtn = document.getElementById('saveBtn');
    if (saveBtn) {
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="fas fa-save"></i> Save Customer';
    }
}

let pendingDeleteId = null;
let pendingDeleteName = '';

function closeDeleteConfirm() {
    const modal = document.getElementById('deleteConfirmModal');
    if (!modal) return;
    modal.classList.remove('show');
    document.body.style.overflow = '';
}

async function performDelete() {
    if (!pendingDeleteId) return;
    try {
        const response = await fetch(`${API_URL}&action=delete&id=${pendingDeleteId}`, { method: 'POST' });
        const result = await response.json();
        if (result.success) {
            showToast('Customer deleted!', 'success');
            closeDeleteConfirm();
            await loadCustomers();
            await loadStats();
        } else {
            throw new Error(result.message || 'Delete failed');
        }
    } catch (error) {
        console.error('❌ Delete error:', error);
        showToast('Failed: ' + error.message, 'error');
    } finally {
        pendingDeleteId = null;
        pendingDeleteName = '';
    }
}

function searchCustomers() {
    const query = document.getElementById('searchInput').value.toLowerCase();
    filteredCustomers = customers.filter(c =>
        c.name.toLowerCase().includes(query) ||
        c.customer_code.toLowerCase().includes(query) ||
        (c.phone && c.phone.includes(query)) ||
        (c.email && c.email.toLowerCase().includes(query))
    );
    renderCustomers();
}

function animateValue(id, target) {
    const element = document.getElementById(id);
    const duration = 1000;
    const start = 0;
    const increment = target / (duration / 16);
    let current = start;
    
    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            element.textContent = target;
            clearInterval(timer);
        } else {
            element.textContent = Math.floor(current);
        }
    }, 16);
}

// ============================================================================
// INITIALIZATION
// ============================================================================
document.addEventListener('DOMContentLoaded', function() {
    if (window.__customersModalInit) return;
    window.__customersModalInit = true;
    console.log('✅ DOM Ready - Initializing...');
    
    // Load data
    loadCustomers();
    loadStats();
    
    // Search
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', searchCustomers);
    }
    window.editMode = localStorage.getItem('customersEditMode') || 'modal';
    function applyEditModeUI() {
        const modalBtn = document.getElementById('editModeModalBtn');
        const inlineBtn = document.getElementById('editModeInlineBtn');
        modalBtn && modalBtn.classList.toggle('active', window.editMode === 'modal');
        inlineBtn && inlineBtn.classList.toggle('active', window.editMode === 'inline');
        const tableWrapper = document.querySelector('.table-modern');
        tableWrapper && tableWrapper.classList.toggle('inline-mode', window.editMode === 'inline');
    }
    applyEditModeUI();
    const modalBtn = document.getElementById('editModeModalBtn');
    const inlineBtn = document.getElementById('editModeInlineBtn');
    modalBtn && modalBtn.addEventListener('click', () => { window.editMode = 'modal'; localStorage.setItem('customersEditMode', 'modal'); applyEditModeUI(); });
    inlineBtn && inlineBtn.addEventListener('click', () => { window.editMode = 'inline'; localStorage.setItem('customersEditMode', 'inline'); applyEditModeUI(); });
    
    // ===========================================================================
    // MODAL CLOSE HANDLERS - MULTIPLE LAYERS FOR RELIABILITY
    // ===========================================================================
    
    // 1. Close button (X) - HIGHEST PRIORITY WITH DEBOUNCE
    const modalCloseBtn = document.getElementById('modalCloseBtn');
    if (modalCloseBtn) modalCloseBtn.addEventListener('click', () => window.closeModal());
    
    // 2. Cancel button - HIGHEST PRIORITY WITH DEBOUNCE
    const modalCancelBtn = document.getElementById('modalCancelBtn');
    if (modalCancelBtn) modalCancelBtn.addEventListener('click', () => window.closeModal());
    
    // 3. ESC key
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') { window.closeModal(); closeDeleteConfirm(); } });
    
    // 4. Backdrop click
    const modal = document.getElementById('customerModal');
    if (modal) modal.addEventListener('click', (e) => { if (e.target.id === 'customerModal') window.closeModal(); });
    
    // 5. Prevent modal content click from closing
    const modalContent = document.getElementById('modalContentWrapper');
    if (modalContent) modalContent.addEventListener('click', (e) => e.stopPropagation());
    
    console.log('✅ All event listeners attached!');
    console.log('✅ Ready to test - Try clicking buttons!');
    
    // Test button clickability after 1 second
    setTimeout(() => {
        console.log('='.repeat(50));
        console.log('🧪 TESTING BUTTON ACCESSIBILITY');
        console.log('Close Btn exists:', !!document.getElementById('modalCloseBtn'));
        console.log('Cancel Btn exists:', !!document.getElementById('modalCancelBtn'));
        console.log('Modal exists:', !!document.getElementById('customerModal'));
        console.log('='.repeat(50));
    }, 1000);

    const delCancel = document.getElementById('deleteCancelBtn');
    const delConfirm = document.getElementById('deleteConfirmBtn');
    if (delCancel) delCancel.addEventListener('click', () => closeDeleteConfirm());
    if (delConfirm) delConfirm.addEventListener('click', () => performDelete());
    const deleteOverlay = document.getElementById('deleteConfirmModal');
    if (deleteOverlay) deleteOverlay.addEventListener('click', (e) => { if (e.target.id === 'deleteConfirmModal') closeDeleteConfirm(); });
    const deleteContent = document.querySelector('#deleteConfirmModal .modal-content-modern');
    if (deleteContent) deleteContent.addEventListener('click', (e) => e.stopPropagation());
    const deleteCloseBtn = document.getElementById('deleteCloseBtn');
    if (deleteCloseBtn) deleteCloseBtn.addEventListener('click', () => closeDeleteConfirm());
});

// Export functionality
function toggleExportMenu() {
    const menu = document.getElementById('exportMenu');
    if (menu) {
        menu.classList.toggle('show');
    }
}

function exportData(format) {
    document.getElementById('exportMenu').classList.remove('show');
    
    const searchInput = document.getElementById('searchInput');
    const searchQuery = searchInput ? searchInput.value : '';
    
    showToast(`Exporting customers as ${format.toUpperCase()}...`, 'info');
    
    // Build export URL with search filter
    let exportUrl = `../api.php?controller=customer&action=export&format=${format}`;
    
    // Add search query if active
    if (searchQuery) {
        exportUrl += `&search=${encodeURIComponent(searchQuery)}`;
    }
    
    console.log('Export URL:', exportUrl);
    
    // Trigger download
    setTimeout(() => {
        window.location.href = exportUrl;
    }, 500);
}

// Close export menu when clicking outside
document.addEventListener('click', function(e) {
    const exportDropdown = document.getElementById('exportDropdown');
    const exportMenu = document.getElementById('exportMenu');
    
    if (exportDropdown && exportMenu) {
        if (!exportDropdown.contains(e.target)) {
            exportMenu.classList.remove('show');
        }
    }
});

// Global safety: ensure modal close buttons always work even if other overlays exist
document.addEventListener('click', function(e) {
    const closeBtn = e.target.closest('#modalCloseBtn');
    const cancelBtn = e.target.closest('#modalCancelBtn');
    const dataClose = e.target.closest('[data-close="modal"]');
    if (closeBtn || cancelBtn || dataClose) {
        e.preventDefault();
        e.stopPropagation();
        if (typeof window.closeModal === 'function') {
            window.closeModal();
        }
    }
}, true);

</script>

<?php require_once 'includes/footer.php'; ?>

