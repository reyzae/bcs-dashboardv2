<?php
/**
 * Help & Documentation Center
 * Comprehensive help guide for all users
 */

// Load bootstrap
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../app/helpers/functions.php';

// Require authentication
requireAuth();

// Page configuration
$page_title = 'Help & Documentation';
$hide_welcome_banner = true;
$additional_css = [];

// Aktifkan compact header untuk tampilan header yang rapi
$header_compact = true;

// Get current user
$current_user = getCurrentUser();
$user_role = $current_user['role'];

// Include header
include __DIR__ . '/includes/header.php';
?>

<style>
    .help-container {
        max-width: 1200px;
        margin: 0 auto;
    }

    .help-hero {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
        color: white;
        padding: 48px;
        border-radius: 16px;
        margin-bottom: 32px;
        text-align: center;
        box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
    }

    .help-hero h1 {
        font-size: 36px;
        font-weight: 800;
        margin: 0 0 16px 0;
    }

    .help-hero p {
        font-size: 18px;
        opacity: 0.95;
        margin: 0;
        max-width: 600px;
        margin: 0 auto;
    }

    .search-box {
        max-width: 600px;
        margin: 32px auto 0;
        position: relative;
    }

    .search-box input {
        width: 100%;
        padding: 16px 56px 16px 24px;
        border: none;
        border-radius: 50px;
        font-size: 16px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        transition: all 0.3s;
    }

    .search-box input:focus {
        outline: none;
        box-shadow: 0 6px 24px rgba(0, 0, 0, 0.15);
    }

    .search-box .search-icon {
        position: absolute;
        right: 24px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--primary-color);
        font-size: 20px;
    }

    .help-categories {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 24px;
        margin-bottom: 48px;
    }

    .help-category-card {
        background: white;
        border-radius: 16px;
        padding: 32px;
        text-align: center;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        transition: all 0.3s;
        cursor: pointer;
        border: 2px solid transparent;
    }

    .help-category-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 32px rgba(102, 126, 234, 0.2);
        border-color: var(--primary-color);
    }

    .help-category-card .icon {
        width: 80px;
        height: 80px;
        margin: 0 auto 24px;
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 36px;
        color: white;
        box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
    }

    .help-category-card h3 {
        font-size: 20px;
        font-weight: 700;
        color: #1f2937;
        margin: 0 0 12px 0;
    }

    .help-category-card p {
        font-size: 14px;
        color: #6b7280;
        margin: 0;
        line-height: 1.6;
    }

    .faq-section {
        background: white;
        border-radius: 16px;
        padding: 40px;
        margin-bottom: 32px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
    }

    .faq-section h2 {
        font-size: 28px;
        font-weight: 700;
        color: #1f2937;
        margin: 0 0 32px 0;
        text-align: center;
    }

    .faq-item {
        margin-bottom: 20px;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.3s;
    }

    .faq-item:hover {
        border-color: #667eea;
    }

    .faq-question {
        padding: 20px 24px;
        background: #f9fafb;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 600;
        color: #1f2937;
        font-size: 16px;
        transition: all 0.3s;
    }

    .faq-question:hover {
        background: #f3f4f6;
    }

    .faq-question.active {
        background: #667eea;
        color: white;
    }

    .faq-question i {
        transition: transform 0.3s;
        color: #667eea;
    }

    .faq-question.active i {
        transform: rotate(180deg);
        color: white;
    }

    .faq-answer {
        padding: 0 24px;
        max-height: 0;
        overflow: hidden;
        transition: all 0.3s;
        background: white;
    }

    .faq-answer.active {
        padding: 24px;
        max-height: 500px;
    }

    .faq-answer p {
        margin: 0 0 12px 0;
        color: #4b5563;
        line-height: 1.8;
    }

    .faq-answer ul {
        margin: 12px 0;
        padding-left: 24px;
    }

    .faq-answer li {
        margin-bottom: 8px;
        color: #4b5563;
    }

    .shortcuts-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 16px;
        margin-top: 24px;
    }

    .shortcut-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 20px;
        background: #f9fafb;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
    }

    .shortcut-key {
        background: #1f2937;
        color: white;
        padding: 8px 16px;
        border-radius: 8px;
        font-family: 'Courier New', monospace;
        font-weight: 600;
        font-size: 14px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .shortcut-action {
        flex: 1;
        margin-left: 16px;
        color: #4b5563;
        font-size: 14px;
    }

    .contact-section {
        background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
        border-radius: 16px;
        padding: 48px;
        text-align: center;
        margin-bottom: 32px;
    }

    .contact-section h2 {
        font-size: 28px;
        font-weight: 700;
        color: #1f2937;
        margin: 0 0 16px 0;
    }

    .contact-section p {
        font-size: 16px;
        color: #6b7280;
        margin: 0 0 32px 0;
    }

    .contact-buttons {
        display: flex;
        gap: 16px;
        justify-content: center;
        flex-wrap: wrap;
    }

    .contact-btn {
        display: inline-flex;
        align-items: center;
        gap: 12px;
        padding: 16px 32px;
        background: white;
        color: #1f2937;
        border-radius: 12px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .contact-btn:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        color: #667eea;
    }

    .contact-btn i {
        font-size: 20px;
    }

    .quick-links {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 16px;
        margin-bottom: 32px;
    }

    .quick-link-card {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        transition: all 0.3s;
        text-decoration: none;
        display: block;
    }

    .quick-link-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 6px 16px rgba(102, 126, 234, 0.2);
    }

    .quick-link-card h4 {
        font-size: 16px;
        font-weight: 600;
        color: #1f2937;
        margin: 0 0 8px 0;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .quick-link-card h4 i {
        color: #667eea;
    }

    .quick-link-card p {
        font-size: 14px;
        color: #6b7280;
        margin: 0;
    }

    .video-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 24px;
        margin-top: 24px;
    }

    .video-card {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        transition: all 0.3s;
    }

    .video-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
    }

    .video-thumbnail {
        width: 100%;
        height: 180px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 48px;
    }

    .video-content {
        padding: 20px;
    }

    .video-content h4 {
        font-size: 16px;
        font-weight: 600;
        color: #1f2937;
        margin: 0 0 8px 0;
    }

    .video-content p {
        font-size: 14px;
        color: #6b7280;
        margin: 0 0 12px 0;
    }

    .video-duration {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        color: #667eea;
        font-weight: 500;
    }

    /* Responsive styles handled by responsive.css */

    .role-badge-help {
        display: inline-block;
        padding: 6px 16px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 8px;
    }

    .badge-admin {
        background: #dc3545;
        color: white;
    }

    .badge-manager {
        background: #0d6efd;
        color: white;
    }

    .badge-staff {
        background: #17a2b8;
        color: white;
    }

    .badge-cashier {
        background: #28a745;
        color: white;
    }

    .search-results {
        background: white;
        border-radius: 12px;
        margin-top: 16px;
        max-height: 400px;
        overflow-y: auto;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        display: none;
    }

    .search-results.active {
        display: block;
    }

    .search-result-item {
        padding: 16px 24px;
        border-bottom: 1px solid #e5e7eb;
        cursor: pointer;
        transition: all 0.2s;
    }

    .search-result-item:hover {
        background: #f9fafb;
    }

    .search-result-item:last-child {
        border-bottom: none;
    }

    .search-result-item h5 {
        font-size: 15px;
        font-weight: 600;
        color: #1f2937;
        margin: 0 0 6px 0;
    }

    .search-result-item p {
        font-size: 13px;
        color: #6b7280;
        margin: 0;
    }
</style>

<div class="help-container">
    <!-- Page Header: Uniform Card Style -->
    <div class="card" style="margin-bottom: 1.5rem;">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-question-circle"></i> Help & Documentation
            </h3>
            <div class="card-actions action-buttons">
                <a href="https://bytebalok.example/docs" target="_blank" class="btn btn-secondary btn-sm">
                    <i class="fas fa-external-link-alt"></i> Open Docs
                </a>
            </div>
        </div>
        <div class="card-body">
            <p style="color: #6b7280; font-size: 0.875rem;">Panduan, FAQ, dan tutorial untuk menggunakan Bytebalok</p>
        </div>
    </div>

    <!-- Hero Section -->
    <div class="help-hero">
        <h1>📚 Help & Documentation Center</h1>
        <p>Temukan jawaban, panduan, dan tutorial lengkap untuk menggunakan sistem Bytebalok</p>
        <span class="role-badge-help badge-<?php echo $user_role; ?>">
            <?php echo strtoupper($user_role); ?> MODE
        </span>
        
        <!-- Search Box -->
        <div class="search-box">
            <input type="text" id="helpSearch" placeholder="Cari bantuan... (misal: cara tambah produk, reset password)" />
            <i class="fas fa-search search-icon"></i>
            <div class="search-results" id="searchResults"></div>
        </div>
    </div>

    <!-- Quick Access Categories -->
    <div class="help-categories">
        <div class="help-category-card" onclick="scrollToSection('getting-started')">
            <div class="icon">
                <i class="fas fa-rocket"></i>
            </div>
            <h3>Getting Started</h3>
            <p>Panduan awal untuk memulai menggunakan sistem</p>
        </div>

        <div class="help-category-card" onclick="scrollToSection('features')">
            <div class="icon">
                <i class="fas fa-star"></i>
            </div>
            <h3>Fitur Utama</h3>
            <p>Pelajari semua fitur yang tersedia untuk role Anda</p>
        </div>

        <div class="help-category-card" onclick="scrollToSection('tutorials')">
            <div class="icon">
                <i class="fas fa-play-circle"></i>
            </div>
            <h3>Video Tutorial</h3>
            <p>Tutorial video step-by-step untuk setiap fungsi</p>
        </div>

        <div class="help-category-card" onclick="scrollToSection('faq')">
            <div class="icon">
                <i class="fas fa-question-circle"></i>
            </div>
            <h3>FAQ</h3>
            <p>Pertanyaan yang sering ditanyakan dan jawabannya</p>
        </div>

        <div class="help-category-card" onclick="scrollToSection('shortcuts')">
            <div class="icon">
                <i class="fas fa-keyboard"></i>
            </div>
            <h3>Keyboard Shortcuts</h3>
            <p>Shortcut keyboard untuk mempercepat pekerjaan</p>
        </div>

        <div class="help-category-card" onclick="scrollToSection('contact')">
            <div class="icon">
                <i class="fas fa-headset"></i>
            </div>
            <h3>Contact Support</h3>
            <p>Hubungi tim support untuk bantuan lebih lanjut</p>
        </div>
    </div>

    <!-- Getting Started Section -->
    <div class="faq-section" id="getting-started">
        <h2>🚀 Getting Started</h2>
        <div class="quick-links">
            <a href="index.php" class="quick-link-card">
                <h4><i class="fas fa-home"></i> Dashboard Overview</h4>
                <p>Kenali dashboard utama sistem</p>
            </a>
            
            <?php if (in_array($user_role, ['admin', 'manager', 'cashier', 'staff'])): ?>
            <a href="pos.php" class="quick-link-card">
                <h4><i class="fas fa-cash-register"></i> POS System</h4>
                <p>Cara menggunakan Point of Sale</p>
            </a>
            <?php endif; ?>
            
            <?php if (in_array($user_role, ['admin', 'manager'])): ?>
            <a href="products.php" class="quick-link-card">
                <h4><i class="fas fa-box"></i> Product Management</h4>
                <p>Kelola produk dan inventory</p>
            </a>
            
            <a href="reports.php" class="quick-link-card">
                <h4><i class="fas fa-chart-bar"></i> Reports & Analytics</h4>
                <p>Lihat laporan penjualan & statistik</p>
            </a>
            <?php endif; ?>
            
            <?php if ($user_role === 'admin'): ?>
            <a href="users.php" class="quick-link-card">
                <h4><i class="fas fa-users"></i> User Management</h4>
                <p>Kelola user dan permissions</p>
            </a>
            
            <a href="settings.php" class="quick-link-card">
                <h4><i class="fas fa-cog"></i> System Settings</h4>
                <p>Konfigurasi sistem</p>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Video Tutorials Section -->
    <div class="faq-section" id="tutorials">
        <h2>🎥 Video Tutorial</h2>
        <div class="video-grid">
            <div class="video-card">
                <div class="video-thumbnail">
                    <i class="fas fa-play-circle"></i>
                </div>
                <div class="video-content">
                    <h4>Cara Login & Navigasi Dashboard</h4>
                    <p>Pelajari cara login dan navigasi dasar sistem Bytebalok</p>
                    <span class="video-duration"><i class="fas fa-clock"></i> 5 menit</span>
                </div>
            </div>

            <?php if (in_array($user_role, ['admin', 'manager', 'cashier', 'staff'])): ?>
            <div class="video-card">
                <div class="video-thumbnail">
                    <i class="fas fa-play-circle"></i>
                </div>
                <div class="video-content">
                    <h4>Menggunakan POS System</h4>
                    <p>Tutorial lengkap cara melakukan transaksi penjualan</p>
                    <span class="video-duration"><i class="fas fa-clock"></i> 8 menit</span>
                </div>
            </div>
            <?php endif; ?>

            <?php if (in_array($user_role, ['admin', 'manager'])): ?>
            <div class="video-card">
                <div class="video-thumbnail">
                    <i class="fas fa-play-circle"></i>
                </div>
                <div class="video-content">
                    <h4>Menambah & Edit Produk</h4>
                    <p>Cara mengelola produk dan inventory dengan mudah</p>
                    <span class="video-duration"><i class="fas fa-clock"></i> 6 menit</span>
                </div>
            </div>

            <div class="video-card">
                <div class="video-thumbnail">
                    <i class="fas fa-play-circle"></i>
                </div>
                <div class="video-content">
                    <h4>Membuat Laporan Penjualan</h4>
                    <p>Cara menggunakan fitur reports dan export data</p>
                    <span class="video-duration"><i class="fas fa-clock"></i> 7 menit</span>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- FAQ Section -->
    <div class="faq-section" id="faq">
        <h2>❓ Frequently Asked Questions</h2>
        
        <!-- General FAQs -->
        <div class="faq-item">
            <div class="faq-question">
                <span>Bagaimana cara login ke sistem?</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                <p>Untuk login ke sistem Bytebalok:</p>
                <ul>
                    <li>Buka URL: <strong>/dashboard/</strong> atau <strong>/public/login.php</strong></li>
                    <li>Masukkan username dan password yang diberikan oleh admin</li>
                    <li>Klik tombol "Login"</li>
                    <li>Sistem akan mengarahkan Anda ke dashboard sesuai role</li>
                </ul>
                <p><strong>Lupa password?</strong> Hubungi admin untuk reset password.</p>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                <span>Apa perbedaan antara role Admin, Manager, Staff, dan Cashier?</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                <p>Setiap role memiliki akses dan permissions berbeda:</p>
                <ul>
                    <li><strong>Admin:</strong> Full access - kelola user, produk, reports, settings</li>
                    <li><strong>Manager:</strong> Kelola produk, view reports, tidak bisa kelola user</li>
                    <li><strong>Staff:</strong> Update stock produk, view reports terbatas</li>
                    <li><strong>Cashier:</strong> Fokus POS, proses transaksi, view own reports</li>
                </ul>
            </div>
        </div>

        <?php if (in_array($user_role, ['admin', 'manager', 'cashier', 'staff'])): ?>
        <!-- POS FAQs -->
        <div class="faq-item">
            <div class="faq-question">
                <span>Bagaimana cara melakukan transaksi di POS?</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                <p>Langkah-langkah transaksi POS:</p>
                <ul>
                    <li>1. Pilih customer (optional untuk member)</li>
                    <li>2. Cari dan tambahkan produk ke cart (klik card, search, atau scan barcode)</li>
                    <li>3. Atur quantity sesuai pesanan</li>
                    <li>4. Berikan diskon jika ada (optional)</li>
                    <li>5. Pilih metode pembayaran (Cash, Card, QRIS, Transfer)</li>
                    <li>6. Untuk Cash: masukkan jumlah uang diterima, sistem otomatis hitung kembalian</li>
                    <li>7. Klik "Process Payment" atau tekan F12</li>
                    <li>8. Receipt akan otomatis terbuka untuk di-print</li>
                    <li>9. Serahkan receipt dan produk ke customer</li>
                </ul>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                <span>Apa itu Hold Transaction dan kapan menggunakannya?</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                <p><strong>Hold Transaction</strong> adalah fitur untuk menyimpan transaksi yang belum selesai.</p>
                <p><strong>Gunakan saat:</strong></p>
                <ul>
                    <li>Customer belum siap membayar</li>
                    <li>Ada customer lain yang ingin transaksi cepat</li>
                    <li>Customer keluar sebentar untuk ambil uang</li>
                    <li>Menunggu konfirmasi harga/diskon</li>
                </ul>
                <p><strong>Cara menggunakan:</strong></p>
                <ul>
                    <li>Tekan <strong>F8</strong> atau klik tombol "Hold"</li>
                    <li>Transaksi akan tersimpan dan cart akan kosong</li>
                    <li>Untuk melanjutkan: klik "Resume" → pilih transaksi → lanjutkan</li>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <?php if (in_array($user_role, ['admin', 'manager'])): ?>
        <!-- Product Management FAQs -->
        <div class="faq-item">
            <div class="faq-question">
                <span>Bagaimana cara menambah produk baru?</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                <p>Untuk menambah produk baru:</p>
                <ul>
                    <li>1. Buka menu <strong>Products</strong></li>
                    <li>2. Klik tombol <strong>"+ Add Product"</strong></li>
                    <li>3. Isi form produk:
                        <ul>
                            <li><strong>SKU:</strong> Kode unik produk (otomatis jika kosong)</li>
                            <li><strong>Name:</strong> Nama produk</li>
                            <li><strong>Category:</strong> Pilih kategori</li>
                            <li><strong>Price:</strong> Harga jual</li>
                            <li><strong>Cost:</strong> Harga modal (optional)</li>
                            <li><strong>Stock:</strong> Jumlah stock awal</li>
                            <li><strong>Min Stock:</strong> Minimum stock untuk alert</li>
                            <li><strong>Barcode:</strong> Barcode produk (optional)</li>
                        </ul>
                    </li>
                    <li>4. Upload gambar produk (optional)</li>
                    <li>5. Klik <strong>"Save"</strong></li>
                </ul>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                <span>Bagaimana cara update stock produk?</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                <p>Ada beberapa cara update stock:</p>
                <ul>
                    <li><strong>Manual Update:</strong>
                        <ul>
                            <li>Buka menu Products</li>
                            <li>Klik tombol Edit pada produk</li>
                            <li>Update field "Stock Quantity"</li>
                            <li>Save perubahan</li>
                        </ul>
                    </li>
                    <li><strong>Otomatis:</strong> Stock akan berkurang otomatis setiap ada transaksi POS</li>
                    <li><strong>Stock Opname:</strong> Untuk rekonsilisasi stock berkala (weekly/monthly)</li>
                </ul>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                <span>Apa artinya indikator warna stock (hijau, kuning, merah)?</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                <p>Indikator warna stock menunjukkan kondisi inventory:</p>
                <ul>
                    <li><strong>🟢 Hijau (Normal):</strong> Stock aman, di atas minimum stock</li>
                    <li><strong>⚠️ Kuning (Low Stock):</strong> Stock mendekati minimum, perlu restock segera</li>
                    <li><strong>🔴 Merah (Out of Stock):</strong> Stock habis, tidak bisa dijual, URGENT restock</li>
                </ul>
                <p>Anda bisa mengatur <strong>Minimum Stock Level</strong> di setiap produk untuk trigger alert.</p>
            </div>
        </div>
        <?php endif; ?>

        <?php if (in_array($user_role, ['admin', 'manager'])): ?>
        <!-- Reports FAQs -->
        <div class="faq-item">
            <div class="faq-question">
                <span>Bagaimana cara melihat laporan penjualan?</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                <p>Untuk melihat laporan penjualan:</p>
                <ul>
                    <li>1. Buka menu <strong>Reports</strong></li>
                    <li>2. Pilih jenis report:
                        <ul>
                            <li><strong>Sales Report:</strong> Total penjualan by periode</li>
                            <li><strong>Product Report:</strong> Best sellers & stock analysis</li>
                            <li><strong>Customer Report:</strong> Customer analytics</li>
                            <li><strong>Inventory Report:</strong> Stock value & movements</li>
                        </ul>
                    </li>
                    <li>3. Filter berdasarkan:
                        <ul>
                            <li>Date range (hari, minggu, bulan, custom)</li>
                            <li>Payment method</li>
                            <li>Customer</li>
                            <li>Product category</li>
                        </ul>
                    </li>
                    <li>4. Klik <strong>"Generate Report"</strong></li>
                    <li>5. Export ke PDF atau Excel jika diperlukan</li>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <!-- Troubleshooting FAQs -->
        <div class="faq-item">
            <div class="faq-question">
                <span>Produk tidak muncul di POS, apa yang harus dilakukan?</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                <p>Jika produk tidak muncul, coba langkah berikut:</p>
                <ul>
                    <li>1. <strong>Refresh halaman:</strong> Tekan Ctrl+F5 untuk hard reload</li>
                    <li>2. <strong>Cek status produk:</strong> Pastikan produk active (tidak di-disable)</li>
                    <li>3. <strong>Cek stock:</strong> Produk out of stock mungkin disembunyikan</li>
                    <li>4. <strong>Search:</strong> Gunakan search box (F2) untuk cari by nama/SKU</li>
                    <li>5. <strong>Clear cache:</strong> Logout dan login kembali</li>
                </ul>
                <p>Jika masih tidak muncul, hubungi admin untuk cek database.</p>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                <span>Receipt tidak auto-print, bagaimana cara print manual?</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                <p>Jika receipt tidak otomatis terbuka:</p>
                <ul>
                    <li><strong>Popup blocker:</strong> Browser mungkin block popup. Allow popup untuk domain ini</li>
                    <li><strong>Manual print:</strong> 
                        <ul>
                            <li>Window receipt tetap akan terbuka di background</li>
                            <li>Switch ke tab receipt</li>
                            <li>Tekan <strong>Ctrl+P</strong> untuk print</li>
                        </ul>
                    </li>
                    <li><strong>Reprint:</strong> Buka menu Transactions → cari transaksi → klik "Print Receipt"</li>
                </ul>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                <span>Lupa password, bagaimana cara reset?</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                <p>Untuk reset password:</p>
                <ul>
                    <li><strong>Hubungi Admin</strong> melalui phone/email</li>
                    <li>Admin akan reset password Anda dari menu Users</li>
                    <li>Anda akan menerima password baru</li>
                    <li>Disarankan untuk <strong>ganti password</strong> setelah login pertama kali</li>
                </ul>
                <?php if ($user_role === 'admin'): ?>
                <p><strong>Untuk Admin:</strong> Buka menu <strong>Users</strong> → Edit user → Reset Password</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Keyboard Shortcuts Section -->
    <div class="faq-section" id="shortcuts">
        <h2>⌨️ Keyboard Shortcuts</h2>
        <p style="text-align: center; color: #6b7280; margin-bottom: 32px;">
            Gunakan shortcut keyboard untuk mempercepat pekerjaan Anda
        </p>
        
        <?php
        // Define shortcuts by role
        $shortcuts = [];
        
        if (in_array($user_role, ['admin', 'manager', 'cashier', 'staff'])) {
            $shortcuts['POS System'] = [
                'F2' => 'Cari Produk',
                'F3' => 'Cari Customer',
                'F4' => 'Clear Cart',
                'F8' => 'Hold Transaction',
                'F9' => 'Cash Payment',
                'F12' => 'Process Payment',
                'ESC' => 'Close Modal'
            ];
        }
        
        if (in_array($user_role, ['admin', 'manager'])) {
            $shortcuts['Products Management'] = [
                'Ctrl+N' => 'Add New Product',
                'Ctrl+F' => 'Search Products',
                'Ctrl+E' => 'Edit Selected Product',
                'F5' => 'Refresh Product List'
            ];
            
            $shortcuts['Customers Management'] = [
                'Ctrl+N' => 'Add New Customer',
                'Ctrl+F' => 'Search Customers',
                'F5' => 'Refresh Customer List'
            ];
            
            $shortcuts['Reports'] = [
                'Ctrl+P' => 'Print Report',
                'Ctrl+E' => 'Export to Excel',
                'F5' => 'Refresh Report'
            ];
        }
        
        $shortcuts['General'] = [
            'Ctrl+H' => 'Go to Dashboard Home',
            'Ctrl+/' => 'Open Help (this page)',
            'Ctrl+L' => 'Logout'
        ];
        
        foreach ($shortcuts as $category => $items):
        ?>
        <h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin: 32px 0 16px 0;">
            <?php echo $category; ?>
        </h3>
        <div class="shortcuts-grid">
            <?php foreach ($items as $key => $action): ?>
            <div class="shortcut-item">
                <span class="shortcut-key"><?php echo $key; ?></span>
                <span class="shortcut-action"><?php echo $action; ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
        
        <div style="margin-top: 32px; padding: 20px; background: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 8px;">
            <p style="margin: 0; color: #92400e; font-size: 14px;">
                <strong>💡 Tip:</strong> Hafalkan shortcuts yang sering Anda gunakan untuk meningkatkan produktivitas hingga 2x lipat!
            </p>
        </div>
    </div>

    <!-- Contact Support Section -->
    <div class="contact-section" id="contact">
        <h2>📞 Need More Help?</h2>
        <p>Tim support kami siap membantu Anda. Hubungi kami melalui:</p>
        
        <div class="contact-buttons">
            <a href="mailto:support@bytebalok.com" class="contact-btn">
                <i class="fas fa-envelope"></i>
                <span>Email Support</span>
            </a>
            
            <a id="supportWhatsApp" href="#" class="contact-btn" target="_blank">
                <i class="fab fa-whatsapp"></i>
                <span>WhatsApp</span>
            </a>
            
            <a id="supportCall" href="#" class="contact-btn">
                <i class="fas fa-phone"></i>
                <span>Call Us</span>
            </a>
        </div>
        
        <div style="margin-top: 32px; padding-top: 24px; border-top: 1px solid #d1d5db;">
            <p style="font-size: 14px; color: #6b7280; margin: 0;">
                <strong>Support Hours:</strong> Senin - Jumat: 09:00 - 17:00 WIB<br>
                <strong>Response Time:</strong> Maksimal 24 jam kerja
            </p>
        </div>
    </div>

    <!-- System Info -->
    <div class="faq-section" style="text-align: center; color: #6b7280;">
        <p style="margin: 0; font-size: 14px;">
            <strong>Bytebalok POS System</strong> v1.0<br>
            © 2025 Bytebalok. All rights reserved.
        </p>
        <p style="margin: 16px 0 0 0; font-size: 13px;">
            <a href="../" style="color: #667eea; text-decoration: none;">Documentation</a> •
            <a href="settings.php" style="color: #667eea; text-decoration: none;">Settings</a> •
            <a href="#" style="color: #667eea; text-decoration: none;">Privacy Policy</a>
        </p>
    </div>
</div>

<script>
// FAQ Toggle
document.querySelectorAll('.faq-question').forEach(question => {
    question.addEventListener('click', () => {
        const answer = question.nextElementSibling;
        const isActive = question.classList.contains('active');
        
        // Close all other FAQs
        document.querySelectorAll('.faq-question').forEach(q => {
            q.classList.remove('active');
            q.nextElementSibling.classList.remove('active');
        });
        
        // Toggle current FAQ
        if (!isActive) {
            question.classList.add('active');
            answer.classList.add('active');
        }
    });
});

// Smooth Scroll to Section
function scrollToSection(sectionId) {
    const section = document.getElementById(sectionId);
    if (section) {
        section.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

// Search Functionality
const helpSearch = document.getElementById('helpSearch');
const searchResults = document.getElementById('searchResults');

// Search index with keywords
const searchIndex = [
    { title: 'Cara Login', keywords: ['login', 'masuk', 'sign in', 'password'], section: 'faq' },
    { title: 'Role & Permissions', keywords: ['role', 'admin', 'manager', 'cashier', 'staff', 'permission', 'akses'], section: 'faq' },
    { title: 'Transaksi POS', keywords: ['pos', 'transaksi', 'jual', 'bayar', 'payment', 'kasir'], section: 'faq' },
    { title: 'Hold Transaction', keywords: ['hold', 'tahan', 'simpan', 'resume'], section: 'faq' },
    { title: 'Tambah Produk', keywords: ['tambah produk', 'add product', 'produk baru', 'new product'], section: 'faq' },
    { title: 'Update Stock', keywords: ['update stock', 'edit stock', 'stok', 'inventory'], section: 'faq' },
    { title: 'Stock Indicator', keywords: ['stock', 'hijau', 'kuning', 'merah', 'low stock', 'out of stock'], section: 'faq' },
    { title: 'Laporan Penjualan', keywords: ['laporan', 'report', 'sales', 'penjualan', 'analytics'], section: 'faq' },
    { title: 'Print Receipt', keywords: ['print', 'receipt', 'struk', 'nota'], section: 'faq' },
    { title: 'Reset Password', keywords: ['password', 'reset', 'lupa', 'forgot'], section: 'faq' },
    { title: 'Keyboard Shortcuts', keywords: ['keyboard', 'shortcut', 'hotkey', 'F2', 'F12'], section: 'shortcuts' },
    { title: 'Contact Support', keywords: ['support', 'help', 'bantuan', 'hubungi', 'contact'], section: 'contact' }
];

helpSearch.addEventListener('input', (e) => {
    const query = e.target.value.toLowerCase();
    
    if (query.length < 2) {
        searchResults.classList.remove('active');
        return;
    }
    
    const results = searchIndex.filter(item => 
        item.title.toLowerCase().includes(query) || 
        item.keywords.some(keyword => keyword.includes(query))
    );
    
    if (results.length > 0) {
        searchResults.innerHTML = results.map(result => `
            <div class="search-result-item" onclick="scrollToSection('${result.section}'); helpSearch.value=''; searchResults.classList.remove('active');">
                <h5>${result.title}</h5>
                <p>Klik untuk melihat detail</p>
            </div>
        `).join('');
        searchResults.classList.add('active');
    } else {
        searchResults.innerHTML = `
            <div class="search-result-item">
                <h5>Tidak ada hasil</h5>
                <p>Coba kata kunci lain atau hubungi support</p>
            </div>
        `;
        searchResults.classList.add('active');
    }
});

// Close search results when clicking outside
document.addEventListener('click', (e) => {
    if (!e.target.closest('.search-box')) {
        searchResults.classList.remove('active');
    }
});

// Keyboard shortcut to open help (Ctrl+/)
document.addEventListener('keydown', (e) => {
    if (e.ctrlKey && e.key === '/') {
        e.preventDefault();
        window.location.href = 'help.php';
    }
});

console.log('✅ Help Center loaded successfully');
console.log('💡 Pro tip: Use Ctrl+/ to quickly access this help page from anywhere');
</script>

<script>
// Inject support phone from settings
(function(){
    const wa = document.getElementById('supportWhatsApp');
    const call = document.getElementById('supportCall');
    if (!wa && !call) return;
    fetch('../api.php?controller=settings&action=get&key=company_phone')
        .then(r => r.json())
        .then(res => {
            const phone = (res && res.success && res.data && res.data.value) ? res.data.value : '+6285121010199';
            const normalized = String(phone).replace(/[^0-9]/g,'');
            if (wa) wa.href = `https://wa.me/${normalized}`;
            if (call) call.href = `tel:+${normalized}`;
        })
        .catch(() => {
            if (wa) wa.href = 'https://wa.me/6285121010199';
            if (call) call.href = 'tel:+6285121010199';
        });
})();
</script>

<?php
// Include footer
include __DIR__ . '/includes/footer.php';
?>

