<!-- Page Content Ends Here -->
</div>

<!-- Footer -->
<footer class="main-footer">
    <div class="footer-content">
        <div class="footer-left">
            <div class="footer-brand">
                <img src="../assets/img/logo.svg" alt="Bytebalok" class="logo-img" style="height:20px;">
                <span>© <?php echo date('Y'); ?> <strong>Bytebalok</strong> POS System</span>
            </div>
            <div class="footer-meta">
                <span class="footer-version">v1.0.0</span>
                <span class="footer-separator">•</span>
                <span class="footer-status">
                    <i class="fas fa-circle text-success"></i> System Online
                </span>
            </div>
        </div>
        <div class="footer-right">
            <a href="help.php" class="footer-link" title="Help & Support">
                <i class="fas fa-question-circle"></i> Help
            </a>
            <a href="https://github.com/bytebalok/docs" target="_blank" class="footer-link" title="Documentation">
                <i class="fas fa-book"></i> Docs
            </a>
            <a href="#" class="footer-link" onclick="showAboutModal(); return false;" title="About System">
                <i class="fas fa-info-circle"></i> About
            </a>
            <a id="supportLink" href="#" target="_blank" class="footer-link" title="Contact Support">
                <i class="fas fa-headset"></i> Support
            </a>
        </div>
    </div>
</footer>
</main>
</div>

<!-- Toast Container -->
<div id="toast" class="toast"></div>

<!-- CRITICAL: INLINE Navigation Fix - GUARANTEED TO WORK! -->
<script>
    (function () {
        'use strict';
        console.log('🚀 INLINE NAVIGATION FIX STARTING...');

        function forceNavigation() {
            // Get ALL navigation links
            const links = document.querySelectorAll('.sidebar a, .sidebar .nav-link, a.nav-link');
            console.log(`Found ${links.length} navigation links`);

            if (links.length === 0) {
                console.warn('No links found, retrying in 300ms...');
                setTimeout(forceNavigation, 300);
                return;
            }

            let armed = 0;
            links.forEach(function (link) {
                const href = link.getAttribute('href');
                const text = link.textContent.trim();

                // Skip anchors and javascript links
                if (!href || href === '#' || href.startsWith('javascript:')) {
                    return;
                }

                armed++;
                console.log(`  ${armed}. ${text} -> ${href}`);

                // Remove ALL existing click handlers
                const newLink = link.cloneNode(true);
                link.parentNode.replaceChild(newLink, link);

                // Add NEW click handler with HIGHEST priority
                newLink.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();

                    const target = this.getAttribute('href');
                    console.log(`🔗 NAVIGATING TO: ${target}`);

                    // Close mobile sidebar if open
                    const sidebar = document.getElementById('sidebar');
                    if (sidebar) sidebar.classList.remove('open');

                    // FORCE NAVIGATE
                    window.location.href = target;
                    return false;
                }, true);
            });

            console.log(`✅ ${armed} links armed and ready!`);
            console.log('👉 Navigation should work now - click any menu!');
        }

        // Start immediately
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function () {
                setTimeout(forceNavigation, 100);
            });
        } else {
            setTimeout(forceNavigation, 100);
        }
    })();
</script>

<script>
    // Dynamically set support WhatsApp link from settings
    (function () {
        const el = document.getElementById('supportLink');
        if (!el) return;
        fetch('../api.php?controller=settings&action=get&key=company_phone')
            .then(r => r.json())
            .then(res => {
                const phone = (res && res.success && res.data && res.data.value) ? res.data.value : '+6285121010199';
                const normalized = String(phone).replace(/[^0-9]/g, '');
                el.href = `https://wa.me/${normalized}`;
            })
            .catch(() => {
                el.href = 'https://wa.me/6285121010199';
            });
    })();
</script>

<!-- Keyboard Shortcuts Modal -->
<div class="modal" id="shortcutsModal" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-keyboard"></i> Keyboard Shortcuts
                </h3>
                <button type="button" class="modal-close" onclick="closeModal('shortcutsModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="shortcuts-list">
                    <?php
                    $shortcuts = getKeyboardShortcuts();
                    if (!empty($shortcuts)):
                        foreach ($shortcuts as $key => $action):
                            ?>
                            <div class="shortcut-item">
                                <kbd class="keyboard-key"><?php echo $key; ?></kbd>
                                <span class="shortcut-description"><?php echo $action; ?></span>
                            </div>
                        <?php
                        endforeach;
                    else:
                        ?>
                        <p class="text-muted">No shortcuts available for your role.</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('shortcutsModal')">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Logout Confirmation Modal -->
<div class="modal" id="logoutConfirmModal" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-sign-out-alt"></i> Konfirmasi Logout</h3>
                <button type="button" class="modal-close" onclick="closeModal('logoutConfirmModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin keluar dari akun?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="logoutCancelBtn">Batal</button>
                <button type="button" class="btn btn-primary" id="logoutConfirmBtn"><i class="fas fa-check"></i>
                    Logout</button>
            </div>
        </div>
    </div>
</div>

<!-- Notifications Panel -->
<div class="notifications-panel" id="notificationsPanel" style="display: none;">
    <div class="notifications-header">
        <h3>Notifications</h3>
        <button class="btn btn-sm btn-text" id="markAllReadBtn">
            Mark all as read
        </button>
    </div>
    <div class="notifications-body" id="notificationsList">
        <!-- Notifications will be loaded here -->
        <div class="notifications-empty">
            <i class="fas fa-bell-slash"></i>
            <p>No new notifications</p>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay" style="display: none;">
    <div class="loading-spinner">
        <div class="spinner"></div>
        <p>Loading...</p>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script src="../assets/js/app.js"></script>
<script src="../assets/js/global-search.js"></script>

<?php if (isset($additional_js)):
    foreach ($additional_js as $js): ?>
        <script src="../assets/js/<?php echo $js; ?>"></script>
    <?php endforeach; endif; ?>

<script>
    // Global variables
    const USER_ROLE = '<?php echo $current_user['role']; ?>';
    const USER_ID = <?php echo $current_user['id']; ?>;
    const USER_NAME = '<?php echo $current_user['full_name']; ?>';
    // CSRF token fallback for JS (meta tag also present)
    window.CSRF_TOKEN = '<?php echo isset($_SESSION['csrf_token']) ? addslashes($_SESSION['csrf_token']) : ''; ?>';

    // Initialize on load
    document.addEventListener('DOMContentLoaded', function () {
        // Menu toggle
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');

        if (menuToggle && sidebar) {
            menuToggle.addEventListener('click', function () {
                sidebar.classList.toggle('sidebar-collapsed');
                localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('sidebar-collapsed'));
            });

            // Restore sidebar state
            if (localStorage.getItem('sidebarCollapsed') === 'true') {
                sidebar.classList.add('sidebar-collapsed');
            }
        }

        // User menu toggle
        const userButton = document.getElementById('userButton');
        const userMenu = document.getElementById('userMenu');

        if (userButton && userMenu) {
            userButton.addEventListener('click', function (e) {
                e.stopPropagation();
                userMenu.style.display = userMenu.style.display === 'none' ? 'block' : 'none';
            });

            // Close user menu when clicking outside
            document.addEventListener('click', function (e) {
                if (!userButton.contains(e.target) && !userMenu.contains(e.target)) {
                    userMenu.style.display = 'none';
                }
            });
        }

        // Logout button
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', function (e) {
                e.preventDefault();
                if (confirm('Are you sure you want to logout?')) {
                    window.location.href = 'logout.php';
                }
            });
        }

        // Notifications
        const notificationsBtn = document.getElementById('notificationsBtn');
        const notificationsPanel = document.getElementById('notificationsPanel');

        if (notificationsBtn && notificationsPanel) {
            notificationsBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                notificationsPanel.style.display =
                    notificationsPanel.style.display === 'none' ? 'block' : 'none';

                if (notificationsPanel.style.display === 'block') {
                    loadNotifications();
                }
            });

            // Close notifications panel when clicking outside
            document.addEventListener('click', function (e) {
                if (!notificationsBtn.contains(e.target) && !notificationsPanel.contains(e.target)) {
                    notificationsPanel.style.display = 'none';
                }
            });
        }

        // Quick actions dropdown
        const quickActionsBtn = document.getElementById('quickActionsBtn');
        const quickActionsMenu = document.getElementById('quickActionsMenu');

        if (quickActionsBtn && quickActionsMenu) {
            quickActionsBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                quickActionsMenu.classList.toggle('show');
            });

            document.addEventListener('click', function (e) {
                if (!quickActionsBtn.contains(e.target) && !quickActionsMenu.contains(e.target)) {
                    quickActionsMenu.classList.remove('show');
                }
            });
        }

        // Load notification count
        loadNotificationCount();

        // Refresh notification count every 30 seconds
        setInterval(loadNotificationCount, 30000);
    });

    // Modal functions
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    }

    // ESC key to close modals
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            const modals = document.querySelectorAll('.modal[style*="display: flex"]');
            modals.forEach(modal => {
                modal.style.display = 'none';
            });
            document.body.style.overflow = '';
        }
    });

    // Loading overlay functions
    function showLoading() {
        document.getElementById('loadingOverlay').style.display = 'flex';
    }

    function hideLoading() {
        document.getElementById('loadingOverlay').style.display = 'none';
    }

    // Notification functions
    let lastNotificationCount = 0;
    async function loadNotificationCount() {
        try {
            const response = await fetch('../api_dashboard.php?action=notifications&method=count', {
                credentials: 'include'
            });
            const data = await response.json();

            const countBadge = document.getElementById('notificationCount');
            const count = (data && data.success && data.data && typeof data.data.count === 'number') ? data.data.count : 0;

            // Show badge when there are unread notifications
            if (count > 0) {
                countBadge.textContent = count;
                countBadge.style.display = 'flex';
            } else {
                countBadge.style.display = 'none';
            }

            // Show toast when new notifications arrive (aggregate delta)
            if (count > lastNotificationCount) {
                const delta = count - lastNotificationCount;
                if (window.showToast) {
                    window.showToast(`Ada notifikasi baru (+${delta})`, 'info');
                }
            }
            lastNotificationCount = count;
        } catch (error) {
            console.error('Error loading notification count:', error);
        }
    }

    async function loadNotifications() {
        const notificationsList = document.getElementById('notificationsList');
        notificationsList.innerHTML = '<div class="notifications-loading"><div class="spinner-sm"></div></div>';

        try {
            const response = await fetch('../api_dashboard.php?action=notifications&method=list', {
                credentials: 'include'
            });
            const data = await response.json();

            const notifications = (data && data.success && data.data && Array.isArray(data.data.notifications)) ? data.data.notifications : [];
            if (notifications.length > 0) {
                notificationsList.innerHTML = notifications.map(notif => {
                    // Optional deep-link for order/payment notifications
                    let linkHTML = '';
                    if (notif.type === 'order' && notif.order_id) {
                        linkHTML = `<a href="orders.php?id=${notif.order_id}" class="notification-link">Lihat Order</a>`;
                    } else if (notif.type === 'payment' && notif.order_id) {
                        linkHTML = `<a href="orders.php?id=${notif.order_id}" class="notification-link">Lihat Pembayaran</a>`;
                    }
                    return `
                    <div class="notification-item ${notif.is_read ? '' : 'unread'}" data-id="${notif.id}">
                        <div class="notification-icon notification-${notif.type}">
                            <i class="fas fa-${getNotificationIcon(notif.type)}"></i>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title">${notif.title}</div>
                            <div class="notification-message">${notif.message} ${linkHTML}</div>
                            <div class="notification-time">${notif.time_ago || ''}</div>
                        </div>
                        ${!notif.is_read ? '<div class="notification-dot"></div>' : ''}
                    </div>`;
                }).join('');

                // Add click handlers
                document.querySelectorAll('.notification-item').forEach(item => {
                    item.addEventListener('click', function () {
                        const notifId = this.dataset.id;
                        markNotificationAsRead(notifId);
                        const link = this.querySelector('.notification-link');
                        if (link) {
                            window.location.href = link.getAttribute('href');
                        }
                    });
                });
            } else {
                notificationsList.innerHTML = `
                    <div class="notifications-empty">
                        <i class="fas fa-bell-slash"></i>
                        <p>Tidak ada notifikasi baru</p>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error loading notifications:', error);
            notificationsList.innerHTML = `
                <div class="notifications-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Gagal memuat notifikasi</p>
                </div>
            `;
        }
    }

    function getNotificationIcon(type) {
        const icons = {
            'info': 'info-circle',
            'success': 'check-circle',
            'warning': 'exclamation-triangle',
            'error': 'times-circle',
            'order': 'shopping-cart',
            'payment': 'receipt'
        };
        return icons[type] || 'bell';
    }

    async function markNotificationAsRead(notificationId) {
        try {
            await fetch('../api_dashboard.php?action=notifications&method=markRead', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({ notification_id: notificationId })
            });
            loadNotificationCount();
            loadNotifications();
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }

    // Mark all as read
    document.getElementById('markAllReadBtn')?.addEventListener('click', async function () {
        try {
            await fetch('../api_dashboard.php?action=notifications&method=markAllRead', {
                method: 'POST',
                credentials: 'include'
            });
            loadNotificationCount();
            loadNotifications();
        } catch (error) {
            console.error('Error marking all as read:', error);
        }
    });
</script>

<script>
    // Logout confirmation binding for all roles
    (function () {
        const btn = document.getElementById('logoutBtn');
        const modalId = 'logoutConfirmModal';
        const cancelBtn = document.getElementById('logoutCancelBtn');
        const confirmBtn = document.getElementById('logoutConfirmBtn');
        function openModal(id) { const m = document.getElementById(id); if (!m) return; m.style.display = 'block'; m.classList.add('show'); }
        function closeModalLocal(id) { const m = document.getElementById(id); if (!m) return; m.classList.remove('show'); setTimeout(() => { m.style.display = 'none'; }, 200); }
        if (btn) {
            btn.addEventListener('click', function (e) { e.preventDefault(); openModal(modalId); });
        }
        if (cancelBtn) { cancelBtn.addEventListener('click', function () { closeModalLocal(modalId); }); }
        if (confirmBtn) {
            confirmBtn.addEventListener('click', function () { window.location.href = 'logout.php'; });
        }
        // Esc to close
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModalLocal(modalId); });
    })();
</script>

<!-- About Modal -->
<div class="modal" id="aboutModal" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-cube"></i> About Bytebalok POS
                </h3>
                <button type="button" class="modal-close" onclick="closeModal('aboutModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="about-content">
                    <div class="about-logo">
                        <img src="../assets/img/logo.svg" alt="Bytebalok" style="height:48px;">
                    </div>
                    <h2 style="text-align: center; margin: 16px 0;">Bytebalok POS System</h2>
                    <p style="text-align: center; color: #666; margin-bottom: 24px;">
                        Professional Point of Sale & Inventory Management System
                    </p>

                    <div class="system-info">
                        <div class="info-row">
                            <span class="info-label">Version:</span>
                            <span class="info-value">1.0.0</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Release Date:</span>
                            <span class="info-value">October 2025</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">PHP Version:</span>
                            <span class="info-value"><?php echo PHP_VERSION; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Database:</span>
                            <span class="info-value">MySQL 8.0</span>
                        </div>
                    </div>

                    <div class="about-features">
                        <h4 style="margin-top: 24px; margin-bottom: 12px;">Features:</h4>
                        <ul style="list-style: none; padding: 0;">
                            <li><i class="fas fa-check-circle text-success"></i> Point of Sale (POS)</li>
                            <li><i class="fas fa-check-circle text-success"></i> Inventory Management</li>
                            <li><i class="fas fa-check-circle text-success"></i> Customer Management</li>
                            <li><i class="fas fa-check-circle text-success"></i> Sales Reports & Analytics</li>
                            <li><i class="fas fa-check-circle text-success"></i> Multi-User & Role Management</li>
                            <li><i class="fas fa-check-circle text-success"></i> Export Data (Excel, PDF)</li>
                        </ul>
                    </div>

                    <p style="text-align: center; margin-top: 24px; color: #999; font-size: 13px;">
                        Developed with ❤️ for Kue Balok Business
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="closeModal('aboutModal')">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function showAboutModal() {
        openModal('aboutModal');
    }
</script>

<style>
    /* Footer Styles */
    .main-footer {
        background: white;
        border-top: 1px solid #e9ecef;
        padding: 16px 24px;
        margin-top: auto;
    }

    .footer-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 16px;
    }

    .footer-left {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .footer-brand {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        color: #666;
    }

    .footer-brand i {
        color: var(--primary-color);
        font-size: 16px;
    }

    .footer-brand strong {
        color: #333;
    }

    .footer-meta {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        color: #999;
    }

    .footer-version {
        background: #f0f0f0;
        padding: 2px 8px;
        border-radius: 4px;
        font-family: monospace;
        font-weight: 600;
    }

    .footer-separator {
        color: #ddd;
    }

    .footer-status {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .footer-status i {
        font-size: 8px;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.5;
        }
    }

    .footer-right {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .footer-link {
        display: flex;
        align-items: center;
        gap: 6px;
        color: #666;
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
        transition: color 0.2s;
    }

    .footer-link:hover {
        color: var(--primary-color);
    }

    .footer-link i {
        font-size: 14px;
    }

    /* About Modal Styles */
    .about-content {
        padding: 8px;
    }

    .about-logo {
        text-align: center;
        margin-bottom: 16px;
    }

    .system-info {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 16px;
        margin-top: 16px;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid #e9ecef;
    }

    .info-row:last-child {
        border-bottom: none;
    }

    .info-label {
        font-weight: 600;
        color: #666;
    }

    .info-value {
        color: #333;
        font-family: monospace;
    }

    .about-features ul {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
    }

    .about-features li {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
    }

    .about-features i {
        font-size: 16px;
    }

    @media (max-width: 768px) {
        .footer-content {
            flex-direction: column;
            align-items: flex-start;
        }

        .footer-right {
            width: 100%;
            justify-content: space-between;
        }

        .about-features ul {
            grid-template-columns: 1fr;
        }
    }

    /* Additional Styles */
    .sidebar-footer {
        padding: 16px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        margin-top: auto;
    }

    .sidebar-user-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .user-avatar-small {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        color: white;
    }

    .user-details {
        flex: 1;
        min-width: 0;
    }

    .user-name {
        font-weight: 600;
        color: white;
        font-size: 14px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .user-role-badge {
        font-size: 10px;
        margin-top: 4px;
        display: inline-block;
    }

    .nav-separator {
        height: 1px;
        background: rgba(255, 255, 255, 0.1);
        margin: 16px;
    }

    .shortcuts-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .shortcut-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px;
        background: #f8f9fa;
        border-radius: 8px;
    }

    .keyboard-key {
        background: linear-gradient(to bottom, #ffffff, #f0f0f0);
        border: 1px solid #ccc;
        border-radius: 4px;
        padding: 6px 12px;
        font-family: monospace;
        font-weight: 600;
        box-shadow: 0 2px 0 rgba(0, 0, 0, 0.1);
    }

    .shortcut-description {
        flex: 1;
        margin-left: 16px;
        color: #666;
    }

    .notifications-panel {
        position: fixed;
        top: 60px;
        right: 20px;
        width: 380px;
        max-height: 500px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        z-index: 1000;
        overflow: hidden;
    }

    .notifications-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px;
        border-bottom: 1px solid #e9ecef;
    }

    .notifications-header h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
    }

    .notifications-body {
        max-height: 420px;
        overflow-y: auto;
    }

    .notification-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 16px 20px;
        border-bottom: 1px solid #f0f0f0;
        cursor: pointer;
        transition: background 0.2s;
        position: relative;
    }

    .notification-item:hover {
        background: #f8f9fa;
    }

    .notification-item.unread {
        background: #f0f7ff;
    }

    .notification-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .notification-info {
        background: #cfe2ff;
        color: #084298;
    }

    .notification-success {
        background: #d1e7dd;
        color: #0f5132;
    }

    .notification-warning {
        background: #fff3cd;
        color: #664d03;
    }

    .notification-error {
        background: #f8d7da;
        color: #842029;
    }

    .notification-content {
        flex: 1;
        min-width: 0;
    }

    .notification-title {
        font-weight: 600;
        font-size: 14px;
        margin-bottom: 4px;
    }

    .notification-message {
        font-size: 13px;
        color: #666;
        margin-bottom: 4px;
    }

    .notification-time {
        font-size: 12px;
        color: #999;
    }

    .notification-dot {
        position: absolute;
        top: 20px;
        right: 20px;
        width: 8px;
        height: 8px;
        background: #0d6efd;
        border-radius: 50%;
    }

    .notifications-empty,
    .notifications-error,
    .notifications-loading {
        padding: 40px 20px;
        text-align: center;
        color: #999;
    }

    .notifications-empty i,
    .notifications-error i {
        font-size: 48px;
        margin-bottom: 12px;
        opacity: 0.5;
    }

    .notification-badge {
        position: absolute;
        top: -4px;
        right: -4px;
        background: #dc3545;
        color: white;
        font-size: 10px;
        font-weight: 600;
        padding: 2px 6px;
        border-radius: 10px;
        min-width: 18px;
        height: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }

    .loading-spinner {
        text-align: center;
        color: white;
    }

    .spinner {
        width: 50px;
        height: 50px;
        border: 4px solid rgba(255, 255, 255, 0.3);
        border-top-color: white;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto 16px;
    }

    .spinner-sm {
        width: 24px;
        height: 24px;
        border: 3px solid rgba(0, 0, 0, 0.1);
        border-top-color: #0d6efd;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 20px auto;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    .dropdown-menu {
        display: none;
        position: absolute;
        top: 100%;
        right: 0;
        min-width: 200px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        margin-top: 8px;
        z-index: 1000;
    }

    .dropdown-menu.show {
        display: block;
    }

    .dropdown-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        color: #333;
        text-decoration: none;
        transition: background 0.2s;
    }

    .dropdown-item:hover {
        background: #f8f9fa;
    }

    .dropdown-divider {
        height: 1px;
        background: #e9ecef;
        margin: 8px 0;
    }
</style>
</body>

</html>