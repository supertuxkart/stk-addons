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