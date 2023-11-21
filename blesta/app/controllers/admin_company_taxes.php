<?php

use Blesta\Core\Util\Input\Fields\Html;

/**
 * Admin Company Taxes Settings
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminCompanyTaxes extends AppController
{
    /**
     * Pre-action
     */
    public function preAction()
    {
        parent::preAction();

        // Require login
        $this->requireLogin();

        $this->uses(['Navigation', 'Taxes']);
        $this->components(['SettingsCollection']);

        Language::loadLang('admin_company_taxes');

        // Set the left nav for all settings pages to settings_leftnav
        $this->set(
            'left_nav',
            $this->partial('settings_leftnav', ['nav' => $this->Navigation->getCompany($this->base_uri)])
        );
    }

    /**
     * Taxes page
     */
    public function index()
    {
        $this->redirect($this->base_uri . 'settings/company/taxes/basic/');
    }

    /**
     * Basic Tax settings
     */
    public function basic()
    {
        $this->uses(['Companies', 'Countries', 'TaxProviders']);
        $this->helpers(['Form']);

        // Get all tax providers
        $tax_providers = $this->TaxProviders->getAll(false);

        // Update Basic tax settings
        if (!empty($this->post)) {
            // Set checkbox settings if not given
            $checkboxes = [
                'enable_tax', 'cascade_tax', 'setup_fee_tax', 'cancelation_fee_tax', 'enable_eu_vat',
                'tax_exempt_eu_vat', 'enable_uk_vat', 'tax_exempt_uk_vat', 'tax_intra_eu_uk_vat'
            ];

            $tax_provider_checkboxes = [];
            foreach ($tax_providers as $tax_provider) {
                if (method_exists($tax_provider, 'getFields')) {
                    $tax_fields = $tax_provider->getFields();

                    foreach ($tax_fields->getFields() as $field) {
                        if ($field->type == 'fieldCheckbox') {
                            $tax_provider_checkboxes[] = $field->params['name'];
                        }

                        if (!empty($field->fields)) {
                            foreach ($field->fields as $sub_field) {
                                if ($sub_field->type == 'fieldCheckbox') {
                                    $tax_provider_checkboxes[] = $sub_field->params['name'];
                                }
                            }
                        }
                    }
                }
            }
            $checkboxes = array_merge($checkboxes, $tax_provider_checkboxes);

            foreach ($checkboxes as $checkbox) {
                if (empty($this->post[$checkbox])) {
                    $this->post[$checkbox] = 'false';
                }
            }

            // Update Company settings
            $fields = [
                'enable_tax', 'cascade_tax', 'setup_fee_tax',
                'cancelation_fee_tax', 'tax_id'
            ];

            $tax_provider_fields = [];
            foreach ($tax_providers as $tax_provider) {
                if (method_exists($tax_provider, 'getFields')) {
                    $tax_fields = $tax_provider->getFields();

                    foreach ($tax_fields->getFields() as $field) {
                        if (!empty($field->params['name'])) {
                            $tax_provider_fields[] = $field->params['name'];
                        }

                        if (!empty($field->fields)) {
                            foreach ($field->fields as $sub_field) {
                                if (!empty($sub_field->params['name'])) {
                                    $tax_provider_fields[] = $sub_field->params['name'];
                                }
                            }
                        }
                    }
                }
            }
            $fields = array_merge($fields, $tax_provider_fields);
            $this->Companies->setSettings($this->company_id, $this->post, $fields);

            // Display success message
            $this->flashMessage('message', Language::_('AdminCompanyTaxes.!success.basic_updated', true));
            $this->redirect($this->base_uri . 'settings/company/taxes/basic/');
        }

        // Get company settings
        $company_settings = $this->SettingsCollection->fetchSettings($this->Companies, $this->company_id);

        // Get tax provider fields
        $tax_fields = [];
        foreach ($tax_providers as $tax_provider) {
            if (method_exists($tax_provider, 'getFields')) {
                $tax_fields[] = [
                    'name' => $tax_provider->getName(),
                    'fields' => (new Html($tax_provider->getFields($company_settings)))->generate()
                ];
            }
        }

        $this->set('tax_fields', $tax_fields);

        // Set all Company settings
        $this->set('vars', $company_settings);
    }

    /**
     * List Tax Rules
     */
    public function rules()
    {
        // Set tax rules
        $this->set('rules', $this->Taxes->getAll($this->company_id));
        $this->set('tax_types', $this->Taxes->getTaxTypes());
    }

    /**
     * Add Tax Rule
     */
    public function add()
    {
        $this->uses(['Countries', 'States']);

        $vars = new stdClass();

        // Create a tax rule
        if (!empty($this->post)) {
            // Set the company ID this rule applies to
            $this->post['company_id'] = $this->company_id;

            // Remove state/country if they are set to apply to All
            if (empty($this->post['country'])) {
                unset($this->post['country']);
            }
            if (empty($this->post['state'])) {
                unset($this->post['state']);
            }

            // Create tax rule
            $this->Taxes->add($this->post);

            // Handle errors
            if (($errors = $this->Taxes->errors())) {
                // Error, reset vars
                $vars = (object) $this->post;
                $this->setMessage('error', $errors);
            } else {
                // Success, display message
                $this->flashMessage('message', Language::_('AdminCompanyTaxes.!success.taxrule_created', true));
                $this->redirect($this->base_uri . 'settings/company/taxes/rules/');
            }
        }

        if (!isset($vars->country)) {
            $vars->country = '';
        }

        // Set tax drop down fields
        $this->set('tax_types', $this->Taxes->getTaxTypes());
        $this->set('tax_levels', $this->Taxes->getTaxLevels());
        // Prepend "all" option to country listing
        $this->set(
            'countries',
            array_merge(
                ['' => Language::_('AdminCompanyTaxes.countries.all', true)],
                $this->Form->collapseObjectArray($this->Countries->getList(), ['name', 'alt_name'], 'alpha2', ' - ')
            )
        );
        // Prepend "all" option to state listing
        $this->set(
            'states',
            array_merge(
                ['' => Language::_('AdminCompanyTaxes.states.all', true)],
                $this->Form->collapseObjectArray($this->States->getList($vars->country), 'name', 'code')
            )
        );
        $this->set('vars', $vars);
    }

    /**
     * Edit Tax Rule
     */
    public function edit()
    {
        $this->uses(['Countries', 'States']);

        // Redirect if invalid tax ID given
        if (empty($this->get[0])
            || !($tax_rule = $this->Taxes->get((int) $this->get[0]))
            || ($tax_rule->company_id !== $this->company_id)
            || ($tax_rule->status != 'active')
        ) {
            $this->redirect($this->base_uri . 'settings/company/taxes/rules/');
        }

        $vars = [];

        // Edit a tax rule
        if (!empty($this->post)) {
            // Set the company ID this rule applies to
            $this->post['company_id'] = $this->company_id;

            // Remove state/country if they are set to apply to All
            if (empty($this->post['country'])) {
                unset($this->post['country']);
            }
            if (empty($this->post['state'])) {
                unset($this->post['state']);
            }

            // Edit tax rule
            $this->Taxes->edit($tax_rule->id, $this->post);

            // Handle errors
            if (($errors = $this->Taxes->errors())) {
                // Error, reset vars
                $vars = (object) $this->post;
                $this->setMessage('error', $errors);
            } else {
                // Success, display message
                $this->flashMessage('message', Language::_('AdminCompanyTaxes.!success.taxrule_updated', true));
                $this->redirect($this->base_uri . 'settings/company/taxes/rules/');
            }
        }

        if (empty($vars)) {
            $vars = $tax_rule;
        }

        if (!isset($vars->country)) {
            $vars->country = '';
        }

        // Set tax drop down fields
        $this->set('tax_types', $this->Taxes->getTaxTypes());
        //$this->set("tax_levels", $this->Taxes->getTaxLevels());
        $this->set('tax_status', $this->Taxes->getTaxStatus());
        // Prepend "all" option to country listing
        $this->set(
            'countries',
            array_merge(
                ['' => Language::_('AdminCompanyTaxes.countries.all', true)],
                $this->Form->collapseObjectArray($this->Countries->getList(), ['name', 'alt_name'], 'alpha2', ' - ')
            )
        );
        // Prepend "all" option to state listing
        $this->set(
            'states',
            array_merge(
                ['' => Language::_('AdminCompanyTaxes.states.all', true)],
                $this->Form->collapseObjectArray($this->States->getList($vars->country), 'name', 'code')
            )
        );
        $this->set('vars', $vars);
    }

    /**
     * Deletes a tax rule
     */
    public function delete()
    {
        // Redirect if invalid currency code given
        if (!isset($this->post['id'])
            || !($tax_rule = $this->Taxes->get($this->post['id']))
            || $tax_rule->company_id != $this->company_id
        ) {
            $this->redirect($this->base_uri . 'settings/company/taxes/rules/');
        }

        // Attempt to delete the tax rule
        $this->Taxes->delete($tax_rule->id);

        $this->flashMessage('message', Language::_('AdminCompanyTaxes.!success.rule_deleted', true));
        $this->redirect($this->base_uri . 'settings/company/taxes/rules/');
    }

    /**
     * Fetch all states belonging to a given country (json encoded ajax request)
     */
    public function getStates()
    {
        $this->uses(['States']);
        // Prepend "all" option to state listing
        $states = ['' => Language::_('AdminCompanyTaxes.states.all', true)];
        if (isset($this->get[0])) {
            $states = array_merge(
                $states,
                (array) $this->Form->collapseObjectArray($this->States->getList($this->get[0]), 'name', 'code')
            );
        }

        echo json_encode($states);
        return false;
    }
}
