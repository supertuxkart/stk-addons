-- phpMyAdmin SQL Dump
-- version 3.3.3
-- http://www.phpmyadmin.net
--
-- Serveur: localhost
-- Généré le : Jeu 06 Janvier 2011 à 17:35
-- Version du serveur: 5.1.41
-- Version de PHP: 5.3.1

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

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
  `Description` text NOT NULL,
  `file` text NOT NULL,
  `image` tinytext NOT NULL,
  `icon` tinytext NOT NULL,
  `date` date NOT NULL,
  `available` int(11) NOT NULL,
  `version` int(11) NOT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `STKVersion` tinytext NOT NULL,
  `Author` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=51 ;


-- --------------------------------------------------------

--
-- Structure de la table `properties`
--

CREATE TABLE IF NOT EXISTS `properties` (
  `type` text NOT NULL,
  `lock` int(11) NOT NULL,
  `typefield` text NOT NULL,
  `default` text NOT NULL,
  `name` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Contenu de la table `properties`
--

INSERT INTO `properties` (`type`, `lock`, `typefield`, `default`, `name`) VALUES
('karts', 1, 'text', '', 'name'),
('karts', 0, 'textarea', '', 'Description'),
('karts', 1, 'text', '1', 'version'),
('karts', 0, 'file', '', 'File'),
('karts', 0, 'file', '', 'Icon'),
('karts', 0, 'file', '', 'Image'),
('karts', 0, 'enum', '0.7\r\n0.6', 'STK Version'),
('tracks', 1, 'text', '', 'name'),
('tracks', 0, 'textarea', '', 'Description'),
('tracks', 0, 'text', '1', 'version'),
('tracks', 0, 'file', '', 'File'),
('tracks', 0, 'text', '', 'Author'),
('tracks', 0, 'file', '', 'Image'),
('tracks', 0, 'enum', '0.7\r\n0.6', 'STK Version'),
('users', 0, 'text', '', 'homepage'),
('users', 0, 'file', '', 'avatar'),
('karts', 0, 'text', '', 'Author');

-- --------------------------------------------------------

--
-- Structure de la table `tracks`
--

CREATE TABLE IF NOT EXISTS `tracks` (
  `user` int(11) NOT NULL,
  `name` tinytext NOT NULL,
  `Description` text NOT NULL,
  `file` text NOT NULL,
  `icon` tinytext NOT NULL,
  `date` date NOT NULL,
  `available` int(11) NOT NULL,
  `version` int(11) NOT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `image` text NOT NULL,
  `STKVersion` text NOT NULL,
  `Author` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=29 ;

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
  `avatar` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=13 ;

--
-- Contenu de la table `users`
--

INSERT INTO `users` (`login`, `pass`, `id`, `range`, `mail`, `available`, `verify`, `date`, `homepage`, `avatar`) VALUES
('admin', '5f4dcc3b5aa765d61d8327deb882cf99', 12, 'administrator', 'a@a.com', 1, '', '2011-01-06', '', '');

