<?php
/**
 * Copyright 2012-2014 Stephen Just <stephenjust@gmail.com>
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
 * Create a template object
 */
class Template
{
    /**
     * @var Smarty
     */
    protected $smarty;

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
     * @param string|null $template
     */
    public function __construct($template_file, $template = null)
    {
        $this->createSmartyInstance();
        $this->setTemplateDir($template);
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
        $this->directory = Template::getTemplateDir($template_name);
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
            throw new TemplateException(sprintf('Could not find template file "%s".', htmlspecialchars($file_name)));
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
        $this->smarty->setCompileDir(TMP_PATH . 'tpl_c' . DS);
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
        catch(Exception $e)
        {
            if (DEBUG_MODE)
            {
                return sprintf("Template Error: %s", $e->getMessage());
            }

            return "Template Error";
        }
    }

    /**
     * Assign multiple values using an associative array
     *
     * @param array $assigns
     *
     * @throws TemplateException
     */
    public function assignments($assigns)
    {
        if (!is_array($assigns))
        {
            throw new TemplateException('Invalid template assignments.');
        }

        foreach ($assigns as $key => $value)
        {
            $this->smarty->assign($key, $value);
        }
    }

    /**
     * Assign a value to a template variable
     *
     * @param string $key
     * @param mixed  $value May be a string or an array
     */
    public function assign($key, $value)
    {
        $this->smarty->assign($key, $value);
    }

    /**
     * Get the path to the template file directory, based on the template name
     *
     * @param string $template
     *
     * @return string
     * @throws TemplateException
     */
    public static function getTemplateDir($template)
    {
        if ($template === null)
        {
            return TPL_PATH;
        }
        if (preg_match('/[a-z0-9\\-_]/i', $template))
        {
            throw new TemplateException('Invalid character in template name.');
        }
        $dir = ROOT_PATH . 'tpl' . DS . $template . DS;
        if (file_exists($dir) && is_dir($dir))
        {
            return $dir;
        }
        throw new TemplateException(sprintf('The selected template "%s" does not exist.', htmlspecialchars($template)));
    }
}
