<?php
/**
 * Notifications Page
 * Displays all system notifications
 */

require_once 'includes/header.php';

// Page configuration
$page_title = 'Notifications';
$additional_css = ['notifications.css'];
?>

<!-- Main Content -->
<main class="main-content">
    <!-- Header -->
    <header class="main-header">
        <div class="header-left">
            <button class="btn btn-icon" id="menuToggle" title="Toggle Menu" aria-label="Toggle menu">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="header-title">Notifications</h1>
        </div>
        
        <div class="header-right">
            <button class="btn btn-sm btn-secondary" id="markAllRead">
                <i class="fas fa-check-double"></i> Mark all read
            </button>
        </div>
    </header>

    <!-- Content -->
    <div class="content">
        <div class="notifications-container">
            <div class="notifications-header">
                <h2>All Notifications</h2>
                <button class="btn btn-sm btn-outline" id="refreshNotifications">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
            
            <div class="notifications-list" id="notificationsList">
                <div class="notification-loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading notifications...</p>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
/**
 * Notifications Page JavaScript
 */
class NotificationsPage {
    constructor() {
        this.notifications = [];
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadNotifications();
    }

    bindEvents() {
        document.getElementById('markAllRead').addEventListener('click', () => {
            this.markAllAsRead();
        });

        document.getElementById('refreshNotifications').addEventListener('click', () => {
            this.refresh();
        });
    }

    async loadNotifications() {
        try {
            const response = await fetch('../api.php?controller=notification&action=recent&limit=50');
            const data = await response.json();
            
            if (data.success) {
                this.notifications = data.data;
                this.renderNotifications();
            } else {
                this.showError('Failed to load notifications');
            }
        } catch (error) {
            console.error('Error loading notifications:', error);
            this.showError('Error loading notifications');
        }
    }

    renderNotifications() {
        const container = document.getElementById('notificationsList');
        
        if (this.notifications.length === 0) {
            container.innerHTML = `
                <div class="notification-empty">
                    <i class="fas fa-bell-slash"></i>
                    <p>No notifications found</p>
                </div>
            `;
            return;
        }

        const html = this.notifications.map(notification => {
            const iconClass = this.getNotificationIcon(notification.type);
            const timeAgo = this.formatTimeAgo(notification.created_at);
            
            return `
                <div class="notification-item ${!notification.is_read ? 'unread' : ''}" 
                     data-notification-id="${notification.id}">
                    <div class="notification-icon ${notification.type}">
                        <i class="${iconClass}"></i>
                    </div>
                    <div class="notification-content">
                        <div class="notification-header-item">
                            <h4>${this.escapeHtml(notification.title)}</h4>
                            <span class="notification-time">${timeAgo}</span>
                        </div>
                        <p class="notification-message">${this.escapeHtml(notification.message)}</p>
                    </div>
                </div>
            `;
        }).join('');

        container.innerHTML = html;
    }

    getNotificationIcon(type) {
        const icons = {
            'order': 'fas fa-shopping-cart',
            'payment': 'fas fa-credit-card',
            'status': 'fas fa-info-circle',
            'system': 'fas fa-cog'
        };
        return icons[type] || 'fas fa-bell';
    }

    formatTimeAgo(timestamp) {
        const now = new Date();
        const notificationTime = new Date(timestamp);
        const diffMs = now - notificationTime;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);

        if (diffMins < 1) return 'Just now';
        if (diffMins < 60) return `${diffMins}m ago`;
        if (diffHours < 24) return `${diffHours}h ago`;
        if (diffDays < 7) return `${diffDays}d ago`;
        
        return notificationTime.toLocaleDateString();
    }

    async markAllAsRead() {
        if (!confirm('Mark all notifications as read?')) return;
        
        try {
            const response = await fetch('../api.php?controller=notification&action=markAllAsRead', {
                method: 'POST',
                headers: {
                    'X-CSRF-Token': this.getCsrfToken()
                }
            });

            const data = await response.json();
            
            if (data.success) {
                // Update local data
                this.notifications.forEach(n => n.is_read = true);
                this.renderNotifications();
                
                // Update header notification count
                if (window.notificationSystem) {
                    window.notificationSystem.refresh();
                }
            }
        } catch (error) {
            console.error('Error marking all notifications as read:', error);
        }
    }

    refresh() {
        this.loadNotifications();
    }

    getCsrfToken() {
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        return csrfToken ? csrfToken.getAttribute('content') : '';
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize notifications page
let notificationsPage;

document.addEventListener('DOMContentLoaded', function() {
    notificationsPage = new NotificationsPage();
});
</script>

<?php require_once 'includes/footer.php'; ?>