<?php

use Blesta\Consoleation\Console;

/**
 * Import Manager manage plugin controller
 *
 * @package blesta
 * @subpackage blesta.plugins.import_manager
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminManagePlugin extends AppController
{
    /**
     * @var array An array of parameters passed via CLI
     */
    private $params = [];

    /**
     * Performs necessary initialization
     */
    private function init()
    {
        // Require login
        $this->parent->requireLogin();

        Language::loadLang('import_manager_manage_plugin', null, PLUGINDIR . 'import_manager' . DS . 'language' . DS);

        $this->uses(['ImportManager.ImportManagerImporter']);
        // Use the parent date helper, it's already configured properly
        $this->Date = $this->parent->Date;

        // Set the company ID
        $this->company_id = Configure::get('Blesta.company_id');

        // Set the plugin ID
        $this->plugin_id = (isset($this->get[0]) ? $this->get[0] : null);

        // Set the page title
        $this->parent->structure->set(
            'page_title',
            Language::_(
                'ImportManagerManagePlugin.'
                . Loader::fromCamelCase($this->action ? $this->action : 'index') . '.page_title',
                true
            )
        );

        // Set the view to render for all actions under this controller
        $this->view->setView(null, 'ImportManager.default');
    }

    /**
     * Returns the view to be rendered when managing this plugin
     */
    public function index()
    {
        // Process the command line installation if requested via CLI
        if ($this->is_cli) {
            $this->processCli();
            return false;
        }

        $this->init();

        $vars = [
            'migrators' => $this->ImportManagerImporter->getMigrators(),
            'plugin_id' => $this->plugin_id,
            'message' => $this->setMessage('notice', Language::_('ImportManagerManagePlugin.index.import_via_cli', true, ROOTWEBDIR), true)
        ];

        // Set the view to render
        return $this->partial('admin_manage_plugin', $vars);
    }

    /**
     * Process CLI installation
     */
    private function processCli()
    {
        // Initialize the console
        $this->Console = new Console();

        // Welcome message
        $this->Console->output("%s\nBlesta CLI Importer\n%s\n", str_repeat('-', 40), str_repeat('-', 40));

        // Set CLI args
        foreach ($_SERVER['argv'] as $i => $val) {
            if ($val == '--type' && isset($_SERVER['argv'][$i + 1])) {
                $this->params['type'] = $_SERVER['argv'][$i + 1];
            }
            if ($val == '--version' && isset($_SERVER['argv'][$i + 1])) {
                $this->params['version'] = $_SERVER['argv'][$i + 1];
            }

            if ($val == '--help' || $val == '-h') {
                $this->Console->output("The options are as follows:\n");
                $this->Console->output("--type The type of migrator to use (e.g. whmcs)\n");
                $this->Console->output("--version The version of the migrator (e.g. 5.2 or 8.0)\n\n");
                $this->Console->output("Pass no parameters to import via interactive mode.\n");
                exit;
            }
        }

        $this->settingsCli();
    }

    /**
     * Import from a migrator via cli
     */
    public function settingsCli()
    {
        $this->uses(['ImportManager.ImportManagerImporter']);
        $this->components(['ImportManager.Migrators']);
        
        // Set the company ID
        $this->company_id = 1;
        $migrators = $this->ImportManagerImporter->getMigrators();
        $input_type = null;
        $input_version = null;
        $selected_migrator = null;
        while (true) {
            $this->Console->output("Choose a migrator from the following list: \n");
            foreach ($migrators as $migrator) {
                if ($migrator->supports_cli) {
                    $this->Console->output(' - ' . $migrator->name . "\n");
                }
            }
            $input_type = strtolower($this->Console->getLine());

            foreach ($migrators as $migrator) {
                if ($migrator->supports_cli && $input_type == strtolower($migrator->name)) {
                    $selected_migrator = $migrator;
                    break 2;
                }
            }
        }

        while (true) {
            $this->Console->output("Choose a version from the following list: \n");
            foreach ($selected_migrator->versions as $key => $version) {
                $this->Console->output(' - ' . $key . "\n");
            }
            $input_version = strtolower($this->Console->getLine());

            foreach ($selected_migrator->versions as $key => $version) {
                if ($key == $input_version) {
                    break 2;
                }
            }
        }

        $created_migrator = $this->Migrators->create(
            $input_type,
            $input_version,
            [$this->ImportManagerImporter->Record]
        );

        if (!$created_migrator) {
            $this->Console->output("The migrator type or version is invalid\n");
            return false;
        }

        $this->Console->output("You will now be asked to enter settings.\n");
        while (true) {
            foreach ($created_migrator->getCliSettings() as $setting) {
                $this->params[$setting['field']] = $this->handleCliSetting($setting);
            }

            $created_migrator->processSettings($this->params);

            if (($errors = $created_migrator->errors())) {
                foreach ($errors as $error) {
                    $this->Console->output($error['valid'] . "\n");
                }
            } else {
                // Process migration
                break;
            }
        }

        $this->ImportManagerImporter->runMigrator(
            $input_type,
            $input_version,
            $this->params
        );
    }

    private function handleCliSetting($setting)
    {
        if ($setting['type'] == 'bool') {
            $this->Console->output($setting['label'] . " (Y/N): \n");

            $setting = false;
            while (!$setting) {
                switch (strtolower(substr($this->Console->getLine(), 0, 1))) {
                    case 'y':
                        $setting = 'true';
                        break;
                    case 'n':
                        $setting = 'false';
                        break;
                    default:
                        $this->Console->output($setting['label'] . " (Y/N): \n");
                        break;
                }
            }
        } else {
            $this->Console->output($setting['label'] . ": \n");
            $setting = strtolower($this->Console->getLine());
        }

        return $setting;
    }

    /**
     * Import from a migrator
     */
    public function import()
    {
        $this->init();

        $this->components(['ImportManager.Migrators']);

        $type = isset($this->get[1]) ? $this->get[1] : null;
        $version = isset($this->get[2]) ? $this->get[2] : null;
        $migrator = $this->Migrators->create($type, $version, [$this->ImportManagerImporter->Record]);

        if (!$migrator) {
            $this->redirect($this->base_uri . 'settings/company/plugins/manage/' . $this->plugin_id . '/');
        }

        $vars = ['content' => $migrator->getSettings($this->post)];
        $vars['continue'] = $migrator->getConfiguration($this->post) != null;

        $migrate = false;
        if (!empty($this->post)) {
            if (!isset($this->post['step'])) {
                $this->post['step'] = 'settings';
            }

            switch ($this->post['step']) {
                default:
                case 'settings':
                    $migrator->processSettings($this->post);

                    if (($errors = $migrator->errors())) {
                        $vars['message'] = $this->setMessage('error', $errors, true, null, false);
                        $vars['content'] = $migrator->getSettings($this->post);
                    } elseif (($content = $migrator->getConfiguration($this->post)) != null) {
                        // Request configuration options
                        $vars['continue'] = false;
                        $vars['content'] = $content;
                    } else {
                        // Process migration
                        $migrate = true;
                    }

                    break;
                case 'configuration':
                    $vars['continue'] = false;
                    $migrator->processSettings($this->post);
                    $migrator->processConfiguration($this->post);

                    if (($errors = $migrator->errors())) {
                        $vars['message'] = $this->setMessage('error', $errors, true, null, false);
                        $vars['content'] = $migrator->getConfiguration($this->post);
                    } else {
                        // Process migration
                        $migrate = true;
                    }

                    break;
            }

            if ($migrate) {
                $this->ImportManagerImporter->runMigrator($type, $version, $this->post);

                if (($errors = $this->ImportManagerImporter->errors())) {
                    $vars['message'] = $this->setMessage('error', $errors, true, null, false);
                } else {
                    $this->parent->flashMessage(
                        'message',
                        Language::_('ImportManagerManagePlugin.!success.imported', true),
                        null,
                        false
                    );
                    $this->redirect($this->base_uri . 'settings/company/plugins/manage/' . $this->plugin_id . '/');
                }
            }
        }

        $vars['type'] = $type;
        $vars['info'] = $this->ImportManagerImporter->getMigrator($type);
        $vars['version'] = $version;
        $vars['plugin_id'] = $this->plugin_id;

        // Set the view to render
        return $this->partial('admin_manage_plugin_import', $vars);

        /*
        $this->init();

        $this->components(array("ImportManager.Migrators"));

        $type = isset($this->get[1]) ? $this->get[1] : null;
        $version = isset($this->get[2]) ? $this->get[2] : null;
        $migrator = $this->Migrators->create($type, $version);

        if (!$migrator)
            $this->redirect($this->base_uri . "settings/company/plugins/manage/" . $this->plugin_id . "/");

        $vars = array();

        if (!empty($this->post)) {
            $this->ImportManagerImporter->runMigrator($type, $version, $this->post);

            if (($errors = $this->ImportManagerImporter->errors())) {
                $vars['message'] = $this->setMessage("error", $errors, true, null, false);
            }
            else {
                $this->parent->flashMessage(
                    "message",
                    Language::_("ImportManagerManagePlugin.!success.imported", true),
                    null,
                    false
                );
                $this->redirect($this->base_uri . "settings/company/plugins/manage/" . $this->plugin_id . "/");
            }
        }

        $vars['type'] = $type;
        $vars['info'] = $this->ImportManagerImporter->getMigrator($type);
        $vars['version'] = $version;
        $vars['content'] = $migrator->getSettings($this->post);
        $vars['plugin_id'] = $this->plugin_id;

        // Set the view to render
        return $this->partial("admin_manage_plugin_import", $vars);
        */
    }
}
