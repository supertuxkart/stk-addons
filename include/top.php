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

if(!isset($title))
    $title=htmlspecialchars(_('SuperTuxKart Add-ons'));
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta content="text/html; charset=UTF-8" http-equiv="content-type" />
        <title><?php echo $title;?></title>
        <link href="<?php echo SITE_ROOT; ?>css/skin_<?php echo $style;?>.css" rel="stylesheet" media="all" type="text/css" />
        <script type="text/javascript" src="<?php echo SITE_ROOT; ?>js/jquery.js"></script>
        <script type="text/javascript" src="<?php echo SITE_ROOT; ?>js/jquery.newsticker.js"></script>
        <script type="text/javascript" src="<?php echo SITE_ROOT; ?>js/script.js"></script>
        <link href="<?php echo SITE_ROOT; ?>css/style_jquery.css" rel="stylesheet" type="text/css" />
