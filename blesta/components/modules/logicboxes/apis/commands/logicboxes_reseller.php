<?php
/**
 * LogicBoxes Reseller Management
 *
 * @copyright Copyright (c) 2022, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package logicboxes.commands
 */
class LogicboxesReseller
{
    /**
     * @var LogicboxesApi
     */
    private $api;

    /**
     * Sets the API to use for communication
     *
     * @param LogicboxesApi $api The API to use for communication
     */
    public function __construct(LogicboxesApi $api)
    {
        $this->api = $api;
    }

    /**
     * Returns Reseller-specific details such as Branding, Personal Details, etc..
     *
     * @param array $vars An array of input params including:
     *  - reseller-id Reseller ID of the Reseller to be retrieved.
     * @return LogicboxesResponse
     */
    public function details(array $vars = [])
    {
        return $this->api->submit('resellers/details', $vars, 'GET');
    }
}
