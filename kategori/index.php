<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$page_title  = 'Kategori Alat';
$breadcrumb  = 'Ruang kerja / Master Data';
$active_menu = 'kategori';

// Ambil flash message jika ada
$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error   = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Pengaturan Pencarian & Pagination
$search = trim($_GET['q'] ?? '');
$limit  = 5;
$page   = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$kategori_list = [];
$total_data    = 0;
$total_pages   = 1;

try {
    // Hitung total data untuk pagination
    $sqlCount = "SELECT COUNT(*) FROM kategori_alat 
                 WHERE kode_kategori LIKE :q1 OR nama_kategori LIKE :q2 OR deskripsi LIKE :q3";
    $stmtCount = $pdo->prepare($sqlCount);
    $keyword = "%$search%";
    $stmtCount->execute([
        'q1' => $keyword,
        'q2' => $keyword,
        'q3' => $keyword
    ]);
    $total_data  = (int) $stmtCount->fetchColumn();
    $total_pages = max(1, (int) ceil($total_data / $limit));

    // Ambil data kategori + hitung jumlah alat (LEFT JOIN + COUNT)
    $sql = "SELECT k.id, k.kode_kategori, k.nama_kategori, k.deskripsi, 
                   COUNT(a.id) AS jumlah_alat
            FROM kategori_alat k
            LEFT JOIN alat a ON k.id = a.kategori_id
            WHERE k.kode_kategori LIKE :q1 OR k.nama_kategori LIKE :q2 OR k.deskripsi LIKE :q3
            GROUP BY k.id, k.kode_kategori, k.nama_kategori, k.deskripsi
            ORDER BY k.kode_kategori ASC
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':q1', $keyword, PDO::PARAM_STR);
    $stmt->bindValue(':q2', $keyword, PDO::PARAM_STR);
    $stmt->bindValue(':q3', $keyword, PDO::PARAM_STR);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $kategori_list = $stmt->fetchAll();

} catch (PDOException $e) {
    $flash_error = "Gagal memuat data kategori: " . $e->getMessage();
}

require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Kategori Alat</h4>
        <p class="text-muted mb-0" style="font-size: 14px;">Atur pengelompokan alat agar inventaris lebih mudah ditemukan.</p>
    </div>
    <a href="form.php" class="btn btn-brown">
        <i class="bi bi-plus-lg me-1"></i> Tambah Kategori
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
                       placeholder="Cari kategori alat..." value="<?= e($search) ?>">
                <?php if ($search !== ''): ?>
                    <a href="index.php" class="btn btn-outline-secondary">Reset</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-outline-dark">Cari</button>
        </div>
        <div class="col-md-5 text-md-end text-muted" style="font-size: 13px;">
            Total: <strong><?= $total_data ?> kategori</strong>
        </div>
    </form>

    <!-- Tabel Kategori -->
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 14px;">
            <thead class="table-light">
                <tr>
                    <th>Kode</th>
                    <th>Nama Kategori</th>
                    <th>Deskripsi</th>
                    <th>Jenis Alat</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($kategori_list)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">Data kategori tidak ditemukan.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($kategori_list as $row): ?>
                        <tr>
                            <td class="fw-semibold"><?= e($row['kode_kategori']) ?></td>
                            <td class="fw-semibold"><?= e($row['nama_kategori']) ?></td>
                            <td class="text-muted"><?= e($row['deskripsi'] ?: '-') ?></td>
                            <td><span class="badge bg-light text-dark border"><?= e($row['jumlah_alat']) ?> jenis</span></td>
                            <td class="text-end">
                                <a href="form.php?id=<?= e($row['id']) ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                                <a href="hapus.php?id=<?= e($row['id']) ?>" 
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Yakin ingin menghapus kategori <?= e($row['nama_kategori']) ?>?')">Hapus</a>
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
    <i class="bi bi-info-circle me-1"></i> Kategori yang masih digunakan oleh alat tidak dapat dihapus. Pindahkan alat ke kategori lain terlebih dahulu.
</div>

<?php require_once '../includes/footer.php'; ?>