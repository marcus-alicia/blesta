<?php
/**
 * LogicBoxes Contact CA Management
 *
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package logicboxes.commands
 */
class LogicboxesContactsDotca
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
     * Gets the Registrant Agreement mandated by the Canadian Internet Registration Authority (CIRA).
     *
     * @return LogicboxesResponse
     */
    public function registrantagreement(array $vars)
    {
        return $this->api->submit('contacts/dotca/registrantagreement', $vars, 'GET');
    }
}
