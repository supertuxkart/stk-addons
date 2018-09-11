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
     * @var int
     */
    private $game_started;

    /**
     * Latitude of IP in float of server (0.0 if not in database)
     * @var float
     */
    private $latitude;

    /**
     * Longitude of IP in float of server (0.0 if not in database)
     * @var float
     */
    private $longitude;

    /**
     *
     * List of players in server with name and connected since time
     * @var array
     */
    private $player_info;

    /**
     *
     * @param array $data An associative array retrieved from the database
     * @param array $pi Player list in server if exists
     */
    private function __construct(array $data, array $pi = [])
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
        $this->game_started = (int)$data["game_started"];
        $this->latitude = $data["latitude"];
        $this->longitude = $data["longitude"];
        $this->player_info = $pi;
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
     * @return array of latitude and longitude.
     *         If location does not exist it returns coordinates [0, 0] (null island)
     *
     * @param int $ip
     */
    public static function getIPCoordinates($ip)
    {
        try
        {
            $result = DBConnection::get()->query(
                "SELECT * FROM `{DB_VERSION}_ipv4_mapping`
                WHERE `ip_start` <= :ip AND `ip_end` >= :ip ORDER BY `ip_start` DESC LIMIT 1;",
                DBConnection::FETCH_FIRST,
                [':ip' => $ip],
                [":ip" => DBConnection::PARAM_INT]
            );
        }
        catch (DBException $e)
        {
            Debug::addException($e);
            return [0.0, 0.0];
        }

        if (!$result)
        {
            return [0.0, 0.0];
        }

        return [$result["latitude"], $result["longitude"]];
    }

    /**
     * Get the latitude and longitude of an IP represented as a string
     * @return float[] of latitude and longitude in string.
     *         If location does not exist it returns coordinates [0, 0] (null island)
     *
     * @param string $ip_string eg: 127.0.0.1
     */
    public static function getIPCoordinatesFromString($ip_string)
    {
        try
        {
            $result = DBConnection::get()->query(
                "SELECT * FROM `{DB_VERSION}_ipv4_mapping`
                WHERE `ip_start` <= INET_ATON(:ip) AND `ip_end` >= INET_ATON(:ip)
                ORDER BY `ip_start` DESC LIMIT 1;",
                DBConnection::FETCH_FIRST,
                [':ip' => $ip_string],
                [":ip" => DBConnection::PARAM_STR]
            );
        }
        catch (DBException $e)
        {
            Debug::addException($e);
            return [0.0, 0.0];
        }

        if (!$result)
        {
            return [0.0, 0.0];
        }

        return [$result["latitude"], $result["longitude"]];
    }

    /**
     * Get server as xml output
     *
     * @return string
     */
    public function asXML()
    {
        $client_coordinates = Server::getIPCoordinatesFromString(Util::getClientIp());
        return $this->asXMLFromClientLocation($client_coordinates[0], $client_coordinates[1]);
    }

    /**
     * Version of asXML so that we can cache the client location.
     *
     * @param float $client_latitude
     * @param float $client_longitude
     *
     * @return string
     */
    public function asXMLFromClientLocation($client_latitude, $client_longitude)
    {
        $server_xml = new XMLOutput();
        $server_xml->startElement('server');
            $server_xml->startElement('server-info');
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
                $server_xml->writeAttribute("game_started", $this->game_started);
                $server_xml->writeAttribute(
                    "distance",
                    Util::getDistance($client_latitude, $client_longitude, $this->latitude, $this->longitude)
                );
                $user = User::getFromID($this->host_id);
                $permission = AccessControl::getPermissions($user->getRole());
                $server_xml->writeAttribute("official", in_array(AccessControl::PERM_OFFICIAL_SERVERS, $permission));
            $server_xml->endElement();
            $server_xml->startElement('players');
                foreach ($this->player_info as $pi)
                {
                    $server_xml->startElement('player-info');
                    $server_xml->writeAttribute("user-id", $pi["user_id"]);
                    $server_xml->writeAttribute("username", $pi["username"]);
                    $server_xml->writeAttribute("connected-since", $pi["connected_since"]);
                    $time_played = (float)(time() - (int)$pi["connected_since"]) / 60.0;
                    $server_xml->writeAttribute("time-played", $time_played);
                    if ($pi["rank"] !== null)
                    {
                        $server_xml->writeAttribute("rank", $pi["rank"]);
                        $server_xml->writeAttribute("scores", $pi["scores"]);
                        $server_xml->writeAttribute("max-scores", $pi["max_scores"]);
                        $server_xml->writeAttribute("num-races-done", $pi["num_races_done"]);
                    }
                    $server_xml->endElement();
                }
            $server_xml->endElement();
        $server_xml->endElement();

        return $server_xml->asString();
    }

    /**
     * Cleans all old servers
     * @throws DBException
     */
    public static function cleanOldServers()
    {
        // Clean non-polled servers < 15 seconds before
        $timeout = time() - 15;
        DBConnection::get()->query(
            "DELETE FROM `{DB_VERSION}_servers`
                WHERE `last_poll_time` < :time",
            DBConnection::NOTHING,
            [':time' => $timeout],
            [':time' => DBConnection::PARAM_INT]
        );
    }

    /**
     * Create server
     *
     * @param int    $ip
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
    public static function create(
        $ip,
        int $port,
        int $private_port,
        int $user_id,
        $server_name,
        int $max_players,
        int $difficulty,
        int $game_mode,
        int $password,
        int $version
    ) {
        try
        {
            static::cleanOldServers();
            $count = DBConnection::get()->query(
                "SELECT `id` FROM `{DB_VERSION}_servers` WHERE `ip`= :ip AND `port`= :port ",
                DBConnection::ROW_COUNT,
                [':ip' => $ip, ':port' => $port]
            );
            if ($count)
            {
                throw new ServerException(_('Specified server already exists.'));
            }

            $server_coordinates = Server::getIPCoordinates($ip);
            $result = DBConnection::get()->query(
                "INSERT INTO `{DB_VERSION}_servers` (host_id, name,
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
     * Stop a server by deleting it from the database
     *
     * @param int $ip   the server ip
     * @param int $port the server port
     * @param int $host_id the server owner
     *
     * @throws ServerException
     */
    public static function stop($ip, int $port, int $host_id)
    {
        try
        {
            // now setup the serv info
            $count = DBConnection::get()->query(
                "DELETE FROM `{DB_VERSION}_servers`
                WHERE `ip`= :ip AND `port`= :port AND `host_id`= :id",
                DBConnection::ROW_COUNT,
                [
                    ':ip'   => $ip,
                    ':port' => $port,
                    ':id'   => $host_id
                ],
                [
                    ':ip'   => DBConnection::PARAM_INT,
                    ':port' => DBConnection::PARAM_INT,
                    ':id'   => DBConnection::PARAM_INT
                ]
            );
            static::cleanOldServers();
        }
        catch (DBException $e)
        {
            throw new ServerException(exception_message_db(_('stop a server')));
        }

        if ($count !== 1)
        {
            throw new ServerException(_h('Not the good number of servers deleted.'));
        }
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
                "SELECT * FROM `{DB_VERSION}_servers`
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
     * @throws ServerException
     * @return string
     */
    public static function getServersAsXML()
    {
        $servers_with_users = [];
        try
        {
            static::cleanOldServers();
            $servers_with_users = DBConnection::get()->query(
                "SELECT `{DB_VERSION}_servers`.id, `{DB_VERSION}_servers`.host_id, `{DB_VERSION}_servers`.name,
                `{DB_VERSION}_servers`.ip, `{DB_VERSION}_servers`.port, `{DB_VERSION}_servers`.private_port,
                `{DB_VERSION}_servers`.max_players, `{DB_VERSION}_servers`.difficulty, `{DB_VERSION}_servers`.game_mode,
                `{DB_VERSION}_servers`.current_players, `{DB_VERSION}_servers`.password, `{DB_VERSION}_servers`.version,
                `{DB_VERSION}_servers`.game_started, `{DB_VERSION}_servers`.latitude, `{DB_VERSION}_servers`.longitude,
                `{DB_VERSION}_server_conn`.user_id, `{DB_VERSION}_server_conn`.connected_since,
                `{DB_VERSION}_users`.username,
                `{DB_VERSION}_rankings`.scores, `{DB_VERSION}_rankings`.max_scores, `{DB_VERSION}_rankings`.num_races_done,
                FIND_IN_SET(scores, (SELECT GROUP_CONCAT(DISTINCT scores ORDER BY scores DESC)
                FROM `{DB_VERSION}_rankings`)) AS rank
                FROM `{DB_VERSION}_servers`
                LEFT JOIN `{DB_VERSION}_server_conn` ON `{DB_VERSION}_servers`.id = `{DB_VERSION}_server_conn`.server_id
                LEFT JOIN `{DB_VERSION}_users` ON `{DB_VERSION}_users`.id = `{DB_VERSION}_server_conn`.user_id
                LEFT JOIN `{DB_VERSION}_rankings` ON `{DB_VERSION}_rankings`.user_id = `{DB_VERSION}_server_conn`.user_id
                ORDER BY id",
                DBConnection::FETCH_ALL
            );
        }
        catch (DBException $e)
        {
            throw new ServerException($e);
        }

        $client_coordinates = Server::getIPCoordinatesFromString(Util::getClientIp());
        $servers = [];
        $users = [[]];
        $current_id = -1;
        $current_user_id = -1;
        foreach ($servers_with_users as $server_user)
        {
            if ($current_id !== (int)$server_user["id"])
            {
                $current_id = (int)$server_user["id"];
                $current_user_id++;
                $users[] = [];
                $servers[] = $server_user;
            }
            if ($server_user["username"] !== null && $server_user["connected_since"] !== null)
                $users[$current_user_id][] = $server_user;
        }

        // build xml
        $partial_output = new XMLOutput();
        $partial_output->startElement('servers');
        $user_index = 0;
        foreach ($servers as $server_result)
        {
            $server = new self($server_result, $users[$user_index]);
            $user_index++;
            $partial_output->insert($server->asXMLFromClientLocation($client_coordinates[0], $client_coordinates[1]));
        }
        $partial_output->endElement();

        return $partial_output->asString();
    }
}
