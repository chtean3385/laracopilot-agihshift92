-- MySQL dump generated from SQLite
-- Generated: 2026-03-30 10:31:37
-- Hotel CRM Database

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table: activity_logs
-- ----------------------------
DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_name` VARCHAR NOT NULL,
  `user_email` VARCHAR NOT NULL,
  `user_role` VARCHAR NOT NULL,
  `action` VARCHAR NOT NULL,
  `module` VARCHAR NOT NULL,
  `description` TEXT NOT NULL,
  `ip_address` VARCHAR DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `activity_logs` (`id`, `user_name`, `user_email`, `user_role`, `action`, `module`, `description`, `ip_address`, `created_at`) VALUES
  ('1', 'Admin User', 'admin@resort.com', 'Admin', 'Logged Out', 'Auth', 'Admin User logged out', '172.31.98.66', '2026-03-06 12:15:47'),
  ('2', 'Super Admin', 'superadmin@gmail.com', 'Super Admin', 'Logged In', 'Auth', 'Super Admin logged in from 172.31.98.66', '172.31.98.66', '2026-03-06 12:16:35'),
  ('3', 'Super Admin', 'superadmin@gmail.com', 'Super Admin', 'Updated', 'Roles', 'Updated role permissions: Admin', '172.31.98.66', '2026-03-06 12:16:55'),
  ('4', 'Super Admin', 'superadmin@gmail.com', 'Super Admin', 'Logged Out', 'Auth', 'Super Admin logged out', '172.31.98.66', '2026-03-06 12:23:34'),
  ('5', 'Front Desk', 'receptionist@resort.com', 'Receptionist', 'Logged In', 'Auth', 'Front Desk logged in from 172.31.98.66', '172.31.98.66', '2026-03-06 12:23:44'),
  ('6', 'Front Desk', 'receptionist@resort.com', 'Receptionist', 'Logged Out', 'Auth', 'Front Desk logged out', '172.31.98.66', '2026-03-06 12:26:03'),
  ('7', 'Admin User', 'admin@resort.com', 'Admin', 'Logged In', 'Auth', 'Admin User logged in from 172.31.98.66', '172.31.98.66', '2026-03-06 12:26:05'),
  ('8', 'Admin User', 'admin@resort.com', 'Admin', 'Created', 'Guest', 'Created guest profile: MAKWANA CHETAN PANKAJBHAI (+918460765785)', '172.31.98.66', '2026-03-06 12:31:37'),
  ('9', 'Admin User', 'admin@resort.com', 'Admin', 'Logged Out', 'Auth', 'Admin User logged out', '172.31.98.66', '2026-03-06 12:41:58'),
  ('10', 'Front Desk', 'receptionist@resort.com', 'Receptionist', 'Logged In', 'Auth', 'Front Desk logged in from 172.31.98.66', '172.31.98.66', '2026-03-06 12:42:07'),
  ('11', 'Front Desk', 'receptionist@resort.com', 'Receptionist', 'Logged Out', 'Auth', 'Front Desk logged out', '172.31.98.66', '2026-03-06 12:43:27'),
  ('12', 'Admin User', 'admin@resort.com', 'Admin', 'Logged In', 'Auth', 'Admin User logged in from 172.31.98.66', '172.31.98.66', '2026-03-06 12:43:48'),
  ('13', 'Admin User', 'admin@resort.com', 'Admin', 'Updated', 'Room', 'Updated room: 101', '172.31.98.66', '2026-03-06 12:53:01'),
  ('14', 'Admin User', 'admin@resort.com', 'Admin', 'Updated', 'Booking', 'Updated booking #BK18DC09', '172.31.98.66', '2026-03-06 12:56:55'),
  ('15', 'Admin User', 'admin@resort.com', 'Admin', 'Deleted', 'Room', 'Deleted room: 101', '172.31.98.66', '2026-03-06 12:59:48'),
  ('16', 'Admin User', 'admin@resort.com', 'Admin', 'Updated', 'Room', 'Updated room: 102', '172.31.98.66', '2026-03-06 13:00:00'),
  ('17', 'Admin User', 'admin@resort.com', 'Admin', 'Updated', 'Room', 'Updated room: 103', '172.31.98.66', '2026-03-06 13:00:05'),
  ('18', 'Admin User', 'admin@resort.com', 'Admin', 'Updated', 'Room', 'Updated room: 202', '172.31.98.66', '2026-03-06 13:00:16'),
  ('19', 'Admin User', 'admin@resort.com', 'Admin', 'Deactivated', 'Room', 'Deactivated room: V01', '172.31.98.66', '2026-03-06 13:00:44'),
  ('20', 'Admin User', 'admin@resort.com', 'Admin', 'Updated', 'Booking', 'Updated booking #BK1DBAC7', '172.31.98.66', '2026-03-06 13:05:59'),
  ('21', 'Admin User', 'admin@resort.com', 'Admin', 'Activated', 'Room', 'Re-activated room: 102', '172.31.98.66', '2026-03-06 13:07:00'),
  ('22', 'Admin User', 'admin@resort.com', 'Admin', 'Activated', 'Room', 'Re-activated room: V01', '172.31.98.66', '2026-03-06 13:07:07'),
  ('23', 'Admin User', 'admin@resort.com', 'Admin', 'Logged In', 'Auth', 'Admin User logged in from 172.31.88.66', '172.31.88.66', '2026-03-07 05:14:35'),
  ('24', 'Admin User', 'admin@resort.com', 'Admin', 'Logged In', 'Auth', 'Admin User logged in from 10.84.4.5', '10.84.4.5', '2026-03-09 11:17:10'),
  ('25', 'Admin User', 'admin@resort.com', 'Admin', 'Updated', 'Settings', 'Resort settings updated by Admin User', '10.84.0.7', '2026-03-09 11:23:02'),
  ('26', 'Admin User', 'admin@resort.com', 'Admin', 'Checked In', 'Check-In', 'Checked in: Meera Nair — Room 103 (Booking #BK1A48DA)', '10.84.0.7', '2026-03-09 11:27:29'),
  ('27', 'Admin User', 'admin@resort.com', 'Admin', 'Checked Out', 'Check-Out', 'Checked out: Meera Nair — Room 103 (Invoice #INV76D926)', '10.84.0.7', '2026-03-09 11:29:43'),
  ('28', 'Admin User', 'admin@resort.com', 'Admin', 'Checked In', 'Check-In', 'Checked in: Robert Wilson — Room PH01 (Booking #BK1BB3F0)', '10.84.0.7', '2026-03-09 11:30:10'),
  ('29', 'Admin User', 'admin@resort.com', 'Admin', 'Checked Out', 'Check-Out', 'Checked out: Robert Wilson — Room PH01 (Invoice #INV73C391)', '10.84.0.7', '2026-03-09 11:30:31'),
  ('30', 'Admin User', 'admin@resort.com', 'Admin', 'Checked In', 'Check-In', 'Checked in: Ananya Das — Room 104 (Booking #BK1C951D)', '10.84.5.38', '2026-03-09 11:31:08'),
  ('31', 'Admin User', 'admin@resort.com', 'Admin', 'Checked Out', 'Check-Out', 'Checked out: Ananya Das — Room 104 (Invoice #INV75CB64)', '10.84.5.38', '2026-03-09 11:31:19'),
  ('32', 'Admin User', 'admin@resort.com', 'Admin', 'Created', 'Booking', 'Booking #BK19BBF5 created for MAKWANA CHETAN PANKAJBHAI — Room 104', '10.84.5.38', '2026-03-09 11:32:01'),
  ('33', 'Admin User', 'admin@resort.com', 'Admin', 'Checked In', 'Check-In', 'Checked in: MAKWANA CHETAN PANKAJBHAI — Room 104 (Booking #BK19BBF5)', '10.84.5.38', '2026-03-09 11:32:07'),
  ('34', 'Admin User', 'admin@resort.com', 'Admin', 'Checked Out', 'Check-Out', 'Checked out: MAKWANA CHETAN PANKAJBHAI — Room 104 (Invoice #INVDA9839)', '10.84.0.7', '2026-03-09 11:32:45'),
  ('35', 'Admin User', 'admin@resort.com', 'Admin', 'Created', 'Booking', 'Booking #BK0B9C31 created for MAKWANA CHETAN PANKAJBHAI — Room 401', '10.84.0.7', '2026-03-09 11:35:44'),
  ('36', 'Admin User', 'admin@resort.com', 'Admin', 'Checked In', 'Check-In', 'Checked in: MAKWANA CHETAN PANKAJBHAI — Room 401 (Booking #BK0B9C31)', '10.84.0.7', '2026-03-09 11:36:15'),
  ('37', 'Admin User', 'admin@resort.com', 'Admin', 'Logged Out', 'Auth', 'Admin User logged out', '10.84.5.38', '2026-03-09 11:40:53'),
  ('38', 'Super Admin', 'superadmin@gmail.com', 'Super Admin', 'Logged In', 'Auth', 'Super Admin logged in from 10.84.0.7', '10.84.0.7', '2026-03-09 11:41:20'),
  ('39', 'Super Admin', 'superadmin@gmail.com', 'Super Admin', 'Updated', 'Roles', 'Updated role permissions: Admin', '10.84.0.7', '2026-03-09 11:41:58'),
  ('40', 'Super Admin', 'superadmin@gmail.com', 'Super Admin', 'Created', 'Booking', 'Booking #BKCCFCAF created for Ananya Das — Room 103', '10.84.5.38', '2026-03-09 11:42:52'),
  ('41', 'Super Admin', 'superadmin@gmail.com', 'Super Admin', 'Checked In', 'Check-In', 'Checked in: Ananya Das — Room 103 (Booking #BKCCFCAF)', '10.84.5.38', '2026-03-09 11:43:04'),
  ('42', 'Super Admin', 'superadmin@gmail.com', 'Super Admin', 'Logged Out', 'Auth', 'Super Admin logged out', '10.84.0.7', '2026-03-09 11:43:34'),
  ('43', 'Admin User', 'admin@resort.com', 'Admin', 'Logged In', 'Auth', 'Admin User logged in from 10.84.0.7', '10.84.0.7', '2026-03-09 11:43:35'),
  ('44', 'Admin User', 'admin@resort.com', 'Admin', 'Logged Out', 'Auth', 'Admin User logged out', '10.84.5.38', '2026-03-09 11:43:55'),
  ('45', 'Super Admin', 'superadmin@gmail.com', 'Super Admin', 'Logged In', 'Auth', 'Super Admin logged in from 10.84.0.7', '10.84.0.7', '2026-03-09 11:44:08'),
  ('46', 'Super Admin', 'superadmin@gmail.com', 'Super Admin', 'Updated', 'Roles', 'Updated role permissions: Admin', '10.84.0.7', '2026-03-09 11:44:25'),
  ('47', 'Super Admin', 'superadmin@gmail.com', 'Super Admin', 'Logged Out', 'Auth', 'Super Admin logged out', '10.84.0.7', '2026-03-09 11:44:27'),
  ('48', 'Admin User', 'admin@resort.com', 'Admin', 'Logged In', 'Auth', 'Admin User logged in from 10.84.5.38', '10.84.5.38', '2026-03-09 11:44:29'),
  ('49', 'Admin User', 'admin@resort.com', 'Admin', 'Checked Out', 'Check-Out', 'Checked out: MAKWANA CHETAN PANKAJBHAI — Room 401 (Invoice #INV0C8DDA)', '10.84.0.7', '2026-03-09 11:46:08'),
  ('50', 'Admin User', 'admin@resort.com', 'Admin', 'Checked Out', 'Check-Out', 'Checked out: Ananya Das — Room 103 (Invoice #INV301F6E)', '10.84.5.38', '2026-03-09 11:46:27'),
  ('51', 'Admin User', 'admin@resort.com', 'Admin', 'Created', 'Payment', 'Payment of ₹8,960.00 recorded for Booking #BK1A48DA (Meera Nair)', '10.84.5.38', '2026-03-09 11:48:52'),
  ('52', 'Admin User', 'admin@resort.com', 'Admin', 'Created', 'Payment', 'Payment of ₹8,640.00 recorded for Booking #BK1A9700 (Arjun Singh)', '10.84.5.38', '2026-03-09 11:49:48'),
  ('53', 'Admin User', 'admin@resort.com', 'Admin', 'Created', 'Booking', 'Booking #BK1CF752 created for Amit Verma — Room 103', '10.84.8.17', '2026-03-09 11:56:17'),
  ('54', 'Admin User', 'admin@resort.com', 'Admin', 'Checked In', 'Check-In', 'Checked in: Amit Verma — Room 103 (Booking #BK1CF752)', '10.84.8.17', '2026-03-09 11:56:56'),
  ('55', 'Admin User', 'admin@resort.com', 'Admin', 'Created', 'Guest', 'Created guest profile: Bela Makwana (+919725225519)', '10.84.8.17', '2026-03-09 11:59:15'),
  ('56', 'Admin User', 'admin@resort.com', 'Admin', 'Deactivated', 'Room', 'Deactivated room: V02', '10.84.8.17', '2026-03-09 11:59:58'),
  ('57', 'Admin User', 'admin@resort.com', 'Admin', 'Updated', 'Room', 'Updated room: 102', '10.84.4.5', '2026-03-09 12:07:42'),
  ('58', 'Admin User', 'admin@resort.com', 'Admin', 'Updated', 'Room', 'Updated room: 102', '10.84.8.17', '2026-03-09 12:08:21'),
  ('59', 'Admin User', 'admin@resort.com', 'Admin', 'Created', 'Booking', 'Booking #BK3D07A5 created for Bela Makwana — Room 102', '10.84.8.17', '2026-03-09 12:08:51'),
  ('60', 'Admin User', 'admin@resort.com', 'Admin', 'Updated', 'Booking', 'Updated booking #BK3D07A5', '10.84.4.5', '2026-03-09 12:10:17'),
  ('61', 'Admin User', 'admin@resort.com', 'Admin', 'Updated', 'Room', 'Updated room: 102', '10.84.4.5', '2026-03-09 12:11:14'),
  ('62', 'Admin User', 'admin@resort.com', 'Admin', 'Updated', 'Booking', 'Updated booking #BK3D07A5', '10.84.3.6', '2026-03-09 12:11:42'),
  ('63', 'Admin User', 'admin@resort.com', 'Admin', 'Updated', 'Booking', 'Updated booking #BK3D07A5', '10.84.4.5', '2026-03-09 12:12:02'),
  ('64', 'Admin User', 'admin@resort.com', 'Admin', 'Updated', 'Room', 'Updated room: 103', '10.84.4.5', '2026-03-09 12:12:39'),
  ('65', 'Admin User', 'admin@resort.com', 'Admin', 'Updated', 'Room', 'Updated room: 104', '10.84.8.17', '2026-03-09 12:12:44'),
  ('66', 'Admin User', 'admin@resort.com', 'Admin', 'Updated', 'Room', 'Updated room: 203', '10.84.8.17', '2026-03-09 12:12:49'),
  ('67', 'Admin User', 'admin@resort.com', 'Admin', 'Created', 'Booking', 'Booking #BK79F416 created for Rajesh Sharma — Room 104', '10.84.4.5', '2026-03-09 12:13:43'),
  ('68', 'Admin User', 'admin@resort.com', 'Admin', 'Checked In', 'Check-In', 'Checked in: Rajesh Sharma — Room 104 (Booking #BK79F416)', '10.84.8.17', '2026-03-09 12:14:10'),
  ('69', 'Admin User', 'admin@resort.com', 'Admin', 'Checked In', 'Check-In', 'Checked in: Bela Makwana — Room 102 (Booking #BK3D07A5)', '10.84.8.17', '2026-03-09 12:14:26'),
  ('70', 'Admin User', 'admin@resort.com', 'Admin', 'Updated', 'Room', 'Updated room: 102', '10.84.4.5', '2026-03-09 12:17:15'),
  ('71', 'Admin User', 'admin@resort.com', 'Admin', 'Checked Out', 'Check-Out', 'Checked out: Bela Makwana — Room 102 (Invoice #INV794F9A)', '10.84.4.5', '2026-03-09 12:17:27'),
  ('72', 'Admin User', 'admin@resort.com', 'Admin', 'Created', 'Booking', 'Booking #BK30EEA8 created for Bela Makwana — Room 102', '10.84.3.6', '2026-03-09 12:18:11'),
  ('73', 'Admin User', 'admin@resort.com', 'Admin', 'Checked In', 'Check-In', 'Checked in: Bela Makwana — Room 102 (Booking #BK30EEA8)', '10.84.3.6', '2026-03-09 12:18:30'),
  ('74', 'Admin User', 'admin@resort.com', 'Admin', 'Updated', 'Room', 'Updated room: 102', '10.84.4.5', '2026-03-09 12:21:31'),
  ('75', 'Admin User', 'admin@resort.com', 'Admin', 'Updated', 'Room', 'Updated room: 105', '10.84.8.17', '2026-03-09 12:22:02'),
  ('76', 'Admin User', 'admin@resort.com', 'Admin', 'Created', 'Booking', 'Booking #BKF6476B created for Sofia Martinez — Room 105', '10.84.4.5', '2026-03-09 12:24:47'),
  ('77', 'Admin User', 'admin@resort.com', 'Admin', 'Checked In', 'Check-In', 'Checked in: Sofia Martinez — Room 105 (Booking #BKF6476B)', '10.84.3.6', '2026-03-09 12:25:30'),
  ('78', 'Admin User', 'admin@resort.com', 'Admin', 'Updated', 'Room', 'Updated room: 201', '10.84.8.17', '2026-03-09 12:26:17'),
  ('79', 'Admin User', 'admin@resort.com', 'Admin', 'Created', 'Booking', 'Booking #BK2545E7 created for Vijay Kumar — Room 201', '10.84.4.5', '2026-03-09 12:28:02'),
  ('80', 'Admin User', 'admin@resort.com', 'Admin', 'Checked In', 'Check-In', 'Checked in: Vijay Kumar — Room 201 (Booking #BK2545E7)', '10.84.4.5', '2026-03-09 12:28:11'),
  ('81', 'Admin User', 'admin@resort.com', 'Admin', 'Created', 'Booking', 'Booking #BK93CBEF created for Suresh Pillai — Room 202', '10.84.4.5', '2026-03-09 12:29:29'),
  ('82', 'Admin User', 'admin@resort.com', 'Admin', 'Checked Out', 'Check-Out', 'Checked out: Bela Makwana — Room 102 (Invoice #INV48A6A1)', '10.84.8.17', '2026-03-09 12:32:52'),
  ('83', 'Admin User', 'admin@resort.com', 'Admin', 'Logged In', 'Auth', 'Admin User logged in from 10.84.5.38', '10.84.5.38', '2026-03-10 09:03:03'),
  ('84', 'Admin User', 'admin@resort.com', 'Admin', 'Updated', 'Settings', 'Resort settings updated by Admin User', '10.84.5.38', '2026-03-10 09:04:30'),
  ('85', 'Admin User', 'admin@resort.com', 'Admin', 'Logged Out', 'Auth', 'Admin User logged out', '10.84.2.10', '2026-03-10 09:24:06'),
  ('86', 'Super Admin', 'superadmin@gmail.com', 'Super Admin', 'Logged In', 'Auth', 'Super Admin logged in from 10.84.0.7', '10.84.0.7', '2026-03-10 09:25:10'),
  ('87', 'Super Admin', 'superadmin@gmail.com', 'Super Admin', 'Created', 'Booking', 'Booking #BK22C596 created for Arjun Singh — Room 203', '10.84.2.10', '2026-03-10 09:33:22'),
  ('88', 'Super Admin', 'superadmin@gmail.com', 'Super Admin', 'Checked In', 'Check-In', 'Checked in: Arjun Singh — Room 203 (Booking #BK22C596)', '10.84.0.7', '2026-03-10 09:33:29'),
  ('89', 'Super Admin', 'superadmin@gmail.com', 'Super Admin', 'Checked Out', 'Check-Out', 'Checked out: Amit Verma — Room 103 (Invoice #INV2E586F)', '10.84.0.7', '2026-03-10 09:33:39'),
  ('90', 'Super Admin', 'superadmin@gmail.com', 'Super Admin', 'Updated', 'Payment Links', 'Payment Links configuration saved.', '10.84.4.5', '2026-03-10 09:40:18'),
  ('91', 'Super Admin', 'superadmin@gmail.com', 'Super Admin', 'Checked Out', 'Check-Out', 'Checked out: Rajesh Sharma — Room 104 (Invoice #INV8D595F)', '10.84.5.38', '2026-03-10 09:51:04'),
  ('92', 'Super Admin', 'superadmin@gmail.com', 'Super Admin', 'Created', 'Booking', 'Booking #BKEA86BC created for Bela Makwana — Room 104', '10.84.5.38', '2026-03-10 09:51:42'),
  ('93', 'Super Admin', 'superadmin@gmail.com', 'Super Admin', 'Checked In', 'Check-In', 'Checked in: Bela Makwana — Room 104 (Booking #BKEA86BC)', '10.84.4.5', '2026-03-10 09:51:53'),
  ('94', 'Super Admin', 'superadmin@gmail.com', 'Super Admin', 'Checked Out', 'Check-Out', 'Checked out: Arjun Singh — Room 203 (Invoice #INVA38660)', '10.84.4.5', '2026-03-10 09:58:18'),
  ('95', 'Super Admin', 'superadmin@gmail.com', 'Super Admin', 'Checked Out', 'Check-Out', 'Checked out: Bela Makwana — Room 104 (Invoice #INV521047)', '10.84.4.5', '2026-03-10 10:01:57'),
  ('96', 'Super Admin', 'superadmin@gmail.com', 'Super Admin', 'Checked Out', 'Check-Out', 'Checked out: Sofia Martinez — Room 105 (Invoice #INVC67446)', '10.84.0.7', '2026-03-10 10:02:20'),
  ('97', 'Super Admin', 'superadmin@gmail.com', 'Super Admin', 'Updated', 'Payment Links', 'Payment Links configuration saved.', '10.84.2.10', '2026-03-10 11:52:10'),
  ('98', 'Super Admin', 'superadmin@gmail.com', 'Super Admin', 'Updated', 'Payment Links', 'Payment Links configuration saved.', '10.84.3.6', '2026-03-10 11:53:06'),
  ('99', 'Super Admin', 'superadmin@gmail.com', 'Super Admin', 'Checked Out', 'Check-Out', 'Checked out: Vijay Kumar — Room 201 (Invoice #INVD81203)', '10.84.3.6', '2026-03-10 11:53:17'),
  ('100', 'Super Admin', 'superadmin@gmail.com', 'Super Admin', 'Created', 'Booking', 'Booking #BKEDC294 created for Ananya Das — Room 103', '10.84.4.5', '2026-03-10 11:53:34'),
  ('101', 'Super Admin', 'superadmin@gmail.com', 'Super Admin', 'Checked In', 'Check-In', 'Checked in: Ananya Das — Room 103 (Booking #BKEDC294)', '10.84.4.5', '2026-03-10 11:53:43'),
  ('102', 'Super Admin', 'superadmin@gmail.com', 'Super Admin', 'Created', 'Booking', 'Booking #BKBDBD9E created for Amit Verma — Room 102', '10.84.5.38', '2026-03-10 12:23:24'),
  ('103', 'Admin User', 'admin@resort.com', 'Admin', 'Logged In', 'Auth', 'Admin User logged in from 10.84.5.15', '10.84.5.15', '2026-03-28 17:20:06'),
  ('104', 'Admin User', 'admin@resort.com', 'Admin', 'Logged In', 'Auth', 'Admin User logged in from 127.0.0.1', '127.0.0.1', '2026-03-28 17:34:44'),
  ('105', 'Admin User', 'admin@resort.com', 'Admin', 'Logged In', 'Auth', 'Admin User logged in from 127.0.0.1', '127.0.0.1', '2026-03-28 17:37:29'),
  ('106', 'Admin User', 'admin@resort.com', 'Admin', 'Added Guest', 'Booking', 'Added guest Zzz to Booking #BK93CBEF', '10.84.6.12', '2026-03-28 17:38:52'),
  ('107', 'Admin User', 'admin@resort.com', 'Admin', 'Signature Saved', 'Booking', 'Signature saved for guest Zzz', '10.84.6.12', '2026-03-28 17:39:13'),
  ('108', 'Admin User', 'admin@resort.com', 'Admin', 'Added Guest', 'Booking', 'Added guest Eee to Booking #BK93CBEF', '10.84.6.12', '2026-03-28 17:39:22'),
  ('109', 'Admin User', 'admin@resort.com', 'Admin', 'Signature Saved', 'Booking', 'Signature saved for guest Eee', '10.84.6.12', '2026-03-28 17:39:29'),
  ('110', 'Admin User', 'admin@resort.com', 'Admin', 'Logged In', 'Auth', 'Admin User logged in from 127.0.0.1', '127.0.0.1', '2026-03-28 17:43:59'),
  ('111', 'Admin User', 'admin@resort.com', 'Admin', 'Logged In', 'Auth', 'Admin User logged in from 127.0.0.1', '127.0.0.1', '2026-03-28 17:44:25'),
  ('112', 'Admin User', 'admin@resort.com', 'Admin', 'Logged In', 'Auth', 'Admin User logged in from 127.0.0.1', '127.0.0.1', '2026-03-28 17:57:57'),
  ('113', 'Admin User', 'admin@resort.com', 'Admin', 'Signature Saved', 'Guest', 'Primary guest signature saved for Amit Verma', '10.84.5.15', '2026-03-28 17:59:51'),
  ('114', 'Admin User', 'admin@resort.com', 'Admin', 'Logged In', 'Auth', 'Admin User logged in from 127.0.0.1', '127.0.0.1', '2026-03-28 18:08:04'),
  ('115', 'Admin User', 'admin@resort.com', 'Admin', 'Signature Saved', 'Guest', 'Primary guest signature saved for Amit Verma', '10.84.3.13', '2026-03-28 18:09:42'),
  ('116', 'Admin User', 'admin@resort.com', 'Admin', 'Logged In', 'Auth', 'Admin User logged in from 10.84.6.12', '10.84.6.12', '2026-03-30 05:03:24'),
  ('117', 'Admin User', 'admin@resort.com', 'Admin', 'Added Guest', 'Booking', 'Added guest chetann to Booking #BKBDBD9E', '10.84.6.12', '2026-03-30 05:08:23'),
  ('118', 'Admin User', 'admin@resort.com', 'Admin', 'Checked In', 'Check-In', 'Checked in: Amit Verma — Room 102 (Booking #BKBDBD9E)', '10.84.6.12', '2026-03-30 05:08:39'),
  ('119', 'Admin User', 'admin@resort.com', 'Admin', 'Created', 'Booking', 'Booking #BK5A780C created for MAKWANA CHETAN PANKAJBHAI — Room 105', '10.84.6.12', '2026-03-30 05:16:21'),
  ('120', 'Admin User', 'admin@resort.com', 'Admin', 'Logged In', 'Auth', 'Admin User logged in from 10.84.6.12', '10.84.6.12', '2026-03-30 05:20:29'),
  ('121', 'Admin User', 'admin@resort.com', 'Admin', 'Logged Out', 'Auth', 'Admin User logged out', '10.84.6.12', '2026-03-30 06:10:26'),
  ('122', 'Admin User', 'admin@resort.com', 'Admin', 'Logged In', 'Auth', 'Admin User logged in from 10.84.0.13', '10.84.0.13', '2026-03-30 06:10:28'),
  ('123', 'Admin User', 'admin@resort.com', 'Admin', 'Signature Saved', 'Guest', 'Primary guest signature saved for Suresh Pillai', '10.84.0.13', '2026-03-30 06:10:41'),
  ('124', 'Admin User', 'admin@resort.com', 'Admin', 'Logged In', 'Auth', 'Admin User logged in from 10.84.3.13', '10.84.3.13', '2026-03-30 10:17:29'),
  ('125', 'Admin User', 'admin@resort.com', 'Admin', 'Logged Out', 'Auth', 'Admin User logged out', '10.84.6.12', '2026-03-30 10:17:35');

-- ----------------------------
-- Table: booking_guests
-- ----------------------------
DROP TABLE IF EXISTS `booking_guests`;
CREATE TABLE `booking_guests` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_id` INT NOT NULL,
  `name` VARCHAR NOT NULL,
  `age` INT DEFAULT NULL,
  `gender` VARCHAR DEFAULT NULL,
  `nationality` VARCHAR NOT NULL DEFAULT 'Indian',
  `id_type` VARCHAR DEFAULT NULL,
  `id_number` VARCHAR DEFAULT NULL,
  `dob` DATE DEFAULT NULL,
  `relation` VARCHAR DEFAULT NULL,
  `signature` TEXT DEFAULT NULL,
  `id_document_path` VARCHAR DEFAULT NULL,
  `id_document_name` VARCHAR DEFAULT NULL,
  `notes` VARCHAR DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `booking_guests` (`id`, `booking_id`, `name`, `age`, `gender`, `nationality`, `id_type`, `id_number`, `dob`, `relation`, `signature`, `id_document_path`, `id_document_name`, `notes`, `created_at`, `updated_at`) VALUES
  ('1', '25', 'Zzz', '12', 'female', 'Indian', 'pan', 'Bsnjs', NULL, 'Self', 'data:image/png;base64,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', NULL, NULL, NULL, '2026-03-28 17:38:52', '2026-03-28 17:39:13'),
  ('2', '25', 'Eee', '12', 'female', 'Indian', 'pan', 'Snsn', NULL, 'Self', 'data:image/png;base64,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', NULL, NULL, NULL, '2026-03-28 17:39:22', '2026-03-28 17:39:29'),
  ('3', '29', 'chetann', '45', 'male', 'Indian', 'aadhaar', NULL, '2026-03-30 00:00:00', 'Self', NULL, 'guest-docs/29/ac0OAiLCUXGuJloCAg98wQhvzGYtGS9BnuDlWNem.png', 'imageedit_2_8470559836.png', NULL, '2026-03-30 05:08:23', '2026-03-30 05:08:23');

-- ----------------------------
-- Table: bookings
-- ----------------------------
DROP TABLE IF EXISTS `bookings`;
CREATE TABLE `bookings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_number` VARCHAR NOT NULL,
  `customer_id` INT NOT NULL,
  `room_id` INT NOT NULL,
  `check_in_date` DATE NOT NULL,
  `check_out_date` DATE NOT NULL,
  `actual_checkin_at` DATETIME DEFAULT NULL,
  `actual_checkout_at` DATETIME DEFAULT NULL,
  `nights` INT NOT NULL DEFAULT '1',
  `adults` INT NOT NULL DEFAULT '1',
  `children` INT NOT NULL DEFAULT '0',
  `total_amount` VARCHAR(255) NOT NULL DEFAULT '0',
  `advance_payment` VARCHAR(255) NOT NULL DEFAULT '0',
  `balance_due` VARCHAR(255) NOT NULL DEFAULT '0',
  `special_requests` TEXT DEFAULT NULL,
  `status` VARCHAR NOT NULL DEFAULT 'confirmed',
  `payment_status` VARCHAR NOT NULL DEFAULT 'pending',
  `checkin_notes` TEXT DEFAULT NULL,
  `checkout_notes` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  `meal_breakfast` INT NOT NULL DEFAULT '0',
  `meal_lunch` INT NOT NULL DEFAULT '0',
  `meal_dinner` INT NOT NULL DEFAULT '0',
  `meal_cost` VARCHAR(255) NOT NULL DEFAULT '0',
  `extra_beds` INT NOT NULL DEFAULT '0',
  `extra_bed_cost` VARCHAR(255) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `bookings` (`id`, `booking_number`, `customer_id`, `room_id`, `check_in_date`, `check_out_date`, `actual_checkin_at`, `actual_checkout_at`, `nights`, `adults`, `children`, `total_amount`, `advance_payment`, `balance_due`, `special_requests`, `status`, `payment_status`, `checkin_notes`, `checkout_notes`, `created_at`, `updated_at`, `meal_breakfast`, `meal_lunch`, `meal_dinner`, `meal_cost`, `extra_beds`, `extra_bed_cost`) VALUES
  ('1', 'BK18DC09', '1', '6', '2026-03-06', '2026-03-09', NULL, NULL, '3', '2', '0', '16500', '5500', '11000', NULL, 'cancelled', 'partial', NULL, NULL, '2026-03-06 10:34:09', '2026-03-06 12:56:55', '0', '0', '0', '0', '0', '0'),
  ('2', 'BK192BE6', '2', '7', '2026-03-05', '2026-03-09', '2026-03-05 14:00:00', '2026-03-06 10:40:16', '4', '2', '0', '22000', '5500', '0', NULL, 'checked_out', 'paid', NULL, NULL, '2026-03-06 10:34:09', '2026-03-06 10:40:16', '0', '0', '0', '0', '0', '0'),
  ('3', 'BK197118', '3', '10', '2026-03-06', '2026-03-08', '2026-03-06 10:53:15', '2026-03-06 10:53:23', '2', '3', '1', '19000', '9500', '0', NULL, 'checked_out', 'paid', NULL, NULL, '2026-03-06 10:34:09', '2026-03-06 10:53:23', '0', '0', '0', '0', '0', '0'),
  ('5', 'BK1A0440', '5', '13', '2026-03-05', '2026-03-12', '2026-03-05 14:00:00', '2026-03-06 10:50:32', '7', '4', '2', '126000', '36000', '0', NULL, 'checked_out', 'paid', NULL, NULL, '2026-03-06 10:34:09', '2026-03-06 10:50:32', '0', '0', '0', '0', '0', '0'),
  ('6', 'BK1A48DA', '6', '3', '2026-03-07', '2026-03-10', '2026-03-09 11:27:29', '2026-03-09 11:29:43', '3', '2', '0', '12000', '4000', '0', NULL, 'checked_out', 'paid', 'trsa asd sadsa dasd assa', NULL, '2026-03-06 10:34:09', '2026-03-09 11:48:52', '0', '0', '0', '0', '0', '0'),
  ('7', 'BK1A9700', '7', '12', '2026-03-01', '2026-03-07', '2026-03-01 14:00:00', '2026-03-07 11:00:00', '6', '2', '0', '72000', '12000', '0', NULL, 'checked_out', 'paid', NULL, NULL, '2026-03-06 10:34:09', '2026-03-09 11:49:48', '0', '0', '0', '0', '0', '0'),
  ('8', 'BK1B26B5', '8', '2', '2026-03-03', '2026-03-07', '2026-03-03 14:00:00', '2026-03-07 11:00:00', '4', '1', '0', '14000', '3500', '10500', NULL, 'checked_out', 'partial', NULL, NULL, '2026-03-06 10:34:09', '2026-03-06 10:34:09', '0', '0', '0', '0', '0', '0'),
  ('9', 'BK1BB3F0', '9', '15', '2026-03-08', '2026-03-13', '2026-03-09 11:30:10', '2026-03-09 11:30:31', '5', '6', '2', '175000', '80000', '18200', NULL, 'checked_out', 'partial', NULL, NULL, '2026-03-06 10:34:09', '2026-03-09 11:30:31', '0', '0', '0', '0', '0', '0'),
  ('10', 'BK1C01B2', '10', '8', '2026-03-06', '2026-03-10', '2026-03-06 10:54:14', '2026-03-06 10:58:51', '4', '2', '0', '24000', '6000', '0', NULL, 'checked_out', 'paid', NULL, NULL, '2026-03-06 10:34:09', '2026-03-06 10:58:51', '0', '0', '0', '0', '0', '0'),
  ('11', 'BK1C4CD3', '11', '14', '2026-03-05', '2026-03-08', '2026-03-05 14:00:00', '2026-03-06 10:43:50', '3', '4', '2', '66000', '22000', '0', NULL, 'checked_out', 'paid', NULL, NULL, '2026-03-06 10:34:09', '2026-03-06 10:43:50', '0', '0', '0', '0', '0', '0'),
  ('12', 'BK1C951D', '12', '4', '2026-03-09', '2026-03-11', '2026-03-09 11:31:08', '2026-03-09 11:31:19', '2', '2', '0', '7000', '5800', '0', NULL, 'checked_out', 'paid', NULL, NULL, '2026-03-06 10:34:09', '2026-03-09 11:31:19', '0', '0', '0', '0', '0', '0'),
  ('13', 'BK1CE4CD', '13', '11', '2026-02-27', '2026-03-07', '2026-02-27 14:00:00', '2026-03-07 11:00:00', '8', '2', '1', '76000', '14000', '62000', NULL, 'checked_out', 'partial', NULL, NULL, '2026-03-06 10:34:09', '2026-03-06 10:34:09', '0', '0', '0', '0', '0', '0'),
  ('14', 'BK1D6FAA', '14', '5', '2026-03-04', '2026-03-07', '2026-03-04 14:00:00', '2026-03-06 10:50:04', '3', '2', '0', '10500', '3500', '0', NULL, 'checked_out', 'paid', NULL, NULL, '2026-03-06 10:34:09', '2026-03-06 10:50:04', '0', '0', '0', '0', '0', '0'),
  ('15', 'BK1DBAC7', '15', '9', '2026-03-11', '2026-03-21', NULL, NULL, '10', '4', '0', '58000', '19000', '39000', NULL, 'cancelled', 'partial', NULL, NULL, '2026-03-06 10:34:09', '2026-03-06 13:05:59', '0', '0', '0', '0', '0', '0'),
  ('16', 'BK19BBF5', '16', '4', '2026-03-10 00:00:00', '2026-03-14 00:00:00', '2026-03-09 11:32:07', '2026-03-09 11:32:45', '4', '1', '0', '14000', '4000', '0', NULL, 'checked_out', 'paid', NULL, NULL, '2026-03-09 11:32:01', '2026-03-09 11:32:45', '0', '0', '0', '0', '0', '0'),
  ('17', 'BK0B9C31', '16', '12', '2026-03-09 00:00:00', '2026-03-12 00:00:00', '2026-03-09 11:36:15', '2026-03-09 11:46:08', '3', '3', '0', '36000', '2000', '0', 'test requrest', 'checked_out', 'paid', 'chekig in nots whwerwe it show not sure', NULL, '2026-03-09 11:35:44', '2026-03-09 11:46:08', '0', '0', '0', '0', '0', '0'),
  ('18', 'BKCCFCAF', '12', '3', '2026-03-09 00:00:00', '2026-03-13 00:00:00', '2026-03-09 11:43:04', '2026-03-09 11:46:26', '4', '1', '0', '16000', '0', '0', NULL, 'checked_out', 'paid', NULL, NULL, '2026-03-09 11:42:52', '2026-03-09 11:46:26', '0', '0', '0', '0', '0', '0'),
  ('19', 'BK1CF752', '3', '3', '2026-03-09 00:00:00', '2026-03-12 00:00:00', '2026-03-09 11:56:56', '2026-03-10 09:33:38', '3', '1', '1', '12000', '2950', '0', 'sads adsa dsad sad sa', 'checked_out', 'paid', '3333 5555', NULL, '2026-03-09 11:56:17', '2026-03-10 09:33:38', '0', '0', '0', '0', '0', '0'),
  ('20', 'BK3D07A5', '17', '2', '2026-03-09 00:00:00', '2026-03-12 00:00:00', '2026-03-09 12:14:26', '2026-03-09 12:17:27', '3', '1', '0', '10500', '692', '0', NULL, 'checked_out', 'paid', '666', NULL, '2026-03-09 12:08:51', '2026-03-09 12:17:27', '1', '1', '1', '0', '0', '0'),
  ('21', 'BK79F416', '1', '4', '2026-03-09 00:00:00', '2026-03-12 00:00:00', '2026-03-09 12:14:10', '2026-03-10 09:51:04', '3', '1', '0', '10500', '500', '0', '123', 'checked_out', 'paid', '3333', NULL, '2026-03-09 12:13:43', '2026-03-10 09:51:04', '0', '1', '0', '0', '0', '0'),
  ('22', 'BK30EEA8', '17', '2', '2026-03-09 00:00:00', '2026-03-12 00:00:00', '2026-03-09 12:18:30', '2026-03-09 12:32:52', '3', '1', '0', '11850', '0', '0', NULL, 'checked_out', 'paid', NULL, NULL, '2026-03-09 12:18:11', '2026-03-09 12:32:52', '1', '1', '1', '1350', '0', '0'),
  ('23', 'BKF6476B', '10', '5', '2026-03-20 00:00:00', '2026-03-22 00:00:00', '2026-03-09 12:25:30', '2026-03-10 10:02:20', '2', '3', '1', '10440', '440', '0', 'new booking', 'checked_out', 'paid', 'check in done8', NULL, '2026-03-09 12:24:47', '2026-03-10 10:02:20', '1', '1', '0', '0', '1', '3440'),
  ('24', 'BK2545E7', '5', '6', '2026-03-20 00:00:00', '2026-03-24 00:00:00', '2026-03-09 12:28:11', '2026-03-10 11:53:17', '4', '4', '1', '26000', '0', '0', NULL, 'checked_out', 'paid', NULL, NULL, '2026-03-09 12:28:02', '2026-03-10 11:53:17', '1', '1', '1', '0', '1', '4000'),
  ('25', 'BK93CBEF', '13', '7', '2026-03-28 00:00:00', '2026-03-31 00:00:00', NULL, NULL, '3', '1', '0', '16500', '0', '16500', NULL, 'confirmed', 'pending', NULL, NULL, '2026-03-09 12:29:29', '2026-03-09 12:29:29', '0', '0', '0', '0', '0', '0'),
  ('26', 'BK22C596', '7', '8', '2026-03-11 00:00:00', '2026-03-12 00:00:00', '2026-03-10 09:33:29', '2026-03-10 09:58:18', '1', '1', '0', '6000', '1000', '0', NULL, 'checked_out', 'paid', NULL, NULL, '2026-03-10 09:33:22', '2026-03-10 09:58:18', '0', '0', '0', '0', '0', '0'),
  ('27', 'BKEA86BC', '17', '4', '2026-03-11 00:00:00', '2026-03-13 00:00:00', '2026-03-10 09:51:53', '2026-03-10 10:01:57', '2', '1', '0', '7000', '1000', '0', NULL, 'checked_out', 'paid', NULL, NULL, '2026-03-10 09:51:42', '2026-03-10 10:01:57', '0', '0', '0', '0', '0', '0'),
  ('28', 'BKEDC294', '12', '3', '2026-03-10 00:00:00', '2026-03-19 00:00:00', '2026-03-10 11:53:43', NULL, '9', '1', '0', '36000', '0', '36000', NULL, 'checked_in', 'pending', NULL, NULL, '2026-03-10 11:53:34', '2026-03-10 11:53:43', '0', '0', '0', '0', '0', '0'),
  ('29', 'BKBDBD9E', '3', '2', '2026-03-10 00:00:00', '2026-03-11 00:00:00', '2026-03-30 05:08:39', NULL, '1', '1', '0', '3500', '0', '3500', NULL, 'checked_in', 'pending', NULL, NULL, '2026-03-10 12:23:23', '2026-03-30 05:08:39', '0', '0', '0', '0', '0', '0'),
  ('30', 'BK5A780C', '16', '5', '2026-03-31 00:00:00', '2026-04-01 00:00:00', NULL, NULL, '1', '1', '0', '3500', '0', '3500', NULL, 'confirmed', 'pending', NULL, NULL, '2026-03-30 05:16:21', '2026-03-30 05:16:21', '0', '0', '0', '0', '0', '0');

-- ----------------------------
-- Table: cache
-- ----------------------------
DROP TABLE IF EXISTS `cache`;
CREATE TABLE `cache` (
  `key` VARCHAR NOT NULL,
  `value` TEXT NOT NULL,
  `expiration` INT NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
  ('laravel-cache-pathik_pending_nhsgiPuYmenVn49oRNotmQ1P', 'a:22:{s:10:"booking_id";s:2:"29";s:14:"booking_number";s:8:"BKBDBD9E";s:4:"name";s:10:"Amit Verma";s:5:"email";s:20:"amit.verma@email.com";s:5:"phone";s:15:"+91 76543 21098";s:7:"address";s:14:"78 Civil Lines";s:4:"city";s:5:"Delhi";s:5:"state";s:5:"Delhi";s:7:"country";s:5:"India";s:11:"nationality";s:6:"Indian";s:7:"id_type";s:15:"driving_license";s:9:"id_number";s:15:"DL0120199903456";s:13:"date_of_birth";s:10:"1982-11-10";s:13:"check_in_date";s:10:"2026-03-10";s:14:"check_out_date";s:10:"2026-03-11";s:6:"nights";s:1:"1";s:6:"adults";s:1:"1";s:8:"children";s:1:"0";s:11:"room_number";s:3:"102";s:9:"room_type";s:8:"standard";s:12:"total_amount";s:7:"3500.00";s:10:"_stored_at";s:27:"2026-03-10T12:23:31.207299Z";}', '1773149011');

-- ----------------------------
-- Table: cache_locks
-- ----------------------------
DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE `cache_locks` (
  `key` VARCHAR NOT NULL,
  `owner` VARCHAR NOT NULL,
  `expiration` INT NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: channel_bookings
-- ----------------------------
DROP TABLE IF EXISTS `channel_bookings`;
CREATE TABLE `channel_bookings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `channel` VARCHAR NOT NULL DEFAULT 'other',
  `ota_booking_id` VARCHAR NOT NULL,
  `guest_name` VARCHAR NOT NULL,
  `guest_phone` VARCHAR DEFAULT NULL,
  `guest_email` VARCHAR DEFAULT NULL,
  `room_id` INT DEFAULT NULL,
  `check_in_date` DATE NOT NULL,
  `check_out_date` DATE NOT NULL,
  `nights` INT NOT NULL DEFAULT '1',
  `rate_per_night` VARCHAR(255) NOT NULL DEFAULT '0',
  `total_amount` VARCHAR(255) NOT NULL DEFAULT '0',
  `commission_pct` VARCHAR(255) NOT NULL DEFAULT '0',
  `net_amount` VARCHAR(255) NOT NULL DEFAULT '0',
  `status` VARCHAR NOT NULL DEFAULT 'pending',
  `converted_booking_id` INT DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `raw_data` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: channel_manager_configs
-- ----------------------------
DROP TABLE IF EXISTS `channel_manager_configs`;
CREATE TABLE `channel_manager_configs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider` VARCHAR NOT NULL DEFAULT 'ezee',
  `api_key` VARCHAR DEFAULT NULL,
  `api_secret` VARCHAR DEFAULT NULL,
  `hotel_code` VARCHAR DEFAULT NULL,
  `property_id` VARCHAR DEFAULT NULL,
  `is_active` INT NOT NULL DEFAULT '0',
  `last_synced_at` DATETIME DEFAULT NULL,
  `extra_config` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `channel_manager_configs` (`id`, `provider`, `api_key`, `api_secret`, `hotel_code`, `property_id`, `is_active`, `last_synced_at`, `extra_config`, `created_at`, `updated_at`) VALUES
  ('1', 'staah', NULL, NULL, NULL, NULL, '0', NULL, NULL, '2026-03-10 11:46:30', '2026-03-10 11:50:11');

-- ----------------------------
-- Table: channel_room_mappings
-- ----------------------------
DROP TABLE IF EXISTS `channel_room_mappings`;
CREATE TABLE `channel_room_mappings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `room_id` INT NOT NULL,
  `channel_room_code` VARCHAR NOT NULL,
  `channel_rate_plan` VARCHAR DEFAULT NULL,
  `extra_bed_rate` VARCHAR(255) NOT NULL DEFAULT '0',
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: customer_documents
-- ----------------------------
DROP TABLE IF EXISTS `customer_documents`;
CREATE TABLE `customer_documents` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id` INT NOT NULL,
  `document_type` VARCHAR NOT NULL,
  `document_number` VARCHAR DEFAULT NULL,
  `file_name` VARCHAR NOT NULL,
  `file_path` VARCHAR NOT NULL,
  `file_type` VARCHAR DEFAULT NULL,
  `file_size` INT DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `customer_documents` (`id`, `customer_id`, `document_type`, `document_number`, `file_name`, `file_path`, `file_type`, `file_size`, `notes`, `created_at`, `updated_at`) VALUES
  ('1', '3', 'Aadhaar Card', NULL, 'ANS BILLION GST NO. (3).pdf', 'documents/3/1772794987_ANS_BILLION_GST_NO.__3_.pdf', 'application/pdf', '69867', NULL, '2026-03-06 11:03:07', '2026-03-06 11:03:07'),
  ('2', '16', 'Aadhaar Card', NULL, '301803673_587896099585539_8750685562881422434_n.jpg', 'documents/16/1772800297_301803673_587896099585539_8750685562881422434_n.jpg', 'image/jpeg', '122559', NULL, '2026-03-06 12:31:37', '2026-03-06 12:31:37'),
  ('3', '17', 'Aadhaar Card', NULL, 'Kingsley_Palace_Luxury_Brochure.pdf', 'documents/17/1773057555_Kingsley_Palace_Luxury_Brochure.pdf', 'application/pdf', '630962', NULL, '2026-03-09 11:59:15', '2026-03-09 11:59:15');

-- ----------------------------
-- Table: customers
-- ----------------------------
DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR NOT NULL,
  `email` VARCHAR DEFAULT NULL,
  `phone` VARCHAR NOT NULL,
  `address` TEXT DEFAULT NULL,
  `city` VARCHAR DEFAULT NULL,
  `state` VARCHAR DEFAULT NULL,
  `country` VARCHAR NOT NULL DEFAULT 'India',
  `id_type` VARCHAR NOT NULL DEFAULT 'aadhaar',
  `id_number` VARCHAR NOT NULL,
  `date_of_birth` DATE DEFAULT NULL,
  `nationality` VARCHAR NOT NULL DEFAULT 'Indian',
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  `signature` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `customers` (`id`, `name`, `email`, `phone`, `address`, `city`, `state`, `country`, `id_type`, `id_number`, `date_of_birth`, `nationality`, `notes`, `created_at`, `updated_at`, `signature`) VALUES
  ('1', 'Rajesh Sharma', 'rajesh.sharma@email.com', '+91 98765 43210', '12 MG Road', 'Mumbai', 'Maharashtra', 'India', 'aadhaar', '1234 5678 9012', '1985-06-15', 'Indian', NULL, '2026-03-06 10:34:09', '2026-03-06 10:34:09', NULL),
  ('2', 'Priya Patel', 'priya.patel@email.com', '+91 87654 32109', '45 Ring Road', 'Ahmedabad', 'Gujarat', 'India', 'passport', 'J8234561', '1990-03-22', 'Indian', NULL, '2026-03-06 10:34:09', '2026-03-06 10:34:09', NULL),
  ('3', 'Amit Verma', 'amit.verma@email.com', '+91 76543 21098', '78 Civil Lines', 'Delhi', 'Delhi', 'India', 'driving_license', 'DL0120199903456', '1982-11-10', 'Indian', NULL, '2026-03-06 10:34:09', '2026-03-28 18:09:42', 'data:image/png;base64,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'),
  ('4', 'Sunita Reddy', 'sunita.reddy@email.com', '+91 65432 10987', '23 Jubilee Hills', 'Hyderabad', 'Telangana', 'India', 'aadhaar', '9876 5432 1098', '1995-07-08', 'Indian', NULL, '2026-03-06 10:34:09', '2026-03-06 10:34:09', NULL),
  ('5', 'Vijay Kumar', 'vijay.kumar@email.com', '+91 54321 09876', '56 Koramangala', 'Bangalore', 'Karnataka', 'India', 'voter_id', 'KAR0412345', '1978-12-25', 'Indian', NULL, '2026-03-06 10:34:09', '2026-03-06 10:34:09', NULL),
  ('6', 'Meera Nair', 'meera.nair@email.com', '+91 43210 98765', '89 Marine Drive', 'Kochi', 'Kerala', 'India', 'passport', 'Z9876543', '1992-04-18', 'Indian', NULL, '2026-03-06 10:34:09', '2026-03-06 10:34:09', NULL),
  ('7', 'Arjun Singh', 'arjun.singh@email.com', '+91 32109 87654', '34 Park Street', 'Kolkata', 'West Bengal', 'India', 'aadhaar', '5678 9012 3456', '1988-09-30', 'Indian', NULL, '2026-03-06 10:34:09', '2026-03-06 10:34:09', NULL),
  ('8', 'Kavitha Iyer', 'kavitha.iyer@email.com', '+91 21098 76543', '67 Anna Nagar', 'Chennai', 'Tamil Nadu', 'India', 'driving_license', 'TN0220187654', '1993-02-14', 'Indian', NULL, '2026-03-06 10:34:09', '2026-03-06 10:34:09', NULL),
  ('9', 'Robert Wilson', 'robert.wilson@email.com', '+44 7700 900123', '10 Oxford Street', 'London', 'England', 'UK', 'passport', 'GB1234567', '1975-05-20', 'British', NULL, '2026-03-06 10:34:09', '2026-03-06 10:34:09', NULL),
  ('10', 'Sofia Martinez', 'sofia.martinez@email.com', '+34 600 123456', '25 Gran Via', 'Madrid', 'Madrid', 'Spain', 'passport', 'ES9876543', '1987-08-12', 'Spanish', NULL, '2026-03-06 10:34:09', '2026-03-06 10:34:09', NULL),
  ('11', 'Deepak Joshi', 'deepak.joshi@email.com', '+91 91234 56789', '15 Sector 18', 'Noida', 'UP', 'India', 'aadhaar', '2345 6789 0123', '1983-01-07', 'Indian', NULL, '2026-03-06 10:34:09', '2026-03-06 10:34:09', NULL),
  ('12', 'Ananya Das', 'ananya.das@email.com', '+91 81234 56789', '42 Salt Lake', 'Kolkata', 'West Bengal', 'India', 'passport', 'M1234567', '1997-10-05', 'Indian', NULL, '2026-03-06 10:34:09', '2026-03-06 10:34:09', NULL),
  ('13', 'Suresh Pillai', 'suresh.pillai@email.com', '+91 71234 56789', '88 Nungambakkam', 'Chennai', 'Tamil Nadu', 'India', 'voter_id', 'TN0523456', '1980-03-28', 'Indian', NULL, '2026-03-06 10:34:09', '2026-03-30 06:10:41', 'data:image/png;base64,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'),
  ('14', 'Ritu Agarwal', 'ritu.agarwal@email.com', '+91 61234 56789', '33 Lalbagh', 'Lucknow', 'UP', 'India', 'aadhaar', '6789 0123 4567', '1991-06-17', 'Indian', NULL, '2026-03-06 10:34:09', '2026-03-06 10:34:09', NULL),
  ('15', 'James Thompson', 'james.thompson@email.com', '+1 555 234 5678', '500 Fifth Avenue', 'New York', 'NY', 'USA', 'passport', 'US1234567', '1970-11-02', 'American', NULL, '2026-03-06 10:34:09', '2026-03-06 10:34:09', NULL),
  ('16', 'MAKWANA CHETAN PANKAJBHAI', 'chetanmakwana3385@gmail.com', '+918460765785', 'A-302 PRAMUKH ELEGANCE\r\nNear Raysan Petrol Pump', 'Gandhinagar', 'Gujarat', 'India', 'aadhaar', '', '2026-03-06 00:00:00', 'Indian', NULL, '2026-03-06 12:31:37', '2026-03-06 12:31:37', NULL),
  ('17', 'Bela Makwana', 'bela@gmail.com', '+919725225519', NULL, NULL, NULL, 'India', 'aadhaar', '', NULL, 'Indian', 'speical guest', '2026-03-09 11:59:15', '2026-03-09 11:59:15', NULL);

-- ----------------------------
-- Table: failed_jobs
-- ----------------------------
DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` VARCHAR NOT NULL,
  `connection` TEXT NOT NULL,
  `queue` TEXT NOT NULL,
  `payload` TEXT NOT NULL,
  `exception` TEXT NOT NULL,
  `failed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: invoices
-- ----------------------------
DROP TABLE IF EXISTS `invoices`;
CREATE TABLE `invoices` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_number` VARCHAR NOT NULL,
  `booking_id` INT NOT NULL,
  `customer_id` INT NOT NULL,
  `total_amount` VARCHAR(255) NOT NULL DEFAULT '0',
  `paid_amount` VARCHAR(255) NOT NULL DEFAULT '0',
  `balance` VARCHAR(255) NOT NULL DEFAULT '0',
  `status` VARCHAR NOT NULL DEFAULT 'unpaid',
  `issued_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  `razorpay_payment_link_id` VARCHAR DEFAULT NULL,
  `razorpay_payment_link_url` VARCHAR DEFAULT NULL,
  `razorpay_payment_link_status` VARCHAR DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `invoices` (`id`, `invoice_number`, `booking_id`, `customer_id`, `total_amount`, `paid_amount`, `balance`, `status`, `issued_at`, `created_at`, `updated_at`, `razorpay_payment_link_id`, `razorpay_payment_link_url`, `razorpay_payment_link_status`) VALUES
  ('1', 'INV1B00BE', '7', '7', '72000', '80640', '0', 'paid', '2026-03-07 00:00:00', '2026-03-07 00:00:00', '2026-03-09 11:49:48', NULL, NULL, NULL),
  ('2', 'INV1B8E9C', '8', '8', '14000', '14000', '1680', 'partial', '2026-03-07 00:00:00', '2026-03-07 00:00:00', '2026-03-09 11:39:48', NULL, NULL, NULL),
  ('3', 'INV1D48E5', '13', '13', '76000', '76000', '9120', 'partial', '2026-03-07 00:00:00', '2026-03-07 00:00:00', '2026-03-09 11:39:48', NULL, NULL, NULL),
  ('4', 'INV0DD25A', '2', '2', '22000', '22000', '2640', 'partial', '2026-03-06 10:40:16', '2026-03-06 10:40:16', '2026-03-09 11:39:48', NULL, NULL, NULL),
  ('5', 'INV68A23C', '11', '11', '66000', '66000', '7920', 'partial', '2026-03-06 10:43:50', '2026-03-06 10:43:50', '2026-03-09 11:39:48', NULL, NULL, NULL),
  ('7', 'INVCCFDE9', '14', '14', '10500', '10500', '1260', 'partial', '2026-03-06 10:50:04', '2026-03-06 10:50:04', '2026-03-09 11:39:48', NULL, NULL, NULL),
  ('8', 'INV8183A0', '5', '5', '126000', '126000', '15120', 'partial', '2026-03-06 10:50:32', '2026-03-06 10:50:32', '2026-03-09 11:39:48', NULL, NULL, NULL),
  ('9', 'INV36541A', '3', '3', '19000', '19000', '2280', 'partial', '2026-03-06 10:53:23', '2026-03-06 10:53:23', '2026-03-09 11:39:48', NULL, NULL, NULL),
  ('10', 'INVB5C11A', '10', '10', '24000', '26880', '0', 'paid', '2026-03-06 10:58:51', '2026-03-06 10:58:51', '2026-03-06 10:58:51', NULL, NULL, NULL),
  ('11', 'INV76D926', '6', '6', '12000', '13440', '0', 'paid', '2026-03-09 11:29:43', '2026-03-09 11:29:43', '2026-03-09 11:48:52', NULL, NULL, NULL),
  ('12', 'INV73C391', '9', '9', '175000', '156800', '39200', 'partial', '2026-03-09 11:30:31', '2026-03-09 11:30:31', '2026-03-09 11:39:48', NULL, NULL, NULL),
  ('13', 'INV75CB64', '12', '12', '7000', '7840', '0', 'paid', '2026-03-09 11:31:19', '2026-03-09 11:31:19', '2026-03-09 11:31:19', NULL, NULL, NULL),
  ('14', 'INVDA9839', '16', '16', '14000', '19600', '0', 'paid', '2026-03-09 11:32:45', '2026-03-09 11:32:45', '2026-03-09 11:32:45', NULL, NULL, NULL),
  ('15', 'INV0C8DDA', '17', '16', '36000', '40320', '0', 'paid', '2026-03-09 11:46:08', '2026-03-09 11:46:08', '2026-03-09 11:46:08', NULL, NULL, NULL),
  ('16', 'INV301F6E', '18', '12', '16000', '17920', '0', 'paid', '2026-03-09 11:46:27', '2026-03-09 11:46:27', '2026-03-09 11:46:27', NULL, NULL, NULL),
  ('17', 'INV794F9A', '20', '17', '10500', '11760', '0', 'paid', '2026-03-09 12:17:27', '2026-03-09 12:17:27', '2026-03-09 12:17:27', NULL, NULL, NULL),
  ('18', 'INV48A6A1', '22', '17', '11850', '13272', '0', 'paid', '2026-03-09 12:32:52', '2026-03-09 12:32:52', '2026-03-09 12:32:52', NULL, NULL, NULL),
  ('19', 'INV2E586F', '19', '3', '12000', '13440', '0', 'paid', '2026-03-10 09:33:38', '2026-03-10 09:33:38', '2026-03-10 09:33:38', NULL, NULL, NULL),
  ('20', 'INV8D595F', '21', '1', '10500', '11760', '0', 'paid', '2026-03-10 09:51:04', '2026-03-10 09:51:04', '2026-03-10 09:51:04', NULL, NULL, NULL),
  ('21', 'INVA38660', '26', '7', '6000', '13440', '0', 'paid', '2026-03-10 09:58:18', '2026-03-10 09:58:18', '2026-03-10 09:58:18', NULL, NULL, NULL),
  ('22', 'INV521047', '27', '17', '7000', '11760', '0', 'paid', '2026-03-10 10:01:57', '2026-03-10 10:01:57', '2026-03-10 10:01:57', NULL, NULL, NULL),
  ('23', 'INVC67446', '23', '10', '10440', '54812.8', '0', 'paid', '2026-03-10 10:02:20', '2026-03-10 10:02:20', '2026-03-10 10:02:20', NULL, NULL, NULL),
  ('24', 'INVD81203', '24', '5', '26000', '96880', '0', 'paid', '2026-03-10 11:53:17', '2026-03-10 11:53:17', '2026-03-10 11:53:17', NULL, NULL, NULL);

-- ----------------------------
-- Table: job_batches
-- ----------------------------
DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE `job_batches` (
  `id` VARCHAR NOT NULL,
  `name` VARCHAR NOT NULL,
  `total_jobs` INT NOT NULL,
  `pending_jobs` INT NOT NULL,
  `failed_jobs` INT NOT NULL,
  `failed_job_ids` TEXT NOT NULL,
  `options` TEXT DEFAULT NULL,
  `cancelled_at` INT DEFAULT NULL,
  `created_at` INT NOT NULL,
  `finished_at` INT DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: jobs
-- ----------------------------
DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue` VARCHAR NOT NULL,
  `payload` TEXT NOT NULL,
  `attempts` INT NOT NULL,
  `reserved_at` INT DEFAULT NULL,
  `available_at` INT NOT NULL,
  `created_at` INT NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: migrations
-- ----------------------------
DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` VARCHAR NOT NULL,
  `batch` INT NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
  ('1', '0001_01_01_000000_create_users_table', '1'),
  ('2', '0001_01_01_000001_create_cache_table', '1'),
  ('3', '0001_01_01_000002_create_jobs_table', '1'),
  ('4', '2024_01_01_000010_create_customers_table', '1'),
  ('5', '2024_01_01_000011_create_customer_documents_table', '1'),
  ('6', '2024_01_01_000012_create_rooms_table', '1'),
  ('7', '2024_01_01_000013_create_bookings_table', '1'),
  ('8', '2024_01_01_000014_create_payments_table', '1'),
  ('9', '2024_01_01_000015_create_settings_table', '1'),
  ('10', '2024_01_01_100000_create_customers_table', '1'),
  ('11', '2024_01_01_100001_create_customer_documents_table', '1'),
  ('12', '2024_01_01_100002_create_rooms_table', '1'),
  ('13', '2024_01_01_100003_create_bookings_table', '1'),
  ('14', '2024_01_01_100004_create_payments_table', '1'),
  ('15', '2024_01_01_100005_create_invoices_table', '1'),
  ('16', '2024_01_01_100006_create_settings_table', '1'),
  ('17', '2026_03_06_110757_make_id_number_nullable_on_customers', '2'),
  ('18', '2026_03_06_111328_add_tagline_to_settings_table', '3'),
  ('19', '2026_03_06_112752_create_permissions_table', '4'),
  ('20', '2026_03_06_112752_create_roles_table', '4'),
  ('21', '2026_03_06_112753_create_activity_logs_table', '4'),
  ('22', '2026_03_06_112753_create_role_permissions_table', '4'),
  ('23', '2026_03_06_122537_add_role_to_users_table', '5'),
  ('24', '2026_03_09_120343_add_meal_options_to_rooms_table', '6'),
  ('25', '2026_03_09_120343_add_meal_plan_to_bookings_table', '6'),
  ('26', '2026_03_09_121807_add_extra_bed_to_bookings_table', '7'),
  ('27', '2026_03_09_121807_add_extra_bed_to_rooms_table', '7'),
  ('28', '2026_03_10_091102_create_modules_table', '8'),
  ('29', '2026_03_10_091102_create_whatsapp_configs_table', '8'),
  ('30', '2026_03_10_091103_create_whatsapp_templates_table', '8'),
  ('31', '2026_03_10_200000_create_payment_link_configs_table', '9'),
  ('32', '2026_03_10_113246_create_channel_manager_configs_table', '10'),
  ('33', '2026_03_10_113246_create_channel_room_mappings_table', '10'),
  ('34', '2026_03_10_113247_create_channel_bookings_table', '10'),
  ('35', '2026_03_10_000001_create_pathik_configs_table', '11'),
  ('36', '2026_03_10_210001_add_new_whatsapp_templates', '12'),
  ('37', '2026_03_10_210002_create_booking_guests_table', '12'),
  ('38', '2026_03_28_175437_add_signature_to_customers_table', '13');

-- ----------------------------
-- Table: modules
-- ----------------------------
DROP TABLE IF EXISTS `modules`;
CREATE TABLE `modules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR NOT NULL,
  `name` VARCHAR NOT NULL,
  `description` TEXT DEFAULT NULL,
  `is_enabled` INT NOT NULL DEFAULT '0',
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `modules` (`id`, `slug`, `name`, `description`, `is_enabled`, `created_at`, `updated_at`) VALUES
  ('1', 'whatsapp', 'WhatsApp Automation', 'Send automated WhatsApp messages on booking, check-in reminders, and check-out.', '1', '2026-03-10 09:14:03', '2026-03-10 11:51:06'),
  ('2', 'payment_links', 'Payment Links', 'Generate UPI QR codes and Razorpay payment links from invoices and bookings.', '1', '2026-03-10 09:14:03', '2026-03-10 11:51:07'),
  ('3', 'pathik', 'Pathik Autofill', 'Auto-fill Gujarat Pathik portal with guest data from the CRM via Chrome extension.', '1', '2026-03-10 09:14:03', '2026-03-10 12:02:15'),
  ('4', 'channel_manager', 'OTA Channel Manager', 'Sync room availability and rates with OTA platforms like eZee, STAAH, SiteMinder.', '1', '2026-03-10 09:14:03', '2026-03-10 11:46:02');

-- ----------------------------
-- Table: password_reset_tokens
-- ----------------------------
DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `email` VARCHAR NOT NULL,
  `token` VARCHAR NOT NULL,
  `created_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: pathik_configs
-- ----------------------------
DROP TABLE IF EXISTS `pathik_configs`;
CREATE TABLE `pathik_configs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `api_token` VARCHAR DEFAULT NULL,
  `is_active` INT NOT NULL DEFAULT '0',
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `pathik_configs` (`id`, `api_token`, `is_active`, `created_at`, `updated_at`) VALUES
  ('1', 'iDWb5VENZj1oBYpkAVvkOw8nxpzlUlqG', '0', '2026-03-10 12:02:32', '2026-03-10 12:02:32');

-- ----------------------------
-- Table: payment_link_configs
-- ----------------------------
DROP TABLE IF EXISTS `payment_link_configs`;
CREATE TABLE `payment_link_configs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `upi_id` VARCHAR DEFAULT NULL,
  `upi_name` VARCHAR DEFAULT NULL,
  `upi_enabled` INT NOT NULL DEFAULT '0',
  `razorpay_key_id` VARCHAR DEFAULT NULL,
  `razorpay_key_secret` VARCHAR DEFAULT NULL,
  `razorpay_enabled` INT NOT NULL DEFAULT '0',
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `payment_link_configs` (`id`, `upi_id`, `upi_name`, `upi_enabled`, `razorpay_key_id`, `razorpay_key_secret`, `razorpay_enabled`, `created_at`, `updated_at`) VALUES
  ('1', '8460765785@okbizaxis', 'Dreams Technology', '1', NULL, NULL, '0', '2026-03-10 09:39:25', '2026-03-10 11:53:06');

-- ----------------------------
-- Table: payments
-- ----------------------------
DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_id` INT NOT NULL,
  `customer_id` INT NOT NULL,
  `amount` VARCHAR(255) NOT NULL DEFAULT '0',
  `payment_method` VARCHAR NOT NULL DEFAULT 'cash',
  `payment_type` VARCHAR NOT NULL DEFAULT 'advance',
  `status` VARCHAR NOT NULL DEFAULT 'completed',
  `transaction_id` VARCHAR DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `payments` (`id`, `booking_id`, `customer_id`, `amount`, `payment_method`, `payment_type`, `status`, `transaction_id`, `notes`, `created_at`, `updated_at`) VALUES
  ('1', '1', '1', '5500', 'upi', 'advance', 'completed', 'TXNDA19053F', NULL, '2026-03-06 00:00:00', '2026-03-06 00:00:00'),
  ('2', '2', '2', '5500', 'upi', 'advance', 'completed', 'TXNDA194DC4', NULL, '2026-03-05 00:00:00', '2026-03-05 00:00:00'),
  ('3', '3', '3', '9500', 'card', 'advance', 'completed', 'TXNDA1992D0', NULL, '2026-03-06 00:00:00', '2026-03-06 00:00:00'),
  ('5', '5', '5', '36000', 'upi', 'advance', 'completed', 'TXNDA1A25C4', NULL, '2026-03-05 00:00:00', '2026-03-05 00:00:00'),
  ('6', '6', '6', '4000', 'card', 'advance', 'completed', 'TXNDA1A71EA', NULL, '2026-03-07 00:00:00', '2026-03-07 00:00:00'),
  ('7', '7', '7', '12000', 'cash', 'advance', 'completed', 'TXNDA1AB959', NULL, '2026-03-01 00:00:00', '2026-03-01 00:00:00'),
  ('8', '7', '7', '60000', 'cash', 'final', 'completed', 'TXNDA1ADC9C', NULL, '2026-03-07 00:00:00', '2026-03-07 00:00:00'),
  ('9', '8', '8', '3500', 'cash', 'advance', 'completed', 'TXNDA1B49C6', NULL, '2026-03-03 00:00:00', '2026-03-03 00:00:00'),
  ('10', '8', '8', '10500', 'cash', 'final', 'completed', 'TXNDA1B6E22', NULL, '2026-03-07 00:00:00', '2026-03-07 00:00:00'),
  ('11', '9', '9', '70000', 'card', 'advance', 'completed', 'TXNDA1BD887', NULL, '2026-03-08 00:00:00', '2026-03-08 00:00:00'),
  ('12', '10', '10', '6000', 'cash', 'advance', 'completed', 'TXNDA1C26F5', NULL, '2026-03-06 00:00:00', '2026-03-06 00:00:00'),
  ('13', '11', '11', '22000', 'cash', 'advance', 'completed', 'TXNDA1C6C2E', NULL, '2026-03-05 00:00:00', '2026-03-05 00:00:00'),
  ('14', '12', '12', '5800', 'card', 'advance', 'completed', 'TXNDA1CBADA', NULL, '2026-03-09 00:00:00', '2026-03-09 00:00:00'),
  ('15', '13', '13', '14000', 'card', 'advance', 'completed', 'TXNDA1D05E2', NULL, '2026-02-27 00:00:00', '2026-02-27 00:00:00'),
  ('16', '13', '13', '62000', 'cash', 'final', 'completed', 'TXNDA1D2698', NULL, '2026-03-07 00:00:00', '2026-03-07 00:00:00'),
  ('17', '14', '14', '3500', 'card', 'advance', 'completed', 'TXNDA1D927B', NULL, '2026-03-04 00:00:00', '2026-03-04 00:00:00'),
  ('18', '15', '15', '19000', 'cash', 'advance', 'completed', 'TXNDA1DE029', NULL, '2026-03-11 00:00:00', '2026-03-11 00:00:00'),
  ('19', '2', '2', '16500', 'cash', 'final', 'completed', 'TXNF10D1086', 'Final payment at check-out', '2026-03-06 10:40:16', '2026-03-06 10:40:16'),
  ('20', '11', '11', '44000', 'cash', 'final', 'completed', 'TXNFE65899B', 'Final payment at check-out', '2026-03-06 10:43:50', '2026-03-06 10:43:50'),
  ('22', '14', '14', '7000', 'cash', 'final', 'completed', 'TXN15CC6FE7', 'Final payment at check-out', '2026-03-06 10:50:04', '2026-03-06 10:50:04'),
  ('23', '5', '5', '90000', 'bank_transfer', 'final', 'completed', 'TXN17807E75', 'Final payment at check-out', '2026-03-06 10:50:32', '2026-03-06 10:50:32'),
  ('24', '3', '3', '9500', 'cash', 'final', 'completed', 'TXN2235EF58', 'Final payment at check-out', '2026-03-06 10:53:23', '2026-03-06 10:53:23'),
  ('25', '10', '10', '20880', 'upi', 'final', 'completed', 'TXN36B561E6', 'Final payment at check-out', '2026-03-06 10:58:51', '2026-03-06 10:58:51'),
  ('26', '6', '6', '480', 'cash', 'final', 'completed', 'TXNF273A343', 'Final payment at check-out', '2026-03-09 11:29:43', '2026-03-09 11:29:43'),
  ('27', '9', '9', '10000', 'cash', 'advance', 'completed', 'TXNF4277118', 'Payment at check-in', '2026-03-09 11:30:10', '2026-03-09 11:30:10'),
  ('28', '9', '9', '76800', 'cash', 'final', 'completed', 'TXNF5721752', 'Final payment at check-out', '2026-03-09 11:30:31', '2026-03-09 11:30:31'),
  ('29', '12', '12', '2040', 'cash', 'final', 'completed', 'TXNF87546F2', 'Final payment at check-out', '2026-03-09 11:31:19', '2026-03-09 11:31:19'),
  ('30', '16', '16', '4000', 'cash', 'advance', 'completed', 'TXNFB1A44ED', 'Advance at booking', '2026-03-09 11:32:01', '2026-03-09 11:32:01'),
  ('31', '16', '16', '15600', 'cash', 'final', 'completed', 'TXNFDD9DCBD', 'Final payment at check-out', '2026-03-09 11:32:45', '2026-03-09 11:32:45'),
  ('32', '17', '16', '1000', 'cash', 'advance', 'completed', 'TXN090C8589', 'Advance at booking', '2026-03-09 11:35:44', '2026-03-09 11:35:44'),
  ('33', '17', '16', '1000', 'cash', 'advance', 'completed', 'TXN0AFD35B4', 'Payment at check-in', '2026-03-09 11:36:15', '2026-03-09 11:36:15'),
  ('34', '17', '16', '38320', 'cash', 'final', 'completed', 'TXN30079B61', 'Final payment at check-out', '2026-03-09 11:46:08', '2026-03-09 11:46:08'),
  ('35', '18', '12', '17920', 'cash', 'final', 'completed', 'TXN312A33F4', 'Final payment at check-out', '2026-03-09 11:46:26', '2026-03-09 11:46:26'),
  ('36', '6', '6', '8960', 'cash', 'final', 'completed', 'TXN3A4615F4', NULL, '2026-03-09 11:48:52', '2026-03-09 11:48:52'),
  ('37', '7', '7', '8640', 'cash', 'final', 'completed', 'TXN3DC93B81', 'asdsadsa', '2026-03-09 11:49:48', '2026-03-09 11:49:48'),
  ('38', '19', '3', '2000', 'cash', 'advance', 'completed', 'TXN561D7748', 'Advance at booking', '2026-03-09 11:56:17', '2026-03-09 11:56:17'),
  ('39', '19', '3', '950', 'upi', 'advance', 'completed', 'TXN588A0370', 'Payment at check-in', '2026-03-09 11:56:56', '2026-03-09 11:56:56'),
  ('40', '21', '1', '500', 'upi', 'advance', 'completed', 'TXN977A7C5E', 'Advance at booking', '2026-03-09 12:13:43', '2026-03-09 12:13:43'),
  ('41', '20', '17', '692', 'cash', 'advance', 'completed', 'TXN9A2216A1', 'Payment at check-in', '2026-03-09 12:14:26', '2026-03-09 12:14:26'),
  ('42', '20', '17', '11068', 'cash', 'final', 'completed', 'TXNA578C4A8', 'Final payment at check-out', '2026-03-09 12:17:27', '2026-03-09 12:17:27'),
  ('43', '23', '10', '440', 'bank_transfer', 'advance', 'completed', 'TXNC0F74A82', 'Advance at booking', '2026-03-09 12:24:47', '2026-03-09 12:24:47'),
  ('44', '22', '17', '13272', 'cash', 'final', 'completed', 'TXNDF4522F1', 'Final payment at check-out', '2026-03-09 12:32:52', '2026-03-09 12:32:52'),
  ('45', '26', '7', '1000', 'cash', 'advance', 'completed', 'TXN5626FCFD', 'Advance at booking', '2026-03-10 09:33:22', '2026-03-10 09:33:22'),
  ('46', '19', '3', '10490', 'cash', 'final', 'completed', 'TXN572D7179', 'Final payment at check-out', '2026-03-10 09:33:38', '2026-03-10 09:33:38'),
  ('47', '21', '1', '11260', 'cash', 'final', 'completed', 'TXN988C79F1', 'Final payment at check-out', '2026-03-10 09:51:04', '2026-03-10 09:51:04'),
  ('48', '27', '17', '1000', 'cash', 'advance', 'completed', 'TXN9AEAE881', 'Advance at booking', '2026-03-10 09:51:42', '2026-03-10 09:51:42'),
  ('49', '26', '7', '12440', 'upi', 'final', 'completed', 'TXNB3A1F692', 'Final payment at check-out', '2026-03-10 09:58:18', '2026-03-10 09:58:18'),
  ('50', '27', '17', '10760', 'upi', 'final', 'completed', 'TXNC151049E', 'Final payment at check-out', '2026-03-10 10:01:57', '2026-03-10 10:01:57'),
  ('51', '23', '10', '54372.8', 'upi', 'final', 'completed', 'TXNC2C4FA68', 'Final payment at check-out', '2026-03-10 10:02:20', '2026-03-10 10:02:20'),
  ('52', '24', '5', '96880', 'upi', 'final', 'completed', 'TXN62D68A1F', 'Final payment at check-out', '2026-03-10 11:53:17', '2026-03-10 11:53:17');

-- ----------------------------
-- Table: permissions
-- ----------------------------
DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR NOT NULL,
  `label` VARCHAR NOT NULL,
  `module` VARCHAR NOT NULL,
  `sort_order` INT NOT NULL DEFAULT '0',
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `permissions` (`id`, `slug`, `label`, `module`, `sort_order`, `created_at`, `updated_at`) VALUES
  ('1', 'guests.view', 'View Guests', 'Guests', '1', '2026-03-06 11:29:09', '2026-03-06 11:29:09'),
  ('2', 'guests.create', 'Add Guests', 'Guests', '2', '2026-03-06 11:29:09', '2026-03-06 11:29:09'),
  ('3', 'guests.edit', 'Edit Guests', 'Guests', '3', '2026-03-06 11:29:09', '2026-03-06 11:29:09'),
  ('4', 'guests.delete', 'Delete Guests', 'Guests', '4', '2026-03-06 11:29:09', '2026-03-06 11:29:09'),
  ('5', 'rooms.view', 'View Rooms', 'Rooms', '5', '2026-03-06 11:29:09', '2026-03-06 11:29:09'),
  ('6', 'rooms.create', 'Add Rooms', 'Rooms', '6', '2026-03-06 11:29:09', '2026-03-06 11:29:09'),
  ('7', 'rooms.edit', 'Edit Rooms', 'Rooms', '7', '2026-03-06 11:29:09', '2026-03-06 11:29:09'),
  ('8', 'rooms.delete', 'Delete Rooms', 'Rooms', '8', '2026-03-06 11:29:09', '2026-03-06 11:29:09'),
  ('9', 'bookings.view', 'View Bookings', 'Bookings', '9', '2026-03-06 11:29:09', '2026-03-06 11:29:09'),
  ('10', 'bookings.create', 'Create Bookings', 'Bookings', '10', '2026-03-06 11:29:09', '2026-03-06 11:29:09'),
  ('11', 'bookings.edit', 'Edit Bookings', 'Bookings', '11', '2026-03-06 11:29:09', '2026-03-06 11:29:09'),
  ('12', 'bookings.delete', 'Delete Bookings', 'Bookings', '12', '2026-03-06 11:29:09', '2026-03-06 11:29:09'),
  ('13', 'checkin.process', 'Process Check-In', 'Operations', '13', '2026-03-06 11:29:09', '2026-03-06 11:29:09'),
  ('14', 'checkout.process', 'Process Check-Out', 'Operations', '14', '2026-03-06 11:29:09', '2026-03-06 11:29:09'),
  ('15', 'payments.view', 'View Payments', 'Payments', '15', '2026-03-06 11:29:09', '2026-03-06 11:29:09'),
  ('16', 'payments.create', 'Record Payments', 'Payments', '16', '2026-03-06 11:29:09', '2026-03-06 11:29:09'),
  ('17', 'payments.delete', 'Delete Payments', 'Payments', '17', '2026-03-06 11:29:09', '2026-03-06 11:29:09'),
  ('18', 'invoices.view', 'View Invoices', 'Invoices', '18', '2026-03-06 11:29:09', '2026-03-06 11:29:09'),
  ('19', 'invoices.delete', 'Delete Invoices', 'Invoices', '19', '2026-03-06 11:29:09', '2026-03-06 11:29:09'),
  ('20', 'reports.view', 'View Reports', 'Reports', '20', '2026-03-06 11:29:09', '2026-03-06 11:29:09'),
  ('21', 'settings.view', 'View Settings', 'Settings', '21', '2026-03-06 11:29:09', '2026-03-06 11:29:09'),
  ('22', 'settings.edit', 'Edit Settings', 'Settings', '22', '2026-03-06 11:29:09', '2026-03-06 11:29:09'),
  ('23', 'activity_log.view', 'View Activity Log', 'System', '23', '2026-03-06 11:29:09', '2026-03-06 11:29:09'),
  ('24', 'roles.view', 'View Roles & Permissions', 'System', '24', '2026-03-06 11:29:09', '2026-03-06 11:29:09'),
  ('25', 'roles.edit', 'Edit Roles & Permissions', 'System', '25', '2026-03-06 11:29:09', '2026-03-06 11:29:09'),
  ('26', 'users.view', 'View Users', 'System', '26', '2026-03-06 12:35:50', '2026-03-06 12:35:50'),
  ('27', 'users.create', 'Create Users', 'System', '27', '2026-03-06 12:35:50', '2026-03-06 12:35:50'),
  ('28', 'users.edit', 'Edit Users', 'System', '28', '2026-03-06 12:35:50', '2026-03-06 12:35:50'),
  ('29', 'users.delete', 'Delete Users', 'System', '29', '2026-03-06 12:35:50', '2026-03-06 12:35:50');

-- ----------------------------
-- Table: role_permissions
-- ----------------------------
DROP TABLE IF EXISTS `role_permissions`;
CREATE TABLE `role_permissions` (
  `role_id` INT NOT NULL,
  `permission_id` INT NOT NULL,
  PRIMARY KEY (`role_id`, `permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
  ('1', '10'),
  ('1', '11'),
  ('1', '9'),
  ('1', '13'),
  ('1', '14'),
  ('1', '2'),
  ('1', '3'),
  ('1', '1'),
  ('1', '18'),
  ('1', '16'),
  ('1', '15'),
  ('1', '20'),
  ('1', '6'),
  ('1', '7'),
  ('1', '5'),
  ('1', '22'),
  ('1', '21'),
  ('2', '10'),
  ('2', '11'),
  ('2', '9'),
  ('2', '13'),
  ('2', '14'),
  ('2', '2'),
  ('2', '3'),
  ('2', '1'),
  ('2', '18'),
  ('2', '16'),
  ('2', '15'),
  ('2', '20'),
  ('2', '6'),
  ('2', '7'),
  ('2', '5'),
  ('3', '10'),
  ('3', '9'),
  ('3', '13'),
  ('3', '14'),
  ('3', '2'),
  ('3', '1'),
  ('3', '18'),
  ('3', '16'),
  ('3', '15'),
  ('3', '5'),
  ('1', '23'),
  ('2', '26'),
  ('1', '27'),
  ('1', '28'),
  ('1', '26');

-- ----------------------------
-- Table: roles
-- ----------------------------
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR NOT NULL,
  `description` VARCHAR DEFAULT NULL,
  `is_system` INT NOT NULL DEFAULT '0',
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`id`, `name`, `description`, `is_system`, `created_at`, `updated_at`) VALUES
  ('1', 'Admin', 'Full access except Roles & Permissions management', '1', '2026-03-06 11:29:09', '2026-03-06 11:29:09'),
  ('2', 'Manager', 'Operational access with reports, no delete or settings', '1', '2026-03-06 11:29:09', '2026-03-06 11:29:09'),
  ('3', 'Receptionist', 'Day-to-day front desk operations only', '1', '2026-03-06 11:29:09', '2026-03-06 11:29:09');

-- ----------------------------
-- Table: rooms
-- ----------------------------
DROP TABLE IF EXISTS `rooms`;
CREATE TABLE `rooms` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `room_number` VARCHAR NOT NULL,
  `type` VARCHAR NOT NULL DEFAULT 'standard',
  `capacity` INT NOT NULL DEFAULT '2',
  `price_per_night` VARCHAR(255) NOT NULL DEFAULT '0',
  `floor` INT DEFAULT NULL,
  `view` VARCHAR DEFAULT NULL,
  `amenities` TEXT DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `status` VARCHAR NOT NULL DEFAULT 'available',
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  `has_breakfast` INT NOT NULL DEFAULT '0',
  `breakfast_price` VARCHAR(255) DEFAULT NULL,
  `has_lunch` INT NOT NULL DEFAULT '0',
  `lunch_price` VARCHAR(255) DEFAULT NULL,
  `has_dinner` INT NOT NULL DEFAULT '0',
  `dinner_price` VARCHAR(255) DEFAULT NULL,
  `has_extra_bed` INT NOT NULL DEFAULT '0',
  `extra_bed_price` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `rooms` (`id`, `room_number`, `type`, `capacity`, `price_per_night`, `floor`, `view`, `amenities`, `description`, `status`, `created_at`, `updated_at`, `has_breakfast`, `breakfast_price`, `has_lunch`, `lunch_price`, `has_dinner`, `dinner_price`, `has_extra_bed`, `extra_bed_price`) VALUES
  ('2', '102', 'standard', '2', '3500', '1', 'Garden View', 'AC, TV, WiFi, Hot Water', NULL, 'occupied', '2026-03-06 10:34:09', '2026-03-10 12:23:24', '1', '100', '1', '200', '1', '150', '1', '1750'),
  ('3', '103', 'standard', '3', '4000', '1', 'Pool View', 'AC, TV, WiFi, Mini Fridge', NULL, 'occupied', '2026-03-06 10:34:09', '2026-03-10 11:53:34', '1', NULL, '0', NULL, '0', NULL, '0', NULL),
  ('4', '104', 'standard', '2', '3500', '1', 'Garden View', 'AC, TV, WiFi, Hot Water', NULL, 'available', '2026-03-06 10:34:09', '2026-03-10 10:01:57', '0', NULL, '1', NULL, '0', NULL, '0', NULL),
  ('5', '105', 'standard', '2', '3500', '1', 'Pool View', 'AC, TV, WiFi, Hot Water', NULL, 'occupied', '2026-03-06 10:34:09', '2026-03-30 05:16:21', '1', NULL, '1', NULL, '1', NULL, '1', '1720'),
  ('6', '201', 'deluxe', '2', '5500', '2', 'Sea View', 'AC, Smart TV, WiFi, Mini Bar, Balcony', NULL, 'available', '2026-03-06 10:34:09', '2026-03-10 11:53:17', '1', NULL, '1', NULL, '1', NULL, '1', '1000'),
  ('7', '202', 'deluxe', '2', '5500', '2', 'Sea View', 'AC, Smart TV, WiFi, Mini Bar, Balcony', NULL, 'occupied', '2026-03-06 10:34:09', '2026-03-09 12:29:29', '0', NULL, '0', NULL, '0', NULL, '0', NULL),
  ('8', '203', 'deluxe', '3', '6000', '2', 'Pool and Sea View', 'AC, Smart TV, WiFi, Mini Bar, Balcony, Jacuzzi', NULL, 'available', '2026-03-06 10:34:09', '2026-03-10 09:58:18', '0', NULL, '1', NULL, '1', NULL, '0', NULL),
  ('9', '204', 'deluxe', '2', '5800', '2', 'Sea View', 'AC, Smart TV, WiFi, Mini Bar, Balcony', NULL, 'available', '2026-03-06 10:34:09', '2026-03-06 13:05:59', '0', NULL, '0', NULL, '0', NULL, '0', NULL),
  ('10', '301', 'suite', '4', '9500', '3', 'Panoramic Sea View', 'AC, Smart TV x2, WiFi, Jacuzzi, Mini Bar, Living Area', NULL, 'available', '2026-03-06 10:34:09', '2026-03-06 13:03:09', '0', NULL, '0', NULL, '0', NULL, '0', NULL),
  ('11', '302', 'suite', '4', '9500', '3', 'Panoramic Sea View', 'AC, Smart TV x2, WiFi, Jacuzzi, Mini Bar, Living Area', NULL, 'available', '2026-03-06 10:34:09', '2026-03-06 13:03:09', '0', NULL, '0', NULL, '0', NULL, '0', NULL),
  ('12', '401', 'suite', '6', '12000', '4', '360 Ocean View', 'AC, Smart TV x3, WiFi, Hot Tub, Full Bar, 2 Balconies', NULL, 'available', '2026-03-06 10:34:09', '2026-03-09 11:46:08', '0', NULL, '0', NULL, '0', NULL, '0', NULL),
  ('13', 'V01', 'villa', '6', '18000', '1', 'Private Beach', 'AC, Smart TV x4, WiFi, Private Pool, Chef, Butler, BBQ', NULL, 'available', '2026-03-06 10:34:09', '2026-03-06 13:07:07', '0', NULL, '0', NULL, '0', NULL, '0', NULL),
  ('14', 'V02', 'villa', '8', '22000', '1', 'Private Beach and Garden', 'AC, Smart TV x5, WiFi, Private Pool, Chef, Butler, Spa', NULL, 'inactive', '2026-03-06 10:34:09', '2026-03-09 11:59:58', '0', NULL, '0', NULL, '0', NULL, '0', NULL),
  ('15', 'PH01', 'penthouse', '8', '35000', '5', 'Full Panoramic Sea View', 'AC, Smart TV x6, WiFi, Private Rooftop Pool, Full Bar, Chef', NULL, 'available', '2026-03-06 10:34:09', '2026-03-09 11:30:31', '0', NULL, '0', NULL, '0', NULL, '0', NULL);

-- ----------------------------
-- Table: sessions
-- ----------------------------
DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` VARCHAR NOT NULL,
  `user_id` INT DEFAULT NULL,
  `ip_address` VARCHAR DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `payload` TEXT NOT NULL,
  `last_activity` INT NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
  ('dEKl4NLcX0qkicClR7RUYDOMT1kNHzYrVWUy5ZOW', NULL, '10.84.3.13', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'YToxMDp7czo2OiJfZmxhc2giO2E6Mjp7czozOiJuZXciO2E6MDp7fXM6Mzoib2xkIjthOjA6e319czo2OiJfdG9rZW4iO3M6NDA6Im1PNHBTbzFqU011ZDZ0UjhLemJGTUd5WmRNeDFGYzE4NzhxTDBCV3MiO3M6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjg3OiJodHRwczovL2QwNjFiOTI2LTQ3N2MtNDcwOS05OWM5LWVkY2EzNjU3NjgwMS0wMC0yOTgzaXRpbDR6MjgyLnBpa2UucmVwbGl0LmRldi9kYXNoYm9hcmQiO3M6NToicm91dGUiO3M6OToiZGFzaGJvYXJkIjt9czoxMzoiY3JtX2xvZ2dlZF9pbiI7YjoxO3M6MTE6ImNybV91c2VyX2lkIjtpOjI7czoxMzoiY3JtX3VzZXJfbmFtZSI7czoxMDoiQWRtaW4gVXNlciI7czoxNDoiY3JtX3VzZXJfZW1haWwiO3M6MTY6ImFkbWluQHJlc29ydC5jb20iO3M6MTM6ImNybV91c2VyX3JvbGUiO3M6NToiQWRtaW4iO3M6MTU6ImNybV91c2VyX2F2YXRhciI7czoxOiJBIjtzOjE1OiJjcm1fcGVybWlzc2lvbnMiO2E6MjE6e2k6MDtzOjExOiJndWVzdHMudmlldyI7aToxO3M6MTM6Imd1ZXN0cy5jcmVhdGUiO2k6MjtzOjExOiJndWVzdHMuZWRpdCI7aTozO3M6MTA6InJvb21zLnZpZXciO2k6NDtzOjEyOiJyb29tcy5jcmVhdGUiO2k6NTtzOjEwOiJyb29tcy5lZGl0IjtpOjY7czoxMzoiYm9va2luZ3MudmlldyI7aTo3O3M6MTU6ImJvb2tpbmdzLmNyZWF0ZSI7aTo4O3M6MTM6ImJvb2tpbmdzLmVkaXQiO2k6OTtzOjE1OiJjaGVja2luLnByb2Nlc3MiO2k6MTA7czoxNjoiY2hlY2tvdXQucHJvY2VzcyI7aToxMTtzOjEzOiJwYXltZW50cy52aWV3IjtpOjEyO3M6MTU6InBheW1lbnRzLmNyZWF0ZSI7aToxMztzOjEzOiJpbnZvaWNlcy52aWV3IjtpOjE0O3M6MTI6InJlcG9ydHMudmlldyI7aToxNTtzOjEzOiJzZXR0aW5ncy52aWV3IjtpOjE2O3M6MTM6InNldHRpbmdzLmVkaXQiO2k6MTc7czoxNzoiYWN0aXZpdHlfbG9nLnZpZXciO2k6MTg7czoxMDoidXNlcnMudmlldyI7aToxOTtzOjEyOiJ1c2Vycy5jcmVhdGUiO2k6MjA7czoxMDoidXNlcnMuZWRpdCI7fX0=', '1774851998'),
  ('JtaddvYMPrP0E0pnIfuxxKSAqMriK7R9OrQa4k5e', NULL, '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/140.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoicUlUUG1QbFFCQVZCYXd2dzBvWHVoQkdPS214RlFJcUh3cUFrYUhzciI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly9sb2NhbGhvc3Q6NTAwMCI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', '1774848065'),
  ('0UeyZrTP1mJT8wadeT8NJDTiFiZGy9rUby7EusuK', NULL, '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/140.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiUDZsVEdoamx2cThiWW1Sc0NXZkRrRGtSUExVSWJldWJDRENWTk9xbyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly9sb2NhbGhvc3Q6NTAwMCI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', '1774850574'),
  ('kiJJHEli6SB0M9xAjoEbUuoP5VScHCWE1dQNWPYG', NULL, '127.0.0.1', 'curl/8.14.1', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoielVOdURocEpjYkQydlJPWWpDeVlESlJYVTYyeEEzM0taTHdEYzdpeCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjk6Imh0dHA6Ly9sb2NhbGhvc3Q6NTAwMC9pbnN0YWxsIjtzOjU6InJvdXRlIjtzOjc6Imluc3RhbGwiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', '1774852003'),
  ('cGQhSXvuIKcK5quHW2y1LPM8Kb8IWmnMMKDqTsgF', NULL, '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/140.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiYXRicE5QZkRWTFY0cnZra2hVck5IZDJwdnE2dkFhVjJKQjdBVzdoRiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjk6Imh0dHA6Ly9sb2NhbGhvc3Q6NTAwMC9pbnN0YWxsIjtzOjU6InJvdXRlIjtzOjc6Imluc3RhbGwiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', '1774852010'),
  ('YfeafuaiVTwzYVBSacpSRp1ZErdH20mVu7f83yLG', NULL, '10.84.10.120', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'YTozOntzOjY6Il9mbGFzaCI7YToyOntzOjM6Im5ldyI7YTowOnt9czozOiJvbGQiO2E6MDp7fX1zOjY6Il90b2tlbiI7czo0MDoibnNSbGxaaVJ6Q0RxYzJwOTFPN0J6U0phUlVmSkw3RmdLa0w2bjdyciI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Nzc6Imh0dHBzOi8vZDA2MWI5MjYtNDc3Yy00NzA5LTk5YzktZWRjYTM2NTc2ODAxLTAwLTI5ODNpdGlsNHoyODIucGlrZS5yZXBsaXQuZGV2IjtzOjU6InJvdXRlIjtzOjQ6ImhvbWUiO319', '1774866398'),
  ('ycozU6mmftgrygM3bPYEfHsCryd6gjxKTzsa4kfM', NULL, '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/140.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiZ3ZPYUJiRGFkQllEVWRvYUg5b2p4bkRXVUVVeXZBNmVheW9vOHljMiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly9sb2NhbGhvc3Q6NTAwMCI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', '1774865453');

-- ----------------------------
-- Table: settings
-- ----------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `resort_name` VARCHAR NOT NULL,
  `address` TEXT NOT NULL,
  `phone` VARCHAR NOT NULL,
  `email` VARCHAR NOT NULL,
  `website` VARCHAR DEFAULT NULL,
  `gst_number` VARCHAR DEFAULT NULL,
  `tax_rate` VARCHAR NOT NULL DEFAULT '12',
  `currency` VARCHAR NOT NULL DEFAULT 'INR',
  `currency_symbol` VARCHAR NOT NULL DEFAULT 'Rs',
  `check_in_time` VARCHAR NOT NULL DEFAULT '14:00',
  `check_out_time` VARCHAR NOT NULL DEFAULT '11:00',
  `cancellation_policy` TEXT DEFAULT NULL,
  `logo` VARCHAR DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  `tagline` VARCHAR DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`id`, `resort_name`, `address`, `phone`, `email`, `website`, `gst_number`, `tax_rate`, `currency`, `currency_symbol`, `check_in_time`, `check_out_time`, `cancellation_policy`, `logo`, `created_at`, `updated_at`, `tagline`) VALUES
  ('1', 'All in on CRM for Resort', 'Gir, Junaghadh', '+91 832 267 8900', 'reservations@azureparadise.com', 'www.azureparadise.com', '30AABCU9603R1ZX', '12', 'INR', 'Rs', '10:00', '11:00', 'Free cancellation up to 48 hours before check-in. 50% charge within 24 to 48 hours.', 'logos/resort_logo_1772795762.png', '2026-03-06 10:34:09', '2026-03-10 09:04:30', 'Best CRM in Market');

-- ----------------------------
-- Table: users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR NOT NULL,
  `email` VARCHAR NOT NULL,
  `email_verified_at` DATETIME DEFAULT NULL,
  `password` VARCHAR NOT NULL,
  `remember_token` VARCHAR DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  `role` VARCHAR NOT NULL DEFAULT 'Admin',
  `is_super_admin` INT NOT NULL DEFAULT '0',
  `status` VARCHAR NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `role`, `is_super_admin`, `status`) VALUES
  ('1', 'Super Admin', 'superadmin@gmail.com', NULL, '$2y$12$.jIik4t9.ftIsMpv3ZXi9.NvcpavrA65B.10jGle3d23T3tpItIqi', NULL, '2026-03-06 12:32:13', '2026-03-06 12:35:52', 'Super Admin', '1', 'active'),
  ('2', 'Admin User', 'admin@resort.com', NULL, '$2y$12$NfD7wAUXnkC01wYwypwyAO2ioc3r4STwvB651DHybiHea9bRvZlqK', NULL, '2026-03-06 12:32:13', '2026-03-06 12:35:52', 'Admin', '0', 'active'),
  ('3', 'Resort Manager', 'manager@resort.com', NULL, '$2y$12$SoA1QoAXngJKygF.m1Eh.OJLz1GBX9eS6WWx7YH2rXmdIX2SzDrXq', NULL, '2026-03-06 12:32:13', '2026-03-06 12:35:52', 'Manager', '0', 'active'),
  ('4', 'Front Desk', 'receptionist@resort.com', NULL, '$2y$12$yzqw4El3tXQg2zZEuRADeuRxNYJ8BA/.1fya32WdodnQPdkjHUodu', NULL, '2026-03-06 12:32:13', '2026-03-06 12:35:52', 'Receptionist', '0', 'active');

-- ----------------------------
-- Table: whatsapp_configs
-- ----------------------------
DROP TABLE IF EXISTS `whatsapp_configs`;
CREATE TABLE `whatsapp_configs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider` VARCHAR NOT NULL DEFAULT 'meta',
  `api_key` TEXT DEFAULT NULL,
  `phone_number_id` VARCHAR DEFAULT NULL,
  `webhook_verify_token` VARCHAR DEFAULT NULL,
  `business_account_id` VARCHAR DEFAULT NULL,
  `test_phone` VARCHAR DEFAULT NULL,
  `is_active` INT NOT NULL DEFAULT '0',
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `whatsapp_configs` (`id`, `provider`, `api_key`, `phone_number_id`, `webhook_verify_token`, `business_account_id`, `test_phone`, `is_active`, `created_at`, `updated_at`) VALUES
  ('1', 'wati', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1bmlxdWVfbmFtZSI6ImNoZXRhbm1ha3dhbmEzMzg1QGdtYWlsLmNvbSIsIm5hbWVpZCI6ImNoZXRhbm1ha3dhbmEzMzg1QGdtYWlsLmNvbSIsImVtYWlsIjoiY2hldGFubWFrd2FuYTMzODVAZ21haWwuY29tIiwiYXV0aF90aW1lIjoiMDMvMTAvMjAyNiAwOTo0Nzo1MCIsInRlbmFudF9pZCI6IjEwMTA5Mjg0IiwiZGJfbmFtZSI6Im10LXByb2QtVGVuYW50cyIsImh0dHA6Ly9zY2hlbWFzLm1pY3Jvc29mdC5jb20vd3MvMjAwOC8wNi9pZGVudGl0eS9jbGFpbXMvcm9sZSI6IkFETUlOSVNUUkFUT1IiLCJleHAiOjI1MzQwMjMwMDgwMCwiaXNzIjoiQ2xhcmVfQUkiLCJhdWQiOiJDbGFyZV9BSSJ9.SQbd5IWZ8MYTSitzaOAKcc02dc0DHR5GyFnFb5fD4OM', '10109284', NULL, NULL, NULL, '1', '2026-03-10 09:48:33', '2026-03-10 11:18:04');

-- ----------------------------
-- Table: whatsapp_templates
-- ----------------------------
DROP TABLE IF EXISTS `whatsapp_templates`;
CREATE TABLE `whatsapp_templates` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `trigger_event` VARCHAR NOT NULL,
  `template_name` VARCHAR NOT NULL,
  `message_body` TEXT NOT NULL,
  `variables_hint` TEXT DEFAULT NULL,
  `is_active` INT NOT NULL DEFAULT '1',
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `whatsapp_templates` (`id`, `trigger_event`, `template_name`, `message_body`, `variables_hint`, `is_active`, `created_at`, `updated_at`) VALUES
  ('1', 'booking.created', 'Booking Confirmation', 'Hello {{guest_name}}, your booking at {{hotel_name}} is confirmed! 🏨\n\nRoom: {{room_number}}\nCheck-in: {{check_in_date}}\nCheck-out: {{check_out_date}}\nBooking Ref: {{booking_number}}\nTotal Amount: ₹{{total_amount}}\n\nWe look forward to welcoming you! For any queries, please contact us.', '{{guest_name}}, {{hotel_name}}, {{room_number}}, {{check_in_date}}, {{check_out_date}}, {{booking_number}}, {{total_amount}}', '1', '2026-03-10 09:14:04', '2026-03-10 09:14:04'),
  ('2', 'checkin.tomorrow', 'Check-In Reminder', 'Hello {{guest_name}}, this is a friendly reminder that your check-in at {{hotel_name}} is tomorrow! 🌟\n\nRoom: {{room_number}}\nCheck-in Date: {{check_in_date}}\n\nYour room is being prepared for you. We look forward to welcoming you! For any queries, please contact us.', '{{guest_name}}, {{hotel_name}}, {{room_number}}, {{check_in_date}}', '1', '2026-03-10 09:14:04', '2026-03-10 09:14:04'),
  ('3', 'checkout.done', 'Check-Out & Invoice', 'Thank you, {{guest_name}}, for staying at {{hotel_name}}! 🙏\n\nWe hope you had a wonderful stay.\n\nInvoice: {{invoice_number}}\nTotal Amount: ₹{{total_amount}}\n\nWe would love to host you again. Please share your feedback — it means the world to us!', '{{guest_name}}, {{hotel_name}}, {{invoice_number}}, {{total_amount}}', '1', '2026-03-10 09:14:04', '2026-03-10 09:14:04'),
  ('4', 'checkin.done', 'Arrival Welcome', 'Welcome to {{hotel_name}}, {{guest_name}}! 🏨\n\nYou\'re all checked in!\n📍 Room: {{room_number}} ({{room_type}})\n📅 Check-out: {{check_out_date}}\n\nWe hope you have a wonderful stay. Please don\'t hesitate to ask if you need anything.', '{{guest_name}}, {{hotel_name}}, {{room_number}}, {{room_type}}, {{check_out_date}}', '1', '2026-03-28 17:21:46', '2026-03-28 17:21:46'),
  ('5', 'payment.received', 'Payment Receipt', 'Payment Received ✅\n\nDear {{guest_name}},\n\nWe\'ve received your payment of {{amount_paid}} via {{payment_method}}.\n\n📋 Booking: {{booking_number}}\n💰 Balance Due: {{balance_due}}\n\nThank you! — {{hotel_name}}', '{{guest_name}}, {{amount_paid}}, {{payment_method}}, {{booking_number}}, {{balance_due}}, {{hotel_name}}', '1', '2026-03-28 17:21:46', '2026-03-28 17:21:46'),
  ('6', 'feedback.request', 'Feedback Request', 'Dear {{guest_name}},\n\nThank you for staying with us at {{hotel_name}}! 🙏\n\nWe hope you had a pleasant stay from {{check_in_date}} to {{check_out_date}}.\n\nWe\'d love to hear your feedback to help us serve you better. Please share your experience whenever you get a moment.\n\nWe look forward to welcoming you again! 🌟', '{{guest_name}}, {{hotel_name}}, {{check_in_date}}, {{check_out_date}}', '1', '2026-03-28 17:21:46', '2026-03-28 17:21:46');

SET FOREIGN_KEY_CHECKS=1;
-- End of dump
