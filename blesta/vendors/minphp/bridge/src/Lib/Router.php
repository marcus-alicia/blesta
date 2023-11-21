<?php

/**
 * Handles mapping of URIs from one type to another
 */
class Router
{
    /**
     * @var array A 2-dimensional array containg the original URIs and their mapped counter parts
     */
    protected static $routes;

    /**
     * @var string The path to the web directory
     */
    protected static $webdir;

    /**
     * @var string the path to the plugin directory
     */
    protected static $plugindir;

    /**
     * @var string The default controller
     */
    protected static $defaultController;

    /**
     * Protected constructor to prevent instance creation
     */
    protected function __construct()
    {
        // Nothing to do
    }

    /**
     * Set the web directory path
     *
     * @param string $dir
     */
    public static function setWebDir($dir)
    {
        self::$webdir = $dir;
    }

    /**
     * Set the plugin directory path
     *
     * @param string $dir
     */
    public static function setPluginDir($dir)
    {
        self::$plugindir = $dir;
    }

    /**
     * Set the default controller
     *
     * @param string $controller
     */
    public static function setDefaultController($controller)
    {
        self::$defaultController = $controller;
    }

    /**
     * Fetch a singleton instance
     *
     * @staticvar self $instance
     * @return self
     */
    public static function get()
    {
        static $instance = null;

        if (null === $instance) {
            $instance = new self();
        }

        return $instance;
    }

    /**
     * Set a route mapping
     *
     * @param string $origUri URI or regex
     * @param string $mappedUri URI or regex replacement
     * @throws Exception Thrown if $origUri or $mappedUri is empty
     */
    public static function route($origUri, $mappedUri)
    {
        if (strlen($origUri) == 0 || strlen($mappedUri) == 0) {
            throw new Exception('Illegal URI specified in Router::route()');
        }

        self::$routes['orig'][] = '/' . self::escape($origUri) . '/i';
        self::$routes['mapped'][] = self::escape($mappedUri);
    }

    /**
     * Maps the requested URI to the proper re-mapped URI, if available
     *
     * @param string $uri
     * @return string The new URI, or the requested URI if no mapping exists for this request
     */
    public static function match($uri)
    {
        if (is_array(self::$routes['orig']) && is_array(self::$routes['mapped'])) {
            $parsed_uri = parse_url($uri);

            return self::unescape(
                preg_replace(
                    self::$routes['orig'],
                    self::$routes['mapped'],
                    (isset($parsed_uri['path']) ? $parsed_uri['path'] : ''),
                    1
                )
            ) . (isset($parsed_uri['query']) ? '?' . $parsed_uri['query'] : '');
        }

        return $uri;
    }

    /**
     * Escapes a URI, making it safe for preg (regex) functions
     *
     * @param string $uri The URI to be escaped
     * @return string the escaped $uri string
     */
    public static function escape($uri)
    {
        return addcslashes($uri, '/\\');
    }

    /**
     * Unescapes a URI that has been escaped with Router::escape()
     *
     * @param string $uri The URI to be unescaped
     * @return string the unescaped $uri string
     */
    public static function unescape($uri)
    {
        return stripcslashes($uri);
    }

    /**
     * Converts a directory string into a properly formed URI
     */
    public static function makeURI($dir)
    {
        return str_replace('\\', '/', $dir);
    }

    /**
     * Parses the given URI into an array of its components
     *
     * @param string $uri The URI to parse
     * @return array The URI broken into its many parts
     * @deprecated 1.0.0
     */
    public static function parseURI($uri)
    {
        return explode('/', str_replace('?', '/?', $uri ?? ''));
    }

    /**
     * Filters out any part of the web root from the uri path
     *
     * @param string $uri The URI to filter
     * @return string The filtered URI
     */
    public static function filterURI($uri)
    {
        return rtrim(
            preg_replace(
                "/^(" . self::escape(self::$webdir) . "|" . self::escape(dirname(self::$webdir)) . "|\/)/i",
                "",
                $uri ?? '', 
                1
            ),
            '/'
        );
    }

    /**
     * Uses PHP's ReflectionClass to test the given object for the given method's callability.
     * Only public, non-abstract, non-constructor/destructors are considered callable.
     *
     * @param Object $obj The object we're searching
     * @param string $method The name of the method we're looking for in $obj
     * @param string $inheritsFrom The class that $obj must inherit from, null otherwise.
     * @return boolean true if the method is callable, false otherwise.
     */
    public static function isCallable($obj, $method, $inheritsFrom = 'Controller')
    {
        $publicUncallables = ['preaction', 'postaction'];

        if (is_object($obj)) {
            $reflectedObj = new ReflectionClass($obj);
            if ($reflectedObj->isAbstract()) {
                return false;
            }

            try {
                $reflectedMethod = $reflectedObj->getMethod($method);
                $declaredClass = $reflectedMethod->getDeclaringClass();

                // A method may be required to inherit from the given class
                if (($inheritsFrom !== null
                        && (
                            $declaredClass->getName() != $inheritsFrom
                            && !$declaredClass->isSubclassOf($inheritsFrom)
                        )
                    )
                    || !$reflectedMethod->isPublic()
                    || $reflectedMethod->isProtected()
                    || $reflectedMethod->isConstructor()
                    || $reflectedMethod->isDestructor()
                    || $reflectedMethod->isAbstract()
                    || in_array(strtolower($method), $publicUncallables)
                ) {
                    return false;
                }
                return true;
            } catch (ReflectionException $e) {
                return false;
            }
        }

        return false;
    }

    /**
     * Finds the controller and action and all get parameters that the given URI routes to
     *
     * @param string $requestUri A URI to parse
     * @return array An array containing the following indexes:
     *  - plugin The name of the plugin this URI maps to
     *  - controller The name of the controller this URI maps to
     *  - action The action method this URI maps to
     *  - get An array of get parameters this URI maps to
     *  - uri An array of URI parts
     *  - uri_str A string representation of the URI containing the controller requested (if not passed in the URI)
     */
    public static function routesTo($requestUri)
    {
        $plugin = null;
        $controller = self::$defaultController;
        $action = null;
        $get = [];
        $uri = [];
        $uri_str = $requestUri;

        $parsedUri = parse_url(
            Router::match(
                Router::filterURI($requestUri)
            )
        );

        // Set URI to the current path
        if ($parsedUri && !empty($parsedUri['path'])) {
            $uri_str = $parsedUri['path'];
        }

        if (!isset($parsedUri['query']) && $uri_str[strlen($uri_str)-1] !== '/') {
            $uri_str .= '/';
        }

        $pathParts = array_reverse(explode(
            '/',
            isset($parsedUri['path'])
                ? $parsedUri['path']
                : null
        ));

        // Begin building URI
        for ($i = count($pathParts)-1; $i >= 0; $i--) {
            if (!empty($pathParts[$i])) {
                $uri[] = $pathParts[$i];
            }
        }

        if (isset($parsedUri['query'])) {
            $query = '?' . $parsedUri['query'];
            $uri[] = $query;
            $uri_str .= $query;
        }
        // End building URI

        // Begin finding plugin, controller, action
        $uriParts = array_reverse($uri);

        if (!empty($uriParts)) {
            $part = array_pop($uriParts);
            if (!empty($part)) {
                $controller = $part;
            }
        }
        if (!empty($uriParts)) {
            $part = array_pop($uriParts);
            // The action may be set if it is not a query string
            if (!empty($part) && substr($part, 0, 1) !== '?') {
                $action = $part;
            }
        }

        $pluginPath = self::$plugindir . DIRECTORY_SEPARATOR . $controller
            . DIRECTORY_SEPARATOR;

        if (is_dir($pluginPath)) {
            $plugin = $controller;
            $controller = $action;
            $action = null;
            if (empty($controller)) {
                $controller = self::$defaultController;
            } elseif (!empty($uriParts)) {
                $part = array_pop($uriParts);
                // The action may be set if it is not a query string
                if (!empty($part) && substr($part, 0, 1) !== '?') {
                    $action = $part;
                }
            }
        }
        // End finding plugin, controller, action

        // Begin setting GET params
        while (!empty($uriParts)) {
            $part = array_pop($uriParts);
            // Only assign GET parameters that are not query parameters
            if (empty($part) || substr($part, 0, 1) === '?') {
                continue;
            }

            $optionalDelim = ':';
            if (strpos($part, $optionalDelim) !== false) {
                $pair = explode($optionalDelim, $part, 2);
                $get[$pair[0]] = $pair[1];
            } else {
                $get[] = $part;
            }
        }

        if (isset($parsedUri['query'])) {
            $query = [];
            parse_str($parsedUri['query'], $query);
            $get = array_merge($get, $query);
        }
        // End setting GET params

        return compact('plugin', 'controller', 'action', 'get', 'uri', 'uri_str');
    }
}
