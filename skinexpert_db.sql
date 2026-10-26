-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 24 Okt 2026 pada 07.39
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
-- Database: `skinexpert_db`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `gejala`
--

CREATE TABLE `gejala` (
  `id` int(11) NOT NULL,
  `kode` varchar(10) NOT NULL,
  `nama` varchar(150) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `gejala`
--

INSERT INTO `gejala` (`id`, `kode`, `nama`, `deskripsi`, `created_at`) VALUES
(1, 'G01', 'Komedo terlihat', 'Tampak titik komedo pada kulit wajah.', '2026-10-22 15:19:13'),
(2, 'G02', 'Kulit tampak berminyak', 'Kulit wajah tampak berminyak.', '2026-10-22 15:19:13'),
(3, 'G03', 'Pori-pori tampak tersumbat', 'Pori-pori tampak tersumbat.', '2026-10-22 15:19:13'),
(4, 'G04', 'Terdapat benjolan kecil', 'Terlihat benjolan kecil pada kulit wajah.', '2026-10-22 15:19:13'),
(5, 'G05', 'Jerawat tampak merah', 'Terdapat benjolan menyerupai jerawat yang tampak merah.', '2026-10-22 15:19:13'),
(6, 'G06', 'Jerawat terasa nyeri', 'Benjolan menyerupai jerawat terasa nyeri.', '2026-10-22 15:19:13'),
(7, 'G07', 'Wajah tampak mengilap', 'Permukaan wajah tampak mengilap.', '2026-10-22 15:19:13');

-- --------------------------------------------------------

--
-- Struktur dari tabel `kondisi_kulit`
--

CREATE TABLE `kondisi_kulit` (
  `id` int(11) NOT NULL,
  `kode` varchar(10) NOT NULL,
  `nama` varchar(150) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `saran` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `kondisi_kulit`
--

INSERT INTO `kondisi_kulit` (`id`, `kode`, `nama`, `deskripsi`, `saran`, `created_at`) VALUES
(1, 'K01', 'Indikasi komedo', 'Kondisi awal yang memiliki gejala menyerupai komedo.', 'Gunakan perawatan kulit yang lembut. Konsultasikan dengan dokter jika menetap atau memburuk.', '2026-10-22 15:19:13'),
(2, 'K02', 'Kulit cenderung berminyak', 'Kondisi kulit dengan ciri minyak berlebih.', 'Gunakan pembersih lembut dan pelembap nonkomedogenik sesuai kebutuhan.', '2026-10-22 15:19:13'),
(3, 'K03', 'Indikasi jerawat meradang', 'Gejala yang dapat menyerupai jerawat meradang.', 'Hindari memencet jerawat. Konsultasikan dengan tenaga kesehatan jika nyeri atau memburuk.', '2026-10-22 15:19:13'),
(4, 'K04', 'Indikasi kulit rentan berjerawat', 'Kondisi awal dengan benjolan kecil dan kulit berminyak.', 'Perhatikan perubahan kulit dan konsultasikan dengan dokter jika keluhan berlanjut.', '2026-10-22 15:19:13');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pemeriksaan`
--

CREATE TABLE `pemeriksaan` (
  `id` int(11) NOT NULL,
  `tanggal` timestamp NOT NULL DEFAULT current_timestamp(),
  `hasil` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `pemeriksaan`
--

INSERT INTO `pemeriksaan` (`id`, `tanggal`, `hasil`) VALUES
(2, '2026-10-22 16:05:29', '[]'),
(3, '2026-10-22 16:31:32', '[{\"kode\":\"K01\",\"nama\":\"Indikasi komedo\",\"deskripsi\":\"Kondisi awal yang memiliki gejala menyerupai komedo.\",\"saran\":\"Gunakan perawatan kulit yang lembut. Konsultasikan dengan dokter jika menetap atau memburuk.\",\"aturan\":[\"R01\"]}]'),
(4, '2026-10-22 16:31:41', '[]'),
(5, '2026-10-22 16:32:04', '[{\"kode\":\"K02\",\"nama\":\"Kulit cenderung berminyak\",\"deskripsi\":\"Kondisi kulit dengan ciri minyak berlebih.\",\"saran\":\"Gunakan pembersih lembut dan pelembap nonkomedogenik sesuai kebutuhan.\",\"aturan\":[\"R02\"]}]'),
(6, '2026-10-22 16:32:17', '[{\"kode\":\"K03\",\"nama\":\"Indikasi jerawat meradang\",\"deskripsi\":\"Gejala yang dapat menyerupai jerawat meradang.\",\"saran\":\"Hindari memencet jerawat. Konsultasikan dengan tenaga kesehatan jika nyeri atau memburuk.\",\"aturan\":[\"R03\"]}]'),
(7, '2026-10-22 16:32:30', '[{\"kode\":\"K04\",\"nama\":\"Indikasi kulit rentan berjerawat\",\"deskripsi\":\"Kondisi awal dengan benjolan kecil dan kulit berminyak.\",\"saran\":\"Perhatikan perubahan kulit dan konsultasikan dengan dokter jika keluhan berlanjut.\",\"aturan\":[\"R04\"]}]'),
(8, '2026-10-22 16:33:43', '[{\"kode\":\"K04\",\"nama\":\"Indikasi kulit rentan berjerawat\",\"deskripsi\":\"Kondisi awal dengan benjolan kecil dan kulit berminyak.\",\"saran\":\"Perhatikan perubahan kulit dan konsultasikan dengan dokter jika keluhan berlanjut.\",\"aturan\":[\"R04\"]}]'),
(9, '2026-10-22 16:41:19', '[]'),
(10, '2026-10-22 16:41:29', '[{\"kode\":\"K01\",\"nama\":\"Indikasi komedo\",\"deskripsi\":\"Kondisi awal yang memiliki gejala menyerupai komedo.\",\"saran\":\"Gunakan perawatan kulit yang lembut. Konsultasikan dengan dokter jika menetap atau memburuk.\",\"aturan\":[\"R01\"]}]');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pemeriksaan_gejala`
--

CREATE TABLE `pemeriksaan_gejala` (
  `pemeriksaan_id` int(11) NOT NULL,
  `gejala_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `pemeriksaan_gejala`
--

INSERT INTO `pemeriksaan_gejala` (`pemeriksaan_id`, `gejala_id`) VALUES
(2, 1),
(3, 1),
(3, 3),
(4, 1),
(5, 2),
(5, 7),
(6, 5),
(6, 6),
(7, 2),
(7, 4),
(8, 2),
(8, 4),
(9, 3),
(10, 1),
(10, 3);

-- --------------------------------------------------------

--
-- Struktur dari tabel `rules`
--

CREATE TABLE `rules` (
  `id` int(11) NOT NULL,
  `kode` varchar(10) NOT NULL,
  `nama` varchar(150) NOT NULL,
  `kondisi_kulit_id` int(11) NOT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `rules`
--

INSERT INTO `rules` (`id`, `kode`, `nama`, `kondisi_kulit_id`, `aktif`, `created_at`) VALUES
(1, 'R01', 'Identifikasi indikasi komedo', 1, 1, '2026-10-22 15:19:13'),
(2, 'R02', 'Identifikasi kulit cenderung berminyak', 2, 1, '2026-10-22 15:19:13'),
(3, 'R03', 'Identifikasi indikasi jerawat meradang', 3, 1, '2026-10-22 15:19:13'),
(4, 'R04', 'Identifikasi kulit rentan berjerawat', 4, 1, '2026-10-22 15:19:13');

-- --------------------------------------------------------

--
-- Struktur dari tabel `rule_gejala`
--

CREATE TABLE `rule_gejala` (
  `rule_id` int(11) NOT NULL,
  `gejala_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `rule_gejala`
--

INSERT INTO `rule_gejala` (`rule_id`, `gejala_id`) VALUES
(1, 1),
(1, 3),
(2, 2),
(2, 7),
(3, 5),
(3, 6),
(4, 2),
(4, 4);

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `gejala`
--
ALTER TABLE `gejala`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode` (`kode`);

--
-- Indeks untuk tabel `kondisi_kulit`
--
ALTER TABLE `kondisi_kulit`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode` (`kode`);

--
-- Indeks untuk tabel `pemeriksaan`
--
ALTER TABLE `pemeriksaan`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `pemeriksaan_gejala`
--
ALTER TABLE `pemeriksaan_gejala`
  ADD PRIMARY KEY (`pemeriksaan_id`,`gejala_id`),
  ADD KEY `gejala_id` (`gejala_id`);

--
-- Indeks untuk tabel `rules`
--
ALTER TABLE `rules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode` (`kode`),
  ADD KEY `kondisi_kulit_id` (`kondisi_kulit_id`);

--
-- Indeks untuk tabel `rule_gejala`
--
ALTER TABLE `rule_gejala`
  ADD PRIMARY KEY (`rule_id`,`gejala_id`),
  ADD KEY `gejala_id` (`gejala_id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `gejala`
--
ALTER TABLE `gejala`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `kondisi_kulit`
--
ALTER TABLE `kondisi_kulit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `pemeriksaan`
--
ALTER TABLE `pemeriksaan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `rules`
--
ALTER TABLE `rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `pemeriksaan_gejala`
--
ALTER TABLE `pemeriksaan_gejala`
  ADD CONSTRAINT `pemeriksaan_gejala_ibfk_1` FOREIGN KEY (`pemeriksaan_id`) REFERENCES `pemeriksaan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pemeriksaan_gejala_ibfk_2` FOREIGN KEY (`gejala_id`) REFERENCES `gejala` (`id`);

--
-- Ketidakleluasaan untuk tabel `rules`
--
ALTER TABLE `rules`
  ADD CONSTRAINT `rules_ibfk_1` FOREIGN KEY (`kondisi_kulit_id`) REFERENCES `kondisi_kulit` (`id`) ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `rule_gejala`
--
ALTER TABLE `rule_gejala`
  ADD CONSTRAINT `rule_gejala_ibfk_1` FOREIGN KEY (`rule_id`) REFERENCES `rules` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rule_gejala_ibfk_2` FOREIGN KEY (`gejala_id`) REFERENCES `gejala` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
