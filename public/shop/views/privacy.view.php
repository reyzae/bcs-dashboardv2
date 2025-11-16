<?php
$page_title = $page_title ?? 'Kebijakan Privasi';
include __DIR__ . '/layout/header.php';
?>

<div class="content">
    <div class="policy-container">
        <div class="policy-grid">
            <section class="policy-section">
                <h3>Ringkasan</h3>
                <p>Kami berkomitmen untuk melindungi privasi Anda. Data yang kami kumpulkan digunakan untuk memproses pesanan, memberikan dukungan, dan meningkatkan pengalaman belanja.</p>
                <ul>
                    <li>Data kontak digunakan untuk konfirmasi pesanan dan komunikasi layanan.</li>
                    <li>Data pesanan dipakai untuk pemenuhan, akuntansi, dan dukungan pelanggan.</li>
                    <li>Cookie fungsional membantu menjaga sesi, keranjang, dan preferensi Anda.</li>
                </ul>
            </section>

            <section class="policy-section">
                <h3>Data Yang Kami Kumpulkan</h3>
                <ul>
                    <li>Identitas: nama, email, nomor WhatsApp/telepon.</li>
                    <li>Pesanan: daftar produk, jumlah, harga, metode pembayaran.</li>
                    <li>Teknis: cookie dan informasi perangkat minimal untuk keperluan fungsional.</li>
                </ul>
            </section>

            <section class="policy-section">
                <h3>Penggunaan Data</h3>
                <ul>
                    <li>Memproses dan mengirimkan pesanan.</li>
                    <li>Memberi notifikasi status pembayaran dan pesanan.</li>
                    <li>Meningkatkan kualitas layanan, mencegah penyalahgunaan, dan audit internal.</li>
                </ul>
            </section>

            <section class="policy-section">
                <h3>Penyimpanan & Retensi</h3>
                <p>Data pesanan disimpan sesuai kebutuhan operasional dan ketentuan perpajakan. Data kontak disimpan untuk layanan purna jual dan dukungan. Anda dapat meminta penghapusan data tertentu sepanjang tidak bertentangan dengan kewajiban hukum.</p>
            </section>

            <section class="policy-section">
                <h3>Berbagi Data</h3>
                <p>Kami tidak menjual data Anda. Data dapat dibagikan dengan pihak ketiga yang mendukung operasional kami (misalnya pembayaran dan pengiriman) dengan kontrol dan perlindungan yang sesuai.</p>
            </section>

            <section class="policy-section">
                <h3>Hak Anda</h3>
                <ul>
                    <li>Mengakses, memperbarui, atau mengoreksi data pribadi.</li>
                    <li>Meminta penghapusan data yang tidak lagi diperlukan.</li>
                    <li>Mengajukan pertanyaan melalui halaman Kontak.</li>
                </ul>
            </section>

            <section class="policy-section">
                <h3>Keamanan</h3>
                <p>Kami menerapkan langkah pengamanan yang wajar untuk melindungi data Anda. Namun tidak ada metode transmisi atau penyimpanan yang sepenuhnya bebas risiko.</p>
            </section>

            <section class="policy-section">
                <h3>Cookie</h3>
                <p>Cookie digunakan untuk sesi, keranjang, preferensi, dan analitik internal yang terbatas. Anda dapat mengatur cookie melalui peramban.</p>
            </section>

            <section class="policy-section">
                <h3>Perubahan Kebijakan</h3>
                <p>Kebijakan ini dapat diperbarui dari waktu ke waktu. Perubahan material akan diinformasikan melalui situs.</p>
            </section>

            <aside class="policy-side">
                <div class="card">
                    <div class="card-body">
                        <h4>Butuh Bantuan?</h4>
                        <div class="policy-links">
                            <a class="btn" href="/shop/contact.php"><i class="fas fa-headset"></i> Kontak</a>
                            <a class="btn" href="/shop/order-status.php"><i class="fas fa-search"></i> Lacak Pesanan</a>
                            <a class="btn" href="/shop/index.php"><i class="fas fa-store"></i> Kembali Belanja</a>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>