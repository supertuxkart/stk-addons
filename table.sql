SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

CREATE TABLE `addons` (
    `id` varchar(30) NOT NULL,
    `type` ENUM('karts','tracks'),
    `name` tinytext NOT NULL,
    `uploader` int(11) NOT NULL,
    `creation_date` timestamp NOT NULL default CURRENT_TIMESTAMP,
    `designer` tinytext NOT NULL,
    `props` int UNSIGNED NOT NULL DEFAULT '0',
    `description` varchar(140),
    `license` varchar(4096) NULL DEFAULT NULL,
    UNIQUE KEY `id` (`id`),
    INDEX (`type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

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

CREATE TABLE `karts_revs` (
    `id` char(23) NOT NULL,
    `addon_id` varchar(30) NOT NULL,
    `fileid` INT UNSIGNED NOT NULL DEFAULT '0',
    `creation_date` timestamp NOT NULL default CURRENT_TIMESTAMP,
    `revision` tinyint(4) NOT NULL default '1',
    `format` tinyint(4) NOT NULL,
    `icon` int UNSIGNED NOT NULL default '0',
    `image` int UNSIGNED NOT NULL default '0',
    `status` mediumint(9) unsigned NOT NULL default '0',
    `moderator_note` varchar(4096) NULL default NULL,
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

CREATE TABLE `tracks_revs` (
    `id` char(23) NOT NULL,
    `addon_id` varchar(30) NOT NULL,
    `fileid` INT UNSIGNED NOT NULL DEFAULT '0',
    `creation_date` timestamp NOT NULL default CURRENT_TIMESTAMP,
    `revision` tinyint(4) NOT NULL default '1',
    `format` tinyint(4) NOT NULL,
    `image` int UNSIGNED NOT NULL default '0',
    `status` mediumint(9) unsigned NOT NULL default '0',
    `moderator_note` varchar(4096) NULL default NULL,
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

/* Default admin password is 'password' - Change after install. */
INSERT INTO `users` (`user`, `pass`, `name`, `role`, `email`, `active`, `last_login`, `verify`, `reg_date`, `homepage`) VALUES
('admin', '5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8', 'Administrator', 'root', 'webmaster@localhost', 1, '2011-03-05 03:24:32', '', '2011-03-03', NULL);
