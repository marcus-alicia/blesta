<?php

use Blesta\Proration\Proration;

/**
 * Package management
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Packages extends AppModel
{
    /**
     * Initialize Packages
     */
    public function __construct()
    {
        parent::__construct();
        Language::loadLang(['packages']);
    }

    /**
     * Adds a new package to the system
     *
     * @param array $vars An array of package information including:
     *
     *  - module_id The ID of the module this package belongs to (optional, default NULL)
     *  - names A list of names for the package in different languages
     *      - lang The language in ISO 636-1 2-char + "_" + ISO 3166-1 2-char (e.g. en_us)
     *      - name The name in the specified language
     *  - descriptions A list of descriptions in text and html for the
     *      package in different languages (optional, default NULL)
     *      - lang The language in ISO 636-1 2-char + "_" + ISO 3166-1 2-char (e.g. en_us)
     *      - text The text description in the specified language
     *      - html The HTML description in the specified language
     *  - qty The maximum quantity available in this package, if any (optional, default NULL)
     *  - client_qty The maximum quantity available per client in this package, if any (optional, default NULL)
     *  - module_row The module row this package belongs to (optional, default 0)
     *  - module_group The module group this package belongs to (optional, default NULL)
     *  - module_groups An list of module groups available for this package (optional)
     *  - taxable Whether or not this package is taxable (optional, default 0)
     *  - single_term Whether or not services derived from this package
     *      should be canceled at the end of term (optional, default 0)
     *  - status The status of this package, 'active', 'inactive', 'restricted' (optional, default 'active')
     *  - hidden Whether or not to hide this package in the interface
     *      - 1 = true, 0 = false (optional, default 0)
     *  - company_id The ID of the company this package belongs to
     *  - prorata_day The prorated day of the month (optional, default NULL)
     *  - prorata_cutoff The day of the month pro rata should cut off (optional, default NULL)
     *  - email_content A numerically indexed array of email content including:
     *      - lang The language of the email content
     *      - html The html content for the email (optional)
     *      - text The text content for the email, will be created automatically from html if not given (optional)
     *  - pricing A numerically indexed array of pricing info including:
     *      - term The term as an integer 1-65535 (period should be given if this is set; optional, default 1)
     *      - period The period, 'day', 'week', 'month', 'year', 'onetime' (optional, default 'month')
     *      - price The price of this term (optional, default 0.00)
     *      - price_renews The renewal price of this term (optional, default null)
     *      - price_transfer The transfer price of this term (optional, default null)
     *      - setup_fee The setup fee for this package (optional, default 0.00)
     *      - cancel_fee The cancelation fee for this package (optional, default 0.00)
     *      - currency The ISO 4217 currency code for this pricing
     *  - groups A numerically indexed array of package group assignments (optional)
     *  - option_groups A numerically indexed array of package option group assignments (optional)
     *  - plugins A numerically-indexed array of valid plugin IDs to associate with the plugin (optional)
     *  - * A set of miscellaneous fields to pass, in addition to the above
     *      fields, to the module when adding the package (optional)
     * @return int The package ID created, void on error
     */
    public function add(array $vars)
    {
        // Trigger the Packages.addBefore event
        extract($this->executeAndParseEvent('Packages.addBefore', ['vars' => $vars]));

        if (($vars['module_group_client'] ?? '0') == '1') {
            $vars['module_group'] = null;
        }
        if (isset($vars['module_group']) && is_numeric($vars['module_group'])) {
            $vars['module_row'] = 0;
        }
        if (!isset($vars['company_id'])) {
            $vars['company_id'] = Configure::get('Blesta.company_id');
        }

        // Attempt to validate $vars with the module, set any meta fields returned by the module
        if (isset($vars['module_id']) && $vars['module_id'] != '') {
            if (!isset($this->ModuleManager)) {
                Loader::loadModels($this, ['ModuleManager']);
            }

            $module = $this->ModuleManager->initModule($vars['module_id']);

            if ($module) {
                $vars['meta'] = $module->addPackage($vars);

                // If any errors encountered through the module, set errors and return
                if (($errors = $module->errors())) {
                    $this->Input->setErrors($errors);
                    return;
                }
            }
        }

        $rules = $this->getRules($vars);

        // The required currency rule can be optional iff no pricing is given
        if (!array_key_exists('pricing', $vars)) {
            $rules['pricing[][currency]']['format']['if_set'] = true;
        }

        $this->Input->setRules($rules);

        if ($this->Input->validates($vars)) {
            // Fetch company settings on clients
            Loader::loadComponents($this, ['SettingsCollection']);
            $company_settings = $this->SettingsCollection->fetchSettings(null, $vars['company_id']);

            // Creates subquery to calculate the next package ID value on the fly
            $sub_query = new Record();
            /*
              $values = array($company_settings['packages_start'], $company_settings['packages_increment'],
              $company_settings['packages_start'], $company_settings['packages_increment'],
              $company_settings['packages_start'], $company_settings['packages_pad_size'],
              $company_settings['packages_pad_str']);
             */
            $values = [$company_settings['packages_start'], $company_settings['packages_increment'],
                $company_settings['packages_start']];

            /*
              $sub_query->select(array("LPAD(IFNULL(GREATEST(MAX(t1.id_value),?)+?,?), " .
              "GREATEST(CHAR_LENGTH(IFNULL(MAX(t1.id_value)+?,?)),?),?)"), false)->
             */
            $sub_query->select(['IFNULL(GREATEST(MAX(t1.id_value),?)+?,?)'], false)->
                appendValues($values)->
                from(['packages' => 't1'])->
                where('t1.company_id', '=', $vars['company_id'])->
                where('t1.id_format', '=', $company_settings['packages_format']);
            // run get on the query so $sub_query->values are built
            $sub_query->get();

            $vars['id_format'] = $company_settings['packages_format'];
            // id_value will be calculated on the fly using a subquery
            $vars['id_value'] = $sub_query;

            // Assign subquery values to this record component
            $this->Record->appendValues($sub_query->values);
            // Ensure the subquery value is set first because its the first value
            $vars = array_merge(['id_value' => null], $vars);

            // Add package
            $fields = ['id_format', 'id_value', 'module_id', 'qty', 'client_qty',
                'module_row', 'module_group', 'module_group_client', 'taxable', 'single_term', 'status', 'hidden',
                'company_id', 'prorata_day', 'prorata_cutoff', 'upgrades_use_renewal', 'override_price'
            ];
            $this->Record->insert('packages', $vars, $fields);

            $package_id = $this->Record->lastInsertId();

            // Add package email contents
            if (!empty($vars['email_content']) && is_array($vars['email_content'])) {
                for ($i = 0, $total = count($vars['email_content']); $i < $total; $i++) {
                    $vars['email_content'][$i]['package_id'] = $package_id;
                    $fields = ['package_id', 'lang', 'html', 'text'];
                    $this->Record->insert('package_emails', $vars['email_content'][$i], $fields);
                }
            }

            // Add package descriptions
            if (!empty($vars['descriptions']) && is_array($vars['descriptions'])) {
                $this->setDescriptions($package_id, $vars['descriptions']);
            }

            // Add package names
            if (!empty($vars['names']) && is_array($vars['names'])) {
                $this->setNames($package_id, $vars['names']);
            }

            // Add package pricing
            if (!empty($vars['pricing']) && is_array($vars['pricing'])) {
                for ($i = 0, $total = count($vars['pricing']); $i < $total; $i++) {
                    $vars['pricing'][$i]['package_id'] = $package_id;

                    // Default one-time package to a term of 0 (never renews)
                    if (isset($vars['pricing'][$i]['period']) && $vars['pricing'][$i]['period'] == 'onetime') {
                        $vars['pricing'][$i]['term'] = 0;
                    }

                    $vars['pricing'][$i]['company_id'] = $vars['company_id'];
                    $this->addPackagePricing($package_id, $vars['pricing'][$i]);
                }
            }

            // Add package meta data
            if (isset($vars['meta']) && !empty($vars['meta']) && is_array($vars['meta'])) {
                $this->setMeta($package_id, $vars['meta']);
            }

            // Set package option groups, if given
            if (!empty($vars['option_groups'])) {
                $this->setOptionGroups($package_id, $vars['option_groups']);
            }

            // Assign plugins if given
            if (!empty($vars['plugins'])) {
                $this->addPlugins($package_id, $vars['plugins']);
            }

            // Add all package groups given
            if (isset($vars['groups'])) {
                $this->setGroups($package_id, $vars['groups']);
            }

            // Set module groups
            if (isset($vars['module_groups'])) {
                $this->setModuleGroups($package_id, $vars['module_groups']);
            }

            // Trigger the Packages.addAfter event
            $this->executeAndParseEvent('Packages.addAfter', ['package_id' => $package_id, 'vars' => $vars]);

            return $package_id;
        }

        // Set any email content parse error
        $this->setParseError();
    }

    /**
     * Update an existing package ID with the data given
     *
     * @param int $package_id The ID of the package to update
     * @param array $vars An array of package information including:
     *
     *  - module_id The ID of the module this package belongs to (optional, default NULL)
     *  - names A list of names for the package in different languages
     *      - lang The language in ISO 636-1 2-char + "_" + ISO 3166-1 2-char (e.g. en_us)
     *      - name The name in the specified language
     *  - descriptions A list of descriptions in text and html for the
     *      package in different languages (optional, default NULL)
     *      - lang The language in ISO 636-1 2-char + "_" + ISO 3166-1 2-char (e.g. en_us)
     *      - text The text description in the specified language
     *      - html The HTML description in the specified language
     *  - qty The maximum quantity available in this package, if any (optional, default NULL)
     *  - client_qty The maximum quantity available per client in this package, if any (optional, default NULL)
     *  - module_row The module row this package belongs to (optional, default 0)
     *  - module_group The module group this package belongs to (optional, default NULL)
     *  - module_groups An list of module groups available for this package (optional)
     *  - taxable Whether or not this package is taxable (optional, default 0)
     *  - single_term Whether or not services derived from this package
     *      should be canceled at the end of term (optional, default 0)
     *  - status The status of this package, 'active', 'inactive', 'restricted' (optional, default 'active')
     *  - hidden Whether or not to hide this package in the interface
     *      - 1 = true, 0 = false (optional, default 0)
     *  - company_id The ID of the company this package belongs to (optional)
     *  - prorata_day The prorated day of the month (optional)
     *  - prorata_cutoff The day of the month pro rata should cut off (optional)
     *  - email_content A numerically indexed array of email content including:
     *      - lang The language of the email content
     *      - html The html content for the email (optional)
     *      - text The text content for the email, will be created automatically from html if not given (optional)
     *  - pricing A numerically indexed array of pricing info including (required):
     *      - id The pricing ID (optional, required if an edit else will add as new)
     *      - term The term as an integer 1-65535 (period should be given
     *          if this is set; optional, default 1), if term is empty will remove this pricing option
     *      - period The period, 'day', 'week', 'month', 'year', 'onetime' (optional, default 'month')
     *      - price The price of this term (optional, default 0.00)
     *      - price_renews The renewal price of this term (optional, default null)
     *      - price_transfer The transfer price of this term (optional, default null)
     *      - setup_fee The setup fee for this package (optional, default 0.00)
     *      - cancel_fee The cancelation fee for this package (optional, default 0.00)
     *      - currency The ISO 4217 currency code for this pricing
     *  - groups A numerically indexed array of package group assignments
     *      (optional), if given will replace all package group assignments with those given
     *  - option_groups A numerically indexed array of package option group assignments (optional)
     *  - plugins A numerically-indexed array of valid plugin IDs to associate with the plugin (optional)
     *  - * A set of miscellaneous fields to pass, in addition to the above
     *      fields, to the module when adding the package (optional)
     */
    public function edit($package_id, array $vars)
    {
        // Trigger the Packages.editBefore event
        extract($this->executeAndParseEvent('Packages.editBefore', ['package_id' => $package_id, 'vars' => $vars]));

        if (($vars['module_group_client'] ?? '0') == '1') {
            $vars['module_group'] = null;
        }
        if (isset($vars['module_group']) && is_numeric($vars['module_group'])) {
            $vars['module_row'] = 0;
        }

        $package = $this->get($package_id);

        // Set module ID if not given, it's necessary to have this in order to validate the meta fields with the module
        if (!isset($vars['module_id'])) {
            $vars['module_id'] = $package->module_id;
        }

        // Attempt to validate $vars with the module, set any meta fields returned by the module
        if (isset($vars['module_id']) && $vars['module_id'] != '') {
            if (!isset($this->ModuleManager)) {
                Loader::loadModels($this, ['ModuleManager']);
            }

            $module = $this->ModuleManager->initModule($vars['module_id']);

            if ($module) {
                $vars['meta'] = $module->editPackage($package, $vars);

                // If any errors encountered through the module, set errors and return
                if (($errors = $module->errors())) {
                    $this->Input->setErrors($errors);
                    return;
                }
            }
        }

        // Set company ID if not given, it's necessary to have this in order to validate package groups
        if (!isset($vars['company_id'])) {
            $vars['company_id'] = $package->company_id;
        }

        $this->Input->setRules($this->getRules($vars, true, $package_id));

        if ($this->Input->validates($vars)) {
            // Update package
            $fields = ['module_id', 'qty', 'client_qty', 'module_row',
                'module_group', 'module_group_client', 'taxable', 'single_term', 'status', 'hidden',
                'company_id', 'prorata_day', 'prorata_cutoff', 'upgrades_use_renewal', 'override_price'
            ];
            $this->Record->where('id', '=', $package_id)->update('packages', $vars, $fields);

            // Update package email
            if (!empty($vars['email_content']) && is_array($vars['email_content'])) {
                for ($i = 0, $total = count($vars['email_content']); $i < $total; $i++) {
                    $fields = ['package_id', 'lang', 'html', 'text'];
                    $vars['email_content'][$i]['package_id'] = $package_id;

                    $this->Record->duplicate(
                        'html',
                        '=',
                        isset($vars['email_content'][$i]['html']) ? $vars['email_content'][$i]['html'] : null
                    )
                    ->duplicate(
                        'text',
                        '=',
                        isset($vars['email_content'][$i]['text']) ? $vars['email_content'][$i]['text'] : null
                    )
                    ->insert('package_emails', $vars['email_content'][$i], $fields);
                }
            }

            // Update package descriptions
            if (!empty($vars['descriptions']) && is_array($vars['descriptions'])) {
                $this->setDescriptions($package_id, $vars['descriptions']);
            }

            // Update package names
            if (!empty($vars['names']) && is_array($vars['names'])) {
                $this->setNames($package_id, $vars['names']);
            }

            // Insert/update package prices
            if (!empty($vars['pricing']) && is_array($vars['pricing'])) {
                for ($i = 0, $total = count($vars['pricing']); $i < $total; $i++) {
                    // Default one-time package to a term of 0 (never renews)
                    if (isset($vars['pricing'][$i]['period']) && $vars['pricing'][$i]['period'] == 'onetime') {
                        $vars['pricing'][$i]['term'] = 0;
                    }

                    $vars['pricing'][$i]['company_id'] = $vars['company_id'];
                    if (!empty($vars['pricing'][$i]['id'])) {
                        $this->editPackagePricing($vars['pricing'][$i]['id'], $vars['pricing'][$i]);
                    } else {
                        $this->addPackagePricing($package_id, $vars['pricing'][$i]);
                    }
                }
            }

            // Update package meta data
            if (isset($vars['meta']) && is_array($vars['meta'])) {
                $this->setMeta($package_id, $vars['meta']);
            }

            // Set package option groups, if given
            if (isset($vars['option_groups']) && is_array($vars['option_groups'])) {
                $this->removeOptionGroups($package_id);
                $this->setOptionGroups($package_id, $vars['option_groups']);
            }

            // Assign plugins if given
            if (isset($vars['plugins']) && is_array($vars['plugins'])) {
                $this->removePlugins($package_id);
                $this->addPlugins($package_id, $vars['plugins']);
            }

            // Replace all group assignments with those that are given (if any given)
            if (isset($vars['groups'])) {
                $this->setGroups($package_id, $vars['groups']);
            }

            // Set module groups
            if (isset($vars['module_groups'])) {
                $this->setModuleGroups($package_id, $vars['module_groups']);
            }

            // Trigger the Packages.editAfter event
            $this->executeAndParseEvent(
                'Packages.editAfter',
                ['package_id' => $package_id, 'vars' => $vars, 'old_package' => $package]
            );
        }

        // Set any email content parse error
        $this->setParseError();
    }

    /**
     * Permanently removes the given package from the system. Packages can only
     * be deleted if no services exist for that package.
     *
     * @param int $package_id The package ID to delete
     * @param bool $remove_services True to remove all canceled services related to the
     *  package (optional, default false)
     */
    public function delete($package_id, $remove_services = false)
    {
        // Trigger the Packages.deleteBefore event
        extract($this->executeAndParseEvent(
            'Packages.deleteBefore',
            ['package_id' => $package_id, 'remove_services' => $remove_services]
        ));

        Loader::loadModels($this, ['Services']);

        $vars = ['package_id' => $package_id];

        $rules = [
            'package_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateServiceExists'], $remove_services],
                    'negate' => true,
                    'message' => $this->_('Packages.!error.package_id.exists')
                ],
                'has_children' => [
                    'rule' => [[$this, 'validateHasChildren'], 'canceled'],
                    'negate' => true,
                    'message' => $this->_('Packages.!error.package_id.has_children')
                ]
            ]
        ];

        $this->Input->setRules($rules);

        if ($this->Input->validates($vars)) {
            // Get the package state prior to update
            $package = $this->get($package_id);

            // Remove cancelled services belonging to this package
            if ($remove_services) {
                $canceled_services = $this->getPackageServices($package_id);

                foreach ($canceled_services as $service) {
                    $this->Services->delete($service->id);
                }
            }

            // No active services exist for this package, so it's safe to delete it
            $this->Record->from('packages')
                ->leftJoin('package_descriptions', 'package_descriptions.package_id', '=', 'packages.id', false)
                ->leftJoin('package_names', 'package_names.package_id', '=', 'packages.id', false)
                ->leftJoin('package_emails', 'package_emails.package_id', '=', 'packages.id', false)
                ->leftJoin('package_meta', 'package_meta.package_id', '=', 'packages.id', false)
                ->leftJoin('package_pricing', 'package_pricing.package_id', '=', 'packages.id', false)
                ->leftJoin('pricings', 'pricings.id', '=', 'package_pricing.pricing_id', false)
                ->leftJoin('package_group', 'package_group.package_id', '=', 'packages.id', false)
                ->leftJoin('package_plugins', 'package_plugins.package_id', '=', 'packages.id', false)
                ->leftJoin('package_option', 'package_option.package_id', '=', 'packages.id', false)
                ->leftJoin('coupon_packages', 'coupon_packages.package_id', '=', 'packages.id', false)
                ->leftJoin('client_packages', 'client_packages.package_id', '=', 'packages.id', false)
                ->where('packages.id', '=', $package_id)
                ->delete([
                    'packages.*', 'package_descriptions.*', 'package_names.*', 'package_emails.*', 'package_meta.*',
                    'package_pricing.*', 'pricings.*', 'package_group.*', 'package_plugins.*',
                    'package_option.*', 'coupon_packages.*', 'client_packages.*'
                ]);

            // Trigger the Packages.deleteAfter event
            $this->executeAndParseEvent(
                'Packages.deleteAfter',
                ['package_id' => $package_id, 'old_package' => $package]
            );
        }
    }

    /**
     * Sets the multilingual package descriptions
     *
     * @param int $package_id The ID of the package to set the names for
     * @param array $descriptions An array including:
     *
     *  - lang The language code (e.g. 'en_us')
     *  - text The text in the specified language
     *  - html The HTML in the specified language
     */
    private function setDescriptions($package_id, array $descriptions)
    {
        // Add package descriptions
        if (!empty($descriptions) && is_array($descriptions)) {
            $fields = ['package_id', 'lang', 'html', 'text'];

            foreach ($descriptions as $description) {
                $description['package_id'] = $package_id;
                $this->Record->duplicate('html', '=', (isset($description['html']) ? $description['html'] : null))
                    ->duplicate('text', '=', (isset($description['text']) ? $description['text'] : null))
                    ->insert('package_descriptions', $description, $fields);
            }
        }
    }

    /**
     * Sets the multilingual package names
     *
     * @param int $package_id The ID of the package to set the names for
     * @param array $names An array including:
     *
     *  - lang The language code (e.g. 'en_us')
     *  - name The name in the specified language
     */
    private function setNames($package_id, array $names)
    {
        // Add package names
        if (!empty($names) && is_array($names)) {
            $fields = ['package_id', 'lang', 'name'];

            foreach ($names as $name) {
                $name['package_id'] = $package_id;
                $this->Record->duplicate('name', '=', (isset($name['name']) ? $name['name'] : null))
                    ->insert('package_names', $name, $fields);
            }
        }
    }

    /**
     * Save the packages for the given group in the provided order
     *
     * @param int $package_group_id The ID of the package group to order packages for
     * @param array $package_ids A numerically indexed array of package IDs
     */
    public function orderPackages($package_group_id, array $package_ids)
    {
        for ($i = 0, $total = count($package_ids); $i < $total; $i++) {
            $this->Record->where('package_id', '=', $package_ids[$i])->
                where('package_group_id', '=', $package_group_id)->
                update('package_group', ['order' => $i]);
        }
    }

    /**
     * Fetches the given package
     *
     * @param int $package_id The package ID to fetch
     * @return mixed A stdClass object representing the package, false if no such package exists
     */
    public function get($package_id)
    {
        $package = $this->Record->select($this->getSelectFieldList())->
            appendValues([$this->replacement_keys['packages']['ID_VALUE_TAG']])->
            from('packages')->
            on('package_names.lang', '=', Configure::get('Blesta.language'))->
            leftJoin('package_names', 'package_names.package_id', '=', 'packages.id', false)->
            where('id', '=', $package_id)->fetch();

        if ($package) {
            $package = $this->appendPackageContent($package);
        }

        return $package;
    }

    /**
     * Gets a list a fields to fetch for packages
     *
     * @return array A list a fields to fetch for packages
     */
    private function getSelectFieldList()
    {
        return [
            'packages.id',
            'packages.id_format',
            'packages.id_value',
            'REPLACE(packages.id_format, ?, packages.id_value)' => 'id_code',
            'packages.module_id',
            'package_names.name' => 'name',
            'packages.qty',
            'packages.client_qty',
            'packages.module_row',
            'packages.module_group',
            'packages.module_group_client',
            'packages.taxable',
            'packages.single_term',
            'packages.status',
            'packages.hidden',
            'packages.company_id',
            'packages.prorata_day',
            'packages.prorata_cutoff',
            'packages.upgrades_use_renewal',
            'packages.override_price'
        ];
    }

    /**
     * Fetches the given package by package pricing ID
     *
     * @param int $package_pricing_id The package pricing ID to use to fetch the package
     * @return mixed A stdClass object representing the package, false if no such package exists
     */
    public function getByPricingId($package_pricing_id)
    {
        $package = $this->Record->select($this->getSelectFieldList())->
            appendValues([$this->replacement_keys['packages']['ID_VALUE_TAG']])->
            from('packages')->
            on('package_names.lang', '=', Configure::get('Blesta.language'))->
            leftJoin('package_names', 'package_names.package_id', '=', 'packages.id', false)->
            innerJoin('package_pricing', 'package_pricing.package_id', '=', 'packages.id', false)->
            where('package_pricing.id', '=', $package_pricing_id)->
            fetch();

        if ($package) {
            $package = $this->appendPackageContent($package);
        }

        return $package;
    }

    /**
     * Updates the given package to set additional package information
     *
     * @param stdClass $package The package to update, containing at minimum:
     *
     *  - id The package ID
     * @return stdClass The updated package
     */
    private function appendPackageContent(stdClass $package)
    {
        $package->email_content = $this->getPackageEmails($package->id);
        $package->pricing = $this->getPackagePricing($package->id);
        $package->meta = $this->getPackageMeta($package->id);
        $package->groups = $this->getPackageGroups($package->id);
        $package->module_groups = $this->getPackageModuleGroups($package->id);
        $package->option_groups = $this->getPackageOptionGroups($package->id);
        $package->plugins = $this->getPackagePlugins($package->id);

        return $this->appendPackageNamesDescriptions($package);
    }

    /**
     * Updates the given package to set package names and descriptions
     *
     * @param stdClass $package The package to update, containing at minimum:
     *
     *  - id The package ID
     * @return stdClass The updated package
     */
    private function appendPackageNamesDescriptions(stdClass $package)
    {
        $package->names = $this->getPackageNames($package->id);
        $package->descriptions = $this->getPackageDescriptions($package->id);

        foreach ($package->names as $name) {
            if ($name->lang == Configure::get('Blesta.language')) {
                $package->name = $name->name;
                break;
            } elseif ($name->lang == 'en_us') {
                $package->name = $name->name;
            }
        }

        foreach ($package->descriptions as $description) {
            if ($description->lang == Configure::get('Blesta.language')) {
                $package->description = $description->text;
                $package->description_html = $description->html;
                break;
            } elseif ($description->lang == 'en_us') {
                $package->description = $description->text;
                $package->description_html = $description->html;
            }
        }

        return $package;
    }

    /**
     * Updates the given package group to set package names and descriptions
     *
     * @param stdClass $package_group The package_group to update, containing at minimum:
     *
     *  - id The package group ID
     * @return stdClass The updated package group
     */
    private function appendGroupNamesDescriptions(stdClass $package_group)
    {
        $package_group->names = $this->getGroupNames($package_group->id);
        $package_group->descriptions = $this->getGroupDescriptions($package_group->id);

        foreach ($package_group->names as $name) {
            if ($name->lang == Configure::get('Blesta.language')) {
                $package_group->name = $name->name;
                break;
            } elseif ($name->lang == 'en_us') {
                $package_group->name = $name->name;
            }
        }

        foreach ($package_group->descriptions as $description) {
            if ($description->lang == Configure::get('Blesta.language')) {
                $package_group->description = $description->description;
                break;
            } elseif ($description->lang == 'en_us') {
                $package_group->description = $description->description;
            }
        }

        return $package_group;
    }

    /**
     * Fetch all packages belonging to the given company
     *
     * @param int $company_id The ID of the company to fetch pages for
     * @param array $order The sort order in key = value order, where 'key' is
     *  the field to sort on and 'value' is the order to sort (asc or desc)
     * @param string $status The status type of packages to retrieve
     *  ('active', 'inactive', 'restricted', default null for all)
     * @param string $type The type of packages to retrieve ('standard', 'addon', default null for all)
     * @param array $filters  A list of package filters including:
     *
     *  - status The status type of packages to retrieve
     *      ('active', 'inactive', 'restricted', default null for all)
     *  - name The name or part of the name of the packages to fetch
     *  - module_id The module ID to filter packages on
     *  - package_group_id The package group ID on which to filter packages
     *  - hidden Whether or nor to include the hidden packages
     * @return array An array of stdClass objects each representing a package
     */
    public function getAll(
        $company_id,
        array $order = ['name' => 'ASC'],
        $status = null,
        $type = null,
        array $filters = []
    ) {
        // If sorting by ID code, use id code sort mode
        if (isset($order_by['id_code']) && Configure::get('Blesta.id_code_sort_mode')) {
            $temp = $order_by['id_code'];
            unset($order_by['id_code']);

            foreach ((array) Configure::get('Blesta.id_code_sort_mode') as $key) {
                $order_by[$key] = $temp;
            }
        }

        $this->Record = $this->getPackages(array_merge($filters, ['status' => $status]));

        if ($type) {
            $this->Record->innerJoin('package_group', 'package_group.package_id', '=', 'packages.id', false)->
                innerJoin('package_groups', 'package_groups.id', '=', 'package_group.package_group_id', false)->
                where('package_groups.type', '=', $type)->
                order(['package_group.order' => 'ASC'])->
                group(['packages.id']);
        }

        $packages = $this->Record->order($order)->where('packages.company_id', '=', $company_id)->fetchAll();

        // Append package names and descriptions to all fetched packages
        foreach ($packages as &$package) {
            $package = $this->appendPackageNamesDescriptions($package);
        }

        return $packages;
    }

    /**
     * Fetches a list of all packages
     *
     * @param int $page The page to return results for (optional, default 1)
     * @param array $order_by The sort and order conditions (e.g. array('sort_field'=>"ASC"), optional)
     * @param string $status The status type of packages to retrieve
     *  ('active', 'inactive', 'restricted', default null for all)
     * @param array $filters A list of package filters including: (optional)
     *
     *  - status The status type of packages to retrieve
     *      ('active', 'inactive', 'restricted', default null for all)
     *  - name The name or part of the name of the packages to fetch
     *  - module_id The module ID to filter packages on
     *  - package_group_id The package group ID on which to filter packages
     *  - hidden Whether or nor to include the hidden packages
     * @return array An array of stdClass objects each representing a package
     */
    public function getList($page = 1, array $order_by = ['id_code' => 'asc'], $status = null, array $filters = [])
    {
        // If sorting by ID code, use id code sort mode
        if (isset($order_by['id_code']) && Configure::get('Blesta.id_code_sort_mode')) {
            $temp = $order_by['id_code'];
            unset($order_by['id_code']);

            foreach ((array) Configure::get('Blesta.id_code_sort_mode') as $key) {
                $order_by[$key] = $temp;
            }
        }

        $this->Record = $this->getPackages(array_merge(['status' => $status], $filters));
        $packages = $this->Record->where('packages.company_id', '=', Configure::get('Blesta.company_id'))->
            order($order_by)->
            limit($this->getPerPage(), (max(1, $page) - 1) * $this->getPerPage())->fetchAll();

        // Append package names and descriptions to all fetched packages
        foreach ($packages as &$package) {
            $package = $this->appendPackageNamesDescriptions($package);
        }

        return $packages;
    }

    /**
     * Search packages
     *
     * @param string $query The value to search packages for
     * @param int $page The page number of results to fetch (optional, default 1)
     * @return array An array of packages that match the search criteria
     */
    public function search($query, $page = 1)
    {
        $this->Record = $this->searchPackages($query);

        // Set order by clause
        $order_by = [];
        if (Configure::get('Blesta.id_code_sort_mode')) {
            foreach ((array) Configure::get('Blesta.id_code_sort_mode') as $key) {
                $order_by[$key] = 'ASC';
            }
        } else {
            $order_by = ['name' => 'DESC'];
        }

        $packages = $this->Record->order($order_by)->
            limit($this->getPerPage(), (max(1, $page) - 1) * $this->getPerPage())->fetchAll();

        // Append package names and descriptions to all fetched packages
        foreach ($packages as &$package) {
            $package = $this->appendPackageNamesDescriptions($package);
        }

        return $packages;
    }

    /**
     * Return the total number of packages returned from Packages::search(), useful
     * in constructing pagination
     *
     * @param string $query The value to search services for
     * @see Packages::search()
     */
    public function getSearchCount($query)
    {
        $this->Record = $this->searchPackages($query);
        return $this->Record->numResults();
    }

    /**
     * Partially constructs the query for searching packages
     *
     * @param string $query The value to search packages for
     * @return Record The partially constructed query Record object
     * @see Packages::search(), Packages::getSearchCount()
     */
    private function searchPackages($query)
    {
        $this->Record = $this->getPackages();
        $this->Record->where('packages.company_id', '=', Configure::get('Blesta.company_id'));

        $sub_query_sql = $this->Record->get();
        $values = $this->Record->values;
        $this->Record->reset();

        $this->Record = $this->Record->select()->appendValues($values)->from([$sub_query_sql => 'temp'])->
            like('CONVERT(temp.id_code USING utf8)', '%' . $query . '%', true, false)->
            orLike('temp.name', '%' . $query . '%')->
            orLike('temp.module_name', '%' . $query . '%');
        return $this->Record;
    }

    /**
     * Retrieves a list of package pricing periods
     *
     * @param bool $plural True to return language for plural periods, false for singular
     * @return array Key=>value pairs of package pricing periods
     */
    public function getPricingPeriods($plural = false)
    {
        if (!isset($this->Pricings)) {
            Loader::loadModels($this, ['Pricings']);
        }
        return $this->Pricings->getPeriods($plural);
    }

    /**
     * Retrieves a list of package status types
     *
     * @return array Key=>value pairs of package status types
     */
    public function getStatusTypes()
    {
        return [
            'active' => $this->_('Packages.getStatusTypes.active'),
            'inactive' => $this->_('Packages.getStatusTypes.inactive'),
            'restricted' => $this->_('Packages.getStatusTypes.restricted')
        ];
    }

    /**
     * Retrieves a list of acceptable pro rata day options
     *
     * @return array A set of key=>value pairs of pro rata days
     */
    public function getProrataDays()
    {
        $range = range(1, 28, 1);
        return array_combine($range, $range);
    }

    /**
     * Determines whether the pro rata cutoff day has passed
     *
     * @param string $date The date to check
     * @param int $cutoff_day The day of the month representing the cutoff day
     * @return bool True if the given date is after the pro rata cutoff day, or false otherwise
     */
    public function isDateAfterProrataCutoff($date, $cutoff_day)
    {
        // Set local date
        $local_date = clone $this->Date;
        $local_date->setTimezone('UTC', Configure::get('Blesta.company_timezone'));

        if (!$this->Input->isDate($date) || !$cutoff_day) {
            return false;
        }
        return ((int) $local_date->cast($date, 'j') > $cutoff_day);
    }

    /**
     * Retrieves the date to prorate a service to
     *
     * @param string $start_date The date to start from
     * @param string $period The period associated with the service
     * @param int $pro_rata_day The day of the month to prorate to
     * @return mixed The prorated UTC end date; null if not prorating; or boolean false if it cannot be determined
     */
    public function getProrateDate($start_date, $period, $pro_rata_day)
    {
        // Convert date to UTC
        $start_date = $this->dateToUtc($start_date, 'c');

        // Not prorating if today is the pro rata day
        if ($this->isProrataDay($start_date, $pro_rata_day)) {
            return null;
        }

        try {
            // Determine the prorate date
            $Proration = new Proration($start_date, $pro_rata_day, null, $period);
            $Proration->setTimezone(Configure::get('Blesta.company_timezone'));
            $date = $Proration->prorateDate();
        } catch (Exception $e) {
            return false;
        }

        // Unable to determine whether proration may occur if there is no date or prorate is false
        if (!$date) {
            return false;
        }

        // Set the prorate date in UTC
        return $this->dateToUtc($date, 'c');
    }

    /**
     * Determines whether the given start date is on the pro rata day
     *
     * @param string $start_date The start date
     * @param int $pro_rata_day The pro rata day of the month
     * @return bool True if the given start date is the current pro rata day
     *  for the company's timezone, or false otherwise
     */
    public function isProrataDay($start_date, $pro_rata_day)
    {
        // Set the Date to the current timezone
        $Date = clone $this->Date;
        $Date->setTimezone('UTC', Configure::get('Blesta.company_timezone'));
        $start_date = $Date->cast($start_date, 'c');
        $day = $Date->cast($start_date, 'j');

        return ($day == $pro_rata_day);
    }

    /**
     * Retrieves the number of days to prorate a service
     *
     * @param string $start_date The date to start from
     * @param string $period The period associated with the service
     * @param int $pro_rata_day The day of the month to prorate to
     * @return mixed The number of days to prorate, or boolean false if it cannot be determined
     */
    public function getDaysToProrate($start_date, $period, $pro_rata_day)
    {
        // Convert date to UTC
        $start_date = $this->dateToUtc($start_date, 'c');

        try {
            // Determine the prorate date
            $Proration = new Proration($start_date, $pro_rata_day, null, $period);
            $Proration->setTimezone(Configure::get('Blesta.company_timezone'));
            $prorate_date = $Proration->prorateDate();
            $prorate_days = $Proration->prorateDays();
        } catch (Exception $e) {
            return false;
        }

        // Return the number of days to prorate, or false if there is no
        // prorate date (because it could not be determined)
        return ($prorate_date ? $prorate_days : false);
    }

    /**
     * Retrieves the price to prorate a value given the date, term, and period to prorate from
     *
     * @param float $amount The total cost for the given term and period
     * @param string $start_date The start date to calculate the price from
     * @param int $term The term length
     * @param string $period The period type (e.g. "month", "year")
     * @param int $pro_rata_day The day of the month to prorate to
     * @param bool $allow_all_recurring_periods True to allow all recurring
     *  periods, or false to limit to "month" and "year" only (optional, default false)
     * @param string $prorate_date The date to prorate to. Setting this value will ignore the $pro_rata_day (optional)
     * @return float The prorate price
     */
    public function getProratePrice(
        $amount,
        $start_date,
        $term,
        $period,
        $pro_rata_day,
        $allow_all_recurring_periods = false,
        $prorate_date = null
    ) {
        // Return the given amount if invalid proration values were given
        if ((!is_numeric($pro_rata_day) && empty($prorate_date))
            || $period == 'onetime'
            || (!$allow_all_recurring_periods && !in_array($period, ['month', 'year']))
        ) {
            return $amount;
        }

        // Convert date to UTC
        $start_date = $this->dateToUtc($start_date, 'c');

        try {
            // Determine the prorate price
            $Proration = new Proration($start_date, $pro_rata_day, $term, $period);
            $Proration->setTimezone(Configure::get('Blesta.company_timezone'));

            // Set the prorate date if given
            if ($prorate_date) {
                $Proration->setProrateDate($this->dateToUtc($prorate_date, 'c'));
            }

            // Set periods available for proration
            if ($allow_all_recurring_periods) {
                $periods = [
                    Proration::PERIOD_DAY, Proration::PERIOD_WEEK,
                    Proration::PERIOD_MONTH, Proration::PERIOD_YEAR
                ];
                $Proration->setProratablePeriods($periods);
            }

            $price = $Proration->proratePrice($amount);
        } catch (Exception $e) {
            $price = 0.0;
        }

        return $price;
    }

    /**
     * Gets a list of dates for proration
     *
     * @param int $pricing_id The ID of the pricing to get the package and period from
     * @param string $prorate_from_date The date to base proration on
     * @param string $date_to_prorate The date to prorate
     * @return mixed An array of dates for proration, false on failure
     */
    public function getProrataDates($pricing_id, $prorate_from_date, $date_to_prorate)
    {
        if (!isset($this->Services)) {
            Loader::loadModels($this, ['Services']);
        }

        if (($package = $this->getByPricingId($pricing_id))) {
            // Get the correct pricing for proration
            $pricing = null;
            foreach ($package->pricing as $package_pricing) {
                if ($package_pricing->id == $pricing_id) {
                    $pricing = $package_pricing;
                }
            }

            if ($pricing) {
                // Get the number of days until the package's prorata date
                $prorate_days = $this->getDaysToProrate(
                    $prorate_from_date,
                    $pricing->period,
                    $package->prorata_day
                );

                if ($prorate_days !== false
                    && $package->prorata_day != null
                    && $package->prorata_cutoff != null
                    && $this->isDateAfterProrataCutoff(
                        $prorate_from_date,
                        $package->prorata_cutoff
                    )
                ) {
                    // Get the beginning date and the ending prorated date
                    $dates = [
                        'end_date' => $this->Services->getNextRenewDate(
                            $date_to_prorate,
                            $pricing->term,
                            $pricing->period,
                            'c'
                        ),
                        'start_date' => $date_to_prorate
                    ];

                    return $dates;
                }
            }
        }

        return false;
    }

    /**
     * Convert pricing to the given currency if allowed
     *
     * @param stdClass $pricing A stdClass object representing a package pricing
     * @param string $currency The ISO 4217 currency code to convert to
     * @param bool $allow_conversion True to allow conversion, false otherwise
     * @return mixed The stdClass object representing the converted pricing (if conversion allowed), null otherwise
     */
    public function convertPricing($pricing, $currency, $allow_conversion)
    {
        if (!isset($this->Currencies)) {
            Loader::loadModels($this, ['Currencies']);
        }

        $company_id = Configure::get('Blesta.company_id');

        if ($pricing->currency == $currency) {
            return $pricing;
        } elseif ($allow_conversion) {
            // Convert prices and set converted currency
            $pricing->price = $this->Currencies->convert(
                $pricing->price,
                $pricing->currency,
                $currency,
                $company_id
            );
            $pricing->price_renews = $this->Currencies->convert(
                $pricing->price_renews,
                $pricing->currency,
                $currency,
                $company_id
            );
            $pricing->price_transfer = $this->Currencies->convert(
                $pricing->price_transfer,
                $pricing->currency,
                $currency,
                $company_id
            );
            $pricing->setup_fee = $this->Currencies->convert(
                $pricing->setup_fee,
                $pricing->currency,
                $currency,
                $company_id
            );
            if (isset($pricing->cancel_fee)) {
                $pricing->cancel_fee = $this->Currencies->convert(
                    $pricing->cancel_fee,
                    $pricing->currency,
                    $currency,
                    $company_id
                );
            }
            $pricing->currency = $currency;

            return $pricing;
        }

        return null;
    }

    /**
     * Return the total number of packages returned from Packages::getList(),
     * useful in constructing pagination for the getList() method.
     *
     * @param string $status The status type of packages to retrieve
     *  ('active', 'inactive', 'restricted', default null for all)
     * @param array $filters  A list of package filters including:
     *
     *  - status The status type of packages to retrieve
     *      ('active', 'inactive', 'restricted', default null for all)
     *  - name The name or part of the name of the packages to fetch
     *  - module_id The module ID to filter packages on
     *  - package_group_id The package group ID on which to filter packages
     *  - hidden Whether or nor to include the hidden packages
     * @return int The total number of packages
     * @see Packages::getList()
     */
    public function getListCount($status = null, array $filters = [])
    {
        $this->Record = $this->getPackages(array_merge(['status' => $status], $filters));

        return $this->Record->where('packages.company_id', '=', Configure::get('Blesta.company_id'))->numResults();
    }

    /**
     * Fetches all package groups belonging to a company, or optionally, all package
     * groups belonging to a specific package
     *
     * @param int $company_id The company ID
     * @param int $package_id The package ID to fetch groups of (optional, default null)
     * @param string $type The type of group to fetch (null, standard, addon)
     * @param array $filters A list of package groups filters including:
     *
     *  - hidden Whether or nor to include the hidden package groups
     * @return mixed An array of stdClass objects representing package groups, or false if none found
     */
    public function getAllGroups($company_id, $package_id = null, $type = null, array $filters = [])
    {
        $this->Record->select([
                'package_groups.id',
                'package_groups.type',
                'package_groups.hidden',
                'package_groups.company_id',
                'package_groups.allow_upgrades'
            ])->
            from('package_groups');

        if ($package_id != null) {
            $this->Record->innerJoin(
                'package_group',
                'package_group.package_group_id',
                '=',
                'package_groups.id',
                false
            )
                ->innerJoin('packages', 'packages.id', '=', 'package_group.package_id', false)
                ->where('packages.id', '=', $package_id);
        }

        if ($type) {
            $this->Record->where('package_groups.type', '=', $type);
        }

        if (!(!empty($filters['hidden']) && (bool)$filters['hidden'])) {
            $this->Record->where('package_groups.hidden', '=', 0);
        }

        $package_groups = $this->Record->where('package_groups.company_id', '=', $company_id)->fetchAll();

        // Append names and descriptions to all fetched package groups
        foreach ($package_groups as &$package_group) {
            $package_group = $this->appendGroupNamesDescriptions($package_group);
        }

        usort(
            $package_groups,
            function ($groupA, $groupB) { return strcmp($groupA->name, $groupB->name); }
        );


        return $package_groups;
    }

    /**
     * Returns all addon package groups for the given package group.
     *
     * @param int $parent_group_id The ID of the parent package group
     * @return array A list of addon package groups
     */
    public function getAllAddonGroups($parent_group_id)
    {
        $package_groups = $this->Record->select([
                'package_groups.id',
                'package_groups.type',
                'package_groups.company_id',
                'package_groups.allow_upgrades'
            ])->
            from('package_group_parents')->
            on('package_groups.type', '=', 'addon')->
            innerJoin('package_groups', 'package_groups.id', '=', 'package_group_parents.group_id', false)->
            where('package_group_parents.parent_group_id', '=', $parent_group_id)->fetchAll();

        // Append names and descriptions to all fetched package groups
        foreach ($package_groups as &$package_group) {
            $package_group = $this->appendGroupNamesDescriptions($package_group);
        }

        return $package_groups;
    }

    /**
     * Fetches all packages belonging to a specific package group
     *
     * @param int $package_group_id The ID of the package group
     * @param string $status The status type of packages to retrieve
     *  ('active', 'inactive', 'restricted', default null for all)
     * @param array $filters  A list of package filters including:
     *
     *  - status The status type of packages to retrieve
     *      ('active', 'inactive', 'restricted', default null for all)
     *  - name The name or part of the name of the packages to fetch
     *  - module_id The module ID to filter packages on
     *  - hidden Whether or nor to include the hidden packages
     * @return mixed An array of stdClass objects representing packages, or false if none exist
     */
    public function getAllPackagesByGroup($package_group_id, $status = null, array $filters = [])
    {
        if (!isset($filters['hidden'])) {
            $package_group = $this->Record->select()->
                from('package_groups')->
                where('package_groups.id', '=', $package_group_id)->
                fetch();
            if ($package_group) {
                $filters['hidden'] = ($package_group->hidden == '1');
            }
        }
        $this->Record = $this->getPackages(array_merge($filters, ['status' => $status]));
        $packages = $this->Record->innerJoin('package_group', 'packages.id', '=', 'package_group.package_id', false)->
            where('package_group.package_group_id', '=', $package_group_id)->
            order(['package_group.order' => 'ASC'])->
            fetchAll();

        foreach ($packages as &$package) {
            $package->pricing = $this->getPackagePricing($package->id);
            $package->meta = $this->getPackageMeta($package->id);
            $package = $this->appendPackageNamesDescriptions($package);
        }

        return $packages;
    }

    /**
     * Get all compatible packages
     *
     * @param int $package_id The ID of the package to fetch all compatible packages for
     * @param int $module_id The ID of the module to include compatible packages for
     * @param string $type The type of package group to include ("standard", "addon")
     * @return array An array of stdClass objects, each representing a compatible package and its pricing
     */
    public function getCompatiblePackages($package_id, $module_id, $type)
    {
        $subquery_record = clone $this->Record;
        $subquery_record->select(['package_group.*'])->from('packages')->
            innerJoin('package_group', 'package_group.package_id', '=', 'packages.id', false)->
            where('packages.id', '=', $package_id);
        $subquery = $subquery_record->get();
        $values = $subquery_record->values;
        unset($subquery_record);


        $this->Record = $this->getPackages();
        $packages = $this->Record->
            innerJoin('package_group', 'packages.id', '=', 'package_group.package_id', false)->
            appendValues($values)->
            innerJoin([$subquery => 'temp'], 'temp.package_group_id', '=', 'package_group.package_group_id', false)->
            innerJoin('package_groups', 'package_groups.id', '=', 'package_group.package_group_id', false)->
            where('package_groups.type', '=', $type)->
            where('package_groups.allow_upgrades', '=', '1')->
            where('packages.module_id', '=', $module_id)->
            order(['package_group.order' => 'ASC'])->
            fetchAll();

        foreach ($packages as &$package) {
            $package->pricing = $this->getPackagePricing($package->id);
            $package = $this->appendPackageNamesDescriptions($package);
        }

        return $packages;
    }

    /**
     * Fetches all names created for the given package
     *
     * @param int $package_id The package ID to fetch names for
     * @return array An array of stdClass objects representing package names
     */
    private function getPackageNames($package_id)
    {
        return $this->Record->select(['lang', 'name'])
            ->from('package_names')
            ->where('package_id', '=', $package_id)
            ->fetchAll();
    }

    /**
     * Fetches all descriptions created for the given package
     *
     * @param int $package_id The package ID to fetch descriptions for
     * @return array An array of stdClass objects representing package descriptions
     */
    private function getPackageDescriptions($package_id)
    {
        return $this->Record->select(['lang', 'html', 'text'])
            ->from('package_descriptions')
            ->where('package_id', '=', $package_id)
            ->fetchAll();
    }

    /**
     * Fetches all names created for the given package group
     *
     * @param int $package_group_id The package group ID to fetch names for
     * @return array An array of stdClass objects representing group names
     */
    private function getGroupNames($package_group_id)
    {
        return $this->Record->select(['lang', 'name'])
            ->from('package_group_names')
            ->where('package_group_id', '=', $package_group_id)
            ->fetchAll();
    }

    /**
     * Fetches all descriptions created for the given package group
     *
     * @param int $package_group_id The package group ID to fetch descriptions for
     * @return array An array of stdClass objects representing group descriptions
     */
    private function getGroupDescriptions($package_group_id)
    {
        return $this->Record->select(['lang', 'description'])
            ->from('package_group_descriptions')
            ->where('package_group_id', '=', $package_group_id)
            ->fetchAll();
    }

    /**
     * Fetches all emails created for the given package
     *
     * @param int $package_id The package ID to fetch email for
     * @return array An array of stdClass objects representing email content
     */
    private function getPackageEmails($package_id)
    {
        return $this->Record->select(['lang', 'html', 'text'])->from('package_emails')->
            where('package_id', '=', $package_id)->fetchAll();
    }

    /**
     * Fetches all pricing for the given package
     *
     * @param int $package_id The package ID to fetch pricing for
     * @return array An array of stdClass objects representing package pricing
     */
    private function getPackagePricing($package_id)
    {
        Loader::loadHelpers($this, ['CurrencyFormat']);

        $fields = ['package_pricing.id', 'package_pricing.pricing_id', 'package_pricing.package_id', 'pricings.term',
            'pricings.period', 'pricings.price', 'pricings.price_renews', 'pricings.price_transfer', 'pricings.setup_fee',
            'pricings.cancel_fee', 'pricings.currency', 'currencies.precision'];

        $pricing = $this->Record->select($fields)->from('package_pricing')->
            innerJoin('pricings', 'pricings.id', '=', 'package_pricing.pricing_id', false)->
            on('currencies.company_id', '=', 'pricings.company_id', false)->
            innerJoin('currencies', 'currencies.code', '=', 'pricings.currency', false)->
            where('package_pricing.package_id', '=', $package_id)->
            order(['period' => 'ASC', 'term' => 'ASC'])->fetchAll();

        // Check if we need to increase the precision of the current price to avoid losing decimals
        foreach ($pricing as &$price) {
            if (substr((string)$price->price, -2) !== '00') {
                $price->precision = 4;
            }
        }

        // Check if the pricing is currently in use
        foreach ($pricing as &$price) {
            $services = $this->Record->select()
                ->from('services')
                ->where('services.pricing_id', '=', $price->id)
                ->where('services.status', '!=', 'canceled')
                ->fetchAll();
            $price->in_use = !empty($services);
        }

        return $pricing;
    }

    /**
     * Fetches a single pricing, including its package's taxable status
     *
     * @param int $package_pricing_id The ID of the package pricing to fetch
     * @return mixed A stdClass object representing the package pricing, false if no such package pricing exists
     */
    private function getAPackagePricing($package_pricing_id)
    {
        Loader::loadHelpers($this, ['CurrencyFormat']);

        $fields = ['package_pricing.id', 'package_pricing.pricing_id', 'package_pricing.package_id', 'pricings.term',
            'pricings.period', 'pricings.price', 'pricings.price_renews', 'pricings.price_transfer', 'pricings.setup_fee',
            'pricings.cancel_fee', 'pricings.currency', 'currencies.precision',
            'packages.taxable'];

        $pricing = $this->Record->select($fields)->from('package_pricing')->
            innerJoin('pricings', 'pricings.id', '=', 'package_pricing.pricing_id', false)->
            innerJoin('packages', 'packages.id', '=', 'package_pricing.package_id', false)->
            on('currencies.company_id', '=', 'pricings.company_id', false)->
            innerJoin('currencies', 'currencies.code', '=', 'pricings.currency', false)->
            where('package_pricing.id', '=', $package_pricing_id)->fetch();

        // Check if we need to increase the precision of the current price to avoid losing decimals
        if (substr((string)$pricing->price, -2) !== '00') {
            $pricing->precision = 4;
        }

        return $pricing;
    }

    /**
     * Adds a pricing and package pricing record
     *
     * @param int package_id The package ID to add pricing for
     * @param array $vars An array of pricing info including:
     *
     *  - company_id The company ID to add pricing for
     *  - term The term as an integer 1-65535 (optional, default 1)
     *  - period The period, 'day', 'week', 'month', 'year', 'onetime' (optional, default 'month')
     *  - price The price of this term (optional, default 0.00)
     *  - setup_fee The setup fee for this package (optional, default 0.00)
     *  - cancel_fee The cancelation fee for this package (optional, default 0.00)
     *  - currency The ISO 4217 currency code for this pricing (optional, default USD)
     * @return int The package pricing ID
     */
    private function addPackagePricing($package_id, array $vars)
    {
        if (!isset($this->Pricings)) {
            Loader::loadModels($this, ['Pricings']);
        }

        $pricing_id = $this->Pricings->add($vars);

        if (($errors = $this->Pricings->errors())) {
            $this->Input->setErrors($errors);
            return;
        }

        if ($pricing_id) {
            $this->Record->insert(
                'package_pricing',
                ['package_id' => $package_id, 'pricing_id' => $pricing_id]
            );
            return $this->Record->lastInsertId();
        }
    }

    /**
     * Edit package pricing, removes any pricing with a missing term
     *
     * @param int $package_pricing_id The package pricing ID to update
     * @param array $vars An array of pricing info including:
     *
     *  - package_id The package ID to add pricing for
     *  - company_id The company ID to add pricing for
     *  - term The term as an integer 1-65535 (optional, default 1)
     *  - period The period, 'day', 'week', 'month', 'year', 'onetime' (optional, default 'month')
     *  - price The price of this term (optional, default 0.00)
     *  - price_renews The renewal price of this term (optional, default null)
     *  - price_transfer The transfer price of this term (optional, default null)
     *  - setup_fee The setup fee for this package (optional, default 0.00)
     *  - cancel_fee The cancelation fee for this package (optional, default 0.00)
     *  - currency The ISO 4217 currency code for this pricing (optional, default USD)
     */
    private function editPackagePricing($package_pricing_id, array $vars)
    {
        if (!isset($this->Pricings)) {
            Loader::loadModels($this, ['Pricings']);
        }

        $package_pricing = $this->getAPackagePricing($package_pricing_id);

        if (isset($vars['term'])) {
            $fields = ['term', 'period', 'price', 'price_renews', 'price_transfer', 'setup_fee', 'cancel_fee', 'currency'];
            $this->Pricings->edit($package_pricing->pricing_id, array_intersect_key($vars, array_flip($fields)));
        } else {
            // Remove the package pricing, term not set
            $this->Pricings->delete($package_pricing->pricing_id);

            $this->Record->where('id', '=', $package_pricing->id)->
                where('package_id', '=', $package_pricing->package_id)->
                from('package_pricing')->delete();
        }
    }

    /**
     * Fetches all package meta data for the given package
     *
     * @param int $package_id The package ID to fetch meta data for
     * @return array An array of stdClass objects representing package meta data
     */
    private function getPackageMeta($package_id)
    {
        $fields = ['key', 'value', 'serialized', 'encrypted'];
        $this->Record->select($fields)->from('package_meta')->
            where('package_id', '=', $package_id);

        return $this->formatRawMeta($this->Record->fetchAll());
    }

    /**
     * Fetches all package group assignment for the given package
     *
     * @param int $package_id The package ID to fetch pricing for
     * @return array An array of stdClass objects representing package groups
     */
    private function getPackageGroups($package_id)
    {
        $package_groups = $this->Record->select([
                'package_groups.id',
                'package_groups.type',
                'package_groups.company_id',
                'package_groups.allow_upgrades'
            ])->
            from('package_group')->
            innerJoin('package_groups', 'package_groups.id', '=', 'package_group.package_group_id', false)->
            where('package_group.package_id', '=', $package_id)->fetchAll();

        // Append names and descriptions to all fetched package groups
        foreach ($package_groups as &$package_group) {
            $package_group = $this->appendGroupNamesDescriptions($package_group);
        }

        return $package_groups;
    }

    /**
     * Fetches all package module groups assignment for the given package
     *
     * @param int $package_id The package ID to fetch the module groups for
     * @return array An array of stdClass objects representing package module groups
     */
    private function getPackageModuleGroups($package_id)
    {
        $package_module_groups = $this->Record->select([
            'module_groups.*'
        ])->
            from('package_module_groups')->
            innerJoin('module_groups', 'module_groups.id', '=', 'package_module_groups.module_group_id', false)->
            where('package_module_groups.package_id', '=', $package_id)->fetchAll();

        return $package_module_groups;
    }

    /**
     * Fetches all package option groups assigned to the given package
     *
     * @param int $package_id The package ID to fetch option groups for
     * @return array An array of stdClass objects representing package option groups
     */
    private function getPackageOptionGroups($package_id)
    {
        $fields = ['package_option_groups.id', 'package_option_groups.name', 'package_option_groups.description'];
        return $this->Record->select($fields)
            ->from('package_option')
            ->innerJoin(
                'package_option_groups',
                'package_option_groups.id',
                '=',
                'package_option.option_group_id',
                false
            )
            ->where('package_option.package_id', '=', $package_id)
            ->order(['package_option.order' => 'ASC'])
            ->fetchAll();
    }

    /**
     * Partially constructs the query required by both Packages::getList() and
     * Packages::getListCount()
     *
     * @param array $filters A list of package filters including:
     *
     *  - status The status type of packages to retrieve
     *      ('active', 'inactive', 'restricted', default null for all)
     *  - name The name or part of the name of the packages to fetch
     *  - module_id The module ID to filter packages on
     *  - package_group_id The package group ID on which to filter packages
     *  - hidden Whether or nor to include the hidden packages
     * @return Record The partially constructed query Record object
     */
    private function getPackages(array $filters = [])
    {
        $fields = array_merge($this->getSelectFieldList(), ['modules.name' => 'module_name']);

        $this->Record->select($fields)
            ->appendValues([$this->replacement_keys['packages']['ID_VALUE_TAG']])
            ->from('packages')
            ->on('package_names.lang', '=', Configure::get('Blesta.language'))
            ->leftJoin('package_names', 'package_names.package_id', '=', 'packages.id', false)
            ->leftJoin('package_pricing', 'package_pricing.package_id', '=', 'packages.id', false)
            ->leftJoin('modules', 'modules.id', '=', 'packages.module_id', false);

        // Set a specific package status
        if (!empty($filters['status'])) {
            $this->Record->where('packages.status', '=', $filters['status']);
        }

        if (!empty($filters['name'])) {
            $this->Record->where('package_names.name', 'LIKE', '%' . $filters['name'] . '%');
        }

        if (!empty($filters['module_id'])) {
            $this->Record->where('packages.module_id', '=', $filters['module_id']);
        }
        
        if (!empty($filters['package_group_id'])) {
            $this->Record->leftJoin('package_group', 'package_group.package_id', '=', 'packages.id', false)->
                where('package_group.package_group_id', '=', $filters['package_group_id']);
        }

        if (!(!empty($filters['hidden']) && (bool)$filters['hidden'])) {
            $this->Record->where('packages.hidden', '=', 0);
        }

        if (!empty($filters['assigned_services'])) {
            switch ($filters['assigned_services']) {
                case 'any':
                    // Require that at least one service be assigned to the package
                    $this->Record->innerJoin('services', 'services.pricing_id', '=', 'package_pricing.id', false);
                    break;
                case 'canceled':
                    // Require that at least one canceled service be assigned to the package and no non-cancelled ones
                    $this->Record->on('canceled_services.status', '=', 'canceled')
                        ->innerJoin(
                            ['services' => 'canceled_services'],
                            'canceled_services.pricing_id',
                            '=',
                            'package_pricing.id',
                            false
                        )
                        ->on('services.status', '!=', 'canceled')
                        ->leftJoin('services', 'services.pricing_id', '=', 'package_pricing.id', false)
                        ->where('services.id', '=', null);
                    break;
                case 'none':
                    // Require that no services be assigned to the package
                    $this->Record->leftJoin('services', 'services.pricing_id', '=', 'package_pricing.id', false)
                        ->where('services.id', '=', null);
                    break;
            }
        }

        return $this->Record->group('packages.id');
    }

    /**
     * Removes all existing groups set for the given package, replaces them with
     * the given list of groups
     *
     * @param int $package_id The package to replace groups on
     * @param array $groups A numerically-indexed array of group IDs to add to the package (optional)
     */
    private function setGroups($package_id, $groups = null)
    {
        // Fetch existing group assignments to maintain their order
        $order = [];
        if (!empty($groups) && is_array($groups)) {
            $package_groups = $this->Record->select()
                ->from('package_group')
                ->where('package_id', '=', $package_id)
                ->fetchAll();

            foreach ($package_groups as $group) {
                $order[$group->package_group_id] = $group->order;
            }
        }

        // Remove all existing groups
        $this->Record->from('package_group')->
            where('package_id', '=', $package_id)->delete();

        // Add all given groups
        if (!empty($groups) && is_array($groups)) {
            for ($i = 0, $total = count($groups); $i < $total; $i++) {
                $vars = [
                    'package_id' => $package_id,
                    'package_group_id' => $groups[$i]
                ];

                // Reset existing package group order
                if (array_key_exists($groups[$i], $order)) {
                    $vars['order'] = $order[$groups[$i]];
                }

                $this->Record->insert('package_group', $vars);
            }
        }
    }

    /**
     * Removes all existing module groups set for the given package, replaces them with
     * the given list of module groups
     *
     * @param int $package_id The package to replace module groups on
     * @param array $module_groups A numerically-indexed array of module group IDs to add to the package (optional)
     */
    private function setModuleGroups($package_id, $module_groups = null)
    {
        // Remove all existing groups
        $this->Record->from('package_module_groups')->
            where('package_id', '=', $package_id)->delete();

        // Add all given groups
        if (!empty($module_groups) && is_array($module_groups)) {
            for ($i = 0, $total = count($module_groups); $i < $total; $i++) {
                $vars = [
                    'package_id' => $package_id,
                    'module_group_id' => $module_groups[$i]
                ];

                $this->Record->insert('package_module_groups', $vars);
            }
        }
    }

    /**
     * Assigns the given package option group to the given package
     *
     * @param int $package_id The ID of the package to be assigned the option group
     * @param array $option_groups A numerically-indexed array of package option groups to assign
     */
    private function setOptionGroups($package_id, array $option_groups)
    {
        $order = 0;
        foreach ($option_groups as $option_group_id) {
            $vars = ['package_id' => $package_id, 'option_group_id' => $option_group_id, 'order' => $order++];
            $this->Record->duplicate('order', '=', $vars['order'])->insert('package_option', $vars);
        }
    }

    /**
     * Removes all package option groups assigned to this package
     *
     * @param int $package_id The ID of the package
     */
    private function removeOptionGroups($package_id)
    {
        $this->Record->from('package_option')->where('package_id', '=', $package_id)->delete();
    }

    /**
     * Assigns the given plugin to the given package
     *
     * @param int $package_id The ID of the package
     * @param array $plugins A numerically-indexed array of plugins to assign
     */
    private function addPlugins($package_id, array $plugins)
    {
        foreach ($plugins as $plugin_id) {
            $vars = ['package_id' => $package_id, 'plugin_id' => $plugin_id];
            $this->Record->duplicate('plugin_id', '=', $plugin_id)->insert('package_plugins', $vars);
        }
    }

    /**
     * Removes all plugins assigned to this package
     *
     * @param int $package_id The ID of the package
     */
    private function removePlugins($package_id)
    {
        $this->Record->from('package_plugins')->where('package_id', '=', $package_id)->delete();
    }

    /**
     * Retrieves a list of plugin IDs associated with this package
     *
     * @param int $package_id The ID of the package whose plugins to fetch
     * @return array An array of package plugins associated with this package
     */
    private function getPackagePlugins($package_id)
    {
        return $this->Record->select()
            ->from('package_plugins')
            ->where('package_id', '=', $package_id)
            ->fetchAll();
    }

    /**
     * Updates the meta data for the given package, removing all existing data and replacing it with the given data
     *
     * @param int $package_id The ID of the package to update
     * @param array $vars A numerically indexed array of meta data containing:
     *
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     */
    private function setMeta($package_id, array $vars)
    {

        // Delete all old meta data for this package
        $this->Record->from('package_meta')->
            where('package_id', '=', $package_id)->delete();

        // Add all new module data
        $fields = ['package_id', 'key', 'value', 'serialized', 'encrypted'];
        $num_vars = count($vars);
        for ($i = 0; $i < $num_vars; $i++) {
            $serialize = !is_scalar($vars[$i]['value']);
            $vars[$i]['package_id'] = $package_id;
            $vars[$i]['serialized'] = (int) $serialize;
            $vars[$i]['value'] = $serialize ? serialize($vars[$i]['value']) : $vars[$i]['value'];

            if (isset($vars[$i]['encrypted']) && $vars[$i]['encrypted'] == '1') {
                $vars[$i]['value'] = $this->systemEncrypt($vars[$i]['value']);
            }

            $this->Record->insert('package_meta', $vars[$i], $fields);
        }
    }

    /**
     * Formats an array of raw meta stdClass objects into a stdClass
     * object whose public member variables represent meta keys and whose values
     * are automatically decrypted and unserialized as necessary.
     *
     * @param array $raw_meta An array of stdClass objects representing meta data
     */
    private function formatRawMeta($raw_meta)
    {
        $meta = new stdClass();
        // Decrypt data as necessary
        foreach ($raw_meta as &$data) {
            if ($data->encrypted > 0) {
                $data->value = $this->systemDecrypt($data->value);
            }

            if ($data->serialized > 0) {
                $data->value = unserialize($data->value);
            }

            $meta->{$data->key} = $data->value;
        }
        return $meta;
    }

    /**
     * Checks whether a service exists for a specific package ID
     *
     * @param int $package_id The package ID to check
     * @param bool $exclude_canceled True to exclude canceled services (optional, default false)
     * @return bool True if a service exists for this package, false otherwise
     */
    public function validateServiceExists($package_id, $exclude_canceled = false)
    {
        $count = $this->Record->select('services.id')->from('package_pricing')->
            innerJoin('services', 'services.pricing_id', '=', 'package_pricing.id', false)->
            where('package_pricing.package_id', '=', $package_id);

        if ($exclude_canceled) {
            $count->where('services.status', '!=', 'canceled');
        }

        $count = $count->numResults();

        if ($count > 0) {
            return true;
        }
        return false;
    }

    /**
     * Validates whether the services belonging to a specific package has children NOT of the given status
     *
     * @param int $package_id The package ID to check
     * @param string $status The status of children services to ignore
     *  (e.g. "canceled") (optional, default null to not ignore any child services)
     * @return bool True if the services belonging to this package has children not of the given status, false otherwise
     */
    public function validateHasChildren($package_id, $status = null)
    {
        $canceled_services = $this->getPackageServices($package_id);

        foreach ($canceled_services as $service) {
            $this->Record->select()
                ->from('services')
                ->where('parent_service_id', '=', $service->id);

            if ($status) {
                $this->Record->where('status', '!=', $status);
            }

            if ($this->Record->numResults() > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all the services belonging to a specific package
     *
     * @param int $package_id The package ID to fetch the services
     * @return array A numerically indexed array of objects containing the services
     */
    private function getPackageServices($package_id)
    {
        return $this->Record->select(['services.*'])
            ->from('services')
            ->innerJoin('package_pricing', 'package_pricing.id', '=', 'services.pricing_id', false)
            ->innerJoin('packages', 'packages.id', '=', 'package_pricing.package_id', false)
            ->where('packages.id', '=', $package_id)
            ->fetchAll();
    }

    /**
     * Validates the package 'status' field type
     *
     * @param string $status The status type
     * @return bool True if validated, false otherwise
     */
    public function validateStatus($status)
    {
        switch ($status) {
            case 'active':
            case 'inactive':
            case 'restricted':
                return true;
        }
        return false;
    }

    /**
     * Validates that the term is valid for the period. That is, the term must be > 0
     * if the period is something other than "onetime".
     *
     * @param int $term The Term to validate
     * @param string $period The period to validate the term against
     * @return bool True if validated, false otherwise
     */
    public function validateTerm($term, $period)
    {
        if ($period == 'onetime') {
            return true;
        }
        return $term > 0;
    }

    /**
     * Validates the pricing 'period' field type
     *
     * @param string $period The period type
     * @return bool True if validated, false otherwise
     */
    public function validatePeriod($period)
    {
        $periods = $this->getPricingPeriods();

        if (isset($periods[$period])) {
            return true;
        }
        return false;
    }

    /**
     * Validates that the given group belongs to the given company ID
     *
     * @param int $group_id The ID of the group to test
     * @param int $company_id The ID of the company to validate exists for the given group
     * @return bool True if validated, false otherwise
     */
    public function validateGroup($group_id, $company_id)
    {
        return (boolean) $this->Record->select(['id'])->
            from('package_groups')->where('company_id', '=', $company_id)->fetch();
    }

    /**
     * Validates that the given group is valid
     *
     * @param int $option_group_id The ID of the package option group to validate
     * @param int $company_id The ID of the company whose option group to validate
     * @return bool True if the package option group is valid, or false otherwise
     */
    public function validateOptionGroup($option_group_id, $company_id)
    {
        // Group may not be given
        if (empty($option_group_id)) {
            return true;
        }

        // Check whether this is a valid option group
        $count = $this->Record->select(['id'])->from('package_option_groups')->
            where('id', '=', $option_group_id)->where('company_id', '=', $company_id)->numResults();

        return ($count > 0);
    }

    /**
     * Validates that the given currency is valid for adding/editing a package
     *
     * @param string $currency The ISO 4217 currency code for this pricing option
     * @param string $term The term for this pricing option
     * @param string $period The period type to validate the currency for
     * @return bool True if the currency is valid, or false otherwise
     */
    public function validateCurrency($currency, $term, $period)
    {
        // Currency is required if term is given (otherwise it could be deleted)
        if (!empty($term) || $period == 'onetime') {
            return (preg_match('/^(.){3}$/', $currency) ? true : false);
        }

        return true;
    }

    /**
     * Validate that the given email content parses template parsing
     *
     * @param array A numerically-indexed array of template data to parse, containing:
     *
     *  - html The HTML version of the email content
     *  - text The text version of the email content
     */
    public function validateParse($email_content)
    {
        $parser_options_text = Configure::get('Blesta.parser_options');

        if (is_array($email_content)) {
            // Check each email template language's HTML/Text contents
            foreach ($email_content as $email) {
                $html = (isset($email['html']) ? $email['html'] : '');
                $text = (isset($email['text']) ? $email['text'] : '');

                try {
                    H2o::parseString($html, $parser_options_text)->render();
                    H2o::parseString($text, $parser_options_text)->render();
                } catch (H2o_Error $e) {
                    $this->parseError = $e->getMessage();
                    return false;
                } catch (Exception $e) {
                    // Don't care about any other exception
                }
            }
        }
        return true;
    }

    /**
     * Validates whether the given package can be changed to the given module
     *
     * @param int $module_id The new module ID
     * @param int $package_id The ID of the package
     * @return bool True if the package can be changed to the module, or false otherwise
     */
    public function validateModuleChange($module_id, $package_id)
    {
        // The module cannot be changed for this package if the package has services
        if (($package = $this->get($package_id))
            && $package->module_id != $module_id
            && $this->validateServiceExists($package_id)
        ) {
            return false;
        }

        return true;
    }

    /**
     * Sets the parse error in the set of errors
     */
    private function setParseError()
    {
        // Swap the error with the actual parse error
        $errors = $this->Input->errors();
        if (isset($errors['email_content']['parse'])) {
            $errors['email_content']['parse'] = $this->_('Packages.!error.email_content.parse', $this->parseError);
        }

        if ($errors) {
            $this->Input->setErrors($errors);
        }
    }

    /**
     * Formats the pricing term
     *
     * @param int $term The term length
     * @param string $period The period of this term
     * @return mixed The term formatted in accordance to the period, if possible
     */
    public function formatPricingTerm($term, $period)
    {
        if ($period == 'onetime') {
            return 0;
        }
        return $term;
    }

    /**
     * Checks if the pricing it's in use
     *
     * @param mixed $value The value of the variable to validate
     * @param string $var The name of the variable to validate
     * @param int $pricing_id
     */
    public function checkInUse($value, $var, $pricing_id = null)
    {
        Loader::loadModels($this, ['Pricings']);

        // If pricing ID is null, we are adding a new pricing
        if (is_null($pricing_id)) {
            return true;
        }

        // Get the current value of the variable to validate
        $package_pricing = $this->Record->select()
            ->from('package_pricing')
            ->where('package_pricing.id', '=', $pricing_id)
            ->fetch();

        return $this->Pricings->checkInUse($value, $var, $package_pricing->pricing_id ?? null);
    }

    /**
     * Fetches the rules for adding/editing a package
     *
     * @param bool $edit True to fetch the edit rules, or false for the add rules
     * @param int $package_id The ID of the package being updated (on edit)
     * @return array The package rules
     */
    private function getRules($vars, $edit = false, $package_id = null)
    {
        $company_id = (isset($vars['company_id']) ? $vars['company_id'] : null);

        $rules = [
            // Package rules
            'module_id' => [
                'exists' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateExists'], 'id', 'modules'],
                    'message' => $this->_('Packages.!error.module_id.exists')
                ]
            ],
            'names' => [
                'format' => [
                    'if_set' => $edit,
                    'rule' => function ($names) {
                        // The 'names' value must be in the correct format
                        if (!is_array($names)) {
                            return false;
                        }

                        // The 'name' and 'lang' keys must exist
                        foreach ($names as $name) {
                            if (!array_key_exists('name', $name) || !array_key_exists('lang', $name)) {
                                return false;
                            }
                        }

                        return true;
                    },
                    'message' => $this->_('Packages.!error.names.format'),
                    // Don't validate the subsequent rules if the formatting is invalid
                    'final' => true
                ],
                'empty_name' => [
                    'if_set' => $edit,
                    'rule' => function ($names) {
                        // The above rule is a 'final' rule, so we can assume the formatting is valid
                        // Verify all name values are not empty
                        foreach ($names as $name) {
                            if (empty($name['name'])) {
                                return false;
                            }
                        }

                        return true;
                    },
                    'message' => $this->_('Packages.!error.names.empty_name')
                ],
                'empty_lang' => [
                    'if_set' => $edit,
                    'rule' => function ($names) {
                        // The above rule is a 'final' rule, so we can assume the formatting is valid
                        // Verify all lang values are not empty
                        foreach ($names as $name) {
                            if (empty($name['lang'])) {
                                return false;
                            }
                        }

                        return true;
                    },
                    'message' => $this->_('Packages.!error.names.empty_lang')
                ]
            ],
            'descriptions' => [
                'format' => [
                    'if_set' => true,
                    'rule' => function ($descriptions) {
                        // The 'descriptions' value must be in the correct format
                        if (!is_array($descriptions)) {
                            return false;
                        }

                        // The 'lang' key must exist, and the 'text' and 'html' keys must be scalar if provided
                        foreach ($descriptions as $description) {
                            if (!array_key_exists('lang', $description)
                                || (array_key_exists('html', $description) && !is_scalar($description['html']))
                                || (array_key_exists('text', $description) && !is_scalar($description['text']))
                            ) {
                                return false;
                            }
                        }

                        return true;
                    },
                    'message' => $this->_('Packages.!error.descriptions.format'),
                    // Don't validate the subsequent rules if the formatting is invalid
                    'final' => true
                ],
                'empty_lang' => [
                    'if_set' => true,
                    'rule' => function ($descriptions) {
                        // The above rule is a 'final' rule, so we can assume the formatting is valid
                        // Verify all lang values are not empty
                        foreach ($descriptions as $description) {
                            if (empty($description['lang'])) {
                                return false;
                            }
                        }

                        return true;
                    },
                    'message' => $this->_('Packages.!error.descriptions.empty_lang')
                ]
            ],
            'qty' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['matches', '/^([0-9]+)$/'],
                    'message' => $this->_('Packages.!error.qty.format')
                ]
            ],
            'client_qty' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['matches', '/^([0-9]+)$/'],
                    'message' => $this->_('Packages.!error.client_qty.format')
                ]
            ],
            'option_groups[]' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateOptionGroup'], $company_id],
                    'message' => $this->_('Packages.!error.option_groups[].valid')
                ]
            ],
            'plugins[]' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => function ($plugin_id) use ($company_id) {
                        // Check whether this is a valid plugin
                        $count = $this->Record->select(['id'])
                            ->from('plugins')
                            ->where('id', '=', $plugin_id)
                            ->where('company_id', '=', $company_id)
                            ->numResults();

                        return ($count > 0);
                    },
                    'message' => $this->_('Packages.!error.plugins[].valid')
                ]
            ],
            'module_row' => [
                'format' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateExists'], 'id', 'module_rows'],
                    'message' => $this->_('Packages.!error.module_row.format')
                ]
            ],
            'module_group' => [
                'format' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateExists'], 'id', 'module_groups'],
                    'message' => $this->_('Packages.!error.module_group.format')
                ]
            ],
            'module_group_client' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['in_array', ['0', '1']],
                    'message' => $this->_('Packages.!error.module_group_client.valid')
                ]
            ],
            'taxable' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'is_numeric',
                    'message' => $this->_('Packages.!error.taxable.format')
                ],
                'length' => [
                    'if_set' => true,
                    'rule' => ['maxLength', 1],
                    'message' => $this->_('Packages.!error.taxable.length')
                ]
            ],
            'single_term' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['in_array', ['0', '1']],
                    'message' => $this->_('Packages.!error.single_term.valid')
                ]
            ],
            'status' => [
                'format' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateStatus']],
                    'message' => $this->_('Packages.!error.status.format')
                ]
            ],
            'hidden' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['in_array', [0, 1]],
                    'message' => $this->_('Packages.!error.hidden.format')
                ]
            ],
            'company_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'companies'],
                    'message' => $this->_('Packages.!error.company_id.exists')
                ]
            ],
            'prorata_day' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['between', 1, 28],
                    'message' => $this->_('Packages.!error.prorata_day.format')
                ]
            ],
            'prorata_cutoff' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['between', 1, 28],
                    'message' => $this->_('Packages.!error.prorata_cutoff.format')
                ]
            ],
            'upgrades_use_renewal' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['in_array', ['0', '1']],
                    'message' => $this->_('Packages.!error.upgrades_use_renewal.valid')
                ]
            ],
            'override_price' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['in_array', ['0', '1']],
                    'message' => $this->_('Packages.!error.override_price.valid')
                ]
            ],
            // Package Email rules
            'email_content[][lang]' => [
                'empty' => [
                    'if_set' => $edit,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('Packages.!error.email_content[][lang].empty')
                ],
                'length' => [
                    'if_set' => $edit,
                    'rule' => ['maxLength', 5],
                    'message' => $this->_('Packages.!error.email_content[][lang].length')
                ]
            ],
            'email_content' => [
                'parse' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateParse']],
                    'message' => $this->_('Packages.!error.email_content.parse')
                ]
            ],
            // Package Pricing rules
            'pricing[][currency]' => [
                'format' => [
                    'rule' => [
                        [$this, 'validateCurrency'],
                        ['_linked' => 'pricing[][term]'],
                        ['_linked' => 'pricing[][period]']
                    ],
                    'message' => $this->_('Packages.!error.pricing[][currency].format')
                ],
                'in_use' => [
                    'if_set' => true,
                    'rule' => [[$this, 'checkInUse'], 'currency', ['_linked' => 'pricing[][id]']],
                    'message' => $this->_('Packages.!error.pricing[][currency].in_use')
                ]
            ],
            'pricing[][term]' => [
                'format' => [
                    'if_set' => true,
                    'pre_format' => [[$this, 'formatPricingTerm'], ['_linked' => 'pricing[][period]']],
                    'rule' => 'is_numeric',
                    'message' => $this->_('Packages.!error.pricing[][term].format')
                ],
                'length' => [
                    'if_set' => true,
                    'rule' => ['maxLength', 5],
                    'message' => $this->_('Packages.!error.pricing[][term].length')
                ],
                'valid' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateTerm'], ['_linked' => 'pricing[][period]']],
                    'message' => $this->_('Packages.!error.pricing[][term].valid')
                ],
                'in_use' => [
                    'if_set' => true,
                    'rule' => [[$this, 'checkInUse'], 'term', ['_linked' => 'pricing[][id]']],
                    'message' => $this->_('Packages.!error.pricing[][term].in_use')
                ]
            ],
            'pricing[][period]' => [
                'format' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validatePeriod']],
                    'message' => $this->_('Packages.!error.pricing[][period].format')
                ],
                'in_use' => [
                    'if_set' => true,
                    'rule' => [[$this, 'checkInUse'], 'period', ['_linked' => 'pricing[][id]']],
                    'message' => $this->_('Packages.!error.pricing[][period].in_use')
                ]
            ],
            'pricing[][price]' => [
                'format' => [
                    'if_set' => true,
                    'pre_format' => [[$this, 'currencyToDecimal'], ['_linked' => 'pricing[][currency]'], 4],
                    'rule' => 'is_numeric',
                    'message' => $this->_('Packages.!error.pricing[][price].format')
                ]
            ],
            'pricing[][price_renews]' => [
                'format' => [
                    'if_set' => true,
                    'pre_format' => [[$this, 'currencyToDecimal'], ['_linked' => 'pricing[][currency]'], 4],
                    'rule' => 'is_numeric',
                    'message' => $this->_('Packages.!error.pricing[][price_renews].format')
                ],
                'valid' => [
                    'if_set' => true,
                    'rule' => [
                        function($price, $period) {
                            // The renewal price may not be set for the onetime period
                            return ($period != 'onetime' || $price === null);
                        },
                        ['_linked' => 'pricing[][period]']
                    ],
                    'message' => $this->_('Packages.!error.pricing[][price_renews].valid')
                ]
            ],
            'pricing[][price_transfer]' => [
                'format' => [
                    'if_set' => true,
                    'pre_format' => [[$this, 'currencyToDecimal'], ['_linked' => 'pricing[][currency]'], 4],
                    'rule' => 'is_numeric',
                    'message' => $this->_('Packages.!error.pricing[][price_transfer].format')
                ]
            ],
            'pricing[][setup_fee]' => [
                'format' => [
                    'if_set' => true,
                    'pre_format' => [[$this, 'currencyToDecimal'], ['_linked' => 'pricing[][currency]'], 4],
                    'rule' => 'is_numeric',
                    'message' => $this->_('Packages.!error.pricing[][setup_fee].format')
                ]
            ],
            'pricing[][cancel_fee]' => [
                'format' => [
                    'if_set' => true,
                    'pre_format' => [[$this, 'currencyToDecimal'], ['_linked' => 'pricing[][currency]'], 4],
                    'rule' => 'is_numeric',
                    'message' => $this->_('Packages.!error.pricing[][cancel_fee].format')
                ]
            ],
            'groups[]' => [
                'exists' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateExists'], 'id', 'package_groups'],
                    'message' => $this->_('Packages.!error.groups[].exists')
                ],
                'valid' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateGroup'], isset($vars['company_id']) ? $vars['company_id'] : null],
                    'message' => $this->_('Packages.!error.groups[].valid')
                ]
            ]
        ];

        // Set edit rules
        if ($edit) {
            $rules['pricing[][id]']['format'] = [
                'if_set' => true,
                'rule' => [[$this, 'validateExists'], 'id', 'package_pricing'],
                'message' => $this->_('Packages.!error.pricing[][id].format')
            ];

            // Pricing cannot be removed if services exist that belong to it
            $rules['pricing[][id]']['deletable'] = [
                'if_set' => true,
                'rule' => function ($pricing_id) use (&$vars, $package_id) {
                    $deleteable = true;

                    // Build new pricings list
                    $new_pricings = [];
                    foreach ($vars['pricing'] as $pricing) {
                        if (isset($pricing['id'])
                            && (!empty($pricing['term']) || $pricing['period'] === 'onetime')
                            && is_numeric($pricing['id'])
                        ) {
                            $new_pricings[] = $pricing['id'];
                        }
                    }

                    // Build old pricings list
                    $old_pricings = [];
                    $terms = $this->Record->select(['id'])
                        ->from('package_pricing')
                        ->where('package_id', '=', $package_id)
                        ->fetchAll();

                    foreach ($terms as $old_pricing) {
                        $old_pricings[] = $old_pricing->id;
                    }

                    // If both arrays are equal, return true
                    if ($new_pricings === $old_pricings || empty($old_pricings)) {
                        $deleteable = true;
                    }

                    // Get the difference between the new and old pricings
                    $diff = array_diff($old_pricings, $new_pricings);
                    foreach ($diff as $diff_pricing) {
                        if ($diff_pricing == $pricing_id) {
                            $count = $this->Record->select()
                                ->from('services')
                                ->where('pricing_id', '=', $diff_pricing)
                                ->numResults();

                            if ($count > 0) {
                                $deleteable = false;
                            }
                        }
                    }

                    // Restore pricing if isn't deletable
                    if (!$deleteable) {
                        foreach ($vars['pricing'] as &$pricing) {
                            if (isset($pricing['id']) && empty($pricing['term']) && $pricing['id'] == $pricing_id) {
                                $db_pricing = $this->Record->select(['pricings.*'])
                                    ->from('pricings')
                                    ->innerJoin('package_pricing', 'package_pricing.pricing_id', '=', 'pricings.id', false)
                                    ->where('package_pricing.id', '=', $pricing_id)
                                    ->fetch();
                                $pricing = array_merge((array) $db_pricing, ['id' => $pricing['id']]);
                            }
                        }
                    }

                    return $deleteable;
                },
                'message' => $this->_('Packages.!error.pricing[][id].deletable')
            ];

            // Module cannot be changed if services exist that belong to it
            $rules['module_id']['changed'] = [
                'if_set' => true,
                'rule' => [[$this, 'validateModuleChange'], $package_id],
                'message' => $this->_('Packages.!error.module_id.changed')
            ];
        }

        if (!isset($vars['module_row']) || $vars['module_row'] == 0) {
            unset($rules['module_row']);
        }

        return $rules;
    }
}
