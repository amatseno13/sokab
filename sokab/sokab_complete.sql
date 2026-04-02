-- ============================================================
-- SOKAB (SAKIP Online BPS Kota Bima) - Complete Database
-- ============================================================
-- Database: sokab_db
-- Versi: 1.0 (Complete)
-- Tanggal: 2026-04-01
-- ============================================================

-- Create database jika belum ada
CREATE DATABASE IF NOT EXISTS sokab_db;
USE sokab_db;

-- ============================================================
-- TABEL 1: users (Pengguna)
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin
INSERT INTO `users` (`username`, `password`, `nama_lengkap`, `role`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator SOKAB', 'admin');
-- Password: admin123

-- ============================================================
-- TABEL 2: dokumen_links (Dokumen SAKIP - Cloud Link atau File Upload)
-- ============================================================
CREATE TABLE IF NOT EXISTS `dokumen_links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `menu_key` varchar(50) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `tahun` int(4) DEFAULT NULL,
  `sub_kategori` varchar(100) DEFAULT NULL,
  `url_gdrive` text,
  `file_path` varchar(255) DEFAULT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `filesize` bigint(20) DEFAULT NULL,
  `upload_method` enum('gdrive','file') DEFAULT 'gdrive',
  `keterangan` text,
  `urutan` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `menu_key` (`menu_key`),
  KEY `tahun` (`tahun`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABEL 3: lakin_files (Laporan Akuntabilitas Kinerja)
-- ============================================================
CREATE TABLE IF NOT EXISTS `lakin_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `judul` varchar(255) NOT NULL,
  `tahun` int(4) NOT NULL,
  `tipe` enum('draft','final') NOT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `filesize` bigint(20) DEFAULT NULL,
  `keterangan` text,
  `uploaded_by` int(11) DEFAULT NULL,
  `upload_method` enum('file','gdrive') DEFAULT 'file',
  `gdrive_link` text,
  `uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tahun` (`tahun`),
  KEY `tipe` (`tipe`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABEL 4: permindok (Permintaan Dokumen)
-- ============================================================
CREATE TABLE IF NOT EXISTS `permindok` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_dokumen` varchar(255) NOT NULL,
  `kategori` varchar(50) DEFAULT NULL,
  `tahun` int(4) DEFAULT NULL,
  `triwulan` varchar(10) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `gdrive_link` text,
  `requested_by` int(11) DEFAULT NULL,
  `requested_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_by` int(11) DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `catatan` text,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `requested_by` (`requested_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABEL 5: jadwal_sakip (Jadwal SAKIP)
-- ============================================================
CREATE TABLE IF NOT EXISTS `jadwal_sakip` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `judul` varchar(255) NOT NULL,
  `deskripsi` text,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `kategori` varchar(50) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tanggal_mulai` (`tanggal_mulai`),
  KEY `kategori` (`kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABEL 6: ikss_master (Master IKSS)
-- ============================================================
CREATE TABLE IF NOT EXISTS `ikss_master` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nomor` varchar(10) NOT NULL,
  `sasaran_kegiatan` text NOT NULL,
  `indikator_kinerja` text NOT NULL,
  `target` varchar(50) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert 17 IKSS dari Perjanjian Kinerja 2026
INSERT INTO `ikss_master` (`nomor`, `sasaran_kegiatan`, `indikator_kinerja`, `target`) VALUES
('1', 'Terwujudnya Penyediaan Data dan Insight Statistik Kependudukan dan Ketenagakerjaan yang Berkualitas dan Terpercaya', 'Persentase Publikasi/Laporan Statistik Kependudukan dan Ketenagakerjaan yang Tersedia Tepat Waktu', '100 Persen'),
('2', 'Terwujudnya Penyediaan Data dan Insight Statistik Kesejahteraan Rakyat yang Berkualitas dan Terpercaya', 'Persentase Publikasi/Laporan Statistik Kesejahteraan Rakyat yang Tersedia Tepat Waktu', '100 Persen'),
('3', 'Terwujudnya Penyediaan Data dan Insight Statistik Ketahanan Sosial yang Berkualitas dan Terpercaya', 'Persentase Publikasi/Laporan Statistik Ketahanan Sosial yang Tersedia Tepat Waktu', '100 Persen'),
('4', 'Terwujudnya Penyediaan Data dan Insight Statistik Sumber Daya Mineral dan Konstruksi yang Berkualitas dan Terpercaya', 'Persentase Publikasi/Laporan Statistik Sumber Daya Mineral dan Konstruksi yang Tersedia Tepat Waktu', '100 Persen'),
('5', 'Terwujudnya Penyediaan Data dan Insight Statistik Sumber Daya Hayati yang Berkualitas dan Terpercaya', 'Persentase Publikasi/Laporan Statistik Sumber Daya Hayati yang Tersedia Tepat Waktu', '100 Persen'),
('6', 'Terwujudnya Penyediaan Data dan Insight Statistik Industri yang Berkualitas dan Terpercaya', 'Persentase Publikasi/Laporan Statistik Industri yang Tersedia Tepat Waktu', '100 Persen'),
('7', 'Terwujudnya Penyediaan Data dan Insight Statistik Distribusi yang Berkualitas dan Terpercaya', 'Persentase Publikasi/Laporan Statistik Distribusi yang Tersedia Tepat Waktu', '100 Persen'),
('8', 'Terwujudnya Penyediaan Data dan Insight Statistik Harga yang Berkualitas dan Terpercaya', 'Persentase Publikasi/Laporan Statistik Harga yang Tersedia Tepat Waktu', '100 Persen'),
('9', 'Terwujudnya Penyediaan Data dan Insight Statistik Jasa yang Berkualitas dan Terpercaya', 'Persentase Publikasi/Laporan Statistik Jasa yang Tersedia Tepat Waktu', '100 Persen'),
('10', 'Terwujudnya Penyediaan Data dan Insight Statistik Lintas Sektor (Neraca Produksi) yang Berkualitas dan Terpercaya', 'Persentase Publikasi/Laporan Statistik Lintas Sektor (Neraca Produksi) yang Tersedia Tepat Waktu', '100 Persen'),
('11', 'Terwujudnya Penyediaan Data dan Insight Statistik Lintas Sektor (Neraca Pengeluaran) yang Berkualitas dan Terpercaya', 'Persentase Publikasi/Laporan Statistik Lintas Sektor (Neraca Pengeluaran) yang Tersedia Tepat Waktu', '100 Persen'),
('12', 'Terwujudnya Penyediaan Data dan Insight Statistik Lintas Sektor (Analisis & Neraca Satelit) yang Berkualitas dan Terpercaya', 'Persentase Publikasi/Laporan Statistik Lintas Sektor (Analisis & Neraca Satelit) yang Tersedia Tepat Waktu', '100 Persen'),
('13', 'Terwujudnya Peningkatan Kualitas Statistik Sektoral', 'Persentase Penyelesaian Rekomendasi dari Hasil Pembinaan Statistik Sektoral', '108.48 Persen'),
('14', 'Terwujudnya Tata Kelola Pemerintahan Desa yang Baik', 'Persentase Desa Prioritas yang Difasilitasi dalam Penerapan SDGs Desa', '19.51 Persen'),
('15', 'Terwujudnya Kemudahan Akses Data BPS', 'Nilai Indeks Kemudahan Akses Data BPS', '4.09 Poin'),
('16', 'Terwujudnya Dukungan Manajemen dan Fungsi Teknis Lainnya yang Andal dalam Pencapaian Sasaran Strategis BPS', 'Nilai SAKIP BPS', '74.45 Poin'),
('17', 'Terwujudnya SDM BPS yang Profesional, Berintegritas, dan Berdaya Saing', 'Nilai Indeks Implementasi Nilai-nilai BerAKHLAK', '58.7 Persen');

-- ============================================================
-- TABEL 7: ikss_links (Link Dokumen IKSS per Triwulan)
-- ============================================================
CREATE TABLE IF NOT EXISTS `ikss_links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ikss_id` int(11) NOT NULL,
  `tahun` int(4) NOT NULL,
  `triwulan` varchar(10) NOT NULL DEFAULT 'TW I',
  `link_dokumen_sumber` text,
  `link_tindak_lanjut` text,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_ikss_tahun_triwulan` (`ikss_id`,`tahun`,`triwulan`),
  CONSTRAINT `fk_ikss_master` FOREIGN KEY (`ikss_id`) REFERENCES `ikss_master` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SELESAI - Database SOKAB Complete
-- ============================================================

SELECT 'Database SOKAB berhasil dibuat!' AS Status;
