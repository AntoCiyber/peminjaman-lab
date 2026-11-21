<?php
require_once __DIR__ . '/config/database.php';   // <-- SESUAIKAN
require_once __DIR__ . '/includes/auth.php';     // <-- SESUAIKAN
// require_login();                              // <-- salin pemanggilan cek login dari index.php

$page_title  = 'Data Alat';
$breadcrumb  = 'Master data / Data Alat';
$active_menu = 'alat';   // <-- samakan dengan kunci menu di header.php

$cari    = trim($_GET['q'] ?? '');
$halaman = max(1, (int)($_GET['hal'] ?? 1));
$perHal  = 5;
$offset  = ($halaman - 1) * $perHal;
$like    = "%$cari%";

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM alat a
        JOIN kategori_alat k ON k.id = a.kategori_id
        WHERE a.nama_alat LIKE :q1 OR a.kode_alat LIKE :q2 OR k.nama_kategori LIKE :q3");
    $stmt->execute([':q1' => $like, ':q2' => $like, ':q3' => $like]);
    $totalData = (int)$stmt->fetchColumn();
    $totalHal  = max(1, (int)ceil($totalData / $perHal));

    $stmt = $pdo->prepare("SELECT a.*, k.nama_kategori
        FROM alat a
        JOIN kategori_alat k ON k.id = a.kategori_id
        WHERE a.nama_alat LIKE :q1 OR a.kode_alat LIKE :q2 OR k.nama_kategori LIKE :q3
        ORDER BY a.id DESC
        LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':q1', $like);
    $stmt->bindValue(':q2', $like);
    $stmt->bindValue(':q3', $like);
    $stmt->bindValue(':limit', $perHal, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $daftarAlat = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $ex) {
    die('Terjadi kesalahan saat mengambil data.');
}

include __DIR__ . '/includes/header.php';
?>

<!-- Pakai pembungkus konten yang sama dengan index.php (cek class-nya, mis. main-content) -->
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Data Alat</h4>
  <a href="alat_tambah.php" class="btn btn-dark"><i class="bi bi-plus-lg"></i> Tambah Alat</a>
</div>

<form method="get" class="mb-3 d-flex gap-2">
  <input type="text" name="q" class="form-control" placeholder="Cari kode, nama, atau kategori..." value="<?= e($cari) ?>">
  <button class="btn btn-outline-dark">Cari</button>
</form>

<div class="table-responsive">
<table class="table table-hover align-middle">
  <thead>
    <tr><th>Kode</th><th>Nama Alat</th><th>Kategori</th><th>Stok</th><th>Kondisi</th><th>Aksi</th></tr>
  </thead>
  <tbody>
    <?php foreach ($daftarAlat as $a): ?>
    <tr>
      <td><?= e($a['kode_alat']) ?></td>
      <td><?= e($a['nama_alat']) ?></td>
      <td><?= e($a['nama_kategori']) ?></td>
      <td><?= (int)$a['stok_tersedia'] ?> / <?= (int)$a['stok_total'] ?></td>
      <td><?= e($a['kondisi']) ?></td>
      <td>
        <a href="alat_edit.php?id=<?= (int)$a['id'] ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
        <a href="alat_hapus.php?id=<?= (int)$a['id'] ?>" class="btn btn-sm btn-outline-danger"
           onclick="return confirm('Hapus alat ini?')">Hapus</a>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$daftarAlat): ?>
    <tr><td colspan="6" class="text-center text-muted">Data tidak ditemukan.</td></tr>
    <?php endif; ?>
  </tbody>
</table>
</div>

<nav>
  <ul class="pagination">
    <?php for ($i = 1; $i <= $totalHal; $i++): ?>
    <li class="page-item <?= $i === $halaman ? 'active' : '' ?>">
      <a class="page-link" href="?q=<?= urlencode($cari) ?>&hal=<?= $i ?>"><?= $i ?></a>
    </li>
    <?php endfor; ?>
  </ul>
</nav>

<?php include __DIR__ . '/includes/footer.php'; ?>