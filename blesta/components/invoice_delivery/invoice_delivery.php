<?php
/**
 * Invoice Delivery component
 *
 * Consolidates invoice creation and delivery. Supports email, interfax, and postalmethods.
 *
 * @package blesta
 * @subpackage blesta.components.invoice_delivery
 * @copyright Copyright (c) 2011, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class InvoiceDelivery
{
    /**
     * @var string The language code to use for all email correspondence
     */
    private $language;

    /**
     * @var object An object representing the company being processed
     */
    private $company;

    /**
     * @var array Company settings
     */
    private $company_settings;

    /**
     * @var array An array of stdClass objects representing invoices
     */
    private $invoices;

    /**
     * Initialize the Invoice Delivery object
     */
    public function __construct()
    {
        Loader::loadComponents($this, ['Input', 'Delivery', 'InvoiceTemplates', 'Download', 'SettingsCollection']);
        Loader::loadModels(
            $this,
            ['Accounts', 'Invoices', 'Clients', 'Contacts', 'Companies', 'Countries', 'Transactions']
        );
        Loader::loadHelpers($this, ['CurrencyFormat', 'Date', 'Form']);

        Language::loadLang('invoice_delivery', null, dirname(__FILE__) . DS . 'language' . DS);

        // Prime current company
        $this->primeCompany();
        
        // Set this constant to ensure that TCPDF uses the correct temp directory
        if (!defined('K_PATH_CACHE')) {
            $temp_dir = $this->company_settings['temp_dir'] ?? sys_get_temp_dir();
            $temp_dir = rtrim($temp_dir, DS) . DS;
            define('K_PATH_CACHE', $temp_dir);
        }
    }

    /**
     * Sets the company and company settings properties for the current company
     *
     * @param int $company_id The ID of the company to prime
     */
    private function primeCompany($company_id = null)
    {
        if (is_null($company_id)) {
            $company_id = Configure::get('Blesta.company_id');
        }

        $this->language = Configure::get('Language.default');
        $this->company = $this->Companies->get($company_id);
        $this->company_settings = $this->SettingsCollection->fetchSettings($this->Companies, $company_id);

        $this->Date->setTimezone('UTC', $this->company_settings['timezone']);
        $this->CurrencyFormat->setCompany($company_id);
    }

    /**
     * Delivers a set of invoices using the given delivery method. All invoices are compiled together into
     * a single document.
     *
     * @param array $invoice_ids An array of invoice IDs to deliver
     * @param string $delivery_method The delivery method (email, interfax, postalmethods)
     * @param mixed $deliver_to The destination of the invoices, a string or array of email addresses or fax
     *  numbers (optional, can not override invoice postal address)
     * @param string $from_staff_id The ID of the staff member this invoice is to be delivered from (optional)
     * @param array $options An array of additional options to pass and may include:
     *
     *  - base_client_url The base URL to the client interface
     *  - email_template The email template name to use (optional)
     *  - email_tags An array of key/value tag replacements (optional)
     *  - language The language to use (optional, defaults to the invoice client's language, or the system's
     *      language otherwise)
     */
    public function deliverInvoices(
        array $invoice_ids,
        $delivery_method,
        $deliver_to = null,
        $from_staff_id = null,
        array $options = null
    ) {
        // Check if the provided delivery method is handled by a messenger
        $non_messenger_methods = ['email', 'paper', 'interfax', 'postalmethods'];

        if (!in_array($delivery_method, $non_messenger_methods)) {
            Loader::loadModels($this, ['MessengerManager']);

            // Fetch all invoices request and build them together into a single document
            $invoices = $this->getInvoices($invoice_ids, true);

            // Set the payment URL and the autodebit date of this invoice if applicable
            foreach ($invoices as &$invoice) {
                $hash = $this->Invoices->createPayHash($invoice->client_id, $invoice->id);
                $invoice->payment_url = (isset($options['base_client_url']) ? $options['base_client_url'] : null)
                    . 'pay/method/'
                    . $invoice->id
                    . '/?sid='
                    . rawurlencode($this->Invoices->systemEncrypt('c=' . $invoice->client_id . '|h=' . $hash));

                // Set a new "autodebit_date" and "autodebit_date_formatted" value for each invoice
                $invoice->autodebit_date = '';
                if (($autodebit_date = $this->Invoices->getAutodebitDate($invoice->id))) {
                    $invoice->autodebit_date = $autodebit_date;
                    $invoice->autodebit_date_formatted = $this->Date->cast(
                        $autodebit_date,
                        $invoice->client->settings['date_format']
                    );
                }
            }
            unset($invoice);

            // Fetch the auto debit account, if any
            $invoice = (object)$invoices[0];

            $autodebit_account = null;
            $debit_account = $this->Clients->getDebitAccount($invoice->billing->client_id ?? $invoice->client->id);
            if (!empty($debit_account)) {
                // Get the account
                $autodebit_account = $debit_account->type == 'cc'
                    ? $this->Accounts->getCc($debit_account->account_id)
                    : $this->Accounts->getAch($debit_account->account_id);

                // Set the account type (as a tag for the email)
                $account_types = $debit_account->type == 'cc'
                    ? $this->Accounts->getCcTypes()
                    : $this->Accounts->getAchTypes();
                $autodebit_account->type_name = isset($account_types[$autodebit_account->type])
                    ? $account_types[$autodebit_account->type]
                    : $autodebit_account->type;

                $autodebit_account->account_type = $debit_account->type;
            }

            // Set default tags
            $tags = [
                'contact' => $invoice->billing ?? $invoice->client,
                'invoices' => $invoices,
                'autodebit' => ($invoice->client->settings['autodebit'] == 'true'),
                'payment_account' => $autodebit_account,
                'client_url' => isset($options['base_client_url']) ? $options['base_client_url'] : null
            ];

            // If only one invoice is set, create a new invoice tag to contain it
            if (count($invoices) === 1) {
                $tags['invoice'] = $invoice;
            }

            // Replace tags with those given
            if (isset($options['email_tags'])) {
                $tags = $options['email_tags'];
            }

            // Set "invoices" tag to those that were built
            if (isset($options['set_built_invoices']) && $options['set_built_invoices'] == true) {
                $tags['invoices'] = $invoices;
            }

            // Set the default email template
            if (!isset($options['email_template'])) {
                $options['email_template'] = 'invoice_delivery_unpaid';
            }

            // Deliver invoice through the messenger
            $this->MessengerManager->send($options['email_template'], $tags, [$invoice->client->user_id], $deliver_to);

            if (($errors = $this->MessengerManager->errors())) {
                $this->Input->setErrors($errors);
            }

            return;
        }

        switch ($delivery_method) {
            case 'email':
                // Fetch all invoices request and build them together into a single document
                $invoices = $this->getInvoices($invoice_ids, true);
                $document = $this->buildInvoices($invoices, true, $options);

                // Ensure we have an invoice document before continuing
                if (!$document) {
                    return;
                }

                if (!isset($this->Emails)) {
                    Loader::loadModels($this, ['Emails']);
                }

                // Create a unique name for the invoice file
                $temp_dir = $this->company_settings['temp_dir'] ?? sys_get_temp_dir();
                $temp_dir = rtrim($temp_dir, DS) . DS;

                if (!is_dir($temp_dir)) {
                    @mkdir($temp_dir, 0777, true);
                }

                if (!is_writeable($temp_dir)) {
                    @chmod($temp_dir, 0777);
                }

                if (is_writeable($temp_dir)) {
                    $inv_path = $temp_dir . 'inv-' . $this->company->id . '-'
                        . substr(
                            $this->Invoices->systemHash(json_encode($invoice_ids) . json_encode($deliver_to) . microtime()),
                            0,
                            20
                        );
                    file_put_contents($inv_path, $document->fetch());
                } else {
                    return;
                }

                // Set the attachment name and extension, either "invoices.ext" or the specific invoice ID
                $invoice = (object)$invoices[0];
                $attachment_name = ((count($invoices) > 1) ? 'invoices' : $invoice->id_code)
                    . '.'
                    . $document->getFileExtension($this->company_settings['inv_mimetype']);

                $attachments = [
                    [
                        'path' => $inv_path,
                        'name' => $attachment_name,
                        'encoding' => 'base64',
                        'type' => $this->company_settings['inv_mimetype']
                    ]
                ];

                // Set the payment URL and the autodebit date of this invoice if applicable
                foreach ($invoices as $key => $invoice) {
                    $hash = $this->Invoices->createPayHash($invoice->client_id, $invoice->id);
                    $invoices[$key]->payment_url = (isset($options['base_client_url']) ? $options['base_client_url'] : null)
                        . 'pay/method/'
                        . $invoice->id
                        . '/?sid='
                        . rawurlencode($this->Invoices->systemEncrypt('c=' . $invoice->client_id . '|h=' . $hash));

                    // Set a new "autodebit_date" and "autodebit_date_formatted" value for each invoice
                    $invoices[$key]->autodebit_date = '';
                    if (($autodebit_date = $this->Invoices->getAutodebitDate($invoice->id))) {
                        $invoices[$key]->autodebit_date = $autodebit_date;
                        $invoices[$key]->autodebit_date_formatted = $this->Date->cast(
                            $autodebit_date,
                            $invoices[$key]->client->settings['date_format']
                        );
                    }
                }
                unset($invoice);

                // Fetch the auto debit account, if any
                $invoice = (object)$invoices[0];
                $debit_account = $this->Clients->getDebitAccount($invoice->billing->client_id ?? $invoice->client->id);
                $autodebit_account = null;

                if (!empty($debit_account)) {
                    // Get the account
                    $autodebit_account = $debit_account->type == 'cc'
                        ? $this->Accounts->getCc($debit_account->account_id)
                        : $this->Accounts->getAch($debit_account->account_id);

                    // Set the account type (as a tag for the email)
                    $account_types = $debit_account->type == 'cc'
                        ? $this->Accounts->getCcTypes()
                        : $this->Accounts->getAchTypes();
                    $autodebit_account->type_name = $account_types[$autodebit_account->type] ?? $autodebit_account->type;
                    $autodebit_account->account_type = $debit_account->type;
                }

                // Set default tags
                $tags = [
                    'contact' => $invoice->billing ?? $invoice->client,
                    'invoices' => $invoices,
                    'autodebit' => ($invoice->client->settings['autodebit'] == 'true'),
                    'payment_account' => $autodebit_account,
                    'client_url' => isset($options['base_client_url']) ? $options['base_client_url'] : null
                ];

                // If only one invoice is set, create a new invoice tag to contain it
                if (count($invoices) === 1) {
                    $tags['invoice'] = $invoice;
                }

                // Replace tags with those given
                if (isset($options['email_tags'])) {
                    $tags = $options['email_tags'];
                }

                // Set "invoices" tag to those that were built
                if (isset($options['set_built_invoices']) && $options['set_built_invoices'] == true) {
                    $tags['invoices'] = $invoices;
                }

                // Set the default email template
                if (!isset($options['email_template'])) {
                    $options['email_template'] = 'invoice_delivery_unpaid';
                }

                $this->Emails->send(
                    $options['email_template'],
                    $this->company->id,
                    $invoice->client->settings['language'] ?? $this->language,
                    $deliver_to,
                    $tags,
                    null,
                    null,
                    $attachments,
                    ['to_client_id' => $invoice->client->id, 'from_staff_id' => $from_staff_id]
                );

                if (($errors = $this->Emails->errors())) {
                    $this->Input->setErrors($errors);
                }

                // Remove the temp invoice file
                @unlink($inv_path);
                break;
            case 'interfax':
                // Fetch all invoices request and build them together into a single document
                $invoices = $this->getInvoices($invoice_ids, true);
                $document = $this->buildInvoices($invoices, true, $options);

                // Ensure we have an invoice document before continuing
                if (!$document) {
                    return;
                }

                if (!isset($this->Interfax)) {
                    $this->Interfax = $this->Delivery->create('Interfax');

                    // Ensure the the system has the libxml extension
                    if (!extension_loaded('libxml')) {
                        unset($this->Interfax);

                        $errors = [
                            'libxml' => [
                                'required' => Language::_('InvoiceDelivery.!error.libxml_required', true)
                            ]
                        ];
                        $this->Input->setErrors($errors);

                        return;
                    }
                }

                $invoice = (object)$invoices[0];

                $this->Interfax->setAccount(
                    $this->company_settings['interfax_username'],
                    $this->company_settings['interfax_password']
                );
                $this->Interfax->setNumbers($deliver_to);
                $this->Interfax->setPageSize($this->company_settings['inv_paper_size']);
                $this->Interfax->setContacts(
                    ($invoice->billing->first_name ?? $invoice->client->first_name) .
                        ' ' . ($invoice->billing->last_name ?? $invoice->client->last_name)
                );
                $this->Interfax->setFile(
                    [
                        [
                            'file' => $document->fetch(),
                            'type' => $document->getFileExtension($this->company_settings['inv_mimetype'])
                        ]
                    ]
                );
                $this->Interfax->setCallerId($this->company->name);
                $this->Interfax->setSubject(
                    Language::_('InvoiceDelivery.deliverinvoices.interfax_subject', true, $invoice->id_code)
                );
                $this->Interfax->send();

                if (($errors = $this->Interfax->errors())) {
                    $this->Input->setErrors($errors);
                }
                break;
            case 'postalmethods':
                // Fetch all invoices, grouped by client
                $invoices = $this->getInvoices($invoice_ids);

                if (!isset($this->PostalMethods)) {
                    $this->PostalMethods = $this->Delivery->create('PostalMethods');
                }

                // Build and send one document per client
                foreach ($invoices as $invoice_set) {
                    // Build the document without address information (postalmethods will add their own)
                    $document = $this->buildInvoices($invoice_set, false, $options);

                    // Ensure we have an invoice document before continuing
                    if (!$document) {
                        continue;
                    }

                    $invoice = (object)$invoice_set[0];
                    $address = [
                        'name' => ($invoice->billing->first_name ?? $invoice->client->first_name) .
                            ' ' . ($invoice->billing->last_name ?? $invoice->client->last_name),
                        'company' => $invoice->billing->company ?? $invoice->client->company,
                        'address1' => $invoice->billing->address1 ?? $invoice->client->address1,
                        'address2' => $invoice->billing->address2 ?? $invoice->client->address2,
                        'city' => $invoice->billing->city ?? $invoice->client->city,
                        // The ISO 3166-2 subdivision code
                        'state' => $invoice->billing->state ?? $invoice->client->state,
                        'zip' => $invoice->billing->zip ?? $invoice->client->zip,
                        // The ISO 3166-1 alpha3 country code
                        'country_code' => $invoice->billing->country->alpha2 ?? $invoice->client->country->alpha2
                    ];

                    // Send invoices via PostalMethods
                    $this->PostalMethods->setApiKey($this->company_settings['postalmethods_apikey']);
                    $this->PostalMethods->setDescription(
                        Language::_(
                            'InvoiceDelivery.deliverinvoices.postalmethods_description',
                            true,
                            $invoice_set[0]->id_code
                        )
                    );
                    $this->PostalMethods->setToAddress($address);

                    if (isset($this->company_settings['postalmethods_doublesided'])) {
                        $this->PostalMethods->setDoubleSided($this->company_settings['postalmethods_doublesided']);
                    }

                    if (isset($this->company_settings['postalmethods_colored'])) {
                        $this->PostalMethods->setColored($this->company_settings['postalmethods_colored']);
                    }

                    $this->PostalMethods->setFile(
                        [
                            'file' => $document->fetch(),
                            'type' => $document->getFileExtension($this->company_settings['inv_mimetype'])
                        ]
                    );
                    $this->PostalMethods->send();

                    if (($errors = $this->PostalMethods->errors())) {
                        $this->Input->setErrors($errors);
                    }
                }
                break;
        }
    }

    /**
     * Offers for download a set of invoices. All invoices are compiled together into a single document.
     *
     * @param array $invoice_ids A numerically-indexed array of invoice IDs from which to download
     * @param array $options An array of options including (optional):
     *  - language The language to use (optional, defaults to the invoice client's language, or the
     *    system's language otherwise)
     */
    public function downloadInvoices(array $invoice_ids, array $options = null)
    {
        // Fetch invoices
        $invoices = $this->getInvoices($invoice_ids, true);

        // Fetch cached invoices
        if (
            Configure::get('Caching.on')
            && is_writable($this->company_settings['uploads_dir'])
            && $this->company_settings['inv_cache'] == 'json_pdf'
        ) {
            $cache = $this->Invoices->fetchCache(implode('_', $invoice_ids), 'pdf', $options['language'] ?? null);

            if (!empty($cache)) {
                $name = 'invoices';
                foreach ($invoices as $invoice) {
                    $name = $invoice->id_code . '.pdf';
                }

                $this->Download->setContentType('application/pdf');
                $this->Download->downloadData($name, $cache);
                exit;
            }
        }

        $this->buildInvoices($invoices, true, $options)->download();
    }

    /**
     * Returns an errors raised
     *
     * @return array An array of errors, boolean false if no errors were set
     */
    public function errors()
    {
        return $this->Input->errors();
    }

    /**
     * Fetches invoices and groups them by client ID
     *
     * @param array $invoice_ids An array of invoice ID numbers to fetch
     * @param bool $merge True to merge all invoices together in single large array, false to keep
     *  invoices divided by client ID
     * @return array An array of stdClass invoice object grouped by client ID (if $merge is true 1st index is numeric,
     *  otherwise 1st index is client ID, 2nd index is numeric)
     */
    private function getInvoices(array $invoice_ids, $merge = false)
    {
        $invoices = [];
        for ($i = 0, $num_invoices = count($invoice_ids); $i < $num_invoices; $i++) {
            $invoice = $this->Invoices->get($invoice_ids[$i]);

            if ($invoice) {
                if (empty($invoice->client)) {
                    $invoice->client = $this->Clients->get($invoice->client_id);
                }

                if (empty($invoice->billing)) {
                    Loader::loadModels($this, ['Contacts']);

                    $contacts = $this->Contacts->getAll($invoice->client_id, 'billing');
                    foreach ($contacts as $contact) {
                        if ($this->Clients->validateBillingContact($contact->id, $invoice->client_id)) {
                            $invoice->billing = $contact;
                        }
                    }
                }

                if (!isset($invoices[$invoice->client_id])) {
                    $invoices[$invoice->client_id] = [];
                }

                $invoices[$invoice->client_id][] = $invoice;
            }
        }

        // Squish the multi-dimensional array into a single dimension
        if ($merge) {
            $all_invoices = [];
            foreach ($invoices as $client_id => $invoice_set) {
                $all_invoices = array_merge($all_invoices, $invoice_set);
            }
            $invoices = $all_invoices;
        }

        $this->invoices = $invoices;

        return $invoices;
    }

    /**
     * Takes an array of invoices and constructs a single document object containing
     * all invoice data (e.g. can create a single PDF containing multiple invoices).
     *
     * @param array A numerically indexed array of stdClass objects each representing an invoice
     * @param bool $include_address True to include address information on the invoices, false otherwise
     * @param array $options An array of options including (optional):
     *  - language The language to use (optional, defaults to the invoice client's language,
     *    or the system's language otherwise)
     * @return object The object containing the build invoices
     */
    private function buildInvoices(array $invoices, $include_address = true, array $options = null)
    {
        $client_id = null;
        $transaction_types = $this->Transactions->transactionTypeNames();

        $invoice_ids = $this->Form->collapseObjectArray($invoices, 'id', 'id');;

        // Prime company
        $this->primeCompany();

        // Set language
        if (!empty($options['language'])) {
            $this->language = $options['language'];
        }

        for ($i = 0, $num_invoices = count($invoices); $i < $num_invoices; $i++) {
            // Fetch invoice client and contact
            $client = null;

            if ($client_id != $invoices[$i]->client_id) {
                $client_id = $invoices[$i]->client_id;

                // Fetch the contact to which invoices should be addressed
                $client = $this->Clients->get($client_id);
                if (!($billing = $this->Contacts->get((int)$client->settings['inv_address_to']))
                    || $billing->client_id != $client_id
                ) {
                    $billing = $this->Contacts->get($client->contact_id);
                }

                $country = $this->Countries->get($billing->country);
                $client->settings = $this->trimInvoiceSettings(
                    $client->settings,
                    $invoices[$i]->client->company_id ?? $this->company->id
                );
                $this->language = $client->settings['language'];
            }

            $invoices[$i]->billing = $billing;
            $invoices[$i]->billing->country = $country;
            $invoices[$i]->client = $client;

            if (empty($client)) {
                $invoices[$i]->client = $this->Clients->get($invoices[$i]->client_id);
            }

            // Set applied transactions
            $invoices[$i]->applied_transactions = $this->Transactions->getApplied(null, $invoices[$i]->id);

            // Fetch cached data
            $cache = $this->Invoices->fetchCache($invoices[$i]->id, 'json', $this->language);

            if (
                Configure::get('Caching.on')
                && $this->company_settings['inv_cache'] !== 'none'
            ) {
                // Fetch cache
                if (!empty($cache)) {
                    $cache->client->settings = (array) $cache->client->settings;
                    $cache->applied_transactions = $invoices[$i]->applied_transactions;

                    $this->company_settings = array_merge((array) $this->company_settings, (array) $cache->company_settings);
                    $this->company = $cache->company;

                    $invoices[$i] = $cache;
                }

                // Save data on cache
                if (is_writable($this->company_settings['uploads_dir'])) {
                    $json_cache = $this->Invoices->fetchCache($invoices[$i]->id, 'json', $this->language);
                    $pdf_cache = $this->Invoices->fetchCache($invoices[$i]->id, 'pdf', $this->language);

                    // Save JSON cache
                    if (empty($json_cache)) {
                        try {
                            $this->Invoices->writeCache(
                                $invoices[$i]->id,
                                (object) array_merge(
                                    (array) $invoices[$i],
                                    (array) ['company_settings' => $this->company_settings, 'company' => $this->company]
                                ),
                                'json',
                                $this->language
                            );
                        } catch (Exception $e) {
                            // Write to cache failed, so disable caching
                            Configure::set('Caching.on', false);
                        }
                    }

                    // Save PDF cache
                    if (empty($pdf_cache) && $this->company_settings['inv_cache'] == 'json_pdf') {
                        try {
                            $document = $this->buildDocument(
                                $this->language,
                                $include_address,
                                [$invoices[$i]]
                            );
                            $this->Invoices->writeCache($invoices[$i]->id, $document->fetch(), 'pdf', $this->language);
                        } catch (Exception $e) {
                            // Write to cache failed, so disable caching
                            Configure::set('Caching.on', false);
                        }
                    }
                }
            }

            // Set real name to applied transactions
            foreach ($invoices[$i]->applied_transactions as &$applied_transaction) {
                $applied_transaction->type_real_name = $transaction_types[
                ($applied_transaction->type_name != ''
                    ? $applied_transaction->type_name
                    : $applied_transaction->type)
                ];
            }
        }

        // Generate PDF document
        $document = $this->buildDocument(
            $options['language'] ?? null,
            $include_address,
            $invoices
        );

        // Save PDF cache
        $pdf_cache = $this->Invoices->fetchCache(implode('_', $invoice_ids), 'pdf', $options['language'] ?? null);
        if (
            Configure::get('Caching.on')
            && $this->company_settings['inv_cache'] !== 'json_pdf'
            && is_writable($this->company_settings['uploads_dir'])
            && empty($pdf_cache)
            && isset($options['language'])
        ) {
            try {
                $this->Invoices->writeCache(
                    implode('_', $invoice_ids),
                    $document->fetch(),
                    'pdf',
                    $options['language']
                );
            } catch (Exception $e) {
                // Write to cache failed, so disable caching
                Configure::set('Caching.on', false);
            }
        }

        // Restore company settings
        if (!empty($cache)) {
            $this->company_settings = $this->SettingsCollection->fetchSettings($this->Companies, $this->company->id);
        }

        return $document;
    }

    /**
     * Generates a PDF document for the given invoices on a specific invoice template
     *
     * @param string $language The language for all invoices in the document
     * @param bool $include_address True to include the address on the invoice
     * @param array $invoices A numerically indexed array of invoice objects
     * @return InvoiceTemplates The document of the invoices
     */
    private function buildDocument($language, $include_address, $invoices = null)
    {
        if (isset($this->CurrencyFormat)) {
            $this->CurrencyFormat->setCompany($company->id ?? null);
        }

        if (is_null($invoices)) {
            $invoices = $this->invoices;
        }

        try {
            $meta = [
                'paper_size' => $this->company_settings['inv_paper_size'],
                'background' => $this->company_settings['inv_background'],
                'logo' => $this->company_settings['inv_logo'],
                'company_name' => $this->company->name,
                'company_address' => $this->company->address,
                'tax_id' => $this->company_settings['tax_id'],
                'terms' => array_key_exists('inv_terms_' . Configure::get('Blesta.language'), (array)$this->company_settings)
                    ? $this->company_settings['inv_terms_' . Configure::get('Blesta.language')]
                    : '',
                'display_logo' => $this->company_settings['inv_display_logo'],
                'display_paid_watermark' => $this->company_settings['inv_display_paid_watermark'],
                'display_companyinfo' => $this->company_settings['inv_display_companyinfo'],
                'display_payments' => $this->company_settings['inv_display_payments'],
                'display_due_date_draft' => $this->company_settings['inv_display_due_date_draft'],
                'display_due_date_proforma' => $this->company_settings['inv_display_due_date_proforma'],
                'display_due_date_inv' => $this->company_settings['inv_display_due_date_inv'],
                'settings' => $this->company_settings,
                'language' => $language
            ];

            $document = $this->InvoiceTemplates->create($this->company_settings['inv_template']);
            $document->setMeta($meta);
            $document->setCurrency($this->CurrencyFormat);
            $document->setDate($this->Date);
            $document->setMimeType($this->company_settings['inv_mimetype']);
            $document->includeAddress($include_address);
            $document->makeDocument($invoices);
        } catch (Throwable $e) {
            $this->Input->setErrors(['InvoiceTemplates' => ['create' => $e->getMessage()]]);
        }

        return $document ?? null;
    }

    /**
     * Removes all settings not relevant to invoices from a list of settings
     *
     * @param array $settings The list of settings to filter
     * @return array The filtered list of settings
     */
    protected function trimInvoiceSettings(array $settings, $company_id)
    {
        if (!isset($this->Languages)) {
            Loader::loadModels($this, ['Languages']);
        }

        // Create a whitelist of invoice settings by which to filter
        $whitelist = array_flip([
            'apply_inv_late_fees',
            'autodebit',
            'autodebit_attempts',
            'autodebit_days_before_due',
            'autosuspend',
            'calendar_begins',
            'cancel_service_changes_days',
            'cancelation_fee_tax',
            'cascade_tax',
            'client_prorate_credits',
            'client_set_invoice',
            'client_set_lang',
            'clients_cancel_services',
            'country',
            'date_format',
            'datetime_format',
            'default_currency',
            'delivery_methods',
            'email_verification',
            'enable_eu_vat',
            'enable_tax',
            'inv_background',
            'inv_cache',
            'inv_cache_compress',
            'inv_days_before_renewal',
            'inv_display_companyinfo',
            'inv_display_due_date_draft',
            'inv_display_due_date_inv',
            'inv_display_due_date_proforma',
            'inv_display_logo',
            'inv_display_paid_watermark',
            'inv_display_payments',
            'inv_draft_format',
            'inv_format',
            'inv_increment',
            'inv_lines_verbose_option_dates',
            'inv_logo',
            'inv_method',
            'inv_mimetype',
            'inv_pad_size',
            'inv_pad_str',
            'inv_paper_size',
            'inv_proforma_format',
            'inv_proforma_start',
            'inv_start',
            'inv_suspended_services',
            'inv_template',
            'inv_type',
            'language',
            'late_fee_total_amount',
            'late_fees',
            'mail_delivery',
            'payments_allowed_ach',
            'payments_allowed_cc',
            'prevent_unverified_payments',
            'setup_fee_tax',
            'show_client_tax_id',
            'show_currency_code',
            'tax_exempt',
            'tax_exempt_eu_vat',
            'tax_home_eu_vat',
            'tax_id',
            'timezone',
            'uploads_dir',
        ]);

        // White list language based settings for each installed language
        $languages = $this->Languages->getAll($company_id);
        $language_based_settings = ['inv_font', 'inv_terms'];

        foreach ($language_based_settings as $language_based_setting) {
            foreach ($languages as $language) {
                $whitelist[$language_based_setting . '_' . $language->code] = true;
            }
        }

        // Filter out all company settings that are not whitelisted
        foreach ($settings as $key => $value) {
            if (!array_key_exists($key, $whitelist)) {
                unset($settings[$key]);
            }
        }

        return $settings;
    }
}
