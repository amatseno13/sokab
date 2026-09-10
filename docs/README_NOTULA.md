# SOKAB — Generate Notula Monitoring Kinerja

Menjalankan `generate_notula.py` langsung dari dalam aplikasi. User cukup
**upload file `excel_FRA.xlsx`** lewat menu di SOKAB, lalu notula `.docx`
langsung terunduh — tidak perlu lagi buka terminal.

---

## Alur

```
Browser  ──►  pages/capaian/notula.php
                 │
                 │ ① upload excel_FRA.xlsx (multipart)
                 ▼
           api/notula_api.php  ── simpan ke tools/notula/tmp/, beri token
                 │
                 │ jalankan: generate_notula.py --excel ... --inspect
                 ▼
           balikan JSON isi Excel  ──►  tabel preview di layar
                 │
                 │ ② user cek data, isi Informasi Rapat, klik Generate
                 ▼
           api/notula_api.php  ── tulis meta.json
                 │
                 │ jalankan: generate_notula.py --excel ... --meta ... --template ... --output ...
                 ▼
           file .docx  ──►  stream ke browser (auto-download)
                            file upload + temp dihapus otomatis
```

Template Word dipakai apa adanya lewat python-docx, jadi format resmi BPS terjaga —
PHP tidak menyentuh XML dokumen sama sekali.

---

## Pemasangan

### 1. Salin file

```
sokab/
├── api/notula_api.php                          ← baru
├── pages/capaian/notula.php                    ← baru
└── tools/
    └── notula/
        ├── generate_notula.py                  ← baru
        ├── template/word_FRA.docx              ← salin template resmi ke sini
        └── tmp/                                ← folder kosong, harus writable
```

### 2. Library Python

```bash
pip3 install python-docx openpyxl
```

### 3. Izin folder temp

```bash
chmod -R 775 /Applications/XAMPP/xamppfiles/htdocs/sokab/tools/notula/tmp
```

### 4. (Opsional) Tabel penyimpan informasi rapat

Jalankan `sql/ck_notula_meta.sql` di phpMyAdmin. Tabel ini hanya menyimpan
hari/tanggal, waktu, tempat, pimpinan rapat, kepala satker, dan notulis — hal
yang tidak ada di Excel — supaya tidak perlu diketik ulang tiap kali.
Tanpa tabel ini fitur tetap jalan, hanya saja field tersebut harus diisi manual
setiap kali generate.

### 5. Patch `dashboard.php`

Ikuti `docs/PATCH_dashboard.md` (3 sisipan kecil).

---

## Cara pakai

1. Sidebar → **Pengukuran → Generate Notula**
2. Seret `excel_FRA.xlsx` ke area upload (atau klik untuk memilih)
3. Cek tabel preview — berapa IKU terbaca, mana yang datanya masih kosong
4. Pilih **Triwulan** yang mau dicetak (penting, lihat catatan di bawah)
5. Isi **Informasi Rapat** → *Simpan* (cukup sekali per triwulan)
6. Klik **Generate & Unduh Notula**

---

## ⚠️ Perbaikan penting: kolom triwulan

Di script lama, kolom yang dibaca di-hardcode ke index 12 / 16 / 20 / 24 —
yaitu kolom M, Q, U, Y. Di `excel_FRA.xlsx`, keempat kolom itu adalah **TW I**:

| Blok | Kolom | Isi |
|---|---|---|
| Alokasi Target (Kumulatif) | M–P | TW I, II, III, IV |
| Realisasi (Kumulatif) | Q–T | TW I, II, III, IV |
| Capaian thd Target Triwulanan | U–X | TW I, II, III, IV |
| Capaian thd Target Setahun | Y–AB | TW I, II, III, IV |

Artinya script lama **selalu mencetak angka TW I**, apa pun triwulan yang
dimaksud. Versi ini menggeser kolom sesuai pilihan triwulan di dropdown, jadi
memilih TW III benar-benar membaca kolom TW III.

Bisa dicek sendiri di preview: dengan file yang kamu kirim, TW I–III semuanya
bernilai 0 dan TW IV bernilai 100 — sesuai isi Excel-nya.

Sheet juga tidak lagi wajib bernama persis `LK_Kabkot`; kalau tidak ketemu,
script mencari sheet yang diawali "LK", dan dropdown sheet tetap bisa diganti manual.

---

## Yang dibaca dari Excel

| Data | Lokasi |
|---|---|
| Satuan kerja | E3 |
| Nilai SAKIP | E4 |
| Predikat | E5 |
| Tujuan | kolom A, baris berawalan `T1:` / `T2:` / `T3:` |
| Sasaran | kolom B (kode) + C (nama) |
| IKU | kolom D (kode) + E (nama), dengan kolom H = `IKU` |
| Target PK / Satuan | K / L |
| Angka triwulanan | M–P, Q–T, U–X, Y–AB (digeser sesuai TW) |
| Kendala / Solusi / RTL | AC / AD / AE |
| PIC / Batas waktu | AF / AG |
| Link bukti / Link TL sebelumnya | AH / AI |

Pemetaan ke tabel template pakai kode tanpa titik: `1.1.1.1` → `1111`.
Ke-17 IKU di file kamu cocok satu-satu dengan 17 tabel di `word_FRA.docx` —
sudah diuji, semuanya terisi.

---

## Mode terminal (masih bisa)

```bash
# lihat isi Excel tanpa membuat dokumen
python3 generate_notula.py --excel excel_FRA.xlsx --triwulan III --inspect

# generate
python3 generate_notula.py \
  --excel excel_FRA.xlsx --sheet LK_Kabkot --triwulan III \
  --template template/word_FRA.docx \
  --output Notula_TW3.docx
```

Bedanya dengan versi lamamu: path lewat argumen, bukan di-hardcode di dalam file.

---

## Kalau gagal

| Gejala | Penyebab & solusi |
|---|---|
| Banner merah "Python3 tidak terdeteksi" | `which python3`, lalu isi manual di baris pertama array `$kandidat` pada `findPython()` di `api/notula_api.php`. PHP di XAMPP jalan sebagai user `daemon` dengan `$PATH` berbeda dari terminalmu. |
| "shell_exec dinonaktifkan" | Hapus `shell_exec` dari `disable_functions` di `php.ini`, restart Apache. |
| "Folder tmp tidak bisa ditulis" | `chmod -R 775 tools/notula/tmp` |
| "Ukuran file melebihi upload_max_filesize" | Naikkan `upload_max_filesize` dan `post_max_size` di `php.ini` (mis. `20M`), restart Apache. Batas PHP saat ini ditampilkan di banner hijau. |
| "0 IKU terbaca" | Sheet yang dipilih salah, atau kolom H tidak berisi teks `IKU`. Coba ganti sheet lewat dropdown. |
| Toast menyebut "IKU tanpa tabel di template" | Ada kode IKU di Excel yang tidak punya tabel padanan di `word_FRA.docx`. Data IKU itu dilewati; sisanya tetap terisi. |
| Error Python | Pesan lengkap masuk ke error log Apache, diawali `[SOKAB notula]`. |

---

## Catatan keamanan

- File upload divalidasi tiga lapis: ekstensi, ukuran, dan isi arsip ZIP
  (`xl/workbook.xml` harus ada) — jadi file lain yang di-rename jadi `.xlsx` ditolak.
- File disimpan dengan nama acak; browser tidak pernah menerima path aslinya,
  hanya token 32 karakter yang dipetakan lewat `$_SESSION`. User lain tidak bisa
  mengakses file upload orang lain meski menebak token.
- Semua argumen shell dibungkus `escapeshellarg()`.
- File upload dan hasil `.docx` dihapus setelah dipakai; sisa file lebih dari
  1 jam dibersihkan otomatis tiap kali ada upload baru.
- Kalau ingin membatasi hanya admin, tambahkan setelah blok definisi fungsi di
  `api/notula_api.php`:

  ```php
  if (!isAdmin()) json_err('Hanya admin yang dapat generate notula', 403);
  ```
