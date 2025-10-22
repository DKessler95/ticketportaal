-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: ticketportaal
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

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

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `default_priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `sla_hours` int(11) DEFAULT 24,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`category_id`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Hardware','Hardware problemen zoals computers, printers, monitors, etc.','medium',24,1,'2025-10-21 16:07:35'),(2,'Software','Software installaties, licenties en applicatie problemen','medium',24,1,'2025-10-21 16:07:35'),(3,'Netwerk','Netwerk connectiviteit, WiFi, VPN en internet problemen','high',8,1,'2025-10-21 16:07:35'),(4,'Account','Account aanvragen, wachtwoord resets en toegangsbeheer','low',48,1,'2025-10-21 16:07:35'),(5,'Email','Email problemen, configuratie en Outlook issues','medium',24,1,'2025-10-21 16:07:35'),(6,'Telefonie','Telefoon systemen, mobiele telefoons en voicemail','medium',24,1,'2025-10-21 16:07:35'),(7,'Beveiliging','Security incidents, virus meldingen en verdachte activiteiten','urgent',4,1,'2025-10-21 16:07:35'),(8,'Backup & Recovery','Data backup, restore requests en file recovery','high',8,1,'2025-10-21 16:07:35'),(9,'Ecoro SHD','Problemen met de applicatie Ecoro','medium',24,1,'2025-10-21 23:43:18'),(10,'WinqlWise','Problemen met WinqlWise','medium',24,1,'2025-10-21 23:43:38'),(11,'Kassa','Problemen met het kassa systeem.','medium',12,1,'2025-10-21 23:44:13');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `category_fields`
--

DROP TABLE IF EXISTS `category_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `category_fields` (
  `field_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `field_name` varchar(255) NOT NULL,
  `field_label` varchar(255) NOT NULL,
  `field_type` enum('text','textarea','select','radio','checkbox','date','number','email','tel') NOT NULL DEFAULT 'text',
  `field_options` text DEFAULT NULL COMMENT 'JSON array for select/radio/checkbox options',
  `is_required` tinyint(1) NOT NULL DEFAULT 0,
  `field_order` int(11) NOT NULL DEFAULT 0,
  `placeholder` varchar(255) DEFAULT NULL,
  `help_text` text DEFAULT NULL,
  `conditional_logic` text DEFAULT NULL COMMENT 'JSON for conditional field logic',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`field_id`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_is_active` (`is_active`),
  CONSTRAINT `fk_category_field` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `category_fields`
--

LOCK TABLES `category_fields` WRITE;
/*!40000 ALTER TABLE `category_fields` DISABLE KEYS */;
INSERT INTO `category_fields` VALUES (16,4,'test','Test','text',NULL,0,1,'Test','test',NULL,1,'2025-10-21 16:44:00','2025-10-22 00:38:21'),(17,4,'test2','Soort','text',NULL,0,2,'testeste','xaxax',NULL,1,'2025-10-21 16:46:24','2025-10-22 00:38:21'),(20,4,'test3','test3','radio','[\"1\",\"2\",\"3\"]',0,3,'','',NULL,1,'2025-10-21 16:52:42','2025-10-21 16:52:42'),(21,1,'type_hardware','Hardware','select','[\"Server\",\"Router\",\"Switch\",\"POE\"]',0,1,'Type hardware','',NULL,1,'2025-10-21 17:13:11','2025-10-21 17:13:11');
/*!40000 ALTER TABLE `category_fields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `change_approvals`
--

DROP TABLE IF EXISTS `change_approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `change_approvals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `change_id` int(11) NOT NULL,
  `approver_id` int(11) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `comments` text DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_change_approver` (`change_id`,`approver_id`),
  KEY `approver_id` (`approver_id`),
  CONSTRAINT `change_approvals_ibfk_1` FOREIGN KEY (`change_id`) REFERENCES `change_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `change_approvals_ibfk_2` FOREIGN KEY (`approver_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `change_approvals`
--

LOCK TABLES `change_approvals` WRITE;
/*!40000 ALTER TABLE `change_approvals` DISABLE KEYS */;
INSERT INTO `change_approvals` VALUES (1,1,1,'rejected','Aanpassen','2025-10-22 00:13:06','2025-10-22 00:13:06');
/*!40000 ALTER TABLE `change_approvals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `change_attachments`
--

DROP TABLE IF EXISTS `change_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `change_attachments` (
  `attachment_id` int(11) NOT NULL AUTO_INCREMENT,
  `change_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `filepath` varchar(500) NOT NULL,
  `filesize` int(11) NOT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`attachment_id`),
  KEY `uploaded_by` (`uploaded_by`),
  KEY `idx_change_id` (`change_id`),
  KEY `idx_uploaded_at` (`uploaded_at`),
  CONSTRAINT `change_attachments_ibfk_1` FOREIGN KEY (`change_id`) REFERENCES `changes` (`change_id`) ON DELETE CASCADE,
  CONSTRAINT `change_attachments_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `change_attachments`
--

LOCK TABLES `change_attachments` WRITE;
/*!40000 ALTER TABLE `change_attachments` DISABLE KEYS */;
/*!40000 ALTER TABLE `change_attachments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `change_ci_relations`
--

DROP TABLE IF EXISTS `change_ci_relations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `change_ci_relations` (
  `relation_id` int(11) NOT NULL AUTO_INCREMENT,
  `change_id` int(11) NOT NULL,
  `ci_id` int(11) NOT NULL,
  `relation_type` enum('affects','modifies','replaces','uses','related_to') DEFAULT 'related_to',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) NOT NULL,
  PRIMARY KEY (`relation_id`),
  UNIQUE KEY `unique_change_ci` (`change_id`,`ci_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_change_id` (`change_id`),
  KEY `idx_ci_id` (`ci_id`),
  KEY `idx_relation_type` (`relation_type`),
  CONSTRAINT `change_ci_relations_ibfk_1` FOREIGN KEY (`change_id`) REFERENCES `changes` (`change_id`) ON DELETE CASCADE,
  CONSTRAINT `change_ci_relations_ibfk_2` FOREIGN KEY (`ci_id`) REFERENCES `configuration_items` (`ci_id`) ON DELETE CASCADE,
  CONSTRAINT `change_ci_relations_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `change_ci_relations`
--

LOCK TABLES `change_ci_relations` WRITE;
/*!40000 ALTER TABLE `change_ci_relations` DISABLE KEYS */;
/*!40000 ALTER TABLE `change_ci_relations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `change_history`
--

DROP TABLE IF EXISTS `change_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `change_history` (
  `history_id` int(11) NOT NULL AUTO_INCREMENT,
  `change_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`history_id`),
  KEY `idx_change_id` (`change_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_action` (`action`),
  CONSTRAINT `change_history_ibfk_1` FOREIGN KEY (`change_id`) REFERENCES `changes` (`change_id`) ON DELETE CASCADE,
  CONSTRAINT `change_history_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `change_history`
--

LOCK TABLES `change_history` WRITE;
/*!40000 ALTER TABLE `change_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `change_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `change_logs`
--

DROP TABLE IF EXISTS `change_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `change_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `change_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_change_id` (`change_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `change_logs_ibfk_1` FOREIGN KEY (`change_id`) REFERENCES `change_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `change_logs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `change_logs`
--

LOCK TABLES `change_logs` WRITE;
/*!40000 ALTER TABLE `change_logs` DISABLE KEYS */;
INSERT INTO `change_logs` VALUES (1,1,1,'rejected',NULL,'rejected','Aanpassen','2025-10-22 00:13:06'),(2,1,1,'status_change','rejected','submitted','Aangepast','2025-10-22 00:14:27'),(3,1,1,'comment',NULL,NULL,'test','2025-10-22 00:17:42'),(4,1,1,'approved',NULL,'approved','Goed ga door','2025-10-22 01:22:54'),(5,1,1,'assigned',NULL,'System Administrator','Toegewezen aan agent','2025-10-22 01:22:57'),(6,1,1,'status_change','approved','in_progress','Bezig','2025-10-22 01:23:12'),(7,1,1,'status_change','in_progress','completed','Klaar','2025-10-22 01:23:24');
/*!40000 ALTER TABLE `change_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `change_requests`
--

DROP TABLE IF EXISTS `change_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `change_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `change_number` varchar(20) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `category` enum('infrastructure','application','security','network','hardware','software','process') NOT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `impact` enum('low','medium','high') DEFAULT 'medium',
  `risk` enum('low','medium','high') DEFAULT 'medium',
  `status` enum('draft','submitted','approved','rejected','scheduled','in_progress','completed','failed','cancelled') DEFAULT 'draft',
  `planned_start` datetime DEFAULT NULL,
  `planned_end` datetime DEFAULT NULL,
  `actual_start` datetime DEFAULT NULL,
  `actual_end` datetime DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `assigned_agent_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `change_number` (`change_number`),
  KEY `approved_by` (`approved_by`),
  KEY `idx_change_number` (`change_number`),
  KEY `idx_status` (`status`),
  KEY `idx_category` (`category`),
  KEY `idx_created_by` (`created_by`),
  CONSTRAINT `change_requests_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `change_requests_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `change_requests`
--

LOCK TABLES `change_requests` WRITE;
/*!40000 ALTER TABLE `change_requests` DISABLE KEYS */;
INSERT INTO `change_requests` VALUES (1,'CHG-2025-9530','test','test','application','medium','medium','medium','completed','2025-10-22 02:08:00','2025-10-29 02:08:00',NULL,NULL,1,1,1,'2025-10-22 00:08:45','2025-10-22 01:23:24');
/*!40000 ALTER TABLE `change_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `changes`
--

DROP TABLE IF EXISTS `changes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `changes` (
  `change_id` int(11) NOT NULL AUTO_INCREMENT,
  `change_number` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `requested_by` int(11) NOT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `type` enum('Feature','Patch','Hardware','Software','Netwerk','Infrastructuur','Overig') NOT NULL,
  `priority` enum('Laag','Normaal','Hoog','Urgent') DEFAULT 'Normaal',
  `impact` enum('Laag','Middel','Hoog') DEFAULT 'Laag',
  `status` enum('Nieuw','In beoordeling','Goedgekeurd','Ingepland','In uitvoering','Ge??mplementeerd','Afgewezen','Geannuleerd') DEFAULT 'Nieuw',
  `description` text DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `expected_result` text DEFAULT NULL,
  `affected_systems` text DEFAULT NULL,
  `affected_users` int(11) DEFAULT NULL,
  `downtime_expected` tinyint(1) DEFAULT 0,
  `downtime_duration` int(11) DEFAULT NULL COMMENT 'Duration in minutes',
  `risk_assessment` text DEFAULT NULL,
  `implementation_plan` text DEFAULT NULL,
  `rollback_plan` text DEFAULT NULL,
  `resources_needed` text DEFAULT NULL,
  `planned_start_date` datetime DEFAULT NULL,
  `planned_end_date` datetime DEFAULT NULL,
  `actual_start_date` datetime DEFAULT NULL,
  `actual_end_date` datetime DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `approval_comment` text DEFAULT NULL,
  `post_implementation_success` tinyint(1) DEFAULT NULL,
  `post_implementation_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`change_id`),
  UNIQUE KEY `change_number` (`change_number`),
  KEY `approved_by` (`approved_by`),
  KEY `idx_change_number` (`change_number`),
  KEY `idx_status` (`status`),
  KEY `idx_type` (`type`),
  KEY `idx_priority` (`priority`),
  KEY `idx_impact` (`impact`),
  KEY `idx_requested_by` (`requested_by`),
  KEY `idx_assigned_to` (`assigned_to`),
  KEY `idx_planned_start_date` (`planned_start_date`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_status_priority` (`status`,`priority`),
  CONSTRAINT `changes_ibfk_1` FOREIGN KEY (`requested_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `changes_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `changes_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `changes`
--

LOCK TABLES `changes` WRITE;
/*!40000 ALTER TABLE `changes` DISABLE KEYS */;
/*!40000 ALTER TABLE `changes` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER before_change_insert
BEFORE INSERT ON changes
FOR EACH ROW
BEGIN
    DECLARE next_number INT;
    DECLARE current_year INT;
    
    SET current_year = YEAR(CURDATE());
    
    
    UPDATE sequences 
    SET current_number = CASE 
        WHEN current_year = YEAR(CURDATE()) THEN current_number + 1
        ELSE 1
    END,
    current_year = current_year
    WHERE sequence_name = 'change_sequence';
    
    
    SELECT current_number INTO next_number
    FROM sequences
    WHERE sequence_name = 'change_sequence';
    
    
    SET NEW.change_number = CONCAT('CHG-', current_year, '-', LPAD(next_number, 3, '0'));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `ci_attachments`
--

DROP TABLE IF EXISTS `ci_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ci_attachments` (
  `attachment_id` int(11) NOT NULL AUTO_INCREMENT,
  `ci_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `filepath` varchar(500) NOT NULL,
  `filesize` int(11) NOT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`attachment_id`),
  KEY `uploaded_by` (`uploaded_by`),
  KEY `idx_ci_id` (`ci_id`),
  KEY `idx_uploaded_at` (`uploaded_at`),
  CONSTRAINT `ci_attachments_ibfk_1` FOREIGN KEY (`ci_id`) REFERENCES `configuration_items` (`ci_id`) ON DELETE CASCADE,
  CONSTRAINT `ci_attachments_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ci_attachments`
--

LOCK TABLES `ci_attachments` WRITE;
/*!40000 ALTER TABLE `ci_attachments` DISABLE KEYS */;
/*!40000 ALTER TABLE `ci_attachments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ci_history`
--

DROP TABLE IF EXISTS `ci_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ci_history` (
  `history_id` int(11) NOT NULL AUTO_INCREMENT,
  `ci_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `field_changed` varchar(100) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`history_id`),
  KEY `idx_ci_id` (`ci_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_action` (`action`),
  CONSTRAINT `ci_history_ibfk_1` FOREIGN KEY (`ci_id`) REFERENCES `configuration_items` (`ci_id`) ON DELETE CASCADE,
  CONSTRAINT `ci_history_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ci_history`
--

LOCK TABLES `ci_history` WRITE;
/*!40000 ALTER TABLE `ci_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `ci_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `configuration_items`
--

DROP TABLE IF EXISTS `configuration_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `configuration_items` (
  `ci_id` int(11) NOT NULL AUTO_INCREMENT,
  `ci_number` varchar(50) NOT NULL,
  `type` enum('Hardware','Software','Licentie','Overig') NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `serial_number` varchar(255) DEFAULT NULL,
  `asset_tag` varchar(100) DEFAULT NULL,
  `status` enum('In gebruik','In voorraad','Defect','Afgeschreven','Onderhoud') NOT NULL DEFAULT 'In voorraad',
  `owner_id` int(11) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `purchase_price` decimal(10,2) DEFAULT NULL,
  `supplier` varchar(255) DEFAULT NULL,
  `warranty_expiry` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) NOT NULL,
  PRIMARY KEY (`ci_id`),
  UNIQUE KEY `ci_number` (`ci_number`),
  UNIQUE KEY `serial_number` (`serial_number`),
  KEY `created_by` (`created_by`),
  KEY `idx_ci_number` (`ci_number`),
  KEY `idx_serial_number` (`serial_number`),
  KEY `idx_status` (`status`),
  KEY `idx_type` (`type`),
  KEY `idx_category` (`category`),
  KEY `idx_owner_id` (`owner_id`),
  KEY `idx_department` (`department`),
  KEY `idx_warranty_expiry` (`warranty_expiry`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_status_type` (`status`,`type`),
  CONSTRAINT `configuration_items_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `configuration_items_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `configuration_items`
--

LOCK TABLES `configuration_items` WRITE;
/*!40000 ALTER TABLE `configuration_items` DISABLE KEYS */;
INSERT INTO `configuration_items` VALUES (1,'CI-2025-001','Hardware','Laptop','Dell','Latitude 5420','Dell Laptop - Sales Manager','DL5420-2024-001',NULL,'In gebruik',2,'Sales','Kantoor','2024-01-15',899.00,'Dell Nederland','2027-01-15','Standaard configuratie met Windows 11 Pro','2025-10-21 14:25:14','2025-10-21 14:25:14',1),(2,'CI-2025-002','Hardware','Desktop','HP','EliteDesk 800','HP Desktop - Administratie','HP800-2024-002',NULL,'In gebruik',2,'Administratie','Kantoor','2024-02-20',1299.00,'HP Store','2027-02-20','i7 processor, 16GB RAM, 512GB SSD','2025-10-21 14:25:14','2025-10-21 14:25:14',1),(3,'CI-2025-003','Hardware','Monitor','Samsung','27\" 4K','Samsung Monitor 27 inch','SAM27-2024-003',NULL,'In gebruik',2,'ICT','Kantoor','2024-03-10',349.00,'Coolblue','2026-03-10',NULL,'2025-10-21 14:25:14','2025-10-21 14:25:14',1),(4,'CI-2025-004','Hardware','Printer','HP','LaserJet Pro','HP Printer - Algemeen','HPLJ-2023-004',NULL,'In gebruik',NULL,'Algemeen','Kantoor - Gang','2023-06-01',599.00,'Office Centre','2026-06-01','Netwerk printer voor hele kantoor','2025-10-21 14:25:14','2025-10-21 14:25:14',1),(5,'CI-2025-005','Software','Licentie','Microsoft','Office 365','Microsoft 365 Business - 10 licenties','M365-2024-001',NULL,'In gebruik',NULL,'ICT','Cloud','2024-01-01',1200.00,'Microsoft','2025-01-01','Jaarlijkse licentie, verlengen in december','2025-10-21 14:25:14','2025-10-21 14:25:14',1),(6,'CI-2025-006','Hardware','Router','Cisco','RV340','Cisco Router - Hoofdkantoor','CISCO-RV340-001',NULL,'In gebruik',NULL,'ICT','Serverruimte','2023-03-15',450.00,'Cisco Partner','2026-03-15','VPN configuratie actief','2025-10-21 14:25:14','2025-10-21 14:25:14',1),(7,'CI-2025-007','Hardware','Laptop','Lenovo','ThinkPad X1','Lenovo Laptop - Directie','LEN-X1-2024-007',NULL,'In gebruik',1,'Directie','Kantoor','2024-05-20',1599.00,'Lenovo Store','2027-05-20','Premium model met extra garantie','2025-10-21 14:25:14','2025-10-21 14:25:14',1),(8,'CI-2025-008','Hardware','Server','Dell','PowerEdge T340','Dell Server - Applicaties','DELL-T340-2023',NULL,'In gebruik',NULL,'ICT','Serverruimte','2023-01-10',2499.00,'Dell Nederland','2026-01-10','Draait ticketportaal en andere applicaties','2025-10-21 14:25:14','2025-10-21 14:25:14',1),(9,'CI-2025-009','Hardware','Telefoon','Apple','iPhone 14','iPhone - Sales','IPHONE14-2024-009',NULL,'In gebruik',2,'Sales','Mobiel','2024-04-01',899.00,'Apple Store','2026-04-01','Zakelijk abonnement','2025-10-21 14:25:14','2025-10-21 14:25:14',1),(10,'CI-2025-010','Hardware','Monitor','Dell','24\" Full HD','Dell Monitor - Reserve','DELL24-2023-010',NULL,'In voorraad',NULL,'ICT','Magazijn','2023-11-15',199.00,'Coolblue','2025-11-15','Reserve monitor voor vervanging','2025-10-21 14:25:14','2025-10-21 14:25:14',1);
/*!40000 ALTER TABLE `configuration_items` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER before_ci_insert
BEFORE INSERT ON configuration_items
FOR EACH ROW
BEGIN
    DECLARE next_number INT;
    DECLARE current_year INT;
    
    SET current_year = YEAR(CURDATE());
    
    
    UPDATE sequences 
    SET current_number = CASE 
        WHEN current_year = YEAR(CURDATE()) THEN current_number + 1
        ELSE 1
    END,
    current_year = current_year
    WHERE sequence_name = 'ci_sequence';
    
    
    SELECT current_number INTO next_number
    FROM sequences
    WHERE sequence_name = 'ci_sequence';
    
    
    SET NEW.ci_number = CONCAT('CI-', current_year, '-', LPAD(next_number, 3, '0'));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `departments` (
  `department_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`department_id`),
  UNIQUE KEY `unique_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (1,'Financiën','Financiële administratie en boekhouding',1,'2025-10-21 17:28:18'),(2,'Service','Klantenservice en support',1,'2025-10-21 17:28:18'),(3,'Directie','Management en directie',1,'2025-10-21 17:28:18'),(4,'Magazijn','Magazijn en voorraad beheer',1,'2025-10-21 17:28:18'),(5,'Transport','Transport en logistiek',1,'2025-10-21 17:28:18'),(6,'Planning','Planning en coördinatie',1,'2025-10-21 17:28:18'),(7,'ICT','ICT afdeling en technische support',1,'2025-10-21 17:28:18'),(8,'Externe partij','Externe medewerkers en partners',1,'2025-10-21 17:28:18'),(9,'Sales','Sales afdeling',1,'2025-10-22 00:51:51'),(10,'Facilitair','Facilitaire diensten',1,'2025-10-22 00:51:51');
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `knowledge_base`
--

DROP TABLE IF EXISTS `knowledge_base`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `knowledge_base` (
  `kb_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `tags` text DEFAULT NULL,
  `author_id` int(11) NOT NULL,
  `views` int(11) DEFAULT 0,
  `is_published` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`kb_id`),
  KEY `idx_published` (`is_published`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_author_id` (`author_id`),
  KEY `idx_views` (`views`),
  FULLTEXT KEY `idx_search` (`title`,`content`),
  CONSTRAINT `knowledge_base_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`),
  CONSTRAINT `knowledge_base_ibfk_2` FOREIGN KEY (`author_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `knowledge_base`
--

LOCK TABLES `knowledge_base` WRITE;
/*!40000 ALTER TABLE `knowledge_base` DISABLE KEYS */;
INSERT INTO `knowledge_base` VALUES (2,3,'Connecting to the Company VPN','# VPN Connection Guide\r\n\r\nTo connect to the Kruit & Kramer VPN:\r\n\r\n## Windows\r\n1. Click the network icon in the system tray\r\n2. Select \"VPN\" from the menu\r\n3. Choose \"Kruit & Kramer VPN\"\r\n4. Enter your company credentials\r\n5. Click \"Connect\"\r\n\r\n## Mac\r\n1. Open System Preferences\r\n2. Click \"Network\"\r\n3. Select the VPN connection\r\n4. Click \"Connect\"\r\n5. Enter your credentials\r\n\r\nIf you experience connection issues, ensure you have the latest VPN client installed. Contact ICT support for assistance.','vpn,network,remote,connection',1,5,1,'2025-10-21 11:58:23','2025-10-21 13:54:47'),(4,5,'Email Configuration on Mobile Devices','# Mobile Email Setup\r\n\r\n## iOS (iPhone/iPad)\r\n1. Go to Settings > Mail > Accounts\r\n2. Tap \"Add Account\"\r\n3. Select \"Other\"\r\n4. Enter your email and password\r\n5. Use these settings:\r\n   - Incoming Mail Server: mail.kruit-en-kramer.nl\r\n   - Outgoing Mail Server: mail.kruit-en-kramer.nl\r\n   - Use SSL: Yes\r\n\r\n## Android\r\n1. Open Email app\r\n2. Tap \"Add Account\"\r\n3. Select \"Other\"\r\n4. Enter your email address\r\n5. Choose IMAP\r\n6. Enter the same server settings as above\r\n\r\nContact ICT if you need assistance with configuration.','email,mobile,configuration,setup,imap',1,0,1,'2025-10-21 11:58:23','2025-10-21 11:58:23'),(5,2,'How to Reset Your Password','# Password Reset Instructions\n\nIf you have forgotten your password, follow these steps:\n\n1. Go to the login page\n2. Click on \"Forgot Password\" link\n3. Enter your email address\n4. Check your email for a reset link\n5. Click the link and enter your new password\n6. Your password must be at least 8 characters and contain letters and numbers\n\nIf you do not receive the email within 5 minutes, check your spam folder or contact ICT support.','password,reset,login,account',1,6,1,'2025-10-21 13:52:03','2025-10-22 01:14:04'),(6,3,'Connecting to the Company VPN','<h1>VPN Connection Guide</h1>\n<p>To connect to the Kruit &amp; Kramer VPN:</p>\n<h2>Windows</h2>\n<ol>\n<li>Click the network icon in the system tray</li>\n<li>Select \"VPN\" from the menu</li>\n<li>Choose \"Kruit &amp; Kramer VPN\"</li>\n<li>Enter your company credentials</li>\n<li>Click \"Connect\"</li>\n</ol>\n<h2>Mac</h2>\n<ol>\n<li>Open System Preferences</li>\n<li>Click \"Network\"</li>\n<li>Select the VPN connection</li>\n<li>Click \"Connect\"</li>\n<li>Enter your credentials</li>\n</ol>\n<p>If you experience connection issues, ensure you have the latest VPN client installed. Contact ICT support for assistance.</p>','vpn,network,remote,connection',1,3,1,'2025-10-21 13:52:03','2025-10-21 14:31:52'),(7,1,'Printer Not Working - Troubleshooting','<h1>Printer Troubleshooting Guide</h1>\n<p>Before creating a ticket, try these steps:</p>\n<ol>\n<li><strong>Check Power</strong>: Ensure the printer is turned on and plugged in</li>\n<li><strong>Check Connection</strong>: Verify the USB or network cable is connected</li>\n<li><strong>Check Paper</strong>: Make sure there is paper in the tray</li>\n<li><strong>Check Ink/Toner</strong>: Verify ink or toner levels</li>\n<li><strong>Restart Printer</strong>: Turn off the printer, wait 30 seconds, turn it back on</li>\n<li><strong>Check Print Queue</strong>: Clear any stuck print jobs on your computer</li>\n<li><strong>Restart Computer</strong>: Sometimes a simple restart resolves the issue</li>\n</ol>\n<p>If none of these steps work, create a ticket with details about the error message or problem.</p>','printer,hardware,troubleshooting,printing',1,0,1,'2025-10-21 13:52:03','2025-10-21 13:52:03'),(8,5,'Email Configuration on Mobile Devices','# Mobile Email Setup\n\n## iOS (iPhone/iPad)\n1. Go to Settings > Mail > Accounts\n2. Tap \"Add Account\"\n3. Select \"Other\"\n4. Enter your email and password\n5. Use these settings:\n   - Incoming Mail Server: mail.kruit-en-kramer.nl\n   - Outgoing Mail Server: mail.kruit-en-kramer.nl\n   - Use SSL: Yes\n\n## Android\n1. Open Email app\n2. Tap \"Add Account\"\n3. Select \"Other\"\n4. Enter your email address\n5. Choose IMAP\n6. Enter the same server settings as above\n\nContact ICT if you need assistance with configuration.','email,mobile,configuration,setup,imap',1,0,1,'2025-10-21 13:52:03','2025-10-21 13:52:03');
/*!40000 ALTER TABLE `knowledge_base` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_resets` (
  `reset_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`reset_id`),
  UNIQUE KEY `token` (`token`),
  KEY `idx_token` (`token`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_expires_at` (`expires_at`),
  CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sequences`
--

DROP TABLE IF EXISTS `sequences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sequences` (
  `sequence_name` varchar(50) NOT NULL,
  `current_year` int(11) NOT NULL,
  `current_number` int(11) NOT NULL DEFAULT 0,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`sequence_name`),
  KEY `idx_sequence_name` (`sequence_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sequences`
--

LOCK TABLES `sequences` WRITE;
/*!40000 ALTER TABLE `sequences` DISABLE KEYS */;
INSERT INTO `sequences` VALUES ('change_sequence',2025,0,'2025-10-21 14:25:02'),('ci_sequence',2025,10,'2025-10-21 14:25:14');
/*!40000 ALTER TABLE `sequences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `session_id` varchar(128) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_data` text DEFAULT NULL,
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`session_id`),
  KEY `idx_last_activity` (`last_activity`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket_attachments`
--

DROP TABLE IF EXISTS `ticket_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ticket_attachments` (
  `attachment_id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `filepath` varchar(500) NOT NULL,
  `filesize` int(11) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`attachment_id`),
  KEY `idx_ticket_id` (`ticket_id`),
  CONSTRAINT `ticket_attachments_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`ticket_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ticket_attachments`
--

LOCK TABLES `ticket_attachments` WRITE;
/*!40000 ALTER TABLE `ticket_attachments` DISABLE KEYS */;
/*!40000 ALTER TABLE `ticket_attachments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket_change_relations`
--

DROP TABLE IF EXISTS `ticket_change_relations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ticket_change_relations` (
  `relation_id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `change_id` int(11) NOT NULL,
  `relation_type` enum('caused_by','resolved_by','related_to') DEFAULT 'related_to',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) NOT NULL,
  PRIMARY KEY (`relation_id`),
  UNIQUE KEY `unique_ticket_change` (`ticket_id`,`change_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_ticket_id` (`ticket_id`),
  KEY `idx_change_id` (`change_id`),
  KEY `idx_relation_type` (`relation_type`),
  CONSTRAINT `ticket_change_relations_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`ticket_id`) ON DELETE CASCADE,
  CONSTRAINT `ticket_change_relations_ibfk_2` FOREIGN KEY (`change_id`) REFERENCES `changes` (`change_id`) ON DELETE CASCADE,
  CONSTRAINT `ticket_change_relations_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ticket_change_relations`
--

LOCK TABLES `ticket_change_relations` WRITE;
/*!40000 ALTER TABLE `ticket_change_relations` DISABLE KEYS */;
/*!40000 ALTER TABLE `ticket_change_relations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket_ci_relations`
--

DROP TABLE IF EXISTS `ticket_ci_relations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ticket_ci_relations` (
  `relation_id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `ci_id` int(11) NOT NULL,
  `relation_type` enum('affects','caused_by','resolved_by','related_to') DEFAULT 'related_to',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) NOT NULL,
  PRIMARY KEY (`relation_id`),
  UNIQUE KEY `unique_ticket_ci` (`ticket_id`,`ci_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_ticket_id` (`ticket_id`),
  KEY `idx_ci_id` (`ci_id`),
  KEY `idx_relation_type` (`relation_type`),
  CONSTRAINT `ticket_ci_relations_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`ticket_id`) ON DELETE CASCADE,
  CONSTRAINT `ticket_ci_relations_ibfk_2` FOREIGN KEY (`ci_id`) REFERENCES `configuration_items` (`ci_id`) ON DELETE CASCADE,
  CONSTRAINT `ticket_ci_relations_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ticket_ci_relations`
--

LOCK TABLES `ticket_ci_relations` WRITE;
/*!40000 ALTER TABLE `ticket_ci_relations` DISABLE KEYS */;
/*!40000 ALTER TABLE `ticket_ci_relations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket_comments`
--

DROP TABLE IF EXISTS `ticket_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ticket_comments` (
  `comment_id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `is_internal` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`comment_id`),
  KEY `idx_ticket_id` (`ticket_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_ticket_internal` (`ticket_id`,`is_internal`),
  CONSTRAINT `ticket_comments_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`ticket_id`) ON DELETE CASCADE,
  CONSTRAINT `ticket_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ticket_comments`
--

LOCK TABLES `ticket_comments` WRITE;
/*!40000 ALTER TABLE `ticket_comments` DISABLE KEYS */;
INSERT INTO `ticket_comments` VALUES (1,1,2,'Test',0,'2025-10-21 12:16:38'),(2,1,1,'<p>Goed?</p>',0,'2025-10-21 22:23:43');
/*!40000 ALTER TABLE `ticket_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket_field_values`
--

DROP TABLE IF EXISTS `ticket_field_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ticket_field_values` (
  `value_id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `field_id` int(11) NOT NULL,
  `field_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`value_id`),
  UNIQUE KEY `unique_ticket_field` (`ticket_id`,`field_id`),
  KEY `idx_ticket_id` (`ticket_id`),
  KEY `idx_field_id` (`field_id`),
  CONSTRAINT `fk_ticket_field_field` FOREIGN KEY (`field_id`) REFERENCES `category_fields` (`field_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ticket_field_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`ticket_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ticket_field_values`
--

LOCK TABLES `ticket_field_values` WRITE;
/*!40000 ALTER TABLE `ticket_field_values` DISABLE KEYS */;
/*!40000 ALTER TABLE `ticket_field_values` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket_templates`
--

DROP TABLE IF EXISTS `ticket_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ticket_templates` (
  `template_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `content` text NOT NULL,
  `template_type` enum('resolution','comment','email') NOT NULL DEFAULT 'resolution',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`template_id`),
  KEY `idx_template_type` (`template_type`),
  KEY `idx_is_active` (`is_active`),
  KEY `fk_template_created_by` (`created_by`),
  CONSTRAINT `fk_template_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ticket_templates`
--

LOCK TABLES `ticket_templates` WRITE;
/*!40000 ALTER TABLE `ticket_templates` DISABLE KEYS */;
INSERT INTO `ticket_templates` VALUES (10,'Ticket Succesvol Afgesloten','Standaard template voor succesvolle afsluiting','<p><strong>Beste [Naam],</strong></p><p>Uw ticket is succesvol afgehandeld en opgelost.</p><p><strong>Uitgevoerde acties:</strong></p><ul><li>[Beschrijf hier de uitgevoerde acties]</li></ul><p><strong>Resultaat:</strong><br>Het probleem is opgelost en u kunt weer verder werken.</p><p>Mocht u nog vragen hebben of het probleem opnieuw optreden, aarzel dan niet om contact met ons op te nemen.</p><hr><p>Met vriendelijke groet,</p><p><strong>Damian Kessler</strong><br>ICT Medewerker</p><p><img src=\"http://localhost/ticketportaal/assets/images/logo/Kruit/logo.svg\" alt=\"Kruit & Kramer\" style=\"max-width: 150px; margin-top: 10px;\"><br><strong>Kruit & Kramer</strong><br>ICT Support Team<br>Email: support@kruitkramer.nl<br>Tel: 777 / ICT afdeling intern</p>','resolution',1,1,'2025-10-22 01:11:26','2025-10-22 01:11:26'),(11,'Probleem Opgelost - Configuratie Aangepast','Voor configuratie wijzigingen','<p><strong>Beste [Naam],</strong></p><p>Het gemelde probleem is opgelost door een configuratie aanpassing.</p><p><strong>Wat is er aangepast:</strong></p><ul><li>[Beschrijf de configuratie wijziging]</li><li>[Eventuele impact of wijzigingen voor gebruiker]</li></ul><p><strong>Actie vereist:</strong><br>[Indien van toepassing: beschrijf wat gebruiker moet doen]</p><p>De wijzigingen zijn direct actief. Test de functionaliteit en laat het ons weten als er nog problemen zijn.</p><hr><p>Met vriendelijke groet,</p><p><strong>Damian Kessler</strong><br>ICT Medewerker</p><p><img src=\"http://localhost/ticketportaal/assets/images/logo/Kruit/logo.svg\" alt=\"Kruit & Kramer\" style=\"max-width: 150px; margin-top: 10px;\"><br><strong>Kruit & Kramer</strong><br>ICT Support Team<br>Email: support@kruitkramer.nl<br>Tel: 777 / ICT afdeling intern</p>','resolution',1,1,'2025-10-22 01:11:26','2025-10-22 01:11:26'),(12,'Probleem Opgelost - Software Update','Voor software updates/patches','<p><strong>Beste [Naam],</strong></p><p>Het probleem is opgelost door een software update uit te voeren.</p><p><strong>Details:</strong></p><ul><li><strong>Software:</strong> [Naam software]</li><li><strong>Oude versie:</strong> [Versienummer]</li><li><strong>Nieuwe versie:</strong> [Versienummer]</li><li><strong>Wijzigingen:</strong> [Belangrijkste verbeteringen]</li></ul><p><strong>Let op:</strong><br>[Eventuele wijzigingen in gebruik of nieuwe functies]</p><p>De update is succesvol geïnstalleerd en getest. U kunt de software weer normaal gebruiken.</p><hr><p>Met vriendelijke groet,</p><p><strong>Damian Kessler</strong><br>ICT Medewerker</p><p><img src=\"http://localhost/ticketportaal/assets/images/logo/Kruit/logo.svg\" alt=\"Kruit & Kramer\" style=\"max-width: 150px; margin-top: 10px;\"><br><strong>Kruit & Kramer</strong><br>ICT Support Team<br>Email: support@kruitkramer.nl<br>Tel: 777 / ICT afdeling intern</p>','resolution',1,1,'2025-10-22 01:11:26','2025-10-22 01:11:26'),(13,'Ticket Afgesloten - Geen Actie Nodig','Voor tickets die geen actie vereisen','<p><strong>Beste [Naam],</strong></p><p>Na analyse van uw melding blijkt dat er geen actie nodig is.</p><p><strong>Reden:</strong></p><ul><li>[Uitleg waarom geen actie nodig is]</li><li>[Eventuele achtergrond informatie]</li></ul><p>Mocht u toch verdere ondersteuning nodig hebben, kunt u een nieuw ticket aanmaken of reageren op dit ticket.</p><hr><p>Met vriendelijke groet,</p><p><strong>Damian Kessler</strong><br>ICT Medewerker</p><p><img src=\"http://localhost/ticketportaal/assets/images/logo/Kruit/logo.svg\" alt=\"Kruit & Kramer\" style=\"max-width: 150px; margin-top: 10px;\"><br><strong>Kruit & Kramer</strong><br>ICT Support Team<br>Email: support@kruitkramer.nl<br>Tel: 777 / ICT afdeling intern</p>','resolution',1,1,'2025-10-22 01:11:26','2025-10-22 01:11:26'),(14,'Meer Informatie Nodig','Vraag om aanvullende informatie','<p><strong>Beste [Naam],</strong></p><p>Om uw ticket goed te kunnen behandelen, hebben wij aanvullende informatie nodig:</p><ul><li>[Vraag 1]</li><li>[Vraag 2]</li><li>[Vraag 3]</li></ul><p><strong>Graag ontvangen wij:</strong></p><ul><li>Screenshots van het probleem (indien van toepassing)</li><li>Exacte foutmeldingen</li><li>Tijdstip waarop het probleem optreedt</li></ul><p>Zodra wij deze informatie hebben ontvangen, kunnen wij verder met de behandeling van uw ticket.</p><hr><p>Met vriendelijke groet,</p><p><strong>Damian Kessler</strong><br>ICT Medewerker</p><p><img src=\"http://localhost/ticketportaal/assets/images/logo/Kruit/logo.svg\" alt=\"Kruit & Kramer\" style=\"max-width: 150px; margin-top: 10px;\"><br><strong>Kruit & Kramer</strong><br>ICT Support Team</p>','comment',1,1,'2025-10-22 01:11:26','2025-10-22 01:11:26'),(15,'In Behandeling - Analyse Gestart','Bevestiging dat ticket in behandeling is','<p><strong>Beste [Naam],</strong></p><p>Uw ticket is in behandeling genomen en wij zijn gestart met de analyse van het probleem.</p><p><strong>Huidige status:</strong></p><ul><li>Ticket toegewezen aan: [Naam agent]</li><li>Verwachte oplostijd: [Tijdsindicatie]</li><li>Prioriteit: [Prioriteit niveau]</li></ul><p>Wij houden u op de hoogte van de voortgang. Mocht u tussentijds vragen hebben, kunt u reageren op dit ticket.</p><hr><p>Met vriendelijke groet,</p><p><strong>Damian Kessler</strong><br>ICT Medewerker</p><p><img src=\"http://localhost/ticketportaal/assets/images/logo/Kruit/logo.svg\" alt=\"Kruit & Kramer\" style=\"max-width: 150px; margin-top: 10px;\"><br><strong>Kruit & Kramer</strong><br>ICT Support Team</p>','comment',1,1,'2025-10-22 01:11:26','2025-10-22 01:11:26'),(16,'Wijziging Verwerkt','Bevestiging van doorgevoerde wijziging','<p><strong>Beste [Naam],</strong></p><p>De door u gevraagde wijziging is succesvol verwerkt.</p><p><strong>Doorgevoerde wijzigingen:</strong></p><ul><li>[Wijziging 1]</li><li>[Wijziging 2]</li><li>[Wijziging 3]</li></ul><p><strong>Ingangsdatum:</strong> [Datum en tijd]</p><p>De wijzigingen zijn direct actief. Controleer of alles naar wens werkt en laat het ons weten als er aanpassingen nodig zijn.</p><hr><p>Met vriendelijke groet,</p><p><strong>Damian Kessler</strong><br>ICT Medewerker</p><p><img src=\"http://localhost/ticketportaal/assets/images/logo/Kruit/logo.svg\" alt=\"Kruit & Kramer\" style=\"max-width: 150px; margin-top: 10px;\"><br><strong>Kruit & Kramer</strong><br>ICT Support Team</p>','comment',1,1,'2025-10-22 01:11:26','2025-10-22 01:11:26'),(17,'Account Gegevens Verstrekt','Voor het verstrekken van account informatie','<p><strong>Beste [Naam],</strong></p><p>Uw account is aangemaakt en de gegevens zijn als volgt:</p><p><strong>Account informatie:</strong></p><ul><li><strong>Gebruikersnaam:</strong> [Gebruikersnaam]</li><li><strong>E-mailadres:</strong> [E-mailadres]</li><li><strong>Toegang tot:</strong> [Systemen/Applicaties]</li></ul><p><strong>Eerste keer inloggen:</strong></p><ol><li>U ontvangt een aparte e-mail met uw tijdelijke wachtwoord</li><li>Log in met uw gebruikersnaam en tijdelijke wachtwoord</li><li>U wordt gevraagd een nieuw wachtwoord in te stellen</li></ol><p><strong>Wachtwoord eisen:</strong></p><ul><li>Minimaal 8 tekens</li><li>Minimaal 1 hoofdletter</li><li>Minimaal 1 cijfer</li><li>Minimaal 1 speciaal teken</li></ul><p>Mocht u problemen ondervinden bij het inloggen, neem dan contact met ons op.</p><hr><p>Met vriendelijke groet,</p><p><strong>Damian Kessler</strong><br>ICT Medewerker</p><p><img src=\"http://localhost/ticketportaal/assets/images/logo/Kruit/logo.svg\" alt=\"Kruit & Kramer\" style=\"max-width: 150px; margin-top: 10px;\"><br><strong>Kruit & Kramer</strong><br>ICT Support Team</p>','comment',1,1,'2025-10-22 01:11:26','2025-10-22 01:11:26'),(18,'Wachtend op Externe Partij','Voor tickets die wachten op derden','<p><strong>Beste [Naam],</strong></p><p>Uw ticket is momenteel in behandeling, maar wij wachten op een reactie van een externe partij.</p><p><strong>Details:</strong></p><ul><li><strong>Externe partij:</strong> [Naam leverancier/partij]</li><li><strong>Verwachte reactietijd:</strong> [Tijdsindicatie]</li><li><strong>Referentienummer:</strong> [Ticket/case nummer bij externe partij]</li></ul><p>Zodra wij een reactie hebben ontvangen, gaan wij direct verder met de behandeling van uw ticket. Wij houden u op de hoogte.</p><hr><p>Met vriendelijke groet,</p><p><strong>Damian Kessler</strong><br>ICT Medewerker</p><p><img src=\"http://localhost/ticketportaal/assets/images/logo/Kruit/logo.svg\" alt=\"Kruit & Kramer\" style=\"max-width: 150px; margin-top: 10px;\"><br><strong>Kruit & Kramer</strong><br>ICT Support Team</p>','comment',1,1,'2025-10-22 01:11:26','2025-10-22 01:11:26'),(19,'Tijdelijke Oplossing - Workaround','Voor tijdelijke oplossingen','<p><strong>Beste [Naam],</strong></p><p>Wij hebben een tijdelijke oplossing (workaround) voor uw probleem.</p><p><strong>Tijdelijke oplossing:</strong></p><ol><li>[Stap 1]</li><li>[Stap 2]</li><li>[Stap 3]</li></ol><p><strong>Let op:</strong> Dit is een tijdelijke oplossing. Wij werken aan een definitieve oplossing.</p><p><strong>Status definitieve oplossing:</strong><br>[Beschrijf de voortgang van de definitieve oplossing]</p><p>U kunt met deze workaround verder werken. Wij houden u op de hoogte van de definitieve oplossing.</p><hr><p>Met vriendelijke groet,</p><p><strong>Damian Kessler</strong><br>ICT Medewerker</p><p><img src=\"http://localhost/ticketportaal/assets/images/logo/Kruit/logo.svg\" alt=\"Kruit & Kramer\" style=\"max-width: 150px; margin-top: 10px;\"><br><strong>Kruit & Kramer</strong><br>ICT Support Team</p>','comment',1,1,'2025-10-22 01:11:26','2025-10-22 01:11:26'),(20,'Geplande Onderhoudsmelding','Voor geplande onderhoudswerkzaamheden','<p><strong>Beste [Naam],</strong></p><p>In verband met uw ticket willen wij u informeren over geplande onderhoudswerkzaamheden.</p><p><strong>Onderhoudsdetails:</strong></p><ul><li><strong>Datum:</strong> [Datum]</li><li><strong>Tijd:</strong> [Starttijd] - [Eindtijd]</li><li><strong>Betrokken systemen:</strong> [Systemen/Applicaties]</li><li><strong>Verwachte impact:</strong> [Beschrijving impact]</li></ul><p><strong>Wat betekent dit voor u:</strong><br>[Uitleg over de gevolgen voor de gebruiker]</p><p>Na het onderhoud verwachten wij dat uw probleem is opgelost. Wij houden u op de hoogte.</p><hr><p>Met vriendelijke groet,</p><p><strong>Damian Kessler</strong><br>ICT Medewerker</p><p><img src=\"http://localhost/ticketportaal/assets/images/logo/Kruit/logo.svg\" alt=\"Kruit & Kramer\" style=\"max-width: 150px; margin-top: 10px;\"><br><strong>Kruit & Kramer</strong><br>ICT Support Team</p>','comment',1,1,'2025-10-22 01:11:26','2025-10-22 01:11:26'),(21,'Change Afgekeurd','Template voor afwijzing van change request','<p><strong>Change Request Afgekeurd</strong></p><p>Uw change request is beoordeeld en helaas afgekeurd.</p><p><strong>Reden van afwijzing:</strong></p><p>[Beschrijf hier de reden waarom de change is afgekeurd]</p><p><strong>Aanbevelingen:</strong></p><ul><li>[Aanbeveling 1]</li><li>[Aanbeveling 2]</li></ul><p><strong>Vervolgstappen:</strong></p><ul><li>U kunt de change aanpassen en opnieuw indienen</li><li>Neem contact op met de Change Manager voor meer informatie</li><li>Overweeg alternatieve oplossingen</li></ul><p>Voor vragen kunt u contact opnemen met het Change Management team.</p><hr><p>Met vriendelijke groet,</p><p><strong>Damian Kessler</strong><br>ICT Medewerker<br>Change Management Team</p><p><img src=\"http://localhost/ticketportaal/assets/images/logo/Kruit/logo.svg\" alt=\"Kruit & Kramer\" style=\"max-width: 150px; margin-top: 10px;\"><br><strong>Kruit & Kramer</strong><br>Email: change@kruitkramer.nl<br>Tel: 777 / ICT afdeling intern</p>','comment',1,1,'2025-10-22 01:11:26','2025-10-22 01:11:26'),(22,'Change Goedgekeurd','Template voor goedkeuring van change request','<p><strong>Change Request Goedgekeurd</strong></p><p>Uw change request is beoordeeld en goedgekeurd.</p><p><strong>Goedgekeurde change:</strong></p><ul><li><strong>Change ID:</strong> [Change nummer]</li><li><strong>Titel:</strong> [Change titel]</li><li><strong>Geplande uitvoering:</strong> [Datum en tijd]</li></ul><p><strong>Volgende stappen:</strong></p><ol><li>Change wordt ingepland in de change kalender</li><li>Betrokken partijen worden geïnformeerd</li><li>Implementatie wordt voorbereid</li><li>U ontvangt een bevestiging voor de uitvoering</li></ol><p><strong>Belangrijk:</strong></p><ul><li>Zorg dat alle voorbereidingen zijn getroffen</li><li>Communiceer eventuele wijzigingen tijdig</li><li>Houd rekening met de geplande downtime</li></ul><hr><p>Met vriendelijke groet,</p><p><strong>Damian Kessler</strong><br>ICT Medewerker<br>Change Management Team</p><p><img src=\"http://localhost/ticketportaal/assets/images/logo/Kruit/logo.svg\" alt=\"Kruit & Kramer\" style=\"max-width: 150px; margin-top: 10px;\"><br><strong>Kruit & Kramer</strong><br>Email: change@kruitkramer.nl<br>Tel: 777 / ICT afdeling intern</p>','comment',1,1,'2025-10-22 01:11:26','2025-10-22 01:11:26'),(23,'Change In Uitvoering','Template voor change in uitvoering','<p><strong>Change In Uitvoering</strong></p><p>De goedgekeurde change wordt momenteel uitgevoerd.</p><p><strong>Status:</strong></p><ul><li><strong>Start uitvoering:</strong> [Datum en tijd]</li><li><strong>Verwachte eindtijd:</strong> [Datum en tijd]</li><li><strong>Huidige fase:</strong> [Beschrijving huidige fase]</li></ul><p><strong>Voortgang:</strong></p><ol><li>[Stap 1] - ✓ Voltooid</li><li>[Stap 2] - ⏳ In uitvoering</li><li>[Stap 3] - ⏸ Nog te doen</li></ol><p>Wij houden u op de hoogte van de voortgang. Bij vragen of problemen kunt u contact opnemen met het implementatie team.</p><hr><p>Met vriendelijke groet,</p><p><strong>Damian Kessler</strong><br>ICT Medewerker<br>Change Management Team</p><p><img src=\"http://localhost/ticketportaal/assets/images/logo/Kruit/logo.svg\" alt=\"Kruit & Kramer\" style=\"max-width: 150px; margin-top: 10px;\"><br><strong>Kruit & Kramer</strong><br>Email: change@kruitkramer.nl<br>Tel: 777 / ICT afdeling intern</p>','comment',1,1,'2025-10-22 01:11:26','2025-10-22 01:11:26'),(24,'Change Succesvol Afgerond','Template voor succesvolle change','<p><strong>Change Succesvol Afgerond</strong></p><p>De change is succesvol uitgevoerd en afgerond.</p><p><strong>Resultaat:</strong></p><ul><li><strong>Uitgevoerd op:</strong> [Datum en tijd]</li><li><strong>Duur:</strong> [Tijdsduur]</li><li><strong>Status:</strong> Succesvol afgerond</li></ul><p><strong>Doorgevoerde wijzigingen:</strong></p><ul><li>[Wijziging 1]</li><li>[Wijziging 2]</li><li>[Wijziging 3]</li></ul><p><strong>Verificatie:</strong></p><ul><li>Alle testen zijn succesvol uitgevoerd</li><li>Systemen zijn operationeel</li><li>Geen onverwachte problemen geconstateerd</li></ul><p><strong>Actie vereist:</strong><br>[Indien van toepassing: beschrijf wat gebruikers moeten doen]</p><p>Mocht u problemen ondervinden na deze change, neem dan direct contact met ons op.</p><hr><p>Met vriendelijke groet,</p><p><strong>Damian Kessler</strong><br>ICT Medewerker<br>Change Management Team</p><p><img src=\"http://localhost/ticketportaal/assets/images/logo/Kruit/logo.svg\" alt=\"Kruit & Kramer\" style=\"max-width: 150px; margin-top: 10px;\"><br><strong>Kruit & Kramer</strong><br>Email: change@kruitkramer.nl<br>Tel: 777 / ICT afdeling intern</p>','comment',1,1,'2025-10-22 01:11:26','2025-10-22 01:11:26');
/*!40000 ALTER TABLE `ticket_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tickets`
--

DROP TABLE IF EXISTS `tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tickets` (
  `ticket_id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_number` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `assigned_agent_id` int(11) DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `ticket_type` enum('incident','service_request','change_request','feature_request') NOT NULL DEFAULT 'incident',
  `status` enum('open','in_progress','pending','resolved','closed') DEFAULT 'open',
  `source` enum('web','email','phone') DEFAULT 'web',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolution` text DEFAULT NULL,
  `satisfaction_rating` int(11) DEFAULT NULL CHECK (`satisfaction_rating` between 1 and 5),
  `satisfaction_comment` text DEFAULT NULL,
  PRIMARY KEY (`ticket_id`),
  UNIQUE KEY `ticket_number` (`ticket_number`),
  KEY `idx_ticket_number` (`ticket_number`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_assigned_agent` (`assigned_agent_id`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_status_priority` (`status`,`priority`),
  KEY `idx_assigned_status` (`assigned_agent_id`,`status`),
  KEY `idx_satisfaction_rating` (`satisfaction_rating`),
  CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `tickets_ibfk_2` FOREIGN KEY (`assigned_agent_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `tickets_ibfk_3` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tickets`
--

LOCK TABLES `tickets` WRITE;
/*!40000 ALTER TABLE `tickets` DISABLE KEYS */;
INSERT INTO `tickets` VALUES (1,'KK-2025-0001',2,1,4,'Test ticket','Test','high','incident','closed','web','2025-10-21 12:14:50','2025-10-21 22:51:38','2025-10-21 22:51:38','Test closure',5,NULL);
/*!40000 ALTER TABLE `tickets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `role` enum('user','agent','admin') DEFAULT 'user',
  `department_id` int(11) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_department_id` (`department_id`),
  KEY `idx_location` (`location`),
  CONSTRAINT `fk_user_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin@kruit-en-kramer.nl','$2y$12$qv/kCt.0Ycm.N6avYDcAj.8aR6fLsUd4ocolsdT6aKXJ5KOMvTRUm','System','Administrator','ICT','admin',NULL,'Kruit en Kramer','2025-10-21 11:58:23','2025-10-22 01:18:11',1),(2,'dckessler95@gmail.com','$2y$12$ejFN43u9IARGbzzOJY8NGe8uuRfTt7eaVms6SGfTyevHYZXn/4AfS','Damian','Kessler','Test','user',7,'Kruit en Kramer','2025-10-21 12:14:26','2025-10-21 23:56:21',1),(5,'testpronto@kruit-en-kramer.nl','$2y$12$pMoca7L1MxF6dUMoYcBtBOGsVEs8W7E1lvnIUF3FsopCKi06Ee/Wy','Test','Pronto',NULL,'user',2,'Pronto','2025-10-22 00:47:20','2025-10-22 01:18:04',1);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `v_active_cis`
--

DROP TABLE IF EXISTS `v_active_cis`;
/*!50001 DROP VIEW IF EXISTS `v_active_cis`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_active_cis` AS SELECT
 1 AS `ci_id`,
  1 AS `ci_number`,
  1 AS `type`,
  1 AS `category`,
  1 AS `name`,
  1 AS `brand`,
  1 AS `model`,
  1 AS `serial_number`,
  1 AS `status`,
  1 AS `department`,
  1 AS `location`,
  1 AS `purchase_date`,
  1 AS `purchase_price`,
  1 AS `warranty_expiry`,
  1 AS `owner_name`,
  1 AS `owner_email`,
  1 AS `created_at` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_change_statistics`
--

DROP TABLE IF EXISTS `v_change_statistics`;
/*!50001 DROP VIEW IF EXISTS `v_change_statistics`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_change_statistics` AS SELECT
 1 AS `status`,
  1 AS `type`,
  1 AS `priority`,
  1 AS `total_changes`,
  1 AS `avg_days_to_implement` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_changes_overview`
--

DROP TABLE IF EXISTS `v_changes_overview`;
/*!50001 DROP VIEW IF EXISTS `v_changes_overview`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_changes_overview` AS SELECT
 1 AS `change_id`,
  1 AS `change_number`,
  1 AS `title`,
  1 AS `type`,
  1 AS `priority`,
  1 AS `impact`,
  1 AS `status`,
  1 AS `requester_name`,
  1 AS `assignee_name`,
  1 AS `planned_start_date`,
  1 AS `planned_end_date`,
  1 AS `created_at`,
  1 AS `updated_at` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_ci_financial_summary`
--

DROP TABLE IF EXISTS `v_ci_financial_summary`;
/*!50001 DROP VIEW IF EXISTS `v_ci_financial_summary`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_ci_financial_summary` AS SELECT
 1 AS `type`,
  1 AS `status`,
  1 AS `total_items`,
  1 AS `total_value`,
  1 AS `avg_value`,
  1 AS `oldest_purchase`,
  1 AS `newest_purchase` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_expiring_warranties`
--

DROP TABLE IF EXISTS `v_expiring_warranties`;
/*!50001 DROP VIEW IF EXISTS `v_expiring_warranties`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_expiring_warranties` AS SELECT
 1 AS `ci_id`,
  1 AS `ci_number`,
  1 AS `name`,
  1 AS `type`,
  1 AS `brand`,
  1 AS `model`,
  1 AS `warranty_expiry`,
  1 AS `days_until_expiry`,
  1 AS `owner_name` */;
SET character_set_client = @saved_cs_client;

--
-- Final view structure for view `v_active_cis`
--

/*!50001 DROP VIEW IF EXISTS `v_active_cis`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = cp850 */;
/*!50001 SET character_set_results     = cp850 */;
/*!50001 SET collation_connection      = cp850_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_active_cis` AS select `ci`.`ci_id` AS `ci_id`,`ci`.`ci_number` AS `ci_number`,`ci`.`type` AS `type`,`ci`.`category` AS `category`,`ci`.`name` AS `name`,`ci`.`brand` AS `brand`,`ci`.`model` AS `model`,`ci`.`serial_number` AS `serial_number`,`ci`.`status` AS `status`,`ci`.`department` AS `department`,`ci`.`location` AS `location`,`ci`.`purchase_date` AS `purchase_date`,`ci`.`purchase_price` AS `purchase_price`,`ci`.`warranty_expiry` AS `warranty_expiry`,concat(`u`.`first_name`,' ',`u`.`last_name`) AS `owner_name`,`u`.`email` AS `owner_email`,`ci`.`created_at` AS `created_at` from (`configuration_items` `ci` left join `users` `u` on(`ci`.`owner_id` = `u`.`user_id`)) where `ci`.`status` <> 'Afgeschreven' */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_change_statistics`
--

/*!50001 DROP VIEW IF EXISTS `v_change_statistics`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = cp850 */;
/*!50001 SET character_set_results     = cp850 */;
/*!50001 SET collation_connection      = cp850_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_change_statistics` AS select `changes`.`status` AS `status`,`changes`.`type` AS `type`,`changes`.`priority` AS `priority`,count(0) AS `total_changes`,avg(timestampdiff(DAY,`changes`.`created_at`,case when `changes`.`status` = 'Ge??mplementeerd' then `changes`.`actual_end_date` else NULL end)) AS `avg_days_to_implement` from `changes` group by `changes`.`status`,`changes`.`type`,`changes`.`priority` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_changes_overview`
--

/*!50001 DROP VIEW IF EXISTS `v_changes_overview`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = cp850 */;
/*!50001 SET character_set_results     = cp850 */;
/*!50001 SET collation_connection      = cp850_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_changes_overview` AS select `c`.`change_id` AS `change_id`,`c`.`change_number` AS `change_number`,`c`.`title` AS `title`,`c`.`type` AS `type`,`c`.`priority` AS `priority`,`c`.`impact` AS `impact`,`c`.`status` AS `status`,concat(`req`.`first_name`,' ',`req`.`last_name`) AS `requester_name`,concat(`asn`.`first_name`,' ',`asn`.`last_name`) AS `assignee_name`,`c`.`planned_start_date` AS `planned_start_date`,`c`.`planned_end_date` AS `planned_end_date`,`c`.`created_at` AS `created_at`,`c`.`updated_at` AS `updated_at` from ((`changes` `c` left join `users` `req` on(`c`.`requested_by` = `req`.`user_id`)) left join `users` `asn` on(`c`.`assigned_to` = `asn`.`user_id`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_ci_financial_summary`
--

/*!50001 DROP VIEW IF EXISTS `v_ci_financial_summary`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = cp850 */;
/*!50001 SET character_set_results     = cp850 */;
/*!50001 SET collation_connection      = cp850_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_ci_financial_summary` AS select `configuration_items`.`type` AS `type`,`configuration_items`.`status` AS `status`,count(0) AS `total_items`,sum(`configuration_items`.`purchase_price`) AS `total_value`,avg(`configuration_items`.`purchase_price`) AS `avg_value`,min(`configuration_items`.`purchase_date`) AS `oldest_purchase`,max(`configuration_items`.`purchase_date`) AS `newest_purchase` from `configuration_items` where `configuration_items`.`purchase_price` is not null group by `configuration_items`.`type`,`configuration_items`.`status` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_expiring_warranties`
--

/*!50001 DROP VIEW IF EXISTS `v_expiring_warranties`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = cp850 */;
/*!50001 SET character_set_results     = cp850 */;
/*!50001 SET collation_connection      = cp850_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_expiring_warranties` AS select `ci`.`ci_id` AS `ci_id`,`ci`.`ci_number` AS `ci_number`,`ci`.`name` AS `name`,`ci`.`type` AS `type`,`ci`.`brand` AS `brand`,`ci`.`model` AS `model`,`ci`.`warranty_expiry` AS `warranty_expiry`,to_days(`ci`.`warranty_expiry`) - to_days(curdate()) AS `days_until_expiry`,concat(`u`.`first_name`,' ',`u`.`last_name`) AS `owner_name` from (`configuration_items` `ci` left join `users` `u` on(`ci`.`owner_id` = `u`.`user_id`)) where `ci`.`warranty_expiry` is not null and `ci`.`warranty_expiry` >= curdate() and `ci`.`warranty_expiry` <= curdate() + interval 90 day and `ci`.`status` = 'In gebruik' order by `ci`.`warranty_expiry` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-10-22  3:29:36
