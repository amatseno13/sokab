<?php
/**
 * SOKAB - Kertas Kerja API (CRUD)
 * List, Create, Read, Update, Delete
 */

session_start();
require_once __DIR__ . '/../includes/check_session.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');

$db = getDBConnection();
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    // ═══════════════════════════════════════════════
    // LIST - Get all kertas kerja
    // ═══════════════════════════════════════════════
    if ($action === 'list' && $method === 'GET') {
        $triwulan = $_GET['triwulan'] ?? null;
        $tahun = $_GET['tahun'] ?? 2026;
        $status = $_GET['status'] ?? null;
        
        $query = "SELECT * FROM kertas_kerja WHERE tahun = :tahun";
        $params = ['tahun' => $tahun];
        
        if ($triwulan) {
            $query .= " AND triwulan = :triwulan";
            $params['triwulan'] = $triwulan;
        }
        
        if ($status) {
            $query .= " AND status = :status";
            $params['status'] = $status;
        }
        
        $query .= " ORDER BY satker ASC, triwulan ASC";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $results
        ]);
        exit;
    }
    
    // ═══════════════════════════════════════════════
    // GET - Get single kertas kerja + details
    // ═══════════════════════════════════════════════
    if ($action === 'get' && $method === 'GET') {
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            throw new Exception('ID required');
        }
        
        // Get main kertas kerja
        $stmt = $db->prepare("SELECT * FROM kertas_kerja WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $kertas_kerja = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$kertas_kerja) {
            throw new Exception('Kertas Kerja not found');
        }
        
        // Get details
        $stmt = $db->prepare("
            SELECT * FROM kertas_kerja_detail 
            WHERE kertas_kerja_id = :id 
            ORDER BY row_order ASC
        ");
        $stmt->execute(['id' => $id]);
        $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'kertas_kerja' => $kertas_kerja,
                'details' => $details
            ]
        ]);
        exit;
    }
    
    // ═══════════════════════════════════════════════
    // CREATE - Create new kertas kerja
    // ═══════════════════════════════════════════════
    if ($action === 'create' && $method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $satker = $data['satker'] ?? null;
        $triwulan = $data['triwulan'] ?? null;
        $tahun = $data['tahun'] ?? 2026;
        
        if (!$satker || !$triwulan) {
            throw new Exception('Satker and Triwulan required');
        }
        
        $stmt = $db->prepare("
            INSERT INTO kertas_kerja (satker, nilai_sakip, predikat, triwulan, tahun, status, created_by)
            VALUES (:satker, :nilai_sakip, :predikat, :triwulan, :tahun, 'draft', :user_id)
        ");
        
        $stmt->execute([
            'satker' => $satker,
            'nilai_sakip' => $data['nilai_sakip'] ?? '',
            'predikat' => $data['predikat'] ?? '',
            'triwulan' => $triwulan,
            'tahun' => $tahun,
            'user_id' => $_SESSION['user_id']
        ]);
        
        $id = $db->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Kertas Kerja created',
            'id' => $id
        ]);
        exit;
    }
    
    // ═══════════════════════════════════════════════
    // UPDATE - Update detail cell
    // ═══════════════════════════════════════════════
    if ($action === 'update' && $method === 'PUT') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $detail_id = $data['detail_id'] ?? null;
        $field = $data['field'] ?? null;
        $value = $data['value'] ?? null;
        
        if (!$detail_id || !$field) {
            throw new Exception('detail_id and field required');
        }
        
        // Get old value
        $stmt = $db->prepare("SELECT * FROM kertas_kerja_detail WHERE id = :id");
        $stmt->execute(['id' => $detail_id]);
        $old_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$old_data) {
            throw new Exception('Detail not found');
        }
        
        $old_value = $old_data[$field] ?? null;
        
        // Update
        $stmt = $db->prepare("
            UPDATE kertas_kerja_detail 
            SET $field = :value, updated_at = NOW()
            WHERE id = :id
        ");
        
        $stmt->execute([
            'value' => $value,
            'id' => $detail_id
        ]);
        
        // Log history
        $stmt = $db->prepare("
            INSERT INTO kertas_kerja_history (detail_id, field_name, old_value, new_value, changed_by)
            VALUES (:detail_id, :field, :old_value, :new_value, :user_id)
        ");
        
        $stmt->execute([
            'detail_id' => $detail_id,
            'field' => $field,
            'old_value' => $old_value,
            'new_value' => $value,
            'user_id' => $_SESSION['user_id']
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Updated'
        ]);
        exit;
    }
    
    // ═══════════════════════════════════════════════
    // ADD DETAIL - Add new IKU row
    // ═══════════════════════════════════════════════
    if ($action === 'add-detail' && $method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $kertas_kerja_id = $data['kertas_kerja_id'] ?? null;
        
        if (!$kertas_kerja_id) {
            throw new Exception('kertas_kerja_id required');
        }
        
        // Get max row order
        $stmt = $db->prepare("
            SELECT MAX(row_order) as max_order FROM kertas_kerja_detail 
            WHERE kertas_kerja_id = :id
        ");
        $stmt->execute(['id' => $kertas_kerja_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $next_order = ($result['max_order'] ?? 0) + 1;
        
        $stmt = $db->prepare("
            INSERT INTO kertas_kerja_detail (
                kertas_kerja_id, iku_code, iku_nama, target_pk, satuan,
                alokasi_tw, realisasi, capaian_tw, capaian_pk, row_order
            ) VALUES (:kk_id, '', '', 0, '', 0, 0, 0, 0, :order)
        ");
        
        $stmt->execute([
            'kk_id' => $kertas_kerja_id,
            'order' => $next_order
        ]);
        
        $detail_id = $db->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Row added',
            'detail_id' => $detail_id
        ]);
        exit;
    }
    
    // ═══════════════════════════════════════════════
    // DELETE - Delete detail row
    // ═══════════════════════════════════════════════
    if ($action === 'delete-detail' && $method === 'DELETE') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $detail_id = $data['detail_id'] ?? null;
        
        if (!$detail_id) {
            throw new Exception('detail_id required');
        }
        
        $stmt = $db->prepare("DELETE FROM kertas_kerja_detail WHERE id = :id");
        $stmt->execute(['id' => $detail_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Row deleted'
        ]);
        exit;
    }
    
    // ═══════════════════════════════════════════════
    // FINALIZE - Lock kertas kerja
    // ═══════════════════════════════════════════════
    if ($action === 'finalize' && $method === 'PUT') {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? null;
        
        if (!$id) {
            throw new Exception('ID required');
        }
        
        $stmt = $db->prepare("
            UPDATE kertas_kerja 
            SET status = 'final', updated_at = NOW(), updated_by = :user_id
            WHERE id = :id
        ");
        
        $stmt->execute([
            'id' => $id,
            'user_id' => $_SESSION['user_id']
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Kertas Kerja finalized'
        ]);
        exit;
    }
    
    throw new Exception('Invalid action or method');
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
