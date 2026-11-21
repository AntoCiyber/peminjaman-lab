<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$page_title  = 'Data Mahasiswa';
$breadcrumb  = 'Ruang kerja / Master Data';
$active_menu = 'mahasiswa';

$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error   = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$search = trim($_GET['q'] ?? '');
$limit  = 8;
$page   = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$mahasiswa_list = [];
$total_data     = 0;
$total_pages    = 1;

try {
    $keyword = "%$search%";

    $sqlCount = "SELECT COUNT(*) FROM mahasiswa 
                 WHERE nim LIKE :q1 OR nama LIKE :q2 OR prodi LIKE :q3";
    $stmtCount = $pdo->prepare($sqlCount);
    $stmtCount->execute([
        ':q1' => $keyword,
        ':q2' => $keyword,
        ':q3' => $keyword
    ]);
    $total_data  = (int) $stmtCount->fetchColumn();
    $total_pages = max(1, (int) ceil($total_data / $limit));

    $sql = "SELECT id, nim, nama, prodi, no_hp 
            FROM mahasiswa
            WHERE nim LIKE :q1 OR nama LIKE :q2 OR prodi LIKE :q3
            ORDER BY nim ASC
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':q1', $keyword, PDO::PARAM_STR);
    $stmt->bindValue(':q2', $keyword, PDO::PARAM_STR);
    $stmt->bindValue(':q3', $keyword, PDO::PARAM_STR);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $mahasiswa_list = $stmt->fetchAll();

} catch (PDOException $e) {
    $flash_error = "Gagal memuat data mahasiswa: " . $e->getMessage();
}

require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Data Mahasiswa</h4>
        <p class="text-muted mb-0" style="font-size: 14px;">Kelola daftar mahasiswa peminjam alat laboratorium.</p>
    </div>
    <a href="form.php" class="btn btn-brown">
        <i class="bi bi-plus-lg me-1"></i> Tambah Mahasiswa
    </a>
</div>

<?php if ($flash_success): ?>
    <div class="alert alert-success py-2"><?= e($flash_success) ?></div>
<?php endif; ?>
<?php if ($flash_error): ?>
    <div class="alert alert-danger py-2"><?= e($flash_error) ?></div>
<?php endif; ?>

<div class="card-custom p-3">
    <!-- Bar Pencarian -->
    <form method="GET" class="row g-2 align-items-center mb-3">
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="q" class="form-control border-start-0" 
                       placeholder="Cari NIM, nama, atau prodi..." value="<?= e($search) ?>">
                <?php if ($search !== ''): ?>
                    <a href="index.php" class="btn btn-outline-secondary">Reset</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-outline-dark">Cari</button>
        </div>
        <div class="col-md-5 text-md-end text-muted" style="font-size: 13px;">
            Total: <strong><?= $total_data ?> mahasiswa</strong>
        </div>
    </form>

    <!-- Tabel Data Mahasiswa -->
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 14px;">
            <thead class="table-light">
                <tr>
                    <th>NIM</th>
                    <th>Nama Mahasiswa</th>
                    <th>Program Studi</th>
                    <th>No. HP</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($mahasiswa_list)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">Data mahasiswa tidak ditemukan.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($mahasiswa_list as $row): ?>
                        <tr>
                            <td class="fw-semibold"><?= e($row['nim']) ?></td>
                            <td class="fw-semibold"><?= e($row['nama']) ?></td>
                            <td><?= e($row['prodi'] ?: '-') ?></td>
                            <td class="text-muted"><?= e($row['no_hp'] ?: '-') ?></td>
                            <td class="text-end">
                                <a href="form.php?id=<?= e($row['id']) ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                                <a href="hapus.php?id=<?= e($row['id']) ?>" 
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Yakin ingin menghapus data mahasiswa <?= e($row['nama']) ?>?')">Hapus</a>
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
                        <a class="page-link" href="?page=<?= $i ?>&q=<?= urlencode($search) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
</div>

<div class="text-muted mt-3" style="font-size: 12px;">
    <i class="bi bi-info-circle me-1"></i> Mahasiswa yang masih tercatat dalam transaksi peminjaman tidak dapat dihapus.
</div>

<?php require_once '../includes/footer.php'; ?>