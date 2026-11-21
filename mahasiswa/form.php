<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$id      = (int)($_GET['id'] ?? 0);
$is_edit = $id > 0;

$page_title  = $is_edit ? 'Edit Mahasiswa' : 'Tambah Mahasiswa';
$breadcrumb  = 'Ruang kerja / Master Data / Data Mahasiswa';
$active_menu = 'mahasiswa';

$nim   = '';
$nama  = '';
$prodi = '';
$no_hp = '';
$error = '';

if ($is_edit) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM mahasiswa WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch();

        if (!$data) {
            $_SESSION['flash_error'] = "Data mahasiswa tidak ditemukan.";
            header("Location: index.php");
            exit;
        }

        $nim   = $data['nim'];
        $nama  = $data['nama'];
        $prodi = $data['prodi'] ?? '';
        $no_hp = $data['no_hp'] ?? '';
    } catch (PDOException $e) {
        $error = "Gagal memuat data: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nim   = strtoupper(trim($_POST['nim'] ?? ''));
    $nama  = trim($_POST['nama'] ?? '');
    $prodi = trim($_POST['prodi'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');

    if ($nim === '' || $nama === '') {
        $error = "NIM dan nama mahasiswa wajib diisi!";
    } else {
        try {
            if ($is_edit) {
                $sql = "UPDATE mahasiswa 
                        SET nim = :nim, nama = :nama, prodi = :prodi, no_hp = :no_hp 
                        WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'nim'   => $nim,
                    'nama'  => $nama,
                    'prodi' => $prodi !== '' ? $prodi : null,
                    'no_hp' => $no_hp !== '' ? $no_hp : null,
                    'id'    => $id
                ]);
                $_SESSION['flash_success'] = "Data mahasiswa berhasil diperbarui.";
            } else {
                $sql = "INSERT INTO mahasiswa (nim, nama, prodi, no_hp) 
                        VALUES (:nim, :nama, :prodi, :no_hp)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'nim'   => $nim,
                    'nama'  => $nama,
                    'prodi' => $prodi !== '' ? $prodi : null,
                    'no_hp' => $no_hp !== '' ? $no_hp : null
                ]);
                $_SESSION['flash_success'] = "Data mahasiswa baru berhasil ditambahkan.";
            }

            header("Location: index.php");
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                $error = "NIM sudah terdaftar untuk mahasiswa lain!";
            } else {
                $error = "Gagal menyimpan data mahasiswa: " . $e->getMessage();
            }
        }
    }
}

require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><?= e($page_title) ?></h4>
        <p class="text-muted mb-0" style="font-size: 14px;">Lengkapi identitas mahasiswa yang terdaftar sebagai peminjam.</p>
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
            <h6 class="fw-bold mb-1">Informasi Mahasiswa</h6>
            <p class="text-muted mb-3" style="font-size: 12px;">Kolom bertanda <span class="text-danger">*</span> wajib diisi.</p>

            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold" style="font-size: 13px;">NIM <span class="text-danger">*</span></label>
                        <input type="text" name="nim" class="form-control" 
                               placeholder="Contoh: H1051231001" value="<?= e($nim) ?>" required>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label fw-semibold" style="font-size: 13px;">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="nama" class="form-control" 
                               placeholder="Contoh: Oktavia Putri" value="<?= e($nama) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size: 13px;">Program Studi</label>
                        <input type="text" name="prodi" class="form-control" 
                               placeholder="Contoh: Rekayasa Sistem Komputer" value="<?= e($prodi) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size: 13px;">No. HP / WhatsApp</label>
                        <input type="text" name="no_hp" class="form-control" 
                               placeholder="Contoh: 081234567890" value="<?= e($no_hp) ?>">
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4 pt-2 border-top">
                    <button type="submit" class="btn btn-brown px-4">Simpan Data</button>
                    <a href="index.php" class="btn btn-outline-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card-custom p-4">
            <h6 class="fw-bold mb-3">Panduan Pengisian</h6>
            <div class="mb-3" style="font-size: 13px;">
                <strong>01 NIM bersifat unik</strong>
                <div class="text-muted">Sistem otomatis menolak jika NIM sudah terdaftar sebelumnya.</div>
            </div>
            <div class="mb-3" style="font-size: 13px;">
                <strong>02 Nama lengkap sesuai KTM</strong>
                <div class="text-muted">Memudahkan verifikasi identitas saat serah terima alat.</div>
            </div>
            <div style="font-size: 13px;">
                <strong>03 Nomor HP aktif</strong>
                <div class="text-muted">Berguna untuk menghubungi peminjam jika melewati tenggat waktu.</div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>