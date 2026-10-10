-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 10, 2026 at 09:59 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `uangku_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `inventaris`
--

CREATE TABLE `inventaris` (
  `id` int(11) NOT NULL,
  `sku` varchar(20) NOT NULL,
  `nama_produk` varchar(100) NOT NULL,
  `harga` decimal(15,2) NOT NULL,
  `stok` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventaris`
--

INSERT INTO `inventaris` (`id`, `sku`, `nama_produk`, `harga`, `stok`) VALUES
(1, 'KOP-001', 'Kopi Susu Gula Aren', 18000.00, 150),
(3, 'ROT-002', 'Roti Tawar Bandung', 15000.00, 8);

-- --------------------------------------------------------

--
-- Table structure for table `tagihan`
--

CREATE TABLE `tagihan` (
  `id` int(11) NOT NULL,
  `nama_tagihan` varchar(100) NOT NULL,
  `tanggal_jatuh_tempo` date NOT NULL,
  `jumlah` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tagihan`
--

INSERT INTO `tagihan` (`id`, `nama_tagihan`, `tanggal_jatuh_tempo`, `jumlah`) VALUES
(1, 'Supplier Biji Kopi', '2026-11-05', 1500000.00),
(2, 'Tagihan Listrik PLN', '2026-11-10', 850000.00);

-- --------------------------------------------------------

--
-- Table structure for table `transaksi`
--

CREATE TABLE `transaksi` (
  `id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `trx_id` varchar(20) NOT NULL,
  `deskripsi` varchar(255) NOT NULL,
  `kategori` varchar(50) NOT NULL,
  `tipe` enum('Pemasukan','Pengeluaran') NOT NULL,
  `jumlah` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaksi`
--

INSERT INTO `transaksi` (`id`, `tanggal`, `trx_id`, `deskripsi`, `kategori`, `tipe`, `jumlah`) VALUES
(1, '2026-11-01', 'TRX-101', 'Penjualan Kasir Shift Pagi', 'Penjualan', 'Pemasukan', 1250000.00),
(2, '2026-11-01', 'TRX-102', 'Beli Susu UHT 2 Karton', 'Bahan Baku', 'Pengeluaran', 340000.00),
(3, '2026-11-02', 'TRX-103', 'Iklan Instagram Ads', 'Pemasaran', 'Pengeluaran', 150000.00),
(4, '2026-10-03', 'TRX-587', 'susu', 'mweme', 'Pemasukan', 17000.00),
(5, '2026-10-03', 'TRX-490', 'Jual Kopi oolong', 'Penjualan', 'Pemasukan', 1000000.00),
(6, '2026-10-03', 'TRX-897', 'Beli Kopi', 'Bahan Baku', 'Pengeluaran', 100000.00),
(8, '2026-10-05', 'TRX-819', 'Jual Seblak', 'Penjualan', 'Pemasukan', 1500000.00),
(9, '2026-10-04', 'TRX-454', 'Jual Freezer', 'Pengembangan', 'Pemasukan', 1500000.00);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `inventaris`
--
ALTER TABLE `inventaris`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tagihan`
--
ALTER TABLE `tagihan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `inventaris`
--
ALTER TABLE `inventaris`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tagihan`
--
ALTER TABLE `tagihan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
