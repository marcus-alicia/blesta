<?php
/**
 * SolusVM Reseller Management
 *
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package solusvm.commands
 */
class SolusvmReseller
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
     * Create a reseller
     *
     * @param array $vars An array of input params including:
     *  - username Username
     *  - password Password
     *  - email Email Address
     *  - firstname First Name
     *  - lastname Last Name
     *  - company Company Name (optional)
     *  - usernameprefix Prefix for reseller client usernames (optional)
     *  - maxvps Maxmimum number of virtual servers (optional)
     *  - maxusers Maxmimum number of users (optional)
     *  - maxmem Maxmimum amount of memory in bytes (optional)
     *  - maxburst Maximum amount of burst memory or swapspace in bytes (optional)
     *  - maxdisk Maximum amount of burst in bytes (optional)
     *  - maxbw Maximum amount of bandwidth in bytes (optional)
     *  - maxipv4 Maximum number of IPv4 addresses (optional)
     *  - maxipv6 Maximum number of IPv6 addresses (optional)
     *  - nodegroup Comma separated list of node groups (optional)
     *  - mediagroups Comma separated list of media groups (optional)
     *  - openvz Allow building of openvz virtual servers (y, n optional)
     *  - xenpv Allow building of xen pv virtual servers (y, n optional)
     *  - xenhvm Allow building of xen hvm virtual servers (y, n optional)
     *  - kvm Allow building of kvm virtual servers (y, n optional)
     * @return SolusvmResponse
     */
    public function create(array $vars)
    {
        return $this->api->submit('reseller-create', $vars);
    }

    /**
     * Modify a reseller's resources
     *
     * @param array $vars An array of input params including:
     *  - username Username
     *  - maxvps Maxmimum number of virtual servers (optional)
     *  - maxusers Maxmimum number of users (optional)
     *  - maxmem Maxmimum amount of memory in bytes (optional)
     *  - maxburst Maximum amount of burst memory or swapspace in bytes (optional)
     *  - maxdisk Maximum amount of burst in bytes (optional)
     *  - maxbw Maximum amount of bandwidth in bytes (optional)
     *  - maxipv4 Maximum number of IPv4 addresses (optional)
     *  - maxipv6 Maximum number of IPv6 addresses (optional)
     *  - nodegroup Comma separated list of node groups (optional)
     *  - mediagroups Comma separated list of media groups (optional)
     *  - openvz Allow building of openvz virtual servers (y, n optional)
     *  - xenpv Allow building of xen pv virtual servers (y, n optional)
     *  - xenhvm Allow building of xen hvm virtual servers (y, n optional)
     *  - kvm Allow building of kvm virtual servers (y, n optional)
     * @return SolusvmResponse
     */
    public function modifyResources(array $vars)
    {
        return $this->api->submit('reseller-modifyresources', $vars);
    }

    /**
     * Lists a reseller's info
     *
     * @param array $vars An array of input params including:
     *  - username Username
     * @return SolusvmResponse
     */
    public function info(array $vars)
    {
        return $this->api->submit('reseller-info', $vars);
    }

    /**
     * List reseller usernames
     *
     * @return SolusvmResponse
     */
    public function getList()
    {
        return $this->api->submit('reseller-list');
    }

    /**
     * Delete a reseller
     *
     * @param array $vars An array of input params including:
     *  - username Username
     * @return SolusvmResponse
     */
    public function delete(array $vars)
    {
        return $this->api->submit('reseller-delete', $vars);
    }
}
