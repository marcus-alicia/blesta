<?php
namespace Blesta\PterodactylSDK\Requestors;

class Servers extends \Blesta\PterodactylSDK\Requestor
{
    /**
     * Initializes the requestor with connection parameters
     *
     * @param string $apiKey The API key
     * @param string $apiUrl The API URL
     * @param bool $useSsl Whether to connect using ssl (optional)
     */
    public function __construct($apiKey, $apiUrl, $useSsl = true)
    {
        $this->setQueryParameters(['include' => 'allocations']);
        parent::__construct($apiKey, $apiUrl, $useSsl);
    }

    /**
     * Fetches a list of servers from Pterodactyl
     *
     * @return PterodactylResponse
     */
    public function getAll()
    {
        return $this->apiRequest('application/servers');
    }

    /**
     * Fetches a server from Pterodactyl
     *
     * @param int $serverId The ID of the server to fetch
     * @return PterodactylResponse
     */
    public function get($serverId)
    {
        return $this->apiRequest('application/servers/' . $serverId);
    }

    /**
     * Fetches a server from Pterodactyl by external ID
     *
     * @param int $externalId The external ID of the server to fetch
     * @return PterodactylResponse
     */
    public function getByExternalID($externalId)
    {
        return $this->apiRequest('application/servers/external/' . $externalId);
    }

    /**
     * Adds a server in Pterodactyl
     *
     * @param array $params A list of request parameters including:
     *
     *  Please note that every environment variable from the egg must be set.
     * @return PterodactylResponse
     */
    public function add(array $params)
    {
        return $this->apiRequest('application/servers', $params, 'POST');
    }

    /**
     * Edits the details for a server in Pterodactyl
     *
     * @param int $serverId The ID of the server to edit
     * @param array $params A list of request parameters including:
     * @return PterodactylResponse
     */
    public function editDetails($serverId, array $params)
    {
        return $this->apiRequest('application/servers/' . $serverId . '/details', $params, 'PATCH');
    }

    /**
     * Edits the build for a server in Pterodactyl
     *
     * @param int $serverId The ID of the server to edit
     * @param array $params A list of request parameters including:
     * @return PterodactylResponse
     */
    public function editBuild($serverId, array $params)
    {
        return $this->apiRequest('application/servers/' . $serverId . '/build', $params, 'PATCH');
    }

    /**
     * Edits the startup parameters for a server in Pterodactyl
     *
     * @param int $serverId The ID of the server to edit
     * @param array $params A list of request parameters including:
     * @return PterodactylResponse
     */
    public function editStartup($serverId, array $params)
    {
        return $this->apiRequest('application/servers/' . $serverId . '/startup', $params, 'PATCH');
    }

    /**
     * Suspends a server in Pterodactyl
     *
     * @param int $serverId The ID of the server to suspend
     * @return PterodactylResponse
     */
    public function suspend($serverId)
    {
        return $this->apiRequest('application/servers/' . $serverId . '/suspend', [], 'POST');
    }

    /**
     * Unsuspends a server in Pterodactyl
     *
     * @param int $serverId The ID of the server to unsuspend
     * @return PterodactylResponse
     */
    public function unsuspend($serverId)
    {
        return $this->apiRequest('application/servers/' . $serverId . '/unsuspend', [], 'POST');
    }

    /**
     * Reinstall a server in Pterodactyl
     *
     * @param int $serverId The ID of the server to reinstall
     * @return PterodactylResponse
     */
    public function reinstall($serverId)
    {
        return $this->apiRequest('application/servers/' . $serverId . '/reinstall', [], 'POST');
    }

    /**
     * Deletes a server in Pterodactyl
     *
     * @param int $serverId The ID of the server to delete
     * @return PterodactylResponse
     */
    public function delete($serverId)
    {
        return $this->apiRequest('application/servers/' . $serverId, [], 'DELETE');
    }

    /**
     * Forcefully deletes a server in Pterodactyl
     *
     * @param int $serverId The ID of the server to delete
     * @return PterodactylResponse
     */
    public function forceDelete($serverId)
    {
        return $this->apiRequest('application/servers/' . $serverId . '/force', [], 'DELETE');
    }

    /**
     * Fetches all databases from the given server in Pterodactyl
     *
     * @param int $serverId The ID of the server from which to fetch databases
     * @return PterodactylResponse
     */
    public function databasesGetAll($serverId)
    {
        return $this->apiRequest('application/servers/' . $serverId . '/databases', [], 'GET');
    }

    /**
     * Fetches a database from the given server in Pterodactyl
     *
     * @param int $serverId The ID of the server from which to fetch the database
     * @param int $databaseId The ID of the database to fetch
     * @return PterodactylResponse
     */
    public function databasesGet($serverId, $databaseId)
    {
        return $this->apiRequest('application/servers/' . $serverId . '/databases/' . $databaseId, [], 'GET');
    }

    /**
     * Adds database for the given server in Pterodactyl
     *
     * @param int $serverId The ID of the server for which to create the database
     * @param array $params A list of request parameters including:
     *
     *  - shortcode The shortcode of the server
     *  - description A description of the server
     * @return PterodactylResponse
     */
    public function databasesAdd($serverId, array $params)
    {
        return $this->apiRequest('application/servers/' . $serverId . '/databases', $params, 'POST');
    }

    /**
     * Resets the password for a database in Pterodactyl
     *
     * @param int $serverId The ID of the server the database is on
     * @param int $databaseId The ID of the database for which to reset the password
     * @return PterodactylResponse
     */
    public function databasesResetPassword($serverId, $databaseId)
    {
        return $this->apiRequest(
            'application/servers/' . $serverId . '/databases/' . $databaseId . 'reset-password',
            [],
            'POST'
        );
    }

    /**
     * Deletes a database in Pterodactyl
     *
     * @param int $serverId The ID of the server the database is on
     * @param int $databaseId The ID of the database to delete
     * @return PterodactylResponse
     */
    public function databasesDelete($serverId, $databaseId)
    {
        return $this->apiRequest('application/servers/' . $serverId . '/databases/' . $databaseId, [], 'DELETE');
    }
}