<?php
// api/permindok.php - API untuk Permintaan Dokumen
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable display untuk production

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set JSON header
header('Content-Type: application/json; charset=utf-8');

// Function to send JSON response
function sendJSON($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

// Function to log errors
function logError($message) {
    error_log("[PERMINDOK API] " . $message);
}

try {
    // Check database config
    $config_file = __DIR__ . '/../config/database.php';
    if (!file_exists($config_file)) {
        sendJSON([
            'success' => false,
            'message' => 'Database configuration file not found'
        ]);
    }
    
    require_once $config_file;
    
    // Get database connection
    try {
        $pdo = getDBConnection();
    } catch (Exception $e) {
        logError("Database connection failed: " . $e->getMessage());
        sendJSON([
            'success' => false,
            'message' => 'Database connection failed'
        ]);
    }
    
    // Check authentication
    $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    
    // Get request method and action
    $method = $_SERVER['REQUEST_METHOD'];
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    // ==================== GET: List Permindok ====================
    if ($method === 'GET' && $action === 'list') {
        $tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : null;
        
        $sql = "SELECT 
                    p.id,
                    p.nomor,
                    p.tahun,
                    p.judul,
                    p.link_permindok,
                    p.created_at,
                    p.updated_at
                FROM permindok p
                WHERE p.is_active = 1";
        
        $params = array();
        if ($tahun) {
            $sql .= " AND p.tahun = ?";
            $params[] = $tahun;
        }
        
        $sql .= " ORDER BY p.tahun DESC, p.nomor ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        sendJSON([
            'success' => true,
            'data' => $data,
            'tahun' => $tahun,
            'count' => count($data)
        ]);
    }
    
    // ==================== GET: Detail Permindok ====================
    elseif ($method === 'GET' && $action === 'detail') {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if (!$id) {
            sendJSON(['success' => false, 'message' => 'ID tidak valid']);
        }
        
        $stmt = $pdo->prepare("SELECT * FROM permindok WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($data) {
            sendJSON(['success' => true, 'data' => $data]);
        } else {
            sendJSON(['success' => false, 'message' => 'Data tidak ditemukan']);
        }
    }
    
    // ==================== POST: Update Link ====================
    elseif ($method === 'POST' && $action === 'update_link') {
        if (!$isAdmin) {
            sendJSON(['success' => false, 'message' => 'Akses ditolak. Hanya admin yang dapat mengubah link.']);
        }
        
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data) {
            sendJSON(['success' => false, 'message' => 'Invalid JSON input']);
        }
        
        $id = isset($data['id']) ? intval($data['id']) : 0;
        $link = isset($data['link_permindok']) ? trim($data['link_permindok']) : '';
        
        if (!$id) {
            sendJSON(['success' => false, 'message' => 'ID tidak valid']);
        }
        
        // Validasi URL (opsional)
        if ($link && !filter_var($link, FILTER_VALIDATE_URL)) {
            sendJSON(['success' => false, 'message' => 'Format URL tidak valid']);
        }
        
        $stmt = $pdo->prepare("UPDATE permindok SET link_permindok = ? WHERE id = ?");
        $result = $stmt->execute([$link, $id]);
        
        sendJSON(['success' => true, 'message' => 'Link berhasil diperbarui']);
    }
    
    // ==================== GET: List All (Admin) ====================
    elseif ($method === 'GET' && $action === 'list_all') {
        if (!$isAdmin) {
            sendJSON(['success' => false, 'message' => 'Akses ditolak']);
        }
        
        $stmt = $pdo->query("
            SELECT * FROM permindok 
            ORDER BY is_active DESC, tahun DESC, nomor ASC
        ");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        sendJSON(['success' => true, 'data' => $data]);
    }
    
    // ==================== POST: Create Permindok ====================
    elseif ($method === 'POST' && $action === 'create') {
        if (!$isAdmin) {
            sendJSON(['success' => false, 'message' => 'Akses ditolak']);
        }
        
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        $nomor = isset($data['nomor']) ? intval($data['nomor']) : 0;
        $tahun = isset($data['tahun']) ? intval($data['tahun']) : 0;
        $judul = isset($data['judul']) ? trim($data['judul']) : '';
        $link = isset($data['link_permindok']) ? trim($data['link_permindok']) : '';
        
        if (!$nomor || !$tahun || !$judul) {
            sendJSON(['success' => false, 'message' => 'Nomor, tahun, dan judul harus diisi']);
        }
        
        // Check duplicate
        $check = $pdo->prepare("SELECT id FROM permindok WHERE nomor = ? AND tahun = ? AND is_active = 1");
        $check->execute([$nomor, $tahun]);
        if ($check->fetch()) {
            sendJSON(['success' => false, 'message' => "Nomor $nomor untuk tahun $tahun sudah ada"]);
        }
        
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        
        $stmt = $pdo->prepare("
            INSERT INTO permindok (nomor, tahun, judul, link_permindok, created_by)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$nomor, $tahun, $judul, $link, $userId]);
        
        sendJSON([
            'success' => true,
            'message' => 'Permindok berhasil ditambahkan',
            'id' => $pdo->lastInsertId()
        ]);
    }
    
    // ==================== POST: Update Permindok ====================
    elseif ($method === 'POST' && $action === 'update') {
        if (!$isAdmin) {
            sendJSON(['success' => false, 'message' => 'Akses ditolak']);
        }
        
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        $id = isset($data['id']) ? intval($data['id']) : 0;
        $nomor = isset($data['nomor']) ? intval($data['nomor']) : 0;
        $tahun = isset($data['tahun']) ? intval($data['tahun']) : 0;
        $judul = isset($data['judul']) ? trim($data['judul']) : '';
        $link = isset($data['link_permindok']) ? trim($data['link_permindok']) : '';
        
        if (!$id || !$nomor || !$tahun || !$judul) {
            sendJSON(['success' => false, 'message' => 'Semua field harus diisi']);
        }
        
        // Check duplicate
        $check = $pdo->prepare("SELECT id FROM permindok WHERE nomor = ? AND tahun = ? AND id != ? AND is_active = 1");
        $check->execute([$nomor, $tahun, $id]);
        if ($check->fetch()) {
            sendJSON(['success' => false, 'message' => "Nomor $nomor untuk tahun $tahun sudah digunakan"]);
        }
        
        $stmt = $pdo->prepare("
            UPDATE permindok 
            SET nomor = ?, tahun = ?, judul = ?, link_permindok = ?
            WHERE id = ?
        ");
        $stmt->execute([$nomor, $tahun, $judul, $link, $id]);
        
        sendJSON(['success' => true, 'message' => 'Permindok berhasil diperbarui']);
    }
    
    // ==================== POST: Delete (Soft) ====================
    elseif ($method === 'POST' && $action === 'delete') {
        if (!$isAdmin) {
            sendJSON(['success' => false, 'message' => 'Akses ditolak']);
        }
        
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        $id = isset($data['id']) ? intval($data['id']) : 0;
        
        if (!$id) {
            sendJSON(['success' => false, 'message' => 'ID tidak valid']);
        }
        
        $stmt = $pdo->prepare("UPDATE permindok SET is_active = 0 WHERE id = ?");
        $stmt->execute([$id]);
        
        sendJSON(['success' => true, 'message' => 'Permindok berhasil dihapus']);
    }
    
    // ==================== Invalid Action ====================
    else {
        sendJSON(['success' => false, 'message' => 'Action tidak valid: ' . $action]);
    }
    
} catch (PDOException $e) {
    logError("PDO Error: " . $e->getMessage());
    sendJSON([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    logError("General Error: " . $e->getMessage());
    sendJSON([
        'success' => false,
        'message' => 'An error occurred'
    ]);
}
