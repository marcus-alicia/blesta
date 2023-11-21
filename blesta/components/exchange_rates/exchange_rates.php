<?php
/**
 * Currency Exchange Rate factory
 *
 * @package blesta
 * @subpackage blesta.components.exchange_rates
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
Loader::load(COMPONENTDIR . 'exchange_rates' . DS . 'exchange_rate.php');

class ExchangeRates
{

    /**
     * Creates a new instance of the given Exchange Rate processor
     *
     * @param string $processor The exchange rate process to initialize
     * @param array $params Parameters to pass to the construtor (if any)
     * @return object Returns an instance of the given class
     */
    public static function create($processor, array $params = [])
    {
        $processor = Loader::toCamelCase($processor);
        $processor_file = Loader::fromCamelCase($processor);

        if (!Loader::load(COMPONENTDIR . 'exchange_rates' . DS . $processor_file . DS . $processor_file . '.php')) {
            throw new Exception("Exchange rate processor '" . $processor . "' does not exist.");
        }

        if (class_exists($processor) && is_subclass_of($processor, 'ExchangeRate')) {
            $reflect = new ReflectionClass($processor);
            return $reflect->newInstanceArgs($params);
        }

        throw new Exception("Processor '" . $processor . "' is not a exchange rate processor.");
    }
}
