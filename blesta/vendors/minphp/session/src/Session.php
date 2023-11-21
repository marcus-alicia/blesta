<?php
namespace Minphp\Session;

use SessionHandlerInterface;
use Minphp\Session\Handlers\NativeHandler;
use LogicException;

/**
 * Session management library.
 */
class Session
{
    /**
     * @var \SessionHandlerInterface The session handler
     */
    protected $handler;
    /**
     * @deprecated since 1.2.0
     * @var bool Whether or not the session has started
     */
    protected $started = false;

    /**
     * Initialize the Session
     *
     * @param \SessionHandlerInterface $handler The session handler
     * @param array $options Session ini options to set
     * @see http://php.net/session.configuration
     */
    public function __construct(SessionHandlerInterface $handler = null, array $options = [])
    {
        session_register_shutdown();

        // The headers must not have already been sent
        if (!$this->hasSentHeaders()) {
            // We can only set the options if the session is not active
            if (!$this->hasStarted()) {
                $this->setOptions($options);
            }

            if (!$this->handler) {
                $this->handler = $handler ?: new NativeHandler();
                session_set_save_handler($this->handler, false);
            }
        }
    }

    /**
     * Sets session ini variables
     *
     * @param array $options Session ini options to set
     * @see http://php.net/session.configuration
     * @throws \LogicException
     */
    public function setOptions(array $options)
    {
        if ($this->hasStarted()) {
            throw new LogicException('Session already started, can not set options.');
        }

        if ($this->hasSentHeaders()) {
            throw new LogicException('Headers already sent, can not set session options.');
        }

        $supportedOptions = ['save_path', 'name', 'save_handler',
            'gc_probability', 'gc_divisor', 'gc_maxlifetime', 'serialize_handler',
            'cookie_lifetime', 'cookie_path', 'cookie_domain', 'cookie_secure',
            'cookie_httponly', 'use_strict_mode', 'use_cookies', 'use_only_cookies',
            'referer_check', 'entropy_file', 'entropy_length', 'cache_limiter',
            'cache_expire', 'use_trans_sid', 'hash_function', 'hash_bits_per_character',
            'upload_progress.enabled', 'upload_progress.cleanup', 'upload_progress.prefix',
            'upload_progress.name', 'upload_progress.freq', 'upload_progress.min_freq',
            'lazy_write'
        ];

        foreach ($options as $key => $value) {
            if (in_array($key, $supportedOptions)) {
                ini_set('session.' . $key, $value);
            }
        }
    }

    /**
     * Start the session
     *
     * @return bool True if the session has started
     */
    public function start()
    {
        if (!$this->hasStarted()) {
            if (!$this->hasSentHeaders()) {
                return session_start();
            }

            return false;
        }

        return true;
    }

    /**
     * Return whether the session has started or not
     *
     * @return bool True if the session has started
     */
    public function hasStarted()
    {
        return ($this->started = (session_status() === PHP_SESSION_ACTIVE));
    }

    /**
     * Return whether any session headers have been sent/output or not
     *
     * @return bool True if session headers have already been sent
     */
    public function hasSentHeaders()
    {
        return headers_sent();
    }

    /**
     * Saves and closes the session
     */
    public function save()
    {
        session_write_close();
    }

    /**
     * Sets a client cookie. Defaults to use the PHP session cookie.
     * Can be used to create specific cookies, keep them alive, or destroy them.
     *
     * @param string $name The name of the cookie to keep alive (if not the default one)
     * @param int $lifetime The cookie time-to-live, in seconds, relative to the
     *  current time (optional; 1 will remove the cookie, 0 will keep it until the browser is closed)
     * @param string $path The cookie's path (optional)
     * @param string $domain The cookie's domain (optional)
     * @param bool $secure Whether to transmit the cookie securely from the client (optional)
     * @param bool $httpOnly Whether the cookie is only accessible over the HTTP protocol (optional)
     */
    public function cookie(
        $name = null,
        $value = null,
        $lifetime = null,
        $path = null,
        $domain = null,
        $secure = null,
        $httpOnly = null
    ) {
        // Determine the cookie lifetime relative to the current time
        // where a lifetime of 1 is used to destroy the cookie
        $prefix = 'session.';
        $ttl = (int)ini_get($prefix . 'cookie_lifetime');
        if ($lifetime === 0 || $lifetime === 1) {
            // Use the given value as the lifetime
            $ttl = $lifetime;
        } elseif ($lifetime !== null) {
            // Set a custom cookie lifetime
            $ttl = time() + $lifetime;
        } else {
            // Set the default cookie lifetime
            $ttl += time();
        }

        // Use the known session options for values not given
        $cookie = [
            'name' => (null !== $name ? $name : ini_get($prefix . 'name')),
            'value' => (null !== $value ? $value : $this->getId()),
            'lifetime' => $ttl,
            'path' => (null !== $path ? $path : ini_get($prefix . 'cookie_path')),
            'domain' => (null !== $domain ? $domain : ini_get($prefix . 'cookie_domain')),
            'secure' => (bool)(null !== $secure ? $secure : ini_get($prefix . 'cookie_secure')),
            'httponly' => (bool)(null !== $httpOnly ? $httpOnly : ini_get($prefix . 'cookie_httponly'))
        ];

        // Set the cookie for the client
        setcookie(
            $cookie['name'],
            $cookie['value'],
            $cookie['lifetime'],
            $cookie['path'],
            $cookie['domain'],
            $cookie['secure'],
            $cookie['httponly']
        );
    }

    /**
     * Regenerates the session
     *
     * @param bool $destroy True to destroy the current session
     * @param int $lifetime The lifetime of the session cookie in seconds
     * @param array $options Session ini options to set
     * @see http://php.net/session.configuration
     * @return bool True if regenerated, false otherwise
     */
    public function regenerate($destroy = false, $lifetime = null, array $options = [])
    {
        if (!$this->hasStarted() || $this->hasSentHeaders()) {
            return false;
        }

        $regenerated = session_regenerate_id($destroy);

        // Close and restart the session with the new options
        $this->save();

        if ($lifetime !== null && !in_array('cookie_lifetime', $options)) {
            $options['cookie_lifetime'] = $lifetime;
        }

        $this->setOptions($options);

        $this->start();

        return $regenerated;
    }

    /**
     * Return the session ID
     *
     * @return string The session ID
     */
    public function getId()
    {
        return session_id();
    }

    /**
     * Sets the session ID
     *
     * @param string $sessionId The ID to set
     * @throws \LogicException
     */
    public function setId($sessionId)
    {
        if ($this->hasStarted()) {
            throw new LogicException('Session already started, can not change ID.');
        }
        session_id($sessionId);
    }

    /**
     * Return the session name
     *
     * @return string The session name
     */
    public function getName()
    {
        return session_name();
    }

    /**
     * Sets the session name
     *
     * @param string $name The session name
     * @throws \LogicException
     */
    public function setName($name)
    {
        if ($this->hasStarted()) {
            throw new LogicException('Session already started, can not change name.');
        }
        session_name($name);
    }

    /**
     * Read session information for the given name
     *
     * @param string $name The name of the item to read
     * @return mixed The value stored in $name of the session, or an empty string.
     */
    public function read($name)
    {
        if (isset($_SESSION[$name])) {
            return $_SESSION[$name];
        }
        return '';
    }

    /**
     * Writes the given session information to the given name
     *
     * @param string $name The name to write to
     * @param mixed $value The value to write
     */
    public function write($name, $value)
    {
        $_SESSION[$name] = $value;
    }

    /**
     * Unsets the value of a given session variable, or the entire session of
     * all values
     *
     * @param string $name The name to unset
     */
    public function clear($name = null)
    {
        if ($name) {
            unset($_SESSION[$name]);
        } else {
            $_SESSION = [];
        }
    }
}
