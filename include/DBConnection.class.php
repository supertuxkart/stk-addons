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
     * Flag to see if we currently in a sql transaction
     * @var bool
     */
    private $in_transaction = false;

    /**
     * @var DBConnection
     */
    private static $instance;

    // Fake enumeration
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
        $this->conn = new PDO(sprintf("mysql:host=%s;dbname=%s;charset=utf8mb4", DB_HOST, DB_NAME), DB_USER, DB_PASSWORD);
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Build the parameters for the query method
     * This method takes an array(eg: [":name" => "Daniel", "date" => "NOW()"] and builds 2 additional arrays
     * The keys that start with a ':' are escaped (are added to the $prepared_pairs, which in turn will be parsed to PDO).
     * The other keys that are NOT prefixed with ':' will just be constant values (no escaping will be done)
     *
     * @param array $fields_data        associative array that maps column to value
     * @param array $prepared_pairs     return associative array for preparing the data
     *                                  Example of output: [":name" => "Daniel"]
     * @param array $column_value_pairs associative array of column => value pairs
     *                                  Example of output: ["name" => ":name", "date" => "17 May 2014"]
     *                                  As shown in the example above the fields that are named parameters will have the column
     *                                  normal as the rest (the unescaped params) but the value will be the named parameter itself
     */
    private static function buildQueryParams(array $fields_data, array &$prepared_pairs, array &$column_value_pairs = [])
    {
        // In our context field = column
        foreach ($fields_data as $field => $value)
        {
            if ($field[0] === ":") // prepare this field
            {
                // :field => value
                $prepared_pairs[$field] = $value;

                // remove : from the beginning
                $key = ltrim($field, ":");

                // column => :value
                $column_value_pairs[$key] = $field;
            }
            else // non prepared field
            {
                // column => value
                $column_value_pairs[$field] = $value;
            }
        }
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

        trigger_error("Did a commit while not having a transaction running!");

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

        trigger_error("Did a rollback while not having a transaction running!");

        return false;
    }

    /**
     * Perform a query on the database
     *
     * @param string $query          The sql string
     * @param int    $return_type    The type of return. Use the class constants
     * @param array  $prepared_pairs An associative array having mapping between variables for prepared statements and values
     * @param array  $data_types     variables in prepared statement for which data type should be explicitly mentioned
     *
     * @throws DBException
     *
     * @return array|int|null depending of the return type
     */
    public function query(
        $query,
        $return_type = DBConnection::NOTHING,
        array $prepared_pairs = [],
        array $data_types = []
    ) {
        if (!$query)
        {
            throw new DBException("Empty Query");
        }

        try
        {
            $sth = $this->conn->prepare($query);

            foreach ($prepared_pairs as $key => $param)
            {
                // TODO maybe check if $key is valid
                if (isset($data_types[$key]))
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
                    trigger_error("A PDO exception occurred during during a transaction, but the rollback failed");
                }
            }

            if (DEBUG_MODE)
            {
                trigger_error("Database Error");
                var_dump($e->errorInfo);
                printf(
                    "SQLSTATE ERR: %s<br>\nmySQL ERR: %s<br>\nMessage: %s<br>\nQuery: %s<br>",
                    $e->errorInfo[0],
                    $e->errorInfo[1],
                    isset($e->errorInfo[2]) ? $e->errorInfo[2] : "",
                    $query
                );
                echo "Fields data: <br>";
                var_dump($prepared_pairs);
            }

            throw new DBException($e->errorInfo[0]);
        }

        throw new DBException("Unexpected reach of end of query(). Possibly return_type was invalid");
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
     * Insert data into the database. Helper method
     *
     * @param string $table       the database table
     * @param array  $fields_data associative array that maps column to value
     *                            example: [":name" => "daniel", ":id" => 23, "date" => "NOW()"]
     *                            If you do not want to prepare a column do not put ":" in front of the key
     * @param array  $data_types  associative array that maps column to param_type
     *
     * @throws DBException
     * @throws InvalidArgumentException
     * @return int the number of affected rows
     */
    public function insert($table, array $fields_data, array $data_types = [])
    {
        if (!$table || !$fields_data)
        {
            throw new InvalidArgumentException("Empty table or data");
        }

        // include and non escaped columns, eg: date => NOW()
        $column_value_pairs = $prepared_pairs = array();
        static::buildQueryParams($fields_data, $prepared_pairs, $column_value_pairs);

        // build the sql query
        $query = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            DB_PREFIX . $table,
            '`' . implode("`, `", array_keys($column_value_pairs)) . '`', // use back ticks for reserved mysql keywords
            implode(", ", array_values($column_value_pairs))
        );
        // TODO add on duplicate key

        Log::newEvent($query);

        return $this->query($query, static::ROW_COUNT, $prepared_pairs, $data_types);
    }

    /**
     * Perform a update on the database. Helper method
     *
     * @param string $table           the table name
     * @param string $where_statement the complete where statement
     * @param array  $fields_data     associative array that maps column to value
     *                                If you do not want to prepare a column do not put ":" in front of the key
     * @param array  $data_types      associative array that maps column to param_type
     *
     * @throws DBException
     * @throws InvalidArgumentException
     * @return int the number of affected rows
     */
    public function update($table, $where_statement, array $fields_data, array $data_types = [])
    {
        if (!$table || !$where_statement)
        {
            throw new InvalidArgumentException("Empty table or where statement");
        }

        $prepared_pairs = $column_value_pairs = [];
        static::buildQueryParams($fields_data, $prepared_pairs, $column_value_pairs);

        // build set value pairs
        $set_string = "";
        foreach ($column_value_pairs as $column => $value)
        {
            $set_string .= "`{$column}` = {$value}, ";
        }
        $set_string = rtrim($set_string, ", ");

        $query = sprintf("UPDATE %s SET %s WHERE %s", DB_PREFIX . $table, $set_string, $where_statement);

        Log::newEvent($query);

        return $this->query($query, static::ROW_COUNT, $prepared_pairs, $data_types);
    }

    /**
     * Perform a delete on the database. Helper method
     *
     * @param string $table           the table name
     * @param string $where_statement the complete statement name
     * @param array  $fields_data     associative array that maps column to value
     *                                If you do not want to prepare a column do not put ":" in front of the key
     * @param array  $data_types      associative array that maps column to param_type
     *
     * @throws DBException
     * @throws InvalidArgumentException
     * @return int the number of affected rows
     */
    public function delete($table, $where_statement, array $fields_data = [], array $data_types = [])
    {
        if (!$table || !$where_statement)
        {
            throw new InvalidArgumentException("Empty table or where statement");
        }

        $prepared_pairs = [];
        static::buildQueryParams($fields_data, $prepared_pairs);

        $query = sprintf("DELETE FROM %s WHERE %s", DB_PREFIX . $table, $where_statement);

        Log::newEvent($query);

        return $this->query($query, static::ROW_COUNT, $prepared_pairs, $data_types);
    }

    /**
     * Perform an sql count on the database, much faster than PDO::rowCount(). Helper method
     *
     * @param string $table           the table name
     * @param string $where_statement the sql where part
     * @param array  $fields_data     associative array that maps column to value
     *                                If you do not want to prepare a column do not put ":" in front of the key
     * @param array  $data_types      associative array that maps column to param_type
     *
     * @throws DBException
     * @throws InvalidArgumentException
     * @return int the count number
     */
    public function count($table, $where_statement = "", array $fields_data = [], array $data_types = [])
    {
        if (!$table)
        {
            throw new InvalidArgumentException("Empty table");
        }

        $prepared_pairs = [];
        static::buildQueryParams($fields_data, $prepared_pairs);

        $query = sprintf("SELECT COUNT(*) FROM %s", DB_PREFIX . $table);
        if ($where_statement)
        {
            $query .= sprintf(" WHERE %s", $where_statement);
        }

        return (int)$this->query($query, static::FETCH_FIRST_COLUMN, $prepared_pairs, $data_types);
    }
}
