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
            $message_html = "<html><head></head><body>Thank you for registering an account on the SuperTuxKart Add-Ons Manager. Please click <a href=\"http://".$_SERVER["SERVER_NAME"].$option[1]."?action=valid&amp;num=$option[0]\">here</a> to activate your account.<br />Username: ".$option[2]."</body></html>";
            $subject = "New Account at ".$_SERVER["SERVER_NAME"];
            break;
        case "bug":
            $message_html = "<html><head></head><body>Bug report :<br />User : ".$option[0]."<br />Description :".$option[1]." <br /><br /></body></html>";
            $subject = "stkaddons : bug report";
            break;
        case 'moderatorNotification':
            $message_html = '<html><head></head><body>'.$option[3].',<br /><br />A moderator has left a note concerning you addon \'<a href="'.$option[1].'">'.$option[0].'</a>\'.<br /><br />'.$option[2].'</body></html>';
            $subject = "New message for addon '{$option[0]}'";
            break;
    }

    $boundary = "-----=".md5(rand());

    $header = "From: \"stkaddons@tuxfamily.org\"<stkaddons@tuxfamily.org>".$passage_ligne;
    $header.= "Reply-to: \"STK-Addons Administrator\" <".get_config('admin_email').">".$passage_ligne;
    $header.= "MIME-Version: 1.0".$passage_ligne;
    $header.= "Content-Type: multipart/alternative;".$passage_ligne." boundary=\"$boundary\"".$passage_ligne;
    $message = $passage_ligne."--".$boundary.$passage_ligne;
    $message.= "Content-Type: text/html; charset=\"ISO-8859-1\"".$passage_ligne;
    $message.= "Content-Transfer-Encoding: 8bit".$passage_ligne;
    $message.= $passage_ligne.$message_html.$passage_ligne;
    $message.= $passage_ligne."--".$boundary."--".$passage_ligne;
    $message.= $passage_ligne."--".$boundary."--".$passage_ligne;
    mail($mail,$subject,$message,$header);
}
?>

