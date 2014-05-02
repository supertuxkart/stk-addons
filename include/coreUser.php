<?php
/* copyright 2009 Lucas Baudin <xapantu@gmail.com>                 
 *
 * This file is part of stkaddons.
 * stkaddons is free software: you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.
 *
 * stkaddons is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * stkaddons.  If not, see <http://www.gnu.org/licenses/>.
 */
/***************************************************************************
 * Project: STK Addon Manager
 *
 * File: coreAddon.php
 * Version: 1
 * Licence: GPLv3
 * Description: file where all functions are
 ***************************************************************************/

require_once(INCLUDE_DIR . 'DBConnection.class.php');

// TODO add generic selectByField
class coreUser
{
    public $userCurrent;

    /**
     * Get a user by it's id
     *
     * @param $id
     *
     * @throws UserException
     */
    public function selectById($id)
    {
        try {
            $users = DBConnection::get()->query(
                    'SELECT * FROM ' . DB_PREFIX . 'users
                     WHERE `id` = :id',
                    DBConnection::FETCH_ALL,
                    array(":id" => (int)$id)
            );
            // no user found
            if (empty($users)) {
                $this->userCurrent = null;
                return;
            }
            $this->userCurrent = $users[0];
        } catch(DBException $e) {
            $this->userCurrent = null;
            if (DEBUG_MODE) {
                throw new UserException("Error on selecting by id");
            }
        }
    }

    /**
     * Get a user by it's username
     *
     * @param $user
     *
     * @throws UserException
     */
    public function selectByUser($user)
    {
        try {
            $users = DBConnection::get()->query(
                    'SELECT * FROM ' . DB_PREFIX . 'users
                     WHERE `user` = :user',
                    DBConnection::FETCH_ALL,
                    array(":user" => (string)$user)
            );
            // no user found
            if (empty($users)) {
                $this->userCurrent = null;
                return;
            }
            $this->userCurrent = $users[0];
        } catch(DBException $e) {
            $this->userCurrent = null;
            if (DEBUG_MODE) {
                throw new UserException("Error on selecting by user");
            }
        }
    }

    /**
     * Get all the users from the database
     *
     * @return array|int
     * @throws UserException
     */
    public function getAll()
    {
        try {
            $users = DBConnection::get()->query(
                    'SELECT * FROM ' . DB_PREFIX . 'users
                    ORDER BY `user` ASC, `id` ASC',
                    DBConnection::FETCH_ALL
            );
        } catch(DBException $e) {
            if (DEBUG_MODE) {
                throw new UserException("Error on selecting all users");
            }
            return array();
        }

        return $users;
    }

    /**
     * Get the overall view about the user
     *
     * @return bool|string
     */
    public function getViewInformation()
    {
        if (!User::$logged_in) {
            return false;
        }

        $output = $this->getInformation();

        // Allow current user to change own profile, and administrators
        // to change all profiles
        if ($_SESSION['role']['manage' . $this->userCurrent['role'] . 's']
                || $this->userCurrent['id'] === $_SESSION['userid']
        ) {
            $output .= $this->getConfig();
        }

        return $output;
    }

    /**
     * Get the html representing the information about the user
     *
     * @return string
     */
    public function getInformation()
    {
        $output = '<h1>' . $this->userCurrent['user'] . '</h1>';
        $output .= '<table><tr><td>' . htmlspecialchars(
                        _('Username:')
                ) . '</td><td>' . $this->userCurrent['user'] . '</td></tr>';
        $output .= '<tr><td>' . htmlspecialchars(
                        _('Registration Date:')
                ) . '</td><td>' . $this->userCurrent['reg_date'] . '</td></tr>';
        $output .= '<tr><td>' . htmlspecialchars(
                        _('Real Name:')
                ) . '</td><td>' . $this->userCurrent['name'] . '</td></tr>';
        $output .= '<tr><td>' . htmlspecialchars(_('Role:')) . '</td><td>' . htmlspecialchars(
                        _($this->userCurrent['role'])
                ) . '</td></tr>';
        if (strlen($this->userCurrent['homepage']) > 0) {
            $output .= '<tr><td>' . htmlspecialchars(
                            _('Homepage:')
                    ) . '</td><td><a href="' . $this->userCurrent['homepage'] . '" >' . $this->userCurrent['homepage'] . '</a></td></tr>';
        }
        $output .= '</table>';

        $output .= $this->getAddonList('karts');
        $output .= $this->getAddonList('tracks');
        $output .= $this->getAddonList('arenas');

        return $output;
    }

    /**
     * Get the html of the config
     *
     * @return string
     */
    public function getConfig()
    {
        $output = '
        <hr />
        <h3>Configuration</h3>
        <form enctype="multipart/form-data" action="?user=' . $this->userCurrent['user'] . '&amp;action=config" method="POST" >
        <table>';
        $output .= '<tr><td>' . htmlspecialchars(
                        _('Homepage:')
                ) . '</td><td><input type="text" name="homepage" value="' . $this->userCurrent['homepage'] . '" disabled /></td></tr>';
        // Edit role if allowed
        if ($_SESSION['role']['manage' . $this->userCurrent['role'] . 's'] == true || $_SESSION['userid'] == $this->userCurrent['id']) {
            $output .= '<tr><td>' . htmlspecialchars(_('Role:')) . '</td><td>';
            $role_disabled = null;
            if ($_SESSION['userid'] == $this->userCurrent['id']) {
                $role_disabled = 'disabled';
            }
            $output .= '<select name="range" ' . $role_disabled . '>';
            $output .= '<option value="basicUser">Basic User</option>';
            $range = array("moderator", "administrator", "supAdministrator", "root");
            for ($i = 0; $i < count($range); $i++) {
                if ($_SESSION['role']['manage' . $range[$i] . 's'] == true || $this->userCurrent['role'] == $range[$i]) {
                    $output .= '<option value="' . $range[$i] . '"';
                    if ($this->userCurrent['role'] == $range[$i]) {
                        $output .= ' selected="selected"';
                    }
                    $output .= '>' . $range[$i] . '</option>';
                }
            }
            $output .= '</select>';
            $output .= '</td></tr><tr><td>' . htmlspecialchars(_('User Activated:')) . '</td><td>';
            $output .= '<input type="checkbox" name="available" ';
            if ($this->userCurrent['active'] == 1) {
                $output .= 'checked="checked" ';
            }
            $output .= '/></td></tr>';
        }
        $output .= '<tr><td></td><td><input type="submit" value="' . htmlspecialchars(
                        _('Save Configuration')
                ) . '" /></td></tr>';
        $output .= '</table></form><br />';
        if ($this->userCurrent['id'] == $_SESSION['userid']) {
            $output .= '<h3>' . htmlspecialchars(_('Change Password')) . '</h3><br />
            <form action="users.php?user=' . $this->userCurrent['user'] . '&amp;action=password" method="POST">
            ' . htmlspecialchars(_('Old Password:')) . '<br />
            <input type="password" name="oldPass" /><br />
            ' . htmlspecialchars(_('New Password:')) . ' (' . htmlspecialchars(
                            sprintf(_('Must be at least %d characters long.'), '8')
                    ) . ')<br />
            <input type="password" name="newPass" /><br />
            ' . htmlspecialchars(_('New Password (Confirm):')) . '<br />
            <input type="password" name="newPass2" /><br />
            <input type="submit" value="' . htmlspecialchars(_('Change Password')) . '" />
            </form>';
        }

        return $output;
    }

    /**
     * Get the html representing the type
     *
     * @param string $type the type of addon
     *
     * @return string|null
     * @throws UserException
     */
    public function getAddonList($type)
    {
        switch ($type) {
            case 'tracks':
                $heading = htmlspecialchars(_('User\'s Tracks'));
                $no_items = htmlspecialchars(_('This user has not uploaded any tracks.'));
                break;
            case 'karts':
                $heading = htmlspecialchars(_('User\'s Karts'));
                $no_items = htmlspecialchars(_('This user has not uploaded any karts.'));
                break;
            case 'arenas':
                $heading = htmlspecialchars(_('User\'s Arenas'));
                $no_items = htmlspecialchars(_('This user has not uploaded any arenas.'));
                break;
            default:
                if (DEBUG_MODE) {
                    throw new UserException(sprintf("Addon type=%s does not exist", $type));
                }
                return null;
        }
        $output = "<h3>$heading</h3>\n";
        try {
            $addon_list = DBConnection::get()->query(
                    'SELECT `a`.*, `r`.`status`
                    FROM `' . DB_PREFIX . 'addons` `a`
                    LEFT JOIN `' . DB_PREFIX . $type . '_revs` `r`
                    ON `a`.`id` = `r`.`addon_id`
                    WHERE `a`.`uploader` = :uploader
                    AND `a`.`type` = :addon_type',
                    DBConnection::FETCH_ALL,
                    array(
                            ":uploader"   => (int)$this->userCurrent['id'],
                            ":addon_type" => $type,
                    )
            );
            if (empty($addon_list)) {
                $output .= "$no_items<br />\n";
                return $output;
            }
        } catch(DBException $e) {
            $output .= "$no_items<br />\n";
            return $output;
        }

        // Print list
        $output .= '<ul>';
        $addon_count = count($addon_list);
        for ($i = 0; $i < $addon_count; $i++) {
            $result = $addon_list[$i];

            // Only list the latest revision of the add-on
            if (!($result['status'] & F_LATEST)) {
                continue;
            }
            if ($result['status'] & F_APPROVED) {
                $output .= '<li><a href="addons.php?type=' . $type . '&amp;name=' . $result['id'] . '">' .
                        $result['name'] . '</a></li>';
            } else {
                if ($_SESSION['role']['manageaddons'] == false && $result['uploader'] !== $_SESSION['userid']) {
                    continue;
                }
                $output .= '<li class="unavailable"><a href="addons.php?type=' . $type . '&amp;name=' . $result['id'] .
                        '">' . $result['name'] . '</a></li>';
            }
        }
        $output .= '</ul>';

        return $output;
    }

    /**
     * Set the user password
     *
     * @param string $old_password
     * @param string $new_password_1
     * @param string $new_password_2
     *
     * @return bool true on success
     * @throws UserException
     */
    public function setPass($old_password, $new_password_1, $new_password_2)
    {
        // TODO: FIX error message on old password
        $new_password = Validate::password($new_password_1, $new_password_2);

        if (Validate::password($old_password, null, $_SESSION['user']) !== $this->userCurrent['pass']) {
            throw new UserException(htmlspecialchars(_('Your old password is not correct.')));
        }

        if (User::$user_id === $this->userCurrent['id']) {
            User::change_password($new_password);
        }
        return true;
    }

    /**
     * Set the user config
     *
     * @param null $available the user active option
     * @param null $role      the role of the user
     *
     * @return bool true on success false otherwise
     */
    public function setConfig($available = null, $role = null)
    {
        if ($_SESSION['role']['manage' . $this->userCurrent['role'] . 's']) {

            // Set availability status
            if ($available === 'on') {
                $available = 1;
            } else {
                $available = 0;
            }
            try {
                DBConnection::get()->query(
                        'UPDATE ' . DB_PREFIX . 'users
                        SET `active` = :active
                        WHERE `id` = :id',
                        DBConnection::NOTHING,
                        array(
                                ":id"     => (int)$this->userCurrent['id'],
                                ":active" => $available,
                        )
                );
            } catch(DBException $e) {
                return false;
            }

            // Set permission level
            if ($role) {
                if ($_SESSION['role']['manage' . $role . 's']) {
                    try {
                        DBConnection::get()->query(
                                'UPDATE ' . DB_PREFIX . 'users fdssdf
                                SET `role` = :role
                                WHERE `id` = :id',
                                DBConnection::NOTHING,
                                array(
                                        ":id"   => (int)$this->userCurrent['id'],
                                        ":role" => $role,
                                )
                        );
                    } catch(DBException $e) {
                        return false;
                    }
                }
            }

            // success
            return true;
        }

        // we do not have permission
        return false;
    }

    /**
     * Generate a url that point to the current user
     *
     * @return string
     */
    public function permalink()
    {
        return 'users.php?user=' . $this->userCurrent['user'];
    }
}
