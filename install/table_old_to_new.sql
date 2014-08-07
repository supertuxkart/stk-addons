-- use this for the databas
-- ALTER DATABASE database_name CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- optimize and repair all tables
-- mysqlcheck -u root -p --auto-repair --optimize --all-databases

DROP PROCEDURE IF EXISTS convert_to_utf8;
DROP FUNCTION IF EXISTS table_exists;

DELIMITER $$

CREATE FUNCTION `table_exists`(tname VARCHAR(64))
    RETURNS TINYINT(1)
    BEGIN
        DECLARE TABLE_FOUND VARCHAR(64) DEFAULT '';

        SELECT table_name INTO TABLE_FOUND FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = tname;

        RETURN CHAR_LENGTH(TABLE_FOUND) > 0;
    END;
$$

CREATE PROCEDURE `convert_to_utf8`()
    BEGIN
        IF table_exists('v2_achieved') THEN
            ALTER TABLE `v2_achieved` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

            REPAIR TABLE `v2_achieved`;
            OPTIMIZE TABLE `v2_achieved`;
        END IF;

        IF table_exists('v2_achievements') THEN
            ALTER TABLE `v2_achievements` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

            ALTER TABLE `v2_achievements` CHANGE `name` `name` VARCHAR( 128 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;

            REPAIR TABLE `v2_achievements`;
            OPTIMIZE TABLE `v2_achievements`;
        END IF;

        IF table_exists('v2_addons') THEN
            # TODO add addon idsi
            #ALTER TABLE `v2_addons` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

            ALTER TABLE `v2_addons` CHANGE `type` `type` ENUM( 'karts', 'tracks', 'arenas' ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL ;
            ALTER TABLE `v2_addons` CHANGE `name` `name` TINYTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL ;
            ALTER TABLE `v2_addons` CHANGE `designer` `designer` TINYTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL ;
            ALTER TABLE `v2_addons` CHANGE `description` `description` VARCHAR( 140 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL ;
            ALTER TABLE `v2_addons` CHANGE `license` `license` MEDIUMTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL ;
            ALTER TABLE `v2_addons` CHANGE `min_include_ver` `min_include_ver` VARCHAR( 16 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL ;
            ALTER TABLE `v2_addons` CHANGE `max_include_ver` `max_include_ver` VARCHAR( 16 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL ;

            REPAIR TABLE `v2_addons`;
            OPTIMIZE TABLE `v2_addons`;
        END IF;

        IF table_exists('v2_arenas_revs') THEN
            ALTER TABLE `v2_achieved` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

            ALTER TABLE `v2_arenas_revs` CHANGE `id` `id` VARCHAR( 23 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL ;
            ALTER TABLE `v2_arenas_revs` CHANGE `moderator_note` `moderator_note` VARCHAR( 4096 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL ;

            REPAIR TABLE `v2_arenas_revs`;
            OPTIMIZE TABLE `v2_arenas_revs`;
        END IF;

        IF table_exists('v2_bugs') THEN
            #ALTER TABLE `v2_bugs` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
            ALTER TABLE `v2_bugs` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

            ALTER TABLE `v2_bugs` CHANGE `close_reason` `close_reason` VARCHAR( 512 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'The reason it was closed';
            ALTER TABLE `v2_bugs` CHANGE `title` `title` VARCHAR( 256 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Bug title';
            ALTER TABLE `v2_bugs` CHANGE `description` `description` VARCHAR( 2048 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Bug description';

            REPAIR TABLE `v2_bugs`;
            OPTIMIZE TABLE `v2_bugs`;
        END IF;

        IF table_exists('v2_bugs_comments') THEN
            ALTER TABLE `v2_bugs_comments` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

            ALTER TABLE `v2_bugs_comments` CHANGE `description` `description` VARCHAR( 2048 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'The comment description';

            REPAIR TABLE `v2_bugs_comments`;
            OPTIMIZE TABLE `v2_bugs_comments`;
        END IF;


        IF table_exists('v2_cache') THEN
            ALTER TABLE `v2_cache` ENGINE = InnoDB;
            ALTER TABLE `v2_cache` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

            ALTER TABLE `v2_cache` CHANGE `file` `file` VARCHAR( 128 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL ;
            ALTER TABLE `v2_cache` CHANGE `addon` `addon` VARCHAR( 30 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL ;
            ALTER TABLE `v2_cache` CHANGE `props` `props` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL ;

            REPAIR TABLE `v2_cache`;
            OPTIMIZE TABLE `v2_cache`;
        END IF;

        IF table_exists('v2_clients') THEN
            ALTER TABLE `v2_clients` ENGINE = InnoDB;
            ALTER TABLE `v2_clients` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

            ALTER TABLE `v2_clients` CHANGE `agent_string` `agent_string` VARCHAR( 256 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL ;
            ALTER TABLE `v2_clients` CHANGE `stk_version` `stk_version` VARCHAR( 64 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'latest';

            REPAIR TABLE `v2_clients`;
            OPTIMIZE TABLE `v2_clients`;
        END IF;

        IF table_exists('v2_client_sessions') THEN
            ALTER TABLE `v2_client_sessions` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

            ALTER TABLE `v2_client_sessions` CHANGE `cid` `cid` CHAR( 24 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL ;

            REPAIR TABLE `v2_client_sessions`;
            OPTIMIZE TABLE `v2_client_sessions`;
        END IF;

        # TODO add config

        IF table_exists('v2_files') THEN
            ALTER TABLE `v2_files` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

            ALTER TABLE `v2_files` CHANGE `addon_type` `addon_type` ENUM( 'karts', 'tracks', 'arenas' ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL ;
            ALTER TABLE `v2_files` CHANGE `file_type` `file_type` ENUM( 'source', 'image', 'addon' ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL ;
            ALTER TABLE `v2_files` CHANGE `file_path` `file_path` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL ;

            REPAIR TABLE `v2_files`;
            OPTIMIZE TABLE `v2_files`;
        END IF;

        IF table_exists('v2_friends') THEN
            ALTER TABLE `v2_friends` ENGINE = InnoDB;
            ALTER TABLE `v2_friends` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

            ALTER TABLE `v2_friends`
            ADD CONSTRAINT `v2_friends_ibfk_1` FOREIGN KEY (`asker_id`) REFERENCES `v2_users` (`id`)
                ON DELETE CASCADE
                ON UPDATE NO ACTION,
            ADD CONSTRAINT `v2_friends_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `v2_users` (`id`)
                ON DELETE CASCADE
                ON UPDATE NO ACTION;

            ALTER TABLE `v2_friends` CHANGE `date` `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ;

            REPAIR TABLE `v2_friends`;
            OPTIMIZE TABLE `v2_friends`;
        END IF;

        IF table_exists('v2_host_votes') THEN
            ALTER TABLE `v2_host_votes` ENGINE = InnoDB;
            ALTER TABLE `v2_host_votes` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

            REPAIR TABLE `v2_host_votes`;
            OPTIMIZE TABLE `v2_host_votes`;
        END IF;

        IF table_exists('v2_karts_revs') THEN
            #ALTER TABLE `v2_karts_revs` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
            ALTER TABLE `v2_karts_revs` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

            ALTER TABLE `v2_karts_revs` CHANGE `id` `id` VARCHAR( 23 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL ;
            ALTER TABLE `v2_karts_revs` CHANGE `moderator_note` `moderator_note` VARCHAR( 4096 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL ;

            REPAIR TABLE `v2_karts_revs`;
            OPTIMIZE TABLE `v2_karts_revs`;
        END IF;

        IF table_exists('v2_logs') THEN
            ALTER TABLE `v2_logs` ENGINE = InnoDB;
            ALTER TABLE `v2_logs` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

            ALTER TABLE `v2_logs` CHANGE `message` `message` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL ;

            REPAIR TABLE `v2_logs`;
            OPTIMIZE TABLE `v2_logs`;
        END IF;

        IF table_exists('v2_music') THEN
            ALTER TABLE `v2_music` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

            ALTER TABLE `v2_music` CHANGE `title` `title` VARCHAR( 256 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL ;
            ALTER TABLE `v2_music` CHANGE `artist` `artist` VARCHAR( 256 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL ;
            ALTER TABLE `v2_music` CHANGE `license` `license` VARCHAR( 1024 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL ;
            ALTER TABLE `v2_music` CHANGE `file` `file` VARCHAR( 191 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL ;
            ALTER TABLE `v2_music` CHANGE `file_md5` `file_md5` CHAR( 32 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL ;
            ALTER TABLE `v2_music` CHANGE `xml_filename` `xml_filename` VARCHAR( 191 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL ;

            REPAIR TABLE `v2_music`;
            OPTIMIZE TABLE `v2_music`;
        END IF;

        IF table_exists('v2_news') THEN
            ALTER TABLE `v2_news` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

            ALTER TABLE `v2_news` CHANGE `content` `content` VARCHAR( 256 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL ;
            ALTER TABLE `v2_news` CHANGE `condition` `condition` VARCHAR( 256 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL ;

            REPAIR TABLE `v2_news`;
            OPTIMIZE TABLE `v2_news`;
        END IF;

        IF table_exists('v2_notifications') THEN
            ALTER TABLE `v2_notifications` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

            ALTER TABLE `v2_notifications` CHANGE `type` `type` VARCHAR( 16 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL ;

            REPAIR TABLE `v2_notifications`;
            OPTIMIZE TABLE `v2_notifications`;
        END IF;

        IF table_exists('v2_roles') THEN
            ALTER TABLE `v2_roles` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

            ALTER TABLE `v2_roles` CHANGE `name` `name` VARCHAR( 256 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'The name identifier';

            REPAIR TABLE `v2_roles`;
            OPTIMIZE TABLE `v2_roles`;
        END IF;

        IF table_exists('v2_role_permissions') THEN
            ALTER TABLE `v2_role_permissions` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

            ALTER TABLE `v2_role_permissions` CHANGE `permission` `permission` VARCHAR( 256 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'The actual permission';

            REPAIR TABLE `v2_role_permissions`;
            OPTIMIZE TABLE `v2_role_permissions`;
        END IF;

        IF table_exists('v2_servers') THEN
            ALTER TABLE `v2_servers` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

            ALTER TABLE `v2_servers` CHANGE `name` `name` TINYTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL ;

            REPAIR TABLE `v2_servers`;
            OPTIMIZE TABLE `v2_servers`;
        END IF;

        IF table_exists('v2_server_conn') THEN
            ALTER TABLE `v2_server_conn` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

            REPAIR TABLE `v2_server_conn`;
            OPTIMIZE TABLE `v2_server_conn`;
        END IF;

        IF table_exists('v2_stats') THEN
            ALTER TABLE `v2_stats` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

            ALTER TABLE `v2_stats` CHANGE `type` `type` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL ;

            REPAIR TABLE `v2_stats`;
            OPTIMIZE TABLE `v2_stats`;
        END IF;

        IF table_exists('v2_tracks_revs') THEN
            #ALTER TABLE `v2_tracks_revs` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
            ALTER TABLE `v2_karts_revs` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

            ALTER TABLE `v2_tracks_revs` CHANGE `id` `id` VARCHAR( 23 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL ;
            ALTER TABLE `v2_tracks_revs` CHANGE `moderator_note` `moderator_note` VARCHAR( 4096 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL ;

            REPAIR TABLE `v2_tracks_revs`;
            OPTIMIZE TABLE `v2_tracks_revs`;
        END IF;

        IF table_exists('v2_users') THEN
            ALTER TABLE `v2_users` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

            ALTER TABLE `v2_users` CHANGE `user` `user` TINYTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL ;
            ALTER TABLE `v2_users` CHANGE `pass` `pass` CHAR( 96 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL ;
            ALTER TABLE `v2_users` CHANGE `name` `name` TINYTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL ;
            ALTER TABLE `v2_users` CHANGE `role` `role` TINYTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL ;
            ALTER TABLE `v2_users` CHANGE `email` `email` TINYTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL ;
            ALTER TABLE `v2_users` CHANGE `homepage` `homepage` VARCHAR( 64 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL ;
            ALTER TABLE `v2_users` CHANGE `avatar` `avatar` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL ;

            REPAIR TABLE `v2_users`;
            OPTIMIZE TABLE `v2_users`;
        END IF;

        IF table_exists('v2_verification') THEN
            ALTER TABLE `v2_verification` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

            ALTER TABLE `v2_verification` CHANGE `code` `code` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'The verification code';

            REPAIR TABLE `v2_verification`;
            OPTIMIZE TABLE `v2_verification`;
        END IF;

        IF table_exists('v2_votes') THEN
            ALTER TABLE `v2_votes` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

            REPAIR TABLE `v2_votes`;
            OPTIMIZE TABLE `v2_votes`;
        END IF;
    END;
$$

DELIMITER ;

CALL convert_to_utf8();

DROP PROCEDURE IF EXISTS convert_to_utf8;
DROP FUNCTION IF EXISTS table_exists;
