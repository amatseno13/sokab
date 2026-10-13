-- Perubahan Rincian Output (RO) berlaku mulai TW III 2026 (kunci 20263 = tahun*10 + triwulan).
-- TW I dan II 2026 tidak berubah. Tidak ada baris yang dihapus: RO lama hanya dibatasi sampai TW II.
-- Jalankan SEKALI. Backup database dulu.

ALTER TABLE ck_ro_master
  ADD COLUMN berlaku_dari   INT NULL DEFAULT NULL COMMENT 'tahun*10+triwulan, NULL = sejak awal',
  ADD COLUMN berlaku_sampai INT NULL DEFAULT NULL COMMENT 'tahun*10+triwulan, NULL = tanpa batas';

-- 1) RO yang dibuang mulai TW III: tampil hanya sampai TW II (isian & foto tetap tersimpan).
--    id 2  1.1.1.1  2905 BMA 005 Kependudukan dan Ketenagakerjaan
--    id 3  1.1.3.1  2906 BMA 003 Statistik Kesejahteraan Rakyat   (punya isian + 2 file TW III, jadi tersembunyi di TW III)
--    id 22 1.4.1.3  2896 BMA U01 IPM
--    id 24 1.4.1.3  2896 QMA 006 Big Data
--    id 28 2.7.1.1  2901 CAN 004 Pengembangan Infrastruktur TIK   (punya isian TW III, jadi tersembunyi di TW III)
UPDATE ck_ro_master SET berlaku_sampai = 20262 WHERE id IN (2, 3, 22, 24, 28);

-- 2) RO yang berganti nama mulai TW III: baris lama berakhir TW II, baris baru dari TW III.
--    id 15 → 2900 BMA 005 | id 19 → 2908 BMA 004 | id 23 → 2896 BMA 004 | id 27 → 2897 BMA 004
INSERT INTO ck_ro_master (iku_kode, nama_ro, ro_order, berlaku_dari)
SELECT iku_kode,
       CASE id
         WHEN 15 THEN '2900 BMA 005. Dokumen/Laporan Pengembangan Metodologi Statistik dan Sains Data'
         WHEN 19 THEN '2908 BMA 004. Publikasi/Laporan Statistik Jasa'
         WHEN 23 THEN '2896 BMA 004. Publikasi/Laporan Analisis Statistik dan Neraca Satelit'
         WHEN 27 THEN '2897 BMA 004 Layanan Statistik Terpadu'
       END, ro_order, 20263
FROM ck_ro_master WHERE id IN (15, 19, 23, 27) ORDER BY id;

-- pasangan lama → baru (baris baru = id terbesar dengan iku_kode sama dan berlaku_dari 20263)
DROP TEMPORARY TABLE IF EXISTS _ro_ganti;
CREATE TEMPORARY TABLE _ro_ganti AS
SELECT o.id AS lama, (SELECT MAX(n.id) FROM ck_ro_master n WHERE n.iku_kode = o.iku_kode AND n.berlaku_dari = 20263) AS baru
FROM ck_ro_master o WHERE o.id IN (15, 19, 23, 27);

UPDATE ck_ro_master SET berlaku_sampai = 20262 WHERE id IN (15, 19, 23, 27);

-- isian, foto, dan uraian kegiatan TW III & IV 2026 ikut pindah ke baris baru
UPDATE ck_entry_ro e   JOIN _ro_ganti g ON g.lama = e.ro_master_id JOIN ck_periode p ON p.id = e.periode_id
   SET e.ro_master_id = g.baru WHERE p.tahun = 2026 AND p.triwulan IN ('III', 'IV');
UPDATE ck_ro_bukti_foto f JOIN _ro_ganti g ON g.lama = f.ro_master_id JOIN ck_periode p ON p.id = f.periode_id
   SET f.ro_master_id = g.baru WHERE p.tahun = 2026 AND p.triwulan IN ('III', 'IV');
UPDATE ck_uraian_node u JOIN _ro_ganti g ON g.lama = u.ro_master_id JOIN ck_periode p ON p.id = u.periode_id
   SET u.ro_master_id = g.baru WHERE p.tahun = 2026 AND p.triwulan IN ('III', 'IV');
DROP TEMPORARY TABLE _ro_ganti;

-- 3) RO baru dari TW III: 1.3.1.1 '2902 CAN 004' (yang lain sudah ada: id 29-35).
INSERT INTO ck_ro_master (iku_kode, nama_ro, ro_order, berlaku_dari)
VALUES ('1.3.1.1', '2902 CAN 004. Pengembangan Infrastruktur dan Layanan Teknologi Informasi dan Komunikasi', 6, 20263);

-- 4) Hapus isian TW III/IV 2026 di RO yang dibuang, yang sudah dipindahkan ke "kegiatan tambahan" (dikonfirmasi pemilik):
--    id 3 (1.1.3.1), id 24 (1.4.1.3), id 28 (2.7.1.1). Baris RO-nya dipertahankan karena TW I-II masih memakainya.
--    Catatan: file fisik di uploads/bukti_ro/ tidak terhapus oleh SQL ini.
DELETE e FROM ck_entry_ro e JOIN ck_periode p ON p.id = e.periode_id
 WHERE e.ro_master_id IN (3, 24, 28) AND p.tahun = 2026 AND p.triwulan IN ('III', 'IV');
DELETE f FROM ck_ro_bukti_foto f JOIN ck_periode p ON p.id = f.periode_id
 WHERE f.ro_master_id IN (3, 24, 28) AND p.tahun = 2026 AND p.triwulan IN ('III', 'IV');
DELETE u FROM ck_uraian_node u JOIN ck_periode p ON p.id = u.periode_id
 WHERE u.ro_master_id IN (3, 24, 28) AND p.tahun = 2026 AND p.triwulan IN ('III', 'IV');
