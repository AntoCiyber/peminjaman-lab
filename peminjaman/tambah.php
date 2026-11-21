<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$page_title  = 'Catat Peminjaman';
$breadcrumb  = 'Ruang kerja / Sirkulasi / Transaksi Peminjaman';
$active_menu = 'peminjaman';

$mahasiswa_id        = 0;
$alat_id             = 0;
$jumlah              = 1;
$tanggal_pinjam      = date('Y-m-d');
$tanggal_jatuh_tempo = date('Y-m-d', strtotime('+3 days'));
$keperluan           = '';
$error               = '';

$mahasiswa_list = [];
$alat_list      = [];

try {
    $mahasiswa_list = $pdo->query("SELECT id, nim, nama, prodi FROM mahasiswa ORDER BY nama ASC")->fetchAll();
    // Hanya tampilkan alat yang kondisinya Baik dan stok tersedia > 0
    $alat_list      = $pdo->query("SELECT id, kode_alat, nama_alat, stok_tersedia 
                                   FROM alat 
                                   WHERE kondisi = 'Baik' AND stok_tersedia > 0 
                                   ORDER BY nama_alat ASC")->fetchAll();
} catch (PDOException $e) {
    $error = "Gagal memuat data referensi: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mahasiswa_id        = (int)($_POST['mahasiswa_id'] ?? 0);
    $alat_id             = (int)($_POST['alat_id'] ?? 0);
    $jumlah              = (int)($_POST['jumlah'] ?? 0);
    $tanggal_pinjam      = $_POST['tanggal_pinjam'] ?? '';
    $tanggal_jatuh_tempo = $_POST['tanggal_jatuh_tempo'] ?? '';
    $keperluan           = trim($_POST['keperluan'] ?? '');
    $admin_id            = (int)($_SESSION['admin_id'] ?? 0);

    if ($mahasiswa_id <= 0 || $alat_id <= 0 || $jumlah <= 0 || $tanggal_pinjam === '' || $tanggal_jatuh_tempo === '') {
        $error = "Semua kolom wajib diisi dan jumlah minimal 1 unit!";
    } elseif ($tanggal_jatuh_tempo < $tanggal_pinjam) {
        $error = "Tanggal jatuh tempo tidak boleh lebih awal dari tanggal pinjam!";
    } else {
        try {
            // Mulai PDO Transaction agar simpan transaksi & potong stok bersifat atomik
            $pdo->beginTransaction();

            // Kunci baris alat untuk memastikan stok tersedia valid
            $stmtAlat = $pdo->prepare("SELECT nama_alat, stok_tersedia, kondisi FROM alat WHERE id = :id FOR UPDATE");
            $stmtAlat->execute(['id' => $alat_id]);
            $alat = $stmtAlat->fetch();

            if (!$alat) {
                throw new Exception("Data alat tidak ditemukan.");
            }
            if ($alat['kondisi'] !== 'Baik') {
                throw new Exception("Alat sedang dalam kondisi " . $alat['kondisi'] . " dan tidak dapat dipinjam.");
            }
            if ((int)$alat['stok_tersedia'] < $jumlah) {
                throw new Exception("Stok tidak mencukupi! Sisa tersedia untuk " . $alat['nama_alat'] . " hanya " . $alat['stok_tersedia'] . " unit.");
            }

            // Generate kode peminjaman otomatis: PJM-YYYYMMDD-001
            $prefix   = 'PJM-' . date('Ymd') . '-';
            $stmtLast = $pdo->prepare("SELECT kode_peminjaman FROM peminjaman WHERE kode_peminjaman LIKE :prefix ORDER BY id DESC LIMIT 1");
            $stmtLast->execute(['prefix' => $prefix . '%']);
            $lastKode = $stmtLast->fetchColumn();
            $urut     = $lastKode ? ((int)substr($lastKode, -3) + 1) : 1;
            $kode_peminjaman = $prefix . str_pad($urut, 3, '0', STR_PAD_LEFT);

            // Simpan record peminjaman
            $sqlInsert = "INSERT INTO peminjaman 
                          (kode_peminjaman, mahasiswa_id, alat_id, admin_id, jumlah, tanggal_pinjam, tanggal_jatuh_tempo, keperluan, status) 
                          VALUES 
                          (:kode, :mhs_id, :alat_id, :admin_id, :jumlah, :tgl_pinjam, :tgl_tempo, :keperluan, 'Dipinjam')";
            $stmtInsert = $pdo->prepare($sqlInsert);
            $stmtInsert->execute([
                'kode'      => $kode_peminjaman,
                'mhs_id'    => $mahasiswa_id,
                'alat_id'   => $alat_id,
                'admin_id'  => $admin_id > 0 ? $admin_id : null,
                'jumlah'    => $jumlah,
                'tgl_pinjam'=> $tanggal_pinjam,
                'tgl_tempo' => $tanggal_jatuh_tempo,
                'keperluan' => $keperluan !== '' ? $keperluan : null
            ]);

            // Kurangi stok tersedia pada tabel alat
            $stmtUpdateStok = $pdo->prepare("UPDATE alat SET stok_tersedia = stok_tersedia - :jumlah WHERE id = :alat_id");
            $stmtUpdateStok->execute([
                'jumlah'  => $jumlah,
                'alat_id' => $alat_id
            ]);

            $pdo->commit();

            $_SESSION['flash_success'] = "Peminjaman $kode_peminjaman berhasil dicatat dan stok alat telah diperbarui.";
            header("Location: index.php");
            exit;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $e->getMessage();
        }
    }
}

require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Catat Peminjaman Baru</h4>
        <p class="text-muted mb-0" style="font-size: 14px;">Isi formulir transaksi peminjaman alat laboratorium oleh mahasiswa.</p>
    </div>
    <a href="index.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger py-2"><?= e($error) ?></div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card-custom p-4">
            <h6 class="fw-bold mb-1">Detail Transaksi</h6>
            <p class="text-muted mb-3" style="font-size: 12px;">Kolom bertanda <span class="text-danger">*</span> wajib diisi.</p>

            <form method="POST">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold" style="font-size: 13px;">Mahasiswa Peminjam <span class="text-danger">*</span></label>
                        <select name="mahasiswa_id" class="form-select" required>
                            <option value="">-- Pilih Mahasiswa --</option>
                            <?php foreach ($mahasiswa_list as $mhs): ?>
                                <option value="<?= e($mhs['id']) ?>" <?= $mahasiswa_id === (int)$mhs['id'] ? 'selected' : '' ?>>
                                    <?= e($mhs['nim']) ?> - <?= e($mhs['nama']) ?> <?= !empty($mhs['prodi']) ? '(' . e($mhs['prodi']) . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label fw-semibold" style="font-size: 13px;">Alat Laboratorium <span class="text-danger">*</span></label>
                        <select name="alat_id" class="form-select" required>
                            <option value="">-- Pilih Alat Tersedia --</option>
                            <?php foreach ($alat_list as $al): ?>
                                <option value="<?= e($al['id']) ?>" <?= $alat_id === (int)$al['id'] ? 'selected' : '' ?>>
                                    [<?= e($al['kode_alat']) ?>] <?= e($al['nama_alat']) ?> — Tersedia: <?= e($al['stok_tersedia']) ?> unit
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size: 13px;">Jumlah Unit <span class="text-danger">*</span></label>
                        <input type="number" name="jumlah" class="form-control" min="1" value="<?= e($jumlah) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size: 13px;">Tanggal Pinjam <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_pinjam" class="form-control" value="<?= e($tanggal_pinjam) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size: 13px;">Tanggal Jatuh Tempo <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_jatuh_tempo" class="form-control" value="<?= e($tanggal_jatuh_tempo) ?>" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold" style="font-size: 13px;">Keperluan / Mata Kuliah</label>
                        <textarea name="keperluan" class="form-control" rows="2" 
                                  placeholder="Contoh: Praktikum Sistem Digital / Tugas Akhir"><?= e($keperluan) ?></textarea>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4 pt-2 border-top">
                    <button type="submit" class="btn btn-brown px-4">Simpan Transaksi</button>
                    <a href="index.php" class="btn btn-outline-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card-custom p-4">
            <h6 class="fw-bold mb-3">Ketentuan Peminjaman</h6>
            <div class="mb-3" style="font-size: 13px;">
                <strong>01 Kondisi alat layak</strong>
                <div class="text-muted">Hanya alat berstatus Baik dengan stok tersedia yang muncul di daftar.</div>
            </div>
            <div class="mb-3" style="font-size: 13px;">
                <strong>02 Pengurangan stok otomatis</strong>
                <div class="text-muted">Stok tersedia langsung berkurang begitu transaksi disimpan.</div>
            </div>
            <div style="font-size: 13px;">
                <strong>03 Batas waktu pengembalian</strong>
                <div class="text-muted">Status otomatis berubah menjadi Terlambat jika melewati jatuh tempo.</div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>