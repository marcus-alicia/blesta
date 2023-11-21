<?php

require_once dirname(__FILE__) . DS . '..' . DS . 'clientexec_migrator.php';

/**
 * Clientexec 5.5 Migrator.
 *
 * @package blesta
 * @subpackage blesta.plugins.import_manager.components.migrators.clientexec
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Clientexec5_5 extends ClientexecMigrator
{
    /**
     * Construct.
     *
     * @param Record $local The database connection object to the local server
     */
    public function __construct(Record $local)
    {
        // Call parent constructor
        parent::__construct($local);

        // Set timeout limit
        set_time_limit(60 * 60 * 15); // 15 minutes

        // Load language
        Language::loadLang(['clientexec5_5'], null, dirname(__FILE__) . DS . 'language' . DS);

        // Load necessary models
        Loader::loadModels($this, ['Companies']);

        // Set current path
        $this->path = dirname(__FILE__);
    }

    /**
     * Processes settings (validating input). Sets any necessary input errors.
     *
     * @param array $vars An array of key/value input pairs
     */
    public function processSettings(array $vars = null)
    {
        $rules = [
            'host' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => 'true',
                    'message' => Language::_('Clientexec5_5.!error.host.invalid', true)
                ]
            ],
            'database' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => 'true',
                    'message' => Language::_('Clientexec5_5.!error.database.invalid', true)
                ]
            ],
            'user' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => 'true',
                    'message' => Language::_('Clientexec5_5.!error.user.invalid', true)
                ]
            ],
            'pass' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => 'true',
                    'message' => Language::_('Clientexec5_5.!error.pass.invalid', true)
                ]
            ],
            'passphrase' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => 'true',
                    'message' => Language::_('Clientexec5_5.!error.passphrase.invalid', true)
                ]
            ]
        ];

        // Validates input
        $this->Input->setRules($rules);

        if (!$this->Input->validates($vars)) {
            return;
        }

        if (isset($vars['enable_debug']) && $vars['enable_debug'] == 'true') {
            $this->enable_debug = true;
        }

        // Build settings array
        $this->settings = $vars;

        $default = [
            'driver' => 'mysql',
            'host' => null,
            'database' => null,
            'user' => null,
            'pass' => null,
            'persistent' => false,
            'charset_query' => "SET NAMES 'utf8'",
            'sqlmode_query' => "SET sql_mode='TRADITIONAL'",
            'options' => []
        ];
        $db_info = array_merge($default, $vars);

        // Unset unused parameters
        unset($db_info['import']);
        unset($db_info['step']);
        unset($db_info['enable_debug']);
        unset($db_info['save']);

        // Connect to the remote database
        try {
            $this->remote = new Record($db_info);
            $this->remote->query("SET sql_mode='TRADITIONAL'");
        } catch (Exception $e) {
            $this->Input->setErrors([[$e->getMessage()]]);
            $this->logException($e);

            return;
        }
    }

    /**
     * Processes configuration (validating input). Sets any necessary input errors.
     *
     * @param array $vars An array of key/value input pairs
     */
    public function processConfiguration(array $vars = null)
    {
        // Set mapping for packages (remote ID => local ID)
        if (isset($vars['create_packages']) && $vars['create_packages'] == 'false') {
            $this->mappings['packages'] = [];
            if (isset($vars['remote_packages'])) {
                foreach ($vars['remote_packages'] as $i => $package_id) {
                    $this->mappings['packages'][$package_id] = $vars['local_packages'][$i] == ''
                        ? null
                        : $vars['local_packages'][$i];
                }
            }
        }
    }

    /**
     * Returns a view to handle settings.
     *
     * @param array $vars An array of input key/value pairs
     * @return string The HTML used to request input settings
     */
    public function getSettings(array $vars)
    {
        $this->view = $this->makeView('settings', 'default', str_replace(ROOTWEBDIR, '', dirname(__FILE__) . DS));
        $this->view->set('vars', (object) $vars);

        Loader::loadHelpers($this, ['Html', 'Form']);

        return $this->view->fetch();
    }

    /**
     * Returns a list settings
     *
     * @return array The input settings
     */
    public function getCliSettings()
    {
        return [
            [
                'label' => Language::_("Clientexec5_5.settings.host", true),
                'field' => 'host',
                'type' => 'text'
            ],
            [
                'label' => Language::_("Clientexec5_5.settings.database", true),
                'field' => 'database',
                'type' => 'text'
            ],
            [
                'label' => Language::_("Clientexec5_5.settings.user", true),
                'field' => 'user',
                'type' => 'text'
            ],
            [
                'label' => Language::_("Clientexec5_5.settings.pass", true),
                'field' => 'pass',
                'type' => 'text'
            ],
            [
                'label' => Language::_("Clientexec5_5.settings.passphrase", true),
                'field' => 'key',
                'type' => 'text'
            ],
            [
                'label' => Language::_("Clientexec5_5.settings.enable_debug", true),
                'field' => 'enable_debug',
                'type' => 'bool'
            ],
        ];
    }

    /**
     * Returns a view to configuration run after settings but before import.
     *
     * @param array $vars An array of input key/value pairs
     * @return string The HTML used to request input settings, return null to bypass
     */
    public function getConfiguration(array $vars)
    {
        $this->view = $this->makeView('configuration', 'default', str_replace(ROOTWEBDIR, '', dirname(__FILE__) . DS));
        $this->view->set('vars', (object) $vars);

        Loader::loadHelpers($this, ['Html', 'Form']);
        Loader::loadModels($this, ['Packages']);

        if ($this->remote) {
            $this->loadModel('ClientexecProducts');
            $remote_packages = [];

            foreach ($this->ClientexecProducts->get() as $remote_package) {
                $remote_packages[] = $remote_package;
            }

            $this->view->set('remote_packages', $remote_packages);
            $this->view->set(
                'local_packages',
                $this->Packages->getAll(Configure::get('Blesta.company_id'), ['name' => 'ASC'], null, 'standard')
            );
        }

        return $this->view->fetch();
    }

    /**
     * Returns the module mapping file for the given module, or for the none module if module does not exist.
     *
     * @param string $module The module
     * @param string $module_type The module type ('server' or 'registrar')
     * @return array An array of mapping data
     */
    protected function getModuleMapping($module, $module_type = 'server')
    {
        Configure::load($module, dirname(__FILE__) . DS . 'config' . DS);

        if (!is_array(Configure::get($module . '.map'))) {
            $module = 'generic_' . $module_type;
            Configure::load($module, dirname(__FILE__) . DS . 'config' . DS);
        }

        return Configure::get($module . '.map');
    }
}
