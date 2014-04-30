<?php
/**
 * copyright 2013 Stephen Just <stephenjust@users.sf.net>
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

// Note that PHP has a built-in Locale class in the newest versions of PHP
class SLocale {
    
    /**
     * Array of supported languages, format is:
     * language code, flag image x-offset, flag image y-offset, flag label
     * @var array
     */
    private static $languages = array(
        array('en_US',0,0,'EN'),
        array('ca_ES',-96,-99,'CA'),
        array('de_DE',0,-33,'DE'),
        array('es_ES',-96,-66,'ES'),
        array('eu_ES', -144, -66, 'EU'),
        array('fr_FR',0,-66,'FR'),
        array('ga_IE',0,-99,'GA'),
        array('gd_GB',-144, -33,'GD'),
        array('gl_ES',-48,0,'GL'),
        array('id_ID',-48,-33,'ID'),
        array('it_IT',-96,-33,'IT'),
        array('nl_NL',-48,-66,'NL'),
        array('pt_BR',-144,0,'PT'),
        array('ru_RU',-48,-99,'RU'),
        array('zh_TW',-96,0,'ZH (T)')
        );
    
    private static $cookie_lifetime = 31536000; // One year
    
    /**
     * Create the locale object
     * @param string $locale
     */
    public function __construct($locale = NULL) {
        if ($locale == NULL && isset($_GET['lang']) && strlen($_GET['lang']) != 0) {
            $locale = $_GET['lang'];
        } elseif ($locale == NULL && isset($_COOKIE['lang'])) {
            $locale = $_COOKIE['lang'];
        } elseif ($locale == NULL)
            $locale = "en_US";

        if (!SLocale::IsValid($locale))
            die("Invalid locale");
        
        SLocale::SetLocale($locale);
    }
    
    /**
     * Check if locale is a valid value
     * @param string $locale
     * @return boolean
     */
    public static function IsValid($locale) {
        foreach (SLocale::$languages AS $lang) {
            if ($locale == $lang[0]) return true;
        }
        return false;
    }
    
    /**
     * Set page locale
     * @param string $locale Locale string - input should already be checked
     */
    private static function SetLocale($locale) {
        // Set cookie
        setcookie('lang', $locale, time() + SLocale::$cookie_lifetime);
        putenv("LC_ALL=$locale.UTF-8");
        setlocale(LC_ALL, "$locale.UTF-8");
        $_COOKIE['lang'] = $locale;

        // Set translation file info
        bindtextdomain('translations', ROOT.'locale');
        textdomain('translations');
        bind_textdomain_codeset('translations', 'UTF-8');

        if (!defined('LANG'))
            define('LANG', $locale);
    }
}
