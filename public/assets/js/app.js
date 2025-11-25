/**
 * Bytebalok System - Main JavaScript Application
 * Modern JavaScript with ES6+ features
 */

class BytebalokApp {
    constructor() {
        this.apiBase = '../api.php';
        this.currentUser = null;
        this.init();
    }

    init() {
        this.setupEventListeners();
        // TEMPORARILY DISABLED - API not ready
        // this.loadUserData();
        // this.initializeComponents();
        console.log('ℹ️ API calls disabled - navigation should work perfectly now!');
        this.applyInitialDensity();
        this.setupDensityToggle();
    }

    setupEventListeners() {
        // Mobile menu toggle
        const menuToggle = document.getElementById('menuToggle');
        if (menuToggle) {
            menuToggle.addEventListener('click', () => this.toggleSidebar());
        }

        // Sidebar close (X button inside sidebar)
        const sidebarClose = document.getElementById('sidebarClose');
        if (sidebarClose) {
            sidebarClose.addEventListener('click', () => {
                const sidebar = document.getElementById('sidebar');
                if (!sidebar) return;

                // On mobile/tablet: close overlay drawer
                if (window.innerWidth <= 1024) {
                    sidebar.classList.remove('open');
                    const overlay = document.getElementById('sidebarOverlay');
                    if (overlay) overlay.classList.remove('show');
                    return;
                }

                // On desktop: collapse sidebar (icon-only)
                sidebar.classList.add('sidebar-collapsed');
                try {
                    localStorage.setItem('sidebarCollapsed', 'true');
                } catch (e) {}
            });
        }

        // Close sidebar when clicking outside (mobile only)
        document.addEventListener('click', (e) => {
            const sidebar = document.getElementById('sidebar');
            const menuToggle = document.getElementById('menuToggle');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (sidebar && sidebar.classList.contains('open')) {
                // Check if click is outside sidebar and not on menu toggle
                const clickedMenuToggle = !!(menuToggle && (e.target === menuToggle || (menuToggle.contains && menuToggle.contains(e.target))));
                if (!sidebar.contains(e.target) && !clickedMenuToggle) {
                    sidebar.classList.remove('open');
                    if (overlay) overlay.classList.remove('show');
                }
            }
        });

        // Sidebar navigation links - ensure they work
        const navLinks = document.querySelectorAll('.sidebar .nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                // Allow normal navigation
                // Close sidebar on mobile after click
                const sidebar = document.getElementById('sidebar');
                if (sidebar && window.innerWidth < 768) {
                    setTimeout(() => {
                        sidebar.classList.remove('open');
                    }, 100);
                }
            });
        });

        // User menu toggle
        const userMenu = document.getElementById('userMenu');
        const userButton = document.getElementById('userButton');
        if (userButton && userMenu) {
            userButton.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggleUserMenu();
            });

            document.addEventListener('click', () => {
                userMenu.classList.remove('show');
            });
        }

        // Logout functionality
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', () => this.logout());
        }
    }

    async loadUserData() {
        try {
            const response = await this.apiCall('AuthController.php?action=me');
            if (response.success) {
                this.currentUser = response.data;
                this.updateUserInterface();
            }
        } catch (error) {
            console.error('Failed to load user data:', error);
        }
    }

    updateUserInterface() {
        if (this.currentUser) {
            // Update user name in header
            const userNameElement = document.getElementById('userName');
            if (userNameElement) {
                userNameElement.textContent = this.currentUser.full_name || this.currentUser.username;
            }

            // Update user avatar
            const userAvatarElement = document.getElementById('userAvatar');
            if (userAvatarElement) {
                const initials = this.getInitials(this.currentUser.full_name || this.currentUser.username);
                userAvatarElement.textContent = initials;
            }
        }
    }

    getInitials(name) {
        return name
            .split(' ')
            .map(word => word.charAt(0))
            .join('')
            .toUpperCase()
            .substring(0, 2);
    }

    toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        if (!sidebar) return;

        // Mobile & tablet: toggle drawer
        if (window.innerWidth <= 1024) {
            sidebar.classList.toggle('open');
            const overlay = document.getElementById('sidebarOverlay');
            if (overlay) {
                const shouldShow = sidebar.classList.contains('open');
                overlay.classList.toggle('show', shouldShow);
            }
            return;
        }

        // Desktop: toggle collapsed state (icon-only)
        const collapsed = sidebar.classList.toggle('sidebar-collapsed');
        try {
            localStorage.setItem('sidebarCollapsed', collapsed ? 'true' : 'false');
        } catch (e) {}
    }

    toggleUserMenu() {
        const userMenu = document.getElementById('userMenu');
        const btn = document.getElementById('userButton');
        if (userMenu) {
            const willShow = !userMenu.classList.contains('show');
            userMenu.classList.toggle('show');
            if (btn) {
                btn.setAttribute('aria-expanded', willShow ? 'true' : 'false');
            }
        }
    }

    applyInitialDensity() {
        try {
            let mode = localStorage.getItem('uiDensity');
            if (!mode) {
                mode = 'auto';
                localStorage.setItem('uiDensity', mode);
            }
            this.applyDensity(mode);
            window.addEventListener('resize', this.debounce(() => {
                const m = localStorage.getItem('uiDensity');
                if (m === 'auto') this.applyDensity('auto');
            }, 200));
        } catch (e) {
            this.applyDensity('auto');
        }
    }

    setupDensityToggle() {
        const btn = document.getElementById('densityToggle');
        if (!btn) return;
        btn.addEventListener('click', () => {
            const isCompact = document.documentElement.classList.contains('density-compact');
            const next = isCompact ? 'comfort' : 'compact';
            try { localStorage.setItem('uiDensity', next); } catch (e) {}
            this.applyDensity(next);
        });
    }

    applyDensity(mode) {
        const root = document.documentElement;
        root.classList.remove('density-compact', 'density-comfort');
        if (mode === 'compact') {
            root.classList.add('density-compact');
        } else if (mode === 'comfort') {
            root.classList.add('density-comfort');
        } else if (mode === 'auto') {
            const computed = this.evaluateAutoDensity();
            if (computed === 'compact') {
                root.classList.add('density-compact');
            } else {
                root.classList.add('density-comfort');
            }
        }
    }

    evaluateAutoDensity() {
        const w = window.innerWidth;
        const h = window.innerHeight;
        const dpr = window.devicePixelRatio || 1;
        const isLaptopish = w >= 1280 && w <= 1600 && h <= 900;
        const smallDesktop = w <= 1440;
        const zoomed = dpr > 1.1;
        if (isLaptopish || smallDesktop || zoomed) return 'compact';
        return 'comfort';
    }

    async apiCall(endpoint, options = {}) {
        // Convert old endpoint format to new API router format
        // Example: "AuthController.php?action=login" -> "controller=auth&action=login"
        const url = this.convertEndpoint(endpoint);

        // Resolve CSRF token from meta tag, global var, or cookie
        const getCsrfToken = () => {
            const meta = document.querySelector('meta[name="csrf-token"]');
            if (meta && meta.content) return meta.content;
            if (window.CSRF_TOKEN) return window.CSRF_TOKEN;
            const match = document.cookie.match(/(?:^|;\s*)csrf_token=([^;]+)/);
            return match ? decodeURIComponent(match[1]) : null;
        };

        const csrfToken = getCsrfToken();

        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'Cache-Control': 'no-cache',
                'Pragma': 'no-cache',
                ...(csrfToken ? { 'X-CSRF-Token': csrfToken } : {}),
            },
            credentials: 'same-origin',
            cache: 'no-store',
        };

        // Merge while preserving and augmenting headers
        const mergedOptions = {
            ...defaultOptions,
            ...options,
            headers: {
                ...defaultOptions.headers,
                ...(options.headers || {}),
                ...(csrfToken ? { 'X-CSRF-Token': csrfToken } : {}),
            },
        };

        const response = await fetch(url, mergedOptions);
        
        // Try to parse JSON response regardless of status code
        try {
            const data = await response.json();
            return data;
        } catch (jsonError) {
            // If JSON parsing fails, throw the HTTP error
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            // If response was OK but JSON parsing failed, throw JSON error
            throw jsonError;
        }
    }

    convertEndpoint(endpoint) {
        // If endpoint already contains .php (full path), use it as is
        if (endpoint.includes('.php')) {
            // If it starts with ../ or / or http, it's already a full path
            if (endpoint.startsWith('../') || endpoint.startsWith('/') || endpoint.startsWith('http')) {
                return endpoint;
            }
            // Convert "XyzController.php?action=abc" to API router format
            const match = endpoint.match(/^(\w+)Controller\.php\?(.+)$/);
            if (match) {
                const controllerName = match[1].toLowerCase();
                const params = match[2];
                return `${this.apiBase}?controller=${controllerName}&${params}`;
            }
        }
        // If it's just query params, append to apiBase
        return `${this.apiBase}?${endpoint}`;
    }

    async logout() {
        try {
            await this.apiCall('AuthController.php?action=logout', {
                method: 'POST'
            });
            
            this.showToast('Logged out successfully', 'success');
            setTimeout(() => {
                window.location.href = '../login.php';
            }, 1500);
        } catch (error) {
            console.error('Logout failed:', error);
            this.showToast('Logout failed', 'error');
        }
    }

    // Toast Manager with triage, queue, rate limit, dedupe
    showToast(message, type = 'info', opts = 4000) {
        const options = typeof opts === 'number' ? { duration: opts } : (opts || {});
        const config = this.toastConfig || (this.toastConfig = {
            maxActive: 2,
            durations: { info: 4000, success: 4000, warning: 5000, error: 0, critical: 0 },
            rateLimitMs: 5000,
            dedupeWindowMs: 10000,
            deferOnInput: true,
        });

        // Resolve duration based on type
        const duration = options.duration != null ? options.duration : (config.durations[type] ?? 4000);

        // Create container if not exists
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            document.body.appendChild(container);
        }

        // Initialize internal state
        this._toastState = this._toastState || {
            active: [],
            queue: [],
            lastShownByType: {},
            recentMessages: new Map(), // key -> {count, ts, el}
        };

        const now = Date.now();
        const key = `${type}:${message.trim()}`;

        // Dedupe: if same message within window, increment counter on existing toast
        if (this._toastState.recentMessages.has(key)) {
            const entry = this._toastState.recentMessages.get(key);
            if (now - entry.ts < config.dedupeWindowMs) {
                entry.count += 1;
                // Update text to show aggregation count
                const msgEl = entry.el?.querySelector('.toast-message');
                if (msgEl) {
                    const base = message.replace(/\s*\(x\d+\)$/,'');
                    msgEl.textContent = `${base} (x${entry.count})`;
                }
                return; // don't spawn new toast
            } else {
                this._toastState.recentMessages.delete(key);
            }
        }

        // Rate limit per type
        const lastTypeTs = this._toastState.lastShownByType[type] || 0;
        const withinRateLimit = (now - lastTypeTs) < config.rateLimitMs;

        const deferForInput = () => {
            const ae = document.activeElement;
            return config.deferOnInput && ae && (ae.tagName === 'INPUT' || ae.tagName === 'TEXTAREA' || ae.tagName === 'SELECT' || ae.isContentEditable);
        };

        const createToastEl = () => {
            const toast = document.createElement('div');
            // Style.css expects variant as `.toast.info|success|warning|error`
            toast.className = `toast ${type}`;
            toast.setAttribute('role', 'status');
            toast.setAttribute('aria-live', duration === 0 ? 'assertive' : 'polite');

            const iconMap = { info: 'fa-info-circle', success: 'fa-check-circle', warning: 'fa-exclamation-triangle', error: 'fa-times-circle', critical: 'fa-bell' };
            const icon = iconMap[type] || iconMap.info;

            toast.innerHTML = `
                <div class="toast-icon"><i class="fas ${icon}"></i></div>
                <div class="toast-content">
                    <p class="toast-message">${message}</p>
                </div>
                <button class="toast-close" aria-label="Close"><i class="fas fa-times"></i></button>
                ${duration > 0 ? '<div class="toast-progress"></div>' : ''}
            `;

            // Close handler
            toast.querySelector('.toast-close')?.addEventListener('click', () => this._dismissToast(toast));
            return toast;
        };

        const enqueueToast = () => {
            const toast = createToastEl();
            this._toastState.queue.push({ toast, duration });
            this._tryShowNext(container);
        };

        // If rate-limited or input-focused, enqueue; else show immediately
        if (withinRateLimit || deferForInput()) {
            enqueueToast();
        } else {
            const toast = createToastEl();
            this._showToastEl(container, toast, duration);
        }

        // Track dedupe info
        // Note: will be updated to include element once shown
        this._toastState.recentMessages.set(key, { count: 1, ts: now, el: null });
    }

    _tryShowNext(container) {
        const st = this._toastState;
        if (!st) return;
        while (st.active.length < (this.toastConfig?.maxActive || 2) && st.queue.length > 0) {
            const { toast, duration } = st.queue.shift();
            this._showToastEl(container, toast, duration);
        }
    }

    _showToastEl(container, toast, duration) {
        const st = this._toastState;
        const type = [...toast.classList].find(cls => ['info','success','warning','error','critical'].includes(cls)) || 'info';
        const now = Date.now();
        st.lastShownByType[type] = now;

        container.appendChild(toast);
        st.active.push(toast);

        // Link for dedupe key
        const msgText = toast.querySelector('.toast-message')?.textContent || '';
        const key = `${type}:${msgText.replace(/\s*\(x\d+\)$/,'')}`;
        const entry = st.recentMessages.get(key);
        if (entry) entry.el = toast;

        // Auto-dismiss if duration > 0
        if (duration > 0) {
            setTimeout(() => this._dismissToast(toast), duration);
        }
    }

    _dismissToast(toast) {
        const st = this._toastState;
        if (!st) return;
        toast.classList.add('removing');
        setTimeout(() => {
            // Remove from DOM
            toast.remove();
            // Remove from active list
            st.active = st.active.filter(t => t !== toast);
            // Try show next from queue
            const container = document.getElementById('toast-container');
            if (container) this._tryShowNext(container);
        }, 300);
    }

    showLoading(element) {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }
        
        if (element) {
            element.innerHTML = '<div class="loading"><div class="spinner"></div></div>';
        }
    }

    // Show skeleton placeholders
    showSkeleton(element, { lines = 3, circle = false } = {}) {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }
        if (!element) return;
        const parts = [];
        if (circle) parts.push('<div class="skeleton skeleton-circle"></div>');
        for (let i = 0; i < lines; i++) {
            parts.push('<div class="skeleton skeleton-text" style="margin-bottom:6px"></div>');
        }
        element.innerHTML = `<div class="skeleton skeleton-rect" style="margin-bottom:8px"></div>${parts.join('')}`;
    }

    hideLoading(element) {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }
        
        if (element) {
            const loading = element.querySelector('.loading');
            if (loading) {
                loading.remove();
            }
        }
    }

    formatCurrency(amount, currency = 'IDR') {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: currency,
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(amount);
    }

    formatNumber(number) {
        return new Intl.NumberFormat('id-ID').format(number);
    }

    formatDate(date, options = {}) {
        const defaultOptions = {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        };
        
        return new Intl.DateTimeFormat('id-ID', { ...defaultOptions, ...options }).format(new Date(date));
    }

    formatDateTime(date) {
        return this.formatDate(date, {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }

    initializeComponents() {
        // Initialize any page-specific components
        this.initializeCharts();
        this.initializeTables();
        this.initializeForms();
    }

    initializeCharts() {
        // Chart.js initialization will be handled by individual pages
        console.log('Charts initialized');
    }

    initializeTables() {
        // DataTables or custom table initialization
        console.log('Tables initialized');
    }

    initializeForms() {
        // Form validation and enhancement
        console.log('Forms initialized');
    }
}

// Utility functions
const Utils = {
    // Generate random ID
    generateId: () => Math.random().toString(36).substr(2, 9),
    
    // Deep clone object
    clone: (obj) => JSON.parse(JSON.stringify(obj)),
    
    // Check if value is empty
    isEmpty: (value) => {
        if (value === null || value === undefined) return true;
        if (typeof value === 'string') return value.trim() === '';
        if (Array.isArray(value)) return value.length === 0;
        if (typeof value === 'object') return Object.keys(value).length === 0;
        return false;
    },
    
    // Capitalize first letter
    capitalize: (str) => str.charAt(0).toUpperCase() + str.slice(1),
    
    // Convert to slug
    slugify: (str) => str.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, ''),
    
    // Get query parameters
    getQueryParams: () => {
        const params = new URLSearchParams(window.location.search);
        const result = {};
        for (const [key, value] of params) {
            result[key] = value;
        }
        return result;
    },
    
    // Set query parameters
    setQueryParams: (params) => {
        const url = new URL(window.location);
        Object.keys(params).forEach(key => {
            if (params[key] !== null && params[key] !== undefined) {
                url.searchParams.set(key, params[key]);
            } else {
                url.searchParams.delete(key);
            }
        });
        window.history.replaceState({}, '', url);
    },

    // Build absolute URL from relative upload paths like "uploads/..."
    buildAbsoluteUrl: (path) => {
        if (!path) return null;
        try {
            const trimmed = String(path).trim();
            if (/^https?:\/\//i.test(trimmed)) return trimmed; // already absolute
            if (trimmed.startsWith('/')) return `${window.location.origin}${trimmed}`;
            // Normalize to "/<path>"
            const normalized = trimmed.replace(/^\/+/, '');
            return `${window.location.origin}/${normalized}`;
        } catch (_) {
            return null;
        }
    },

    // Resolve image URL with placeholder fallback (align with /shop checkout)
    resolveImageUrl: (path, placeholder = '../assets/img/placeholder-product.svg') => {
        const abs = Utils.buildAbsoluteUrl(path);
        return abs || placeholder;
    },
    // Escape text for safe HTML injection
    escapeHTML: (str) => {
        const s = String(str ?? '');
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
        return s.replace(/[&<>"']/g, (ch) => map[ch]);
    }
};

// Initialize app when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.app = new BytebalokApp();
    // Expose global helper to keep backward compatibility
    window.showToast = (...args) => window.app?.showToast?.(...args);
});

// Export for use in other scripts
window.BytebalokApp = BytebalokApp;
window.Utils = Utils;
