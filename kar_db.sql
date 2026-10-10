-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 19, 2025 at 05:44 AM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `kar_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `activity_type` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `worker_id` int(11) DEFAULT NULL,
  `path_id` int(11) DEFAULT NULL,
  `confidence` decimal(5,2) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password_hash`, `created_at`) VALUES
(1, 'admin', '$2y$10$mpJ.sz41.RPIkSqYCbKB6uGLeQvW.DCg3S560bkvvpAQyUhFopCfO', '2025-12-19 05:40:19');

-- --------------------------------------------------------

--
-- Table structure for table `face_encodings`
--

CREATE TABLE `face_encodings` (
  `id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `descriptor` text NOT NULL,
  `worker_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `face_encodings`
--

INSERT INTO `face_encodings` (`id`, `filename`, `descriptor`, `worker_id`, `created_at`) VALUES
(1, 'face_1766114487090_6944c4b719ff9.jpg', '{\"timestamp\":1766114487090,\"videoWidth\":640,\"videoHeight\":480,\"captureTime\":\"2025-12-19T03:21:27.090Z\"}', NULL, '2025-12-19 06:51:27'),
(2, 'face_1766114562713_6944c502c1626.jpg', '{\"timestamp\":1766114562713,\"videoWidth\":640,\"videoHeight\":480,\"captureTime\":\"2025-12-19T03:22:42.713Z\"}', NULL, '2025-12-19 06:52:42');

-- --------------------------------------------------------

--
-- Table structure for table `paths`
--

CREATE TABLE `paths` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `x` decimal(10,6) DEFAULT NULL,
  `y` decimal(10,6) DEFAULT NULL,
  `width` decimal(10,6) DEFAULT NULL,
  `height` decimal(10,6) DEFAULT NULL,
  `start_lat` decimal(10,6) DEFAULT NULL,
  `start_lng` decimal(10,6) DEFAULT NULL,
  `end_lat` decimal(10,6) DEFAULT NULL,
  `end_lng` decimal(10,6) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `paths`
--

INSERT INTO `paths` (`id`, `name`, `description`, `x`, `y`, `width`, `height`, `start_lat`, `start_lng`, `end_lat`, `end_lng`, `created_at`) VALUES
(1, 'ورودی اصلی', 'کنترل ورود و خروج کارگران', NULL, NULL, NULL, NULL, '33.487700', '48.355300', '33.488000', '48.356000', '2025-12-19 01:31:49'),
(2, 'سالن تولید', 'نظارت بر فعالیت های تولیدی', NULL, NULL, NULL, NULL, '33.487500', '48.355500', '33.487800', '48.356500', '2025-12-19 01:31:49'),
(3, 'انبار مواد', 'کنترل دسترسی به انبار', NULL, NULL, NULL, NULL, '33.487000', '48.355000', '33.487300', '48.355800', '2025-12-19 01:31:49'),
(4, 'مثال مسیر 1', 'نمونه', '0.100000', '0.100000', '0.300000', '0.300000', NULL, NULL, NULL, NULL, '2025-12-19 01:56:38'),
(5, 'مثال مسیر 1', 'نمونه', '0.100000', '0.100000', '0.300000', '0.300000', NULL, NULL, NULL, NULL, '2025-12-19 01:58:33'),
(6, 'مثال مسیر 1', 'نمونه', '0.100000', '0.100000', '0.300000', '0.300000', NULL, NULL, NULL, NULL, '2025-12-19 02:00:44'),
(7, 'ورودی اصلی', 'کنترل ورود و خروج کارگران', NULL, NULL, NULL, NULL, '33.487700', '48.355300', '33.488000', '48.356000', '2025-12-19 02:03:02'),
(8, 'سالن تولید', 'نظارت بر فعالیت های تولیدی', NULL, NULL, NULL, NULL, '33.487500', '48.355500', '33.487800', '48.356500', '2025-12-19 02:03:02'),
(9, 'انبار مواد', 'کنترل دسترسی به انبار', NULL, NULL, NULL, NULL, '33.487000', '48.355000', '33.487300', '48.355800', '2025-12-19 02:03:02');

-- --------------------------------------------------------

--
-- Table structure for table `path_assignments`
--

CREATE TABLE `path_assignments` (
  `id` int(11) NOT NULL,
  `path_id` int(11) NOT NULL,
  `worker_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `workers`
--

CREATE TABLE `workers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `path_id` int(11) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `workers`
--

INSERT INTO `workers` (`id`, `name`, `details`, `path_id`, `image`, `image_url`, `created_at`) VALUES
(1, 'احمد محمدی', '', 0, '', NULL, '2025-12-19 01:31:49'),
(2, 'علی رضایی', '', 0, '', NULL, '2025-12-19 01:31:49'),
(3, 'حسن کریمی', '', 0, '', NULL, '2025-12-19 01:31:49'),
(4, 'علی رضایی', '', 1, '', NULL, '2025-12-19 02:00:44'),
(5, 'احمد محمدی', '', 0, '', NULL, '2025-12-19 02:03:02'),
(6, 'علی رضایی', '', 0, '', NULL, '2025-12-19 02:03:02');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `worker_id` (`worker_id`),
  ADD KEY `path_id` (`path_id`),
  ADD KEY `idx_activity_type` (`activity_type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `face_encodings`
--
ALTER TABLE `face_encodings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `worker_id` (`worker_id`);

--
-- Indexes for table `paths`
--
ALTER TABLE `paths`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_paths_name` (`name`);

--
-- Indexes for table `path_assignments`
--
ALTER TABLE `path_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_path_worker` (`path_id`,`worker_id`),
  ADD KEY `worker_id` (`worker_id`);

--
-- Indexes for table `workers`
--
ALTER TABLE `workers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_workers_name` (`name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `face_encodings`
--
ALTER TABLE `face_encodings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `paths`
--
ALTER TABLE `paths`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `path_assignments`
--
ALTER TABLE `path_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `workers`
--
ALTER TABLE `workers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`worker_id`) REFERENCES `workers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `activity_logs_ibfk_2` FOREIGN KEY (`path_id`) REFERENCES `paths` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `face_encodings`
--
ALTER TABLE `face_encodings`
  ADD CONSTRAINT `face_encodings_ibfk_1` FOREIGN KEY (`worker_id`) REFERENCES `workers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `path_assignments`
--
ALTER TABLE `path_assignments`
  ADD CONSTRAINT `path_assignments_ibfk_1` FOREIGN KEY (`path_id`) REFERENCES `paths` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `path_assignments_ibfk_2` FOREIGN KEY (`worker_id`) REFERENCES `workers` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
