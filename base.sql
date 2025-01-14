# ************************************************************
# Sequel Ace SQL dump
# Version 20080
#
# https://sequel-ace.com/
# https://github.com/Sequel-Ace/Sequel-Ace
#
# Host: 127.0.0.1 (MySQL 5.5.5-10.11.10-MariaDB-ubu2204-log)
# Database: db
# Generation Time: 2025-01-14 16:50:40 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
SET NAMES utf8mb4;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE='NO_AUTO_VALUE_ON_ZERO', SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table config
# ------------------------------------------------------------

DROP TABLE IF EXISTS `config`;

CREATE TABLE `config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(255) DEFAULT NULL,
  `description` blob DEFAULT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'application',
  `key` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  `value_type` enum('text','longtext','number','boolean','object') DEFAULT 'text',
  `date_created` timestamp NULL DEFAULT current_timestamp(),
  `created_by` int(11) NOT NULL DEFAULT 0 COMMENT 'User ID, 0 for System',
  `date_modified` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `modified_by` int(11) NOT NULL DEFAULT 0 COMMENT 'User ID, 0 for System',
  PRIMARY KEY (`type`,`key`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

LOCK TABLES `config` WRITE;
/*!40000 ALTER TABLE `config` DISABLE KEYS */;

INSERT INTO `config` (`id`, `label`, `description`, `type`, `key`, `value`, `value_type`, `date_created`, `created_by`, `date_modified`, `modified_by`)
VALUES
	(4,'Copyright text',X'5465787420746F20666F6C6C6F772074686520666F6F746572277320C2A9207B796561727D','application','copyright','Tetra','text','2025-01-11 01:09:16',1,'2025-01-14 10:49:50',1),
	(1,'Application Name',X'4170706C69636174696F6E206E616D652C207573656420696E20746865206C6F676F2C2073697465207469746C652C20657463','application','name','Demo Project','text','2025-01-07 12:05:13',1,'2025-01-14 10:50:19',1),
	(2,'Password Reset Expires (days)',X'546865206E756D626572206F66206461797320612070617373776F7264207265736574206C696E6B2077696C6C2062652076616C69642E2041667465722074686973206E756D626572206F6620646179732C20746865206C696E6B2077696C6C206E6F206C6F6E67657220776F726B2E','application','password_reset_expires_days','30','number','2025-01-09 10:20:10',0,'2025-01-10 15:34:19',0),
	(3,'User Session Expires (days)',X'546865206E756D626572206F66206461797320776974686F7574206163746976697479206265666F726520612075736572206973206175746F6D61746963616C6C79206C6F67676564206F75742E20416E792074696D6520746865207573657220696E746572616374732077697468207468652073697465207468652074696D65722077696C6C2072657365742E','application','session_expires_days','1','number','2025-01-07 13:04:50',0,'2025-01-11 01:40:52',0);

/*!40000 ALTER TABLE `config` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table menu
# ------------------------------------------------------------

DROP TABLE IF EXISTS `menu`;

CREATE TABLE `menu` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `path` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `parent` int(11) NOT NULL DEFAULT 0,
  `icon` varchar(255) DEFAULT NULL,
  `weight` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



# Dump of table user_permissions
# ------------------------------------------------------------

DROP TABLE IF EXISTS `user_permissions`;

CREATE TABLE `user_permissions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `dimension` varchar(255) NOT NULL,
  `action` enum('*','VIEW','VIEW OWN','CREATE','UPDATE','UPDATE OWN','DELETE','DELETE OWN') NOT NULL,
  `description` blob DEFAULT NULL,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) NOT NULL DEFAULT 0,
  `date_modified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `modified_by` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dimension/action` (`dimension`,`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



# Dump of table user_role_assignments
# ------------------------------------------------------------

DROP TABLE IF EXISTS `user_role_assignments`;

CREATE TABLE `user_role_assignments` (
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) NOT NULL DEFAULT 0 COMMENT 'User ID, 0 for System',
  `date_modified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `modified_by` int(11) NOT NULL DEFAULT 0 COMMENT 'User ID, 0 for System',
  PRIMARY KEY (`user_id`,`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



# Dump of table user_role_permissions_assignments
# ------------------------------------------------------------

DROP TABLE IF EXISTS `user_role_permissions_assignments`;

CREATE TABLE `user_role_permissions_assignments` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) NOT NULL DEFAULT 0 COMMENT 'User ID, 0 for System',
  `date_modified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `modified_by` int(11) NOT NULL DEFAULT 0 COMMENT 'User ID, 0 for System',
  PRIMARY KEY (`role_id`,`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



# Dump of table user_roles
# ------------------------------------------------------------

DROP TABLE IF EXISTS `user_roles`;

CREATE TABLE `user_roles` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` blob DEFAULT NULL,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) NOT NULL DEFAULT 0 COMMENT 'User ID, 0 for System',
  `date_modified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `modified_by` int(11) NOT NULL DEFAULT 0 COMMENT 'User ID, 0 for System',
  PRIMARY KEY (`id`),
  UNIQUE KEY `title` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



# Dump of table user_sessions
# ------------------------------------------------------------

DROP TABLE IF EXISTS `user_sessions`;

CREATE TABLE `user_sessions` (
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `date_started` timestamp NULL DEFAULT current_timestamp(),
  `date_last_access` timestamp NULL DEFAULT current_timestamp(),
  `date_expires` timestamp NOT NULL,
  `date_ended` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



# Dump of table users
# ------------------------------------------------------------

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name_prefix` varchar(255) DEFAULT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `middle_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `name_suffix` varchar(255) DEFAULT NULL,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) NOT NULL DEFAULT 0 COMMENT 'User ID, 0 for System',
  `date_modified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `modified_by` int(11) NOT NULL DEFAULT 0 COMMENT 'User ID, 0 for System',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
