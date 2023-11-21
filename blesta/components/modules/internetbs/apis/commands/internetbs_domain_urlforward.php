<?php
/**
 * Internet.bs Domain URL Forwarding Management
 *
 * @copyright Copyright (c) 2022, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package internetbs.commands
 */
class InternetbsDomainUrlforward
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
     * The command is intended to add a new URL Forwarding rule.
     *
     * @param array $vars An array of input params including:
     *  - Source The URL Forwarding rule source.
     *  - Destination The forwarding rule destination.
     *  - isFramed If set to YES then a frame will be used to, If set to NO then the destination
     *      URL will be rewritten and appear in the URL bar. The default value is set to YES.
     *  - redirect301 Redirect user to destination page using HTTP 301 redirection code. Possible values are YES NO.
     * @return InternetbsResponse The response object
     */
    public function add(array $vars) : InternetbsResponse
    {
        return $this->api->apiRequest('/Domain/UrlForward/Add', $vars, 'POST');
    }

    /**
     * The command is intended to update an existing URL Forwarding rule.
     *
     * @param array $vars An array of input params including:
     *  - Source The URL Forwarding rule source.
     *  - Destination The forwarding rule destination.
     *  - isFramed If set to YES then a frame will be used to, If set to NO then the destination
     *      URL will be rewritten and appear in the URL bar. The default value is set to YES.
     *  - redirect301 Redirect user to destination page using HTTP 301 redirection code. Possible values are YES NO.
     * @return InternetbsResponse The response object
     */
    public function update(array $vars) : InternetbsResponse
    {
        return $this->api->apiRequest('/Domain/UrlForward/Update', $vars, 'POST');
    }

    /**
     * The command is intended to remove an existing URL Forwarding rule.
     *
     * @param array $vars An array of input params including:
     *  - Source The URL Forwarding rule source.
     * @return InternetbsResponse The response object
     */
    public function remove(array $vars) : InternetbsResponse
    {
        return $this->api->apiRequest('/Domain/UrlForward/Remove', $vars, 'POST');
    }

    /**
     * The command is intended to retrieve the list of URL forwarding rules for a domain.
     *
     * @param array $vars An array of input params including:
     *  - Domain The domain name for which the URL Forwarding rules have to be retrieved.
     * @return InternetbsResponse The response object
     */
    public function list(array $vars) : InternetbsResponse
    {
        return $this->api->apiRequest('/Domain/UrlForward/List', $vars);
    }
}
