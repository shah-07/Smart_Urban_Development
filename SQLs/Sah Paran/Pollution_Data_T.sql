-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 19, 2025 at 06:49 AM
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
-- Database: `smart_city`
--

-- --------------------------------------------------------

--
-- Table structure for table `Pollution_Data_T`
--

CREATE TABLE `Pollution_Data_T` (
  `sensorID` int(11) NOT NULL,
  `timestamp` datetime NOT NULL,
  `airQuality` varchar(20) DEFAULT NULL,
  `noiseLevel` decimal(5,2) DEFAULT NULL,
  `pm25Level` decimal(5,2) DEFAULT NULL,
  `noXLevel` decimal(5,2) DEFAULT NULL,
  `co2Level` decimal(6,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `Pollution_Data_T`
--
ALTER TABLE `Pollution_Data_T`
  ADD PRIMARY KEY (`sensorID`,`timestamp`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `Pollution_Data_T`
--
ALTER TABLE `Pollution_Data_T`
  ADD CONSTRAINT `pollution_data_t_ibfk_1` FOREIGN KEY (`sensorID`) REFERENCES `iot_sensor_t` (`sensorID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
