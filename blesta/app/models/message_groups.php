<?php

/**
 * Message Groups
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class MessageGroups extends AppModel
{
    /**
     * Initialize MessageGroups
     */
    public function __construct()
    {
        parent::__construct();
        Language::loadLang(['message_groups']);
    }

    /**
     * Adds a message group
     *
     * @param array $vars An array of message info including:
     *
     *  - action The message group action
     *  - type The message group type
     *  - plugin_dir The directory name of the plugin where the message group belongs (optional)
     *  - tags Tags that apply to this group (optional, default null)
     * @return int The ID for this message group
     */
    public function add(array $vars)
    {
        $rules = $this->getRules($vars);

        $this->Input->setRules($rules);

        // Insert the message group
        if ($this->Input->validates($vars)) {
            $fields = ['action', 'type', 'plugin_dir', 'tags'];
            $this->Record->insert('message_groups', $vars, $fields);

            return $this->Record->lastInsertId();
        }
    }

    /**
     * Edits an existing message group
     *
     * @param int $message_group_id The ID of the message group to update
     * @param array $vars An array of message info including:
     *
     *  - action The message group action
     *  - type The message group type
     *  - plugin_dir The directory name of the plugin where the message group belongs (optional)
     *  - tags Tags that apply to this group (optional, default null)
     */
    public function edit($message_group_id, array $vars)
    {
        $rules = $this->getRules($vars, true);

        $this->Input->setRules($rules);

        // Update a message group
        if ($this->Input->validates($vars)) {
            $fields = ['action', 'type', 'plugin_dir'];
            $this->Record->where('id', '=', $message_group_id)->update('message_groups', $vars, $fields);
        }
    }

    /**
     * Deletes an existing message group
     *
     * @param int $message_group_id The message group ID to delete
     */
    public function delete($message_group_id)
    {
        $this->Record->from('message_groups')->
            innerJoin('messages', 'messages.message_group_id', '=', 'message_groups.id', false)->
            innerJoin('message_content', 'message_content.message_id', '=', 'messages.id', false)->
            where('message_groups.id', '=', $message_group_id)->
            delete(['message_groups.*', 'messages.*', 'message_content.*']);
    }

    /**
     * Fetches an existing message
     *
     * @param int $message_group_id The ID of the message group to fetch
     * @return mixed A stdClass object representing the message group if it exists, false otherwise
     */
    public function get($message_group_id)
    {
        return $this->Record->select()
            ->where('id', '=', $message_group_id)
            ->from('message_groups')
            ->fetch();
    }

    /**
     * Fetches a full list of all message groups
     *
     * @param array $filters A list of parameters to filter by, including:
     *
     *  - plugin_dir The directory name of the plugin to fetch message groups for
     * @return array A list of stdClass objects representing all message groups
     */
    public function getAll(array $filters = [])
    {
        $this->Record->select()->from('message_groups');

        if (isset($filters['plugin_dir'])) {
            $this->Record->where('plugin_dir', '=', $filters['plugin_dir']);
        }

        return $this->Record->fetchAll();
    }

    /**
     * Fetches a list of all messages and message groups under a company for a specific type and language
     *
     * @param int $company_id The company ID to fetch message groups for
     * @param string $type The type of message group to get, "client", "staff", "shared"
     *  or null to fetch all types (optional, default "client")
     * @param bool $core True to fetch all core message groups, or false to
     *   fetch all plugin-related message groups (optional, default true)
     * @param string $lang The language in ISO 636-1 2-char + "_"
     *   + ISO 3166-1 2-char (e.g. en_us) (optional, defaults to default language)
     *
     * @return array A list of stdClass objects representing the messages
     */
    public function getAllMessages($company_id, $type = 'client', $core = true, $lang = null)
    {
        $fields = [
            'messages.*', 'message_groups.action' => 'message_group_action',
            'message_groups.type' => 'message_group_type', 'message_groups.plugin_dir',
            'message_groups.tags' => 'message_group_tags', 'message_content.content'
        ];

        if ($lang == null) {
            $lang = Configure::get('Language.default');
        }

        // Fetch the plugin name if not a core message group
        if (!$core) {
            $fields['plugins.name'] = 'plugin_name';
        }

        $this->Record->select($fields)
            ->from('messages')
            ->innerJoin('message_groups', 'message_groups.id', '=', 'messages.message_group_id', false)
            ->innerJoin('message_content', 'message_content.message_id', '=', 'messages.id', false);

        // Fetch the plugins specific to this company
        if (!$core) {
            $this->Record->innerJoin('plugins', 'plugins.dir', '=', 'message_groups.plugin_dir', false)
                ->where('plugins.company_id', '=', $company_id);
        }

        $this->Record->where('message_groups.plugin_dir', ($core ? '=' : '!='), null)
            ->where('messages.company_id', '=', $company_id);

        if (!is_null($type)) {
            $this->Record->where('message_groups.type', '=', $type);
        }

        return $this->Record->group('message_groups.id')
            ->order(['message_groups.action' => 'ASC'])
            ->where('message_content.lang', '=', $lang)
            ->fetchAll();
    }

    /**
     * Fetches a message group
     *
     * @param string $action The action of message group to fetch
     * @return mixed An stdClass object representing the message group, or false if one does not exist
     */
    public function getByAction($action)
    {
        return $this->Record->select()
            ->from('message_groups')
            ->where('action', '=', $action)
            ->fetch();
    }

    /**
     * Returns a list of the supported message group types
     *
     * @return array A list of the supported message group types
     */
    public function getTypes()
    {
        return [
            'client' => $this->_('ModuleManager.getTypes.client'),
            'staff' => $this->_('ModuleManager.getTypes.staff'),
            'shared' => $this->_('ModuleManager.getTypes.shared')
        ];
    }

    /**
     * Rules to validate when adding or editing a message group
     *
     * @param array $vars An array of input fields to validate against
     * @param bool $edit Whether or not it's an edit (optional)
     * @return array Rules to validate
     */
    private function getRules(array $vars = [], $edit = false)
    {
        $rules = [
            'action' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('MessageGroups.!error.action.empty')
                ]
            ],
            'type' => [
                'valid' => [
                    'rule' => ['in_array', array_keys($this->getTypes())],
                    'message' => $this->_('MessageGroups.!error.type.valid')
                ]
            ],
            'plugin_dir' => [
                'empty' => [
                    'if_set' => true,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('MessageGroups.!error.plugin_dir.empty')
                ]
            ],
            'tags' => [
                'empty' => [
                    'if_set' => true,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('MessageGroups.!error.tags.empty')
                ]
            ]
        ];

        if ($edit) {
            unset($rules['company_id']);
        }

        return $rules;
    }
}
