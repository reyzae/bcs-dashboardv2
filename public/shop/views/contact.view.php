<?php
$page_title = 'Kontak';
include __DIR__ . '/layout/header.php';
?>

<div class="content">
    <div class="card">
        <div class="card-body contact-grid">
            <div class="contact-primary">
                <h3>Hubungi Kami</h3>
                <?php
                $name = $settings['company_name'] ?? 'Bytebalok';
                $email = $settings['company_email'] ?? '';
                $phone = $settings['company_phone'] ?? '';
                $address = $settings['company_address'] ?? '';
                $digits = preg_replace('/[^0-9]/','', (string)$phone);
                if (strpos($digits, '0') === 0) { $digits = '62' . substr($digits, 1); }
                if (strpos($digits, '62') !== 0) { $digits = '62' . $digits; }
                $wa = 'https://wa.me/' . $digits . '?text=' . urlencode('Halo ' . $name . ', saya ingin bertanya tentang pesanan.');
                $maps = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($address);
                ?>
                <div class="contact-actions">
                    <a class="btn btn-primary contact-whatsapp" href="<?= $wa ?>" target="_blank" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i> WhatsApp</a>
                    <?php if (!empty($email)): ?>
                    <a class="btn btn-primary contact-email" href="mailto:<?= htmlspecialchars($email) ?>" aria-label="Email"><i class="fas fa-envelope"></i> Email</a>
                    <?php endif; ?>
                </div>
                
            </div>
            <div class="contact-secondary">
                <h3>Bantuan Cepat</h3>
                <div class="contact-quick-links">
                    <a class="btn" href="/shop/order-status.php" aria-label="Lacak Pesanan"><i class="fas fa-search"></i> Lacak Pesanan</a>
                    <a class="btn" href="/shop/faq.php" aria-label="FAQ"><i class="fas fa-question-circle"></i> FAQ</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>