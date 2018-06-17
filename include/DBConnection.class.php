<?php
/**
 * copyright 2013      Glenn De Jonghe
 *           2013      Stephen Just <stephenjust@users.sourceforge.net>
 *           2014-2015 Daniel Butum <danibutum at gmail dot com>
 * This file is part of SuperTuxKart
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
 * Class DBConnection, handles all the database connections
 */
class DBConnection
{
    /**
     * The PDO database handle
     * @var PDO
     */
    private $connection;

    /**
     * The singleton used for the database connection
     * @var DBConnection|null
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
        try
        {
            $this->connection =
                new PDO(sprintf("mysql:host=%s;dbname=%s;charset=utf8mb4", DB_HOST, DB_NAME), DB_USER, DB_PASSWORD);

            // add database PDO collector
            if (Debug::isToolbarEnabled())
            {
                $this->connection = new DebugBar\DataCollector\PDO\TraceablePDO($this->connection);
                Debug::getToolbar()->addCollector(new DebugBar\DataCollector\PDO\PDOCollector($this->connection));
            }

            if (!$this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION))
            {
                throw new DBException("setAttribute ATTR_ERRMODE failed", ErrorType::DB_SET_ATTRIBUTE);
            }
            if (!$this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC))
            {
                throw new DBException("setAttribute ATTR_DEFAULT_FETCH_MODE failed", ErrorType::DB_SET_ATTRIBUTE);
            }
        }
        catch (Exception $e)
        {
            exit("ERROR: Can not connect to the database. " . $e->getMessage());
        }
    }

    /**
     * Build the parameters for the query method
     * This method takes an array(eg: [":name" => "Daniel", "date" => "NOW()"] and builds 2 additional arrays
     * The keys that start with a ':' are escaped (are added to the $prepared_pairs, which in turn will be parsed to
     * PDO). The other keys that are NOT prefixed with ':' will just be constant values (no escaping will be done)
     *
     * @param array $fields_data        associative array that maps column to value
     * @param array $prepared_pairs     return associative array for preparing the data
     *                                  Example of output: [":name" => "Daniel"]
     * @param array $column_value_pairs associative array of column => value pairs
     *                                  Example of output: ["name" => ":name", "date" => "17 May 2014"]
     *                                  As shown in the example above the fields that are named parameters will have
     *                                  the column normal as the rest (the unescaped params) but the value will be the
     *                                  named parameter itself
     */
    private static function buildQueryParams(
        array $fields_data,
        array &$prepared_pairs,
        array &$column_value_pairs = []
    ) {
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
     * @return array
     */
    private static function getReturnTypes()
    {
        return [
            static::ROW_COUNT,
            static::FETCH_ALL,
            static::FETCH_FIRST_COLUMN,
            static::FETCH_FIRST,
            static::NOTHING
        ];
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
     * Get the internal PDO connection object
     * @return PDO
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @return boolean
     */
    public function isInTransaction()
    {
        return (bool)$this->connection->inTransaction();
    }

    /**
     * Start a database transaction
     *
     * @return bool
     */
    public function beginTransaction()
    {
        // TODO better test PDO transaction
        if (!$this->isInTransaction())
        {
            return $this->connection->beginTransaction();
        }

        return false;
    }

    /**
     * Commit the current transaction if we are in one
     *
     * @return bool
     */
    public function commit()
    {
        if ($this->isInTransaction())
        {
            return $this->connection->commit();
        }

        Debug::addMessage("Did a commit while not having a transaction running!");

        return false;
    }

    /**
     * Perform a rollback (undo) on the current transaction
     *
     * @return bool
     */
    public function rollback()
    {
        if ($this->isInTransaction())
        {
            return $this->connection->rollback();
        }

        Debug::addMessage("Did a rollback while not having a transaction running!");

        return false;
    }

    /**
     * Perform a query on the database
     *
     * @param string $query          The sql string
     * @param int    $return_type    The type of return. Use the class constants
     * @param array  $prepared_pairs An associative array having mapping between variables for prepared statements and
     *                               values
     * @param array  $data_types     variables in prepared statement for which data type should be explicitly mentioned
     *
     * @throws DBException
     *
     * @return array|int|string|null depending of the return type
     */
    public function query(
        $query,
        $return_type = DBConnection::NOTHING,
        array $prepared_pairs = [],
        array $data_types = []
    ) {
        if (DEBUG_MODE && !in_array($return_type, static::getReturnTypes()))
        {
            Debug::addMessage("Return type is invalid");
            exit;
        }

        // Replace {DB_VERSION} macro with the actual version number
        $query = str_ireplace('{DB_VERSION}', DB_VERSION, $query);

        try
        {
            $sth = $this->connection->prepare($query);

            foreach ($prepared_pairs as $key => $param)
            {
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
        catch (PDOException $e)
        {
            if ($this->isInTransaction())
            {
                $success = $this->connection->rollback();
                if (!$success)
                {
                    Debug::addMessage("A PDO exception occurred during during a transaction, but the rollback failed");
                }
            }

            // common error codes http://docstore.mik.ua/orelly/java-ent/jenut/ch08_06.htm
            // error info array info https://secure.php.net/manual/en/pdostatement.errorinfo.php
            if (DEBUG_MODE)
            {
                $str_errorInfo = var_export($e->errorInfo, true);
                $str_prepared_pairs = var_export($prepared_pairs, true);

                printf("<p>Raw errorInfoArray:</p><pre>%s</pre>", $str_errorInfo);
                printf(
                    "SQLSTATE ERR: %s<br>\nDriver specific error code: %s<br>\nDriver specific error message: %s<br>\nQuery: %s<br>",
                    $e->errorInfo[0],
                    $e->errorInfo[1],
                    isset($e->errorInfo[2]) ? $e->errorInfo[2] : "",
                    $query
                );
                printf("<p>Fields data (aka prepared pairs): </p><pre>%s</pre>", $str_prepared_pairs);
                Debug::addMessage("Database Error: \nerrorInfo = " . $str_errorInfo);
            }

            throw DBException::get("Database error happened, yikes!", ErrorType::DB_GENERIC)->setSqlErrorCode(
                $e->errorInfo[0]
            );
        }

        throw new DBException(
            "Unexpected reach of end of query(). Possibly return_type was invalid",
            ErrorType::DB_GENERIC
        );
    }

    /**
     * Get the last id inserted into the database
     *
     * @return int|string
     */
    public function lastInsertId()
    {
        return $this->connection->lastInsertId();
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
        $column_value_pairs = $prepared_pairs = [];
        static::buildQueryParams($fields_data, $prepared_pairs, $column_value_pairs);

        // build the sql query
        $query = sprintf(
            "INSERT INTO `{DB_VERSION}_%s` (%s) VALUES (%s)",
            $table,
            '`' . implode("`, `", array_keys($column_value_pairs)) . '`', // use back ticks for reserved mysql keywords
            implode(", ", array_values($column_value_pairs))
        ); // TODO add on duplicate key

        return $this->query($query, static::ROW_COUNT, $prepared_pairs, $data_types);
    }

    /**
     * Perform a update on the database. Helper method
     *
     * @param string $table           the table name
     * @param string $where_statement the complete where statement, if any prepared columns are set here, they will not
     *                                be included in the SET SQL
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
            // ignore updating value from the where clause
            if ($value[0] === ":" && Util::str_contains($where_statement, $value))
            {
                continue;
            }

            $set_string .= "`{$column}` = {$value}, ";
        }
        $set_string = rtrim($set_string, ", ");

        $query = sprintf("UPDATE `{DB_VERSION}_%s` SET %s WHERE %s", $table, $set_string, $where_statement);

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

        $query = sprintf("DELETE FROM `{DB_VERSION}_%s` WHERE %s", $table, $where_statement);

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

        $query = sprintf("SELECT COUNT(*) FROM `{DB_VERSION}_%s`", $table);
        if ($where_statement)
        {
            $query .= sprintf(" WHERE %s", $where_statement);
        }

        return (int)$this->query($query, static::FETCH_FIRST_COLUMN, $prepared_pairs, $data_types);
    }
}
