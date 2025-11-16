<?php
/**
 * Dashboard Index - Role-based Main Dashboard
 * Different views for different roles
 */

// Load bootstrap
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../app/helpers/functions.php';

// Require authentication
requireAuth();

// Page configuration
$page_title = getDashboardTitle();
$additional_css = []; // dashboard.css not needed - using style.css
// Konsisten dengan halaman lain: aktifkan dashboard.js dan compact header
$additional_js = ['dashboard.js'];
$header_compact = true;
$hide_welcome_banner = true;

// Get current user
$current_user = getCurrentUser();

// Include header
include __DIR__ . '/includes/header.php';
?>

<!-- Role-based Dashboard Content -->
<?php if ($current_user['role'] === 'admin'): ?>
    <?php include __DIR__ . '/views/dashboard/admin.php'; ?>
<?php elseif ($current_user['role'] === 'manager'): ?>
    <?php include __DIR__ . '/views/dashboard/manager.php'; ?>
<?php elseif ($current_user['role'] === 'staff'): ?>
    <?php include __DIR__ . '/views/dashboard/staff.php'; ?>
<?php elseif ($current_user['role'] === 'cashier'): ?>
    <?php include __DIR__ . '/views/dashboard/cashier.php'; ?>
<?php else: ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        Unknown role. Please contact administrator.
    </div>
<?php endif; ?>

<?php
include __DIR__ . '/includes/footer.php';
?>

