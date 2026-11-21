<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    try {
        $stmt = $pdo->prepare("DELETE FROM kategori_alat WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $_SESSION['flash_success'] = "Kategori berhasil dihapus.";
    } catch (PDOException $e) {
        if ($e->getCode() == '23000') {
            $_SESSION['flash_error'] = "Kategori tidak dapat dihapus karena masih digunakan oleh data alat!";
        } else {
            $_SESSION['flash_error'] = "Gagal menghapus kategori: " . $e->getMessage();
        }
    }
}

header("Location: index.php");
exit;