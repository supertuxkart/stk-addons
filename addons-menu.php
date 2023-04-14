<?php
/**
 * Copyright 2009      Lucas Baudin <xapantu@gmail.com>
 *           2011-2014 Stephen Just <stephenjust@gmail.com>
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

try
{
    $type = isset($_GET['type']) ? Addon::stringToType($_GET['type']) : null;
    $sort = isset($_GET['sort']) ? $_GET['sort'] : Addon::SORT_FEATURED;
    $order = isset($_GET['order']) ? $_GET['order'] : null;
    $addon_name = empty($_GET['name']) ? null : $_GET['name'];

    $current_page = PaginationTemplate::getPageNumber();
    $limit = PaginationTemplate::getLimitNumber();

    $addons = Addon::getAll($type, $limit, $current_page, $sort, $order);
    $template_addons = Addon::filterMenuTemplate($addons, $addon_name);

    $pagination_addons = Addon::filterMenuTemplate(Addon::getAll($type));
    $pagination = PaginationTemplate::get()
        ->setItemsPerPage($limit)
        ->setTotalItems(count($pagination_addons))
        ->setCurrentPage($current_page);

    $tpl = StkTemplate::get("addons/menu.tpl")
        ->assign("addons", $template_addons)
        ->assign("pagination", $pagination->toString());

    echo $tpl;
}
catch (AddonException $e)
{
    echo $e->getMessage();
}
