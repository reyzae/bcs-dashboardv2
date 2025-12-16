/**
 * GLOBAL SEARCH FUNCTIONALITY
 * Real-time search across products, customers, and orders
 */

(function () {
    'use strict';

    const searchInput = document.getElementById('globalSearch');
    const searchResults = document.getElementById('searchResults');
    let searchTimeout;

    if (!searchInput) return;

    // Search function
    async function performSearch(query) {
        if (query.length < 2) {
            searchResults.style.display = 'none';
            return;
        }

        // Show loading
        searchResults.style.display = 'block';
        searchResults.innerHTML = '<div class="search-results-loading"><i class="fas fa-spinner fa-spin"></i> Searching...</div>';

        try {
            // Search products
            const productsResponse = await fetch(`../api.php?controller=product&action=list&search=${encodeURIComponent(query)}&limit=3`);
            const productsData = await productsResponse.json();

            // Search customers
            const customersResponse = await fetch(`../api.php?controller=customer&action=list&search=${encodeURIComponent(query)}&limit=3`);
            const customersData = await customersResponse.json();

            // Build results HTML
            let resultsHTML = '';
            let totalResults = 0;

            // Products section
            if (productsData.success && productsData.data && productsData.data.products && productsData.data.products.length > 0) {
                resultsHTML += '<div class="search-section-title" style="padding: 12px 16px; font-size: 11px; font-weight: 700; color: #9ca3af; text-transform: uppercase;">Products</div>';
                productsData.data.products.forEach(product => {
                    resultsHTML += `
                        <a href="products.php?id=${product.id}" class="search-result-item">
                            <div class="search-result-icon product">
                                <i class="fas fa-box"></i>
                            </div>
                            <div class="search-result-content">
                                <div class="search-result-title">${product.name}</div>
                                <div class="search-result-meta">SKU: ${product.sku} • Stock: ${product.stock_quantity}</div>
                            </div>
                        </a>
                    `;
                    totalResults++;
                });
            }

            // Customers section
            if (customersData.success && customersData.data && customersData.data.customers && customersData.data.customers.length > 0) {
                resultsHTML += '<div class="search-section-title" style="padding: 12px 16px; font-size: 11px; font-weight: 700; color: #9ca3af; text-transform: uppercase;">Customers</div>';
                customersData.data.customers.forEach(customer => {
                    resultsHTML += `
                        <a href="customers.php?id=${customer.id}" class="search-result-item">
                            <div class="search-result-icon customer">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="search-result-content">
                                <div class="search-result-title">${customer.name}</div>
                                <div class="search-result-meta">${customer.email || customer.phone || 'No contact'}</div>
                            </div>
                        </a>
                    `;
                    totalResults++;
                });
            }

            // No results
            if (totalResults === 0) {
                resultsHTML = `
                    <div class="search-no-results">
                        <i class="fas fa-search"></i>
                        <div style="margin-top: 8px; font-size: 14px; font-weight: 600;">No results found</div>
                        <div style="font-size: 12px; margin-top: 4px;">Try different keywords</div>
                    </div>
                `;
            }

            searchResults.innerHTML = resultsHTML;

        } catch (error) {
            console.error('Search error:', error);
            searchResults.innerHTML = `
                <div class="search-no-results">
                    <i class="fas fa-exclamation-circle"></i>
                    <div style="margin-top: 8px; font-size: 14px; font-weight: 600;">Search failed</div>
                    <div style="font-size: 12px; margin-top: 4px;">Please try again</div>
                </div>
            `;
        }
    }

    // Input event listener with debounce
    searchInput.addEventListener('input', function (e) {
        clearTimeout(searchTimeout);
        const query = e.target.value.trim();

        if (query.length < 2) {
            searchResults.style.display = 'none';
            return;
        }

        searchTimeout = setTimeout(() => {
            performSearch(query);
        }, 300);
    });

    // Focus event
    searchInput.addEventListener('focus', function () {
        if (searchInput.value.trim().length >= 2) {
            searchResults.style.display = 'block';
        }
    });

    // Click outside to close
    document.addEventListener('click', function (e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.style.display = 'none';
        }
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', function (e) {
        // Ctrl/Cmd + K to focus search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            searchInput.focus();
        }

        // Escape to close
        if (e.key === 'Escape') {
            searchResults.style.display = 'none';
            searchInput.blur();
        }
    });

})();
