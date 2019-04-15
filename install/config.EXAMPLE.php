<?php
/**
 * Copyright 2009 - 2017 SuperTuxKart-Team
 *
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
declare(strict_types=1);

// useful for phpunit testing
if (!defined('TEST_MODE')) define('TEST_MODE', false);
// useful for cron jobs
if (!defined('CRON_MODE')) define('CRON_MODE', false);
// useful for the API
if (!defined('API_MODE')) define('API_MODE', false);
// useful for to know if downloading file
if (!defined('DOWNLOAD_MODE')) define('DOWNLOAD_MODE', false);

// Redirect access from web browser to secure server
// NOTE: Do not redirect API requests or download requests as Old STK versions can't handle proper certificates
const REDIRECT_TO_HTTPS_SERVER_NAME = false;
const STK_HTTPS_SERVER_NAME = "https://online.supertuxkart.net";
if (!API_MODE && !DOWNLOAD_MODE && REDIRECT_TO_HTTPS_SERVER_NAME)
{
    header("Location: " . STK_HTTPS_SERVER_NAME . $_SERVER['REQUEST_URI'], true, 307);
    exit;
}

// WARNING!!!! turn OFF in the production server.
// Enable this when you want detailed debugging output.
// WARNING!!!! turn OFF in the production server.
define('DEBUG_MODE', false);

// Enable the debug toolbar, will only work when in DEBUG_MODE.
// WARNING!!! Never enable in the production server
define('DEBUG_TOOLBAR', false);

// Enable maintenance mode, will disable all requests and redirect to an HTML page
define('MAINTENANCE_MODE', false);

// Indicate if the certificate is signed by an authority
const IS_SSL_CERTIFICATE_VALID = false;

// Do you prefer https?
const PREFER_SSL = true;

// set default values
ini_set('html_errors', 'On');
if (DEBUG_MODE)
{
    // This does not show parse errors, to show those edit the php.ini file and edit the display_errors value
    error_reporting(E_ALL);
    ini_set('display_errors', "true");
}
else
{
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', "false");
}

if (empty($_SERVER['SERVER_NAME']) || empty($_SERVER['SERVER_PORT']))
{
    // NOTE: We can't access the server name variable in cron mode
    // Make sure that this does not end with a trailing slash, and does not have a prefix in front
    $DOMAIN_NAME = 'stk-addons.localhost';
}
else
{
    $DOMAIN_NAME = $_SERVER['SERVER_NAME'] . (!in_array($_SERVER['SERVER_PORT'], ['80', '443'], true) ? ':' . $_SERVER['SERVER_PORT'] : '');
}

// Cron constants

// After how many days should we delete the verification emails
const CRON_DAILY_VERIFICATION_DAYS = 1;

// Paths on the local filesystem
const DS = DIRECTORY_SEPARATOR;
const ROOT_PATH = __DIR__ . DS;
const INCLUDE_PATH = ROOT_PATH . 'include' . DS;
const TPL_PATH = ROOT_PATH . 'tpl' . DS . 'default' . DS; // Template properties
define('TMP_PATH', sys_get_temp_dir() . DS); // define temporary directory path
const UPLOAD_PATH = ROOT_PATH . 'dl' . DS;
const UP_PATH = UPLOAD_PATH;
const BUGS_PATH = ROOT_PATH . 'bugs' . DS;
const STATS_PATH = ROOT_PATH . 'stats' . DS;
const ASSETS_PATH = ROOT_PATH . 'assets' . DS;
const CACHE_PATH = ASSETS_PATH . 'cache' . DS; // cache for images/html/template
const FONTS_PATH = ASSETS_PATH . 'fonts' . DS;

const NEWS_XML_PATH = UP_PATH . 'xml' . DS . 'news.xml';
const NEWS_V2_XML_PATH = UP_PATH . 'xml' . DS . 'news_v2.xml';
const ASSETS_XML_PATH = UP_PATH . 'xml' . DS . 'assets.xml';
const ASSETS_V2_XML_PATH = UP_PATH . 'xml' . DS . 'assets_v2.xml';
const ASSETS2_XML_PATH = UP_PATH . 'xml' . DS . 'assets2.xml';

// Location urls
define('ROOT_LOCATION_UNSECURE', 'http://' . $DOMAIN_NAME . '/');
if ((PREFER_SSL && IS_SSL_CERTIFICATE_VALID) || (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'))
{
    define('ROOT_LOCATION', 'https://' . $DOMAIN_NAME . '/');
}
else
{
    define('ROOT_LOCATION', ROOT_LOCATION_UNSECURE);
}

// Change this if you want downloads to be from another server
const DOWNLOAD_LOCATION_ROOT_SERVER = ROOT_LOCATION;

const DOWNLOAD_LOCATION = DOWNLOAD_LOCATION_ROOT_SERVER . 'dl/';
const DOWNLOAD_XML_LOCATION = DOWNLOAD_LOCATION . 'xml/';
const NEWS_XM_LOCATION = DOWNLOAD_XML_LOCATION . 'news.xml';

const ASSETS_XML_LOCATION = DOWNLOAD_XML_LOCATION . 'assets.xml';
const ASSETS2_XML_LOCATION = DOWNLOAD_XML_LOCATION . 'assets.xml';

const BUGS_LOCATION = ROOT_LOCATION . 'bugs/';
const STATS_LOCATION = ROOT_LOCATION . 'stats/';
const ASSETS_LOCATION = ROOT_LOCATION . 'assets/';
const CACHE_LOCATION = ASSETS_LOCATION . 'cache/';
const LIBS_LOCATION = ASSETS_LOCATION . 'libs/@bower_components/';
const IMG_LOCATION = ASSETS_LOCATION . 'img/';
const JS_LOCATION = ASSETS_LOCATION . 'js/';
const CSS_LOCATION = ASSETS_LOCATION . 'css/';

// CAPTCHA properties, Register API keys at https://www.google.com/recaptcha/admin
const CAPTCHA_SITE_KEY = null;
const CAPTCHA_SECRET = null;

// Database properties
const DB_USER = 'stk_addons';
const DB_PASSWORD = 'your super secret password';
const DB_NAME = 'stk_addons';
const DB_HOST = 'localhost';
// should not be modified
const DB_VERSION = 'v3';

// Mail properties
const IS_SMTP = false; // true for 'smtp' and false for 'sendmail'
const SENDMAIL_PATH = null; // Path to sendmail if your sendmail path is not standard
const SMTP_HOST = null; // SMTP server host
const SMTP_PORT = null; // SMTP server port (usually 25)
const SMTP_PREFIX = 'ssl'; // usually ssl or tls
const SMTP_AUTH = null; // Whether or not to use SMTP authentication, true/false
const SMTP_USER = null; // SMTP username
const SMTP_PASS = null; // SMTP password

// Add-On Flags
//
// Do not change existing flags! Doing so will cause errors with existing add-ons, and possible game incompatibility.
// To add new flags, create a new constant, and set it to the next power of 2. The current database schema allows 24 flags.
const F_APPROVED = 1;
const F_ALPHA = 2;
const F_BETA = 4;
const F_RC = 8;
const F_INVISIBLE = 16;
const F_RESERVED2 = 32; // Reserved for future use
const F_DFSG = 64;
const F_FEATURED = 128;
const F_LATEST = 256;
const F_TEX_NOT_POWER_OF_2 = 512;

// API
// this should be changed depending where you have the api, for api.supertuxkart.net is should be empty string
// for online.supertuxkart.net/api, this is the default location
const API_LOCATION = '/api';
const API_VERSION = 'v2';

// auto load stuff, when testing we do this manually
if (!TEST_MODE)
{
    // set string encoding
    if (mb_internal_encoding('UTF-8') !== true) user_error('mb_internal_encoding failed');
    if (mb_regex_encoding('UTF-8') !== true) user_error('mb_regex_encoding failed');
    if (mb_language('uni') !== true) user_error('mb_language failed');

    // disable external entity loading
    libxml_disable_entity_loader(true);

    // Maintenance mode
    if (MAINTENANCE_MODE)
    {
        if (API_MODE) // handle API
        {
            require(INCLUDE_PATH . 'XMLOutput.class.php');
            XMLOutput::exitXML('Server is down for maintenance. More details at ' . ROOT_LOCATION);
        }
        else
        {
            require(TPL_PATH . 'maintenance.html');
            exit;
        }
    }
    else // normal mode
    {
        // add composer autoload
        require_once(ROOT_PATH . 'vendor' . DS . 'autoload.php');

        // add nice error handling https://filp.github.io/whoops/
        if (DEBUG_MODE)
        {
            $whoops = new \Whoops\Run;
            $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
            $whoops->register();
        }
    }
}
