-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 04, 2026 at 09:53 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pharmacy`
--

-- --------------------------------------------------------

--
-- Table structure for table `drugs`
--

CREATE TABLE `drugs` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `dosage` varchar(50) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `reorder_level` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `drugs`
--

INSERT INTO `drugs` (`id`, `name`, `dosage`, `description`, `unit`, `reorder_level`, `created_at`) VALUES
(5, 'OFW Clinic', NULL, 'sad', '120g', 2, '2026-06-01 16:13:56'),
(6, 'KEFUROX', NULL, 'CEFUROXIME', '750 MG', 0, '2026-06-01 16:23:14'),
(10, 'Dev Net, Inc.', '15mg', 'CEFUROXIME', 'ampule', 0, '2026-06-01 16:52:01');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `drug_id` int(11) NOT NULL,
  `type` enum('received_in','given_out') NOT NULL,
  `date` date NOT NULL,
  `supplier` varchar(150) DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `particulars` varchar(255) DEFAULT NULL,
  `balance` int(11) NOT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `drug_id`, `type`, `date`, `supplier`, `cost`, `quantity`, `particulars`, `balance`, `remarks`, `expiry_date`, `user_id`, `created_at`) VALUES
(7, 5, 'received_in', '2026-06-01', '5', 120.00, 2, NULL, 2, NULL, '2027-06-15', 1, '2026-06-01 16:14:32'),
(8, 6, 'received_in', '2026-06-01', 'PHARMASIA', 500.00, 300, NULL, 300, NULL, '2028-12-31', 1, '2026-06-01 16:24:08'),
(9, 6, 'given_out', '2026-06-30', NULL, NULL, 250, NULL, 50, NULL, NULL, 1, '2026-06-01 16:24:31'),
(13, 10, 'received_in', '2026-06-01', '5', 120.00, 3, NULL, 3, NULL, '2027-06-15', 1, '2026-06-01 16:58:08'),
(14, 10, 'given_out', '2026-06-01', NULL, NULL, 1, NULL, 2, NULL, NULL, 1, '2026-06-01 16:58:15');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','staff') NOT NULL DEFAULT 'staff',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$LURdKaQXe7SF/IDCd.YwXuZJpZqBxkC/JOpTNgdC7eP3NeYH1jXqa', 'Administrator', 'admin', '2026-06-01 16:03:45'),
(2, 'haidee', '$2y$10$FZsLUodjYypj5CH46B97x.1wkUouYKLpNovz8abLT/0N5HLpJrrNW', 'haidee', 'staff', '2026-06-01 16:35:31');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `drugs`
--
ALTER TABLE `drugs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `drug_id` (`drug_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `drugs`
--
ALTER TABLE `drugs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`drug_id`) REFERENCES `drugs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
