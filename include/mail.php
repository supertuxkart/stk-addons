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

File: mail.php
Version: 1
Licence: GPLv3
Description: page to send an e-mail

***************************************************************************/
function sendMail($mail, $subject, $option)
{
$passage_ligne = "\n";
	switch($subject)
	{
		case "newAccount":
			$message_html = "<html><head></head><body>Thank you for subscribing to the SuperTuxKart Add-Ons Manager. Please click <a href=\"http://".$_SERVER["SERVER_NAME"].$option[1]."?action=valid&amp;num=$option[0]\">here</a> to confirm your account<br />Username : ".$option[2]."<br />Password : ".$option[3]."</body></html>";
			$sujet = "New Account at ".$_SERVER["SERVER_NAME"];
			break;
		case "bug":
			$message_html = "<html><head></head><body>Bug report :<br />User : ".$option[0]."<br />Description :".$option[1]." <br /><br /></body></html>";
			$sujet = "stkaddons : bug report";
			break;
	}

	$boundary = "-----=".md5(rand());

	$header = "From: \"stkaddons@tuxfamily.org\"<stkaddons@tuxfamily.org>".$passage_ligne;
	$header.= "Reply-to: \"xapantu@gmail.com\" <xapantu@gmail.com>".$passage_ligne;
	$header.= "MIME-Version: 1.0".$passage_ligne;
	$header.= "Content-Type: multipart/alternative;".$passage_ligne." boundary=\"$boundary\"".$passage_ligne;
	$message.= $passage_ligne."--".$boundary.$passage_ligne;
	$message.= "Content-Type: text/html; charset=\"ISO-8859-1\"".$passage_ligne;
	$message.= "Content-Transfer-Encoding: 8bit".$passage_ligne;
	$message.= $passage_ligne.$message_html.$passage_ligne;
	$message.= $passage_ligne."--".$boundary."--".$passage_ligne;
	$message.= $passage_ligne."--".$boundary."--".$passage_ligne;
	mail($mail,$sujet,$message,$header);
}
?>

