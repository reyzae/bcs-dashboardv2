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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <link rel="stylesheet" href="../assets/css/accessibility.css">
    <?php if (isset($additional_css)): foreach ($additional_css as $css): ?>
    <link rel="stylesheet" href="../assets/css/<?php echo $css; ?>">
    <?php endforeach; endif; ?>
    
    <!-- Chart.js Library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    
    
    <style>
        :root {
            --role-color: <?php 
                echo match($current_user['role']) {
                    'admin' => '#dc3545',
                    'manager' => '#0d6efd',
                    'staff' => '#17a2b8',
                    'cashier' => '#28a745',
                    default => '#6c757d'
                };
            ?>;
            --role-color-dark: <?php 
                echo match($current_user['role']) {
                    'admin' => '#c82333',
                    'manager' => '#0b5ed7',
                    'staff' => '#138496',
                    'cashier' => '#1e7e34',
                    default => '#5a6268'
                };
            ?>;
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
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border: 2px solid rgba(255,255,255,0.2);
            text-transform: uppercase;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .sidebar-role-banner i {
            font-size: 16px;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
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

        .user-menu-button {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 12px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .user-menu-button:hover {
            background: #f9fafb;
            border-color: var(--primary-color);
            box-shadow: 0 2px 8px rgba(22, 163, 74, 0.25);
        }

        .user-menu-button .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 16px;
            flex-shrink: 0;
        }

        .user-info-compact {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 2px;
        }

        .user-name-text {
            font-size: 14px;
            font-weight: 600;
            color: #1f2937;
            line-height: 1.2;
        }

        .user-role-text {
            font-size: 11px;
            font-weight: 500;
            color: #6b7280;
            text-transform: capitalize;
        }

        .user-menu-arrow {
            font-size: 12px;
            color: #9ca3af;
            transition: transform 0.2s;
            margin-left: 4px;
        }

        .user-menu-button[aria-expanded="true"] .user-menu-arrow {
            transform: rotate(180deg);
        }

        .user-menu-dropdown {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            min-width: 280px;
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
            padding: 20px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
        }

        .user-avatar-large {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 20px;
            flex-shrink: 0;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .user-menu-info {
            flex: 1;
            min-width: 0;
        }

        .user-menu-name {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-menu-email {
            font-size: 13px;
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
            background: rgba(255, 255, 255, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .user-menu-divider {
            height: 1px;
            background: #e5e7eb;
            margin: 8px 0;
        }

        .user-menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: #374151;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            font-size: 14px;
        }

        .user-menu-item i {
            width: 18px;
            text-align: center;
            color: #6b7280;
            flex-shrink: 0;
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
                    echo match($current_user['role']) {
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
            <header class="main-header <?php echo (isset($header_compact) && $header_compact) ? 'compact-header' : ''; ?>">
                <div class="header-left">
                    <button class="btn btn-icon" id="menuToggle" title="Toggle Menu" aria-label="Toggle menu">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="header-title"><?php echo $page_title ?? $dashboard_title; ?></h1>
                </div>
                
                <div class="header-right">
                    <button class="btn btn-icon header-icon-btn" id="densityToggle" title="Toggle density" aria-label="Toggle density">
                        <i class="fas fa-compress"></i>
                    </button>
                    <!-- Notifications -->
                    <div class="notification-wrapper">
                        <button class="btn btn-icon header-icon-btn" id="notificationsBtn" title="Notifications" aria-label="Open notifications">
                            <i class="fas fa-bell"></i>
                            <span class="notification-badge" id="notificationCount" style="display: none;">0</span>
                        </button>
                    </div>
                    
                    <!-- Quick Actions Dropdown (Role-based) -->
                    <?php if ($current_user['role'] === 'admin' || $current_user['role'] === 'manager'): ?>
                    <div class="dropdown">
                        <button class="btn btn-icon header-icon-btn" id="quickActionsBtn" title="Quick Actions" aria-label="Open quick actions" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-bolt"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right" id="quickActionsMenu">
                            <?php if ($current_user['role'] === 'admin'): ?>
                            <a href="users.php?action=add" class="dropdown-item">
                                <i class="fas fa-user-plus"></i> Add User
                            </a>
                            <?php endif; ?>
                            <a href="products.php?action=add" class="dropdown-item">
                                <i class="fas fa-plus"></i> Add Product
                            </a>
                            <a href="customers.php?action=add" class="dropdown-item">
                                <i class="fas fa-user-plus"></i> Add Customer
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="reports.php" class="dropdown-item">
                                <i class="fas fa-chart-bar"></i> View Reports
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- User Menu -->
                    <div class="user-menu-wrapper">
                        <button class="user-menu-button" id="userButton" type="button" aria-label="Open user menu" aria-haspopup="true" aria-expanded="false">
                            <div class="user-avatar"><?php echo $user_initials; ?></div>
                            <div class="user-info-compact">
                                <span class="user-name-text"><?php echo $current_user['full_name']; ?></span>
                                <span class="user-role-text"><?php echo ucfirst($current_user['role']); ?></span>
                            </div>
                            <i class="fas fa-chevron-down user-menu-arrow"></i>
                        </button>
                        
                        <div class="user-menu-dropdown" id="userMenu" style="display: none;">
                            <div class="user-menu-header">
                                <div class="user-avatar-large"><?php echo $user_initials; ?></div>
                                <div class="user-menu-info">
                                    <div class="user-menu-name"><?php echo htmlspecialchars($current_user['full_name']); ?></div>
                                    <div class="user-menu-email"><?php echo htmlspecialchars($current_user['email'] ?? $current_user['username']); ?></div>
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

