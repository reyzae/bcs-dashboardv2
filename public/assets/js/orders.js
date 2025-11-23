/**
 * Orders Page - Manage incoming orders and statuses
 */

const ensureOrderAnimStyles = () => {
    if (document.getElementById('orderAnimStyles')) return;
    const style = document.createElement('style');
    style.id = 'orderAnimStyles';
    style.textContent = `.accept-pulse{animation:acceptPulse .8s ease-out}.badge-success.blink{animation:badgeBlink 1.2s ease-out}.action-buttons .btn{position:relative}@keyframes acceptPulse{0%{transform:scale(.95);box-shadow:0 0 0 0 rgba(16,185,129,0)}50%{transform:scale(1.05);box-shadow:0 0 0 8px rgba(16,185,129,.18)}100%{transform:scale(1);box-shadow:0 0 0 0 rgba(16,185,129,0)}}@keyframes badgeBlink{0%{transform:scale(.92)}40%{transform:scale(1.06)}100%{transform:scale(1)}}`;
    document.head.appendChild(style);
}

class OrdersPage {
    constructor() {
        this.activeStatus = 'pending';
        this.pollIntervalMs = 5000; // 5s for more real-time
        this.poller = null;
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadCounts();
        this.loadOrders(this.activeStatus);
        this.startPolling();
    }

    bindEvents() {
        // Tabs
        const tabs = [
            { id: 'tabPending', status: 'pending' },
            { id: 'tabProcessing', status: 'processing' },
            { id: 'tabCompleted', status: 'completed' },
        ];
        tabs.forEach(tab => {
            const el = document.getElementById(tab.id);
            if (el) {
                el.addEventListener('click', () => {
                    // Update active status
                    this.activeStatus = tab.status;
                    const label = document.getElementById('activeStatusLabel');
                    if (label) label.textContent = tab.status;

                    // Toggle active class on tabs for visual feedback
                    const allTabButtons = document.querySelectorAll('.status-tab-btn');
                    allTabButtons.forEach(btn => btn.classList.remove('active'));
                    el.classList.add('active');

                    // Reload orders for selected tab
                    this.loadOrders(tab.status);
                });
            }
        });

        // Refresh
        const refreshBtn = document.getElementById('refreshOrdersBtn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                this.loadCounts();
                this.loadOrders(this.activeStatus);
                app.showToast('Orders refreshed', 'info');
            });
        }
    }

    startPolling() {
        if (this.poller) clearInterval(this.poller);
        this.poller = setInterval(() => {
            this.loadCounts();
            this.loadOrders(this.activeStatus, true);
        }, this.pollIntervalMs);
    }

    async loadCounts() {
        try {
            const pending = await this.fetchCount('pending');
            const processing = await this.fetchCount('processing');
            const completed = await this.fetchCount('completed');

            const pendingEl = document.getElementById('countPending');
            const processingEl = document.getElementById('countProcessing');
            const completedEl = document.getElementById('countCompleted');
            if (pendingEl) pendingEl.textContent = pending;
            if (processingEl) processingEl.textContent = processing;
            if (completedEl) completedEl.textContent = completed;
        } catch (e) {
            console.warn('Failed to load counts', e);
        }
    }

    async fetchCount(status) {
        const resp = await app.apiCall(`../api.php?controller=order&action=list&status=${status}&limit=1`);
        if (resp && resp.success && resp.data && resp.data.pagination) {
            return resp.data.pagination.total || 0;
        }
        return 0;
    }

    async loadOrders(status, silent = false) {
        const tbody = document.getElementById('ordersTableBody');
        if (!silent && tbody) {
            tbody.innerHTML = `<tr><td colspan="8" style="text-align:center; padding: 1rem; color: #6b7280;">Memuat pesanan...</td></tr>`;
        }
        try {
            const resp = await app.apiCall(`../api.php?controller=order&action=list&status=${status}&limit=50`);
            if (resp.success) {
                this.renderTable(resp.data.orders || []);
            } else {
                throw new Error('API error');
            }
        } catch (err) {
            console.error('Failed to load orders', err);
            if (tbody) {
                tbody.innerHTML = `<tr><td colspan="8" style="text-align:center; padding: 1rem; color: #ef4444;">Gagal memuat pesanan</td></tr>`;
            }
        }
    }

    renderTable(orders) {
        const tbody = document.getElementById('ordersTableBody');
        if (!tbody) return;
        if (!orders || orders.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8" style="text-align:center; padding: 1rem; color: #6b7280;">Tidak ada pesanan</td></tr>`;
            return;
        }

        const rows = orders.map(order => {
            const itemsCount = (order.items_count ?? order.item_count ?? order.items_total ?? '-');
            const total = app.formatCurrency(order.total_amount || 0);
            const placed = app.formatDateTime(order.created_at || '');
            const paymentMain = this.paymentBadge(order.payment_status_resolved || order.payment_status);
            const verifyBadge = (order.verification_requested === 1 || order.verification_requested === true)
                ? ' <span class="badge badge-info" title="Pembeli meminta verifikasi"><i class="fas fa-shield-alt"></i> Verify Req</span>'
                : '';
            const payment = `${paymentMain}${verifyBadge}`;
            const statusBadge = this.orderStatusBadge(order.order_status);

            return `
                <tr>
                    <td><strong>${order.order_number || ''}</strong></td>
                    <td>
                        ${order.customer_name || '-'}
                        <div style="color:#6b7280; font-size: 0.8rem;">${order.customer_phone || ''}</div>
                    </td>
                    <td>${itemsCount}</td>
                    <td>${total}</td>
                    <td>${payment}</td>
                    <td>${statusBadge}</td>
                    <td>${placed}</td>
                    <td>
                        ${this.actionButtons(order)}
                    </td>
                </tr>
            `;
        }).join('');
        tbody.innerHTML = rows;
        const table = document.getElementById('ordersTable');
        if (table) { table.classList.add('table-refresh'); setTimeout(()=>table.classList.remove('table-refresh'), 240); }

        // Bind action buttons
        tbody.querySelectorAll('[data-action]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const id = parseInt(btn.getAttribute('data-id'), 10);
                const action = btn.getAttribute('data-action');
                const orderNumber = btn.getAttribute('data-order-number');
                if (action === 'accept-payment') {
                    this.handleAcceptPayment(orderNumber, btn);
                } else {
                    this.handleAction(id, action);
                }
            });
        });
    }

    actionButtons(order) {
        const id = order.id;
        const status = (order.order_status || '').toLowerCase();
        const paymentStatus = ((order.payment_status_resolved || order.payment_status || '')).toLowerCase();
        const btn = (icon, label, action, color = 'primary') => {
            return `<button class="btn btn-${color} btn-sm" data-id="${id}" data-action="${action}"><i class="fas fa-${icon}"></i> ${label}</button>`;
        };

        let content = '';
        if (status === 'pending') {
            const canStart = (paymentStatus === 'paid' || paymentStatus === 'success');
            const startBtn = canStart
                ? btn('play', 'Mulai', 'processing', 'primary')
                : `<button class="btn btn-secondary btn-sm" data-id="${id}" data-action="processing" disabled title="Menunggu Payment Accepted"><i class="fas fa-play"></i> Mulai</button>`;
            content = startBtn + btn('times', 'Batal', 'cancelled', 'danger');
        } else if (status === 'processing') {
            content = btn('check-circle', 'Selesai', 'completed', 'success') + btn('ban', 'Batal', 'cancelled', 'danger');
        } else if (status === 'completed') {
            content = `<span class="badge badge-success">Selesai</span>`;
        } else if (status === 'cancelled') {
            content = `<span class="badge badge-danger">Dibatalkan</span>`;
        } else {
            content = `<span class="badge badge-secondary">${status}</span>`;
        }
        // Payment actions: hanya tampil jika pembeli sudah klik verifikasi
        const verReq = (order.verification_requested === 1 || order.verification_requested === true);
        const allowAccept = (status === 'pending' && !(paymentStatus === 'paid' || paymentStatus === 'success'));
        if (allowAccept && verReq) {
            content += ` <button class="btn btn-success btn-sm" title="Verifikasi: Pembayaran sudah diterima" data-id="${id}" data-order-number="${order.order_number}" data-action="accept-payment"><i class="fas fa-check"></i> Payment Accepted</button>`;
        }
        return `<div class="action-buttons">${content}</div>`;
    }

    async handleAction(id, targetStatus) {
        try {
            const resp = await app.apiCall(`../api.php?controller=order&action=update-status&id=${id}`, {
                method: 'POST',
                body: JSON.stringify({ status: targetStatus }),
            });
            if (resp.success) {
                app.showToast('Status pesanan diperbarui', 'success');
                // Reload current tab
                this.loadCounts();
                this.loadOrders(this.activeStatus);
            } else {
                throw new Error(resp.message || 'Gagal memperbarui status');
            }
        } catch (err) {
            console.error('Update status failed', err);
            app.showToast('Gagal memperbarui status', 'error');
        }
    }

    paymentBadge(status) {
        const st = (status || '').toLowerCase();
        if (st === 'paid' || st === 'success') return '<span class="badge badge-success">PAID</span>';
        if (st === 'pending') return '<span class="badge badge-warning">PENDING</span>';
        if (st === 'verification_requested') return '<span class="badge badge-info">VERIFY REQ</span>';
        if (st === 'failed') return '<span class="badge badge-danger">FAILED</span>';
        return `<span class="badge badge-secondary">${status || '-'}</span>`;
    }

    orderStatusBadge(status) {
        const st = (status || '').toLowerCase();
        if (st === 'pending') return '<span class="badge badge-warning">WAITING</span>';
        if (st === 'processing') return '<span class="badge badge-info">IN PROGRESS</span>';
        if (st === 'ready') return '<span class="badge badge-primary">READY</span>';
        if (st === 'completed') return '<span class="badge badge-success">COMPLETED</span>';
        if (st === 'cancelled') return '<span class="badge badge-danger">CANCELLED</span>';
        return `<span class="badge badge-secondary">${status || '-'}</span>`;
    }

    async handleAcceptPayment(orderNumber, buttonEl) {
        if (!orderNumber) return;
        try {
            const original = buttonEl.innerHTML;
            buttonEl.disabled = true;
            buttonEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
            buttonEl.style.transition = 'transform 240ms ease';
            buttonEl.style.transform = 'scale(0.96)';
            const resp = await app.apiCall(`../api.php?controller=payment&action=manualUpdate`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_number: orderNumber, confirm_paid: true })
            });
            if (resp.success) {
                app.showToast('Pembayaran diterima', 'success');
                if (window.app && app.showToast) app.showToast('Pembayaran dikonfirmasi, klik Mulai untuk memproses', 'info');
                ensureOrderAnimStyles();
                buttonEl.innerHTML = '<i class="fas fa-check" style="color:#10b981"></i> Payment Accepted';
                buttonEl.classList.add('accept-pulse');
                setTimeout(()=>{ buttonEl.style.transform = 'scale(1)'; }, 120);
                // Enable tombol Mulai pada baris ini tanpa memindahkan status
                const id = parseInt(buttonEl.getAttribute('data-id'), 10);
                const row = buttonEl.closest('tr');
                if (row) {
                    const startBtn = row.querySelector(`button[data-action="processing"][data-id="${id}"]`);
                    if (startBtn) {
                        startBtn.disabled = false;
                        startBtn.className = startBtn.className.replace('btn-secondary', 'btn-primary');
                        startBtn.title = 'Mulai';
                    }
                    // Update badge Payment menjadi PAID
                    const paymentCell = row.children && row.children[4];
                    if (paymentCell) {
                        paymentCell.innerHTML = '<span class="badge badge-success blink">PAID</span>';
                    }
                }
            } else {
                throw new Error(resp.message || 'Gagal mengonfirmasi pembayaran');
            }
        } catch (err) {
            console.error('Accept payment failed', err);
            app.showToast('Gagal mengonfirmasi pembayaran', 'error');
        } finally {
            if (buttonEl) {
                buttonEl.disabled = false;
                buttonEl.innerHTML = '<i class="fas fa-check" style="color:#10b981"></i> Payment Accepted';
            }
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.ordersPage = new OrdersPage();
});
