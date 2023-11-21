<?php
/**
 * SolusVM Node Management
 *
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package solusvm.commands
 */
class SolusvmNodes
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
     * List nodes
     *
     * @param array $vars An array of input params including:
     *  - type (openvz, xen, xen hvm, kvm)
     * @return SolusvmResponse
     */
    public function getList(array $vars)
    {
        return $this->api->submit('listnodes', $vars);
    }

    /**
     * List nodes by ID
     *
     * @param array $vars An array of input params including:
     *  - type (openvz, xen, xen hvm, kvm)
     * @return SolusvmResponse
     */
    public function idList(array $vars)
    {
        return $this->api->submit('node-idlist', $vars);
    }

    /**
     * List node groups
     *
     * @return SolusvmResponse
     */
    public function listGroups()
    {
        return $this->api->submit('listnodegroups');
    }

    /**
     * List all IP addresses for a node
     *
     * @param array $vars An array of input params including:
     *  - nodeid The ID of the node
     * @return SolusvmResponse
     */
    public function ipList(array $vars)
    {
        return $this->api->submit('node-iplist', $vars);
    }

    /**
     * List virtual servers
     *
     * @param array $vars An array of input params including:
     *  - nodeid The ID of the node
     * @return SolusvmResponse
     */
    public function virtualServers(array $vars)
    {
        return $this->api->submit('node-virtualservers', $vars);
    }

    /**
     * List Xen node resources
     *
     * @param array $vars An array of input params including:
     *  - nodeid The ID of the node
     * @return SolusvmResponse
     */
    public function xenResources(array $vars)
    {
        return $this->api->submit('node-xenresources', $vars);
    }

    /**
     * List node statistics
     *
     * @param array $vars An array of input params including:
     *  - nodeid The ID of the node
     * @return SolusvmResponse
     */
    public function statistics(array $vars)
    {
        return $this->api->submit('node-statistics', $vars);
    }
}
