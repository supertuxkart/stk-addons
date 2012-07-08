<?php
/* copyright 2009 Lucas Baudin <xapantu@gmail.com>                 
                                                                              
 This file is part of stkaddons.                                 
                                                                              
 stkaddons is free software: you can redistribute it and/or      
 modify it under the terms of the GNU General Public License as published by  
 the Free Software Foundation, either version 3 of the License, or (at your   
 option) any later version.                                                   
                                                                              
 stkaddons is distributed in the hope that it will be useful, but
 WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for    
 more details.                                                                
                                                                              
 You should have received a copy of the GNU General Public License along with 
 stkaddons.  If not, see <http://www.gnu.org/licenses/>.   */

$dirUpload = "/media/serveur/stkaddons/upload/";
$dirUploadCron = $dirUpload;
$dirBase = "http://127.0.0.1/stkaddons/";
$dirDownload = $dirBase."upload/";

$style="default";
$admin = "yourname@example.com";

// CAPTCHA properties
define('CAPTCHA_PUB',''); // reCAPTCHA public key
define('CAPTCHA_PRIV',''); // reCAPTCHA private key

// Template properties (Fixme: define this in user prefs)
define('TPL_PATH', 'tpl/default/');

define("DB_USER", 'root');
define("DB_PASSWORD", 'pass');
define("DB_NAME", 'stkbase');
define("DB_PREFIX", '');
define("DB_HOST", 'localhost');
if (!defined('CRON'))
    define("UP_LOCATION", $dirUpload);
else
    define("UP_LOCATION", $dirUploadCron);
define("DOWN_LOCATION", $dirDownload);
define("SITE_ROOT", "http://stkaddons.tuxfamily.org/");
define("CACHE_DIR", ROOT.'assets/temp/');
define("CACHE_DL", $dirBase.'assets/temp/');
define("NEWS_XML",  DOWN_LOCATION."xml/news.xml");
define("ASSET_XML", DOWN_LOCATION."xml/assets.xml");
define("NEWS_XML_LOCAL", UP_LOCATION."xml/news.xml");
define("ASSET_XML_LOCAL", UP_LOCATION."xml/news.xml");
define("JPG_ROOT",ROOT);
?>