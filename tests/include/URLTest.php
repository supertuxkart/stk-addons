<?php
/**
 * Copyright 2017 SuperTuxKart-Team
 *
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

class URLTest extends PHPUnit_Framework_TestCase
{
    private static $valid_query_string = "second=value2&first=value&arr[0]=foo+bar&arr[1]=baz";

    private static $valid_query_array = [
        "second" => "value2",
        "first"  => "value",
        "arr"    => ["foo bar", "baz"]
    ];

    public function testQueryStringToArray()
    {
        $valid_array = URL::queryStringToArray(static::$valid_query_string);
        $this->assertInternalType("array", $valid_array);
        $this->assertEquals(3, count($valid_array));
        $this->assertArrayHasKey("second", $valid_array);
        $this->assertArrayHasKey("arr", $valid_array);
        $this->assertArrayHasKey("first", $valid_array);
        $this->assertInternalType("array", $valid_array["arr"]);
        $this->assertEquals(2, count($valid_array["arr"]));
        $this->assertEquals($valid_array, static::$valid_query_array);
    }

    public function testQueryArrayToString()
    {
        $valid_string = URL::queryArrayToString(static::$valid_query_array);
        $this->assertInternalType("string", $valid_string);
        $this->assertEquals(URL::decode($valid_string), URL::decode(static::$valid_query_string));
    }
}