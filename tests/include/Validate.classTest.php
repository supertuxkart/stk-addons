<?php

//require_once("Validate.class.php");

class ValidateTest extends \PHPUnit_Framework_TestCase
{
    public function providerTestEmailThrowsException()
    {
        return array(
            array(1),
            array(null),
            array(''),
            array(true),
            array(false),
            array(""),
            array("email"),
            array("email@"),
            array("@example.com"),
            array("valid@"),
            array("me@mytld"),
        );
    }

    public function providerTestEmailNoThrowsException()
    {
        return array(
            array("email@example.com"),
            array("email.test@example.com"),
            array("xx@xx.xx")
        );
    }

    /**
     * @dataProvider providerTestEmailThrowsException
     * @expectedException UserException
     */
    public function testEmailThrowsException($email)
    {
        Validate::email($email);
    }

    /**
     * @dataProvider providerTestEmailNoThrowsException
     */
    public function testEmailNoThrowsException($email)
    {
        Validate::email($email);
    }

}