<?php

/**
 * Adds the CSS for custom themes
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminTheme extends AppController
{
    /**
     * Pre-action setup method that is called before the index method, or the set controller action
     */
    public function preAction()
    {
        parent::preAction();
    }

    /**
     * Render the custom CSS required for the current theme
     *
     * @return bool False, to prevent structure from rendering
     */
    public function index()
    {
        $this->uses(['Companies', 'Themes']);
        $this->components(['SettingsCollection']);

        $css = '';

        if (isset($this->Session)) {
            // Get theme setting
            $theme_setting = $this->SettingsCollection->fetchSetting(
                $this->Companies,
                $this->company_id,
                'theme_admin'
            );

            // Get theme and set CSS
            if (isset($theme_setting['value'])) {
                $theme = $this->Themes->get($theme_setting['value']);

                $dir = isset($this->get['dir']) ? $this->get['dir'] : null;

                if ($dir != '') {
                    $dir = $dir . DS;
                }

                // Set the path to the custom theme style sheet
                $theme_loc = VIEWDIR . 'admin' . DS . $this->layout . DS . 'css' . DS . $dir . 'theme.css';

                // Admin Login is in the default view
                if ($dir === 'admin_login' . DS) {
                    $theme_loc = VIEWDIR . 'default' . DS . 'css' . DS . $dir . 'theme.css';
                }

                if (file_exists($theme_loc)) {
                    // Read the theme file and replace tags with new theme properties
                    $css = file_get_contents($theme_loc);

                    // Update each non-transparent color to prepend it with '#'
                    foreach ($theme->colors as $key => &$color) {
                        if ($color !== 'transparent') {
                            $color = '#' . $color;
                        }
                    }

                    // Replace all matching tags in CSS
                    $css = str_replace(array_keys((array) $theme->colors), array_values((array) $theme->colors), $css);
                }
            }
        }

        // Send the custom theme CSS
        header('Content-type: text/css');
        echo $css;

        return false;
    }
}
