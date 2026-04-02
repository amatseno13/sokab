-- ============================================
-- VERIFIKASI DATABASE SOKAB
-- ============================================

-- Cek semua tabel ada
SELECT 'Checking tables...' AS status;

SELECT 
    CASE 
        WHEN COUNT(*) = 7 THEN '✅ ALL TABLES EXIST'
        ELSE '❌ MISSING TABLES'
    END AS table_check,
    GROUP_CONCAT(table_name ORDER BY table_name) AS tables
FROM information_schema.tables 
WHERE table_schema = 'sokab_db';

-- Cek struktur ikss_master
SELECT 'Checking ikss_master structure...' AS status;

SELECT column_name 
FROM information_schema.columns 
WHERE table_schema = 'sokab_db' 
  AND table_name = 'ikss_master'
ORDER BY ordinal_position;

-- Cek data ikss_master
SELECT 'Checking ikss_master data...' AS status;

SELECT COUNT(*) AS total_ikss FROM ikss_master;
SELECT nomor, LEFT(sasaran_kegiatan, 50) AS sasaran, target 
FROM ikss_master 
LIMIT 5;

-- Cek tabel lain
SELECT 'Checking other tables...' AS status;

SELECT 'users' AS tabel, COUNT(*) AS jumlah FROM users
UNION ALL
SELECT 'dokumen_links', COUNT(*) FROM dokumen_links
UNION ALL
SELECT 'lakin_files', COUNT(*) FROM lakin_files
UNION ALL
SELECT 'jadwal_sakip', COUNT(*) FROM jadwal_sakip
UNION ALL
SELECT 'ikss_links', COUNT(*) FROM ikss_links;

SELECT '✅ VERIFICATION COMPLETE' AS status;
