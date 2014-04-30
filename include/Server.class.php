<?php
/**
 * copyright 2013 Glenn De Jonghe
 *
 * This file is part of SuperTuxKart
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

require_once(INCLUDE_DIR . 'exceptions.php');
require_once(INCLUDE_DIR . 'DBConnection.class.php');
require_once(INCLUDE_DIR . 'XMLOutput.class.php');


class ServerException extends Exception {}

/**
 * Server class
 */
class Server
{
    protected $server_id;
    protected $host_id;
    protected $server_name;
    protected $max_players;

    /**
     * 
     * @param array $info_array an associative array based on the database
     */
    protected function __construct($info_array)
    {
		$this->info_array = $info_array;
    }
    
    public function getServerId()
    {
        return $this->info_array['id'];
    }
    
    public function getHostId()
    {
        return $this->info_array['hostid'];
    }
    
    public function getName()
    {
        return $this->info_array['name'];
    }
    
    public function getMaxPlayers()
    {
        return $this->info_array['max_players'];
    }
    
    public function asXML()
    {
    	$server_xml = new XMLOutput();
	    $server_xml->startElement('server');
	    foreach ($this->info_array as $key => $value)
	    	$server_xml->writeAttribute($key, $value);
	    $server_xml->endElement();
	    return $server_xml->asString();
    }
    
    /**
     * Create server
     * @param 
     * @param 
     * @return Server
     * @throws 
     */
    public static function create(  $ip,
                                    $port,
                                    $private_port,
                                    $userid,
                                    $server_name,
                                    $max_players)
    {
        $max_players = (int) $max_players;
        try{
            $count = DBConnection::get()->query
            (
                "SELECT `id` FROM `" . DB_PREFIX . "servers`
                    WHERE `ip`= :ip AND `port`= :port ",
                DBConnection::ROW_COUNT,
                array
                (
                    ':ip'   => $ip,
                    ':port' => $port
                )
            );
            if ($count != 0)
                throw new ServerException(_('Specified server already exists.'));
            $result = DBConnection::get()->query
            (
                "INSERT INTO `" . DB_PREFIX ."servers` (hostid, ip, port, private_port, name, max_players)
                VALUES (:hostid, :ip, :port, :private_port, :name, :max_players)",
                DBConnection::ROW_COUNT,
                array
                (
                    ':hostid'       => (int) $userid,
                    ':ip'           =>       $ip, // do not use (int) or it truncates to 127.255.255.255
                    ':port'         => (int) $port,
                    ':private_port' => (int) $private_port,
                    ':name'         => (string) $server_name,
                    ':max_players'  => (int)    $max_players
                )
            );
            if ($result != 1) {
                throw new ServerException(_('Could not create server'));
            }
            return Server::getServer(DBConnection::get()->lastInsertId());

        }catch(PDOExpcetion $e){
            throw new ServerException(
                _('An error occurred while creating server.') .' '.
                _('Please contact a website administrator.')
            );
        }
    }
    
    public static function getServer($id)
    {
    	try{
    		$result = DBConnection::get()->query
    		(
    				"SELECT * FROM `" . DB_PREFIX . "servers`
                    WHERE `id`= :id",
    				DBConnection::FETCH_ALL,
    				array
    				(
    						':id'   => (int) $id
    				)
    		);
    		if (count($result) == 0) 
    		{
    			throw new ServerException(_("Server doesn't exist."));
    		}else if (count($result) > 1)
    		{
    			throw new PDOException();
    		}
    		return new Server($result[0]);   		
    	}catch(PDOExpcetion $e){
    		throw new ServerException(
    				_('An error occurred while creating server.') .' '.
    				_('Please contact a website administrator.')
    		);
    	}
    }
    
    public static function getServersAsXML()
    {
        $servers = DBConnection::get()->query
        (
            "SELECT *
            FROM `" . DB_PREFIX ."servers`",
            DBConnection::FETCH_ALL
        );
        $partial_output = new XMLOutput();
        $partial_output->startElement('servers');
        foreach ($servers as $server_result)
        {
        	$server = new Server($server_result);
            $partial_output->insert($server->asXML());
        }
        $partial_output->endElement();
        return $partial_output->asString();
    }
}
