-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: mariadb
-- Generation Time: Nov 26, 2025 at 12:02 PM
-- Server version: 10.8.8-MariaDB-1:10.8.8+maria~ubu2204
-- PHP Version: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `qmc_db`
--
CREATE DATABASE IF NOT EXISTS `qmc_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `qmc_db`;

-- --------------------------------------------------------

--
-- Table structure for table `Audit_Log`
--

CREATE TABLE `Audit_Log` (
  `audit_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action_type` enum('select','insert','update','delete') NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` varchar(50) DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  `details` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `Audit_Log`
--

INSERT INTO `Audit_Log` (`audit_id`, `user_id`, `action_type`, `table_name`, `record_id`, `timestamp`, `details`) VALUES
(1, 1, 'select', 'Patient', 'P001', '2025-11-26 11:47:40', 'Doctor viewed patient record');

-- --------------------------------------------------------

--
-- Table structure for table `Department`
--

CREATE TABLE `Department` (
  `department_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `location` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `Department`
--

INSERT INTO `Department` (`department_id`, `name`, `location`) VALUES
(1, 'Dermatology', 'Block A'),
(2, 'Urology', 'Block B'),
(3, 'Orthopaedics', 'Block C');

-- --------------------------------------------------------

--
-- Table structure for table `Doctor`
--

CREATE TABLE `Doctor` (
  `doctor_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `staff_no` varchar(20) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `specialisation` varchar(100) DEFAULT NULL,
  `qualification` varchar(100) DEFAULT NULL,
  `pay` decimal(10,2) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `address_city` varchar(100) DEFAULT NULL,
  `address_street` varchar(100) DEFAULT NULL,
  `address_code` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `Doctor`
--

INSERT INTO `Doctor` (`doctor_id`, `user_id`, `staff_no`, `first_name`, `last_name`, `specialisation`, `qualification`, `pay`, `gender`, `address_city`, `address_street`, `address_code`) VALUES
(1, 1, 'NHS001', 'Mark', 'Ceards', 'Cardiology', 'MBBS', 75000.00, 'Male', 'Nottingham', 'Main St 20', 'NG1 5AA'),
(2, 2, 'NHS002', 'Sarah', 'Moorland', 'Neurology', 'PhD', 82000.00, 'Female', 'Nottingham', 'Hill Road 10', 'NG2 4BB'),
(3, 4, '111', 'DOC', 'CTOR', 'aaa', '11', 1100.00, 'Male', 'LDN', '11', 'sdad'),
(4, 5, '1111', '11111', '111', '111', '111', 111.00, '111', '111', '111', '111');

-- --------------------------------------------------------

--
-- Table structure for table `Parking_Permit`
--

CREATE TABLE `Parking_Permit` (
  `permit_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `car_registration` varchar(20) DEFAULT NULL,
  `permit_choice` enum('monthly','yearly') NOT NULL,
  `activation_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `rejection_reason` varchar(255) DEFAULT NULL,
  `permit_number` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `Parking_Permit`
--

INSERT INTO `Parking_Permit` (`permit_id`, `doctor_id`, `car_registration`, `permit_choice`, `activation_date`, `end_date`, `amount`, `status`, `rejection_reason`, `permit_number`) VALUES
(1, 1, 'ABC1234', 'monthly', NULL, NULL, 50.00, 'pending', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `Patient`
--

CREATE TABLE `Patient` (
  `patient_id` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `primary_phone` varchar(20) DEFAULT NULL,
  `emergency_phone` varchar(20) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `address_city` varchar(100) DEFAULT NULL,
  `address_street` varchar(100) DEFAULT NULL,
  `address_code` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `Patient`
--

INSERT INTO `Patient` (`patient_id`, `name`, `primary_phone`, `emergency_phone`, `gender`, `address_city`, `address_street`, `address_code`) VALUES
('P001', 'John Smith', '07111111111', '07999999999', 'Male', 'Nottingham', 'Forest Rd 5', 'NG3 1AA'),
('P002', 'Emily White', '07222222222', '07888888888', 'Female', 'Nottingham', 'City St 9', 'NG7 2BB');

-- --------------------------------------------------------

--
-- Table structure for table `Patient_Admission`
--

CREATE TABLE `Patient_Admission` (
  `admission_id` int(11) NOT NULL,
  `patient_id` varchar(20) NOT NULL,
  `ward_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `admission_date` date NOT NULL,
  `admission_time` time NOT NULL,
  `room_no` varchar(10) DEFAULT NULL,
  `bed_no` varchar(10) DEFAULT NULL,
  `status` enum('admitted','discharged') DEFAULT 'admitted'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `Patient_Admission`
--

INSERT INTO `Patient_Admission` (`admission_id`, `patient_id`, `ward_id`, `doctor_id`, `admission_date`, `admission_time`, `room_no`, `bed_no`, `status`) VALUES
(1, 'P001', 1, 1, '2025-01-10', '10:30:00', '101', '1A', 'admitted'),
(2, 'P002', 2, 2, '2025-01-11', '12:00:00', '202', '2B', 'admitted');

-- --------------------------------------------------------

--
-- Table structure for table `Patient_Test`
--

CREATE TABLE `Patient_Test` (
  `pt_id` int(11) NOT NULL,
  `patient_id` varchar(20) NOT NULL,
  `test_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `test_date` date DEFAULT NULL,
  `test_time` time DEFAULT NULL,
  `result` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `Patient_Test`
--

INSERT INTO `Patient_Test` (`pt_id`, `patient_id`, `test_id`, `doctor_id`, `test_date`, `test_time`, `result`) VALUES
(1, 'P001', 1, 1, '2025-01-12', '09:00:00', 'Normal'),
(2, 'P002', 2, 2, '2025-01-15', '14:30:00', 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `Test`
--

CREATE TABLE `Test` (
  `test_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `Test`
--

INSERT INTO `Test` (`test_id`, `name`, `category`, `description`) VALUES
(1, 'Blood Test', 'Blood', 'General blood analysis'),
(2, 'MRI Scan', 'MRI', 'Full body MRI scan');

-- --------------------------------------------------------

--
-- Table structure for table `UserAccount`
--

CREATE TABLE `UserAccount` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(50) NOT NULL,
  `role` enum('doctor','admin') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `UserAccount`
--

INSERT INTO `UserAccount` (`user_id`, `username`, `password`, `role`) VALUES
(1, 'mceards', 'lord456', 'doctor'),
(2, 'moorland', 'buzz48', 'doctor'),
(3, 'jelina', 'iron99', 'admin'),
(4, 'doct1', '123', 'doctor'),
(5, '111', '1111', 'doctor');

-- --------------------------------------------------------

--
-- Table structure for table `Ward`
--

CREATE TABLE `Ward` (
  `ward_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `total_patients` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `Ward`
--

INSERT INTO `Ward` (`ward_id`, `department_id`, `name`, `phone`, `location`, `total_patients`) VALUES
(1, 1, 'Derm-W1', '0115-100100', '1st Floor', 0),
(2, 2, 'Uro-W1', '0115-200200', '2nd Floor', 0),
(3, 3, 'Ortho-W1', '0115-300300', '3rd Floor', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `Audit_Log`
--
ALTER TABLE `Audit_Log`
  ADD PRIMARY KEY (`audit_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `Department`
--
ALTER TABLE `Department`
  ADD PRIMARY KEY (`department_id`);

--
-- Indexes for table `Doctor`
--
ALTER TABLE `Doctor`
  ADD PRIMARY KEY (`doctor_id`),
  ADD UNIQUE KEY `staff_no` (`staff_no`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `Parking_Permit`
--
ALTER TABLE `Parking_Permit`
  ADD PRIMARY KEY (`permit_id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `Patient`
--
ALTER TABLE `Patient`
  ADD PRIMARY KEY (`patient_id`);

--
-- Indexes for table `Patient_Admission`
--
ALTER TABLE `Patient_Admission`
  ADD PRIMARY KEY (`admission_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `ward_id` (`ward_id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `Patient_Test`
--
ALTER TABLE `Patient_Test`
  ADD PRIMARY KEY (`pt_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `test_id` (`test_id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `Test`
--
ALTER TABLE `Test`
  ADD PRIMARY KEY (`test_id`);

--
-- Indexes for table `UserAccount`
--
ALTER TABLE `UserAccount`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `Ward`
--
ALTER TABLE `Ward`
  ADD PRIMARY KEY (`ward_id`),
  ADD KEY `department_id` (`department_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `Audit_Log`
--
ALTER TABLE `Audit_Log`
  MODIFY `audit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `Department`
--
ALTER TABLE `Department`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `Doctor`
--
ALTER TABLE `Doctor`
  MODIFY `doctor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `Parking_Permit`
--
ALTER TABLE `Parking_Permit`
  MODIFY `permit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `Patient_Admission`
--
ALTER TABLE `Patient_Admission`
  MODIFY `admission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `Patient_Test`
--
ALTER TABLE `Patient_Test`
  MODIFY `pt_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `Test`
--
ALTER TABLE `Test`
  MODIFY `test_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `UserAccount`
--
ALTER TABLE `UserAccount`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `Ward`
--
ALTER TABLE `Ward`
  MODIFY `ward_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `Audit_Log`
--
ALTER TABLE `Audit_Log`
  ADD CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `UserAccount` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `Doctor`
--
ALTER TABLE `Doctor`
  ADD CONSTRAINT `doctor_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `UserAccount` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `Parking_Permit`
--
ALTER TABLE `Parking_Permit`
  ADD CONSTRAINT `parking_permit_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `Doctor` (`doctor_id`) ON DELETE CASCADE;

--
-- Constraints for table `Patient_Admission`
--
ALTER TABLE `Patient_Admission`
  ADD CONSTRAINT `patient_admission_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `Patient` (`patient_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `patient_admission_ibfk_2` FOREIGN KEY (`ward_id`) REFERENCES `Ward` (`ward_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `patient_admission_ibfk_3` FOREIGN KEY (`doctor_id`) REFERENCES `Doctor` (`doctor_id`) ON DELETE CASCADE;

--
-- Constraints for table `Patient_Test`
--
ALTER TABLE `Patient_Test`
  ADD CONSTRAINT `patient_test_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `Patient` (`patient_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `patient_test_ibfk_2` FOREIGN KEY (`test_id`) REFERENCES `Test` (`test_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `patient_test_ibfk_3` FOREIGN KEY (`doctor_id`) REFERENCES `Doctor` (`doctor_id`) ON DELETE CASCADE;

--
-- Constraints for table `Ward`
--
ALTER TABLE `Ward`
  ADD CONSTRAINT `ward_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `Department` (`department_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
