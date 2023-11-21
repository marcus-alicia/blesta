<?php
/**
 * Order System type controller
 *
 * @package blesta
 * @subpackage blesta.plugins.order.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class OrderType extends OrderFormController
{
    /**
     * Executes the handleRequest() method on the order type object for the given order type,
     * allowing the order type object to accept HTTP requests.
     */
    public function index()
    {
        if ($this->order_type) {
            $this->order_type->handleRequest($this->get, $this->post, $this->files);
        }
        return false;
    }
}
