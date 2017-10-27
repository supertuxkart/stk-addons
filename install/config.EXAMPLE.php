<?php
/**
 * Copyright        2009 Lucas Baudin <xapantu@gmail.com>
 *           2011 - 2014 Stephen Just <stephenjust@gmail.com>
 *           2014 - 2016 Daniel Butum <danibutum at gmail dot com>
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

// Make sure that this does not end with a trailing slash, and does not have a prefix in front
$ROOT_LOCATION = 'addons.supertuxkart.net';

// WARNING!!!! turn OFF in the production server.
// Enable this when you want detailed debugging output.
// WARNING!!!! turn OFF in the production server.
define('DEBUG_MODE', false);

// Enable maintenance mode, will disable all requests and redirect to an HTML page
define('MAINTENANCE_MODE', false);

// Indicate if the certificate is signed by an authority
define('IS_SSL_CERTIFICATE_VALID', false);

// Enable the debug toolbar, will only work when in DEBUG_MODE.
// WARNING!!! Never enable in the production server
define('DEBUG_TOOLBAR', false);

// set default values
ini_set('html_errors', 'On');
if (DEBUG_MODE)
{
    // This does not show parse errors, to show those edit the php.ini file and edit the display_errors value
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}
else
{
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', 0);
}

// useful for phpunit testing
if (!defined('TEST_MODE')) define('TEST_MODE', false);
// useful for cron jobs
if (!defined('CRON_MODE')) define('CRON_MODE', false);
// useful for the API
if (!defined('API_MODE')) define('API_MODE', false);

// Paths on the local filesystem
define('DS', DIRECTORY_SEPARATOR);
define('ROOT_PATH', __DIR__ . DS);
define('INCLUDE_PATH', ROOT_PATH . 'include' . DS);
define('TPL_PATH', ROOT_PATH . 'tpl' . DS . 'default' . DS); // Template properties
define('TMP_PATH', sys_get_temp_dir() . DS); // define temporary directory path
define('UPLOAD_PATH', ROOT_PATH . 'dl' . DS);
define('UP_PATH', UPLOAD_PATH);
define('BUGS_PATH', ROOT_PATH . 'bugs' . DS);
define('STATS_PATH', ROOT_PATH . 'stats' . DS);
define('ASSETS_PATH', ROOT_PATH . 'assets' . DS);
define('CACHE_PATH', ASSETS_PATH . 'cache' . DS); // cache for images/html/template
define('FONTS_PATH', ASSETS_PATH . 'fonts' . DS);

define('NEWS_XML_PATH', UP_PATH . 'xml' . DS . 'news.xml');
define('ASSETS_XML_PATH', UP_PATH . 'xml' . DS . 'assets.xml');
define('ASSETS2_XML_PATH', UP_PATH . 'xml' . DS . 'assets2.xml');

// Location urls
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
{
    define('ROOT_LOCATION', 'https://' . $ROOT_LOCATION . '/');
}
else
{
    define('ROOT_LOCATION', 'http://' . $ROOT_LOCATION . '/');
}

define('DOWNLOAD_LOCATION', ROOT_LOCATION . 'dl/');
define('DOWNLOAD_XML_LOCATION', DOWNLOAD_LOCATION . 'xml/');
define('DOWNLOAD_ASSETS_LOCATION', DOWNLOAD_LOCATION);
define('NEWS_XM_LOCATION', DOWNLOAD_XML_LOCATION . 'news.xml');
define('ASSETS_XML_LOCATION', DOWNLOAD_XML_LOCATION . 'assets.xml');
define('ASSETS2_XML_LOCATION', DOWNLOAD_XML_LOCATION . 'assets.xml');
define('BUGS_LOCATION', ROOT_LOCATION . 'bugs/');
define('STATS_LOCATION', ROOT_LOCATION . 'stats/');
define('ASSETS_LOCATION', ROOT_LOCATION . 'assets/');
define('CACHE_LOCATION', ASSETS_LOCATION . 'cache/');
define('LIBS_LOCATION', ASSETS_LOCATION . 'libs/');
define('IMG_LOCATION', ASSETS_LOCATION . 'img/');
define('JS_LOCATION', ASSETS_LOCATION . 'js/');
define('CSS_LOCATION', ASSETS_LOCATION . 'css/');

// CAPTCHA properties, Register API keys at https://www.google.com/recaptcha/admin
define('CAPTCHA_SITE_KEY', '');
define('CAPTCHA_SECRET', '');

// Database proprieties
define('DB_USER', 'stk_addons');
define('DB_PASSWORD', '');
define('DB_NAME', 'stk_addons');
define('DB_HOST', 'localhost');
define('DB_PREFIX', 'v3_'); // should not be modified

// Mail proprieties
define('IS_SMTP', false); // true for 'smtp' and false for 'sendmail'
define('SENDMAIL_PATH', null); // Path to sendmail if your sendmail path is not standard
define('SMTP_HOST', null); // SMTP server host
define('SMTP_PORT', null); // SMTP server port (usually 25)
define('SMTP_PREFIX', 'ssl'); // usually ssl or tls
define('SMTP_AUTH', null); // Whether or not to use SMTP authentication, true/false
define('SMTP_USER', null); // SMTP username
define('SMTP_PASS', null); // SMTP password

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
// this should be changed depending where you have the api, for api.supertuxkart.net is should be empty string
// for addons.supertuxkart.net/api, this is the default location
define('API_LOCATION', '/api');
define('API_VERSION', 'v2');

// auto load stuff, when testing we do this manually
if (!TEST_MODE)
{
    // set string encoding
    if (mb_internal_encoding('UTF-8') !== true) trigger_error('mb_internal_encoding failed');
    if (mb_regex_encoding('UTF-8') !== true) trigger_error('mb_regex_encoding failed');
    if (mb_language('uni') !== true) trigger_error('mb_language failed');

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

// Aliases
class Assert extends \Webmozart\Assert\Assert {}
