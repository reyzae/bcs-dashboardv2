<?php
$page_title = $page_title ?? 'Bantuan & FAQ';
include __DIR__ . '/layout/header.php';
?>

<div class="content">
    <div class="faq-container">
        <div class="faq-grid">
            <section class="faq-section">
                <h3>Cara Belanja</h3>
                <details class="faq-item" open>
                    <summary>Bagaimana cara menambahkan produk ke keranjang?</summary>
                    <div class="faq-answer">Pilih produk, atur jumlah, lalu tekan tombol keranjang. Ikon keranjang di kanan atas menunjukkan jumlah item.</div>
                </details>
                <details class="faq-item">
                    <summary>Bagaimana cara menyelesaikan pesanan?</summary>
                    <div class="faq-answer">Buka halaman keranjang, lanjutkan ke checkout, isi data, dan pilih metode pembayaran. Setelah membuat order, Anda bisa memantau status di Lacak Pesanan.</div>
                </details>
            </section>

            <section class="faq-section">
                <h3>Pembayaran</h3>
                <details class="faq-item" open>
                    <summary>Metode pembayaran apa yang tersedia?</summary>
                    <div class="faq-answer">Saat ini tersedia QRIS dan Transfer Bank. Instruksi detail tampil setelah order dibuat di halaman checkout.</div>
                </details>
                <details class="faq-item">
                    <summary>Bagaimana jika pembayaran tertunda?</summary>
                    <div class="faq-answer">Status akan diperbarui otomatis. Jika perlu bantuan, hubungi kami melalui halaman Kontak.</div>
                </details>
            </section>

            <section class="faq-section">
                <h3>Pengiriman & Status</h3>
                <details class="faq-item" open>
                    <summary>Bagaimana cara melacak status pesanan?</summary>
                    <div class="faq-answer">Masukkan kode pesanan di halaman Lacak Pesanan. Anda akan melihat status pembayaran dan progres pesanan.</div>
                </details>
                <details class="faq-item">
                    <summary>Berapa lama pesanan diproses?</summary>
                    <div class="faq-answer">Pesanan diproses secepatnya setelah pembayaran terkonfirmasi. Waktu proses dapat bervariasi tergantung antrian.</div>
                </details>
            </section>

            <aside class="faq-side">
                <div class="card">
                    <div class="card-body">
                        <h4>Link Cepat</h4>
                        <div class="faq-links">
                            <a class="btn" href="/shop/order-status.php"><i class="fas fa-search"></i> Lacak Pesanan</a>
                            <a class="btn" href="/shop/contact.php"><i class="fas fa-headset"></i> Kontak</a>
                            <a class="btn" href="/shop/index.php"><i class="fas fa-store"></i> Kembali Belanja</a>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>