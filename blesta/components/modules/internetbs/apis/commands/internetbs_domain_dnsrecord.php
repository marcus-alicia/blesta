<?php
/**
 * Internet.bs Domain DNS Record Management
 *
 * @copyright Copyright (c) 2022, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package internetbs.commands
 */
class InternetbsDomainDnsrecord
{
    /**
     * @var InternetbsApi
     */
    private $api;

    /**
     * Sets the API to use for communication
     *
     * @param InternetbsApi $api The API to use for communication
     */
    public function __construct(InternetbsApi $api)
    {
        $this->api = $api;
    }

    /**
     * The command is intended to add a new DNS record to a specific zone (domain).
     *
     * @param array $vars An array of input params including:
     *  - FullRecordName Is the full record name for which you are creating a DNS record.
     *  - Type Defines the type of the DNSrecord to be added. Accepted values are:
     *      A, AAAA, DYNAMIC, CNAME, MX, SRV, TXT and NS
     *  - Value The record value.
     *  - Ttl Numeric values representing the time to live in seconds.
     *  - Priority A number representing the priority. It is only used for MX records and the default value is 10
     * @return InternetbsResponse The response object
     */
    public function add(array $vars) : InternetbsResponse
    {
        return $this->api->apiRequest('/Domain/DnsRecord/Add', $vars, 'POST');
    }

    /**
     * The command is intended to update an existing DNS record.
     *
     * @param array $vars An array of input params including:
     *  - FullRecordName Is the full record name for which you are creating a DNS record.
     *  - Type Defines the type of the DNSrecord to be added. Accepted values are:
     *      A, AAAA, DYNAMIC, CNAME, MX, SRV, TXT and NS
     *  - CurrentValue Only required if there is a possible ambiguity, ex. if you have a CNAME and a A record for
     *      the same FullRecordName or if you have multiple entries for the same FullRecordName.
     *  - CurrentTtl Only required for disambiguation purposes. Numeric values representing the time to live in seconds.
     *  - CurrentPriority Only required for disambiguation purposes. A number representing the priority.
     *  - NewValue The record value. Ex: 192.168.1.1 for an A record.
     *  - NewTtl Numeric values representing the time to live in seconds.
     *  - NewPriority A number representing the priority.
     * @return InternetbsResponse The response object
     */
    public function update(array $vars) : InternetbsResponse
    {
        return $this->api->apiRequest('/Domain/DnsRecord/Update', $vars, 'POST');
    }

    /**
     * The command is intended to remove a DNS record from a specific zone.
     *
     * @param array $vars An array of input params including:
     *  - FullRecordName Is the full record name for which you are creating a DNS record.
     *  - Type Defines the type of the DNSrecord to be added. Accepted values are:
     *      A, AAAA, DYNAMIC, CNAME, MX, SRV, TXT and NS
     *  - Value The record value.
     *  - Ttl Numeric values representing the time to live in seconds.
     *  - Priority A number representing the priority. It is only used for MX records and the default value is 10
     * @return InternetbsResponse The response object
     */
    public function remove(array $vars) : InternetbsResponse
    {
        return $this->api->apiRequest('/Domain/DnsRecord/Remove', $vars, 'POST');
    }

    /**
     * The command is intended to retrieve the list of DNS records for a specific domain.
     *
     * @param array $vars An array of input params including:
     *  - Domain The domain name all called the zone in DNS language.
     *  - FilterType You can specify here a DNS record type to retrieve only records of that type.
     *      By default all record are retrieved. Accepted values are: A, AAAA, DYNAMIC, CNAME, MX, TXT, NS and ALL
     * @return InternetbsResponse The response object
     */
    public function list(array $vars) : InternetbsResponse
    {
        return $this->api->apiRequest('/Domain/DnsRecord/List', $vars);
    }
}
