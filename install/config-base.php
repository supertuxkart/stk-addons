<?php
/**
 * Copyright        2009 Lucas Baudin <xapantu@gmail.com>
 *           2011 - 2014 Stephen Just <stephenjust@gmail.com>
 *                  2014 Daniel Butum <danibutum at gmail dot com>
 *
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

define("DEBUG_MODE", true); // FIXME turn off on server.
if (DEBUG_MODE)
{
    error_reporting(E_ALL);
    ini_set('display_errors', 'On');
    ini_set('html_errors', 'On');
}

// paths
define("DS", DIRECTORY_SEPARATOR);
define("ROOT_PATH", __DIR__ . DS);
define("INCLUDE_PATH", ROOT_PATH . 'include' . DS);
define("TPL_PATH", ROOT_PATH . 'tpl' . DS . 'default' . DS); // Template properties (Fixme: define this in user prefs)
define("TMP_PATH", sys_get_temp_dir() . DS); // define temporary directory path
define("CACHE_PATH", ROOT_PATH . 'assets' . DS . 'temp' . DS);
define("UPLOAD_PATH", ROOT_PATH . 'upload' . DS);
define("UPLOAD_CRON_PATH", UPLOAD_PATH);
define("BUGS_PATH", ROOT_PATH . "bugs" . DS);

// CAPTCHA properties
define('CAPTCHA_PUB', ''); // reCAPTCHA public key
define('CAPTCHA_PRIV', ''); // reCAPTCHA private key

// Database proprieties
define("DB_USER", 'root');
define("DB_PASSWORD", 'pass');
define("DB_NAME", 'stkbase');
define("DB_PREFIX", 'v2_');
define("DB_HOST", 'localhost:3306');

// Mail proprieties
define('MAIL_METHOD', 'sendmail'); // 'smtp' or 'sendmail' supported
define('SENDMAIL_PATH', '/usr/bin/sendmail'); // Path to sendmail
define('SENDMAIL_ARGS', '-i'); // Sendmail arguments
define('SMTP_HOST', null); // SMTP server host
define('SMTP_PORT', null); // SMTP server port (usually 25)
define('SMTP_AUTH', null); // Whether or not to use SMTP authentication, true/false
define('SMTP_USER', null); // SMTP username
define('SMTP_PASS', null); // SMTP password

if (!defined('CRON'))
{
    define("UP_PATH", UPLOAD_PATH);
}
else
{
    define("UP_PATH", UPLOAD_CRON_PATH);
}

// make sure that this ends with a trailing slash, otherwise it would break a few things (like the activation email)
define("SITE_ROOT", "http://stkaddons.net/");
define("DOWNLOAD_LOCATION", SITE_ROOT . 'upload/');
define("CACHE_LOCATION", SITE_ROOT . 'assets/temp/');
define("BUGS_LOCATION", SITE_ROOT . 'bugs/');

define("NEWS_XM_LOCATION", DOWNLOAD_LOCATION . "xml/news.xml");
define("ASSETS_XML_LOCATION", DOWNLOAD_LOCATION . "xml/assets.xml");

define("NEWS_XML_PATH", UP_PATH . "xml" . DS . "news.xml");
define("ASSETS_XML_PATH", UP_PATH . "xml" . DS . "assets.xml");

define("IMG_LOCATION", SITE_ROOT . 'assets/img/');
define("JS_LOCATION", SITE_ROOT . 'assets/js/');
define("CSS_LOCATION", SITE_ROOT . 'assets/css/');

// add composer autoload
require_once(ROOT_PATH . 'vendor' . DS . 'autoload.php');
