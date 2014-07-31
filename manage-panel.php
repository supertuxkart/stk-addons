<?php
/**
 * copyright 2011 Stephen Just <stephenjust@users.sourceforge.net>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with stkaddons.  If not, see <http://www.gnu.org/licenses/>.
 */
require_once(__DIR__ . DIRECTORY_SEPARATOR . "config.php");
AccessControl::setLevel(AccessControl::PERM_EDIT_ADDONS);

$_GET['action'] = (isset($_GET['action'])) ? $_GET['action'] : null;

switch ($_GET['view'])
{

    case 'overview':
        $tpl = new StkTemplate("manage-overview.tpl");
        $tpl_data = [
            "addons"   => [],
            "images"   => [],
            "archives" => []
        ];

        // Get all add-ons
        $addons = array_merge(
            Addon::getAll(Addon::KART),
            Addon::getAll(Addon::TRACK),
            Addon::getAll(Addon::ARENA)
        );
        /**@var Addon[] $addons */
        foreach ($addons as $addon)
        {
            // populate addons
            $unapproved = [];
            $addon_revisions = $addon->getAllRevisions();

            // Don't list if the latest revision is approved
            $last_revision = Util::array_last($addon_revisions);
            if (!Addon::isApproved($last_revision["status"]))
            {
                foreach ($addon_revisions as $rev_n => $revision)
                {
                    // see if approved
                    if (!Addon::isApproved($revision["status"]))
                    {
                        $unapproved[] = $revision["revision"];
                    }
                }
            }
            // add to view
            if ($unapproved)
            {
                $tpl_data["addons"][] = [
                    "href"       => $addon->getLink(),
                    "name"       => $addon->getName(),
                    "unapproved" => implode(', ', $unapproved)
                ];
            }

            // populate images
            $unapproved = [];
            foreach ($addon->getImages() as $image)
            {
                if ($image["approved"] == 0)
                {
                    $unapproved[] = '<img src="' . SITE_ROOT . 'image.php?size=' . SImage::SIZE_MEDIUM . '&pic=' . $image['file_path'] . '" />';
                }
            }
            // add to view
            if ($unapproved)
            {
                $managePanelData["images"][] = [
                    "href"       => $addon->getLink(),
                    "name"       => $addon->getName(),
                    "unapproved" => implode("<br>", $unapproved)
                ];
            }

            // populate archives
            $unapproved = 0;
            foreach ($addon->getSourceFiles() as $archive)
            {
                if ($archive["approved"] == 0)
                {
                    $unapproved++;
                }
            }
            // add to view
            if ($unapproved)
            {
                $managePanelData["archives"][] = [
                    "href"       => $addon->getLink(),
                    "name"       => $addon->getName(),
                    "unapproved" => $unapproved
                ];
            }
        }

        $tpl->assign("overview", $tpl_data);
        break;

    case 'general':
        if (!User::hasPermission(AccessControl::PERM_EDIT_SETTINGS))
        {
            exit("You do not have the necessary permission");
        }
        $tpl = new StkTemplate("manage-general.tpl");
        $tpl_data = [
            "xml_frequency"       => ConfigManager::getConfig("xml_frequency"),
            "allowed_addon_exts"  => ConfigManager::getConfig("allowed_addon_exts"),
            "allowed_source_exts" => ConfigManager::getConfig("allowed_source_exts"),
            "admin_email"         => ConfigManager::getConfig("admin_email"),
            "list_email"          => ConfigManager::getConfig("list_email"),
            "list_invisible"      => [
                "options"  => [
                    0 => _h("False"),
                    1 => _h("True"),
                ],
                "selected" => (ConfigManager::getConfig('list_invisible') == 1) ? 1 : 0
            ],
            "blog_feed"           => ConfigManager::getConfig("blog_feed"),
            "max_image_dimension" => ConfigManager::getConfig("max_image_dimension"),
            "apache_rewrites"     => ConfigManager::getConfig("apache_rewrites"),
        ];

        $tpl->assign("general", $tpl_data);
        break;

    case 'news':
        /*
         * TODO Allow selecting from a list of conditions rather than typing. Too typo-prone.
         * TODO Type semicolon-delimited expressions, e.g. stkversion > 0.7.0;addonid not installed;
         * TODO Allow editing in future, in case of goofs or changes.
         */
        if (!User::hasPermission(AccessControl::PERM_EDIT_SETTINGS))
        {
            exit("You do not have the necessary permission");
        }
        $tpl = new StkTemplate("manage-news.tpl");
        $tpl_data = ["items" => News::getAll()];

        $tpl->assign("news", $tpl_data);
        break;

    case 'clients':
        /*
         * TODO Allow changing association of user-agent strings with versions of STK
         * TODO Allow setting various components of the generated XML for each different user-agent
         * TODO Make XML generating script generate files for each configuration set
         * TODO Make download script provide a certain file based on the user-agent
         */
        if (!User::hasPermission(AccessControl::PERM_EDIT_SETTINGS))
        {
            exit("You do not have the necessary permission");
        }
        $tpl = new StkTemplate("manage-clients.tpl");
        $tpl_data = [
            "items" => DBConnection::get()->query(
                    'SELECT * FROM ' . DB_PREFIX . 'clients
                    ORDER BY `agent_string` ASC',
                    DBConnection::FETCH_ALL
                )
        ];

        $tpl->assign("clients", $tpl_data);
        break;

    case 'cache':
        // TODO List cache files
        if (!User::hasPermission(AccessControl::PERM_EDIT_SETTINGS))
        {
            exit("You do not have the necessary permission");
        }
        $tpl = new StkTemplate("manage-cache.tpl");
        $tpl_data = [];

        $tpl->assign("cache", $tpl_data);
        break;

    case 'files':
        $tpl = new StkTemplate("manage-files.tpl");
        $tpl_data = [];

        $files = File::getAllFiles();
        $items = [];
        foreach ($files as $file)
        {
            if (!isset($file["file_type"]))
            {
                continue;
            }

            $references = "";
            switch ($file["file_type"])
            {
                case false:
                    break;

                case "addon":
                    $references = [];

                    $types = ["track", "kart", "arena"];
                    foreach ($types as $type)
                    {
                        $type_plural = $type . 's_revs';
                        try
                        {
                            $rev_files = DBConnection::get()->query(
                                'SELECT * FROM `' . DB_PREFIX . $type_plural .
                                '` WHERE `fileid` = :id',
                                DBConnection::FETCH_ALL,
                                [':id' => $file["id"]]
                            );

                            // add to
                            foreach ($rev_files as $rev_file)
                            {
                                $references[] = $rev_file['addon_id'] . sprintf(' (%s)', $type);
                            }
                        }
                        catch(DBException $e)
                        {
                            throw new Exception(sprintf("Error on selecting all %s", $type_plural));
                        }
                    }

                    $references = implode(', ', $references);
                    break;

                default:
                    $references = "TODO";
                    break;
            }

            $file["references"] = $references;
            $items[] = $file;
        }

        $tpl_data["items"] = $items;
        $tpl->assign("upload", $tpl_data);
        break;

    case 'logs':
        $tpl = new StkTemplate("manage-logs.tpl");
        $tpl_data = ["items" => Log::getEvents()];

        $tpl->assign("logs", $tpl_data);
        break;

    case 'roles':
        if (!User::hasPermission(AccessControl::PERM_EDIT_PERMISSIONS))
        {
            exit("You do not have the necessary permission");
        }
        $tpl = new StkTemplate("manage-roles.tpl");
        $tpl_data = [
            "roles"       => AccessControl::getRoles(),
            "permissions" => AccessControl::getPermissionsChecked()
        ];

        $tpl->assign("roles", $tpl_data);
        break;

    default:
        // TODO maybe redirect
        exit(_h('Invalid page. You may have followed a broken link.'));
}

// output the view
echo $tpl;
