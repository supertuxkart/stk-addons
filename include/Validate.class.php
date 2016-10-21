<?php
/**
 * copyright 2011      Stephen Just <stephenjust@users.sf.net>
 *           2013      Glenn De Jonghe
 *           2014-2015 Daniel Butum <danibutum at gmail dot com>
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
 * Class to contain all common validation functions, the user validation functions are in the User class
 */
class Validate
{
    /**
     * @param string $box
     * @param string $message
     *
     * @return mixed
     * @throws ValidateException
     */
    public static function checkbox($box, $message)
    {
        if ($box !== 'on')
        {
            throw new ValidateException($message);
        }

        return $box;
    }

    /**
     * Validate the version string
     *
     * @param string $string
     *
     * @throws ValidateException
     */
    public static function versionString($string)
    {
        if (!preg_match('/^(svn|[\d]+\.[\d]+\.[\d](-rc[\d])?)$/i', $string))
        {
            throw new ValidateException(_h('Invalid version string! Format should be: W.X.Y[-rcZ]'));
        }
    }

    /**
     * Validator singleton
     *
     * @param array $data
     *
     * @link https://github.com/vlucas/valitron
     * @return \Valitron\Validator
     */
    public static function get($data)
    {
        return new Valitron\Validator($data);
    }

    /**
     * Check if an array has the keys in $params and must be not empty
     *
     * @param array $pool   the array to check
     * @param array $params the keys to check
     *
     * @return array the error array
     */
    public static function ensureNotEmpty(array $pool, array $params)
    {
        $errors = [];

        foreach ($params as $param)
        {
            if (empty($pool[$param]))
            {
                $errors[] = sprintf(_h("%s field is empty"), ucfirst($param));
            }
        }

        return $errors;
    }

    /**
     * Check if an array has the keys in $params
     *
     * @param array $pool   the array to check
     * @param array $params the keys to check
     *
     * @return array the error array
     */
    public static function ensureIsSet(array $pool, array $params)
    {
        $errors = [];

        foreach ($params as $param)
        {
            if (!isset($pool[$param]))
            {
                $errors[] = sprintf(_h("%s field is not set"), ucfirst($param));
            }
        }

        return $errors;
    }
}
