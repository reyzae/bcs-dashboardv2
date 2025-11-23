## Tujuan
- Card Today’s Transactions, Today’s Revenue, This Month Revenue, Pending Transactions konsisten realtime dan merefleksikan gabungan POS + Shop.
- Opsional: saat user memilih Source (All/Pos/Shop), card mengikuti filter sumber yang aktif.

## Backend
- Update `TransactionController::getStats` untuk menerima parameter `type=all|pos|shop`, tetap memanggil `syncShopTransactions()` di awal.
- Ubah `Transaction::getSalesStats($startDate, $endDate, $type)`:
  - Terapkan filter sumber jika `type='pos'` atau `type='shop'` menggunakan heuristik yang sudah dipakai di listing (served_by='System Online' atau notes prefix "Order ").
  - Hitung:
    - `today_count`: transaksi hari ini (semua status, terfilter sumber) + jumlah orders Shop `paid/completed` hari ini yang belum punya transaksi (hanya ditambahkan jika `type` adalah `all` atau `shop`).
    - `today_revenue`: SUM(total_amount) status `completed` hari ini (terfilter sumber).
    - `month_revenue`: SUM(total_amount) status `completed` bulan berjalan (terfilter sumber).
    - `pending_count`: COUNT status `pending` (terfilter sumber).
- Optimisasi query: gunakan kondisi SQL terpusat agar tidak perlu memuat semua baris.

## Frontend
- `public/dashboard/transactions.php::loadStats()` tambahkan parameter `type` dari `this.currentSource` pada URL (`...&type=all|pos|shop`).
- Saat user mengganti tab Source, selain `loadTransactions()` panggil `loadStats()` (sudah ada, pastikan tetap).
- Di `setupAutoRefresh(interval)`, sertakan `loadStats()` (sudah ada) agar kartu ikut update.
- Pastikan animasi counter memakai angka terbaru dan `updateLastUpdated()` dieksekusi setelah `loadStats()`.

## Keamanan & UX
- Tidak ada perubahan skema DB atau kredensial.
- Untuk entri pseudo Shop (belum punya ID transaksi), UI sudah aman; tidak ada tombol aksi yang menyebabkan error.

## Verifikasi
- Buat transaksi POS dan selesaikan order Shop ➜ cek Tab All menampilkan gabungan.
- Ganti tab Source ke POS/Shop ➜ pastikan kartu menyesuaikan.
- Aktifkan Auto Refresh 60s ➜ kartu dan daftar bergerak sesuai data.

## Deliverables
- Perubahan pada `app/controllers/TransactionController.php` dan `app/models/Transaction.php` untuk filter sumber di stats.
- Perubahan kecil pada `public/dashboard/transactions.php` agar `loadStats()` mengirim `type` dan memanggil ulang saat Source berubah.

Konfirmasi rencana ini, lalu saya implementasikan, uji di UI, dan pastikan semua kartu konsisten realtime untuk POS + Shop.