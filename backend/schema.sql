-- =============================================================
-- Skema Database: Formulir Daftar Kepentingan Pribadi
-- Jalankan lewat phpMyAdmin (tab SQL) atau: mysql -u root < schema.sql
-- =============================================================

CREATE DATABASE IF NOT EXISTS sikonkep
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE sikonkep;

CREATE TABLE IF NOT EXISTS pengisian (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nama          VARCHAR(150) NOT NULL DEFAULT '',
  nip           VARCHAR(50)  NOT NULL DEFAULT '',
  pangkat       VARCHAR(100) NOT NULL DEFAULT '',
  jabatan       VARCHAR(150) NOT NULL DEFAULT '',
  perangkat     VARCHAR(150) NOT NULL DEFAULT '',
  unit_kerja    VARCHAR(150) NOT NULL DEFAULT '',
  tanggal_isi   VARCHAR(50)  NOT NULL DEFAULT '',

  -- Bagian A - F disimpan sebagai JSON agar jumlah baris fleksibel (1,2,3,..., dst)
  bagian_a      LONGTEXT,
  bagian_b      LONGTEXT,
  bagian_c      LONGTEXT,
  bagian_d      LONGTEXT,
  bagian_e      LONGTEXT,
  bagian_f      LONGTEXT,

  -- Tanda tangan digital (data URL PNG, base64)
  ttd           LONGTEXT,

  ttd_nama      VARCHAR(150) NOT NULL DEFAULT '',
  ttd_nip       VARCHAR(50)  NOT NULL DEFAULT '',

  -- Untuk pengelolaan oleh admin
  status        VARCHAR(20)  NOT NULL DEFAULT 'BARU',
  catatan_admin VARCHAR(255) NOT NULL DEFAULT '',

  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_nip (nip),
  KEY idx_status (status),
  KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
