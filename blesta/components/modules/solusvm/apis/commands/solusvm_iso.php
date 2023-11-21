<?php
/**
 * SolusVM ISO Management
 *
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package solusvm.commands
 */
class SolusvmIso
{
    /**
     * @var SolusvmApi
     */
    private $api;

    /**
     * Sets the API to use for communication
     *
     * @param SolusvmApi $api The API to use for communication
     */
    public function __construct(SolusvmApi $api)
    {
        $this->api = $api;
    }

    /**
     * List ISOs
     *
     * @param array $vars An array of input params including:
     *  - type (xen hvm, kvm)
     * @return SolusvmResponse
     */
    public function getList(array $vars)
    {
        return $this->api->submit('listiso', $vars);
    }
}
