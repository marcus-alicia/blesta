<?php

/**
 * Email Group management
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class EmailGroups extends AppModel
{
    /**
     * Initialize Email Groups
     */
    public function __construct()
    {
        parent::__construct();
        Language::loadLang('email_groups');
    }

    /**
     * Adds an email group
     *
     * @param array $vars An array of variable email group info, including:
     *
     *   - action A unique action
     *   - type The type of user this email group applies to (optional, default 'client')
     *   - notice_type The type of notice this email is for ('bcc', 'to', or null for none; optional, default null)
     *   - plugin_dir The directory where the plugin resides that is associated with this email group (optional)
     *   - tags Tags that apply to this group (optional, default NULL)
     * @return mixed The email group ID, or void on error
     */
    public function add(array $vars)
    {
        // Group ID cannot be used for rule validation
        unset($vars['group_id']);

        $this->Input->setRules($this->getRules($vars));

        if ($this->Input->validates($vars)) {
            // Add an email group
            $fields = ['action', 'type', 'notice_type', 'plugin_dir', 'tags'];
            $this->Record->insert('email_groups', $vars, $fields);

            return $this->Record->lastInsertId();
        }
    }

    /**
     * Updates an email group
     *
     * @param int $id The ID of the email group to edit
     * @param array $vars An array of variable email group info, including:
     *
     *   - action A unique action
     *   - type The type of user this email group applies to (optional, default 'client')
     *   - notice_type The type of notice this email is for ('bcc', 'to', or null for none; optional)
     *   - plugin_dir The directory where the plugin resides that is associated with this email group (optional)
     *   - tags Tags that apply to this group (optional, default NULL)
     */
    public function edit($id, array $vars)
    {
        $vars['group_id'] = $id;

        $this->Input->setRules($this->getRules($vars, true));

        if ($this->Input->validates($vars)) {
            // Add an email group
            $fields = ['action', 'type', 'notice_type', 'plugin_dir', 'tags'];
            $this->Record->where('id', '=', $id)->update('email_groups', $vars, $fields);
        }
    }

    /**
     * Deletes an email group
     *
     * @param int $id The email group ID
     */
    public function delete($id)
    {
        $this->Record->from('email_groups')->
            where('email_groups.id', '=', $id)->
            delete(['email_groups.*']);

        $this->Record->from('emails')->
            where('emails.email_group_id', '=', $id)->
            delete(['emails.*']);
    }

    /**
     * Fetches an email group
     *
     * @param string $action The type of email group to fetch
     * @return mixed An stdClass object representing the email group, or false if one does not exist
     */
    public function getByAction($action)
    {
        return $this->Record->select()->from('email_groups')->where('action', '=', $action)->fetch();
    }

    /**
     * Fetches a list of all email groups irrespective of company
     *
     * @param string $type The type of email groups to fetch, one of the following (optional, default "client")
     *
     *   - client
     *   - staff
     *   - shared
     * @param bool $core True to fetch all core email groups, or false to fetch all plugin-related email groups
     * @return array A list of stdClass objects representing email groups
     */
    public function getAll($type = 'client', $core = true)
    {
        return $this->Record->select()->from('email_groups')->
            where('type', '=', $type)->
            where('plugin_dir', ($core ? '=' : '!='), null)->
            fetchAll();
    }

    /**
     * Fetches a list of all email groups by the notice type
     *
     * @param mixed $notice_type A string representing the notice type to fetch, one of the following:
     *
     *   - bcc The BCC type
     *   - to The To type
     *   - null
     * @param string $type The type of email groups to fetch, one of the following (optional)
     *
     *   - client
     *   - staff
     *   - shared
     * @param string $core True to fetch all core email groups, or false to
     *  fetch all plugin-related email groups (optional, default true)
     * @return array A list of stdClass objects representing email groups
     */
    public function getAllByNoticeType($notice_type, $type = null, $core = true)
    {
        $this->Record->select()->from('email_groups')->
            where('notice_type', '=', $notice_type)->
            where('plugin_dir', ($core ? '=' : '!='), null);

        if ($type) {
            $this->Record->where('type', '=', $type);
        }

        return $this->Record->fetchAll();
    }

    /**
     * Fetches a list of all emails and email groups under a company for a specific type and language
     *
     * @param int $company_id The company ID to fetch email groups for
     * @param string $type The type of email group to get, "client", "staff", "shared" (optional, default "client")
     * @param string $core True to fetch all core email groups, or false to
     *   fetch all plugin-related email groups (optional, default true)
     * @param string $lang The language in ISO 636-1 2-char + "_"
     *   + ISO 3166-1 2-char (e.g. en_us) (optional, defaults to default language)
     * @return array A list of stdClass objects representing emails
     */
    public function getAllEmails($company_id, $type = 'client', $core = true, $lang = null)
    {
        $fields = [
            'emails.*', 'email_groups.action' => 'email_group_action',
            'email_groups.type' => 'email_group_type', 'email_groups.notice_type' => 'email_group_notice_type',
            'email_groups.plugin_dir', 'email_groups.tags' => 'email_group_tags'
        ];

        if ($lang == null) {
            $lang = Configure::get('Language.default');
        }

        // Fetch the plugin name if not a core email group
        if (!$core) {
            $fields['plugins.name'] = 'plugin_name';
        }

        $this->Record->select($fields)->from('emails')->
            innerJoin('email_groups', 'email_groups.id', '=', 'emails.email_group_id', false);

        // Fetch the plugins specific to this company
        if (!$core) {
            $this->Record->innerJoin('plugins', 'plugins.dir', '=', 'email_groups.plugin_dir', false)->
                where('plugins.company_id', '=', $company_id);
        }

        $this->Record->where('email_groups.plugin_dir', ($core ? '=' : '!='), null)->
            where('emails.company_id', '=', $company_id)->where('emails.lang', '=', $lang)->
            where('email_groups.type', '=', $type);

        return $this->Record->group('email_groups.id')->
            order(['email_groups.action' => 'ASC'])->fetchAll();
    }

    /**
     * Validates an email group's 'type' field
     *
     * @param string $type The type
     * @return bool True if type is validated, false otherwise
     */
    public function validateType($type)
    {
        return in_array($type, ['client', 'staff', 'shared']);
    }

    /**
     * Returns the rule set for adding/editing email groups
     *
     * @param array $vars The input vars
     * @param bool $edit True to fetch the edit rules, or false for the add rules (default false)
     * @return array Email group rules
     */
    private function getRules(array $vars, $edit = false)
    {
        // Use the group ID for validation
        $group_id = (isset($vars['group_id']) ? $vars['group_id'] : null);

        $rules = [
            'action' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('EmailGroups.!error.action.empty')
                ],
                'length' => [
                    'rule' => ['maxLength', 64],
                    'message' => $this->_('EmailGroups.!error.action.length')
                ],
                'unique' => [
                    'rule' => function ($action) use ($group_id) {
                        // The action may not be in use unless it is for the given group
                        $this->Record->select()
                            ->from('email_groups')
                            ->where('action', '=', $action);

                        if ($group_id) {
                            $this->Record->where('id', '!=', $group_id);
                        }

                        return ($this->Record->numResults() === 0);
                    },
                    'message' => $this->_('EmailGroups.!error.action.unique')
                ]
            ],
            'type' => [
                'format' => [
                    'rule' => [[$this, 'validateType']],
                    'message' => $this->_('EmailGroups.!error.type.format')
                ]
            ],
            'notice_type' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['in_array', ['bcc', 'to']],
                    'message' => $this->_('EmailGroups.!error.notice_type.valid')
                ]
            ]
        ];

        // Rules for editing email groups
        if ($edit) {
            // All fields are optional
            $rules = $this->setRulesIfSet($rules);

            // Require that the email group exists
            $rules['group_id'] = [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'email_groups'],
                    'message' => $this->_('EmailGroups.!error.group_id.exists')
                ]
            ];
        }

        return $rules;
    }
}
