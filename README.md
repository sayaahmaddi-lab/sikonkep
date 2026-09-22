# SiKonKep — Aplikasi Formulir Daftar Kepentingan Pribadi

Aplikasi web untuk mengisi **Formulir Daftar Kepentingan Pribadi** (pencegahan
Konflik Kepentingan) secara digital, lengkap dengan **tanda tangan digital**
(mouse/touch), lalu menyimpan data ke **MySQL**.

## Fitur

- Formulir digital sesuai format asli (Identitas + Bagian A–F + Pernyataan).
- Tanda tangan digital via canvas (mouse / layar sentuh), dengan Undo & Hapus.
- Simpan otomatis di browser (localStorage) agar isian tidak hilang.
- Cetak / Simpan PDF dengan tata letak rapi ukuran A4.
- Simpan ke database MySQL melalui backend PHP (tombol "Simpan ke Database").
- Halaman admin: rekap data, detail isian, ubah status, export CSV.
- **Halaman admin dilindungi login** (sesi aman, CSRF token, anti brute-force).

## Berkas Utama

| Berkas | Fungsi |
|---|---|
| `formulir-daftar-kepentingan-pribadi.html` | Halaman formulir untuk pegawai |
| `backend/schema.sql` | Skrip pembuatan database & tabel MySQL |
| `backend/config.php` | Konfigurasi koneksi database + akun admin |
| `backend/auth.php` | Sesi login, CSRF, anti brute-force |
| `backend/simpan.php` | Endpoint penerima & penyimpan data |
| `backend/admin/login.php` | Halaman login admin |
| `backend/admin/logout.php` | Keluar dari sesi admin |
| `backend/admin/index.php` | Daftar isian (admin) + export CSV |
| `backend/admin/detail.php` | Detail isian + ubah status |
| `backend/tools/hash_pass.php` | Alat pembuat hash password |
| `PANDUAN.md` | Panduan lengkap pemasangan & pemakaian |

## Cara Pakai (ringkas)

1. Pasang XAMPP (Apache + MySQL), salin folder ini ke `htdocs`.
2. Jalankan `backend/schema.sql` di phpMyAdmin untuk membuat database.
3. Sesuaikan `backend/config.php` dengan kredensial MySQL Anda.
4. Buka `http://localhost/sikonkep/formulir-....html` untuk mengisi,
   dan `http://localhost/sikonkep/backend/admin/` untuk melihat rekap.

Detail lengkap: lihat **`PANDUAN.md`**.
