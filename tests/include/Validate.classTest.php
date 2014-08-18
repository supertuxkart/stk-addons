<?php

//require_once("Validate.class.php");

class ValidateTest extends \PHPUnit_Framework_TestCase
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
     * @dataProvider providerTestEnsureInput
     */
    public function testEnsureInput($is_empty, $pool, $params)
    {
        $errors = Validate::ensureInput($pool, $params);
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
        ];
    }

    /**
     * @dataProvider providerTestVersionStringThrowsException
     * @expectedException ValidateException
     */
    public function testVersionStringThrowsException($version)
    {
        Validate::versionString($version);
    }

    /**
     * @dataProvider providerTestVersionStringNoThrowsException
     */
    public function testVersionStringNoThrowsException($version)
    {
        Validate::versionString($version);
    }
}