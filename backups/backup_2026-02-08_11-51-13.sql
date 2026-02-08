-- Database Backup
-- Generated: 2026-02-08 11:51:13

SET FOREIGN_KEY_CHECKS=0;

-- Table structure for table `admins`
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'editor',
  `remember_token` varchar(255) DEFAULT NULL,
  `is_banned` tinyint(1) DEFAULT 0,
  `ban_reason` varchar(255) DEFAULT NULL,
  `last_active` timestamp NULL DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `admins`
INSERT INTO `admins` VALUES("1","samnang","samnang@gmail.com","$2y$10$MOXi3GEsHI1hNm0Ye5m/GODIAkvu7TgnlVoxk4PLbf4BikZDpF12C","69864c3b9a7e6.png","2026-02-08 14:35:57","Hello","editor",NULL,"0",NULL,"2026-02-08 17:47:18",NULL,NULL);
INSERT INTO `admins` VALUES("2","Admin","admin@gmail.com","$2y$10$054EPpyRNgkjLQ5PJB3eU.J3eb2FMSvZW.q8WKiaofhP.ka/5pvOa","69869dd17ea75.png","2026-02-08 17:47:30","","admin","4b50c041ecb32db4ace60b052eaaec45492d74663b4a969afd2f42fe8d82aeb0","0",NULL,"2026-02-08 17:51:13",NULL,NULL);
INSERT INTO `admins` VALUES("3","dara","dara@gmail.com","$2y$10$MRZ6ypKYT5XPLRP/TV.KauC8qAHF2ktTg6S3gWvycuGM/EPyoJMca",NULL,NULL,NULL,"editor",NULL,"1","Resignation",NULL,NULL,NULL);

-- Table structure for table `clicks`
DROP TABLE IF EXISTS `clicks`;
CREATE TABLE `clicks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page` varchar(50) NOT NULL,
  `type` enum('follow','share') NOT NULL,
  `ip` varchar(45) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `click_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `clicks`
INSERT INTO `clicks` VALUES("1","main","follow","::1","2026-02-06 22:19:07","2026-02-07 07:55:42",NULL);
INSERT INTO `clicks` VALUES("2","main","follow","::1","2026-02-06 22:23:06","2026-02-07 07:55:42",NULL);
INSERT INTO `clicks` VALUES("3","main","share","::1","2026-02-06 22:23:58","2026-02-07 07:55:42",NULL);
INSERT INTO `clicks` VALUES("4","main","follow","::1","2026-02-06 22:31:17","2026-02-07 07:55:42",NULL);
INSERT INTO `clicks` VALUES("5","main","follow","::1","2026-02-06 22:31:28","2026-02-07 07:55:42",NULL);
INSERT INTO `clicks` VALUES("6","main","follow","::1","2026-02-06 22:33:15","2026-02-07 07:55:42",NULL);
INSERT INTO `clicks` VALUES("7","main","follow","::1","2026-02-06 22:35:22","2026-02-07 07:55:42",NULL);
INSERT INTO `clicks` VALUES("8","main","follow","::1","2026-02-06 22:36:51","2026-02-07 07:55:42",NULL);
INSERT INTO `clicks` VALUES("9","second","follow","::1","2026-02-06 22:57:34","2026-02-07 07:55:42",NULL);
INSERT INTO `clicks` VALUES("10","main","follow","::1","2026-02-06 22:57:55","2026-02-07 07:55:42",NULL);
INSERT INTO `clicks` VALUES("11","third","follow","::1","2026-02-06 22:58:59","2026-02-07 07:55:42",NULL);
INSERT INTO `clicks` VALUES("12","second","follow","::1","2026-02-06 23:04:55","2026-02-07 07:55:42",NULL);
INSERT INTO `clicks` VALUES("13","second","follow","::1","2026-02-06 23:05:32","2026-02-07 07:55:42",NULL);
INSERT INTO `clicks` VALUES("14","second","follow","::1","2026-02-06 23:05:56","2026-02-07 07:55:42",NULL);
INSERT INTO `clicks` VALUES("15","main","follow","::1","2026-02-06 23:07:13","2026-02-07 07:55:42",NULL);
INSERT INTO `clicks` VALUES("16","second","follow","::1","2026-02-06 23:07:21","2026-02-07 07:55:42",NULL);
INSERT INTO `clicks` VALUES("17","second","follow","::1","2026-02-06 23:07:27","2026-02-07 07:55:42",NULL);
INSERT INTO `clicks` VALUES("18","third","follow","::1","2026-02-06 23:07:34","2026-02-07 07:55:42",NULL);
INSERT INTO `clicks` VALUES("19","ITNova","follow","::1","2026-02-06 23:14:42","2026-02-07 07:55:42",NULL);
INSERT INTO `clicks` VALUES("20","main","share","::1","2026-02-06 23:19:27","2026-02-07 07:55:42",NULL);
INSERT INTO `clicks` VALUES("21","second","follow","::1","2026-02-07 01:05:09","2026-02-07 07:55:42",NULL);
INSERT INTO `clicks` VALUES("22","second","follow","::1","2026-02-07 03:31:53","2026-02-07 07:55:42",NULL);
INSERT INTO `clicks` VALUES("23","Samnang-Page","follow","::1","2026-02-07 10:40:49","2026-02-07 10:40:49",NULL);
INSERT INTO `clicks` VALUES("24","Samnang-Page","share","::1","2026-02-07 11:24:49","2026-02-07 11:24:49","1");
INSERT INTO `clicks` VALUES("25","Samnang-Page","follow","::1","2026-02-07 12:08:41","2026-02-07 12:08:41","1");
INSERT INTO `clicks` VALUES("26","Samnang-Page","follow","::1","2026-02-07 12:41:06","2026-02-07 12:41:06",NULL);
INSERT INTO `clicks` VALUES("27","Samnang-Page","follow","::1","2026-02-07 12:41:59","2026-02-07 12:41:59",NULL);
INSERT INTO `clicks` VALUES("28","ITNova","follow","::1","2026-02-08 00:45:44","2026-02-08 00:45:44","1");
INSERT INTO `clicks` VALUES("29","Tech Khmer Hub","follow","::1","2026-02-08 01:29:49","2026-02-08 01:29:49","1");
INSERT INTO `clicks` VALUES("30","Good morning😘🎉","follow","::1","2026-02-08 01:40:13","2026-02-08 01:40:13","1");
INSERT INTO `clicks` VALUES("31","ITNova","follow","::1","2026-02-08 14:44:04","2026-02-08 14:44:04","2");
INSERT INTO `clicks` VALUES("32","Tech Khmer Hub","follow","::1","2026-02-08 16:15:51","2026-02-08 16:15:51","2");

-- Table structure for table `favorites`
DROP TABLE IF EXISTS `favorites`;
CREATE TABLE `favorites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `link` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `category` varchar(50) DEFAULT 'General',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `favorites`

-- Table structure for table `fb_comments`
DROP TABLE IF EXISTS `fb_comments`;
CREATE TABLE `fb_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `comment_id` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `replied` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `comment_id` (`comment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `fb_comments`

-- Table structure for table `notifications`
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `notifications`
INSERT INTO `notifications` VALUES("1","dara","hello","0","2026-02-07 09:43:00");
INSERT INTO `notifications` VALUES("2","samnang","are you ok today ?","1","2026-02-07 09:44:04");
INSERT INTO `notifications` VALUES("3","samnang","are you ok today ?","1","2026-02-07 09:44:51");
INSERT INTO `notifications` VALUES("53","samnang","👋 Hello! This is a sample notification to show you how alerts look in the system. Generated at 05:23 AM","1","2026-02-07 11:23:00");

-- Table structure for table `pages`
DROP TABLE IF EXISTS `pages`;
CREATE TABLE `pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `fb_link` varchar(255) NOT NULL,
  `target_clicks` bigint(20) DEFAULT 0,
  `status` enum('active','completed','paused') DEFAULT 'active',
  `daily_limit` bigint(20) DEFAULT 0,
  `type` enum('page','follower','post') DEFAULT 'page',
  `paused_by_limit` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_fast` tinyint(1) DEFAULT 0,
  `start_count` bigint(20) DEFAULT 0,
  `speed` varchar(50) DEFAULT 'normal',
  `scheduled_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `pages`
INSERT INTO `pages` VALUES("5","ITNova","https://www.facebook.com/profile.php?id=61582369283941","0","active","0","page","0","2026-02-08 02:29:34","0","0","normal",NULL);
INSERT INTO `pages` VALUES("6","ITNova","https://www.facebook.com/profile.php?id=61582369283941","0","active","0","page","0","2026-02-08 02:29:34","0","0","normal",NULL);
INSERT INTO `pages` VALUES("7","ITNova","https://www.facebook.com/profile.php?id=61582369283941","0","active","0","page","0","2026-02-08 02:29:34","0","0","normal",NULL);
INSERT INTO `pages` VALUES("9","Samnang-Page","https://www.facebook.com/profile.php?id=61561186613912","10","active","0","page","0","2026-02-08 02:29:34","0","0","normal",NULL);
INSERT INTO `pages` VALUES("10","ITNova","https://www.facebook.com/profile.php?id=61587440882724","5000","active","5","follower","0","2026-02-08 02:29:34","0","0","normal",NULL);
INSERT INTO `pages` VALUES("11","Tech Khmer Hub","https://www.facebook.com/share/1Mw3bN9jyY/?mibextid=wwXIfr","120000000","active","0","page","0","2026-02-08 02:29:34","0","0","normal",NULL);
INSERT INTO `pages` VALUES("12","Good morning😘🎉","https://www.facebook.com/share/p/1BzQMw83dp/?mibextid=wwXIfr","10000","active","20","post","0","2026-02-08 02:29:34","0","0","normal",NULL);
INSERT INTO `pages` VALUES("13","Good morning😘🎉","https://www.facebook.com/share/p/1BzQMw83dp/?mibextid=wwXIfr","10000","active","20","post","0","2026-02-08 02:29:34","0","0","normal",NULL);
INSERT INTO `pages` VALUES("14","Tech Khmer Hub (Clone)","https://www.facebook.com/share/1Mw3bN9jyY/?mibextid=wwXIfr","120000000","paused","0","page","0","2026-02-08 02:29:34","0","0","normal",NULL);
INSERT INTO `pages` VALUES("15","Samnang-Page","https://www.facebook.com/profile.php?id=61582369283941","4000","active","0","page","0","2026-02-08 02:46:12","0","0","normal",NULL);
INSERT INTO `pages` VALUES("16","ITNova","https://www.facebook.com/profile.php?id=61587440882724","500000000","active","0","follower","0","2026-02-08 13:28:14","0","0","normal",NULL);
INSERT INTO `pages` VALUES("17","ITNova","https://www.facebook.com/profile.php?id=61587440882724","500","active","0","follower","0","2026-02-08 13:37:43","0","0","normal",NULL);
INSERT INTO `pages` VALUES("18","ITNova","https://www.facebook.com/profile.php?id=61587440882724","5000000000","active","0","follower","0","2026-02-08 14:13:50","1","0","fast",NULL);

-- Table structure for table `redemptions`
DROP TABLE IF EXISTS `redemptions`;
CREATE TABLE `redemptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `reward_id` int(11) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `redemptions`
INSERT INTO `redemptions` VALUES("1","1","1","approved","2026-02-07 23:55:35");

-- Table structure for table `rewards`
DROP TABLE IF EXISTS `rewards`;
CREATE TABLE `rewards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `points_cost` int(11) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `stock` int(11) DEFAULT -1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `rewards`
INSERT INTO `rewards` VALUES("1","Phone","good","100","reward_1770437368.jpeg","11","2026-02-07 11:09:28");

-- Table structure for table `server_metrics`
DROP TABLE IF EXISTS `server_metrics`;
CREATE TABLE `server_metrics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `load_val` float NOT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `server_metrics`
INSERT INTO `server_metrics` VALUES("1","4.31543","2026-02-07 09:23:00");
INSERT INTO `server_metrics` VALUES("2","3.84814","2026-02-07 16:51:18");
INSERT INTO `server_metrics` VALUES("3","2.91064","2026-02-08 00:10:15");
INSERT INTO `server_metrics` VALUES("4","3.35791","2026-02-08 13:45:56");

-- Table structure for table `settings`
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `settings`
INSERT INTO `settings` VALUES("1","site_title","FB Money System");
INSERT INTO `settings` VALUES("8","sidebar_color","#1c07bb");
INSERT INTO `settings` VALUES("19","notification_sound","notif_sound_1770481542.mp3");

-- Table structure for table `support_messages`
DROP TABLE IF EXISTS `support_messages`;
CREATE TABLE `support_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Open','Resolved') DEFAULT 'Open',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `support_messages`
INSERT INTO `support_messages` VALUES("1","samnang","he","jjhh","2026-02-07 03:56:36","Resolved");
INSERT INTO `support_messages` VALUES("2","samnang","add user","sas","2026-02-07 08:13:02","Resolved");

-- Table structure for table `system_logs`
DROP TABLE IF EXISTS `system_logs`;
CREATE TABLE `system_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=183 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `system_logs`
INSERT INTO `system_logs` VALUES("1","samnang","Clear Logs","All system logs were cleared.","::1","2026-02-07 02:40:25");
INSERT INTO `system_logs` VALUES("2","samnang","Login","User logged in successfully","::1","2026-02-07 02:57:49");
INSERT INTO `system_logs` VALUES("3","samnang","Register","New user registered publicly","::1","2026-02-07 03:05:49");
INSERT INTO `system_logs` VALUES("4","samnang","Update Profile","Updated profile info","::1","2026-02-07 03:16:59");
INSERT INTO `system_logs` VALUES("5","samnang","Add Page","Added page: Samnang-Page","::1","2026-02-07 03:32:57");
INSERT INTO `system_logs` VALUES("6","samnang","Login","User logged in successfully","::1","2026-02-07 03:54:10");
INSERT INTO `system_logs` VALUES("7","samnang","Update Settings","Updated Site Settings","::1","2026-02-07 08:03:48");
INSERT INTO `system_logs` VALUES("8","samnang","Update Settings","Updated Site Settings","::1","2026-02-07 08:04:14");
INSERT INTO `system_logs` VALUES("9","samnang","Update Settings","Updated Site Settings","::1","2026-02-07 08:04:25");
INSERT INTO `system_logs` VALUES("10","samnang","Update Settings","Updated Site Settings","::1","2026-02-07 08:04:42");
INSERT INTO `system_logs` VALUES("11","samnang","Update Profile","Updated profile info","::1","2026-02-07 08:12:22");
INSERT INTO `system_logs` VALUES("12","Admin","Register","New user registered publicly","::1","2026-02-07 08:23:54");
INSERT INTO `system_logs` VALUES("13","Admin","Login","User logged in successfully","::1","2026-02-07 08:33:11");
INSERT INTO `system_logs` VALUES("14","Admin","Add User","Created new user: dara","::1","2026-02-07 08:58:55");
INSERT INTO `system_logs` VALUES("15","Admin","Ban User","Banned user ID: 3. Reason: Resignation","::1","2026-02-07 08:59:37");
INSERT INTO `system_logs` VALUES("16","Admin","Ban User","Banned user ID: 3. Reason: Resignation","::1","2026-02-07 08:59:44");
INSERT INTO `system_logs` VALUES("17","Admin","Update Profile","Updated profile info","::1","2026-02-07 09:05:05");
INSERT INTO `system_logs` VALUES("18","Admin","Update User","Updated user ID: 3","::1","2026-02-07 09:12:52");
INSERT INTO `system_logs` VALUES("19","Admin","Update User","Updated user ID: 3","::1","2026-02-07 09:12:59");
INSERT INTO `system_logs` VALUES("20","Admin","Update User","Updated user ID: 3","::1","2026-02-07 09:15:36");
INSERT INTO `system_logs` VALUES("21","Admin","Backup Database","Downloaded database backup","::1","2026-02-07 09:18:06");
INSERT INTO `system_logs` VALUES("22","Admin","Health Check","Passed.","::1","2026-02-07 09:23:33");
INSERT INTO `system_logs` VALUES("23","Admin","Clear Cache","Cleared system cache.","::1","2026-02-07 09:26:48");
INSERT INTO `system_logs` VALUES("24","Admin","Clear Cache","Cleared system cache.","::1","2026-02-07 09:27:02");
INSERT INTO `system_logs` VALUES("25","Admin","Clear Cache","Cleared system cache.","::1","2026-02-07 09:27:31");
INSERT INTO `system_logs` VALUES("26","Admin","Optimize DB","Optimized all database tables.","::1","2026-02-07 09:27:40");
INSERT INTO `system_logs` VALUES("27","Admin","Health Check","Failed. Alert sent.","::1","2026-02-07 09:28:21");
INSERT INTO `system_logs` VALUES("28","Admin","Health Check","Failed. Alert sent.","::1","2026-02-07 09:28:57");
INSERT INTO `system_logs` VALUES("29","Admin","Health Check","Failed. Alert sent.","::1","2026-02-07 09:29:49");
INSERT INTO `system_logs` VALUES("30","Admin","Optimize DB","Optimized all database tables.","::1","2026-02-07 09:30:55");
INSERT INTO `system_logs` VALUES("31","Admin","Optimize DB","Optimized all database tables.","::1","2026-02-07 09:31:12");
INSERT INTO `system_logs` VALUES("32","Admin","Optimize DB","Optimized all database tables.","::1","2026-02-07 09:31:36");
INSERT INTO `system_logs` VALUES("33","Admin","Send Notification","Sent message to dara","::1","2026-02-07 09:43:00");
INSERT INTO `system_logs` VALUES("34","Admin","Send Notification","Sent message to samnang","::1","2026-02-07 09:44:04");
INSERT INTO `system_logs` VALUES("35","Admin","Send Notification","Sent message to samnang","::1","2026-02-07 09:44:51");
INSERT INTO `system_logs` VALUES("36","Admin","Optimize DB","Optimized all database tables.","::1","2026-02-07 10:00:14");
INSERT INTO `system_logs` VALUES("37","Admin","Update Page","Updated link for page ID: 9","::1","2026-02-07 10:40:32");
INSERT INTO `system_logs` VALUES("38","Admin","Update Page","Updated link for page ID: 9","::1","2026-02-07 10:41:28");
INSERT INTO `system_logs` VALUES("39","Admin","Update Page","Updated link for page ID: 9","::1","2026-02-07 10:41:36");
INSERT INTO `system_logs` VALUES("40","Admin","Update Page","Updated page ID: 1","::1","2026-02-07 11:04:33");
INSERT INTO `system_logs` VALUES("41","Admin","Update Page","Updated page ID: 1","::1","2026-02-07 11:04:53");
INSERT INTO `system_logs` VALUES("42","Admin","Update Page","Updated page ID: 1","::1","2026-02-07 11:05:07");
INSERT INTO `system_logs` VALUES("43","Admin","Update Page","Updated page ID: 1","::1","2026-02-07 11:05:20");
INSERT INTO `system_logs` VALUES("44","Admin","Update Page","Updated page ID: 2","::1","2026-02-07 11:05:29");
INSERT INTO `system_logs` VALUES("45","Admin","Update Page","Updated page ID: 3","::1","2026-02-07 11:05:37");
INSERT INTO `system_logs` VALUES("46","Admin","Update Page","Updated page ID: 9","::1","2026-02-07 11:05:51");
INSERT INTO `system_logs` VALUES("47","Admin","Update Page","Updated page ID: 9","::1","2026-02-07 11:06:00");
INSERT INTO `system_logs` VALUES("48","Admin","Update Page","Updated page ID: 9","::1","2026-02-07 11:06:12");
INSERT INTO `system_logs` VALUES("49","Admin","Add Reward","Added reward: Phone","::1","2026-02-07 11:09:28");
INSERT INTO `system_logs` VALUES("50","Admin","Update Page","Updated page ID: 9","::1","2026-02-07 11:11:23");
INSERT INTO `system_logs` VALUES("51","Admin","Update User","Updated user ID: 1 (Password changed)","::1","2026-02-07 11:14:45");
INSERT INTO `system_logs` VALUES("52","Admin","Update User","Updated user ID: 1 (Password changed)","::1","2026-02-07 11:20:50");
INSERT INTO `system_logs` VALUES("53","samnang","Login","User logged in successfully","::1","2026-02-07 11:22:39");
INSERT INTO `system_logs` VALUES("54","samnang","Update Page","Updated page ID: 9","::1","2026-02-07 11:24:15");
INSERT INTO `system_logs` VALUES("55","samnang","Update Page","Updated page ID: 9","::1","2026-02-07 11:24:22");
INSERT INTO `system_logs` VALUES("56","samnang","Login","User logged in successfully","::1","2026-02-07 11:26:21");
INSERT INTO `system_logs` VALUES("57","samnang","Optimize DB","Optimized all database tables.","::1","2026-02-07 11:28:14");
INSERT INTO `system_logs` VALUES("58","samnang","Boost Video","Added video: កុំ​ស្រលាញ់​គេ​ ភ្លេច​ស្រលាញ់​ខ្លួន​ឯង​","::1","2026-02-07 11:45:44");
INSERT INTO `system_logs` VALUES("59","samnang","Boost Video","Added video: កុំ​ស្រលាញ់​គេ​ ភ្លេច​ស្រលាញ់​ខ្លួន​ឯង​","::1","2026-02-07 11:49:45");
INSERT INTO `system_logs` VALUES("60","samnang","Update Video Status","Updated video ID: 1 to paused","::1","2026-02-07 11:49:54");
INSERT INTO `system_logs` VALUES("61","samnang","Update Video Status","Updated video ID: 1 to active","::1","2026-02-07 11:49:58");
INSERT INTO `system_logs` VALUES("62","samnang","Update Video Status","Updated video ID: 1 to paused","::1","2026-02-07 11:50:00");
INSERT INTO `system_logs` VALUES("63","samnang","Update Video Status","Updated video ID: 1 to active","::1","2026-02-07 11:50:01");
INSERT INTO `system_logs` VALUES("64","samnang","Update Video Status","Updated video ID: 1 to active","::1","2026-02-07 11:50:35");
INSERT INTO `system_logs` VALUES("65","samnang","Boost Video","Added video: ស្នេហ៍​គេ​តែង​តែម្ខាង​","::1","2026-02-07 12:06:32");
INSERT INTO `system_logs` VALUES("66","samnang","Boost View","Added facebook view: ក្តី​ស្រមៃ​ ដែល​មនុស្ស​ម្នាក់ៗ​ចង់​បាន💝💍","::1","2026-02-07 12:10:28");
INSERT INTO `system_logs` VALUES("67","samnang","Delete Video","Deleted video ID: 4","::1","2026-02-07 12:12:59");
INSERT INTO `system_logs` VALUES("68","samnang","Boost View","Added facebook view: ក្តី​ស្រមៃ​ ដែល​មនុស្ស​ម្នាក់ៗ​ចង់​បាន💝💍","::1","2026-02-07 12:13:45");
INSERT INTO `system_logs` VALUES("69","samnang","Boost View","Added facebook view: ក្តី​ស្រមៃ​ ដែល​មនុស្ស​ម្នាក់ៗ​ចង់​បាន","::1","2026-02-07 12:18:02");
INSERT INTO `system_logs` VALUES("70","samnang","Boost Video","Added video: ក្តី​ស្រមៃ​ ដែល​មនុស្ស​ម្នាក់ៗ​ចង់​បាន","::1","2026-02-07 12:19:05");
INSERT INTO `system_logs` VALUES("71","samnang","Delete Video","Deleted video ID: 7","::1","2026-02-07 12:19:20");
INSERT INTO `system_logs` VALUES("72","samnang","Delete Video","Deleted video ID: 6","::1","2026-02-07 12:19:22");
INSERT INTO `system_logs` VALUES("73","samnang","Delete Video","Deleted video ID: 5","::1","2026-02-07 12:19:25");
INSERT INTO `system_logs` VALUES("74","samnang","Boost View","Added facebook view: ក្តី​ស្រមៃ​ ដែល​មនុស្ស​ម្នាក់ៗ​ចង់​បាន","::1","2026-02-07 12:21:53");
INSERT INTO `system_logs` VALUES("75","samnang","Boost View","Added facebook view: ក្តី​ស្រមៃ​ ដែល​មនុស្ស​ម្នាក់ៗ​ចង់​បាន","::1","2026-02-07 12:24:40");
INSERT INTO `system_logs` VALUES("76","samnang","Delete Video","Deleted video ID: 8","::1","2026-02-07 12:25:24");
INSERT INTO `system_logs` VALUES("77","samnang","Delete Video","Deleted video ID: 9","::1","2026-02-07 12:25:26");
INSERT INTO `system_logs` VALUES("78","samnang","Delete Video","Deleted video ID: 9","::1","2026-02-07 12:25:32");
INSERT INTO `system_logs` VALUES("79","samnang","Boost View","Added youtube view: ស្នេហ៍​គេ​តែង​តែម្ខាង","::1","2026-02-07 12:26:29");
INSERT INTO `system_logs` VALUES("80","samnang","Boost View","Added facebook view: love","::1","2026-02-07 12:31:12");
INSERT INTO `system_logs` VALUES("81","Admin","Login","User logged in successfully","::1","2026-02-07 12:33:51");
INSERT INTO `system_logs` VALUES("82","Admin","Boost View","Added facebook view: ក្តី​ស្រមៃ​ ដែល​មនុស្ស​ម្នាក់ៗ​ចង់​បាន","::1","2026-02-07 12:43:01");
INSERT INTO `system_logs` VALUES("83","Admin","Boost View","Added facebook view: ក្តី​ស្រមៃ​ ដែល​មនុស្ស​ម្នាក់ៗ​ចង់​បាន💝💍","::1","2026-02-07 12:48:04");
INSERT INTO `system_logs` VALUES("84","Admin","Boost View","Added youtube view: កុំ​ស្រលាញ់​គេ​ ភ្លេច​ស្រលាញ់​ខ្លួន​ឯង","::1","2026-02-07 12:57:41");
INSERT INTO `system_logs` VALUES("85","Admin","Boost View","Added youtube view: កុំ​ស្រលាញ់​គេ​ ភ្លេច​ស្រលាញ់​ខ្លួន​ឯង","::1","2026-02-07 12:58:41");
INSERT INTO `system_logs` VALUES("86","Admin","Boost View","Added facebook view: ជឿ​ហើយ​យាយ​AI😂 យាយ​សុទ្ធ​តែ​ចិន​😂","::1","2026-02-07 13:01:52");
INSERT INTO `system_logs` VALUES("87","Admin","Boost View","Added facebook view: ជឿ​ហើយ​យាយ​AI😂 យាយ​សុទ្ធ​តែ​ចិន​😂","::1","2026-02-07 13:05:21");
INSERT INTO `system_logs` VALUES("88","Admin","Optimize DB","Optimized all database tables.","::1","2026-02-07 13:27:48");
INSERT INTO `system_logs` VALUES("89","Admin","Backup Database","Downloaded database backup","::1","2026-02-07 13:28:22");
INSERT INTO `system_logs` VALUES("90","System","Cron Job","Resumed 0 campaigns (Daily Limit Reset)","127.0.0.1","2026-02-07 13:40:19");
INSERT INTO `system_logs` VALUES("91","Admin","Delete Page","Deleted page ID: 2","::1","2026-02-07 13:43:56");
INSERT INTO `system_logs` VALUES("92","Admin","Delete Page","Deleted page ID: 1","::1","2026-02-07 13:44:06");
INSERT INTO `system_logs` VALUES("93","Admin","Delete Page","Deleted page ID: 3","::1","2026-02-07 13:44:10");
INSERT INTO `system_logs` VALUES("94","Admin","Delete Page","Deleted page ID: 3","::1","2026-02-07 13:44:19");
INSERT INTO `system_logs` VALUES("95","Admin","Delete Page","Deleted page ID: 8","::1","2026-02-07 13:45:18");
INSERT INTO `system_logs` VALUES("96","Admin","Delete Page","Deleted page ID: 8","::1","2026-02-07 13:45:21");
INSERT INTO `system_logs` VALUES("97","Admin","Delete Page","Deleted page ID: 8","::1","2026-02-07 13:45:46");
INSERT INTO `system_logs` VALUES("98","Admin","Boost View","Added facebook view: ក្តី​ស្រមៃ​ ដែល​មនុស្ស​ម្នាក់ៗ​ចង់​បាន💝💍","::1","2026-02-07 22:39:56");
INSERT INTO `system_logs` VALUES("99","Admin","Optimize DB","Optimized all database tables.","::1","2026-02-07 22:45:14");
INSERT INTO `system_logs` VALUES("100","Admin","Optimize DB","Optimized all database tables.","::1","2026-02-07 22:45:40");
INSERT INTO `system_logs` VALUES("101","Admin","Boost View","Added facebook view: ចង់​បាន​អី​? ថ្ងៃ​១៤កុម្ភៈ​😻🌷","::1","2026-02-07 22:52:02");
INSERT INTO `system_logs` VALUES("102","Admin","Boost View","Added facebook view: គ្រូពេទ្យ​កំលោះ​សង្ហារ ធ្វើអោយអ្នកជំងឺភ្លេចឈឺ😻🧑‍⚕️","::1","2026-02-07 22:55:37");
INSERT INTO `system_logs` VALUES("103","Admin","Boost Follower","Added page for followers: ITNova","::1","2026-02-07 22:56:40");
INSERT INTO `system_logs` VALUES("104","Admin","Boost View","Added facebook view: ស្នេហាសម័យនេះ​មិនដូចរឿងនិទានទេ🥹🥰","::1","2026-02-07 23:08:05");
INSERT INTO `system_logs` VALUES("105","Admin","Update Settings","Updated Site Settings","::1","2026-02-07 23:25:32");
INSERT INTO `system_logs` VALUES("106","Admin","Update Settings","Updated Site Settings","::1","2026-02-07 23:25:42");
INSERT INTO `system_logs` VALUES("107","Admin","Login","User logged in successfully","::1","2026-02-07 23:58:05");
INSERT INTO `system_logs` VALUES("108","Admin","Boost Page","Added page: Tech Khmer Hub","::1","2026-02-08 00:49:49");
INSERT INTO `system_logs` VALUES("109","Admin","Boost View","Added facebook view: «ស្នេហា មិនមែនសន្យា តែជាការអនុវត្តរៀងរាល់ថ្ងៃ» 💞🎉","::1","2026-02-08 00:53:07");
INSERT INTO `system_logs` VALUES("110","Admin","Boost Video","Added video: អរគុណ​គ្រប់​យ៉ាង​😂","::1","2026-02-08 00:56:19");
INSERT INTO `system_logs` VALUES("111","Admin","Boost View","Added facebook view: អរគុណ​គ្រប់​យ៉ាង​😂","::1","2026-02-08 00:57:24");
INSERT INTO `system_logs` VALUES("112","Admin","Backup Database","Downloaded database backup","::1","2026-02-08 01:13:01");
INSERT INTO `system_logs` VALUES("113","Admin","Update Video","Updated video ID: 24","::1","2026-02-08 01:15:12");
INSERT INTO `system_logs` VALUES("114","Admin","Update Video","Updated video ID: 23","::1","2026-02-08 01:15:19");
INSERT INTO `system_logs` VALUES("115","Admin","Update Video","Updated video ID: 22","::1","2026-02-08 01:15:21");
INSERT INTO `system_logs` VALUES("116","Admin","Update Video","Updated video ID: 22","::1","2026-02-08 01:15:31");
INSERT INTO `system_logs` VALUES("117","Admin","Update Video","Updated video ID: 24","::1","2026-02-08 01:15:40");
INSERT INTO `system_logs` VALUES("118","Admin","Update Video","Updated video ID: 21","::1","2026-02-08 01:15:52");
INSERT INTO `system_logs` VALUES("119","Admin","Update Video","Updated video ID: 20","::1","2026-02-08 01:16:07");
INSERT INTO `system_logs` VALUES("120","Admin","Update Video","Updated video ID: 19","::1","2026-02-08 01:16:23");
INSERT INTO `system_logs` VALUES("121","Admin","Update Video","Updated video ID: 18","::1","2026-02-08 01:16:31");
INSERT INTO `system_logs` VALUES("122","Admin","Update Video","Updated video ID: 17","::1","2026-02-08 01:16:38");
INSERT INTO `system_logs` VALUES("123","Admin","Update Video","Updated video ID: 16","::1","2026-02-08 01:16:45");
INSERT INTO `system_logs` VALUES("124","Admin","Update Video","Updated video ID: 15","::1","2026-02-08 01:16:57");
INSERT INTO `system_logs` VALUES("125","Admin","Update Video","Updated video ID: 14","::1","2026-02-08 01:17:02");
INSERT INTO `system_logs` VALUES("126","Admin","Update Video","Updated video ID: 13","::1","2026-02-08 01:17:09");
INSERT INTO `system_logs` VALUES("127","Admin","Update Video","Updated video ID: 12","::1","2026-02-08 01:17:15");
INSERT INTO `system_logs` VALUES("128","Admin","Update Video","Updated video ID: 12","::1","2026-02-08 01:17:42");
INSERT INTO `system_logs` VALUES("129","Admin","Update Video","Updated video ID: 3","::1","2026-02-08 01:17:50");
INSERT INTO `system_logs` VALUES("130","Admin","Update Video","Updated video ID: 2","::1","2026-02-08 01:17:56");
INSERT INTO `system_logs` VALUES("131","Admin","Update Video","Updated video ID: 1","::1","2026-02-08 01:18:04");
INSERT INTO `system_logs` VALUES("132","Admin","Update Video","Updated video ID: 10","::1","2026-02-08 01:18:16");
INSERT INTO `system_logs` VALUES("133","Admin","Update Video","Updated video ID: 11","::1","2026-02-08 01:18:23");
INSERT INTO `system_logs` VALUES("134","Admin","Update Video","Updated video ID: 11","::1","2026-02-08 01:18:39");
INSERT INTO `system_logs` VALUES("135","Admin","Update Video","Updated video ID: 10","::1","2026-02-08 01:19:12");
INSERT INTO `system_logs` VALUES("136","Admin","Optimize DB","Optimized all database tables.","::1","2026-02-08 01:20:18");
INSERT INTO `system_logs` VALUES("137","Admin","Boost Post","Added post: Good morning😘🎉","::1","2026-02-08 01:38:00");
INSERT INTO `system_logs` VALUES("138","Admin","Boost Post","Added post: Good morning😘🎉","::1","2026-02-08 01:38:16");
INSERT INTO `system_logs` VALUES("139","Admin","Boost Reel","Added reel: គ្រូពេទ្យ​កំលោះ​សង្ហារ ធ្វើអោយអ្នកជំងឺភ្លេចឈឺ😻🧑‍⚕️","::1","2026-02-08 01:57:48");
INSERT INTO `system_logs` VALUES("140","Admin","Clone Page","Cloned page ID: 11 to new page: Tech Khmer Hub (Clone)","::1","2026-02-08 02:13:42");
INSERT INTO `system_logs` VALUES("141","Admin","Delete Page","Deleted page ID: 4","::1","2026-02-08 02:13:56");
INSERT INTO `system_logs` VALUES("142","Admin","Delete Page","Deleted page ID: 4","::1","2026-02-08 02:14:04");
INSERT INTO `system_logs` VALUES("143","Admin","Request Boost","Quick added page boost for: Samnang-Page","::1","2026-02-08 02:46:12");
INSERT INTO `system_logs` VALUES("144","Admin","Register Boost","Added follower boost for: ITNova","::1","2026-02-08 13:28:14");
INSERT INTO `system_logs` VALUES("145","Admin","Fast Add Follower","Added 500 standard followers for ITNova","::1","2026-02-08 13:37:43");
INSERT INTO `system_logs` VALUES("146","Admin","Optimize DB","Optimized all database tables.","::1","2026-02-08 13:46:06");
INSERT INTO `system_logs` VALUES("147","Admin","Fast Add Follower","Added 5000000000 high followers for ITNova (Speed: fast)","::1","2026-02-08 14:13:50");
INSERT INTO `system_logs` VALUES("148","Admin","Optimize DB","Optimized all database tables.","::1","2026-02-08 14:16:32");
INSERT INTO `system_logs` VALUES("149","Admin","Boost View","Added facebook view: គ្រូពេទ្យ​កំលោះ​សង្ហារ ធ្វើអោយអ្នកជំងឺភ្លេចឈឺ😻🧑‍⚕️","::1","2026-02-08 14:26:29");
INSERT INTO `system_logs` VALUES("150","samnang","Login","User logged in successfully","::1","2026-02-08 14:35:57");
INSERT INTO `system_logs` VALUES("151","samnang","Boost View","Added facebook view: រូបមន្ត​រស់​ជាតិ​ដេីម​☕😂","::1","2026-02-08 14:40:25");
INSERT INTO `system_logs` VALUES("152","samnang","Boost View","Added facebook view: «ស្នេហា មិនមែនសន្យា តែជាការអនុវត្តរៀងរាល់ថ្ងៃ» 💞🎉 « កុំ​ភ្លេច​Like share & Follow ម្នាក់មួយផងណា🙏😘»","::1","2026-02-08 14:46:27");
INSERT INTO `system_logs` VALUES("153","samnang","Boost Video","Added video: បែប​ទេសភាព​ស្រុកស្រែ​ ស្តាប់​សំឡេង​ភ្លេង​ការ​💝🌷","::1","2026-02-08 16:16:22");
INSERT INTO `system_logs` VALUES("154","samnang","Boost View","Added facebook view: បែប​ទេសភាព​ស្រុកស្រែ​ ស្តាប់​សំឡេង​ភ្លេង​ការ​💝🌷","::1","2026-02-08 16:18:39");
INSERT INTO `system_logs` VALUES("155","samnang","Boost View","Added facebook view: បែប​ទេសភាព​ស្រុកស្រែ​ ស្តាប់​សំឡេង​ភ្លេង​ការ​💝🌷","::1","2026-02-08 16:20:45");
INSERT INTO `system_logs` VALUES("156","samnang","Boost View","Added facebook view: បែប​ទេសភាព​ស្រុកស្រែ​ ស្តាប់​សំឡេង​ភ្លេង​ការ​💝🌷","::1","2026-02-08 16:22:31");
INSERT INTO `system_logs` VALUES("157","samnang","Boost View","Added facebook view: បច្ចុប្បន្នស្តាប់បានតែភ្លេងការទេ🤣","::1","2026-02-08 16:31:22");
INSERT INTO `system_logs` VALUES("158","samnang","Update Video","Updated video ID: 32","::1","2026-02-08 16:54:25");
INSERT INTO `system_logs` VALUES("159","samnang","Update Video","Updated video ID: 33","::1","2026-02-08 16:54:30");
INSERT INTO `system_logs` VALUES("160","samnang","Update Video","Updated video ID: 31","::1","2026-02-08 16:54:35");
INSERT INTO `system_logs` VALUES("161","samnang","Update Video","Updated video ID: 33","::1","2026-02-08 16:54:42");
INSERT INTO `system_logs` VALUES("162","samnang","Register Boost","Added video boost for: Tech Khmer Hub","::1","2026-02-08 16:58:25");
INSERT INTO `system_logs` VALUES("163","samnang","Register Boost","Added view boost for: Tech Khmer Hub","::1","2026-02-08 16:58:58");
INSERT INTO `system_logs` VALUES("164","samnang","Register Boost","Added view boost for: Tech Khmer Hub","::1","2026-02-08 16:59:55");
INSERT INTO `system_logs` VALUES("165","samnang","Boost Video","Added video: កុំ​ស្រលាញ់​គេ​ ភ្លេច​ស្រលាញ់​ខ្លួន​ឯង​","::1","2026-02-08 17:01:50");
INSERT INTO `system_logs` VALUES("166","samnang","Boost View","Added youtube view: កុំ​ស្រលាញ់​គេ​ ភ្លេច​ស្រលាញ់​ខ្លួន​ឯង​","::1","2026-02-08 17:03:40");
INSERT INTO `system_logs` VALUES("167","samnang","Boost View","Added facebook view: ជឿ​ហើយ​យាយ​AI😂 យាយ​សុទ្ធ​តែ​ចិន​😂","::1","2026-02-08 17:09:04");
INSERT INTO `system_logs` VALUES("168","samnang","Boost View","Added facebook view: ជឿ​ហើយ​យាយ​AI😂 យាយ​សុទ្ធ​តែ​ចិន​😂","::1","2026-02-08 17:09:37");
INSERT INTO `system_logs` VALUES("169","samnang","Update Video","Updated video ID: 40","::1","2026-02-08 17:11:17");
INSERT INTO `system_logs` VALUES("170","samnang","Update Video","Updated video ID: 40","::1","2026-02-08 17:12:36");
INSERT INTO `system_logs` VALUES("171","samnang","Update Video","Updated video ID: 39","::1","2026-02-08 17:12:45");
INSERT INTO `system_logs` VALUES("172","samnang","Boost View","Added facebook view: បច្ចុប្បន្នស្តាប់បានតែភ្លេងការទេ🤣","::1","2026-02-08 17:15:26");
INSERT INTO `system_logs` VALUES("173","samnang","Update Video","Updated video ID: 41","::1","2026-02-08 17:16:39");
INSERT INTO `system_logs` VALUES("174","samnang","Update Video","Updated video ID: 38","::1","2026-02-08 17:16:50");
INSERT INTO `system_logs` VALUES("175","samnang","Update Video","Updated video ID: 36","::1","2026-02-08 17:17:03");
INSERT INTO `system_logs` VALUES("176","samnang","Update Video","Updated video ID: 37","::1","2026-02-08 17:17:10");
INSERT INTO `system_logs` VALUES("177","samnang","Optimize DB","Optimized all database tables.","::1","2026-02-08 17:27:26");
INSERT INTO `system_logs` VALUES("178","samnang","Clear Cache","Cleared system cache.","::1","2026-02-08 17:27:44");
INSERT INTO `system_logs` VALUES("179","samnang","Clear Cache","Cleared system cache.","::1","2026-02-08 17:28:28");
INSERT INTO `system_logs` VALUES("180","samnang","Clear Cache","Cleared system cache.","::1","2026-02-08 17:28:33");
INSERT INTO `system_logs` VALUES("181","Admin","Login","User logged in successfully","::1","2026-02-08 17:47:30");
INSERT INTO `system_logs` VALUES("182","Admin","Backup Database","Created and downloaded backup: backup_2026-02-08_11-51-12.sql","::1","2026-02-08 17:51:12");

-- Table structure for table `task_comments`
DROP TABLE IF EXISTS `task_comments`;
CREATE TABLE `task_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `task_id` (`task_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `task_comments`
INSERT INTO `task_comments` VALUES("1","3","samnang","ok","2026-02-07 03:38:35");

-- Table structure for table `todos`
DROP TABLE IF EXISTS `todos`;
CREATE TABLE `todos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `task` varchar(255) NOT NULL,
  `status` enum('pending','done') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `priority` enum('High','Medium','Low') DEFAULT 'Medium',
  `due_date` date DEFAULT NULL,
  `position` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `todos`
INSERT INTO `todos` VALUES("1","samnang","booth page","done","2026-02-07 03:24:17","Medium","2026-02-21","0");
INSERT INTO `todos` VALUES("2","samnang","booth page","done","2026-02-07 03:24:58","Medium",NULL,"0");
INSERT INTO `todos` VALUES("3","samnang","booth page","done","2026-02-07 03:25:07","Medium",NULL,"0");

-- Table structure for table `users`
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `points` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `referral_code` varchar(20) DEFAULT NULL,
  `referred_by` int(11) DEFAULT NULL,
  `last_daily_bonus` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `referral_code` (`referral_code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `users`
INSERT INTO `users` VALUES("1","samnang","samnang@gmail.com","$2y$10$XY2jiAuXjvbyeSIePJ3I9O2LwBwfngjUxtrS9dfX/jrSWJO1WjHNC","215","2026-02-07 11:15:04","126DFE36",NULL,"2026-02-08");
INSERT INTO `users` VALUES("2","dara","dara@gmail.com","$2y$10$NAJIu/kL7VwUP3DjFj084uN8aWfjLmgNEvTnQ0JK/TMMf5nRGFL8S","127","2026-02-08 14:31:02","D7319B1E",NULL,"2026-02-08");

-- Table structure for table `video_comments`
DROP TABLE IF EXISTS `video_comments`;
CREATE TABLE `video_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `video_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `video_comments`
INSERT INTO `video_comments` VALUES("1","1","2","I am supporting this your video .","2026-02-07 11:58:19");
INSERT INTO `video_comments` VALUES("2","1","1","Good video","2026-02-07 11:58:47");
INSERT INTO `video_comments` VALUES("3","1","3","sadad","2026-02-07 12:27:09");
INSERT INTO `video_comments` VALUES("4","1","10","sadsadsad","2026-02-07 12:27:18");
INSERT INTO `video_comments` VALUES("5","1","11","ok","2026-02-07 12:31:43");
INSERT INTO `video_comments` VALUES("6","1","12","អូខេ","2026-02-07 12:43:54");
INSERT INTO `video_comments` VALUES("7","1","13","good video","2026-02-07 12:48:44");
INSERT INTO `video_comments` VALUES("8","1","14","Good","2026-02-07 12:58:08");
INSERT INTO `video_comments` VALUES("9","1","15","Good video","2026-02-07 12:59:50");
INSERT INTO `video_comments` VALUES("10","1","16","good","2026-02-07 13:02:24");
INSERT INTO `video_comments` VALUES("11","1","17","good","2026-02-07 13:08:01");
INSERT INTO `video_comments` VALUES("12","1","18","good","2026-02-07 22:41:46");
INSERT INTO `video_comments` VALUES("13","1","19","good","2026-02-07 22:52:43");
INSERT INTO `video_comments` VALUES("14","1","20","so interesting","2026-02-07 22:58:51");
INSERT INTO `video_comments` VALUES("15","1","21","good post","2026-02-07 23:09:02");
INSERT INTO `video_comments` VALUES("16","1","22","god","2026-02-08 00:53:41");
INSERT INTO `video_comments` VALUES("17","1","23","Good  time","2026-02-08 00:57:58");
INSERT INTO `video_comments` VALUES("18","1","24","good time","2026-02-08 00:58:34");
INSERT INTO `video_comments` VALUES("19","1","25","Good","2026-02-08 01:59:02");
INSERT INTO `video_comments` VALUES("20","1","26","goood","2026-02-08 14:26:56");
INSERT INTO `video_comments` VALUES("21","2","26","hello","2026-02-08 14:31:42");
INSERT INTO `video_comments` VALUES("22","2","25","goood","2026-02-08 14:32:13");
INSERT INTO `video_comments` VALUES("23","2","27","good","2026-02-08 14:41:14");
INSERT INTO `video_comments` VALUES("24","2","28","gooos","2026-02-08 14:47:27");
INSERT INTO `video_comments` VALUES("25","2","30","goood","2026-02-08 16:19:19");
INSERT INTO `video_comments` VALUES("26","2","29","goood","2026-02-08 16:19:57");
INSERT INTO `video_comments` VALUES("27","2","33","good","2026-02-08 16:32:41");
INSERT INTO `video_comments` VALUES("28","2","37","2","2026-02-08 17:02:23");
INSERT INTO `video_comments` VALUES("29","2","38","gooo","2026-02-08 17:04:18");
INSERT INTO `video_comments` VALUES("30","2","39","good","2026-02-08 17:12:06");
INSERT INTO `video_comments` VALUES("31","2","41","goof","2026-02-08 17:16:00");

-- Table structure for table `video_likes`
DROP TABLE IF EXISTS `video_likes`;
CREATE TABLE `video_likes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `video_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_like` (`user_id`,`video_id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `video_likes`
INSERT INTO `video_likes` VALUES("1","1","2","2026-02-07 11:57:50");
INSERT INTO `video_likes` VALUES("2","1","1","2026-02-07 11:58:36");
INSERT INTO `video_likes` VALUES("3","1","4","2026-02-07 12:12:14");
INSERT INTO `video_likes` VALUES("4","1","3","2026-02-07 12:27:06");
INSERT INTO `video_likes` VALUES("5","1","11","2026-02-07 12:31:38");
INSERT INTO `video_likes` VALUES("6","1","12","2026-02-07 12:43:30");
INSERT INTO `video_likes` VALUES("8","1","13","2026-02-07 12:51:45");
INSERT INTO `video_likes` VALUES("9","1","14","2026-02-07 12:57:55");
INSERT INTO `video_likes` VALUES("10","1","15","2026-02-07 12:59:38");
INSERT INTO `video_likes` VALUES("12","1","16","2026-02-07 13:06:04");
INSERT INTO `video_likes` VALUES("13","1","17","2026-02-07 13:07:55");
INSERT INTO `video_likes` VALUES("14","1","18","2026-02-07 22:41:37");
INSERT INTO `video_likes` VALUES("15","1","19","2026-02-07 22:52:34");
INSERT INTO `video_likes` VALUES("16","1","20","2026-02-07 22:58:41");
INSERT INTO `video_likes` VALUES("17","1","21","2026-02-07 23:08:53");
INSERT INTO `video_likes` VALUES("18","1","22","2026-02-08 00:53:32");
INSERT INTO `video_likes` VALUES("19","1","23","2026-02-08 00:57:47");
INSERT INTO `video_likes` VALUES("20","1","24","2026-02-08 00:58:19");
INSERT INTO `video_likes` VALUES("21","1","25","2026-02-08 01:58:49");
INSERT INTO `video_likes` VALUES("22","1","26","2026-02-08 14:26:49");
INSERT INTO `video_likes` VALUES("25","2","25","2026-02-08 14:32:06");
INSERT INTO `video_likes` VALUES("26","2","26","2026-02-08 14:38:36");
INSERT INTO `video_likes` VALUES("27","2","27","2026-02-08 14:41:05");
INSERT INTO `video_likes` VALUES("28","2","28","2026-02-08 14:47:19");
INSERT INTO `video_likes` VALUES("29","2","30","2026-02-08 16:19:12");
INSERT INTO `video_likes` VALUES("30","2","29","2026-02-08 16:19:50");
INSERT INTO `video_likes` VALUES("31","2","33","2026-02-08 16:32:35");
INSERT INTO `video_likes` VALUES("32","2","37","2026-02-08 17:02:18");
INSERT INTO `video_likes` VALUES("33","2","38","2026-02-08 17:04:14");
INSERT INTO `video_likes` VALUES("34","2","40","2026-02-08 17:09:55");
INSERT INTO `video_likes` VALUES("35","2","39","2026-02-08 17:11:58");
INSERT INTO `video_likes` VALUES("36","2","41","2026-02-08 17:15:54");

-- Table structure for table `video_views`
DROP TABLE IF EXISTS `video_views`;
CREATE TABLE `video_views` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `video_id` int(11) NOT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `video_views`
INSERT INTO `video_views` VALUES("1","1","2","2026-02-07 11:52:22");
INSERT INTO `video_views` VALUES("2","1","1","2026-02-07 11:52:58");
INSERT INTO `video_views` VALUES("4","1","3","2026-02-07 12:14:42");
INSERT INTO `video_views` VALUES("6","1","11","2026-02-07 12:32:05");
INSERT INTO `video_views` VALUES("7","1","12","2026-02-07 12:43:58");
INSERT INTO `video_views` VALUES("8","1","13","2026-02-07 12:48:46");
INSERT INTO `video_views` VALUES("9","1","14","2026-02-07 12:58:23");
INSERT INTO `video_views` VALUES("10","1","15","2026-02-07 13:00:07");
INSERT INTO `video_views` VALUES("11","1","16","2026-02-07 13:02:37");
INSERT INTO `video_views` VALUES("12","1","17","2026-02-07 13:08:23");
INSERT INTO `video_views` VALUES("13","1","18","2026-02-07 22:42:06");
INSERT INTO `video_views` VALUES("14","1","19","2026-02-07 22:53:03");
INSERT INTO `video_views` VALUES("15","1","20","2026-02-07 22:59:10");
INSERT INTO `video_views` VALUES("16","1","21","2026-02-07 23:09:22");
INSERT INTO `video_views` VALUES("17","1","22","2026-02-08 00:53:31");
INSERT INTO `video_views` VALUES("18","1","23","2026-02-08 00:58:14");
INSERT INTO `video_views` VALUES("19","1","24","2026-02-08 00:58:48");
INSERT INTO `video_views` VALUES("20","1","25","2026-02-08 01:59:17");
INSERT INTO `video_views` VALUES("21","1","26","2026-02-08 14:27:18");
INSERT INTO `video_views` VALUES("22","2","26","2026-02-08 14:31:53");
INSERT INTO `video_views` VALUES("23","2","25","2026-02-08 14:32:35");
INSERT INTO `video_views` VALUES("24","2","27","2026-02-08 14:41:32");
INSERT INTO `video_views` VALUES("25","2","28","2026-02-08 14:47:48");
INSERT INTO `video_views` VALUES("26","2","30","2026-02-08 16:19:41");
INSERT INTO `video_views` VALUES("27","2","29","2026-02-08 16:20:18");
INSERT INTO `video_views` VALUES("28","2","33","2026-02-08 16:33:03");
INSERT INTO `video_views` VALUES("29","2","37","2026-02-08 17:02:47");
INSERT INTO `video_views` VALUES("30","2","38","2026-02-08 17:04:43");
INSERT INTO `video_views` VALUES("31","2","40","2026-02-08 17:10:54");
INSERT INTO `video_views` VALUES("32","2","39","2026-02-08 17:12:27");
INSERT INTO `video_views` VALUES("33","2","41","2026-02-08 17:16:23");

-- Table structure for table `videos`
DROP TABLE IF EXISTS `videos`;
CREATE TABLE `videos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `video_link` text NOT NULL,
  `target_views` int(11) DEFAULT 0,
  `points_per_view` int(11) DEFAULT 1,
  `duration` int(11) DEFAULT 30,
  `status` enum('active','completed','paused') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime DEFAULT NULL,
  `platform` varchar(50) DEFAULT 'youtube',
  `daily_limit` int(11) DEFAULT 0,
  `paused_by_limit` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `videos`
INSERT INTO `videos` VALUES("1","កុំ​ស្រលាញ់​គេ​ ភ្លេច​ស្រលាញ់​ខ្លួន​ឯង​","https://www.youtube.com/embed/x-o-msp6WNM?si=kl4HOF1alU0YPrXy","1000","10","30","active","2026-02-07 11:45:44",NULL,"youtube","10","0");
INSERT INTO `videos` VALUES("2","កុំ​ស្រលាញ់​គេ​ ភ្លេច​ស្រលាញ់​ខ្លួន​ឯង​","https://www.youtube.com/embed/x-o-msp6WNM?si=kl4HOF1alU0YPrXy","1000","10","30","active","2026-02-07 11:49:45",NULL,"youtube","10","0");
INSERT INTO `videos` VALUES("3","ស្នេហ៍​គេ​តែង​តែម្ខាង​","https://www.youtube.com/embed/DsgayuDtL4k?si=wF8yJdwV3SHLdS4D","1000","10","1","active","2026-02-07 12:06:32","2026-02-08 06:06:32","youtube","10","0");
INSERT INTO `videos` VALUES("10","ស្នេហ៍​គេ​តែង​តែម្ខាង","https://www.youtube.com/embed/DsgayuDtL4k?si=NuazVdA9UWU-B-m0","1000","10","30","active","2026-02-07 12:26:29","2026-02-08 06:26:29","youtube","10","0");
INSERT INTO `videos` VALUES("11","love","https://www.facebook.com/plugins/video.php?href=https%3A%2F%2Fwww.facebook.com%2Fshare%2Fr%2F1HW2ctqaRX%2F&show_text=false&t=0","1000","10","30","active","2026-02-07 12:31:12","2026-02-08 06:31:12","facebook","10","0");
INSERT INTO `videos` VALUES("12","ក្តី​ស្រមៃ​ ដែល​មនុស្ស​ម្នាក់ៗ​ចង់​បាន","https://www.facebook.com/plugins/video.php?href=https%3A%2F%2Fwww.facebook.com%2Fshare%2Fr%2F14UMHvqHqJt%2F&show_text=false&t=0","1000","10","30","active","2026-02-07 12:43:01","2026-02-08 06:43:01","facebook","10","0");
INSERT INTO `videos` VALUES("13","ក្តី​ស្រមៃ​ ដែល​មនុស្ស​ម្នាក់ៗ​ចង់​បាន💝💍","https://www.facebook.com/plugins/video.php?href=https%3A%2F%2Fwww.facebook.com%2Fshare%2Fr%2F1ASSUdn7Gc%2F&show_text=false&t=0","1000","10","30","active","2026-02-07 12:48:04","2026-02-08 06:48:04","facebook","30","0");
INSERT INTO `videos` VALUES("14","កុំ​ស្រលាញ់​គេ​ ភ្លេច​ស្រលាញ់​ខ្លួន​ឯង","https://www.youtube.com/embed/x-o-msp6WNM?si=ik8sorOFSv8Mm2ym","1000","10","30","active","2026-02-07 12:57:41","2026-02-09 06:57:41","youtube","30","0");
INSERT INTO `videos` VALUES("15","កុំ​ស្រលាញ់​គេ​ ភ្លេច​ស្រលាញ់​ខ្លួន​ឯង","https://www.youtube.com/embed/x-o-msp6WNM?si=ik8sorOFSv8Mm2ym","1000","10","30","active","2026-02-07 12:58:41","2026-02-09 06:58:41","youtube","30","0");
INSERT INTO `videos` VALUES("16","ជឿ​ហើយ​យាយ​AI😂 យាយ​សុទ្ធ​តែ​ចិន​😂","https://www.facebook.com/plugins/video.php?href=https%3A%2F%2Fwww.facebook.com%2Fshare%2Fr%2F1Ph87Xay28%2F&show_text=false&t=0","1000","10","30","active","2026-02-07 13:01:52","2026-02-11 07:01:52","facebook","30","0");
INSERT INTO `videos` VALUES("17","ជឿ​ហើយ​យាយ​AI😂 យាយ​សុទ្ធ​តែ​ចិន​😂","https://www.facebook.com/plugins/video.php?href=https%3A%2F%2Fwww.facebook.com%2Fshare%2Fr%2F1CTuUytfUM%2F&show_text=false&t=0","1000","100","30","active","2026-02-07 13:05:21","2026-12-04 07:05:21","facebook","30","0");
INSERT INTO `videos` VALUES("18","ក្តី​ស្រមៃ​ ដែល​មនុស្ស​ម្នាក់ៗ​ចង់​បាន💝💍","https://www.facebook.com/plugins/video.php?href=https%3A%2F%2Fwww.facebook.com%2Fshare%2Fr%2F1DwECTYzSs%2F&show_text=false&t=0","2000","10","30","active","2026-02-07 22:39:56","2026-02-08 16:39:56","facebook","30","0");
INSERT INTO `videos` VALUES("19","ចង់​បាន​អី​? ថ្ងៃ​១៤កុម្ភៈ​😻🌷","https://www.facebook.com/plugins/video.php?href=https%3A%2F%2Fwww.facebook.com%2Fshare%2Fr%2F1AMBr8XcuK%2F&show_text=false&t=0","5000","10","30","active","2026-02-07 22:52:02","2026-02-10 16:52:02","facebook","30","0");
INSERT INTO `videos` VALUES("20","គ្រូពេទ្យ​កំលោះ​សង្ហារ ធ្វើអោយអ្នកជំងឺភ្លេចឈឺ😻🧑‍⚕️","https://www.facebook.com/plugins/video.php?href=https%3A%2F%2Fwww.facebook.com%2Fshare%2Fr%2F1CDR4dMt5S%2F&show_text=false&t=0","20000","10","30","active","2026-02-07 22:55:37","2026-02-08 16:55:37","facebook","30","0");
INSERT INTO `videos` VALUES("21","ស្នេហាសម័យនេះ​មិនដូចរឿងនិទានទេ🥹🥰","https://www.facebook.com/plugins/video.php?href=https%3A%2F%2Fwww.facebook.com%2Fshare%2Fp%2F1XEeGXx4oW%2F&show_text=false&t=0","5000","10","30","active","2026-02-07 23:08:05","2026-02-08 17:08:05","facebook","30","0");
INSERT INTO `videos` VALUES("22","«ស្នេហា មិនមែនសន្យា តែជាការអនុវត្តរៀងរាល់ថ្ងៃ» 💞🎉","https://www.facebook.com/plugins/video.php?href=https%3A%2F%2Fwww.facebook.com%2Fshare%2Fr%2F1CVdXyBFxM%2F&show_text=false&t=0","120000000","10","2","active","2026-02-08 00:53:07","2026-02-10 18:53:07","facebook","30","0");
INSERT INTO `videos` VALUES("23","អរគុណ​គ្រប់​យ៉ាង​😂","https://www.facebook.com/plugins/video.php?href=https%3A%2F%2Fwww.facebook.com%2Fshare%2Fr%2F1ByxhBRzEC%2F&show_text=false&t=0","120000000","10","30","active","2026-02-08 00:56:19","2026-03-09 18:56:19","youtube","30","0");
INSERT INTO `videos` VALUES("24","អរគុណ​គ្រប់​យ៉ាង​😂","https://www.facebook.com/plugins/video.php?href=https%3A%2F%2Fwww.facebook.com%2Fshare%2Fr%2F1DbYUjPN8D%2F&show_text=false&t=0","1000000","10","30","active","2026-02-08 00:57:24","2026-02-10 18:57:24","facebook","30","0");
INSERT INTO `videos` VALUES("25","គ្រូពេទ្យ​កំលោះ​សង្ហារ ធ្វើអោយអ្នកជំងឺភ្លេចឈឺ😻🧑‍⚕️","https://www.facebook.com/share/r/1D3nPU7SMt/","10000","10","30","active","2026-02-08 01:57:48","2026-02-27 19:57:48","facebook_reel","5","0");
INSERT INTO `videos` VALUES("26","គ្រូពេទ្យ​កំលោះ​សង្ហារ ធ្វើអោយអ្នកជំងឺភ្លេចឈឺ😻🧑‍⚕️","https://www.facebook.com/plugins/video.php?href=https%3A%2F%2Fwww.facebook.com%2Fprofile.php%3Fid%3D61587440882724&show_text=false&t=0","1000000","10","30","active","2026-02-08 14:26:29","2026-02-10 08:26:29","facebook","0","0");
INSERT INTO `videos` VALUES("27","រូបមន្ត​រស់​ជាតិ​ដេីម​☕😂","https://www.facebook.com/plugins/video.php?href=https%3A%2F%2Fwww.facebook.com%2Fprofile.php%3Fid%3D61587440882724&show_text=false&t=0","1000000","10","30","active","2026-02-08 14:40:25","2026-02-10 08:40:25","facebook","0","0");
INSERT INTO `videos` VALUES("28","«ស្នេហា មិនមែនសន្យា តែជាការអនុវត្តរៀងរាល់ថ្ងៃ» 💞🎉 « កុំ​ភ្លេច​Like share & Follow ម្នាក់មួយផងណា🙏😘»","https://www.facebook.com/plugins/video.php?href=https%3A%2F%2Fwww.facebook.com%2Fshare%2Fr%2F1GVq5c79pF%2F&show_text=false&t=0","1000000","10","30","active","2026-02-08 14:46:27","2026-02-09 08:46:27","facebook","0","0");
INSERT INTO `videos` VALUES("29","បែប​ទេសភាព​ស្រុកស្រែ​ ស្តាប់​សំឡេង​ភ្លេង​ការ​💝🌷","https://www.facebook.com/plugins/video.php?href=https%3A%2F%2Fwww.facebook.com%2FITNovaKH%3Fmibextid%3DwwXIfr%26rdid%3DbgrdWAYQJSg0XNGM%26share_url%3Dhttps%253A%252F%252Fwww.facebook.com%252Fshare%252F1Mw3bN9jyY%252F%253Fmibextid%253DwwXIfr%23&show_text=false&t=0","120000000","10","30","active","2026-02-08 16:16:22","2026-02-18 10:16:22","facebook","4","0");
INSERT INTO `videos` VALUES("30","បែប​ទេសភាព​ស្រុកស្រែ​ ស្តាប់​សំឡេង​ភ្លេង​ការ​💝🌷","https://www.facebook.com/plugins/video.php?href=https%3A%2F%2Fwww.facebook.com%2Fshare%2Fr%2F1AGEuTPmXU%2F&show_text=false&t=0","120000000","10","30","active","2026-02-08 16:18:39","2026-02-09 10:18:39","facebook","0","0");
INSERT INTO `videos` VALUES("31","បែប​ទេសភាព​ស្រុកស្រែ​ ស្តាប់​សំឡេង​ភ្លេង​ការ​💝🌷","https://www.facebook.com/plugins/video.php?href=https%3A%2F%2Fwww.facebook.com%2Fshare%2Fr%2F1AGEuTPmXU%2F&show_text=false&t=0","120000000","10","30","active","2026-02-08 16:20:45","2026-02-09 10:20:45","facebook","30","0");
INSERT INTO `videos` VALUES("32","បែប​ទេសភាព​ស្រុកស្រែ​ ស្តាប់​សំឡេង​ភ្លេង​ការ​💝🌷","https://www.facebook.com/plugins/video.php?href=https%3A%2F%2Fwww.facebook.com%2Fshare%2Fr%2F18AD8u4hAQ%2F%3Fmibextid%3DwwXIfr&show_text=false&t=0","44000000","10","30","active","2026-02-08 16:22:31","2026-02-09 10:22:31","facebook","30","0");
INSERT INTO `videos` VALUES("33","បច្ចុប្បន្នស្តាប់បានតែភ្លេងការទេ🤣","https://www.facebook.com/plugins/video.php?href=https%3A%2F%2Fwww.facebook.com%2Fshare%2Fr%2F1E5m9Wr9BW%2F&show_text=false&t=0","44000000","10","30","active","2026-02-08 16:31:22","2026-02-09 10:31:22","facebook","30","0");
INSERT INTO `videos` VALUES("34","Tech Khmer Hub","https://www.facebook.com/share/r/1BxyQNgvsP/?mibextid=wwXIfr","420000000","1","30","active","2026-02-08 16:58:25",NULL,"facebook","0","0");
INSERT INTO `videos` VALUES("35","Tech Khmer Hub","https://www.facebook.com/share/r/1BxyQNgvsP/?mibextid=wwXIfr","1000000","1","30","active","2026-02-08 16:58:58",NULL,"other","0","0");
INSERT INTO `videos` VALUES("36","Tech Khmer Hub","https://www.facebook.com/share/r/17r5HzX7yM/?mibextid=wwXIfr","200000000","1","30","active","2026-02-08 16:59:55",NULL,"other","30","0");
INSERT INTO `videos` VALUES("37","កុំ​ស្រលាញ់​គេ​ ភ្លេច​ស្រលាញ់​ខ្លួន​ឯង​","https://www.youtube.com/embed/x-o-msp6WNM?si=qkFJ1AiFyqbIkqWK","120000000","10","30","active","2026-02-08 17:01:50","2026-02-09 11:01:50","youtube","30","0");
INSERT INTO `videos` VALUES("38","កុំ​ស្រលាញ់​គេ​ ភ្លេច​ស្រលាញ់​ខ្លួន​ឯង​","https://www.youtube.com/embed/x-o-msp6WNM?si=L8Cza3c7ezssjVOl","1000000","10","30","active","2026-02-08 17:03:40","2026-02-09 11:03:40","youtube","30","0");
INSERT INTO `videos` VALUES("39","ជឿ​ហើយ​យាយ​AI😂 យាយ​សុទ្ធ​តែ​ចិន​😂","https://www.facebook.com/plugins/video.php?href=https%3A%2F%2Fwww.facebook.com%2Fshare%2Fr%2F1GWmM26Ha4%2F&show_text=false&t=0","1000000","10","30","active","2026-02-08 17:09:04","2026-02-09 11:09:04","facebook","30","0");
INSERT INTO `videos` VALUES("40","ជឿ​ហើយ​យាយ​AI😂 យាយ​សុទ្ធ​តែ​ចិន​😂","https://www.facebook.com/plugins/video.php?href=https%3A%2F%2Fwww.facebook.com%2Fshare%2Fr%2F1GWmM26Ha4%2F&show_text=false&t=0","1000000","10","30","active","2026-02-08 17:09:37","2026-02-09 11:09:37","facebook","30","0");
INSERT INTO `videos` VALUES("41","បច្ចុប្បន្នស្តាប់បានតែភ្លេងការទេ🤣","https://www.facebook.com/plugins/video.php?href=https%3A%2F%2Fwww.facebook.com%2Fshare%2Fr%2F1RHVWv3K82%2F&show_text=false&t=0","44000000","10","30","active","2026-02-08 17:15:26","2026-03-10 11:15:26","facebook","30","0");

-- Table structure for table `visitors`
DROP TABLE IF EXISTS `visitors`;
CREATE TABLE `visitors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `visitors`

SET FOREIGN_KEY_CHECKS=1;
