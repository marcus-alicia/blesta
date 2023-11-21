<?php
/**
 * Plesk Service Plan management
 *
 * From API RPC version 1.6.3.0 and greater
 *
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @package plesk.commands
 */
class PleskServicePlans extends PleskPacket
{
    /**
     * @var The earliest version this API may be used with
     */
    private $earliest_api_version = '1.6.3.0';
    /**
     * @var The PleskApi
     */
    private $api;
    /**
     * @var The base XML container for this API
     */
    private $base_container = '/service-plan';

    /**
     * Sets the API to use for communication
     *
     * @param PleskApi $api The API to use for communication
     * @param string $api_version The API RPC version
     * @param PleskPacket $packet The current plesk packet
     */
    public function __construct(PleskApi $api, $api_version)
    {
        parent::__construct($api_version);
        $this->api = $api;

        $this->buildContainer();
    }

    /**
     * Retrieves the earliest version this API command supports
     *
     * @return string The earliest API RPC version supported
     */
    public function getEarliestVersion()
    {
        return $this->earliest_api_version;
    }

    /**
     * Builds the packet container for the API command
     */
    protected function buildContainer()
    {
        $this->insert(['service-plan' => null]);
        $this->setContainer($this->base_container);
    }

    /**
     * Resets the container path to the base path
     */
    public function resetContainer()
    {
        $this->setContainer($this->base_container);
    }

    /**
     * Retrieves the service plans and information
     * An owner MUST be specified unless the API version is 1.6.3.4 or greater.
     * 1.6.3.4 allows no owner to be specified, which will receive plans from everyone
     *
     * @param array $vars An array of input params including:
     *  - filter An array of containing one of the following fields to filter results on:
     *      - name The name of the service plan
     *      - id The service plan ID
     *      - guid The GUID of the service plan
     *      - external_id The GUID of the service plan in the Panel components
     *  - plans An array of plan names to fetch information for
     *      (optional, default empty array to fetch all plans)
     *  - owner_id The ID of the owner/reseller that the plans belong to, or that you wish to
     *      fetch (optional, will be used if owner_login is also specified)
     *  - owner_login The login name of the owner/reseller that the plans belong to,
     *      or that you wish to fetch (optional)
     *  - owner_all Set to true to fetch plans for all owners/resellers
     *      (optional, requires version 1.6.3.4; will be used if specified)
     * @return PleskResponse
     */
    public function get(array $vars = [])
    {
        // Set the container for this API request
        $this->insert(['get' => null], $this->getContainer());
        $this->setContainer($this->base_container . '/get');

        // Build service plan XML container
        $filter = (isset($vars['filter']) ? $vars['filter'] : []);
        $this->buildFilter($filter, $this->getContainer());

        // Set any plans
        if (!empty($vars['plans'])) {
            foreach ($vars['plans'] as $plan) {
                $this->insert(['name' => $plan], $this->getContainer() . '/filter');
            }
        }

        // Set the owner/reseller, if any
        if (isset($vars['owner_all']) && $vars['owner_all']) {
            $this->insert(['owner-all' => null], $this->getContainer());
        } elseif (!empty($vars['owner_id'])) {
            $this->insert(['owner-id' => $vars['owner_id']], $this->getContainer());
        } elseif (!empty($vars['owner_login'])) {
            $this->insert(['owner-login' => $vars['owner_login']], $this->getContainer());
        }

        return $this->api->submit($this->fetch(), $this->getContainer());
    }

    /**
     * Builds the filter section of the XML packet
     *
     * @param array $vars An array containing one of the following fields to filter results on:
     *  - name The name of the service plan
     *  - id The service plan ID
     *  - guid The GUID of the service plan
     *  - external_id The GUID of the service plan in the Panel components
     * @param string $container The container path to insert the filter section into
     */
    private function buildFilter(array $vars, $container)
    {
        $filter = ['filter' => []];

        // Set optional fields
        if (isset($vars['name'])) {
            $filter['filter']['name'] = $vars['name'];
        } elseif (isset($vars['id'])) {
            $filter['filter']['id'] = $vars['id'];
        } elseif (isset($vars['guid'])) {
            $filter['filter']['guid'] = $vars['guid'];
        } elseif (isset($vars['external_id'])) {
            $filter['filter']['external-id'] = $vars['external_id'];
        }

        $this->insert($filter, $container);
    }
}
