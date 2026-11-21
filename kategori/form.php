<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$id      = (int)($_GET['id'] ?? 0);
$is_edit = $id > 0;

$page_title  = $is_edit ? 'Edit Kategori' : 'Tambah Kategori';
$breadcrumb  = 'Ruang kerja / Master Data / Kategori Alat';
$active_menu = 'kategori';

$kode_kategori = '';
$nama_kategori = '';
$deskripsi     = '';
$error         = '';

// Jika mode Edit, ambil data lama berdasarkan ID
if ($is_edit) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM kategori_alat WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch();

        if (!$data) {
            $_SESSION['flash_error'] = "Data kategori tidak ditemukan.";
            header("Location: index.php");
            exit;
        }

        $kode_kategori = $data['kode_kategori'];
        $nama_kategori = $data['nama_kategori'];
        $deskripsi     = $data['deskripsi'] ?? '';
    } catch (PDOException $e) {
        $error = "Gagal mengambil data: " . $e->getMessage();
    }
}

// Proses simpan saat form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_kategori = strtoupper(trim($_POST['kode_kategori'] ?? ''));
    $nama_kategori = trim($_POST['nama_kategori'] ?? '');
    $deskripsi     = trim($_POST['deskripsi'] ?? '');

    if ($kode_kategori === '' || $nama_kategori === '') {
        $error = "Kode kategori dan nama kategori wajib diisi!";
    } else {
        try {
            if ($is_edit) {
                $sql = "UPDATE kategori_alat 
                        SET kode_kategori = :kode, nama_kategori = :nama, deskripsi = :deskripsi 
                        WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'kode'      => $kode_kategori,
                    'nama'      => $nama_kategori,
                    'deskripsi' => $deskripsi,
                    'id'        => $id
                ]);
                $_SESSION['flash_success'] = "Kategori berhasil diperbarui.";
            } else {
                $sql = "INSERT INTO kategori_alat (kode_kategori, nama_kategori, deskripsi) 
                        VALUES (:kode, :nama, :deskripsi)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'kode'      => $kode_kategori,
                    'nama'      => $nama_kategori,
                    'deskripsi' => $deskripsi
                ]);
                $_SESSION['flash_success'] = "Kategori baru berhasil ditambahkan.";
            }

            header("Location: index.php");
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                $error = "Kode kategori atau nama kategori sudah terdaftar!";
            } else {
                $error = "Gagal menyimpan data: " . $e->getMessage();
            }
        }
    }
}

require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><?= e($page_title) ?></h4>
        <p class="text-muted mb-0" style="font-size: 14px;">Lengkapi informasi pengelompokan alat laboratorium.</p>
    </div>
    <a href="index.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger py-2"><?= e($error) ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-7">
        <div class="card-custom p-4">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size: 13px;">Kode Kategori <span class="text-danger">*</span></label>
                    <input type="text" name="kode_kategori" class="form-control" 
                           placeholder="Contoh: KAT-07" value="<?= e($kode_kategori) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size: 13px;">Nama Kategori <span class="text-danger">*</span></label>
                    <input type="text" name="nama_kategori" class="form-control" 
                           placeholder="Contoh: Mekatronika" value="<?= e($nama_kategori) ?>" required>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold" style="font-size: 13px;">Deskripsi</label>
                    <textarea name="deskripsi" class="form-control" rows="3" 
                              placeholder="Keterangan singkat kategori alat..."><?= e($deskripsi) ?></textarea>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-brown px-4">Simpan Kategori</button>
                    <a href="index.php" class="btn btn-outline-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>