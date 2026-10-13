-- SOKAB — hasil pemeriksaan cap tanggal/jam/koordinat pada foto bukti (peringatan di halaman Entry).
-- Nilai: 'ok' (cap lengkap) | 'chat' (screenshot chat, tanggal terbaca) | 'kurang:tanggal,jam,koordinat lokasi' | NULL (belum diperiksa).
-- Jalankan sekali di phpMyAdmin. Tanpa kolom ini aplikasi tetap jalan; label hanya tidak tersimpan setelah halaman dimuat ulang.
ALTER TABLE ck_ro_bukti_foto ADD COLUMN cap_status VARCHAR(80) DEFAULT NULL;
