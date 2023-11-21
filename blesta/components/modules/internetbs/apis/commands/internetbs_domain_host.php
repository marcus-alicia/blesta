<?php
/**
 * Internet.bs Domain Host Management
 *
 * @copyright Copyright (c) 2022, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package internetbs.commands
 */
class InternetbsDomainHost
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
     * The command is intended to create a host also known as name server or child host.
     *
     * @param array $vars An array of input params including:
     *  - Host The host to be created.
     *  - IP_List List of IP addresses separated by comma for the host.
     * @return InternetbsResponse The response object
     */
    public function create(array $vars) : InternetbsResponse
    {
        return $this->api->apiRequest('/Domain/Host/Create', $vars, 'POST');
    }

    /**
     * The command is intended to retrieve existing host (name server) information for a specific host.
     *
     * @param array $vars An array of input params including:
     *  - Host The host for which you want to retrieve information.
     * @return InternetbsResponse The response object
     */
    public function info(array $vars) : InternetbsResponse
    {
        return $this->api->apiRequest('/Domain/Host/Info', $vars);
    }

    /**
     * The command is intended to update a host; the command is replacing the current list of IP
     * for the host with the new one you provide.
     *
     * @param array $vars An array of input params including:
     *  - host The host to be updated.
     *  - IP_List List of IP addresses separated by comma for the host.
     * @return InternetbsResponse The response object
     */
    public function update(array $vars) : InternetbsResponse
    {
        return $this->api->apiRequest('/Domain/Host/Update', $vars, 'POST');
    }

    /**
     * The command is intended to delete (remove) an unwanted host.
     *
     * @param array $vars An array of input params including:
     *  - host The host to delete.
     * @return InternetbsResponse The response object
     */
    public function delete(array $vars) : InternetbsResponse
    {
        return $this->api->apiRequest('/Domain/Host/Delete', $vars, 'POST');
    }

    /**
     * The command is intended to retrieve the list of hosts defined for a domain.
     *
     * @param array $vars An array of input params including:
     *  - Domain The domain name for which the list of hosts have to be retrieved.
     *  - CompactList By default we only return the list of hosts. However, you may obtain extra information
     *      such as IPs and host status if you set CompactList=no. The default value is ComptactList=yes.
     * @return InternetbsResponse The response object
     */
    public function list(array $vars) : InternetbsResponse
    {
        return $this->api->apiRequest('/Domain/Host/List', $vars);
    }
}
