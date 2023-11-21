<?php
/**
 * Upgrades to version 5.2.0-b1
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2021, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade5_2_0B1 extends UpgradeUtil
{
    /**
     * @var array An array of all tasks completed
     */
    private $tasks = [];

    /**
     * Setup
     */
    public function __construct()
    {
        Loader::loadComponents($this, ['Record']);
    }

    /**
     * Returns a numerically indexed array of tasks to execute for the upgrade process
     *
     * @return array A numerically indexed array of tasks to execute for the upgrade process
     */
    public function tasks()
    {
        return [
            'addPackageOptionOrderColumn',
            'removeAccountAndContactClientNav',
            'addClientsCanRenewSetting',
            'addInclusiveCalculatedTaxType',
            'migrateUkFromViesToHmrc',
            'addServicesClientNavigationItem',
            'addOptionConditionNotinOperator',
            'addOptionGroupsHideOptions',
            'convertPackageOptionConditionValueId',
            'removePostalMethodsReturnAddressCompanySettings',
            'addInvoiceLineTaxesSubtractColumn',
            'addPackageOptionConditionSetValues'
        ];
    }

    /**
     * Processes the given task
     *
     * @param string $task The task to process
     */
    public function process($task)
    {
        $tasks = $this->tasks();

        // Ensure task exists
        if (!in_array($task, $tasks)) {
            return;
        }

        $this->tasks[] = $task;
        $this->{$task}();
    }

    /**
     * Rolls back all tasks completed for the upgrade process
     */
    public function rollback()
    {
        // Undo all tasks
        while (($task = array_pop($this->tasks))) {
            $this->{$task}(true);
        }
    }

    /**
     * Add an `order` column to the package_option table
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addPackageOptionOrderColumn($undo = false)
    {
        $this->Record->query(
            "ALTER TABLE `package_option` ADD `order` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0';"
        );
    }

    /**
     * Removes the 'Contacts' and 'Payment Accounts' nav items from the client interface
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function removeAccountAndContactClientNav($undo = false)
    {
        $this->Record->from('navigation_items')->
            innerJoin('actions', 'actions.id', '=', 'navigation_items.action_id', false)->
            where('actions.url', '=', 'contacts/')->
            orWhere('actions.url', '=', 'accounts/')->
            delete(['navigation_items.*', 'actions.*']);
    }

    /**
     * Adds the new "clients_renew_services" company setting
     *
     * @param bool $undo Whether to undo the upgrade
     */
    private function addClientsCanRenewSetting($undo = false)
    {
        // Add the required contact field setting
        $setting = 'clients_renew_services';

        if ($undo) {
            // Remove the new setting
            $this->Record->from('client_group_settings')->where('key', '=', $setting)->delete();
            $this->Record->from('company_settings')->where('key', '=', $setting)->delete();
        } else {
            $value = 'true';

            // Add to company settings
            $companies = $this->Record->select()->from('companies')->getStatement();
            foreach ($companies as $company) {
                $this->Record->insert(
                    'company_settings',
                    ['key' => $setting, 'value' => $value, 'company_id' => $company->id]
                );
            }
        }
    }

    /**
     * Add an `inclusive_calculated` column to the package_option table
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addInclusiveCalculatedTaxType($undo = false)
    {
        $this->Record->query(
            "ALTER TABLE `taxes` CHANGE `type` `type` ENUM('inclusive_calculated', 'inclusive', 'exclusive') NOT NULL DEFAULT 'exclusive';"
        );
    }

    /**
     * Migrates the UK VIES VAT validation to the HMRC VAT validation
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function migrateUkFromViesToHmrc($undo = false)
    {
        Loader::loadModels($this, ['Companies']);
        Loader::loadHelpers($this, ['DataStructure']);

        // Load format helper for settings
        $this->ArrayHelper = $this->DataStructure->create('Array');

        // Get all companies
        $companies = $this->Companies->getAll();

        foreach ($companies as $company) {
            $company_settings = $this->Companies->getSettings($company->id);
            $company_settings = $this->ArrayHelper->numericToKey($company_settings, 'key', 'value');

            if ($company_settings['tax_home_eu_vat'] == 'GB') {
                // Enable UK VAT and Intra-EU mode if EU tax exemption is enabled
                $this->Companies->setSetting($company->id, 'tax_exempt_uk_vat', $company_settings['tax_exempt_eu_vat']);
                $this->Companies->setSetting($company->id, 'tax_intra_eu_uk_vat', $company_settings['tax_exempt_eu_vat']);
                $this->Companies->setSetting($company->id, 'enable_uk_vat', $company_settings['enable_eu_vat']);
            } else {
                $this->Companies->setSetting($company->id, 'tax_exempt_uk_vat', 'false');
                $this->Companies->setSetting($company->id, 'tax_intra_eu_uk_vat', 'false');
                $this->Companies->setSetting($company->id, 'enable_uk_vat', 'false');
            }
        }
    }

    /**
     * Adds a new "Services" action on the client navigation bar
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addServicesClientNavigationItem($undo = false)
    {
        Loader::loadModels($this, ['Companies', 'Actions']);

        // Fetch all companies
        $companies = $this->Companies->getAll();

        if ($undo) {
            foreach ($companies as $company) {
                // Delete the action
                $this->Record->from('actions')->
                    leftJoin('navigation_items', 'navigation_items.action_id', '=', 'actions.id', false)->
                    where('actions.url', '=', 'services/index/active/')->
                    where('actions.location', '=', 'nav_client')->
                    where('actions.company_id', '=', $company->id)->
                    delete(['actions.*', 'navigation_items.*']);
            }
        } else {
            foreach ($companies as $company) {
                $action_id = $this->Actions->add([
                    'location' => 'nav_client',
                    'url' => 'services/index/',
                    'name' => 'Navigation.getprimaryclient.nav_services',
                    'company_id' => $company->id,
                    'editable' => 0
                ]);

                // Reorder navigation items
                $navigation_items = $this->Record->select(['navigation_items.*', 'actions.location', 'actions.url'])->
                    from('navigation_items')->
                    innerJoin('actions', 'actions.id', '=', 'navigation_items.action_id', false)->
                    order(['navigation_items.order' => 'ASC'])->
                    fetchAll();

                $order = 0;
                foreach ($navigation_items as $item) {
                    $this->Record->where('navigation_items.id', '=', $item->id)->
                        update('navigation_items', ['order' => $order]);

                    $order++;

                    if ($item->location == 'nav_client' && $item->url == '') {
                        // Insert the navigation item
                        $navigation_vars = [
                            'action_id' => $action_id,
                            'order' => $order,
                            'parent_id' => null
                        ];
                        $this->Record->insert('navigation_items', $navigation_vars);
                        $item_id = $this->Record->lastInsertId();

                        $sub_navigation_vars = [
                            'action_id' => $action_id,
                            'order' => ($order + 1),
                            'parent_id' => $item_id
                        ];
                        $this->Record->insert('navigation_items', $sub_navigation_vars);

                        $order += 2;
                    }
                }
            }
        }
    }

    /**
     * Adds a new "notin" operator to the package_option_conditions
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addOptionConditionNotinOperator($undo = false)
    {
        if ($undo) {
            $this->Record->query(
                "ALTER TABLE `package_option_conditions` CHANGE `operator` `operator` ENUM('>', '<', '=', '!=', 'in') NOT NULL DEFAULT '=';"
            );
        } else {
            $this->Record->query(
                "ALTER TABLE `package_option_conditions` CHANGE `operator` `operator` ENUM('>', '<', '=', '!=', 'in', 'notin') NOT NULL DEFAULT '=';"
            );
        }
    }

    /**
     * Adds a new "hide_options" column to the package_option_groups table
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addOptionGroupsHideOptions($undo = false)
    {
        if ($undo) {
            $this->Record->query(
                "ALTER TABLE `package_option_groups` DROP `hide_options`;"
            );
        } else {
            $this->Record->query(
                "ALTER TABLE `package_option_groups` ADD `hide_options` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1';"
            );
        }
    }

    /**
     * Updates "package_option_conditions.value_id" to a text field
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function convertPackageOptionConditionValueId($undo = false)
    {
        if ($undo) {
            $conditions = $this->Record->select()->from('package_option_conditions')->fetchAll();
            foreach ($conditions as $condition) {
                $value_id = json_decode($condition->value_id, true);
                if (!$value_id) {
                    $value_id = $condition->value_id;
                } else {
                    $value_id = $value_id[0];
                }

                $this->Record->where('id', '=', $condition->id)->update('package_option_conditions', ['value_id' => $value_id]);
            }
            $this->Record->query(
                "ALTER TABLE `package_option_conditions` CHANGE `value_id` `value_id` INT(10) NULL DEFAULT NULL;"
            );
        } else {
            $this->Record->query(
                "ALTER TABLE `package_option_conditions` CHANGE `value_id` `value_id` MEDIUMTEXT NULL DEFAULT NULL;"
            );

            $conditions = $this->Record->select()->from('package_option_conditions')->fetchAll();
            foreach ($conditions as $condition) {
                $this->Record->where('id', '=', $condition->id)->
                    update('package_option_conditions', ['value_id' => json_encode($condition->value_id)]);
            }
        }
    }

    /**
     * Adds a new "package_option_condition_set_values" table
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addPackageOptionConditionSetValues($undo = false)
    {
        if ($undo) {
            // Add back the old package_option_condition_sets.value_id column
            $this->Record->query('ALTER TABLE `package_option_condition_sets`
                ADD `option_value_id` INT(10) UNSIGNED NULL DEFAULT NULL
            ');

            // Add current condition set value IDs to the old column
            $condition_set_values = $this->Record->select()->
                from('package_option_condition_set_values')->
                group('package_option_condition_set_values.condition_set_id')->
                fetchAll();
            foreach ($condition_set_values as $condition_set_value) {
                $this->Record->where('', '=', $condition_set_value->condition_set_id)->
                    update('package_option_condition_sets', ['value_id' => $condition_set_value->value_id]);
            }

            // Drop the new package_option_condition_set_values table
            $this->Record->drop('package_option_condition_set_values');
        } else {
            // Add new table
            $this->Record->
                setField('condition_set_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('value_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setKey(['condition_set_id', 'value_id'], 'primary')->
                create('package_option_condition_set_values', true);

            // Add current condition set value IDs to the new table
            $condition_sets = $this->Record->select()->from('package_option_condition_sets')->fetchAll();
            foreach ($condition_sets as $condition_set) {
                $this->Record->insert(
                    'package_option_condition_set_values',
                    ['condition_set_id' => $condition_set->id, 'value_id' => $condition_set->option_value_id]
                );
            }

            // Drop old package_option_condition_sets.value_id column
            $this->Record->query('ALTER TABLE `package_option_condition_sets` DROP `option_value_id`');
        }
    }

    /**
     * Removes the Postal Methods Return Address company settings
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function removePostalMethodsReturnAddressCompanySettings($undo = false)
    {
        if ($undo) {
            // Nothing to do
        } else {
            $settings = [
                'postalmethods_replyenvelope',
                'postalmethods_return_address1',
                'postalmethods_return_address2',
                'postalmethods_return_city',
                'postalmethods_return_country',
                'postalmethods_return_state',
                'postalmethods_return_zip'
            ];

            foreach ($settings as $setting) {
                $this->Record->from('company_settings')->
                    where('company_settings.key', '=', $setting)->
                    delete();
            }
        }
    }

    /**
     * Adds a new "subtract" column to the invoice_line_taxes table
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addInvoiceLineTaxesSubtractColumn($undo = false)
    {
        if ($undo) {
            $this->Record->query(
                "ALTER TABLE `invoice_line_taxes` DROP `subtract`;"
            );
        } else {
            $this->Record->query(
                "ALTER TABLE `invoice_line_taxes` ADD `subtract` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0';"
            );
        }
    }
}
