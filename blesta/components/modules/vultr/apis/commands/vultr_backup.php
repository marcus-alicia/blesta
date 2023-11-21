<?php
/**
 * Vultr backup management.
 *
 * @package blesta
 * @subpackage blesta.components.modules.vultr
 * @copyright Copyright (c) 2023, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class VultrBackup
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
     * Get a specific backup.
     *
     * @param array $params An array containing the following arguments:
     *     - backup-id: The Backup id.
     * @return VultrResponse An object containing the api response
     */
    public function get($params = [])
    {
        return $this->api->apiRequest('/backups/' . ($params['backup-id'] ?? ''));
    }

    /**
     * List all backups on the current account.
     *
     * @param array $params An array containing the following arguments:
     *     - instance_id: Filter result set to only contain backups of this instance. (optional)
     *     - per_page: Number of items requested per page. Default is 100 and Max is 500. (optional)
     *     - cursor: Cursor for paging. See Meta and Pagination. (optional)
     * @return VultrResponse An object containing the api response
     */
    public function listBackups($params = [])
    {
        return $this->api->apiRequest('/backups', $params);
    }
}
