<?php
/**
 * Enom API request funnel
 *
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package enom.commands
 */
class EnomAll
{
    /**
     * @var EnomApi
     */
    private $api;

    /**
     * Sets the API to use for communication
     *
     * @param EnomApi $api The API to use for communication
     */
    public function __construct(EnomApi $api)
    {
        $this->api = $api;
    }

    /**
     * Returns the response from the Enom API
     *
     * @param array $vars An array of input params
     * @return EnomResponse
     */
    public function __call($command, array $vars)
    {
        return $this->api->submit($command, $vars[0]);
    }
}
