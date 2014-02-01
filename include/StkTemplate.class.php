<?php
/**
 * Copyright 2014 Stephen Just <stephenjust@gmail.com>
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

require_once(INCLUDE_DIR.'locale.php');
require_once(INCLUDE_DIR.'Template.class.php');

/**
 * Customization of generic template class for main STKAddons pages
 *
 * @author Stephen
 */
class StkTemplate extends Template {
    private $meta_tags = array();
    private $meta_desc = NULL;
    
    protected function setup() {
        $this->setupHead();
        $this->setupTopMenu();
        $this->setupLanguageMenu();
    }
    
    private function setupHead() {
        // Fill meta tags
        $meta_tags = array_merge(array(
            'content-type' => 'text/html; charset=UTF-8',
            'content-language' => LANG,
            'description' => $this->meta_desc
                ), $this->meta_tags);
        $this->smarty->assign('meta_tags', $meta_tags);

        // Fill script tags
        $script_inline = array(
            array('content' => "var siteRoot='http://localhost/stk-web/';")
        );
        $this->smarty->assign('script_inline', $script_inline);
        $script_includes = array(
            array('src' => 'https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js'),
            array('src' => SITE_ROOT . 'js/jquery.newsticker.js'),
            array('src' => SITE_ROOT . 'js/script.js')
        );
        $this->smarty->assign('script_includes', $script_includes);
    }
    
    private function setupTopMenu() {
        $name = isset($_SESSION['real_name']) ? $_SESSION['real_name'] : NULL;
        $menu = array(
            'welcome' => sprintf(htmlspecialchars(_('Welcome, %s')), $name),
            'home' => File::link('index.php', htmlspecialchars(_("Home"))),
            'login' => File::link('login.php', htmlspecialchars(_('Login'))),
            'logout' => File::link('login.php?action=logout', htmlspecialchars(_('Log out'))),
            'users' => File::link('users.php', htmlspecialchars(_('Users'))),
            'upload' => File::link('upload.php', htmlspecialchars(_('Upload'))),
            'manage' => File::link('manage.php', htmlspecialchars(_('Manage'))),
            'karts' => File::link('addons.php?type=karts', htmlspecialchars(_('Karts'))),
            'tracks' => File::link('addons.php?type=tracks', htmlspecialchars(_('Tracks'))),
            'arenas' => File::link('addons.php?type=arenas', htmlspecialchars(_('Arenas'))),
            'about' => File::link('about.php', htmlspecialchars(_('About'))),
            'privacy' => File::link('privacy.php', htmlspecialchars(_('Privacy'))),
            'stk_home' => File::link('http://supertuxkart.sourceforge.net', htmlspecialchars(_('STK Homepage')))
        );
        $this->smarty->assign('show_welcome', User::$logged_in);
        $this->smarty->assign('show_login', !User::$logged_in);
        $this->smarty->assign('show_users', User::$logged_in);
        $this->smarty->assign('show_upload', User::$logged_in);
        $this->smarty->assign('show_manage', (isset($_SESSION['role']['manageaddons'])) ? $_SESSION['role']['manageaddons'] : false);
        if (basename(get_self()) == 'addons.php') {
            $this->smarty->assign('show_karts', !($_GET['type'] == 'karts'));
            $this->smarty->assign('show_tracks', !($_GET['type'] == 'tracks'));
            $this->smarty->assign('show_arenas', !($_GET['type'] == 'arenas'));
        } else {
            $this->smarty->assign('show_karts', false);
            $this->smarty->assign('show_tracks', false);
            $this->smarty->assign('show_arenas', false);
        }
        $this->smarty->assign('menu', $menu);
    }
    
    /**
     * Populate language menu
     */
    private function setupLanguageMenu() {
               // Language menu
        $this->smarty->assign('lang_menu_lbl', htmlspecialchars(_('Languages')));
        $langs = array(
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
        for ($i = 0; $i < count($langs); $i++) {
            $url = $_SERVER['REQUEST_URI'];
            // Generate the url to change the language
            if (strstr($url, '?') === false)
                $url .= '?lang=' . $langs[$i][0];
            else {
                // Make sure any existing instances of lang are removed
                $url = preg_replace('/(&(amp;)?)*lang=[a-z_]+/i', NULL, $url);
                $url = preg_replace('/&(amp;)?$/i', NULL, $url);
                $url .= '&amp;lang=' . $langs[$i][0];
                $url = str_replace('?&amp;', '?', $url);
            }
            $langs[$i][0] = $url;
        }
        $this->smarty->assign('lang_menu_items', $langs);
    }
    
    public function setMetaDesc($desc) {
        $this->meta_desc = $desc;
    }
    public function setMetaRefresh($target, $timeout) {
        $this->meta_tags['refresh'] = sprintf('%d;URL=%s', $timeout, $target);
    }
    public function setMetaTag($key, $value) {
        $this->meta_tags[$key] = $value;
    }
}

?>
