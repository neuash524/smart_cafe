-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 07, 2026 at 12:35 AM
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
-- Database: `smart_cafe`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `add_to_queue` (IN `p_customer_name` VARCHAR(255), IN `p_customer_phone` VARCHAR(20), IN `p_party_size` INT)   BEGIN
    DECLARE next_position INT;
    
    -- Get next position in queue
    SELECT COALESCE(MAX(position), 0) + 1 INTO next_position
    FROM queue WHERE status = 'waiting';
    
    INSERT INTO queue (
        customer_name, customer_phone, party_size,
        position, estimated_wait_time, status
    ) VALUES (
        p_customer_name, p_customer_phone, p_party_size,
        next_position, next_position * 15, 'waiting'
    );
    
    SELECT LAST_INSERT_ID() as queue_id, next_position as position;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `create_order` (IN `p_user_id` INT, IN `p_customer_name` VARCHAR(255), IN `p_customer_phone` VARCHAR(20), IN `p_total_amount` DECIMAL(10,2), IN `p_order_type` VARCHAR(20))   BEGIN
    INSERT INTO orders (
        user_id, customer_name, customer_phone,
        total_amount, order_type, status
    ) VALUES (
        p_user_id, p_customer_name, p_customer_phone,
        p_total_amount, p_order_type, 'pending'
    );
    
    SELECT LAST_INSERT_ID() as order_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `create_reservation` (IN `p_user_id` INT, IN `p_customer_name` VARCHAR(255), IN `p_customer_email` VARCHAR(255), IN `p_customer_phone` VARCHAR(20), IN `p_table_id` INT, IN `p_reservation_date` DATE, IN `p_reservation_time` TIME, IN `p_number_of_guests` INT, IN `p_special_requests` TEXT)   BEGIN
    INSERT INTO reservations (
        user_id, customer_name, customer_email, customer_phone,
        table_id, reservation_date, reservation_time,
        number_of_guests, special_requests, status
    ) VALUES (
        p_user_id, p_customer_name, p_customer_email, p_customer_phone,
        p_table_id, p_reservation_date, p_reservation_time,
        p_number_of_guests, p_special_requests, 'pending'
    );
    
    -- Update table status
    UPDATE cafe_tables SET status = 'reserved' WHERE table_id = p_table_id;
    
    SELECT LAST_INSERT_ID() as reservation_id;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action_type` varchar(100) NOT NULL,
  `table_name` varchar(100) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`log_id`, `user_id`, `action_type`, `table_name`, `record_id`, `description`, `ip_address`, `created_at`) VALUES
(1, NULL, 'CREATE', 'reservations', 10, 'New reservation for Hari Oli on 2026-03-27', NULL, '2026-03-27 08:42:33'),
(2, NULL, 'CREATE', 'reservations', 10, 'Reservation created for Hari Oli on 2026-03-27 at 09:48', '::1', '2026-03-27 08:42:33'),
(3, NULL, 'CREATE', 'orders', 2, 'Order ORD-002 placed by Hari Oli — $12.99', '::1', '2026-03-27 08:43:16'),
(4, NULL, 'UPDATE', 'cafe_tables', 2, 'Table Table 2 status changed from available to available', '::1', '2026-03-27 08:44:47'),
(5, NULL, 'UPDATE', 'cafe_tables', 1, 'Table Table 1 status changed from reserved to available', '::1', '2026-03-27 08:44:49'),
(6, NULL, 'UPDATE', 'cafe_tables', 1, 'Table Table 1 status changed from available to occupied', '::1', '2026-03-27 08:47:04'),
(7, NULL, 'UPDATE', 'cafe_tables', 3, 'Table Table 3 status changed from available to available', '::1', '2026-03-27 08:47:06'),
(8, NULL, 'UPDATE', 'cafe_tables', 2, 'Table Table 2 status changed from available to occupied', '::1', '2026-03-27 08:48:26'),
(9, NULL, 'UPDATE', 'cafe_tables', 3, 'Table Table 3 status changed from available to occupied', '::1', '2026-03-27 09:20:41'),
(10, NULL, 'UPDATE', 'cafe_tables', 3, 'Table Table 3 status changed from occupied to available', '::1', '2026-03-27 09:20:50'),
(11, NULL, 'CREATE', 'reservations', 11, 'New reservation for Hari Oli on 2026-03-27', NULL, '2026-03-27 09:24:07'),
(12, NULL, 'CREATE', 'reservations', 11, 'Reservation created for Hari Oli on 2026-03-27 at 12:30', '::1', '2026-03-27 09:24:07'),
(13, NULL, 'UPDATE', 'cafe_tables', 1, 'Table Table 1 status changed from occupied to available', '::1', '2026-03-27 09:24:48'),
(14, NULL, 'UPDATE', 'cafe_tables', 2, 'Table Table 2 status changed from occupied to available', '::1', '2026-03-27 09:24:49'),
(15, NULL, 'CREATE', 'orders', 3, 'Order ORD-003 placed by Hari Oli — $8.99', '::1', '2026-03-27 09:25:32'),
(16, NULL, 'UPDATE', 'cafe_tables', 3, 'Table Table 3 status changed from reserved to available', '::1', '2026-03-31 20:44:26'),
(17, NULL, 'UPDATE', 'cafe_tables', 1, 'Table Table 1 status changed from available to occupied', '::1', '2026-03-31 20:44:32'),
(18, NULL, 'UPDATE', 'orders', 3, 'Order #ORD-003 status changed from pending to preparing', '::1', '2026-03-31 20:58:45'),
(19, NULL, 'UPDATE', 'orders', 3, 'Order #ORD-003 status changed from preparing to ready', '::1', '2026-03-31 20:58:50'),
(20, NULL, 'UPDATE', 'orders', 3, 'Order #ORD-003 status changed from ready to completed', '::1', '2026-03-31 20:58:58'),
(21, NULL, 'UPDATE', 'cafe_tables', 1, 'Table Table 1 status changed from occupied to available', '::1', '2026-03-31 20:59:47'),
(22, NULL, 'UPDATE', 'orders', 1, 'Order #ORD-001 status changed from preparing to ready', '::1', '2026-03-31 21:07:27'),
(23, NULL, 'UPDATE', 'orders', 1, 'Order #ORD-001 status changed from ready to completed', '::1', '2026-03-31 21:08:08'),
(24, NULL, 'UPDATE', 'orders', 2, 'Order #ORD-002 status changed from pending to preparing', '::1', '2026-03-31 21:08:50'),
(25, NULL, 'CREATE', 'reservations', 12, 'New reservation for Neymar Jr on 2026-04-01', NULL, '2026-03-31 21:23:45'),
(26, NULL, 'CREATE', 'reservations', 12, 'Reservation created for Neymar Jr on 2026-04-01 at 08:30', '::1', '2026-03-31 21:23:45'),
(27, NULL, 'CREATE', 'orders', 4, 'Order ORD-004 placed by Neymar Jr — $9.99', '::1', '2026-03-31 21:23:57'),
(28, NULL, 'UPDATE', 'orders', 4, 'Order #ORD-004 status changed from pending to preparing', '::1', '2026-03-31 21:24:48'),
(29, NULL, 'UPDATE', 'orders', 4, 'Order #ORD-004 status changed from preparing to ready', '::1', '2026-03-31 21:25:36'),
(30, NULL, 'UPDATE', 'orders', 4, 'Order #ORD-004 status changed from ready to completed', '::1', '2026-03-31 21:25:39'),
(31, NULL, 'UPDATE', 'orders', 2, 'Order #ORD-002 status changed from preparing to ready', '::1', '2026-03-31 21:47:38'),
(32, NULL, 'UPDATE', 'orders', 2, 'Order #ORD-002 status changed from ready to completed', '::1', '2026-03-31 21:47:40'),
(33, NULL, 'CREATE', 'orders', 5, 'Order ORD-005 placed by Neymar Jr — $10.99', '::1', '2026-03-31 21:48:21'),
(34, NULL, 'UPDATE', 'orders', 5, 'Order #ORD-005 status changed from pending to preparing', '::1', '2026-03-31 21:48:54'),
(35, NULL, 'UPDATE', 'orders', 5, 'Order #ORD-005 status changed from preparing to ready', '::1', '2026-03-31 21:48:57'),
(36, NULL, 'UPDATE', 'orders', 5, 'Order #ORD-005 status changed from ready to completed', '::1', '2026-03-31 21:50:03'),
(37, NULL, 'CREATE', 'reservations', 13, 'New reservation for Prab Shiram on 2026-04-02', NULL, '2026-03-31 22:31:59'),
(38, NULL, 'CREATE', 'reservations', 13, 'Reservation created for Prab Shiram on 2026-04-02 at 05:30', '::1', '2026-03-31 22:31:59'),
(39, NULL, 'CREATE', 'orders', 6, 'Order ORD-006 placed by Prab Shiram — $4.5', '::1', '2026-03-31 22:37:03'),
(40, NULL, 'UPDATE', 'orders', 6, 'Order #ORD-006 status changed from pending to preparing', '::1', '2026-03-31 22:38:05'),
(41, NULL, 'UPDATE', 'orders', 6, 'Order #ORD-006 status changed from preparing to ready', '::1', '2026-03-31 22:38:07'),
(42, NULL, 'UPDATE', 'orders', 6, 'Order #ORD-006 status changed from ready to completed', '::1', '2026-03-31 22:38:08'),
(43, NULL, 'CREATE', 'orders', 7, 'Order ORD-007 placed by Prab Shiram — $15.99', '::1', '2026-03-31 22:53:12'),
(44, NULL, 'UPDATE', 'orders', 7, 'Order #ORD-007 status changed from pending to preparing', '::1', '2026-03-31 22:54:07'),
(45, NULL, 'UPDATE', 'orders', 7, 'Order #ORD-007 status changed from preparing to ready', '::1', '2026-03-31 22:54:10'),
(46, NULL, 'UPDATE', 'orders', 7, 'Order #ORD-007 status changed from ready to completed', '::1', '2026-03-31 22:55:53'),
(47, NULL, 'UPDATE', 'cafe_tables', 1, 'Table Table 1 status changed from reserved to available', '::1', '2026-04-01 10:58:46'),
(48, NULL, 'UPDATE', 'cafe_tables', 3, 'Table Table 3 status changed from reserved to available', '::1', '2026-04-01 10:58:47'),
(49, NULL, 'UPDATE', 'cafe_tables', 1, 'Table Table 1 status changed from available to occupied', '::1', '2026-04-01 10:58:59'),
(50, NULL, 'UPDATE', 'cafe_tables', 2, 'Table Table 2 status changed from available to occupied', '::1', '2026-04-01 11:00:36'),
(51, NULL, 'UPDATE', 'cafe_tables', 2, 'Table Table 2 status changed from occupied to available', '::1', '2026-04-01 11:15:55'),
(52, NULL, 'UPDATE', 'cafe_tables', 1, 'Table Table 1 status changed from occupied to available', '::1', '2026-04-01 11:15:57'),
(53, 4, 'CREATE', 'reservations', 14, 'New reservation for Neymar Jr on 2026-04-01', NULL, '2026-04-01 11:17:43'),
(54, 4, 'CREATE', 'reservations', 14, 'Reservation created for Neymar Jr on 2026-04-01 at 20:30', '::1', '2026-04-01 11:17:43'),
(55, NULL, 'UPDATE', 'cafe_tables', 1, 'Table Table 1 status changed from available to occupied', '::1', '2026-04-01 11:20:28'),
(56, 4, 'CREATE', 'reservations', 15, 'New reservation for Neymar Jr on 2026-04-02', NULL, '2026-04-02 17:37:51'),
(57, 4, 'CREATE', 'reservations', 15, 'Reservation request created for Neymar Jr on 2026-04-02 at 21:40 (pending approval)', '::1', '2026-04-02 17:37:51'),
(58, NULL, 'UPDATE', 'cafe_tables', 3, 'Table Table 3 status changed from reserved to occupied', '::1', '2026-04-02 17:46:31'),
(59, NULL, 'UPDATE', 'cafe_tables', 4, 'Table Table 4 status changed from occupied to occupied', '::1', '2026-04-02 17:53:44'),
(60, NULL, 'UPDATE', 'cafe_tables', 4, 'Table Table 4 status changed from occupied to available', '::1', '2026-04-02 17:54:24'),
(61, NULL, 'UPDATE', 'cafe_tables', 5, 'Table Table 5 status changed from reserved to available', '::1', '2026-04-02 17:54:28'),
(62, 5, 'CREATE', 'reservations', 16, 'New reservation for Prab Shiram on 2026-04-02', NULL, '2026-04-02 17:55:02'),
(63, 5, 'CREATE', 'reservations', 16, 'Reservation request created for Prab Shiram on 2026-04-02 at 22:54 (pending approval)', '::1', '2026-04-02 17:55:02'),
(64, NULL, 'UPDATE', 'cafe_tables', 1, 'Table Table 1 status changed from occupied to available', '::1', '2026-04-05 09:02:28'),
(65, NULL, 'UPDATE', 'cafe_tables', 3, 'Table Table 3 status changed from occupied to available', '::1', '2026-04-05 09:02:30'),
(66, NULL, 'UPDATE', 'cafe_tables', 4, 'Table Table 4 status changed from reserved to available', '::1', '2026-04-05 09:02:31'),
(67, 6, 'CREATE', 'reservations', 17, 'New reservation for Baibab Bista on 2026-04-05', NULL, '2026-04-05 09:12:31'),
(68, 6, 'CREATE', 'reservations', 17, 'Reservation request created for Baibab Bista on 2026-04-05 at 16:12 (pending approval)', '::1', '2026-04-05 09:12:31'),
(69, NULL, 'UPDATE', 'cafe_tables', 4, 'Table Table 4 status changed from reserved to occupied', '::1', '2026-04-05 09:15:16'),
(70, 6, 'CREATE', 'reservations', 18, 'New reservation for Baibab Bista on 2026-04-05', NULL, '2026-04-05 20:44:41'),
(71, 6, 'CREATE', 'reservations', 18, 'Reservation request created for Baibab Bista on 2026-04-05 at 02:48 (pending approval)', '::1', '2026-04-05 20:44:41'),
(72, NULL, 'UPDATE', 'cafe_tables', 1, 'Table Table 1 status changed from reserved to occupied', '::1', '2026-04-05 20:48:56'),
(73, NULL, 'UPDATE', 'cafe_tables', 1, 'Table Table 1 status changed from occupied to available', '::1', '2026-04-13 11:01:32'),
(74, NULL, 'UPDATE', 'cafe_tables', 4, 'Table Table 4 status changed from occupied to available', '::1', '2026-04-13 11:01:34'),
(75, NULL, 'UPDATE', 'cafe_tables', 1, 'Table Table 1 status changed from available to occupied', '::1', '2026-04-14 00:23:20'),
(76, NULL, 'UPDATE', 'cafe_tables', 2, 'Table Table 2 status changed from available to occupied', '::1', '2026-04-14 00:23:27'),
(77, 7, 'CREATE', 'reservations', 19, 'New reservation for Madi Kumar on 2026-04-14', NULL, '2026-04-14 00:26:34'),
(78, 7, 'CREATE', 'reservations', 19, 'Reservation request created for Madi Kumar on 2026-04-14 at 07:30 (pending approval)', '::1', '2026-04-14 00:26:34'),
(79, NULL, 'UPDATE', 'cafe_tables', 1, 'Table Table 1 status changed from occupied to available', '::1', '2026-04-14 11:06:50'),
(80, NULL, 'UPDATE', 'cafe_tables', 2, 'Table Table 2 status changed from occupied to available', '::1', '2026-04-14 11:06:51'),
(81, 10, 'CREATE', 'reservations', 20, 'New reservation for Aashish Neupane on 2026-04-16', NULL, '2026-04-15 21:45:39'),
(82, 10, 'CREATE', 'reservations', 20, 'Reservation request created for Aashish Neupane on 2026-04-16 at 13:00 (pending approval)', '::1', '2026-04-15 21:45:39'),
(83, 10, 'CREATE', 'reservations', 21, 'New reservation for Aashish Neupane on 2026-04-17', NULL, '2026-04-15 22:14:34'),
(84, NULL, 'UPDATE', 'reservations', 21, 'Reservation #21 status changed from pending to confirmed', '::1', '2026-04-15 22:15:19'),
(85, NULL, 'UPDATE', 'reservations', 21, 'Reservation #21 status changed from confirmed to confirmed', '::1', '2026-04-15 22:19:53'),
(86, NULL, 'UPDATE', 'reservations', 21, 'Reservation #21 status changed from confirmed to confirmed', '::1', '2026-04-15 22:24:39'),
(87, 10, 'CREATE', 'reservations', 22, 'New reservation for Aashish Neupane on 2026-04-24', NULL, '2026-04-15 22:36:44'),
(88, NULL, 'UPDATE', 'cafe_tables', 2, 'Table Table 2 status changed from reserved to available', '::1', '2026-04-15 22:37:04'),
(89, NULL, 'UPDATE', 'cafe_tables', 3, 'Table Table 3 status changed from reserved to available', '::1', '2026-04-15 22:37:05'),
(90, 10, 'CREATE', 'reservations', 23, 'New reservation for Aashish Neupane on 2026-04-17', NULL, '2026-04-15 23:21:02'),
(91, NULL, 'UPDATE', 'reservations', 23, 'Reservation #23 status changed from pending to confirmed', '::1', '2026-04-15 23:21:46'),
(92, NULL, 'UPDATE', 'cafe_tables', 3, 'Table Table 3 status changed from reserved to available', '::1', '2026-04-15 23:27:16'),
(93, NULL, 'UPDATE', 'cafe_tables', 4, 'Table Table 4 status changed from reserved to available', '::1', '2026-04-15 23:27:17'),
(94, NULL, 'UPDATE', 'reservations', 19, 'Reservation #19 status changed from cancelled to confirmed', '::1', '2026-04-15 23:27:42'),
(95, 7, 'CREATE', 'reservations', 24, 'New reservation for Madi Kumar on 2026-04-16', NULL, '2026-04-16 08:15:34'),
(96, NULL, 'UPDATE', 'cafe_tables', 3, 'Table Table 3 status changed from reserved to available', '::1', '2026-04-16 08:16:15'),
(97, 1, 'CREATE', 'reservations', 25, 'New reservation for Madi Kumar on 2026-04-16', NULL, '2026-04-16 08:16:57'),
(98, NULL, 'CREATE', 'reservations', 26, 'New reservation for Pukar Neupane on 2026-04-19', NULL, '2026-04-18 20:57:14'),
(99, NULL, 'UPDATE', 'reservations', 26, 'Reservation #26 status changed from pending to confirmed', '::1', '2026-04-18 20:58:07'),
(100, NULL, 'UPDATE', 'cafe_tables', 2, 'Table Table 2 status changed from reserved to available', '::1', '2026-04-18 20:59:27'),
(101, NULL, 'UPDATE', 'cafe_tables', 3, 'Table Table 3 status changed from reserved to available', '::1', '2026-04-18 20:59:29'),
(102, NULL, 'UPDATE', 'reservations', 22, 'Reservation #22 status changed from pending to cancelled', '::1', '2026-04-18 20:59:46'),
(103, 10, 'CREATE', 'reservations', 27, 'New reservation for Aashish Neupane on 2026-04-19', NULL, '2026-04-18 21:01:36'),
(104, NULL, 'UPDATE', 'reservations', 27, 'Reservation #27 status changed from pending to confirmed', '::1', '2026-04-18 21:02:28'),
(105, 10, 'CREATE', 'reservations', 28, 'New reservation for Aashish Neupane on 2026-04-18', NULL, '2026-04-18 21:20:19'),
(106, NULL, 'UPDATE', 'reservations', 28, 'Reservation #28 status changed from pending to confirmed', '::1', '2026-04-18 21:38:19'),
(107, NULL, 'UPDATE', 'reservations', 28, 'Reservation #28 status changed from confirmed to completed', '::1', '2026-04-18 21:38:25'),
(108, NULL, 'UPDATE', 'cafe_tables', 2, 'Table Table 2 status changed from reserved to occupied', '::1', '2026-04-18 21:38:27'),
(109, NULL, 'UPDATE', 'reservations', 25, 'Reservation #25 status changed from pending to confirmed', '::1', '2026-04-18 21:45:58'),
(110, NULL, 'UPDATE', 'cafe_tables', 3, 'Table Table 3 status changed from reserved to occupied', '::1', '2026-04-18 21:46:05'),
(111, NULL, 'UPDATE', 'cafe_tables', 3, 'Table Table 3 status changed from occupied to available', '::1', '2026-04-18 21:46:23'),
(112, 1, 'CREATE', 'orders', 15, 'Order #ORD-0416 placed by Admin User — $13.99', '::1', '2026-04-18 21:50:26'),
(113, 10, 'CREATE', 'queue', 17, 'Aashish Neupane joined queue - Position #2, Party of 2', '::1', '2026-04-19 08:20:03'),
(114, NULL, 'DELETE', 'queue', 17, 'Queue entry removed for Aashish Neupane (Party of 2)', '::1', '2026-04-19 08:20:46'),
(115, 10, 'CREATE', 'queue', 18, 'Aashish Neupane joined queue - Position #2, Party of 2', '::1', '2026-04-19 08:21:51'),
(116, NULL, 'DELETE', 'queue', 18, 'Queue entry removed for Aashish Neupane (Party of 2)', '::1', '2026-04-19 08:22:48'),
(117, 10, 'CREATE', 'queue', 19, 'Aashish Neupane joined queue - Position #2, Party of 2', '::1', '2026-04-19 08:23:15'),
(118, 10, 'CREATE', 'orders', 16, 'Order #ORD-9454 placed by Aashish Neupane — $12.99', '::1', '2026-04-19 08:58:17'),
(119, 10, 'CREATE', 'orders', 17, 'Order #ORD-0307 placed by Aashish Neupane — $14.99', '::1', '2026-04-19 09:07:43'),
(120, 10, 'CREATE', 'orders', 18, 'Order #ORD-8925 placed by Aashish Neupane — $6.99', '::1', '2026-04-19 09:10:53'),
(121, 10, 'CREATE', 'orders', 19, 'Order #ORD-4987 placed by Aashish Neupane — $12.99', '::1', '2026-04-19 09:31:29'),
(122, 10, 'CREATE', 'orders', 20, 'Order #ORD-7903 placed by Aashish Neupane — $21.98', '::1', '2026-04-19 09:42:51'),
(123, NULL, 'UPDATE', 'orders', 20, 'Order #ORD-7903 status changed from pending to preparing', '::1', '2026-04-19 12:11:34'),
(124, NULL, 'UPDATE', 'orders', 20, 'Order #ORD-7903 status changed from preparing to ready', '::1', '2026-04-19 12:11:38'),
(125, NULL, 'UPDATE', 'orders', 20, 'Order #ORD-7903 status changed from ready to completed', '::1', '2026-04-19 12:11:45'),
(126, 10, 'CREATE', 'orders', 21, 'Order #ORD-5210 placed by Aashish Neupane — $7.99', '::1', '2026-04-20 20:49:58'),
(127, 10, 'CREATE', 'orders', 22, 'Order #ORD-5091 placed by Aashish Neupane — $12.99', '::1', '2026-04-20 20:56:35'),
(128, NULL, 'UPDATE', 'reservations', 23, 'Reservation #23 status changed from confirmed to confirmed', '::1', '2026-04-21 10:08:41'),
(129, NULL, 'UPDATE', 'reservations', 28, 'Reservation #28 status changed from completed to confirmed', '::1', '2026-04-21 10:09:18'),
(130, NULL, 'UPDATE', 'reservations', 28, 'Reservation #28 status changed from confirmed to confirmed', '::1', '2026-04-21 10:09:51'),
(131, NULL, 'UPDATE', 'cafe_tables', 1, 'Table Table 1 status changed from reserved to available', '::1', '2026-04-21 19:09:52'),
(132, NULL, 'UPDATE', 'cafe_tables', 2, 'Table Table 2 status changed from reserved to available', '::1', '2026-04-21 19:09:54'),
(133, 10, 'CREATE', 'reservations', 29, 'New reservation for Aashish Neupane on 2026-04-21', NULL, '2026-04-21 19:10:40'),
(134, NULL, 'UPDATE', 'reservations', 29, 'Reservation #29 status changed from pending to confirmed', '::1', '2026-04-21 19:11:21'),
(135, NULL, 'DELETE', 'queue', 19, 'Queue entry removed for Aashish Neupane (Party of 2)', '::1', '2026-04-21 19:11:46'),
(136, 10, 'CREATE', 'queue', 20, 'Aashish Neupane joined queue - Position #2, Party of 2', '::1', '2026-04-21 19:12:36'),
(137, NULL, 'UPDATE', 'queue', 20, 'Queue entry for Aashish Neupane seated (Party of 2)', '::1', '2026-04-21 19:12:53'),
(138, NULL, 'UPDATE', 'cafe_tables', 1, 'Table Table 1 status changed from reserved to occupied', '::1', '2026-04-21 19:12:55'),
(139, NULL, 'UPDATE', 'reservations', 29, 'Reservation #29 status changed from confirmed to completed', '::1', '2026-04-21 19:12:55'),
(140, 10, 'CREATE', 'orders', 23, 'Order #ORD-9165 placed by Aashish Neupane — $12.99', '::1', '2026-04-21 19:20:39'),
(141, 12, 'CREATE', 'reservations', 30, 'New reservation for San Watson on 2026-04-22', NULL, '2026-04-21 19:21:55'),
(142, 5, 'CREATE', 'reservations', 31, 'New reservation for Prab Shiram on 2026-04-21', NULL, '2026-04-21 19:24:13'),
(143, 7, 'CREATE', 'reservations', 32, 'New reservation for Madi Kumar on 2026-04-22', NULL, '2026-04-21 19:25:43'),
(144, NULL, 'UPDATE', 'reservations', 32, 'Reservation #32 status changed from pending to cancelled', '::1', '2026-04-21 19:26:26'),
(145, NULL, 'UPDATE', 'reservations', 3, 'Reservation #3 status changed from pending to cancelled', '::1', '2026-04-21 19:26:29'),
(146, NULL, 'UPDATE', 'reservations', 30, 'Reservation #30 status changed from pending to cancelled', '::1', '2026-04-21 19:27:23'),
(147, NULL, 'UPDATE', 'reservations', 31, 'Reservation #31 status changed from pending to cancelled', '::1', '2026-04-21 19:27:30'),
(148, NULL, 'UPDATE', 'reservations', 24, 'Reservation #24 status changed from pending to cancelled', '::1', '2026-04-21 19:27:37'),
(149, NULL, 'UPDATE', 'cafe_tables', 7, 'Table Table 7 status changed from available to occupied', '::1', '2026-04-21 19:27:45'),
(150, NULL, 'UPDATE', 'cafe_tables', 7, 'Table Table 7 status changed from occupied to available', '::1', '2026-04-21 19:27:53'),
(151, 7, 'CREATE', 'reservations', 33, 'New reservation for Madi Kumar on 2026-04-22', NULL, '2026-04-21 19:28:52'),
(152, NULL, 'UPDATE', 'cafe_tables', 3, 'Table Table 3 status changed from reserved to available', '::1', '2026-04-21 19:29:21'),
(153, NULL, 'UPDATE', 'reservations', 9, 'Reservation #9 status changed from pending to cancelled', '::1', '2026-04-21 19:29:36'),
(154, NULL, 'UPDATE', 'reservations', 33, 'Reservation #33 status changed from pending to cancelled', '::1', '2026-04-21 19:29:42'),
(155, NULL, 'UPDATE', 'reservations', 8, 'Reservation #8 status changed from pending to cancelled', '::1', '2026-04-21 19:29:59'),
(156, 10, 'CREATE', 'reservations', 34, 'New reservation for Aashish Neupane on 2026-04-21', NULL, '2026-04-21 19:31:11'),
(157, NULL, 'UPDATE', 'reservations', 34, 'Reservation #34 status changed from pending to cancelled', '::1', '2026-04-21 19:31:46'),
(158, NULL, 'UPDATE', 'cafe_tables', 5, 'Table Table 5 status changed from available to occupied', '::1', '2026-04-21 19:31:51'),
(159, NULL, 'UPDATE', 'cafe_tables', 5, 'Table Table 5 status changed from occupied to available', '::1', '2026-04-21 19:32:06'),
(160, NULL, 'UPDATE', 'cafe_tables', 1, 'Table Table 1 status changed from available to available', '::1', '2026-04-21 19:32:09'),
(161, NULL, 'UPDATE', 'cafe_tables', 6, 'Table Table 6 status changed from occupied to available', '::1', '2026-04-21 21:13:43'),
(162, NULL, 'UPDATE', 'cafe_tables', 1, 'Table Table 1 status changed from available to occupied', '::1', '2026-04-21 21:21:31'),
(163, NULL, 'UPDATE', 'cafe_tables', 2, 'Table Table 2 status changed from available to occupied', '::1', '2026-04-21 21:21:33'),
(164, NULL, 'UPDATE', 'cafe_tables', 2, 'Table Table 2 status changed from occupied to available', '::1', '2026-04-21 21:22:00'),
(165, NULL, 'UPDATE', 'cafe_tables', 1, 'Table Table 1 status changed from occupied to available', '::1', '2026-04-21 21:22:01'),
(166, 10, 'CREATE', 'reservations', 35, 'New reservation for Aashish Neupane on 2026-04-22', NULL, '2026-04-22 11:12:50'),
(167, NULL, 'UPDATE', 'reservations', 35, 'Reservation #35 status changed from pending to confirmed', '::1', '2026-04-22 11:13:37'),
(168, 13, 'CREATE', 'reservations', 36, 'New reservation for Punam william on 2026-04-23', NULL, '2026-04-22 11:19:23'),
(169, NULL, 'UPDATE', 'reservations', 36, 'Reservation #36 status changed from pending to confirmed', '::1', '2026-04-22 11:19:49'),
(170, NULL, 'UPDATE', 'reservations', 36, 'Reservation #36 status changed from confirmed to confirmed', '::1', '2026-04-22 11:24:22'),
(171, 1, 'CREATE', 'reservations', 37, 'New reservation for Punam william on 2026-04-22', NULL, '2026-04-22 11:25:52'),
(172, NULL, 'UPDATE', 'reservations', 37, 'Reservation #37 status changed from pending to confirmed', '::1', '2026-04-22 11:26:09'),
(173, 10, 'CREATE', 'reservations', 38, 'New reservation for Aashish Neupane on 2026-04-22', NULL, '2026-04-22 11:27:38'),
(174, NULL, 'UPDATE', 'reservations', 38, 'Reservation #38 status changed from pending to confirmed', '::1', '2026-04-22 11:28:12'),
(175, NULL, 'UPDATE', 'cafe_tables', 1, 'Table Table 1 status changed from reserved to available', '::1', '2026-04-22 11:28:36'),
(176, NULL, 'UPDATE', 'cafe_tables', 2, 'Table Table 2 status changed from reserved to available', '::1', '2026-04-22 11:28:38'),
(177, NULL, 'UPDATE', 'cafe_tables', 3, 'Table Table 3 status changed from reserved to available', '::1', '2026-04-22 11:28:39'),
(178, NULL, 'UPDATE', 'cafe_tables', 4, 'Table Table 4 status changed from reserved to available', '::1', '2026-04-22 11:28:41'),
(179, 10, 'CREATE', 'reservations', 39, 'New reservation for Aashish Neupane on 2026-04-27', NULL, '2026-04-26 18:55:23'),
(180, 10, 'CREATE', 'queue', 21, 'Aashish Neupane joined queue - Position #2, Party of 2', '::1', '2026-04-26 18:55:42'),
(181, NULL, 'UPDATE', 'reservations', 39, 'Reservation #39 status changed from pending to confirmed', '::1', '2026-04-26 18:56:24'),
(182, 10, 'CREATE', 'reservations', 40, 'New reservation for Aashish Neupane on 2026-04-26', NULL, '2026-04-26 18:59:24'),
(183, NULL, 'UPDATE', 'reservations', 40, 'Reservation #40 status changed from pending to confirmed', '::1', '2026-04-26 18:59:52'),
(184, NULL, 'UPDATE', 'queue', 21, 'Queue entry for Aashish Neupane seated (Party of 2)', '::1', '2026-04-26 18:59:55'),
(185, NULL, 'UPDATE', 'cafe_tables', 2, 'Table Table 2 status changed from reserved to occupied', '::1', '2026-04-26 18:59:59'),
(186, NULL, 'UPDATE', 'reservations', 40, 'Reservation #40 status changed from confirmed to completed', '::1', '2026-04-26 18:59:59'),
(187, 1, 'CREATE', 'orders', 24, 'Order #ORD-3487 placed by Admin User — $8.99', '::1', '2026-04-26 19:00:55'),
(188, NULL, 'UPDATE', 'orders', 23, 'Order #ORD-9165 status changed from pending to preparing', '::1', '2026-04-26 19:01:40'),
(189, NULL, 'UPDATE', 'orders', 23, 'Order #ORD-9165 status changed from preparing to ready', '::1', '2026-04-26 19:02:01'),
(190, NULL, 'UPDATE', 'orders', 23, 'Order #ORD-9165 status changed from ready to completed', '::1', '2026-04-26 19:02:18'),
(191, NULL, 'UPDATE', 'cafe_tables', 1, 'Table Table 1 status changed from reserved to available', '::1', '2026-05-03 16:42:12'),
(192, NULL, 'UPDATE', 'cafe_tables', 2, 'Table Table 2 status changed from occupied to available', '::1', '2026-05-03 16:42:13'),
(193, 10, 'CREATE', 'queue', 22, 'Aashish Neupane joined queue - Position #2, Party of 2', '::1', '2026-05-03 21:21:43'),
(194, 10, 'CREATE', 'queue', 23, 'Aashish Neupane joined queue - Position #3, Party of 3', '::1', '2026-05-03 21:21:52'),
(195, 10, 'CREATE', 'orders', 25, 'Order #ORD-4022 placed by Aashish Neupane — $13.99', '::1', '2026-05-06 11:41:40'),
(196, 10, 'CREATE', 'orders', 26, 'Order #ORD-8181 placed by Aashish Neupane — $24.98', '::1', '2026-05-06 11:42:36');

-- --------------------------------------------------------

--
-- Stand-in structure for view `available_tables`
-- (See below for the actual view)
--
CREATE TABLE `available_tables` (
`table_id` int(11)
,`table_number` varchar(50)
,`capacity` int(11)
);

-- --------------------------------------------------------

--
-- Table structure for table `cafe_tables`
--

CREATE TABLE `cafe_tables` (
  `table_id` int(11) NOT NULL,
  `table_number` varchar(50) NOT NULL,
  `capacity` int(11) NOT NULL,
  `status` enum('available','occupied','reserved','maintenance') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cafe_tables`
--

INSERT INTO `cafe_tables` (`table_id`, `table_number`, `capacity`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Table 1', 2, 'available', '2026-03-27 08:40:04', '2026-05-03 16:42:12'),
(2, 'Table 2', 2, 'available', '2026-03-27 08:40:04', '2026-05-03 16:42:13'),
(3, 'Table 3', 4, 'available', '2026-03-27 08:40:04', '2026-04-22 11:28:39'),
(4, 'Table 4', 4, 'available', '2026-03-27 08:40:04', '2026-04-22 11:28:41'),
(5, 'Table 5', 6, 'available', '2026-03-27 08:40:04', '2026-04-21 19:32:06'),
(6, 'Table 6', 6, 'available', '2026-03-27 08:40:04', '2026-04-21 21:13:43'),
(7, 'Table 7', 8, 'available', '2026-03-27 08:40:04', '2026-04-21 19:27:53'),
(8, 'Table 8', 4, 'available', '2026-03-27 08:40:04', '2026-04-21 19:26:23');

-- --------------------------------------------------------

--
-- Stand-in structure for view `current_queue`
-- (See below for the actual view)
--
CREATE TABLE `current_queue` (
`queue_id` int(11)
,`customer_name` varchar(255)
,`customer_phone` varchar(20)
,`party_size` int(11)
,`position` int(11)
,`estimated_wait_time` int(11)
,`joined_at` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `menu_categories`
--

CREATE TABLE `menu_categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu_categories`
--

INSERT INTO `menu_categories` (`category_id`, `category_name`, `display_order`, `is_active`) VALUES
(1, 'Breakfast', 1, 1),
(2, 'Lunch', 2, 1),
(3, 'Beverages', 3, 1),
(4, 'Desserts', 4, 1);

-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

CREATE TABLE `menu_items` (
  `item_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `item_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `is_featured` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`item_id`, `category_id`, `item_name`, `description`, `price`, `image_url`, `is_available`, `is_featured`, `created_at`, `updated_at`) VALUES
(1, 1, 'Classic Pancakes', 'Fluffy buttermilk pancakes served with maple syrup and butter', 8.99, NULL, 1, 0, '2026-03-27 08:40:04', '2026-03-27 08:40:04'),
(2, 1, 'Eggs Benedict', 'Poached eggs on English muffin with hollandaise sauce', 12.99, NULL, 1, 0, '2026-03-27 08:40:04', '2026-03-27 08:40:04'),
(3, 1, 'French Toast', 'Thick-cut brioche with cinnamon and fresh berries', 9.99, NULL, 1, 0, '2026-03-27 08:40:04', '2026-03-27 08:40:04'),
(4, 1, 'Avocado Toast', 'Smashed avocado on sourdough with poached egg', 10.99, NULL, 1, 0, '2026-03-27 08:40:04', '2026-03-27 08:40:04'),
(5, 2, 'Caesar Salad', 'Crisp romaine with parmesan and house-made dressing', 11.99, NULL, 1, 0, '2026-03-27 08:40:04', '2026-03-27 08:40:04'),
(6, 2, 'Club Sandwich', 'Triple-decker with turkey, bacon, and fresh vegetables', 13.99, NULL, 1, 0, '2026-03-27 08:40:04', '2026-03-27 08:40:04'),
(7, 2, 'Beef Burger', 'Angus beef patty with cheddar, lettuce, and special sauce', 14.99, NULL, 1, 0, '2026-03-27 08:40:04', '2026-03-27 08:40:04'),
(8, 2, 'Grilled Chicken', 'Herb-marinated chicken breast with seasonal vegetables', 15.99, NULL, 1, 0, '2026-03-27 08:40:04', '2026-03-27 08:40:04'),
(9, 3, 'Espresso', 'Rich Italian espresso, double shot', 3.50, NULL, 1, 0, '2026-03-27 08:40:04', '2026-03-27 08:40:04'),
(10, 3, 'Cappuccino', 'Espresso with steamed milk and foam', 4.50, NULL, 1, 0, '2026-03-27 08:40:04', '2026-03-27 08:40:04'),
(11, 3, 'Iced Latte', 'Cold espresso with milk over ice', 5.00, NULL, 1, 0, '2026-03-27 08:40:04', '2026-03-27 08:40:04'),
(12, 3, 'Fresh Orange Juice', 'Freshly squeezed Valencia oranges', 4.00, NULL, 1, 0, '2026-03-27 08:40:04', '2026-03-27 08:40:04'),
(13, 4, 'Chocolate Cake', 'Rich chocolate layers with ganache frosting', 6.99, NULL, 1, 0, '2026-03-27 08:40:04', '2026-03-27 08:40:04'),
(14, 4, 'Tiramisu', 'Classic Italian dessert with mascarpone and coffee', 7.99, NULL, 1, 0, '2026-03-27 08:40:04', '2026-03-27 08:40:04'),
(15, 4, 'Apple Pie', 'Warm apple pie with vanilla ice cream', 6.50, NULL, 1, 0, '2026-03-27 08:40:04', '2026-03-27 08:40:04'),
(16, 4, 'Cheesecake', 'New York style with berry compote', 7.50, NULL, 1, 0, '2026-03-27 08:40:04', '2026-03-27 08:40:04');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `notification_type` enum('reservation','queue','order','payment','general') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `notification_type`, `title`, `message`, `is_read`, `created_at`) VALUES
(1, 2, 'order', 'Order Update: ORD-001', '✅ Your order is ready for pickup!', 0, '2026-03-31 21:07:27'),
(2, 2, 'order', 'Order Update: ORD-001', '🎉 Your order has been completed. Thank you!', 0, '2026-03-31 21:08:08'),
(4, 4, 'reservation', 'Reservation Confirmed', 'Your reservation at 20:30 on 2026-04-01 for 4 guests at Table 5 is confirmed.', 1, '2026-04-01 11:17:43'),
(5, 4, 'reservation', 'Reservation confirmed', 'Your reservation #14 has been confirmed.', 1, '2026-04-01 11:18:56'),
(6, 4, 'queue', 'Queue Update', 'You joined the queue. Position #1, estimated wait: 15 minutes.', 1, '2026-04-01 11:19:56'),
(7, 4, 'reservation', '✅ Reservation Confirmed!', 'Your reservation for 2026-04-02 at 21:40:00 has been confirmed by the admin.', 0, '2026-04-02 17:39:33'),
(8, 4, 'order', '👨‍🍳 Order Being Prepared', 'Your order #ORD-6170 is now being prepared by our kitchen staff.', 0, '2026-04-02 17:39:41'),
(9, 4, 'queue', '✅ Your Table is Ready!', 'Neymar Jr, your table is now ready. Please proceed to the host station.', 0, '2026-04-02 17:46:31'),
(10, 5, 'reservation', '✅ Reservation Confirmed!', 'Your reservation for 2026-04-02 at 22:54:00 has been confirmed by the admin.', 1, '2026-04-02 17:56:00'),
(11, 6, 'reservation', '✅ Reservation Confirmed!', 'Your reservation for 2026-04-05 at 16:12:00 has been confirmed by the admin.', 1, '2026-04-05 09:14:54'),
(12, 6, 'queue', '✅ Your Table is Ready!', 'Baibab Bista, your table is now ready. Please proceed to the host station.', 1, '2026-04-05 09:15:16'),
(13, 6, 'order', '👨‍🍳 Order Being Prepared', 'Your order #ORD-5566 is now being prepared by our kitchen staff.', 1, '2026-04-05 20:47:19'),
(14, 6, 'order', '✅ Order Ready for Pickup!', 'Your order #ORD-5566 is ready for pickup. Please come to the counter.', 1, '2026-04-05 20:47:24'),
(15, 6, 'reservation', '✅ Reservation Confirmed!', 'Your reservation for 2026-04-05 at 02:48:00 has been confirmed by the admin.', 1, '2026-04-05 20:47:57'),
(16, 6, 'queue', '✅ Your Table is Ready!', 'Baibab Bista, your table is now ready. Please proceed to the host station.', 1, '2026-04-05 20:48:55'),
(17, 7, 'reservation', '✅ Reservation Confirmed!', 'Your reservation for 2026-04-14 at 07:30:00 has been confirmed by the admin.', 1, '2026-04-14 00:27:28'),
(18, 7, 'order', '👨‍🍳 Order Being Prepared', 'Your order #ORD-5043 is now being prepared by our kitchen staff.', 1, '2026-04-14 00:31:10'),
(19, 7, 'order', '✅ Order Ready for Pickup!', 'Your order #ORD-5043 is ready for pickup. Please come to the counter.', 1, '2026-04-14 00:31:13'),
(20, 7, 'reservation', '❌ Reservation Cancelled', 'Your reservation for 2026-04-14 at 07:30:00 has been cancelled by the admin.', 1, '2026-04-14 20:17:06'),
(21, 10, 'reservation', '✅ Reservation Confirmed!', 'Your reservation for 2026-04-16 at 13:00:00 has been confirmed by the admin.', 1, '2026-04-15 21:46:27'),
(22, 10, 'reservation', '✅ Reservation Confirmed!', 'Your reservation for 2026-04-16 at 13:00:00 has been confirmed by the admin.', 1, '2026-04-15 21:46:37'),
(23, 10, 'reservation', 'Reservation Confirmed!', 'Your reservation for 2026-04-16 at 13:00:00 has been confirmed.', 1, '2026-04-15 21:53:45'),
(24, 10, 'reservation', '✅ Reservation Confirmed!', 'Your reservation for 2026-04-17 at 01:15:00 has been confirmed.', 1, '2026-04-15 22:15:19'),
(25, 10, 'reservation', '✅ Reservation Confirmed!', 'Your reservation for 2026-04-17 at 01:15:00 has been confirmed.', 1, '2026-04-15 22:19:53'),
(26, 10, 'reservation', '✅ Reservation Confirmed!', 'Your reservation for 2026-04-17 at 01:15:00 has been confirmed.', 1, '2026-04-15 22:24:39'),
(27, 10, 'reservation', '✅ Reservation Confirmed!', 'Your reservation for 2026-04-17 at 01:24:00 has been confirmed.', 1, '2026-04-15 23:21:46'),
(28, 7, 'reservation', '✅ Reservation Confirmed!', 'Your reservation for 2026-04-14 at 07:30:00 has been confirmed.', 1, '2026-04-15 23:27:42'),
(29, 10, 'reservation', '❌ Reservation Cancelled', 'Your reservation for 2026-04-24 at 06:36:00 has been cancelled.', 1, '2026-04-18 20:59:46'),
(30, 10, 'reservation', '✅ Reservation Confirmed!', 'Your reservation for 2026-04-19 at 10:01:00 has been confirmed.', 1, '2026-04-18 21:02:28'),
(31, 10, 'order', '👨‍🍳 Order Being Prepared', 'Your order #ORD-6581 is now being prepared by our kitchen staff.', 1, '2026-04-18 21:05:35'),
(32, 10, 'order', '✅ Order Ready for Pickup!', 'Your order #ORD-6581 is ready for pickup. Please come to the counter.', 1, '2026-04-18 21:05:40'),
(33, 10, 'order', '🎉 Order Completed', 'Your order #ORD-6581 has been completed. Thank you for dining with us!', 1, '2026-04-18 21:05:46'),
(34, 10, 'reservation', '✅ Reservation Confirmed!', 'Your reservation for 2026-04-18 at 00:21:00 has been confirmed.', 1, '2026-04-18 21:38:19'),
(35, 10, 'queue', '✅ Your Table is Ready!', 'Aashish Neupane, your table is now ready. Please proceed to the host station.', 1, '2026-04-18 21:38:27'),
(36, 10, 'order', '👨‍🍳 Order Being Prepared', 'Your order #ORD-4044 is now being prepared by our kitchen staff.', 1, '2026-04-18 21:41:34'),
(37, 10, 'order', '✅ Order Ready for Pickup!', 'Your order #ORD-4044 is ready for pickup. Please come to the counter.', 1, '2026-04-18 21:41:41'),
(38, 10, 'order', '🎉 Order Completed', 'Your order #ORD-4044 has been completed. Thank you for dining with us!', 1, '2026-04-18 21:41:44'),
(39, 1, 'reservation', '✅ Reservation Confirmed!', 'Your reservation for 2026-04-16 at 14:20:00 has been confirmed.', 1, '2026-04-18 21:45:58'),
(40, 10, 'order', '👨‍🍳 Order Being Prepared', 'Your order #ORD-7903 is now being prepared by our kitchen staff.', 1, '2026-04-19 12:11:36'),
(41, 10, 'order', '✅ Order Ready for Pickup!', 'Your order #ORD-7903 is ready for pickup. Please come to the counter.', 1, '2026-04-19 12:11:40'),
(42, 10, 'order', '🎉 Order Completed', 'Your order #ORD-7903 has been completed. Thank you for dining with us!', 1, '2026-04-19 12:11:47'),
(43, 11, 'general', '🎉 Welcome to Smart Café!', 'Thank you for joining us! You can now reserve tables, order food, and join the queue.', 1, '2026-04-20 21:22:09'),
(44, 10, 'reservation', '✅ Reservation Confirmed!', 'Your reservation for 2026-04-17 at 01:24:00 has been confirmed.', 1, '2026-04-21 10:08:41'),
(45, 10, 'reservation', '✅ Reservation Confirmed!', 'Your reservation for 2026-04-18 at 00:21:00 has been confirmed.', 1, '2026-04-21 10:09:18'),
(46, 10, 'reservation', '✅ Reservation Confirmed!', 'Your reservation for 2026-04-18 at 00:21:00 has been confirmed.', 1, '2026-04-21 10:09:51'),
(47, 10, 'reservation', '✅ Reservation Confirmed!', 'Your reservation for 2026-04-21 at 02:15:00 has been confirmed.', 1, '2026-04-21 19:11:21'),
(48, 10, 'queue', '✅ Your Table is Ready!', 'Aashish Neupane, your table is now ready at your reserved table Table 1. Please proceed to the host station.', 1, '2026-04-21 19:12:55'),
(49, 7, 'reservation', '❌ Reservation Cancelled', 'Your reservation for 2026-04-22 at 21:25:00 has been cancelled.', 0, '2026-04-21 19:26:26'),
(50, 12, 'reservation', '❌ Reservation Cancelled', 'Your reservation for 2026-04-22 at 15:30:00 has been cancelled.', 0, '2026-04-21 19:27:23'),
(51, 5, 'reservation', '❌ Reservation Cancelled', 'Your reservation for 2026-04-21 at 02:29:00 has been cancelled.', 0, '2026-04-21 19:27:30'),
(52, 7, 'reservation', '❌ Reservation Cancelled', 'Your reservation for 2026-04-16 at 14:20:00 has been cancelled.', 0, '2026-04-21 19:27:37'),
(53, 2, 'reservation', '❌ Reservation Cancelled', 'Your reservation for 2026-02-25 at 10:31:00 has been cancelled.', 0, '2026-04-21 19:29:36'),
(54, 7, 'reservation', '❌ Reservation Cancelled', 'Your reservation for 2026-04-22 at 21:33:00 has been cancelled.', 0, '2026-04-21 19:29:42'),
(55, 2, 'reservation', '❌ Reservation Cancelled', 'Your reservation for 2026-02-26 at 10:25:00 has been cancelled.', 0, '2026-04-21 19:29:59'),
(56, 10, 'reservation', '❌ Reservation Cancelled', 'Your reservation for 2026-04-21 at 02:31:00 has been cancelled.', 1, '2026-04-21 19:31:46'),
(57, 10, 'reservation', '✅ Reservation Confirmed!', 'Your reservation for 2026-04-22 at 13:12:00 has been confirmed.', 1, '2026-04-22 11:13:37'),
(58, 13, 'reservation', '✅ Reservation Confirmed!', 'Your reservation for 2026-04-23 at 17:19:00 has been confirmed.', 0, '2026-04-22 11:19:49'),
(59, 13, 'reservation', '✅ Reservation Confirmed!', 'Your reservation for 2026-04-23 at 17:19:00 has been confirmed.', 0, '2026-04-22 11:24:22'),
(60, 1, 'reservation', '✅ Reservation Confirmed!', 'Your reservation for 2026-04-22 at 15:25:00 has been confirmed.', 0, '2026-04-22 11:26:09'),
(61, 10, 'reservation', '✅ Reservation Confirmed!', 'Your reservation for 2026-04-22 at 18:30:00 has been confirmed.', 1, '2026-04-22 11:28:12'),
(62, 10, 'reservation', '✅ Reservation Confirmed!', 'Your reservation for 2026-04-27 at 18:00:00 has been confirmed.', 1, '2026-04-26 18:56:24'),
(63, 10, 'reservation', '✅ Reservation Confirmed!', 'Your reservation for 2026-04-26 at 21:00:00 has been confirmed.', 1, '2026-04-26 18:59:52'),
(64, 10, 'queue', '✅ Your Table is Ready!', 'Aashish Neupane, your table is now ready at your reserved table Table 2. Please proceed to the host station.', 1, '2026-04-26 18:59:59'),
(65, 10, 'order', '👨‍🍳 Order Being Prepared', 'Your order #ORD-9165 is now being prepared by our kitchen staff.', 1, '2026-04-26 19:01:42'),
(66, 10, 'order', '✅ Order Ready for Pickup!', 'Your order #ORD-9165 is ready for pickup. Please come to the counter.', 1, '2026-04-26 19:02:05'),
(67, 10, 'order', '🎉 Order Completed', 'Your order #ORD-9165 has been completed. Thank you for dining with us!', 1, '2026-04-26 19:02:19');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `reservation_id` int(11) DEFAULT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `order_type` enum('pre_order','dine_in','takeaway') DEFAULT 'pre_order',
  `status` enum('pending','preparing','ready','completed','cancelled') DEFAULT 'pending',
  `total_amount` decimal(10,2) NOT NULL,
  `payment_status` enum('pending','paid','refunded') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `special_instructions` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `order_ref` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `reservation_id`, `customer_name`, `customer_phone`, `order_type`, `status`, `total_amount`, `payment_status`, `payment_method`, `special_instructions`, `created_at`, `updated_at`, `order_ref`) VALUES
(1, 2, NULL, 'Aashish Neupane', '+1234567891', 'pre_order', 'completed', 24.98, 'paid', 'credit_card', NULL, '2026-03-27 08:40:04', '2026-03-31 21:08:08', 'ORD-001'),
(2, NULL, NULL, 'Hari Oli', '+45 66666666', 'pre_order', 'completed', 12.99, 'paid', 'cash', 'ORD-002', '2026-03-27 08:43:16', '2026-03-31 21:47:39', 'ORD-002'),
(3, NULL, NULL, 'Hari Oli', '+45 66666666', 'pre_order', 'completed', 8.99, 'paid', 'cash', 'ORD-003', '2026-03-27 09:25:32', '2026-03-31 20:58:58', 'ORD-003'),
(4, NULL, NULL, 'Neymar Jr', '+45 12345678', 'pre_order', 'completed', 9.99, 'paid', 'cash', NULL, '2026-03-31 21:23:57', '2026-03-31 21:25:39', 'ORD-004'),
(5, NULL, NULL, 'Neymar Jr', '+45 12345678', 'pre_order', 'completed', 10.99, 'paid', 'cash', NULL, '2026-03-31 21:48:21', '2026-03-31 21:50:03', 'ORD-005'),
(6, NULL, NULL, 'Prab Shiram', '+45 23456789', 'pre_order', 'completed', 4.50, 'paid', 'cash', NULL, '2026-03-31 22:37:03', '2026-03-31 22:38:08', 'ORD-006'),
(7, NULL, NULL, 'Prab Shiram', '+45 23456789', 'pre_order', 'completed', 15.99, 'paid', 'cash', NULL, '2026-03-31 22:53:12', '2026-03-31 22:55:53', 'ORD-007'),
(8, 4, NULL, 'Neymar Jr', '+45 12345678', 'pre_order', 'preparing', 5.00, 'paid', 'cash', NULL, '2026-04-02 17:38:25', '2026-04-02 17:39:41', 'ORD-6170'),
(9, 6, NULL, 'Baibab Bista', '+45 66666664', 'pre_order', 'pending', 9.99, 'paid', 'cash', NULL, '2026-04-05 09:12:43', '2026-04-05 09:12:43', 'ORD-3568'),
(10, 6, NULL, 'Baibab Bista', '+45 66666664', 'pre_order', 'ready', 11.99, 'paid', 'cash', NULL, '2026-04-05 20:45:20', '2026-04-05 20:47:24', 'ORD-5566'),
(11, 7, NULL, 'Madi Kumar', '12345678', 'pre_order', 'ready', 15.99, 'paid', 'cash', NULL, '2026-04-14 00:30:13', '2026-04-14 00:31:13', 'ORD-5043'),
(12, 1, NULL, 'Admin User', '+1234567890', 'pre_order', 'pending', 4.50, 'paid', 'cash', NULL, '2026-04-18 21:04:13', '2026-04-18 21:04:13', 'ORD-0531'),
(13, 10, NULL, 'Aashish Neupane', '+4591437762', 'pre_order', 'completed', 5.00, 'paid', 'credit_card', NULL, '2026-04-18 21:05:07', '2026-04-18 21:05:44', 'ORD-6581'),
(14, 10, NULL, 'Aashish Neupane', '+4591437762', 'pre_order', 'completed', 12.99, 'paid', 'credit_card', NULL, '2026-04-18 21:40:03', '2026-04-18 21:41:42', 'ORD-4044'),
(15, 1, NULL, 'Admin User', '+1234567890', 'pre_order', 'pending', 13.99, 'paid', 'cash', NULL, '2026-04-18 21:50:26', '2026-04-18 21:50:26', 'ORD-0416'),
(16, 10, NULL, 'Aashish Neupane', '+4591437762', 'pre_order', 'pending', 12.99, 'paid', 'credit_card', NULL, '2026-04-19 08:58:17', '2026-04-19 08:58:17', 'ORD-9454'),
(17, 10, NULL, 'Aashish Neupane', '+4591437762', 'pre_order', 'pending', 14.99, 'paid', 'credit_card', NULL, '2026-04-19 09:07:43', '2026-04-19 09:07:43', 'ORD-0307'),
(18, 10, NULL, 'Aashish Neupane', '+4591437762', 'pre_order', 'pending', 6.99, 'paid', 'credit_card', NULL, '2026-04-19 09:10:53', '2026-04-19 09:10:53', 'ORD-8925'),
(19, 10, NULL, 'Aashish Neupane', '+4591437762', 'pre_order', 'pending', 12.99, 'paid', 'credit_card', NULL, '2026-04-19 09:31:29', '2026-04-19 09:31:29', 'ORD-4987'),
(20, 10, NULL, 'Aashish Neupane', '+4591437762', 'pre_order', 'completed', 21.98, 'paid', 'credit_card', NULL, '2026-04-19 09:42:51', '2026-04-19 12:11:45', 'ORD-7903'),
(21, 10, NULL, 'Aashish Neupane', '+4591437762', 'pre_order', 'pending', 7.99, 'paid', 'credit_card', NULL, '2026-04-20 20:49:58', '2026-04-20 20:49:58', 'ORD-5210'),
(22, 10, NULL, 'Aashish Neupane', '+4591437762', 'pre_order', 'pending', 12.99, 'paid', 'credit_card', NULL, '2026-04-20 20:56:34', '2026-04-20 20:56:34', 'ORD-5091'),
(23, 10, NULL, 'Aashish Neupane', '+4591437762', 'pre_order', 'completed', 12.99, 'paid', 'credit_card', NULL, '2026-04-21 19:20:39', '2026-04-26 19:02:18', 'ORD-9165'),
(24, 1, NULL, 'Admin User', '+1234567890', 'pre_order', 'pending', 8.99, 'paid', 'cash', NULL, '2026-04-26 19:00:55', '2026-04-26 19:00:55', 'ORD-3487'),
(25, 10, NULL, 'Aashish Neupane', '+4591437762', 'pre_order', 'pending', 13.99, 'paid', 'credit_card', NULL, '2026-05-06 11:41:40', '2026-05-06 11:41:40', 'ORD-4022'),
(26, 10, NULL, 'Aashish Neupane', '+4591437762', 'pre_order', 'pending', 24.98, 'paid', 'cash', NULL, '2026-05-06 11:42:36', '2026-05-06 11:42:36', 'ORD-8181');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `item_id` int(11) DEFAULT NULL,
  `item_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `item_id`, `item_name`, `quantity`, `unit_price`, `subtotal`) VALUES
(1, 1, 1, 'Classic Pancakes', 2, 8.99, 17.98),
(2, 1, 9, 'Espresso', 2, 3.50, 7.00),
(3, 2, 2, 'Eggs Benedict', 1, 12.99, 12.99),
(4, 3, 1, 'Classic Pancakes', 1, 8.99, 8.99),
(5, 4, 3, 'French Toast', 1, 9.99, 9.99),
(6, 5, 4, 'Avocado Toast', 1, 10.99, 10.99),
(7, 6, 10, 'Cappuccino', 1, 4.50, 4.50),
(8, 7, 8, 'Grilled Chicken', 1, 15.99, 15.99),
(9, 8, 11, 'Iced Latte', 1, 5.00, 5.00),
(10, 9, 3, 'French Toast', 1, 9.99, 9.99),
(11, 10, 5, 'Caesar Salad', 1, 11.99, 11.99),
(12, 11, 8, 'Grilled Chicken', 1, 15.99, 15.99),
(13, 12, 10, 'Cappuccino', 1, 4.50, 4.50),
(14, 13, 11, 'Iced Latte', 1, 5.00, 5.00),
(15, 14, 2, 'Eggs Benedict', 1, 12.99, 12.99),
(16, 15, 6, 'Club Sandwich', 1, 13.99, 13.99),
(17, 16, 2, 'Eggs Benedict', 1, 12.99, 12.99),
(18, 17, 7, 'Beef Burger', 1, 14.99, 14.99),
(19, 18, 13, 'Chocolate Cake', 1, 6.99, 6.99),
(20, 19, 2, 'Eggs Benedict', 1, 12.99, 12.99),
(21, 20, 1, 'Classic Pancakes', 1, 8.99, 8.99),
(22, 20, 2, 'Eggs Benedict', 1, 12.99, 12.99),
(23, 21, 14, 'Tiramisu', 1, 7.99, 7.99),
(24, 22, 2, 'Eggs Benedict', 1, 12.99, 12.99),
(25, 23, 2, 'Eggs Benedict', 1, 12.99, 12.99),
(26, 24, 1, 'Classic Pancakes', 1, 8.99, 8.99),
(27, 25, 6, 'Club Sandwich', 1, 13.99, 13.99),
(28, 26, 5, 'Caesar Salad', 1, 11.99, 11.99),
(29, 26, 2, 'Eggs Benedict', 1, 12.99, 12.99);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `reservation_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('credit_card','debit_card','paypal','cash','other') NOT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `order_id`, `reservation_id`, `amount`, `payment_method`, `transaction_id`, `status`, `payment_date`) VALUES
(1, 1, NULL, 24.98, 'credit_card', 'TXN-001-2026', 'completed', '2026-03-27 08:40:04'),
(2, 2, NULL, 12.99, 'cash', 'TXN-002-20260327', 'completed', '2026-03-27 08:43:16'),
(3, 3, NULL, 8.99, 'cash', 'TXN-003-20260327', 'completed', '2026-03-27 09:25:32'),
(4, 4, NULL, 9.99, 'cash', 'TXN-004-20260331', 'completed', '2026-03-31 21:23:57'),
(5, 5, NULL, 10.99, 'cash', 'TXN-005-20260331', 'completed', '2026-03-31 21:48:21'),
(6, 6, NULL, 4.50, 'cash', 'TXN-006-20260331', 'completed', '2026-03-31 22:37:03'),
(7, 7, NULL, 15.99, 'cash', 'TXN-007-20260331', 'completed', '2026-03-31 22:53:12'),
(8, 8, NULL, 5.00, 'cash', NULL, 'completed', '2026-04-02 17:38:25'),
(9, 9, NULL, 9.99, 'cash', NULL, 'completed', '2026-04-05 09:12:43'),
(10, 10, NULL, 11.99, 'cash', NULL, 'completed', '2026-04-05 20:45:20'),
(11, 11, NULL, 15.99, 'cash', NULL, 'completed', '2026-04-14 00:30:13'),
(12, 12, NULL, 4.50, 'cash', NULL, 'completed', '2026-04-18 21:04:13'),
(13, 13, NULL, 5.00, 'credit_card', NULL, 'completed', '2026-04-18 21:05:07'),
(14, 14, NULL, 12.99, 'credit_card', NULL, 'completed', '2026-04-18 21:40:03'),
(15, 15, NULL, 13.99, 'cash', NULL, 'completed', '2026-04-18 21:50:26'),
(16, 16, NULL, 12.99, 'credit_card', NULL, 'completed', '2026-04-19 08:58:17'),
(17, 17, NULL, 14.99, 'credit_card', NULL, 'completed', '2026-04-19 09:07:43'),
(18, 18, NULL, 6.99, 'credit_card', NULL, 'completed', '2026-04-19 09:10:53'),
(19, 19, NULL, 12.99, 'credit_card', NULL, 'completed', '2026-04-19 09:31:29'),
(20, 20, NULL, 21.98, 'credit_card', NULL, 'completed', '2026-04-19 09:42:51'),
(21, 21, NULL, 7.99, 'credit_card', NULL, 'completed', '2026-04-20 20:49:58'),
(22, 22, NULL, 12.99, 'credit_card', NULL, 'completed', '2026-04-20 20:56:34'),
(23, 23, NULL, 12.99, 'credit_card', NULL, 'completed', '2026-04-21 19:20:39'),
(24, 24, NULL, 8.99, 'cash', NULL, 'completed', '2026-04-26 19:00:55'),
(25, 25, NULL, 13.99, 'credit_card', NULL, 'completed', '2026-05-06 11:41:40'),
(26, 26, NULL, 24.98, 'cash', NULL, 'completed', '2026-05-06 11:42:36');

-- --------------------------------------------------------

--
-- Stand-in structure for view `pending_orders`
-- (See below for the actual view)
--
CREATE TABLE `pending_orders` (
`order_id` int(11)
,`customer_name` varchar(255)
,`total_amount` decimal(10,2)
,`status` enum('pending','preparing','ready','completed','cancelled')
,`created_at` timestamp
,`item_count` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `queue`
--

CREATE TABLE `queue` (
  `queue_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `party_size` int(11) NOT NULL,
  `status` enum('waiting','called','seated','cancelled') DEFAULT 'waiting',
  `position` int(11) DEFAULT NULL,
  `estimated_wait_time` int(11) DEFAULT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `called_at` timestamp NULL DEFAULT NULL,
  `seated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `queue`
--

INSERT INTO `queue` (`queue_id`, `user_id`, `customer_name`, `customer_phone`, `party_size`, `status`, `position`, `estimated_wait_time`, `joined_at`, `called_at`, `seated_at`) VALUES
(1, NULL, 'Hari Oli', '+45 66666666', 2, 'seated', 1, 15, '2026-03-27 08:44:08', NULL, '2026-03-27 08:48:26'),
(2, NULL, 'Hari Oli', '+45 66666666', 3, 'seated', 1, 15, '2026-03-27 09:25:48', NULL, '2026-03-31 20:44:32'),
(3, NULL, 'Neymar Jr', '+45 12345678', 3, 'seated', 1, 15, '2026-03-31 21:24:07', NULL, '2026-04-01 11:00:36'),
(4, NULL, 'Prab Shiram', '+45 23456789', 2, 'seated', 2, 30, '2026-03-31 22:33:15', NULL, '2026-04-01 10:58:59'),
(5, NULL, 'Neymar Jr', '+45 12345678', 4, 'seated', 1, 15, '2026-04-01 11:19:56', NULL, '2026-04-01 11:20:28'),
(12, 4, 'Neymar Jr', '+45 12345678', 4, 'seated', 1, 15, '2026-04-02 17:46:00', NULL, '2026-04-02 17:46:31'),
(13, 6, 'Baibab Bista', '+45 66666664', 3, 'seated', 1, 15, '2026-04-05 09:12:55', NULL, '2026-04-05 09:15:16'),
(14, 6, 'Baibab Bista', '+45 66666664', 2, 'seated', 1, 15, '2026-04-05 20:45:54', NULL, '2026-04-05 20:48:55'),
(15, 1, 'Madi Kumar', '12345678', 6, 'waiting', 1, 15, '2026-04-14 00:32:29', NULL, NULL),
(16, 10, 'Aashish Neupane', '+4591437762', 2, 'seated', 2, 30, '2026-04-18 21:01:47', NULL, '2026-04-18 21:38:25'),
(20, 10, 'Aashish Neupane', '+4591437762', 2, 'seated', 2, 30, '2026-04-21 19:12:36', NULL, '2026-04-21 19:12:53'),
(21, 10, 'Aashish Neupane', '+4591437762', 2, 'seated', 2, 30, '2026-04-26 18:55:42', NULL, '2026-04-26 18:59:55'),
(22, 10, 'Aashish Neupane', '+4591437762', 2, 'waiting', 2, 30, '2026-05-03 21:21:43', NULL, NULL),
(23, 10, 'Aashish Neupane', '+4591437762', 3, 'waiting', 3, 45, '2026-05-03 21:21:52', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `reservation_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_email` varchar(255) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `table_id` int(11) DEFAULT NULL,
  `reservation_date` date NOT NULL,
  `reservation_time` time NOT NULL,
  `number_of_guests` int(11) NOT NULL,
  `special_requests` text DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled','completed','no_show') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`reservation_id`, `user_id`, `customer_name`, `customer_email`, `customer_phone`, `table_id`, `reservation_date`, `reservation_time`, `number_of_guests`, `special_requests`, `status`, `created_at`, `updated_at`) VALUES
(1, 2, 'Aashish Neupane', 'aashish@example.com', '+1234567891', 1, '2026-02-24', '18:00:00', 2, 'Window seat preferred', 'confirmed', '2026-03-27 08:40:04', '2026-03-27 08:40:04'),
(2, NULL, 'Sarah Johnson', 'sarah@email.com', '+1234567890', 3, '2026-02-24', '19:00:00', 4, '', 'confirmed', '2026-03-27 08:40:04', '2026-03-27 08:40:04'),
(3, NULL, 'Emily Davis', 'emily@email.com', '+1234567892', 5, '2026-02-24', '12:00:00', 3, 'Vegetarian please', 'cancelled', '2026-03-27 08:40:04', '2026-04-21 19:26:27'),
(4, NULL, 'James Wilson', 'james@email.com', '+1234567893', 2, '2026-02-25', '13:30:00', 2, '', 'confirmed', '2026-03-27 08:40:04', '2026-03-27 08:40:04'),
(5, NULL, 'Mike Chen', 'mike@email.com', '+1234567894', 7, '2026-02-25', '18:30:00', 6, 'Birthday celebration', 'confirmed', '2026-03-27 08:40:04', '2026-03-27 08:40:04'),
(6, 2, 'Aashish Neupane', 'aashish@example.com', '+1234567891', 5, '2026-02-26', '10:11:00', 2, '', 'cancelled', '2026-03-27 08:40:04', '2026-03-27 08:40:04'),
(7, 2, 'Shyam', 'aashish@example.com', '+1234567891', 2, '2026-03-06', '10:20:00', 6, '', 'cancelled', '2026-03-27 08:40:04', '2026-03-27 08:40:04'),
(8, 2, 'Ramesh', 'aashish@example.com', '+1234567891', 1, '2026-02-26', '10:25:00', 1, '', 'cancelled', '2026-03-27 08:40:04', '2026-04-21 19:29:57'),
(9, 2, 'Aashish Neupane', 'aashish@example.com', '+1234567891', 1, '2026-02-25', '10:31:00', 1, '', 'cancelled', '2026-03-27 08:40:04', '2026-04-21 19:29:34'),
(10, NULL, 'Hari Oli', 'hario@gmail.com', '+45 66666666', 1, '2026-03-27', '09:48:00', 1, '', 'confirmed', '2026-03-27 08:42:33', '2026-03-27 08:42:33'),
(11, NULL, 'Hari Oli', 'hario@gmail.com', '+45 66666666', 3, '2026-03-27', '12:30:00', 2, '', 'confirmed', '2026-03-27 09:24:07', '2026-03-27 09:24:07'),
(12, NULL, 'Neymar Jr', 'neymar@gmail.com', '+45 12345678', 3, '2026-04-01', '08:30:00', 3, '', 'confirmed', '2026-03-31 21:23:45', '2026-03-31 21:23:45'),
(13, NULL, 'Prab Shiram', 'prab@gmail.com', '+45 23456789', 1, '2026-04-02', '05:30:00', 2, '', 'confirmed', '2026-03-31 22:31:59', '2026-04-01 10:57:48'),
(14, 4, 'Neymar Jr', 'neymar@gmail.com', '+45 12345678', 5, '2026-04-01', '20:30:00', 4, '', 'confirmed', '2026-04-01 11:17:43', '2026-04-01 11:18:56'),
(15, 4, 'Neymar Jr', 'neymar@gmail.com', '+45 12345678', 3, '2026-04-02', '21:40:00', 4, '', 'completed', '2026-04-02 17:37:51', '2026-04-02 17:46:30'),
(16, 5, 'Prab Shiram', 'prab@gmail.com', '+45 23456789', 4, '2026-04-02', '22:54:00', 4, '', 'confirmed', '2026-04-02 17:55:02', '2026-04-02 17:56:00'),
(17, 6, 'Baibab Bista', 'baibab@gmail.com', '+45 66666664', 4, '2026-04-05', '16:12:00', 3, '', 'completed', '2026-04-05 09:12:31', '2026-04-05 09:15:16'),
(18, 6, 'Baibab Bista', 'baibab@gmail.com', '+45 66666664', 1, '2026-04-05', '02:48:00', 2, '', 'completed', '2026-04-05 20:44:41', '2026-04-05 20:48:55'),
(19, 7, 'Madi Kumar', 'madi@gmail.com', '12345678', 5, '2026-04-14', '07:30:00', 5, '', 'confirmed', '2026-04-14 00:26:34', '2026-04-15 23:27:40'),
(20, 10, 'Aashish Neupane', 'neu.ash56@gmail.com', '+4591437762', 3, '2026-04-16', '13:00:00', 4, '', 'confirmed', '2026-04-15 21:45:39', '2026-04-15 21:53:45'),
(21, 10, 'Aashish Neupane', 'neu.ash56@gmail.com', '+4591437762', 2, '2026-04-17', '01:15:00', 2, '', 'confirmed', '2026-04-15 22:14:34', '2026-04-15 22:24:37'),
(22, 10, 'Aashish Neupane', 'neu.ash56@gmail.com', '+4591437762', 4, '2026-04-24', '06:36:00', 4, '', 'cancelled', '2026-04-15 22:36:44', '2026-04-18 20:59:44'),
(23, 10, 'Aashish Neupane', 'neu.ash56@gmail.com', '+4591437762', 3, '2026-04-17', '01:24:00', 3, '', 'confirmed', '2026-04-15 23:21:02', '2026-04-21 10:08:39'),
(24, 7, 'Madi Kumar', 'madi@gmail.com', '12345678', 3, '2026-04-16', '14:20:00', 4, '', 'cancelled', '2026-04-16 08:15:34', '2026-04-21 19:27:35'),
(25, 1, 'Madi Kumar', 'madi@gmail.com', '12345678', 3, '2026-04-16', '14:20:00', 4, '', 'confirmed', '2026-04-16 08:16:57', '2026-04-18 21:45:56'),
(26, NULL, 'Pukar Neupane', 'puk@gmail.com', '+45 12345677', 2, '2026-04-19', '16:00:00', 2, '', 'confirmed', '2026-04-18 20:57:14', '2026-04-18 20:58:05'),
(27, 10, 'Aashish Neupane', 'neu.ash56@gmail.com', '+4591437762', 1, '2026-04-19', '10:01:00', 2, '', 'confirmed', '2026-04-18 21:01:36', '2026-04-18 21:02:26'),
(28, 10, 'Aashish Neupane', 'neu.ash56@gmail.com', '+4591437762', 2, '2026-04-18', '00:21:00', 2, '', 'confirmed', '2026-04-18 21:20:19', '2026-04-21 10:09:49'),
(29, 10, 'Aashish Neupane', 'neu.ash56@gmail.com', '+4591437762', 1, '2026-04-21', '02:15:00', 2, '', 'completed', '2026-04-21 19:10:40', '2026-04-21 19:12:55'),
(30, 12, 'San Watson', 'san@gmail.com', '+4591332263', 7, '2026-04-22', '15:30:00', 3, '', 'cancelled', '2026-04-21 19:21:55', '2026-04-21 19:27:21'),
(31, 5, 'Prab Shiram', 'prab@gmail.com', '+45 23456789', 4, '2026-04-21', '02:29:00', 3, '', 'cancelled', '2026-04-21 19:24:13', '2026-04-21 19:27:28'),
(32, 7, 'Madi Kumar', 'madi@gmail.com', '12345678', 8, '2026-04-22', '21:25:00', 4, '', 'cancelled', '2026-04-21 19:25:43', '2026-04-21 19:26:23'),
(33, 7, 'Madi Kumar', 'madi@gmail.com', '12345678', 3, '2026-04-22', '21:33:00', 3, '', 'cancelled', '2026-04-21 19:28:52', '2026-04-21 19:29:39'),
(34, 10, 'Aashish Neupane', 'neu.ash56@gmail.com', '+4591437762', 5, '2026-04-21', '02:31:00', 5, '', 'cancelled', '2026-04-21 19:31:11', '2026-04-21 19:31:44'),
(35, 10, 'Aashish Neupane', 'neu.ash56@gmail.com', '+4591437762', 3, '2026-04-22', '13:12:00', 3, '', 'confirmed', '2026-04-22 11:12:50', '2026-04-22 11:13:37'),
(36, 13, 'Punam william', 'magarpunpunam@gmail.com', '+4566666666', 1, '2026-04-23', '17:19:00', 2, '', 'confirmed', '2026-04-22 11:19:23', '2026-04-22 11:24:21'),
(37, 1, 'Punam william', 'magarpunpunam@gmail.com', '+4566666666', 2, '2026-04-22', '15:25:00', 2, '', 'confirmed', '2026-04-22 11:25:52', '2026-04-22 11:26:07'),
(38, 10, 'Aashish Neupane', 'neu.ash56@gmail.com', '+4591437762', 4, '2026-04-22', '18:30:00', 4, '', 'confirmed', '2026-04-22 11:27:38', '2026-04-22 11:28:09'),
(39, 10, 'Aashish Neupane', 'neu.ash56@gmail.com', '+4591437762', 1, '2026-04-27', '18:00:00', 2, 'Quite Environment', 'confirmed', '2026-04-26 18:55:23', '2026-04-26 18:56:21'),
(40, 10, 'Aashish Neupane', 'neu.ash56@gmail.com', '+4591437762', 2, '2026-04-26', '21:00:00', 2, '', 'completed', '2026-04-26 18:59:24', '2026-04-26 18:59:59');

--
-- Triggers `reservations`
--
DELIMITER $$
CREATE TRIGGER `after_reservation_cancel` AFTER UPDATE ON `reservations` FOR EACH ROW BEGIN
    IF NEW.status = 'cancelled' AND OLD.status != 'cancelled' THEN
        UPDATE cafe_tables SET status = 'available'
        WHERE table_id = NEW.table_id;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_reservation_insert` AFTER INSERT ON `reservations` FOR EACH ROW BEGIN
    INSERT INTO activity_logs (user_id, action_type, table_name, record_id, description)
    VALUES (NEW.user_id, 'CREATE', 'reservations', NEW.reservation_id,
            CONCAT('New reservation for ', NEW.customer_name, ' on ', NEW.reservation_date));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `todays_reservations`
-- (See below for the actual view)
--
CREATE TABLE `todays_reservations` (
`reservation_id` int(11)
,`user_id` int(11)
,`customer_name` varchar(255)
,`customer_email` varchar(255)
,`customer_phone` varchar(20)
,`table_id` int(11)
,`reservation_date` date
,`reservation_time` time
,`number_of_guests` int(11)
,`special_requests` text
,`status` enum('pending','confirmed','cancelled','completed','no_show')
,`created_at` timestamp
,`updated_at` timestamp
,`table_number` varchar(50)
,`user_name` varchar(255)
);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `user_type` enum('customer','admin') DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `email`, `password_hash`, `full_name`, `phone`, `user_type`, `created_at`, `last_login`, `is_active`) VALUES
(1, 'admin@smartcafe.com', '$2y$10$Ow6OFGM3To7ye4A0aUXJpe2bP6QJpyIKRNmKHFJcx53myt82/uW52', 'Admin User', '+1234567890', 'admin', '2026-03-27 08:40:04', '2026-05-03 16:42:02', 1),
(2, 'aashish@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Aashish Neupane', '+1234567891', 'customer', '2026-03-27 08:40:04', NULL, 1),
(3, 'hario@gmail.com', '$2y$10$8jErS2sX9KDAfZxkRsWykuWSdIBiurKzDpryZjYaN/P5SI9FipEnC', 'Hari Oli', '+45 66666666', 'customer', '2026-03-27 08:41:55', '2026-03-27 09:28:19', 1),
(4, 'neymar@gmail.com', '$2y$10$zMs.IFayfvnOeISK01awI.wiGkMJcnnR1xlEHQ9XdqdPrPRwenfaa', 'Neymar Jr', '+45 12345678', 'customer', '2026-03-31 21:22:55', '2026-04-02 17:40:22', 1),
(5, 'prab@gmail.com', '$2y$10$wlKKzF4pHUP9FynjTi5N6upVuCCy6zXRe.JuDJKCuAA70bOKVjovC', 'Prab Shiram', '+45 23456789', 'customer', '2026-03-31 22:31:29', '2026-04-21 19:22:57', 1),
(6, 'baibab@gmail.com', '$2y$10$eZCydBCTsDAaypBvhhGbj.RQPWPeGhhwqpGcParpIAQdDosq3amrG', 'Baibab Bista', '+45 66666664', 'customer', '2026-04-05 09:11:42', '2026-04-14 12:47:24', 1),
(7, 'madi@gmail.com', '$2y$10$1G5UdpNFh0/PrF5NlBK8nOn19C4ZubEvJN6VWaBGgR33g1vnK5Y.m', 'Madi Kumar', '12345678', 'customer', '2026-04-14 00:25:25', '2026-04-21 19:28:07', 1),
(8, 'b@gmail.com', '$2y$10$xuHX7eosaGxiqGBGfYxSQe0okZVfjr93.VdK6cpQRkVs9n9kFnE2.', 'hhhhh bbbbb', 'hhhhhhh', 'customer', '2026-04-14 20:00:45', NULL, 1),
(9, 'gautam@gmail.com', '$2y$10$P6dbiiyFJsR/GBCSilKAfOpysuNPWHDSBfjX830ptV0cxJOanqQGG', 'Gautam Regmi', '91437762', 'customer', '2026-04-14 20:10:08', NULL, 1),
(10, 'neu.ash56@gmail.com', '$2y$10$KUIu2NJlomy75TZSuxAW4OHF4AdwAABpR83Df0CgkkxjoADKVuDL2', 'Aashish Neupane', '+4591437762', 'customer', '2026-04-15 21:44:26', '2026-05-06 11:41:21', 1),
(11, 'bladin397@gmail.com', '$2y$10$T7Ggf/wys9GjisDbe6U1juQWOhKokdPQIw/3rdTnbsZcEyiP5i3pG', 'Bin Ladin', '+4522334455', 'customer', '2026-04-20 21:22:07', '2026-04-20 21:30:06', 1),
(12, 'san@gmail.com', '$2y$10$PyXV6CxdX6nydM3ruaLyp.R8ecchtD5l.UzeOfM/3OYwabBFSlXNi', 'San Watson', '+4591332263', 'customer', '2026-04-21 19:00:54', '2026-04-21 19:20:54', 1),
(13, 'magarpunpunam@gmail.com', '$2y$10$pPkcL50hfO//2HoxlBWnaOIzg04wFdhK80IZTBdspjeq6HARrvTc.', 'Punam william', '+4566666666', 'customer', '2026-04-22 11:18:45', NULL, 1);

-- --------------------------------------------------------

--
-- Structure for view `available_tables`
--
DROP TABLE IF EXISTS `available_tables`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `available_tables`  AS SELECT `cafe_tables`.`table_id` AS `table_id`, `cafe_tables`.`table_number` AS `table_number`, `cafe_tables`.`capacity` AS `capacity` FROM `cafe_tables` WHERE `cafe_tables`.`status` = 'available' ;

-- --------------------------------------------------------

--
-- Structure for view `current_queue`
--
DROP TABLE IF EXISTS `current_queue`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `current_queue`  AS SELECT `queue`.`queue_id` AS `queue_id`, `queue`.`customer_name` AS `customer_name`, `queue`.`customer_phone` AS `customer_phone`, `queue`.`party_size` AS `party_size`, `queue`.`position` AS `position`, `queue`.`estimated_wait_time` AS `estimated_wait_time`, `queue`.`joined_at` AS `joined_at` FROM `queue` WHERE `queue`.`status` = 'waiting' ORDER BY `queue`.`position` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `pending_orders`
--
DROP TABLE IF EXISTS `pending_orders`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `pending_orders`  AS SELECT `o`.`order_id` AS `order_id`, `o`.`customer_name` AS `customer_name`, `o`.`total_amount` AS `total_amount`, `o`.`status` AS `status`, `o`.`created_at` AS `created_at`, count(`oi`.`order_item_id`) AS `item_count` FROM (`orders` `o` left join `order_items` `oi` on(`o`.`order_id` = `oi`.`order_id`)) WHERE `o`.`status` in ('pending','preparing') GROUP BY `o`.`order_id` ORDER BY `o`.`created_at` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `todays_reservations`
--
DROP TABLE IF EXISTS `todays_reservations`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `todays_reservations`  AS SELECT `r`.`reservation_id` AS `reservation_id`, `r`.`user_id` AS `user_id`, `r`.`customer_name` AS `customer_name`, `r`.`customer_email` AS `customer_email`, `r`.`customer_phone` AS `customer_phone`, `r`.`table_id` AS `table_id`, `r`.`reservation_date` AS `reservation_date`, `r`.`reservation_time` AS `reservation_time`, `r`.`number_of_guests` AS `number_of_guests`, `r`.`special_requests` AS `special_requests`, `r`.`status` AS `status`, `r`.`created_at` AS `created_at`, `r`.`updated_at` AS `updated_at`, `t`.`table_number` AS `table_number`, `u`.`full_name` AS `user_name` FROM ((`reservations` `r` left join `cafe_tables` `t` on(`r`.`table_id` = `t`.`table_id`)) left join `users` `u` on(`r`.`user_id` = `u`.`user_id`)) WHERE `r`.`reservation_date` = curdate() ORDER BY `r`.`reservation_time` ASC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `cafe_tables`
--
ALTER TABLE `cafe_tables`
  ADD PRIMARY KEY (`table_id`),
  ADD KEY `idx_tables_status` (`status`);

--
-- Indexes for table `menu_categories`
--
ALTER TABLE `menu_categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `idx_menu_items_available` (`is_available`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `reservation_id` (`reservation_id`),
  ADD KEY `idx_orders_status` (`status`),
  ADD KEY `idx_order_ref` (`order_ref`),
  ADD KEY `idx_order_status` (`status`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `reservation_id` (`reservation_id`);

--
-- Indexes for table `queue`
--
ALTER TABLE `queue`
  ADD PRIMARY KEY (`queue_id`),
  ADD KEY `idx_queue_status` (`status`),
  ADD KEY `idx_queue_user_id` (`user_id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`reservation_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `table_id` (`table_id`),
  ADD KEY `idx_reservations_date` (`reservation_date`),
  ADD KEY `idx_reservations_status` (`status`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=197;

--
-- AUTO_INCREMENT for table `cafe_tables`
--
ALTER TABLE `cafe_tables`
  MODIFY `table_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `menu_categories`
--
ALTER TABLE `menu_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `queue`
--
ALTER TABLE `queue`
  MODIFY `queue_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `reservation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD CONSTRAINT `menu_items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `menu_categories` (`category_id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`reservation_id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `menu_items` (`item_id`) ON DELETE SET NULL;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`reservation_id`) ON DELETE SET NULL;

--
-- Constraints for table `queue`
--
ALTER TABLE `queue`
  ADD CONSTRAINT `fk_queue_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`table_id`) REFERENCES `cafe_tables` (`table_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
