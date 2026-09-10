<?php
/**
 * SOKAB - Login Page
 * Identik struktur dengan SAMAWA
 */

session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error   = $_GET['error']   ?? '';
$message = $_GET['message'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SOKAB BPS Bima</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
    <div class="login-container">
        <!-- Kiri - Branding -->
        <div class="login-left">
            <div class="logo-container">
                <img src="assets/images/logo-bps.png" alt="BPS Bima" style="width:100%;max-width:150px;border-radius:12px;margin-bottom:1.5rem;box-shadow:0 8px 24px rgba(0,0,0,0.15);">
                <h1 class="brand-name">SOKAB</h1>
                <p class="brand-tagline">SAKIP Online BPS Kota Bima</p>
            </div>
            <p class="brand-description">
                Sistem pengelolaan dokumen SAKIP BPS Kota Bima terintegrasi.
            </p>
        </div>

        <!-- Kanan - Form Login -->
        <div class="login-right">
            <div class="login-header">
                <h2 class="login-title">Selamat Datang!</h2>
                <p class="login-subtitle">Silakan login untuk melanjutkan</p>
            </div>

            <div id="alertContainer"></div>

            <form class="login-form" id="loginForm" method="POST">
                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-wrapper">
                        <span class="input-icon">👤</span>
                        <input type="text" id="username" name="username"
                               class="form-input" placeholder="Masukkan username"
                               required autocomplete="username" autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon">🔒</span>
                        <input type="password" id="password" name="password"
                               class="form-input" placeholder="Masukkan password"
                               required autocomplete="current-password">
                        <button type="button" class="toggle-password" id="togglePassword">👁️</button>
                    </div>
                </div>

                <div class="form-options">
                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember" value="1">
                        <label for="remember">Ingat saya</label>
                    </div>
                </div>

                <button type="submit" class="btn-login" id="btnLogin">Login</button>
            </form>

            <div class="login-footer">
                <p>BPS Kota Bima &copy; <?php echo date('Y'); ?></p>
            </div>
        </div>
    </div>

    <script>
        window.addEventListener('DOMContentLoaded', function() {
            <?php if ($error): ?>
                showAlert('<?php echo getErrorMessage($error); ?>', 'error');
            <?php endif; ?>
            <?php if ($message): ?>
                showAlert('<?php echo getMessage($message); ?>', 'success');
            <?php endif; ?>
        });

        document.getElementById('togglePassword').addEventListener('click', function() {
            const input = document.getElementById('password');
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            this.textContent = isPassword ? '👁️‍🗨️' : '👁️';
        });

        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const btn = document.getElementById('btnLogin');
            btn.disabled = true;
            btn.classList.add('loading');
            btn.textContent = '';

            try {
                const formData = new FormData();
                formData.append('username', document.getElementById('username').value);
                formData.append('password', document.getElementById('password').value);

                const response = await fetch('includes/login.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showAlert(data.message, 'success');
                    setTimeout(() => { window.location.href = data.redirect; }, 500);
                } else {
                    showAlert(data.message, 'error');
                    btn.disabled = false;
                    btn.classList.remove('loading');
                    btn.textContent = 'Login';
                }
            } catch (err) {
                showAlert('Terjadi kesalahan koneksi. Silakan coba lagi.', 'error');
                btn.disabled = false;
                btn.classList.remove('loading');
                btn.textContent = 'Login';
            }
        });

        function showAlert(message, type = 'error') {
            const container = document.getElementById('alertContainer');
            container.innerHTML = `<div class="alert alert-${type} show">${message}</div>`;
            setTimeout(() => {
                const alert = container.querySelector('.alert');
                if (alert) alert.classList.remove('show');
            }, 5000);
        }
    </script>
</body>
</html>

<?php
function getErrorMessage($error) {
    $messages = [
        'not_logged_in'    => 'Harus login terlebih dahulu, Gan.',
        'session_timeout'  => 'Sesi Bro telah berakhir. Silakan login kembali.',
        'access_denied'    => 'Aksesmu ditolak, Gan.',
        'invalid_credentials' => 'Username atau password salah, Gan.'
    ];
    return $messages[$error] ?? 'Terjadi kesalahan. Silakan coba lagi.';
}

function getMessage($message) {
    $messages = [
        'logout_success'   => 'Agan telah berhasil logout.',
        'password_changed' => 'Password berhasil diubah.'
    ];
    return $messages[$message] ?? '';
}
?>