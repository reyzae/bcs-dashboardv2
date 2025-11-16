<?php
/**
 * Orders Management Page
 * Role-based access: Admin, Manager
 */

// Load bootstrap and helpers
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../app/helpers/functions.php';

// Require authentication
requireAuth();

// Only Admin/Manager can manage orders
requireRole(['admin', 'manager']);

// Page configuration
$page_title = 'Incoming Orders';
$hide_welcome_banner = true;
$additional_css = [];
$additional_js = ['orders.js'];

// Current user
$current_user = getCurrentUser();

// Aktifkan compact header untuk tampilan header yang rapi
$header_compact = true;

// Include header
require_once __DIR__ . '/includes/header.php';
?>

<!-- Orders Management Content -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header" style="display: flex; align-items: center; justify-content: space-between;">
        <h3 class="card-title"><i class="fas fa-receipt" style="color: var(--primary-color);"></i> Incoming Orders</h3>
        <div style="display: flex; gap: 0.5rem;">
            <button class="btn btn-secondary btn-sm" id="refreshOrdersBtn"><i class="fas fa-sync"></i> Refresh</button>
        </div>
    </div>
    <div class="card-body">
        <p class="text-muted" style="margin:0;">Pantau pesanan masuk dan ubah status dengan cepat</p>
    </div>
</div>

<!-- Status Tabs -->
<div class="card" style="margin-bottom: 1rem;">
    <div class="card-body" style="padding: 0.75rem 1rem;">
        <div class="status-tabs">
            <button class="status-tab-btn active" data-status="pending" id="tabPending">
                <i class="fas fa-clock"></i>
                <span>Waiting</span>
                <span class="badge" id="countPending" style="margin-left: 6px;">0</span>
            </button>
            <button class="status-tab-btn" data-status="processing" id="tabProcessing">
                <i class="fas fa-cog"></i>
                <span>In Progress</span>
                <span class="badge" id="countProcessing" style="margin-left: 6px;">0</span>
            </button>
            <button class="status-tab-btn" data-status="completed" id="tabCompleted">
                <i class="fas fa-check-circle"></i>
                <span>Completed</span>
                <span class="badge" id="countCompleted" style="margin-left: 6px;">0</span>
            </button>
        </div>
    </div>
</div>

<!-- Orders Table -->
<div class="card">
    <div class="card-header" style="display: flex; align-items: center; justify-content: space-between;">
        <h3 class="card-title">Daftar Pesanan</h3>
        <div style="display: flex; gap: 0.5rem; align-items: center;">
            <span id="activeStatusLabel" class="badge">pending</span>
        </div>
    </div>
    <div class="card-body no-padding">
        <div class="table-responsive">
            <table class="table table--wide" id="ordersTable">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Placed</th>
                        <th class="col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody id="ordersTableBody">
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 1rem; color: #6b7280;">Memuat pesanan...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>