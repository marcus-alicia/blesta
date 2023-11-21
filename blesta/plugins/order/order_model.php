<?php
/**
 * Order System Parent Model
 *
 * @package blesta
 * @subpackage blesta.plugins.order
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class OrderModel extends AppModel
{
    public function __construct()
    {
        parent::__construct();

        // Auto load language for these models
        Language::loadLang([Loader::fromCamelCase(get_class($this))], null, dirname(__FILE__) . DS . 'language' . DS);
    }

    /**
     * Loads the given order type
     *
     * @param string $type The type of order type to load
     * @return object An object of OrderType* where * is the order type to load (e.g. OrderTypeGeneral)
     */
    public function loadOrderType($type)
    {
        Loader::load(PLUGINDIR . 'order' . DS . 'lib' . DS . 'order_type.php');

        $type = Loader::fromCamelCase($type);
        $type_class = Loader::toCamelCase('order_type_' . $type);

        if (!Loader::load(PLUGINDIR . 'order' . DS . 'lib' . DS . 'order_types'
            . DS . $type . DS . 'order_type_' . $type . '.php')
        ) {
            throw new Exception("Order Type '" . $type_class . "' does not exist.");
        }

        if (class_exists($type_class) && is_subclass_of($type_class, 'OrderType')) {
            return new $type_class();
        }

        throw new Exception("Order Type '" . $type_class . "' is not a recognized order type.");
    }

    /**
     * Returns the HTML content to render for the given order form type when adding/editing an order form
     *
     * @param string $type The type of order form
     * @param array $vars An array of form data to use to populate the order type fields
     * @return string The HTML content to render when adding/editing an order form
     */
    public function getOrderTypeFields($type, array $vars = null)
    {
        $order_type = $this->loadOrderType($type);

        return $order_type->getSettings($vars);
    }
}
