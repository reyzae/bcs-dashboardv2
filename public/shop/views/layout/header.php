<?php
require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../../../../app/helpers/functions.php';
$page_title = $page_title ?? 'Shop';
$cart_count = $cart_count ?? 0;
$current = $_SERVER['SCRIPT_NAME'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <!-- PHP session id helps sync local cart to current session -->
    <meta name="php-session-id" content="<?= htmlspecialchars(session_id()) ?>">
    <title><?= htmlspecialchars($page_title) ?> - Bytebalok Shop</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/shop.css" />
    <link rel="stylesheet" href="/assets/css/style.css" />
</head>
<body class="shop-theme">
    <header class="shop-header">
        <div class="container">
            <div class="header-content">
                <a href="/shop/index.php" class="logo">
                    <img src="/assets/img/logo.svg" alt="Bytebalok" class="logo-img" />
                    <span>Bytebalok Shop</span>
                </a>
                <form class="search-bar" method="get" action="/shop/index.php">
                    <input id="searchInput" class="search-input" type="text" name="q" aria-label="Cari produk" value="<?= htmlspecialchars($query ?? '') ?>" placeholder="Cari produk..." />
                </form>
                <nav class="header-menu">
                    <a href="/shop/order-status.php" class="menu-link <?= (strpos($current, '/shop/order-status.php') !== false ? 'active' : '') ?>" title="Cek status pesanan"><i class="fas fa-receipt"></i><span>Lacak Pesanan</span></a>
                    <a href="/shop/faq.php" class="menu-link <?= (strpos($current, '/shop/faq.php') !== false ? 'active' : '') ?>" title="Panduan & FAQ"><i class="fas fa-question-circle"></i><span>Bantuan / FAQ</span></a>
                </nav>
                <div class="header-actions">
                    <?php if (strpos($current, '/shop/index.php') === false): ?>
                    <a href="/shop/index.php" class="home-button" aria-label="Beranda">
                        <i class="fas fa-home"></i>
                    </a>
                    <?php endif; ?>
                    <a href="/shop/cart.php" class="cart-button cart-button-clean" aria-label="Keranjang">
                        <svg class="cart-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="9" cy="20" r="2"></circle>
                            <circle cx="17" cy="20" r="2"></circle>
                            <path d="M3 3h2l3 12h11l2-8H6" />
                        </svg>
                        <span class="cart-count" id="cartBadge" style="<?= (intval($cart_count) > 0 ? '' : 'display:none;') ?>"><?= intval($cart_count) ?></span>
                    </a>
                </div>
            </div>
        </div>
    </header>
    <main class="container-fluid" style="padding-top:1rem; padding-bottom:2rem;">