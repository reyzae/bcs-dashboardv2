## Tujuan
- Membuat tombol Edit dan Delete di customers.php berfungsi konsisten tanpa bergantung pada inline onclick dengan payload JSON berisiko.

## Strategi
1. Ganti pembuatan tombol aksi di renderCustomers menjadi memakai data attributes:
- Edit: `button[data-action="edit"][data-id="..."]`
- Delete: `button[data-action="delete"][data-id][data-name]`
2. Tambah event delegation satu kali pada `tbody` untuk menangkap klik ke tombol aksi, mengambil data dari dataset, dan memanggil fungsi `editCustomer(...)` atau `deleteCustomer(...)` sesuai action.
3. Hindari duplikasi listener dengan sentinel `tbody.__binded`.
4. Jaga kompatibilitas: fungsi `editCustomer` dan `deleteCustomer` tetap dipakai tanpa modifikasi API.

## Hasil Diharapkan
- Klik pada ikon/tombol Edit/Delete selalu bekerja.
- Tidak ada error karena string quoting atau payload JSON di inline onclick.
- Tidak terjadi duplikasi handler setelah re-render.

## Implementasi
- Modifikasi `public/dashboard/customers.php` pada fungsi `renderCustomers()` (HTML tombol & binding listener).