<?php
/**
 * copyright 2009 Lucas Baudin <xapantu@gmail.com>
 *           2014 Daniel Butum <danibutum at gmail dot com>
 * This file is part of stk-addons.
 *
 * stk-addons is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * stk-addons is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with stk-addons. If not, see <http://www.gnu.org/licenses/>.
 */
require_once(__DIR__ . DIRECTORY_SEPARATOR . "config.php");
AccessControl::setLevel(AccessControl::PERM_ADD_ADDON);

// used to set the post field with the same name
$upload_type = !empty($_GET['upload-type']) ? Upload::stringToType($_GET['upload-type']) : null;
$addon_type_string = !empty($_GET['type']) ? $_GET['type'] : null; // addon type
$addon_type = Addon::stringToType($addon_type_string);
$addon_name = !empty($_GET['name']) ? $_GET['name'] : null; // addon name

$tpl = StkTemplate::get('upload.tpl')
    ->addBootstrapFileInputLibrary()
    ->addScriptInclude("upload.js");

$upload_form = [
    // add new things for this addon
    "is_update"   => Addon::isAllowedType($addon_type) && $addon_name,
    "addon"       => [
        "type" => $addon_type_string,
        "name" => $addon_name,
    ],
    "upload_type" => [
        "options"  => [
            Upload::SOURCE   => _h('Source Archive'),
            Upload::IMAGE    => _h('Image File') . ' (.png, .jpg, .jpeg)',
            Upload::REVISION => _h('Addon Revision')
        ],
        "selected" => $upload_type,
        "default"  => Upload::ADDON // default value when the upload type is not defined
    ],
    "display"     => true,
];

if (isset($_GET["submit"])) // form submitted
{
    if (empty($_POST))
    {
        $upload_form["display"] = false;
        $tpl->assign("upload", $upload_form);
        $tpl->assign(
            "errors",
            _h("You did not submit anything/Maximum POST (upload) size exceeded (your file is too large!)")
        );

        exit($tpl);
    }

    $l_author = !empty($_POST["l_author"]) ? (int)$_POST["l_author"] : null;
    $expected_type = !empty($_POST['upload-type']) ? (int)$_POST['upload-type'] : null;
    function getAgreement($l_author, $expected_type)
    {
        // Check to make sure all form boxes are good
        $required_fields_license = [
            'license_gpl',
            'license_cc-by',
            'license_cc-by-sa',
            'license_pd',
            'license_bsd',
            'license_other',
        ];
        // at least one of the checkboxes must be checked
        if (count($required_fields_license) === count(Validate::ensureIsSet($_POST, $required_fields_license)))
        {
            return false;
        }
        if (!isset($_POST['l_agreement']) || !$l_author)
        {
            return false;
        }

        if ($expected_type === Upload::IMAGE) // do not require License.txt file
        {
            if ($l_author !== 1 && $l_author !== 2)
            {
                return false;
            }
        }
        else
        {
            // the 2 big radios with checkboxes
            if ($l_author === 1 && !isset($_POST['l_licensefile1']))
            {
                return false;
            }
            if ($l_author === 2 && !isset($_POST['l_licensefile2']))
            {
                return false;
            }
        }

        return true;
    }

    if (getAgreement($l_author, $expected_type) === true) // upload process
    {
        $upload_form["display"] = false;

        // Generate a note to moderators for license verification
        $moderator_message = '';
        if (isset($_POST['license_other']))
        {
            $moderator_message .= 'Auto-message: Moderator: Please verify that license is "free"' . "\n";
        }

        if ($l_author == 1)
        {
            $moderator_message .= 'Auto-message: Content is solely created by uploader.' . "\n";
        }
        else // 2
        {
            $moderator_message .= 'Auto-message: Content contains third-party open content.' . "\n";
        }

        try
        {
            $upload = new Upload($_FILES['file_addon'], $addon_name, $addon_type, $expected_type, $moderator_message);
            $tpl->assign("warnings", $upload->getWarningMessage());
            $tpl->assign("success", $upload->getSuccessMessage());

        }
        catch (UploadException $e)
        {
            $tpl->assign("errors", $e->getMessage());
        }
        catch (Exception $e)
        {
            if (DEBUG_MODE)
            {
                // Let the fancy UI catch it.
                throw $e;
            }
            else
            {
                $tpl->assign(
                    "errors",
                    'Unexpected exception: ' . $e->getMessage() .
                    '<strong>If this is ever visible, that\'s a bug!</strong>'
                );
            }
        }
    }
    else
    {
        $upload_form["display"] = false;
        $tpl->assign(
            "errors",
            _h(
                'Your response to the agreement was unacceptable. You may not upload this content to the STK Addons website.'
            )
        );
    }
}

$tpl->assign("upload", $upload_form);
echo $tpl;
