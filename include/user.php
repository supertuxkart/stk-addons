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

class user
{
    public $logged_in = false;
    public $user_id = 0;

    function __construct() {
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
            $this->logout();
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
            $this->logout();
            return false;
        }
        $this->user_id = $_SESSION['userid'];
        $this->logged_in = true;
    }

    function login($username,$password)
    {
        // Validate parameters
        // Username must be 4 or more characters
        if (strlen($username) < 4)
            return false;
        // Password must be 8 or more characters
        if (strlen($password) < 8)
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
            $this->logout();
            return false;
        }
        $num_rows = mysql_num_rows($reqSql);

        // Check if the user exists
        if($num_rows != 1) {
            $this->logout();
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
            $this->logout();
            return false;
        }
        $this->user_id = $result['id'];
        $this->logged_in = true;
        return true;
    }

    function logout()
    {
        unset($_SESSION['userid']);
        unset($_SESSION['user']);
        unset($_SESSION['pass']);
        unset($_SESSION['role']);
        unset($_SESSION['real_name']);
        unset($_SESSION['last_login']);
        session_destroy();
        session_start();
        $this->user_id = 0;
        $this->logged_in = false;
    }
}

function loadUsers()
{
    global $style, $js;
    $userLoader = new coreUser();
    $userLoader->loadAll();
    echo <<< EOF
<ul id="list-addons">
<li>
<a class="menu-addons" href="javascript:loadAddon({$_SESSION['userid']},'user.php')">
<img class="icon"  src="image/$style/user.png" />
EOF;
    echo _('Me').'</a></li>';
    ?>
    <?php
    while($userLoader->next())
    {
        echo '<li><a class="menu-addons';
        if($userLoader->userCurrent['active'] == 0) echo ' unavailable';
        echo '" href="javascript:loadAddon('.$userLoader->userCurrent['id'].',\'user.php\')">';
        echo '<img class="icon"  src="image/'.$style.'/user.png" />';
        echo $userLoader->userCurrent['user']."</a></li>";
        if($userLoader->userCurrent['user'] == $_GET['title']) $js.= 'loadAddon('.$userLoader->userCurrent['id'].',\'user.php\')';
    }
    echo "</ul>";

}

$user = new user;
?>
