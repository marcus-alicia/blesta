# Minphp/Bridge

This library allows you to seamlessly take advantage of newer namespaced minPHP
libraries while at the same time maintaining backwards compatibility with
minPHP 0.x global classes.

**Why use this library**

This library is intended for projects that are built using minPHP 0.x that want
to take advantage of other namespaced minPHP libraries.

## Installation

Install via composer:

```sh
composer require minphp/bridge
```

## Usage

The bridge requires some information before it's able to initialize some
libraries. This is handled by populating and passing in a container that
implements `Minphp\Container\ContainerInterface`.

The following config files in minphp 0.x were removed in minphp 1.0, which is
what necessitates populating the container:

- core.php
- database.php
- session.php

minPHP uses the `Minphp\Container\Container`, which meets this requirement. The
following elements are required to be set:

- `minphp.cache` *array* containing:
    - `dir` *string*
    - `dir_permission` *int* (octal)
    - `extension` *string*
    - `enabled` *bool*
- `minphp.config` *array* containing:
    - `dir` *string*
- `minphp.constants` *array* containing:
    - `APPDIR` *string*
    - `CACHEDIR` *string*
    - `COMPONENTDIR` *string*
    - `CONFIGDIR` *string*
    - `CONTROLLERDIR` *string*
    - `DS` *string*
    - `HELPERDIR` *string*
    - `HTACCESS` *bool*
    - `LANGDIR` *string*
    - `LIBDIR` *string*
    - `MINPHP_VERSION` *string*
    - `MODELDIR` *string*
    - `PLUGINDIR` *string*
    - `ROOTWEBDIR` *string*
    - `VENDORDIR` *string*
    - `VIEWDIR` *string*
    - `WEBDIR` *string*
- `minphp.language` *array* containing:
    - `default` *string* 'en_us'
    - `dir` *string*
    - `pass_through` *bool*
- `minphp.mvc` *array* containing the following keys:
    - `default_controller` *string*
    - `default_structure` *string*
    - `default_view` *string*
    - `error_view` *string*
    - `view_extension` *string*
    - `cli_render_views` *bool*
    - `404_forwarding` * bool*
- `minphp.session` *array* containing the following keys (all optional):
    - `db` *array* containing:
        - `tbl` *string* The session database table
        - `tbl_id` *string* The ID database field
        - `tbl_exp` *string* The expiration database field
        - `tbl_val` *string* The value database field
        - `ttl` *int* The session time-to-live, in seconds, relative to current
server time (should be set to the same value as the other TTLs, e.g.,
'max(ttl, cookie_ttl)' to correctly sync client and server session expirations)
    - `ttl` *int* Number of seconds to keep a session alive.
    - `cookie_ttl` *int* Number of seconds to keep long storage cookie alive.
    - `session_name` *string* Name of the session.
    - `session_httponly` *bool* True to enable HTTP only session cookies.
- `cache` *Minphp\Cache\Cache*
- `view` *View* As a factory (new instance each time)
- `loader` *Loader*
- `pdo` *PDO*

### Creating and Using the Container

First create a new config file called `services.php` that will be used to
define our service providers.

Each service is defined as the fully qualified class name. It can be whatever
you want as long as it can be properly autoloaded.

**/config/services.php**

```php
<?php
return [
    'App\\ServiceProviders\\MinphpBridge'
];
```

Next, create the service provider that matches the one we added to `services.php`.

**/app/ServiceProviders/MinphpBridge.php**

> Note: You can auotload classes in this directory by defining the namespace
in your composer.json file under the "autoload" section like so:

```
    "autoload": {
        "psr-4": {
            "App\\ServiceProviders\\": "app/ServiceProviders/"
        }
    }
```

```php
<?php
namespace App\ServiceProviders;

use Pimple\ServiceProviderInterface;
use Pimple\Container;
use Cache;
use View;
use Loader;
use PDO;
use Configure;

class MinphpBridge implements ServiceProviderInterface
{
    private $container;

    /**
     * {@inheritdoc}
     */
    public function register(Container $container)
    {
        $this->container = $container;
        $this->registerCache();
        $this->registerConfig();
        $this->registerConstants();
        $this->registerLanguage();
        $this->registerMvc();
        $this->registerSession();

        $container->set('cache', function ($c) {
            return Cache::get();
        });

        $container->set('view', $container->factory(function ($c) {
            return new View();
        }));

        $container->set('loader', function ($c) {
            $constants = $c->get('minphp.constants');
            $loader = Loader::get();
            $loader->setDirectories([
                $constants['ROOTWEBDIR'] . $constants['APPDIR'],
                'models' => $constants['MODELDIR'],
                'controllers' => $constants['CONTROLLERDIR'],
                'components' => $constants['COMPONENTDIR'],
                'helpers' => $constants['HELPERDIR'],
                'plugins' => $constants['PLUGINDIR']
            ]);

            return $loader;
        });

        $container->set('pdo', function ($c) {
            Configure::load('database');
            $dbInfo = Configure::get('Database.profile');
            return new PDO(
                $dbInfo['driver'] . ':dbname=' . $dbInfo['database']
                . ';host=' . $dbInfo['host'] . (
                    isset($dbInfo['port'])
                    ? ':' . $dbInfo['port']
                    : ''
                ),
                $dbInfo['user'],
                $dbInfo['pass']
            );
        });
    }

    private function registerCache()
    {
        $this->container->set('minphp.cache', function ($c) {
            return [
                'dir' => $c->get('minphp.constants')['CACHEDIR'],
                'dir_permissions' => 0755,
                'extension' => '.html',
                'enabled' => true
            ];
        });
    }

    private function registerConfig()
    {
        $this->container->set('minphp.config', function ($c) {
            return [
                'dir' => $c->get('minphp.constants')['CONFIGDIR']
            ];
        });
    }

    private function registerConstants()
    {
        $this->container->set('minphp.constants', function ($c) {
            $rootWebDir = realpath(dirname(dirname(dirname(__FILE__))))
                . DIRECTORY_SEPARATOR;

            $appDir = 'app' . DIRECTORY_SEPARATOR;
            $htaccess = file_exists($rootWebDir . '.htaccess');

            $script = isset($_SERVER['SCRIPT_NAME'])
                ? $_SERVER['SCRIPT_NAME']
                : (
                    isset($_SERVER['PHP_SELF'])
                    ? $_SERVER['PHP_SELF']
                    : null
                );

            $webDir = (
                !$htaccess
                ? $script
                : (
                    ($path = dirname($script)) === '/'
                    || $path == DIRECTORY_SEPARATOR ? '' : $path
                )
            ) . '/';

            if ($webDir === $rootWebDir) {
                $webDir = '/';
            }


            return [
                'APPDIR' => $appDir,
                'CACHEDIR' => $rootWebDir . 'cache' . DIRECTORY_SEPARATOR,
                'COMPONENTDIR' => $rootWebDir . 'components' . DIRECTORY_SEPARATOR,
                'CONFIGDIR' => $rootWebDir . 'config' . DIRECTORY_SEPARATOR,
                'CONTROLLERDIR' => $rootWebDir . $appDir . 'controllers' . DIRECTORY_SEPARATOR,
                'DS' => DIRECTORY_SEPARATOR,
                'HELPERDIR' => $rootWebDir . 'helpers' . DIRECTORY_SEPARATOR,
                'HTACCESS' => $htaccess,
                'LANGDIR' => $rootWebDir . 'language' . DIRECTORY_SEPARATOR,
                'LIBDIR' => $rootWebDir . 'lib' . DIRECTORY_SEPARATOR,
                'MINPHP_VERSION' => '1.0.0',
                'MODELDIR' => $rootWebDir . $appDir . 'models' . DIRECTORY_SEPARATOR,
                'PLUGINDIR' => $rootWebDir . 'plugins' . DIRECTORY_SEPARATOR,
                'ROOTWEBDIR' => $rootWebDir,
                'VEDNORDIR' => $rootWebDir . 'vendors' . DIRECTORY_SEPARATOR,
                'VIEWDIR' => $rootWebDir . $appDir . 'views' . DIRECTORY_SEPARATOR,
                'WEBDIR' => $webDir
            ];
        });
    }

    private function registerLanguage()
    {
        $this->container->set('minphp.language', function ($c) {
            return [
                'default' => 'en_us',
                'dir' => $c->get('minphp.constants')['LANGDIR'],
                'pass_through' => false
            ];
        });
    }

    private function registerMvc()
    {
        $this->container->set('minphp.mvc', function ($c) {
            return [
                'default_controller' => 'main',
                'default_structure' => 'structure',
                'default_view' => 'default',
                'error_view' => 'errors',
                'view_extension' => '.pdt',
                'cli_render_views' => false,
                '404_forwarding' => false
            ];
        });
    }

    private function registerSession()
    {
        $this->container->set('minphp.session', function ($c) {
            return [
                'db' => [
                    'tbl' => 'sessions',
                    'tbl_id' => 'id',
                    'tbl_exp' => 'expire',
                    'tbl_val' => 'value'
                ],
                'ttl' => 1800, // 30 mins
                'cookie_ttl' => 604800, // 7 days
                'session_name' => 'sid',
                'session_httponly' => true
            ];
        });
    }
}

```

#### Updating init.php

Update `/lib/init.php` so it looks like the following:

```php
<?php
error_reporting(-1);

// include autoloader
require_once dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR
    . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

// Fetch available services
$services = require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config'
    . DIRECTORY_SEPARATOR . 'services.php';

// Initialize
$container = new Minphp\Container\Container();

// Set services
foreach ($services as $service) {
    $container->register(new $service());
}

// Run bridge
$bridge = Minphp\Bridge\Initializer::get();
$bridge->setContainer($container);
$bridge->run();

// Set the container
Configure::set('container', $container);

return $container;

```

#### Removing Unused Files

With this bridge in place, you can now remove minPHP 0.x files that are no
longer required in your project.

**Remove the following directories and files:**

- components/acl/
- components/input/
- components/record/
- components/session/
- helpers/date/
- helpers/form/
- helpers/html/
- helpers/javascript/
- helpers/pagination/
- helpers/xml/
- config/core.php
- config/database.php (unless you used it in your `MinphpBridge` service provider)
- config/session.php
- lib/ - **except the modified `init.php` file**

> **Q:** Why do we keep the `init.php` file?

> **A:** Because `index.php` loads it in minPHP 0.x, and we're maintaining
backwards compatibility. If `init.php` isn't loaded anywhere else, then you
could put its contents in another file and update your `index.php` file to load
that file instead. It's up to you.
