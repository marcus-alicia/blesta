<?php
/**
 * Internet.bs Domain Email Forwarding Management
 *
 * @copyright Copyright (c) 2022, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package internetbs.commands
 */
class InternetbsDomainEmailforward
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
     * The command is intended to add a new Email Forwarding rule.
     *
     * @param array $vars An array of input params including:
     *  - Source The forwarding rule source.
     *  - Destination The forwarding rule destination.
     * @return InternetbsResponse The response object
     */
    public function add(array $vars) : InternetbsResponse
    {
        return $this->api->apiRequest('/Domain/EmailForward/Add', $vars, 'POST');
    }

    /**
     * The command is intended to update an existing Email Forwarding rule
     *
     * @param array $vars An array of input params including:
     *  - Source The forwarding rule source.
     *  - Destination The forwarding rule destination.
     * @return InternetbsResponse The response object
     */
    public function update(array $vars) : InternetbsResponse
    {
        return $this->api->apiRequest('/Domain/EmailForward/Update', $vars, 'POST');
    }

    /**
     * The command is intended to remove an existing Email Forwarding rule.
     *
     * @param array $vars An array of input params including:
     *  - Source The forwarding rule source.
     * @return InternetbsResponse The response object
     */
    public function remove(array $vars) : InternetbsResponse
    {
        return $this->api->apiRequest('/Domain/EmailForward/Remove', $vars, 'POST');
    }

    /**
     * The command is intended to retrieve the list of email forwarding rules for a domain.
     *
     * @param array $vars An array of input params including:
     *  - Domain The domain name for which the forwarding rules have to be retrieved.
     * @return InternetbsResponse The response object
     */
    public function list(array $vars) : InternetbsResponse
    {
        return $this->api->apiRequest('/Domain/EmailForward/List', $vars);
    }
}
