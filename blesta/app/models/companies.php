<?php

/**
 * Company management
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Companies extends AppModel
{
    /**
     * Initialize Companies
     */
    public function __construct()
    {
        parent::__construct();
        Language::loadLang(['companies']);
    }

    /**
     * Add a company
     *
     * @param array $vars An array of company info including:
     *
     *  - company_id The ID of the company whose settings to import from
     *  - name The name of the company
     *  - hostname The hostname of the company
     *  - address The address of the company (whole postal address)
     *  - phone The phone number of the company
     *  - fax The fax number of the company
     * @return int The company ID added, void on error
     */
    public function add(array $vars)
    {
        // Trigger the Companies.addBefore event
        extract($this->executeAndParseEvent('Companies.addBefore', ['vars' => $vars]));

        $this->Input->setRules($this->getRules($vars));

        if ($this->Input->validates($vars)) {
            // Start transaction
            $this->begin();

            try {
                // Add a company
                $fields = ['name', 'hostname', 'address', 'phone', 'fax'];
                $this->Record->insert('companies', $vars, $fields);

                $company_id = $this->Record->lastInsertId();

                // Set this company's settings to the settings of the given company
                Loader::loadModels($this, ['Themes', 'Actions']);
                $company_settings = $this->getSettings($vars['company_id'], true);
                $skip_settings = ['private_key', 'public_key', 'private_key_passphrase'];
                foreach ($company_settings as $company_setting) {
                    // Don't copy keys, those will be generated separately
                    if (in_array($company_setting->key, $skip_settings)) {
                        continue;
                    }

                    // Set the default admin/client themes
                    if ($company_setting->key == 'theme_admin' && ($admin_theme = $this->Themes->getDefault('admin'))
                    ) {
                        $company_setting->value = $admin_theme->id;
                    } elseif ($company_setting->key == 'theme_client'
                        && ($client_theme = $this->Themes->getDefault('client'))
                    ) {
                        $company_setting->value = $client_theme->id;
                    }

                    // Add the company setting
                    $this->setSetting(
                        $company_id,
                        $company_setting->key,
                        $company_setting->value,
                        $company_setting->encrypted,
                        $company_setting->inherit
                    );
                }
                unset($company_settings, $company_setting);

                // Copy over the client groups and client group settings
                $client_groups = $this->Record->select()->from('client_groups')->
                    where('company_id', '=', $vars['company_id'])->fetchAll();

                $client_group_fields = ['company_id', 'name', 'description', 'color'];
                $client_group_setting_fields = ['key', 'client_group_id', 'value', 'encrypted'];
                foreach ($client_groups as $client_group) {
                    $client_group->company_id = $company_id;

                    // Create the new client group
                    $this->Record->insert('client_groups', (array) $client_group, $client_group_fields);
                    $client_group_id = $this->Record->lastInsertId();

                    // Get the client group settings to copy
                    $client_group_settings = $this->Record->select()->from('client_group_settings')->
                        where('client_group_id', '=', $client_group->id)->fetchAll();

                    // Set the new client group settings
                    foreach ($client_group_settings as $client_group_setting) {
                        $client_group_setting->client_group_id = $client_group_id;
                        $this->Record->insert(
                            'client_group_settings',
                            (array) $client_group_setting,
                            $client_group_setting_fields
                        );
                    }
                }
                unset($client_group_fields, $client_group_setting_fields);

                $key_length = 1024;
                // Only allow large keys if the system can handle them efficiently
                if (extension_loaded('gmp')) {
                    $key_length = 3072;
                }
                // Set key pairs
                $this->generateKeyPair($company_id, $key_length);

                // Set the company's email signatures to those of the given company
                $fields = ['company_id', 'name', 'text', 'html'];
                $signatures = $this->Record->select(array_merge(['id'], $fields))->
                    from('email_signatures')->
                    where('company_id', '=', $vars['company_id'])->fetchAll();
                $signature_ids = [];

                // Create new email signatures for this company
                if ($signatures) {
                    for ($i = 0, $num_signatures = count($signatures); $i < $num_signatures; $i++) {
                        $signatures[$i] = (array) $signatures[$i];

                        // Set the new company ID
                        $signatures[$i]['company_id'] = $company_id;

                        // Insert the signature
                        $this->Record->insert('email_signatures', $signatures[$i], $fields);
                        $signature_ids[$signatures[$i]['id']] = $this->Record->lastInsertId();
                    }
                }

                // Set the company's email templates to those of the given company
                $fields = ['email_group_id', 'company_id', 'lang', 'from',
                    'from_name', 'subject', 'text', 'html', 'email_signature_id',
                    'include_attachments', 'status'
                ];
                $emails = $this->Record->select($fields)
                    ->from('emails')
                    ->where('company_id', '=', $vars['company_id'])
                    ->fetchAll();

                // Create new emails for this company
                if ($emails) {
                    // Setup the signature IDs for use in the email templates
                    $num_signatures = count($signature_ids);

                    // Set new email template values and create the email template
                    for ($i = 0, $num_emails = count($emails); $i < $num_emails; $i++) {
                        $emails[$i] = (array) $emails[$i];

                        // Set the new company ID
                        $emails[$i]['company_id'] = $company_id;

                        // Set the new email signature ID if this email template has one
                        $sig_id = $emails[$i]['email_signature_id'];
                        if ($sig_id !== null && isset($signature_ids[$sig_id])) {
                            $emails[$i]['email_signature_id'] = $signature_ids[$sig_id];
                        }

                        // Insert the email template
                        $this->Record->insert('emails', $emails[$i], $fields);
                    }
                }

                // Create new cron task runs for this company
                $fields = [
                    'cron_task_runs.task_id', 'cron_task_runs.company_id',
                    'cron_task_runs.time', 'cron_task_runs.interval', 'cron_task_runs.enabled'
                ];
                $cron_task_runs = $this->Record->select($fields)->from('cron_task_runs')->
                    innerJoin('cron_tasks', 'cron_tasks.id', '=', 'cron_task_runs.task_id', false)->
                    where('cron_tasks.task_type', '=', 'system')->
                    where('cron_task_runs.company_id', '=', $vars['company_id'])->
                    fetchAll();

                // Add each cron task run
                $fields = ['task_id', 'company_id', 'time', 'interval', 'enabled'];
                foreach ($cron_task_runs as $cron_task_run) {
                    $task_run = (array) $cron_task_run;
                    $task_run['company_id'] = $company_id;

                    $this->Record->insert('cron_task_runs', $task_run, $fields);
                }

                // Copy over currencies for this company
                $currencies = $this->Record->select()->from('currencies')->
                    where('company_id', '=', $vars['company_id'])->fetchAll();
                foreach ($currencies as $currency) {
                    $currency->company_id = $company_id;
                    $currency->exchange_updated = null;
                    $this->Record->insert('currencies', (array) $currency);
                }

                // Copy over languages for this company
                $languages = $this->Record->select()->from('languages')->
                    where('company_id', '=', $vars['company_id'])->fetchAll();
                foreach ($languages as $language) {
                    $language->company_id = $company_id;
                    $this->Record->insert('languages', (array) $language);
                }

                // Copy over themes for this company
                $this->Themes->cloneThemes($vars['company_id'], $company_id);

                // Get all non-plugin actions for the copied company
                $actions = $this->Actions->getAll(
                    ['company_id' => $vars['company_id'], 'plugin_id' => null, 'editable' => 0],
                    false
                );
                $subnav_items = [];
                $parent_nav_mapping = [];
                // Clone the actions
                foreach ($actions as $action) {
                    // Format action parameters and remove unused ones
                    $action->company_id = $company_id;
                    if (property_exists($action, 'options')) {
                        $action->options = ($action->options === null ? null : serialize($action->options));
                    }
                    $nav_items = $action->nav_items;
                    unset($action->nav_items);
                    unset($action->uri);
                    unset($action->action);
                    unset($action->plugin_dir);
                    unset($action->id);

                    $this->Record->insert('actions', (array) $action);
                    $action_id = $this->Record->lastInsertId();

                    // Add all the primary navigation items for this action and track the subnav items
                    foreach ($nav_items as $nav_item) {
                        $nav_item->action_id = $action_id;

                        if (isset($nav_item->parent_id)) {
                            $subnav_items[] = $nav_item;
                        } else {
                            $this->Record->insert('navigation_items', (array) $nav_item, ['action_id', 'order']);
                            // Map the old navigation item id to the new one so sub items can be correctly assigned
                            $parent_nav_mapping[$nav_item->id] = $this->Record->lastInsertId();
                        }
                    }
                }

                // Add subnavigation items
                foreach ($subnav_items as $subnav_item) {
                    if (isset($parent_nav_mapping[$subnav_item->parent_id])) {
                        // Map the old parent nav id to the newly created one
                        $subnav_item->parent_id = $parent_nav_mapping[$subnav_item->parent_id];
                        $this->Record->insert(
                            'navigation_items',
                            (array) $subnav_item,
                            ['action_id', 'order', 'parent_id']
                        );
                    }
                }

                $this->commit();

                // Trigger the Companies.addAfter event
                $this->executeAndParseEvent('Companies.addAfter', ['company_id' => $company_id, 'vars' => $vars]);
            } catch (Exception $e) {
                $this->rollBack();
                $this->Record->reset();
                $this->Input->setErrors(['exception' => ['message' => $e->getMessage()]]);
            }
            return $company_id;
        }
    }

    /**
     * Edit an existing company
     *
     * @param int $company_id The ID of the company to update
     * @param array $vars An array of company info including:
     *
     *  - name The name of the company
     *  - hostname The hostname of the company
     *  - address The address of the company (whole postal address)
     *  - phone The phone number of the company
     *  - fax The fax number of the company
     */
    public function edit($company_id, array $vars)
    {
        // Trigger the Companies.editBefore event
        extract($this->executeAndParseEvent('Companies.editBefore', ['company_id' => $company_id, 'vars' => $vars]));

        $vars['company_id'] = $company_id;

        $this->Input->setRules($this->getRules($vars, true));

        if ($this->Input->validates($vars)) {
            // Get the company state prior to update
            $company = $this->get($company_id);

            // Update a company
            $fields = ['name', 'hostname', 'address', 'phone', 'fax'];
            $this->Record->where('id', '=', $company_id)->update('companies', $vars, $fields);

            // Trigger the Companies.editAfter event
            $this->executeAndParseEvent(
                'Companies.editAfter',
                ['company_id' => $company_id, 'vars' => $vars, 'old_company' => $company]
            );
        }
    }

    /**
     * Delete an existing company. Only companies no longer in use may be removed.
     *
     * @param int $company_id The company ID to remove from the system
     */
    public function delete($company_id)
    {
        // Trigger the Companies.deleteBefore event
        extract($this->executeAndParseEvent('Companies.deleteBefore', ['company_id' => $company_id]));

        $rules = [
            'company_id' => [
                'in_use' => [
                    'rule' => ['compares', '==', Configure::get('Blesta.company_id')],
                    'negate' => true,
                    'message' => $this->_('Companies.!error.company_id.in_use')
                ]
            ]
        ];

        $this->Input->setRules($rules);

        $vars = ['company_id' => $company_id];

        if ($this->Input->validates($vars)) {
            // Get the company state prior to update
            $company = $this->get($company_id);

            $this->Record->from('companies')->
                where('companies.id', '=', $company_id)->delete();

            // Trigger the Companies.deleteAfter event
            $this->executeAndParseEvent(
                'Companies.deleteAfter',
                ['company_id' => $company_id, 'old_company' => $company]
            );
        }
    }

    /**
     * Generates a new public/private key pair for the company and stores it in the database.
     * Will not overwrite any existing key pair for the same company.
     *
     * @param int $company_id The ID of the company to generate the key pair for
     * @param int $bits The number of bits (i.e. 1024) for the key length
     * @param string $passphrase The (optional) passphrase to use to encrypt
     *  the private key with. Only set if you want super high security
     *  (and don't even trust Blesta to decrypt things by itself)
     * @return mixed An array containing both parts of the key pair on success,
     *  false if the key is already set for this company
     */
    public function generateKeyPair($company_id, $bits, $passphrase = null)
    {
        $private_key = $this->getSetting($company_id, 'private_key');

        // If private key is already set, or the company requested does not exist
        // we can not set a private key
        if (($private_key && $private_key->value != '') || !$this->get($company_id)) {
            return false;
        }

        $this->loadCrypto(['RSA']);
        $key_pair = $this->Crypt_RSA->createKey((int) $bits);

        if (isset($key_pair['publickey']) && isset($key_pair['privatekey'])) {
            $this->setSetting($company_id, 'public_key', $key_pair['publickey']);
            $this->setSetting(
                $company_id,
                'private_key',
                $this->systemEncrypt($key_pair['privatekey'], $passphrase)
            );
            $this->setSetting(
                $company_id,
                'private_key_passphrase',
                ($passphrase ? $this->systemHash($passphrase) : '')
            );
        }

        return $key_pair;
    }

    /**
     * Fetches a company with the given ID
     *
     * @param int $company_id The ID of the company to fetch
     * @return mixed A stdClass object representing the company, false if no such company exists
     */
    public function get($company_id)
    {
        $fields = ['id', 'name', 'hostname', 'address', 'phone', 'fax'];

        return $this->Record->select($fields)->
            from('companies')->where('id', '=', $company_id)->fetch();
    }

    /**
     * Retrieves the company configured for the given hostname. Looks for hostname and www.hostname
     *
     * @param string $hostname The hostname to fetch on (also looks at www.hostname)
     * @return stdClass A stdClass object representing the company if it exists, false otherwise
     */
    public function getByHostname($hostname)
    {
        $fields = ['id', 'name', 'hostname', 'address', 'phone', 'fax'];

        return $this->Record->select($fields)->from('companies')->
            where('hostname', '=', $hostname)->
            orWhere('hostname', '=', 'www.' . $hostname)->fetch();
    }

    /**
     * Fetches all companies available to the given staff ID
     *
     * @param int $staff_id The ID of the staff member
     * @return array An array of stdClass objects each representing a company
     */
    public function getAllAvailable($staff_id)
    {
        $this->Record = $this->getCompanies();

        return $this->Record->innerJoin('staff', 'staff.id', '=', $staff_id)
            ->innerJoin('users', 'users.id', '=', 'staff.user_id', false)
            ->innerJoin('staff_group', 'staff_group.staff_id', '=', 'staff.id', false)
            ->on('staff_group.staff_group_id', '=', 'staff_groups.id', false)
            ->on('companies.id', '=', 'staff_groups.company_id', false)
            ->innerJoin('staff_groups')
            ->where('staff.status', '=', 'active')
            ->group('companies.id')
            ->fetchAll();
    }

    /**
     * Fetches all companies
     *
     * @return mixed An array of stdClass objects representing each company, or false if none exist
     */
    public function getAll()
    {
        return $this->getCompanies()->fetchAll();
    }

    /**
     * Fetches a list of all companies
     *
     * @param int $page The page to return results for
     * @param string $order_by The sort and order conditions (e.g. array('sort_field'=>"ASC"), optional)
     * @return mixed An array of objects or false if no results.
     */
    public function getList($page = 1, $order_by = ['id' => 'ASC'])
    {
        $this->Record = $this->getCompanies();

        // Return the results
        return $this->Record->order($order_by)->
            limit($this->getPerPage(), (max(1, $page) - 1) * $this->getPerPage())->fetchAll();
    }

    /**
     * Return the total number of client groups returned from Companies::getList(),
     * useful in constructing pagination for the getList() method.
     *
     * @return int The total number of clients
     * @see Companies::getList()
     */
    public function getListCount()
    {
        $this->Record = $this->getCompanies();

        // Return the number of results
        return $this->Record->numResults();
    }

    /**
     * Partially constructs the query required by both Companies::getList() and
     * Companies::getListCount()
     *
     * @return Record The partially constructed query Record object
     */
    private function getCompanies()
    {
        $fields = [
            'companies.id', 'companies.hostname', 'companies.name',
            'companies.address', 'companies.phone', 'companies.fax'
        ];

        $this->Record->select($fields)->from('companies');

        return $this->Record;
    }

    /**
     * Fetches data about each installed view directory
     *
     * @param string $type The type of view directory to scan
     * @return array An array of view dirs
     */
    public function getViewDirs($type = 'client')
    {
        $base_dir = VIEWDIR . $type;
        $view_dirs = [];

        if (is_dir($base_dir)) {
            if ($dh = opendir($base_dir)) {
                while (($directory = readdir($dh)) !== false) {
                    if (substr($directory, 0, 1) == '.') {
                        continue;
                    }
                    if (!file_exists($base_dir . DS . $directory . DS . 'config.json')) {
                        continue;
                    }

                    $view_dirs[$directory] = json_decode(
                        file_get_contents($base_dir . DS . $directory . DS . 'config.json')
                    );
                }

                closedir($dh);
            }
        }

        return $view_dirs;
    }

    /**
     * Sets a group of company settings
     *
     * @param int $company_id The company ID
     * @param array $settings Settings to set as key/value pairs
     * @param array $value_keys An array of key values to accept as valid fields
     */
    public function setSettings($company_id, array $settings, array $value_keys = null)
    {
        if (!empty($value_keys)) {
            $settings = array_intersect_key($settings, array_flip($value_keys));
        }
        foreach ($settings as $key => $value) {
            $this->setSetting($company_id, $key, $value);
        }
    }

    /**
     * Sets a company settings
     *
     * @param int $company_id The company ID
     * @param string $key The setting key (i.e. name)
     * @param string $value The value to set for this setting
     * @param mixed $encrypted True to encrypt $value, false to store
     *  unencrypted, null to encrypt if currently set to encrypt
     * @param int $inherit Whether or not the setting should be inheritable
     */
    public function setSetting($company_id, $key, $value, $encrypted = null, $inherit = null)
    {
        $fields = ['company_id' => $company_id, 'key' => $key, 'value' => $value];

        if ($inherit !== null) {
            $fields['inherit'] = (int) $inherit;
        }

        // If encryption is mentioned set the appropriate value and encrypt if necessary
        if ($encrypted !== null) {
            $fields['encrypted'] = (int) $encrypted;
            if ($encrypted) {
                $fields['value'] = $this->systemEncrypt($fields['value']);
            }
        } else {
            // Check if the value is currently encrypted and encrypt if necessary
            $setting = $this->getSetting($company_id, $key);
            if ($setting && $setting->encrypted) {
                $fields['encrypted'] = 1;
                $fields['value'] = $this->systemEncrypt($fields['value']);
            }
        }

        $this->Record->duplicate('value', '=', $fields['value'])->
            insert('company_settings', $fields);
    }

    /**
     * Unsets a setting from the company settings. CAUTION: This method will
     * physically remove the setting from the system, and could have dire consequences.
     * You should never use this method, except when attempting to remove a setting
     * created by Companies::setSettings() or Companies::setSetting() that did not
     * previously exist for this installation.
     *
     * @param int $company_id The ID of the company whose setting to unset
     * @param string $key The setting to unset
     */
    public function unsetSetting($company_id, $key)
    {
        $this->Record->from('company_settings')->where('key', '=', $key)->
            where('company_id', '=', $company_id)->delete();
    }

    /**
     * Fetch all settings that may apply to this company. Settings are inherited
     * in the order of company_settings -> settings where "->" represents the
     * left item inheriting (and overwriting in the case of duplicates) values
     * found in the right item.
     *
     * @param int $company_id The company ID to retrieve settings for
     * @param bool $ignore_inheritence True to only retrieve company settings,
     *  false to get all inherited settings (default false)
     * @return mixed An array of objects containg key/values for the settings, false if no records found
     */
    public function getSettings($company_id, $ignore_inheritence = false)
    {

        // Company Settings
        $sql1 = $this->Record->select(['key', 'value', 'encrypted', 'inherit'])->
            select(['?' => 'level'], false)->appendValues(['company'])->
            from('company_settings')->where('company_id', '=', $company_id)->get();
        $values = $this->Record->values;
        $this->Record->reset();
        $this->Record->values = $values;

        // Return only company settings when ignoring inheritence
        if ($ignore_inheritence) {
            $settings = $this->Record->select()->from(['(' . $sql1 . ')' => 'temp'])->
                group('temp.key')->fetchAll();

            // Decrypt values where necessary
            for ($i = 0, $total = count($settings); $i < $total; $i++) {
                if ($settings[$i]->encrypted) {
                    $settings[$i]->value = $this->systemDecrypt($settings[$i]->value);
                }
            }
            return $settings;
        }

        // System settings
        $sql2 = $this->Record->select(['key', 'value', 'encrypted', 'inherit'])->
            select(['?' => 'level'], false)->appendValues(['system'])->
            from('settings')->where('inherit', '=', '1')->get();
        $values = $this->Record->values;
        $this->Record->reset();
        $this->Record->values = $values;

        $settings = $this->Record->select()->from(['((' . $sql1 . ') UNION (' . $sql2 . '))' => 'temp'])->
            group('temp.key')->fetchAll();

        // Decrypt values where necessary
        for ($i = 0, $total = count($settings); $i < $total; $i++) {
            if ($settings[$i]->encrypted) {
                $settings[$i]->value = $this->systemDecrypt($settings[$i]->value);
            }
        }
        return $settings;
    }

    /**
     * Fetch a specific setting that may apply to this company. Settings are inherited
     * in the order of company_settings -> settings where "->" represents the left
     * item inheriting (and overwriting in the case of duplicates) values found
     * in the right item.
     *
     * @param int $company_id The company ID to retrieve a setting for
     * @param string $key The key name of the setting to fetch
     * @return mixed A stdObject containg the key and value, false if no such key exists
     */
    public function getSetting($company_id, $key)
    {

        // Company Settings
        $sql1 = $this->Record->select(['key', 'value', 'encrypted', 'inherit'])->
            select(['?' => 'level'], false)->appendValues(['company'])->
            from('company_settings')->
            where('company_id', '=', $company_id)->where('key', '=', $key)->get();
        $values = $this->Record->values;
        $this->Record->reset();
        $this->Record->values = $values;

        // System settings
        $sql2 = $this->Record->select(['key', 'value', 'encrypted', 'inherit'])->
            select(['?' => 'level'], false)->appendValues(['system'])->
            from('settings')->where('key', '=', $key)->where('inherit', '=', '1')->get();
        $values = $this->Record->values;
        $this->Record->reset();
        $this->Record->values = $values;

        $setting = $this->Record->select()->from(['((' . $sql1 . ') UNION (' . $sql2 . '))' => 'temp'])->
            group('temp.key')->fetch();

        if ($setting && $setting->encrypted) {
            $setting->value = $this->systemDecrypt($setting->value);
        }
        return $setting;
    }

    /**
     * Returns the rule set for adding/editing companies
     *
     * @param array $vars A list of vars to be validated
     * @param bool $edit True when editing a company, false otherwise
     * @return array Company rules
     * @see Companies::add() and Companies::edit()
     */
    private function getRules(array $vars, $edit = false)
    {
        $rules = [
            'company_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'companies'],
                    'message' => $this->_('Companies.!error.company_id.exists')
                ]
            ],
            'name' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('Companies.!error.name.empty')
                ],
                'length' => [
                    'rule' => ['maxLength', 64],
                    'message' => $this->_('Companies.!error.name.length')
                ]
            ],
            'hostname' => [
                'valid' => [
                    'rule' => [
                        'matches',
                        '/^([a-z0-9]|[a-z0-9][a-z0-9\-]{0,61}[a-z0-9])'
                        . '(\.([a-z0-9]|[a-z0-9][a-z0-9\-]{0,61}[a-z0-9]))+$/i'
                    ],
                    'message' => $this->_('Companies.!error.hostname.valid')
                ],
                'length' => [
                    'rule' => ['maxLength', 255],
                    'message' => $this->_('Companies.!error.hostname.length')
                ],
                'unique' => [
                    'rule' => [[$this, 'validateHostnameUnique']],
                    'message' => $this->_('Companies.!error.hostname.unique')
                ]
            ],
            'address' => [
                'empty' => [
                    'if_set' => true,
                    'rule' => true,
                    'post_format' => [[$this, 'setDefaultIfEmpty']]
                ]
            ],
            'phone' => [
                'length' => [
                    'if_set' => true,
                    'rule' => ['maxLength', 64],
                    'message' => $this->_('Companies.!error.phone.length'),
                    'post_format' => [[$this, 'setDefaultIfEmpty']]
                ]
            ],
            'fax' => [
                'length' => [
                    'if_set' => true,
                    'rule' => ['maxLength', 64],
                    'message' => $this->_('Companies.!error.fax.length'),
                    'post_format' => [[$this, 'setDefaultIfEmpty']]
                ]
            ],
            'quota' => [
                'allowed' => [
                    'rule' => [[$this, 'validateAddAllowed']],
                    'message' => $this->_('Companies.!error.quota.allowed')
                ]
            ]
        ];

        // When editing a company, the unique hostname may exclude itself from the check
        if ($edit && isset($vars['company_id'])) {
            // Don't check on edit
            unset($rules['quota']);

            $rules['name']['empty']['if_set'] = true;
            $rules['name']['length']['if_set'] = true;

            $rules['hostname']['unique']['rule'] = [[$this, 'validateHostnameUnique'], $vars['company_id']];
            $rules['hostname']['valid']['if_set'] = true;
            $rules['hostname']['length']['if_set'] = true;
            $rules['hostname']['unique']['if_set'] = true;
        }

        return $rules;
    }

    /**
     * Verifies that the company can be added
     *
     * @return bool True if the company can be added, false otherwise
     */
    public function validateAddAllowed()
    {
        if (!isset($this->License)) {
            Loader::loadModels($this, ['License']);
        }

        $this->License->fetchLicense();
        $license = $this->License->getLocalData();
        if (!isset($license['comp_allowed']) || !isset($license['comp_total'])) {
            return false;
        }

        return $license['comp_allowed'] > $license['comp_total'];
    }

    /**
     * Checks whether the given hostname is unique or not
     *
     * @param string $hostname The hostname to check
     * @param int $company_id The company ID to exclude from the unique check (ie itself, optional, default null)
     * @return bool True if the given hostname is unique, false otherwise
     */
    public function validateHostnameUnique($hostname, $company_id = null)
    {
        $this->Record->select('id')->from('companies')->
            where('hostname', '=', $hostname);

        // Exclude the given company ID from the unique check
        if ($company_id != null) {
            $this->Record->where('id', '!=', $company_id);
        }

        $count = $this->Record->numResults();

        if ($count > 0) {
            return false;
        }
        return true;
    }

    /**
     * Validates Invoice customization settings
     *
     * @param array $vars An array of key/value pairs of invoice customization settings to validate
     */
    public function validateCustomization($vars)
    {
        $rules = [
            'inv_format' => [
                'format' => [
                    'rule' => ['compares', '!=', $vars['inv_draft_format']],
                    'message' => $this->_('Companies.!error.inv_format.format')
                ],
                'contains' => [
                    'rule' => ['strstr', '{num}'],
                    'message' => $this->_('Companies.!error.inv_format.contains')
                ]
            ],
            'inv_draft_format' => [
                'format' => [
                    'rule' => ['compares', '!=', $vars['inv_format']],
                    'message' => $this->_('Companies.!error.inv_draft_format.format')
                ],
                'contains' => [
                    'rule' => ['strstr', '{num}'],
                    'message' => $this->_('Companies.!error.inv_draft_format.contains')
                ]
            ],
            'inv_proforma_format' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['compares', '!=', $vars['inv_draft_format']],
                    'message' => $this->_('Companies.!error.inv_proforma_format.format')
                ],
                'contains' => [
                    'if_set' => true,
                    'rule' => ['strstr', '{num}'],
                    'message' => $this->_('Companies.!error.inv_proforma_format.contains')
                ]
            ],
            'inv_start' => [
                'number' => [
                    'rule' => ['matches', '/[0-9]+/i'],
                    'message' => $this->_('Companies.!error.inv_start.number')
                ]
            ],
            'inv_proforma_start' => [
                'number' => [
                    'if_set' => true,
                    'rule' => ['matches', '/[0-9]+/i'],
                    'message' => $this->_('Companies.!error.inv_proforma_start.number')
                ]
            ],
            'inv_increment' => [
                'number' => [
                    'rule' => ['matches', '/[0-9]+/i'],
                    'message' => $this->_('Companies.!error.inv_increment.number')
                ]
            ],
            'inv_pad_size' => [
                'number' => [
                    'rule' => ['matches', '/[0-9]+/i'],
                    'message' => $this->_('Companies.!error.inv_pad_size.number')
                ]
            ],
            'inv_pad_str' => [
                'length' => [
                    'rule' => ['betweenLength', 1, 1],
                    'message' => $this->_('Companies.!error.inv_pad_str.length')
                ]
            ]
        ];

        $this->Input->setRules($rules);

        return $this->Input->validates($vars);
    }
}
