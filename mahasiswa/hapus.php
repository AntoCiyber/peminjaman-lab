<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    try {
        $stmt = $pdo->prepare("DELETE FROM mahasiswa WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $_SESSION['flash_success'] = "Data mahasiswa berhasil dihapus.";
    } catch (PDOException $e) {
        if ($e->getCode() == '23000') {
            $_SESSION['flash_error'] = "Data mahasiswa tidak dapat dihapus karena masih memiliki riwayat peminjaman!";
        } else {
            $_SESSION['flash_error'] = "Gagal menghapus data mahasiswa: " . $e->getMessage();
        }
    }
}

header("Location: index.php");
exit;