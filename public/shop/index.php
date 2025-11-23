<?php
require_once __DIR__ . '/../bootstrap.php';
?><!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop</title>
    <link rel="icon" type="image/x-icon" href="../assets/img/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/shop.css">
</head>
<body class="shop-theme">
    <header class="shop-header">
        <div class="container header-content">
            <a href="index.php" class="shop-logo">
                <img src="../assets/img/logo.svg" alt="Bytebalok" class="logo-img" onerror="this.style.display='none'">
                <span>Bytebalok</span>
            </a>
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input id="searchInput" type="text" placeholder="Cari produk" autocomplete="off">
            </div>
            <div class="header-actions">
                <a href="index.php" class="action-button" aria-label="Beranda">
                    <i class="fas fa-home"></i>
                </a>
                <a href="cart.php" class="action-button" aria-label="Keranjang">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-badge" id="cartBadge" style="display:none">0</span>
                </a>
            </div>
        </div>
        <nav class="header-menu" id="categoriesNav"></nav>
    </header>

    <main class="content">
        <section class="section">
            <div class="section-controls">
                <div class="quick-filters" id="quickFilters"></div>
                <div class="control-group">
                    <label for="sortSelect" class="form-label">Urutkan</label>
                    <select id="sortSelect" class="form-select">
                        <option value="relevance">Relevansi</option>
                        <option value="newest">Terbaru</option>
                        <option value="bestseller">Terlaris</option>
                        <option value="price_asc">Harga Termurah</option>
                        <option value="price_desc">Harga Termahal</option>
                    </select>
                </div>
                <div class="view-toggle">
                    <button id="viewGrid" class="btn btn-secondary active">
                        <i class="fas fa-grip mr-2"></i>Grid
                    </button>
                    <button id="viewList" class="btn btn-secondary">
                        <i class="fas fa-list mr-2"></i>List
                    </button>
                </div>
            </div>
        </section>

        <section class="section">
            <div id="productsGrid" class="products-grid"></div>
            <div id="emptyState" class="empty-state" style="display:none">
                <div class="empty-icon">
                    <i class="fas fa-inbox"></i>
                </div>
                <div class="empty-title">Produk tidak ditemukan</div>
                <div class="empty-description">Coba ubah kata kunci pencarian atau filter yang digunakan</div>
                <button id="resetFilters" class="btn btn-outline">
                    <i class="fas fa-refresh mr-2"></i>Reset Filter
                </button>
            </div>
        </section>
    </main>

    <div id="miniCartBar" class="mini-cart-bar">
        <div class="mini-cart-info">
            <span class="cart-count text-sm" id="miniCartCount">0 item</span>
            <span class="cart-total price font-semibold" id="miniCartSubtotal">Rp 0</span>
        </div>
        <div class="mini-cart-actions">
            <a href="checkout.php" class="btn btn-primary btn-sm">
                <i class="fas fa-credit-card mr-2"></i>Checkout
            </a>
        </div>
    </div>

    <script src="../assets/js/shop.js"></script>
</body>
</html>