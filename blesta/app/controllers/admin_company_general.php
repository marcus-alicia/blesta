<?php

use Blesta\Core\Util\Captcha\Captcha;
use Blesta\Core\Util\Captcha\CaptchaFactory;
use Blesta\Core\Util\Input\Fields\Html as FieldsHtml;

/**
 * Admin Company General Settings
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminCompanyGeneral extends AppController
{
    /**
     * Pre-action setup method that is called before the index method, or the set controller action
     */
    public function preAction()
    {
        parent::preAction();

        // Require login
        $this->requireLogin();

        $this->uses(['Navigation']);

        Language::loadLang('admin_company_general');

        $this->set(
            'left_nav',
            $this->partial('settings_leftnav', ['nav' => $this->Navigation->getCompany($this->base_uri)])
        );
    }

    /**
     * General Company settings landing page
     */
    public function index()
    {
        $this->redirect($this->base_uri . 'settings/company/general/localization/');
    }

    /**
     * Localization Settings page
     */
    public function localization()
    {
        $this->uses(['Companies', 'Countries', 'Languages']);
        $this->components(['SettingsCollection']);

        $company_id = $this->company_id;

        if (!empty($this->post)) {
            // Set check boxes
            if (empty($this->post['client_set_lang'])) {
                $this->post['client_set_lang'] = 'false';
            }

            $fields = ['language', 'client_set_lang', 'calendar_begins',
                'timezone', 'date_format', 'datetime_format', 'country'];
            $this->Companies->setSettings($company_id, $this->post, $fields);

            $this->setMessage('message', Language::_('AdminCompanyGeneral.!success.localization_updated', true));
        }

        $timezones = $this->Date->getTimezones();
        $zones = [];

        $i = 0;
        foreach ($timezones as $loc => $timezone) {
            $zones[$i]['name'] = $loc;
            $zones[$i]['value'] = 'optgroup';

            $i++;

            if (is_array($timezone)) {
                $num_zones = count($timezone);
                for ($j = 0; $j < $num_zones; $i++, $j++) {
                    $zones[$i]['name'] = Language::_(
                        'AdminCompanyGeneral.localization.tz_format',
                        true,
                        $timezone[$j]['utc'],
                        $timezone[$j]['name']
                    );
                    $zones[$i]['value'] = $timezone[$j]['id'];
                }
            }
        }

        $this->set('timezones', $zones);
        $this->set(
            'countries',
            $this->Form->collapseObjectArray($this->Countries->getList(), ['name', 'alt_name'], 'alpha2', ' - ')
        );
        $this->set(
            'languages',
            $this->Form->collapseObjectArray($this->Languages->getAll($company_id), 'name', 'code')
        );
        $this->set('vars', $this->SettingsCollection->fetchSettings($this->Companies, $company_id));
    }

    /**
     * International Settings page
     */
    public function international()
    {
        $this->uses(['Languages']);

        $all_languages = $this->Languages->getAvailable();
        $installed_languages = $this->Languages->getAll($this->company_id);
        $uninstallable_languages = $this->Languages->getAllUninstallable($this->company_id);

        // Format the languages for the view
        $languages = [];
        $i = 0;
        foreach ($all_languages as $code => $name) {
            $languages[$i] = new stdClass();
            $languages[$i]->code = $code;
            $languages[$i]->name = $name;
            $languages[$i]->installed = false;
            $languages[$i]->uninstallable = in_array($code, $uninstallable_languages);
            $i++;
        }
        unset($i);

        // Set whether or not a language has been installed
        $num_installed = count($installed_languages);
        $num_languages = count($languages);
        for ($i = 0; $i < $num_installed; $i++) {
            for ($j = 0; $j < $num_languages; $j++) {
                if ($installed_languages[$i]->code == $languages[$j]->code) {
                    $languages[$j]->installed = true;
                }
            }
        }

        $this->set('languages', $languages);
        $this->setMessage('notice', Language::_('AdminCompanyGeneral.!notice.international_languages', true));
    }

    /**
     * Installs a language
     */
    public function installLanguage()
    {
        // Ensure the language is not installed for this company, but is available
        $this->uses(['Languages']);
        $available_languages = $this->Languages->getAvailable();
        if (!isset($this->get[0])
            || !array_key_exists($this->get[0], (array)$available_languages)
            || ($language = $this->Languages->get($this->company_id, $this->get[0]))
        ) {
            $this->redirect($this->base_uri . 'settings/company/general/international/');
        }

        // Install the language
        $this->Languages->add($this->company_id, $this->get[0]);

        // Display a success/error message
        if (($errors = $this->Languages->errors())) {
            $this->flashMessage('error', $errors);
        } else {
            $language = $this->Languages->get($this->company_id, $this->get[0]);
            $this->flashMessage(
                'message',
                Language::_('AdminCompanyGeneral.!success.language_installed', true, $language->name)
            );
        }

        $this->redirect($this->base_uri . 'settings/company/general/international/');
    }

    /**
     * Uninstalls a language
     */
    public function uninstallLanguage()
    {
        // Ensure the language is installed for this company
        $this->uses(['Languages']);
        if (!isset($this->get[0]) || !($language = $this->Languages->get($this->company_id, $this->get[0]))) {
            $this->redirect($this->base_uri . 'settings/company/general/international/');
        }

        // Uninstall the language
        $this->Languages->delete($this->company_id, $this->get[0]);

        if (($errors = $this->Languages->errors())) {
            $this->flashMessage('error', $errors);
        } else {
            $this->flashMessage(
                'message',
                Language::_('AdminCompanyGeneral.!success.language_uninstalled', true, $language->name)
            );
        }

        $this->redirect($this->base_uri . 'settings/company/general/international/');
    }

    /**
     * Encryption settings
     */
    public function encryption()
    {
        $this->uses(['Encryption']);
        $this->components(['SettingsCollection']);

        $vars = new stdClass();

        if (!empty($this->post)) {
            // Set the new passphrase
            $this->Encryption->setPassphrase($this->post, true);

            if (($errors = $this->Encryption->errors())) {
                // Error, reset vars
                $vars = (object) $this->post;
                $this->setMessage('error', $errors);
            } else {
                $this->setMessage('message', Language::_('AdminCompanyGeneral.!success.encryption_updated', true));
            }
        }


        // Get company settings
        $company_settings = $this->SettingsCollection->fetchSettings(null, $this->company_id);

        // Set warning for passphrases
        $company_has_passphrase = false;
        if (!empty($company_settings['private_key_passphrase'])) {
            $company_has_passphrase = true;
            if (empty($this->post)) {
                $this->setMessage('notice', Language::_('AdminCompanyGeneral.!notice.passphrase_set', true));
            }
        } elseif (empty($this->post)) {
            $this->setMessage('notice', Language::_('AdminCompanyGeneral.!notice.passphrase', true));
        }

        $this->set('vars', $vars);
        $this->set('company_has_passphrase', $company_has_passphrase);
    }

    /**
     * Contact Types
     */
    public function contactTypes()
    {
        $this->uses(['Contacts']);

        $this->set('contact_types', $this->Contacts->getTypes($this->company_id));
    }

    /**
     * Marketing settings
     */
    public function marketing()
    {
        $this->uses(['Companies']);

        $vars = new stdClass();

        if (!empty($this->post)) {
            // Set check boxes
            if (empty($this->post['show_receive_email_marketing'])) {
                $this->post['show_receive_email_marketing'] = 'false';
            }

            $fields = ['show_receive_email_marketing'];
            $this->Companies->setSettings($this->company_id, $this->post, $fields);

            $this->setMessage('message', Language::_('AdminCompanyGeneral.!success.marketing_updated', true));
        }

        $show_receive_email_marketing = $this->Companies->getSetting($this->company_id, 'show_receive_email_marketing');
        $vars->show_receive_email_marketing = $show_receive_email_marketing
            ? $show_receive_email_marketing->value
            : 'true';

        $this->set('vars', $vars);
    }

    /**
     * Smart Search settings
     */
    public function smartSearch()
    {
        $this->uses(['Companies']);
        $this->components(['SettingsCollection']);

        $fields = ['client_search', 'invoice_search', 'quotation_search', 'transaction_search', 'service_search', 'package_search'];
        if (!empty($this->post)) {
            // Set check boxes
            foreach ($fields as $key => $field) {
                if (empty($this->post[$field])){
                    $this->post[$field] = 'false';
                }
            }

            $this->Companies->setSettings($this->company_id, $this->post, $fields);

            $this->setMessage('message', Language::_('AdminCompanyGeneral.!success.smartsearch_updated', true));
        }

        $this->set('fields', $fields);
        $this->set('vars', $this->SettingsCollection->fetchSettings($this->Companies, $this->company_id));
    }

    /**
     * Human verification settings
     */
    public function humanVerification()
    {
        $this->uses(['Companies']);
        $this->components(['SettingsCollection']);

        // Get company settings
        $company_settings = $this->SettingsCollection->fetchSettings($this->Companies, $this->company_id);

        if (isset($company_settings['captcha_enabled_forms'])) {
            $company_settings['captcha_enabled_forms'] = unserialize($company_settings['captcha_enabled_forms']);
        }

        $vars = !empty($this->post) ? $this->post : $company_settings;

        // Get available captchas on the system
        $available_captchas = Captcha::getAvailable();
        $captchas = ['' => Language::_('AdminCompanyGeneral.humanverification.field_captcha_none', true)];
        $fields = ['captcha', 'captcha_enabled_forms'];
        $options = [];

        foreach ($available_captchas as $captcha) {
            $captchas[$captcha->id] = $captcha->name;

            // Initialize captcha
            $factory = new CaptchaFactory();
            $instance = $factory->{$captcha->class}();

            if (($captcha_fields = $instance->getOptionFields($vars))) {
                if (!empty($captcha_fields)) {
                    $options[$captcha->id] = new FieldsHtml($captcha_fields);
                    $options_fields = $captcha_fields->getFields();
                    foreach ($options_fields as $field) {
                        if ($field->type == 'label') {
                            foreach ($field->fields as $sub_field) {
                                $fields[] = $sub_field->params['name'];
                            }
                        } else {
                            $fields[] = $field->params['name'];
                        }
                    }
                }
            }
        }

        if (!empty($this->post)) {
            // Validate if the GD extension is loaded
            if (
                (extension_loaded('gd') && $this->post['captcha'] == 'internalcaptcha') ||
                ($this->post['captcha'] !== 'internalcaptcha')
            ) {
                if (isset($this->post['captcha_enabled_forms'])) {
                    $this->post['captcha_enabled_forms'] = serialize($this->post['captcha_enabled_forms']);
                } else {
                    $this->post['captcha_enabled_forms'] = serialize([]);
                }

                $this->Companies->setSettings($this->company_id, $this->post, $fields);

                $this->flashMessage(
                    'message',
                    Language::_('AdminCompanyGeneral.!success.humanverification_updated', true)
                );
                $this->redirect($this->base_uri . 'settings/company/general/humanverification/');
            } else {
                $this->setMessage('error', Language::_('AdminCompanyGeneral.!error.captcha_gd', true));
            }
        }

        $forms = [
            'admin_login', 'client_login', 'admin_login_reset', 'client_login_reset', 'client_login_forgot'
        ];

        $this->set('captchas', $captchas);
        $this->set('forms', $forms);
        $this->set('options', $options);
        $this->set('vars', $vars);
    }

    /**
     * Adds a contact type
     */
    public function addContactType()
    {
        $this->uses(['Contacts']);

        $vars = new stdClass();

        // Add the contact type
        if (!empty($this->post)) {
            // Set unset checkboxes
            if (empty($this->post['is_lang'])) {
                $this->post['is_lang'] = '0';
            }

            $this->post['company_id'] = $this->company_id;

            // Add contact type
            $contact_type_id = $this->Contacts->addType($this->post);

            if (($errors = $this->Contacts->errors())) {
                // Error
                $vars = (object) $this->post;
                $this->setMessage('error', $errors);
            } else {
                // Success
                $contact_type = $this->Contacts->getType($contact_type_id);
                $this->flashMessage(
                    'message',
                    Language::_('AdminCompanyGeneral.!success.contact_type_added', true, $contact_type->real_name)
                );
                $this->redirect($this->base_uri . 'settings/company/general/contacttypes/');
            }
        }

        $this->set('vars', $vars);
    }

    /**
     * Edits a contact type
     */
    public function editContactType()
    {
        $this->uses(['Contacts']);

        // Ensure a contact type has been given
        if (!isset($this->get[0]) || !($contact_type = $this->Contacts->getType((int) $this->get[0])) ||
            ($contact_type->company_id != $this->company_id)) {
            $this->redirect($this->base_uri . 'settings/company/general/contacttypes/');
        }

        // Edit the contact type
        if (!empty($this->post)) {
            // Set unset checkboxes
            if (empty($this->post['is_lang'])) {
                $this->post['is_lang'] = '0';
            }

            // Edit contact type
            $this->Contacts->editType($contact_type->id, $this->post);

            if (($errors = $this->Contacts->errors())) {
                // Error
                $vars = (object) $this->post;
                $this->setMessage('error', $errors);
            } else {
                // Success
                $contact_type = $this->Contacts->getType($contact_type->id);
                $this->flashMessage(
                    'message',
                    Language::_('AdminCompanyGeneral.!success.contact_type_updated', true, $contact_type->real_name)
                );
                $this->redirect($this->base_uri . 'settings/company/general/contacttypes/');
            }
        }

        // Set the default contact type
        if (empty($vars)) {
            $vars = $contact_type;
        }

        $this->set('vars', $vars);
    }

    /**
     * Deletes a contact type
     */
    public function deleteContactType()
    {
        $this->uses(['Contacts']);

        // Ensure a contact type has been given
        if (!isset($this->post['id']) || !($contact_type = $this->Contacts->getType((int) $this->post['id'])) ||
            ($contact_type->company_id != $this->company_id)) {
            $this->redirect($this->base_uri . 'settings/company/general/contacttypes/');
        }

        // Delete the contact type
        $this->Contacts->deleteType($contact_type->id);

        $this->flashMessage(
            'message',
            Language::_('AdminCompanyGeneral.!success.contact_type_deleted', true, $contact_type->real_name)
        );
        $this->redirect($this->base_uri . 'settings/company/general/contacttypes/');
    }
}
