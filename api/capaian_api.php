<?php
/**
 * SOKAB — Capaian Kinerja Triwulanan API
 * Endpoint: /sokab/api/capaian_api.php
 */

session_start();
require_once __DIR__ . '/../includes/check_session.php';
require_once __DIR__ . '/../config/database.php';
requireLogin();

header('Content-Type: application/json; charset=utf-8');

$db  = getDBConnection();
$act = $_GET['action'] ?? $_POST['action'] ?? '';

// ── Helper ───────────────────────────────────────────────
function json_ok($data = [])  { echo json_encode(['success' => true]  + $data); exit; }
function json_err($msg)       { http_response_code(400); echo json_encode(['success' => false, 'message' => $msg]); exit; }

function body(): array {
    $raw = file_get_contents('php://input');
    return $raw ? (json_decode($raw, true) ?? []) : [];
}

function user_id(): ?int {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

// ── Router ───────────────────────────────────────────────
switch ($act) {

    // ── GET: daftar semua IKU + status pengisian per periode ──
    case 'list_iku':
        $periode_id = (int)($_GET['periode_id'] ?? 0);
        if (!$periode_id) json_err('periode_id required');

        $sql = "
            SELECT
                m.*,
                e.id        AS entry_id,
                e.kendala,
                e.solusi,
                e.rtl,
                e.pic,
                e.updated_at,
                u.nama_lengkap AS diisi_oleh,
                CASE
                    WHEN e.id IS NULL THEN 'kosong'
                    WHEN (m.jenis_satuan = '%'  AND (e.x_tw1 IS NOT NULL OR e.x_tw2 IS NOT NULL OR e.x_tw3 IS NOT NULL OR e.x_tw4 IS NOT NULL)) THEN 'isi'
                    WHEN (m.jenis_satuan = 'Non %' AND (e.realisasi_tw1 IS NOT NULL OR e.realisasi_tw2 IS NOT NULL OR e.realisasi_tw3 IS NOT NULL OR e.realisasi_tw4 IS NOT NULL)) THEN 'isi'
                    WHEN (e.kendala IS NOT NULL AND e.kendala != '') THEN 'sebagian'
                    ELSE 'kosong'
                END AS status_isi
            FROM ck_iku_master m
            LEFT JOIN ck_entry_iku e ON e.iku_kode = m.kode AND e.periode_id = :pid
            LEFT JOIN users u ON u.id = e.updated_by
            ORDER BY m.row_order
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([':pid' => $periode_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        json_ok(['data' => $rows]);

    // ── GET: data lengkap satu IKU ──
    case 'get_iku':
        $periode_id = (int)($_GET['periode_id'] ?? 0);
        $kode       = $_GET['kode'] ?? '';
        if (!$periode_id || !$kode) json_err('periode_id + kode required');

        // IKU master
        $stmt = $db->prepare("SELECT * FROM ck_iku_master WHERE kode = ?");
        $stmt->execute([$kode]);
        $master = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$master) json_err('IKU tidak ditemukan');

        // Entry data
        $stmt = $db->prepare("SELECT * FROM ck_entry_iku WHERE periode_id = ? AND iku_kode = ?");
        $stmt->execute([$periode_id, $kode]);
        $entry = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        // RO master + entry
        $stmt = $db->prepare("
            SELECT rm.*, re.vol_ro, re.progres, re.narasi, re.updated_at AS ro_updated
            FROM ck_ro_master rm
            LEFT JOIN ck_entry_ro re ON re.ro_master_id = rm.id AND re.periode_id = ?
            WHERE rm.iku_kode = ?
            ORDER BY rm.ro_order
        ");
        $stmt->execute([$periode_id, $kode]);
        $ros = $stmt->fetchAll(PDO::FETCH_ASSOC);

        json_ok(['master' => $master, 'entry' => $entry, 'ros' => $ros]);

    // ── POST: simpan data IKU ──
    case 'save_iku':
        $body = body();
        $periode_id = (int)($body['periode_id'] ?? 0);
        $kode       = $body['iku_kode'] ?? '';
        if (!$periode_id || !$kode) json_err('periode_id + iku_kode required');

        // Cek master ada
        $stmt = $db->prepare("SELECT jenis_satuan FROM ck_iku_master WHERE kode = ?");
        $stmt->execute([$kode]);
        $master = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$master) json_err('IKU tidak ditemukan');

        $uid = user_id();

        // Build fields
        $fields = [
            'x_tw1' => $body['x_tw1'] ?? null, 'y_tw1' => $body['y_tw1'] ?? null,
            'x_tw2' => $body['x_tw2'] ?? null, 'y_tw2' => $body['y_tw2'] ?? null,
            'x_tw3' => $body['x_tw3'] ?? null, 'y_tw3' => $body['y_tw3'] ?? null,
            'x_tw4' => $body['x_tw4'] ?? null, 'y_tw4' => $body['y_tw4'] ?? null,
            'realisasi_tw1' => $body['realisasi_tw1'] ?? null,
            'realisasi_tw2' => $body['realisasi_tw2'] ?? null,
            'realisasi_tw3' => $body['realisasi_tw3'] ?? null,
            'realisasi_tw4' => $body['realisasi_tw4'] ?? null,
            'kendala'       => $body['kendala'] ?? null,
            'solusi'        => $body['solusi']  ?? null,
            'rtl'           => $body['rtl']     ?? null,
            'pic'           => $body['pic']     ?? null,
            'batas_waktu'   => $body['batas_waktu'] ?? null,
            'link_bukti'    => $body['link_bukti']  ?? null,
            'link_tl'       => $body['link_tl']     ?? null,
            'updated_by'    => $uid,
        ];

        // Upsert
        $stmt = $db->prepare("SELECT id FROM ck_entry_iku WHERE periode_id = ? AND iku_kode = ?");
        $stmt->execute([$periode_id, $kode]);
        $existing = $stmt->fetchColumn();

        if ($existing) {
            $sets = implode(', ', array_map(fn($k) => "`$k` = :$k", array_keys($fields)));
            $stmt = $db->prepare("UPDATE ck_entry_iku SET $sets WHERE periode_id = :pid AND iku_kode = :kode");
            $stmt->execute($fields + [':pid' => $periode_id, ':kode' => $kode]);
        } else {
            $fields['periode_id'] = $periode_id;
            $fields['iku_kode']   = $kode;
            $cols = implode(', ', array_map(fn($k) => "`$k`", array_keys($fields)));
            $vals = implode(', ', array_map(fn($k) => ":$k", array_keys($fields)));
            $stmt = $db->prepare("INSERT INTO ck_entry_iku ($cols) VALUES ($vals)");
            $stmt->execute($fields);
        }

        json_ok(['message' => 'Data IKU tersimpan']);

    // ── POST: simpan data RO ──
    case 'save_ro':
        $body = body();
        $periode_id  = (int)($body['periode_id'] ?? 0);
        $ro_master_id = (int)($body['ro_master_id'] ?? 0);
        $iku_kode    = $body['iku_kode'] ?? '';
        if (!$periode_id || !$ro_master_id || !$iku_kode) json_err('periode_id + ro_master_id + iku_kode required');

        $uid = user_id();

        $fields = [
            'vol_ro'   => strlen($body['vol_ro'] ?? '') ? $body['vol_ro'] : null,
            'progres'  => strlen($body['progres'] ?? '') ? $body['progres'] : null,
            'narasi'   => $body['narasi'] ?? null,
            'updated_by' => $uid,
        ];

        $stmt = $db->prepare("SELECT id FROM ck_entry_ro WHERE periode_id = ? AND ro_master_id = ?");
        $stmt->execute([$periode_id, $ro_master_id]);
        $existing = $stmt->fetchColumn();

        if ($existing) {
            $stmt = $db->prepare("UPDATE ck_entry_ro SET vol_ro=:vol_ro, progres=:progres, narasi=:narasi, updated_by=:updated_by WHERE periode_id=:pid AND ro_master_id=:rid");
            $stmt->execute($fields + [':pid' => $periode_id, ':rid' => $ro_master_id]);
        } else {
            $stmt = $db->prepare("INSERT INTO ck_entry_ro (periode_id, ro_master_id, iku_kode, vol_ro, progres, narasi, updated_by) VALUES (?,?,?,?,?,?,?)");
            $stmt->execute([$periode_id, $ro_master_id, $iku_kode, $fields['vol_ro'], $fields['progres'], $fields['narasi'], $uid]);
        }

        json_ok(['message' => 'Data RO tersimpan']);

    // ── GET: daftar periode ──
    case 'list_periode':
        $stmt = $db->query("SELECT * FROM ck_periode ORDER BY tahun DESC, FIELD(triwulan,'I','II','III','IV')");
        json_ok(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);

    // ── GET: ringkasan progress per periode ──
    case 'summary':
        $periode_id = (int)($_GET['periode_id'] ?? 0);
        if (!$periode_id) json_err('periode_id required');

        $total = 17;
        $stmt = $db->prepare("
            SELECT COUNT(DISTINCT iku_kode) AS isi
            FROM ck_entry_iku
            WHERE periode_id = ?
              AND (kendala IS NOT NULL AND kendala != '')
        ");
        $stmt->execute([$periode_id]);
        $isi = (int)$stmt->fetchColumn();

        json_ok(['total' => $total, 'isi' => $isi, 'kosong' => $total - $isi]);

    // ── GET: ambil tautan dari IKSS (menu Monitoring Capaian Kinerja) ──
    //
    // Dua tautan yang diambil punya asal triwulan berbeda:
    //   link_bukti  ← link_dokumen_sumber IKSS pada triwulan YANG SAMA
    //   link_tl     ← link_tindak_lanjut  IKSS pada triwulan SEBELUMNYA
    //
    // TW I mundur ke TW IV TAHUN SEBELUMNYA — karena itulah ikss_links perlu
    // kolom tahun. Kalau kolomnya belum ada, tahun diabaikan agar tetap jalan.
    case 'ikss_links':
        $periode_id = (int)($_GET['periode_id'] ?? 0);
        $kode       = $_GET['iku_kode'] ?? '';
        if (!$periode_id || !$kode) json_err('periode_id + iku_kode required');

        // Tabel pemetaan bersifat opsional — fitur mati mulus kalau belum dibuat
        try {
            $db->query("SELECT 1 FROM ck_iku_ikss LIMIT 1");
        } catch (PDOException $e) {
            json_ok([
                'tersedia' => false,
                'pesan'    => 'Tabel ck_iku_ikss belum dibuat. Jalankan sql/ck_iku_ikss.sql.',
            ]);
        }

        $stmt = $db->prepare("SELECT triwulan, tahun FROM ck_periode WHERE id = ?");
        $stmt->execute([$periode_id]);
        $per = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$per) json_err('Periode tidak ditemukan');
        $tw    = $per['triwulan'];
        $tahun = (int)$per['tahun'];

        $urut     = ['I', 'II', 'III', 'IV'];
        $idx      = array_search($tw, $urut, true);
        $tw_label = 'TW ' . $tw;
        $tw_sblm  = 'TW ' . $urut[($idx + 3) % 4];      // mundur satu, TW I → TW IV

        // TW I mundur melewati pergantian tahun
        $tahun_sblm = ($idx === 0) ? $tahun - 1 : $tahun;

        // Pastikan tabelnya ada
        try {
            $db->query("SELECT 1 FROM ikss_links LIMIT 1");
        } catch (PDOException $e) {
            json_ok(['tersedia' => false, 'pesan' => 'Tabel ikss_links tidak ditemukan.']);
        }

        // Kolom tahun mungkin belum ditambahkan (sql/ikss_links_tahun.sql).
        // Diprobe dengan query ringan, bukan SHOW COLUMNS, supaya tidak terikat
        // ke satu jenis database dan tidak menelan error lain sebagai "tabel hilang".
        $pakai_tahun = true;
        try {
            $db->query("SELECT tahun FROM ikss_links LIMIT 1");
        } catch (PDOException $e) {
            $pakai_tahun = false;
        }

        $stmt = $db->prepare("
            SELECT p.ikss_id, p.keterangan, s.indikator_kinerja
            FROM ck_iku_ikss p
            LEFT JOIN ikss_master s ON s.id = p.ikss_id
            WHERE p.iku_kode = ?
        ");
        $stmt->execute([$kode]);
        $map = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$map) {
            json_ok([
                'tersedia' => false,
                'pesan'    => "IKU $kode belum dipetakan ke IKSS mana pun.",
            ]);
        }

        $ambil = function ($tw_cari, $th_cari) use ($db, $map, $pakai_tahun) {
            if ($pakai_tahun) {
                $stmt = $db->prepare("
                    SELECT link_dokumen_sumber, link_tindak_lanjut, updated_at
                    FROM ikss_links
                    WHERE ikss_id = ? AND triwulan = ? AND tahun = ?
                    ORDER BY updated_at DESC LIMIT 1
                ");
                $stmt->execute([$map['ikss_id'], $tw_cari, $th_cari]);
            } else {
                $stmt = $db->prepare("
                    SELECT link_dokumen_sumber, link_tindak_lanjut, updated_at
                    FROM ikss_links
                    WHERE ikss_id = ? AND triwulan = ?
                    ORDER BY updated_at DESC LIMIT 1
                ");
                $stmt->execute([$map['ikss_id'], $tw_cari]);
            }
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        };

        $kini = $ambil($tw_label, $tahun);
        $sblm = $ambil($tw_sblm, $tahun_sblm);

        json_ok([
            'tersedia'       => true,
            'ikss_id'        => (int)$map['ikss_id'],
            'ikss_indikator' => $map['indikator_kinerja'] ?: $map['keterangan'],
            'triwulan'       => $tw_label . ' ' . $tahun,
            'triwulan_sblm'  => $tw_sblm . ' ' . $tahun_sblm,
            'pakai_tahun'    => $pakai_tahun,
            'link_bukti'     => $kini['link_dokumen_sumber'] ?? '',
            'link_tl'        => $sblm['link_tindak_lanjut'] ?? '',
            'ada_kini'       => (bool)$kini,
            'ada_sblm'       => (bool)$sblm,
        ]);

    // ── GET: daftar foto bukti semua RO pada satu periode ──
    // Dipakai halaman Entry supaya galeri tiap baris RO terisi dalam satu request.
    case 'foto_list':
        $periode_id = (int)($_GET['periode_id'] ?? 0);
        if (!$periode_id) json_err('periode_id required');

        $stmt = $db->prepare("
            SELECT id, ro_master_id, file_path, original_name, keterangan, urutan
            FROM ck_ro_bukti_foto
            WHERE periode_id = ?
            ORDER BY ro_master_id, urutan, id
        ");
        $stmt->execute([$periode_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $per_ro = [];
        foreach ($rows as $r) $per_ro[$r['ro_master_id']][] = $r;
        json_ok(['data' => $per_ro]);

    // ── POST: upload satu foto bukti untuk satu RO ──
    case 'foto_upload':
        $ro_master_id = (int)($_POST['ro_master_id'] ?? 0);
        $periode_id   = (int)($_POST['periode_id'] ?? 0);
        $keterangan   = trim($_POST['keterangan'] ?? '');
        if (!$ro_master_id || !$periode_id) json_err('ro_master_id + periode_id required');

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

        $dir = __DIR__ . '/../uploads/bukti_ro/';
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        if (!is_writable($dir)) json_err('Folder uploads/bukti_ro tidak writable', 500);

        $filename = 'ro' . $ro_master_id . '_p' . $periode_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext_by_mime[$mime];
        $dest = $dir . $filename;
        if (!move_uploaded_file($f['tmp_name'], $dest)) json_err('Gagal menyimpan foto ke server', 500);

        $stmt = $db->prepare("SELECT COALESCE(MAX(urutan), -1) + 1 FROM ck_ro_bukti_foto WHERE ro_master_id = ? AND periode_id = ?");
        $stmt->execute([$ro_master_id, $periode_id]);
        $urutan = (int)$stmt->fetchColumn();

        $file_path = 'uploads/bukti_ro/' . $filename;
        $stmt = $db->prepare("
            INSERT INTO ck_ro_bukti_foto (ro_master_id, periode_id, file_path, original_name, keterangan, urutan, uploaded_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$ro_master_id, $periode_id, $file_path, $f['name'], $keterangan ?: null, $urutan, user_id()]);

        json_ok(['id' => (int)$db->lastInsertId(), 'file_path' => $file_path]);

    // ── POST: hapus satu foto bukti ──
    case 'foto_delete':
        $body = body() ?: $_POST;
        $id = (int)($body['id'] ?? 0);
        if (!$id) json_err('id required');

        $stmt = $db->prepare("SELECT file_path FROM ck_ro_bukti_foto WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) json_err('Foto tidak ditemukan');

        $abs = __DIR__ . '/../' . $row['file_path'];
        if (is_file($abs)) @unlink($abs);

        $db->prepare("DELETE FROM ck_ro_bukti_foto WHERE id = ?")->execute([$id]);
        json_ok(['message' => 'Foto dihapus']);

    default:
        json_err("Action '$act' tidak dikenal");
}
