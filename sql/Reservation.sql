-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql107.infinityfree.com
-- Generation Time: May 20, 2025 at 03:40 AM
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
-- Table structure for table `Reservation`
--

CREATE TABLE `Reservation` (
  `reservation_id` int(11) NOT NULL,
  `guest_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `check_in` date NOT NULL,
  `check_out` date NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('confirmed','checked_in','checked_out','cancelled','pending') DEFAULT 'confirmed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `Reservation`
--

INSERT INTO `Reservation` (`reservation_id`, `guest_id`, `room_id`, `admin_id`, `check_in`, `check_out`, `total_price`, `status`) VALUES
(25, 5, 2, NULL, '2025-05-20', '2025-05-21', '111.00', 'checked_out'),
(26, 6, 1, NULL, '2025-05-19', '2025-05-20', '350.00', 'checked_out'),
(28, 6, 4, NULL, '2025-05-20', '2025-05-22', '222.00', 'checked_out'),
(29, 5, 5, NULL, '2025-05-19', '2025-05-21', '222.00', 'checked_out'),
(30, 5, 2, NULL, '2025-05-19', '2025-05-20', '111.00', 'checked_out'),
(31, 5, 4, NULL, '2025-05-19', '2025-05-21', '222.00', 'checked_in'),
(32, 5, 5, NULL, '2025-05-20', '2025-05-21', '111.00', 'checked_out'),
(33, 5, 2, NULL, '2025-05-20', '2025-05-21', '111.00', 'checked_in'),
(34, 5, 5, NULL, '2025-05-19', '2025-05-21', '222.00', 'pending'),
(35, 7, 1, NULL, '2025-05-20', '2025-05-21', '299.00', 'checked_in');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `Reservation`
--
ALTER TABLE `Reservation`
  ADD PRIMARY KEY (`reservation_id`),
  ADD KEY `guest_id` (`guest_id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `Reservation`
--
ALTER TABLE `Reservation`
  MODIFY `reservation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `Reservation`
--
ALTER TABLE `Reservation`
  ADD CONSTRAINT `fk_reservation_admin` FOREIGN KEY (`admin_id`) REFERENCES `Admin` (`admin_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_reservation_guest` FOREIGN KEY (`guest_id`) REFERENCES `Guest` (`guest_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reservation_room` FOREIGN KEY (`room_id`) REFERENCES `Room` (`room_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
