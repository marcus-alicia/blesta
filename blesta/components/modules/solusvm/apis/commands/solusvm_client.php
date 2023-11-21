<?php
/**
 * SolusVM Client Management
 *
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package solusvm.commands
 */
class SolusvmClient
{
    /**
     * @var SolusvmApi
     */
    private $api;

    /**
     * Sets the API to use for communication
     *
     * @param SolusvmApi $api The API to use for communication
     */
    public function __construct(SolusvmApi $api)
    {
        $this->api = $api;
    }

    /**
     * Check if a client exists
     *
     * @param array $vars An array of input params including:
     *  - username
     * @return SolusvmResponse
     */
    public function checkExists(array $vars)
    {
        return $this->api->submit('client-checkexists', $vars);
    }

    /**
     * Delete a client
     *
     * @param array $vars An array of input params including:
     *  - username
     * @return SolusvmResponse
     */
    public function delete(array $vars)
    {
        return $this->api->submit('client-delete', $vars);
    }

    /**
     * Create a client
     *
     * @param array $vars An array of input params including:
     *  - username
     *  - password
     *  - email
     *  - firstname
     *  - lastname
     *  - company
     * @return SolusvmResponse
     */
    public function create(array $vars)
    {
        return $this->api->submit('client-create', $vars);
    }

    /**
     * Update a client's password
     *
     * @param array $vars An array of input params including:
     *  - username
     *  - password
     * @return SolusvmResponse
     */
    public function updatePassword(array $vars)
    {
        return $this->api->submit('client-updatepassword', $vars);
    }

    /**
     * List clients
     *
     * @return SolusvmResponse
     */
    public function getList()
    {
        return $this->api->submit('client-list');
    }

    /**
     * Authenticate a client
     *
     * @param array $vars An array of input params including:
     *  - username
     *  - password
     * @return SolusvmResponse
     */
    public function authenticate(array $vars)
    {
        return $this->api->submit('client-authenticate', $vars);
    }
}
