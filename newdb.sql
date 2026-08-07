-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jul 09, 2026 at 07:39 AM
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
(7, 'new_campaign', 'New Campaign: Celebrating birthday with the vulnerable children', 'Ajwer Norman created \"Celebrating birthday with the vulnerable children\"', '/admin/index.php?tab=campaigns&view=14', 0, '2026-07-08 09:12:08');

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
(1, 2, NULL, 'Family Medical Fund for Baby Grace', 'family-medical-fund-baby-grace', 'Our daughter Grace was born with a condition that requires immediate surgery. The total cost is UGX 5,000,000. We have raised some funds from family but we need help from our wider community. Every contribution brings us closer to saving our baby girl.', 'Medical', 5000000.00, 'UGX', 403000.00, 6, 'https://images.unsplash.com/photo-1582750433449-648ed127bb54?w=800', '256712345678', 'MTN Mobile Money', 'suspended', '2026-06-23 14:26:32', NULL, 'Uganda', 1, 1282, 91, '2026-06-23 14:26:32', '2026-07-04 15:13:31', NULL),
(2, 3, NULL, 'Clean Water Borehole for Kibera Community', 'clean-water-borehole-kibera', 'Access to clean water is a daily challenge for the Kibera community. We are raising funds to drill a borehole that will serve over 500 families. This project will transform lives and provide sustainable access to safe drinking water.', 'Community', 8000000.00, 'KES', 1000000.00, 3, 'https://images.unsplash.com/photo-1548839140-29a749e1cf4d?w=800', '254712345678', 'Airtel Money', 'active', '2026-06-23 14:26:32', NULL, 'Kenya', 1, 2303, 156, '2026-06-23 14:26:32', '2026-07-01 13:12:36', NULL),
(3, 2, NULL, 'Education Scholarship for 10 Bright Students', 'education-scholarship-10-students', 'We are raising funds to provide full scholarships for 10 academically gifted students from low-income families. Each scholarship covers tuition, books, and supplies for one academic year. Help us invest in the future leaders of Uganda.', 'Education', 3000000.00, 'UGX', 450000.00, 3, 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=800', '256789012345', 'MTN Mobile Money', 'suspended', '2026-06-23 14:26:32', NULL, 'Uganda', 0, 891, 43, '2026-06-23 14:26:32', '2026-07-04 15:13:21', NULL),
(4, 3, NULL, 'Emergency Flood Relief for Kisumu Families', 'emergency-flood-relief-kisumu', 'Heavy rains have caused devastating floods in Kisumu, displacing over 200 families. We are raising emergency funds to provide food, shelter, and essential supplies to those affected. Every donation makes a difference in helping these families rebuild.', 'Emergency', 10000000.00, 'KES', 1750000.00, 3, 'https://images.unsplash.com/photo-1534274988757-a28bf1a57c17?w=800', '254789012345', 'Safaricom M-PESA', 'active', '2026-06-23 14:26:32', NULL, 'Kenya', 0, 3458, 201, '2026-06-23 14:26:32', '2026-07-01 13:06:45', NULL),
(5, 2, NULL, 'Sedricks School Fees', 'sedricks-school-fees-52ccd', 'Sedrick is raising funds to help him complete his examinations and continue the academic program he has started at the university. He is seeking support to enable him to successfully achieve this goal. Any contribution, big or small, will make a meaningful difference and is deeply appreciated. Thank you for your generosity and support.', 'Education', 1500000.00, 'UGX', 13000.00, 4, '/uploads/campaigns/camp_6a3ac78952e22.jpg', '0777676206', 'MTN Mobile Money', 'suspended', '2026-06-23 17:51:05', '2026-07-08 21:00:00', 'Uganda', 0, 84, 3, '2026-06-23 17:51:05', '2026-07-08 08:58:33', NULL),
(6, 5, NULL, 'Johnson Opio Heart Surgery', 'johnson-opio-heart-surgery-f0173', 'This fundraising campaign is dedicated to supporting a child in need of urgent heart surgery. The child requires specialized medical care and treatment to improve their health and give them a chance at a better future. We are seeking your support to help cover the medical expenses and ensure the surgery can be carried out successfully. Any contribution, no matter the amount, will make a life-changing difference and is deeply appreciated. Thank you for your kindness, generosity, and support.', 'Medical', 5000000.00, 'UGX', 1010000.00, 2, '/uploads/campaigns/camp_6_1782240285.png', '0777676206', 'MTN Mobile Money', 'active', '2026-06-23 18:26:14', '2026-06-29 21:00:00', 'Uganda', 0, 20, 0, '2026-06-23 18:26:14', '2026-07-08 08:58:33', NULL),
(8, 7, NULL, 'End Period Poverty for Vulnerable Girls in Abim District, Karamoja, Uganda', 'end-period-poverty-for-vulnerable-girls-in-abim-district-karamoja-uganda-2b312', 'Riziki Youth Umbrella is reaching out to the communities of Karamoja Region, Abim District, as a nonprofit organization dedicated to improving menstrual hygiene for vulnerable girls.\r\n\r\nWe invite compassionate individuals, organizations, and partners to support our mission of ending period poverty in Uganda. Your donation will help provide menstrual hygiene kits, promote menstrual health education, and ensure that girls can stay in school with dignity and confidence.\r\n\r\nEvery contribution, no matter the amount, brings us one step closer to a future where no girl misses school because of her period.\r\n\r\nTogether, we can end period poverty in Uganda—one girl, one community, and one future at a time.', 'Community', 7500000.00, 'UGX', 1000.00, 1, '/uploads/campaigns/camp_7_1783168407_0.webp', '256743573637', 'Airtel Money', 'suspended', '2026-07-04 12:33:27', '2026-08-31 00:00:00', 'Uganda', 0, 29, 1, '2026-07-04 12:33:27', '2026-07-08 08:58:33', NULL),
(9, 8, NULL, 'fun raising for Alupu\'s tuition', 'fun-raising-for-alupu-s-tuition-f1b54', 'This is a very brilliant girl but she likes tuition to finish her school so you want to be able to raise that money to help her finish school well so that she becomes a good girl because right now she\'s', 'Education', 500000.00, 'UGX', 0.00, 0, '/uploads/campaigns/camp_8_1783177155_0.jpg', '0777681745', 'MTN Mobile Money', 'suspended', '2026-07-04 14:59:15', '2026-07-30 00:00:00', 'Uganda', 0, 5, 0, '2026-07-04 14:59:15', '2026-07-08 08:58:33', NULL),
(10, 7, NULL, 'End Period Poverty for Vulnerable Girls in Abim District, Karamoja, Uganda', 'end-period-poverty-for-vulnerable-girls-in-abim-district-karamoja-uganda-a5247', 'Campaign Story\r\nRiziki Youth Umbrella is reaching out to the communities of Karamoja Region, Abim District, as a nonprofit organization dedicated to improving menstrual hygiene for vulnerable girls.\r\n\r\nWe invite compassionate individuals, organizations, and partners to support our mission of ending period poverty in Uganda. Your donation will help provide menstrual hygiene kits, promote menstrual health education, and ensure that girls can stay in school with dignity and confidence.\r\n\r\nEvery contribution, no matter the amount, brings us one step closer to a future where no girl misses school because of her period.\r\n\r\nTogether, we can end period poverty in Uganda—one girl, one community, and one future at a time.', 'Community', 7500000.00, 'UGX', 7000.00, 7, '/uploads/campaigns/camp_7_1783177692_0.webp', '256743573637', 'Airtel Money', 'paused', '2026-07-04 15:08:12', '2026-08-31 00:00:00', 'Uganda', 0, 101, 1, '2026-07-04 15:08:12', '2026-07-08 14:35:27', NULL),
(11, 9, NULL, 'Surgery', 'surgery-5cc58', 'It’s really sad that I can’t get my surgery in Uganda and have to go to India', 'Medical', 5000000.00, 'UGX', 0.00, 0, '/uploads/campaigns/camp_9_1783192940_0.jpg', '0707711682', 'MTN Mobile Money', 'draft', '2026-07-04 19:22:20', '2026-08-01 00:00:00', 'Uganda', 0, 0, 0, '2026-07-04 19:22:20', '2026-07-04 19:22:20', NULL),
(12, 10, NULL, 'Birthday Celebration with Vulnerable children', 'birthday-celebration-with-vulnerable-children-904d4', 'On 24th July 2026, I have chosen to celebrate with the beautiful children at Ngetta Babies Home. Many of these children have no parents to hold them, no relatives they know, and no family to celebrate life’s special moments with them.\r\n\r\nFor just one day, I want to be the parent they can smile with, the relative they can lean on, and the friend who reminds them that they are loved, valued, and never forgotten.\r\n\r\nInstead of buying me birthday gifts, I humbly ask you to bless these children instead. Your contribution, no matter how small, can make a real difference. You can donate items such as:\r\n\r\n* Sugar\r\n* Soap\r\n* Rice\r\n* Posho (maize flour)\r\n* Beans\r\n* Cooking oil\r\n* Milk\r\n* Clothes\r\n* Shoes\r\n* Diapers\r\n* Toiletries\r\n* School supplies\r\n* Or any other item you feel would bring comfort and joy to these children.\r\n\r\nTogether, let’s make this birthday a celebration of hope, kindness, and love. Let us remind these children that they are part of a family that cares.\r\n\r\nThe greatest gift you can give me this year is seeing a smile on a child’s face.\r\n\r\nThank you for standing with me and for helping make this birthday truly meaningful.', 'Community', 2000000.00, 'UGX', 2000.00, 1, '/uploads/campaigns/camp_10_1783408539_0.png', '256760167722', 'MTN Mobile Money', 'suspended', '2026-07-07 07:15:39', '2026-07-23 00:00:00', 'Uganda', 0, 68, 1, '2026-07-07 07:15:39', '2026-07-09 03:53:18', NULL),
(13, 6, NULL, 'Collections for Sedrick Otolo', 'collections-for-sedrick-otolo-f339e', 'On 24th July 2026, I have chosen to celebrate with the beautiful children at Ngetta Babies Home. Many of these children have no parents to hold them, no relatives they know, and no family to celebrate life’s special moments with them.\r\n\r\nFor just one day, I want to be the parent they can smile with, the relative they can lean on, and the friend who reminds them that they are loved, valued, and never forgotten.\r\n\r\nInstead of buying me birthday gifts, I humbly ask you to bless these children instead. Your contribution, no matter how small, can make a real difference. You can donate items such as:\r\n\r\n* Sugar\r\n* Soap\r\n* Rice\r\n* Posho (maize flour)\r\n* Beans\r\n* Cooking oil\r\n* Milk\r\n* Clothes\r\n* Shoes\r\n* Diapers\r\n* Toiletries\r\n* School supplies\r\n* Or any other item you feel would bring comfort and joy to these children.\r\n\r\nTogether, let’s make this birthday a celebration of hope, kindness, and love. Let us remind these children that they are part of a family that cares.\r\n\r\nThe greatest gift you can give me this year is seeing a smile on a child’s face.\r\n\r\nThank you for standing with me and for helping make this birthday truly meaningful.', 'Emergency', 40000.00, 'UGX', 0.00, 0, '/uploads/campaigns/camp_6_1783501356_0.jpg', '256700000005', 'MTN Mobile Money', 'suspended', '2026-07-08 09:02:36', '2026-08-05 00:00:00', 'Uganda', 0, 3, 0, '2026-07-08 09:02:36', '2026-07-08 09:05:54', NULL),
(14, 11, NULL, 'Celebrating birthday with the vulnerable children', 'celebrating-birthday-with-the-vulnerable-children-b8d82', 'On 24th July 2026, I have chosen to celebrate with the beautiful children at Ngetta Babies Home. Many of these children have no parents to hold them, no relatives they know, and no family to celebrate life’s special moments with them.\r\n\r\nFor just one day, I want to be the parent they can smile with, the relative they can lean on, and the friend who reminds them that they are loved, valued, and never forgotten.\r\n\r\nInstead of buying me birthday gifts, I humbly ask you to bless these children instead. Your contribution, no matter how small, can make a real difference. You can donate items such as:\r\n\r\n* Sugar\r\n* Soap\r\n* Rice\r\n* Posho (maize flour)\r\n* Beans\r\n* Cooking oil\r\n* Milk\r\n* Clothes\r\n* Shoes\r\n* Diapers\r\n* Toiletries\r\n* School supplies\r\n* Or any other item you feel would bring comfort and joy to these children.\r\n\r\nTogether, let’s make this birthday a celebration of hope, kindness, and love. Let us remind these children that they are part of a family that cares.\r\n\r\nThe greatest gift you can give me this year is seeing a smile on a child’s face.\r\n\r\nThank you for standing with me and for helping make this birthday truly meaningful.', 'Community', 1500000.00, 'UGX', 1000.00, 1, '/uploads/campaigns/camp_11_1783501926_0.png', '0760167722', 'MTN Mobile Money', 'active', '2026-07-08 09:12:06', '2026-07-23 00:00:00', 'Uganda', 0, 31, 0, '2026-07-08 09:12:06', '2026-07-09 07:22:05', NULL);

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
(27, 14, '/uploads/campaigns/camp_11_1783501926_0.png', 1, 0, NULL, '2026-07-08 09:12:06');

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
(1, 1, 4, 'Grace Achieng', 'grace.achieng@gmail.com', '256789012345', 0, 250000.00, 7.50, 0.00, 'completed', 'MMT-UG-2026-001', 'MTN Mobile Money', '2026-01-15 07:30:00', '2026-06-23 14:26:32', NULL, 'UGX', NULL),
(2, 1, NULL, 'Peter Okello', 'peter.okello@gmail.com', '256701234567', 0, 100000.00, 7.50, 5000.00, 'completed', 'MMT-UG-2026-002', 'MTN Mobile Money', '2026-01-16 11:20:00', '2026-06-23 14:26:32', NULL, 'UGX', NULL),
(3, 1, NULL, 'Anonymous Donor', NULL, '256702345678', 1, 50000.00, 7.50, 0.00, 'completed', 'MMT-UG-2026-003', 'Airtel Money', '2026-01-17 06:45:00', '2026-06-23 14:26:32', NULL, 'UGX', NULL),
(5, 2, NULL, 'David Mwangi', 'david.mwangi@gmail.com', '254723456789', 0, 500000.00, 7.50, 0.00, 'completed', 'MMT-KE-2026-005', 'Safaricom M-PESA', '2026-01-14 08:00:00', '2026-06-23 14:26:32', NULL, 'UGX', NULL),
(6, 2, NULL, 'Alice Wanjiku', 'alice.wanjiku@gmail.com', '254734567890', 0, 300000.00, 7.50, 10000.00, 'completed', 'MMT-KE-2026-006', 'Airtel Money', '2026-01-15 05:30:00', '2026-06-23 14:26:32', NULL, 'UGX', NULL),
(7, 2, NULL, 'Anonymous Donor', NULL, '254745678901', 1, 200000.00, 7.50, 0.00, 'completed', 'MMT-KE-2026-007', 'Safaricom M-PESA', '2026-01-16 10:15:00', '2026-06-23 14:26:32', NULL, 'UGX', NULL),
(9, 3, 4, 'Grace Achieng', 'grace.achieng@gmail.com', '256789012345', 0, 200000.00, 7.50, 5000.00, 'completed', 'MMT-UG-2026-009', 'MTN Mobile Money', '2026-01-12 06:00:00', '2026-06-23 14:26:32', NULL, 'UGX', NULL),
(10, 3, NULL, 'Martha Nambooze', 'martha.nambooze@gmail.com', '256707890123', 0, 150000.00, 7.50, 0.00, 'completed', 'MMT-UG-2026-010', 'Airtel Money', '2026-01-13 08:30:00', '2026-06-23 14:26:32', NULL, 'UGX', NULL),
(11, 3, NULL, 'Anonymous Donor', NULL, '256708901234', 1, 100000.00, 7.50, 0.00, 'completed', 'MMT-UG-2026-011', 'MTN Mobile Money', '2026-01-14 12:20:00', '2026-06-23 14:26:32', NULL, 'UGX', NULL),
(13, 4, NULL, 'Faith Akinyi', 'faith.akinyi@gmail.com', '254767890123', 0, 1000000.00, 7.50, 20000.00, 'completed', 'MMT-KE-2026-013', 'Safaricom M-PESA', '2026-01-10 04:00:00', '2026-06-23 14:26:32', NULL, 'UGX', NULL),
(14, 4, NULL, 'Anonymous Donor', NULL, '254778901234', 1, 500000.00, 7.50, 0.00, 'completed', 'MMT-KE-2026-014', 'Airtel Money', '2026-01-11 06:30:00', '2026-06-23 14:26:32', NULL, 'UGX', NULL),
(15, 4, NULL, 'Joseph Odhiambo', 'joseph.odhiambo@gmail.com', '254789012345', 0, 250000.00, 7.50, 0.00, 'completed', 'MMT-KE-2026-015', 'Safaricom M-PESA', '2026-01-12 11:45:00', '2026-06-23 14:26:32', NULL, 'UGX', NULL),
(17, 5, 1, 'Sedu Otolo', 'ot.sedrick@gmail.com', '0777676206', 1, 10000.00, 7.50, 0.00, 'completed', 'MMT-SED-2026-6A3AC81201483', 'MTN Mobile Money', '2026-06-23 17:53:22', '2026-06-23 17:53:22', NULL, 'UGX', NULL),
(18, 6, NULL, 'Sedu Otolo', 'ot.sedrick@gmail.com', '0777676206', 0, 10000.00, 7.50, 0.00, 'completed', 'MMT-JOH-2026-6A3AD2EF29E6E', 'MTN Mobile Money', '2026-06-23 18:39:43', '2026-06-23 18:39:43', NULL, 'UGX', NULL),
(19, 6, NULL, 'Sedu Otolo', 'ot.sedrick@gmail.com', '0777676206', 0, 1000000.00, 7.50, 0.00, 'completed', 'MMT-JOH-2026-6A3AD30102B75', 'MTN Mobile Money', '2026-06-23 18:40:01', '2026-06-23 18:40:01', NULL, 'UGX', NULL),
(20, 5, NULL, 'Sedu Otolo', 'ot.sedrick@gmail.com', '079123453', 0, 1000.00, 7.50, 0.00, 'completed', 'MMT-SED-2026-6A44B1F83EE9D', 'MTN Mobile Money', '2026-07-01 06:21:44', '2026-07-01 06:21:44', NULL, 'UGX', NULL),
(25, 5, NULL, 'Sedu Otolo', 'ot.sedrick@gmail.com', '0777676206', 0, 1000.00, 7.50, 0.00, 'completed', 'CF_6a4520a5a7d42_1782915237', 'MTN Mobile Money', '2026-07-01 14:14:47', '2026-07-01 14:13:57', 'b94522fd-403f-40bd-8545-da3275c7d860', 'UGX', NULL),
(26, 1, NULL, 'Anonymous', 'sedricksedu2@gmail.com', '0777676206', 1, 1000.00, 7.50, 0.00, 'completed', 'CF_6a4651ce99f41_1782993358', 'MTN Mobile Money', '2026-07-02 11:56:36', '2026-07-02 11:55:58', '8c69b903-1a40-4078-bd8f-da31ea024459', 'UGX', NULL),
(29, 5, NULL, 'Hanah', 'hanah.terisah256@gmail.com', '0705977394', 0, 1000.00, 7.50, 0.00, 'completed', 'CF_6a47bd89887c8_1783086473', 'Airtel Money', '2026-07-03 13:48:42', '2026-07-03 13:47:53', 'd5d15f85-4c84-46c2-b9be-da30883a4584', 'UGX', NULL),
(31, 1, NULL, 'Ivan Obin', '', '0743573637', 0, 1000.00, 7.50, 0.00, 'completed', 'CF_6a47cbbe5da18_1783090110', 'Airtel Money', '2026-07-03 14:49:08', '2026-07-03 14:48:30', 'e50c10bc-9121-40f2-8342-da3016c5f568', 'UGX', NULL),
(32, 1, NULL, 'Jojo', '', '0759526143', 0, 1000.00, 7.50, 0.00, 'completed', 'CF_6a48128e0482c_1783108238', 'Airtel Money', '2026-07-03 19:51:23', '2026-07-03 19:50:38', '7c321914-efb0-4075-9fc1-da309aacbcb9', 'UGX', NULL),
(33, 8, 6, 'Sedrick Otolo', 'sedricksedu2@gmail.com', '0777676206', 0, 1000.00, 7.50, 0.00, 'completed', 'CF_6a49055a23d44_1783170394', 'MTN Mobile Money', '2026-07-04 13:07:34', '2026-07-04 13:06:34', '3cb64d18-d5ee-40ba-8f34-da2f6d6ffe76', 'UGX', NULL),
(35, 10, NULL, 'Obin Ivan', 'obinacademy@gmail.com', '0743573637', 0, 1000.00, 7.50, 0.00, 'completed', 'CF_6a49232f1d4a2_1783178031', 'Airtel Money', '2026-07-04 15:20:18', '2026-07-04 15:13:51', 'c334ca9d-8f85-4684-aec9-da2ff780d3c2', 'UGX', NULL),
(39, 10, NULL, 'Walter Okol', '', '772626203', 0, 1000.00, 7.50, 0.00, 'completed', 'CF_6a49fee6f1311_1783234278', 'MTN Mobile Money', '2026-07-05 06:52:03', '2026-07-05 06:51:18', '82d84fe5-df53-46a6-b1ef-da2e5542a7d3', 'UGX', NULL),
(42, 10, NULL, 'Natasha Ahereza', '', '0784181635', 0, 1000.00, 7.50, 0.00, 'completed', 'CF_6a4b65461e59c_1783326022', 'MTN Mobile Money', '2026-07-06 08:21:05', '2026-07-06 08:20:22', '7d587b4f-9d43-4836-a33c-da2dfa63cc4f', 'UGX', NULL),
(43, 10, NULL, 'Sedrick Otolo', 'ot.sedrick@gmail.com', '0777676206', 0, 1000.00, 7.50, 0.00, 'completed', 'DON-1783360122-6a4bea7a2367a', 'MTN Mobile Money', '2026-07-07 06:29:58', '2026-07-06 17:48:42', NULL, 'UGX', '019f3b44-a39e-716e-95cc-869a9f9c9ee8'),
(44, 10, NULL, 'Odongo Brian', 'ot.sedrick@gmail.com', '0777676206', 0, 1000.00, 7.50, 0.00, 'completed', 'DON-1783361174-6a4bee96f25e8', 'MTN Mobile Money', '2026-07-07 08:28:36', '2026-07-06 18:06:14', NULL, 'UGX', '019f3bb1-3539-76f0-a281-2b075be57b24'),
(45, 10, NULL, 'odongo', 'ot.sedrick@gmail.com', '0777676206', 0, 1000.00, 7.50, 0.00, 'completed', 'DON-1783362060-6a4bf20c10c56', 'MTN Mobile Money', '2026-07-06 18:21:16', '2026-07-06 18:21:00', NULL, 'UGX', '019f38a9-8318-7723-a411-7bc5cf7fb027'),
(46, 10, NULL, 'opio', 'ot.sedrick@gmail.com', '0777676206', 0, 1000.00, 7.50, 0.00, 'failed', 'DON-1783362122-6a4bf24ab926e', 'MTN Mobile Money', NULL, '2026-07-06 18:22:02', NULL, 'UGX', '019f38aa-76e2-77a2-baf0-44e74740e3e6'),
(47, 10, NULL, 'Opio', 'sedricksedu2@gmail.com', '0777676206', 0, 1000.00, 7.50, 0.00, 'failed', 'DON-1783362357-6a4bf33547c15', 'MTN Mobile Money', NULL, '2026-07-06 18:25:57', NULL, 'UGX', '019f38ae-0aed-7582-ab39-ac7d46369317'),
(48, 10, NULL, 'Mukisa Brian', 'sedricksedu2@gmail.com', '0777676206', 0, 1000.00, 7.50, 0.00, 'completed', 'DON-1783363564-6a4bf7ecc2b55', 'MTN Mobile Money', '2026-07-06 18:46:13', '2026-07-06 18:46:04', NULL, 'UGX', '019f38c0-7864-7780-bc8c-b8d20feffea4'),
(49, 10, NULL, 'Obin Ivan', 'obinacademy@gmail.com', '256743573637', 0, 10000.00, 7.50, 0.00, 'failed', 'DON-1783364035-6a4bf9c370a3f', 'Airtel Money', NULL, '2026-07-06 18:53:55', NULL, 'UGX', '019f38c7-a758-7687-bb81-809cc8dfce6b'),
(50, 12, NULL, 'Anonymous', 'sedricksedu2@gmail.com', '0777676206', 1, 2000.00, 7.50, 0.00, 'completed', 'CF_6a4ca9dec7aea_1783409118', 'MTN Mobile Money', '2026-07-07 07:25:55', '2026-07-07 07:25:18', '9295144b-0e88-4fd3-8a68-da2c6a6b2acd', 'UGX', NULL),
(51, 12, 10, 'Anonymous', '', '392972444', 1, 3000.00, 7.50, 0.00, 'pending', 'CF_6a4caac687cee_1783409350', 'MTN Mobile Money', NULL, '2026-07-07 07:29:10', '84b4ef2a-94f5-4f09-a938-da2cadd74e7d', 'UGX', NULL),
(52, 14, NULL, 'Sedu Otolo', 'ot.sedrick@gmail.com', '0777676206', 0, 1000.00, 7.50, 0.00, 'completed', 'CF_6a4e50b7594db_1783517367', 'MTN Mobile Money', '2026-07-08 13:30:10', '2026-07-08 13:29:27', '47eddcfb-e485-41ef-995d-da2b494bef67', 'UGX', NULL),
(53, 14, NULL, 'Sedu Otolo', 'ot.sedrick@gmail.com', '0777676206', 0, 1000.00, 7.50, 0.00, 'pending', 'CF_6a4e52891707b_1783517833', 'MTN Mobile Money', NULL, '2026-07-08 13:37:13', '5c502dc4-b04d-439a-a6c1-da2b1cb85f88', 'UGX', NULL),
(54, 14, NULL, 'Anonymous', '', '256788332214', 1, 1000.00, 7.50, 0.00, 'pending', 'CF_6a4f25d242bd7_1783571922', 'MTN Mobile Money', NULL, '2026-07-09 04:38:42', 'f0020b81-17ff-469f-b512-da2ab4d80ea2', 'UGX', NULL);

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
(19, 11, 'donation', 'New Donation Received!', 'Sedu Otolo just donated 1,000 to your campaign \"Celebrating birthday with the vulnerable children\"', 'https://chamafunds.com/campaign-detail.php?id=14', 0, 0, 0, '2026-07-08 13:30:10');

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
(6, 'Sedrick Otolo', 'ot.sedrick@gmail.com', '256700000005', 'S3izE316', 'admin', 'Uganda', NULL, 1, 1, 0, '2026-07-08 13:38:11', '2026-07-04 07:15:37', '2026-07-08 13:38:11'),
(7, 'Obin Ivan', 'obinacademy@gmail.com', '256743573637', 'Password1*', 'campaigner', 'Uganda', NULL, 1, 0, 0, NULL, '2026-07-04 12:01:10', '2026-07-04 12:01:10'),
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
  MODIFY `notif_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `campaigns`
--
ALTER TABLE `campaigns`
  MODIFY `campaign_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `campaign_categories`
--
ALTER TABLE `campaign_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `campaign_images`
--
ALTER TABLE `campaign_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `countries`
--
ALTER TABLE `countries`
  MODIFY `country_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `donations`
--
ALTER TABLE `donations`
  MODIFY `donation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

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
