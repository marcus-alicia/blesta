<?php
require_once dirname(__FILE__) . DS . '..' . DS . 'whmcs_migrator.php';

/**
 * WHMCS 8.0 Migrator
 *
 * @package blesta
 * @subpackage blesta.plugins.import_manager.components.migrators.whmcs
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Whmcs8_0 extends WhmcsMigrator
{
    /**
     * Construct
     *
     * @param Record $local The database connection object to the local server
     */
    public function __construct(Record $local)
    {
        parent::__construct($local);

        set_time_limit(60*60*15); // 15 minutes

        Language::loadLang(['whmcs8_0'], null, dirname(__FILE__) . DS . 'language' . DS);

        Loader::loadModels($this, ['Companies']);

        $this->path = dirname(__FILE__);
    }

    /**
     * Processes settings (validating input). Sets any necessary input errors
     *
     * @param array $vars An array of key/value input pairs
     */
    public function processSettings(array $vars = null)
    {
        $rules = [
            'host' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => 'true',
                    'message' => Language::_('Whmcs8_0.!error.host.invalid', true)
                ]
            ],
            'database' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => 'true',
                    'message' => Language::_('Whmcs8_0.!error.database.invalid', true)
                ]
            ],
            'user' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => 'true',
                    'message' => Language::_('Whmcs8_0.!error.user.invalid', true)
                ]
            ],
            'pass' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => 'true',
                    'message' => Language::_('Whmcs8_0.!error.pass.invalid', true)
                ]
            ]
        ];

        $this->Input->setRules($rules);

        if (!$this->Input->validates($vars)) {
            return;
        }

        if (isset($vars['enable_debug']) && $vars['enable_debug'] == 'true') {
            $this->enable_debug = true;
        }

        $this->settings = $vars;

        $default = [
            'driver' => 'mysql',
            'host' => null,
            'database' => null,
            'user' => null,
            'pass' => null,
            'persistent' => false,
            'charset_query' => "SET NAMES 'utf8'",
            'sqlmode_query' => "SET sql_mode='TRADITIONAL'",
            'options' => ['sql_mode' => null]
        ];
        $db_info = array_merge($default, $vars);

        try {
            $this->remote = new Record($db_info);
            $this->remote->query("SET NAMES utf8");
            $this->remote->query("SET sql_mode='TRADITIONAL'");
        } catch (Throwable $e) {
            $this->Input->setErrors([[$e->getMessage()]]);
            $this->logException($e);
            return;
        }
    }

    /**
     * Processes configuration (validating input). Sets any necessary input errors
     *
     * @param array $vars An array of key/value input pairs
     */
    public function processConfiguration(array $vars = null)
    {
        // Set mapping for packages (remote ID => local ID)
        if (isset($vars['create_packages']) && $vars['create_packages'] == 'false') {
            $this->mappings['packages'] = [];
            if (isset($vars['remote_packages'])) {
                foreach ($vars['remote_packages'] as $i => $package_id) {
                    $this->mappings['packages'][$package_id] = $vars['local_packages'][$i] == ''
                        ? null
                        : $vars['local_packages'][$i];
                }
            }
        }
    }

    /**
     * Returns a view to handle settings
     *
     * @param array $vars An array of input key/value pairs
     * @return string The HTML used to request input settings
     */
    public function getSettings(array $vars)
    {
        $this->view = $this->makeView('settings', 'default', str_replace(ROOTWEBDIR, '', dirname(__FILE__) . DS));
        $this->view->set('vars', (object)$vars);

        Loader::loadHelpers($this, ['Html', 'Form']);

        return $this->view->fetch();
    }

    /**
     * Returns a list settings
     *
     * @return array The input settings
     */
    public function getCliSettings()
    {
        return [
            [
                'label' => Language::_("Whmcs8_0.settings.host", true),
                'field' => 'host',
                'type' => 'text'
            ],
            [
                'label' => Language::_("Whmcs8_0.settings.database", true),
                'field' => 'database',
                'type' => 'text'
            ],
            [
                'label' => Language::_("Whmcs8_0.settings.user", true),
                'field' => 'user',
                'type' => 'text'
            ],
            [
                'label' => Language::_("Whmcs8_0.settings.pass", true),
                'field' => 'pass',
                'type' => 'text'
            ],
            [
                'label' => Language::_("Whmcs8_0.settings.key", true),
                'field' => 'key',
                'type' => 'text'
            ],
            [
                'label' => Language::_("Whmcs8_0.settings.balance_credit", true),
                'field' => 'balance_credit',
                'type' => 'bool'
            ],
            [
                'label' => Language::_("Whmcs8_0.settings.enable_debug", true),
                'field' => 'enable_debug',
                'type' => 'bool'
            ],
        ];
    }

    /**
     * Returns a view to configuration run after settings but before import
     *
     * @param array $vars An array of input key/value pairs
     * @return string The HTML used to request input settings, return null to bypass
     */
    public function getConfiguration(array $vars)
    {
        $this->view = $this->makeView('configuration', 'default', str_replace(ROOTWEBDIR, '', dirname(__FILE__) . DS));
        $this->view->set('vars', (object)$vars);

        Loader::loadHelpers($this, ['Html', 'Form']);
        Loader::loadModels($this, ['Packages']);

        if ($this->remote) {
            $this->loadModel('WhmcsProducts');
            $remote_packages = [];

            foreach ($this->WhmcsProducts->get() as $remote_package) {
                $remote_packages[] = $remote_package;
            }

            $this->view->set('remote_packages', $remote_packages);
            $this->view->set(
                'local_packages',
                $this->Packages->getAll(Configure::get('Blesta.company_id'), ['name' => 'ASC'], null, 'standard')
            );
        }

        return $this->view->fetch();
    }

    /**
     * Returns the module mapping file for the given module, or for the none module if module does not exist
     *
     * @param string $module The module
     * @param string $module_type The module type ('server' or 'registrar')
     * @return array An array of mapping data
     */
    protected function getModuleMapping($module, $module_type = 'server')
    {
        Configure::load($module, dirname(__FILE__) . DS . 'config' . DS);

        if (!is_array(Configure::get($module . '.map'))) {
            $version = abs((int)filter_var($module, FILTER_SANITIZE_NUMBER_INT));
            $module = substr($module, 0, strpos($module, (string)$version));

            Configure::load($module, dirname(__FILE__) . DS . 'config' . DS);
        }

        if (!is_array(Configure::get($module . '.map'))) {
            $module = 'generic_' . $module_type;
            Configure::load($module, dirname(__FILE__) . DS . 'config' . DS);
        }

        return Configure::get($module . '.map');
    }

    /**
     * Returns the gateway mapping file for the given gateway, or null if gateway does not exist.
     *
     * @param string $gateway The gateway
     * @param string $gateway_type The gateway type ('merchant' or 'nonmerchant')
     * @return array An array of mapping data
     */
    protected function getGatewayMapping($gateway, $gateway_type = 'nonmerchant')
    {
        Configure::load($gateway, dirname(__FILE__) . DS . 'config' . DS);

        if (!is_array(Configure::get($gateway . '.map'))) {
            $version = abs((int)filter_var($gateway, FILTER_SANITIZE_NUMBER_INT));
            $gateway = substr($gateway, 0, strpos($gateway, (string)$version));

            Configure::load($gateway, dirname(__FILE__) . DS . 'config' . DS);
        }

        if (is_array(Configure::get($gateway . '.map'))) {
            return Configure::get($gateway . '.map');
        }

        return [];
    }

    /**
     * Import clients
     */
    protected function importClients()
    {
        Loader::loadModels($this, ['Accounts', 'Clients', 'ClientGroups']);
        $this->loadModel('WhmcsClients');

        // Set default client group
        $client_groups = $this->ClientGroups->getAll(Configure::get('Blesta.company_id'));
        $this->mappings['client_groups'][0] = isset($client_groups[0]->id) ? $client_groups[0]->id : null;

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
        $client_emails = [];
        foreach ($clients as $client) {
            // Create user
            $user_id = null;
            try {
                $email = in_array($client->user_email, $client_emails)
                    ? (in_array($client->email, $client_emails)
                        ? $client->id . md5($client->id)
                        : $this->decode($client->email)
                    )
                    : $this->decode($client->user_email);
                $user_id = $this->createUser([
                    'username' => $email,
                    'password' => substr($client->password, 0, 64),
                    'date_added' => $this->getValidDate($client->datecreated)
                ]);
                $client_emails[] = $email;
            } catch (Throwable $e) {
                $this->logException($e);
            }
            if (!$user_id) {
                continue;
            }

            // Create client
            $client_id = null;
            try {
                $vars = [
                    'id_format' => '{num}',
                    'id_value' => $client->id,
                    'user_id' => $user_id,
                    'client_group_id' => $this->mappings['client_groups'][$client->groupid],
                    'status' => strtolower($client->status) == 'closed' ? 'inactive' : 'active'
                ];
                $this->local->insert('clients', $vars);
                $client_id = $this->local->lastInsertId();
            } catch (Throwable $e) {
                $this->logException($e);
            }
            if (!$client_id) {
                continue;
            }

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

            if ($client->cardnum != '') {
                $client->cardnum = $this->decrypt('client', $client->cardnum, $client->id);
            }
            if ($client->expdate != '') {
                $client->expdate = $this->decrypt('client', $client->expdate, $client->id);
            }
            if ($client->bankacct != '') {
                $client->bankacct = $this->decrypt('client', $client->bankacct, $client->id);
            }
            if ($client->bankcode != '') {
                $client->bankcode = $this->decrypt('client', $client->bankcode, $client->id);
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
                        $this->logException($e);
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

        // Import payment gateways
        $this->importPaymentGateways();

        // Import credit cards
        $this->importCreditCards();
    }

    /**
     * Import payment gateways
     */
    private function importPaymentGateways()
    {
        Loader::loadModels($this, ['GatewayManager']);
        $this->loadModel('WhmcsPaymentGateways');

        // Import payment gateways
        $payment_gateways = $this->fetchall
            ? $this->WhmcsPaymentGateways->get()->fetchAll()
            : $this->WhmcsPaymentGateways->get();
        $gateways = [];

        foreach ($payment_gateways as $payment_gateway) {
            $gateways[$payment_gateway->gateway][$payment_gateway->setting] = $payment_gateway->value;
        }

        foreach ($gateways as $gateway => $settings) {
            // Import gateway mapping
            $mapping = $this->getGatewayMapping($gateway);

            if (empty($mapping) || empty($settings)) {
                continue;
            }

            // Check if the gateway is already installed
            if ($this->GatewayManager->isInstalled(
                $mapping['gateway'],
                $mapping['type'],
                Configure::get('Blesta.company_id')
            )) {
                $local_gateway = $this->GatewayManager->getByClass(
                    $mapping['gateway'],
                    Configure::get('Blesta.company_id')
                );
                $gateway_id = !is_null($local_gateway[0]->id) ? $local_gateway[0]->id : null;

                $this->GatewayManager->delete($gateway_id);
            }

            // Install gateway
            $gateway_id = $this->GatewayManager->add([
                'class' => $mapping['gateway'],
                'type' => $mapping['type'],
                'company_id' => Configure::get('Blesta.company_id')
            ]);

            // Import settings
            if (!is_null($gateway_id)) {
                foreach ($mapping['gateway_meta'] as $gateway_field) {
                    $field = [
                        'gateway_id' => $gateway_id,
                        'key' => $gateway_field->key,
                        'value' => $settings[$gateway_field->value],
                        'encrypted' => $gateway_field->encrypted
                    ];

                    if ($gateway_field->decrypt) {
                        $field['value'] = $this->decrypt(
                            'gateway',
                            $gateway,
                            $gateway_field->value,
                            $field['value']
                        );
                    }

                    if (isset($gateway_field->callback)) {
                        $field['value'] = call_user_func_array($gateway_field->callback, [$field['value'], (array)$mapping['gateway_meta']]);
                    }

                    if ($gateway_field->encrypted) {
                        $field['value'] = $this->GatewayManager->systemEncrypt($field['value']);
                    }

                    $this->local->insert('gateway_meta', $field);
                }

                $this->mappings['gateway'][$gateway] = $gateway_id;
            }
        }
    }

    /**
     * Import Credit Cards
     */
    private function importCreditCards()
    {
        Loader::loadModels($this, ['Accounts', 'GatewayManager']);
        $this->loadModel('WhmcsCreditCards');

        // Import credit cards
        $credit_cards = $this->fetchall ? $this->WhmcsCreditCards->get()->fetchAll() : $this->WhmcsCreditCards->get();
        foreach ($credit_cards as $credit_card) {
            // Import gateway mapping
            $mapping = $this->getGatewayMapping($credit_card->gateway_name);

            if (empty($mapping) || empty($credit_card)) {
                continue;
            }

            // Get Stripe gateway
            $gateway = $this->GatewayManager->getByClass(
                $mapping['gateway'],
                Configure::get('Blesta.company_id')
            );

            if (isset($gateway[0]->id)) {
                $gateway_id = !is_null($gateway[0]->id) ? $gateway[0]->id : null;
            }

            // Import credit cards
            if (!is_null($gateway_id)) {
                $credit_card->card_data = $this->decrypt('cc', $credit_card->card_data, $credit_card->client_id);

                if (
                    ($credit_card->card_data = json_decode($credit_card->card_data))
                    && json_last_error() === JSON_ERROR_NONE
                ) {
                    $credit_card = (object) array_merge((array) $credit_card, (array) $credit_card->card_data);

                    foreach ($credit_card as $property => $value) {
                        if (!is_scalar($value)) {
                            $credit_card = (object) array_merge((array) $credit_card, (array) $value);
                        }
                    }

                    // Save card
                    $card = [
                        'contact_id' => $this->mappings['primary_contacts'][$credit_card->client_id],
                        'first_name' => $credit_card->{$mapping['accounts_cc']['first_name']} ?? $this->default_firstname,
                        'last_name' => $credit_card->{$mapping['accounts_cc']['last_name']} ?? $this->default_lastname,
                        'address1' => $credit_card->{$mapping['accounts_cc']['address1']} ?? '',
                        'address2' => $credit_card->{$mapping['accounts_cc']['address2']} ?? null,
                        'state' => !empty($credit_card->{$mapping['accounts_cc']['state']})
                            ? $this->getValidState(
                                $credit_card->{$mapping['accounts_cc']['country']},
                                $credit_card->{$mapping['accounts_cc']['state']}
                            )
                            : $this->default_state,
                        'city' => $credit_card->{$mapping['accounts_cc']['city']} ?? '',
                        'zip' => $credit_card->{$mapping['accounts_cc']['zip']} ?? '00000',
                        'country' => $credit_card->{$mapping['accounts_cc']['country']} ?? $this->default_country,
                        'number' => isset($credit_card->{$mapping['accounts_cc']['number']})
                            ? $this->GatewayManager->systemEncrypt(
                                $credit_card->{$mapping['accounts_cc']['number']}
                            )
                            : null,
                        'expiration' => isset($credit_card->{$mapping['accounts_cc']['expiration']})
                            ? $this->GatewayManager->systemEncrypt(
                                date('Ym', strtotime($credit_card->{$mapping['accounts_cc']['expiration']}))
                            )
                            : null,
                        'last4' => isset($credit_card->{$mapping['accounts_cc']['last4']})
                            ? $this->GatewayManager->systemEncrypt(
                                $credit_card->{$mapping['accounts_cc']['last4']}
                            )
                            : null,
                        'type' => isset($credit_card->{$mapping['accounts_cc']['type']})
                            ? $this->getCreditCardType($credit_card->{$mapping['accounts_cc']['type']})
                            : 'other',
                        'gateway_id' => $gateway_id,
                        'client_reference_id' => $credit_card->{$mapping['accounts_cc']['client_reference_id']} ?? null,
                        'reference_id' => $credit_card->{$mapping['accounts_cc']['reference_id']} ?? null,
                        'status' => 'active'
                    ];

                    $this->local->insert('accounts_cc', $card);
                }
            }
        }
    }
}
