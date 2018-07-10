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

class ValidateTest extends \PHPUnit\Framework\TestCase
{
    public function providerTestCheckboxThrowsException()
    {
        return [
            [1],
            [" "],
            ["example"],
            ["off"]
        ];
    }

    /**
     * @param string $box
     *
     * @dataProvider providerTestCheckboxThrowsException
     * @expectedException UserException
     */
    public function testCheckboxThrowsException($box)
    {
        Validate::checkbox($box, "");
    }

    public function testCheckboxNoThrowsException()
    {
        $this->assertEquals(Validate::checkbox("on", ""), "on");
    }

    public function providerTestEnsureInput()
    {
        return [
            [true, ["a" => 1, "b" => 2], ["a", "b"]],
            [false, ["a" => 1, "b" => 2], ["c"]],
            [false, [42, 54, 65], [5, 6]],
            [true, [42, 54, 65], [0, 1]],
            [true, [42, 54, 65], [0, 1, 2]]
        ];
    }

    /**
     * @param bool  $is_empty
     * @param array $pool
     * @param array $params
     *
     * @dataProvider providerTestEnsureInput
     */
    public function testEnsureInput($is_empty, $pool, $params)
    {
        $errors = Validate::ensureNotEmpty($pool, $params);
        $this->assertEquals($is_empty, empty($errors));
    }

    public function providerTestVersionStringThrowsException()
    {
        return [
            [""],
            ["0.8 "],
            ["gibberish"],
            ["a.b.c"],
            ["0.8.2-rc"],
            ["0.9"],
        ];
    }

    public function providerTestVersionStringNoThrowsException()
    {
        return [
            ["svn"],
            ["SVN"],
            ["sVn"],
            ["0.8.2"],
            ["100.8.2"],
            ["100.8.2-rc0"],
            ["100.8.2-rc1"],
            ["0.8.2-rc1"],
            ["0.9.0"],
            ["0.9.1"],
            ["0.9.1-rc0"]
        ];
    }

    /**
     * @param string $version
     *
     * @dataProvider providerTestVersionStringThrowsException
     * @expectedException ValidateException
     */
    public function testVersionStringThrowsException($version)
    {
        Validate::versionString($version);
    }

    /**
     * @param string $version
     *
     * @dataProvider providerTestVersionStringNoThrowsException
     */
    public function testVersionStringNoThrowsException($version)
    {
        Validate::versionString($version);
    }
}
