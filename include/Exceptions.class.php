<?php
/**
 * copyright 2011      Stephen Just <stephenjust@users.sf.net>
 *           2014-2015 Daniel Butum <danibutum at gmail dot com>
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
 * Class DBException
 */
class DBException extends Exception
{
    /**
     * @param string $error_code
     */
    public function __construct($error_code = "")
    {
        $this->error_code = $error_code;
    }

    /**
     * @return string
     */
    public function getErrorCode()
    {
        return $this->error_code;
    }
}

class BaseException extends Exception {}
class BugException extends BaseException {}
class UserException extends BaseException {}
class VerificationException extends UserException {}
class ValidateException extends UserException {}
class AddonException extends BaseException {}

class FileException extends Exception {}
class UploadException extends Exception {}
class NewsException extends  Exception {}
class RatingsException extends Exception {}
class TemplateException extends Exception {}
class ServerException extends Exception {}
class LogException extends Exception {}
class CacheException extends Exception {}
class StatisticException extends Exception {}
class AchievementException extends Exception {}
class FriendException extends Exception {}
class AccessControlException extends Exception {}
class SImageException extends Exception {}
class SMailException extends Exception {}

class ParserException extends Exception {}
class XMLParserException extends ParserException {}
class B3DException extends ParserException {}

class ClientSessionException extends Exception {}
class ClientSessionConnectException extends ClientSessionException {}
class ClientSessionExpiredException extends ClientSessionException {}
