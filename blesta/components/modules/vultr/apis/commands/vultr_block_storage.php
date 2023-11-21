<?php
/**
 * Vultr block storage management.
 *
 * @package blesta
 * @subpackage blesta.components.modules.vultr
 * @copyright Copyright (c) 2023, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class VultrBlockStorage
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
     * Attach a block storage subscription to a VPS subscription.
     *
     * @param array $params An array containing the following arguments:
     *     - block-id: The Block Storage id.
     *     - instance_id: Attach the Block Storage to this Instance id.
     *     - live: Attach Block Storage without restarting the Instance.
     * @return VultrResponse An object containing the api response
     */
    public function attach($params = [])
    {
        return $this->api->apiRequest(
            '/blocks/' . ($params['block-id'] ?? '') . '/attach',
            ['instance_id' => ($params['instance_id'] ?? ''), 'live' => ($params['live'] ?? false)],
            'POST'
        );
    }

    /**
     * Create a block storage subscription.
     *
     * @param array $params An array containing the following arguments:
     *     - region: The Region id where the Block Storage will be created.
     *     - size_gb: Size (in GB) of this subscription.
     *     - label: Text label that will be associated with the subscription.
     *     - block_type: An optional parameter, that determines on the type of block storage
     *          volume that will be created. Soon to become a required parameter.
     * @return VultrResponse An object containing the api response
     */
    public function create($params = [])
    {
        if (empty($params['block_type'])) {
            $params['block_type'] = 'storage_opt';
        }

        return $this->api->apiRequest('/blocks', $params, 'POST');
    }

    /**
     * Delete a block storage subscription.
     *
     * @param array $params An array containing the following arguments:
     *     - block-id: The Block Storage id.
     * @return VultrResponse An object containing the api response
     */
    public function delete($params = [])
    {
        return $this->api->apiRequest('/blocks/' . ($params['block-id'] ?? ''), [], 'DELETE');
    }

    /**
     * Detach a block storage subscription from the currently attached instance.
     *
     * @param array $params An array containing the following arguments:
     *     - block-id: The Block Storage id.
     *     - live: Attach Block Storage without restarting the Instance.
     * @return VultrResponse An object containing the api response
     */
    public function detach($params = [])
    {
        return $this->api->apiRequest(
            '/blocks/' . ($params['block-id'] ?? '') . '/detach',
            ['live' => ($params['live'] ?? false)],
            'POST'
        );
    }

    /**
     * Set the label of a block storage subscription.
     *
     * @param array $params An array containing the following arguments:
     *     - block-id: The Block Storage id.
     *     - label: Text label that will be shown in the control panel.
     * @return VultrResponse An object containing the api response
     */
    public function setLabel($params = [])
    {
        return $this->api->apiRequest(
            '/blocks/' . ($params['block-id'] ?? ''),
            ['label' => ($params['label'] ?? '')],
            'PATCH'
        );
    }

    /**
     * Retrieve a list of any active block storage subscriptions on this account.
     *
     * @param array $params An array containing the following arguments:
     *     - per_page: Number of items requested per page. Default is 100 and Max is 500. (optional)
     *     - cursor: Cursor for paging. See Meta and Pagination. (optional)
     * @return VultrResponse An object containing the api response
     */
    public function listBlockStorage($params = [])
    {
        return $this->api->apiRequest('/blocks', $params);
    }

    /**
     * Resize the block storage volume to a new size.
     *
     * @param array $params An array containing the following arguments:
     *     - block-id: The Block Storage id.
     *     - size_gb: New size (in GB) of the block storage subscription.
     * @return VultrResponse An object containing the api response
     */
    public function resize($params = [])
    {
        return $this->api->apiRequest(
            '/blocks/' . ($params['block-id'] ?? ''),
            ['size_gb' => ($params['size_gb'] ?? '')],
            'PATCH'
        );
    }
}
