<?php


class UserTest extends PHPUnit_Framework_TestCase
{

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
     * @dataProvider providerTestUsernameValid
     */
    public function testUsernameValid($username)
    {
        User::validateUserName($username);
    }


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
     * @dataProvider providerTestUsernameThrowsException
     * @@expectedException UserException
     */
    public function testUsernameThrowsException($username)
    {
        User::validateUserName($username);
    }


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