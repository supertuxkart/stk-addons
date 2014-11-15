<?php
/**
 * copyright 2012 Stephen Just <stephenjust@users.sf.net>
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
 * Abstract base class to all primitives
 */
abstract class Base
{
    /**
     * Throw a custom exception
     *
     * @param string $message
     *
     * @throws BaseException
     */
    protected static function throwException($message)
    {
        throw new BaseException($message);
    }

    /**
     * Validate a field length
     *
     * @param string $field_name  the name of the field
     * @param mixed  $field_value the field value
     * @param int    $min_field   minimum allowed field length
     * @param int    $max_field   maximum allowed field length
     * @param bool   $allow_space flag that indicates whether to trim or not the field value
     * @param bool   $is_unicode  flag that indicates whether the field value is an utf-8 string or ascii
     *
     * @throws BaseException change it in throwException
     */
    protected static function validateFieldLength(
        $field_name,
        $field_value,
        $min_field,
        $max_field,
        $allow_space = false,
        $is_unicode = true
    ) {
        if (!$allow_space)
        {
            $field_value = trim($field_value);
        }

        if ($is_unicode)
        {
            $length = mb_strlen($field_value);
        }
        else
        {
            $length = strlen($field_value);
        }

        if ($length < $min_field || $length > $max_field)
        {
            $message = sprintf(_h("The %s must be between %s and %s characters long"), $field_name, $min_field, $max_field);
            static::throwException($message);
        }
    }

    /**
     * Get an object data from a field
     *
     * @param string $table         the table name
     * @param string $field         the from field
     * @param mixed  $value         the value of the field that must match
     * @param int    $value_type    the PDO var type
     * @param string $empty_message custom message on empty database
     *
     * @return array the data from the database
     * @throws mixed
     */
    protected static function getFromField(
        $table,
        $field,
        $value,
        $value_type = DBConnection::PARAM_STR,
        $empty_message = "The abstract values does not exist"
    ) {
        $data = [];
        try
        {
            $data = DBConnection::get()->query(
                "SELECT *
                FROM `" . DB_PREFIX . $table . "`
                WHERE " . sprintf("`%s` = :%s", $field, $field) . " LIMIT 1",
                DBConnection::FETCH_FIRST,
                [':' . $field => $value],
                [':' . $field => $value_type] // bind value
            );
        }
        catch(DBException $e)
        {
            $message =
                h(sprintf(_('An error occurred while retrieving the %s'), $table) . ' .' . _('Please contact a website administrator.'));
            static::throwException($message);
        }

        // empty result
        if (empty($data))
        {
            static::throwException($empty_message);
        }

        return $data;
    }

    /**
     * Verify if a value exists in the table
     *
     * @param string $table      the table name
     * @param string $field      the table field
     * @param mixed  $value      field value
     * @param int    $value_type type of the value
     *
     * @return bool
     */
    protected static function existsField($table, $field, $value, $value_type = DBConnection::PARAM_STR)
    {
        $count = 0;
        try
        {
            $count = DBConnection::get()->count(
                $table,
                sprintf("`%s` = :%s", $field, $field),
                [":" . $field => $value],
                [":" . $field => $value_type]
            );
        }
        catch(DBException $e)
        {
            $message = h(sprintf(_("Tried to see if a %s exists."), $table) . '. ' . _("Please contact a website administrator."));
            static::throwException($message);
        }

        return $count !== 0;
    }

    /**
     * Get all the data from the database
     *
     * @param string $table        the table name
     * @param string $order_by     the sql order clause
     * @param string $where        where statement
     * @param int    $limit        number of retrievals, -1 for all
     * @param int    $current_page the current page
     *
     * @return array
     */
    protected static function getAllFromTable($table, $order_by, $where = "", $limit = -1, $current_page = 1)
    {
        // build query
        $query = "SELECT * FROM `" . DB_PREFIX . $table . "`";
        if ($where)
        {
            $query .= sprintf(" WHERE %s", $where);
        }
        $query .= " " . $order_by;
        $data = [];

        try
        {
            if ($limit > 0) // get pagination
            {
                $offset = ($current_page - 1) * $limit;
                $query .= " LIMIT :limit OFFSET :offset";

                $data = DBConnection::get()->query(
                    $query,
                    DBConnection::FETCH_ALL,
                    [
                        ":limit"  => $limit,
                        ":offset" => $offset
                    ],
                    [
                        ":limit"  => DBConnection::PARAM_INT,
                        ":offset" => DBConnection::PARAM_INT
                    ]
                );
            }
            else // get all
            {
                $data = DBConnection::get()->query($query, DBConnection::FETCH_ALL);
            }
        }
        catch(DBException $e)
        {
            static::throwException(_h("Error on selecting all from table"));
        }

        return $data;
    }
}