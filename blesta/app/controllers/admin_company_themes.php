<?php

/**
 * Admin Company Theme Settings
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminCompanyThemes extends AppController
{
    /**
     * Pre-action
     */
    public function preAction()
    {
        parent::preAction();

        // Require login
        $this->requireLogin();

        $this->uses(['Navigation', 'Themes']);
        $this->components(['SettingsCollection', 'Session']);
        $this->helpers(['DataStructure']);

        $this->ArrayHelper = $this->DataStructure->create('Array');

        Language::loadLang('admin_company_themes');

        // Set the left nav for all settings pages to settings_leftnav
        $this->set(
            'left_nav',
            $this->partial('settings_leftnav', ['nav' => $this->Navigation->getCompany($this->base_uri)])
        );
    }

    /**
     * Themes settings
     */
    public function index()
    {
        // Set the type of theme
        $theme_type = 'admin';
        if (isset($this->get[0]) && $this->get[0] == 'client') {
            $theme_type = $this->get[0];
        }

        if (!empty($this->post)) {
            $this->Themes->change($this->post['id'], $theme_type);

            if (($errors = $this->Themes->errors())) {
                $this->setMessage('error', $errors);
                $vars = (object) $this->post;
            } else {
                $this->flashMessage('message', Language::_('AdminCompanyThemes.!success.themes_updated', true));
                $this->redirect($this->base_uri . 'settings/company/themes/index/' . $theme_type . '/');
            }

            $this->Session->clear('blesta_theme');
        }

        // Set the current theme
        if (empty($vars)) {
            $vars = $this->Themes->getCurrent($this->company_id, $theme_type);
        }

        $this->set('vars', $vars);
        $this->set('themes', $this->Themes->getAll($theme_type));
        $this->set('colors', $this->Themes->getAvailableColors($theme_type));
        $this->set('theme_types', $this->Themes->getTypes());
        $this->set('selected_type', $theme_type);
    }

    /**
     * Add a theme
     */
    public function add()
    {
        // Set the type of theme
        $theme_type = 'admin';
        if (isset($this->get[0]) && $this->get[0] == 'client') {
            $theme_type = $this->get[0];
        }

        $vars = new stdClass();
        $vars->type = $theme_type;

        if (!empty($this->post)) {
            // Import a theme
            $error = false;
            if (!isset($this->post['add_type']) || $this->post['add_type'] == 'import') {
                // Check whether a file has been uploaded
                if (empty($this->files['import_file'])
                    || !isset($this->files['import_file']['error'])
                    || $this->files['import_file']['error'] != 0
                    || !($file_contents = file_get_contents($this->files['import_file']['tmp_name']))
                ) {
                    $error = true;
                    $this->setMessage(
                        'error',
                        [
                            'import_file' => [
                                'missing' => Language::_('AdminCompanyThemes.!error.import_file.missing', true)
                            ]
                        ]
                    );
                } else {
                    // Set the imported data
                    $this->post = (array) json_decode($file_contents, true);

                    // Check that the theme being imported matches the theme type
                    if (isset($this->post['type']) && $this->post['type'] != $theme_type) {
                        $error = true;
                        $this->post = [];
                        $this->setMessage(
                            'error',
                            [
                                'import_file' => [
                                    'theme_type' => Language::_(
                                        'AdminCompanyThemes.!error.import_file.theme_type_' . $theme_type,
                                        true
                                    )
                                ]
                            ]
                        );
                    }
                }
            }

            // Add the theme
            if (!$error) {
                $theme_id = $this->Themes->add($this->post, $this->company_id);

                if (($errors = $this->Themes->errors())) {
                    // Error, reset vars
                    $this->setMessage('error', $errors);
                    $vars = (object) $this->post;
                    $vars->add_type = 'manual';
                } else {
                    // Success
                    $theme = $this->Themes->get($theme_id);
                    $this->flashMessage(
                        'message',
                        Language::_('AdminCompanyThemes.!success.theme_added', true, $theme->name)
                    );
                    $redirect_path = (!isset($this->post['add_type'])
                        || $this->post['add_type'] == 'import'
                            ? 'edit/' . $theme->id . '/'
                            : 'index/' . $theme_type . '/'
                    );
                    $this->redirect($this->base_uri . 'settings/company/themes/' . $redirect_path);
                }
            }
        }

        // Format colors
        if (isset($vars->colors) && is_array($vars->colors)) {
            $vars->colors = $this->formatTransparentColors($vars->colors);
        }

        $this->set('colors', $this->Themes->getAvailableColors($theme_type));
        $this->set('vars', $vars);

        // Load the color picker
        $this->Javascript->setFile('colorpicker.min.js');
    }

    /**
     * Edit a theme
     */
    public function edit()
    {
        // Check theme exists and belongs to this company
        if (!isset($this->get[0])
            || !($theme = $this->Themes->get((int) $this->get[0]))
            || ($theme->company_id == null)
            || ($theme->company_id != $this->company_id)
        ) {
            $this->redirect($this->base_uri . 'settings/company/themes/');
        }

        if (!empty($this->post)) {
            // Set the theme type
            $data = $this->post;
            $data['type'] = $theme->type;

            $this->Themes->edit($theme->id, $data);

            if (($errors = $this->Themes->errors())) {
                // Error, reset vars
                $this->setMessage('error', $errors);
                $vars = (object) $this->post;
            } else {
                // Success
                $theme = $this->Themes->get($theme->id);
                $this->flashMessage(
                    'message',
                    Language::_('AdminCompanyThemes.!success.theme_updated', true, $theme->name)
                );
                $this->redirect($this->base_uri . 'settings/company/themes/index/' . $theme->type . '/');
            }
        }

        // Set theme
        if (empty($vars)) {
            $vars = $theme;
        }

        // Format colors
        if (isset($vars->colors) && is_array($vars->colors)) {
            $vars->colors = $this->formatTransparentColors($vars->colors);
        }

        $this->set('colors', $this->Themes->getAvailableColors($theme->type));
        $this->set('vars', $vars);

        // Load the color picker
        $this->Javascript->setFile('colorpicker.min.js');
    }

    /**
     * Delete a theme
     */
    public function delete()
    {
        // Check theme exists and belongs to this company
        if (!isset($this->post['id'])
            || !($theme = $this->Themes->get((int) $this->post['id']))
            || ($theme->company_id != $this->company_id)
        ) {
            $this->redirect($this->base_uri . 'settings/company/themes/');
        }

        // Delete the theme
        $this->Themes->delete($theme->id);

        if (($errors = $this->Themes->errors())) {
            // Error, could not delete theme
            $this->flashMessage('error', $errors);
        } else {
            // Success
            $this->flashMessage(
                'message',
                Language::_('AdminCompanyThemes.!success.theme_deleted', true, $theme->name)
            );
        }

        $this->redirect($this->base_uri . 'settings/company/themes/');
    }

    /**
     * Exports a theme
     */
    public function export()
    {
        // Check that the theme exists for this company
        if (!isset($this->get[0])
            || !($theme = $this->Themes->get((int) $this->get[0]))
            || ($theme->company_id !== null && $theme->company_id != $this->company_id)
        ) {
            $this->redirect($this->base_uri . 'settings/company/themes/');
        }

        $this->components(['Download']);

        // Export the theme
        $export = clone $theme;
        unset($export->id, $export->company_id);

        $theme_name = strtolower(str_replace(' ', '_', $export->name));
        $theme_name = substr(preg_replace('/[^a-z0-9_-]/i', '', $theme_name), 0, 249);
        $this->Download->downloadData('theme-' . $theme_name . '.json', json_encode($export));
        exit;
    }

    /**
     * Formats transparent colors into empty strings
     *
     * @param array $colors Formats the given color values to set 'transparent' to an empty string
     * @return array The formatted colors
     */
    private function formatTransparentColors(array $colors)
    {
        foreach ($colors as $key => &$value) {
            if ($value === 'transparent') {
                $value = '';
            }
        }

        return $colors;
    }
}
