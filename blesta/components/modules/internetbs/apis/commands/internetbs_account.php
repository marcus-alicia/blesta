<?php
/**
 * Internet.bs Account Management
 *
 * @copyright Copyright (c) 2022, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package internetbs.commands
 */
class InternetbsAccount
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
     * The command is intended to retrieve the prepaid account balance.
     *
     * @param array $vars An array of input params including:
     *  - Currency The currency for which the account balance should be retrieved for.
     * @return InternetbsResponse The response object
     */
    public function getBalance(array $vars) : InternetbsResponse
    {
        return $this->api->apiRequest('/Account/Balance/Get', $vars);
    }

    /**
     * The command is intended to set the default currency. The default currency is used when you
     * have available balances in multiple currencies.
     *
     * @param array $vars An array of input params including:
     *  - Currency The currency of the prepaid balance that will be used for all billable API operations.
     * @return InternetbsResponse The response object
     */
    public function setDefaultCurrency(array $vars) : InternetbsResponse
    {
        return $this->api->apiRequest('/Account/DefaultCurrency/Set', $vars, 'POST');
    }

    /**
     * The command is intended to set the default currency.
     *
     * @return InternetbsResponse The response object
     */
    public function getDefaultCurrency() : InternetbsResponse
    {
        return $this->api->apiRequest('/Account/DefaultCurrency/Get');
    }

    /**
     * The command is intended to obtain our pricelist.
     *
     * @param array $vars An array of input params including:
     *  - discountCode A discount code for which to get the prices.
     *  - Currency The prices currency. If not provided we will return prices in your default currency.
     *  - version The command version. Possible values are 1 and 2. Default value is 1 and it will return
     *      the price for the first year of registration, first year of renewal, etc. If set to 2 then
     *      command will return all current prices for all products.
     * @return InternetbsResponse The response object
     */
    public function getPriceList(array $vars) : InternetbsResponse
    {
        return $this->api->apiRequest('/Account/PriceList/Get', $vars);
    }
}
