<?php
/**
 * Vultr DNS management.
 *
 * @package blesta
 * @subpackage blesta.components.modules.vultr
 * @copyright Copyright (c) 2023, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class VultrDns
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
     * Create a domain name in DNS.
     *
     * @param array $params An array containing the following arguments:
     *     - domain: Domain name to create.
     *     - ip: Server IP to use when creating default records.
     * @return VultrResponse An object containing the api response
     */
    public function createDomain($params = [])
    {
        return $this->api->apiRequest('/domains', $params, 'POST');
    }

    /**
     * Add a DNS record.
     *
     * @param array $params An array containing the following arguments:
     *     - dns-domain: Domain name to add record to.
     *     - name: Name of the record.
     *     - type: Type (A, AAAA, MX, SRV, TXT) of the record.
     *     - data: The data for this record.
     *     - ttl: TTL of this record. (optional)
     *     - priority: Priority of this record. (only required for MX and SRV)
     * @return VultrResponse An object containing the api response
     */
    public function createRecord($params = [])
    {
        $dns_domain = $params['dns-domain'] ?? '';
        unset($params['dns-domain']);

        return $this->api->apiRequest('/domains/' . $dns_domain . '/records', $params, 'POST');
    }

    /**
     * Delete a domain name and all associated records.
     *
     * @param array $params An array containing the following arguments:
     *     - dns-domain: Domain name to delete.
     * @return VultrResponse An object containing the api response
     */
    public function deleteDomain($params = [])
    {
        return $this->api->apiRequest('/domains/' . ($params['dns-domain'] ?? ''), [], 'DELETE');
    }

    /**
     * Delete an individual DNS record.
     *
     * @param array $params An array containing the following arguments:
     *     - dns-domain: Domain name to delete.
     *     - record-id: ID of record to delete.
     * @return VultrResponse An object containing the api response
     */
    public function deleteRecord($params = [])
    {
        return $this->api->apiRequest(
            '/domains/' . ($params['dns-domain'] ?? '') . '/records/' . ($params['record-id'] ?? ''),
            [],
            'DELETE'
        );
    }

    /**
     * Enable or disable DNSSEC for a domain.
     *
     * @param array $params An array containing the following arguments:
     *     - dns-domain: Domain name to delete.
     *     - dns_sec: 'enabled' or 'disabled'.  If enabled, DNSSEC will be enabled for the given domain.
     * @return VultrResponse An object containing the api response
     */
    public function dnssecEnable($params = [])
    {
        return $this->api->apiRequest(
            '/domains/' . ($params['dns-domain'] ?? ''),
            ['dns_sec' => ($params['dns_sec'] ?? '')],
            'PUT'
        );
    }

    /**
     * Get the DNSSEC keys (if enabled) for a domain.
     *
     * @param array $params An array containing the following arguments:
     *     - dns-domain: Domain name to delete.
     * @return VultrResponse An object containing the api response
     */
    public function dnssecInfo($params = [])
    {
        return $this->api->apiRequest('/domains/' . ($params['dns-domain'] ?? '') . '/dnssec', $params);
    }

    /**
     * List all domains associated with the current account.
     *
     * @param array $params An array containing the following arguments:
     *     - per_page: Number of items requested per page. Default is 100 and Max is 500. (optional)
     *     - cursor: Cursor for paging. See Meta and Pagination. (optional)
     * @return VultrResponse An object containing the api response
     */
    public function listDns($params = [])
    {
        return $this->api->apiRequest('/domains', $params);
    }

    /**
     * List all the records associated with a particular domain.
     *
     * @param array $params An array containing the following arguments:
     *     - dns-domain: Domain name to delete.
     *     - per_page: Number of items requested per page. Default is 100 and Max is 500. (optional)
     *     - cursor: Cursor for paging. See Meta and Pagination. (optional)
     * @return VultrResponse An object containing the api response
     */
    public function records($params = [])
    {
        $dns_domain = $params['dns-domain'] ?? '';
        unset($params['dns-domain']);

        return $this->api->apiRequest('/domains/' . $dns_domain . '/records', $params);
    }

    /**
     * Get the SOA record information for a domain.
     *
     * @param array $params An array containing the following arguments:
     *     - dns-domain: Domain name to delete.
     * @return VultrResponse An object containing the api response
     */
    public function soaInfo($params = [])
    {
        return $this->api->apiRequest('/domains/' . ($params['dns-domain'] ?? '') . '/soa', $params);
    }

    /**
     * Update the SOA record information for a domain.
     *
     * @param array $params An array containing the following arguments:
     *     - dns-domain: Domain name to delete.
     *     - nsprimary: Primary nameserver to store in the SOA record.
     *     - email: Administrative email to store in the SOA record.
     * @return VultrResponse An object containing the api response
     */
    public function soaUpdate($params = [])
    {
        $dns_domain = $params['dns-domain'] ?? '';
        unset($params['dns-domain']);

        return $this->api->apiRequest('/domains/' . $dns_domain . '/soa', $params, 'PATCH');
    }

    /**
     * Update a DNS record.
     *
     * @param array $params An array containing the following arguments:
     *     - dns-domain: Domain name to delete.
     *     - record-id: ID of record to update.
     *     - name: Name of the record.
     *     - data: The data for this record.
     *     - ttl: TTL of this record. (optional)
     *     - priority: Priority of this record. (only required for MX and SRV)
     * @return VultrResponse An object containing the api response
     */
    public function updateRecord($params = [])
    {
        $dns_domain = $params['dns-domain'] ?? '';
        unset($params['dns-domain']);

        $record_id = $params['record-id'] ?? '';
        unset($params['record-id']);

        return $this->api->apiRequest('/domains/' . $dns_domain . '/records/' . $record_id, $params, 'PATCH');
    }
}
