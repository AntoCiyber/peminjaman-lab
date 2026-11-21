<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$page_title  = 'Laporan Alat Terpopuler';
$breadcrumb  = 'Ruang kerja / Laporan';
$active_menu = 'populer';

$periode         = $_GET['periode'] ?? 'semua';
$kategori_filter = (int)($_GET['kategori'] ?? 0);

$kategori_options = [];
$populer_list     = [];
$error            = '';

try {
    $kategori_options = $pdo->query("SELECT id, nama_kategori FROM kategori_alat ORDER BY nama_kategori ASC")->fetchAll();

    // Kondisi periode diletakkan pada LEFT JOIN agar alat dengan 0 peminjaman tetap terdata
    $joinCondition = "a.id = p.alat_id";
    if ($periode === 'bulan_ini') {
        $joinCondition .= " AND MONTH(p.tanggal_pinjam) = MONTH(CURDATE()) AND YEAR(p.tanggal_pinjam) = YEAR(CURDATE())";
    } elseif ($periode === 'tahun_ini') {
        $joinCondition .= " AND YEAR(p.tanggal_pinjam) = YEAR(CURDATE())";
    } else {
        $periode = 'semua';
    }

    $whereSql = "1=1";
    $params   = [];
    if ($kategori_filter > 0) {
        $whereSql .= " AND a.kategori_id = :kategori_id";
        $params[':kategori_id'] = $kategori_filter;
    }

    $sql = "SELECT a.id, a.kode_alat, a.nama_alat, a.stok_tersedia, a.stok_total, a.kondisi,
                   k.nama_kategori,
                   COUNT(p.id) AS total_transaksi,
                   COALESCE(SUM(p.jumlah), 0) AS total_unit_dipinjam
            FROM alat a
            JOIN kategori_alat k ON a.kategori_id = k.id
            LEFT JOIN peminjaman p ON $joinCondition
            WHERE $whereSql
            GROUP BY a.id, a.kode_alat, a.nama_alat, a.stok_tersedia, a.stok_total, a.kondisi, k.nama_kategori
            ORDER BY total_transaksi DESC, total_unit_dipinjam DESC, a.nama_alat ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $populer_list = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = "Gagal memuat laporan alat terpopuler: " . $e->getMessage();
}

$total_frekuensi = array_sum(array_column($populer_list, 'total_transaksi'));
$total_unit_all  = array_sum(array_column($populer_list, 'total_unit_dipinjam'));
$alat_teratas    = (!empty($populer_list) && $populer_list[0]['total_transaksi'] > 0)
                   ? $populer_list[0]['nama_alat']
                   : '-';

require_once '../includes/header.php';
?>

<style>
@media print {
    .sidebar, .topbar, .no-print { display: none !important; }
    .main-content { margin-left: 0 !important; padding: 0 !important; width: 100% !important; }
    .card-custom { border: 1px solid #ddd !important; box-shadow: none !important; }
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Laporan Alat Terpopuler</h4>
        <p class="text-muted mb-0" style="font-size: 14px;">Peringkat frekuensi peminjaman alat laboratorium untuk evaluasi kebutuhan inventaris.</p>
    </div>
    <button type="button" onclick="window.print()" class="btn btn-brown no-print">
        <i class="bi bi-printer me-1"></i> Cetak Laporan
    </button>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger py-2"><?= e($error) ?></div>
<?php endif; ?>

<!-- Ringkasan Statistik -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card-custom p-3">
            <div class="text-muted" style="font-size: 13px;">Alat Paling Sering Dipinjam</div>
            <div class="fs-5 fw-bold mt-1 text-truncate"><?= e($alat_teratas) ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card-custom p-3">
            <div class="text-muted" style="font-size: 13px;">Total Frekuensi Peminjaman</div>
            <div class="fs-4 fw-bold mt-1"><?= $total_frekuensi ?> kali</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card-custom p-3">
            <div class="text-muted" style="font-size: 13px;">Total Akumulasi Unit Dipinjam</div>
            <div class="fs-4 fw-bold mt-1"><?= $total_unit_all ?> unit</div>
        </div>
    </div>
</div>

<div class="card-custom p-3">
    <!-- Filter Periode & Kategori -->
    <form method="GET" class="row g-2 align-items-center mb-3 no-print">
        <div class="col-md-3">
            <select name="periode" class="form-select">
                <option value="semua" <?= $periode === 'semua' ? 'selected' : '' ?>>Semua Waktu</option>
                <option value="bulan_ini" <?= $periode === 'bulan_ini' ? 'selected' : '' ?>>Bulan Ini (<?= date('M Y') ?>)</option>
                <option value="tahun_ini" <?= $periode === 'tahun_ini' ? 'selected' : '' ?>>Tahun Ini (<?= date('Y') ?>)</option>
            </select>
        </div>
        <div class="col-md-3">
            <select name="kategori" class="form-select">
                <option value="0">Semua Kategori</option>
                <?php foreach ($kategori_options as $kat): ?>
                    <option value="<?= e($kat['id']) ?>" <?= $kategori_filter === (int)$kat['id'] ? 'selected' : '' ?>>
                        <?= e($kat['nama_kategori']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-1">
            <button type="submit" class="btn btn-outline-dark">Terapkan</button>
            <?php if ($periode !== 'semua' || $kategori_filter > 0): ?>
                <a href="populer.php" class="btn btn-outline-secondary">Reset</a>
            <?php endif; ?>
        </div>
        <div class="col-md-3 text-md-end text-muted" style="font-size: 13px;">
            Menampilkan <strong><?= count($populer_list) ?> jenis alat</strong>
        </div>
    </form>

    <!-- Tabel Peringkat Alat -->
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 14px;">
            <thead class="table-light">
                <tr>
                    <th style="width: 70px;">Peringkat</th>
                    <th>Kode</th>
                    <th>Nama Alat</th>
                    <th>Kategori</th>
                    <th>Frekuensi Pinjam</th>
                    <th>Total Unit Dipinjam</th>
                    <th>Stok Saat Ini</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($populer_list)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">Data alat tidak ditemukan.</td>
                    </tr>
                <?php else: ?>
                    <?php $rank = 1; ?>
                    <?php foreach ($populer_list as $row): ?>
                        <tr>
                            <td>
                                <?php if ($rank === 1 && $row['total_transaksi'] > 0): ?>
                                    <span class="badge bg-warning text-dark">#1 Teratas</span>
                                <?php else: ?>
                                    <span class="fw-semibold text-muted">#<?= $rank ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="fw-semibold"><?= e($row['kode_alat']) ?></td>
                            <td class="fw-semibold"><?= e($row['nama_alat']) ?></td>
                            <td><?= e($row['nama_kategori']) ?></td>
                            <td>
                                <span class="badge bg-primary"><?= e($row['total_transaksi']) ?>x dipinjam</span>
                            </td>
                            <td class="fw-semibold"><?= e($row['total_unit_dipinjam']) ?> unit</td>
                            <td><?= e($row['stok_tersedia']) ?>/<?= e($row['stok_total']) ?> unit</td>
                        </tr>
                        <?php $rank++; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>