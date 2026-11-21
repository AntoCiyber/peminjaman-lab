<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$page_title  = 'Transaksi Peminjaman';
$breadcrumb  = 'Ruang kerja / Sirkulasi';
$active_menu = 'peminjaman';

$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error   = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$search        = trim($_GET['q'] ?? '');
$status_filter = trim($_GET['status'] ?? '');
$limit         = 8;
$page          = max(1, (int)($_GET['page'] ?? 1));
$offset        = ($page - 1) * $limit;

$transaksi_list = [];
$total_data     = 0;
$total_pages    = 1;

try {
    // Otomatis tandai peminjaman aktif yang melewati jatuh tempo menjadi 'Terlambat'
    $pdo->exec("UPDATE peminjaman 
                SET status = 'Terlambat' 
                WHERE status = 'Dipinjam' AND tanggal_jatuh_tempo < CURDATE()");

    $where  = ["(p.kode_peminjaman LIKE :q1 OR m.nim LIKE :q2 OR m.nama LIKE :q3 OR a.nama_alat LIKE :q4)"];
    $params = [
        ':q1' => "%$search%",
        ':q2' => "%$search%",
        ':q3' => "%$search%",
        ':q4' => "%$search%"
    ];

    if (in_array($status_filter, ['Dipinjam', 'Terlambat', 'Dikembalikan'], true)) {
        $where[] = "p.status = :status";
        $params[':status'] = $status_filter;
    }

    $whereSql = implode(' AND ', $where);

    $sqlCount = "SELECT COUNT(*) 
                 FROM peminjaman p
                 JOIN mahasiswa m ON p.mahasiswa_id = m.id
                 JOIN alat a ON p.alat_id = a.id
                 WHERE $whereSql";
    $stmtCount = $pdo->prepare($sqlCount);
    $stmtCount->execute($params);
    $total_data  = (int) $stmtCount->fetchColumn();
    $total_pages = max(1, (int) ceil($total_data / $limit));

    $sql = "SELECT p.*, 
                   m.nim, m.nama AS nama_mahasiswa,
                   a.kode_alat, a.nama_alat,
                   adm.nama_lengkap AS nama_admin
            FROM peminjaman p
            JOIN mahasiswa m ON p.mahasiswa_id = m.id
            JOIN alat a ON p.alat_id = a.id
            LEFT JOIN admin adm ON p.admin_id = adm.id
            WHERE $whereSql
            ORDER BY FIELD(p.status, 'Terlambat', 'Dipinjam', 'Dikembalikan'), p.id DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val, PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $transaksi_list = $stmt->fetchAll();

} catch (PDOException $e) {
    $flash_error = "Gagal memuat data transaksi: " . $e->getMessage();
}

require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Transaksi Peminjaman</h4>
        <p class="text-muted mb-0" style="font-size: 14px;">Catat peminjaman baru dan kelola pengembalian alat laboratorium.</p>
    </div>
    <a href="tambah.php" class="btn btn-brown">
        <i class="bi bi-plus-lg me-1"></i> Catat Peminjaman
    </a>
</div>

<?php if ($flash_success): ?>
    <div class="alert alert-success py-2"><?= e($flash_success) ?></div>
<?php endif; ?>
<?php if ($flash_error): ?>
    <div class="alert alert-danger py-2"><?= e($flash_error) ?></div>
<?php endif; ?>

<div class="card-custom p-3">
    <!-- Bar Pencarian & Filter Status -->
    <form method="GET" class="row g-2 align-items-center mb-3">
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="q" class="form-control border-start-0" 
                       placeholder="Cari kode, NIM, nama mahasiswa, atau alat..." value="<?= e($search) ?>">
            </div>
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">Semua status</option>
                <option value="Dipinjam" <?= $status_filter === 'Dipinjam' ? 'selected' : '' ?>>Dipinjam</option>
                <option value="Terlambat" <?= $status_filter === 'Terlambat' ? 'selected' : '' ?>>Terlambat</option>
                <option value="Dikembalikan" <?= $status_filter === 'Dikembalikan' ? 'selected' : '' ?>>Dikembalikan</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-outline-dark">Filter</button>
            <?php if ($search !== '' || $status_filter !== ''): ?>
                <a href="index.php" class="btn btn-outline-secondary">Reset</a>
            <?php endif; ?>
        </div>
        <div class="col-md-2 text-md-end text-muted" style="font-size: 13px;">
            <strong><?= $total_data ?> transaksi</strong>
        </div>
    </form>

    <!-- Tabel Transaksi -->
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 14px;">
            <thead class="table-light">
                <tr>
                    <th>Kode</th>
                    <th>Peminjam</th>
                    <th>Alat & Jumlah</th>
                    <th>Tgl Pinjam / Tempo</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transaksi_list)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Belum ada data transaksi peminjaman.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transaksi_list as $row): ?>
                        <?php
                            $badge = 'bg-primary';
                            if ($row['status'] === 'Terlambat') $badge = 'bg-danger';
                            elseif ($row['status'] === 'Dikembalikan') $badge = 'bg-success';
                        ?>
                        <tr>
                            <td class="fw-semibold"><?= e($row['kode_peminjaman']) ?></td>
                            <td>
                                <div class="fw-semibold"><?= e($row['nama_mahasiswa']) ?></div>
                                <small class="text-muted"><?= e($row['nim']) ?></small>
                            </td>
                            <td>
                                <div class="fw-semibold"><?= e($row['nama_alat']) ?> <span class="badge bg-light text-dark border"><?= e($row['jumlah']) ?> unit</span></div>
                                <?php if (!empty($row['keperluan'])): ?>
                                    <small class="text-muted"><?= e($row['keperluan']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td style="font-size: 13px;">
                                <div>Pinjam: <?= e(date('d/m/Y', strtotime($row['tanggal_pinjam']))) ?></div>
                                <div class="<?= $row['status'] === 'Terlambat' ? 'text-danger fw-semibold' : 'text-muted' ?>">
                                    Tempo: <?= e(date('d/m/Y', strtotime($row['tanggal_jatuh_tempo']))) ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?= $badge ?>"><?= e($row['status']) ?></span>
                                <?php if ($row['status'] === 'Dikembalikan' && !empty($row['tanggal_kembali'])): ?>
                                    <div class="text-muted" style="font-size: 11px;">
                                        Kembali: <?= e(date('d/m/Y', strtotime($row['tanggal_kembali']))) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if ($row['status'] !== 'Dikembalikan'): ?>
                                    <a href="kembali.php?id=<?= e($row['id']) ?>" 
                                       class="btn btn-sm btn-success"
                                       onclick="return confirm('Konfirmasi pengembalian alat <?= e($row['nama_alat']) ?> (<?= e($row['jumlah']) ?> unit) oleh <?= e($row['nama_mahasiswa']) ?>?')">
                                        <i class="bi bi-check2-circle me-1"></i> Kembalikan
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size: 12px;">Selesai</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top" style="font-size: 13px;">
        <span class="text-muted">
            Halaman <?= $page ?> dari <?= $total_pages ?> (<?= $total_data ?> data)
        </span>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&q=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>