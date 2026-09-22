# SiKonKep — Aplikasi Formulir Daftar Kepentingan Pribadi

Aplikasi web untuk mengisi **Formulir Daftar Kepentingan Pribadi** (pencegahan
Konflik Kepentingan) secara digital, lengkap dengan **tanda tangan digital**
(mouse/touch), lalu menyimpan data ke **MySQL (XAMPP/lokal)** atau **Neon PostgreSQL (Vercel/online)**.

## Fitur

- Formulir digital sesuai format asli (Identitas + Bagian A–F + Pernyataan).
- Tanda tangan digital via canvas (mouse / layar sentuh), dengan Undo & Hapus.
- Simpan otomatis di browser (localStorage) agar isian tidak hilang.
- Cetak / Simpan PDF dengan tata letak rapi ukuran A4.
- Simpan ke database (Neon PostgreSQL bila online, MySQL bila XAMPP) via tombol "Simpan ke Database".
- Halaman admin: rekap data, detail isian, ubah status, export CSV — **wajib login**.
- **Dua mode deployment** dalam satu repo:
  - **Lokal (XAMPP)**: `backend/` (PHP + MySQL)
  - **Online (Vercel + Neon)**: `api/` (Node.js) + `neon-schema.sql` + `login.html`/`admin.html`

## Berkas Utama

| Berkas | Fungsi |
|---|---|
| `formulir-daftar-kepentingan-pribadi.html` | Halaman formulir untuk pegawai (otomatis pilih endpoint lokal/online) |
| `login.html` | Login admin online (Vercel + Neon) — JWT |
| `admin.html` | Dashboard admin online (list, detail, ubah status, export CSV) |
| `api/_lib.js` | Helper Neon + JWT + CORS untuk Vercel Functions |
| `api/simpan.js` | Endpoint `POST /api/simpan` — simpan formulir ke Neon |
| `api/admin.js` | Endpoint `/api/admin?action=...` — login, list, get, status, export |
| `neon-schema.sql` | Skrip pembuatan tabel PostgreSQL di Neon |
| `backend/schema.sql` | Skrip pembuatan database & tabel MySQL (XAMPP) |
| `backend/config.php` | Konfigurasi koneksi MySQL + akun admin lokal |
| `backend/simpan.php` | Endpoint penerima & penyimpan data (PHP) |
| `backend/admin/*.php` | Halaman admin lokal (PHP) |
| `vercel.json` | Konfigurasi deploy Vercel |
| `package.json` | Dependensi Vercel Functions (`pg`) |
| `.env.example` | Contoh environment variables untuk Neon/Vercel |
| `PANDUAN.md` | Panduan lengkap pemasangan lokal & online |

## Cara Pakai — Ringkas

### Opsi A — Online (Vercel + Neon) — disarankan untuk publik
1. Buat project di **Neon** (neon.tech) → copy `DATABASE_URL` → **jalankan `neon-schema.sql` di SQL Editor Neon** (wajib! Bila terlewat muncul error `relation "pengisian" does not exist`; alternatif: `npm run setup-db`).
2. Import repo ke **Vercel** → atur Environment Variables: `DATABASE_URL`, `ADMIN_USER`, `ADMIN_PASS`, `ADMIN_SECRET`.
3. Deploy → buka `https://namaproject.vercel.app/formulir-daftar-kepentingan-pribadi.html` untuk mengisi, `.../login.html` untuk admin.

### Opsi B — Lokal (XAMPP + MySQL)
1. Pasang XAMPP (Apache + MySQL), salin folder ini ke `htdocs`.
2. Jalankan `backend/schema.sql` di phpMyAdmin untuk membuat database.
3. Sesuaikan `backend/config.php` dengan kredensial MySQL Anda.
4. Buka `http://localhost/sikonkep/formulir-....html` untuk mengisi,
   dan `http://localhost/sikonkep/backend/admin/` untuk melihat rekap.

Detail lengkap kedua opsi: lihat **`PANDUAN.md`**.
