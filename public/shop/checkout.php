<?php
require_once __DIR__ . '/../bootstrap.php';
?><!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
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
        <section class="checkout-page">
            <div class="container">
                <div class="section-spacing">
                    <h1 class="text-2xl font-bold text-gray-800 mb-4">Checkout</h1>
                    <div class="flex items-center text-sm text-gray-600 mb-6">
                        <span class="flex items-center">
                            <i class="fas fa-shopping-cart mr-3"></i>
                            <span>Keranjang</span>
                        </span>
                        <i class="fas fa-chevron-right mx-2 text-gray-400"></i>
                        <span class="font-medium text-primary-600">
                            <i class="fas fa-credit-card mr-3"></i>
                            Checkout
                        </span>
                    </div>
                </div>

                <!-- Progress Stepper -->
                <div class="checkout-progress">
                    <div class="progress-step completed">
                        <div class="progress-step-circle">
                            <i class="fas fa-check"></i>
                        </div>
                        <span class="progress-step-label">Keranjang</span>
                    </div>
                    <i class="fas fa-chevron-right progress-arrow"></i>
                    <div class="progress-step active">
                        <div class="progress-step-circle">2</div>
                        <span class="progress-step-label">Checkout</span>
                    </div>
                    <i class="fas fa-chevron-right progress-arrow"></i>
                    <div class="progress-step">
                        <div class="progress-step-circle">3</div>
                        <span class="progress-step-label">Pembayaran</span>
                    </div>
                </div>

                <!-- Error Alert Container -->
                <div id="checkoutErrors" class="alert alert-error" style="display:none">
                    <i class="fas fa-exclamation-circle"></i>
                    <div class="alert-content">
                        <strong>Terjadi Kesalahan:</strong>
                        <ul id="errorList"></ul>
                    </div>
                </div>

                <div class="checkout-layout">
                    <div class="checkout-main">
                        <div class="checkout-form">
                            <div class="mb-6">
                                <h2 class="text-lg font-semibold text-gray-800 mb-4">
                                    <i class="fas fa-user mr-3 text-primary-600"></i>
                                    Informasi Pembeli
                                </h2>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label class="form-label" for="customerName">
                                            Nama Lengkap <span class="text-danger-500" title="Wajib diisi">*</span>
                                        </label>
                                        <input id="customerName" type="text" class="form-input"
                                            placeholder="Masukkan nama lengkap" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" for="customerPhone">
                                            Nomor HP <span class="text-danger-500" title="Wajib diisi">*</span>
                                        </label>
                                        <input id="customerPhone" type="text" class="form-input"
                                            placeholder="08xxxxxxxxxx" required>
                                        <div class="form-helper-text">
                                            <i class="fas fa-info-circle mr-1"></i>
                                            Digunakan untuk mengirim invoice pembelian via WhatsApp
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" for="customerEmail">Email (Opsional)</label>
                                        <input id="customerEmail" type="email" class="form-input"
                                            placeholder="email@example.com">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" for="customerAddress">
                                            Alamat Lengkap <span id="addressRequired" class="text-muted"
                                                style="font-size: 0.875rem;">(Opsional)</span>
                                        </label>
                                        <textarea id="customerAddress" class="form-textarea"
                                            placeholder="Masukkan alamat lengkap pengiriman"></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-6">
                                <h2 class="text-lg font-semibold text-gray-800 mb-4">
                                    <i class="fas fa-credit-card mr-3 text-primary-600"></i>
                                    Metode Pembayaran
                                </h2>
                                <div class="payment-methods">
                                    <label class="form-radio-group">
                                        <input type="radio" name="pay" value="transfer" checked class="form-radio">
                                        <div class="radio-content">
                                            <div class="font-medium">
                                                <span class="method-icon bank" aria-hidden="true"><i
                                                        class="fas fa-building"></i></span>
                                                Transfer Bank
                                            </div>
                                            <div class="text-sm text-gray-600">Transfer ke rekening BCA, BNI, atau
                                                Mandiri</div>
                                        </div>
                                    </label>
                                    <label class="form-radio-group">
                                        <input type="radio" name="pay" value="qris" class="form-radio">
                                        <div class="radio-content">
                                            <div class="font-medium">
                                                <span class="method-icon qris" aria-hidden="true"><i
                                                        class="fas fa-qrcode"></i></span>
                                                QRIS
                                            </div>
                                            <div class="text-sm text-gray-600">Bayar dengan QRIS (Gopay, OVO, Dana, dll)
                                            </div>
                                        </div>
                                    </label>
                                    <label class="form-radio-group disabled">
                                        <input type="radio" name="pay" value="cod" disabled class="form-radio">
                                        <div class="radio-content">
                                            <div class="font-medium">Bayar di Tempat (COD)</div>
                                            <div class="text-sm text-gray-600">Sementara tidak tersedia</div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>


                    </div>

                    <aside class="checkout-summary-section">
                        <div class="cart-summary-card">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                                <i class="fas fa-shopping-bag mr-3 text-primary-600"></i>
                                Ringkasan Order
                            </h3>
                            <div id="checkoutSummary" aria-live="polite">
                                <div class="text-center text-gray-500 py-8">
                                    <i class="fas fa-shopping-cart text-4xl mb-2"></i>
                                    <p>Memuat ringkasan...</p>
                                </div>
                            </div>
                            <button id="placeOrderBtn" class="btn btn-primary btn-lg btn-checkout-cta"
                                style="margin-top: var(--space-4); width: 100%;">
                                <div class="btn-checkout-cta-content">
                                    <i class="fas fa-lock mr-2"></i>
                                    <span>Buat Pesanan</span>
                                    <span id="btnTotalAmount" class="btn-checkout-cta-amount"></span>
                                </div>
                            </button>
                            <div class="btn-checkout-reassurance">
                                <i class="fas fa-shield-alt"></i>
                                <span>Pembayaran Aman & Terenkripsi</span>
                            </div>

                            <!-- Trust Badges -->
                            <div class="trust-badges">
                                <div class="trust-badge">
                                    <i class="fas fa-check-circle"></i>
                                    <span>SSL Secure</span>
                                </div>
                                <div class="trust-badge">
                                    <i class="fas fa-undo"></i>
                                    <span>100% Uang Kembali</span>
                                </div>
                                <div class="trust-badge">
                                    <i class="fas fa-headset"></i>
                                    <span>Support 24/7</span>
                                </div>
                            </div>
                        </div>

                        <div class="cart-summary-card mt-4">
                            <h4 class="font-semibold text-gray-800 mb-2">
                                <i class="fas fa-shield-alt mr-3 text-green-600"></i>
                                Garansi & Keamanan
                            </h4>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <li><i class="fas fa-check text-green-500 mr-1"></i> Cocok untuk keluarga & acara</li>
                                <li><i class="fas fa-check text-green-500 mr-1"></i> Bebas pengawet, aman</li>
                                <li><i class="fas fa-check text-green-500 mr-1"></i> Higienis dan halal</li>
                            </ul>
                        </div>
                    </aside>
                </div>
            </div>
        </section>
    </main>

    <script src="../assets/js/order-id-integration.js"></script>
    <script src="../assets/js/shop.js"></script>
    <div id="paymentSheet" class="payment-sheet" style="display:none">
        <div class="payment-card">
            <div class="payment-header">
                <div class="payment-title">
                    <i class="fas fa-credit-card"></i>
                    Pembayaran
                </div>
                <button id="closePayment" class="payment-close" aria-label="Tutup">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="payment-body">
                <div class="payment-left">
                    <!-- Order ID & Timer -->
                    <div class="payment-order-info">
                        <div class="order-id-section">
                            <span class="order-id-label">Order ID:</span>
                            <span id="paymentOrderId" class="order-id-value">#ORD-LOADING</span>
                            <button id="copyOrderIdBtn" class="btn-copy-small" title="Copy Order ID">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                        <div class="order-timestamp" id="paymentTimestamp"></div>
                    </div>

                    <!-- Payment Timer -->
                    <div id="paymentTimer" class="payment-timer">
                        <i class="fas fa-clock"></i>
                        <span>Selesaikan pembayaran dalam: </span>
                        <span id="timerDisplay" class="timer-countdown">15:00</span>
                    </div>

                    <div id="paymentMethodLabel" class="payment-method-label"></div>

                    <div id="qrisSection" style="display:none">
                        <div class="qr-image">
                            <img id="qrisImage" src="" alt="QRIS Code">
                        </div>

                        <!-- QR Download Button -->
                        <button id="downloadQrBtn" class="btn btn-outline btn-sm"
                            style="width:100%; margin-top: var(--space-3);">
                            <i class="fas fa-download mr-2"></i>
                            Download QR Code
                        </button>

                        <!-- Step-by-step Instructions -->
                        <div class="payment-instructions">
                            <h4 class="instructions-title">
                                <i class="fas fa-mobile-alt mr-2"></i>
                                Cara Pembayaran:
                            </h4>
                            <ol class="instructions-list">
                                <li>
                                    <span class="step-number">1</span>
                                    <span>Buka aplikasi e-wallet atau m-banking Anda</span>
                                </li>
                                <li>
                                    <span class="step-number">2</span>
                                    <span>Pilih menu <strong>"Scan QR"</strong> atau <strong>"QRIS"</strong></span>
                                </li>
                                <li>
                                    <span class="step-number">3</span>
                                    <span>Scan kode QR di atas</span>
                                </li>
                                <li>
                                    <span class="step-number">4</span>
                                    <span>Konfirmasi pembayaran di aplikasi</span>
                                </li>
                                <li>
                                    <span class="step-number">5</span>
                                    <span>Klik tombol <strong>"Verifikasi Pembayaran"</strong> di bawah</span>
                                </li>
                            </ol>


                            <!-- E-wallet & M-banking Logos -->
                            <div class="payment-apps-section">
                                <div class="payment-apps-label">E-Wallet:</div>
                                <div class="ewallet-logos">
                                    <span class="ewallet-logo">GoPay</span>
                                    <span class="ewallet-logo">OVO</span>
                                    <span class="ewallet-logo">Dana</span>
                                    <span class="ewallet-logo">ShopeePay</span>
                                </div>

                                <div class="payment-apps-label" style="margin-top: 8px;">M-Banking:</div>
                                <div class="ewallet-logos">
                                    <span class="ewallet-logo">BCA Mobile</span>
                                    <span class="ewallet-logo">Livin</span>
                                    <span class="ewallet-logo">BRImo</span>
                                    <span class="ewallet-logo">BNI Mobile</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="transferSection" class="transfer-info" style="display:none">
                        <h4 class="font-semibold text-gray-800 mb-3">Informasi Transfer</h4>
                        <div id="transferDetails"></div>
                    </div>

                    <div id="paymentMeta" class="payment-meta"></div>
                </div>
                <div class="payment-right">
                    <!-- Success State -->
                    <div id="paymentSuccess" class="payment-state payment-success" style="display:none">
                        <div class="success-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h3 class="success-title">Pembayaran Berhasil!</h3>
                        <p class="success-message">Invoice telah dikirim ke WhatsApp Anda</p>
                    </div>

                    <!-- Error State -->
                    <div id="paymentError" class="payment-state payment-error" style="display:none">
                        <div class="error-icon">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <h3 class="error-title">Verifikasi Gagal</h3>
                        <p class="error-message" id="errorMessage">Pembayaran belum terdeteksi. Mohon tunggu beberapa
                            saat.</p>
                    </div>

                    <!-- Order Summary -->
                    <div id="paymentOrderSummary"></div>

                    <!-- Action Buttons -->
                    <div class="mt-4 space-y-3">
                        <!-- Auto-check Status Indicator -->
                        <div id="autoCheckIndicator" class="auto-check-indicator" style="display:none">
                            <i class="fas fa-sync fa-spin mr-2"></i>
                            <span>Memeriksa status pembayaran...</span>
                        </div>

                        <button id="requestVerificationBtn" class="btn btn-success btn-block mb-3">
                            <i class="fas fa-shield-alt mr-2"></i>
                            Verifikasi Pembayaran
                        </button>

                        <button id="changePaymentMethodBtn" class="btn btn-outline btn-block mt-2">
                            <i class="fas fa-exchange-alt mr-2"></i>
                            Ganti Metode Pembayaran
                        </button>

                        <a id="trackOrderLink" href="#" class="btn btn-primary btn-block">
                            <i class="fas fa-search mr-2"></i>
                            Lihat Status Pesanan
                        </a>

                        <!-- Contact Support -->
                        <a href="https://wa.me/6281234567890" target="_blank" class="btn btn-outline btn-block"
                            id="contactSupportBtn" style="display:none">
                            <i class="fas fa-headset mr-2"></i>
                            Hubungi Customer Service
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>