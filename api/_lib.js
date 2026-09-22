// =============================================================
// Helper bersama untuk Vercel Functions (Neon PostgreSQL + JWT)
// =============================================================
const { Pool } = require('pg');
const crypto = require('crypto');

// ------- Koneksi Neon (singleton) -------
let pool;
function getPool() {
  if (pool) return pool;
  const conn = process.env.DATABASE_URL;
  if (!conn) throw new Error('DATABASE_URL belum diatur di Environment Variables Vercel');
  pool = new Pool({
    connectionString: conn,
    ssl: { rejectUnauthorized: false },
    max: 5,
  });
  return pool;
}

// ------- Persiapan skema otomatis (idempoten) -------
// PENTING: harus selalu sama isinya dengan neon-schema.sql.
// Semua pernyataan memakai IF NOT EXISTS sehingga aman dijalankan berulang.
// Ini jaring pengaman bila neon-schema.sql lupa dijalankan manual di Neon.
const SCHEMA_SQL = `
CREATE TABLE IF NOT EXISTS pengisian (
  id            SERIAL PRIMARY KEY,
  nama          VARCHAR(150) NOT NULL DEFAULT '',
  nip           VARCHAR(50)  NOT NULL DEFAULT '',
  pangkat       VARCHAR(100) NOT NULL DEFAULT '',
  jabatan       VARCHAR(150) NOT NULL DEFAULT '',
  perangkat     VARCHAR(150) NOT NULL DEFAULT '',
  unit_kerja    VARCHAR(150) NOT NULL DEFAULT '',
  tanggal_isi   VARCHAR(50)  NOT NULL DEFAULT '',
  bagian_a      TEXT,
  bagian_b      TEXT,
  bagian_c      TEXT,
  bagian_d      TEXT,
  bagian_e      TEXT,
  bagian_f      TEXT,
  ttd           TEXT,
  ttd_nama      VARCHAR(150) NOT NULL DEFAULT '',
  ttd_nip       VARCHAR(50)  NOT NULL DEFAULT '',
  status        VARCHAR(20)  NOT NULL DEFAULT 'BARU',
  catatan_admin VARCHAR(255) NOT NULL DEFAULT '',
  created_at    TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_pengisian_nip     ON pengisian (nip);
CREATE INDEX IF NOT EXISTS idx_pengisian_status  ON pengisian (status);
CREATE INDEX IF NOT EXISTS idx_pengisian_created ON pengisian (created_at DESC);
`;

let schemaPromise = null;
function ensureSchema() {
  if (!schemaPromise) {
    schemaPromise = getPool()
      .query(SCHEMA_SQL) // multi-statement tanpa parameter (simple query)
      .catch((err) => {
        schemaPromise = null; // gagal -> coba lagi di request berikutnya
        throw err;
      });
  }
  return schemaPromise;
}

async function query(text, params) {
  await ensureSchema(); // pastikan tabel ada sebelum query apa pun
  const p = getPool();
  return p.query(text, params);
}

// ------- CORS -------
function setCors(res) {
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET,POST,PUT,DELETE,OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
  res.setHeader('Access-Control-Max-Age', '86400');
}
function handleOptions(req, res) {
  if (req.method === 'OPTIONS') {
    setCors(res);
    res.statusCode = 204;
    res.end();
    return true;
  }
  setCors(res);
  return false;
}

// ------- Body JSON -------
async function readJsonBody(req) {
  if (req.body !== undefined) {
    if (typeof req.body === 'string') {
      try { return JSON.parse(req.body); } catch { return {}; }
    }
    if (typeof req.body === 'object' && req.body !== null) return req.body;
  }
  // Fallback stream
  return new Promise((resolve) => {
    let data = '';
    req.on('data', (c) => (data += c));
    req.on('end', () => {
      try { resolve(JSON.parse(data || '{}')); } catch { resolve({}); }
    });
    req.on('error', () => resolve({}));
  });
}

// ------- Base64URL -------
function b64urlEncode(buf) {
  return Buffer.from(buf).toString('base64').replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/,'');
}
function b64urlDecode(str) {
  str = str.replace(/-/g,'+').replace(/_/g,'/');
  while (str.length % 4) str += '=';
  return Buffer.from(str, 'base64').toString();
}

// ------- JWT sederhana (HS256, tanpa lib luar) -------
function jwtSign(payload, secret, expiresInSec = 8*3600) {
  const header = { alg: 'HS256', typ: 'JWT' };
  const now = Math.floor(Date.now()/1000);
  const body = { ...payload, iat: now, exp: now + expiresInSec };
  const h = b64urlEncode(JSON.stringify(header));
  const p = b64urlEncode(JSON.stringify(body));
  const sig = crypto.createHmac('sha256', secret).update(h+'.'+p).digest('base64')
    .replace(/\+/g,'-').replace(/\//g,'_').replace(/=+$/,'');
  return `${h}.${p}.${sig}`;
}

function jwtVerify(token, secret) {
  if (!token || typeof token !== 'string') return null;
  const parts = token.split('.');
  if (parts.length !== 3) return null;
  const [h,p,sig] = parts;
  const expected = crypto.createHmac('sha256', secret).update(h+'.'+p).digest('base64')
    .replace(/\+/g,'-').replace(/\//g,'_').replace(/=+$/,'');
  // timingSafeEqual
  const a = Buffer.from(sig);
  const b = Buffer.from(expected);
  if (a.length !== b.length || !crypto.timingSafeEqual(a,b)) return null;
  try {
    const payload = JSON.parse(b64urlDecode(p));
    const now = Math.floor(Date.now()/1000);
    if (payload.exp && now > payload.exp) return null;
    return payload;
  } catch { return null; }
}

// ------- Auth -------
function getSecret() {
  return process.env.ADMIN_SECRET || 'dev-secret-jangan-pakai-di-produksi-ganti-min32karakter';
}

function getAdminCreds() {
  return {
    user: process.env.ADMIN_USER || 'admin',
    pass: process.env.ADMIN_PASS || 'ganti-password-anda',
  };
}

// cek password: mendukung plain text maupun bcrypt $2y$ / $2a$
// bcryptjs tidak di-dependencies agar ringan; kalau hash terdeteksi tapi tidak ada bcryptjs,
// fallback ke perbandingan plain (dan beri warning di log).
function verifyPass(input, stored) {
  if (!stored) return false;
  const isBcrypt = stored.startsWith('$2y$') || stored.startsWith('$2a$') || stored.startsWith('$2b$');
  if (!isBcrypt) {
    // plain
    try { return crypto.timingSafeEqual(Buffer.from(input), Buffer.from(stored)); }
    catch { return input === stored; }
  }
  // bcrypt hash terdeteksi
  try {
    // coba pakai bcryptjs jika ada
    const bcrypt = require('bcryptjs');
    // $2y$ di PHP kompatibel dengan $2a$ di bcryptjs
    const normalized = stored.replace(/^\$2y\$/, '$2a$');
    return bcrypt.compareSync(input, normalized);
  } catch (e) {
    console.warn('ADMIN_PASS berbentuk hash bcrypt tapi bcryptjs tidak terpasang - bandingkan plain fallback');
    return input === stored;
  }
}

function extractBearer(req) {
  const h = req.headers.authorization || req.headers.Authorization || '';
  if (!h) return null;
  const m = String(h).match(/^Bearer\s+(.+)$/i);
  return m ? m[1].trim() : null;
}

function requireAuth(req) {
  const secret = getSecret();
  const token = extractBearer(req);
  if (!token) return null;
  const payload = jwtVerify(token, secret);
  if (!payload || payload.role !== 'admin') return null;
  return payload;
}

function json(res, code, obj) {
  res.statusCode = code;
  res.setHeader('Content-Type', 'application/json; charset=utf-8');
  res.end(JSON.stringify(obj));
}

module.exports = {
  getPool, query, ensureSchema,
  setCors, handleOptions,
  readJsonBody,
  jwtSign, jwtVerify,
  getSecret, getAdminCreds, verifyPass,
  extractBearer, requireAuth,
  json,
  b64urlEncode, b64urlDecode
};
