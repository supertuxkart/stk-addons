<?php
/**
 * copyright 2013      Glenn De Jonghe
 *           2014-2015 Daniel Butum <danibutum at gmail dot com>
 * This file is part of stk-addons.
 *
 * stk-addons is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * stk-addons is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with stk-addons. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Class Verification
 */
class Verification
{
    /**
     * Verifies a supplied verification code.
     *
     * @param int    $user_id
     * @param string $ver_code
     *
     * @throws VerificationException when verification failed
     */
    public static function verify($user_id, $ver_code)
    {
        try
        {
            $count = DBConnection::get()->query(
                "SELECT `user_id`
    	        FROM `{DB_VERSION}_verification`
    	        WHERE `user_id` = :user_id
                AND `code` = :code",
                DBConnection::ROW_COUNT,
                [
                    ':user_id' => $user_id,
                    ':code'    => $ver_code
                ],
                [':user_id' => DBConnection::PARAM_INT]
            );
        }
        catch (DBException $e)
        {
            throw new VerificationException(exception_message_db(_('validate verification information')));
        }
        if ($count !== 1)
        {
            throw new VerificationException(
                _h(
                    "Verification failed. Either the supplied user doesn't exist, the account doesn't need verification (anymore), or the verification code is incorrect."
                )
            );
        }
    }

    /**
     * Deletes an entry from the verification table
     *
     * @param int $user_id
     *
     * @throws VerificationException when nothing got deleted.
     */
    public static function delete($user_id)
    {
        try
        {
            $count = DBConnection::get()->delete(
                "verification",
                "`user_id` = :user_id",
                [':user_id' => $user_id],
                [':user_id' => DBConnection::PARAM_INT]
            );
        }
        catch (DBException $e)
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
     * @param int $user_id
     *
     * @throws VerificationException
     * @return string the generated verification code
     */
    public static function generate($user_id)
    {
        try
        {
            $verification_code = Util::getRandomString(12);
            DBConnection::get()->query(
                "INSERT INTO `{DB_VERSION}_verification` (`user_id`,`code`)
                VALUES(:user_id, :code)
                ON DUPLICATE KEY UPDATE code = :code",
                DBConnection::ROW_COUNT,
                [
                    ':user_id' => $user_id,
                    ':code'    => $verification_code
                ],
                [':user_id' => DBConnection::PARAM_INT]
            );
        }
        catch (DBException $e)
        {
            throw new VerificationException(exception_message_db(_("generate verification entry")));
        }

        return $verification_code;
    }

    /**
     * Daily cron job, to delete non activated users and old verifications
     *
     * @param int $days
     *
     * @throws VerificationException
     */
    public static function cron($days)
    {
        // delete all users that have a verification and did not activate
        // their account in the last $days or more
        try
        {
            DBConnection::get()->query(
                "DELETE U
                FROM `{DB_VERSION}_verification` V
                INNER JOIN `{DB_VERSION}_users` U
                    ON V.user_id = U.id
                WHERE
                    is_active = 0
                AND
                    DATEDIFF(CURDATE(), U.date_register) >= :days",
                DBConnection::NOTHING,
                [":days" => $days],
                [":days" => DBConnection::PARAM_INT]
            );

        }
        catch (DBException $e)
        {
            throw new VerificationException($e->getMessage());
        }

        // delete old verification queries, because sometimes we can activate users manually, from the user panel
        try
        {
            DBConnection::get()->query(
                "DELETE V
                FROM `{DB_VERSION}_verification` V
                INNER JOIN `{DB_VERSION}_users` U
                    ON V.user_id = U.id
                WHERE
                    is_active = 1
                AND
                    DATEDIFF(CURDATE(), U.date_register) >= :days",
                DBConnection::NOTHING,
                [":days" => $days],
                [":days" => DBConnection::PARAM_INT]
            );

        }
        catch (DBException $e)
        {
            throw new VerificationException($e->getMessage());
        }
    }
}
