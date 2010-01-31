-- phpMyAdmin SQL Dump
-- version 3.2.2.1deb1
-- http://www.phpmyadmin.net
--
-- Serveur: localhost
-- Généré le : Dim 31 Janvier 2010 à 12:27
-- Version du serveur: 5.1.37
-- Version de PHP: 5.2.10-2ubuntu6.4

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Base de données: `stkbase`
--

-- --------------------------------------------------------

--
-- Structure de la table `help`
--

CREATE TABLE IF NOT EXISTS `help` (
  `user` int(11) NOT NULL,
  `name` tinytext NOT NULL,
  `description` mediumtext NOT NULL,
  `file` text NOT NULL,
  `image` tinytext NOT NULL,
  `icon` tinytext NOT NULL,
  `date` date NOT NULL,
  `available` int(11) NOT NULL,
  `version` int(11) NOT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `versionStk` tinytext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=27 ;

--
-- Contenu de la table `help`
--


-- --------------------------------------------------------

--
-- Structure de la table `history`
--

CREATE TABLE IF NOT EXISTS `history` (
  `date` date NOT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` tinytext NOT NULL,
  `action` tinytext NOT NULL,
  `option` tinytext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Contenu de la table `history`
--


-- --------------------------------------------------------

--
-- Structure de la table `karts`
--

CREATE TABLE IF NOT EXISTS `karts` (
  `user` int(11) NOT NULL,
  `name` tinytext NOT NULL,
  `description` text NOT NULL,
  `file` text NOT NULL,
  `image` tinytext NOT NULL,
  `icon` tinytext NOT NULL,
  `date` date NOT NULL,
  `available` int(11) NOT NULL,
  `version` int(11) NOT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `versionStk` tinytext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=27 ;

--
-- Contenu de la table `karts`
--


-- --------------------------------------------------------

--
-- Structure de la table `tracks`
--

CREATE TABLE IF NOT EXISTS `tracks` (
  `user` int(11) NOT NULL,
  `name` tinytext NOT NULL,
  `description` text NOT NULL,
  `file` text NOT NULL,
  `icon` tinytext NOT NULL,
  `date` date NOT NULL,
  `available` int(11) NOT NULL,
  `version` int(11) NOT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `image` text NOT NULL,
  `versionStk` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=9 ;

--
-- Contenu de la table `tracks`
--


-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `login` tinytext NOT NULL,
  `pass` tinytext NOT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `range` tinytext NOT NULL,
  `mail` tinytext NOT NULL,
  `available` int(11) NOT NULL,
  `verify` text NOT NULL,
  `date` date NOT NULL,
  `homepage` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=12 ;

--
-- Contenu de la table `users`
--

INSERT INTO `users` (`login`, `pass`, `id`, `range`, `mail`, `available`, `verify`, `date`, `homepage`) VALUES
('pass', '1a1dc91c907325c69271ddf0c944bc72', 11, 'administrator', 'a@a.fr', 0, 'ynmdkgrsbady', '2010-01-26', '');

