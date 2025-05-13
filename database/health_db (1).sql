-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 12, 2025 at 05:39 PM
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
-- Database: `health_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL,
  `patient_id` int(11) UNSIGNED NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `appointment_datetime` datetime NOT NULL,
  `reason_for_visit` text DEFAULT NULL,
  `status` enum('Scheduled','Completed','Cancelled','No-Show','Rejected','Requested') DEFAULT 'Scheduled',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `billing_records`
--

CREATE TABLE `billing_records` (
  `billing_id` int(10) UNSIGNED NOT NULL,
  `patient_id` int(10) UNSIGNED NOT NULL,
  `record_id` int(10) UNSIGNED DEFAULT NULL,
  `billing_datetime` timestamp NOT NULL DEFAULT current_timestamp(),
  `description` text NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_status` enum('Pending','Paid','Partial','Cancelled') DEFAULT 'Pending',
  `payment_date` datetime DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `invoice_number` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `doctor_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `specialization_id` int(11) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`doctor_id`, `user_id`, `first_name`, `last_name`, `specialization_id`, `contact_number`, `email`, `address`, `created_at`, `updated_at`) VALUES
(1, 19, 'John', 'Smith', 1, '039988999', '', '123 Main St', '2025-05-11 18:03:08', '2025-05-11 18:03:38');

-- --------------------------------------------------------

--
-- Table structure for table `doctor_availability`
--

CREATE TABLE `doctor_availability` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `time_slot` varchar(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctor_availability_exceptions`
--

CREATE TABLE `doctor_availability_exceptions` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `exception_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 0,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctor_availability_exceptions`
--

INSERT INTO `doctor_availability_exceptions` (`id`, `doctor_id`, `exception_date`, `start_time`, `end_time`, `is_available`, `notes`) VALUES
(14, 1, '2025-05-13', '07:00:00', '19:00:00', 1, NULL),
(20, 1, '2025-05-12', '07:00:00', '11:00:00', 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `doctor_schedule`
--

CREATE TABLE `doctor_schedule` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE `logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `event_type` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `logs`
--

INSERT INTO `logs` (`log_id`, `user_id`, `event_type`, `description`, `timestamp`) VALUES
(1, 14, 'login', 'jonard logged in as patient.', '2025-05-11 12:48:56'),
(2, 14, 'login', 'jonard logged in as patient.', '2025-05-11 13:03:27'),
(3, 14, 'login', 'jonard logged in as patient.', '2025-05-11 13:03:47'),
(4, 14, 'login', 'jonard logged in as patient.', '2025-05-11 13:04:03'),
(5, 14, 'login', 'jonard logged in as patient.', '2025-05-11 13:04:07'),
(6, 14, 'login', 'jonard logged in as patient.', '2025-05-11 13:04:14'),
(7, 14, 'login', 'jonard logged in as patient.', '2025-05-11 13:04:48'),
(8, 15, 'login', 'c logged in as nurse.', '2025-05-11 13:18:46'),
(9, 15, 'login', 'c logged in as nurse.', '2025-05-11 13:19:58'),
(10, 15, 'login', 'c logged in as nurse.', '2025-05-11 13:20:47'),
(11, 16, 'login', 'nardjo logged in as doctor.', '2025-05-11 14:55:17'),
(12, 16, 'login', 'nardjo logged in as doctor.', '2025-05-11 14:57:48'),
(13, 16, 'login', 'nardjo logged in as doctor.', '2025-05-11 15:00:44'),
(14, 16, 'login', 'nardjo logged in as doctor.', '2025-05-11 15:01:39'),
(15, 16, 'login', 'nardjo logged in as doctor.', '2025-05-11 15:02:14'),
(16, 16, 'login', 'nardjo logged in as doctor.', '2025-05-11 15:04:17'),
(17, 16, 'login', 'nardjo logged in as doctor.', '2025-05-11 15:08:51'),
(18, 16, 'login', 'nardjo logged in as doctor.', '2025-05-11 15:16:46'),
(19, 16, 'login', 'nardjo logged in as doctor.', '2025-05-11 15:20:06'),
(20, 16, 'login', 'nardjo logged in as doctor.', '2025-05-11 15:20:57'),
(21, 17, 'login', 'asd logged in as patient.', '2025-05-11 15:31:21'),
(22, 16, 'login', 'nardjo logged in as doctor.', '2025-05-11 15:48:47'),
(23, 17, 'login', 'asd logged in as nurse.', '2025-05-11 15:49:09'),
(24, 17, 'login', 'asd logged in as nurse.', '2025-05-11 15:52:07'),
(25, 17, 'login', 'asd logged in as nurse.', '2025-05-11 15:57:10'),
(26, 17, 'login', 'asd logged in as nurse.', '2025-05-11 16:03:28'),
(27, 17, 'login', 'asd logged in as nurse.', '2025-05-11 16:07:23'),
(28, 17, 'login', 'asd logged in as nurse.', '2025-05-11 16:07:56'),
(29, 17, 'login', 'asd logged in as nurse.', '2025-05-11 16:08:27'),
(30, 17, 'login', 'asd logged in as nurse.', '2025-05-11 16:09:25'),
(31, 17, 'login', 'asd logged in as nurse.', '2025-05-11 16:22:45'),
(32, 17, 'login', 'asd logged in as nurse.', '2025-05-11 16:28:33'),
(33, 17, 'login', 'asd logged in.', '2025-05-11 16:41:38'),
(34, 17, 'login', 'asd logged in.', '2025-05-11 16:42:09'),
(35, 17, 'login', 'asd logged in.', '2025-05-11 16:42:19'),
(36, 17, 'login', 'asd logged in.', '2025-05-11 16:42:42'),
(37, 17, 'login', 'asd logged in.', '2025-05-11 16:42:45'),
(38, 17, 'login', 'asd logged in.', '2025-05-11 16:42:58'),
(39, 18, 'login', 'doc logged in.', '2025-05-11 17:46:32'),
(40, 17, 'login', 'asd logged in.', '2025-05-11 17:46:45'),
(41, 16, 'login', 'nardjo logged in.', '2025-05-11 17:47:22'),
(42, 15, 'login', 'c logged in.', '2025-05-11 17:47:31'),
(43, 15, 'login', 'c logged in.', '2025-05-11 17:47:46'),
(44, 15, 'login', 'c logged in.', '2025-05-11 17:48:00'),
(45, 15, 'login', 'c logged in.', '2025-05-11 17:48:07'),
(46, 15, 'login', 'c logged in.', '2025-05-11 17:48:39'),
(47, 15, 'login', 'c logged in.', '2025-05-11 17:48:43'),
(48, 15, 'login', 'c logged in.', '2025-05-11 17:48:47'),
(49, 15, 'login', 'c logged in.', '2025-05-11 17:48:49'),
(50, 15, 'login', 'c logged in.', '2025-05-11 17:50:55'),
(51, 17, 'login', 'asd logged in.', '2025-05-11 18:24:03');

-- --------------------------------------------------------

--
-- Table structure for table `medicalrecords`
--

CREATE TABLE `medicalrecords` (
  `record_id` int(10) UNSIGNED NOT NULL,
  `patient_id` int(10) UNSIGNED NOT NULL,
  `record_datetime` timestamp NOT NULL DEFAULT current_timestamp(),
  `diagnosis` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `treatment` text DEFAULT NULL,
  `prescribed_medications` text DEFAULT NULL,
  `test_results` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nurses`
--

CREATE TABLE `nurses` (
  `nurse_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `patient_id` int(11) UNSIGNED NOT NULL,
  `user_id_fk` int(11) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `description_id` int(11) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patient_descriptions`
--

CREATE TABLE `patient_descriptions` (
  `id` int(11) UNSIGNED NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `address` varchar(255) DEFAULT NULL,
  `gender` varchar(50) DEFAULT NULL,
  `medical_record_number` varchar(100) DEFAULT NULL,
  `insurance_provider` varchar(100) DEFAULT NULL,
  `insurance_policy_number` varchar(100) DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`) VALUES
(3, 'admin'),
(2, 'doctor'),
(1, 'nurse'),
(4, 'patient');

-- --------------------------------------------------------

--
-- Table structure for table `specializations`
--

CREATE TABLE `specializations` (
  `specialization_id` int(11) NOT NULL,
  `specialization_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `specializations`
--

INSERT INTO `specializations` (`specialization_id`, `specialization_name`, `created_at`, `updated_at`) VALUES
(1, 'Cardiologist', '2025-05-11 14:32:55', '2025-05-11 14:32:55'),
(2, 'Dermatologist', '2025-05-11 14:32:55', '2025-05-11 14:32:55'),
(3, 'Pediatrician', '2025-05-11 14:32:55', '2025-05-11 14:32:55'),
(4, 'General Practitioner', '2025-05-11 14:32:55', '2025-05-11 14:32:55');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role_id` int(11) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `created_at`, `updated_at`, `first_name`, `last_name`, `phone_number`, `password_hash`, `role_id`, `last_login`) VALUES
(15, NULL, 'nurse@test.com', '2025-05-11 13:18:41', '2025-05-11 17:50:55', 'c', 'c', '262626262', '$2y$10$FK/po5Dv/LKFZg3SQTz/OetBmxTobzVYJgyzh7i78U4VgscC54ct.', 1, '2025-05-11 17:50:55'),
(16, NULL, 'nardjo@gmail.com', '2025-05-11 14:55:11', '2025-05-11 17:47:22', 'nardjo', 'doe', '915933296', '$2y$10$efO1Nd99bexKms5sr.LGW.hrbjT4DrbYUWoEloB3x6m9Ki.akY4E2', 2, '2025-05-11 17:47:22'),
(17, NULL, 'asd@test.com', '2025-05-11 15:31:16', '2025-05-11 18:24:03', 'asd', 'asd', 'asd@test.com', '$2y$10$qrbrgocSBNVkISaHvKr1y.ZMqzpl7Lm/etcc7aBoP7amwdpSJc2cG', 1, '2025-05-11 18:24:03'),
(19, 'dr_smith', 'john.smith@clinic.com', '2025-05-11 18:03:08', '2025-05-11 18:03:08', 'John', 'Smith', '555-1234', '$2y$10$hashed_password_here', 2, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`appointment_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `appointments_ibfk_1` (`patient_id`);

--
-- Indexes for table `billing_records`
--
ALTER TABLE `billing_records`
  ADD PRIMARY KEY (`billing_id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `record_id` (`record_id`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`doctor_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `specialization_id` (`specialization_id`);

--
-- Indexes for table `doctor_availability`
--
ALTER TABLE `doctor_availability`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_slot` (`doctor_id`,`date`,`time_slot`);

--
-- Indexes for table `doctor_availability_exceptions`
--
ALTER TABLE `doctor_availability_exceptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `doctor_schedule`
--
ALTER TABLE `doctor_schedule`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_schedule` (`doctor_id`,`day_of_week`);

--
-- Indexes for table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `medicalrecords`
--
ALTER TABLE `medicalrecords`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `nurses`
--
ALTER TABLE `nurses`
  ADD PRIMARY KEY (`nurse_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`patient_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `description_id` (`description_id`),
  ADD KEY `description_id_2` (`description_id`);

--
-- Indexes for table `patient_descriptions`
--
ALTER TABLE `patient_descriptions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `specializations`
--
ALTER TABLE `specializations`
  ADD PRIMARY KEY (`specialization_id`),
  ADD UNIQUE KEY `specialization_name` (`specialization_name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `fk_user_role` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `billing_records`
--
ALTER TABLE `billing_records`
  MODIFY `billing_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `doctor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `doctor_availability`
--
ALTER TABLE `doctor_availability`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `doctor_availability_exceptions`
--
ALTER TABLE `doctor_availability_exceptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `doctor_schedule`
--
ALTER TABLE `doctor_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `medicalrecords`
--
ALTER TABLE `medicalrecords`
  MODIFY `record_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `nurses`
--
ALTER TABLE `nurses`
  MODIFY `nurse_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `patient_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patient_descriptions`
--
ALTER TABLE `patient_descriptions`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `specializations`
--
ALTER TABLE `specializations`
  MODIFY `specialization_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`);

--
-- Constraints for table `billing_records`
--
ALTER TABLE `billing_records`
  ADD CONSTRAINT `billing_records_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `billing_records_ibfk_2` FOREIGN KEY (`record_id`) REFERENCES `medicalrecords` (`record_id`) ON DELETE SET NULL;

--
-- Constraints for table `doctors`
--
ALTER TABLE `doctors`
  ADD CONSTRAINT `doctors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `doctors_ibfk_2` FOREIGN KEY (`specialization_id`) REFERENCES `specializations` (`specialization_id`);

--
-- Constraints for table `doctor_availability`
--
ALTER TABLE `doctor_availability`
  ADD CONSTRAINT `doctor_availability_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON DELETE CASCADE;

--
-- Constraints for table `doctor_availability_exceptions`
--
ALTER TABLE `doctor_availability_exceptions`
  ADD CONSTRAINT `doctor_availability_exceptions_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON DELETE CASCADE;

--
-- Constraints for table `doctor_schedule`
--
ALTER TABLE `doctor_schedule`
  ADD CONSTRAINT `doctor_schedule_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON DELETE CASCADE;

--
-- Constraints for table `medicalrecords`
--
ALTER TABLE `medicalrecords`
  ADD CONSTRAINT `medicalrecords_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE;

--
-- Constraints for table `nurses`
--
ALTER TABLE `nurses`
  ADD CONSTRAINT `nurses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `fk_patient_description` FOREIGN KEY (`description_id`) REFERENCES `patient_descriptions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `patients_ibfk_1` FOREIGN KEY (`description_id`) REFERENCES `patient_descriptions` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_user_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
