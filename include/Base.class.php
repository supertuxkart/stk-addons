<?php

/**
 * copyright 2012        Stephen Just <stephenjust@users.sf.net>
 *           2014 - 2016 Daniel Butum <danibutum at gmail dot com>
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
interface IBaseException
{
    /**
     * Get this class exception, this is overridden in classes who implement this
     * @return BaseException
     */
    static function getException();
}

/**
 * Abstract base class to all primitives
 */
abstract class Base implements IBaseException
{
    /**
     * Throw a custom exception
     *
     * @param string $message
     * @param int    $code [optional] The Exception code. Default error code is UNKNOWN
     *
     * @throws BaseException
     */
    private static final function throwException($message, $code = ErrorType::UNKNOWN)
    {
        throw static::getException()->setMessage($message)->setCode($code);
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
            $message =
                sprintf(_h("The %s must be between %s and %s characters long"), $field_name, $min_field, $max_field);
            static::throwException($message, ErrorType::VALIDATE_NOT_IN_CHAR_RANGE);
        }
    }

    /**
     * Get an object data from a field
     *
     * @param string      $query          the select query
     * @param string      $field          the from field
     * @param mixed       $value          the value of the field that must match
     * @param int         $value_type     the PDO var type
     * @param string      $empty_message  custom message on empty database
     * @param string|null $prepared_field optional name for the prepared field
     *
     * @return array the data from the database
     * @throws mixed
     */
    protected static function getFromField(
        $query,
        $field,
        $value,
        $value_type = DBConnection::PARAM_STR,
        $empty_message = "The abstract values does not exist",
        $prepared_field = null
    ) {
        if (!$prepared_field)
        {
            $prepared_field = ":" . $field;
        }

        $data = [];
        try
        {
            $data = DBConnection::get()->query(
                $query . " WHERE " . sprintf("%s = %s", $field, $prepared_field) . " LIMIT 1",
                DBConnection::FETCH_FIRST,
                [$prepared_field => $value],
                [$prepared_field => $value_type] // bind value
            );
        }
        catch (DBException $e)
        {
            static::throwException(exception_message_db(_("retrieve the singleton")), ErrorType::DB_GET_ROW);
        }

        // empty result
        if (!$data)
        {
            static::throwException($empty_message, ErrorType::DB_EMPTY_RESULT);
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
        catch (DBException $e)
        {
            static::throwException(
                exception_message_db(sprintf(_("see if a '%s' exists."), $table)),
                ErrorType::DB_FIELD_EXISTS
            );
        }

        return $count !== 0;
    }

    /**
     * Get all the data from the database with pagination support
     *
     * @param string $query
     * @param int    $limit        number of retrievals, -1 for all
     * @param int    $current_page the current page
     *
     * @return array
     */
    protected static function getAllFromTable($query, $limit = -1, $current_page = 1)
    {
        $data = [];
        if ($current_page <= 0) $current_page = 1;

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
        catch (DBException $e)
        {
            static::throwException(exception_message_db(_("select all data from table")), ErrorType::DB_GET_ALL);
        }

        return $data;
    }
}