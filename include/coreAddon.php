<?php
/* copyright 2010 Lucas Baudin <xapantu@gmail.com>                 
                                                                              
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

class coreAddon
{
    //e.g. karts or tracks
    var $addonType;
    var $reqSql;
    var $addonCurrent;

    function coreAddon($type)
    {
        $this->addonType = $type;
    }

    function selectById($id, $rev = NULL)
    {
        if ($rev == NULL) {
            $querySql = 'SELECT a.*, r.id AS fileid, r.creation_date AS revision_timestamp,
                    r.revision, r.format, r.image, r.status
                FROM '.DB_PREFIX.$this->addonType.' a
                LEFT JOIN '.DB_PREFIX.$this->addonType.'_revs r
                ON a.id = r.addon_id
                WHERE a.id = \''.$id.'\'
                ORDER BY r.revision DESC';
        }
        else
        {
            $querySql = 'SELECT a.*, r.id AS fileid, r.creation_date AS revision_timestamp,
                    r.revision, r.format, r.image, r.status
                FROM '.DB_PREFIX.$this->addonType.' a
                LEFT JOIN '.DB_PREFIX.$this->addonType.'_revs r
                ON a.id = r.addon_id
                WHERE a.id = \''.$id.'\'
                AND r.revision = \''.$rev.'\'';
        }
        $this->reqSql = sql_query($querySql);
        $this->addonCurrent = sql_next($this->reqSql);
        if ($this->addonCurrent)
            $this->addonCurrent['permUrl'] = 'http://'.$_SERVER['SERVER_NAME'].
                    str_replace("addon.php", "addon-view.php", $_SERVER['SCRIPT_NAME']).
                    '?addons='.$this->addonType.'&amp;title='.$this->addonCurrent['id'];
    }

    function selectByUser($id)
    {
        $querySql = 'SELECT a.*, r.id AS fileid,
                r.creation_date AS revision_timestamp, r.revision,
                r.format, r.image, r.status
            FROM '.DB_PREFIX.$this->addonType.' a
            LEFT JOIN '.DB_PREFIX.$this->addonType.'_revs r
            ON a.id = r.addon_id
            WHERE a.uploader = \''.$id.'\'';
        $this->reqSql = sql_query($querySql);
        $this->addonCurrent = sql_next($this->reqSql);
        if ($this->addonCurrent)
            $this->addonCurrent['permUrl'] = 'http://'.$_SERVER['SERVER_NAME'].
                    str_replace("addon.php", "addon-view.php", $_SERVER['SCRIPT_NAME']).
                    '?addons='.$this->addonType.'&amp;title='.$this->addonCurrent['id'];
    }

    function loadAll()
    {
        $querySql = 'SELECT a.*, r.id AS fileid, r.revision, r.format, r.image, r.status
            FROM '.DB_PREFIX.$this->addonType.' a
            LEFT JOIN '.DB_PREFIX.$this->addonType.'_revs r
            ON a.id = r.addon_id
            WHERE r.status & '.F_LATEST.'
            ORDER BY a.`name` ASC, a.`id` ASC';
        $this->reqSql = sql_query($querySql);
        return $this->reqSql;
    }

    function next()
    {
        $this->addonCurrent = sql_next($this->reqSql);
        if(!$this->addonCurrent)
            return false;
        $this->addonCurrent['permUrl'] = 'http://'.$_SERVER['SERVER_NAME'].
                str_replace("addon.php", "addon-view.php", $_SERVER['SCRIPT_NAME']).
                '?addons='.$this->addonType.'&amp;title='.$this->addonCurrent['id'];
        return true;
    }

    /**
     * Change the approval value on the add-on revision
     * @global user $user
     * @return boolean Success
     */
    function approve()
    {
        global $user;
        if (!$user->logged_in)
            return false;
        if($_SESSION['role']['manageaddons'] != true)
            return false;

        /* if the addons is already available, we want to deactivate it :
            $is_available = abs(1 - 1) = 0
           else, it isn't and we want to activate it:
            $is_available = abs(0 - 1) = 1
         */
        $current_status = bindec($this->addonCurrrent['status']);
        if ($current_status & F_APPROVED)
        {
            $current_status = $current_status - F_APPROVED;
        }
        else
        {
            $current_status = $current_status + F_APPROVED;
        }
        if (!sql_update($this->addonType.'_revs', "id",
                   $this->addonCurrent['id'],
                   "status",
                   decbin($current_status)))
            return false;
        return true;
    }

    function setFile($filetype = "image")
    {
        if($_SESSION['role']['manageaddons'] == true || $this->addonCurrent['user'] == $_SESSION['userid'])
        {
            if (isset($_FILES['fileSend']))
            {
                $file_path = UP_LOCATION.$_POST['fileType'].'/'.$this->addonCurrent[$filetype];
        		if(file_exists($file_path))
                {
                    /* Remove the existing file before copy the new one. */
                    /* FIXME: is it really needed? */
                    unlink($file_path);
                }
                /* Move the file which has been sent to it permanent location. */
                move_uploaded_file($_FILES['fileSend']['tmp_name'], $file_path);
            }
        }
    }

    /** Set an information of the addon.
        \param $info The name of the information (e.g. 'name', 'version')
        \param $value The new value of the information (e.g. 'Tux', 'Adiumy')
    */
    function setInformation($info, $value)
    {
        global $user;
        if (!$user->logged_in)
            return false;

        if ($_SESSION['role']['manageaddons'] != true && $this->addonCurrent['uploader'] != $_SESSION['userid'])
            return false;
        if (sql_exist("properties", "name", $info))
        {
            $propertie_sql = sql_get_all_where("properties", "name", $info);
            $propertie = sql_next($propertie_sql);
            if($propertie["lock"] != 1)
            {
                if($propertie['typefield'] == "file")
                {
                    $this->setFile(post('fileType'));
                }
                else
                {
                    sql_update($this->addonType, "id", $this->addonCurrent['id'], $propertie['name'], $value);

                }
                return true;
            }
        }
    }

    /** Remove the selected addons. */
    function remove()
    {
        global $user;
        if (!$user->logged_in)
            return false;
        if($_SESSION['role']['manageaddons'] != true)
            return false;
        sql_remove_where($this->addonType, "id", $this->addonCurrent['id']);
        return true;
    }

    /** Print the information of the addon, it name, it description, it
      * version...
      */
    function writeInformation()
    {
        $addonUser = new coreUser();
        $addonUser->selectById($this->addonCurrent['uploader']);
        if ($this->addonCurrent['designer'] == NULL)
            $this->addonCurrent['designer'] = '<em>'._('Unknown').'</em>';
        if ($this->addonCurrent['description'] == NULL)
            $description = NULL;
        else
            $description = htmlentities ($this->addonCurrent['description']).'<br />';

        //div for jqery TODO:add jquery effects
        echo '<div id="accordion"><div>
        <h1>'.$this->addonCurrent['name'].'</h1>
        <img class="preview" src="image.php?type=big&amp;pic=images/'.$this->addonCurrent['image'].'" style="float: right;" />
        '.$description.'
        <table>
        <tr><td><strong>'._('Designer:').'</strong></td><td>'.$this->addonCurrent['designer'].'</td></tr>
        <tr><td><strong>'._('Upload date:').'</strong></td><td>'.$this->addonCurrent['revision_timestamp'].'</td></tr>
        <tr><td><strong>'._('Submitted by:').'</strong></td><td><a href="account.php?title='.$addonUser->userCurrent['id'].'">'.$addonUser->userCurrent['name'].'</a></td></tr>
        <tr><td><strong>'._('Revision:').'</strong></td><td>'.$this->addonCurrent['revision'].'</td></tr>
        <tr><td><strong>'._('Compatible with:').'</strong></td><td>'.format_compat($this->addonCurrent['format'],$this->addonType).'</td></tr>
        </table></div>

        <a href="'.DOWN_LOCATION.$this->addonCurrent['fileid'].'.zip"><img src="image/download.png" alt="Download" title="Download" /></a>

        <br /><br /><strong>'._('Permalink:').'</strong><br />
        '.$this->addonCurrent['permUrl'].'<br />';

        $addonRevs = new coreAddon($this->addonType);
        $addonRevs->selectById($this->addonCurrent['id']);
        echo '<strong>'._('Revisions:').'</strong><br />';
        echo '<table>';
        while ($addonRevs->addonCurrent)
        {
            echo '<tr><td>'.$addonRevs->addonCurrent['revision_timestamp'].'</td>
                <td><a href="'.DOWN_LOCATION.$addonRevs->addonCurrent['fileid'].'.zip">'._('Download revision').' '.$addonRevs->addonCurrent['revision'].'</a></td></tr>';
            $addonRevs->next();
        }
        echo '</table>';

    }

    /* FIXME: this function needs a lot of cleanup / a rewrite. */
    function writeConfig()
    {
        // Check permission
        global $user;
        if ($user->logged_in == false)
            return false;
        if ($_SESSION['role']['manageaddons'] == false && $this->addonCurrent['uploader'] != $_SESSION['userid'])
            return false;

        echo '<hr /><h3>Configuration</h3>';
        // Edit description
        echo '<form name="changeDesc" action="'.$this->addonCurrent['permUrl'].'&amp;save=desc" method="POST">';
        echo '<strong>'._('Description:').'</strong> ('._('Max 140 characters').')<br />';
        echo '<textarea name="description" id="desc_field" rows="4" cols="60">'.$this->addonCurrent['description'].'</textarea><br />';
        echo '<input type="submit" value="'._('Save Description').'" />';
        echo '</form><br />';

        // Add revision
        if ($this->addonCurrent['uploader'] == $_SESSION['userid'])
        {
            echo '<strong>'._('Add revision:').'</strong><br />';
            echo '<form name="addRevision" enctype="multipart/form-data" action="'.$this->addonCurrent['permUrl'].'&amp;save=rev" method="POST">';
            echo '<strong>'._('File:').'</strong> <input type="file" name="file_addon" /><br />';
            echo _('Supported file types are:').' .zip<br />';
            echo '<input type="submit" value="'._('Upload File').'" /><br />';
            echo '</form><br />';
        }

        // Set status flags
        echo '<strong>'._('Status Flags:').'</strong><br />';
        echo '<form method="POST" action="'.$this->addonCurrent['permUrl'].'&amp;save=status">';
        echo '<table id="addon_flags"><tr><th></th>';
        if ($_SESSION['role']['manageaddons'])
            echo '<th>'.img_label(_('Approved')).'</th>';
        echo '<th>'.img_label(_('Alpha')).'</th>
            <th>'.img_label(_('Beta')).'</th>
            <th>'.img_label(_('Release-Candidate')).'</th>
            <th>'.img_label(_('Latest')).'</th>';
        if ($_SESSION['role']['manageaddons'])
            echo '<th>'.img_label(_('Fan-Made')).'</th>
                <th>'.img_label(_('High-Quality')).'</th>
                <th>'.img_label(_('DFSG Compliant')).'</th>
                <th>'.img_label(_('Featured')).'</th>';
        echo '<th>'.img_label(_('Invalid Textures')).'</th>';
        echo '</tr>';
        $addonRevs = new coreAddon($this->addonType);
        $addonRevs->selectById($this->addonCurrent['id']);
        $fields = array();
        $fields[] = 'latest';
        while ($addonRevs->addonCurrent)
        {
            echo '<tr><td style="text-align: center;">Rev '.$addonRevs->addonCurrent['revision'].'</td>';

            // F_APPROVED
            if ($_SESSION['role']['manageaddons'] == true)
            {
                echo '<td>';
                if ($addonRevs->addonCurrent['status'] & F_APPROVED)
                {
                    echo '<input type="checkbox" name="approved-'.$addonRevs->addonCurrent['revision'].'" checked />';
                }
                else
                {
                    echo '<input type="checkbox" name="approved-'.$addonRevs->addonCurrent['revision'].'" />';
                }
                echo '</td>';
                $fields[] = 'approved-'.$addonRevs->addonCurrent['revision'];
            }

            // F_ALPHA
            echo '<td>';
            if ($addonRevs->addonCurrent['status'] & F_ALPHA)
            {
                echo '<input type="checkbox" name="alpha-'.$addonRevs->addonCurrent['revision'].'" checked />';
            }
            else
            {
                echo '<input type="checkbox" name="alpha-'.$addonRevs->addonCurrent['revision'].'" />';
            }
            echo '</td>';
            $fields[] = 'alpha-'.$addonRevs->addonCurrent['revision'];

            // F_BETA
            echo '<td>';
            if ($addonRevs->addonCurrent['status'] & F_BETA)
            {
                echo '<input type="checkbox" name="beta-'.$addonRevs->addonCurrent['revision'].'" checked />';
            }
            else
            {
                echo '<input type="checkbox" name="beta-'.$addonRevs->addonCurrent['revision'].'" />';
            }
            echo '</td>';
            $fields[] = 'beta-'.$addonRevs->addonCurrent['revision'];

            // F_RC
            echo '<td>';
            if ($addonRevs->addonCurrent['status'] & F_RC)
            {
                echo '<input type="checkbox" name="rc-'.$addonRevs->addonCurrent['revision'].'" checked />';
            }
            else
            {
                echo '<input type="checkbox" name="rc-'.$addonRevs->addonCurrent['revision'].'" />';
            }
            echo '</td>';
            $fields[] = 'rc-'.$addonRevs->addonCurrent['revision'];

            // F_LATEST
            echo '<td>';
            if ($addonRevs->addonCurrent['status'] & F_LATEST)
            {
                echo '<input type="radio" name="latest" value="'.$addonRevs->addonCurrent['revision'].'" checked />';
            }
            else
            {
                echo '<input type="radio" name="latest" value="'.$addonRevs->addonCurrent['revision'].'" />';
            }
            echo '</td>';

            if ($_SESSION['role']['manageaddons'])
            {
                // F_FANMADE
                echo '<td>';
                if ($addonRevs->addonCurrent['status'] & F_FANMADE)
                {
                    echo '<input type="checkbox" name="fanmade-'.$addonRevs->addonCurrent['revision'].'" checked />';
                }
                else
                {
                    echo '<input type="checkbox" name="fanmade-'.$addonRevs->addonCurrent['revision'].'" />';
                }
                echo '</td>';
                $fields[] = 'fanmade-'.$addonRevs->addonCurrent['revision'];

                // F_HQ
                echo '<td>';
                if ($addonRevs->addonCurrent['status'] & F_HQ)
                {
                    echo '<input type="checkbox" name="hq-'.$addonRevs->addonCurrent['revision'].'" checked />';
                }
                else
                {
                    echo '<input type="checkbox" name="hq-'.$addonRevs->addonCurrent['revision'].'" />';
                }
                echo '</td>';
                $fields[] = 'hq-'.$addonRevs->addonCurrent['revision'];

                // F_DFSG
                echo '<td>';
                if ($addonRevs->addonCurrent['status'] & F_DFSG)
                {
                    echo '<input type="checkbox" name="dfsg-'.$addonRevs->addonCurrent['revision'].'" checked />';
                }
                else
                {
                    echo '<input type="checkbox" name="dfsg-'.$addonRevs->addonCurrent['revision'].'" />';
                }
                echo '</td>';
                $fields[] = 'dfsg-'.$addonRevs->addonCurrent['revision'];

                // F_FEATURED
                echo '<td>';
                if ($addonRevs->addonCurrent['status'] & F_FEATURED)
                {
                    echo '<input type="checkbox" name="featured-'.$addonRevs->addonCurrent['revision'].'" checked />';
                }
                else
                {
                    echo '<input type="checkbox" name="featured-'.$addonRevs->addonCurrent['revision'].'" />';
                }
                echo '</td>';
                $fields[] = 'featured-'.$addonRevs->addonCurrent['revision'];
            }

            // F_TEX_NOT_POWER_OF_2
            echo '<td>';
            if ($addonRevs->addonCurrent['status'] & F_TEX_NOT_POWER_OF_2)
            {
                echo '<input type="checkbox" name="texpower-'.$addonRevs->addonCurrent['revision'].'" checked disabled />';
            }
            else
            {
                echo '<input type="checkbox" name="texpower-'.$addonRevs->addonCurrent['revision'].'" disabled />';
            }
            echo '</td>';
            
            echo '</tr>';
            $addonRevs->next();
        }
        echo '</table>';
        echo '<input type="hidden" name="fields" value="'.implode(',',$fields).'" />';
        echo '<input type="submit" value="'._('Save Changes').'" />';
        echo '</form>';
    }

    function viewInformation($config=True)
    {
        global $user;
        if ($user->logged_in == false)
            return false;

        $this->writeInformation();
        //write configuration for the submiter and administrator
        if(($_SESSION['role']['manageaddons'] == true || $this->addonCurrent['uploader'] == $_SESSION['userid']) && $config)
        {
            $this->writeConfig();
        }
    }

    /* FIXME: please cleanup me! */
    /* FIXME: this function needs a _lot_ of a tests. */
    function addAddon($fileid, $addonid, $attributes)
    {
	global $user;
        // Check if logged in
        if (!$user->logged_in) {
            return false;
        }

        // Make sure no addon with this id exists
        if(sql_exist($this->addonType.'s_revs', "id", $fileid))
        {
            echo '<span class="error">'._('The add-on you are trying to create already exists.').'</span><br />';
            return false;
        }

        // Check if we're creating a new add-on
        if (!sql_exist($this->addonType.'s', 'id', $addonid))
        {
            echo _('Creating a new add-on...').'<br />';
            $fields = array('id','name','uploader','designer');
            $values = array($addonid,$attributes['name'],$_SESSION['userid'],$attributes['designer']);
            if ($this->addonType == 'track')
            {
                $fields[] = 'arena';
                $values[] = $attributes['arena'];
            }
            if (!sql_insert($this->addonType.'s',$fields,$values))
                return false;
        }
        else
        {
            echo _('This add-on already exists. Adding revision...').'<br />';
        }

        // Add the new revision
        $prevRevQuerySql = 'SELECT `revision` FROM '.DB_PREFIX.$this->addonType.'s_revs
            WHERE `addon_id` = \''.$addonid.'\' ORDER BY `revision` DESC LIMIT 1';
        $reqSql = sql_query($prevRevQuerySql);
        if (!$reqSql)
        {
            echo '<span class="error">'._('Failed to check for previous add-on revisions.').'</span><br />';
            return false;
        }
        if (mysql_num_rows($reqSql) == 0)
        {
            $rev = 1;
        }
        else
        {
            $result = mysql_fetch_assoc($reqSql);
            $rev = $result['revision'] + 1;
        }
        // Add revision entry
        $fields = array('id','addon_id','revision','format','image','status');
        $values = array($fileid,$addonid,$rev,$attributes['version'],$attributes['image'],$attributes['status']);
        if (!sql_insert($this->addonType.'s_revs',$fields,$values))
            return false;
        return true;
    }

    /** To get the permanent link of the current addon */ 
    function permalink()
    {
        return 'addon-view.php?addons='.$this->addonType.'&amp;title='.$this->addonCurrent['name'];
    }
}

function addon_id_clean($string)
{
    if (!is_string($string))
        return false;
    $length = strlen($string);
    if ($length == 0)
        return false;
    $string = strtolower($string);
    // Validate all characters in addon id
    // Rather than using str_replace, and removing bad characters,
    // it makes more sense to only allow certain characters
    for ($i = 0; $i<$length; $i++)
    {
        $substr = substr($string,$i,1);
        if (!preg_match('/^[a-z0-9\-_]$/i',$substr))
            $substr = '-';
        $string = substr_replace($string,$substr,$i,1);
    }
    return $string;
}

function set_description($addon_type,$addon_id,$rev,$description)
{
    // Validate parameters
    if ($addon_type != 'karts' && $addon_type != 'tracks')
        return false;
    $addon_id = addon_id_clean($addon_id);
    if (!is_numeric($rev))
        return false;
    $rev = (int)$rev;
    $description = mysql_escape_string($description);

    // Check if addon exists, and permissions
    $addon = new coreAddon($addon_type);
    $addon->selectById($addon_id,$rev);
    if (!$addon->addonCurrent)
        return false;
    if (!$_SESSION['role']['manageaddons'] && $_SESSION['userid'] != $addon->addonCurrent['uploader'])
        return false;

    $update_query = 'UPDATE `'.DB_PREFIX.$addon_type.'`
        SET `description` = \''.$description.'\'
        WHERE `id` = \''.$addon_id.'\'';
    $reqSql = sql_query($update_query);
    if (!$reqSql)
        return false;
    return true;
}

function update_status($type,$addon_id,$fields)
{
    if ($type != 'karts' && $type != 'tracks')
        return false;
    $addon_id = addon_id_clean($addon_id);
    $fields = explode(',',$fields);
    $status = array();
    foreach ($fields AS $field)
    {
        if (!isset($_POST[$field]))
            $_POST[$field] = NULL;
        if ($field == 'latest')
            $fieldinfo = array('',(int)$_POST['latest']);
        else
            $fieldinfo = explode('-',$field);
        // Initialize the status of the current revision if it has
        // not been created yet.
        if (!isset($status[$fieldinfo[1]]))
            $status[$fieldinfo[1]] = 0;
        if ($field == 'latest')
        {
            $status[(int)$_POST['latest']] += F_LATEST;
            continue;
        }
        // Update status values for all flags
        if ($_POST[$field] == 'on')
        {
            $revision = (int)$fieldinfo[1];
            switch ($fieldinfo[0])
            {
                default: break;
                case 'approved':
                    $status[$revision] += F_APPROVED;
                    break;
                case 'alpha':
                    $status[$revision] += F_ALPHA;
                    break;
                case 'beta':
                    $status[$revision] += F_BETA;
                    break;
                case 'rc':
                    $status[$revision] += F_RC;
                    break;
                case 'fanmade':
                    $status[$revision] += F_FANMADE;
                    break;
                case 'hq':
                    $status[$revision] += F_HQ;
                    break;
                case 'dfsg':
                    $status[$revision] += F_DFSG;
                    break;
                case 'featured':
                    $status[$revision] += F_FEATURED;
                    break;
            }
        }
    }
    $error = 0;
    foreach ($status AS $revision => $value)
    {
        // Check if F_TEX_NOT_POWER_OF_2 is set in database
        $addon = new coreAddon($type);
        $addon->selectById($addon_id,$revision);
        if ($addon->addonCurrent['status'] & F_TEX_NOT_POWER_OF_2)
            $value += F_TEX_NOT_POWER_OF_2;
        $query = 'UPDATE `'.DB_PREFIX.$type.'_revs`
            SET `status` = '.$value.'
            WHERE `addon_id` = \''.$addon_id.'\'
            AND `revision` = '.$revision;
        $reqSql = sql_query($query);
        if (!$reqSql)
            $error = 1;
    }
    if ($error != 1)
        return true;
    return false;
}

function format_compat($format,$filetype)
{
    // FIXME: Stub
    return 'Unknown';
}
?>
