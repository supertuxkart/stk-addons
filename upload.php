<?php
/**
 * copyright 2009 Lucas Baudin <xapantu@gmail.com>
 *           2014 Daniel Butum <danibutum at gmail dot com>
 * This file is part of stkaddons
 *
 * stkaddons is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * stkaddons is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with stkaddons. If not, see <http://www.gnu.org/licenses/>.
 */

require_once(__DIR__ . DIRECTORY_SEPARATOR . "config.php");
AccessControl::setLevel(AccessControl::PERM_ADD_ADDON);

// Define possibly undefined variables
$_GET['action'] = (isset($_GET['action'])) ? $_GET['action'] : null;
$_GET['type'] = (isset($_GET['type'])) ? $_GET['type'] : null;
$_GET['name'] = (isset($_GET['name'])) ? $_GET['name'] : null;

$tpl = new StkTemplate('upload.tpl');

$inline_script = <<<JS
function uploadFormFieldToggle()
{
    var radio1 = document.getElementById('l_author1');
    var check1 = document.getElementById('l_licensefile1');
    var check2 = document.getElementById('l_licensefile2');
    var checklabel1 = document.getElementById('l_licensetext1');
    var checklabel2 = document.getElementById('l_licensetext2');
    var filetypeselect = document.getElementById('upload-type');

    if(radio1.checked)
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
    if(filetypeselect != undefined)
    {
        if(filetypeselect.value == 'image')
        {
            check1.disabled = true;
            check2.disabled = true;
            checklabel1.style.color = '#999999';
            checklabel2.style.color = '#999999';
        }
        else
        {
            if(radio1.checked)
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
uploadFormFieldToggle();
JS;

// assign inline javascript
$tpl->addScriptInline(
    $inline_script,
    StkTemplate::ORDER_AFTER
);

$upload_form = array(
    "display" => true,
    "form"    => array(
        // new addon revision
        "update" => false, // update addon or insert addon
    )
);

$errors = '';
if ($_GET['action'] === "submit") // form submitted
{
    if (empty($_POST))
    {
        $upload_form["display"] = false;
        $tpl->assign("upload", $upload_form);
        $tpl->assign("errors", _h("Maximum POST size exceeded. Your file is too large!"));

        echo $tpl;
        exit;
    }

    // Check to make sure all form license boxes are good
    $agreement_form = 1;
    while ($agreement_form == 1)
    {
        if (!isset($_POST['license_gpl'])
            && !isset($_POST['license_cc-by'])
            && !isset($_POST['license_cc-by-sa'])
            && !isset($_POST['license_pd'])
            && !isset($_POST['license_bsd'])
            && !isset($_POST['license_other'])
        )
        {
            $agreement_form = 0;
        }
        if (!isset($_POST['l_agreement']) || !isset($_POST['l_clean']))
        {
            $agreement_form = 0;
        }
        if (!isset($_POST['l_author']))
        {
            $agreement_form = 0;
        }
        if (isset($_POST['upload-type']) && $_POST['upload-type'] == 'image')
        {
            if ($_POST['l_author'] != 1 && $_POST['l_author'] != 2)
            {
                $agreement_form = 0;
            }
        }
        else
        {
            if ($_POST['l_author'] == 1 && !isset($_POST['l_licensefile1']))
            {
                $agreement_form = 0;
            }
            if ($_POST['l_author'] == 2 && !isset($_POST['l_licensefile2']))
            {
                $agreement_form = 0;
            }
        }
        break;
    }
    if ($agreement_form === 0)
    {
        $upload_form["display"] = false;
        $tpl->assign(
            "errors",
            _h(
                'Your response to the agreement was unacceptable. You may not upload this content to the STK Addons website.'
            )
        );
    }
    else // upload process
    {
        // Generate a note to moderators for license verification
        $moderator_message = '';
        if (isset($_POST['license_other']))
        {
            $moderator_message .= 'Auto-message: Moderator: Please verify that license is "free"' . "\n";
        }

        if ($_POST['l_author'] == 1)
        {
            $moderator_message .= 'Auto-message: Content is solely created by uploader.' . "\n";
        }
        else
        {
            $moderator_message .= 'Auto-message: Content contains third-party open content.' . "\n";
        }

        try
        {
            // TODO fix upload type field
            if (!isset($_POST['upload-type']))
            {
                $_POST['upload-type'] = null;
            }
            switch ($_POST['upload-type'])
            {
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

            var_dump($_FILES['file_addon'], $expected_type);
            $upload = new Upload($_FILES['file_addon'], $expected_type);
            //$upload->removeTempFiles();
        }
        catch(UploadException $e)
        {
            $upload_form["display"] = false;
            $tpl->assign("errors", $e->getMessage());
        }
        catch(Exception $e)
        {
            $upload_form["display"] = false;
            $tpl->assign(
                "errors",
                'Unexpected exception: ' . $e->getMessage() . '<strong>If this is ever visible, that\'s a bug!</strong>'
            );
        }
    }
}

// Working with an already existing addon
if (($_GET['type'] === 'karts' || $_GET['type'] === 'tracks' || $_GET['type'] === 'arenas')
    && !empty($_GET['name'])
)
{
    $upload_form["form"]["update"] = true;
}

// standard page
$tpl->assign("upload", $upload_form);

echo $tpl;
