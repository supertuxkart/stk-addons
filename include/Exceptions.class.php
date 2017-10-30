<?php
/**
 * copyright 2011        Stephen Just <stephenjust@users.sf.net>
 *           2014 - 2016 Daniel Butum <danibutum at gmail dot com>
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


class ErrorType extends \MyCLabs\Enum\Enum
{
    const UNKNOWN = 0;

    const DB_CONNECT = 32;          // can not connect to the database
    const DB_SET_ATTRIBUTE = 33;    // cannot set an attribute
    const DB_GENERIC = 34;          // generic error happened
    const DB_FIELD_EXISTS = 35;     // trying to see if a value exists in the database see Base::existsField
    const DB_GET_ROW = 36;          // trying to get a result row from the database, see Base::getFromField
    const DB_EMPTY_RESULT = 37;     // empty result returned from database
    const DB_GET_ALL = 38;          // trying to get all values from database, see Base::getAllFromTable

    const USER_DB_EXCEPTION = 64;           // a generic database exception occurred while querying some user data
    const USER_ADDON_TYPE_NOT_EXIST = 65;   // addon type does not exist for user
    const USER_VALID_SESSION = 66;          // invalid session
    const USER_SEARCH = 67;                 // while user searching
    const USER_COUNT = 68;                  // while counting number of users
    const USER_INVALID_PERMISSION = 69;     // invalid permission
    const USER_UPDATE_PROFILE = 70;         // while updating profile
    const USER_INVALID_ROLE = 71;           // invalid role
    const USER_UPDATE_ROLE = 72;            // while updating role
    const USER_UPDATE_LAST_LOGIN = 73;      // update last login
    const USER_CHANGE_PASSWORD = 74;        // while changing password
    const USER_ACTIVATE_ACCOUNT = 75;       // while activating account
    const USER_SENDING_RECOVER_EMAIL = 76;  // sending recover email
    const USER_CREATE_ACCOUNT = 77;         // while creating account
    const USER_SENDING_CREATE_EMAIL = 78;   // while sending create account email
    const USER_INACTIVE_ACCOUNT = 79;       // account is not active

    const VALIDATE_NOT_IN_CHAR_RANGE = 500;             // string is not in min/max char string range
    const VALIDATE_PASSWORDS_MATCH = 501;               // passwords do not match

    const VALIDATE_USERNAME = 530;                      // username is not made of proper length and alphanumeric chars
    const VALIDATE_USERNAME_NOT_EXISTS = 531;           // username does not exist
    const VALIDATE_USERNAME_TAKEN = 532;                // username is already taken (it exists *ahem*)
    const VALIDATE_USERNAME_OR_PASSWORD = 533;          // username or password is invalid
    const VALIDATE_USERNAME_AND_EMAIL = 534;            // username and email not found
    const VALIDATE_MULTIPLE_USERNAME_AND_EMAIL = 535;   // multiple accounts with the same username and email combination

    const VALIDATE_EMAIL_NOT_EXISTS = 540;              // email does not exist
    const VALIDATE_EMAIL_TAKEN = 541;                   // email is already taken
    const VALIDATE_EMAIL_LONG = 542;                    // email too long
    const VALIDATE_EMAIL = 543;                         // not a valid email address

    const VALIDATE_HOMEPAGE_URL = 550;                  // homepage is not a valid url
    const VALIDATE_HOMEPAGE_LONG = 551;                 // homepage is too long
}


class BaseException extends Exception
{
    /**
     * Construct the exception.
     * @link http://php.net/manual/en/exception.construct.php
     * @param string $message [optional] The Exception message to throw.
     * @param int $code [optional] The Exception code. Default error code is UNKNOWN
     * @param Exception $previous [optional] The previous exception used for the exception chaining.
     */
    public function __construct($message = "", $code = ErrorType::UNKNOWN, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Factory method
     * @param string $message [optional] The Exception message to throw.
     * @param int $code [optional] The Exception code. Default error code is UNKNOWN
     * @param Exception $previous [optional] The previous exception used for the exception chaining.
     * @return static
     */
    public static function get($message = "", $code = ErrorType::UNKNOWN, Exception $previous = null)
    {
        return new static($message, $code, $previous);
    }

    /**
     * @param mixed $message
     * @return static
     */
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * @param mixed $code
     * @return static
     */
    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }

    /**
     * @param mixed $file
     * @return static
     */
    public function setFile($file)
    {
        $this->file = $file;
        return $this;
    }

    /**
     * @param mixed $line
     * @return static
     */
    public function setLine($line)
    {
        $this->line = $line;
        return $this;
    }
}

class DBException extends BaseException
{
    /**
     * SQLSTATE error code (a five characters alphanumeric identifier defined in the ANSI SQL standard).
     * @var string
     */
    private $sql_error_code;

    /**
     * @return string
     */
    public function getSqlErrorCode()
    {
        return $this->sql_error_code;
    }

    /**
     * @param string $sql_error_code
     * @return DBException
     */
    public function setSqlErrorCode($sql_error_code)
    {
        $this->sql_error_code = $sql_error_code;
        return $this;
    }
}

class BugException extends BaseException {}

class UserException extends BaseException {}
class VerificationException extends UserException {}
class ValidateException extends UserException {}


class AddonException extends BaseException {}

class FileException extends BaseException {}
class FileSystemException extends BaseException {}
class UploadException extends BaseException {}
class NewsException extends  BaseException {}
class RatingsException extends BaseException {}
class TemplateException extends BaseException {}
class ServerException extends BaseException {}
class LogException extends BaseException {}
class CacheException extends BaseException {}
class StatisticException extends BaseException {}
class AchievementException extends BaseException {}
class FriendException extends BaseException {}
class AccessControlException extends BaseException {}
class SImageException extends BaseException {}
class SMailException extends BaseException {}

class ParserException extends BaseException {}
class XMLParserException extends ParserException {}
class B3DException extends ParserException {}

class ClientSessionException extends BaseException {}
class ClientSessionConnectException extends ClientSessionException {}
class ClientSessionExpiredException extends ClientSessionException {}

