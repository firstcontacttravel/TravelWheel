-- phpMyAdmin SQL Dump
-- version 5.1.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 25, 2023 at 10:37 AM
-- Server version: 10.4.24-MariaDB
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hca`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(225) NOT NULL,
  `email` varchar(225) NOT NULL,
  `password` varchar(225) NOT NULL,
  `admin` tinyint(1) NOT NULL DEFAULT 0,
  `role` varchar(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `email`, `password`, `admin`, `role`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@hca.com', '$2y$10$TJRdb1WS1Si/ah4jS2zp.uozZPOTIjcbDAESFkxbY0teB8/V7VYg.', 2, '2', '2023-08-27 02:57:59', '2023-08-27 02:57:59');

-- --------------------------------------------------------

--
-- Table structure for table `hca_worker`
--

CREATE TABLE `hca_worker` (
  `id` int(11) NOT NULL,
  `username` varchar(250) NOT NULL,
  `title` varchar(225) NOT NULL,
  `fullname` varchar(250) NOT NULL,
  `email` varchar(250) NOT NULL,
  `phone` varchar(225) NOT NULL,
  `address` varchar(225) NOT NULL,
  `next_of_kin` varchar(225) NOT NULL,
  `phone2` varchar(225) NOT NULL,
  `password` varchar(250) NOT NULL,
  `role` varchar(50) DEFAULT NULL,
  `shift` varchar(250) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `hca_worker`
--

INSERT INTO `hca_worker` (`id`, `username`, `title`, `fullname`, `email`, `phone`, `address`, `next_of_kin`, `phone2`, `password`, `role`, `shift`, `created_at`, `updated_at`) VALUES
(1, 'HCA1', '', 'Oladokun Damilola', 'oladokundamiloladaniel@gmail.com', '', '', '', '', '$2y$10$.TXBY6zJFfLpU1MnHwpIaOwB9vMvzu3LOCIZr.62cjgW2o2imJPMG', '4', NULL, '2023-09-09 06:45:20', '2023-08-28 15:54:21'),
(2, 'HCA2', '', 'Adebola Damilola', 'odd.cr8tives@gmail.com', '', '', '', '', '$2y$10$14c/HZzDt6FSa8p6pA8VVevUR2j8ppjXfUjlDxsaEokPA23raFsLy', '4', NULL, '2023-09-09 06:45:42', '2023-08-28 18:40:06'),
(3, 'HCA3', '', 'Adebola Damilola', 'user@gmail.com', '', '', '', '', '$2y$10$VYZQsRa/Rzo5N7McT3aoEO3/IVvAjulTNH0XMgPkSO0iPjZYqNM5S', '4', NULL, '2023-09-09 06:45:31', '2023-08-28 21:20:50'),
(4, 'fait@gmail.com', '', 'faith', 'fait@gmail.com', '', '', '', '', '$2y$10$rnixwPiIVnwcDNXzFbtYh.hHDzJDl1RXczi4cia.8Jfe0Vq9KTt82', '4', NULL, '2023-09-09 06:45:28', '2023-09-08 20:40:13'),
(5, 'dan@gmail.com', '', 'dan', 'dan@gmail.com', '', '', '', '', '$2y$10$eoKWg8xUu.ESjfR/trZNEu9qViWQLykfr16Ebj2iQubiAULLyHuJa', '4', NULL, '2023-09-09 06:45:24', '2023-09-08 20:51:07'),
(6, 'HCA1', 'Mr', 'Temilope Eromaphere', 'oladokundamiloladaniel@gmail.com', '08109422607', 'Ikordu, Lagos State', 'Demola John', '07034567892', '$2y$10$TB6/CO7wPPWg5JMmyv4ujOyVRT7L22oKan7pxecStnoOHAwHtppWm', '4', NULL, '2023-09-10 14:31:33', '2023-09-10 15:31:33'),
(7, 'Tray', 'Mr', 'John Doe Ben', 'john@gmail.com', '08109422607', 'Ikordu, Lagos State', 'Demola John', '07034567892', '$2y$10$hteTe30m5wgX73oze9GMBOBquHj5NKw9xYZymUBQRy8XAhxKNdzHy', '4', NULL, '2023-09-24 19:26:01', '2023-09-24 20:26:01');

-- --------------------------------------------------------

--
-- Table structure for table `nurse`
--

CREATE TABLE `nurse` (
  `id` int(11) NOT NULL,
  `username` varchar(250) NOT NULL,
  `title` varchar(225) NOT NULL,
  `fullname` varchar(250) NOT NULL,
  `position` varchar(225) NOT NULL,
  `email` varchar(250) NOT NULL,
  `phone` varchar(225) NOT NULL,
  `address` varchar(225) NOT NULL,
  `next_of_kin` varchar(225) NOT NULL,
  `phone2` varchar(225) NOT NULL,
  `supervision` varchar(225) NOT NULL,
  `status` varchar(255) NOT NULL,
  `password` varchar(250) NOT NULL,
  `role` varchar(50) DEFAULT NULL,
  `shift` varchar(250) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `nurse`
--

INSERT INTO `nurse` (`id`, `username`, `title`, `fullname`, `position`, `email`, `phone`, `address`, `next_of_kin`, `phone2`, `supervision`, `status`, `password`, `role`, `shift`, `created_at`, `updated_at`) VALUES
(1, 'Nurse1', '', 'John Doe', '', 'tommydammy02@gmail.com', '', '', '', '', '', '', '$2y$10$oVjzbe7sUyZFXs1hTmNJFenNTm4kcERuf5pmh8lokRCGPmoi1F4xa', '3', NULL, '2023-09-09 06:51:57', '2023-08-28 20:07:14'),
(2, 'Nurse2', '', 'Oladokun Damilola', '', 'odd.cr8tives@gmail.com', '', '', '', '', '', '', '$2y$10$az/1U.knIGnA2Dc7zR3p1eKrA7.xM8cHVBpQTKnIj9.DUeVkMAhGy', '3', NULL, '2023-09-09 06:52:02', '2023-08-28 21:24:01'),
(3, 'Titi', '', 'Titilayo', '', 'titi@gmail.com', '', '', '', '', '', '', '$2y$10$r1kOhG8n4Uv8IoWAlP/SOeHtTzCeQG.g74ApiTAF8rh08x5p70G4q', '3', NULL, '2023-09-09 08:16:45', '2023-09-09 08:16:04'),
(4, 'HCA1', 'Mrs', 'Oladokun Damilola', 'Auxilary', 'oladokundamiloladaniel@gmail.com', '08109422607', 'Ikordu, Lagos State', 'Demola John', '07034567892', 'Team 1', 'Team Leader', '$2y$10$SUCh3VgCIq/v/L5WlHPPO.EGv3nl.5mdsQHmWB0uguvV5KGZjpwjm', '3', NULL, '2023-09-10 14:08:00', '2023-09-10 15:08:00');

-- --------------------------------------------------------

--
-- Table structure for table `residents`
--

CREATE TABLE `residents` (
  `id` int(11) NOT NULL,
  `hca_no` varchar(50) NOT NULL,
  `title` varchar(50) NOT NULL,
  `fullname` varchar(250) NOT NULL,
  `dob` varchar(250) NOT NULL,
  `address` varchar(250) NOT NULL,
  `email` varchar(250) NOT NULL,
  `gender` varchar(50) NOT NULL,
  `maritalstatus` varchar(225) DEFAULT NULL,
  `room_no` varchar(250) NOT NULL,
  `nationalty` varchar(225) NOT NULL,
  `language` varchar(225) NOT NULL,
  `next_of_kin` varchar(225) NOT NULL,
  `relationship` varchar(225) NOT NULL,
  `phone_no` varchar(225) NOT NULL,
  `nextofkin_address` varchar(225) NOT NULL,
  `nextofkin_gender` varchar(225) NOT NULL,
  `medical_status` varchar(225) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `residents`
--

INSERT INTO `residents` (`id`, `hca_no`, `title`, `fullname`, `dob`, `address`, `email`, `gender`, `maritalstatus`, `room_no`, `nationalty`, `language`, `next_of_kin`, `relationship`, `phone_no`, `nextofkin_address`, `nextofkin_gender`, `medical_status`, `created_at`, `updated_at`) VALUES
(1, 'HCARSDT001', 'Mr', 'Oladokun Damilola', '2023-09-01', 'Ikordu, Lagos State', 'oladokundamiloladaniel@gmail.com', 'male', 'Single', 'Floor 1, Room 1 ', 'United Kingdom', 'English', 'Femi Ola', 'Brother', '08109422607', 'Ikorodu, Lagos State', 'male', 'Placement: On going from...', '2023-09-11 07:50:22', '2023-09-09 18:12:11'),
(2, 'HCARSDT002', 'Mrs', 'Adebola Damilola', '2023-09-01', 'Ikordu, Lagos State', 'odd.cr8tives@gmail.com', 'male', 'Single', 'Floor 1, Room 2', 'United Kingdom', 'English', 'Femi Ola', 'Brother', '08109422607', 'Ikorodu, Lagos State', 'male', 'Placement: Not determine', '2023-09-11 07:52:00', '2023-09-09 18:13:42');

-- --------------------------------------------------------

--
-- Table structure for table `resident_form`
--

CREATE TABLE `resident_form` (
  `id` int(11) NOT NULL,
  `hca_name` varchar(225) NOT NULL,
  `hca_no` varchar(225) NOT NULL,
  `form_type` varchar(225) NOT NULL,
  `date` varchar(225) DEFAULT NULL,
  `time` varchar(225) DEFAULT NULL,
  `type` varchar(225) DEFAULT NULL,
  `quality` varchar(225) DEFAULT NULL,
  `quantity` varchar(225) DEFAULT NULL,
  `qty_taken` varchar(225) DEFAULT NULL,
  `color` varchar(225) DEFAULT NULL,
  `note` varchar(225) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `resident_form`
--

INSERT INTO `resident_form` (`id`, `hca_name`, `hca_no`, `form_type`, `date`, `time`, `type`, `quality`, `quantity`, `qty_taken`, `color`, `note`, `created_at`, `updated_at`) VALUES
(1, 'John Doe Ben', 'HCARSDT001', 'Bowel', '2023-09-25', '09:10', 'mediun', NULL, '3ml', '2ml', NULL, 'taken', '2023-09-25 08:13:37', '2023-09-25 08:13:37'),
(2, 'John Doe Ben', 'HCARSDT001', 'Fluid Intake', '2023-09-25', '09:33', 'mediun', NULL, '2ml', '2ml', NULL, 'taken', '2023-09-25 08:29:37', '2023-09-25 08:29:37'),
(3, 'John Doe Ben', 'HCARSDT001', 'Bowel', '2023-09-25', '09:41', 'mediun', '2', NULL, NULL, 'White', 'taken', '2023-09-25 08:36:22', '2023-09-25 08:36:22');

-- --------------------------------------------------------

--
-- Table structure for table `resident_note`
--

CREATE TABLE `resident_note` (
  `id` int(11) NOT NULL,
  `hca_name` varchar(225) NOT NULL,
  `hca_no` varchar(225) NOT NULL,
  `date` varchar(225) NOT NULL,
  `note` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `resident_note`
--

INSERT INTO `resident_note` (`id`, `hca_name`, `hca_no`, `date`, `note`, `created_at`, `updated_at`) VALUES
(1, 'John Doe Ben', 'HCARSDT001', '2023-09-25', 'all is well', '2023-09-25 03:09:18', '2023-09-25 04:09:18'),
(2, 'John Doe Ben', 'HCARSDT001', '2023-09-25', 'all is well in deed', '2023-09-25 03:11:19', '2023-09-25 04:11:19'),
(3, 'John Doe Ben', 'HCARSDT001', '2023-09-25', 'all is well in deed', '2023-09-25 03:13:54', '2023-09-25 04:13:54');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hca_worker`
--
ALTER TABLE `hca_worker`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `nurse`
--
ALTER TABLE `nurse`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `residents`
--
ALTER TABLE `residents`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `resident_form`
--
ALTER TABLE `resident_form`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `resident_note`
--
ALTER TABLE `resident_note`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `hca_worker`
--
ALTER TABLE `hca_worker`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `nurse`
--
ALTER TABLE `nurse`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `residents`
--
ALTER TABLE `residents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `resident_form`
--
ALTER TABLE `resident_form`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `resident_note`
--
ALTER TABLE `resident_note`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
