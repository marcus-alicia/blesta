<?php

/**
 * Company Theme Settings
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Themes extends AppModel
{
    /**
     * @var Available Staff Theme color fields. Each array VALUE must be unique
     */
    private $staff_theme_colors = [
        'theme_header_bg_color' => ['theme_header_bg_color_top', 'theme_header_bg_color_bottom'],
        'theme_header_text_color' => ['theme_header_text_color'],
        'theme_navigation_background_color' => [
            'theme_navigation_background_color_top',
            'theme_navigation_background_color_bottom'
        ],
        'theme_navigation_text_color' => ['theme_navigation_text_color'],
        'theme_navigation_text_hover_color' => ['theme_navigation_text_hover_color'],
        'theme_subnavigation_bg_color' => ['theme_subnavigation_bg_color_top', 'theme_subnavigation_bg_color_bottom'],
        'theme_subnavigation_text_color' => ['theme_subnavigation_text_color'],
        'theme_subnavigation_text_active_color' => ['theme_subnavigation_text_active_color'],
        'theme_widget_heading_bg_color' => [
            'theme_widget_heading_bg_color_top',
            'theme_widget_heading_bg_color_bottom'
        ],
        'theme_widget_icon_heading_bg_color' => [
            'theme_widget_icon_heading_bg_color_top',
            'theme_widget_icon_heading_bg_color_bottom'
        ],
        'theme_box_text_color' => ['theme_box_text_color'],
        'theme_text_shadow' => ['theme_text_shadow'],
        'theme_actions_text_color' => ['theme_actions_text_color'],
        'theme_highlight_bg_color' => ['theme_highlight_bg_color'],
        'theme_box_shadow_color' => ['theme_box_shadow_color']
    ];

    /**
     * @var Available Client Theme color fields. Each array value must be unique
     */
    private $client_theme_colors = [
        'theme_header_bg_color' => ['theme_header_bg_color_top', 'theme_header_bg_color_bottom'],
        'theme_page_title_background_color' => [
            'theme_page_title_background_color_top',
            'theme_page_title_background_color_bottom'
        ],
        'theme_page_title_text_color' => ['theme_page_title_text_color'],
        'theme_navigation_background_color' => [
            'theme_navigation_background_color_top',
            'theme_navigation_background_color_bottom'
        ],
        'theme_navigation_text_color' => ['theme_navigation_text_color'],
        'theme_navigation_text_active_color' => ['theme_navigation_text_active_color'],
        'theme_page_background_color' => ['theme_page_background_color'],
        'theme_panel_header_background_color' => [
            'theme_panel_header_background_color_top',
            'theme_panel_header_background_color_bottom'
        ],
        'theme_panel_header_text_color' => ['theme_panel_header_text_color'],
        'theme_link_color' => ['theme_link_color'],
        'theme_link_settings_color' => ['theme_link_settings_color'],
        'theme_highlight_hover_color' => ['theme_highlight_hover_color'],
        'theme_highlight_navigation_text_color_top' => ['theme_highlight_navigation_text_color_top'],
        'theme_highlight_navigation_hover_text_color_top' => ['theme_highlight_navigation_hover_text_color_top'],
        'theme_primary_alert_color' => ['theme_primary_alert_text_color', 'theme_primary_alert_background_color', 'theme_primary_alert_border_color'],
        'theme_secondary_alert_color' => ['theme_secondary_alert_text_color', 'theme_secondary_alert_background_color', 'theme_secondary_alert_border_color'],
        'theme_success_alert_color' => ['theme_success_alert_text_color', 'theme_success_alert_background_color', 'theme_success_alert_border_color'],
        'theme_info_alert_color' => ['theme_info_alert_text_color', 'theme_info_alert_background_color', 'theme_info_alert_border_color'],
        'theme_warning_alert_color' => ['theme_warning_alert_text_color', 'theme_warning_alert_background_color', 'theme_warning_alert_border_color'],
        'theme_danger_alert_color' => ['theme_danger_alert_text_color', 'theme_danger_alert_background_color', 'theme_danger_alert_border_color'],
        'theme_light_alert_color' => ['theme_light_alert_text_color', 'theme_light_alert_background_color', 'theme_light_alert_border_color'],
        'theme_primary_button_color' => ['theme_primary_button_text_color', 'theme_primary_button_background_color', 'theme_primary_button_hover_color'],
        'theme_secondary_button_color' => ['theme_secondary_button_text_color', 'theme_secondary_button_background_color', 'theme_secondary_button_hover_color'],
        'theme_success_button_color' => ['theme_success_button_text_color', 'theme_success_button_background_color', 'theme_success_button_hover_color'],
        'theme_info_button_color' => ['theme_info_button_text_color', 'theme_info_button_background_color', 'theme_info_button_hover_color'],
        'theme_warning_button_color' => ['theme_warning_button_text_color', 'theme_warning_button_background_color', 'theme_warning_button_hover_color'],
        'theme_danger_button_color' => ['theme_danger_button_text_color', 'theme_danger_button_background_color', 'theme_danger_button_hover_color'],
        'theme_light_button_color' => ['theme_light_button_text_color', 'theme_light_button_background_color', 'theme_light_button_hover_color']
    ];

    /**
     * Initialize Themes
     */
    public function __construct()
    {
        parent::__construct();
        Language::loadLang(['themes']);
    }

    /**
     * Retrieves a list of available theme color keys
     *
     * @param string $type The type of theme colors to fetch (i.e. "admin" or "client", optional, default "admin")
     * @return array A list of available theme color keys
     */
    public function getAvailableColors($type = 'admin')
    {
        if ($type == 'client') {
            return $this->client_theme_colors;
        }
        return $this->staff_theme_colors;
    }

    /**
     * Changes the theme to another theme for the given company
     *
     * @param int $id The theme ID of the theme to change to
     * @param string $type The theme type ("admin" or "client", optional, default "admin")
     */
    public function change($id, $type = 'admin')
    {
        // Set theme updating rules
        $rules = [
            'id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'themes'],
                    'message' => $this->_('Themes.!error.id.exists', true)
                ]
            ],
            'company_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'companies'],
                    'message' => $this->_('Themes.!error.company_id.exists', true)
                ]
            ],
            'type' => [
                'format' => [
                    'rule' => ['in_array', array_keys($this->getTypes())],
                    'message' => $this->_('Themes.!error.type.format', true)
                ]
            ]
        ];

        $this->Input->setRules($rules);

        $company_id = (Configure::get('Blesta.company_id') ? Configure::get('Blesta.company_id') : null);
        $vars = [
            'id' => (int) $id,
            'company_id' => $company_id,
            'type' => $type
        ];

        // Update theme
        if ($this->Input->validates($vars)) {
            $this->Record->duplicate('value', '=', $vars['id'])->
                insert('company_settings', ['key' => 'theme_' . $type, 'company_id' => $company_id, 'value' => $id]);

            // Update logo
            $theme = $this->get($id);
            $this->Record->duplicate('value', '=', $theme->logo_url)->
                insert('company_settings', [
                    'key' => 'logo_' . $type,
                    'company_id' => $company_id,
                    'value' => $theme->logo_url
                ]
            );
        }
    }

    /**
     * Fetches a specific theme
     *
     * @param int $id The theme ID
     * @return mixed An stdClass object representing the theme, or false if no results.
     */
    public function get($id)
    {
        $theme = $this->Record->select()->from('themes')->where('id', '=', $id)->fetch();

        if ($theme) {
            return $this->formatData($theme);
        }
        return false;
    }

    /**
     * Fetches the default theme of the given type (limit 1)
     *
     * @param string $type The type of theme to fetch ("admin", or "client"; optional, default "admin")
     * @return mixed An stdClass object representing the default theme of the given type, or false if none exist
     */
    public function getDefault($type = 'admin')
    {
        $theme = $this->Record->select()->from('themes')->where('company_id', '=', null)->
            where('type', '=', $type)->fetch();

        if ($theme) {
            return $this->formatData($theme);
        }
        return false;
    }

    /**
     * Adds a theme to this company
     *
     * @param array $vars An array of theme info including:
     *
     *  - name The name of the theme
     *  - logo_url The URL to the header logo
     *  - type The type of theme ("admin", or "client")
     *  - colors An array of color fields dependent on vars type, including:
     *      - Type 'admin':
     *          - theme_header_bg_color_top The header background hex code for the top gradient
     *          - theme_header_bg_color_bottom The header background hex code for the bottom gradient
     *          - theme_header_text_color The header text hex code
     *          - theme_navigation_background_color_top The header navigation background hex code top gradient
     *          - theme_navigation_background_color_bottom The header navigation background hex code bottom gradient
     *          - theme_navigation_text_color The header navigation text hex code
     *          - theme_navigation_text_hover_color The header navigation text hex code on hover
     *          - theme_subnavigation_bg_color_top The header subnavigation background color top gradient
     *          - theme_subnavigation_bg_color_bottom The header subnavigation background color bottom gradient
     *          - theme_subnavigation_text_color The header subnavigation text color
     *          - theme_subnavigation_text_active_color The header subnavigation text active color
     *          - theme_widget_heading_bg_color_top The widget heading background color top gradient
     *          - theme_widget_heading_bg_color_bottom The widget heading background color bottom gradient
     *          - theme_widget_icon_heading_bg_color_top The widget heading icon background color top gradient
     *          - theme_widget_icon_heading_bg_color_bottom The widget heading icon background color bottom gradient
     *          - theme_box_text_color General box hex code
     *          - theme_text_shadow General text shadow hex code
     *          - theme_actions_text_color General links hex code
     *          - theme_highlight_bg_color General text highlight/active-link hex code
     *          - theme_box_shadow_color The color of shadows shown under boxes
     *      - Type 'client':
     *          - theme_header_bg_color_top The header background hex code for the top gradient
     *          - theme_header_bg_color_bottom The header background hex code for the bottom gradient
     *          - theme_page_title_background_color_top The page title background hex code for the top gradient
     *          - theme_page_title_background_color_bottom The page title background hex code for the bottom gradient
     *          - theme_page_title_text_color The text color hex code for the page title
     *          - theme_navigation_background_color_top The navigation background hex code for the top gradient
     *          - theme_navigation_background_color_bottom The navigation background hex code for the bottom gradient
     *          - theme_navigation_text_color The hex code for the navigation text
     *          - theme_navigation_text_active_color The hex code for the navigation text when active
     *          - theme_page_background_color The page background hex code
     *          - theme_panel_header_background_color_top The page content header background hex code for the
     *              top gradient
     *          - theme_panel_header_background_color_bottom The page content
     *              header background hex code for the bottom gradient
     *          - theme_panel_header_text_color The text color of the page content header
     *          - theme_link_color General links hex code
     *          - theme_link_settings_color The settings links hex code
     *          - theme_highlight_hover_color General text highlight/hover hex code
     * @param int $company_id The ID of the company to add this theme to (optional, defaults to the current company)
     * @return int The ID of the theme added
     */
    public function add(array $vars, $company_id = null)
    {
        // Set theme add rules
        $vars['company_id'] = $company_id;
        if ($company_id === null) {
            $vars['company_id'] = (Configure::get('Blesta.company_id') ? Configure::get('Blesta.company_id') : null);
        }

        $this->Input->setRules($this->getRules($vars));

        if ($this->Input->validates($vars)) {
            // Update the theme
            $fields = ['company_id', 'name', 'type', 'data'];

            $theme_options = [
                'colors' => $vars['colors'],
                'logo_url' => (isset($vars['logo_url']) ? $vars['logo_url'] : '')
            ];

            $vars['data'] = base64_encode(serialize($theme_options));
            $this->Record->insert('themes', $vars, $fields);

            return $this->Record->lastInsertId();
        }
    }

    /**
     * Updates a theme belonging to this company
     *
     * @param int $theme_id The ID of the theme to update
     * @param array $vars An array of theme info including:
     *
     *  - name The name of the theme
     *  - logo_url The URL to the header logo
     *  - type The type of theme ("admin", "client")
     *  - colors An array of color fields including:
     *      - Type 'admin':
     *          - theme_header_bg_color_top The header background hex code for the top gradient
     *          - theme_header_bg_color_bottom The header background hex code for the bottom gradient
     *          - theme_header_text_color The header text hex code
     *          - theme_navigation_background_color_top The header navigation background hex code top gradient
     *          - theme_navigation_background_color_bottom The header navigation background hex code bottom gradient
     *          - theme_navigation_text_color The header navigation text hex code
     *          - theme_navigation_text_hover_color The header navigation text hex code on hover
     *          - theme_subnavigation_bg_color_top The header subnavigation background color top gradient
     *          - theme_subnavigation_bg_color_bottom The header subnavigation background color bottom gradient
     *          - theme_subnavigation_text_color The header subnavigation text color
     *          - theme_subnavigation_text_active_color The header subnavigation text active color
     *          - theme_widget_heading_bg_color_top The widget heading background color top gradient
     *          - theme_widget_heading_bg_color_bottom The widget heading background color bottom gradient
     *          - theme_widget_icon_heading_bg_color_top The widget heading icon background color top gradient
     *          - theme_widget_icon_heading_bg_color_bottom The widget heading icon background color bottom gradient
     *          - theme_box_text_color General box hex code
     *          - theme_text_shadow General text shadow hex code
     *          - theme_actions_text_color General links hex code
     *          - theme_highlight_bg_color General text highlight/active-link hex code
     *          - theme_box_shadow_color The color of shadows shown under boxes
     *      - Type 'client':
     *          - theme_header_bg_color_top The header background hex code for the top gradient
     *          - theme_header_bg_color_bottom The header background hex code for the bottom gradient
     *          - theme_page_title_background_color_top The page title background hex code for the top gradient
     *          - theme_page_title_background_color_bottom The page title background hex code for the bottom gradient
     *          - theme_page_title_text_color The text color hex code for the page title
     *          - theme_navigation_background_color_top The navigation background hex code for the top gradient
     *          - theme_navigation_background_color_bottom The navigation background hex code for the bottom gradient
     *          - theme_navigation_text_color The hex code for the navigation text
     *          - theme_navigation_text_active_color The hex code for the navigation text when active
     *          - theme_page_background_color The page background hex code
     *          - theme_panel_header_background_color_top The page content header background hex code for the
     *              top gradient
     *          - theme_panel_header_background_color_bottom The page content
     *              header background hex code for the bottom gradient
     *          - theme_panel_header_text_color The text color of the page content header
     *          - theme_link_color General links hex code
     *          - theme_link_settings_color The settings links hex code
     *          - theme_highlight_hover_color General text highlight/hover hex code
     */
    public function edit($theme_id, array $vars)
    {
        // Set theme edit rules
        $vars['id'] = (int) $theme_id;
        if (!isset($vars['company_id'])) {
            $vars['company_id'] = (Configure::get('Blesta.company_id') ? Configure::get('Blesta.company_id') : null);
        }

        $this->Input->setRules($this->getRules($vars, true));

        if ($this->Input->validates($vars)) {
            // Update the theme
            $fields = ['name', 'type', 'data'];

            $theme_options = [
                'colors' => $vars['colors'],
                'logo_url' => (isset($vars['logo_url']) ? $vars['logo_url'] : '')
            ];

            $data = base64_encode(serialize($theme_options));
            $vars['data'] = $data;
            $this->Record->where('id', '=', $vars['id'])->update('themes', $vars, $fields);

            // Update logo
            $theme = $this->get($theme_id);
            $current_theme = $this->getCurrent($theme->company_id, $theme->type);

            if ($current_theme && $theme->id == $current_theme->id && !empty($theme->logo_url)) {
                $this->Record->duplicate('value', '=', $theme->logo_url)->
                    insert('company_settings', [
                        'key' => 'logo_' . $theme->type,
                        'company_id' => $theme->company_id,
                        'value' => $theme->logo_url
                    ]
                );
            }
        }
    }

    /**
     * Deletes a theme belonging to this company
     *
     * @param int $theme_id The theme ID of the theme to delete
     */
    public function delete($theme_id)
    {
        $vars = [
            'id' => (int) $theme_id,
            'company_id' => (Configure::get('Blesta.company_id') ? Configure::get('Blesta.company_id') : null)
        ];

        // Set theme delete rules
        $rules = [
            'id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'themes'],
                    'message' => $this->_('Themes.!error.id.exists', true)
                ]
            ],
            'company_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'companies'],
                    'message' => $this->_('Themes.!error.company_id.exists', true)
                ],
                'isset' => [
                    'rule' => [[$this, 'validateCompanySet'], $vars['id']],
                    'message' => $this->_('Themes.!error.company_id.isset', true)
                ]
            ]
        ];

        $this->Input->setRules($rules);

        // Delete the theme
        if ($this->Input->validates($vars)) {
            $this->Record->from('themes')->where('id', '=', $vars['id'])->delete();
        }
    }

    /**
     * Clones the themes from one company and adds them to another
     *
     * @param int $from_company_id The ID of the company to duplicate the themes from
     * @param int $to_company_id The ID of the company to add the themes to
     */
    public function cloneThemes($from_company_id, $to_company_id)
    {
        // Fetch the themes
        $themes = $this->getAll(null, $from_company_id);

        // Add each theme
        foreach ($themes as $theme) {
            // Skip the default themes
            if ($theme->company_id === null) {
                continue;
            }

            $this->add((array) $theme, $to_company_id);
        }
    }

    /**
     * Fetches the current theme in use by a given company. The theme is inherited
     * in the order of company settings -> system settings where "->" represents the
     * left item inheriting (and overwriting in the case of duplicates) values
     * found in the right item.
     *
     * @param int $company_id The company ID
     * @param string $type The type of theme to fetch (i.e. "admin" or "client", optional, default "admin")
     * @return mixed An array of objects containg key/values for the theme, false if no records found
     */
    public function getCurrent($company_id, $type = 'admin')
    {
        // Company Settings
        $sql1 = $this->Record->select(['key', 'value'])
            ->from('company_settings')
            ->where('company_id', '=', $company_id)
            ->get();
        $values = $this->Record->values;
        $this->Record->reset();
        $this->Record->values = $values;

        // System settings, in some cases no company theme may be set so inherit the system theme
        $sql2 = $this->Record->select(['key', 'value'])->from('settings')->get();
        $values = $this->Record->values;
        $this->Record->reset();
        $this->Record->values = $values;

        // Set the type of theme to fetch
        $type_field = 'theme_admin';
        if ($type == 'client') {
            $type_field = 'theme_client';
        }

        $theme_setting = $this->Record->select()->from(['((' . $sql1 . ') UNION (' . $sql2 . '))' => 'temp'])->
            where('temp.key', '=', $type_field)->fetch();

        if ($theme_setting) {
            return $this->get($theme_setting->value);
        }
        return false;
    }

    /**
     * Fetches all themes in the system
     *
     * @param string $type The type of themes to get (i.e. "admin", "client", or null for all. optional, default null)
     * @param $company_id The ID of the company whose themes to fetch
     *  (optional, defaults to the current company's themes)
     * @return array An array of stdClass objects representing each theme
     */
    public function getAll($type = null, $company_id = null)
    {
        // Fetch all general themes
        $this->Record->select()->from('themes');

        // Filter on type
        if ($type) {
            $this->Record->where('type', '=', $type);
        }

        $this->Record->open()->
            where('company_id', '=', null);

        // Include company-specific themes
        if ($company_id === null && Configure::get('Blesta.company_id')) {
            $this->Record->orWhere('company_id', '=', Configure::get('Blesta.company_id'));
        } elseif ($company_id !== null) {
            $this->Record->orWhere('company_id', '=', $company_id);
        }

        $themes = $this->Record->close()->
            order(['company_id' => 'asc', 'name' => 'asc'])->
            fetchAll();

        // Format the theme data
        foreach ($themes as &$theme) {
            $theme = $this->formatData($theme);
        }

        return $themes;
    }

    /**
     * Retrieves a list of the theme types and their language
     *
     * @return array A key/value list of theme types and their language
     */
    public function getTypes()
    {
        return [
            'admin' => $this->_('Themes.type.admin'),
            'client' => $this->_('Themes.type.client')
        ];
    }

    /**
     * Validates that the given $company_id belongs to the given theme
     *
     * @param int $company_id The company ID
     * @param int $theme_id The theme ID
     * @return bool True if the given company ID belongs to the given theme, or false otherwise
     */
    public function validateCompanySet($company_id, $theme_id)
    {
        $count = $this->Record->select('id')->from('themes')->where('id', '=', $theme_id)->
            where('company_id', '=', $company_id)->numResults();

        if ($count > 0) {
            return true;
        }
        return false;
    }

    /**
     * Validates that the given theme $colors exist and are set
     *
     * @param array $colors An array of colors including:
     *
     *  - Type 'admin':
     *      - theme_header_bg_color_top The header background hex code for the top gradient
     *      - theme_header_bg_color_bottom The header background hex code for the bottom gradient
     *      - theme_header_text_color The header text hex code
     *      - theme_navigation_background_color_top The header navigation background hex code top gradient
     *      - theme_navigation_background_color_bottom The header navigation background hex code bottom gradient
     *      - theme_navigation_text_color The header navigation text hex code
     *      - theme_navigation_text_hover_color The header navigation text hex code on hover
     *      - theme_subnavigation_bg_color_top The header subnavigation background color top gradient
     *      - theme_subnavigation_bg_color_bottom The header subnavigation background color bottom gradient
     *      - theme_subnavigation_text_color The header subnavigation text color
     *      - theme_subnavigation_text_active_color The header subnavigation text active color
     *      - theme_widget_heading_bg_color_top The widget heading background color top gradient
     *      - theme_widget_heading_bg_color_bottom The widget heading background color bottom gradient
     *      - theme_widget_icon_heading_bg_color_top The widget heading icon background color top gradient
     *      - theme_widget_icon_heading_bg_color_bottom The widget heading icon background color bottom gradient
     *      - theme_box_text_color General box hex code
     *      - theme_text_shadow General text shadow hex code
     *      - theme_actions_text_color General links hex code
     *      - theme_highlight_bg_color General text highlight/active-link hex code
     *      - theme_box_shadow_color The color of shadows shown under boxes
     *  - Type 'client':
     *      - theme_header_bg_color_top The header background hex code for the top gradient
     *      - theme_header_bg_color_bottom The header background hex code for the bottom gradient
     *      - theme_page_title_background_color_top The page title background hex code for the top gradient
     *      - theme_page_title_background_color_bottom The page title background hex code for the bottom gradient
     *      - theme_page_title_text_color The text color hex code for the page title
     *      - theme_navigation_background_color_top The navigation background hex code for the top gradient
     *      - theme_navigation_background_color_bottom The navigation background hex code for the bottom gradient
     *      - theme_navigation_text_color The hex code for the navigation text
     *      - theme_navigation_text_active_color The hex code for the navigation text when active
     *      - theme_page_background_color The page background hex code
     *      - theme_panel_header_background_color_top The page content header background hex code for the top gradient
     *      - theme_panel_header_background_color_bottom The page content
     *          header background hex code for the bottom gradient
     *      - theme_panel_header_text_color The text color of the page content header
     *      - theme_link_color General links hex code
     *      - theme_link_settings_color The settings links hex code
     *      - theme_highlight_hover_color General text highlight/hover hex code
     * @param string $type The theme type ("admin" or "client", optional, default "admin")
     * @return bool True if the colors exist and are set, false otherwise
     */
    public function validateColorsSet($colors, $type = 'admin')
    {
        if (!isset($colors) || !is_array($colors)) {
            return false;
        }

        $num_keys_found = 0;
        $theme_type_colors = ($type == 'client' ? $this->client_theme_colors : $this->staff_theme_colors);

        // Set available theme color codes
        $theme_color_codes = [];
        foreach ($theme_type_colors as $key => $theme_colors) {
            foreach ($theme_colors as $color) {
                $theme_color_codes[] = $color;
            }
        }

        // Validate the colors given exist
        foreach ($colors as $key => $hex_code) {
            // Count how many theme colors we're given that we expect
            if (in_array($key, $theme_color_codes)) {
                $num_keys_found++;
            }

            // Check that the hex code is valid ('transparent' is also valid)
            if (!$this->validateHexCode($hex_code)) {
                return false;
            }
        }

        // Check that every theme color key was given
        $num_theme_keys = count($theme_color_codes);
        if ($num_theme_keys != $num_keys_found) {
            return false;
        }
        return true;
    }

    /**
     * Formats the given color codes to set blank hex codes to 'transparent'
     *
     * @param array $colors A key/value array of admin/client colors
     * @return array An array of updated colors
     */
    public function formatMissingColors($colors)
    {
        if (!isset($colors) || !is_array($colors)) {
            return $colors;
        }

        foreach ($colors as $key => &$color) {
            if ($color === '') {
                $color = 'transparent';
            }
        }

        return $colors;
    }

    /**
     * Validates that the given string is a valid 6-character hex code
     *
     * @param string $hex_code A hex color code
     * @return bool True if the given hex code is a valid 6-character hex code, or false otherwise
     */
    private function validateHexCode($hex_code)
    {
        if ($hex_code === 'transparent' || preg_match('/^[0-9a-f]{6}$/i', $hex_code)) {
            return true;
        }
        return false;
    }

    /**
     * Formats the given theme's serialized and encoded data into useful fields
     *
     * @param stdClass $theme An stdClass representing the theme
     * @return stdClass An updated theme object
     */
    private function formatData(stdClass $theme)
    {
        // Add data fields
        if ($theme) {
            // Set any theme data
            $data = unserialize(base64_decode($theme->data));

            if ($data) {
                $theme->colors = $data['colors'];
                $theme->logo_url = isset($data['logo_url']) ? $data['logo_url'] : '';
            } else {
                $theme->colors = [];
                $theme->logo_url = null;
            }

            unset($theme->data);
        }

        return $theme;
    }

    /**
     * Retrieves a list of rules for adding/editing/deleting a theme
     *
     * @param array $vars A list of input vars for use with the rules
     * @param bool $edit True when editing a theme, false when adding
     * @return array The rules for adding or editing a theme
     */
    private function getRules(array $vars = [], $edit = false)
    {
        // Set add rules
        $rules = [
            'company_id' => [
                'exists' => [
                    'rule' => function ($company_id) {
                        return $company_id === null || $this->validateExists($company_id, 'id', 'companies');
                    },
                    'message' => $this->_('Themes.!error.company_id.exists', true)
                ]
            ],
            'name' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('Themes.!error.name.empty', true)
                ],
                'length' => [
                    'rule' => ['maxLength', 64],
                    'message' => $this->_('Themes.!error.name.length', true)
                ]
            ],
            'type' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['in_array', array_keys($this->getTypes())],
                    'message' => $this->_('Themes.!error.type.format', true)
                ]
            ],
            'colors' => [
                'exist' => [
                    'rule' => [[$this, 'validateColorsSet'], (isset($vars['type']) ? $vars['type'] : null)],
                    'message' => $this->_('Themes.!error.colors.exist', true),
                    'pre_format' => [[$this, 'formatMissingColors']]
                ]
            ]
        ];

        // Set edit-specific rules
        if ($edit) {
            $rules['id'] = [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'themes'],
                    'message' => $this->_('Themes.!error.id.exists', true)
                ]
            ];

            $rules['company_id']['set'] = [
                'rule' => [[$this, 'validateCompanySet'], (isset($vars['id']) ? $vars['id'] : null), (isset($vars['type']) ? $vars['type'] : null)],
                'message' => $this->_('Themes.!error.company_id.set', true)
            ];
        }

        return $rules;
    }
}
