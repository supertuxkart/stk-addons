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
 * Server class
 */
class Server
{
    /**
     * The server id
     * @var int
     */
    private $id;

    /**
     * The user who created the server
     * @var int
     */
    private $host_id;

    /**
     * The server name
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $max_players;
    
    /**
     * The server's IP address
     * @var int
     */
    private $ip;

    /**
     * The server's public port
     * @var int
     */
    private $port;
    
    /**
     * The server's private port
     * @var int
     */
    private $private_port;
    
    /**
     *
     * @param array $data an associative array retrieved from the database
     */
    private function __construct(array $data)
    {
        $this->id = (int)$data["id"];
        $this->host_id = (int)$data["host_id"];
        $this->name = $data["name"];
        $this->max_players = (int)$data["max_players"];
        $this->ip = (int)$data["ip"];
        $this->port = (int)$data["port"];
        $this->private_port = (int)$data["private_port"];
    }

    /**
     * @return int
     */
    public function getServerId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getHostId()
    {
        return $this->host_id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getMaxPlayers()
    {
        return $this->max_players;
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
        $server_xml->writeAttribute("id", $this->id);
        $server_xml->writeAttribute("hostid", $this->host_id);
        $server_xml->writeAttribute("name", $this->name);
        $server_xml->writeAttribute("max_players", $this->max_players);
        $server_xml->writeAttribute("ip", $this->ip);
        $server_xml->writeAttribute("port", $this->port);
        $server_xml->writeAttribute("private_port", $this->private_port);
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
     *
     * @return Server
     * @throws ServerException
     */
    public static function create($ip, $port, $private_port, $user_id, $server_name, $max_players)
    {
        $max_players = (int)$max_players;

        try
        {
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
                "INSERT INTO `" . DB_PREFIX . "servers` (host_id, ip, port, private_port, name, max_players)
                VALUES (:host_id, :ip, :port, :private_port, :name, :max_players)",
                DBConnection::ROW_COUNT,
                [
                    ':host_id'      => $user_id,
                    ':ip'           => $ip, // do not use (int) or it truncates to 127.255.255.255
                    ':port'         => $port,
                    ':private_port' => $private_port,
                    ':name'         => $server_name,
                    ':max_players'  => $max_players
                ],
                [
                    ':host_id'      => DBConnection::PARAM_INT,
                    ':ip'           => DBConnection::PARAM_INT,
                    ':port'         => DBConnection::PARAM_INT,
                    ':private_port' => DBConnection::PARAM_INT,
                    ':max_players'  => DBConnection::PARAM_INT,
                ]
            );
        }
        catch(DBException $e)
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
        catch(DBException $e)
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
