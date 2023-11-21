<?php
/**
 * Extension Generator admin nonmerchant controller
 *
 * @package blesta
 * @subpackage blesta.plugins.extension_generator
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminNonmerchant extends ExtensionGeneratorController
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
        $this->structure->set('page_title', Language::_('AdminNonmerchant.index.page_title', true, $extension->name));
    }

    /**
     * Returns the view to be rendered when configuring the basic settings for a nonmerchant gateway
     */
    public function basic()
    {
        // Attempt to upload logo if submitted
        $errors = $this->uploadLogo();

        if (isset($this->post['currencies'])) {
            $this->post['currencies'] = ['code' => explode(',', $this->post['currencies'])];
        }

        if (!$errors) {
            // Perform edit and redirect or set errors and repopulate vars
            $vars = $this->processStep('nonmerchant/basic', $this->extension);
        } else {
            $vars = $this->post;

            $this->setMessage('error', $errors, false, null, false);
        }

        if (isset($vars['currencies']['code'])) {
            $vars['currencies'] = implode(',', $vars['currencies']['code']);
        }

        // Set the view to render for all actions under this controller
        $this->set('vars', $vars);

        // Set the node progress bar
        $this->set('form_type', $this->extension->form_type);
        $nodes = $this->getNodes($this->extension);
        $page_step = array_search('nonmerchant/basic', array_keys($nodes));
        $this->set(
            'progress_bar',
            $this->partial(
                'partial_progress_bar',
                ['nodes' => $nodes, 'page_step' => $page_step, 'extension' => $this->extension]
            )
        );
    }

    /**
     * Returns the view to be rendered when configuring the fields for a nonmerchant gateway
     */
    public function fields()
    {
        // Set empty array inputs
        if (!empty($this->post)) {
            $array_fields = ['fields'];
            foreach ($array_fields as $array_field) {
                if (!isset($this->post[$array_field])) {
                    // Set empty array inputs
                    $this->post[$array_field] = [];
                }
            }
        }

        // Perform edit and redirect or set errors and repopulate vars
        $vars = $this->processStep('nonmerchant/fields', $this->extension);

        // Set the view to render for all actions under this controller
        $this->set('field_types', $this->getFieldTypes());
        $this->set('vars', $vars);

        // Set the node progress bar
        $nodes = $this->getNodes($this->extension);
        $page_step = array_search('nonmerchant/fields', array_keys($nodes));
        $this->set(
            'progress_bar',
            $this->partial(
                'partial_progress_bar',
                ['nodes' => $nodes, 'page_step' => $page_step, 'extension' => $this->extension]
            )
        );
    }

    /**
     * Returns the view to be rendered when configuring the additional features for a nonmerchant gateway
     */
    public function features()
    {
        // Perform edit and redirect or set errors and repopulate vars
        $vars = $this->processStep('nonmerchant/features', $this->extension);

        // Set the view to render for all actions under this controller
        $this->set('optional_functions', $this->getOptionalFunctions());
        $this->set('vars', $vars);

        // Set the node progress bar
        $nodes = $this->getNodes($this->extension);
        $page_step = array_search('nonmerchant/features', array_keys($nodes));
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
     * @return A list of optional functions and their settings
     */
    protected function getOptionalFunctions()
    {
        $functions = [
            'refund' => ['enabled' => 'true'],
            'void' => ['enabled' => 'true'],
            'buildProcess' => ['enabled' => 'true'],
            'upgrade' => ['enabled' => 'false'],
        ];

        foreach ($functions as $function => &$settings) {
            $settings['tooltip'] = Language::_(
                'AdminNonmerchant.getoptionalfunctions.tooltip_' . $function,
                true
            );
        }

        return $functions;
    }
}
