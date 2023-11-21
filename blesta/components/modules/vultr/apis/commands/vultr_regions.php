<?php
/**
 * Vultr regions management.
 *
 * @package blesta
 * @subpackage blesta.components.modules.vultr
 * @copyright Copyright (c) 2023, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class VultrRegions
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
     * Retrieve a list of the VPSPLANIDs currently available in this location.
     *
     * @param array $params An array containing the following arguments:
     *     - region-id: Location to check availability.
     *     - type: Filter the results by type.
     * @return VultrResponse An object containing the api response
     */
    public function availability($params = [])
    {
        return $this->api->apiRequest(
            '/regions/' . ($params['region-id'] ?? '') . '/availability',
            ['type' => $params['type'] ?? 'all']
        );
    }

    /**
     * Retrieve a list of the METALPLANIDs currently available in this location.
     *
     * @param array $params An array containing the following arguments:
     *     - region-id: Location to check availability.
     * @return VultrResponse An object containing the api response
     */
    public function availabilityBaremetal($params = [])
    {
        return $this->availability([
            'region-id' => $params['region-id'] ?? '',
            'type' => 'vbm'
        ]);
    }

    /**
     * Retrieve a list of the vc2 VPSPLANIDs currently available in this location.
     *
     * @param array $params An array containing the following arguments:
     *     - region-id: Location to check availability.
     * @return VultrResponse An object containing the api response
     */
    public function availabilityVc2($params = [])
    {
        return $this->availability([
            'region-id' => $params['region-id'] ?? '',
            'type' => 'vc2'
        ]);
    }

    /**
     * Retrieve a list of the vdc2 VPSPLANIDs currently available in this location.
     *
     * @param array $params An array containing the following arguments:
     *     - region-id: Location to check availability.
     * @return VultrResponse An object containing the api response
     */
    public function availabilityVdc2($params = [])
    {
        return $this->availability([
            'region-id' => $params['region-id'] ?? '',
            'type' => 'vdc'
        ]);
    }

    /**
     * Retrieve a list of all active regions.
     *
     * @param array $params An array containing the following arguments:
     *     - per_page: Number of items requested per page. Default is 100 and Max is 500. (optional)
     *     - cursor: Cursor for paging. See Meta and Pagination. (optional)
     * @return VultrResponse An object containing the api response
     */
    public function listRegions($params = [])
    {
        return $this->api->apiRequest('/regions', $params);
    }
}
