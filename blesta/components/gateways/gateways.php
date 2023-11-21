<?php
Loader::load(COMPONENTDIR . 'gateways' . DS . 'lib' . DS . 'gateway.php');
Loader::load(COMPONENTDIR . 'gateways' . DS . 'lib' . DS . 'nonmerchant_gateway.php');
Loader::load(COMPONENTDIR . 'gateways' . DS . 'lib' . DS . 'merchant_gateway.php');
Loader::load(COMPONENTDIR . 'gateways' . DS . 'lib' . DS . 'merchant_ach.php');
Loader::load(COMPONENTDIR . 'gateways' . DS . 'lib' . DS . 'merchant_cc.php');
Loader::load(COMPONENTDIR . 'gateways' . DS . 'lib' . DS . 'merchant_ach_offsite.php');
Loader::load(COMPONENTDIR . 'gateways' . DS . 'lib' . DS . 'merchant_ach_verification.php');
Loader::load(COMPONENTDIR . 'gateways' . DS . 'lib' . DS . 'merchant_cc_offsite.php');
Loader::load(COMPONENTDIR . 'gateways' . DS . 'lib' . DS . 'merchant_ach_form.php');
Loader::load(COMPONENTDIR . 'gateways' . DS . 'lib' . DS . 'merchant_cc_form.php');

/**
 * Factory class for creating Gateway objects
 *
 * @package blesta
 * @subpackage blesta.components.gateways
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Gateways
{
    /**
     * @var array The types of Gateways supported
     */
    private static $gw_types = ['merchant', 'nonmerchant'];

    /**
     * Creates and returns an instance of the given gateway
     *
     * @param string $gw_name The name of the Gateway to load
     * @param string $type The type of gateway to load. Accepted types are listed in Gateways::$gw_name
     * @return Object An instance of the requested Gateway
     * @throws Exception Thrown if the $type is not recognized or the gateway
     * requested does not exist, or does not inherit from the appropriate parent.
     */
    public static function create($gw_name, $type)
    {
        $gw_name = Loader::toCamelCase($gw_name);
        $gw_file = Loader::fromCamelCase($gw_name);

        if (!in_array($type, self::$gw_types)) {
            throw new Exception("Gateway type '" . $type . "' is not a recognized gateway type");
        }

        if (!Loader::load(COMPONENTDIR . 'gateways' . DS . $type . DS. $gw_file . DS . $gw_file . '.php')) {
            throw new Exception("Gateway '" . $gw_name . "' does not exist");
        }

        $reflect = new ReflectionClass($gw_name);

        if (($reflect->isSubclassOf('MerchantGateway') &&
            (
                $reflect->implementsInterface('MerchantAch') ||
                $reflect->implementsInterface('MerchantAchOffsite') ||
                $reflect->implementsInterface('MerchantCc') ||
                $reflect->implementsInterface('MerchantCcOffsite')
            )) || $reflect->isSubclassOf('NonmerchantGateway')
        ) {
            return new $gw_name();
        }

        throw new Exception("Gateway '" . $gw_name . "' is not a recognized gateway");
    }
}
