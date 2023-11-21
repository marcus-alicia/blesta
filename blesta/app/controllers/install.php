<?php

use Blesta\Consoleation\Console;
use phpseclib\Crypt\Random;

/**
 * Handle the installation process via web or command line
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Install extends Controller
{
    /**
     * @var array An array of database connection details
     */
    private $db_info = [];

    /**
     * @var array An array of parameters passed via CLI
     */
    private $params = [];

    /**
     * @var bool True if already installed, false otherwise
     */
    private $installed = false;

    /**
     * @var string The default server timezone
     */
    private $server_timezone = 'UTC';

    /**
     * @var array An array of helpers
     */
    protected $helpers = ['Html', 'Form'];

    /**
     * Set up
     */
    public function __construct()
    {
        // Set the vendor web directory path
        if (!defined('VENDORWEBDIR')) {
            define('VENDORWEBDIR', str_replace('/index.php', '', WEBDIR) . 'vendors/');
        }

        // get default timezone from the server
        if (function_exists('date_default_timezone_get')) {
            $this->server_timezone = date_default_timezone_get();
        }

        // Set default timezone to UTC-time
        if (function_exists('date_default_timezone_set')) {
            date_default_timezone_set('UTC');
        }

        parent::__construct();
    }

    /**
     * Check installed status
     */
    public function preAction()
    {
        parent::preAction();

        // Check if installed
        Configure::load('blesta');
        $db_info = Configure::get('Blesta.database_info');
        if ($db_info && !empty($db_info)) {
            $this->installed = true;
        }
        unset($db_info);

        if ($this->is_cli && $this->action != 'index') {
            $this->processCli();
            return false;
        }
    }

    /**
     * Process CLI installation
     */
    private function processCli()
    {
        // Initialize the console
        $this->Console = new Console();

        if ($this->installed) {
            $this->Console->output("Already installed.\n");
            exit;
        }

        // Welcome message
        $this->Console->output("%s\nBlesta CLI Installer\n%s\n", str_repeat('-', 40), str_repeat('-', 40));

        // Set CLI args
        foreach ($_SERVER['argv'] as $i => $val) {
            if ($val == '-dbhost' && isset($_SERVER['argv'][$i + 1])) {
                $this->params['dbhost'] = $_SERVER['argv'][$i + 1];
            }
            if ($val == '-dbport' && isset($_SERVER['argv'][$i + 1])) {
                $this->params['dbport'] = $_SERVER['argv'][$i + 1];
            }
            if ($val == '-dbname' && isset($_SERVER['argv'][$i + 1])) {
                $this->params['dbname'] = $_SERVER['argv'][$i + 1];
            }
            if ($val == '-dbuser' && isset($_SERVER['argv'][$i + 1])) {
                $this->params['dbuser'] = $_SERVER['argv'][$i + 1];
            }
            if ($val == '-dbpass' && isset($_SERVER['argv'][$i + 1])) {
                $this->params['dbpass'] = $_SERVER['argv'][$i + 1];
            }
            if ($val == '-hostname' && isset($_SERVER['argv'][$i + 1])) {
                $this->params['hostname'] = $_SERVER['argv'][$i + 1];
            }
            if ($val == '-docroot' && isset($_SERVER['argv'][$i + 1])) {
                $this->params['docroot'] = $_SERVER['argv'][$i + 1];
            }
            if ($val == '-help' || $val == '-h') {
                $this->Console->output("The options are as follows:\n");
                $this->Console->output("-dbhost The database host\n");
                $this->Console->output("-dbport The database port\n");
                $this->Console->output("-dbname The database name\n");
                $this->Console->output("-dbuser The database user\n");
                $this->Console->output("-dbpass The database password\n");
                $this->Console->output("-hostname The server hostname\n");
                $this->Console->output("-docroot The absolute path to the web server's document root\n");
                $this->Console->output("Pass no parameters to install via interactive mode.\n");
                exit;
            }
        }

        // If any arguments are passed, assume we're running in "automatic" mode
        // and do not prompt for any additional input
        if (empty($this->params)) {
            $this->agreeCli();
            $this->systemRequirementsCli();
        }
        $this->databaseCli();

        // Write the config
        $config_written = false;
        while (!$config_written) {
            $this->Console->output('Attempting to write config... ');

            try {
                $config_written = $this->writeConfig();
            } catch (Throwable $e) {
                // nothing to do
            }

            if ($config_written) {
                $this->Console->output("Success.\n");
            } else {
                $this->Console->output(
                    "Ensure that the file (%s) is writable.\nPress any key to retry.",
                    CONFIGDIR . 'blesta-new.php'
                );
                $this->Console->getLine();
            }
        }

        try {
            $this->postInstall();
        } catch (Throwable $e) {
            $this->Console->output("\nERROR:" . $e->getMessage() . "\n");
            $this->Console->output("Installation FAILED.\n");
            exit(1);
        }

        $this->Console->output(
            "\nFinished. To complete setup visit /admin/login/ in your browser,
            \nor if you do not have mod_rewrite, /index.php/admin/login/.\n"
        );

        // Success
        exit(0);
    }

    /**
     * Agree to terms and conditions
     */
    private function agreeCli()
    {
        $this->Console->output(
            "Please acknowledge your agreement to the terms and conditions as explained at
            \nhttp://www.blesta.com/license/\n\n"
        );
        $this->Console->output('Do you agree? (Y/N): ');

        $agreed = false;
        while (!$agreed) {
            switch (strtolower(substr($this->Console->getLine(), 0, 1))) {
                case 'y':
                    $agreed = true;
                    break;
                case 'n':
                    exit;
                default:
                    $this->Console->output("You must agree to the terms and conditions in order to continue.\n");
                    $this->Console->output('Do you agree? (Y/N): ');
                    break;
            }
        }
    }

    /**
     * Handle system requirements check
     */
    private function systemRequirementsCli()
    {
        $this->Console->output("Performing system requirements check...\n");

        if (($reqs = $this->meetsMinReq()) !== true) {
            $this->Console->output("The following minimum requirements failed:\n");

            foreach ($reqs as $key => $req) {
                $this->Console->output("\t" . $key . ': ' . $req['message'] . "\n");
            }

            $this->Console->output(
                "Failed minimum system requirements. You must correct these issues before continuing.\n"
            );
            exit(2);
        } elseif (($reqs = $this->meetsRecReq()) !== true) {
            $this->Console->output("The following recommended requirements failed:\n");

            foreach ($reqs as $key => $req) {
                $this->Console->output("\t" . $key . ': ' . $req['message'] . "\n");
            }

            $this->Console->output('Do you wish to continue anyway? (Y): ');
            if (strtolower(substr($this->Console->getLine(), 0, 1)) != 'y') {
                exit;
            }
        }
    }

    /**
     * Collect DB information, verify credentials and run DB installation
     */
    private function databaseCli()
    {
        if (empty($this->params)) {
            $this->Console->output("You will now be asked to enter your database credentials.\n");
        }

        $valid = false;
        while (!$valid) {
            if (empty($this->params)) {
                $this->Console->output('Database host (default localhost): ');
                $host = $this->Console->getLine();
                if (!$host) {
                    $host = 'localhost';
                }

                $this->Console->output('Database port (default 3306): ');
                $port = $this->Console->getLine();
                if (!$port) {
                    $port = '3306';
                }

                $database = '';
                while ($database == '') {
                    $this->Console->output('Database name: ');
                    $database = $this->Console->getLine();

                    if ($database == '') {
                        $this->Console->output("\nA database name is required\n");
                    }
                }

                $this->Console->output('Database user: ');
                $user = $this->Console->getLine();
                $this->Console->output('Database password: ');
                $password = $this->Console->getLine();

                $this->Console->output('Attempting to verify database credentials... ');
            } else {
                $host = isset($this->params['dbhost']) ? $this->params['dbhost'] : null;
                $port = isset($this->params['dbport']) ? $this->params['dbport'] : null;
                $database = isset($this->params['dbname']) ? $this->params['dbname'] : null;
                $user = isset($this->params['dbuser']) ? $this->params['dbuser'] : null;
                $password = isset($this->params['dbpass']) ? $this->params['dbpass'] : null;
            }
            $this->db_info = [
                'driver' => 'mysql',
                'host' => $host,
                'port' => $port,
                'database' => $database,
                'user' => $user,
                'pass' => $password,
                'persistent' => false,
                'charset_query' => "SET NAMES 'utf8'",
                'sqlmode_query' => "SET sql_mode='TRADITIONAL'",
                'options' => []
            ];

            try {
                // Verify credentials by attempting connection to DB
                $this->components(['Upgrades' => [$this->db_info]]);

                $this->Console->output("OK\n");

                $this->Console->output('Checking InnoDB support... ');
                if (!$this->checkDb()) {
                    $this->Console->output("FAILED\n");
                    exit(3);
                }
                $this->Console->output("OK\n");

                // Preset queries
                $this->presetQueries(
                    $this->Upgrades,
                    [$this->db_info['charset_query'], $this->db_info['sqlmode_query']]
                );

                $valid = true;
            } catch (Throwable $e) {
                $this->Console->output(
                    "\nConnection FAILED. Ensure that you have created the database and that
                    the credentials are correct.\n"
                );
                if (!empty($this->params)) {
                    exit(4);
                }
            }
        }

        $statement = $this->Upgrades->query('SHOW TABLES');
        if ($statement->fetch()) {
            $this->Console->output("Installation cannot continue unless the database is empty.\n");
            exit(5);
        }
        $statement->closeCursor();

        // Install database schema
        $this->Console->output("Installing database...\n");
        $this->Upgrades->processSql(
            COMPONENTDIR . 'upgrades' . DS . 'db' . DS . 'schema.sql',
            [$this->Console, 'progressBar']
        );
        $this->Console->output("Completed.\n");

        // Set initial state of the database
        $this->Console->output("Configuring database...\n");
        $this->Upgrades->processSql(
            COMPONENTDIR . 'upgrades' . DS . 'db' . DS . '3.0.0' . DS . '1.sql',
            [$this->Console, 'progressBar']
        );
        $this->Console->output("Completed.\n");

        // Attempt to upgrade from base database to current version
        $this->Console->output("Upgrading database...\n");
        $this->Upgrades->start('3.0.0-a3', null, [$this->Console, 'progressBar']);

        if (($errors = $this->Upgrades->errors())) {
            $this->Console->output("Upgrade could not complete, the following errors occurred:\n");
            foreach ($errors as $key => $value) {
                $this->Console->output(implode("\n", $value) . "\n");
            }
        } else {
            $this->Console->output("Completed.\n\n");
        }
    }

    /**
     * Write the config file details
     *
     * @return false If the file could not be renamed (e.g. written to)
     */
    private function writeConfig()
    {
        // Attempt to rename the config from blesta-new.php to blesta.php
        if (!rename(CONFIGDIR . 'blesta-new.php', CONFIGDIR . 'blesta.php')) {
            return false;
        }

        // Generate a sufficiently large random value
        $random = new Random();
        $length = 16;
        $system_key = md5($random::string($length) . uniqid(php_uname('n'), true))
            . md5(uniqid(php_uname('n'), true) . $random::string($length));

        $config = file_get_contents(CONFIGDIR . 'blesta.php');
        $replacements = [
            '{database_host}' => $this->db_info['host'],
            '{database_port}' => $this->db_info['port'],
            '{database_name}' => $this->db_info['database'],
            '{database_user}' => $this->db_info['user'],
            '{database_password}' => $this->db_info['pass'],
            '{system_key}' => $system_key
        ];
        foreach ($replacements as &$value) {
            $value = str_replace(['\\', '\''], ['\\\\', '\\\''], $value);
        }

        file_put_contents(
            CONFIGDIR . 'blesta.php',
            str_replace(array_keys($replacements), array_values($replacements), $config)
        );

        return true;
    }

    /**
     * Install
     */
    public function index()
    {
        // Process the command line installation if requested via CLI
        if ($this->is_cli) {
            $this->processCli();
            return false;
        }

        // If already installed send to admin interface
        if ($this->installed) {
            $this->redirect(WEBDIR);
        }

        $this->structure->set('title', 'Blesta Installer');
    }

    /**
     * Process GUI installation
     */
    public function process()
    {
        // Nothing to do here if CLI
        if ($this->is_cli) {
            return;
        }

        // If already installed send to admin interface
        if ($this->installed) {
            $this->redirect(WEBDIR);
        }

        // Test for minimum requirements
        $pass_min = $this->meetsMinReq();
        // Test for recommended requirements
        $pass_rec = $this->meetsRecReq();

        $error = false;

        if (!empty($this->post) && !isset($this->post['reload'])) {
            // Ensure acceptance of license agreement
            if (!isset($this->post['agree']) || $this->post['agree'] != 'yes') {
                $error = 'You must agree to the terms and conditions in order to continue.';
            }

            // Ensure passes min requirements
            if (!$error && $pass_min !== true) {
                $error = 'Failed minimum system requirements. You must correct these issues before continuing.';
            }

            // Check database credentials
            if (!$error) {
                $this->db_info = [
                    'driver' => 'mysql',
                    'host' => $this->post['host'],
                    'port' => $this->post['port'],
                    'database' => $this->post['database'],
                    'user' => $this->post['user'],
                    'pass' => $this->post['password'],
                    'persistent' => false,
                    'charset_query' => "SET NAMES 'utf8'",
                    'sqlmode_query' => "SET sql_mode='TRADITIONAL'",
                    'options' => []
                ];

                try {
                    // Verify credentials by attempting connection to DB
                    $this->components(['Upgrades' => [$this->db_info]]);

                    if (!$this->checkDb()) {
                        $error = 'Failed InnoDB support check.';
                    }

                    // Preset queries
                    $this->presetQueries(
                        $this->Upgrades,
                        [$this->db_info['charset_query'], $this->db_info['sqlmode_query']]
                    );
                } catch (Throwable $e) {
                    $error = 'Database connection FAILED. Ensure that you have created the
                        database and that the credentials are correct.';
                }
            }

            // Install database
            if (!$error) {
                try {
                    $statement = $this->Upgrades->query('SHOW TABLES');
                    if ($statement->fetch()) {
                        $error = "Installation cannot continue unless the database is empty.\n";
                    }
                    $statement->closeCursor();
                } catch (Throwable $e) {
                    // Likely invalid/blank database credentials
                    $error = 'Database connection FAILED. Ensure that you have created the
                        database and that the credentials are correct.';
                }

                if (!$error) {
                    // Install database schema
                    $this->Upgrades->processSql(COMPONENTDIR . 'upgrades' . DS . 'db' . DS . 'schema.sql');

                    // Set initial state of the database
                    $this->Upgrades->processSql(COMPONENTDIR . 'upgrades' . DS . 'db' . DS . '3.0.0' . DS . '1.sql');

                    // Attempt to upgrade from base database to current version
                    $this->Upgrades->start('3.0.0-a3', null);

                    if (($errors = $this->Upgrades->errors())) {
                        if ($this->is_cli) {
                            $this->Console->output("Upgrade could not complete, the following errors occurred:\n");
                        }
                        $error = [];
                        foreach ($errors as $key => $value) {
                            if ($this->is_cli) {
                                $this->Console->output(implode("\n", (array) $value) . "\n");
                            }
                            $error[] = implode("\n", (array) $value);
                        }
                    }
                }
            }

            // Write config
            if (!$error) {
                $config_written = false;
                try {
                    $config_written = $this->writeConfig();
                } catch (Throwable $e) {
                    // nothing to do
                }

                if (!$config_written) {
                    $error = sprintf('Ensure that the file (%s) is writable.', CONFIGDIR . 'blesta-new.php');
                }
            }

            // Post install
            if (!$error) {
                try {
                    $this->postInstall();
                    $this->redirect(WEBDIR . Configure::get('Route.admin') . '/login/setup/');
                } catch (Throwable $e) {
                    $error = sprintf('Installation FAILED: %s', $e->getMessage());
                }
            }

            if ($error) {
                $this->setMessage('error', $error);
            }
        }

        $this->set('vars', (object) $this->post);
        $this->set('min_requirements', $this->getMinReq());
        $this->set('pass_min', $pass_min);
        $this->set('rec_requirements', $this->getRecReq());
        $this->set('pass_rec', $pass_rec);
        $this->structure->set('title', 'Blesta Installer');
    }

    /**
     * Finish the installation process by generating key pairs and installing base plugins
     */
    private function postInstall()
    {
        // Load our newly created config file
        Configure::load('blesta');
        // Set the database connection profile
        Configure::set('Database.profile', Configure::get('Blesta.database_info'));

        Configure::set('Blesta.company_id', 1);

        $this->uses(['Companies', 'PluginManager', 'Settings', 'ModuleManager']);

        // Set temp directory
        $this->Settings->setSetting('temp_dir', $this->tmpDir());

        // Set default timezone
        $this->Companies->setSetting(Configure::get('Blesta.company_id'), 'timezone', $this->server_timezone);

        if ($this->is_cli) {
            $this->Console->output('Generating encryption keys. This may take a minute or two... ');
        }

        // Generate/set key pair
        $key_length = 1024;
        // Only allow large keys if the system can handle them efficiently
        if (extension_loaded('gmp')) {
            $key_length = 3072;
        }
        $key_pair = $this->Companies->generateKeyPair(Configure::get('Blesta.company_id'), $key_length);

        // Set hostname for default company and the root_web_dir setting
        $hostname = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : null;
        $docroot = DOCROOTDIR;
        if ($this->is_cli) {
            $hostname = isset($this->params['hostname']) ? $this->params['hostname'] : php_uname('n');
            $docroot = isset($this->params['docroot']) ? $this->params['docroot'] : $docroot;
        }

        $this->Companies->edit(Configure::get('Blesta.company_id'), ['hostname' => $hostname]);
        $this->Settings->setSetting('root_web_dir', $docroot);

        if ($this->is_cli) {
            $this->Console->output("Done.\n");
            $this->Console->output('Creating uploads directory... ');
        }

        // Attempt to create the uploads directory above the web directory if it does not already exist
        $webdir = str_replace('/', DS, str_replace('index.php/', '', WEBDIR));
        $public_root_web = rtrim(str_replace($webdir == DS ? '' : $webdir, '', ROOTWEBDIR), DS) . DS;
        $upload_dir = realpath(dirname($public_root_web)) . DS . 'uploads' . DS;
        if (!file_exists($upload_dir)) {
            if (@mkdir($upload_dir, 0755)) {
                @mkdir($upload_dir . '1' . DS, 0755);
                @mkdir($upload_dir . 'system' . DS, 0755);

                // Attempt to create an .htaccess file to deny access to the directory just in case
                // it's made public and mod rewrite is available to deny access
                $htaccess = <<<HT
Order deny,allow
Deny from all
HT;
                file_put_contents($upload_dir . '.htaccess', $htaccess);
            }
        }

        // Save the new uploads_dir setting
        $this->Settings->setSetting('uploads_dir', $upload_dir);

        // Save the parity setting
        $this->Settings->setSetting(
            'system_key_parity_string',
            $this->Settings->systemEncrypt("I pity the fool that doesn't copy their config file!")
        );

        // Set plugins that need to be installed by default
        $plugins = [
            'system_status',
            'system_overview',
            'billing_overview',
            'feed_reader',
            'cms',
            'domains',
            'order',
            'support_manager'
        ];

        if ($this->is_cli) {
            $this->Console->output("Done.\n");
            $this->Console->output('Installing default plugins... ');
        }

        // Install base plugins
        foreach ($plugins as $plugin) {
            $this->PluginManager->add(
                ['dir' => $plugin, 'company_id' => Configure::get('Blesta.company_id'), 'staff_group_id' => 1]
            );
        }

        if ($this->is_cli) {
            $this->Console->output('Done.\n');
            $this->Console->output('Installing default modules... ');
        }

        // Set modules that need to be installed by default
        $modules = ['none', 'generic_domains'];

        // Install base modules
        foreach ($modules as $module) {
            // Install it if it wasn't already installed from the upgrade scripts
            $none = $this->ModuleManager->getByClass($module, Configure::get('Blesta.company_id'));
            if (empty($none)) {
                $this->ModuleManager->add(['class' => $module, 'company_id' => Configure::get('Blesta.company_id')]);
            }
        }

        if ($this->is_cli) {
            $this->Console->output('Done.\n');
        }
    }

    /**
     * Determine the location of the temp directory on this system
     */
    private function tmpDir()
    {
        $dir = ini_get('upload_tmp_dir');

        if (!$dir && function_exists('sys_get_temp_dir')) {
            $dir = sys_get_temp_dir();
        }

        if (!$dir) {
            $dir = '/tmp/';
            if ($this->getOs() == 'WIN') {
                $dir = 'C:\\Windows\\TEMP\\';
            }
        }

        $dir = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        return $dir;
    }

    /**
     * Runs the given queries on the given model. The database connection must already be established.
     *
     * @param Model $model The model to run the query from
     * @param array $queries An array of SQL statements to execute
     */
    private function presetQueries(Model $model, array $queries)
    {
        foreach ($queries as $query) {
            $model->query($query)
                ->closeCursor();
        }
    }

    /**
     * Check DB support
     *
     * @return bool True if InnoDB is supported, false otherwise
     */
    private function checkDb()
    {
        $innodb_supported = false;

        try {
            $engines = $this->Upgrades->query('SHOW ENGINES')->fetchAll();
            foreach ($engines as $engine) {
                if (strtolower($engine->engine) == 'innodb') {
                    if (strtolower($engine->support) == 'yes' || strtolower($engine->support) == 'default') {
                        $innodb_supported = true;
                    }
                    break;
                }
            }
        } catch (Throwable $e) {
            // Check for InnoDB support
            $statement = $this->Upgrades->query("SHOW VARIABLES LIKE 'have_innodb'");
            $have_innodb = $statement->fetch();
            $statement->closeCursor();

            if (strtolower($have_innodb->value) == 'yes') {
                $innodb_supported = true;
            }
        }
        return $innodb_supported;
    }

    /**
     * Sets the given error type into the view
     *
     * @param string $type The type of message ("message", "error", "info", or "notice")
     * @param string $value The text to display
     * @param bool $return True to return the message, false to set it withing the view
     */
    protected function setMessage($type, $value, $return = false)
    {
        $this->messages[$type] = $value;

        $message = $this->partial('message', $this->messages, Configure::get('System.default_view'));

        if ($return) {
            return $message;
        }
        $this->set('message', $message);
    }

    /**
     * Returns an array of minimum requirements
     *
     * @return array An associative array of requirements
     */
    protected function getMinReq()
    {
        Language::loadLang('system_requirements');

        // Determine the message to show for the ioncube extension, i.e., one with or without the ioncube loader version
        $ioncube_ext = 'ioncube loader';
        $ioncube_message = (extension_loaded($ioncube_ext) && function_exists('ioncube_loader_version')
            ? Language::_(
                'SystemRequirements.!error.extension_version.minimum',
                true,
                $ioncube_ext,
                ioncube_loader_version()
            )
            : Language::_('SystemRequirements.!error.extension.minimum', true, $ioncube_ext)
        );

        // Requirements and their values
        $reqs = [
            'php' => [
                'message' => Language::_('SystemRequirements.!error.php.minimum', true, '7.2.0', PHP_VERSION),
                'req' => true,
                'cur' => version_compare(PHP_VERSION, '7.2.0', '>=')
            ],
            'ext-pdo' => [
                'message' => Language::_('SystemRequirements.!error.extension.minimum', true, 'pdo'),
                'req' => true,
                'cur' => extension_loaded('pdo')
            ],
            'ext-pdo_mysql' => [
                'message' => Language::_('SystemRequirements.!error.extension.minimum', true, 'pdo_mysql'),
                'req' => true,
                'cur' => extension_loaded('pdo_mysql')
            ],
            'ext-curl' => [
                'message' => Language::_('SystemRequirements.!error.extension.minimum', true, 'curl'),
                'req' => true,
                'cur' => extension_loaded('curl')
            ],
            'ext-openssl' => [
                'message' => Language::_('SystemRequirements.!error.extension.minimum', true, 'openssl'),
                'req' => true,
                'cur' => extension_loaded('openssl')
            ],
            'config_writable' => [
                'message' => Language::_(
                    'SystemRequirements.!error.config_writable.minimum',
                    true,
                    CONFIGDIR . 'blesta-new.php',
                    CONFIGDIR
                ),
                'req' => true,
                'cur' => is_writable(CONFIGDIR) && is_writable(CONFIGDIR . 'blesta-new.php')
            ], // To auto-write config file to config dir
        ];

        if (version_compare(PHP_VERSION, '8.1.0', '>=')) {
            $reqs['ext-ioncube-sourceguardian'] = [
                'message' => Language::_(
                    'SystemRequirements.!' . (extension_loaded('SourceGuardian') ? 'info' : 'error') . '.ext-ioncube-sourceguardian.minimum',
                    true
                ),
                'req' => true,
                'cur' => extension_loaded('SourceGuardian') || extension_loaded($ioncube_ext)
            ];
        } elseif (version_compare(PHP_VERSION, '8.0.0', '>=')) {
            $reqs['ext-sourceguardian'] = [
                'message' => Language::_('SystemRequirements.!error.extension.minimum', true, 'SourceGuardian'),
                'req' => true,
                'cur' => extension_loaded('SourceGuardian')
            ];
        } else {
            $reqs['ext-ioncube'] = [
                'message' => $ioncube_message,
                'req' => true,
                'cur' => extension_loaded($ioncube_ext)
            ];
        }

        return $reqs;
    }

    /**
     * Returns an array of recommended requirements
     *
     * @return array An associative array of requirements
     */
    protected function getRecReq()
    {
        Language::loadLang('system_requirements');

        // Requirements and their values
        $reqs = [
            'ext-gd' => [
                'message' => Language::_('SystemRequirements.!warning.gd.recommended', true),
                'req' => true,
                'cur' => extension_loaded('gd')
            ], // Invoice PDFs may perform image manipulation
            'ext-gmp' => [
                'message' => Language::_('SystemRequirements.!warning.gmp.recommended', true),
                'req' => true,
                'cur' => extension_loaded('gmp')
            ], // Faster BigInteger (for RSA encryption/decrpytion)
            'ext-imap' => [
                'message' => Language::_('SystemRequirements.!warning.imap.recommended', true),
                'req' => true,
                'cur' => extension_loaded('imap')
            ], // send/receive mail via SMTP/IMAP
            'ext-libxml' => [
                'message' => Language::_('SystemRequirements.!warning.libxml.recommended', true),
                'req' => true,
                'cur' => extension_loaded('libxml')
            ], // Some modules/gateways may require XML parsing support
            'ext-mailparse' => [
                'message' => Language::_('SystemRequirements.!warning.mailparse.recommended', true),
                'req' => true,
                'cur' => extension_loaded('mailparse')
            ], // Some modules/gateways may require iconv
            'ext-iconv' => [
                'message' => Language::_('SystemRequirements.!warning.iconv.recommended', true),
                'req' => true,
                'cur' => extension_loaded('iconv')
            ], // Faster encryption/decryption
            'ext-mbstring' => [
                'message' => Language::_('SystemRequirements.!warning.mbstring.recommended', true),
                'req' => true,
                'cur' => extension_loaded('mbstring')
            ], // Libraries that require multi-byte string handling
            'cache_writable' => [
                'message' => Language::_('SystemRequirements.!warning.cache_writable.recommended', true, CACHEDIR),
                'req' => true,
                'cur' => is_writable(CACHEDIR)
            ], // To cache view files for performance
            'ext-simplexml' => [
                'message' => Language::_('SystemRequirements.!warning.simplexml.recommended', true),
                'req' => true,
                'cur' => extension_loaded('simplexml')
            ], // Some modules/gateways may require XML parsing support
            'ext-zlib' => [
                'message' => Language::_('SystemRequirements.!warning.zlib.recommended', true),
                'req' => true,
                'cur' => extension_loaded('zlib')
            ], // Automatic VAT tax handling requires the SOAP extension
            'ext-soap' => [
                'message' => Language::_('SystemRequirements.!warning.soap.recommended', true),
                'req' => true,
                'cur' => extension_loaded('soap')
            ]
        ];

        return $reqs;
    }

    /**
     * Checks to ensure that the current system meets the required minimum
     * requirements.
     *
     * @return mixed Boolean true if all requirements met, an array of failed requirements on failure
     */
    protected function meetsMinReq()
    {
        return $this->meetsReq($this->getMinReq());
    }

    /**
     * Checks to ensure that the current system meets the recommended minimum
     * requirements.
     *
     * @return mixed Boolean true on success, an array of failed requirements on failure
     */
    protected function meetsRecReq()
    {
        return $this->meetsReq($this->getRecReq());
    }

    /**
     * Tests the requirements
     *
     * @param array $reqs An array of requirements to test
     * @return bool|array True if passing, an array of extensions that failed otherwise
     */
    protected function meetsReq(array $reqs)
    {
        $failed_reqs = [];

        foreach ($reqs as $key => $req) {
            $failed = false;
            if (array_key_exists('compare', $req) && !call_user_func($req['compare'], $req['req'], $req['cur'])) {
                $failed = true;
            } elseif (!$req['cur'] || $req['cur'] < $req['req']) {
                $failed = true;
            }

            if ($failed) {
                $failed_reqs[$key] = $req;
            }
        }

        if (empty($failed_reqs)) {
            return true;
        }
        return $failed_reqs;
    }

    /**
     * Determine the operating system on this system
     *
     * @return string A 3-character OS name (e.g. LIN or WIN)
     */
    protected function getOs()
    {
        return strtoupper(substr(php_uname('s'), 0, 3));
    }
}
