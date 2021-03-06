<?php
/**
 * Copyright 2012-2014 Stephen Just <stephenjust@gmail.com>
 *                2016 Daniel Butum <danibutum at gmail dot com>
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
 * Create a template object. This class is just a simple wrapper around a smarty instance.
 */
class Template
{
    /**
     * The smarty instance used
     * @var Smarty
     */
    protected $smarty;

    /**
     * @var StkLocale|null
     */
    protected static $locale;

    /**
     * The template file
     * @var string
     */
    private $file;

    /**
     * The template directory
     * @var string
     */
    private $directory;

    /**
     * Flag that indicates to minify the html
     * @var boolean
     */
    private $minify = true;

    /**
     * Template constructor.
     *
     * @param string      $template_file
     * @param null|string $template_dir
     */
    public function __construct($template_file, $template_dir = null)
    {
        if (!static::$locale)
        {
            static::$locale = new StkLocale();
        }

        $this->createSmartyInstance();
        $this->setTemplateDirectory($template_dir);
        $this->setTemplateFile($template_file);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getFilledTemplate();
    }

    /**
     * @return string
     */
    public function toString()
    {
        return (string)$this;
    }

    /**
     * Assign multiple values using an associative array
     *
     * @param array $assigns
     *
     * @return Template
     * @throws TemplateException
     */
    public function assignments(array $assigns)
    {
        foreach ($assigns as $key => $value)
        {
            $this->smarty->assign($key, $value);
        }

        return $this;
    }

    /**
     * Assign a value to a template variable
     *
     * @param string $key
     * @param mixed  $value May be a string or an array
     *
     * @return $this
     */
    public function assign($key, $value)
    {
        $this->smarty->assign($key, $value);

        return $this;
    }

    /**
     * Get the path to the template file directory, based on the template directory
     *
     * @param string|null $template_dir if null return the default template version
     *
     * @return string
     * @throws TemplateException
     */
    public static function getTemplateDirectoryVersion($template_dir = null)
    {
        if (!$template_dir)
        {
            return TPL_PATH;
        }
        if (preg_match('/[a-z0-9\\-_]/i', $template_dir))
        {
            throw new TemplateException('Invalid character in template name.');
        }

        $dir = ROOT_PATH . 'tpl' . DS . $template_dir . DS;
        if (FileSystem::isDirectory($dir))
        {
            return $dir;
        }

        throw new TemplateException(sprintf('The selected template "%s" does not exist.', h($template_dir)));
    }

    /**
     * Enable or disable minify
     *
     * @param bool $minify
     *
     * @return $this
     */
    public function setMinify($minify)
    {
        $this->minify = $minify;

        return $this;
    }

    /**
     * Setup function for children to override
     */
    protected function setup()
    {
        throw new TemplateException("Not Implemented");
    }

    /**
     * Setup HTTP headers
     */
    protected function setupHeaders()
    {
        header('Content-Type: text/html; charset=utf-8', true);
    }

    /**
     * Set the template directory to use
     *
     * @param string $template_name
     *
     * @throws TemplateException
     */
    private function setTemplateDirectory($template_name)
    {
        if ($this->file !== null)
        {
            throw new TemplateException('You cannot change the template after a template file is selected.');
        }
        $this->directory = static::getTemplateDirectoryVersion($template_name);
        $this->smarty->setTemplateDir($this->directory);
    }

    /**
     * Set the template file to use
     *
     * @param string $file_name
     *
     * @throws TemplateException
     */
    private function setTemplateFile($file_name)
    {
        if ($this->directory === null)
        {
            throw new TemplateException('You cannot select a template file until you select a template directory.');
        }
        if (!FileSystem::exists($this->directory . $file_name))
        {
            throw new TemplateException(sprintf('Could not find template file "%s".', h($file_name)));
        }

        $this->file = $this->directory . $file_name;
    }

    /**
     * @return string
     */
    public function getTemplateDirectory()
    {
        return $this->directory;
    }

    /**
     * @return string
     */
    public function getTemplateFile()
    {
        return $this->file;
    }

    /**
     * Create an instance of Smarty to use
     *
     * @throws TemplateException
     */
    private function createSmartyInstance()
    {
        if ($this->smarty !== null)
        {
            throw new TemplateException('Smarty was already configured.');
        }
        $this->smarty = new Smarty;
        $this->smarty->setCompileDir(CACHE_PATH . 'tpl_c' . DS);
    }

    /**
     * Populate a template and return it
     *
     * @return string
     */
    private function getFilledTemplate()
    {
        $this->setupHeaders();

        try
        {
            $this->setup();

            // minify html
            if (!DEBUG_MODE && $this->minify)
            {
                $this->smarty->registerFilter("output", function ($tpl_output, Smarty_Internal_Template $template) {
                    return minify_html($tpl_output, $template);
                });
            }

            ob_start();
            $this->smarty->display($this->file, $this->directory);

            return ob_get_clean();
        }
        catch (SmartyException|TemplateException|Exception $e)
        {
            Debug::addMessage("Template error = " . var_export($e));

            return sprintf("Template Error: %s", $e->getMessage());
        }
    }
}
