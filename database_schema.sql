-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 02, 2026 at 07:57 PM
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
(63, 5, 'CREATE', 'reservations', 16, 'Reservation request created for Prab Shiram on 2026-04-02 at 22:54 (pending approval)', '::1', '2026-04-02 17:55:02');

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
(1, 'Table 1', 2, 'occupied', '2026-03-27 08:40:04', '2026-04-01 11:20:28'),
(2, 'Table 2', 2, 'available', '2026-03-27 08:40:04', '2026-04-01 11:15:55'),
(3, 'Table 3', 4, 'occupied', '2026-03-27 08:40:04', '2026-04-02 17:46:31'),
(4, 'Table 4', 4, 'reserved', '2026-03-27 08:40:04', '2026-04-02 17:55:02'),
(5, 'Table 5', 6, 'available', '2026-03-27 08:40:04', '2026-04-02 17:54:28'),
(6, 'Table 6', 6, 'occupied', '2026-03-27 08:40:04', '2026-03-27 08:40:04'),
(7, 'Table 7', 8, 'available', '2026-03-27 08:40:04', '2026-03-27 08:40:04'),
(8, 'Table 8', 4, 'available', '2026-03-27 08:40:04', '2026-03-27 08:40:04');

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
(10, 5, 'reservation', '✅ Reservation Confirmed!', 'Your reservation for 2026-04-02 at 22:54:00 has been confirmed by the admin.', 0, '2026-04-02 17:56:00');

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
(8, 4, NULL, 'Neymar Jr', '+45 12345678', 'pre_order', 'preparing', 5.00, 'paid', 'cash', NULL, '2026-04-02 17:38:25', '2026-04-02 17:39:41', 'ORD-6170');

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
(9, 8, 11, 'Iced Latte', 1, 5.00, 5.00);

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
(8, 8, NULL, 5.00, 'cash', NULL, 'completed', '2026-04-02 17:38:25');

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
(12, 4, 'Neymar Jr', '+45 12345678', 4, 'seated', 1, 15, '2026-04-02 17:46:00', NULL, '2026-04-02 17:46:31');

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
(3, NULL, 'Emily Davis', 'emily@email.com', '+1234567892', 5, '2026-02-24', '12:00:00', 3, 'Vegetarian please', 'pending', '2026-03-27 08:40:04', '2026-03-27 08:40:04'),
(4, NULL, 'James Wilson', 'james@email.com', '+1234567893', 2, '2026-02-25', '13:30:00', 2, '', 'confirmed', '2026-03-27 08:40:04', '2026-03-27 08:40:04'),
(5, NULL, 'Mike Chen', 'mike@email.com', '+1234567894', 7, '2026-02-25', '18:30:00', 6, 'Birthday celebration', 'confirmed', '2026-03-27 08:40:04', '2026-03-27 08:40:04'),
(6, 2, 'Aashish Neupane', 'aashish@example.com', '+1234567891', 5, '2026-02-26', '10:11:00', 2, '', 'cancelled', '2026-03-27 08:40:04', '2026-03-27 08:40:04'),
(7, 2, 'Shyam', 'aashish@example.com', '+1234567891', 2, '2026-03-06', '10:20:00', 6, '', 'cancelled', '2026-03-27 08:40:04', '2026-03-27 08:40:04'),
(8, 2, 'Ramesh', 'aashish@example.com', '+1234567891', 1, '2026-02-26', '10:25:00', 1, '', 'pending', '2026-03-27 08:40:04', '2026-03-27 08:40:04'),
(9, 2, 'Aashish Neupane', 'aashish@example.com', '+1234567891', 1, '2026-02-25', '10:31:00', 1, '', 'pending', '2026-03-27 08:40:04', '2026-03-27 08:40:04'),
(10, NULL, 'Hari Oli', 'hario@gmail.com', '+45 66666666', 1, '2026-03-27', '09:48:00', 1, '', 'confirmed', '2026-03-27 08:42:33', '2026-03-27 08:42:33'),
(11, NULL, 'Hari Oli', 'hario@gmail.com', '+45 66666666', 3, '2026-03-27', '12:30:00', 2, '', 'confirmed', '2026-03-27 09:24:07', '2026-03-27 09:24:07'),
(12, NULL, 'Neymar Jr', 'neymar@gmail.com', '+45 12345678', 3, '2026-04-01', '08:30:00', 3, '', 'confirmed', '2026-03-31 21:23:45', '2026-03-31 21:23:45'),
(13, NULL, 'Prab Shiram', 'prab@gmail.com', '+45 23456789', 1, '2026-04-02', '05:30:00', 2, '', 'confirmed', '2026-03-31 22:31:59', '2026-04-01 10:57:48'),
(14, 4, 'Neymar Jr', 'neymar@gmail.com', '+45 12345678', 5, '2026-04-01', '20:30:00', 4, '', 'confirmed', '2026-04-01 11:17:43', '2026-04-01 11:18:56'),
(15, 4, 'Neymar Jr', 'neymar@gmail.com', '+45 12345678', 3, '2026-04-02', '21:40:00', 4, '', 'completed', '2026-04-02 17:37:51', '2026-04-02 17:46:30'),
(16, 5, 'Prab Shiram', 'prab@gmail.com', '+45 23456789', 4, '2026-04-02', '22:54:00', 4, '', 'confirmed', '2026-04-02 17:55:02', '2026-04-02 17:56:00');

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
(1, 'admin@smartcafe.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', '+1234567890', 'admin', '2026-03-27 08:40:04', NULL, 1),
(2, 'aashish@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Aashish Neupane', '+1234567891', 'customer', '2026-03-27 08:40:04', NULL, 1),
(3, 'hario@gmail.com', '$2y$10$8jErS2sX9KDAfZxkRsWykuWSdIBiurKzDpryZjYaN/P5SI9FipEnC', 'Hari Oli', '+45 66666666', 'customer', '2026-03-27 08:41:55', '2026-03-27 09:28:19', 1),
(4, 'neymar@gmail.com', '$2y$10$zMs.IFayfvnOeISK01awI.wiGkMJcnnR1xlEHQ9XdqdPrPRwenfaa', 'Neymar Jr', '+45 12345678', 'customer', '2026-03-31 21:22:55', '2026-04-02 17:40:22', 1),
(5, 'prab@gmail.com', '$2y$10$wlKKzF4pHUP9FynjTi5N6upVuCCy6zXRe.JuDJKCuAA70bOKVjovC', 'Prab Shiram', '+45 23456789', 'customer', '2026-03-31 22:31:29', '2026-04-02 17:56:14', 1);

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
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

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
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `queue`
--
ALTER TABLE `queue`
  MODIFY `queue_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `reservation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
