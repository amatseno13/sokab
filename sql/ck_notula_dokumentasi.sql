-- SOKAB — Foto dokumentasi rapat per periode, ditempel di halaman terakhir Notula.
-- Jalankan sekali di phpMyAdmin / mysql client kalau belum ada.

CREATE TABLE IF NOT EXISTS ck_notula_dokumentasi (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    periode_id    INT NOT NULL,
    file_path     VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) DEFAULT NULL,
    keterangan    VARCHAR(255) DEFAULT NULL,
    urutan        INT NOT NULL DEFAULT 0,
    uploaded_by   INT DEFAULT NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_periode (periode_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
