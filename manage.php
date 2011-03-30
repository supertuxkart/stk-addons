<?php
/**
 * copyright 2011 Stephen Just <stephenjust@users.sourceforge.net>
 *
 * This file is part of stkaddons
 *
 * stkaddons is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * stkaddons is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with stkaddons.  If not, see <http://www.gnu.org/licenses/>.
 */

define('ROOT','./');
$security = 'managesettings';
require('include.php');
require('include/top.php');
require('include/menu.php');
if (!isset($_GET['action'])) $_GET['action'] = NULL;
?>

<div id="left-menu">
    <div id="left-menu_top"></div>
    <div id="left-menu_body">
        <ul>
           <li>
               <a class="menu-item" href="javascript:loadFrame('general','manage-panel.php');">
                   <?php echo _('General Settings'); ?>
               </a>
           </li>
           <li>
               <a class="menu-item" href="javascript:loadFrame('news','manage-panel.php');">
                   <?php echo _('News Messages'); ?>
               </a>
           </li>
           <li>
               <a class="menu-item" href="javascript:loadFrame('clients','manage-panel.php');">
                   <?php echo _('Client Versions'); ?>
               </a>
           </li>
        </ul>
    </div>
    <div id="left-menu_bottom"></div>
</div>
<div id="right-content">
    <div id="right-content_top"></div>
    <div id="right-content_status">
<?php
switch ($_GET['action'])
{
    default:
        break;
    case 'save_config':
        if (!isset($_POST['xml_frequency']))
        {
            echo '<span class="error">'._('One or more fields has been left blank. Settings were not saved.').'</span><br />';
            break;
        }
        if (!is_numeric($_POST['xml_frequency']))
        {
            echo '<span class="error">'._('XML Download Frequency value is invalid.').'</span><br />';
        }
        else
        {
            set_config('xml_frequency',(int)$_POST['xml_frequency']);
        }
        echo _('Saved settings.').'<br />';
        break;
    case 'new_news':
        if (!isset($_POST['message']) || !isset($_POST['condition']))
            break;
        if (strlen($_POST['message']) == 0)
            break;
        $new_message = mysql_real_escape_string($_POST['message']);
        $reqSql = 'INSERT INTO `'.DB_PREFIX.'news`
            (`author_id`,`content`,`condition`,`active`)
            VALUES
            ('.$_SESSION['userid'].',\''.$new_message.'\',\''.mysql_real_escape_string($_POST['condition']).'\',1)';
        $handle = sql_query($reqSql);
        if ($handle)
        {
            echo _('Created message.').'<br />';
        }
        else
        {
            echo '<span class="error">'._('Failed to create message.').'</span><br />';
            break;
        }
        // Regenerate xml
        writeNewsXML();
        break;
}
?>
    </div>
    <div id="right-content_body"></div>
    <div id="right-content_bottom"></div>
</div>
<?php
if (!isset($_GET['view']))
    $_GET['view'] = 'general';
?>
<script type="text/javascript">
    loadFrame('<?php echo htmlentities($_GET['view']); ?>','manage-panel.php');
</script>
<?php

require('include/footer.php');
?>
