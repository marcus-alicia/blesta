<?php
/**
 * Upgrades to version 3.2.0-b1
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2014, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade3_2_0B1 extends UpgradeUtil
{

    /**
     * @var array An array of all tasks completed
     */
    private $tasks = [];

    /**
     * Setup
     */
    public function __construct()
    {
        Loader::loadComponents($this, ['Record']);
    }

    /**
     * Returns a numerically indexed array of tasks to execute for the upgrade process
     *
     * @retrun array A numerically indexed array of tasks to execute for the upgrade process
     */
    public function tasks()
    {
        return [
            'updateConfig',
            'updateClientThemes',
            'addClientThemes',
            'updateSettings',
            'updatePermissions',
            'addClientAddonSetting',
            'updateServiceSuspensionEmails'
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
     * Updates the config
     *
     * @param bool $undo True to undo the change false to perform the change
     */
    private function updateConfig($undo = false)
    {
        if ($undo) {
            // No need to undo anything
        } else {
            // ADD Blesta.pagination_client
            if (file_exists(CONFIGDIR . 'blesta.php')) {
                $this->mergeConfig(CONFIGDIR . 'blesta.php', CONFIGDIR . 'blesta-new.php');
            }
        }
    }

    /**
     * Updates client themes to add new theme colors
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function updateClientThemes($undo = false)
    {
        $themes = $this->Record->select()->from('themes')->where('type', '=', 'client')->getStatement();
        $default_themes = ['Blesta Blue', 'Minegrass', 'Cloudy Day', 'Vintage', 'Clean'];
        $default_colors = ['theme_header_bg_color_top', 'theme_header_bg_color_bottom',
            'theme_page_title_background_color_top', 'theme_page_title_background_color_bottom',
            'theme_page_title_text_color', 'theme_navigation_background_color_top',
            'theme_navigation_background_color_bottom', 'theme_navigation_text_color',
            'theme_navigation_text_active_color', 'theme_page_background_color',
            'theme_panel_header_background_color_top', 'theme_panel_header_background_color_bottom',
            'theme_panel_header_text_color', 'theme_link_color', 'theme_link_settings_color',
            'theme_highlight_hover_color'
        ];

        if ($undo) {
            // Remove the new theme colors
            foreach ($themes as $theme) {
                if ($data && isset($data['colors'])) {
                    $data = unserialize(base64_decode($theme->data));

                    if ($data && isset($data['colors'])) {
                        $new_data = [
                            'colors' => $data['colors'],
                            'logo_url' => (isset($data['logo_url']) ? $data['logo_url'] : '')
                        ];
                        unset(
                            $new_data['colors']['theme_panel_header_background_color_top'],
                            $new_data['colors']['theme_panel_header_background_color_bottom'],
                            $new_data['colors']['theme_panel_header_text_color'],
                            $new_data['colors']['theme_highlight_hover_color']
                        );

                        // Update the theme
                        $fields = ['data'];

                        $vars = ['data' => base64_encode(serialize($new_data))];
                        $this->Record->where('id', '=', $theme->id)->update('themes', $vars, $fields);
                    }
                }
            }
        } else {
            // Add new theme colors for default themes
            $theme_colors = [
                'Blesta Blue' => [
                    'theme_panel_header_background_color_top' => '6fb1df',
                    'theme_panel_header_background_color_bottom' => '0075b2',
                    'theme_panel_header_text_color' => 'ffffff',
                    'theme_highlight_hover_color' => 'ecf7ff'
                ],
                'Minegrass' => [
                    'theme_panel_header_background_color_top' => '6c9a3f',
                    'theme_panel_header_background_color_bottom' => '3d6c20',
                    'theme_panel_header_text_color' => 'ffffff',
                    'theme_highlight_hover_color' => 'd7e6c8'
                ],
                'Cloudy Day' => [
                    'theme_panel_header_background_color_top' => 'c9c8c1',
                    'theme_panel_header_background_color_bottom' => 'c9c8c1',
                    'theme_panel_header_text_color' => '6e6d68',
                    'theme_highlight_hover_color' => 'e6e6e6'
                ],
                'Vintage' => [
                    'theme_panel_header_background_color_top' => 'a32c28',
                    'theme_panel_header_background_color_bottom' => 'a32c28',
                    'theme_panel_header_text_color' => 'fffcf5',
                    'theme_highlight_hover_color' => 'ede4ce'
                ],
                'Clean' => [
                    'theme_panel_header_background_color_top' => '333333',
                    'theme_panel_header_background_color_bottom' => '333333',
                    'theme_panel_header_text_color' => 'ffffff',
                    'theme_highlight_hover_color' => 'fae6e1'
                ]
            ];

            foreach ($themes as $theme) {
                // Skip non-default themes by name
                if (!in_array($theme->name, $default_themes)) {
                    continue;
                }

                $data = unserialize(base64_decode($theme->data));

                if ($data && isset($data['colors'])) {
                    // Ensure each color field exists
                    foreach ($default_colors as $name) {
                        if (!array_key_exists($name, (array)$data['colors'])) {
                            $data['colors'][$name] = '';
                        }
                    }

                    // Set colors, maintain order
                    $new_data = [
                        'colors' => [
                            'theme_header_bg_color_top'
                                => $data['colors']['theme_header_bg_color_top'],
                            'theme_header_bg_color_bottom'
                                => $data['colors']['theme_header_bg_color_bottom'],
                            'theme_page_title_background_color_top'
                                => $data['colors']['theme_page_title_background_color_top'],
                            'theme_page_title_background_color_bottom'
                                => $data['colors']['theme_page_title_background_color_bottom'],
                            'theme_page_title_text_color'
                                => $data['colors']['theme_page_title_text_color'],
                            'theme_navigation_background_color_top'
                                => $data['colors']['theme_navigation_background_color_top'],
                            'theme_navigation_background_color_bottom'
                                => $data['colors']['theme_navigation_background_color_bottom'],
                            'theme_navigation_text_color'
                                => $data['colors']['theme_navigation_text_color'],
                            'theme_navigation_text_active_color'
                                => $data['colors']['theme_navigation_text_active_color'],
                            'theme_page_background_color'
                                => $data['colors']['theme_page_background_color'],
                            'theme_panel_header_background_color_top'
                                => $theme_colors[$theme->name]['theme_panel_header_background_color_top'],
                            'theme_panel_header_background_color_bottom'
                                => $theme_colors[$theme->name]['theme_panel_header_background_color_bottom'],
                            'theme_panel_header_text_color'
                                => $theme_colors[$theme->name]['theme_panel_header_text_color'],
                            'theme_link_color'
                                => $data['colors']['theme_link_color'],
                            'theme_link_settings_color'
                                => $data['colors']['theme_link_settings_color'],
                            'theme_highlight_hover_color'
                                => $theme_colors[$theme->name]['theme_highlight_hover_color']
                        ],
                        'logo_url' => (isset($data['logo_url']) ? $data['logo_url'] : '')
                    ];

                    // Update the theme
                    $fields = ['data'];

                    $vars = ['data' => base64_encode(serialize($new_data))];
                    $this->Record->where('id', '=', $theme->id)->update('themes', $vars, $fields);
                }
            }
        }
    }

    /**
     * Adds new client themes
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addClientThemes($undo = false)
    {
        $companies = $this->Record->select()->from('companies')->fetchAll();

        // Theme names
        $theme_names = ['Booty', 'Slate'];
        foreach ($theme_names as $theme_name) {
            $theme_data = [
                'colors' => $this->getThemeColors($theme_name),
                'logo_url' => ''
            ];

            $theme = [
                'name' => $theme_name,
                'type' => 'client',
                'data' => base64_encode(serialize($theme_data))
            ];

            // Add or remove the theme
            if ($undo) {
                // Remove the theme
                foreach ($companies as $company) {
                    $this->Record->where('company_id', '=', $company->id)->
                        where('name', '=', $theme['name'])->where('type', '=', $theme['type'])->
                        where('data', '=', $theme['data'])->delete('themes');
                }
            } else {
                // Add the theme to every company
                foreach ($companies as $company) {
                    $temp_theme = $theme;
                    $temp_theme['company_id'] = $company->id;
                    $this->Record->insert('themes', $temp_theme);
                }
            }
        }
    }

    /**
     * Retrieves the theme colors by name
     *
     * @param string $theme The theme name (i.e. "Slate" or "Booty")
     * @return array A key/value list of theme keys and hex values
     */
    private function getThemeColors($theme)
    {
        $slate = ($theme == 'Slate');
        return [
            'theme_header_bg_color_top' => ($slate ? 'ffffff' : '563d7c'),
            'theme_header_bg_color_bottom' => ($slate ? 'ffffff' : '6e5499'),
            'theme_page_title_background_color_top' => ($slate ? 'f5f5f5' : '563d7c'),
            'theme_page_title_background_color_bottom' => ($slate ? 'f0f0f0' : '563d7c'),
            'theme_page_title_text_color' => ($slate ? '3a3a3a' : 'ffffff'),
            'theme_navigation_background_color_top' => ($slate ? '4f4f4f' : '6e5499'),
            'theme_navigation_background_color_bottom' => ($slate ? '4f4f4f' : '705999'),
            'theme_navigation_text_color' => ($slate ? 'ebebeb' : 'cdbfe3'),
            'theme_navigation_text_active_color' => ($slate ? 'ffffff' : 'ffffff'),
            'theme_page_background_color' => ($slate ? 'fafafa' : 'ffffff'),
            'theme_panel_header_background_color_top' => ($slate ? '4f4f4f' : '563d7c'),
            'theme_panel_header_background_color_bottom' => ($slate ? '4f4f4f' : '563d7c'),
            'theme_panel_header_text_color' => ($slate ? 'f2f2f2' : 'ffffff'),
            'theme_link_color' => ($slate ? '0074b2' : '787878'),
            'theme_link_settings_color' => ($slate ? '4f4f4f' : '787878'),
            'theme_highlight_hover_color' => ($slate ? 'e0f5ff' : 'dcd1f0')
        ];
    }

    /**
     * Update settings
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function updateSettings($undo = false)
    {
        $companies = $this->Record->select()->from('companies')->fetchAll();

        if ($undo) {
            // Delete client_view_dir
            foreach ($companies as $company) {
                $this->Record->from('company_settings')->
                    where('company_id', '=', $company->id)->
                    where('key', '=', 'client_view_dir')->
                    delete();
            }
        } else {
            // Add client_view_dir
            foreach ($companies as $company) {
                $values = [
                    'key' => 'client_view_dir',
                    'company_id' => $company->id,
                    'value' => 'bootstrap'
                ];
                $this->Record->insert('company_settings', $values);
            }
        }
    }

    /**
     * Update permissions
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function updatePermissions($undo = false)
    {
        Loader::loadModels($this, ['Permissions', 'StaffGroups']);
        Loader::loadComponents($this, ['Acl']);

        if ($undo) {
            // Nothing to undo
        } else {
            $staff_groups = $this->StaffGroups->getAll();
            // Determine comparable permission access
            $staff_group_access = [];
            foreach ($staff_groups as $staff_group) {
                $themes = $this->Permissions->authorized(
                    'staff_group_' . $staff_group->id,
                    'admin_company_general',
                    'themes',
                    'staff',
                    $staff_group->company_id
                );

                $staff_group_access[$staff_group->id] = [
                    'admin_company_lookandfeel::*' => $themes,
                    'admin_company_themes::*' => $themes,
                ];
            }

            $group = $this->Permissions->getGroupByAlias('admin_settings');
            if ($group) {
                $permissions = [
                    // Look and feel
                    [
                        'group_id' => $group->id,
                        'name' => 'StaffGroups.permissions.admin_company_lookandfeel',
                        'alias' => 'admin_company_lookandfeel',
                        'action' => '*'
                    ],
                    // Themes
                    [
                        'group_id' => $group->id,
                        'name' => 'StaffGroups.permissions.admin_company_themes',
                        'alias' => 'admin_company_themes',
                        'action' => '*'
                    ],
                ];
                foreach ($permissions as $vars) {
                    // If the permission exists skip it
                    if ($this->Permissions->getByAlias($vars['alias'], null, $vars['action'])) {
                        continue;
                    }

                    $this->Permissions->add($vars);

                    foreach ($staff_groups as $staff_group) {
                        // If staff group has access to similar item, grant access to this item
                        $access = false;
                        if (isset($staff_group_access[$staff_group->id][$vars['alias'] . '::' . $vars['action']])) {
                            $access = $staff_group_access[$staff_group->id][$vars['alias'] . '::' . $vars['action']];
                        } elseif (isset($staff_group_access[$staff_group->id][$vars['alias'] . '::*'])) {
                            $access = $staff_group_access[$staff_group->id][$vars['alias'] . '::*'];
                        }

                        if ($access) {
                            $this->Acl->allow('staff_group_' . $staff_group->id, $vars['alias'], $vars['action']);
                        }
                    }
                }
            }

            // Remove unused permissions
            $remove_permissions = [
                [
                    'alias' => 'admin_company_general',
                    'action' => 'themes'
                ],
                [
                    'alias' => 'admin_company_general',
                    'action' => 'addtheme'
                ],
                [
                    'alias' => 'admin_company_general',
                    'action' => 'edittheme'
                ],
                [
                    'alias' => 'admin_company_general',
                    'action' => 'deletetheme'
                ]
            ];

            foreach ($remove_permissions as $vars) {
                if ($permission = $this->Permissions->getByAlias($vars['alias'], null, $vars['action'])) {
                    $this->Permissions->delete($permission->id);
                }
            }

            // Clear cache for each staff group
            foreach ($staff_groups as $staff_group) {
                Cache::clearCache('nav_staff_group_' . $staff_group->id, $staff_group->company_id . DS . 'nav' . DS);
            }
        }
    }

    /**
     * Adds a new 'client_create_addons' company/client group setting
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    public function addClientAddonSetting($undo = false)
    {
        if ($undo) {
            // Remove the new setting
            $this->Record->from('client_group_settings')->where('key', '=', 'client_create_addons')->delete();
            $this->Record->from('company_settings')->where('key', '=', 'client_create_addons')->delete();
        } else {
            // Add the new setting. Default to 'false' to maintain current behavior for existing installations
            // only, assuming they have clients
            $clients = $this->Record->select()->from('clients')->numResults();
            $setting_value = ($clients > 0 ? 'false' : 'true');

            // Add to client group settings
            $client_groups = $this->Record->select()->from('client_groups')->getStatement();
            foreach ($client_groups as $group) {
                $this->Record->insert(
                    'client_group_settings',
                    ['key' => 'client_create_addons', 'value' => $setting_value, 'client_group_id' => $group->id]
                );
            }

            // Add to company settings
            $companies = $this->Record->select()->from('companies')->getStatement();
            foreach ($companies as $company) {
                $this->Record->insert(
                    'company_settings',
                    ['key' => 'client_create_addons', 'value' => $setting_value, 'company_id' => $company->id]
                );
            }
        }
    }

    /**
     * Updates the service_suspension and service_unsuspension email templates for new installations
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    public function updateServiceSuspensionEmails($undo = false)
    {
        // Fetch the email groups
        $actions = ['service_suspension', 'service_unsuspension'];
        $email_groups = [];
        foreach ($actions as $action) {
            $email_group = $this->Record->select()->from('email_groups')->where('action', '=', $action)->fetch();

            if ($email_group) {
                $email_groups[] = $email_group->id;
            }
        }

        if ($undo) {
            // Remove only the email change. The {service.name} tag can remain since it already exists
            $fields = [
                'from' => '{package.name} - {service.name}',
                'to' => '{package.name}'
            ];
        } else {
            // Update the email templates, but only for new installations, assuming new installations do
            // not have clients
            $clients = $this->Record->select()->from('clients')->numResults();

            if ($clients == 0) {
                // Update the email groups to add a new tag label, {service.name}
                $tags = '{contact.first_name},{contact.last_name},{package.name},{service.name}';
                $fields = [
                    'from' => '{package.name}',
                    'to' => '{package.name} - {service.name}'
                ];

                foreach ($email_groups as $email_group_id) {
                    $this->Record->where('id', '=', $email_group_id)->update('email_groups', ['tags' => $tags]);
                }
            }
        }

        // Update the email templates in use
        if (isset($fields)) {
            foreach ($email_groups as $email_group_id) {
                $emails = $this->Record->select()
                    ->from('emails')
                    ->where('email_group_id', '=', $email_group_id)
                    ->fetchAll();

                foreach ($emails as $email) {
                    $vars = [
                        'text' => str_replace($fields['from'], $fields['to'], $email->text),
                        'html' => str_replace($fields['from'], $fields['to'], $email->html)
                    ];
                    $this->Record->where('id', '=', $email->id)->update('emails', $vars);
                }
            }
        }
    }
}
