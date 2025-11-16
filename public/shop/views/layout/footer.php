</main>
    <footer class="shop-footer">
        <div class="container">
            <div class="footer-bar">
                <div class="footer-left">&copy; <?= date('Y') ?> Bytebalok. Semua hak dilindungi.</div>
                <nav class="footer-links">
                    <a href="/shop/privacy.php">Kebijakan Privasi</a>
                    <a href="/shop/terms.php">Syarat & Ketentuan</a>
                    <a href="/shop/contact.php">Kontak</a>
                </nav>
            </div>
        </div>
    </footer>
    <!-- Product Detail Modal untuk Quick View -->
    <div class="modal" id="productModal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h3 id="modalProductName">Detail Produk</h3>
                <button class="modal-close" id="closeProductModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="product-detail-content">
                    <div class="product-image-section">
                        <img id="modalProductImage" src="" alt="Product Image">
                    </div>
                    <div class="product-info-section">
                        <div class="product-category" id="modalProductCategory"></div>
                        <h2 id="modalProductNameLarge"></h2>
                        <div class="product-price" id="modalProductPrice"></div>
                        <div class="product-stock" id="modalProductStock"></div>
                        <div class="product-description" id="modalProductDescription"></div>

                        <div class="quantity-selector">
                            <label>Quantity:</label>
                            <div class="quantity-controls">
                                <button class="quantity-btn" id="decreaseQuantity">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" id="modalQuantity" value="1" min="1" readonly>
                                <button class="quantity-btn" id="increaseQuantity">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>

                        <button class="btn btn-primary btn-large" id="addToCartBtn">
                            <i class="fas fa-shopping-cart"></i>
                            Tambah ke Keranjang
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Scripts modular tanpa ketergantungan jQuery/Bootstrap -->
    <script></script>
    <script src="/assets/js/shop.js"></script>
    </body>
    </html>