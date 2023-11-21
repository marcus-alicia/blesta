<?php
/**
 * Extension Generator admin merchant controller
 *
 * @package blesta
 * @subpackage blesta.plugins.extension_generator
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminMerchant extends ExtensionGeneratorController
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
        $this->structure->set('page_title', Language::_('AdminMerchant.index.page_title', true, $extension->name));
    }

    /**
     * Returns the view to be rendered when configuring the basic settings for a merchant gateway
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
            $vars = $this->processStep('merchant/basic', $this->extension);
        } else {
            $vars = $this->post;

            $this->setMessage('error', $errors, false, null, false);
        }

        if (isset($vars['currencies']['code'])) {
            $vars['currencies'] = implode(',', $vars['currencies']['code']);
        }

        // Set the view to render for all actions under this controller
        $this->set('form_type', $this->extension->form_type);
        $this->set('vars', $vars);

        // Set the node progress bar
        $nodes = $this->getNodes($this->extension);
        $page_step = array_search('merchant/basic', array_keys($nodes));
        $this->set(
            'progress_bar',
            $this->partial(
                'partial_progress_bar',
                ['nodes' => $nodes, 'page_step' => $page_step, 'extension' => $this->extension]
            )
        );
    }

    /**
     * Returns the view to be rendered when configuring the fields for a merchant gateway
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
        $vars = $this->processStep('merchant/fields', $this->extension);

        // Set the view to render for all actions under this controller
        $this->set('field_types', $this->getFieldTypes());
        $this->set('vars', $vars);

        // Set the node progress bar
        $nodes = $this->getNodes($this->extension);
        $page_step = array_search('merchant/fields', array_keys($nodes));
        $this->set(
            'progress_bar',
            $this->partial(
                'partial_progress_bar',
                ['nodes' => $nodes, 'page_step' => $page_step, 'extension' => $this->extension]
            )
        );
    }

    /**
     * Returns the view to be rendered when configuring the supported features for a merchant gateway
     */
    public function features()
    {
        if (!empty($this->post)) {
            // Set unchecked options
            if (!isset($this->post['supported_features'])) {
                $this->post['supported_features'] = [];
            }

            foreach ($this->getSupportedFeatures() as $supported_feature => $settings) {
                if (!isset($this->post['supported_features'][$supported_feature])) {
                    $this->post['supported_features'][$supported_feature] = 'false';
                }
            }

            $optional_function_mapping = [
                'cc' => ['proccessCc', 'authorizeCc', 'captureCc', 'refundCc', 'voidCc'],
                'cc_offsite' => ['proccessStoredCc', 'authorizeStoredCc', 'captureStoredCc',
                    'voidStoredCc', 'refundStoredCc', 'storeCc', 'updateCc', 'removeCc'
                ],
                'cc_form' => ['buildCcForm', 'buildPaymentConfirmation'],
                'ach' => ['proccessAch', 'refundAch', 'voidAch'],
                'ach_offsite' => ['proccessStoredAch', 'refundStoredAch',
                    'voidStoredAch', 'storeAch', 'updateAch', 'removeAch'
                ],
                'ach_form' => ['buildAchForm']
            ];

            // Set optional functions based on the selected features
            foreach ($optional_function_mapping as $supported_feature => $optional_functions) {
                foreach ($optional_functions as $optional_function) {
                    $temp = $this->post['supported_features'][$supported_feature];
                    $this->post['optional_functions'][$optional_function] = $temp;
                }
            }

            // Set required cc storage based on if only offsite is enabled
            if ($this->post['supported_features']['cc'] == 'false'
                && $this->post['supported_features']['cc_offsite'] == 'true'
            ) {
                $this->post['require_cc_storage'] = 'true';
            } else {
                $this->post['require_cc_storage'] = 'false';
            }

            // Set required ach storage based on if only offsite is enabled
            if ($this->post['supported_features']['ach'] == 'false'
                && $this->post['supported_features']['ach_offsite'] == 'true'
            ) {
                $this->post['require_ach_storage'] = 'true';
            } else {
                $this->post['require_ach_storage'] = 'false';
            }
        }

        // Perform edit and redirect or set errors and repopulate vars
        $vars = $this->processStep('merchant/features', $this->extension);

        // Set the view to render for all features under this controller
        $this->set('supported_features', $this->getSupportedFeatures());
        $this->set('vars', $vars);

        // Set the node progress bar
        $nodes = $this->getNodes($this->extension);
        $page_step = array_search('merchant/features', array_keys($nodes));
        $this->set(
            'progress_bar',
            $this->partial(
                'partial_progress_bar',
                ['nodes' => $nodes, 'page_step' => $page_step, 'extension' => $this->extension]
            )
        );
    }

    /**
     * Gets a list of supported features and their settings
     *
     * @return A list of supported features and their settings
     */
    protected function getSupportedFeatures()
    {
        $functions = [
            'cc' => ['enabled' => 'true'],
            'cc_offsite' => ['enabled' => 'false'],
            'cc_form' => ['enabled' => 'false'],
            'ach' => ['enabled' => 'false'],
            'ach_offsite' => ['enabled' => 'false'],
        ];

        foreach ($functions as $function => &$settings) {
            $settings['name'] = Language::_(
                'AdminMerchant.getsupportedfeatures.' . $function,
                true
            );
            $settings['tooltip'] = Language::_(
                'AdminMerchant.getsupportedfeatures.tooltip_' . $function,
                true
            );
        }

        return $functions;
    }
}
