<?php
/**
 * SOKAB — Export Kertas Kerja ke Excel
 * Endpoint: /sokab/api/export_kertas_kerja.php
 *
 *   GET ?action=cek                      → status kesiapan (python + template)
 *   GET ?action=preview&periode_id=1     → ringkasan data yang akan ditulis
 *   GET ?action=export&periode_id=1      → unduh .xlsx
 *
 * Mengisi template kertas_kerja.xlsx dengan data Entry Capaian Kinerja.
 * Hanya sel tanpa formula yang ditulis — seluruh rumus di kertas kerja tetap utuh.
 */

session_start();
require_once __DIR__ . '/../includes/check_session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/xlsx_kertas_kerja.php';

define('SOKAB_TOOLS', realpath(__DIR__ . '/../tools/notula'));
define('SOKAB_TMP',   SOKAB_TOOLS . '/tmp');

function masalahTmp(): ?string {
    if (!is_dir(SOKAB_TMP)) @mkdir(SOKAB_TMP, 0755, true);
    if (!is_dir(SOKAB_TMP))      return 'Folder tools/notula/tmp tidak ada dan tidak bisa dibuat.';
    if (!is_writable(SOKAB_TMP)) return 'Folder tools/notula/tmp tidak bisa ditulis (chmod 755).';
    return null;
}
function bersihkanTmp(int $ttl = 3600): void {
    if (!is_dir(SOKAB_TMP)) return;
    foreach (glob(SOKAB_TMP . '/*') as $f) {
        if (is_file($f) && basename($f) !== '.gitkeep' && (time() - filemtime($f)) > $ttl) @unlink($f);
    }
}
requireLogin();

define('XK_TEMPLATE', SOKAB_TOOLS . '/template/kertas_kerja.xlsx');

$db  = getDBConnection();
$act = $_GET['action'] ?? '';

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

function ambilPeriode(PDO $db, int $id): ?array {
    $stmt = $db->prepare("SELECT * FROM ck_periode WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

/**
 * Ambil data entry satu periode, apa adanya dari database.
 * Tidak ada perhitungan di sini — biar rumus di Excel yang mengerjakannya.
 */
function ambilData(PDO $db, int $periode_id): array {
    $sql = "SELECT m.kode, m.jenis_satuan, m.pic_default,
                   e.x_tw1, e.x_tw2, e.x_tw3, e.x_tw4,
                   e.y_tw1, e.y_tw2, e.y_tw3, e.y_tw4,
                   e.realisasi_tw1, e.realisasi_tw2, e.realisasi_tw3, e.realisasi_tw4,
                   e.kendala, e.solusi, e.rtl, e.pic, e.batas_waktu,
                   e.link_bukti, e.link_tl
            FROM ck_iku_master m
            LEFT JOIN ck_entry_iku e ON e.iku_kode = m.kode AND e.periode_id = :pid
            WHERE m.jenis_iku = 'IKU'
            ORDER BY m.row_order";
    $stmt = $db->prepare($sql);
    $stmt->execute([':pid' => $periode_id]);

    $bln = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    $out = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $batas = '';
        if (!empty($r['batas_waktu']) && $r['batas_waktu'] !== '0000-00-00') {
            $ts = strtotime($r['batas_waktu']);
            $batas = date('j', $ts) . ' ' . $bln[(int)date('n', $ts)] . ' ' . date('Y', $ts);
        }

        $iku = [
            'kode'         => $r['kode'],
            'jenis_satuan' => $r['jenis_satuan'],
            'kendala'      => $r['kendala'],
            'solusi'       => $r['solusi'],
            'rtl'          => $r['rtl'],
            'pic'          => $r['pic'] ?: $r['pic_default'],
            'batas'        => $batas,
            'link_bukti'   => $r['link_bukti'],
            'link_tl'      => $r['link_tl'],
        ];
        foreach (['x', 'y', 'realisasi'] as $p) {
            for ($i = 1; $i <= 4; $i++) {
                $k = $p . '_tw' . $i;
                $iku[$k] = $r[$k];
            }
        }
        $out[] = $iku;
    }
    return $out;
}

function ambilSatker(PDO $db, int $periode_id): array {
    try {
        $stmt = $db->prepare("SELECT satker, nilai_sakip FROM ck_notula_meta WHERE periode_id = ?");
        $stmt->execute([$periode_id]);
        $m = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($m) return ['satker' => $m['satker'], 'nilai_sakip' => $m['nilai_sakip']];
    } catch (PDOException $e) {
        // tabel opsional — abaikan
    }
    return ['satker' => 'BPS Kota Bima', 'nilai_sakip' => ''];
}

// ══════════════════════════════════════════════════════════
switch ($act) {

case 'cek':
    header('Cache-Control: no-store');
    json_ok([
        'mesin'        => 'php',
        'zip_ok'       => class_exists('ZipArchive'),
        'dom_ok'       => class_exists('DOMDocument'),
        'script_ok'    => true,
        'template_ok'  => file_exists(XK_TEMPLATE),
        'masalah_tmp'  => masalahTmp(),
        'php'          => PHP_VERSION,
    ]);

case 'preview':
    $pid = (int)($_GET['periode_id'] ?? 0);
    if (!$pid) json_err('periode_id required');
    if (!ambilPeriode($db, $pid)) json_err('Periode tidak ditemukan');

    $data = ambilData($db, $pid);
    $isi = 0;
    foreach ($data as $d) {
        $ada = false;
        foreach (['x_tw1','x_tw2','x_tw3','x_tw4','realisasi_tw1','realisasi_tw2',
                  'realisasi_tw3','realisasi_tw4'] as $k) {
            if ($d[$k] !== null && $d[$k] !== '') { $ada = true; break; }
        }
        if (!$ada && trim((string)$d['kendala']) !== '') $ada = true;
        if ($ada) $isi++;
    }
    json_ok(['total' => count($data), 'terisi' => $isi, 'data' => $data]);

case 'export':
    $pid = (int)($_GET['periode_id'] ?? 0);
    if (!$pid) json_err('periode_id required');

    $periode = ambilPeriode($db, $pid);
    if (!$periode) json_err('Periode tidak ditemukan');

    if (!class_exists('ZipArchive'))  json_err('Ekstensi PHP "zip" tidak aktif. Aktifkan lewat hPanel → PHP Configuration.', 500);
    if (!class_exists('DOMDocument')) json_err('Ekstensi PHP "xml/dom" tidak aktif.', 500);
    if (!file_exists(XK_TEMPLATE))    json_err('Template kertas_kerja.xlsx tidak ditemukan di ' . XK_TEMPLATE, 500);
    if ($m = masalahTmp())            json_err($m, 500);

    bersihkanTmp();

    $meta = ambilSatker($db, $pid);
    $payload = [
        'satker'      => $meta['satker'],
        'nilai_sakip' => $meta['nilai_sakip'],
        'tahun'       => (int)$periode['tahun'],
        'triwulan'    => $periode['triwulan'],
        'iku_list'    => ambilData($db, $pid),
    ];

    $f_out = SOKAB_TMP . '/xk_' . bin2hex(random_bytes(8)) . '.xlsx';

    try {
        $hasil = isiKertasKerjaXlsx($payload, XK_TEMPLATE, $f_out);
    } catch (Throwable $e) {
        @unlink($f_out);
        json_err('Gagal menyusun Excel: ' . $e->getMessage(), 500);
    }

    $satker_bersih = trim(preg_replace('/[^A-Za-z0-9]+/', '_', $payload['satker']), '_');
    $filename = sprintf('Kertas_Kerja_%s_TW%s_%d.xlsx',
                        $satker_bersih ?: 'Satker', $periode['triwulan'], $periode['tahun']);

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($f_out));
    header('X-Export-Info: ' . json_encode([
        'sel_ditulis'    => $hasil['sel_ditulis'],
        'sel_dilewati'   => $hasil['sel_dilewati'],
        'iku_tak_ketemu' => $hasil['iku_tak_ketemu'],
    ]));
    header('Cache-Control: no-store');
    readfile($f_out);
    @unlink($f_out);
    exit;

default:
    json_err("Action '$act' tidak dikenal");
}
