<?php
/**
 * copyright 2015 Daniel Butum <danibutum at gmail dot com>
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
 * Handle the session for the web user
 */
class Session
{
    /**
     * The key to use for $_SESSION
     * @var string
     */
    private $key;

    /**
     * @param $key
     */
    private function __construct($key)
    {
        $this->key = $key;
    }

    /**
     * Set a value in the session for the user
     *
     * @param mixed $key
     * @param mixed $value
     *
     * @return Session
     */
    public function set($key, $value)
    {
        $_SESSION[$this->key][$key] = $value;

        return $this;
    }

    /**
     * Get a key from the session for the user
     *
     * @param mixed $key
     * @param mixed $default the default value if $key does not exist
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (isset($_SESSION[$this->key][$key]))
        {
            return $_SESSION[$this->key][$key];
        }

        return $default;
    }

    /**
     * Check if the session is empty
     * @return bool
     */
    public function isEmpty()
    {
        return static::isStarted() ? empty($_SESSION[$this->key]) : false;
    }

    /**
     * See if the key exists in the user session
     *
     * @param mixed $key
     *
     * @return bool
     */
    public function has($key)
    {
        return static::isStarted() ? isset($_SESSION[$this->key][$key]) : false;
    }

    /**
     * Init the session values for the current key
     *
     * @return Session
     */
    public function init()
    {
        if (!static::isStarted())
        {
            trigger_error(sprintf("Can not build session object = '%s' without starting the session", $this->key));

            return $this;
        }

        $_SESSION[$this->key] = [];

        return $this;
    }

    /**
     * Util method, create a new user with key 'user'
     * @return Session
     */
    public static function user()
    {
        static $instance;
        if (!$instance)
        {
            $instance = static::withKey("user");
        }

        return $instance;
    }

    /**
     * Create a new session with key
     *
     * @param string $key
     *
     * @return Session
     */
    public static function withKey($key)
    {
        return new static($key);
    }

    /**
     * Remove all items from the session
     */
    public static function flush()
    {
        if (!static::isStarted())
        {
            trigger_error("Session failed to clear because it was not started");

            return;
        }

        $_SESSION = [];
        session_unset();
    }

    /**
     * Destroys all data registered to a session
     */
    public static function destroy()
    {
        if (!static::isStarted())
        {
            return;
        }

        if (!session_destroy())
        {
            trigger_error("Session failed to destroy");
        }
    }

    /**
     * Start a session, only if was no previous started
     *
     * @param string $name the name of the session identifier
     */
    public static function start($name = "STK_SESSID")
    {
        if (static::isStarted())
        {
            return;
        }

        session_name($name);
        if (!session_start())
        {
            trigger_error("Session failed to start");
        }
    }

    /**
     * Checks if sessions are enabled and one session exists
     * @return bool
     */
    public static function isStarted()
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }
}
