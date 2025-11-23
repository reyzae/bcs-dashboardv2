<?php
require_once __DIR__ . '/../bootstrap.php';
?><!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang</title>
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
            <div class="search-bar" style="display: none;">
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
    </header>

    <main class="content">
        <section class="cart-section">
            <div class="container">
                <div class="section-header">
                    <h1 class="section-title">Keranjang Belanja</h1>
                </div>
                
                <div class="cart-layout">
                    <div class="cart-items">
                        <div class="card-header">
                            <h2 class="card-title">Daftar Produk</h2>
                            <button id="clearCart" class="btn btn-ghost btn-sm">
                                <i class="fas fa-trash mr-2"></i>Hapus Semua
                            </button>
                        </div>
                        <div id="cartItems">
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <div class="empty-title">Keranjang kosong</div>
                                <div class="empty-description">Ayo tambahkan produk favoritmu ke keranjang</div>
                                <a href="index.php" class="btn btn-primary">
                                    <i class="fas fa-arrow-left mr-2"></i>Lanjut Belanja
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <aside class="cart-summary">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Ringkasan Belanja</h3>
                            </div>
                            <div class="card-body">
                                <div class="summary-row">
                                    <span class="text-gray-600">Subtotal</span>
                                    <span id="summarySubtotal" class="price">Rp 0</span>
                                </div>
                                <div class="summary-row" id="taxRow" style="display: none;">
                                    <span class="text-gray-600">Pajak</span>
                                    <span id="summaryTax" class="price">Rp 0</span>
                                </div>
                                <div class="summary-divider"></div>
                                <div class="summary-total">
                                    <span class="font-semibold">Total</span>
                                    <span id="summaryTotal" class="price text-lg font-bold">Rp 0</span>
                                </div>
                                <a id="checkoutBtn" href="checkout.php" class="btn btn-primary btn-block btn-lg mt-4">
                                    <i class="fas fa-credit-card mr-2"></i>Proses Checkout
                                </a>
                                <a href="index.php" class="btn btn-ghost btn-block mt-2">
                                    <i class="fas fa-arrow-left mr-2"></i>Lanjut Belanja
                                </a>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </section>
    </main>

    <script src="../assets/js/shop.js"></script>
</body>
</html>