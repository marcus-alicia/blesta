<?php
/**
 * OpenSRS User Management
 *
 * @copyright Copyright (c) 2021, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package opensrs.commands
 */
class OpensrsUsers
{
    /**
     * @var OpensrsApi
     */
    private $api;

    /**
     * Sets the API to use for communication
     *
     * @param OpensrsApi $api The API to use for communication
     */
    public function __construct(OpensrsApi $api)
    {
        $this->api = $api;
    }

    /**
     * Creates a subuser for a user's account. Only one subuser can exist per account.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function add(array $vars) : OpensrsResponse
    {
        return $this->api->submit('add', $vars, 'subuser');
    }

    /**
     * Deletes a subuser.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function delete(array $vars) : OpensrsResponse
    {
        return $this->api->submit('delete', $vars, 'subuser');
    }

    /**
     * Queries a domain's sub-user data.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function get(array $vars) : OpensrsResponse
    {
        return $this->api->submit('get', $vars, 'subuser');
    }

    /**
     * Modifies a domain's sub-user data.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function modify(array $vars) : OpensrsResponse
    {
        return $this->api->submit('modify', $vars, 'subuser');
    }
}
