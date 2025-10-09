-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 09 Sep 2025 pada 18.23
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
-- Database: `it-rspi`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_bahasa`
--

CREATE TABLE `tb_bahasa` (
  `id_bahasa` int(11) NOT NULL,
  `id_calon` int(11) NOT NULL,
  `nama_bahasa` varchar(50) DEFAULT NULL,
  `tingkat` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_barang`
--

CREATE TABLE `tb_barang` (
  `barang_id` int(11) NOT NULL,
  `pengajuan_id` int(11) DEFAULT NULL,
  `nama_barang` varchar(150) NOT NULL,
  `jenis_barang` enum('Komputer & Laptop','Komponen Komputer & Laptop','Printer & Scanner','Komponen Printer & Scanner','Komponen Network') NOT NULL,
  `nomor_seri` varchar(150) DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `jumlah` int(11) NOT NULL,
  `harga` decimal(15,2) DEFAULT NULL,
  `spesifikasi` text DEFAULT NULL,
  `tanggal_terima` date DEFAULT curdate(),
  `kondisi` enum('baru','bekas','rusak','dalam perbaikan','-') DEFAULT '-',
  `lokasi_id` int(11) DEFAULT NULL,
  `keterangan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tb_barang`
--

INSERT INTO `tb_barang` (`barang_id`, `pengajuan_id`, `nama_barang`, `jenis_barang`, `nomor_seri`, `ip_address`, `jumlah`, `harga`, `spesifikasi`, `tanggal_terima`, `kondisi`, `lokasi_id`, `keterangan`) VALUES
(2, NULL, 'SSD ADATA SU650 512GB', 'Komponen Komputer & Laptop', '3efghggg', '', 1, 22.00, 'dwdw', '2025-09-09', 'baru', 8, 'nbbb');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_calon`
--

CREATE TABLE `tb_calon` (
  `id_calon` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `jenis_kelamin` enum('L','P') DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `divisi_lamaran` varchar(50) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `status` enum('belum tes','tes selesai','lulus','tidak lulus') DEFAULT 'belum tes',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data untuk tabel `tb_calon`
--

INSERT INTO `tb_calon` (`id_calon`, `username`, `password`, `nama_lengkap`, `email`, `no_hp`, `jenis_kelamin`, `tanggal_lahir`, `alamat`, `divisi_lamaran`, `foto`, `status`, `created_at`, `updated_at`) VALUES
(2, 'firda', '$2y$10$1oB36s/z0n87mzzfD1LzQuYCTUF1qBbqrvsb4LnNWzYrlZf8m32GC', 'www', NULL, '908221222222', NULL, NULL, NULL, NULL, NULL, 'belum tes', '2025-07-15 14:48:38', '2025-07-15 14:48:38');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_keahlian`
--

CREATE TABLE `tb_keahlian` (
  `id_keahlian` int(11) NOT NULL,
  `id_calon` int(11) NOT NULL,
  `nama_keahlian` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_kegiatan_lembur`
--

CREATE TABLE `tb_kegiatan_lembur` (
  `id_kegiatan` int(11) NOT NULL,
  `id_lembur` int(11) NOT NULL,
  `kegiatan` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tb_kegiatan_lembur`
--

INSERT INTO `tb_kegiatan_lembur` (`id_kegiatan`, `id_lembur`, `kegiatan`) VALUES
(6, 4, 'Perbaikan Sever'),
(7, 4, 'Menambah Jaringan'),
(8, 5, 'Menjadi Petugas Presentasi'),
(9, 6, 'fff'),
(10, 6, 'aaa'),
(11, 6, 'qqqq'),
(17, 9, 'aaaa');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_lembur`
--

CREATE TABLE `tb_lembur` (
  `id_lembur` int(11) NOT NULL,
  `id_staff` int(11) NOT NULL,
  `tanggal_lembur` date NOT NULL,
  `status_lembur` enum('Menunggu','Diterima','Ditolak') DEFAULT 'Menunggu',
  `id_pimpinan` int(11) DEFAULT NULL,
  `waktu_input` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tb_lembur`
--

INSERT INTO `tb_lembur` (`id_lembur`, `id_staff`, `tanggal_lembur`, `status_lembur`, `id_pimpinan`, `waktu_input`) VALUES
(4, 2, '2025-05-31', 'Ditolak', 1, '2025-05-31 03:08:03'),
(5, 5, '2025-05-31', 'Diterima', 1, '2025-05-31 03:11:46'),
(6, 2, '2025-06-07', 'Diterima', 1, '2025-06-07 04:11:00'),
(9, 5, '2025-07-14', 'Menunggu', NULL, '2025-07-14 14:52:01');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_logbook`
--

CREATE TABLE `tb_logbook` (
  `id_log` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `tanggal_log` date NOT NULL,
  `judul_log` varchar(200) NOT NULL,
  `deskripsi_log` text NOT NULL,
  `catatan_log` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_lokasi`
--

CREATE TABLE `tb_lokasi` (
  `lokasi_id` int(11) NOT NULL,
  `nama_lokasi` varchar(100) NOT NULL,
  `keterangan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tb_lokasi`
--

INSERT INTO `tb_lokasi` (`lokasi_id`, `nama_lokasi`, `keterangan`) VALUES
(1, 'IT', 'Unit IT dan Komputer'),
(2, 'Keuangan', 'Unit Keuangan'),
(3, 'FO Ralan', 'Front Office atau Pendaftaran Rawat Jalan'),
(4, 'FO Ranap', 'Front Office atau Pendaftaran Rawat Inap (IGD)'),
(5, 'Kecubung', 'Counter Kecubung'),
(6, 'Yakut C', 'Counter Yakut C'),
(7, 'Counter Lt.3', 'Counter Rawat Inap Lt.3'),
(8, 'Counter Lt.2', 'Counter Rawat Inap Lt.2'),
(9, 'Manajemen', 'Unit Manajemen'),
(10, 'Radiologi', 'Ruang Radiologi'),
(11, 'Lab', 'Ruang Laboratorium'),
(12, 'PL Anak', 'Poliklinik Anak'),
(13, 'PL Kandungan', 'Poliklinik Kandungan'),
(14, 'PL Penyakit Dalam', 'Poliklinik Penyakit Dalam');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_mutasi_barang`
--

CREATE TABLE `tb_mutasi_barang` (
  `mutasi_id` int(11) NOT NULL,
  `barang_id` int(11) NOT NULL,
  `lokasi_asal` int(11) DEFAULT NULL,
  `lokasi_tujuan` int(11) DEFAULT NULL,
  `tanggal_mutasi` date DEFAULT curdate(),
  `id_user` int(11) DEFAULT NULL,
  `keterangan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tb_mutasi_barang`
--

INSERT INTO `tb_mutasi_barang` (`mutasi_id`, `barang_id`, `lokasi_asal`, `lokasi_tujuan`, `tanggal_mutasi`, `id_user`, `keterangan`) VALUES
(2, 2, 3, 8, '2025-09-10', 5, 'qqq');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_organisasi`
--

CREATE TABLE `tb_organisasi` (
  `id_organisasi` int(11) NOT NULL,
  `id_calon` int(11) NOT NULL,
  `nama_organisasi` varchar(100) DEFAULT NULL,
  `tahun_mulai` year(4) DEFAULT NULL,
  `tahun_selesai` year(4) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_pendidikan`
--

CREATE TABLE `tb_pendidikan` (
  `id_pendidikan` int(11) NOT NULL,
  `id_calon` int(11) NOT NULL,
  `jenjang` varchar(50) DEFAULT NULL,
  `nama_sekolah` varchar(100) DEFAULT NULL,
  `jurusan` varchar(100) DEFAULT NULL,
  `tahun_masuk` year(4) DEFAULT NULL,
  `tahun_lulus` year(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_pengajuan`
--

CREATE TABLE `tb_pengajuan` (
  `pengajuan_id` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `nama_barang` varchar(150) NOT NULL,
  `unit` varchar(50) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `perkiraan_harga` decimal(15,2) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `status` enum('diajukan','disetujui','ditolak','selesai') DEFAULT 'diajukan',
  `tanggal_pengajuan` date DEFAULT curdate(),
  `tanggal_acc` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tb_pengajuan`
--

INSERT INTO `tb_pengajuan` (`pengajuan_id`, `id_user`, `nama_barang`, `unit`, `jumlah`, `perkiraan_harga`, `keterangan`, `status`, `tanggal_pengajuan`, `tanggal_acc`) VALUES
(2, 5, 'fff', 'Unit IT', 2, 200.00, 'fff', 'disetujui', '2025-09-09', '2025-09-09');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_pengalaman`
--

CREATE TABLE `tb_pengalaman` (
  `id_pengalaman` int(11) NOT NULL,
  `id_calon` int(11) NOT NULL,
  `nama_perusahaan` varchar(100) DEFAULT NULL,
  `posisi` varchar(100) DEFAULT NULL,
  `tahun_masuk` year(4) DEFAULT NULL,
  `tahun_keluar` year(4) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_remote`
--

CREATE TABLE `tb_remote` (
  `id_remote` int(11) NOT NULL,
  `ip_add` varchar(25) NOT NULL,
  `password` varchar(25) NOT NULL,
  `nama_desktop` varchar(25) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tb_remote`
--

INSERT INTO `tb_remote` (`id_remote`, `ip_add`, `password`, `nama_desktop`) VALUES
(3, '1 529 038 096', 'riyanap210896', 'AndyDesk Laptop Riyan');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_sertifikasi`
--

CREATE TABLE `tb_sertifikasi` (
  `id_sertifikasi` int(11) NOT NULL,
  `id_calon` int(11) NOT NULL,
  `nama_sertifikasi` varchar(100) DEFAULT NULL,
  `penyelenggara` varchar(100) DEFAULT NULL,
  `tahun` year(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_user`
--

CREATE TABLE `tb_user` (
  `id_user` int(11) NOT NULL,
  `nip` varchar(30) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `role` enum('Kepala Ruangan','Staff') NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data untuk tabel `tb_user`
--

INSERT INTO `tb_user` (`id_user`, `nip`, `username`, `password`, `nama_lengkap`, `email`, `no_hp`, `role`, `foto`, `status`, `created_at`, `updated_at`) VALUES
(1, '097.011113', 'admin', 'admin', 'Qhusnul Arinda, Amd. Far', 'arien@gmail.com', '082130304411', 'Kepala Ruangan', '1753849951_004170300_1636348075-young-man-engineer-making-program-analyses_1303-20402.png', 'aktif', '2024-11-30 16:00:00', '2025-07-30 04:32:31'),
(5, '635.090125', 'riyan', '12345', 'Riyan Aditya Pradana, S.Kom', 'riyanadityapradanaa@gmail.com', '082130304411', 'Staff', '1753883681_1753801450_IMG_20250227_182823-removebg-preview.png', 'aktif', '2025-03-11 16:00:00', '2025-07-30 13:54:41');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `tb_bahasa`
--
ALTER TABLE `tb_bahasa`
  ADD PRIMARY KEY (`id_bahasa`),
  ADD KEY `id_calon` (`id_calon`);

--
-- Indeks untuk tabel `tb_barang`
--
ALTER TABLE `tb_barang`
  ADD PRIMARY KEY (`barang_id`),
  ADD KEY `pengajuan_id` (`pengajuan_id`);

--
-- Indeks untuk tabel `tb_calon`
--
ALTER TABLE `tb_calon`
  ADD PRIMARY KEY (`id_calon`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indeks untuk tabel `tb_keahlian`
--
ALTER TABLE `tb_keahlian`
  ADD PRIMARY KEY (`id_keahlian`),
  ADD KEY `id_calon` (`id_calon`);

--
-- Indeks untuk tabel `tb_kegiatan_lembur`
--
ALTER TABLE `tb_kegiatan_lembur`
  ADD PRIMARY KEY (`id_kegiatan`),
  ADD KEY `id_lembur` (`id_lembur`);

--
-- Indeks untuk tabel `tb_lembur`
--
ALTER TABLE `tb_lembur`
  ADD PRIMARY KEY (`id_lembur`);

--
-- Indeks untuk tabel `tb_logbook`
--
ALTER TABLE `tb_logbook`
  ADD PRIMARY KEY (`id_log`);

--
-- Indeks untuk tabel `tb_lokasi`
--
ALTER TABLE `tb_lokasi`
  ADD PRIMARY KEY (`lokasi_id`);

--
-- Indeks untuk tabel `tb_mutasi_barang`
--
ALTER TABLE `tb_mutasi_barang`
  ADD PRIMARY KEY (`mutasi_id`),
  ADD KEY `barang_id` (`barang_id`),
  ADD KEY `lokasi_asal` (`lokasi_asal`),
  ADD KEY `lokasi_tujuan` (`lokasi_tujuan`),
  ADD KEY `id_user` (`id_user`);

--
-- Indeks untuk tabel `tb_organisasi`
--
ALTER TABLE `tb_organisasi`
  ADD PRIMARY KEY (`id_organisasi`),
  ADD KEY `id_calon` (`id_calon`);

--
-- Indeks untuk tabel `tb_pendidikan`
--
ALTER TABLE `tb_pendidikan`
  ADD PRIMARY KEY (`id_pendidikan`),
  ADD KEY `id_calon` (`id_calon`);

--
-- Indeks untuk tabel `tb_pengajuan`
--
ALTER TABLE `tb_pengajuan`
  ADD PRIMARY KEY (`pengajuan_id`),
  ADD KEY `id_user` (`id_user`);

--
-- Indeks untuk tabel `tb_pengalaman`
--
ALTER TABLE `tb_pengalaman`
  ADD PRIMARY KEY (`id_pengalaman`),
  ADD KEY `id_calon` (`id_calon`);

--
-- Indeks untuk tabel `tb_remote`
--
ALTER TABLE `tb_remote`
  ADD PRIMARY KEY (`id_remote`);

--
-- Indeks untuk tabel `tb_sertifikasi`
--
ALTER TABLE `tb_sertifikasi`
  ADD PRIMARY KEY (`id_sertifikasi`),
  ADD KEY `id_calon` (`id_calon`);

--
-- Indeks untuk tabel `tb_user`
--
ALTER TABLE `tb_user`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `nip` (`nip`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `tb_bahasa`
--
ALTER TABLE `tb_bahasa`
  MODIFY `id_bahasa` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `tb_barang`
--
ALTER TABLE `tb_barang`
  MODIFY `barang_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `tb_calon`
--
ALTER TABLE `tb_calon`
  MODIFY `id_calon` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `tb_keahlian`
--
ALTER TABLE `tb_keahlian`
  MODIFY `id_keahlian` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `tb_kegiatan_lembur`
--
ALTER TABLE `tb_kegiatan_lembur`
  MODIFY `id_kegiatan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT untuk tabel `tb_lembur`
--
ALTER TABLE `tb_lembur`
  MODIFY `id_lembur` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `tb_logbook`
--
ALTER TABLE `tb_logbook`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `tb_lokasi`
--
ALTER TABLE `tb_lokasi`
  MODIFY `lokasi_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT untuk tabel `tb_mutasi_barang`
--
ALTER TABLE `tb_mutasi_barang`
  MODIFY `mutasi_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `tb_organisasi`
--
ALTER TABLE `tb_organisasi`
  MODIFY `id_organisasi` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `tb_pendidikan`
--
ALTER TABLE `tb_pendidikan`
  MODIFY `id_pendidikan` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `tb_pengajuan`
--
ALTER TABLE `tb_pengajuan`
  MODIFY `pengajuan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `tb_pengalaman`
--
ALTER TABLE `tb_pengalaman`
  MODIFY `id_pengalaman` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `tb_remote`
--
ALTER TABLE `tb_remote`
  MODIFY `id_remote` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `tb_sertifikasi`
--
ALTER TABLE `tb_sertifikasi`
  MODIFY `id_sertifikasi` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `tb_user`
--
ALTER TABLE `tb_user`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `tb_bahasa`
--
ALTER TABLE `tb_bahasa`
  ADD CONSTRAINT `tb_bahasa_ibfk_1` FOREIGN KEY (`id_calon`) REFERENCES `tb_calon` (`id_calon`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tb_barang`
--
ALTER TABLE `tb_barang`
  ADD CONSTRAINT `tb_barang_ibfk_1` FOREIGN KEY (`pengajuan_id`) REFERENCES `tb_pengajuan` (`pengajuan_id`);

--
-- Ketidakleluasaan untuk tabel `tb_keahlian`
--
ALTER TABLE `tb_keahlian`
  ADD CONSTRAINT `tb_keahlian_ibfk_1` FOREIGN KEY (`id_calon`) REFERENCES `tb_calon` (`id_calon`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tb_kegiatan_lembur`
--
ALTER TABLE `tb_kegiatan_lembur`
  ADD CONSTRAINT `tb_kegiatan_lembur_ibfk_1` FOREIGN KEY (`id_lembur`) REFERENCES `tb_lembur` (`id_lembur`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tb_mutasi_barang`
--
ALTER TABLE `tb_mutasi_barang`
  ADD CONSTRAINT `tb_mutasi_barang_ibfk_1` FOREIGN KEY (`barang_id`) REFERENCES `tb_barang` (`barang_id`),
  ADD CONSTRAINT `tb_mutasi_barang_ibfk_2` FOREIGN KEY (`lokasi_asal`) REFERENCES `tb_lokasi` (`lokasi_id`),
  ADD CONSTRAINT `tb_mutasi_barang_ibfk_3` FOREIGN KEY (`lokasi_tujuan`) REFERENCES `tb_lokasi` (`lokasi_id`),
  ADD CONSTRAINT `tb_mutasi_barang_ibfk_4` FOREIGN KEY (`id_user`) REFERENCES `tb_user` (`id_user`);

--
-- Ketidakleluasaan untuk tabel `tb_organisasi`
--
ALTER TABLE `tb_organisasi`
  ADD CONSTRAINT `tb_organisasi_ibfk_1` FOREIGN KEY (`id_calon`) REFERENCES `tb_calon` (`id_calon`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tb_pendidikan`
--
ALTER TABLE `tb_pendidikan`
  ADD CONSTRAINT `tb_pendidikan_ibfk_1` FOREIGN KEY (`id_calon`) REFERENCES `tb_calon` (`id_calon`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tb_pengajuan`
--
ALTER TABLE `tb_pengajuan`
  ADD CONSTRAINT `tb_pengajuan_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `tb_user` (`id_user`);

--
-- Ketidakleluasaan untuk tabel `tb_pengalaman`
--
ALTER TABLE `tb_pengalaman`
  ADD CONSTRAINT `tb_pengalaman_ibfk_1` FOREIGN KEY (`id_calon`) REFERENCES `tb_calon` (`id_calon`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tb_sertifikasi`
--
ALTER TABLE `tb_sertifikasi`
  ADD CONSTRAINT `tb_sertifikasi_ibfk_1` FOREIGN KEY (`id_calon`) REFERENCES `tb_calon` (`id_calon`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
