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

class UserTest extends PHPUnit_Framework_TestCase
{

    /**
     * @return array
     */
    public function providerTestUsernameValid()
    {
        return [
            ["bob"],
            ["B.ob"],
            ["Test"],
            ["___"],
            ["..."],
            ["-_-"],
            ["007"],
            ["-007-"],
            [str_repeat("a", User::MAX_USERNAME)] // exactly 30
        ];
    }

    /**
     * @param string $username
     *
     * @dataProvider providerTestUsernameValid
     */
    public function testUsernameValid($username)
    {
        User::validateUserName($username);
    }


    /**
     * @return array
     */
    public function providerTestUsernameThrowsException()
    {
        return [
            ["S p a"],
            [""],
            ["|space"],
            ["__="],
            ["fff "],
            [" fff"],
            [str_repeat("a", User::MAX_USERNAME + 1)]
        ];
    }

    /**
     * @param string $username
     *
     * @dataProvider providerTestUsernameThrowsException
     *               @@expectedException UserException
     */
    public function testUsernameThrowsException($username)
    {
        User::validateUserName($username);
    }


    /**
     * @return array
     */
    public function providerTestUsernameValidWithSpace()
    {
        return [
            ["Bob Bob"],
            ["0 0"],
            ["_ . . user name -   "]
        ];
    }

    /**
     * @dataProvider providerTestUsernameValidWithSpace
     */
    public function testUsernameValidWithSpace($username)
    {
        User::validateUserName($username, true);
    }
}