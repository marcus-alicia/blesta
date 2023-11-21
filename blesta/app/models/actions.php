<?php

/**
 * Actions
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Actions extends AppModel
{
    /**
     * @var array A mapping of old `action` values to their equivilent `location`
     */
    private $action_to_location_map = [
        'nav_secondary_staff' => 'nav_staff','nav_primary_staff' => 'nav_staff',
        'nav_secondary_client' => 'nav_client', 'nav_primary_client' => 'nav_client',
    ];

    /**
     * @var array A mapping of `location` values to their old equivilent `action`
     */
    private $location_to_action_map = [
        'nav_staff' => 'nav_primary_staff',
        'nav_client' => 'nav_primary_client',
        'nav_public' => 'nav_primary_client'
    ];

    /**
     * @var array A list of valid `location` values
     */
    private $locations = [
        'nav_staff', 'nav_client', 'nav_public',
        'widget_client_home', 'widget_staff_home',
        'widget_staff_client', 'widget_staff_billing',
        'action_staff_client'
    ];

    /**
     * Initialize Messages
     */
    public function __construct()
    {
        parent::__construct();
        Language::loadLang(['actions']);
    }

    /**
     * Adds a new action
     *
     * @param array $vars An array of action info including:
     *
     *  - location The identifier for the location to display the action (optional, "nav_staff" by default)
     *       ('nav_client', 'nav_staff', 'nav_public', 'widget_client_home', 'widget_staff_home',
     *          'widget_staff_client', 'widget_staff_billing', 'action_staff_client')
     *  - url The full or partial URL of the action
     *  - name The language identifier or text with which to label the action
     *  - options A list of additional options for the action
     *  - plugin_id The ID of the plugin with which this action is associated (optional, null by default)
     *  - company_id The ID of the company to which this action belongs
     *  - editable Whether the action can be updated through the interface (0 or 1) (optional, 1 by default)
     *  - enabled Whether the action can be used in the interface (0 or 1)  (optional, 1 by default)
     * @return int The ID for this action, void on error
     */
    public function add(array $vars)
    {
        Loader::loadModels($this, ['Navigation', 'PluginManager']);
        $rules = $this->getRules($vars);

        $this->Input->setRules($rules);

        // Insert the action
        $vars = $this->mapOldFields($vars);
        if ($this->Input->validates($vars)) {
            $fields = ['location', 'url', 'name', 'options', 'plugin_id', 'company_id', 'editable', 'enabled'];
            $this->Record->insert('actions', $vars, $fields);

            return $this->Record->lastInsertId();
        }
    }

    /**
     * Edits an existing action
     *
     * @param int $action_id The action ID to update
     * @param array $vars An array of action info including:
     *
     *  - name The the text or language identifier with which to label the action
     *  - url The URL of the action
     *  - options A list of additional options for the action
     *  - enabled Whether the action can be used in the interface (0 or 1)
     * @return int The ID for this action, void on error
     */
    public function edit($action_id, array $vars)
    {
        $rules = $this->getRules($vars, true);

        $this->Input->setRules($rules);

        // Update an action
        $vars = $this->mapOldFields($vars);
        if ($this->Input->validates($vars)) {
            $fields = ['name', 'url', 'options', 'enabled'];
            $this->Record->where('id', '=', $action_id)->update('actions', $vars, $fields);

            return $action_id;
        }
    }

    /**
     * Map the pre Blesta v5 fields to their new equivalent
     *
     * @param array $vars A list of input vars to map
     * @return array The converted list of input vars
     */
    public function mapOldFields(array $vars)
    {
        if (isset($vars['uri']) && !isset($vars['url'])) {
            $vars['url'] = $vars['uri'];
        }

        if (isset($vars['action']) && !isset($vars['location'])) {
            $vars['location'] = array_key_exists($vars['action'], $this->action_to_location_map)
                ? $this->action_to_location_map[$vars['action']]
                : $vars['action'];
        }

        return $vars;
    }

    /**
     * Deletes an existing action
     *
     * @param int $plugin_id The ID of the plugin from which to remove the action
     * @param string $url The URL of the specific record to delete,
     *  otherwise defaults to delete all records for this action (optional)
     */
    public function delete($plugin_id, $url = null)
    {
        // Delete the action
        $this->Record->from('actions')->
            leftJoin('navigation_items', 'navigation_items.action_id', '=', 'actions.id', false)->
            where('actions.plugin_id', '=', $plugin_id);

        if ($url) {
            $this->Record->where('actions.url', '=', $url);
        }

        $this->Record->delete(['actions.*', 'navigation_items.*']);
    }

    /**
     * Fetches an existing action
     *
     * @param int $action_id The ID of the action to fetch
     * @param bool $translate Whether or not to translate any language definitions (optional, default true)
     * @return mixed A stdClass object representing the action if it exists, false otherwise
     */
    public function get($action_id, $translate = true)
    {
        $action = $this->getActionRecord(['id' => $action_id])->fetch();
        return $action ? $this->formatAction($action, $translate) : $action;
    }

    /**
     * Fetches an existing action by URL
     *
     * @param string $url The URL of the action to fetch
     * @param string $location The location identifier by which to get actions (optional)
     *      ('nav_client', 'nav_staff', 'nav_public', 'widget_client_home', 'widget_staff_home',
     *          'widget_staff_client', 'widget_staff_billing', 'action_staff_client')
     * @param int $company_id The ID of the company for which to fetch actions (optional)
     * @param bool $translate Whether or not to translate any language definitions (optional, default true)
     * @return mixed A stdClass object representing the action if it exists, false otherwise
     */
    public function getByUrl($url, $location = null, $company_id = null, $translate = true)
    {
        if ($company_id === null) {
            $company_id = Configure::get('Blesta.company_id');
        }
        $action = $this->getActionRecord(['url' => $url, 'location' => $location, 'company_id' => $company_id])->
            fetch();
        return $action ? $this->formatAction($action, $translate) : $action;
    }

    /**
     * Partially constructs the query for searching actions
     *
     * @param array $filters A list of parameters to filter by, including:
     *
     *  - id The ID of a particular action to fetch
     *  - url The URL of a particular action to fetch
     *  - location The location identifier by which to get actions
     *      ('nav_client', 'nav_staff', 'nav_public', 'widget_client_home', 'widget_staff_home',
     *          'widget_staff_client', 'widget_staff_billing', 'action_staff_client')
     *  - plugin_id The ID of the plugin for which to fetch actions
     *  - company_id The ID of the company for which to fetch actions
     *  - editable Whether to fetch only aditable actions
     *  - enabled Whether to fetch only enabled actions
     * @return Record The partially constructed query Record object
     */
    private function getActionRecord(array $filters)
    {
        $this->Record->select(['actions.*', 'plugins.dir' => 'plugin_dir'])->
            from('actions')->
            leftJoin('plugins', 'plugins.id', '=', 'actions.plugin_id', false);

        $value_filters = ['id', 'url', 'location', 'plugin_id', 'company_id', 'editable', 'enabled'];
        foreach ($value_filters as $filter) {
            if (array_key_exists($filter, $filters)) {
                $this->Record->where('actions.' . $filter, '=', $filters[$filter]);
            }
        }

        return $this->Record;
    }

    /**
     * Fetches a full list of all actions
     *
     * @param array $filters A list of parameters to filter by, including:
     *
     *  - location The location identifier by which to get actions
     *      ('nav_client', 'nav_staff', 'nav_public', 'widget_client_home', 'widget_staff_home',
     *          'widget_staff_client', 'widget_staff_billing', 'action_staff_client')
     *  - plugin_id The ID of the plugin for which to fetch actions
     *  - company_id The ID of the company for which to fetch actions
     *  - enabled Whether to fetch only enabled actions
     *  - editable Whether to fetch only aditable actions
     * @param bool $translate Whether or not to translate any language definitions (optional, default true)
     * @return array An array of stdClass objects representing all actions
     */
    public function getAll(array $filters = [], $translate = true)
    {
        return $this->formatActions(
            $this->getActionRecord(
                $this->mapOldFields($filters)
            )->fetchAll(),
            $translate
        );
    }


    /**
     * Fetches a paginated list of actions
     *
     * @param array $filters A list of parameters to filter by, including:
     *
     *  - location The location identifier by which to get actions
     *      ('nav_client', 'nav_staff', 'nav_public', 'widget_client_home', 'widget_staff_home',
     *          'widget_staff_client', 'widget_staff_billing', 'action_staff_client')
     *  - plugin_id The ID of the plugin for which to fetch actions
     *  - company_id The ID of the plugin for which to fetch actions
     *  - enabled Whether to fetch only enabled actions
     *  - editable Whether to fetch only aditable actions
     * @param int $page The page to return results for (optional, default 1)
     * @param string $order_by The sort and order conditions (e.g. array('sort_field'=>"ASC"), optional)
     * @param bool $translate Whether or not to translate any language definitions (optional, default true)
     * @return array An array of stdClass objects representing all actions
     */
    public function getList(array $filters = [], $page = 1, array $order_by = ['id' => 'DESC'], $translate = true)
    {
        return $this->formatActions(
            $this->getActionRecord($this->mapOldFields($filters))->
                order($order_by)->
                limit($this->getPerPage(), (max(1, $page) - 1) * $this->getPerPage())->
                fetchAll(),
            $translate
        );
    }

    /**
     * Return the total number of actions returned from Action::getList(),
     * useful in constructing pagination for the getList() method.
     *
     * @param array $filters A list of parameters to filter by, including:
     *
     *  - location The location identifier by which to get actions
     *      ('nav_client', 'nav_staff', 'nav_public', 'widget_client_home', 'widget_staff_home',
     *          'widget_staff_client', 'widget_staff_billing', 'action_staff_client')
     *  - plugin_id The ID of the plugin for which to fetch actions
     *  - company_id The ID of the plugin for which to fetch actions
     *  - editable Whether to fetch only aditable actions
     *  - enabled Whether to fetch only enabled actions
     * @return int The total number of actions
     * @see Actions::getList()
     */
    public function getListCount($filters)
    {
        // Return the number of results
        return $this->getActionRecord($this->mapOldFields($filters))->numResults();
    }

    /**
     * Format the plugin options and action name
     *
     * @param array $actions A list of stdClass objects representing plugin actions to format
     * @param bool $translate Whether or not to translate any language definitions (optional, default true)
     * @return array The given $actions formatted
     */
    private function formatActions(array $actions, $translate = true)
    {
        foreach ($actions as $index => $action) {
            $actions[$index] = $this->formatAction($action, $translate);
        }

        return $actions;
    }

    /**
     * Formats the given plugin action options and name
     *
     * @param stdClass $action The stdClass object representing the plugin action
     * @param bool $translate Whether or not to translate any language definitions (optional, default true)
     * @return stdClass The stdClass $action formatted
     */
    private function formatAction(stdClass $action, $translate = true)
    {
        Loader::loadModels($this, ['Navigation']);

        // Unserialize the options
        if (property_exists($action, 'options')) {
            $action->options = ($action->options === null ? null : unserialize($action->options));
        }

        // Translate the action's names
        if ($translate) {
            $action = $this->translateAction($action);
        }

        $action->uri = $action->url;
        $action->action = isset($this->location_to_action_map[$action->location])
            ? $this->location_to_action_map[$action->location]
            : $action->location;

        $action->nav_items = $this->Navigation->getAll(['action_id' => $action->id]);

        return $action;
    }

    /**
     * Translates the language definitions within the action
     *
     * @param stdClass $action The action whose language definitions to translate
     * @return stdClass $action The given action with language definitions translated
     */
    private function translateAction(stdClass $action)
    {
        if (!isset($this->PluginManager)) {
            Loader::loadModels($this, ['PluginManager']);
        }

        if (!isset($this->loaded_plugins)) {
            $this->loaded_plugins = [];
        }

        // Load the language file for the plugin associated with this navigation item
        if (isset($action->plugin_id)
            && !in_array($action->plugin_id, $this->loaded_plugins)
            && ($plugin = $this->PluginManager->get($action->plugin_id))
        ) {
            Language::loadLang([$plugin->dir . '_plugin'], null, PLUGINDIR . $plugin->dir . DS . 'language' . DS);

            $this->loaded_plugins[] = $action->plugin_id;
        }

        // Translate the action name
        if (property_exists($action, 'name')) {
            $language = Language::_($action->name, true);
            $action->name = empty($language) ? $action->name : $language;
        }

        return $action;
    }

    /**
     * Retrieves a list of action locations
     *
     * @return array Key=>value pairs of action locations
     */
    public function getLocations()
    {
        $descriptions = [];
        foreach ($this->locations as $location) {
            $descriptions[$location]  = $this->_('Actions.getLocations.' . $location);
        }
        return $descriptions;
    }

    /**
     * Retrieves a list of action locations and their descriptions
     *
     * @return array Key=>value pairs of action locations and their descriptions
     *
     */
    public function getLocationDescriptions()
    {
        $descriptions = [];
        foreach ($this->locations as $location) {
            $descriptions[$location]  = $this->_('Actions.getLocationDescriptions.' . $location);
        }
        return $descriptions;
    }

    /**
     * Rules to validate when adding or editing an action
     *
     * @param array $vars An array of input fields to validate against
     *
     *  - location The identifier for the location to display the action (optional, "nav_staff" by default)
     *       ('nav_client', 'nav_staff', 'nav_public', 'widget_client_home', 'widget_staff_home',
     *          'widget_staff_client', 'widget_staff_billing', 'action_staff_client')
     *  - url The URL of the action
     *  - name The the text or language identifier with which to label the action
     *  - options A list of additional options for the action
     *  - plugin_id The ID of the plugin with which this action is associated (optional, null by default)
     *  - company_id The ID of the company to which this action belongs
     *  - editable Whether the action can be updated through the interface (0 or 1) (optional, 1 by default)
     *  - enabled Whether the action can be used in the interface (0 or 1) (optional, 1 by default)
     * @param bool $edit Whether or not it's an edit (optional, false by default)
     * @return array Rules to validate
     */
    private function getRules(array $vars = [], $edit = false)
    {
        $rules = [
            'location' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['in_array', array_keys($this->getLocations())],
                    'message' => $this->_('Actions.!error.location.valid')
                ],
                'unique' => [
                    'rule' => [
                        function ($location, $company_id, $url) {
                            $total = $this->Record->select()
                                ->from('actions')
                                ->where('company_id', '=', $company_id)
                                ->where('location', '=', $location)
                                ->where('url', '=', $url)
                                ->numResults();

                            return ($total === 0);
                        },
                        ['_linked' => 'company_id'],
                        ['_linked' => 'url']
                    ],
                    'message' => $this->_('Actions.!error.location.unique')
                ]
            ],
            'url' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('Actions.!error.url.empty')
                ]
            ],
            'name' => [
                'action_empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('Actions.!error.name.action_empty')
                ]
            ],
            'options' => [
                'empty' => [
                    'if_set' => true,
                    'rule' => true,
                    'post_format' => 'serialize'
                ]
            ],
            'plugin_id' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateExists'], 'id', 'plugins'],
                    'message' => $this->_('Actions.!error.plugin_id.valid')
                ]
            ],
            'company_id' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateExists'], 'id', 'companies'],
                    'message' => $this->_('Actions.!error.company_id.valid')
                ]
            ],
            'editable' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['in_array', [0, 1]],
                    'message' => $this->_('Actions.!error.editable.valid')
                ]
            ],
            'enabled' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['in_array', [0, 1]],
                    'message' => $this->_('Actions.!error.enabled.valid')
                ]
            ],
        ];

        if ($edit) {
            unset($rules['location']);
            unset($rules['plugin_id']);
            unset($rules['company_id']);
            unset($rules['editable']);
        }

        return $rules;
    }
}
