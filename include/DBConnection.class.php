<?php

require_once(ROOT . 'config.php');

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

class DBConnection
{

    private $conn;

    private static $instance;

    // Faking enumeration
    const ROW_COUNT = 1;

    const FETCH_ALL = 2;

    const NOTHING = 4;


    private function __construct()
    {
        $this->conn = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASSWORD);
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->conn->exec("set names utf8");
        $this->in_transaction = false;

    }

    /**
     * Get the DBConnection singleton
     * @return \DBConnection
     */
    public static function get()
    {
        if (!self::$instance) {
            self::$instance = new DBConnection();
        }
        return self::$instance;
    }

    /**
     * Start a database transaction
     * @return bool
     */
    public function beginTransaction()
    {
        if (!$this->in_transaction) {
            $this->in_transaction = $this->conn->beginTransaction();
        }
        return $this->in_transaction;

    }

    /**
     * @return bool
     */
    public function commit()
    {
        if ($this->in_transaction) {
            $this->in_transaction = !$this->conn->commit();
            return !$this->in_transaction;
        }
        if (DEBUG_MODE) {
            printf("Did a commit while not having a transaction running!");
        }
        return false;
    }

    /**
     * @return bool
     */
    public function rollback()
    {
        if ($this->in_transaction) {
            $this->in_transaction = !$this->conn->rollback();
            return !$this->in_transaction;
        }
        if (DEBUG_MODE) {
            printf("Did a rollback while not having a transaction running!");
        }
        return false;
    }

    /**
     * Perform a query on the database
     *
     * @param string $query
     * @param int    $return_type
     * @param null   $params
     *
     * @return array|int
     * @throws DBException
     */
    public function query($query, $return_type = DBConnection::NOTHING, $params = null)
    {
        if (!$query) {
            throw new DBException("Empty Query");
        }
        try {
            $sth = $this->conn->prepare($query);
            $sth->execute($params);
            if ($return_type === self::NOTHING) {
                return;
            }
            if ($return_type === self::ROW_COUNT) {
                return $sth->rowCount();
            }
            if ($return_type === self::FETCH_ALL) {
                return $sth->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch(PDOException $e) {
            if ($this->in_transaction) {
                $success = $this->conn->rollback();
                if (DEBUG_MODE && !$success) {
                    printf("A PDO exception occured during during a transaction, but the rollback failed");
                }
            }
            if (DEBUG_MODE) {
                var_dump($e->errorInfo);
                printf(
                        "SQLSTATE ERR: %s<br />\nmySQL ERR: %s<br />\nMessage: %s<br />\nQuery: %s<br />\n",
                        $e->errorInfo[0],
                        $e->errorInfo[1],
                        $e->errorInfo[2],
                        $query
                );
            }
            throw new DBException($e->errorInfo[0]);
        }
    }

    /**
     * Get the last id inserted into the database
     * @return string
     */
    public function lastInsertId()
    {
        return $this->conn->lastInsertId();
    }

    /**
     * Insert data into the database. helper method
     * @param string $table
     * @param array  $fields_data an associative array in which the key is the column and the value is the actual value
     *
     * @throws DBException
     */
    public function insert($table, array $fields_data)
    {
        if (!$table or empty($fields_data)) {
            throw new DBException("Empty table or data");
        }

        // associative array for preparing the data
        $prepared_pairs = array();

        foreach ($fields_data as $field => $value) {
            // :field => value
            $prepared_pairs[":" . $field] = $value;
        }

        // build the sql query
        $query = sprintf(
                "INSERT INTO %s (%s) VALUES (%s)",
                DB_PREFIX . $table,
                implode(", ", array_keys($fields_data)),
                implode(", ", array_keys($prepared_pairs))
        );

        $this->query($query, static::NOTHING, $prepared_pairs);
    }
}