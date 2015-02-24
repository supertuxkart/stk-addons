<?php
/**
 * copyright 2013      Stephen Just <stephenjust@users.sourceforge.net>
 *           2014-2015 Daniel Butum <danibutum at gmail dot com>
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
require_once(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config.php");

if (empty($_POST["action"]))
{
    exit_json_error("action param is not defined or is empty");
}
if (!User::isLoggedIn())
{
    exit_json_error("You are not logged in");
}


$addon = null; // get addon
if (!in_array($_POST["action"], ["update-approval-file"])) // these actions do not need the addon
{
    if (empty($_POST["addon-id"]))
    {
        exit_json_error("Addon id is not set or empty");
    }

    try
    {
        $addon = Addon::get($_POST["addon-id"]);
    }
    catch(AddonException $e)
    {
        exit_json_error($e->getMessage());
    }
}

// TODO set maximum/minimum length
switch ($_POST["action"])
{
    case "edit-props":
        if (Validate::ensureIsSet($_POST, ["description", "designer"]))
        {
            exit_json_error("One or more proprieties are not set");
        }

        try
        {
            $addon->setDescription($_POST['description'])->setDesigner($_POST['designer']);
            writeXML();
        }
        catch(Exception $e)
        {
            exit_json_error($e->getMessage());
        }

        exit_json_success(_h("Proprieties saved"));
        break;

    case 'edit-include-versions':
        if (Validate::ensureIsSet($_POST, ["include-start", "include-end"]))
        {
            exit_json_error("One or more proprieties are not set");
        }

        try
        {
            $addon->setIncludeVersions($_POST["include-start"], $_POST["include-end"]);
            writeXML();
        }
        catch(Exception $e)
        {
            exit_json_error($e->getMessage());
        }

        exit_json_success(_h('Marked game versions in which this add-on is included.'));
        break;

    case 'update-approval-file': // unapprove, approve file
        if (Validate::ensureIsSet($_POST, ["file-id", "approve"]))
        {
            exit_json_error("One or more proprieties are not set");
        }
        if (!User::hasPermission(AccessControl::PERM_EDIT_ADDONS))
        {
            exit_json_error(_h('Insufficient permissions to approve a file'));
        }

        try
        {
            File::approve((int)$_POST['file-id'], $_POST['approve'] === 'true');
            writeXML();
        }
        catch(Exception $e)
        {
            exit_json_error($e->getMessage());
        }

        exit_json_success(_h('File approval status changed.'));
        break;

    case 'set-image':
        if (Validate::ensureIsSet($_POST, ["file-id"]))
        {
            exit_json_error("One or more proprieties are not set");
        }

        try
        {
            $addon->setImage((int)$_POST['file-id']);
        }
        catch(AddonException $e)
        {
            exit_json_error($e->getMessage());
        }

        exit_json_success(_h('Set image.'));
        break;

    case 'set-icon':
        if (Validate::ensureIsSet($_POST, ["file-id"]))
        {
            exit_json_error("One or more proprieties are not set");
        }

        try
        {
            $addon->setIcon((int)$_POST['file-id']);
        }
        catch(AddonException $e)
        {
            exit_json_error($e->getMessage());
        }

        exit_json_success(_h('Set icon.'));
        break;

    case 'delete-file':
        if (Validate::ensureIsSet($_POST, ["file-id"]))
        {
            exit_json_error("One or more proprieties are not set");
        }

        try
        {
            $addon->deleteFile((int)$_POST['file-id']);
            writeXML();
        }
        catch(Exception $e)
        {
            exit_json_error($e->getMessage());
        }

        exit_json_success(_h('Deleted file.'));
        break;

    case 'delete-revision':
        if (Validate::ensureIsSet($_POST, ["revision-id"]))
        {
            exit_json_error("One or more proprieties are not set");
        }

        try
        {
            $addon->deleteRevision((int)$_POST['revision-id']);
            writeXML();
        }
        catch(Exception $e)
        {
            exit_json_error($e->getMessage());
        }

        exit_json_success(_h('Deleted add-on revision.'));
        break;

    case 'delete-addon':
        try
        {
            $addon->delete();
            writeXML();
        }
        catch(Exception $e)
        {
            exit_json_error($e->getMessage());
        }

        exit_json_success(_h('Deleted addon.'));
        break;

    case 'set-flags':
        if (Validate::ensureIsSet($_POST, ["fields"]))
        {
            exit_json_error("One or more proprieties are not set");
        }

        try
        {
            $addon->setStatus($_POST, $_POST['fields']);
            writeXML();
        }
        catch(Exception $e)
        {
            exit_json_error($e->getMessage());
        }

        exit_json_success(_h('Saved status.'));
        break;

    case 'set-notes':
        if (Validate::ensureIsSet($_POST, ["fields"]))
        {
            exit_json_error("One or more proprieties are not set");
        }
        if (!User::hasPermission(AccessControl::PERM_EDIT_ADDONS))
        {
            exit_json_error(_h("You do not have the permission to change this addon's notes"));
        }

        // prepare notes array
        $fields = Util::commaStringToArray($_POST["fields"]); // array of numbers
        $notes = [];
        foreach ($fields as $field)
        {
            $key = "note-" . $field;
            if (!isset($_POST[$key])) // do not ignore not set fields
            {
                exit_json_error(sprintf("Note for revision %s is missing", $field));
            }

            $notes[(int)$field] = $_POST[$key];
        }

        try
        {
            $addon->setNotes($notes);
        }
        catch(Exception $e)
        {
            exit_json_error($e->getMessage());
        }

        exit_json_success(_h('Saved notes.'));
        break;

    default:
        exit_json_error(sprintf("action = %s is not recognized", h($_POST["action"])));
        break;
}
