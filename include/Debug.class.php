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

use DebugBar\DataCollector\ExceptionsCollector;
use DebugBar\DataCollector\MemoryCollector;
use DebugBar\DataCollector\MessagesCollector;
use DebugBar\DataCollector\PhpInfoCollector;
use DebugBar\DataCollector\RequestDataCollector;
use DebugBar\DataCollector\TimeDataCollector;

/**
 * Class Debug used only when DEBUG_MODE is enabled
 * If DEBUG_TOOLBAR is also enabled it also logs to that
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
            static::$debug_toolbar = new DebugBar\DebugBar();
            static::$debug_toolbar->addCollector(new PhpInfoCollector());
            static::$debug_toolbar->addCollector(new MessagesCollector());
            static::$debug_toolbar->addCollector(new RequestDataCollector());
            static::$debug_toolbar->addCollector(new TimeDataCollector());
            static::$debug_toolbar->addCollector(new MemoryCollector());
            static::$debug_toolbar->addCollector(new ExceptionsCollector());

            //static::$debug_toolbar->setStorage(new DebugBar\Storage\FileStorage(ROOT_PATH));
            //static::$debug_toolbar->addCollector(new DebugBar\DataCollector\MessagesCollector('test'));
        }

        return static::$debug_toolbar;
    }

    public static function addException(Exception $e, $add_to_error_log = true)
    {
        if (!DEBUG_MODE)
            return;

        if ($add_to_error_log)
            error_log('STK-ADDONS: ' . $e);

        if (static::isToolbarEnabled())
            static::getToolbar()['exceptions']->addException($e);
    }

    public static function addMessage($message, $log_level = LogLevel::INFO, $add_to_error_log = true)
    {
        if (!DEBUG_MODE)
            return;

        if ($add_to_error_log)
            error_log('STK-ADDONS: '. $message);

        if (static::isToolbarEnabled())
            static::getToolbar()['messages']->addMessage($message, $log_level);
    }

    /**
     * @return bool
     */
    public static function isToolbarEnabled()
    {
        return DEBUG_MODE && DEBUG_TOOLBAR;
    }
}
