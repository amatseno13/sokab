<?php
/**
 * SOKAB — Notula Monitoring Kinerja API
 * Endpoint: /sokab/api/notula_api.php
 *
 * ALUR UTAMA (upload Excel):
 *   1. POST ?action=upload             → user upload excel_FRA.xlsx, PHP simpan + parse
 *   2. POST ?action=generate           → susun .docx dari file yang tadi di-upload
 *
 * Action lain:
 *   GET  ?action=env_check             → cek python, template, izin folder
 *   GET  ?action=meta_get&periode_id=1 → ambil metadata rapat tersimpan
 *   POST ?action=meta_save             → simpan metadata rapat
 *   GET  ?action=discard&token=...     → hapus file upload
 */

session_start();
require_once __DIR__ . '/../includes/check_session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/docx_notula.php';
require_once __DIR__ . '/../includes/docx_dokumen_sumber.php';
require_once __DIR__ . '/../includes/xlsx_kertas_kerja.php';

// Folder kerja (dulu didefinisikan di python_env.php)
define('SOKAB_TOOLS', realpath(__DIR__ . '/../tools/notula'));
define('SOKAB_TMP',   SOKAB_TOOLS . '/tmp');

/** Pastikan tmp siap dipakai; kembalikan pesan masalah atau null. */
function masalahTmp(): ?string {
    if (!is_dir(SOKAB_TMP)) @mkdir(SOKAB_TMP, 0755, true);
    if (!is_dir(SOKAB_TMP))      return 'Folder tools/notula/tmp tidak ada dan tidak bisa dibuat.';
    if (!is_writable(SOKAB_TMP)) return 'Folder tools/notula/tmp tidak bisa ditulis (chmod 755).';
    return null;
}

/** Buang file lama yang tertinggal. */
function bersihkanTmp(int $ttl = 3600): void {
    if (!is_dir(SOKAB_TMP)) return;
    foreach (glob(SOKAB_TMP . '/*') as $f) {
        if (is_file($f) && basename($f) !== '.gitkeep' && (time() - filemtime($f)) > $ttl) @unlink($f);
    }
}
requireLogin();

// ── Konfigurasi ──────────────────────────────────────────
define('NOTULA_DIR',      SOKAB_TOOLS);
define('NOTULA_TEMPLATE', SOKAB_TOOLS . '/template/word_FRA.docx');
define('NOTULA_TMP',      SOKAB_TMP);
define('MAX_UPLOAD_MB',   15);
define('UPLOAD_TTL',      3600);   // file upload dibuang setelah 1 jam
define('DOKSUM_TEMPLATE_KOSONG', SOKAB_TOOLS . '/template/blank_dokumen_sumber.docx');

$db  = getDBConnection();
$act = $_GET['action'] ?? $_POST['action'] ?? '';

function json_ok($data = []) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => true] + $data, JSON_UNESCAPED_UNICODE);
    exit;
}
function json_err($msg, $code = 400) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}
function body(): array {
    $raw = file_get_contents('php://input');
    return $raw ? (json_decode($raw, true) ?? []) : [];
}

/** Pastikan prasyarat generate terpenuhi. */
function pastikanSiap(): void {
    if (!class_exists('ZipArchive'))   json_err('Ekstensi PHP "zip" tidak aktif. Aktifkan lewat hPanel → PHP Configuration.', 500);
    if (!class_exists('DOMDocument'))  json_err('Ekstensi PHP "xml/dom" tidak aktif.', 500);
    if (!file_exists(NOTULA_TEMPLATE)) json_err('Template word_FRA.docx tidak ditemukan di ' . NOTULA_TEMPLATE, 500);
    if ($m = masalahTmp())             json_err($m, 500);
}

/** Ambil path file upload dari token — hanya token milik sesi ini yang diterima. */
function pathDariToken(string $token): string {
    if (!preg_match('/^[a-f0-9]{32}$/', $token)) json_err('Token upload tidak valid.');
    $daftar = $_SESSION['notula_uploads'] ?? [];
    if (!isset($daftar[$token]))                json_err('File upload tidak ditemukan. Silakan upload ulang.');
    $path = $daftar[$token];
    if (!file_exists($path))                    json_err('File upload sudah kedaluwarsa. Silakan upload ulang.');
    return $path;
}

/** Ambil daftar foto dokumentasi satu periode, siap dipakai isiNotulaDocx(). */
function ambilDokumentasi(PDO $db, int $periodeId): array {
    if (!$periodeId) return [];
    $stmt = $db->prepare("SELECT file_path, keterangan FROM ck_notula_dokumentasi
                          WHERE periode_id = ? ORDER BY urutan, id");
    $stmt->execute([$periodeId]);
    $root = realpath(__DIR__ . '/..');
    $out = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $out[] = ['path' => $root . '/' . $r['file_path'], 'keterangan' => $r['keterangan']];
    }
    return $out;
}

/** Tabel penyimpan info rapat bersifat opsional — fitur tetap jalan tanpanya. */
function tabelMetaAda(PDO $db): bool {
    static $ada = null;
    if ($ada === null) {
        try {
            $db->query("SELECT 1 FROM ck_notula_meta LIMIT 1");
            $ada = true;
        } catch (PDOException $e) {
            $ada = false;
        }
    }
    return $ada;
}

function ambilMeta(PDO $db, int $periode_id): array {
    if (tabelMetaAda($db)) {
        $stmt = $db->prepare("SELECT * FROM ck_notula_meta WHERE periode_id = ?");
        $stmt->execute([$periode_id]);
        $m = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($m) return $m;
    }
    return [
        'periode_id' => $periode_id, 'satker' => 'BPS Kota Bima',
        'nilai_sakip' => '', 'predikat' => '', 'hari_tanggal' => '', 'waktu' => '',
        'tempat' => '', 'pimpinan' => '', 'kepala_satker' => '', 'notulis' => '',
        'tempat_ttd' => 'Kota Bima',
    ];
}

/**
 * Gabungkan data dari Excel dengan metadata rapat dari form.
 * Nilai dari form menimpa nilai dari Excel kalau diisi.
 */
function gabungMetaExcel(array $sumber, array $meta): array {
    foreach (['satker', 'nilai_sakip', 'predikat', 'triwulan'] as $k) {
        if (trim((string)($meta[$k] ?? '')) !== '') $sumber[$k] = trim((string)$meta[$k]);
    }
    if (!empty($meta['tahun'])) $sumber['tahun'] = (int)$meta['tahun'];
    $sumber['agenda'] = array_filter(($meta['agenda'] ?? []), function ($v) {
        return trim((string)$v) !== '';
    });
    return $sumber;
}

/** Susun file meta JSON sementara dari input form. */
function tulisMetaSementara(array $in): string {
    $tgl_saja = preg_replace('/^[A-Za-z]+,\s*/', '', trim($in['hari_tanggal'] ?? ''));
    $ttd = trim($in['tempat_ttd'] ?? '');
    if ($ttd !== '' && $tgl_saja !== '') $ttd .= ', ' . $tgl_saja;

    $meta = [
        'satker'      => trim($in['satker']      ?? ''),
        'nilai_sakip' => trim($in['nilai_sakip'] ?? ''),
        'predikat'    => trim($in['predikat']    ?? ''),
        'triwulan'    => trim($in['triwulan']    ?? ''),
        'tahun'       => (int)($in['tahun'] ?? 2026),
        'agenda'      => [
            'hari_tanggal'       => trim($in['hari_tanggal']  ?? ''),
            'waktu'              => trim($in['waktu']         ?? ''),
            'tempat'             => trim($in['tempat']        ?? ''),
            'pimpinan'           => trim($in['pimpinan']      ?? ''),
            'kepala_satker'      => trim($in['kepala_satker'] ?? ''),
            'notulis'            => trim($in['notulis']       ?? ''),
            'tempat_tanggal_ttd' => $ttd,
        ],
    ];
    $path = NOTULA_TMP . '/meta_' . bin2hex(random_bytes(8)) . '.json';
    file_put_contents($path, json_encode($meta, JSON_UNESCAPED_UNICODE));
    return $path;
}

// ══════════════════════════════════════════════════════════
// ROUTER
// ══════════════════════════════════════════════════════════
switch ($act) {

// ── Cek lingkungan server ────────────────────────────────
case 'env_check':
    header('Cache-Control: no-store, no-cache, must-revalidate');
    json_ok([
        'mesin'         => 'php',
        'zip_ok'        => class_exists('ZipArchive'),
        'dom_ok'        => class_exists('DOMDocument'),
        'script_ok'     => true,
        'template_ok'   => file_exists(NOTULA_TEMPLATE),
        'tmp_writable'  => masalahTmp() === null,
        'masalah_tmp'   => masalahTmp(),
        'max_upload'    => ini_get('upload_max_filesize'),
        'php'           => PHP_VERSION,
    ]);

// ── 1. UPLOAD EXCEL + PARSE ──────────────────────────────
case 'upload':
    pastikanSiap();
    bersihkanTmp();

    if (empty($_FILES['excel'])) json_err('Tidak ada file yang dikirim.');
    $f = $_FILES['excel'];

    if ($f['error'] !== UPLOAD_ERR_OK) {
        $pesan = [
            UPLOAD_ERR_INI_SIZE   => 'Ukuran file melebihi batas upload_max_filesize di php.ini.',
            UPLOAD_ERR_FORM_SIZE  => 'Ukuran file terlalu besar.',
            UPLOAD_ERR_PARTIAL    => 'File hanya terkirim sebagian, coba ulangi.',
            UPLOAD_ERR_NO_FILE    => 'Tidak ada file yang dipilih.',
            UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary PHP tidak tersedia.',
            UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk.',
        ];
        json_err($pesan[$f['error']] ?? 'Upload gagal (kode ' . $f['error'] . ').');
    }

    if ($f['size'] > MAX_UPLOAD_MB * 1024 * 1024) {
        json_err('Ukuran file maksimal ' . MAX_UPLOAD_MB . ' MB.');
    }

    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if ($ext !== 'xlsx') {
        json_err('Format harus .xlsx. File .xls lama tidak didukung — buka di Excel lalu Save As ke .xlsx.');
    }

    // .xlsx sebenarnya arsip ZIP — verifikasi isinya, bukan hanya nama file
    $zip = new ZipArchive();
    if ($zip->open($f['tmp_name']) !== true || $zip->locateName('xl/workbook.xml') === false) {
        @$zip->close();
        json_err('File tidak dikenali sebagai workbook Excel yang valid.');
    }
    $zip->close();

    $token = bin2hex(random_bytes(16));
    $dest  = NOTULA_TMP . '/upl_' . $token . '.xlsx';
    if (!move_uploaded_file($f['tmp_name'], $dest)) {
        json_err('Gagal menyimpan file upload ke server.', 500);
    }
    @chmod($dest, 0664);

    // Buang upload lama milik sesi ini supaya tidak menumpuk
    foreach (($_SESSION['notula_uploads'] ?? []) as $t => $p) {
        if (!is_file($p) || (time() - filemtime($p)) > UPLOAD_TTL) {
            @unlink($p);
            unset($_SESSION['notula_uploads'][$t]);
        }
    }
    $_SESSION['notula_uploads'][$token] = $dest;

    // Parse isinya untuk preview
    try {
        $hasil = bacaKertasKerjaXlsx(
            $dest,
            !empty($_POST['sheet']) ? $_POST['sheet'] : KK_SHEET,
            !empty($_POST['triwulan']) ? $_POST['triwulan'] : null
        );
    } catch (Throwable $e) {
        @unlink($dest);
        unset($_SESSION['notula_uploads'][$token]);
        json_err('Gagal membaca isi Excel: ' . $e->getMessage(), 500);
    }

    json_ok([
        'token'           => $token,
        'nama_file'       => $f['name'],
        'ukuran'          => $f['size'],
        'satker'          => $hasil['satker'],
        'nilai_sakip'     => $hasil['nilai_sakip'],
        'predikat'        => $hasil['predikat'],
        'triwulan'        => $hasil['triwulan'],
        'sheet'           => $hasil['sheet'],
        'sheets_tersedia' => $hasil['sheets_tersedia'],
        'peringatan'      => $hasil['peringatan'],
        'iku_list'        => $hasil['iku_list'],
    ]);

// ── 2. GENERATE DARI FILE YANG SUDAH DI-UPLOAD ───────────
case 'generate':
    pastikanSiap();

    $in    = $_POST + $_GET;
    $excel = pathDariToken($in['token'] ?? '');

    $meta_path = tulisMetaSementara($in);
    $out_path  = NOTULA_TMP . '/out_' . bin2hex(random_bytes(8)) . '.docx';

    try {
        $sumber  = bacaKertasKerjaXlsx(
            $excel,
            !empty($in['sheet']) ? $in['sheet'] : KK_SHEET,
            !empty($in['triwulan']) ? $in['triwulan'] : null
        );
        $meta    = json_decode((string)file_get_contents($meta_path), true) ?: [];
        $payload = gabungMetaExcel($sumber, $meta);

        // Cocokkan ke ck_periode lewat triwulan+tahun supaya foto dokumentasi ikut,
        // walau sumbernya Excel yang di-upload (bukan mode database).
        $stmtP = $db->prepare("SELECT id FROM ck_periode WHERE tahun = ? AND triwulan = ?");
        $stmtP->execute([(int)$payload['tahun'], $payload['triwulan']]);
        $payload['dokumentasi'] = ambilDokumentasi($db, (int)$stmtP->fetchColumn());

        $info    = isiNotulaDocx($payload, NOTULA_TEMPLATE, $out_path);
    } catch (Throwable $e) {
        @unlink($meta_path); @unlink($out_path);
        json_err('Gagal menyusun notula: ' . $e->getMessage(), 500);
    }
    @unlink($meta_path);

    $hasil = $info + ['satker' => $payload['satker'],
                      'triwulan' => $payload['triwulan'],
                      'tahun' => $payload['tahun']];

    $satker_bersih = trim(preg_replace('/[^A-Za-z0-9]+/', '_', $hasil['satker']), '_');
    $filename = sprintf('Notula_Monitoring_Kinerja_%s_TW%s_%s.docx',
                        $satker_bersih ?: 'Satker', $hasil['triwulan'], $hasil['tahun']);

    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($out_path));
    header('X-Notula-Info: ' . json_encode([
        'iku_total'       => $hasil['iku_total'],
        'iku_terisi'      => $hasil['iku_terisi'],
        'iku_tanpa_tabel' => $hasil['iku_tanpa_tabel'],
        'dok_terpasang'   => $hasil['dok_terpasang'] ?? 0,
        'dok_gagal'       => $hasil['dok_gagal'] ?? [],
    ]));
    header('Access-Control-Expose-Headers: X-Notula-Info');
    header('Cache-Control: no-store');
    readfile($out_path);
    @unlink($out_path);
    exit;

// ── 3. GENERATE LANGSUNG DARI DATABASE (tanpa upload Excel) ──
//
// Sumber data: Entry Capaian Kinerja yang sudah diisi lewat aplikasi.
// Perhitungan dibuat sama persis dengan hitungCapaian() di entry.php supaya
// angka di notula konsisten dengan yang dilihat operator saat input.
case 'generate_db':
    pastikanSiap();
    bersihkanTmp();

    $pid = (int)($_GET['periode_id'] ?? $_POST['periode_id'] ?? 0);
    if (!$pid) json_err('periode_id required');

    $stmt = $db->prepare("SELECT * FROM ck_periode WHERE id = ?");
    $stmt->execute([$pid]);
    $periode = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$periode) json_err('Periode tidak ditemukan');

    $tw     = $periode['triwulan'];
    $tw_idx = array_search($tw, ['I', 'II', 'III', 'IV'], true) + 1;

    $stmt = $db->prepare("
        SELECT m.*, e.x_tw{$tw_idx} AS x_val, e.y_tw{$tw_idx} AS y_val,
               e.realisasi_tw{$tw_idx} AS real_val,
               e.kendala, e.solusi, e.rtl, e.pic, e.batas_waktu,
               e.link_bukti, e.link_tl
        FROM ck_iku_master m
        LEFT JOIN ck_entry_iku e ON e.iku_kode = m.kode AND e.periode_id = :pid
        WHERE m.jenis_iku = 'IKU'
        ORDER BY m.row_order
    ");
    $stmt->execute([':pid' => $pid]);
    $baris = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Narasi Rincian Output, digabung per IKU
    $stmt = $db->prepare("
        SELECT rm.iku_kode, rm.nama_ro, re.progres, re.narasi
        FROM ck_ro_master rm
        JOIN ck_entry_ro re ON re.ro_master_id = rm.id AND re.periode_id = :pid
        WHERE re.narasi IS NOT NULL AND re.narasi != ''
        ORDER BY rm.iku_kode, rm.ro_order
    ");
    $stmt->execute([':pid' => $pid]);
    $ro_map = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $nama = trim(preg_replace('/^\d+\s+[A-Z]{3}\s+\w+\.\s*/', '', $r['nama_ro']));
        $teks = $nama . ': ' . trim($r['narasi']);
        if ($r['progres'] !== null && $r['progres'] !== '') {
            $teks .= ' (progres ' . rtrim(rtrim(number_format((float)$r['progres'], 2, '.', ''), '0'), '.') . '%)';
        }
        $ro_map[$r['iku_kode']][] = $teks;
    }

    $fmt = function ($v) {
        if ($v === null || $v === '') return '';
        $s = number_format((float)$v, 2, '.', '');
        $s = rtrim(rtrim($s, '0'), '.');
        return $s === '' ? '0' : $s;
    };

    $bln = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
    $iku_list = [];
    $terisi = 0;

    foreach ($baris as $r) {
        $target  = (float)$r['target'];
        $alokasi = (float)$r["alokasi_tw{$tw_idx}"];

        // Realisasi: IKU '%' dihitung X/Y*100, selain itu diambil langsung
        $realisasi = null;
        if ($r['jenis_satuan'] === '%' && (int)$r['has_xy'] === 1) {
            if ($r['x_val'] !== null && $r['y_val'] !== null && (float)$r['y_val'] > 0) {
                $realisasi = (float)$r['x_val'] / (float)$r['y_val'] * 100;
            }
        } elseif ($r['real_val'] !== null && $r['real_val'] !== '') {
            $realisasi = (float)$r['real_val'];
        }

        $cap_tw = ($realisasi !== null && $alokasi > 0) ? $realisasi / $alokasi * 100 : null;
        $cap_pk = ($realisasi !== null && $target  > 0) ? $realisasi / $target  * 100 : null;

        $batas = '';
        if (!empty($r['batas_waktu']) && $r['batas_waktu'] !== '0000-00-00') {
            $ts = strtotime($r['batas_waktu']);
            $batas = date('j', $ts) . ' ' . $bln[(int)date('n', $ts)] . ' ' . date('Y', $ts);
        }

        if ($realisasi !== null || trim((string)$r['kendala']) !== '') $terisi++;

        $iku_list[] = [
            'kode'         => $r['kode'],
            'nama'         => $r['nama'],
            'sasaran_kode' => (string)$r['sasaran_kode'],
            'sasaran_nama' => (string)$r['sasaran_nama'],
            'tujuan'       => (string)$r['tujuan_nama'],
            'target_pk'    => $fmt($target),
            'satuan'       => (string)$r['satuan'],
            'alokasi_tw'   => $fmt($alokasi),
            'real_tw'      => $realisasi === null ? '' : $fmt($realisasi),
            'capaian_tw'   => $cap_tw === null ? '' : $fmt($cap_tw),
            'capaian_pk'   => $cap_pk === null ? '' : $fmt($cap_pk),
            'kendala'      => (string)$r['kendala'],
            'solusi'       => (string)$r['solusi'],
            'rtl'          => (string)$r['rtl'],
            'pic'          => (string)($r['pic'] ?: $r['pic_default']),
            'batas'        => $batas,
            'link_bukti'   => (string)$r['link_bukti'],
            'link_tl_sblm' => (string)$r['link_tl'],
            'ro_narasi'    => isset($ro_map[$r['kode']]) ? implode(' ', $ro_map[$r['kode']]) : '',
        ];
    }

    // Mode pratinjau: kirim ringkasannya saja, tanpa membuat dokumen
    if (($_GET['preview'] ?? '') === '1') {
        header('Cache-Control: no-store');
        json_ok([
            'periode'  => $periode,
            'meta'     => ambilMeta($db, $pid),
            'total'    => count($iku_list),
            'terisi'   => $terisi,
            'iku_list' => $iku_list,
        ]);
    }

    $in   = $_POST + $_GET;
    $meta = ambilMeta($db, $pid);
    foreach (['satker','nilai_sakip','predikat','hari_tanggal','waktu','tempat',
              'pimpinan','kepala_satker','notulis','tempat_ttd'] as $k) {
        if (isset($in[$k]) && trim((string)$in[$k]) !== '') $meta[$k] = trim($in[$k]);
    }

    $tgl_saja = preg_replace('/^[A-Za-z]+,\s*/', '', trim((string)$meta['hari_tanggal']));
    $ttd = trim((string)$meta['tempat_ttd']);
    if ($ttd !== '' && $tgl_saja !== '') $ttd .= ', ' . $tgl_saja;

    $payload = [
        'satker'      => $meta['satker'] ?: 'BPS Kota Bima',
        'nilai_sakip' => (string)$meta['nilai_sakip'],
        'predikat'    => (string)$meta['predikat'],
        'triwulan'    => $tw,
        'tahun'       => (int)$periode['tahun'],
        'agenda'      => [
            'hari_tanggal'       => (string)$meta['hari_tanggal'],
            'waktu'              => (string)$meta['waktu'],
            'tempat'             => (string)$meta['tempat'],
            'pimpinan'           => (string)$meta['pimpinan'],
            'kepala_satker'      => (string)$meta['kepala_satker'],
            'notulis'            => (string)$meta['notulis'],
            'tempat_tanggal_ttd' => $ttd,
        ],
        'iku_list'    => $iku_list,
        'dokumentasi' => ambilDokumentasi($db, $pid),
    ];

    $f_out = NOTULA_TMP . '/db_' . bin2hex(random_bytes(8)) . '.docx';

    try {
        $hasil = isiNotulaDocx($payload, NOTULA_TEMPLATE, $f_out);
    } catch (Throwable $e) {
        @unlink($f_out);
        json_err('Gagal menyusun notula: ' . $e->getMessage(), 500);
    }

    $bersih = trim(preg_replace('/[^A-Za-z0-9]+/', '_', $payload['satker']), '_');
    $filename = sprintf('Notula_Monitoring_Kinerja_%s_TW%s_%d.docx',
                        $bersih ?: 'Satker', $tw, $periode['tahun']);

    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($f_out));
    header('X-Notula-Info: ' . json_encode([
        'iku_total'       => $hasil['iku_total'],
        'iku_terisi'      => $hasil['iku_terisi'],
        'iku_tanpa_tabel' => $hasil['iku_tanpa_tabel'],
        'dok_terpasang'   => $hasil['dok_terpasang'] ?? 0,
        'dok_gagal'       => $hasil['dok_gagal'] ?? [],
    ]));
    header('Access-Control-Expose-Headers: X-Notula-Info');
    header('Cache-Control: no-store');
    readfile($f_out);
    @unlink($f_out);
    exit;

// ── 4. GENERATE DOKUMEN SUMBER — satu IKU, satu triwulan ──
//
// Beda dengan notula (rekap semua IKU): ini satu dokumen bukti dukung per
// indikator, isinya narasi RO + foto yang diupload lewat menu Entry.
case 'generate_dokumen_sumber':
    $pid  = (int)($_GET['periode_id'] ?? $_POST['periode_id'] ?? 0);
    $kode = $_GET['iku_kode'] ?? $_POST['iku_kode'] ?? '';
    if (!$pid || !$kode) json_err('periode_id + iku_kode required');

    if (!class_exists('ZipArchive')) json_err('Ekstensi PHP "zip" tidak aktif.', 500);
    if (!class_exists('DOMDocument')) json_err('Ekstensi PHP "xml/dom" tidak aktif.', 500);
    if (!file_exists(DOKSUM_TEMPLATE_KOSONG)) json_err('Template blank_dokumen_sumber.docx tidak ditemukan.', 500);
    if ($m = masalahTmp()) json_err($m, 500);

    bersihkanTmp();

    $stmt = $db->prepare("SELECT * FROM ck_periode WHERE id = ?");
    $stmt->execute([$pid]);
    $periode = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$periode) json_err('Periode tidak ditemukan');

    $tw     = $periode['triwulan'];
    $tw_idx = array_search($tw, ['I', 'II', 'III', 'IV'], true) + 1;

    $stmt = $db->prepare("
        SELECT m.*, e.x_tw{$tw_idx} AS x_val, e.y_tw{$tw_idx} AS y_val,
               e.realisasi_tw{$tw_idx} AS real_val,
               e.kendala, e.solusi, e.rtl, e.pic, e.batas_waktu
        FROM ck_iku_master m
        LEFT JOIN ck_entry_iku e ON e.iku_kode = m.kode AND e.periode_id = :pid
        WHERE m.kode = :kode
    ");
    $stmt->execute([':pid' => $pid, ':kode' => $kode]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$r) json_err('IKU tidak ditemukan');

    $fmt = function ($v) {
        if ($v === null || $v === '') return '';
        $s = number_format((float)$v, 2, '.', '');
        return rtrim(rtrim($s, '0'), '.') ?: '0';
    };

    $target  = (float)$r['target'];
    $alokasi = (float)$r["alokasi_tw{$tw_idx}"];
    $realisasi = null;
    if ($r['jenis_satuan'] === '%' && (int)$r['has_xy'] === 1) {
        if ($r['x_val'] !== null && $r['y_val'] !== null && (float)$r['y_val'] > 0) {
            $realisasi = (float)$r['x_val'] / (float)$r['y_val'] * 100;
        }
    } elseif ($r['real_val'] !== null && $r['real_val'] !== '') {
        $realisasi = (float)$r['real_val'];
    }
    $cap_tw = ($realisasi !== null && $alokasi > 0) ? $realisasi / $alokasi * 100 : null;
    $cap_pk = ($realisasi !== null && $target  > 0) ? $realisasi / $target  * 100 : null;

    $batas = '';
    if (!empty($r['batas_waktu']) && $r['batas_waktu'] !== '0000-00-00') {
        $bln = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
        $ts = strtotime($r['batas_waktu']);
        $batas = date('j', $ts) . ' ' . $bln[(int)date('n', $ts)] . ' ' . date('Y', $ts);
    }

    // RO + narasi + foto milik periode ini
    $stmt = $db->prepare("
        SELECT rm.id, rm.nama_ro, re.narasi
        FROM ck_ro_master rm
        LEFT JOIN ck_entry_ro re ON re.ro_master_id = rm.id AND re.periode_id = ?
        WHERE rm.iku_kode = ?
        ORDER BY rm.ro_order
    ");
    $stmt->execute([$pid, $kode]);
    $ros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $db->prepare("
        SELECT ro_master_id, file_path, keterangan
        FROM ck_ro_bukti_foto
        WHERE periode_id = ? AND ro_master_id IN (
            SELECT id FROM ck_ro_master WHERE iku_kode = ?
        )
        ORDER BY ro_master_id, urutan, id
    ");
    $stmt->execute([$pid, $kode]);
    $foto_per_ro = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $f) {
        $foto_per_ro[$f['ro_master_id']][] = [
            'path'       => realpath(__DIR__ . '/../' . $f['file_path']) ?: (__DIR__ . '/../' . $f['file_path']),
            'keterangan' => $f['keterangan'],
        ];
    }

    $ro_list = [];
    foreach ($ros as $ro) {
        $nama = trim(preg_replace('/^\d+\s+[A-Z]{3}\s+\w+\.\s*/', '', $ro['nama_ro']));
        $ro_list[] = [
            'nama_ro' => $nama !== '' ? $nama : $ro['nama_ro'],
            'narasi'  => (string)($ro['narasi'] ?? ''),
            'foto'    => $foto_per_ro[$ro['id']] ?? [],
        ];
    }

    $payload = [
        'satker'       => 'BPS Kota Bima',
        'triwulan'     => $tw,
        'tahun'        => (int)$periode['tahun'],
        'kode'         => $r['kode'],
        'nama'         => $r['nama'],
        'sasaran_nama' => (string)($r['sasaran_nama'] ?? ''),
        'target_pk'    => $fmt($target),
        'satuan'       => (string)$r['satuan'],
        'alokasi_tw'   => $fmt($alokasi),
        'real_tw'      => $realisasi === null ? '' : $fmt($realisasi),
        'capaian_tw'   => $cap_tw === null ? '' : $fmt($cap_tw),
        'capaian_pk'   => $cap_pk === null ? '' : $fmt($cap_pk),
        'kendala'      => (string)$r['kendala'],
        'solusi'       => (string)$r['solusi'],
        'rtl'          => (string)$r['rtl'],
        'pic'          => (string)($r['pic'] ?: $r['pic_default']),
        'batas'        => $batas,
        'ro_list'      => $ro_list,
    ];

    $out_path = NOTULA_TMP . '/dsum_out_' . bin2hex(random_bytes(8)) . '.docx';

    try {
        $info = buatDokumenSumberDocx($payload, DOKSUM_TEMPLATE_KOSONG, $out_path);
    } catch (Throwable $e) {
        error_log('[SOKAB dokumen_sumber] ' . $e->getMessage());
        @unlink($out_path);
        json_err('Gagal menyusun dokumen sumber: ' . $e->getMessage(), 500);
    }

    $kode_bersih = trim(preg_replace('/[^A-Za-z0-9]+/', '_', $kode), '_');
    $filename = sprintf('Bukti_Dokumen_Sumber_%s_TW%s_%d.docx', $kode_bersih, $tw, $periode['tahun']);

    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($out_path));
    header('X-Dokumen-Sumber-Info: ' . json_encode([
        'ro_terisi'      => $info['ro_terisi'] ?? 0,
        'foto_terpasang' => $info['foto_terpasang'] ?? 0,
        'foto_gagal'     => $info['foto_gagal'] ?? [],
    ]));
    header('Access-Control-Expose-Headers: X-Dokumen-Sumber-Info');
    header('Cache-Control: no-store');
    readfile($out_path);
    @unlink($out_path);
    exit;

// ── Dokumentasi rapat: daftar foto untuk satu periode ────
case 'dok_list':
    $pid = (int)($_GET['periode_id'] ?? 0);
    if (!$pid) json_err('periode_id required');
    $stmt = $db->prepare("SELECT id, file_path, original_name, keterangan, urutan
                          FROM ck_notula_dokumentasi WHERE periode_id = ? ORDER BY urutan, id");
    $stmt->execute([$pid]);
    json_ok(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);

// ── Dokumentasi rapat: upload satu foto ──────────────────
case 'dok_upload':
    $pid = (int)($_POST['periode_id'] ?? 0);
    $keterangan = trim($_POST['keterangan'] ?? '');
    if (!$pid) json_err('periode_id required');

    if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $pesan = [
            UPLOAD_ERR_INI_SIZE  => 'Ukuran file melebihi batas upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'Ukuran file terlalu besar',
            UPLOAD_ERR_PARTIAL   => 'File hanya terkirim sebagian',
            UPLOAD_ERR_NO_FILE   => 'Tidak ada file yang dipilih',
        ];
        json_err($pesan[$_FILES['file']['error'] ?? -1] ?? 'Upload gagal');
    }

    $f = $_FILES['file'];
    if ($f['size'] > 8 * 1024 * 1024) json_err('Ukuran foto maksimal 8 MB');

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($f['tmp_name']);
    $ext_by_mime = ['image/png' => 'png', 'image/jpeg' => 'jpg'];
    if (!isset($ext_by_mime[$mime])) json_err('Hanya file PNG atau JPG yang diizinkan');

    $dir = __DIR__ . '/../uploads/notula_dok/';
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    if (!is_writable($dir)) json_err('Folder uploads/notula_dok tidak writable', 500);

    $filename = 'dok_p' . $pid . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext_by_mime[$mime];
    $dest = $dir . $filename;
    if (!move_uploaded_file($f['tmp_name'], $dest)) json_err('Gagal menyimpan foto ke server', 500);

    $stmt = $db->prepare("SELECT COALESCE(MAX(urutan), -1) + 1 FROM ck_notula_dokumentasi WHERE periode_id = ?");
    $stmt->execute([$pid]);
    $urutan = (int)$stmt->fetchColumn();

    $file_path = 'uploads/notula_dok/' . $filename;
    $stmt = $db->prepare("INSERT INTO ck_notula_dokumentasi
                          (periode_id, file_path, original_name, keterangan, urutan, uploaded_by)
                          VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$pid, $file_path, $f['name'], $keterangan ?: null, $urutan, $_SESSION['user_id'] ?? null]);

    json_ok(['id' => (int)$db->lastInsertId(), 'file_path' => $file_path]);

// ── Dokumentasi rapat: hapus satu foto ───────────────────
case 'dok_delete':
    $b  = body() ?: $_POST;
    $id = (int)($b['id'] ?? 0);
    if (!$id) json_err('id required');

    $stmt = $db->prepare("SELECT file_path FROM ck_notula_dokumentasi WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) json_err('Foto tidak ditemukan');

    $abs = __DIR__ . '/../' . $row['file_path'];
    if (is_file($abs)) @unlink($abs);

    $db->prepare("DELETE FROM ck_notula_dokumentasi WHERE id = ?")->execute([$id]);
    json_ok(['message' => 'Foto dihapus']);

// ── Daftar periode untuk dropdown ────────────────────────
case 'list_periode':
    header('Cache-Control: no-store');
    $rows = $db->query("SELECT id, triwulan, tahun, status FROM ck_periode
                        ORDER BY tahun DESC, FIELD(triwulan,'I','II','III','IV')")
               ->fetchAll(PDO::FETCH_ASSOC);
    json_ok(['data' => $rows]);

// ── Hapus file upload (tombol "ganti file") ──────────────
case 'discard':
    $token = $_GET['token'] ?? '';
    if (preg_match('/^[a-f0-9]{32}$/', $token) && isset($_SESSION['notula_uploads'][$token])) {
        @unlink($_SESSION['notula_uploads'][$token]);
        unset($_SESSION['notula_uploads'][$token]);
    }
    json_ok(['message' => 'File dihapus']);

// ── Metadata rapat: ambil ────────────────────────────────
case 'meta_get':
    $pid = (int)($_GET['periode_id'] ?? 0);
    if (!$pid) json_err('periode_id required');
    json_ok(['data' => ambilMeta($db, $pid)]);

// ── Metadata rapat: simpan ───────────────────────────────
case 'meta_save':
    $b   = body() ?: $_POST;
    $pid = (int)($b['periode_id'] ?? 0);
    if (!$pid) json_err('periode_id required');
    if (!tabelMetaAda($db)) {
        json_err('Tabel ck_notula_meta belum dibuat. Jalankan sql/ck_notula_meta.sql di phpMyAdmin '
               . 'kalau ingin informasi rapat tersimpan. Tanpa itu, notula tetap bisa di-generate '
               . 'dengan mengisi form setiap kali.');
    }

    $f = [
        'satker'        => trim($b['satker']        ?? 'BPS Kota Bima'),
        'nilai_sakip'   => trim($b['nilai_sakip']   ?? ''),
        'predikat'      => trim($b['predikat']      ?? ''),
        'hari_tanggal'  => trim($b['hari_tanggal']  ?? ''),
        'waktu'         => trim($b['waktu']         ?? ''),
        'tempat'        => trim($b['tempat']        ?? ''),
        'pimpinan'      => trim($b['pimpinan']      ?? ''),
        'kepala_satker' => trim($b['kepala_satker'] ?? ''),
        'notulis'       => trim($b['notulis']       ?? ''),
        'tempat_ttd'    => trim($b['tempat_ttd']    ?? ''),
        'updated_by'    => $_SESSION['user_id'] ?? null,
    ];

    $stmt = $db->prepare("SELECT id FROM ck_notula_meta WHERE periode_id = ?");
    $stmt->execute([$pid]);
    if ($stmt->fetchColumn()) {
        $sets = implode(', ', array_map(fn($k) => "`$k` = :$k", array_keys($f)));
        $db->prepare("UPDATE ck_notula_meta SET $sets WHERE periode_id = :pid")
           ->execute($f + ['pid' => $pid]);
    } else {
        $f['periode_id'] = $pid;
        $cols = implode(', ', array_map(fn($k) => "`$k`", array_keys($f)));
        $vals = implode(', ', array_map(fn($k) => ":$k", array_keys($f)));
        $db->prepare("INSERT INTO ck_notula_meta ($cols) VALUES ($vals)")->execute($f);
    }
    json_ok(['message' => 'Informasi rapat tersimpan']);

default:
    json_err("Action '$act' tidak dikenal");
}
