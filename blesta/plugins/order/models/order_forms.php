<?php
/**
 * Order Form Management
 *
 * @package blesta
 * @subpackage blesta.plugins.order.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class OrderForms extends OrderModel
{
    /**
     * Returns the total number of order forms for the given company
     *
     * @param int $company_id The ID of the company to fetch order form count from
     * @param int $page The page number of results to fetch
     * @param array $order A key/value pair array of fields to order the results by
     * @param string $status The status of the order forms to fetch:
     *  - "active" Only active order forms
     *  - "inactive" Only inactive order forms
     *  - null All order forms
     * @return array An array of stdClass objects, each representing an order form
     */
    public function getList($company_id, $page = 1, array $order = ['id' => 'desc'], $status = null)
    {
        $this->Record = $this->getOrderForm($status);
        return $this->Record->where('company_id', '=', $company_id)->order($order)->
            limit($this->getPerPage(), (max(1, $page) - 1)*$this->getPerPage())->fetchAll();
    }

    /**
     * Returns the total number of order forms for the given company
     *
     * @param int $company_id The ID of the company to fetch order form count from
     * @param string $status The status of the order forms to fetch:
     *  - "active" Only active order forms
     *  - "inactive" Only inactive order forms
     *  - null All order forms
     * @return int The total number of order forms for the given company
     */
    public function getListCount($company_id, $status = null)
    {
        $this->Record = $this->getOrderForm($status);
        return $this->Record->where('company_id', '=', $company_id)->numResults();
    }

    /**
     * Returns all order forms in the system for the given company
     *
     * @param int $company_id The ID of the company to fetch order forms for
     * @param string $status The status of the order forms to fetch:
     *  - "active" Only active order forms
     *  - "inactive" Only inactive order forms
     *  - null All order forms
     * @param array $order A key/value pair array of fields to order the results by
     * @return array An array of stdClass objects, each representing an order form
     */
    public function getAll($company_id, $status, array $order = ['id' => 'desc'])
    {
        $this->Record = $this->getOrderForm($status);
        return $this->Record->where('company_id', '=', $company_id)->order($order)->
            fetchAll();
    }

    /**
     * Retrieves a list of order forms based on the given search criteria
     *
     * @param type $company_id The ID of the company to fetch order forms for
     * @param array $query Optional criteria to query on:
     *  - status string The order form status (e.g. 'active', 'inactive')
     *  - visibility string|array A single visibility or an array of visibilities
     *  - client_id int The client ID to compare package restrictions to
     * @param array $order The order to sort results in
     * @return array A list of order forms
     */
    public function search($company_id, array $query = [], array $order = ['order_forms.order' => 'asc'])
    {
        $status = !empty($query['status'])
            ? $query['status']
            : null;

        // Get all order forms with a package group that is available given the $query
        $this->Record = $this->getOrderForm($status)->
            innerJoin(
                'order_form_groups',
                'order_form_groups.order_form_id',
                '=',
                'order_forms.id',
                false
            );

        // Apply package group restrictions
        $form_subquery = $this->restrictGroups($this->Record, $query);
        $form = $form_subquery->get();
        $form_values = $form_subquery->values;
        $this->Record->reset();


        // Fetch the order form meta in a way that it can be substituted for `order_form_groups` in the
        // ::restrictGroups() method
        $order_form_meta_subquery = $this->Record->select([
                'order_form_meta.value' => 'package_group_id',
                'order_form_meta.order_form_id'
            ])->
            from('order_form_meta')->
            where('order_form_meta.key', '=', 'domain_group');
        $order_form_meta = $order_form_meta_subquery->get();
        $order_form_meta_values = $order_form_meta_subquery->values;
        $this->Record->reset();

        // Get all order forms with a domain group that is available given the $query
        $this->Record = $this->getOrderForm($status)->
            innerJoin(
                [$order_form_meta => 'order_form_groups'],
                'order_form_groups.order_form_id',
                '=',
                'order_forms.id',
                false
            )->
            appendValues($order_form_meta_values);

        // Apply package group restrictions
        $domain_form_subquery = $this->restrictGroups($this->Record, $query);
        $domain_form = $domain_form_subquery->get();
        $domain_form_values = $domain_form_subquery->values;
        $this->Record->reset();

        // Get all order forms that either have a package group that is viewable given the $query, have no package group
        // but have a domain group that is viewable given the $query, or have a package group and a domain group that
        // are both viewable given the $query
        $this->Record->select(['order_forms.*'])->from('order_forms')->
            leftJoin(
                'order_form_groups',
                'order_form_groups.order_form_id',
                '=',
                'order_forms.id',
                false
            )->
            // Join to order forms with viewable package groups
            leftJoin(
                [$form => 'nondomain_order_forms'],
                'nondomain_order_forms.id',
                '=',
                'order_forms.id',
                false
            )->
            appendValues($form_values)->
            // Join to order forms with viewable domain groups
            leftJoin(
                [$domain_form => 'domain_order_forms'],
                'domain_order_forms.id',
                '=',
                'order_forms.id',
                false
            )->
            appendValues($domain_form_values)->
            open()->
                // When the order form is not of the domain type, make sure it has a package group that is viewable
                where('order_forms.type', '!=', 'domain')->
                where('nondomain_order_forms.id', '!=', null)->
                // Otherwise, make sure it has a domain group that is viewable and either doesn't have a package group
                // or has a package group that is viewable
                open()->
                    orWhere('order_forms.type', '=', 'domain')->
                    where('domain_order_forms.id', '!=', null)->
                    open()->
                        where('order_form_groups.order_form_id', '=', null)->
                        orWhere('nondomain_order_forms.id', '!=', null)->
                    close()->
                close()->
                orWhere('order_forms.type', '=', 'registration')->
            close();


        if (array_key_exists('visibility', $query)) {
            $this->Record->where('order_forms.visibility', 'in', $query['visibility']);
        }

        if (array_key_exists('label', $query)) {
            $this->Record->where('order_forms.label', '=', $query['label']);
        }

        return $this->Record->where('order_forms.company_id', '=', $company_id)
            ->group('order_forms.id')
            ->order($order)
            ->fetchAll();
    }

    /**
     * Save the order forms in the provided order
     *
     * @param array $order_form_ids A numerically indexed array of order form IDs
     */
    public function sortOrderForms(array $order_form_ids)
    {
        for ($i = 0, $total = count($order_form_ids); $i < $total; $i++) {
            $this->Record->where('id', '=', $order_form_ids[$i])->
                update('order_forms', ['order' => $i]);
        }
    }

    /**
     * Applies filters for the given record
     *
     * @param PDOStatement $record A partial record to apply filters to
     * @param array $query Filters to apply
     * @return PDOStatement  A partial record representing a filtered list of order forms
     */
    private function restrictGroups($record, array $query = [])
    {
        if (array_key_exists('restrict_groups', $query) && !$query['restrict_groups']) {
            return $record;
        }

        $record->innerJoin(
                'package_group',
                'package_group.package_group_id',
                '=',
                'order_form_groups.package_group_id',
                false
            )->
            leftJoin('packages', 'packages.id', '=', 'package_group.package_id', false);

        if (array_key_exists('client_id', $query) && $query['client_id']) {
            $record->leftJoin(
                    'client_packages',
                    'client_packages.package_id',
                    '=',
                    'package_group.package_id',
                    false
                );
        }

        $record->open()->
            where('packages.status', '!=', 'restricted');

        if (array_key_exists('client_id', $query) && $query['client_id']) {
            $record->orWhere('client_packages.client_id', '=', $query['client_id']);
        }
        $record->close();

        return $record;
    }

    /**
     * Fetches the order form with the given label for the given company
     *
     * @param int $company_id The ID of the company to fetch on
     * @param int $label The label of the order form to fetch on
     * @param array $query Optional criteria to query on:
     *  - client_id int The client ID to compare package restrictions to
     *  - restrict_groups Whether to restrict package base on client settings
     * @return mixed A stdClass object representing the order form, false if no such order form exists
     */
    public function getByLabel($company_id, $label, array $query = [])
    {
        $this->Record = $this->getOrderForm();
        $form = $this->Record->where('order_forms.company_id', '=', $company_id)->
            where('order_forms.label', '=', $label)->fetch();

        if ($form) {
            $form->currencies = $this->getCurrencies($form->id);
            $form->gateways = $this->getGateways($form->id);
            $form->groups = $this->getGroups($form->id, $query);
            $form->meta = $this->getMeta($form->id);
        }

        return $form;
    }

    /**
     * Fetches the order form with the given ID
     *
     * @param int $order_form_id The ID of the order form to fetch on
     * @param array $query Optional criteria to query on:
     *  - client_id int The client ID to compare package restrictions to
     *  - restrict_groups Whether to restrict package base on client settings
     * @return mixed A stdClass object representing  the order form, false if no such order form exists
     */
    public function get($order_form_id, array $query = [])
    {
        $this->Record = $this->getOrderForm();
        $form = $this->Record->where('order_forms.id', '=', $order_form_id)->fetch();

        if ($form) {
            $form->currencies = $this->getCurrencies($form->id);
            $form->gateways = $this->getGateways($form->id);
            $form->groups = $this->getGroups($form->id, $query);
            $form->meta = $this->getMeta($form->id);
        }

        return $form;
    }

    /**
     * Fetches all order types available to the plugin
     *
     * @return array An array of key/value pairs where each key is the order
     *  type and each value is the order type's name
     */
    public function getTypes()
    {
        // Cache results in this object due to having to read/load from disk
        static $types = [];

        if (!empty($types)) {
            return $types;
        }

        Loader::load(PLUGINDIR . 'order' . DS . 'lib' . DS . 'order_type.php');

        $order_type_dir = PLUGINDIR . 'order' . DS . 'lib' . DS . 'order_types';

        $dh = opendir($order_type_dir);

        while (($dir = readdir($dh)) !== false) {
            if (substr($dir, 0, 1) == '.'
                || !file_exists($order_type_dir . DS . $dir . DS . 'order_type_' . $dir . '.php')) {
                continue;
            }

            // Load the order type so we can fetch its name
            Loader::load($order_type_dir . DS . $dir . DS . 'order_type_' . $dir . '.php');

            $class_name = Loader::toCamelCase('order_type_' . $dir);

            $order_type = new $class_name();
            $types[$dir] = $order_type->getName();
        }
        closedir($dh);

        return $types;
    }

    /**
     * Fetches all templates available to the plugin and the order types they support
     *
     * @return array An array of key/value pairs where each key is the template directory
     *  and each value is an object representing the order template
     */
    public function getTemplates()
    {
        // Cache results in this object due to having to read/load from disk
        static $templates = [];

        if (!empty($templates)) {
            return $templates;
        }

        $templates_dir = PLUGINDIR . 'order' . DS . 'views' . DS . 'templates' . DS;

        $dh = opendir($templates_dir);

        $i=0;
        while (($dir = readdir($dh)) !== false) {
            if (substr($dir, 0, 1) == '.' || !is_dir($templates_dir . $dir)) {
                continue;
            }

            $templates[$dir] = json_decode(file_get_contents($templates_dir . $dir . DS . 'config.json'));
            $templates[$dir]->types = $this->getSupportedTypes($dir);
        }
        closedir($dh);
        ksort($templates);

        return $templates;
    }

    /**
     * Returns all supported order type for the given template
     *
     * @param string $template The template to fetch all supported order types for
     * @return array An array of supported order types
     */
    public function getSupportedTypes($template)
    {
        $types = [];

        $types_dir = PLUGINDIR . 'order' . DS . 'views' . DS . 'templates' . DS . $template . DS . 'types';

        Configure::load('order', PLUGINDIR . 'order' . DS . 'config' . DS);
        $default_template = Configure::get('Order.order_forms.default_template');
        if (is_dir($types_dir)) {
            $types_dir = PLUGINDIR . 'order' . DS . 'views' . DS . 'templates' . DS . $default_template . DS . 'types';
        }

        $dh = opendir($types_dir);

        if ($dh) {
            // All template types support the general type
            $types[] = 'general';

            // Read all order types supported by this template
            while (($type = readdir($dh)) !== false) {
                if (substr($type, 0, 1) == '.' || !is_dir($types_dir . DS . $type)) {
                    continue;
                }
                $types[] = $type;
            }
            closedir($dh);
        }
        return $types;
    }

    /**
     * Add an order form
     *
     * @param array $vars An array of order form data including:
     *  - company_id (optional, defaults to current company ID)
     *  - label The label used to access the order form
     *  - name The name of the order form
     *  - description The description of the order form
     *  - template The template to use for the order form
     *  - template_style The template style to use for the order form
     *  - type The type of order form
     *  - client_group_id The default client group to assign clients to when ordering from this order form
     *  - manual_review Whether or not to require all orders placed to be manually reviewed (default 0)
     *  - allow_coupons Whether or not to allow coupons (default 0)
     *  - require_ssl Whether or not to force secure connection (i.e. HTTPS) (default 0)
     *  - require_captcha Whether or not to force captcha (default 0)
     *  - require_tos Whether or not to require Terms of Service agreement (default 0)
     *  - tos_url The URL to the terms of service agreement (optional)
     *  - abandoned_cart_first The number of days after an unpaid order is placed to send the first reminder email (optional)
     *  - abandoned_cart_second The number of days after an unpaid order is placed to send the second reminder email (optional)
     *  - abandoned_cart_third The number of days after an unpaid order is placed to send the third reminder email (optional)
     *  - abandoned_cart_cancellation The number of days after an unpaid order is placed to cancel the order (optional)
     *  - inactive_after_cancellation Whether or not to mark a client as inactive after cancellation if this
     *      order/service is their first one (optional)
     *  - status The status of the order form (active/inactive default active)
     *  - visibility The visbility control for the order form (public, shared, client)
     *  - meta An array of key/value pairs to assign to this order form (optional dependent upon the order type)
     *  - groups An array of package group IDs to assign to this order form (optional dependent upon the order type)
     *  - gateways An array of gateway ID to assign to this order form (optional dependent upon the order type)
     *  - currencies An array of ISO 4217 currency codes to assign to this order form
     *      (optional dependent upon the order type)
     * @return int The ID of the order form that was created, void on error
     */
    public function add(array $vars)
    {
        Loader::loadModels($this, ['Order.OrderSettings']);

        if (!isset($vars['company_id'])) {
            $vars['company_id'] = Configure::get('Blesta.company_id');
        }

        $vars['date_added'] = date('c');

        $order_type = $this->loadOrderType($vars['type']);

        // Run order form through order type settings
        $vars = $order_type->editSettings($vars);

        if (($errors = $order_type->errors())) {
            $this->Input->setErrors($errors);
            return;
        }

        $this->Input->setRules($this->getFormRules($vars));

        if ($this->Input->validates($vars)) {
            $fields = ['company_id', 'label', 'name', 'description', 'template',
                'template_style', 'type', 'client_group_id', 'manual_review',
                'allow_coupons', 'require_ssl', 'require_tos', 'tos_url',
                'abandoned_cart_first', 'abandoned_cart_second', 'abandoned_cart_third',
                'abandoned_cart_cancellation', 'inactive_after_cancellation',
                'require_captcha', 'status', 'visibility', 'date_added'];

            $this->Record->insert('order_forms', $vars, $fields);

            $order_form_id = $this->Record->lastInsertId();

            if (isset($vars['currencies'])) {
                $this->setCurrencies($order_form_id, $vars['currencies']);
            }
            if (isset($vars['gateways'])) {
                $this->setGateways($order_form_id, $vars['gateways']);
            }
            if (isset($vars['groups'])) {
                $this->setGroups($order_form_id, $vars['groups']);
            }
            if (isset($vars['meta'])) {
                $this->setMeta($order_form_id, $vars['meta']);
            }

            // Set this as the default order form if it's the only active one that exists
            $order_form = $this->get($order_form_id);
            if ($order_form && $order_form->status == 'active' && ($this->getListCount($vars['company_id']) == 1)) {
                $default_form = $this->OrderSettings->getSetting($vars['company_id'], 'default_form');

                if (!$default_form || empty($default_form->value)) {
                    $this->OrderSettings->setSetting($vars['company_id'], 'default_form', $order_form->label);
                }
            }

            return $order_form_id;
        }
    }

    /**
     * Edit an order form
     *
     * @param int $order_form_id The ID of the order form to edit
     * @param array $vars An array of order form data including:
     *  - company_id (optional, defaults to current company ID)
     *  - label The label used to access the order form
     *  - name The name of the order form
     *  - description The description of the order form
     *  - template The template to use for the order form
     *  - template_style The template style to use for the order form
     *  - type The type of order form
     *  - client_group_id The default client group to assign clients to when ordering from this order form
     *  - manual_review Whether or not to require all orders placed to be manually reviewed (default 0)
     *  - allow_coupons Whether or not to allow coupons (default 0)
     *  - require_ssl Whether or not to force secure connection (i.e. HTTPS) (default 0)
     *  - require_captcha Whether or not to force captcha (default 0)
     *  - require_tos Whether or not to require Terms of Service agreement (default 0)
     *  - tos_url The URL to the terms of service agreement (optional)
     *  - abandoned_cart_first The number of days after an unpaid order is placed to send the first reminder email (optional)
     *  - abandoned_cart_second The number of days after an unpaid order is placed to send the second reminder email (optional)
     *  - abandoned_cart_third The number of days after an unpaid order is placed to send the third reminder email (optional)
     *  - abandoned_cart_cancellation The number of days after an unpaid order is placed to cancel the order (optional)
     *  - inactive_after_cancellation Whether or not to mark a client as inactive after cancellation if this
     *      order/service is their first one (optional)
     *  - status The status of the order form (active/inactive default active)
     *  - visibility The visbility control for the order form (public, shared, client)
     *  - meta An array of key/value pairs to assign to this order form (optional dependent upon the order type)
     *  - groups An array of package group IDs to assign to this order form (optional dependent upon the order type)
     *  - gateways An array of gateway ID to assign to this order form (optional dependent upon the order type)
     *  - currencies An array of ISO 4217 currency codes to assign to this order form
     *      (optional dependent upon the order type)
     * @return int The ID of the order form that was updated, void on error
     */
    public function edit($order_form_id, array $vars)
    {
        if (!isset($vars['company_id'])) {
            $vars['company_id'] = Configure::get('Blesta.company_id');
        }

        $vars['order_id'] = $order_form_id;

        if (isset($vars['type'])) {
            $order_type = $this->loadOrderType($vars['type']);

            // Run order form through order type settings
            $vars = $order_type->editSettings($vars);

            if (($errors = $order_type->errors())) {
                $this->Input->setErrors($errors);
                return;
            }
        }

        $this->Input->setRules($this->getFormRules($vars, true));

        if ($this->Input->validates($vars)) {
            $fields = ['company_id', 'label', 'name', 'description', 'template',
                'template_style', 'type', 'client_group_id', 'manual_review',
                'allow_coupons', 'require_ssl', 'require_tos', 'tos_url',
                'abandoned_cart_first', 'abandoned_cart_second', 'abandoned_cart_third',
                'abandoned_cart_cancellation', 'inactive_after_cancellation',
                'require_captcha', 'status', 'visibility'];

            $this->Record->where('id', '=', $order_form_id)->update('order_forms', $vars, $fields);

            if (isset($vars['currencies'])) {
                $this->setCurrencies($order_form_id, $vars['currencies']);
            }
            if (isset($vars['gateways'])) {
                $this->setGateways($order_form_id, $vars['gateways']);
            }
            if (isset($vars['groups'])) {
                $this->setGroups($order_form_id, $vars['groups']);
            }
            if (isset($vars['meta'])) {
                $this->setMeta($order_form_id, $vars['meta']);
            }

            return $order_form_id;
        }
    }

    /**
     * Permanently deletes the given order form
     *
     * @param int $order_form_id The ID of the order form to delete
     */
    public function delete($order_form_id)
    {

        // Check that no pending order exists for this order form
        $this->Input->setRules($this->getDeleteFormRules());

        $vars = ['order_form_id' => $order_form_id];
        if ($this->Input->validates($vars)) {
            $this->Record->from('order_forms')->
                leftJoin('order_form_currencies', 'order_form_currencies.order_form_id', '=', 'order_forms.id', false)->
                leftJoin('order_form_gateways', 'order_form_gateways.order_form_id', '=', 'order_forms.id', false)->
                leftJoin('order_form_groups', 'order_form_groups.order_form_id', '=', 'order_forms.id', false)->
                leftJoin('order_form_meta', 'order_form_meta.order_form_id', '=', 'order_forms.id', false)->
                where('order_forms.id', '=', $order_form_id)->
                where('order_forms.company_id', '=', Configure::get('Blesta.company_id'))->
                delete(
                    [
                        'order_forms.*',
                        'order_form_currencies.*',
                        'order_form_gateways.*',
                        'order_form_groups.*',
                        'order_form_meta.*'
                    ]
                );
        }
    }

    /**
     * Returns all supported visbilities in key/value pairs
     *
     * @return array
     */
    public function getVisibilities()
    {
        return [
            'public' => $this->_('OrderForms.getVisibilities.public'),
            'shared' => $this->_('OrderForms.getVisibilities.shared'),
            'client' => $this->_('OrderForms.getVisibilities.client'),
        ];
    }

    /**
     * Returns a partial order form query
     *
     * @param string $status The status of results to fetch, null to fetch all results
     * @return Record A partially built order form query
     */
    private function getOrderForm($status = null)
    {
        if ($status) {
            $this->Record->where('order_forms.status', '=', $status);
        }

        return $this->Record->select('order_forms.*')->from('order_forms');
    }

    /**
     * Returns all currencies set for the given form ID
     *
     * @param int $form_id The ID of the form to fetch on
     * @return array An array of stdClass objects containing currencies
     */
    private function getCurrencies($form_id)
    {
        return $this->Record->select()->from('order_form_currencies')->
            where('order_form_id', '=', $form_id)->fetchAll();
    }

    /**
     * Returns all gateways set for the given form ID
     *
     * @param int $form_id The ID of the form to fetch on
     * @return array An array of stdClass objects containing gateway IDs
     */
    private function getGateways($form_id)
    {
        return $this->Record->select()->from('order_form_gateways')->
            where('order_form_id', '=', $form_id)->fetchAll();
    }

    /**
     * Returns all order form groups set for the given form ID
     *
     * @param int $form_id The ID of the form to fetch on
     * @param array $query Optional criteria to query on:
     *  - client_id int The client ID to compare package restrictions to
     *  - restrict_groups Whether to restrict package base on client settings
     * @return array An array of stdClass objects containing order form groups
     */
    private function getGroups($form_id, array $query = [])
    {
        $this->Record->select('order_form_groups.*')->
            from('order_form_groups');

        // Apply package group restrictions
        $this->Record = $this->restrictGroups($this->Record, $query);

        return $this->Record->where('order_form_groups.order_form_id', '=', $form_id)->
            group(['order_form_groups.package_group_id'])->
            order(['order_form_groups.order' => 'asc'])->
            fetchAll();
    }

    /**
     * Returns all meta fields set for the given form ID
     *
     * @param int $form_id The ID of the form to fetch on
     * @return array An array of stdClass objects representing order form meta fields
     */
    private function getMeta($form_id)
    {
        $meta = $this->Record->select()->from('order_form_meta')->
            where('order_form_id', '=', $form_id)->fetchAll();

        foreach ($meta as &$entry) {
            $data = @unserialize($entry->value);
            if ($data !== false) {
                $entry->value = $data;
            }
        }

        return $meta;
    }

    /**
     * Sets currencies for the given order form
     *
     * @param int $form_id The order form ID to set currencies for
     * @param array $currencies An array of currency codes to set for the order form
     */
    private function setCurrencies($form_id, array $currencies)
    {
        // Remove old assigned currencies
        $this->Record->from('order_form_currencies')->where('order_form_id', '=', $form_id)->delete();

        // Add any new currencies
        foreach ($currencies as $currency) {
            $this->Record->insert('order_form_currencies', ['order_form_id' => $form_id, 'currency' => $currency]);
        }
    }

    /**
     * Sets gateways for the given order form
     *
     * @param int $form_id The order form ID to set gateways for
     * @param array $gateways An array of gateway IDs to set for the order form
     */
    private function setGateways($form_id, array $gateways)
    {
        // Remove old assigned gateways
        $this->Record->from('order_form_gateways')->where('order_form_id', '=', $form_id)->delete();

        // Add any new gateways
        foreach ($gateways as $gateway) {
            $this->Record->insert('order_form_gateways', ['order_form_id' => $form_id, 'gateway_id' => $gateway]);
        }
    }

    /**
     * Sets package groups for the given order form
     *
     * @param int $form_id The order form ID to set package groups for
     * @param array $groups An array of package group IDs to set for the order form
     */
    private function setGroups($form_id, array $groups)
    {
        // Remove old assigned package groups
        $this->Record->from('order_form_groups')->where('order_form_id', '=', $form_id)->delete();

        // Add any new package groups
        $i = 0;
        foreach ($groups as $group) {
            $this->Record->insert(
                'order_form_groups',
                ['order_form_id' => $form_id, 'package_group_id' => $group, 'order' => $i++]
            );
        }
    }

    /**
     * Sets meta fields for the given order form
     *
     * @param int $form_id The order form ID to set meta fields for
     * @param array $meta An array of key/value pairs
     */
    private function setMeta($form_id, array $meta)
    {
        // Remove old assigned meta fields
        $this->Record->from('order_form_meta')->where('order_form_id', '=', $form_id)->delete();

        // Add any new meta fields
        foreach ($meta as $key => $value) {
            if (!is_scalar($value)) {
                $value = serialize((array) $value);
            }

            $this->Record->insert('order_form_meta', ['order_form_id' => $form_id, 'key' => $key, 'value' => $value]);
        }
    }

    /**
     * Returns all validation rules for adding/editing forms
     *
     * @param array $vars An array of input key/value pairs
     * @param bool $edit True if this if an edit, false otherwise
     * @return array An array of validation rules
     */
    private function getFormRules($vars, $edit = false)
    {
        $rules = [
            'name' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'if_set' => $edit,
                    'message' => $this->_('OrderForms.!error.name.empty')
                ]
            ],
            'label' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'if_set' => $edit,
                    'message' => $this->_('OrderForms.!error.label.empty')
                ],
                'unique' => [
                    'rule' => [[$this, 'validateUnique'], $edit ? ($vars['order_id'] ?? null) : null],
                    'if_set' => $edit,
                    'message' => $this->_('OrderForms.!error.label.unique')
                ],
                'length' => [
                    'rule' => ['maxLength', 32],
                    'message' => $this->_('OrderForms.!error.label.length')
                ],
                'format' => [
                    'rule' => ['matches', '/^([a-z0-9]+(\-|\_)?)*[a-z0-9]+$/i'],
                    'message' => $this->_('OrderForms.!error.label.format')
                ]
            ],
            'template' => [
                'supported' => [
                    'rule' => [[$this, 'validateTemplate'], (isset($vars['type']) ? $vars['type'] : null)],
                    'if_set' => $edit,
                    'message' => $this->_('OrderForms.!error.template.supported')
                ]
            ],
            'client_group_id' => [
                'valid' => [
                    'rule' => [[$this, 'validateClientGroup']],
                    'if_set' => $edit,
                    'message' => $this->_('OrderForms.!error.client_group_id.valid')
                ]
            ],
            'require_tos' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateTermsRequired'], (isset($vars['tos_url']) ? $vars['tos_url'] : null)],
                    'message' => $this->_('OrderForms.!error.require_tos.valid')
                ]
            ],
            'date_added' => [
                'valid' => [
                    'rule' => 'isDate',
                    'pre_format' => [[$this, 'dateToUtc']],
                    'if_set' => true,
                    'message' => $this->_('OrderForms.!error.date_added.valid')
                ]
            ],
            'visibility' => [
                'valid' => [
                    'rule' => ['in_array', array_keys($this->getVisibilities())],
                    'if_set' => true,
                    'message' => $this->_('OrderForms.!error.visibility.valid')
                ]
            ],
            'abandoned_cart_first' => [
                'valid' => [
                    'rule' => [[$this, 'validateAbandonedReminder']],
                    'pre_format' => [[$this, 'emptyToNull']],
                    'if_set' => true,
                    'message' => $this->_('OrderForms.!error.abandoned_cart_first.valid')
                ]
            ],
            'abandoned_cart_second' => [
                'valid' => [
                    'rule' => [[$this, 'validateAbandonedReminder']],
                    'pre_format' => [[$this, 'emptyToNull']],
                    'if_set' => true,
                    'message' => $this->_('OrderForms.!error.abandoned_cart_second.valid')
                ]
            ],
            'abandoned_cart_third' => [
                'valid' => [
                    'rule' => [[$this, 'validateAbandonedReminder']],
                    'pre_format' => [[$this, 'emptyToNull']],
                    'if_set' => true,
                    'message' => $this->_('OrderForms.!error.abandoned_cart_third.valid')
                ]
            ],
            'abandoned_cart_cancellation' => [
                'valid' => [
                    'rule' => [[$this, 'validateAbandonedReminder']],
                    'pre_format' => [[$this, 'emptyToNull']],
                    'if_set' => true,
                    'message' => $this->_('OrderForms.!error.field_abandoned_cart_cancellation.valid')
                ]
            ],
            'inactive_after_cancellation' => [
                'valid' => [
                    'rule' => ['in_array', [0, 1]],
                    'if_set' => true,
                    'message' => $this->_('OrderForms.!error.inactive_after_cancellation.valid')
                ]
            ]
        ];

        if ($edit) {
            $rules['label']['format']['rule'] = function($label) use ($vars) {
                if ((bool) preg_match('/^([a-z0-9]+(\-|\_)?)*[a-z0-9]+$/i', $label)) {
                    return true;
                }

                // Fetch current label
                if (isset($vars['order_id'])) {
                    $form = $this->get($vars['order_id']);

                    if (trim($form->label ?? '') == trim($label)) {
                        return true;
                    }
                }

                return false;
            };
        }

        return $rules;
    }

    /**
     * Converts an empty string to a null value
     *
     * @param mixed $value The value to evaluate
     * @return mixed null if the value is an empty string, $value otherwise
     */
    public function emptyToNull($value)
    {
        if (empty($value)) {
            return null;
        }

        return $value;
    }

    /**
     * Checks if a value is valid for the abandoned reminder notice
     *
     * @param mixed $value The value to evaluate
     * @return bool True if the value is valid
     */
    public function validateAbandonedReminder($value)
    {
        return is_numeric($value) || empty($value);
    }

    /**
     * Returns all validation rules to check when deleting an order form
     *
     * @return array An array of validation rules
     */
    private function getDeleteFormRules()
    {
        return [
            'order_form_id' => [
                'exists' => [
                    'rule' => [[$this, 'validatePendingOrdersExists']],
                    'negate' => true,
                    'message' => $this->_('OrderForms.!error.order_form_id.exists')
                ]
            ]
        ];
    }

    /**
     * Validates whether or not there are any pending orders for the given order form
     *
     * @param int $order_form_id The order form ID to validate against
     * @return bool True if there are pending orders, false otherwise
     */
    public function validatePendingOrdersExists($order_form_id)
    {
        return (boolean)$this->Record->select(['id'])->from('orders')->
            where('order_form_id', '=', $order_form_id)->
            where('status', '=', 'pending')->limit(1)->fetch();
    }

    /**
     * Validates whether or not the terms of service URL is required
     *
     * @param string $require_tos Set to "1" if TOS is required, "0" otherwise
     * @param string $tos_url The URL to the terms of service
     * @return bool true if $require_tos is not "1" or if $tos_url is non-empty, false otherwise
     */
    public function validateTermsRequired($require_tos, $tos_url)
    {
        return ($require_tos != '1' || !empty($tos_url));
    }

    /**
     * Validates whether or not the given
     *
     * @param string $label The label to validate
     * @param int $order_form_id The current order form ID (if it already exists) to exclude from the check
     * @return bool True if the label is unique and does not exist, false otherwise
     */
    public function validateUnique($label, $order_form_id = null)
    {
        $this->Record->select(['order_forms.label'])->
            from('order_forms')->where('order_forms.label', '=', $label)->
            where('order_forms.company_id', '=', Configure::get('Blesta.company_id'));

        if ($order_form_id) {
            $this->Record->where('order_forms.id', '!=', $order_form_id);
        }

        return !(boolean)$this->Record->fetch();
    }

    /**
     * Validates that the given order form template supports the given order form type
     *
     * @param string $template The order form template
     * @param string $type The order form type
     * @return bool True if the template is supported, false otherwise
     */
    public function validateTemplate($template, $type)
    {
        $types = $this->getSupportedTypes($template);

        return in_array($type, $types);
    }

    /**
     * Validates that the given client group exists and is part of the current company
     *
     * @param int $client_group_id The ID of the client group to verify exists
     * @return bool True if the client group exists and is part of the current company, false otherwise
     */
    public function validateClientGroup($client_group_id)
    {
        return $this->Record->select(['id'])->from('client_groups')->
            where('id', '=', $client_group_id)->
            where('company_id', '=', Configure::get('Blesta.company_id'))->
            fetch();
    }

    /**
     * Retrieves a list of abandoned order reminder intervals in hours
     *
     * @param int $days Number of day intervals to fetch
     * @return array A list of hours/days and their language
     */
    public function getNoticeIntervals($days)
    {
        $options = [1 => $this->_('OrderOrders.getNoticeIntervals.hour')];

        for ($i = 2; $i <= 12; $i++) {
            $options[$i] = $this->_('OrderOrders.getNoticeIntervals.hours', $i);
        }

        $options[24] = $this->_('OrderOrders.getNoticeIntervals.day');

        for ($i = 2; $i <= $days; $i++) {
            $options[($i * 24)] = $this->_('OrderOrders.getNoticeIntervals.days', $i);
        }

        return $options;
    }
}
