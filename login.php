<?php
session_start();
require_once 'config/database.php';

// Jika sudah login, langsung arahkan ke dashboard
if (isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    try {
        $stmt = $pdo->prepare("SELECT id, username, password, nama_lengkap FROM admin WHERE username = :username LIMIT 1");
        $stmt->execute(['username' => $username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id']   = $admin['id'];
            $_SESSION['admin_nama'] = $admin['nama_lengkap'];

            header("Location: index.php");
            exit;
        } else {
            $error = "Username atau password tidak sesuai!";
        }
    } catch (PDOException $e) {
        $error = "Terjadi kesalahan sistem: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - LabPinjam</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f7f6f2;
            color: #2c251d;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        .login-card {
            background: #ffffff;
            border: 1px solid #e6e2d8;
            border-radius: 14px;
            box-shadow: 0 4px 16px rgba(59, 47, 30, 0.06);
        }
        .btn-brown {
            background-color: #3b2f1e;
            color: #ffffff;
            border: none;
            padding: 10px;
            font-weight: 600;
        }
        .btn-brown:hover {
            background-color: #292014;
            color: #ffffff;
        }
    </style>
</head>
<body class="d-flex align-items-center min-vh-100">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="text-center mb-4">
                <h4 class="fw-bold mb-1" style="color: #3b2f1e;">
                    <i class="bi bi-box-seam-fill me-2"></i>LabPinjam
                </h4>
                <small class="text-muted text-uppercase fw-semibold" style="letter-spacing: 0.8px;">
                    Administrasi Laboratorium
                </small>
            </div>

            <div class="login-card p-4">
                <h5 class="fw-bold mb-1">Masuk ke Sistem</h5>
                <p class="text-muted mb-4" style="font-size: 13px;">Silakan login menggunakan akun admin laboratorium.</p>

                <?php if ($error): ?>
                    <div class="alert alert-danger py-2" style="font-size: 13px;">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size: 13px;">Username</label>
                        <input type="text" name="username" class="form-control" placeholder="Masukkan username" required autofocus>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold" style="font-size: 13px;">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
                    </div>
                    <button type="submit" class="btn btn-brown w-100 rounded-3">Masuk ke Dashboard</button>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>