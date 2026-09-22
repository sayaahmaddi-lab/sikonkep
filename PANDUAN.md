# Formulir Daftar Kepentingan Pribadi — Panduan Pemasangan

Proyek ini berisi:
- **Halaman formulir** (HTML) yang bisa diisi + tanda tangan digital (mouse/touch).
- **Backend PHP** untuk menyimpan data & tanda tangan ke **MySQL**.
- **Halaman admin** untuk melihat rekap, detail, export CSV, dan mengubah status.

---

## Struktur Folder

```
sikonkep/
├── formulir-daftar-kepentingan-pribadi.html   ← halaman pengisian (untuk pegawai)
└── backend/
    ├── config.php        ← GANTI kredensial database & password admin DI SINI
    ├── db.php            ← koneksi PDO (tidak perlu diubah)
    ├── auth.php          ← sesi login, CSRF, anti brute-force (tidak perlu diubah)
    ├── simpan.php        ← endpoint penerima data (tidak perlu diubah)
    ├── schema.sql        ← script pembuatan database & tabel
    ├── tools/
    │   └── hash_pass.php ← alat membuat hash password admin
    └── admin/
        ├── login.php     ← halaman login admin (USAHAKAN login dulu)
        ├── logout.php    ← keluar dari sesi admin
        ├── index.php     ← daftar isian + export CSV (untuk admin/verifikator)
        └── detail.php    ← detail isian + ubah status
```

---

## Langkah Pemasangan di XAMPP (paling umum)

### 1. Siapkan web server + MySQL
- Pasang **XAMPP** (atau Laragon/WAMP) di komputer/server kantor.
- Start **Apache** dan **MySQL** dari XAMPP Control Panel.

### 2. Salin folder proyek
- Salin seluruh folder `sikonkep` ke `C:\xampp\htdocs\sikonkep`
  (hasil akhir: `C:\xampp\htdocs\sikonkep\formulir-....html` dan `C:\xampp\htdocs\sikonkep\backend\...`).

### 3. Buat database
- Buka browser → **http://localhost/phpmyadmin**
- Pilih tab **SQL**, lalu tempel seluruh isi file `backend/schema.sql`, klik **Go**.
  - Atau via terminal: `mysql -u root < backend/schema.sql`
- Akan terbentuk database **`sikonkep`** dan tabel **`pengisian`**.

### 4. Atur koneksi database & akun admin
- Buka `backend/config.php`, sesuaikan:
  ```php
  define('DB_HOST', 'localhost');   // host (default localhost)
  define('DB_NAME', 'sikonkep');    // nama database
  define('DB_USER', 'root');        // user (default root di XAMPP)
  define('DB_PASS', '');            // password (default kosong di XAMPP)
  ```
- Atur akun admin di baris yang sama:
  ```php
  define('ADMIN_USER', 'admin');               // username login admin
  define('ADMIN_PASS', 'ganti-password-anda'); // password (boleh teks biasa)
  ```

### 5. (Disarankan) Ganti password admin dengan hash bcrypt
- Password boleh ditulis biasa, tapi lebih aman disimpan sebagai hash.
- Jalankan dari terminal di folder `htdocs/sikonkep`:
  ```
  php backend/tools/hash_pass.php "password-rahasia-anda"
  ```
- Salin output yang berawalan `$2y$`, lalu tempel sebagai nilai `ADMIN_PASS`:
  ```php
  define('ADMIN_PASS', '$2y$10$..................');
  ```
- Setelah selesai, **hapus folder `backend/tools/`** agar alatnya tidak terpublikasi.

### 6. Akses aplikasi
- Formulir (pegawai): **http://localhost/sikonkep/formulir-daftar-kepentingan-pribadi.html**
- Admin (rekap): **http://localhost/sikonkep/backend/admin/**
  → akan diarahkan ke halaman **login** terlebih dahulu.

---

## Pengamanan Halaman Admin

Halaman `backend/admin/` kini **wajib login**:

- **Login**: kunjungi `backend/admin/login.php` atau langsung buka `backend/admin/`.
  Masukkan `ADMIN_USER` dan `ADMIN_PASS` yang diatur di `config.php`.
- **Logout**: klik tombol **"Keluar"** di pojok kanan atas, atau buka `backend/admin/logout.php`.
- **Sesi login** berlaku maksimal **8 jam** (bisa diubah lewat `ADMIN_SESSION_LIFETIME`).
- **Anti brute-force**: setelah **5× gagal login** (bisa diubah lewat `ADMIN_MAX_ATTEMPTS`),
  IP akan diblokir sementara selama ±1 menit.
- **CSRF token**: semua form POST (login & ubah status) memakai token acak untuk
  mencegah serangan CSRF.
- **Header keamanan**: `X-Frame-Options` (anti clickjacking), `X-Content-Type-Options`
  (anti MIME sniffing), dan `Referrer-Policy` otomatis terpasang.
- Cookie sesi ber-`HttpOnly` + `SameSite=Lax`, serta sesi diregenerasi berkala
  (anti session fixation).

> Untuk lingkungan produksi resmi, sebaiknya integrasikan dengan SSO/portal
> kepegawaian daerah atau tambahkan HTTPS.

---

## Cara Kerja Aliran Data

```
Browser (formulir + tanda tangan digital)
        │ 1. Klik "Simpan ke Database"
        │    → kirim JSON via fetch() ke backend/simpan.php
        ▼
backend/simpan.php (PHP + PDO)
        │ 2. Validasi, lalu INSERT ke tabel `pengisian`
        ▼
MySQL: database `sikonkep` → tabel `pengisian`
        ▲
        │ 3. Admin buka backend/admin/ untuk melihat/export/menindaklanjuti
backend/admin/index.php & detail.php
```

---

## Catatan Penting

### Tanda tangan
- Tanda tangan digambar di atas **canvas**, lalu dikirim sebagai **gambar PNG (base64)**
  dan disimpan di kolom `ttd` (tipe `LONGTEXT`). Data dikonversi saat dikirim
  (`canvas.toDataURL('image/png')`).
- Maksimal ukuran tanda tangan yang diterima: **5 MB** (diatur di `simpan.php`).

### Keamanan
- Semua query memakai **PDO prepared statements** → aman dari SQL injection.
- Nama/no input disaring & dibatasi panjangnya.
- Jika perlu proteksi halaman admin, aktifkan password sederhana dengan
  mengisi `ADMIN_PASS` di `config.php` lalu tambahkan cek sesi sederhana.
  (Untuk produksi resmi, sebaiknya gunakan autentikasi SSO/portal kepegawaian.)

### Kredensial
- Jangan berikan akses MySQL ke publik. Pastikan file `config.php` tidak
  diakses langsung lewat browser — di XAMPP `define()` tidak menampilkan apa pun,
  tetapi untuk produksi gunakan server dengan konfigurasi `open_basedir`
  atau letakkan `config.php` 1 tingkat di atas `htdocs`.

---

## Troubleshooting Ringkas

| Gejala | Penyebab / Solusi |
|---|---|
| Tombol "Simpan ke Database" muncul "Tidak dapat terhubung ke server" | Backend belum aktif: pastikan folder disalin ke `htdocs` dan Apache/MySQL berjalan. |
| Muncul "Gagal terhubung ke database" | Cek `backend/config.php` (host, nama db, user, password) & pastikan database sudah dibuat lewat `schema.sql`. |
| Muncul "Kolom wajib belum diisi: nama, nip" | Nama & NIP harus diisi di bagian identitas formulir. |
| Data masuk tapi tanda tangan kosong | Tanda tangan belum digambar. Gambar dulu di pad sebelum klik simpan. |
| Huruf berantakan di CSV | Sudah diberi BOM UTF-8; buka CSV dengan Excel lalu pilih import UTF-8. |
