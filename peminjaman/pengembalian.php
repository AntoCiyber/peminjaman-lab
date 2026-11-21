<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$page_title  = 'Pengembalian Alat';
$breadcrumb  = 'Ruang kerja / Sirkulasi';
$active_menu = 'pengembalian';

$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error   = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Proses pengembalian alat jika tombol "Proses Kembali" diklik
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['peminjaman_id'])) {
    $id = (int) $_POST['peminjaman_id'];

    if ($id > 0) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT id, kode_peminjaman, alat_id, jumlah, status 
                                   FROM peminjaman 
                                   WHERE id = :id FOR UPDATE");
            $stmt->execute(['id' => $id]);
            $pinjam = $stmt->fetch();

            if (!$pinjam) {
                throw new Exception("Data peminjaman tidak ditemukan.");
            }
            if ($pinjam['status'] === 'Dikembalikan') {
                throw new Exception("Transaksi " . $pinjam['kode_peminjaman'] . " sudah dikembalikan sebelumnya.");
            }

            // Update status peminjaman & catat tanggal kembali
            $stmtUpdatePinjam = $pdo->prepare("UPDATE peminjaman 
                                               SET status = 'Dikembalikan', tanggal_kembali = CURDATE() 
                                               WHERE id = :id");
            $stmtUpdatePinjam->execute(['id' => $id]);

            // Kembalikan stok tersedia pada tabel alat
            $stmtUpdateAlat = $pdo->prepare("UPDATE alat 
                                             SET stok_tersedia = LEAST(stok_total, stok_tersedia + :jumlah) 
                                             WHERE id = :alat_id");
            $stmtUpdateAlat->execute([
                'jumlah'  => (int) $pinjam['jumlah'],
                'alat_id' => (int) $pinjam['alat_id']
            ]);

            $pdo->commit();
            $_SESSION['flash_success'] = "Pengembalian transaksi " . $pinjam['kode_peminjaman'] . " berhasil diproses dan stok alat telah dipulihkan.";
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $_SESSION['flash_error'] = "Gagal memproses pengembalian: " . $e->getMessage();
        }
    }

    header("Location: pengembalian.php");
    exit;
}

$search      = trim($_GET['q'] ?? '');
$aktif_list  = [];
$riwayat_list = [];

try {
    // Otomatis tandai peminjaman aktif yang lewat jatuh tempo menjadi Terlambat
    $pdo->exec("UPDATE peminjaman 
                SET status = 'Terlambat' 
                WHERE status = 'Dipinjam' AND tanggal_jatuh_tempo < CURDATE()");

    $keyword = "%$search%";

    // 1. Ambil daftar peminjaman aktif (Dipinjam / Terlambat) yang menunggu dikembalikan
    $sqlAktif = "SELECT p.*, 
                        m.nim, m.nama AS nama_mahasiswa, m.no_hp,
                        a.kode_alat, a.nama_alat
                 FROM peminjaman p
                 JOIN mahasiswa m ON p.mahasiswa_id = m.id
                 JOIN alat a ON p.alat_id = a.id
                 WHERE p.status IN ('Dipinjam', 'Terlambat')
                   AND (p.kode_peminjaman LIKE :q1 OR m.nim LIKE :q2 OR m.nama LIKE :q3 OR a.nama_alat LIKE :q4)
                 ORDER BY FIELD(p.status, 'Terlambat', 'Dipinjam'), p.tanggal_jatuh_tempo ASC";

    $stmtAktif = $pdo->prepare($sqlAktif);
    $stmtAktif->execute([
        ':q1' => $keyword,
        ':q2' => $keyword,
        ':q3' => $keyword,
        ':q4' => $keyword
    ]);
    $aktif_list = $stmtAktif->fetchAll();

    // 2. Ambil 8 riwayat pengembalian terbaru
    $sqlRiwayat = "SELECT p.*, 
                          m.nim, m.nama AS nama_mahasiswa,
                          a.kode_alat, a.nama_alat
                   FROM peminjaman p
                   JOIN mahasiswa m ON p.mahasiswa_id = m.id
                   JOIN alat a ON p.alat_id = a.id
                   WHERE p.status = 'Dikembalikan'
                   ORDER BY p.tanggal_kembali DESC, p.id DESC
                   LIMIT 8";
    $riwayat_list = $pdo->query($sqlRiwayat)->fetchAll();

} catch (PDOException $e) {
    $flash_error = "Gagal memuat data pengembalian: " . $e->getMessage();
}

require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Pengembalian Alat</h4>
        <p class="text-muted mb-0" style="font-size: 14px;">Verifikasi pengembalian alat yang sedang dipinjam untuk memulihkan stok laboratorium.</p>
    </div>
    <a href="index.php" class="btn btn-outline-secondary">
        <i class="bi bi-list-ul me-1"></i> Semua Transaksi
    </a>
</div>

<?php if ($flash_success): ?>
    <div class="alert alert-success py-2"><?= e($flash_success) ?></div>
<?php endif; ?>
<?php if ($flash_error): ?>
    <div class="alert alert-danger py-2"><?= e($flash_error) ?></div>
<?php endif; ?>

<!-- Tabel Peminjaman Aktif yang Belum Dikembalikan -->
<div class="card-custom p-3 mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h6 class="fw-bold mb-0">Menunggu Pengembalian</h6>
            <small class="text-muted">Daftar alat yang masih dibawa mahasiswa.</small>
        </div>
        <form method="GET" class="d-flex gap-2">
            <div class="input-group input-group-sm" style="width: 280px;">
                <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="q" class="form-control border-start-0" 
                       placeholder="Cari kode, NIM, nama, alat..." value="<?= e($search) ?>">
            </div>
            <button type="submit" class="btn btn-sm btn-outline-dark">Cari</button>
            <?php if ($search !== ''): ?>
                <a href="pengembalian.php" class="btn btn-sm btn-outline-secondary">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 14px;">
            <thead class="table-light">
                <tr>
                    <th>Kode</th>
                    <th>Peminjam</th>
                    <th>Alat & Jumlah</th>
                    <th>Jatuh Tempo</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($aktif_list)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            Tidak ada alat yang sedang dipinjam saat ini.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($aktif_list as $row): ?>
                        <?php $is_late = ($row['status'] === 'Terlambat'); ?>
                        <tr>
                            <td class="fw-semibold"><?= e($row['kode_peminjaman']) ?></td>
                            <td>
                                <div class="fw-semibold"><?= e($row['nama_mahasiswa']) ?></div>
                                <small class="text-muted">
                                    <?= e($row['nim']) ?>
                                    <?= !empty($row['no_hp']) ? ' &bull; ' . e($row['no_hp']) : '' ?>
                                </small>
                            </td>
                            <td>
                                <div class="fw-semibold">
                                    [<?= e($row['kode_alat']) ?>] <?= e($row['nama_alat']) ?>
                                    <span class="badge bg-light text-dark border"><?= e($row['jumlah']) ?> unit</span>
                                </div>
                                <?php if (!empty($row['keperluan'])): ?>
                                    <small class="text-muted"><?= e($row['keperluan']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="<?= $is_late ? 'text-danger fw-semibold' : '' ?>">
                                <?= e(date('d/m/Y', strtotime($row['tanggal_jatuh_tempo']))) ?>
                            </td>
                            <td>
                                <span class="badge <?= $is_late ? 'bg-danger' : 'bg-primary' ?>">
                                    <?= e($row['status']) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <form method="POST" class="d-inline" 
                                      onsubmit="return confirm('Konfirmasi pengembalian <?= e($row['nama_alat']) ?> (<?= e($row['jumlah']) ?> unit) dari <?= e($row['nama_mahasiswa']) ?>?')">
                                    <input type="hidden" name="peminjaman_id" value="<?= e($row['id']) ?>">
                                    <button type="submit" class="btn btn-sm btn-success">
                                        <i class="bi bi-check2-circle me-1"></i> Proses Kembali
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Tabel Riwayat Pengembalian Terbaru -->
<div class="card-custom p-3">
    <div class="mb-3">
        <h6 class="fw-bold mb-0">Riwayat Pengembalian Terbaru</h6>
        <small class="text-muted">8 transaksi pengembalian terakhir yang telah selesai.</small>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 14px;">
            <thead class="table-light">
                <tr>
                    <th>Kode</th>
                    <th>Peminjam</th>
                    <th>Alat & Jumlah</th>
                    <th>Tgl Pinjam</th>
                    <th>Tgl Kembali</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($riwayat_list)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Belum ada riwayat pengembalian.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($riwayat_list as $row): ?>
                        <tr>
                            <td class="fw-semibold"><?= e($row['kode_peminjaman']) ?></td>
                            <td>
                                <div class="fw-semibold"><?= e($row['nama_mahasiswa']) ?></div>
                                <small class="text-muted"><?= e($row['nim']) ?></small>
                            </td>
                            <td>
                                <?= e($row['nama_alat']) ?> 
                                <span class="badge bg-light text-dark border"><?= e($row['jumlah']) ?> unit</span>
                            </td>
                            <td><?= e(date('d/m/Y', strtotime($row['tanggal_pinjam']))) ?></td>
                            <td class="fw-semibold text-success">
                                <?= !empty($row['tanggal_kembali']) ? e(date('d/m/Y', strtotime($row['tanggal_kembali']))) : '-' ?>
                            </td>
                            <td><span class="badge bg-success">Dikembalikan</span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>