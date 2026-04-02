<?php
/**
 * API IKSS (Indikator Kinerja Sasaran Strategis)
 * BPS Kota Bima - Version 2.0
 * Updated: 2026-04-01
 */

session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Cek login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Helper function: Check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $pdo = getDBConnection();
    
    // ==================== GET: List IKSS dengan Link ====================
    if ($method === 'GET' && $action === 'list') {
        $triwulan = $_GET['triwulan'] ?? 'TW I';
        
        $sql = "SELECT 
                    m.id,
                    m.nomor,
                    m.sasaran_kegiatan,
                    m.indikator_kinerja,
                    m.target,
                    l.link_dokumen_sumber,
                    l.link_tindak_lanjut,
                    l.updated_at
                FROM ikss_master m
                LEFT JOIN ikss_links l ON m.id = l.ikss_id AND l.triwulan = ?
                WHERE m.is_active = 1
                ORDER BY CAST(m.nomor AS UNSIGNED)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$triwulan]);
        $data = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $data,
            'triwulan' => $triwulan
        ]);
        exit();
    }
    
    // ==================== GET: Detail IKSS ====================
    if ($method === 'GET' && $action === 'detail') {
        $ikss_id = $_GET['ikss_id'] ?? null;
        $triwulan = $_GET['triwulan'] ?? 'TW I';
        
        if (!$ikss_id) {
            echo json_encode(['success' => false, 'message' => 'IKSS ID harus diisi']);
            exit();
        }
        
        $sql = "SELECT 
                    m.id,
                    m.nomor,
                    m.sasaran_kegiatan,
                    m.indikator_kinerja,
                    m.target,
                    l.link_dokumen_sumber,
                    l.link_tindak_lanjut
                FROM ikss_master m
                LEFT JOIN ikss_links l ON m.id = l.ikss_id AND l.triwulan = ?
                WHERE m.id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$triwulan, $ikss_id]);
        $data = $stmt->fetch();
        
        if ($data) {
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            echo json_encode(['success' => false, 'message' => 'IKSS tidak ditemukan']);
        }
        exit();
    }
    
    // ==================== POST: Update Link ====================
    if ($method === 'POST' && $action === 'update_link') {
        $ikss_id = $_POST['ikss_id'] ?? null;
        $triwulan = $_POST['triwulan'] ?? 'TW I';
        $link_dokumen_sumber = trim($_POST['link_dokumen_sumber'] ?? '');
        $link_tindak_lanjut = trim($_POST['link_tindak_lanjut'] ?? '');
        
        if (!$ikss_id) {
            echo json_encode(['success' => false, 'message' => 'IKSS ID harus diisi']);
            exit();
        }
        
        // Cek apakah sudah ada data untuk triwulan ini
        $check = $pdo->prepare("SELECT id FROM ikss_links WHERE ikss_id = ? AND triwulan = ?");
        $check->execute([$ikss_id, $triwulan]);
        $existing = $check->fetch();
        
        if ($existing) {
            // Update
            $sql = "UPDATE ikss_links 
                    SET link_dokumen_sumber = ?, 
                        link_tindak_lanjut = ?,
                        updated_by = ?,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE ikss_id = ? AND triwulan = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $link_dokumen_sumber,
                $link_tindak_lanjut,
                $_SESSION['user_id'],
                $ikss_id,
                $triwulan
            ]);
        } else {
            // Insert
            $sql = "INSERT INTO ikss_links (ikss_id, triwulan, link_dokumen_sumber, link_tindak_lanjut, updated_by) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $ikss_id,
                $triwulan,
                $link_dokumen_sumber,
                $link_tindak_lanjut,
                $_SESSION['user_id']
            ]);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Link berhasil disimpan'
        ]);
        exit();
    }
    
    // ==================== GET: List All IKSS (for manage - admin only) ====================
    if ($method === 'GET' && $action === 'list_all') {
        if (!isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized - Admin only']);
            exit();
        }
        
        $sql = "SELECT id, nomor, sasaran_kegiatan, indikator_kinerja, target, is_active, created_at, updated_at
                FROM ikss_master
                ORDER BY CAST(nomor AS UNSIGNED)";
        
        $stmt = $pdo->query($sql);
        $data = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
        exit();
    }
    
    // ==================== POST: Create IKSS (admin only) ====================
    if ($method === 'POST' && $action === 'create_ikss') {
        if (!isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized - Admin only']);
            exit();
        }
        
        $nomor = trim($_POST['nomor'] ?? '');
        $sasaran_kegiatan = trim($_POST['sasaran_kegiatan'] ?? '');
        $indikator_kinerja = trim($_POST['indikator_kinerja'] ?? '');
        $target = trim($_POST['target'] ?? '');
        
        if (!$nomor || !$sasaran_kegiatan || !$indikator_kinerja || !$target) {
            echo json_encode(['success' => false, 'message' => 'Semua field harus diisi']);
            exit();
        }
        
        $sql = "INSERT INTO ikss_master (nomor, sasaran_kegiatan, indikator_kinerja, target, created_by) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $nomor,
            $sasaran_kegiatan,
            $indikator_kinerja,
            $target,
            $_SESSION['user_id']
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'IKSS berhasil ditambahkan',
            'id' => $pdo->lastInsertId()
        ]);
        exit();
    }
    
    // ==================== POST: Update IKSS (admin only) ====================
    if ($method === 'POST' && $action === 'update_ikss') {
        if (!isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized - Admin only']);
            exit();
        }
        
        $id = $_POST['id'] ?? null;
        $nomor = trim($_POST['nomor'] ?? '');
        $sasaran_kegiatan = trim($_POST['sasaran_kegiatan'] ?? '');
        $indikator_kinerja = trim($_POST['indikator_kinerja'] ?? '');
        $target = trim($_POST['target'] ?? '');
        
        if (!$id || !$nomor || !$sasaran_kegiatan || !$indikator_kinerja || !$target) {
            echo json_encode(['success' => false, 'message' => 'Semua field harus diisi']);
            exit();
        }
        
        $sql = "UPDATE ikss_master 
                SET nomor = ?, 
                    sasaran_kegiatan = ?, 
                    indikator_kinerja = ?,
                    target = ?
                WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $nomor,
            $sasaran_kegiatan,
            $indikator_kinerja,
            $target,
            $id
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'IKSS berhasil diupdate'
        ]);
        exit();
    }
    
    // ==================== POST: Delete IKSS (admin only) ====================
    if ($method === 'POST' && $action === 'delete_ikss') {
        if (!isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized - Admin only']);
            exit();
        }
        
        $id = $_POST['id'] ?? null;
        
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID harus diisi']);
            exit();
        }
        
        // Soft delete
        $sql = "UPDATE ikss_master SET is_active = 0 WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'IKSS berhasil dihapus'
        ]);
        exit();
    }
    
    // ==================== Invalid action ====================
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
