-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Jan 10, 2026 at 10:18 PM
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
-- Database: `re_sghms`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointment`
--

CREATE TABLE `appointment` (
  `appointmentID` varchar(10) NOT NULL,
  `appointmentDate` date NOT NULL,
  `appointmentTime` time(6) NOT NULL,
  `reason` varchar(500) NOT NULL,
  `status` varchar(50) NOT NULL,
  `patientID` varchar(10) NOT NULL,
  `doctorID` varchar(10) NOT NULL,
  `diagnosedDate` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointment`
--

INSERT INTO `appointment` (`appointmentID`, `appointmentDate`, `appointmentTime`, `reason`, `status`, `patientID`, `doctorID`) VALUES
('APT001', '2026-01-10', '16:00:00.000000', 'General consultation', 'Confirmed', 'PAT002', 'DOC001'),
('APT002', '2026-01-10', '16:00:00.000000', 'medical checkup', 'Confirmed', 'PAT002', 'DOC001'),
('APT003', '2026-01-10', '15:00:00.000000', 'x-ray', 'Pending', 'PAT001', 'DOC002'),
('APT004', '2026-01-23', '08:00:00.000000', 'medical', 'Pending', 'PAT001', 'DOC001');

-- --------------------------------------------------------

--
-- Table structure for table `doctor`
--

CREATE TABLE `doctor` (
  `doctorID` varchar(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  `specialization` varchar(100) NOT NULL,
  `contactDetails` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctor`
--

INSERT INTO `doctor` (`doctorID`, `name`, `specialization`, `contactDetails`) VALUES
('DOC001', 'Dr. Rizal Shah Alimun', 'Cardiology', '0163556694'),
('DOC002', 'Dr. Nilam', 'Orthopedics', '0172342728'),
('DOC003', 'Dr Alima', 'Orthopedics', '01222223456');

-- --------------------------------------------------------

--
-- Table structure for table `doctor_schedule`
--

CREATE TABLE `doctor_schedule` (
  `scheduleID` int(11) NOT NULL,
  `doctorID` varchar(10) NOT NULL,
  `dayOfWeek` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `startTime` time NOT NULL,
  `endTime` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patient`
--

CREATE TABLE `patient` (
  `patientID` varchar(10) NOT NULL,
  `name` varchar(500) NOT NULL,
  `age` varchar(100) NOT NULL,
  `gender` varchar(40) NOT NULL,
  `contactNumber` varchar(50) NOT NULL,
  `medicalHistory` varchar(500) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patient`
--

INSERT INTO `patient` (`patientID`, `name`, `age`, `gender`, `contactNumber`, `medicalHistory`) VALUES
('PAT001', 'Anis', '24', 'Female', '0163556695', 'Asthma'),
('PAT002', 'Nureen', '24', 'Female', '0133137994', 'Eczema, Asthma'),
('PAT003', 'Wani', '34', 'Female', '0133137009', 'Asthma');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `username` varchar(30) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(50) NOT NULL,
  `role` varchar(30) NOT NULL,
  `fullname` varchar(255) DEFAULT NULL,
  `refID` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`username`, `password`, `email`, `role`, `fullname`, `refID`) VALUES
('testuser', '$2y$10$U9XkR9o/Mpk/Gmq.y3fS/.u7/l7m2.jI/8k8h3O.6u4k2g2v6f2uG', 'test@mail.com', 'patient', 'Anis', 'PAT001'),
('aina123', '$2y$10$/5Z9i28EyM9bbJKQTuPO8OiEHvXE7twR01B/rFwqApiIcqXCLmYPS', 'aina@mail.com', 'patient', 'Nureen', 'PAT002'),
('farah123', '$2y$10$VGoE9JToQGaLtQcMW3R9Ee69XRrKfjbXYCiJRbvmRztKRBZyUFb4C', 'farah@mail.com', 'doctor', 'Dr. Rizal Shah Alimun', 'DOC001'),
('wati123', '$2y$10$ymg2mLR8jV9u/z63v4cLkOGqhidGl.o5FR9x7M3WTv1YiYAK9ElgO', 'wati@mail.com', 'staff', 'wati', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `prescription`
--

CREATE TABLE `prescription` (
  `prescriptionID` varchar(10) NOT NULL,
  `patientID` varchar(10) NOT NULL,
  `doctorID` varchar(10) NOT NULL,
  `appointmentID` varchar(10) DEFAULT NULL,
  `medicineName` varchar(200) NOT NULL,
  `dosage` varchar(100) NOT NULL,
  `duration` varchar(100) NOT NULL,
  `instructions` varchar(500) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Pending Preparation',
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prescription`
--

INSERT INTO `prescription` (`prescriptionID`, `patientID`, `doctorID`, `appointmentID`, `medicineName`, `dosage`, `duration`, `instructions`, `status`) VALUES
('RX001', 'PAT001', 'DOC001', NULL, 'Salbutamol Inhaler', '2 puffs', '30 days', 'Use when experiencing breathing difficulty.', 'Ready for Collection'),
('RX002', 'PAT002', 'DOC001', NULL, 'Hydrocortisone Cream', 'Apply thin layer', '14 days', 'Apply to affected skin twice daily.', 'Pending Preparation');

-- --------------------------------------------------------

--
-- Table structure for table `notification`
--

CREATE TABLE `notification` (
  `notificationID` varchar(10) NOT NULL,
  `patientID` varchar(10) NOT NULL,
  `appointmentID` varchar(10) DEFAULT NULL,
  `messageBody` varchar(500) NOT NULL,
  `isRead` tinyint(1) NOT NULL DEFAULT 0,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notification`
--

INSERT INTO `notification` (`notificationID`, `patientID`, `appointmentID`, `messageBody`, `isRead`) VALUES
('NTF001', 'PAT001', 'APT004', 'Reminder: Appointment with Dr. Rizal Shah Alimun on 23/01/2026 at 08:00 AM. Please confirm or cancel.', 0),
('NTF002', 'PAT001', 'APT003', 'Your appointment status is pending confirmation.', 1);

-- --------------------------------------------------------

--
-- Sample doctor schedule data
--

INSERT INTO `doctor_schedule` (`scheduleID`, `doctorID`, `dayOfWeek`, `startTime`, `endTime`) VALUES
(1, 'DOC001', 'Monday', '08:00:00', '12:00:00'),
(2, 'DOC001', 'Wednesday', '14:00:00', '17:00:00'),
(3, 'DOC002', 'Tuesday', '09:00:00', '13:00:00'),
(4, 'DOC002', 'Thursday', '09:00:00', '13:00:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointment`
--
ALTER TABLE `appointment`
  ADD PRIMARY KEY (`appointmentID`);

--
-- Indexes for table `doctor`
--
ALTER TABLE `doctor`
  ADD PRIMARY KEY (`doctorID`);

--
-- Indexes for table `doctor_schedule`
--
ALTER TABLE `doctor_schedule`
  ADD PRIMARY KEY (`scheduleID`),
  ADD KEY `doctorID` (`doctorID`);

--
-- Indexes for table `patient`
--
ALTER TABLE `patient`
  ADD PRIMARY KEY (`patientID`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `prescription`
--
ALTER TABLE `prescription`
  ADD PRIMARY KEY (`prescriptionID`);

--
-- Indexes for table `notification`
--
ALTER TABLE `notification`
  ADD PRIMARY KEY (`notificationID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `doctor_schedule`
--
ALTER TABLE `doctor_schedule`
  MODIFY `scheduleID` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
