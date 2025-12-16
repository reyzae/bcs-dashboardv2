## Target
- Menyelesaikan fitur export di semua halaman (Transactions, Products, Customers, Orders) agar CSV/Excel/PDF bekerja dan membawa semua filter aktif.
- Mengaudit CTA (buttons/menus) di seluruh halaman `public/dashboard` dan `public/shop`, memastikan API call benar dan bebas error.

## Pekerjaan Tersisa
1. Konsolidasi Export Backend
- Customers: selaraskan SELECT kolom dengan kolom yang dipakai pada mapping (hilangkan field non-eksis seperti `date_of_birth`, `points`, `visit_count`, `last_visit`, gunakan `postal_code`, `customer_type`, `total_purchases`, `last_purchase_date` jika tersedia via model/stats).
- Products: pastikan endpoint `product/export` mengembalikan kolom yang konsisten (SKU/Code, Name, Category, Price, Stock, Status, Created At).
- Orders: tambahkan dukungan filter `search`, `date_from`, `date_to`, `payment_status` pada export agar hasil sesuai tab/filters UI.
- Transactions: pastikan `source`/`type` filter (POS/Shop) ikut pada export; verifikasi parameter `status`, `payment_method`, `date_from`, `date_to` diterapkan.
- PDF support: gunakan `ExportHelper::exportToPDF` untuk Customers/Products/Orders/Transactions; fallback ke HTML bila TCPDF tidak tersedia.

2. Integrasi Filter di UI
- Orders: propagasi `search`, `date range`, `payment status` dari UI ke URL export.
- Transactions: propagasi semua filter aktif (`status`, `payment_method`, `source`, `date range`, `search`).
- Products/Customers: propagasi `search` dan filter status jika ada.

3. Audit CTA Dashboard
- Halaman: `index.php`, `pos.php`, `transactions.php`, `orders.php`, `sales.php`, `products.php`, `customers.php`, `users.php`, `settings.php`, `notifications.php`, `reports.php`, `receipt.php`.
- Identifikasi: tombol aksi (add/edit/delete/refresh/sync/export/print/view), menu dropdown, keyboard shortcuts.
- Validasi: endpoint `../api.php?controller=...` sesuai controller, method HTTP tepat, body/payload benar, CSRF/session aman.
- Perbaikan: selaraskan nama parameter, cegah error null, tambahkan handling error/feedback (toast), dan nonaktifkan tombol saat proses.

4. Audit CTA Shop
- Halaman: `public/shop/index.php`, `checkout.php`.
- Validasi: add to cart, filter kategori, sort, place order, request verification; endpoint `api.php?controller=order` dan `payment` harus sesuai.

5. Pengujian & Verifikasi
- Jalankan dev server lokal dan uji setiap export (CSV, Excel, PDF) di setiap halaman.
- Uji unduhan dalam sesi login (hindari 401 ketika dipanggil dari luar).
- Uji CTA utama: berhasil, ada feedback UI, dan tidak ada error di console.
- Dokumentasikan hasil uji (ringkas) dan daftar perubahan.

## Output yang Diharapkan
- Semua tombol export berfungsi dengan CSV/Excel/PDF dan membawa filter aktif.
- Semua CTA utama di dashboard dan shop berfungsi tanpa error.
- Konsistensi kolom dan format di file export.

## Mohon Konfirmasi
Saya akan lanjut mengimplementasikan perubahan di controller dan UI untuk propagasi filter, menambah dukungan PDF merata, menyelaraskan kolom export, serta memperbaiki CTA yang ditemukan bermasalah. Setelah itu saya uji end-to-end di server lokal dan laporkan hasilnya.