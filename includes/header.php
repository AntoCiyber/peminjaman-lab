<?php
$page_title  = $page_title ?? 'Dashboard';
$breadcrumb  = $breadcrumb ?? 'Ruang kerja / Dashboard';
$active_menu = $active_menu ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> - LabPinjam</title>
    <!-- Bootstrap 5 CSS & Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
    background-color: #f7f6f2;
    color: #2c251d;
    font-family: 'Plus Jakarta Sans', 'Segoe UI', system-ui, sans-serif;
    line-height: 1.6;
    }
    h1, h2, h3 {
    font-weight: 600;
    letter-spacing: 0.01em;
    }
        .sidebar {
            width: 260px;
            min-height: 100vh;
            background-color: #ffffff;
            border-right: 1px solid #e6e2d8;
            position: fixed;
            top: 0;
            left: 0;
            display: flex;
            flex-direction: column;
            z-index: 100;
        }
        .sidebar-brand {
            padding: 20px 22px;
            border-bottom: 1px solid #e6e2d8;
        }
        .sidebar-brand h5 {
            margin: 0;
            font-weight: 700;
            color: #3b2f1e;
        }
        .sidebar-brand small {
            font-size: 11px;
            letter-spacing: 0.8px;
            color: #8c8275;
            font-weight: 600;
        }
        .sidebar-menu {
            padding: 14px 12px;
            flex-grow: 1;
            overflow-y: auto;
        }
        .menu-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: #9b9183;
            padding: 10px 12px 4px;
            letter-spacing: 0.6px;
        }
        .sidebar .nav-link {
            color: #52483c;
            font-size: 14px;
            padding: 8px 12px;
            border-radius: 8px;
            margin-bottom: 2px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.15s;
        }
        .sidebar .nav-link:hover {
            background-color: #f3f0e8;
            color: #3b2f1e;
        }
        .sidebar .nav-link.active {
            background-color: #3b2f1e;
            color: #ffffff;
            font-weight: 600;
        }
        .sidebar-footer {
            padding: 14px 16px;
            border-top: 1px solid #e6e2d8;
            background-color: #faf9f6;
        }
        .main-wrapper {
            margin-left: 260px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .topbar {
            background-color: #ffffff;
            border-bottom: 1px solid #e6e2d8;
            padding: 14px 28px;
        }
        .content-area {
            padding: 24px 28px;
            flex-grow: 1;
        }
        .card-custom {
            background: #ffffff;
            border: 1px solid #e6e2d8;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        }
        .btn-brown {
            background-color: #3b2f1e;
            color: #ffffff;
            border: none;
        }
        .btn-brown:hover {
            background-color: #292014;
            color: #ffffff;
        }
    </style>
</head>
<body>

<!-- SIDEBAR KIRI -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <h5><i class="bi bi-box-seam-fill me-2"></i>LabPinjam</h5>
        <small>ADMINISTRASI LAB</small>
    </div>

    <div class="sidebar-menu">
        <div class="menu-label">Menu Utama</div>
        <a href="/peminjaman-lab/index.php" class="nav-link <?= $active_menu === 'dashboard' ? 'active' : '' ?>">
            <i class="bi bi-grid"></i> Dashboard
        </a>

        <div class="menu-label">Master Data</div>
        <a href="/peminjaman-lab/alat/index.php" class="nav-link <?= $active_menu === 'alat' ? 'active' : '' ?>">
            <i class="bi bi-tools"></i> Data Alat
        </a>
        <a href="/peminjaman-lab/kategori/index.php" class="nav-link <?= $active_menu === 'kategori' ? 'active' : '' ?>">
            <i class="bi bi-tags"></i> Kategori Alat
        </a>
        <a href="/peminjaman-lab/mahasiswa/index.php" class="nav-link <?= $active_menu === 'mahasiswa' ? 'active' : '' ?>">
            <i class="bi bi-people"></i> Data Mahasiswa
        </a>

        <div class="menu-label">Transaksi</div>
        <a href="/peminjaman-lab/peminjaman/index.php" class="nav-link <?= $active_menu === 'peminjaman' ? 'active' : '' ?>">
            <i class="bi bi-journal-text"></i> Peminjaman
        </a>
        <a href="/peminjaman-lab/peminjaman/tambah.php" class="nav-link <?= $active_menu === 'form_peminjaman' ? 'active' : '' ?>">
            <i class="bi bi-plus-circle"></i> Form Peminjaman
        </a>
        <a href="/peminjaman-lab/peminjaman/pengembalian.php" class="nav-link <?= $active_menu === 'pengembalian' ? 'active' : '' ?>">
            <i class="bi bi-arrow-return-left"></i> Pengembalian
        </a>

        <div class="menu-label">Laporan</div>
        <a href="/peminjaman-lab/laporan/keterlambatan.php" class="nav-link <?= $active_menu === 'keterlambatan' ? 'active' : '' ?>">
            <i class="bi bi-exclamation-circle"></i> Keterlambatan
        </a>
        <a href="/peminjaman-lab/laporan/populer.php" class="nav-link <?= $active_menu === 'populer' ? 'active' : '' ?>">
            <i class="bi bi-bar-chart-line"></i> Alat Terpopuler
        </a>
    </div>

    <div class="sidebar-footer">
        <div class="d-flex align-items-center mb-2">
            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-2" style="width:34px;height:34px;font-weight:bold;">
                <?= strtoupper(substr($_SESSION['admin_nama'] ?? 'A', 0, 1)) ?>
            </div>
            <div style="line-height: 1.2;">
                <div class="fw-semibold" style="font-size: 13px;"><?= e($_SESSION['admin_nama'] ?? 'Administrator') ?></div>
                <small class="text-muted" style="font-size: 11px;">Admin laboratorium</small>
            </div>
        </div>
        <a href="/peminjaman-lab/logout.php" class="btn btn-outline-danger btn-sm w-100 mt-1">
            <i class="bi bi-box-arrow-right me-1"></i> Logout
        </a>
    </div>
</aside>

<!-- AREA KONTEN KANAN -->
<div class="main-wrapper">
    <header class="topbar d-flex justify-content-between align-items-center">
        <div>
            <div class="fw-bold" style="font-size: 15px;">Sistem Peminjaman Alat Laboratorium</div>
            <small class="text-muted">Laboratorium Terpadu FMIPA UNTAN</small>
        </div>
        <div class="text-end">
            <span class="badge bg-light text-dark border px-3 py-2">
                <i class="bi bi-calendar3 me-1"></i> <?= date('d M Y') ?>
            </span>
        </div>
    </header>

    <main class="content-area">
        <div class="text-muted mb-2" style="font-size: 12px;"><?= e($breadcrumb) ?></div>