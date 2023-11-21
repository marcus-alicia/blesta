<?php
/**
 * LogicBoxes DNS Activation
 *
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package logicboxes.commands
 */
class LogicboxesDns
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
     * Activates the DNS service
     *
     * @param array $vars An array of input params including:
     *  - order-id Order Id of the Order for which the DNS service is to be activated
     * @return LogicboxesResponse
     */
    public function activate(array $vars)
    {
        return $this->api->submit('dns/activate', $vars);
    }
}
