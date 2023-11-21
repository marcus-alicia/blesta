<?php

/**
 * Handles the loading of various files and objects
 */
class Loader
{
    private static $paths;
    private static $subPaths = ['models', 'controllers', 'components', 'helpers'];

    /**
     * Protected constructor to prevent instance creation
     */
    protected function __construct()
    {
        // Nothing to do
    }

    /**
     * Fetch the instance of Loader
     *
     * @staticvar self $instance
     * @return self
     */
    public static function get()
    {
        static $instance = null;

        if (null === $instance) {
            $instance = new static();
        }

        return $instance;
    }

    /**
     * Set directories to autoload from
     *
     * @param array $paths
     */
    public static function setDirectories(array $paths)
    {
        self::$paths = $paths;
    }

    /**
     * Autoload classes
     *
     * @param string $class
     * @param string $type The type of class to load (helpers, components, models, controllers, null for all)
     * @return bool True if loaded, false otherwise
     */
    public static function autoload($class, $type = null)
    {
        // Skip namespaces
        if (strpos($class, "\\") !== false) {
            return false;
        }

        $class_parts = self::parseClassName($class);
        $class = $class_parts['class'];
        $plugin = $class_parts['plugin'];

        $paths = self::$paths;
        if ($type !== null) {
            $paths = [];
            if (array_key_exists($type, self::$paths)) {
                $paths[] = self::$paths[$type];
            }
        }

        if ($plugin !== null && array_key_exists('plugins', self::$paths)) {
            if ($type !== null) {
                $paths = [self::$paths['plugins'] . $plugin . $type . DIRECTORY_SEPARATOR];
            } else {
                $paths = [self::$paths['plugins'] . $plugin];
                foreach (self::$subPaths as $dir) {
                    $paths[] = $paths[0] . $dir . DIRECTORY_SEPARATOR;
                }
            }
        }

        $class_file = self::fromCamelCase($class);
        if (substr($class, 0, 1) === '_') {
            $class_file = substr($class, 1);
        }

        $file_name = $class_file . '.php';

        // Class file is in the directory or a subdirectory with a similar name
        foreach ($paths as $path) {
            if (self::load($path . $file_name)) {
                return true;
            } elseif (self::load($path . $class_file . DIRECTORY_SEPARATOR . $file_name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Prase a class name from PluginName.ClassName into its components
     *
     * @param string $class
     * @return array
     */
    private static function parseClassName($class)
    {
        $plugin = null;
        if (($pos = strpos($class, '.'))) {
            $plugin = self::fromCamelCase(substr($class, 0, $pos)) . DIRECTORY_SEPARATOR;
            $class = substr($class, $pos + 1);
        }

        return compact('plugin', 'class');
    }

    /**
     * Loads models, which may or may not exist within a plugin of the same
     * name. First looks in the plugin directory, if no match is found, looks
     * in the models directory.
     *
     * @param object $parent The object to which to attach the given models
     * @param array $models An array of models to load and initialize
     */
    public static function loadModels($parent, $models)
    {
        self::loadInstances($models, [$parent], 'models');
    }

    /**
     * Loads the given components, attaching them to the given parent object.
     *
     * @param object $parent The parent to which to attach the given components
     * @param array $components An array of components and [optionally] their parameters
     */
    public static function loadComponents($parent, $components)
    {
        self::loadInstances($components, [$parent], 'components');
    }

    /**
     * Loads the given helpers, attaching them to the given parent object.
     *
     * @param object $parent The parent to which to attach the given helpers
     * @param array $helpers An array of helpers and [optionally] their parameters
     */
    public static function loadHelpers($parent, $helpers)
    {
        $parents = [$parent];
        if (isset($parent->view) && $parent->view instanceof View) {
            $parents[] = $parent->view;
        }
        if (isset($parent->structure) && $parent->structure instanceof View) {
            $parents[] = $parent->structure;
        }
        self::loadInstances($helpers, $parents, 'helpers');
    }

    /**
     * Convert a string to "CamelCase" from "file_case"
     *
     * @param string $str the string to convert
     * @return string the converted string
     */
    public static function toCamelCase($str)
    {
        if (isset($str[0])) {
            $str[0] = strtoupper($str[0]);
        }

        return preg_replace_callback(
            '/_([a-z])/',
            function ($c) {
                return strtoupper($c[1]);
            },
            $str
        );
    }

    /**
     * Convert a string to "file_case" from "CamelCase".
     *
     * @param string $str the string to convert
     * @return string the converted string
     */
    public static function fromCamelCase($str)
    {
        if (isset($str[0])) {
            $str[0] = strtolower($str[0]);
        }

        return preg_replace_callback(
            '/([A-Z])/',
            function ($c) {
                return '_' . strtolower($c[1]);
            },
            $str
        );
    }

    /**
     * Attempts to include the given file, if it exists.
     *
     * @param string $file The file to include
     * @return bool Returns true if the file exists and could be included, false otherwise
     */
    public static function load($file)
    {
        if (file_exists($file)) {
            return (bool) include_once $file;
        }
        return false;
    }

    /**
     * Load new instances of the given classes. Class can be in the format of
     * 'ClassName' or 'PluginName.ClassName'.
     *
     * @param array $classes An array of classes or class/param pairs
     * @param array $set_in An array of objects to set the loaded class into
     * @param string $type The type of class to load (helpers, components, models, controllers, null for all)
     */
    private static function loadInstances(array $classes, array $set_in = [], $type = null)
    {
        foreach ($classes as $key => $value) {
            if (is_array($value)) {
                $class = self::toCamelCase($key);
            } else {
                $class = self::toCamelCase($value);
                $value = [];
            }

            // Include plugin model/controller base classes, if they exist
            // e.g. when a plugin loads another plugin
            $class_parts = self::parseClassName($class);
            $class_name = $class_parts['class'];
            $plugin = $class_parts['plugin'];

            if ($plugin !== null) {
                $plugin_name = self::toCamelCase(trim($plugin, DIRECTORY_SEPARATOR));
                self::autoload($plugin_name . '.' . $plugin_name . 'Model');
                self::autoload($plugin_name . '.' . $plugin_name . 'Controller');
            }

            // Autoload the given class
            if (!class_exists($class, true)) {
                self::autoload($class, $type);
            }

            // Create an instance of the class by class name, not the class (e.g. Plugin.ClassName)
            $object = self::createInstance($class_name, $value);
            foreach ($set_in as $parent) {
                $parent->$class_name = $object;
            }
        }
    }

    /**
     * Create a new instance of the given class
     *
     * @param string $class
     * @param array $params
     * @return object
     */
    private static function createInstance($class, array $params = [])
    {
        return call_user_func_array(
            [
                new ReflectionClass($class),
                'newInstance'
            ],
            $params
        );
    }
}
