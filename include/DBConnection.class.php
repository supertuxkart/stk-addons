<?php

require_once(ROOT . 'config.php');

class DBException extends Exception 
{
    public function __construct($error_code = "") {
        $this->error_code = $error_code;
    }
    
    public function getErrorCode()
    {
        return $this->error_code;
    }
}

class DBConnection
{
    private $conn;
    private static $instance;
    
    //Faking enumeration
    const ROW_COUNT = 1;
    const FETCH_ALL = 2;
    const NOTHING = 4;
    

    private function __construct() {
        $this->conn = new PDO('mysql:host='. DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASSWORD);
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->conn->exec("set names utf8");
        $this->in_transaction = false;
        
    }

    /**
     * Get the DBConnection singleton
     * @return \DBConnection
     */
    public static function get() {
        if( !self::$instance ) {
	        self::$instance = new DBConnection();
        }
        return self::$instance;
    }
    
    public function beginTransaction()
    {
        if(!$this->in_transaction)
            $this->in_transaction = $this->conn->beginTransaction();
        return $this->in_transaction;
        
    }
    
    public function commit()
    {
        if($this->in_transaction)
        {
            $this->in_transaction = !$this->conn->commit();
            return !$this->in_transaction;
        }
        if (DEBUG_MODE){
            printf("Did a commit while not having a transaction running!");
        }
        return false;
    }
    
    public function rollback()
    {
        if($this->in_transaction)
        {
            $this->in_transaction = !$this->conn->rollback();
            return !$this->in_transaction;
        }
        if (DEBUG_MODE){
            printf("Did a rollback while not having a transaction running!");
        }
        return false;
    }

    public function query($query, $return_type = DBConnection::NOTHING, $params = NULL) {
        if(!$query)
	        throw new DBException("Empty Query");     
        try{
	        $sth = $this->conn->prepare($query);
	        $sth->execute($params);
	        if($return_type == self::NOTHING)
	            return;
            if($return_type == self::ROW_COUNT)
                return $sth->rowCount();
            if($return_type == self::FETCH_ALL)
                return $sth->fetchAll(PDO::FETCH_ASSOC);    
        } catch (PDOException $e){
            if($this->in_transaction)
            {
                $success = $this->conn->rollback();
                if (DEBUG_MODE && !$success){
                    printf("A PDO exception occured during during a transaction, but the rollback failed");
                }
            }
            if (DEBUG_MODE){
                var_dump($e->errorInfo);
                printf("SQLSTATE ERR: %s<br />\nmySQL ERR: %s<br />\nMessage: %s<br />\n",$e->errorInfo[0], $e->errorInfo[1], $e->errorInfo[2]);
            }
            throw new DBException($e->errorInfo[0]);
        }
    }
    
    public function lastInsertId(){
        return $this->conn->lastInsertId();  
    }
}
?>
