<?php
/**
 * copyright 2015 - 2016 Daniel Butum <danibutum at gmail dot com>
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

class DBExceptionTest extends \PHPUnit\Framework\TestCase
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
