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

require_once(INCLUDE_PATH . 'locale.php');

/**
 * Customization of generic template class for main stkaddons pages
 *
 * @author Stephen
 */
class StkTemplate extends Template
{
    const ORDER_AFTER = "after";

    const ORDER_BEFORE = "before";

    /**
     * Hold the meta tags
     * @var array
     */
    private $meta_tags = array();

    /**
     * The meta tag description
     * @var string
     */
    private $meta_desc;

    /**
     * Contains the script inlines
     * @var array
     */
    private $script_inline = array(
        "after"  => array(), // output them after the script includes
        "before" => array() // output them before the script includes
    );

    /**
     * Contains the script includes
     * @var array
     */
    private $script_includes = array();

    /**
     * Contains the css files
     * @var array
     */
    private $css_includes = array();

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
     * Setup the header info for the template
     */
    private function setupHead()
    {
        // Fill meta tags
        $meta_tags = array_merge(
            array(
                'content-type'     => 'text/html; charset=UTF-8',
                'content-language' => LANG,
                'description'      => $this->meta_desc
            ),
            $this->meta_tags
        );
        $this->smarty->assign('meta_tags', $meta_tags);

        // fill css
        array_push(
            $this->css_includes,
            array(
                "media" => "all",
                "href"  => "//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.1.1/css/bootstrap.min.css"
            ),
            array(
                "media" => "screen",
                "href"  => SITE_ROOT . 'css/screen.css'
            ),
            array(
                "media" => "print",
                "href"  => SITE_ROOT . 'css/print.css'
            )
        );
        $this->smarty->assign("css_includes", $this->css_includes);
    }

    /**
     * Setup the footer info for the template
     */
    private function setupFooter()
    {
        // Fill script tags
        $this->script_inline["before"][] = array('content' => "var siteRoot='" . SITE_ROOT . "';");

        $this->smarty->assign('script_inline', $this->script_inline);

        array_push(
            $this->script_includes,
            array('src' => '//cdnjs.cloudflare.com/ajax/libs/jquery/1.11.1/jquery.min.js'),
            array('src' => '//cdnjs.cloudflare.com/ajax/libs/underscore.js/1.6.0/underscore-min.js'),
            array('src' => "//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.1.1/js/bootstrap.min.js"),
            array('src' => SITE_ROOT . 'js/jquery.newsticker.js'),
            array('src' => SITE_ROOT . 'js/script.js')
        );
        $this->smarty->assign('script_includes', $this->script_includes);
    }

    /**
     * Populate the top menu
     */
    private function setupTopMenu()
    {
        $name = isset($_SESSION['real_name']) ? $_SESSION['real_name'] : "";
        $menu = array(
            'welcome'  => sprintf(htmlspecialchars(_('Welcome, %s')), $name),
            'home'     => File::link('index.php', htmlspecialchars(_("Home"))),
            'login'    => File::link('login.php', htmlspecialchars(_('Login'))),
            'logout'   => File::link('login.php?action=logout', htmlspecialchars(_('Log out'))),
            'users'    => File::link('users.php', htmlspecialchars(_('Users'))),
            'upload'   => File::link('upload.php', htmlspecialchars(_('Upload'))),
            'manage'   => File::link('manage.php', htmlspecialchars(_('Manage'))),
            'karts'    => File::link('addons.php?type=karts', htmlspecialchars(_('Karts'))),
            'tracks'   => File::link('addons.php?type=tracks', htmlspecialchars(_('Tracks'))),
            'arenas'   => File::link('addons.php?type=arenas', htmlspecialchars(_('Arenas'))),
            'about'    => File::link('about.php', htmlspecialchars(_('About'))),
            'privacy'  => File::link('privacy.php', htmlspecialchars(_('Privacy'))),
            'stk_home' => File::link('http://supertuxkart.sourceforge.net', htmlspecialchars(_('STK Homepage')))
        );
        $this->smarty->assign('show_welcome', User::isLoggedIn());
        $this->smarty->assign('show_login', !User::isLoggedIn());
        $this->smarty->assign('show_users', User::isLoggedIn());
        $this->smarty->assign('show_upload', User::isLoggedIn());
        $this->smarty->assign(
            'show_manage',
            User::hasPermission(AccessControl::PERM_EDIT_ADDONS)
        );
        if (basename(get_self()) === 'addons.php')
        {
            $this->smarty->assign('show_karts', !($_GET['type'] == 'karts'));
            $this->smarty->assign('show_tracks', !($_GET['type'] == 'tracks'));
            $this->smarty->assign('show_arenas', !($_GET['type'] == 'arenas'));
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
        $this->smarty->assign('lang_menu_lbl', htmlspecialchars(_('Languages')));
        $langs = array(
            // lang href, position left, position top, text
            array('en_US', 0, 0, 'EN'),
            array('ca_ES', -96, -99, 'CA'),
            array('de_DE', 0, -33, 'DE'),
            array('es_ES', -96, -66, 'ES'),
            array('eu_ES', -144, -66, 'EU'),
            array('fr_FR', 0, -66, 'FR'),
            array('ga_IE', 0, -99, 'GA'),
            array('gd_GB', -144, -33, 'GD'),
            array('gl_ES', -48, 0, 'GL'),
            array('id_ID', -48, -33, 'ID'),
            array('it_IT', -96, -33, 'IT'),
            array('nl_NL', -48, -66, 'NL'),
            array('pt_BR', -144, 0, 'PT'),
            array('ru_RU', -48, -99, 'RU'),
            array('zh_TW', -96, 0, 'ZH (T)')
        );
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
     * Add a inline script to the page
     *
     * @param string $content the js source code
     * @param string $order   'before' to display before the include script or 'after' to display after
     *
     * @throws TemplateException on invalid order
     */
    public function addScriptInline($content, $order = "before")
    {
        if (!in_array($order, array(static::ORDER_AFTER, static::ORDER_BEFORE)))
        {
            throw new TemplateException("Invalid order");
        }
        $this->script_inline[$order][] = array(
            "content" => $content
        );
    }

    /**
     * Add a script file to the page
     *
     * @param string $src the js file location
     */
    public function addScriptInclude($src)
    {
        $this->script_includes[] = array(
            "src" => $src
        );
    }

    /**
     * Add a css file to the page
     *
     * @param string $href
     * @param string $media
     */
    public function addCssInclude($href, $media = "all")
    {
        $this->css_includes[] = array(
            "href"  => $href,
            "media" => $media
        );
    }

    /**
     * Set the description meta tag
     *
     * @param string $desc
     */
    public function setMetaDesc($desc)
    {
        $this->meta_desc = $desc;
    }

    /**
     * Set the meta refresh tag for redirect
     *
     * @param string $target  an destination url
     * @param int    $timeout in seconds
     */
    public function setMetaRefresh($target, $timeout)
    {
        $this->meta_tags['refresh'] = sprintf('%d;URL=%s', $timeout, $target);
    }

    /**
     * Set a meta tag to display on the page
     *
     * @param string $key   the attribute key
     * @param string $value the attribute value
     */
    public function setMetaTag($key, $value)
    {
        $this->meta_tags[$key] = $value;
    }
}
