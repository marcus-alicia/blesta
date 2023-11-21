<?php
/**
 * Security factory that wraps PHPSecLib.
 *
 * @package blesta
 * @subpackage blesta.components.security
 * @copyright Copyright (c) 2010-2018, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Security
{
    /**
     * Creates a new instance of the given PHPSecLib library
     *
     * @param string $lib The Library to load from (directory/namespace path) within phpseclib
     * @param string $class The class name
     * @param array $params Parameters to pass to the construtor (if any)
     * @return object Returns an instance of the given class
     */
    public static function create($lib, $class, array $params = [])
    {
        try {
            $reflect = new ReflectionClass('phpseclib\\' . $lib . '\\' . $class);
            return $reflect->newInstanceArgs($params);
        } catch (Throwable $e) {
            throw new Exception('Unable to load library ' . $lib . '\\' . $class);
        }
    }
}
