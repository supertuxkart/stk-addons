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

if (!defined('ROOT'))
    define('ROOT','./');
require_once('include.php');
AccessControl::setLevel('manageaddons');

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
            echo htmlspecialchars(_('Deleted message.')).'<br />';
        }
        else
        {
            echo '<span class="error">'.htmlspecialchars(_('Failed to delete message.')).'</span><br />';
            break;
        }
        // Regenerate xml
        writeNewsXML();
        break;
    case 'cache_clear':
        Cache::clear();
        echo 'Emptied cache.<br />';
        break;
}

switch ($_POST['id'])
{
    default:
        echo '<span class="error">'.htmlspecialchars(_('Invalid page. You may have followed a broken link.')).'</span><br />';
        exit;
    case 'overview':
	// I18N: Moderator panel
	echo '<h1>'.htmlspecialchars(_('Overview')).'</h1>';
	overview_panel();
	break;
    case 'general':
	// I18N: Moderator panel
        echo '<h1>'.htmlspecialchars(_('General Settings')).'</h1>';
        settings_panel();
        break;
    case 'news':
	// I18N: Moderator panel
        echo '<h1>'.htmlspecialchars(_('News Messages')).'</h1>';
        news_message_panel();
        break;
    case 'files':
        echo '<h1>'.htmlspecialchars(_('Uploaded Files')).'</h1>';
        files_panel();
        break;
    case 'clients':
        echo '<h1>'.htmlspecialchars(_('Client Versions')).'</h1>';
        clients_panel();
        break;
    case 'cache':
        echo '<h1>'.htmlspecialchars(_('Cache Files')).'</h1>';
        cache_panel();
        break;
    case 'logs':
	// I18N: Moderator panel
        echo '<h1>'.htmlspecialchars(_('Event Logs')).'</h1>';
        logs_panel();
        break;
}

function overview_panel()
{
    // Get all add-ons
    $addons = array_merge(Addon::getAddonList('karts'),
		    Addon::getAddonList('tracks'),
		    Addon::getAddonList('arenas'));

    // I18N: Heading in moderator overview panel
    echo '<h2>'.htmlspecialchars(_('Unapproved Add-Ons')).'</h2>';
    // I18N: Notice on unapproved add-on list in moderator overview panel
    echo '<p>'.htmlspecialchars(_('Note that only add-ons where the newest revision is unapproved will appear here.')).'</p>';
    // Loop through each add-on
    $empty_section = true;
    foreach ($addons as $addon) {
	$a = new Addon($addon);
	$revisions = $a->getAllRevisions();
	reset($revisions);
	$unapproved = array();
	$revision = current($revisions);
	for ($i = 0; $i < count($revisions); $i++) {
	    if (!($revision['status'] & F_APPROVED))
		$unapproved[] = $revision['revision'];
	    if ($i+1 < count($revisions))
		$revision = next($revisions);
	}
	// Don't list if the latest revision is approved
	if ($revision['status'] & F_APPROVED)
	    $unapproved = array();
	
	if ($unapproved !== array()) {
	    $empty_section = false;
	    echo '<strong><a href="'.$a->getLink().'">'.Addon::getName($addon).'</a></strong><br />';
	    echo htmlspecialchars(_('Revisions:')).' '.implode(', ',$unapproved);
	    echo '<br /><br />';
	}
    }
    if ($empty_section === true)
	echo htmlspecialchars(_('No unapproved add-ons.')).'<br /><br />';
    
    
    echo '<h2>'.htmlspecialchars(_('Unapproved Files')).'</h1>';
    echo '<h3>'.htmlspecialchars(_('Images:')).'</h3>';
    $empty_section = true;
    foreach ($addons as $addon) {
	$a = new Addon($addon);
	$images = $a->getImages();
	$unapproved = array();
	for ($i = 0; $i < count($images); $i++) {
	    if ($images[$i]['approved'] == 0)
		$unapproved[] = '<img src="'.ROOT.'image.php?type=medium&amp;pic='.$images[$i]['file_path'].'" />';
	}
	if ($unapproved !== array()) {
	    $empty_section = false;
	    echo '<strong><a href="'.$a->getLink().'">'.Addon::getName($addon).'</a></strong><br />';
	    echo htmlspecialchars(_('Images:')).'<br />'.implode('<br />',$unapproved);
	    echo '<br /><br />';
	}
    }
    if ($empty_section === true)
	echo htmlspecialchars(_('No unapproved images.')).'<br /><br />';
    
    echo '<h3>'.htmlspecialchars(_('Source Archives:')).'</h3>';
    $empty_section = true;
    foreach ($addons as $addon) {
	$a = new Addon($addon);
	$images = $a->getSourceFiles();
	$unapproved = 0;
	for ($i = 0; $i < count($images); $i++) {
	    if ($images[$i]['approved'] == 0)
		$unapproved++;
	}
	if ($unapproved !== 0) {
	    $empty_section = false;
	    echo '<strong><a href="'.$a->getLink().'">'.Addon::getName($addon).'</a></strong><br />';
	    printf(htmlspecialchars(ngettext('%d File','%d Files',$unapproved)),$unapproved);
	    echo '<br /><br />';
	}
    }
    if ($empty_section === true)
	echo htmlspecialchars(_('No unapproved source archives.')).'<br /><br />';
}

function settings_panel()
{
    if (!$_SESSION['role']['managesettings']) return;
    echo '<form method="POST" action="manage.php?view=general&amp;action=save_config">';
    echo '<table>';
    echo '<tr><td>'.htmlspecialchars(_('XML Download Frequency')).'</td><td><input type="text" name="xml_frequency" value="'.ConfigManager::get_config('xml_frequency').'" size="6" maxlength="8" /></td></tr>';
    echo '<tr><td>'.htmlspecialchars(_('Permitted Addon Filetypes')).'</td><td><input type="text" name="allowed_addon_exts" value="'.ConfigManager::get_config('allowed_addon_exts').'" /></td></tr>';
    echo '<tr><td>'.htmlspecialchars(_('Permitted Source Archive Filetypes')).'</td><td><input type="text" name="allowed_source_exts" value="'.ConfigManager::get_config('allowed_source_exts').'" /></td></tr>';
    echo '<tr><td>'.htmlspecialchars(_('Administrator Email')).'</td><td><input type="text" name="admin_email" value="'.ConfigManager::get_config('admin_email').'" /></td></tr>';
    echo '<tr><td>'.htmlspecialchars(_('Moderator List Email')).'</td><td><input type="text" name="list_email" value="'.ConfigManager::get_config('list_email').'" /></td></tr>';
    if (ConfigManager::get_config('list_invisible') == 1)
        $invisible_opts = '<option value="1" selected>'.htmlspecialchars(_('True')).'</option><option value="0">'.htmlspecialchars(_('False')).'</option>';
    else
        $invisible_opts = '<option value="1">'.htmlspecialchars(_('True')).'</option><option value="0" selected>'.htmlspecialchars(_('False')).'</option>';
    echo '<tr><td>'.htmlspecialchars(_('List Invisible Addons in XML')).'</td><td><select name="list_invisible">'.$invisible_opts.'</option></td></tr>';
    echo '<tr><td>'.htmlspecialchars(_('Blog RSS Feed')).'</td><td><input name="blog_feed" value="'.ConfigManager::get_config('blog_feed').'" /></td></tr>';
    echo '<tr><td>'.htmlspecialchars(_('Maximum Uploaded Image Dimension')).'</td><td><input name="max_image_dimension" value="'.ConfigManager::get_config('max_image_dimension').'" /></td></tr>';
    echo '<tr><td>'.htmlspecialchars(_('Apache Rewrites')).'</td><td><textarea name="apache_rewrites">'.htmlspecialchars(ConfigManager::get_config('apache_rewrites')).'</textarea></td></tr>';
    echo '<tr><td></td><td><input type="submit" value="'.htmlspecialchars(_('Save Settings')).'" /></td></tr>';
    echo '</table>';
}

function news_message_panel()
{
    if (!$_SESSION['role']['managesettings']) return;
    echo '<form method="POST" action="manage.php?view=news&amp;action=new_news"><table><tr>';
    echo '<td>'.htmlspecialchars(_('Message:')).'</td><td><input type="text" name="message" id="news_message" size="60" maxlength="140" /></td></tr><tr>';
    echo '<td>'.htmlspecialchars(_('Condition:')).'</td><td><input type="text" name="condition" id="news_condition" size="60" maxlength="255" /></td></tr><tr>';
    echo '<td>'.htmlspecialchars(_('Display on Website:')).'</td><td><input type="checkbox" name="web_display" id="web_display" checked /></td></tr>';
    echo '<td></td><td><input type="submit" value="'.htmlspecialchars(_('Create Message')).'" /></td></tr></table>';
    echo '</form>';
    echo 'Todo:<ol><li>Allow selecting from a list of conditions rather than typing. Too typo-prone.</li><li>Type semicolon-delimited expressions, e.g. <tt>stkversion > 0.7.0;addonid not installed;</tt>.</li><li>Allow editing in future, in case of goofs or changes.</li></ol>';
    echo '<br />';

    $reqSql = 'SELECT `n`.*, `u`.`user`
	FROM '.DB_PREFIX.'news n
	LEFT JOIN '.DB_PREFIX.'users u
	ON (`n`.`author_id`=`u`.`id`)
        ORDER BY `n`.`id` DESC';
    $handle = sql_query($reqSql);
    if (!$handle)
    {
        echo htmlspecialchars(_('No news messages currently exist.')).'<br />';
    }
    else
    {
        echo '<table width="100%"><tr>
            <th width="100">'.htmlspecialchars(_('Date:')).'</th>
            <th>'.htmlspecialchars(_('Message:')).'</th>
            <th>'.htmlspecialchars(_('Author:')).'</th>
            <th>'.htmlspecialchars(_('Condition:')).'</th>
            <th>'.htmlspecialchars(_('Web:')).'</th>
            <th>'.htmlspecialchars(_('Actions:')).'</th></tr>';
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

function files_panel()
{
    $files = File::getAllFiles();
    if (count($files) == 0) {
        echo htmlspecialchars(_('No files have been uploaded.'));
        return;
    }
    
    $name_label = htmlspecialchars(_('Name:'));
    $type_label = htmlspecialchars(_('Type:'));
    $references_label = htmlspecialchars(_('References:'));

    echo <<< EOF
<table class="info">
<thead>
<tr>
<th>$name_label</th>
<th>$type_label</th>
<th>$references_label</th>
</tr>
</thead>
<tbody>
EOF;
    $last_id = NULL;
    for ($i = 0; $i < count($files); $i++)
    {
        if ($last_id !== $files[$i]['addon_id']) {
            if ($files[$i]['addon_id'] === false) {
                echo '<tr><th colspan="3" align="left">unassociated</th></tr>';
            } else {
                echo '<tr><th colspan="3" align="left">'.$files[$i]['addon_id'].' ('.$files[$i]['addon_type'].')</th></tr>';
            }
            $last_id = $files[$i]['addon_id'];
        }
        // Get references to files
        switch ($files[$i]['file_type'])
        {
            default:
                $references = 'TODO';
                break;
            case false:
                $references = '<span class="error">No record found.</span>';
                break;
            case 'addon':
                $references = array();
                // Look for tracks with this file
                $refQuery = 'SELECT * FROM `'.DB_PREFIX.'tracks_revs`
                    WHERE `fileid` = '.$files[$i]['id'];
                $refHandle = sql_query($refQuery);
                for ($j = 0; mysql_num_rows($refHandle) > $j; $j++)
                {
                    $refResult = mysql_fetch_assoc($refHandle);
                    $references[] = $refResult['addon_id'].' (track)';
                }

                // Look for karts with this file
                $refQuery = 'SELECT * FROM `'.DB_PREFIX.'karts_revs`
                    WHERE `fileid` = '.$files[$i]['id'];
                $refHandle = sql_query($refQuery);
                for ($j = 0; mysql_num_rows($refHandle) > $j; $j++)
                {
                    $refResult = mysql_fetch_assoc($refHandle);
                    $references[] = $refResult['addon_id'].' (kart)';
                }
                
                // Look for arenas with this file
                $refQuery = 'SELECT * FROM `'.DB_PREFIX.'arenas_revs`
                    WHERE `fileid` = '.$files[$i]['id'];
                $refHandle = sql_query($refQuery);
                for ($j = 0; mysql_num_rows($refHandle) > $j; $j++)
                {
                    $refResult = mysql_fetch_assoc($refHandle);
                    $references[] = $refResult['addon_id'].' (arena)';
                }
                
                if (count($references) == 0)
                    $references[] = '<span class="error">None</span>';
                
                $references = implode(', ',$references);
                
                break;
        }
        if ($files[$i]['exists'] == false)
            $references .= ' <span class="error">File not found.</span>';

        echo "<tr><td>{$files[$i]['file_path']}</td>
            <td>{$files[$i]['file_type']}</td><td>$references</td></tr>";
    }
    echo '</tbody></table>';
}

function clients_panel()
{
    if (!$_SESSION['role']['managesettings']) return;
    echo '<h3>'.htmlspecialchars(_('Clients by User-Agent')).'</h3>';
    // Read recorded user-agents from database
    $clientsSql = 'SELECT * FROM `'.DB_PREFIX.'clients`
        ORDER BY `agent_string` ASC';
    $clientsHandle = sql_query($clientsSql);
    if (mysql_num_rows($clientsHandle) == 0)
    {
        echo htmlspecialchars(_('There are currently no SuperTuxKart clients recorded. Your download script may not be configured properly.')).'<br />';
    }
    else
    {
        echo '<table width="100%">';
        echo '<tr><th>'.htmlspecialchars(_('User-Agent String:')).'</th><th>'.htmlspecialchars(_('Game Version:')).'</th></tr>';
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

function cache_panel() {
    if (!$_SESSION['role']['managesettings']) return;
    echo '<a href="manage.php?view=cache&amp;action=cache_clear">'.htmlspecialchars(_('Empty cache')).'</a><br />';
    echo 'TODO: List cache files.<br />';
}

function logs_panel() {
    echo 'The table below lists the most recent logged events.<br /><br />';
    
    $events = Log::getEvents();
    if (count($events) === 0) {
        echo 'No events have been logged yet.<br />';
        return;
    }

    echo '<table width="100%">
        <thead>
        <tr>
        <th width="100px">Date</th><th>User</th><th>Description</th>
        </tr></thead>
        <tbody>';
    for ($i = 0; $i < count($events); $i++) {
        echo '<tr><td>'.$events[$i]['date'].'</td>
            <td>'.$events[$i]['name'].'</td>
            <td>'.$events[$i]['message'].'</td></tr>';
    }
    echo '</tbody></table>';
}

?>