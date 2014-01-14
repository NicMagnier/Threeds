-- phpMyAdmin SQL Dump
-- version OVH
-- http://www.phpmyadmin.net
--
-- Host: mysql5-2.90
-- Generation Time: Jan 14, 2014 at 09:24 AM
-- Server version: 5.1.66
-- PHP Version: 5.3.8

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `mxjoenvi_mx`
--

-- --------------------------------------------------------

--
-- Table structure for table `threeds_authentication`
--

CREATE TABLE IF NOT EXISTS `threeds_authentication` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `login` varchar(32) DEFAULT NULL,
  `hashpass` varchar(32) DEFAULT NULL,
  `sessionID` varchar(32) DEFAULT NULL,
  `attemptCounter` tinyint(4) NOT NULL,
  `attemptLast` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `twitterAccessToken` varchar(64) NOT NULL,
  `twitterAccessSecret` varchar(64) NOT NULL,
  `twitterUserID` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `login` (`login`,`sessionID`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1479 ;

-- --------------------------------------------------------

--
-- Table structure for table `threeds_image`
--

CREATE TABLE IF NOT EXISTS `threeds_image` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gid` varchar(6) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `authID` int(11) NOT NULL,
  `description` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `parallax` float NOT NULL,
  `dateCreated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `gid` (`gid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3521 ;

-- --------------------------------------------------------

--
-- Table structure for table `threeds_passwordrecovery`
--

CREATE TABLE IF NOT EXISTS `threeds_passwordrecovery` (
  `authID` int(10) unsigned NOT NULL,
  `magichash` varchar(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  PRIMARY KEY (`authID`),
  UNIQUE KEY `magichash` (`magichash`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='New entry when user ask to reset his password';

-- --------------------------------------------------------

--
-- Table structure for table `threeds_user`
--

CREATE TABLE IF NOT EXISTS `threeds_user` (
  `authID` int(11) NOT NULL,
  `gid` varchar(5) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `name` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `profile` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `twitterName` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`authID`),
  UNIQUE KEY `gid` (`gid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
