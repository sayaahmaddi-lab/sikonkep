-- =============================================================
-- Skema Database Neon PostgreSQL: Formulir Daftar Kepentingan Pribadi
-- Jalankan di Neon Dashboard → SQL Editor, atau:
--   psql "$DATABASE_URL" -f neon-schema.sql
-- =============================================================

CREATE TABLE IF NOT EXISTS pengisian (
  id            SERIAL PRIMARY KEY,
  nama          VARCHAR(150) NOT NULL DEFAULT '',
  nip           VARCHAR(50)  NOT NULL DEFAULT '',
  pangkat       VARCHAR(100) NOT NULL DEFAULT '',
  jabatan       VARCHAR(150) NOT NULL DEFAULT '',
  perangkat     VARCHAR(150) NOT NULL DEFAULT '',
  unit_kerja    VARCHAR(150) NOT NULL DEFAULT '',
  tanggal_isi   VARCHAR(50)  NOT NULL DEFAULT '',

  -- Bagian A - F disimpan sebagai JSON string (fleksibel)
  bagian_a      TEXT,
  bagian_b      TEXT,
  bagian_c      TEXT,
  bagian_d      TEXT,
  bagian_e      TEXT,
  bagian_f      TEXT,

  -- Tanda tangan digital (data URL PNG base64, maks 5 MB)
  ttd           TEXT,

  ttd_nama      VARCHAR(150) NOT NULL DEFAULT '',
  ttd_nip       VARCHAR(50)  NOT NULL DEFAULT '',

  -- Untuk pengelolaan oleh admin
  status        VARCHAR(20)  NOT NULL DEFAULT 'BARU',
  catatan_admin VARCHAR(255) NOT NULL DEFAULT '',

  created_at    TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_pengisian_nip     ON pengisian (nip);
CREATE INDEX IF NOT EXISTS idx_pengisian_status  ON pengisian (status);
CREATE INDEX IF NOT EXISTS idx_pengisian_created ON pengisian (created_at DESC);

-- Contoh: lihat isi
-- SELECT id, nama, nip, status, created_at FROM pengisian ORDER BY created_at DESC LIMIT 10;
