<?php
/**
 * Vultr baremetal management.
 *
 * @package blesta
 * @subpackage blesta.components.modules.vultr
 * @copyright Copyright (c) 2023, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class VultrBaremetal
{
    /**
     * @var VultrApi The API to use for communication.
     */
    private $api;

    /**
     * Sets the API to use for communication.
     *
     * @param VultrApi $api The API to use for communication
     */
    public function __construct(VultrApi $api)
    {
        $this->api = $api;
    }

    /**
     * Create a new baremetal server.
     *
     * @param array $params An array containing the following arguments:
     *     - region: The Region id to create the instance.
     * @return VultrResponse An instance of the API response
     */
    public function create($params = [])
    {
        return $this->api->apiRequest('/bare-metals', $params, 'POST');
    }

    /**
     * Destroy (delete) a baremetal server.
     *
     * @param array $params An array containing the following arguments:
     *     - baremetal-id: The Bare Metal id.
     *     - plan: Plan to use when creating this server.
     *     - os_id: Operating system to use.
     *     - script_id: If you've not selected a 'custom' operating system, this can be the
     *         script_id of a startup script to execute on boot. (optional)
     *     - snapshot_id: If you've selected the 'snapshot' operating system, this
     *         should be the SNAPSHOTID. (optional)
     *     - enable_ipv6: If true, an IPv6 subnet will be assigned. (optional)
     *     - label: This is a text label that will be shown in the control panel. (optional)
     *     - sshkey_id: List of SSH keys to apply to this server on install. (optional)
     *     - app_id: If launching an application (OSID 186), this is the APPID to launch. (optional)
     *     - user_data: Base64 encoded user-data. (optional)
     *     - activation_email: 'true' or 'false'. If true, an activation email will be sent. (optional)
     *     - reserved_ipv4: IP address of the floating IP to use on this server. (optional)
     *     - hostname: The hostname to assign to this server. (optional)
     *     - tags: The tags to assign to this server. (optional)
     * @return VultrResponse An instance of the API response
     */
    public function destroy($params = [])
    {
        return $this->api->apiRequest('/bare-metals/' . ($params['baremetal-id'] ?? ''), [], 'DELETE');
    }

    /**
     * Fetches a baremetal server.
     *
     * @param array $params An array containing the following arguments:
     *     - baremetal-id: Unique identifier for this instance.
     * @return VultrResponse An object containing the api response.
     */
    public function get($params = [])
    {
        return $this->api->apiRequest('/bare-metals/' . ($params['baremetal-id'] ?? ''));
    }

    /**
     * List all baremetal servers on the current account.
     *
     * @param array $params An array containing the following arguments:
     *     - per_page: Number of items requested per page. Default is 100 and Max is 500. (optional)
     *     - cursor: Cursor for paging. See Meta and Pagination. (optional)
     * @return VultrResponse An instance of the API response
     */
    public function listBaremetal($params = [])
    {
        return $this->api->apiRequest('/bare-metals', $params);
    }

    /**
     * List the IPv4 information of a baremetal server.
     *
     * @param array $params An array containing the following arguments:
     *     - baremetal-id: The Bare Metal id.
     * @return VultrResponse An instance of the API response
     */
    public function listIpv4($params = [])
    {
        return $this->api->apiRequest('/bare-metals/' . ($params['baremetal-id'] ?? '') . '/ipv4');
    }

    /**
     * List the IPv6 information of a baremetal server.
     *
     * @param array $params An array containing the following arguments:
     *     - baremetal-id: The Bare Metal id.
     * @return VultrResponse An instance of the API response
     */
    public function listIpv6($params = [])
    {
        return $this->api->apiRequest('/bare-metals/' . ($params['baremetal-id'] ?? '') . '/ipv6');
    }

    /**
     * Halt a baremetal server. This is a hard power off, meaning that the power to
     * the machine is severed.
     *
     * @param array $params An array containing the following arguments:
     *     - baremetal-id: The Bare Metal id.
     * @return VultrResponse An instance of the API response
     */
    public function halt($params = [])
    {
        return $this->api->apiRequest('/bare-metals/' . ($params['baremetal-id'] ?? '') . '/halt', $params, 'POST');
    }

    /**
     * Reboot a baremetal server. This is a hard reboot, which means that the server
     * is powered off, then back on.
     *
     * @param array $params An array containing the following arguments:
     *     - baremetal-id: The Bare Metal id.
     * @return VultrResponse An instance of the API response
     */
    public function reboot($params = [])
    {
        return $this->api->apiRequest('/bare-metals/' . ($params['baremetal-id'] ?? '') . '/reboot', $params, 'POST');
    }

    /**
     * Reinstall the operating system on a baremetal server.
     *
     * @param array $params An array containing the following arguments:
     *     - baremetal-id: The Bare Metal id.
     * @return VultrResponse An instance of the API response
     */
    public function reinstall($params = [])
    {
        return $this->api->apiRequest('/bare-metals/' . ($params['baremetal-id'] ?? '') . '/reinstall', [], 'POST');
    }

    /**
     * Enables IPv6 networking on a baremetal server by assigning an IPv6 subnet to it.
     *
     * @param array $params An array contaning the following arguments:
     *     - baremetal-id: The Bare Metal id.
     * @return VultrResponse An instance of the API response
     */
    public function enableIpv6($params = [])
    {
        return $this->api->apiRequest(
            '/bare-metals/' . ($params['baremetal-id'] ?? ''),
            ['enable_ipv6' => true],
            'PATCH'
        );
    }

    /**
     * Start the Bare Metal instance.
     *
     * @param array $params An array containing the following arguments:
     *     - baremetal-id: The Bare Metal id.
     * @return VultrResponse An instance of the API response
     */
    public function start($params = [])
    {
        return $this->api->apiRequest('/bare-metals/' . ($params['baremetal-id'] ?? '') . '/start', [], 'POST');
    }

    /**
     * Set the label of a baremetal server.
     *
     * @param array $params An array containing the following arguments:
     *     - baremetal-id: The Bare Metal id.
     *     - label: This is a text label that will be shown in the control panel.
     * @return VultrResponse An instance of the API response
     */
    public function setLabel($params = [])
    {
        return $this->api->apiRequest(
            '/bare-metals/' . ($params['baremetal-id'] ?? ''),
            ['label' => ($params['label'] ?? '')],
            'PATCH'
        );
    }

    /**
     * Get bandwidth information for the Bare Metal instance.
     *
     * @param array $params An array containing the following arguments:
     *     - baremetal-id: The Bare Metal id.
     * @return VultrResponse An instance of the API response
     */
    public function getBandwidth($params = [])
    {
        return $this->api->apiRequest('/bare-metals/' . ($params['baremetal-id'] ?? '') . '/bandwidth', $params);
    }

    /**
     * Retrieves the (base64 encoded) user-data for this subscription.
     *
     * @param array $params An array containing the following arguments:
     *     - baremetal-id: The Bare Metal id.
     * @return VultrResponse An instance of the API response
     */
    public function getUserData($params = [])
    {
        return $this->api->apiRequest('/bare-metals/' . ($params['baremetal-id'] ?? '') . '/user-data', $params);
    }

    /**
     * Reinstalls the baremetal server to a different Vultr one-click application.
     *
     * @param array $params An array containing the following arguments:
     *     - baremetal-id: The Bare Metal id.
     *     - app_id: Application to use.
     * @return VultrResponse An instance of the API response
     */
    public function appChange($params = [])
    {
        return $this->api->apiRequest(
            '/bare-metals/' . ($params['baremetal-id'] ?? ''),
            ['app_id' => ($params['app_id'] ?? '')],
            'PATCH'
        );
    }

    /**
     * Retrieves a list of Vultr one-click applications to which a baremetal server can be changed.
     *
     * @param array $params An array containing the following arguments:
     *     - baremetal-id: The Bare Metal id.
     * @return VultrResponse An instance of the API response
     */
    public function appChangeList($params = [])
    {
        return $this->api->apiRequest('/bare-metals/' . ($params['baremetal-id'] ?? '') . '/upgrades', ['type' => 'applications']);
    }

    /**
     * Changes the virtual machine to a different operating system. All data will
     * be permanently lost.
     *
     * @param array $params An array containing the following arguments:
     *     - baremetal-id: Unique identifier for this instance.
     *     - os_id: Operating system to use.
     * @return VultrResponse An object containing the api response.
     */
    public function osChange($params = [])
    {
        return $this->api->apiRequest(
            '/bare-metals/' . ($params['baremetal-id'] ?? ''),
            ['os_id' => ($params['os_id'] ?? '')],
            'PATCH'
        );
    }

    /**
     * Retrieves a list of operating systems to which a baremetal server can be changed.
     *
     * @param array $params An array containing the following arguments:
     *     - baremetal-id: The Bare Metal id.
     * @return VultrResponse An instance of the API response
     */
    public function osChangeList($params = [])
    {
        return $this->api->apiRequest('/bare-metals/' . ($params['baremetal-id'] ?? '') . '/upgrades', ['type' => 'os']);
    }

    /**
     * Sets the user-data for this subscription.
     *
     * @param array $params An array containing the following arguments:
     *     - baremetal-id: The Bare Metal id.
     *     - user_data: Base64 encoded user-data.
     * @return VultrResponse An instance of the API response
     */
    public function setUserData($params = [])
    {
        return $this->api->apiRequest(
            '/bare-metals/' . ($params['baremetal-id'] ?? ''),
            ['user_data' => ($params['user_data'] ?? '')],
            'PATCH'
        );
    }

    /**
     * Set the tag of a baremetal server.
     *
     * @param array $params An array containing the following arguments:
     *     - baremetal-id: The Bare Metal id.
     *     - tag: A tag string. Only subscription objects with this tag will be returned.
     * @return VultrResponse An instance of the API response
     */
    public function setTag($params = [])
    {
        return $this->api->apiRequest(
            '/bare-metals/' . ($params['baremetal-id'] ?? ''),
            ['tags' => [($params['tag'] ?? '')]],
            'PATCH'
        );
    }
}
