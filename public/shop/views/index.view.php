<?php
// Expect $products (array) and $cart_count (int)
$page_title = 'Belanja';
include __DIR__ . '/layout/header.php';
?>

<!-- Listing Produk -->
<div class="content">
    <!-- Skeleton Loader (ditampilkan saat ShopCatalog.initialize) -->
    <div id="skeletonGrid" class="skeleton-grid" style="display:none; margin-bottom:1rem;">
        <div class="skeleton-card">
            <div class="skeleton-image"></div>
            <div class="skeleton-text skeleton-title"></div>
            <div class="skeleton-text skeleton-subtitle"></div>
            <div class="skeleton-text skeleton-price"></div>
        </div>
        <div class="skeleton-card">
            <div class="skeleton-image"></div>
            <div class="skeleton-text skeleton-title"></div>
            <div class="skeleton-text skeleton-subtitle"></div>
            <div class="skeleton-text skeleton-price"></div>
        </div>
        <div class="skeleton-card">
            <div class="skeleton-image"></div>
            <div class="skeleton-text skeleton-title"></div>
            <div class="skeleton-text skeleton-subtitle"></div>
            <div class="skeleton-text skeleton-price"></div>
        </div>
    </div>
    <?php if (!empty($query ?? '')): ?>
        <p class="text-muted">Hasil untuk "<?= htmlspecialchars($query) ?>"</p>
    <?php endif; ?>

    <?php if (empty($products)): ?>
        <div class="card">
            <div class="card-body">
                <p>Tidak ada produk ditemukan.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="products-grid">
            <?php foreach ($products as $p): ?>
                <?php
                    $img = $p['image'] ?? '';
                    $hasImage = !empty($img);
                    $isRel = $hasImage && strpos($img, 'uploads/') !== false;
                    $imgSrc = $hasImage ? ('/' . ($isRel ? $img : ('uploads/products/' . $img))) : '';
                ?>
                <div class="product-card<?= (!$hasImage ? ' no-image-card' : '') ?><?= (intval($p['stock_quantity']) <= 0 ? ' out-of-stock-card' : '') ?>">
                    <div class="product-image-wrapper">
                        <div class="product-image">
                            <?php if ($hasImage): ?>
                                <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($p['name']) ?>" />
                            <?php else: ?>
                                <img src="/assets/img/product-placeholder.jpg" alt="Tidak ada gambar" />
                            <?php endif; ?>
                        </div>
                        <!-- Overlay Quick View -->
                        <div class="product-overlay">
                            <button class="btn-quick-view" onclick="event.stopPropagation(); ShopProduct.showProductModal(<?= intval($p['id']) ?>)" title="Quick View">
                                <i class="fas fa-eye"></i>
                                <span>Lihat Cepat</span>
                            </button>
                        </div>
                    </div>
                    <div class="product-content" style="padding: 12px 12px 16px;">
                        <div class="product-title" title="<?= htmlspecialchars($p['name']) ?>" style="font-weight:700; margin-bottom:6px;">
                            <?= htmlspecialchars($p['name']) ?>
                        </div>
                        <div class="product-actions-row">
                            <div class="product-price">
                                <?= formatCurrency($p['unit_price']) ?>
                            </div>
                            <form method="post" action="/shop/cart.php" class="add-to-cart-form">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>" />
                                <input type="hidden" name="action" value="add" />
                                <input type="hidden" name="product_id" value="<?= intval($p['id']) ?>" />
                                <?php $maxStock = max(0, intval($p['stock_quantity'])); ?>
                                <div class="action-pill">
                                    <button type="button" class="qty-btn" onclick="var i=this.parentElement.querySelector('input[name=qty]'); var v=Math.max(1,(parseInt(i.value)||1)-1); i.value=v;" <?= ($maxStock <= 0 ? 'disabled' : '') ?>>−</button>
                                    <input type="number" name="qty" value="1" min="1" max="<?= $maxStock ?>" <?= ($maxStock <= 0 ? 'disabled' : '') ?> />
                                    <button type="button" class="qty-btn" onclick="var i=this.parentElement.querySelector('input[name=qty]'); var m=parseInt(i.max||99); var v=Math.min(m,(parseInt(i.value)||1)+1); i.value=v;" <?= ($maxStock <= 0 ? 'disabled' : '') ?>>+</button>
                                    <button type="submit" class="btn btn-primary btn-sm btn-add-to-cart" data-product-id="<?= intval($p['id']) ?>" <?= ($maxStock <= 0 ? 'disabled' : '') ?>><i class="fas fa-cart-plus"></i></button>
                                </div>
                            </form>
                        </div>
                        <?php if (!empty($p['description'])): ?>
                            <?php
                                $desc = (string)($p['description'] ?? '');
                                if (function_exists('mb_strimwidth')) {
                                    $short = mb_strimwidth($desc, 0, 90, '...');
                                } else {
                                    $short = (strlen($desc) > 90) ? substr($desc, 0, 90) . '...' : $desc;
                                }
                            ?>
                            <p class="product-description-short">
                                <?= htmlspecialchars($short) ?>
                            </p>
                        <?php endif; ?>
                        <?php if (intval($p['stock_quantity']) <= 0): ?>
                            <span class="badge badge-danger" style="margin-top:0.5rem;">Stok Habis</span>
                        <?php elseif (intval($p['stock_quantity']) <= intval($p['min_stock_level'])): ?>
                            <span class="badge badge-warning" style="margin-top:0.5rem;">Stok Menipis</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>