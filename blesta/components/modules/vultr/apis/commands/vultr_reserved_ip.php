<?php
/**
 * Vultr reserved IPs management.
 *
 * @package blesta
 * @subpackage blesta.components.modules.vultr
 * @copyright Copyright (c) 2023, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class VultrReservedIp
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
     * Attach a reserved IP to an existing subscription.
     *
     * @param array $params An array containing the following arguments:
     *     - reserved-ip: The Reserved IP id
     *     - instance_id: Unique identifier of the target server.
     * @return VultrResponse An object containing the api response
     */
    public function attach($params = [])
    {
        return $this->api->apiRequest(
            '/reserved-ips/' . ($params['reserved-ip'] ?? '') . '/attach',
            ['instance_id' => $params['instance_id'] ?? ''],
            'POST'
        );
    }

    /**
     * Convert an existing IP on a subscription to a reserved IP.
     *
     * @param array $params An array containing the following arguments:
     *     - ip_address: Reserved IP to be attached. Include the subnet size (e.g: /32 or /64).
     *     - label: Label for this reserved IP. (optional)
     * @return VultrResponse An object containing the api response
     */
    public function convert($params = [])
    {
        return $this->api->apiRequest('/reserved-ips/convert', $params, 'POST');
    }

    /**
     * Create a new reserved IP. Reserved IPs can only be used within the same datacenter
     * for which they were created.
     *
     * @param array $params An array containing the following arguments:
     *     - region: The Region id where the Reserved IP will be created.
     *     - ip_type: 'v4' or 'v6' Type of reserved IP to create.
     *     - label: Label for this reserved IP. (optional)
     * @return VultrResponse An object containing the api response
     */
    public function create($params = [])
    {
        return $this->api->apiRequest('/reserved-ips', $params, 'POST');
    }

    /**
     * Remove a reserved IP from your account. After making this call, you will not be able
     * to recover the IP address.
     *
     * @param array $params An array containing the following arguments:
     *     - reserved-ip: The Reserved IP id
     * @return VultrResponse An object containing the api response
     */
    public function destroy($params = [])
    {
        return $this->api->apiRequest('/reserved-ips/' . ($params['reserved-ip'] ?? ''), $params, 'DELETE');
    }

    /**
     * Detach a reserved IP from an existing subscription.
     *
     * @param array $params An array containing the following arguments:
     *     - reserved-ip: The Reserved IP id
     * @return VultrResponse An object containing the api response
     */
    public function detach($params = [])
    {
        return $this->api->apiRequest('/reserved-ips/' . ($params['reserved-ip'] ?? '') . '/detach', $params, 'POST');
    }

    /**
     * List all the active reserved IPs on this account. The "subnet_size" field is the size
     * of the network assigned to this subscription.
     *
     * @param array $params An array containing the following arguments:
     *     - per_page: Number of items requested per page. Default is 100 and Max is 500. (optional)
     *     - cursor: Cursor for paging. See Meta and Pagination. (optional)
     * @return VultrResponse An object containing the api response
     */
    public function listReservedIps($params = [])
    {
        return $this->api->apiRequest('/reserved-ips', $params);
    }
}
