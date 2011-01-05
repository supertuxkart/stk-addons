<?php
/*
copyright 2011 Lucas Baudin <xapantu@gmail.com>                   
                                                                          
This file is part of stkaddons

stkaddons is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

stkaddons is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of       
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with stkaddons.  If not, see <http://www.gnu.org/licenses/>.
*/

function get($arg)
{
    if(isset($_GET[$arg]))
        return mysql_real_escape_string($_GET[$arg]);
    else
        return false;
}
function post($arg)
{
    return mysql_real_escape_string($_POST[$arg]);
}
?>
