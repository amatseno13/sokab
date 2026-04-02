<?php
/**
 * SOKAB - API Dokumen (Upload File OR Cloud Link)
 * Updated: Support dual method seperti LAKIN
 */
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit(); }

$method  = $_SERVER['REQUEST_METHOD'];
$action  = $_GET['action'] ?? $_POST['action'] ?? '';
$menu    = $_GET['menu'] ?? '';
$isAdmin = ($_SESSION['role'] === 'admin');

$UPLOAD_DIR = __DIR__ . '/../uploads/dokumen/';
if (!is_dir($UPLOAD_DIR)) mkdir($UPLOAD_DIR, 0755, true);

try {
    $pdo = getDBConnection();

    if ($method === 'GET' && !$action) {
        if (!$menu) { echo json_encode(['success'=>false,'message'=>'menu_key diperlukan']); exit(); }
        $stmt = $pdo->prepare("SELECT d.*, u.nama_lengkap AS created_by_name FROM dokumen_links d LEFT JOIN users u ON d.created_by = u.id WHERE d.menu_key = ? ORDER BY d.tahun DESC, d.urutan ASC, d.created_at DESC");
        $stmt->execute([$menu]);
        $rows = $stmt->fetchAll();
        
        // Add upload_method & filesize_human for backward compatibility
        foreach ($rows as &$r) {
            $r['upload_method'] = $r['upload_method'] ?? 'gdrive';
            $r['filesize_human'] = $r['filesize'] ? formatBytes($r['filesize']) : '-';
        }
        echo json_encode(['success'=>true,'data'=>$rows]);

    } elseif ($method === 'POST' && $action === 'tambah') {
        if (!$isAdmin) { echo json_encode(['success'=>false,'message'=>'Akses ditolak']); exit(); }

        $menu_key = $_POST['menu_key'] ?? '';
        $judul = trim($_POST['judul'] ?? '');
        $tahun = $_POST['tahun'] ?? null;
        $sub_kategori = $_POST['sub_kategori'] ?? null;
        $keterangan = trim($_POST['keterangan'] ?? '');
        $urutan = intval($_POST['urutan'] ?? 0);
        $upload_method = $_POST['upload_method'] ?? 'gdrive';

        if (!$menu_key || !$judul) {
            echo json_encode(['success'=>false,'message'=>'Menu dan judul wajib diisi']); exit();
        }

        $filename = null;
        $original_name = null;
        $filesize = 0;
        $url_gdrive = null;
        $file_path = null;

        if ($upload_method === 'file') {
            // UPLOAD FILE
            if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                $errorMsg = 'File tidak valid';
                if (!empty($_FILES['file']['error'])) {
                    $errors = [
                        UPLOAD_ERR_INI_SIZE => 'File melebihi upload_max_filesize',
                        UPLOAD_ERR_FORM_SIZE => 'File melebihi MAX_FILE_SIZE',
                        UPLOAD_ERR_PARTIAL => 'File hanya terupload sebagian',
                        UPLOAD_ERR_NO_FILE => 'Tidak ada file',
                        UPLOAD_ERR_NO_TMP_DIR => 'Folder temp tidak ada',
                        UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file',
                        UPLOAD_ERR_EXTENSION => 'Upload dihentikan extension'
                    ];
                    $errorMsg = $errors[$_FILES['file']['error']] ?? 'Error: ' . $_FILES['file']['error'];
                }
                echo json_encode(['success'=>false,'message'=>$errorMsg]); exit();
            }

            $allowedTypes = ['application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','image/png','image/jpeg'];
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($_FILES['file']['tmp_name']);

            if (!in_array($mimeType, $allowedTypes)) {
                echo json_encode(['success'=>false,'message'=>'Hanya file PDF, Word, Excel, PNG, JPG yang diizinkan. Type: ' . $mimeType]); exit();
            }

            if ($_FILES['file']['size'] > 20 * 1024 * 1024) {
                echo json_encode(['success'=>false,'message'=>'Ukuran file maksimal 20MB']); exit();
            }

            $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
            $filename = 'dok_' . $menu_key . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $dest = $UPLOAD_DIR . $filename;

            // CEK FOLDER WRITABLE
            if (!is_writable($UPLOAD_DIR)) {
                echo json_encode(['success'=>false,'message'=>'Folder tidak writable: ' . $UPLOAD_DIR . ' - Jalankan: chmod 777 uploads/dokumen/']); exit();
            }

            // CEK FILE TMP
            if (!is_uploaded_file($_FILES['file']['tmp_name'])) {
                echo json_encode(['success'=>false,'message'=>'File temporary tidak valid: ' . $_FILES['file']['tmp_name']]); exit();
            }

            if (!move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
                $error = error_get_last();
                echo json_encode(['success'=>false,'message'=>'Gagal move file dari ' . $_FILES['file']['tmp_name'] . ' ke ' . $dest . '. Error: ' . ($error['message'] ?? 'unknown')]); exit();
            }

            // VERIFIKASI FILE TERSIMPAN
            if (!file_exists($dest)) {
                echo json_encode(['success'=>false,'message'=>'File tidak ditemukan setelah upload: ' . $dest]); exit();
            }

            $original_name = $_FILES['file']['name'];
            $filesize = $_FILES['file']['size'];
            $file_path = 'uploads/dokumen/' . $filename;

        } else {
            // GDRIVE LINK
            $url_gdrive = trim($_POST['url_gdrive'] ?? '');
            
            if (!$url_gdrive) {
                echo json_encode(['success'=>false,'message'=>'URL Link harus diisi']); exit();
            }
        }

        $stmt = $pdo->prepare("INSERT INTO dokumen_links (menu_key,judul,tahun,sub_kategori,url_gdrive,file_path,filename,original_name,filesize,upload_method,keterangan,urutan,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$menu_key,$judul,$tahun,$sub_kategori,$url_gdrive,$file_path,$filename,$original_name,$filesize,$upload_method,$keterangan,$urutan,$_SESSION['user_id']]);
        
        echo json_encode(['success'=>true,'message'=>'Dokumen berhasil ditambahkan','id'=>$pdo->lastInsertId()]);

    } elseif ($method === 'POST' && $action === 'edit') {
        if (!$isAdmin) { echo json_encode(['success'=>false,'message'=>'Akses ditolak']); exit(); }
        
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $id = intval($data['id'] ?? 0);
        
        if (!$id || empty($data['judul'])) { 
            echo json_encode(['success'=>false,'message'=>'Data tidak lengkap']); exit(); 
        }
        
        // Get existing data
        $stmt = $pdo->prepare("SELECT * FROM dokumen_links WHERE id=?");
        $stmt->execute([$id]);
        $existing = $stmt->fetch();
        
        if (!$existing) {
            echo json_encode(['success'=>false,'message'=>'Dokumen tidak ditemukan']); exit();
        }
        
        // Simple update (judul, tahun, keterangan, urutan only - no file change)
        $stmt = $pdo->prepare("UPDATE dokumen_links SET judul=?,tahun=?,sub_kategori=?,keterangan=?,urutan=? WHERE id=?");
        $stmt->execute([$data['judul'],$data['tahun']??null,$data['sub_kategori']??null,$data['keterangan']??'',$data['urutan']??0,$id]);
        
        echo json_encode(['success'=>true,'message'=>'Dokumen berhasil diperbarui']);

    } elseif ($method === 'POST' && $action === 'hapus') {
        if (!$isAdmin) { echo json_encode(['success'=>false,'message'=>'Akses ditolak']); exit(); }
        
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $id = intval($data['id'] ?? 0);
        
        if (!$id) { echo json_encode(['success'=>false,'message'=>'ID tidak valid']); exit(); }
        
        // Get file info before delete
        $stmt = $pdo->prepare("SELECT filename, upload_method FROM dokumen_links WHERE id=?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        
        // Delete physical file if upload method = file
        if ($row && ($row['upload_method'] ?? 'gdrive') === 'file' && $row['filename'] && file_exists($UPLOAD_DIR . $row['filename'])) {
            unlink($UPLOAD_DIR . $row['filename']);
        }
        
        $pdo->prepare("DELETE FROM dokumen_links WHERE id=?")->execute([$id]);
        echo json_encode(['success'=>true,'message'=>'Dokumen berhasil dihapus']);
        
    } elseif ($method === 'GET' && $action === 'download') {
        $id = intval($_GET['id'] ?? 0);
        if (!$id) { http_response_code(404); exit('File tidak ditemukan'); }

        $stmt = $pdo->prepare("SELECT * FROM dokumen_links WHERE id=?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        
        if (!$row) { http_response_code(404); exit('File tidak ditemukan'); }
        
        // If GDrive link, redirect
        if (($row['upload_method'] ?? 'gdrive') === 'gdrive' && $row['url_gdrive']) {
            header('Location: ' . $row['url_gdrive']);
            exit();
        }
        
        // If file
        if (!file_exists($UPLOAD_DIR . $row['filename'])) {
            http_response_code(404); exit('File tidak ditemukan');
        }

        $mimeTypes = ['pdf'=>'application/pdf','doc'=>'application/msword','docx'=>'application/vnd.openxmlformats-officedocument.wordprocessingml.document','xlsx'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg'];
        $ext = strtolower(pathinfo($row['filename'], PATHINFO_EXTENSION));
        $mime = $mimeTypes[$ext] ?? 'application/octet-stream';

        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $row['original_name'] . '"');
        header('Content-Length: ' . filesize($UPLOAD_DIR . $row['filename']));
        readfile($UPLOAD_DIR . $row['filename']);
        exit();
    }

} catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}

function formatBytes($bytes) {
    if (!$bytes) return '-';
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1048576) return round($bytes/1024,1) . ' KB';
    return round($bytes/1048576,1) . ' MB';
}
?>
