<?php
$page_title = 'Status Pesanan';
include __DIR__ . '/layout/header.php';
?>

<style>
/* Subtle animations when status changes */
.status-change { animation: flashCard 0.8s ease-in-out; }
@keyframes flashCard {
  0%   { box-shadow: 0 0 0 0 rgba(99,102,241,0.0); transform: scale(1); }
  50%  { box-shadow: 0 0 0 6px rgba(99,102,241,0.12); transform: scale(1.02); }
  100% { box-shadow: 0 0 0 0 rgba(99,102,241,0.0); transform: scale(1); }
}
.badge-bounce { animation: bounceBadge 0.6s ease-out; }
@keyframes bounceBadge {
  0%   { transform: scale(0.95); }
  40%  { transform: scale(1.08); }
  100% { transform: scale(1); }
}
.highlight-green { animation: flashGreen 1s ease-in-out; }
@keyframes flashGreen { 0% { background-color:#dcfce7; } 100% { background-color: inherit; } }
.highlight-yellow { animation: flashYellow 1s ease-in-out; }
@keyframes flashYellow { 0% { background-color:#fef3c7; } 100% { background-color: inherit; } }
</style>

<div class="content">
    <div class="card">
        <div class="card-body">
            <?php $exampleOrder = 'ORD' . date('Ymd') . '0001'; ?>
            <form method="get" action="/shop/order-status.php" class="track-form" id="trackFormSection">
                <div class="input-with-actions">
                    <input class="form-control" type="text" name="code" id="trackOrderNumber" placeholder="Contoh: <?= htmlspecialchars($exampleOrder) ?>" value="<?= htmlspecialchars($code ?? '') ?>" pattern="^ORD[0-9]{8}[0-9]{4}$" title="Format: ORDYYYYMMDDNNNN" required />
                    <button class="input-action paste" type="button" id="pasteCodeBtn" title="Tempel"><i class="fas fa-clipboard"></i></button>
                    <button class="input-action clear" type="button" id="clearCodeBtn" title="Bersihkan"><i class="fas fa-times"></i></button>
                </div>
                <button class="btn btn-primary track-submit" type="submit" id="trackOrderBtn"><span class="btn-label">Cari</span></button>
            </form>
            <div class="form-hint">Kode ada di invoice atau WhatsApp.</div>
            <div class="form-error" id="orderCodeError" style="display:none;">Format kode salah. Gunakan ORDYYYYMMDDNNNN.</div>
        </div>
    </div>

    <?php if (!empty($order)): ?>
        <div class="card" id="orderDetailsSection">
            <div class="card-body">
                <?php $badgeClass = function($status){ $s = strtolower(trim($status)); if(in_array($s,['paid','success','paid_success','completed'])) return 'badge-success'; if(in_array($s,['pending','process','processing','awaiting','unpaid','in_progress'])) return 'badge-warning'; if(in_array($s,['failed','cancelled','canceled','void'])) return 'badge-danger'; return 'badge-secondary'; }; ?>
                <h5>Pesanan #<span id="detailOrderNumber"><?= htmlspecialchars($order['order_number']) ?></span></h5>
                <p class="text-muted">Status Pembayaran: <span id="paymentStatusBadge" class="badge <?= $badgeClass($order['payment_status']) ?>"><?= htmlspecialchars($order['payment_status']) ?></span> • Status Pesanan: <span id="orderStatusBadge" class="badge <?= $badgeClass($order['order_status']) ?>"><?= htmlspecialchars($order['order_status']) ?></span></p>
                <div id="orderItemsList">
                <?php foreach (($order['items'] ?? []) as $it): ?>
                    <div class="summary-row">
                        <span><?= htmlspecialchars($it['product_name']) ?> x <?= intval($it['quantity']) ?></span>
                        <span><?= formatCurrency($it['total_price']) ?></span>
                    </div>
                <?php endforeach; ?>
                </div>
                <div class="summary-divider"></div>
                <div class="summary-row summary-total"><span>Total</span><span id="orderTotal"><?= formatCurrency($order['total_amount']) ?></span></div>
            </div>
        </div>
    <?php elseif (!empty($code)): ?>
        <div class="card">
            <div class="card-body">
                <p>Pesanan dengan kode itu tidak ditemukan.</p>
                <div class="status-legend">
                    <div class="legend-item"><span class="badge badge-success">Selesai</span><span>Berhasil dibayar/diproses</span></div>
                    <div class="legend-item"><span class="badge badge-warning">Diproses</span><span>Menunggu atau sedang diproses</span></div>
                    <div class="legend-item"><span class="badge badge-danger">Dibatalkan</span><span>Pesanan dibatalkan atau gagal</span></div>
                </div>
                <div style="margin-top:0.75rem;">
                    <a class="btn btn-primary" href="index.php">Belanja Lagi</a>
                    <a class="btn" href="https://wa.me/6281234567890" target="_blank">Kontak CS</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('trackOrderNumber');
    const pasteBtn = document.getElementById('pasteCodeBtn');
    const clearBtn = document.getElementById('clearCodeBtn');
    const submitBtn = document.getElementById('trackOrderBtn');
    const errorEl = document.getElementById('orderCodeError');

    if (pasteBtn && input) {
        pasteBtn.addEventListener('click', async () => {
            try {
                const text = await navigator.clipboard.readText();
                if (text) { input.value = text.trim(); input.focus(); }
            } catch (_) {}
        });
    }
    if (clearBtn && input) {
        clearBtn.addEventListener('click', () => { input.value = ''; input.focus(); });
    }
    const form = document.getElementById('trackFormSection');
    if (form && submitBtn) {
        form.addEventListener('submit', (e) => {
            const val = (input?.value || '').trim().toUpperCase();
            if (!/^ORD\d{8}\d{4}$/.test(val)) {
                if (errorEl) errorEl.style.display = 'block';
                input?.focus();
                e.preventDefault();
                return;
            }
            if (errorEl) errorEl.style.display = 'none';
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span class="btn-label">Cari</span>';
        });
        if (input && errorEl) {
            input.addEventListener('input', () => {
                errorEl.style.display = 'none';
            });
        }
    }
});

// Auto-refresh status untuk order tracking
document.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    const orderNumber = params.get('code') || document.getElementById('trackOrderNumber')?.value || '';
    if (!orderNumber) return;

    const statusClass = (status) => {
        const s = String(status || '').toLowerCase();
        if (['completed','success','paid','paid_success'].includes(s)) return 'badge-success';
        if (['processing','in_progress','pending','awaiting','unpaid','process'].includes(s)) return 'badge-warning';
        if (['failed','cancelled','canceled','void'].includes(s)) return 'badge-danger';
        return 'badge-secondary';
    };

    let prevOrderStatus = null;
    let prevPaymentStatus = null;

    const render = (order) => {
        const paymentBadge = document.getElementById('paymentStatusBadge');
        const orderBadge = document.getElementById('orderStatusBadge');
        const totalEl = document.getElementById('orderTotal');
        const itemsEl = document.getElementById('orderItemsList');
        const numEl = document.getElementById('detailOrderNumber');
        const container = document.getElementById('orderDetailsSection');
        const newPayment = (order.payment_status || '').toLowerCase();
        const newOrder = (order.order_status || '').toLowerCase();
        const changedPayment = prevPaymentStatus !== null && prevPaymentStatus !== newPayment;
        const changedOrder = prevOrderStatus !== null && prevOrderStatus !== newOrder;
        if (paymentBadge) {
            paymentBadge.textContent = newPayment;
            paymentBadge.className = 'badge ' + statusClass(order.payment_status);
        }
        if (orderBadge) {
            orderBadge.textContent = newOrder || '';
            orderBadge.className = 'badge ' + statusClass(order.order_status);
        }
        if (totalEl) {
            totalEl.textContent = new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',minimumFractionDigits:0,maximumFractionDigits:0}).format(order.total_amount || 0);
        }
        if (numEl) { numEl.textContent = order.order_number || orderNumber; }
        if (itemsEl && Array.isArray(order.items)) {
            itemsEl.innerHTML = order.items.map(it => `
                <div class="summary-row">
                    <span>${(it.product_name || 'Item')} x ${parseInt(it.quantity||1)}</span>
                    <span>${new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',minimumFractionDigits:0,maximumFractionDigits:0}).format((it.total_price||0))}</span>
                </div>
            `).join('');
        }

        // Animasi kecil saat status berubah
        if (changedPayment && paymentBadge) {
            paymentBadge.classList.add('badge-bounce');
            setTimeout(() => paymentBadge.classList.remove('badge-bounce'), 600);
        }
        if (changedOrder) {
            if (orderBadge) {
                orderBadge.classList.add('badge-bounce');
                setTimeout(() => orderBadge.classList.remove('badge-bounce'), 600);
            }
            if (container) {
                const isSuccess = statusClass(order.order_status) === 'badge-success';
                container.classList.add('status-change');
                container.classList.add(isSuccess ? 'highlight-green' : 'highlight-yellow');
                setTimeout(() => {
                    container.classList.remove('status-change','highlight-green','highlight-yellow');
                }, 1000);
            }
        }

        prevPaymentStatus = newPayment;
        prevOrderStatus = newOrder;
    };

    const setLoading = (on) => {
        const btn = document.getElementById('trackOrderBtn');
        if (!btn) return;
        if (on) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span class="btn-label">Cari</span>';
        } else {
            btn.disabled = false;
            btn.innerHTML = '<span class="btn-label">Cari</span>';
        }
    };

    const fetchAndUpdate = async () => {
        try {
            setLoading(true);
            const res = await fetch(`../api.php?controller=order&action=get-by-number&order_number=${encodeURIComponent(orderNumber)}`);
            const json = await res.json();
            if (json && json.success && json.data) {
                render(json.data);
            }
            setLoading(false);
        } catch (e) { /* silent */ }
    };

    // Initial render + polling setiap 5 detik
    fetchAndUpdate();
    setInterval(fetchAndUpdate, 5000);
});
</script>