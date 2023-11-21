<?php
/**
 * Vultr plans management.
 *
 * @package blesta
 * @subpackage blesta.components.modules.vultr
 * @copyright Copyright (c) 2023, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class VultrPlans
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
     * Retrieve a list of all active plans. Plans that are no longer available will not be shown.
     *
     * @param array $params An array containing the following arguments:
     *     - type: Filter the results by type.
     *     - per_page: Number of items requested per page. Default is 100 and Max is 500. (optional)
     *     - cursor: Cursor for paging. See Meta and Pagination. (optional)
     *     - os: Filter the results by operating system. (optional)
     * @return VultrResponse An object containing the api response
     */
    public function listPlans($params = [])
    {
        return $this->api->apiRequest('/plans', $params);
    }

    /**
     * Retrieve a list of all active bare metal plans.
     *
     * @param array $params An array containing the following arguments:
     *     - per_page: Number of items requested per page. Default is 100 and Max is 500. (optional)
     *     - cursor: Cursor for paging. See Meta and Pagination. (optional)
     * @return VultrResponse An object containing the api response
     */
    public function listBaremetalPlans($params = [])
    {
        return $this->api->apiRequest('/plans-metal', $params);
    }

    /**
     * Retrieve a list of all active vc2 plans.
     *
     * @return VultrResponse An object containing the api response
     */
    public function listVc2()
    {
        return $this->listPlans(['type' => 'vc2']);
    }

    /**
     * Retrieve a list of all active vdc2 plans.
     *
     * @return VultrResponse An object containing the api response
     */
    public function listVdc2()
    {
        return $this->listPlans(['type' => 'vdc']);
    }
}
