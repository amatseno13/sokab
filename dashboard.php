<?php
/**
 * SOKAB — Dashboard
 *
 * File ini sengaja tipis: hanya merangkai potongan-potongan.
 * Yang biasanya perlu disunting BUKAN file ini, melainkan:
 *
 *   config/dashboard.php     daftar menu, modal, dan berkas JS
 *   pages/dashboard/         isi tiap menu, satu file per menu
 *   includes/layout/         kerangka: head, header, sidebar, footer
 *   includes/modals/         jendela popup
 *   assets/css, assets/js    gaya dan perilaku
 *
 * Kredensial database tetap di config/database.php, tidak tersentuh refactor ini.
 */

require_once 'includes/check_session.php';
requireLogin();

$user      = getCurrentUser();
$user_name = $user['nama_lengkap'];
$user_role = $user['role'];
$username  = $user['username'];
$is_admin  = ($user_role === 'admin');

$cfg = require __DIR__ . '/config/dashboard.php';

/**
 * Muat berkas halaman kalau ada; kalau tidak, tampilkan pesan yang terlihat.
 *
 * PENTING: `global` di bawah wajib. include di dalam fungsi memakai scope fungsi,
 * sehingga variabel seperti $user_role tidak terlihat oleh berkas yang dimuat —
 * akibatnya blok <?php if ($user_role === 'admin') ?> di dalam halaman diam-diam
 * bernilai salah dan menu admin hilang tanpa pesan error.
 */
function sokab_muat(string $berkas, string $keterangan = ''): void {
    global $user, $user_name, $user_role, $username, $is_admin;

    if (is_file($berkas)) { include $berkas; return; }
    echo '<div class="loading-state">Berkas belum ada: '
       . htmlspecialchars($keterangan ?: $berkas) . '</div>';
}

/** Tag dengan penanda versi, supaya browser tidak memakai cache lama. */
function sokab_versi(string $path): string {
    $abs = __DIR__ . '/' . $path;
    return $path . '?v=' . (is_file($abs) ? filemtime($abs) : time());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOKAB - SAKIP BPS Bima</title>
<?php foreach ($cfg['cdn']['css'] as $url): ?>
    <link href="<?= htmlspecialchars($url) ?>" rel="stylesheet">
<?php endforeach; ?>
    <link rel="stylesheet" href="<?= sokab_versi('assets/css/dashboard.css') ?>">
</head>
<body>

<?php include 'includes/layout/header.php'; ?>

<div class="container">
<?php include 'includes/layout/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
<?php
foreach ($cfg['halaman'] as $h) {
    sokab_muat(__DIR__ . "/pages/dashboard/$h.php", "pages/dashboard/$h.php");
}
include 'includes/layout/footer.php';
?>
    </main>
</div>

<?php
foreach ($cfg['modal'] as $m) {
    $f = __DIR__ . "/includes/modals/$m.php";
    if (is_file($f)) sokab_muat($f);
}
?>

<!-- Data dari PHP untuk file JS. Satu-satunya jembatan PHP → JavaScript. -->
<script>
    window.SOKAB = {
        isAdmin : <?= json_encode($is_admin) ?>,
        userId  : <?= json_encode($_SESSION['user_id'] ?? null) ?>,
        userName: <?= json_encode($user_name) ?>,
        role    : <?= json_encode($user_role) ?>
    };
    const isAdmin = window.SOKAB.isAdmin;   // dipakai banyak fungsi lama
</script>

<?php foreach ($cfg['cdn']['js'] as $url): ?>
<script src="<?= htmlspecialchars($url) ?>"></script>
<?php endforeach; ?>

<?php
$daftar_js = $cfg['js'];
if ($is_admin) $daftar_js = array_merge($daftar_js, $cfg['js_admin']);

foreach ($daftar_js as $nama) {
    if (!is_file(__DIR__ . "/assets/js/$nama.js")) continue;
    echo '<script src="' . sokab_versi("assets/js/$nama.js") . '"></script>' . "\n";
}
?>
</body>
</html>
