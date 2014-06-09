<?php
/**
 * copyright 2013 Glenn De Jonghe
 *           2013 Stephen Just <stephenjust@users.sourceforge.net>
 *           2014 Daniel Butum <danibutum at gmail dot com>
 * This file is part of SuperTuxKart
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
 * Class DBException
 */
class DBException extends Exception
{
    /**
     * @param string $error_code
     */
    public function __construct($error_code = "")
    {
        $this->error_code = $error_code;
    }

    /**
     * @return string
     */
    public function getErrorCode()
    {
        return $this->error_code;
    }
}

/**
 * Class DBConnection
 */
class DBConnection
{

    /**
     * @var PDO
     */
    private $conn;

    /**
     * @var bool
     */
    private $in_transaction = false;

    /**
     * @var DBConnection
     */
    private static $instance;

    // Faking enumeration
    const ROW_COUNT = 1;

    const FETCH_ALL = 2;

    const FETCH_FIRST = 3;

    const FETCH_FIRST_COLUMN = 4;

    const NOTHING = 99;

    // Alias for PDO Constants
    const PARAM_BOOL = PDO::PARAM_BOOL;

    const PARAM_INT = PDO::PARAM_INT;

    const PARAM_NULL = PDO::PARAM_NULL;

    const PARAM_STR = PDO::PARAM_STR;

    /**
     * The constructor
     */
    private function __construct()
    {
        $this->conn = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASSWORD);
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->conn->exec("set names utf8");
    }

    /**
     * Get the DBConnection singleton
     *
     * @return \DBConnection
     */
    public static function get()
    {
        if (!static::$instance)
        {
            static::$instance = new DBConnection();
        }

        return static::$instance;
    }

    /**
     * Start a database transaction
     *
     * @return bool
     */
    public function beginTransaction()
    {
        if (!$this->in_transaction)
        {
            $this->in_transaction = $this->conn->beginTransaction();
        }

        return $this->in_transaction;

    }

    /**
     * Commit the current transaction if we are in one
     *
     * @return bool
     */
    public function commit()
    {
        if ($this->in_transaction)
        {
            $this->in_transaction = !$this->conn->commit();

            return !$this->in_transaction;
        }
        if (DEBUG_MODE)
        {
            echo "Did a commit while not having a transaction running!";
        }

        return false;
    }

    /**
     * Perform a rollback (undo) on the current transaction
     *
     * @return bool
     */
    public function rollback()
    {
        if ($this->in_transaction)
        {
            $this->in_transaction = !$this->conn->rollback();

            return !$this->in_transaction;
        }
        if (DEBUG_MODE)
        {
            echo "Did a rollback while not having a transaction running!";
        }

        return false;
    }

    /**
     * Perform a query on the database
     *
     * @param string $query
     * @param int    $return_type
     * @param array  $params     An associative array having mapping between variables for prepared statements and values
     * @param array  $data_types variables in prepared statement for which data type should be explicitly mentioned
     *
     * @throws DBException
     *
     * @return array|int|null depending of the return type
     */
    public function query(
        $query,
        $return_type = DBConnection::NOTHING,
        array $params = array(),
        array $data_types = array()
    ) {
        if (empty($query))
        {
            throw new DBException("Empty Query");
        }
        try
        {
            $sth = $this->conn->prepare($query);

            foreach ($params as $key => $param)
            {
                if (array_key_exists($key, $data_types))
                {
                    $sth->bindValue($key, $param, $data_types[$key]);
                }
                else
                {
                    $sth->bindValue($key, $param);
                }
            }
            $sth->execute();

            if ($return_type === static::NOTHING)
            {
                return null;
            }
            if ($return_type === static::ROW_COUNT)
            {
                return $sth->rowCount();
            }
            if ($return_type === static::FETCH_ALL)
            {
                return $sth->fetchAll(PDO::FETCH_ASSOC);
            }
            if ($return_type === static::FETCH_FIRST)
            {
                return $sth->fetch(PDO::FETCH_ASSOC);
            }
            if ($return_type === static::FETCH_FIRST_COLUMN)
            {
                return $sth->fetchColumn();
            }
        }
        catch(PDOException $e)
        {
            if ($this->in_transaction)
            {
                $success = $this->conn->rollback();
                if (DEBUG_MODE && !$success)
                {
                    echo "A PDO exception occurred during during a transaction, but the rollback failed";
                }
            }
            if (DEBUG_MODE)
            {
                echo "Database Error";
                var_dump($e->errorInfo);
                printf(
                    "SQLSTATE ERR: %s<br>\nmySQL ERR: %s<br>\nMessage: %s<br>\nQuery: %s<br>",
                    $e->errorInfo[0],
                    $e->errorInfo[1],
                    isset($e->errorInfo[2]) ? $e->errorInfo[2] : "",
                    $query
                );
                echo "Params: <br>";
                var_dump($params);
            }
            throw new DBException($e->errorInfo[0]);
        }

        throw new DBException("Unexpected reach of end of query()");
    }

    /**
     * Get the last id inserted into the database
     *
     * @return string
     */
    public function lastInsertId()
    {
        return $this->conn->lastInsertId();
    }

    /**
     * Insert data into the database. helper method
     *
     * @param string $table
     * @param array  $fields_data an associative array in which the key is the column and the value is the actual value
     *                            example: "name" => "daniel", "id" => 23
     * @param array $other_data data that is not prepared (can be a constant value, a mysql function, etc)
     *
     * @throws DBException
     */
    public function insert($table, array $fields_data, array $other_data = array())
    {
        if (empty($table) or empty($fields_data))
        {
            throw new DBException("Empty table or data");
        }

        // associative array for preparing the data
        $prepared_pairs = array();

        foreach ($fields_data as $field => $value)
        {
            // :field => value
            $prepared_pairs[":" . $field] = $value;
        }

        $columns = array_merge(array_keys($fields_data), array_keys($other_data));
        $values = array_merge(array_keys($prepared_pairs), array_values($other_data));

        // build the sql query
        $query = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            DB_PREFIX . $table,
            '`' . implode("`, `", $columns) . '`', // use back ticks for reserved mysql keywords
            implode(", ", $values)
        );

        $this->query($query, static::NOTHING, $prepared_pairs);
    }

    /**
     * Perform an sql count on the database, much faster than PDO::rowCount()
     *
     * @param string $table
     * @param string $where_statement the sql where part
     * @param array  $prepared_pairs  pairs to parse to pdo
     * @param array  $data_types      data types for the prepared statements
     *
     * @throws DBException
     * @return int the count statement
     */
    public function count($table, $where_statement = "", array $prepared_pairs = array(), array $data_types = array())
    {
        if (!$table or empty($where_statement))
        {
            throw new DBException("Empty table or data");
        }

        if (empty($where_statement))
        {
            $query = sprintf(
                "SELECT COUNT(*) FROM %s",
                DB_PREFIX . $table
            );
        }
        else
        {
            $query = sprintf(
                "SELECT COUNT(*) FROM %s WHERE %s",
                DB_PREFIX . $table,
                $where_statement
            );
        }

        return (int)$this->query($query, static::FETCH_FIRST_COLUMN, $prepared_pairs, $data_types);
    }
}
