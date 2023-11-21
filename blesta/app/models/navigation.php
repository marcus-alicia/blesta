<?php

/**
 * Handles navigation.
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Navigation extends AppModel
{
    /**
     * @var array An array of all preset base URIs
     */
    private $base_uris = [];

    /**
     * Initialize Navigation
     */
    public function __construct()
    {
        parent::__construct();
        Language::loadLang(['navigation']);
    }

    /**
     * Adds a new navigation item
     *
     * @param array $vars An array of navigation item info including:
     *
     *  - action_id The ID of the action linked to this navigation item
     *  - order The order index used to determine the order in which navigation items
     *       are displayed (optional, highest index + 1 by default)
     *  - parent_id The ID of the navigation item under which
     *      to display this navigation item (optional, null by default)
     *  - parent_url The URL by which to determine which navigation item to display this one under (optional)
     * @return int The ID for this navigation item, void on error
     */
    public function add(array $vars)
    {
        Loader::loadModels($this, ['Actions']);
        $this->Input->setRules($this->getRules($vars));

        // Insert the navigation item
        if ($this->Input->validates($vars)) {
            // Get the parent ID based on the given parent URL
            if (isset($vars['parent_url'])
                && ($action = $this->Actions->get($vars['action_id']))
                && ($parent_nav = $this->getNavRecord([
                    'url' => $vars['parent_url'],
                    'company_id' => $action->company_id,
                    'location' => $action->location
                ])->fetch())
            ) {
                $vars['parent_id'] = $parent_nav->id;
            }

            // If no order index was given, insert this navigation item at the end of the list
            if (!isset($vars['order'])) {
                $last_nav_item = $this->getNavRecord([], ['navigation_items.order' => 'DESC'])->fetch();
                $vars['order'] = $last_nav_item ? $last_nav_item->order + 1 : 0;
            }

            $fields = ['action_id', 'order', 'parent_id'];
            $this->Record->insert('navigation_items', $vars, $fields);
            return $this->Record->lastInsertId();
        }
    }

    /**
     * Rules to validate when adding or editing a navigation item
     *
     * @param array $vars An array of input fields to validate against
     *
     *  - action_id The ID of the action linked to this navigation item
     *  - order The order index used to determine the order in which navigation items
     *       are displayed (optional, highest index + 1 by default)
     *  - parent_id The ID of the navigation item under which
     *      to display this navigation item (optional, null by default)
     *  - parent_url The URL by which to determine which navigation item to display this one under (optional)
     * @param bool $edit Whether or not it's an edit (optional)
     * @return array Rules to validate
     */
    private function getRules(array $vars = [], $edit = false)
    {
        $rules = [
            'action_id' => [
                'valid' => [
                    'if_set' => $edit,
                    'rule' => [[$this, 'validateExists'], 'id', 'actions'],
                    'message' => $this->_('Navigation.!error.action_id.valid')
                ]
            ],
            'order' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => 'is_numeric',
                    'message' => $this->_('Navigation.!error.order.valid')
                ]
            ],
            'parent_id' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateExists'], 'id', 'navigation_items'],
                    'message' => $this->_('Navigation.!error.parent_id.valid')
                ]
            ],
        ];

        if ($edit) {
            // Do nothing
        }

        return $rules;
    }

    /**
     * Deletes existing actions
     *
     * @param array $filters A list of parameters by which to filter the results, including:
     *
     *  - company_id The ID of the company for which to delete action
     *  - url The URL of the navigation item's action
     *  - location The location in which an action is allowed to be displayed
     *  - plugin_id The ID of the plugin for which to delete action
     *  - parent_id The ID of the parent nav for which to delete subitems
     */
    public function delete(array $filters = [])
    {
        if (empty($filters)) {
            // Let's not randomly erase all the navigation items, use the delete_all filter if you really want to
            return;
        }

        // Delete the action
        $this->Record->from('navigation_items')->
            innerJoin('actions', 'actions.id', '=', 'navigation_items.action_id', false);

        if (isset($filters['url'])) {
            $this->Record->where('actions.url', '=', $filters['url']);
        }

        if (isset($filters['location'])) {
            $this->Record->where('actions.location', '=', $filters['location']);
        }

        if (isset($filters['plugin_id'])) {
            $this->Record->where('actions.plugin_id', '=', $filters['plugin_id']);
        }

        if (isset($filters['company_id'])) {
            $this->Record->where('actions.company_id', '=', $filters['company_id']);
        }

        if (isset($filters['parent_id'])) {
            $this->Record->where('navigation_items.parent_id', '=', $filters['parent_id']);
        }

        if (isset($filters['delete_all'])) {
            // Do nothing, this is just used to delete all the navigation items in the system
        }

        $this->Record->delete(['navigation_items.*']);
    }

    /**
     * Gets a list of navigation items matching the given filters
     *
     * @param array $filters A list of parameters by which to filter the results, including:
     *
     *  - action_id The ID of the action to which the navigation item is attached
     *  - company_id The ID of the company for which the navigation item is display
     *  - plugin_id The ID of the plugin for which the navigation item was created
     *  - url The URL of the navigation item's action
     *  - location The location in which an action is allowed to be displayed
     *  - child Whether to fetch navigation items with parents (true to fetch child items,
     *      false to fetch parent items)
     * @param array $order The sort and order conditions (e.g. array('sort_field'=>"ASC"), optional)
     * @return array A partial record object used to fetch navigation items and their actions from the database
     */
    public function getAll(array $filters, array $order = ['navigation_items.order' => 'ASC'])
    {
        return $this->getNavRecord($filters, $order)->fetchAll();
    }

    /**
     * Retrieves the primary navigation
     *
     * @param string $base_uri The base_uri for the currently logged in user
     * @return array An array of main navigation elements in key/value pairs
     *  where each key is the URI and each value is an array representing that element including:
     *
     *  - name The name of the link
     *  - active True if the element is active
     *  - sub An array of subnav elements (optional) following the same indexes as above
     */
    public function getPrimary($base_uri)
    {
        return $this->getNavItems($base_uri, 'nav_staff');
    }

    /**
     * Retrieves the primary navigation for the client interface
     *
     * @param string $base_uri The base_uri for the currently logged in user
     * @return array An array of main navigation elements in key/value pairs
     *  where each key is the URI and each value is an array representing that element including:
     *
     *  - name The name of the link
     *  - active True if the element is active
     *  - sub An array of subnav elements (optional) following the same indexes as above
     */
    public function getPrimaryClient($base_uri)
    {
        return $this->getNavItems($base_uri, 'nav_client');
    }

    /**
     * Retrieves the navigation for unauthenticated clients
     *
     * @param string $base_uri The base_uri for the user not logged in
     * @param string $base_user_uri The base_uri for a logged in user
     * @return array An array of main navigation elements in key/value pairs
     *  where each key is the URI and each value is an array representing that element including:
     *
     *  - name The name of the link
     *  - active True if the element is active
     *  - sub An array of subnav elements (optional) following the same indexes as above
     */
    public function getPrimaryPublic($base_uri, $base_user_uri)
    {
        ##
        # TODO: Use base uri for non-plugin navigation items $base_uri and $base_user_uri for plugin uris
        ##
        return $this->getNavItems($base_uri, 'nav_public');
    }

    /**
     * Retrieves the navigation for unauthenticated clients
     *
     * @param string $base_uri The base_uri for the user
     * @return array An array of main navigation elements in key/value pairs
     *  where each key is the URI and each value is an array representing that element including:
     *
     *  - name The name of the link
     *  - active True if the element is active
     *  - sub An array of subnav elements (optional) following the same indexes as above
     */
    private function getNavItems($base_uri, $location)
    {
        Loader::loadModels($this, ['PluginManager']);
        $loaded_plugins = [];
        $ordered_nav_items = [];
        $levels = ['primary', 'secondary'];
        foreach ($levels as $level) {
            $filters = [
                'company_id' => Configure::get('Blesta.company_id'),
                'child' => $level == 'secondary',
                'location' => $location,
                'enabled' => 1
            ];
            // Get navigation items and their actions
            $nav_items = $this->getNavRecord($filters)->fetchAll();

            foreach ($nav_items as $nav_item) {
                // Prepend the base_uri for internal links
                if (!str_contains($nav_item->url, '://')) {
                    $options = unserialize($nav_item->options ?? '');
                    if (
                        $nav_item->plugin_id
                        && isset($this->base_uris['public'])
                        && $location != 'nav_staff'
                        && !isset($options['base_uri'])
                    ) {
                        // Make all plugin links relative to the public uri
                        $nav_item->url = $this->base_uris['public'] . $nav_item->url;
                    } elseif ($nav_item->plugin_id && isset($options['base_uri']) && $location != 'nav_staff') {
                        // Override plugin base uri
                        $nav_item->url = $this->base_uris[$options['base_uri']] . $nav_item->url;
                    } else {
                        $nav_item->url = $base_uri . $nav_item->url;
                    }
                }

                // Load the language file for the plugin associated with this navigation item
                if (isset($nav_item->plugin_id)
                    && !in_array($nav_item->plugin_id, $loaded_plugins)
                    && ($plugin = $this->PluginManager->get($nav_item->plugin_id))
                ) {
                    Language::loadLang(
                        [$plugin->dir . '_plugin'],
                        null,
                        PLUGINDIR . $plugin->dir . DS . 'language' . DS
                    );

                    $loaded_plugins[] = $nav_item->plugin_id;
                }

                // Set options on the navigation item
                $nav_item->active = false;
                $language = Language::_($nav_item->name, true);
                $nav_item->name = empty($language) ? $nav_item->name : $language;
                if (!empty($nav_item->options)) {
                    $nav_item->options = unserialize($nav_item->options);
                    foreach ($nav_item->options as $key => $option) {
                        if (!property_exists($nav_item, $key)) {
                            $nav_item->{$key} = $option;
                        }
                    }
                }

                // Add navigation item or subitem
                if ($level == 'primary') {
                    $ordered_nav_items[$nav_item->id] = (array)$nav_item;
                } elseif (isset($ordered_nav_items[$nav_item->parent_id])) {
                    $sub_location = isset($nav_item->options['sub_as_secondary'])
                        && $nav_item->options['sub_as_secondary']
                            ? 'secondary'
                            : 'sub';
                    $ordered_nav_items[$nav_item->parent_id][$sub_location][] = (array)$nav_item;
                }
            }
        }

        // Sort primary navigation items by the order property
        usort($ordered_nav_items, function ($a, $b) {
            return $a['order'] - $b['order'];
        });

        // Key all values by url
        $keyed_nav_items = [];
        foreach ($ordered_nav_items as $ordered_nav_item) {
            if (isset($ordered_nav_item['sub'])) {
                $keyed_sub_items = [];
                foreach ($ordered_nav_item['sub'] as $subitem) {
                    $keyed_sub_items[$subitem['url']] = $subitem;
                }

                $ordered_nav_item['sub'] = $keyed_sub_items;
            }

            if (isset($ordered_nav_item['secondary'])) {
                foreach ($ordered_nav_item['secondary'] as &$secondary_item) {
                    $language = Language::_($secondary_item['name'], true);
                    $secondary_item['name'] = empty($language) ? $secondary_item['name'] : $language;
                }
            }

            $keyed_nav_items[$ordered_nav_item['url']] = $ordered_nav_item;
        }

        return $keyed_nav_items;
    }

    /**
     *
     * @param array $filters A list of parameters by which to filter the results, including:
     *
     *  - action_id The ID of the action to which the navigation item is attached
     *  - company_id The ID of the company for which the navigation item is display
     *  - plugin_id The ID of the plugin for which the navigation item was created
     *  - url The URL of the navigation item's action
     *  - location The location in which an action is allowed to be displayed
     *  - child Whether to fetch navigation items with parents (true to fetch child items,
     *      false to fetch parent items)
     * @param array $order The sort and order conditions (e.g. array('sort_field'=>"ASC"), optional)
     * @return Record A partial record object used to fetch navigation items and their actions from the database
     */
    private function getNavRecord(array $filters, array $order = ['navigation_items.order' => 'ASC'])
    {
        // Get navigation items and their actions
        $fields = [
            'actions.*',
            'actions.id' => 'action_id',
            'navigation_items.id',
            'navigation_items.parent_id',
            'navigation_items.order',
            'parent_actions.url' => 'parent_url',
        ];
        $this->Record->select($fields)->
            from('navigation_items')->
            innerJoin('actions', 'actions.id', '=', 'navigation_items.action_id', false)->
            leftJoin(
                ['navigation_items' => 'parent_items'],
                'parent_items.id',
                '=',
                'navigation_items.parent_id',
                false
            )->
            leftJoin(['actions' => 'parent_actions'], 'parent_actions.id', '=', 'parent_items.action_id', false);

        // Filter on `navigation_items` fields
        $nav_value_filters = [];
        foreach ($nav_value_filters as $nav_filter) {
            if (isset($filters[$nav_filter])) {
                $this->Record->where('navigation_items.' . $nav_filter, '=', $filters[$nav_filter]);
            }
        }

        // Filter on `actions` fields
        $action_value_filters = ['url', 'location', 'enabled', 'company_id', 'plugin_id'];
        foreach ($action_value_filters as $action_filter) {
            if (isset($filters[$action_filter])) {
                $this->Record->where('actions.' . $action_filter, '=', $filters[$action_filter]);
            }
        }

        // Filter on action ID
        if (isset($filters['action_id'])) {
            $this->Record->where('actions.id', '=', $filters['action_id']);
        }

        // Filter on whether the navigation item is a parent or child
        if (isset($filters['child'])) {
            $this->Record->where('navigation_items.parent_id', ($filters['child'] ? '!=' : '='), null);
        }

        return $this->Record->order($order);
    }

    /**
     * Retrieves the navigation for company settings
     *
     * @param string $base_uri The base_uri for the currently logged in user
     * @return array A numerically-indexed array of the company settings
     *  navigation where each element contains an array which includes:
     *
     *  - name The name of the element
     *  - class The CSS class name for the element
     *  - uri The URI for the element
     *  - children An array of child elements which follow the same indexes as above
     */
    public function getCompany($base_uri)
    {
        $nav = [
            [
                'name' => $this->_('Navigation.getcompany.nav_general'),
                'class' => '',
                'icon' => 'tools',
                'uri' => $base_uri . 'settings/company/general/',
                'children' => [
                    [
                        'name' => $this->_('Navigation.getcompany.nav_general_localization'),
                        'uri' => $base_uri . 'settings/company/general/localization/'
                    ],
                    [
                        'name' => $this->_('Navigation.getcompany.nav_general_international'),
                        'uri' => $base_uri . 'settings/company/general/international/'
                    ],
                    [
                        'name' => $this->_('Navigation.getcompany.nav_general_encryption'),
                        'uri' => $base_uri . 'settings/company/general/encryption/'
                    ],
                    [
                        'name' => $this->_('Navigation.getcompany.nav_general_contacttypes'),
                        'uri' => $base_uri . 'settings/company/general/contacttypes/'
                    ],
                    [
                        'name' => $this->_('Navigation.getcompany.nav_general_marketing'),
                        'uri' => $base_uri . 'settings/company/general/marketing/'
                    ],
                    [
                        'name' => $this->_('Navigation.getcompany.nav_general_smart_search'),
                        'uri' => $base_uri . 'settings/company/general/smartsearch/'
                    ],
                    [
                        'name' => $this->_('Navigation.getcompany.nav_general_humanverification'),
                        'uri' => $base_uri . 'settings/company/general/humanverification/'
                    ]
                ]
            ],
            [
                'name' => $this->_('Navigation.getcompany.nav_lookandfeel'),
                'class' => '',
                'icon' => 'pencil-ruler',
                'uri' => $base_uri . 'settings/company/themes/',
                'children' => [
                    [
                        'name' => $this->_('Navigation.getcompany.nav_lookandfeel_themes'),
                        'uri' => $base_uri . 'settings/company/themes/'
                    ],
                    [
                        'name' => $this->_('Navigation.getcompany.nav_lookandfeel_template'),
                        'uri' => $base_uri . 'settings/company/lookandfeel/template/'
                    ],
                    [
                        'name' => $this->_('Navigation.getcompany.nav_lookandfeel_layout'),
                        'uri' => $base_uri . 'settings/company/lookandfeel/layout/'
                    ],
                    [
                        'name' => $this->_('Navigation.getcompany.nav_lookandfeel_customize'),
                        'uri' => $base_uri . 'settings/company/lookandfeel/customize/'
                    ],
                    [
                        'name' => $this->_('Navigation.getcompany.nav_lookandfeel_navigation'),
                        'uri' => $base_uri . 'settings/company/lookandfeel/navigation/'
                    ],
                    [
                        'name' => $this->_('Navigation.getcompany.nav_lookandfeel_actions'),
                        'uri' => $base_uri . 'settings/company/lookandfeel/actions/'
                    ]
                ]
            ],
            [
                'name' => $this->_('Navigation.getcompany.nav_automation'),
                'class' => '',
                'icon' => 'clock',
                'uri' => $base_uri . 'settings/company/automation/'
            ],
            [
                'name' => $this->_('Navigation.getcompany.nav_billing'),
                'class' => '',
                'icon' => 'calculator',
                'uri' => $base_uri . 'settings/company/billing/',
                'children' => [
                    [
                        'name' => $this->_('Navigation.getcompany.nav_billing_invoices'),
                        'uri' => $base_uri . 'settings/company/billing/invoices/'
                    ],
                    [
                        'name' => $this->_('Navigation.getcompany.nav_billing_custominvoice'),
                        'uri' => $base_uri . 'settings/company/billing/customization/'
                    ],
                    [
                        'name' => $this->_('Navigation.getcompany.nav_billing_deliverymethods'),
                        'uri' => $base_uri . 'settings/company/billing/deliverymethods/'
                    ],
                    [
                        'name' => $this->_('Navigation.getcompany.nav_billing_latefees'),
                        'uri' => $base_uri . 'settings/company/billing/latefees/'
                    ],
                    [
                        'name' => $this->_('Navigation.getcompany.nav_billing_acceptedtypes'),
                        'uri' => $base_uri . 'settings/company/billing/acceptedtypes/'
                    ],
                    [
                        'name' => $this->_('Navigation.getcompany.nav_billing_notices'),
                        'uri' => $base_uri . 'settings/company/billing/notices/'
                    ],
                    [
                        'name' => $this->_('Navigation.getcompany.nav_billing_coupons'),
                        'uri' => $base_uri . 'settings/company/billing/coupons/'
                    ]
                ]
            ],
            [
                'name' => $this->_('Navigation.getcompany.nav_modules'),
                'class' => '',
                'icon' => 'puzzle-piece',
                'uri' => $base_uri . 'settings/company/modules/'
            ],
            [
                'name' => $this->_('Navigation.getcompany.nav_messengers'),
                'class' => '',
                'icon' => 'comment-dots',
                'uri' => $base_uri . 'settings/company/messengers/',
                'children' => [
                    [
                        'name' => $this->_('Navigation.getcompany.nav_messengers_messengers'),
                        'uri' => $base_uri . 'settings/company/messengers/installed/'
                    ],
                    [
                        'name' => $this->_('Navigation.getcompany.nav_messengers_configuration'),
                        'uri' => $base_uri . 'settings/company/messengers/configuration/'
                    ],
                    [
                        'name' => $this->_('Navigation.getcompany.nav_messengers_templates'),
                        'uri' => $base_uri . 'settings/company/messengers/templates/'
                    ]
                ]
            ],
            [
                'name' => $this->_('Navigation.getcompany.nav_gateways'),
                'class' => '',
                'icon' => 'university',
                'uri' => $base_uri . 'settings/company/gateways/'
            ],
            [
                'name' => $this->_('Navigation.getcompany.nav_taxes'),
                'class' => '',
                'icon' => 'money-bill-wave',
                'uri' => $base_uri . 'settings/company/taxes/',
                'children' => [
                    [
                        'name' => $this->_('Navigation.getcompany.nav_taxes_basictax'),
                        'uri' => $base_uri . 'settings/company/taxes/basic/'
                    ],
                    [
                        'name' => $this->_('Navigation.getcompany.nav_taxes_taxrules'),
                        'uri' => $base_uri . 'settings/company/taxes/rules/'
                    ]
                ]
            ],
            [
                'name' => $this->_('Navigation.getcompany.nav_emails'),
                'class' => '',
                'icon' => 'envelope-open-text',
                'uri' => $base_uri . 'settings/company/emails/',
                'current' => false,
                'children' => [
                    [
                        'name' => $this->_('Navigation.getcompany.nav_emails_templates'),
                        'uri' => $base_uri . 'settings/company/emails/templates/'
                    ],
                    [
                        'name' => $this->_('Navigation.getcompany.nav_emails_mail'),
                        'uri' => $base_uri . 'settings/company/emails/mail/'
                    ],
                    [
                        'name' => $this->_('Navigation.getcompany.nav_emails_signatures'),
                        'uri' => $base_uri . 'settings/company/emails/signatures/'
                    ]
                ]
            ],
            [
                'name' => $this->_('Navigation.getcompany.nav_clientoptions'),
                'class' => '',
                'icon' => 'sliders-h',
                'uri' => $base_uri . 'settings/company/clientoptions/',
                'children' => [
                    [
                        'name' => $this->_('Navigation.getcompany.nav_clientoptions_general'),
                        'uri' => $base_uri . 'settings/company/clientoptions/general/'
                    ],
                    [
                        'name' => $this->_('Navigation.getcompany.nav_clientoptions_customfields'),
                        'uri' => $base_uri . 'settings/company/clientoptions/customfields/'
                    ],
                    [
                        'name' => $this->_('Navigation.getcompany.nav_clientoptions_requiredfields'),
                        'uri' => $base_uri . 'settings/company/clientoptions/requiredfields/'
                    ]
                ]
            ],
            [
                'name' => $this->_('Navigation.getcompany.nav_currencies'),
                'class' => '',
                'icon' => 'dollar-sign',
                'uri' => $base_uri . 'settings/company/currencies/',
                'children' => [
                    [
                        'name' => $this->_('Navigation.getcompany.nav_currency_currencysetup'),
                        'uri' => $base_uri . 'settings/company/currencies/setup/'
                    ],
                    [
                        'name' => $this->_('Navigation.getcompany.nav_currency_active'),
                        'uri' => $base_uri . 'settings/company/currencies/active/'
                    ]
                ]
            ],
            [
                'name' => $this->_('Navigation.getcompany.nav_plugins'),
                'class' => '',
                'icon' => 'plug',
                'uri' => $base_uri . 'settings/company/plugins/'
            ],
            [
                'name' => $this->_('Navigation.getcompany.nav_groups'),
                'class' => '',
                'icon' => 'user-friends',
                'uri' => $base_uri . 'settings/company/groups/'
            ],
            [
                'name' => $this->_('Navigation.getcompany.nav_feeds'),
                'class' => '',
                'icon' => 'rss',
                'uri' => $base_uri . 'settings/company/feeds/',
                'children' => [
                    [
                        'name' => $this->_('Navigation.getcompany.nav_feeds_general'),
                        'uri' => $base_uri . 'settings/company/feeds/index/'
                    ],
                    [
                        'name' => $this->_('Navigation.getcompany.nav_feeds_settings'),
                        'uri' => $base_uri . 'settings/company/feeds/settings/'
                    ],
                ]
            ]
        ];

        return $nav;
    }

    /**
     * Retrieves the navigation for system settings
     *
     * @param string $base_uri The base_uri for the currently logged in user
     * @return array A numerically-indexed array of the system settings
     *  navigation where each element contains an array which includes:
     *
     *  - name The name of the element
     *  - class The CSS class name for the element
     *  - uri The URI for the element
     *  - children An array of child elements which follow the same indexes as above
     */
    public function getSystem($base_uri)
    {
        $nav = [
            [
                'name' => $this->_('Navigation.getsystem.nav_general'),
                'class' => '',
                'icon' => 'tools',
                'uri' => $base_uri . 'settings/system/general/',
                'children' => [
                    [
                        'name' => $this->_('Navigation.getsystem.nav_general_basic'),
                        'uri' => $base_uri . 'settings/system/general/basic/'
                    ],
                    [
                        'name' => $this->_('Navigation.getsystem.nav_general_geoip'),
                        'uri' => $base_uri . 'settings/system/general/geoip/'
                    ],
                    [
                        'name' => $this->_('Navigation.getsystem.nav_general_maintenance'),
                        'uri' => $base_uri . 'settings/system/general/maintenance/'
                    ],
                    [
                        'name' => $this->_('Navigation.getsystem.nav_general_license'),
                        'uri' => $base_uri . 'settings/system/general/license/'
                    ],
                    [
                        'name' => $this->_('Navigation.getsystem.nav_general_paymenttypes'),
                        'uri' => $base_uri . 'settings/system/general/paymenttypes/'
                    ]
                ]
            ],
            [
                'name' => $this->_('Navigation.getsystem.nav_automation'),
                'class' => '',
                'icon' => 'clock',
                'uri' => $base_uri . 'settings/system/automation/'
            ],
            [
                'name' => $this->_('Navigation.getsystem.nav_companies'),
                'class' => '',
                'icon' => 'building',
                'uri' => $base_uri . 'settings/system/companies'
            ],
            [
                'name' => $this->_('Navigation.getsystem.nav_backup'),
                'class' => '',
                'icon' => 'hdd',
                'uri' => $base_uri . 'settings/system/backup/',
                'children' => [
                    [
                        'name' => $this->_('Navigation.getsystem.nav_backup_index'),
                        'uri' => $base_uri . 'settings/system/backup/index/'
                    ],
                    [
                        'name' => $this->_('Navigation.getsystem.nav_backup_ftp'),
                        'uri' => $base_uri . 'settings/system/backup/ftp/'
                    ],
                    [
                        'name' => $this->_('Navigation.getsystem.nav_backup_amazon'),
                        'uri' => $base_uri . 'settings/system/backup/amazon/'
                    ]
                ]
            ],
            [
                'name' => $this->_('Navigation.getsystem.nav_staff'),
                'class' => '',
                'icon' => 'user-tie',
                'uri' => $base_uri . 'settings/system/staff/',
                'children' => [
                    [
                        'name' => $this->_('Navigation.getsystem.nav_staff_manage'),
                        'uri' => $base_uri . 'settings/system/staff/manage/'
                    ],
                    [
                        'name' => $this->_('Navigation.getsystem.nav_staff_groups'),
                        'uri' => $base_uri . 'settings/system/staff/groups/'
                    ]
                ]
            ],
            [
                'name' => $this->_('Navigation.getsystem.nav_api'),
                'class' => '',
                'icon' => 'code',
                'uri' => $base_uri . 'settings/system/api/'
            ],
            [
                'name' => $this->_('Navigation.getsystem.nav_upgrade'),
                'class' => '',
                'icon' => 'cloud-download-alt',
                'uri' => $base_uri . 'settings/system/upgrade/'
            ],
            [
                'name' => $this->_('Navigation.getsystem.nav_help'),
                'class' => '',
                'icon' => 'life-ring',
                'uri' => $base_uri . 'settings/system/help/',
                'children' => [
                    [
                        'name' => $this->_('Navigation.getsystem.nav_help_index'),
                        'uri' => $base_uri . 'settings/system/help/index/'
                    ],
                    [
                        'name' => $this->_('Navigation.getsystem.nav_help_notes'),
                        'uri' => 'https://docs.blesta.com/display/support/Releases',
                        'attributes' => [
                            'target' => 'blank'
                        ]
                    ],
                    [
                        'name' => $this->_('Navigation.getsystem.nav_help_about'),
                        'uri' => $base_uri . 'settings/system/help/credits/',
                        'attributes' => ['rel' => 'modal']
                    ]
                ]
            ],
            [
                'name' => $this->_('Navigation.getsystem.nav_marketplace'),
                'class' => '',
                'icon' => 'shopping-cart',
                'uri' => Configure::get('Blesta.marketplace_url'),
                'attributes' => ['target' => '_blank']
            ]
        ];
        return $nav;
    }

    /**
     * Fetches all search options available to the current company
     *
     * @param string $base_uri The base_uri for the currently logged in user
     * @return array An array of search items in key/value pairs, where each
     *  key is the search type and each value is the language for the search type
     */
    public function getSearchOptions($base_uri = null)
    {
        $options = [
            'smart' => $this->_('Navigation.getsearchoptions.smart'),
            'clients' => $this->_('Navigation.getsearchoptions.clients'),
            'invoices' => $this->_('Navigation.getsearchoptions.invoices'),
            'transactions' => $this->_('Navigation.getsearchoptions.transactions'),
            'services' => $this->_('Navigation.getsearchoptions.services'),
            'packages' => $this->_('Navigation.getsearchoptions.packages')
        ];

        // Allow custom search options to be appended to the list of search options
        $eventFactory = $this->getFromContainer('util.events');
        $eventListener = $eventFactory->listener();
        $eventListener->register('Navigation.getSearchOptions');
        $event = $eventListener->trigger(
            $eventFactory->event('Navigation.getSearchOptions', compact('options', 'base_uri'))
        );

        $params = $event->getParams();

        if (isset($params['options'])) {
            $options = $params['options'];
        }

        return $options;
    }

    /**
     * Returns all plugin navigation for the requested location
     *
     * @param string $location The location to fetch plugin navigation for
     * @return array An array of plugin navigation
     */
    public function getPluginNav($location)
    {
        if (!isset($this->PluginManager)) {
            Loader::loadModels($this, ['PluginManager']);
        }

        return $this->PluginManager->getActions(Configure::get('Blesta.company_id'), $location, true);
    }

    /**
     * Adds a URI referenced by its label
     *
     * @param string $label The unique label to set for the URI
     * @param string $uri The URI
     * @return this
     */
    public function baseUri($label, $uri)
    {
        $this->base_uris[$label] = $uri;
        return $this;
    }

    /**
     * Retrieves the base URI to use for a navigation element.
     * Defaults to $base_uri if the element's base URI is unknown.
     *
     * @param string $base_uri The current base URI
     * @param string $element_base_uri The element's defined base URI
     * @return string The base URI for the current element
     */
    private function getElementBaseUri($base_uri, $element_base_uri = null)
    {
        return (isset($element_base_uri) ? $this->getBaseUri($element_base_uri) : $base_uri);
    }

    /**
     * Retrieves the base URI matching the given label.
     * If not found, returns back the given label.
     *
     * @param string $label The base URI label
     * @return string The base URI
     */
    private function getBaseUri($label)
    {
        return (array_key_exists($label, (array)$this->base_uris) ? $this->base_uris[$label] : $label);
    }
}
