<?php
use Blesta\Core\Util\Common\Traits\Container;

/**
 * Tcadmin API
 *
 * @package blesta
 * @subpackage blesta.components.modules.tcadmin.tcadminapi
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Tcadminapi
{
    // Load traits
    use Container;

    private $user_name;
    private $password;
    private $host_name;
    private $port;
    private $use_ssl;

    /**
     * Initializes the class
     */
    public function __construct($user_name, $password, $host_name, $port, $use_ssl)
    {
        $this->user_name = $user_name;
        $this->password = $password;
        $this->host_name = $host_name;
        $this->port = $port;
        $this->use_ssl = $use_ssl;

        // Initialize logger
        $logger = $this->getFromContainer('logger');
        $this->logger = $logger;
    }

    /**
     * Return a string containing the last error for the current session
     * @param string $function the TCAdmin API Function to call
     * @param array $params the parameters to include in the API request
     * @return mixed string|Array the curl error message or an array representing the API response
     */
    private function apiRequest($function, array $params)
    {
        $curl = curl_init();

        $params["tcadmin_username"] = $this->user_name;
        $params["tcadmin_password"] = $this->password;
        $params["function"] = $function;
        $params["response_type"] = "xml";
        $params = http_build_query($params);
        $url = "";

        if ($this->use_ssl == "true") {
            $url .= "https://";
        } else if ($this->use_ssl == "false") {
            $url .= "http://";

        }
        $url .= $this->host_name . ":" . $this->port . "/billingapi.aspx";

        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Accept: application/xml", " Accept-Charset: UTF-8"));
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Expect:', 'Accept-Charset: UTF-8'));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        if (Configure::get('Blesta.curl_verify_ssl')) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        } else {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }

        $host_name_output = "<?xml version='1.0'?><document>";
        $host_name_output .= curl_exec($curl);
        $host_name_output .= "</document>";
        $error = curl_error($curl);

        if (!empty($error)) {
            $this->logger->error($error);
        }

        curl_close($curl);

        if ($host_name_output != false) {
            $response = json_decode(json_encode(simplexml_load_string($host_name_output)), true);

            return $response;
        }

        return $error;
    }

    /**
     * Creates a service
     * @param array $params an array of parameters, should include the following properties:
     * ---------------------
     * Billing Info:
     * ---------------------
     * - game_package_id: The billing id of the game package purchased by the client
     * - voice_package_id: The billing id of the voice package purchased by the client
     * - client_id: The client's billing id
     * ---------------------
     * User Info:
     * ---------------------
     * - user_name: If sent blank the user will be asked for a user name in the game server creation page.
     * If skip_page = 1 and this value is blank it will create a user name using the client's names.
     * If a user with the same billing id already exists this value will be ignored
     * - user_password: If left blank TCAdmin will generate a random password
     * - user_email: email address
     * - user_fname: first name
     * - user_lname: last name
     * - user_address1: address 1
     * - user_address2: address 2
     * - user_city: city
     * - user_state: state
     * - user_zip: zip
     * - user_country: country
     * - user_phone1: phone number 1
     * - user_phone2: phone number 2
     * ---------------------------------------------------------------
     * Game Settings (If the account to be created is a game account):
     * ---------------------------------------------------------------
     * - game_id: The game id that will be created. If sent blank a game server will not be created.
     * - game_slots: The number of slots that the game server will have. If sent blank a game server will not be created.
     * This value can also include the prefixes PRI and PUB to indicate a private or public game server.
     * For example PRI8 will create a private server with 8 slots.
     * - game_private: 1 if the game server is private. 0 if the game server is public. Default value is 0
     * - game_additional_slots: Additional slots for the game server. This value will be added to game_slots. Default value is 0.
     * - game_branded: 1 if the game server is branded. 0 if the game server is not branded. Default value is 0.
     * - game_start: 1 if the game server is started. 0 if the game server is not started. Default value is 1.
     * - game_priority: Options (AboveNormal, BelowNormal, Normal, High, Idle, RealTime). Set the process priority. Default value is Normal
     * - game_startup: Options (Automatic, Manual, Disabled), Set the service startup. Default value is Automatic.
     * - game_datacenter: The data center id where the game server will be created. Instead of game_datacenter you can specify a server id with game_server
     * - game_hostname: The host name of the game server. (Optional)
     * - game_rcon_password: The game server's rcon password. (Optional)
     * - game_private_password: The game server's private password if game_private = 1. (Optional)
     * ---------------------------------------------------------------
     * Voice Settings (If the account to be created is a voice account):
     * ---------------------------------------------------------------
     * - voice_id: The voice id that will be created. If sent blank a voice server will not be created.
     * Valid values are TEAMSPEAK2 for TS2 and TEAMSPEAK3 for TS3
     * - voice_slots: The number of slots that the voice server will have. If sent blank a voice server will not be created.
     * This value can also include the prefixes PRI and PUB to indicate a private or public game server.
     * For example PRI8 will create a private server with 8 slots.
     * - voice_private: 1 if the voice server is private. 0 if the voice server is public. Default value is 0.
     * - voice_additional_slots: Additional slots for the voice server. This value will be added to voice_slots. Default value is 0.
     * - voice_upload_quota: The upload quota for a TS3 server. Default value is 0.
     * - voice_download_quota: The download quota for a TS3 server. Default value is 0.
     * - voice_priority: Options (AboveNormal, BelowNormal, Normal, High, Idle, RealTime). Set the process priority. Default value is Normal
     * - voice_startup: Options (Automatic, Manual, Disabled), Set the service startup. Default value is Automatic.
     * - voice_datacenter: The datacenter id where the voice server will be created. If this value is blank it will take the value of game_datacenter.
     * Instead of voice_datacenter you can specify a server id with voice_server.
     * - voice_hostname: The host name of the voice server. (Optional)
     * - voice_rcon_password: The voice server's rcon password. (Optional)
     * - voice_private_password: The voice server's private password if voice_private = 1. (Optional)
     * @return mixed string|Array the curl error message or an array representing the API response
     */
    public function createAccount(array $params)
    {
        return $this->apiRequest("AddPendingSetup", $params);
    }

    /**
     * Starts a game/voice server
     * @param int $client_package_id The billing id of the service that will be started
     * @return mixed string|Array the curl error message or an array representing the API response
     */

    public function startServer($client_package_id)
    {
        return $this->apiRequest("StartByBillingId", array("client_package_id" => $client_package_id));
    }

    /**
     * Stops a game/voice server
     * @param int $client_package_id The billing id of the service that will be stopped
     * @return mixed string|Array the curl error message or an array representing the API response
     */

    public function stopServer($client_package_id)
    {
        return $this->apiRequest("StopByBillingId", array("client_package_id" => $client_package_id));
    }

    /**
     * Restarts a game/voice server
     * @param int $client_package_id The billing id of the service that will be restarted
     * @return mixed string|Array the curl error message or an array representing the API response
     */

    public function restartServer($client_package_id)
    {
        return $this->apiRequest("RestartByBillingId", array("client_package_id" => $client_package_id));
    }

    /**
     * Suspends a game/voice server
     * @param int $client_package_id The billing id of the service that will be suspended
     * @return mixed string|Array the curl error message or an array representing the API response
     */

    public function suspendServer($client_package_id)
    {
        return $this->apiRequest("SuspendGameAndVoiceByBillingID", array("client_package_id" => $client_package_id));
    }

    /**
     * Un-Suspends a game/voice server
     * @param int $client_package_id The billing id of the service that will be un-suspended
     * @return mixed string|Array the curl error message or an array representing the API response
     */

    public function unSuspendServer($client_package_id)
    {
        return $this->apiRequest("UnSuspendGameAndVoiceByBillingID", array("client_package_id" => $client_package_id));
    }

    /**
     * Deletes a game/voice server
     * @param int $client_package_id The billing id of the service that will be deleted
     * @return mixed string|Array the curl error message or an array representing the API response
     */

    public function deleteServer($client_package_id)
    {
        return $this->apiRequest("DeleteGameAndVoiceByBillingID", array("client_package_id" => $client_package_id));
    }

    /**
     * Gets a List of Configured Games Servers
     * @return mixed string|Array the curl error message or an array representing the API response
     */

    public function getGameServers()
    {
        return $this->apiRequest("GetSupportedGames", array());
    }

    /**
     * Gets a List of Configured Voice Servers
     * @return mixed string|Array the curl error message or an array representing the API response
     */

    public function getVoiceServers()
    {
        return $this->apiRequest("GetSupportedVoiceServers", array());
    }

    /**
     * Updates a client's settings
     * @param array $params The params to upgrade:
     * - client_package_id: The billing id of the package that will be updated
     * ---------------------------
     * Parameters for game server
     * ---------------------------
     * - game_private: 1 if the game server is private. 0 if the game server is public. Default value is 0
     * - game_slots: The number of slots that the game server will have
     * - game_branded: 1 if the game server is branded. 0 if the game server is not branded.
     * - game_priority: Options (AboveNormal, BelowNormal, Normal, High, Idle, RealTime)
     * ---------------------------
     * Parameters for voice server
     * ---------------------------
     * - voice_private: 1 if the voice server is private. 0 if the voice server is public
     * - voice_slots: The number of slots that the voice server will have
     * - voice_branded: 1 if the voice server is branded. 0 if the voice server is not branded
     * - voice_upload_quota: The upload quota for a TS3 server
     * - voice_download_quota: The download quota for a TS3 server
     * - voice_priority: Options (AboveNormal, BelowNormal, Normal, High, Idle, RealTime).
     * @return mixed string|Array the curl error message or an array representing the API response
     */
    public function updateUserSettings(array $params)
    {
        return $this->apiRequest("UpdateSettings", $params);
    }

}

