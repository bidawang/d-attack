-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Oct 09, 2026 at 08:43 AM
-- Server version: 8.0.30
-- PHP Version: 8.5.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `penanganan2`
--

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('5c785c036466adea360111aa28563bfd556b5fba', 'i:1;', 1791431727),
('5c785c036466adea360111aa28563bfd556b5fba:timer', 'i:1791431727;', 1791431727),
('a003db8cf82954a14a185b3e439d6b661ca5355c', 'i:1;', 1791476601),
('a003db8cf82954a14a185b3e439d6b661ca5355c:timer', 'i:1791476601;', 1791476601),
('laravel-cache-5c785c036466adea360111aa28563bfd556b5fba', 'i:4;', 1791526469),
('laravel-cache-5c785c036466adea360111aa28563bfd556b5fba:timer', 'i:1791526469;', 1791526469);

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fees`
--

CREATE TABLE `fees` (
  `id` bigint UNSIGNED NOT NULL,
  `tagihan_id` bigint UNSIGNED NOT NULL,
  `siklus_bunga_id` bigint UNSIGNED NOT NULL,
  `basis_bayar` decimal(15,2) NOT NULL,
  `sisa_kena_bunga` decimal(15,2) NOT NULL,
  `ambang_persen` decimal(5,2) NOT NULL,
  `persen_fee` decimal(5,2) NOT NULL,
  `total_fee` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fee_komponen`
--

CREATE TABLE `fee_komponen` (
  `id` bigint UNSIGNED NOT NULL,
  `kode` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `persen` decimal(5,2) NOT NULL,
  `urutan` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `is_aktif` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fee_komponen`
--

INSERT INTO `fee_komponen` (`id`, `kode`, `nama`, `persen`, `urutan`, `is_aktif`) VALUES
(1, 'akomodasi', 'Akomodasi', 50.00, 1, 1),
(2, 'player', 'Player', 40.00, 2, 1),
(3, 'support', 'Support', 5.00, 3, 1),
(4, 'aset', 'Aset', 5.00, 4, 1);

-- --------------------------------------------------------

--
-- Table structure for table `fee_rincian`
--

CREATE TABLE `fee_rincian` (
  `id` bigint UNSIGNED NOT NULL,
  `fee_id` bigint UNSIGNED NOT NULL,
  `fee_komponen_id` bigint UNSIGNED NOT NULL,
  `persen` decimal(5,2) NOT NULL,
  `jumlah` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint UNSIGNED NOT NULL,
  `reserved_at` int UNSIGNED DEFAULT NULL,
  `available_at` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1);

-- --------------------------------------------------------

--
-- Table structure for table `nasabah`
--

CREATE TABLE `nasabah` (
  `id` bigint UNSIGNED NOT NULL,
  `nama` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `no_hp` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alamat` text COLLATE utf8mb4_unicode_ci,
  `catatan` text COLLATE utf8mb4_unicode_ci,
  `is_aktif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `nasabah`
--

INSERT INTO `nasabah` (`id`, `nama`, `no_hp`, `alamat`, `catatan`, `is_aktif`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Adi', '085668947486', 'karj', 'no', 1, '2026-10-07 17:54:22', '2026-10-07 17:54:22', NULL),
(2, 'danang2', '028996311475', 'sa', NULL, 1, '2026-10-07 18:19:42', '2026-10-07 18:19:42', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pembayaran`
--

CREATE TABLE `pembayaran` (
  `id` bigint UNSIGNED NOT NULL,
  `tagihan_id` bigint UNSIGNED NOT NULL,
  `siklus_bunga_id` bigint UNSIGNED DEFAULT NULL,
  `penagih_id` bigint UNSIGNED DEFAULT NULL,
  `jumlah_bayar` decimal(15,2) NOT NULL,
  `tanggal_bayar` datetime NOT NULL,
  `tahap` enum('sebelum_bunga','sesudah_bunga') COLLATE utf8mb4_unicode_ci NOT NULL,
  `metode` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'tunai',
  `bukti` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `catatan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pembayaran`
--

INSERT INTO `pembayaran` (`id`, `tagihan_id`, `siklus_bunga_id`, `penagih_id`, `jumlah_bayar`, `tanggal_bayar`, `tahap`, `metode`, `bukti`, `catatan`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, NULL, 1, 500000.00, '2026-10-08 12:02:00', 'sebelum_bunga', 'tunai', NULL, NULL, '2026-10-07 20:02:59', '2026-10-07 20:02:59', NULL),
(2, 3, NULL, 1, 500000.00, '2026-10-09 16:02:00', 'sebelum_bunga', 'tunai', NULL, NULL, '2026-10-09 00:02:35', '2026-10-09 00:02:35', NULL),
(3, 2, NULL, 1, 600000.00, '2026-10-09 16:02:00', 'sebelum_bunga', 'tunai', NULL, NULL, '2026-10-09 00:02:48', '2026-10-09 00:02:48', NULL),
(5, 4, NULL, 1, 625000.00, '2026-10-09 16:03:00', 'sebelum_bunga', 'tunai', NULL, NULL, '2026-10-09 00:03:54', '2026-10-09 00:03:54', NULL),
(6, 2, NULL, 1, 25000.00, '2026-10-09 16:03:00', 'sebelum_bunga', 'tunai', NULL, NULL, '2026-10-09 00:03:59', '2026-10-09 00:03:59', NULL),
(7, 5, NULL, 1, 500000.00, '2026-10-09 16:06:00', 'sebelum_bunga', 'tunai', NULL, NULL, '2026-10-09 00:06:34', '2026-10-09 00:06:34', NULL),
(8, 6, NULL, 1, 25000.00, '2026-10-09 16:08:00', 'sebelum_bunga', 'tunai', NULL, NULL, '2026-10-09 00:08:18', '2026-10-09 00:08:18', NULL),
(9, 7, NULL, 1, 1000000.00, '2026-10-09 16:32:00', 'sebelum_bunga', 'tunai', NULL, NULL, '2026-10-09 00:32:33', '2026-10-09 00:32:33', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `pengaturan`
--

CREATE TABLE `pengaturan` (
  `kunci` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nilai` decimal(10,2) NOT NULL,
  `keterangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pengaturan`
--

INSERT INTO `pengaturan` (`kunci`, `nilai`, `keterangan`, `updated_at`) VALUES
('ambang_fee', 60.00, 'Minimal persen bayar (dari sisa kena bunga) agar fee berlaku', '2026-10-08 01:49:29'),
('pembulatan', 1000.00, 'Pembulatan nominal fee', '2026-10-08 01:49:29'),
('persen_bunga', 25.00, 'Persen bunga dari sisa hutang saat tenggat', '2026-10-08 01:49:29'),
('persen_fee', 20.00, 'Persen fee dari total bayar setelah pembungaan', '2026-10-08 01:49:29'),
('tanggal_tenggat', 8.00, 'Tanggal tenggat yang tampil di beranda', '2026-10-08 02:57:02');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('2OfCVXrkZWuhtAgRmsanCtPfc0W2TvxvgpQu9kKP', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0', 'eyJfdG9rZW4iOiJ0UnVvRnpQb01uazVxRnV5cmNqSElXOERYdzk4SEtIejloTFU2dlhIIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2xvY2FsaG9zdDo4MDAwXC9uYXNhYmFoXC8yIiwicm91dGUiOiJuYXNhYmFoLnNob3cifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI6MSwicGFzc3dvcmRfaGFzaF93ZWIiOiJhYzY0NjJlODY1MjVjYTIwODEwNGQ3NjIyNjcyNmVkOTBiYTczYjg2ZDFhYTY0YWI0ZGQ3ZTBjMzg5YjYyNjNmIn0=', 1791535293);

-- --------------------------------------------------------

--
-- Table structure for table `siklus_bunga`
--

CREATE TABLE `siklus_bunga` (
  `id` bigint UNSIGNED NOT NULL,
  `tagihan_id` bigint UNSIGNED NOT NULL,
  `siklus_ke` tinyint UNSIGNED NOT NULL,
  `tenggat_waktu` date NOT NULL,
  `saldo_awal` decimal(15,2) NOT NULL,
  `bayar_sebelum_tenggat` decimal(15,2) NOT NULL DEFAULT '0.00',
  `sisa_kena_bunga` decimal(15,2) NOT NULL,
  `persen_bunga` decimal(5,2) NOT NULL,
  `bunga` decimal(15,2) NOT NULL,
  `tenggat_berikutnya` date DEFAULT NULL,
  `diproses_pada` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tagihan`
--

CREATE TABLE `tagihan` (
  `id` bigint UNSIGNED NOT NULL,
  `kode` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nasabah_id` bigint UNSIGNED NOT NULL,
  `penagih_id` bigint UNSIGNED DEFAULT NULL,
  `jumlah_hutang` decimal(15,2) NOT NULL,
  `tanggal_hutang` date NOT NULL,
  `tenggat_waktu` date NOT NULL,
  `status` enum('bon_gantung','berbunga','lunas','sambungan','belum_lunas') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'belum_lunas',
  `id_tagihan_awal` bigint UNSIGNED DEFAULT NULL,
  `keterangan` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tagihan`
--

INSERT INTO `tagihan` (`id`, `kode`, `nasabah_id`, `penagih_id`, `jumlah_hutang`, `tanggal_hutang`, `tenggat_waktu`, `status`, `id_tagihan_awal`, `keterangan`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'TG00001', 1, 2, 1000000.00, '2026-09-01', '2026-09-08', 'lunas', 0, NULL, '2026-10-07 20:02:28', '2026-10-07 20:02:59', NULL),
(2, 'TGH-000002', 1, 2, 625000.00, '2026-10-08', '2026-11-08', 'lunas', 0, 'Tagihan lanjutan dari TG00001. Total tagihan sebelumnya Rp1.000.000. Sudah dibayar Rp500.000. Sisa Rp500.000. Karena pembayaran dilakukan setelah tenggat 08-09-2026, sisa dikenakan bunga 25%. Bunga Rp125.000 (Rp500.000 × 25%). Total tagihan baru Rp625.000.', '2026-10-07 20:02:59', '2026-10-09 00:03:59', NULL),
(3, 'TG00003', 1, 2, 1000000.00, '2026-09-09', '2026-10-08', 'lunas', 0, NULL, '2026-10-08 22:16:57', '2026-10-09 00:02:35', NULL),
(4, 'TGH-000004', 1, 2, 625000.00, '2026-10-09', '2026-11-09', 'lunas', 0, 'Tagihan lanjutan dari TG00003. Total tagihan sebelumnya Rp1.000.000. Sudah dibayar Rp500.000. Sisa Rp500.000. Karena pembayaran dilakukan setelah tenggat 08-10-2026, sisa dikenakan bunga 25%. Bunga Rp125.000 (Rp500.000 × 25%). Total tagihan baru Rp625.000.', '2026-10-09 00:02:35', '2026-10-09 00:03:54', NULL),
(5, 'TG00005', 2, 2, 1000000.00, '2026-09-01', '2026-10-08', 'lunas', 0, NULL, '2026-10-09 00:06:05', '2026-10-09 00:06:34', NULL),
(6, 'TGH-000006', 2, 2, 625000.00, '2026-10-09', '2026-11-09', 'bon_gantung', 0, 'Tagihan lanjutan dari TG00005. Total tagihan sebelumnya Rp1.000.000. Sudah dibayar Rp500.000. Sisa Rp500.000. Karena pembayaran dilakukan setelah tenggat 08-10-2026, sisa dikenakan bunga 25%. Bunga Rp125.000 (Rp500.000 × 25%). Total tagihan baru Rp625.000.', '2026-10-09 00:06:34', '2026-10-09 00:06:34', NULL),
(7, 'TG00007', 2, 2, 10000000.00, '2026-08-06', '2026-09-08', 'lunas', 0, NULL, '2026-10-09 00:13:03', '2026-10-09 00:32:33', NULL),
(8, 'TGH-000008', 2, 2, 11250000.00, '2026-10-09', '2026-11-09', 'sambungan', 0, 'Tagihan lanjutan dari TG00007. Total tagihan sebelumnya Rp10.000.000. Sudah dibayar Rp1.000.000. Sisa Rp9.000.000. Karena pembayaran dilakukan setelah tenggat 08-09-2026, sisa dikenakan bunga 25%. Bunga Rp2.250.000 (Rp9.000.000 × 25%). Total tagihan baru Rp11.250.000.', '2026-10-09 00:32:33', '2026-10-09 00:32:33', NULL),
(9, 'TG00009', 2, NULL, 500000000.00, '2026-08-04', '2026-10-08', 'bon_gantung', NULL, NULL, '2026-10-09 00:41:33', '2026-10-09 00:41:33', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','penagih') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'penagih',
  `no_hp` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_aktif` tinyint(1) NOT NULL DEFAULT '1',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `role`, `no_hp`, `is_aktif`, `remember_token`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Test User', 'test@example.com', '2026-10-07 17:52:13', '$2y$12$anJpmijWI.OincAIHDriEOe.vju2W7j9/FxmGu2t0Ze3.m5v9LrBW', 'admin', NULL, 1, 'xANNNogbZIZXDmWD0MocPzEfyIbgZfqqQVMi9tZnP1BSIlnyNTbfIt47SQzh', '2026-10-07 17:52:14', '2026-10-08 03:54:19', NULL),
(2, 'Danang', 'danang@gmail.com', NULL, '$2y$12$JZ1pY1wTcAuVEiWfIV4f8.OLYAGmWTYulMt2EVCtwY639cQwC7ISu', 'penagih', '085665947586', 1, NULL, '2026-10-07 17:56:42', '2026-10-07 17:56:42', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  ADD KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`);

--
-- Indexes for table `fees`
--
ALTER TABLE `fees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `siklus_bunga_id` (`siklus_bunga_id`),
  ADD KEY `fk_fees_tagihan` (`tagihan_id`);

--
-- Indexes for table `fee_komponen`
--
ALTER TABLE `fee_komponen`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode` (`kode`);

--
-- Indexes for table `fee_rincian`
--
ALTER TABLE `fee_rincian`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_fee_komponen` (`fee_id`,`fee_komponen_id`),
  ADD KEY `fk_rincian_komponen` (`fee_komponen_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `nasabah`
--
ALTER TABLE `nasabah`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_nasabah_nama` (`nama`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_bayar_siklus` (`siklus_bunga_id`),
  ADD KEY `idx_bayar_tagihan_siklus` (`tagihan_id`,`siklus_bunga_id`),
  ADD KEY `idx_bayar_penagih_tgl` (`penagih_id`,`tanggal_bayar`);

--
-- Indexes for table `pengaturan`
--
ALTER TABLE `pengaturan`
  ADD PRIMARY KEY (`kunci`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `siklus_bunga`
--
ALTER TABLE `siklus_bunga`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_siklus` (`tagihan_id`,`siklus_ke`);

--
-- Indexes for table `tagihan`
--
ALTER TABLE `tagihan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode` (`kode`),
  ADD KEY `idx_tagihan_status_tenggat` (`status`,`tenggat_waktu`),
  ADD KEY `idx_tagihan_nasabah` (`nasabah_id`),
  ADD KEY `idx_tagihan_penagih` (`penagih_id`),
  ADD KEY `id_tagihan_awal` (`id_tagihan_awal`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fees`
--
ALTER TABLE `fees`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fee_komponen`
--
ALTER TABLE `fee_komponen`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `fee_rincian`
--
ALTER TABLE `fee_rincian`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `nasabah`
--
ALTER TABLE `nasabah`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `pembayaran`
--
ALTER TABLE `pembayaran`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `siklus_bunga`
--
ALTER TABLE `siklus_bunga`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tagihan`
--
ALTER TABLE `tagihan`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `fees`
--
ALTER TABLE `fees`
  ADD CONSTRAINT `fk_fees_siklus` FOREIGN KEY (`siklus_bunga_id`) REFERENCES `siklus_bunga` (`id`),
  ADD CONSTRAINT `fk_fees_tagihan` FOREIGN KEY (`tagihan_id`) REFERENCES `tagihan` (`id`);

--
-- Constraints for table `fee_rincian`
--
ALTER TABLE `fee_rincian`
  ADD CONSTRAINT `fk_rincian_fee` FOREIGN KEY (`fee_id`) REFERENCES `fees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rincian_komponen` FOREIGN KEY (`fee_komponen_id`) REFERENCES `fee_komponen` (`id`);

--
-- Constraints for table `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD CONSTRAINT `fk_bayar_penagih` FOREIGN KEY (`penagih_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_bayar_siklus` FOREIGN KEY (`siklus_bunga_id`) REFERENCES `siklus_bunga` (`id`),
  ADD CONSTRAINT `fk_bayar_tagihan` FOREIGN KEY (`tagihan_id`) REFERENCES `tagihan` (`id`);

--
-- Constraints for table `siklus_bunga`
--
ALTER TABLE `siklus_bunga`
  ADD CONSTRAINT `fk_siklus_tagihan` FOREIGN KEY (`tagihan_id`) REFERENCES `tagihan` (`id`);

--
-- Constraints for table `tagihan`
--
ALTER TABLE `tagihan`
  ADD CONSTRAINT `fk_tagihan_nasabah` FOREIGN KEY (`nasabah_id`) REFERENCES `nasabah` (`id`),
  ADD CONSTRAINT `fk_tagihan_penagih` FOREIGN KEY (`penagih_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
