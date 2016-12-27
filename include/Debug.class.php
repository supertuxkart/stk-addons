<?php

/**
 * Copyright 2016 Daniel Butum <danibutum at gmail dot com>
 *
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
class Debug
{
    /**
     * Singleton instance of the debug toolbar;
     * @var DebugBar\StandardDebugBar
     */
    public static $debug_toolbar;

    /**
     * @return DebugBar\StandardDebugBar|null
     * @throws AccessControlException
     */
    public static function getToolbar()
    {
        // prevent information leakage
        if (!static::isToolbarEnabled())
        {
            // should we panic here?
            return null;
        }

        if (!static::$debug_toolbar)
        {
            static::$debug_toolbar = new DebugBar\StandardDebugBar();

            //static::$debug_toolbar->setStorage(new DebugBar\Storage\FileStorage(ROOT_PATH));
            //static::$debug_toolbar->addCollector(new DebugBar\DataCollector\MessagesCollector('test'));
        }

        return static::$debug_toolbar;
    }

    public static function addException(Exception $e)
    {
        static::getToolbar()['exceptions']->addException($e);
    }

    public static function addMessage($message)
    {
        if (DEBUG_MODE)
        {
            error_log('STK-ADDONS - '. $message);
        }

        if (static::isToolbarEnabled())
        {
            static::getToolbar()['messages']->addMessage($message);
        }
    }

    /**
     * @return bool
     */
    public static function isToolbarEnabled()
    {
        return DEBUG_MODE && DEBUG_TOOLBAR;
    }
}
