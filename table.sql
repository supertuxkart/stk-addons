SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

CREATE TABLE `addons` (
    `id` varchar(30) NOT NULL,
    `type` ENUM('karts','tracks','arenas'),
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

CREATE TABLE `arenas_revs` (
    `id` char(23) NOT NULL,
    `addon_id` varchar(30) NOT NULL,
    `fileid` int(10) unsigned NOT NULL DEFAULT '0',
    `creation_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `revision` tinyint(4) NOT NULL DEFAULT '1',
    `format` tinyint(4) NOT NULL,
    `image` int(10) unsigned NOT NULL DEFAULT '0',
    `status` mediumint(9) unsigned NOT NULL DEFAULT '0',
    `moderator_note` varchar(4096) DEFAULT NULL,
    UNIQUE KEY `id` (`id`),
    KEY `track_id` (`addon_id`),
    KEY `status` (`status`),
    KEY `revision` (`revision`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `client_sessions` (
    `uid` INT(10) UNSIGNED NOT NULL DEFAULT 0,
    `name` VARCHAR(64) NULL DEFAULT NULL,
    `cid` CHAR(24) NOT NULL,
    `last-request` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (`uid` , `cid`),
    UNIQUE (`name`)
) ENGINE=MyISAM;

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
('admin_email','webmaster@localhost'),
('list_email','supertuxkart-addons@lists.sourceforge.net'),
('list_invisible','1'),
('blog_feed','http://supertuxkart.blogspot.com/feeds/posts/default?alt=rss'),
('max_image_dimension','1024');

CREATE TABLE `files` (
    `id` int(11) NOT NULL auto_increment,
    `addon_id` varchar(30) NOT NULL,
    `addon_type` ENUM('karts','tracks','arenas'),
    `file_type` ENUM('source','image','addon'),
    `file_path` text NOT NULL,
    `approved` int(1) NOT NULL default 0,
    `downloads` int UNSIGNED NOT NULL default 0,
    PRIMARY KEY (`id`),
    FULLTEXT (`file_path`)
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

CREATE TABLE `logs` (
    `id` int(10) unsigned NOT NULL auto_increment,
    `date` timestamp NOT NULL default CURRENT_TIMESTAMP,
    `user` int(10) unsigned NOT NULL,
    `message` text NOT NULL,
    `emailed` int(1) unsigned NOT NULL default 0,
    PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

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
    KEY `status` (`status`),
    KEY `revision` (`revision`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `news` (
    `id` int(10) unsigned NOT NULL auto_increment,
    `date` timestamp NOT NULL default CURRENT_TIMESTAMP,
    `author_id` int(11) unsigned NOT NULL default '0',
    `content` char(140) default NULL,
    `condition` varchar(255) default NULL,
    `web_display` tinyint(1) NOT NULL default '1',
    `active` tinyint(1) NOT NULL default '1',
    `dynamic` int(1) unsigned NOT NULL default '0',
    PRIMARY KEY  (`id`),
    INDEX (`dynamic`),
    KEY `date` (`date`,`active`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE `stats` (
    `id` int unsigned NOT NULL auto_increment,
    `type` text NOT NULL,
    `date` date NOT NULL,
    `value` int(10) unsigned NOT NULL default '0',
    PRIMARY KEY (`id`),
    KEY (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

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
    KEY `status` (`status`),
    KEY `revision` (`revision`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `users` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `user` tinytext NOT NULL,
  `pass` char(96) NOT NULL,
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

CREATE TABLE `votes` (
    `id` int unsigned NOT NULL auto_increment,
    `user_id` int unsigned NOT NULL,
    `addon_id` varchar(30) NOT NULL,
    `vote` int(1) unsigned NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- Stored Procedures
DELIMITER //
CREATE PROCEDURE create_file_record (IN id TEXT, IN atype TEXT, IN ftype TEXT, IN fname TEXT, OUT insertid INT)
BEGIN
    INSERT INTO `files`
    (`addon_id`,`addon_type`,`file_type`,`file_path`)
    VALUES
    (id,atype,ftype,fname);
    SELECT LAST_INSERT_ID() INTO insertid;
END//
DELIMITER ;

CREATE PROCEDURE increment_download (IN filepath TEXT)
    UPDATE `files`
    SET `downloads` = `downloads` + 1
    WHERE `file_path` = filepath;

CREATE PROCEDURE register_user (IN in_user TEXT, IN in_pass CHAR(96), IN in_name TEXT, IN in_email TEXT, IN in_vercode TEXT, IN in_regdate DATE)
    INSERT INTO `users`
    (`user`,`pass`,`name`,`role`,`email`,`active`,`verify`,`reg_date`)
    VALUES
    (in_user, in_pass, in_name, 'basicUser', in_email, 0, in_vercode, in_regdate);

CREATE PROCEDURE set_logintime (IN userid INT(11), IN logintime TIMESTAMP)
    UPDATE `users`
    SET `last_login` = logintime
    WHERE `id` = userid;

CREATE PROCEDURE log_event (IN in_user INT(10) unsigned, IN in_message TEXT)
    INSERT INTO `logs`
    (`user`,`message`)
    VALUES
    (in_user,in_message);