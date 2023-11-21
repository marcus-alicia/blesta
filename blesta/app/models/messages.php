<?php

/**
 * Messages
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Messages extends AppModel
{
    /**
     * Initialize Messages
     */
    public function __construct()
    {
        parent::__construct();
        Language::loadLang(['messages']);
    }

    /**
     * Adds a new message
     *
     * @param array $vars An array of message info including:
     *
     *  - message_group_id The message group ID where the message belongs
     *  - company_id The company ID
     *  - type The message type (optional, "sms" by default)
     *  - status The message status (optional, "active" by default)
     *  - content A numerically indexed array each containing the message content for each language on the system
     *      - lang The language of the message content
     *      - content The message content
     * @return int The ID for this message, void on error
     */
    public function add(array $vars)
    {
        $rules = $this->getRules($vars);

        $this->Input->setRules($rules);

        // Insert the message
        if ($this->Input->validates($vars)) {
            $fields = ['message_group_id', 'company_id', 'type', 'status'];
            $this->Record->insert('messages', $vars, $fields);

            $message_id = $this->Record->lastInsertId();

            // Add message content
            $this->setMessageContent($message_id, $vars['content']);

            return $message_id;
        }
    }

    /**
     * Edits an existing message
     *
     * @param int $message_id The message ID to update
     * @param array $vars An array of message info including:
     *
     *  - message_group_id The message group ID where the message belongs
     *  - type The message type (optional, "sms" by default)
     *  - status The message status (optional, "active" by default)
     *  - content A numerically indexed array each containing the message content for each language on the system
     *      - lang The language of the message content
     *      - content The message content
     * @return int The ID for this message, void on error
     */
    public function edit($message_id, array $vars)
    {
        $rules = $this->getRules($vars, true);

        $this->Input->setRules($rules);

        // Update a message
        if ($this->Input->validates($vars)) {
            $fields = ['message_group_id', 'type', 'status'];
            $this->Record->where('id', '=', $message_id)->update('messages', $vars, $fields);

            // Update message content
            $this->setMessageContent($message_id, $vars['content']);

            return $message_id;
        }
    }

    /**
     * Deletes an existing message
     *
     * @param int $message_id The message ID to delete
     */
    public function delete($message_id)
    {
        // Delete the message
        $this->Record->from('messages')
            ->where('id', '=', $message_id)
            ->delete();

        // Delete the message content
        $this->Record->from('message_content')
            ->where('message_id', '=', $message_id)
            ->delete();
    }

    /**
     * Fetches an existing message
     *
     * @param int $message_id The ID of the message to fetch
     * @return mixed A stdClass object representing the message if it exists, false otherwise
     */
    public function get($message_id)
    {
        $fields = [
            'messages.*',
            'message_groups.plugin_dir',
            'message_groups.type' => 'message_group_type',
        ];

        $message = (object) $this->Record->select($fields)
            ->innerJoin('message_groups', 'messages.message_group_id', '=', 'message_groups.id', false)
            ->where('messages.id', '=', $message_id)
            ->from('messages')
            ->fetch();

        if ($message) {
            $message->content = $this->getMessageContent($message_id);
        }

        return $message;
    }

    /**
     * Fetches a full list of all messages for a given company
     *
     * @param int $company_id The ID of the company to fetch messages for
     * @param array $filters A list of parameters to filter by, including:
     *
     *  - plugin_dir The directory name of the plugin to fetch message groups for
     * @return mixed An array of stdClass objects representing all messages, or false if no messages found
     */
    public function getAll($company_id = null, array $filters = [])
    {
        $fields = [
            'messages.*',
            'message_groups.plugin_dir',
            'message_groups.type' => 'message_group_type',
        ];

        $this->Record->select($fields)
            ->innerJoin('message_groups', 'messages.message_group_id', '=', 'message_groups.id', false)
            ->from('messages');

        if (!is_null($company_id)) {
            $this->Record->where('messages.company_id', '=', $company_id);
        }

        if (isset($filters['plugin_dir'])) {
            $this->Record->where('message_groups.plugin_dir', '=', $filters['plugin_dir']);
        }

        $messages = $this->Record->fetchAll();

        if ($messages) {
            foreach ($messages as $key => $message) {
                $messages[$key]->content = $this->getMessageContent($message->id);
            }
        }

        return $messages;
    }

    /**
     * Fetches a messages for a given message group and language
     *
     * @param string $message_group_id The ID of the message group
     * @param string $lang The language in ISO 636-1 2-char + "_"
     *  + ISO 3166-1 2-char (e.g. en_us) (optional)
     * @param int $company_id The ID of the company to fetch messages for (optional)
     * @return stdClass An stdClass object representing the message group with their messages,
     *  or false if no messages found
     */
    public function getByGroup($message_group_id, $lang = null, $company_id = null)
    {
        if (is_null($company_id)) {
            $company_id = Configure::get('Blesta.company_id');
        }

        $message_group = $this->Record->select('message_groups.*')
            ->innerJoin('messages', 'message_groups.id', '=', 'messages.message_group_id', false)
            ->where('message_groups.id', '=', $message_group_id);

        if (!is_null($lang)) {
            $message_group->innerJoin('message_content', 'messages.id', '=', 'message_content.message_id', false)
                ->where('message_content.lang', '=', $lang);
        }

        $message_group = (object) $message_group->group('messages.message_group_id')
            ->from('message_groups')
            ->fetch();

        $message_group->messages = $this->Record->select()
            ->where('message_group_id', '=', $message_group_id)
            ->where('company_id', '=', $company_id)
            ->order(['type' => 'ASC'])
            ->from('messages')
            ->fetchAll();

        if ($message_group->messages) {
            $messages = [];
            foreach ($message_group->messages as $key => $message) {
                $messages[$message->type] = (object) $this->Record->select('messages.*')
                    ->innerJoin('message_groups', 'messages.message_group_id', '=', 'message_groups.id', false)
                    ->where('messages.id', '=', $message->id)
                    ->from('messages')
                    ->fetch();

                if ($messages[$message->type] ?? false) {
                    $content = $this->getMessageContent($message->id, $lang);

                    foreach ($content as $message_content) {
                        $messages[$message->type]->content[$message_content->lang] = $message_content;
                    }
                }
            }
            $message_group->messages = $messages;
        }

        if ($message_group) {
            $message_group->tags = explode(',', $message_group->tags ?? '');
        }

        return $message_group;
    }

    /**
     * Returns a list of all the types enabled for a message group
     *
     * @param string $message_group_id The ID of the message group
     * @return array A list containing the enabled types for the given message
     */
    public function getMessageGroupEnabledTypes($message_group_id)
    {
        $types = [];
        $message_group = $this->getByGroup($message_group_id);

        foreach ($message_group->messages as $message) {
            if ($message->status == 'active') {
                $types[$message->type] = $message->type;
            }
        }

        return $types;
    }

    /**
     * Returns a list of the supported message types
     *
     * @return array A list of the supported message types
     */
    public function getTypes()
    {
        return [
            'sms' => $this->_('Messages.getTypes.sms')
        ];
    }

    /**
     * Retrieves a list of message status types
     *
     * @return array Key=>value pairs of email status types
     */
    public function getStatusTypes()
    {
        return [
            'active' => $this->_('Messages.getStatusTypes.active'),
            'inactive' => $this->_('Messages.getStatusTypes.inactive')
        ];
    }

    /**
     * Gets the content from an specific message
     *
     * @param int $message_id The message ID to fetch the message content
     * @param string $lang The language in ISO 636-1 2-char + "_"
     *  + ISO 3166-1 2-char (e.g. en_us) (optional)
     * @return array An array of stdClass objects, containing each the message content
     */
    public function getMessageContent($message_id, $lang = null)
    {
        $this->Record->select()
            ->where('message_id', '=', $message_id);

        if (!is_null($lang)) {
            $this->Record->where('lang', '=', $lang);
        }

        return $this->Record->from('message_content')
            ->fetchAll();
    }

    /**
     * Sets the message content of an existing message
     *
     * @param int $message_id The message ID where the message content belongs
     * @param array $vars A numerically indexed array each containing the message content for each language on the system
     *  - lang The language of the message content
     *  - content The message content
     */
    public function setMessageContent($message_id, array $vars)
    {
        foreach ($vars as $message_content)
        {
            $message_content['message_id'] = $message_id;

            $this->Record->duplicate('content', '=', $message_content['content'])
                ->insert('message_content', $message_content);
        }
    }

    /**
     * Rules to validate when adding or editing a message
     *
     * @param array $vars An array of input fields to validate against
     * @param bool $edit Whether or not it's an edit (optional)
     * @return array Rules to validate
     */
    private function getRules(array $vars = [], $edit = false)
    {
        $rules = [
            'message_group_id' => [
                'valid' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'message_groups'],
                    'message' => $this->_('Messages.!error.message_group_id.valid')
                ]
            ],
            'company_id' => [
                'valid' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'companies'],
                    'message' => $this->_('Messages.!error.company_id.valid')
                ]
            ],
            'type' => [
                'valid' => [
                    'rule' => ['in_array', array_keys($this->getTypes())],
                    'message' => $this->_('Messages.!error.type.valid')
                ]
            ],
            'status' => [
                'valid' => [
                    'rule' => ['in_array', array_keys($this->getStatusTypes())],
                    'message' => $this->_('Messages.!error.status.valid')
                ]
            ],
            'content' => [
                'format' => [
                    'rule' => 'is_array',
                    'message' => $this->_('Messages.!error.content.format')
                ]
            ]
        ];

        if ($edit) {
            unset($rules['company_id']);

            $rules['message_group_id']['valid']['if_set'] = true;
            $rules['type']['valid']['if_set'] = true;
            $rules['status']['valid']['if_set'] = true;
            $rules['content']['format']['if_set'] = true;
        }

        return $rules;
    }
}
