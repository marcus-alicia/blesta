<?php

use Minphp\Bridge\Initializer;

/**
 * Dispatcher Bridge
 */
class Dispatcher extends Controller
{
    /**
     * @var Router
     */
    private static $router;

    /**
     * Set the Router to use
     *
     * @param Router $router
     */
    public static function setRouter(Router $router)
    {
        self::$router = $router;
    }

    /**
     * Fetch the Router in use
     *
     * @return Router
     */
    private static function getRouter()
    {
        if (self::$router === null) {
            $container = Initializer::get()->getContainer();
            self::$router = Router::get();
            self::$router->setWebDir(
                $container->get('minphp.constants')['WEBDIR']
            );
            self::$router->setPluginDir(
                $container->get('minphp.constants')['PLUGINDIR']
            );
            self::$router->setDefaultController(
                $container->get('minphp.mvc')['default_controller']
            );
        }
        return self::$router;
    }

    /**
     * Dispatch a Command Line Interface request
     *
     * @param array $args All CLI arguments
     */
    public static function dispatchCli($args)
    {
        $requestUri = '/';

        for ($i = 1; $i < count($args); $i++) {
            $requestUri .= $args[$i] . '/';
        }

        self::dispatch($requestUri, true);
    }

    /**
     * Dispatch the request to the proper controller
     *
     * @param string $requestUri The request URI string
     * @param boolean $isCli Whether or not this requests is a command line request
     * @throws Exception thrown when request can not be dispatched or Dispatcher::raiseError can not handle the error
     */
    public static function dispatch($requestUri, $isCli = false)
    {
        $router = self::getRouter();

        list($plugin, $controller, $action, $get, $uri, $uriStr) = array_values($router->routesTo(
            $requestUri
        ));

        $container = Initializer::get()->getContainer();

        // Delivery from cache if possible
        if (empty($_POST)
            && $container->get('minphp.cache')['enabled']
            && $container->has('cache')
        ) {
            if (($output = $container->get('cache')->fetch($uriStr))) {
                echo $output;
                return;
            }
        }

        $pluginPath = null;

        if ($plugin) {
            $pluginPath = str_replace(
                $container->get('minphp.constants')['ROOTWEBDIR'],
                '',
                $container->get('minphp.constants')['PLUGINDIR']
            ) . $plugin . DIRECTORY_SEPARATOR;
        }

        $loader = $container->get('loader');
        $controllerClass = (is_numeric(substr($controller, 0, 1)) ? '_' : '')
            . $loader->toCamelCase($controller);

        if ($plugin) {
            $loader->autoload($plugin . '.' . $loader->toCamelCase($plugin) . 'Model');
            $loader->autoload($plugin . '.' . $loader->toCamelCase($plugin) . 'Controller');
            $loader->autoload($plugin . '.' . $controllerClass);
        }

        if (!class_exists($controllerClass) || !method_exists($controllerClass, 'preAction')) {
            throw new Exception(
                sprintf('%s is not a valid controller', $controllerClass),
                404
            );
        }

        if (null === $action) {
            $action = 'index';
        }
        
        $ctrl = new $controllerClass($controller, $action, $isCli);
        $ctrl->uri = $uri;
        $ctrl->get = $get;
        $ctrl->post = $_POST;
        $ctrl->files = $_FILES;
        $ctrl->plugin = $plugin;
        $ctrl->controller = $controller;
        $ctrl->action = $action;
        $ctrl->is_cli = $isCli;

        if ($pluginPath) {
            $ctrl->setDefaultViewPath($pluginPath);
        }

        $ctrl->preAction();

        $result = null;
        if (method_exists($ctrl, $action)) {
            if ($router->isCallable($ctrl, $action)) {
                $result = $ctrl->$action();
            } else {
                throw new Exception(
                    sprintf(
                        '%s is not a callable method in controller %s',
                        $action,
                        $controllerClass
                    ),
                    404
                );
            }
        } else {
            throw new Exception(
                sprintf(
                    '%s is not a valid method in controller %s',
                    $action,
                    $controllerClass
                ),
                404
            );
        }

        $ctrl->postAction();

        // Only render view if action is non-false and if this is a CLI request
        // that CLI requests are expected to render views
        if ($result !== false
            && (
                !$isCli || $container->get('minphp.mvc')['cli_render_views']
            )
        ) {
            $ctrl->render();
        }
    }

    /**
     * Print an exception thrown error page
     *
     * @param Exception|Throwable $e An exception/error thrown
     * @throws Exception|Throwable
     */
    public static function raiseError($e)
    {
        // Require an Exception (php5) or Throwable (php7) object
        if (!($e instanceof Throwable) && !($e instanceof Exception)) {
            return;
        }

        $container = Initializer::get()->getContainer();
        $error = htmlentities($e->getMessage(), ENT_QUOTES, 'UTF-8');

        if ($e->getCode() === 404
            && $container->get('minphp.mvc')['404_forwarding']
        ) {
            header('HTTP/1.0 404 Not Found');
            header(
                'Location: '
                . $container->get('minphp.constants')['WEBDIR']
                . '404/'
            );
            exit();
        }

        if (error_reporting() === 0) {
            return;
        }

        // Attempt to render the error message
        try {
            $structure = $container->get('view');
            $view = $container->get('view');

            $view->set('error', $error);
            $view->set('exception', $e);

            $structure->set(
                'content',
                $view->fetch('error', $container->get('minphp.mvc')['error_view'])
            );
            echo $structure->fetch('structure', $container->get('minphp.mvc')['error_view']);
        } catch (Throwable $ex) {
            throw $e;
        } catch (Exception $ex) {
            throw $e;
        }
    }

    /**
     * Strip slashes from the given string
     *
     * @param string $str
     * @deprecated 1.0.0
     */
    public static function stripSlashes(&$str)
    {
        $str = stripslashes($str);
    }
}
