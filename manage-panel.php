<?php
/**
 * copyright 2011      Stephen Just <stephenjust@users.sourceforge.net>
 *           2014-2015 Daniel Butum <danibutum at gmail dot com>
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
AccessControl::setLevel(AccessControl::PERM_EDIT_ADDONS);

$_GET['action'] = (isset($_GET['action'])) ? $_GET['action'] : null;

switch ($_GET['view'])
{
    case 'overview':
        $tpl = StkTemplate::get("manage/page/overview.tpl");
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
                    "name"       => h($addon->getName()),
                    "unapproved" => implode(', ', $unapproved)
                ];
            }

            // populate images
            $unapproved = [];
            foreach ($addon->getImages() as $image)
            {
                if (!$image->isApproved())
                {
                    $unapproved[] =
                        '<img src="' . ROOT_LOCATION . 'image.php?size=' . StkImage::SIZE_MEDIUM . '&pic=' . $image->getPath() . '" />';
                }
            }
            // add to view
            if ($unapproved)
            {
                $tpl_data["images"][] = [
                    "href"       => $addon->getLink(),
                    "name"       => h($addon->getName()),
                    "unapproved" => implode("<br>", $unapproved)
                ];
            }

            // populate archives
            $unapproved = 0;
            foreach ($addon->getSourceFiles() as $archive)
            {
                if (!$archive->isApproved())
                {
                    $unapproved++;
                }
            }
            // add to view
            if ($unapproved)
            {
                $tpl_data["archives"][] = [
                    "href"       => $addon->getLink(),
                    "name"       => h($addon->getName()),
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
        $tpl = StkTemplate::get("manage/page/general.tpl");
        $tpl_data = [
            Config::XML_UPDATE_TIME           => (int)Config::get(Config::XML_UPDATE_TIME),
            Config::ALLOWED_ADDON_EXTENSIONS  => Config::get(Config::ALLOWED_ADDON_EXTENSIONS),
            Config::ALLOWED_SOURCE_EXTENSIONS => Config::get(Config::ALLOWED_SOURCE_EXTENSIONS),
            Config::EMAIL_ADMIN               => Config::get(Config::EMAIL_ADMIN),
            Config::EMAIL_LIST                => Config::get(Config::EMAIL_LIST),
            Config::SHOW_INVISIBLE_ADDONS     => [
                "options"  => [
                    0 => _h("False"),
                    1 => _h("True"),
                ],
                "selected" => (Config::get(Config::SHOW_INVISIBLE_ADDONS) == 1) ? 1 : 0
            ],
            Config::FEED_BLOG                 => Config::get(Config::FEED_BLOG),
            Config::IMAGE_MAX_DIMENSION       => (int)Config::get(Config::IMAGE_MAX_DIMENSION),
            Config::APACHE_REWRITES           => Config::get(Config::APACHE_REWRITES),
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
        $tpl = StkTemplate::get("manage/page/news.tpl");
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
        $tpl = StkTemplate::get("manage/page/clients.tpl");
        $tpl_data = [
            "items" => DBConnection::get()->query(
                'SELECT * FROM `{DB_VERSION}_clients`
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
        $tpl = StkTemplate::get("manage/page/cache.tpl");
        $tpl_data = [];

        $tpl->assign("cache", $tpl_data);
        break;

    case 'files':
        $tpl = StkTemplate::get("manage/page/files.tpl");
        $tpl_data = [];

        $files = File::getAllFiles();
        $items = [];
        foreach ($files as $file)
        {
            $references = "";
            switch ($file["type"])
            {
                case File::ADDON:
                    $references = [];

                    try
                    {
                        $rev_files = DBConnection::get()->query(
                            'SELECT * FROM `{DB_VERSION}_addon_revisions`
                            WHERE `file_id` = :id',
                            DBConnection::FETCH_ALL,
                            [':id' => $file["id"]]
                        );

                        // add to
                        foreach ($rev_files as $rev_file)
                        {
                            $references[] = $rev_file['addon_id'];
                        }
                    }
                    catch(DBException $e)
                    {
                        throw new Exception("Error on selecting all addon revisions");
                    }

                    $references = implode(', ', $references);
                    break;

                default:
                    $references = "TODO";
                    break;
            }

            $file["addon_type"] = Addon::typeToString($file["addon_type"]);
            $file["references"] = $references;
            $items[] = $file;
        }

        $tpl_data["items"] = $items;
        $tpl->assign("upload", $tpl_data);
        break;

    case 'logs':
        $tpl = StkTemplate::get("manage/page/logs.tpl");
        $tpl_data = ["items" => StkLog::getEvents()];

        $tpl->assign("logs", $tpl_data);
        break;

    case 'roles':
        if (!User::hasPermission(AccessControl::PERM_EDIT_PERMISSIONS))
        {
            exit("You do not have the necessary permission");
        }
        $tpl = StkTemplate::get("manage/page/roles.tpl");
        $tpl_data = [
            "roles"       => AccessControl::getRoleNames(),
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
