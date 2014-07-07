<?php
/**
 * copyright 2011 Stephen Just <stephenjust@users.sf.net>
 *           2013 Glenn De Jonghe
 *           2014 Daniel Butum <danibutum at gmail dot com>
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
 * Class to contain all the validation functions
 * @author stephenjust
 */
class Validate
{
    const MIN_PASSWORD_LENGTH = 8;

    const MIN_USERNAME_LENGTH = 4;

    const MIN_REAL_NAME = 2;

    // fake enumeration
    const PASSWORD_ID = 1;

    const PASSWORD_USERNAME = 2;

    const CREDENTIAL_ID = 1;

    const CREDENTIAL_USERNAME = 2;

    /**
     * Checks a username/email address combination and returns the user id if valid
     *
     * @param string $username
     * @param string $email
     *
     * @throws UserException when username/email combination is invalid, or multiple accounts are found
     */
    public static function account($username, $email)
    {
        $users = DBConnection::get()->query(
            "SELECT `id`
	        FROM `" . DB_PREFIX . "users`
	        WHERE `user` = :username
            AND `email` = :email
            AND `active` = 1",
            DBConnection::FETCH_ALL,
            [':username' => $username, ':email' => $email]
        );

        if (empty($users))
        {
            throw new UserException(_h('Username and email address combination not found.'));
        }
        if (count($users) > 1)
        {
            throw new UserException(_h("Multiple accounts with the same username and email combination."));
        }

        return $users[0]['id'];
    }

    /**
     * Check if the username/password matches
     *
     * @param string $password unhashed password
     * @param mixed $field_value the field value
     * @param int $credential_type
     *
     * @throws UserException
     * @throws InvalidArgumentException
     * @return array associative with user information from the database
     */
    public static function credentials($password, $field_value, $credential_type)
    {
        if ($credential_type === static::CREDENTIAL_ID)
        {
            $password = static::password($password, $field_value, static::PASSWORD_ID);
            $where_part = "`id` = :field_value";
        }
        elseif ($credential_type === static::CREDENTIAL_USERNAME)
        {
            $password = static::password($password, $field_value, static::PASSWORD_USERNAME);
            $field_value = static::username($field_value);
            $where_part = "`user` = :field_value";
        }
        else
        {
            throw new InvalidArgumentException("credential type is invalid");
        }

        // build query
        $query = sprintf(
            "SELECT `id`, `user`, `pass`, `name`, `role`
             FROM `" . DB_PREFIX . "users`
             WHERE `pass` = :pass AND %s",
            $where_part
        );

        try
        {
            $users = DBConnection::get()->query(
                $query,
                DBConnection::FETCH_ALL,
                [
                    ":pass"        => $password,
                    ":field_value" => $field_value
                ]
            );
        }
        catch(PDOException $e)
        {
            throw new UserException(
                _h('An error occurred while signing in.') . ' ' .
                _h('Please contact a website administrator.')
            );
        }

        $count_users = count($users);

        // combination is invalid
        if ($count_users === 0)
        {
            throw new UserException(_h('Username and/or password combination is invalid.'));
        }

        // 2 users have the same username/password combination. This should never happen
        if ($count_users > 1)
        {
            // TODO send email to moderator
            Log::newEvent("To users with the same credentials: field_value = " . h($field_value));
            throw new UserException(_h('Username and/or password combination is invalid.'));
        }

        // get the first
        return $users[0];
    }

    /**
     * Check if the input is a valid email address, and return the email html escaped
     *
     * @param string $email Email address
     *
     * @throws UserException
     * @return string Email address
     */
    public static function email($email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        {
            throw new UserException(h(sprintf(_('"%s" is not a valid email address.'), h($email))));
        }

        return h($email);
    }

    /**
     * Check if the input is a valid alphanumeric username, and return the username html escaped
     *
     * @param string $username Alphanumeric username
     *
     * @throws UserException
     * @return string Username
     */
    public static function username($username)
    {
        $username = Util::str_strip_space($username);
        if (mb_strlen($username) < static::MIN_USERNAME_LENGTH)
        {
            throw new UserException(_h('Your username must be at least 4 characters long(with no spaces)'));
        }
        if (!preg_match('/^[a-z0-9]+$/i', $username))
        {
            throw new UserException(_h('Your username can only contain alphanumeric characters.'));
        }

        return h($username);
    }

    /**
     * @param string $name
     *
     * @return string
     * @throws UserException
     */
    public static function realName($name)
    {
        $name = trim($name);
        if (mb_strlen($name) < static::MIN_REAL_NAME)
        {
            throw new UserException(_h('You must enter a name at least with 2 characters long'));
        }

        return h($name);
    }

    /**
     * Validate if the password is the correct length
     *
     * @param string $password
     *
     * @throws UserException
     */
    protected static function checkPasswordLength($password)
    {
        if (mb_strlen($password) < static::MIN_PASSWORD_LENGTH)
        {
            throw new UserException(sprintf(_h('Your password must be at least %d characters long.'), static::MIN_PASSWORD_LENGTH));
        }
    }

    /**
     * Validate if the 2 passwords match and are the correct legnth
     *
     * @param string $new_password
     * @param string $new_password_verify
     *
     * @return string the password hash
     * @throws UserException
     */
    public static function newPassword($new_password, $new_password_verify)
    {
        static::checkPasswordLength($new_password);

        // check if they match
        if ($new_password !== $new_password_verify)
        {
            throw new UserException(_h('Passwords do not match'));
        }

        return Util::getPasswordHash($new_password);
    }

    /**
     * @param string $password
     * @param string $field_value
     * @param int    $field_type
     *
     * @return string
     * @throws UserException
     */
    public static function password($password, $field_value, $field_type)
    {
        // Check password properties
        static::checkPasswordLength($password);

        if ($field_type === static::PASSWORD_ID) // get by id
        {
            $user = User::getFromID($field_value);
        }
        elseif ($field_type === static::PASSWORD_USERNAME) // get by username
        {
            $user = User::getFromUserName($field_value);
        }
        else
        {
            throw new UserException(_h("Invalid validation field type"));
        }

        // db password
        $db_password_hash = $user->getPassword();

        // password is salted
        if (Util::isPasswordSalted($db_password_hash))
        {
            return $db_password_hash;
        }

        // password is not hashed
        return hash("sha256", $password);
    }

    /**
     * @param string $box
     * @param string $message
     *
     * @return mixed
     * @throws UserException
     */
    public static function checkbox($box, $message)
    {
        if ($box !== 'on')
        {
            throw new UserException($message);
        }

        return $box;
    }

    /**
     * @param string $string
     *
     * @return bool
     * @throws Exception
     */
    public static function versionString($string)
    {
        if (!preg_match('/^(svn|[\d]+\.[\d]+\.[\d](-rc[\d])?)$/i', $string))
        {
            throw new UserException(_h('Invalid version string! Format should be: W.X.Y[-rcZ]'));
        }

        return true;
    }

    /**
     * Check if an array has multiple keys, return the error messages
     *
     * @param array $pool   the array to check agains
     * @param array $params the keys to check
     *
     * @return array the error array
     */
    public static function ensureInput(array $pool, array $params)
    {
        $errors = array();

        foreach ($params as $param)
        {
            if (empty($pool[$param]))
            {

                $errors[] = sprintf("%s is empty", ucfirst($param));
            }
        }

        return $errors;
    }
}
