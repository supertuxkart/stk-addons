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

class Mail {
    public static function newAccountNotification($email, $username, $ver_code, $ver_page) {
	$url_username = urlencode($username);
	$message = "Thank you for registering an account on the SuperTuxKart Add-Ons Manager.\n".
		"Please go to http://{$_SERVER["SERVER_NAME"]}$ver_page?action=valid&num=$ver_code&user=$url_username to activate your account.\n\n".
		"Username: $username";
        $subject = "New Account at ".$_SERVER["SERVER_NAME"];
	
	Mail::send($email, $subject, $message);
    }
    
    public static function passwordResetNotification($email, $username, $ver_code, $ver_page) {
	$url_username = urlencode($username);
	$message = "You have requested to reset your password on the SuperTuxKart Add-Ons Manager.\n".
		"Please go to http://{$_SERVER["SERVER_NAME"]}$ver_page?action=valid&num=$ver_code&user=$url_username to reset your password.\n\n".
		"Username: $username";
        $subject = "Reset Password on ".$_SERVER["SERVER_NAME"];
	
	Mail::send($email, $subject, $message);
    }
    
    public static function addonNoteNotification($email, $addon_id, $notes) {
	$addon_name = Addon::getName($addon_id);
	$message = "A moderator has left a note concerning your add-on, '$addon_name.' The notes saved for each revision of this add-on are shown below.\n\n";
	$message .= $notes;
	$subject = "New message for add-on '$addon_name'";
	
	Mail::send($email, $subject, $message);
    }

    private static function send($to, $subject, $message) {
	$from = '"STK-Addons Administrator" <'.ConfigManager::get_config('admin_email').'>';
	$replyto = '"STK-Addons Administrator" <'.ConfigManager::get_config('admin_email').'>';
	
	$headers = "From: $from\r\nReply-To: $replyto";
	$subject = strip_tags($subject);
	$message = strip_tags($message);
	$message = "This is an automated email message. Please do not reply directly, as it may not be received.\n".
		"If you have any questions, please contact the website administrator directly at ".ConfigManager::get_config('admin_email').".\n\n".$message;
	try {
	    $to = Validate::email($to);
	}
	catch (UserException $e) {
	    throw new Exception('Invalid email address.');
	}
	
	$success = @mail($to,$subject,$message,$headers);
	if (!$success)
	    throw new Exception('Failed to send email message.');
    }
}

function moderator_email($subject, $message_html)
{
    $mail_address = ConfigManager::get_config('list_email');
    if (strlen($mail_address) == 0)
    {
        echo '<span class="warning">'.htmlspecialchars(_('No moderator mailing-list email is set.')).'</span><br />';
        return;
    }

    $boundary = "-----=".md5(rand());
    $header = "From: \"STK-Addons Administrator\" <".ConfigManager::get_config('admin_email').">\n"
        ."Reply-to: \"STK-Addons Administrator\" <".ConfigManager::get_config('admin_email').">\n"
        ."MIME-Version: 1.0\n"
        ."Content-Type: multipart/alternative;\n boundary=\"$boundary\"\n";
    $message = "\n--".$boundary."\n"
        ."Content-Type: text/html; charset=\"ISO-8859-1\"\n"
        ."Content-Transfer-Encoding: 8bit\n"
        ."\n".$message_html."\n"
        ."\n--".$boundary."--\n"
        ."\n--".$boundary."--\n";
    mail($mail_address,$subject,$message,$header);
}
?>

