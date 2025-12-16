<?php
/**
 * Dashboard Header Component
 * Role-based header with user menu
 */

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

require_once __DIR__ . '/../../../app/helpers/functions.php';
// Ensure CSRF token is available in session for AJAX requests
// SecurityMiddleware provides token generation; include if not already loaded
if (empty($_SESSION['csrf_token'])) {
    require_once __DIR__ . '/../../../app/helpers/SecurityMiddleware.php';
    $_SESSION['csrf_token'] = SecurityMiddleware::generateCsrfToken();
}

$current_user = getCurrentUser();
$user_initials = getAvatarInitials($current_user['full_name']);
$welcome_message = getWelcomeMessage();
$dashboard_title = getDashboardTitle();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $dashboard_title; ?> - Bytebalok</title>
    <!-- CSRF token for AJAX requests -->
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
    <!-- Brand theme color for mobile UI -->
    <meta name="theme-color" content="#16a34a">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- Core Styles -->
    <link rel="stylesheet" href="../assets/css/design-system.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <link rel="stylesheet" href="../assets/css/components-table.css">
    <link rel="stylesheet" href="../assets/css/components-loading.css">
    <link rel="stylesheet" href="../assets/css/components-empty-error.css">
    <link rel="stylesheet" href="../assets/css/mobile-responsive.css">
    <link rel="stylesheet" href="../assets/css/design-system.css">
    <link rel="stylesheet" href="../assets/css/accessibility.css">
    <link rel="stylesheet" href="../assets/css/products-mobile.css">
    <link rel="stylesheet" href="../assets/css/modal-optimized.css">
    <link rel="stylesheet" href="../assets/css/header-enhanced.css">

    <!-- Additional CSS if specified -->
    <?php if (!empty($additional_css)):
        foreach ($additional_css as $css): ?>
            <link rel="stylesheet" href="../assets/css/<?php echo $css; ?>">
        <?php endforeach; endif; ?>

    <!-- Chart.js Library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>



    <style>
        :root {
            --role-color:
            <?php
            echo match ($current_user['role']) {
                'admin' => '#dc3545',
                'manager' => '#0d6efd',
                'staff' => '#17a2b8',
                'cashier' => '#28a745',
                default => '#6c757d'
            };
            ?>
            ;
            --role-color-dark:
            <?php
            echo match ($current_user['role']) {
                'admin' => '#c82333',
                'manager' => '#0b5ed7',
                'staff' => '#138496',
                'cashier' => '#1e7e34',
                default => '#5a6268'
            };
            ?>
            ;
        }

        /* Logo sizing fix */
        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-logo .logo-img {
            width: 40px;
            height: 40px;
            object-fit: contain;
            display: inline-block;
        }

        @media (max-width: 768px) {
            .sidebar-logo .logo-img {
                width: 32px;
                height: 32px;
            }
        }

        .role-indicator {
            background: var(--role-color);
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .sidebar-role-banner {
            background: linear-gradient(135deg, var(--role-color) 0%, var(--role-color-dark) 100%);
            color: white;
            padding: 14px 18px;
            margin: 12px 16px 20px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 700;
            text-align: center;
            letter-spacing: 1px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border: 2px solid rgba(255, 255, 255, 0.2);
            text-transform: uppercase;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .sidebar-role-banner i {
            font-size: 16px;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
        }

        .welcome-banner {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-lg);
        }

        .welcome-banner h2 {
            font-size: 24px;
            font-weight: 700;
            margin: 0 0 8px 0;
        }

        .welcome-banner p {
            margin: 0;
            opacity: 0.9;
            font-size: 14px;
        }

        /* User Menu Styles - Enhanced */
        .user-menu-wrapper {
            position: relative;
        }

        /* User menu now uses standard header-icon-btn class */

        /* Enhanced Notification Button */
        .notification-btn {
            position: relative;
            transition: all 0.2s ease;
        }

        .notification-btn:hover {
            transform: scale(1.05);
        }

        .notification-btn.has-new {
            animation: bellRing 0.5s ease-in-out;
        }

        @keyframes bellRing {

            0%,
            100% {
                transform: rotate(0deg);
            }

            25% {
                transform: rotate(-10deg);
            }

            75% {
                transform: rotate(10deg);
            }
        }

        .notification-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: #ef4444;
            color: white;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 10px;
            min-width: 18px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(239, 68, 68, 0.4);
            animation: badgePulse 2s ease-in-out infinite;
        }

        @keyframes badgePulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.1);
            }
        }

        /* Header Icon Spacing */
        .header-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .header-icon-btn {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.2s;
            color: #6b7280;
        }

        .header-icon-btn:hover {
            background: #f3f4f6;
            color: #374151;
        }

        /* Hide user info for minimal design */
        .user-info-compact {
            display: none !important;
        }

        .user-menu-arrow {
            display: none !important;
        }

        .user-menu-dropdown {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            min-width: 260px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            overflow: hidden;
            border: 1px solid #e5e7eb;
        }

        .user-menu-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
        }

        .user-avatar-large {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
            flex-shrink: 0;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .user-menu-info {
            flex: 1;
            min-width: 0;
        }

        .user-menu-name {
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-menu-email {
            font-size: 12px;
            opacity: 0.9;
            margin-bottom: 6px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-menu-badge {
            display: inline-flex;
        }

        .user-menu-badge .role-indicator {
            padding: 3px 10px;
            font-size: 10px;
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .user-menu-divider {
            height: 1px;
            background: #e5e7eb;
            margin: 6px 0;
        }

        .user-menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 16px;
            color: #374151;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            font-size: 14px;
            font-weight: 500;
        }

        .user-menu-item i {
            width: 18px;
            text-align: center;
            color: #6b7280;
            flex-shrink: 0;
            font-size: 14px;
        }

        .user-menu-item:hover {
            background: #f9fafb;
            color: var(--primary-color);
        }

        .user-menu-item:hover i {
            color: var(--primary-color);
        }

        .user-menu-logout {
            color: #dc2626;
            font-weight: 600;
        }

        .user-menu-logout:hover {
            background: #fef2f2;
            color: #dc2626;
        }

        .user-menu-logout i {
            color: #dc2626;
        }

        /* Notification Dropdown Styles */
        .notification-wrapper {
            position: relative;
        }

        .notification-dropdown {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            width: 380px;
            max-height: 500px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            overflow: hidden;
            border: 1px solid #e5e7eb;
        }

        .notification-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            background: #f8fafc;
            border-bottom: 1px solid #e5e7eb;
        }

        .notification-header h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
        }

        .mark-all-read {
            font-size: 12px;
            padding: 6px 12px;
            background: transparent;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            color: #6b7280;
            cursor: pointer;
            transition: all 0.2s;
        }

        .mark-all-read:hover {
            background: #f3f4f6;
            color: #374151;
            border-color: #9ca3af;
        }

        .notification-list {
            max-height: 350px;
            overflow-y: auto;
        }

        .notification-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 16px 20px;
            border-bottom: 1px solid #f3f4f6;
            cursor: pointer;
            transition: background-color 0.2s;
            text-decoration: none;
            color: inherit;
        }

        .notification-item:hover {
            background: #f8fafc;
        }

        .notification-item.unread {
            background: #eff6ff;
            border-left: 3px solid var(--primary-color);
        }

        .notification-item.unread:hover {
            background: #dbeafe;
        }

        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 16px;
        }

        .notification-icon.order {
            background: #fef3c7;
            color: #d97706;
        }

        .notification-icon.payment {
            background: #d1fae5;
            color: #059669;
        }

        .notification-icon.status {
            background: #e0e7ff;
            color: #6366f1;
        }

        .notification-content {
            flex: 1;
            min-width: 0;
        }

        .notification-title {
            font-size: 14px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 4px;
            line-height: 1.3;
        }

        .notification-message {
            font-size: 13px;
            color: #6b7280;
            line-height: 1.4;
            margin-bottom: 4px;
        }

        .notification-time {
            font-size: 11px;
            color: #9ca3af;
        }

        .notification-empty {
            text-align: center;
            padding: 40px 20px;
            color: #9ca3af;
        }

        .notification-empty i {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .notification-empty p {
            margin: 0;
            font-size: 14px;
        }

        .notification-footer {
            padding: 12px 20px;
            background: #f8fafc;
            border-top: 1px solid #e5e7eb;
            text-align: center;
        }

        .view-all-notifications {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .view-all-notifications:hover {
            color: var(--primary-dark);
        }

        .notification-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background: #ef4444;
            color: white;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 10px;
            min-width: 18px;
            text-align: center;
            line-height: 1.2;
        }

        @media (max-width: 768px) {
            .user-info-compact {
                display: none;
            }

            .user-menu-dropdown {
                right: -12px;
                min-width: 260px;
            }

            .notification-dropdown {
                right: -12px;
                width: calc(100vw - 24px);
                max-width: 360px;
            }

            .notification-list {
                max-height: 300px;
            }
        }
    </style>
    <script>
        // Apply branding from settings on load
        (function () {
            window.addEventListener('DOMContentLoaded', function () {
                fetch('../api.php?controller=settings&action=get')
                    .then(r => r.json())
                    .then(res => {
                        if (!res || !res.success || !res.data) return;
                        const s = res.data;
                        if (s.brand_primary_color) {
                            document.documentElement.style.setProperty('--primary-color', s.brand_primary_color);
                            const meta = document.querySelector('meta[name="theme-color"]');
                            if (meta) meta.setAttribute('content', s.brand_primary_color);
                        }
                        if (s.brand_logo) {
                            const img = document.querySelector('.sidebar-logo .logo-img');
                            if (img) img.src = '../' + s.brand_logo;
                        }
                    })
                    .catch(() => { });
            });
        })();
    </script>
</head>

<body>
    <a href="#main" class="skip-link">Skip to content</a>
    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="index.php" class="sidebar-logo">
                    <img src="../assets/img/logo.svg" alt="Bytebalok" class="logo-img">
                    <h1>Bytebalok</h1>
                </a>
                <!-- Mobile Close Button -->
                <button class="sidebar-close" id="sidebarClose" aria-label="Close Sidebar">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Role Banner -->
            <div class="sidebar-role-banner">
                <i class="fas fa-<?php
                echo match ($current_user['role']) {
                    'admin' => 'user-shield',
                    'manager' => 'user-tie',
                    'staff' => 'user',
                    'cashier' => 'cash-register',
                    default => 'user'
                };
                ?>"></i>
                <?php echo strtoupper($current_user['role']); ?> MODE
            </div>

            <!-- Navigation Menu -->
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

                <!-- Separator -->
                <div class="nav-separator"></div>

                <!-- Help & Documentation -->
                <div class="nav-item">
                    <a href="help.php" class="nav-link">
                        <i class="fas fa-question-circle"></i>
                        <span>Help</span>
                    </a>
                </div>
            </nav>

        </aside>

        <!-- Global Sidebar Overlay for mobile/tablet -->
        <div id="sidebarOverlay" class="sidebar-overlay"></div>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header
                class="main-header <?php echo (isset($header_compact) && $header_compact) ? 'compact-header' : ''; ?>">
                <div class="header-left">
                    <button class="btn btn-icon" id="menuToggle" title="Toggle Menu" aria-label="Toggle menu">
                        <i class="fas fa-bars"></i>
                    </button>

                    <!-- Breadcrumb Navigation -->
                    <div class="breadcrumb-wrapper">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a href="index.php">
                                        <i class="fas fa-home"></i> Dashboard
                                    </a>
                                </li>
                                <?php if (isset($breadcrumb_items) && is_array($breadcrumb_items)): ?>
                                    <?php foreach ($breadcrumb_items as $index => $item): ?>
                                        <li
                                            class="breadcrumb-item <?php echo ($index === count($breadcrumb_items) - 1) ? 'active' : ''; ?>">
                                            <?php if (isset($item['url']) && $index !== count($breadcrumb_items) - 1): ?>
                                                <a href="<?php echo htmlspecialchars($item['url']); ?>">
                                                    <?php echo htmlspecialchars($item['label']); ?>
                                                </a>
                                            <?php else: ?>
                                                <?php echo htmlspecialchars($item['label']); ?>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li class="breadcrumb-item active">
                                        <?php echo $page_title ?? $dashboard_title; ?>
                                    </li>
                                <?php endif; ?>
                            </ol>
                        </nav>
                    </div>
                </div>

                <!-- Global Search Bar -->
                <div class="header-center">
                    <div class="global-search-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" class="global-search-input" id="globalSearch"
                            placeholder="Search products, customers, orders..." autocomplete="off">
                        <div class="search-results-dropdown" id="searchResults" style="display: none;">
                            <div class="search-results-loading">
                                <i class="fas fa-spinner fa-spin"></i> Searching...
                            </div>
                        </div>
                    </div>
                </div>

                <div class="header-right">
                    <!-- Notifications with Badge -->
                    <div class="notification-wrapper">
                        <button class="btn btn-icon header-icon-btn notification-btn" id="notificationsBtn"
                            title="Notifications" aria-label="Open notifications">
                            <i class="fas fa-bell"></i>
                            <span class="notification-badge" id="notificationCount" style="display: none;">0</span>
                        </button>
                    </div>

                    <!-- User Menu -->
                    <div class="user-menu-wrapper">
                        <button class="btn btn-icon header-icon-btn user-menu-button" id="userButton" type="button"
                            aria-label="Open user menu" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-user-circle"></i>
                        </button>

                        <div class="user-menu-dropdown" id="userMenu" style="display: none;">
                            <div class="user-menu-header">
                                <div class="user-avatar-large"><?php echo $user_initials; ?></div>
                                <div class="user-menu-info">
                                    <div class="user-menu-name">
                                        <?php echo htmlspecialchars($current_user['full_name']); ?>
                                    </div>
                                    <div class="user-menu-email">
                                        <?php echo htmlspecialchars($current_user['email'] ?? $current_user['username']); ?>
                                    </div>
                                    <div class="user-menu-badge">
                                        <?php echo getRoleBadge($current_user['role']); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="user-menu-divider"></div>
                            <a href="profile.php" class="user-menu-item">
                                <i class="fas fa-user-circle"></i>
                                <span>My Profile</span>
                            </a>
                            <?php if ($current_user['role'] === 'admin'): ?>
                                <a href="settings.php" class="user-menu-item">
                                    <i class="fas fa-cog"></i>
                                    <span>Settings</span>
                                </a>
                            <?php endif; ?>
                            <a href="help.php" class="user-menu-item">
                                <i class="fas fa-life-ring"></i>
                                <span>Help & Support</span>
                            </a>
                            <div class="user-menu-divider"></div>
                            <button class="user-menu-item user-menu-logout" id="logoutBtn" type="button">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Logout</span>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="content">
                <!-- Welcome Banner -->
                <?php if (!isset($hide_welcome_banner)): ?>
                    <div class="welcome-banner">
                        <h2><?php echo $welcome_message; ?></h2>
                        <p><?php echo $dashboard_title; ?> • <?php echo date('l, d F Y'); ?></p>
                    </div>
                <?php endif; ?>

                <!-- Flash Messages -->
                <?php
                $flash = getFlashMessage();
                if ($flash):
                    ?>
                    <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible">
                        <button type="button" class="alert-close" onclick="this.parentElement.remove()">
                            <i class="fas fa-times"></i>
                        </button>
                        <?php echo $flash['message']; ?>
                    </div>
                <?php endif; ?>

                <!-- Page Content Starts Here -->