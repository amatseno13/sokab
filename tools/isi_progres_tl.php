<?php
/**
 * SOKAB — isi Progres Pelaksanaan Tindak Lanjut = 100% untuk semua IKU (hanya yang masih kosong).
 *
 * Progres disimpan di ck_entry_ro (periode TW yang RTL-nya dilaporkan) dengan ro_master_id = -(900000 + row_order IKU),
 * sama dengan kolom "Progres Pelaksanaan" di Bagian 4 entry.php. Nilai yang sudah terisi tidak ditimpa. Aman diulang.
 *
 * Web (admin login):  /sokab/tools/isi_progres_tl.php                  → SIMULASI
 *                     /sokab/tools/isi_progres_tl.php?jalankan=1       → isi sungguhan
 *                     tambahan: &tahun=2026&tw=I,II,III (bawaan: 2026, TW I,II,III)
 * CLI:                php tools/isi_progres_tl.php [--jalankan] [--tahun=2026] [--tw=I,II,III]
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

$opt = [];
foreach ($cli ? array_slice($argv ?? [], 1) : [] as $a) if (preg_match('/^--(\w+)(?:=(.*))?$/', $a, $m)) $opt[$m[1]] = $m[2] ?? '1';
$jalankan = $cli ? isset($opt['jalankan']) : isset($_GET['jalankan']);
$tahun    = (int)($cli ? ($opt['tahun'] ?? 2026) : ($_GET['tahun'] ?? 2026));
$tw       = array_filter(explode(',', strtoupper($cli ? ($opt['tw'] ?? 'I,II,III') : ($_GET['tw'] ?? 'I,II,III'))), fn($t) => in_array($t, ['I', 'II', 'III', 'IV'], true));

$db = getDBConnection();
echo $jalankan ? "=== ISI SUNGGUHAN ===\n" : "=== SIMULASI (tidak ada yang diubah; tambahkan ?jalankan=1) ===\n";

$iku = $db->query("SELECT kode, row_order FROM ck_iku_master ORDER BY row_order")->fetchAll(PDO::FETCH_ASSOC);
$cari = $db->prepare("SELECT id, progres FROM ck_entry_ro WHERE periode_id = ? AND ro_master_id = ?");
$ubah = $db->prepare("UPDATE ck_entry_ro SET progres = 1 WHERE id = ?");
$baru = $db->prepare("INSERT INTO ck_entry_ro (periode_id, ro_master_id, iku_kode, progres) VALUES (?, ?, ?, 1)");

foreach ($tw as $t) {
    $st = $db->prepare("SELECT id FROM ck_periode WHERE tahun = ? AND triwulan = ?");
    $st->execute([$tahun, $t]);
    $pid = (int)$st->fetchColumn();
    if (!$pid) { echo "TW $t $tahun: periode tidak ada, dilewati\n"; continue; }
    $n = ['isi' => 0, 'sudah' => 0];
    foreach ($iku as $m) {
        $k = -(900000 + (int)$m['row_order']);
        $cari->execute([$pid, $k]);
        $r = $cari->fetch(PDO::FETCH_ASSOC);
        if ($r && $r['progres'] !== null) { $n['sudah']++; continue; }   // sudah ada nilainya: jangan ditimpa
        if ($jalankan) { $r ? $ubah->execute([$r['id']]) : $baru->execute([$pid, $k, $m['kode']]); }
        $n['isi']++;
    }
    echo "TW $t $tahun (periode $pid): {$n['isi']} IKU " . ($jalankan ? 'diisi' : 'akan diisi') . " 100%, {$n['sudah']} sudah terisi (dibiarkan)\n";
}
