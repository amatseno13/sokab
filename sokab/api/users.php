<?php
/**
 * SOKAB - API Kelola User (Admin Only)
 */
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// Cek login dan role admin
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak. Hanya admin yang dapat mengelola user.']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $pdo = getDBConnection();

    // ── GET ALL USERS ────────────────────────────────────
    if ($method === 'GET' && !$action) {
        $stmt = $pdo->query("
            SELECT id, username, nama_lengkap, role, created_at
            FROM users 
            ORDER BY role DESC, nama_lengkap ASC
        ");
        $users = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $users]);

    // ── TAMBAH USER ──────────────────────────────────────
    } elseif ($method === 'POST' && $action === 'tambah') {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        
        $username = trim($data['username'] ?? '');
        $nama_lengkap = trim($data['nama_lengkap'] ?? '');
        $password = trim($data['password'] ?? '');
        $role = $data['role'] ?? 'user';

        // Validasi
        if (!$username || !$nama_lengkap || !$password) {
            echo json_encode(['success' => false, 'message' => 'Username, nama lengkap, dan password wajib diisi']);
            exit();
        }

        if (strlen($password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password minimal 6 karakter']);
            exit();
        }

        if (!in_array($role, ['admin', 'user'])) {
            echo json_encode(['success' => false, 'message' => 'Role tidak valid']);
            exit();
        }

        // Cek username sudah ada
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Username sudah digunakan']);
            exit();
        }

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert user
        $stmt = $pdo->prepare("
            INSERT INTO users (username, password, nama_lengkap, role, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$username, $hashed_password, $nama_lengkap, $role]);

        echo json_encode([
            'success' => true, 
            'message' => 'User berhasil ditambahkan',
            'id' => $pdo->lastInsertId()
        ]);

    // ── EDIT USER ────────────────────────────────────────
    } elseif ($method === 'POST' && $action === 'edit') {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        
        $id = intval($data['id'] ?? 0);
        $username = trim($data['username'] ?? '');
        $nama_lengkap = trim($data['nama_lengkap'] ?? '');
        $role = $data['role'] ?? 'user';

        if (!$id || !$username || !$nama_lengkap) {
            echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
            exit();
        }

        if (!in_array($role, ['admin', 'user'])) {
            echo json_encode(['success' => false, 'message' => 'Role tidak valid']);
            exit();
        }

        // Cek user exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'User tidak ditemukan']);
            exit();
        }

        // Cek username conflict (selain diri sendiri)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $id]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Username sudah digunakan']);
            exit();
        }

        // Update user
        $stmt = $pdo->prepare("
            UPDATE users 
            SET username = ?, nama_lengkap = ?, role = ?
            WHERE id = ?
        ");
        $stmt->execute([$username, $nama_lengkap, $role, $id]);

        echo json_encode(['success' => true, 'message' => 'User berhasil diperbarui']);

    // ── RESET PASSWORD ───────────────────────────────────
    } elseif ($method === 'POST' && $action === 'reset_password') {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        
        $id = intval($data['id'] ?? 0);
        $new_password = trim($data['new_password'] ?? '');

        if (!$id || !$new_password) {
            echo json_encode(['success' => false, 'message' => 'ID dan password baru wajib diisi']);
            exit();
        }

        if (strlen($new_password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password minimal 6 karakter']);
            exit();
        }

        // Hash password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update password
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed_password, $id]);

        echo json_encode(['success' => true, 'message' => 'Password berhasil direset']);

    // ── HAPUS USER ───────────────────────────────────────
    } elseif ($method === 'POST' && $action === 'hapus') {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        
        $id = intval($data['id'] ?? 0);

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
            exit();
        }

        // Tidak bisa hapus diri sendiri
        if ($id == $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'Tidak dapat menghapus akun sendiri']);
            exit();
        }

        // Hapus user
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode(['success' => true, 'message' => 'User berhasil dihapus']);

    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
    }

} catch (PDOException $e) {
    error_log('Users API Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log('Users API Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
