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
class Server implements IAsXML
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
     * The server's difficulty
     * @var int
     */
    private $difficulty;

    /**
     * The server's game mode
     * @var int
     */
    private $game_mode;

    /**
     * @var int
     */
    private $current_players;

    /**
     * @var int
     */
    private $password;

    /**
     * @var int
     */
    private $version;

    /**
     * @var array
     * Latitude of IP in float of server (null if not in database)
     */
    private $latitude;

    /**
     * @var array
     * Longitude of IP in float of server (null if not in database)
     */
    private $longitude;

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
        $this->ip = $data["ip"];
        $this->port = (int)$data["port"];
        $this->private_port = (int)$data["private_port"];
        $this->difficulty = (int)$data["difficulty"];
        $this->game_mode = (int)$data["game_mode"];
        $this->current_players = (int)$data["current_players"];
        $this->password = (int)$data["password"];
        $this->version = (int)$data["version"];
        $this->latitude = $data["latitude"];
        $this->longitude = $data["longitude"];
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
     * Get the latitude and longitude of an IP
     * @return array of latitude and longitude in float, or null if not in database
     */
    public static function getIPCoordinates($ip)
    {
        try
        {
            $result = DBConnection::get()->query(
                "SELECT * FROM `" . DB_PREFIX . "ip_mapping`
                WHERE `ip_start` <= :ip AND `ip_end` >= :ip ORDER BY `ip_start` DESC LIMIT 1;",
                DBConnection::FETCH_FIRST,
                [':ip' => $ip],
                [":ip" => DBConnection::PARAM_INT]
            );
        }
        catch (DBException $e)
        {
            throw new ServerException(exception_message_db(_('finding ip coordinates')));
        }

        if (!$result)
        {
            return [NULL, NULL];
        }
        return [$result["latitude"], $result["longitude"]];
    }

    /**
     * Get distance between two coordinates using Haversine formula:
     * https://en.wikipedia.org/wiki/Haversine_formula
     * Notice: Haversine formula does not take into account that earth is a
     * spheroid (not a perfect sphere) so it has some small inaccuracies.
     * if any coordinates is NULL return -1.0
     * @return float
     */
    public static function getDistance($lat_from_degree, $lon_from_degree,
                                       $lat_to_degree, $lon_to_degree,
                                       $earth_radius = 6371.0)
    {
        if (is_null($lat_from_degree) || is_null($lon_from_degree) ||
            is_null($lat_to_degree) || is_null($lon_to_degree))
            return -1.0;
        $lat_from = deg2rad($lat_from_degree);
        $lon_from = deg2rad($lon_from_degree);
        $lat_to = deg2rad($lat_to_degree);
        $lon_to = deg2rad($lon_to_degree);
        $lat_delta = $lat_to - $lat_from;
        $lon_delta = $lon_to - $lon_from;
        $angle = 2 * asin(sqrt(pow(sin($lat_delta / 2), 2) +
            cos($lat_from) * cos($lat_to) * pow(sin($lon_delta / 2), 2)));
        return $angle * $earth_radius;
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
        $server_xml->writeAttribute("host_id", $this->host_id);
        $server_xml->writeAttribute("name", $this->name);
        $server_xml->writeAttribute("max_players", $this->max_players);
        $server_xml->writeAttribute("ip", $this->ip);
        $server_xml->writeAttribute("port", $this->port);
        $server_xml->writeAttribute("private_port", $this->private_port);
        $server_xml->writeAttribute("difficulty", $this->difficulty);
        $server_xml->writeAttribute("game_mode", $this->game_mode);
        $server_xml->writeAttribute("current_players", $this->current_players);
        $server_xml->writeAttribute("password", $this->password);
        $server_xml->writeAttribute("version", $this->version);
        $client_ip = ip2long(Util::getClientIp());
        $client_coordinates = Server::getIPCoordinates($client_ip);
        $server_xml->writeAttribute("distance",
            Server::getDistance($client_coordinates[0], $client_coordinates[1],
            $this->latitude, $this->longitude));
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

            $server_coordinates = Server::getIPCoordinates($ip);
            $result = DBConnection::get()->query(
                "INSERT INTO `" . DB_PREFIX . "servers` (host_id, name,
                last_poll_time, ip, port, private_port, max_players,
                difficulty, game_mode, password, version, latitude, longitude)
                VALUES (:host_id, :name, :last_poll_time, :ip, :port,
                :private_port, :max_players, :difficulty, :game_mode,
                :password, :version, :latitude, :longitude)",
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
                    ':version'        => $version,
                    ':latitude'       => $server_coordinates[0],
                    ':longitude'      => $server_coordinates[1]
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
                    ':version'        => DBConnection::PARAM_INT,
                    ':latitude'       => DBConnection::PARAM_STR,
                    ':longitude'      => DBConnection::PARAM_STR
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
