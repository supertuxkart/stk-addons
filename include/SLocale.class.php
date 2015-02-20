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

    const LANG_KEY = "lang";

    /**
     * Array of supported languages, format is:
     * language code, flag image y-offset, flag label
     * @var array
     */
    private static $languages = [
        ['en_US', -560, 'GD'],
        ['fr_FR', -160, 'FR'],
        ['de_DE', -240, 'DE'],
        ['es_ES', -520, 'ES'],
        ['it_IT', -360, 'IT'],
        ['nl_NL', -400, 'NL'],
        ['ru_RU', -480, 'RU'],
        ['zh_TW', -120, 'ZH'],
        //['pt_PT', -440, 'PT'],
        ['pt_BR', -40,  'PT'],
        ['ga_IE', -320, 'GA'],
        ['gl_ES', -200, 'GL'],
        ['id_ID', -280, 'ID'],
        ['eu_ES', -0,   'EU'],
        ['ca_ES', -80,  'CA'],
    ];

    /**
     * Create the locale object
     *
     * @param string $locale optional
     */
    public function __construct($locale = null)
    {
        $cookie_lang = !empty($_COOKIE[static::LANG_KEY]) ? $_COOKIE[static::LANG_KEY] : null;
        $get_lang = !empty($_GET[static::LANG_KEY]) ? $_GET[static::LANG_KEY] : null;

        if (!$locale && $get_lang) // set the locale from the get params
        {
            $locale = $get_lang;
        }
        elseif ($cookie_lang) // set the locale from the cookies
        {
            $locale = $cookie_lang;
        }
        else
        {
            $locale = "en_US"; // default locale
        }

        if (!static::isLocale($locale)) // locale is invalid, fallback to default
        {
            $locale = "en_US";
        }

        static::setLocale($locale);
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
        foreach (static::$languages as $lang)
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
        $cookie_lang = !empty($_COOKIE[static::LANG_KEY]) ? $_COOKIE[static::LANG_KEY] : null;

        // Set cookie
        putenv("LC_ALL=$locale.UTF-8");
        if (setlocale(LC_ALL, $locale . ".UTF-8") === false)
        {
            trigger_error(sprintf("Set locale has failed for '%s'. No localization is possible", $locale));
        }

        // change language cookie for next request only if language is different
        if ($cookie_lang !== $locale)
        {
            if(!setcookie(static::LANG_KEY, $locale, time() + static::COOKIE_LIFETIME, "/"))
            {
                trigger_error("Failed to set locale language cookie");
            }
            $_COOKIE[static::LANG_KEY] = $locale;
        }

        // Set translation file info
        bindtextdomain($domain, ROOT_PATH . 'locale');
        textdomain($domain);
        bind_textdomain_codeset($domain, 'UTF-8');

        define('LANG', $locale);
    }
}
