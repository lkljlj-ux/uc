-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 17, 2026 at 05:57 AM
-- Server version: 11.4.12-MariaDB
-- PHP Version: 8.4.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `d17393_suman`
--

-- --------------------------------------------------------

--
-- Table structure for table `system_name_data`
--

CREATE TABLE `system_name_data` (
  `Id` int(11) NOT NULL,
  `user_id` varchar(1000) DEFAULT NULL,
  `user_name` varchar(1000) DEFAULT NULL,
  `user_count` varchar(1000) DEFAULT NULL,
  `system_date` varchar(1000) DEFAULT NULL,
  `macId` varchar(500) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `system_name_data`
--

INSERT INTO `system_name_data` (`Id`, `user_id`, `user_name`, `user_count`, `system_date`, `macId`) VALUES
(2875, '351535873875', NULL, '97', '2026-06-14', 'CBG0Q13'),
(2876, '351535873875', NULL, '31', '2026-06-14', 'UNVPNSI735M1524890'),
(2877, '351535873875', NULL, '1', '2026-06-14', 'WX4FCZA'),
(2878, '351535873875', NULL, '1', '2026-06-14', '2723877505000'),
(2879, '351535873875', NULL, '49', '2026-06-15', '2723877505000'),
(2880, '351535873875', NULL, '30', '2026-06-15', 'UNVPNSI735M1524890'),
(2881, '351535873875', NULL, '34', '2026-06-15', 'WX4FCZA'),
(2882, '351535873875', NULL, '60', '2026-06-15', 'CBG0Q13'),
(2883, '351535873875', NULL, '60', '2026-06-16', 'CBG0Q13'),
(2884, '351535873875', NULL, '51', '2026-06-16', '2723877505000'),
(2885, '351535873875', NULL, '5', '2026-06-16', 'UNVPNSI735M1524890'),
(2886, '351535873875', NULL, '40', '2026-06-16', 'WX4FCZA'),
(2887, '351535873875', NULL, '58', '2026-06-17', 'CBG0Q13'),
(2888, '351535873875', NULL, '14', '2026-06-17', '2723877505000'),
(2889, '351535873875', NULL, '8', '2026-06-17', 'UNVPNSI735M1524890');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `system_name_data`
--
ALTER TABLE `system_name_data`
  ADD PRIMARY KEY (`Id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `system_name_data`
--
ALTER TABLE `system_name_data`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2890;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
