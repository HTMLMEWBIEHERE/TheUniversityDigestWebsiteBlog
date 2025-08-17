-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 17, 2025 at 01:43 PM
-- Server version: 11.4.5-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `udg_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `about_us`
--

CREATE TABLE `about_us` (
  `about_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `about_us`
--

INSERT INTO `about_us` (`about_id`, `title`, `description`, `image`, `purpose`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'The University Digest.', 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.', '68a1b2b9c749b.png', NULL, 2, '2025-05-01 10:15:41', '2025-08-17 10:45:13');

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `account_id` int(11) NOT NULL,
  `firstname` varchar(255) NOT NULL,
  `lastname` varchar(255) NOT NULL,
  `middlename` varchar(255) DEFAULT NULL,
  `user_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `role` enum('superadmin','subadmin','user') NOT NULL,
  `image` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `verification_code` varchar(255) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`account_id`, `firstname`, `lastname`, `middlename`, `user_name`, `email`, `password`, `google_id`, `role`, `image`, `created_at`, `updated_at`, `verification_code`, `is_verified`) VALUES
(2, 'Arthur', 'Morgan', 'Vander', 'The University Digest', 'udg@gmail.com', '$2y$10$uwPZzNmpEZ3hdxWP1oC3OuH8SRrVBYpRLvFcbKgkhXa/ZblguHjd6', NULL, 'superadmin', 'profile_68134a65e56c6.png', '2025-05-01 10:11:39', '2025-05-01 10:18:13', NULL, 0),
(3, 'Test', 'Demo', 'Subadmin', 'subadmin', 'subadmin@gmail.com', '$2y$10$H2S1VrdRfQzu.b9ZWF4g7.ykiQWf8QzzigTMUguGNZ.N026pYxh8S', NULL, 'subadmin', 'profile_6813b33bba294.jpg', '2025-05-01 10:38:10', '2025-05-01 17:45:31', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `announcement_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`announcement_id`, `title`, `content`, `image`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Website Coming Soon.', 'The University Digest Will be Launching its first website for pilot testing!', 'UD LOGO NEW BLACK.png', 2, '2025-05-01 10:31:03', '2025-05-01 10:31:03');

-- --------------------------------------------------------

--
-- Table structure for table `articles`
--

CREATE TABLE `articles` (
  `article_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `content` text NOT NULL,
  `image` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('draft','published') NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `carousel_images`
--

CREATE TABLE `carousel_images` (
  `id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `display_order` int(11) NOT NULL,
  `account_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `carousel_images`
--

INSERT INTO `carousel_images` (`id`, `image_url`, `display_order`, `account_id`, `created_at`, `updated_at`) VALUES
(4, 'carousel_6813511a26a18.jpg', 3, 2, '2025-05-01 10:46:50', '2025-05-01 10:46:50'),
(5, 'carousel_6813512324341.jpg', 2, 2, '2025-05-01 10:46:59', '2025-05-01 10:46:59'),
(6, 'carousel_6813512ae7d95.jpg', 1, 2, '2025-05-01 10:47:06', '2025-05-01 10:47:06');

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `category_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`category_id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Comics', '2025-05-01 10:18:28', '2025-05-01 10:18:28'),
(2, 'News', '2025-05-01 10:18:34', '2025-05-01 10:18:34'),
(3, 'Miscellaneous', '2025-05-01 10:18:39', '2025-05-01 10:18:39'),
(4, 'Editorial', '2025-05-01 10:18:41', '2025-05-01 10:18:41'),
(5, 'Horror Magazine', '2025-05-01 10:18:48', '2025-05-01 10:18:48'),
(6, 'Horror Tejido', '2025-05-01 10:18:53', '2025-05-01 10:18:53');

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `comment_id` int(11) NOT NULL,
  `post_id` int(11) DEFAULT NULL,
  `commented_by` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `comment` text NOT NULL,
  `commented_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `e_magazines`
--

CREATE TABLE `e_magazines` (
  `magazine_id` int(11) NOT NULL,
  `author` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `context` text NOT NULL,
  `link` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `category_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `e_magazines`
--

INSERT INTO `e_magazines` (`magazine_id`, `author`, `title`, `context`, `link`, `image`, `created_by`, `created_at`, `updated_at`, `category_id`) VALUES
(1, 'Cyrell', 'Magazine', 'Test', 'https://www.example.edu/journals/sample-academic-paper-2023.pdf', 'mag1.jpg', 2, '2025-05-01 10:50:52', '2025-05-01 10:50:52', 5),
(2, 'Test', 'Test', 'Test', 'https://www.example.edu/journals/sample-academic-paper-2023.pdf', 'mag2.jpg', 2, '2025-05-01 10:51:13', '2025-05-01 10:51:13', 5),
(3, 'Test', 'Test', 'Test', 'https://www.example.edu/journals/sample-academic-paper-2023.pdf', 'mag2.jpg', 2, '2025-05-01 10:51:30', '2025-05-01 10:51:30', 5),
(4, 'Test', 'Test', 'Test', 'https://www.example.edu/journals/sample-academic-paper-2023.pdf', 'mag1.jpg', 2, '2025-05-01 10:51:49', '2025-05-01 10:51:49', 5),
(5, 'Test', 'Test', 'Test', 'https://www.example.edu/journals/sample-academic-paper-2023.pdf', 'mag2.jpg', 2, '2025-05-01 10:52:15', '2025-05-01 10:52:15', 5),
(6, 'Test', 'Test', 'Test', 'https://www.example.edu/journals/sample-academic-paper-2023.pdf', 'mag1.jpg', 2, '2025-05-01 10:52:33', '2025-05-01 10:52:33', 5);

-- --------------------------------------------------------

--
-- Table structure for table `footer_info`
--

CREATE TABLE `footer_info` (
  `footer_id` int(11) NOT NULL,
  `address` text NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `copyright_text` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `footer_info`
--

INSERT INTO `footer_info` (`footer_id`, `address`, `email`, `phone`, `copyright_text`, `created_by`, `created_at`, `updated_at`) VALUES
(0, 'Campus A', 'TheUniversityDigest@gmail.com', '09090934134', '© 2025 Your Website. All rights reserved.', 2, '2025-05-01 10:11:56', '2025-05-01 10:13:50');

-- --------------------------------------------------------

--
-- Table structure for table `footer_socials`
--

CREATE TABLE `footer_socials` (
  `social_id` int(11) NOT NULL,
  `platform` varchar(50) NOT NULL,
  `url` varchar(255) NOT NULL,
  `icon_class` varchar(100) DEFAULT NULL,
  `footer_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `likes`
--

CREATE TABLE `likes` (
  `like_id` int(11) NOT NULL,
  `account_id` int(11) DEFAULT NULL,
  `post_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `likes`
--

INSERT INTO `likes` (`like_id`, `account_id`, `post_id`, `created_at`) VALUES
(9, 3, 1, '2025-05-01 17:59:59'),
(26, 2, 1, '2025-08-02 18:51:29'),
(30, 2, 4, '2025-08-17 09:39:00');

-- --------------------------------------------------------

--
-- Table structure for table `magazine_views`
--

CREATE TABLE `magazine_views` (
  `view_id` int(11) NOT NULL,
  `magazine_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `viewed_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `organizational_chart`
--

CREATE TABLE `organizational_chart` (
  `org_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `position` varchar(255) NOT NULL,
  `date_appointed` date NOT NULL,
  `date_ended` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT 0,
  `category_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `organizational_chart`
--

INSERT INTO `organizational_chart` (`org_id`, `name`, `image`, `position`, `date_appointed`, `date_ended`, `created_at`, `updated_at`, `is_deleted`, `category_id`, `created_by`) VALUES
(1, 'TEST', NULL, 'Member', '2025-05-02', '2025-05-02', '2025-05-01 18:27:39', '2025-05-02 02:55:39', 1, 2, 2),
(2, 'Cartoonist', NULL, 'Member', '2025-05-02', '2025-05-02', '2025-05-02 02:52:14', '2025-05-02 02:53:07', 1, 3, 2),
(3, 'Jane Doe', 'uploads/members/68143421358a8.jpg', 'President', '2025-05-02', NULL, '2025-05-02 02:55:29', '2025-05-02 02:55:29', 0, 4, 2),
(4, 'Cartoonist 1', NULL, 'Member', '2024-09-09', NULL, '2025-05-02 02:56:54', '2025-05-02 02:56:54', 0, 5, 2),
(5, 'Cartoonist 2', 'uploads/members/6814348db1168.jpg', 'Member', '2024-09-08', NULL, '2025-05-02 02:57:17', '2025-05-02 02:57:17', 0, 5, 2);

-- --------------------------------------------------------

--
-- Table structure for table `org_categories`
--

CREATE TABLE `org_categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `org_categories`
--

INSERT INTO `org_categories` (`category_id`, `category_name`, `created_at`, `updated_at`) VALUES
(1, 'PIO', '2025-05-01 18:24:41', '2025-05-01 18:24:41'),
(2, 'PRESIDENT', '2025-05-01 18:24:51', '2025-05-01 18:24:51'),
(3, 'INSTRUCTOR', '2025-05-01 18:25:00', '2025-05-01 18:25:00'),
(4, 'BOARD', '2025-05-02 02:53:38', '2025-05-02 02:53:38'),
(5, 'Cartoonist', '2025-05-02 02:56:30', '2025-05-02 02:56:30');

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `post_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `content` text NOT NULL,
  `image` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('draft','published') NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`post_id`, `title`, `content`, `image`, `created_at`, `updated_at`, `status`, `created_by`, `category_id`) VALUES
(1, 'Test Post!!', 'This is an experimental context.', 'c3.jpg', '2025-05-01 10:19:18', '2025-05-14 21:01:06', 'published', 2, 1),
(4, 'Test Post Mobile', 'Mobile Upload', 'images (4).jpeg', '2025-05-01 17:59:30', '2025-05-01 17:59:30', 'published', 3, 2),
(9, 'Draft', 'Draft', '', '2025-05-14 21:18:19', '2025-05-14 21:18:19', 'draft', 3, 2);

-- --------------------------------------------------------

--
-- Table structure for table `purpose_card`
--

CREATE TABLE `purpose_card` (
  `purpose_id` int(11) NOT NULL,
  `purpose_text` text NOT NULL,
  `school_name` varchar(255) NOT NULL,
  `campus_name` varchar(255) NOT NULL,
  `established_year` varchar(50) NOT NULL,
  `operating_hours` varchar(100) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purpose_card`
--

INSERT INTO `purpose_card` (`purpose_id`, `purpose_text`, `school_name`, `campus_name`, `established_year`, `operating_hours`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Lorem ipsum dolor sit amet. 33 minima culpa ut odit itaque At veritatis enim. Eum voluptas facilis aut aliquid vero sed minima culpa sit omnis modi v\'\'\'\'\'el magni adipisci est nostrum eveniet. Ab facere veritatis ut nisi magni est totam quia sit temporibus ipsum sed accusantium voluptas et totam deserunt aut laudantium rerum. Qui aperiam quasi non tempora facilis et iste sint.\r\n\r\nHic provident suscipit id delectus tempore sed explicabo mollitia ea veritatis eligendi aut ipsa doloribus aut amet quam. 33 reiciendis illum quo consequatur debitis et quibusdam magni et minima quos et accusantium dignissimos id accusantium nobis qui sapiente reprehenderit. Non asperiores dolores ut eligendi voluptate est eius exercitationem vel fugiat nobis ut accusamus galisum eos magnam totam sed sint numquam?', 'Western Mindanao State University', 'Campus A', '1905-2023', 'Monday - Sunday', NULL, '2025-05-01 10:48:52', '2025-05-02 01:30:56');

-- --------------------------------------------------------

--
-- Table structure for table `site_settings`
--

CREATE TABLE `site_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_name` varchar(50) NOT NULL,
  `setting_value` text NOT NULL,
  `setting_description` text DEFAULT NULL,
  `setting_group` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `site_settings`
--

INSERT INTO `site_settings` (`setting_id`, `setting_name`, `setting_value`, `setting_description`, `setting_group`, `created_at`, `updated_at`) VALUES
(1, 'email_smtp_host', 'smtp.gmail.com', 'SMTP Server Host', 'email', '2025-05-14 22:55:59', '2025-05-14 22:55:59'),
(2, 'email_smtp_port', '587', 'SMTP Server Port', 'email', '2025-05-14 22:55:59', '2025-05-14 22:55:59'),
(3, 'email_username', 'cyrellfelix@gmail.com', 'Email Username/Address', 'email', '2025-05-14 22:55:59', '2025-05-14 22:55:59'),
(4, 'email_password', 'hzit uknk vjxf eesj', 'Email Password (App Password for Gmail)', 'email', '2025-05-14 22:55:59', '2025-05-14 22:55:59'),
(5, 'email_sender_name', 'The University Digest', 'Email Sender Name', 'email', '2025-05-14 22:55:59', '2025-05-14 22:55:59'),
(6, 'email_encryption', 'tls', 'Email Encryption (tls/ssl)', 'email', '2025-05-14 22:55:59', '2025-05-14 22:55:59'),
(7, 'google_client_id', '502512356932-b08caquk2r3lsqtotrl5u82surgi84sq.apps.googleusercontent.com', 'Google OAuth Client ID', 'google', '2025-05-14 22:55:59', '2025-05-14 22:55:59'),
(8, 'google_client_secret', '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', 'Google OAuth Client Secret', 'google', '2025-05-14 22:55:59', '2025-05-14 22:55:59'),
(9, 'site_name', 'The University Digest', 'Website Name', 'general', '2025-05-14 22:55:59', '2025-05-14 22:55:59'),
(10, 'verification_expiry_hours', '24', 'Email Verification Link Expiry (Hours)', 'email', '2025-05-14 22:55:59', '2025-05-14 22:55:59');

-- --------------------------------------------------------

--
-- Table structure for table `tejido`
--

CREATE TABLE `tejido` (
  `tejido_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `img` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('draft','published') NOT NULL,
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tejido`
--

INSERT INTO `tejido` (`tejido_id`, `title`, `description`, `category_id`, `img`, `created_at`, `updated_at`, `status`, `created_by`) VALUES
(1, 'The Last of Us Part 1', 'Lorem ipsum dolor sit amet. 33 minima culpa ut odit itaque At veritatis enim. Eum voluptas facilis aut aliquid vero sed minima culpa sit omnis modi vel magni adipisci est nostrum eveniet. Ab facere veritatis ut nisi magni est totam quia sit temporibus ipsum sed accusantium voluptas et totam deserunt aut laudantium rerum. Qui aperiam quasi non tempora facilis et iste sint.\r\n\r\nHic provident suscipit id delectus tempore sed explicabo mollitia ea veritatis eligendi aut ipsa doloribus aut amet quam. 33 reiciendis illum quo consequatur debitis et quibusdam magni et minima quos et accusantium dignissimos id accusantium nobis qui sapiente reprehenderit. Non asperiores dolores ut eligendi voluptate est eius exercitationem vel fugiat nobis ut accusamus galisum eos magnam totam sed sint numquam?', 6, '68134fc73650f.jpg', '2025-05-01 10:41:11', '2025-05-01 10:41:11', 'published', 2),
(2, 'Program', 'Test', 6, '68134fd8548c6.jpg', '2025-05-01 10:41:28', '2025-05-01 10:41:28', 'published', 2),
(3, 'The Last Car Bender', 'Test Desc', 6, '68134feb4bc83.jpg', '2025-05-01 10:41:47', '2025-05-01 10:41:47', 'published', 2),
(4, 'Avengers!', 'Lorem ipsum dolor sit amet. 33 minima culpa ut odit itaque At veritatis enim. Eum voluptas facilis aut aliquid vero sed minima culpa sit omnis modi vel magni adipisci est nostrum eveniet. Ab facere veritatis ut nisi magni est totam quia sit temporibus ipsum sed accusantium voluptas et totam deserunt aut laudantium rerum. Qui aperiam quasi non tempora facilis et iste sint.\r\n\r\nHic provident suscipit id delectus tempore sed explicabo mollitia ea veritatis eligendi aut ipsa doloribus aut amet quam. 33 reiciendis illum quo consequatur debitis et quibusdam magni et minima quos et accusantium dignissimos id accusantium nobis qui sapiente reprehenderit. Non asperiores dolores ut eligendi voluptate est eius exercitationem vel fugiat nobis ut accusamus galisum eos magnam totam sed sint numquam?\r\n\r\nLorem ipsum dolor sit amet. 33 minima culpa ut odit itaque At veritatis enim. Eum voluptas facilis aut aliquid vero sed minima culpa sit omnis modi vel magni adipisci est nostrum eveniet. Ab facere veritatis ut nisi magni est totam quia sit temporibus ipsum sed accusantium voluptas et totam deserunt aut laudantium rerum. Qui aperiam quasi non tempora facilis et iste sint.\r\n\r\nHic provident suscipit id delectus tempore sed explicabo mollitia ea veritatis eligendi aut ipsa doloribus aut amet quam. 33 reiciendis illum quo consequatur debitis et quibusdam magni et minima quos et accusantium dignissimos id accusantium nobis qui sapiente reprehenderit. Non asperiores dolores ut eligendi voluptate est eius exercitationem vel fugiat nobis ut accusamus galisum eos magnam totam sed sint numquam?\r\n\r\nLorem ipsum dolor sit amet. 33 minima culpa ut odit itaque At veritatis enim. Eum voluptas facilis aut aliquid vero sed minima culpa sit omnis modi vel magni adipisci est nostrum eveniet. Ab facere veritatis ut nisi magni est totam quia sit temporibus ipsum sed accusantium voluptas et totam deserunt aut laudantium rerum. Qui aperiam quasi non tempora facilis et iste sint.\r\n\r\nHic provident suscipit id delectus tempore sed explicabo mollitia ea veritatis eligendi aut ipsa doloribus aut amet quam. 33 reiciendis illum quo consequatur debitis et quibusdam magni et minima quos et accusantium dignissimos id accusantium nobis qui sapiente reprehenderit. Non asperiores dolores ut eligendi voluptate est eius exercitationem vel fugiat nobis ut accusamus galisum eos magnam totam sed sint numquam?', 6, '681350043a9b1.jpg', '2025-05-01 10:42:12', '2025-05-01 14:29:42', 'published', 2),
(5, 'Company Bad', 'Enter the Company Bad', 6, '6813501b69293.jpg', '2025-05-01 10:42:35', '2025-05-01 10:42:35', 'published', 2),
(6, 'Harry Potters', 'Sigma Boy.', 6, '68135037132bc.jpg', '2025-05-01 10:43:03', '2025-05-01 10:43:03', 'published', 2),
(7, 'The Last of Us Part 2', 'Horror Action Packed', 6, '6813505a22ac7.jpg', '2025-05-01 10:43:38', '2025-05-01 10:43:38', 'published', 2),
(8, 'Test PIC', 'Lorem ipsum dolor sit amet. 33 minima culpa ut odit itaque At veritatis enim. Eum voluptas facilis aut aliquid vero sed minima culpa sit omnis modi vel magni adipisci est nostrum eveniet. Ab facere veritatis ut nisi magni est totam quia sit temporibus ipsum sed accusantium voluptas et totam deserunt aut laudantium rerum. Qui aperiam quasi non tempora facilis et iste sint.\r\n\r\nHic provident suscipit id delectus tempore sed explicabo mollitia ea veritatis eligendi aut ipsa doloribus aut amet quam. 33 reiciendis illum quo consequatur debitis et quibusdam magni et minima quos et accusantium dignissimos id accusantium nobis qui sapiente reprehenderit. Non asperiores dolores ut eligendi voluptate est eius exercitationem vel fugiat nobis ut accusamus galisum eos magnam totam sed sint numquam?Lorem ipsum dolor sit amet. 33 minima culpa ut odit itaque At veritatis enim. Eum voluptas facilis aut aliquid vero sed minima culpa sit omnis modi vel magni adipisci est nostrum eveniet. Ab facere veritatis ut nisi magni est totam quia sit temporibus ipsum sed accusantium voluptas et totam deserunt aut laudantium rerum. Qui aperiam quasi non tempora facilis et iste sint.\r\n\r\nHic provident suscipit id delectus tempore sed explicabo mollitia ea veritatis eligendi aut ipsa doloribus aut amet quam. 33 reiciendis illum quo consequatur debitis et quibusdam magni et minima quos et accusantium dignissimos id accusantium nobis qui sapiente reprehenderit. Non asperiores dolores ut eligendi voluptate est eius exercitationem vel fugiat nobis ut accusamus galisum eos magnam totam sed sint numquam?Lorem ipsum dolor sit amet. 33 minima culpa ut odit itaque At veritatis enim. Eum voluptas facilis aut aliquid vero sed minima culpa sit omnis modi vel magni adipisci est nostrum eveniet. Ab facere veritatis ut nisi magni est totam quia sit temporibus ipsum sed accusantium voluptas et totam deserunt aut laudantium rerum. Qui aperiam quasi non tempora facilis et iste sint.\r\n\r\nHic provident suscipit id delectus tempore sed explicabo mollitia ea veritatis eligendi aut ipsa doloribus aut amet quam. 33 reiciendis illum quo consequatur debitis et quibusdam magni et minima quos et accusantium dignissimos id accusantium nobis qui sapiente reprehenderit. Non asperiores dolores ut eligendi voluptate est eius exercitationem vel fugiat nobis ut accusamus galisum eos magnam totam sed sint numquam?Lorem ipsum dolor sit amet. 33 minima culpa ut odit itaque At veritatis enim. Eum voluptas facilis aut aliquid vero sed minima culpa sit omnis modi vel magni adipisci est nostrum eveniet. Ab facere veritatis ut nisi magni est totam quia sit temporibus ipsum sed accusantium voluptas et totam deserunt aut laudantium rerum. Qui aperiam quasi non tempora facilis et iste sint.\r\n\r\nHic provident suscipit id delectus tempore sed explicabo mollitia ea veritatis eligendi aut ipsa doloribus aut amet quam. 33 reiciendis illum quo consequatur debitis et quibusdam magni et minima quos et accusantium dignissimos id accusantium nobis qui sapiente reprehenderit. Non asperiores dolores ut eligendi voluptate est eius exercitationem vel fugiat nobis ut accusamus galisum eos magnam totam sed sint numquam?Lorem ipsum dolor sit amet. 33 minima culpa ut odit itaque At veritatis enim. Eum voluptas facilis aut aliquid vero sed minima culpa sit omnis modi vel magni adipisci est nostrum eveniet. Ab facere veritatis ut nisi magni est totam quia sit temporibus ipsum sed accusantium voluptas et totam deserunt aut laudantium rerum. Qui aperiam quasi non tempora facilis et iste sint.\r\n\r\nHic provident suscipit id delectus tempore sed explicabo mollitia ea veritatis eligendi aut ipsa doloribus aut amet quam. 33 reiciendis illum quo consequatur debitis et quibusdam magni et minima quos et accusantium dignissimos id accusantium nobis qui sapiente reprehenderit. Non asperiores dolores ut eligendi voluptate est eius exercitationem vel fugiat nobis ut accusamus galisum eos magnam totam sed sint numquam?', 6, '6813880f465f9.jpg', '2025-05-01 14:41:19', '2025-05-01 14:41:19', 'published', 2),
(9, 'TEST PORTRAIT', 'GAGAGAGAGAGALorem ipsum dolor sit amet. 33 minima culpa ut odit itaque At veritatis enim. Eum voluptas facilis aut aliquid vero sed minima culpa sit omnis modi vel magni adipisci est nostrum eveniet. Ab facere veritatis ut nisi magni est totam quia sit temporibus ipsum sed accusantium voluptas et totam deserunt aut laudantium rerum. Qui aperiam quasi non tempora facilis et iste sint.\r\n\r\nHic provident suscipit id delectus tempore sed explicabo mollitia ea veritatis eligendi aut ipsa doloribus aut amet quam. 33 reiciendis illum quo consequatur debitis et quibusdam magni et minima quos et accusantium dignissimos id accusantium nobis qui sapiente reprehenderit. Non asperiores dolores ut eligendi voluptate est eius exercitationem vel fugiat nobis ut accusamus galisum eos magnam totam sed sint numquam?Lorem ipsum dolor sit amet. 33 minima culpa ut odit itaque At veritatis enim. Eum voluptas facilis aut aliquid vero sed minima culpa sit omnis modi vel magni adipisci est nostrum eveniet. Ab facere veritatis ut nisi magni est totam quia sit temporibus ipsum sed accusantium voluptas et totam deserunt aut laudantium rerum. Qui aperiam quasi non tempora facilis et iste sint.\r\n\r\nHic provident suscipit id delectus tempore sed explicabo mollitia ea veritatis eligendi aut ipsa doloribus aut amet quam. 33 reiciendis illum quo consequatur debitis et quibusdam magni et minima quos et accusantium dignissimos id accusantium nobis qui sapiente reprehenderit. Non asperiores dolores ut eligendi voluptate est eius exercitationem vel fugiat nobis ut accusamus galisum eos magnam totam sed sint numquam?Lorem ipsum dolor sit amet. 33 minima culpa ut odit itaque At veritatis enim. Eum voluptas facilis aut aliquid vero sed minima culpa sit omnis modi vel magni adipisci est nostrum eveniet. Ab facere veritatis ut nisi magni est totam quia sit temporibus ipsum sed accusantium voluptas et totam deserunt aut laudantium rerum. Qui aperiam quasi non tempora facilis et iste sint.\r\n\r\nHic provident suscipit id delectus tempore sed explicabo mollitia ea veritatis eligendi aut ipsa doloribus aut amet quam. 33 reiciendis illum quo consequatur debitis et quibusdam magni et minima quos et accusantium dignissimos id accusantium nobis qui sapiente reprehenderit. Non asperiores dolores ut eligendi voluptate est eius exercitationem vel fugiat nobis ut accusamus galisum eos magnam totam sed sint numquam?', 6, '681388269c98d.jpg', '2025-05-01 14:41:42', '2025-05-01 14:41:42', 'published', 2),
(10, 'Tejido (subadmin)', 'A subadminposted this!', 6, 'tejido_6813b726bdc54.jpg', '2025-05-01 18:02:14', '2025-05-01 18:02:14', 'published', 3);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `about_us`
--
ALTER TABLE `about_us`
  ADD PRIMARY KEY (`about_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`account_id`),
  ADD UNIQUE KEY `user_name` (`user_name`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`announcement_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `articles`
--
ALTER TABLE `articles`
  ADD PRIMARY KEY (`article_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `carousel_images`
--
ALTER TABLE `carousel_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `account_id` (`account_id`);

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `commented_by` (`commented_by`);

--
-- Indexes for table `e_magazines`
--
ALTER TABLE `e_magazines`
  ADD PRIMARY KEY (`magazine_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `likes`
--
ALTER TABLE `likes`
  ADD PRIMARY KEY (`like_id`),
  ADD UNIQUE KEY `unique_like` (`account_id`,`post_id`),
  ADD KEY `post_id` (`post_id`);

--
-- Indexes for table `magazine_views`
--
ALTER TABLE `magazine_views`
  ADD PRIMARY KEY (`view_id`),
  ADD KEY `magazine_id` (`magazine_id`),
  ADD KEY `account_id` (`account_id`);

--
-- Indexes for table `organizational_chart`
--
ALTER TABLE `organizational_chart`
  ADD PRIMARY KEY (`org_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `org_categories`
--
ALTER TABLE `org_categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`post_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `purpose_card`
--
ALTER TABLE `purpose_card`
  ADD PRIMARY KEY (`purpose_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_name` (`setting_name`);

--
-- Indexes for table `tejido`
--
ALTER TABLE `tejido`
  ADD PRIMARY KEY (`tejido_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `created_by` (`created_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `about_us`
--
ALTER TABLE `about_us`
  MODIFY `about_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `account_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `articles`
--
ALTER TABLE `articles`
  MODIFY `article_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `carousel_images`
--
ALTER TABLE `carousel_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- AUTO_INCREMENT for table `e_magazines`
--
ALTER TABLE `e_magazines`
  MODIFY `magazine_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `likes`
--
ALTER TABLE `likes`
  MODIFY `like_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `magazine_views`
--
ALTER TABLE `magazine_views`
  MODIFY `view_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `organizational_chart`
--
ALTER TABLE `organizational_chart`
  MODIFY `org_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `org_categories`
--
ALTER TABLE `org_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `purpose_card`
--
ALTER TABLE `purpose_card`
  MODIFY `purpose_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `site_settings`
--
ALTER TABLE `site_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tejido`
--
ALTER TABLE `tejido`
  MODIFY `tejido_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `about_us`
--
ALTER TABLE `about_us`
  ADD CONSTRAINT `about_us_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `accounts` (`account_id`) ON DELETE SET NULL;

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `accounts` (`account_id`) ON DELETE SET NULL;

--
-- Constraints for table `articles`
--
ALTER TABLE `articles`
  ADD CONSTRAINT `articles_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `articles_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `accounts` (`account_id`) ON DELETE SET NULL;

--
-- Constraints for table `carousel_images`
--
ALTER TABLE `carousel_images`
  ADD CONSTRAINT `carousel_images_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`) ON DELETE CASCADE;

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`commented_by`) REFERENCES `accounts` (`account_id`) ON DELETE SET NULL;

--
-- Constraints for table `e_magazines`
--
ALTER TABLE `e_magazines`
  ADD CONSTRAINT `e_magazines_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `accounts` (`account_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `e_magazines_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`) ON DELETE SET NULL;

--
-- Constraints for table `likes`
--
ALTER TABLE `likes`
  ADD CONSTRAINT `likes_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `likes_ibfk_2` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE;

--
-- Constraints for table `magazine_views`
--
ALTER TABLE `magazine_views`
  ADD CONSTRAINT `magazine_views_ibfk_1` FOREIGN KEY (`magazine_id`) REFERENCES `e_magazines` (`magazine_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `magazine_views_ibfk_2` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`) ON DELETE CASCADE;

--
-- Constraints for table `organizational_chart`
--
ALTER TABLE `organizational_chart`
  ADD CONSTRAINT `organizational_chart_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `org_categories` (`category_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `organizational_chart_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `accounts` (`account_id`) ON DELETE SET NULL;

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `accounts` (`account_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `posts_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`) ON DELETE SET NULL;

--
-- Constraints for table `purpose_card`
--
ALTER TABLE `purpose_card`
  ADD CONSTRAINT `purpose_card_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `accounts` (`account_id`) ON DELETE SET NULL;

--
-- Constraints for table `tejido`
--
ALTER TABLE `tejido`
  ADD CONSTRAINT `tejido_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tejido_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `accounts` (`account_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
