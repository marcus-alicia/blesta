<?php

use Minphp\Bridge\Initializer;
use Minphp\Cache\Cache as MinphpCache;

/**
 * Cache Bridge
 *
 * Intended for legacy backwards compatibility ONLY.
 * Use Minphp\Cache\Cache instead.
 */
class Cache
{
    private static $cache;

    /**
     * Return the cache instance
     *
     * @return Minphp\Cache\Cache
     */
    public static function get()
    {
        if (!self::$cache) {
            $container = Initializer::get()->getContainer();
            $config = $container->get('minphp.cache');

            self::$cache = new MinphpCache(
                array_key_exists('dir', $config)
                ? $config['dir']
                : null,
                array_key_exists('dir_permission', $config)
                ? $config['dir_permission']
                : 0755,
                array_key_exists('extension', $config)
                ? $config['extension']
                : null
            );
        }
        return self::$cache;
    }

    /**
     * Empties the entire cache of all files (directories excluded, not recursive)
     *
     * @param string $path The path within the cache directory
     */
    public static function emptyCache($path = null)
    {
        self::get()->clear($path);
    }

    /**
     * Removes the given entry from the cache
     *
     * @param string $name The name of the entry to remove
     * @param string $path The path within the cache directory to remove
     * @return boolean True if removed, false if no such file exists
     */
    public static function clearCache($name, $path = null)
    {
        return self::get()->remove($name, $path);
    }

    /**
     * Writes an entry to the cache
     *
     * @param string $name The name of the entry
     * @param string $data The date to write
     * @param int $ttl The entries TTL
     * @param string $path The path within the cache directory to write to
     */
    public static function writeCache($name, $data, $ttl, $path = null)
    {
        self::get()->write($name, $data, $ttl, $path);
    }

    /**
     * Fetches an entry from the cache
     *
     * @param string $name The name of the entry to fetch
     * @param string $path The path within the cache directory to read from
     * @return string A string containing the file contents if the cache file
     *  exists and has not yet expired, false otherwise.
     */
    public static function fetchCache($name, $path = null)
    {
        return self::get()->fetch($name, $path);
    }
}
