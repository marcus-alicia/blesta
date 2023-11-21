<?php

/**
 * Client portal pay controller
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ClientPay extends AppController
{
    /**
     * Pre-action
     */
    public function preAction()
    {
        if (strtolower($this->action) == 'received') {
            // Disable automatic CSRF check for callback action
            Configure::set('Blesta.verify_csrf_token', false);
        }

        parent::preAction();

        $this->uses(
            [
                'Accounts',
                'Clients',
                'Contacts',
                'Currencies',
                'Invoices',
                'Services',
                'Transactions',
                'EmailVerifications',
                'ClientGroups'
            ]
        );

        // If hash access requested, verify that it is correct
        if ($this->action == 'method' && isset($this->get['sid'])) {
            $params = [];
            $temp = explode('|', $this->Invoices->systemDecrypt($this->get['sid']));

            if (count($temp) <= 1) {
                $this->redirect($this->base_uri . 'login/');
            }

            foreach ($temp as $field) {
                $field = explode('=', $field, 2);
                $params[$field[0]] = $field[1];
            }

            // Verify hash matches
            if (!$this->Invoices->verifyPayHash(
                $params['c'],
                (isset($this->get[0]) ? $this->get[0] : null),
                $params['h']
            )) {
                $this->redirect($this->base_uri . 'login/');
            }

            // Fetch the client record being processed
            $this->client = $this->Clients->get($params['c']);
        }

        // Get the logged-in client ID
        $blesta_client_id = $this->Session->read('blesta_client_id');

        // The logged-in client does not match the client in the stored payment, so clear the payment altogether
        // The admin likely tried a payment for a client and switched to another client
        $payment = $this->Session->read('payment');
        if (!empty($payment)
            && isset($payment['client_id'])
            && !empty($blesta_client_id)
            && $payment['client_id'] != $blesta_client_id
        ) {
            $this->Session->clear('payment');
            $payment = null;
        }

        // Require login if not on payment received screen or making payment
        if (!isset($this->client) && empty($payment) && $this->action != 'received') {
            $this->requireLogin();
        } elseif (isset($payment['client_id'])) {
            // Fetch the client from this payment session
            $this->client = $this->Clients->get($payment['client_id']);
        } elseif (isset($this->get['client_id'])) {
            $this->client = $this->Clients->get($this->get['client_id']);
        }

        Language::loadLang('client_pay');

        if (!isset($this->client)) {
            $this->client = $this->Clients->get($blesta_client_id);
        }

        // Verify if the email address belonging to this client has been verified
        if ($this->client) {
            Loader::loadHelpers($this, ['Form']);

            $settings = $this->ClientGroups->getSettings($this->client->client_group_id);
            $settings = $this->Form->collapseObjectArray($settings, 'value', 'key');

            $email_verification = $this->EmailVerifications->getByContactId($this->client->contact_id);

            if (
                isset($settings['email_verification'])
                && $settings['email_verification'] == 'true'
                && isset($settings['prevent_unverified_payments'])
                && $settings['prevent_unverified_payments'] == 'true'
                && isset($email_verification->verified)
                && $email_verification->verified == 0
            ) {
                // Update redirect url
                if (isset($this->get[0])) {
                    $vars = [
                        'redirect_url' => $this->base_uri . 'pay/method/' . $this->get[0] . '/'
                            . (isset($this->get['sid']) ? '?sid=' . $this->get['sid'] : null)
                    ];
                    $this->EmailVerifications->edit($email_verification->id, $vars);
                }

                // Set flash message with re-send link
                $time = time();
                $hash = $this->Clients->systemHash('c=' . $email_verification->contact_id . '|t=' . $time);
                $options = [
                    'info_buttons' => [
                        [
                            'url' => $this->base_uri . 'verify/send/?sid=' . rawurlencode(
                                $this->EmailVerifications->systemEncrypt(
                                    'c=' . $email_verification->contact_id . '|t=' . $time
                                        . '|h=' . substr($hash, -16)
                                )
                            ),
                            'label' => Language::_('ClientPay.!info.unverified_email_button', true),
                            'icon_class' => 'fa-share'
                        ]
                    ]
                ];

                $this->setMessage(
                    'info',
                    Language::_('ClientPay.!info.unverified_email', true),
                    false,
                    $options
                );

                // Prevent processing the submitted form
                $this->post = [];
            }
        }

        // Attempt to set the page title language
        if ($this->client) {
            try {
                $language = Language::_(
                    'ClientPay.' . Loader::fromCamelCase($this->action ? $this->action : 'index') . '.page_title',
                    true,
                    $this->client->id_code
                );
                $this->structure->set('page_title', $language);
            } catch (Throwable $e) {
                // Attempting to set the page title language has failed, likely due to
                // the language definition requiring multiple parameters.
                // Fallback to index. Assume the specific page will set its own page title otherwise.
                $this->structure->set(
                    'page_title',
                    Language::_('ClientPay.index.page_title', true),
                    $this->client->id_code
                );
            }
        } else {
            $this->redirect($this->base_uri);
        }
    }

    /**
     * Step 1 - select invoices to pay
     */
    public function index()
    {
        // Check if payment data has already been set
        $payment = $this->Session->read('payment');
        // Set payment data for updating
        if (!empty($payment['currency'])) {
            // Default to the current payment
            if (!isset($this->get[0]) || $this->get[0] == $payment['currency']) {
                $currency = $this->Currencies->get($payment['currency'], $this->company_id);
                $vars = new stdClass();
                $vars->credit = (isset($payment['credit']) ? $payment['credit'] : '');

                // Set the selected payment amounts
                if (isset($payment['amounts'])) {
                    $vars->applyamount = [];
                    $vars->invoice_id = [];
                    foreach ($payment['amounts'] as $amount) {
                        $vars->applyamount[$amount['invoice_id']] = $amount['amount'];
                        $vars->invoice_id[] = $amount['invoice_id'];
                    }
                    unset($amount);
                }
            }
        }

        // Ensure a valid currency was given
        if (isset($this->get[0]) && !($currency = $this->Currencies->get($this->get[0], $this->company_id))) {
            $this->redirect($this->base_uri);
        }

        // Get the client settings
        $client_settings = $this->client->settings;

        // Use the default currency
        if (empty($currency)) {
            $currency = $this->Currencies->get($client_settings['default_currency'], $this->company_id);
        }

        if (!empty($this->post)) {
            // Set the invoices selected to be paid
            $invoice_ids = (isset($this->post['invoice_id']) ? $this->post['invoice_id'] : null);
            if (isset($invoice_ids[0]) && $invoice_ids[0] == 'all') {
                unset($invoice_ids[0]);
            }

            // Check for invalid credit amounts
            $errors = [];
            $credit = $this->CurrencyFormat->cast(
                (isset($this->post['credit']) ? $this->post['credit'] : ''),
                $currency->code
            );
            if ($credit < 0) {
                $errors = [['invalid_credit' => Language::_('ClientPay.!error.invalid_amount', true)]];
            }

            // Check that either invoices were given to be paid, or a credit was
            if (empty($invoice_ids) && empty($this->post['credit'])) {
                $errors[] = ['payment_amounts' => Language::_('ClientPay.!error.payment_amounts', true)];
            }

            // Verify payment amounts, ensure that amounts entered do no exceed total due on invoice
            if (!empty($invoice_ids) || isset($this->post['credit'])) {
                $apply_amounts = [
                    'amounts' => [],
                    'currency' => $currency->code,
                    'credit' => (isset($this->post['credit']) ? $this->post['credit'] : 0),
                    'client_id' => (isset($payment['client_id']) ? $payment['client_id'] : null)
                ];

                $transaction_errors = [];
                if (!empty($invoice_ids)) {
                    foreach ($invoice_ids as $inv_id) {
                        if (isset($this->post['applyamount'][$inv_id])) {
                            $apply_amounts['amounts'][] = [
                                'invoice_id' => $inv_id,
                                'amount' => $this->CurrencyFormat->cast(
                                    $this->post['applyamount'][$inv_id],
                                    $currency->code
                                )
                            ];
                        }
                    }

                    $this->Transactions->verifyApply($apply_amounts, false);
                    $transaction_errors = $this->Transactions->errors();
                }
                $errors = array_merge(($transaction_errors ? $transaction_errors : []), $errors);
            }

            if ($errors) {
                $vars = (object) $this->post;
                $this->setMessage('error', $errors);
            } else {
                // Save the payment amounts
                $this->Session->write('payment', $apply_amounts);
                $this->redirect($this->base_uri . 'pay/method/');
            }
        }

        if (!isset($vars)) {
            $vars = new stdClass();
        }

        // Default select all invoices
        if (empty($vars->invoice_id) && empty($errors)) {
            $vars->invoice_id = ['all'];
        }

        // Get all invoices open for this client (to be paid)
        $invoice_list = (isset($invoice)
            ? [$invoice]
            : $this->Invoices->getAll($this->client->id, 'open', ['date_due' => 'ASC'], $currency->code)
        );

        // Check for different amounts due and disable toggle link
        $toggle_amounts = true;
        foreach ($invoice_list as $inv) {
            if (isset($vars->applyamount[$inv->id])
                && $vars->applyamount[$inv->id] != $this->CurrencyFormat->cast($inv->due, $currency->code)
            ) {
                $toggle_amounts = false;
                break;
            }
        }

        // Determine whether to check only past due invoices
        $show_past_due = (isset($this->get[1]) && $this->get[1] === 'pastdue');

        // If no specific invoice IDs are selected and we only want to show past due, check only past due
        if ($show_past_due
            && !empty($vars->invoice_id)
            && is_array($vars->invoice_id)
            && count($vars->invoice_id) == 1
            && $vars->invoice_id[0] == 'all'
        ) {
            $vars->invoice_id = [];

            foreach ($invoice_list as $inv) {
                // Add invoice to the list if it is past due
                if ($inv->date_due < $this->Invoices->dateToUtc(date('c'))) {
                    $vars->invoice_id[] = $inv->id;
                }
            }
        }

        $this->set('vars', $vars);
        $this->set('currency', (isset($currency) && $currency ? $currency->code : ''));
        $this->set(
            'invoice_info',
            $this->partial(
                'client_pay_multiple_invoices',
                [
                    'vars' => (isset($vars) ? $vars : new stdClass()),
                    'invoices' => $invoice_list,
                    'toggle_amounts' => $toggle_amounts,
                    'show_past_due' => $show_past_due
                ]
            )
        );
    }

    /**
     * Step 2 - select payment method
     */
    public function method()
    {
        if (isset($this->get[0]) && ($invoice = $this->Invoices->get($this->get[0]))) {
            // Redirect to main page if the invoice has been voided
            if ($invoice->status == 'void') {
                $this->flashMessage('error', Language::_('ClientPay.!error.invoice_voided', true));
                $this->redirect($this->base_uri);
            }

            // Set a message if the invoice has been paid
            if ($invoice->date_closed != null) {
                $this->setMessage('success', Language::_('ClientPay.!success.invoice_paid', true));
                $this->set('invoice_paid', true);
            }
        }

        // Pay a single invoice
        $pay_invoice = null;
        if (isset($this->get[0]) && isset($invoice) && $invoice->client_id == $this->client->id) {
            // Format the fields and save the info
            $pay_invoice = $invoice;
            $invoices = [
                'amounts' => [[
                    'invoice_id' => $invoice->id,
                    'amount' => $this->CurrencyFormat->cast($invoice->due, $invoice->currency)
                    ]],
                'currency' => $invoice->currency,
                'credit' => '',
                'client_id' => $this->client->id
            ];
            $this->Session->write('payment', $invoices);
        } else {
            // Ensure some invoices exist from the first step
            $invoices = $this->Session->read('payment');
            if ((!empty($invoice) && $invoice && $invoice->client_id != $this->client->id)
                || empty($invoices)
                || empty($invoices['currency'])
                || (empty($invoices['amounts']) && empty($invoices['credit']))
            ) {
                $this->Session->clear('payment');
                $this->redirect($this->base_uri);
            }

            // Set the invoice being paid if there is only one
            if (isset($invoices['amounts'])
                && is_array($invoices['amounts'])
                && count($invoices['amounts']) === 1
                && isset($invoices['amounts'][0]['invoice_id'])
                && ($invoice = $this->Invoices->get($invoices['amounts'][0]['invoice_id']))
            ) {
                $pay_invoice = $invoice;
            }
        }

        // Get all non-merchant gateways
        $this->uses(['GatewayManager']);
        $nm_gateways = $this->GatewayManager->getAllInstalledNonmerchant($this->company_id, $invoices['currency']);

        if (!empty($this->post)) {
            $errors = [];
            $this->post['pay_with'] = isset($this->post['pay_with']) ? $this->post['pay_with'] : null;

            // Apply any available credit to these invoices before continuing
            if (empty($errors) && isset($this->post['apply_credit']) && $this->post['apply_credit'] == 'true') {
                $temp_invoices = $invoices;
                $invoices = $this->applyCredit($invoices, $errors);

                // Credit was applied, show a success message if the credits covered the entire payment amount
                if ($temp_invoices != $invoices) {
                    // Determine whether all of the invoices have been closed
                    $credit_covered_invoices = true;
                    foreach ($invoices['amounts'] as $amount) {
                        if (!($invoice_applied = $this->Invoices->get($amount['invoice_id']))
                            || empty($invoice_applied->date_closed)
                        ) {
                            $credit_covered_invoices = false;
                            break;
                        }
                    }

                    // Credits covered all invoice amounts, and no further amount is to be paid
                    if ($credit_covered_invoices && empty($invoices['credit'])) {
                        $this->Session->clear('payment');

                        // Redirect to the login page if the client paid without
                        // being logged in, otherwise the client dashboard
                        $this->flashMessage('message', Language::_('ClientPay.!success.credit_full_processed', true));
                        $this->redirect($this->base_uri . ($this->isLoggedIn() ? '' : 'login/'));
                    } else {
                        // Update the session payment info to reflect the credit that has been applied
                        $this->Session->write('payment', array_merge($temp_invoices, $invoices));
                    }
                }
            }

            // Set payment currency
            $this->post['currency'] = $invoices['currency'] ?? $invoice->currency ?? $this->client->settings['default_currency'] ?? null;

            // Attempt to process the payment using one of the selected pay types
            if ($this->post['pay_with'] == 'details') {
                // Fetch the contact we're about to set the payment account for
                $this->post['contact_id'] = (isset($this->post['contact_id']) ? $this->post['contact_id'] : 0);
                $contact = $this->Contacts->get($this->post['contact_id']);

                if ($this->post['contact_id'] == 'none' || !$contact || ($contact->client_id != $this->client->id)) {
                    $this->post['contact_id'] = $this->client->contact_id;
                }

                // Attempt to save the account, then set it as the account to use
                if (isset($this->post['save_details']) && $this->post['save_details'] == 'true') {
                    if ($this->post['payment_type'] == 'ach') {
                        $account_id = $this->Accounts->addAch($this->post);

                        // Assign the newly created payment account as the account to use for this payment
                        if ($account_id) {
                            $this->post['payment_account'] = 'ach_' . $account_id;
                            $this->post['pay_with'] = 'account';
                        }
                    } elseif ($this->post['payment_type'] == 'cc') {
                        if (isset($this->post['expiration_year']) || isset($this->post['expiration_month'])) {
                            // Concatenate the expiration date to the form 'yyyymm'
                            $this->post['expiration'] = (
                                    isset($this->post['expiration_year']) ? $this->post['expiration_year'] : ''
                                ) . (isset($this->post['expiration_month']) ? $this->post['expiration_month'] : '');
                        }

                        // Remove type, it will be automatically determined
                        unset($this->post['type']);
                        $account_id = $this->Accounts->addCc($this->post);

                        // Assign the newly created payment account as the account to use for this payment
                        if ($account_id) {
                            $this->post['payment_account'] = 'cc_' . $account_id;
                            $this->post['pay_with'] = 'account';
                        }
                    }
                } else {
                    // Verify the payment account details entered were correct, since we're not storing them
                    $vars_arr = $this->post;
                    if ($this->post['payment_type'] == 'ach') {
                        $this->Accounts->verifyAch($vars_arr);
                    } elseif ($this->post['payment_type'] == 'cc') {
                        if (isset($this->post['expiration_year']) || isset($this->post['expiration_month'])) {
                            // Concatenate the expiration date to the form 'yyyymm'
                            $vars_arr['expiration'] = $this->post['expiration'] = (
                                    isset($this->post['expiration_year']) ? $this->post['expiration_year'] : ''
                                ) . (isset($this->post['expiration_month']) ? $this->post['expiration_month'] : '');
                        }

                        // Remove type, it will be automatically determined
                        unset($this->post['type'], $vars_arr['type']);
                        $this->Accounts->verifyCc($vars_arr, !isset($vars_arr['reference_id']));
                    } else {
                        $errors = [
                            [
                                'invalid_details' => Language::_('ClientPay.!error.invalid_details', true)
                            ]
                        ];
                    }

                    if (isset($vars_arr['type'])) {
                        $this->post['type'] = $vars_arr['type'];
                    }
                    unset($vars_arr);
                }

                if (!empty($errors)) {
                    if ($this->Accounts->errors()) {
                        $errors = array_merge($errors, $this->Accounts->errors());
                    }
                } else {
                    $errors = $this->Accounts->errors();
                }
            } elseif ($this->post['pay_with'] == 'account') {
                if (empty($this->post['payment_account'])) {
                    $errors = [
                        [
                            'invalid_details' => Language::_('ClientPay.!error.invalid_details', true)
                        ]
                    ];
                }
            } elseif ($this->post['pay_with'] != 'details' && $this->post['pay_with'] != 'account') {
                // Non-merchant gateway selected, make sure it's valid
                $errors = [
                    [
                        'invalid_details' => Language::_('ClientPay.!error.invalid_details', true)
                    ]
                ];
                foreach ($nm_gateways as $gateway) {
                    if ($this->post['pay_with'] == $gateway->id) {
                        $errors = [];
                        break;
                    }
                }
            }

            // Set any errors
            if ($errors) {
                // If a credit was applied, show the message
                if (isset($temp_invoices) && $temp_invoices != $invoices) {
                    $this->setMessage('message', Language::_('ClientPay.!success.credit_partial_processed', true));
                }

                $this->setMessage('error', $errors);
                $vars = (object) $this->post;
            } else {
                // If a credit was applied, show the message
                if (isset($temp_invoices) && $temp_invoices != $invoices) {
                    $this->flashMessage('message', Language::_('ClientPay.!success.credit_partial_processed', true));
                }

                // Save the payment method
                $this->Session->write('payment', array_merge($invoices, ['method' => $this->post]));
                $this->redirect($this->base_uri . 'pay/confirm/');
            }
        }

        // Set initial vars
        if (empty($vars)) {
            $vars = new stdClass();
        }

        // Fetch the auto-debit payment account (if set), so we can identify it
        $autodebit = $this->Clients->getDebitAccount($this->client->id);

        // Get ACH payment types
        $ach_types = $this->Accounts->getAchTypes();
        // Get CC payment types
        $cc_types = $this->Accounts->getCcTypes();

        // Set the payment types allowed
        $transaction_types = $this->Transactions->transactionTypeNames();
        $payment_types = ['' => Language::_('AppController.select.please', true)];
        if ($this->client->settings['payments_allowed_ach'] == 'true') {
            $payment_types['ach'] = $transaction_types['ach'];
        }
        if ($this->client->settings['payments_allowed_cc'] == 'true') {
            $payment_types['cc'] = $transaction_types['cc'];
        }

        // Set non-merchant gateway payment types
        $this->set('nm_gateways', $nm_gateways);

        // Set available payment accounts
        $payment_accounts = ['' => Language::_('AppController.select.please', true)];

        // Only allow CC payment accounts if enabled
        if (isset($payment_types['cc'])) {
            $cc = $this->Accounts->getAllCcByClient($this->client->id);

            $temp_cc_accounts = [];
            foreach ($cc as $account) {
                // Get the merchant gateway that can be used for this payment and this payment account
                $merchant_gateway = $this->GatewayManager->getInstalledMerchant(
                    $this->company_id,
                    $invoices['currency'],
                    $account->gateway_id
                );

                // Skip this payment account if it is expecting a different
                // merchant gateway, one is not available, or the payment
                // method is not supported by the gateway
                if (!$merchant_gateway
                    || ($merchant_gateway
                        && (
                            ($account->gateway_id && $account->gateway_id != $merchant_gateway->id)
                            || (
                                $account->reference_id
                                && !in_array('MerchantCcOffsite', $merchant_gateway->info['interfaces'])
                            )
                            || (
                                !$account->reference_id
                                && !in_array('MerchantCc', $merchant_gateway->info['interfaces'])
                            )
                        )
                    )
                ) {
                    continue;
                }

                $is_autodebit = false;
                if ($autodebit && $autodebit->type == 'cc' && $autodebit->account_id == $account->id) {
                    $is_autodebit = true;
                    $vars->payment_account = 'cc_' . $account->id;
                }
                $lang_define = ($is_autodebit
                    ? 'ClientPay.method.field_paymentaccount_autodebit'
                    : 'ClientPay.method.field_paymentaccount'
                );
                $temp_cc_accounts['cc_' . $account->id] = Language::_(
                    $lang_define,
                    true,
                    $account->first_name,
                    $account->last_name,
                    $cc_types[$account->type],
                    $account->last4
                );
            }

            // Add the CC payment accounts that can be used for this payment
            if (!empty($temp_cc_accounts)) {
                $payment_accounts[] = [
                    'value' => 'optgroup',
                    'name' => Language::_('ClientPay.method.field_paymentaccount_cc', true)
                ];
                $payment_accounts = array_merge($payment_accounts, $temp_cc_accounts);
            }
            unset($temp_cc_accounts);
        }

        // Only allow ACH payment accounts if enabled
        if (isset($payment_types['ach'])) {
            $ach = $this->Accounts->getAllAchByClient($this->client->id);

            $temp_ach_accounts = [];
            foreach ($ach as $account) {
                // Get the merchant gateway that can be used for this payment and this payment account
                $merchant_gateway = $this->GatewayManager->getInstalledMerchant(
                    $this->company_id,
                    $invoices['currency'],
                    $account->gateway_id
                );

                // Skip this payment account if it is expecting a different
                // merchant gateway, one is not available, or the payment
                // method is not supported by the gateway
                if (!$merchant_gateway
                    || ($merchant_gateway
                        && (
                            ($account->gateway_id && $account->gateway_id != $merchant_gateway->id)
                            || (
                                $account->reference_id
                                && !in_array('MerchantAchOffsite', $merchant_gateway->info['interfaces'])
                            ) || (
                                !$account->reference_id
                                && !in_array('MerchantAch', $merchant_gateway->info['interfaces'])
                            )
                        )
                    )
                ) {
                    continue;
                }

                $is_autodebit = false;
                if ($autodebit && $autodebit->type == 'ach' && $autodebit->account_id == $account->id) {
                    $is_autodebit = true;
                    $vars->payment_account = 'ach_' . $account->id;
                }
                $lang_define = ($is_autodebit
                    ? 'ClientPay.method.field_paymentaccount_autodebit'
                    : 'ClientPay.method.field_paymentaccount'
                );
                $temp_ach_accounts['ach_' . $account->id] = Language::_(
                    $lang_define,
                    true,
                    $account->first_name,
                    $account->last_name,
                    $ach_types[$account->type],
                    $account->last4
                );
            }

            // Add the ACH payment accounts that can be used for this payment
            if (!empty($temp_ach_accounts)) {
                $payment_accounts[] = [
                    'value' => 'optgroup',
                    'name' => Language::_('ClientPay.method.field_paymentaccount_ach', true)
                ];
                $payment_accounts = array_merge($payment_accounts, $temp_ach_accounts);
            }
            unset($temp_ach_accounts);
        }

        $this->set('payment_accounts', $payment_accounts);
        $this->set('require_passphrase', !empty($this->client->settings['private_key_passphrase']));

        // Set the country
        $vars->country = (!empty($this->client->settings['country']) ? $this->client->settings['country'] : '');

        // Default to paying with a payment account if one is set
        if (empty($vars->pay_with) && isset($vars->payment_account)) {
            $vars->pay_with = 'account';
        }

        // Set currency
        $vars->currency = $invoices['currency'] ?? $this->client->settings['default_currency'] ?? null;

        // Set the contact info partial to the view
        $this->setContactView($vars);
        // Set the CC info partial to the view
        $this->setCcView($vars, false, true);
        // Set the ACH info partial to the view
        $this->setAchView($vars, false, true);
        // Set the total available credit that can be applied to the invoices
        $total_credit = $this->Transactions->getTotalCredit($this->client->id, $invoices['currency']);

        // Set the total due
        $total = ($invoices['credit'] ? $invoices['credit'] : 0);

        // Calculate the total to pay for each invoice
        foreach ($invoices['amounts'] as $inv) {
            $total += $inv['amount'];
        }

        $this->set('total', $this->CurrencyFormat->cast($total, $invoices['currency']));
        $this->set('currency', $this->Currencies->get($invoices['currency'], $this->company_id));
        $this->set('payment_types', $payment_types);
        $this->set('vars', $vars);
        $this->set('credits', ['currency' => $invoices['currency'], 'amount' => $total_credit]);
        $this->set('invoice', $pay_invoice);
    }

    /**
     * Step 3 - confirm and make payment
     */
    public function confirm()
    {
        $this->uses(['Payments', 'Accounts']);

        // Ensure some invoices exist from the first step
        $payment = $this->Session->read('payment');
        if (empty($payment)
            || empty($payment['method'])
            || (empty($payment['amounts']) && empty($payment['credit']))
            || empty($payment['currency'])
            || !isset($payment['credit'])
        ) {
            $this->redirect($this->base_uri);
        }

        // Get the credit amount
        $total = $this->CurrencyFormat->cast($payment['credit'], $payment['currency']);
        $invoices = [];
        $apply_amounts = [];

        // Calculate the total to pay for each invoice
        foreach ($payment['amounts'] as $invoice) {
            $apply_amounts[$invoice['invoice_id']] = $this->CurrencyFormat->cast(
                $invoice['amount'],
                $payment['currency']
            );
            $total += $apply_amounts[$invoice['invoice_id']];

            $invoice = $this->Invoices->get($invoice['invoice_id']);
            if ($invoice && $invoice->client_id == $this->client->id) {
                $invoices[] = $invoice;
            }
        }

        // Check if the account must be verified, before executing the payment
        if ($payment['method']['pay_with'] == 'account' && $payment['method']['payment_type'] == 'ach') {
            // Set the account to use
            [$type, $account_id] = explode('_', $payment['method']['payment_account'], 2);
            $payment_account = $this->Accounts->getAch($account_id);

            if (($payment_account->status ?? '') == 'unverified') {
                $this->redirect($this->base_uri . 'accounts/verifyach/' . $account_id . '/');
            }

            // Unset the account before executing the payment
            unset($type, $account_id);
        }

        // Execute payment
        if (!empty($this->post)) {
            $options = [
                'invoices' => $apply_amounts,
                'staff_id' => null,
                'email_receipt' => true
            ];

            // Pay via existing CC/ACH account
            if ($payment['method']['pay_with'] == 'account') {
                $account_info = null;
                [$type, $account_id] = explode('_', $payment['method']['payment_account'], 2);
            } elseif ($payment['method']['pay_with'] == 'details') {
                // Pay one-time with the given details
                $type = $payment['method']['payment_type'];
                $account_id = null;
                $account_info = [
                    'first_name' => $payment['method']['first_name'],
                    'last_name' => $payment['method']['last_name'],
                    'address1' => $payment['method']['address1'],
                    'address2' => $payment['method']['address2'],
                    'city' => $payment['method']['city'],
                    'state' => $payment['method']['state'],
                    'country' => $payment['method']['country'],
                    'zip' => $payment['method']['zip']
                ];

                // Set ACH/CC-specific fields
                if ($type == 'ach') {
                    $account_info['account_number'] = $payment['method']['account'];
                    $account_info['routing_number'] = $payment['method']['routing'];
                    $account_info['type'] = $payment['method']['type'];
                } elseif ($type == 'cc') {
                    $account_info = $this->getCcAccountInfo($payment);
                }
            }

            // Process the payment (not non-merchant gateway payments)
            if ($payment['method']['pay_with'] == 'account' || $payment['method']['pay_with'] == 'details') {
                // Capture payment if the funds were previously authorized, otherwise process the whole payment now
                $transaction_id = $this->Session->read('authorized_transaction_id');
                if ($transaction_id) {
                    // Capture the payment
                    $transaction = $this->Payments->capturePayment(
                        $this->client->id,
                        $transaction_id,
                        $total,
                        $options
                    );

                    $this->Session->write('authorized_transaction_id', null);
                } else {
                    // Process the payment
                    $transaction = $this->Payments->processPayment(
                        $this->client->id,
                        $type,
                        $total,
                        $payment['currency'],
                        $account_info,
                        $account_id,
                        $options
                    );
                }

                if (($errors = $this->Payments->errors())) {
                    // Error
                    $this->setMessage('error', $errors);
                } else {
                    // Success, remove the payment data
                    $this->Session->clear('payment');

                    $this->flashMessage(
                        'message',
                        Language::_(
                            'ClientPay.!success.payment_processed',
                            true,
                            $this->CurrencyFormat->format($transaction->amount, $transaction->currency),
                            $transaction->transaction_id
                        )
                    );

                    // Redirect to the login page if the client paid without being logged in
                    $this->redirect($this->base_uri . ($this->isLoggedIn() ? '' : 'login/'));
                }
            }
        }

        // Set the payment account being used if one exists
        if ($payment['method']['pay_with'] == 'account') {
            // Set the account to use
            [$type, $account_id] = explode('_', $payment['method']['payment_account'], 2);

            if ($type == 'cc') {
                $this->set('account', $this->Accounts->getCc($account_id));
            } elseif ($type == 'ach') {
                $this->set('account', $this->Accounts->getAch($account_id));
            }

            $this->set('account_type', $type);
            $this->set('account_id', $account_id);
        } elseif ($payment['method']['pay_with'] == 'details') {
            // Set the last 4
            if ($payment['method']['payment_type'] == 'ach') {
                $payment['method']['last4'] = substr($payment['method']['account'], -4);
            } elseif ($payment['method']['payment_type'] == 'cc' && isset($payment['method']['number'])) {
                $payment['method']['last4'] = substr($payment['method']['number'], -4);
            }

            $this->set('account_type', $payment['method']['payment_type']);
            $this->set('account', (object) $payment['method']);
        } else {
            // Non-merchant gateway
            $this->uses(['Countries', 'Payments', 'States']);

            // Fetch this contact
            $contact = $this->Contacts->get($this->client->contact_id);

            $contact_info = [
                'id' => $this->client->contact_id,
                'client_id' => $this->client->id,
                'user_id' => $this->client->user_id,
                'contact_type' => $contact->contact_type_name,
                'contact_type_id' => $contact->contact_type_id,
                'first_name' => $this->client->first_name,
                'last_name' => $this->client->last_name,
                'title' => $contact->title,
                'company' => $this->client->company,
                'address1' => $this->client->address1,
                'address2' => $this->client->address2,
                'city' => $this->client->city,
                'zip' => $this->client->zip,
                'country' => (array) $this->Countries->get($this->client->country),
                'state' => (array) $this->States->get($this->client->country, $this->client->state)
            ];

            $options = [];
            $allow_recur = true;

            // Set the description for this payment
            $description = Language::_('ClientPay.confirm.description_credit', true);
            foreach ($invoices as $index => $invoice) {
                if ($index == 0) {
                    $description = Language::_('ClientPay.confirm.description_invoice', true, $invoice->id_code);
                } else {
                    $description .= Language::_('ClientPay.confirm.description_invoice_separator', true)
                        . ' ' . Language::_('ClientPay.confirm.description_invoice_number', true, $invoice->id_code);
                }

                // Check for recurring info
                if ($allow_recur && ($recur = $this->Invoices->getRecurringInfo($invoice->id))) {
                    // Only keep recurring info if none exists or is the same term and period as the existing
                    if (!isset($options['recur'])
                        || (
                            $options['recur']['term'] == $recur['term']
                            && $options['recur']['period'] == $recur['period']
                        )
                    ) {
                        if (!isset($options['recur'])) {
                            $options['recur'] = $recur;
                        } else {
                            // Sum recurring amounts
                            $options['recur']['amount'] += $recur['amount'];
                        }
                    } else {
                        unset($options['recur']);
                        $allow_recur = false;
                    }
                }
            }

            $options['description'] = $description;
            $options['return_url'] = rtrim($this->base_url, '/');

            // Get all non-merchant gateways
            $this->uses(['GatewayManager']);
            $nm_gateways = $this->GatewayManager->getAllInstalledNonmerchant($this->company_id, $payment['currency']);

            foreach ($nm_gateways as $gateway) {
                if ($gateway->id == $payment['method']['pay_with']) {
                    $this->set('gateway_name', $gateway->name);
                    $options['return_url'] .= $this->client_uri . 'pay/received/'
                        . $gateway->class . '/?client_id=' . $this->client->id;
                    break;
                }
            }

            $this->set('client', $this->client);
            $this->set(
                'gateway_buttons',
                $this->Payments->getBuildProcess(
                    $contact_info,
                    $total,
                    $payment['currency'],
                    $apply_amounts,
                    $options,
                    $payment['method']['pay_with']
                )
            );

            if (($errors = $this->Payments->errors())) {
                // Error
                $this->setMessage('error', $errors);
            }
        }

        // Build and authorize any payment confirmation (e.g. tokenized gateway)
        $this->set('merchant_payment_confirmation', $this->buildPaymentConfirmation($payment, $apply_amounts, $total));

        $this->set('invoices', $invoices);
        $this->set('apply_amounts', $apply_amounts);
        $this->set('total', $total);
        $this->set('account_types', $this->Accounts->getTypes());
        $this->set('ach_types', $this->Accounts->getAchTypes());
        $this->set('cc_types', $this->Accounts->getCcTypes());
        $this->set('currency', $payment['currency']);
    }

    /**
     * Authorizes a payment and builds the payment confirmation view for the gateway
     *
     * @param array $payment An array of payment details
     * @param array $apply_amounts An array of invoice amounts to pay
     * @param float $total The total amount to pay
     * @return string The HTML for the payment confirmation
     */
    private function buildPaymentConfirmation(array $payment, array $apply_amounts, $total)
    {
        // Authorize a payment transaction
        $transaction = $this->authorizePayment($payment, $apply_amounts, $total);

        if (!empty($transaction)) {
            return $this->Payments->getBuildPaymentConfirmation($this->client->id, $transaction->id);
        }

        return '';
    }

    /**
     * Attempts to create a payment authorization
     *
     * @param array $payment An array of payment details
     * @param array $apply_amounts An array of invoice amounts to pay
     * @param float $total The total amount to pay
     * @return stdClass|null An stdClass object representing the transaction for this authorization if successful,
     *  otherwise null
     */
    private function authorizePayment(array $payment, array $apply_amounts, $total)
    {
        $transaction_id = $this->Session->read('authorized_transaction_id');
        if ($transaction_id) {
            // Void the previously authorized transaction so we don't end up with a bunch
            // of hanging transaction holding funds on the CC. Give no error if this doesn't
            // succeed, it is just a best attempt to keep records clean
            $this->Payments->voidPayment($this->client->id, $transaction_id);

            $this->Payments->Input->setErrors([]);
        }

        // Attempt to authorize the payment. If successful, the funds will be captured after
        // confirmation. Otherwise all payment steps will be completed after submission using
        // Payments::processPayment()
        $transaction = null;
        if ((isset($payment['method']['payment_account'])
            && substr($payment['method']['payment_account'], 0, 2) == 'cc'
            ) || (isset($payment['method']['payment_type']) && $payment['method']['payment_type'] == 'cc')
        ) {
            $options = [
                'invoices' => $apply_amounts,
                'staff_id' => null,
                'email_receipt' => true
            ];

            $account_id = null;
            $account_info = null;
            if ($payment['method']['pay_with'] == 'account') {
                [$type, $account_id] = explode('_', $payment['method']['payment_account'], 2);
            } else {
                $account_info = $this->getCcAccountInfo($payment);
            }

            // Attempt to authorize the payment. This may not be supported by the current merchant gateway
            $transaction = $this->Payments->authorizePayment(
                $this->client->id,
                'cc',
                $total,
                $payment['currency'],
                $account_info,
                $account_id,
                $options
            );

            $errors = $this->Payments->errors();
            if ($errors) {
                foreach ($errors as $error) {
                    if (!array_key_exists('unsupported', $error)) {
                        $this->flashMessage('error', Language::_('ClientPay.!error.payment_authorize', true));
                        $this->redirect($this->base_uri . 'pay/method/');
                        break;
                    }
                }
            }

            // TODO Look into checking for validation errors and outputing those somewhere
            if ($transaction) {
                // Keep track of the current authorized transaction
                $this->Session->write('authorized_transaction_id', $transaction->id);
            }
        }

        return $transaction;
    }

    /**
     * Formats cc account info for making payments
     *
     * @param array $payment A list of payment info
     * @return array A formatted list of payment info
     */
    private function getCcAccountInfo($payment)
    {
        // Pay one-time with the given details
        $account_info = [
            'first_name' => $payment['method']['first_name'],
            'last_name' => $payment['method']['last_name'],
            'address1' => $payment['method']['address1'],
            'address2' => $payment['method']['address2'],
            'city' => $payment['method']['city'],
            'state' => $payment['method']['state'],
            'country' => $payment['method']['country'],
            'zip' => $payment['method']['zip']
        ];

        // Since we support gateway cc forms, we can't guarantee that these fields will be set
        $account_info['card_number'] = isset($payment['method']['number'])
            ? $payment['method']['number']
            : null;
        $account_info['card_exp'] = (isset($payment['method']['expiration_year'])
                && isset($payment['method']['expiration_month'])
            )
            ? $payment['method']['expiration_year'] . $payment['method']['expiration_month']
            : null;
        $account_info['card_security_code'] = isset($payment['method']['security_code'])
            ? $payment['method']['security_code']
            : null;
        $account_info['reference_id'] = isset($payment['method']['reference_id'])
            ? $payment['method']['reference_id']
            : null;
        $account_info['client_reference_id'] = isset($payment['method']['client_reference_id'])
            ? $payment['method']['client_reference_id']
            : null;

        return $account_info;
    }

    /**
     * Nonmerchant gateway payment received callback
     */
    public function received()
    {
        $this->components(['GatewayPayments']);

        $gateway_name = isset($this->get[0]) ? $this->get[0] : null;
        $this->get['client_id'] = $this->client->id;

        $trans_data = $this->GatewayPayments->processReceived($gateway_name, $this->get, $this->post);

        if (($errors = $this->GatewayPayments->errors())) {
            $this->setMessage('error', $errors);
        } else {
            // Get invoice data
            if (isset($trans_data['invoices'])) {
                $temp_invoices = [];
                foreach ($trans_data['invoices'] as $key => $invoice) {
                    if (isset($invoice['id']) && ($inv = $this->Invoices->get($invoice['id']))) {
                        $temp_invoices[] = $inv;
                    }
                }

                $trans_data['invoices'] = $temp_invoices;
            }

            $this->set('trans_data', $trans_data);
        }
    }

    /**
     * Applies any existing credits to the given invoices from this client
     *
     * @param array $invoices A list of invoices containing:
     *
     *  - amounts A list of invoice amounts including:
     *      - invoice_id The ID of the invoice to pay
     *      - amount The amount to apply to the invoice
     *  - currency The currency that these invoices are in
     * @param array $errors A reference to errors that will be set if attempting to apply credits leads to an error
     * @return array A subset of the given $invoices, with invoice amounts updated to the amounts still remaining
     */
    private function applyCredit(array $invoices, array &$errors = [])
    {
        // Nothing to apply a credit to
        if (empty($invoices['amounts'])) {
            return $invoices;
        }

        $amounts_applied = $this->Transactions->applyFromCredits(
            $this->client->id,
            $invoices['currency'],
            $invoices['amounts']
        );
        $errors = $this->Transactions->errors();

        // Subtract the amount applied as credit from the invoice amount given
        if (!empty($amounts_applied) && empty($errors)) {
            foreach ($invoices['amounts'] as &$invoice) {
                foreach ($amounts_applied as $transaction_id => $amounts) {
                    foreach ($amounts as $amount_applied) {
                        if (isset($amount_applied['invoice_id']) && isset($amount_applied['amount']) &&
                            $amount_applied['invoice_id'] == $invoice['invoice_id']) {
                            $invoice['amount'] = max(0, $invoice['amount'] - $amount_applied['amount']);
                        }
                    }
                }
            }
        }

        return $invoices;
    }

    /**
     * Sets the contact partial view
     * @see ClientPay::index()
     *
     * @param stdClass $vars The input vars object for use in the view
     * @param bool $edit True if this is an edit, false otherwise
     */
    private function setContactView(stdClass $vars, $edit = false)
    {
        $this->uses(['Countries', 'States', 'ClientGroups']);

        $contacts = [];
        $contact_fields_groups = ['required_contact_fields', 'shown_contact_fields', 'read_only_contact_fields'];

        if (!$edit) {
            // Set an option for no contact
            $no_contact = [
                (object) [
                    'id' => 'none',
                    'first_name' => Language::_('ClientPay.setcontactview.text_none', true),
                    'last_name' => ''
                ]
            ];

            // Set all contacts whose info can be prepopulated (primary or billing only)
            $contacts = array_merge(
                $this->Contacts->getAll($this->client->id, 'primary'),
                $this->Contacts->getAll($this->client->id, 'billing')
            );
            $contacts = array_merge($no_contact, $contacts);
        }

        // Set partial for contact info
        $contact_info = [
            'js_contacts' => json_encode($contacts),
            'contacts' => $this->Form->collapseObjectArray($contacts, ['first_name', 'last_name'], 'id', ' '),
            'countries' => $this->Form->collapseObjectArray(
                $this->Countries->getList(),
                ['name', 'alt_name'],
                'alpha2',
                ' - '
            ),
            'states' => $this->Form->collapseObjectArray($this->States->getList($vars->country), 'name', 'code'),
            'vars' => $vars,
            'edit' => $edit,
            'first_heading' => false,
            'show_company' => false
        ];

        // Get contact field groups
        foreach ($contact_fields_groups as $group_name) {
            if ($this->client) {
                ${$group_name} = $this->ClientGroups->getSetting($this->client->client_group_id, $group_name);

                if (${$group_name}) {
                    ${$group_name} = unserialize(base64_decode(${$group_name}->value));
                }
            }

            $contact_info[$group_name] = ${$group_name} ?? [];
        }

        // Load language for partial
        Language::loadLang('client_accounts');
        $this->set('contact_info', $this->partial('client_accounts_contact_info', $contact_info));
    }

    /**
     * Sets the ACH partial view
     * @see ClientPay::index()
     *
     * @param stdClass $vars The input vars object for use in the view
     * @param bool $edit True if this is an edit, false otherwise
     * @param bool $save_account True to offer an option to save these payment details, false otherwise
     */
    private function setAchView(stdClass $vars, $edit = false, $save_account = false)
    {
        $this->uses(['Payments', 'GatewayManager']);
        $this->components(['Gateways']);

        // Fetch the ach form to be used with this company and currency
        $gateway_form = $this->Payments->getBuildAchForm($vars->currency ?? $this->client->settings['default_currency'], (array)$vars);

        // Check if the account must be verified
        $gateway = $this->GatewayManager->getInstalledMerchant(
            $this->company_id,
            $this->client->settings['default_currency']
        );
        if ($gateway) {
            $gateway_obj = $this->Gateways->create($gateway->class, $gateway->type);
            if ($gateway_obj instanceof MerchantAchVerification) {
                $message = $this->setMessage(
                    'notice',
                    Language::_('ClientPay.!info.ach_verification_redirect', true),
                    true
                );
            }
        }

        // Set partial for ACH info
        $ach_info = [
            'types' => $this->Accounts->getAchTypes(),
            'vars' => $vars,
            'edit' => $edit,
            'client' => $this->client,
            'gateway_form' => $gateway_form,
            'save_account' => $save_account,
            'message' => $message ?? ''
        ];

        // Load language for partial
        Language::loadLang('client_accounts');
        $this->set('ach_info', $this->partial('client_accounts_ach_info', $ach_info));
    }

    /**
     * Sets the CC partial view
     * @see ClientPay::index()
     *
     * @param stdClass $vars The input vars object for use in the view
     * @param bool $edit True if this is an edit, false otherwise
     * @param bool $save_account True to offer an option to save these payment details, false otherwise
     */
    private function setCcView(stdClass $vars, $edit = false, $save_account = false)
    {
        $this->uses(['Payments']);

        // Fetch the cc form to be used with this company and currency
        $gateway_form = $this->Payments->getBuildCcForm($vars->currency ?? $this->client->settings['default_currency']);

        // Set available credit card expiration dates
        $years = $this->Date->getYears(date('Y'), date('Y') + 10, 'Y', 'Y');

        // Set the card year in case of an old, expired, card
        if (!empty($vars->expiration_year)
            && !array_key_exists($vars->expiration_year, $years)
            && preg_match('/^[0-9]{4}$/', $vars->expiration_year)
        ) {
            $card_year = [$vars->expiration_year => $vars->expiration_year];

            if ((int) $vars->expiration_year < reset($years)) {
                $years = $card_year + $years;
            } elseif ((int) $vars->expiration_year > end($years)) {
                $years += $card_year;
            }
        }

        $expiration = [
            // Get months with full name (e.g. "January")
            'months' => $this->Date->getMonths(1, 12, 'm', 'F'),
            // Sets years from the current year to 10 years in the future
            'years' => $years
        ];

        // Set partial for CC info
        $cc_info = [
            'expiration' => $expiration,
            'vars' => $vars,
            'edit' => $edit,
            'client' => $this->client,
            'gateway_form' => $gateway_form,
            'save_account' => $save_account
        ];

        // Load language for partial
        Language::loadLang('client_accounts');
        $this->set('cc_info', $this->partial('client_accounts_cc_info', $cc_info));
    }
}
