<?php
/**
 * SOKAB - API LAKIN Upload
 * Updated: Support Upload File OR Cloud Storage Link
 */
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';

$action  = $_GET['action'] ?? $_POST['action'] ?? '';
$isAdmin = ($_SESSION['role'] === 'admin');

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit();
}

$UPLOAD_DIR = __DIR__ . '/../uploads/lakin/';
if (!is_dir($UPLOAD_DIR)) mkdir($UPLOAD_DIR, 0755, true);

try {
    $pdo = getDBConnection();

    // GET - list file
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && !$action) {
        header('Content-Type: application/json');
        $tahun = $_GET['tahun'] ?? null;
        $tipe  = $_GET['tipe']  ?? null;
        $sql   = "SELECT l.*, u.nama_lengkap AS uploaded_by_name FROM lakin_files l LEFT JOIN users u ON l.uploaded_by = u.id WHERE 1=1";
        $params = [];
        if ($tahun) { $sql .= " AND l.tahun = ?"; $params[] = $tahun; }
        if ($tipe)  { $sql .= " AND l.tipe = ?";  $params[] = $tipe; }
        $sql .= " ORDER BY l.tahun DESC, l.tipe ASC, l.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        // Tambah filesize human readable & upload_method
        foreach ($rows as &$r) {
            $r['filesize_human'] = $r['filesize'] ? formatBytes($r['filesize']) : '-';
            $r['upload_method'] = $r['upload_method'] ?? 'file';
        }
        echo json_encode(['success'=>true,'data'=>$rows]);

    // POST - upload
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'upload') {
        header('Content-Type: application/json');
        if (!$isAdmin) { echo json_encode(['success'=>false,'message'=>'Akses ditolak']); exit(); }

        $judul  = trim($_POST['judul'] ?? '');
        $tahun  = intval($_POST['tahun'] ?? 0);
        $tipe   = $_POST['tipe'] ?? '';
        $ket    = trim($_POST['keterangan'] ?? '');
        $upload_method = $_POST['upload_method'] ?? 'file';

        if (!$judul || !$tahun || !in_array($tipe, ['draft','final'])) {
            echo json_encode(['success'=>false,'message'=>'Judul, tahun, dan tipe wajib diisi']); exit();
        }

        $filename = null;
        $original_name = null;
        $filesize = 0;
        $gdrive_link = null;

        if ($upload_method === 'gdrive') {
            $gdrive_link = trim($_POST['gdrive_link'] ?? '');
            if (!$gdrive_link) {
                echo json_encode(['success'=>false,'message'=>'Link Cloud Storage harus diisi']); exit();
            }
            $filename = 'gdrive_' . $tahun . '_' . $tipe . '_' . time();
            $original_name = 'File dari Google Drive';
        } else {
            if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success'=>false,'message'=>'File tidak valid atau gagal upload']); exit();
            }
            $allowedTypes = ['application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($_FILES['file']['tmp_name']);
            if (!in_array($mimeType, $allowedTypes)) {
                echo json_encode(['success'=>false,'message'=>'Hanya file PDF, Word, atau Excel yang diizinkan']); exit();
            }
            if ($_FILES['file']['size'] > 20 * 1024 * 1024) {
                echo json_encode(['success'=>false,'message'=>'Ukuran file maksimal 20MB']); exit();
            }
            $ext      = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
            $filename = 'lakin_' . $tahun . '_' . $tipe . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $dest     = $UPLOAD_DIR . $filename;
            if (!move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
                echo json_encode(['success'=>false,'message'=>'Gagal menyimpan file']); exit();
            }
            $original_name = $_FILES['file']['name'];
            $filesize = $_FILES['file']['size'];
        }

        $stmt = $pdo->prepare("INSERT INTO lakin_files (judul,tahun,tipe,filename,original_name,filesize,keterangan,uploaded_by,upload_method,gdrive_link) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$judul,$tahun,$tipe,$filename,$original_name,$filesize,$ket,$_SESSION['user_id'],$upload_method,$gdrive_link]);
        echo json_encode(['success'=>true,'message'=>'LAKIN berhasil diupload','id'=>$pdo->lastInsertId()]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'hapus') {
        header('Content-Type: application/json');
        if (!$isAdmin) { echo json_encode(['success'=>false,'message'=>'Akses ditolak']); exit(); }
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $id   = intval($data['id'] ?? $_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['success'=>false,'message'=>'ID tidak valid']); exit(); }
        $stmt = $pdo->prepare("SELECT filename, upload_method FROM lakin_files WHERE id=?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row && ($row['upload_method'] ?? 'file') === 'file' && file_exists($UPLOAD_DIR . $row['filename'])) {
            unlink($UPLOAD_DIR . $row['filename']);
        }
        $pdo->prepare("DELETE FROM lakin_files WHERE id=?")->execute([$id]);
        echo json_encode(['success'=>true,'message'=>'File berhasil dihapus']);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'download') {
        $id   = intval($_GET['id'] ?? 0);
        if (!$id) { http_response_code(404); exit('File tidak ditemukan'); }
        $stmt = $pdo->prepare("SELECT * FROM lakin_files WHERE id=?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) { http_response_code(404); exit('File tidak ditemukan'); }
        if (($row['upload_method'] ?? 'file') === 'gdrive' && $row['gdrive_link']) {
            header('Location: ' . $row['gdrive_link']);
            exit();
        }
        if (!file_exists($UPLOAD_DIR . $row['filename'])) {
            http_response_code(404); exit('File tidak ditemukan');
        }
        $mimeTypes = ['pdf'=>'application/pdf','doc'=>'application/msword','docx'=>'application/vnd.openxmlformats-officedocument.wordprocessingml.document','xlsx'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
        $ext  = strtolower(pathinfo($row['filename'], PATHINFO_EXTENSION));
        $mime = $mimeTypes[$ext] ?? 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $row['original_name'] . '"');
        header('Content-Length: ' . filesize($UPLOAD_DIR . $row['filename']));
        readfile($UPLOAD_DIR . $row['filename']);
        exit();
    }
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}

function formatBytes($bytes) {
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1048576) return round($bytes/1024,1) . ' KB';
    return round($bytes/1048576,1) . ' MB';
}
?>
