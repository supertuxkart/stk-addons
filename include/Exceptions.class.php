<?php
/**
 * copyright 2011        Stephen Just <stephenjust@users.sf.net>
 *           2014 - 2016 Daniel Butum <danibutum at gmail dot com>
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
 * along with stkaddons. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Class DBException
 */


class BaseException extends Exception {}

class DBException extends BaseException {}
class BugException extends BaseException {}

class UserException extends BaseException {}
class VerificationException extends UserException {}
class ValidateException extends UserException {}


class AddonException extends BaseException {}

class FileException extends BaseException {}
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

