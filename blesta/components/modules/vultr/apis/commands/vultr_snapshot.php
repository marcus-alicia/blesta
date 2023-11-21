<?php
/**
 * Vultr snapshot management.
 *
 * @package blesta
 * @subpackage blesta.components.modules.vultr
 * @copyright Copyright (c) 2023, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class VultrSnapshot
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
     * List all snapshots on the current account.
     *
     * @param array $params An array containing the following arguments:
     *     - description: Filter the list of Snapshots by description
     *     - per_page: Number of items requested per page. Default is 100 and Max is 500. (optional)
     *     - cursor: Cursor for paging. See Meta and Pagination. (optional)
     * @return VultrResponse An object containing the api response.
     */
    public function listSnapshots($params = [])
    {
        return $this->api->apiRequest('/snapshots', $params);
    }

    /**
     * Create a snapshot from an existing virtual machine.
     *
     * @param array $params An array containing the following arguments:
     *     - instance_id: Unique identifier for this subscription.
     *     - description: Description of snapshot contents.
     * @return VultrResponse An object containing the api response.
     */
    public function create($params = [])
    {
        return $this->api->apiRequest('/snapshots', $params, 'POST');
    }

    /**
     * Destroy (delete) a snapshot.
     *
     * @param array $params An array containing the following arguments:
     *     - snapshot-id: Unique identifier for this snapshot.
     * @return VultrResponse An object containing the api response.
     */
    public function destroy($params = [])
    {
        return $this->api->apiRequest('/snapshots/' . ($params['snapshot-id'] ?? ''), [], 'DELETE');
    }
}
