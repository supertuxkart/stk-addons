SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT = @@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS = @@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION = @@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

DELIMITER $$
--
-- Procedures
--
CREATE PROCEDURE `v3_create_file_record`(IN id TEXT, IN atype TEXT, IN ftype TEXT, IN fname TEXT, OUT insertid INT)
    BEGIN
        INSERT INTO `v3_files`
        (`addon_id`, `addon_type`, `file_type`, `file_path`)
        VALUES
            (id, atype, ftype, fname);
        SELECT
            LAST_INSERT_ID()
        INTO insertid;
    END$$

CREATE PROCEDURE `v3_increment_download`(IN filepath TEXT)
    UPDATE `v3_files`
    SET `downloads` = `downloads` + 1
    WHERE `file_path` = filepath$$

CREATE PROCEDURE `v3_log_event`(IN in_user INT(10) UNSIGNED, IN in_message TEXT)
    INSERT INTO `v3_logs`
    (`user`, `message`)
    VALUES
        (in_user, in_message)$$

CREATE PROCEDURE `v3_set_logintime`(IN userid INT(11), IN logintime TIMESTAMP)
    UPDATE `v3_users`
    SET `last_login` = logintime
    WHERE `id` = userid$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `v3_achieved`
--

CREATE TABLE IF NOT EXISTS `v3_achieved` (
    `userid`        INT(10) UNSIGNED NOT NULL,
    `achievementid` INT(10) UNSIGNED NOT NULL,
    PRIMARY KEY (`userid`, `achievementid`),
    KEY `userid` (`userid`),
    KEY `achievementid` (`achievementid`)
)
    ENGINE =InnoDB
    DEFAULT CHARSET =utf8mb4
    COLLATE =utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `v3_achievements`
--

CREATE TABLE IF NOT EXISTS `v3_achievements` (
    `id`   INT(10) UNSIGNED           NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(128)
           COLLATE utf8mb4_unicode_ci NOT NULL,
    PRIMARY KEY (`id`)
)
    ENGINE =InnoDB
    DEFAULT CHARSET =utf8mb4
    COLLATE =utf8mb4_unicode_ci;

--
-- Dumping data for table `v3_achievements`
--

INSERT INTO `v3_achievements` (`id`, `name`) VALUES
    (1, 'Christoffel Columbus'),
    (2, 'Strike!'),
    (3, 'Arch Enemy'),
    (4, 'Marathoner'),
    (5, 'Skid-row'),
    (6, 'Gold driver'),
    (7, 'Powerup Love'),
    (8, 'Unstoppable'),
    (9, 'Banana Lover'),
    (10, 'It''s secret');

-- --------------------------------------------------------

--
-- Table structure for table `v3_addons`
--

CREATE TABLE IF NOT EXISTS `v3_addons` (
    `id`              VARCHAR(30)                NOT NULL,
    `type`            ENUM('karts', 'tracks', 'arenas')
                      CHARACTER SET utf8mb4
                      COLLATE utf8mb4_unicode_ci NOT NULL,
    `name`            TINYTEXT
                      CHARACTER SET utf8mb4
                      COLLATE utf8mb4_unicode_ci NOT NULL,
    `uploader`        INT(11) UNSIGNED DEFAULT NULL,
    `creation_date`   TIMESTAMP                  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `designer`        TINYTEXT
                      CHARACTER SET utf8mb4
                      COLLATE utf8mb4_unicode_ci NOT NULL,
    `props`           INT(10) UNSIGNED           NOT NULL DEFAULT '0',
    `description`     VARCHAR(140)
                      CHARACTER SET utf8mb4
                      COLLATE utf8mb4_unicode_ci NOT NULL,
    `license`         MEDIUMTEXT
                      CHARACTER SET utf8mb4
                      COLLATE utf8mb4_unicode_ci,
    `min_include_ver` VARCHAR(16)
                      CHARACTER SET utf8mb4
                      COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `max_include_ver` VARCHAR(16)
                      CHARACTER SET utf8mb4
                      COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `type` (`type`),
    KEY `uploader` (`uploader`)
)
    ENGINE =InnoDB
    DEFAULT CHARSET =utf8;

-- --------------------------------------------------------

--
-- Table structure for table `v3_arenas_revs`
--

CREATE TABLE IF NOT EXISTS `v3_arenas_revs` (
    `id`             VARCHAR(23)
                     COLLATE utf8mb4_unicode_ci NOT NULL,
    `addon_id`       VARCHAR(30)
                     CHARACTER SET utf8         NOT NULL,
    `fileid`         INT(10) UNSIGNED           NOT NULL DEFAULT '0',
    `creation_date`  TIMESTAMP                  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `revision`       TINYINT(4)                 NOT NULL DEFAULT '1',
    `format`         TINYINT(4)                 NOT NULL,
    `image`          INT(10) UNSIGNED           NOT NULL DEFAULT '0',
    `status`         MEDIUMINT(9) UNSIGNED      NOT NULL DEFAULT '0',
    `moderator_note` VARCHAR(4096)
                     COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    PRIMARY KEY (`addon_id`, `revision`),
    UNIQUE KEY `id` (`id`),
    KEY `status` (`status`)
)
    ENGINE =InnoDB
    DEFAULT CHARSET =utf8mb4
    COLLATE =utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `v3_tracks_revs`
--

CREATE TABLE IF NOT EXISTS `v3_tracks_revs` (
    `id`             VARCHAR(23)
                     COLLATE utf8mb4_unicode_ci NOT NULL,
    `addon_id`       VARCHAR(30)
                     CHARACTER SET utf8         NOT NULL,
    `fileid`         INT(10) UNSIGNED           NOT NULL DEFAULT '0',
    `creation_date`  TIMESTAMP                  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `revision`       TINYINT(4)                 NOT NULL DEFAULT '1',
    `format`         TINYINT(4)                 NOT NULL,
    `image`          INT(10) UNSIGNED DEFAULT NULL,
    `status`         MEDIUMINT(9) UNSIGNED      NOT NULL DEFAULT '0',
    `moderator_note` VARCHAR(4096)
                     COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    PRIMARY KEY (`addon_id`, `revision`),
    KEY `status` (`status`),
    KEY `image` (`image`)
)
    ENGINE =InnoDB
    DEFAULT CHARSET =utf8mb4
    COLLATE =utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `v3_karts_revs`
--

CREATE TABLE IF NOT EXISTS `v3_karts_revs` (
    `id`             VARCHAR(23)
                     COLLATE utf8mb4_unicode_ci NOT NULL,
    `addon_id`       VARCHAR(30)
                     CHARACTER SET utf8         NOT NULL,
    `fileid`         INT(10) UNSIGNED           NOT NULL DEFAULT '0',
    `creation_date`  TIMESTAMP                  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `revision`       TINYINT(4)                 NOT NULL DEFAULT '1',
    `format`         TINYINT(4)                 NOT NULL,
    `image`          INT(10) UNSIGNED           NOT NULL DEFAULT '0',
    `icon`           INT(10) UNSIGNED           NOT NULL DEFAULT '0',
    `status`         MEDIUMINT(9) UNSIGNED      NOT NULL DEFAULT '0',
    `moderator_note` VARCHAR(4096)
                     COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    PRIMARY KEY (`addon_id`, `revision`),
    KEY `track_id` (`addon_id`),
    KEY `status` (`status`)
)
    ENGINE =InnoDB
    DEFAULT CHARSET =utf8mb4
    COLLATE =utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `v3_cache`
--

CREATE TABLE IF NOT EXISTS `v3_cache` (
    `file`  VARCHAR(128)
            COLLATE utf8mb4_unicode_ci NOT NULL,
    `addon` VARCHAR(30)
            COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `props` TINYTEXT
            COLLATE utf8mb4_unicode_ci,
    UNIQUE KEY `file` (`file`)
)
    ENGINE =InnoDB
    DEFAULT CHARSET =utf8mb4
    COLLATE =utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `v3_clients`
--

CREATE TABLE IF NOT EXISTS `v3_clients` (
    `agent_string` VARCHAR(255)
                   COLLATE utf8mb4_unicode_ci NOT NULL,
    `stk_version`  VARCHAR(64)
                   COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'latest',
    `disabled`     INT(1)                     NOT NULL DEFAULT '0',
    PRIMARY KEY (`agent_string`(32))
)
    ENGINE =InnoDB
    DEFAULT CHARSET =utf8mb4
    COLLATE =utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `v3_client_sessions`
--

CREATE TABLE IF NOT EXISTS `v3_client_sessions` (
    `uid`          INT(10) UNSIGNED           NOT NULL,
    `cid`          CHAR(24)
                   COLLATE utf8mb4_unicode_ci NOT NULL,
    `online`       TINYINT(1)                 NOT NULL DEFAULT '1',
    `save`         TINYINT(1)                 NOT NULL DEFAULT '0',
    `ip`           INT(10) UNSIGNED           NOT NULL DEFAULT '0',
    `private_port` SMALLINT(5) UNSIGNED       NOT NULL DEFAULT '0',
    `port`         SMALLINT(5) UNSIGNED       NOT NULL DEFAULT '0',
    `last-online`  TIMESTAMP                  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`uid`),
    UNIQUE KEY `session` (`uid`, `cid`)
)
    ENGINE = MEMORY
    DEFAULT CHARSET =utf8mb4
    COLLATE =utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `v3_config`
--

CREATE TABLE IF NOT EXISTS `v3_config` (
    `name`  VARCHAR(128)
            CHARACTER SET utf8mb4
            COLLATE utf8mb4_unicode_ci NOT NULL,
    `value` VARCHAR(512)
            CHARACTER SET utf8mb4
            COLLATE utf8mb4_unicode_ci NOT NULL,
    UNIQUE KEY `name` (`name`)
)
    ENGINE =InnoDB
    DEFAULT CHARSET =utf8mb4
    COLLATE =utf8mb4_unicode_ci;

--
-- Dumping data for table `v3_config`
--

INSERT INTO `v3_config` (`name`, `value`) VALUES
    ('allowed_addon_exts', 'zip, tar, tar.gz, tgz, gz, tbz, tar.bz2, bz2, b3d, txt, png, jpg, jpeg, xml'),
    ('allowed_source_exts', 'txt, blend, b3d, xml, png, jpg, jpeg, xcf, rgb, svg'),
    ('max_image_dimension', '2048'),
    ('blog_feed', 'http://supertuxkart.blogspot.com/feeds/posts/default');

-- --------------------------------------------------------

--
-- Table structure for table `v3_files`
--

CREATE TABLE IF NOT EXISTS `v3_files` (
    `id`          INT(11) UNSIGNED           NOT NULL AUTO_INCREMENT,
    `addon_id`    VARCHAR(30)
                  COLLATE utf8mb4_unicode_ci NOT NULL,
    `addon_type`  ENUM('karts', 'tracks', 'arenas')
                  COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `file_type`   ENUM('source', 'image', 'addon')
                  COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `file_path`   VARCHAR(256)
                  COLLATE utf8mb4_unicode_ci NOT NULL,
    `date_added`  TIMESTAMP                  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `approved`    INT(1)                     NOT NULL DEFAULT '0',
    `downloads`   INT(10) UNSIGNED           NOT NULL DEFAULT '0',
    `delete_date` DATE                       NOT NULL DEFAULT '0000-00-00',
    PRIMARY KEY (`id`),
    KEY `delete_date` (`delete_date`),
    KEY `addon_id` (`addon_id`)
)
    ENGINE =InnoDB
    DEFAULT CHARSET =utf8mb4
    COLLATE =utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `v3_friends`
--

CREATE TABLE IF NOT EXISTS `v3_friends` (
    `asker_id`    INT(10) UNSIGNED NOT NULL,
    `receiver_id` INT(10) UNSIGNED NOT NULL,
    `request`     TINYINT(1)       NOT NULL DEFAULT '1',
    `date`        TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`asker_id`, `receiver_id`),
    KEY `v3_friends_ibfk_2` (`receiver_id`)
)
    ENGINE =InnoDB
    DEFAULT CHARSET =utf8mb4
    COLLATE =utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `v3_host_votes`
--

CREATE TABLE IF NOT EXISTS `v3_host_votes` (
    `userid` INT(10) UNSIGNED NOT NULL,
    `hostid` INT(10) UNSIGNED NOT NULL,
    `vote`   INT(11)          NOT NULL,
    PRIMARY KEY (`userid`, `hostid`),
    KEY `hostid` (`hostid`)
)
    ENGINE =InnoDB
    DEFAULT CHARSET =utf8mb4
    COLLATE =utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `v3_logs`
--

CREATE TABLE IF NOT EXISTS `v3_logs` (
    `id`      INT(10) UNSIGNED           NOT NULL AUTO_INCREMENT,
    `date`    TIMESTAMP                  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `user`    INT(10) UNSIGNED           NOT NULL,
    `message` TEXT
              COLLATE utf8mb4_unicode_ci NOT NULL,
    `emailed` INT(1) UNSIGNED            NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`)
)
    ENGINE =InnoDB
    DEFAULT CHARSET =utf8mb4
    COLLATE =utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `v3_music`
--

CREATE TABLE IF NOT EXISTS `v3_music` (
    `id`           INT(11)                    NOT NULL AUTO_INCREMENT,
    `title`        VARCHAR(256)
                   COLLATE utf8mb4_unicode_ci NOT NULL,
    `artist`       VARCHAR(256)
                   COLLATE utf8mb4_unicode_ci NOT NULL,
    `license`      VARCHAR(1024)
                   COLLATE utf8mb4_unicode_ci NOT NULL,
    `gain`         FLOAT                      NOT NULL DEFAULT '1',
    `length`       INT(11)                    NOT NULL DEFAULT '0',
    `file`         VARCHAR(191)
                   COLLATE utf8mb4_unicode_ci NOT NULL,
    `file_md5`     CHAR(32)
                   COLLATE utf8mb4_unicode_ci NOT NULL,
    `xml_filename` VARCHAR(191)
                   COLLATE utf8mb4_unicode_ci NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `file` (`file`),
    UNIQUE KEY `xml_filename` (`xml_filename`)
)
    ENGINE =InnoDB
    DEFAULT CHARSET =utf8mb4
    COLLATE =utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `v3_news`
--

CREATE TABLE IF NOT EXISTS `v3_news` (
    `id`          INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `date`        TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `author_id`   INT(11) UNSIGNED DEFAULT NULL,
    `content`     VARCHAR(256)
                  COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `condition`   VARCHAR(256)
                  COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `important`   TINYINT(1)       NOT NULL DEFAULT '0',
    `web_display` TINYINT(1)       NOT NULL DEFAULT '1',
    `active`      TINYINT(1)       NOT NULL DEFAULT '1',
    `dynamic`     INT(1) UNSIGNED  NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY `date` (`date`, `active`),
    KEY `dynamic` (`dynamic`),
    KEY `author_id` (`author_id`)
)
    ENGINE =InnoDB
    DEFAULT CHARSET =utf8mb4
    COLLATE =utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `v3_notifications`
--

CREATE TABLE IF NOT EXISTS `v3_notifications` (
    `to`   INT(10) UNSIGNED           NOT NULL,
    `from` INT(10) UNSIGNED           NOT NULL,
    `type` VARCHAR(16)
           COLLATE utf8mb4_unicode_ci NOT NULL,
    UNIQUE KEY `to_2` (`to`, `type`),
    KEY `to` (`to`)
)
    ENGINE =InnoDB
    DEFAULT CHARSET =utf8mb4
    COLLATE =utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `v3_servers`
--

CREATE TABLE IF NOT EXISTS `v3_servers` (
    `id`              INT(10) UNSIGNED           NOT NULL AUTO_INCREMENT,
    `hostid`          INT(10) UNSIGNED           NOT NULL,
    `name`            TINYTEXT
                      COLLATE utf8mb4_unicode_ci NOT NULL,
    `ip`              INT(10) UNSIGNED           NOT NULL DEFAULT '0',
    `port`            SMALLINT(5) UNSIGNED       NOT NULL DEFAULT '0',
    `private_port`    SMALLINT(5) UNSIGNED       NOT NULL DEFAULT '0',
    `max_players`     TINYINT(3) UNSIGNED        NOT NULL DEFAULT '0',
    `current_players` TINYINT(4) UNSIGNED        NOT NULL DEFAULT '0'
    COMMENT 'Isn''t exact. Just to show in the server-list, where it doens''t need to be exact.',
    PRIMARY KEY (`id`),
    KEY `hostid` (`hostid`)
)
    ENGINE =InnoDB
    DEFAULT CHARSET =utf8mb4
    COLLATE =utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `v3_server_conn`
--

CREATE TABLE IF NOT EXISTS `v3_server_conn` (
    `serverid` INT(10) UNSIGNED NOT NULL,
    `userid`   INT(10) UNSIGNED NOT NULL,
    `request`  TINYINT(1)       NOT NULL DEFAULT '1',
    UNIQUE KEY `userid` (`userid`),
    KEY `serverid` (`serverid`)
)
    ENGINE =InnoDB
    DEFAULT CHARSET =utf8mb4
    COLLATE =utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `v3_stats`
--

CREATE TABLE IF NOT EXISTS `v3_stats` (
    `type`  TEXT
            COLLATE utf8mb4_unicode_ci NOT NULL,
    `date`  DATE                       NOT NULL,
    `value` INT(10) UNSIGNED           NOT NULL DEFAULT '0',
    PRIMARY KEY (`date`, `type`(40)),
    KEY `date` (`date`)
)
    ENGINE =InnoDB
    DEFAULT CHARSET =utf8mb4
    COLLATE =utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `v3_users`
--

CREATE TABLE IF NOT EXISTS `v3_users` (
    `id`         INT(11) UNSIGNED           NOT NULL AUTO_INCREMENT,
    `user`       VARCHAR(30)
                 CHARACTER SET ascii        NOT NULL,
    `pass`       CHAR(96)
                 COLLATE utf8mb4_unicode_ci NOT NULL,
    `name`       VARCHAR(64)
                 COLLATE utf8mb4_unicode_ci NOT NULL,
    `role`       VARCHAR(64)
                 COLLATE utf8mb4_unicode_ci NOT NULL,
    `email`      VARCHAR(64)
                 COLLATE utf8mb4_unicode_ci NOT NULL,
    `active`     TINYINT(1)                 NOT NULL,
    `last_login` TIMESTAMP                  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `reg_date`   DATE                       NOT NULL,
    `homepage`   VARCHAR(64)
                 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `avatar`     VARCHAR(64)
                 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `user` (`user`)
)
    ENGINE =InnoDB
    DEFAULT CHARSET =utf8mb4
    COLLATE =utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `v3_verification`
--

CREATE TABLE IF NOT EXISTS `v3_verification` (
    `userid` INT(10) UNSIGNED           NOT NULL DEFAULT '0',
    `code`   VARCHAR(32)
             COLLATE utf8mb4_unicode_ci NOT NULL
    COMMENT 'The verification code',
    PRIMARY KEY (`userid`),
    UNIQUE KEY `userid` (`userid`)
)
    ENGINE =InnoDB
    DEFAULT CHARSET =utf8mb4
    COLLATE =utf8mb4_unicode_ci
    COMMENT ='Used for account activation and recovery';


-- --------------------------------------------------------

--
-- Table structure for table `v3_votes`
--

CREATE TABLE IF NOT EXISTS `v3_votes` (
    `user_id`  INT(11) UNSIGNED   NOT NULL,
    `addon_id` VARCHAR(30)
               CHARACTER SET utf8 NOT NULL,
    `vote`     FLOAT UNSIGNED     NOT NULL,
    PRIMARY KEY (`user_id`, `addon_id`),
    KEY `addon_id` (`addon_id`)
)
    ENGINE =InnoDB
    DEFAULT CHARSET =utf8mb4
    COLLATE =utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `v3_bugs`
--

CREATE TABLE IF NOT EXISTS `v3_bugs` (
    `id`           INT(11) UNSIGNED           NOT NULL AUTO_INCREMENT,
    `user_id`      INT(11) UNSIGNED           NOT NULL
    COMMENT 'User who filed the bug report',
    `addon_id`     VARCHAR(30)
                   CHARACTER SET utf8 DEFAULT NULL
    COMMENT 'The bug culprit',
    `close_id`     INT(11) UNSIGNED DEFAULT NULL
    COMMENT 'The user who closed the bug',
    `close_reason` VARCHAR(512)
                   COLLATE utf8mb4_unicode_ci DEFAULT NULL
    COMMENT 'The reason it was closed',
    `date_report`  TIMESTAMP                  NULL DEFAULT NULL
    COMMENT 'Report date',
    `date_edit`    TIMESTAMP                  NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last edit date',
    `date_close`   TIMESTAMP                  NULL DEFAULT NULL
    COMMENT 'Close date',
    `title`        VARCHAR(64)
                   COLLATE utf8mb4_unicode_ci NOT NULL
    COMMENT 'Bug title',
    `description`  VARCHAR(1024)
                   COLLATE utf8mb4_unicode_ci NOT NULL
    COMMENT 'Bug description',
    `is_report`    TINYINT(1)                 NOT NULL DEFAULT '0'
    COMMENT 'Flag to indicate if the bug is a feedback',
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `addon_id` (`addon_id`)
)
    ENGINE =InnoDB
    DEFAULT CHARSET =utf8mb4
    COLLATE =utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `v3_bugs_comments`
--

CREATE TABLE IF NOT EXISTS `v3_bugs_comments` (
    `id`          INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `bug_id`      INT(11) UNSIGNED NOT NULL
    COMMENT 'The bug we commented on',
    `user_id`     INT(11) UNSIGNED NOT NULL
    COMMENT 'The user who commented',
    `date`        TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP
    COMMENT 'The date it was reported',
    `description` VARCHAR(512)
                  COLLATE utf8mb4_unicode_ci DEFAULT NULL
    COMMENT 'The comment description',
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `bug_id` (`bug_id`)
)
    ENGINE =InnoDB
    DEFAULT CHARSET =utf8mb4
    COLLATE =utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `v3_roles`
--

CREATE TABLE IF NOT EXISTS `v3_roles` (
    `id`   INT(4)                     NOT NULL AUTO_INCREMENT COMMENT 'The role unique identifier',
    `name` VARCHAR(128)
           COLLATE utf8mb4_unicode_ci NOT NULL
    COMMENT 'The name identifier',
    PRIMARY KEY (`id`)
)
    ENGINE =InnoDB
    DEFAULT CHARSET =utf8mb4
    COLLATE =utf8mb4_unicode_ci
    AUTO_INCREMENT =4;

--
-- Dumping data for table `v3_roles`
--

INSERT INTO `v3_roles` (`id`, `name`) VALUES
    (1, 'user'),
    (2, 'moderator'),
    (3, 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `v3_role_permissions`
--

CREATE TABLE IF NOT EXISTS `v3_role_permissions` (
    `role_id`    INT(4)                     NOT NULL
    COMMENT 'The id from the roles table',
    `permission` VARCHAR(128)
                 COLLATE utf8mb4_unicode_ci NOT NULL
    COMMENT 'The actual permission',
    KEY `role_id` (`role_id`)
)
    ENGINE =InnoDB
    DEFAULT CHARSET =utf8mb4
    COLLATE =utf8mb4_unicode_ci;

--
-- Dumping data for table `v3_role_permissions`
--

INSERT INTO `v3_role_permissions` (`role_id`, `permission`) VALUES
    (1, 'view_basic_page'),
    (1, 'add_addon'),
    (1, 'add_bug'),
    (1, 'add_bug_comment'),
    (2, 'view_basic_page'),
    (2, 'add_addon'),
    (2, 'add_bug'),
    (2, 'add_bug_comment'),
    (2, 'edit_addons'),
    (2, 'edit_bugs'),
    (2, 'edit_users'),
    (3, 'view_basic_page'),
    (3, 'add_addon'),
    (3, 'add_bug'),
    (3, 'add_bug_comment'),
    (3, 'edit_addons'),
    (3, 'edit_bugs'),
    (3, 'edit_users'),
    (3, 'edit_settings'),
    (3, 'edit_permissions'),
    (3, 'edit_admins');

-- --------------------------------------------------------

--
-- Constraints for table `v3_achieved`
--
ALTER TABLE `v3_achieved`
ADD CONSTRAINT `v3_achieved_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `v3_users` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
ADD CONSTRAINT `v3_achieved_ibfk_2` FOREIGN KEY (`achievementid`) REFERENCES `v3_achievements` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION;

--
-- Constraints for table `v3_friends`
--
ALTER TABLE `v3_friends`
ADD CONSTRAINT `v3_friends_ibfk_1` FOREIGN KEY (`asker_id`) REFERENCES `v3_users` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
ADD CONSTRAINT `v3_friends_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `v3_users` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION;

--
-- Constraints for table `v3_addons`
--
ALTER TABLE `v3_addons`
ADD CONSTRAINT `v3_addons_ibfk_1` FOREIGN KEY (`uploader`) REFERENCES `v3_users` (`id`)
    ON DELETE SET NULL
    ON UPDATE NO ACTION;

--
-- Constraints for table `v3_arenas_revs`
--
ALTER TABLE `v3_arenas_revs`
ADD CONSTRAINT `v3_arenas_revs_ibfk_1` FOREIGN KEY (`addon_id`) REFERENCES `v3_addons` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION;

--
-- Constraints for table `v3_karts_revs`
--
ALTER TABLE `v3_karts_revs`
ADD CONSTRAINT `v3_karts_revs_ibfk_1` FOREIGN KEY (`addon_id`) REFERENCES `v3_addons` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION;

--
-- Constraints for table `v3_tracks_revs`
--
ALTER TABLE `v3_tracks_revs`
ADD CONSTRAINT `v3_tracks_revs_ibfk_1` FOREIGN KEY (`addon_id`) REFERENCES `v3_addons` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION;

--
-- Constraints for table `v3_news`
--
ALTER TABLE `v3_news`
ADD CONSTRAINT `v3_news_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `v3_users` (`id`)
    ON DELETE SET NULL
    ON UPDATE NO ACTION;

--
-- Constraints for table `v3_servers`
--
ALTER TABLE `v3_servers`
ADD CONSTRAINT `v3_servers_ibfk_1` FOREIGN KEY (`hostid`) REFERENCES `v3_users` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION;

--
-- Constraints for table `v3_server_conn`
--
ALTER TABLE `v3_server_conn`
ADD CONSTRAINT `v3_server_conn_ibfk_1` FOREIGN KEY (`serverid`) REFERENCES `v3_servers` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
ADD CONSTRAINT `v3_server_conn_ibfk_2` FOREIGN KEY (`userid`) REFERENCES `v3_users` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION;

--
-- Constraints for table `v3_verification`
--
ALTER TABLE `v3_verification`
ADD CONSTRAINT `v3_verification_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `v3_users` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION;

--
-- Constraints for table `v3_votes`
--
ALTER TABLE `v3_votes`
ADD CONSTRAINT `v3_votes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `v3_users` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
ADD CONSTRAINT `v3_votes_ibfk_2` FOREIGN KEY (`addon_id`) REFERENCES `v3_addons` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION;

--
-- Constraints for table `v3_bugs`
--
ALTER TABLE `v3_bugs`
ADD CONSTRAINT `v3_bugs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `v3_users` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
ADD CONSTRAINT `v3_bugs_ibfk_2` FOREIGN KEY (`addon_id`) REFERENCES `v3_addons` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION;

--
-- Constraints for table `v3_bugs_comments`
--
ALTER TABLE `v3_bugs_comments`
ADD CONSTRAINT `v3_bugs_comments_ibfk_1` FOREIGN KEY (`bug_id`) REFERENCES `v3_bugs` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
ADD CONSTRAINT `v3_bugs_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `v3_users` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION;

--
-- Constraints for table `v3_role_permissions`
--
ALTER TABLE `v3_role_permissions`
ADD CONSTRAINT `v3_role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `v3_roles` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION;

/*!40101 SET CHARACTER_SET_CLIENT = @OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS = @OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION = @OLD_COLLATION_CONNECTION */;
