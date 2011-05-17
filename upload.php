<?php
/**
 * copyright 2009 Lucas Baudin <xapantu@gmail.com>
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
$security = 'addAddon';
include('include.php');
include('include/top.php');

// Define possibly undefined variables
$_GET['action'] = (isset($_GET['action'])) ? $_GET['action'] : null;
$_GET['type'] = (isset($_GET['type'])) ? $_GET['type'] : null;
$_GET['name'] = (isset($_GET['name'])) ? $_GET['name'] : null;
?>
<script type="text/javascript">
function uploadFormFieldToggle()
{
    var radio1 = document.getElementById('l_author1');
    var check1 = document.getElementById('l_licensefile1');
    var check2 = document.getElementById('l_licensefile2');
    var checklabel1 = document.getElementById('l_licensetext1');
    var checklabel2 = document.getElementById('l_licensetext2');
    if (radio1.checked)
    {
        check1.disabled = false;
        check2.disabled = true;
        checklabel1.style.color = '#000000';
        checklabel2.style.color = '#999999';
    }
    else
    {
        check1.disabled = true;
        check2.disabled = false;
        checklabel1.style.color = '#999999';
        checklabel2.style.color = '#000000';
    }
}
</script>
</head><body>
<?php
include(ROOT.'include/menu.php');

if($_GET['action'] == "submit")
{
    echo '<div id="content">';
    if (!isset($_POST['l_author'])
            || (!isset($_POST['l_licensefile1']) && !isset($_POST['l_licensefile2']))
            || (!isset($_POST['license_gpl']) && !isset($_POST['license_cc-by'])
                    && !isset($_POST['license_cc-by-sa'])
                    && !isset($_POST['license_pd'])
                    && !isset($_POST['license_bsd'])
                    && !isset($_POST['license_other']))
            || !isset($_POST['l_agreement'])
            || !isset($_POST['l_clean'])
            || ($_POST['l_author'] == 1 && !isset($_POST['l_licensefile1']))
            || ($_POST['l_author'] == 2 && !isset($_POST['l_licensefile2'])))
    {
        echo '<span class="error">'._('Your response to the agreement was unacceptable. You may not upload this content to the STK Addons website.').'</span><br />';
    }
    else
    {
        // Generate a note to moderators for license verification
        $moderator_message = NULL;
        if (isset($_POST['license_other']))
            $moderator_message .= 'Verify "other license"'."\n";
        if ($_POST['l_author'] == 1)
            $moderator_message .= 'Verify sole author'."\n";
        else
            $moderator_message .= 'Verify open content'."\n";
        if (isset($_GET['name']))
        {
            parseUpload($_FILES['file_addon'],true);
        }
        else
        {
            parseUpload($_FILES['file_addon']);
        }
    }
    echo '</div>';
    include('include/footer.php');
    exit;
}
?>
    <div id="content">
        <?php
        if (($_GET['type'] == 'karts' || $_GET['type'] == 'tracks')
                && strlen($_GET['name']) != 0)
        {
            // Working with an already existing addon
            echo '<form id="formKart" enctype="multipart/form-data" action="upload.php?type='.$_GET['type'].'&amp;name='.$_GET['name'].'&amp;action=submit" method="POST">';
            if ($_GET['action'] != 'file')
            {
                echo _('Please upload a new revision of your kart or track.').'<br />';
            }
            else
            {
                echo _('What type of file are you uploading?').'<br />';
                echo '<select name="upload-type">
                    <option value="source">'._('Source Archive').'</option>
                    <option value="image">'._('Image File').' (.png, .jpg, .jpeg)</option>
                    </select><br />';
            }
        }
        else
        {
            echo '<form id="formKart" enctype="multipart/form-data" action="upload.php?action=submit" method="POST">';
            echo _('Please upload a kart or track.').'<br />';
            echo _('Do not use this form if you are updating an existing add-on.'.'<br />');
        }
        ?>
        <label><?php echo _("File:"); ?><br /><input type="file" name="file_addon" /><br /></label>
        <?php echo _('Supported archive types are:'); ?> .zip<br /><br />
        <strong><?php echo _('Agreement:'); ?></strong><br />
        <table width="800" id="upload_agreement">
            <tr>
                <td width="1"><input type="radio" name="l_author" id="l_author1" value="1" onChange="uploadFormFieldToggle();" checked /></td>
                <td colspan="3">
                    <?php echo _('I am the sole author of every file (model, texture, sound effect, etc.) in this package'); ?>
                </td>
            </tr>
            <tr>
                <td colspan="2" width="60"></td>
                <td><input type="checkbox" name="l_licensefile1" id="l_licensefile1"></td>
                <td>
                    <span id="l_licensetext1"><?php echo _('I have included a License.txt file describing the license under which my work is released, and my name (or nickname) if I want credit.').' <strong>'._('Required').'</strong>'; ?></span>
                </td>
            </tr>
            <tr>
                <td width="1"><input type="radio" name="l_author" id="l_author2" value="2" onChange="uploadFormFieldToggle();" /></td>
                <td colspan="3">
                    <?php echo _('I have included open content made by people other than me'); ?>
                </td>
            </tr>
            <tr>
                <td colspan="2" width="60"></td>
                <td><input type="checkbox" name="l_licensefile2" id="l_licensefile2"></td>
                <td>
                    <span id="l_licensetext2"><?php echo _('I have included a License.txt file including the name of every author whose material is used in this package, along with the license under which their work is released.').' <strong>'._('Required').'</strong>'; ?></span>
                </td>
            </tr>
            <tr>
                <td colspan="4"><?php echo _('This package includes files released under:').' <strong>'._('Must check at least one').'</strong>'; ?></td>
            </tr>
            <tr>
                <td colspan="2" width="60"></td>
                <td><input type="checkbox" name="license_gpl" /></td>
                <td><?php echo _('GNU GPL'); ?></td>
            </tr>
            <tr>
                <td colspan="2" width="60"></td>
                <td><input type="checkbox" name="license_cc-by" /></td>
                <td><?php echo _('Creative Commons BY 3.0'); ?></td>
            </tr>
            <tr>
                <td colspan="2" width="60"></td>
                <td><input type="checkbox" name="license_cc-by-sa" /></td>
                <td><?php echo _('Creative Commons BY SA 3.0'); ?></td>
            </tr>
            <tr>
                <td colspan="2" width="60"></td>
                <td><input type="checkbox" name="license_pd" /></td>
                <td><?php echo _('CC0 (Public Domain)'); ?></td>
            </tr>
            <tr>
                <td colspan="2" width="60"></td>
                <td><input type="checkbox" name="license_bsd" /></td>
                <td><?php echo _('BSD License'); ?></td>
            </tr>
            <tr>
                <td colspan="2" width="60"></td>
                <td><input type="checkbox" name="license_other" /></td>
                <td><?php echo _('Other open license'); ?></td>
            </tr>
            <tr>
                <td colspan="2" width="60"></td>
                <td colspan="2">
                    <?php echo _('Files released under other licenses will be rejected unless it can be verified that the license is open.') ?><br /><br />
                </td>
            </tr>
            <tr>
                <td><input type="checkbox" name="l_agreement" /></td>
                <td colspan="3">
                    <?php echo _('I recognize that if my file does not meet the above rules, it may be removed at any time without prior notice; I also assume the entire responsibility for any copyright violation that may result from not following the above rules.').' <strong>'._('Required').'</strong><br /><br />'; ?>
                </td>
            </tr>
            <tr>
                <td><input type="checkbox" name="l_clean" /></td>
                <td colspan="3">
                    <?php echo _('My package does not include:'); ?><br />
                    1. <?php echo _('Profanity'); ?><br />
                    2. <?php echo _('Explicit images'); ?><br />
                    3. <?php echo _('Hateful messages and/or images'); ?><br />
                    4. <?php echo _('Any other content that may be unsuitable for children'); ?><br />
                    <strong><?php echo _('Required'); ?></strong>
                </td>
            </tr>
        </table>
        <script type="text/javascript">
            uploadFormFieldToggle();
        </script>
        <input type="submit" value="<?php echo _('Upload file'); ?>" />
    </form>
</div>
<?php
include("include/footer.php");
?>
