## Tujuan
- Standarisasi kolom export untuk Transactions, Orders, Products, Customers sesuai saran.
- Excel selalu .xlsx, header difreeze, AutoFilter aktif, formatting kolom rapi (ID/Phone teks, Amount angka), baris TOTAL hanya menjumlah kolom numeric relevan.

## Implementasi
1. ExportHelper (Excel)
- Paksa ekstensi .xlsx.
- Tambahkan Freeze top row (`A2`) dan AutoFilter pada range data.
- Terapkan formatting per kolom berdasarkan nama header:
  - Teks: Transaction/Order/Customer Code/SKU/Barcode/Phone → format `@` dan tulis sebagai explicit string.
  - Angka: Amount/Subtotal/Discount/Tax/Shipping/Revenue/Profit/Price/Cost/Total Spent/Stock Quantity → format `#,##0`.
  - Tanggal tetap string `dd/mm/yyyy hh:mm`.
- Totals: hanya menjumlah kolom numeric relevan (berdasar header kategori), tampil tebal & latar abu-abu.

2. Transactions export
- SELECT tambahkan `subtotal`, `discount_amount`, `tax_amount`, `payment_reference`, `items_count` (subselect SUM(quantity)).
- Data & headers disusun: Transaction Number, Date, Customer, Phone, Cashier, Payment Method, Status, Notes, Source, Items Count, Subtotal, Discount Amount, Tax Amount, Total Amount, Payment Reference.

3. Orders export
- Tambahkan kolom amounts: Subtotal, Discount, Tax, Shipping, Total; Paid At atau Order Date.
- Headers disusun konsisten; numeric akan otomatis terformat & ikut total.

4. Products & Customers
- Biarkan headers saat ini, formatting di Excel akan menangani tipe teks/angka; baris TOTAL otomatis menjumlah kolom angka seperti Stock Quantity, Total Spent.

5. Verifikasi
- Uji unduhan dari tiap halaman dashboard (autentik).
- Cek: .xlsx, header freeze+filter, tidak ada notasi ilmiah untuk ID/Phone, baris TOTAL hanya di kolom numeric.

## Hasil
- Export Excel di semua halaman rapi, dapat dianalisis langsung, dengan baris total yang akurat dan formatting konsisten.