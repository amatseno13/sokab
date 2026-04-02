<?php
/**
 * SOKAB - API Jadwal SAKIP
 */

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Cek login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesi habis, silakan login kembali']);
    exit();
}

$method  = $_SERVER['REQUEST_METHOD'];
$action  = $_GET['action'] ?? '';
$isAdmin = ($_SESSION['role'] === 'admin');

// Warna per kategori
$warnaKat = [
    'FRA'     => '#0ea5e9',
    'KKPK'    => '#3b82f6',
    'POK'     => '#48bb78',
    'PKPT'    => '#ed8936',
    'Renstra' => '#667eea',
    'LAKIN'   => '#f56565',
    'Lainnya' => '#a0aec0',
];

try {
    $pdo = getDBConnection();

    // ── GET ──────────────────────────────────────────────
    if ($method === 'GET') {

        if ($action === 'kalender') {
            $stmt = $pdo->query("
                SELECT id, judul, kategori,
                       tanggal_mulai  AS start,
                       DATE_ADD(tanggal_selesai, INTERVAL 1 DAY) AS end,
                       status, warna AS color
                FROM jadwal_sakip
                ORDER BY tanggal_mulai
            ");
            $rows = $stmt->fetchAll();
            // Tambah opacity kalau selesai
            foreach ($rows as &$r) {
                if ($r['status'] === 'selesai') {
                    $r['opacity'] = 0.45;
                    $r['textColor'] = '#fff';
                }
                $r['title'] = $r['judul']; // FullCalendar pakai 'title'
            }
            echo json_encode(['success' => true, 'data' => $rows]);

        } elseif ($action === 'upcoming') {
            $stmt = $pdo->prepare("
                SELECT id, judul, deskripsi, kategori,
                       tanggal_mulai, tanggal_selesai, status, warna
                FROM jadwal_sakip
                WHERE tanggal_selesai >= CURDATE()
                  AND tanggal_selesai <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                  AND status != 'selesai'
                ORDER BY tanggal_selesai ASC
                LIMIT 10
            ");
            $stmt->execute();
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);

        } elseif ($action === 'detail') {
            $id = intval($_GET['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID tidak valid']); exit(); }
            $stmt = $pdo->prepare("SELECT j.*, u.nama_lengkap AS created_by_name FROM jadwal_sakip j LEFT JOIN users u ON j.created_by = u.id WHERE j.id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if (!$row) { echo json_encode(['success' => false, 'message' => 'Jadwal tidak ditemukan']); exit(); }
            echo json_encode(['success' => true, 'data' => $row]);

        } else {
            // Semua jadwal (untuk list admin)
            $stmt = $pdo->query("
                SELECT j.*, u.nama_lengkap AS created_by_name
                FROM jadwal_sakip j
                LEFT JOIN users u ON j.created_by = u.id
                ORDER BY j.tanggal_mulai ASC
            ");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        }

    // ── POST ─────────────────────────────────────────────
    } elseif ($method === 'POST') {
        $raw  = file_get_contents('php://input');
        $data = json_decode($raw, true) ?? [];

        if ($action === 'tambah') {
            if (!$isAdmin) { echo json_encode(['success' => false, 'message' => 'Hanya admin yang bisa menambah jadwal']); exit(); }

            $judul   = trim($data['judul'] ?? '');
            $mulai   = $data['tanggal_mulai'] ?? '';
            $selesai = $data['tanggal_selesai'] ?? '';
            $kat     = $data['kategori'] ?? 'Lainnya';

            if (!$judul || !$mulai || !$selesai) {
                echo json_encode(['success' => false, 'message' => 'Judul dan tanggal wajib diisi']);
                exit();
            }

            if ($selesai < $mulai) {
                echo json_encode(['success' => false, 'message' => 'Tanggal selesai tidak boleh sebelum tanggal mulai']);
                exit();
            }

            $warna = $warnaKat[$kat] ?? '#a0aec0';

            $stmt = $pdo->prepare("
                INSERT INTO jadwal_sakip (judul, deskripsi, kategori, tanggal_mulai, tanggal_selesai, status, warna, created_by)
                VALUES (?, ?, ?, ?, ?, 'belum', ?, ?)
            ");
            $stmt->execute([$judul, $data['deskripsi'] ?? '', $kat, $mulai, $selesai, $warna, $_SESSION['user_id']]);
            echo json_encode(['success' => true, 'message' => 'Jadwal berhasil ditambahkan', 'id' => $pdo->lastInsertId()]);

        } elseif ($action === 'update_status') {
            $id     = intval($data['id'] ?? 0);
            $status = $data['status'] ?? '';

            if (!$id || !in_array($status, ['belum', 'proses', 'selesai'])) {
                echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
                exit();
            }

            $stmt = $pdo->prepare("UPDATE jadwal_sakip SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            echo json_encode(['success' => true, 'message' => 'Status diperbarui']);

        } elseif ($action === 'edit') {
            if (!$isAdmin) { echo json_encode(['success' => false, 'message' => 'Akses ditolak']); exit(); }

            $id      = intval($data['id'] ?? 0);
            $judul   = trim($data['judul'] ?? '');
            $mulai   = $data['tanggal_mulai'] ?? '';
            $selesai = $data['tanggal_selesai'] ?? '';
            $kat     = $data['kategori'] ?? 'Lainnya';

            if (!$id || !$judul || !$mulai || !$selesai) {
                echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
                exit();
            }

            $warna = $warnaKat[$kat] ?? '#a0aec0';

            $stmt = $pdo->prepare("
                UPDATE jadwal_sakip
                SET judul=?, deskripsi=?, kategori=?, tanggal_mulai=?, tanggal_selesai=?, warna=?
                WHERE id=?
            ");
            $stmt->execute([$judul, $data['deskripsi'] ?? '', $kat, $mulai, $selesai, $warna, $id]);
            echo json_encode(['success' => true, 'message' => 'Jadwal berhasil diperbarui']);

        } elseif ($action === 'hapus') {
            if (!$isAdmin) { echo json_encode(['success' => false, 'message' => 'Hanya admin yang bisa menghapus jadwal']); exit(); }

            $id = intval($data['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID tidak valid']); exit(); }

            $stmt = $pdo->prepare("DELETE FROM jadwal_sakip WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Jadwal berhasil dihapus']);

        } else {
            echo json_encode(['success' => false, 'message' => 'Action tidak dikenal']);
        }

    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan']);
    }

} catch (PDOException $e) {
    error_log('Jadwal API PDO Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Kesalahan database: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log('Jadwal API Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
?>
