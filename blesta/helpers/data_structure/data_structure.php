<?php
/**
 * Factory class for creating Data Structure Helper objects
 *
 * @package minPHP
 * @subpackage minPHP.helpers.data_structure
 */
class DataStructure
{
    /**
     * Returns an instance of the requested helper
     *
     * @param string $structure The name of the data structure helper to instantiate
     * @return mixed A helper whose purpose is to manipulate data structures of the type $structure
     * @throws Exception Thrown when the helper does not exist
     */
    public static function create($structure)
    {
        $structure = Loader::fromCamelCase($structure);
        $structure_file = 'data_structure_' . $structure;
        $structure_name = Loader::toCamelCase($structure_file);

        if (!Loader::load(dirname(__FILE__) . DS . $structure . DS . $structure_file . '.php')) {
            throw new Exception("Data structure helper '" . $structure_name . "' does not exist.");
        }

        if (class_exists($structure_name)) {
            return new $structure_name();
        }

        throw new Exception("The helper '" . $structure_name . "' is not a recognized data structure helper.");
    }
}
