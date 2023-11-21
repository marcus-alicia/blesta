<?php
use Blesta\Core\Util\Common\Traits\Container;

/**
 *
 *   Copyright © 2010-2015 by xhost.ch GmbH
 *
 *   All rights reserved.
 *
 **/

/**
 * Sample Multicraft API implementation.
 *
 * For examples and function reference, please see:
 * http://www.multicraft.org/site/page?view=api-doc
 *
 **/
class MulticraftApi
{
    // Load traits
    use Container;

    /**
     * @var string
     */
    private $key = '';

    /**
     * @var string
     */
    private $url = '';

    /**
     * @var string
     */
    private $lastResponse = '';

    /**
     * @var array
     */
    private $methods = [
        //User functions
        'listUsers' => [],
        'findUsers' => [['name' => 'field', 'type' => 'array'], ['name' => 'value', 'type' => 'array']],
        'getUser' => ['id'],
        'getCurrentUser' => [],
        'updateUser' => ['id', ['name' => 'field', 'type' => 'array'], ['name' => 'value', 'type' => 'array'], ['name' => 'send_mail', 'default' => 0]],
        'createUser' => ['name', 'email', 'password', ['name' => 'lang', 'default' => ''], ['name' => 'send_mail', 'default' => 0]],
        'deleteUser' => ['id'],
        'getUserRole' => ['user_id', 'server_id'],
        'setUserRole' => ['user_id', 'server_id', 'role'],
        'getUserFtpAccess' => ['user_id', 'server_id'],
        'setUserFtpAccess' => ['user_id', 'server_id', 'mode'],
        'getUserId' => ['name'],
        'validateUser' => ['name', 'password'],
        'generateUserApiKey' => ['user_id'],
        'getUserApiKey' => ['user_id'],
        'removeUserApiKey' => ['user_id'],
        //Player functions
        'listPlayers' => ['server_id'],
        'findPlayers' => ['server_id', ['name' => 'field', 'type' => 'array'], ['name' => 'value', 'type' => 'array']],
        'getPlayer' => ['id'],
        'updatePlayer' => ['id', ['name' => 'field', 'type' => 'array'], ['name' => 'value', 'type' => 'array']],
        'createPlayer' => ['server_id', 'name'],
        'deletePlayer' => ['id'],
        'assignPlayerToUser' => ['player_id', 'user_id'],
        //Command functions
        'listCommands' => ['server_id'],
        'findCommands' => ['server_id', ['name' => 'field', 'type' => 'array'], ['name' => 'value', 'type' => 'array']],
        'getCommand' => ['id'],
        'updateCommand' => ['id', ['name' => 'field', 'type' => 'array'], ['name' => 'value', 'type' => 'array']],
        'createCommand' => ['server_id', 'name', 'role', 'chat', 'response', 'run'],
        'deleteCommand' => ['id'],
        //Server functions
        'listServers' => [],
        'findServers' => [['name' => 'field', 'type' => 'array'], ['name' => 'value', 'type' => 'array']],
        'listServersByConnection' => ['connection_id'],
        'listServersByOwner' => ['user_id'],
        'getServer' => ['id'],
        'updateServer' => ['id', ['name' => 'field', 'type' => 'array'], ['name' => 'value', 'type' => 'array']],
        'createServerOn' => [['name' => 'daemon_id', 'default' => 0], ['name' => 'no_commands', 'default' => 0], ['name' => 'no_setup_script', 'default' => 0]],
        'createServer' => [['name' => 'name', 'default' => ''], ['name' => 'port', 'default' => 0], ['name' => 'base', 'default' => ''], ['name' => 'players', 'default' => 0], ['name' => 'no_commands', 'default' => 0], ['name' => 'no_setup_script', 'default' => 0]],
        'suspendServer' => ['id', ['name' => 'stop', 'default' => 1]],
        'resumeServer' => ['id', ['name' => 'start', 'default' => 1]],
        'deleteServer' => ['id', ['name' => 'delete_dir', 'default' => 'no'], ['name' => 'delete_user', 'default' => 'no']],
        'getServerStatus' => ['id', ['name' => 'player_list', 'default' => 0]],
        'getServerOwner' => ['server_id'],
        'setServerOwner' => ['server_id', 'user_id', ['name' => 'send_mail', 'default' => 0]],
        'getServerConfig' => ['id'],
        'updateServerConfig' => ['id', ['name' => 'field', 'type' => 'array'], ['name' => 'value', 'type' => 'array']],
        'startServerBackup' => ['id'],
        'getServerBackupStatus' => ['id'],
        'startServer' => ['id'],
        'stopServer' => ['id'],
        'restartServer' => ['id'],
        'killServer' => ['id'],
        'startAllServers' => [],
        'stopAllServers' => [],
        'restartAllServers' => [],
        'killAllServers' => [],
        'sendConsoleCommand' => ['server_id', 'command'],
        'sendAllConsoleCommand' => ['command'],
        'runCommand' => ['server_id', 'command_id', ['name' => 'run_for', 'default' => 0]],
        'getServerLog' => ['id'],
        'clearServerLog' => ['id'],
        'getServerChat' => ['id'],
        'clearServerChat' => ['id'],
        'sendServerControl' => ['id', 'command'],
        'getServerResources' => ['id'],
        'moveServer' => ['server_id', 'daemon_id'],
        //Daemon functions
        'listConnections' => [],
        'findConnections' => [['name' => 'field', 'type' => 'array'], ['name' => 'value', 'type' => 'array']],
        'getConnection' => ['id'],
        'removeConnection' => ['id'],
        'getConnectionStatus' => ['id'],
        'getConnectionMemory' => ['id', ['name' => 'include_suspended', 'default' => 0]],
        'getStatistics' => [['name' => 'daemon_id', 'default' => 0], ['name' => 'include_suspended', 'default' => 0]],
        //Settings functions
        'listSettings' => [],
        'getSetting' => ['key'],
        'setSetting' => ['key', 'value'],
        'deleteSetting' => ['key'],
        //Schedule functions
        'listSchedules' => ['server_id'],
        'findSchedules' => ['server_id', ['name' => 'field', 'type' => 'array'], ['name' => 'value', 'type' => 'array']],
        'getSchedule' => ['id'],
        'updateSchedule' => ['id', ['name' => 'field', 'type' => 'array'], ['name' => 'value', 'type' => 'array']],
        'createSchedule' => ['server_id', 'name', 'ts', 'interval', 'cmd', 'status', 'for'],
        'deleteSchedule' => ['id'],
        //Database functions
        'getDatabaseInfo' => ['server_id'],
        'createDatabase' => ['server_id'],
        'changeDatabasePassword' => ['server_id'],
        'deleteDatabase' => ['server_id'],
    ];

    /**
     * MulticraftApi constructor.
     *
     * @param $url
     * @param $user
     * @param $key
     */
    public function __construct($url, $user, $key)
    {
        $this->url = $url;
        $this->user = $user;
        $this->key = $key;

        // Initialize logger
        $logger = $this->getFromContainer('logger');
        $this->logger = $logger;
    }

    /**
     * @param $function
     * @param $args
     * @return array|mixed
     */
    public function __call($function, $args)
    {
        $argnames = @$this->methods[$function];
        if (!is_array($argnames)) {
            return ['success' => false, 'errors' => ['Unknown API method "' . $function . '()"'], 'data' => []];
        }
        $callargs = [];
        $name = '';
        $value = '';
        for ($i = 0; $i < count($argnames); $i++) {
            if (is_array($argnames[$i])) {
                $name = $argnames[$i]['name'];
            } else {
                $name = $argnames[$i];
            }

            if ($i < count($args)) {
                $value = $args[$i];
            } else if (is_array($argnames[$i]) && isset($argnames[$i]['default'])) {
                if ($i >= count($args)) {
                    $value = $argnames[$i]['default'];
                } else {
                    $value = $args[$i];
                }
            } else {
                return ['success' => false, 'errors' => ['"' . $function . '()": Not enough arguments (' . count($args) . ')'], 'data' => []];
            }

            if (is_array($argnames[$i]) && isset($argnames[$i]['type'])) {
                if ($argnames[$i]['type'] == 'array') {
                    $value = json_encode($value);
                }
            }
            $callargs[$name] = $value;
        }

        return $this->call($function, $callargs);
    }

    /**
     * @param $method
     * @param array $params
     * @return array|mixed
     */
    public function call($method, $params = [])
    {
        if (!$this->url) {
            return ['success' => false, 'errors' => ['Invalid target URL']];
        }
        if (!$this->key) {
            return ['success' => false, 'errors' => ['Invalid API key']];
        }

        $url = $this->url;
        $query = '';
        $str = '';
        if (!is_array($params)) {
            $params = [$params => $params];
        }
        $params['_MulticraftAPIMethod'] = $method;
        $params['_MulticraftAPIUser'] = $this->user;
        foreach ($params as $k => $v) {
            $str .= $k . $v;
            $query .= '&' . urlencode($k) . '=' . urlencode($v);
        }
        $ret = $this->send($url, $query . '&_MulticraftAPIKey=' . hash_hmac('sha256', $str, $this->key));
        if (@$ret['errors'][0] == 'Invalid API key.') // This is an old panel, use MD5 method instead
        {
            $ret = $this->send($url, $query . '&_MulticraftAPIKey=' . md5($this->key . implode($params)));
        }

        return $ret;
    }

    /**
     * @param $url
     * @param $query
     * @return array|mixed
     */
    public function send($url, $query)
    {
        $response = '';
        $error = '';

        if (function_exists('curl_init')) {
            $curl = curl_init($url);

            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $query);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

            if (Configure::get('Blesta.curl_verify_ssl')) {
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
            } else {
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            }

            $response = curl_exec($curl);
            $error = curl_error($curl);

            if ($response == false) {
                $this->logger->error(curl_error($curl));
            }

            curl_close($curl);
        } else {
            $response = file_get_contents($url . '?' . ltrim($query, '&'));
        }

        if (!$response) {
            if (!$error) {
                $error = 'Empty response (wrong API URL or connection problem)';
            }

            return ['success' => false, 'errors' => [$error], 'data' => ''];
        }

        $this->lastResponse = $response;
        $ret = json_decode($response, true);

        if (!is_array($ret)) {
            return ['success' => false, 'errors' => [$ret], 'data' => []];
        }

        return $ret;
    }

    /**
     * @return string
     */
    public function rawResponse()
    {
        return $this->lastResponse;
    }
}
