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
     * @var int
     */
    protected $totalItems = 0;

    /**
     * @var int
     */
    protected $currentPage = 1;

    /**
     * @var int
     */
    protected $itemsPerPage = 5;

    /**
     * @var string
     */
    protected $pageUrl;

    /**
     * @param null $template_dir
     */
    public function __construct($template_dir = null)
    {
        parent::__construct("pagination.tpl", $template_dir);
    }

    /**
     * @param null $template_dir
     *
     * @return static
     */
    public static function  get($template_dir = null)
    {
        return new static($template_dir);
    }

    /**
     *
     */
    public function setup()
    {
        $totalPages = (int)ceil($this->totalItems / $this->itemsPerPage);
        $hasPagination = ($this->totalItems > $this->itemsPerPage); // do not paginate

        // check to not go over the limit
        if ($this->currentPage > $totalPages)
        {
            $this->currentPage = $totalPages;
        }

        // 0 means disabled
        $prevPage = ($this->currentPage === 1) ? 0 : $this->currentPage - 1;
        $nextPage = ($this->currentPage === $totalPages) ? 0 : $this->currentPage + 1;

        // set default page, if not set already
        if (!$this->pageUrl)
        {
            $this->pageUrl = Util::getCurrentUrl();
        }

        $this->assign(
            "pagination",
            [
                "has_pagination" => $hasPagination,
                "current_page"   => $this->currentPage,
                "prev_page"      => $prevPage,
                "next_page"      => $nextPage,
                "total_pages"    => $totalPages,
                "url"            => $this->pageUrl
            ]
        );
    }

    /**
     * @param int $page
     *
     * @return $this
     */
    public function setCurrentPage($page)
    {
        $this->currentPage = $page;

        return $this;
    }

    /**
     * @param int $items
     *
     * @return $this
     */
    public function setTotalItems($items)
    {
        $this->totalItems = $items;

        return $this;
    }

    /**
     * @param string $url
     *
     * @return $this
     */
    public function setPageUrl($url)
    {
        $this->pageUrl = $url;

        return $this;
    }

    /**
     * @param int $perPage
     *
     * @return $this
     */
    public function setItemsPerPage($perPage)
    {
        $this->itemsPerPage = $perPage;

        return $this;
    }
}
