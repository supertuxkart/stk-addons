<?php
/**
 * copyright 2013 Glenn De Jonghe
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
 * Class Verification
 */
class Verification
{
    /**
     * Verifies a supplied verification code.
     *
     * @param int    $userid
     * @param string $ver_code
     *
     * @throws UserException when verification failed
     */
    public static function verify($userid, $ver_code)
    {
        try
        {
            $count = DBConnection::get()->query(
                "SELECT `userid`
    	        FROM `" . DB_PREFIX . "verification`
    	        WHERE `userid` = :userid
                AND `code` = :code",
                DBConnection::ROW_COUNT,
                array(
                    ':userid' => $userid,
                    ':code'   => $ver_code
                )
            );
        }
        catch(DBException $e)
        {
            throw new UserException(htmlspecialchars(
                _('An error occurred while trying to validate verification information.') . ' ' .
                _('Please contact a website administrator.')
            ));
        }
        if ($count !== 1)
        {
            throw new UserException(_(
                "Verification failed. Either the supplied user doesn't exist,"
                . "the account doesn't need verification (anymore), or the verification code is incorrect."
            ));
        }
    }

    /**
     * Deletes an entry from the verification table
     *
     * @param int $userid
     *
     * @throws DBException when nothing got deleted.
     */
    public static function delete($userid)
    {
        $count = DBConnection::get()->query(
            "DELETE
            FROM `" . DB_PREFIX . "verification`
    	    WHERE `userid` = :userid",
            DBConnection::ROW_COUNT,
            array(
                ':userid' => $userid
            )
        );
        if ($count == 0)
        {
            throw new DBException();
        }
    }

    /**
     * Generates and insert a verification code for the user with supplied user id
     *
     * @param int $userid
     *
     * @throws DBException
     * @return string the generated verification code
     */
    public static function generate($userid)
    {
        $verification_code = cryptUrl(12);
        $count = DBConnection::get()->query(
            "INSERT INTO `" . DB_PREFIX . "verification` (`userid`,`code`)
            VALUES(:userid, :code)
            ON DUPLICATE KEY UPDATE code = :code",
            DBConnection::ROW_COUNT,
            array(
                ':userid' => (int)$userid,
                ':code'   => (string)$verification_code
            )
        );
        if ($count == 0)
        {
            throw new DBException();
        }

        return $verification_code;
    }
}
