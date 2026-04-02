# SOKAB - SAKIP Online BPS Kota Bima

Sistem manajemen SAKIP berbasis web untuk BPS Kota Bima.

## 📦 Struktur File

```
sokab/
├── sokab_complete.sql      # Database lengkap (IMPORT INI!)
├── README.md                # File ini
├── index.php                # Halaman login
├── dashboard.php            # Halaman utama
├── reset_password.php       # Reset password
├── config/
│   └── database.php         # Konfigurasi database
├── includes/
│   ├── check_session.php
│   ├── login.php
│   ├── logout.php
│   └── user_management.js
├── api/
│   ├── dokumen.php
│   ├── ikss.php             # API IKSS (fitur baru dengan CRUD!)
│   ├── jadwal.php
│   ├── lakin.php
│   ├── permindok.php
│   └── users.php
├── assets/
│   ├── css/
│   │   └── login.css
│   └── js/
│       └── ikss_functions.js  # JavaScript terpisah untuk IKSS
└── uploads/
    ├── dokumen/
    ├── lakin/
    └── permindok/
```

## 🚀 Instalasi

### 1. Database
```sql
-- phpMyAdmin → Import → sokab_complete.sql
-- File ini sudah include:
-- - 7 tabel lengkap
-- - 17 IKSS dari Perjanjian Kinerja 2026
-- - User admin default
```

### 2. Konfigurasi
Edit `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'sokab_db');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### 3. Copy File
```bash
Copy folder sokab ke: /Applications/XAMPP/htdocs/sokab
```

### 4. Permissions
```bash
chmod 755 uploads/dokumen uploads/lakin uploads/permindok
```

### 5. Login
```
URL: http://localhost/sokab
Username: admin
Password: admin123
```

## ✨ Fitur IKSS (Baru!)

### Struktur Tabel dari Perjanjian Kinerja 2026
- **17 IKSS** dengan struktur:
  - Nomor
  - Sasaran Kegiatan (dari PDF)
  - Indikator Kinerja (dari PDF)
  - Link Dokumen Sumber (dengan tombol Edit di dalam kolom)
  - Link Bukti Tindak Lanjut TW Sebelumnya (dengan tombol Edit di dalam kolom)

### Filter
- **Tahun:** Dropdown dinamis (2020 - sekarang + 5 tahun)
- **Triwulan:** TW I, II, III, IV (tombol)

### Fitur User
- Edit link dokumen sumber (per triwulan)
- Edit link tindak lanjut (per triwulan)
- Link berbeda tiap triwulan
- Terima semua cloud storage (Google Drive, OneDrive, Dropbox, dll)

### Fitur Admin
- **Kelola IKSS:** Tombol hijau "⚙️ Kelola IKSS"
- **Tambah IKSS:** Buat IKSS baru
- **Edit IKSS:** Ubah sasaran, indikator, target
- **Hapus IKSS:** Soft delete (data tetap ada di database)

## 🎯 Cara Pakai IKSS

### User Biasa:
1. Login → Monitoring Capaian Kinerja
2. Scroll ke bawah → Card "📊 Tabel IKSS"
3. Filter: Pilih tahun (dropdown) + triwulan (TW I/II/III/IV)
4. Klik tombol **Edit** di dalam kolom link
5. Modal muncul → Isi link → Simpan
6. Ganti triwulan → Link berbeda per TW ✅

### Admin:
1. Klik tombol "⚙️ Kelola IKSS"
2. Modal muncul dengan tabel semua IKSS
3. **Tambah:** Klik "➕ Tambah IKSS" → Isi form → Simpan
4. **Edit:** Klik tombol "Edit" → Ubah data → Simpan
5. **Hapus:** Klik tombol "Hapus" → Konfirmasi → IKSS dihapus (soft delete)

## 📊 Database Tables

- `users` - Pengguna sistem
- `dokumen` - Dokumen SAKIP
- `lakin` - Laporan Akuntabilitas
- `permindok` - Permintaan dokumen
- `jadwal` - Jadwal kegiatan
- `ikss_master` - **17 IKSS (pre-filled dari PDF Perjanjian Kinerja 2026)**
- `ikss_links` - **Link dokumen per IKSS per triwulan (TANPA kolom tahun)**

## 🔧 Arsitektur IKSS

### Database
```sql
ikss_master:
- id, nomor, sasaran_kegiatan, indikator_kinerja, target, 
  created_by, is_active, created_at, updated_at

ikss_links:
- id, ikss_id, triwulan, link_dokumen_sumber, 
  link_tindak_lanjut, updated_by, updated_at
- UNIQUE KEY (ikss_id, triwulan)
```

### API Endpoints
```
GET  /api/ikss.php?action=list&triwulan=TW%20I
GET  /api/ikss.php?action=detail&ikss_id=1&triwulan=TW%20I
POST /api/ikss.php?action=update_link
GET  /api/ikss.php?action=list_all (admin only)
POST /api/ikss.php?action=create_ikss (admin only)
POST /api/ikss.php?action=update_ikss (admin only)
POST /api/ikss.php?action=delete_ikss (admin only)
```

### JavaScript (File Terpisah)
**File:** `assets/js/ikss_functions.js`

**Functions:**
- `generateYearOptions()` - Generate dropdown tahun dinamis
- `loadIKSSData(triwulan)` - Load data IKSS
- `renderIKSSTable(data)` - Render tabel
- `editLinkDokumen(id, sasaran, link)` - Edit link dokumen
- `editLinkTindakLanjut(id, sasaran, link)` - Edit link tindak lanjut
- `kelolaIKSS()` - Modal kelola (admin)
- `tambahIKSS()` - Tambah IKSS baru (admin)
- `editIKSSMaster(id)` - Edit IKSS (admin)
- `hapusIKSS(id)` - Hapus IKSS (admin)

## 💡 Perbedaan dengan Versi Lama

### Database:
- ❌ Kolom `tahun` di `ikss_links` **DIHAPUS**
- ✅ Link per triwulan saja (bukan per tahun + triwulan)
- ✅ Struktur dari PDF: Sasaran Kegiatan + Indikator Kinerja + Target

### UI:
- ✅ Tombol Edit **di dalam kolom** link (bukan kolom terpisah)
- ✅ 2 modal edit (1 untuk dokumen, 1 untuk tindak lanjut)
- ✅ Dropdown tahun **dinamis** (2020 - sekarang + 5)
- ✅ Tombol "Kelola IKSS" untuk admin

### Fitur:
- ✅ CRUD IKSS lengkap (admin)
- ✅ JavaScript terpisah (mudah maintain)
- ✅ Semua modal baru (3 modal untuk edit, 1 modal untuk kelola, 1 modal untuk form)

## 🐛 Troubleshooting

### IKSS tidak muncul?
1. Klik menu "Monitoring Capaian Kinerja"
2. Scroll ke bawah
3. F12 → Console → Cek error
4. Pastikan file `assets/js/ikss_functions.js` ter-load

### Data tidak sesuai PDF?
1. Cek: `SELECT * FROM ikss_master;`
2. Harusnya ada 17 rows dengan:
   - sasaran_kegiatan
   - indikator_kinerja
   - target

### Modal tidak muncul?
1. F12 → Console → Cek error
2. Pastikan ID modal benar:
   - `modalEditLinkDokumen`
   - `modalEditLinkTindakLanjut`
   - `modalKelolaIKSS`
   - `modalFormIKSS`

### Tombol Kelola IKSS tidak muncul?
- Hanya muncul untuk role `admin`
- Login sebagai admin untuk melihat tombol

---

**Developed for BPS Kota Bima** - SAKIP Online 2026 v2.0
