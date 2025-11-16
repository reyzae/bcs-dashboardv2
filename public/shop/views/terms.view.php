<?php
$page_title = $page_title ?? 'Syarat & Ketentuan';
include __DIR__ . '/layout/header.php';
?>

<div class="content">
    <div class="policy-container">
        <div class="policy-grid">
            <section class="policy-section">
                <h3>Definisi</h3>
                <ul>
                    <li>“Kami” merujuk pada penyelenggara toko Bytebalok.</li>
                    <li>“Anda” merujuk pada pengguna/pelanggan layanan.</li>
                    <li>“Pesanan” adalah transaksi pembelian yang dibuat melalui situs.</li>
                </ul>
            </section>

            <section class="policy-section">
                <h3>Pemesanan</h3>
                <ul>
                    <li>Pesanan dianggap sah setelah Anda menyelesaikan proses checkout.</li>
                    <li>Ketersediaan produk mengikuti stok aktual dan dapat berubah.</li>
                    <li>Kami berhak menolak atau membatalkan pesanan dengan alasan yang wajar.</li>
                </ul>
            </section>

            <section class="policy-section">
                <h3>Harga & Pembayaran</h3>
                <ul>
                    <li>Harga dapat berubah sewaktu-waktu. Harga final mengikuti saat checkout.</li>
                    <li>Metode pembayaran: QRIS dan Transfer Bank sesuai instruksi.</li>
                    <li>Pesanan diproses setelah pembayaran terkonfirmasi.</li>
                </ul>
            </section>

            <section class="policy-section">
                <h3>Pengiriman & Pengambilan</h3>
                <ul>
                    <li>Pengiriman/pengambilan mengikuti opsi yang tersedia pada checkout.</li>
                    <li>Estimasi waktu proses dapat bervariasi tergantung antrian dan ketersediaan.</li>
                </ul>
            </section>

            <section class="policy-section">
                <h3>Pembatalan & Pengembalian</h3>
                <ul>
                    <li>Pembatalan dapat dilakukan sebelum pesanan diproses, sesuai kebijakan.</li>
                    <li>Pengembalian dana mengikuti hasil verifikasi dan metode pembayaran.</li>
                </ul>
            </section>

            <section class="policy-section">
                <h3>Tanggung Jawab</h3>
                <ul>
                    <li>Kami bertanggung jawab atas kualitas produk sesuai standar kami.</li>
                    <li>Tanggung jawab kami dibatasi pada nilai pesanan terkait, sejauh diizinkan hukum.</li>
                </ul>
            </section>

            <section class="policy-section">
                <h3>Perubahan Ketentuan</h3>
                <p>Ketentuan ini dapat diperbarui dari waktu ke waktu. Perubahan material akan diinformasikan melalui situs.</p>
            </section>

            <aside class="policy-side">
                <div class="card">
                    <div class="card-body">
                        <h4>Bantuan</h4>
                        <div class="policy-links">
                            <a class="btn" href="/shop/contact.php"><i class="fas fa-headset"></i> Kontak</a>
                            <a class="btn" href="/shop/order-status.php"><i class="fas fa-search"></i> Lacak Pesanan</a>
                            <a class="btn" href="/shop/faq.php"><i class="fas fa-question-circle"></i> FAQ</a>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>