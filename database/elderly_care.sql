-- MySQL dump 10.11
--
-- Host: localhost    Database: elderly_care_db
-- ------------------------------------------------------
-- Server version	5.0.41-community-nt

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `appointments`
--

DROP TABLE IF EXISTS `appointments`;
CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL auto_increment,
  `resident_id` int(11) NOT NULL,
  `doctor_name` varchar(100) NOT NULL,
  `appointment_datetime` datetime NOT NULL,
  `purpose` text NOT NULL,
  PRIMARY KEY  (`appointment_id`),
  KEY `resident_id` (`resident_id`),
  CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`resident_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `appointments`
--

LOCK TABLES `appointments` WRITE;
/*!40000 ALTER TABLE `appointments` DISABLE KEYS */;
/*!40000 ALTER TABLE `appointments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `caregivers`
--

DROP TABLE IF EXISTS `caregivers`;
CREATE TABLE `caregivers` (
  `caregiver_id` int(11) NOT NULL auto_increment,
  `user_id` int(11) default NULL,
  `name` varchar(100) NOT NULL,
  `contact` varchar(20) NOT NULL,
  `shift` enum('Day Shift','Night Shift') NOT NULL default 'Day Shift',
  PRIMARY KEY  (`caregiver_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `caregivers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `caregivers`
--

LOCK TABLES `caregivers` WRITE;
/*!40000 ALTER TABLE `caregivers` DISABLE KEYS */;
INSERT INTO `caregivers` VALUES (1,2,'Caregiver Perera','0771234567','Day Shift');
/*!40000 ALTER TABLE `caregivers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `residents`
--

DROP TABLE IF EXISTS `residents`;
CREATE TABLE `residents` (
  `resident_id` int(11) NOT NULL auto_increment,
  `name` varchar(100) NOT NULL,
  `age` int(11) NOT NULL,
  `medical_condition` text NOT NULL,
  `assigned_caregiver_id` int(11) default NULL,
  PRIMARY KEY  (`resident_id`),
  KEY `assigned_caregiver_id` (`assigned_caregiver_id`),
  CONSTRAINT `residents_ibfk_1` FOREIGN KEY (`assigned_caregiver_id`) REFERENCES `caregivers` (`caregiver_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `residents`
--

LOCK TABLES `residents` WRITE;
/*!40000 ALTER TABLE `residents` DISABLE KEYS */;
INSERT INTO `residents` VALUES (1,'A. Silva',74,'Hypertension',1);
/*!40000 ALTER TABLE `residents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `task_schedules`
--

DROP TABLE IF EXISTS `task_schedules`;
CREATE TABLE `task_schedules` (
  `schedule_id` int(11) NOT NULL auto_increment,
  `resident_id` int(11) NOT NULL,
  `caregiver_id` int(11) NOT NULL,
  `task_details` text NOT NULL,
  `scheduled_time` time NOT NULL,
  `status` enum('Pending','Completed') default 'Pending',
  PRIMARY KEY  (`schedule_id`),
  KEY `resident_id` (`resident_id`),
  KEY `caregiver_id` (`caregiver_id`),
  CONSTRAINT `task_schedules_ibfk_1` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`resident_id`) ON DELETE CASCADE,
  CONSTRAINT `task_schedules_ibfk_2` FOREIGN KEY (`caregiver_id`) REFERENCES `caregivers` (`caregiver_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `task_schedules`
--

LOCK TABLES `task_schedules` WRITE;
/*!40000 ALTER TABLE `task_schedules` DISABLE KEYS */;
/*!40000 ALTER TABLE `task_schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL auto_increment,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','caregiver','family') NOT NULL,
  `full_name` varchar(100) NOT NULL,
  PRIMARY KEY  (`user_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'uoc','uoc','admin','System Administrator'),(2,'caregiver1','uoc','caregiver','Caregiver Perera'),(3,'family1','uoc','family','S. Silva');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `visit_requests`
--

DROP TABLE IF EXISTS `visit_requests`;
CREATE TABLE `visit_requests` (
  `request_id` int(11) NOT NULL auto_increment,
  `resident_id` int(11) NOT NULL,
  `relative_name` varchar(100) NOT NULL,
  `request_details` text NOT NULL,
  `status` enum('Pending','Approved','Declined') default 'Pending',
  PRIMARY KEY  (`request_id`),
  KEY `resident_id` (`resident_id`),
  CONSTRAINT `visit_requests_ibfk_1` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`resident_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `visit_requests`
--

LOCK TABLES `visit_requests` WRITE;
/*!40000 ALTER TABLE `visit_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `visit_requests` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-04  9:26:13
