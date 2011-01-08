<?php
/*
copyright 2010 Lucas Baudin <xapantu@gmail.com>                   
                                                                          
This file is part of stkaddons

stkaddons is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

stkaddons is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of       
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with stkaddons.  If not, see <http://www.gnu.org/licenses/>.
*/

define("ROOT", "../");
include_once("../include/security.php");
include_once("../include/connectMysql.php");
define("UNIT_TEST", 1);
class coreAddonTest extends PHPUnit_Framework_TestCase
{
    public function testPushAndPop()
    {
        global $USER_LOGGED;
        $stack = new coreAddon('karts');
        $stack->loadAll();
        $this->assertEquals(true, $stack->loadAll());
        
        /* We think our test DB has at least one kart. */
        $this->assertEquals(true, $stack->next());

        $this->assertEquals(false, $stack->setAvailable());

        /* To set an addon available, the user need to be logged... */
        $USER_LOGGED = true;
        $_SESSION['range'] = array();
        $_SESSION['range']["manageaddons"] = false;
        $this->assertEquals(false, $stack->setAvailable());

        /*... and to have enough rights. */
        $_SESSION['range']["manageaddons"] = true;
        $this->assertEquals(true, $stack->setAvailable());

        /* We aren't a moderator, either the original author. */
        $_SESSION['id'] = 128; /* We are quite sure that user 128 is not the author. */
        $_SESSION['range']["manageaddons"] = false;
        $this->assertEquals(false, $stack->setInformation("Description", "New description"));

        /* We aren't a moderator, but the original author. */
        $_SESSION['id'] = 128; /* We are quite sure that user 128 is not the author. */
        $_SESSION['range']["manageaddons"] = true;
        $this->assertEquals(true, $stack->setInformation("Description", "New description"));

        /* We aren't a moderator, but the original author. */
        $_SESSION['id'] = $stack->addonCurrent["user"]; /* We are quite sure that user 128 is not the author. */
        $_SESSION['range']["manageaddons"] = false;
        $this->assertEquals(true, $stack->setInformation("Description", "New description"));
        $this->assertEquals(false, $stack->setInformation("property_not_exist", "New description"));

        $this->assertEquals(UP_LOCATION."file/test.png", zip_path("test"));
        $this->assertEquals(UP_LOCATION."image/test.png", image_path("test"));

        $USER_LOGGED = false;
        $this->assertEquals(false, $stack->addAddon("Test_addon", "this is a test description"));
        $USER_LOGGED = true;
        $_FILES['file_addon'] = array();
        $_FILES['file_addon']['type'] = "application/zip";
        $this->assertEquals(true, $stack->addAddon("Test_addon", "this is a test description"));
        $_SESSION['range']["manageaddons"] = true;
        $this->assertEquals(true, $stack->remove());
        $path_zip = "./test.zip";
        $info = read_info_from_zip($path_zip);
        $this->assertNotNull($info);
        $this->assertEquals("chinchila", $info["name"]);
        $this->assertEquals("2", $info["version"]);

        $this->assertEquals(true, repack_zip("./test.zip-extract"));
    }
}
?>

