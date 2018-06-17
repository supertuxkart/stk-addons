<?php
/**
 * Copyright 2017 SuperTuxKart-Team
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

/**
 * Class URL contains helper functions for URL manipulation
 */
class URL
{
    /**
     * This function is convenient when encoding a string to be used in a query part of a URL, as a convenient way to
     * pass variables to the next page.
     *
     * @param string $str The string to be encoded.
     *
     * @return string the url encode string
     */
    public static function encode($str)
    {
        return urlencode($str);
    }

    /**
     * Decodes any %## encoding in the given string. Plus symbols ('+') are decoded to a space character.
     *
     * @param string $str The string to be decoded.
     *
     * @return string Returns the decoded string.
     */
    public static function decode($str)
    {
        return urldecode($str);
    }

    /**
     * Gets/Converts a string query to an hash map array
     *
     * @param string $query
     *
     * @return array
     */
    public static function queryStringToArray($query)
    {
        // build vars
        $vars = [];
        parse_str($query, $vars);

        return $vars;
    }

    /**
     * Generates a URL-encoded query string from the associative (or indexed) array provided.
     *
     * @param array  $query_data     it may be a simple one-dimensional structure, or an array of arrays (which in turn
     *                               may contain other arrays).
     * @param string $numeric_prefix If numeric indices are used in the base array and this parameter is provided, it
     *                               will be prepended to the numeric index for elements in the base array only.
     *
     * @return string
     */
    public static function queryArrayToString(array $query_data, $numeric_prefix = null)
    {
        return $numeric_prefix ? http_build_query($query_data, $numeric_prefix) : http_build_query($query_data);
    }

    /**
     * Removes an item or list from the query string.
     *
     * @param string[] $keys Query key or keys to remove.
     * @param string   $url  The url to remove them from
     *
     * @return string
     */
    public static function removeQueryArguments(array $keys, $url)
    {
        $parsed = parse_url($url);
        $url = rtrim($url, "?&");

        // the query is empty
        if (empty($parsed["query"]))
        {
            return $url . "?";
        }

        $vars = static::queryStringToArray($parsed["query"]);

        // remove query
        foreach ($keys as $key)
        {
            unset($vars[$key]);
        }

        $query = empty($vars) ? "" : static::queryArrayToString($vars) . "&";
        $new_url = $parsed["scheme"] . "://" . $parsed["host"] . $parsed["path"] . "?" . $query;

        return $new_url;
    }

    /**
     * Get the url address currently displayed
     *
     * @param bool $request_params      retrieve the url tih the GET params
     * @param bool $request_script_name retrieve the url with only the script name
     *
     * Possible usage: getCurrent(true, false) - the default, get the full url
     *                 getCurrent(false, true) - get the url without the GET params only the script name
     *                 getCurrent(false, false) - get the url's directory path only
     *
     * @return string
     */
    public static function getCurrent($request_params = true, $request_script_name = false)
    {
        // begin buildup
        $page_url = "http";

        // add for ssl secured connections
        if (Util::isHTTPS())
        {
            $page_url .= "s";
        }
        $page_url .= "://";

        // find the end part of the url
        if ($request_params) // full url with requests
        {
            $end_url = $_SERVER["REQUEST_URI"];
        }
        else if ($request_script_name) // full url without requests
        {
            $end_url = $_SERVER["SCRIPT_NAME"];
        }
        else // url directory path
        {
            $end_url = dirname($_SERVER["SCRIPT_NAME"]) . "/";
        }

        // add host
        $page_url .= $_SERVER["SERVER_NAME"];

        if ((int)$_SERVER["SERVER_PORT"] !== 80)
        {
            $page_url .= ":" . $_SERVER["SERVER_PORT"] . $end_url;
        }
        else
        {
            $page_url .= $end_url;
        }

        return $page_url;
    }

    /**
     * Modify an the internal link using the apache_rewrites config from the database
     *
     * @param string $link
     *
     * @return string the rewrote link
     */
    public static function rewriteFromConfig($link)
    {
        // Don't rewrite external links
        $has_prefix =
            Util::isHTTPS() ? (mb_substr($link, 0, 8) === 'https://') : (mb_substr($link, 0, 7) === 'http://');

        if ($has_prefix && mb_substr($link, 0, mb_strlen(ROOT_LOCATION)) !== ROOT_LOCATION)
        {
            return $link;
        }

        $link = str_replace(ROOT_LOCATION, null, $link);
        $rules = Config::get(Config::APACHE_REWRITES);
        $rules = preg_split('/(\\r)?\\n/', $rules);

        foreach ($rules as $rule)
        {
            // Check for invalid lines
            if (!preg_match('/^([^\ ]+) ([^\ ]+)( L)?$/i', $rule, $parts))
            {
                continue;
            }

            // Check rewrite regular expression
            $search = '@' . $parts[1] . '@i';
            $new_link = $parts[2];
            if (!preg_match($search, $link, $matches))
            {
                continue;
            }

            $matches_count = count($matches);
            for ($i = 1; $i < $matches_count; $i++)
            {
                $new_link = str_replace('$' . $i, $matches[$i], $new_link);
            }
            $link = $new_link;

            if (isset($parts[3]) && ($parts[3] === ' L'))
            {
                break;
            }
        }

        return ROOT_LOCATION . $link;
    }
}
