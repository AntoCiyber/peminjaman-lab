<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Tendang ke halaman login jika admin belum login
if (!isset($_SESSION['admin_id'])) {
    header("Location: /peminjaman-lab/login.php");
    exit;
}

// Fungsi singkat untuk mengamankan output dari serangan XSS
function e($string) {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}