<?php
/**
 * Generic Clientexec Migrator.
 *
 * @package blesta
 * @subpackage blesta.plugins.import_manager.components.migrators.clientexec
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ClientexecMigrator extends Migrator
{
    /**
     * @var array An array of settings
     */
    protected $settings;

    /**
     * @var string Default conutry code
     */
    protected $default_country = 'US';

    /**
     * @var string Current path
     */
    protected $path;

    /**
     * Runs the import, sets any Input errors encountered.
     */
    public function import()
    {
        $actions = [
            'importUsersGroups',
            'importStaff',
            'importClients',
            'importClientsNotes',
            'importTaxes',
            'importCurrencies',
            'importInvoices',
            'importTransactions',
            'importModules',
            'importPackages',
            'importPackageOptions',
            'importServices',
            'importSupportDepartments',
            'importSupportTickets',
            'importKnowledgeBase',
            'importCoupons',
            'importSettings'
        ];

        $errors = [];
        $this->startTimer('total time');
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
            } catch (Exception $e) {
                $errors[] = $action . ': ' . $e->getMessage() . ' on line ' . $e->getLine();
                $this->logException($e);
            }
        }

        if (!empty($errors)) {
            array_unshift($errors, Language::_('Clientexec5_5.!error.import', true));
            $this->Input->setErrors(['error' => $errors]);
        }
        $this->endTimer('total time');

        if ($this->enable_debug) {
            $this->debug(print_r($this->Input->errors(), true));
            exit;
        }
    }

    /**
     * Import users groups.
     */
    protected function importUsersGroups()
    {
        // Load required models
        Loader::loadModels($this, ['StaffGroups', 'ClientGroups']);

        // Load remote model
        $this->loadModel('ClientexecUsers');

        // Get staff groups
        $staff_groups = $this->StaffGroups->getAll(Configure::get('Blesta.company_id'));

        $groups = [];
        foreach ($staff_groups as $group) {
            if ($group->name == 'Administrators') {
                $groups['admin'] = $group->id;
            } elseif ($group->name == 'Billing') {
                $groups['billing'] = $group->id;
            }
        }

        // Get clients groups
        $client_groups = $this->ClientGroups->getAll(Configure::get('Blesta.company_id'));
        foreach ($client_groups as $group) {
            if ($group->name == 'General') {
                $groups['client'] = $group->id;
            }
        }

        // Get remote users groups
        $remote_groups = $this->ClientexecUsers->getAllUsersGroups();

        // Import users groups
        $this->local->begin();
        foreach ($remote_groups as $group) {
            if ($group->isadmin && $group->issuperadmin) {
                if ($group->name == 'Super Admin') {
                    // Map to the existent admin staff group
                    $this->mappings['staff_groups'][$group->id] = $groups['admin'];
                } else {
                    // Create a new staff group
                    $this->mappings['staff_groups'][$group->id] = $this->StaffGroups->add([
                        'company_id' => Configure::get('Blesta.company_id'),
                        'name' => $this->decode($group->name),
                        'session_lock' => 1,
                        'permission_group' => [],
                        'permission' => []
                    ]);
                }
            } elseif ($group->isadmin && !$group->issuperadmin) {
                if ($group->name == 'Billing') {
                    // Map to the existent billing staff group
                    $this->mappings['staff_groups'][$group->id] = $groups['billing'];
                } else {
                    // Create a new staff group
                    $this->mappings['staff_groups'][$group->id] = $this->StaffGroups->add([
                        'company_id' => Configure::get('Blesta.company_id'),
                        'name' => $this->decode($group->name),
                        'session_lock' => 1,
                        'permission_group' => [],
                        'permission' => []
                    ]);
                }
            } elseif (!$group->isadmin && !$group->issuperadmin) {
                if ($group->name == 'Customer') {
                    // Map to the existent client group
                    $this->mappings['client_groups'][$group->id] = $groups['client'];
                } else {
                    // Create a new client group
                    $this->mappings['client_groups'][$group->id] = $this->ClientGroups->add([
                        'company_id' => Configure::get('Blesta.company_id'),
                        'name' => $this->decode($group->name),
                        'description' => $this->decode($group->description),
                        'color' => $group->groupcolor
                    ]);
                }
            }
        }
        $this->local->commit();
    }

    /**
     * Import staff.
     */
    protected function importStaff()
    {
        // Load required models
        Loader::loadModels($this, ['Staff', 'Users']);

        // Load remote model
        $this->loadModel('ClientexecUsers');

        // Get users
        $users = $this->ClientexecUsers->getStaff();

        // Import staff members
        $this->local->begin();
        foreach ($users as $user) {
            // Get custom fields
            $fields = $this->ClientexecUsers->getCustomFields($user->id);

            // Create user account
            $vars = [
                'username' => $user->email,
                'password' => substr($fields['password']->value, 0, 64),
                'date_added' => $this->Users->dateToUtc(date('c'))
            ];
            $this->local->insert('users', $vars);
            $user_id = $this->local->lastInsertId();

            // Create staff account
            $vars = [
                'user_id' => $user_id,
                'first_name' => $this->decode($user->firstname),
                'last_name' => $this->decode($user->lastname),
                'email' => $user->email,
                'status' => $user->status == '1' ? 'active' : 'inactive',
                'groups' => [
                    $this->mappings['staff_groups'][$user->groupid]
                ]
            ];
            $this->addStaff($vars, $user->id);
        }
        $this->local->commit();
    }

    /**
     * Import clients.
     */
    protected function importClients()
    {
        // Load required models
        Loader::loadModels($this, ['Clients', 'Users', 'Accounts', 'Transactions']);
        Loader::loadComponents($this, ['Security']);
        $this->Crypt_Blowfish = $this->Security->create('Crypt', 'Blowfish');

        // Load remote model
        $this->loadModel('ClientexecUsers');

        // Get users
        $users = $this->ClientexecUsers->getClients();

        // Import clients
        $this->local->begin();
        foreach ($users as $user) {
            // Get current user group
            $group = $this->ClientexecUsers->getUserGroup($user->groupid);

            // Get custom fields
            $fields = $this->ClientexecUsers->getCustomFields($user->id);

            // Create client
            if ($group->iscustomersmaingroup) {
                // Create user account
                $vars = [
                    'username' => $user->email,
                    'password' => substr($fields['password']->value, 0, 64),
                    'date_added' => $this->Users->dateToUtc(date('c'))
                ];
                $this->local->insert('users', $vars);
                $user_id = $this->local->lastInsertId();


                // Create client account
                $vars = [
                    'id_format' => '{num}',
                    'id_value' => $user->id,
                    'user_id' => $user_id,
                    'client_group_id' => $this->mappings['client_groups'][$user->groupid],
                    'status' => (int) $user->status >= 0 ? 'active' : 'inactive'
                ];
                $this->local->insert('clients', $vars);
                $client_id = $this->local->lastInsertId();
                $this->mappings['clients'][$user->id] = $client_id;

                // Create primary contact
                $vars = [
                    'client_id' => $client_id,
                    'contact_type' => 'primary',
                    'first_name' => $this->decode($user->firstname),
                    'last_name' => $this->decode($user->lastname),
                    'company' => $this->decode($user->organization != '' ? $user->organization : null),
                    'email' => $user->email,
                    'address1' => $this->decode(!empty($fields['address']->value) ? $fields['address']->value : null),
                    'city' => $this->decode(!empty($fields['city']->value) ? $fields['city']->value : null),
                    'state' => $this->decode(!empty($fields['state']->value)
                        ? strtoupper(substr($fields['state']->value, 0, 2))
                        : null),
                    'zip' => !empty($fields['zipcode']->value) ? $fields['zipcode']->value : null,
                    'country' => $this->decode(!empty($fields['country']->value) ? $fields['country']->value : $this->default_country),
                    'date_added' => $this->Users->dateToUtc($user->dateactivated)
                ];
                $this->local->insert('contacts', $vars);
                $contact_id = $this->local->lastInsertId();
                $this->mappings['primary_contacts'][$user->id] = $contact_id;

                // Save client settings
                $vars = [
                    'autodebit' => $user->autopayment == '1' ? 'true' : 'false',
                    'autosuspend' => 'true',
                    'default_currency' => $user->currency,
                    'inv_address_to' => $contact_id,
                    'inv_method' => 'email',
                    'language' => 'en_us',
                    'tax_exempt' => $user->taxable == '1' ? 'false' : 'true',
                    'tax_id' => null,
                    'username_type' => 'email'
                ];
                $this->Clients->setSettings($client_id, $vars);

                // Add contact phone number
                if (isset($fields['phone']->value) && $fields['phone']->value != '') {
                    $vars = [
                        'contact_id' => $contact_id,
                        'number' => $fields['phone']->value,
                        'type' => 'phone',
                        'location' => 'home'
                    ];
                    $this->local->insert('contact_numbers', $vars);
                }

                // Add the payment account
                if ($user->data3 != '') {
                    $this->Crypt_Blowfish->setKey($user->id . $this->settings['passphrase']);
                    $this->Crypt_Blowfish->setIV(base64_decode($user->data2));
                    $credit_card = $this->Crypt_Blowfish->decrypt(base64_decode($user->data3));
                    $vars = [
                        'contact_id' => $contact_id,
                        'first_name' => $this->decode($user->firstname),
                        'last_name' => $this->decode($user->lastname),
                        'address1' => $this->decode($fields['address']->value != '' ? $fields['address']->value : null),
                        'city' => $this->decode($fields['city']->value != '' ? $fields['city']->value : null),
                        'state' => $this->decode($fields['state']->value != '' ? strtoupper(substr($fields['state']->value, 0, 2)) : null),
                        'zip' => $fields['zipcode']->value != '' ? $fields['zipcode']->value : null,
                        'country' => $this->decode($fields['country']->value != '' ? $fields['country']->value : $this->default_country),
                        'number' => $credit_card,
                        'expiration' => $user->ccyear . (strlen($user->ccmonth) == 1 ? '0' . $user->ccmonth : $user->ccmonth)
                    ];
                    $account_id = $this->Accounts->addCc($vars);

                    // Set account for autodebit
                    if ($account_id) {
                        $vars = [
                            'client_id' => $client_id,
                            'account_id' => $account_id,
                            'type' => 'cc'
                        ];
                        $this->local->insert('client_account', $vars);
                    }
                }

                // Add credit balance
                if ($user->balance > 0) {
                    $vars = [
                        'client_id' => $client_id,
                        'amount' => $user->balance,
                        'currency' => $user->currency,
                        'transaction_type_id' => $this->getTransactionTypeId('in_house_credit'),
                        'transaction_id' => md5($client_id . $user->balance . $user->currency),
                        'status' => 'approved',
                        'date_added' => $this->Transactions->dateToUtc(date('c'))
                    ];
                    $this->Transactions->add($vars);
                }
            }
        }
        $this->local->commit();
    }

    /**
     * Import clients notes.
     */
    protected function importClientsNotes()
    {
        // Load required models
        Loader::loadModels($this, ['Clients']);

        // Load remote model
        $this->loadModel('ClientexecClients');

        // Get clients notes
        $notes = $this->ClientexecClients->getNotes();

        // Import notes
        $this->local->begin();
        foreach ($notes as $note) {
            $vars = [
                'client_id' => $this->mappings['clients'][$note->target_id],
                'staff_id' => isset($this->mappings['staff'][$note->admin_id]) ? $this->mappings['staff'][$note->admin_id] : 0,
                'title' => $this->decode($note->subject),
                'description' => $this->decode($note->note),
                'stickied' => 0,
                'date_added' => $this->Clients->dateToUtc($note->date),
                'date_updated' => $this->Clients->dateToUtc($note->date)
            ];
            $this->local->insert('client_notes', $vars);
        }
        $this->local->commit();
    }

    /**
     * Import taxes.
     */
    protected function importTaxes()
    {
        // Load remote model
        $this->loadModel('ClientexecTaxes');

        // Get taxes
        $taxes = $this->ClientexecTaxes->get();

        // Import taxes
        $this->local->begin();
        foreach ($taxes as $tax) {
            $state = $this->local->select()->from('states')->where('name', '=', trim($tax->state))->fetch();
            $vars = [
                'company_id' => Configure::get('Blesta.company_id'),
                'level' => $tax->level,
                'name' => $this->decode($tax->name),
                'state' => $state ? $state->code : null,
                'country' => $tax->countryiso != '_ALL' ? $tax->countryiso : null,
                'amount' => $tax->tax
            ];
            $this->local->insert('taxes', $vars);
            $tax_id = $this->local->lastInsertId();

            $this->mappings['taxes'][$tax->id] = $tax_id;
        }
        $this->local->commit();
    }

    /**
     * Import currencies.
     */
    protected function importCurrencies()
    {
        // Load required models
        Loader::loadModels($this, ['Companies']);

        // Load remote model
        $this->loadModel('ClientexecCurrencies');

        // Get currencies
        $currencies = $this->ClientexecCurrencies->getEnabled();

        // Import currencies
        $this->local->begin();
        foreach ($currencies as $currency) {
            $vars = [
                'code' => $currency->abrv,
                'company_id' => Configure::get('Blesta.company_id'),
                'format' => $this->getCurrencyFormat($currency),
                'precision' => $currency->precision,
                'prefix' => $this->decode(
                    $currency->symbol != '' ? $currency->symbol : substr($currency->abrv, 0, 1) . '$'
                ),
                'exchange_rate' => $currency->rate,
                'exchange_updated' => null
            ];
            $this->local->duplicate('format', '=', $vars['format'])->
                duplicate('prefix', '=', $vars['prefix'])->
                duplicate('exchange_rate', '=', $vars['exchange_rate'])->
                insert('currencies', $vars);
        }
        $this->local->commit();

        // Set default currency
        $default_currency = $this->ClientexecCurrencies->getDefault();
        $this->Companies->setSetting(Configure::get('Blesta.company_id'), 'default_currency', $default_currency);
    }

    /**
     * Import invoices.
     */
    protected function importInvoices()
    {
        // Load required models
        Loader::loadModels($this, ['Companies']);

        // Load remote model
        $this->loadModel('ClientexecInvoices');

        // Get invoices
        $invoices = $this->ClientexecInvoices->get();

        // Import invoices
        $this->local->begin();
        foreach ($invoices as $invoice) {
            // Get invoice lines
            $lines = $this->ClientexecInvoices->getInvoiceLines($invoice->id);

            // Get invoice currency
            $currency = $this->ClientexecInvoices->getInvoiceCurrency($invoice->id);

            // Unpaid (0), paid (1), and partially paid (5) are marked as active
            $status = 'active';
            switch ($invoice->status) {
                case -1: // draft
                    $status = 'draft';
                    break;
                case 2: // void
                case 3: // refunded
                    $status = 'void';
                    break;
            }
            // Create invoice
            $vars = [
                'id_format' => '{num}',
                'id_value' => $invoice->id,
                'client_id' => $this->mappings['clients'][$invoice->customerid],
                'date_billed' => $this->Companies->dateToUtc($invoice->datecreated),
                'date_due' => $this->Companies->dateToUtc($invoice->billdate),
                'date_closed' => strtolower($invoice->status) != '1' || $invoice->datepaid == '0000-00-00' ? null : $this->Companies->dateToUtc($invoice->datepaid),
                'date_autodebit' => null,
                'status' => $status,
                'total' => number_format($invoice->amount, 4),
                'paid' => number_format($invoice->amount - $invoice->balance_due, 4),
                'currency' => $currency,
                'note_public' => $this->decode($invoice->note),
                'note_private' => null,
            ];
            $this->local->insert('invoices', $vars);
            $invoice_id = $this->local->lastInsertId();

            $this->mappings['invoices'][$invoice->id] = $invoice_id;

            // Import invoice lines
            foreach ($lines as $line) {
                $vars = [
                    'invoice_id' => $invoice_id,
                    'service_id' => null,
                    'description' => $this->decode($line->description) . ' ' . $this->decode($line->detail),
                    'qty' => 1,
                    'amount' => $line->price,
                    'order' => 0
                ];
                $this->local->insert('invoice_lines', $vars);
                $line_id = $this->local->lastInsertId();

                // Import tax lines
                if ($line->taxable == '1') {
                    if ($invoice->taxname != '') {
                        $level1 = $this->local->select()
                            ->from('taxes')
                            ->where('name', '=', trim($invoice->taxname))
                            ->fetch();

                        $vars = [
                            'line_id' => $line_id,
                            'tax_id' => $level1->id,
                            'cascade' => $invoice->tax2compound
                        ];
                        $this->local->insert('invoice_line_taxes', $vars);
                    }

                    if ($invoice->tax2name != '') {
                        $level2 = $this->local->select()->from('taxes')->where('name', '=', trim($invoice->tax2name))->where('level', '=', 2)->fetch();

                        $vars = [
                            'line_id' => $line_id,
                            'tax_id' => $level2->id,
                            'cascade' => $invoice->tax2compound
                        ];
                        $this->local->insert('invoice_line_taxes', $vars);
                    }
                }
            }
        }
        $this->local->commit();
    }

    /**
     * Import transactions.
     */
    protected function importTransactions()
    {
        // Load required models
        Loader::loadModels($this, ['Invoices', 'Transactions']);

        // Load remote model
        $this->loadModel('ClientexecTransactions');
        $this->loadModel('ClientexecInvoices');

        // Get transactions
        $transactions = $this->ClientexecTransactions->get();

        // Import transactions
        $this->local->begin();
        foreach ($transactions as $transaction) {
            // Get parent invoice
            $invoice = $this->ClientexecInvoices->getInvoice($transaction->invoiceid);

            // Get invoice currency
            $currency = $this->ClientexecInvoices->getInvoiceCurrency($transaction->invoiceid);

            // Add transaction
            $status = 'approved';
            if ($transaction->action == 'refund') {
                $status = 'refunded';
            } elseif ($transaction->accepted != '1') {
                $status = 'declined';
            }

            // Approved transanctions on a refunded invoice should be voided so they are not counted towards credit.
            // The refunded amount from the invoice is stored in a different transaction.
            if ($invoice->status == 3 && $status == 'approved') {
                $status = 'void';
            }

            $vars = [
                'client_id' => $this->mappings['clients'][$invoice->customerid],
                'amount' => $transaction->amount,
                'currency' => $currency,
                'transaction_id' => $transaction->transactionid == 'NA' ? md5($transaction->response) : $transaction->transactionid,
                'status' => $status,
                'date_added' => $this->Transactions->dateToUtc($transaction->transactiondate, 'c')
            ];
            $transaction_id = $this->addTransaction($vars, $transaction->id);

            // Apply payment
            if (isset($this->mappings['invoices'][$transaction->invoiceid]) && $transaction->amount > 0) {
                $vars = [
                    'date' => $this->Transactions->dateToUtc($transaction->transactiondate, 'c'),
                    'amounts' => [
                        [
                            'invoice_id' => $this->mappings['invoices'][$transaction->invoiceid],
                            'amount' => $transaction->amount,
                        ]
                    ]
                ];
                $this->Transactions->apply($transaction_id, $vars);
            }
        }
        $this->local->commit();
    }

    /**
     * Import modules.
     */
    protected function importModules()
    {
        // Load remote model
        $this->loadModel('ClientexecProducts');

        // Get servers
        $servers = $this->ClientexecProducts->getServers();

        // Import generic server module required for all package assigned to no module
        $this->installModuleRow(['id' => 'none', 'type' => 'none']);

        // Import servers
        foreach ($servers as $server) {
            // Build row array
            $notes = (!empty($server->statsurl) ? 'Stats URL: ' . $server->statsurl . "\n" : null) .
                (!empty($server->status_message) ? 'Status Message: ' . $server->status_message . "\n" : null) .
                (!empty($server->provider) ? 'Provider/Datacenter: ' . $server->provider : null);

            $row = [
                'type' => $server->plugin,
                'id' => $server->id,
                'hostname' => $server->hostname,
                'sharedip' => $server->sharedip,
                'domains_quota' => $server->domains_quota == '0' ? null : $server->domains_quota,
                'notes' => $notes
            ];

            // Get server fields
            $server_fields = $this->ClientexecProducts->getServerFields($server->id);

            foreach ($server_fields as $server_field) {
                $key = strtolower($server_field->optionname);
                $row[$key] = $server_field->value;
            }

            // Get nameservers
            $nameservers = $this->ClientexecProducts->getServerNameservers($server->id);

            if (!empty($nameservers)) {
                foreach ($nameservers as $nameserver) {
                    $row['nameservers'][] = $nameserver->hostname;
                }
            }

            // Install module
            $this->installModuleRow($row);
        }

        // Get registrars
        $registrars = $this->ClientexecProducts->getReigstrars();

        // Import registrars
        foreach ($registrars as $registrar) {
            $row = [
                'type' => $registrar,
                'id' => $registrar
            ];

            // Get registrar fields
            $registrar_fields = $this->ClientexecProducts->getRegistrarFields($registrar);

            foreach ($registrar_fields as $registrar_field) {
                $key = str_replace(' ', '_', strtolower($registrar_field->name));
                $row[$key] = $registrar_field->value;
            }

            // Install module
            $this->installModuleRow($row, 'registrar');
        }
    }

    /**
     * Import packages.
     */
    protected function importPackages()
    {
        // Load required models
        Loader::loadModels($this, ['PackageGroups']);

        // Load remote model
        $this->loadModel('ClientexecProducts');
        $this->loadModel('ClientexecCurrencies');

        // Get products groups
        $packages_groups = $this->ClientexecProducts->getGroups();

        // Get packages
        $packages = $this->ClientexecProducts->get();

        // Get default currency
        $currency = $this->ClientexecCurrencies->getDefault();

        // Import package groups
        $this->local->begin();
        foreach ($packages_groups as $package_group) {
            $vars = [
                'company_id' => Configure::get('Blesta.company_id'),
                'names' => [['lang' => 'en_us', 'name' => $this->decode($package_group->name)]],
                'description' => [
                    [
                        'lang' => 'en_us',
                        'description' => $this->decode(
                            $package_group->description == '' ? $package_group->description : null
                        )
                    ]
                ],
                'type' => 'standard'
            ];
            $package_group_id = $this->PackageGroups->add($vars);
            $this->mappings['package_groups'][$package_group->id] = $package_group_id;
        }
        $this->local->commit();

        // Import packages
        $this->local->begin();
        foreach ($packages as $package) {
            // Get remote package pricing & info
            $pricing = unserialize($package->pricing);
            $stock = unserialize($package->stockinfo);

            // Add product package
            if (!isset($pricing['pricedata'])) {
                // Get module mapping
                $server = $this->ClientexecProducts->getProductServer($package->id);
                $mapping = $this->getModuleMapping($server->plugin);

                // Add product package
                $vars = [
                    'id_format' => '{num}',
                    'id_value' => $package->id,
                    'module_id' => $this->mappings['modules'][$server->plugin],
                    'qty' => $stock['stockEnabled'] == '1' ? $stock['availableStock'] : null,
                    'module_row' => $this->mappings['module_rows'][$server->plugin][$server->id],
                    'module_group' => null,
                    'taxable' => 1,
                    'status' => $package->showpackage == '1' ? 'active' : 'restricted',
                    'company_id' => Configure::get('Blesta.company_id')
                ];
                $this->local->insert('packages', $vars);
                $this->mappings['packages'][$package->id] = $this->local->lastInsertId();

                // Assign package group
                $this->local->insert('package_group', [
                    'package_id' => $this->mappings['packages'][$package->id],
                    'package_group_id' => $this->mappings['package_groups'][$package->planid]
                ]);

                // Add package pricing
                $package_pricing = $this->ClientexecProducts->getProductPricing($package->id);
                $this->addPackagePricing($package_pricing, $this->mappings['packages'][$package->id]);

                // Import package fields
                $fields = [
                    'id' => $package->id
                ];
                $product_fields = $this->ClientexecProducts->getProductFields($package->id);

                foreach ($product_fields as $product_field) {
                    $fields[$product_field->varname] = $product_field->value;
                }

                $this->addPackageMeta($fields, $mapping);

                // Import package name
                $this->local->insert(
                    'package_names',
                    [
                        'package_id' => $this->mappings['packages'][$package->id],
                        'lang' => 'en_us',
                        'name' => $this->decode($package->planname)
                    ]
                );

                // Import package description
                $this->local->insert(
                    'package_descriptions',
                    [
                        'package_id' => $this->mappings['packages'][$package->id],
                        'lang' => 'en_us',
                        'text' => $this->decode(strip_tags($package->description)),
                        'html' => $this->decode($package->description)
                    ]
                );
            } else {
                // Get module mapping
                $module = $pricing['pricedata'][0]['registrar'];
                $mapping = $this->getModuleMapping($module);

                // Add domain package
                $vars = [
                    'id_format' => '{num}',
                    'id_value' => $package->id,
                    'module_id' => $this->mappings['modules'][$module],
                    'qty' => $stock['stockEnabled'] == '1' ? $stock['availableStock'] : null,
                    'module_row' => $this->mappings['module_rows'][$module][$module],
                    'module_group' => null,
                    'taxable' => 1,
                    'status' => $package->showpackage == '1' ? 'active' : 'restricted',
                    'company_id' => Configure::get('Blesta.company_id')
                ];
                $this->local->insert('packages', $vars);
                $this->mappings['packages'][$package->id] = $this->local->lastInsertId();

                // Assign package group
                $this->local->insert('package_group', [
                    'package_id' => $this->mappings['packages'][$package->id],
                    'package_group_id' => $this->mappings['package_groups'][$package->planid]
                ]);

                // Add package pricing
                $package_pricing = $this->ClientexecProducts->getProductPricing($package->id);
                $this->addPackagePricing($package_pricing, $this->mappings['packages'][$package->id]);

                // Import package fields
                $fields = [
                    'id' => $package->id,
                    'tlds' => [$package->planname]
                ];
                $product_fields = $this->ClientexecProducts->getProductFields($package->id);

                foreach ($product_fields as $product_field) {
                    $fields[$product_field->varname] = $product_field->value;
                }

                $this->addPackageMeta($fields, $mapping);

                // Import package name
                $this->local->insert(
                    'package_names',
                    [
                        'package_id' => $this->mappings['packages'][$package->id],
                        'lang' => 'en_us',
                        'name' => $this->decode($package->planname)
                    ]
                );

                // Import package description
                $this->local->insert(
                    'package_descriptions',
                    [
                        'package_id' => $this->mappings['packages'][$package->id],
                        'lang' => 'en_us',
                        'text' => $this->decode(strip_tags($package->description)),
                        'html' => $this->decode($package->description)
                    ]
                );
            }
        }
        $this->local->commit();
    }

    /**
     * Import package options.
     */
    protected function importPackageOptions()
    {
        // Load required models
        Loader::loadModels($this, ['PackageOptionGroups', 'PackageOptions']);

        // Load remote model
        $this->loadModel('ClientexecProducts');
        $this->loadModel('ClientexecCurrencies');

        // Get addons
        $addons = $this->ClientexecProducts->getAddons();

        // Get default currency
        $currency = $this->ClientexecCurrencies->getDefault();

        // Import products groups
        foreach ($addons as $addon) {
            // Get assigned packages to the addon
            $packages = $this->ClientexecProducts->getAddonPackages($addon->id);

            // Create package addon groups
            foreach ($packages as $package) {
                // Get package data
                $package_data = $this->ClientexecProducts->getProduct($package->product_id);

                // Create the option group
                if (!isset($this->mappings['package_options_groups'][$package->product_id])) {
                    $vars = [
                        'company_id' => Configure::get('Blesta.company_id'),
                        'name' => $this->decode($package_data->planname . ' Addons'),
                        'description' => $this->decode(strip_tags($package_data->description)),
                        'packages' => [
                            $this->mappings['packages'][$package->product_id]
                        ]
                    ];
                    $option_group_id = $this->PackageOptionGroups->add($vars);

                    // Record package group mapping
                    $this->mappings['package_options_groups'][$package->product_id] = $option_group_id;
                }
            }

            // Add package options
            $values = $this->ClientexecProducts->getAddonPricing($addon->id);
            $groups = [];

            foreach ($packages as $package) {
                $groups[] = $this->mappings['package_options_groups'][$package->product_id];
            }

            $vars = [
                'company_id' => Configure::get('Blesta.company_id'),
                'label' => $addon->name,
                'name' => str_replace(' ', '_', strtolower($addon->name)),
                'type' => 'select',
                'values' => $values,
                'groups' => $groups
            ];
            $option_id = $this->PackageOptions->add($vars);

            // Record package option mapping
            $this->mappings['package_options'][$addon->id] = $option_id;
        }
    }

    /**
     * Import services.
     */
    protected function importServices()
    {
        // Load required models
        Loader::loadModels($this, ['Packages', 'ModuleManager']);

        // Load remote model
        $this->loadModel('ClientexecServices');
        $this->loadModel('ClientexecCurrencies');

        // Get services
        $services = $this->ClientexecServices->get();

        // Import services
        foreach ($services as $service) {
            // If the client doesn't exist, we can't import the service
            if (!isset($this->mappings['clients'][$service->customerid])) {
                continue;
            }

            // If the package doesn't exist, we can't import the service
            if (!isset($this->mappings['packages'][$service->plan])) {
                continue;
            }

            // Get local package
            $package = $this->Packages->get($this->mappings['packages'][$service->plan]);

            // Get package module
            if (!isset($this->mappings['modules'])) {
                $module = $this->ModuleManager->get($package->module_id, false, false);
                if ($module) {
                    $modules[$package->module_id] = $module->class;
                }
            } else {
                $modules = array_flip($this->mappings['modules']);
            }

            // Get module mapping
            $mapping = $this->getModuleMapping(
                isset($modules[$package->module_id]) ? $modules[$package->module_id] : 'generic_server'
            );

            // Get default currency
            $currency = $this->ClientexecCurrencies->getDefault();

            // Get module row id
            if ($package->module_row > 0) {
                $module_row_id = $package->module_row;
            } else {
                continue;
            }

            // Get service pricing id
            $pricing_id = null;
            $pricing = $this->ClientexecServices->getServicePricing($service->id);

            foreach ($package->pricing as $price) {
                if ($price->term == $pricing['term'] && $price->period == $pricing['period'] && $price->currency == $pricing['currency']) {
                    $pricing_id = $price->id;
                    $pricing = $price;
                    break;
                }
            }

            // Create service
            $status = $this->ClientexecServices->getServiceStatus($service->id);
            $renew_date = $this->ClientexecServices->getServiceNextRenewDate($service->id);

            $vars = [
                'parent_service_id' => null,
                'package_group_id' => null,
                'id_format' => '{num}',
                'id_value' => $service->id,
                'pricing_id' => $pricing_id,
                'client_id' => $this->mappings['clients'][$service->customerid],
                'module_row_id' => $module_row_id,
                'coupon_id' => null,
                'qty' => 1,
                'override_price' => $service->use_custom_price == '1' ? $service->custom_price : null,
                'override_currency' => null,
                'status' => $status,
                'date_added' => $this->Companies->dateToUtc($service->dateactivated),
                'date_renews' => !empty($renew_date) ? $this->Companies->dateToUtc($renew_date) : null,
                'date_last_renewed' => null,
                'date_suspended' => null,
                'date_canceled' => null
            ];
            $this->local->insert('services', $vars);
            $service_id = $this->local->lastInsertId();
            $this->mappings['services'][$service->id] = $service_id;

            // Import service fields
            $fields = [
                'id' => $service->id
            ];
            $service_fields = $this->ClientexecServices->getServiceFields($service->id);

            foreach ($service_fields as $service_field) {
                $fields[$service_field->name] = $service_field->value;
            }

            $this->addServiceFields($fields, $mapping);
        }
    }

    /**
     * Adds service fields.
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
                'value' => $value != null ? $value : '',
                'serialized' => $field->serialized,
                'encrypted' => $field->encrypted
            ];
            $this->local->insert('service_fields', $vars);
        }
    }

    /**
     * Import support departments.
     */
    protected function importSupportDepartments()
    {
        // Load required models
        Loader::loadModels($this, ['PluginManager', 'Staff']);

        // Install support plugin if not already installed
        if (!$this->PluginManager->isInstalled('support_manager', Configure::get('Blesta.company_id'))) {
            $this->PluginManager->add(
                ['dir' => 'support_manager', 'company_id' => Configure::get('Blesta.company_id')]
            );
        }

        // Load remote model
        $this->loadModel('ClientexecSupportDepartments');
        $this->loadModel('ClientexecSettings');

        // Get support departments
        $departments = $this->ClientexecSupportDepartments->get();

        // Import support departments
        foreach ($departments as $department) {
            // Get department staff
            $staff = $this->ClientexecSupportDepartments->getDepartmentStaff($department->id);

            // Get department email
            $email = null;

            if (empty($department->emails_to_notify)) {
                // Get the email address of the first staff member of the department
                $staff_id = $this->mappings['staff'][$staff[0]->member_id];
                $email = $this->Staff->get($staff_id)->email;
            } else {
                $emails = explode(',', $department->emails_to_notify);
                $email = $emails[0];
            }

            // Create department
            $vars = [
                'company_id' => Configure::get('Blesta.company_id'),
                'name' => $this->decode($department->name),
                'description' => $this->decode($department->name . ' Department'),
                'email' => $email,
                'method' => 'none',
                'security' => 'none',
                'mark_messages' => 'read',
                'default_priority' => 'medium',
                'clients_only' => 1,
                'override_from_email' => 0,
                'status' => 'visible'
            ];
            $this->local->insert('support_departments', $vars);
            $department_id = $this->local->lastInsertId();
            $this->mappings['support_departments'][$department->id] = $department_id;

            // Add staff members to department
            foreach ($staff as $memeber) {
                $vars = [
                    'department_id' => $department_id,
                    'staff_id' => $this->mappings['staff'][$memeber->member_id]
                ];
                $this->local->duplicate('staff_id', '=', $this->mappings['staff'][$memeber->member_id])->
                    insert('support_staff_departments', $vars);

                // Add schedules
                $days = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
                foreach ($days as $day) {
                    $vars = [
                        'staff_id' => $this->mappings['staff'][$memeber->member_id],
                        'company_id' => Configure::get('Blesta.company_id'),
                        'day' => $day,
                        'start_time' => '00:00:00',
                        'end_time' => '00:00:00'
                    ];
                    $this->local->duplicate('staff_id', '=', $this->mappings['staff'][$memeber->member_id])->
                        insert('support_staff_schedules', $vars);
                }

                // Add notices
                $vars = [
                    'key' => 'ticket_emails',
                    'company_id' => Configure::get('Blesta.company_id'),
                    'staff_id' => $this->mappings['staff'][$memeber->member_id],
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
                $this->local->duplicate('staff_id', '=', $this->mappings['staff'][$memeber->member_id])
                    ->insert('support_staff_settings', $vars);
            }
        }
    }

    /**
     * Import support tickets.
     */
    protected function importSupportTickets()
    {
        // Load required models
        Loader::loadModels($this, ['Companies']);

        // Load remote model
        $this->loadModel('ClientexecSupportTickets');

        $priorities = [
            '1' => 'high',
            '2' => 'medium',
            '3' => 'low'
        ];
        $statuses = [
            '0' => 'open',
            '1' => 'open',
            '2' => 'in_progress',
            '3' => 'awaiting_reply',
            '4' => 'closed',
        ];

        // Get support tickets
        $tickets = $this->ClientexecSupportTickets->get();

        // Import tickets
        foreach ($tickets as $ticket) {
            $vars = [
                'code' => is_numeric($ticket->id) ? (int) $ticket->id : preg_replace('/[^0-9]+/', '', $ticket->id),
                'department_id' => isset($this->mappings['support_departments'][$ticket->assignedtodeptid]) ? $this->mappings['support_departments'][$ticket->assignedtodeptid] : 0,
                'staff_id' => isset($this->mappings['staff'][$ticket->assignedtoid]) ? $this->mappings['staff'][$ticket->assignedtoid] : null,
                'service_id' => null,
                'client_id' => $ticket->userid > 0 && isset($this->mappings['clients'][$ticket->userid]) ? $this->mappings['clients'][$ticket->userid] : null,
                'email' => null,
                'summary' => $this->decode($ticket->subject),
                'priority' => isset($priorities[$ticket->priority]) ? $priorities[$ticket->priority] : 'medium',
                'status' => isset($statuses[$ticket->status]) ? $statuses[$ticket->status] : 'open',
                'date_added' => $this->Companies->dateToUtc($ticket->datesubmitted),
                'date_updated' => $this->Companies->dateToUtc($ticket->lastlog_datetime),
                'date_closed' => isset($statuses[$ticket->status]) && $statuses[$ticket->status] == 'closed' ? $this->Companies->dateToUtc($ticket->lastlog_datetime) : null,
            ];

            $this->local->insert('support_tickets', $vars);
            $this->mappings['support_tickets'][$ticket->id] = $this->local->lastInsertId();

            // Import ticket replies
            $responses = $this->ClientexecSupportTickets->getTicketReplies($ticket->id);
            foreach ($responses as $response) {
                if ($response->logtype == '0') {
                    $vars = [
                        'ticket_id' => $this->mappings['support_tickets'][$response->troubleticketid],
                        'staff_id' => isset($this->mappings['staff'][$response->userid]) ? $this->mappings['staff'][$response->userid] : null,
                        'type' => 'reply',
                        'details' => $this->decode($response->message),
                        'date_added' => $this->Companies->dateToUtc($response->mydatetime),
                    ];
                    $this->local->insert('support_replies', $vars);
                }
            }
        }

        // Get canned responses
        $responses = $this->ClientexecSupportTickets->getCannedResponses();

        // Create a group for imported canned responses
        $vars = [
            'company_id' => Configure::get('Blesta.company_id'),
            'name' => 'Imported'
        ];
        $this->local->insert('support_response_categories', $vars);
        $category_id = $this->local->lastInsertId();

        // Import canned respones
        foreach ($responses as $response) {
            $vars = [
                'category_id' => $category_id,
                'name' => $this->decode($response->name),
                'details' => $this->decode($response->response)
            ];
            $this->local->insert('support_responses', $vars);
        }
    }

    /**
     * Import knowledge base.
     */
    protected function importKnowledgeBase()
    {
        // Load required models
        Loader::loadModels($this, ['Companies']);

        // Load remote model
        $this->loadModel('ClientexecKnowledgeBase');

        // Get knowledge base categories
        $categories = $this->ClientexecKnowledgeBase->get();

        // Import knowledge base categories
        foreach ($categories as $category) {
            $vars = [
                'company_id' => Configure::get('Blesta.company_id'),
                'name' => $this->decode($category->name),
                'description' => $this->decode($category->description),
                'access' => $category->staffonly == '1' ? 'hidden' : 'public',
                'date_created' => $this->Companies->dateToUtc(date('c')),
                'date_updated' => $this->Companies->dateToUtc(date('c'))
            ];
            $this->local->insert('support_kb_categories', $vars);
            $category_id = $this->local->lastInsertId();

            // Import category articles
            $articles = $this->ClientexecKnowledgeBase->getArticles($category->id);

            foreach ($articles as $article) {
                $vars = [
                    'company_id' => Configure::get('Blesta.company_id'),
                    'access' => $article->access == '2' ? 'public' : 'hidden',
                    'date_created' => $this->Companies->dateToUtc($article->created),
                    'date_updated' => $this->Companies->dateToUtc($article->modified)
                ];
                $this->local->insert('support_kb_articles', $vars);
                $article_id = $this->local->lastInsertId();

                $vars = [
                    'article_id' => $article_id,
                    'lang' => 'en_us',
                    'title' => $this->decode($article->title),
                    'body' => $this->decode($article->content),
                    'content_type' => 'html'
                ];
                $this->local->insert('support_kb_article_content', $vars);

                $vars = [
                    'category_id' => $category_id,
                    'article_id' => $article_id
                ];
                $this->local->insert('support_kb_article_categories', $vars);
            }
        }
    }

    /**
     * Import coupons.
     */
    protected function importCoupons()
    {
        // Load required models
        Loader::loadModels($this, ['Companies', 'Packages']);

        // Load remote model
        $this->loadModel('ClientexecCoupons');
        $this->loadModel('ClientexecCurrencies');

        // Get coupons
        $coupons = $this->ClientexecCoupons->get();

        // Get coupons packages
        $coupons_packages = $this->ClientexecCoupons->getCouponsPackages();

        // Get default currency
        $currency = $this->ClientexecCurrencies->getDefault();

        // Import coupons
        foreach ($coupons as $coupon) {
            $vars = [
                'code' => $coupon->coupons_code,
                'company_id' => Configure::get('Blesta.company_id'),
                'used_qty' => 0,
                'max_qty' => empty($coupon->coupons_quantity) ? 1000 : $coupon->coupons_quantity,
                'start_date' => $this->Companies->dateToUtc($coupon->coupons_start),
                'end_date' => $this->Companies->dateToUtc($coupon->coupons_expires),
                'status' => 'active',
                'recurring' => $coupon->coupons_recurring,
                'limit_recurring' => $coupon->coupons_recurringmonths
            ];
            $this->local->insert('coupons', $vars);
            $this->mappings['coupons'][$coupon->coupons_id] = $this->local->lastInsertId();

            // Add coupon amount
            $vars = [
                'coupon_id' => $this->mappings['coupons'][$coupon->coupons_id],
                'currency' => $currency,
                'amount' => $coupon->coupons_discount < 1 ? ($coupon->coupons_discount * 100) : $coupon->coupons_discount,
                'type' => $coupon->coupons_discount < 1 ? 'percent' : 'amount'
            ];
            $this->local->insert('coupon_amounts', $vars);
        }

        // Import coupons packages
        foreach ($coupons_packages as $coupon_package) {
            $packages = $this->Packages->getAllPackagesByGroup($this->mappings['package_groups'][$coupon_package->promotion_id]);

            foreach ($packages as $package) {
                $vars = [
                    'coupon_id' => $this->mappings['coupons'][$coupon_package->coupons_id],
                    'package_id' => $package->id,
                ];
                $this->local->insert('coupon_packages', $vars);
            }
        }
    }

    /**
     * Import settings.
     */
    protected function importSettings()
    {
        // Load required models
        Loader::loadModels($this, ['Settings']);

        // Load remote model
        $this->loadModel('ClientexecSettings');

        $setting_fields = [
            'smtp_host' => 'smtp_host',
            'smtp_password' => 'smtp_password',
            'smtp_port' => 'smtp_port',
            'smtp_username' => 'smtp_user'
        ];

        // Get settings
        $settings = $this->ClientexecSettings->get();

        // Import settings
        foreach ($settings as $setting) {
            if (isset($setting_fields[$setting->name])) {
                $this->Settings->setSetting($setting_fields[$setting->name], $setting->value);
            }
        }
    }

    /**
     * Returns the currency format.
     *
     * @param mixed $currency WHMCS currency format value
     * @return string Blesta currency format value
     */
    private function getCurrencyFormat($currency)
    {
        $format = '#' . ($currency->thousandssep == 'space' ? ' ' : $currency->thousandssep) . '###';

        if ($currency->precision > 0) {
            $format = $format . ($currency->decimalssep == 'space' ? ' ' : $currency->decimalssep) . '##';
        }

        return $format;
    }

    /**
     * Decodes the HTML entities and UTF8 characters of the given string
     *
     * @param string $str The string to decode
     * @return string The decoded string
     */
    protected function decode($str)
    {
        if ($str === null) {
            return $str;
        }
        return utf8_encode(html_entity_decode($str, ENT_QUOTES, 'UTF-8'));
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
}
