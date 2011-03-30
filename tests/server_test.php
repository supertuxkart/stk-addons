<?php
/**
 * copyright 2011 Stephen Just <stephenjust@users.sf.net>
 *
 * This file is part of stkaddons
 *
 * stkaddons is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * stkaddons is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with stkaddons.  If not, see <http://www.gnu.org/licenses/>.
 */
define("ROOT", "../");
define("UNIT_TEST", 1);
include('phpunit/Autoload.php');
class coreServerTest extends PHPUnit_Framework_TestCase
{
    public function testPEAR()
    {
	// System.php is shipped with PEAR
	$this->assertEquals(true, require_once('System.php'));
	$this->assertEquals(true, class_exists('System'));
    }
    public function testGettext()
    {
	$this->assertEquals(true, function_exists('gettext'));
	$this->assertEquals(true, function_exists('_'));
    }
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
        $_SESSION['role'] = array();
        $_SESSION['role']["manageaddons"] = false;
        $this->assertEquals(false, $stack->setAvailable());

        /*... and to have enough rights. */
        $_SESSION['role']["manageaddons"] = true;
        $this->assertEquals(true, $stack->setAvailable());

        /* We aren't a moderator, either the original author. */
        $_SESSION['id'] = 128; /* We are quite sure that user 128 is not the author. */
        $_SESSION['role']["manageaddons"] = false;
        $this->assertEquals(false, $stack->setInformation("Description", "New description"));

        /* We aren't a moderator, but the original author. */
        $_SESSION['id'] = 128; /* We are quite sure that user 128 is not the author. */
        $_SESSION['role']["manageaddons"] = true;
        $this->assertEquals(true, $stack->setInformation("Description", "New description"));

        /* We aren't a moderator, but the original author. */
        $_SESSION['id'] = $stack->addonCurrent["user"]; /* We are quite sure that user 128 is not the author. */
        $_SESSION['role']["manageaddons"] = false;
        $this->assertEquals(true, $stack->setInformation("Description", "New description"));
        $this->assertEquals(false, $stack->setInformation("property_not_exist", "New description"));

        $this->assertEquals(UP_LOCATION."file/test.zip", zip_path("test"));
        $this->assertEquals(UP_LOCATION."image/test.png", image_path("test"));

        $this->assertEquals("test2.zip-extract//bigbuckbunny/kart.xml", find_xml("test2.zip-extract/"));

        $USER_LOGGED = false;
        $this->assertEquals(false, $stack->addAddon("Test_addon", "this is a test description"));
        $USER_LOGGED = true;
        $_FILES['file_addon'] = array();
        $_FILES['file_addon']['type'] = "application/zip";
        $this->assertEquals(true, $stack->addAddon("Test_addon", "this is a test description"));
        $_SESSION['role']["manageaddons"] = true;
        $this->assertEquals(true, $stack->remove());
        $path_zip = "./test2.zip";
        $info = read_info_from_zip($path_zip);
        $this->assertNotNull($info);
        $this->assertEquals("bigbuckbunny", $info["name"]);
        $this->assertEquals("2", $info["version"]);

        $this->assertEquals(true, repack_zip("./test2.zip-extract", "./test.zip-repack"));
    }
}
?>
