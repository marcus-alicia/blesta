<?php
/**
 * TeamSpeak API.
 *
 * @package blesta
 * @subpackage blesta.components.modules.teamspeak
 * @copyright Copyright (c) 2018, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'TeamSpeak3.php';

class TeamspeakApi
{
    /**
     * @var string The server hostname
     */
    private $hostname;

    /**
     * @var int The server port
     */
    private $port;

    /**
     * @var string The TeamSpeak username
     */
    private $username;

    /**
     * @var string The TeamSpeak password
     */
    private $password;

    /**
     * Initializes the class
     *
     * @param string $hostname The TeamSpeak server hostname
     * @param string $username The TeamSpeak server username
     * @param string $password The TeamSpeak server password
     * @param int $port The TeamSpeak server port
     */
    public function __construct($hostname, $username, $password, $port = 10011)
    {
        $this->hostname = $hostname;
        $this->username = $username;
        $this->password = $password;
        $this->port = $port;
    }

    /**
     * Initializes the TeamSpeak 3 Framework
     *
     * @param string $adapter Specifies the adapter method to invoke
     * @param array $options The options of the request
     * @param string $flags The flags of the request
     * @return TeamSpeak3_Node_Host The TeamSpeak Framework instance
     */
    public function apiRequest($adapter, array $options = [], $flags = null)
    {
        // Build uri request
        $query = (!empty($options) ? '&' . http_build_query($options) : null) . (!empty($flags) ? '#' . $flags : null);
        $uri = $adapter . '://' . $this->username . ':' . $this->password
            . '@' . $this->hostname . ':' . $this->port . '/' . $query;

        // Initialize TeamSpeak 3 Framework
        return TeamSpeak3::factory($uri);
    }

    /**
     * Creates a new virtual server
     *
     * @param array $params An array contaning the following arguments:
     *  - name: Specifies the name for the virtual server
     *  - maxclients: Specifies the maximum number of listeners that may simultaneously tune in to this stream
     *  - port: The port of the virtual server
     *  - url: The host button url
     *  - tooltip: The host button tooltip (optional)
     * @return stdClass An object containing the request response
     */
    public function createServer($params)
    {
        try {
            // Build the parameters array
            $api_params = [
                'virtualserver_name' => isset($params['name']) ? $params['name'] : null,
                'virtualserver_maxclients' => isset($params['maxclients']) ? $params['maxclients'] : null,
                'virtualserver_port' => isset($params['port']) ? $params['port'] : null,
                'virtualserver_hostbutton_tooltip' => isset($params['tooltip']) ? $params['tooltip'] : null,
                'virtualserver_hostbutton_url' => isset($params['url']) ? $params['url'] : null
            ];

            // Create the virtual server
            $result = $this->apiRequest('serverquery')->serverCreate($api_params);

            // Add a control variable to know if the API request has been sent successfully
            if (empty($result['error'])) {
                $result['status'] = true;
            }

            // Convert the token to a string
            if (isset($result['token'])) {
                $result['token'] = (string) $result['token'];
            }
        } catch (Exception $e) {
            $result = [
                'error' => $e->getMessage(),
                'status' => false,
                'code' => $e->getCode()
            ];
        }

        return (object) $result;
    }

    /**
     * Edits an existing virtual server
     *
     * @param int $sid The virtual server ID
     * @param array $params An array contaning the following arguments:
     *  - name: Specifies the name for the virtual server
     *  - maxclients: Specifies the maximum number of listeners that may simultaneously tune in to this stream
     *  - port: The port of the virtual server
     *  - url: The host button url
     *  - tooltip: The host button tooltip (optional)
     * @return stdClass An object containing the request response
     */
    public function editServer($sid, $params)
    {
        try {
            // Build the parameters array
            $api_params = [
                'virtualserver_name' => isset($params['name']) ? $params['name'] : null,
                'virtualserver_maxclients' => isset($params['maxclients']) ? $params['maxclients'] : null,
                'virtualserver_port' => isset($params['port']) ? $params['port'] : null,
                'virtualserver_hostbutton_tooltip' => isset($params['tooltip']) ? $params['tooltip'] : null,
                'virtualserver_hostbutton_url' => isset($params['url']) ? $params['url'] : null
            ];

            // Fetch the virtual server instance, and modify it
            $instance = $this->apiRequest('serverquery')->serverList()[$sid];
            $result = $instance->modify($api_params);

            // Add a control variable to know if the API request has been sent successfully
            if (empty($result['error'])) {
                $result['status'] = true;
            }

            // Convert the token to a string
            if (isset($result['token'])) {
                $result['token'] = (string) $result['token'];
            }
        } catch (Exception $e) {
            $result = [
                'error' => $e->getMessage(),
                'status' => false,
                'code' => $e->getCode()
            ];
        }

        return (object) $result;
    }

    /**
     * Deletes an existing virtual server
     *
     * @param int $sid The virtual server ID
     * @return stdClass An object containing the request response
     */
    public function deleteServer($sid)
    {
        try {
            // Stop the virtual server
            try {
                $this->apiRequest('serverquery')->serverStop($sid);
            } catch (Exception $e) {
                // Throw the exception only if the code isn't equal to 1033
                $code = $e->getCode();
                if ($code == !1033) {
                    throw $e;
                }
            }

            // Delete the virtual server
            $this->apiRequest('serverquery')->serverDelete($sid);

            // Build the result
            $result = [
                'status' => true
            ];
        } catch (Exception $e) {
            $result = [
                'error' => $e->getMessage(),
                'status' => false,
                'code' => $e->getCode()
            ];
        }

        return (object) $result;
    }

    /**
     * Get the instance of a virtual server
     *
     * @param int $sid The virtual server ID
     * @return stdClass An object containing the server instance
     */
    public function getServer($sid)
    {
        try {
            // Get the virtual server
            $instance = $this->apiRequest('serverquery')->serverGetById($sid);

            // Build result
            $result = [];
            if (!empty($instance)) {
                $result['instance'] = $instance;
            }

            // Add a control variable to know if the API request has been sent successfully
            if (empty($result['error'])) {
                $result['status'] = true;
            }
        } catch (Exception $e) {
            $result = [
                'error' => $e->getMessage(),
                'status' => false,
                'code' => $e->getCode()
            ];
        }

        return (object) $result;
    }

    /**
     * List all the servers of the system
     *
     * @return stdClass An object containing a list of the servers
     */
    public function listServers()
    {
        try {
            $servers = $this->apiRequest('serverquery')->serverList();

            // Build result
            $result = [];
            if (!empty($servers)) {
                $result['servers'] = $servers;
            }

            // Add a control variable to know if the API request has been sent successfully
            if (empty($result['error'])) {
                $result['status'] = true;
            }
        } catch (Exception $e) {
            $result = [
                'error' => $e->getMessage(),
                'status' => false,
                'code' => $e->getCode()
            ];
        }

        return (object) $result;
    }

    /**
     * Starts the virtual server
     *
     * @param int $sid The virtual server ID
     * @return stdClass An object containing the response of the request
     */
    public function startServer($sid)
    {
        try {
            // Start the virtual server
            try {
                $result = $this->apiRequest('serverquery')->serverStart($sid);
            } catch (Exception $e) {
                // Throw the exception only if the code isn't equal to 1033
                $code = $e->getCode();
                if ($code == !1033) {
                    throw $e;
                }
            }

            // Add a control variable to know if the API request has been sent successfully
            if (empty($result['error'])) {
                $result['status'] = true;
            }
        } catch (Exception $e) {
            $result = [
                'error' => $e->getMessage(),
                'status' => false,
                'code' => $e->getCode()
            ];
        }

        return (object) $result;
    }

    /**
     * Restarts the virtual server
     *
     * @param int $sid The virtual server ID
     * @return stdClass An object containing the response of the request
     */
    public function restartServer($sid)
    {
        try {
            // Because TeamSpeak does not support the function of restarting a virtual
            // server, we will manually stop and restart the virtual server
            try {
                $this->apiRequest('serverquery')->serverStop($sid);
                $result = $this->apiRequest('serverquery')->serverStart($sid);
            } catch (Exception $e) {
                // Throw the exception only if the code isn't equal to 1033
                $code = $e->getCode();
                if ($code == !1033) {
                    throw $e;
                }
            }

            // Add a control variable to know if the API request has been sent successfully
            if (empty($result['error'])) {
                $result['status'] = true;
            }
        } catch (Exception $e) {
            $result = [
                'error' => $e->getMessage(),
                'status' => false,
                'code' => $e->getCode()
            ];
        }

        return (object) $result;
    }

    /**
     * Stop the virtual server
     *
     * @param int $sid The virtual server ID
     * @return stdClass An object containing the response of the request
     */
    public function stopServer($sid)
    {
        try {
            // Stop the virtual server
            try {
                $result = $this->apiRequest('serverquery')->serverStop($sid);
            } catch (Exception $e) {
                // Throw the exception only if the code isn't equal to 1033
                $code = $e->getCode();
                if ($code == !1033) {
                    throw $e;
                }
            }

            // Add a control variable to know if the API request has been sent successfully
            if (empty($result['error'])) {
                $result['status'] = true;
            }
        } catch (Exception $e) {
            $result = [
                'error' => $e->getMessage(),
                'status' => false,
                'code' => $e->getCode()
            ];
        }

        return (object) $result;
    }

    /**
     * Suspends an existing virtual server
     *
     * @param int $sid The virtual server ID
     * @return stdClass An object containing the request response
     */
    public function suspendServer($sid)
    {
        try {
            // Build the parameters array
            $api_params = [
                'virtualserver_autostart' => 0
            ];

            // Fetch the virtual server instance, and modify it
            $server = $this->getServer($sid);

            if (isset($server->instance)) {
                $result = $server->instance->modify($api_params);
            }

            // Stop the server instance
            $this->stopServer($sid);

            // Add a control variable to know if the API request has been sent successfully
            if (empty($result['error'])) {
                $result['status'] = true;
            }
        } catch (Exception $e) {
            $result = [
                'error' => $e->getMessage(),
                'status' => false,
                'code' => $e->getCode()
            ];
        }

        return (object) $result;
    }

    /**
     * Unsuspends an existing virtual server
     *
     * @param int $sid The virtual server ID
     * @return stdClass An object containing the request response
     */
    public function unsuspendServer($sid)
    {
        try {
            // Build the parameters array
            $api_params = [
                'virtualserver_autostart' => 1
            ];

            // Start the server first, before modify it
            $this->startServer($sid);

            // Fetch the virtual server instance, and modify it
            $server = $this->getServer($sid);

            if (isset($server->instance)) {
                $result = $server->instance->modify($api_params);
            }

            // Add a control variable to know if the API request has been sent successfully
            if (empty($result['error'])) {
                $result['status'] = true;
            }
        } catch (Exception $e) {
            $result = [
                'error' => $e->getMessage(),
                'status' => false,
                'code' => $e->getCode()
            ];
        }

        return (object) $result;
    }

    /**
     * Get the state of a virtual server
     *
     * @param int $sid The virtual server ID
     * @return stdClass An object containing the server state
     */
    public function getServerState($sid)
    {
        // Get the virtual server
        $server = $this->getServer($sid);

        // Check the state of the virtual server
        if (isset($server->error) && $server->code == !1033) {
            return $server;
        } elseif (isset($server->error) && $server->code === 1033 || !isset($server->instance)) {
            $state = false;
        } else {
            $state = $server->instance->isOnline();
        }

        // Build the result
        $result = [
            'state' => $state ? 'online' : 'offline',
            'status' => true
        ];

        return (object) $result;
    }

    /**
     * Get an existing server group
     *
     * @param int $sid The virtual server ID
     * @param int $sgid The server group ID
     * @return stdClass An object containing the server group
     */
    public function getServerGroup($sid, $sgid)
    {
        try {
            // Initialize the server instance
            $server = $this->getServer($sid);

            if (isset($server->instance) && $server->status) {
                $server = $server->instance;
            } else {
                throw new Exception($server->error, $server->code);
            }

            // Get the server group
            $instance = $server->serverGroupGetById($sgid);

            // Build result
            $client_info = $instance->getInfo();
            $result = [
                'sgid' => $client_info['sgid'],
                'name' => (string) $client_info['name'],
                'instance' => $instance,
                'status' => true
            ];
        } catch (Exception $e) {
            $result = [
                'error' => $e->getMessage(),
                'status' => false,
                'code' => $e->getCode()
            ];
        }

        return (object) $result;
    }

    /**
     * List all the server groups
     *
     * @param int $sid The virtual server ID
     * @return stdClass An object containing the server groups
     */
    public function listServerGroups($sid)
    {
        try {
            // Initialize the server instance
            $server = $this->getServer($sid);

            if (isset($server->instance) && $server->status) {
                $server = $server->instance;
            } else {
                throw new Exception($server->error, $server->code);
            }

            // Get all the available server groups
            $server_groups = $server->serverGroupList();

            // Parse the server groups response
            $options = [];
            foreach ($server_groups as $server_group) {
                $group_info = $server_group->getInfo();

                if ($group_info['type'] == 1) {
                    $options[] = (object) [
                        'sgid' => $server_group->getId(),
                        'name' => (string) $server_group,
                        'instance' => $server_group
                    ];
                }
            }

            // Build the result
            $result = [
                'server_groups' => $options,
                'status' => true
            ];
        } catch (Exception $e) {
            $result = [
                'error' => $e->getMessage(),
                'status' => false,
                'code' => $e->getCode()
            ];
        }

        return (object) $result;
    }

    /**
     * Creates a new token
     *
     * @param int $sid The virtual server ID
     * @param int $sgid The server group ID
     * @param string $description The token description
     * @return stdClass An object containing the new token
     */
    public function createPrivilegeKey($sid, $sgid, $description = null)
    {
        try {
            // Initialize the server instance
            $server = $this->getServer($sid);

            if (isset($server->instance) && $server->status) {
                $server = $server->instance;
            } else {
                throw new Exception($server->error, $server->code);
            }

            // Creates a new privilege key
            $token = $server->privilegeKeyCreate(TeamSpeak3::TOKEN_SERVERGROUP, $sgid, 0, $description);

            // Build the result
            $result = [
                'token' => (string) $token,
                'status' => true
            ];
        } catch (Exception $e) {
            $result = [
                'error' => $e->getMessage(),
                'status' => false,
                'code' => $e->getCode()
            ];
        }

        return (object) $result;
    }

    /**
     * Deletes an existing token
     *
     * @param int $sid The virtual server ID
     * @param string $token The token
     * @return stdClass An object containing the API response
     */
    public function deletePrivilegeKey($sid, $token)
    {
        try {
            // Initializate the server instance
            $server = $this->getServer($sid);

            if (isset($server->instance) && $server->status) {
                $server = $server->instance;
            } else {
                throw new Exception($server->error, $server->code);
            }

            // Delete the privilege key
            $server->privilegeKeyDelete($token);

            // Build the result
            $result = [
                'status' => true
            ];
        } catch (Exception $e) {
            $result = [
                'error' => $e->getMessage(),
                'status' => false,
                'code' => $e->getCode()
            ];
        }

        return (object) $result;
    }

    /**
     * List all the existing tokens
     *
     * @param int $sid The virtual server ID
     * @return stdClass An object containing the tokens
     */
    public function listPrivilegeKeys($sid)
    {
        try {
            // Initialize the server instance
            $server = $this->getServer($sid);

            if (isset($server->instance) && $server->status) {
                $server = $server->instance;
            } else {
                throw new Exception($server->error, $server->code);
            }

            // Get all the available privilege keys
            $privilege_keys = $server->privilegeKeyList();

            // Parse the privilege keys response
            $options = [];
            foreach ($privilege_keys as $privilege_key) {
                $options[] = (object) $privilege_key;
            }

            // Build the result
            $result = [
                'tokens' => $options,
                'status' => true
            ];
        } catch (Exception $e) {
            $result = [
                'error' => $e->getMessage(),
                'status' => false,
                'code' => $e->getCode()
            ];
        }

        return (object) $result;
    }

    /**
     * Kick client
     *
     * @param int $sid The virtual server ID
     * @param int $clid The client ID
     * @return stdClass An object containing the API response
     */
    public function kickClient($sid, $clid)
    {
        try {
            // Initialize the server instance
            $server = $this->getServer($sid);

            if (isset($server->instance) && $server->status) {
                $server = $server->instance;
            } else {
                throw new Exception($server->error, $server->code);
            }

            // Kick the client
            $server->clientKick($clid, TeamSpeak3::KICK_SERVER);

            // Build the result
            $result = [
                'status' => true
            ];
        } catch (Exception $e) {
            $result = [
                'error' => $e->getMessage(),
                'status' => false,
                'code' => $e->getCode()
            ];
        }

        return (object) $result;
    }

    /**
     * Get an existing client by their name
     *
     * @param int $sid The virtual server ID
     * @param string $name The client name
     * @return stdClass An object containing the requested client
     */
    public function getClientByName($sid, $name)
    {
        try {
            // Initialize the server instance
            $server = $this->getServer($sid);

            if (isset($server->instance) && $server->status) {
                $server = $server->instance;
            } else {
                throw new Exception($server->error, $server->code);
            }

            // Get the server group
            $instance = $server->clientGetByUid($name);

            // Build result
            $client_info = $instance->getInfo();
            $result = [
                'clid' => $client_info['clid'],
                'name' => (string) $client_info['client_unique_identifier'],
                'instance' => $instance,
                'status' => true
            ];
        } catch (Exception $e) {
            $result = [
                'error' => $e->getMessage(),
                'status' => false,
                'code' => $e->getCode()
            ];
        }

        return (object) $result;
    }

    /**
     * List all the active clients
     *
     * @param int $sid The virtual server ID
     * @return stdClass An object containing the active clients
     */
    public function listClients($sid)
    {
        try {
            // Initialize the server instance
            $server = $this->getServer($sid);

            if (isset($server->instance) && $server->status) {
                $server = $server->instance;
            } else {
                throw new Exception($server->error, $server->code);
            }

            // Get all the available clients
            $clients = $server->clientList();

            // Parse the server groups response
            $options = [];
            foreach ($clients as $client) {
                $client_info = $client->getInfo();

                if ($client_info['client_type'] == 0) {
                    $options[] = (object) [
                        'clid' => $client->getId(),
                        'name' => (string) $client_info['client_nickname'],
                        'instance' => $client
                    ];
                }
            }

            // Build the result
            $result = [
                'clients' => $options,
                'status' => true
            ];
        } catch (Exception $e) {
            $result = [
                'error' => $e->getMessage(),
                'status' => false,
                'code' => $e->getCode()
            ];
        }

        return (object) $result;
    }

    /**
     * Reset the virtual server permissions
     *
     * @param int $sid The virtual server ID
     * @return stdClass An object containing the API response
     */
    public function resetPermissions($sid)
    {
        try {
            // Initialize the server instance
            $server = $this->getServer($sid);

            if (isset($server->instance) && $server->status) {
                $server = $server->instance;
            } else {
                throw new Exception($server->error, $server->code);
            }

            // Reset the virtual server permissions
            $token = $server->permReset();

            // Build the result
            $result = [
                'token' => (string) $token,
                'status' => true
            ];
        } catch (Exception $e) {
            $result = [
                'error' => $e->getMessage(),
                'status' => false,
                'code' => $e->getCode()
            ];
        }

        return (object) $result;
    }

    /**
     * Get the virtual server logs
     *
     * @param int $sid The virtual server ID
     * @param int $lines The maximum amount of lines to retreive.
     * @return stdClass An object containing the API response
     */
    public function getLog($sid, $lines = 30)
    {
        try {
            // Initialize the server instance
            $server = $this->getServer($sid);

            if (isset($server->instance) && $server->status) {
                $server = $server->instance;
            } else {
                throw new Exception($server->error, $server->code);
            }

            // Get the server log
            $log_lines = $server->logView($lines);

            // Parse log lines response
            $log = [];
            foreach ($log_lines as $log_line) {
                // Convert the log line in to an object
                $log_line = (string) end($log_line);
                $log_elements = explode('|', $log_line, 5);

                $log[] = (object) [
                    'date' => trim($log_elements[0]),
                    'type' => trim($log_elements[1]),
                    'function' => trim($log_elements[2]),
                    'server_id' => trim($log_elements[3]),
                    'description' => trim($log_elements[4])
                ];
            }

            // Build the result
            $result = [
                'log' => $log,
                'status' => true
            ];
        } catch (Exception $e) {
            $result = [
                'error' => $e->getMessage(),
                'status' => false,
                'code' => $e->getCode()
            ];
        }

        return (object) $result;
    }

    /**
     * Add a ban rule
     *
     * @param int $sid The virtual server ID
     * @param string $ip_address The IP address to ban
     * @param string $reason The reason of the ban
     * @return stdClass An object containing the API response
     */
    public function addBan($sid, $ip_address, $reason = null)
    {
        try {
            // Initialize the server instance
            $server = $this->getServer($sid);

            if (isset($server->instance) && $server->status) {
                $server = $server->instance;
            } else {
                throw new Exception($server->error, $server->code);
            }

            // Ban the ip address
            $banid = $server->banCreate(['ip' => $ip_address], null, $reason);

            // Build the result
            $result = [
                'banid' => $banid,
                'status' => true
            ];
        } catch (Exception $e) {
            $result = [
                'error' => $e->getMessage(),
                'status' => false,
                'code' => $e->getCode()
            ];
        }

        return (object) $result;
    }

    /**
     * Delete a ban rule
     *
     * @param int $sid The virtual server ID
     * @param int $banid The ban rule ID
     * @return stdClass An object containing the API response
     */
    public function deleteBan($sid, $banid)
    {
        try {
            // Initialize the server instance
            $server = $this->getServer($sid);

            if (isset($server->instance) && $server->status) {
                $server = $server->instance;
            } else {
                throw new Exception($server->error, $server->code);
            }

            // Delete the ban rule
            $server->banDelete($banid);

            // Build the result
            $result = [
                'status' => true
            ];
        } catch (Exception $e) {
            $result = [
                'error' => $e->getMessage(),
                'status' => false,
                'code' => $e->getCode()
            ];
        }

        return (object) $result;
    }

    /**
     * Delete all the ban rules
     *
     * @param int $sid The virtual server ID
     * @return stdClass An object containing the API response
     */
    public function deleteAllBans($sid)
    {
        try {
            // Initialize the server instance
            $server = $this->getServer($sid);

            if (isset($server->instance) && $server->status) {
                $server = $server->instance;
            } else {
                throw new Exception($server->error, $server->code);
            }

            // Reset the virtual server permissions
            $server->banListClear();

            // Build the result
            $result = [
                'status' => true
            ];
        } catch (Exception $e) {
            $result = [
                'error' => $e->getMessage(),
                'status' => false,
                'code' => $e->getCode()
            ];
        }

        return (object) $result;
    }

    /**
     * List all the active ban rules
     *
     * @param int $sid The virtual server ID
     * @return stdClass An object containing the ban rules
     */
    public function listBans($sid)
    {
        try {
            // Initialize the server instance
            $server = $this->getServer($sid);

            if (isset($server->instance) && $server->status) {
                $server = $server->instance;
            } else {
                throw new Exception($server->error, $server->code);
            }

            // Get all the active bans
            $bans = $server->banList();

            // Parse the server groups response
            $options = [];
            foreach ($bans as $ban) {
                $options[] = (object) $ban;
            }

            // Build the result
            $result = [
                'bans' => $options,
                'status' => true
            ];
        } catch (Exception $e) {
            $result = [
                'error' => $e->getMessage(),
                'status' => false,
                'code' => $e->getCode()
            ];
        }

        return (object) $result;
    }
}
