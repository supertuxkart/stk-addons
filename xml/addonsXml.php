<?
/***************************************************************************
Project: STK Addon Manager

File: index.php
Version: 1
Licence: GPLv3
Description: index page

***************************************************************************/
include("../include/connectMysql.php");
include("../include/coreAddon.php");
$type = mysql_real_escape_string($_GET["type"]);
echo "<?xml version=\"1.0\"?>\n";
echo "<addons  xmlns='http://stkaddons.tuxfamily.org/'>\n";
$addon  =new coreAddon($type);
$addon->loadAll();
while($addon->next())
{
	echo "<".$type.">\n\t";
	echo "<name>";
	echo $addon->addonCurrent["name"];
	echo "</name>\n\t";
	echo "<description>";
	echo $addon->addonCurrent["description"];
	echo "</description>\n\t";
	echo "<version>";
	echo $addon->addonCurrent["version"];
	echo "</version>\n\t";
	echo "<file>";
	echo $addon->addonCurrent["file"];
	echo "</file>\n";
	echo "</".$type.">\n";
}
echo "</addons>";
?>
