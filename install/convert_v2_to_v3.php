<?php
if (php_sapi_name() !== 'cli')
{
    exit('Not in CLI Mode');
}
assert_options(ASSERT_ACTIVE, true);
assert_options(ASSERT_BAIL, true);
define('CRON_MODE', true);
require_once('../config.php');

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
        return static::$db->query("SELECT COUNT(*) FROM $table", DBConnection::FETCH_FIRST_COLUMN);
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

            if ($check_integrity && $count_v2 != $count_v3)
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
            if (static::$db->isInTransaction())
            {
                static::$db->rollBack();
            }
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
            static::$db->commit();
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

// Check if tables exist
//if (!get_prompt_answer("Run script?"))
//{
//    echo_error("ABORTING");
//    exit();
//}

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

echo "Converting v2_users ";
$dbh = Convert::$db->getConnection();
try
{
    $count_v2 = Convert::count_table('v2_users');
    assert($dbh->beginTransaction());

    $roles = AccessControl::getRoles();
    $select_sth = $dbh->query('SELECT * FROM `v2_users`');
    $insert_sth = $dbh->prepare(
        'INSERT INTO `v3_users`(`id`, `role_id`, `username`, `password`, `realname`, `email`, `is_active`, `date_login`, `date_register`, `homepage`)
        VALUES(:id, :role_id, :username, :password, :realname, :email, :is_active, :date_login, :date_register, :homepage)'
    );

    while ($row = $select_sth->fetch(PDO::FETCH_ASSOC))
    {
        $insert_sth->execute(
            [
                ':id'            => $row['id'],
                ':role_id'       => $roles[$row['role']],
                ':username'      => $row['user'],
                ':password'      => $row['pass'],
                ':realname'      => $row['name'],
                ':email'         => $row['email'],
                ':is_active'     => $row['active'],
                ':date_login'    => $row['last_login'],
                ':date_register' => $row['reg_date'],
                ':homepage'      => $row['homepage']
            ]
        );
    }

    assert($dbh->commit());
    $count_v3 = Convert::count_table('v3_users');

    if ($count_v2 != $count_v3)
    {
        throw new Exception(sprintf('Integrity check failed, v2 != V3, %d != %d', $count_v2, $count_v3));
    }
}
catch (Exception $e)
{
    if ($dbh->inTransaction())
    {
        $dbh->rollBack();
    }
    Convert::exit_error($e->getMessage());
}
Convert::echo_success("✓");
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
if ($count_v2 != $count_v3)
{
    Convert::exit_error(sprintf('Integrity check failed, v2 != V3, %d != %d', $count_v2, $count_v3));
}
Convert::echo_newline();


// News
Convert::empty_table('v3_news');
Convert::copy_table_one_to_one_sql(
    'v2_news',
    'v3_news',
    'INSERT INTO v3_news(`id`, author_id, `date`, `content`, `condition`, is_important, is_web_display, is_active, is_dynamic)
                  SELECT `id`, author_id, `date`, `content`, `condition`, important, web_display, active, dynamic FROM v2_news'
);
Convert::echo_newline();


// Host votes
Convert::empty_table('v3_host_votes');
Convert::copy_table_one_to_one('host_votes');
Convert::echo_newline();


// Config
Convert::echo_warning('You must copy v2_config table manually');
Convert::echo_newline();
