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
     * @param string $password    unhashed password
     * @param mixed  $field_value the field value
     * @param int    $credential_type
     *
     * @throws UserException
     * @throws InvalidArgumentException
     * @return User
     */
    public static function credentials($password, $field_value, $credential_type)
    {
        // validate
        if ($credential_type === static::CREDENTIAL_ID)
        {
            $user = static::password($password, $field_value, static::PASSWORD_ID);
        }
        elseif ($credential_type === static::CREDENTIAL_USERNAME)
        {
            $field_value = static::username($field_value);
            $user = static::password($password, $field_value, static::PASSWORD_USERNAME);
        }
        else
        {
            throw new InvalidArgumentException("credential type is invalid");
        }

        return $user;
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

        if (mb_strlen($email) > User::MAX_EMAIL)
        {
            throw new UserException(_h("Email is to long."));
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
        $length = strlen($username); // username is alpha numeric, use normal strlen

        if ($length < User::MIN_USERNAME || $length > User::MAX_USERNAME)
        {
            throw new UserException(sprintf(
                _h('The username must be between %s and %s characters long'),
                User::MIN_USERNAME,
                User::MAX_USERNAME
            ));
        }

        if (!preg_match('/^[a-z0-9]+$/i', $username))
        {
            throw new UserException(_h('Your username can only contain alphanumeric characters'));
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
        $length = mb_strlen($name);

        if ($length < User::MIN_REALNAME || $length > User::MAX_REALNAME)
        {
            throw new UserException(sprintf(
                _h('The nam must be between %s and %s characters long'),
                User::MIN_REALNAME,
                User::MAX_REALNAME
            ));
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
        $length = mb_strlen($password);

        if ($length < User::MIN_PASSWORD || $length > User::MAX_PASSWORD)
        {
            throw new UserException(sprintf(
                _h('The password must be between %s and %s characters long'),
                User::MIN_PASSWORD,
                User::MAX_PASSWORD
            ));
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
     * @return User
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

        // the field value exists, so something about the user is true
        $db_password_hash = $user->getPassword();

        // verify if password is correct
        if (Util::isPasswordSalted($db_password_hash)) // password is salted
        {
            $salt = Util::getSaltFromPassword($db_password_hash);

            if (Util::getPasswordHash($password, $salt) !== $db_password_hash)
            {
                throw new UserException(_h("Invalid password"));
            }
        }
        else // not salted
        {
            if ($db_password_hash !== hash("sha256", $password))
            {
                throw new UserException(_h("Invalid password"));
            }
        }

        return $user;
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
     * Validator singleton
     *
     * @param array $data
     *
     * @return \Valitron\Validator
     */
    public static function get($data)
    {
        return new Valitron\Validator($data);
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
