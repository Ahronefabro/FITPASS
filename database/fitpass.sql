-- FitPass database (MySQL / MariaDB)
-- Import in phpMyAdmin, or: mysql -u root < database/fitpass.sql
-- Sample logins: admin / password (Owner), staff / password (Staff)

CREATE DATABASE IF NOT EXISTS `fitpass` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `fitpass`;


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_name` varchar(255) NOT NULL,
  `action` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `check_ins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `check_ins` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` bigint(20) unsigned DEFAULT NULL,
  `guest_name` varchar(255) DEFAULT NULL,
  `time_in` datetime NOT NULL,
  `time_out` datetime DEFAULT NULL,
  `method` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `check_ins_member_id_foreign` (`member_id`),
  KEY `check_ins_time_in_index` (`time_in`),
  CONSTRAINT `check_ins_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `members` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `plan` varchar(255) NOT NULL,
  `membership_type` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `joined_at` date NOT NULL,
  `expires_at` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `contact` varchar(255) DEFAULT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `members_qr_code_unique` (`qr_code`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `paid_on` date NOT NULL,
  `kind` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `method` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payments_member_id_foreign` (`member_id`),
  KEY `payments_paid_on_index` (`paid_on`),
  CONSTRAINT `payments_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staff` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL,
  `shift_start` time NOT NULL,
  `status` varchar(255) NOT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `staff_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staff_notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `body` varchar(255) NOT NULL,
  `kind` varchar(255) NOT NULL,
  `level` varchar(255) NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `role` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_username_unique` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_10_10_000001_fitpass_membership_schema',1),(5,'2026_10_10_000002_fitpass_staff_portal',1),(6,'2026_10_10_000003_require_an_explicit_user_role',1),(7,'2026_10_10_000004_allow_guest_check_ins',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `username`, `role`) VALUES (1,'Super Admin','owner@fitpass.test',NULL,'$2y$12$sLV3pK1.NHKw0AsHtAQlEeL.pM3CIifmvAkXjESsA56tQsn63xI1y',NULL,'2026-10-25 17:54:04','2026-10-25 17:54:04','admin','Owner'),(2,'Front Desk Staff','staff@fitpass.test',NULL,'$2y$12$w6vZkiMVvDukKxzmK43fr.WfK9pkummLIKXMi3HgC30obRaMDAq9m',NULL,'2026-10-25 17:54:04','2026-10-25 17:54:04','staff','Staff');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `members` WRITE;
/*!40000 ALTER TABLE `members` DISABLE KEYS */;
INSERT INTO `members` (`id`, `name`, `plan`, `membership_type`, `amount`, `joined_at`, `expires_at`, `created_at`, `updated_at`, `contact`, `qr_code`) VALUES (1,'Jaspher Sibayan','Regular','Monthly Unlimited',1000.00,'2026-10-06','2026-11-05','2026-10-25 17:54:04','2026-10-25 17:54:04','09000000001','FP-K0XQJ5PB'),(2,'Ahrone Magtibay','Student','Quarterly',800.00,'2026-08-27','2026-11-25','2026-10-25 17:54:04','2026-10-25 17:54:04','09000000002','FP-VGUWIFU5'),(3,'Emman Jalal','Regular','Monthly Unlimited',1000.00,'2026-09-11','2026-10-11','2026-10-25 17:54:04','2026-10-25 17:54:04','09000000003','FP-ISLHXLZA'),(4,'Fabs Dela Cruz','Senior','Monthly Unlimited',700.00,'2026-10-16','2026-11-15','2026-10-25 17:54:04','2026-10-25 17:54:04','09000000004','FP-C3ZHXF7E'),(5,'Dwayne Tore','Regular','Monthly Unlimited',1000.00,'2026-09-29','2026-10-29','2026-10-25 17:54:04','2026-10-25 17:54:04','09000000005','FP-6O6WVHZG'),(6,'Emman Pico','Student','Monthly Unlimited',800.00,'2026-09-16','2026-10-16','2026-10-25 17:54:04','2026-10-25 17:54:04','09000000006','FP-MUQRVWIU');
/*!40000 ALTER TABLE `members` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `staff` WRITE;
/*!40000 ALTER TABLE `staff` DISABLE KEYS */;
INSERT INTO `staff` (`id`, `name`, `role`, `shift_start`, `status`, `last_login_at`, `created_at`, `updated_at`) VALUES (1,'Front Desk Staff','Staff','06:00:00','Active',NULL,'2026-10-25 17:54:04','2026-10-25 17:54:04'),(2,'Jaspher Sibayan','Front Desk','06:00:00','Off Duty',NULL,'2026-10-25 17:54:04','2026-10-25 17:54:04'),(3,'Ahrone Magtibay','Front Desk','06:00:00','Active',NULL,'2026-10-25 17:54:04','2026-10-25 17:54:04'),(4,'Emman Jalal','Front Desk','06:00:00','Active',NULL,'2026-10-25 17:54:04','2026-10-25 17:54:04'),(5,'Fabs Dela Cruz','Front Desk','06:00:00','Active',NULL,'2026-10-25 17:54:04','2026-10-25 17:54:04'),(6,'Dwayne Tore','Front Desk','06:00:00','Active',NULL,'2026-10-25 17:54:04','2026-10-25 17:54:04'),(7,'Emman Pico','Front Desk','06:00:00','Off Duty',NULL,'2026-10-25 17:54:04','2026-10-25 17:54:04');
/*!40000 ALTER TABLE `staff` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `check_ins` WRITE;
/*!40000 ALTER TABLE `check_ins` DISABLE KEYS */;
INSERT INTO `check_ins` (`id`, `member_id`, `guest_name`, `time_in`, `time_out`, `method`, `created_at`, `updated_at`) VALUES (1,1,NULL,'2026-10-26 01:34:04',NULL,'Manual','2026-10-25 17:54:04','2026-10-25 17:54:04'),(2,1,NULL,'2026-10-24 01:34:04','2026-10-24 01:49:04','Manual','2026-10-25 17:54:04','2026-10-25 17:54:04'),(3,1,NULL,'2026-10-22 01:34:04','2026-10-22 01:49:04','Manual','2026-10-25 17:54:04','2026-10-25 17:54:04'),(4,1,NULL,'2026-10-20 01:34:04','2026-10-20 01:49:04','Manual','2026-10-25 17:54:04','2026-10-25 17:54:04'),(5,1,NULL,'2026-10-18 01:34:04','2026-10-18 01:49:04','Manual','2026-10-25 17:54:04','2026-10-25 17:54:04'),(6,1,NULL,'2026-10-16 01:34:04','2026-10-16 01:49:04','Manual','2026-10-25 17:54:04','2026-10-25 17:54:04'),(7,1,NULL,'2026-10-14 01:34:04','2026-10-14 01:49:04','Manual','2026-10-25 17:54:04','2026-10-25 17:54:04'),(8,2,NULL,'2026-10-25 00:49:04','2026-10-25 01:04:04','QR Code','2026-10-25 17:54:04','2026-10-25 17:54:04'),(9,2,NULL,'2026-10-23 00:49:04','2026-10-23 01:04:04','QR Code','2026-10-25 17:54:04','2026-10-25 17:54:04'),(10,2,NULL,'2026-10-21 00:49:04','2026-10-21 01:04:04','QR Code','2026-10-25 17:54:04','2026-10-25 17:54:04'),(11,2,NULL,'2026-10-19 00:49:04','2026-10-19 01:04:04','QR Code','2026-10-25 17:54:04','2026-10-25 17:54:04'),(12,2,NULL,'2026-10-17 00:49:04','2026-10-17 01:04:04','QR Code','2026-10-25 17:54:04','2026-10-25 17:54:04'),(13,2,NULL,'2026-10-15 00:49:04','2026-10-15 01:04:04','QR Code','2026-10-25 17:54:04','2026-10-25 17:54:04'),(14,2,NULL,'2026-10-13 00:49:04','2026-10-13 01:04:04','QR Code','2026-10-25 17:54:04','2026-10-25 17:54:04'),(15,3,NULL,'2026-10-26 00:04:04','2026-10-26 00:19:04','Manual','2026-10-25 17:54:04','2026-10-25 17:54:04'),(16,3,NULL,'2026-10-24 00:04:04','2026-10-24 00:19:04','Manual','2026-10-25 17:54:04','2026-10-25 17:54:04'),(17,3,NULL,'2026-10-22 00:04:04','2026-10-22 00:19:04','Manual','2026-10-25 17:54:04','2026-10-25 17:54:04'),(18,3,NULL,'2026-10-20 00:04:04','2026-10-20 00:19:04','Manual','2026-10-25 17:54:04','2026-10-25 17:54:04'),(19,3,NULL,'2026-10-18 00:04:04','2026-10-18 00:19:04','Manual','2026-10-25 17:54:04','2026-10-25 17:54:04'),(20,3,NULL,'2026-10-16 00:04:04','2026-10-16 00:19:04','Manual','2026-10-25 17:54:04','2026-10-25 17:54:04'),(21,3,NULL,'2026-10-14 00:04:04','2026-10-14 00:19:04','Manual','2026-10-25 17:54:04','2026-10-25 17:54:04'),(22,4,NULL,'2026-10-24 23:19:04','2026-10-24 23:34:04','QR Code','2026-10-25 17:54:04','2026-10-25 17:54:04'),(23,4,NULL,'2026-10-22 23:19:04','2026-10-22 23:34:04','QR Code','2026-10-25 17:54:04','2026-10-25 17:54:04'),(24,4,NULL,'2026-10-20 23:19:04','2026-10-20 23:34:04','QR Code','2026-10-25 17:54:04','2026-10-25 17:54:04'),(25,4,NULL,'2026-10-18 23:19:04','2026-10-18 23:34:04','QR Code','2026-10-25 17:54:04','2026-10-25 17:54:04'),(26,4,NULL,'2026-10-16 23:19:04','2026-10-16 23:34:04','QR Code','2026-10-25 17:54:04','2026-10-25 17:54:04'),(27,4,NULL,'2026-10-14 23:19:04','2026-10-14 23:34:04','QR Code','2026-10-25 17:54:04','2026-10-25 17:54:04'),(28,4,NULL,'2026-10-12 23:19:04','2026-10-12 23:34:04','QR Code','2026-10-25 17:54:04','2026-10-25 17:54:04'),(29,5,NULL,'2026-10-25 22:34:04','2026-10-25 22:49:04','Manual','2026-10-25 17:54:04','2026-10-25 17:54:04'),(30,5,NULL,'2026-10-23 22:34:04','2026-10-23 22:49:04','Manual','2026-10-25 17:54:04','2026-10-25 17:54:04'),(31,5,NULL,'2026-10-21 22:34:04','2026-10-21 22:49:04','Manual','2026-10-25 17:54:04','2026-10-25 17:54:04'),(32,5,NULL,'2026-10-19 22:34:04','2026-10-19 22:49:04','Manual','2026-10-25 17:54:04','2026-10-25 17:54:04'),(33,5,NULL,'2026-10-17 22:34:04','2026-10-17 22:49:04','Manual','2026-10-25 17:54:04','2026-10-25 17:54:04'),(34,5,NULL,'2026-10-15 22:34:04','2026-10-15 22:49:04','Manual','2026-10-25 17:54:04','2026-10-25 17:54:04'),(35,5,NULL,'2026-10-13 22:34:04','2026-10-13 22:49:04','Manual','2026-10-25 17:54:04','2026-10-25 17:54:04'),(36,6,NULL,'2026-10-24 21:49:04','2026-10-24 22:04:04','QR Code','2026-10-25 17:54:04','2026-10-25 17:54:04'),(37,6,NULL,'2026-10-22 21:49:04','2026-10-22 22:04:04','QR Code','2026-10-25 17:54:04','2026-10-25 17:54:04'),(38,6,NULL,'2026-10-20 21:49:04','2026-10-20 22:04:04','QR Code','2026-10-25 17:54:04','2026-10-25 17:54:04'),(39,6,NULL,'2026-10-18 21:49:04','2026-10-18 22:04:04','QR Code','2026-10-25 17:54:04','2026-10-25 17:54:04'),(40,6,NULL,'2026-10-16 21:49:04','2026-10-16 22:04:04','QR Code','2026-10-25 17:54:04','2026-10-25 17:54:04'),(41,6,NULL,'2026-10-14 21:49:04','2026-10-14 22:04:04','QR Code','2026-10-25 17:54:04','2026-10-25 17:54:04'),(42,6,NULL,'2026-10-12 21:49:04','2026-10-12 22:04:04','QR Code','2026-10-25 17:54:04','2026-10-25 17:54:04');
/*!40000 ALTER TABLE `check_ins` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` (`id`, `member_id`, `name`, `paid_on`, `kind`, `amount`, `method`, `created_at`, `updated_at`) VALUES (1,1,'Jaspher Sibayan','2026-10-23','Monthly',700.00,'Gcash','2026-10-25 17:54:04','2026-10-25 17:54:04'),(2,3,'Emman Jalal','2026-10-20','Monthly',700.00,'Gcash','2026-10-25 17:54:04','2026-10-25 17:54:04'),(3,NULL,'Michael Jackson','2026-10-20','Walk-in',80.00,'Cash','2026-10-25 17:54:04','2026-10-25 17:54:04'),(4,4,'Fabs Dela Cruz','2026-10-19','Monthly',700.00,'Gcash','2026-10-25 17:54:04','2026-10-25 17:54:04'),(5,NULL,'Juan Tamad','2026-10-18','Walk-in',80.00,'Cash','2026-10-25 17:54:04','2026-10-25 17:54:04'),(6,NULL,'Chris Bumstead','2026-10-18','Walk-in',80.00,'Cash','2026-10-25 17:54:04','2026-10-25 17:54:04'),(7,5,'Dwayne Tore','2026-10-17','Monthly',700.00,'Gcash','2026-10-25 17:54:04','2026-10-25 17:54:04'),(8,2,'Ahrone Magtibay','2026-10-15','Monthly',700.00,'Gcash','2026-10-25 17:54:04','2026-10-25 17:54:04'),(9,6,'Emman Pico','2026-10-15','Monthly',700.00,'Gcash','2026-10-25 17:54:04','2026-10-25 17:54:04');
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `staff_notifications` WRITE;
/*!40000 ALTER TABLE `staff_notifications` DISABLE KEYS */;
INSERT INTO `staff_notifications` (`id`, `title`, `body`, `kind`, `level`, `read_at`, `created_at`, `updated_at`) VALUES (1,'Unusual check-in pattern','Jaspher Sibayan checked in twice within 5 minutes at the front desk.','QR-code','success',NULL,'2026-10-25 14:54:04','2026-10-25 14:54:04'),(2,'Membership expiring soon','Dwayne Tore is on a plan that ends in 3 days.','Manual','warning',NULL,'2026-10-25 11:54:04','2026-10-25 11:54:04'),(3,'Membership expired','Emman Jalal expired and has not been renewed.','Manual','danger',NULL,'2026-10-24 15:54:04','2026-10-24 15:54:04'),(4,'New member checked in for the first time','Fabs Dela Cruz completed a first check-in using a QR code.','QR-code','success',NULL,'2026-10-23 17:54:04','2026-10-23 17:54:04'),(5,'Backup complete','Nightly database backup finished successfully.','Manual','info',NULL,'2026-10-23 15:54:04','2026-10-23 15:54:04'),(6,'Daily report ready','The check-in and revenue summary for yesterday has been generated.','Manual','info',NULL,'2026-10-22 17:54:04','2026-10-22 17:54:04'),(7,'3 members expiring next week','Review the Members page to send renewal reminders.','Manual','warning',NULL,'2026-10-22 15:54:04','2026-10-22 15:54:04');
/*!40000 ALTER TABLE `staff_notifications` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` (`id`, `user_name`, `action`, `created_at`, `updated_at`) VALUES (1,'System','loaded the sample data','2026-10-25 17:54:04','2026-10-25 17:54:04');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

