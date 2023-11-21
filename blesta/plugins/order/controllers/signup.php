<?php

use Blesta\Core\Util\Captcha\CaptchaFactory;
use Blesta\Core\Util\Captcha\Captcha;

/**
 * Order System signup controller
 *
 * @package blesta
 * @subpackage blesta.plugins.order.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Signup extends OrderFormController
{
    /**
     * Signup
     */
    public function index()
    {
        $vars = new stdClass();

        $this->uses(['Users', 'Contacts', 'Countries', 'States', 'ClientGroups', 'EmailVerifications']);
        $this->components(['SettingsCollection']);
        $this->helpers(['Form']);
        $this->ArrayHelper = $this->DataStructure->create('Array');
        $requestor = $this->getFromContainer('requestor');

        $order_settings = $this->ArrayHelper->numericToKey(
            $this->OrderSettings->getSettings($this->company_id),
            'key',
            'value'
        );

        // Get company settings
        $company_settings = $this->SettingsCollection->fetchSettings($this->Companies, $this->company_id);

        // Check if captcha is required for signups
        $catpcha_forms = unserialize($company_settings['captcha_enabled_forms']);
        $require_captcha = (
                ($this->order_form->require_captcha ?? '0') == '1' || Captcha::enabled('client_login')
            )
            && isset($company_settings['captcha']);

        $captcha = null;
        if ($require_captcha) {
            $options = Captcha::getOptions();
            $captcha = $this->getCaptcha($company_settings['captcha'], $options);
        }

        // Fetch client group tax ID setting
        $show_client_tax_id = $this->SettingsCollection->fetchClientGroupSetting(
            $this->order_form->client_group_id,
            null,
            'show_client_tax_id'
        );
        $show_client_tax_id = (isset($show_client_tax_id['value']) ? $show_client_tax_id['value'] : '');

        // Fetch client group force email setting
        $force_email_usernames = $this->SettingsCollection->fetchClientGroupSetting(
            $this->order_form->client_group_id,
            null,
            'force_email_usernames'
        );
        $force_email_usernames = (isset($force_email_usernames['value']) ? $force_email_usernames['value'] :
            (isset($company_settings['force_email_usernames']) ? $company_settings['force_email_usernames'] : 'false')
        );

        // Set default currency, country, and language settings from this company
        $vars = new stdClass();
        $vars->country = $company_settings['country'];

        if (!empty($this->post)) {
            $errors = false;

            if ($captcha !== null) {
                $success = $captcha->verify($this->post);

                if (!$success) {
                    $errors = ['captcha' => ['invalid' => Language::_('Signup.!error.captcha.invalid', true)]];
                }
            }

            if (!$errors) {
                // Set mandatory defaults
                $this->post['client_group_id'] = $this->order_form->client_group_id;

                $client_info = $this->post;
                $client_info['verify'] = false;
                $client_info['settings'] = [
                    'username_type' => $this->post['username_type'],
                    'tax_id' => ($show_client_tax_id == 'true' ? $this->post['tax_id'] : ''),
                    'default_currency' => $this->SessionCart->getData('currency'),
                    'language' => $company_settings['language'],
                    'receive_email_marketing' => (isset($this->post['receive_email_marketing'])
                        ? $this->post['receive_email_marketing']
                        : 'false'
                    )
                ];

                // Force email usernames
                if ($force_email_usernames == 'true') {
                    $client_info['username_type'] = 'email';
                    $client_info['username'] = '';
                }

                // Set client setting overrides from the order settings iff available
                $client_settings = ['payments_allowed_ach', 'payments_allowed_cc'];
                foreach ($client_settings as $setting) {
                    if (array_key_exists($setting, (array)$order_settings)) {
                        $client_info['settings'][$setting] = $order_settings[$setting];
                    }
                }

                $client_info['numbers'] = $this->ArrayHelper->keyToNumeric($client_info['numbers'] ?? []);

                foreach ($this->post as $key => $value) {
                    if (substr($key, 0, strlen($this->custom_field_prefix)) == $this->custom_field_prefix) {
                        $client_info['custom'][str_replace($this->custom_field_prefix, '', $key)] = $value;
                    }
                }

                // Check client info before fraud check if set to do so
                if (isset($order_settings['antifraud_after_validate'])
                    && $order_settings['antifraud_after_validate'] == 'true'
                ) {
                    $this->Clients->validateCreation($client_info);
                    $errors = $this->Clients->errors();
                }

                // Fraud detection
                if (!$errors && !empty($order_settings['antifraud'])) {
                    $errors = $this->runAntifraudCheck($order_settings, (object)$client_info);
                }

                if (!$errors) {
                    // Create the client
                    $this->client = $this->Clients->create($client_info);

                    $errors = $this->Clients->errors();
                }

                if (isset($this->client->client_group_id) && is_numeric($this->client->client_group_id)) {
                    // Set email address for verification
                    $settings = $this->ClientGroups->getSettings($this->client->client_group_id);
                    $settings = $this->Form->collapseObjectArray($settings, 'value', 'key');
                }

                if (
                    isset($settings['email_verification'])
                    && $settings['email_verification'] == 'true'
                ) {
                    // Add email verification
                    $email_verification = $this->EmailVerifications->getByContactId($this->client->contact_id);
                    if (empty($email_verification)) {
                        $this->EmailVerifications->add([
                            'contact_id' => $this->client->contact_id,
                            'email' => $this->client->email,
                            'redirect_url' => $this->base_uri . 'order/cart/index/' . $this->order_form->label
                        ]);
                    }
                }
            }

            if ($errors) {
                $this->setMessage('error', $errors, false, null, false);
            } else {
                // Log the user into the newly created client account
                $login = [
                    'username' => $this->client->username,
                    'password' => $client_info['new_password']
                ];
                $user_id = $this->Users->login($this->Session, $login);

                if ($user_id) {
                    $this->Session->write('blesta_company_id', $this->company_id);
                    $this->Session->write('blesta_client_id', $this->client->id);
                }

                if (!$this->isAjax()) {
                    if ($settings['email_verification'] == 'true') {
                        $this->flashMessage(
                            'notice',
                            Language::_('Signup.!notice.email_verification', true)
                        );
                    }
                    $this->redirect($this->base_uri . 'order/checkout/index/' . $this->order_form->label);
                } elseif ($settings['email_verification'] == 'true') {
                    $this->setMessage(
                        'notice',
                        Language::_('Signup.!notice.email_verification', true),
                        false,
                        null,
                        false
                    );
                }
            }
            $vars = (object)$this->post;
        } elseif (($geo_location = $this->getGeoIp($requestor->ip_address)) && $geo_location['location']) {
            $vars->country = $geo_location['location']['country_code'];
            $vars->state = $geo_location['location']['region'];
        }


        // Set custom fields to display
        $custom_fields = $this->Clients->getCustomFields(
            $this->company_id,
            $this->order_form->client_group_id,
            ['show_client' => 1]
        );

        // Swap key/value pairs for "Select" option custom fields (to display)
        foreach ($custom_fields as &$field) {
            if ($field->type == 'select' && is_array($field->values)) {
                $field->values = array_flip($field->values);
            }
        }

        // Default the client's option to receive marketing emails base on the order plugin setting
        if (!isset($vars->receive_email_marketing) && isset($order_settings['marketing_default'])) {
            $vars->receive_email_marketing = $order_settings['marketing_default'];
        }

        $show_recieve_email_marketing = $this->SettingsCollection->fetchClientGroupSetting(
            $this->client ? $this->client->client_group_id : $this->order_form->client_group_id,
            $this->ClientGroups,
            'show_receive_email_marketing'
        );

        // Check if captcha is required for log in
        $signup_captcha = null;
        $login_captcha = null;
        if ($require_captcha && !is_null($captcha)) {
            $catpcha_html = $captcha->buildHtml();
            if ($this->order_form->require_captcha == '1') {
                $signup_captcha = $catpcha_html;
            }
            if ($catpcha_forms['client_login'] ?? '0' == '1') {
                $login_captcha = $catpcha_html;
            }
        }

        // Get required contact fields
        $required_contact_fields = $this->ClientGroups->getSetting(
            $this->client->client_group_id ?? $this->order_form->client_group_id,
            'required_contact_fields'
        );

        if ($required_contact_fields) {
            $required_contact_fields = unserialize(base64_decode($required_contact_fields->value));
        }

        // Get shown contact fields
        $shown_contact_fields = $this->ClientGroups->getSetting(
            $this->client->client_group_id ?? $this->order_form->client_group_id,
            'shown_contact_fields'
        );

        if ($shown_contact_fields) {
            $shown_contact_fields = unserialize(base64_decode($shown_contact_fields->value));
        }

        $this->set('custom_field_prefix', $this->custom_field_prefix);
        $this->set('custom_fields', $custom_fields);

        $this->set(
            'countries',
            $this->Form->collapseObjectArray($this->Countries->getList(), ['name', 'alt_name'], 'alpha2', ' - ')
        );
        $this->set('states', $this->Form->collapseObjectArray($this->States->getList($vars->country), 'name', 'code'));
        $this->set('currencies', $this->Currencies->getAll($this->company_id));

        $this->set('vars', $vars);

        $this->set('client', $this->client);
        if (!$this->isClientOwner($this->client, $this->Session)) {
            $this->setMessage('error', Language::_('Signup.!error.not_client_owner', true), false, null, false);
            $this->set('client', false);
        }
        $this->set('show_client_tax_id', ($show_client_tax_id == 'true'));
        $this->set('force_email_usernames', $force_email_usernames);
        $this->set(
            'show_receive_email_marketing',
            $show_recieve_email_marketing ? $show_recieve_email_marketing['value'] : 'true'
        );
        $this->set('captcha', ($signup_captcha ?? ''));
        $this->set('login_captcha', ($login_captcha ?? ''));
        $this->set('required_contact_fields', ($required_contact_fields ?? ''));
        $this->set('shown_contact_fields', ($shown_contact_fields ?? ''));

        return $this->renderView();
    }

    /**
     * Outputs clients info
     */
    public function clientinfo()
    {
        $this->set('client', $this->Clients->get($this->Session->read('blesta_client_id')));
        $this->outputAsJson($this->view->fetch());
        return false;
    }

    /**
     * AJAX Fetch all states belonging to a given country (json encoded ajax request)
     */
    public function getStates()
    {
        $this->uses(['States']);
        $states = [];
        if (isset($this->get[1])) {
            $states = (array)$this->Form->collapseObjectArray($this->States->getList($this->get[1]), 'name', 'code');
        }

        $this->outputAsJson($states);
        return false;
    }

    /**
     * Retrieve an instance of the captcha
     *
     * @param string $captcha The captcha to be used, can be "recaptcha" or "internalcaptcha"
     * @param array $options The options to be used with the captcha:
     *
     *  - site_key The reCaptcha site key
     *  - shared_key The reCaptcha shared key
     *  - lang The user's language
     *  - ip_address The user's IP address (optional)
     * @return Blesta\Core\Util\Captcha\Common\CaptchaInterface
     */
    private function getCaptcha($captcha, array $options)
    {
        $factory = new CaptchaFactory();
        $instance = $factory->{$captcha}($options);

        return $instance ?? null;
    }
}
