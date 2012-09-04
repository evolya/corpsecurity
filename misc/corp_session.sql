-- phpMyAdmin SQL Dump
-- version 3.4.5
-- http://www.phpmyadmin.net
--
-- Client: localhost
-- Généré le : Sam 28 Juillet 2012 à 02:54
-- Version du serveur: 5.5.16
-- Version de PHP: 5.3.8

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Structure de la table `corp_session`
--

CREATE TABLE IF NOT EXISTS `corp_session` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sid` varchar(128) NOT NULL COMMENT 'Session unique ID',
  `type` varchar(50) NOT NULL COMMENT 'Class name',
  `api` varchar(50) NOT NULL COMMENT 'SAPI name',
  `agent` text NOT NULL COMMENT 'Corp_Agent',
  `qop` text NOT NULL COMMENT 'Corp_Request_QoP',
  `identity` text NOT NULL COMMENT 'Corp_Auth_Identity',
  `creation_time` int(11) NOT NULL COMMENT 'Timestamp (sec)',
  `last_request_time` int(11) NOT NULL COMMENT 'Timestamp (sec)',
  `expiration_delay` int(11) NOT NULL COMMENT 'Delay (sec)',
  `user_data` longtext NOT NULL COMMENT 'mixed[]',
  `session_data` longtext NOT NULL COMMENT 'mixed[]',
  PRIMARY KEY (`id`),
  UNIQUE KEY `sid` (`sid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
