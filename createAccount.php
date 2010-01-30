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

File: index.php
Version: 1
Licence: GPLv3
Description: index page

***************************************************************************/
$security ="";
include("include/security.php");
include("include/top.php");
include("include/config.php");
?>

	</head>
	<body>
		<?php include("menu.php");
		echo '
		<div id="content">';
		//exit();
			if($_GET['action'] == "submit")
			{
				if($_POST['pass1'] == $_POST['pass2'])
				{
					$login = $_POST['login'];
					$existSql= mysql_query("SELECT * FROM `users` WHERE `users`.`login` = '".$login."'");
					$exist =true;
					$sql =  mysql_fetch_array($existSql) or $exist = false;
					if($exist == false && $login != null)
					{
						$crypt = cryptUrl(12);
						mysql_query("
						INSERT INTO `".$base."`.`users` (
						`login` ,
						`pass` ,
						`date` ,
						`id` ,
						`range` ,
						`mail` ,
						`available`,
						`verify`
						)
						VALUES (
						'".mysql_real_escape_string($_POST['login'])."', '".md5(mysql_real_escape_string($_POST['pass1']))."', '".date("Y-m-d")."', NULL , 'basicUser', '".mysql_real_escape_string($_POST['mail'])."', '0', '".$crypt."'
						);
						");
						include("include/mail.php");
						sendMail(mysql_real_escape_string($_POST['mail']), "newAccount", array($crypt, $_SERVER["PHP_SELF"], $login, $_POST['pass1']));
						echo "Your request is succesful, an e-mail will be sent to you ta activate your account.";
					}
					else
					{
					echo "Your login is already used.<br /><br />";
					echo '		<form id="form" action="createAccount.php?action=submit" method="POST">
		Your login : <br />
		<input type="text" name="login" /><br />
		Your password : <br />
		<input type="password" id="pass1" name="pass1" /><br />
		A second time your password : <br />
		<input type="password" id="pass2" name="pass2" /><br />
		Your mail : <br />
		<input type="text" name="mail" /><br /><br />
		<input type="submit" value="Submit" />
		</form>';
					}
				}
				else
				{
					?>
						<form id="form" action="createAccount.php?action=submit" method="POST">
						Your login : <br />
						<input type="text" name="login" value="<?php echo $_POST['login']?>"/><br />
						<b>Your passwords are not same.</b><br/>
						Your password : <br />
						<input type="password" id="pass1" name="pass1" /><br />
						A second time your password : <br />
						<input type="password" id="pass2" name="pass2" /><br />
						Your mail : <br />
						<input type="text" name="mail" value="<?php echo $_POST['mail']?>" /><br /><br />
						<input type="submit" value="Submit" />
						</form>
					<?php
				}
			}
			elseif($_GET['action'] == "valid")
			{
				mysql_query("UPDATE `".$base."`.`users` SET `available` = '1' WHERE `users`.`verify` ='".mysql_real_escape_string($_GET['num'])."';")or die(mysql_error());
				mysql_query("UPDATE `".$base."`.`users` SET `verify` = ' ' WHERE `users`.`verify` ='".mysql_real_escape_string($_GET['num'])."';")or die(mysql_error());
				echo "Your account is now available";
			}
			else
			{
			
		?>
		<form id="form" action="createAccount.php?action=submit" method="POST">
		Your login : <br />
		<input type="text" name="login" /><br />
		Your password : <br />
		<input type="password" id="pass1" name="pass1" /><br />
		A second time your password : <br />
		<input type="password" id="pass2" name="pass2" /><br />
		Your mail : <br />
		<input type="text" name="mail" /><br /><br />
		<input type="submit" value="Submit" />
		</form>
		<?php
			}
			echo '
		</div>';
			 include("include/footer.php"); ?>
	</body>
</html>
<?php
function cryptUrl($nbr) {
$str = "";
$chaine = "abcdefghijklmnpqrstuvwxy";
srand((double)microtime()*1000000);
for($i=0; $i<$nbr; $i++) {
$str .= $chaine[rand()%strlen($chaine)];
}
return $str;
}

$str = cryptUrl(12);
?>
