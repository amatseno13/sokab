<?php
// api/permindok.php - API untuk Permintaan Dokumen (MASAKO Style)
// Error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

// Check if database config exists
$config_file = __DIR__ . '/../config/database.php';
if (!file_exists($config_file)) {
    echo json_encode([
        'success' => false,
        'message' => 'Database config file not found: ' . $config_file
    ]);
    exit();
}

require_once $config_file;

// ── Jaring pengaman: fatal error pun tetap dibalas JSON, bukan 500 kosong ──
register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (!headers_sent()) {
            http_response_code(200);
            header('Content-Type: application/json');
        }
        echo json_encode([
            'success' => false,
            'message' => 'Fatal error: ' . $e['message'],
            'file'    => $e['file'],
            'line'    => $e['line'],
        ]);
    }
});

/**
 * Tabel permindok punya dua versi skema yang beredar:
 *   - sokab_complete.sql : nama_dokumen, kategori, gdrive_link, requested_by, status
 *   - versi terbaru      : nomor, judul, link_permindok, created_by, is_active
 * API ini butuh versi terbaru. Kalau yang terpasang versi lama, beri tahu dengan jelas.
 */
function cekSkemaPermindok(PDO $pdo) {
    $ada = [];
    foreach ($pdo->query("SHOW COLUMNS FROM permindok") as $r) {
        $ada[] = $r['Field'];
    }
    $wajib  = ['nomor', 'judul', 'link_permindok', 'created_by', 'is_active', 'updated_at'];
    $hilang = array_values(array_diff($wajib, $ada));
    if ($hilang) {
        echo json_encode([
            'success' => false,
            'message' => 'Struktur tabel permindok tidak cocok. Kolom yang hilang: '
                       . implode(', ', $hilang)
                       . '. Sepertinya database dibuat dari sokab_complete.sql (skema lama). '
                       . 'Jalankan sql/permindok_upgrade.sql untuk memperbaikinya.',
            'kolom_terpasang' => $ada,
        ]);
        exit();
    }
}

// Auth check
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $pdo = getDBConnection();
    cekSkemaPermindok($pdo);
    
    // ==================== GET: List Permindok ====================
    if ($method === 'GET' && $action === 'list') {
        $tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : null;
        
        $sql = "SELECT 
                    p.id,
                    p.nomor,
                    p.tahun,
                    p.judul,
                    p.link_permindok,
                    p.updated_at,
                    u.nama_lengkap AS created_by_name
                FROM permindok p
                LEFT JOIN users u ON p.created_by = u.id
                WHERE p.is_active = 1";
        
        $params = [];
        if ($tahun) {
            $sql .= " AND p.tahun = ?";
            $params[] = $tahun;
        }
        
        $sql .= " ORDER BY p.tahun DESC, p.nomor ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $data,
            'tahun' => $tahun,
            'count' => count($data)
        ]);
        
    // ==================== GET: Detail Permindok ====================
    } elseif ($method === 'GET' && $action === 'detail') {
        $id = intval($_GET['id'] ?? 0);
        
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
            exit();
        }
        
        $stmt = $pdo->prepare("
            SELECT 
                p.*,
                u.nama_lengkap AS created_by_name
            FROM permindok p
            LEFT JOIN users u ON p.created_by = u.id
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($data) {
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan']);
        }
        
    // ==================== POST: Update Link ====================
    } elseif ($method === 'POST' && $action === 'update_link') {
        if (!isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Akses ditolak. Hanya admin yang dapat mengubah link.']);
            exit();
        }
        
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data) {
            echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
            exit();
        }
        
        $id = intval($data['id'] ?? 0);
        $link = trim($data['link_permindok'] ?? '');
        
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
            exit();
        }
        
        // Validasi URL (opsional, allow kosong)
        if ($link && !filter_var($link, FILTER_VALIDATE_URL)) {
            echo json_encode(['success' => false, 'message' => 'Format URL tidak valid']);
            exit();
        }
        
        $stmt = $pdo->prepare("
            UPDATE permindok 
            SET link_permindok = ?
            WHERE id = ?
        ");
        $stmt->execute([$link, $id]);
        
        echo json_encode(['success' => true, 'message' => 'Link berhasil diperbarui']);
        
    // ==================== GET: List All (Admin) ====================
    } elseif ($method === 'GET' && $action === 'list_all') {
        if (!isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
            exit();
        }
        
        $stmt = $pdo->query("
            SELECT 
                p.*,
                u.nama_lengkap AS created_by_name
            FROM permindok p
            LEFT JOIN users u ON p.created_by = u.id
            ORDER BY p.is_active DESC, p.tahun DESC, p.nomor ASC
        ");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $data]);
        
    // ==================== POST: Create Permindok ====================
    } elseif ($method === 'POST' && $action === 'create') {
        if (!isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
            exit();
        }
        
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        $nomor = intval($data['nomor'] ?? 0);
        $tahun = intval($data['tahun'] ?? 0);
        $judul = trim($data['judul'] ?? '');
        $link = trim($data['link_permindok'] ?? '');
        
        if (!$nomor || !$tahun || !$judul) {
            echo json_encode(['success' => false, 'message' => 'Nomor, tahun, dan judul harus diisi']);
            exit();
        }
        
        // Cek duplikat nomor per tahun
        $check = $pdo->prepare("SELECT id FROM permindok WHERE nomor = ? AND tahun = ? AND is_active = 1");
        $check->execute([$nomor, $tahun]);
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'message' => "Nomor $nomor untuk tahun $tahun sudah ada"]);
            exit();
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO permindok (nomor, tahun, judul, link_permindok, created_by)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$nomor, $tahun, $judul, $link, $_SESSION['user_id'] ?? null]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Permindok berhasil ditambahkan',
            'id' => $pdo->lastInsertId()
        ]);
        
    // ==================== POST: Update Permindok ====================
    } elseif ($method === 'POST' && $action === 'update') {
        if (!isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
            exit();
        }
        
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        $id = intval($data['id'] ?? 0);
        $nomor = intval($data['nomor'] ?? 0);
        $tahun = intval($data['tahun'] ?? 0);
        $judul = trim($data['judul'] ?? '');
        $link = trim($data['link_permindok'] ?? '');
        
        if (!$id || !$nomor || !$tahun || !$judul) {
            echo json_encode(['success' => false, 'message' => 'Semua field harus diisi']);
            exit();
        }
        
        // Cek duplikat nomor per tahun (exclude current id)
        $check = $pdo->prepare("SELECT id FROM permindok WHERE nomor = ? AND tahun = ? AND id != ? AND is_active = 1");
        $check->execute([$nomor, $tahun, $id]);
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'message' => "Nomor $nomor untuk tahun $tahun sudah digunakan"]);
            exit();
        }
        
        $stmt = $pdo->prepare("
            UPDATE permindok 
            SET nomor = ?, tahun = ?, judul = ?, link_permindok = ?
            WHERE id = ?
        ");
        $stmt->execute([$nomor, $tahun, $judul, $link, $id]);
        
        echo json_encode(['success' => true, 'message' => 'Permindok berhasil diperbarui']);
        
    // ==================== POST: Delete (Soft) ====================
    } elseif ($method === 'POST' && $action === 'delete') {
        if (!isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
            exit();
        }
        
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        $id = intval($data['id'] ?? 0);
        
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
            exit();
        }
        
        $stmt = $pdo->prepare("UPDATE permindok SET is_active = 0 WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true, 'message' => 'Permindok berhasil dihapus']);
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Action tidak valid: ' . $action]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
