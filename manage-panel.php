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

if (!isset($_GET['action'])) $_GET['action'] = NULL;

switch ($_GET['action'])
{
    default:
        break;
    case 'del_news':
        if (!isset($_POST['value']))
            break;
        if (!is_numeric($_POST['value']))
            break;
        $del_id = (int)$_POST['value'];

        $reqSql = 'DELETE FROM `'.DB_PREFIX.'news`
            WHERE `id` = '.$del_id;
        $handle = sql_query($reqSql);
        if ($handle)
        {
            echo _('Deleted message.').'<br />';
        }
        else
        {
            echo '<span class="error">'._('Failed to delete message.').'</span><br />';
            break;
        }
        // Regenerate xml
        writeNewsXML();
        break;
}

switch ($_POST['id'])
{
    default:
        echo '<span class="error">'._('Invalid page. You may have followed a broken link.').'</span><br />';
        exit;
    case 'general':
        echo '<h1>'._('General Settings').'</h1>';
        settings_panel();
        break;
    case 'news':
        echo '<h1>'._('News Messages').'</h1>';
        news_message_panel();
        break;
    case 'clients':
        echo '<h1>'._('Client Versions').'</h1>';
        clients_panel();
        break;
}

function settings_panel()
{
    echo '<form method="POST" action="manage.php?view=general&amp;action=save_config">';
    echo '<table>';
    echo '<tr><td>'._('XML Download Frequency').'</td><td><input type="text" name="xml_frequency" value="'.get_config('xml_frequency').'" size="6" maxlength="8" /></td></tr>';
    echo '<tr><td>'._('Permitted Addon Filetypes').'</td><td><input type="text" name="allowed_addon_exts" value="'.get_config('allowed_addon_exts').'" /></td></tr>';
    echo '<tr><td>'._('Permitted Source Archive Filetypes').'</td><td><input type="text" name="allowed_source_exts" value="'.get_config('allowed_source_exts').'" /></td></tr>';
    echo '<tr><td></td><td><input type="submit" value="'._('Save Settings').'" /></td></tr>';
    echo '</table>';
}

function news_message_panel()
{
    echo '<form method="POST" action="manage.php?view=news&amp;action=new_news"><table><tr>';
    echo '<td>'._('Message:').'</td><td><input type="text" name="message" id="news_message" size="60" maxlength="140" /></td></tr><tr>';
    echo '<td>'._('Condition:').'</td><td><input type="text" name="condition" id="news_condition" size="60" maxlength="255" /></td></tr><tr>';
    echo '<td>'._('Display on Website:').'</td><td><input type="checkbox" name="web_display" id="web_display" checked /></td></tr>';
    echo '<td></td><td><input type="submit" value="'._('Create Message').'" /></td></tr></table>';
    echo '</form>';
    echo 'Todo:<ol><li>Allow selecting from a list of conditions rather than typing. Too typo-prone.</li><li>Type semicolon-delimited expressions, e.g. <tt>stkversion > 0.7.0;addonNotInstalled = {addonID};</tt>.</li><li>Allow editing in future, in case of goofs or changes.</li></ol>';
    echo '<br />';

    $reqSql = 'SELECT `n`.*, `u`.`user`
	FROM '.DB_PREFIX.'news n
	LEFT JOIN '.DB_PREFIX.'users u
	ON (`n`.`author_id`=`u`.`id`)
        ORDER BY `n`.`id` DESC';
    $handle = sql_query($reqSql);
    if (!$handle)
    {
        echo _('No news messages currently exist.').'<br />';
    }
    else
    {
        echo '<table width="100%"><tr>
            <th width="100">'._('Date:').'</th>
            <th>'._('Message:').'</th>
            <th>'._('Author:').'</th>
            <th>'._('Condition:').'</th>
            <th>'._('Web:').'</th>
            <th>'._('Actions:').'</th></tr>';
        for ($result = sql_next($handle); $result; $result = sql_next($handle))
        {
            echo '<tr>';
            echo '<td>'.$result['date'].'</td>';
            echo '<td>'.$result['content'].'</td>';
            echo '<td>'.$result['user'].'</td>';
            echo '<td>'.$result['condition'].'</td>';
            echo '<td>'.$result['web_display'].'</td>';
            echo '<td><a href="#" onClick="loadFrame(\'news\', \'manage-panel.php?action=del_news\', '.$result['id'].')">Delete</a></td>';
            echo '</tr>';
        }
        echo '</table>';
    }
}

function clients_panel()
{
    echo '<h3>'._('Clients by User-Agent').'</h3>';
    // Read recorded user-agents from database
    $clientsSql = 'SELECT * FROM `'.DB_PREFIX.'clients`
        ORDER BY `agent_string` ASC';
    $clientsHandle = sql_query($clientsSql);
    if (mysql_num_rows($clientsHandle) == 0)
    {
        echo _('There are currently no SuperTuxKart clients recorded. Your download script may not be configured properly.').'<br />';
    }
    else
    {
        echo '<table width="100%">';
        echo '<tr><th>'._('User-Agent String:').'</th><th>'._('Game Version:').'</th></tr>';
        for ($clientsResult = sql_next($clientsHandle); $clientsResult; $clientsResult = sql_next($clientsHandle))
        {
            echo '<tr><td>'.$clientsResult['agent_string'].'</td><td>'.$clientsResult['stk_version'].'</td></tr>';
        }
        echo '</table>';
    }
    echo <<< EOL
TODO:<br />
<ol>
    <li>Allow changing association of user-agent strings with versions of STK</li>
    <li>Allow setting various components of the generated XML for each different user-agent</li>
    <li>Make XML generating script generate files for each configuration set</li>
    <li>Make download script provide a certain file based on the user-agent</li>
</ol>
EOL;
}

?>