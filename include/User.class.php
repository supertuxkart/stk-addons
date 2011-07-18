<?php
/**
 * copyright 2011 Stephen Just <stephenjust@users.sf.net>
 *
 * This file is part of stkaddons
 *
 * stkaddons is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * stkaddons is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with stkaddons.  If not, see <http://www.gnu.org/licenses/>.
 */

class User
{
    public static $logged_in = false;
    public static $user_id = 0;

    static function init() {
        // Validate user's session on every page
        if (session_id() == "") {
            session_start();
        }

        // Check if any session variables are not set
        if (!isset($_SESSION['userid']) ||
                !isset($_SESSION['user']) ||
                !isset($_SESSION['pass']) ||
                !isset($_SESSION['real_name']) ||
                !isset($_SESSION['last_login']) ||
                !isset($_SESSION['role']))
        {
            // One or more of the session variables was not set - this may
            // be an issue, so force logout
            User::logout();
            return;
        }
        // Validate session if complete set of variables is available
        $querySql = 'SELECT `id`,`user`,`pass`,`name`,`role`
            FROM `'.DB_PREFIX.'users`
            WHERE `user` = \''.$_SESSION['user'].'\'
            AND `pass` = \''.$_SESSION['pass'].'\'
            AND `last_login` = \''.$_SESSION['last_login'].'\'
            AND `name` = \''.$_SESSION['real_name'].'\'
            AND `active` = 1';
        $reqSql = sql_query($querySql);
        $num_rows = mysql_num_rows($reqSql);
        if($num_rows != 1)
        {
            User::logout();
            return false;
        }
        User::$user_id = $_SESSION['userid'];
        User::$logged_in = true;
    }

    static function login($username,$password)
    {
        // Validate parameters
        // Username must be 4 or more characters
        if (strlen($username) < 4)
            return false;
        // Password must be 8 or more characters
        if (strlen($password) < 6)
            return false;
        // Username can only contain alphanumeric characters
        if (!preg_match('/^[a-z0-9]+$/i',$username))
            return false;
        $password = hash('sha256',$password);

        // Get user record
        $querySql = 'SELECT `id`, `user`, `pass`, `name`, `role`
                FROM `'.DB_PREFIX.'users`
                WHERE `user` = \''.$username.'\'
                AND `pass` = \''.$password.'\'
                AND `active` = 1';
        $reqSql = sql_query($querySql);
        if (!$reqSql)
        {
            User::logout();
            return false;
        }
        $num_rows = mysql_num_rows($reqSql);

        // Check if the user exists
        if($num_rows != 1) {
            User::logout();
            return false;
        }
        $result = mysql_fetch_assoc($reqSql);

        $_SESSION['userid'] = $result['id'];
        $_SESSION['user'] = $username;
        $_SESSION['pass'] = $password;
        $_SESSION['real_name'] = $result['name'];
        $_SESSION['last_login'] = date('Y-m-d H:i:s');
        include(ROOT.'include/allow.php');

        // Set latest login time
        $set_logintime_query = 'UPDATE '.DB_PREFIX.'users
                SET last_login=\''.$_SESSION['last_login'].'\'
                WHERE id = '.$_SESSION['userid'];
        $reqSql = mysql_query($set_logintime_query);
        if (!$reqSql) {
            User::logout();
            return false;
        }
        User::$user_id = $result['id'];
        User::$logged_in = true;
        return true;
    }

    static function logout()
    {
        unset($_SESSION['userid']);
        unset($_SESSION['user']);
        unset($_SESSION['pass']);
        unset($_SESSION['role']);
        unset($_SESSION['real_name']);
        unset($_SESSION['last_login']);
        session_destroy();
        session_start();
        User::$user_id = 0;
        User::$logged_in = false;
    }
}
User::init();

function loadUsers()
{
    global $js;
    $userLoader = new coreUser();
    $userLoader->loadAll();
    echo <<< EOF
<ul>
<li>
<a class="menu-item" href="javascript:loadFrame({$_SESSION['userid']},'users-panel.php')">
<img class="icon" src="image/user.png" />
EOF;
    echo htmlspecialchars(_('Me')).'</a></li>';
    ?>
    <?php
    while($userLoader->next())
    {
        // Make sure that the user is active, or the viewer has permission to
        // manage this type of user
        if ($_SESSION['role']['manage'.$userLoader->userCurrent['role'].'s']
                || $userLoader->userCurrent['active'] == 1)
        {
            echo '<li><a class="menu-item';
            if($userLoader->userCurrent['active'] == 0) echo ' unavailable';
            echo '" href="javascript:loadFrame('.$userLoader->userCurrent['id'].',\'users-panel.php\')">';
            echo '<img class="icon"  src="image/user.png" />';
            echo $userLoader->userCurrent['user']."</a></li>";
            // When running for the list of users, check if we want to load this
            // user's profile. Doing this here is more efficient than searching
            // for the user name with another query. Also, leaving this here
            // cause the lookup to fail if permissions were invalid.
            if($userLoader->userCurrent['user'] == $_GET['user']) $js.= 'loadFrame('.$userLoader->userCurrent['id'].',\'users-panel.php\')';
        }
    }
    echo "</ul>";

}
?>
