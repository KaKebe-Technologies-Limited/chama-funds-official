-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jul 09, 2026 at 01:33 PM
-- Server version: 11.8.8-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u850523537_ChamaFunds`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `log_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `target_type` varchar(50) DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `target_name` varchar(255) DEFAULT NULL,
  `changes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`changes`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_logs`
--

INSERT INTO `admin_logs` (`log_id`, `admin_id`, `action`, `target_type`, `target_id`, `target_name`, `changes`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'Approved Withdrawal', 'withdrawal', 1, 'Family Medical Fund - UGX 500,000', '{\"status\": \"pending\", \"new_status\": \"approved\"}', '192.168.1.100', 'Chrome/120.0.0.0', '2026-06-23 14:26:32'),
(2, 1, 'Approved Withdrawal', 'withdrawal', 2, 'Family Medical Fund - UGX 250,000', '{\"status\": \"pending\", \"new_status\": \"approved\"}', '192.168.1.100', 'Chrome/120.0.0.0', '2026-06-23 14:26:32'),
(3, 1, 'Logged In', 'user', 1, 'Admin Kakebe', '{\"ip\": \"192.168.1.100\"}', '192.168.1.100', 'Chrome/120.0.0.0', '2026-06-23 14:26:32'),
(4, 1, 'Featured Campaign', 'campaign', 1, 'Family Medical Fund for Baby Grace', '{\"is_featured\": \"false\", \"new_is_featured\": \"true\"}', '192.168.1.100', 'Chrome/120.0.0.0', '2026-06-23 14:26:32');

-- --------------------------------------------------------

--
-- Table structure for table `admin_notifications`
--

CREATE TABLE `admin_notifications` (
  `notif_id` int(11) NOT NULL,
  `type` enum('new_campaign','new_donation','new_user','withdrawal','flag','system') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT '',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_notifications`
--

INSERT INTO `admin_notifications` (`notif_id`, `type`, `title`, `message`, `link`, `is_read`, `created_at`) VALUES
(1, 'new_campaign', 'New Campaign: End Period Poverty for Vulnerable Girls in Abim District, Karamoja, Uganda', 'Obin Ivan created \"End Period Poverty for Vulnerable Girls in Abim District, Karamoja, Uganda\"', '/admin/index.php?tab=campaigns&view=8', 0, '2026-07-04 12:33:28'),
(2, 'new_campaign', 'New Campaign: fun raising for Alupu\'s tuition', 'Elizabeth Akello created \"fun raising for Alupu\'s tuition\"', '/admin/index.php?tab=campaigns&view=9', 0, '2026-07-04 14:59:17'),
(3, 'new_campaign', 'New Campaign: End Period Poverty for Vulnerable Girls in Abim District, Karamoja, Uganda', 'Obin Ivan created \"End Period Poverty for Vulnerable Girls in Abim District, Karamoja, Uganda\"', '/admin/index.php?tab=campaigns&view=10', 0, '2026-07-04 15:08:14'),
(4, 'new_campaign', 'New Campaign: Surgery', 'Jerome  Oscar created \"Surgery\"', '/admin/index.php?tab=campaigns&view=11', 0, '2026-07-04 19:22:21'),
(5, 'new_campaign', 'New Campaign: Birthday Celebration with Vulnerable children', 'Ajwer Norman created \"Birthday Celebration with Vulnerable children\"', '/admin/index.php?tab=campaigns&view=12', 0, '2026-07-07 07:15:42'),
(6, 'new_campaign', 'New Campaign: Collections for Sedrick Otolo', 'Sedrick Otolo created \"Collections for Sedrick Otolo\"', '/admin/index.php?tab=campaigns&view=13', 0, '2026-07-08 09:02:39'),
(7, 'new_campaign', 'New Campaign: Celebrating birthday with the vulnerable children', 'Ajwer Norman created \"Celebrating birthday with the vulnerable children\"', '/admin/index.php?tab=campaigns&view=14', 0, '2026-07-08 09:12:08'),
(8, 'new_campaign', 'New Campaign: Help Surgery Get Account', 'Sedrick Otolo created \"Help Surgery Get Account\"', '/admin/index.php?tab=campaigns&view=15', 0, '2026-07-09 09:22:55');

-- --------------------------------------------------------

--
-- Table structure for table `campaigns`
--

CREATE TABLE `campaigns` (
  `campaign_id` int(11) NOT NULL,
  `campaigner_id` int(11) NOT NULL,
  `display_name` varchar(120) DEFAULT NULL,
  `title` varchar(150) NOT NULL,
  `slug` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `category` varchar(50) NOT NULL,
  `goal_amount` decimal(15,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'UGX',
  `raised_amount` decimal(15,2) DEFAULT 0.00,
  `contributor_count` int(11) DEFAULT 0,
  `image_url` varchar(500) DEFAULT NULL,
  `mobile_money_number` varchar(20) NOT NULL,
  `mobile_money_network` varchar(50) NOT NULL,
  `status` enum('draft','active','paused','suspended','completed','flagged') DEFAULT 'draft',
  `start_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `end_date` timestamp NULL DEFAULT NULL,
  `country` varchar(50) NOT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `view_count` int(11) DEFAULT 0,
  `share_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `multiple_images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`multiple_images`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `campaigns`
--

INSERT INTO `campaigns` (`campaign_id`, `campaigner_id`, `display_name`, `title`, `slug`, `description`, `category`, `goal_amount`, `currency`, `raised_amount`, `contributor_count`, `image_url`, `mobile_money_number`, `mobile_money_network`, `status`, `start_date`, `end_date`, `country`, `is_featured`, `view_count`, `share_count`, `created_at`, `updated_at`, `multiple_images`) VALUES
(14, 11, NULL, 'Celebrating birthday with the vulnerable children', 'celebrating-birthday-with-the-vulnerable-children-b8d82', 'On 24th July 2026, I have chosen to celebrate with the beautiful children at Ngetta Babies Home. Many of these children have no parents to hold them, no relatives they know, and no family to celebrate life’s special moments with them.\r\n\r\nFor just one day, I want to be the parent they can smile with, the relative they can lean on, and the friend who reminds them that they are loved, valued, and never forgotten.\r\n\r\nInstead of buying me birthday gifts, I humbly ask you to bless these children instead. Your contribution, no matter how small, can make a real difference. You can donate items such as:\r\n\r\n* Sugar\r\n* Soap\r\n* Rice\r\n* Posho (maize flour)\r\n* Beans\r\n* Cooking oil\r\n* Milk\r\n* Clothes\r\n* Shoes\r\n* Diapers\r\n* Toiletries\r\n* School supplies\r\n* Or any other item you feel would bring comfort and joy to these children.\r\n\r\nTogether, let’s make this birthday a celebration of hope, kindness, and love. Let us remind these children that they are part of a family that cares.\r\n\r\nThe greatest gift you can give me this year is seeing a smile on a child’s face.\r\n\r\nThank you for standing with me and for helping make this birthday truly meaningful.', 'Community', 1500000.00, 'UGX', 330000.00, 19, '/uploads/campaigns/camp_11_1783501926_0.png', '0760167722', 'MTN Mobile Money', 'active', '2026-07-08 09:12:06', '2026-07-23 00:00:00', 'Uganda', 0, 175, 3, '2026-07-08 09:12:06', '2026-07-09 13:28:34', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `campaign_categories`
--

CREATE TABLE `campaign_categories` (
  `category_id` int(11) NOT NULL,
  `name` varchar(80) NOT NULL,
  `slug` varchar(80) NOT NULL,
  `icon` varchar(10) DEFAULT '­ƒôî',
  `color_class` varchar(30) DEFAULT 'badge-other',
  `description` varchar(255) DEFAULT '',
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `campaign_categories`
--

INSERT INTO `campaign_categories` (`category_id`, `name`, `slug`, `icon`, `color_class`, `description`, `is_active`, `sort_order`, `created_at`) VALUES
(1, 'Medical', 'medical', '­ƒÅÑ', 'badge-medical', '', 1, 1, '2026-06-23 19:02:57'),
(2, 'Education', 'education', '­ƒôÜ', 'badge-education', '', 1, 2, '2026-06-23 19:02:57'),
(3, 'Community', 'community', '­ƒÆº', 'badge-community', '', 1, 3, '2026-06-23 19:02:57'),
(4, 'Family', 'family', '­ƒæ¿ÔÇì­ƒæ', 'badge-family', '', 1, 4, '2026-06-23 19:02:57'),
(5, 'Business', 'business', '­ƒÆ╝', 'badge-business', '', 1, 5, '2026-06-23 19:02:57'),
(6, 'Emergency', 'emergency', '­ƒåÿ', 'badge-emergency', '', 1, 6, '2026-06-23 19:02:57'),
(7, 'Marriage', 'marriage', '­ƒÆì', 'badge-family', '', 1, 7, '2026-06-23 19:02:57'),
(8, 'Funeral', 'funeral', '­ƒòè´©Å', 'badge-other', '', 1, 8, '2026-06-23 19:02:57'),
(9, 'Agriculture', 'agriculture', '­ƒî¥', 'badge-community', '', 1, 9, '2026-06-23 19:02:57'),
(10, 'Religion', 'religion', 'Ôø¬', 'badge-other', '', 1, 10, '2026-06-23 19:02:57'),
(11, 'Sports', 'sports', 'ÔÜ¢', 'badge-other', '', 1, 11, '2026-06-23 19:02:57'),
(12, 'Other', 'other', '­ƒôî', 'badge-other', '', 1, 12, '2026-06-23 19:02:57');

-- --------------------------------------------------------

--
-- Table structure for table `campaign_images`
--

CREATE TABLE `campaign_images` (
  `image_id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `image_url` varchar(500) NOT NULL,
  `is_cover` tinyint(1) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0,
  `caption` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `campaign_images`
--

INSERT INTO `campaign_images` (`image_id`, `campaign_id`, `image_url`, `is_cover`, `sort_order`, `caption`, `created_at`) VALUES
(1, 1, 'https://images.unsplash.com/photo-1582750433449-648ed127bb54?w=800', 1, 0, NULL, '2026-06-23 23:23:25'),
(2, 2, 'https://images.unsplash.com/photo-1548839140-29a749e1cf4d?w=800', 1, 0, NULL, '2026-06-23 23:23:25'),
(3, 3, 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=800', 1, 0, NULL, '2026-06-23 23:23:25'),
(4, 4, 'https://images.unsplash.com/photo-1534274988757-a28bf1a57c17?w=800', 1, 0, NULL, '2026-06-23 23:23:25'),
(5, 5, '/uploads/campaigns/camp_6a3ac78952e22.jpg', 1, 0, NULL, '2026-06-23 23:23:25'),
(6, 6, '/uploads/campaigns/camp_6_1782240285.png', 1, 0, NULL, '2026-06-23 23:23:25'),
(11, 8, '/uploads/campaigns/camp_7_1783168407_0.webp', 1, 0, NULL, '2026-07-04 12:33:27'),
(12, 8, '/uploads/campaigns/camp_7_1783168407_1.webp', 0, 1, NULL, '2026-07-04 12:33:27'),
(13, 8, '/uploads/campaigns/camp_7_1783168407_2.webp', 0, 2, NULL, '2026-07-04 12:33:27'),
(14, 8, '/uploads/campaigns/camp_7_1783168407_3.webp', 0, 3, NULL, '2026-07-04 12:33:27'),
(15, 8, '/uploads/campaigns/camp_7_1783168407_4.webp', 0, 4, NULL, '2026-07-04 12:33:27'),
(16, 8, '/uploads/campaigns/camp_7_1783168407_5.webp', 0, 5, NULL, '2026-07-04 12:33:27'),
(17, 9, '/uploads/campaigns/camp_8_1783177155_0.jpg', 1, 0, NULL, '2026-07-04 14:59:15'),
(18, 10, '/uploads/campaigns/camp_7_1783177692_0.webp', 1, 0, NULL, '2026-07-04 15:08:12'),
(19, 10, '/uploads/campaigns/camp_7_1783177692_1.webp', 0, 1, NULL, '2026-07-04 15:08:12'),
(20, 10, '/uploads/campaigns/camp_7_1783177692_2.webp', 0, 2, NULL, '2026-07-04 15:08:12'),
(21, 10, '/uploads/campaigns/camp_7_1783177692_3.webp', 0, 3, NULL, '2026-07-04 15:08:12'),
(22, 10, '/uploads/campaigns/camp_7_1783177692_4.webp', 0, 4, NULL, '2026-07-04 15:08:12'),
(23, 10, '/uploads/campaigns/camp_7_1783177692_5.webp', 0, 5, NULL, '2026-07-04 15:08:12'),
(24, 11, '/uploads/campaigns/camp_9_1783192940_0.jpg', 1, 0, NULL, '2026-07-04 19:22:20'),
(25, 12, '/uploads/campaigns/camp_10_1783408539_0.png', 1, 0, NULL, '2026-07-07 07:15:39'),
(26, 13, '/uploads/campaigns/camp_6_1783501356_0.jpg', 1, 0, NULL, '2026-07-08 09:02:36'),
(27, 14, '/uploads/campaigns/camp_11_1783501926_0.png', 1, 0, NULL, '2026-07-08 09:12:06'),
(28, 15, '/uploads/campaigns/camp_6_1783588973_0.jpg', 1, 0, NULL, '2026-07-09 09:22:53');

-- --------------------------------------------------------

--
-- Table structure for table `countries`
--

CREATE TABLE `countries` (
  `country_id` int(11) NOT NULL,
  `country_name` varchar(100) NOT NULL,
  `country_code` varchar(2) NOT NULL,
  `currency_code` varchar(3) NOT NULL,
  `currency_symbol` varchar(5) NOT NULL,
  `payment_partner` varchar(50) DEFAULT NULL,
  `api_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`api_config`)),
  `is_active` tinyint(1) DEFAULT 1,
  `campaign_count` int(11) DEFAULT 0,
  `user_count` int(11) DEFAULT 0,
  `fee_percentage` decimal(5,2) DEFAULT 7.50,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `countries`
--

INSERT INTO `countries` (`country_id`, `country_name`, `country_code`, `currency_code`, `currency_symbol`, `payment_partner`, `api_config`, `is_active`, `campaign_count`, `user_count`, `fee_percentage`, `created_at`) VALUES
(1, 'Uganda', 'UG', 'UGX', 'UGX', 'PawaPay', NULL, 1, 2, 3, 7.50, '2026-06-23 14:26:32'),
(2, 'Kenya', 'KE', 'KES', 'KSh', 'PawaPay', NULL, 1, 2, 1, 7.50, '2026-06-23 14:26:32'),
(3, 'Rwanda', 'RW', 'RWF', 'RWF', 'PawaPay', NULL, 1, 0, 0, 7.50, '2026-06-23 14:26:32'),
(4, 'Nigeria', 'NG', 'NGN', '₦', 'PawaPay', NULL, 1, 0, 0, 7.50, '2026-06-23 14:26:32');

-- --------------------------------------------------------

--
-- Table structure for table `donations`
--

CREATE TABLE `donations` (
  `donation_id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `donor_id` int(11) DEFAULT NULL,
  `donor_name` varchar(100) DEFAULT NULL,
  `donor_email` varchar(100) DEFAULT NULL,
  `donor_phone` varchar(20) NOT NULL,
  `is_anonymous` tinyint(1) DEFAULT 0,
  `amount` decimal(15,2) NOT NULL,
  `fee_percentage` decimal(5,2) NOT NULL DEFAULT 7.50,
  `fee_amount` decimal(15,2) GENERATED ALWAYS AS (`amount` * (`fee_percentage` / 100)) STORED,
  `net_amount` decimal(15,2) GENERATED ALWAYS AS (`amount` - `amount` * (`fee_percentage` / 100)) STORED,
  `tip_amount` decimal(15,2) DEFAULT 0.00,
  `status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `transaction_reference` varchar(100) DEFAULT NULL,
  `mobile_money_network` varchar(50) NOT NULL,
  `payment_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `pesapal_tracking_id` varchar(100) DEFAULT NULL,
  `currency` varchar(3) DEFAULT 'UGX',
  `iotec_transaction_id` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `donations`
--

INSERT INTO `donations` (`donation_id`, `campaign_id`, `donor_id`, `donor_name`, `donor_email`, `donor_phone`, `is_anonymous`, `amount`, `fee_percentage`, `tip_amount`, `status`, `transaction_reference`, `mobile_money_network`, `payment_date`, `created_at`, `pesapal_tracking_id`, `currency`, `iotec_transaction_id`) VALUES
(49, 10, NULL, 'Obin Ivan', 'obinacademy@gmail.com', '256743573637', 0, 10000.00, 7.50, 0.00, 'failed', 'DON-1783364035-6a4bf9c370a3f', 'Airtel Money', NULL, '2026-07-06 18:53:55', NULL, 'UGX', '019f38c7-a758-7687-bb81-809cc8dfce6b'),
(51, 12, 10, 'Anonymous', '', '392972444', 1, 3000.00, 7.50, 0.00, 'pending', 'CF_6a4caac687cee_1783409350', 'MTN Mobile Money', NULL, '2026-07-07 07:29:10', '84b4ef2a-94f5-4f09-a938-da2cadd74e7d', 'UGX', NULL),
(52, 14, NULL, 'Sedu Otolo', 'ot.sedrick@gmail.com', '0777676206', 0, 1000.00, 7.50, 0.00, 'completed', 'CF_6a4e50b7594db_1783517367', 'MTN Mobile Money', '2026-07-08 13:30:10', '2026-07-08 13:29:27', '47eddcfb-e485-41ef-995d-da2b494bef67', 'UGX', NULL),
(53, 14, NULL, 'Sedu Otolo', 'ot.sedrick@gmail.com', '0777676206', 0, 1000.00, 7.50, 0.00, 'pending', 'CF_6a4e52891707b_1783517833', 'MTN Mobile Money', NULL, '2026-07-08 13:37:13', '5c502dc4-b04d-439a-a6c1-da2b1cb85f88', 'UGX', NULL),
(54, 14, NULL, 'Anonymous', '', '256788332214', 1, 1000.00, 7.50, 0.00, 'pending', 'CF_6a4f25d242bd7_1783571922', 'MTN Mobile Money', NULL, '2026-07-09 04:38:42', 'f0020b81-17ff-469f-b512-da2ab4d80ea2', 'UGX', NULL),
(55, 14, NULL, 'Anonymous', 'ot.sedrick@gmail.com', '0777676206', 1, 1000.00, 7.50, 0.00, 'completed', 'CF_6a4f6b595ba01_1783589721', 'MTN Mobile Money', '2026-07-09 09:36:13', '2026-07-09 09:35:21', 'fc3fcf0f-e0de-4ae9-992e-da2ab84f1cfc', 'UGX', NULL),
(56, 14, NULL, 'Precious Nimar', 'preciousnimar@gmail.com', '256778244521', 0, 2000.00, 7.50, 0.00, 'pending', 'CF_6a4f7ecc47bab_1783594700', 'MTN Mobile Money', NULL, '2026-07-09 10:58:20', '514d247e-97a1-49e2-96aa-da2af9e38cf8', 'UGX', NULL),
(57, 14, NULL, 'Sarah Nakato', 'sarah.nakato@gmail.com', '256712345678', 0, 50000.00, 7.50, 0.00, 'completed', 'MMT-CAMP14-001', 'MTN Mobile Money', '2026-07-08 11:03:37', '2026-07-08 11:03:37', NULL, 'UGX', NULL),
(58, 14, NULL, 'John Mwangi', 'john.mwangi@gmail.com', '254712345678', 0, 25000.00, 7.50, 0.00, 'completed', 'MMT-CAMP14-002', 'Safaricom M-PESA', '2026-07-07 11:03:37', '2026-07-07 11:03:37', NULL, 'UGX', NULL),
(59, 14, NULL, 'Grace Achieng', 'grace.achieng@gmail.com', '256789012345', 0, 35000.00, 7.50, 0.00, 'completed', 'MMT-CAMP14-003', 'Airtel Money', '2026-07-06 11:03:37', '2026-07-06 11:03:37', NULL, 'UGX', NULL),
(60, 14, NULL, 'Peter Okello', 'peter.okello@gmail.com', '256701234567', 0, 15000.00, 7.50, 0.00, 'completed', 'MMT-CAMP14-004', 'MTN Mobile Money', '2026-07-05 11:03:37', '2026-07-05 11:03:37', NULL, 'UGX', NULL),
(61, 14, NULL, 'Mary Adong', 'mary.adong@gmail.com', '256798012345', 0, 20000.00, 7.50, 0.00, 'completed', 'MMT-CAMP14-005', 'Airtel Money', '2026-07-04 11:03:37', '2026-07-04 11:03:37', NULL, 'UGX', NULL),
(62, 14, NULL, 'David Mukasa', 'david.mukasa@gmail.com', '256712345678', 0, 30000.00, 7.50, 0.00, 'completed', 'MMT-CAMP14-006', 'MTN Mobile Money', '2026-07-03 11:03:37', '2026-07-03 11:03:37', NULL, 'UGX', NULL),
(63, 14, NULL, 'Martha Nambooze', 'martha.nambooze@gmail.com', '256707890123', 0, 12000.00, 7.50, 0.00, 'completed', 'MMT-CAMP14-007', 'Airtel Money', '2026-07-02 11:03:37', '2026-07-02 11:03:37', NULL, 'UGX', NULL),
(64, 14, NULL, 'James Ssemakula', 'james.ssemakula@gmail.com', '256703456789', 0, 18000.00, 7.50, 0.00, 'completed', 'MMT-CAMP14-008', 'MTN Mobile Money', '2026-07-01 11:03:37', '2026-07-01 11:03:37', NULL, 'UGX', NULL),
(65, 14, NULL, 'Faith Akinyi', 'faith.akinyi@gmail.com', '254767890123', 0, 22000.00, 7.50, 0.00, 'completed', 'MMT-CAMP14-009', 'Safaricom M-PESA', '2026-06-30 11:03:37', '2026-06-30 11:03:37', NULL, 'UGX', NULL),
(66, 14, NULL, 'Robert Kato', 'robert.kato@gmail.com', '256709012345', 0, 16000.00, 7.50, 0.00, 'completed', 'MMT-CAMP14-010', 'MTN Mobile Money', '2026-06-29 11:03:37', '2026-06-29 11:03:37', NULL, 'UGX', NULL),
(67, 14, NULL, 'Anonymous', NULL, '256702345678', 1, 10000.00, 7.50, 0.00, 'completed', 'MMT-CAMP14-011', 'MTN Mobile Money', '2026-06-28 11:03:37', '2026-06-28 11:03:37', NULL, 'UGX', NULL),
(68, 14, NULL, 'Anonymous', NULL, '256705678901', 1, 14000.00, 7.50, 0.00, 'completed', 'MMT-CAMP14-012', 'Airtel Money', '2026-06-27 11:03:37', '2026-06-27 11:03:37', NULL, 'UGX', NULL),
(69, 14, NULL, 'Anonymous', NULL, '254745678901', 1, 20000.00, 7.50, 0.00, 'completed', 'MMT-CAMP14-013', 'Safaricom M-PESA', '2026-06-26 11:03:37', '2026-06-26 11:03:37', NULL, 'UGX', NULL),
(70, 14, NULL, 'Anonymous', NULL, '256708901234', 1, 8000.00, 7.50, 0.00, 'completed', 'MMT-CAMP14-014', 'MTN Mobile Money', '2026-06-25 11:03:37', '2026-06-25 11:03:37', NULL, 'UGX', NULL),
(71, 14, NULL, 'Anonymous', NULL, '256700123456', 1, 12000.00, 7.50, 0.00, 'completed', 'MMT-CAMP14-015', 'Airtel Money', '2026-06-24 11:03:37', '2026-06-24 11:03:37', NULL, 'UGX', NULL),
(72, 14, NULL, 'Anonymous', NULL, '254756789012', 1, 20000.00, 7.50, 0.00, 'completed', 'MMT-CAMP14-016', 'Safaricom M-PESA', '2026-06-23 11:03:37', '2026-06-23 11:03:37', NULL, 'UGX', NULL),
(73, 14, NULL, 'Anonymous', 'okumuoscar1712@gmail.com', '+256765341463', 1, 5000.00, 7.50, 0.00, 'pending', 'CF_6a4f94063bbb3_1783600134', 'MTN Mobile Money', NULL, '2026-07-09 12:28:54', '06472982-d694-46fb-84f9-da2a0f1afc1e', 'UGX', NULL),
(74, 14, NULL, 'Brian Oscar Ojok', '', '0777676206', 0, 1000.00, 7.50, 0.00, 'completed', 'CF_6a4f95356a387_1783600437', 'MTN Mobile Money', '2026-07-09 12:34:42', '2026-07-09 12:33:57', 'b338493e-2c09-4df5-81bb-da2ab2e5f364', 'UGX', NULL),
(75, 14, NULL, 'Jenniffer Akello', '', '0783056076', 0, 2000.00, 7.50, 0.00, 'pending', 'CF_6a4f9aa527d2f_1783601829', 'MTN Mobile Money', NULL, '2026-07-09 12:57:09', 'a7c0fd09-5213-4b81-be83-da2a59f75f08', 'UGX', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `sent_via_email` tinyint(1) DEFAULT 0,
  `sent_via_sms` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `type`, `title`, `message`, `link`, `is_read`, `sent_via_email`, `sent_via_sms`, `created_at`) VALUES
(1, 2, 'donation', 'New Donation Received!', 'Grace Achieng just donated UGX 250,000 to your campaign \"Family Medical Fund for Baby Grace\"', '/campaign/1', 0, 1, 0, '2026-01-15 07:31:00'),
(2, 2, 'withdrawal', 'Withdrawal Approved', 'Your withdrawal of UGX 500,000 for \"Family Medical Fund for Baby Grace\" has been approved and is being processed.', '/withdrawals', 0, 1, 1, '2026-01-20 07:05:00'),
(3, 3, 'donation', 'New Donation Received!', 'Anonymous Donor just donated KES 200,000 to your campaign \"Clean Water Borehole for Kibera Community\"', '/campaign/2', 0, 1, 0, '2026-01-16 10:16:00'),
(4, 4, 'donation', 'Donation Confirmed', 'Your donation of UGX 250,000 to \"Family Medical Fund for Baby Grace\" was successful. Thank you!', '/campaign/1', 1, 1, 1, '2026-01-15 07:32:00'),
(5, 2, 'donation', 'New Donation Received!', 'Anonymous Donor just donated 10,000 to your campaign \"Sedricks School Fees\"', '/chama/campaign-detail.php?id=5', 0, 0, 0, '2026-06-23 17:53:22'),
(6, 5, 'donation', 'New Donation Received!', 'Sedu Otolo just donated 10,000 to your campaign \"Johnson Opio Heart Surgery\"', '/chama/campaign-detail.php?id=6', 0, 0, 0, '2026-06-23 18:39:43'),
(7, 5, 'donation', 'New Donation Received!', 'Sedu Otolo just donated 1,000,000 to your campaign \"Johnson Opio Heart Surgery\"', '/chama/campaign-detail.php?id=6', 0, 0, 0, '2026-06-23 18:40:01'),
(8, 2, 'donation', 'New Donation Received!', 'Sedu Otolo just donated 1,000 to your campaign \"Sedricks School Fees\"', '/chama/campaign-detail.php?id=5', 0, 0, 0, '2026-07-01 06:21:44'),
(9, 2, 'donation', 'New Donation Received!', 'Sedu Otolo just donated 1,000 to your campaign \"Sedricks School Fees\"', '/chama/campaign-detail.php?id=5', 0, 0, 0, '2026-07-01 14:14:47'),
(10, 2, 'donation', 'New Donation Received!', 'Anonymous Donor just donated 1,000 to your campaign \"Family Medical Fund for Baby Grace\"', '/chama/campaign-detail.php?id=1', 0, 0, 0, '2026-07-02 11:56:36'),
(11, 2, 'donation', 'New Donation Received!', 'Hanah just donated 1,000 to your campaign \"Sedricks School Fees\"', '/chama/campaign-detail.php?id=5', 0, 0, 0, '2026-07-03 13:48:42'),
(12, 2, 'donation', 'New Donation Received!', 'Ivan Obin just donated 1,000 to your campaign \"Family Medical Fund for Baby Grace\"', '/chama/campaign-detail.php?id=1', 0, 0, 0, '2026-07-03 14:49:08'),
(13, 2, 'donation', 'New Donation Received!', 'Jojo just donated 1,000 to your campaign \"Family Medical Fund for Baby Grace\"', '/chama/campaign-detail.php?id=1', 0, 0, 0, '2026-07-03 19:51:23'),
(14, 7, 'donation', 'New Donation Received!', 'Sedrick Otolo just donated 1,000 to your campaign \"End Period Poverty for Vulnerable Girls in Abim District, Karamoja, Uganda\"', 'https://undpconnect.org/chama/campaign-detail.php?id=8', 0, 0, 0, '2026-07-04 13:07:34'),
(15, 7, 'donation', 'New Donation Received!', 'Obin Ivan just donated 1,000 to your campaign \"End Period Poverty for Vulnerable Girls in Abim District, Karamoja, Uganda\"', 'https://undpconnect.org/chama/campaign-detail.php?id=10', 0, 0, 0, '2026-07-04 15:20:18'),
(16, 7, 'donation', 'New Donation Received!', 'Walter Okol just donated 1,000 to your campaign \"End Period Poverty for Vulnerable Girls in Abim District, Karamoja, Uganda\"', 'https://chamafunds.com/campaign-detail.php?id=10', 0, 0, 0, '2026-07-05 06:52:03'),
(17, 7, 'donation', 'New Donation Received!', 'Natasha Ahereza just donated 1,000 to your campaign \"End Period Poverty for Vulnerable Girls in Abim District, Karamoja, Uganda\"', 'https://chamafunds.com/campaign-detail.php?id=10', 0, 0, 0, '2026-07-06 08:21:05'),
(18, 10, 'donation', 'New Donation Received!', 'Anonymous Donor just donated 2,000 to your campaign \"Birthday Celebration with Vulnerable children\"', 'https://chamafunds.com/campaign-detail.php?id=12', 0, 0, 0, '2026-07-07 07:25:55'),
(19, 11, 'donation', 'New Donation Received!', 'Sedu Otolo just donated 1,000 to your campaign \"Celebrating birthday with the vulnerable children\"', 'https://chamafunds.com/campaign-detail.php?id=14', 0, 0, 0, '2026-07-08 13:30:10'),
(20, 11, 'donation', 'New Donation Received!', 'Anonymous Donor just donated 1,000 to your campaign \"Celebrating birthday with the vulnerable children\"', 'https://chamafunds.com/campaign-detail.php?id=14', 0, 0, 0, '2026-07-09 09:36:13'),
(21, 11, 'donation', 'New Donation Received!', 'Brian Oscar Ojok just donated 1,000 to your campaign \"Celebrating birthday with the vulnerable children\"', 'https://chamafunds.com/campaign-detail.php?id=14', 0, 0, 0, '2026-07-09 12:34:42');

-- --------------------------------------------------------

--
-- Table structure for table `platform_settings`
--

CREATE TABLE `platform_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_group` varchar(50) DEFAULT 'general',
  `is_encrypted` tinyint(1) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `platform_settings`
--

INSERT INTO `platform_settings` (`setting_id`, `setting_key`, `setting_value`, `setting_group`, `is_encrypted`, `updated_at`) VALUES
(1, 'platform_name', 'ChamaFunds', 'general', 0, '2026-06-23 14:26:32'),
(2, 'platform_tagline', 'Pool Money Together for What Matters Most', 'general', 0, '2026-06-23 14:26:32'),
(3, 'platform_email', 'support@chamafunds.com', 'general', 0, '2026-06-23 14:26:32'),
(4, 'platform_phone', '+256700000001', 'general', 0, '2026-06-23 14:26:32'),
(5, 'platform_fee', '7.5', 'fees', 0, '2026-06-23 14:26:32'),
(6, 'fee_applied_at', 'withdrawal', 'fees', 0, '2026-06-23 14:26:32'),
(7, 'maintenance_mode', 'false', 'security', 0, '2026-06-23 14:26:32'),
(8, 'max_donation_amount', '1000000', 'payments', 0, '2026-06-23 14:26:32'),
(9, 'min_donation_amount', '1000', 'payments', 0, '2026-06-23 14:26:32'),
(10, 'session_timeout', '60', 'security', 0, '2026-06-23 14:26:32'),
(11, 'default_country', 'Uganda', 'general', 0, '2026-06-23 14:26:32'),
(12, 'two_factor_enabled', 'false', 'security', 0, '2026-06-23 14:26:32'),
(13, 'email_notifications_enabled', 'true', 'notifications', 0, '2026-06-23 14:26:32'),
(14, 'sms_notifications_enabled', 'true', 'notifications', 0, '2026-06-23 14:26:32'),
(15, 'default_currency', 'UGX', 'general', 0, '2026-06-23 14:26:32'),
(16, 'pesapal_ipn_id', '29251393-21c3-4b72-9403-da322310d3c8', 'payment', 0, '2026-07-01 14:13:59');

-- --------------------------------------------------------

--
-- Table structure for table `pledges`
--

CREATE TABLE `pledges` (
  `pledge_id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `pledger_name` varchar(100) NOT NULL,
  `pledger_phone` varchar(20) DEFAULT NULL,
  `pledger_email` varchar(100) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `status` enum('pending','paid','reminded','cancelled') DEFAULT 'pending',
  `reminder_sent_count` int(11) DEFAULT 0,
  `last_reminder_sent` timestamp NULL DEFAULT NULL,
  `paid_donation_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pledges`
--

INSERT INTO `pledges` (`pledge_id`, `campaign_id`, `pledger_name`, `pledger_phone`, `pledger_email`, `amount`, `status`, `reminder_sent_count`, `last_reminder_sent`, `paid_donation_id`, `created_at`) VALUES
(1, 1, 'Mary Adong', '256798012345', 'mary.adong@gmail.com', 50000.00, 'pending', 1, '2026-01-18 05:00:00', NULL, '2026-01-10 09:00:00'),
(2, 1, 'John Opio', '256799123456', 'john.opio@gmail.com', 75000.00, 'reminded', 2, '2026-01-19 06:00:00', NULL, '2026-01-09 11:30:00'),
(3, 2, 'Esther Kamau', '254780123456', 'esther.kamau@gmail.com', 100000.00, 'pending', 0, NULL, NULL, '2026-01-12 07:00:00'),
(4, 3, 'David Balidawa', '256701234567', 'david.balidawa@gmail.com', 50000.00, 'paid', 1, '2026-01-14 05:00:00', NULL, '2026-01-05 13:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','campaigner','donor') NOT NULL DEFAULT 'donor',
  `country` varchar(50) DEFAULT 'Uganda',
  `avatar_url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `is_verified` tinyint(1) DEFAULT 0,
  `two_factor_enabled` tinyint(1) DEFAULT 0,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `phone`, `password_hash`, `role`, `country`, `avatar_url`, `is_active`, `is_verified`, `two_factor_enabled`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'Sedrick Otolo', 'info@chamafunds.com', '256700000001', '$2y$10$dJjq8XZqB3XqB3XqB3XqB3XqB3XqB3XqB3XqB3XqB3XqB3XqB3Xq', 'admin', 'Uganda', NULL, 1, 1, 0, NULL, '2026-07-02 13:48:44', '2026-07-04 07:11:16'),
(2, 'Sarah Nakato', 'campaigner@chamafunds.com', '256712345678', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'campaigner', 'Uganda', NULL, 1, 1, 0, NULL, '2026-07-02 13:48:44', '2026-07-02 13:48:44'),
(6, 'Sedrick Otolo', 'ot.sedrick@gmail.com', '256700000005', 'S3izE316', 'admin', 'Uganda', NULL, 1, 1, 0, '2026-07-09 09:18:36', '2026-07-04 07:15:37', '2026-07-09 09:18:36'),
(7, 'Obin Ivan', 'obinacademy@gmail.com', '256743573637', 'Password1*', 'campaigner', 'Uganda', NULL, 1, 0, 0, '2026-07-09 13:25:48', '2026-07-04 12:01:10', '2026-07-09 13:25:48'),
(8, 'Elizabeth Akello', 'elizabethakello246@gmail.com', '0777681745', 'lizy123456', 'campaigner', 'Uganda', NULL, 1, 0, 0, NULL, '2026-07-04 14:55:51', '2026-07-04 14:55:51'),
(9, 'Jerome  Oscar', 'jeromeoscar2002@gmail.com', '0707711682', '2026@New', 'donor', 'Uganda', NULL, 1, 0, 0, '2026-07-04 21:10:30', '2026-07-04 19:17:16', '2026-07-04 21:10:30'),
(10, 'Ajwer Norman', 'ajwernorman@gmail.com', '256392972444', 'Lovenomi13#', 'campaigner', 'Uganda', NULL, 1, 0, 0, NULL, '2026-07-07 07:09:03', '2026-07-07 07:09:03'),
(11, 'Ajwer Norman', 'lifespringmedicalcentre1@gmail.com', '0726902523', 'Lovenomi13#', 'campaigner', 'Uganda', NULL, 1, 0, 0, NULL, '2026-07-08 09:10:09', '2026-07-08 09:10:09');

-- --------------------------------------------------------

--
-- Table structure for table `withdrawals`
--

CREATE TABLE `withdrawals` (
  `withdrawal_id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `campaigner_id` int(11) NOT NULL,
  `gross_amount` decimal(15,2) NOT NULL,
  `fee_percentage` decimal(5,2) NOT NULL DEFAULT 7.50,
  `fee_amount` decimal(15,2) GENERATED ALWAYS AS (`gross_amount` * (`fee_percentage` / 100)) STORED,
  `net_amount` decimal(15,2) GENERATED ALWAYS AS (`gross_amount` - `gross_amount` * (`fee_percentage` / 100)) STORED,
  `mobile_money_number` varchar(20) NOT NULL,
  `mobile_money_network` varchar(50) NOT NULL,
  `status` enum('pending','approved','completed','rejected') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `transaction_reference` varchar(100) DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `withdrawals`
--

INSERT INTO `withdrawals` (`withdrawal_id`, `campaign_id`, `campaigner_id`, `gross_amount`, `fee_percentage`, `mobile_money_number`, `mobile_money_network`, `status`, `approved_by`, `approved_at`, `rejection_reason`, `admin_notes`, `transaction_reference`, `completed_at`, `requested_at`, `updated_at`) VALUES
(1, 1, 2, 500000.00, 7.50, '256712345678', 'MTN Mobile Money', 'completed', 1, '2026-01-20 07:00:00', NULL, 'Approved - first withdrawal', 'MMT-WD-UG-2026-001', '2026-01-20 09:00:00', '2026-01-19 05:00:00', '2026-06-23 14:26:32'),
(2, 1, 2, 250000.00, 7.50, '256712345678', 'MTN Mobile Money', 'approved', 1, '2026-01-22 06:00:00', NULL, 'Approved - second withdrawal', NULL, NULL, '2026-01-21 08:00:00', '2026-06-23 14:26:32'),
(3, 2, 3, 800000.00, 7.50, '254712345678', 'Safaricom M-PESA', 'pending', NULL, NULL, NULL, 'Awaiting approval', NULL, NULL, '2026-01-22 11:30:00', '2026-06-23 14:26:32'),
(4, 3, 2, 300000.00, 7.50, '256789012345', 'MTN Mobile Money', 'rejected', NULL, NULL, 'Rejected by admin.', NULL, NULL, NULL, '2026-01-23 06:00:00', '2026-07-06 18:30:24');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_logs_admin` (`admin_id`),
  ADD KEY `idx_logs_target` (`target_type`,`target_id`),
  ADD KEY `idx_logs_created` (`created_at`);

--
-- Indexes for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  ADD PRIMARY KEY (`notif_id`),
  ADD KEY `idx_admin_notif_read` (`is_read`),
  ADD KEY `idx_admin_notif_type` (`type`);

--
-- Indexes for table `campaigns`
--
ALTER TABLE `campaigns`
  ADD PRIMARY KEY (`campaign_id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `campaigner_id` (`campaigner_id`),
  ADD KEY `idx_campaigns_slug` (`slug`),
  ADD KEY `idx_campaigns_status` (`status`),
  ADD KEY `idx_campaigns_category` (`category`),
  ADD KEY `idx_campaigns_country` (`country`),
  ADD KEY `idx_campaigns_end_date` (`end_date`);

--
-- Indexes for table `campaign_categories`
--
ALTER TABLE `campaign_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `campaign_images`
--
ALTER TABLE `campaign_images`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `idx_campaign_images` (`campaign_id`),
  ADD KEY `idx_cover` (`campaign_id`,`is_cover`);

--
-- Indexes for table `countries`
--
ALTER TABLE `countries`
  ADD PRIMARY KEY (`country_id`),
  ADD UNIQUE KEY `country_name` (`country_name`),
  ADD UNIQUE KEY `country_code` (`country_code`);

--
-- Indexes for table `donations`
--
ALTER TABLE `donations`
  ADD PRIMARY KEY (`donation_id`),
  ADD KEY `idx_donations_campaign` (`campaign_id`),
  ADD KEY `idx_donations_donor` (`donor_id`),
  ADD KEY `idx_donations_status` (`status`),
  ADD KEY `idx_donations_created` (`created_at`),
  ADD KEY `idx_pesapal_tracking` (`pesapal_tracking_id`),
  ADD KEY `idx_donations_pesapal_tracking` (`pesapal_tracking_id`),
  ADD KEY `idx_donations_pesapal` (`pesapal_tracking_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `idx_notifications_user` (`user_id`),
  ADD KEY `idx_notifications_read` (`is_read`);

--
-- Indexes for table `platform_settings`
--
ALTER TABLE `platform_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `pledges`
--
ALTER TABLE `pledges`
  ADD PRIMARY KEY (`pledge_id`),
  ADD KEY `paid_donation_id` (`paid_donation_id`),
  ADD KEY `idx_pledges_campaign` (`campaign_id`),
  ADD KEY `idx_pledges_status` (`status`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD KEY `idx_users_email` (`email`),
  ADD KEY `idx_users_phone` (`phone`),
  ADD KEY `idx_users_role` (`role`);

--
-- Indexes for table `withdrawals`
--
ALTER TABLE `withdrawals`
  ADD PRIMARY KEY (`withdrawal_id`),
  ADD KEY `campaigner_id` (`campaigner_id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_withdrawals_campaign` (`campaign_id`),
  ADD KEY `idx_withdrawals_status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  MODIFY `notif_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `campaigns`
--
ALTER TABLE `campaigns`
  MODIFY `campaign_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `campaign_categories`
--
ALTER TABLE `campaign_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `campaign_images`
--
ALTER TABLE `campaign_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `countries`
--
ALTER TABLE `countries`
  MODIFY `country_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `donations`
--
ALTER TABLE `donations`
  MODIFY `donation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `platform_settings`
--
ALTER TABLE `platform_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `pledges`
--
ALTER TABLE `pledges`
  MODIFY `pledge_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `withdrawals`
--
ALTER TABLE `withdrawals`
  MODIFY `withdrawal_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD CONSTRAINT `admin_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
