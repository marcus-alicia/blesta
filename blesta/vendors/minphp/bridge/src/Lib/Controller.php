<?php

use Minphp\Bridge\Initializer;

/**
 * Controller Bridge
 */
abstract class Controller
{
    /**
     * @var object The structure View for this instance
     */
    public $structure;

    /**
     * @var string Name of the structure view file
     */
    public $structure_view;

    /**
     * @var object The main View for this instance
     */
    public $view;

    /**
     * @var array All parts of the Routed URI
     */
    public $uri;

    /**
     * @var string Requested URI after being Routed
     */
    public $uri_str;

    /**
     * @var array All GET parameters
     */
    public $get;

    /**
     * @var array All POST data
     */
    public $post;

    /**
     * @var array All FILE data
     */
    public $files;

    /**
     * @var string Name of the plugin invoked by this request (if any)
     */
    public $plugin;

    /**
     * @var string Name of the controller invoked by this request
     */
    public $controller;

    /**
     * @var string Action invoked by this request
     */
    public $action;

    /**
     * @var boolean Flag whether this is a CLI request
     */
    public $is_cli;

    /**
     * @var array Names of all Models this Controller uses
     */
    protected $uses = [];

    /**
     * @var array Names of all Components this Controller uses
     */
    protected $components = [];

    /**
     * @var array Names of all Helpers this Controller and child Views use
     */
    protected $helpers = [];

    /**
     * @var \Minphp\Container\ContainerInterface
     */
    private $container;

    /**
     * @var int
     */
    private $cache_ttl;

    /**
     * @var bool
     */
    private $rendered = false;

    /**
     * Constructs a new Controller object
     */
    public function __construct()
    {
        $this->container = Initializer::get()->getContainer();

        $this->structure_view = $this->container
            ->get('minphp.mvc')['default_structure'];

        // Initialize the structure view
        $this->structure = $this->container->get('view');

        // Initialize the main view
        $this->view = $this->container->get('view');

        // Load any preset models
        $this->uses($this->uses);

        // Load any preset components
        $this->components($this->components);

        // Load any preset helpers
        $this->helpers($this->helpers);
    }

    /**
     * Load the given models into this controller
     *
     * @param array $models All models to load
     */
    protected function uses(array $models)
    {
        $this->container->get('loader')->loadModels($this, $models);
    }

    /**
     * Load the given components into this controller
     *
     * @param array $components All components to load
     */
    protected function components(array $components)
    {
        $this->container->get('loader')->loadComponents($this, $components);
    }

    /**
     * Load the given helpers into this controller, making them available to
     * any implicitly initialized Views.
     *
     * @param array $helpers All helpers to load
     */
    protected function helpers(array $helpers)
    {
        $this->container->get('loader')->loadHelpers($this, $helpers);
    }

    /**
     * The default action method, overwritable.
     */
    public function index()
    {
        // Nothing to do
    }

    /**
     * Overwritable method called before the index method, or controller
     * specified action.
     */
    public function preAction()
    {
        // Nothing to do
    }

    /**
     * Overwritable method called after the index method, or controller
     * specified action.
     */
    public function postAction()
    {
        // Nothing to do
    }

    /**
     * Sets the value within the view
     *
     * @param string $name The name of the variable to set in this view
     * @param mixed $value The value to assign to the variable set in this view
     */
    protected function set($name, $value = null)
    {
        $this->view->set($name, $value);
    }

    /**
     * Prints the given view
     *
     * @param string $view The view name
     * @param string $dir The directory where the view resides
     * @see Controller::partial()
     */
    protected function draw($view = null, $dir = null)
    {
        echo $this->container->get('view')
            ->fetch($view, $dir);
    }

    /**
     * Overwritable method called before the partial method
     *
     * @param string $view The name of the view file to render
     * @param array $params An array of parameters to set in the view
     * @param string $dir The directory to find the given view in
     * @return array A list containing: (optional)
     *
     *  - view The name of the view file to render
     *  - params An array of parameters to set in the view
     *  - dir The directory to find the given view in
     */
    public function prePartial($view, $params = null, $dir = null)
    {
        return [
            'view' => $view,
            'params' => $params,
            'dir' => $dir
        ];
    }

    /**
     * Overwritable method called after the partial method
     *
     * @param string $view The name of the view file to render
     * @param array $params An array of parameters to set in the view
     * @param string $dir The directory to find the given view in
     * @return array A list containing: (optional)
     *
     *  - view The name of the view file to render
     *  - params An array of parameters to set in the view
     *  - dir The directory to find the given view in
     */
    public function postPartial($view, $params = null, $dir = null)
    {
        return [
            'view' => $view,
            'params' => $params,
            'dir' => $dir
        ];
    }

    /**
     * Returns the given view using the supplied params.
     *
     * @param string $view The name of the view file to render
     * @param array $params An array of parameters to set in the view
     * @param string $dir The directory to find the given view in
     * @return string The rendered view
     */
    protected function partial($view, $params = null, $dir = null)
    {
        $partial = clone $this->view;

        $vars = $this->prePartial($view, $params, $dir);
        if (isset($vars['view'])) {
            $view = $vars['view'];
        }
        if (isset($vars['params'])) {
            $params = array_merge((isset($params) ? $params : []), $vars['params']);
        }
        if (isset($vars['dir'])) {
            $dir = $vars['dir'];
        }

        if (is_array($params)) {
            $partial->set($params);
        }

        $vars = $this->postPartial($view, $params,$dir);
        if (isset($vars['view'])) {
            $view = $vars['view'];
        }
        if (isset($vars['params'])) {
            $params = array_merge((isset($params) ? $params : []), $vars['params']);
        }
        if (isset($vars['dir'])) {
            $dir = $vars['dir'];
        }

        return $partial->fetch($view, $dir);
    }

    /**
     * Starts caching for the current request
     *
     * @param int|string $time The amount of time to cache for, either an
     *  integer (seconds) or a proper strtotime string (e.g. "1 hour").
     * @return bool True if caching is enabled, false otherwise.
     */
    protected function startCaching($time)
    {
        $options = $this->container->get('minphp.cache');
        if (!isset($options['enabled']) || !$options['enabled']) {
            return false;
        }

        if (!is_numeric($time)) {
            $time = strtotime($time) - time();
        }
        $this->cache_ttl = $time;

        return true;
    }

    /**
     * Stops caching for the current request. If invoked, caching will not be
     * performed for this request.
     */
    protected function stopCaching()
    {
        $this->cache_ttl = 0;
    }

    /**
     * Clears the cache file for the given URI, or for the curren request if
     * no URI is given
     *
     * @param mixed $uri The request to clear, if not given or false the
     *  current request is cleared
     */
    protected function clearCache($uri = false)
    {
        $this->container->get('cache')->remove(strtolower(
            $uri
            ? $uri
            : $this->uri_str
        ));
    }

    /**
     * Empties the entire cache of all files (directories excluded)
     */
    protected function emptyCache()
    {
        $this->container->get('cache')->clear();
    }

    /**
     * Renders the view with its structure (if set).  The view is set into the
     * structure as $content. This method can only be called once, since it
     * includes the structure when outputting. To render a partial view use
     * Controller::partial()
     *
     * @see Controller::partial()
     * @param string $view The name of the view to render
     * @param string $dir The directory where the view file resides
     */
    protected function render($view = null, $dir = null)
    {
        if ($this->rendered) {
            return;
        }
        $this->rendered = true;

        if (null === $view) {
            $view = $this->view->file;
            if (null === $view) {
                $view = $this->container->get('loader')->fromCamelCase(
                    get_class($this)
                    . (
                        $this->action !== 'index'
                        && $this->action !== null
                        ? '_' . strtolower($this->action)
                        : ''
                    )
                );
            }
        }

        // Prepare the structure
        $structure_view = $this->structure_view;
        $structure_dir = null;
        if (($pos = strrpos($this->structure_view, DIRECTORY_SEPARATOR)) > 0) {
            $structure_dir = substr($this->structure_view, 0, $pos);
            $structure_view = substr($this->structure_view, $pos + 1);
        }

        // Render view
        $output = $this->view->fetch($view, $dir);

        // Render view in structure
        if (null !== $structure_view) {
            $this->structure->set('content', $output);
            $output = $this->structure->fetch($structure_view, $structure_dir);
        }

        // Cache result
        if ($this->cache_ttl > 0) {
            $this->container->get('cache')->write(
                $this->uri_str,
                $output,
                $this->cache_ttl
            );
        }

        // Output the structure containing the view to standard out
        echo $output;
    }

    /**
     * Initiates a header redirect to the given URI/URL. Automatically prepends
     * base URI to $uri if $uri is relative (e.g. does not start with a '/' and
     * is not a url)
     *
     * @param string $uri The URI or URL to redirect to.
     */
    protected static function redirect($uri = null)
    {
        $base_uri = Initializer::get()->getContainer()
            ->get('minphp.constants')['WEBDIR'];
        if (null === $uri) {
            $uri = $base_uri;
        }

        $parts = parse_url($uri);
        $relative = true;
        if (substr($uri, 0, 1) == "/") {
            $relative = false;
        }
        // If not scheme is specified, assume http(s)
        if (!isset($parts['scheme'])) {
            $uri = "http" . (
                    isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != "off"
                    ? "s"
                    : ""
                ) . "://" . (
                    isset($_SERVER['HTTP_HOST'])
                    ? $_SERVER['HTTP_HOST']
                    : $_SERVER['SERVER_NAME']
                ) . (
                    $relative
                    ? $base_uri
                    : ""
                ) . $uri;
        }

        header("Location: " . $uri);
        exit;
    }

    /**
     * Sets the default view path for this view and its structure view
     *
     * @param string $path The view path to replace the current view path
     */
    protected function setDefaultViewPath($path)
    {
        $this->view->setDefaultView($path);
        $this->structure->setDefaultView($path);
    }
}
