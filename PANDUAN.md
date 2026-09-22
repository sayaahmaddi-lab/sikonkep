# Formulir Daftar Kepentingan Pribadi — Panduan Pemasangan

Proyek ini berisi:
- **Halaman formulir** (HTML) yang bisa diisi + tanda tangan digital (mouse/touch).
- **Backend PHP** untuk menyimpan data & tanda tangan ke **MySQL** (untuk XAMPP/lokal).
- **API Node.js** untuk menyimpan data ke **Neon PostgreSQL** (untuk Vercel/online).
- **Halaman admin** untuk melihat rekap, detail, export CSV, dan mengubah status (dua versi).

---

## Struktur Folder

```
sikonkep/
├── formulir-daftar-kepentingan-pribadi.html   ← halaman pengisian (untuk pegawai) - otomatis pilih endpoint
├── login.html                                ← login admin ONLINE (Vercel + Neon, JWT)
├── admin.html                                ← dashboard admin ONLINE (Vercel + Neon)
├── api/
│   ├── _lib.js      ← helper Neon + JWT + CORS
│   ├── simpan.js    ← POST /api/simpan  (Neon)
│   └── admin.js     ← /api/admin?action=login|list|get|status|export (Neon)
├── neon-schema.sql                           ← skema PostgreSQL untuk Neon
├── vercel.json      ← konfigurasi deploy Vercel
├── package.json     ← dependensi Vercel Functions (pg)
├── .env.example     ← contoh environment variables
│
└── backend/         ← versi XAMPP / PHP + MySQL (tetap berfungsi lokal)
    ├── config.php
    ├── db.php
    ├── auth.php
    ├── simpan.php
    ├── schema.sql
    ├── tools/hash_pass.php
    └── admin/
        ├── login.php
        ├── logout.php
        ├── index.php
        └── detail.php
```

> **Catatan:** `formulir-daftar-kepentingan-pribadi.html` cerdas — bila dibuka di `localhost` ia kirim ke `backend/simpan.php` (MySQL), bila di Vercel (`*.vercel.app`) ia kirim ke `/api/simpan` (Neon). Fallback otomatis bila salah satu 404.

---

## OPSI A — Deploy Online ke Vercel + Neon (disarankan)

### A1. Buat database di Neon (gratis)

1. Buka **https://neon.tech** → Sign up (bisa pakai GitHub/Google) → **Create Project**.
2. Pilih region **Singapore** (paling dekat ke Indonesia) → buat project (mis. `sikonkep`).
3. Di dashboard Neon → **Connection string** → pilih **Pooled** → copy string yang terlihat seperti:
   ```
   postgresql://user:password@ep-xxx.neon.tech/neondb?sslmode=require
   ```
   Simpan sebagai `DATABASE_URL`.
4. Buka **SQL Editor** di Neon → klik **New Query** → tempel seluruh isi file `neon-schema.sql** → **Run**.
   - Harus muncul `CREATE TABLE` sukses. Cek di **Tables** → `pengisian` sudah ada.
   - Alternatif via CLI: `psql "$DATABASE_URL" -f neon-schema.sql` atau `npm run setup-db` (membaca `DATABASE_URL` dari `.env`).

> ⚠️ **LANGKAH INI PALING SERING TERLEWAT!** Jika dilewati, tombol *"Simpan ke Database"* akan gagal dengan pesan `relation "pengisian" does not exist`.
> Pastikan juga skrip dijalankan di **project/database yang sama** dengan `DATABASE_URL` yang dipasang di Vercel.

### A2. Siapkan kredensial admin online

Tentukan 3 nilai ini (jangan pakai spasi, catat baik-baik):

- `ADMIN_USER` — mis. `admin` atau `kominfo`
- `ADMIN_PASS` — password kuat, boleh teks biasa atau hash bcrypt (`$2y$...`). Untuk hash bcrypt: `php backend/tools/hash_pass.php "password-anda"` atau `node -e "console.log(require('bcryptjs').hashSync('pass',10))"`
- `ADMIN_SECRET` — string acak panjang **minimal 32 karakter** untuk tanda tangan JWT. Generate:
  ```
  node -e "console.log(require('crypto').randomBytes(32).toString('hex'))"
  # atau
  openssl rand -hex 32
  ```

Contoh `.env` (jangan commit file `.env` asli):
```
DATABASE_URL=postgresql://...
ADMIN_USER=admin
ADMIN_PASS=GantiPasswordKuat123!
ADMIN_SECRET=a3f8c9d2e1b4...32hex...
```

### A3. Import repo ke Vercel

1. Buka **https://vercel.com** → Sign up (pakai GitHub) → **Add New → Project** → **Import** repo `sayaahmaddi-lab/sikonkep`.
2. Vercel otomatis deteksi **Framework: Other** (karena static + Functions) → biarkan default.
3. Buka **Settings → Environment Variables** → tambah 4 variabel satu per satu (Environment: **Production** + **Preview**):
   - `DATABASE_URL`
   - `ADMIN_USER`
   - `ADMIN_PASS`
   - `ADMIN_SECRET`
4. Klik **Deploy** → tunggu ±1 menit hingga muncul **Congratulations**.
5. Buka URL yang diberikan Vercel, mis. `https://sikonkep-xxx.vercel.app`:
   - Formulir: `.../formulir-daftar-kepentingan-pribadi.html`
   - Login admin: `.../login.html` (atau `.../login`) → masuk dengan `ADMIN_USER`/`ADMIN_PASS` → otomatis ke `.../admin.html`
   - Cek `.../api/admin?action=list` harus minta token (401 bila tanpa login — tanda aman).

### A4. Uji alur online

1. Buka formulir → isi Nama, NIP, beberapa tabel A–D, jawab E/F, gambar tanda tangan → **Simpan ke Database** → harus muncul "Tersimpan ke database (ID #...)".
2. Buka `login.html` → login → dashboard `admin.html` → data baru harus muncul → klik **Lihat** → ubah **Status** → **Simpan Status** → cek export CSV.

### A5. Troubleshooting Vercel + Neon

| Gejala | Solusi |
|---|---|
| `DATABASE_URL belum diatur` | Cek Vercel → Settings → Env Vars, pastikan `DATABASE_URL` ada di **Production**, lalu **Redeploy** (Deployments → ⋯ → Redeploy). |
| `Gagal menyimpan: ... self-signed certificate` | Pastikan `DATABASE_URL` pakai `?sslmode=require` dan `api/_lib.js` sudah `ssl:{rejectUnauthorized:false}` (sudah). |
| `401 Belum login` terus | Token kadaluarsa (8 jam) → login ulang di `login.html`. Pastikan `ADMIN_SECRET` sama saat login dan saat verifikasi (jangan ganti di tengah sesi tanpa redeploy). |
| `404 /api/simpan` | Pastikan `vercel.json` ada dan `api/simpan.js` ada. Cek tab **Functions** di dashboard Vercel. |
| Tanda tangan kosong di admin | Pastikan menggambar dulu sebelum klik Simpan, dan payload `ttd` <5 MB. |

---

## OPSI B — Pemasangan di XAMPP (lokal / intranet kantor)

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

### Versi XAMPP (PHP)
- **Login**: kunjungi `backend/admin/login.php` atau langsung buka `backend/admin/`.
  Masukkan `ADMIN_USER` dan `ADMIN_PASS` yang diatur di `config.php`.
- **Logout**: klik tombol **"Keluar"** di pojok kanan atas, atau buka `backend/admin/logout.php`.
- **Sesi login** berlaku maksimal **8 jam** (bisa diubah lewat `ADMIN_SESSION_LIFETIME`).
- **Anti brute-force**: setelah **5× gagal login** (bisa diubah lewat `ADMIN_MAX_ATTEMPTS`),
  IP akan diblokir sementara selama ±1 menit.
- **CSRF token**: semua form POST (login & ubah status) memakai token acak
- **Header keamanan**: `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`
- Cookie `HttpOnly` + `SameSite=Lax`, sesi diregenerasi berkala.

### Versi Vercel (Node + JWT)
- Login via `login.html` → `POST /api/admin?action=login` → mengembalikan **JWT** (8 jam).
- Semua endpoint admin (`list`, `get`, `status`, `export`) wajib header `Authorization: Bearer <token>`.
- Token ditandatangani dengan `ADMIN_SECRET` (HS256) — ganti secara berkala dan redeploy.
- Tanpa token valid → `401 Belum login`.
- Admin online dan admin XAMPP saling independen (kredensial diatur terpisah).

---

## Cara Kerja Aliran Data

### Online (Vercel + Neon)
```
Browser (formulir + tanda tangan digital)
        │ 1. Klik "Simpan ke Database" (di https://xxx.vercel.app)
        │    → fetch POST /api/simpan (JSON)
        ▼
api/simpan.js (Node.js + pg)
        │ 2. Validasi, INSERT ke Neon PostgreSQL → RETURNING id
        ▼
Neon: database → tabel pengisian
        ▲
        │ 3. Admin buka /login.html → /admin.html
        │    → fetch /api/admin?action=list (Bearer JWT)
api/admin.js  → query SELECT * FROM pengisian
```

### Lokal (XAMPP + MySQL)
```
Browser (formulir + tanda tangan digital)
        │ 1. Klik "Simpan ke Database" (di http://localhost/...)
        │    → fetch POST backend/simpan.php
        ▼
backend/simpan.php (PHP + PDO)
        │ 2. Validasi, INSERT ke MySQL
        ▼
MySQL: database sikonkep → tabel pengisian
        ▲
backend/admin/index.php & detail.php (PHP sesi)
```

---

## Catatan Penting

### Tanda tangan
- Tanda tangan digambar di atas **canvas**, lalu dikirim sebagai **gambar PNG (base64)**
  dan disimpan di kolom `ttd` (TEXT/LONGTEXT). `canvas.toDataURL('image/png')`.
- Maksimal ukuran tanda tangan yang diterima: **5 MB** (diatur di `simpan.php` & `api/simpan.js`).

### Keamanan
- Semua query memakai **prepared statements** (PDO di PHP, `pg` parameterised di Node) → aman dari SQL injection.
- Nama/no input disaring & dibatasi panjangnya.
- Untuk produksi resmi, integrasikan dengan SSO/portal kepegawaian daerah & pakai HTTPS (Vercel sudah HTTPS otomatis).

### Kredensial
- Jangan commit file `.env` ke Git. Gunakan `.env.example` sebagai template.
- `backend/config.php` jangan diakses publik — di Vercel file `backend/` tidak terekspos sebagai endpoint (hanya `api/`).

---

## Troubleshooting Ringkas

| Gejala | Penyebab / Solusi |
|---|---|
| Tombol "Simpan ke Database" muncul "Tidak dapat terhubung ke server" | Backend belum aktif: pastikan folder disalin ke `htdocs` dan Apache/MySQL berjalan (lokal), atau cek Functions di Vercel (online). |
| Muncul "Gagal terhubung ke database" | Cek `backend/config.php` (lokal) atau `DATABASE_URL` di Vercel → Env Vars. Pastikan database sudah dibuat (`schema.sql` atau `neon-schema.sql`). |
| Muncul "Kolom wajib belum diisi: nama, nip" | Nama & NIP harus diisi di bagian identitas formulir. |
| Data masuk tapi tanda tangan kosong | Tanda tangan belum digambar. Gambar dulu di pad sebelum klik simpan. |
| Huruf berantakan di CSV | Sudah diberi BOM UTF-8; buka CSV dengan Excel lalu pilih import UTF-8. |
| `401` di admin.html | Sesi habis — login ulang di `login.html`. Cek `ADMIN_SECRET` konsisten. |
