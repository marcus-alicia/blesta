<?php
/**
 * Order System checkout controller
 *
 * @package blesta
 * @subpackage blesta.plugins.order.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Checkout extends OrderFormController
{
    /**
     * Setup
     */
    public function preAction()
    {
        if ($this->action == 'complete') {
            // Disable CSRF for this request
            Configure::set('Blesta.verify_csrf_token', false);
        }
        parent::preAction();

        $this->components(['Input']);
    }

    /**
     * Collect payment/create order
     */
    public function index()
    {
        $this->uses([
            'Accounts', 'Contacts', 'Transactions', 'Payments', 'Invoices',
            'ClientGroups', 'EmailVerifications', 'Order.OrderOrders'
        ]);
        $this->helpers(['Form']);
        $vars = new stdClass();

        $invoice = false;

        // Remove any illegal items from the cart
        $this->cleanCart();

        // Run verifications
        // May redirect
        $order = $this->verifyClientAndOrder();
        $this->emailVerification();

        // If we have an order already, fetch the invoice
        if ($order && ($invoice = $this->Invoices->get($order->invoice_id)) && $invoice->currency) {
            // Update the cart to the currency from the invoice if one exists
            $this->SessionCart->setData('currency', $invoice->currency);
        }

        extract($this->getPaymentOptions());
        $summary = $this->getSummary();

        // Record TOS acceptance
        if (!empty($this->post) && (($this->post['agree_tos'] ?? 'false') == 'true')) {
            $this->SessionCart->setData('tos_accepted', true);
        }

        // Verify that terms of service have been accepted
        if (!$order
            && $this->order_form->require_tos
            && !$this->SessionCart->getData('tos_accepted', false)
        ) {
            if (!empty($this->post)) {
                $this->setMessage(
                    'error',
                    Language::_('Checkout.!error.invalid_agree_tos', true),
                    false,
                    null,
                    false
                );
            }
        } else {
            if (!$order) {
                // Create the order now to keep track if it gets abandoned, if no amount is due, skip the payment step
                // May redirect
                $order = $this->createOrderAndOrRedirect($summary, $currency);

                // Fetch the invoice created for the order
                $invoice = $this->Invoices->get($order->invoice_id);
            }

            // Check if the checkout process must be skipped
            $skip_checkout = (bool) $this->SessionCart->getData('skip_checkout');

            if (!empty($this->post) || $skip_checkout) {
                // Select the first non-merchant account if the order must be skipped
                if ($skip_checkout && !empty($nonmerchant_gateways)) {
                    $gateway = reset($nonmerchant_gateways);
                    $this->post = [
                        'checkout' => 'true',
                        'gateway' => $gateway->id ?? null
                    ];
                    $this->SessionCart->setData('skip_checkout', null);
                }

                if ($order && $invoice) {
                    // Apply credits to the order if appicable
                    // May redirect
                    $invoice = $this->applyCreditsToOrder($order, $invoice);

                    // Process payment
                    // May redirect
                    $this->processPaymentForOrder($order, $invoice);
                }

                $vars = (object)$this->post;
            }
        }

        $payment_accounts = $this->getPaymentAccounts($merchant_gateway, $currency, $payment_types);
        $require_passphrase = !empty($this->client->settings['private_key_passphrase']);

        $vars->country = (!empty($this->client->settings['country']) ? $this->client->settings['country'] : '');

        // Set currency
        $vars->currency = $invoice->currency ?? $this->client->settings['default_currency'] ?? null;

        // Set the contact info partial to the view
        $this->setContactView($vars);
        // Set the CC info partial to the view
        $this->setCcView($vars, false, true);
        // Set the ACH info partial to the view
        $this->setAchView($vars, false, true);

        // Set the total available credit that can be applied to the invoices
        $total_credit = $this->Transactions->getTotalCredit($this->client->id, $currency);
        $credits = ['currency' => $currency, 'amount' => $total_credit];

        // Skip to the next step if there is only one non-merchant gateway,
        // there is no available credit and the order form is not using the ajax template
        if (
            count($payment_accounts) == 0
            && count($payment_types) == 0
            && $credits['amount'] == 0
            && count($nonmerchant_gateways) == 1
            && $this->order_form->template !== 'ajax'
            && $this->SessionCart->getData('skip_checkout') != 1
        ) {
            $this->SessionCart->setData('skip_checkout', 1);

            if (isset($order->order_number)) {
                $this->redirect(
                    $this->base_uri . 'order/checkout/index/' . $this->order_form->label . '/' .
                    $order->order_number . '/'
                );
            } else {
                $this->redirect(
                    $this->base_uri . 'order/checkout/index/' . $this->order_form->label
                );
            }
        }

        $cart = $summary['cart'] ?? false;
        $totals = $summary['totals'] ?? false;
        $totals_section = (isset($order->order_number) ? $this->getTotals($order->order_number, true) : '');

        $this->set(
            compact(
                'vars',
                'cart',
                'totals',
                'payment_accounts',
                'require_passphrase',
                'payment_types',
                'nonmerchant_gateways',
                'order',
                'invoice',
                'credits',
                'totals_section'
            )
        );
    }

    /**
     * Verifies that the data submitted is valid for the current client and that no fraud is detected
     *
     * @return stdClass An object representing the current order
     */
    private function verifyClientAndOrder()
    {
        $order = false;
        // Require login to proceed
        if (!$this->client) {
            $this->redirect($this->base_uri . 'order/signup/index/' . $this->order_form->label);
        }

        // Can't proceed unless this is the account owner
        if (!$this->isClientOwner($this->client, $this->Session)) {
            $this->setMessage('error', Language::_('Checkout.!error.not_client_owner', true), false, null, false);
            $this->post = [];
        }

        // If order number given, verify it belongs to this client
        if (isset($this->get[1]) && (!($order = $this->OrderOrders->getByNumber($this->get[1])) ||
            !isset($this->client) || $order->client_id != $this->client->id)) {
            $this->redirect($this->base_uri . 'order/main/index/' . $this->order_form->label);
        }

        // Require order or non-empty cart
        if (!$order && $this->SessionCart->isEmptyCart()) {
            $this->redirect($this->base_uri . 'order/main/index/' . $this->order_form->label);
        }

        $this->ArrayHelper = $this->DataStructure->create('Array');
        $order_settings = $this->ArrayHelper->numericToKey(
            $this->OrderSettings->getSettings($this->company_id),
            'key',
            'value'
        );

        // Fraud detection
        if (!empty($order_settings['antifraud'])
            && (!isset($order_settings['antifraud_frequency'])
                || $order_settings['antifraud_frequency'] == 'always')
        ) {
            $errors = $this->runAntifraudCheck($order_settings, $this->client);

            if ($errors) {
                $this->flashMessage('error', $errors, null, false);
                $this->redirect($this->base_uri . 'order/cart/index/' . $this->order_form->label);
            }
        }

        return $order;
    }

    /**
     * Make sure that any email verifications are taken care of before accepting payment (if configured to do so)
     */
    private function emailVerification()
    {
        // Email verification
        $settings = $this->Form->collapseObjectArray(
            $this->ClientGroups->getSettings($this->client->client_group_id),
            'value',
            'key'
        );

        if (($settings['email_verification'] ?? false) == 'true'
            && ($settings['prevent_unverified_payments'] ?? false) == 'true'
            && ($email_verification = $this->EmailVerifications->getByContactId($this->client->contact_id))
            && $email_verification->verified == 0
        ) {
            // Update redirect url
            $vars = ['redirect_url' => $this->base_uri . 'order/checkout/index/' . $this->order_form->label];
            $this->EmailVerifications->edit($email_verification->id, $vars);

            // Set flash message with re-send link
            $time = time();
            $hash = $this->Clients->systemHash('c=' . $email_verification->contact_id . '|t=' . $time);
            $options = [
                'info_buttons' => [
                    [
                        'url' => $this->base_uri . 'client/verify/send/?sid=' . rawurlencode(
                            $this->EmailVerifications->systemEncrypt(
                                'c=' . $email_verification->contact_id . '|t=' . $time . '|h=' . substr($hash, -16)
                            )) . '&redirect=' . urlencode(
                            $this->base_uri . 'order/cart/index/' . $this->order_form->label
                        ),
                        'label' => Language::_('Checkout.!info.unverified_email_button', true),
                        'icon_class' => 'fa-share'
                    ]
                ]
            ];

            $this->flashMessage('info', Language::_('Checkout.!info.unverified_email', true), $options, false);
            $this->redirect($this->base_uri . 'order/cart/index/' . $this->order_form->label);
        }
    }

    /**
     * Attempt to create an order and redirect if there is an error or if the order total is 0
     *
     * @param array $summary Data summarizing the current order
     * @param string $currency The ISO 4217 currency code set for the order
     * @return stdClass An object representing the current order
     */
    private function createOrderAndOrRedirect($summary, $currency)
    {
        $order = $this->createOrder($summary['cart']['items'], $currency);

        // Set errors if add order failed
        if (($errors = $this->OrderOrders->errors())) {
            $this->flashMessage('error', $errors, null, false);
            $this->redirect($this->base_uri . 'order/cart/index/' . $this->order_form->label . '/');
        } else {
            // Order recorded, empty the cart
            $this->SessionCart->emptyCart();

            if ($summary['totals']['total']['amount'] <= 0) {
                $this->redirect(
                    $this->base_uri . 'order/checkout/complete/' . $this->order_form->label . '/' .
                    $order->order_number . '/'
                );
            }
        }

        return $order;
    }

    /**
     * Applies available credits to the current order invoice if told to do so
     *
     * @param stdClass $order An object representing the current order
     * @param stdClass $invoice An object representing the invoice for the current order
     * @return stdClass An object representing the invoice for the current order after applying credits
     */
    private function applyCreditsToOrder($order, $invoice)
    {
        // Apply any credits to the invoice
        if (isset($this->post['apply_credit']) && $this->post['apply_credit'] == 'true') {
            $amount_applied = $this->applyCredit($invoice);
            // Refetch the invoice
            if ($amount_applied !== false) {
                $invoice = $this->Invoices->get($invoice->id);

                // Redirect straight to the complete page if the credits took care of the entire invoice
                if ($invoice->due <= 0 && $invoice->date_closed !== null) {
                    $this->redirect(
                        $this->base_uri . 'order/checkout/complete/'
                        . $this->order_form->label . '/' . $order->order_number . '/'
                    );
                }
            }
        }

        return $invoice;
    }

    /**
     * Processes payment for an order and handles any errors and redirects
     *
     * @param stdClass $order An object representing the current order
     * @param stdClass $invoice An object representing the invoice for the current order
     */
    private function processPaymentForOrder($order, $invoice)
    {
        if (!isset($this->post['set_vars'])
            && (!empty($this->post['payment_account']) || !empty($this->post['payment_type']))) {
            $this->processPayment($order, $invoice);

            // If payment error occurred display error and allow repayment
            if (($errors = $this->Input->errors())) {
                $this->setMessage('error', $errors, false, null, false);
            } else {
                $this->redirect(
                    $this->base_uri . 'order/checkout/complete/'
                    . $this->order_form->label . '/' . $order->order_number . '/'
                );
            }
        } elseif (isset($this->post['gateway'])) {
            // Redirect to order complete page and render gateway button
            $this->redirect(
                $this->base_uri . 'order/checkout/complete/' . $this->order_form->label
                . '/' . $order->order_number . '/?gateway=' . $this->post['gateway']
            );
        } elseif (!isset($this->post['set_vars'])) {
            $this->setMessage(
                'error',
                Language::_('Checkout.!error.no_payment_info', true),
                false,
                null,
                false
            );
        }
    }

    /**
     * AJAX Retrieves the partial that displays totals
     * @see Checkout::index()
     *
     * @param int $order_number The order number whose totals to fetch (optional, default null)
     * @param bool $return True to return the partial for totals, or false to output as JSON (optional, default false)
     * @return string A string representing the totals partial
     */
    public function getTotals($order_number = null, $return = false)
    {
        $this->uses(['Invoices', 'Order.OrderOrders', 'Transactions']);

        // Require login to proceed
        if (!$this->client) {
            $this->redirect($this->base_uri . 'order/signup/index/' . $this->order_form->label);
        }

        if (!$return && !$this->isAjax()) {
            $this->redirect($this->base_uri . 'order/main/index/' . $this->order_form->label);
        }

        $order_number = ($order_number !== null ? $order_number : (isset($this->get[1]) ? $this->get[1] : null));

        // If order number given, verify it belongs to this client
        if ($order_number === null || !($order = $this->OrderOrders->getByNumber($order_number)) ||
            !isset($this->client) || $order->client_id != $this->client->id) {
            $this->redirect($this->base_uri . 'order/main/index/' . $this->order_form->label);
        }

        $invoice = $this->Invoices->get($order->invoice_id);

        if ($invoice) {
            // Format taxes
            $taxes = [];
            $exclusive_tax_amount = 0;
            if (!empty($invoice->taxes)) {
                foreach ($invoice->taxes as $tax) {
                    $taxes[] = [
                        'id' => $tax->id,
                        'name' => $tax->name,
                        'percentage' => $tax->amount,
                        'amount' => $tax->tax_total,
                        'amount_formatted' => $this->CurrencyFormat->format($tax->tax_total, $invoice->currency)
                    ];

                    // Calculate the total tax amount from exclusive taxes
                    if ($tax->type == 'exclusive') {
                        $exclusive_tax_amount += $tax->tax_total;
                    }
                }
            }

            // Set a credit, if any
            $total_credit = 0;
            if (isset($this->post['apply_credit']) && $this->post['apply_credit'] == 'true') {
                $total_credit = $this->Transactions->getTotalCredit($this->client->id, $invoice->currency);
            }

            // Set totals, with any credits
            $total_w_tax = max(0, ($invoice->due - $total_credit));
            $total = max(0, ($total_w_tax - $exclusive_tax_amount));
            $usable_credit = ($total_credit >= $invoice->due ? $invoice->due : $total_credit);
            $totals = [
                'subtotal' => [
                    'amount' => $invoice->subtotal,
                    'amount_formatted' => $this->CurrencyFormat->format($invoice->subtotal, $invoice->currency)
                ],
                'credit' => [
                    'amount' => -$usable_credit,
                    'amount_formatted' => $this->CurrencyFormat->format(-$usable_credit, $invoice->currency)
                ],
                'total' => [
                    'amount' => $total,
                    'amount_formatted' => $this->CurrencyFormat->format($total, $invoice->currency)
                ],
                'total_w_tax' => [
                    'amount' => $total_w_tax,
                    'amount_formatted' => $this->CurrencyFormat->format($total_w_tax, $invoice->currency)
                ],
                'paid' => [
                    'amount' => -$invoice->paid,
                    'amount_formatted' => $this->CurrencyFormat->format(-$invoice->paid, $invoice->currency)
                ],
                'tax' => $taxes
            ];

            $partial = $this->partial('checkout_total_info', ['totals' => $totals]);

            if ($return) {
                return $partial;
            }
            echo json_encode($partial);
        }

        return false;
    }

    /**
     * Display order complete/nonmerchant pay page
     */
    public function complete()
    {
        $this->uses(['Order.OrderOrders', 'Invoices']);

        if (!isset($this->get[1]) || !($order = $this->OrderOrders->getByNumber($this->get[1])) ||
            !isset($this->client) || $order->client_id != $this->client->id) {
            $this->redirect($this->base_uri . 'order/main/index/' . $this->order_form->label);
        }

        $invoice = $this->Invoices->get($order->invoice_id);

        if (isset($this->get['gateway'])) {
            $this->setNonmerchantDetails($order, $invoice, $this->get['gateway']);
        }

        $this->set('order', $order);
        $this->set('invoice', $invoice);
    }

    /**
     * Sets nonmerchant gateway payment details within the current view
     *
     * @param stdClass $order The order
     * @param stdClass $invoice The invoice
     * @param stdClass $gateway_id The ID of the gateway to render
     */
    private function setNonmerchantDetails($order, $invoice, $gateway_id)
    {
        extract($this->getPaymentOptions($invoice->currency));

        // Non-merchant gateway
        $this->uses(['Contacts', 'Countries', 'Payments', 'States']);

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
            'country' => (array)$this->Countries->get($this->client->country),
            'state' => (array)$this->States->get($this->client->country, $this->client->state)
        ];

        $options = [];
        $apply_amounts = [];
        // Set payment be applied to the invoice that was created
        $apply_amounts[$invoice->id] = $invoice->due;

        // Check for recurring info
        if (($recur = $this->Invoices->getRecurringInfo($invoice->id))) {
            $options['recur'] = $recur;
        }

        $options['description'] = Language::_('Checkout.index.description_invoice', true, $invoice->id_code);
        $options['return_url'] = rtrim($this->base_url, '/');

        $order_complete_uri = $this->base_uri . 'order/checkout/complete/'
            . $this->order_form->label . '/' . $order->order_number;

        foreach ($nonmerchant_gateways as $gateway) {
            if ($gateway->id == $gateway_id) {
                $this->set('gateway_name', $gateway->name);
                $options['return_url'] .= $order_complete_uri;

                $this->set(
                    'gateway_buttons',
                    $this->Payments->getBuildProcess(
                        $contact_info,
                        $invoice->due,
                        $currency,
                        $apply_amounts,
                        $options,
                        $gateway->id
                    )
                );
                break;
            }
        }

        $this->set('client', $this->client);
    }

    /**
     * Creates an order from the cart's items
     * @see Checkout::index
     *
     * @param array An array of cart items
     * @param string $currency The ISO 4217 currency code set for the order
     * @return mixed An stdClass object representing the order, or void on error
     */
    private function createOrder(array $items, $currency)
    {
        $this->uses([
            'Services',
            'Coupons',
            'Invoices',
            'EmailVerifications',
            'Order.OrderSettings',
            'Order.OrderAffiliates',
            'Order.OrderAffiliateReferrals',
            'Order.OrderAffiliateSettings',
            'Order.OrderAffiliateCompanySettings'
        ]);

        $requestor = $this->getFromContainer('requestor');

        $hold_unverified_orders = $this->OrderSettings->getSetting(
            Configure::get('Blesta.company_id'),
            'hold_unverified_orders'
        );
        $email_verification = $this->EmailVerifications->getByContactId($this->client->contact_id);
        $unverified_order_hold = $hold_unverified_orders
            && $hold_unverified_orders->value == 'true'
            && isset($email_verification->verified)
            && $email_verification->verified == 0;
        // Set order details
        $details = [
            'client_id' => $this->client->id,
            'order_form_id' => $this->order_form->id,
            'currency' => $currency,
            'fraud_report' => $this->SessionCart->getData('fraud_report')
                ? json_encode($this->SessionCart->getData('fraud_report'))
                : null,
            'fraud_status' => $this->SessionCart->getData('fraud_status'),
            'status' => (($this->order_form->manual_review
                    || $this->SessionCart->getData('fraud_status') == 'review'
                    || $unverified_order_hold
                )
                ? 'pending'
                : 'accepted'
            ),
            'coupon' => $this->SessionCart->getData('coupon'),
            'ip_address' => $requestor->ip_address
        ];

        // Attempt to add the order
        $order = $this->OrderOrders->add($details, $items);

        // Add affiliate referral
        $affiliate_code = isset($_COOKIE['affiliate_code']) ? $_COOKIE['affiliate_code'] : null;

        if (!empty($order) && !empty($affiliate_code)) {
            // Get invoice collection
            $presenter = $this->Invoices->getPresenter($order->invoice_id);
            $collection = $presenter->collection();

            $affiliate = $this->OrderAffiliates->getByCode($affiliate_code);
            $client = $this->Clients->get($order->client_id, false);

            // Get excluded packages
            $settings = $this->OrderAffiliateCompanySettings->getSetting($this->company_id, 'excluded_packages');
            $excluded_packages = isset($settings->value) ? (array)unserialize($settings->value) : [];

            // Remove the excluded packages from the collection
            foreach ($collection as $item) {
                $lines = $item->meta();

                foreach ($lines as $line) {
                    $fields = $line->getFields();
                    $service = $this->Services->get($fields->line->service_id);

                    if (in_array($service->package_pricing->package_id, $excluded_packages)) {
                        $collection->remove($item);
                    }

                }
            }
            $totals = $presenter->totals();

            // If the total amount of the order minus the excluded packages is less or equal to 0, return the order
            if ($totals->total_after_discount <= 0) {
                return $order;
            }

            // Validate if the affiliate code, does not belong to the current user
            if ((isset($affiliate->client_id) ? $affiliate->client_id : null) == $order->client_id || empty($affiliate)) {
                return $order;
            }

            // Get all affiliate settings
            $settings = [];
            $affiliate_settings = $this->OrderAffiliateSettings->getSettings($affiliate->id);

            foreach ($affiliate_settings as $setting) {
                $settings[$setting->key] = $setting->value;
            }

            // Validate if it's the first order placed by the client
            if (!isset($settings['order_frequency']) || $settings['order_frequency'] == 'first') {
                $client_orders = $this->OrderOrders->getOrdersByClientIdCount($order->client_id);

                if ($client_orders > 1) {
                    return $order;
                }
            }

            // Add referral
            $referral = [
                'affiliate_id' => $affiliate->id,
                'order_id' => $order->id,
                'name' => $client->first_name . ' ' . $client->last_name,
                'amount' => $totals->total_after_discount,
                'currency' => $order->currency,
                'commission' => ($settings['commission_type'] == 'percentage')
                    ? $totals->total_after_discount * ($settings['commission_amount'] / 100)
                    : $settings['commission_amount']
            ];
            $this->OrderAffiliateReferrals->add($referral);
        }

        return $order;
    }

    /**
     * Applies any existing credits from this client to the given invoice
     * @see Checkout::index()
     *
     * @param stdClass $invoice An stdClass object representing the invoice to be paid via credit
     * @return mixed A float value representing the amount that was applied to the invoice, otherwise boolean false
     */
    private function applyCredit($invoice)
    {
        if (!isset($this->Transactions)) {
            $this->uses(['Transactions']);
        }

        // Fetch the credits we have available
        $total_credit = $this->Transactions->getTotalCredit($this->client->id, $invoice->currency);

        // Apply as much credit as possible toward this invoice
        if ($total_credit > 0) {
            $apply_amount = ($invoice->due - $total_credit > 0 ? $total_credit : $invoice->due);
            $amounts = [
                ['invoice_id' => $invoice->id, 'amount' => $apply_amount]
            ];

            $amounts_applied = $this->Transactions->applyFromCredits($this->client->id, $invoice->currency, $amounts);
            $errors = $this->Transactions->errors();

            if (!empty($amounts_applied) && empty($errors)) {
                return $apply_amount;
            }
        }

        return false;
    }

    /**
     * Attempt merchant payment for the given order and invoice.
     * Sets Input errors on error.
     *
     * @param stdClass $order The order to process payment for
     * @param stdClass $invoice The invoice to process payment for
     */
    private function processPayment($order, $invoice)
    {
        extract($this->getPaymentOptions($invoice->currency));

        $this->uses(['Contacts', 'Accounts', 'Payments']);

        $payment_account = $this->Session->read('payment_account');
        if ($payment_account) {
            $this->post['payment_account'] = $payment_account;
            $this->Session->clear('payment_account');
        }

        $options = [];
        $pay_with = !empty($this->post['payment_account'])
            ? 'account'
            : (!empty($this->post['payment_type']) ? 'details' : null);

        if ($pay_with == 'account' || $pay_with == 'details') {
            $account_id =  null;
            $account_info = null;
            $type = $this->post['payment_type'] ?? 'cc';

            // Set payment account details
            if ($pay_with == 'account') {
                [$type, $account_id] = explode('_', $this->post['payment_account'], 2);
            } else {
                // Set the new payment account details
                // Fetch the contact we're about to set the payment account for
                $this->post['contact_id'] = (isset($this->post['contact_id']) ? $this->post['contact_id'] : 0);
                $contact = $this->Contacts->get($this->post['contact_id']);

                if ($this->post['contact_id'] == 'none' || !$contact || ($contact->client_id != $this->client->id)) {
                    $this->post['contact_id'] = $this->client->contact_id;
                }

                $type = $this->post['payment_type'];

                // Set payment currency
                $this->post['currency'] = $invoices['currency'] ?? $invoice->currency ?? $client->settings['default_currency'] ?? null;

                // Attempt to save the account, then set it as the account to use
                if (isset($this->post['save_details']) && $this->post['save_details'] == 'true') {
                    // Remove type, it will be automatically determined
                    unset($this->post['type']);

                    if ($type == 'ach') {
                        $account_id = $this->Accounts->addAch($this->post);
                    } elseif ($type == 'cc') {
                        if (isset($this->post['expiration_year']) || isset($this->post['expiration_month'])) {
                            // Concatenate the expiration date to the form 'yyyymm'
                            $this->post['expiration'] = ($this->post['expiration_year'] ?? '')
                                . ($this->post['expiration_month'] ?? '');
                        }
                        $account_id = $this->Accounts->addCc($this->post);
                    }

                    if (($errors = $this->Accounts->errors())) {
                        $this->Input->setErrors($errors);
                        return;
                    }
                } else {
                    $account_info = $this->getAccountInfo($this->post, $type);
                }
            }

            // Set payment to be applied to the invoice that was created
            $options['invoices'] = [$invoice->id => $invoice->due];

            // Capture payment if the funds were previously authorized, otherwise process the whole payment now
            $transaction_id = $this->Session->read('authorized_transaction_id');

            if ($transaction_id) {
                // Capture the payment
                $transaction = $this->Payments->capturePayment(
                    $this->client->id,
                    $transaction_id,
                    $invoice->due,
                    $options
                );

                $this->Session->write('authorized_transaction_id', null);
            } else {
                $transaction = $this->Payments->processPayment(
                    $this->client->id,
                    $type,
                    $invoice->due,
                    $invoice->currency,
                    $account_info,
                    $account_id,
                    $options
                );
            }

            // If payment error occurred, send client to pay invoice page (they're already logged in)
            if (($errors = $this->Payments->errors())) {
                $this->Input->setErrors($errors);
                return;
            }
        }
    }

    /**
     * Authorizes a payment and gets any payment confirmation html from the merchant gateway
     */
    public function getPaymentConfirmation()
    {
        $this->uses(['Order.OrderOrders', 'Invoices', 'Payments']);

        // If order number given, verify it belongs to this client
        if (!$this->isAjax()
            || (isset($this->get[1])
                && ($order = $this->OrderOrders->getByNumber($this->get[1]))
                && (!isset($this->client) || $order->client_id != $this->client->id)
            )
        ) {
            $this->redirect($this->base_uri . 'order/main/index/' . $this->order_form->label);
        }

        $summary = $this->getSummary();
        $invoice = null;
        if (isset($order)) {
            $invoice = $this->Invoices->get($order->invoice_id);
        }

        // Authorize a payment transaction
        $this->post['currency'] = $invoice ? $invoice->currency : $this->SessionCart->getData('currency');
        $transaction_response = $this->authorizePayment(
            $this->post,
            $invoice ? [$invoice->id => $invoice->due] : [],
            $invoice ? $invoice->due : $summary['totals']['total']['amount']
        );

        $form = '';
        if (isset($transaction_response['transaction']) && $transaction_response['transaction']) {
            $form = $this->Payments->getBuildPaymentConfirmation(
                $this->client->id,
                $transaction_response['transaction']->id
            );
        }

        echo $this->outputAsJson([
            'form' => $form,
            'error' => !empty($transaction_response['errors'])
                ? $this->setMessage('error', $transaction_response['errors'], true, null, false)
                : '',
            'redirect' => $transaction_response['redirect'] ?? null
        ]);

        return false;
    }

    /**
     * Attempts to create a payment authorization
     *
     * @param array $vars An array of payment details
     * @param array $apply_amounts An array of invoice amounts to pay
     * @param float $total The total amount to pay
     * @return array A list containing an stdClass object representing the transaction for this authorization if
     *  successful, or any errors that were returned when unsuccessful
     */
    private function authorizePayment(array $vars, array $apply_amounts, $total)
    {
        $this->uses(['Accounts', 'Payments', 'GatewayManager', 'Gateways']);

        $currency = isset($this->client) ? $this->client->settings['default_currency'] : null;

        if (isset($vars['currency'])) {
            $currency = $vars['currency'];
        }

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
        $response = ['transaction' => null, 'errors' => []];
        $options = [
            'invoices' => $apply_amounts,
            'staff_id' => null,
            'email_receipt' => true
        ];

        $account_id = null;
        $account_info = null;
        $type = $vars['payment_type'] ?? 'cc';

        // Set payment account details
        if (!empty($vars['payment_account'])) {
            [$type, $account_id] = explode('_', $vars['payment_account'], 2);
        } else {
            // Attempt to save the account, then set it as the account to use
            // Give no error if this doesn't succeed, some gateways save the
            // account after processing the payment
            if (isset($vars['save_details']) && $vars['save_details'] == 'true') {
                if ($type == 'ach') {
                    $account_id = $this->Accounts->addAch($vars);
                } elseif ($type == 'cc') {
                    if (isset($vars['expiration_year']) || isset($vars['expiration_month'])) {
                        // Concatenate the expiration date to the form 'yyyymm'
                        $vars['expiration'] = ($vars['expiration_year'] ?? '')
                            . ($vars['expiration_month'] ?? '');
                    }
                    $account_id = $this->Accounts->addCc($vars);
                }

                if ($account_id) {
                    $this->Session->write('payment_account', implode('_', [$type, $account_id]));
                }
            } else {
                $account_info = $this->getAccountInfo($vars, $type);
            }
        }

        // Check if the account must be verified
        $gateway = $this->GatewayManager->getInstalledMerchant(
            $this->company_id,
            $currency
        );
        if ($gateway) {
            $gateway_obj = $this->Gateways->create($gateway->class, $gateway->type);

            if ($type == 'ach'
                && $gateway_obj instanceof MerchantAchVerification
                && !empty($account_id)
                && ($account = $this->Accounts->getAch($account_id))
                && $account->status == 'unverified'
            ) {
                $response['redirect'] = $this->client_uri . 'accounts/verifyach/' . $account_id . '/';

                return $response;
            }
        }

        // Authorize CC payment
        if ((isset($vars['payment_account']) && substr($vars['payment_account'], 0, 2) == 'cc')
            || (isset($vars['payment_type']) && $vars['payment_type'] == 'cc')
        ) {
            // Attempt to authorize the payment. This may not be supported by the current merchant gateway
            $transaction = $this->Payments->authorizePayment(
                $this->client->id,
                $type,
                $total,
                $vars['currency'],
                $account_info,
                $account_id,
                $options
            );

            $errors = $this->Payments->errors();
            if ($errors) {
                foreach ($errors as $error) {
                    if (!array_key_exists('unsupported', $error)) {
                        $response['errors'] = Language::_('Checkout.!error.payment_authorize', true);
                        break;
                    }
                }
            }

            if ($transaction) {
                // Keep track of the current authorized transaction
                $this->Session->write('authorized_transaction_id', $transaction->id);
                $response['transaction'] = $transaction;
            }
        }

        return $response;
    }

    /**
     * Formats cc account info for making payments
     *
     * @param array $vars A list of payment info
     * @oaram string $type The type of the payment account
     * @return array A formatted list of payment info
     */
    private function getAccountInfo($vars, $type = 'cc')
    {
        // Pay one-time with the given details
        $account = [
            'first_name' => $vars['first_name'] ?? '',
            'last_name' => $vars['last_name'] ?? '',
            'address1' => $vars['address1'] ?? '',
            'address2' => $vars['address2'] ?? '',
            'city' => $vars['city'] ?? '',
            'state' => $vars['state'] ?? '',
            'country' => $vars['country'] ?? '',
            'zip' => $vars['zip'] ?? ''
        ];

        // Since we support gateway custom forms, we can't guarantee that these fields will be set
        if ($type == 'cc') {
            $account['card_number'] = $vars['number'] ?? null;
            $account['card_exp'] = (isset($vars['expiration_year']) && isset($vars['expiration_month'])) ?
                $vars['expiration_year'] . $vars['expiration_month'] : null;
            $account['card_security_code'] = $vars['security_code'] ?? null;
            $account['reference_id'] = $vars['reference_id'] ?? null;
            $account['client_reference_id'] = $vars['client_reference_id'] ?? null;
        }

        if ($type == 'ach') {
            $account_info['account_number'] = $vars['account'] ?? null;
            $account_info['routing_number'] = $vars['routing'] ?? null;
        }

        return $account;
    }

    /**
     * Sets the contact partial view
     *
     * @param stdClass $vars The input vars object for use in the view
     * @param bool $edit True if this is an edit, false otherwise
     */
    private function setContactView(stdClass $vars, $edit = false)
    {
        $this->uses(['Contacts', 'Countries', 'States', 'ClientGroups']);

        $contacts = [];

        if (!$edit) {
            // Set an option for no contact
            $no_contact = [
                (object)[
                    'id'=>'none',
                    'first_name'=>Language::_('Checkout.setcontactview.text_none', true),
                    'last_name'=>''
                ]
            ];

            // Set all contacts whose info can be prepopulated (primary or billing only)
            $primary_contact = $this->Contacts->getAll($this->client->id, 'primary');
            if (!isset($vars->contact_id) && isset($primary_contact[0])) {
                $vars->contact_id = $primary_contact[0]->id;
            }

            $contacts = array_merge($primary_contact, $this->Contacts->getAll($this->client->id, 'billing'));
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
            'order_form' => $this->order_form
        ];

        $this->set('contact_info', $this->partial('checkout_contact_info', $contact_info));
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

        $currency = isset($this->client) ? $this->client->settings['default_currency'] : null;

        if (isset($vars->currency)) {
            $currency = $vars->currency;
        }

        // Fetch the ach form to be used with this company and currency
        $gateway_form = $this->Payments->getBuildAchForm($currency, (array)$vars);

        // Check if the account must be verified
        $gateway = $this->GatewayManager->getInstalledMerchant($this->company_id, $currency);

        if ($gateway) {
            $gateway_obj = $this->Gateways->create($gateway->class, $gateway->type);
            if ($gateway_obj instanceof MerchantAchVerification) {
                $message = $this->setMessage(
                    'notice',
                    Language::_('Checkout.!info.ach_verification', true),
                    true,
                    null,
                    false
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

        $this->set('ach_info', $this->partial('checkout_ach_info', $ach_info));
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

        $currency = isset($this->client) ? $this->client->settings['default_currency'] : null;

        if (isset($vars->currency)) {
            $currency = $vars->currency;
        }

        // Fetch the cc form to be used with this company and currency
        $gateway_form = $this->Payments->getBuildCcForm($currency);

        // Set available credit card expiration dates
        $expiration = [
            // Get months with full name (e.g. "January")
            'months' => $this->Date->getMonths(1, 12, 'm', 'F'),
            // Sets years from the current year to 10 years in the future
            'years' => $this->Date->getYears(date('Y'), date('Y') + 10, 'Y', 'Y')
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

        $this->set('cc_info', $this->partial('checkout_cc_info', $cc_info));
    }

    /**
     * Gets all payments the client can choose from
     *
     * @param stdClass $merchant_gateway A stdClass object representin the merchant gateway,
     *  false if no merchant gateway set
     * @param string $currency The ISO 4217 currency code to pay in
     * @param array $payment_types An array of allowed key/value payment types,
     *  where each key is the payment type and each value is the payment type name
     */
    private function getPaymentAccounts($merchant_gateway, $currency, array $payment_types)
    {
        $this->uses(['Accounts', 'GatewayManager']);

        // Get ACH payment types
        $ach_types = $this->Accounts->getAchTypes();
        // Get CC payment types
        $cc_types = $this->Accounts->getCcTypes();

        // Set available payment accounts
        $payment_accounts = [];

        // Only allow CC payment accounts if enabled
        if (isset($payment_types['cc'])) {
            $cc = $this->Accounts->getAllCcByClient($this->client->id);

            $temp_cc_accounts = [];
            foreach ($cc as $account) {
                // Skip this payment account if it is expecting a different
                // merchant gateway, one is not available, or the payment
                // method is not supported by the gateway
                if (!$merchant_gateway
                    || ($merchant_gateway &&
                        (
                            ($account->gateway_id && $account->gateway_id != $merchant_gateway->id)
                            || ($account->reference_id
                                && !in_array('MerchantCcOffsite', $merchant_gateway->info['interfaces'])
                            )
                            || (!$account->reference_id
                                && !in_array('MerchantCc', $merchant_gateway->info['interfaces'])
                            )
                        )
                    )) {
                    continue;
                }

                $temp_cc_accounts['cc_' . $account->id] = Language::_(
                    'Checkout.getpaymentaccounts.account_name',
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
                    'name' => Language::_('Checkout.getpaymentaccounts.paymentaccount_cc', true)
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
                // Skip this payment account if it is expecting a different
                // merchant gateway, one is not available, or the payment
                // method is not supported by the gateway
                if (!$merchant_gateway
                    || ($merchant_gateway
                        && (
                            ($account->gateway_id && $account->gateway_id != $merchant_gateway->id)
                            || ($account->reference_id
                                && !in_array('MerchantAchOffsite', $merchant_gateway->info['interfaces'])
                            )
                            || (!$account->reference_id
                                && !in_array('MerchantAch', $merchant_gateway->info['interfaces'])
                            )
                        )
                    )) {
                    continue;
                }

                $temp_ach_accounts['ach_' . $account->id] = Language::_(
                    'Checkout.getpaymentaccounts.account_name',
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
                    'name' => Language::_('Checkout.getpaymentaccounts.paymentaccount_ach', true)
                ];
                $payment_accounts = array_merge($payment_accounts, $temp_ach_accounts);
            }
            unset($temp_ach_accounts);
        }

        return $payment_accounts;
    }
}
