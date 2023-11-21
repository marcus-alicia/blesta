<?php
Loader::load(dirname(__FILE__) . DS . 'migrator.php');

/**
 * Factory class for creating Migrator objects
 *
 * @package blesta
 * @subpackage blesta.plugins.import_manager.components.migrators
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Migrators
{
    /**
     * Creates and returns an instance of the given migrator
     *
     * @param string $type The type of migrator (dir in /import_manager/components/migrators/)
     * @param string $version The version of the migrator to run (dir in /import_manager/components/migrators/$type/)
     * @param array $params Parameters to pass to the construtor (if any)
     * @return Object An instance of the requested Migrator
     * @throws Exception Thrown if the $type is not recognized or the migrator
     * requested does not exist, or does not inherit from the appropriate parent.
     */
    public static function create($type, $version, array $params = [])
    {
        $name = Loader::toCamelCase($type . str_replace('.', '_', $version));
        $file = Loader::fromCamelCase($name);

        if (!Loader::load(dirname(__FILE__) . DS. $type . DS . $version . DS . $file . '.php')) {
            throw new Exception("Migrator '" . $name . "' does not exist");
        }

        $reflect = new ReflectionClass($name);

        if ($reflect->isSubclassOf('Migrator')) {
            return $reflect->newInstanceArgs($params);
        }

        throw new Exception("Migrator '" . $name . "' is not a recognized migrator");
    }
}
