<?php
namespace Blesta\PterodactylSDK\Requestors;

class Nodes extends \Blesta\PterodactylSDK\Requestor
{
    /**
     * Fetches a list of nodes from Pterodactyl
     *
     * @return PterodactylResponse
     */
    public function getAll()
    {
        return $this->apiRequest('application/nodes');
    }

    /**
     * Fetches a node from Pterodactyl
     *
     * @param int $nodeId The ID of the node to fetch
     * @return PterodactylResponse
     */
    public function get($nodeId)
    {
        return $this->apiRequest('application/nodes/' . $nodeId);
    }

    /**
     * Adds a node in Pterodactyl
     *
     * @param array $params A list of request parameters including:
     *
     *  - name Then name of the node
     *  - location_id The ID of the location for this node
     *  - public Whether the visibility of node is public (0 or 1)
     *  - fqdn The domain name used to connect to the daemon (can be an IP address if not connecting over HTTPS)
     *  - scheme The transfer protocol (e.g. HTTPS)
     *  - behind_proxy Whether the daemon being used is behind a proxy
     *  - memory The total memory (in MB) available for new servers
     *  - memory_overallocate The percentage amount of memory over the maximum to allocate (set to -1 to skip the overallocation check entirely)
     *  - disk The total memory (in MB) available for new servers
     *  - disk_overallocate The percentage amount of memory over the maximum to allocate (set to -1 to skip the overallocation check entirely)
     *  - daemon_base  The path to the directory where files should be stored
     *  - daemon_listen The port the daemon should listen on
     *  - daemon_sftp The port the daemon sftp-server or standalone SFTP server listen on
     * @return PterodactylResponse
     */
    public function add(array $params)
    {
        return $this->apiRequest('application/nodes', $params, 'POST');
    }

    /**
     * Edits a node in Pterodactyl
     *
     * @param int $nodeId The ID of the node to edit
     * @param array $params A list of request parameters including:
     *
     *  - name Then name of the node
     *  - location_id The ID of the location for this node
     *  - public Whether the visibility of node is public (0 or 1)
     *  - fqdn The domain name used to connect to the daemon (can be an IP address if not connecting over HTTPS)
     *  - scheme The transfer protocol (e.g. HTTPS)
     *  - behind_proxy Whether the daemon being used is behind a proxy
     *  - memory The total memory (in MB) available for new servers
     *  - memory_overallocate The percentage amount of memory over the maximum to allocate (set to -1 to skip the overallocation check entirely)
     *  - disk The total memory (in MB) available for new servers
     *  - disk_overallocate The percentage amount of memory over the maximum to allocate (set to -1 to skip the overallocation check entirely)
     *  - daemon_base  The path to the directory where files should be stored
     *  - daemon_listen The port the daemon should listen on
     *  - daemon_sftp The port the daemon sftp-server or standalone SFTP server listen on
     * @return PterodactylResponse
     */
    public function edit($nodeId, array $params)
    {
        return $this->apiRequest('application/nodes/' . $nodeId, $params, 'PATCH');
    }

    /**
     * Deletes a node in Pterodactyl
     *
     * @param int $nodeId The ID of the node to delete
     * @return PterodactylResponse
     */
    public function delete($nodeId)
    {
        return $this->apiRequest('application/nodes/' . $nodeId, [], 'DELETE');
    }

    /**
     * Fetches all allocations for a node from Pterodactyl
     *
     * @param int $nodeId The ID of the node for which to fetch allocations
     * @return PterodactylResponse
     */
    public function allocationsGetAll($nodeId)
    {
        return $this->apiRequest('application/nodes/' . $nodeId . '/allocations');
    }

    /**
     * Adds a node allocation in Pterodactyl
     *
     * @param int $nodeId The ID of the node for which to add an allocation
     * @param array $params A list of request parameters including:
     *
     *  - ip
     *  - alias
     *  - ports
     * @return PterodactylResponse
     */
    public function allocationsAdd($nodeId, array $params)
    {
        return $this->apiRequest('application/nodes/' . $nodeId . '/allocations/', $params, 'POST');
    }

    /**
     * Deletes a node allocation in Pterodactyl
     *
     * @param int $nodeId The ID of the node for which to delete an allocation
     * @param int $allocationId The ID of the allocation to delete
     * @return PterodactylResponse
     */
    public function allocationsDelete($nodeId, $allocationId)
    {
        return $this->apiRequest('application/nodes/' . $nodeId . '/allocations/' . $allocationId, [], 'DELETE');
    }
}