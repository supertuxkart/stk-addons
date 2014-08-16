<?php
/**
 * copyright 2011 Stephen Just <stephenjust@users.sf.net>
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

class BaseException extends Exception {}
class BugException extends BaseException {}
class UserException extends BaseException {}
class AddonException extends BaseException {}

class ValidateException extends Exception {}
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
