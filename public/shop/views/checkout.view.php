<?php
$page_title = 'Checkout';
include __DIR__ . '/layout/header.php';
?>

<div class="main-content">
        <!-- Grid dua kolom: form di kiri, ringkasan di kanan -->
        <div class="checkout-page">
            <!-- Kiri: Form Checkout -->
            <section class="checkout-form-section" id="checkoutFormSection">
                <!-- Informasi Pelanggan -->
                <div class="form-section">
                    <h3><i class="fas fa-user"></i> Customer Information</h3>
                    <form id="checkoutForm">
                        <div class="form-group">
                            <label for="customer_name">Full Name *</label>
                            <input id="customer_name" name="customer_name" type="text" placeholder="Nama lengkap" required />
                        </div>
                        <div class="form-group">
                            <label for="customer_email">Email (Optional)</label>
                            <input id="customer_email" name="customer_email" type="email" placeholder="email@domain.com" />
                        </div>
                        <div class="form-group">
                            <label for="customer_phone">Phone *</label>
                            <input id="customer_phone" name="customer_phone" type="tel" placeholder="08xxxxxxxxxx" required />
                        </div>

                        <!-- Alamat Pengiriman (opsional) -->
                        <div class="form-section">
                            <h3><i class="fas fa-map-marker-alt"></i> Shipping Address (Optional)</h3>
                            <div class="form-group">
                                <label for="customer_address">Full Address (Optional)</label>
                                <textarea id="customer_address" name="customer_address" rows="3" placeholder="Nama jalan, patokan, kota"></textarea>
                            </div>
                        </div>

                        <!-- Metode Pembayaran -->
                        <div class="form-section">
                            <h3><i class="fas fa-credit-card"></i> Payment Method</h3>
                            <div class="form-group">
                                <div class="payment-methods">
                                    <label class="payment-method-option">
                                        <input type="radio" name="payment_method" value="qris" checked />
                                        <div class="payment-method-card">
                                            <i class="fas fa-qrcode" style="color: var(--primary-color);"></i>
                                            <div>
                                                <strong>QRIS</strong>
                                                <span>Scan QR untuk bayar</span>
                                            </div>
                                        </div>
                                    </label>
                                    <label class="payment-method-option">
                                        <input type="radio" name="payment_method" value="transfer" />
                                        <div class="payment-method-card">
                                            <i class="fas fa-university" style="color: var(--primary-color);"></i>
                                            <div>
                                                <strong>Bank Transfer</strong>
                                                <span>Transfer ke rekening kami</span>
                                            </div>
                                        </div>
                                    </label>
                                </div>
</div>

                        <!-- Catatan Order -->
                        <div class="form-section">
                            <h3><i class="fas fa-sticky-note"></i> Order Notes (Optional)</h3>
                            <div class="form-group">
                                <label for="notes" class="visually-hidden">Ada instruksi khusus?</label>
                                <textarea id="notes" name="notes" rows="3" placeholder="Ada instruksi khusus?"></textarea>
                            </div>
                        </div>

                        <!-- Submit -->
                        <button class="btn btn-primary" id="placeOrderBtn" type="submit">
                            <i class="fas fa-shopping-bag"></i> Place Order
                        </button>
                    </form>
                </div>
            </section>

            <!-- Kanan: Ringkasan Pesanan -->
            <aside class="checkout-summary-section">
                <div class="checkout-summary-card">
                    <h3>Order Summary</h3>

                    <!-- Daftar item dari cart -->
                    <div id="checkoutItems"></div>

                    <div class="summary-divider"></div>
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span id="summarySubtotal">Rp 0</span>
                    </div>
                    <div class="summary-row">
                        <span id="summaryTaxLabel">Tax (Inactive)</span>
                        <span id="summaryTax">Rp 0</span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping</span>
                        <span id="summaryShipping">Rp 0</span>
                    </div>

                    <div class="summary-divider"></div>
                    <div class="summary-row summary-total">
                        <span>Total</span>
                        <span id="summaryTotal">Rp 0</span>
                    </div>
                </div>
            </aside>
        </div>

        <!-- Section Pembayaran (muncul setelah order berhasil dibuat) -->
        <section class="payment-section" id="paymentSection" style="display:none;">
            <div class="payment-header">
                <i class="fas fa-check-circle success-icon"></i>
                <h2>Order Created</h2>
                <p>Order Number: <strong id="orderNumber">-</strong></p>
            </div>

            <!-- Metode: QRIS -->
            <div id="qrisPayment" class="payment-qris" style="display:none;">
                <h3><i class="fas fa-qrcode"></i> Pay with QRIS</h3>
                <div class="qr-code-container">
                    <img id="qrCodeImage" src="../assets/img/qris-gopay.svg" alt="QRIS Code" />
                </div>
                <div class="payment-amount">Amount: <strong id="paymentAmount">Rp 0</strong></div>
                <div class="payment-expires" id="qrExpiry">-</div>

                <div class="payment-status" id="paymentStatus">
                    <i class="fas fa-info-circle"></i>
                    <p>Silakan selesaikan pembayaran. Status pesanan akan diperbarui otomatis. Jika sudah bayar, klik &lsquo;Minta verifikasi kasir&rsquo;.</p>
                </div>

                <div class="payment-actions">
                    <button class="btn btn-secondary" id="manualPaidBtnQr">
                        <i class="fas fa-shield-alt"></i> Minta verifikasi kasir
                    </button>
                    <button class="btn btn-danger" id="cancelOrderBtnQr"><i class="fas fa-ban"></i> Batalkan Pesanan</button>
                    <button class="btn" id="copyOrderBtnQr"><i class="fas fa-copy"></i> Copy Order Number</button>
                    <button class="btn" id="shareWhatsAppBtnQr"><i class="fab fa-whatsapp"></i> Share via WhatsApp</button>
                    <a class="btn btn-primary" id="trackOrderLinkQr" href="#"><i class="fas fa-search"></i> Lihat status order</a>
                </div>
            </div>

            <!-- Metode: Transfer -->
            <div id="transferPayment" class="payment-transfer" style="display:none;">
                <h3><i class="fas fa-university"></i> Bank Transfer</h3>
                <div class="payment-amount">Amount: <strong id="transferAmount">Rp 0</strong></div>

                <div class="bank-details">
                    <div class="bank-info">
                        <p>Bank: <strong id="bankName">-</strong></p>
                        <p>No. Rekening: <strong id="accountNumber">-</strong></p>
                        <p>Atas Nama: <strong id="accountName">-</strong></p>
                        <p>Virtual Account: <strong id="virtualAccount">-</strong></p>
                        <p>Reference: <strong id="referenceNumber">-</strong></p>
                    </div>
                    <p id="transferInstructions" class="payment-note">Ikuti instruksi pembayaran yang diberikan.</p>
                </div>

                <div class="payment-expires" id="transferExpiry">-</div>

                <div class="payment-status" id="paymentStatusTransfer">
                    <i class="fas fa-info-circle"></i>
                    <p>Jika sudah transfer, klik tombol verifikasi untuk memberi tahu kami.</p>
                </div>

                <div class="payment-actions">
                    <button class="btn btn-secondary" id="manualPaidBtnTransfer">
                        <i class="fas fa-shield-alt"></i> Minta verifikasi kasir
                    </button>
                    <button class="btn btn-danger" id="cancelOrderBtnTransfer"><i class="fas fa-ban"></i> Batalkan Pesanan</button>
                    <button class="btn" id="copyOrderBtnTransfer"><i class="fas fa-copy"></i> Copy Order Number</button>
                    <button class="btn" id="shareWhatsAppBtnTransfer"><i class="fab fa-whatsapp"></i> Share via WhatsApp</button>
                    <a class="btn btn-primary" id="trackOrderLinkTf" href="#"><i class="fas fa-search"></i> Lihat status order</a>
                </div>
            </div>
        </section>

        <!-- Order Cancelled Section -->
        <section class="payment-section" id="orderCancelled" style="display:none;">
            <div class="payment-header">
                <i class="fas fa-ban" style="color: var(--accent-color); font-size: 2rem;"></i>
                <h2>Order Dibatalkan</h2>
                <p>Order Number: <strong id="cancelledOrderNumber">-</strong></p>
            </div>
            <div class="payment-status">
                <i class="fas fa-info-circle"></i>
                <p id="cancelledReason">Pesanan telah dibatalkan. Jika masih ingin melanjutkan, silakan buat order baru.</p>
            </div>
            <div class="payment-actions">
                <a class="btn btn-primary" href="index.php"><i class="fas fa-shopping-bag"></i> Belanja Lagi</a>
                <a class="btn" id="trackOrderLinkCancelled" href="#"><i class="fas fa-search"></i> Lihat status order</a>
            </div>
        </section>
    </div>
    
    <!-- Sedikit ide bagus tambahan: CTA share WA & copy nomor order akan aktif ketika order dibuat
         Implementasinya bisa ditambahkan di JS kemudian jika diinginkan -->
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>

<script>
// Inisialisasi halaman checkout
document.addEventListener('DOMContentLoaded', () => {
    try { ShopCheckout.initialize(); } catch (e) {}
});
</script>