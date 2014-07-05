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
        $result = DBConnection::get()->query(
            "SELECT `id`
	        FROM `" . DB_PREFIX . "users`
	        WHERE `user` = :username
            AND `email` = :email
            AND `active` = 1",
            DBConnection::FETCH_ALL,
            array(
                ':username' => $username,
                ':email'    => $email
            )
        );

        if (empty($result))
        {
            throw new UserException(_h('Username and email address combination not found.'));
        }
        if (count($result) > 1)
        {
            throw new UserException(_h("Multiple accounts with the same username and email combination."));
        }

        return $result[0]['id'];
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
            throw new UserException(h(sprintf(_('"%s" is not a valid email address.'), $email)));
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
        if (mb_strlen($username) < 4)
        {
            throw new UserException(_h('Your username must be at least 4 characters long.'));
        }
        if (!preg_match('/^[a-z0-9]+$/i', $username))
        {
            throw new UserException(_h('Your username can only contain alphanumeric characters.'));
        }

        return h($username);
    }

    /**
     * @param string      $password1
     * @param null|string $password2
     * @param null|string $username
     * @param null|int    $userid
     *
     * @return string
     * @throws UserException
     */
    public static function password($password1, $password2 = null, $username = null, $userid = null)
    {
        // TODO make method more sane
        // Check password properties
        if (strlen($password1) < 8)
        {
            throw new UserException(_h('Your password must be at least 8 characters long.'));
        }
        if ($password2 !== null)
        {
            if ($password1 !== $password2)
            {
                throw new UserException(_h('Your passwords do not match.'));
            }
        }

        // Salt password
        $salt_length = 32;
        if ($username === null && $userid === null) // no info provided
        {
            $salt = md5(uniqid(null, true));
        }
        else
        {
            // Get current user password entry to get salt
            try
            {
                if ($userid === null)
                {
                    $result = DBConnection::get()->query(
                        "SELECT `pass` 
            	        FROM `" . DB_PREFIX . "users`
            	        WHERE `user` = :username",
                        DBConnection::FETCH_FIRST,
                        array(
                            ':username' => $username
                        )
                    );
                }
                else
                {
                    $result = DBConnection::get()->query(
                        "SELECT `pass`
            	        FROM `" . DB_PREFIX . "users`
            	        WHERE `id` = :userid",
                        DBConnection::FETCH_FIRST,
                        array(
                            ':userid' => (int)$userid
                        )
                    );
                }
            }
            catch(DBException $e)
            {
                throw new UserException(
                    _h('An error occurred trying to validate your password.') . ' ' .
                    _h('Please contact a website administrator.')
                );
            }

            if (empty($result))
            {
                $salt = md5(uniqid(null, true));
            }
            else
            {
                if (strlen($result['pass']) === 64)
                {
                    // Not a salted password
                    return hash('sha256', $password1);
                }
                $salt = substr($result['pass'], 0, $salt_length);
            }
        }

        return $salt . hash('sha256', $salt . $password1);
    }

    /**
     * @param string $name
     *
     * @return string
     * @throws UserException
     */
    public static function realName($name)
    {
        if (strlen(trim($name)) < 2)
        {
            throw new UserException(_h('You must enter a name.'));
        }

        return h(trim($name));
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
     * Check if the input is a valid alphanumeric username
     *
     * @param string $username Alphanumeric username
     * @param string $password unhashed password
     *
     * @throws UserException
     * @return array associative with user information from the database
     */
    public static function credentials($username, $password)
    {
        try
        {
            $result = DBConnection::get()->query(
                "SELECT `id`,`user`,`pass`,`name`,`role`
                FROM `" . DB_PREFIX . "users`
                WHERE `user` = :username AND `pass` = :pass",
                DBConnection::FETCH_ALL,
                array(
                    ':username' => static::username($username),
                    ':pass'     => static::password($password, null, $username)
                )
            );
        }
        catch(UserException $e)
        {
            throw new UserException($e->getMessage());
        }
        catch(PDOException $e)
        {
            throw new UserException(
                _h('An error occurred while signing in.') . ' ' .
                _h('Please contact a website administrator.')
            );
        }

        return $result;
    }

    /**
     * Check if an array has multiple keys, return the error messages
     *
     * @param array $pool the array to check agains
     * @param array $params the keys to check
     *
     * @return array the error array
     */
    public static function ensureInput(array $pool, array $params)
    {
        $errors = array();

        foreach($params as $param)
        {
            if(empty($pool[$param]))
            {

                $errors[] = sprintf("%s is empty", ucfirst($param));
            }
        }

        return $errors;
    }
}
