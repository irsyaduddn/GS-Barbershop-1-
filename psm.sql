-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 03, 2025 at 04:12 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `psm`
--

-- --------------------------------------------------------

--
-- Table structure for table `addon_services`
--

CREATE TABLE `addon_services` (
  `addon_id` int(11) NOT NULL,
  `service_name` varchar(100) NOT NULL,
  `service_price` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `addon_services`
--

INSERT INTO `addon_services` (`addon_id`, `service_name`, `service_price`, `description`) VALUES
(2, 'Beard Trim', 1.00, ''),
(5, 'Hair Wash', 2.00, '');

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_email` varchar(255) NOT NULL,
  `admin_password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_email`, `admin_password`) VALUES
('admin@gsbarbershop.com', '123');

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `barber_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `variation` varchar(100) DEFAULT NULL,
  `selected_addons` varchar(255) DEFAULT NULL,
  `appointment_date` date DEFAULT NULL,
  `appointment_time` time DEFAULT NULL,
  `status` enum('Pending','Confirmed','Cancelled','Completed') DEFAULT 'Pending',
  `is_booked` tinyint(1) DEFAULT 0,
  `haircut_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `remider_sent` tinyint(1) DEFAULT 0,
  `payment_status` varchar(20) DEFAULT NULL,
  `stripe_payment_intent` varchar(50) DEFAULT NULL,
  `stripe_session_id` varchar(50) DEFAULT NULL,
  `payment_date` datetime DEFAULT NULL,
  `payment_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`appointment_id`, `customer_id`, `barber_id`, `service_id`, `variation`, `selected_addons`, `appointment_date`, `appointment_time`, `status`, `is_booked`, `haircut_id`, `total_amount`, `remider_sent`, `payment_status`, `stripe_payment_intent`, `stripe_session_id`, `payment_date`, `payment_id`) VALUES
(49, 8, 7, 9, 'Mid Fade', '5', '2025-05-10', '14:00:00', 'Completed', 1, NULL, 17.00, 0, 'pending', NULL, NULL, NULL, NULL),
(50, 8, 7, 9, 'Mid Fade', '5', '2025-05-13', '14:00:00', 'Completed', 1, NULL, 17.00, 0, 'pending', NULL, NULL, NULL, NULL),
(52, 8, 7, 9, 'Mid Fade', '2,5', '2025-05-16', '14:00:00', 'Completed', 1, NULL, 18.00, 0, 'pending', NULL, NULL, NULL, NULL),
(53, NULL, 7, 9, NULL, NULL, '2025-05-16', '14:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(54, 8, 7, 9, 'Mid Fade', '2,5', '2025-05-16', '15:00:00', 'Completed', 1, NULL, 18.00, 0, 'pending', NULL, NULL, NULL, NULL),
(55, 9, 7, 9, 'Upper Cut', '2,5', '2025-05-16', '15:30:00', 'Completed', 1, NULL, 18.00, 0, 'pending', NULL, NULL, NULL, NULL),
(57, 10, 7, 9, 'Upper Cut', '2,5', '2025-05-22', '14:35:00', 'Completed', 1, NULL, 18.00, 0, 'pending', NULL, NULL, NULL, NULL),
(59, NULL, 9, 12, NULL, NULL, '2025-05-23', '11:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(60, 10, 9, 9, 'Mid Fade', '2,5', '2025-05-23', '12:00:00', 'Completed', 1, NULL, 18.00, 0, 'pending', NULL, NULL, NULL, NULL),
(61, NULL, 7, 9, NULL, NULL, '2025-05-23', '13:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(62, NULL, 9, 12, NULL, NULL, '2025-05-23', '16:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(63, 9, 9, 9, 'Mid Fade', '2', '2025-05-23', '15:00:00', 'Completed', 1, NULL, 16.00, 0, 'pending', NULL, NULL, NULL, NULL),
(75, NULL, 9, 9, NULL, NULL, '2025-05-25', '10:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(76, NULL, 9, 9, NULL, NULL, '2025-05-25', '11:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(77, NULL, 9, 9, NULL, NULL, '2025-05-25', '11:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(78, NULL, 9, 9, NULL, NULL, '2025-05-25', '12:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(79, NULL, 9, 9, NULL, NULL, '2025-05-25', '12:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(80, NULL, 9, 9, NULL, NULL, '2025-05-25', '14:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(81, NULL, 9, 9, NULL, NULL, '2025-05-25', '14:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(82, NULL, 9, 9, NULL, NULL, '2025-05-25', '15:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(83, NULL, 9, 9, NULL, NULL, '2025-05-25', '15:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(84, NULL, 9, 9, NULL, NULL, '2025-05-25', '16:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(85, NULL, 9, 9, NULL, NULL, '2025-05-25', '16:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(86, NULL, 9, 9, NULL, NULL, '2025-05-25', '17:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(87, NULL, 9, 9, NULL, NULL, '2025-05-25', '17:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(88, NULL, 10, 9, NULL, NULL, '2025-05-25', '10:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(89, NULL, 10, 9, NULL, NULL, '2025-05-25', '11:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(90, NULL, 10, 9, NULL, NULL, '2025-05-25', '11:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(91, NULL, 10, 9, NULL, NULL, '2025-05-25', '12:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(92, NULL, 10, 9, NULL, NULL, '2025-05-25', '12:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(93, NULL, 10, 9, NULL, NULL, '2025-05-25', '14:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(94, NULL, 10, 9, NULL, NULL, '2025-05-25', '14:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(95, NULL, 10, 9, NULL, NULL, '2025-05-25', '15:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(96, NULL, 10, 9, NULL, NULL, '2025-05-25', '15:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(97, NULL, 10, 9, NULL, NULL, '2025-05-25', '16:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(98, NULL, 10, 9, NULL, NULL, '2025-05-25', '16:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(99, NULL, 10, 9, NULL, NULL, '2025-05-25', '17:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(100, NULL, 10, 9, NULL, NULL, '2025-05-25', '17:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(153, NULL, 7, 9, NULL, NULL, '2025-05-25', '10:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(154, NULL, 7, 9, NULL, NULL, '2025-05-25', '11:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(155, NULL, 7, 9, NULL, NULL, '2025-05-25', '11:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(156, NULL, 7, 9, NULL, NULL, '2025-05-25', '12:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(157, 8, 7, 9, 'Mid Fade', '5', '2025-05-25', '12:30:00', 'Confirmed', 1, NULL, 17.00, 0, 'pending', NULL, NULL, NULL, NULL),
(158, NULL, 7, 9, NULL, NULL, '2025-05-25', '14:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(159, NULL, 7, 9, NULL, NULL, '2025-05-25', '14:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(160, 8, 7, 9, 'Mid Fade', '5', '2025-05-25', '15:00:00', 'Cancelled', 1, NULL, 17.00, 0, 'pending', NULL, NULL, NULL, NULL),
(161, 8, 7, 9, 'Mid Fade', '', '2025-05-25', '15:30:00', 'Confirmed', 1, NULL, 15.00, 0, 'pending', NULL, NULL, NULL, NULL),
(162, 8, 7, 9, 'Mid Fade', '', '2025-05-25', '16:00:00', 'Cancelled', 1, NULL, 15.00, 0, 'pending', NULL, NULL, NULL, NULL),
(163, 8, 7, 9, 'Mid Fade', '', '2025-05-25', '16:30:00', 'Cancelled', 1, NULL, 15.00, 0, 'pending', NULL, NULL, NULL, NULL),
(164, NULL, 7, 9, NULL, NULL, '2025-05-25', '17:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(165, 8, 7, 9, 'Mid Fade', '5', '2025-05-25', '17:30:00', 'Completed', 1, NULL, 17.00, 0, 'pending', NULL, NULL, NULL, NULL),
(166, 9, 7, 9, 'Taper', '', '2025-05-26', '10:30:00', 'Completed', 1, NULL, 15.00, 0, 'pending', NULL, NULL, NULL, NULL),
(167, NULL, 7, 9, NULL, NULL, '2025-05-26', '11:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(168, NULL, 7, 9, NULL, NULL, '2025-05-26', '11:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(169, NULL, 7, 9, NULL, NULL, '2025-05-26', '12:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(170, NULL, 7, 9, NULL, NULL, '2025-05-26', '12:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(171, NULL, 7, 9, NULL, NULL, '2025-05-26', '14:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(172, NULL, 7, 9, NULL, NULL, '2025-05-26', '14:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(173, NULL, 7, 9, NULL, NULL, '2025-05-26', '15:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(174, NULL, 7, 9, NULL, NULL, '2025-05-26', '15:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(175, NULL, 7, 9, NULL, NULL, '2025-05-26', '16:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(176, NULL, 7, 9, NULL, NULL, '2025-05-26', '16:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(177, NULL, 7, 9, NULL, NULL, '2025-05-26', '17:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(178, NULL, 7, 9, NULL, NULL, '2025-05-26', '17:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(179, NULL, 10, 9, NULL, NULL, '2025-05-26', '10:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(180, NULL, 10, 9, NULL, NULL, '2025-05-26', '11:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(181, NULL, 10, 9, NULL, NULL, '2025-05-26', '11:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(182, NULL, 10, 9, NULL, NULL, '2025-05-26', '12:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(183, NULL, 10, 9, NULL, NULL, '2025-05-26', '12:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(184, NULL, 10, 9, NULL, NULL, '2025-05-26', '14:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(185, NULL, 10, 9, NULL, NULL, '2025-05-26', '14:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(186, NULL, 10, 9, NULL, NULL, '2025-05-26', '15:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(187, NULL, 10, 9, NULL, NULL, '2025-05-26', '15:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(188, NULL, 10, 9, NULL, NULL, '2025-05-26', '16:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(189, NULL, 10, 9, NULL, NULL, '2025-05-26', '16:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(190, NULL, 10, 9, NULL, NULL, '2025-05-26', '17:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(191, NULL, 10, 9, NULL, NULL, '2025-05-26', '17:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(192, NULL, 7, 9, NULL, NULL, '2025-05-27', '10:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(193, NULL, 7, 9, NULL, NULL, '2025-05-27', '11:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(194, NULL, 7, 9, NULL, NULL, '2025-05-27', '11:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(195, NULL, 7, 9, NULL, NULL, '2025-05-27', '12:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(196, NULL, 7, 9, NULL, NULL, '2025-05-27', '12:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(197, NULL, 7, 9, NULL, NULL, '2025-05-27', '14:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(198, NULL, 7, 9, NULL, NULL, '2025-05-27', '14:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(199, NULL, 7, 9, NULL, NULL, '2025-05-27', '15:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(200, NULL, 7, 9, NULL, NULL, '2025-05-27', '15:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(201, NULL, 7, 9, NULL, NULL, '2025-05-27', '16:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(202, NULL, 7, 9, NULL, NULL, '2025-05-27', '16:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(203, NULL, 7, 9, NULL, NULL, '2025-05-27', '17:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(204, NULL, 7, 9, NULL, NULL, '2025-05-27', '17:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(214, NULL, 7, 9, NULL, NULL, '2025-05-28', '16:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(215, NULL, 7, 9, NULL, NULL, '2025-05-28', '16:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(216, NULL, 7, 9, NULL, NULL, '2025-05-28', '17:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(217, NULL, 7, 9, NULL, NULL, '2025-05-28', '17:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(226, NULL, 9, 9, NULL, NULL, '2025-05-28', '15:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(227, NULL, 9, 9, NULL, NULL, '2025-05-28', '16:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(228, NULL, 9, 9, NULL, NULL, '2025-05-28', '16:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(229, NULL, 9, 9, NULL, NULL, '2025-05-28', '17:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(230, NULL, 9, 9, NULL, NULL, '2025-05-28', '17:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(231, NULL, 7, 9, NULL, NULL, '2025-05-29', '10:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(232, NULL, 7, 9, NULL, NULL, '2025-05-29', '11:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(233, NULL, 7, 9, NULL, NULL, '2025-05-29', '11:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(234, NULL, 7, 9, NULL, NULL, '2025-05-29', '12:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(235, NULL, 7, 9, NULL, NULL, '2025-05-29', '12:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(236, NULL, 7, 9, NULL, NULL, '2025-05-29', '14:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(237, NULL, 7, 9, NULL, NULL, '2025-05-29', '14:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(238, NULL, 7, 9, NULL, NULL, '2025-05-29', '15:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(239, NULL, 7, 9, NULL, NULL, '2025-05-29', '15:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(240, NULL, 7, 9, NULL, NULL, '2025-05-29', '16:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(241, NULL, 7, 9, NULL, NULL, '2025-05-29', '16:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(242, NULL, 7, 9, NULL, NULL, '2025-05-29', '17:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(243, NULL, 7, 9, NULL, NULL, '2025-05-29', '17:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(244, NULL, 7, 9, NULL, NULL, '2025-05-30', '10:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(245, 8, 7, 9, 'Burst Fade', '5', '2025-05-30', '11:00:00', 'Confirmed', 1, NULL, 17.00, 0, 'pending', NULL, NULL, NULL, NULL),
(246, 8, 7, 9, 'Low Fade', '2', '2025-05-30', '11:30:00', 'Confirmed', 1, NULL, 16.00, 0, 'pending', NULL, NULL, NULL, NULL),
(247, NULL, 7, 9, NULL, NULL, '2025-05-30', '12:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(248, NULL, 7, 9, NULL, NULL, '2025-05-30', '12:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(249, 8, 7, 9, 'Mid Fade', '5', '2025-05-30', '14:00:00', 'Confirmed', 1, NULL, 17.00, 0, 'pending', NULL, NULL, NULL, NULL),
(250, 8, 7, 9, 'Mullet', '', '2025-05-30', '14:30:00', 'Confirmed', 1, NULL, 15.00, 0, 'pending', NULL, NULL, NULL, NULL),
(251, NULL, 7, 9, NULL, NULL, '2025-05-30', '15:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(252, NULL, 7, 9, NULL, NULL, '2025-05-30', '15:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(253, NULL, 7, 9, NULL, NULL, '2025-05-30', '16:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(254, NULL, 7, 9, NULL, NULL, '2025-05-30', '16:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(255, 9, 7, 9, 'Mullet', '', '2025-05-30', '17:00:00', 'Confirmed', 1, NULL, 15.00, 0, 'pending', NULL, NULL, NULL, NULL),
(256, 8, 7, 9, 'Taper', '5', '2025-05-30', '17:30:00', 'Confirmed', 1, NULL, 17.00, 0, 'pending', NULL, NULL, NULL, NULL),
(257, NULL, 7, 9, NULL, NULL, '2025-05-31', '10:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(258, 9, 7, 9, 'Burst Fade', '5', '2025-05-31', '11:00:00', 'Completed', 1, NULL, 17.00, 0, 'pending', NULL, NULL, NULL, NULL),
(259, NULL, 7, 9, NULL, NULL, '2025-05-31', '11:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(260, NULL, 7, 9, NULL, NULL, '2025-05-31', '12:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(261, NULL, 7, 9, NULL, NULL, '2025-05-31', '12:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(262, NULL, 7, 9, NULL, NULL, '2025-05-31', '14:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(263, NULL, 7, 9, NULL, NULL, '2025-05-31', '14:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(264, NULL, 7, 9, NULL, NULL, '2025-05-31', '15:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(265, NULL, 7, 9, NULL, NULL, '2025-05-31', '15:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(266, NULL, 7, 9, NULL, NULL, '2025-05-31', '16:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(267, NULL, 7, 9, NULL, NULL, '2025-05-31', '16:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(268, NULL, 7, 9, NULL, NULL, '2025-05-31', '17:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(269, NULL, 7, 9, NULL, NULL, '2025-05-31', '17:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(270, NULL, 7, 9, NULL, NULL, '2025-06-01', '10:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(271, NULL, 7, 9, NULL, NULL, '2025-06-01', '11:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(272, NULL, 7, 9, NULL, NULL, '2025-06-01', '11:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(273, NULL, 7, 9, NULL, NULL, '2025-06-01', '12:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(274, NULL, 7, 9, NULL, NULL, '2025-06-01', '12:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(275, NULL, 7, 9, NULL, NULL, '2025-06-01', '14:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(276, NULL, 7, 9, NULL, NULL, '2025-06-01', '14:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(277, NULL, 7, 9, NULL, NULL, '2025-06-01', '15:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(278, NULL, 7, 9, NULL, NULL, '2025-06-01', '15:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(279, NULL, 7, 9, NULL, NULL, '2025-06-01', '16:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(280, NULL, 7, 9, NULL, NULL, '2025-06-01', '16:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(281, NULL, 7, 9, NULL, NULL, '2025-06-01', '17:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(282, NULL, 7, 9, NULL, NULL, '2025-06-01', '17:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(283, NULL, 7, 9, NULL, NULL, '2025-06-02', '10:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(284, NULL, 7, 9, NULL, NULL, '2025-06-02', '11:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(285, NULL, 7, 9, NULL, NULL, '2025-06-02', '11:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(286, NULL, 7, 9, NULL, NULL, '2025-06-02', '12:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(287, NULL, 7, 9, NULL, NULL, '2025-06-02', '12:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(288, NULL, 7, 9, NULL, NULL, '2025-06-02', '14:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(289, NULL, 7, 9, NULL, NULL, '2025-06-02', '14:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(290, NULL, 7, 9, NULL, NULL, '2025-06-02', '15:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(291, NULL, 7, 9, NULL, NULL, '2025-06-02', '15:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(292, NULL, 7, 9, NULL, NULL, '2025-06-02', '16:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(293, NULL, 7, 9, NULL, NULL, '2025-06-02', '16:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(294, NULL, 7, 9, NULL, NULL, '2025-06-02', '17:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(295, NULL, 7, 9, NULL, NULL, '2025-06-02', '17:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(296, NULL, 7, 9, NULL, NULL, '2025-05-28', '10:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(297, NULL, 7, 9, NULL, NULL, '2025-05-28', '11:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(298, NULL, 7, 9, NULL, NULL, '2025-05-28', '11:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(299, NULL, 7, 9, NULL, NULL, '2025-05-28', '12:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(300, NULL, 7, 9, NULL, NULL, '2025-05-28', '12:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(301, NULL, 7, 9, NULL, NULL, '2025-05-28', '14:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(302, NULL, 7, 9, NULL, NULL, '2025-05-28', '14:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(303, NULL, 7, 9, NULL, NULL, '2025-05-28', '15:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(304, NULL, 7, 9, NULL, NULL, '2025-05-28', '15:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(305, NULL, 7, 9, NULL, NULL, '2025-06-03', '10:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(306, NULL, 7, 9, NULL, NULL, '2025-06-03', '11:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(307, NULL, 7, 9, NULL, NULL, '2025-06-03', '11:30:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(308, NULL, 7, 9, NULL, NULL, '2025-06-03', '12:00:00', '', 0, NULL, 0.00, 0, 'pending', NULL, NULL, NULL, NULL),
(311, 8, 7, 9, 'Pompadour', '', '2025-06-03', '14:30:00', 'Confirmed', 1, NULL, 15.00, 0, 'pending', NULL, NULL, NULL, 'cs_test_a1cHXVje3X3B55TgyUIwPF58WpcXOpjx85FCuonacm3cNF4MFCZepIP6JS'),
(312, 8, 7, 9, 'Taper', '', '2025-06-03', '15:00:00', 'Confirmed', 1, NULL, 15.00, 0, 'pending', NULL, NULL, NULL, 'cs_test_a1rJbx87dC9n2jdmcy6ffjSGljEUr1U1Lowa9SzseDUVK3XJ2HDzuZfvrD'),
(313, 8, 7, 9, 'Burst Fade', '5', '2025-06-03', '15:30:00', 'Confirmed', 1, NULL, 17.00, 0, 'paid', NULL, NULL, '2025-06-03 11:45:10', 'cs_test_b1ox5y1E9oIHwoXk5p55ggY9qCyuIY3zwuA41XYQPvqKvGTDVy9CwZLbQQ'),
(314, 8, 7, 9, 'Taper', '5', '2025-06-03', '16:00:00', 'Confirmed', 1, NULL, 17.00, 0, 'pending', NULL, NULL, '2025-06-03 11:40:44', 'cs_test_b1pXSuWm12ooVcKPeXhffJU2oLeKhdcAOYQhsQY0zaSi8cr8GnwgEqy5N0'),
(315, 8, 7, 9, 'Buzz Cut', '', '2025-06-03', '16:30:00', 'Confirmed', 1, NULL, 15.00, 0, 'pending', NULL, NULL, NULL, 'cs_test_a1usoKf22yZMBsKJWhrWnLn0rBGdt6ktPc43pYG0tnWr7PvdnBqwkhqaSa'),
(317, 8, 7, 9, 'Mid Fade', '5', '2025-06-03', '17:30:00', 'Confirmed', 1, NULL, 17.00, 0, 'pending', NULL, NULL, NULL, 'cs_test_b1n4EcutrYX9kqGbFweiSbiQYDLdGtwF01jo02uzc5uqPTQ95ILjcdQDcS'),
(324, 8, 9, 9, 'Pompadour', '5', '2025-06-04', '10:30:00', 'Cancelled', 1, NULL, 17.00, 0, 'Refunded', NULL, NULL, '2025-06-03 15:50:17', 'cs_test_b1nXI4kGQZtpWqeSg5FWO7FwrTN6rZGpvCioSoMA2TYiJk7w4NwkdV5MRr'),
(325, 8, 9, 9, 'Mid Fade', '', '2025-06-04', '11:00:00', 'Cancelled', 1, NULL, 15.00, 0, 'Refunded', NULL, NULL, '2025-06-03 16:51:11', 'cs_test_a1xVNirnybW7axfK4ECwbBAStS2kQ0XSCFn2rQg6zXxd56m9cdNtrY40N5'),
(326, NULL, 9, 9, NULL, NULL, '2025-06-04', '11:30:00', '', 0, NULL, 0.00, 0, NULL, NULL, NULL, NULL, NULL),
(327, NULL, 9, 9, NULL, NULL, '2025-06-04', '12:00:00', '', 0, NULL, 0.00, 0, NULL, NULL, NULL, NULL, NULL),
(328, NULL, 9, 9, NULL, NULL, '2025-06-04', '12:30:00', '', 0, NULL, 0.00, 0, NULL, NULL, NULL, NULL, NULL),
(329, NULL, 9, 9, NULL, NULL, '2025-06-04', '14:00:00', '', 0, NULL, 0.00, 0, NULL, NULL, NULL, NULL, NULL),
(330, NULL, 9, 9, NULL, NULL, '2025-06-04', '14:30:00', '', 0, NULL, 0.00, 0, NULL, NULL, NULL, NULL, NULL),
(331, NULL, 9, 9, NULL, NULL, '2025-06-04', '15:00:00', '', 0, NULL, 0.00, 0, NULL, NULL, NULL, NULL, NULL),
(332, NULL, 9, 9, NULL, NULL, '2025-06-04', '15:30:00', '', 0, NULL, 0.00, 0, NULL, NULL, NULL, NULL, NULL),
(333, NULL, 9, 9, NULL, NULL, '2025-06-04', '16:00:00', '', 0, NULL, 0.00, 0, NULL, NULL, NULL, NULL, NULL),
(334, NULL, 9, 9, NULL, NULL, '2025-06-04', '16:30:00', '', 0, NULL, 0.00, 0, NULL, NULL, NULL, NULL, NULL),
(335, NULL, 9, 9, NULL, NULL, '2025-06-04', '17:00:00', '', 0, NULL, 0.00, 0, NULL, NULL, NULL, NULL, NULL),
(336, 8, 9, 9, 'Buzz Cut', '', '2025-06-04', '17:30:00', 'Confirmed', 1, NULL, 15.00, 0, 'paid', NULL, NULL, '2025-06-03 17:37:57', 'cs_test_a1Iqlf5UaCpS8ORW4a848Bc7CnFuiNCYbNloK1a4syMqL6XLp4DfVarqWD'),
(337, NULL, 9, 9, 'Mid Fade', '', '2025-06-04', '11:00:00', '', 0, NULL, 0.00, 0, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `barbers`
--

CREATE TABLE `barbers` (
  `barber_id` int(11) NOT NULL,
  `barber_name` varchar(255) DEFAULT NULL,
  `barber_email` varchar(255) DEFAULT NULL,
  `barber_password` varchar(255) DEFAULT NULL,
  `barber_phone` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `barbers`
--

INSERT INTO `barbers` (`barber_id`, `barber_name`, `barber_email`, `barber_password`, `barber_phone`) VALUES
(7, 'Ishak', 'Ishak@gmail.com', '123', '01287984533'),
(9, 'Adan', 'Adan@gmail.com', '123', '01967229965'),
(10, 'Sahak', 'sahak@gmail.com', '123', '0178865489');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `customer_password` varchar(255) DEFAULT NULL,
  `customer_phone` varchar(15) DEFAULT NULL,
  `customer_age` int(3) DEFAULT NULL,
  `customer_photo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `customer_name`, `customer_email`, `customer_password`, `customer_phone`, `customer_age`, `customer_photo`) VALUES
(8, 'Ahmad Irsyaduddin', 'irsyaduddn@gmail.com', 'Irsyad03', '0196838354', 22, 'uploads/photo_2024-01-08_01-35-10.jpg'),
(9, 'Azizi', 'Aziziazimm@gmail.com', '123456', '0195845847', 21, 'uploads/photo_2024-12-29_13-28-22.jpg'),
(10, 'AMAR ZAMANI AZZIM BIN AHMAD ZAIDI', 'gamersamar3@gmail.com', '123456', '0189473714', 21, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `feedback_id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `barber_id` int(11) DEFAULT NULL,
  `service_id` int(11) DEFAULT NULL,
  `rating` int(1) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `comments` text DEFAULT NULL,
  `feedback_date` date DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_status` enum('pending','completed','failed','refunded') NOT NULL,
  `payment_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `service_id` int(11) NOT NULL,
  `service_name` varchar(255) DEFAULT NULL,
  `service_price` decimal(10,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `duration` int(11) NOT NULL DEFAULT 30 COMMENT 'Duration in minutes'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`service_id`, `service_name`, `service_price`, `description`, `duration`) VALUES
(9, 'Adult Haircut', 15.00, 'A standard haircut service for men aged 18 to 59. Haircut using clippers and/or scissors, basic styling, and neck cleanup. Ideal for maintaining a clean and professional look.', 30),
(10, 'Senior Citizen Haircut', 12.00, 'A gentle and respectful haircut service for seniors aged 60 and above. Focuses on comfort and simplicity, often with shorter styles for easy maintenance.', 30),
(11, 'Child Haircut', 12.00, 'A patient and fun haircut service for kids aged 12 and below. Tailored for sensitive scalps and shorter attention spans.', 30),
(12, 'Teen Haircut', 14.00, 'Designed for teenagers aged 13 to 17, this service offers trendier styles, skin fades, or textured looks. Includes light styling to match current youth trends.', 30);

-- --------------------------------------------------------

--
-- Table structure for table `service_addons`
--

CREATE TABLE `service_addons` (
  `service_id` int(11) NOT NULL,
  `addon_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_addons`
--

INSERT INTO `service_addons` (`service_id`, `addon_id`) VALUES
(9, 2),
(9, 5),
(10, 2),
(10, 5),
(11, 5),
(12, 5);

-- --------------------------------------------------------

--
-- Table structure for table `service_variations`
--

CREATE TABLE `service_variations` (
  `variation_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `variation_name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_variations`
--

INSERT INTO `service_variations` (`variation_id`, `service_id`, `variation_name`, `price`, `description`) VALUES
(3, 9, 'Upper Cut', 0.00, NULL),
(4, 9, 'Mid Fade', 0.00, NULL),
(5, 9, 'Buzz Cut', 0.00, NULL),
(6, 9, 'Low Fade', 0.00, NULL),
(7, 9, 'High Fade', 0.00, NULL),
(8, 9, 'Under Cut', 0.00, NULL),
(9, 9, 'Pompadour', 0.00, NULL),
(10, 9, 'French Crop', 0.00, NULL),
(11, 9, 'Mullet', 0.00, NULL),
(12, 9, 'Taper', 0.00, NULL),
(13, 9, 'Burst Fade', 0.00, NULL),
(14, 11, 'Classic Taper', 0.00, NULL),
(15, 11, 'Side Part', 0.00, NULL),
(16, 10, 'Classic Taper', 0.00, NULL),
(17, 10, 'Side Part', 0.00, NULL),
(18, 12, 'Mid Fade', 0.00, NULL),
(19, 12, 'Low Fade', 0.00, NULL),
(20, 12, 'Taper', 0.00, NULL),
(21, 12, 'Side Part', 0.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `web_users`
--

CREATE TABLE `web_users` (
  `email` varchar(255) NOT NULL,
  `user_type` enum('admin','barber','customer') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `web_users`
--

INSERT INTO `web_users` (`email`, `user_type`) VALUES
('Adan@gmail.com', 'barber'),
('admin@gsbarbershop.com', 'admin'),
('Aziziazimm@gmail.com', 'customer'),
('gamersamar3@gmail.com', 'customer'),
('Irsyaduddn@gmail.com', 'customer'),
('Ishak@gmail.com', 'barber'),
('sahak@gmail.com', 'barber');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `addon_services`
--
ALTER TABLE `addon_services`
  ADD PRIMARY KEY (`addon_id`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_email`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`appointment_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `barber_id` (`barber_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `barbers`
--
ALTER TABLE `barbers`
  ADD PRIMARY KEY (`barber_id`),
  ADD UNIQUE KEY `barber_email` (`barber_email`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `customer_email` (`customer_email`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `appointment_id` (`appointment_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `barber_id` (`barber_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `appointment_id` (`appointment_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`service_id`);

--
-- Indexes for table `service_addons`
--
ALTER TABLE `service_addons`
  ADD PRIMARY KEY (`service_id`,`addon_id`),
  ADD KEY `addon_id` (`addon_id`);

--
-- Indexes for table `service_variations`
--
ALTER TABLE `service_variations`
  ADD PRIMARY KEY (`variation_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `web_users`
--
ALTER TABLE `web_users`
  ADD PRIMARY KEY (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `addon_services`
--
ALTER TABLE `addon_services`
  MODIFY `addon_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=338;

--
-- AUTO_INCREMENT for table `barbers`
--
ALTER TABLE `barbers`
  MODIFY `barber_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `service_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `service_variations`
--
ALTER TABLE `service_variations`
  MODIFY `variation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`barber_id`) REFERENCES `barbers` (`barber_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_3` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_4` FOREIGN KEY (`haircut_id`) REFERENCES `haircuts` (`haircut_id`) ON DELETE SET NULL;

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feedback_ibfk_3` FOREIGN KEY (`barber_id`) REFERENCES `barbers` (`barber_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feedback_ibfk_4` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`);

--
-- Constraints for table `service_addons`
--
ALTER TABLE `service_addons`
  ADD CONSTRAINT `service_addons_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `service_addons_ibfk_2` FOREIGN KEY (`addon_id`) REFERENCES `addon_services` (`addon_id`) ON DELETE CASCADE;

--
-- Constraints for table `service_variations`
--
ALTER TABLE `service_variations`
  ADD CONSTRAINT `service_variations_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
