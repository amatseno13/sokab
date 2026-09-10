-- SOKAB — Foto bukti dukung per Rincian Output (RO), per periode.
-- Dipakai oleh Generate Dokumen Sumber (lihat tools/notula/generate_dokumen_sumber.py).
-- Jalankan sekali di phpMyAdmin / mysql client kalau belum ada.

CREATE TABLE IF NOT EXISTS ck_ro_bukti_foto (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    ro_master_id  INT NOT NULL,
    periode_id    INT NOT NULL,
    file_path     VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) DEFAULT NULL,
    keterangan    VARCHAR(255) DEFAULT NULL,
    urutan        INT NOT NULL DEFAULT 0,
    uploaded_by   INT DEFAULT NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_ro_periode (ro_master_id, periode_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
