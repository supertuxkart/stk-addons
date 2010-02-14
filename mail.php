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
$security ="basicPage";
$title = "Report a bug";
include("include/security.php");
include("include/view.php");
include("include/top.php");
include("include/mail.php");
?>    </head>
    <body>
        <?php 
        include("menu.php");
        if($_GET['action'] = "bug") sendMail($admin, "bug", array($_SESSION["login"],$_POST['bug']));
        ?>
		<div id="content">
        Thanks for your report !
        </div>
	<?php include("include/footer.php"); ?>
	</body>
</html>
