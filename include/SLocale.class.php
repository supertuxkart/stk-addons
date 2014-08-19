<?php
/**
 * copyright 2013 Stephen Just <stephenjust@users.sf.net>
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

/**
 * Class SLocale
 * Note that PHP has a built-in Locale class in the newest versions of PHP
 */
class SLocale
{
    /**
     * Cookie lifetime in seconds representing a year
     * @var int
     */
    const COOKIE_LIFETIME = 31536000;

    /**
     * Array of supported languages, format is:
     * language code, flag image y-offset, flag label
     * @var array
     */
    private static $languages = [
        ['eu_ES', -0,   'EU'],
        ['pt_BR', -40,  'PT'],
        ['ca_ES', -80,  'CA'],
        ['zh_TW', -120, 'ZH'],
        ['fr_FR', -160, 'FR'],
        ['gl_ES', -200, 'GL'],
        ['de_DE', -240, 'DE'],
        ['id_ID', -280, 'ID'],
        ['ga_IE', -320, 'GA'],
        ['it_IT', -360, 'IT'],
        ['nl_NL', -400, 'NL'],
        ['pt_PT', -440, 'PT'],
        ['ru_RU', -480, 'RU'],
        ['es_ES', -520, 'ES'],
        ['en_US', -560, 'GD']
    ];

    /**
     * Create the locale object
     *
     * @param string $locale optional
     */
    public function __construct($locale = null)
    {
        // default locale
        $locale = "en_US";

        if (!$locale && !empty($_GET['lang']))
        {
            $locale = $_GET['lang'];
        }
        elseif (!empty($_COOKIE['lang']))
        {
            $locale = $_COOKIE['lang'];
        }

        if (!SLocale::isLocale($locale))
        {
            $locale = "en_US";
        }

        SLocale::setLocale($locale);
    }

    /**
     * Get all the supported translate languages
     *
     * @return array
     */
    public static function getLanguages()
    {
        return static::$languages;
    }

    /**
     * Check if locale is a valid value
     *
     * @param string $locale
     *
     * @return bool
     */
    public static function isLocale($locale)
    {
        foreach (SLocale::$languages as $lang)
        {
            if ($locale === $lang[0])
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Set page locale
     *
     * @param string $locale Locale string - input should already be checked
     */
    private static function setLocale($locale)
    {
        $domain = 'translations';

        // Set cookie
        header('Content-Type: text/html; charset=utf-8');
        setcookie('lang', $locale, time() + static::COOKIE_LIFETIME);
        putenv("LC_ALL=$locale.UTF-8");
        if (setlocale(LC_ALL, $locale . ".UTF-8") === false)
        {
            trigger_error("Set locale has failed. No localization is possible");
        }
        $_COOKIE['lang'] = $locale;

        // Set translation file info
        bindtextdomain($domain, ROOT_PATH . 'locale');
        textdomain($domain);
        bind_textdomain_codeset($domain, 'UTF-8');

        define('LANG', $locale);
    }
}
