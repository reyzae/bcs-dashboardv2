<?php
require_once __DIR__ . '/../bootstrap.php';
$code = $_GET['code'] ?? '';
?><!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Pesanan</title>
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
        <section class="track-order-page">
            <div class="container">
                <div class="section-header">
                    <h1 class="section-title">Status Pesanan</h1>
                    <nav aria-label="Breadcrumb" class="breadcrumb">
                        <a href="index.php" class="breadcrumb-item">
                            <i class="fas fa-home mr-2"></i>
                            <span>Beranda</span>
                        </a>
                        <span class="breadcrumb-separator" aria-hidden="true">›</span>
                        <span class="breadcrumb-item current" aria-current="page">
                            Status Pesanan
                        </span>
                    </nav>
                </div>
                
                <div class="card fade-in">
                    <div class="card-body text-center">
                        <div class="text-4xl text-primary-600 mb-3">
                            <i class="fas fa-box"></i>
                        </div>
                        <h2 class="card-title text-center mb-2">Tracking Pesanan</h2>
                        <p class="card-subtitle text-center mb-6">Masukkan nomor pesanan untuk melihat status pengiriman</p>
                        
                        <form id="trackOrderForm" class="space-y-4">
                            <div class="form-group">
                                <label class="form-label" for="trackOrderNumber">Nomor Pesanan</label>
                                <div class="input-group">
                                    <input id="trackOrderNumber" type="text" class="form-input" placeholder="Contoh: ORD-2024-001234" value="<?php echo htmlspecialchars($code); ?>">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-search mr-2"></i>Lacak
                                    </button>
                                </div>
                                <p class="form-help">Nomor pesanan biasanya diawali dengan "ORD-"</p>
                            </div>
                        </form>
                        
                        <div class="mt-6 text-center">
                            <p class="text-sm text-gray-500 mb-2">Butuh bantuan?</p>
                            <a id="shopSupportWhatsApp" href="#" class="btn btn-outline btn-sm" target="_blank">
                                <i class="fab fa-whatsapp mr-2"></i>Hubungi CS
                            </a>
                        </div>
                    </div>
                </div>
                
                <div id="orderStatusResult" class="order-details-card mt-6" style="display:none">
                    <div class="loading-overlay">
                        <div class="loading-spinner"></div>
                    </div>
                </div>
            </div>
        </section>
</main>

    <script src="../assets/js/shop.js"></script>
    <script>
    (function(){
        const el = document.getElementById('shopSupportWhatsApp');
        if (!el) return;
        fetch('../api.php?controller=settings&action=get&key=company_phone')
            .then(r => r.json())
            .then(res => {
                const phone = (res && res.success && res.data && res.data.value) ? res.data.value : '+6285121010199';
                const normalized = String(phone).replace(/[^0-9]/g,'');
                el.href = `https://wa.me/${normalized}`;
            })
            .catch(() => { el.href = 'https://wa.me/6285121010199'; });
    })();
    </script>
</body>
</html>
