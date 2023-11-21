<?php

Loader::load(COMPONENTDIR . 'plugins' . DS . 'lib' . DS . 'plugin.php');

/**
 * Factory class for creating Plugin handler objects
 *
 * @package blesta
 * @subpackage blesta.components.plugins
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Plugins
{

    /**
     * Creates and returns an instance of the given plugin handler
     *
     * @param string $plugin_name The name of the Plugin handler to load
     * @return Object An instance of the requested Plugin handler
     * @throws Exception Thrown if the $plugin_name is not a recognized plugin handler or does not inherit
     *  from the appropriate parent.
     */
    public static function create($plugin_name)
    {
        $plugin_name = Loader::toCamelCase($plugin_name);
        $plugin_handler = $plugin_name . 'Plugin';
        $plugin_file = Loader::fromCamelCase($plugin_name);
        $plugin_handler_file = Loader::fromCamelCase($plugin_handler);

        if (!Loader::load(PLUGINDIR . $plugin_file . DS . $plugin_handler_file . '.php')) {
            throw new Exception("Plugin handler '" . $plugin_handler . "' does not exist");
        }

        $reflect = new ReflectionClass($plugin_handler);

        if ($reflect->isSubclassOf('Plugin')) {
            return new $plugin_handler();
        }

        throw new Exception("Plugin handler '" . $plugin_handler . "' is not a recognized plugin handler");
    }
}
