<?php
namespace Blesta\PterodactylSDK\Requestors;

class Users extends \Blesta\PterodactylSDK\Requestor
{
    /**
     * Fetches a list of users from Pterodactyl
     *
     * @return PterodactylResponse
     */
    public function getAll()
    {
        return $this->apiRequest('application/users');
    }

    /**
     * Fetches a user from Pterodactyl
     *
     * @param int $userId The ID of the user to fetch
     * @return PterodactylResponse
     */
    public function get($userId)
    {
        return $this->apiRequest('application/users/' . $userId);
    }

    /**
     * Fetches a user from Pterodactyl by external ID
     *
     * @param int $externalId The external ID of the user to fetch
     * @return PterodactylResponse
     */
    public function getByExternalID($externalId)
    {
        return $this->apiRequest('application/users/external/' . $externalId);
    }

    /**
     * Adds a user in Pterodactyl
     *
     * @param array $params A list of request parameters including:
     *
     *  - username The username for the accoount
     *  - email The email address for the account
     *  - first_name The user's first name
     *  - last_name The user's last name
     *  - password A plain text input of the desired password
     * @return PterodactylResponse
     */
    public function add(array $params)
    {
        return $this->apiRequest('application/users', $params, 'POST');
    }

    /**
     * Edits a user in Pterodactyl
     *
     * @param int $userId The ID of the user to edit
     * @param array $params A list of request parameters including:
     *
     *  - username The username for the accoount
     *  - email The email address for the account
     *  - first_name The user's first name
     *  - last_name The user's last name
     *  - password A plain text input of the desired password
     * @return PterodactylResponse
     */
    public function edit($userId, array $params)
    {
        return $this->apiRequest('application/users/' . $userId, $params, 'PATCH');
    }

    /**
     * Deletes a user in Pterodactyl
     *
     * @param int $userId The ID of the user to delete
     * @return PterodactylResponse
     */
    public function delete($userId)
    {
        return $this->apiRequest('application/users/' . $userId, [], 'DELETE');
    }

    /**
     * Fetches a user from Pterodactyl by email address
     *
     * @param string $email The email address of the user to fetch
     * @return PterodactylResponse
     */
    public function getByEmail($email)
    {
        return $this->apiRequest('application/users?filter[email]=' . $email);
    }
}