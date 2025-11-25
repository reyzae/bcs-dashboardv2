<?php
/**
 * Receipt Display & Print Page
 * 
 * Displays transaction receipt with print functionality
 * Access: All authenticated users
 */

require_once '../bootstrap.php';
require_once '../../app/helpers/functions.php';

// Require authentication
requireAuth();

// Get transaction ID
$transactionId = $_GET['id'] ?? null;

if (!$transactionId) {
    header('Location: transactions.php');
    exit();
}

// Get user info
$user_name = $_SESSION['user_name'] ?? 'User';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #<?php echo htmlspecialchars($transactionId); ?> - Bytebalok</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../assets/img/favicon.ico">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f3f4f6;
            padding: 20px;
        }
        
        .receipt-container {
            max-width: 420px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .receipt-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        
        .receipt-header .company-logo {
            margin-bottom: 10px;
        }
        .receipt-header .company-logo .logo-img {
            height: 48px;
            width: auto;
            display: inline-block;
            vertical-align: middle;
        }
        
        .receipt-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
            color: #ffffff;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .receipt-header p {
            margin: 8px 0 0 0;
            opacity: 0.95;
            font-size: 14px;
        }
        
        .receipt-body {
            padding: 25px;
        }
        
        .receipt-info {
            background: #f9fafb;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .receipt-info div {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .receipt-info div:last-child {
            margin-bottom: 0;
        }
        
        .receipt-info div strong {
            color: #374151;
            font-weight: 600;
        }
        
        .receipt-info div span:last-child {
            color: #6b7280;
            text-align: right;
        }
        
        .section-title {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #9ca3af;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .receipt-items {
            margin-bottom: 20px;
        }
        
        .receipt-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px dashed #e5e7eb;
        }
        
        .receipt-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-name {
            font-weight: 600;
            color: #111827;
            margin-bottom: 4px;
        }
        
        .item-quantity {
            font-size: 13px;
            color: #6b7280;
        }
        
        .item-price {
            font-weight: 600;
            color: #111827;
            white-space: nowrap;
            margin-left: 15px;
            text-align: right;
        }
        
        .receipt-summary {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .summary-row span:last-child { text-align: right; min-width: 140px; display: inline-block; }
        
        .summary-row:last-child {
            margin-bottom: 0;
        }
        
        .summary-row.total {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
            padding-top: 15px;
            margin-top: 15px;
            border-top: 2px solid #d1d5db;
        }
        
        .payment-info {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .payment-info div {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: #065f46;
        }
        
        .payment-method {
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .receipt-footer {
            background: #f9fafb;
            padding: 25px;
            text-align: center;
            border-top: 2px dashed #d1d5db;
        }
        
        .receipt-footer p {
            margin: 0 0 8px 0;
            font-size: 14px;
            color: #6b7280;
        }
        
        .receipt-footer p:last-child {
            margin-bottom: 0;
        }
        
        .receipt-footer .thank-you {
            font-size: 16px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 12px;
        }
        
        :root { --primary-color: #16a34a; --primary-dark: #15803d; }

        .fab-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            z-index: 1000;
        }

        .fab-btn {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            transition: transform .2s ease, box-shadow .2s ease;
            color: #fff;
        }

        .fab-btn i { color: #fff; font-size: 18px; }

        .fab-btn:hover { transform: translateY(-2px); box-shadow: 0 12px 24px rgba(0,0,0,0.2); }

        .fab-print { background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%); }
        .fab-back { background: #374151; }
        .fab-whatsapp { background: #25D366; }
        .fab-btn.disabled { background: #9ca3af; cursor: not-allowed; pointer-events: none; }
        .fab-toggle { background: #10b981; font-weight: 700; }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(22, 163, 74, 0.35);
        }
        
        .btn-secondary {
            background: white;
            color: #374151;
            border: 1px solid #d1d5db;
        }
        
        .btn-secondary:hover {
            background: #f9fafb;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .loading-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .loading-spinner {
            font-size: 48px;
            color: var(--primary-color);
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .error-state {
            text-align: center;
            padding: 60px 20px;
            color: #dc2626;
        }
        
        .error-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .fab-container { display: none !important; }
            
            .receipt-container {
                box-shadow: none;
                max-width: 100%;
                border-radius: 0;
            }
            .receipt-header { background: #ffffff !important; color: #000 !important; }
            .receipt-header h1 { color: #000 !important; }
            .receipt-info, .receipt-summary, .payment-info { background: #ffffff !important; }
            .section-title { border-bottom-color: #000 !important; color: #000 !important; }
        }
        
        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            
            .receipt-container {
                border-radius: 8px;
            }
            
            .fab-container { bottom: 10px; right: 10px; }
            .fab-btn { width: 44px; height: 44px; }
            
            .btn {
                padding: 10px 16px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <!-- Floating CTAs -->
    <div class="fab-container">
        <a href="transactions.php" class="fab-btn fab-back" title="Back">
            <i class="fas fa-arrow-left"></i>
        </a>
        <button class="fab-btn fab-print" onclick="window.print()" title="Print Receipt" aria-label="Print Receipt">
            <i class="fas fa-print"></i>
        </button>
        <button id="waTargetToggle" class="fab-btn fab-toggle" title="WhatsApp target" aria-label="WhatsApp target">C</button>
        <a id="whatsappShareBtn" class="fab-btn fab-whatsapp" target="_blank" rel="noopener" title="Share to WhatsApp">
            <i class="fab fa-whatsapp"></i>
        </a>
    </div>
    
    <!-- Receipt Container -->
        <div class="receipt-container">
            <div class="receipt-header">
                <div class="company-logo">
                <img src="../assets/img/logo.svg" alt="Bytebalok" class="logo-img">
                </div>
                <h1>Bytebalok</h1>
                <p>Business Management System</p>
            </div>
        
        <div class="receipt-body" id="receiptBody">
            <!-- Loading State -->
            <div class="loading-state">
                <div class="loading-spinner">
                    <i class="fas fa-spinner"></i>
                </div>
                <p style="margin-top: 20px; color: #6b7280;">Loading transaction data...</p>
            </div>
        </div>
    </div>
    
    <!-- Receipt Template (Hidden) -->
    <template id="receiptTemplate">
        <div class="receipt-info">
            <div>
                <strong>Transaction #:</strong>
                <span id="transactionNumber"></span>
            </div>
            <div>
                <strong>Date:</strong>
                <span id="transactionDate"></span>
            </div>
            <div>
                <strong>Cashier:</strong>
                <span id="cashierName"></span>
            </div>
            <div>
                <strong>Customer:</strong>
                <span id="customerName"></span>
            </div>
        </div>
        
        <h3 class="section-title">Items</h3>
        <div class="receipt-items" id="receiptItems"></div>
        
        <div class="receipt-summary">
            <div class="summary-row">
                <span>Subtotal:</span>
                <span id="subtotal"></span>
            </div>
            <div class="summary-row">
                <span>Discount:</span>
                <span id="discount"></span>
            </div>
            <div class="summary-row">
                <span>Tax:</span>
                <span id="tax"></span>
            </div>
            <div class="summary-row total">
                <span>Total:</span>
                <span id="total"></span>
            </div>
        </div>
        
        <div class="payment-info" id="paymentInfo" style="display: none;">
            <div>
                <span>Payment Method:</span>
                <span class="payment-method" id="paymentMethod"></span>
            </div>
        </div>
    </template>
    
    <script>
        // Normalize number helper (handles strings like "28.000", "28,000", "Rp 28.000")
        const toNumber = (val) => {
            if (val === null || val === undefined) return 0;
            if (typeof val === 'number') return isFinite(val) ? val : 0;
            if (typeof val === 'string') {
                // Remove non-digit characters except dot/minus
                const cleaned = val
                    .replace(/Rp/gi, '')
                    .replace(/\s+/g, '')
                    .replace(/[,]/g, '.')
                    .replace(/[^0-9.-]/g, '');
                const num = Number(cleaned);
                return isFinite(num) ? num : 0;
            }
            try { return Number(val) || 0; } catch { return 0; }
        };

        // Format currency helper (always formats numbers reliably)
        const formatCurrency = (amount) => {
            const num = toNumber(amount);
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            }).format(num);
        };

        // Normalize phone number to WhatsApp format (Indonesia)
        const normalizePhoneToWa = (phone) => {
            try {
                let p = String(phone || '').trim();
                // Remove non-digit
                p = p.replace(/[^0-9]/g, '');
                if (!p) return '';
                // If starts with 62, keep
                if (p.startsWith('62')) return p;
                // If starts with 0, convert to 62
                if (p.startsWith('0')) return '62' + p.slice(1);
                // If starts with 8 (common local), prepend 62
                if (p.startsWith('8')) return '62' + p;
                // Fallback: ensure has country code
                return '62' + p;
            } catch { return ''; }
        };

        // Format date helper
        const formatDate = (dateString) => {
            const date = new Date(dateString);
            const options = {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            };
            const timeOptions = {
                hour: '2-digit',
                minute: '2-digit'
            };
            
            const datePart = date.toLocaleDateString('id-ID', options);
            const timePart = date.toLocaleTimeString('id-ID', timeOptions);
            
            // Gabungkan tanpa kata "pukul"
            return `${datePart} ${timePart}`;
        };

        // Load settings from API
        async function loadSettings() {
            try {
                const response = await fetch(`../api.php?controller=settings&action=get`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    return result.data;
                }
            } catch (error) {
                console.error('Error loading settings:', error);
            }
            
            // Return default settings if failed
            return {
                receipt_header_text: 'Terima kasih telah berbelanja!',
                receipt_footer_text: 'Terima kasih atas kunjungan Anda!\nSampai jumpa kembali.',
                company_phone: '+6285121010199'
            };
        }

        // Load transaction data
        async function loadTransaction() {
            try {
                const transactionId = '<?php echo addslashes($transactionId); ?>';
                
                // Load settings and transaction in parallel
                const [settingsResult, transactionResult] = await Promise.all([
                    loadSettings(),
                    fetch(`../api.php?controller=transaction&action=get&id=${transactionId}`).then(r => r.json())
                ]);
                
                if (transactionResult.success && transactionResult.data) {
                    renderReceipt(transactionResult.data, settingsResult);
                } else {
                    showError(transactionResult.error || 'Transaction not found');
                }
            } catch (error) {
                console.error('Error loading transaction:', error);
                showError(error.message || 'Failed to load transaction');
            }
        }

        // Render receipt
        function renderReceipt(transaction, settings = {}) {
            const template = document.getElementById('receiptTemplate');
            const clone = template.content.cloneNode(true);
            
            // Fill transaction info
            clone.getElementById('transactionNumber').textContent = transaction.transaction_number || '#N/A';
            clone.getElementById('transactionDate').textContent = formatDate(transaction.created_at);
            clone.getElementById('cashierName').textContent = transaction.user_name || 'Unknown';
            clone.getElementById('customerName').textContent = transaction.customer_name || 'Walk-in Customer';
            
            // Render items
            const itemsContainer = clone.getElementById('receiptItems');
            if (transaction.items && transaction.items.length > 0) {
                itemsContainer.innerHTML = transaction.items.map(item => `
                    <div class="receipt-item">
                        <div class="item-details">
                            <div class="item-name">${item.product_name || 'Unknown Product'}</div>
                            <div class="item-quantity">${toNumber(item.quantity) || 0} × ${formatCurrency(item.unit_price)}</div>
                        </div>
                        <div class="item-price">${formatCurrency(item.total_price)}</div>
                    </div>
                `).join('');
            } else {
                itemsContainer.innerHTML = '<div style="text-align: center; color: #9ca3af; padding: 20px;">No items found</div>';
            }
            
            // Compute summary with fallbacks if API returns strings or missing values
            let subtotal = toNumber(transaction.subtotal);
            let discount = toNumber(transaction.discount_amount);
            let tax = toNumber(transaction.tax_amount);
            let total = toNumber(transaction.total_amount);

            if ((!subtotal || subtotal <= 0) && Array.isArray(transaction.items)) {
                // Recompute subtotal from items in case API sent strings or zeros
                subtotal = transaction.items.reduce((sum, it) => {
                    const lineTotal = toNumber(it.total_price);
                    const qty = toNumber(it.quantity);
                    const unit = toNumber(it.unit_price);
                    return sum + (lineTotal || (qty * unit));
                }, 0);
            }
            // If total missing, recompute using discount and tax
            if (!total || total <= 0) {
                const taxable = Math.max(subtotal - discount, 0);
                tax = tax || Math.round(taxable * (toNumber(transaction.tax_percentage) || 10) / 100);
                total = taxable + tax;
            }

            // Fill summary
            clone.getElementById('subtotal').textContent = formatCurrency(subtotal);
            clone.getElementById('discount').textContent = formatCurrency(discount);
            clone.getElementById('tax').textContent = formatCurrency(tax);
            clone.getElementById('total').textContent = formatCurrency(total);
            
            // Payment method
            if (transaction.payment_method) {
                const paymentInfo = clone.getElementById('paymentInfo');
                paymentInfo.style.display = 'block';
                clone.getElementById('paymentMethod').textContent = transaction.payment_method.toUpperCase();
            }
            
            // Replace loading state with receipt
            const receiptBody = document.getElementById('receiptBody');
            receiptBody.innerHTML = '';
            receiptBody.appendChild(clone);
            
            // Add footer with dynamic settings
            const headerText = settings.receipt_header_text || 'Terima kasih telah berbelanja!';
            const footerText = settings.receipt_footer_text || 'Terima kasih atas kunjungan Anda!\nSampai jumpa kembali.';
            const phoneNumber = settings.company_phone || '+6285121010199';
            
            const footer = document.createElement('div');
            footer.className = 'receipt-footer';
            footer.innerHTML = `
                <p class="thank-you">${escapeHtml(headerText)}</p>
                <p style="white-space: pre-line;">${escapeHtml(footerText)}</p>
                <p style="margin-top: 15px; font-size: 12px;">
                    <i class="fas fa-phone"></i> Contact: ${escapeHtml(phoneNumber)}
                </p>
            `;
            document.querySelector('.receipt-container').appendChild(footer);

            // Prepare WhatsApp share link & target toggle
            try {
                const itemsText = (Array.isArray(transaction.items) ? transaction.items : []).map(it => {
                    const q = toNumber(it.quantity) || 0;
                    const p = formatCurrency(it.unit_price);
                    const t = formatCurrency(it.total_price);
                    return `- ${q} × ${it.product_name || 'Item'} @ ${p} = ${t}`;
                }).join('\n');
                const shareText = [
                    `Receipt ${transaction.transaction_number || ''}`,
                    `Date: ${formatDate(transaction.created_at)}`,
                    `Total: ${formatCurrency(total)}`,
                    itemsText ? 'Items:\n' + itemsText : '' ,
                    `Link: ${window.location.href}`
                ].filter(Boolean).join('\n');
                const waBtn = document.getElementById('whatsappShareBtn');
                const toggle = document.getElementById('waTargetToggle');
                const getTargetPref = () => localStorage.getItem('receiptWaTarget') || 'customer';
                const setTargetPref = (val) => localStorage.setItem('receiptWaTarget', val);
                function deriveNumberByTarget(target) {
                    const phone = target === 'customer'
                        ? (transaction.customer_phone || transaction.customerPhone || transaction.customer_phone_number || transaction.customer_mobile || '')
                        : (settings.company_phone || '+6285121010199');
                    return normalizePhoneToWa(phone);
                }
                function applyTarget() {
                    const target = getTargetPref();
                    if (toggle) toggle.textContent = target === 'customer' ? 'C' : 'A';
                    const normalized = deriveNumberByTarget(target);
                    if (waBtn) {
                        const url = normalized ? `https://wa.me/${normalized}?text=${encodeURIComponent(shareText)}` : `https://wa.me/?text=${encodeURIComponent(shareText)}`;
                        waBtn.setAttribute('href', url);
                        if (!normalized) {
                            waBtn.classList.add('disabled');
                            waBtn.setAttribute('aria-disabled', 'true');
                            waBtn.setAttribute('title', 'Nomor tidak tersedia');
                        } else {
                            waBtn.classList.remove('disabled');
                            waBtn.removeAttribute('aria-disabled');
                            waBtn.setAttribute('title', 'Share to WhatsApp');
                        }
                    }
                }
                applyTarget();
                if (toggle) {
                    toggle.addEventListener('click', () => {
                        const current = getTargetPref();
                        const next = current === 'customer' ? 'admin' : 'customer';
                        setTargetPref(next);
                        applyTarget();
                    });
                }
            } catch (e) { /* ignore */ }
        }
        
        // Escape HTML helper
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Show error state
        function showError(message) {
            const receiptBody = document.getElementById('receiptBody');
            receiptBody.innerHTML = `
                <div class="error-state">
                    <div class="error-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <h3 style="margin-bottom: 10px; font-size: 20px;">Failed to Load Receipt</h3>
                    <p style="color: #6b7280; font-size: 14px;">${message}</p>
                    <a href="transactions.php" class="btn btn-primary" style="margin-top: 20px;">
                        <i class="fas fa-arrow-left"></i>
                        Back to Transactions
                    </a>
                </div>
            `;
        }
        
        // Load transaction when page loads
        document.addEventListener('DOMContentLoaded', loadTransaction);

        // Keyboard shortcuts: P=print, W=share WhatsApp, ESC=back
        document.addEventListener('keydown', (e) => {
            if (e.key.toLowerCase() === 'p') { e.preventDefault(); window.print(); }
            if (e.key.toLowerCase() === 'w') {
                const waBtn = document.getElementById('whatsappShareBtn');
                if (waBtn && !waBtn.classList.contains('disabled')) {
                    waBtn.click();
                }
            }
            if (e.key === 'Escape') { window.location.href = 'transactions.php'; }
        });
    </script>
</body>
</html>
