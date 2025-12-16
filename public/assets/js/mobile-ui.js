/**
 * MOBILE UI HELPERS - JavaScript
 * 3-Second Understanding: Mobile-specific interactions and utilities
 */

// ============================================
// MOBILE CARD VIEW GENERATOR
// ============================================

class MobileCardView {
  constructor(container, config = {}) {
    this.container = container;
    this.config = {
      idField: config.idField || 'id',
      titleField: config.titleField || 'name',
      badgeField: config.badgeField || 'status',
      fields: config.fields || [],
      actions: config.actions || [],
      onCardClick: config.onCardClick || null,
      ...config
    };
  }

  render(data) {
    if (!Array.isArray(data) || data.length === 0) {
      showEmptyState(this.container, {
        icon: 'fa-inbox',
        title: 'No Data Available',
        description: 'There are no items to display.',
        compact: true
      });
      return;
    }

    const cards = data.map(item => this.createCard(item)).join('');
    this.container.innerHTML = cards;

    // Attach event listeners
    this.attachEventListeners();
  }

  createCard(item) {
    const id = item[this.config.idField];
    const title = item[this.config.titleField] || 'Untitled';
    const badge = this.createBadge(item);
    const fields = this.createFields(item);
    const actions = this.createActions(item);

    return `
            <div class="mobile-card" data-id="${id}">
                <div class="mobile-card-header">
                    <div class="mobile-card-title">${title}</div>
                    ${badge}
                </div>
                <div class="mobile-card-body">
                    ${fields}
                </div>
                ${actions ? `<div class="mobile-card-footer">${actions}</div>` : ''}
            </div>
        `;
  }

  createBadge(item) {
    if (!this.config.badgeField) return '';

    const value = item[this.config.badgeField];
    if (!value) return '';

    const badgeClass = this.getBadgeClass(value);
    return `<span class="mobile-card-badge ${badgeClass}">${value}</span>`;
  }

  getBadgeClass(value) {
    const lowerValue = String(value).toLowerCase();
    const classMap = {
      'active': 'badge-success',
      'inactive': 'badge-secondary',
      'pending': 'badge-warning',
      'completed': 'badge-success',
      'cancelled': 'badge-danger',
      'processing': 'badge-info'
    };
    return classMap[lowerValue] || 'badge-secondary';
  }

  createFields(item) {
    return this.config.fields.map(field => {
      const value = this.formatValue(item[field.key], field.type);
      return `
                <div class="mobile-card-row">
                    <div class="mobile-card-label">
                        ${field.icon ? `<i class="fas ${field.icon}"></i>` : ''}
                        ${field.label}
                    </div>
                    <div class="mobile-card-value">${value}</div>
                </div>
            `;
    }).join('');
  }

  formatValue(value, type) {
    if (value === null || value === undefined) return '-';

    switch (type) {
      case 'currency':
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
      case 'date':
        return new Date(value).toLocaleDateString('id-ID');
      case 'datetime':
        return new Date(value).toLocaleString('id-ID');
      case 'badge':
        return `<span class="badge ${this.getBadgeClass(value)}">${value}</span>`;
      default:
        return value;
    }
  }

  createActions(item) {
    if (!this.config.actions || this.config.actions.length === 0) return '';

    return this.config.actions.map(action => {
      const id = item[this.config.idField];
      return `
                <button class="btn btn-sm btn-${action.variant || 'primary'}" 
                        data-action="${action.name}" 
                        data-id="${id}">
                    ${action.icon ? `<i class="fas ${action.icon}"></i>` : ''}
                    ${action.label}
                </button>
            `;
    }).join('');
  }

  attachEventListeners() {
    // Card click
    if (this.config.onCardClick) {
      this.container.querySelectorAll('.mobile-card').forEach(card => {
        card.addEventListener('click', (e) => {
          if (!e.target.closest('button')) {
            const id = card.dataset.id;
            this.config.onCardClick(id);
          }
        });
      });
    }

    // Action buttons
    this.container.querySelectorAll('[data-action]').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        const action = btn.dataset.action;
        const id = btn.dataset.id;
        const handler = this.config.actions.find(a => a.name === action)?.handler;
        if (handler) handler(id);
      });
    });
  }
}

// ============================================
// COLLAPSIBLE FILTER TOGGLE
// ============================================

function initCollapsibleFilters() {
  // Create toggle button if it doesn't exist
  const filterBar = document.querySelector('.filter-bar, [data-mobile-filter-bar]');
  if (!filterBar) return;

  let toggleBtn = document.getElementById('filterToggleMobile');
  if (!toggleBtn) {
    toggleBtn = document.createElement('button');
    toggleBtn.id = 'filterToggleMobile';
    toggleBtn.className = 'filter-toggle-mobile';
    toggleBtn.innerHTML = `
            <span><i class="fas fa-filter"></i> Filters</span>
            <i class="fas fa-chevron-down"></i>
        `;
    filterBar.parentNode.insertBefore(toggleBtn, filterBar);
  }

  // Toggle functionality
  toggleBtn.addEventListener('click', () => {
    filterBar.classList.toggle('show');
    toggleBtn.classList.toggle('active');
  });
}

// ============================================
// BOTTOM SHEET MODAL HANDLER
// ============================================

function initBottomSheetModals() {
  // Add swipe-to-close functionality for mobile modals
  const modals = document.querySelectorAll('.modal');

  modals.forEach(modal => {
    let startY = 0;
    let currentY = 0;
    let isDragging = false;

    const modalContent = modal.querySelector('.modal-content');
    if (!modalContent) return;

    const header = modalContent.querySelector('.modal-header');
    if (!header) return;

    header.addEventListener('touchstart', (e) => {
      startY = e.touches[0].clientY;
      isDragging = true;
    });

    header.addEventListener('touchmove', (e) => {
      if (!isDragging) return;
      currentY = e.touches[0].clientY;
      const diff = currentY - startY;

      if (diff > 0) {
        modalContent.style.transform = `translateY(${diff}px)`;
      }
    });

    header.addEventListener('touchend', () => {
      if (!isDragging) return;
      isDragging = false;

      const diff = currentY - startY;
      if (diff > 100) {
        // Close modal
        modal.style.display = 'none';
        modalContent.style.transform = '';
      } else {
        // Snap back
        modalContent.style.transform = '';
      }
    });
  });
}

// ============================================
// MOBILE SIDEBAR TOGGLE
// ============================================

function initMobileSidebar() {
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebarOverlay');
  const menuToggle = document.getElementById('menuToggle');
  const sidebarClose = document.getElementById('sidebarClose');

  if (!sidebar || !overlay || !menuToggle) return;

  function openSidebar() {
    sidebar.classList.add('active');
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
  }

  function closeSidebar() {
    sidebar.classList.remove('active');
    overlay.classList.remove('active');
    document.body.style.overflow = '';
  }

  menuToggle.addEventListener('click', openSidebar);
  if (sidebarClose) sidebarClose.addEventListener('click', closeSidebar);
  overlay.addEventListener('click', closeSidebar);

  // Close on nav link click (mobile only)
  if (window.innerWidth <= 1024) {
    sidebar.querySelectorAll('.nav-link').forEach(link => {
      link.addEventListener('click', closeSidebar);
    });
  }
}

// ============================================
// MOBILE DROPDOWN HANDLER
// ============================================

function convertDropdownToBottomSheet(dropdown) {
  if (window.innerWidth > 768) return;

  const menu = dropdown.querySelector('.dropdown-menu, .table-actions-dropdown-menu');
  if (!menu) return;

  // Create overlay
  const overlay = document.createElement('div');
  overlay.className = 'dropdown-overlay';
  overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 999;
        display: none;
    `;

  document.body.appendChild(overlay);

  // Show/hide logic
  const toggle = dropdown.querySelector('[data-toggle="dropdown"], button');
  if (toggle) {
    toggle.addEventListener('click', () => {
      const isVisible = menu.style.display === 'block';
      menu.style.display = isVisible ? 'none' : 'block';
      overlay.style.display = isVisible ? 'none' : 'block';
    });
  }

  overlay.addEventListener('click', () => {
    menu.style.display = 'none';
    overlay.style.display = 'none';
  });
}

// ============================================
// TOUCH FEEDBACK
// ============================================

function addTouchFeedback() {
  const touchElements = document.querySelectorAll('.btn, .action-btn, .filter-chip, .mobile-card');

  touchElements.forEach(el => {
    el.addEventListener('touchstart', function () {
      this.style.opacity = '0.7';
    });

    el.addEventListener('touchend', function () {
      this.style.opacity = '1';
    });

    el.addEventListener('touchcancel', function () {
      this.style.opacity = '1';
    });
  });
}

// ============================================
// PREVENT ZOOM ON INPUT FOCUS (iOS)
// ============================================

function preventInputZoom() {
  const inputs = document.querySelectorAll('input, select, textarea');

  inputs.forEach(input => {
    // Ensure font-size is at least 16px to prevent zoom on iOS
    const fontSize = window.getComputedStyle(input).fontSize;
    if (parseInt(fontSize) < 16) {
      input.style.fontSize = '16px';
    }
  });
}

// ============================================
// HORIZONTAL SCROLL INDICATOR
// ============================================

function addScrollIndicators() {
  const scrollContainers = document.querySelectorAll('[data-chips-scroll], .status-tabs');

  scrollContainers.forEach(container => {
    const wrapper = document.createElement('div');
    wrapper.style.position = 'relative';
    container.parentNode.insertBefore(wrapper, container);
    wrapper.appendChild(container);

    const leftIndicator = document.createElement('div');
    leftIndicator.className = 'scroll-indicator scroll-indicator-left';
    leftIndicator.style.cssText = `
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 30px;
            background: linear-gradient(to right, white, transparent);
            pointer-events: none;
            z-index: 1;
            display: none;
        `;

    const rightIndicator = document.createElement('div');
    rightIndicator.className = 'scroll-indicator scroll-indicator-right';
    rightIndicator.style.cssText = `
            position: absolute;
            right: 0;
            top: 0;
            bottom: 0;
            width: 30px;
            background: linear-gradient(to left, white, transparent);
            pointer-events: none;
            z-index: 1;
        `;

    wrapper.appendChild(leftIndicator);
    wrapper.appendChild(rightIndicator);

    function updateIndicators() {
      const scrollLeft = container.scrollLeft;
      const scrollWidth = container.scrollWidth;
      const clientWidth = container.clientWidth;

      leftIndicator.style.display = scrollLeft > 0 ? 'block' : 'none';
      rightIndicator.style.display = scrollLeft < scrollWidth - clientWidth - 1 ? 'block' : 'none';
    }

    container.addEventListener('scroll', updateIndicators);
    window.addEventListener('resize', updateIndicators);
    updateIndicators();
  });
}

// ============================================
// INITIALIZE ALL MOBILE FEATURES
// ============================================

function initMobileUI() {
  if (window.innerWidth <= 1024) {
    initMobileSidebar();
  }

  if (window.innerWidth <= 768) {
    initCollapsibleFilters();
    initBottomSheetModals();
    addTouchFeedback();
    preventInputZoom();
    addScrollIndicators();

    // Convert dropdowns to bottom sheets
    document.querySelectorAll('.dropdown, .table-actions-dropdown').forEach(convertDropdownToBottomSheet);
  }
}

// Auto-initialize on DOM ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initMobileUI);
} else {
  initMobileUI();
}

// Re-initialize on window resize (debounced)
let resizeTimer;
window.addEventListener('resize', () => {
  clearTimeout(resizeTimer);
  resizeTimer = setTimeout(() => {
    initMobileUI();
  }, 250);
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
  module.exports = {
    MobileCardView,
    initCollapsibleFilters,
    initBottomSheetModals,
    initMobileSidebar,
    initMobileUI
  };
}
