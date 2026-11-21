<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    try {
        $pdo->beginTransaction();

        // Kunci data transaksi agar tidak diproses ganda
        $stmt = $pdo->prepare("SELECT id, kode_peminjaman, alat_id, jumlah, status FROM peminjaman WHERE id = :id FOR UPDATE");
        $stmt->execute(['id' => $id]);
        $pinjam = $stmt->fetch();

        if (!$pinjam) {
            throw new Exception("Data transaksi tidak ditemukan.");
        }
        if ($pinjam['status'] === 'Dikembalikan') {
            throw new Exception("Transaksi " . $pinjam['kode_peminjaman'] . " sudah dikembalikan sebelumnya.");
        }

        // Ubah status menjadi Dikembalikan & catat tanggal kembali hari ini
        $stmtUpdatePinjam = $pdo->prepare("UPDATE peminjaman 
                                           SET status = 'Dikembalikan', tanggal_kembali = CURDATE() 
                                           WHERE id = :id");
        $stmtUpdatePinjam->execute(['id' => $id]);

        // Kembalikan stok tersedia (dijaga agar tidak melebihi stok_total)
        $stmtUpdateAlat = $pdo->prepare("UPDATE alat 
                                         SET stok_tersedia = LEAST(stok_total, stok_tersedia + :jumlah) 
                                         WHERE id = :alat_id");
        $stmtUpdateAlat->execute([
            'jumlah'  => (int)$pinjam['jumlah'],
            'alat_id' => (int)$pinjam['alat_id']
        ]);

        $pdo->commit();
        $_SESSION['flash_success'] = "Alat pada transaksi " . $pinjam['kode_peminjaman'] . " berhasil dikembalikan dan stok dipulihkan.";

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['flash_error'] = "Gagal memproses pengembalian: " . $e->getMessage();
    }
}

header("Location: index.php");
exit;