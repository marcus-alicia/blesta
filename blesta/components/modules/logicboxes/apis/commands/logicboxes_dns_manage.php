<?php
/**
 * LogicBoxes DNS Management
 *
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package logicboxes.commands
 */
class LogicboxesDnsManage
{
    /**
     * @var LogicboxesApi
     */
    private $api;

    /**
     * Sets the API to use for communication
     *
     * @param LogicboxesApi $api The API to use for communication
     */
    public function __construct(LogicboxesApi $api)
    {
        $this->api = $api;
    }

    /**
     * Adds an IPv4 Address (A) record.
     *
     * @param array $vars An array of input params including:
     *  - domain-name Domain name for which you want to add the A record
     *  - value An IPv4 address
     *  - host The host for which you need to add the A record. By default, IP address gets added for the domain name.
     *  - ttl Number of seconds the record needs to be cached by the DNS Resolvers. Default value is 14400.
     * @return LogicboxesResponse
     */
    public function addIpv4Record(array $vars)
    {
        return $this->api->submit('dns/manage/add-ipv4-record', $vars);
    }

    /**
     * Adds an IPv6 Address (AAAA) record.
     *
     * @param array $vars An array of input params including:
     *  - domain-name Domain name for which you want to add the AAAA record
     *  - value An IPv6 address
     *  - host The host for which you need to add the AAAA record. By default, IP address
     *      gets added for the domain name.
     *  - ttl Number of seconds the record needs to be cached by the DNS Resolvers. Default value is 14400.
     * @return LogicboxesResponse
     */
    public function addIpv6Record(array $vars)
    {
        return $this->api->submit('dns/manage/add-ipv6-record', $vars);
    }

    /**
     * Adds a Canonical (CNAME) record.
     *
     * @param array $vars An array of input params including:
     *  - domain-name Domain name for which you want to add the CNAME record
     *  - value A Fully Qualified Domain Name (FQDN) as the destination
     *  - host The host part of the domain-name for which you need to add a CNAME
     *  - ttl Number of seconds the record needs to be cached by the DNS Resolvers. Default value is 14400.
     * @return LogicboxesResponse
     */
    public function addCnameRecord(array $vars)
    {
        return $this->api->submit('dns/manage/add-cname-record', $vars);
    }

    /**
     * Adds a Mail Exchanger (MX) record.
     *
     * @param array $vars An array of input params including:
     *  - domain-name Domain name for which you want to add the MX record
     *  - value A Fully Qualified Domain Name (FQDN) as the destination
     *  - host The host part of the domain-name for which you need to add an MX.
     *      By default, MX record gets added for the domain name.
     *  - ttl Number of seconds the record needs to be cached by the DNS Resolvers. Default value is 14400.
     *  - priority The Priority of the MX record
     * @return LogicboxesResponse
     */
    public function addMxRecord(array $vars)
    {
        return $this->api->submit('dns/manage/add-mx-record', $vars);
    }

    /**
     * Adds a Name Server (NS) record.
     *
     * @param array $vars An array of input params including:
     *  - domain-name Domain name for which you want to add the NS record
     *  - value A Fully Qualified Domain Name (FQDN) as the authoritative Name Server
     *  - host The host part of the domain-name for which you need to add an NS record.
     *      By default, NS record gets added for the domain name.
     *  - ttl Number of seconds the record needs to be cached by the DNS Resolvers. Default value is 14400.
     * @return LogicboxesResponse
     */
    public function addNsRecord(array $vars)
    {
        return $this->api->submit('dns/manage/add-ns-record', $vars);
    }

    /**
     * Adds a Text (TXT) record.
     *
     * @param array $vars An array of input params including:
     *  - domain-name Domain name for which you want to add the TXT record
     *  - value Any text through which you wish to convey information about the zone
     *  - host The host part of the domain-name for which you need to add a TXT record
     *  - ttl Number of seconds the record needs to be cached by the DNS Resolvers. Default value is 14400.
     * @return LogicboxesResponse
     */
    public function addTxtRecord(array $vars)
    {
        return $this->api->submit('dns/manage/add-txt-record', $vars);
    }

    /**
     * Adds a Service (SRV) record.
     *
     * @param array $vars An array of input params including:
     *  - domain-name Domain name for which you want to add the SRV record
     *  - value The hostname of the machine providing the service
     *  - host A fully qualified Service name consisting of: _< service-name >._< protocol >.domain-name.com
     *  - ttl Number of seconds the record needs to be cached by the DNS Resolvers. Default value is 14400.
     *  - priority The Priority of the host. Value ranges from 0 to 65535.
     *  - port The port number of the service
     *  - weight A relative weight for records with the same priority
     * @return LogicboxesResponse
     */
    public function addSrvRecord(array $vars)
    {
        return $this->api->submit('dns/manage/add-srv-record', $vars);
    }

    /**
     * Modifies an IPv4 Address (A) record.
     *
     * @param array $vars An array of input params including:
     *  - domain-name Domain name for which you want to modify the A record
     *  - host The host for which you need to modify the A record
     *  - current-value Current IPv4 address
     *  - new-value New IPv4 address
     *  - ttl Number of seconds the record needs to be cached by the DNS Resolvers. Default value is 14400.
     * @return LogicboxesResponse
     */
    public function updateIpv4Record(array $vars)
    {
        return $this->api->submit('dns/manage/update-ipv4-record', $vars);
    }

    /**
     * Modifies an IPv6 (AAAA) record.
     *
     * @param array $vars An array of input params including:
     *  - domain-name Domain name for which you want to modify the AAAA record
     *  - host The host for which you need to modify the AAAA record
     *  - current-value Current IPv6 address
     *  - new-value New IPv6 address
     *  - ttl Number of seconds the record needs to be cached by the DNS Resolvers. Default value is 14400.
     * @return LogicboxesResponse
     */
    public function updateIpv6Record(array $vars)
    {
        return $this->api->submit('dns/manage/update-ipv6-record', $vars);
    }

    /**
     * Modifies a Canonical (CNAME) record.
     *
     * @param array $vars An array of input params including:
     *  - domain-name Domain name for which you want to modify the CNAME record
     *  - host The host part of the domain-name for which you need to modify a CNAME
     *  - current-value Current CNAME value
     *  - new-value New CNAME value
     *  - ttl Number of seconds the record needs to be cached by the DNS Resolvers. Default value is 14400.
     * @return LogicboxesResponse
     */
    public function updateCnameRecord(array $vars)
    {
        return $this->api->submit('dns/manage/update-cname-record', $vars);
    }

    /**
     * Modifies a Mail Exchanger (MX) record.
     *
     * @param array $vars An array of input params including:
     *  - domain-name Domain name for which you want to modify the MX record
     *  - host The host part of the domain-name for which you need to modify an MX
     *  - current-value Current MX value
     *  - new-value New MX value
     *  - ttl Number of seconds the record needs to be cached by the DNS Resolvers. Default value is 14400.
     *  - priority The Priority of the MX record
     * @return LogicboxesResponse
     */
    public function updateMxRecord(array $vars)
    {
        return $this->api->submit('dns/manage/update-mx-record', $vars);
    }

    /**
     * Modifies a Name Server (NS) record.
     *
     * @param array $vars An array of input params including:
     *  - domain-name Domain name for which you want to modify the NS record
     *  - host The host part of the domain-name for which you need to modify the NS record
     *  - current-value Current NS value
     *  - new-value New NS value
     *  - ttl Number of seconds the record needs to be cached by the DNS Resolvers. Default value is 14400.
     * @return LogicboxesResponse
     */
    public function updateNsRecord(array $vars)
    {
        return $this->api->submit('dns/manage/update-ns-record', $vars);
    }

    /**
     * Modifies a Text (TXT) record.
     *
     * @param array $vars An array of input params including:
     *  - domain-name Domain name for which you want to modify the TXT record
     *  - host The host part of the domain-name for which you need to modify a TXT record
     *  - current-value Current TXTvalue
     *  - new-value New TXT value
     *  - ttl Number of seconds the record needs to be cached by the DNS Resolvers. Default value is 14400.
     * @return LogicboxesResponse
     */
    public function updateTxtRecord(array $vars)
    {
        return $this->api->submit('dns/manage/update-txt-record', $vars);
    }

    /**
     * Modifies a Service (SRV) record.
     *
     * @param array $vars An array of input params including:
     *  - domain-name Domain name for which you want to modify the SRV record
     *  - host A fully qualified Service name consisting of: _< service-name >._< protocol >.domain-name.com
     *  - current-value Current hostname of the machine providing the service
     *  - new-value New hostname of the machine providing the service
     *  - ttl Number of seconds the record needs to be cached by the DNS Resolvers. Default value is 14400.
     *  - priority The Priority of the host. Value ranges from 0 to 65535.
     *  - port The Port number of the service
     *  - weight A relative weight for records with the same priority
     * @return LogicboxesResponse
     */
    public function updateSrvRecord(array $vars)
    {
        return $this->api->submit('dns/manage/update-srv-record', $vars);
    }

    /**
     * Modifies a Start of Authority (SOA) record.
     *
     * @param array $vars An array of input params including:
     *  - domain-name Domain name for which you want to modify a SOA record
     *  - responsible-person The email address of the person responsible for maintenance of the Zone
     *  - refresh The number of seconds after which the Secondary DNS Server checks the
     *      Primary DNS Server to check if the Zone has changed. Value should not be less than 14400.
     *  - retry Number of seconds that should elapse before a failed refresh should be retried.
     *      Value should not be less than 14400.
     *  - expire Number of seconds that specifies the upper limit on the time interval that
     *      can elapse before the zone is no longer authoritative. Value should not be less than 14400.
     *  - ttl Number of seconds the record needs to be cached by the DNS Resolvers.
     *      Value should not be less than 14400.
     * @return LogicboxesResponse
     */
    public function updateSoaRecord(array $vars)
    {
        return $this->api->submit('dns/manage/update-soa-record', $vars);
    }

    /**
     * Searches records based on the specified criteria.
     *
     * @param array $vars An array of input params including:
     *  - domain-name The Domain name whose DNS record(s) you want to search
     *  - type Type of record. Values may be: A, MX, CNAME, TXT, NS, SRV, AAAA
     *  - no-of-records Number of Resource Records to be fetched
     *  - page-no Page number for which details are to be fetched
     *  - host Hostname of the record
     *  - value Value of the record
     * @return LogicboxesResponse
     */
    public function searchRecords(array $vars)
    {
        return $this->api->submit('dns/manage/search-records', $vars);
    }

    /**
     * Deletes an IPv4 Address (A) record.
     *
     * @param array $vars An array of input params including:
     *  - domain-name Domain name for which you want to delete an IPv4 record
     *  - host Hostname of the record to be deleted
     *  - value An IPv4 address to be deleted
     * @return LogicboxesResponse
     */
    public function deleteIpv4Record(array $vars)
    {
        return $this->api->submit('dns/manage/delete-ipv4-record', $vars);
    }

    /**
     * Deletes an IPv6 Address (AAAA) record.
     *
     * @param array $vars An array of input params including:
     *  - domain-name Domain name for which you want to delete an IPv6 record
     *  - host Hostname of the record to be deleted
     *  - value An IPv6 address to be deleted
     * @return LogicboxesResponse
     */
    public function deleteIpv6Record(array $vars)
    {
        return $this->api->submit('dns/manage/delete-ipv6-record', $vars);
    }

    /**
     * Deletes a Canonical (CNAME) record.
     *
     * @param array $vars An array of input params including:
     *  - domain-name Domain name for which you want to delete a CNAME record
     *  - host Hostname of the record to be deleted
     *  - value A Fully Qualified Domain Name (FQDN)
     * @return LogicboxesResponse
     */
    public function deleteCnameRecord(array $vars)
    {
        return $this->api->submit('dns/manage/delete-cname-record', $vars);
    }

    /**
     * Deletes a Mail Exchanger (MX) record.
     *
     * @param array $vars An array of input params including:
     *  - domain-name Domain name for which you want to delete a MX record
     *  - host Hostname of the record to be deleted
     *  - value A Fully Qualified Domain Name (FQDN)
     * @return LogicboxesResponse
     */
    public function deleteMxRecord(array $vars)
    {
        return $this->api->submit('dns/manage/delete-mx-record', $vars);
    }

    /**
     * Deletes a Name Server (NS) record.
     *
     * @param array $vars An array of input params including:
     *  - domain-name Domain name for which you want to delete a NS record
     *  - host Hostname of the record to be deleted
     *  - value A Fully Qualified Domain Name (FQDN)
     * @return LogicboxesResponse
     */
    public function deleteNsRecord(array $vars)
    {
        return $this->api->submit('dns/manage/delete-ns-record', $vars);
    }

    /**
     * Deletes a Text (TXT) record.
     *
     * @param array $vars An array of input params including:
     *  - domain-name Domain name for which you want to delete a TXT record
     *  - host Hostname of the record to be deleted
     *  - value A text value for which the record to be deleted
     * @return LogicboxesResponse
     */
    public function deleteTxtRecord(array $vars)
    {
        return $this->api->submit('dns/manage/delete-txt-record', $vars);
    }

    /**
     * Deletes a Service (SRV) record.
     *
     * @param array $vars An array of input params including:
     *  - domain-name Domain name for which you want to delete a SRV record
     *  - host A fully qualified Service name consisting of: _< service-name >._< protocol >.domain-name.com
     *  - value The hostname of the machine providing the service
     *  - port The port number of the service
     *  - weight A relative weight for records with the same priority
     * @return LogicboxesResponse
     */
    public function deleteSrvRecord(array $vars)
    {
        return $this->api->submit('dns/manage/delete-srv-record', $vars);
    }
}
