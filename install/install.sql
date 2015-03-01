SET NAMES utf8mb4;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+00:00';


DELIMITER $$
--
-- Procedures
--
DROP PROCEDURE IF EXISTS v3_create_file_record$$
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

DROP PROCEDURE IF EXISTS v3_increment_download$$
CREATE PROCEDURE `v3_increment_download`(IN filepath TEXT)
    UPDATE `v3_files`
    SET `downloads` = `downloads` + 1
    WHERE `file_path` = filepath$$

DROP PROCEDURE IF EXISTS v3_log_event$$
CREATE PROCEDURE `v3_log_event`(IN in_user INT UNSIGNED, IN in_message TEXT)
    INSERT INTO `v3_logs`
    (`user`, `message`)
    VALUES
        (in_user, in_message)$$

DROP PROCEDURE IF EXISTS v3_set_logintime$$
CREATE PROCEDURE `v3_set_logintime`(IN userid INT, IN logintime TIMESTAMP)
    UPDATE `v3_users`
    SET `last_login` = logintime
    WHERE `id` = userid$$

DELIMITER ;

-- --------------------------------------------------------------------------------
-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_roles`
--
CREATE TABLE IF NOT EXISTS `v3_roles` (
    `id`   INT UNSIGNED               NOT NULL AUTO_INCREMENT COMMENT 'The role unique identifier',
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
    (3, 'admin')
ON DUPLICATE KEY UPDATE `id` = VALUES(`id`), `name` = VALUES(`name`);

-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_role_permissions`
--
CREATE TABLE IF NOT EXISTS `v3_role_permissions` (
    `role_id`    INT UNSIGNED               NOT NULL
    COMMENT 'The id from the roles table',
    `permission` VARCHAR(128)
                 COLLATE utf8mb4_unicode_ci NOT NULL
    COMMENT 'The actual permission',
    PRIMARY KEY (`role_id`, `permission`),
    CONSTRAINT `v3_role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `v3_roles` (`id`)
        ON DELETE CASCADE
        ON UPDATE NO ACTION
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
    (3, 'edit_admins')
ON DUPLICATE KEY UPDATE `role_id` = VALUES(`role_id`), `permission` = VALUES(`permission`);

-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_users`
--
CREATE TABLE IF NOT EXISTS `v3_users` (
    `id`         INT UNSIGNED               NOT NULL AUTO_INCREMENT,
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
    `is_active`  BOOL                       NOT NULL,
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

-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_verification`
--
CREATE TABLE IF NOT EXISTS `v3_verification` (
    `userid` INT UNSIGNED               NOT NULL DEFAULT '0',
    `code`   VARCHAR(32)
             COLLATE utf8mb4_unicode_ci NOT NULL
    COMMENT 'The verification code',
    PRIMARY KEY (`userid`),
    UNIQUE KEY `userid` (`userid`),
    CONSTRAINT `v3_verification_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `v3_users` (`id`)
        ON DELETE CASCADE
        ON UPDATE NO ACTION
)
    ENGINE =InnoDB
    DEFAULT CHARSET =utf8mb4
    COLLATE =utf8mb4_unicode_ci
    COMMENT ='Used for account activation and recovery';

-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_achievements`
--
CREATE TABLE IF NOT EXISTS `v3_achievements` (
    `id`   INT UNSIGNED               NOT NULL AUTO_INCREMENT,
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
    (10, 'It''s secret')
ON DUPLICATE KEY UPDATE `id` = VALUES(`id`), `name` = VALUES(`name`);

-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_achieved`
--
CREATE TABLE IF NOT EXISTS `v3_achieved` (
    `userid`        INT UNSIGNED NOT NULL,
    `achievementid` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`userid`, `achievementid`),
    KEY `userid` (`userid`),
    KEY `achievementid` (`achievementid`),
    CONSTRAINT `v3_achieved_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `v3_users` (`id`)
        ON DELETE CASCADE
        ON UPDATE NO ACTION,
    CONSTRAINT `v3_achieved_ibfk_2` FOREIGN KEY (`achievementid`) REFERENCES `v3_achievements` (`id`)
        ON DELETE CASCADE
        ON UPDATE NO ACTION
)
    ENGINE =InnoDB
    DEFAULT CHARSET =utf8mb4
    COLLATE =utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_friends`
--
CREATE TABLE IF NOT EXISTS `v3_friends` (
    `asker_id`    INT UNSIGNED NOT NULL,
    `receiver_id` INT UNSIGNED NOT NULL,
    `is_request`  BOOL         NOT NULL DEFAULT '1',
    `date`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`asker_id`, `receiver_id`),
    CONSTRAINT `v3_friends_ibfk_1` FOREIGN KEY (`asker_id`) REFERENCES `v3_users` (`id`)
        ON DELETE CASCADE
        ON UPDATE NO ACTION,
    CONSTRAINT `v3_friends_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `v3_users` (`id`)
        ON DELETE CASCADE
        ON UPDATE NO ACTION
)
    ENGINE =InnoDB
    DEFAULT CHARSET =utf8mb4
    COLLATE =utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_notifications`
--
CREATE TABLE IF NOT EXISTS `v3_notifications` (
    `to`   INT UNSIGNED               NOT NULL,
    `from` INT UNSIGNED               NOT NULL,
    `type` VARCHAR(16)
           COLLATE utf8mb4_unicode_ci NOT NULL,
    UNIQUE KEY `to_2` (`to`, `type`),
    KEY `to` (`to`),
    CONSTRAINT `v3_notifications_ibfk_1` FOREIGN KEY (`to`) REFERENCES `v3_users` (`id`)
        ON DELETE CASCADE
        ON UPDATE NO ACTION,
    CONSTRAINT `v3_notifications_ibfk_2` FOREIGN KEY (`from`) REFERENCES `v3_users` (`id`)
        ON DELETE CASCADE
        ON UPDATE NO ACTION
)
    ENGINE =InnoDB
    DEFAULT CHARSET =utf8mb4
    COLLATE =utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_logs`
--
CREATE TABLE IF NOT EXISTS `v3_logs` (
    `id`         INT UNSIGNED               NOT NULL AUTO_INCREMENT,
    `user`       INT UNSIGNED DEFAULT NULL,
    `date`       TIMESTAMP                  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `message`    TEXT
                 COLLATE utf8mb4_unicode_ci NOT NULL,
    `is_emailed` BOOL                       NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    CONSTRAINT `v3_logs_ibfk_1` FOREIGN KEY (`user`) REFERENCES `v3_users` (`id`)
        ON DELETE CASCADE
        ON UPDATE NO ACTION
)
    ENGINE =InnoDB
    DEFAULT CHARSET =utf8mb4
    COLLATE =utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_news`
--
CREATE TABLE IF NOT EXISTS `v3_news` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `author_id`      INT UNSIGNED DEFAULT NULL,
    `date`           TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `content`        VARCHAR(256)
                     COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `condition`      VARCHAR(256)
                     COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `is_important`   BOOL         NOT NULL DEFAULT '0',
    `is_web_display` BOOL         NOT NULL DEFAULT '1',
    `is_active`      BOOL         NOT NULL DEFAULT '1',
    `is_dynamic`     BOOL         NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    CONSTRAINT `v3_news_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `v3_users` (`id`)
        ON DELETE SET NULL
        ON UPDATE NO ACTION
)
    ENGINE =InnoDB
    DEFAULT CHARSET =utf8mb4
    COLLATE =utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_client_sessions`
--
CREATE TABLE IF NOT EXISTS `v3_client_sessions` (
    `uid`          INT UNSIGNED               NOT NULL,
    `cid`          CHAR(24)
                   COLLATE utf8mb4_unicode_ci NOT NULL,
    `is_online`    BOOL                       NOT NULL DEFAULT '1',
    `is_save`      BOOL                       NOT NULL DEFAULT '0',
    `ip`           INT UNSIGNED               NOT NULL DEFAULT '0',
    `private_port` SMALLINT UNSIGNED          NOT NULL DEFAULT '0',
    `port`         SMALLINT UNSIGNED          NOT NULL DEFAULT '0',
    `last-online`  TIMESTAMP                  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`uid`),
    UNIQUE KEY `session` (`uid`, `cid`)
)
    ENGINE = MEMORY
    DEFAULT CHARSET =utf8mb4
    COLLATE =utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_host_votes`
--
CREATE TABLE IF NOT EXISTS `v3_host_votes` (
    `userid` INT UNSIGNED NOT NULL,
    `hostid` INT UNSIGNED NOT NULL,
    `vote`   INT          NOT NULL,
    PRIMARY KEY (`userid`, `hostid`),
    KEY `hostid` (`hostid`)
)
    ENGINE =InnoDB
    DEFAULT CHARSET =utf8mb4
    COLLATE =utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_servers`
--
CREATE TABLE IF NOT EXISTS `v3_servers` (
    `id`              INT UNSIGNED               NOT NULL AUTO_INCREMENT,
    `hostid`          INT UNSIGNED               NOT NULL,
    `name`            TINYTEXT
                      COLLATE utf8mb4_unicode_ci NOT NULL,
    `ip`              INT UNSIGNED               NOT NULL DEFAULT '0',
    `port`            SMALLINT UNSIGNED          NOT NULL DEFAULT '0',
    `private_port`    SMALLINT UNSIGNED          NOT NULL DEFAULT '0',
    `max_players`     TINYINT UNSIGNED           NOT NULL DEFAULT '0',
    `current_players` TINYINT UNSIGNED           NOT NULL DEFAULT '0'
    COMMENT 'Isn''t exact. Just to show in the server-list, where it doens''t need to be exact.',
    PRIMARY KEY (`id`),
    KEY `hostid` (`hostid`),
    CONSTRAINT `v3_servers_ibfk_1` FOREIGN KEY (`hostid`) REFERENCES `v3_users` (`id`)
        ON DELETE CASCADE
        ON UPDATE NO ACTION
)
    ENGINE =InnoDB
    DEFAULT CHARSET =utf8mb4
    COLLATE =utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_server_conn`
--
CREATE TABLE IF NOT EXISTS `v3_server_conn` (
    `serverid`   INT UNSIGNED NOT NULL,
    `userid`     INT UNSIGNED NOT NULL,
    `is_request` BOOL         NOT NULL DEFAULT '1',
    UNIQUE KEY `userid` (`userid`),
    KEY `serverid` (`serverid`),
    CONSTRAINT `v3_server_conn_ibfk_1` FOREIGN KEY (`serverid`) REFERENCES `v3_servers` (`id`)
        ON DELETE CASCADE
        ON UPDATE NO ACTION,
    CONSTRAINT `v3_server_conn_ibfk_2` FOREIGN KEY (`userid`) REFERENCES `v3_users` (`id`)
        ON DELETE CASCADE
        ON UPDATE NO ACTION
)
    ENGINE =InnoDB
    DEFAULT CHARSET =utf8mb4
    COLLATE =utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------------
-- --------------------------------------------------------------------------------
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
    `uploader`        INT UNSIGNED DEFAULT NULL,
    `creation_date`   TIMESTAMP                  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `designer`        TINYTEXT
                      CHARACTER SET utf8mb4
                      COLLATE utf8mb4_unicode_ci NOT NULL,
    `props`           INT UNSIGNED               NOT NULL DEFAULT '0',
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
    KEY `uploader` (`uploader`),
    CONSTRAINT `v3_addons_ibfk_1` FOREIGN KEY (`uploader`) REFERENCES `v3_users` (`id`)
        ON DELETE SET NULL
        ON UPDATE NO ACTION
)
    ENGINE =InnoDB
    DEFAULT CHARSET =utf8;

-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_files`
--
CREATE TABLE IF NOT EXISTS `v3_files` (
    `id`          INT UNSIGNED               NOT NULL AUTO_INCREMENT,
    `addon_id`    VARCHAR(30)
                  COLLATE utf8mb4_unicode_ci NOT NULL,
    `addon_type`  ENUM('karts', 'tracks', 'arenas')
                  COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `file_type`   ENUM('source', 'image', 'addon')
                  COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `file_path`   VARCHAR(256)
                  COLLATE utf8mb4_unicode_ci NOT NULL,
    `date_added`  TIMESTAMP                  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `is_approved` BOOL                       NOT NULL DEFAULT '0',
    `downloads`   INT UNSIGNED               NOT NULL DEFAULT '0',
    `delete_date` DATE                       NOT NULL DEFAULT '0000-00-00',
    PRIMARY KEY (`id`),
    KEY `addon_id` (`addon_id`)
)
    ENGINE =InnoDB
    DEFAULT CHARSET =utf8mb4
    COLLATE =utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_arenas_revs`
--
CREATE TABLE IF NOT EXISTS `v3_arenas_revs` (
    `id`             VARCHAR(23)
                     COLLATE utf8mb4_unicode_ci NOT NULL,
    `addon_id`       VARCHAR(30)
                     CHARACTER SET utf8         NOT NULL,
    `fileid`         INT UNSIGNED               NOT NULL DEFAULT '0',
    `creation_date`  TIMESTAMP                  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `revision`       TINYINT                    NOT NULL DEFAULT '1',
    `format`         TINYINT                    NOT NULL,
    `image`          INT UNSIGNED               NOT NULL DEFAULT '0',
    `status`         MEDIUMINT UNSIGNED         NOT NULL DEFAULT '0',
    `moderator_note` VARCHAR(4096)
                     COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    PRIMARY KEY (`addon_id`, `revision`),
    KEY `status` (`status`),
    CONSTRAINT `v3_arenas_revs_ibfk_1` FOREIGN KEY (`addon_id`) REFERENCES `v3_addons` (`id`)
        ON DELETE CASCADE
        ON UPDATE NO ACTION
)
    ENGINE =InnoDB
    DEFAULT CHARSET =utf8mb4
    COLLATE =utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_tracks_revs`
--
CREATE TABLE IF NOT EXISTS `v3_tracks_revs` (
    `id`             VARCHAR(23)
                     COLLATE utf8mb4_unicode_ci NOT NULL,
    `addon_id`       VARCHAR(30)
                     CHARACTER SET utf8         NOT NULL,
    `fileid`         INT UNSIGNED               NOT NULL DEFAULT '0',
    `creation_date`  TIMESTAMP                  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `revision`       TINYINT                    NOT NULL DEFAULT '1',
    `format`         TINYINT                    NOT NULL,
    `image`          INT UNSIGNED               NOT NULL DEFAULT '0',
    `status`         MEDIUMINT UNSIGNED         NOT NULL DEFAULT '0',
    `moderator_note` VARCHAR(4096)
                     COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    PRIMARY KEY (`addon_id`, `revision`),
    KEY `status` (`status`),
    CONSTRAINT `v3_tracks_revs_ibfk_1` FOREIGN KEY (`addon_id`) REFERENCES `v3_addons` (`id`)
        ON DELETE CASCADE
        ON UPDATE NO ACTION
)
    ENGINE =InnoDB
    DEFAULT CHARSET =utf8mb4
    COLLATE =utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_karts_revs`
--
CREATE TABLE IF NOT EXISTS `v3_karts_revs` (
    `id`             VARCHAR(23)
                     COLLATE utf8mb4_unicode_ci NOT NULL,
    `addon_id`       VARCHAR(30)
                     CHARACTER SET utf8         NOT NULL,
    `fileid`         INT UNSIGNED               NOT NULL DEFAULT '0',
    `creation_date`  TIMESTAMP                  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `revision`       TINYINT                    NOT NULL DEFAULT '1',
    `format`         TINYINT                    NOT NULL,
    `image`          INT UNSIGNED               NOT NULL DEFAULT '0',
    `icon`           INT UNSIGNED               NOT NULL DEFAULT '0',
    `status`         MEDIUMINT UNSIGNED         NOT NULL DEFAULT '0',
    `moderator_note` VARCHAR(4096)
                     COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    PRIMARY KEY (`addon_id`, `revision`),
    KEY `status` (`status`),
    CONSTRAINT `v3_karts_revs_ibfk_1` FOREIGN KEY (`addon_id`) REFERENCES `v3_addons` (`id`)
        ON DELETE CASCADE
        ON UPDATE NO ACTION
)
    ENGINE =InnoDB
    DEFAULT CHARSET =utf8mb4
    COLLATE =utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------------
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

-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_votes`
--
CREATE TABLE IF NOT EXISTS `v3_votes` (
    `user_id`  INT UNSIGNED       NOT NULL,
    `addon_id` VARCHAR(30)
               CHARACTER SET utf8 NOT NULL,
    `vote`     FLOAT UNSIGNED     NOT NULL,
    PRIMARY KEY (`user_id`, `addon_id`),
    KEY `addon_id` (`addon_id`),
    CONSTRAINT `v3_votes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `v3_users` (`id`)
        ON DELETE CASCADE
        ON UPDATE NO ACTION,
    CONSTRAINT `v3_votes_ibfk_2` FOREIGN KEY (`addon_id`) REFERENCES `v3_addons` (`id`)
        ON DELETE CASCADE
        ON UPDATE NO ACTION
)
    ENGINE =InnoDB
    DEFAULT CHARSET =utf8mb4
    COLLATE =utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_bugs`
--
CREATE TABLE IF NOT EXISTS `v3_bugs` (
    `id`           INT UNSIGNED               NOT NULL AUTO_INCREMENT,
    `user_id`      INT UNSIGNED               NOT NULL
    COMMENT 'User who filed the bug report',
    `addon_id`     VARCHAR(30)
                   CHARACTER SET utf8 DEFAULT NULL
    COMMENT 'The bug culprit',
    `close_id`     INT UNSIGNED DEFAULT NULL
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
    `is_report`    BOOL                       NOT NULL DEFAULT '0'
    COMMENT 'Flag to indicate if the bug is a feedback',
    PRIMARY KEY (`id`),
    KEY `addon_id` (`addon_id`),
    CONSTRAINT `v3_bugs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `v3_users` (`id`)
        ON DELETE CASCADE
        ON UPDATE NO ACTION,
    CONSTRAINT `v3_bugs_ibfk_2` FOREIGN KEY (`addon_id`) REFERENCES `v3_addons` (`id`)
        ON DELETE CASCADE
        ON UPDATE NO ACTION
)
    ENGINE =InnoDB
    DEFAULT CHARSET =utf8mb4
    COLLATE =utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_bugs_comments`
--
CREATE TABLE IF NOT EXISTS `v3_bugs_comments` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `bug_id`      INT UNSIGNED NOT NULL
    COMMENT 'The bug we commented on',
    `user_id`     INT UNSIGNED NOT NULL
    COMMENT 'The user who commented',
    `date`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
    COMMENT 'The date it was reported',
    `description` VARCHAR(512)
                  COLLATE utf8mb4_unicode_ci DEFAULT NULL
    COMMENT 'The comment description',
    PRIMARY KEY (`id`),
    KEY `bug_id` (`bug_id`),
    CONSTRAINT `v3_bugs_comments_ibfk_1` FOREIGN KEY (`bug_id`) REFERENCES `v3_bugs` (`id`)
        ON DELETE CASCADE
        ON UPDATE NO ACTION,
    CONSTRAINT `v3_bugs_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `v3_users` (`id`)
        ON DELETE CASCADE
        ON UPDATE NO ACTION
)
    ENGINE =InnoDB
    DEFAULT CHARSET =utf8mb4
    COLLATE =utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------------
-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_clients`
--
CREATE TABLE IF NOT EXISTS `v3_clients` (
    `agent_string` VARCHAR(255)
                   COLLATE utf8mb4_unicode_ci NOT NULL,
    `stk_version`  VARCHAR(64)
                   COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'latest',
    `is_disabled`  BOOL                       NOT NULL DEFAULT '0',
    PRIMARY KEY (`agent_string`(32))
)
    ENGINE =InnoDB
    DEFAULT CHARSET =utf8mb4
    COLLATE =utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------------
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

-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_music`
--
CREATE TABLE IF NOT EXISTS `v3_music` (
    `id`           INT                        NOT NULL AUTO_INCREMENT,
    `title`        VARCHAR(256)
                   COLLATE utf8mb4_unicode_ci NOT NULL,
    `artist`       VARCHAR(256)
                   COLLATE utf8mb4_unicode_ci NOT NULL,
    `license`      VARCHAR(1024)
                   COLLATE utf8mb4_unicode_ci NOT NULL,
    `gain`         FLOAT                      NOT NULL DEFAULT '1',
    `length`       INT                        NOT NULL DEFAULT '0',
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

-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_stats`
--
CREATE TABLE IF NOT EXISTS `v3_stats` (
    `type`  TEXT
            COLLATE utf8mb4_unicode_ci NOT NULL,
    `date`  DATE                       NOT NULL,
    `value` INT UNSIGNED               NOT NULL DEFAULT '0',
    PRIMARY KEY (`date`, `type`(40)),
    KEY `date` (`date`)
)
    ENGINE =InnoDB
    DEFAULT CHARSET =utf8mb4
    COLLATE =utf8mb4_unicode_ci;
