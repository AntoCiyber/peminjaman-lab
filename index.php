<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

$page_title  = 'Dashboard';
$breadcrumb  = 'Ruang kerja / Dashboard';
$active_menu = 'dashboard';

$error_db = '';
$stat_alat = ['jenis' => 0, 'total_unit' => 0, 'tersedia' => 0];
$stat_pinjam = ['transaksi' => 0, 'unit' => 0];
$total_mahasiswa = 0;
$total_terlambat = 0;
$unit_perawatan = 0;
$peminjaman_terbaru = [];

try {
    // 1. Statistik Total Alat & Tersedia (Agregasi)
    $stat_alat = $pdo->query("
        SELECT COUNT(*) AS jenis, 
               COALESCE(SUM(stok_total), 0) AS total_unit, 
               COALESCE(SUM(stok_tersedia), 0) AS tersedia 
        FROM alat
    ")->fetch();

    // 2. Unit dalam kondisi perawatan
    $unit_perawatan = (int) $pdo->query("
        SELECT COALESCE(SUM(stok_total), 0) FROM alat WHERE kondisi = 'Perawatan'
    ")->fetchColumn();

    // 3. Statistik Peminjaman Aktif
    $stat_pinjam = $pdo->query("
        SELECT COUNT(*) AS transaksi, 
               COALESCE(SUM(jumlah), 0) AS unit 
        FROM peminjaman 
        WHERE status IN ('Dipinjam', 'Terlambat')
    ")->fetch();

    // 4. Total Mahasiswa Terdaftar
    $total_mahasiswa = (int) $pdo->query("SELECT COUNT(*) FROM mahasiswa")->fetchColumn();

    // 5. Total Peminjaman Terlambat
    $total_terlambat = (int) $pdo->query("
        SELECT COUNT(*) FROM peminjaman 
        WHERE status = 'Terlambat' OR (status = 'Dipinjam' AND tanggal_jatuh_tempo < CURDATE())
    ")->fetchColumn();

    // 6. Tabel Peminjaman Terbaru (Query JOIN)
    $peminjaman_terbaru = $pdo->query("
        SELECT p.kode_peminjaman, m.nama AS nama_mahasiswa, a.nama_alat, 
               p.tanggal_jatuh_tempo, p.status
        FROM peminjaman p
        JOIN mahasiswa m ON p.mahasiswa_id = m.id
        JOIN alat a ON p.alat_id = a.id
        ORDER BY p.id DESC
        LIMIT 5
    ")->fetchAll();

} catch (PDOException $e) {
    $error_db = "Gagal memuat data dashboard: " . $e->getMessage();
}

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Selamat datang, <?= e($_SESSION['admin_nama']) ?></h4>
        <p class="text-muted mb-0" style="font-size: 14px;">Pantau ketersediaan alat dan aktivitas peminjaman hari ini.</p>
    </div>
    <a href="peminjaman/tambah.php" class="btn btn-brown">
        <i class="bi bi-plus-lg me-1"></i> Buat Peminjaman
    </a>
</div>

<?php if ($error_db): ?>
    <div class="alert alert-danger"><?= e($error_db) ?></div>
<?php endif; ?>

<!-- 4 Kartu Ringkasan -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card-custom p-3 h-100">
            <small class="text-muted fw-semibold">Total Alat</small>
            <h3 class="fw-bold my-1"><?= e($stat_alat['total_unit']) ?></h3>
            <small class="text-muted">unit dalam <?= e($stat_alat['jenis']) ?> jenis alat</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-custom p-3 h-100">
            <small class="text-muted fw-semibold">Sedang Dipinjam</small>
            <h3 class="fw-bold my-1"><?= e($stat_pinjam['unit']) ?></h3>
            <small class="text-muted">unit dalam <?= e($stat_pinjam['transaksi']) ?> peminjaman aktif</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-custom p-3 h-100">
            <small class="text-muted fw-semibold">Mahasiswa Terdaftar</small>
            <h3 class="fw-bold my-1"><?= e($total_mahasiswa) ?></h3>
            <small class="text-muted">mahasiswa aktif</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-custom p-3 h-100">
            <small class="text-muted fw-semibold">Peminjaman Terlambat</small>
            <h3 class="fw-bold my-1 text-danger"><?= e($total_terlambat) ?></h3>
            <small class="text-danger">perlu ditindaklanjuti</small>
        </div>
    </div>
</div>

<!-- Banner Peringatan Keterlambatan -->
<?php if ($total_terlambat > 0): ?>
<div class="alert alert-warning d-flex justify-content-between align-items-center border-warning mb-4" style="border-radius: 10px;">
    <div>
        <strong><i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($total_terlambat) ?> peminjaman melewati batas pengembalian</strong>
        <div style="font-size: 13px;">Hubungi peminjam untuk menjaga ketersediaan alat bagi praktikum berikutnya.</div>
    </div>
    <a href="laporan/keterlambatan.php" class="btn btn-sm btn-outline-dark fw-semibold">Lihat keterlambatan &rarr;</a>
</div>
<?php endif; ?>

<!-- Konten Bawah: Tabel Peminjaman Terbaru & Ketersediaan Alat -->
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card-custom p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="fw-bold mb-0">Peminjaman Terbaru</h6>
                    <small class="text-muted">Aktivitas terakhir di laboratorium Anda</small>
                </div>
                <a href="peminjaman/index.php" class="text-decoration-none fw-semibold" style="font-size: 13px; color: #3b2f1e;">
                    Lihat semua &rarr;
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 14px;">
                    <thead class="table-light">
                        <tr>
                            <th>ID Peminjaman</th>
                            <th>Mahasiswa</th>
                            <th>Alat</th>
                            <th>Batas Kembali</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($peminjaman_terbaru)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">Belum ada transaksi peminjaman.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($peminjaman_terbaru as $row): ?>
                                <?php
                                    $badge = 'bg-warning text-dark';
                                    if ($row['status'] === 'Dikembalikan') $badge = 'bg-success';
                                    elseif ($row['status'] === 'Terlambat') $badge = 'bg-danger';
                                ?>
                                <tr>
                                    <td class="fw-semibold"><?= e($row['kode_peminjaman']) ?></td>
                                    <td><?= e($row['nama_mahasiswa']) ?></td>
                                    <td><?= e($row['nama_alat']) ?></td>
                                    <td><?= e(date('d M Y', strtotime($row['tanggal_jatuh_tempo']))) ?></td>
                                    <td><span class="badge <?= $badge ?>"><?= e($row['status']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card-custom p-3 h-100 d-flex flex-column justify-content-between">
            <div>
                <h6 class="fw-bold mb-3">Ketersediaan Alat</h6>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted"><i class="bi bi-circle-fill text-success me-2" style="font-size: 10px;"></i>Tersedia</span>
                    <strong><?= e($stat_alat['tersedia']) ?> unit</strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted"><i class="bi bi-circle-fill text-warning me-2" style="font-size: 10px;"></i>Dipinjam</span>
                    <strong><?= e($stat_pinjam['unit']) ?> unit</strong>
                </div>
                <div class="d-flex justify-content-between py-2">
                    <span class="text-muted"><i class="bi bi-circle-fill text-secondary me-2" style="font-size: 10px;"></i>Perawatan</span>
                    <strong><?= e($unit_perawatan) ?> unit</strong>
                </div>
            </div>

            <div class="mt-4 pt-3 border-top">
                <small class="text-muted d-block mb-2">Mulai transaksi baru</small>
                <a href="peminjaman/tambah.php" class="btn btn-outline-dark w-100 fw-semibold">
                    + Buat peminjaman &rarr;
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>