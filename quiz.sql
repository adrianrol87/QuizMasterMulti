-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jun 17, 2022 at 08:02 AM
-- Server version: 10.4.21-MariaDB
-- PHP Version: 7.4.24

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `quiz_707`
--

-- --------------------------------------------------------

--
-- Table structure for table `authenticate`
--

CREATE TABLE `authenticate` (
  `auth_username` varchar(12) NOT NULL,
  `auth_pass` varchar(50) NOT NULL,
  `role` varchar(32) NOT NULL,
  `app_passcode` varchar(16) NOT NULL,
  `android_key` text NOT NULL,
  `status` int(11) NOT NULL DEFAULT 0,
  `created` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `authenticate`
--

INSERT INTO `authenticate` (`auth_username`, `auth_pass`, `role`, `app_passcode`, `android_key`, `status`, `created`) VALUES
('admin', '0192023a7bbd73250516f069df18b500', 'admin', '', '', 1, '2021-06-27 04:53:24');

-- --------------------------------------------------------

--
-- Table structure for table `battle_questions`
--

CREATE TABLE `battle_questions` (
  `id` int(11) NOT NULL,
  `match_id` varchar(128) NOT NULL,
  `questions` text CHARACTER SET utf8 NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `battle_statistics`
--

CREATE TABLE `battle_statistics` (
  `id` int(11) NOT NULL,
  `user_id1` int(11) NOT NULL,
  `user_id2` int(11) NOT NULL,
  `is_drawn` tinyint(4) NOT NULL,
  `winner_id` int(11) NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `id` int(11) NOT NULL,
  `language_id` tinyint(4) NOT NULL DEFAULT 0,
  `category_name` varchar(250) CHARACTER SET utf8 NOT NULL,
  `type` tinyint(4) NOT NULL,
  `image` longtext CHARACTER SET utf8 DEFAULT NULL,
  `row_order` varchar(30) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `contest`
--

CREATE TABLE `contest` (
  `id` int(11) NOT NULL,
  `name` varchar(256) CHARACTER SET utf8 NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `description` varchar(1024) CHARACTER SET utf8 NOT NULL,
  `image` varchar(512) NOT NULL,
  `entry` int(11) NOT NULL,
  `prize_status` int(11) NOT NULL,
  `date_created` datetime NOT NULL,
  `status` int(11) NOT NULL COMMENT '0=deactive,1=active'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `contest_leaderboard`
--

CREATE TABLE `contest_leaderboard` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `contest_id` int(11) NOT NULL,
  `questions_attended` int(11) NOT NULL,
  `correct_answers` int(11) NOT NULL,
  `score` double NOT NULL,
  `last_modified` datetime NOT NULL,
  `date_created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `contest_prize`
--

CREATE TABLE `contest_prize` (
  `id` int(11) NOT NULL,
  `contest_id` int(11) NOT NULL,
  `top_winner` int(11) NOT NULL,
  `points` double NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `contest_questions`
--

CREATE TABLE `contest_questions` (
  `id` int(11) NOT NULL,
  `contest_id` int(11) NOT NULL,
  `image` varchar(256) NOT NULL,
  `question` text CHARACTER SET utf8 NOT NULL,
  `question_type` int(11) NOT NULL COMMENT '1= normal, 2= true/false',
  `optiona` text CHARACTER SET utf8 NOT NULL,
  `optionb` text CHARACTER SET utf8 NOT NULL,
  `optionc` text CHARACTER SET utf8 NOT NULL,
  `optiond` text CHARACTER SET utf8 NOT NULL,
  `optione` text CHARACTER SET utf8 NOT NULL,
  `answer` varchar(12) CHARACTER SET utf8 NOT NULL,
  `note` text CHARACTER SET utf8 NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `daily_leaderboard`
--

CREATE TABLE `daily_leaderboard` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `score` int(11) NOT NULL,
  `last_updated` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  `date_created` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `daily_quiz`
--

CREATE TABLE `daily_quiz` (
  `id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `questions_id` text NOT NULL,
  `date_published` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `daily_quiz_user`
--

CREATE TABLE `daily_quiz_user` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- --------------------------------------------------------

--
-- Table structure for table `languages`
--

CREATE TABLE `languages` (
  `id` int(11) NOT NULL,
  `language` varchar(64) CHARACTER SET utf8 NOT NULL,
  `status` tinyint(4) NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `monthly_leaderboard`
--

CREATE TABLE `monthly_leaderboard` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `score` int(11) NOT NULL,
  `last_updated` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  `date_created` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `title` varchar(128) CHARACTER SET utf8 NOT NULL,
  `message` varchar(512) CHARACTER SET utf8 NOT NULL,
  `users` varchar(8) NOT NULL DEFAULT 'all',
  `type` varchar(12) NOT NULL,
  `type_id` int(11) NOT NULL,
  `image` varchar(128) NOT NULL,
  `date_sent` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `question`
--

CREATE TABLE `question` (
  `id` int(11) NOT NULL,
  `category` int(11) NOT NULL,
  `subcategory` int(11) NOT NULL,
  `language_id` tinyint(4) NOT NULL DEFAULT 0,
  `image` varchar(512) CHARACTER SET utf8 NOT NULL,
  `question` text CHARACTER SET utf8 NOT NULL,
  `question_type` int(11) NOT NULL COMMENT '1= normal, 2= true/false',
  `optiona` text CHARACTER SET utf8 NOT NULL,
  `optionb` text CHARACTER SET utf8 NOT NULL,
  `optionc` text CHARACTER SET utf8 NOT NULL,
  `optiond` text CHARACTER SET utf8 NOT NULL,
  `optione` text CHARACTER SET utf8 DEFAULT NULL,
  `answer` text CHARACTER SET utf8 NOT NULL,
  `level` int(11) NOT NULL,
  `note` text CHARACTER SET utf8 NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `question_reports`
--

CREATE TABLE `question_reports` (
  `id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` varchar(512) CHARACTER SET utf8 NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `type` varchar(32) NOT NULL,
  `message` text CHARACTER SET utf8 NOT NULL,
  `status` int(4) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `type`, `message`, `status`) VALUES
(1, 'privacy_policy', '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Fusce aliquet vulputate tincidunt. Etiam pharetra auctor massa in aliquet. Curabitur a elit ut mauris ullamcorper pulvinar. Phasellus maximus tellus dui, id iaculis lectus fermentum nec. Aliquam odio erat, porttitor vel luctus id, sollicitudin non tortor. Vestibulum neque est, semper vel dui eu, varius aliquam ante. Donec mollis magna sed metus vestibulum consequat. Ut aliquam vulputate ligula, non cursus nibh gravida vitae. Phasellus tellus tellus, accumsan eget tortor laoreet, molestie mollis nisl. Donec molestie semper nibh in efficitur. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Suspendisse dignissim, ex ac iaculis pulvinar, sem nisi scelerisque justo, pulvinar pellentesque ex mi bibendum urna. Quisque ac commodo justo. Integer ut dignissim lectus. Donec a elementum dolor. Vivamus eu nunc vitae mi iaculis imperdiet.</p>\r\n<p>Ut ullamcorper risus leo, sit amet dictum magna consequat id. Cras eros leo, ullamcorper a vehicula sed, suscipit nec mi. Donec facilisis, urna eu placerat condimentum, nisi quam tincidunt ex, ac auctor nisi metus vel tellus. Curabitur aliquam felis ut ex facilisis eleifend. Mauris dapibus consectetur eros, id venenatis risus pretium eget. Proin sit amet egestas odio. Vivamus interdum, enim nec egestas vulputate, purus dui convallis velit, eu elementum massa nibh at nulla. Morbi ullamcorper accumsan ipsum, id pulvinar purus ultrices vel. In vehicula ultrices diam sit amet dapibus. Integer arcu diam, luctus nec urna eu, iaculis tempor arcu. Sed sit amet pulvinar arcu, eget consequat ante. Curabitur nunc ante, venenatis at tellus eu, euismod vulputate lectus. Vivamus finibus arcu nulla.</p>\r\n<p>Proin mollis ullamcorper nibh et viverra. Nullam iaculis leo et erat commodo pretium. Phasellus ut sapien vel dui mattis vulputate. Duis non volutpat elit. Nulla vitae mi metus. Donec euismod vulputate risus, ac maximus erat maximus quis. Nullam molestie eget orci ac accumsan. Proin tortor lectus, ultrices id tortor vel, mollis faucibus enim. Proin augue ante, mollis id libero eget, ultrices auctor augue. Nam lacinia dapibus dui, nec bibendum lacus pharetra sit amet. Maecenas ut diam urna. Sed consectetur ipsum nec tempus facilisis. Proin gravida est lectus, vel sagittis lorem porta non. Maecenas id tempus ex. Integer ullamcorper, lacus sed interdum imperdiet, purus tellus dapibus ipsum, sed auctor dui dolor at lorem.</p>\r\n<p>Sed non placerat erat. Nullam diam purus, cursus vitae sapien et, ultrices molestie eros. Aliquam eleifend sem libero, et facilisis tellus sagittis id. Aliquam faucibus, enim ut fermentum aliquam, arcu nunc mollis justo, in pulvinar ante nisl nec ex. Morbi vel eros non tellus tincidunt sagittis. Sed massa felis, finibus non placerat a, pharetra sit amet massa. Proin ornare magna vitae risus accumsan, vel sagittis nisl finibus. Sed sit amet finibus magna. Proin fringilla risus sit amet velit auctor, sit amet faucibus tellus scelerisque.</p>', 1),
(4, 'about_us', '<p>Welcome to <strong>Quiz Online</strong></p>\r\n<p>Best Android app for online quiz is here. We guarantee you the best quizing experience for your dedicated users.</p>\r\n<p> </p>\r\n<p>Made with <3 by <a href=\"https://wrteam.in\"><strong>WRTeam</strong></a></p>', 1),
(2, 'update_terms', '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Fusce aliquet vulputate tincidunt. Etiam pharetra auctor massa in aliquet. Curabitur a elit ut mauris ullamcorper pulvinar. Phasellus maximus tellus dui, id iaculis lectus fermentum nec. Aliquam odio erat, porttitor vel luctus id, sollicitudin non tortor. Vestibulum neque est, semper vel dui eu, varius aliquam ante. Donec mollis magna sed metus vestibulum consequat. Ut aliquam vulputate ligula, non cursus nibh gravida vitae. Phasellus tellus tellus, accumsan eget tortor laoreet, molestie mollis nisl. Donec molestie semper nibh in efficitur. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Suspendisse dignissim, ex ac iaculis pulvinar, sem nisi scelerisque justo, pulvinar pellentesque ex mi bibendum urna. Quisque ac commodo justo. Integer ut dignissim lectus. Donec a elementum dolor. Vivamus eu nunc vitae mi iaculis imperdiet.</p>\r\n<p>Ut ullamcorper risus leo, sit amet dictum magna consequat id. Cras eros leo, ullamcorper a vehicula sed, suscipit nec mi. Donec facilisis, urna eu placerat condimentum, nisi quam tincidunt ex, ac auctor nisi metus vel tellus. Curabitur aliquam felis ut ex facilisis eleifend. Mauris dapibus consectetur eros, id venenatis risus pretium eget. Proin sit amet egestas odio. Vivamus interdum, enim nec egestas vulputate, purus dui convallis velit, eu elementum massa nibh at nulla. Morbi ullamcorper accumsan ipsum, id pulvinar purus ultrices vel. In vehicula ultrices diam sit amet dapibus. Integer arcu diam, luctus nec urna eu, iaculis tempor arcu. Sed sit amet pulvinar arcu, eget consequat ante. Curabitur nunc ante, venenatis at tellus eu, euismod vulputate lectus. Vivamus finibus arcu nulla.</p>\r\n<p>Proin mollis ullamcorper nibh et viverra. Nullam iaculis leo et erat commodo pretium. Phasellus ut sapien vel dui mattis vulputate. Duis non volutpat elit. Nulla vitae mi metus. Donec euismod vulputate risus, ac maximus erat maximus quis. Nullam molestie eget orci ac accumsan. Proin tortor lectus, ultrices id tortor vel, mollis faucibus enim. Proin augue ante, mollis id libero eget, ultrices auctor augue. Nam lacinia dapibus dui, nec bibendum lacus pharetra sit amet. Maecenas ut diam urna. Sed consectetur ipsum nec tempus facilisis. Proin gravida est lectus, vel sagittis lorem porta non. Maecenas id tempus ex. Integer ullamcorper, lacus sed interdum imperdiet, purus tellus dapibus ipsum, sed auctor dui dolor at lorem.</p>\r\n<p>Sed non placerat erat. Nullam diam purus, cursus vitae sapien et, ultrices molestie eros. Aliquam eleifend sem libero, et facilisis tellus sagittis id. Aliquam faucibus, enim ut fermentum aliquam, arcu nunc mollis justo, in pulvinar ante nisl nec ex. Morbi vel eros non tellus tincidunt sagittis. Sed massa felis, finibus non placerat a, pharetra sit amet massa. Proin ornare magna vitae risus accumsan, vel sagittis nisl finibus. Sed sit amet finibus magna. Proin fringilla risus sit amet velit auctor, sit amet faucibus tellus scelerisque.</p>', 1),
(3, 'system_configurations', '{\"system_configurations\":\"1\",\"system_configurations_id\":\"3\",\"system_timezone_gmt\":\"+05:30\",\"system_timezone\":\"Asia/Kolkata\",\"app_link\":\"https://play.google.com/store/apps/details?id=com.wrteam.quiz\",\"more_apps\":\"https://play.google.com/store/apps/developer?id=\",\"ios_app_link\":\"https://play.google.com/store/apps/details?id=com.wrteam.quiz\",\"ios_more_apps\":\"https://play.google.com/store/apps/developer?id=\",\"refer_coin\":\"50\",\"earn_coin\":\"100\",\"reward_coin\":\"4\",\"app_version\":\"1.0\",\"true_value\":\"True\",\"false_value\":\"False\",\"answer_mode\":\"1\",\"language_mode\":\"1\",\"option_e_mode\":\"1\",\"force_update\":\"0\",\"daily_quiz_mode\":\"1\",\"contest_mode\":\"1\",\"battle_random_category_mode\":\"0\",\"battle_group_category_mode\":\"0\",\"in_app_purchase_mode\":\"1\",\"learning_zone_mode\":\"1\",\"maths_quiz_mode\":\"1\",\"true_false_mode\":\"1\",\"fix_question\":\"1\",\"total_question\":\"10\",\"app_maintenance\":\"1\",\"app_maintenance_message\":\"App Maintenance Message\",\"shareapp_text\":\"Hello, This is a simple share text. User will be happy to read this..\",\"in_app_ads_mode\":\"1\",\"ads_type\":\"1\",\"adAppId\":\"test\",\"admob_Rewarded_Video_Ads\":\"test\",\"admob_interstitial_id\":\"test\",\"admob_banner_id\":\"test\",\"native_unit_id\":\"test\",\"admob_openads_id\":\"test\",\"fb_interstitial_id\":\"\",\"fb_banner_id\":\"\",\"fb_rewarded_video_ads\":\"\",\"fb_native_unit_id\":\"\",\"ios_in_app_ads_mode\":\"1\",\"ios_ads_type\":\"1\",\"ios_admob_Rewarded_Video_Ads\":\"iostest\",\"ios_admob_interstitial_id\":\"iostest\",\"ios_admob_banner_id\":\"iostest\",\"ios_admob_openads_id\":\"iostest\",\"ios_fb_interstitial_id\":\"\",\"ios_fb_banner_id\":\"\",\"ios_fb_rewarded_video_ads\":\"\",\"instagram_link\":\"http://demo.com\",\"facebook_link\":\"http://demo.com\",\"youtube_link\":\"http://demo.com\"}', 1),
(5, 'instructions', '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Fusce aliquet vulputate tincidunt. Etiam pharetra auctor massa in aliquet. Curabitur a elit ut mauris ullamcorper pulvinar. Phasellus maximus tellus dui, id iaculis lectus fermentum nec. Aliquam odio erat, porttitor vel luctus id, sollicitudin non tortor. Vestibulum neque est, semper vel dui eu, varius aliquam ante. Donec mollis magna sed metus vestibulum consequat. Ut aliquam vulputate ligula, non cursus nibh gravida vitae. Phasellus tellus tellus, accumsan eget tortor laoreet, molestie mollis nisl. Donec molestie semper nibh in efficitur. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Suspendisse dignissim, ex ac iaculis pulvinar, sem nisi scelerisque justo, pulvinar pellentesque ex mi bibendum urna. Quisque ac commodo justo. Integer ut dignissim lectus. Donec a elementum dolor. Vivamus eu nunc vitae mi iaculis imperdiet.</p>\r\n<p>Ut ullamcorper risus leo, sit amet dictum magna consequat id. Cras eros leo, ullamcorper a vehicula sed, suscipit nec mi. Donec facilisis, urna eu placerat condimentum, nisi quam tincidunt ex, ac auctor nisi metus vel tellus. Curabitur aliquam felis ut ex facilisis eleifend. Mauris dapibus consectetur eros, id venenatis risus pretium eget. Proin sit amet egestas odio. Vivamus interdum, enim nec egestas vulputate, purus dui convallis velit, eu elementum massa nibh at nulla. Morbi ullamcorper accumsan ipsum, id pulvinar purus ultrices vel. In vehicula ultrices diam sit amet dapibus. Integer arcu diam, luctus nec urna eu, iaculis tempor arcu. Sed sit amet pulvinar arcu, eget consequat ante. Curabitur nunc ante, venenatis at tellus eu, euismod vulputate lectus. Vivamus finibus arcu nulla.</p>\r\n<p>Proin mollis ullamcorper nibh et viverra. Nullam iaculis leo et erat commodo pretium. Phasellus ut sapien vel dui mattis vulputate. Duis non volutpat elit. Nulla vitae mi metus. Donec euismod vulputate risus, ac maximus erat maximus quis. Nullam molestie eget orci ac accumsan. Proin tortor lectus, ultrices id tortor vel, mollis faucibus enim. Proin augue ante, mollis id libero eget, ultrices auctor augue. Nam lacinia dapibus dui, nec bibendum lacus pharetra sit amet. Maecenas ut diam urna. Sed consectetur ipsum nec tempus facilisis. Proin gravida est lectus, vel sagittis lorem porta non. Maecenas id tempus ex. Integer ullamcorper, lacus sed interdum imperdiet, purus tellus dapibus ipsum, sed auctor dui dolor at lorem.</p>\r\n<p>Sed non placerat erat. Nullam diam purus, cursus vitae sapien et, ultrices molestie eros. Aliquam eleifend sem libero, et facilisis tellus sagittis id. Aliquam faucibus, enim ut fermentum aliquam, arcu nunc mollis justo, in pulvinar ante nisl nec ex. Morbi vel eros non tellus tincidunt sagittis. Sed massa felis, finibus non placerat a, pharetra sit amet massa. Proin ornare magna vitae risus accumsan, vel sagittis nisl finibus. Sed sit amet finibus magna. Proin fringilla risus sit amet velit auctor, sit amet faucibus tellus scelerisque.</p>', 1),
(7, 'app_id_fb', '', 1),
(8, 'apiKey', '', 1),
(9, 'authDomain', '', 1),
(10, 'databaseURL', '', 1),
(11, 'projectId', '', 1),
(12, 'storageBucket', '', 1),
(13, 'messagingSenderId', '', 1),
(14, 'appId', '', 1),
(15, 'client_id_google', '', 1),
(16, 'quiz_version', '7.1.6', 1);

-- --------------------------------------------------------

--
-- Table structure for table `subcategory`
--

CREATE TABLE `subcategory` (
  `id` int(11) NOT NULL,
  `maincat_id` int(11) NOT NULL,
  `language_id` tinyint(4) NOT NULL DEFAULT 0,
  `subcategory_name` varchar(250) CHARACTER SET utf8 NOT NULL,
  `image` text CHARACTER SET utf8 DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `row_order` varchar(4) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_bookmark`
--

CREATE TABLE `tbl_bookmark` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `status` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_fcm_key`
--

CREATE TABLE `tbl_fcm_key` (
  `id` int(11) NOT NULL,
  `fcm_key` text CHARACTER SET utf8 NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_learning`
--

CREATE TABLE `tbl_learning` (
  `id` int(11) NOT NULL,
  `category` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `video_id` varchar(255) NOT NULL,
  `detail` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `status` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_learning_question`
--

CREATE TABLE `tbl_learning_question` (
  `id` int(11) NOT NULL,
  `learning_id` int(11) NOT NULL,
  `question` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `question_type` int(11) NOT NULL COMMENT '1= normal, 2= true/false',
  `optiona` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `optionb` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `optionc` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `optiond` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `optione` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `answer` varchar(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_level`
--

CREATE TABLE `tbl_level` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category` int(11) NOT NULL,
  `subcategory` int(11) NOT NULL,
  `level` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_maths_question`
--

CREATE TABLE `tbl_maths_question` (
  `id` int(11) NOT NULL,
  `category` int(11) NOT NULL,
  `subcategory` int(11) NOT NULL,
  `language_id` tinyint(4) NOT NULL DEFAULT 0,
  `image` varchar(512) CHARACTER SET utf8 NOT NULL,
  `question` text CHARACTER SET utf8 NOT NULL,
  `question_type` int(11) NOT NULL COMMENT '1= normal, 2= true/false',
  `optiona` text CHARACTER SET utf8 NOT NULL,
  `optionb` text CHARACTER SET utf8 NOT NULL,
  `optionc` text CHARACTER SET utf8 NOT NULL,
  `optiond` text CHARACTER SET utf8 NOT NULL,
  `optione` text CHARACTER SET utf8 DEFAULT NULL,
  `answer` text CHARACTER SET utf8 NOT NULL,
  `note` text CHARACTER SET utf8 NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_rooms`
--

CREATE TABLE `tbl_rooms` (
  `id` int(11) NOT NULL,
  `room_id` text NOT NULL,
  `user_id` int(11) NOT NULL,
  `room_type` varchar(11) CHARACTER SET utf8mb4 NOT NULL,
  `category_id` int(11) NOT NULL,
  `no_of_que` int(11) NOT NULL,
  `questions` longtext CHARACTER SET utf8mb4 NOT NULL,
  `date_created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_tracker`
--

CREATE TABLE `tbl_tracker` (
  `id` int(255) NOT NULL,
  `user_id` int(11) NOT NULL,
  `uid` text CHARACTER SET utf8 NOT NULL,
  `points` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `type` text CHARACTER SET utf8 NOT NULL,
  `date` date NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `firebase_id` longtext CHARACTER SET utf8 NOT NULL,
  `name` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `email` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `mobile` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `type` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `profile` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `fcm_id` varchar(1024) COLLATE utf8_unicode_ci DEFAULT NULL,
  `coins` int(11) NOT NULL DEFAULT 0,
  `refer_code` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `friends_code` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ip_address` char(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `status` int(10) UNSIGNED DEFAULT 0,
  `date_registered` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users_statistics`
--

CREATE TABLE `users_statistics` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `questions_answered` int(11) NOT NULL,
  `correct_answers` int(11) NOT NULL,
  `strong_category` int(11) NOT NULL,
  `ratio1` double NOT NULL,
  `weak_category` int(11) NOT NULL,
  `ratio2` double NOT NULL,
  `best_position` int(11) NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `authenticate`
--
ALTER TABLE `authenticate`
  ADD PRIMARY KEY (`auth_username`),
  ADD UNIQUE KEY `auth_username` (`auth_username`);

--
-- Indexes for table `battle_questions`
--
ALTER TABLE `battle_questions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `match_id` (`match_id`);

--
-- Indexes for table `battle_statistics`
--
ALTER TABLE `battle_statistics`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contest`
--
ALTER TABLE `contest`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contest_leaderboard`
--
ALTER TABLE `contest_leaderboard`
  ADD PRIMARY KEY (`id`),
  ADD KEY `score` (`score`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `contest_id` (`contest_id`);

--
-- Indexes for table `contest_prize`
--
ALTER TABLE `contest_prize`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contest_id` (`contest_id`);

--
-- Indexes for table `contest_questions`
--
ALTER TABLE `contest_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contest_id` (`contest_id`) USING BTREE;

--
-- Indexes for table `daily_leaderboard`
--
ALTER TABLE `daily_leaderboard`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`,`date_created`);

--
-- Indexes for table `daily_quiz`
--
ALTER TABLE `daily_quiz`
  ADD PRIMARY KEY (`id`),
  ADD KEY `language_id` (`language_id`);

--
-- Indexes for table `daily_quiz_user`
--
ALTER TABLE `daily_quiz_user`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `languages`
--
ALTER TABLE `languages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `monthly_leaderboard`
--
ALTER TABLE `monthly_leaderboard`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`,`date_created`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `question`
--
ALTER TABLE `question`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category` (`category`),
  ADD KEY `subcategory` (`subcategory`) USING BTREE;

--
-- Indexes for table `question_reports`
--
ALTER TABLE `question_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `question_id` (`question_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `subcategory`
--
ALTER TABLE `subcategory`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_bookmark`
--
ALTER TABLE `tbl_bookmark`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tbl_fcm_key`
--
ALTER TABLE `tbl_fcm_key`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_learning`
--
ALTER TABLE `tbl_learning`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category` (`category`),
  ADD KEY `language_id` (`language_id`);

--
-- Indexes for table `tbl_learning_question`
--
ALTER TABLE `tbl_learning_question`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contest_id` (`learning_id`) USING BTREE;

--
-- Indexes for table `tbl_level`
--
ALTER TABLE `tbl_level`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_maths_question`
--
ALTER TABLE `tbl_maths_question`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category` (`category`),
  ADD KEY `subcategory` (`subcategory`) USING BTREE;

--
-- Indexes for table `tbl_rooms`
--
ALTER TABLE `tbl_rooms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tbl_tracker`
--
ALTER TABLE `tbl_tracker`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email` (`email`,`mobile`);

--
-- Indexes for table `users_statistics`
--
ALTER TABLE `users_statistics`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `battle_questions`
--
ALTER TABLE `battle_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `battle_statistics`
--
ALTER TABLE `battle_statistics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contest`
--
ALTER TABLE `contest`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contest_leaderboard`
--
ALTER TABLE `contest_leaderboard`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contest_prize`
--
ALTER TABLE `contest_prize`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contest_questions`
--
ALTER TABLE `contest_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `daily_leaderboard`
--
ALTER TABLE `daily_leaderboard`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `daily_quiz`
--
ALTER TABLE `daily_quiz`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `daily_quiz_user`
--
ALTER TABLE `daily_quiz_user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `languages`
--
ALTER TABLE `languages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `monthly_leaderboard`
--
ALTER TABLE `monthly_leaderboard`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `question`
--
ALTER TABLE `question`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `question_reports`
--
ALTER TABLE `question_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `subcategory`
--
ALTER TABLE `subcategory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_bookmark`
--
ALTER TABLE `tbl_bookmark`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_fcm_key`
--
ALTER TABLE `tbl_fcm_key`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_learning`
--
ALTER TABLE `tbl_learning`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_learning_question`
--
ALTER TABLE `tbl_learning_question`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_level`
--
ALTER TABLE `tbl_level`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_maths_question`
--
ALTER TABLE `tbl_maths_question`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_rooms`
--
ALTER TABLE `tbl_rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_tracker`
--
ALTER TABLE `tbl_tracker`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users_statistics`
--
ALTER TABLE `users_statistics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;


CREATE TABLE `user_purchased_category` (`id` INT NOT NULL AUTO_INCREMENT , `category_id` INT NOT NULL , `user_id` INT NOT NULL , `is_Purchased` BOOLEAN NULL DEFAULT TRUE , PRIMARY KEY (`id`));

ALTER TABLE `category` ADD `plan` VARCHAR(255) NOT NULL DEFAULT 'Free' AFTER `image`, ADD `amount` INT NOT NULL DEFAULT '0' AFTER `plan`;
COMMIT;
ALTER TABLE `tbl_learning` ADD `pdf_file` VARCHAR(255) NULL AFTER `detail`;

ALTER TABLE `category` ADD `status` BOOLEAN NOT NULL DEFAULT TRUE AFTER `amount`;


/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
