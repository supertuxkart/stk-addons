<?php
/**
 * copyright 2013      Glenn De Jonghe
 *           2014-2015 Daniel Butum <danibutum at gmail dot com>
 * This file is part of SuperTuxKart
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
 * Data field in database that is not returned
 * @var array
 */
define('SERVER_PRIVATE_FIELD', array('last_poll_time'));

/**
 * Server class
 */

class Server implements IAsXML
{
    /**
     * The server array data from database
     * @var array
     */
    private $data_array;

    /**
     *
     * @param array $data an associative array retrieved from the database
     */
    private function __construct(array $data)
    {
        $this->data_array = $data;
    }

    /**
     * Get server as xml output
     *
     * @return string
     */
    public function asXML()
    {
        $server_xml = new XMLOutput();
        $server_xml->startElement('server');
        foreach ($this->data_array as $key => $value)
        {
            if (in_array($key, SERVER_PRIVATE_FIELD))
                continue;
            $server_xml->writeAttribute($key, $value);
        }
        $server_xml->endElement();
        return $server_xml->asString();
    }

    /**
     * Create server
     *
     * @param string $ip
     * @param int    $port
     * @param int    $private_port
     * @param int    $user_id
     * @param string $server_name
     * @param int    $max_players
     * @param int    $difficulty
     * @param int    $game_mode
     * @param int    $password
     * @param int    $version
     *
     * @return Server
     * @throws ServerException
     */
    public static function create($ip, $port, $private_port, $user_id,
        $server_name, $max_players, $difficulty, $game_mode, $password, $version)
    {
        try
        {
            // Clean non-polled servers < 15 seconds before
            $timeout = time() - 15;
            DBConnection::get()->query(
                "DELETE FROM `" . DB_PREFIX . "servers`
                WHERE `last_poll_time` < :time",
                DBConnection::NOTHING,
                [ ':time'   => $timeout ],
                [ ':time'   => DBConnection::PARAM_INT]
            );

            $count = DBConnection::get()->query(
                "SELECT `id` FROM `" . DB_PREFIX . "servers` WHERE `ip`= :ip AND `port`= :port ",
                DBConnection::ROW_COUNT,
                [':ip' => $ip, ':port' => $port]
            );
            if ($count)
            {
                throw new ServerException(_('Specified server already exists.'));
            }

            $result = DBConnection::get()->query(
                "INSERT INTO `" . DB_PREFIX . "servers` (host_id, name,
                last_poll_time, ip, port, private_port, max_players,
                difficulty, game_mode, password, version)
                VALUES (:host_id, :name, :last_poll_time, :ip, :port,
                :private_port, :max_players, :difficulty, :game_mode,
                :password, :version)",
                DBConnection::ROW_COUNT,
                [
                    ':host_id'        => $user_id,
                    ':name'           => $server_name,
                    ':last_poll_time' => time(),
                    // Do not use (int) or it truncates to 127.255.255.255
                    ':ip'             => $ip,
                    ':port'           => $port,
                    ':private_port'   => $private_port,
                    ':max_players'    => $max_players,
                    ':difficulty'     => $difficulty,
                    ':game_mode'      => $game_mode,
                    ':password'       => $password,
                    ':version'        => $version
                ],
                [
                    ':host_id'        => DBConnection::PARAM_INT,
                    ':name'           => DBConnection::PARAM_STR,
                    ':last_poll_time' => DBConnection::PARAM_INT,
                    ':ip'             => DBConnection::PARAM_INT,
                    ':port'           => DBConnection::PARAM_INT,
                    ':private_port'   => DBConnection::PARAM_INT,
                    ':max_players'    => DBConnection::PARAM_INT,
                    ':difficulty'     => DBConnection::PARAM_INT,
                    ':game_mode'      => DBConnection::PARAM_INT,
                    ':password'       => DBConnection::PARAM_INT,
                    ':version'        => DBConnection::PARAM_INT
                ]
            );
        }

        catch (DBException $e)
        {
            throw new ServerException(exception_message_db(_('create a server')));
        }

        if ($result != 1)
        {
            throw new ServerException(_h('Could not create server'));
        }

        return Server::getServer(DBConnection::get()->lastInsertId());
    }

    /**
     * Get a server instance by id
     *
     * @param int $id
     *
     * @return Server
     * @throws ServerException
     */
    public static function getServer($id)
    {
        try
        {
            $result = DBConnection::get()->query(
                "SELECT * FROM `" . DB_PREFIX . "servers`
                WHERE `id`= :id",
                DBConnection::FETCH_ALL,
                [':id' => $id],
                [":id" => DBConnection::PARAM_INT]
            );
        }
        catch (DBException $e)
        {
            throw new ServerException(exception_message_db(_('retrieve a server')));
        }

        if (!$result)
        {
            throw new ServerException(_h("Server doesn't exist."));
        }
        else if (count($result) > 1)
        {
            throw new ServerException("Multiple servers match the same id.");
        }

        return new self($result[0]);
    }

    /**
     * Get all servers as xml output
     *
     * @return string
     */
    public static function getServersAsXML()
    {
        $servers = DBConnection::get()->query(
            "SELECT *
            FROM `" . DB_PREFIX . "servers`",
            DBConnection::FETCH_ALL
        );

        // build xml
        $partial_output = new XMLOutput();
        $partial_output->startElement('servers');
        foreach ($servers as $server_result)
        {
            $server = new self($server_result);
            $partial_output->insert($server->asXML());
        }
        $partial_output->endElement();

        return $partial_output->asString();
    }
}
