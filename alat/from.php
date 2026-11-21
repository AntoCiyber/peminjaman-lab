<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$id      = (int)($_GET['id'] ?? 0);
$is_edit = $id > 0;

$page_title  = $is_edit ? 'Edit Alat' : 'Tambah Alat';
$breadcrumb  = 'Ruang kerja / Master Data / Data Alat';
$active_menu = 'alat';

$kode_alat          = '';
$nama_alat          = '';
$kategori_id        = 0;
$stok_total         = 1;
$lokasi_penyimpanan = '';
$kondisi            = 'Baik';
$error              = '';
$kategori_list      = [];

try {
    $kategori_list = $pdo->query("SELECT id, nama_kategori FROM kategori_alat ORDER BY nama_kategori ASC")->fetchAll();

    if ($is_edit) {
        $stmt = $pdo->prepare("SELECT * FROM alat WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch();

        if (!$data) {
            $_SESSION['flash_error'] = "Data alat tidak ditemukan.";
            header("Location: index.php");
            exit;
        }

        $kode_alat          = $data['kode_alat'];
        $nama_alat          = $data['nama_alat'];
        $kategori_id        = (int)$data['kategori_id'];
        $stok_total         = (int)$data['stok_total'];
        $lokasi_penyimpanan = $data['lokasi_penyimpanan'] ?? '';
        $kondisi            = $data['kondisi'];
    }
} catch (PDOException $e) {
    $error = "Gagal memuat data: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_alat          = strtoupper(trim($_POST['kode_alat'] ?? ''));
    $nama_alat          = trim($_POST['nama_alat'] ?? '');
    $kategori_id        = (int)($_POST['kategori_id'] ?? 0);
    $stok_total         = (int)($_POST['stok_total'] ?? 0);
    $lokasi_penyimpanan = trim($_POST['lokasi_penyimpanan'] ?? '');
    $kondisi            = $_POST['kondisi'] ?? 'Baik';

    $valid_kondisi = ['Baik', 'Perawatan', 'Rusak'];

    if ($kode_alat === '' || $nama_alat === '' || $kategori_id <= 0) {
        $error = "Kode alat, nama alat, dan kategori wajib diisi!";
    } elseif ($stok_total < 0) {
        $error = "Jumlah stok total tidak boleh kurang dari 0!";
    } elseif (!in_array($kondisi, $valid_kondisi, true)) {
        $error = "Pilihan kondisi alat tidak valid!";
    } else {
        try {
            if ($is_edit) {
                // Hitung unit yang sedang dipinjam agar stok tersedia tetap konsisten
                $stmtDipinjam = $pdo->prepare("SELECT COALESCE(SUM(jumlah), 0) FROM peminjaman WHERE alat_id = :id AND status IN ('Dipinjam', 'Terlambat')");
                $stmtDipinjam->execute(['id' => $id]);
                $sedang_dipinjam = (int) $stmtDipinjam->fetchColumn();

                if ($stok_total < $sedang_dipinjam) {
                    $error = "Stok total tidak boleh lebih kecil dari jumlah yang sedang dipinjam ($sedang_dipinjam unit)!";
                } else {
                    $stok_tersedia = $stok_total - $sedang_dipinjam;
                    $sql = "UPDATE alat 
                            SET kode_alat = :kode, nama_alat = :nama, kategori_id = :kat, 
                                stok_total = :total, stok_tersedia = :tersedia, 
                                lokasi_penyimpanan = :lokasi, kondisi = :kondisi 
                            WHERE id = :id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        'kode'     => $kode_alat,
                        'nama'     => $nama_alat,
                        'kat'      => $kategori_id,
                        'total'    => $stok_total,
                        'tersedia' => $stok_tersedia,
                        'lokasi'   => $lokasi_penyimpanan,
                        'kondisi'  => $kondisi,
                        'id'       => $id
                    ]);
                    $_SESSION['flash_success'] = "Data alat berhasil diperbarui.";
                    header("Location: index.php");
                    exit;
                }
            } else {
                $sql = "INSERT INTO alat (kode_alat, nama_alat, kategori_id, stok_total, stok_tersedia, lokasi_penyimpanan, kondisi) 
                        VALUES (:kode, :nama, :kat, :total, :tersedia, :lokasi, :kondisi)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'kode'     => $kode_alat,
                    'nama'     => $nama_alat,
                    'kat'      => $kategori_id,
                    'total'    => $stok_total,
                    'tersedia' => $stok_total,
                    'lokasi'   => $lokasi_penyimpanan,
                    'kondisi'  => $kondisi
                ]);
                $_SESSION['flash_success'] = "Alat baru berhasil ditambahkan.";
                header("Location: index.php");
                exit;
            }
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                $error = "Kode alat sudah digunakan oleh alat lain!";
            } else {
                $error = "Gagal menyimpan data alat: " . $e->getMessage();
            }
        }
    }
}

require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><?= e($page_title) ?></h4>
        <p class="text-muted mb-0" style="font-size: 14px;">Lengkapi informasi alat untuk menambahkan atau memperbarui inventaris.</p>
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
            <h6 class="fw-bold mb-1">Informasi Alat</h6>
            <p class="text-muted mb-3" style="font-size: 12px;">Kolom bertanda <span class="text-danger">*</span> wajib diisi.</p>

            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size: 13px;">Kode Alat <span class="text-danger">*</span></label>
                        <input type="text" name="kode_alat" class="form-control" 
                               placeholder="Contoh: BIO-001" value="<?= e($kode_alat) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size: 13px;">Kategori Alat <span class="text-danger">*</span></label>
                        <select name="kategori_id" class="form-select" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php foreach ($kategori_list as $kat): ?>
                                <option value="<?= e($kat['id']) ?>" <?= $kategori_id === (int)$kat['id'] ? 'selected' : '' ?>>
                                    <?= e($kat['nama_kategori']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold" style="font-size: 13px;">Nama Alat <span class="text-danger">*</span></label>
                        <input type="text" name="nama_alat" class="form-control" 
                               placeholder="Contoh: Mikroskop Binokuler" value="<?= e($nama_alat) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size: 13px;">Jumlah Stok Total <span class="text-danger">*</span></label>
                        <input type="number" name="stok_total" class="form-control" min="0" 
                               value="<?= e($stok_total) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size: 13px;">Kondisi Alat <span class="text-danger">*</span></label>
                        <select name="kondisi" class="form-select" required>
                            <option value="Baik" <?= $kondisi === 'Baik' ? 'selected' : '' ?>>Baik</option>
                            <option value="Perawatan" <?= $kondisi === 'Perawatan' ? 'selected' : '' ?>>Perawatan</option>
                            <option value="Rusak" <?= $kondisi === 'Rusak' ? 'selected' : '' ?>>Rusak</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold" style="font-size: 13px;">Lokasi Penyimpanan</label>
                        <input type="text" name="lokasi_penyimpanan" class="form-control" 
                               placeholder="Contoh: Ruang Biologi Lemari A-02" value="<?= e($lokasi_penyimpanan) ?>">
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4 pt-2 border-top">
                    <button type="submit" class="btn btn-brown px-4">Simpan Alat</button>
                    <a href="index.php" class="btn btn-outline-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card-custom p-4">
            <h6 class="fw-bold mb-3">Panduan Pengisian</h6>
            <div class="mb-3" style="font-size: 13px;">
                <strong>01 Kode alat unik</strong>
                <div class="text-muted">Contoh: BIO-001 untuk alat biologi.</div>
            </div>
            <div class="mb-3" style="font-size: 13px;">
                <strong>02 Catat kondisi terkini</strong>
                <div class="text-muted">Pilih Baik, Perawatan, atau Rusak.</div>
            </div>
            <div style="font-size: 13px;">
                <strong>03 Periksa jumlah unit</strong>
                <div class="text-muted">Stok tersedia otomatis menyesuaikan transaksi aktif.</div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>