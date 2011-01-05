<?
/***************************************************************************
Project: STK Addon Manager

File: index.php
Version: 1
Licence: GPLv3
Description: index page

***************************************************************************/
include("../include/connectMysql.php");
echo "<?xml version=\"1.0\"?>\n";
echo "<addons  xmlns='http://stkaddons.tuxfamily.org/'>\n";
$addon  =new coreAddon('karts');
$addon->loadAll();
while($addon->next())
{
	echo "<karts>\n\t";
	echo "<name>";
	echo $addon->addonCurrent["name"];
	echo "</name>\n\t";
	echo "<description>";
	echo $addon->addonCurrent["description"];
	echo "</description>\n\t";
	echo "<version>";
	echo $addon->addonCurrent["version"];
	echo "</version>\n\t";
	echo "<file>".DOWN_LOCATION;
	echo $addon->addonCurrent["file"];
	echo "</file>\n";
	echo "<icon>";
	echo 'http://stkaddons.tuxfamily.org/image.php?type=medium&pic=/data/repository/stkaddons/icon/'.$addon->addonCurrent["icon"];
	echo "</icon>\n";
	echo "</karts>\n";
}
$addon  =new coreAddon('tracks');
$addon->loadAll();
while($addon->next())
{
	echo "<tracks>\n\t";
	echo "<name>";
	echo $addon->addonCurrent["name"];
	echo "</name>\n\t";
	echo "<description>";
	echo $addon->addonCurrent["description"];
	echo "</description>\n\t";
	echo "<version>";
	echo $addon->addonCurrent["version"];
	echo "</version>\n\t";
	echo "<file>".DOWN_LOCATION;
	echo $addon->addonCurrent["file"];
	echo "</file>\n";
	echo "<icon>";
	echo 'http://download.tuxfamily.org/stkaddons/icon/icon.png';
	echo "</icon>\n";
	echo "</tracks>\n";
}
echo "</addons>";
?>
