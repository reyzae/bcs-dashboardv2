## Diagnosa Singkat
- Tab All hanya menampilkan POS karena gabungan Shop dari orders yang belum terbentuk transaksi ditambahkan setelah pengambilan data tetapi tidak diurutkan ulang sebelum pagination; hasilnya, entri Shop berada di akhir dan terpotong oleh `array_slice`.
- Kartu Today’s Transactions sudah menghitung gabungan POS+Shop, namun jika sinkron Shop belum berjalan atau ada orders yang belum menjadi transaksi, angka bisa tidak akurat.

## Rencana Perbaikan
### 1) Backend: Perbaiki list untuk Tab All
- Jalankan `syncShopTransactions()` di awal `TransactionController::list()` untuk memastikan transaksi Shop tercipta bila memungkinkan.
- Saat `type=all`, gabungkan orders Shop `paid/completed` yang belum punya transaksi ke array `transactions` sebagai entri tampilan.
- Setelah penggabungan, URUTKAN `transactions` berdasarkan `created_at DESC` sebelum menghitung `total` dan melakukan `array_slice` pagination.
- Pastikan entri gabungan berisi minimal: `transaction_number`, `customer_name`, `items_count`, `total_amount`, `payment_method`, `status='completed'`, `created_at=paid_at`, `served_by='System Online'`, `notes='Order <ORDER_NUMBER>'`.

### 2) Frontend: Tahan Aksi untuk entri tanpa ID
- Pada tabel actions (View/Receipt/Print), jika `txn.id` null (entri gabungan orders), sembunyikan tombol aksi yang memerlukan ID untuk menghindari error; tetap tampilkan baris dan badge sumber.

### 3) Statistik: Pastikan kartu Today aktif
- Di endpoint `stats`, tetap jalankan sinkron Shop di awal.
- Hitung `today_count` sebagai: transaksi hari ini (semua status) + orders Shop `paid/completed` hari ini yang belum punya transaksi.

### 4) Verifikasi
- Buka Transactions → Source: All, pastikan entri Shop tampil di urutan atas.
- Uji pagination; entri Shop tetap muncul di halaman 1 berkat pengurutan ulang.
- Cek kartu Today’s Transactions naik saat ada POS atau Shop baru.

## Dampak Kode
- Mengubah `app/controllers/TransactionController.php::list` untuk penggabungan dan pengurutan ulang.
- Menyesuaikan render actions di `public/dashboard/transactions.php` agar aman bila `id` null.
- Tidak mengubah skema database.

## Konfirmasi
Setujui rencana ini, maka saya akan menerapkan perubahan, menguji di halaman Transactions, dan pastikan Tab All serta kartu Today’s Transactions menampilkan gabungan POS+Shop secara konsisten.