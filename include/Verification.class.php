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
     * @throws VerificationException when verification failed
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
                [
                    ':userid' => $userid,
                    ':code'   => $ver_code
                ],
                [':userid' => DBConnection::PARAM_INT]
            );
        }
        catch(DBException $e)
        {
            throw new VerificationException(exception_message_db(_('validate verification information')));
        }
        if ($count !== 1)
        {
            throw new VerificationException(_h(
                "Verification failed. Either the supplied user doesn't exist, the account doesn't need verification (anymore), or the verification code is incorrect."
            ));
        }
    }

    /**
     * Deletes an entry from the verification table
     *
     * @param int $userid
     *
     * @throws VerificationException when nothing got deleted.
     */
    public static function delete($userid)
    {
        try
        {
            $count = DBConnection::get()->delete(
                "verification",
                "`userid` = :userid",
                [':userid' => $userid],
                [':userid' => DBConnection::PARAM_INT]
            );
        }
        catch(DBException $e)
        {
            throw new VerificationException(exception_message_db(_("delete verification entry")));
        }


        if (!$count)
        {
            throw new VerificationException("No verify entry got deleted");
        }
    }

    /**
     * Generates and insert a verification code for the user with supplied user id
     *
     * @param int $userid
     *
     * @throws VerificationException
     * @return string the generated verification code
     */
    public static function generate($userid)
    {
        try
        {
            $verification_code = Util::getRandomString(12);
            DBConnection::get()->query(
                "INSERT INTO `" . DB_PREFIX . "verification` (`userid`,`code`)
                VALUES(:userid, :code)
                ON DUPLICATE KEY UPDATE code = :code",
                DBConnection::ROW_COUNT,
                [
                    ':userid' => $userid,
                    ':code'   => $verification_code
                ],
                [':userid' => DBConnection::PARAM_INT]
            );
        }
        catch(DBException $e)
        {
            throw new VerificationException(exception_message_db(_("generate verification entry")));
        }

        return $verification_code;
    }
}
