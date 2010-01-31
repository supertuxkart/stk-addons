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

File: login.php
Version: 1
Licence: GPLv3
Description: login page

***************************************************************************/
session_start();

// connect to mysql
include("include/connectMysql.php");
include("include/top.php");

// define possibly undefined variables
$_POST['login'] = (isset($_POST['login'])) ? $_POST['login'] : NULL;
$_POST['pass'] = (isset($_POST['pass'])) ? $_POST['pass'] : NULL;

// protect sql
$loginSubmit = mysql_real_escape_string($_POST['login']);
$passSubmit = mysql_real_escape_string($_POST['pass']);

//add a variable to verify if the users is already connected
$auth = false;
if(isset($_SESSION["login"]))
{
	
	$loginSearch = mysql_query("SELECT * FROM users WHERE login='".$_SESSION["login"]."'");
	$loginSql = mysql_fetch_array($loginSearch);
	if($loginSql['pass'] == md5($_SESSION["pass"]))
		{
			//The users is already connected
			$auth=true;
		}
}
//if the users isn't connected, start to try to connect it
if($auth == false)
{
	//sql request
	$loginSearch = mysql_query("SELECT * FROM users WHERE login='$loginSubmit'") or die(mysql_error());
	$loginSql = mysql_fetch_array($loginSearch);
	
		//brute-forcing
		sleep(1);
		
		
		if($loginSql['pass'] == md5($passSubmit) && $loginSql['pass'] != ""  && $loginSql['available'] == 1)
		{
			//add sessions variable
			$succes ="successful";
			$_SESSION["login"] = $loginSubmit;
			$_SESSION["level"] = $loginSql['level'];
			$_SESSION["id"] = $loginSql["id"];
			$_SESSION["pass"] = $passSubmit;
			include("include/allow.php");
			?>
				<meta http-equiv="refresh" content="3;URL=index.php">
				</head>
				<body>
					<?php include("menu.php"); ?>
					<div id="content">
					<?php echo _("Welcome"); echo $_SESSION["login"] ?> :)
					<?php echo _("You will be redirected to the home page."); ?>
					</div>
					<?php include("include/footer.php"); ?>
				</body>
			</html>
			<?php
		}
		
		
		elseif(isset($_POST['login']))
		{
			?>
						</head>
						<body>
							<?php include("menu.php"); ?>
					<div id="content">
							<?php echo _("Authentification failed."); ?>
							<form action="login.php" method="POST">
								<input type="text" name="login" />
								<input type="password" name="pass" />
								<input type="submit" value="Submit" />
							</form>
							</div>
							<?php include("include/footer.php"); ?>
						</body>
					</html>
			<?php
		}
		
		
		else
		{
			?>
						</head>
						<body>
							<?php include("menu.php"); ?>
					<div id="content">
					        <?php if(!ereg("login.php",$_SERVER['PHP_SELF'])) echo _("You must be logged in to access this page.")."<br />"; ?>
							<form action="login.php" method="POST">
								<input type="text" name="login" />
								<input type="password" name="pass" />
								<input type="submit" value="Submit" />
							</form>
							<a href="createAccount.php"><?php echo _("Create an account."); ?></a>
							</div>
							<?php include("include/footer.php"); ?>
						</body>
					</html>
			<?php
			exit();
		}
}
?>
				<meta http-equiv="refresh" content="0;URL=index.php">
				</head>
				<body>
				</body>
			</html>
