-- phpMyAdmin SQL Dump
-- version 3.2.4
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 04. November 2010 um 21:19
-- Server Version: 5.1.41
-- PHP-Version: 5.3.1

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Datenbank: `a3o`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `a3o_alliancenations`
--

CREATE TABLE IF NOT EXISTS `a3o_alliancenations` (
  `alliancenation_alliance` int(10) unsigned NOT NULL COMMENT 'FK a3o_alliances',
  `alliancenation_nation` int(10) unsigned NOT NULL COMMENT 'FK a3o_nations',
  PRIMARY KEY (`alliancenation_alliance`,`alliancenation_nation`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- RELATIONEN DER TABELLE `a3o_alliancenations`:
--   `alliancenation_alliance`
--       `a3o_alliances` -> `alliance_id`
--   `alliancenation_nation`
--       `a3o_nations` -> `nation_id`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `a3o_alliances`
--

CREATE TABLE IF NOT EXISTS `a3o_alliances` (
  `alliance_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Surrogate Primary Key',
  `alliance_name` varchar(50) NOT NULL,
  `alliance_game` int(10) unsigned NOT NULL COMMENT 'FK a3o_games',
  PRIMARY KEY (`alliance_id`),
  UNIQUE KEY `NATURAL` (`alliance_name`,`alliance_game`),
  KEY `alliance_game` (`alliance_game`),
  KEY `alliance_name` (`alliance_name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

--
-- RELATIONEN DER TABELLE `a3o_alliances`:
--   `alliance_game`
--       `a3o_games` -> `game_id`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `a3o_basezoneoptions`
--

CREATE TABLE IF NOT EXISTS `a3o_basezoneoptions` (
  `basezoneoption_basezone` int(10) unsigned NOT NULL COMMENT 'FK a3o_basezones',
  `basezoneoption_name` varchar(50) NOT NULL,
  `basezoneoption_value` varchar(50) NOT NULL,
  PRIMARY KEY (`basezoneoption_basezone`,`basezoneoption_name`),
  KEY `basezoneoption_basezone` (`basezoneoption_basezone`),
  KEY `basezoneoption_name` (`basezoneoption_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- RELATIONEN DER TABELLE `a3o_basezoneoptions`:
--   `basezoneoption_basezone`
--       `a3o_basezones` -> `basezone_id`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `a3o_basezones`
--

CREATE TABLE IF NOT EXISTS `a3o_basezones` (
  `basezone_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Surrogate Primary Key',
  `basezone_game` int(10) unsigned NOT NULL COMMENT 'Foreign Key to a3o_games',
  `basezone_name` varchar(50) NOT NULL,
  `basezone_owner` int(10) unsigned NOT NULL,
  PRIMARY KEY (`basezone_id`),
  UNIQUE KEY `NATURAL` (`basezone_game`,`basezone_name`),
  KEY `basezone_owner` (`basezone_owner`),
  KEY `basezone_game` (`basezone_game`),
  KEY `basezone_name` (`basezone_name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='Base zones for each game' AUTO_INCREMENT=5 ;

--
-- RELATIONEN DER TABELLE `a3o_basezones`:
--   `basezone_game`
--       `a3o_games` -> `game_id`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `a3o_connections`
--

CREATE TABLE IF NOT EXISTS `a3o_connections` (
  `connection_firstzone` int(10) unsigned NOT NULL,
  `connection_secondzone` int(10) unsigned NOT NULL,
  PRIMARY KEY (`connection_firstzone`,`connection_secondzone`),
  KEY `connection_firstzone` (`connection_firstzone`),
  KEY `connection_secondzone` (`connection_secondzone`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='connections between zones';

--
-- RELATIONEN DER TABELLE `a3o_connections`:
--   `connection_firstzone`
--       `a3o_basezones` -> `basezone_id`
--   `connection_secondzone`
--       `a3o_basezones` -> `basezone_id`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `a3o_games`
--

CREATE TABLE IF NOT EXISTS `a3o_games` (
  `game_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Surrogate Primary Key',
  `game_name` varchar(50) NOT NULL,
  `game_active` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'can new games be started?',
  PRIMARY KEY (`game_id`),
  KEY `game_active` (`game_active`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `a3o_matches`
--

CREATE TABLE IF NOT EXISTS `a3o_matches` (
  `match_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Surrogate Primary Key',
  `match_turn` smallint(5) unsigned NOT NULL COMMENT 'Turn counter',
  `match_activenation` int(10) unsigned NOT NULL COMMENT 'Foreign Key a3o_nations',
  `match_game` int(10) unsigned NOT NULL COMMENT 'Foreign Key a3o_games',
  `match_turncache` text NOT NULL,
  PRIMARY KEY (`match_id`),
  KEY `match_game` (`match_game`),
  KEY `match_activenation` (`match_activenation`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- RELATIONEN DER TABELLE `a3o_matches`:
--   `match_game`
--       `a3o_games` -> `game_id`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `a3o_nations`
--

CREATE TABLE IF NOT EXISTS `a3o_nations` (
  `nation_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Surrogate Primary Key',
  `nation_name` varchar(50) NOT NULL,
  `nation_game` int(10) unsigned NOT NULL COMMENT 'FK a3o_games',
  PRIMARY KEY (`nation_id`),
  UNIQUE KEY `NATURAL` (`nation_name`,`nation_game`),
  KEY `nation_game` (`nation_game`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=7 ;

--
-- RELATIONEN DER TABELLE `a3o_nations`:
--   `nation_game`
--       `a3o_games` -> `game_id`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `a3o_pieces`
--

CREATE TABLE IF NOT EXISTS `a3o_pieces` (
  `pieces_zone` int(10) unsigned NOT NULL COMMENT 'FK a3o_zones',
  `pieces_nation` int(10) unsigned NOT NULL COMMENT 'FK a3o_nations',
  `pieces_type` int(10) unsigned NOT NULL COMMENT 'FK a3o_types',
  `pieces_count` mediumint(8) unsigned NOT NULL COMMENT 'number of pieces',
  PRIMARY KEY (`pieces_zone`,`pieces_nation`,`pieces_type`),
  KEY `pieces_type` (`pieces_type`),
  KEY `pieces_nation` (`pieces_nation`),
  KEY `pieces_zone` (`pieces_zone`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- RELATIONEN DER TABELLE `a3o_pieces`:
--   `pieces_nation`
--       `a3o_nations` -> `nation_id`
--   `pieces_type`
--       `a3o_types` -> `type_id`
--   `pieces_zone`
--       `a3o_zones` -> `zone_id`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `a3o_playeroptions`
--

CREATE TABLE IF NOT EXISTS `a3o_playeroptions` (
  `playeroption_player` int(10) unsigned NOT NULL,
  `playeroption_name` varchar(50) NOT NULL,
  `playeroption_value` varchar(50) NOT NULL,
  PRIMARY KEY (`playeroption_player`,`playeroption_name`),
  KEY `playeroption_name` (`playeroption_name`),
  KEY `playeroption_player` (`playeroption_player`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- RELATIONEN DER TABELLE `a3o_playeroptions`:
--   `playeroption_player`
--       `a3o_players` -> `player_id`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `a3o_players`
--

CREATE TABLE IF NOT EXISTS `a3o_players` (
  `player_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `player_user` int(10) unsigned NOT NULL COMMENT 'FK a3o_users',
  `player_nation` int(10) unsigned NOT NULL COMMENT 'FK a3o_nations',
  `player_match` int(10) unsigned NOT NULL COMMENT 'FK a3o_matches',
  PRIMARY KEY (`player_id`),
  UNIQUE KEY `NATURAL` (`player_nation`,`player_match`),
  KEY `player_user` (`player_user`),
  KEY `player_nation` (`player_nation`),
  KEY `player_match` (`player_match`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- RELATIONEN DER TABELLE `a3o_players`:
--   `player_match`
--       `a3o_matches` -> `match_id`
--   `player_nation`
--       `a3o_nations` -> `nation_id`
--   `player_user`
--       `a3o_users` -> `user_id`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `a3o_typeoptions`
--

CREATE TABLE IF NOT EXISTS `a3o_typeoptions` (
  `typeoption_type` int(10) unsigned NOT NULL COMMENT 'FK a3o_types',
  `typeoption_name` varchar(50) NOT NULL COMMENT 'name of option',
  `typeoption_value` varchar(50) NOT NULL COMMENT 'value of option',
  PRIMARY KEY (`typeoption_type`,`typeoption_name`),
  KEY `typeoption_type` (`typeoption_type`),
  KEY `typeoption_name` (`typeoption_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- RELATIONEN DER TABELLE `a3o_typeoptions`:
--   `typeoption_type`
--       `a3o_types` -> `type_id`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `a3o_types`
--

CREATE TABLE IF NOT EXISTS `a3o_types` (
  `type_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Surrogate Primary Key',
  `type_name` varchar(50) NOT NULL,
  `type_game` int(10) unsigned NOT NULL COMMENT 'FK a3o_games',
  PRIMARY KEY (`type_id`),
  UNIQUE KEY `NATURAL` (`type_name`,`type_game`),
  KEY `type_game` (`type_game`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;

--
-- RELATIONEN DER TABELLE `a3o_types`:
--   `type_game`
--       `a3o_games` -> `game_id`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `a3o_users`
--

CREATE TABLE IF NOT EXISTS `a3o_users` (
  `user_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Surrogate Primary Key',
  PRIMARY KEY (`user_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=6 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `a3o_zones`
--

CREATE TABLE IF NOT EXISTS `a3o_zones` (
  `zone_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Surrogate Primary Key',
  `zone_basezone` int(10) unsigned NOT NULL COMMENT 'Foreign Key a3o_basezones',
  `zone_match` int(10) unsigned NOT NULL COMMENT 'Foreign Key a3o_matches',
  `zone_owner` int(10) unsigned NOT NULL,
  PRIMARY KEY (`zone_id`),
  UNIQUE KEY `NATURAL` (`zone_basezone`,`zone_match`),
  KEY `zone_owner` (`zone_owner`),
  KEY `zone_basezone` (`zone_basezone`),
  KEY `zone_match` (`zone_match`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='Current zones in a match' AUTO_INCREMENT=5 ;

--
-- RELATIONEN DER TABELLE `a3o_zones`:
--   `zone_basezone`
--       `a3o_basezones` -> `basezone_id`
--   `zone_match`
--       `a3o_matches` -> `match_id`
--
