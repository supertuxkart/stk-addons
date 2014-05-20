<?php

//require_once("Validate.class.php");

class ValidateTest extends \PHPUnit_Framework_TestCase
{
    /*
     * @dataProvider providerTestEmailThrowsException
     * @expectedException UserException
     */
    public function testEmailThrowsException()
    {
        //Validate::email($email);
        Validate::email("exampleexample.com");
    }

    public function providerTestEmailThrowsException()
    {
        return array(
            array(""),
            array("email"),
            array("email@"),
            array("@example.com")
        );
    }

}