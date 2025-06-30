-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jun 30, 2025 at 03:18 AM
-- Server version: 10.11.11-MariaDB-ubu2204
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `amaz_aaaahgh`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_balance`
--

CREATE TABLE `admin_balance` (
  `id` int(11) NOT NULL,
  `balance` int(11) NOT NULL DEFAULT 0,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_balance`
--

INSERT INTO `admin_balance` (`id`, `balance`, `updated_at`) VALUES
(1, 899000, '2025-06-29 15:08:45');

-- --------------------------------------------------------

--
-- Table structure for table `admin_control`
--

CREATE TABLE `admin_control` (
  `control_key` varchar(50) NOT NULL,
  `control_value` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_control`
--

INSERT INTO `admin_control` (`control_key`, `control_value`) VALUES
('win_ratio', '30/70');

-- --------------------------------------------------------

--
-- Table structure for table `admin_money_history`
--

CREATE TABLE `admin_money_history` (
  `id` int(11) NOT NULL,
  `type` enum('deposit','withdraw') NOT NULL,
  `amount` int(11) NOT NULL,
  `balance` int(11) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_money_history`
--

INSERT INTO `admin_money_history` (`id`, `type`, `amount`, `balance`, `created_at`) VALUES
(1, 'deposit', 800000, 920000, '2025-06-20 03:10:45'),
(2, 'deposit', 500000, 1010000, '2025-06-26 20:43:31'),
(3, 'withdraw', 20000, 990000, '2025-06-26 20:43:46'),
(4, 'withdraw', 20000, 970000, '2025-06-27 08:37:21'),
(5, 'withdraw', 30000, 940000, '2025-06-27 08:38:12'),
(6, 'withdraw', 50000, 890000, '2025-06-27 10:08:31'),
(7, 'deposit', 40000, 930000, '2025-06-27 10:10:01'),
(8, 'withdraw', 20000, 910000, '2025-06-28 02:48:35'),
(9, 'withdraw', 50000, 860000, '2025-06-28 16:49:48'),
(10, 'withdraw', 8000, 852000, '2025-06-29 13:27:18'),
(11, 'deposit', 7000, 859000, '2025-06-29 14:35:41'),
(12, 'deposit', 20000, 879000, '2025-06-29 14:41:38'),
(13, 'deposit', 20000, 899000, '2025-06-29 15:08:45');

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `phone`, `password_hash`, `created_at`) VALUES
(1, '09681919489', '$2y$10$b7PP6zKRpcj92Cn2bNfjEeJCQ/r2dytJeu6NqyZac4qjCrj5Q2QlO', '2025-06-19 13:25:33');

-- --------------------------------------------------------

--
-- Table structure for table `bank_accounts`
--

CREATE TABLE `bank_accounts` (
  `id` int(11) NOT NULL,
  `bank_type` varchar(10) NOT NULL,
  `account_number` varchar(32) NOT NULL,
  `show_user` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bank_accounts`
--

INSERT INTO `bank_accounts` (`id`, `bank_type`, `account_number`, `show_user`, `created_at`) VALUES
(1, 'Kpay', '09771601896', 1, '2025-06-28 01:43:32'),
(2, 'Wave', '09771601896', 1, '2025-06-28 01:43:42');

-- --------------------------------------------------------

--
-- Table structure for table `bets`
--

CREATE TABLE `bets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `number` varchar(10) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `draw_time` time NOT NULL,
  `draw_date` date NOT NULL,
  `status` enum('pending','won','lost') NOT NULL DEFAULT 'pending',
  `winnings` decimal(15,2) DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp(),
  `round_no` int(11) DEFAULT 0,
  `bet_animals` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bets`
--

INSERT INTO `bets` (`id`, `user_id`, `number`, `amount`, `draw_time`, `draw_date`, `status`, `winnings`, `created_at`, `round_no`, `bet_animals`) VALUES
(1, 2, '12345', 1000.00, '16:00:00', '2025-06-28', 'won', 1500.00, '2025-06-28 16:05:16', 1, '{\"chicken\":500,\"fish\":500}');

-- --------------------------------------------------------

--
-- Table structure for table `bet_history`
--

CREATE TABLE `bet_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `round_no` int(11) DEFAULT NULL,
  `animal` varchar(20) DEFAULT NULL,
  `bet_amount` int(11) DEFAULT NULL,
  `bet_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bet_records`
--

CREATE TABLE `bet_records` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_time` time NOT NULL DEFAULT '00:00:00',
  `entry_time` time NOT NULL,
  `bet_time` varchar(20) NOT NULL,
  `numbers` text NOT NULL,
  `amount` int(11) NOT NULL,
  `total` int(11) NOT NULL,
  `date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bet_records`
--

INSERT INTO `bet_records` (`id`, `user_id`, `session_time`, `entry_time`, `bet_time`, `numbers`, `amount`, `total`, `date`) VALUES
(1, 4, '00:00:00', '00:00:00', '08:20:35', '[\"02\",\"03\",\"09\",\"10\",\"06\",\"07\",\"08\",\"04\",\"26\",\"27\",\"14\",\"15\",\"20\"]', 100, 2100, '2025-06-30');

-- --------------------------------------------------------

--
-- Table structure for table `closing_dates`
--

CREATE TABLE `closing_dates` (
  `id` int(11) NOT NULL,
  `closing_date` date NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contacts`
--

CREATE TABLE `contacts` (
  `id` int(11) NOT NULL,
  `type` varchar(16) NOT NULL,
  `phone` varchar(32) DEFAULT NULL,
  `username` varchar(64) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contacts`
--

INSERT INTO `contacts` (`id`, `type`, `phone`, `username`, `active`) VALUES
(1, 'viber', '09987654321', NULL, 1),
(2, 'telegram', '09981234567', NULL, 1),
(3, 'telegram', NULL, 'azm2dsupport', 1);

-- --------------------------------------------------------

--
-- Table structure for table `deposits`
--

CREATE TABLE `deposits` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `amount` int(11) DEFAULT NULL,
  `screenshot` varchar(255) DEFAULT NULL,
  `txid` varchar(20) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp(),
  `admin_note` varchar(255) DEFAULT NULL,
  `type` varchar(10) NOT NULL DEFAULT 'kpay'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deposits`
--

INSERT INTO `deposits` (`id`, `user_id`, `amount`, `screenshot`, `txid`, `status`, `created_at`, `admin_note`, `type`) VALUES
(15, 1, 20000, NULL, '675656', 'rejected', '2025-06-27 07:24:47', NULL, 'kpay'),
(16, 1, 20000, NULL, '565656', 'rejected', '2025-06-27 07:25:08', NULL, 'kpay'),
(17, 2, 20000, NULL, '343434', 'approved', '2025-06-27 08:37:03', NULL, 'kpay'),
(18, 2, 30000, NULL, '787979', 'approved', '2025-06-27 08:38:01', NULL, 'kpay'),
(19, 2, 50000, NULL, '565656', 'approved', '2025-06-27 10:08:07', NULL, 'kpay'),
(20, 2, 50000, NULL, '890890', 'rejected', '2025-06-27 15:51:07', NULL, 'wave');

-- --------------------------------------------------------

--
-- Table structure for table `draw_results`
--

CREATE TABLE `draw_results` (
  `id` int(11) NOT NULL,
  `draw_time` time NOT NULL,
  `draw_date` date NOT NULL,
  `number` varchar(10) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fake_winners`
--

CREATE TABLE `fake_winners` (
  `id` int(11) NOT NULL,
  `winner_name` varchar(50) NOT NULL,
  `animal_key` varchar(20) NOT NULL,
  `animal_mm` varchar(20) NOT NULL,
  `prize` int(11) NOT NULL,
  `win_date` date NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `key` varchar(64) NOT NULL,
  `value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `key`, `value`) VALUES
(1, 'kpay_phone', '09260214770'),
(2, 'wave_phone', '09XXXXXXXXX'),
(3, 'kpay_admin_phone', '09xxxxxxxxx'),
(4, 'wave_admin_phone', '09yyyyyyyyy');

-- --------------------------------------------------------

--
-- Table structure for table `slot_bets`
--

CREATE TABLE `slot_bets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `bet_amount` int(11) NOT NULL,
  `bet_animals` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `win_animals` text DEFAULT NULL,
  `payout` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `slot_results`
--

CREATE TABLE `slot_results` (
  `id` int(11) NOT NULL,
  `slot1` varchar(32) NOT NULL,
  `slot2` varchar(32) NOT NULL,
  `slot3` varchar(32) NOT NULL,
  `result_time` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `slot_rounds`
--

CREATE TABLE `slot_rounds` (
  `id` int(11) NOT NULL,
  `round_no` int(11) NOT NULL,
  `slot1` varchar(32) NOT NULL,
  `slot2` varchar(32) NOT NULL,
  `slot3` varchar(32) NOT NULL,
  `draw_time` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `slot_rounds`
--

INSERT INTO `slot_rounds` (`id`, `round_no`, `slot1`, `slot2`, `slot3`, `draw_time`, `created_at`) VALUES
(1, 9727186, 'chicken', 'tiger', 'shrimp', '2025-06-25 23:21:00', '2025-06-25 23:19:02'),
(2, 9727187, 'chicken', 'tiger', 'turtle', '2025-06-25 23:24:00', '2025-06-25 23:21:02'),
(3, 9727188, 'chicken', 'tiger', 'fish', '2025-06-25 23:27:00', '2025-06-25 23:24:01'),
(4, 9727189, 'chicken', 'shrimp', 'turtle', '2025-06-25 23:30:00', '2025-06-25 23:27:09'),
(5, 9727191, 'chicken', 'elephant', 'shrimp', '2025-06-25 23:36:00', '2025-06-25 23:34:18'),
(6, 9727192, 'chicken', 'tiger', 'turtle', '2025-06-25 23:39:00', '2025-06-25 23:36:03'),
(7, 9727193, 'tiger', 'shrimp', 'turtle', '2025-06-25 23:42:00', '2025-06-25 23:39:01'),
(8, 9727194, 'chicken', 'shrimp', 'fish', '2025-06-25 23:45:00', '2025-06-25 23:42:02'),
(9, 9727199, 'chicken', 'elephant', 'turtle', '2025-06-26 00:00:00', '2025-06-25 23:58:16'),
(10, 9728268, 'chicken', 'elephant', 'tiger', '2025-06-28 05:27:00', '2025-06-28 05:24:42'),
(11, 9728276, 'tiger', 'shrimp', 'fish', '2025-06-28 05:51:00', '2025-06-28 05:49:26'),
(12, 9728285, 'chicken', 'elephant', 'shrimp', '2025-06-28 06:18:00', '2025-06-28 06:15:59'),
(13, 9728318, 'elephant', 'tiger', 'shrimp', '2025-06-28 07:57:00', '2025-06-28 07:55:16'),
(14, 9728329, 'chicken', 'elephant', 'tiger', '2025-06-28 08:30:00', '2025-06-28 08:28:10'),
(15, 9728339, 'elephant', 'turtle', 'fish', '2025-06-28 09:00:00', '2025-06-28 08:57:13'),
(16, 9728343, 'elephant', 'turtle', 'fish', '2025-06-28 09:12:00', '2025-06-28 09:10:48'),
(17, 9728351, 'chicken', 'elephant', 'fish', '2025-06-28 09:36:00', '2025-06-28 09:33:42'),
(18, 9728385, 'chicken', 'shrimp', 'fish', '2025-06-28 11:18:00', '2025-06-28 11:17:51'),
(19, 9728386, 'chicken', 'tiger', 'fish', '2025-06-28 11:21:00', '2025-06-28 11:18:01'),
(20, 9728387, 'chicken', 'tiger', 'shrimp', '2025-06-28 11:24:00', '2025-06-28 11:21:23'),
(21, 9728388, 'chicken', 'elephant', 'turtle', '2025-06-28 11:27:00', '2025-06-28 11:24:02'),
(22, 9728389, 'tiger', 'turtle', 'fish', '2025-06-28 11:30:00', '2025-06-28 11:27:59'),
(23, 9728390, 'chicken', 'elephant', 'turtle', '2025-06-28 11:33:00', '2025-06-28 11:30:48'),
(24, 9728391, 'chicken', 'elephant', 'shrimp', '2025-06-28 11:36:00', '2025-06-28 11:33:02'),
(25, 9728392, 'elephant', 'tiger', 'fish', '2025-06-28 11:39:00', '2025-06-28 11:36:56'),
(26, 9728401, 'chicken', 'shrimp', 'fish', '2025-06-28 12:06:00', '2025-06-28 12:03:08'),
(27, 244, 'tiger', 'turtle', 'fish', '2025-06-28 12:15:00', '2025-06-28 12:12:54'),
(28, 245, 'shrimp', 'turtle', 'fish', '2025-06-28 12:18:00', '2025-06-28 12:15:02'),
(29, 247, 'chicken', 'elephant', 'turtle', '2025-06-28 12:24:00', '2025-06-28 12:22:37'),
(30, 248, 'chicken', 'turtle', 'fish', '2025-06-28 12:27:00', '2025-06-28 12:24:02'),
(31, 249, 'elephant', 'shrimp', 'turtle', '2025-06-28 12:30:00', '2025-06-28 12:27:03'),
(32, 251, 'chicken', 'turtle', 'fish', '2025-06-28 12:36:00', '2025-06-28 12:35:15'),
(33, 252, 'tiger', 'shrimp', 'fish', '2025-06-28 12:39:00', '2025-06-28 12:38:26'),
(34, 253, 'elephant', 'tiger', 'turtle', '2025-06-28 12:42:00', '2025-06-28 12:39:02'),
(35, 254, 'elephant', 'turtle', 'fish', '2025-06-28 12:45:00', '2025-06-28 12:44:32'),
(36, 255, 'chicken', 'tiger', 'fish', '2025-06-28 12:48:00', '2025-06-28 12:45:01'),
(37, 257, 'chicken', 'tiger', 'shrimp', '2025-06-28 12:54:00', '2025-06-28 12:52:45'),
(38, 258, 'tiger', 'shrimp', 'turtle', '2025-06-28 12:57:00', '2025-06-28 12:54:01'),
(39, 261, 'chicken', 'tiger', 'shrimp', '2025-06-28 13:06:00', '2025-06-28 13:03:51'),
(40, 262, 'shrimp', 'turtle', 'fish', '2025-06-28 13:09:00', '2025-06-28 13:06:01'),
(41, 263, 'chicken', 'tiger', 'fish', '2025-06-28 13:12:00', '2025-06-28 13:10:15'),
(42, 264, 'elephant', 'tiger', 'shrimp', '2025-06-28 13:15:00', '2025-06-28 13:12:02'),
(43, 266, 'chicken', 'elephant', 'shrimp', '2025-06-28 13:21:00', '2025-06-28 13:20:00'),
(44, 267, 'chicken', 'shrimp', 'turtle', '2025-06-28 13:24:00', '2025-06-28 13:21:02'),
(45, 268, 'elephant', 'tiger', 'turtle', '2025-06-28 13:27:00', '2025-06-28 13:24:51'),
(46, 269, 'elephant', 'shrimp', 'fish', '2025-06-28 13:30:00', '2025-06-28 13:27:01'),
(47, 270, 'tiger', 'shrimp', 'fish', '2025-06-28 13:33:00', '2025-06-28 13:30:01'),
(48, 271, 'elephant', 'turtle', 'fish', '2025-06-28 13:36:00', '2025-06-28 13:34:00'),
(49, 272, 'elephant', 'tiger', 'fish', '2025-06-28 13:39:00', '2025-06-28 13:37:14'),
(50, 273, 'chicken', 'shrimp', 'turtle', '2025-06-28 13:42:00', '2025-06-28 13:39:02'),
(51, 274, 'chicken', 'elephant', 'shrimp', '2025-06-28 13:45:00', '2025-06-28 13:43:30'),
(52, 275, 'chicken', 'shrimp', 'turtle', '2025-06-28 13:48:00', '2025-06-28 13:45:02'),
(53, 277, 'elephant', 'shrimp', 'fish', '2025-06-28 13:54:00', '2025-06-28 13:52:04'),
(54, 279, 'chicken', 'turtle', 'fish', '2025-06-28 14:00:00', '2025-06-28 13:59:03'),
(55, 280, 'chicken', 'elephant', 'shrimp', '2025-06-28 14:03:00', '2025-06-28 14:00:01'),
(56, 285, 'chicken', 'elephant', 'fish', '2025-06-28 14:18:00', '2025-06-28 14:17:51'),
(57, 286, 'elephant', 'shrimp', 'turtle', '2025-06-28 14:21:00', '2025-06-28 14:18:01'),
(58, 287, 'elephant', 'shrimp', 'turtle', '2025-06-28 14:24:00', '2025-06-28 14:21:02'),
(59, 292, 'chicken', 'tiger', 'fish', '2025-06-28 14:39:00', '2025-06-28 14:38:02'),
(60, 293, 'elephant', 'shrimp', 'fish', '2025-06-28 14:42:00', '2025-06-28 14:39:01'),
(61, 294, 'elephant', 'tiger', 'turtle', '2025-06-28 14:45:00', '2025-06-28 14:42:19'),
(62, 295, 'chicken', 'shrimp', 'fish', '2025-06-28 14:48:00', '2025-06-28 14:45:02'),
(63, 300, 'tiger', 'turtle', 'fish', '2025-06-28 15:03:00', '2025-06-28 15:00:17'),
(64, 301, 'tiger', 'shrimp', 'turtle', '2025-06-28 15:06:00', '2025-06-28 15:03:01'),
(65, 302, 'elephant', 'turtle', 'fish', '2025-06-28 15:09:00', '2025-06-28 15:08:36'),
(66, 303, 'chicken', 'turtle', 'fish', '2025-06-28 15:12:00', '2025-06-28 15:11:29'),
(67, 304, 'chicken', 'tiger', 'turtle', '2025-06-28 15:15:00', '2025-06-28 15:12:01'),
(68, 305, 'chicken', 'elephant', 'turtle', '2025-06-28 15:18:00', '2025-06-28 15:15:27'),
(69, 306, 'chicken', 'turtle', 'fish', '2025-06-28 15:21:00', '2025-06-28 15:18:27'),
(70, 309, 'chicken', 'elephant', 'fish', '2025-06-28 15:30:00', '2025-06-28 15:29:39'),
(71, 310, 'chicken', 'elephant', 'tiger', '2025-06-28 15:33:00', '2025-06-28 15:30:01'),
(72, 311, 'chicken', 'elephant', 'shrimp', '2025-06-28 15:36:00', '2025-06-28 15:33:02'),
(73, 312, 'chicken', 'shrimp', 'fish', '2025-06-28 15:39:00', '2025-06-28 15:37:06'),
(74, 313, 'chicken', 'tiger', 'shrimp', '2025-06-28 15:42:00', '2025-06-28 15:39:02'),
(75, 314, 'tiger', 'shrimp', 'turtle', '2025-06-28 15:45:00', '2025-06-28 15:42:40'),
(76, 318, 'chicken', 'elephant', 'shrimp', '2025-06-28 15:57:00', '2025-06-28 15:56:32'),
(77, 319, 'chicken', 'tiger', 'shrimp', '2025-06-28 16:00:00', '2025-06-28 15:57:01'),
(78, 322, 'elephant', 'shrimp', 'turtle', '2025-06-28 16:09:00', '2025-06-28 16:07:26'),
(79, 323, 'chicken', 'shrimp', 'turtle', '2025-06-28 16:12:00', '2025-06-28 16:09:02'),
(80, 326, 'chicken', 'shrimp', 'fish', '2025-06-28 16:21:00', '2025-06-28 16:18:20'),
(81, 327, 'elephant', 'turtle', 'fish', '2025-06-28 16:24:00', '2025-06-28 16:21:02'),
(82, 330, 'tiger', 'turtle', 'fish', '2025-06-28 16:33:00', '2025-06-28 16:30:48'),
(83, 331, 'chicken', 'shrimp', 'turtle', '2025-06-28 16:36:00', '2025-06-28 16:33:04'),
(84, 332, 'elephant', 'tiger', 'turtle', '2025-06-28 16:39:00', '2025-06-28 16:37:44'),
(85, 333, 'chicken', 'elephant', 'tiger', '2025-06-28 16:42:00', '2025-06-28 16:39:01'),
(86, 335, 'chicken', 'shrimp', 'fish', '2025-06-28 16:48:00', '2025-06-28 16:47:21'),
(87, 336, 'tiger', 'shrimp', 'fish', '2025-06-28 16:51:00', '2025-06-28 16:50:17'),
(88, 337, 'shrimp', 'turtle', 'fish', '2025-06-28 16:54:00', '2025-06-28 16:52:27'),
(89, 343, 'elephant', 'tiger', 'fish', '2025-06-28 17:12:00', '2025-06-28 17:11:41'),
(90, 344, 'chicken', 'shrimp', 'turtle', '2025-06-28 17:15:00', '2025-06-28 17:12:02'),
(91, 345, 'shrimp', 'turtle', 'fish', '2025-06-28 17:18:00', '2025-06-28 17:15:02'),
(92, 376, 'chicken', 'shrimp', 'fish', '2025-06-28 18:51:00', '2025-06-28 18:49:28'),
(93, 377, 'tiger', 'shrimp', 'turtle', '2025-06-28 18:54:00', '2025-06-28 18:53:22'),
(94, 378, 'chicken', 'elephant', 'fish', '2025-06-28 18:57:00', '2025-06-28 18:54:02'),
(95, 379, 'elephant', 'turtle', 'fish', '2025-06-28 19:00:00', '2025-06-28 18:57:01'),
(96, 380, 'elephant', 'tiger', 'fish', '2025-06-28 19:03:00', '2025-06-28 19:01:31'),
(97, 381, 'shrimp', 'turtle', 'fish', '2025-06-28 19:06:00', '2025-06-28 19:04:13'),
(98, 382, 'chicken', 'shrimp', 'fish', '2025-06-28 19:09:00', '2025-06-28 19:06:02'),
(99, 383, 'chicken', 'turtle', 'fish', '2025-06-28 19:12:00', '2025-06-28 19:11:42'),
(100, 384, 'chicken', 'elephant', 'tiger', '2025-06-28 19:15:00', '2025-06-28 19:12:05'),
(101, 385, 'chicken', 'elephant', 'tiger', '2025-06-28 19:18:00', '2025-06-28 19:16:01'),
(102, 386, 'tiger', 'shrimp', 'turtle', '2025-06-28 19:21:00', '2025-06-28 19:18:02'),
(103, 387, 'elephant', 'shrimp', 'fish', '2025-06-28 19:24:00', '2025-06-28 19:23:54'),
(104, 388, 'chicken', 'elephant', 'shrimp', '2025-06-28 19:27:00', '2025-06-28 19:24:01'),
(105, 389, 'tiger', 'shrimp', 'turtle', '2025-06-28 19:30:00', '2025-06-28 19:27:13'),
(106, 390, 'elephant', 'shrimp', 'fish', '2025-06-28 19:33:00', '2025-06-28 19:31:44'),
(107, 391, 'chicken', 'shrimp', 'turtle', '2025-06-28 19:36:00', '2025-06-28 19:33:04'),
(108, 405, 'elephant', 'shrimp', 'turtle', '2025-06-28 20:18:00', '2025-06-28 20:15:24'),
(109, 406, 'elephant', 'shrimp', 'turtle', '2025-06-28 20:21:00', '2025-06-28 20:18:02'),
(110, 408, 'chicken', 'tiger', 'fish', '2025-06-28 20:27:00', '2025-06-28 20:24:06'),
(111, 409, 'elephant', 'turtle', 'fish', '2025-06-28 20:30:00', '2025-06-28 20:27:02'),
(112, 416, 'elephant', 'shrimp', 'fish', '2025-06-28 20:51:00', '2025-06-28 20:50:30'),
(113, 418, 'elephant', 'turtle', 'fish', '2025-06-28 20:57:00', '2025-06-28 20:56:56'),
(114, 419, 'elephant', 'tiger', 'turtle', '2025-06-28 21:00:00', '2025-06-28 20:57:01'),
(115, 422, 'elephant', 'tiger', 'fish', '2025-06-28 21:09:00', '2025-06-28 21:06:24'),
(116, 424, 'elephant', 'tiger', 'turtle', '2025-06-28 21:15:00', '2025-06-28 21:14:27'),
(117, 436, 'elephant', 'shrimp', 'fish', '2025-06-28 21:51:00', '2025-06-28 21:49:14'),
(118, 437, 'elephant', 'shrimp', 'fish', '2025-06-28 21:54:00', '2025-06-28 21:51:02'),
(119, 438, 'chicken', 'elephant', 'turtle', '2025-06-28 21:57:00', '2025-06-28 21:54:02'),
(120, 444, 'elephant', 'turtle', 'fish', '2025-06-28 22:15:00', '2025-06-28 22:14:52'),
(121, 449, 'elephant', 'shrimp', 'fish', '2025-06-28 22:30:00', '2025-06-28 22:28:53'),
(122, 450, 'elephant', 'tiger', 'turtle', '2025-06-28 22:33:00', '2025-06-28 22:30:03'),
(123, 452, 'chicken', 'shrimp', 'fish', '2025-06-28 22:39:00', '2025-06-28 22:36:16'),
(124, 454, 'elephant', 'shrimp', 'turtle', '2025-06-28 22:45:00', '2025-06-28 22:43:47'),
(125, 455, 'chicken', 'shrimp', 'turtle', '2025-06-28 22:48:00', '2025-06-28 22:45:01'),
(126, 457, 'shrimp', 'turtle', 'fish', '2025-06-28 22:54:00', '2025-06-28 22:52:05'),
(127, 458, 'chicken', 'turtle', 'fish', '2025-06-28 22:57:00', '2025-06-28 22:54:01'),
(128, 461, 'chicken', 'elephant', 'fish', '2025-06-28 23:06:00', '2025-06-28 23:05:38'),
(129, 462, 'chicken', 'shrimp', 'fish', '2025-06-28 23:09:00', '2025-06-28 23:06:01'),
(130, 463, 'shrimp', 'turtle', 'fish', '2025-06-28 23:12:00', '2025-06-28 23:09:02'),
(131, 665, 'shrimp', 'turtle', 'fish', '2025-06-29 09:18:00', '2025-06-29 09:17:38'),
(132, 666, 'elephant', 'tiger', 'turtle', '2025-06-29 09:21:00', '2025-06-29 09:18:01'),
(133, 667, 'chicken', 'shrimp', 'turtle', '2025-06-29 09:24:00', '2025-06-29 09:21:28'),
(134, 678, 'chicken', 'tiger', 'turtle', '2025-06-29 09:57:00', '2025-06-29 09:56:22'),
(135, 679, 'elephant', 'shrimp', 'fish', '2025-06-29 10:00:00', '2025-06-29 09:57:00'),
(136, 680, 'elephant', 'shrimp', 'turtle', '2025-06-29 10:03:00', '2025-06-29 10:00:03'),
(137, 681, 'chicken', 'elephant', 'tiger', '2025-06-29 10:06:00', '2025-06-29 10:03:02'),
(138, 682, 'elephant', 'turtle', 'fish', '2025-06-29 10:09:00', '2025-06-29 10:06:01'),
(139, 696, 'chicken', 'elephant', 'tiger', '2025-06-29 10:51:00', '2025-06-29 10:49:40'),
(140, 707, 'tiger', 'turtle', 'fish', '2025-06-29 11:24:00', '2025-06-29 11:21:09'),
(141, 727, 'elephant', 'tiger', 'turtle', '2025-06-29 12:24:00', '2025-06-29 12:22:47'),
(142, 731, 'tiger', 'shrimp', 'fish', '2025-06-29 12:36:00', '2025-06-29 12:34:10'),
(143, 739, 'tiger', 'turtle', 'fish', '2025-06-29 13:00:00', '2025-06-29 12:59:38'),
(144, 740, 'chicken', 'elephant', 'fish', '2025-06-29 13:03:00', '2025-06-29 13:00:01'),
(145, 741, 'elephant', 'shrimp', 'fish', '2025-06-29 13:06:00', '2025-06-29 13:05:44'),
(146, 751, 'chicken', 'elephant', 'fish', '2025-06-29 13:36:00', '2025-06-29 13:33:18'),
(147, 752, 'elephant', 'tiger', 'fish', '2025-06-29 13:39:00', '2025-06-29 13:36:02'),
(148, 753, 'elephant', 'tiger', 'shrimp', '2025-06-29 13:42:00', '2025-06-29 13:39:01'),
(149, 754, 'elephant', 'shrimp', 'fish', '2025-06-29 13:45:00', '2025-06-29 13:42:02'),
(150, 755, 'chicken', 'elephant', 'tiger', '2025-06-29 13:48:00', '2025-06-29 13:45:02'),
(151, 767, 'elephant', 'shrimp', 'turtle', '2025-06-29 14:24:00', '2025-06-29 14:21:13'),
(152, 768, 'elephant', 'shrimp', 'fish', '2025-06-29 14:27:00', '2025-06-29 14:24:02'),
(153, 769, 'chicken', 'shrimp', 'fish', '2025-06-29 14:30:00', '2025-06-29 14:28:19'),
(154, 770, 'elephant', 'tiger', 'shrimp', '2025-06-29 14:33:00', '2025-06-29 14:30:04'),
(155, 771, 'elephant', 'shrimp', 'turtle', '2025-06-29 14:36:00', '2025-06-29 14:33:03'),
(156, 772, 'chicken', 'shrimp', 'fish', '2025-06-29 14:39:00', '2025-06-29 14:36:09'),
(157, 773, 'chicken', 'tiger', 'fish', '2025-06-29 14:42:00', '2025-06-29 14:39:02'),
(158, 774, 'elephant', 'tiger', 'turtle', '2025-06-29 14:45:00', '2025-06-29 14:42:03'),
(159, 777, 'chicken', 'elephant', 'shrimp', '2025-06-29 14:54:00', '2025-06-29 14:52:08'),
(160, 778, 'shrimp', 'turtle', 'fish', '2025-06-29 14:57:00', '2025-06-29 14:54:03');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('deposit','withdrawal') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `method` varchar(20) NOT NULL,
  `status` varchar(20) NOT NULL,
  `note` varchar(255) DEFAULT '',
  `screenshot` varchar(255) DEFAULT '',
  `to_phone` varchar(20) DEFAULT '',
  `created_at` datetime DEFAULT current_timestamp(),
  `icon` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `user_id`, `type`, `amount`, `method`, `status`, `note`, `screenshot`, `to_phone`, `created_at`, `icon`) VALUES
(1, 2, 'deposit', 20000.00, 'kpay', 'approved', '454577', '', '', '2025-06-28 02:40:58', NULL),
(2, 2, 'deposit', 10000.00, 'kpay', 'rejected', '565656', '', '', '2025-06-28 02:58:49', NULL),
(3, 2, 'deposit', 20000.00, 'wave', 'rejected', '565677', '', '', '2025-06-28 04:52:50', NULL),
(4, 2, 'withdrawal', 20000.00, 'wave', 'rejected', '', '', '09798241599', '2025-06-28 04:54:28', NULL),
(5, 4, 'deposit', 50000.00, 'wave', 'approved', '5656t6', '', '', '2025-06-28 16:48:52', NULL),
(6, 7, 'deposit', 8000.00, 'wave', 'approved', '130358', '', '', '2025-06-29 13:16:36', NULL),
(7, 7, 'deposit', 8000.00, 'wave', 'rejected', '130358', '', '', '2025-06-29 13:28:05', NULL),
(8, 7, 'withdrawal', 7000.00, 'wave', 'approved', '', '', '09425600319', '2025-06-29 13:45:51', NULL),
(9, 2, 'withdrawal', 20000.00, 'wave', 'approved', '', '', '09798241599', '2025-06-29 14:40:40', NULL),
(10, 4, 'withdrawal', 20000.00, 'kpay', 'approved', '', '', '09663663663', '2025-06-29 15:08:05', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `balance` decimal(12,2) NOT NULL DEFAULT 1000.00,
  `agent_code` varchar(50) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_disabled` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `phone`, `password_hash`, `balance`, `agent_code`, `created_at`, `updated_at`, `is_disabled`) VALUES
(1, 'Admin', '09681919489', '$2y$10$examplehash', -200.00, 'AGENT01', '2025-06-27 07:21:31', '2025-06-29 23:29:11', 0),
(2, 'á€€á€­á€¯á€•á€¼á€Šá€ºá€·á€…á€¯á€¶', '09798241599', '$2y$10$HQS10QujVbUh1m7eh3mbpuMVQNE3koXDUtpeq7imp.LT7bmFW4nl.', 53700.00, '', '2025-06-23 06:35:35', '2025-06-29 13:32:32', 0),
(3, 'á€™á€»á€­á€¯á€¸á€œá€„á€ºá€¸', '09366366989', '$2y$10$/m9aATu57gRabKREafKM7.JMuu8lz20zmaOfK8xweE2KMDltirZrS', 0.00, '', '2025-06-27 06:43:40', '2025-06-29 12:38:17', 0),
(4, 'â€‹á€…á€±á€¬á€€á€¼á€®á€¸', '09663663663', '$2y$10$ZBgSyJCPxu7iN9Uv3iu3Luq8JpGq0mVUCxQ/qtd0t/Ayz19iv9KLS', 24100.00, '', '2025-06-28 16:48:25', '2025-06-30 01:50:35', 0),
(5, 'á€…á€”á€ºá€¸â€‹á€›á€±', '09636399663', '$2y$10$sAuepwyRFPHlt7nwzNR6le28UYmNbQfbYx5QpddYKqfBtMWayxNFW', 200.00, '', '2025-06-28 23:05:25', '2025-06-29 23:51:21', 0),
(6, 'Aung Myint Myat', '09790343606', '$2y$10$MFBGlihe28vUpHLVHuAY2ewrSTlzssXbRDZ9sKK.VJwjN593rWP0K', 1000.00, '', '2025-06-29 12:57:30', '2025-06-29 12:57:30', 0),
(7, 'KoKo', '09425600319', '$2y$10$lsRhmvaOJf/vjEnbJP6lD.yDDYdm5eqkk.vy5/NTkwDsYSTOYL9Pi', 0.00, '', '2025-06-29 13:10:54', '2025-06-29 14:49:48', 0),
(8, 'Chan myae mgmg', '09970421725', '$2y$10$JmsG4jAmlvBdo4vbismU9OVdXS0t28shKKdo5ugbDMO4sBXsVybWe', 1000.00, '', '2025-06-29 13:21:09', '2025-06-29 13:21:09', 0),
(9, 'Zenus', '09765140536', '$2y$10$IJl5m1BDsTYQXhg7o42FK.2K32Un97LaZi41eZcLJ4G4f6jm6XwBG', 0.00, '', '2025-06-29 14:17:59', '2025-06-29 14:21:48', 0),
(10, 'Zenith', '09769303868', '$2y$10$xImHFuPtTneonpClARzT0uhRXK9VoPGmH/QSaiU6Bz3RhRQSG56nW', 0.00, '', '2025-06-29 14:25:41', '2025-06-29 14:31:54', 0),
(11, 'Zenus X', '09760269431', '$2y$10$kzllghEB5CzCNjckTQWoL.QiPzArzHha5JQi0DXHckwCehNE4EGX6', 0.00, '', '2025-06-29 14:37:48', '2025-06-29 14:39:12', 0),
(12, 'á€¡á€±á€¬á€„á€ºá€€á€»á€±á€¬á€ºá€…á€­á€¯á€¸', '09665409494', '$2y$10$pFYBcdJo17Dt1lduUIL89.usNw636PS6o6CKKXCUifRJLn5BBYK22', 1000.00, '', '2025-06-29 17:12:57', '2025-06-29 17:12:57', 0),
(13, 'Sawhtikeaung', '09458298603', '$2y$10$AEAJd83WB4kQpJEeIgx4Lul3Ur6y6rQRyLx6au0lKI7Jt0acOQfhi', 1000.00, '', '2025-06-30 03:13:40', '2025-06-30 03:13:40', 0);

-- --------------------------------------------------------

--
-- Table structure for table `user_bets`
--

CREATE TABLE `user_bets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `number` varchar(2) NOT NULL,
  `amount` int(11) NOT NULL,
  `session_time` time NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `bet_time` time NOT NULL DEFAULT curtime(),
  `entry_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_bets`
--

INSERT INTO `user_bets` (`id`, `user_id`, `number`, `amount`, `session_time`, `created_at`, `bet_time`, `entry_time`) VALUES
(1, 4, '07', 100, '11:00:00', '2025-06-29 23:07:01', '01:20:00', '2025-06-30 01:32:39'),
(2, 4, '08', 100, '11:00:00', '2025-06-29 23:07:01', '01:20:00', '2025-06-30 01:32:39'),
(3, 5, '03', 100, '12:01:00', '2025-06-29 23:14:24', '01:20:00', '2025-06-30 01:32:39'),
(4, 5, '09', 100, '12:01:00', '2025-06-29 23:14:24', '01:20:00', '2025-06-30 01:32:39'),
(5, 5, '03', 100, '12:01:00', '2025-06-29 23:27:01', '01:20:00', '2025-06-30 01:32:39'),
(6, 5, '04', 100, '12:01:00', '2025-06-29 23:27:01', '01:20:00', '2025-06-30 01:32:39'),
(7, 5, '03', 100, '12:01:00', '2025-06-29 23:36:50', '01:20:00', '2025-06-30 01:32:39'),
(8, 5, '09', 100, '12:01:00', '2025-06-29 23:36:50', '01:20:00', '2025-06-30 01:32:39'),
(9, 5, '00', 100, '11:00:00', '2025-06-29 23:51:21', '01:20:00', '2025-06-30 01:32:39'),
(10, 5, '01', 100, '11:00:00', '2025-06-29 23:51:21', '01:20:00', '2025-06-30 01:32:39'),
(11, 4, '01', 500, '11:00:00', '2025-06-30 00:30:15', '01:20:00', '2025-06-30 01:32:39'),
(12, 4, '02', 500, '11:00:00', '2025-06-30 00:30:15', '01:20:00', '2025-06-30 01:32:39'),
(13, 4, '02', 100, '11:00:00', '2025-06-30 00:30:44', '01:20:00', '2025-06-30 01:32:39'),
(14, 4, '03', 100, '11:00:00', '2025-06-30 00:30:44', '01:20:00', '2025-06-30 01:32:39'),
(15, 4, '02', 100, '11:00:00', '2025-06-30 00:47:35', '01:20:00', '2025-06-30 01:32:39'),
(16, 4, '03', 100, '11:00:00', '2025-06-30 00:47:35', '01:20:00', '2025-06-30 01:32:39'),
(17, 4, '02', 100, '11:00:00', '2025-06-30 00:55:38', '01:20:00', '2025-06-30 01:32:39'),
(18, 4, '03', 100, '11:00:00', '2025-06-30 00:55:38', '01:20:00', '2025-06-30 01:32:39'),
(19, 4, '09', 100, '07:34:43', '2025-06-30 01:04:43', '01:20:00', '2025-06-30 01:32:39'),
(20, 4, '10', 100, '07:34:43', '2025-06-30 01:04:43', '01:20:00', '2025-06-30 01:32:39'),
(21, 4, '06', 100, '07:35:14', '2025-06-30 01:05:14', '01:20:00', '2025-06-30 01:32:39'),
(22, 4, '07', 100, '07:35:14', '2025-06-30 01:05:14', '01:20:00', '2025-06-30 01:32:39'),
(23, 4, '08', 100, '07:35:14', '2025-06-30 01:05:14', '01:20:00', '2025-06-30 01:32:39'),
(28, 4, '03', 100, '07:51:30', '2025-06-30 01:21:30', '01:21:30', '2025-06-30 01:32:39'),
(29, 4, '04', 100, '07:51:30', '2025-06-30 01:21:30', '01:21:30', '2025-06-30 01:32:39'),
(34, 4, '02', 100, '08:05:44', '2025-06-30 01:35:44', '01:35:44', '2025-06-30 01:35:44'),
(35, 4, '03', 100, '08:05:44', '2025-06-30 01:35:44', '01:35:44', '2025-06-30 01:35:44'),
(36, 4, '26', 100, '08:06:16', '2025-06-30 01:36:16', '01:36:16', '2025-06-30 01:36:16'),
(37, 4, '27', 100, '08:06:16', '2025-06-30 01:36:16', '01:36:16', '2025-06-30 01:36:16'),
(38, 4, '02', 100, '08:13:55', '2025-06-30 01:43:55', '01:43:55', '2025-06-30 08:13:55'),
(39, 4, '03', 100, '08:13:55', '2025-06-30 01:43:55', '01:43:55', '2025-06-30 08:13:55'),
(40, 4, '03', 100, '08:14:17', '2025-06-30 01:44:17', '01:44:17', '2025-06-30 08:14:17'),
(41, 4, '04', 100, '08:14:17', '2025-06-30 01:44:17', '01:44:17', '2025-06-30 08:14:17'),
(42, 4, '14', 100, '08:14:43', '2025-06-30 01:44:43', '01:44:43', '2025-06-30 08:14:43'),
(43, 4, '15', 100, '08:14:43', '2025-06-30 01:44:43', '01:44:43', '2025-06-30 08:14:43'),
(44, 4, '20', 100, '08:20:35', '2025-06-30 01:50:35', '01:50:35', '2025-06-30 08:20:35'),
(45, 4, '26', 100, '08:20:35', '2025-06-30 01:50:35', '01:50:35', '2025-06-30 08:20:35');

-- --------------------------------------------------------

--
-- Table structure for table `user_tokens`
--

CREATE TABLE `user_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_tokens`
--

INSERT INTO `user_tokens` (`id`, `user_id`, `token`, `expires_at`, `created_at`) VALUES
(1, 2, 'ee183aa879c0af899d2f1f3b25d4600554db3f8008802377a67d777ad4a7d2c5', '2025-07-23 00:05:35', '2025-06-23 06:35:35'),
(2, 2, '32de932bb8e9322b07011422d6ef96a5b4477c4e8d72eec193723c779e516d67', '2025-07-23 00:45:25', '2025-06-23 07:15:25'),
(3, 2, '7356f9f5abbc110a6adec5e6c65fdc34322bbaa213d383e9365d9e3a01539df8', '2025-07-23 11:29:40', '2025-06-23 17:59:40'),
(4, 2, '178a8fd83db50c47d76be195c9db021ff71d8a842cef53c4d83acaff1e88ad5e', '2025-07-23 12:20:08', '2025-06-23 18:50:08'),
(5, 2, '7105cb187d5247f89b8a7d7565e9bc44d748d101ac10ee4dca7fe106e062edc4', '2025-07-23 12:20:52', '2025-06-23 18:50:52'),
(6, 2, 'b3889e2d8ad9686b32980ae54b64dc639d3e3b350a266b56463f6a651add0377', '2025-07-23 12:21:30', '2025-06-23 18:51:30'),
(7, 2, 'f95710eb366e725b4d66ff4628f270693f9d05d9addd1f4988a7d5952e425132', '2025-07-23 12:23:05', '2025-06-23 18:53:05'),
(8, 2, 'b46f484d63876b98d658e459d4d7af831a5177014b268465277797e1a4fa31e4', '2025-07-23 12:24:32', '2025-06-23 18:54:32'),
(9, 2, 'f0907b012721ccde1b6a5d73abfb1568416ab54d5b6854f567b149039a5bc0ae', '2025-07-23 13:31:39', '2025-06-23 20:01:39'),
(10, 2, '3fb82201216752faf9fc5367bc436cfa64af05b1b94de0edc41b3db0850f4aea', '2025-07-23 20:24:58', '2025-06-24 02:54:58'),
(11, 2, 'c0b1574b02648526d1580fc53da95b78ca56ac7bc883ec55a306873c28200274', '2025-07-24 09:38:52', '2025-06-24 16:08:52'),
(12, 2, '1ada2e662561070e9d6d772befa9a44b2141d097483f1fc8a3c7314767d5ecc7', '2025-07-24 21:00:25', '2025-06-25 03:30:25'),
(13, 2, '055d1ad6537c0cba8624912733173ab921376cb7cc32f8542e96fa5460635217', '2025-07-24 21:51:52', '2025-06-25 04:21:52'),
(14, 2, '240015880586ce667dd0d9f1821325877d9534d6ffe3cccf016bb71a2561dd3d', '2025-07-24 22:49:20', '2025-06-25 05:19:20'),
(15, 2, 'a19852d57c7b7d4ea2b24cc699361d3ae2e2184fc2d8916f98ab8eed38a462bc', '2025-07-24 23:05:54', '2025-06-25 05:35:54'),
(16, 2, '7d6f8611b19367232a8002f225ca0e9d92d2b04e217389cfa5f4d59b48f28a48', '2025-07-25 00:25:28', '2025-06-25 06:55:28'),
(17, 2, '071cda5be874e6bd91074a06b78d9b0ed6cd55d4140a0218e1d6c563eee32132', '2025-07-25 02:36:02', '2025-06-25 02:36:02'),
(18, 2, '073550ff886296e55df96fb9e9cbf85c139ab9b746eba96c2e4500a905b6c677', '2025-07-25 06:54:22', '2025-06-25 06:54:22'),
(19, 2, '79b008690283b8d0074ce291cebae5827e349dc0414afc9f9e8456b7edd29d33', '2025-07-25 23:46:08', '2025-06-25 23:46:08'),
(20, 2, '635a5cf9c57f83ac948757a37d348fe58940f1405225e28d273e103f0de914d5', '2025-07-26 01:10:51', '2025-06-26 01:10:51'),
(21, 2, '6b4fa8b59b84736f38b8da095213c1770fe932e95f77950610bb1f8680d10b86', '2025-07-26 01:43:17', '2025-06-26 01:43:17'),
(22, 2, 'ec2d3f5d062792c2b28caf614e73e925d7369ebd315e21a28bd32e4dfa6488eb', '2025-07-26 02:40:00', '2025-06-26 02:40:00'),
(23, 2, 'c5c0267b5007f854487835fe1fee578878a0154d2de352ade4c0732e616f3165', '2025-07-26 02:45:51', '2025-06-26 02:45:51'),
(24, 2, 'a34610c85dd3cb9c63ff82bf0ab9bb39bf0eb9e6d08cdc0269a3e0fbf9dd781f', '2025-07-26 02:47:36', '2025-06-26 02:47:36'),
(25, 2, 'b06bee1d4bbe5b69b125686af7ceb8192f3f02e017122f9384db78fdbf34743d', '2025-07-26 02:52:57', '2025-06-26 02:52:57'),
(26, 2, '77068e80551e508860cc44475c0de23f38e347bba98add50d965f7ce0a67995a', '2025-07-26 03:27:06', '2025-06-26 03:27:06'),
(27, 2, '377b8fe38bcbfc1715d27cc84781fa96dcca82f43aacf1adbac28477ec0ba600', '2025-07-26 05:08:40', '2025-06-26 05:08:40'),
(28, 2, 'b71420d0e58c64d733ddfab740332b2df93dd00f62db376adf1b0e4bfdcbf40d', '2025-07-26 09:16:11', '2025-06-26 09:16:11'),
(29, 2, '3ece38c805be5021a8d1228176ca0c4a4a966d15f3632cc22389d2fe5ad4e9e2', '2025-07-26 09:37:59', '2025-06-26 09:37:59'),
(30, 2, '8de4dedc06238335289532391e41211e3bc8ad2fba2ad001fbbb51233438e0df', '2025-07-26 11:15:44', '2025-06-26 11:15:44'),
(31, 2, 'b608bc85fac195efc243db75aae0bbfd7daf3aba13dba89fc28e2e1d6a441efe', '2025-07-26 11:16:37', '2025-06-26 11:16:37'),
(32, 2, '69448cd708407edb3cc2bc954d9beb1c5316113751562ed21070554075c1fdf0', '2025-07-26 11:27:25', '2025-06-26 11:27:25'),
(33, 2, '0cfb54dc317843cf261adcc17eeee6d33346b6d4d0389ce1c6184237259cdbbd', '2025-07-26 11:34:37', '2025-06-26 11:34:37'),
(34, 2, '33e2160a41c19d0957d40886d7a50ed635e0c3cee20238337c35c480871dd817', '2025-07-26 12:04:04', '2025-06-26 12:04:04'),
(35, 2, '94d4fbfc6b94ba7f56e615dc542a79180eaccaa19d12e1eba4d367ccdc2d3d2b', '2025-07-26 12:06:04', '2025-06-26 12:06:04'),
(36, 2, '6b513781736816a7259a71c307774f702f202ab77ea285aa6459a8a7e045d23c', '2025-07-26 12:20:48', '2025-06-26 12:20:48'),
(37, 2, '64ee21d041f3101cbc46a2b2779c0483fe548991c7cba68b8c5ce765ad0dda29', '2025-07-26 14:03:45', '2025-06-26 14:03:45'),
(38, 2, '4619a7e189180cd44a7034c08017f55ba62d16c35be66f58413622ddfeecfede', '2025-07-26 15:12:16', '2025-06-26 15:12:16'),
(39, 2, '93881a7b897e4baba0d644e37d19dcf2ba7d323cc04ac04eb6f713649678e593', '2025-07-26 15:20:15', '2025-06-26 15:20:15'),
(40, 2, '12d8fe69c1c48d0be8da77a90cb4b152198ba4af88ef4435e542d0d0478865ee', '2025-07-26 15:34:45', '2025-06-26 15:34:45'),
(41, 2, '8d3b924e2d8f469d2051dadf31daceef81be4ad5e12e9e3eae7c0b27471398f1', '2025-07-26 15:59:19', '2025-06-26 15:59:19'),
(42, 2, 'd532312259b7e3e61711b285b2cf92f6a4643d64304752249d3c04f8fdf84540', '2025-07-26 16:42:51', '2025-06-26 16:42:51'),
(43, 2, '4a8e0304ad46f6f6f4c9cc95e521a133e8b83583a07f99060ced0cb7b56e1aab', '2025-07-26 16:52:03', '2025-06-26 16:52:03'),
(44, 2, '28ddef6ecd542b90f2bfaf2f2d99c774eac6bfe8b9af5f07c8374ec9de1e84cd', '2025-07-26 16:55:46', '2025-06-26 16:55:46'),
(45, 2, '706ea6b104817fde5448b6b7f5dcc8ac20ed58ad1f1b93b785b2b64ee41f8875', '2025-07-26 17:52:19', '2025-06-26 17:52:19'),
(46, 2, '80681a21e1a63047518b422a1355cec539ab1b5ead8aa4eb3008d96a926b6d7d', '2025-07-26 17:53:11', '2025-06-26 17:53:11'),
(47, 2, 'b016478541f9307ef4a4693363372172214c8ef8ea43aaacf40229e953a8d4d4', '2025-07-26 17:54:42', '2025-06-26 17:54:42'),
(48, 2, '6018fd19406269068afa0d13337c993c9320b53cff177024ab004b6278545f17', '2025-07-26 19:24:31', '2025-06-26 19:24:31'),
(49, 2, 'bd5c264c788b857a7f5b3dd91e01b55c3b2a3be6781a9280fb0e74e7fcf336e0', '2025-07-26 19:49:54', '2025-06-26 19:49:54'),
(50, 2, '12cf585cb131db53c460b07e5aaf5dbf91619bc1ea47098159e48a04cc430c85', '2025-07-26 20:30:29', '2025-06-26 20:30:29'),
(51, 2, 'f59e44259aa2881e2cf87b7c6afce4efdbbde20658c7fe1bbfa3115ef365616c', '2025-07-26 22:10:04', '2025-06-26 22:10:04'),
(52, 2, '6e8fdc06db3f1f61fb508e5c710501dc202eb00d2e9c983d67cdfbbe3bd537bf', '2025-07-27 06:35:19', '2025-06-27 06:35:19'),
(53, 2, 'a61727d7275ef28ff4832508049f4ecebf8e031a7985699187bf2b455cecac64', '2025-07-27 06:42:30', '2025-06-27 06:42:30'),
(54, 3, 'bb5f929f32bbf8b0b37c71c044dc6721233647e130522804c66b5d67a2e8e23b', '2025-07-27 06:43:40', '2025-06-27 06:43:40'),
(55, 3, 'e34f1292b1735792216e5fff5184180ccb9dd569bcef539c75581fe9deb5fcf3', '2025-07-27 06:58:47', '2025-06-27 06:58:47'),
(56, 2, '592a3af17ebd963d439a28260c0336a7969583329869249b28841e870d9801d7', '2025-07-27 08:36:45', '2025-06-27 08:36:45'),
(57, 3, 'd65a652eb76310765460817ac3bc032674a7de47e7e545fff7de95c9c2d90d2a', '2025-07-27 09:08:49', '2025-06-27 09:08:49'),
(58, 2, '5bb3b314b26c516e11648dad7048a590a44ca4b91c905b435ec67c714bdf6a76', '2025-07-27 09:20:34', '2025-06-27 09:20:34'),
(59, 2, '8e9eaf37ddbaef60120dc05c7eafb190733b5001cdd6b68d013eae655804a746', '2025-07-27 10:36:25', '2025-06-27 10:36:25'),
(60, 2, 'e1683e46d6ad4c73f09f495aeeb0a949dab769a44a977ef683abc23a8f47cdb5', '2025-07-27 11:20:26', '2025-06-27 11:20:26'),
(61, 2, '2f4b0138370aba5da1c8879782fd0b456e442e4cc5cc63cfb2b1fa295d19cf94', '2025-07-27 13:20:36', '2025-06-27 13:20:36'),
(62, 2, 'e168c008a566129ef82f5ddd9b424d22556275dad44223888b8d451e1e5d7d4c', '2025-07-27 13:26:13', '2025-06-27 13:26:13'),
(63, 2, '3731bf3f50c3810a3feb9f0f8014f846d92902e94e4302b544e76a434948a3b3', '2025-07-27 13:29:34', '2025-06-27 13:29:34'),
(64, 2, '6b6354e2f2aa346fdb2b4b80c07630ca53f9d8eb4ab123749bfe268238c50a29', '2025-07-27 13:31:40', '2025-06-27 13:31:40'),
(65, 2, '71840b80a42ba60807132611b41d693e946fea9cd80006c7d3da8e1d6a222f21', '2025-07-27 14:03:06', '2025-06-27 14:03:06'),
(66, 2, 'a6943a325d3db0f15b391d962ad4b31e23ae69a29c793c9d939ff6b6676ba519', '2025-07-27 14:17:41', '2025-06-27 14:17:41'),
(67, 2, 'c47b4aec852acae24b210add4c488516d18b0a193c5e0b713312980a6b869e6a', '2025-07-27 15:50:22', '2025-06-27 15:50:22'),
(68, 2, 'd60116e34c65883b111b35458d7a312e21ef35f6ffaab89fdd4f80ca8de9e2f4', '2025-07-27 17:17:34', '2025-06-27 17:17:34'),
(69, 2, '46cf17e435873fc2ba0f2a8bb95ad34c96310eb7045b71fdbe98d2e62495966f', '2025-07-27 17:18:20', '2025-06-27 17:18:20'),
(70, 2, 'a3bc8af6c2b58ab1c6173fa0a48650237e8e6ad296b5267a0e068a9565d06cdf', '2025-07-27 17:22:15', '2025-06-27 17:22:15'),
(71, 2, '07edc1954c2f7b540b9cd588b7507956fd0c7e1c62a2ddf7d235f151badee0e2', '2025-07-27 17:49:32', '2025-06-27 17:49:32'),
(72, 2, 'c62b8849d47c4ce20c57e813adfd0026ab21828f9cc8f638dab74b9a7c900d1b', '2025-07-27 17:57:17', '2025-06-27 17:57:17'),
(73, 2, 'cc81e372bb444e6db8e22775e6d465998e5513238ea9dc394cfeaa458adfe1ad', '2025-07-27 18:48:01', '2025-06-27 18:48:01'),
(74, 2, 'a77798e6f47cc813c7756a67138d2b53b7c936af9a4e6816928680e519a2ed43', '2025-07-27 19:02:43', '2025-06-27 19:02:43'),
(75, 2, '62ad3346b3dcdb472c2a26f3fa1460a282e0cb1e85a1920dce8fe38333713182', '2025-07-27 19:03:44', '2025-06-27 19:03:44'),
(76, 2, '36988ae60acc1c32617998b3adccebb238bf6d6b16ff8141c549d5798bdb0e9d', '2025-07-27 20:25:52', '2025-06-27 20:25:52'),
(77, 2, 'eee967b21d42a4e7a07aaf8e123266fe2022dea44c7ff22835aeba3b3250f0cc', '2025-07-27 20:54:12', '2025-06-27 20:54:12'),
(78, 2, 'f3208b72a89d120f3e09ceaa3440ca915a1b0eb4c100e040c4096cc26469176e', '2025-07-27 21:01:10', '2025-06-27 21:01:10'),
(79, 2, '36b5caee8d3726086a587a83dfe8497fd3dafeed1ae935acdea9f52f7f0b351e', '2025-07-27 21:18:33', '2025-06-27 21:18:33'),
(80, 2, '2154e3097c962f9a05fcdb33881a283fe97a4eea67589da4756757fe3de81060', '2025-07-27 21:32:12', '2025-06-27 21:32:12'),
(81, 2, '1805afb9a1432ccafcb9eace8668108026c25cf39bc25f8868e70b37e8e64725', '2025-07-27 21:45:20', '2025-06-27 21:45:20'),
(82, 2, '743d8fb8c119fffd228618476931ff0c86bdb712033d9b4b989985f48382a31e', '2025-07-27 21:50:01', '2025-06-27 21:50:01'),
(83, 2, '5600f6f62b61b46a49069dfd254e626d1b9e1760a2617b17c2db03adbc8368a6', '2025-07-27 22:03:05', '2025-06-27 22:03:05'),
(84, 2, '7bb1c91f39a83080ebdfb419de54fd51a711f3143d3197bfa3e71fc4a4f49b3e', '2025-07-27 22:29:43', '2025-06-27 22:29:43'),
(85, 2, '1adc2049723b0c55b377b729e470ccb65475125968f51ef9b21c805910b00037', '2025-07-27 22:47:42', '2025-06-27 22:47:42'),
(86, 2, '42ae539ca291ddd5a022e85fdc72feffe2759f8ee13c9006f405f96ab87180a4', '2025-07-27 22:57:35', '2025-06-27 22:57:35'),
(87, 2, 'c90d03b362db51de27a4517f675538761288ac617f68e1829e43576c1e176fa6', '2025-07-27 23:04:29', '2025-06-27 23:04:29'),
(88, 2, 'd299045c69e119dbcdd1a43f7d01abd0b5ff0b638f737ad60bfdb4296eb442a2', '2025-07-27 23:11:04', '2025-06-27 23:11:04'),
(89, 2, '49e5bab81b73030c0e8f59b411cebbf963350a2bb139f8ea8b9d16599116f68c', '2025-07-27 23:20:06', '2025-06-27 23:20:06'),
(90, 2, 'd71e9be084ead7ccb0cbbc025c5182322193e597c983b16874ba1f8e8e123cf2', '2025-07-27 23:21:17', '2025-06-27 23:21:17'),
(91, 2, '690c5918411eb64b4b13a635c870fd7fb2bea5f89f3e44ee8036da8b93eee5cc', '2025-07-27 23:25:37', '2025-06-27 23:25:37'),
(92, 2, '1a9efebf295feedce3e107571daf4f5c96dbed0488a16db4d1f4be7b5389aed4', '2025-07-28 00:26:45', '2025-06-28 00:26:45'),
(93, 2, '1c0072f700051b0531c3f3fff8062eadb5fc320a6176f2a61f15d1b7cb193af7', '2025-07-28 01:47:18', '2025-06-28 01:47:18'),
(94, 2, 'e18b328b814f989173952d0ea574590decf3ac78dd55d97ce54d9cf1ed0a5349', '2025-07-28 02:15:08', '2025-06-28 02:15:08'),
(95, 2, '08eae8bc69184b45481fae40a8f97504b7f3fcf9c244524642a7efd56b2adb66', '2025-07-28 03:25:52', '2025-06-28 03:25:52'),
(96, 2, '56dade3811c95021da2e6d2f4df2c99404ca25cb509bb6c1b120ef66dab55c52', '2025-07-28 04:41:47', '2025-06-28 04:41:47'),
(97, 2, '64698cbf5e0ddf43b2699a94dcdc0a679c31d682c1b82d8dcb59247c2d2fba94', '2025-07-28 04:42:45', '2025-06-28 04:42:45'),
(98, 2, 'fcd1ab8b227358f1a23bd9e30b2d6cad1187675505bfa8cf0940e2b1c8312d48', '2025-07-28 04:52:01', '2025-06-28 04:52:01'),
(99, 2, '1f029fed8a81bd8301b71db5917d27713df03640151034ad21458eb2cdf13200', '2025-07-28 05:49:03', '2025-06-28 05:49:03'),
(100, 2, 'a93929c774e6538f06eaf366949dc17a631e4a4e0acf9ca56e12aaed1021feab', '2025-07-28 06:20:39', '2025-06-28 06:20:39'),
(101, 2, 'accb1e4b245600dff13be9826b47565f48640ee497340b424ae43b10de946920', '2025-07-28 07:02:04', '2025-06-28 07:02:04'),
(102, 2, '950e5a51116d995f6187e8f76001b92a49104acea3f22be1211db49a24c0691b', '2025-07-28 07:08:19', '2025-06-28 07:08:19'),
(103, 2, '9456f5c2205deaa7ebfcac9a586ef391d0e9e2ce1f208a55795db28e9ad4559b', '2025-07-28 07:21:10', '2025-06-28 07:21:10'),
(104, 2, 'f3df07800ea9d4e219b14131cc45947389e27fb47cc2eaf715bd2d81cd165e31', '2025-07-28 07:47:51', '2025-06-28 07:47:51'),
(105, 2, '53696d6a13e38b0de56dfa954548191720929f86a44546810d890a9bf71685f4', '2025-07-28 08:34:05', '2025-06-28 08:34:05'),
(106, 2, '3b2387674187c054590bec75becb303322f8df00820c17ac8a70ccc33fb664dd', '2025-07-28 08:39:32', '2025-06-28 08:39:32'),
(107, 2, '873bc91ac865c158c3c8b10e699aa8f528141bf974f5e5da8b1cda4429ffcde8', '2025-07-28 08:56:57', '2025-06-28 08:56:57'),
(108, 2, '387ae43d354ebce0db54caeba6a03e55a4bad5efaaa7a3f85f1493c91fd09ac4', '2025-07-28 09:11:07', '2025-06-28 09:11:07'),
(109, 2, '583e2935f262750513c8c0367cf76b8d992101f6a648d02d5ffe7ab42cc72693', '2025-07-28 09:43:41', '2025-06-28 09:43:41'),
(110, 2, 'db09529dcd21842bea1bc55244be154e15c6f1b74472fc50386f0e5788b2db46', '2025-07-28 11:05:20', '2025-06-28 11:05:20'),
(111, 2, 'f34fe8d57a5865cff4738766e67ad4159130202cb8934e1339a331f3638da9ea', '2025-07-28 11:13:50', '2025-06-28 11:13:50'),
(112, 2, '319894d56d9e2dedd77f7981952018320e263cca236b1da2fcec87126d95695c', '2025-07-28 12:12:45', '2025-06-28 12:12:45'),
(113, 2, '23595baf9b1522460229314e754ce1f5f3446d505ce45283ffbd6e7e200ef7ce', '2025-07-28 12:22:29', '2025-06-28 12:22:29'),
(114, 2, 'f841f04f3fe75d84bfe55bfe8cf794c0e941fd836f136e2694584f7a9d47978b', '2025-07-28 12:35:08', '2025-06-28 12:35:08'),
(115, 4, '19570c2edef50b0276c5ba3e212385618c5ee2c9f906a56e6f814f633a3fca94', '2025-07-28 16:48:25', '2025-06-28 16:48:25'),
(116, 4, 'a92cf1f187dedd99ba240cc171a16fe346833ad97acb1bcfa3d8b1b556eadb76', '2025-07-28 17:11:26', '2025-06-28 17:11:26'),
(117, 4, 'ccdd4b99d790b9465571a7a96a1c060f8f9be49e4648c7262111bfb8f8944e1a', '2025-07-28 17:35:57', '2025-06-28 17:35:57'),
(118, 4, '340866dc44c8917ef17756607f3ec356e849411284a7fe82147fbb85dca9921e', '2025-07-28 17:38:37', '2025-06-28 17:38:37'),
(119, 4, '6ce7b4c621c8abb165b89cfa4430d51f4ab64ccd2920dd74bc3c6b55926ac7d3', '2025-07-28 17:44:46', '2025-06-28 17:44:46'),
(120, 4, 'f2db81f23232dca8fe7d43dbfe205069e0abffbb6e6326d513383a2c666d7303', '2025-07-28 17:52:00', '2025-06-28 17:52:00'),
(121, 4, 'c12b739918fb6d8c6798613e54553b4a85ff283c26113c12b177558ef82580db', '2025-07-28 17:56:49', '2025-06-28 17:56:49'),
(122, 4, '5b941b965fcf17f4139a01d94a50201d4471ec6997648ee92e31aae538d4cdb2', '2025-07-28 18:17:16', '2025-06-28 18:17:16'),
(123, 4, 'bc9d08a21859667ba69c198b873d643227ef9530c30d258d813d63e026c24e76', '2025-07-28 20:15:19', '2025-06-28 20:15:19'),
(124, 4, 'e4eee641f2a2ba0613a0c7fb46b2df73b3876bb1ba9d7f966033b4f2c6ade773', '2025-07-28 21:51:55', '2025-06-28 21:51:55'),
(125, 4, 'a4b2226a7931423a3bc3fc2ad2b5d3de73571f3bd48484ba098e1d73f43225a3', '2025-07-28 22:14:03', '2025-06-28 22:14:03'),
(126, 4, '68481aa14373e9822ea4afaef4a1dae892a8db5de1aa4240b9aab2d2512ab564', '2025-07-28 22:24:45', '2025-06-28 22:24:45'),
(127, 4, '076051dbec37913309204326e464f60df17437c063102bb688c6b7fb2d0fe1de', '2025-07-28 22:27:34', '2025-06-28 22:27:34'),
(128, 4, 'a32f0af80957c1244610a4ccaafa1cfd33e081d6c4916aad1a6610aad882ad95', '2025-07-28 22:28:01', '2025-06-28 22:28:01'),
(129, 4, 'b72116f8518020a6b8ccf3397f878c447bf774fb12b272f1fce577d2a8da840c', '2025-07-28 22:44:24', '2025-06-28 22:44:24'),
(130, 5, 'ea3fb8bcae335098e661914ce56a3e7a9f7f7ac447487d236496de2c5cb49609', '2025-07-28 23:05:25', '2025-06-28 23:05:25'),
(131, 5, '1d7fb392b2d0e01095d92393c73bec5b80cc680b98684a0e852e1cf1dd3cf1a4', '2025-07-28 23:07:25', '2025-06-28 23:07:25'),
(132, 4, 'f944fffea82f2df1496d2e8bc2135582a438ca387f804535eadf0884c8fceb63', '2025-07-29 09:17:16', '2025-06-29 09:17:16'),
(133, 4, '87a4cb040516d6e6a6abaa31d9b5bb279a91be05e74abbe94e86756070c3ebdc', '2025-07-29 09:19:10', '2025-06-29 09:19:10'),
(134, 4, 'e7ca06384e9cfbeb8891e08bfd3e3514a812e2f833e4b90a017fd5f94c80a0af', '2025-07-29 10:49:35', '2025-06-29 10:49:35'),
(135, 6, '5990c4bf59158c446ec16e3ab35c299c763f76bcdaa20c660e26495b5f487326', '2025-07-29 12:57:30', '2025-06-29 12:57:30'),
(136, 6, '4c7100e9629d80d1909af887c518a6d90553b823427bf3a732f9f1cf045a8d0a', '2025-07-29 12:58:19', '2025-06-29 12:58:19'),
(137, 4, '41b1686935ff19ccb84ea5aee4709cb4e7d1ae591747d5293ffec4039072f691', '2025-07-29 12:59:33', '2025-06-29 12:59:33'),
(138, 6, '859ca7932ea6b181a50d48ad8a60d652ebb456f265ee9c553d431da6e39f79fc', '2025-07-29 13:03:40', '2025-06-29 13:03:40'),
(139, 5, '746f6a3cea3b616d6c61cf8fe3465e03d29e10da136a58e3846e732e7ca5b9e2', '2025-07-29 13:05:36', '2025-06-29 13:05:36'),
(140, 7, '019921eda7a471e0b77288462a77e61f4ff0e7b3a1049be7a0bf1a5935ccb365', '2025-07-29 13:10:54', '2025-06-29 13:10:54'),
(141, 7, '2c1a2effd4d53c669918fc1cc7d64ff1d296afbefd5c0e183adfdbcd9c2ab7a2', '2025-07-29 13:15:37', '2025-06-29 13:15:37'),
(142, 8, '694e157c8abc902ab2afe801117e15fd9080ea3be2e12676486cb0b5487e6709', '2025-07-29 13:21:09', '2025-06-29 13:21:09'),
(143, 8, '178ac2b4df424266431730cddf40fd37dc0a3e8e070fe9ded6f487eb64609eb6', '2025-07-29 13:22:28', '2025-06-29 13:22:28'),
(144, 8, '157aaddb7a11ccfe0bd28164e4f402ceb4a195e41c28d15575170f1c3823d452', '2025-07-29 13:24:42', '2025-06-29 13:24:42'),
(145, 9, '0b53cd757aa4b8f530210f634092279092c67e6589ce4f191df6a8669648d215', '2025-07-29 14:17:59', '2025-06-29 14:17:59'),
(146, 9, 'db63a06b37ea4bdfbd76e7a1790f2ecaf6e8a85ad58a0c4f575a4613ed02cf47', '2025-07-29 14:20:42', '2025-06-29 14:20:42'),
(147, 10, '7dbca546346b1be3c5568ed0b23a8674b67c218dabefddf2ff003b153c300d8d', '2025-07-29 14:25:41', '2025-06-29 14:25:41'),
(148, 10, '0abe0f5a3777b8a681e1b1b11bc2c5fbcc189703517c2a3755bf4cd9644e46db', '2025-07-29 14:28:12', '2025-06-29 14:28:12'),
(149, 9, 'c7d369bca6995a563f2fd1ff43dba1595414e393727eee41c69f932d9d953cc5', '2025-07-29 14:37:02', '2025-06-29 14:37:02'),
(150, 11, '712bf3d08942c915c120f8ce01c60b0c1cf06b2085259487336e2e8e3bae37f1', '2025-07-29 14:37:48', '2025-06-29 14:37:48'),
(151, 11, '53d5238698d0853b7e28b1197cbd7ee6dfe054f6e4c0214486ef571997ee8497', '2025-07-29 14:38:21', '2025-06-29 14:38:21'),
(152, 4, '61409b350798af30c706e7f14fe8e35399382d9ec21c4e932c18d922f5fb413a', '2025-07-29 14:38:21', '2025-06-29 14:38:21'),
(153, 4, '1ed37f937d96519652658ab75b6c84e5f177aa171e4c464907167c13f40f94c5', '2025-07-29 14:41:05', '2025-06-29 14:41:05'),
(154, 5, '5ff99cbf876d4bc5e5e4ea32412aeda5d05a21f7e526123f4786869075bab2b6', '2025-07-29 15:43:55', '2025-06-29 15:43:55'),
(155, 5, '6926590cc123347f8fc0ed8dd36c8256f18875243816f42da7855b79d871a179', '2025-07-29 16:38:03', '2025-06-29 16:38:03'),
(156, 12, '61f4e60489a490148dfe21032b7ebeeea4234542eff57f38eefc51d10339904b', '2025-07-29 17:12:57', '2025-06-29 17:12:57'),
(157, 12, '0b328a684af9253d96883df7e24fb1c48758406ff468fb05490b79048dcd4088', '2025-07-29 17:18:02', '2025-06-29 17:18:02'),
(158, 5, 'a8104c3555d8ba56e077117866dc25eb8dca535c8cf5d74932ce6778efb73aa5', '2025-07-29 22:10:28', '2025-06-29 22:10:28'),
(159, 5, 'bfe1c088925fa433019c6a6cbc4abd48f1827ff336ac4e962edd4b53f5ce1344', '2025-07-29 22:21:16', '2025-06-29 22:21:16'),
(160, 5, '5cc6d652c8b63ed3c35c908c87812432b42fe2e0dde1372d04dd44639501b5b7', '2025-07-29 23:14:05', '2025-06-29 23:14:05'),
(161, 13, '2ba4d593cd93211532f92672c951995bf33b6fb955510ed48361a35910b99824', '2025-07-30 03:13:40', '2025-06-30 03:13:40');

-- --------------------------------------------------------

--
-- Table structure for table `wallets`
--

CREATE TABLE `wallets` (
  `id` int(11) NOT NULL,
  `name` varchar(50) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `active` tinyint(4) DEFAULT 1,
  `history_icon` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wallets`
--

INSERT INTO `wallets` (`id`, `name`, `logo`, `active`, `history_icon`) VALUES
(1, 'WavePay', 'https://amazemm.xyz/images/wave.png', 1, 'https://amazemm.xyz/images/history.png'),
(2, 'KBZPay', 'https://amazemm.xyz/images/kpay.png', 1, 'https://amazemm.xyz/images/history.png');

-- --------------------------------------------------------

--
-- Table structure for table `winners`
--

CREATE TABLE `winners` (
  `id` int(11) NOT NULL,
  `winner_name` varchar(50) NOT NULL,
  `winner_name_en` varchar(50) DEFAULT NULL,
  `animal_key` varchar(20) NOT NULL,
  `animal_mm` varchar(20) NOT NULL,
  `animal_en` varchar(20) DEFAULT NULL,
  `prize` int(11) NOT NULL,
  `win_date` date NOT NULL,
  `is_real` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `bet_amount` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `winners`
--

INSERT INTO `winners` (`id`, `winner_name`, `winner_name_en`, `animal_key`, `animal_mm`, `animal_en`, `prize`, `win_date`, `is_real`, `created_at`, `phone`, `bet_amount`) VALUES
(201, 'Tin Tin', 'Tin Tin', 'tiger', 'á€€á€»á€¬á€¸', 'Tiger', 4000, '2025-06-28', 0, '2025-06-28 21:42:03', '09242113603', 2000),
(202, 'Maung Min', 'Maung Min', 'chicken', 'á€€á€¼á€€á€º', 'Chicken', 1000, '2025-06-28', 0, '2025-06-28 21:42:03', '09637220358', 1000),
(203, 'Shwe Htway', 'Shwe Htway', 'elephant', 'á€†á€„á€º', 'Elephant', 100, '2025-06-28', 0, '2025-06-28 21:42:03', '09853151836', 100),
(204, 'Thein Zaw', 'Thein Zaw', 'elephant', 'á€†á€„á€º', 'Elephant', 4000, '2025-06-28', 0, '2025-06-28 21:42:03', '09086535513', 2000),
(205, 'Thet Nyein', 'Thet Nyein', 'chicken', 'á€€á€¼á€€á€º', 'Chicken', 100, '2025-06-28', 0, '2025-06-28 21:42:03', '09695059883', 100),
(206, 'Nyi Nyi', 'Nyi Nyi', 'chicken', 'á€€á€¼á€€á€º', 'Chicken', 30000, '2025-06-28', 0, '2025-06-28 21:42:03', '09995840870', 10000),
(207, 'Ma Ae', 'Ma Ae', 'shrimp', 'á€•á€¯á€‡á€½á€”á€º', 'Shrimp', 20000, '2025-06-28', 0, '2025-06-28 21:42:03', '09090895076', 10000),
(208, 'Maung Htun', 'Maung Htun', 'elephant', 'á€†á€„á€º', 'Elephant', 6000, '2025-06-28', 0, '2025-06-28 21:42:03', '09461640842', 3000),
(209, 'Soe Soe', 'Soe Soe', 'elephant', 'á€†á€„á€º', 'Elephant', 6000, '2025-06-28', 0, '2025-06-28 21:42:03', '09277656388', 3000),
(210, 'Maung Min', 'Maung Min', 'shrimp', 'á€•á€¯á€‡á€½á€”á€º', 'Shrimp', 10000, '2025-06-28', 0, '2025-06-28 21:42:03', '09121976337', 5000),
(211, 'Sanda', 'Sanda', 'shrimp', 'á€•á€¯á€‡á€½á€”á€º', 'Shrimp', 200, '2025-06-28', 0, '2025-06-28 21:42:03', '09289306864', 200),
(212, 'May May', 'May May', 'chicken', 'á€€á€¼á€€á€º', 'Chicken', 4000, '2025-06-28', 0, '2025-06-28 21:42:03', '09617513447', 2000),
(213, 'Maung Htun', 'Maung Htun', 'tiger', 'á€€á€»á€¬á€¸', 'Tiger', 300, '2025-06-28', 0, '2025-06-28 21:42:03', '09872429370', 100),
(214, 'Shwe Htway', 'Shwe Htway', 'elephant', 'á€†á€„á€º', 'Elephant', 6000, '2025-06-28', 0, '2025-06-28 21:42:03', '09429354265', 3000),
(215, 'Aung Myint', 'Aung Myint', 'elephant', 'á€†á€„á€º', 'Elephant', 1000, '2025-06-28', 0, '2025-06-28 21:42:03', '09096821921', 1000),
(216, 'Lay Lay', 'Lay Lay', 'elephant', 'á€†á€„á€º', 'Elephant', 500, '2025-06-28', 0, '2025-06-28 21:42:03', '09225522372', 500),
(217, 'Aung Myint', 'Aung Myint', 'shrimp', 'á€•á€¯á€‡á€½á€”á€º', 'Shrimp', 10000, '2025-06-28', 0, '2025-06-28 21:42:03', '09590809102', 10000),
(218, 'Maung Maung', 'Maung Maung', 'shrimp', 'á€•á€¯á€‡á€½á€”á€º', 'Shrimp', 200, '2025-06-28', 0, '2025-06-28 21:42:03', '09407307410', 200),
(219, 'Ko Ko Tin', 'Ko Ko Tin', 'tiger', 'á€€á€»á€¬á€¸', 'Tiger', 400, '2025-06-28', 0, '2025-06-28 21:42:03', '09805878445', 200),
(220, 'Lay Lay', 'Lay Lay', 'tiger', 'á€€á€»á€¬á€¸', 'Tiger', 5000, '2025-06-28', 0, '2025-06-28 21:42:03', '09311576275', 5000),
(221, 'Min Thu', 'Min Thu', 'chicken', 'á€€á€¼á€€á€º', 'Chicken', 6000, '2025-06-28', 0, '2025-06-28 21:42:03', '09714287355', 3000),
(222, 'Lay Lay', 'Lay Lay', 'fish', 'á€„á€«á€¸', 'Fish', 3000, '2025-06-28', 0, '2025-06-28 21:42:03', '09434998114', 3000),
(223, 'Thein Zaw', 'Thein Zaw', 'fish', 'á€„á€«á€¸', 'Fish', 300, '2025-06-28', 0, '2025-06-28 21:42:03', '09635855300', 100),
(224, 'Maung Min', 'Maung Min', 'shrimp', 'á€•á€¯á€‡á€½á€”á€º', 'Shrimp', 1000, '2025-06-28', 0, '2025-06-28 21:42:03', '09528813221', 500),
(225, 'Thein Zaw', 'Thein Zaw', 'chicken', 'á€€á€¼á€€á€º', 'Chicken', 15000, '2025-06-28', 0, '2025-06-28 21:42:03', '09436354512', 5000),
(226, 'Min Thu', 'Min Thu', 'fish', 'á€„á€«á€¸', 'Fish', 6000, '2025-06-28', 0, '2025-06-28 21:42:03', '09957969719', 3000),
(227, 'Shwe Htway', 'Shwe Htway', 'chicken', 'á€€á€¼á€€á€º', 'Chicken', 2000, '2025-06-28', 0, '2025-06-28 21:42:03', '09388612333', 2000),
(228, 'Sanda', 'Sanda', 'fish', 'á€„á€«á€¸', 'Fish', 400, '2025-06-28', 0, '2025-06-28 21:42:03', '09066189929', 200),
(229, 'Su Myint', 'Su Myint', 'shrimp', 'á€•á€¯á€‡á€½á€”á€º', 'Shrimp', 2000, '2025-06-28', 0, '2025-06-28 21:42:03', '09226800621', 1000),
(230, 'Thet Nyein', 'Thet Nyein', 'fish', 'á€„á€«á€¸', 'Fish', 3000, '2025-06-28', 0, '2025-06-28 21:42:03', '09490804446', 1000),
(231, 'Soe Soe', 'Soe Soe', 'chicken', 'á€€á€¼á€€á€º', 'Chicken', 30000, '2025-06-28', 0, '2025-06-28 21:42:03', '09673617192', 10000),
(232, 'Thein Zaw', 'Thein Zaw', 'fish', 'á€„á€«á€¸', 'Fish', 500, '2025-06-28', 0, '2025-06-28 21:42:03', '09525239108', 500),
(233, 'Sanda', 'Sanda', 'fish', 'á€„á€«á€¸', 'Fish', 500, '2025-06-28', 0, '2025-06-28 21:42:03', '09738617035', 500),
(234, 'Ma Ae', 'Ma Ae', 'chicken', 'á€€á€¼á€€á€º', 'Chicken', 20000, '2025-06-28', 0, '2025-06-28 21:42:03', '09629312302', 10000),
(235, 'Thet Nyein', 'Thet Nyein', 'shrimp', 'á€•á€¯á€‡á€½á€”á€º', 'Shrimp', 100, '2025-06-28', 0, '2025-06-28 21:42:03', '09111002024', 100),
(236, 'Soe Soe', 'Soe Soe', 'elephant', 'á€†á€„á€º', 'Elephant', 3000, '2025-06-28', 0, '2025-06-28 21:42:03', '09945914718', 1000),
(237, 'Aung Myint', 'Aung Myint', 'turtle', 'á€œá€­á€•á€º', 'Turtle', 1000, '2025-06-28', 0, '2025-06-28 21:42:03', '09222523634', 500),
(238, 'Ko Ko Tin', 'Ko Ko Tin', 'elephant', 'á€†á€„á€º', 'Elephant', 9000, '2025-06-28', 0, '2025-06-28 21:42:03', '09430573384', 3000),
(239, 'Nyi Nyi', 'Nyi Nyi', 'elephant', 'á€†á€„á€º', 'Elephant', 5000, '2025-06-28', 0, '2025-06-28 21:42:03', '09756765594', 5000),
(240, 'Myint Myat', 'Myint Myat', 'chicken', 'á€€á€¼á€€á€º', 'Chicken', 2000, '2025-06-28', 0, '2025-06-28 21:42:03', '09945733399', 1000),
(241, 'Aung Myint', 'Aung Myint', 'chicken', 'á€€á€¼á€€á€º', 'Chicken', 6000, '2025-06-28', 0, '2025-06-28 21:42:03', '09670078372', 3000),
(242, 'Sanda', 'Sanda', 'elephant', 'á€†á€„á€º', 'Elephant', 400, '2025-06-28', 0, '2025-06-28 21:42:03', '09109240061', 200),
(243, 'Ko Ko Tin', 'Ko Ko Tin', 'elephant', 'á€†á€„á€º', 'Elephant', 1000, '2025-06-28', 0, '2025-06-28 21:42:03', '09973770083', 1000),
(244, 'Su Myint', 'Su Myint', 'elephant', 'á€†á€„á€º', 'Elephant', 4000, '2025-06-28', 0, '2025-06-28 21:42:03', '09548276271', 2000),
(245, 'Maung Min', 'Maung Min', 'chicken', 'á€€á€¼á€€á€º', 'Chicken', 10000, '2025-06-28', 0, '2025-06-28 21:42:03', '09329426631', 10000),
(246, 'Aung Myint', 'Aung Myint', 'fish', 'á€„á€«á€¸', 'Fish', 3000, '2025-06-28', 0, '2025-06-28 21:42:03', '09212093387', 1000),
(247, 'Maung Yo', 'Maung Yo', 'shrimp', 'á€•á€¯á€‡á€½á€”á€º', 'Shrimp', 6000, '2025-06-28', 0, '2025-06-28 21:42:03', '09299639551', 3000),
(248, 'Aung Myint', 'Aung Myint', 'turtle', 'á€œá€­á€•á€º', 'Turtle', 15000, '2025-06-28', 0, '2025-06-28 21:42:03', '09317218535', 5000),
(249, 'Maung Htun', 'Maung Htun', 'fish', 'á€„á€«á€¸', 'Fish', 20000, '2025-06-28', 0, '2025-06-28 21:42:03', '09098688707', 10000),
(250, 'Maung Htun', 'Maung Htun', 'tiger', 'á€€á€»á€¬á€¸', 'Tiger', 1000, '2025-06-28', 0, '2025-06-28 21:42:03', '09570094290', 500),
(251, 'Soe Soe', 'Soe Soe', 'shrimp', 'á€•á€¯á€‡á€½á€”á€º', 'Shrimp', 600, '2025-06-28', 0, '2025-06-28 21:42:03', '09868188686', 200),
(252, 'Ko Ko Tin', 'Ko Ko Tin', 'elephant', 'á€†á€„á€º', 'Elephant', 10000, '2025-06-28', 0, '2025-06-28 21:42:03', '09349939284', 5000),
(253, 'Ko Ko Tin', 'Ko Ko Tin', 'elephant', 'á€†á€„á€º', 'Elephant', 6000, '2025-06-28', 0, '2025-06-28 21:42:03', '09678807517', 3000),
(254, 'Tin Tin', 'Tin Tin', 'chicken', 'á€€á€¼á€€á€º', 'Chicken', 300, '2025-06-28', 0, '2025-06-28 21:42:03', '09680470901', 100),
(255, 'Soe Soe', 'Soe Soe', 'shrimp', 'á€•á€¯á€‡á€½á€”á€º', 'Shrimp', 300, '2025-06-28', 0, '2025-06-28 21:42:03', '09612605297', 100),
(256, 'Thein Zaw', 'Thein Zaw', 'elephant', 'á€†á€„á€º', 'Elephant', 5000, '2025-06-28', 0, '2025-06-28 21:42:03', '09861267494', 5000),
(257, 'Aung Myint', 'Aung Myint', 'tiger', 'á€€á€»á€¬á€¸', 'Tiger', 600, '2025-06-28', 0, '2025-06-28 21:42:03', '09051648081', 200),
(258, 'Ko Ko Tin', 'Ko Ko Tin', 'chicken', 'á€€á€¼á€€á€º', 'Chicken', 100, '2025-06-28', 0, '2025-06-28 21:42:03', '09345053743', 100),
(259, 'Myint Myat', 'Myint Myat', 'tiger', 'á€€á€»á€¬á€¸', 'Tiger', 1000, '2025-06-28', 0, '2025-06-28 21:42:03', '09661945247', 1000),
(260, 'Maung Htun', 'Maung Htun', 'shrimp', 'á€•á€¯á€‡á€½á€”á€º', 'Shrimp', 6000, '2025-06-28', 0, '2025-06-28 21:42:03', '09569272204', 2000),
(261, 'Maung Maung', 'Maung Maung', 'chicken', 'á€€á€¼á€€á€º', 'Chicken', 2000, '2025-06-28', 0, '2025-06-28 21:42:03', '09709020743', 1000),
(262, 'Su Myint', 'Su Myint', 'shrimp', 'á€•á€¯á€‡á€½á€”á€º', 'Shrimp', 1000, '2025-06-28', 0, '2025-06-28 21:42:03', '09513088730', 1000),
(263, 'Maung Yo', 'Maung Yo', 'fish', 'á€„á€«á€¸', 'Fish', 5000, '2025-06-28', 0, '2025-06-28 21:42:03', '09707249605', 5000),
(264, 'Ko Ko Tin', 'Ko Ko Tin', 'chicken', 'á€€á€¼á€€á€º', 'Chicken', 1000, '2025-06-28', 0, '2025-06-28 21:42:03', '09916339228', 500),
(265, 'Myint Myat', 'Myint Myat', 'chicken', 'á€€á€¼á€€á€º', 'Chicken', 3000, '2025-06-28', 0, '2025-06-28 21:42:03', '09399826178', 1000),
(266, 'Tin Tin', 'Tin Tin', 'turtle', 'á€œá€­á€•á€º', 'Turtle', 20000, '2025-06-28', 0, '2025-06-28 21:42:03', '09138698178', 10000),
(267, 'Shwe Htway', 'Shwe Htway', 'shrimp', 'á€•á€¯á€‡á€½á€”á€º', 'Shrimp', 200, '2025-06-28', 0, '2025-06-28 21:42:03', '09669397295', 200),
(268, 'Maung Htun', 'Maung Htun', 'fish', 'á€„á€«á€¸', 'Fish', 600, '2025-06-28', 0, '2025-06-28 21:42:03', '09065848121', 200),
(269, 'Nyi Nyi', 'Nyi Nyi', 'tiger', 'á€€á€»á€¬á€¸', 'Tiger', 400, '2025-06-28', 0, '2025-06-28 21:42:03', '09187622731', 200),
(270, 'Maung Min', 'Maung Min', 'elephant', 'á€†á€„á€º', 'Elephant', 5000, '2025-06-28', 0, '2025-06-28 21:42:03', '09416660655', 5000),
(271, 'Tin Tin', 'Tin Tin', 'fish', 'á€„á€«á€¸', 'Fish', 4000, '2025-06-28', 0, '2025-06-28 21:42:03', '09430994784', 2000),
(272, 'Thein Zaw', 'Thein Zaw', 'tiger', 'á€€á€»á€¬á€¸', 'Tiger', 6000, '2025-06-28', 0, '2025-06-28 21:42:03', '09190486602', 3000),
(273, 'Maung Yo', 'Maung Yo', 'fish', 'á€„á€«á€¸', 'Fish', 2000, '2025-06-28', 0, '2025-06-28 21:42:03', '09316503272', 2000),
(274, 'Thet Nyein', 'Thet Nyein', 'chicken', 'á€€á€¼á€€á€º', 'Chicken', 300, '2025-06-28', 0, '2025-06-28 21:42:03', '09593940028', 100),
(275, 'Thet Nyein', 'Thet Nyein', 'turtle', 'á€œá€­á€•á€º', 'Turtle', 15000, '2025-06-28', 0, '2025-06-28 21:42:03', '09202225010', 5000),
(276, 'Sanda', 'Sanda', 'elephant', 'á€†á€„á€º', 'Elephant', 200, '2025-06-28', 0, '2025-06-28 21:42:03', '09405952533', 100),
(277, 'Maung Htun', 'Maung Htun', 'chicken', 'á€€á€¼á€€á€º', 'Chicken', 1500, '2025-06-28', 0, '2025-06-28 21:42:03', '09901176259', 500),
(278, 'Su Myint', 'Su Myint', 'elephant', 'á€†á€„á€º', 'Elephant', 6000, '2025-06-28', 0, '2025-06-28 21:42:03', '09789900462', 3000),
(279, 'Ma Hlaing', 'Ma Hlaing', 'elephant', 'á€†á€„á€º', 'Elephant', 100, '2025-06-28', 0, '2025-06-28 21:42:03', '09640446135', 100),
(280, 'Ma Ae', 'Ma Ae', 'chicken', 'á€€á€¼á€€á€º', 'Chicken', 200, '2025-06-28', 0, '2025-06-28 21:42:03', '09471603845', 100),
(281, 'Su Myint', 'Su Myint', 'fish', 'á€„á€«á€¸', 'Fish', 10000, '2025-06-28', 0, '2025-06-28 21:42:03', '09744960708', 10000),
(282, 'Maung Min', 'Maung Min', 'tiger', 'á€€á€»á€¬á€¸', 'Tiger', 15000, '2025-06-28', 0, '2025-06-28 21:42:03', '09164649682', 5000),
(283, 'Ma Hlaing', 'Ma Hlaing', 'elephant', 'á€†á€„á€º', 'Elephant', 1000, '2025-06-28', 0, '2025-06-28 21:42:03', '09864845798', 1000),
(284, 'Ma Hlaing', 'Ma Hlaing', 'tiger', 'á€€á€»á€¬á€¸', 'Tiger', 4000, '2025-06-28', 0, '2025-06-28 21:42:03', '09837959628', 2000),
(285, 'Aung Myint', 'Aung Myint', 'fish', 'á€„á€«á€¸', 'Fish', 6000, '2025-06-28', 0, '2025-06-28 21:42:03', '09924271547', 2000),
(286, 'Nyi Nyi', 'Nyi Nyi', 'shrimp', 'á€•á€¯á€‡á€½á€”á€º', 'Shrimp', 1000, '2025-06-28', 0, '2025-06-28 21:42:03', '09649506243', 1000),
(287, 'Soe Soe', 'Soe Soe', 'chicken', 'á€€á€¼á€€á€º', 'Chicken', 15000, '2025-06-28', 0, '2025-06-28 21:42:03', '09148099146', 5000),
(288, 'Shwe Htway', 'Shwe Htway', 'elephant', 'á€†á€„á€º', 'Elephant', 600, '2025-06-28', 0, '2025-06-28 21:42:03', '09140811037', 200),
(289, 'Tin Tin', 'Tin Tin', 'turtle', 'á€œá€­á€•á€º', 'Turtle', 1500, '2025-06-28', 0, '2025-06-28 21:42:03', '09300962808', 500),
(290, 'Soe Soe', 'Soe Soe', 'tiger', 'á€€á€»á€¬á€¸', 'Tiger', 100, '2025-06-28', 0, '2025-06-28 21:42:03', '09532558891', 100),
(291, 'Shwe Htway', 'Shwe Htway', 'fish', 'á€„á€«á€¸', 'Fish', 30000, '2025-06-28', 0, '2025-06-28 21:42:03', '09580033628', 10000),
(292, 'Maung Yo', 'Maung Yo', 'shrimp', 'á€•á€¯á€‡á€½á€”á€º', 'Shrimp', 300, '2025-06-28', 0, '2025-06-28 21:42:03', '09426339873', 100),
(293, 'Sanda', 'Sanda', 'turtle', 'á€œá€­á€•á€º', 'Turtle', 200, '2025-06-28', 0, '2025-06-28 21:42:03', '09826621052', 100),
(294, 'Shwe Htway', 'Shwe Htway', 'tiger', 'á€€á€»á€¬á€¸', 'Tiger', 4000, '2025-06-28', 0, '2025-06-28 21:42:03', '09136058875', 2000),
(295, 'Su Myint', 'Su Myint', 'shrimp', 'á€•á€¯á€‡á€½á€”á€º', 'Shrimp', 30000, '2025-06-28', 0, '2025-06-28 21:42:03', '09534273187', 10000),
(296, 'Thet Nyein', 'Thet Nyein', 'shrimp', 'á€•á€¯á€‡á€½á€”á€º', 'Shrimp', 15000, '2025-06-28', 0, '2025-06-28 21:42:03', '09587411695', 5000),
(297, 'Ma Hlaing', 'Ma Hlaing', 'elephant', 'á€†á€„á€º', 'Elephant', 3000, '2025-06-28', 0, '2025-06-28 21:42:03', '09764609950', 1000),
(298, 'Myint Myat', 'Myint Myat', 'tiger', 'á€€á€»á€¬á€¸', 'Tiger', 4000, '2025-06-28', 0, '2025-06-28 21:42:03', '09908921163', 2000),
(299, 'Nyi Nyi', 'Nyi Nyi', 'tiger', 'á€€á€»á€¬á€¸', 'Tiger', 10000, '2025-06-28', 0, '2025-06-28 21:42:03', '09015460661', 5000),
(300, 'Maung Min', 'Maung Min', 'chicken', 'á€€á€¼á€€á€º', 'Chicken', 300, '2025-06-28', 0, '2025-06-28 21:42:03', '09810185440', 100),
(401, 'Soe Soe', 'Soe Soe', 'chicken', 'á€€á€¼á€€á€º', 'Chicken', 10000, '2025-06-29', 0, '2025-06-29 14:51:38', '09557376861', 5000),
(402, 'Soe Soe', 'Soe Soe', 'tiger', 'á€€á€»á€¬á€¸', 'Tiger', 30000, '2025-06-29', 0, '2025-06-29 14:51:38', '09729086747', 10000),
(403, 'Thet Nyein', 'Thet Nyein', 'turtle', 'á€œá€­á€•á€º', 'Turtle', 200, '2025-06-29', 0, '2025-06-29 14:51:38', '09632332253', 200),
(404, 'Aung Myint', 'Aung Myint', 'tiger', 'á€€á€»á€¬á€¸', 'Tiger', 15000, '2025-06-29', 0, '2025-06-29 14:51:38', '09791299179', 5000),
(405, 'Ko Ko Tin', 'Ko Ko Tin', 'chicken', 'á€€á€¼á€€á€º', 'Chicken', 15000, '2025-06-29', 0, '2025-06-29 14:51:38', '09682257480', 5000),
(406, 'Thein Zaw', 'Thein Zaw', 'elephant', 'á€†á€„á€º', 'Elephant', 10000, '2025-06-29', 0, '2025-06-29 14:51:38', '09199931707', 5000),
(407, 'Tin Tin', 'Tin Tin', 'turtle', 'á€œá€­á€•á€º', 'Turtle', 1000, '2025-06-29', 0, '2025-06-29 14:51:38', '09173690953', 1000),
(408, 'Shwe Htway', 'Shwe Htway', 'elephant', 'á€†á€„á€º', 'Elephant', 200, '2025-06-29', 0, '2025-06-29 14:51:38', '09900035095', 200),
(409, 'Ma Ae', 'Ma Ae', 'tiger', 'á€€á€»á€¬á€¸', 'Tiger', 5000, '2025-06-29', 0, '2025-06-29 14:51:38', '09300510006', 5000),
(410, 'Maung Maung', 'Maung Maung', 'chicken', 'á€€á€¼á€€á€º', 'Chicken', 6000, '2025-06-29', 0, '2025-06-29 14:51:38', '09600421059', 3000),
(411, 'Thein Zaw', 'Thein Zaw', 'elephant', 'á€†á€„á€º', 'Elephant', 200, '2025-06-29', 0, '2025-06-29 14:51:38', '09658613446', 200),
(412, 'Maung Htun', 'Maung Htun', 'fish', 'á€„á€«á€¸', 'Fish', 5000, '2025-06-29', 0, '2025-06-29 14:51:38', '09080922502', 5000),
(413, 'Min Thu', 'Min Thu', 'fish', 'á€„á€«á€¸', 'Fish', 6000, '2025-06-29', 0, '2025-06-29 14:51:38', '09675716432', 3000),
(414, 'Maung Htun', 'Maung Htun', 'fish', 'á€„á€«á€¸', 'Fish', 5000, '2025-06-29', 0, '2025-06-29 14:51:38', '09566798314', 5000),
(415, 'Shwe Htway', 'Shwe Htway', 'shrimp', 'á€•á€¯á€‡á€½á€”á€º', 'Shrimp', 10000, '2025-06-29', 0, '2025-06-29 14:51:38', '09073166680', 10000),
(416, 'Aung Myint', 'Aung Myint', 'fish', 'á€„á€«á€¸', 'Fish', 20000, '2025-06-29', 0, '2025-06-29 14:51:38', '09827235370', 10000),
(417, 'Lay Lay', 'Lay Lay', 'fish', 'á€„á€«á€¸', 'Fish', 3000, '2025-06-29', 0, '2025-06-29 14:51:38', '09042711920', 1000),
(418, 'Nyi Nyi', 'Nyi Nyi', 'tiger', 'á€€á€»á€¬á€¸', 'Tiger', 30000, '2025-06-29', 0, '2025-06-29 14:51:38', '09364542158', 10000),
(419, 'Maung Maung', 'Maung Maung', 'turtle', 'á€œá€­á€•á€º', 'Turtle', 400, '2025-06-29', 0, '2025-06-29 14:51:38', '09211209912', 200),
(420, 'Nyi Nyi', 'Nyi Nyi', 'chicken', 'á€€á€¼á€€á€º', 'Chicken', 600, '2025-06-29', 0, '2025-06-29 14:51:38', '09518690212', 200),
(421, 'Soe Soe', 'Soe Soe', 'elephant', 'á€†á€„á€º', 'Elephant', 10000, '2025-06-29', 0, '2025-06-29 14:51:38', '09635163761', 5000),
(422, 'Maung Min', 'Maung Min', 'fish', 'á€„á€«á€¸', 'Fish', 2000, '2025-06-29', 0, '2025-06-29 14:51:38', '09969844072', 1000),
(423, 'Maung Htun', 'Maung Htun', 'fish', 'á€„á€«á€¸', 'Fish', 500, '2025-06-29', 0, '2025-06-29 14:51:38', '09933031559', 500),
(424, 'Min Thu', 'Min Thu', 'shrimp', 'á€•á€¯á€‡á€½á€”á€º', 'Shrimp', 2000, '2025-06-29', 0, '2025-06-29 14:51:38', '09778127936', 1000),
(425, 'Maung Htun', 'Maung Htun', 'turtle', 'á€œá€­á€•á€º', 'Turtle', 30000, '2025-06-29', 0, '2025-06-29 14:51:38', '09846401617', 10000),
(426, 'Thet Nyein', 'Thet Nyein', 'shrimp', 'á€•á€¯á€‡á€½á€”á€º', 'Shrimp', 200, '2025-06-29', 0, '2025-06-29 14:51:38', '09032556009', 100),
(427, 'Ma Ae', 'Ma Ae', 'elephant', 'á€†á€„á€º', 'Elephant', 10000, '2025-06-29', 0, '2025-06-29 14:51:38', '09993770253', 10000),
(428, 'Tin Tin', 'Tin Tin', 'chicken', 'á€€á€¼á€€á€º', 'Chicken', 100, '2025-06-29', 0, '2025-06-29 14:51:38', '09858196878', 100),
(429, 'Maung Maung', 'Maung Maung', 'tiger', 'á€€á€»á€¬á€¸', 'Tiger', 2000, '2025-06-29', 0, '2025-06-29 14:51:38', '09410511765', 1000),
(430, 'Myint Myat', 'Myint Myat', 'elephant', 'á€†á€„á€º', 'Elephant', 5000, '2025-06-29', 0, '2025-06-29 14:51:38', '09180705545', 5000),
(431, 'May May', 'May May', 'shrimp', 'á€•á€¯á€‡á€½á€”á€º', 'Shrimp', 1000, '2025-06-29', 0, '2025-06-29 14:51:38', '09436135565', 500),
(432, 'Ma Ae', 'Ma Ae', 'turtle', 'á€œá€­á€•á€º', 'Turtle', 1000, '2025-06-29', 0, '2025-06-29 14:51:38', '09281596266', 1000),
(433, 'Myint Myat', 'Myint Myat', 'turtle', 'á€œá€­á€•á€º', 'Turtle', 2000, '2025-06-29', 0, '2025-06-29 14:51:38', '09677671712', 1000),
(434, 'Thein Zaw', 'Thein Zaw', 'tiger', 'á€€á€»á€¬á€¸', 'Tiger', 500, '2025-06-29', 0, '2025-06-29 14:51:38', '09541869285', 500),
(435, 'Lay Lay', 'Lay Lay', 'fish', 'á€„á€«á€¸', 'Fish', 15000, '2025-06-29', 0, '2025-06-29 14:51:38', '09266185819', 5000),
(436, 'Shwe Htway', 'Shwe Htway', 'shrimp', 'á€•á€¯á€‡á€½á€”á€º', 'Shrimp', 3000, '2025-06-29', 0, '2025-06-29 14:51:38', '09039096382', 3000),
(437, 'Soe Soe', 'Soe Soe', 'turtle', 'á€œá€­á€•á€º', 'Turtle', 5000, '2025-06-29', 0, '2025-06-29 14:51:38', '09047627816', 5000),
(438, 'Ma Hlaing', 'Ma Hlaing', 'tiger', 'á€€á€»á€¬á€¸', 'Tiger', 200, '2025-06-29', 0, '2025-06-29 14:51:38', '09156773635', 200),
(439, 'Maung Min', 'Maung Min', 'elephant', 'á€†á€„á€º', 'Elephant', 4000, '2025-06-29', 0, '2025-06-29 14:51:38', '09973406812', 2000),
(440, 'Maung Yo', 'Maung Yo', 'elephant', 'á€†á€„á€º', 'Elephant', 20000, '2025-06-29', 0, '2025-06-29 14:51:38', '09700953263', 10000),
(441, 'Maung Maung', 'Maung Maung', 'fish', 'á€„á€«á€¸', 'Fish', 2000, '2025-06-29', 0, '2025-06-29 14:51:38', '09374645154', 1000),
(442, 'Myint Myat', 'Myint Myat', 'chicken', 'á€€á€¼á€€á€º', 'Chicken', 500, '2025-06-29', 0, '2025-06-29 14:51:38', '09997715493', 500),
(443, 'Nyi Nyi', 'Nyi Nyi', 'turtle', 'á€œá€­á€•á€º', 'Turtle', 4000, '2025-06-29', 0, '2025-06-29 14:51:38', '09141241100', 2000),
(444, 'Maung Min', 'Maung Min', 'chicken', 'á€€á€¼á€€á€º', 'Chicken', 30000, '2025-06-29', 0, '2025-06-29 14:51:38', '09119648917', 10000),
(445, 'Tin Tin', 'Tin Tin', 'fish', 'á€„á€«á€¸', 'Fish', 1000, '2025-06-29', 0, '2025-06-29 14:51:38', '09741005804', 1000),
(446, 'Thein Zaw', 'Thein Zaw', 'elephant', 'á€†á€„á€º', 'Elephant', 15000, '2025-06-29', 0, '2025-06-29 14:51:38', '09484630032', 5000),
(447, 'Maung Maung', 'Maung Maung', 'turtle', 'á€œá€­á€•á€º', 'Turtle', 4000, '2025-06-29', 0, '2025-06-29 14:51:38', '09644744764', 2000),
(448, 'May May', 'May May', 'elephant', 'á€†á€„á€º', 'Elephant', 100, '2025-06-29', 0, '2025-06-29 14:51:38', '09168720029', 100),
(449, 'Lay Lay', 'Lay Lay', 'turtle', 'á€œá€­á€•á€º', 'Turtle', 1000, '2025-06-29', 0, '2025-06-29 14:51:38', '09064423611', 1000),
(450, 'Maung Yo', 'Maung Yo', 'fish', 'á€„á€«á€¸', 'Fish', 10000, '2025-06-29', 0, '2025-06-29 14:51:38', '09095363715', 5000),
(451, 'Ko Ko Tin', 'Ko Ko Tin', 'shrimp', 'á€•á€¯á€‡á€½á€”á€º', 'Shrimp', 10000, '2025-06-29', 0, '2025-06-29 14:51:38', '09551979816', 5000),
(452, 'Nyi Nyi', 'Nyi Nyi', 'chicken', 'á€€á€¼á€€á€º', 'Chicken', 1000, '2025-06-29', 0, '2025-06-29 14:51:38', '09181891392', 1000),
(453, 'Maung Htun', 'Maung Htun', 'shrimp', 'á€•á€¯á€‡á€½á€”á€º', 'Shrimp', 100, '2025-06-29', 0, '2025-06-29 14:51:38', '09779282963', 100),
(454, 'Tin Tin', 'Tin Tin', 'tiger', 'á€€á€»á€¬á€¸', 'Tiger', 10000, '2025-06-29', 0, '2025-06-29 14:51:38', '09458456139', 10000),
(455, 'Soe Soe', 'Soe Soe', 'shrimp', 'á€•á€¯á€‡á€½á€”á€º', 'Shrimp', 400, '2025-06-29', 0, '2025-06-29 14:51:38', '09053375661', 200),
(456, 'Soe Soe', 'Soe Soe', 'chicken', 'á€€á€¼á€€á€º', 'Chicken', 6000, '2025-06-29', 0, '2025-06-29 14:51:38', '09378824298', 2000),
(457, 'Shwe Htway', 'Shwe Htway', 'fish', 'á€„á€«á€¸', 'Fish', 4000, '2025-06-29', 0, '2025-06-29 14:51:38', '09060400969', 2000),
(458, 'Sanda', 'Sanda', 'turtle', 'á€œá€­á€•á€º', 'Turtle', 30000, '2025-06-29', 0, '2025-06-29 14:51:38', '09883604688', 10000),
(459, 'Aung Myint', 'Aung Myint', 'elephant', 'á€†á€„á€º', 'Elephant', 3000, '2025-06-29', 0, '2025-06-29 14:51:38', '09825598895', 3000),
(460, 'Thein Zaw', 'Thein Zaw', 'chicken', 'á€€á€¼á€€á€º', 'Chicken', 2000, '2025-06-29', 0, '2025-06-29 14:51:38', '09471797398', 2000),
(461, 'Soe Soe', 'Soe Soe', 'turtle', 'á€œá€­á€•á€º', 'Turtle', 2000, '2025-06-29', 0, '2025-06-29 14:51:38', '09351260781', 1000),
(462, 'Tin Tin', 'Tin Tin', 'tiger', 'á€€á€»á€¬á€¸', 'Tiger', 100, '2025-06-29', 0, '2025-06-29 14:51:38', '09636173030', 100),
(463, 'Maung Htun', 'Maung Htun', 'shrimp', 'á€•á€¯á€‡á€½á€”á€º', 'Shrimp', 300, '2025-06-29', 0, '2025-06-29 14:51:38', '09745862948', 100),
(464, 'Ma Hlaing', 'Ma Hlaing', 'chicken', 'á€€á€¼á€€á€º', 'Chicken', 300, '2025-06-29', 0, '2025-06-29 14:51:38', '09452569465', 100),
(465, 'Su Myint', 'Su Myint', 'elephant', 'á€†á€„á€º', 'Elephant', 3000, '2025-06-29', 0, '2025-06-29 14:51:38', '09471099364', 3000),
(466, 'Nyi Nyi', 'Nyi Nyi', 'shrimp', 'á€•á€¯á€‡á€½á€”á€º', 'Shrimp', 20000, '2025-06-29', 0, '2025-06-29 14:51:38', '09148099942', 10000),
(467, 'Tin Tin', 'Tin Tin', 'turtle', 'á€œá€­á€•á€º', 'Turtle', 3000, '2025-06-29', 0, '2025-06-29 14:51:38', '09156214787', 1000),
(468, 'Maung Htun', 'Maung Htun', 'turtle', 'á€œá€­á€•á€º', 'Turtle', 1500, '2025-06-29', 0, '2025-06-29 14:51:38', '09252843431', 500),
(469, 'Maung Maung', 'Maung Maung', 'elephant', 'á€†á€„á€º', 'Elephant', 1000, '2025-06-29', 0, '2025-06-29 14:51:38', '09574990793', 1000),
(470, 'Lay Lay', 'Lay Lay', 'shrimp', 'á€•á€¯á€‡á€½á€”á€º', 'Shrimp', 15000, '2025-06-29', 0, '2025-06-29 14:51:38', '09423582488', 5000),
(471, 'Sanda', 'Sanda', 'chicken', 'á€€á€¼á€€á€º', 'Chicken', 400, '2025-06-29', 0, '2025-06-29 14:51:38', '09208005630', 200),
(472, 'May May', 'May May', 'tiger', 'á€€á€»á€¬á€¸', 'Tiger', 600, '2025-06-29', 0, '2025-06-29 14:51:38', '09400462746', 200),
(473, 'Ma Hlaing', 'Ma Hlaing', 'tiger', 'á€€á€»á€¬á€¸', 'Tiger', 3000, '2025-06-29', 0, '2025-06-29 14:51:38', '09126194453', 1000),
(474, 'Thet Nyein', 'Thet Nyein', 'elephant', 'á€†á€„á€º', 'Elephant', 200, '2025-06-29', 0, '2025-06-29 14:51:38', '09997693309', 200),
(475, 'Shwe Htway', 'Shwe Htway', 'fish', 'á€„á€«á€¸', 'Fish', 6000, '2025-06-29', 0, '2025-06-29 14:51:38', '09916458355', 2000),
(476, 'May May', 'May May', 'turtle', 'á€œá€­á€•á€º', 'Turtle', 3000, '2025-06-29', 0, '2025-06-29 14:51:38', '09756699886', 1000),
(477, 'Nyi Nyi', 'Nyi Nyi', 'fish', 'á€„á€«á€¸', 'Fish', 2000, '2025-06-29', 0, '2025-06-29 14:51:38', '09923440416', 1000),
(478, 'Lay Lay', 'Lay Lay', 'shrimp', 'á€•á€¯á€‡á€½á€”á€º', 'Shrimp', 3000, '2025-06-29', 0, '2025-06-29 14:51:38', '09540053970', 1000),
(479, 'Thein Zaw', 'Thein Zaw', 'turtle', 'á€œá€­á€•á€º', 'Turtle', 30000, '2025-06-29', 0, '2025-06-29 14:51:38', '09851222441', 10000),
(480, 'Soe Soe', 'Soe Soe', 'fish', 'á€„á€«á€¸', 'Fish', 10000, '2025-06-29', 0, '2025-06-29 14:51:38', '09056183782', 10000),
(481, 'Maung Min', 'Maung Min', 'chicken', 'á€€á€¼á€€á€º', 'Chicken', 6000, '2025-06-29', 0, '2025-06-29 14:51:38', '09374787393', 3000),
(482, 'Maung Maung', 'Maung Maung', 'turtle', 'á€œá€­á€•á€º', 'Turtle', 6000, '2025-06-29', 0, '2025-06-29 14:51:38', '09422677236', 3000),
(483, 'Ko Ko Tin', 'Ko Ko Tin', 'elephant', 'á€†á€„á€º', 'Elephant', 3000, '2025-06-29', 0, '2025-06-29 14:51:38', '09915177718', 1000),
(484, 'Soe Soe', 'Soe Soe', 'shrimp', 'á€•á€¯á€‡á€½á€”á€º', 'Shrimp', 6000, '2025-06-29', 0, '2025-06-29 14:51:38', '09365031997', 3000),
(485, 'Ko Ko Tin', 'Ko Ko Tin', 'turtle', 'á€œá€­á€•á€º', 'Turtle', 1500, '2025-06-29', 0, '2025-06-29 14:51:38', '09502120749', 500),
(486, 'Ma Hlaing', 'Ma Hlaing', 'tiger', 'á€€á€»á€¬á€¸', 'Tiger', 600, '2025-06-29', 0, '2025-06-29 14:51:38', '09387967691', 200),
(487, 'Ma Ae', 'Ma Ae', 'fish', 'á€„á€«á€¸', 'Fish', 6000, '2025-06-29', 0, '2025-06-29 14:51:38', '09473358603', 3000),
(488, 'Ko Ko Tin', 'Ko Ko Tin', 'shrimp', 'á€•á€¯á€‡á€½á€”á€º', 'Shrimp', 30000, '2025-06-29', 0, '2025-06-29 14:51:38', '09246238738', 10000),
(489, 'Thein Zaw', 'Thein Zaw', 'turtle', 'á€œá€­á€•á€º', 'Turtle', 20000, '2025-06-29', 0, '2025-06-29 14:51:38', '09459921271', 10000),
(490, 'Soe Soe', 'Soe Soe', 'tiger', 'á€€á€»á€¬á€¸', 'Tiger', 200, '2025-06-29', 0, '2025-06-29 14:51:38', '09088838780', 200),
(491, 'Min Thu', 'Min Thu', 'elephant', 'á€†á€„á€º', 'Elephant', 9000, '2025-06-29', 0, '2025-06-29 14:51:38', '09708922308', 3000),
(492, 'Tin Tin', 'Tin Tin', 'elephant', 'á€†á€„á€º', 'Elephant', 3000, '2025-06-29', 0, '2025-06-29 14:51:38', '09408419459', 3000),
(493, 'Sanda', 'Sanda', 'chicken', 'á€€á€¼á€€á€º', 'Chicken', 6000, '2025-06-29', 0, '2025-06-29 14:51:38', '09748209131', 2000),
(494, 'Ko Ko Tin', 'Ko Ko Tin', 'shrimp', 'á€•á€¯á€‡á€½á€”á€º', 'Shrimp', 4000, '2025-06-29', 0, '2025-06-29 14:51:38', '09793889440', 2000),
(495, 'Maung Yo', 'Maung Yo', 'turtle', 'á€œá€­á€•á€º', 'Turtle', 100, '2025-06-29', 0, '2025-06-29 14:51:38', '09801145601', 100),
(496, 'Soe Soe', 'Soe Soe', 'fish', 'á€„á€«á€¸', 'Fish', 10000, '2025-06-29', 0, '2025-06-29 14:51:38', '09893032254', 10000),
(497, 'Maung Min', 'Maung Min', 'elephant', 'á€†á€„á€º', 'Elephant', 6000, '2025-06-29', 0, '2025-06-29 14:51:38', '09167108381', 2000),
(498, 'Su Myint', 'Su Myint', 'turtle', 'á€œá€­á€•á€º', 'Turtle', 1000, '2025-06-29', 0, '2025-06-29 14:51:38', '09386923254', 1000),
(499, 'Ma Hlaing', 'Ma Hlaing', 'shrimp', 'á€•á€¯á€‡á€½á€”á€º', 'Shrimp', 10000, '2025-06-29', 0, '2025-06-29 14:51:38', '09824413194', 5000),
(500, 'Maung Yo', 'Maung Yo', 'elephant', 'á€†á€„á€º', 'Elephant', 30000, '2025-06-29', 0, '2025-06-29 14:51:38', '09545817666', 10000);

-- --------------------------------------------------------

--
-- Table structure for table `withdrawals`
--

CREATE TABLE `withdrawals` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `amount` int(11) DEFAULT NULL,
  `target_phone` varchar(20) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp(),
  `admin_note` varchar(255) DEFAULT NULL,
  `type` varchar(10) NOT NULL DEFAULT 'kpay'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `withdrawals`
--

INSERT INTO `withdrawals` (`id`, `user_id`, `amount`, `target_phone`, `status`, `created_at`, `admin_note`, `type`) VALUES
(1, 2, 20000, '09798241599', 'approved', '2025-06-27 09:25:47', NULL, 'kpay'),
(2, 2, 10000, '09798241599', 'rejected', '2025-06-27 09:26:39', NULL, 'kpay'),
(3, 2, 30000, '09798241599', 'approved', '2025-06-27 09:29:49', NULL, 'kpay'),
(4, 2, 40000, '09798241599', 'approved', '2025-06-27 10:09:30', NULL, 'kpay');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_balance`
--
ALTER TABLE `admin_balance`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin_control`
--
ALTER TABLE `admin_control`
  ADD PRIMARY KEY (`control_key`);

--
-- Indexes for table `admin_money_history`
--
ALTER TABLE `admin_money_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`);

--
-- Indexes for table `bank_accounts`
--
ALTER TABLE `bank_accounts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bets`
--
ALTER TABLE `bets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_draw` (`draw_date`,`draw_time`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `bet_history`
--
ALTER TABLE `bet_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bet_records`
--
ALTER TABLE `bet_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `closing_dates`
--
ALTER TABLE `closing_dates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `closing_date` (`closing_date`),
  ADD KEY `idx_date` (`closing_date`);

--
-- Indexes for table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `deposits`
--
ALTER TABLE `deposits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `draw_results`
--
ALTER TABLE `draw_results`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_draw_unique` (`draw_date`,`draw_time`),
  ADD KEY `idx_number` (`number`);

--
-- Indexes for table `fake_winners`
--
ALTER TABLE `fake_winners`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key` (`key`);

--
-- Indexes for table `slot_bets`
--
ALTER TABLE `slot_bets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `slot_results`
--
ALTER TABLE `slot_results`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `slot_rounds`
--
ALTER TABLE `slot_rounds`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD KEY `idx_phone` (`phone`);

--
-- Indexes for table `user_bets`
--
ALTER TABLE `user_bets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_tokens`
--
ALTER TABLE `user_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `wallets`
--
ALTER TABLE `wallets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `winners`
--
ALTER TABLE `winners`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `withdrawals`
--
ALTER TABLE `withdrawals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_balance`
--
ALTER TABLE `admin_balance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `admin_money_history`
--
ALTER TABLE `admin_money_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `bank_accounts`
--
ALTER TABLE `bank_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `bets`
--
ALTER TABLE `bets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `bet_history`
--
ALTER TABLE `bet_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bet_records`
--
ALTER TABLE `bet_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `closing_dates`
--
ALTER TABLE `closing_dates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `deposits`
--
ALTER TABLE `deposits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `draw_results`
--
ALTER TABLE `draw_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fake_winners`
--
ALTER TABLE `fake_winners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `slot_bets`
--
ALTER TABLE `slot_bets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `slot_results`
--
ALTER TABLE `slot_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `slot_rounds`
--
ALTER TABLE `slot_rounds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=161;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `user_bets`
--
ALTER TABLE `user_bets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=140;

--
-- AUTO_INCREMENT for table `user_tokens`
--
ALTER TABLE `user_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=162;

--
-- AUTO_INCREMENT for table `wallets`
--
ALTER TABLE `wallets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `winners`
--
ALTER TABLE `winners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=501;

--
-- AUTO_INCREMENT for table `withdrawals`
--
ALTER TABLE `withdrawals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bets`
--
ALTER TABLE `bets`
  ADD CONSTRAINT `bets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `deposits`
--
ALTER TABLE `deposits`
  ADD CONSTRAINT `deposits_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_tokens`
--
ALTER TABLE `user_tokens`
  ADD CONSTRAINT `user_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `withdrawals`
--
ALTER TABLE `withdrawals`
  ADD CONSTRAINT `withdrawals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;