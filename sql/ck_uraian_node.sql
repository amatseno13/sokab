-- SOKAB — Uraian kegiatan per RO: Kegiatan → (opsional) Sub Kegiatan → Tahapan.
--   kegiatan : parent_id NULL
--   sub      : parent = kegiatan
--   tahap    : parent = kegiatan ATAU sub (boleh langsung dari kegiatan)
-- Foto node disimpan di ck_ro_bukti_foto dengan ro_master_id = -(10000000 + id node).
-- Prasyarat: sql/ck_ro_bukti_foto_iku.sql (kolom iku_kode) sudah dijalankan.

CREATE TABLE IF NOT EXISTS ck_uraian_node (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    periode_id   INT NOT NULL,
    iku_kode     VARCHAR(20) NOT NULL,
    ro_master_id INT NOT NULL,
    parent_id    INT DEFAULT NULL,
    tipe         ENUM('kegiatan','sub','tahap') NOT NULL,
    narasi       LONGTEXT DEFAULT NULL,
    urutan       INT NOT NULL DEFAULT 0,
    old_key      INT DEFAULT NULL,
    updated_by   INT DEFAULT NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_iku_periode (iku_kode, periode_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Migrasi uraian lama (satu tingkat, id -(1000000 + RO*1000 + urutan)) → kegiatan ──
-- Aman dijalankan ulang: yang sudah dimigrasi sudah tidak berkunci lama.
INSERT INTO ck_uraian_node (periode_id, iku_kode, ro_master_id, tipe, narasi, urutan, old_key)
SELECT periode_id, iku_kode, FLOOR((-ro_master_id - 1000000) / 1000), 'kegiatan', narasi,
       MOD(-ro_master_id - 1000000, 1000), ro_master_id
FROM ck_entry_ro WHERE ro_master_id <= -1000000 AND ro_master_id > -10000000;

INSERT INTO ck_uraian_node (periode_id, iku_kode, ro_master_id, tipe, narasi, urutan, old_key)
SELECT DISTINCT f.periode_id, f.iku_kode, FLOOR((-f.ro_master_id - 1000000) / 1000), 'kegiatan', NULL,
       MOD(-f.ro_master_id - 1000000, 1000), f.ro_master_id
FROM ck_ro_bukti_foto f
WHERE f.ro_master_id <= -1000000 AND f.ro_master_id > -10000000
  AND NOT EXISTS (SELECT 1 FROM ck_uraian_node n WHERE n.old_key = f.ro_master_id AND n.periode_id = f.periode_id);

UPDATE ck_ro_bukti_foto f JOIN ck_uraian_node n ON n.old_key = f.ro_master_id AND n.periode_id = f.periode_id
SET f.ro_master_id = -(10000000 + n.id)
WHERE f.ro_master_id <= -1000000 AND f.ro_master_id > -10000000;

DELETE FROM ck_entry_ro WHERE ro_master_id <= -1000000 AND ro_master_id > -10000000;
