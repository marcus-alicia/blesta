<?php
/**
 * Upgrades to version 3.3.0-b2
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2014, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade3_3_0B2 extends UpgradeUtil
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
            'addContestThemes',
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
     * Adds winning contest themes
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    private function addContestThemes($undo = false)
    {
        $companies = $this->Record->select()->from('companies')->fetchAll();
        $themes = $this->getContestThemes();

        foreach ($themes as $theme) {
            $theme_data = [
                'colors' => $theme['colors'],
                'logo_url' => $theme['logo_url']
            ];

            $new_theme = [
                'name' => $theme['name'],
                'type' => $theme['type'],
                'data' => base64_encode(serialize($theme_data))
            ];

            // Add or remove the theme
            if ($undo) {
                // Remove the theme
                foreach ($companies as $company) {
                    $this->Record->where('company_id', '=', $company->id)->
                        where('name', '=', $new_theme['name'])->where('type', '=', $new_theme['type'])->
                        where('data', '=', $new_theme['data'])->delete('themes');
                }
            } else {
                // Add the theme to every company
                foreach ($companies as $company) {
                    $temp_theme = $new_theme;
                    $temp_theme['company_id'] = $company->id;
                    $this->Record->insert('themes', $temp_theme);
                }
            }
        }
    }

    /**
     * Retrieves the winning contest themes
     *
     * @return array An array of contest themes
     */
    private function getContestThemes()
    {
        return [
            [
                'name' => 'Venustas',
                'type' => 'client',
                'colors' => [
                    'theme_header_bg_color_top' => '42124a',
                    'theme_header_bg_color_bottom' => '42124a',
                    'theme_page_title_background_color_top' => '5d1963',
                    'theme_page_title_background_color_bottom' => '5d1963',
                    'theme_page_title_text_color' => 'ffffff',
                    'theme_navigation_background_color_top' => '42124a',
                    'theme_navigation_background_color_bottom' => '42124a',
                    'theme_navigation_text_color' => 'ffffff',
                    'theme_navigation_text_active_color' => 'b5d98f',
                    'theme_page_background_color' => 'f8e8fc',
                    'theme_panel_header_background_color_top' => '42124a',
                    'theme_panel_header_background_color_bottom' => '5d1963',
                    'theme_panel_header_text_color' => 'ffffff',
                    'theme_link_color' => '202f60',
                    'theme_link_settings_color' => '000000',
                    'theme_highlight_hover_color' => 'e6e6e6'
                ],
                'logo_url' => ''
            ],
            [
                'name' => 'WHMBlesta',
                'type' => 'admin',
                'colors' => [
                    'theme_header_bg_color_top' => '333333',
                    'theme_header_bg_color_bottom' => '333333',
                    'theme_header_text_color' => 'ffffff',
                    'theme_navigation_background_color_top' => '4C4C4C',
                    'theme_navigation_background_color_bottom' => '4C4C4C',
                    'theme_navigation_text_color' => 'ffffff',
                    'theme_navigation_text_hover_color' => '6e6e6e',
                    'theme_subnavigation_bg_color_top' => 'EAEAEA',
                    'theme_subnavigation_bg_color_bottom' => 'EAEAEA',
                    'theme_subnavigation_text_color' => '6e6e6e',
                    'theme_subnavigation_text_active_color' => '6e6e6e',
                    'theme_widget_heading_bg_color_top' => 'EAEAEA',
                    'theme_widget_heading_bg_color_bottom' => 'EAEAEA',
                    'theme_widget_icon_heading_bg_color_top' => 'D0D0D0',
                    'theme_widget_icon_heading_bg_color_bottom' => 'D0D0D0',
                    'theme_box_text_color' => '6e6e6e',
                    'theme_text_shadow' => '000000',
                    'theme_actions_text_color' => '6e6e6e',
                    'theme_highlight_bg_color' => 'F78E1E'
                ],
                'logo_url' => ''
            ]
        ];
    }
}
