-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql107.infinityfree.com
-- Generation Time: May 20, 2025 at 03:42 AM
-- Server version: 10.6.19-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_38689249_hotel_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `Guest`
--

CREATE TABLE `Guest` (
  `guest_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `Guest`
--

INSERT INTO `Guest` (`guest_id`, `first_name`, `last_name`, `email`, `phone`, `address`, `created_at`) VALUES
(1, 'John Rico', 'Tolentino', '2220777@ub.edu.ph', '', '', '2025-05-02 05:43:57'),
(3, 'Kelly', 'Tolentino', '2220111@ub.edu.ph', '', '', '2025-05-02 13:40:39'),
(4, 'test', 'test', 'test1@sample.com', '', '', '2025-05-03 09:09:28'),
(5, 'test', 'test', 'test@sample.com', '', '', '2025-05-13 04:42:00'),
(6, 'John Rico', 'Tolentino', 'johnrico.tolentino.12@gmail.com', '', '', '2025-05-17 10:15:54'),
(7, 'ku', 'ri', 'kuri@example.com', '', '', '2025-05-20 06:30:30'),
(8, 'JR', 'T', '2220778@ub.edu.ph', '', '', '2025-05-20 07:14:08');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `Guest`
--
ALTER TABLE `Guest`
  ADD PRIMARY KEY (`guest_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `Guest`
--
ALTER TABLE `Guest`
  MODIFY `guest_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
