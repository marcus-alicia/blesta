<?php
/**
 * Generic WHMCS Migrator
 *
 * @package blesta
 * @subpackage blesta.plugins.import_manager.components.migrators.whmcs
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class WhmcsMigrator extends Migrator
{
    /**
     * @var array An array of settings
     */
    protected $settings;
    /**
     * @var string Current path
     */
    protected $path;
    /**
     * @var bool True to fetch all records instead of looping through PDOStatement
     */
    protected $fetchall = false;
    /**
     * @var string The default country
     */
    protected $default_country = 'US';
    /**
     * @var string The default state
     */
    protected $default_state = 'TX';
    /**
     * @var string The default first name
     */
    protected $default_firstname = 'unknown';
    /**
     * @var string The default last name
     */
    protected $default_lastname = 'unknown';
    /**
     * @var string The default client group name
     */
    protected $default_client_group_name = 'unknown';
    /**
     * @var array An array of credits
     */
    protected $credits = [];

    /**
     * Runs the import, sets any Input errors encountered
     */
    public function import()
    {
        Loader::loadModels($this, ['Companies']);
        Loader::loadComponents($this, ['DataStructure']);

        Configure::set('Whmcs.import_fetchall', false);

        if (Configure::get('Whmcs.import_fetchall')) {
            $this->fetchall = true;
            ini_set('memory_limit', '512M');
        }

        $actions = [
            'importStaff', // works
            'importClients', // works
            'importContacts', // works
            'importTaxes', // works
            'importCurrencies', // works
            'importInvoices', // works
            'importTransactions', // works
            'importPackages', // works
            'importPackageOptions', // works
            'importServices', // works
            'importDomains', // works
            'importSupportDepartments', // works
            'importSupportTickets', // works
            'importAffiliates', // works
            'importMisc' // works
        ];

        $errors = [];
        $this->startTimer('total time');
        $this->decrypt_count = 0;
        $this->startTimer('decrypt');
        $this->pauseTimer('decrypt');
        foreach ($actions as $action) {
            try {
                // Only import packages if no mappings exist
                if ($action == 'importPackages' && isset($this->mappings['packages'])) {
                    continue;
                }

                $this->debug($action);
                $this->debug('-----------------');
                $this->startTimer($action);
                $this->{$action}();
                $this->endTimer($action);
                $this->debug("-----------------\n");
            } catch (Throwable $e) {
                $errors[] = $action . ': ' . $e->getMessage() . ' on line ' . $e->getLine();
                $this->logException($e);
            }
        }

        if (!empty($errors)) {
            array_unshift($errors, Language::_('Whmcs5_2.!error.import', true));
            $this->Input->setErrors(['error' => $errors]);
        }
        $this->debug('decrypted ' . $this->decrypt_count . " values using WHMCS' custom algorithm");
        $this->endTimer('decrypt');
        $this->endTimer('total time');

        if ($this->enable_debug) {
            $this->debug(print_r($this->Input->errors(), true));
            exit;
        }
    }

    /**
     * Import staff
     */
    protected function importStaff()
    {
        Loader::loadModels($this, ['StaffGroups']);
        Loader::loadModels($this, ['Users']);
        $this->loadModel('WhmcsAdmins');

        // Create "Support" staff group (no permissions)
        $staff_group = [
            'company_id' => Configure::get('Blesta.company_id'),
            'name' => 'Support',
            'session_lock' => 0,
            'permission_group' => [],
            'permission' => []
        ];
        $this->StaffGroups->add($staff_group);

        $staff_groups = $this->StaffGroups->getAll(Configure::get('Blesta.company_id'));

        $groups = [];
        foreach ($staff_groups as $group) {
            if ($group->name == 'Administrators') {
                $groups[0] = $group->id;
                $groups[1] = $group->id;
            } elseif ($group->name == 'Billing') {
                $groups[2] = $group->id;
            } elseif ($group->name == 'Support') {
                $groups[3] = $group->id;
            }
        }

        $admins = $this->fetchall ? $this->WhmcsAdmins->get()->fetchAll() : $this->WhmcsAdmins->get();
        foreach ($admins as $admin) {
            $this->Users->begin();

            // Set aside assigned support departments
            $this->mappings['admin_departs'][$admin->id] = $admin->supportdepts;

            try {
                $user_id = $this->createUser([
                    'username' => $this->decode($admin->username),
                    'password' => substr($admin->password, 0, 64),
                    'date_added' => $this->Users->dateToUtc(date('c'))
                ]);

                $vars = [
                    'user_id' => $user_id,
                    'first_name' => $this->decode($admin->firstname),
                    'last_name' => $this->decode($admin->lastname),
                    'email' => $this->decode($admin->email),
                    'status' => $admin->disabled == '0' ? 'active' : 'inactive',
                    'groups' => isset($groups[$admin->roleid]) ? [$groups[$admin->roleid]] : null
                ];

                $staff_id = $this->addStaff($vars, $admin->id);

                if ($staff_id) {
                    $this->Users->commit();
                } else {
                    $this->Users->rollback();
                }
            } catch (Throwable $e) {
                $this->logException($e);
                $this->Users->rollback();
            }
        }
        unset($admins);
    }

    /**
     * Import clients
     */
    protected function importClients()
    {
        Loader::loadModels($this, ['Accounts', 'Clients', 'ClientGroups']);
        $this->loadModel('WhmcsClients');

        // Initialize crypto (AES in ECB)
        Loader::loadComponents($this, ['Security']);
        $aes = $this->Security->create('Crypt', 'AES', [1]); // 1 = CRYPT_AES_MODE_ECB
        $aes->disablePadding();

        // Set default client group
        $client_groups = $this->ClientGroups->getAll(Configure::get('Blesta.company_id'));
        $this->mappings['client_groups'][0] = $client_groups[0]->id;

        // Import client groups
        $groups = $this->fetchall ? $this->WhmcsClients->getGroups()->fetchAll() : $this->WhmcsClients->getGroups();
        foreach ($groups as $group) {
            $group_id = $this->ClientGroups->add([
                'name' => $group->groupname == '' ? $this->default_client_group_name : $this->decode($group->groupname),
                'company_id' => Configure::get('Blesta.company_id'),
                'color' => str_replace('#', '', $group->groupcolour)
            ]);

            $this->mappings['client_groups'][$group->id] = $group_id;
        }
        unset($groups);

        // Import clients
        $clients = $this->fetchall ? $this->WhmcsClients->get()->fetchAll() : $this->WhmcsClients->get();
        $this->local->begin();
        foreach ($clients as $client) {
            // Create user
            $user_id = null;
            try {
                $user_id = $this->createUser([
                    'username' => $this->decode($client->email),
                    'password' => substr($client->password, 0, 64),
                    'date_added' => $this->getValidDate($client->datecreated)
                ]);
            } catch (Throwable $e) {
                $this->logException($e);
            }
            if (!$user_id) {
                continue;
            }

            // Create client
            $vars = [
                'id_format' => '{num}',
                'id_value' => $client->id,
                'user_id' => $user_id,
                'client_group_id' => $this->mappings['client_groups'][$client->groupid],
                'status' => strtolower($client->status) == 'closed' ? 'inactive' : 'active'
            ];
            $this->local->insert('clients', $vars);
            $client_id = $this->local->lastInsertId();

            $this->mappings['clients'][$client->id] = $client_id;

            // Create primary contact
            try {
                $vars = [
                    'client_id' => $client_id,
                    'contact_type' => 'primary',
                    'first_name' => $this->decode(
                        trim($client->firstname) != '' ? $client->firstname : $this->default_firstname
                    ),
                    'last_name' => $this->decode(
                        trim($client->lastname) != '' ? $client->lastname : $this->default_lastname
                    ),
                    'company' => $this->decode($client->companyname != '' ? $client->companyname : null),
                    'email' => $this->decode($client->email),
                    'address1' => $this->decode($client->address1),
                    'address2' => $this->decode($client->address2 != '' ? $client->address2 : null),
                    'city' => $this->decode($client->city),
                    'state' => $this->getValidState(
                        $client->country,
                        $this->decode($client->state != '' ? $client->state : null)
                    ),
                    'zip' => $this->decode($client->postcode != '' ? $client->postcode : null),
                    'country' => $client->country != '' ? $client->country : $this->default_country,
                    'date_added' => $this->getValidDate($client->datecreated)
                ];
                $this->local->insert('contacts', $vars);
                $contact_id = $this->local->lastInsertId();
                $this->mappings['primary_contacts'][$client->id] = $contact_id;
            } catch (Throwable $e) {
                $this->logException($e);
                continue;
            }

            // Save client settings
            $settings = [
                'autodebit' => $client->disableautocc == 'on' ? 'false' : 'true',
                'autosuspend' => 'true',
                'default_currency' => $client->currency_code,
                'inv_address_to' => $contact_id,
                'inv_method' => 'email',
                'language' => 'en_us',
                'tax_exempt' => $client->taxexempt == 'on' ? 'true' : 'false',
                'tax_id' => null,
                'username_type' => 'email'
            ];
            $this->Clients->setSettings($client_id, $settings);

            // Add contact phone number
            if ($client->phonenumber != '') {
                $vars = [
                    'contact_id' => $contact_id,
                    'number' => $this->decode($client->phonenumber),
                    'type' => 'phone',
                    'location' => 'home'
                ];
                $this->local->insert('contact_numbers', $vars);
            }

            $aes->setKey($this->mysqlAesKey(md5($this->settings['key'] . $client->id)));

            if ($client->cardnum != '') {
                $client->cardnum = $aes->decrypt($client->cardnum);
            }
            if ($client->expdate != '') {
                $client->expdate = $aes->decrypt($client->expdate);
            }
            if ($client->bankacct != '') {
                $client->bankacct = $aes->decrypt($client->bankacct);
            }
            if ($client->bankcode != '') {
                $client->bankcode = $aes->decrypt($client->bankcode);
            }

            // Add the payment account
            if ($client->cardnum != '') {
                $vars = [
                    'contact_id' => $this->mappings['primary_contacts'][$client->id],
                    'first_name' => $this->decode(
                        trim($client->firstname) != '' ? $client->firstname : $this->default_firstname
                    ),
                    'last_name' => $this->decode(
                        trim($client->lastname) != '' ? $client->lastname : $this->default_lastname
                    ),
                    'address1' => $this->decode($client->address1 != '' ? $client->address1 : null),
                    'address2' => $this->decode($client->address2 != '' ? $client->address2 : null),
                    'city' => $this->decode($client->city != '' ? $client->city : null),
                    'state' => $this->getValidState(
                        $client->country,
                        $this->decode($client->state != '' ? $client->state : null)
                    ),
                    'zip' => $this->decode($client->postcode != '' ? $client->postcode : null),
                    'country' => $client->country != '' ? $client->country : $this->default_country,
                    'number' => $client->cardnum,
                    'expiration' => '20' . substr($client->expdate, 2, 2) . substr($client->expdate, 0, 2)
                ];

                $account_id = $this->Accounts->addCc($vars);

                // Set account for autodebit
                if ($account_id) {
                    try {
                        $vars = [
                            'client_id' => $this->mappings['clients'][$client->id],
                            'account_id' => $account_id,
                            'type' => 'cc'
                        ];
                        $this->local->insert('client_account', $vars);
                    } catch (Throwable $e) {
                        $this->local->reset();
                        // Skip duplicated entry
                        continue;
                    }
                }
            }
        }
        $this->local->commit();
        unset($clients);

        // Import custom client fields
        $custom_fields = $this->WhmcsClients->getCustomFields()->fetchAll();
        $this->local->begin();
        foreach ($custom_fields as $custom_field) {
            // Add each field to each client group
            foreach ($this->mappings['client_groups'] as $remote_group_id => $group_id) {
                $vars = [
                    'client_group_id' => $group_id,
                    'name' => $this->decode($custom_field->fieldname),
                    'type' => $this->getFieldType($this->decode($custom_field->fieldtype)),
                    'values' => $this->getFieldValues($this->decode($custom_field->fieldoptions)),
                    'regex' => $this->decode($custom_field->regexpr != '' ? $custom_field->regexpr : null),
                    'show_client' => $custom_field->adminonly == 'on' ? '0' : '1'
                ];
                $this->local->insert('client_fields', $vars);
                $this->mappings['client_fields'][$custom_field->id][$remote_group_id] = $this->local->lastInsertId();
            }

            // Insert custom client values for this field
            $custom_values = $this->fetchall
                ? $this->WhmcsClients->getCustomFieldValues($custom_field->id)->fetchAll()
                : $this->WhmcsClients->getCustomFieldValues($custom_field->id);
            foreach ($custom_values as $custom_value) {
                if (!isset($this->mappings['clients'][$custom_value->relid])) {
                    continue;
                }

                $vars = [
                    'client_field_id' => $this->mappings['client_fields'][$custom_field->id][$custom_value->groupid],
                    'client_id' => $this->mappings['clients'][$custom_value->relid],
                    'value' => $this->decode($custom_value->value)
                ];
                $this->local->duplicate('value', '=', $vars['value'])->insert('client_values', $vars);
            }
            unset($custom_values);
        }
        $this->local->commit();

        // Import client notes
        $notes = $this->fetchall ? $this->WhmcsClients->getNotes()->fetchAll() : $this->WhmcsClients->getNotes();
        $this->local->begin();
        foreach ($notes as $note) {
            if (!isset($this->mappings['clients'][$note->userid])) {
                continue;
            }

            $note->note = $this->decode($note->note);
            $title = wordwrap($note->note, 32, "\n", true);
            if (strpos($title, "\n") > 0) {
                $title = substr($title, 0, strpos($title, "\n"));
            }

            $vars = [
                'client_id' => $this->mappings['clients'][$note->userid],
                'staff_id' => isset($this->mappings['staff'][$note->adminid])
                    ? $this->mappings['staff'][$note->adminid]
                    : 0,
                'title' => $title,
                'description' => trim($title) == trim($note->note) ? null : $note->note,
                'stickied' => $note->sticky ? 1 : 0,
                'date_added' => $this->getValidDate($note->created),
                'date_updated' => $this->getValidDate($note->modified)
            ];
            $this->local->insert('client_notes', $vars);
        }
        $this->local->commit();
        unset($notes);
    }

    /**
     * Import contacts
     */
    protected function importContacts()
    {
        $this->loadModel('WhmcsContacts');

        $contacts = $this->fetchall ? $this->WhmcsContacts->get()->fetchAll() : $this->WhmcsContacts->get();
        $this->local->begin();
        foreach ($contacts as $contact) {
            if (!isset($this->mappings['clients'][$contact->userid])) {
                continue;
            }
            $vars = [
                'client_id' => $this->mappings['clients'][$contact->userid],
                'contact_type' => 'billing',
                'first_name' => $this->decode($contact->firstname),
                'last_name' => $this->decode($contact->lastname),
                'company' => $this->decode($contact->companyname != '' ? $contact->companyname : null),
                'email' => $this->decode($contact->email),
                'address1' => $this->decode($contact->address1 != '' ? $contact->address1 : null),
                'address2' => $this->decode($contact->address2 != '' ? $contact->address2 : null),
                'city' => $this->decode($contact->city != '' ? $contact->city : null),
                'state' => $this->getValidState(
                    $contact->country,
                    $this->decode($contact->state != '' ? $contact->state : null)
                ),
                'zip' => $this->decode($contact->postcode != '' ? $contact->postcode : null),
                'country' => $contact->country != '' ? $contact->country : $this->default_country,
                'date_added' => $this->Companies->dateToUtc(date('c'))
            ];
            $this->local->insert('contacts', $vars);
            $contact_id = $this->local->lastInsertId();

            $this->mappings['contacts'][$contact->id] = $contact_id;

            // Add contact phone number
            if ($contact->phonenumber != '') {
                $vars = [
                    'contact_id' => $contact_id,
                    'number' => $this->decode($contact->phonenumber),
                    'type' => 'phone',
                    'location' => 'home'
                ];
                $this->local->insert('contact_numbers', $vars);
            }
        }
        $this->local->commit();
        unset($contacts);
    }

    /**
     * Import taxes
     */
    protected function importTaxes()
    {
        $this->loadModel('WhmcsTaxes');

        $taxes = $this->fetchall ? $this->WhmcsTaxes->get()->fetchAll() : $this->WhmcsTaxes->get();
        $this->local->begin();
        foreach ($taxes as $tax) {
            $state = $this->local->select()->from('states')->
                where('country_alpha2', '=', $tax->country)->
                where('name', '=', $this->getValidState($tax->country, $tax->state))->fetch();

            $vars = [
                'company_id' => Configure::get('Blesta.company_id'),
                'level' => $tax->level,
                'name' => $this->decode($tax->name),
                'state' => $state ? $state->code : null,
                'country' => $tax->country != '' ? $tax->country : null,
                'amount' => $tax->taxrate
            ];
            $this->local->insert('taxes', $vars);
            $tax_id = $this->local->lastInsertId();

            $this->mappings['taxes'][$tax->id] = $tax_id;
        }
        $this->local->commit();
        unset($taxes);
    }

    /**
     * Import currencies
     */
    protected function importCurrencies()
    {
        $this->loadModel('WhmcsCurrencies');

        $currencies = $this->fetchall ? $this->WhmcsCurrencies->get()->fetchAll() : $this->WhmcsCurrencies->get();
        foreach ($currencies as $currency) {
            $vars = [
                'code' => $currency->code,
                'company_id' => Configure::get('Blesta.company_id'),
                'format' => $this->getCurrencyFormat((int)$currency->format),
                'prefix' => $this->decode($currency->prefix != '' ? $currency->prefix : null),
                'suffix' => $this->decode($currency->suffix != '' ? $currency->suffix : null),
                'exchange_rate' => $currency->rate,
                'exchange_updated' => null
            ];
            $this->local->
                duplicate('format', '=', $vars['format'])->
                duplicate('prefix', '=', $vars['prefix'])->
                duplicate('suffix', '=', $vars['suffix'])->
                duplicate('exchange_rate', '=', $vars['exchange_rate'])->
                insert('currencies', $vars);

            // Set default currency
            if ($currency->default == '1') {
                $this->Companies->setSetting(Configure::get('Blesta.company_id'), 'default_currency', $currency->code);
            }
        }
        unset($currencies);
    }

    /**
     * Import invoices
     */
    protected function importInvoices()
    {
        $this->loadModel('WhmcsConfiguration');
        $this->loadModel('WhmcsInvoices');
        Loader::loadModels($this, ['Invoices']);

        $cascade_tax = false;
        // Get compound tax setting
        $cascade = $this->WhmcsConfiguration->get('TaxL2Compound')->fetch();
        if ($cascade && $cascade->value == 'on') {
            $cascade_tax = true;
        }

        $invoices = $this->fetchall ? $this->WhmcsInvoices->get()->fetchAll() : $this->WhmcsInvoices->get();
        $this->local->begin();
        foreach ($invoices as $invoice) {
            if (!isset($this->mappings['clients'][$invoice->userid])) {
                continue;
            }

            // Get tax rules
            $level1 = $this->getTaxRule(1, $invoice->taxrate);
            $level2 = $this->getTaxRule(2, $invoice->taxrate2);

            $status = 'active';
            switch (strtolower($invoice->status)) {
                case 'refunded':
                case 'cancelled':
                    $status = 'void';
                    break;
                default:
                    $status = 'active';
                    break;
            }

            $vars = [
                'id_format' => $this->decode($invoice->invoicenum != '' ? $invoice->invoicenum : '{num}'),
                'id_value' => $invoice->invoicenum != '' ? 0 : $invoice->id,
                'client_id' => $this->mappings['clients'][$invoice->userid],
                'date_billed' => $this->getValidDate($invoice->date),
                'date_due' => $this->getValidDate($invoice->duedate),
                'date_closed' => strtolower($invoice->status) != 'paid'
                    ? null
                    : $this->getValidDate($invoice->datepaid, 'Y-m-d H:i:s', true),
                'date_autodebit' => null,
                'status' => $status,
                'previous_due' => 0,
                'currency' => $invoice->currency,
                'note_public' => $invoice->notes,
                'note_private' => null,
            ];

            // Manually add the invoice so we can set the correct tax IDs and invoice ID
            $this->local->insert('invoices', $vars);
            $local_invoice_id = $this->local->lastInsertId();

            $this->mappings['invoices'][$invoice->id] = $local_invoice_id;
            $this->mappings['invoice_tax_rules'][$invoice->id] = [
                'level1' => $level1,
                'level2' => $level2
            ];

            if ($invoice->credit > 0) {
                $this->credits[] = [
                    'invoice_id' => $local_invoice_id,
                    'client_id' => $this->mappings['clients'][$invoice->userid],
                    'amount' => $invoice->credit,
                    'currency' => $invoice->currency,
                    'transaction_id' => 'invoice credit',
                    'transaction_type' => 'other',
                    'transaction_type_id' => $this->getTransactionTypeId('in_house_credit'),
                    'status' => 'approved',
                    'date_added' => $this->getValidDate($invoice->date, 'c')
                ];
            }
        }
        $this->local->commit();
        unset($invoices);

        // Import line items
        $lines = $this->fetchall ? $this->WhmcsInvoices->getLines()->fetchAll() : $this->WhmcsInvoices->getLines();
        $this->local->begin();
        foreach ($lines as $line) {
            if (!isset($this->mappings['invoices'][$line->invoiceid])) {
                continue;
            }

            // Import lines
            $vars = [
                'invoice_id' => $this->mappings['invoices'][$line->invoiceid],
                'service_id' => null,
                'description' => $this->decode($line->description),
                'qty' => 1,
                'amount' => $line->amount,
                'order' => 0
            ];
            $this->local->insert('invoice_lines', $vars);
            $line_id = $this->local->lastInsertId();

            // Import tax lines
            if ($line->taxed > 0) {
                if ($this->mappings['invoice_tax_rules'][$line->invoiceid]['level1']) {
                    $vars = [
                        'line_id' => $line_id,
                        'tax_id' => $this->mappings['invoice_tax_rules'][$line->invoiceid]['level1']->id
                    ];
                    $this->local->insert('invoice_line_taxes', $vars);
                }

                if ($this->mappings['invoice_tax_rules'][$line->invoiceid]['level2']) {
                    $vars = [
                        'line_id' => $line_id,
                        'tax_id' => $this->mappings['invoice_tax_rules'][$line->invoiceid]['level2']->id,
                        'cascade' => $cascade_tax ? 1 : 0
                    ];
                    $this->local->insert('invoice_line_taxes', $vars);
                }
            }
        }
        $this->local->commit();
        unset($lines);

        // Update totals
        if (isset($this->mappings['invoices'])) {
            foreach ($this->mappings['invoices'] as $remote_invoice_id => $local_invoice_id) {
                $totals = $this->getInvoiceTotals($local_invoice_id);

                $this->local->where('id', '=', $local_invoice_id)
                    ->update(
                        'invoices',
                        ['subtotal' => $totals['subtotal'], 'total' => $totals['total'], 'paid' => $totals['paid']]
                    );
            }
        }

        $periods = [
            'Days' => 'day',
            'Weeks' => 'week',
            'Months' => 'month',
            'Years' => 'year'
        ];

        // Import recurring invoices
        $lines = $this->fetchall
            ? $this->WhmcsInvoices->getRecurringLines()->fetchAll()
            : $this->WhmcsInvoices->getRecurringLines();
        $this->local->begin();
        foreach ($lines as $line) {
            if (!isset($periods[$line->recurcycle]) || !isset($this->mappings['clients'][$invoice->userid])) {
                continue;
            }

            $vars = [
                'client_id' => $this->mappings['clients'][$line->userid],
                'term' => $line->recur,
                'period' => $periods[$line->recurcycle],
                'duration' => $line->recurfor > 0 ? $line->recurfor : null,
                'date_renews' => $this->getValidDate($line->duedate, 'c'),
                'currency' => $line->currency,
                'lines' => [
                    [
                        'description' => $this->decode($line->description),
                        'qty' => 1,
                        'amount' => $line->amount,
                        'tax' => 0
                    ]
                ],
                'delivery' => ['email']
            ];

            $recurring_id = $this->Invoices->addRecurring($vars);
            if ($recurring_id) {
                $this->mappings['recurring_invoices'][$line->id] = $recurring_id;
            }
        }
        $this->local->commit();
        unset($lines);

        if (isset($this->mappings['recurring_invoices'])) {
            // Record each recurring invoice instance
            $this->local->begin();
            foreach ($this->mappings['recurring_invoices'] as $remote_id => $recurring_id) {
                $lines = $this->fetchall
                    ? $this->WhmcsInvoices->getRecurInstances($remote_id)->fetchAll()
                    : $this->WhmcsInvoices->getRecurInstances($remote_id);
                foreach ($lines as $line) {
                    if (!isset($this->mappings['invoices'][$line->invoiceid])) {
                        continue;
                    }

                    $vars = [
                        'invoice_recur_id' => $recurring_id,
                        'invoice_id' => $this->mappings['invoices'][$line->invoiceid]
                    ];
                    $this->local->
                        duplicate('invoice_recur_id', '=', $recurring_id)->
                        insert('invoices_recur_created', $vars);
                }
                unset($lines);
            }
            $this->local->commit();
        }
    }

    /**
     * Retrieves the subtotal, total, and paid amounts set for the given invoice
     *
     * @param int $invoice_id The ID of the invoice whose totals to fetch
     * @return array An array including:
     *  - subtotal The invoice subtotal
     *  - total The invoice total
     *  - paid The amount paid toward this invoice
     */
    private function getInvoiceTotals($invoice_id)
    {
        Loader::loadModels($this, ['Invoices']);

        $total = [
            'subtotal' => 0,
            'total' => 0,
            'paid' => $this->Invoices->getPaid($invoice_id)
        ];

        $presenter = $this->Invoices->getPresenter($invoice_id);
        if ($presenter) {
            $totals = $presenter->totals();
            $total['subtotal'] = $totals->subtotal;
            $total['total'] = $totals->total;
        }

        return $total;
    }

    /**
     * Import transactions
     */
    protected function importTransactions()
    {
        $this->loadModel('WhmcsAccounts');
        $this->loadModel('WhmcsCurrencies');
        Loader::loadModels($this, ['Invoices']);

        $default_currency = $this->WhmcsCurrencies->getDefaultCode();
        $invoice_ids = [];

        // Add invoice credits
        $this->local->begin();
        foreach ($this->credits as $credit) {
            $transaction_id = $this->addTransaction($credit, null);
            $vars = [
                'date' => $credit['date_added'],
                'amounts' => [
                    [
                        'invoice_id' => $credit['invoice_id'],
                        'amount' => $credit['amount'],
                    ]
                ]
            ];
            $this->Transactions->apply($transaction_id, $vars);

            if (!in_array($credit['invoice_id'], $invoice_ids)) {
                $invoice_ids[] = $credit['invoice_id'];
            }
        }
        $this->local->commit();
        unset($this->credits);

        $transactions = $this->fetchall ? $this->WhmcsAccounts->get(true)->fetchAll() : $this->WhmcsAccounts->get(true);
        $this->local->begin();
        foreach ($transactions as $transaction) {
            if (!isset($this->mappings['clients'][$transaction->userid])) {
                continue;
            }

            $currency = $default_currency;
            if ($transaction->trans_currency != '') {
                $currency = $transaction->trans_currency;
            } elseif ($transaction->client_currency != '') {
                $currency = $transaction->client_currency;
            }

            // Only add income transactions
            if ($transaction->amountin > 0) {
                $status = ($transaction->refund > 0 ? 'refunded' : 'approved');
                $vars = [
                    'client_id' => $this->mappings['clients'][$transaction->userid],
                    'amount' => $transaction->amountin,
                    'currency' => $currency,
                    'transaction_id' => $transaction->transid,
                    'status' => $status,
                    'date_added' => $this->getValidDate($transaction->date, 'c')
                ];
                $transaction_id = $this->addTransaction($vars, $transaction->id);

                // If the transactions was refunded add a new transaction for the difference
                if ($status == 'refunded' && $transaction->refund < $transaction->amountin) {
                    $vars = [
                        'client_id' => $this->mappings['clients'][$transaction->userid],
                        'amount' => $transaction->amountin - $transaction->refund,
                        'currency' => $currency,
                        'transaction_id' => $transaction->transid,
                        'status' => 'approved',
                        'date_added' => $this->getValidDate($transaction->date, 'c')
                    ];
                    $transaction_id = $this->addTransaction($vars, $transaction->id);
                }
            }

            // Apply payment
            if (isset($this->mappings['invoices'][$transaction->invoiceid]) && $transaction->amountin > 0) {
                $vars = [
                    'date' => $this->getValidDate($transaction->date, 'c'),
                    'amounts' => [
                        [
                            'invoice_id' => $this->mappings['invoices'][$transaction->invoiceid],
                            'amount' => $transaction->amountin - ($transaction->refund > 0 ? $transaction->refund : 0),
                        ]
                    ]
                ];
                $this->Transactions->apply($transaction_id, $vars);

                if (!in_array($this->mappings['invoices'][$transaction->invoiceid], $invoice_ids)) {
                    $invoice_ids[] = $this->mappings['invoices'][$transaction->invoiceid];
                }
            }
        }
        $this->local->commit();
        unset($transactions);

        // Add client credits
        $credits = $this->fetchall
            ? $this->WhmcsAccounts->getOpenCredits()->fetchAll()
            : $this->WhmcsAccounts->getOpenCredits();
        $this->local->begin();
        foreach ($credits as $credit) {
            if (!isset($this->mappings['clients'][$credit->userid])) {
                continue;
            }

            $vars = [
                'client_id' => $this->mappings['clients'][$credit->userid],
                'amount' => $credit->credit,
                'currency' => $credit->currency,
                'type' => 'other',
                'transaction_type_id' => $this->getTransactionTypeId('in_house_credit'),
                'transaction_id' => null,
                'status' => 'approved',
                'date_added' => $this->Companies->dateToUtc(date('c'))
            ];
            $transaction_id = $this->addTransaction($vars, $transaction->id);
        }
        $this->local->commit();
        unset($credits);

        // Update paid totals
        $this->local->begin();
        foreach ($invoice_ids as $invoice_id) {
            // Update paid total
            $paid = $this->Invoices->getPaid($invoice_id);
            $this->local->where('id', '=', $invoice_id)->
                update('invoices', ['paid' => $paid]);
        }
        $this->local->commit();

        $this->balanceClientCredit();
    }

    /**
     * Verifies that total transaction credit for a each client matches credit
     * set in WHMCS
     */
    protected function balanceClientCredit()
    {
        if ($this->settings['balance_credit'] != 'true') {
            return;
        }

        $this->loadModel('WhmcsAccounts');
        if (!isset($this->Transactions)) {
            Loader::loadModels($this, ['Transactions']);
        }
        if (!isset($this->Invoices)) {
            Loader::loadModels($this, ['Invoices']);
        }

        // Fetch all client credit values
        $credits = $this->WhmcsAccounts->getCredits();
        $date = date('c');
        foreach ($credits as $credit) {
            if (!isset($this->mappings['clients'][$credit->userid])) {
                continue;
            }

            $client_id = $this->mappings['clients'][$credit->userid];
            $total_credit = $this->Transactions->getTotalCredit($client_id, $credit->currency);
            $credit_diff = round($total_credit-$credit->credit, 4);

            // We have excess credit, so consume it
            if ($credit_diff > 0) {
                // Create an invoice to balance credits
                $vars = [
                    'client_id' => $client_id,
                    'currency' => $credit->currency,
                    'date_billed' => $date,
                    'date_due' => $date,
                    'status' => 'active',
                    'lines' => [
                        [
                            'description' => 'Automatic credit balance adjustment.',
                            'qty' => 1,
                            'amount' => $credit_diff
                        ]
                    ]
                ];
                $invoice_id = $this->Invoices->add($vars);

                // Consume the credit
                $amounts = [
                    [
                        'invoice_id' => $invoice_id,
                        'amount' => $credit_diff
                    ]
                ];
                $this->Transactions->applyFromCredits($client_id, $credit->currency, $amounts);
            } elseif ($credit_diff < 0) {
                // Create transaction to hold the credit diff
                $vars = [
                    'client_id' => $client_id,
                    'amount' => -1*$credit_diff,
                    'currency' => $credit->currency,
                    'type' => 'other',
                    'transaction_type_id' => $this->getTransactionTypeId('in_house_credit'),
                    'transaction_id' => null,
                    'status' => 'approved',
                    'date_added' => $this->Companies->dateToUtc($date)
                ];
                $transaction_id = $this->addTransaction($vars, $transaction->id);
            }
        }
    }

    /**
     * Import modules
     */
    protected function importModules()
    {
        $this->loadModel('WhmcsProducts');

        // Import generic server module required for all package assigned to no module
        $this->installModuleRow(['id' => 'generic_server', 'type' => 'generic_server']);

        // Import servers
        $rows = $this->fetchall ? $this->WhmcsProducts->getServers()->fetchAll() : $this->WhmcsProducts->getServers();
        foreach ($rows as $row) {
            $module_row = (array)$row;
            foreach ($module_row as $key => &$value) {
                if ($key == 'password') {
                    $value = $this->decryptData($value);
                }

                $value = $this->decode($value);
            }

            $this->installModuleRow($module_row);
        }
        unset($rows);

        // Import generic registrar module required for all domains assigned to no module
        $this->installModuleRow(['id' => 'generic_registrar', 'type' => 'generic_registrar']);

        // Import registrars
        foreach ($this->WhmcsProducts->getReigstrars() as $registrar) {
            $row = $this->WhmcsProducts->getRegistrarFields($registrar);
            foreach ($row as &$value) {
                $value = $this->decode($this->decryptData($value));
            }

            $row['id'] = $registrar;
            $row['type'] = $registrar;

            $this->installModuleRow($row, 'registrar');
        }
    }

    /**
     * Import packages
     */
    protected function importPackages()
    {
        $this->importModules();

        $this->loadModel('WhmcsProducts');
        $this->loadModel('WhmcsConfiguration');
        Loader::loadModels($this, ['PackageGroups', 'PluginManager']);

        // Add imported package group
        $vars = [
            'company_id' => Configure::get('Blesta.company_id'),
            'names' => [['lang' => 'en_us', 'name' => 'Imported']],
            'type' => 'standard'
        ];
        $package_group_id = $this->PackageGroups->add($vars);
        $this->mappings['package_group_id'] = $package_group_id;

        $products = $this->WhmcsProducts->get()->fetchAll();
        $i=1;
        $this->local->begin();
        foreach ($products as $product) {
            if (!isset($this->mappings['modules'][$product->servertype])) {
                $product->servertype = 'generic_server';
            }

            $pricing = $this->WhmcsProducts->getPricing($product->id);
            $mapping = $this->getModuleMapping($product->servertype);

            // Add package
            $vars = [
                'id_format' => '{num}',
                'id_value' => $product->id,
                'module_id' => $this->mappings['modules'][$product->servertype],
                'qty' => $product->stockcontrol == 'on' ? $product->qty : null,
                'module_row' => 0, // WHMCS doesn't associate a service with a product
                'module_group' => null,
                'taxable' => $product->tax,
                'status' => $product->retired == '1' ? 'inactive' : 'active',
                'company_id' => Configure::get('Blesta.company_id')
            ];
            $this->local->insert('packages', $vars);
            $this->mappings['packages'][$product->id] = $this->local->lastInsertId();

            // Assign group
            $this->local->insert(
                'package_group',
                ['package_id' => $this->mappings['packages'][$product->id], 'package_group_id' => $package_group_id]
            );

            // Add package pricing
            $this->addPackagePricing($pricing, $this->mappings['packages'][$product->id]);

            // Import package meta
            $this->addPackageMeta((array)$product, $mapping);

            // Import package name
            $this->local->insert(
                'package_names',
                [
                    'package_id' => $this->mappings['packages'][$product->id],
                    'lang' => 'en_us',
                    'name' => $this->decode($product->name)
                ]
            );

            // Import package description
            $this->local->insert(
                'package_descriptions',
                [
                    'package_id' => $this->mappings['packages'][$product->id],
                    'lang' => 'en_us',
                    'text' => strip_tags($this->decode($product->description)),
                    'html' => $this->decode($product->description)
                ]
            );
            $i = max(++$i, $product->id);
        }
        $this->local->commit();

        // Import TLDs
        $taxable = 0;
        $tax_domains = $this->WhmcsConfiguration->get('TaxDomains')->fetch();
        if ($tax_domains) {
            $taxable = $tax_domains->value == 'on' ? 1 : 0;
        }

        // Get TLDs package group ID, if the domain manager it's installed
        if ($this->PluginManager->isInstalled('domains', Configure::get('Blesta.company_id'))) {
            Loader::loadModels($this, ['Domains.DomainsTlds']);

            $company_settings = $this->DomainsTlds->getDomainsCompanySettings(Configure::get('Blesta.company_id'));
            $package_group_id = $company_settings['domains_package_group'] ?? $package_group_id;
        }

        $tlds = $this->WhmcsProducts->getTlds();
        $this->local->begin();
        foreach ($tlds as $tld) {
            $pricing = $this->WhmcsProducts->getTldPricing($tld->extension);
            $registrar = trim($tld->autoreg);
            if ($registrar == '') {
                continue;
            }

            $mapping = $this->getModuleMapping($registrar, 'registrar');

            $vars = [
                'id_format' => '{num}',
                'id_value' => max($tld->id, $i++),
                'module_id' => $this->mappings['modules'][$registrar],
                'qty' => null,
                'module_row' => !isset($this->mappings['module_rows'][$registrar][$registrar])
                    ? 0
                    : $this->mappings['module_rows'][$registrar][$registrar],
                'module_group' => null,
                'taxable' => $taxable,
                'status' => 'active',
                'company_id' => Configure::get('Blesta.company_id')
            ];

            // Add the package
            $this->local->insert('packages', $vars);
            $this->mappings['packages'][$tld->extension . $registrar] = $this->local->lastInsertId();

            // Set the last package id for future use
            $this->mappings['tld_last_id'] = $tld->id;

            // Assign group
            $this->local->insert(
                'package_group',
                [
                    'package_id' => $this->mappings['packages'][$tld->extension . $registrar],
                    'package_group_id' => $package_group_id
                ]
            );

            // Add package pricing
            $this->addPackagePricing($pricing, $this->mappings['packages'][$tld->extension . $registrar]);

            // Import package meta
            $product = [
                'id' => $tld->extension . $registrar,
                'tlds' => [$tld->extension]
            ];
            $this->addPackageMeta($product, $mapping);

            // Import package name
            $this->local->insert(
                'package_names',
                [
                    'package_id' => $this->mappings['packages'][$tld->extension . $registrar],
                    'lang' => 'en_us',
                    'name' => 'Domain Registration (' . $tld->extension . ')'
                ]
            );

            // Import package description
            $this->local->insert(
                'package_descriptions',
                [
                    'package_id' => $this->mappings['packages'][$tld->extension . $registrar],
                    'lang' => 'en_us',
                    'text' => null,
                    'html' => null
                ]
            );
        }
        $this->local->commit();
    }

    /**
     * Import package options
     */
    protected function importPackageOptions()
    {
        $this->loadModel('WhmcsProducts');
        Loader::loadModels($this, ['PackageOptionGroups', 'PackageOptions']);

        $option_types = $this->WhmcsProducts->getConfigOptionTypes();

        $option_groups = $this->WhmcsProducts->getConfigOptionGroups();

        foreach ($option_groups as $option_group) {
            $packages = [];
            // Map WHMCS packages to packages in Blesta
            foreach ($option_group->packages as $package_id) {
                if (isset($this->mappings['packages'][$package_id])) {
                    $packages[] = $this->mappings['packages'][$package_id];
                }
            }

            $vars = [
                'company_id' => Configure::get('Blesta.company_id'),
                'name' => $this->decode($option_group->name),
                'description' => $this->decode($option_group->description),
                'packages' => $packages
            ];
            $option_group_id = $this->PackageOptionGroups->add($vars);

            // Record package group mapping
            $this->mappings['package_options_groups'][$option_group->id] = $option_group_id;

            // Import package options
            $options = $this->WhmcsProducts->getConfigOptions($option_group->id);
            foreach ($options as $option) {
                $values = [];
                foreach ($option->values as $value) {
                    $is_qty = isset($option_types[$option->optiontype])
                        && $option_types[$option->optiontype] == 'quantity';

                    $values[] = [
                        'name' => $this->decode($value->optionname),
                        'value' => $is_qty ? null : $this->decode($value->optionname),
                        'min' => $is_qty ? max(0, $option->qtyminimum) : null,
                        'max' => $is_qty && $option->qtymaximum > 0 ? max(1, $option->qtymaximum) : null,
                        'step' => $is_qty ? '1' : null,
                        'pricing' => $this->WhmcsProducts->getPricing($value->id, 'configoptions')
                    ];
                }

                // WHMCS only supports one group per option... weak!
                $groups = [$this->mappings['package_options_groups'][$option->gid]];

                $vars = [
                    'company_id' => Configure::get('Blesta.company_id'),
                    'label' => $this->decode($option->optionname),
                    'name' => $this->decode($option->optionname),
                    'type' => isset($option_types[$option->optiontype]) ? $option_types[$option->optiontype] : 'select',
                    'values' => $values,
                    'groups' => $groups
                ];
                $option_id = $this->PackageOptions->add($vars);

                // Record package option mapping
                $this->mappings['package_options'][$option->id] = $option_id;

                // Record package option value mappings
                $opt_values = $this->PackageOptions->getValues($option_id);
                foreach ($opt_values as $v => $val) {
                    $this->mappings['option_values'][$option->values[$v]->id] = $val->id;
                }
            }
        }
    }

    /**
     * Import services
     */
    protected function importServices()
    {
        $this->loadModel('WhmcsServices');
        $this->loadModel('WhmcsProducts');
        Loader::loadModels($this, ['Clients', 'Packages']);

        $servers = [];
        $rows = $this->fetchall ? $this->WhmcsProducts->getServers()->fetchAll() : $this->WhmcsProducts->getServers();
        foreach ($rows as $row) {
            $servers[$row->id] = $row;
        }
        unset($rows);

        $services = $this->fetchall ? $this->WhmcsServices->get()->fetchAll() : $this->WhmcsServices->get();
        $this->local->begin();
        foreach ($services as $service) {
            // Get service custom fields
            $custom_fields = $this->WhmcsServices->getCustomFields($service->id);
            if (!empty($custom_fields) && !is_scalar($custom_fields)) {
                $service = (object) array_merge((array) $service, (array) $custom_fields);
            }

            // If the client doesn't exist, we can't import the service
            if (!isset($this->mappings['clients'][$service->userid])) {
                continue;
            }
            // If the package doesn't exist, we can't import the service
            if (!isset($this->mappings['packages'][$service->packageid])) {
                continue;
            }

            $package = $this->Packages->get($this->mappings['packages'][$service->packageid]);

            if (!isset($this->mappings['modules'])) {
                if (!isset($this->ModuleManager)) {
                    Loader::loadModels($this, ['ModuleManager']);
                }

                $module = $this->ModuleManager->get($package->module_id, false, false);
                if ($module) {
                    $modules[$package->module_id] = $module->class;
                }
            } else {
                $modules = array_flip($this->mappings['modules']);
            }

            $mapping = $this->getModuleMapping(
                isset($modules[$package->module_id]) ? $modules[$package->module_id] : 'generic_server'
            );

            // Get currency this client is invoiced in
            $currency = $this->getCurrency($this->mappings['clients'][$service->userid]);

            if ($package->module_row > 0) {
                $module_row_id = $package->module_row;
            } else {
                if (isset($mapping['module_row_key'])
                    && isset($servers[$service->server]->{$mapping['module_row_key']})) {
                    $module_row_id = $this->getModuleRowId(
                        $package->module_id,
                        $servers[$service->server]->{$mapping['module_row_key']},
                        null
                    );
                } else {
                    $module_row_id = $this->getModuleRowId(
                        $package->module_id,
                        null,
                        isset($modules[$package->module_id]) ? $modules[$package->module_id] : null
                    );
                }
            }
            if (!$module_row_id) {
                continue;
            }

            $status = $this->getServiceStatus($service->domainstatus);
            $pricing = $this->getPricing(
                $this->WhmcsServices->getTerm($service->billingcycle),
                $package,
                $currency,
                $service->amount
            );
            $override_price = (
                ($p = number_format($pricing->price, 2, '.', '')) == number_format($service->amount, 2, '.', '')
                    ? $p
                    : null
            );
            $override_currency = ($override_price === null ? null : $currency);

            $vars = [
                'parent_service_id' => null,
                'package_group_id' => null,
                'id_format' => '{num}',
                'id_value' => $service->id,
                'pricing_id' => $pricing->id,
                'client_id' => $this->mappings['clients'][$service->userid],
                'module_row_id' => $module_row_id,
                'coupon_id' => null,
                'qty' => 1,
                'override_price' => $override_price,
                'override_currency' => $override_currency,
                'status' => $status,
                'date_added' => $this->getValidDate($service->regdate),
                'date_renews' => $this->getValidDate($service->nextinvoicedate, 'Y-m-d H:i:s', true),
                'date_last_renewed' => null,
                'date_suspended' => $status == 'suspended' ? $this->Companies->dateToUtc(date('c')) : null,
                'date_canceled' => $status == 'canceled' ? $this->Companies->dateToUtc(date('c')) : null
            ];

            $this->local->insert('services', $vars);
            $service_id = $this->local->lastInsertId();
            $this->mappings['services'][$service->id] = $service_id;

            $this->addServiceFields((array)$service, $mapping);
        }
        $this->local->commit();
        unset($services);

        $option_types = $this->WhmcsProducts->getConfigOptionTypes();

        // Import options for services
        $options = $this->WhmcsServices->getConfigOptions();
        $this->local->begin();
        foreach ($options as $option) {
            // Ensure parent service and the option value exist
            if (!isset($this->mappings['services'][$option->relid])
                || !isset($this->mappings['option_values'][$option->optionid])) {
                continue;
            }

            $currency = $this->getCurrency($this->mappings['clients'][$option->userid]);
            $value_id = $this->mappings['option_values'][$option->optionid];
            if (($pricing = $this->getOptionPricing(
                    $this->WhmcsServices->getTerm($option->billingcycle),
                    $value_id,
                    $currency
                )
            )) {
                $vars = [
                    'service_id' => $this->mappings['services'][$option->relid],
                    'option_pricing_id' => $pricing->id,
                    // option isn't a quantity type, set qty to 1
                    'qty' => $option_types[$option->optiontype] == 'quantity' ? $option->qty : 1
                ];
                $this->local->insert('service_options', $vars);
            }
        }
        $this->local->commit();
        unset($options);
    }

    /**
     * Import domains
     */
    protected function importDomains()
    {
        $this->loadModel('WhmcsServices');
        $this->loadModel('WhmcsProducts');
        Loader::loadModels($this, ['Clients', 'Packages', 'PluginManager']);

        $servers = [];
        $rows = $this->fetchall ? $this->WhmcsProducts->getServers()->fetchAll() : $this->WhmcsProducts->getServers();
        foreach ($rows as $row) {
            $servers[$row->id] = $row;
        }
        unset($rows);

        $domains = $this->fetchall
            ? $this->WhmcsServices->getDomains()->fetchAll()
            : $this->WhmcsServices->getDomains();
        $this->local->begin();
        $i = 0;
        foreach ($domains as $domain) {
            // If the client doesn't exist, we can't import the service
            if (!isset($this->mappings['clients'][$domain->userid])) {
                continue;
            }

            // If the domain does not have a registrar assigned, we will use the generic registrar
            if ($domain->registrar == '') {
                $domain->registrar = 'generic_registrar';
            }

            // Get list of supported TLDs by the registrar modules
            Configure::load($domain->registrar, COMPONENTDIR . 'modules' . DS . $domain->registrar . DS . 'config' . DS);

            $tld = $this->getTld($domain->domain, $domain->registrar);

            // If package does not exist, create a placeholder package
            if (!isset($this->mappings['packages'][$tld . $domain->registrar])) {
                $tld = strstr(ltrim($domain->domain, '.'), '.');

                // Verify if the TLD is supported by the registrar module
                $supported_tlds = Configure::get(Loader::toCamelCase($domain->registrar) . '.tlds');
                if (!empty($supported_tlds)) {
                    if (!in_array($tld, $supported_tlds)) {
                        continue;
                    }
                }

                // Verify if the registrar module exists
                if (!isset($this->mappings['module_rows'][$domain->registrar][$domain->registrar])) {
                    $domain->registrar = 'generic_registrar';
                }

                $id_value = $this->mappings['tld_last_id'] + $i++;
                $vars = [
                    'id_format' => '{num}',
                    'id_value' => $id_value,
                    'module_id' => $this->mappings['modules'][$domain->registrar] ?? $this->mappings['modules']['generic_registrar'],
                    'qty' => null,
                    'module_row' => $this->mappings['module_rows'][$domain->registrar][$domain->registrar]
                        ?? $this->mappings['module_rows']['generic_registrar']['generic_registrar']
                        ?? 0,
                    'module_group' => null,
                    'taxable' => 0,
                    'status' => 'inactive',
                    'hidden' => 1,
                    'company_id' => Configure::get('Blesta.company_id')
                ];

                // Add the package
                $this->local->insert('packages', $vars);
                $this->mappings['packages'][$tld . $domain->registrar] = $this->local->lastInsertId();
                $this->mappings['tld_last_id'] = $id_value;

                // Get package group
                $package_group_id = $this->mappings['package_group_id'] ?? null;
                if ($this->PluginManager->isInstalled('domains', Configure::get('Blesta.company_id'))) {
                    Loader::loadModels($this, ['Domains.DomainsTlds']);

                    $company_settings = $this->DomainsTlds->getDomainsCompanySettings(Configure::get('Blesta.company_id'));
                    $package_group_id = $company_settings['domains_package_group'] ?? $package_group_id;
                }

                // Assign group
                $this->local->insert(
                    'package_group',
                    [
                        'package_id' => $this->mappings['packages'][$tld . $domain->registrar],
                        'package_group_id' => $package_group_id
                    ]
                );

                // Add package pricing
                $pricing = $this->WhmcsProducts->getTldPricing($tld);
                if (empty($pricing)) {
                    $currency = $this->getCurrency($this->mappings['clients'][$domain->userid]);
                    for ($i = 1; $i <= 10; $i++) {
                        $pricing[] = ['term' => $i, 'period' => 'year', 'currency' => $currency, 'price' => 0];
                    }
                }

                $this->addPackagePricing($pricing, $this->mappings['packages'][$tld . $domain->registrar]);

                // Import package meta
                $mapping = $this->getModuleMapping($domain->registrar, 'registrar');
                $product = [
                    'id' => $tld . $domain->registrar,
                    'tlds' => [$tld]
                ];
                $this->addPackageMeta($product, $mapping);

                // Import package name
                $this->local->insert(
                    'package_names',
                    [
                        'package_id' => $this->mappings['packages'][$tld . $domain->registrar],
                        'lang' => 'en_us',
                        'name' => 'Domain Registration (' . $tld . ')'
                    ]
                );

                // Import package description
                $this->local->insert(
                    'package_descriptions',
                    [
                        'package_id' => $this->mappings['packages'][$tld . $domain->registrar],
                        'lang' => 'en_us',
                        'text' => null,
                        'html' => null
                    ]
                );
            }

            $package = $this->Packages->get($this->mappings['packages'][$tld . $domain->registrar]);
            $mapping = $this->getModuleMapping($domain->registrar, 'registrar');

            // Get currency this client is invoiced in
            $currency = $this->getCurrency($this->mappings['clients'][$domain->userid]);

            $module_row_id = $this->mappings['module_rows'][$domain->registrar][$domain->registrar];

            if (!$module_row_id) {
                continue;
            }

            $status = $this->getServiceStatus($domain->status);
            $pricing = $this->getPricing(
                $this->WhmcsServices->getTerm($domain->registrationperiod),
                $package,
                $currency,
                $domain->recurringamount
            );
            $override_price = (
                ($p = number_format($pricing->price, 2, '.', ''))
                == number_format($domain->recurringamount, 2, '.', '')
                ? $p
                : null
            );
            $override_currency = ($override_price === null ? null : $currency);

            $vars = [
                'parent_service_id' => null,
                'package_group_id' => null,
                'id_format' => '{num}',
                'id_value' => $domain->id,
                'pricing_id' => $pricing->id,
                'client_id' => $this->mappings['clients'][$domain->userid],
                'module_row_id' => $module_row_id,
                'coupon_id' => null,
                'qty' => 1,
                'override_price' => $override_price,
                'override_currency' => $override_currency,
                'status' => $status,
                'date_added' => $this->getValidDate($domain->registrationdate),
                'date_renews' => $this->getValidDate($domain->nextinvoicedate, 'Y-m-d H:i:s', true),
                'date_last_renewed' => null,
                'date_suspended' => $status == 'suspended' ? $this->Companies->dateToUtc(date('c')) : null,
                'date_canceled' => $status == 'canceled' ? $this->Companies->dateToUtc(date('c')) : null
            ];

            $this->local->insert('services', $vars);
            $service_id = $this->local->lastInsertId();
            $this->mappings['services'][$domain->id] = $service_id;

            $this->addServiceFields((array)$domain, $mapping);
        }
        $this->local->commit();
        unset($domains);
    }

    /**
     * Import support departments
     */
    protected function importSupportDepartments()
    {
        Loader::loadModels($this, ['PluginManager']);

        // Install support plugin if not already installed
        if (!$this->PluginManager->isInstalled('support_manager', Configure::get('Blesta.company_id'))) {
            $this->PluginManager->add(
                ['dir' => 'support_manager', 'company_id' => Configure::get('Blesta.company_id')]
            );
        }

        $this->loadModel('WhmcsSupportDepartments');

        $departments = $this->fetchall
            ? $this->WhmcsSupportDepartments->get()->fetchAll()
            : $this->WhmcsSupportDepartments->get();
        $this->local->begin();
        $this->mappings['support_departments'] = [];
        foreach ($departments as $department) {
            $vars = [
                'company_id' => Configure::get('Blesta.company_id'),
                'name' => $this->decode($department->name),
                'description' => $this->decode($department->description),
                'email' => $this->decode($department->email),
                'method' => !empty($department->host)
                    ? ($department->piperepliesonly == 'on' ? 'pipe' : 'pop3')
                    : 'none',
                'default_priority' => 'medium',
                'host' => !empty($department->host) ? $this->decode($department->host) : null,
                'user' => !empty($department->login) ? $this->decode($department->login) : null,
                'pass' => !empty($department->password)
                    ? $this->decode($this->decryptData($department->password))
                    : null,
                'port' => !empty($department->port) ? $department->port : null,
                'security' => 'none',
                'box_name' => null,
                'mark_messages' => 'deleted',
                'clients_only' => $department->clientsonly == 'on' ? 1 : 0,
                'status' => $department->hidden == 'on' ? 'hidden' : 'visible'
            ];
            $this->local->insert('support_departments', $vars);
            $department_id = $this->local->lastInsertId();
            $this->mappings['support_departments'][$department->id] = $department_id;
        }
        $this->local->commit();
        unset($departments);

        // Assign admins to support departments
        $this->local->begin();

        // The initial admin (ID 1) should be assigned to every department
        $this->addSupportStaff(1, $this->mappings['support_departments']);

        foreach ($this->mappings['admin_departs'] as $remote_admin_id => $departs) {
            if (!isset($this->mappings['staff'][$remote_admin_id])) {
                continue;
            }
            $local_staff_id = $this->mappings['staff'][$remote_admin_id];

            $departs = explode(',', $departs);
            $this->addSupportStaff($local_staff_id, $departs);
        }
        $this->local->commit();
    }

    /**
     * Adds a support staff member and assigns them to the given departments.  Also adds support
     *  departments as necessary
     *
     * @param int $staff_id The ID of the staff to add
     * @param array $departments The departments
     */
    private function addSupportStaff($staff_id, array $departments)
    {
        foreach ($departments as $depart_id) {
            $depart_id = trim($depart_id);
            if (isset($this->mappings['support_departments'][$depart_id])) {
                $vars = [
                    'department_id' => $this->mappings['support_departments'][$depart_id],
                    'staff_id' => $staff_id
                ];
                $this->local->
                    duplicate('staff_id', '=', $staff_id)->
                    insert('support_staff_departments', $vars);
            }
        }

        // Add schedules
        $days = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
        foreach ($days as $day) {
            $vars = [
                'staff_id' => $staff_id,
                'company_id' => Configure::get('Blesta.company_id'),
                'day' => $day,
                'start_time' => '00:00:00',
                'end_time' => '00:00:00'
            ];
            try {
                $this->local->insert('support_staff_schedules', $vars);
            } catch (Throwable $e) {
                $this->logException($e);
            }
        }

        // Add notices
        $keys = ['ticket_emails'];
        foreach ($keys as $key) {
            $vars = [
                'key' => $key,
                'company_id' => Configure::get('Blesta.company_id'),
                'staff_id' => $staff_id,
                'value' => serialize(
                    [
                        'emergency' => 'true',
                        'critical' => 'true',
                        'high' => 'true',
                        'medium' => 'true',
                        'low' => 'true'
                    ]
                )
            ];
            try {
                $this->local->insert('support_staff_settings', $vars);
            } catch (Throwable $e) {
                $this->logException($e);
            }
        }
    }

    /**
     * Import support tickets
     */
    protected function importSupportTickets()
    {
        $this->loadModel('WhmcsSupportTickets');

        $tickets = $this->fetchall ? $this->WhmcsSupportTickets->get()->fetchAll() : $this->WhmcsSupportTickets->get();
        $this->local->begin();
        foreach ($tickets as $ticket) {
            $vars = $this->formatData($ticket, 'ticket');

            $this->local->insert('support_tickets', $vars);
            $this->mappings['support_tickets'][$ticket->id] = $this->local->lastInsertId();

            // Add ticket body
            $vars = [
                'ticket_id' => $this->mappings['support_tickets'][$ticket->id],
                'staff_id' => $ticket->admin_id
                    && isset($this->mappings['staff'][$ticket->admin_id])
                    ? $this->mappings['staff'][$ticket->admin_id]
                    : null,
                'type' => 'reply',
                'details' => $this->decode($ticket->message),
                'date_added' => $this->getValidDate($ticket->date),

            ];
            $this->local->insert('support_replies', $vars);
        }
        $this->local->commit();
        unset($tickets);

        // Import ticket replies
        $replies = $this->fetchall
            ? $this->WhmcsSupportTickets->getReplies()->fetchAll()
            : $this->WhmcsSupportTickets->getReplies();
        $this->local->begin();
        foreach ($replies as $reply) {
            if (!isset($this->mappings['support_tickets'][$reply->tid])) {
                continue;
            }

            $vars = [
                'ticket_id' => $this->mappings['support_tickets'][$reply->tid],
                'staff_id' => $reply->admin_id
                    && isset($this->mappings['staff'][$reply->admin_id])
                    ? $this->mappings['staff'][$reply->admin_id]
                    : null,
                'type' => 'reply',
                'details' => $this->decode($reply->message),
                'date_added' => $this->getValidDate($reply->date),

            ];
            $this->local->insert('support_replies', $vars);
        }
        $this->local->commit();
        unset($replies);

        // Import ticket notes
        $notes = $this->fetchall
            ? $this->WhmcsSupportTickets->getNotes()->fetchAll()
            : $this->WhmcsSupportTickets->getNotes();
        $this->local->begin();
        foreach ($notes as $note) {
            if (!isset($this->mappings['support_tickets'][$note->ticketid])) {
                continue;
            }

            $vars = [
                'ticket_id' => $this->mappings['support_tickets'][$note->ticketid],
                'staff_id' => $note->admin_id
                    && isset($this->mappings['staff'][$note->admin_id])
                    ? $this->mappings['staff'][$note->admin_id]
                    : null,
                'type' => 'note',
                'details' => $this->decode($note->message),
                'date_added' => $this->getValidDate($note->date),

            ];
            $this->local->insert('support_replies', $vars);
        }
        $this->local->commit();
        unset($notes);

        // Import predefined categories
        $categories = $this->fetchall
            ? $this->WhmcsSupportTickets->getResponseCategories()->fetchAll()
            : $this->WhmcsSupportTickets->getResponseCategories();
        $this->local->begin();
        foreach ($categories as $category) {
            $vars = [
                'company_id' => Configure::get('Blesta.company_id'),
                'parent_id' => isset($this->mappings['support_response_categories'][$category->parentid])
                    ? $this->mappings['support_response_categories'][$category->parentid]
                    : null,
                'name' => $this->decode($category->name)
            ];
            $this->local->insert('support_response_categories', $vars);
            $this->mappings['support_response_categories'][$category->id] = $this->local->lastInsertId();
        }
        $this->local->commit();
        unset($categories);

        // Import predefined replies
        $responses = $this->fetchall
            ? $this->WhmcsSupportTickets->getResponses()->fetchAll()
            : $this->WhmcsSupportTickets->getResponses();
        $this->local->begin();
        foreach ($responses as $response) {
            if (!isset($this->mappings['support_response_categories'][$response->catid])) {
                continue;
            }

            $vars = [
                'category_id' => $this->mappings['support_response_categories'][$response->catid],
                'name' => $this->decode($response->name),
                'details' => $this->decode($response->reply)
            ];
            $this->local->insert('support_responses', $vars);
        }
        $this->local->commit();
        unset($responses);
    }

    /**
     * Import affiliates
     */
    protected function importAffiliates()
    {
        Loader::loadModels($this, ['Order.OrderAffiliateCompanySettings', 'Order.OrderAffiliateStatistics']);
        $this->loadModel('WhmcsAffiliates');

        // Import affiliates
        $affiliates = $this->fetchall ? $this->WhmcsAffiliates->get()->fetchAll() : $this->WhmcsAffiliates->get();
        $this->local->begin();
        foreach ($affiliates as $affiliate) {
            if (!isset($this->mappings['clients'][$affiliate->clientid])) {
                continue;
            }

            // Add affiliate
            $vars = [
                'client_id' => $this->mappings['clients'][$affiliate->clientid],
                'code' => base64_encode($this->mappings['clients'][$affiliate->clientid]),
                'status' => 'active',
                'date_added' => $this->getValidDate($affiliate->date),
                'date_updated' => $this->getValidDate($affiliate->date)
            ];
            $this->local->insert('order_affiliates', $vars);
            $this->mappings['affiliates'][$affiliate->clientid] = $this->local->lastInsertId();

            // Set affiliate settings
            $settings = [
                'commission_amount' => $affiliate->payamount,
                'commission_type' => ($affiliate->paytype == 'percentage' ? 'percentage' : 'fixed'),
                'maturity_days' => $this->OrderAffiliateCompanySettings->getSetting(
                    Configure::get('Blesta.company_id'),
                    'maturity_days'
                )->value,
                'max_withdrawal_amount' => $this->OrderAffiliateCompanySettings->getSetting(
                    Configure::get('Blesta.company_id'),
                    'max_withdrawal_amount'
                )->value,
                'min_withdrawal_amount' => $this->OrderAffiliateCompanySettings->getSetting(
                    Configure::get('Blesta.company_id'),
                    'min_withdrawal_amount'
                )->value,
                'order_frequency' => ((bool)$affiliate->onetime ? 'first' : 'any'),
                'total_available' => $affiliate->balance,
                'total_withdrawn' => $affiliate->withdrawn,
                'withdrawal_currency' => $affiliate->currency_code
            ];

            foreach ($settings as $key => $value) {
                $this->local->insert('order_affiliate_settings', [
                    'affiliate_id' => $this->mappings['affiliates'][$affiliate->clientid],
                    'key' => $key,
                    'value' => $value
                ]);
            }

            // Import statistics
            $vars = [
                'affiliate_id' => $affiliate->id,
                'visits' => $affiliate->visitors,
                'date' => $this->getValidDate($affiliate->date)
            ];
            $this->local->insert('order_affiliate_statistics', $vars);

            $sales = $this->WhmcsAffiliates->getSales($affiliate->id);
            foreach ($sales as $sale) {
                $this->OrderAffiliateStatistics->registerSale($affiliate->id, $this->getValidDate($sale->lastpaid));
            }

            // Import pending referrals
            $pending_referrals = $this->fetchall
                ? $this->WhmcsAffiliates->getPending($affiliate->id)->fetchAll()
                : $this->WhmcsAffiliates->getPending($affiliate->id);

            foreach ($pending_referrals as $referral) {
                $vars = [
                    'affiliate_id' => $this->mappings['affiliates'][$referral->clientid],
                    'order_id' => 0,
                    'name' => $referral->firstname . ' ' . $referral->lastname,
                    'status' => 'pending',
                    'amount' => $referral->amount,
                    'currency' => $referral->currency_code,
                    'commission' => $referral->commission,
                    'date_added' => $this->getValidDate($referral->created_at),
                    'date_updated' => $this->getValidDate($referral->updated_at)
                ];
                $this->local->insert('order_affiliate_referrals', $vars);
            }
            unset($pending_referrals);

            // Import available balance
            if ($affiliate->balance > 0) {
                $vars = [
                    'affiliate_id' => $this->mappings['affiliates'][$affiliate->clientid],
                    'order_id' => 0,
                    'name' => 'Imported Balance',
                    'status' => 'mature',
                    'amount' => $affiliate->balance,
                    'currency' => $affiliate->currency_code,
                    'commission' => $affiliate->balance,
                    'date_added' => $this->getValidDate($affiliate->created_at),
                    'date_updated' => $this->getValidDate($affiliate->updated_at)
                ];
                $this->local->insert('order_affiliate_referrals', $vars);
            }

            // Import payouts
            $withdrawal_balance = 0;
            $withdrawals = $this->fetchall
                ? $this->WhmcsAffiliates->getWithdrawals($affiliate->id)->fetchAll()
                : $this->WhmcsAffiliates->getWithdrawals($affiliate->id);

            foreach ($withdrawals as $withdrawal) {
                $vars = [
                    'affiliate_id' => $this->mappings['affiliates'][$affiliate->clientid],
                    'status' => 'approved',
                    'requested_amount' => $withdrawal->amount,
                    'requested_currency' => $affiliate->currency_code,
                    'paid_amount' => $withdrawal->amount,
                    'paid_currency' => $affiliate->currency_code,
                    'date_requested' => $this->getValidDate($withdrawal->date)
                ];
                $this->local->insert('order_affiliate_payouts', $vars);

                $withdrawal_balance = $withdrawal_balance + $withdrawal->amount;
            }

            // Import withdrawn balance
            if ($affiliate->withdrawn > $withdrawal_balance) {
                $vars = [
                    'affiliate_id' => $this->mappings['affiliates'][$affiliate->clientid],
                    'status' => 'approved',
                    'requested_amount' => ($affiliate->withdrawn - $withdrawal_balance),
                    'requested_currency' => $affiliate->currency_code,
                    'paid_amount' => ($affiliate->withdrawn - $withdrawal_balance),
                    'paid_currency' => $affiliate->currency_code,
                    'date_requested' => $this->getValidDate($affiliate->created_at)
                ];
                $this->local->insert('order_affiliate_payouts', $vars);
            }
        }
        $this->local->commit();
        unset($affiliates);
    }

    /**
     * Import miscellaneous
     */
    protected function importMisc()
    {
        $this->loadModel('WhmcsEmails');
        $this->loadModel('WhmcsConfiguration');
        $this->loadModel('WhmcsCalendar');

        $from_name = $this->WhmcsConfiguration->get('SystemEmailsFromName')->fetch();
        $from_email = $this->WhmcsConfiguration->get('SystemEmailsFromEmail')->fetch();

        // Mail log
        $this->startTimer('mail log');
        $this->local->begin();
        foreach ($this->WhmcsEmails->get() as $message) {
            if ($message->to == '') {
                continue;
            }

            $message->from_email = $from_email->value;
            $message->from_name = $from_name->value;
            $vars = $this->formatData($message, 'mail_log');
            $this->local->insert('log_emails', $vars);
        }
        $this->local->commit();
        $this->endTimer('mail log');

        // Configurations
        $this->startTimer('settings');
        $settings = $this->fetchall ? $this->WhmcsConfiguration->get()->fetchAll() : $this->WhmcsConfiguration->get();
        $this->local->begin();
        foreach ($settings as $setting) {
            $setting->value = $this->decode($setting->value);

            switch ($setting->setting) {
                case 'MailType':
                    $this->Companies->setSetting(
                        Configure::get('Blesta.company_id'),
                        'mail_delivery',
                        $setting->value == 'mail' ? 'php' : $setting->value
                    );
                    break;
                case 'SMTPHost':
                    $this->Companies->setSetting(Configure::get('Blesta.company_id'), 'smtp_host', $setting->value);
                    break;
                case 'SMTPUsername':
                    $this->Companies->setSetting(Configure::get('Blesta.company_id'), 'smtp_user', $setting->value);
                    break;
                case 'SMTPPassword':
                    if ($setting->value != '') {
                        $this->Companies->setSetting(
                            Configure::get('Blesta.company_id'),
                            'smtp_user',
                            $this->decryptData($setting->value)
                        );
                    }
                    break;
                case 'SMTPPort':
                    $this->Companies->setSetting(Configure::get('Blesta.company_id'), 'smtp_port', $setting->value);
                    break;
                case 'SMTPSSL':
                    $this->Companies->setSetting(Configure::get('Blesta.company_id'), 'smtp_security', $setting->value);
                    break;
                case 'CreateInvoiceDaysBefore':
                    $this->Companies->setSetting(
                        Configure::get('Blesta.company_id'),
                        'inv_days_before_renewal',
                        $setting->value
                    );
                    break;
                case 'SendInvoiceReminderDays':
                    $this->Companies->setSetting(
                        Configure::get('Blesta.company_id'),
                        'notice1',
                        $setting->value != 0 ? -1 * $setting->value : 'disabled'
                    );
                    break;
                case 'SendFirstOverdueInvoiceReminder':
                    $this->Companies->setSetting(
                        Configure::get('Blesta.company_id'),
                        'notice2',
                        $setting->value != 0 ? $setting->value : 'disabled'
                    );
                    break;
                case 'SendSecondOverdueInvoiceReminder':
                    $this->Companies->setSetting(
                        Configure::get('Blesta.company_id'),
                        'notice3',
                        $setting->value != 0 ? $setting->value : 'disabled'
                    );
                    break;
                case 'CCProcessDaysBefore':
                    $this->Companies->setSetting(
                        Configure::get('Blesta.company_id'),
                        'autodebit_days_before_due',
                        $setting->value
                    );
                    break;
                case 'AutoSuspensionDays':
                    $this->Companies->setSetting(
                        Configure::get('Blesta.company_id'),
                        'suspend_services_days_after_due',
                        $setting->value
                    );
                    break;
            }
        }
        $this->local->commit();
        $this->endTimer('settings');
        unset($settings);

        // Import calendar events
        $this->startTimer('calendar events');
        $events = $this->fetchall ? $this->WhmcsCalendar->get()->fetchAll() : $this->WhmcsCalendar->get();
        $this->local->begin();
        foreach ($events as $event) {
            $vars = [
                'company_id' => Configure::get('Blesta.company_id'),
                'staff_id' => isset($this->mappings['staff'][$event->adminid])
                    ? $this->mappings['staff'][$event->adminid]
                    : 0,
                'shared' => 0,
                'title' => $this->decode($event->title . ' ' . $event->desc),
                'url' => null,
                'start_date' => $this->Companies->dateToUtc($event->start),
                'end_date' => $event->end != 0
                    ? $this->Companies->dateToUtc($event->end)
                    : $this->Companies->dateToUtc($event->start),
                'all_day' => $event->allday
            ];
            $this->local->insert('calendar_events', $vars);
        }
        $this->local->commit();
        $this->endTimer('calendar events');
        unset($events);

        // Import todo events
        $this->startTimer('todo events');
        $events = $this->fetchall ? $this->WhmcsCalendar->getTodos()->fetchAll() : $this->WhmcsCalendar->getTodos();
        $this->local->begin();
        foreach ($events as $event) {
            $vars = [
                'company_id' => Configure::get('Blesta.company_id'),
                'staff_id' => isset($this->mappings['staff'][$event->admin])
                    ? $this->mappings['staff'][$event->admin]
                    : 0,
                'shared' => 0,
                'title' => $this->decode($event->title . ' ' . $event->description),
                'url' => null,
                'start_date' => $this->getValidDate($event->date),
                'end_date' => $this->getValidDate($event->duedate),
                'all_day' => 1
            ];
            $this->local->insert('calendar_events', $vars);
        }
        $this->local->commit();
        $this->startTimer('todo events');
        unset($events);
    }

    /**
     * Creates a user
     *
     * @param array $user An array of key/value pairs including:
     *  - username
     *  - password
     *  - date_added
     */
    protected function createUser(array $user)
    {
        $this->local->insert('users', $user);
        return $this->local->lastInsertId();
    }

    /**
     * Returns the field type
     *
     * @param string WHMCS field type
     * @return string Blesta field type
     */
    protected function getFieldType($type)
    {
        switch ($type) {
            case 'text':
            case 'textarea':
                return $type;
                break;
            case 'dropdown':
                return 'select';
            case 'tickbox':
                return 'checkbox';
            default:
                return 'text';
        }
    }

    /**
     * Returns the serialized selection of field values
     *
     * @param string WHMCS serialized values
     * @return string Blesta serialized values
     */
    protected function getFieldValues($values)
    {
        if ($values == '') {
            return null;
        }
        $values = explode(',', $values);
        return serialize(array_combine($values, $values));
    }

    /**
     * Returns the currency format
     *
     * @param int WHMCS currency format value
     * @return string Blesta currency format value
     */
    private function getCurrencyFormat($format)
    {
        switch ($format) {
            default:
            case 1:
            case 2:
                return '#,###.##';
            case 3:
                return '#.###,##';
            case 4:
                return '#,###';
        }
    }

    /**
     * Returns the local tax rule for the given level and rate
     *
     * @param int $level
     * @param float $rate
     * @return mixed A stdClass object representing the local tax rule, false if no rule exists
     */
    private function getTaxRule($level, $rate)
    {
        return $this->local->select()->from('taxes')->
            where('company_id', '=', Configure::get('Blesta.company_id'))->
            where('level', '=', $level)->where('amount', '=', $rate)->fetch();
    }

    /**
     * Convert WHMCS service status into Blesta service status
     *
     * @param string $status
     * @return string The service status
     */
    private function getServiceStatus($status)
    {
        $status = strtolower($status);
        switch ($status) {
            case 'active':
            case 'pending':
            case 'suspended':
                return $status;
            case 'fraud':
            case 'terminated':
            case 'cancelled':
            case 'expired':
                return 'canceled';
        }
        return 'in_review';
    }

    /**
     * Convert WHMCS credit card type into Blesta card type
     *
     * @param string $type The WHMCS credit card type
     * @return string The Blesta credit card type
     */
    protected function getCreditCardType($type)
    {
        switch ($type) {
            case 'Visa':
                return 'visa';
            case 'MasterCard':
                return 'mc';
            case 'Discover':
                return 'disc';
            case 'American Express':
                return 'amex';
            case 'JCB':
                return 'jcb';
            case 'Diners Club':
                return 'dc-int';
            default:
                return 'other';
        }

        return 'other';
    }

    /**
     * Decrypts data from WHMCS
     *
     * @param string $str The data to decrypt
     * @return string The decrypted data
     */
    protected function decryptData($str)
    {
        $this->decrypt_count++;
        $this->unpauseTimer('decrypt');
        try {
            $key = $this->settings['key'];
            $y = base64_decode($str);
            $x = null;

            // Key derivation
            $key = sha1(md5(md5($key)) . md5($key));
            $temp_key = null;
            for ($i=0; $i<strlen($key); $i+=2) {
                $temp_key .= chr(hexdec($key[$i] . $key[$i + 1]));
            }
            $key = $temp_key;
            $key_length = strlen($key);

            // Extract key seed from input
            $key_seed = substr($y, 0, $key_length);
            $y = substr($y, $key_length, strlen($y) - $key_length);

            // Calculate final key
            $z = null;
            for ($i=0; $i<$key_length; $i++) {
                $z .= chr(ord($key_seed[$i]) ^ ord($key[$i]));
            }

            // Decrypt
            for ($i=0; $i<strlen($y); $i++) {
                // Generate new key schedule for each block
                if ($i != 0 && $i % $key_length == 0) {
                    $temp = sha1($z . substr($x, $i - $key_length, $key_length));
                    $z = null;
                    for ($j=0; $j<strlen($temp); $j+=2) {
                        $z .= chr(hexdec($temp[$j] . $temp[$j+1]));
                    }
                }

                $x .= chr(ord($z[$i % $key_length]) ^ ord($y[$i]));
            }
            $this->pauseTimer('decrypt');
            return $x;
        } catch (Throwable $e) {
            $this->pauseTimer('decrypt');
            return $str;
        }
    }

    /**
     * Formats the given key into a mysql AES key
     *
     * @param string $key The AES key to format
     * @return string The mysql formatted AES key
     */
    protected function mysqlAesKey($key)
    {
        $new_key = str_repeat(chr(0), 16);
        for ($i = 0, $len = strlen($key); $i < $len; $i++) {
            $new_key[$i%16] = $new_key[$i%16] ^ $key[$i];
        }
        return $new_key;
    }

    /**
     * Decrypts a gateway setting
     *
     * @param string $gateway The gateway directory name
     * @param string $key The gateway field key
     * @param string $value The gateway field value to decrypt
     * @return string The decrypted gateway setting
     */
    protected function decryptGatewaySetting($gateway, $key, $value)
    {
        // Initialize crypto (AES in CBC)
        if (!isset($this->Security)) {
            Loader::loadComponents($this, ['Security']);
        }
        $aes = $this->Security->create('Crypt', 'AES', [2]); // 2 = phpseclib\Crypt\Base::MODE_CBC
        $aes->setKeyLength(256);
        $aes->disablePadding();

        $aes->setKey(hash("sha256", $gateway . ':' . $key . ':' . $this->settings['key']));

        $setting = $aes->decrypt($this->hexToBinary($value));

        return substr(
            $setting,
            strpos($setting, '"') + 1,
            strrpos($setting, '"') - strpos($setting, '"') - 1
        );
    }

    /**
     * Decrypts a stored credit card
     *
     * @param string $card_number The credit card number or token to decrypt
     * @param int $whmcs_client_id The ID of the client from WHMCS
     * @return string The decoded data
     */
    protected function decryptCreditCard($card_number, $whmcs_client_id)
    {
        // Initialize crypto (AES in CBC)
        if (!isset($this->Security)) {
            Loader::loadComponents($this, ['Security']);
        }
        $aes = $this->Security->create('Crypt', 'AES', [2]); // 2 = phpseclib\Crypt\Base::MODE_CBC
        $aes->setKeyLength(256);
        $aes->disablePadding();

        $aes->setKey(md5($this->settings['key'] . $whmcs_client_id));

        $setting = $aes->decrypt($this->hexToBinary($card_number));

        return str_replace(['\"', '"{', '}"'], ['"', '{', '}'], substr(
            $setting,
            0,
            strrpos($setting, '}') + 1
        ));
    }

    /**
     * Decrypts a client field
     *
     * @param string $value The encrypted value to decrypt
     * @param int $whmcs_client_id The ID of the client from WHMCS
     * @return string The decrypted value
     */
    protected function decryptClientField($value, $whmcs_client_id)
    {
        // Initialize crypto (AES in ECB)
        if (!isset($this->Security)) {
            Loader::loadComponents($this, ['Security']);
        }
        $aes = $this->Security->create('Crypt', 'AES', [1]); // 1 = CRYPT_AES_MODE_ECB
        $aes->disablePadding();

        $aes->setKey($this->mysqlAesKey(md5($this->settings['key'] . $whmcs_client_id)));

        return $aes->decrypt($value);
    }

    /**
     * Provides a wrapper for all decrypt functions
     *
     * @param string $method The decryption method to use, it can be
     *  'data', 'gateway', 'cc' or 'client'
     * @return string The decrypted value
     */
    protected function decrypt($method = 'data')
    {
        $arguments = func_get_args();

        if (count($arguments) > 1 && isset($arguments[0])) {
            unset($arguments[0]);
        }

        switch ($method) {
            case 'data':
                return call_user_func_array([$this, 'decryptData'], $arguments);
                break;
            case 'gateway':
                return call_user_func_array([$this, 'decryptGatewaySetting'], $arguments);
                break;
            case 'cc':
                return call_user_func_array([$this, 'decryptCreditCard'], $arguments);
                break;
            case 'client':
                return call_user_func_array([$this, 'decryptClientField'], $arguments);
                break;
        }
    }

    /**
     * Converts the given data from hexadecimal to binary
     *
     * @param string $hex_input The data in hexadecimal to be converted
     * @return mixed The data converted to binary, false if the conversion failed
     */
    private function hexToBinary($hex_input)
    {
        if (function_exists("hex2bin")) {
            return hex2bin($hex_input);
        }

        $length = strlen($hex_input);
        if ($length % 2 != 0) {
            return false;
        }

        if (strspn($hex_input, "0123456789abcdefABCDEF") != $length) {
            return false;
        }

        $output = "";
        $i = 0;
        while ($i < $length) {
            $output .= pack("H*", substr($hex_input, $i, 2));
            $i += 2;
        }

        return $output;
    }

    /**
     * Adds service fields
     *
     * @param array $service An array of key/value pairs for the remote service including:
     *  - id The ID of the service on the remote server
     *  - * other fields
     * @param array $mapping An array of module mapping config settings
     */
    private function addServiceFields($service, $mapping)
    {
        if (!isset($this->Services)) {
            Loader::loadModels($this, ['Services']);
        }

        foreach ($mapping['service_fields'] as $key => $field) {
            $value = array_key_exists($key, $service) ? $service[$key] : null;
            if ($key == 'password' && $value != '') {
                $value = $this->decryptData($value);
            }

            if (!is_object($field)) {
                continue;
            }

            if (isset($field->callback)) {
                $value = call_user_func_array($field->callback, [$value, (array)$service]);
            }

            if ($field->serialized > 0) {
                $value = serialize($value);
            }
            if ($field->encrypted > 0) {
                $value = $this->Services->systemEncrypt($value);
            }

            $vars = [
                'service_id' => $this->mappings['services'][$service['id']],
                'key' => $field->key,
                'value' => $value != null ? $this->decode($value) : '',
                'serialized' => $field->serialized,
                'encrypted' => $field->encrypted
            ];
            $this->local->insert('service_fields', $vars);
        }
    }

    /**
     * Get the pricing for the given term, package, and currency
     *
     * @param array $term An array of term info including:
     *  - term
     *  - period
     * @param stdClass $package The package in Blesta
     * @param string $currency The currency to fetch the pricing ID in,
     *  will fallback to any currency for the matching term and period
     * @param string $amount The service amount
     * @return stdClass An object containing the pricing
     */
    private function getPricing($term, $package, $currency = null, $amount = null)
    {
        if (!is_array($term)) {
            return null;
        }

        $pricing_id = null;
        if ($package) {
            foreach ($package->pricing as $price) {
                if ($price->term == $term['term'] && $price->period == $term['period']) {
                    $pricing_id = $price->id;
                    if ($price->currency == $currency) {
                        return $price;
                    }
                }
            }
        }

        // If no pricing found, add default pricing
        $pricing = [
            [
                'term' => $term['term'],
                'period' => $term['period'],
                'currency' => $currency ? $currency : 'USD',
                'price' => $amount !== null ? $amount : 0,
            ]
        ];
        $pricing_id = $this->addPackagePricing($pricing, $package->id);

        $fields = ['package_pricing.id', 'package_pricing.pricing_id', 'package_pricing.package_id', 'pricings.term',
            'pricings.period', 'pricings.price', 'pricings.setup_fee',
            'pricings.cancel_fee', 'pricings.currency',
            'packages.taxable'];
        return $this->local->select($fields)->from('package_pricing')->
            innerJoin('pricings', 'pricings.id', '=', 'package_pricing.pricing_id', false)->
            innerJoin('packages', 'packages.id', '=', 'package_pricing.package_id', false)->
            where('package_pricing.id', '=', $pricing_id)->fetch();
    }

    /**
     * Get the pricing for the given term, option value ID, and currency
     *
     * @param array $term An array of term info including:
     *  - term
     *  - period
     * @param int $value_id The option value ID in Blesta
     * @param string $currency The currency to fetch the pricing ID in
     * @return stdClass An object containing the pricing
     */
    private function getOptionPricing($term, $value_id, $currency)
    {
        if (!is_array($term)) {
            return null;
        }

        $fields = ['package_option_pricing.id', 'package_option_pricing.pricing_id',
            'package_option_pricing.option_value_id', 'pricings.term',
            'pricings.period', 'pricings.price', 'pricings.setup_fee',
            'pricings.cancel_fee', 'pricings.currency'];
        return $this->local->select($fields)->from('package_option_pricing')->
            innerJoin('pricings', 'pricings.id', '=', 'package_option_pricing.pricing_id', false)->
            where('pricings.currency', '=', $currency)->
            where('pricings.period', '=', $term['period'])->
            where('pricings.term', '=', $term['term'])->
            where('package_option_pricing.option_value_id', '=', $value_id)->fetch();
    }

    /**
     * Returns the local module row ID used for the remote service
     *
     * @param int $local_module_id The local ID of the module
     * @param string $row_value The module row field value for the remote
     *  service that uniquely identifies the module row
     * @param string $remote_module The name of the module on the remote server
     * @return int The local module row ID for the service
     */
    private function getModuleRowId($local_module_id, $row_value = null, $remote_module = null)
    {
        $module_row = false;
        if ($row_value) {
            $module_row = $this->local->select(['module_rows.*'])->from('module_rows')->
                innerJoin('module_row_meta', 'module_row_meta.module_row_id', '=', 'module_rows.id', false)->
                where('module_row_meta.value', '=', $row_value)->
                where('module_rows.module_id', '=', $local_module_id)->fetch();
        } else {
            // If no field, attempt to look up module row based on module name, since
            // the universal module uses the module name to create module rows
            $module_row = $this->local->select(['module_rows.*'])->from('module_rows')->
                innerJoin('modules', 'modules.id', '=', 'module_rows.module_id', false)->
                on('module_row_meta.module_row_id', '=', 'module_rows.id', false)->
                innerJoin('module_row_meta', 'module_row_meta.key', '=', 'name')->
                where('modules.class', '=', 'universal_module')->
                where('module_row_meta.value', '=', $remote_module)->
                where('module_rows.module_id', '=', $local_module_id)->fetch();
        }
        if ($module_row) {
            return $module_row->id;
        } else {
            $module_row = $this->local->select(['module_rows.*'])->from('module_rows')->
                where('module_rows.module_id', '=', $local_module_id)->fetch();
            return $module_row->id;
        }
    }

    /**
     * Fetch the currency in used by the given client
     *
     * @param int $client_id
     * @return string The currency code for the client
     */
    private function getCurrency($client_id)
    {
        if (!isset($this->Clients)) {
            Loader::loadModels($this, ['Clients']);
        }

        static $currencies = [];

        if (!isset($currencies[$client_id])) {
            $default_currency = $this->Clients->getSetting($client_id, 'default_currency');
            if ($default_currency) {
                $currencies[$client_id] = $default_currency->value;
            }
        }

        // Get currency this client is invoiced in
        return isset($currencies[$client_id]) ? $currencies[$client_id] : 'USD';
    }

    /**
     * Looks for the TLD of the given domain based on the packages created for the given registrar
     *
     * @param string $domain
     * @param string $registrar The registrar
     * @return string The TLD
     */
    private function getTld($domain, $registrar)
    {
        $tld = $domain;
        do {
            $tld = strstr(ltrim($tld, '.'), '.');
        } while (!isset($this->mappings['packages'][$tld . $registrar]) && $tld != '');

        return $tld;
    }

    /**
     * Decodes the HTML entities of the given string
     *
     * @param string $str The string to decode
     * @return string The decoded string
     */
    protected function decode($str)
    {
        if ($str === null) {
            return $str;
        }

        if (function_exists('mb_detect_encoding') && mb_detect_encoding($str) == 'UTF-8') {
            return html_entity_decode($str, ENT_QUOTES, 'UTF-8');
        }

        return utf8_encode(html_entity_decode($str, ENT_QUOTES, 'UTF-8'));
    }

    /**
     * Returns a valid date if 0000-00-00 given
     *
     * @param string $date The date to correct
     * @param string $format The format to cast the given date to
     * @param bool $error_null Whether to return null for an error
     * @return string A valid date cast to UTC
     */
    protected function getValidDate($date, $format = 'Y-m-d H:i:s', $error_null = false)
    {
        return substr($date, 0, 10) == '0000-00-00'
            ? ($error_null ? null : $this->Companies->dateToUtc(date('c'), $format))
            : $this->Companies->dateToUtc($date, $format);
    }

    /**
     * Load the given local model
     *
     * @param string $name The name of the model to load
     */
    protected function loadModel($name)
    {
        $name = Loader::toCamelCase($name);
        $file = Loader::fromCamelCase($name);
        Loader::load($this->path . DS . 'models' . DS . $file . '.php');
        $this->{$name} = new $name($this->remote);
    }

    /**
     * Formats the given data based on type
     *
     * @param stdClass $data The data to format
     * @param string $type The type of data being formatted
     * @return array The formatted data
     */
    protected function formatData($data, $type)
    {
        $fields = $this->getDataFields($type);
        $formatters = $this->getDataFormatters();
        $return_data = [];
        foreach ($fields as $field => $formating_options) {
            $return_data[$field] = isset($formating_options['field']) ? $data->{$formating_options['field']} : $data;

            foreach ($formating_options['formatters'] as $formatter => $parameters) {
                $return_data[$field] = call_user_func_array(
                    $formatters[$formatter],
                    array_merge(
                        [$return_data[$field]],
                        $parameters
                    )
                );
            }
        }

        return $return_data;
    }

    /**
     * Gets a list of all possible formatters
     *
     * @return array A list of formatters
     */
    private function getDataFormatters()
    {
        return [
            'numeric' => function($value) {
                return is_numeric($value) ? (int)$value : preg_replace('/[^0-9]+/', '', $value);
            },
            'map_field' => function($value, $mapped_type, $default) {
                return isset($this->mappings[$mapped_type][$value]) ? $this->mappings[$mapped_type][$value] : $default;
            },
            'map_value' => function($value, $value_map, $default) {
                return isset($value_map[$value]) ? $value_map[$value] : $default;
            },
            'decode' => function($value) {
                return $this->decode($value);
            },
            'if_set' => function($value, $default) {
                return isset($value) ? $value : $default;
            },
            'if_empty' => function($value, $default) {
                return empty($value) ? $default : $value;
            },
            'date' => function($value) {
                return $this->getValidDate($value);
            },
            'truncate' => function($value, $max_length) {
                $string = $this->DataStructure->create('string');
                return $string->truncate($value, ['length' => $max_length]);
            },
            'strip_tags' => function($value) {
                return strip_tags($value);
            },
            'constant' => function($value, $constant) {
                return $constant;
            },
            'custom' => function($value, $custom_function, $params = []) {
                return call_user_func_array($custom_function, array_merge([$value], $params));
            }
        ];
    }

    /**
     * Gets a list of field mappings and formatters to apply to each field
     *
     * @param string $type The type of data to fetch mappings and formatters for
     * @return array The mappings and formatters
     */
    private function getDataFields($type)
    {
        $formatters = [];
        switch ($type) {
            case 'ticket':
                // Mapping for ticket priority from WHMCS to Blesta
                $priorities = [
                    'High' => 'high',
                    'Medium' => 'medium',
                    'Low' => 'low'
                ];
                // Mapping for ticket statuses from WHMCS to Blesta
                $statuses = [
                    'Open' => 'open',
                    'Answered' => 'closed',
                    'Customer-Reply' => 'awaiting_reply',
                    'Closed' => 'closed',
                    'In Progress' => 'in_progress'
                ];

                // Set the field mapping and formatters
                $formatters = [
                    'code' => ['field' => 'tid', 'formatters' => ['numeric' => []]],
                    'department_id' => ['field' => 'did', 'formatters' => ['map_field' => ['support_departments', 0]]],
                    'staff_id' => ['formatters' => ['constant' => [null]]],
                    'service_id' => ['formatters' => ['constant' => [null]]],
                    'client_id' => ['formatters' => ['custom' => [
                        function ($ticket) {
                            return $ticket->userid > 0
                                && isset($this->mappings['clients'][$ticket->userid])
                                ? $this->mappings['clients'][$ticket->userid]
                                : null;
                        }
                    ]]],
                    'email' => ['field' => 'email', 'formatters' => ['if_empty' => [null], 'decode' => []]],
                    'summary' => ['field' => 'title', 'formatters' => ['decode' => [], 'truncate' => [255]]],
                    'priority' => ['field' => 'urgency', 'formatters' => ['map_value' => [$priorities, 'medium']]],
                    'status' => ['field' => 'status', 'formatters' => ['map_value' => [$statuses, 'open']]],
                    'date_added' => ['field' => 'date', 'formatters' => ['date' => []]],
                    'date_updated' => ['field' => 'date', 'formatters' => ['date' => []]],
                    'date_closed' => ['formatters' => ['custom' => [
                        function ($ticket, $statuses) {
                            return isset($statuses[$ticket->status])
                                && $statuses[$ticket->status] == 'closed'
                                ? $this->getValidDate($ticket->lastreply)
                                : null;
                        },
                        [$statuses]
                    ]]],
                ];
                break;
            case 'mail_log':
                $formatters = [
                    'company_id' => ['formatters' => ['custom' => [
                        function () {
                            return Configure::get('Blesta.company_id');
                        }
                    ]]],
                    'to_client_id' => ['formatters' => ['custom' => [
                        function ($message) {
                            return ($message->userid > 0
                                && isset($this->mappings['clients'][$message->userid])
                                ? $this->mappings['clients'][$message->userid]
                                : null);
                        }
                    ]]],
                    'from_staff_id' => ['formatters' => ['constant' => [null]]],
                    'to_address' => ['field' => 'to', 'formatters' => ['decode' => [], 'truncate' => [255]]],
                    'from_address' => ['field' => 'from_email', 'formatters' => ['decode' => [], 'truncate' => [255]]],
                    'from_name' => ['field' => 'from_name', 'formatters' => ['decode' => [], 'truncate' => [255]]],
                    'cc_address' => ['field' => 'cc', 'formatters' => ['if_empty' => [null], 'decode' => []]],
                    'subject' => [
                        'field' => 'subject',
                        'formatters' => ['if_empty' => [' '], 'decode' => [], 'truncate' => [255]]
                    ],
                    'body_text' => ['field' => 'message', 'formatters' => ['decode' => [], 'strip_tags' => []]],
                    'body_html' => ['field' => 'message', 'formatters' => ['decode' => []]],
                    'sent' => ['formatters' => ['constant' => [1]]],
                    'error' =>['formatters' => ['constant' => [null]]],
                    'date_sent' => ['field' => 'date', 'formatters' => ['date' => []]]
                ];
            default:
                // Do nothing
        }

        return $formatters;
    }

    /**
     * Formats and returns a valid country code
     *
     * @param string $country The country to look up
     */
    protected function getValidCountry($country)
    {
        Loader::loadComponents($this, ['Record']);

        $country = trim($country);
        if (strlen($country) > 2) {
            $country_code = $this->Record->select()
                ->from('countries')
                ->where('countries.name', 'LIKE', '%' . $country . '%')
                ->orWhere('countries.alt_name', 'LIKE', '%' . $country . '%')
                ->fetch();

            if (function_exists('mb_detect_encoding') && mb_detect_encoding($country) == 'UTF-8') {
                $country = isset($country_code->alpha2) ? $country_code->alpha2 : mb_strtoupper(mb_substr($country, 0, 2));
            } else {
                $country = isset($country_code->alpha2) ? $country_code->alpha2 : strtoupper(substr($country, 0, 2));
            }
        }

        return $country;
    }

    /**
     * Formats and returns a valid state code
     *
     * @param string $country The country where the state belongs
     * @param string $state The state to look up
     */
    protected function getValidState($country, $state)
    {
        Loader::loadModels($this, ['States']);
        Loader::loadComponents($this, ['Record']);

        $country = trim($country);
        $state = trim($state);

        // Check if the given state code is a valid code
        $state_code = $this->States->get($country, $state);

        if (isset($state_code->code)) {
            return strtoupper($state_code->code);
        }

        // Check if there is a state code matching the name of the given state
        if (!empty($state)) {
            $country = $this->getValidCountry($country);
            $state_code = $this->Record->select()
                ->from('states')
                ->where('states.country_alpha2', '=', $country)
                ->open()
                    ->where('states.code', 'LIKE', '%' . $state . '%')
                    ->orWhere('states.name', 'LIKE', '%' . $state . '%')
                ->close()
                ->fetch();

            if (isset($state_code->code)) {
                return $state_code->code;
            }
        }

        // There is no matching state
        if (!empty($state)) {
            if (function_exists('mb_detect_encoding') && mb_detect_encoding($state) == 'UTF-8') {
                return mb_strtoupper(mb_substr($state, 0, 3));
            } else {
                return strtoupper(substr($state, 0, 3));
            }
        }

        return null;
    }
}
