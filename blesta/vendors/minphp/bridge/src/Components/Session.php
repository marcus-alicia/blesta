<?php

use Minphp\Bridge\Initializer;
use Minphp\Session\Session as MinphpSession;
use Minphp\Session\Handlers\PdoHandler;

/**
 * Session Bridge
 *
 * Intended for legacy backwards compatibility ONLY.
 * Use Minphp\Session\Session instead.
 */
class Session
{
    private static $session = null;
    private static $instances = 0;
    private $config = [];

    /**
     * Initialize
     */
    public function __construct()
    {
        $container = Initializer::get()->getContainer();
        $this->config = $container->get('minphp.session');

        self::$instances++;
        if (self::$session instanceof MinphpSession) {
            return;
        }

        $options = [];

        if (array_key_exists('ttl', $this->config)) {
            $options['cookie_lifetime'] = $this->config['ttl'];
        }

        // The garbage collector max lifetime should be set to the max session TTL being used
        // to avoid deleting sessions prior to their expiration
        if (array_key_exists('ttl', $this->config) || array_key_exists('cookie_ttl', $this->config)) {
            $options['gc_maxlifetime'] = max(
                (array_key_exists('ttl', $this->config) ? $this->config['ttl'] : 0),
                (array_key_exists('cookie_ttl', $this->config) ? $this->config['cookie_ttl'] : 0)
            );
        }

        if (array_key_exists('session_name', $this->config)) {
            $options['name'] = $this->config['session_name'];
        }

        if (array_key_exists('session_httponly', $this->config)) {
            $options['cookie_httponly'] = $this->config['session_httponly'];
        }

        self::$session = new MinphpSession(
            new PdoHandler(
                $container->get('pdo'),
                array_key_exists('db', $this->config)
                ? $this->config['db']
                : []
            ),
            $options
        );

        self::$session->start();
    }

    /**
     * Close the session
     */
    public function __destruct()
    {
        --self::$instances;
        if (self::$instances <= 0) {
            self::$session->save();
        }
    }

    /**
     * Return the session ID
     *
     * @return string
     */
    public function getSid()
    {
        return self::$session->getId();
    }

    /**
     * Read session data
     *
     * @param string $name The key to read
     * @return mixed
     */
    public function read($name)
    {
        return self::$session->read($name);
    }

    /**
     * Writes a value to the session
     *
     * @param string $name The key to write
     * @param mixed $value The value to write
     */
    public function write($name, $value)
    {
        self::$session->write($name, $value);
    }

    /**
     * Unsets a valur, or all values from the session
     *
     * @param string $name The key to unset
     */
    public function clear($name = null)
    {
        self::$session->clear($name);
    }

    /**
     * The session cookie creation is handled automatically by PHP so this method
     * is left merely for backwards compatibility.
     *
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httponly
     */
    public function setSessionCookie($path = '', $domain = '', $secure = false, $httponly = false)
    {
        // The default session cookie is set automatically by PHP.
        // However, define a separate cookie as the 'remember me' cookie
        if (array_key_exists('cookie_name', $this->config)) {
            $cookieLifetime = array_key_exists('cookie_ttl', $this->config)
                ? (int)$this->config['cookie_ttl']
                : 0;

            self::$session->cookie(
                $this->config['cookie_name'],
                null,
                $cookieLifetime,
                $path,
                $domain,
                $secure,
                $httponly
            );
        }
    }

    /**
     * Set long term storage of session cookie
     *
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httponly
     */
    public function keepAliveSessionCookie($path = '', $domain = '', $secure = false, $httponly = false)
    {
        $lifetime = array_key_exists('ttl', $this->config)
            ? (int)$this->config['ttl']
            : 0;

        // Keep alive the other session cookie
        if (array_key_exists('cookie_name', $this->config) && isset($_COOKIE[$this->config['cookie_name']])) {
            // Update the lifetime to the cookie lifetime
            $lifetime = array_key_exists('cookie_ttl', $this->config)
                ? (int)$this->config['cookie_ttl']
                : 0;

            self::$session->cookie(
                $this->config['cookie_name'],
                null,
                $lifetime,
                $path,
                $domain,
                $secure,
                $httponly
            );
        }

        // Keep alive the default PHP session cookie
        self::$session->cookie(null, null, $lifetime);
    }

    /**
     * Remove long term storage of session cookie
     *
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httponly
     */
    public function clearSessionCookie($path = '', $domain = '', $secure = false, $httponly = false)
    {
        $options = [
            'cookie_path' => $path,
            'cookie_domain' => $domain,
            'cookie_secure' => $secure,
            'cookie_httponly' => $httponly,
            'cookie_lifetime' => array_key_exists('ttl', $this->config) ? $this->config['ttl'] : 0
        ];

        // Remove the separate cookie by setting it into the past
        if (array_key_exists('cookie_name', $this->config) && isset($_COOKIE[$this->config['cookie_name']])) {
            self::$session->cookie(
                $this->config['cookie_name'],
                null,
                1,
                $path,
                $domain,
                $secure,
                $httponly
            );
        }

        self::$session->regenerate(true, null, $options);
    }
}
