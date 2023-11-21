<?php
/**
 * Plesk Reseller Plan management
 *
 * From API RPC version 1.6.3.0 and greater
 *
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @package plesk.commands
 */
class PleskResellerPlans extends PleskPacket
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
    private $base_container = '/reseller-plan';

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
        $this->insert(['reseller-plan' => null]);
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
     * Retrieves the reseller plans and information
     * An owner MUST be specified unless the API version is 1.6.3.4 or greater.
     * 1.6.3.4 allows no owner to be specified, which will receive plans from everyone
     *
     * @param array $vars An array of input params including:
     *  - filter An array of one of the following fields to identify the reseller plans to fetch:
     *      - id The ID of a reseller plan
     *      - name The name of a reseller plan
     *      - all True to fetch all reseller plans
     *      - guid The reseller plan GUID
     *      - external_id The reseller plan ID in the Panel components
     *  - plans An array of plan names to fetch information for (optional, default empty array to fetch all plans)
     *  - owner_id The ID of the owner/reseller that the plans belong to, or that you wish to fetch
     *      (optional, will be used if owner_login is also specified)
     *  - owner_login The login name of the owner/reseller that the plans belong to, or that you
     *      wish to fetch (optional)
     *  - owner_all Set to true to fetch plans for all owners/resellers
     *      (optional, requires version 1.6.3.4; will be used if specified)
     * @param array $settings A list of key/value options of additional settings to fetch
     *  - limits True to fetch the plan limits
     *  - permissions True to fetch plan permissions
     *  - ip_pool True to fetch IP pool settings
     * @return PleskResponse
     */
    public function get(array $vars = [])
    {
        // Set the container for this API request
        $this->insert(['get' => null], $this->getContainer());
        $this->setContainer($this->base_container . '/get');

        // Build filter options
        $filter = (isset($vars['filter']) ? $vars['filter'] : []);
        $this->buildFilter($filter, $this->getContainer());

        // Set whether to fetch any additional settings
        $settings = (isset($vars['settings']) ? $vars['settings'] : []);
        if (isset($settings['limits']) && $settings['limits']) {
            $this->insert(['limits' => null], $this->getContainer());
        }
        if (isset($settings['permissions']) && $settings['permissions']) {
            $this->insert(['permissions' => null], $this->getContainer());
        }
        if (isset($settings['ip_pool']) && $settings['ip_pool']) {
            $this->insert(['ip-pool' => null], $this->getContainer());
        }

        return $this->api->submit($this->fetch(), $this->getContainer());
    }

    /**
     * Builds the filter section of the XML packet
     *
     * @param array $vars A list of input params to filter on, including one of:
     *  - id The ID of a reseller plan
     *  - name The name of a reseller plan
     *  - all True to fetch all reseller plans
     *  - guid The reseller plan GUID
     *  - external_id The reseller plan ID in the Panel components
     * @param string $container The container path to insert this filter section into
     *  (optional, default to the base container)
     */
    private function buildFilter(array $vars, $container = null)
    {
        $container = (empty($container) ? $this->getContainer() : $container);

        // Build filter XML container
        $this->insert(['filter' => null], $container);
        $filter_path = $container . '/filter';

        // Specify the filter options
        if (isset($vars['id'])) {
            $this->insert(['id' => $vars['id']], $filter_path);
        } elseif (isset($vars['name'])) {
            $this->insert(['name' => $vars['name']], $filter_path);
        } elseif (isset($vars['all']) && $vars['all']) {
            $this->insert(['all' => null], $filter_path);
        } elseif (isset($vars['guid'])) {
            $this->insert(['guid' => $vars['guid']], $filter_path);
        } elseif (isset($vars['external_id'])) {
            $this->insert(['external-id' => $vars['external_id']], $filter_path);
        }
    }
}
