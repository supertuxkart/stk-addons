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
            $user = static::checkPassword($password, $field_value, static::PASSWORD_ID);
        }
        elseif ($credential_type === static::CREDENTIAL_USERNAME)
        {
            User::validateUserName($field_value);
            $field_value = h($field_value);

            $user = static::checkPassword($password, $field_value, static::PASSWORD_USERNAME);
        }
        else
        {
            throw new InvalidArgumentException("credential type is invalid");
        }

        if (!$user->isActive())
        {
            throw new UserException(_h("Your account is not active"));
        }

        return $user;
    }

    /**
     * Check the password length and check it against the database
     *
     * @param string $password
     * @param string $field_value
     * @param int    $field_type
     *
     * @return User
     * @throws UserException
     * @throws InvalidArgumentException when the $field_type is invalid
     */
    protected static function checkPassword($password, $field_value, $field_type)
    {
        // Check password properties
        User::validatePassword($password);

        try
        {
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
                throw new InvalidArgumentException(_h("Invalid validation field type"));
            }
        }
        catch (UserException $e)
        {
            throw new UserException(_h("Username or password is invalid"));
        }

        // the field value exists, so something about the user is true
        $db_password_hash = $user->getPassword();

        // verify if password is correct
        $salt = Util::getSaltFromPassword($db_password_hash);
        if (Util::getPasswordHash($password, $salt) !== $db_password_hash)
        {
            throw new UserException(_h("Username or password is invalid"));
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
     * Validate the version string
     *
     * @param string $string
     *
     * @throws ValidateException
     */
    public static function versionString($string)
    {
        if (!preg_match('/^(svn|[\d]+\.[\d]+\.[\d](-rc[\d])?)$/i', $string))
        {
            throw new ValidateException(_h('Invalid version string! Format should be: W.X.Y[-rcZ]'));
        }
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
     * Check if an array has the keys in $params and must be not empty
     *
     * @param array $pool   the array to check
     * @param array $params the keys to check
     *
     * @return array the error array
     */
    public static function ensureNotEmpty(array $pool, array $params)
    {
        $errors = [];

        foreach ($params as $param)
        {
            if (empty($pool[$param]))
            {
                $errors[] = sprintf(_h("%s field is empty"), ucfirst($param));
            }
        }

        return $errors;
    }

    /**
     * Check if an array has the keys in $params
     *
     * @param array $pool   the array to check
     * @param array $params the keys to check
     *
     * @return array the error array
     */
    public static function ensureIsSet(array $pool, array $params)
    {
        $errors = [];

        foreach ($params as $param)
        {
            if (!isset($pool[$param]))
            {
                $errors[] = sprintf(_h("%s field is not set"), ucfirst($param));
            }
        }

        return $errors;
    }
}
