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
    ini_set("display_errors", "On");
    ini_set("html_errors", "On");
}

// Paths and locations
define("DS", DIRECTORY_SEPARATOR);
define("ROOT_PATH", __DIR__ . DS);
define("INCLUDE_PATH", ROOT_PATH . "include" . DS);
define("TPL_PATH", ROOT_PATH . "tpl" . DS . "default" . DS); // Template properties
define("TMP_PATH", sys_get_temp_dir() . DS); // define temporary directory path
define("UPLOAD_PATH", ROOT_PATH . "dl" . DS);
define("UPLOAD_CRON_PATH", UPLOAD_PATH);
define("BUGS_PATH", ROOT_PATH . "bugs" . DS);
define("STATS_PATH", ROOT_PATH . "stats" . DS);
define("ASSETS_PATH", ROOT_PATH . "assets" . DS);
define("CACHE_PATH", ASSETS_PATH . "cache" . DS); // cache for images/html/template
define("FONTS_PATH", ASSETS_PATH . "fonts" . DS);

if (defined("CRON")) // this is a cron job
{
    define("UP_PATH", UPLOAD_CRON_PATH);
}
else
{
    define("UP_PATH", UPLOAD_PATH);
}

define("NEWS_XML_PATH", UP_PATH . "xml" . DS . "news.xml");
define("ASSETS_XML_PATH", UP_PATH . "xml" . DS . "assets.xml");
define("ASSETS2_XML_PATH", UP_PATH . "xml" . DS . "assets2.xml");

// make sure that this ends with a trailing slash, and does not have a prefix in front
$ROOT_LOCATION = "addons.supertuxkart.net/";

if (isset($_SERVER["HTTPS"]) && $_SERVER['HTTPS'] === "on")
{
    define("ROOT_LOCATION", "https://" . $ROOT_LOCATION);
}
else
{
    define("ROOT_LOCATION", "http://" . $ROOT_LOCATION);
}

define("DOWNLOAD_LOCATION", ROOT_LOCATION . "dl/");
define("DOWNLOAD_XML_LOCATION", DOWNLOAD_LOCATION . "xml/");
define("DOWNLOAD_ASSETS_LOCATION", DOWNLOAD_LOCATION);
define("NEWS_XM_LOCATION", DOWNLOAD_XML_LOCATION . "news.xml");
define("ASSETS_XML_LOCATION", DOWNLOAD_XML_LOCATION . "assets.xml");
define("ASSETS2_XML_LOCATION", DOWNLOAD_XML_LOCATION . "assets.xml");
define("BUGS_LOCATION", ROOT_LOCATION . "bugs/");
define("STATS_LOCATION", ROOT_LOCATION . "stats/");
define("ASSETS_LOCATION", ROOT_LOCATION . "assets/");
define("CACHE_LOCATION", ASSETS_LOCATION . "cache/");
define("LIBS_LOCATION", ASSETS_LOCATION . "libs/");
define("IMG_LOCATION", ASSETS_LOCATION . "img/");
define("JS_LOCATION", ASSETS_LOCATION . "js/");
define("CSS_LOCATION", ASSETS_LOCATION . "css/");

// CAPTCHA properties
define("CAPTCHA_PUB", ""); // reCAPTCHA public key
define("CAPTCHA_PRIV", ""); // reCAPTCHA private key

// Database proprieties
define("DB_USER", "root");
define("DB_PASSWORD", "");
define("DB_NAME", "stkbase");
define("DB_HOST", "localhost");
define("DB_PREFIX", "v2_"); // should not be modified

// Mail proprieties
define("IS_SMTP", true); // true for "smtp" and false for "sendmail"
define("SENDMAIL_PATH", null); // Path to sendmail if your sendmail path is not standard
define("SMTP_HOST", null); // SMTP server host
define("SMTP_PORT", null); // SMTP server port (usually 25)
define("SMTP_PREFIX", "ssl"); // usually ssl or tls
define("SMTP_AUTH", null); // Whether or not to use SMTP authentication, true/false
define("SMTP_USER", null); // SMTP username
define("SMTP_PASS", null); // SMTP password

// Add-On Flags
//
// Do not change existing flags! Doing so will cause errors with existing add-ons, and possible game incompatibility.
// To add new flags, create a new constant, and set it to the next power of 2. The current database schema allows 24 flags.
define('F_APPROVED', 1);
define('F_ALPHA', 2);
define('F_BETA', 4);
define('F_RC', 8);
define('F_INVISIBLE', 16);
define('F_RESERVED2', 32); // Reserved for future use
define('F_DFSG', 64);
define('F_FEATURED', 128);
define('F_LATEST', 256);
define('F_TEX_NOT_POWER_OF_2', 512);

// API
// this should be changed depending where you have the api, for api.stkaddons.net is should be empty string
define("API_LOCATION", "/stkaddons/api");
define("API_VERSION", "v2");

// set string encoding
if (mb_internal_encoding("UTF-8") !== true)
{
    trigger_error("mb_internal_encoding failed");
}
if (mb_regex_encoding("UTF-8") !== true)
{
    trigger_error("mb_regex_encoding failed");
}
if (mb_language("uni") !== true)
{
    trigger_error("mb_language failed");
}

// add composer autoload
require_once(ROOT_PATH . "vendor" . DS . "autoload.php");
