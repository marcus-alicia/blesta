<?php
/**
 * Vultr firewall management.
 *
 * @package blesta
 * @subpackage blesta.components.modules.vultr
 * @copyright Copyright (c) 2023, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class VultrFirewall
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
     * Create a new firewall group on the current account.
     *
     * @param array $params An array containing the following arguments:
     *     - description: Description of firewall group.
     * @return VultrResponse An object containing the api response
     */
    public function groupCreate($params = [])
    {
        return $this->api->apiRequest('/firewalls', $params, 'POST');
    }

    /**
     * Delete a firewall group.
     *
     * @param array $params An array containing the following arguments:
     *     - firewall-group-id: Firewall group to delete.
     * @return VultrResponse An object containing the api response
     */
    public function groupDelete($params = [])
    {
        return $this->api->apiRequest('/firewalls/' . ($params['firewall-group-id'] ?? ''), [], 'DELETE');
    }

    /**
     * List all firewall groups on the current account.
     *
     * @param array $params An array containing the following arguments:
     *     - per_page: Number of items requested per page. Default is 100 and Max is 500. (optional)
     *     - cursor: Cursor for paging. See Meta and Pagination. (optional)
     * @return VultrResponse An object containing the api response
     */
    public function groupList($params = [])
    {
        return $this->api->apiRequest('/firewalls', $params);
    }

    /**
     * Change the description on a firewall group.
     *
     * @param array $params An array containing the following arguments:
     *     - firewall-group-id: Firewall group to delete.
     *     - description: Description of firewall group.
     * @return VultrResponse An object containing the api response
     */
    public function groupSetDescription($params = [])
    {
        return $this->api->apiRequest(
            '/firewalls/' . ($params['firewall-group-id'] ?? ''),
            ['description' => ($params['description'] ?? '')],
            'PUT'
        );
    }

    /**
     * Create a rule in a firewall group.
     *
     * @param array $params An array containing the following arguments:
     *     - firewall-group-id: Target firewall group.
     *     - ip_type: IP address type. Possible values: "v4", "v6".
     *     - protocol: Protocol type. Possible values: "ICMP", "TCP", "UDP", "GRE", "ESP", "AH".
     *     - subnet: IP address representing a subnet. The IP address format must match with the "ip_type" parameter value.
     *     - subnet_size: IP prefix size in bits.
     *     - port: TCP/UDP only. This field can be an integer value specifying a port or a colon separated port range.
     *     - source: If the source string is given a value of "cloudflare" subnet and subnet_size will both be ignored. Possible values:
     *          "" Use the value from subnet and subnet_size.
     *          "cloudflare" Allow all of Cloudflare's IP space through the firewall
     *          "*load-balancer-id*" Provide a load balancer ID to use its IPs
     *     - notes: User-supplied notes for this rule.
     * @return VultrResponse An object containing the api response
     */
    public function ruleCreate($params = [])
    {
        $firewall_group_id = $params['firewall-group-id'] ?? '';
        unset($params['firewall-group-id']);

        return $this->api->apiRequest('/firewalls/' . $firewall_group_id . '/rules', $params, 'POST');
    }

    /**
     * Delete a rule in a firewall group.
     *
     * @param array $params An array containing the following arguments:
     *     - firewall-group-id: Target firewall group.
     *     - firewall-rule-id: Rule number to delete.
     * @return VultrResponse An object containing the api response
     */
    public function ruleDelete($params = [])
    {
        return $this->api->apiRequest(
            '/firewalls/' . ($params['firewall-group-id'] ?? '') . '/rules/' . ($params['firewall-rule-id'] ?? ''),
            [],
            'DELETE'
        );
    }

    /**
     * List all firewall groups on the current account.
     *
     * @param array $params An array containing the following arguments:
     *     - firewall-group-id: Target firewall group.
     *     - per_page: Number of items requested per page. Default is 100 and Max is 500. (optional)
     *     - cursor: Cursor for paging. See Meta and Pagination. (optional)
     * @return VultrResponse An object containing the api response
     */
    public function ruleList($params = [])
    {
        return $this->api->apiRequest('/firewalls/' . ($params['firewall-group-id'] ?? '') . '/rules', $params);
    }
}
