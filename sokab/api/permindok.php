<?php
/**
 * API untuk Evaluasi Permindok
 * Handle upload, list, dan delete dokumen evaluasi permindok
 */

session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Check login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'user';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            // Get all documents
            $sql = "SELECT * FROM evaluasi_permindok ORDER BY tahun DESC, upload_date DESC";
            $stmt = $pdo->query($sql);
            $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $docs
            ]);
            break;
            
        case 'upload':
            // Only admin can upload
            if ($user_role !== 'admin') {
                echo json_encode(['success' => false, 'message' => 'Akses ditolak. Hanya Admin yang bisa upload.']);
                exit;
            }
            
            $judul = $_POST['judul'] ?? '';
            $tahun = $_POST['tahun'] ?? date('Y');
            $upload_method = $_POST['upload_method'] ?? 'file';
            
            if (empty($judul)) {
                echo json_encode(['success' => false, 'message' => 'Judul harus diisi']);
                exit;
            }
            
            $file_path = null;
            $gdrive_link = null;
            $filename = null;
            $original_name = null;
            $filesize = null;
            
            if ($upload_method === 'file') {
                // Handle file upload
                
                // Check if file was uploaded
                if (!isset($_FILES['file'])) {
                    echo json_encode(['success' => false, 'message' => 'File tidak ditemukan. Pastikan field name="file"']);
                    exit;
                }
                
                $file = $_FILES['file'];
                
                // Check for upload errors
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $error_messages = [
                        UPLOAD_ERR_INI_SIZE => 'File melebihi batas upload_max_filesize di php.ini',
                        UPLOAD_ERR_FORM_SIZE => 'File melebihi batas MAX_FILE_SIZE',
                        UPLOAD_ERR_PARTIAL => 'File hanya terupload sebagian',
                        UPLOAD_ERR_NO_FILE => 'Tidak ada file yang diupload',
                        UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary tidak ditemukan',
                        UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk',
                        UPLOAD_ERR_EXTENSION => 'Upload dihentikan oleh extension'
                    ];
                    $error_msg = $error_messages[$file['error']] ?? 'Error upload: ' . $file['error'];
                    echo json_encode(['success' => false, 'message' => $error_msg]);
                    exit;
                }
                
                // Check file size
                $max_size = 20 * 1024 * 1024; // 20MB
                if ($file['size'] > $max_size) {
                    echo json_encode(['success' => false, 'message' => 'Ukuran file maksimal 20MB (File Anda: ' . round($file['size']/1024/1024, 2) . 'MB)']);
                    exit;
                }
                
                if ($file['size'] == 0) {
                    echo json_encode(['success' => false, 'message' => 'File kosong (0 bytes)']);
                    exit;
                }
                
                // Check file extension
                $allowed_ext = ['pdf'];
                $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                
                if (!in_array($file_ext, $allowed_ext)) {
                    echo json_encode(['success' => false, 'message' => 'Hanya file PDF yang diperbolehkan (File Anda: .' . $file_ext . ')']);
                    exit;
                }
                
                // Create upload directory if not exists
                $upload_dir = dirname(__DIR__) . '/uploads/permindok/';
                if (!is_dir($upload_dir)) {
                    if (!mkdir($upload_dir, 0777, true)) {
                        echo json_encode(['success' => false, 'message' => 'Gagal membuat folder: ' . $upload_dir]);
                        exit;
                    }
                    chmod($upload_dir, 0777);
                }
                
                // Check if directory is writable
                if (!is_writable($upload_dir)) {
                    echo json_encode(['success' => false, 'message' => 'Folder tidak writable: ' . $upload_dir . ' - Jalankan: chmod 777 uploads/permindok/']);
                    exit;
                }
                
                // Generate unique filename
                $filename = 'permindok_' . $tahun . '_' . time() . '_' . uniqid() . '.' . $file_ext;
                $upload_path = $upload_dir . $filename;
                
                // Debug info
                if (!is_uploaded_file($file['tmp_name'])) {
                    echo json_encode(['success' => false, 'message' => 'File temporary tidak valid: ' . $file['tmp_name']]);
                    exit;
                }
                
                // Move uploaded file
                if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
                    $error = error_get_last();
                    echo json_encode(['success' => false, 'message' => 'Gagal move file dari ' . $file['tmp_name'] . ' ke ' . $upload_path . '. Error: ' . ($error['message'] ?? 'unknown')]);
                    exit;
                }
                
                // Verify file was actually moved
                if (!file_exists($upload_path)) {
                    echo json_encode(['success' => false, 'message' => 'File tidak ditemukan setelah upload: ' . $upload_path]);
                    exit;
                }
                
                $file_path = 'uploads/permindok/' . $filename;
                $original_name = $file['name'];
                $filesize = $file['size'];
                
            } else {
                // Handle Google Drive link
                $gdrive_link = $_POST['gdrive_link'] ?? '';
                
                if (empty($gdrive_link)) {
                    echo json_encode(['success' => false, 'message' => 'Link Google Drive harus diisi']);
                    exit;
                }
                
                // Validate URL
                if (!filter_var($gdrive_link, FILTER_VALIDATE_URL)) {
                    echo json_encode(['success' => false, 'message' => 'URL tidak valid. Format: https://drive.google.com/...']);
                    exit;
                }
            }
            
            // Insert to database
            $sql = "INSERT INTO evaluasi_permindok (judul, tahun, upload_method, file_path, gdrive_link, filename, original_name, filesize, uploaded_by) 
                    VALUES (:judul, :tahun, :upload_method, :file_path, :gdrive_link, :filename, :original_name, :filesize, :uploaded_by)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'judul' => $judul,
                'tahun' => $tahun,
                'upload_method' => $upload_method,
                'file_path' => $file_path,
                'gdrive_link' => $gdrive_link,
                'filename' => $filename,
                'original_name' => $original_name,
                'filesize' => $filesize,
                'uploaded_by' => $user_id
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Dokumen berhasil diupload'
            ]);
            break;
            
        case 'delete':
            // Only admin can delete
            if ($user_role !== 'admin') {
                echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
                exit;
            }
            
            $id = $_POST['id'] ?? 0;
            
            // Get file info first
            $sql = "SELECT * FROM evaluasi_permindok WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['id' => $id]);
            $doc = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$doc) {
                echo json_encode(['success' => false, 'message' => 'Dokumen tidak ditemukan']);
                exit;
            }
            
            // Delete file if exists
            if ($doc['upload_method'] === 'file' && !empty($doc['file_path'])) {
                $file_path = '../' . $doc['file_path'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
            
            // Delete from database
            $sql = "DELETE FROM evaluasi_permindok WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['id' => $id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Dokumen berhasil dihapus'
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
