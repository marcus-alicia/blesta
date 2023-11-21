<?php
/**
 * SolusVM Plan Management
 *
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package solusvm.commands
 */
class SolusvmPlans
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
     * List plans
     *
     * @param array $vars An array of input params including:
     *  - type (openvz, xen, xen hvm, kvm)
     * @return SolusvmResponse
     */
    public function getList(array $vars)
    {
        return $this->api->submit('listplans', $vars);
    }

    /**
     * List all plans and their details (e.g. ram, disk, bandwidth)
     *
     * @param array $vars An array of input params including:
     *  - type (openvz, xen, xen hvm, kvm)
     * @return SolusvmResponse
     */
    public function getDetails(array $vars)
    {
        return $this->api->submit('list-plans', $vars);
    }
}
