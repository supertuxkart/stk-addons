<?php
/**
 * Copyright 2014      Stephen Just <stephenjust@gmail.com>
 *           2014-2016 Daniel Butum <danibutum at gmail dot com>
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


/**
 * Customization of generic template class for main stkaddons pages
 * @author Stephen
 */
class StkTemplate extends Template
{
    // fake order enumeration
    /**
     * @var string
     */
    const ORDER_AFTER = "after";

    /**
     * @var string
     */
    const ORDER_BEFORE = "before";

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
     * Return a instance of the class, factory method
     *
     * @param string      $template_file
     * @param string|null $template_dir
     *
     * @return StkTemplate
     */
    public static function get($template_file, $template_dir = null)
    {
        return new static($template_file, $template_dir);
    }

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
            ["href" => LIBS_LOCATION . "bootstrap/dist/css/bootstrap.min.css"],
            ["href" => CSS_LOCATION . "main.css", "media" => "screen"]
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
                "var ROOT_LOCATION = '%s', BUGS_LOCATION = '%s', JSON_LOCATION = '%s';",
                ROOT_LOCATION,
                BUGS_LOCATION,
                ROOT_LOCATION . "json/"
            )
        ];

        $this->smarty->assign('script_inline', $this->script_inline);

        array_push(
            $this->script_includes,
            ['src' => LIBS_LOCATION . "jquery/dist/jquery.min.js"],
            ['src' => LIBS_LOCATION . "underscore/underscore.js"],
            ['src' => LIBS_LOCATION . "bootstrap/dist/js/bootstrap.min.js"],
            ['src' => LIBS_LOCATION . "history.js/scripts/bundled/html4+html5/jquery.history.js"],
            ['src' => LIBS_LOCATION . "bootstrap-hover-dropdown/bootstrap-hover-dropdown.min.js"],
            ['src' => LIBS_LOCATION . "bootstrap.growl/bootstrap-growl.min.js"],
            ['src' => LIBS_LOCATION . "bootbox/bootbox.js"],
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
            'welcome' => h(sprintf(_h('Welcome, %s'), User::getLoggedRealName())),
            'home'    => File::rewrite('index.php'),
            'login'   => File::rewrite('login.php'),
            'logout'  => File::rewrite('login.php?action=logout'),
            'users'   => File::rewrite('users.php'),
            'upload'  => File::rewrite('upload.php'),
            'manage'  => File::rewrite('manage.php'),
            'bugs'    => BUGS_LOCATION,
            'karts'   => File::rewrite('addons.php?type=' . Addon::typeToString(Addon::KART)),
            'tracks'  => File::rewrite('addons.php?type=' . Addon::typeToString(Addon::TRACK)),
            'arenas'  => File::rewrite('addons.php?type=' . Addon::typeToString(Addon::ARENA)),
            'about'   => File::rewrite('about.php'),
            'stats'   => STATS_LOCATION,
            'privacy' => File::rewrite('privacy.php'),
        ];

        // if the user can edit addons then he can access the manage panel
        $this->smarty->assign('is_logged', User::isLoggedIn());
        $this->smarty->assign('can_edit_addons', User::hasPermission(AccessControl::PERM_EDIT_ADDONS));

        $this->smarty->assign('menu', $menu);
    }

    /**
     * Populate language menu
     */
    private function setupLanguageMenu()
    {
        // Language menu
        $languages = SLocale::getLanguages();
        $data = [
            "label" => _h('Languages'),
            "items" => []
        ];

        $languages_count = count($languages);
        for ($i = 0; $i < $languages_count; $i++)
        {
            // Get the current page address (without "lang" parameter)
            $url = $_SERVER['REQUEST_URI'];

            // Generate the url to change the language
            if (!Util::str_contains($url, "?"))
            {
                $url .= '?lang=' . $languages[$i][0];
            }
            else
            {
                // Make sure any existing instances of lang are removed
                $url = preg_replace('/(&(amp;)?)*lang=[a-z_]+/i', null, $url);
                $url = preg_replace('/&(amp;)?$/i', null, $url);
                $url .= '&amp;lang=' . $languages[$i][0];
                $url = str_replace('?&amp;', '?', $url);
            }

            $data["items"][] = [
                "url" => $url,
                "y"   => $languages[$i][1]
            ];
        }

        $this->smarty->assign('lang', $data);
    }

    /**
     * Setup the necessary config files variable template paths and others
     */
    private function setupConfigVars()
    {
        $directory = $this->getTemplateDirectory();
        $config = [
            "directory" => $directory,
            "header"    => $directory . "header.tpl",
            "footer"    => $directory . "footer.tpl"
        ];
        $this->assign("tpl_config", $config);

        $this->assign("root_location", ROOT_LOCATION);
        $this->assign("favicon_location", IMG_LOCATION . "favicon/");
    }

    /**
     * Setup the header meta tags and js includes, the top menu and the language menu
     */
    protected function setup()
    {
        // Enable debug toolbar
        if (Debug::isToolbarEnabled())
        {
            $renderer = Debug::getToolbar()->getJavascriptRenderer();
            $this->smarty->assign(
                'debug_toolbar',
                ['header' => $renderer->renderHead(), 'footer' => $renderer->render()]
            );
        }
        else
        {
            $this->smarty->assign('debug_toolbar', ['header' => '', 'footer' => '']);
        }

        $this->setupHead();
        $this->setupTopMenu();
        $this->setupLanguageMenu();
        $this->setupFooter();
        $this->setupConfigVars();
    }

    /**
     * Assign the page title with the stk prefix. The title is html escaped
     *
     * @param string $title
     *
     * @return $this
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
     * @return $this
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
     * Add a script file to the page from the web
     *
     * @param string $src the js file location
     * @param bool   $ie  see if this script is for IE only, add it as a conditional
     *
     * @return $this
     */
    public function addScriptIncludeWeb($src, $ie = false)
    {
        return $this->addScriptInclude($src, '', $ie);
    }

    /**
     * Add a script file to the page from the local filesystem
     *
     * @param string $src      the js file location
     * @param string $location the path to get the resource from
     * @param bool   $ie       see if this script is for IE only, add it as a conditional
     *
     * @return $this
     */
    public function addScriptInclude($src, $location = JS_LOCATION, $ie = false)
    {
        $this->user_script_includes[] = ["src" => $location . $src, "ie" => $ie];

        return $this;
    }

    /**
     * Add a css file to the page from the local filesystem
     *
     * @param string $href
     * @param string $location default path to look to
     * @param string $media
     *
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
     */
    public function setMetaTag($key, $value)
    {
        $this->meta_tags[$key] = $value;

        return $this;
    }

    /**
     * Add the util.js library
     *
     * @return $this
     */
    public function addUtilLibrary()
    {
        $this->addScriptInclude("util.js");

        return $this;
    }

    /**
     * Add the bootstrap 3 multiselect plugin
     *
     * @link http://davidstutz.github.io/bootstrap-multiselect/
     * @return $this
     */
    public function addBootstrapMultiSelectLibrary()
    {
        $this->addCssInclude("bootstrap-multiselect/dist/css/bootstrap-multiselect.css", LIBS_LOCATION);
        $this->addScriptInclude("bootstrap-multiselect/dist/js/bootstrap-multiselect.js", LIBS_LOCATION);

        return $this;
    }

    /**
     * Add bootstrap select plugin
     *
     * @link http://silviomoreto.github.io/bootstrap-select/
     * @return $this
     */
    public function addBootstrapSelectLibrary()
    {
        $this->addCssInclude("bootstrap-select/dist/css/bootstrap-select.min.css", LIBS_LOCATION);
        $this->addScriptInclude("bootstrap-select/dist/js/bootstrap-select.min.js", LIBS_LOCATION);

        return $this;
    }

    /**
     * Add bootstrap file input plugin
     *
     * @link https://github.com/kartik-v/bootstrap-fileinput
     * @return $this
     */
    public function addBootstrapFileInputLibrary()
    {
        $this->addCssInclude("bootstrap-fileinput/css/fileinput.min.css", LIBS_LOCATION);
        $this->addScriptInclude("bootstrap-fileinput/js/fileinput.min.js", LIBS_LOCATION);

        return $this;
    }

    /**
     * Add bootstrap validation plugin
     *
     * @link http://bootstrapvalidator.com/
     * @return $this
     */
    public function addBootstrapValidatorLibrary()
    {
        $this->addCssInclude("bootstrapValidator/dist/css/bootstrapValidator.min.css", LIBS_LOCATION);
        $this->addScriptInclude("bootstrapValidator/dist/js/bootstrapValidator.min.js", LIBS_LOCATION);

        return $this;
    }

    /**
     * Add twitter typehead library for autocompletion
     *
     * @link http://twitter.github.io/typeahead.js/
     * @return StkTemplate
     */
    public function addTypeHeadLibrary()
    {
        $this->addScriptInclude("typeahead.js/dist/typeahead.jquery.min.js", LIBS_LOCATION);

        return $this;
    }

    /**
     * Add the editor library
     *
     * @link https://github.com/Waxolunist/bootstrap3-wysihtml5-bower
     * @return StkTemplate
     */
    public function addWYSIWYGLibrary()
    {
        $this->addCssInclude("bootstrap3-wysihtml5-bower/dist/bootstrap3-wysihtml5.min.css", LIBS_LOCATION);

        // includes handlebars runtime and editor library
        $this->addScriptInclude("bootstrap3-wysihtml5-bower/dist/bootstrap3-wysihtml5.all.min.js", LIBS_LOCATION);

        return $this;
    }

    /**
     * Add the datatables library
     *
     * @link http://www.datatables.net/
     * @return $this
     */
    public function addDataTablesLibrary()
    {
        $this->addCssInclude("datatables/media/css/jquery.dataTables.min.css", LIBS_LOCATION);
        $this->addCssInclude("datatables-bootstrap3/BS3/assets/css/datatables.css", LIBS_LOCATION);
        $this->addScriptInclude("datatables/media/js/jquery.dataTables.min.js", LIBS_LOCATION);
        $this->addScriptInclude("datatables-bootstrap3/BS3/assets/js/datatables.js", LIBS_LOCATION);

        return $this;
    }

    /**
     * Add the flot library
     *
     * @link http://www.flotcharts.org/
     * @return $this
     */
    public function addFlotLibrary()
    {
        $this->addScriptInclude("flot/excanvas.min.js", LIBS_LOCATION, true);
        $this->addScriptInclude("flot/jquery.flot.js", LIBS_LOCATION);
        $this->addScriptInclude("flot/jquery.flot.pie.js", LIBS_LOCATION);
        $this->addScriptInclude("flot/jquery.flot.time.js", LIBS_LOCATION);
        $this->addScriptInclude("flot.tooltip/js/jquery.flot.tooltip.min.js", LIBS_LOCATION);

        return $this;
    }
}
