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

if (!defined('ROOT'))
{
    define('ROOT', './');
}
require_once(ROOT . 'config.php');
AccessControl::setLevel('manageaddons');

$_GET['action'] = (isset($_GET['action'])) ? $_GET['action'] : null;

switch ($_GET['id'])
{

    case 'overview':
        $managePanelTpl = new StkTemplate("panels/manage-overview.tpl");
        $managePanelData = array(
            "addons"   => array(),
            "images"   => array(),
            "archives" => array()
        );

        // Get all add-ons
        $addons_ids = array_merge(
            Addon::getAddonList('karts'),
            Addon::getAddonList('tracks'),
            Addon::getAddonList('arenas')
        );

        foreach ($addons_ids as $addon_id)
        {
            $addon = new Addon($addon_id);

            // populate addons
            $unapproved = array();
            $addon_revisions = $addon->getAllRevisions();
            // Don't list if the latest revision is approved
            $last_revision = \utilphp\util::array_last($addon_revisions);
            if (!($last_revision["status"] & F_APPROVED))
            {
                foreach ($addon_revisions as $rev_n => $revision)
                {
                    // see if approved
                    if (!($revision["status"] & F_APPROVED))
                    {
                        $unapproved[] = $revision["revision"];
                    }
                }
            }
            // add to view
            if (!empty($unapproved))
            {
                $managePanelData["addons"][] = array(
                    "href"       => $addon->getLink(),
                    "name"       => Addon::getName($addon_id),
                    "unapproved" => implode(', ', $unapproved)
                );
            }

            // populate images
            $unapproved = array();
            foreach ($addon->getImages() as $image)
            {
                if ($image["approved"] == 0)
                {
                    $unapproved[] = '<img src="' . ROOT . 'image.php?type=medium&pic=' . $image['file_path'] . '" />';
                }
            }
            // add to view
            if (!empty($unapproved))
            {
                $managePanelData["images"][] = array(
                    "href"       => $addon->getLink(),
                    "name"       => Addon::getName($addon_id),
                    "unapproved" => implode("<br>", $unapproved)
                );
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
                $managePanelData["archives"][] = array(
                    "href"       => $addon->getLink(),
                    "name"       => $addon->getName($addon_id),
                    "unapproved" => $unapproved
                );
            }
        }

        $managePanelTpl->assign("overview", $managePanelData);
        break;
    case 'general':
        $managePanelTpl = new StkTemplate("panels/manage-general.tpl");
        $managePanelData = array(
            "xml_frequency"       => ConfigManager::getConfig("xml_frequency"),
            "allowed_addon_exts"  => ConfigManager::getConfig("allowed_addon_exts"),
            "allowed_source_exts" => ConfigManager::getConfig("allowed_source_exts"),
            "admin_email"         => ConfigManager::getConfig("admin_email"),
            "list_email"          => ConfigManager::getConfig("list_email"),
            "list_invisible"      => array(
                "options"  => array(
                    0 => _h("False"),
                    1 => _h("True"),
                ),
                "selected" => (ConfigManager::getConfig('list_invisible') == 1) ? 1 : 0
            ),
            "blog_feed"           => ConfigManager::getConfig("blog_feed"),
            "max_image_dimension" => ConfigManager::getConfig("max_image_dimension"),
            "apache_rewrites"     => ConfigManager::getConfig("apache_rewrites"),
        );

        $managePanelTpl->assign("general", $managePanelData);
        break;
    case 'news':
        /*
         * TODO Allow selecting from a list of conditions rather than typing. Too typo-prone.
         * TODO Type semicolon-delimited expressions, e.g. stkversion > 0.7.0;addonid not installed;
         * TODO Allow editing in future, in case of goofs or changes.
         */

        $managePanelTpl = new StkTemplate("panels/manage-news.tpl");
        $managePanelData = array(
            "items" => News::getAll()
        );

        $managePanelTpl->assign("news", $managePanelData);
        break;
    case 'clients':
        /*
         * TODO Allow changing association of user-agent strings with versions of STK
         * TODO Allow setting various components of the generated XML for each different user-agent
         * TODO Make XML generating script generate files for each configuration set
         * TODO Make download script provide a certain file based on the user-agent
         */
        $managePanelTpl = new StkTemplate("panels/manage-clients.tpl");
        $managePanelData = array(
            "items" => DBConnection::get()->query(
                    'SELECT * FROM ' . DB_PREFIX . 'clients
                    ORDER BY `agent_string` ASC',
                    DBConnection::FETCH_ALL
                )
        );

        $managePanelTpl->assign("clients", $managePanelData);
        break;
    case 'cache':
        // TODO List cache files

        $managePanelTpl = new StkTemplate("panels/manage-cache.tpl");
        $managePanelData = array();

        $managePanelTpl->assign("cache", $managePanelData);
        break;
    case 'files':
        // TODO test files overview properly
        $managePanelTpl = new StkTemplate("panels/manage-files.tpl");
        $managePanelData = array();

        $files = File::getAllFiles();
        $items = array();
        foreach ($files as $file)
        {
            switch ($file["file_type"])
            {
                case false:
                    $references = '<span class="error">No record found.</span>';
                    break;
                case "addon":
                    $references = array();

                    $types = array("track", "kart", "arena");
                    foreach ($types as $type)
                    {
                        $type_plural = $type . 's_revs';
                        try
                        {
                            $files = DBConnection::get()->query(
                                'SELECT * FROM `' . DB_PREFIX . $type_plural .
                                'WHERE `fileid` = :id',
                                DBConnection::FETCH_ALL,
                                array(
                                    ':id' => $file["id"]
                                )
                            );

                            // add to
                            foreach ($files as $file)
                            {
                                $references[] = $file['addon_id'] . sprintf(' (%s)', $type);
                            }
                        }
                        catch(DBException $e)
                        {
                            throw new Exception(sprintf("Error on selecting all %s", $type_plural));
                        }
                    }

                    if (empty($references))
                    {
                        $references[] = '<span class="error">None</span>';
                    }

                    $references = implode(', ', $references);
                    break;
                default:
                    $references = "TODO";
                    break;
            }

            if ($file["exists"] == false)
            {
                $references .= ' <span class="error">File not found.</span>';
            }

            $file["references"] = $references;
            $items[] = $file;
        }

        $managePanelData["items"] = $items;
        $managePanelTpl->assign("files", $managePanelData);
        break;
    case 'logs':
        $managePanelTpl = new StkTemplate("panels/manage-logs.tpl");
        $managePanelData = array(
            "items" => Log::getEvents()
        );

        $managePanelTpl->assign("logs", $managePanelData);
        break;
    default:
        $managePanelData["errors"] = _h('Invalid page. You may have followed a broken link.');
        $managePanelTpl->assign("manage", $managePanelData);
        echo $managePanelTpl;
        exit;
}

// output the view
echo $managePanelTpl;