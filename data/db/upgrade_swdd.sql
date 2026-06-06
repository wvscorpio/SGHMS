-- Run this on an existing re_sghms database to add SWDD modules

ALTER TABLE `user` ADD COLUMN IF NOT EXISTS `refID` varchar(10) DEFAULT NULL;

CREATE TABLE IF NOT EXISTS `prescription` (
  `prescriptionID` varchar(10) NOT NULL,
  `patientID` varchar(10) NOT NULL,
  `doctorID` varchar(10) NOT NULL,
  `medicineName` varchar(200) NOT NULL,
  `dosage` varchar(100) NOT NULL,
  `duration` varchar(100) NOT NULL,
  `instructions` varchar(500) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Active',
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`prescriptionID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `notification` (
  `notificationID` varchar(10) NOT NULL,
  `patientID` varchar(10) NOT NULL,
  `appointmentID` varchar(10) DEFAULT NULL,
  `messageBody` varchar(500) NOT NULL,
  `isRead` tinyint(1) NOT NULL DEFAULT 0,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`notificationID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

UPDATE `user` SET `refID` = 'PAT001' WHERE `username` = 'testuser';
UPDATE `user` SET `refID` = 'PAT002' WHERE `username` = 'aina123';
UPDATE `user` SET `refID` = 'DOC001' WHERE `username` = 'farah123';
