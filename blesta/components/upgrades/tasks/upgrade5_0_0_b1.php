<?php

use Blesta\Core\Util\Tax\EuropeTax;

/**
 * Upgrades to version 5.0.0-b1
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade5_0_0B1 extends UpgradeUtil
{
    /**
     * @var array An array of all tasks completed
     */
    private $tasks = [];

    /**
     * @var int The order number of the next navigation item to insert
     */
    private $navigation_index = 0;

    /**
     * @var array An array of all system companies
     */
    private $companies = [];

    /**
     * Setup
     */
    public function __construct()
    {
        Configure::load('blesta');
        Loader::loadComponents($this, ['Record']);
        Loader::loadModels($this, ['Companies']);
        $this->companies = $this->Companies->getAll();
    }

    /**
     * Returns a numerically indexed array of tasks to execute for the upgrade process
     *
     * @return array A numerically indexed array of tasks to execute for the upgrade process
     */
    public function tasks()
    {
        return [
            'setConfigSessionNames',
            'removeDeprecatedLibraries',
            'addEuropeTaxSettings',
            'addActionsTable',
            'addNavigationItemsTable',
            'convertExistingCoreActions',
            'convertExistingPluginActions',
            'addActionNavigationPagePermissions',
            'updatePluginCardsFields',
            'updatePaginationConfiguration',
            'updateClientThemes',
            'migrate2Checkout',
            'migrateLogo',
            'installThemes',
            'addCustomizeSettingsPermission',
            'removePackageAndGroupDescriptionsNames',
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
     * Sets config values for the session name and cookie session name
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function setConfigSessionNames($undo = false)
    {
        if ($undo) {
            // Do Nothing
        } else {
            if (file_exists(CONFIGDIR . 'blesta.php')) {
                // Set session name
                $this->addConfig(CONFIGDIR . 'blesta.php', 'Blesta.session_name', 'blesta_sid');

                // Set cookie session name
                $this->addConfig(CONFIGDIR . 'blesta.php', 'Blesta.cookie_name', 'blesta_csid');
            }
        }
    }

    /**
     * Removes unused code and deprecated libraries
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function removeDeprecatedLibraries($undo = false)
    {
        if ($undo) {
            // Do Nothing
        } else {
            $directories = [
                VIEWDIR . 'admin' . DS . 'default' . DS . 'javascript' . DS . 'jqplot',
                COMPONENTDIR . 'gateways' . DS . 'nonmerchant' . DS . '_2checkout',
                COMPONENTDIR . 'acl',
                COMPONENTDIR . 'input',
                COMPONENTDIR . 'events',
                COMPONENTDIR . 'json',
                COMPONENTDIR . 'pricing_presenter',
                COMPONENTDIR . 'record',
                COMPONENTDIR . 'session',
                HELPERDIR . 'areyouahuman',
                HELPERDIR . 'date',
                HELPERDIR . 'form',
                HELPERDIR . 'html',
                HELPERDIR . 'javascript',
                HELPERDIR . 'pagination',
                HELPERDIR . 'recaptcha',
                HELPERDIR . 'xml',
                VENDORDIR . 'amazons3',
                VENDORDIR . 'binary-to-text-php',
                VENDORDIR . 'ckeditor',
                VENDORDIR . 'consoleation',
                VENDORDIR . 'fugue_icons',
                VENDORDIR . 'fullcalendar' . DS . 'fullcalendar',
                VENDORDIR . 'fullcalendar' . DS . 'jquery',
                VENDORDIR . 'h2o',
                VENDORDIR . 'jqplot',
                VENDORDIR . 'json',
                VENDORDIR . 'maxmind' . DS . 'geoip',
                VENDORDIR . 'parsedown',
                VENDORDIR . 'phpass',
                VENDORDIR . 'phpunit' . DS . 'dbunit',
                VENDORDIR . 'phpseclib' . DS . 'Crypt',
                VENDORDIR . 'phpseclib' . DS . 'File',
                VENDORDIR . 'phpseclib' . DS . 'Math',
                VENDORDIR . 'phpseclib' . DS . 'Net',
                VENDORDIR . 'phpseclib' . DS . 'System',
                VENDORDIR . 'php-markdown',
                VENDORDIR . 'sshterm-applet',
                VENDORDIR . 'swiftmailer' . DS . 'lib',
                VENDORDIR . 'tcpdf',
                VENDORDIR . 'vcard'
            ];

            $files = [
                VIEWDIR . 'admin' . DS . 'default' . DS . 'javascript' . DS . 'jquery-1.8.3.min.js',
                VIEWDIR . 'admin' . DS . 'default' . DS . 'javascript' . DS . 'jquery.qtip.js',
                VIEWDIR . 'admin' . DS . 'default' . DS . 'fonts' . DS . 'fontawesome-webfont.eot',
                VIEWDIR . 'admin' . DS . 'default' . DS . 'fonts' . DS . 'fontawesome-webfont.svg',
                VIEWDIR . 'admin' . DS . 'default' . DS . 'fonts' . DS . 'fontawesome-webfont.ttf',
                VIEWDIR . 'admin' . DS . 'default' . DS . 'fonts' . DS . 'fontawesome-webfont.woff',
                VIEWDIR . 'admin' . DS . 'default' . DS . 'fonts' . DS . 'fontawesome-webfont.woff2',
                VIEWDIR . 'admin' . DS . 'default' . DS . 'fonts' . DS . 'FontAwesome.otf',
                VIEWDIR . 'client' . DS . 'bootstrap' . DS . 'fonts' . DS . 'fontawesome-webfont.eot',
                VIEWDIR . 'client' . DS . 'bootstrap' . DS . 'fonts' . DS . 'fontawesome-webfont.svg',
                VIEWDIR . 'client' . DS . 'bootstrap' . DS . 'fonts' . DS . 'fontawesome-webfont.ttf',
                VIEWDIR . 'client' . DS . 'bootstrap' . DS . 'fonts' . DS . 'fontawesome-webfont.woff',
                VIEWDIR . 'client' . DS . 'bootstrap' . DS . 'fonts' . DS . 'fontawesome-webfont.woff2',
                VIEWDIR . 'client' . DS . 'bootstrap' . DS . 'fonts' . DS . 'FontAwesome.otf',
                DOCROOTDIR . 'lib' . DS . 'cache.php',
                DOCROOTDIR . 'lib' . DS . 'configure.php',
                DOCROOTDIR . 'lib' . DS . 'controller.php',
                DOCROOTDIR . 'lib' . DS . 'dispatcher.php',
                DOCROOTDIR . 'lib' . DS . 'language.php',
                DOCROOTDIR . 'lib' . DS . 'loader.php',
                DOCROOTDIR . 'lib' . DS . 'model.php',
                DOCROOTDIR . 'lib' . DS . 'router.php',
                DOCROOTDIR . 'lib' . DS . 'stdlib.php',
                DOCROOTDIR . 'lib' . DS . 'unknown_exception.php',
                DOCROOTDIR . 'lib' . DS . 'view.php',
                VENDORDIR . 'html2text' . DS . 'html2text.class.php',
                VENDORDIR . 'phpseclib' . DS . 'openssl.cnf',
                VENDORDIR . 'swiftmailer' . DS . 'CHANGES',
                VENDORDIR . 'swiftmailer' . DS . 'LICENSE',
                VENDORDIR . 'swiftmailer' . DS . 'README',
                VENDORDIR . 'swiftmailer' . DS . 'VERSION'
            ];

            // Remove directories
            foreach ($directories as $directory) {
                $this->deleteFiles($directory);
            }

            // Remove files
            foreach ($files as $file) {
                $this->deleteFiles($file);
            }
        }
    }

    /**
     * Deletes all the files and subdirectories of a given directory
     *
     * @param string $directory The directory to delete
     */
    private function deleteFiles($directory)
    {
        try {
            if (is_file($directory)) {
                unlink($directory);
            } elseif (is_dir($directory)) {
                foreach (array_diff(scandir($directory), ['.', '..']) as $file) {
                    if (is_dir($directory . DS . $file)) {
                        $this->deleteFiles($directory . DS . $file);
                    } else {
                        unlink($directory . DS . $file);
                    }
                }

                rmdir($directory);
            }
        } catch (Exception $e) {
            // Do nothing
        }
    }

    /**
     * Add the text_color column to the plugin_cards table
     *
     * @param bool $undo True to undo the change, false to perform the change
     */
    private function updatePluginCardsFields($undo = false)
    {
        if ($undo) {
            $this->Record->query('ALTER TABLE `plugin_cards` DROP `text_color`;');
        } else {
            // Add the column
            $this->Record->query("ALTER TABLE `plugin_cards`
                ADD `text_color` VARCHAR( 255 ) NULL DEFAULT '#343A40' AFTER `callback_type` ;");
        }
    }

    /**
     * Update pagination configuration
     *
     * @param bool $undo True to undo the change, false to perform the change
     */
    private function updatePaginationConfiguration($undo = false)
    {
        if ($undo) {
            if (file_exists(CONFIGDIR . 'blesta.php')) {
                // Update pagination configuration
                $pagination_client = [
                    'show' => 'if_needed',
                    'total_results' => 0,
                    'pages_to_show' => 5,
                    'results_per_page' => Configure::get('Blesta.results_per_page'),
                    'uri' => WEBDIR,
                    'uri_labels' => [
                        'page' => 'p',
                        'per_page' => 'pp'
                    ],
                    'navigation' => [
                        'surround' => [
                            'attributes' => [
                                'class' => 'pagination pagination-sm'
                            ]
                        ],
                        'current' => [
                            'link' => true,
                            'attributes' => ['class' => 'active']
                        ],
                        'first' => [
                            'show' => 'always'
                        ],
                        'prev' => [
                            'show' => 'always'
                        ],
                        'next' => [
                            'show' => 'always'
                        ],
                        'last' => [
                            'show' => 'always',
                            'attributes' => ['class' => 'next']
                        ]
                    ],
                    'params' => []
                ];

                $pagination_ajax = [
                    'merge_get' => false,
                    'navigation' => [
                        'current' => [
                            'link_attributes' => ['class' => 'ajax']
                        ],
                        'first' => [
                            'link_attributes' => ['class' => 'ajax']
                        ],
                        'prev' => [
                            'link_attributes' => ['class' => 'ajax']
                        ],
                        'next' => [
                            'link_attributes' => ['class' => 'ajax']
                        ],
                        'last' => [
                            'link_attributes' => ['class' => 'ajax']
                        ],
                        'numerical' => [
                            'link_attributes' => ['class' => 'ajax']
                        ]
                    ]
                ];

                $this->editConfig(CONFIGDIR . 'blesta.php', 'Blesta.pagination_client', $pagination_client);
                $this->editConfig(CONFIGDIR . 'blesta.php', 'Blesta.pagination_ajax', $pagination_ajax);
            }
        } else {
            if (file_exists(CONFIGDIR . 'blesta.php')) {
                // Update pagination configuration
                $pagination_client = [
                    'show' => 'if_needed',
                    'total_results' => 0,
                    'pages_to_show' => 5,
                    'results_per_page' => Configure::get('Blesta.results_per_page'),
                    'uri' => WEBDIR,
                    'uri_labels' => [
                        'page' => 'p',
                        'per_page' => 'pp'
                    ],
                    'navigation' => [
                        'surround' => [
                            'attributes' => [
                                'class' => 'pagination pagination-sm'
                            ]
                        ],
                        'current' => [
                            'link' => true,
                            'attributes' => ['class' => 'page-item active']
                        ],
                        'first' => [
                            'show' => 'always',
                            'attributes' => ['class' => 'page-item']
                        ],
                        'prev' => [
                            'show' => 'always',
                            'attributes' => ['class' => 'page-item']
                        ],
                        'next' => [
                            'show' => 'always',
                            'attributes' => ['class' => 'page-item']
                        ],
                        'last' => [
                            'show' => 'always',
                            'attributes' => ['class' => 'page-item next']
                        ],
                        'numerical' => [
                            'attributes' => ['class' => 'page-item']
                        ]
                    ],
                    'params' => []
                ];

                $pagination_ajax = [
                    'merge_get' => false,
                    'navigation' => [
                        'current' => [
                            'link_attributes' => ['class' => 'page-link ajax']
                        ],
                        'first' => [
                            'link_attributes' => ['class' => 'page-link ajax']
                        ],
                        'prev' => [
                            'link_attributes' => ['class' => 'page-link ajax']
                        ],
                        'next' => [
                            'link_attributes' => ['class' => 'page-link ajax']
                        ],
                        'last' => [
                            'link_attributes' => ['class' => 'page-link ajax']
                        ],
                        'numerical' => [
                            'link_attributes' => ['class' => 'page-link ajax']
                        ]
                    ]
                ];

                $this->editConfig(CONFIGDIR . 'blesta.php', 'Blesta.pagination_client', $pagination_client);
                $this->editConfig(CONFIGDIR . 'blesta.php', 'Blesta.pagination_ajax', $pagination_ajax);
            }
        }
    }

    /**
     * Adds the new european tax settings to all system companies
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addEuropeTaxSettings($undo = false)
    {
        if ($undo) {
            $this->Record->query("DELETE FROM `company_settings` WHERE `key`='enable_eu_vat';");
            $this->Record->query("DELETE FROM `company_settings` WHERE `key`='tax_exempt_eu_vat';");
            $this->Record->query("DELETE FROM `company_settings` WHERE `key`='tax_home_eu_vat';");
        } else {
            // Fetch all companies
            $eu_countries = (array) (new EuropeTax())->getCountries();

            foreach ($this->companies as $company) {
                // Get company country
                $country = $this->Companies->getSetting($company->id, 'country');
                $country = isset($country->value) ? $country->value : '';

                $this->Record->query(
                    "INSERT INTO `company_settings` (`key`, `company_id`, `value`)
                        VALUES ('enable_eu_vat', ?, 'false')
                        ON DUPLICATE KEY UPDATE value = 'false';",
                    $company->id
                );
                $this->Record->query(
                    "INSERT INTO `company_settings` (`key`, `company_id`, `value`)
                        VALUES ('tax_exempt_eu_vat', ?, 'false')
                        ON DUPLICATE KEY UPDATE value = 'false';",
                    $company->id
                );
                $vat_home_country = (in_array($country, $eu_countries) ? $country : '');
                $this->Record->query(
                    "INSERT INTO `company_settings` (`key`, `company_id`, `value`)
                        VALUES ('tax_home_eu_vat', ?, '" . $vat_home_country . "')
                        ON DUPLICATE KEY UPDATE value = '" . $vat_home_country . "';",
                    $company->id
                );
            }
            reset($this->companies);
        }
    }

    /**
     * Adds a new `actions` table
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addActionsTable($undo = false)
    {
        if ($undo) {
            $this->Record->drop('actions');
        } else {
            $this->Record
                ->setField('id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])
                ->setField(
                    'location',
                    [
                        'type' => 'enum',
                        'size' => "'nav_public','nav_client','nav_staff',
                            'widget_client_home',
                            'widget_staff_home','widget_staff_client',
                            'widget_staff_billing','action_staff_client'",
                        'default' => 'nav_staff'
                    ]
                )
                ->setField('url', ['type' => 'varchar', 'size' => 255])
                ->setField('name', ['type' => 'varchar', 'size' => 128])
                ->setField('options', ['type' => 'text', 'is_null' => true, 'default' => null])
                ->setField('company_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])
                ->setField(
                    'plugin_id',
                    ['type' => 'int', 'size' => 10, 'unsigned' => true, 'is_null' => true, 'default' => null]
                )
                ->setField('editable', ['type' => 'tinyint', 'size' => 1, 'default' => 1])
                ->setField('enabled', ['type' => 'tinyint', 'size' => 1, 'default' => 1])
                ->setKey(['id'], 'primary')
                ->setKey(['url', 'location', 'company_id'], 'unique')
                ->setKey(['location'], 'index')
                ->setKey(['company_id'], 'index')
                ->setKey(['plugin_id'], 'index')
                ->setKey(['enabled'], 'index')
                ->setKey(['editable'], 'index')
                ->create('actions', true);
        }
    }

    /**
     * Adds a new `navigation_items` table
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addNavigationItemsTable($undo = false)
    {
        if ($undo) {
            $this->Record->drop('navigation_items');
        } else {
            $this->Record
                ->setField('id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])
                ->setField('action_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])
                ->setField(
                    'order',
                    ['type' => 'smallint', 'size' => 5, 'unsigned' => true, 'is_null' => true, 'default' => null]
                )
                ->setField(
                    'parent_id',
                    ['type' => 'int', 'size' => 10, 'unsigned' => true, 'is_null' => true, 'default' => null]
                )
                ->setKey(['id'], 'primary')
                ->setKey(['action_id'], 'index')
                ->setKey(['parent_id'], 'index')
                ->create('navigation_items', true);
        }
    }

    /**
     * Adds core actions and navigation items
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function convertExistingCoreActions($undo = false)
    {
        if ($undo) {
            // Do nothing
        } else {
            $this->convertCoreNavigation();
        }
    }

    /**
     * Adds plugin actions and navigation items, and removes the `plugin_actions` table
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function convertExistingPluginActions($undo = false)
    {
        if ($undo) {
            $this->Record
                ->setField('plugin_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])
                ->setField('action', ['type' => 'varchar', 'size' => 32])
                ->setField('uri', ['type' => 'varchar', 'size' => 255])
                ->setField('name', ['type' => 'varchar', 'size' => 255, 'is_null' => true, 'default' => null])
                ->setField('options', ['type' => 'text'])
                ->setField('enabled', ['type' => 'tinyint', 'size' => 1, 'default' => 1])
                ->setKey(['plugin_id','action','uri'], 'primary')
                ->setKey(['enabled'], 'index')
                ->create('plugin_actions', true);
        } else {
            $this->convertPluginActions();

            $this->Record->drop('plugin_actions');
        }
    }

    /**
     * Adds permissions for action and navigation pages
     *
     * @param bool $undo Whether to add or undo the change
     */
    private function addActionNavigationPagePermissions($undo = false)
    {
        if ($undo) {
            // Do Nothing
        } else {
            // Get the settings permission group
            Loader::loadModels($this, ['Permissions']);
            Loader::loadComponents($this, ['Acl']);
            $group = $this->Permissions->getGroupByAlias('admin_settings');

            // Add the new messenger permissions
            if ($group) {
                $actions = ['actions', 'addaction', 'editaction', 'navigation'];
                $staff_groups = $this->Record->select(['id', 'company_id'])->from('staff_groups')->fetchAll();

                foreach ($actions as $action) {
                    $this->Permissions->add([
                        'group_id' => $group->id,
                        'name' => 'StaffGroups.permissions.admin_company_lookandfeel_' . $action,
                        'alias' => 'admin_company_lookandfeel',
                        'action' => $action
                    ]);
                    foreach ($staff_groups as $staff_group) {
                        $authorized = $this->Permissions->authorized(
                            'staff_group_' . $staff_group->id,
                            'admin_company_lookandfeel',
                            'layout',
                            'staff',
                            $staff_group->company_id
                        );
                        if ($authorized) {
                            $this->Acl->allow('staff_group_' . $staff_group->id, 'admin_company_lookandfeel', $action);
                        } else {
                            $this->Acl->deny('staff_group_' . $staff_group->id, 'admin_company_lookandfeel', $action);
                        }
                    }
                }
            }
        }
    }

    /**
     * Adds to the database all of the current navigation items that used to be supplied by the Navigation model
     */
    private function convertCoreNavigation()
    {
        // Insert the actions and navigation items for the staff navigation
        $staff_nav_items = $this->getCoreStaffNavItems();
        foreach ($staff_nav_items['primary'] as $primary_staff_nav_item) {
            $this->insertCoreAction($primary_staff_nav_item, 'nav_staff');
        }

        foreach ($staff_nav_items['secondary'] as $secondary_staff_nav_item) {
            $this->insertCoreAction($secondary_staff_nav_item, 'nav_staff');
        }

        // Insert the actions and navigation items for the client navigation
        $client_nav_items = $this->getCoreClientNavItems();
        foreach ($client_nav_items['primary'] as $primary_nav_item) {
            $this->insertCoreAction($primary_nav_item, 'nav_client');
        }

        // Insert the dashboard action/navigation item for the public interface
        $this->insertCoreAction(['name' => 'Navigation.getprimarypublic.nav_dashboard', 'uri' => ''], 'nav_public');
    }

    /**
     * Inserts an action and navigation item for the given level
     *
     * @param array $nav_item An array containing the navigation item details
     *
     *  - uri The uri to which this navigation item will link
     *  - name The language key from which the title of this navigation item will be derived
     *  - options A list of addition option for the navigation item (optional)
     *  - parent The URI of the parent navigation item to this one (optional)
     * @param string $location The location of the navigation and action ('nav_public','nav_client','nav_staff')
     */
    private function insertCoreAction(array $nav_item, $location)
    {
        foreach ($this->companies as $company) {
            if (isset($this->nav_item_id_by_uri[$company->id][$location][$nav_item['uri']])) {
                $action_id = $this->nav_item_id_by_uri[$company->id][$location][$nav_item['uri']]['action_id'];
            } else {
                $action_vars = [
                    'location' => $location,
                    'url' => $nav_item['uri'],
                    'name' => $nav_item['name'],
                    'company_id' => $company->id,
                    'options' => isset($nav_item['options']) ? serialize($nav_item['options']) : null,
                    'editable' => '0',
                ];
                // Insert the action for this navigation item
                $this->Record->insert('actions', $action_vars);
                $action_id = $this->Record->lastInsertId();
            }

            // Insert the navigation item
            $navigation_vars = [
                'action_id' => $action_id,
                'order' => $this->navigation_index++,
                'parent_id' => isset($nav_item['parent'])
                    ? $this->nav_item_id_by_uri[$company->id][$location][$nav_item['parent']]['id']
                    : null
            ];

            $this->Record->insert('navigation_items', $navigation_vars);
            if (!isset($nav_item['parent'])) {
                $navigation_vars['id'] = $this->Record->lastInsertId();
                $this->nav_item_id_by_uri[$company->id][$location][$nav_item['uri']] = $navigation_vars;
            }
        }
        reset($this->companies);
    }

    /**
     * Adds actions and navigation items for all the records currently stored in `plugin_actions`
     */
    private function convertPluginActions()
    {
        $plugin_actions = $this->Record->select(['plugin_actions.*', 'plugins.company_id'])->
            from('plugin_actions')->
            innerJoin('plugins', 'plugins.id', '=', 'plugin_actions.plugin_id', false)->
            fetchAll();

        foreach ($plugin_actions as $plugin_action) {
            // Map nav actions to the new simpler action locations
            $action_to_location_map = [
                'nav_primary_staff' => 'nav_staff', 'nav_secondary_staff' => 'nav_staff',
                'nav_primary_client' => 'nav_client', 'nav_secondary_client' => 'nav_client'
            ];
            $location = array_key_exists($plugin_action->action, $action_to_location_map)
                ? $action_to_location_map[$plugin_action->action]
                : $plugin_action->action;

            // Create sub navigation items for the plugin action sub options
            $options = !empty($plugin_action->options) ? unserialize($plugin_action->options) : null;
            $sub_items = [];
            if (isset($options['sub'])) {
                foreach ($options['sub'] as $sub_nav) {
                    $sub_items[] = [
                        'location' => $location,
                        'url' => $sub_nav['uri'],
                        'name' => $sub_nav['name'],
                        'plugin_id' => $plugin_action->plugin_id,
                        'company_id' => $plugin_action->company_id,
                        'enabled' => $plugin_action->enabled,
                        'parent_url' => $plugin_action->uri
                    ];
                }
            }
            unset($options['sub']);

            // Create sub navigation items for the plugin action secondary options
            if (isset($options['secondary'])) {
                foreach ($options['secondary'] as $sub_nav) {
                    $sub_items[] = [
                        'location' => $location,
                        'url' => $sub_nav['uri'],
                        'name' => $sub_nav['name'],
                        'plugin_id' => $plugin_action->plugin_id,
                        'company_id' => $plugin_action->company_id,
                        'enabled' => $plugin_action->enabled,
                        'options' => serialize(['sub_as_secondary' => true]),
                        'parent_url' => $plugin_action->uri
                    ];
                }
            }
            unset($options['secondary']);

            $parent_url = null;
            if (isset($options['parent'])) {
                $parent_url = $options['parent'];
            }
            unset($options['parent']);

            // Create an action from the plugin_actions records
            $action_vars = [
                'location' => $location,
                'url' => $plugin_action->uri,
                'name' => $plugin_action->name,
                'options' => ($options !== null ? serialize($options) : null),
                'plugin_id' => $plugin_action->plugin_id,
                'company_id' => $plugin_action->company_id,
                'enabled' => $plugin_action->enabled,
                'parent_url' => $parent_url
            ];

            // Add new actions
            $nav_id = $this->insertPluginAction($action_vars);
            foreach ($sub_items as $sub_item) {
                $this->insertPluginAction($sub_item);
            }

            // If the nev item was a client navigation item, also insert it into the public nav as well
            if ($action_vars['location'] == 'nav_client') {
                $action_vars['location'] = 'nav_public';
                $nav_id = $this->insertPluginAction($action_vars);
                foreach ($sub_items as $sub_item) {
                    $sub_item['location'] = 'nav_public';
                    $this->insertPluginAction($sub_item);
                }
            }
        }
    }

    /**
     * Adds an action and navigation item for a plugin_action and each of it's
     *
     * @param array $action
     */
    private function insertPluginAction(array $action)
    {
        $action_fields = ['location', 'url', 'name', 'options', 'plugin_id', 'company_id', 'enabled'];

        $company_id = $action['company_id'];
        $location = $action['location'];
        if (isset($this->nav_item_id_by_uri[$company_id][$location][$action['url']])) {
            $action_id = $this->nav_item_id_by_uri[$company_id][$location][$action['url']]['action_id'];
        } else {
            // Insert the plugin action
            $this->Record->insert('actions', $action, $action_fields);
            $action_id = $this->Record->lastInsertId();
        }

        // Only add navigation items for plugin actions that are enabled and the correct type
        if ($action['enabled'] === 0
            || !in_array($action['location'], ['nav_staff', 'nav_client', 'nav_public'])
        ) {
            return;
        }

        // Get the parent ID
        $parent_id = null;
        $parent_url = $action['parent_url'];
        if ($parent_url !== null && isset($this->nav_item_id_by_uri[$company_id][$location][$parent_url])) {
            $parent_id = $this->nav_item_id_by_uri[$company_id][$location][$parent_url]['id'];
        }

        // Insert the plugin navigation item
        $navigation_vars = [
            'action_id' => $action_id,
            'order' => $this->navigation_index++,
            'parent_id' => $parent_id,
        ];

        $this->Record->insert('navigation_items', $navigation_vars);
        $navigation_vars['id'] = $this->Record->lastInsertId();
        $this->nav_item_id_by_uri[$company_id][$location][$action['url']] = $navigation_vars;
        return $navigation_vars['id'];
    }

    /**
     * Gets a list of core staff navigation item specifications
     *
     * @return array A list of primary and secondary navigation items
     *
     *  - primary
     *  - - name
     *  - - uri
     *  - - options
     *  - secondary
     *  - - name
     *  - - uri
     *  - - parent
     *  - - options
     */
    private function getCoreStaffNavItems()
    {
        return [
            'primary' => [
                [
                    'name' => 'Navigation.getprimary.nav_home',
                    'uri' => '',
                ],
                [
                    'name' => 'Navigation.getprimary.nav_clients',
                    'uri' => 'clients/',
                ],
                [
                    'name' => 'Navigation.getprimary.nav_billing',
                    'uri' => 'billing/',
                ],
                [
                    'name' => 'Navigation.getprimary.nav_packages',
                    'uri' => 'packages/',
                ],
                [
                    'name' => 'Navigation.getprimary.nav_tools',
                    'uri' => 'tools/',
                ],
            ],
            'secondary' => [
                [
                    'name' => 'Navigation.getprimary.nav_home_dashboard',
                    'uri' => '',
                    'parent' => ''
                ],
                [
                    'name' => 'Navigation.getprimary.nav_clients_browse',
                    'uri' => 'clients/',
                    'parent' => 'clients/'
                ],
                [
                    'name' => 'Navigation.getprimary.nav_billing_overview',
                    'uri' => 'billing/index/',
                    'parent' => 'billing/'
                ],
                [
                    'name' => 'Navigation.getprimary.nav_billing_invoices',
                    'uri' => 'billing/invoices/',
                    'parent' => 'billing/'
                ],
                [
                    'name' => 'Navigation.getprimary.nav_billing_transactions',
                    'uri' => 'billing/transactions/',
                    'parent' => 'billing/'
                ],
                [
                    'name' => 'Navigation.getprimary.nav_billing_services',
                    'uri' => 'billing/services/',
                    'parent' => 'billing/'
                ],
                [
                    'name' => 'Navigation.getprimary.nav_billing_reports',
                    'uri' => 'reports/',
                    'parent' => 'billing/'
                ],
                [
                    'name' => 'Navigation.getprimary.nav_billing_printqueue',
                    'uri' => 'billing/printqueue/',
                    'parent' => 'billing/'
                ],
                [
                    'name' => 'Navigation.getprimary.nav_billing_batch',
                    'uri' => 'billing/batch/',
                    'parent' => 'billing/'
                ],
                [
                    'name' => 'Navigation.getprimary.nav_packages_browse',
                    'uri' => 'packages/',
                    'parent' => 'packages/'
                ],
                [
                    'name' => 'Navigation.getprimary.nav_packages_groups',
                    'uri' => 'packages/groups/',
                    'parent' => 'packages/'
                ],
                [
                    'options' => [
                        'route' => [
                            'controller' => 'admin_package_options',
                            'action' => '*'
                        ]
                    ],
                    'name' => 'Navigation.getprimary.nav_package_options',
                    'uri' => 'package_options/',
                    'parent' => 'packages/'
                ],
                [
                    'name' => 'Navigation.getprimary.nav_tools_logs',
                    'uri' => 'tools/logs/',
                    'parent' => 'tools/'
                ],
                [
                    'name' => 'Navigation.getprimary.nav_tools_currency',
                    'uri' => 'tools/convertcurrency/',
                    'parent' => 'tools/'
                ]
            ]
        ];
    }

    /**
     * Gets a list of core client navigation item specifications
     *
     * @return array A list of primary and secondary navigation items
     *
     *  - primary
     *  - - name
     *  - - uri
     *  - - options
     *  - secondary
     *  - - name
     *  - - uri
     *  - - parent
     *  - - options
     *
     */
    private function getCoreClientNavItems()
    {
        return [
            'primary' => [
                [
                    'name' => 'Navigation.getprimaryclient.nav_dashboard',
                    'uri' => ''
                ],
                [
                    'name' => 'Navigation.getprimaryclient.nav_paymentaccounts',
                    'uri' => 'accounts/',
                    'options' => [
                        'secondary' => [
                            'accounts/' => [
                                'name' => 'Navigation.getprimaryclient.nav_paymentaccounts',
                                'active' => false,
                                'icon' => 'fas fa-list'
                            ],
                            'accounts/add/' => [
                                'name' => 'Navigation.getprimaryclient.nav_paymentaccounts_add',
                                'active' => false,
                                'icon' => 'fas fa-plus-square'
                            ],
                            '' => [
                                'name' => 'Navigation.getprimaryclient.nav_return',
                                'active' => false,
                                'icon' => 'fas fa-arrow-left'
                            ]
                        ]
                    ]
                ],
                [
                    'name' => 'Navigation.getprimaryclient.nav_contacts',
                    'uri' => 'contacts/',
                    'options' => [
                        'secondary' => [
                            'contacts/' => [
                                'name' => 'Navigation.getprimaryclient.nav_contacts',
                                'active' => false,
                                'icon' => 'fas fa-list'
                            ],
                            'contacts/add/' => [
                                'name' => 'Navigation.getprimaryclient.nav_contacts_add',
                                'active' => false,
                                'icon' => 'fas fa-plus-square'
                            ],
                            '' => [
                                'name' => 'Navigation.getprimaryclient.nav_return',
                                'active' => false,
                                'icon' => 'fas fa-arrow-left'
                            ]
                        ]
                    ]
                ],
            ]
        ];
    }

    /**
     * Adds the new colors to all client themes
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function updateClientThemes($undo = false)
    {
        if ($undo) {
            // Nothing to do
        } else {
            // Load required models
            Loader::loadModels($this, ['Themes']);

            $colors = [
                'theme_primary_alert_text_color' => '004085',
                'theme_primary_alert_background_color' => 'cce5ff',
                'theme_primary_alert_border_color' => 'b8daff',
                'theme_secondary_alert_text_color' => '383d41',
                'theme_secondary_alert_background_color' => 'e2e3e5',
                'theme_secondary_alert_border_color' => 'd6d8db',
                'theme_success_alert_text_color' => '155724',
                'theme_success_alert_background_color' => 'd4edda',
                'theme_success_alert_border_color' => 'c3e6cb',
                'theme_info_alert_text_color' => '0c5460',
                'theme_info_alert_background_color' => 'd1ecf1',
                'theme_info_alert_border_color' => 'bee5eb',
                'theme_warning_alert_text_color' => '856404',
                'theme_warning_alert_background_color' => 'fff3cd',
                'theme_warning_alert_border_color' => 'ffeeba',
                'theme_danger_alert_text_color' => '721c24',
                'theme_danger_alert_background_color' => 'f8d7da',
                'theme_danger_alert_border_color' => 'f5c6cb',
                'theme_light_alert_text_color' => '818182',
                'theme_light_alert_background_color' => 'fefefe',
                'theme_light_alert_border_color' => 'fdfdfe',
                'theme_primary_button_text_color' => 'ffffff',
                'theme_primary_button_background_color' => '007bff',
                'theme_primary_button_hover_color' => '0069d9',
                'theme_secondary_button_text_color' => 'ffffff',
                'theme_secondary_button_background_color' => '6c757d',
                'theme_secondary_button_hover_color' => '5a6268',
                'theme_success_button_text_color' => 'ffffff',
                'theme_success_button_background_color' => '28a745',
                'theme_success_button_hover_color' => '218838',
                'theme_info_button_text_color' => 'ffffff',
                'theme_info_button_background_color' => '17a2b8',
                'theme_info_button_hover_color' => '138496',
                'theme_warning_button_text_color' => '212529',
                'theme_warning_button_background_color' => 'ffc107',
                'theme_warning_button_hover_color' => 'e0a800',
                'theme_danger_button_text_color' => 'ffffff',
                'theme_danger_button_background_color' => 'dc3545',
                'theme_danger_button_hover_color' => 'c82333',
                'theme_light_button_text_color' => '212529',
                'theme_light_button_background_color' => 'ffffff',
                'theme_light_button_hover_color' => 'e2e6ea'
            ];

            // Add additional theme colors for every company
            foreach ($this->companies as $company) {
                $themes = $this->Themes->getAll('client', $company->id);
                foreach ($themes as $theme) {
                    $theme->colors = array_merge($colors, $theme->colors);
                    $this->Themes->edit($theme->id, (array) $theme);
                }
            }
            reset($this->companies);
        }
    }

    /**
     * Migrates the data from the older 2Checkout gateway to the new one
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function migrate2Checkout($undo = false)
    {
        if ($undo) {
            $this->Record->where('class', '=', 'checkout2')->update('gateways', ['class' => '_2checkout']);
        } else {
            $this->Record->where('class', '=', '_2checkout')->update('gateways', ['class' => 'checkout2']);
        }
    }

    /**
     * Migrates the logo url from the Themes to Customize
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function migrateLogo($undo = false)
    {
        if ($undo) {
            $this->Record->query("DELETE FROM `company_settings` WHERE `key`='logo_admin';");
            $this->Record->query("DELETE FROM `company_settings` WHERE `key`='logo_client';");
        } else {
            // Load required models
            Loader::loadModels($this, ['Themes']);

            // Load required components
            Loader::loadComponents($this, ['SettingsCollection']);

            // Migrate the logo of all companies
            foreach ($this->companies as $company) {
                // Get admin logo
                $theme_id = $this->SettingsCollection->fetchSetting(null, $company->id, 'theme_admin');
                $admin_logo = '';

                if (!empty($theme_id['value'])) {
                    $theme = $this->Themes->get($theme_id['value']);

                    if ($theme) {
                        $admin_logo = $theme->logo_url;
                    }
                }

                // Get client logo
                $theme_id = $this->SettingsCollection->fetchSetting(null, $company->id, 'theme_client');
                $client_logo = '';

                if (!empty($theme_id['value'])) {
                    $theme = $this->Themes->get($theme_id['value']);

                    if ($theme) {
                        $client_logo = $theme->logo_url;
                    }
                }

                $this->Record->query(
                    "INSERT INTO `company_settings` (`key`, `company_id`, `value`)
                        VALUES ('logo_admin', ?, ?)
                        ON DUPLICATE KEY UPDATE value = ?;",
                    $company->id,
                    $admin_logo,
                    $admin_logo
                );
                $this->Record->query(
                    "INSERT INTO `company_settings` (`key`, `company_id`, `value`)
                        VALUES ('logo_client', ?, ?)
                        ON DUPLICATE KEY UPDATE value = ?;",
                    $company->id,
                    $client_logo,
                    $client_logo
                );
            }
            reset($this->companies);
        }
    }

    /**
     * Installs the new default theme
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function installThemes($undo = false)
    {
        if ($undo) {
            // Nothing to do
        } else {
            // Load required models
            Loader::loadModels($this, ['Themes']);

            // Set themes
            $themes = [
                [
                    'name' => 'FIVE',
                    'type' => 'admin',
                    'colors' => [
                        'theme_header_bg_color_top' => '25282b',
                        'theme_header_bg_color_bottom' => '25282b',
                        'theme_header_text_color' => 'ffffff',
                        'theme_navigation_background_color_top' => '3e4347',
                        'theme_navigation_background_color_bottom' => '3e4347',
                        'theme_navigation_text_color' => 'ffffff',
                        'theme_navigation_text_hover_color' => '585f65',
                        'theme_subnavigation_bg_color_top' => 'ededed',
                        'theme_subnavigation_bg_color_bottom' => 'ededed',
                        'theme_subnavigation_text_color' => '757b82',
                        'theme_subnavigation_text_active_color' => '393c40',
                        'theme_widget_heading_bg_color_top' => 'efefef',
                        'theme_widget_heading_bg_color_bottom' => 'efefef',
                        'theme_widget_icon_heading_bg_color_top' => 'dedede',
                        'theme_widget_icon_heading_bg_color_bottom' => 'dedede',
                        'theme_box_text_color' => '626970',
                        'theme_text_shadow' => 'ffffff',
                        'theme_actions_text_color' => '0e6292',
                        'theme_highlight_bg_color' => 'ecf7ff',
                        'theme_box_shadow_color' => 'transparent'
                    ],
                    'logo_url' => ''
                ],
                [
                    'name' => 'FIVE Light',
                    'type' => 'admin',
                    'colors' => [
                        'theme_header_bg_color_top' => 'ffffff',
                        'theme_header_bg_color_bottom' => 'ffffff',
                        'theme_header_text_color' => '3d3d3d',
                        'theme_navigation_background_color_top' => '3e4347',
                        'theme_navigation_background_color_bottom' => '3e4347',
                        'theme_navigation_text_color' => 'ffffff',
                        'theme_navigation_text_hover_color' => '585f65',
                        'theme_subnavigation_bg_color_top' => 'ededed',
                        'theme_subnavigation_bg_color_bottom' => 'ededed',
                        'theme_subnavigation_text_color' => '757b82',
                        'theme_subnavigation_text_active_color' => '393c40',
                        'theme_widget_heading_bg_color_top' => 'efefef',
                        'theme_widget_heading_bg_color_bottom' => 'efefef',
                        'theme_widget_icon_heading_bg_color_top' => 'dedede',
                        'theme_widget_icon_heading_bg_color_bottom' => 'dedede',
                        'theme_box_text_color' => '626970',
                        'theme_text_shadow' => 'ffffff',
                        'theme_actions_text_color' => '157bb8',
                        'theme_highlight_bg_color' => 'ecf7ff',
                        'theme_box_shadow_color' => 'transparent'
                    ],
                    'logo_url' => ''
                ],
                [
                    'name' => 'FIVE',
                    'type' => 'client',
                    'colors' => [
                        'theme_header_bg_color_top' => 'ffffff',
                        'theme_header_bg_color_bottom' => 'ffffff',
                        'theme_page_title_background_color_top' => 'f7f9fb',
                        'theme_page_title_background_color_bottom' => 'f9f9fb',
                        'theme_page_title_text_color' => '232629',
                        'theme_navigation_background_color_top' => '2e3338',
                        'theme_navigation_background_color_bottom' => '2e3338',
                        'theme_navigation_text_color' => 'd8dbde',
                        'theme_navigation_text_active_color' => 'fbfcfd',
                        'theme_page_background_color' => 'ffffff',
                        'theme_panel_header_background_color_top' => '2e3338',
                        'theme_panel_header_background_color_bottom' => '2e3338',
                        'theme_panel_header_text_color' => 'f2f2f2',
                        'theme_link_color' => '0074b2',
                        'theme_link_settings_color' => '4f4f4f',
                        'theme_highlight_hover_color' => 'd1ecf1',
                        'theme_highlight_navigation_text_color_top' => 'ebebeb',
                        'theme_highlight_navigation_hover_text_color_top' => 'ffffff',
                        'theme_primary_alert_text_color' => '004085',
                        'theme_primary_alert_background_color' => 'cce5ff',
                        'theme_primary_alert_border_color' => 'b8daff',
                        'theme_secondary_alert_text_color' => '383d41',
                        'theme_secondary_alert_background_color' => 'e2e3e5',
                        'theme_secondary_alert_border_color' => 'd6d8db',
                        'theme_success_alert_text_color' => '155725',
                        'theme_success_alert_background_color' => 'd4edda',
                        'theme_success_alert_border_color' => 'c3e6cb',
                        'theme_info_alert_text_color' => '0c5360',
                        'theme_info_alert_background_color' => 'd1ecf1',
                        'theme_info_alert_border_color' => 'bee5eb',
                        'theme_warning_alert_text_color' => '856504',
                        'theme_warning_alert_background_color' => 'fff3cd',
                        'theme_warning_alert_border_color' => 'ffeeba',
                        'theme_danger_alert_text_color' => '721c25',
                        'theme_danger_alert_background_color' => 'f8d7da',
                        'theme_danger_alert_border_color' => 'f5c6cb',
                        'theme_light_alert_text_color' => '818182',
                        'theme_light_alert_background_color' => 'fefefe',
                        'theme_light_alert_border_color' => 'fdfdfe',
                        'theme_primary_button_text_color' => 'ffffff',
                        'theme_primary_button_background_color' => '007bff',
                        'theme_primary_button_hover_color' => '0069d9',
                        'theme_secondary_button_text_color' => 'ffffff',
                        'theme_secondary_button_background_color' => '6c757d',
                        'theme_secondary_button_hover_color' => '545b62',
                        'theme_success_button_text_color' => 'ffffff',
                        'theme_success_button_background_color' => '28a746',
                        'theme_success_button_hover_color' => '218838',
                        'theme_info_button_text_color' => 'ffffff',
                        'theme_info_button_background_color' => '17a3b8',
                        'theme_info_button_hover_color' => '138596',
                        'theme_warning_button_text_color' => 'ffffff',
                        'theme_warning_button_background_color' => 'ffc107',
                        'theme_warning_button_hover_color' => 'e0a800',
                        'theme_danger_button_text_color' => 'ffffff',
                        'theme_danger_button_background_color' => 'dc3545',
                        'theme_danger_button_hover_color' => 'c82334',
                        'theme_light_button_text_color' => '212529',
                        'theme_light_button_background_color' => 'ffffff',
                        'theme_light_button_hover_color' => 'e2e6ea'
                    ],
                    'logo_url' => ''
                ]
            ];
            $default_themes = ['FIVE'];
            $theme_types = ['admin', 'client'];

            // Install default themes
            $installed_themes = [];
            foreach ($themes as $theme) {
                if (in_array($theme['name'], $default_themes)) {
                    $theme_id = $this->Themes->add($theme);
                    $this->Record->where('id', '=', $theme_id)
                        ->update('themes', ['company_id' => null]);

                    $installed_themes[$theme['type']] = $theme_id;
                }
            }

            // Install additional themes
            foreach ($this->companies as $company) {
                foreach ($themes as $theme) {
                    if (!in_array($theme['name'], $default_themes)) {
                        $this->Themes->add($theme, $company->id);
                    }
                }

                // Update this company to use the new default theme if they are
                // currently using the old default theme
                foreach ($theme_types as $theme_type) {
                    $old_theme = $this->Record->select()
                        ->from('themes')
                        ->where('company_id', '=', null)
                        ->where('name', '=', 'FOUR')
                        ->where('type', '=', $theme_type)
                        ->fetch();

                    $this->Record->where('value', '=', $old_theme->id)
                        ->where('key', '=', 'theme_' . $theme_type)
                        ->where('company_id', '=', $company->id)
                        ->update('company_settings', ['value' => $installed_themes[$theme_type]]);
                }
            }
            reset($this->companies);

            // Fetch the current default theme
            $old_themes = $this->Record->select()
                ->from('themes')
                ->where('company_id', '=', null)
                ->where('name', '=', 'FOUR')
                ->fetchAll();

            // Add the current default theme to all the companies
            foreach ($old_themes as $old_theme) {
                $theme = (array) $old_theme;
                $fields = ['company_id', 'name', 'type', 'data'];

                foreach ($this->companies as $company) {
                    $theme['company_id'] = $company->id;
                    $this->Record->insert('themes', $theme, $fields);
                }
            }

            // Delete the old theme
            $this->Record->from('themes')
                ->where('company_id', '=', null)
                ->where('name', '=', 'FOUR')
                ->delete();
        }
    }

    /**
     * Adds a new permission for Customize settings page
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addCustomizeSettingsPermission($undo = false)
    {
        if ($undo) {
            // Do Nothing
        } else {
            // Get the package permission group
            Loader::loadModels($this, ['Permissions']);
            Loader::loadComponents($this, ['Acl']);

            // Fetch all staff groups
            $staff_groups = $this->Record->select(['staff_groups.*'])
                ->from('staff_groups')
                ->group(['staff_groups.id'])
                ->fetchAll();

            // Determine comparable permission access
            $staff_group_access = [];
            foreach ($staff_groups as $staff_group) {
                $staff_group_access[$staff_group->id] = [
                    'admin_company_lookandfeel' => $this->Permissions->authorized(
                        'staff_group_' . $staff_group->id,
                        'admin_company_lookandfeel',
                        '*',
                        'staff',
                        $staff_group->company_id
                    )
                ];
            }

            $group = $this->Permissions->getGroupByAlias('admin_settings');

            // Add the new layout permission
            if ($group) {
                $permissions = [
                    'group_id' => $group->id,
                    'name' => 'StaffGroups.permissions.admin_company_lookandfeel_customize',
                    'alias' => 'admin_company_lookandfeel',
                    'action' => 'customize'
                ];
                $this->Permissions->add($permissions);

                foreach ($staff_groups as $staff_group) {
                    // If staff group has access to similar item, grant access to this item
                    if ($staff_group_access[$staff_group->id][$permissions['alias']]) {
                        $this->Acl->allow('staff_group_' . $staff_group->id, $permissions['alias'], $permissions['action']);
                    } else {
                        $this->Acl->deny('staff_group_' . $staff_group->id, $permissions['alias'], $permissions['action']);
                    }
                }
            }
        }
    }

    /**
     * Remove the `name`, `description`, and `description_html` fields from the `packages` and `package_groups` tables
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function removePackageAndGroupDescriptionsNames($undo = false) {
        if ($undo) {
            $this->Record->query('ALTER TABLE `packages`
                ADD `name` VARCHAR(255) NULL DEFAULT NULL AFTER `module_id`,
                ADD `description` TEXT NULL DEFAULT NULL AFTER `name`,
                ADD `description_html` TEXT NULL DEFAULT NULL AFTER `description`');
            $this->Record->query('ALTER TABLE `package_groups`
                ADD `name` VARCHAR(255) NULL DEFAULT NULL AFTER `id`,
                ADD `description` TEXT NULL DEFAULT NULL AFTER `name`');
        } else {
            foreach ($this->getAllLanguages() as $language) {
                // Copy names over from the packages table
                $this->Record->query(
                    'INSERT INTO `package_names` (`package_id`, `lang`, `name`)
                    SELECT `packages`.`id`, ?, `packages`.`name`
                    FROM `packages`
                    LEFT JOIN `package_names` AS `names` ON `names`.`package_id` = `packages`.`id`
                    WHERE `packages`.`company_id` = ? AND `names`.`package_id` IS NULL
                    GROUP BY `packages`.`id`',
                    [$language->code, $language->company_id]
                );

                // Copy descriptions over from the packages table
                $this->Record->query(
                    'INSERT INTO `package_descriptions` (`package_id`, `lang`, `html`, `text`)
                    SELECT `packages`.`id`, ?, `packages`.`description_html`, `packages`.`description`
                    FROM `packages`
                    LEFT JOIN `package_descriptions` AS `descriptions` ON `descriptions`.`package_id` = `packages`.`id`
                    WHERE `packages`.`company_id` = ? AND `descriptions`.`package_id` IS NULL
                    GROUP BY `packages`.`id`',
                    [$language->code, $language->company_id]
                );

                // Copy names over from the package_groups table
                $this->Record->query(
                    'INSERT INTO `package_group_names` (`package_group_id`, `lang`, `name`)
                    SELECT `package_groups`.`id`, ?, `package_groups`.`name`
                    FROM `package_groups`
                    LEFT JOIN `package_group_names` AS `names` ON `names`.`package_group_id` = `package_groups`.`id`
                    WHERE `package_groups`.`company_id` = ? AND `names`.`package_group_id` IS NULL
                    GROUP BY `package_groups`.`id`',
                    [$language->code, $language->company_id]
                );

                // Copy descriptions over from the package_groups table
                $this->Record->query(
                    'INSERT INTO `package_group_descriptions` (`package_group_id`, `lang`, `description`)
                    SELECT `package_groups`.`id`, ?, `package_groups`.`description`
                    FROM `package_groups`
                    LEFT JOIN `package_group_descriptions` AS `descriptions`
                        ON `descriptions`.`package_group_id` = `package_groups`.`id`
                    WHERE `package_groups`.`company_id` = ? AND `descriptions`.`package_group_id` IS NULL
                    GROUP BY `package_groups`.`id`',
                    [$language->code, $language->company_id]
                );
            }

            $this->Record->query('ALTER TABLE `packages` DROP `name`,  DROP `description`, DROP `description_html`');
            $this->Record->query('ALTER TABLE `package_groups` DROP `name`,  DROP `description`');
        }
    }

    /**
     * Retrieves all languages
     *
     * @return array An array of stdClass objects each representing a language in the system
     */
    private function getAllLanguages()
    {
        return $this->Record->select()->from('languages')->fetchAll();
    }
}
