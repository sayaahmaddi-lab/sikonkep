// =============================================================
// Menjalankan neon-schema.sql ke database Neon PostgreSQL.
//
// Pemakaian:
//   1) Isi DATABASE_URL di file .env  (salin dari .env.example), atau
//      set di environment:  setx DATABASE_URL "postgresql://..."  (Windows)
//   2) Jalankan:  npm install   (sekali saja)
//                 npm run setup-db
//
// Aman dijalankan berulang kali (semua CREATE memakai IF NOT EXISTS).
// =============================================================
import { readFileSync, existsSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';
import pg from 'pg';

const here = dirname(fileURLToPath(import.meta.url));
const root = join(here, '..');

// Baca .env sederhana (format KEY=VALUE per baris) bila belum ada di environment
function loadDotEnv(file) {
  if (!existsSync(file)) return;
  for (const line of readFileSync(file, 'utf8').split(/\r?\n/)) {
    const m = line.match(/^\s*([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*)\s*$/);
    if (!m || line.trimStart().startsWith('#')) continue;
    let val = m[2];
    if ((val.startsWith('"') && val.endsWith('"')) || (val.startsWith("'") && val.endsWith("'"))) {
      val = val.slice(1, -1);
    }
    if (process.env[m[1]] === undefined) process.env[m[1]] = val;
  }
}

if (!process.env.DATABASE_URL) loadDotEnv(join(root, '.env'));

const url = process.env.DATABASE_URL;
if (!url) {
  console.error('❌ DATABASE_URL belum diisi. Salin .env.example menjadi .env lalu isi, atau set variabel environment.');
  process.exit(1);
}
if (/user:password@ep-xxx/.test(url)) {
  console.error('❌ DATABASE_URL masih contoh dari .env.example. Isi connection string asli dari dashboard Neon.');
  process.exit(1);
}

const sql = readFileSync(join(root, 'neon-schema.sql'), 'utf8');
const client = new pg.Client({ connectionString: url, ssl: { rejectUnauthorized: false } });

try {
  console.log('🔌 Menghubungkan ke Neon...');
  await client.connect();
  await client.query(sql);
  const { rows } = await client.query('SELECT COUNT(*)::int AS n FROM pengisian');
  console.log('✅ Berhasil! Tabel "pengisian" siap dipakai.');
  console.log(`   Jumlah baris tersimpan saat ini: ${rows[0].n}`);
  console.log('   Silakan coba kembali "Simpan ke Database" di formulir.');
} catch (e) {
  console.error('❌ Gagal:', e.message);
  console.error('   Periksa kembali DATABASE_URL (host, database, password) — harus sama dengan yang di Vercel.');
  process.exitCode = 1;
} finally {
  await client.end().catch(() => {});
}
