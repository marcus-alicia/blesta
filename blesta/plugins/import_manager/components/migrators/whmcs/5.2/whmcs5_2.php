<?php
require_once dirname(__FILE__) . DS . '..' . DS . 'whmcs_migrator.php';

/**
 * WHMCS 5.2 Migrator
 *
 * @package blesta
 * @subpackage blesta.plugins.import_manager.components.migrators.whmcs
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Whmcs5_2 extends WhmcsMigrator
{
    /**
     * Construct
     *
     * @param Record $local The database connection object to the local server
     */
    public function __construct(Record $local)
    {
        parent::__construct($local);

        set_time_limit(60*60*15); // 15 minutes

        Language::loadLang(['whmcs5_2'], null, dirname(__FILE__) . DS . 'language' . DS);

        Loader::loadModels($this, ['Companies']);

        $this->path = dirname(__FILE__);
    }

    /**
     * Processes settings (validating input). Sets any necessary input errors
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
                    'message' => Language::_('Whmcs5_2.!error.host.invalid', true)
                ]
            ],
            'database' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => 'true',
                    'message' => Language::_('Whmcs5_2.!error.database.invalid', true)
                ]
            ],
            'user' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => 'true',
                    'message' => Language::_('Whmcs5_2.!error.user.invalid', true)
                ]
            ],
            'pass' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => 'true',
                    'message' => Language::_('Whmcs5_2.!error.pass.invalid', true)
                ]
            ]
        ];

        $this->Input->setRules($rules);

        if (!$this->Input->validates($vars)) {
            return;
        }

        if (isset($vars['enable_debug']) && $vars['enable_debug'] == 'true') {
            $this->enable_debug = true;
        }

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

        try {
            $this->remote = new Record($db_info);
            $this->remote->query("SET NAMES utf8");
            $this->remote->query("SET sql_mode='TRADITIONAL'");
        } catch (Throwable $e) {
            $this->Input->setErrors([[$e->getMessage()]]);
            $this->logException($e);
            return;
        }
    }

    /**
     * Processes configuration (validating input). Sets any necessary input errors
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
     * Returns a view to handle settings
     *
     * @param array $vars An array of input key/value pairs
     * @return string The HTML used to request input settings
     */
    public function getSettings(array $vars)
    {
        $this->view = $this->makeView('settings', 'default', str_replace(ROOTWEBDIR, '', dirname(__FILE__) . DS));
        $this->view->set('vars', (object)$vars);

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
                'label' => Language::_("Whmcs5_2.settings.host", true),
                'field' => 'host',
                'type' => 'text'
            ],
            [
                'label' => Language::_("Whmcs5_2.settings.database", true),
                'field' => 'database',
                'type' => 'text'
            ],
            [
                'label' => Language::_("Whmcs5_2.settings.user", true),
                'field' => 'user',
                'type' => 'text'
            ],
            [
                'label' => Language::_("Whmcs5_2.settings.pass", true),
                'field' => 'pass',
                'type' => 'text'
            ],
            [
                'label' => Language::_("Whmcs5_2.settings.key", true),
                'field' => 'key',
                'type' => 'text'
            ],
            [
                'label' => Language::_("Whmcs5_2.settings.balance_credit", true),
                'field' => 'balance_credit',
                'type' => 'bool'
            ],
            [
                'label' => Language::_("Whmcs5_2.settings.enable_debug", true),
                'field' => 'enable_debug',
                'type' => 'bool'
            ],
        ];
    }

    /**
     * Returns a view to configuration run after settings but before import
     *
     * @param array $vars An array of input key/value pairs
     * @return string The HTML used to request input settings, return null to bypass
     */
    public function getConfiguration(array $vars)
    {
        $this->view = $this->makeView('configuration', 'default', str_replace(ROOTWEBDIR, '', dirname(__FILE__) . DS));
        $this->view->set('vars', (object)$vars);

        Loader::loadHelpers($this, ['Html', 'Form']);
        Loader::loadModels($this, ['Packages']);

        if ($this->remote) {
            $this->loadModel('WhmcsProducts');
            $remote_packages = [];

            foreach ($this->WhmcsProducts->get() as $remote_package) {
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
     * Returns the module mapping file for the given module, or for the none module if module does not exist
     *
     * @param string $module The module
     * @param string $module_type The module type ('server' or 'registrar')
     * @return array An array of mapping data
     */
    protected function getModuleMapping($module, $module_type = 'server')
    {
        Configure::load($module, dirname(__FILE__) . DS . 'config' . DS);

        if (!is_array(Configure::get($module . '.map'))) {
            $version = abs((int)filter_var($module, FILTER_SANITIZE_NUMBER_INT));
            $module = substr($module, 0, strpos($module, (string)$version));

            Configure::load($module, dirname(__FILE__) . DS . 'config' . DS);
        }

        if (!is_array(Configure::get($module . '.map'))) {
            $module = 'generic_' . $module_type;
            Configure::load($module, dirname(__FILE__) . DS . 'config' . DS);
        }

        return Configure::get($module . '.map');
    }
}
