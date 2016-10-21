<?php
/**
 * Copyright 2015 - 2016  Daniel Butum <danibutum at gmail dot com>
 *
 * This file is part of stk-addons.
 *
 * stk-addons is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * stk-addons is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with stkaddons. If not, see <http://www.gnu.org/licenses/>.
 */

if (php_sapi_name() !== 'cli') exit('Not in CLI Mode');

// Prevent against disaster!!!
error_reporting(-1);
function exception_error_handler($severity, $message, $file, $line)
{
    if (!(error_reporting() & $severity))
    {
        // This error code is not included in error_reporting
        return;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
}

set_error_handler("exception_error_handler");
assert_options(ASSERT_ACTIVE, true);
assert_options(ASSERT_BAIL, true);

define('CRON_MODE', true);
require('../config.php');

$doc = <<<DOC
 Script to convert the stk-addons from version 2 to version 3 of the database.
 Database version 2 can be found in the branch `db-v2` (https://github.com/supertuxkart/stk-addons/branches)
 The tables for version 3 must be freshly created by running the `install.sql` on the database
\033[91m
--------------------------------------------------------------------------------------------------
WARNING!!! WARNING!!! WARNING!!! WARNING!!! WARNING!!! WARNING!!! WARNING!!! WARNING!!! WARNING!!!
WILL DELETE DATA FROM `v3_` TABLES. USE WITH CAUTION!!!
---------------------------------------------------------------------------------------------
\033[0m

DOC;


class Convert
{
    /**
     * @var DBConnection
     */
    public static $db;

    /**
     * @var string
     */
    private static $from_prefix;

    /**
     * @var string
     */
    private static $to_prefix;

    /**
     * @param string $from_prefix
     * @param string $to_prefix
     */
    public static function init($from_prefix, $to_prefix)
    {
        static::$db = DBConnection::get();
        static::$from_prefix = $from_prefix;
        static::$to_prefix = $to_prefix;
    }

    /**
     * @param string $question
     *
     * @return bool
     */
    public static function get_prompt_answer($question)
    {
        $response = strtolower(trim(readline(sprintf('%s [Y/N] ', $question))));
        if (in_array($response, ['y', 'yes']))
        {
            return true;
        }

        return false;
    }

    /**
     * @param string    $message
     * @param bool|true $newline
     * @param string    $color
     */
    public static function echo_color($message, $newline = true, $color)
    {
        echo $color . $message . "\033[0m" . ($newline ? "\n" : "");
    }

    /**
     * @param string    $message
     * @param bool|true $newline
     */
    public static function echo_error($message, $newline = true)
    {
        static::echo_color($message, $newline, "\033[91m");
    }

    /**
     * @param string    $message
     * @param bool|true $newline
     */
    public static function echo_success($message, $newline = true)
    {
        static::echo_color($message, $newline, "\033[92m");
    }

    /**
     * @param string    $message
     * @param bool|true $newline
     */
    public static function echo_warning($message, $newline = true)
    {
        static::echo_color($message, $newline, "\033[93m");
    }

    /**
     * @param string    $message
     * @param bool|true $newline
     */
    public static function exit_error($message, $newline = true)
    {
        static::echo_error($message, $newline);
        exit();
    }

    /**
     * @param int $times
     */
    public static function echo_newline($times = 1)
    {
        echo join('', array_fill(0, $times, "\n"));
    }

    /**
     * Count the number of rows in a table
     *
     * @param $table string
     *
     * @return int
     */
    public static function count_table($table)
    {
        return (int)static::$db->query("SELECT COUNT(*) FROM $table", DBConnection::FETCH_FIRST_COLUMN);
    }

    /**
     * Copy from version 2 to version 3 with a one to one mapping
     *
     * @param $from_table      string
     * @param $to_table        string
     * @param $insert_sql      string
     * @param $check_integrity bool
     *
     * @return array
     */
    public static function copy_table_one_to_one_sql($from_table, $to_table, $insert_sql, $check_integrity = true)
    {
        echo "Copying $from_table to $to_table ";
        $count_v2 = $count_v3 = -1;
        try
        {
            $count_v2 = static::count_table($from_table);
            assert(static::$db->beginTransaction());
            static::$db->query($insert_sql);
            assert(static::$db->commit());
            $count_v3 = static::count_table($to_table);

            if ($check_integrity && $count_v2 !== $count_v3)
            {
                throw new Exception(
                    sprintf(
                        'Integrity check failed, %s != %s, %d != %d',
                        static::$from_prefix,
                        static::$to_prefix,
                        $count_v2,
                        $count_v3
                    )
                );
            }
        }
        catch (Exception $e)
        {
            static::exit_error("\n" . $e->getMessage());
        }
        static::echo_success("✓");

        return [$count_v2, $count_v3];
    }

    /**
     * Copy from version 2 to version 3 with a one to one mapping with, helper function
     *
     * @param $table string
     */
    public static function copy_table_one_to_one($table)
    {
        $table_v2 = static::$from_prefix . '_' . $table;
        $table_v3 = static::$to_prefix . '_' . $table;
        static::copy_table_one_to_one_sql($table_v2, $table_v3, "INSERT INTO `$table_v3` SELECT * FROM `$table_v2`");
    }

    /**
     * Empty a table with TRUNCATE
     *
     * @param $table string
     */
    public static function empty_table($table)
    {
        echo "Emptying $table ";
        try
        {
            assert(static::$db->beginTransaction());
            static::$db->query("SET FOREIGN_KEY_CHECKS=0; TRUNCATE TABLE `$table`; SET FOREIGN_KEY_CHECKS = 1;");
            assert(static::$db->commit());
        }
        catch (Exception $e)
        {
            static::$db->rollBack();
            static::exit_error("\n" . $e->getMessage());
        }
        static::echo_success('✓');
    }
}

Convert::init('v2', 'v3');
echo $doc;

if (!Convert::get_prompt_answer("Run script?"))
{
    Convert::exit_error("ABORTING");
}

// Check if tables exist
$tables = [
    'v3_bugs_comments',
    'v3_bugs',
    'v3_votes',
    'v3_addon_revisions',
    'v3_cache',
    'v3_files_delete',
    'v3_files',
    'v3_file_types',
    'v3_addons',
    'v3_addon_types',
    'v3_server_conn',
    'v3_servers',
    'v3_host_votes',
    'v3_client_sessions',
    'v3_news',
    'v3_notifications',
    'v3_friends',
    'v3_achieved',
    'v3_achievements',
    'v3_verification',
    'v3_users',
    'v3_role_permissions',
    'v3_roles',
    'v3_stats',
    'v3_config',
    'v3_music',
    'v3_clients'
];

echo "Checking tables exist ";
try
{
    foreach ($tables as $table)
    {
        Convert::$db->query(sprintf('SELECT 1 FROM `%s` LIMIT 1', $table), DBConnection::FETCH_FIRST);
    }
}
catch (DBException $e)
{
    Convert::exit_error($e->getMessage());
}
Convert::echo_success('✓');


// Clients
Convert::empty_table('v3_clients');
Convert::copy_table_one_to_one('clients');
Convert::echo_newline();


// Music
Convert::empty_table('v3_music');
Convert::copy_table_one_to_one('music');
Convert::echo_newline();


// Stats
Convert::empty_table('v3_stats');
Convert::copy_table_one_to_one('stats');
Convert::echo_newline();


// Roles and role permissions
Convert::empty_table('v3_role_permissions');
Convert::empty_table('v3_roles');
Convert::copy_table_one_to_one('roles');
Convert::copy_table_one_to_one('role_permissions');
Convert::echo_newline();


// Users
Convert::empty_table('v3_users');
Convert::copy_table_one_to_one_sql(
    'v2_users',
    'v3_users',
    'INSERT INTO `v3_users`(`id`, `role_id`, `username`, `password`, `realname`, `email`, `is_active`, `date_login`, `date_register`, `homepage`)
                  SELECT V2.`id`, V3R.`id`, `user`, `pass`, V2.`name`, `email`, `active`, `last_login`, `reg_date`, `homepage`
                  FROM `v2_users` V2
                        LEFT JOIN `v3_roles` V3R
                            ON V2.`role` = V3R.`name`'
);

// Non normal users count is equal
assert(
    Convert::$db->query(
        "SELECT COUNT(*) FROM `v2_users` WHERE `role` != 'user'",
        DBConnection::FETCH_FIRST_COLUMN
    ) === Convert::$db->query(
        "SELECT COUNT(*) FROM `v3_users` WHERE `role_id` != 1 ",
        DBConnection::FETCH_FIRST_COLUMN
    )
);
Convert::echo_newline();


// Verification
Convert::empty_table('v3_verification');
Convert::copy_table_one_to_one('verification');
Convert::echo_newline();


// Achievements
Convert::empty_table('v3_achieved');
Convert::empty_table('v3_achievements');
Convert::copy_table_one_to_one('achievements');
Convert::copy_table_one_to_one('achieved');
Convert::echo_newline();


// Friends
Convert::empty_table('v3_friends');
Convert::copy_table_one_to_one('friends');
Convert::echo_newline();


// Notifications
Convert::empty_table('v3_notifications');
echo "Cleaning old id's that do not respect constraints for v2_notifications";
$affected = 0;
try
{
    $affected = Convert::$db->getConnection()->exec(
        'DELETE FROM `v2_notifications`
            WHERE `to` NOT IN (SELECT id FROM `v2_users`) OR
                  `from` NOT IN (SELECT id FROM `v2_users`)'
    );
}
catch (Exception $e)
{
    Convert::exit_error($e->getMessage());
}
Convert::echo_success(" $affected ✓");
Convert::copy_table_one_to_one('notifications');
Convert::echo_newline();


// Logs
Convert::empty_table('v3_logs');
echo "USERID == 0, ";
Convert::copy_table_one_to_one_sql(
    'v2_logs',
    'v3_logs',
    'INSERT INTO v3_logs(id, date, message, is_emailed)
                  SELECT id, date, message, emailed FROM v2_logs WHERE `user` = 0',
    false
);
echo "USERID != 0, ";
list($count_v2, $count_v3) = Convert::copy_table_one_to_one_sql(
    'v2_logs',
    'v3_logs',
    'INSERT INTO v3_logs(id, user_id, date, message, is_emailed)
                  SELECT id, user, date, message, emailed FROM v2_logs WHERE `user` != 0',
    false
);
if ($count_v2 !== $count_v3)
{
    Convert::exit_error(sprintf('Integrity check failed, v2 != v3, %d != %d', $count_v2, $count_v3));
}
Convert::echo_newline();


// News
Convert::empty_table('v3_news');
Convert::copy_table_one_to_one_sql(
    'v2_news',
    'v3_news',
    'INSERT INTO v3_news(`id`, author_id, `date`, `content`, `condition`, is_important, is_web_display, is_active, is_dynamic)
                  SELECT `id`, author_id, `date`, `content`, `condition`, important, web_display, active, dynamic
                  FROM v2_news'
);
Convert::echo_newline();


// Addons
Convert::empty_table('v3_addons');
Convert::copy_table_one_to_one_sql(
    'v2_addons',
    'v3_addons',
    'INSERT INTO v3_addons(`id`, `type`, `name`, `uploader`, `creation_date`, `designer`, `props`, `description`, `license`, `min_include_ver`, `max_include_ver`)
                      SELECT `id`, V3T.`type`, `name`, `uploader`, `creation_date`, `designer`, `props`, `description`, `license`, `min_include_ver`, `max_include_ver`
                      FROM v2_addons V2
                           LEFT JOIN v3_addon_types V3T
                                ON V2.`type` = V3T.name_plural'
);
Convert::echo_newline();


// Files
Convert::empty_table('v3_files_delete');
Convert::empty_table('v3_files');
Convert::copy_table_one_to_one_sql(
    'v2_files',
    'v3_files',
    'INSERT INTO v3_files(id, addon_id, `type`, `path`, date_added, is_approved, downloads)
                   SELECT id, addon_id, `type`, `file_path`, date_added, approved, downloads
                   FROM v2_files V2
                        LEFT JOIN v3_file_types V3T
                            ON V3T.name = V2.file_type'
);
echo "(delete_date column) - ";
Convert::copy_table_one_to_one_sql(
    'v2_files',
    'v3_files_delete',
    "INSERT INTO v3_files_delete(file_id, date_delete)
                   SELECT id, delete_date FROM `v2_files` WHERE delete_date <> '0000-00-00'",
    false
);
Convert::echo_newline();


// Addon votes
Convert::empty_table('v3_votes');
Convert::copy_table_one_to_one('votes');
Convert::echo_newline();


// Bugs
Convert::empty_table('v3_bugs_comments');
Convert::empty_table('v3_bugs');
Convert::copy_table_one_to_one('bugs');
Convert::copy_table_one_to_one('bugs_comments');
Convert::echo_newline();


// Addon revisions
Convert::empty_table('v3_addon_revisions');
list($count_karts, $count_total) = Convert::copy_table_one_to_one_sql(
    'v2_karts_revs',
    'v3_addon_revisions',
    'INSERT INTO v3_addon_revisions(addon_id, file_id, creation_date, revision, `format`, image_id, icon_id, `status`, moderator_note)
                             SELECT addon_id, fileid, creation_date, revision, `format`, `image`, `icon`, `status`, moderator_note
                             FROM v2_karts_revs'
);
list($count_arenas, $count_total) = Convert::copy_table_one_to_one_sql(
    'v2_arenas_revs',
    'v3_addon_revisions',
    'INSERT INTO v3_addon_revisions(addon_id, file_id, creation_date, revision, `format`, image_id, `status`, moderator_note)
                             SELECT addon_id, fileid, creation_date, revision, `format`, `image`, `status`, moderator_note
                             FROM v2_arenas_revs',
    false
);

$check_total = $count_karts + $count_arenas;
if ($check_total !== $count_total)
{
    Convert::exit_error(
        sprintf('Integrity check failed, v2 (arenas + karts) != v3 (revisions), %d != %d', $check_total, $count_total)
    );
}

list($count_tracks, $count_total) = Convert::copy_table_one_to_one_sql(
    'v2_tracks_revs',
    'v3_addon_revisions',
    'INSERT INTO v3_addon_revisions(addon_id, file_id, creation_date, revision, `format`, image_id, `status`, moderator_note)
                             SELECT addon_id, fileid, creation_date, revision, `format`, `image`, `status`, moderator_note
                             FROM v2_tracks_revs',
    false
);

$check_total = $count_karts + $count_arenas + $count_tracks;
if ($check_total !== $count_total)
{
    Convert::exit_error(
        sprintf(
            'Integrity check failed, v2 (arenas + karts + tracks) != v3 (revisions), %d != %d',
            $check_total,
            $count_total
        )
    );
}
Convert::echo_newline();


// Config, Servers
Convert::echo_warning('Servers, Host votes, cache and server connections table are not converted');
Convert::echo_warning('You must copy v2_config table manually');
Convert::echo_newline();
