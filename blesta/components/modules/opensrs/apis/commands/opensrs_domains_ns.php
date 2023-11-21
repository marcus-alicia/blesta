<?php
/**
 * OpenSRS Nameserver Management
 *
 * @copyright Copyright (c) 2021, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package opensrs.commands
 */
class OpensrsDomainsNs
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
     * Queries nameservers that exist in the current user profile.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function get(array $vars) : OpensrsResponse
    {
        return $this->api->submit('get', $vars, 'nameserver');
    }

    /**
     * Deletes a nameserver.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function delete(array $vars) : OpensrsResponse
    {
        return $this->api->submit('delete', $vars, 'nameserver');
    }

    /**
     * Modifies a nameserver.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function modify(array $vars) : OpensrsResponse
    {
        return $this->api->submit('modify', $vars, 'nameserver');
    }

    /**
     * Adds a nameserver to one or all registries to which a reseller has access.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function registryAddNs(array $vars) : OpensrsResponse
    {
        return $this->api->submit('registry_add_ns', $vars, 'nameserver');
    }

    /**
     * Verifies whether a nameserver exists at a particular registry.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function registryCheckNameserver(array $vars) : OpensrsResponse
    {
        return $this->api->submit('registry_check_nameserver', $vars, 'nameserver');
    }

    /**
     * Adds or removes nameservers for a domain.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function advancedUpdateNameserver(array $vars) : OpensrsResponse
    {
        return $this->api->submit('advanced_update_nameservers', $vars);
    }
}
