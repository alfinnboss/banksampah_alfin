BANK SAMPAH - WEB PHP + MYSQL

1. Pastikan XAMPP Apache dan MySQL aktif.
2. Buat database bernama: banksampah_nandhiva
3. Import file banksampah_nandhiva.sql melalui phpMyAdmin.
4. Salin folder "banksampah_web" ke:
   C:\xampp\htdocs\
5. Buka:
   http://localhost/banksampah_web/

Akun:
admin / admin123
siswa / siswa123
guru / guru123
pengepul / pengepul123

Catatan:
- Web ini dibuat mengikuti tabel yang ada pada SQL yang diberikan:
  user, sampah, transaksi, tarik_saldo, setoran_pengepul.
- Konfigurasi database ada di config.php.
- Jika MySQL Anda memakai password root, ubah variabel $pass di config.php.

ALUR BARU: Admin menerima setoran -> stok bertambah -> Admin jual stok ke Pengepul -> Pengepul setujui -> stok berkurang -> transaksi tersimpan.
