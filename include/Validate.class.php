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

/**
 * Class to contain all string validation functions
 * @author stephenjust
 */
class Validate {
    /**
     * Check if the input is a valid email address
     * @param string $email Email address
     * @return string Email address
     */
    public static function email($email) {
        if (strlen($email) == 0) {
            throw new UserException(htmlspecialchars(_('You must enter an email address.')));
        }
        if (!preg_match('/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i',$email)) {
            throw new UserException(htmlspecialchars(sprintf(_('"%s" is not a valid email address.'),$email)));
        }
        return mysql_real_escape_string(htmlspecialchars($email));
    }
    
    /**
     * Check if the input is a valid alphanumeric username
     * @param string $username Alphanumeric username
     * @return string Username
     */
    public static function username($username) {
        if (strlen($username) < 4) {
            throw new UserException(htmlspecialchars(_('Your username must be at least 4 characters long.')));
        }
        if (!preg_match('/^[a-z0-9]+$/i',$username)) {
            throw new UserException(htmlspecialchars(_('Your username can only contain alphanumeric characters.')));
        }
        return mysql_real_escape_string(htmlspecialchars($username));
    }
    
    public static function password($password1, $password2 = NULL) {
        if (strlen($password1) < 6) {
            throw new UserException(htmlspecialchars(_('Your password must be at least 6 characters long.')));
        }
        if ($password2 != NULL) {
            if ($password1 !== $password2) {
                throw new UserException(htmlspecialchars(_('Your passwords do not match.')));
            }
        }
        return hash('sha256',$password1);
    }
    
    public static function realname($name) {
        if (strlen(trim($name)) < 2) {
            throw new UserException(htmlspecialchars(_('You must enter a name.')));
        }
        return mysql_real_escape_string(htmlspecialchars(trim($name)));
    }
    
    public static function checkbox($box, $message) {
        if ($box !== 'on') {
            throw new UserException($message);
        }
        return $box;
    }
}
?>
