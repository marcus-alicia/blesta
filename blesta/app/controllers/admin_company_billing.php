<?php

/**
 * Admin Company Billing Settings
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminCompanyBilling extends AppController
{
    /**
     * Pre-action
     */
    public function preAction()
    {
        parent::preAction();

        // Require login
        $this->requireLogin();

        $this->uses(['Companies', 'Navigation']);
        $this->components(['SettingsCollection']);

        Language::loadLang('admin_company_billing');

        // Set the left nav for all settings pages to settings_leftnav
        $this->set(
            'left_nav',
            $this->partial('settings_leftnav', ['nav' => $this->Navigation->getCompany($this->base_uri)])
        );
    }

    /**
     * Billing settings page
     */
    public function index()
    {
        $this->redirect($this->base_uri . 'settings/company/billing/invoices/');
    }

    /**
     * Billing/Payment Invoice and Charge Settings page
     */
    public function invoices()
    {
        // Set a notice message if any client group has client group settings applied
        if ($this->clientGroupSettingsExist()) {
            $this->setMessage('notice', Language::_('AdminCompanyBilling.!notice.group_settings', true));
        }

        // Update Invoice and Charge settings
        if (!empty($this->post)) {
            // Set checkbox settings if not given
            $checkboxes = ['autodebit', 'client_set_invoice', 'inv_suspended_services', 'clients_cancel_services',
                'clients_renew_services', 'synchronize_addons', 'client_create_addons', 'auto_apply_credits',
                'auto_paid_pending_services', 'client_change_service_term', 'client_prorate_credits',
                'client_change_service_package', 'show_client_tax_id', 'process_paid_service_changes', 'inv_group_services',
                'inv_append_descriptions', 'inv_lines_verbose_option_dates', 'void_invoice_canceled_service',
                'void_inv_canceled_service_days', 'quotation_valid_days', 'quotation_dead_days', 'quotation_deposit_percentage'
            ];
            foreach ($checkboxes as $field) {
                if (empty($this->post[$field])) {
                    $this->post[$field] = 'false';
                }
            }

            $fields = array_merge(['inv_days_before_renewal', 'autodebit_days_before_due',
                'suspend_services_days_after_due', 'autodebit_attempts', 'service_renewal_attempts',
                'cancel_service_changes_days', 'apply_inv_late_fees'], $checkboxes);
            $this->Companies->setSettings($this->company_id, $this->post, $fields);

            $this->setMessage('message', Language::_('AdminCompanyBilling.!success.invoices_updated', true));
        }

        // Set invoice days and autodebit days drop down options
        $invoice_days = [Language::_('AdminCompanyBilling.invoices.text_sameday', true)];
        $quotation_days = [Language::_('AdminCompanyBilling.invoices.text_sameday', true)];
        $autodebit_days = [Language::_('AdminCompanyBilling.invoices.text_sameday', true)];
        $suspend_days = ['never' => Language::_('AdminCompanyBilling.invoices.text_never', true)];
        $autodebit_attempts = [];
        $service_renewal_attempts = [];
        $void_inv_canceled_service_days = ['any' => Language::_('AdminCompanyBilling.invoices.text_any', true)];

        for ($i = 1; $i <= Configure::get('Blesta.invoice_renewal_max_days'); $i++) {
            $invoice_days[$i] = Language::_(
                'AdminCompanyBilling.invoices.text_day' . (($i == 1) ? '' : 's'),
                true,
                $i
            );
        }
        for ($i = 1; $i <= Configure::get('Blesta.quotation_valid_max_days'); $i++) {
            $quotation_days[$i] = Language::_(
                'AdminCompanyBilling.invoices.text_day' . (($i == 1) ? '' : 's'),
                true,
                $i
            );
        }
        for ($i = 1; $i <= Configure::get('Blesta.autodebit_before_due_max_days'); $i++) {
            $autodebit_days[$i] = Language::_(
                'AdminCompanyBilling.invoices.text_day' . (($i == 1) ? '' : 's'),
                true,
                $i
            );
        }
        for ($i = 1; $i <= Configure::get('Blesta.suspend_services_after_due_max_days'); $i++) {
            $suspend_days[$i] = Language::_(
                'AdminCompanyBilling.invoices.text_day' . (($i == 1) ? '' : 's'),
                true,
                $i
            );
        }
        for ($i = 1; $i <= 30; $i++) {
            $autodebit_attempts[$i] = $i;
        }
        for ($i = 1; $i <= 30; $i++) {
            $service_renewal_attempts[$i] = $i;
        }
        for ($i = 0; $i <= 60; $i++) {
            $void_inv_canceled_service_days[$i] = Language::_(
                'AdminCompanyBilling.invoices.text_day' . (($i == 1) ? '' : 's'),
                true,
                $i
            );
        }

        // Set variables for the partial billing form template
        $form_fields = [
            'vars' => $this->SettingsCollection->fetchSettings($this->Companies, $this->company_id),
            'invoice_days' => $invoice_days,
            'quotation_days' => $quotation_days,
            'autodebit_days' => $autodebit_days,
            'suspend_days' => $suspend_days,
            'autodebit_attempts' => $autodebit_attempts,
            'service_renewal_attempts' => $service_renewal_attempts,
            'service_change_days' => $autodebit_attempts,
            'void_inv_canceled_service_days' => $void_inv_canceled_service_days
        ];

        // Load the partial form template for this page
        $invoice_form = $this->partial('admin_company_billing_invoices_form', $form_fields);

        $this->set('invoice_form', $invoice_form);
    }

    /**
     * Billing/Payment Invoice Customization Settings page
     */
    public function customization()
    {
        $this->uses(['InvoiceTemplateManager', 'Languages', 'Invoices']);
        $this->components(['Upload', 'Input']);

        $vars = [];

        if (!empty($this->post)) {
            // Set validation rules
            $rules = [
                'inv_start' => [
                    'valid' => [
                        'rule' => 'is_numeric',
                        'message' => Language::_('AdminCompanyBilling.!error.inv_start.valid', true)
                    ]
                ],
                'inv_increment' => [
                    'valid' => [
                        'rule' => 'is_numeric',
                        'message' => Language::_('AdminCompanyBilling.!error.inv_increment.valid', true)
                    ]
                ],
                'quotation_start' => [
                    'valid' => [
                        'rule' => 'is_numeric',
                        'message' => Language::_('AdminCompanyBilling.!error.quotation_start.valid', true)
                    ]
                ],
                'quotation_increment' => [
                    'valid' => [
                        'rule' => 'is_numeric',
                        'message' => Language::_('AdminCompanyBilling.!error.quotation_increment.valid', true)
                    ]
                ]
            ];
            $this->Input->setRules($rules);
            
            if ($this->Input->validates($this->post)) {
                // Set checkbox settings if not given
                $checkboxes = [
                    'inv_cache_compress',
                    'inv_display_logo',
                    'inv_display_companyinfo',
                    'inv_display_paid_watermark',
                    'inv_display_payments',
                    'inv_display_due_date_draft',
                    'inv_display_due_date_inv',
                    'inv_display_due_date_proforma',
                ];
                foreach ($checkboxes as $checkbox) {
                    if (!isset($this->post[$checkbox])) {
                        $this->post[$checkbox] = 'false';
                    }
                }

                // Validate if the Zlib extension is loaded
                if (
                    (extension_loaded('zlib') && $this->post['inv_cache_compress'] == 'true') ||
                    ($this->post['inv_cache_compress'] !== 'true')
                ) {
                    $temp = $this->post['inv_mimetype'];
                    $this->post['inv_mimetype'] = isset($this->post['inv_mimetype'][$this->post['inv_template']])
                        ? $this->post['inv_mimetype'][$this->post['inv_template']]
                        : null;

                    $this->Companies->validateCustomization($this->post);
                    if (!($errors = $this->Companies->errors())) {
                        // Remove inv_logo if set to do so
                        if (isset($this->post['remove_inv_logo']) && $this->post['remove_inv_logo'] == 'true') {
                            $inv_logo = $this->SettingsCollection->fetchSetting(
                                $this->Companies,
                                $this->company_id,
                                'inv_logo'
                            );
                            if (isset($inv_logo['value']) && file_exists($inv_logo['value'])) {
                                unlink($inv_logo['value']);
                                $this->post['inv_logo'] = '';
                            }
                        }
                        // Remove non-setting post fields
                        unset($this->post['remove_inv_logo']);

                        // Remove inv_background if set to do so
                        if (isset($this->post['remove_inv_background']) && $this->post['remove_inv_background'] == 'true') {
                            $inv_background = $this->SettingsCollection->fetchSetting(
                                $this->Companies,
                                $this->company_id,
                                'inv_background'
                            );
                            if (isset($inv_background['value']) && file_exists($inv_background['value'])) {
                                unlink($inv_background['value']);
                                $this->post['inv_background'] = '';
                            }
                        }
                        // Remove non-setting post fields
                        unset($this->post['remove_inv_background']);

                        // Handle file uploads
                        if (isset($this->files) && !empty($this->files)) {
                            $temp = $this->SettingsCollection->fetchSetting($this->Companies, $this->company_id, 'uploads_dir');
                            $upload_path = $temp['value'] . $this->company_id . DS . 'invoices' . DS;

                            $this->Upload->setFiles($this->files);
                            // Create the upload path if it doesn't already exists
                            $this->Upload->createUploadPath($upload_path);
                            $this->Upload->setUploadPath($upload_path);

                            if (!($errors = $this->Upload->errors())) {
                                $expected_files = ['inv_logo', 'inv_background'];
                                // Will overwrite existing file, which is exactly what we want
                                $this->Upload->writeFiles($expected_files, true, $expected_files);
                                $data = $this->Upload->getUploadData();

                                foreach ($expected_files as $file) {
                                    if (isset($data[$file])) {
                                        $this->post[$file] = $data[$file]['full_path'];
                                    }
                                }

                                $errors = $this->Upload->errors();
                            }
                        }

                        if ($errors) {
                            $this->setMessage('error', $errors);
                            $vars = $this->post;
                        } else {
                            $fields = ['inv_format', 'inv_draft_format', 'inv_start',
                                'inv_increment', 'inv_pad_size', 'inv_pad_str',
                                'inv_paper_size', 'inv_template', 'inv_mimetype',
                                'inv_display_logo', 'inv_display_companyinfo', 'inv_display_paid_watermark',
                                'inv_display_payments', 'inv_logo', 'inv_background',
                                'inv_type', 'inv_proforma_format', 'inv_proforma_start',
                                'inv_cache', 'inv_cache_compress', 'inv_display_due_date_draft',
                                'inv_display_due_date_inv', 'inv_display_due_date_proforma',
                                'quotation_format', 'quotation_increment', 'quotation_start'
                            ];
                            foreach ($this->post as $key => $value) {
                                if (str_contains($key, 'inv_font_') || str_contains($key, 'inv_terms_')) {
                                    $fields[] = $key;
                                }
                            }
                            unset($key);

                            $this->Companies->setSettings($this->company_id, $this->post, $fields);
                            $this->setMessage(
                                'message',
                                Language::_('AdminCompanyBilling.!success.customization_updated', true)
                            );
                        }
                    }
                } else {
                    $this->setMessage(
                        'error',
                        Language::_('AdminCompanyBilling.!error.extension_zlib', true)
                    );
                    $vars = $this->post;
                }
            } elseif ($errors = $this->Input->errors()) {
                $this->setMessage('error', $errors);
            }
        }

        // Set initial settings
        if (empty($vars)) {
            $vars = $this->SettingsCollection->fetchSettings($this->Companies, $this->company_id);
        }

        $this->set('vars', $vars);
        $this->set('inv_types', $this->Invoices->getTypes());
        $this->set('inv_cache_methods', $this->Invoices->getCacheMethods());
        $this->set('templates', $this->InvoiceTemplateManager->getAll());
        $this->set('paper_sizes', $this->InvoiceTemplateManager->getPaperSizes());
        $this->set('fonts', $this->InvoiceTemplateManager->getPdfFonts());
        $this->set('languages', $this->Languages->getAll($this->company_id));
    }

    /**
     * Billing/Payment Notices Settings page
     */
    public function notices()
    {
        // Set a notice message if any client group has client group settings applied
        if ($this->clientGroupSettingsExist()) {
            $this->setMessage('notice', Language::_('AdminCompanyBilling.!notice.group_settings', true));
        }

        $this->uses(['EmailGroups']);

        // Update Notice settings
        if (!empty($this->post)) {
            if (!empty($this->post['notice1']) && is_numeric($this->post['notice1'])) {
                if (!empty($this->post['notice1_type'])) {
                    $this->post['notice1'] *= $this->post['notice1_type'];
                }
            }

            if (!empty($this->post['notice2']) && is_numeric($this->post['notice2'])) {
                if (!empty($this->post['notice2_type'])) {
                    $this->post['notice2'] *= $this->post['notice2_type'];
                }
            }

            if (!empty($this->post['notice3']) && is_numeric($this->post['notice3'])) {
                if (!empty($this->post['notice3_type'])) {
                    $this->post['notice3'] *= $this->post['notice3_type'];
                }
            }

            // Set missing checkboxes
            if (empty($this->post['send_payment_notices'])) {
                $this->post['send_payment_notices'] = 'false';
            }

            // Set missing checkboxes
            if (empty($this->post['send_cancellation_notice'])) {
                $this->post['send_cancellation_notice'] = 'false';
            }

            $fields = [
                'notice1', 'notice2', 'notice3',
                'notice_pending_autodebit', 'send_payment_notices', 'send_cancellation_notice'
            ];
            $this->Companies->setSettings($this->company_id, $this->post, $fields);

            $this->setMessage('message', Language::_('AdminCompanyBilling.!success.notices_updated', true));
        }

        // Set the day range for notices in days
        $notice_range = [];
        $notice_range[] = Language::_('AdminCompanyBilling.notices.text_duedate', true);
        for ($i = 1; $i <= Configure::get('Blesta.payment_notices_max_days'); $i++) {
            $notice_range[$i] = Language::_('AdminCompanyBilling.notices.text_day' . (($i == 1) ? '' : 's'), true, $i);
        }

        $notice_range = array_merge(
            ['disabled' => Language::_('AdminCompanyBilling.notices.text_disabled', true)],
            $notice_range
        );

        // Get the email group IDs of each notice in order to link to it
        $email_group_actions = ['invoice_notice_first', 'invoice_notice_second',
            'invoice_notice_third', 'auto_debit_pending'];
        $email_groups = [];
        foreach ($email_group_actions as $action) {
            $email_groups[$action] = ($email_group = $this->EmailGroups->getByAction($action))
                ? $email_group->id
                : null;
        }

        // Set variables for the partial billing form template
        $form_fields = [
            'vars' => $this->SettingsCollection->fetchSettings($this->Companies, $this->company_id),
            'notice_range' => $notice_range,
            'email_templates' => $email_groups
        ];

        // Load the partial form template for this page
        $notice_form = $this->partial('admin_company_billing_notices_form', $form_fields);

        $this->set('notice_form', $notice_form);
    }

    /**
     * Accepted Payment Types for gateways
     */
    public function acceptedTypes()
    {
        $this->uses(['ClientGroups', 'Clients']);

        // Update accepted payment type settings
        if (!empty($this->post)) {
            // Set empty checkboxes
            if (empty($this->post['payments_allowed_cc'])) {
                $this->post['payments_allowed_cc'] = 'false';
            }
            if (empty($this->post['payments_allowed_ach'])) {
                $this->post['payments_allowed_ach'] = 'false';
            }
            $update_clients = !empty($this->post['update_clients']);

            // Do not save this placeholder value as a setting
            unset($this->post['save']);
            unset($this->post['update']);
            unset($this->post['update_clients']);

            // Update settings
            $fields = ['payments_allowed_cc', 'payments_allowed_ach'];
            $this->Companies->setSettings($this->company_id, $this->post, $fields);

            if ($update_clients) {
                $client_groups = $this->ClientGroups->getAll($this->company_id);
                foreach ($client_groups as $client_group) {
                    $clients = $this->Clients->getAll(null, $client_group->id);
                    foreach ($clients as $client) {
                        if ($this->post['payments_allowed_cc'] == 'false') {
                            $this->Clients->unsetSetting($client->id, 'payments_allowed_cc');
                        }

                        if ($this->post['payments_allowed_ach'] == 'false') {
                            $this->Clients->unsetSetting($client->id, 'payments_allowed_ach');
                        }
                    }
                }
            }

            $this->setMessage('message', Language::_('AdminCompanyBilling.!success.acceptedtypes_updated', true));
        }

        $this->set('vars', $this->SettingsCollection->fetchSettings($this->Companies, $this->company_id));
        $this->set(
            'partial_payment_types',
            $this->partial(
                'partial_payment_types',
                ['vars' => $this->SettingsCollection->fetchSettings($this->Companies, $this->company_id)]
            )
        );
    }

    /**
     * Accepted invoice delivery methods
     */
    public function deliveryMethods()
    {
        // Set a notice message if any client group has client group settings applied
        if ($this->clientGroupSettingsExist()) {
            $this->setMessage('notice', Language::_('AdminCompanyBilling.!notice.group_settings', true));
        }

        $this->uses(['Countries', 'Invoices', 'States']);

        // Update accepted delivery methods
        if (!empty($this->post)) {
            // Set empty checkboxes
            if (empty($this->post['postalmethods_replyenvelope'])) {
                $this->post['postalmethods_replyenvelope'] = 'false';
            }
            if (empty($this->post['postalmethods_doublesided'])) {
                $this->post['postalmethods_doublesided'] = 'false';
            }
            if (empty($this->post['postalmethods_colored'])) {
                $this->post['postalmethods_colored'] = 'false';
            }

            // Always ensure email and paper are available (can not be disabled)
            $this->post['delivery_methods'][] = 'email';
            $this->post['delivery_methods'] = base64_encode(serialize($this->post['delivery_methods']));

            // Update settings
            $fields = ['delivery_methods', 'interfax_username', 'interfax_password',
                'postalmethods_apikey', 'postalmethods_replyenvelope',
                'postalmethods_doublesided', 'postalmethods_colored',
                'postalmethods_return_address1', 'postalmethods_return_address2',
                'postalmethods_return_city', 'postalmethods_return_state',
                'postalmethods_return_zip', 'postalmethods_return_country'
            ];
            $this->Companies->setSettings($this->company_id, $this->post, $fields);

            $this->setMessage('message', Language::_('AdminCompanyBilling.!success.deliverymethods_updated', true));
        }

        // Set all delivery methods available
        $vars = array_merge(
            (array) $this->SettingsCollection->fetchSettings($this->Companies, $this->company_id),
            (array) $this->Invoices->getDeliveryMethods(null)
        );

        if (!isset($vars['postalmethods_return_country'])) {
            $vars['postalmethods_return_country'] = $vars['country'];
        }
        $this->set('delivery_methods', $this->Invoices->getDeliveryMethods(null, null, false));
        $this->set(
            'countries',
            $this->Form->collapseObjectArray($this->Countries->getList(), ['name', 'alt_name'], 'alpha2', ' - ')
        );
        $this->set(
            'states',
            $this->Form->collapseObjectArray(
                $this->States->getList($vars['postalmethods_return_country']),
                'name',
                'code'
            )
        );
        $this->set('vars', $vars);
    }

    /**
     * Late fees
     */
    public function lateFees()
    {
        $this->uses(['Currencies']);
        $this->components(['Input']);

        // Set a notice message if any client group has client group settings applied
        if ($this->clientGroupSettingsExist()) {
            $this->setMessage('notice', Language::_('AdminCompanyBilling.!notice.group_settings', true));
        }

        // Get all company currencies
        $currencies = $this->Currencies->getAll($this->company_id);

        // Set validation rules
        $rules = [];
        foreach ($currencies as $currency) {
            $rules['late_fees[' . $currency->code . '][amount]'] = [
                'format' => [
                    'rule' => function($value) { return is_numeric($value) || empty($value); },
                    'if_set' => true,
                    'message' => Language::_('AdminCompanyBilling.!error.amount.format', true)
                ]
            ];

            $rules['late_fees[' . $currency->code . '][minimum]'] = [
                'format' => [
                    'rule' => function($value) { return is_numeric($value) || empty($value); },
                    'if_set' => true,
                    'message' => Language::_('AdminCompanyBilling.!error.minimum.format', true)
                ]
            ];
        }
        $this->Input->setRules($rules);

        // Update late fees
        if (!empty($this->post)) {
            // Set empty checkboxes
            if (empty($this->post['late_fee_total_amount'])) {
                $this->post['late_fee_total_amount'] = 'false';
            }

            foreach ($currencies as $currency) {
                if (empty($this->post['late_fees'][$currency->code]['enabled'])) {
                    $this->post['late_fees'][$currency->code]['enabled'] = 'false';
                }
            }

            if ($this->Input->validates($this->post)) {
                $this->post['late_fees'] = base64_encode(serialize($this->post['late_fees']));

                // Update settings
                $fields = ['late_fee_total_amount', 'late_fees'];
                $this->Companies->setSettings($this->company_id, $this->post, $fields);

                $this->setMessage(
                    'message',
                    Language::_('AdminCompanyBilling.!success.latefees_updated', true)
                );
            } elseif ($errors = $this->Input->errors()) {
                $this->setMessage('error', $errors);
            }
        }

        $vars = (array) $this->SettingsCollection->fetchSettings($this->Companies, $this->company_id);
        $vars['late_fees'] = isset($vars['late_fees']) ? unserialize(base64_decode($vars['late_fees'])) : [];

        // Set variables for the partial billing late fees form template
        $form_fields = [
            'currencies' => $currencies,
            'vars' => $vars
        ];

        // Load the partial form template for this page
        $late_fees_form = $this->partial('admin_company_billing_latefees_form', $form_fields);

        $this->set('late_fees_form', $late_fees_form);
    }

    /**
     * Billing/Payment Coupons page
     */
    public function coupons()
    {
        $this->uses(['Coupons']);

        // Set current page of results
        $page = (isset($this->get[0]) ? (int) $this->get[0] : 1);

        // Get the default currency
        $default_currency = $this->SettingsCollection->fetchSetting(
            $this->Companies,
            $this->company_id,
            'default_currency'
        );
        $default_currency = isset($default_currency['value']) ? $default_currency['value'] : '';

        // Get all coupons
        $coupons = $this->Coupons->getList($this->company_id, $page);
        $total_results = $this->Coupons->getListCount($this->company_id);

        $this->set('coupons', $coupons);
        $this->set('default_currency', $default_currency);

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination'),
            [
                'total_results' => $total_results,
                'uri' => $this->base_uri . 'settings/company/billing/coupons/[p]/',
            ]
        );
        $this->setPagination($this->get, $settings);

        // Render the request if ajax
        return $this->renderAjaxWidgetIfAsync(isset($this->get[0]));
    }

    /**
     * Add a coupon
     */
    public function addCoupon()
    {
        $this->uses(['Coupons', 'CouponTerms', 'Currencies', 'Packages']);

        // Set the default currency for discount options
        $company_settings = $this->SettingsCollection->fetchSettings($this->Companies, $this->company_id);

        $default_currency = $company_settings['default_currency'];
        $vars = new stdClass();
        $vars->amounts = [
            'currency' => [
                $default_currency
            ]
        ];

        // Create coupon
        if (!empty($this->post)) {
            // Set empty fields to default values
            if (empty($this->post['status'])) {
                $this->post['status'] = 'inactive';
            }

            $checkbox_fields = ['max_qty', 'apply_package_options', 'internal_use_only'];
            foreach ($checkbox_fields as $checkbox_field) {
                if (empty($this->post[$checkbox_field])) {
                    $this->post[$checkbox_field] = '0';
                }
            }

            // Format coupon amounts for insertion
            $vars = $this->post;
            $amounts = [];
            if (!empty($this->post['amounts'])
                && !empty($this->post['amounts']['currency'])
                && is_array($this->post['amounts']['currency'])
            ) {
                // Set all row amounts
                for ($i = 0; $i < count($this->post['amounts']['currency']); $i++) {
                    $amounts[$i]['currency'] = $this->post['amounts']['currency'][$i];
                    $amounts[$i]['type'] = $this->post['amounts']['type'][$i];
                    $amounts[$i]['amount'] = $this->post['amounts']['amount'][$i];
                }
                $vars['amounts'] = $amounts;
            }

            // Update coupon dates to encompase the entire day
            if (!empty($vars['start_date'])) {
                $vars['start_date'] .= ' 00:00:00';
            }
            if (!empty($vars['end_date'])) {
                $vars['end_date'] .= ' 23:59:59';
            }

            // Set company ID
            $vars['company_id'] = $this->company_id;

            // Add a coupon
            $this->Coupons->begin();
            $coupon_id = $this->Coupons->add($vars);
            $errors = $this->Coupons->errors();

            // Add coupon terms
            if (!$errors) {
                foreach ((isset($this->post['terms']) ? $this->post['terms'] : []) as $term) {
                    if (!isset($term['period'])) {
                        continue;
                    }

                    $period_terms = explode(',', isset($term['term']) ? $term['term'] : '');
                    foreach ($period_terms as $period_term) {
                        $vars = [
                            'coupon_id' => $coupon_id,
                            'term' => $term['period'] == 'onetime' ? 0 : $period_term,
                            'period' => $term['period']
                        ];
                        $this->CouponTerms->add($vars);

                        if (($errors = $this->CouponTerms->errors())) {
                            break 2;
                        }
                    }
                }
            }

            if ($errors) {
                $this->Coupons->rollback();

                // Error, reset vars
                $vars = (object) $this->post;
                $this->setMessage('error', $errors);
            } else {
                $this->Coupons->commit();

                // Success
                $this->flashMessage('message', Language::_('AdminCompanyBilling.!success.coupon_created', true));
                $this->redirect($this->base_uri . 'settings/company/billing/coupons/');
            }
        }

        // Set package groups
        $package_groups = $this->Form->collapseObjectArray(
            $this->Packages->getAllGroups($this->company_id, null, null, ['hidden' => 1]),
            'name',
            'id'
        );
        $all = ['' => Language::_('AdminCompanyBilling.addcoupon.text_all', true)];
        $package_groups = $all + $package_groups;
        $package_attributes = [];
        $packages = $this->Packages->getAll($this->company_id, [], null, null, ['hidden' => 1]);

        // Build the package option attributes
        foreach ($packages as $package) {
            $groups = $this->Packages->getAllGroups($this->company_id, $package->id, null, ['hidden' => 1]);

            $group_ids = [];
            foreach ($groups as $group) {
                $group_ids[] = 'group_' . $group->id;
            }

            if (!empty($group_ids)) {
                $package_attributes[$package->id] = ['class' => implode(' ', $group_ids)];
            }
        }

        // Set the selected assigned packages
        if (!empty($vars->packages)) {
            $temp_packages = array_flip($vars->packages);
            $assigned_packages = [];

            // Find any assigned packages from the list of packages, and set them
            for ($i = 0, $num_packages = count($packages); $i < $num_packages; $i++) {
                if (isset($temp_packages[$packages[$i]->id])) {
                    // Set an assigned package
                    $assigned_packages[$packages[$i]->id] = $packages[$i]->name;
                    // Remove it from available packages
                    unset($packages[$i]);
                }
            }
            $vars->packages = $assigned_packages;
        }

        $this->set('types', $this->Coupons->getAmountTypes());
        $this->set(
            'currencies',
            $this->Form->collapseObjectArray($this->Currencies->getAll($this->company_id), 'code', 'code')
        );
        $this->set('package_groups', $package_groups);
        $this->set('packages', $this->Form->collapseObjectArray($packages, 'name', 'id'));
        $this->set('package_attributes', $package_attributes);

        $this->set('vars', $vars);
        $this->set('periods', $this->CouponTerms->getPeriods());

        $this->Javascript->setFile('date.min.js');
        $this->Javascript->setFile('jquery.datePicker.min.js');
        $this->Javascript->setInline(
            'Date.firstDayOfWeek=' . ($company_settings['calendar_begins'] == 'sunday' ? 0 : 1) . ';'
        );
    }

    /**
     * Edit a coupon
     */
    public function editCoupon()
    {
        $this->uses(['Coupons', 'CouponTerms', 'Currencies', 'Packages']);
        $this->helpers(['DataStructure']);
        $this->components(['SettingsCollection']);

        // Create array helper
        $this->ArrayHelper = $this->DataStructure->create('Array');

        if (!isset($this->get[0])
            || !($coupon = $this->Coupons->get((int) $this->get[0]))
            || $coupon->company_id != $this->company_id
        ) {
            $this->redirect($this->base_uri . 'settings/company/billing/coupons/');
        }

        // Get the company settings
        $company_settings = $this->SettingsCollection->fetchSettings($this->Companies, $this->company_id);

        $vars = [];

        // Edit coupon
        if (!empty($this->post)) {
            // Set empty fields to default values
            if (empty($this->post['status'])) {
                $this->post['status'] = 'inactive';
            }

            $checkbox_fields = ['max_qty', 'apply_package_options', 'internal_use_only'];
            foreach ($checkbox_fields as $checkbox_field) {
                if (empty($this->post[$checkbox_field])) {
                    $this->post[$checkbox_field] = '0';
                }
            }

            // Format coupon amounts for insertion
            $vars = $this->post;
            $amounts = [];
            if (!empty($this->post['amounts'])
                && !empty($this->post['amounts']['currency'])
                && is_array($this->post['amounts']['currency'])
            ) {
                // Set all row amounts
                for ($i = 0; $i < count($this->post['amounts']['currency']); $i++) {
                    $amounts[$i]['currency'] = $this->post['amounts']['currency'][$i];
                    $amounts[$i]['type'] = $this->post['amounts']['type'][$i];
                    $amounts[$i]['amount'] = $this->post['amounts']['amount'][$i];
                }
                $vars['amounts'] = $amounts;
            }

            // Update coupon dates to encompass the entire day
            if (!empty($vars['start_date'])) {
                $vars['start_date'] .= ' 00:00:00';
            }
            if (!empty($vars['end_date'])) {
                $vars['end_date'] .= ' 23:59:59';
            }

            // Set company ID
            $vars['company_id'] = $this->company_id;

            // Edit a coupon
            $this->Coupons->begin();
            $this->Coupons->edit($coupon->id, $vars);
            $errors = $this->Coupons->errors();

            // Add coupon terms
            if (!$errors) {
                // Delete and re-add the terms
                $this->CouponTerms->delete($coupon->id);
                foreach ((isset($this->post['terms']) ? $this->post['terms'] : []) as $term) {
                    if (!isset($term['period'])) {
                        continue;
                    }

                    // Get the individual terms from the CSV formatted string
                    $period_terms = explode(',', isset($term['term']) ? $term['term'] : '');
                    foreach ($period_terms as $period_term) {
                        $vars = [
                            'coupon_id' => $coupon->id,
                            'term' => $term['period'] == 'onetime' ? 0 : $period_term,
                            'period' => $term['period']
                        ];
                        $this->CouponTerms->add($vars);

                        if (($errors = $this->CouponTerms->errors())) {
                            break 2;
                        }
                    }
                }
            }

            if ($errors) {
                $this->Coupons->rollback();

                // Error, reset vars
                $vars = (object) $this->post;
                $this->setMessage('error', $errors);
            } else {
                $this->Coupons->commit();

                // Success
                $this->flashMessage('message', Language::_('AdminCompanyBilling.!success.coupon_updated', true));
                $this->redirect($this->base_uri . 'settings/company/billing/coupons/');
            }
        }

        // Set current coupon and format amounts
        if (empty($vars)) {
            $vars = $coupon;
            $vars->amounts = $this->ArrayHelper->numericToKey($vars->amounts);

            // Update packages to only the package id
            foreach ($vars->packages as &$package) {
                $package = $package->package_id;
            }
            unset($package);

            $vars->terms = $this->formatCouponTerms($vars->terms);
        }

        // Set package groups
        $package_groups = $this->Form->collapseObjectArray(
            $this->Packages->getAllGroups($this->company_id, null, null, ['hidden' => 1]),
            'name',
            'id'
        );
        $all = ['' => Language::_('AdminCompanyBilling.editcoupon.text_all', true)];
        $package_groups = $all + $package_groups;
        $package_attributes = [];
        $packages = $this->Packages->getAll($this->company_id, [], null, null, ['hidden' => 1]);

        // Build the package option attributes
        foreach ($packages as $package) {
            $groups = $this->Packages->getAllGroups($this->company_id, $package->id, null, ['hidden' => 1]);

            $group_ids = [];
            foreach ($groups as $group) {
                $group_ids[] = 'group_' . $group->id;
            }

            if (!empty($group_ids)) {
                $package_attributes[$package->id] = ['class' => implode(' ', $group_ids)];
            }
        }

        // Set the selected assigned packages
        if (!empty($vars->packages)) {
            $temp_packages = array_flip($vars->packages);
            $assigned_packages = [];

            // Find any assigned packages from the list of packages, and set them
            for ($i = 0, $num_packages = count($packages); $i < $num_packages; $i++) {
                if (isset($temp_packages[$packages[$i]->id])) {
                    // Set an assigned package
                    $assigned_packages[$packages[$i]->id] = $packages[$i]->name;
                    // Remove it from available packages
                    unset($packages[$i]);
                }
            }
            $vars->packages = $assigned_packages;
        }

        // Do not format dates (again) after an error
        if (empty($errors)) {
            // Format dates
            $vars->start_date = $this->Date->cast($vars->start_date, 'Y-m-d');
            $vars->end_date = $this->Date->cast($vars->end_date, 'Y-m-d');
        }

        $this->set('types', $this->Coupons->getAmountTypes());
        $this->set(
            'currencies',
            $this->Form->collapseObjectArray($this->Currencies->getAll($this->company_id), 'code', 'code')
        );
        $this->set('package_groups', $package_groups);
        $this->set('packages', $this->Form->collapseObjectArray($packages, 'name', 'id'));
        $this->set('package_attributes', $package_attributes);

        $this->set('vars', $vars);
        $this->set('coupon', $coupon);
        $this->set('periods', $this->CouponTerms->getPeriods());

        $this->Javascript->setFile('date.min.js');
        $this->Javascript->setFile('jquery.datePicker.min.js');
        $this->Javascript->setInline(
            'Date.firstDayOfWeek=' . ($company_settings['calendar_begins'] == 'sunday' ? 0 : 1) . ';'
        );
    }

    /**
     * Formats terms into a numerically indexed array of periods and all their assigned terms
     *
     * @param array $terms The terms to format
     * @return A list of periods and terms
     */
    private function formatCouponTerms(array $terms)
    {
        $this->uses(['CouponTerms']);

        // Get all valid periods
        $periods = $this->CouponTerms->getPeriods();

        // Create an entry for each period
        $period_terms = [];
        foreach ($periods as $period => $name) {
            $period_terms[$period] = ['period' => $period, 'term' => []];
        }

        // Add each term to the correct period entry
        foreach ($terms as $term) {
            if (array_key_exists($term->period, (array)$period_terms)) {
                $period_terms[$term->period]['term'][] = $term->term;
            }
        }

        // Re-index the terms
        $terms = array_values($period_terms);
        foreach ($terms as $key => $coupon_term) {
            if (empty($terms[$key]['term'])) {
                // Remove entries with an empty term list
                unset($terms[$key]);
            } else {
                $terms[$key]['term'] = implode(',', $terms[$key]['term']);
            }
        }

        return $terms;
    }

    /**
     * Deletes a coupon
     */
    public function deleteCoupon()
    {
        $this->uses(['Coupons', 'CouponTerms']);

        // Redirect if invalid coupon was given
        if (!isset($this->post['id']) || !($coupon = $this->Coupons->get((int) $this->post['id']))) {
            $this->redirect($this->base_uri . 'settings/company/billing/coupons');
        }

        // Delete the coupon
        $this->Coupons->delete($coupon->id);
        $this->CouponTerms->delete($coupon->id);

        $this->flashMessage('message', Language::_('AdminCompanyBilling.!success.coupon_deleted', true));
        $this->redirect($this->base_uri . 'settings/company/billing/coupons/');
    }

    /**
     * Checks if any client group has client-group-specific settings attached to it
     *
     * @return bool True if a client group exists with client-group-specific settings for this company, false otherwise
     */
    private function clientGroupSettingsExist()
    {
        $this->uses(['ClientGroups']);

        // Get a list of all Client Groups belonging to this company
        $client_groups = $this->ClientGroups->getAll($this->company_id);

        // Check if any client groups have client-group-specific settings
        if (!empty($client_groups) && is_array($client_groups)) {
            $num_groups = count($client_groups);
            for ($i = 0; $i < $num_groups; $i++) {
                // Fetch settings for a client group, ignoring any setting inheritence
                $settings = $this->SettingsCollection->fetchClientGroupSettings(
                    $client_groups[$i]->id,
                    $this->ClientGroups,
                    true
                );

                // If any client group settings exist, return
                if (!empty($settings)) {
                    return true;
                }
            }
        }
        return false;
    }
}
