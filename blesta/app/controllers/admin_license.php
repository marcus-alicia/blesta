<?php

/**
 * Admin License
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminLicense extends AppController
{
    /**
     * Allows the license key to be updated
     */
    public function index()
    {
        $this->uses(['License']);

        $this->view->view = 'errors';
        $this->structure->view = 'errors';

        // Update the license key if possible
        if (!empty($this->post) && isset($this->post['key'])) {
            $updated = $this->License->updateLicenseKey($this->post['key']);

            // If license key was updated, send back to login page
            if ($updated) {
                $this->flashMessage('message', Language::_('AppController.!success.license_key_updated', true));
                $this->redirect($this->base_uri . 'login/');
            }

            $this->License->validate();
        } else {
            // Attempt to revalidate license, if valid redirect to login
            if ($this->License->validate(true)) {
                $this->flashMessage('message', Language::_('AppController.!success.license_updated', true));
                $this->redirect($this->base_uri . 'login/');
            }
        }

        $errors = $this->License->errors();

        if ($errors) {
            $this->set('errors', $errors);
        }

        $this->render('admin_license');
    }
}
