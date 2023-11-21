<?php
/**
 * LogicBoxes Product Management
 *
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package logicboxes.commands
 */
class LogicboxesProducts
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
     * Gets a list of product mappings
     *
     * @return LogicboxesResponse
     */
    public function getMappings()
    {
        return $this->api->submit('products/category-keys-mapping', [], 'GET');
    }

    /**
     * Gets a list of reseller product pricings
     *
     * @param array $vars An array of input params including:
     *  - reseller-id Reseller ID of the Reseller whose Cost Price has to be retrieved.
     *   By default, Cost Price of the current user will be retrieved.
     * @return LogicboxesResponse
     */
    public function getPricing(array $vars)
    {
        return $this->api->submit('products/reseller-cost-price', $vars, 'GET');
    }
}
