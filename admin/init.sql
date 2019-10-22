-- phpMyAdmin SQL Dump
-- version 2.11.9.2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 30. Dezember 2010 um 12:26
-- Server Version: 5.0.67
-- PHP-Version: 5.2.6

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Datenbank: `aplan`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `aplan_arbeitstage`
--

CREATE TABLE IF NOT EXISTS `aplan_arbeitstage` (
  `id` bigint(20) NOT NULL auto_increment,
  `user_id` int(11) NOT NULL,
  `dateofday` datetime NOT NULL,
  `holliday_id` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=48 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `aplan_daydescriptions`
--

CREATE TABLE IF NOT EXISTS `aplan_daydescriptions` (
  `id` int(11) NOT NULL auto_increment,
  `user_id` int(11) NOT NULL,
  `workday` date NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=35 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `aplan_holliday`
--

CREATE TABLE IF NOT EXISTS `aplan_holliday` (
  `id` int(11) NOT NULL auto_increment,
  `beschreibung` varchar(50) NOT NULL,
  `typ` smallint(6) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=6 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `aplan_kilometers`
--

CREATE TABLE IF NOT EXISTS `aplan_kilometers` (
  `id` int(11) NOT NULL auto_increment,
  `user_id` int(11) NOT NULL,
  `day` datetime NOT NULL,
  `km` int(11) NOT NULL,
  `fromwhere` varchar(200) NOT NULL,
  `towhere` varchar(200) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=14 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `aplan_timefromto`
--

CREATE TABLE IF NOT EXISTS `aplan_timefromto` (
  `id` int(11) NOT NULL auto_increment,
  `user_id` int(11) NOT NULL,
  `timefrom` time NOT NULL,
  `timeto` time NOT NULL,
  `dateofday` date NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=39 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `aplan_users`
--

CREATE TABLE IF NOT EXISTS `aplan_users` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `uname` varchar(50) NOT NULL,
  `email` varchar(200) NOT NULL,
  `reg_date` datetime NOT NULL,
  `session_id` varchar(250) NOT NULL,
  `password` varchar(50) NOT NULL,
  `status` int(11) NOT NULL,
  `alteueberstunden` int(50) NOT NULL,
  `feiertage` int(50) NOT NULL,
  `urlaubstage` int(50) NOT NULL,
  `kmsatz` float NOT NULL,
  `startdate` datetime NOT NULL,
  `dname` varchar(50) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=9 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `aplan_workday`
--

CREATE TABLE IF NOT EXISTS `aplan_workday` (
  `id` int(11) NOT NULL auto_increment,
  `user_id` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `workfield_id` int(11) NOT NULL,
  `hours` time NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=989 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `aplan_workfields`
--

CREATE TABLE IF NOT EXISTS `aplan_workfields` (
  `id` int(11) NOT NULL auto_increment,
  `rank` int(11) NOT NULL,
  `explanation` varchar(250) NOT NULL,
  `description` varchar(25) NOT NULL,
  `user` int(11) NOT NULL,
  `timecapital` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=71 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `aplan_workhours`
--

CREATE TABLE IF NOT EXISTS `aplan_workhours` (
  `id` int(11) NOT NULL auto_increment,
  `user` int(11) NOT NULL,
  `hours` float NOT NULL,
  `workday` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=6 ;

