SET NAMES utf8mb4;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+00:00';

DELIMITER $$
--
-- Procedures
--
DROP PROCEDURE IF EXISTS v3_create_file_record$$
CREATE PROCEDURE `v3_create_file_record`(IN id TEXT, IN ftype INT, IN fname TEXT, OUT insertid INT)
    BEGIN
        INSERT INTO `v3_files`
        (`addon_id`, `type`, `path`)
        VALUES
            (id, ftype, fname);
        SELECT LAST_INSERT_ID()
        INTO insertid;
    END$$

DELIMITER ;

-- --------------------------------------------------------------------------------
-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_roles`
--
CREATE TABLE IF NOT EXISTS `v3_roles` (
    `id`   INT UNSIGNED NOT NULL AUTO_INCREMENT
    COMMENT 'The role unique identifier',
    `name` VARCHAR(128) NOT NULL
    COMMENT 'The name identifier',
    PRIMARY KEY (`id`),
    UNIQUE KEY `key_name` (`name`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci
    AUTO_INCREMENT = 4;

--
-- Dumping data for table `v3_roles`
--
INSERT INTO `v3_roles` (`id`, `name`) VALUES
    (1, 'user'),
    (2, 'moderator'),
    (3, 'admin'),
    (4, 'server_hoster')
ON DUPLICATE KEY UPDATE `id` = VALUES(`id`), `name` = VALUES(`name`);

-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_role_permissions`
--
CREATE TABLE IF NOT EXISTS `v3_role_permissions` (
    `role_id`    INT UNSIGNED NOT NULL
    COMMENT 'The id from the roles table',
    `permission` VARCHAR(128) NOT NULL
    COMMENT 'The actual permission',
    PRIMARY KEY (`role_id`, `permission`),
    CONSTRAINT `v3_role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `v3_roles` (`id`)
        ON DELETE CASCADE
        ON UPDATE NO ACTION
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

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
    (2, 'submit_rankings'),
    (3, 'view_basic_page'),
    (3, 'add_addon'),
    (3, 'add_bug'),
    (3, 'add_bug_comment'),
    (3, 'edit_addons'),
    (3, 'edit_bugs'),
    (3, 'edit_users'),
    (3, 'edit_settings'),
    (3, 'edit_permissions'),
    (3, 'edit_admins'),
    (3, 'submit_rankings'),
    (4, 'view_basic_page'),
    (4, 'add_addon'),
    (4, 'add_bug'),
    (4, 'add_bug_comment'),
    (4, 'official_servers'),
    (4, 'submit_rankings')

ON DUPLICATE KEY UPDATE `role_id` = VALUES(`role_id`), `permission` = VALUES(`permission`);

-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_users`
--
CREATE TABLE IF NOT EXISTS `v3_users` (
    `id`            INT UNSIGNED             NOT NULL AUTO_INCREMENT,
    `role_id`       INT UNSIGNED DEFAULT '1' NOT NULL,
    `username`      VARCHAR(30)
                    CHARACTER SET ascii      NOT NULL,
    `password`      CHAR(96)                 NOT NULL,
    `realname`      VARCHAR(64)              NOT NULL,
    `email`         VARCHAR(64)              NOT NULL,
    `is_active`     BOOL                     NOT NULL DEFAULT '0',
    `date_login`    TIMESTAMP                NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `date_register` DATE                     NOT NULL,
    `homepage`      VARCHAR(64)                       DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `key_username` (`username`),
    UNIQUE KEY `key_email` (`email`),
    KEY `key_role_id` (`role_id`),
    CONSTRAINT `v3_users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `v3_roles` (`id`)
        ON DELETE RESTRICT
        ON UPDATE NO ACTION
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_verification`
--
CREATE TABLE IF NOT EXISTS `v3_verification` (
    `user_id` INT UNSIGNED NOT NULL DEFAULT '0',
    `code`    VARCHAR(32)  NOT NULL
    COMMENT 'The verification code',
    PRIMARY KEY (`user_id`),
    CONSTRAINT `v3_verification_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `v3_users` (`id`)
        ON DELETE CASCADE
        ON UPDATE NO ACTION
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci
    COMMENT = 'Account activation and recovery codes';

-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_achievements`
--
CREATE TABLE IF NOT EXISTS `v3_achievements` (
    `id`   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(128) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `key_name` (`name`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci
    COMMENT = 'Keep track of all achievements (see install/get-achievements.php)';

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
    (10, 'It''s secret'),
    (11, 'Mosquito Hunter')
ON DUPLICATE KEY UPDATE `id` = VALUES(`id`), `name` = VALUES(`name`);

-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_achieved`
--
CREATE TABLE IF NOT EXISTS `v3_achieved` (
    `user_id`        INT UNSIGNED NOT NULL,
    `achievement_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`user_id`, `achievement_id`),
    KEY `key_user_id` (`user_id`),
    KEY `key_achievement_id` (`achievement_id`),
    CONSTRAINT `v3_achieved_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `v3_users` (`id`)
        ON DELETE CASCADE
        ON UPDATE NO ACTION,
    CONSTRAINT `v3_achieved_ibfk_2` FOREIGN KEY (`achievement_id`) REFERENCES `v3_achievements` (`id`)
        ON DELETE CASCADE
        ON UPDATE NO ACTION
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci
    COMMENT = 'The achievements of each user';

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
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_notifications`
--
CREATE TABLE IF NOT EXISTS `v3_notifications` (
    `to`   INT UNSIGNED NOT NULL,
    `from` INT UNSIGNED NOT NULL,
    `type` VARCHAR(16)  NOT NULL,
    PRIMARY KEY (`to`, `from`, `type`),
    KEY `key_to` (`to`),
    KEY `key_from` (`from`),
    CONSTRAINT `v3_notifications_ibfk_1` FOREIGN KEY (`to`) REFERENCES `v3_users` (`id`)
        ON DELETE CASCADE
        ON UPDATE NO ACTION,
    CONSTRAINT `v3_notifications_ibfk_2` FOREIGN KEY (`from`) REFERENCES `v3_users` (`id`)
        ON DELETE CASCADE
        ON UPDATE NO ACTION
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_logs`
--
CREATE TABLE IF NOT EXISTS `v3_logs` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`    INT UNSIGNED          DEFAULT NULL,
    `date`       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `message`    TEXT         NOT NULL,
    `is_emailed` BOOL         NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY `key_user_id` (`user_id`),
    CONSTRAINT `v3_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `v3_users` (`id`)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_news`
--
CREATE TABLE IF NOT EXISTS `v3_news` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `author_id`      INT UNSIGNED          DEFAULT NULL,
    `date`           TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `content`        VARCHAR(256)          DEFAULT NULL,
    `condition`      VARCHAR(256)          DEFAULT NULL,
    `is_important`   BOOL         NOT NULL DEFAULT '0',
    `is_web_display` BOOL         NOT NULL DEFAULT '1',
    `is_active`      BOOL         NOT NULL DEFAULT '1',
    `is_dynamic`     BOOL         NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY `key_author_id` (`author_id`),
    CONSTRAINT `v3_news_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `v3_users` (`id`)
        ON DELETE SET NULL
        ON UPDATE NO ACTION
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_client_sessions`
--
CREATE TABLE IF NOT EXISTS `v3_client_sessions` (
    `uid`          INT UNSIGNED      NOT NULL,
    `cid`          CHAR(24)          NOT NULL,
    `is_online`    BOOL              NOT NULL DEFAULT '1',
    `is_save`      BOOL              NOT NULL DEFAULT '0',
    `ip`           INT UNSIGNED      NOT NULL DEFAULT '0',
    `private_port` SMALLINT UNSIGNED NOT NULL DEFAULT '0',
    `port`         SMALLINT UNSIGNED NOT NULL DEFAULT '0',
    `last-online`  TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`uid`),
    UNIQUE KEY `key_session` (`uid`, `cid`)
)
    ENGINE = MEMORY
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_servers`
--
CREATE TABLE IF NOT EXISTS `v3_servers` (
    `id`              INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    `host_id`         INT UNSIGNED      NOT NULL,
    `name`            VARCHAR(64)       NOT NULL,
    `last_poll_time`  INT               NOT NULL,
    `ip`              INT UNSIGNED      NOT NULL DEFAULT '0',
    `port`            SMALLINT UNSIGNED NOT NULL DEFAULT '0',
    `private_port`    SMALLINT UNSIGNED NOT NULL DEFAULT '0',
    `max_players`     TINYINT UNSIGNED  NOT NULL DEFAULT '0',
    `difficulty`      TINYINT UNSIGNED  NOT NULL DEFAULT '0',
    `game_mode`       TINYINT UNSIGNED  NOT NULL DEFAULT '0',
    `current_players` TINYINT UNSIGNED  NOT NULL DEFAULT '0',
    `password`        TINYINT UNSIGNED  NOT NULL DEFAULT '0',
    `version`         TINYINT UNSIGNED  NOT NULL DEFAULT '1',
    `latitude`        FLOAT             NOT NULL DEFAULT '0.0',
    `longitude`       FLOAT             NOT NULL DEFAULT '0.0'
    COMMENT 'Isn''t exact. Just to show in the server-list, where it doesn''t need to be exact.',
    PRIMARY KEY (`id`),
    KEY `key_hostid` (`host_id`),
    CONSTRAINT `v3_servers_ibfk_1` FOREIGN KEY (`host_id`) REFERENCES `v3_users` (`id`)
        ON DELETE CASCADE
        ON UPDATE NO ACTION
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_server_conn`
--
CREATE TABLE IF NOT EXISTS `v3_server_conn` (
    `user_id`    INT UNSIGNED NOT NULL,
    `server_id`  INT UNSIGNED NOT NULL,
    `is_request` BOOL         NOT NULL DEFAULT '1',
    PRIMARY KEY (`user_id`),
    KEY `key_server_id` (`server_id`),
    CONSTRAINT `v3_server_conn_ibfk_1` FOREIGN KEY (`server_id`) REFERENCES `v3_servers` (`id`)
        ON DELETE CASCADE
        ON UPDATE NO ACTION,
    CONSTRAINT `v3_server_conn_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `v3_users` (`id`)
        ON DELETE CASCADE
        ON UPDATE NO ACTION
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_host_votes`
--
CREATE TABLE IF NOT EXISTS `v3_host_votes` (
    `user_id` INT UNSIGNED NOT NULL,
    `host_id` INT UNSIGNED NOT NULL,
    `vote`    INT          NOT NULL,
    PRIMARY KEY (`user_id`, `host_id`),
    KEY `key_hostid` (`host_id`),
    CONSTRAINT `v3_host_votes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `v3_users` (`id`)
        ON DELETE CASCADE
        ON UPDATE NO ACTION
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------------
-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_addon_types`
--
CREATE TABLE IF NOT EXISTS `v3_addon_types` (
    `type`          INT UNSIGNED NOT NULL,
    `name_singular` VARCHAR(30)  NOT NULL,
    `name_plural`   VARCHAR(30)  NOT NULL,
    PRIMARY KEY (`type`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

--
-- Dumping data for table `v3_addon_types`
--
INSERT INTO `v3_addon_types` (`type`, `name_singular`, `name_plural`) VALUES
    (1, 'kart', 'karts'),
    (2, 'track', 'tracks'),
    (3, 'arena', 'arenas')
ON DUPLICATE KEY UPDATE `type` = VALUES(`type`), `name_singular` = VALUES(`name_singular`),
    `name_plural`              = VALUES(`name_plural`);

-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_addons`
--
CREATE TABLE IF NOT EXISTS `v3_addons` (
    `id`              VARCHAR(30)  NOT NULL,
    `type`            INT UNSIGNED NOT NULL,
    `name`            VARCHAR(64)  NOT NULL,
    `uploader`        INT UNSIGNED          DEFAULT NULL,
    `creation_date`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `designer`        VARCHAR(64)  NOT NULL,
    `props`           INT UNSIGNED NOT NULL DEFAULT '0',
    `description`     VARCHAR(140)          DEFAULT NULL,
    `license`         TEXT,
    `min_include_ver` VARCHAR(16)           DEFAULT NULL,
    `max_include_ver` VARCHAR(16)           DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `key_uploader` (`uploader`),
    KEY `key_type` (`type`),
    CONSTRAINT `v3_addons_ibfk_1` FOREIGN KEY (`uploader`) REFERENCES `v3_users` (`id`)
        ON DELETE SET NULL
        ON UPDATE NO ACTION,
    CONSTRAINT `v3_addons_ibfk_2` FOREIGN KEY (`type`) REFERENCES `v3_addon_types` (`type`)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_file_types`
--
CREATE TABLE IF NOT EXISTS `v3_file_types` (
    `type` INT UNSIGNED NOT NULL,
    `name` VARCHAR(30)  NOT NULL,
    PRIMARY KEY (`type`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

--
-- Dumping data for table `v3_file_types`
--
INSERT INTO `v3_file_types` (`type`, `name`) VALUES
    (1, 'image'),
    (2, 'source'),
    (3, 'addon')
ON DUPLICATE KEY UPDATE `type` = VALUES(`type`), `name` = VALUES(`name`);

-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_files`
--
CREATE TABLE IF NOT EXISTS `v3_files` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `addon_id`    VARCHAR(30)  NOT NULL,
    `type`        INT UNSIGNED NOT NULL,
    `path`        VARCHAR(256) NOT NULL,
    `date_added`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `is_approved` BOOL         NOT NULL DEFAULT '0',
    `downloads`   INT UNSIGNED NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY `key_addon_id` (`addon_id`),
    KEY `key_type` (`type`),
    CONSTRAINT `v3_files_ibfk_1` FOREIGN KEY (`addon_id`) REFERENCES `v3_addons` (`id`)
        ON DELETE CASCADE
        ON UPDATE NO ACTION,
    CONSTRAINT `v3_files_ibfk_2` FOREIGN KEY (`type`) REFERENCES `v3_file_types` (`type`)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_files_delete`
--
CREATE TABLE IF NOT EXISTS `v3_files_delete` (
    `file_id`     INT UNSIGNED NOT NULL,
    `date_delete` DATE         NOT NULL DEFAULT '0000-00-00',
    PRIMARY KEY (`file_id`),
    CONSTRAINT `v3_files_delete_ibfk_1` FOREIGN KEY (`file_id`) REFERENCES `v3_files` (`id`)
        ON DELETE CASCADE
        ON UPDATE NO ACTION
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;


-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_addon_revisions`
--
CREATE TABLE IF NOT EXISTS `v3_addon_revisions` (
    `addon_id`       VARCHAR(30)        NOT NULL,
    `file_id`        INT UNSIGNED       NULL     DEFAULT NULL,
    `creation_date`  TIMESTAMP          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `revision`       TINYINT UNSIGNED   NOT NULL DEFAULT '1',
    `format`         TINYINT UNSIGNED   NOT NULL,
    `image_id`       INT UNSIGNED       NULL     DEFAULT NULL,
    `icon_id`        INT UNSIGNED       NULL     DEFAULT NULL,
    `status`         MEDIUMINT UNSIGNED NOT NULL DEFAULT '0',
    `moderator_note` VARCHAR(4096)               DEFAULT NULL,
    PRIMARY KEY (`addon_id`, `revision`),
    KEY `key_status` (`status`),
    KEY `key_addon_id` (`addon_id`),
    KEY `key_file_id` (`file_id`),
    KEY `key_image_id` (`image_id`),
    KEY `key_icon_id` (`icon_id`),
    CONSTRAINT `v3_addon_revisions_ibfk_1` FOREIGN KEY (`addon_id`) REFERENCES `v3_addons` (`id`)
        ON DELETE CASCADE
        ON UPDATE NO ACTION,
    CONSTRAINT `v3_addon_revisions_ibfk_2` FOREIGN KEY (`file_id`) REFERENCES `v3_files` (`id`)
        ON DELETE SET NULL
        ON UPDATE NO ACTION,
    CONSTRAINT `v3_addon_revisions_ibfk_3` FOREIGN KEY (`image_id`) REFERENCES `v3_files` (`id`)
        ON DELETE SET NULL
        ON UPDATE NO ACTION,
    CONSTRAINT `v3_addon_revisions_ibfk_4` FOREIGN KEY (`icon_id`) REFERENCES `v3_files` (`id`)
        ON DELETE SET NULL
        ON UPDATE NO ACTION
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_cache`
--
CREATE TABLE IF NOT EXISTS `v3_cache` (
    `file`     VARCHAR(128) NOT NULL,
    `addon_id` VARCHAR(30) DEFAULT NULL,
    `props`    VARCHAR(256),
    PRIMARY KEY (`file`),
    KEY `key_addon_id` (`addon_id`),
    CONSTRAINT `v3_cache_ibfk_1` FOREIGN KEY (`addon_id`) REFERENCES `v3_addons` (`id`)
        ON DELETE CASCADE
        ON UPDATE NO ACTION
)
    ENGINE = InnoDB
    CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_votes`
--
CREATE TABLE IF NOT EXISTS `v3_votes` (
    `user_id`  INT UNSIGNED   NOT NULL,
    `addon_id` VARCHAR(30)    NOT NULL,
    `vote`     FLOAT UNSIGNED NOT NULL,
    PRIMARY KEY (`user_id`, `addon_id`),
    KEY `key_addon_id` (`addon_id`),
    CONSTRAINT `v3_votes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `v3_users` (`id`)
        ON DELETE CASCADE
        ON UPDATE NO ACTION,
    CONSTRAINT `v3_votes_ibfk_2` FOREIGN KEY (`addon_id`) REFERENCES `v3_addons` (`id`)
        ON DELETE CASCADE
        ON UPDATE NO ACTION
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_bugs`
--
CREATE TABLE IF NOT EXISTS `v3_bugs` (
    `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `user_id`      INT UNSIGNED  NOT NULL
    COMMENT 'User who filed the bug report',
    `addon_id`     VARCHAR(30)   NOT NULL
    COMMENT 'The bug culprit',
    `close_id`     INT UNSIGNED           DEFAULT '0'
    COMMENT 'The user who closed the bug',
    `close_reason` VARCHAR(512)           DEFAULT NULL
    COMMENT 'The reason it was closed',
    `date_report`  TIMESTAMP     NULL     DEFAULT NULL
    COMMENT 'Report date',
    `date_edit`    TIMESTAMP     NULL     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last edit date',
    `date_close`   TIMESTAMP     NULL     DEFAULT NULL
    COMMENT 'Close date',
    `title`        VARCHAR(64)   NOT NULL
    COMMENT 'Bug title',
    `description`  VARCHAR(1024) NOT NULL
    COMMENT 'Bug description',
    `is_report`    BOOL          NOT NULL DEFAULT '0'
    COMMENT 'Flag to indicate if the bug is a feedback',
    PRIMARY KEY (`id`),
    KEY `key_user_id` (`user_id`),
    KEY `key_addon_id` (`addon_id`),
    CONSTRAINT `v3_bugs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `v3_users` (`id`)
        ON DELETE CASCADE
        ON UPDATE NO ACTION,
    CONSTRAINT `v3_bugs_ibfk_2` FOREIGN KEY (`addon_id`) REFERENCES `v3_addons` (`id`)
        ON DELETE CASCADE
        ON UPDATE NO ACTION
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

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
    `description` VARCHAR(512)          DEFAULT NULL
    COMMENT 'The comment description',
    PRIMARY KEY (`id`),
    KEY `key_bug_id` (`bug_id`),
    KEY `key_user_id` (`user_id`),
    CONSTRAINT `v3_bugs_comments_ibfk_1` FOREIGN KEY (`bug_id`) REFERENCES `v3_bugs` (`id`)
        ON DELETE CASCADE
        ON UPDATE NO ACTION,
    CONSTRAINT `v3_bugs_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `v3_users` (`id`)
        ON DELETE CASCADE
        ON UPDATE NO ACTION
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------------
-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_clients`
--
CREATE TABLE IF NOT EXISTS `v3_clients` (
    `agent_string` VARCHAR(255) NOT NULL,
    `stk_version`  VARCHAR(64)  NOT NULL DEFAULT 'latest',
    `is_disabled`  BOOL         NOT NULL DEFAULT '0',
    PRIMARY KEY (`agent_string`(32))
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_config`
--
CREATE TABLE IF NOT EXISTS `v3_config` (
    `name`  VARCHAR(128) NOT NULL,
    `value` VARCHAR(512) NOT NULL,
    PRIMARY KEY (`name`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

--
-- Dumping data for table `v3_config`
--
INSERT INTO `v3_config` (`name`, `value`) VALUES
    ('allowed_addon_exts', 'b3d, bz2, gz, jpeg, jpg, music, ogg, png, spm, tar, tar.bz2, tar.gz, tbz, tgz, txt, xml, zip'),
    ('allowed_source_exts', 'b3d, blend, jpeg, jpg, music, ogg, png, rgb, spm, svg, txt, xcf, xml'),
    ('max_image_dimension', '2048'),
    ('blog_feed', 'http://supertuxkart.blogspot.com/feeds/posts/default')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `value` = VALUES(`value`);

-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_music`
--
CREATE TABLE IF NOT EXISTS `v3_music` (
    `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `title`        VARCHAR(256)  NOT NULL,
    `artist`       VARCHAR(256)  NOT NULL,
    `license`      VARCHAR(1024) NOT NULL,
    `gain`         FLOAT         NOT NULL DEFAULT '1',
    `length`       INT UNSIGNED  NOT NULL DEFAULT '0',
    `file`         VARCHAR(191)  NOT NULL,
    `file_md5`     CHAR(32)      NOT NULL,
    `xml_filename` VARCHAR(191)  NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `key_file` (`file`),
    UNIQUE KEY `key_xml_filename` (`xml_filename`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------------
--
-- Table structure for table `v3_stats`
--
CREATE TABLE IF NOT EXISTS `v3_stats` (
    `type`  VARCHAR(256) NOT NULL,
    `date`  DATE         NOT NULL,
    `value` INT UNSIGNED NOT NULL DEFAULT '0',
    PRIMARY KEY (`date`, `type`(40)),
    KEY `key_date` (`date`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------------
--
-- Table structure for ip to latitude and longitude map `v3_ipv4_mapping`
-- see tools/generate-ip-mappings.py for generation
--
CREATE TABLE IF NOT EXISTS `v3_ipv4_mapping` (
    `ip_start`  INT UNSIGNED NOT NULL,
    `ip_end`    INT UNSIGNED NOT NULL,
    `latitude`  FLOAT        NOT NULL DEFAULT '0.0',
    `longitude` FLOAT        NOT NULL DEFAULT '0.0',
    PRIMARY KEY (`ip_start`),
    UNIQUE KEY `ip_start` (`ip_start`),
    UNIQUE KEY `ip_end` (`ip_end`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------------
--
-- Table structure for player ranking scores `v3_rankings`
--
CREATE TABLE IF NOT EXISTS `v3_rankings` (
    `user_id`        INT UNSIGNED NOT NULL,
    `scores`         DOUBLE NOT NULL,
    `max_scores`     DOUBLE NOT NULL,
    `num_races_done` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`user_id`),
    KEY `scores` (`scores`),
    CONSTRAINT `v3_rankings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `v3_users` (`id`)
        ON DELETE CASCADE
        ON UPDATE NO ACTION
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;
