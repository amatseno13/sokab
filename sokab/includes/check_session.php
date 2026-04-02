<?php
/**
 * SOKAB - Session Checker
 * Identik dengan SAMAWA
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']) &&
           isset($_SESSION['username']) &&
           isset($_SESSION['role']);
}

function checkSessionTimeout() {
    if (!isset($_SESSION['last_activity'])) return false;
    $inactive = time() - $_SESSION['last_activity'];
    if ($inactive > SESSION_TIMEOUT) return false;
    $_SESSION['last_activity'] = time();
    return true;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php?error=not_logged_in');
        exit();
    }
    if (!checkSessionTimeout()) {
        session_destroy();
        header('Location: index.php?error=session_timeout');
        exit();
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    return [
        'id'           => $_SESSION['user_id'],
        'username'     => $_SESSION['username'],
        'nama_lengkap' => $_SESSION['nama_lengkap'] ?? '',
        'role'         => $_SESSION['role']
    ];
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function sanitize($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}
?>
