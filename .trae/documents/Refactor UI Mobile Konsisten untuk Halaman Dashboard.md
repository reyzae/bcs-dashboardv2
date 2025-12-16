## Sasaran

- Menyederhanakan UI mobile: bar Filter ringkas, segmented toggle untuk View & Source, chips scrollable, bottom sheet untuk filter detail.
- Konsistensi di seluruh halaman dashboard (Transactions, POS, Orders, Products, Customers, Reports, Index/Admin/Manager/Cashier/Staff).
- Aksesibilitas & kinerja lebih baik di layar kecil.

## Komponen UI Baru

### Mobile Filter Bar
- Segmented control: `View` (Table/Chart), `Source` (All/POS/Shop).
- Input cari ringkas dengan tombol clear di dalam.
- Chips scrollable horizontal untuk `Status`, `Method`, `Range` (Today/7D/30D/Custom).
- Tombol `Reset` kecil (ikon ↺) di ujung chips.

### Bottom Sheet Filter
- Panel `position: fixed; bottom: 0;` berisi filter detail (status, metode, periode kustom).
- Dibuka via ikon filter di bar; backdrop click menutup.

### Auto Refresh Compact
- Ikon timer dengan label interval (mis. “60s”).
- Ketuk membuka pilihan interval (30/60/120/Off); simpan preferensi di `localStorage`.

### CTA Responsif & Aksesibilitas
- Tap target min 44px; jarak antar kontrol 8–12px.
- `aria-label` untuk ikon; kontras chip aktif/nonaktif.

## Implementasi Teknis

### CSS
- Tambah utilitas di `public/assets/css/responsive.css`:
  - `.mobile-filter-bar`, `.chips-scroll`, `.segmented`, `.bottom-sheet`, `.backdrop`.
  - Media query `@media (max-width: 480px)` untuk padding/gap & menyembunyikan shadow berat.

### JS
- Buat `public/assets/js/mobile-ui.js`:
  - Inisialisasi segmented control & chips scroll.
  - Logika bottom sheet (open/close), auto refresh, simpan preferensi.
  - API ringan: `initMobileFilters({ onApply })` memanggil callback untuk menerapkan filter ke halaman.

### Integrasi Halaman (Urutan)
1. Transactions (`public/dashboard/transactions.php`): ganti bar filter + binding ke filter existing.
2. POS (`public/dashboard/pos.php`): search & kategori chips; compact auto refresh untuk quick stats.
3. Orders (`public/dashboard/orders.php`): chips status & periode.
4. Reports (`public/dashboard/reports.php`): range preset & bottom sheet export options.
5. Products (`public/dashboard/products.php`): chips kategori & status; search inline.
6. Customers (`public/dashboard/customers.php`): search & chips status aktif.
7. Index/role-specific views (`public/dashboard/views/...`): adopsi header ringkas & segmented view.

## Konsistensi Data & Preferensi
- Simpan preferensi user (view, source, auto refresh, range preset) di `localStorage` per halaman.
- Default aman untuk mobile: View=Table, Source=All, Range=Today, Refresh=Off.

## Pengujian
- Unit: interaksi segmented, chips scroll, bottom sheet open/close.
- Manual: iPhone/Android width 360–414; verifikasi tap target & overflow-x lancar.
- Regressi: filter server tetap menerima parameter yang sama; tidak ada perubahan payload.

## Rilis Bertahap
- Tahap 1: Transactions + POS.
- Tahap 2: Orders + Products.
- Tahap 3: Customers + Reports + Dashboard views.

## Kriteria Sukses
- Tidak ada scroll vertikal berlebihan; filter muat dalam satu layar.
- Semua halaman dashboard mobile tampil seragam; fungsi filter sama seperti desktop.
- Lighthouse mobile aksesibilitas ≥ 90.
