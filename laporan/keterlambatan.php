<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$page_title  = 'Laporan Keterlambatan';
$breadcrumb  = 'Ruang kerja / Laporan';
$active_menu = 'keterlambatan';

$search         = trim($_GET['q'] ?? '');
$terlambat_list = [];
$error          = '';

try {
    // Pastikan status peminjaman yang melewati jatuh tempo diperbarui otomatis
    $pdo->exec("UPDATE peminjaman 
                SET status = 'Terlambat' 
                WHERE status = 'Dipinjam' AND tanggal_jatuh_tempo < CURDATE()");

    $keyword = "%$search%";
    $sql = "SELECT p.id, p.kode_peminjaman, p.jumlah, p.tanggal_pinjam, p.tanggal_jatuh_tempo, p.keperluan,
                   DATEDIFF(CURDATE(), p.tanggal_jatuh_tempo) AS hari_terlambat,
                   m.nim, m.nama AS nama_mahasiswa, m.prodi, m.no_hp,
                   a.kode_alat, a.nama_alat
            FROM peminjaman p
            JOIN mahasiswa m ON p.mahasiswa_id = m.id
            JOIN alat a ON p.alat_id = a.id
            WHERE p.status = 'Terlambat'
              AND (p.kode_peminjaman LIKE :q1 OR m.nim LIKE :q2 OR m.nama LIKE :q3 OR a.nama_alat LIKE :q4)
            ORDER BY hari_terlambat DESC, p.tanggal_jatuh_tempo ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':q1' => $keyword,
        ':q2' => $keyword,
        ':q3' => $keyword,
        ':q4' => $keyword
    ]);
    $terlambat_list = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = "Gagal memuat laporan keterlambatan: " . $e->getMessage();
}

// Hitung ringkasan statistik keterlambatan
$total_kasus   = count($terlambat_list);
$total_unit    = array_sum(array_column($terlambat_list, 'jumlah'));
$maks_hari     = $total_kasus > 0 ? max(array_column($terlambat_list, 'hari_terlambat')) : 0;

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
        <h4 class="fw-bold mb-1">Laporan Keterlambatan</h4>
        <p class="text-muted mb-0" style="font-size: 14px;">Daftar peminjaman aktif yang telah melewati tanggal jatuh tempo pengembalian.</p>
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
            <div class="text-muted" style="font-size: 13px;">Total Transaksi Terlambat</div>
            <div class="fs-4 fw-bold text-danger mt-1"><?= $total_kasus ?> transaksi</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card-custom p-3">
            <div class="text-muted" style="font-size: 13px;">Total Unit Belum Kembali</div>
            <div class="fs-4 fw-bold mt-1"><?= $total_unit ?> unit</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card-custom p-3">
            <div class="text-muted" style="font-size: 13px;">Keterlambatan Terlama</div>
            <div class="fs-4 fw-bold mt-1"><?= $maks_hari ?> hari</div>
        </div>
    </div>
</div>

<div class="card-custom p-3">
    <!-- Bar Pencarian -->
    <form method="GET" class="row g-2 align-items-center mb-3 no-print">
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="q" class="form-control border-start-0" 
                       placeholder="Cari kode, NIM, nama mahasiswa, atau alat..." value="<?= e($search) ?>">
                <?php if ($search !== ''): ?>
                    <a href="keterlambatan.php" class="btn btn-outline-secondary">Reset</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-outline-dark">Cari</button>
        </div>
        <div class="col-md-5 text-md-end text-muted" style="font-size: 13px;">
            Tanggal Cetak: <strong><?= date('d/m/Y') ?></strong>
        </div>
    </form>

    <!-- Tabel Keterlambatan -->
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 14px;">
            <thead class="table-light">
                <tr>
                    <th>Kode</th>
                    <th>Peminjam & Kontak</th>
                    <th>Alat & Jumlah</th>
                    <th>Tgl Pinjam</th>
                    <th>Jatuh Tempo</th>
                    <th>Terlambat</th>
                    <th class="text-end no-print">Tindak Lanjut</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($terlambat_list)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            Tidak ada peminjaman yang terlambat saat ini.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($terlambat_list as $row): ?>
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
                                <div class="fw-semibold"><?= e($row['nama_alat']) ?></div>
                                <small class="text-muted"><?= e($row['kode_alat']) ?> (<?= e($row['jumlah']) ?> unit)</small>
                            </td>
                            <td><?= e(date('d/m/Y', strtotime($row['tanggal_pinjam']))) ?></td>
                            <td class="text-danger fw-semibold">
                                <?= e(date('d/m/Y', strtotime($row['tanggal_jatuh_tempo']))) ?>
                            </td>
                            <td>
                                <span class="badge bg-danger"><?= e($row['hari_terlambat']) ?> hari</span>
                            </td>
                            <td class="text-end no-print">
                                <a href="../peminjaman/kembali.php?id=<?= e($row['id']) ?>" 
                                   class="btn btn-sm btn-success"
                                   onclick="return confirm('Konfirmasi pengembalian alat <?= e($row['nama_alat']) ?> oleh <?= e($row['nama_mahasiswa']) ?>?')">
                                    Kembalikan
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>