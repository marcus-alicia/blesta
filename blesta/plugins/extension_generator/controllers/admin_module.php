<?php
/**
 * Extension Generator admin module controller
 *
 * @package blesta
 * @subpackage blesta.plugins.extension_generator
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminModule extends ExtensionGeneratorController
{
    /**
     * Setup
     */
    public function preAction()
    {
        parent::preAction();

        // Ensure extension exists
        if (!isset($this->get[0])
            || !($extension = $this->ExtensionGeneratorExtensions->get($this->get[0]))
            || $extension->company_id != $this->company_id
        ) {
            $this->redirect($this->base_uri . 'plugin/extension_generator/admin_main/');
        }

        $this->extension = $extension;
        $this->structure->set('page_title', Language::_('AdminModule.index.page_title', true, $extension->name));
    }

    /**
     * Returns the view to be rendered when configuring the basic settings for a module
     */
    public function basic()
    {
        // Attempt to upload logo if submitted
        $errors = $this->uploadLogo();

        if (!$errors) {
            // Set required parameters
            if (!empty($this->post) && $this->post['module_type'] ?? 'generic' == 'registrar') {
                if (!isset($this->extension->data['package_fields']['name'][0])) {
                    $this->post['package_fields'] = [
                        'name' => [0 => 'epp_code'],
                        'label' => [0 => Language::_('AdminModule.fields.package_fields_epp_code_label', true)],
                        'type' => [0 => 'Checkbox'],
                        'tooltip' => [0 => Language::_('AdminModule.fields.package_fields_epp_code_tooltip', true)],
                        'name_key' => [0 => 'true']
                    ];
                }
                if (!isset($this->extension->data['service_fields']['name'][0])) {
                    $this->post['service_fields'] = [
                        'name' => [0 => 'domain'],
                        'label' => [0 => Language::_('AdminModule.fields.service_fields_domain_label', true)],
                        'type' => [0 => 'Text'],
                        'name_key' => [0 => 'true']
                    ];
                }
            }

            // Perform edit and redirect or set errors and repopulate vars
            $vars = $this->processStep('module/basic', $this->extension);
        } else {
            $vars = $this->post;

            $this->setMessage('error', $errors, false, null, false);
        }

        // Set the view to render for all actions under this controller
        $this->set('module_types', $this->getModuleTypes());
        $this->set('form_type', $this->extension->form_type);
        $this->set('vars', $vars);

        // Set the node progress bar
        $nodes = $this->getNodes($this->extension);
        $page_step = array_search('module/basic', array_keys($nodes));
        $this->set(
            'progress_bar',
            $this->partial(
                'partial_progress_bar',
                ['nodes' => $nodes, 'page_step' => $page_step, 'extension' => $this->extension]
            )
        );
    }

    /**
     * Returns the view to be rendered when configuring the module fields for a module
     */
    public function fields()
    {
        // Set empty array inputs
        if (!empty($this->post)) {
            $array_fields = ['module_rows', 'package_fields', 'service_fields'];
            foreach ($array_fields as $array_field) {
                if (!isset($this->post[$array_field])) {
                    // Set empty array inputs
                    $this->post[$array_field] = [];
                }
            }
        }

        // Perform edit and redirect or set errors and repopulate vars
        $vars = $this->processStep('module/fields', $this->extension);

        // Set the view to render for all actions under this controller
        $this->set('field_types', $this->getFieldTypes());
        $this->set('module_type', $vars['module_type'] ?? 'generic');
        $this->set('vars', $vars);

        // Set the node progress bar
        $nodes = $this->getNodes($this->extension);
        $page_step = array_search('module/fields', array_keys($nodes));
        $this->set(
            'progress_bar',
            $this->partial(
                'partial_progress_bar',
                ['nodes' => $nodes, 'page_step' => $page_step, 'extension' => $this->extension]
            )
        );
    }

    /**
     * Returns the view to be rendered when configuring the additional features for a module
     */
    public function features()
    {
        // Set empty array inputs
        if (!empty($this->post)) {
            if (!isset($this->post['service_tabs'])) {
                $this->post['service_tabs'] = [];
            }

            if (!isset($this->post['cron_tasks'])) {
                $this->post['cron_tasks'] = [];
            }
        }

        // Perform edit and redirect or set errors and repopulate vars
        $vars = $this->processStep('module/features', $this->extension);

        // Set the view to render for all actions under this controller
        $this->set('tab_levels', $this->getTabLevels());
        $this->set('task_types', $this->getTaskTypes());
        $this->set('module_type', $vars['module_type'] ?? 'generic');
        $this->set('optional_functions', $this->getOptionalFunctions($vars['module_type'] ?? 'generic'));
        $this->set('vars', $vars);

        // Set the node progress bar
        $nodes = $this->getNodes($this->extension);
        $page_step = array_search('module/features', array_keys($nodes));
        $this->set(
            'progress_bar',
            $this->partial(
                'partial_progress_bar',
                ['nodes' => $nodes, 'page_step' => $page_step, 'extension' => $this->extension]
            )
        );
    }

    /**
     * Gets a list of optional functions and their settings
     *
     * @param string $module_type The type of the module to fetch the optional functions
     * @return array A list of optional functions and their settings
     */
    protected function getOptionalFunctions(string $module_type = 'generic') : array
    {
        if (!in_array($module_type, ['generic', 'registrar'])) {
            return [];
        }

        $functions = [
            'upgrade' => ['enabled' => 'true'],
            'cancelService' => ['enabled' => 'true'],
            'suspendService' => ['enabled' => 'true'],
            'unsuspendService' => ['enabled' => 'true'],
            'renewService' => ['enabled' => 'true'],
            'addPackage' => ['enabled' => 'true'],
            'editPackage' => ['enabled' => 'true'],
            'deletePackage' => ['enabled' => 'true'],
            'deleteModuleRow' => ['enabled' => 'false'],
            'getGroupOrderOptions' => ['enabled' => 'false'],
            'selectModuleRow' => ['enabled' => 'false'],
            'getAdminServiceInfo' => ['enabled' => 'false'],
            'getClientServiceInfo' => ['enabled' => 'false']
        ];

        $registrar_functions = [
            'checkAvailability' => ['enabled' => 'true'],
            'checkTransferAvailability' => ['enabled' => 'true'],
            'getDomainInfo' => ['enabled' => 'true'],
            'getExpirationDate' => ['enabled' => 'true'],
            'getServiceDomain' => ['enabled' => 'true'],
            'getTldPricing' => ['enabled' => 'true'],
            'registerDomain' => ['enabled' => 'true'],
            'renewDomain' => ['enabled' => 'true'],
            'transferDomain' => ['enabled' => 'true'],
            'getDomainContacts' => ['enabled' => 'false'],
            'getDomainIsLocked' => ['enabled' => 'false'],
            'getDomainNameServers' => ['enabled' => 'false'],
            'lockDomain' => ['enabled' => 'false'],
            'resendTransferEmail' => ['enabled' => 'false'],
            'restoreDomain' => ['enabled' => 'false'],
            'sendEppEmail' => ['enabled' => 'false'],
            'setDomainContacts' => ['enabled' => 'false'],
            'setDomainNameservers' => ['enabled' => 'false'],
            'setNameserverIps' => ['enabled' => 'false'],
            'unlockDomain' => ['enabled' => 'false'],
            'updateEppCode' => ['enabled' => 'false']
        ];

        if ($module_type == 'registrar') {
            $functions = array_merge($functions, $registrar_functions);
        }

        foreach ($functions as $function => &$settings) {
            $settings['tooltip'] = Language::_(
                'AdminModule.getoptionalfunctions.tooltip_' . $function,
                true
            );
        }

        return $functions;
    }

    /**
     * Gets a list of supported module types
     *
     * @return array A list of the supported module types and their language definition
     */
    private function getModuleTypes() : array
    {
        return [
            'generic' => Language::_('AdminModule.basic.module_type_generic', true),
            'registrar' => Language::_('AdminModule.basic.module_type_registrar', true)
        ];
    }
}
