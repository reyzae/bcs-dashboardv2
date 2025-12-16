/**
 * ADVANCED SEARCH & FILTER SYSTEM
 * 3-Second Understanding: Real-time search with smart filtering
 */

// ============================================
// REAL-TIME SEARCH WITH DEBOUNCING
// ============================================

class RealTimeSearch {
    constructor(inputElement, callback, options = {}) {
        this.input = inputElement;
        this.callback = callback;
        this.options = {
            debounceDelay: options.debounceDelay || 300,
            minLength: options.minLength || 2,
            placeholder: options.placeholder || 'Search...',
            ...options
        };

        this.debounceTimer = null;
        this.lastQuery = '';

        this.init();
    }

    init() {
        if (!this.input) return;

        this.input.placeholder = this.options.placeholder;

        this.input.addEventListener('input', (e) => {
            this.handleInput(e.target.value);
        });

        // Clear button
        if (this.options.clearButton) {
            this.addClearButton();
        }
    }

    handleInput(value) {
        clearTimeout(this.debounceTimer);

        const query = value.trim();

        // Don't search if below minimum length
        if (query.length > 0 && query.length < this.options.minLength) {
            return;
        }

        // Don't search if same as last query
        if (query === this.lastQuery) {
            return;
        }

        this.debounceTimer = setTimeout(() => {
            this.lastQuery = query;
            this.callback(query);
        }, this.options.debounceDelay);
    }

    addClearButton() {
        const wrapper = document.createElement('div');
        wrapper.style.position = 'relative';
        wrapper.style.display = 'inline-block';
        wrapper.style.width = '100%';

        this.input.parentNode.insertBefore(wrapper, this.input);
        wrapper.appendChild(this.input);

        const clearBtn = document.createElement('button');
        clearBtn.type = 'button';
        clearBtn.className = 'search-clear-btn';
        clearBtn.innerHTML = '<i class="fas fa-times"></i>';
        clearBtn.style.cssText = `
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            padding: 4px 8px;
            display: none;
        `;

        wrapper.appendChild(clearBtn);

        this.input.addEventListener('input', () => {
            clearBtn.style.display = this.input.value ? 'block' : 'none';
        });

        clearBtn.addEventListener('click', () => {
            this.input.value = '';
            clearBtn.style.display = 'none';
            this.handleInput('');
            this.input.focus();
        });
    }

    clear() {
        this.input.value = '';
        this.lastQuery = '';
        this.callback('');
    }
}

// ============================================
// FILTER MANAGER
// ============================================

class FilterManager {
    constructor(options = {}) {
        this.filters = {};
        this.options = {
            onFilterChange: options.onFilterChange || (() => { }),
            urlSync: options.urlSync !== false, // Default true
            storageKey: options.storageKey || 'filters',
            ...options
        };

        if (this.options.urlSync) {
            this.loadFromURL();
        }
    }

    setFilter(key, value) {
        if (value === null || value === undefined || value === '') {
            delete this.filters[key];
        } else {
            this.filters[key] = value;
        }

        this.updateUI();
        this.syncToURL();
        this.options.onFilterChange(this.getFilters());
    }

    getFilter(key) {
        return this.filters[key];
    }

    getFilters() {
        return { ...this.filters };
    }

    clearFilters() {
        this.filters = {};
        this.updateUI();
        this.syncToURL();
        this.options.onFilterChange(this.getFilters());
    }

    getActiveCount() {
        return Object.keys(this.filters).length;
    }

    updateUI() {
        // Update filter count badge
        const countEl = document.getElementById('filterCount');
        if (countEl) {
            const count = this.getActiveCount();
            countEl.textContent = count;
            countEl.parentElement.style.display = count > 0 ? 'flex' : 'none';
        }

        // Update filter chips/selects
        Object.keys(this.filters).forEach(key => {
            const element = document.getElementById(key);
            if (element) {
                element.value = this.filters[key];
            }
        });
    }

    syncToURL() {
        if (!this.options.urlSync) return;

        const url = new URL(window.location);

        // Clear existing filter params
        const params = new URLSearchParams(url.search);
        Object.keys(Object.fromEntries(params)).forEach(key => {
            if (key.startsWith('filter_')) {
                params.delete(key);
            }
        });

        // Add current filters
        Object.entries(this.filters).forEach(([key, value]) => {
            params.set(`filter_${key}`, value);
        });

        url.search = params.toString();
        window.history.replaceState({}, '', url);
    }

    loadFromURL() {
        const params = new URLSearchParams(window.location.search);

        params.forEach((value, key) => {
            if (key.startsWith('filter_')) {
                const filterKey = key.replace('filter_', '');
                this.filters[filterKey] = value;
            }
        });

        this.updateUI();
    }

    saveToStorage() {
        if (!this.options.storageKey) return;
        localStorage.setItem(this.options.storageKey, JSON.stringify(this.filters));
    }

    loadFromStorage() {
        if (!this.options.storageKey) return;

        const stored = localStorage.getItem(this.options.storageKey);
        if (stored) {
            try {
                this.filters = JSON.parse(stored);
                this.updateUI();
            } catch (e) {
                console.error('Failed to load filters from storage:', e);
            }
        }
    }
}

// ============================================
// BULK OPERATIONS MANAGER
// ============================================

class BulkOperationsManager {
    constructor(options = {}) {
        this.selected = new Set();
        this.options = {
            checkboxSelector: options.checkboxSelector || '.bulk-checkbox',
            selectAllSelector: options.selectAllSelector || '#selectAll',
            bulkActionsContainer: options.bulkActionsContainer || '#bulkActions',
            onSelectionChange: options.onSelectionChange || (() => { }),
            ...options
        };

        this.init();
    }

    init() {
        // Select all checkbox
        const selectAll = document.querySelector(this.options.selectAllSelector);
        if (selectAll) {
            selectAll.addEventListener('change', (e) => {
                this.toggleAll(e.target.checked);
            });
        }

        // Individual checkboxes
        this.attachCheckboxListeners();
    }

    attachCheckboxListeners() {
        document.querySelectorAll(this.options.checkboxSelector).forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                const id = e.target.value || e.target.dataset.id;
                if (e.target.checked) {
                    this.select(id);
                } else {
                    this.deselect(id);
                }
            });
        });
    }

    select(id) {
        this.selected.add(String(id));
        this.updateUI();
    }

    deselect(id) {
        this.selected.delete(String(id));
        this.updateUI();
    }

    toggleAll(checked) {
        const checkboxes = document.querySelectorAll(this.options.checkboxSelector);

        if (checked) {
            checkboxes.forEach(cb => {
                const id = cb.value || cb.dataset.id;
                this.selected.add(String(id));
                cb.checked = true;
            });
        } else {
            this.selected.clear();
            checkboxes.forEach(cb => cb.checked = false);
        }

        this.updateUI();
    }

    getSelected() {
        return Array.from(this.selected);
    }

    getCount() {
        return this.selected.size;
    }

    clear() {
        this.selected.clear();
        document.querySelectorAll(this.options.checkboxSelector).forEach(cb => {
            cb.checked = false;
        });
        const selectAll = document.querySelector(this.options.selectAllSelector);
        if (selectAll) selectAll.checked = false;
        this.updateUI();
    }

    updateUI() {
        const count = this.getCount();
        const container = document.querySelector(this.options.bulkActionsContainer);

        if (container) {
            container.style.display = count > 0 ? 'flex' : 'none';
            const countEl = container.querySelector('.bulk-count');
            if (countEl) {
                countEl.textContent = `${count} selected`;
            }
        }

        // Update select all checkbox state
        const selectAll = document.querySelector(this.options.selectAllSelector);
        if (selectAll) {
            const checkboxes = document.querySelectorAll(this.options.checkboxSelector);
            const allChecked = checkboxes.length > 0 && count === checkboxes.length;
            selectAll.checked = allChecked;
            selectAll.indeterminate = count > 0 && count < checkboxes.length;
        }

        this.options.onSelectionChange(this.getSelected());
    }

    async executeBulkAction(action, confirmMessage = null) {
        const selected = this.getSelected();

        if (selected.length === 0) {
            toastManager.warning('No items selected');
            return;
        }

        if (confirmMessage) {
            const confirmed = await confirmAction({
                title: 'Confirm Bulk Action',
                message: confirmMessage.replace('{count}', selected.length),
                confirmText: 'Proceed',
                type: 'warning'
            });

            if (!confirmed) return;
        }

        return selected;
    }
}

// ============================================
// ADVANCED TABLE MANAGER
// ============================================

class AdvancedTableManager {
    constructor(tableId, options = {}) {
        this.table = document.getElementById(tableId);
        this.tbody = this.table?.querySelector('tbody');
        this.options = {
            searchable: options.searchable !== false,
            filterable: options.filterable !== false,
            sortable: options.sortable !== false,
            bulkActions: options.bulkActions || false,
            pagination: options.pagination || false,
            itemsPerPage: options.itemsPerPage || 20,
            mobileCardView: options.mobileCardView || false,
            ...options
        };

        this.data = [];
        this.filteredData = [];
        this.currentPage = 1;

        if (this.options.searchable) {
            this.initSearch();
        }

        if (this.options.filterable) {
            this.filterManager = new FilterManager({
                onFilterChange: (filters) => this.applyFilters(filters)
            });
        }

        if (this.options.bulkActions) {
            this.bulkManager = new BulkOperationsManager({
                onSelectionChange: (selected) => this.onSelectionChange(selected)
            });
        }

        if (this.options.sortable) {
            this.initSorting();
        }
    }

    initSearch() {
        const searchInput = document.getElementById(this.options.searchInputId || 'searchInput');
        if (searchInput) {
            this.search = new RealTimeSearch(searchInput, (query) => {
                this.applySearch(query);
            }, {
                clearButton: true
            });
        }
    }

    initSorting() {
        const headers = this.table?.querySelectorAll('th[data-sortable]');
        headers?.forEach(header => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', () => {
                const field = header.dataset.sortable;
                this.sortBy(field);
            });
        });
    }

    setData(data) {
        this.data = data;
        this.filteredData = [...data];
        this.render();
    }

    applySearch(query) {
        if (!query) {
            this.filteredData = [...this.data];
        } else {
            const lowerQuery = query.toLowerCase();
            this.filteredData = this.data.filter(item => {
                return Object.values(item).some(value =>
                    String(value).toLowerCase().includes(lowerQuery)
                );
            });
        }
        this.render();
    }

    applyFilters(filters) {
        this.filteredData = this.data.filter(item => {
            return Object.entries(filters).every(([key, value]) => {
                return String(item[key]) === String(value);
            });
        });
        this.render();
    }

    sortBy(field, direction = 'auto') {
        // Auto-toggle direction
        if (direction === 'auto') {
            direction = this.lastSortField === field && this.lastSortDirection === 'asc' ? 'desc' : 'asc';
        }

        this.filteredData.sort((a, b) => {
            const aVal = a[field];
            const bVal = b[field];

            if (aVal < bVal) return direction === 'asc' ? -1 : 1;
            if (aVal > bVal) return direction === 'asc' ? 1 : -1;
            return 0;
        });

        this.lastSortField = field;
        this.lastSortDirection = direction;
        this.render();
    }

    render() {
        if (!this.tbody) return;

        if (this.filteredData.length === 0) {
            showNoResults(this.tbody.parentElement, this.search?.lastQuery || '');
            return;
        }

        // Render table rows
        const html = this.filteredData.map(item => this.renderRow(item)).join('');
        this.tbody.innerHTML = html;

        // Render mobile card view if enabled
        if (this.options.mobileCardView && window.innerWidth <= 768) {
            this.renderMobileView();
        }

        // Re-attach bulk checkbox listeners
        if (this.bulkManager) {
            this.bulkManager.attachCheckboxListeners();
        }
    }

    renderRow(item) {
        // Override this method in subclass
        return '';
    }

    renderMobileView() {
        // Override this method in subclass
    }

    onSelectionChange(selected) {
        // Override this method in subclass
    }
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        RealTimeSearch,
        FilterManager,
        BulkOperationsManager,
        AdvancedTableManager
    };
}
