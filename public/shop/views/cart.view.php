<?php
$page_title = 'Keranjang';
include __DIR__ . '/layout/header.php';
?>

<div class="main-content">
    <div class="container">
        <div class="cart-page">
            <!-- Bagian daftar item -->
            <section class="cart-items-section">
                <h2>Shopping Cart</h2>

                <div id="cartItemsContainer"></div>

                <div class="cart-empty" id="cartEmpty" style="display:none;">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>Keranjang Anda kosong</h3>
                    <p>Tambahkan beberapa produk untuk mulai berbelanja!</p>
                    <a href="/shop/index.php" class="btn btn-primary">Lanjut Belanja</a>
                </div>
            </section>

            <!-- Ringkasan pesanan -->
            <aside class="cart-summary-section" id="cartSummary" style="display:none;">
                <div class="cart-summary-card">
                    <h3>Order Summary</h3>

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

                    <!-- Kode promo -->
                    <div class="discount-section" id="discountSection">
                        <div class="discount-toggle" id="discountToggle">
                            <i class="fas fa-tag"></i>
                            <span>Have a promo code?</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="discount-form" id="discountForm" style="display:none;">
                            <div class="input-group">
                                <input type="text" id="promoCode" placeholder="Masukkan kode promo" aria-label="Promo code">
                                <button class="btn btn-secondary" id="applyPromoBtn">
                                    <i class="fas fa-check"></i>
                                    Terapkan
                                </button>
                            </div>
                            <div class="promo-message" id="promoMessage"></div>
                        </div>
                    </div>

                    <div class="summary-row discount-row" id="discountRow" style="display:none;">
                        <span>
                            <i class="fas fa-tag"></i> Discount
                            <button class="remove-discount" id="removeDiscountBtn" aria-label="Remove discount">
                                <i class="fas fa-times"></i>
                            </button>
                        </span>
                        <span id="summaryDiscount" class="discount-amount">-Rp 0</span>
                    </div>

                    <div class="summary-divider"></div>

                    <div class="summary-row summary-total">
                        <span>Total</span>
                        <span id="summaryTotal">Rp 0</span>
                    </div>

                    <div class="summary-actions">
                        <button class="btn btn-primary btn-block" id="checkoutBtn">
                            <i class="fas fa-credit-card"></i>
                            Proceed to Checkout
                        </button>
                        <a href="/shop/index.php" class="continue-shopping">
                            <i class="fas fa-arrow-left"></i>
                            Continue Shopping
                        </a>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>

<script>
// Inisialisasi halaman cart agar interaktif
document.addEventListener('DOMContentLoaded', () => {
    try {
        ShopCart.loadCart();
        ShopCart.updateCartDisplay();
    } catch (e) {}
    try { PromoCodeManager.initialize(); } catch (e) {}
});
</script>