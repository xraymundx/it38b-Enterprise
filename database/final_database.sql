-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 14, 2025 at 01:59 AM
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
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `appointment_datetime` datetime NOT NULL,
  `reason_for_visit` text DEFAULT NULL,
  `status` enum('Requested','Scheduled','Completed','No Show','Cancelled') DEFAULT 'Scheduled',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`appointment_id`, `patient_id`, `doctor_id`, `appointment_datetime`, `reason_for_visit`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(4, 1, 1, '2025-05-13 09:00:00', 'asd', 'Completed', 'asd', '2025-05-13 05:14:53', '2025-05-13 06:00:41'),
(5, 1, 1, '2025-05-13 10:00:00', 'd', 'Requested', 'd', '2025-05-13 07:06:01', '2025-05-13 07:06:12'),
(6, 2, 1, '2025-05-13 11:00:00', 'Follow-up', 'Completed', 'ASD', '2025-05-13 13:47:41', '2025-05-13 14:10:01');

--
-- Triggers `appointments`
--
DELIMITER $$
CREATE TRIGGER `appointments_after_insert` AFTER INSERT ON `appointments` FOR EACH ROW BEGIN
    INSERT INTO audit_log (user_id, event_type, table_name, row_id, new_values)
    VALUES (NULL, -- Capture user_id if possible
            'INSERT',
            'appointments',
            NEW.appointment_id,
            JSON_OBJECT('appointment_id', NEW.appointment_id, 'patient_id', NEW.patient_id, 'doctor_id', NEW.doctor_id, 'appointment_datetime', NEW.appointment_datetime, 'reason_for_visit', NEW.reason_for_visit, 'status', NEW.status, 'notes', NEW.notes, 'created_at', NEW.created_at, 'updated_at', NEW.updated_at));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `appointments_after_update` AFTER UPDATE ON `appointments` FOR EACH ROW BEGIN
    INSERT INTO audit_log (user_id, event_type, table_name, row_id, old_values, new_values)
    VALUES (NULL, -- Capture user_id if possible
            'UPDATE',
            'appointments',
            OLD.appointment_id,
            JSON_OBJECT('appointment_id', OLD.appointment_id, 'patient_id', OLD.patient_id, 'doctor_id', OLD.doctor_id, 'appointment_datetime', OLD.appointment_datetime, 'reason_for_visit', OLD.reason_for_visit, 'status', OLD.status, 'notes', OLD.notes, 'created_at', OLD.created_at, 'updated_at', OLD.updated_at),
            JSON_OBJECT('appointment_id', NEW.appointment_id, 'patient_id', NEW.patient_id, 'doctor_id', NEW.doctor_id, 'appointment_datetime', NEW.appointment_datetime, 'reason_for_visit', NEW.reason_for_visit, 'status', NEW.status, 'notes', NEW.notes, 'created_at', NEW.created_at, 'updated_at', NEW.updated_at));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `appointments_before_delete` BEFORE DELETE ON `appointments` FOR EACH ROW BEGIN
    INSERT INTO audit_log (user_id, event_type, table_name, row_id, old_values)
    VALUES (NULL, -- Capture user_id if possible
            'DELETE',
            'appointments',
            OLD.appointment_id,
            JSON_OBJECT('appointment_id', OLD.appointment_id, 'patient_id', OLD.patient_id, 'doctor_id', OLD.doctor_id, 'appointment_datetime', OLD.appointment_datetime, 'reason_for_visit', OLD.reason_for_visit, 'status', OLD.status, 'notes', OLD.notes, 'created_at', OLD.created_at, 'updated_at', OLD.updated_at));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `log_id` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL,
  `event_type` varchar(50) NOT NULL,
  `table_name` varchar(255) NOT NULL,
  `row_id` int(11) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`log_id`, `timestamp`, `user_id`, `event_type`, `table_name`, `row_id`, `old_values`, `new_values`, `description`) VALUES
(1, '2025-05-13 22:34:06', 7, 'UPDATE', 'users', 7, '{\"user_id\": 7, \"username\": \"adminadmin\", \"email\": \"adminadmin@adminadmin.com\", \"phone_number\": \"adminadmin@adminadmi\", \"first_name\": \"adminadmin\", \"last_name\": \"adminadmin\", \"role_id\": 1, \"created_at\": \"2025-05-14 00:12:34\", \"updated_at\": \"2025-05-14 00:12:37\", \"last_login\": \"2025-05-14 00:12:37\"}', '{\"user_id\": 7, \"username\": \"adminadmin\", \"email\": \"adminadmin@adminadmin.com\", \"phone_number\": \"adminadmin@adminadmi\", \"first_name\": \"adminadmin\", \"last_name\": \"adminadmin\", \"role_id\": 1, \"created_at\": \"2025-05-14 00:12:34\", \"updated_at\": \"2025-05-14 06:34:06\", \"last_login\": \"2025-05-14 06:34:06\"}', NULL),
(2, '2025-05-13 23:22:16', 8, 'INSERT', 'users', 8, NULL, '{\"user_id\": 8, \"username\": \"jonard\", \"email\": \"jonard@gmail.com\", \"phone_number\": \"915933296\", \"first_name\": \"jonard\", \"last_name\": \"pinalas\", \"role_id\": 3, \"created_at\": \"2025-05-14 07:22:16\", \"updated_at\": \"2025-05-14 07:22:16\", \"last_login\": null}', NULL),
(3, '2025-05-13 23:22:16', 8, 'INSERT', 'nurses', 1, NULL, '{\"nurse_id\": 1, \"user_id\": 8, \"created_at\": \"2025-05-14 07:22:16\", \"updated_at\": \"2025-05-14 07:22:16\"}', NULL),
(4, '2025-05-13 23:36:27', NULL, 'DELETE', 'appointments', 7, '{\"appointment_id\": 7, \"patient_id\": 2, \"doctor_id\": 1, \"appointment_datetime\": \"2025-05-13 23:43:00\", \"reason_for_visit\": \"Regular Checkup\", \"status\": \"Cancelled\", \"notes\": \"asd\", \"created_at\": \"2025-05-13 23:42:36\", \"updated_at\": \"2025-05-13 23:42:41\"}', NULL, NULL),
(7, '2025-05-13 23:56:14', 10, 'INSERT', 'users', 10, NULL, '{\"user_id\": 10, \"username\": \"wood\", \"email\": \"wood@wood.com\", \"phone_number\": null, \"first_name\": \"wood\", \"last_name\": \"mahogany\", \"role_id\": 2, \"created_at\": \"2025-05-14 07:56:14\", \"updated_at\": \"2025-05-14 07:56:14\", \"last_login\": null}', NULL),
(8, '2025-05-13 23:56:14', 10, 'INSERT', 'doctors', 3, NULL, '{\"doctor_id\": 3, \"user_id\": 10, \"specialization_id\": 3, \"address\": null, \"created_at\": \"2025-05-14 07:56:14\", \"updated_at\": \"2025-05-14 07:56:14\"}', NULL),
(9, '2025-05-13 23:57:40', 10, 'UPDATE', 'users', 10, '{\"user_id\": 10, \"username\": \"wood\", \"email\": \"wood@wood.com\", \"phone_number\": null, \"first_name\": \"wood\", \"last_name\": \"mahogany\", \"role_id\": 2, \"created_at\": \"2025-05-14 07:56:14\", \"updated_at\": \"2025-05-14 07:56:14\", \"last_login\": null}', '{\"user_id\": 10, \"username\": \"wood\", \"email\": \"wood@wood.com\", \"phone_number\": null, \"first_name\": \"wood\", \"last_name\": \"mahogany\", \"role_id\": 2, \"created_at\": \"2025-05-14 07:56:14\", \"updated_at\": \"2025-05-14 07:57:40\", \"last_login\": null}', NULL),
(10, '2025-05-13 23:57:40', 10, 'UPDATE', 'doctors', 3, '{\"doctor_id\": 3, \"user_id\": 10, \"specialization_id\": 3, \"address\": null, \"created_at\": \"2025-05-14 07:56:14\", \"updated_at\": \"2025-05-14 07:56:14\"}', '{\"doctor_id\": 3, \"user_id\": 10, \"specialization_id\": 3, \"address\": null, \"created_at\": \"2025-05-14 07:56:14\", \"updated_at\": \"2025-05-14 07:57:40\"}', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `billingrecords`
--

CREATE TABLE `billingrecords` (
  `bill_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `patient_id` int(11) NOT NULL,
  `billing_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `description` text DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_status` varchar(20) DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `record_id` int(11) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_date` datetime DEFAULT NULL,
  `invoice_number` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `billingrecords`
--

INSERT INTO `billingrecords` (`bill_id`, `appointment_id`, `patient_id`, `billing_date`, `description`, `amount`, `payment_status`, `created_at`, `updated_at`, `record_id`, `payment_method`, `payment_date`, `invoice_number`, `notes`) VALUES
(1, 4, 1, '2025-05-13 00:14:05', 'dd', 12.00, 'Paid', '2025-05-13 06:14:05', '2025-05-13 06:14:05', 3, 'Cash', NULL, 'ad', 'dd'),
(2, 6, 2, '2025-05-13 08:10:20', 'd', 123.00, 'Paid', '2025-05-13 14:10:20', '2025-05-13 14:10:20', 4, 'Cash', NULL, 'a', 'asd');

--
-- Triggers `billingrecords`
--
DELIMITER $$
CREATE TRIGGER `billingrecords_after_insert` AFTER INSERT ON `billingrecords` FOR EACH ROW BEGIN
    INSERT INTO audit_log (user_id, event_type, table_name, row_id, new_values)
    VALUES (NULL, -- Capture user_id if possible
            'INSERT',
            'billingrecords',
            NEW.bill_id,
            JSON_OBJECT('bill_id', NEW.bill_id, 'appointment_id', NEW.appointment_id, 'patient_id', NEW.patient_id, 'billing_date', NEW.billing_date, 'description', NEW.description, 'amount', NEW.amount, 'payment_status', NEW.payment_status, 'created_at', NEW.created_at, 'updated_at', NEW.updated_at, 'record_id', NEW.record_id, 'payment_method', NEW.payment_method, 'payment_date', NEW.payment_date, 'invoice_number', NEW.invoice_number, 'notes', NEW.notes));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `billingrecords_after_update` AFTER UPDATE ON `billingrecords` FOR EACH ROW BEGIN
    INSERT INTO audit_log (user_id, event_type, table_name, row_id, old_values, new_values)
    VALUES (NULL, -- Capture user_id if possible
            'UPDATE',
            'billingrecords',
            OLD.bill_id,
            JSON_OBJECT('bill_id', OLD.bill_id, 'appointment_id', OLD.appointment_id, 'patient_id', OLD.patient_id, 'billing_date', OLD.billing_date, 'description', OLD.description, 'amount', OLD.amount, 'payment_status', OLD.payment_status, 'created_at', OLD.created_at, 'updated_at', OLD.updated_at, 'record_id', OLD.record_id, 'payment_method', OLD.payment_method, 'payment_date', OLD.payment_date, 'invoice_number', OLD.invoice_number, 'notes', OLD.notes),
            JSON_OBJECT('bill_id', NEW.bill_id, 'appointment_id', NEW.appointment_id, 'patient_id', NEW.patient_id, 'billing_date', NEW.billing_date, 'description', NEW.description, 'amount', NEW.amount, 'payment_status', NEW.payment_status, 'created_at', NEW.created_at, 'updated_at', NEW.updated_at, 'record_id', NEW.record_id, 'payment_method', NEW.payment_method, 'payment_date', NEW.payment_date, 'invoice_number', NEW.invoice_number, 'notes', NEW.notes));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `billingrecords_before_delete` BEFORE DELETE ON `billingrecords` FOR EACH ROW BEGIN
    INSERT INTO audit_log (user_id, event_type, table_name, row_id, old_values)
    VALUES (NULL, -- Capture user_id if possible
            'DELETE',
            'billingrecords',
            OLD.bill_id,
            JSON_OBJECT('bill_id', OLD.bill_id, 'appointment_id', OLD.appointment_id, 'patient_id', OLD.patient_id, 'billing_date', OLD.billing_date, 'description', OLD.description, 'amount', OLD.amount, 'payment_status', OLD.payment_status, 'created_at', OLD.created_at, 'updated_at', OLD.updated_at, 'record_id', OLD.record_id, 'payment_method', OLD.payment_method, 'payment_date', OLD.payment_date, 'invoice_number', OLD.invoice_number, 'notes', OLD.notes));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `doctor_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `specialization_id` int(11) NOT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`doctor_id`, `user_id`, `specialization_id`, `address`, `created_at`, `updated_at`) VALUES
(1, 4, 3, '123 Medical Center Drive, Cityville, ST 12345', '2025-05-13 04:00:10', '2025-05-13 04:00:10'),
(3, 10, 3, NULL, '2025-05-13 23:56:14', '2025-05-13 23:56:14');

--
-- Triggers `doctors`
--
DELIMITER $$
CREATE TRIGGER `doctors_after_insert_user_audit` AFTER INSERT ON `doctors` FOR EACH ROW BEGIN
    INSERT INTO audit_log (user_id, event_type, table_name, row_id, new_values)
    VALUES (NEW.user_id,
            'INSERT',
            'doctors',
            NEW.doctor_id,
            JSON_OBJECT('doctor_id', NEW.doctor_id, 'user_id', NEW.user_id, 'specialization_id', NEW.specialization_id, 'address', NEW.address, 'created_at', NEW.created_at, 'updated_at', NEW.updated_at));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `doctors_after_update_user_audit` AFTER UPDATE ON `doctors` FOR EACH ROW BEGIN
    INSERT INTO audit_log (user_id, event_type, table_name, row_id, old_values, new_values)
    VALUES (NEW.user_id,
            'UPDATE',
            'doctors',
            OLD.doctor_id,
            JSON_OBJECT('doctor_id', OLD.doctor_id, 'user_id', OLD.user_id, 'specialization_id', OLD.specialization_id, 'address', OLD.address, 'created_at', OLD.created_at, 'updated_at', OLD.updated_at),
            JSON_OBJECT('doctor_id', NEW.doctor_id, 'user_id', NEW.user_id, 'specialization_id', NEW.specialization_id, 'address', NEW.address, 'created_at', NEW.created_at, 'updated_at', NEW.updated_at));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `doctors_before_delete_user_audit` BEFORE DELETE ON `doctors` FOR EACH ROW BEGIN
    INSERT INTO audit_log (user_id, event_type, table_name, row_id, old_values)
    VALUES (OLD.user_id,
            'DELETE',
            'doctors',
            OLD.doctor_id,
            JSON_OBJECT('doctor_id', OLD.doctor_id, 'user_id', OLD.user_id, 'specialization_id', OLD.specialization_id, 'address', OLD.address, 'created_at', OLD.created_at, 'updated_at', OLD.updated_at));
END
$$
DELIMITER ;

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
(2, 1, '2025-05-15', NULL, NULL, 0, '');

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

--
-- Dumping data for table `doctor_schedule`
--

INSERT INTO `doctor_schedule` (`id`, `doctor_id`, `day_of_week`, `start_time`, `end_time`, `is_available`, `created_at`, `updated_at`) VALUES
(1, 1, 'Monday', '09:00:00', '17:00:00', 1, '2025-05-13 04:00:10', '2025-05-13 04:00:10'),
(2, 1, 'Tuesday', '09:00:00', '17:00:00', 1, '2025-05-13 04:00:10', '2025-05-13 04:00:10'),
(3, 1, 'Wednesday', '09:00:00', '17:00:00', 1, '2025-05-13 04:00:10', '2025-05-13 04:00:10'),
(4, 1, 'Thursday', '09:00:00', '17:00:00', 1, '2025-05-13 04:00:10', '2025-05-13 04:00:10'),
(5, 1, 'Friday', '09:00:00', '17:00:00', 1, '2025-05-13 04:00:10', '2025-05-13 04:00:10'),
(13, 3, 'Monday', '07:00:00', '18:00:00', 1, '2025-05-13 23:57:40', '2025-05-13 23:57:40'),
(14, 3, 'Tuesday', '07:00:00', '18:00:00', 1, '2025-05-13 23:57:40', '2025-05-13 23:57:40'),
(15, 3, 'Wednesday', '07:00:00', '18:00:00', 1, '2025-05-13 23:57:40', '2025-05-13 23:57:40'),
(16, 3, 'Thursday', '07:00:00', '18:00:00', 1, '2025-05-13 23:57:40', '2025-05-13 23:57:40'),
(17, 3, 'Friday', '07:00:00', '18:00:00', 1, '2025-05-13 23:57:40', '2025-05-13 23:57:40');

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
(20, 7, 'login', 'adminadmin logged in.', '2025-05-13 22:34:06');

-- --------------------------------------------------------

--
-- Table structure for table `medicalrecords`
--

CREATE TABLE `medicalrecords` (
  `record_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `record_datetime` timestamp NOT NULL DEFAULT current_timestamp(),
  `diagnosis` text DEFAULT NULL,
  `treatment` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `appointment_id` int(11) DEFAULT NULL,
  `prescribed_medications` text DEFAULT NULL,
  `test_results` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medicalrecords`
--

INSERT INTO `medicalrecords` (`record_id`, `patient_id`, `doctor_id`, `record_datetime`, `diagnosis`, `treatment`, `notes`, `created_at`, `updated_at`, `appointment_id`, `prescribed_medications`, `test_results`) VALUES
(3, 1, 1, '2025-05-13 06:00:41', 'a', 'a', 'a', '2025-05-13 06:00:41', '2025-05-13 06:00:41', 4, 'a', 'a'),
(4, 2, 1, '2025-05-13 08:10:06', 'a', 'a', 'a', '2025-05-13 14:10:06', '2025-05-13 14:10:06', 6, 'a', 'a');

--
-- Triggers `medicalrecords`
--
DELIMITER $$
CREATE TRIGGER `medicalrecords_after_insert` AFTER INSERT ON `medicalrecords` FOR EACH ROW BEGIN
    INSERT INTO audit_log (user_id, event_type, table_name, row_id, new_values)
    VALUES (NULL, -- Capture user_id if possible
            'INSERT',
            'medicalrecords',
            NEW.record_id,
            JSON_OBJECT('record_id', NEW.record_id, 'patient_id', NEW.patient_id, 'doctor_id', NEW.doctor_id, 'record_datetime', NEW.record_datetime, 'diagnosis', NEW.diagnosis, 'treatment', NEW.treatment, 'notes', NEW.notes, 'created_at', NEW.created_at, 'updated_at', NEW.updated_at, 'appointment_id', NEW.appointment_id, 'prescribed_medications', NEW.prescribed_medications, 'test_results', NEW.test_results));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `medicalrecords_after_update` AFTER UPDATE ON `medicalrecords` FOR EACH ROW BEGIN
    INSERT INTO audit_log (user_id, event_type, table_name, row_id, old_values, new_values)
    VALUES (NULL, -- Capture user_id if possible
            'UPDATE',
            'medicalrecords',
            OLD.record_id,
            JSON_OBJECT('record_id', OLD.record_id, 'patient_id', OLD.patient_id, 'doctor_id', OLD.doctor_id, 'record_datetime', OLD.record_datetime, 'diagnosis', OLD.diagnosis, 'treatment', OLD.treatment, 'notes', OLD.notes, 'created_at', OLD.created_at, 'updated_at', OLD.updated_at, 'appointment_id', OLD.appointment_id, 'prescribed_medications', OLD.prescribed_medications, 'test_results', OLD.test_results),
            JSON_OBJECT('record_id', NEW.record_id, 'patient_id', NEW.patient_id, 'doctor_id', NEW.doctor_id, 'record_datetime', NEW.record_datetime, 'diagnosis', NEW.diagnosis, 'treatment', NEW.treatment, 'notes', NEW.notes, 'created_at', NEW.created_at, 'updated_at', NEW.updated_at, 'appointment_id', NEW.appointment_id, 'prescribed_medications', NEW.prescribed_medications, 'test_results', NEW.test_results));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `medicalrecords_before_delete` BEFORE DELETE ON `medicalrecords` FOR EACH ROW BEGIN
    INSERT INTO audit_log (user_id, event_type, table_name, row_id, old_values)
    VALUES (NULL, -- Capture user_id if possible
            'DELETE',
            'medicalrecords',
            OLD.record_id,
            JSON_OBJECT('record_id', OLD.record_id, 'patient_id', OLD.patient_id, 'doctor_id', OLD.doctor_id, 'record_datetime', OLD.record_datetime, 'diagnosis', OLD.diagnosis, 'treatment', OLD.treatment, 'notes', OLD.notes, 'created_at', OLD.created_at, 'updated_at', OLD.updated_at, 'appointment_id', OLD.appointment_id, 'prescribed_medications', OLD.prescribed_medications, 'test_results', OLD.test_results));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `medicalrecords_backup`
--

CREATE TABLE `medicalrecords_backup` (
  `record_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `record_datetime` timestamp NOT NULL DEFAULT current_timestamp(),
  `diagnosis` text DEFAULT NULL,
  `treatment` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `appointment_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nurses`
--

CREATE TABLE `nurses` (
  `nurse_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `nurses`
--

INSERT INTO `nurses` (`nurse_id`, `user_id`, `created_at`, `updated_at`) VALUES
(1, 8, '2025-05-13 23:22:16', '2025-05-13 23:22:16');

--
-- Triggers `nurses`
--
DELIMITER $$
CREATE TRIGGER `nurses_after_insert_user_audit` AFTER INSERT ON `nurses` FOR EACH ROW BEGIN
    INSERT INTO audit_log (user_id, event_type, table_name, row_id, new_values)
    VALUES (NEW.user_id,
            'INSERT',
            'nurses',
            NEW.nurse_id,
            JSON_OBJECT('nurse_id', NEW.nurse_id, 'user_id', NEW.user_id, 'created_at', NEW.created_at, 'updated_at', NEW.updated_at));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `nurses_after_update_user_audit` AFTER UPDATE ON `nurses` FOR EACH ROW BEGIN
    INSERT INTO audit_log (user_id, event_type, table_name, row_id, old_values, new_values)
    VALUES (NEW.user_id,
            'UPDATE',
            'nurses',
            OLD.nurse_id,
            JSON_OBJECT('nurse_id', OLD.nurse_id, 'user_id', OLD.user_id, 'created_at', OLD.created_at, 'updated_at', OLD.updated_at),
            JSON_OBJECT('nurse_id', NEW.nurse_id, 'user_id', NEW.user_id, 'created_at', NEW.created_at, 'updated_at', NEW.updated_at));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `nurses_before_delete_user_audit` BEFORE DELETE ON `nurses` FOR EACH ROW BEGIN
    INSERT INTO audit_log (user_id, event_type, table_name, row_id, old_values)
    VALUES (OLD.user_id,
            'DELETE',
            'nurses',
            OLD.nurse_id,
            JSON_OBJECT('nurse_id', OLD.nurse_id, 'user_id', OLD.user_id, 'created_at', OLD.created_at, 'updated_at', OLD.updated_at));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `patient_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`patient_id`, `user_id`, `date_of_birth`, `gender`, `created_at`, `updated_at`) VALUES
(1, 2, '2025-05-19', 'Male', '2025-05-13 03:27:16', '2025-05-13 03:27:16'),
(2, 6, '2004-02-13', 'Male', '2025-05-13 13:47:32', '2025-05-13 13:47:32');

--
-- Triggers `patients`
--
DELIMITER $$
CREATE TRIGGER `patients_after_insert_user_audit` AFTER INSERT ON `patients` FOR EACH ROW BEGIN
    INSERT INTO audit_log (user_id, event_type, table_name, row_id, new_values)
    VALUES (NEW.user_id,
            'INSERT',
            'patients',
            NEW.patient_id,
            JSON_OBJECT('patient_id', NEW.patient_id, 'user_id', NEW.user_id, 'date_of_birth', NEW.date_of_birth, 'gender', NEW.gender, 'created_at', NEW.created_at, 'updated_at', NEW.updated_at));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `patients_after_update_user_audit` AFTER UPDATE ON `patients` FOR EACH ROW BEGIN
    INSERT INTO audit_log (user_id, event_type, table_name, row_id, old_values, new_values)
    VALUES (NEW.user_id,
            'UPDATE',
            'patients',
            OLD.patient_id,
            JSON_OBJECT('patient_id', OLD.patient_id, 'user_id', OLD.user_id, 'date_of_birth', OLD.date_of_birth, 'gender', OLD.gender, 'created_at', OLD.created_at, 'updated_at', OLD.updated_at),
            JSON_OBJECT('patient_id', NEW.patient_id, 'user_id', NEW.user_id, 'date_of_birth', NEW.date_of_birth, 'gender', NEW.gender, 'created_at', NEW.created_at, 'updated_at', NEW.updated_at));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `patients_before_delete_user_audit` BEFORE DELETE ON `patients` FOR EACH ROW BEGIN
    INSERT INTO audit_log (user_id, event_type, table_name, row_id, old_values)
    VALUES (OLD.user_id,
            'DELETE',
            'patients',
            OLD.patient_id,
            JSON_OBJECT('patient_id', OLD.patient_id, 'user_id', OLD.user_id, 'date_of_birth', OLD.date_of_birth, 'gender', OLD.gender, 'created_at', OLD.created_at, 'updated_at', OLD.updated_at));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `patient_descriptions`
--

CREATE TABLE `patient_descriptions` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `address` text DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `medical_record_number` varchar(50) DEFAULT NULL,
  `insurance_provider` varchar(100) DEFAULT NULL,
  `insurance_policy_number` varchar(50) DEFAULT NULL,
  `emergency_contact_name` varchar(255) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patient_descriptions`
--

INSERT INTO `patient_descriptions` (`id`, `patient_id`, `description`, `address`, `gender`, `medical_record_number`, `insurance_provider`, `insurance_policy_number`, `emergency_contact_name`, `emergency_contact_phone`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 'kamatyonon', 'manolo', NULL, '121212212', 'Pagibig', '121212', 'jonard pinalas', '915933296', 'tabangi tawon ning bataa', '2025-05-13 03:43:20', '2025-05-13 03:43:20');

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
(1, 'administrator'),
(2, 'doctor'),
(3, 'nurse'),
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
(1, 'General Medicine', '2025-05-13 01:36:48', '2025-05-13 01:36:48'),
(2, 'Pediatrics', '2025-05-13 01:36:48', '2025-05-13 01:36:48'),
(3, 'Cardiology', '2025-05-13 01:36:48', '2025-05-13 01:36:48'),
(4, 'Dermatology', '2025-05-13 01:36:48', '2025-05-13 01:36:48'),
(5, 'Orthopedics', '2025-05-13 01:36:48', '2025-05-13 01:36:48');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `role_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `phone_number`, `password_hash`, `first_name`, `last_name`, `role_id`, `created_at`, `updated_at`, `last_login`, `profile_image`) VALUES
(1, 'admin', 'admin@clinic.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', 1, '2025-05-13 01:36:48', '2025-05-13 01:36:48', NULL, NULL),
(2, 'jonard.pinalas', 'jonardsalanip@gmail.com', '915933296', '$2y$10$UToC3itfA2LAtkP8dl7mNe7PVMMXSYSR3Dhc8wOGNBMoHUmM1d0cm', 'jonard', 'pinalas', 4, '2025-05-13 03:27:16', '2025-05-13 03:27:16', NULL, NULL),
(4, 'drjohnsmith', 'john.smith@clinic.com', '123-456-7890', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Smith', 2, '2025-05-13 04:00:10', '2025-05-13 04:00:10', NULL, NULL),
(5, 'johnnybravo', 'johnjohn@john.com', '0901545', '$2y$10$fWGLBdR2aLMHcQ4UCDJK2e6ASh9HfkXlmH3R8PUi2QT94pB0hm.5K', 'JOHN', 'DOE', 3, '2025-05-13 06:31:27', '2025-05-13 14:09:27', '2025-05-13 14:09:27', '/it38b-Enterprise/uploads/avatars/5_1747121981_download.jpg'),
(6, 'dsadsa', 'dsadsa@dsa.com', 'dsadsa@dsa.com', '$2y$10$alnIbFs6aZe9S2qtT6Mup.QJ5oKV96bdcrIOkacRGhH1mDop44dm2', 'dsadsa', 'dsadsadd', 4, '2025-05-13 10:55:49', '2025-05-13 15:34:08', '2025-05-13 15:34:08', NULL),
(7, 'adminadmin', 'adminadmin@adminadmin.com', 'adminadmin@adminadmi', '$2y$10$CHFFH2MZRisn7u7G6SCjiu3e2YX58/57L3CJYbrTAJ2cvdGpTFjWu', 'adminadmin', 'adminadmin', 1, '2025-05-13 16:12:34', '2025-05-13 22:34:06', '2025-05-13 22:34:06', NULL),
(8, 'jonard', 'jonard@gmail.com', '915933296', '$2y$10$XRe6E9uUUDpmJFn8Qc8Z9OUROne50kBs24n3ASNYOufn/5qrfIoge', 'jonard', 'pinalas', 3, '2025-05-13 23:22:16', '2025-05-13 23:22:16', NULL, NULL),
(10, 'wood', 'wood@wood.com', NULL, '$2y$10$zEoZJYC88pexcPG7J5AA/On.f/ia2W3raR7ZibkwXUuMgZk2cxmzu', 'wood', 'mahogany', 2, '2025-05-13 23:56:14', '2025-05-13 23:56:14', NULL, NULL);

--
-- Triggers `users`
--
DELIMITER $$
CREATE TRIGGER `users_after_insert_audit` AFTER INSERT ON `users` FOR EACH ROW BEGIN
    INSERT INTO audit_log (user_id, event_type, table_name, row_id, new_values)
    VALUES (NEW.user_id, -- The new user's ID
            'INSERT',
            'users',
            NEW.user_id,
            JSON_OBJECT('user_id', NEW.user_id, 'username', NEW.username, 'email', NEW.email, 'phone_number', NEW.phone_number, 'first_name', NEW.first_name, 'last_name', NEW.last_name, 'role_id', NEW.role_id, 'created_at', NEW.created_at, 'updated_at', NEW.updated_at, 'last_login', NEW.last_login));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `users_after_update_audit` AFTER UPDATE ON `users` FOR EACH ROW BEGIN
    INSERT INTO audit_log (user_id, event_type, table_name, row_id, old_values, new_values)
    VALUES (NEW.user_id, -- The updated user's ID
            'UPDATE',
            'users',
            OLD.user_id,
            JSON_OBJECT('user_id', OLD.user_id, 'username', OLD.username, 'email', OLD.email, 'phone_number', OLD.phone_number, 'first_name', OLD.first_name, 'last_name', OLD.last_name, 'role_id', OLD.role_id, 'created_at', OLD.created_at, 'updated_at', OLD.updated_at, 'last_login', OLD.last_login),
            JSON_OBJECT('user_id', NEW.user_id, 'username', NEW.username, 'email', NEW.email, 'phone_number', NEW.phone_number, 'first_name', NEW.first_name, 'last_name', NEW.last_name, 'role_id', NEW.role_id, 'created_at', NEW.created_at, 'updated_at', NEW.updated_at, 'last_login', NEW.last_login));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `users_before_delete_audit` BEFORE DELETE ON `users` FOR EACH ROW BEGIN
    -- When a user is deleted, who is performing the deletion?
    -- We might not have that context here unless the application sets a variable.
    -- For now, we'll log the deleted user's ID as the 'user_id' in the audit log.
    INSERT INTO audit_log (user_id, event_type, table_name, row_id, old_values)
    VALUES (OLD.user_id, -- The ID of the user being deleted
            'DELETE',
            'users',
            OLD.user_id,
            JSON_OBJECT('user_id', OLD.user_id, 'username', OLD.username, 'email', OLD.email, 'phone_number', OLD.phone_number, 'first_name', OLD.first_name, 'last_name', OLD.last_name, 'role_id', OLD.role_id, 'created_at', OLD.created_at, 'updated_at', OLD.updated_at, 'last_login', OLD.last_login));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_appointment_records`
-- (See below for the actual view)
--
CREATE TABLE `view_appointment_records` (
`appointment_id` int(11)
,`appointment_datetime` datetime
,`status` enum('Requested','Scheduled','Completed','No Show','Cancelled')
,`reason_for_visit` text
,`patient_name` varchar(511)
,`doctor_name` varchar(511)
,`medical_record_count` bigint(21)
,`billing_record_count` bigint(21)
,`total_billed_amount` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Structure for view `view_appointment_records`
--
DROP TABLE IF EXISTS `view_appointment_records`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_appointment_records`  AS SELECT `a`.`appointment_id` AS `appointment_id`, `a`.`appointment_datetime` AS `appointment_datetime`, `a`.`status` AS `status`, `a`.`reason_for_visit` AS `reason_for_visit`, concat(`pu`.`first_name`,' ',`pu`.`last_name`) AS `patient_name`, concat(`du`.`first_name`,' ',`du`.`last_name`) AS `doctor_name`, count(distinct `m`.`record_id`) AS `medical_record_count`, count(distinct `b`.`bill_id`) AS `billing_record_count`, sum(ifnull(`b`.`amount`,0)) AS `total_billed_amount` FROM ((((((`appointments` `a` join `patients` `p` on(`a`.`patient_id` = `p`.`patient_id`)) join `users` `pu` on(`p`.`user_id` = `pu`.`user_id`)) join `doctors` `d` on(`a`.`doctor_id` = `d`.`doctor_id`)) join `users` `du` on(`d`.`user_id` = `du`.`user_id`)) left join `medicalrecords` `m` on(`a`.`appointment_id` = `m`.`appointment_id`)) left join `billingrecords` `b` on(`a`.`appointment_id` = `b`.`appointment_id`)) GROUP BY `a`.`appointment_id` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`appointment_id`),
  ADD KEY `appointment_datetime` (`appointment_datetime`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `timestamp` (`timestamp`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `table_name` (`table_name`),
  ADD KEY `row_id` (`row_id`),
  ADD KEY `event_type` (`event_type`);

--
-- Indexes for table `billingrecords`
--
ALTER TABLE `billingrecords`
  ADD PRIMARY KEY (`bill_id`),
  ADD KEY `billing_date` (`billing_date`),
  ADD KEY `appointment_id` (`appointment_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `idx_record_id` (`record_id`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`doctor_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `specialization_id` (`specialization_id`);

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
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `idx_medicalrecords_appointment_id` (`appointment_id`),
  ADD KEY `idx_appointment_id` (`appointment_id`);

--
-- Indexes for table `medicalrecords_backup`
--
ALTER TABLE `medicalrecords_backup`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `idx_medicalrecords_appointment_id` (`appointment_id`);

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
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `patient_descriptions`
--
ALTER TABLE `patient_descriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `medical_record_number` (`medical_record_number`),
  ADD KEY `patient_id` (`patient_id`);

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
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `billingrecords`
--
ALTER TABLE `billingrecords`
  MODIFY `bill_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `doctor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `doctor_availability_exceptions`
--
ALTER TABLE `doctor_availability_exceptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `doctor_schedule`
--
ALTER TABLE `doctor_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `medicalrecords`
--
ALTER TABLE `medicalrecords`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `medicalrecords_backup`
--
ALTER TABLE `medicalrecords_backup`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `nurses`
--
ALTER TABLE `nurses`
  MODIFY `nurse_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `patient_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `patient_descriptions`
--
ALTER TABLE `patient_descriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `specializations`
--
ALTER TABLE `specializations`
  MODIFY `specialization_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admins`
--
ALTER TABLE `admins`
  ADD CONSTRAINT `admins_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`),
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`);

--
-- Constraints for table `billingrecords`
--
ALTER TABLE `billingrecords`
  ADD CONSTRAINT `billingrecords_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`),
  ADD CONSTRAINT `billingrecords_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`),
  ADD CONSTRAINT `fk_billingrecords_medicalrecords` FOREIGN KEY (`record_id`) REFERENCES `medicalrecords` (`record_id`) ON DELETE SET NULL;

--
-- Constraints for table `doctors`
--
ALTER TABLE `doctors`
  ADD CONSTRAINT `doctors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `doctors_ibfk_2` FOREIGN KEY (`specialization_id`) REFERENCES `specializations` (`specialization_id`);

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
  ADD CONSTRAINT `fk_medicalrecords_appointments` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `medicalrecords_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`),
  ADD CONSTRAINT `medicalrecords_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`);

--
-- Constraints for table `nurses`
--
ALTER TABLE `nurses`
  ADD CONSTRAINT `nurses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `patients_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `patient_descriptions`
--
ALTER TABLE `patient_descriptions`
  ADD CONSTRAINT `patient_descriptions_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
