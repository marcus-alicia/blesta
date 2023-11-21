<?php
Loader::load(dirname(__FILE__) . DS . 'lib' . DS . 'fraud_detect.php');

/**
 * Antifraud Factory
 *
 * @package blesta
 * @subpackage blesta.plugins.order.components.antifraud
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Antifraud
{
    /**
     * Creates and returns an instance of the given antifraud component
     *
     * @param string $antifraud The name of the Antifraud component to load
     * @param array $params Parameters to pass to the construtor (if any)
     * @return Object An instance of the requested Antifraud component
     * @throws Exception Thrown if $antifraud does not exist or is invalid
     */
    public static function create($antifraud, array $params = [])
    {
        $antifraud_name = Loader::toCamelCase($antifraud);
        $antifraud_file = Loader::fromCamelCase($antifraud_name);

        if (!Loader::load(dirname(__FILE__) . DS . $antifraud_file . DS . $antifraud_file . '.php')) {
            throw new Exception("Antifraud '" . $antifraud_name . "' does not exist");
        }


        $reflect = new ReflectionClass($antifraud_name);

        if ($reflect->implementsInterface('FraudDetect')) {
            return $reflect->newInstanceArgs($params);
        }

        throw new Exception("Antifraud '" . $antifraud_name . "' is not a recognized Antifraud component");
    }
}
