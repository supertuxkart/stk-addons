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

File: manageAccount.php
Version: 1
Licence: GPLv3
Description: people

***************************************************************************/
$security = "basicPage";
include("include/view.php");
include("include/security.php");
$users = new menu();
include("include/coreUser.php");
$allUser = new coreUser();

$title = "SuperTuxKart Add-ons | Users";
include("include/top.php");
?>
	</head>
	<body>
		<?php include("menu.php");
		$allUser->selectById($_SESSION['id']);
		$users ->addRoot("My Profile", "profile");
		$users ->addSub("Me", 'javascript:addonRequest(\'user.php?action=none\', '.$allUser->userCurrent['id'].')', "profile");
		$users ->addSub("Password", 'javascript:loadDiv(0)', "profile");
		$users ->addSub("Homepage", 'javascript:addonRequest(\'user.php?action=homepage\', '.$allUser->userCurrent['id'].')', "profile");
		$users->addDiv('<h3>Change password</h3>
		<form action="user.php?id='.$allUser->userCurrent['id'].'&amp;action=password" method="POST">
		Old password :<br />
		<input type="password" name="oldPass" /><br />
		New password :<br />
		<input type="password" name="newPass" /><br />
		Please enter a second time your password : <br />
		<input type="password" name="newPass2" /><br />
		<input type="submit" value="Submit" />
		</form>		
		');
		$users ->addRoot("Other users", "users");
		$allUser->loadAll();
		while ($allUser->next())
		{
			$users ->addSub($allUser->userCurrent['login'], 'javascript:addonRequest(\'user.php?action=none\', '.$allUser->userCurrent['id'].')', "users");
		}
		$users -> affiche();
		include("include/footer.php"); ?>
	</body>
</html>
