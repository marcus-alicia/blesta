<?php
use Blesta\Core\Util\Common\Traits\Container;

/**
 * ClientDataPortability report
 *
 * @package blesta
 * @subpackage blesta.components.reports.client_data_portability
 * @copyright Copyright (c) 2018, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ClientDataPortability implements ReportInterface
{
    // Load traits
    use Container;

    /**
     * Load language
     */
    public function __construct()
    {
        Loader::loadModels(
            $this,
            ['Clients', 'Services', 'Contacts', 'Users', 'Invoices', 'Transactions', 'Logs', 'Accounts']
        );
        Loader::loadComponents($this, ['Input']);

        // Load the language required by this report
        Language::loadLang('client_data_portability', null, dirname(__FILE__) . DS . 'language' . DS);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return Language::_('ClientDataPortability.name', true);
    }

    /**
     * {@inheritdoc}
     */
    public function getFormats()
    {
        return ['json'];
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions($company_id, array $vars = [])
    {
        Loader::loadHelpers($this, ['Javascript']);

        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('options', 'default');
        $this->view->setDefaultView('components' . DS . 'reports' . DS . 'client_data_portability' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $this->view->set('vars', (object)$vars);

        return $this->view->fetch();
    }

    /**
     * {@inheritdoc}
     */
    public function getKeyInfo()
    {
        return [
            'id_code' => [],
            'status' => [],
            'children' => [
                'accounts_ach' => [
                    'first_name' => [],
                    'last_name' => [],
                    'address1' => [],
                    'address2' => [],
                    'city' => [],
                    'state' => [],
                    'zip' => [],
                    'country' => [],
                    'last4' => [],
                    'type' => [],
                    'status' => []
                ],
                'accounts_cc' => [
                    'first_name' => [],
                    'last_name' => [],
                    'address1' => [],
                    'address2' => [],
                    'city' => [],
                    'state' => [],
                    'zip' => [],
                    'country' => [],
                    'last4' => [],
                    'type' => [],
                    'status' => []
                ],
                'contacts' => [
                    'contact_type' => [],
                    'first_name' => [],
                    'last_name' => [],
                    'title' => [],
                    'company' => [],
                    'email' => [],
                    'address1' => [],
                    'address2' => [],
                    'city' => [],
                    'state' => [],
                    'zip' => [],
                    'country' => [],
                    'children' => [
                        'user' => [
                            'username' => [],
                            'two_factor_mode' => [],
                            'date_added' => []
                        ]
                    ]
                ],
                'invoices' => [
                    'id_code' => [],
                    'date_billed' => [],
                    'date_due' => [],
                    'date_closed' => [],
                    'date_autodebit' => [],
                    'autodebit' => [],
                    'status' => [],
                    'subtotal' => [],
                    'total' => [],
                    'paid' => [],
                    'previous_due' => [],
                    'currency' => [],
                    'note_public' => [],
                    'children' => [
                        'line_items' => [
                            'description' => [],
                            'qty' => [],
                            'amount' => [],
                            'taxes_applied' => [
                                'children' => [
                                    'taxes_applied' => [
                                        'name' => [],
                                        'percentage' => [],
                                        'amount' => []
                                    ]
                                ]
                            ],
                            'tax_subtotal' => [],
                            'tax_total' => [],
                            'total' => [],
                            'total_w_tax' => []
                        ]
                    ]
                ],
                'log_contacts' => [
                    'change' => [],
                    'date_changed' => [],
                    'first_name' => [],
                    'last_name' => [],
                ],
                'log_users' => [
                    'ip_address' => [],
                    'date_added' => [],
                    'result' => []
                ],
                'services' => [
                    'id_code' => [],
                    'qty' => [],
                    'override_price' => [],
                    'override_currency' => [],
                    'status' => [],
                    'date_added' => [],
                    'name' => [],
                    'children' => [
                        'fields' => [
                            'key' => [],
                            'value' => []
                        ],
                        'package_pricing' => [
                            'term' => [],
                            'period' => [],
                            'price' => [],
                            'setup_fee' => [],
                            'cancel_fee' => [],
                            'currency' => []
                        ],
                        'package' => [
                            'name' => [],
                            'description' => [],
                            'description_html' => [],
                            'qty' => [],
                            'taxable' => [],
                            'single_term' => [],
                            'status' => [],
                            'prorata_day' => [],
                            'prorata_cutoff' => []
                        ]
                    ]
                ],
                'transactions' => [
                    'amount' => [],
                    'currency' => [],
                    'type' => [],
                    'status' => [],
                    'date_added' => [],
                    'applied_amount' => [],
                    'type_name' => [],
                    'gateway_name' => [],
                    'gateway_type' => [],
                    'credited_amount' => []
                ]
            ],
            'additional_information' => []
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAll($company_id, array $vars)
    {
        // Get the client
        $client = isset($vars['client_id']) ? $this->Clients->get($vars['client_id'], false) : null;
        if (!$client || $client->company_id != Configure::get('Blesta.company_id')) {
            !$this->Input->setErrors(
                ['valid' => ['client_id' => Language::_('ClientDataPortability.!error.client_id', true)]]
            );

            return;
        }

        // Get all contacts connected to this client
        $client->contacts = $this->Contacts->getAll($vars['client_id']);
        foreach ($client->contacts as &$contact) {
            if (($user = $this->Users->get(
                $contact->contact_type == 'primary' ? $client->user_id : $contact->user_id
            ))) {
                $contact->user = $user;
            } else {
                $contact->user = null;
            }
        }

        // Get all invoices associated with this client and their line items
        $client->invoices = $this->Invoices->getAll($vars['client_id'], 'all');
        foreach ($client->invoices as &$invoice) {
            $invoice->line_items = $this->Invoices->getLineItems($invoice->id);
        }

        // Get all services associated with this client
        $client->services = $this->Services->getAllByClient($vars['client_id'], 'all');

        // Unset encrypted service fields
        foreach ($client->services as &$service) {
            foreach ($service->fields as $key => $field) {
                if ($field->encrypted == '1') {
                    unset($service->fields[$key]);
                }
            }
        }

        // Get all services associated with this client
        $client->transactions = [];
        $i = 1;
        while ($transactions = $this->Transactions->getList($vars['client_id'], 'all', $i++)) {
            // Unset gateway names for cc transations
            foreach ($transactions as &$transaction) {
                if ($transaction->type == 'cc') {
                    $transaction->gateway_name = null;
                }
            }

            $client->transactions = array_merge($client->transactions, $transactions);
        }

        // Get all contact and user logs associated with this client
        $client->log_contacts = [];
        $j = 1;
        while (($contact_logs = $this->Logs->searchContactLogs(['client_id' => $vars['client_id']], $j++))) {
            $client->log_contacts = array_merge($client->log_contacts, $contact_logs);
        }

        $client->log_users = [];
        $k = 1;
        while (($user_logs = $this->Logs->searchUserLogs(['client_id' => $vars['client_id']], $k++))) {
            $client->log_users = array_merge($client->log_users, $user_logs);
        }

        // Get all payment accounts associated with this client
        $client->accounts_ach = $this->Accounts->getAllAchByClient($vars['client_id']);
        $client->accounts_cc = $this->Accounts->getAllCcByClient($vars['client_id']);

        // Get all plugin data associated with this client
        $eventFactory = $this->getFromContainer('util.events');
        $eventListener = $eventFactory->listener();
        $eventListener->register('Report.clientData');
        $event = $eventFactory->event('Report.clientData', ['client_id' => $vars['client_id']]);
        $event->setReturnValue(['extra' => []]);
        $event = $eventListener->trigger($event);

        $plugin_return = $event->getReturnValue();
        $client->additional_information = isset($plugin_return['extra']) ? $plugin_return['extra'] : [];

        return [$client];
    }

    /**
     * Returns any errors
     *
     * @return mixed An array of errors, false if no errors set
     */
    public function errors()
    {
        return $this->Input->errors();
    }
}
