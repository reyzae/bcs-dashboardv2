/**
 * UI HELPERS - JavaScript Utilities
 * 3-Second Understanding: Instant visual feedback for all user actions
 */

// ============================================
// TOAST NOTIFICATION SYSTEM
// ============================================

class ToastManager {
    constructor() {
        this.container = null;
        this.toasts = [];
        this.init();
    }

    init() {
        // Create toast container if it doesn't exist
        if (!document.querySelector('.toast-container')) {
            this.container = document.createElement('div');
            this.container.className = 'toast-container';
            document.body.appendChild(this.container);
        } else {
            this.container = document.querySelector('.toast-container');
        }
    }

    show(message, type = 'info', duration = 5000, title = null) {
        const toast = this.createToast(message, type, title);
        this.container.appendChild(toast);
        this.toasts.push(toast);

        // Auto remove after duration
        if (duration > 0) {
            setTimeout(() => this.remove(toast), duration);
        }

        return toast;
    }

    createToast(message, type, title) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;

        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };

        const titles = {
            success: title || 'Success',
            error: title || 'Error',
            warning: title || 'Warning',
            info: title || 'Info'
        };

        toast.innerHTML = `
            <div class="toast-icon">
                <i class="fas ${icons[type] || icons.info}"></i>
            </div>
            <div class="toast-content">
                <div class="toast-title">${titles[type]}</div>
                <div class="toast-message">${message}</div>
            </div>
            <button class="toast-close" onclick="toastManager.remove(this.closest('.toast'))">
                <i class="fas fa-times"></i>
            </button>
            <div class="toast-progress">
                <div class="toast-progress-bar"></div>
            </div>
        `;

        return toast;
    }

    remove(toast) {
        if (!toast || !toast.parentElement) return;

        toast.classList.add('toast-removing');
        setTimeout(() => {
            if (toast.parentElement) {
                toast.parentElement.removeChild(toast);
            }
            const index = this.toasts.indexOf(toast);
            if (index > -1) {
                this.toasts.splice(index, 1);
            }
        }, 300);
    }

    success(message, title = null, duration = 5000) {
        return this.show(message, 'success', duration, title);
    }

    error(message, title = null, duration = 7000) {
        return this.show(message, 'error', duration, title);
    }

    warning(message, title = null, duration = 6000) {
        return this.show(message, 'warning', duration, title);
    }

    info(message, title = null, duration = 5000) {
        return this.show(message, 'info', duration, title);
    }

    clearAll() {
        this.toasts.forEach(toast => this.remove(toast));
    }
}

// Initialize global toast manager
const toastManager = new ToastManager();

// Backward compatibility with existing showToast function
function showToast(message, type = 'info', duration = 5000) {
    toastManager.show(message, type, duration);
}

// ============================================
// LOADING OVERLAY
// ============================================

class LoadingOverlay {
    constructor() {
        this.overlay = null;
    }

    show(message = 'Loading...', subtext = 'Please wait') {
        if (this.overlay) return; // Already showing

        this.overlay = document.createElement('div');
        this.overlay.className = 'loading-overlay';
        this.overlay.innerHTML = `
            <div class="loading-overlay-content">
                <div class="loading-overlay-spinner">
                    <i class="fas fa-spinner fa-spin"></i>
                </div>
                <div class="loading-overlay-text">${message}</div>
                <div class="loading-overlay-subtext">${subtext}</div>
            </div>
        `;
        document.body.appendChild(this.overlay);
        document.body.style.overflow = 'hidden';
    }

    hide() {
        if (this.overlay && this.overlay.parentElement) {
            this.overlay.parentElement.removeChild(this.overlay);
            this.overlay = null;
            document.body.style.overflow = '';
        }
    }

    update(message, subtext = null) {
        if (!this.overlay) return;

        const textEl = this.overlay.querySelector('.loading-overlay-text');
        const subtextEl = this.overlay.querySelector('.loading-overlay-subtext');

        if (textEl) textEl.textContent = message;
        if (subtextEl && subtext) subtextEl.textContent = subtext;
    }
}

const loadingOverlay = new LoadingOverlay();

// ============================================
// SKELETON LOADER UTILITIES
// ============================================

function showTableSkeleton(tableBody, rows = 5, cols = 6) {
    const skeletonRows = [];
    for (let i = 0; i < rows; i++) {
        const cells = [];
        for (let j = 0; j < cols; j++) {
            cells.push('<td><div class="skeleton skeleton-text"></div></td>');
        }
        skeletonRows.push(`<tr>${cells.join('')}</tr>`);
    }
    tableBody.innerHTML = skeletonRows.join('');
}

function showCardSkeleton(container) {
    container.innerHTML = `
        <div class="skeleton skeleton-title"></div>
        <div class="skeleton skeleton-text"></div>
        <div class="skeleton skeleton-text"></div>
        <div class="skeleton skeleton-text" style="width: 60%;"></div>
    `;
}

function showListSkeleton(container, items = 5) {
    const skeletonItems = [];
    for (let i = 0; i < items; i++) {
        skeletonItems.push(`
            <div style="display: flex; gap: 12px; margin-bottom: 16px;">
                <div class="skeleton skeleton-avatar"></div>
                <div style="flex: 1;">
                    <div class="skeleton skeleton-text"></div>
                    <div class="skeleton skeleton-text" style="width: 70%;"></div>
                </div>
            </div>
        `);
    }
    container.innerHTML = skeletonItems.join('');
}

// ============================================
// EMPTY STATE UTILITIES
// ============================================

function showEmptyState(container, options = {}) {
    const defaults = {
        icon: 'fa-inbox',
        title: 'No Data Available',
        description: 'There are no items to display at the moment.',
        actionText: null,
        actionCallback: null,
        compact: false
    };

    const config = { ...defaults, ...options };
    const compactClass = config.compact ? 'empty-state-compact' : '';

    let actionHtml = '';
    if (config.actionText && config.actionCallback) {
        const actionId = 'empty-action-' + Math.random().toString(36).substr(2, 9);
        actionHtml = `
            <div class="empty-state-action">
                <button class="btn btn-primary" id="${actionId}">
                    <i class="fas fa-plus"></i> ${config.actionText}
                </button>
            </div>
        `;

        setTimeout(() => {
            const btn = document.getElementById(actionId);
            if (btn) btn.addEventListener('click', config.actionCallback);
        }, 0);
    }

    container.innerHTML = `
        <div class="empty-state ${compactClass}">
            <div class="empty-state-icon">
                <i class="fas ${config.icon}"></i>
            </div>
            <div class="empty-state-title">${config.title}</div>
            <div class="empty-state-description">${config.description}</div>
            ${actionHtml}
        </div>
    `;
}

function showNoResults(container, searchTerm = '') {
    const suggestions = searchTerm ? `
        <div class="no-results-suggestions">
            <strong>Suggestions:</strong>
            <ul>
                <li>Check your spelling</li>
                <li>Try different keywords</li>
                <li>Use more general terms</li>
                <li>Clear filters and try again</li>
            </ul>
        </div>
    ` : '';

    container.innerHTML = `
        <div class="no-results">
            <div class="no-results-icon">
                <i class="fas fa-search"></i>
            </div>
            <div class="no-results-title">No Results Found</div>
            <div class="no-results-message">
                ${searchTerm ? `We couldn't find anything matching "<strong>${searchTerm}</strong>"` : 'No items match your current filters'}
            </div>
            ${suggestions}
        </div>
    `;
}

// ============================================
// ERROR STATE UTILITIES
// ============================================

function showErrorState(container, error, options = {}) {
    const defaults = {
        title: 'Something Went Wrong',
        showDetails: false,
        retryCallback: null,
        homeCallback: null
    };

    const config = { ...defaults, ...options };
    const errorMessage = typeof error === 'string' ? error : error.message || 'An unexpected error occurred';

    let detailsHtml = '';
    if (config.showDetails && error.stack) {
        detailsHtml = `<div class="error-state-details">${error.stack}</div>`;
    }

    let actionsHtml = '';
    if (config.retryCallback || config.homeCallback) {
        const retryId = 'error-retry-' + Math.random().toString(36).substr(2, 9);
        const homeId = 'error-home-' + Math.random().toString(36).substr(2, 9);

        actionsHtml = '<div class="error-state-actions">';
        if (config.retryCallback) {
            actionsHtml += `<button class="btn btn-primary" id="${retryId}"><i class="fas fa-redo"></i> Try Again</button>`;
        }
        if (config.homeCallback) {
            actionsHtml += `<button class="btn btn-secondary" id="${homeId}"><i class="fas fa-home"></i> Go Home</button>`;
        }
        actionsHtml += '</div>';

        setTimeout(() => {
            if (config.retryCallback) {
                const retryBtn = document.getElementById(retryId);
                if (retryBtn) retryBtn.addEventListener('click', config.retryCallback);
            }
            if (config.homeCallback) {
                const homeBtn = document.getElementById(homeId);
                if (homeBtn) homeBtn.addEventListener('click', config.homeCallback);
            }
        }, 0);
    }

    container.innerHTML = `
        <div class="error-state">
            <div class="error-state-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="error-state-title">${config.title}</div>
            <div class="error-state-message">${errorMessage}</div>
            ${detailsHtml}
            ${actionsHtml}
        </div>
    `;
}

// ============================================
// BUTTON LOADING STATE
// ============================================

function setButtonLoading(button, loading = true, originalText = null) {
    if (loading) {
        if (!button.dataset.originalText) {
            button.dataset.originalText = button.innerHTML;
        }
        button.classList.add('btn-loading');
        button.disabled = true;
        if (originalText) {
            button.innerHTML = originalText;
        }
    } else {
        button.classList.remove('btn-loading');
        button.disabled = false;
        if (button.dataset.originalText) {
            button.innerHTML = button.dataset.originalText;
            delete button.dataset.originalText;
        }
    }
}

// ============================================
// FORM VALIDATION FEEDBACK
// ============================================

function showFieldError(input, message) {
    // Remove existing error
    clearFieldError(input);

    // Add error class
    input.classList.add('is-invalid');
    input.classList.remove('is-valid');

    // Create error message
    const errorDiv = document.createElement('div');
    errorDiv.className = 'form-error';
    errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;

    // Insert after input
    input.parentNode.insertBefore(errorDiv, input.nextSibling);
}

function showFieldSuccess(input, message = 'Looks good!') {
    // Remove existing error
    clearFieldError(input);

    // Add success class
    input.classList.add('is-valid');
    input.classList.remove('is-invalid');

    // Create success message
    const successDiv = document.createElement('div');
    successDiv.className = 'form-success';
    successDiv.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;

    // Insert after input
    input.parentNode.insertBefore(successDiv, input.nextSibling);
}

function clearFieldError(input) {
    input.classList.remove('is-invalid', 'is-valid');

    // Remove error/success message
    const next = input.nextSibling;
    if (next && (next.classList.contains('form-error') || next.classList.contains('form-success'))) {
        next.remove();
    }
}

// ============================================
// CONFIRMATION DIALOG
// ============================================

function confirmAction(options = {}) {
    const defaults = {
        title: 'Are you sure?',
        message: 'This action cannot be undone.',
        confirmText: 'Confirm',
        cancelText: 'Cancel',
        type: 'warning', // warning, danger, info
        onConfirm: () => { },
        onCancel: () => { }
    };

    const config = { ...defaults, ...options };

    return new Promise((resolve) => {
        const modal = document.createElement('div');
        modal.className = 'modal';
        modal.style.display = 'flex';

        const typeColors = {
            warning: '#f59e0b',
            danger: '#ef4444',
            info: '#3b82f6'
        };

        const typeIcons = {
            warning: 'fa-exclamation-triangle',
            danger: 'fa-exclamation-circle',
            info: 'fa-info-circle'
        };

        modal.innerHTML = `
            <div class="modal-dialog" style="max-width: 500px;">
                <div class="modal-content">
                    <div class="modal-body" style="text-align: center; padding: 32px;">
                        <div style="font-size: 56px; color: ${typeColors[config.type]}; margin-bottom: 16px;">
                            <i class="fas ${typeIcons[config.type]}"></i>
                        </div>
                        <h3 style="font-size: 20px; font-weight: 700; margin-bottom: 12px; color: #111827;">
                            ${config.title}
                        </h3>
                        <p style="font-size: 14px; color: #6b7280; margin-bottom: 24px;">
                            ${config.message}
                        </p>
                        <div style="display: flex; gap: 12px; justify-content: center;">
                            <button class="btn btn-secondary" id="confirmCancel">
                                ${config.cancelText}
                            </button>
                            <button class="btn btn-${config.type === 'danger' ? 'danger' : 'primary'}" id="confirmOk">
                                ${config.confirmText}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        const cleanup = () => {
            modal.style.display = 'none';
            setTimeout(() => modal.remove(), 300);
        };

        document.getElementById('confirmOk').addEventListener('click', () => {
            cleanup();
            config.onConfirm();
            resolve(true);
        });

        document.getElementById('confirmCancel').addEventListener('click', () => {
            cleanup();
            config.onCancel();
            resolve(false);
        });

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                cleanup();
                config.onCancel();
                resolve(false);
            }
        });
    });
}

// ============================================
// PROGRESS TRACKER
// ============================================

class ProgressTracker {
    constructor(container, total) {
        this.container = container;
        this.total = total;
        this.current = 0;
        this.render();
    }

    render() {
        this.container.innerHTML = `
            <div class="progress-with-label">
                <div class="progress-label">Progress</div>
                <div class="progress-bar-container" style="flex: 1;">
                    <div class="progress-bar" style="width: 0%"></div>
                </div>
                <div class="progress-percentage">0%</div>
            </div>
        `;
        this.progressBar = this.container.querySelector('.progress-bar');
        this.percentageEl = this.container.querySelector('.progress-percentage');
    }

    update(current, message = null) {
        this.current = current;
        const percentage = Math.round((current / this.total) * 100);

        this.progressBar.style.width = percentage + '%';
        this.percentageEl.textContent = percentage + '%';

        if (message) {
            const label = this.container.querySelector('.progress-label');
            if (label) label.textContent = message;
        }
    }

    complete(message = 'Complete!') {
        this.update(this.total, message);
    }
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        toastManager,
        loadingOverlay,
        showTableSkeleton,
        showCardSkeleton,
        showListSkeleton,
        showEmptyState,
        showNoResults,
        showErrorState,
        setButtonLoading,
        showFieldError,
        showFieldSuccess,
        clearFieldError,
        confirmAction,
        ProgressTracker
    };
}
