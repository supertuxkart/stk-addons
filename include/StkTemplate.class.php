<?php
/**
 * Copyright 2014 Stephen Just <stephenjust@gmail.com>
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

// TODO compress assets and html
/**
 * Customization of generic template class for main stkaddons pages
 *
 * @author Stephen
 */
class StkTemplate extends Template
{
    // fake order enumeration
    const ORDER_AFTER = 1;

    const ORDER_BEFORE = 2;

    /**
     * Hold the meta tags
     * @var array
     */
    private $meta_tags = [];

    /**
     * The meta tag description
     * @var string
     */
    private $meta_desc;

    /**
     * Contains the script inline
     * @var array
     */
    private $script_inline = [
        "after"  => [], // output them after the script includes
        "before" => [] // output them before the script includes
    ];

    /**
     * Contains the script includes defined statically
     * @var array
     */
    private $script_includes = [];

    /**
     * Contains the script includes defined dynamically
     * @var array
     */
    private $user_script_includes = [];

    /**
     * Contains the css files defined statically
     * @var array
     */
    private $css_includes = [];

    /**
     * Contains the css files defined dynamically
     * @var array
     */
    private $user_css_includes = [];

    /**
     * Setup the header info for the template
     */
    private function setupHead()
    {
        // Fill meta tags
        $meta_tags = array_merge(
            [
                'content-language' => LANG,
                'description'      => $this->meta_desc
            ],
            $this->meta_tags
        );
        $this->smarty->assign('meta_tags', $meta_tags);

        // fill css
        array_push(
            $this->css_includes,
            ["href"  => LIBS_LOCATION . "bootstrap/dist/css/bootstrap.css"],
            ["href" => CSS_LOCATION . "screen.css", "media" => "screen"],
            ["href" => CSS_LOCATION . "print.css", "media" => "print"],
            ["href" => LIBS_LOCATION . "bootstrap3-wysihtml5-bower/dist/bootstrap3-wysihtml5.min.css"],
            ["href" => "//cdnjs.cloudflare.com/ajax/libs/datatables/1.10.0/css/jquery.dataTables.min.css"],
            ["href" => "//cdn.datatables.net/plug-ins/be7019ee387/integration/bootstrap/3/dataTables.bootstrap.css"]
        );
        $this->smarty->assign("css_includes", array_merge($this->css_includes, $this->user_css_includes));
    }

    /**
     * Setup the footer info for the template
     */
    private function setupFooter()
    {
        // Fill script tags
        $this->script_inline["before"][] = [
            'content' => sprintf(
                "var SITE_ROOT = '%s', BUGS_LOCATION = '%s', JSON_LOCATION = '%s';",
                SITE_ROOT,
                BUGS_LOCATION,
                SITE_ROOT . "json/"
            )
        ];

        $this->smarty->assign('script_inline', $this->script_inline);

        array_push(
            $this->script_includes,
            ['src' => LIBS_LOCATION . "jquery/dist/jquery.js"],
            ['src' => LIBS_LOCATION . "underscore/underscore.js"],
            ['src' => LIBS_LOCATION . "bootstrap/dist/js/bootstrap.js"],
            //['src' => LIBS_LOCATION . "handlebars/handlebars.js"],
            ['src' => LIBS_LOCATION . "typeahead.js/dist/typeahead.bundle.js"],
            ['src' => LIBS_LOCATION . "history.js/scripts/bundled-uncompressed/html4+html5/jquery.history.js"],
            ['src' => "//cdnjs.cloudflare.com/ajax/libs/datatables/1.10.0/jquery.dataTables.min.js"],
            ['src' => "//cdn.datatables.net/plug-ins/be7019ee387/integration/bootstrap/3/dataTables.bootstrap.js"],
            //['src' => LIBS_LOCATION . "wysihtml5x/dist/wysihtml5x.js"],
            ['src' => LIBS_LOCATION . "bootstrap3-wysihtml5-bower/dist/bootstrap3-wysihtml5.all.js"],
            ['src' => LIBS_LOCATION . "bootstrap.growl/bootstrap-growl.js"],
            ['src' => LIBS_LOCATION . "bootbox/bootbox.js"],
            ['src' => JS_LOCATION . 'jquery.newsticker.js'],
            ['src' => JS_LOCATION . 'util.js'],
            ['src' => JS_LOCATION . 'main.js']
        );

        $this->smarty->assign('script_includes', array_merge($this->script_includes, $this->user_script_includes));
    }

    /**
     * Populate the top menu
     */
    private function setupTopMenu()
    {
        // TODO make top menu more dynamic
        $menu = [
            'welcome'  => sprintf(_h('Welcome, %s'), User::getLoggedRealName()),
            'home'     => File::link('index.php', _h("Home")),
            'login'    => File::link('login.php', _h('Login')),
            'logout'   => File::link('login.php?action=logout', _h('Log out')),
            'users'    => File::link('users.php', _h('Users')),
            'upload'   => File::link('upload.php', _h('Upload')),
            'manage'   => File::link('manage.php', _h('Manage')),
            'bugs'     => File::link("bugs/", _h("Bugs")),
            'karts'    => File::link('addons.php?type=karts', _h('Karts')),
            'tracks'   => File::link('addons.php?type=tracks', _h('Tracks')),
            'arenas'   => File::link('addons.php?type=arenas', _h('Arenas')),
            'about'    => File::link('about.php', _h('About')),
            'privacy'  => File::link('privacy.php', _h('Privacy')),
            'stk_home' => File::link('http://supertuxkart.sourceforge.net', _h('STK Homepage'))
        ];

        $logged_in = User::isLoggedIn();
        $this->smarty->assign('show_welcome', $logged_in);
        $this->smarty->assign('show_login', !$logged_in);
        $this->smarty->assign('show_users', $logged_in);
        $this->smarty->assign('show_upload', $logged_in);

        // if the user can edit addons then he can access the manage panel
        $this->smarty->assign('show_manage', User::hasPermission(AccessControl::PERM_EDIT_ADDONS));

        if (Util::getScriptFilename() === 'addons.php')
        {
            $this->smarty->assign('show_karts', !($_GET['type'] === 'karts'));
            $this->smarty->assign('show_tracks', !($_GET['type'] === 'tracks'));
            $this->smarty->assign('show_arenas', !($_GET['type'] === 'arenas'));
        }
        else
        {
            $this->smarty->assign('show_karts', false);
            $this->smarty->assign('show_tracks', false);
            $this->smarty->assign('show_arenas', false);
        }
        $this->smarty->assign('menu', $menu);
    }

    /**
     * Populate language menu
     */
    private function setupLanguageMenu()
    {
        // Language menu
        $this->smarty->assign('lang_menu_lbl', _h('Languages'));
        $langs = [
            // lang href, position left, position top, text
            ['en_US', 0, 0, 'EN'],
            ['ca_ES', -96, -99, 'CA'],
            ['de_DE', 0, -33, 'DE'],
            ['es_ES', -96, -66, 'ES'],
            ['eu_ES', -144, -66, 'EU'],
            ['fr_FR', 0, -66, 'FR'],
            ['ga_IE', 0, -99, 'GA'],
            ['gd_GB', -144, -33, 'GD'],
            ['gl_ES', -48, 0, 'GL'],
            ['id_ID', -48, -33, 'ID'],
            ['it_IT', -96, -33, 'IT'],
            ['nl_NL', -48, -66, 'NL'],
            ['pt_BR', -144, 0, 'PT'],
            ['ru_RU', -48, -99, 'RU'],
            ['zh_TW', -96, 0, 'ZH (T)']
        ];

        $langs_count = count($langs);
        for ($i = 0; $i < $langs_count; $i++)
        {
            $url = $_SERVER['REQUEST_URI'];

            // Generate the url to change the language
            if (strstr($url, '?') === false)
            {
                $url .= '?lang=' . $langs[$i][0];
            }
            else
            {
                // Make sure any existing instances of lang are removed
                $url = preg_replace('/(&(amp;)?)*lang=[a-z_]+/i', null, $url);
                $url = preg_replace('/&(amp;)?$/i', null, $url);
                $url .= '&amp;lang=' . $langs[$i][0];
                $url = str_replace('?&amp;', '?', $url);
            }
            $langs[$i][0] = $url;
        }

        $this->smarty->assign('lang_menu_items', $langs);
    }

    /**
     * Setup the header meta tags and js includes, the top menu and the language menu
     */
    protected function setup()
    {
        $this->setupHead();
        $this->setupTopMenu();
        $this->setupLanguageMenu();
        $this->setupFooter();
    }

    /**
     * Assign the page title with the stk prefix. The title is html escaped
     *
     * @param string $title
     *
     * @return StkTemplate
     */
    public function assignTitle($title)
    {
        $this->smarty->assign("title", h(_('STK Add-ons') . ' | ' . $title));

        return $this;
    }

    /**
     * Add a inline script to the page
     *
     * @param string $content the js source code
     * @param string $order   ORDER_BEFORE to display before the include script or ORDER_AFTER to display after
     *
     * @return StkTemplate
     * @throws InvalidArgumentException on invalid order
     */
    public function addScriptInline($content, $order)
    {
        if (!in_array($order, [static::ORDER_AFTER, static::ORDER_BEFORE]))
        {
            throw new InvalidArgumentException("Invalid order");
        }
        $this->script_inline[$order][] = ["content" => $content];

        return $this;
    }

    /**
     * Add a script file to the page
     *
     * @param string $src      the js file location
     * @param string $location the path to get the resource from
     *
     * @return StkTemplate
     */
    public function addScriptInclude($src, $location = JS_LOCATION)
    {
        $this->user_script_includes[] = ["src" => $location . $src];

        return $this;
    }

    /**
     * Add a css file to the page
     *
     * @param string $href
     * @param string $location default path to look to
     * @param string $media
     *
     * @return StkTemplate
     */
    public function addCssInclude($href, $location = CSS_LOCATION, $media = "")
    {
        $this->user_css_includes[] = [
            "href"  => $location . $href,
            "media" => $media
        ];

        return $this;
    }

    /**
     * Set the description meta tag
     *
     * @param string $desc the page description content
     *
     * @return StkTemplate
     */
    public function setMetaDesc($desc)
    {
        $this->meta_desc = $desc;

        return $this;
    }

    /**
     * Set the meta refresh tag for redirect
     *
     * @param string $target  an destination url
     * @param int    $timeout in seconds
     *
     * @return StkTemplate
     */
    public function setMetaRefresh($target, $timeout = 5)
    {
        $this->meta_tags['refresh'] = sprintf('%d;URL=%s', $timeout, $target);

        return $this;
    }

    /**
     * Set a meta tag to display on the page
     *
     * @param string $key   the attribute key
     * @param string $value the attribute value
     *
     * @return StkTemplate
     */
    public function setMetaTag($key, $value)
    {
        $this->meta_tags[$key] = $value;

        return $this;
    }
}
