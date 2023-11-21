<?php

use Minphp\Bridge\Initializer;
use Minphp\Configure\Configure as MinphpConfigure;

/**
 * Configure Bridge
 *
 * Intended for legacy backwards compatibility ONLY.
 * Use Minphp\Configure\Configure instead.
 */
class Configure
{
    private static $configure;
    private static $config = [];
    private static $containerConfigMappings = [
        'System.debug' => [],
        'System.benchmark' => [],
        'System.default_structure' => ['minphp.mvc', 'default_structure'],
        'System.default_controller' => ['minphp.mvc', 'default_controller'],
        'System.default_view' => ['minphp.mvc', 'default_view'],
        'System.error_view' => ['minphp.mvc', 'error_view'],
        'System.view_ext' => ['minphp.mvc', 'view_extension'],
        'System.404_forwarding' => [],
        'System.cli_render_views' => [],
        'Caching.on' => ['minphp.cache', 'enabled'],
        'Cache.dir_permissions' => ['minphp.cache', 'dir_permission'],
        'Caching.ext' => ['minphp.cache', 'extension'],
        'Language.default' => ['minphp.language', 'default'],
        'Language.allow_pass_through' => ['minphp.language', 'pass_through'],
        'Database.lazy_connecting' => [],
        'Database.fetch_mode' => [],
        'Database.reuse_connection' => [],
        'Database.profile' => [],
        'Session.ttl' => ['minphp.session', 'ttl'],
        'Session.cookie_ttl' => ['minphp.session', 'cookie_ttl'],
        'Session.cookie_name' => [],
        'Session.tbl' => ['minphp.session', 'db', 'tbl'],
        'Session.tbl_id' => ['minphp.session', 'db', 'tbl_id'],
        'Session.tbl_exp' => ['minphp.session', 'db', 'tbl_exp'],
        'Session.tbl_val' => ['minphp.session', 'db', 'tbl_val'],
        'Session.session_name' => ['minphp.session', 'session_name'],
        'Session.session_httponly' => ['minphp.session', 'session_httponly']
    ];

    /**
     * Singleton
     */
    private function __construct()
    {
        $container = Initializer::get()->getContainer();
        if ($container->has('minphp.config')) {
            self::$config = $container->get('minphp.config');
        }
        self::$configure = new MinphpConfigure();

        // Initialize config
        foreach (self::$containerConfigMappings as $key => $map) {
            if (!empty($map) && $container->has($map[0])) {
                $item = $container->get($map[0]);
                $value = $item;
                $set = array_slice($map, 1);
                $found = true;

                // Map reduction
                for ($i = 0; $i < count($set); $i++) {
                    if (!array_key_exists($set[$i], $value)) {
                        $found = false;
                        break;
                    }
                    $value = $value[$set[$i]];
                }

                if ($found) {
                    $this->set($key, $value);
                }
            }
        }
    }

    /**
     * Fetches the Configure instance
     *
     * @return Minphp\Configure\Configure
     */
    private static function getInstance()
    {
        if (!self::$configure) {
            new self();
        }
        return self::$configure;
    }

    /**
     * Fetches a setting from the config
     *
     * @param string $name
     */
    public static function get($name)
    {
        return self::getInstance()->get($name);
    }

    /**
     * Checks if the setting exists
     *
     * @param string $name
     */
    public static function exists($name)
    {
        return self::getInstance()->exists($name);
    }

    /**
     * Removes the setting
     *
     * @param string $name
     */
    public static function free($name)
    {
        self::getInstance()->remove($name);
    }

    /**
     * Set a setting
     *
     * @param string $name
     * @param mixed $value
     */
    public static function set($name, $value)
    {
        self::updateContainerParam($name, $value);
        self::getInstance()->set($name, $value);
    }

    /**
     * Update parameter in the container that maps to the given config setting
     *
     * @param string $name The config setting
     * @param mixed $value The new value of the config setting
     */
    private static function updateContainerParam($name, $value)
    {
        if (array_key_exists($name, self::$containerConfigMappings)
            && !empty(self::$containerConfigMappings[$name])
        ) {
            $container = Initializer::get()->getContainer();

            $paramName = self::$containerConfigMappings[$name][0];
            $keys = array_slice(self::$containerConfigMappings[$name], 1);
            $param = $container->get($paramName);
            $option = &$param;

            foreach ($keys as $key) {
                if (!array_key_exists($key, $option)) {
                    return;
                }
                $option = &$option[$key];
            }
            $option = $value;

            $container->offsetUnset($paramName);
            $container->set($paramName, $param);
        }
    }

    /**
     * Load a config file of the format 'myconfig' where 'minconfig.php' exists
     *
     * @param string $file The name of the file excluding the '.php' extension
     * @param string $dir The directory the file exists in
     */
    public static function load($file, $dir = null)
    {
        $configure = self::getInstance();
        $file .= '.php';

        if (null === $dir) {
            $dir = array_key_exists('dir', self::$config)
                ? self::$config['dir']
                : null;
        }

        foreach (self::readFile($dir . $file) as $name => $value) {
            $configure->set($name, $value);
        }
    }

    /**
     * Reads the given config file. Supports both config files that define
     * `$config` variable options, as well as return an array.
     *
     * @param string $file
     * @return array
     */
    private static function readFile($file)
    {
        $options = [];
        if (file_exists($file)) {
            $options = include_once $file;

            if (isset($config) && is_array($config)) {
                $options = $config;
                unset($config);
            }

            if (!is_array($options)) {
                $options = [];
            }
        }

        return $options;
    }

    /**
     * Sets error reporting level
     *
     * @param int $level
     */
    public static function errorReporting($level)
    {
        error_reporting($level);
    }
}
