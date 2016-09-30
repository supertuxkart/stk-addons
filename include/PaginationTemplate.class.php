<?php
/**
 * Copyright 2014 Daniel Butum <danibutum at gmail dot com>
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

/**
 * Class for pagination view
 */
class PaginationTemplate extends Template
{
    /**
     * Where we get the page number from $_GET
     * @const string
     */
    const PAGE_ARGUMENT = "p";

    /**
     * Where we get the limit number from $_GET
     * @const string
     */
    const LIMIT_ARGUMENT = "l";

    /**
     * The maximum allowed limit on items
     * @const int
     */
    const MAX_LIMIT_ITEMS = 50;

    /**
     * The minimum allowed limit on items
     * @const int
     */
    const MIN_LIMIT_ITEMS = 10;

    /**
     * The total entries present
     * @var int
     */
    protected $total_items = 0;

    /**
     * The current page
     * @var int
     */
    protected $current_page = 1;

    /**
     * Items on page ratio, set to MIN_LIMIT_ITEMS in the constructor
     * @var int
     */
    protected $items_per_page;

    /**
     * The number of button visible, expect the first and last button
     * @var int
     */
    protected $nr_buttons = 4;

    /**
     * The base url to build each button href/url
     * @var string
     */
    protected $page_url;

    /**
     * @param null|string $template_dir
     */
    public function __construct($template_dir = null)
    {
        parent::__construct("pagination/template.tpl", $template_dir);
        $this->items_per_page = static::MIN_LIMIT_ITEMS;
    }

    /**
     * @param null $template_dir
     *
     * @return static
     */
    public static function get($template_dir = null)
    {
        return new static($template_dir);
    }

    /**
     * Get the current page number from the get params
     *
     * @param int $default_page the default page
     *
     * @return int
     */
    public static function getPageNumber($default_page = 1)
    {
        if (!empty($_GET[static::PAGE_ARGUMENT]))
        {
            $page = (int)$_GET[static::PAGE_ARGUMENT];

            // do not accept negative page numbers
            if ($page <= 0) return $default_page;

            return $page;
        }

        return $default_page;
    }

    /**
     * Get the items per page number from the get params
     *
     * @param int|null $default_limit the default number of items
     *
     * @return int
     */
    public static function getLimitNumber($default_limit = null)
    {
        // set default if not already set
        if (is_null($default_limit)) $default_limit = static::MIN_LIMIT_ITEMS;

        if (!empty($_GET[static::LIMIT_ARGUMENT]))
        {
            $limit = (int)$_GET[static::LIMIT_ARGUMENT];

            // clamp if too large or too small
            if ($limit < static::MIN_LIMIT_ITEMS || $limit > static::MAX_LIMIT_ITEMS)
            {
                return static::MIN_LIMIT_ITEMS;
            }

            return $limit;
        }

        return $default_limit;
    }

    /**
     * Test the template by outputting consecutive
     *
     * @param int $total_items
     */
    public static function testTemplate($total_items = 30)
    {
        for ($per_page = 1; $per_page < $total_items / 2; $per_page++)
        {
            for ($i = 1; $i <= ceil($total_items / $per_page); $i++)
            {
                echo PaginationTemplate::get()->setCurrentPage($i)->setTotalItems($total_items)->setItemsPerPage(
                    $per_page
                );
                echo "<br>";
            }
        }
    }

    /**
     * Build the template
     */
    protected function setup()
    {
        $totalPages = (int)ceil($this->total_items / $this->items_per_page);
        $hasPagination = ($this->total_items > $this->items_per_page); // do not paginate

        // check to not go over the limit
        if ($this->current_page > $totalPages)
        {
            $this->current_page = $totalPages;
        }

        // 0 means disabled
        $prevPage = ($this->current_page === 1) ? 0 : $this->current_page - 1;
        $nextPage = ($this->current_page === $totalPages) ? 0 : $this->current_page + 1;

        // set default page, if not set already
        if (!$this->page_url)
        {
            $this->page_url = Util::removeQueryArguments([static::PAGE_ARGUMENT], Util::getCurrentUrl());
        }

        // see if we build the ... on one direction or the other
        $buildLeft = ($this->current_page - 1) > $this->nr_buttons;

        $rightOffset = $this->current_page - 1 + $this->nr_buttons;
        $buildRight = ($rightOffset !== $totalPages) && ($rightOffset < $totalPages);

        if (!$buildLeft && !$buildRight) // both are false, should not happen, build right by default
        {
            $buildRight = true;
        }

        $pagination = [
            "has_pagination" => $hasPagination, // pagination is present
            "current_page"   => $this->current_page,
            "prev_page"      => $prevPage,
            "next_page"      => $nextPage,
            "total_pages"    => $totalPages,
            "url"            => $this->page_url, // the base url to build each button
            "nr_buttons"     => $this->nr_buttons, // the relative number of buttons present except the first and last
            "build_left"     => $buildLeft, // display '...' on the left
            "build_right"    => $buildRight, // display '...' on the right
            "limit_options"  => [
                static::MIN_LIMIT_ITEMS => static::MIN_LIMIT_ITEMS,
                25                      => 25,
                static::MAX_LIMIT_ITEMS => static::MAX_LIMIT_ITEMS
            ],
            "limit_selected" => $this->items_per_page
        ];
        $this->assign("pagination", $pagination);
    }

    /**
     * @param int $page
     *
     * @return $this
     */
    public function setCurrentPage($page)
    {
        $this->current_page = $page;

        return $this;
    }

    /**
     * @param int $items
     *
     * @return $this
     */
    public function setTotalItems($items)
    {
        $this->total_items = $items;

        return $this;
    }

    /**
     * @param string $url
     *
     * @return $this
     */
    public function setPageUrl($url)
    {
        $this->page_url = $url;

        return $this;
    }

    /**
     * @param int $nr
     *
     * @return $this
     */
    public function setNumberButtons($nr)
    {
        $this->nr_buttons = $nr;

        return $this;
    }

    /**
     * @param int $limit
     *
     * @return $this
     */
    public function setItemsPerPage($limit)
    {
        $this->items_per_page = $limit;

        return $this;
    }
}
