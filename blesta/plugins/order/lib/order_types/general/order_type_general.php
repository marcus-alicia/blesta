<?php
/**
 * General Order Type
 *
 * @package blesta
 * @subpackage blesta.plugins.order.lib.order_types
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class OrderTypeGeneral extends OrderType
{
    /**
     * @var string The authors of this order type
     */
    private static $authors = [['name'=>'Phillips Data, Inc.','url'=>'http://www.blesta.com']];

    /**
     * Construct
     */
    public function __construct()
    {
        Language::loadLang('order_type_general', null, dirname(__FILE__) . DS . 'language' . DS);
    }

    /**
     * Returns the name of this order type
     *
     * @return string The common name of this order type
     */
    public function getName()
    {
        return Language::_('OrderTypeGeneral.name', true);
    }

    /**
     * Returns the name and URL for the authors of this order type
     *
     * @return array The name and URL of the authors of this order type
     */
    public function getAuthors()
    {
        return self::$authors;
    }
}
