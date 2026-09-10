<?php
/**
 * SOKAB - Reset Password Tool
 * Letakkan di: htdocs/sokab/reset_password.php
 * Akses: http://localhost/sokab/reset_password.php
 * HAPUS FILE INI SETELAH SELESAI!
 */

require_once 'config/database.php';

$results = [];
$done    = false;
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getDBConnection();

        $users = [
            [
                'username'     => 'admin',
                'password'     => $_POST['admin_pass'],
                'nama_lengkap' => 'Administrator SOKAB',
                'role'         => 'admin'
            ],
            [
                'username'     => 'user',
                'password'     => $_POST['user_pass'],
                'nama_lengkap' => 'User SOKAB',
                'role'         => 'user'
            ],
        ];

        // Reset semua user
        $pdo->exec("DELETE FROM users");

        foreach ($users as $u) {
            $hash = password_hash($u['password'], PASSWORD_BCRYPT, ['cost' => 10]);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$u['username'], $hash, $u['nama_lengkap'], $u['role']]);
            $results[] = ['username' => $u['username'], 'password' => $u['password'], 'hash' => $hash];
        }

        $done = true;

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - SOKAB</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #1e293b, #0ea5e9); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .card { background: white; border-radius: 16px; padding: 40px; max-width: 500px; width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        h1 { color: #1e293b; font-size: 1.6rem; margin-bottom: 5px; }
        .subtitle { color: #718096; font-size: 0.85rem; margin-bottom: 25px; }
        .warning { background: #fff5f5; border-left: 4px solid #fc8181; padding: 14px; border-radius: 8px; color: #c53030; font-size: 0.85rem; margin-bottom: 25px; }
        .form-group { margin-bottom: 18px; }
        label { display: block; font-size: 0.85rem; font-weight: 600; color: #2d3748; margin-bottom: 7px; }
        input[type=text], input[type=password] { width: 100%; padding: 12px 14px; border: 2px solid #e2e8f0; border-radius: 9px; font-size: 0.95rem; font-family: inherit; transition: all 0.2s; }
        input:focus { outline: none; border-color: #0ea5e9; box-shadow: 0 0 0 3px rgba(66,153,225,0.12); }
        .btn { width: 100%; padding: 13px; background: linear-gradient(135deg, #1e293b, #0ea5e9); color: white; border: none; border-radius: 9px; font-size: 1rem; font-weight: 600; cursor: pointer; font-family: inherit; transition: all 0.3s; margin-top: 5px; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(66,153,225,0.35); }
        .success-box { background: #f0fff4; border-left: 4px solid #48bb78; padding: 20px; border-radius: 8px; }
        .success-box h3 { color: #276749; margin-bottom: 15px; }
        .user-row { background: white; border: 1px solid #c6f6d5; border-radius: 8px; padding: 14px; margin-bottom: 12px; }
        .user-row .name { font-weight: 600; color: #1e293b; margin-bottom: 5px; }
        .user-row .pass { font-size: 0.85rem; color: #4a5568; margin-bottom: 8px; }
        .hash-box { background: #edf2f7; padding: 10px; border-radius: 6px; font-family: monospace; font-size: 0.72rem; word-break: break-all; color: #2d3748; }
        .error-box { background: #fff5f5; border-left: 4px solid #fc8181; padding: 14px; border-radius: 8px; color: #c53030; margin-bottom: 18px; }
        .goto { display: block; text-align: center; margin-top: 20px; padding: 12px; background: #1e293b; border-radius: 9px; text-decoration: none; color: white; font-weight: 600; font-size: 0.9rem; }
        .goto:hover { background: #1a365d; }
        .del-warn { background: #fffbeb; border-left: 4px solid #f6ad55; padding: 12px; border-radius: 8px; color: #744210; font-size: 0.82rem; margin-top: 15px; }
        hr { border: none; border-top: 1px solid #e2e8f0; margin: 22px 0; }
    </style>
</head>
<body>
<div class="card">
    <h1>🔐 Reset Password</h1>
    <p class="subtitle">Generate hash baru & update database SOKAB</p>

    <div class="warning">
        ⚠️ <strong>HAPUS file ini dari server setelah selesai!</strong>
    </div>

    <?php if ($error): ?>
    <div class="error-box">❌ <strong>Error:</strong> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($done): ?>

        <div class="success-box">
            <h3>✅ Password berhasil direset!</h3>
            <?php foreach ($results as $r): ?>
            <div class="user-row">
                <div class="name">👤 <?= htmlspecialchars($r['username']) ?></div>
                <div class="pass">🔑 Password baru: <strong><?= htmlspecialchars($r['password']) ?></strong></div>
                <div style="font-size:0.75rem;color:#718096;margin-bottom:4px;">Hash yang tersimpan di database:</div>
                <div class="hash-box"><?= htmlspecialchars($r['hash']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <a href="index.php" class="goto">🚀 Langsung ke Halaman Login</a>

        <div class="del-warn">
            🗑️ <strong>Penting:</strong> Segera hapus file <code>reset_password.php</code> dari folder sokab setelah login berhasil!
        </div>

    <?php else: ?>

        <form method="POST">
            <hr>
            <div class="form-group">
                <label>🔑 Password Baru untuk <span style="color:#0ea5e9">admin</span></label>
                <input type="text" name="admin_pass" placeholder="Contoh: Admin@BPS2026" value="admin123" required>
            </div>
            <div class="form-group">
                <label>🔑 Password Baru untuk <span style="color:#48bb78">user</span></label>
                <input type="text" name="user_pass" placeholder="Contoh: User@BPS2026" value="user123" required>
            </div>
            <button type="submit" class="btn">⚡ Reset & Update Database</button>
        </form>

    <?php endif; ?>
</div>
</body>
</html>
