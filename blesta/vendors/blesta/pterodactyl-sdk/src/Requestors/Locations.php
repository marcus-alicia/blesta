<?php
namespace Blesta\PterodactylSDK\Requestors;

class Locations extends \Blesta\PterodactylSDK\Requestor
{
    /**
     * Fetches a list of locations from Pterodactyl
     *
     * @return PterodactylResponse
     */
    public function getAll()
    {
        return $this->apiRequest('application/locations');
    }

    /**
     * Fetches a location from Pterodactyl
     *
     * @param int $locationId The ID of the location to fetch
     * @return PterodactylResponse
     */
    public function get($locationId)
    {
        return $this->apiRequest('application/locations/' . $locationId);
    }

    /**
     * Adds a location in Pterodactyl
     *
     * @param array $params A list of request parameters including:
     *
     *  - shortcode The shortcode of the location
     *  - description A description of the location
     * @return PterodactylResponse
     */
    public function add(array $params)
    {
        return $this->apiRequest('application/locations', $params, 'POST');
    }

    /**
     * Edits a location in Pterodactyl
     *
     * @param int $locationId The ID of the location to edit
     * @param array $params A list of request parameters including:
     *
     *  - shortcode The shortcode of the location
     *  - description A description of the location
     * @return PterodactylResponse
     */
    public function edit($locationId, array $params)
    {
        return $this->apiRequest('application/locations/' . $locationId, $params, 'PATCH');
    }

    /**
     * Deletes a location in Pterodactyl
     *
     * @param int $locationId The ID of the location to delete
     * @return PterodactylResponse
     */
    public function delete($locationId)
    {
        return $this->apiRequest('application/locations/' . $locationId, [], 'DELETE');
    }
}