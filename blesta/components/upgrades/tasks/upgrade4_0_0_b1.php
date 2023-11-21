<?php
/**
 * Upgrades to version 4.0.0-b1
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2015, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade4_0_0B1 extends UpgradeUtil
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
            'addDefaultTheme',
            'updateAdminThemeSettings',
            'addDefaultClientTheme',
            'removeCompanyTaxIdInheritance',
            'fixPaymentApprovedCompanyEmailTag'
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
     * Adds a new default admin theme
     *
     * @param bool $undo Whether to add or undo the change
     */
    public function addDefaultTheme($undo = false)
    {
        $this->addTheme($undo, 'admin');
    }

    /**
     * Adds a new default client theme
     *
     * @param bool $undo Whether to add or undo the change
     */
    public function addDefaultClientTheme($undo = false)
    {
        $this->addTheme($undo, 'client');
    }

    /**
     * Makes the tax_id setting uninheritable for all companies
     *
     * @param bool $undo Whether to add or undo the change
     */
    public function removeCompanyTaxIdInheritance($undo = false)
    {
        // No undoing this fix
        if ($undo) {
            return;
        }

        $this->Record->where('key', '=', 'tax_id')
            ->update('company_settings', ['inherit' => 0]);
    }

    /**
     * Fixes the CC and ACH Payment Approved email template {company.name} tag
     *
     * @param bool $undo Whether to add or undo the change
     */
    public function fixPaymentApprovedCompanyEmailTag($undo = false)
    {
        // No undoing this fix
        if ($undo) {
            return;
        }

        // Fetch all companies
        $companies = $this->getAllCompanies();

        foreach ($companies as $company) {
            // Fetch all languages installed for this company
            $languages = $this->getAllLanguages($company->id);

            // Update the email templates for each installed language
            foreach ($languages as $language) {
                // Fetch the emails to update
                foreach (['payment_cc_approved', 'payment_ach_approved'] as $email_action) {
                    $email = $this->getEmail($company->id, $email_action, $language->code);

                    // Update the email to replace the erroneous {company_name} tag
                    if ($email) {
                        // Only replace exact matches
                        $vars = [
                            'text' => str_replace('{company_name}', '{company.name}', $email->text)
                        ];

                        if ($email->text != $vars['text']) {
                            $this->Record->where('id', '=', $email->id)
                                ->update('emails', $vars, ['text']);
                        }
                    }
                }
            }
        }
    }

    /**
     * Retrieves an email template
     *
     * @param int $company_id The company ID
     * @param string $email_action The email action
     * @param string $language_code The email language
     * @return mixed An stdClass object representing the email, otherwise bool false
     */
    private function getEmail($company_id, $email_action, $language_code)
    {
        return $this->Record->select(['emails.*'])
            ->from('emails')
            ->innerJoin('email_groups', 'email_groups.id', '=', 'emails.email_group_id', false)
            ->where('emails.company_id', '=', $company_id)
            ->where('emails.lang', '=', $language_code)
            ->where('email_groups.action', '=', $email_action)
            ->fetch();
    }

    /**
     * Retrieves a list of all languages installed for this company
     *
     * @param int $company_id The company ID
     * @return array An array of stdClass objects representing each company
     */
    private function getAllLanguages($company_id)
    {
        return $this->Record->select()
            ->from('languages')
            ->where('company_id', '=', $company_id)
            ->fetchAll();
    }

    /**
     * Retrieves a list of all companies
     *
     * @return array An array of stdClass objects representing each company
     */
    private function getAllCompanies()
    {
        return $this->Record->select()
            ->from('companies')
            ->fetchAll();
    }

    /**
     * Adds the new default FOUR theme
     *
     * @param bool $undo Whether to undo the theme
     * @param string $type The type of theme to add, 'admin' or 'client', default 'admin'
     */
    private function addTheme($undo = false, $type = 'admin')
    {
        // Fetch the theme data
        $theme = $this->getTheme($type);

        $theme_data = [
            'colors' => $theme['colors'],
            'logo_url' => $theme['logo_url']
        ];

        $new_theme = [
            'company_id' => null,
            'name' => $theme['name'],
            'type' => $theme['type'],
            'data' => base64_encode(serialize($theme_data))
        ];

        if ($undo) {
            // Remove the theme from all companies
            $this->Record->where('company_id', '=', $new_theme['company_id'])
                ->where('name', '=', $new_theme['name'])
                ->where('type', '=', $new_theme['type'])
                ->where('data', '=', $new_theme['data'])
                ->delete(['themes.*']);
        } else {
            // Add the new theme
            $this->Record->insert('themes', $new_theme);

            // Add the current Blesta Blue default theme to every company
            $this->switchThemes($this->Record->lastInsertId(), $type);
        }
    }

    /**
     * Updates all admin themes to include new theme settings
     *
     * @param bool $undo True to undo the change, or false to perform the change
     */
    public function updateAdminThemeSettings($undo = false)
    {
        if ($undo) {
            return;
        }

        // Update the following theme settings
        $settings = [
            'colors' => [
                'theme_box_shadow_color' => 'bfbfbf'
            ]
        ];

        // Fetch every admin theme that is not the default theme (FOUR)
        $themes = $this->Record->select()
            ->from('themes')
            ->where('type', '=', 'admin')
            ->where('company_id', '!=', null)
            ->fetchAll();

        // Update the settings for each theme
        foreach ($themes as $theme) {
            $data = unserialize(base64_decode($theme->data));

            // Skip themes with no color data
            if (!isset($data['colors'])) {
                continue;
            }

            // Update the theme data
            $vars = [
                'data' => base64_encode(serialize([
                    'colors' => array_merge((array)$data['colors'], $settings['colors']),
                    'logo_url' => isset($data['logo_url']) ? $data['logo_url'] : ''
                ]))
            ];

            $this->Record->where('id', '=', $theme->id)
                ->update('themes', $vars, ['data']);
        }
    }

    /**
     * Adds the default Blesta Blue theme to every company and sets the default
     * theme to the new theme on companies that are using Blesta Blue
     *
     * @param int $theme_id The ID of the new theme
     * @param string $type The type of theme, 'admin' or 'client', default admin
     */
    private function switchThemes($theme_id, $type = 'admin')
    {
        // Fetch the default Blesta Blue theme
        $old_theme = $this->Record->select()
            ->from('themes')
            ->where('company_id', '=', null)
            ->where('name', '=', 'Blesta Blue')
            ->where('type', '=', $type)
            ->fetch();

        $company_theme_key = 'theme_' . $type;

        // Add the Blesta Blue theme to every company
        if ($old_theme) {
            $companies = $this->Record->select()->from('companies')->fetchAll();
            $fields = ['company_id', 'name', 'type', 'data'];

            foreach ($companies as $company) {
                $data = (array)clone $old_theme;
                $data['company_id'] = $company->id;
                $this->Record->insert('themes', $data, $fields);

                // Update this company to use the new default theme if they are
                // currently using the old default theme
                $this->Record->where('value', '=', $old_theme->id)
                    ->where('key', '=', $company_theme_key)
                    ->where('company_id', '=', $company->id)
                    ->update('company_settings', ['value' => $theme_id]);
            }

            // Delete the old theme
            $this->Record->from('themes')
                ->where('id', '=', $old_theme->id)
                ->delete();
        }
    }

    /**
     * Retrieves the theme fields for the new FOUR admin theme
     *
     * @param string $type The type of theme, 'admin' or 'client', default admin
     * @return array The theme fields
     */
    private function getTheme($type = 'admin')
    {
        if ($type === 'client') {
            $colors = [
                'theme_header_bg_color_top' => 'ffffff',
                'theme_header_bg_color_bottom' => 'ffffff',
                'theme_page_title_background_color_top' => 'f5f5f5',
                'theme_page_title_background_color_bottom' => 'f0f0f0',
                'theme_page_title_text_color' => '3a3a3a',
                'theme_navigation_background_color_top' => '4f4f4f',
                'theme_navigation_background_color_bottom' => '4f4f4f',
                'theme_navigation_text_color' => 'ebebeb',
                'theme_navigation_text_active_color' => 'ffffff',
                'theme_page_background_color' => 'ffffff',
                'theme_panel_header_background_color_top' => '4f4f4f',
                'theme_panel_header_background_color_bottom' => '4f4f4f',
                'theme_panel_header_text_color' => 'f2f2f2',
                'theme_link_color' => '0074b2',
                'theme_link_settings_color' => '4f4f4f',
                'theme_highlight_hover_color' => 'e0f5ff'
            ];
        } else {
            $colors = [
                'theme_header_bg_color_top' => '333333',
                'theme_header_bg_color_bottom' => '333333',
                'theme_header_text_color' => 'ffffff',
                'theme_navigation_background_color_top' => '4c4c4c',
                'theme_navigation_background_color_bottom' => '4c4c4c',
                'theme_navigation_text_color' => 'ffffff',
                'theme_navigation_text_hover_color' => '6e6e6e',
                'theme_subnavigation_bg_color_top' => 'eaeaea',
                'theme_subnavigation_bg_color_bottom' => 'eaeaea',
                'theme_subnavigation_text_color' => '8c8c8c',
                'theme_subnavigation_text_active_color' => '3d3d3d',
                'theme_widget_heading_bg_color_top' => 'eaeaea',
                'theme_widget_heading_bg_color_bottom' => 'eaeaea',
                'theme_widget_icon_heading_bg_color_top' => 'd2d2d2',
                'theme_widget_icon_heading_bg_color_bottom' => 'e2e1e1',
                'theme_box_text_color' => '6e6e6e',
                'theme_text_shadow' => 'ffffff',
                'theme_actions_text_color' => '157bb8',
                'theme_highlight_bg_color' => 'ecf7ff',
                'theme_box_shadow_color' => 'transparent'
            ];
        }

        return [
            'name' => 'FOUR',
            'type' => $type,
            'colors' => $colors,
            'logo_url' => ''
        ];
    }
}
