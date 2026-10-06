-- SOKAB — kolom iku_kode untuk foto yang tidak terikat ke RO
-- (poin tindak lanjut: ro_master_id -1..-999, uraian tambahan: <= -1000).
-- Jalankan sekali di phpMyAdmin kalau tabel ck_ro_bukti_foto sudah ada sebelumnya.
ALTER TABLE ck_ro_bukti_foto ADD COLUMN iku_kode VARCHAR(20) DEFAULT NULL AFTER periode_id;
