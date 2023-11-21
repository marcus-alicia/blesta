<?php
/**
 * OpenSRS DNS Management
 *
 * @copyright Copyright (c) 2021, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package opensrs.commands
 */
class OpensrsDomainsDns
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
     * Enables the DNS service for a domain.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function createDnsZone(array $vars) : OpensrsResponse
    {
        return $this->api->submit('create_dns_zone', $vars);
    }

    /**
     * Deletes the DNS zones defined for the specified domain.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function deleteDnsZone(array $vars) : OpensrsResponse
    {
        return $this->api->submit('delete_dns_zone', $vars);
    }

    /**
     * Changes the nameservers on your domain to use the DNS nameservers.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function forceDnsNameservers(array $vars) : OpensrsResponse
    {
        return $this->api->submit('force_dns_nameservers', $vars);
    }

    /**
     * Allows you to view the DNS records for a specified domain.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function getDnsZone(array $vars) : OpensrsResponse
    {
        return $this->api->submit('get_dns_zone', $vars);
    }

    /**
     * Sets the DNS zone to the values in the specified template.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function resetDnsZone(array $vars) : OpensrsResponse
    {
        return $this->api->submit('reset_dns_zone', $vars);
    }

    /**
     * Sets the records for a domain's DNS zone.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function setDnsZone(array $vars) : OpensrsResponse
    {
        return $this->api->submit('set_dns_zone', $vars);
    }
}
