<?php


class DBExceptionTest extends PHPUnit_Framework_TestCase
{
    public function testThrowExceptionSqlCode()
    {
        $message = 'test';
        $error = ErrorType::DB_GENERIC;
        $sql_error = 'random_sql_code';

        try
        {
            throw DBException::get($message, $error)->setSqlErrorCode($sql_error);
        }
        catch (DBException $e)
        {
            $this->assertEquals($e->getMessage(), $message);
            $this->assertEquals($e->getCode(), $error);
            $this->assertEquals($e->getSqlErrorCode(), $sql_error);
        }
    }
}