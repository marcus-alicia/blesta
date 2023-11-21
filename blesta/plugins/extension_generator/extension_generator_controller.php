<?php
require_once PLUGINDIR . 'extension_generator' . DS . 'lib' . DS . 'form_rules.php';

/**
 * Extension Generator parent controller
 *
 * @package blesta
 * @subpackage blesta.plugins.extension_generator
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ExtensionGeneratorController extends AppController
{
    /**
     * Require admin to be login and setup the view
     */
    public function preAction()
    {
        $this->structure->setDefaultView(APPDIR);
        parent::preAction();

        $this->requireLogin();

        // Auto load language for the controller
        Language::loadLang(
            [Loader::fromCamelCase(get_class($this))],
            null,
            dirname(__FILE__) . DS . 'language' . DS
        );
        Language::loadLang(
            'extension_generator_controller',
            null,
            dirname(__FILE__) . DS . 'language' . DS
        );

        // Override default view directory
        $this->view->view = "default";
        $this->orig_structure_view = $this->structure->view;
        $this->structure->view = "default";

        // Restore structure view location of the admin portal
        $this->structure->setDefaultView(APPDIR);
        $this->structure->setView(null, $this->orig_structure_view);

        $this->uses(['ExtensionGenerator.ExtensionGeneratorExtensions']);
    }

    /**
     * Attempt to upload a submitted logo file if any exists
     *
     * @return array|null A list of errors encountered, if any
     */
    protected function uploadLogo()
    {
        if (!isset($this->SettingsCollection)) {
            Loader::loadComponents($this, ['SettingsCollection']);
        }
        if (!isset($this->Upload)) {
            Loader::loadComponents($this, ['Upload']);
        }

        // Handle logo upload
        $errors = null;
        if (!empty($this->files['logo'])) {
            // Set the uploads directory
            $temp = $this->SettingsCollection->fetchSetting(
                null,
                Configure::get('Blesta.company_id'),
                'uploads_dir'
            );
            $upload_path = $temp['value'] . Configure::get('Blesta.company_id') . DS . 'extension_generator' . DS;

            $this->Upload->setFiles($this->files);
            $this->Upload->setUploadPath($upload_path);
            $file_name = $this->extension->id . '_' . $this->files['logo']['name'];

            if (!($errors = $this->Upload->errors())) {
                @unlink($upload_path . $file_name);

                $this->Upload->writeFile('logo', false, $file_name);
                $data = $this->Upload->getUploadData();

                // Set the file name of the file that was uploaded
                if (isset($data['logo']['full_path'])) {
                    $this->post['logo_path'] = $data['logo']['full_path'];
                }

                $errors = $this->Upload->errors();
            }

            // Error, could not upload the file
            if ($errors) {
                // Attempt to remove the file if it was somehow written
                @unlink($upload_path . $file_name);
            } elseif (isset($this->extension->data['logo_path']) && isset($this->post['logo_path'])
                && $this->extension->data['logo_path'] != $this->post['logo_path']
            ) {
                // Remove the old logo file
                @unlink($this->extension->data['logo_path']);
            }
        }

        return $errors;
    }

    /**
     * Processes a given configuration step for the given extension
     *
     * @param string $step The step to process
     * @param stdClass $extension The extension for which to process this step
     * @return array A list vars to be sent to the view
     */
    protected function processStep($step, $extension)
    {
        $this->ArrayHelper = $this->DataStructure->create('Array');
        // Update the extension
        if (!empty($this->post))
        {
            $temp_vars = $this->post;

            // Convert array input to a more usable form before storing
            foreach ($temp_vars as $key => $value) {
                if (is_array($value)
                    && !in_array($key, ['optional_functions', 'tables', 'controllers', 'supported_features'])
                ) {
                    $temp_vars[$key] = $this->ArrayHelper->keyToNumeric($value);
                }
            }

            // If this step contains optional functions, set unchecked options
            $optional_function_steps = ['module/features', 'plugin/features'];
            if (in_array($step, $optional_function_steps)) {
                if (!isset($temp_vars['optional_functions'])) {
                    $temp_vars['optional_functions'] = [];
                }

                foreach ($this->getOptionalFunctions($extension->data['module_type'] ?? 'generic') as $optional_function => $settings) {
                    if (!isset($temp_vars['optional_functions'][$optional_function])) {
                        $temp_vars['optional_functions'][$optional_function] = 'false';
                    }
                }
            }

            $form_rules = new FormRules();

            if ($form_rules->validate(str_replace('/', '', $step), $temp_vars)) {
                // Update the extension with the new data
                $vars = ['data' => array_merge($extension->data, $temp_vars)];
                $this->ExtensionGeneratorExtensions->edit($extension->id, $vars);

                $errors = $this->ExtensionGeneratorExtensions->errors();
            } else {
                $errors = $form_rules->errors();
            }

            if ($errors) {
                $this->setMessage('error', $errors, false, null, false);

                $vars = $this->post;
            } else {
                $next_step = $this->getNextStep($step, $extension->form_type, $extension->type);

                // Redirect to the next step in the configuration process
                $this->redirect(
                    $this->base_uri
                    . 'plugin/extension_generator/admin_' . $next_step . '/' . $extension->id
                );
            }
        } else {
            // Set vars stored by the extension record
            $vars = $extension->data;

            // Convert array input to a more usable form before displaying
            foreach ($vars as $key => $value) {
                if (is_array($value)
                    && !in_array($key, ['optional_functions', 'tables', 'controllers', 'supported_features'])
                ) {
                    $vars[$key] = $this->ArrayHelper->numericToKey($value);
                }
            }
        }

        return $vars;
    }

    /**
     * Gets a list of progress nodes for the given extension
     *
     * @param stdClass $extension The extension for which to get progress nodes
     * @return array A list of progress nodes, keyed by the step to which they should link
     */
    protected function getNodes($extension = null)
    {
        // Use a simplified set of steps if set to use the basic extension form
        $node_sets = [];
        if (isset($extension) && $extension->form_type == 'basic') {
            $node_sets = [
                'module' => [
                    'module/basic' => Language::_('ExtensionGeneratorController.getnodes.basic_info', true),
                ],
                'plugin' => [
                    'plugin/basic' => Language::_('ExtensionGeneratorController.getnodes.basic_info', true),
                ],
                'merchant' => [
                    'merchant/basic' => Language::_('ExtensionGeneratorController.getnodes.basic_info', true),
                    'merchant/features' => Language::_('ExtensionGeneratorController.getnodes.merchant_features', true),
                ],
                'nonmerchant' => [
                    'nonmerchant/basic' => Language::_('ExtensionGeneratorController.getnodes.basic_info', true),
                ],
            ];
        } else {
            $node_sets = [
                'module' => [
                    'module/basic' => Language::_('ExtensionGeneratorController.getnodes.basic_info', true),
                    'module/fields' => Language::_('ExtensionGeneratorController.getnodes.module_fields', true),
                    'module/features' => Language::_('ExtensionGeneratorController.getnodes.additional_features', true),
                ],
                'plugin' => [
                    'plugin/basic' => Language::_('ExtensionGeneratorController.getnodes.basic_info', true),
                    'plugin/database' => Language::_('ExtensionGeneratorController.getnodes.plugin_database', true),
                    'plugin/integrations' => Language::_(
                        'ExtensionGeneratorController.getnodes.plugin_integrations', true
                    ),
                    'plugin/features' => Language::_('ExtensionGeneratorController.getnodes.additional_features', true),
                ],
                'merchant' => [
                    'merchant/basic' => Language::_('ExtensionGeneratorController.getnodes.basic_info', true),
                    'merchant/fields' => Language::_('ExtensionGeneratorController.getnodes.merchant_fields', true),
                    'merchant/features' => Language::_('ExtensionGeneratorController.getnodes.merchant_features', true),
                ],
                'nonmerchant' => [
                    'nonmerchant/basic' => Language::_(
                        'ExtensionGeneratorController.getnodes.basic_info',
                        true
                    ),
                    'nonmerchant/fields' => Language::_(
                        'ExtensionGeneratorController.getnodes.nonmerchant_fields',
                        true
                    ),
                    'nonmerchant/features' => Language::_(
                        'ExtensionGeneratorController.getnodes.additional_features',
                        true
                    ),
                ],
            ];
        }

        $nodes = ['main/general' => Language::_('ExtensionGeneratorController.getnodes.general_settings', true)];

        // Add the nodes for the given extension type
        if (isset($extension)) {
            foreach ($node_sets as $type => $node_set) {
                if ($extension->type == $type) {
                    $nodes += $node_set;
                    break;
                }
            }
        } else {
            $nodes['module/basic'] = Language::_('ExtensionGeneratorController.getnodes.basic_info', true);
        }

        $nodes['main/confirm'] = Language::_('ExtensionGeneratorController.getnodes.confirm', true);

        return $nodes;
    }

    /**
     * Returns the next step in the chain of extension forms
     *
     * @param string $current_step The current step being displayed
     * @return string The next step to be displayed
     */
    private function getNextStep($current_step, $form_type = 'advanced', $extension_type = 'module')
    {
        $step_mapping = [
            'main/general' => $extension_type . '/basic',
            'module/basic' => 'module/fields',
            'module/fields' => 'module/features',
            'module/features' => 'main/confirm',
            'plugin/basic' => 'plugin/database',
            'plugin/database' => 'plugin/integrations',
            'plugin/integrations' => 'plugin/features',
            'plugin/features' => 'main/confirm',
            'merchant/basic' => 'merchant/fields',
            'merchant/fields' => 'merchant/features',
            'merchant/features' => 'main/confirm',
            'nonmerchant/basic' => 'nonmerchant/fields',
            'nonmerchant/fields' => 'nonmerchant/features',
            'nonmerchant/features' => 'main/confirm',
            'main/confirm' => 'main/general',
        ];

        // Use a simplified set of steps if set to use the basic extension form
        if ($form_type == 'basic') {
            $step_mapping = [
                'main/general' => $extension_type . '/basic',
                'module/basic' => 'main/confirm',
                'plugin/basic' => 'main/confirm',
                'merchant/basic' => 'merchant/features',
                'merchant/features' => 'main/confirm',
                'nonmerchant/basic' => 'main/confirm',
                'main/confirm' => 'main/general',
            ];
        }

        return isset($step_mapping[$current_step]) ? $step_mapping[$current_step] : 'main/general';
    }

    /**
     * Gets a list of field types and their language
     *
     * @return A list of field types and their language
     */
    protected function getFieldTypes()
    {
        return [
            'Text' => Language::_('ExtensionGeneratorController.getfieldtypes.text', true),
            'Textarea' => Language::_('ExtensionGeneratorController.getfieldtypes.textarea', true),
            'Checkbox' => Language::_('ExtensionGeneratorController.getfieldtypes.checkbox', true)
        ];
    }

    /**
     * Gets a list of cron task types and their language
     *
     * @return A list of cron task types and their language
     */
    protected function getTaskTypes()
    {
        return [
            'time' => Language::_('ExtensionGeneratorController.gettasktypes.time', true),
            'interval' => Language::_('ExtensionGeneratorController.gettasktypes.interval', true)
        ];
    }

    /**
     * Gets a list of tab levels and their language
     *
     * @return A list of tab levels and their language
     */
    protected function getTabLevels()
    {
        return [
            'staff' => Language::_('ExtensionGeneratorController.gettablevels.staff', true),
            'client' => Language::_('ExtensionGeneratorController.gettablevels.client', true)
        ];
    }
}
