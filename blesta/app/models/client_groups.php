<?php

/**
 * Client group management
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ClientGroups extends AppModel
{
    /**
     * Initialize ClientGroups
     */
    public function __construct()
    {
        parent::__construct();
        Language::loadLang(['client_groups']);
    }

    /**
     * Add a client group using the supplied data
     *
     * @param array $vars A single dimensional array of keys including:
     *
     *  - name The name of this group
     *  - description A description of this group (optional)
     *  - company_id The company ID this group belongs to
     *   color The HTML color that represents this group (optional)
     * @return int The client group ID created, or void on error
     */
    public function add(array $vars)
    {
        // Trigger the ClientGroups.addBefore event
        extract($this->executeAndParseEvent('ClientGroups.addBefore', ['vars' => $vars]));

        $this->Input->setRules($this->getRules());

        if ($this->Input->validates($vars)) {
            // Add a client group
            $fields = ['name', 'description', 'company_id', 'color'];
            $this->Record->insert('client_groups', $vars, $fields);

            $client_group_id = $this->Record->lastInsertId();

            // Trigger the ClientGroups.addAfter event
            $this->executeAndParseEvent(
                'ClientGroups.addAfter',
                ['client_group_id' => $client_group_id, 'vars' => $vars]
            );

            return $client_group_id;
        }
    }

    /**
     * Edit a client group using the supplied data
     *
     * @param int $client_group_id The ID of the group to be updated
     * @param array $vars A single dimensional array of keys including:
     *
     *  - name The name of this group
     *  - description A description of this group (optional)
     *  - company_id The company ID this group belongs to
     *  - color The HTML color that represents this group (optional)
     */
    public function edit($client_group_id, array $vars)
    {
        // Trigger the ClientGroups.editBefore event
        extract($this->executeAndParseEvent(
            'ClientGroups.editBefore',
            ['client_group_id' => $client_group_id, 'vars' => $vars]
        ));

        $rules = $this->getRules();
        $rules['group_id'] = [
            'exists' => [
                'rule' => [[$this, 'validateExists'], 'id', 'client_groups'],
                'message' => $this->_('ClientGroups.!error.group_id.exists')
            ]
        ];

        $this->Input->setRules($rules);

        $vars['group_id'] = $client_group_id;

        if ($this->Input->validates($vars)) {
            // Get the client group state prior to update
            $client_group = $this->get($client_group_id);

            // Update a client group
            $fields = ['name', 'description', 'company_id', 'color'];
            $this->Record->where('id', '=', $client_group_id)
                ->update('client_groups', $vars, $fields);

            // Trigger the ClientGroups.editAfter event
            $this->executeAndParseEvent(
                'ClientGroups.editAfter',
                ['client_group_id' => $client_group_id, 'vars' => $vars, 'old_client_group' => $client_group]
            );
        }
    }

    /**
     * Delete a client group and all associated client group settings
     *
     * @param int $client_group_id The ID for this client group
     */
    public function delete($client_group_id)
    {
        $client_group_id = (int) $client_group_id;

        // Trigger the ClientGroups.deleteBefore event
        extract($this->executeAndParseEvent('ClientGroups.deleteBefore', ['client_group_id' => $client_group_id]));

        $client_group = $this->get($client_group_id);
        $default_group = $this->getDefault($client_group->company_id);

        // If default client group, we cannot delete it
        if (!$default_group || $client_group_id == $default_group->id) {
            return false;
        }

        // Update all clients with this client group, set to the default group
        $this->Record->where('client_group_id', '=', $client_group_id)
            ->update('clients', ['client_group_id' => $default_group->id]);

        // Finally, delete the client group, and settings specific to this group
        $this->Record->from('client_group_settings')
            ->where('client_group_id', '=', $client_group_id)
            ->delete();

        $this->Record->from('client_groups')
            ->where('id', '=', $client_group_id)
            ->delete();

        // Trigger the ClientGroups.deleteAfter event
        $this->executeAndParseEvent(
            'ClientGroups.deleteAfter',
            ['client_group_id' => $client_group_id, 'old_client_group' => $client_group]
        );
    }

    /**
     * Finds the default client group for the given company.
     *
     * @param int $company_id
     * @return mixed stdClass object representing the default client group, false if no such group exists
     */
    public function getDefault($company_id)
    {
        return $this->getClientGroups($company_id)
            ->order(['client_groups.id' => 'ASC'])
            ->limit(1)
            ->fetch();
    }

    /**
     * Returns the given client group
     *
     * @param int $client_group_id The ID of the client group to fetch
     * @return mixed A stdClass object representing the client group, false if it does not exist
     */
    public function get($client_group_id)
    {
        return $this->Record->select(['id', 'company_id', 'name', 'description', 'color'])
            ->from('client_groups')
            ->where('id', '=', $client_group_id)
            ->fetch();
    }

    /**
     * Fetches a list of all client groups
     *
     * @param int $company_id The company ID
     * @param int $page The page to return results for (optional, default 1)
     * @param string $order_by The sort and order conditions (e.g. array('sort_field'=>"ASC"), optional)
     * @return mixed An array of objects or false if no results.
     */
    public function getList($company_id, $page = 1, array $order_by = ['name' => 'ASC'])
    {
        $this->Record = $this->getClientGroups($company_id);

        // Return the results
        return $this->Record->order($order_by)
            ->limit($this->getPerPage(), (max(1, $page) - 1) * $this->getPerPage())
            ->fetchAll();
    }

    /**
     * Return the total number of client groups returned from ClientGroups::getList(),
     * useful in constructing pagination for the getList() method.
     *
     * @param int $company_id The ID of the company whose client group count to fetch
     * @return int The total number of clients
     * @see ClientGroups::getList()
     */
    public function getListCount($company_id)
    {
        $this->Record = $this->getClientGroups($company_id);

        // Return the number of results
        return $this->Record->numResults();
    }

    /**
     * Fetches all custom client groups by company
     *
     * @param int $company_id The company ID to fetch client groups for
     * @return mixed An array of stdClass objects representing all client groups for the given company
     */
    public function getAll($company_id)
    {
        $this->Record = $this->getClientGroups($company_id);

        return $this->Record->fetchAll();
    }

    /**
     * Partially constructs the query required by ClientGroups::getList(),
     * ClientGroups::getListCount(), and ClientGroups::getAll()
     *
     * @param int $company_id The ID of the company whose client groups to fetch
     * @return Record The partially constructed query Record object
     */
    private function getClientGroups($company_id)
    {
        $fields = ['client_groups.id', 'client_groups.company_id', 'client_groups.name',
            'client_groups.description', 'client_groups.color', 'COUNT(clients.id)' => 'num_clients'
        ];

        // Find all client groups and the number of clients that belong to them
        $this->Record->select($fields)
            ->from('client_groups')
            ->leftJoin('clients', 'clients.client_group_id', '=', 'client_groups.id', false)
            ->where('client_groups.company_id', '=', $company_id)
            ->group('client_groups.id');

        return $this->Record;
    }

    /**
     * Fetch all settings that may apply to this client group. Settings are inherited
     * in the order of client_group_settings -> company_settings -> settings
     * where "->" represents the left item inheriting (and overwriting in the
     * case of duplicates) values found in the right item.
     *
     * @param int $client_group_id The client group ID to retrieve settings for
     * @param bool $ignore_inheritence True to fetch only client group settings without inheriting from
     *  company or system settings (default false)
     * @return mixed An array of objects containg key/values for the settings, false if no records found
     */
    public function getSettings($client_group_id, $ignore_inheritence = false)
    {

        // Client Group Settings
        $sql1 = $this->Record->select(['key', 'value', 'encrypted'])
            ->select(['?' => 'level'], false)
            ->appendValues(['client_group'])
            ->from('client_group_settings')
            ->where('client_group_id', '=', $client_group_id)
            ->get();
        $values = $this->Record->values;
        $this->Record->reset();
        $this->Record->values = $values;

        // Return only client group settings when ignoring company and system setting inheritence
        if ($ignore_inheritence) {
            $settings = $this->Record->select()
                ->from([$sql1 => 'temp'])
                ->group('temp.key')
                ->fetchAll();

            // Decrypt values where necessary
            for ($i = 0, $total = count($settings); $i < $total; $i++) {
                if ($settings[$i]->encrypted) {
                    $settings[$i]->value = $this->systemDecrypt($settings[$i]->value);
                }
            }
            return $settings;
        }

        // Company Settings
        $sql2 = $this->Record->select(['key', 'value', 'encrypted'])
            ->select(['?' => 'level'], false)
            ->appendValues(['company'])
            ->from('client_groups')
            ->innerJoin(
                'company_settings',
                'company_settings.company_id',
                '=',
                'client_groups.company_id',
                false
            )
            ->where('client_groups.id', '=', $client_group_id)
            ->where('company_settings.inherit', '=', '1')
            ->get();
        $values = $this->Record->values;
        $this->Record->reset();
        $this->Record->values = $values;

        // System settings
        $sql3 = $this->Record->select(['key', 'value', 'encrypted'])
            ->select(['?' => 'level'], false)
            ->appendValues(['system'])
            ->from('settings')
            ->where('settings.inherit', '=', '1')
            ->get();
        $values = $this->Record->values;
        $this->Record->reset();
        $this->Record->values = $values;

        $settings = $this->Record->select()
            ->from(
                ['((' . $sql1 . ') UNION (' . $sql2 . ') UNION (' . $sql3 . '))' => 'temp']
            )
            ->group('temp.key')
            ->fetchAll();

        // Decrypt values where necessary
        for ($i = 0, $total = count($settings); $i < $total; $i++) {
            if ($settings[$i]->encrypted) {
                $settings[$i]->value = $this->systemDecrypt($settings[$i]->value);
            }
        }
        return $settings;
    }

    /**
     * Fetch a specific setting that may apply to this client group. Settings are inherited
     * in the order of client_group_settings -> company_settings -> settings
     * where "->" represents the left item inheriting (and overwriting in the
     * case of duplicates) values found in the right item.
     *
     * @param int $client_group_id The client group ID to retrieve settings for
     * @param string $key The key name of the setting to fetch
     * @return mixed A stdClass object containg key/values for the settings, false if no records found
     */
    public function getSetting($client_group_id, $key)
    {
        // Client Group Settings
        $sql1 = $this->Record->select(['key', 'value', 'encrypted'])
            ->select(['?' => 'level'], false)->appendValues(['client_group'])
            ->from('client_group_settings')
            ->where('client_group_id', '=', $client_group_id)
            ->where('client_group_settings.key', '=', $key)
            ->get();
        $values = $this->Record->values;
        $this->Record->reset();
        $this->Record->values = $values;

        // Company Settings
        $sql2 = $this->Record->select(['key', 'value', 'encrypted'])
            ->select(['?' => 'level'], false)->appendValues(['company'])
            ->from('client_groups')
            ->innerJoin('company_settings', 'company_settings.company_id', '=', 'client_groups.company_id', false)
            ->where('client_groups.id', '=', $client_group_id)
            ->where('company_settings.key', '=', $key)
            ->where('company_settings.inherit', '=', '1')
            ->get();
        $values = $this->Record->values;
        $this->Record->reset();
        $this->Record->values = $values;

        // System settings
        $sql3 = $this->Record->select(['key', 'value', 'encrypted'])
            ->select(['?' => 'level'], false)
            ->appendValues(['system'])
            ->from('settings')
            ->where('settings.key', '=', $key)
            ->where('settings.inherit', '=', '1')
            ->get();
        $values = $this->Record->values;
        $this->Record->reset();
        $this->Record->values = $values;

        $setting = $this->Record->select()
            ->from(
                ['((' . $sql1 . ') UNION (' . $sql2 . ') UNION (' . $sql3 . '))' => 'temp']
            )
            ->group('temp.key')
            ->fetch();

        if ($setting && $setting->encrypted) {
            $setting->value = $this->systemDecrypt($setting->value);
        }
        return $setting;
    }

    /**
     * Add a client group setting, if duplicate then update the value
     *
     * @param int $client_group_id The ID for the specified client group
     * @param string $key The key for this client group setting
     * @param string $value The value for this client group setting
     * @param mixed $encrypted True to encrypt $value, false to store
     *  unencrypted, null to encrypt if currently set to encrypt
     */
    public function setSetting($client_group_id, $key, $value, $encrypted = null)
    {
        $fields = ['key' => $key, 'client_group_id' => $client_group_id, 'value' => $value];

        // If encryption is mentioned set the appropriate value and encrypt if necessary
        if ($encrypted !== null) {
            $fields['encrypted'] = (int) $encrypted;
            if ($encrypted) {
                $fields['value'] = $this->systemEncrypt($fields['value']);
            }
        } else {
            // Check if the value is currently encrypted and encrypt if necessary
            $setting = $this->getSetting($client_group_id, $key);
            if ($setting && $setting->encrypted) {
                $fields['encrypted'] = 1;
                $fields['value'] = $this->systemEncrypt($fields['value']);
            }
        }

        $this->Record->duplicate('value', '=', $fields['value'])
            ->insert('client_group_settings', $fields);
    }

    /**
     * Delete a client group setting
     *
     * @param int $client_group_id The ID for the specified client group
     * @param string $key The key for this client group setting
     */
    public function unsetSetting($client_group_id, $key)
    {
        $this->Record->from('client_group_settings')
            ->where('key', '=', $key)
            ->where('client_group_id', '=', $client_group_id)
            ->delete();
    }

    /**
     * Deletes all client group settings
     *
     * @param int $client_group_id The ID for the specified client group
     */
    public function unsetSettings($client_group_id)
    {
        $this->Record->from('client_group_settings')
            ->where('client_group_id', '=', $client_group_id)
            ->delete();
    }

    /**
     * Add multiple client group settings, if duplicate then update the value
     *
     * @param int $client_group_id The ID for the specified client group
     * @param array $vars A single dimensional array of key/value pairs of settings
     * @param array $value_keys An array of key values to accept as valid fields
     */
    public function setSettings($client_group_id, array $vars, array $value_keys = null)
    {
        if (!empty($value_keys)) {
            $vars = array_intersect_key($vars, array_flip($value_keys));
        }
        foreach ($vars as $key => $value) {
            $this->setSetting($client_group_id, $key, $value);
        }
    }

    /**
     * Returns the rule set for adding/editing groups
     *
     * @return array A list of client group rules
     */
    private function getRules()
    {
        $rules = [
            'name' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('ClientGroups.!error.name.empty')
                ]
            ],
            'company_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'companies'],
                    'message' => $this->_('ClientGroups.!error.company_id.exists')
                ]
            ],
            'color' => [
                'length' => [
                    'if_set' => true,
                    'rule' => ['maxLength', 16],
                    'message' => $this->_('ClientGroups.!error.color.length')
                ]
            ],
            'clients_format' => [
                'format' => [
                    'if_set' => true,
                    'rule' => function ($format) {
                        return str_contains($format, '{num}');
                    },
                    'message' => $this->_('ClientGroups.!error.clients_format.format')
                ]
            ]
        ];
        return $rules;
    }
}
