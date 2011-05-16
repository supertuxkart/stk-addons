SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

CREATE TABLE `clients` (
    `id` int(11) NOT NULL auto_increment PRIMARY KEY,
    `agent_string` varchar(255) NOT NULL,
    `stk_version` varchar(64) NOT NULL default 'latest',
    `disabled` int(1) NOT NULL default 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `config` (
    `name` varchar(256) NOT NULL UNIQUE,
    `value` varchar(512) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO `config`
(`name`,`value`)
VALUES
('xml_frequency','172800'),
('allowed_addon_exts','txt,b3d,xml,png,jpg,jpeg,music,ogg'),
('allowed_source_exts','txt,blend,png,jpg,jpeg,xcf,psd,wav,ogg,flac,xml'),
('admin_email','webmaster@localhost');

CREATE TABLE `files` (
    `id` int(11) NOT NULL auto_increment,
    `addon_id` varchar(30) NOT NULL,
    `addon_type` ENUM('karts','tracks'),
    `file_type` ENUM('source','image','addon'),
    `file_path` text NOT NULL,
    `approved` int(1) NOT NULL default 0,
    `downloads` int UNSIGNED NOT NULL default 0,
    PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `help` (
  `user` int(11) NOT NULL,
  `name` tinytext NOT NULL,
  `description` mediumtext NOT NULL,
  `file` text NOT NULL,
  `image` tinytext NOT NULL,
  `icon` tinytext NOT NULL,
  `date` date NOT NULL,
  `available` int(11) NOT NULL,
  `version` int(11) NOT NULL,
  `id` int(11) NOT NULL auto_increment,
  `versionStk` tinytext NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


CREATE TABLE `history` (
  `date` date NOT NULL,
  `id` int(11) NOT NULL auto_increment,
  `user` tinytext NOT NULL,
  `action` tinytext NOT NULL,
  `option` tinytext NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


CREATE TABLE `karts` (
  `id` varchar(30) NOT NULL,
  `name` tinytext NOT NULL,
  `description` varchar(140),
  `uploader` int(11) NOT NULL,
  `creation_date` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `designer` tinytext NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `karts_revs` (
    `id` char(23) NOT NULL,
    `addon_id` varchar(30) NOT NULL,
    `creation_date` timestamp NOT NULL default CURRENT_TIMESTAMP,
    `revision` tinyint(4) NOT NULL default '1',
    `format` tinyint(4) NOT NULL,
    `icon` int UNSIGNED NOT NULL default '0',
    `image` int UNSIGNED NOT NULL default '0',
    `status` mediumint(9) unsigned NOT NULL default '0',
    `moderator_note` varchar(255) NULL default NULL,
    UNIQUE KEY `id` (`id`),
    KEY `track_id` (`addon_id`),
    KEY `status` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `news` (
    `id` int(10) unsigned NOT NULL auto_increment,
    `date` timestamp NOT NULL default CURRENT_TIMESTAMP,
    `author_id` int(11) unsigned NOT NULL default '0',
    `content` char(140) default NULL,
    `condition` varchar(255) default NULL,
    `web_display` tinyint(1) NOT NULL default '1',
    `active` tinyint(1) NOT NULL default '1',
    PRIMARY KEY  (`id`),
    KEY `date` (`date`,`active`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE `properties` (
    `type` text NOT NULL,
    `lock` int(11) NOT NULL,
    `typefield` text NOT NULL,
    `default` text NOT NULL,
    `name` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

INSERT INTO `properties` (`type`, `lock`, `typefield`, `default`, `name`) VALUES
('karts', 1, 'text', '', 'name'),
('karts', 1, 'text', '1', 'version'),
('karts', 0, 'file', '', 'File'),
('karts', 0, 'file', '', 'Icon'),
('karts', 0, 'file', '', 'Image'),
('karts', 0, 'enum', '0.7\r\n0.6', 'STK Version'),
('tracks', 1, 'text', '', 'name'),
('tracks', 0, 'text', '1', 'version'),
('tracks', 0, 'file', '', 'File'),
('tracks', 0, 'text', '', 'Author'),
('tracks', 0, 'file', '', 'Image'),
('tracks', 0, 'enum', '0.7\r\n0.6', 'STK Version')

CREATE TABLE `tracks` (
  `id` varchar(30) NOT NULL,
  `name` tinytext NOT NULL,
  `description` varchar(140),
  `uploader` int(11) NOT NULL,
  `creation_date` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `designer` tinytext NOT NULL,
  `arena` tinyint(1) NOT NULL default '0',
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `tracks_revs` (
    `id` char(23) NOT NULL,
    `addon_id` varchar(30) NOT NULL,
    `creation_date` timestamp NOT NULL default CURRENT_TIMESTAMP,
    `revision` tinyint(4) NOT NULL default '1',
    `format` tinyint(4) NOT NULL,
    `image` int UNSIGNED NOT NULL default '0',
    `status` mediumint(9) unsigned NOT NULL default '0',
    `moderator_note` varchar(255) NULL default NULL,
    UNIQUE KEY `id` (`id`),
    KEY `track_id` (`addon_id`),
    KEY `status` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `users` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `user` tinytext NOT NULL,
  `pass` char(64) NOT NULL,
  `name` tinytext NOT NULL,
  `role` tinytext NOT NULL,
  `email` tinytext NOT NULL,
  `active` tinyint(1) NOT NULL,
  `last_login` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `verify` text NOT NULL,
  `reg_date` date NOT NULL,
  `homepage` text,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

INSERT INTO `users` (`user`, `pass`, `name`, `role`, `email`, `active`, `last_login`, `verify`, `reg_date`, `homepage`, `avatar`) VALUES
('admin', 'd8fc1f6f58d1872be71a07ed0094b9bf715e5c9d90018c9ce82af0a0582ee868', 'Administrator', 'root', 'webmaster@localhost', 1, '2011-03-05 03:24:32', '', '2011-03-03', NULL, NULL);
