/**
 * Real-time Notification System
 * Handles notification fetching, display, and interaction
 */

class NotificationSystem {
    constructor() {
        this.pollInterval = 5000; // Poll every 5 seconds
        this.lastNotificationId = 0;
        this.unreadCount = 0;
        this.isDropdownOpen = false;
        this.soundEnabled = true;
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.startPolling();
        this.loadNotifications();
        this.createAudioElements();
    }

    bindEvents() {
        // Notification dropdown toggle
        const notificationsBtn = document.getElementById('notificationsBtn');
        const notificationDropdown = document.getElementById('notificationDropdown');
        
        if (notificationsBtn && notificationDropdown) {
            notificationsBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggleDropdown();
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (!notificationDropdown.contains(e.target) && !notificationsBtn.contains(e.target)) {
                    this.closeDropdown();
                }
            });

            // Mark all as read
            const markAllRead = document.getElementById('markAllRead');
            if (markAllRead) {
                markAllRead.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.markAllAsRead();
                });
            }
        }
    }

    createAudioElements() {
        // Create audio element for notification sound
        this.notificationSound = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSuBzvLZiTYIG2m98OScTgwOUarm7blmFgU7k9n1unEiBC13yO/eizEIHWq+8+OWT');
        this.notificationSound.volume = 0.5;
        
        // Create audio element for order sound (different tone)
        this.orderSound = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSuBzvLZiTYIG2m98OScTgwOUarm7blmFgU7k9n1unEiBC13yO/eizEIHWq+8+OWT');
        this.orderSound.volume = 0.7;
    }

    playNotificationSound(type = 'default') {
        if (!this.soundEnabled) return;
        
        try {
            const sound = type === 'order' ? this.orderSound : this.notificationSound;
            sound.currentTime = 0;
            sound.play().catch(e => {
                // Ignore audio play errors (browser autoplay restrictions)
                console.log('Audio play blocked by browser');
            });
        } catch (e) {
            console.log('Audio not supported');
        }
    }

    async loadNotifications() {
        try {
            const response = await fetch('../api.php?controller=notification&action=unread', {
                method: 'GET',
                headers: {
                    'X-CSRF-Token': this.getCsrfToken()
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load notifications');
            }

            const data = await response.json();
            
            if (data.success) {
                this.updateNotificationUI(data.data);
                this.updateUnreadCount(data.data.length);
            }
        } catch (error) {
            console.error('Error loading notifications:', error);
        }
    }

    async loadRecentNotifications() {
        try {
            const response = await fetch('../api.php?controller=notification&action=recent&limit=10', {
                method: 'GET',
                headers: {
                    'X-CSRF-Token': this.getCsrfToken()
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load recent notifications');
            }

            const data = await response.json();
            
            if (data.success) {
                this.updateNotificationUI(data.data);
                this.updateUnreadCount(data.data.filter(n => !n.is_read).length);
            }
        } catch (error) {
            console.error('Error loading recent notifications:', error);
        }
    }

    updateNotificationUI(notifications) {
        const notificationList = document.getElementById('notificationList');
        
        if (!notificationList) return;

        if (!notifications || notifications.length === 0) {
            notificationList.innerHTML = `
                <div class="notification-empty">
                    <i class="fas fa-bell-slash"></i>
                    <p>No new notifications</p>
                </div>
            `;
            return;
        }

        const notificationHTML = notifications.map(notification => {
            const iconClass = this.getNotificationIcon(notification.type);
            const timeAgo = this.formatTimeAgo(notification.created_at);
            
            return `
                <div class="notification-item ${!notification.is_read ? 'unread' : ''}" 
                     data-notification-id="${notification.id}"
                     onclick="notificationSystem.markAsRead(${notification.id})">
                    <div class="notification-icon ${notification.type}">
                        <i class="${iconClass}"></i>
                    </div>
                    <div class="notification-content">
                        <div class="notification-title">${this.escapeHtml(notification.title)}</div>
                        <div class="notification-message">${this.escapeHtml(notification.message)}</div>
                        <div class="notification-time">${timeAgo}</div>
                    </div>
                </div>
            `;
        }).join('');

        notificationList.innerHTML = notificationHTML;
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
        if (diffMins < 60) return `${diffMins} minute${diffMins > 1 ? 's' : ''} ago`;
        if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
        if (diffDays < 7) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
        
        return notificationTime.toLocaleDateString();
    }

    updateUnreadCount(count) {
        const notificationCount = document.getElementById('notificationCount');
        
        if (!notificationCount) return;

        this.unreadCount = count;
        
        if (count > 0) {
            notificationCount.textContent = count > 99 ? '99+' : count;
            notificationCount.style.display = 'block';
        } else {
            notificationCount.style.display = 'none';
        }
    }

    toggleDropdown() {
        const notificationDropdown = document.getElementById('notificationDropdown');
        const notificationsBtn = document.getElementById('notificationsBtn');
        
        if (!notificationDropdown || !notificationsBtn) return;

        this.isDropdownOpen = !this.isDropdownOpen;
        
        if (this.isDropdownOpen) {
            notificationDropdown.style.display = 'block';
            notificationsBtn.setAttribute('aria-expanded', 'true');
            this.loadRecentNotifications();
        } else {
            this.closeDropdown();
        }
    }

    closeDropdown() {
        const notificationDropdown = document.getElementById('notificationDropdown');
        const notificationsBtn = document.getElementById('notificationsBtn');
        
        if (!notificationDropdown || !notificationsBtn) return;

        this.isDropdownOpen = false;
        notificationDropdown.style.display = 'none';
        notificationsBtn.setAttribute('aria-expanded', 'false');
    }

    async markAsRead(notificationId) {
        try {
            const response = await fetch('../api.php?controller=notification&action=markAsRead', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.getCsrfToken()
                },
                body: JSON.stringify({ id: notificationId })
            });

            if (!response.ok) {
                throw new Error('Failed to mark notification as read');
            }

            const data = await response.json();
            
            if (data.success) {
                // Update UI
                const notificationItem = document.querySelector(`[data-notification-id="${notificationId}"]`);
                if (notificationItem) {
                    notificationItem.classList.remove('unread');
                }
                
                // Update count
                this.updateUnreadCount(Math.max(0, this.unreadCount - 1));
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }

    async markAllAsRead() {
        try {
            const response = await fetch('../api.php?controller=notification&action=markAllAsRead', {
                method: 'POST',
                headers: {
                    'X-CSRF-Token': this.getCsrfToken()
                }
            });

            if (!response.ok) {
                throw new Error('Failed to mark all notifications as read');
            }

            const data = await response.json();
            
            if (data.success) {
                // Update UI
                document.querySelectorAll('.notification-item.unread').forEach(item => {
                    item.classList.remove('unread');
                });
                
                // Update count
                this.updateUnreadCount(0);
            }
        } catch (error) {
            console.error('Error marking all notifications as read:', error);
        }
    }

    startPolling() {
        // Initial load
        this.loadNotifications();
        
        // Start polling
        setInterval(() => {
            if (!this.isDropdownOpen) {
                this.checkForNewNotifications();
            }
        }, this.pollInterval);
    }

    async checkForNewNotifications() {
        try {
            const response = await fetch(`../api.php?controller=notification&action=recent&limit=5&after_id=${this.lastNotificationId}`, {
                method: 'GET',
                headers: {
                    'X-CSRF-Token': this.getCsrfToken()
                }
            });

            if (!response.ok) {
                throw new Error('Failed to check for new notifications');
            }

            const data = await response.json();
            
            if (data.success && data.data && data.data.length > 0) {
                const newNotifications = data.data;
                
                // Update last notification ID
                this.lastNotificationId = Math.max(...newNotifications.map(n => n.id));
                
                // Check if there are new unread notifications
                const newUnreadCount = newNotifications.filter(n => !n.is_read).length;
                
                if (newUnreadCount > 0) {
                    // Play sound based on notification type
                    const hasNewOrder = newNotifications.some(n => n.type === 'order' && !n.is_read);
                    this.playNotificationSound(hasNewOrder ? 'order' : 'default');
                    
                    // Update unread count
                    this.updateUnreadCount(this.unreadCount + newUnreadCount);
                    
                    // Show browser notification if supported
                    this.showBrowserNotification(newNotifications[0]);
                }
            }
        } catch (error) {
            console.error('Error checking for new notifications:', error);
        }
    }

    showBrowserNotification(notification) {
        if (!('Notification' in window)) return;
        
        // Request permission if not granted
        if (Notification.permission === 'default') {
            Notification.requestPermission();
            return;
        }
        
        if (Notification.permission === 'granted') {
            const title = notification.title;
            const options = {
                body: notification.message,
                icon: '/assets/img/logo.svg',
                tag: `notification-${notification.id}`,
                requireInteraction: true
            };
            
            const browserNotification = new Notification(title, options);
            
            browserNotification.onclick = () => {
                window.focus();
                this.markAsRead(notification.id);
                browserNotification.close();
            };
            
            // Auto-close after 10 seconds
            setTimeout(() => {
                browserNotification.close();
            }, 10000);
        }
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

    // Public method to manually refresh notifications
    refresh() {
        this.loadNotifications();
    }

    // Public method to enable/disable sound
    setSoundEnabled(enabled) {
        this.soundEnabled = enabled;
    }
}

// Initialize notification system when DOM is ready
let notificationSystem;

document.addEventListener('DOMContentLoaded', function() {
    notificationSystem = new NotificationSystem();
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = NotificationSystem;
}