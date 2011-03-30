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

File: topphp
Version: 1
Licence: GPLv3
Description: top of all page

***************************************************************************/

$nom_page = $_SERVER['PHP_SELF']."?";
foreach($_GET as $cle => $element)
{
    if($cle != "lang") $nom_page = $nom_page.$cle."=".$element."&amp;";
}
$timestamp_expire = time() + 365*24*3600;
if(!isset($_COOKIE['lang']))
{
	setcookie('lang', 'en_EN', $timestamp_expire);
}
if (isset($_GET['lang'])) { // If the user has chosen a language
	switch ($_GET['lang']) { // En fonction de la langue, on crée une variable $langage qui contient le code
		case 'fr':
			setcookie('lang', 'fr_FR', $timestamp_expire);
			break;
		case 'en':
			setcookie('lang', 'en_EN', $timestamp_expire);
			break;
		case 'nl':
			setcookie('lang', 'nl_NL', $timestamp_expire);
			break;
		case 'de':
			setcookie('lang', 'de_DE', $timestamp_expire);
			break;
		case 'ga':
			setcookie('lang', 'ga_IE', $timestamp_expire);
			break;
		default:
			setcookie('lang', 'en_EN', $timestamp_expire);
			break;
	}
	?>
	<html>
	<head>
		<meta http-equiv="refresh" content="0;URL=<?php echo $nom_page; ?>">
	</head>
	</html>
	<?php
	exit();

}
setlocale(LC_ALL, $_COOKIE['lang'].'.UTF-8');

bindtextdomain('translations', 'locale');
textdomain('translations');
bind_textdomain_codeset('translations', 'UTF-8');
if(!isset($title))
    $title="SuperTuxKart Add-ons";
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta content="text/html; charset=UTF-8" http-equiv="content-type" />
        <title><?php echo $title;?></title>
        <link href="css/skin_<?php echo $style;?>.css" rel="stylesheet" media="all" type="text/css" />
        <script type="text/javascript" src="js/jquery.js"></script>
        <script type="text/javascript" src="js/jquery.newsticker.js"></script>
        <script type="text/javascript" src="js/script.js"></script>
        <link href="css/style_jquery.css" rel="stylesheet" type="text/css" />
