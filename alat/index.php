<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$page_title  = 'Data Alat';
$breadcrumb  = 'Ruang kerja / Master Data';
$active_menu = 'alat';

$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error   = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Filter, Pencarian & Pagination
$search          = trim($_GET['q'] ?? '');
$kategori_filter = (int)($_GET['kategori'] ?? 0);
$limit           = 8;
$page            = max(1, (int)($_GET['page'] ?? 1));
$offset          = ($page - 1) * $limit;

$kategori_options = [];
$alat_list        = [];
$total_data       = 0;
$total_pages      = 1;

try {
    // Ambil daftar kategori untuk dropdown filter
    $kategori_options = $pdo->query("SELECT id, nama_kategori FROM kategori_alat ORDER BY nama_kategori ASC")->fetchAll();

    // Susun kondisi pencarian & filter secara dinamis
    $where  = ["(a.kode_alat LIKE :q1 OR a.nama_alat LIKE :q2)"];
    $params = [
        ':q1' => "%$search%",
        ':q2' => "%$search%"
    ];

    if ($kategori_filter > 0) {
        $where[] = "a.kategori_id = :kategori_id";
        $params[':kategori_id'] = $kategori_filter;
    }

    $whereSql = implode(' AND ', $where);

    // Hitung total data untuk pagination
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM alat a WHERE $whereSql");
    $stmtCount->execute($params);
    $total_data  = (int) $stmtCount->fetchColumn();
    $total_pages = max(1, (int) ceil($total_data / $limit));

    // Ambil data alat beserta nama kategorinya (JOIN)
    $sql = "SELECT a.*, k.nama_kategori 
            FROM alat a
            JOIN kategori_alat k ON a.kategori_id = k.id
            WHERE $whereSql
            ORDER BY a.kode_alat ASC
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $alat_list = $stmt->fetchAll();

} catch (PDOException $e) {
    $flash_error = "Gagal memuat data alat: " . $e->getMessage();
}

require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Data Alat</h4>
        <p class="text-muted mb-0" style="font-size: 14px;">Kelola inventaris, ketersediaan, dan kondisi alat laboratorium.</p>
    </div>
    <a href="form.php" class="btn btn-brown">
        <i class="bi bi-plus-lg me-1"></i> Tambah Alat
    </a>
</div>

<?php if ($flash_success): ?>
    <div class="alert alert-success py-2"><?= e($flash_success) ?></div>
<?php endif; ?>
<?php if ($flash_error): ?>
    <div class="alert alert-danger py-2"><?= e($flash_error) ?></div>
<?php endif; ?>

<div class="card-custom p-3">
    <!-- Bar Pencarian & Filter Kategori -->
    <form method="GET" class="row g-2 align-items-center mb-3">
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="q" class="form-control border-start-0" 
                       placeholder="Cari kode atau nama alat..." value="<?= e($search) ?>">
            </div>
        </div>
        <div class="col-md-3">
            <select name="kategori" class="form-select">
                <option value="0">Semua kategori</option>
                <?php foreach ($kategori_options as $kat): ?>
                    <option value="<?= e($kat['id']) ?>" <?= $kategori_filter === (int)$kat['id'] ? 'selected' : '' ?>>
                        <?= e($kat['nama_kategori']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-outline-dark">Filter</button>
            <?php if ($search !== '' || $kategori_filter > 0): ?>
                <a href="index.php" class="btn btn-outline-secondary">Reset</a>
            <?php endif; ?>
        </div>
        <div class="col-md-2 text-md-end text-muted" style="font-size: 13px;">
            <strong><?= $total_data ?> jenis alat</strong>
        </div>
    </form>

    <!-- Tabel Data Alat -->
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 14px;">
            <thead class="table-light">
                <tr>
                    <th>Kode Alat</th>
                    <th>Nama Alat</th>
                    <th>Kategori</th>
                    <th>Tersedia / Total</th>
                    <th>Kondisi</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($alat_list)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Data alat tidak ditemukan.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($alat_list as $row): ?>
                        <?php
                            $badge = 'bg-success';
                            if ($row['kondisi'] === 'Perawatan') $badge = 'bg-warning text-dark';
                            elseif ($row['kondisi'] === 'Rusak') $badge = 'bg-danger';
                        ?>
                        <tr>
                            <td class="fw-semibold"><?= e($row['kode_alat']) ?></td>
                            <td>
                                <div class="fw-semibold"><?= e($row['nama_alat']) ?></div>
                                <?php if (!empty($row['lokasi_penyimpanan'])): ?>
                                    <small class="text-muted"><i class="bi bi-geo-alt me-1"></i><?= e($row['lokasi_penyimpanan']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= e($row['nama_kategori']) ?></td>
                            <td>
                                <span class="fw-semibold <?= $row['stok_tersedia'] == 0 ? 'text-danger' : '' ?>">
                                    <?= e($row['stok_tersedia']) ?>/<?= e($row['stok_total']) ?> unit
                                </span>
                            </td>
                            <td><span class="badge <?= $badge ?>"><?= e($row['kondisi']) ?></span></td>
                            <td class="text-end">
                                <a href="form.php?id=<?= e($row['id']) ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                                <a href="hapus.php?id=<?= e($row['id']) ?>" 
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Yakin ingin menghapus alat <?= e($row['nama_alat']) ?>?')">Hapus</a>
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
                        <a class="page-link" href="?page=<?= $i ?>&q=<?= urlencode($search) ?>&kategori=<?= $kategori_filter ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
</div>

<div class="text-muted mt-3" style="font-size: 12px;">
    <i class="bi bi-info-circle me-1"></i> Stok tersedia tidak termasuk alat yang sedang dipinjam.
</div>

<?php require_once '../includes/footer.php'; ?>