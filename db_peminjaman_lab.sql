-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 21 Nov 2026 pada 07.41
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_peminjaman_lab`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`, `nama_lengkap`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator'),
(2, 'aslab_mei', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Meiliyanto');

-- --------------------------------------------------------

--
-- Struktur dari tabel `alat`
--

CREATE TABLE `alat` (
  `id` int(11) NOT NULL,
  `kode_alat` varchar(20) NOT NULL,
  `nama_alat` varchar(100) NOT NULL,
  `kategori_id` int(11) NOT NULL,
  `stok_total` int(11) NOT NULL DEFAULT 0,
  `stok_tersedia` int(11) NOT NULL DEFAULT 0,
  `lokasi_penyimpanan` varchar(100) DEFAULT NULL,
  `kondisi` enum('Baik','Perawatan','Rusak') NOT NULL DEFAULT 'Baik'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `alat`
--

INSERT INTO `alat` (`id`, `kode_alat`, `nama_alat`, `kategori_id`, `stok_total`, `stok_tersedia`, `lokasi_penyimpanan`, `kondisi`) VALUES
(1, 'BIO-001', 'Mikroskop Binokuler', 1, 12, 8, 'Ruang Biologi Lemari A-02', 'Baik'),
(2, 'KIM-002', 'Neraca Analitik', 2, 6, 5, 'Ruang Kimia Lemari B-01', 'Baik'),
(3, 'FIS-003', 'Multimeter Digital', 3, 16, 14, 'Ruang Fisika Rak C-01', 'Baik'),
(4, 'KIM-004', 'Spektrofotometer UV-Vis', 2, 2, 0, 'Ruang Kimia Meja Instrumen', 'Baik'),
(5, 'BIO-005', 'Sentrifus Laboratorium', 1, 4, 3, 'Ruang Biologi Lemari A-04', 'Perawatan'),
(6, 'KIM-006', 'pH Meter Digital', 2, 8, 7, 'Ruang Kimia Lemari B-03', 'Baik'),
(7, 'FIS-007', 'Osiloskop Digital', 3, 6, 4, 'Ruang Fisika Meja Uji 2', 'Baik'),
(8, 'UMM-008', 'Hot Plate Magnetic Stirrer', 4, 4, 2, 'Gudang Umum Rak A-01', 'Rusak');

-- --------------------------------------------------------

--
-- Struktur dari tabel `kategori_alat`
--

CREATE TABLE `kategori_alat` (
  `id` int(11) NOT NULL,
  `kode_kategori` varchar(20) NOT NULL,
  `nama_kategori` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `kategori_alat`
--

INSERT INTO `kategori_alat` (`id`, `kode_kategori`, `nama_kategori`, `deskripsi`) VALUES
(1, 'KAT-01', 'Biologi', 'Pengamatan dan analisis sampel biologis'),
(2, 'KAT-02', 'Kimia', 'Analisis dan pengukuran bahan kimia'),
(3, 'KAT-03', 'Fisika', 'Pengukuran besaran dan eksperimen fisika'),
(4, 'KAT-04', 'Peralatan Umum', 'Peralatan pendukung kegiatan praktikum'),
(5, 'KAT-05', 'Elektronika', 'Pengujian rangkaian dan komponen elektronik'),
(6, 'KAT-06', 'Keselamatan Kerja', 'Peralatan keselamatan laboratorium');

-- --------------------------------------------------------

--
-- Struktur dari tabel `mahasiswa`
--

CREATE TABLE `mahasiswa` (
  `id` int(11) NOT NULL,
  `nim` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `prodi` varchar(100) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `mahasiswa`
--

INSERT INTO `mahasiswa` (`id`, `nim`, `nama`, `prodi`, `no_hp`) VALUES
(1, '230401001', 'Andi Pratama', 'Biologi', '0812-3456-7801'),
(2, '230402014', 'Siti Nurhaliza', 'Kimia', '0857-8901-2342'),
(3, '230403008', 'Budi Santoso', 'Fisika', '0813-4567-8903'),
(4, '230402021', 'Dewi Lestari', 'Kimia', '0821-5678-9014'),
(5, '230401017', 'Rizky Ramadhan', 'Biologi', '0852-6789-0125'),
(6, '230403026', 'Putri Ayu Wulandari', 'Fisika', '0819-7890-1236'),
(7, 'H1051251114', 'Meiliyanto', 'Rekayasa Sistem Komputer', '0812-3456-7899');

-- --------------------------------------------------------

--
-- Struktur dari tabel `peminjaman`
--

CREATE TABLE `peminjaman` (
  `id` int(11) NOT NULL,
  `kode_peminjaman` varchar(30) NOT NULL,
  `mahasiswa_id` int(11) NOT NULL,
  `alat_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `tanggal_pinjam` date NOT NULL,
  `tanggal_jatuh_tempo` date NOT NULL,
  `tanggal_kembali` date DEFAULT NULL,
  `keperluan` text DEFAULT NULL,
  `status` enum('Dipinjam','Dikembalikan','Terlambat') NOT NULL DEFAULT 'Dipinjam'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `peminjaman`
--

INSERT INTO `peminjaman` (`id`, `kode_peminjaman`, `mahasiswa_id`, `alat_id`, `admin_id`, `jumlah`, `tanggal_pinjam`, `tanggal_jatuh_tempo`, `tanggal_kembali`, `keperluan`, `status`) VALUES
(1, 'PJM-1126-031', 6, 7, 1, 1, '2026-11-02', '2026-11-08', NULL, 'Praktikum gelombang dan osilasi', 'Terlambat'),
(2, 'PJM-1126-035', 5, 1, 1, 1, '2026-11-04', '2026-11-09', NULL, 'Pengamatan jaringan tumbuhan', 'Terlambat'),
(3, 'PJM-1126-038', 4, 4, 2, 1, '2026-11-06', '2026-11-10', NULL, 'Praktikum analisis sampel laboratorium', 'Terlambat'),
(4, 'PJM-1126-040', 3, 3, 1, 2, '2026-11-09', '2026-11-11', '2026-11-11', 'Pengukuran rangkaian listrik', 'Dikembalikan'),
(5, 'PJM-1126-041', 2, 2, 2, 1, '2026-11-10', '2026-11-13', NULL, 'Penimbangan bahan kimia', 'Dipinjam'),
(6, 'PJM-1126-042', 1, 1, 1, 2, '2026-11-11', '2026-11-14', NULL, 'Praktikum mikrobiologi dasar', 'Dipinjam');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indeks untuk tabel `alat`
--
ALTER TABLE `alat`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_alat` (`kode_alat`),
  ADD KEY `fk_alat_kategori` (`kategori_id`);

--
-- Indeks untuk tabel `kategori_alat`
--
ALTER TABLE `kategori_alat`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_kategori` (`kode_kategori`),
  ADD UNIQUE KEY `nama_kategori` (`nama_kategori`);

--
-- Indeks untuk tabel `mahasiswa`
--
ALTER TABLE `mahasiswa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nim` (`nim`);

--
-- Indeks untuk tabel `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_peminjaman` (`kode_peminjaman`),
  ADD KEY `fk_peminjaman_mahasiswa` (`mahasiswa_id`),
  ADD KEY `fk_peminjaman_alat` (`alat_id`),
  ADD KEY `fk_peminjaman_admin` (`admin_id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `alat`
--
ALTER TABLE `alat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `kategori_alat`
--
ALTER TABLE `kategori_alat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `mahasiswa`
--
ALTER TABLE `mahasiswa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `peminjaman`
--
ALTER TABLE `peminjaman`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `alat`
--
ALTER TABLE `alat`
  ADD CONSTRAINT `fk_alat_kategori` FOREIGN KEY (`kategori_id`) REFERENCES `kategori_alat` (`id`) ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD CONSTRAINT `fk_peminjaman_admin` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_peminjaman_alat` FOREIGN KEY (`alat_id`) REFERENCES `alat` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_peminjaman_mahasiswa` FOREIGN KEY (`mahasiswa_id`) REFERENCES `mahasiswa` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
