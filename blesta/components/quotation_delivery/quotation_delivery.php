<?php
/**
 * Quotation Delivery component
 *
 * Consolidates quotation creation and delivery. Supports the same delivery methods as Invoices.
 *
 * @package blesta
 * @subpackage blesta.components.quotation_delivery
 * @copyright Copyright (c) 2023, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class QuotationDelivery extends InvoiceDelivery
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
     * @var array An array of stdClass objects representing quotations
     */
    private $quotations;

    /**
     * Initialize the Quotation Delivery object
     */
    public function __construct()
    {
        Loader::loadModels($this, ['Quotations']);

        Language::loadLang('quotation_delivery', null, dirname(__FILE__) . DS . 'language' . DS);

        parent::__construct();

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
     * Delivers a set of quotations using the given delivery method. All quotations are compiled together into
     * a single document.
     *
     * @param array $quotation_ids An array of quotation IDs to deliver
     * @param string $delivery_method The delivery method (email, interfax, postalmethods)
     * @param mixed $deliver_to The destination of the quotations, a string or array of email addresses or fax
     *  numbers (optional, can not override quotation postal address)
     * @param string $from_staff_id The ID of the staff member this quotation is to be delivered from (optional)
     * @param array $options An array of additional options to pass and may include:
     *
     *  - base_client_url The base URL to the client interface
     *  - email_template The email template name to use (optional)
     *  - email_tags An array of key/value tag replacements (optional)
     *  - language The language to use (optional, defaults to the quotation client's language, or the system's
     *      language otherwise)
     */
    public function deliverQuotations(
        array $quotation_ids,
        $delivery_method,
        $deliver_to = null,
        $from_staff_id = null,
        array $options = null
    ) {
        // Check if the provided delivery method is handled by a messenger
        $non_messenger_methods = ['email', 'paper', 'interfax', 'postalmethods'];

        if (!in_array($delivery_method, $non_messenger_methods)) {
            Loader::loadModels($this, ['MessengerManager']);

            // Fetch all quotations request and build them together into a single document
            $quotations = $this->getQuotations($quotation_ids, true);

            // Set default tags
            $quotation = (object)$quotations[0];
            $tags = [
                'contact' => $quotation->billing ?? $quotation->client,
                'quotations' => $quotations,
                'company' => $this->Companies->get($this->company->id),
                'client_url' => $options['base_client_url'] ?? null
            ];

            // If only one quotation is set, create a new quotation tag to contain it
            if (count($quotations) === 1) {
                $tags['quotation'] = $quotation;
            }

            // Replace tags with those given
            if (isset($options['email_tags'])) {
                $tags = $options['email_tags'];
            }

            // Set the default email template
            if (!isset($options['email_template'])) {
                $options['email_template'] = 'quotation_delivery';
            }

            // Deliver quotation through the messenger
            $this->MessengerManager->send($options['email_template'], $tags, [$quotation->client->user_id], $deliver_to);

            if (($errors = $this->MessengerManager->errors())) {
                $this->Input->setErrors($errors);
            }

            return;
        }

        switch ($delivery_method) {
            case 'email':
                // Fetch all quotations request and build them together into a single document
                $quotations = $this->getQuotations($quotation_ids, true);
                $document = $this->buildQuotations($quotations, true, $options);

                // Ensure we have a quotation document before continuing
                if (!$document) {
                    return;
                }

                if (!isset($this->Emails)) {
                    Loader::loadModels($this, ['Emails']);
                }

                // Create a unique name for the quotation file
                $temp_dir = $this->company_settings['temp_dir'] ?? sys_get_temp_dir();
                $temp_dir = rtrim($temp_dir, DS) . DS;

                if (!is_dir($temp_dir)) {
                    @mkdir($temp_dir, 0777, true);
                }

                if (!is_writeable($temp_dir)) {
                    @chmod($temp_dir, 0777);
                }

                if (is_writeable($temp_dir)) {
                    $inv_path = $temp_dir . 'quote-' . $this->company->id . '-' . substr(
                        $this->Quotations->systemHash(json_encode($quotation_ids) . json_encode($deliver_to) . microtime()),
                        0,
                        20
                    );
                    file_put_contents($inv_path, $document->fetch());
                } else {
                    return;
                }

                // Set the attachment name and extension, either "quotations.ext" or the specific quotation ID
                $quotation = (object) $quotations[0];
                $attachment_name = ((count($quotations) > 1) ? 'quotations' : $quotation->id_code)
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

                // Set default tags
                $quotation = (object) $quotations[0];
                $tags = [
                    'contact' => $quotation->billing ?? $quotation->client,
                    'quotations' => $quotations,
                    'company' => $this->Companies->get($this->company->id),
                    'client_url' => $options['base_client_url'] ?? null
                ];

                // If only one quotation is set, create a new quotation tag to contain it
                if (count($quotations) === 1) {
                    $tags['quotation'] = $quotation;
                }

                // Replace tags with those given
                if (isset($options['email_tags'])) {
                    $tags = $options['email_tags'];
                }

                // Set the default email template
                if (!isset($options['email_template'])) {
                    $options['email_template'] = 'quotation_delivery';
                }

                $this->Emails->send(
                    $options['email_template'],
                    $this->company->id,
                    $quotation->client->settings['language'] ?? $this->language,
                    $deliver_to,
                    $tags,
                    null,
                    null,
                    $attachments,
                    ['to_client_id' => $quotation->client->id, 'from_staff_id' => $from_staff_id]
                );

                if (($errors = $this->Emails->errors())) {
                    $this->Input->setErrors($errors);
                }

                // Remove the temp quotation file
                @unlink($inv_path);
                break;
            case 'interfax':
                // Fetch all quotations request and build them together into a single document
                $quotations = $this->getQuotations($quotation_ids, true);
                $document = $this->buildQuotations($quotations, true, $options);

                // Ensure we have a quotation document before continuing
                if (!$document) {
                    return;
                }

                if (!isset($this->Interfax)) {
                    $this->Interfax = $this->Delivery->create('Interfax');

                    // Ensure the system has the libxml extension
                    if (!extension_loaded('libxml')) {
                        unset($this->Interfax);

                        $errors = [
                            'libxml' => [
                                'required' => Language::_('QuotationDelivery.!error.libxml_required', true)
                            ]
                        ];
                        $this->Input->setErrors($errors);

                        return;
                    }
                }

                $quotation = (object) $quotations[0];

                $this->Interfax->setAccount(
                    $this->company_settings['interfax_username'],
                    $this->company_settings['interfax_password']
                );
                $this->Interfax->setNumbers($deliver_to);
                $this->Interfax->setPageSize($this->company_settings['inv_paper_size']);
                $this->Interfax->setContacts(
                    ($quotation->billing->first_name ?? $quotation->client->first_name) . ' ' .
                    ($quotation->billing->last_name ?? $quotation->client->last_name)
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
                    Language::_('QuotationDelivery.deliverquotations.interfax_subject', true, $quotation->id_code)
                );
                $this->Interfax->send();

                if (($errors = $this->Interfax->errors())) {
                    $this->Input->setErrors($errors);
                }
                break;
            case 'postalmethods':
                // Fetch all quotations, grouped by client
                $quotations = $this->getQuotations($quotation_ids);

                if (!isset($this->PostalMethods)) {
                    $this->PostalMethods = $this->Delivery->create('PostalMethods');
                }

                // Build and send one document per client
                foreach ($quotations as $quotation_set) {
                    // Build the document without address information (postalmethods will add their own)
                    $document = $this->buildQuotations($quotation_set, false, $options);

                    // Ensure we have a quotation document before continuing
                    if (!$document) {
                        continue;
                    }

                    $quotation = (object) $quotation_set[0];
                    $address = [
                        'name' => ($quotation->billing->first_name ?? $quotation->client->first_name) . ' ' .
                            ($quotation->billing->last_name ?? $quotation->client->last_name),
                        'company' => $quotation->billing->company ?? $quotation->client->company,
                        'address1' => $quotation->billing->address1 ?? $quotation->client->address1,
                        'address2' => $quotation->billing->address2 ?? $quotation->client->address2,
                        'city' => $quotation->billing->city ?? $quotation->client->city,
                        // The ISO 3166-2 subdivision code
                        'state' => $quotation->billing->state ?? $quotation->client->state,
                        'zip' => $quotation->billing->zip ?? $quotation->client->zip,
                        // The ISO 3166-1 alpha3 country code
                        'country_code' => $quotation->billing->country->alpha2 ?? $quotation->client->country->alpha2
                    ];

                    // Send quotations via PostalMethods
                    $this->PostalMethods->setApiKey($this->company_settings['postalmethods_apikey']);
                    $this->PostalMethods->setDescription(
                        Language::_(
                            'QuotationDelivery.deliverquotations.postalmethods_description',
                            true,
                            $quotation_set[0]->id_code
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
     * Fetches quotations and groups them by client ID
     *
     * @param array $quotation_ids An array of quotation ID numbers to fetch
     * @param bool $merge True to merge all quotations together in single large array, false to keep
     *  quotations divided by client ID
     * @return array An array of stdClass quotation object grouped by client ID (if $merge is true 1st index is numeric,
     *  otherwise 1st index is client ID, 2nd index is numeric)
     */
    private function getQuotations(array $quotation_ids, $merge = false)
    {
        $quotations = [];
        for ($i = 0, $num_quotations = count($quotation_ids); $i < $num_quotations; $i++) {
            $quotation = $this->Quotations->get($quotation_ids[$i]);

            if ($quotation) {
                if (empty($quotation->client)) {
                    $quotation->client = $this->Clients->get($quotation->client_id);
                }

                if (empty($quotation->billing)) {
                    Loader::loadModels($this, ['Contacts']);

                    $contacts = $this->Contacts->getAll($quotation->client_id, 'billing');
                    foreach ($contacts as $contact) {
                        if ($this->Clients->validateBillingContact($contact->id, $quotation->client_id)) {
                            $quotation->billing = $contact;
                        }
                    }
                }

                if (!isset($quotations[$quotation->client_id])) {
                    $quotations[$quotation->client_id] = [];
                }

                $quotations[$quotation->client_id][] = $quotation;
            }
        }

        // Squish the multi-dimensional array into a single dimension
        if ($merge) {
            $all_quotations = [];
            foreach ($quotations as $client_id => $quotation_set) {
                $all_quotations = array_merge($all_quotations, $quotation_set);
            }
            $quotations = $all_quotations;
        }

        $this->quotations = $quotations;

        return $quotations;
    }

    /**
     * Offers for download a set of quotations. All quotations are compiled together into a single document.
     *
     * @param array $quotation_ids A numerically-indexed array of quotation IDs from which to download
     * @param array $options An array of options including (optional):
     *  - language The language to use (optional, defaults to the quotation client's language, or the
     *    system's language otherwise)
     */
    public function downloadQuotations(array $quotation_ids, array $options = null)
    {
        // Fetch quotations
        $quotations = $this->getQuotations($quotation_ids, true);

        // Fetch cached quotations
        if (
            Configure::get('Caching.on')
            && is_writable($this->company_settings['uploads_dir'])
            && $this->company_settings['inv_cache'] == 'json_pdf'
        ) {
            $cache = $this->Quotations->fetchCache(implode('_', $quotation_ids), 'pdf', $options['language'] ?? null);

            if (!empty($cache)) {
                $name = 'quotations';
                foreach ($quotations as $quotation) {
                    $name = $quotation->id_code . '.pdf';
                }

                $this->Download->setContentType('application/pdf');
                $this->Download->downloadData($name, $cache);
                exit;
            }
        }

        $this->buildQuotations($quotations, true, $options)->download();
    }

    /**
     * Takes an array of quotations and constructs a single document object containing
     * all quotation data (e.g. can create a single PDF containing multiple quotations).
     *
     * @param array A numerically indexed array of stdClass objects each representing an quotation
     * @param bool $include_address True to include address information on the quotations, false otherwise
     * @param array $options An array of options including (optional):
     *  - language The language to use (optional, defaults to the quotation client's language,
     *    or the system's language otherwise)
     * @return object The object containing the build quotations
     */
    private function buildQuotations(array $quotations, $include_address = true, array $options = null)
    {
        $client_id = null;

        // Set language
        if (!empty($options['language'])) {
            $this->language = $options['language'];
        }

        $quotation_ids = $this->Form->collapseObjectArray($quotations, 'id', 'id');;

        for ($i = 0, $num_quotations = count($quotations); $i < $num_quotations; $i++) {
            // Fetch quotation client and contact
            $client = null;

            if ($client_id != $quotations[$i]->client_id) {
                $client_id = $quotations[$i]->client_id;

                // Fetch the contact to which quotations should be addressed
                $client = $this->Clients->get($client_id);
                if (!($billing = $this->Contacts->get((int)$client->settings['inv_address_to']))
                    || $billing->client_id != $client_id
                ) {
                    $billing = $this->Contacts->get($client->contact_id);
                }

                $billing->country = $this->Countries->get($billing->country);
                $client->settings = $this->trimInvoiceSettings(
                    $client->settings,
                    $quotations[$i]->client->company_id ?? $this->company->id
                );
                $this->language = $client->settings['language'];
            }

            if (empty($client)) {
                $client = $this->Clients->get($quotations[$i]->client_id);
            }

            $quotations[$i]->billing = $billing ?? null;
            $quotations[$i]->client = $client;
            $quotations[$i]->date_closed = null;
            $quotations[$i]->date_billed = null;
            $quotations[$i]->date_due = null;

            // Fetch cached data
            $cache = $this->Quotations->fetchCache($quotations[$i]->id, 'json', $this->language);

            if (
                Configure::get('Caching.on')
                && $this->company_settings['inv_cache'] !== 'none'
            ) {
                // Fetch cache
                if (!empty($cache)) {
                    $cache->client->settings = (array) $cache->client->settings;
                    $cache->applied_transactions = $quotations[$i]->applied_transactions;

                    $this->company_settings = array_merge((array) $this->company_settings, (array) $cache->company_settings);
                    $this->company = $cache->company;

                    $quotations[$i] = $cache;
                }

                // Save data on cache
                if (is_writable($this->company_settings['uploads_dir'])) {
                    $json_cache = $this->Quotations->fetchCache($quotations[$i]->id, 'json', $this->language);
                    $pdf_cache = $this->Quotations->fetchCache($quotations[$i]->id, 'pdf', $this->language);

                    // Save JSON cache
                    if (empty($json_cache)) {
                        try {
                            $this->Quotations->writeCache(
                                $quotations[$i]->id,
                                (object) array_merge(
                                    (array) $quotations[$i],
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
                                [$quotations[$i]]
                            );
                            $this->Quotations->writeCache($quotations[$i]->id, $document->fetch(), 'pdf', $this->language);
                        } catch (Exception $e) {
                            // Write to cache failed, so disable caching
                            Configure::set('Caching.on', false);
                        }
                    }
                }
            }
        }

        // Generate PDF document
        $document = $this->buildDocument(
            $options['language'] ?? null,
            $include_address,
            $quotations
        );

        // Save PDF cache
        $pdf_cache = $this->Quotations->fetchCache(implode('_', $quotation_ids), 'pdf', $options['language'] ?? null);
        if (
            Configure::get('Caching.on')
            && $this->company_settings['inv_cache'] !== 'json_pdf'
            && is_writable($this->company_settings['uploads_dir'])
            && empty($pdf_cache)
            && isset($options['language'])
        ) {
            try {
                $this->Quotations->writeCache(
                    implode('_', $quotation_ids),
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
     * Generates a PDF document for the given quotations on a specific quotation template
     *
     * @param string $language The language for all quotations in the document
     * @param bool $include_address True to include the address on the quotation
     * @param array $quotations A numerically indexed array of quotation objects
     * @return InvoiceTemplates The document of the quotations
     */
    private function buildDocument($language, $include_address, $quotations = null)
    {
        if (isset($this->CurrencyFormat)) {
            $this->CurrencyFormat->setCompany($company->id ?? null);
        }

        if (is_null($quotations)) {
            $quotations = $this->quotations;
        }

        try {
            $meta = [
                'paper_size' => $this->company_settings['inv_paper_size'],
                'background' => $this->company_settings['inv_background'],
                'logo' => $this->company_settings['inv_logo'],
                'company_name' => $this->company->name,
                'company_address' => $this->company->address,
                'tax_id' => $this->company_settings['tax_id'],
                'display_logo' => $this->company_settings['inv_display_logo'],
                'display_paid_watermark' => $this->company_settings['inv_display_paid_watermark'],
                'display_companyinfo' => $this->company_settings['inv_display_companyinfo'],
                'display_payments' => $this->company_settings['inv_display_payments'],
                'display_due_date_draft' => $this->company_settings['inv_display_due_date_draft'],
                'display_due_date_proforma' => $this->company_settings['inv_display_due_date_proforma'],
                'display_due_date_inv' => $this->company_settings['inv_display_due_date_inv'],
                'settings' => $this->company_settings,
                'language' => $language,
                'quotation' => true
            ];

            $document = $this->InvoiceTemplates->create($this->company_settings['inv_template']);
            if (!$document->supportsQuotes()) {
                $document = $this->InvoiceTemplates->create('default_invoice');
            }
            $document->setMeta($meta);
            $document->setCurrency($this->CurrencyFormat);
            $document->setDate($this->Date);
            $document->setMimeType($this->company_settings['inv_mimetype']);
            $document->includeAddress($include_address);
            $document->makeDocument($quotations);
        } catch (Throwable $e) {
            $this->Input->setErrors(['InvoiceTemplates' => ['create' => $e->getMessage()]]);
        }

        return $document ?? null;
    }
}
