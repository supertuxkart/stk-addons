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
require_once(ROOT.'include/Upload.class.php');
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
    var filetypeselect = document.getElementById('upload-type');
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
    if (filetypeselect != undefined)
    {
        if (filetypeselect.value == 'image') {
            check1.disabled = true;
            check2.disabled = true;
            checklabel1.style.color = '#999999';
            checklabel2.style.color = '#999999';
        } else {
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
    }
}
</script>
</head><body>
<?php
include(ROOT.'include/menu.php');

if($_GET['action'] == "submit")
{
    echo '<div id="content">';
    // Check to make sure all form license boxes are good
    $agreement_form = 1;
    while ($agreement_form == 1)
    {
        if (!isset($_POST['license_gpl'])
                && !isset($_POST['license_cc-by'])
                && !isset($_POST['license_cc-by-sa'])
                && !isset($_POST['license_pd'])
                && !isset($_POST['license_bsd'])
                && !isset($_POST['license_other']))
            $agreement_form = 0;
        if (!isset($_POST['l_agreement']) || !isset($_POST['l_clean']))
            $agreement_form = 0;
        if (!isset($_POST['l_author']))
            $agreement_form = 0;
        if (isset($_POST['upload-type']) && $_POST['upload-type'] == 'image')
        {
            if ($_POST['l_author'] != 1 && $_POST['l_author'] != 2)
                $agreement_form = 0;
        }
        else
        {
            if ($_POST['l_author'] == 1 && !isset($_POST['l_licensefile1']))
                $agreement_form = 0;
            if ($_POST['l_author'] == 2 && !isset($_POST['l_licensefile2']))
                $agreement_form = 0;
        }
        break;
    }
    if ($agreement_form == 0)
    {
        echo '<span class="error">'.htmlspecialchars(_('Your response to the agreement was unacceptable. You may not upload this content to the STK Addons website.')).'</span><br />';
    }
    else
    {
        // Generate a note to moderators for license verification
        $moderator_message = NULL;
        if (isset($_POST['license_other']))
            $moderator_message .= 'Auto-message: Moderator: Please verify that license is "free"'."\n";
        if ($_POST['l_author'] == 1)
            $moderator_message .= 'Auto-message: Content is solely created by uploader.'."\n";
        else
            $moderator_message .= 'Auto-message: Content contains third-party open content.'."\n";
        try {
	    if (!isset($_POST['upload-type'])) $_POST['upload-type'] = NULL;
	    switch ($_POST['upload-type']) {
		case 'image':
		    $expected_type = 'image';
		    break;
		case 'source':
		    $expected_type = 'source';
		    break;
		default:
		    $expected_type = 'addon';
		    break;
	    }
	    $upload = new Upload($_FILES['file_addon'],$expected_type);
        }
        catch (UploadException $e) {
            echo '<span class="error">'.$e->getMessage().'</span><br />';
        }
	if (isset($upload)) $upload->removeTempFiles();
    }
    echo '</div>';
    include('include/footer.php');
    exit;
}
?>
    <div id="content">
        <?php
        if (($_GET['type'] == 'karts' || $_GET['type'] == 'tracks' || $_GET['type'] == 'arenas')
                && strlen($_GET['name']) != 0)
        {
            // Working with an already existing addon
            echo '<form id="formKart" enctype="multipart/form-data" action="upload.php?type='.$_GET['type'].'&amp;name='.$_GET['name'].'&amp;action=submit" method="POST">';
            if ($_GET['action'] != 'file')
            {
                echo htmlspecialchars(_('Please upload a new revision of your kart or track.')).'<br />';
            }
            else
            {
                echo htmlspecialchars(_('What type of file are you uploading?')).'<br />';
                echo '<select name="upload-type" id="upload-type" onChange="uploadFormFieldToggle();">
                    <option value="source">'.htmlspecialchars(_('Source Archive')).'</option>
                    <option value="image">'.htmlspecialchars(_('Image File')).' (.png, .jpg, .jpeg)</option>
                    </select><br />';
            }
        }
        else
        {
            echo '<form id="formKart" enctype="multipart/form-data" action="upload.php?action=submit" method="POST">';
            echo htmlspecialchars(_('Please upload a kart or track.')).'<br />';
            echo htmlspecialchars(_('Do not use this form if you are updating an existing add-on.')).'<br />';
        }
        ?>
        <label><?php echo htmlspecialchars(_("File:")); ?><br /><input type="file" name="file_addon" /><br /></label>
        <?php echo htmlspecialchars(_('Supported archive types are:')); ?> .zip, .tar, .tgz, .tar.gz, .tbz, .tar.bz2<br /><br />
        <strong><?php echo htmlspecialchars(_('Agreement:')); ?></strong><br />
        <table width="800" id="upload_agreement">
            <tr>
                <td width="1"><input type="radio" name="l_author" id="l_author1" value="1" onChange="uploadFormFieldToggle();" checked /></td>
                <td colspan="3">
                    <?php echo htmlspecialchars(_('I am the sole author of every file (model, texture, sound effect, etc.) in this package')); ?>
                </td>
            </tr>
            <tr>
                <td colspan="2" width="60"></td>
                <td><input type="checkbox" name="l_licensefile1" id="l_licensefile1"></td>
                <td>
                    <span id="l_licensetext1"><?php echo htmlspecialchars(_('I have included a License.txt file describing the license under which my work is released, and my name (or nickname) if I want credit.')).' <strong>'.htmlspecialchars(_('Required')).'</strong>'; ?></span>
                </td>
            </tr>
            <tr>
                <td width="1"><input type="radio" name="l_author" id="l_author2" value="2" onChange="uploadFormFieldToggle();" /></td>
                <td colspan="3">
                    <?php echo htmlspecialchars(_('I have included open content made by people other than me')); ?>
                </td>
            </tr>
            <tr>
                <td colspan="2" width="60"></td>
                <td><input type="checkbox" name="l_licensefile2" id="l_licensefile2"></td>
                <td>
                    <span id="l_licensetext2"><?php echo htmlspecialchars(_('I have included a License.txt file including the name of every author whose material is used in this package, along with the license under which their work is released.')).' <strong>'.htmlspecialchars(_('Required')).'</strong>'; ?></span>
                </td>
            </tr>
            <tr>
                <td colspan="4"><?php echo htmlspecialchars(_('This package includes files released under:')).' <strong>'.htmlspecialchars(_('Must check at least one')).'</strong>'; ?></td>
            </tr>
            <tr>
                <td colspan="2" width="60"></td>
                <td><input type="checkbox" name="license_gpl" /></td>
                <td><?php echo htmlspecialchars(_('GNU GPL')); ?></td>
            </tr>
            <tr>
                <td colspan="2" width="60"></td>
                <td><input type="checkbox" name="license_cc-by" /></td>
                <td><?php echo htmlspecialchars(_('Creative Commons BY 3.0')); ?></td>
            </tr>
            <tr>
                <td colspan="2" width="60"></td>
                <td><input type="checkbox" name="license_cc-by-sa" /></td>
                <td><?php echo htmlspecialchars(_('Creative Commons BY SA 3.0')); ?></td>
            </tr>
            <tr>
                <td colspan="2" width="60"></td>
                <td><input type="checkbox" name="license_pd" /></td>
                <td><?php echo htmlspecialchars(_('CC0 (Public Domain)')); ?></td>
            </tr>
            <tr>
                <td colspan="2" width="60"></td>
                <td><input type="checkbox" name="license_bsd" /></td>
                <td><?php echo htmlspecialchars(_('BSD License')); ?></td>
            </tr>
            <tr>
                <td colspan="2" width="60"></td>
                <td><input type="checkbox" name="license_other" /></td>
                <td><?php echo htmlspecialchars(_('Other open license')); ?></td>
            </tr>
            <tr>
                <td colspan="2" width="60"></td>
                <td colspan="2">
                    <?php echo htmlspecialchars(_('Files released under other licenses will be rejected unless it can be verified that the license is open.')) ?><br /><br />
                </td>
            </tr>
            <tr>
                <td><input type="checkbox" name="l_agreement" /></td>
                <td colspan="3">
                    <?php echo htmlspecialchars(_('I recognize that if my file does not meet the above rules, it may be removed at any time without prior notice; I also assume the entire responsibility for any copyright violation that may result from not following the above rules.')).' <strong>'.htmlspecialchars(_('Required')).'</strong><br /><br />'; ?>
                </td>
            </tr>
            <tr>
                <td><input type="checkbox" name="l_clean" /></td>
                <td colspan="3">
                    <?php echo htmlspecialchars(_('My package does not include:')); ?><br />
                    1. <?php echo htmlspecialchars(_('Profanity')); ?><br />
                    2. <?php echo htmlspecialchars(_('Explicit images')); ?><br />
                    3. <?php echo htmlspecialchars(_('Hateful messages and/or images')); ?><br />
                    4. <?php echo htmlspecialchars(_('Any other content that may be unsuitable for children')); ?><br />
                    <strong><?php echo htmlspecialchars(_('Required')); ?></strong>
                </td>
            </tr>
        </table>
        <script type="text/javascript">
            uploadFormFieldToggle();
        </script>
        <input type="submit" value="<?php echo htmlspecialchars(_('Upload file')); ?>" />
    </form>
</div>
<?php
include("include/footer.php");
?>
