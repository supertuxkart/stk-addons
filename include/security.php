<?php
/* copyright 2009 Lucas Baudin <xapantu@gmail.com>                 
                                                                              
 This file is part of stkaddons.                                 
                                                                              
 stkaddons is free software: you can redistribute it and/or      
 modify it under the terms of the GNU General Public License as published by  
 the Free Software Foundation, either version 3 of the License, or (at your   
 option) any later version.                                                   
                                                                              
 stkaddons is distributed in the hope that it will be useful, but
 WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for    
 more details.                                                                
                                                                              
 You should have received a copy of the GNU General Public License along with 
 stkaddons.  If not, see <http://www.gnu.org/licenses/>.   */
?>
<?php
/***************************************************************************
Project: STK Addon Manager

File: security.php
Version: 1
Licence: GPLv3
Description: security

***************************************************************************/
session_start();
include("connectMysql.php");
$USER_LOGGED = false;
if($security != "")
{
	$USER_LOGGED = false;
	if(isset($_SESSION["login"]))
	{
		if($_SESSION["range"][$security] == true)
		{
			$USER_LOGGED = true;
		}
		else
		{
			?>
			<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
			<html xmlns="http://www.w3.org/1999/xhtml">
				<head>
					<link rel="StyleSheet" href="css/page.css" type="text/css" media="screen" title="Default Theme">
					<meta http-equiv="refresh" content="3;URL=index.php">
				</head>
				<body>
					<?php include("menu.php"); ?>
					You haven't right to access this page.<br />
					You will be redirect at the home page.
					<?php include("footer.php"); ?>
				</body>
			</html>
			<?php
		}
	}
	if($USER_LOGGED == false)
	{
		include("login.php");
	    exit();
	}
}
?>
