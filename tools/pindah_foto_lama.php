<?php
/**
 * SOKAB — pindahkan file bukti lama ke struktur uploads/bukti_ro/{tahun}/TW{n}/{kode IKU}/
 *
 * Tujuan folder dibaca dari database (ck_ro_bukti_foto → periode + IKU), bukan ditebak dari nama file.
 * Aman diulang: baris yang sudah berada di subfolder dilewati. File yang tidak ada di disk dilaporkan, tidak disentuh.
 *
 * Web (admin login):  /sokab/tools/pindah_foto_lama.php          → SIMULASI (tidak mengubah apa pun)
 *                     /sokab/tools/pindah_foto_lama.php?jalankan=1 → pindahkan sungguhan
 * CLI:                php tools/pindah_foto_lama.php [--jalankan]
 *
 * SEBELUM ?jalankan=1: backup database + folder uploads/.
 */

$cli = PHP_SAPI === 'cli';
if (!$cli) {
    session_start();
    require_once __DIR__ . '/../includes/check_session.php';
    requireLogin();
    if (($_SESSION['role'] ?? '') !== 'admin') { http_response_code(403); exit('Hanya admin.'); }
    header('Content-Type: text/plain; charset=utf-8');
}
require_once __DIR__ . '/../config/database.php';

$jalankan = $cli ? in_array('--jalankan', $argv ?? [], true) : isset($_GET['jalankan']);
$db   = getDBConnection();
$root = realpath(__DIR__ . '/..');
$awal = 'uploads/bukti_ro/';

echo $jalankan ? "=== PINDAHKAN (sungguhan) ===\n" : "=== SIMULASI (tidak ada yang diubah; tambahkan ?jalankan=1 untuk memindahkan) ===\n";

$rows = $db->query("
    SELECT f.id, f.ro_master_id, f.iku_kode, f.file_path, p.tahun, p.triwulan, rm.iku_kode AS iku_ro
    FROM ck_ro_bukti_foto f
    JOIN ck_periode p ON p.id = f.periode_id
    LEFT JOIN ck_ro_master rm ON rm.id = f.ro_master_id AND f.ro_master_id > 0
    ORDER BY f.id
")->fetchAll(PDO::FETCH_ASSOC);

$upd = $db->prepare("UPDATE ck_ro_bukti_foto SET file_path = ? WHERE id = ?");
$n = ['pindah' => 0, 'sudah' => 0, 'hilang' => 0, 'tanpa_iku' => 0, 'gagal' => 0];

foreach ($rows as $r) {
    $path = (string)$r['file_path'];
    if (strpos($path, $awal) !== 0) continue;                      // bukan milik folder ini
    $sisa = substr($path, strlen($awal));
    if (strpos($sisa, '/') !== false) { $n['sudah']++; continue; } // sudah di subfolder

    $kode = $r['ro_master_id'] > 0 ? (string)$r['iku_ro'] : (string)$r['iku_kode'];
    if ($kode === '') { $kode = '_tanpa_IKU'; $n['tanpa_iku']++; echo "IKU tak diketahui → _tanpa_IKU  #{$r['id']} $path\n"; }   // baris lama sebelum kolom iku_kode ada

    $asal = $root . '/' . $path;
    if (!is_file($asal)) { $n['hilang']++; echo "HILANG di disk   #{$r['id']} $path\n"; continue; }

    $sub    = (int)$r['tahun'] . '/TW' . $r['triwulan'] . '/' . preg_replace('/[^0-9A-Za-z.]+/', '_', $kode) . '/';
    $tujuan = $root . '/' . $awal . $sub . $sisa;
    $baru   = $awal . $sub . $sisa;

    if (!$jalankan) { $n['pindah']++; echo "AKAN pindah      #{$r['id']} $sisa  →  $sub\n"; continue; }

    if (!is_dir(dirname($tujuan)) && !@mkdir(dirname($tujuan), 0775, true)) { $n['gagal']++; echo "GAGAL buat folder #{$r['id']} $sub\n"; continue; }
    if (!@rename($asal, $tujuan)) { $n['gagal']++; echo "GAGAL pindah     #{$r['id']} $path\n"; continue; }
    try {
        $upd->execute([$baru, $r['id']]);
        $n['pindah']++;
    } catch (Throwable $e) {
        @rename($tujuan, $asal);                                    // database gagal → kembalikan file
        $n['gagal']++; echo "GAGAL database    #{$r['id']} (file dikembalikan)\n";
    }
}

// File di folder utama yang tidak tercatat di database (sisa/yatim) — hanya dilaporkan
$tercatat = array_flip(array_map(fn($r) => basename($r['file_path']), $rows));
$yatim = 0;
foreach (glob($root . '/' . $awal . '*') as $f) {
    if (is_file($f) && basename($f) !== '.gitkeep' && !isset($tercatat[basename($f)])) $yatim++;
}

echo "\nRingkasan: " . ($jalankan ? 'dipindah' : 'akan dipindah') . " {$n['pindah']}, sudah rapi {$n['sudah']}, "
   . "hilang di disk {$n['hilang']}, tanpa IKU {$n['tanpa_iku']}, gagal {$n['gagal']}\n";
echo "File di folder utama yang tidak ada di database (tidak disentuh): $yatim\n";
