<?php
/**
 * Copyright 2012-2014 Stephen Just <stephenjust@gmail.com>
 *                2014 Daniel Butum <danibutum at gmail dot com>
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
 * Create a template object
 */
class Template
{
    /**
     * @var Smarty
     */
    protected $smarty;

    /**
     * @var SLocale
     */
    protected static $locale;

    /**
     * @var string
     */
    private $file;

    /**
     * @var string
     */
    private $directory;

    /**
     * @param string      $template_file
     * @param string|null $template_dir
     */
    public function __construct($template_file, $template_dir = null)
    {
        if (!static::$locale)
        {
            static::$locale = SLocale::get();
        }

        $this->createSmartyInstance();
        $this->setTemplateDir($template_dir);
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
     * @return Template
     */
    public function assign($key, $value)
    {
        $this->smarty->assign($key, $value);

        return $this;
    }

    /**
     * Get the path to the template file directory, based on the template name
     *
     * @param string $template_dir
     *
     * @return string
     * @throws TemplateException
     */
    public static function getTemplateDir($template_dir)
    {
        if (is_null($template_dir))
        {
            return TPL_PATH;
        }
        if (preg_match('/[a-z0-9\\-_]/i', $template_dir))
        {
            throw new TemplateException('Invalid character in template name.');
        }

        $dir = ROOT_PATH . 'tpl' . DS . $template_dir . DS;
        if (file_exists($dir) && is_dir($dir))
        {
            return $dir;
        }

        throw new TemplateException(sprintf('The selected template "%s" does not exist.', h($template_dir)));
    }

    /**
     * Return a instance of the class, factory method
     *
     * @param string      $template_file
     * @param string|null $template_dir
     *
     * @return static
     */
    public static function get($template_file, $template_dir = null)
    {
        return new static($template_file, $template_dir);
    }

    /**
     * Setup function for children to override
     */
    protected function setup() { throw new TemplateException("Not Implemented"); }

    /**
     * Set the template directory to use
     *
     * @param string $template_name
     *
     * @throws TemplateException
     */
    private function setTemplateDir($template_name)
    {
        if ($this->file !== null)
        {
            throw new TemplateException('You cannot change the template after a template file is selected.');
        }
        $this->directory = static::getTemplateDir($template_name);
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
            throw new TemplateException('You cannot select a template file until you select a template.');
        }
        if (!file_exists($this->directory . $file_name))
        {
            throw new TemplateException(sprintf('Could not find template file "%s".', h($file_name)));
        }

        $this->file = $this->directory . $file_name;
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
        try
        {
            $this->setup();
            ob_start();
            $this->smarty->display($this->file, $this->directory);

            return ob_get_clean();
        }
        catch(SmartyException $e)
        {
            if (DEBUG_MODE)
            {
                trigger_error("Template error: ");
                var_dump($e);
            }

            return sprintf("Template Error: %s", $e->getMessage());
        }
    }
}
