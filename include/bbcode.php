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

File: bbcode.php
Version: 1
Licence: GPLv3
Description: bbcode

***************************************************************************/

function bbc($text)
{
    $text = preg_replace('#\[b\](.+)\[/b\]#isU', '<strong>$1</strong>', $text);
    $text = preg_replace('#\[i\](.+)\[/i\]#isU', '<em>$1</em>', $text);
    $text = preg_replace('#\n#isU', '<br //>', $text);
    return $text;
}
?>
