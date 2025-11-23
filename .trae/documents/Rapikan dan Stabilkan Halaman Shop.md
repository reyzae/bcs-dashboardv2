## Cakupan
- Merapikan seluruh halaman di `public/shop` (`index.php`, `cart.php`, `checkout.php`, `order-status.php`).
- Menyamakan alur API dengan dashboard admin (`public/api.php` + controller terkait).
- Menjadikan UI compact, responsif, dan bebas bug di perangkat kecil (portrait/landscape).
- Menyempurnakan alur pembayaran (QRIS/Transfer) dengan Payment Sheet overlay.

## Deliverables
- Halaman Shop siap pakai: tidak ada error JS, link/endpoint konsisten, cart & checkout stabil.
- Payment Sheet menampilkan QRIS (gambar statis `qris-gopay.svg`) atau info transfer bank/VA.
- CSS compact responsif di semua rasio, dengan list/grid view rapi, skeleton loading, dan sticky controls.
- Validasi: alur pencarian, kategori, sort, keranjang, checkout, tracking status pesanan bekerja end-to-end.

## Perubahan Teknis
- `public/assets/js/shop.js`
  - Konsolidasi `apiCall` dengan header CSRF untuk non-GET; penanganan error dan empty-state.
  - Pencarian: gunakan `search` (kompatibel dengan `ProductController::list`).
  - Kategori: chip aktif seragam (`filter-chip`) + handler.
  - View toggle Grid/List: kelas `products-list` + render ulang.
  - Checkout payload: `customer_name`, `customer_phone`, `customer_email`, `customer_address`, `payment_method`, `items[{product_id, quantity}]` → `OrderController::create`.
  - Payment Sheet overlay: render QRIS pakai `../assets/img/qris-gopay.svg`; Transfer render `bank_name`, `account_name`, `account_number`, `virtual_account`, `reference_number`; total & expired_at; link ke `order-status.php?code=<order_number>`.
  - Tracking pesanan: endpoint `order&action=getByNumber&order_number=...` dengan tampilan `order_status`, `payment_status`, `total_amount`.
  - Opsional: auto-refresh status pembayaran per 10–15 detik sampai `paid`/`expired` (akan disiapkan jika disetujui).
- `public/shop/*.php`
  - Perbaikan navigasi logo (`index.php`), tautan mini-cart “Checkout” ke `checkout.php`.
  - `checkout.php`: Payment Sheet markup overlay; opsi COD disabled.
- `public/assets/css/shop.css`
  - Compact spacing: density-compact; tombol, padding/margin disesuaikan.
  - List view produk (.products-grid.products-list): layout horizontal, image 96×96, satu baris judul.
  - Orientation-aware: portrait/landscape breakpoint untuk grid, checkout layout, container padding.
  - Payment Sheet styling: card, header, body grid, QR image, summary card, total row.

## Pembayaran & Status
- QRIS: tampilkan `qris-gopay.svg` sebagai QR visual; tanpa menampilkan QR string (sesuai arahan).
- Transfer: tampilkan detail manual/VA dari Payment model/gateway.
- Link "Lihat Status Pesanan" arahkan ke `order-status.php?code=<order_number>`.
- Opsional: tambahkan countdown ke `expired_at` dan polling status pembayaran.

## Keamanan
- CSRF untuk mutating requests via header `X-CSRF-Token` (cookie `csrf_token`).
- CSP tetap ketat; karena QR image statis lokal, tidak perlu membuka domain eksternal.

## Kinerja
- Skeleton loader untuk grid produk; debounce pencarian; minimal repaint saat toggle view.
- Hindari render berulang yang tidak perlu; gunakan class toggle untuk grid/list.

## Pengujian & Verifikasi
- Manual QA flow:
  - Pencarian, kategori, sorting, tambah ke keranjang.
  - Keranjang (ubah qty, hapus item, subtotal/total).
  - Checkout QRIS/Transfer → Payment Sheet tampil; keranjang kosong; link ke status pesanan.
  - Tracking berdasarkan `order_number` menampilkan status & total.
- Konsol bebas error; network request sukses dengan payload yang sesuai.
- Responsif diuji pada ponsel/tablet (portrait/landscape).

## Milestone
1) Audit & sinkronisasi API (produk, kategori, order) dan perbaiki link/endpoint.
2) Implementasi/penyempurnaan Payment Sheet (QRIS gambar statis, Transfer info).
3) Perapihan CSS compact & orientasi; list/grid view dan skeleton.
4) Tambahkan penanganan error & empty states.
5) QA end-to-end, polishing kecil (spacing, overflow, aksesibilitas dasar).
6) Opsional: countdown & polling pembayaran.

Konfirmasi: jika Anda setuju dengan rencana di atas, saya akan langsung eksekusi per langkah dan melakukan verifikasi end-to-end hingga halaman `/shop` siap pakai tanpa bug.