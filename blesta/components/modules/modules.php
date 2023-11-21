<?php
Loader::load(COMPONENTDIR . 'modules' . DS . 'module.php');
Loader::load(COMPONENTDIR . 'modules' . DS . 'registrar_module.php');
Loader::load(COMPONENTDIR . 'modules' . DS . 'module_field.php');
Loader::load(COMPONENTDIR . 'modules' . DS . 'module_fields.php');

/**
 * Factory class for creating Module objects
 *
 * @package blesta
 * @subpackage blesta.components.modules
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Modules
{
    /**
     * Returns an instance of the requested module
     *
     * @param string $module_name The name of the module to instantiate
     * @return mixed An object of type $module_name
     * @throws Exception Thrown when the module does not exists or does not inherit from the appropriate parent
     */
    public static function create($module_name)
    {
        $module_name = Loader::toCamelCase($module_name);
        $module_file = Loader::fromCamelCase($module_name);

        if (!Loader::load(COMPONENTDIR . 'modules' . DS . $module_file . DS . $module_file . '.php')) {
            throw new Exception("Module '" . $module_name . "' does not exist.");
        }

        if (class_exists($module_name) && is_subclass_of($module_name, 'Module')) {
            return new $module_name();
        }

        throw new Exception("Module '" . $module_name . "' is not a recognized module.");
    }
}
