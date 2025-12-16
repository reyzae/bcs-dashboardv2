/**
 * Modal Helper Functions
 * Simple functions untuk show/hide modals
 * 
 * USAGE:
 * showConfirmModal('Logout', 'Are you sure?', onConfirm, onCancel);
 * showModal('my-modal-id');
 * hideModal('my-modal-id');
 */

/**
 * Show confirmation modal
 * @param {string} title - Modal title
 * @param {string} message - Confirmation message
 * @param {Function} onConfirm - Callback when confirmed
 * @param {Function} onCancel - Callback when cancelled
 * @param {string} type - danger, warning, info (default: info)
 */
function showConfirmModal(title, message, onConfirm, onCancel, type = 'info') {
    // Remove existing confirm modal if any
    const existing = document.getElementById('confirm-modal');
    if (existing) {
        existing.remove();
    }

    // Icon berdasarkan type
    const icons = {
        danger: '⚠️',
        warning: '⚠️',
        info: 'ℹ️',
        success: '✓'
    };

    // Button text & class berdasarkan type
    const confirmBtnClass = type === 'danger' ? 'btn-danger' : 'btn-primary';
    const confirmBtnText = type === 'danger' ? 'Ya, Hapus' : 'Ya';

    // Create modal HTML
    const modalHTML = `
        <div class="modal-backdrop" id="confirm-modal-backdrop">
            <div class="modal modal-confirm modal-${type}">
                <div class="modal-header">
                    <div class="modal-title">
                        <span class="modal-title-icon">${icons[type]}</span>
                        ${title}
                    </div>
                    <button class="modal-close" onclick="hideConfirmModal()" aria-label="Close">
                        ×
                    </button>
                </div>
                <div class="modal-body">
                    <p>${message}</p>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline" onclick="hideConfirmModal()">
                        Batal
                    </button>
                    <button class="btn ${confirmBtnClass}" id="confirm-btn">
                        ${confirmBtnText}
                    </button>
                </div>
            </div>
        </div>
    `;

    // Add to body
    document.body.insertAdjacentHTML('beforeend', modalHTML);

    // Get elements
    const backdrop = document.getElementById('confirm-modal-backdrop');
    const confirmBtn = document.getElementById('confirm-btn');

    // Show modal
    setTimeout(() => backdrop.classList.add('active'), 10);

    // Confirm button handler
    confirmBtn.addEventListener('click', function () {
        hideConfirmModal();
        if (onConfirm) {
            onConfirm();
        }
    });

    // Cancel on backdrop click
    backdrop.addEventListener('click', function (e) {
        if (e.target === backdrop) {
            hideConfirmModal();
            if (onCancel) {
                onCancel();
            }
        }
    });

    // ESC key to close
    document.addEventListener('keydown', function escHandler(e) {
        if (e.key === 'Escape') {
            hideConfirmModal();
            if (onCancel) {
                onCancel();
            }
            document.removeEventListener('keydown', escHandler);
        }
    });
}

/**
 * Hide confirmation modal
 */
function hideConfirmModal() {
    const backdrop = document.getElementById('confirm-modal-backdrop');
    if (backdrop) {
        backdrop.classList.remove('active');
        setTimeout(() => backdrop.remove(), 300);
    }
}

/**
 * Show logout confirmation
 * @param {Function} onConfirm - Callback when confirmed
 */
function showLogoutConfirm(onConfirm) {
    showConfirmModal(
        'Konfirmasi Logout',
        'Apakah Anda yakin ingin keluar dari akun?',
        onConfirm,
        null,
        'warning'
    );
}

/**
 * Show delete confirmation
 * @param {string} itemName - Name of item to delete
 * @param {Function} onConfirm - Callback when confirmed
 */
function showDeleteConfirm(itemName, onConfirm) {
    showConfirmModal(
        'Konfirmasi Hapus',
        `Apakah Anda yakin ingin menghapus "${itemName}"? Tindakan ini tidak dapat dibatalkan.`,
        onConfirm,
        null,
        'danger'
    );
}

/**
 * Show generic modal by ID
 * @param {string} modalId - ID of modal element
 */
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
    }
}

/**
 * Hide generic modal by ID
 * @param {string} modalId - ID of modal element
 */
function hideModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        // Optional: remove from DOM after animation
        // setTimeout(() => modal.remove(), 300);
    }
}

/**
 * Create custom modal
 * @param {Object} config - Modal configuration
 * @returns {string} - Modal ID
 */
function createModal(config) {
    const {
        id = 'custom-modal-' + Date.now(),
        title = 'Modal',
        content = '',
        footer = '',
        size = 'medium', // small, medium, large
        closable = true
    } = config;

    const sizeClass = size === 'small' ? 'modal-sm' : size === 'large' ? 'modal-lg' : '';

    const modalHTML = `
        <div class="modal-backdrop" id="${id}-backdrop">
            <div class="modal ${sizeClass}">
                <div class="modal-header">
                    <div class="modal-title">${title}</div>
                    ${closable ? `<button class="modal-close" onclick="hideModal('${id}-backdrop')" aria-label="Close">×</button>` : ''}
                </div>
                <div class="modal-body">
                    ${content}
                </div>
                ${footer ? `<div class="modal-footer">${footer}</div>` : ''}
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHTML);

    // Show modal
    setTimeout(() => {
        const backdrop = document.getElementById(id + '-backdrop');
        if (backdrop) {
            backdrop.classList.add('active');
        }
    }, 10);

    return id;
}

// Export functions (jika pakai modules)
// export { showConfirmModal, hideConfirmModal, showLogoutConfirm, showDeleteConfirm, showModal, hideModal, createModal };
