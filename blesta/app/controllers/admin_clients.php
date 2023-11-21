<?php

use Blesta\Core\Pricing\Presenter\Type\PresenterInterface;
use Blesta\Core\Util\Filters\ClientFilters;
use Blesta\Core\Util\Filters\ServiceFilters;
use Blesta\Core\Util\Filters\InvoiceFilters;
use Blesta\Core\Util\Filters\QuotationFilters;
use Blesta\Core\Util\Filters\TransactionFilters;
use Blesta\Core\Util\Input\Fields\Html as FieldsHtml;
use Blesta\Core\Util\PackageOptions\Logic as OptionLogic;

/**
 * Admin Clients Management
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminClients extends AppController
{
    /**
     * @param array The current state of widgets to be displayed in the given view
     */
    private $widgets_state = [];
    /**
     * @var string The custom field prefix used in form names to keep them unique and easily referenced
     */
    private $custom_field_prefix = 'custom_field';

    /**
     * Pre-action setup method that is called before the index method, or the set controller action
     */
    public function preAction()
    {
        parent::preAction();

        // Require login
        $this->requireLogin();

        $this->uses(['Clients', 'Staff']);
        $this->helpers(['Color']);
        Language::loadLang(['admin_clients']);

        // Sets the page title for this client page
        if (isset($this->get[0])) {
            // Get the client id code, assuming this is the client ID
            if (($client = $this->Clients->get((int)$this->get[0]))) {
                // Attempt to set the page title language
                try {
                    $language = Language::_(
                        'AdminClients.'
                        . Loader::fromCamelCase($this->action ? $this->action : 'index') . '.page_title',
                        true,
                        $client->id_code
                    );
                    $this->structure->set('page_title', $language);
                } catch (Throwable $e) {
                    // Attempting to set the page title language has failed, likely due to
                    // the language definition requiring multiple parameters.
                    // Fallback to index. Assume the specific page will set its own page title otherwise.
                    $this->structure->set('page_title', Language::_('AdminClients.index.page_title', true));
                }

                $this->Javascript->setFile('date.min.js');
                $this->Javascript->setFile('jquery.datePicker.min.js');
                $this->Javascript->setInline(
                    'Date.firstDayOfWeek=' . ($client->settings['calendar_begins'] == 'sunday' ? 0 : 1) . ';'
                );
            }
        }
    }

    /**
     * Browse Clients
     */
    public function index()
    {
        $this->uses(['ClientGroups', 'SettingsCollection']);

        // Set filters from post input
        $post_filters = [];
        if (isset($this->post['filters'])) {
            $post_filters = $this->post['filters'];
            unset($this->post['filters']);

            foreach($post_filters as $filter => $value) {
                if (empty($value)) {
                    unset($post_filters[$filter]);
                }
            }
        }

        // Get the company settings
        $company_settings = $this->SettingsCollection->fetchSettings(null, $this->company_id);

        // Set current page of results
        $status = (isset($this->get[0]) ? $this->get[0] : 'active');
        $page = (isset($this->get[1]) ? (int)$this->get[1] : 1);
        $sort = (isset($this->get['sort']) ? $this->get['sort'] : 'id_code');
        $order = (isset($this->get['order']) ? $this->get['order'] : 'desc');

        // Set the number of clients of each type
        $status_count = [
            'active' => $this->Clients->getStatusCount('active', $post_filters),
            'inactive' => $this->Clients->getStatusCount('inactive', $post_filters),
            'fraud' => $this->Clients->getStatusCount('fraud', $post_filters)
        ];

        $clients = $this->Clients->getList($status, $page, [$sort => $order], $post_filters);
        $total_results = $this->Clients->getListCount($status, $post_filters);

        // Add client group info to each client
        $client_groups = [];
        for ($i = 0, $num_clients = count($clients); $i < $num_clients; $i++) {
            if (!array_key_exists($clients[$i]->client_group_id, (array)$client_groups)) {
                $client_groups[$clients[$i]->client_group_id] = $this->ClientGroups->get($clients[$i]->client_group_id);
            }

            $clients[$i]->group = $client_groups[$clients[$i]->client_group_id];
        }

        // Set the input field filters for the widget
        $client_filters = new ClientFilters();
        $this->set(
            'filters',
            $client_filters->getFilters(
                ['language' => Configure::get('Blesta.language'), 'company_id' => Configure::get('Blesta.company_id')],
                $post_filters
            )
        );

        $this->set('filter_vars', $post_filters);
        $this->set('clients', $clients);
        $this->set('status_count', $status_count);
        $this->set('status', $status);
        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));

        $this->Javascript->setFile('date.min.js');
        $this->Javascript->setFile('jquery.datePicker.min.js');
        $this->Javascript->setInline(
            'Date.firstDayOfWeek=' . ($company_settings['calendar_begins'] == 'sunday' ? 0 : 1) . ';'
        );

        // Set pagination parameters, set group if available
        $params = ['sort' => $sort,'order' => $order];

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination'),
            [
                'total_results' => $total_results,
                'uri' => $this->base_uri . 'clients/index/' . $status . '/[p]/',
                'params' => $params
            ]
        );
        $this->setPagination($this->get, $settings);

        // Render the request if ajax
        return $this->renderAjaxWidgetIfAsync(isset($this->get[1]) || isset($this->get['sort']));
    }

    /**
     * View a specific client profile, may optionally set what content to view within the content view.
     *
     * @param string $content The content to set in the content view of the client profile page
     */
    public function view($content = null)
    {
        // If this request was made via ajax render only the right container
        if ($content == null && $this->isAjax()) {
            header($this->server_protocol . ' 406 AJAX requests not supported by this resource');
            return false;
        }

        $this->uses(['ClientGroups', 'Contacts', 'Invoices', 'Logs', 'Actions', 'EmailVerifications']);

        // Ensure we have a client ID to load
        if (!isset($this->get[0]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            $this->redirect($this->base_uri . 'clients/');
        }

        // Fetch email verification status
        $email_verification = $this->EmailVerifications->getByContactId($client->contact_id);

        $client->settings['email_verification_status'] = 'unsent';
        if (isset($email_verification->verified)) {
            $client->settings['email_verification_status'] = ($email_verification->verified == 1 ? 'verified' : 'unverified');
        }

        // Get all contacts, excluding the primary
        $client->contacts = array_merge(
            $this->Contacts->getAll($client->id, 'billing'),
            $this->Contacts->getAll($client->id, 'other')
        );
        $client->numbers = $this->Contacts->getNumbers($client->contact_id);
        $client->note_count = $this->Clients->getNoteListCount($client->id);
        $client->group = $this->ClientGroups->get($client->client_group_id);

        // Include an international-formatted version of each number
        foreach ($client->numbers as $number) {
            $number->international = $this->Contacts->intlNumber($number->number, $client->country, ' ');
        }

        // Set any client sticky notes
        if ($content == null) {
            $sticky_note_list_vars = [
                'notes' => $this->Clients->getAllStickyNotes($client->id, Configure::get('Blesta.sticky_notes_max')),
                'number_notes_to_show' => Configure::get('Blesta.sticky_notes_to_show')
            ];

            $sticky_note_vars = [
                'sticky_notes' => $this->partial('admin_clients_stickynote_list', $sticky_note_list_vars)
            ];

            $this->set('sticky_notes', $this->partial('admin_clients_stickynotes', $sticky_note_vars));
        }

        // Set the last time this client was logged in successfully
        if (($user_log = $this->Logs->getUserLog($client->user_id, 'success'))) {
            $this->components(['SettingsCollection']);

            // Set last activity time language (in minutes) if within the last 30 minutes
            $user_activity_timestamp = $this->Date->toTime($user_log->date_updated);
            $last_activity = ($this->Date->toTime($this->Logs->dateToUtc(date('c'))) - $user_activity_timestamp) / 60;
            $thirty_minutes = 30;

            if ($last_activity < 1) {
                $user_log->last_activity = Language::_('AdminClients.view.tooltip_last_activity_now', true);
            } elseif ($last_activity == 1) {
                $user_log->last_activity = Language::_('AdminClients.view.tooltip_last_activity_minute', true);
            } elseif ($last_activity <= $thirty_minutes) {
                $user_log->last_activity = Language::_(
                    'AdminClients.view.tooltip_last_activity_minutes',
                    true,
                    ceil($last_activity)
                );
            }

            $system_settings = $this->SettingsCollection->fetchSystemSettings();
            if ($system_settings['geoip_enabled'] == 'true') {
                // Load GeoIP database
                $this->components(['Net']);
                if (!isset($this->NetGeoIp)) {
                    $this->NetGeoIp = $this->Net->create('NetGeoIp');
                }

                // Set GeoIp data
                $user_log->geo_ip = ['location' => $this->NetGeoIp->getLocation($user_log->ip_address)];
            }
        }

        // Set all contact types besides 'primary' and 'other'
        $contact_types = $this->Contacts->getContactTypes();
        $contact_type_ids = $this->Form->collapseObjectArray(
            $this->Contacts->getTypes($this->company_id),
            'real_name',
            'id'
        );
        unset($contact_types['primary'], $contact_types['other']);

        $this->set('contact_types', $contact_types + $contact_type_ids);
        $this->set('user_log', $user_log);
        $this->set('client', $client);
        $this->set('content', $content);
        $this->set('number_types', $this->Contacts->getNumberTypes());
        $this->set('number_locations', $this->Contacts->getNumberLocations());
        $this->set('status', $this->Clients->getStatusTypes());
        $this->set('default_currency', $client->settings['default_currency']);
        $this->set('multiple_groups', $this->ClientGroups->getListCount($this->company_id) > 0);
        $this->set('delivery_methods', $this->Invoices->getDeliveryMethods($client->id));
        $this->set(
            'plugin_actions',
            $this->Actions->getAll(
                ['company_id' => $this->company_id, 'location' => 'action_staff_client', 'enabled' => 1],
                true
            )
        );
        $this->set('client_account', $this->Clients->getDebitAccount($client->id));
        $this->render('admin_clients_view');
    }

    /**
     * List services
     */
    public function services()
    {
        $this->uses(['Packages', 'Services', 'PluginManager']);

        if (!isset($this->get[0]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            $this->redirect($this->base_uri . 'clients/');
        }

        // Set filters from post input
        $post_filters = [];
        if (isset($this->post['filters'])) {
            $post_filters = $this->post['filters'];
            unset($this->post['filters']);

            foreach($post_filters as $filter => $value) {
                if (empty($value)) {
                    unset($post_filters[$filter]);
                }
            }
        }
        $filters = $post_filters;

        // Exclude domains, if the domain manager plugin is installed
        if ($this->PluginManager->isInstalled('domains', Configure::get('Blesta.company_id'))) {
            $filters['type'] = 'services';
        }

        // Process service actions
        if (!empty($this->post)) {
            if (($errors = $this->updateServices($client, $this->post))) {
                $this->set('vars', (object)$this->post);
                $this->setMessage('error', $errors);
            } else {
                switch ($this->post['action']) {
                    case 'schedule_cancellation':
                        $term = 'AdminClients.!success.services_scheduled_';
                        $term .= isset($this->post['action_type'])
                            && $this->post['action_type'] == 'none' ? 'uncancel' : 'cancel';
                        break;
                    case 'invoice_renewal':
                        $term = 'AdminClients.!success.services_renewed';
                        break;
                    case 'push_to_client':
                        $term = 'AdminClients.!success.services_pushed';
                        break;
                }

                $this->flashMessage('message', Language::_($term, true));
                $this->redirect($this->base_uri . 'clients/view/' . $client->id . '/');
            }
        }

        $status = (isset($this->get[1]) ? $this->get[1] : 'active');
        $page = (isset($this->get[2]) ? (int)$this->get[2] : 1);
        $sort = (isset($this->get['sort']) ? $this->get['sort'] : 'date_added');
        $order = (isset($this->get['order']) ? $this->get['order'] : 'desc');

        // Get only parent services
        $services = $this->Services->getList($client->id, $status, $page, [$sort => $order], false, $filters);
        $total_results = $this->Services->getListCount($client->id, $status, false, null, $filters);

        // Set the number of services of each type, not including children
        $status_count = [
            'active' => $this->Services->getStatusCount($client->id, 'active', false, $filters),
            'canceled' => $this->Services->getStatusCount($client->id, 'canceled', false, $filters),
            'pending' => $this->Services->getStatusCount($client->id, 'pending', false, $filters),
            'suspended' => $this->Services->getStatusCount($client->id, 'suspended', false, $filters),
        ];

        // Set the expected service renewal price
        foreach ($services as $service) {
            $service->renewal_price = $this->Services->getRenewalPrice($service->id);
        }

        // Build service actions
        $actions = $this->getServiceActions();

        // Set the input field filters for the widget
        $service_filters = new ServiceFilters();
        $this->set(
            'filters',
            $service_filters->getFilters(
                [
                    'language' => Configure::get('Blesta.language'),
                    'company_id' => Configure::get('Blesta.company_id'),
                    'client_id' => $client->id
                ],
                $post_filters
            )
        );

        $this->set('filter_vars', $post_filters);
        $this->set('client', $client);
        $this->set('status', $status);
        $this->set('services', $services);
        $this->set('status_count', $status_count);
        $this->set('widget_state', isset($this->widgets_state['services']) ? $this->widgets_state['services'] : null);
        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));
        $this->set('actions', $actions);

        // Set language for periods
        $periods = $this->Packages->getPricingPeriods();
        foreach ($this->Packages->getPricingPeriods(true) as $period => $lang) {
            $periods[$period . '_plural'] = $lang;
        }
        $this->set('periods', $periods);

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination'),
            [
                'total_results' => $total_results,
                'uri' => $this->base_uri . 'clients/services/' . $client->id . '/' . $status . '/[p]/',
                'params' => ['sort' => $sort, 'order' => $order]
            ]
        );
        $this->setPagination($this->get, $settings);

        if ($this->isAjax()) {
            return $this->renderAjaxWidgetIfAsync(
                isset($this->get['whole_widget'])
                ? null
                : (isset($this->get[2]) || isset($this->get['sort']))
            );
        }
        return $this->renderClientView($this->controller . '_' . $this->action);
    }

    /**
     * Gets the available actions for services
     *
     * @return array An array containing the available actions for services
     */
    private function getServiceActions()
    {
        return [
            'schedule_cancellation' => Language::_('AdminClients.services.action.schedule_cancellation', true),
            'invoice_renewal' => Language::_('AdminClients.services.action.invoice_renewal', true),
            'push_to_client' => Language::_('AdminClients.services.action.push_to_client', true)
        ];
    }

    /**
     * Updates the given services to change their scheduled cancellation date
     *
     * @param stdClass $client The client whose services are being updated
     * @param array $data An array of POST data including:
     *
     *  - service_ids An array of each service ID
     *  - action The action to perform, e.g. "schedule_cancellation", "invoice_renewal" or "push_to_client"
     *  - action_type The type of action to perform, e.g. "term", "date"
     *  - date The cancel date if the action type is "date"
     * @return mixed An array of errors, or false otherwise
     */
    private function updateServices($client, array $data)
    {
        // Require authorization to update a client's service
        if (!$this->authorized('admin_clients', 'editservice')) {
            $this->flashMessage('error', Language::_('AppController.!error.unauthorized_access', true));
            $this->redirect($this->base_uri . 'clients/view/' . $client->id . '/');
        }

        // Only include service IDs in the list
        $service_ids = [];
        if (isset($data['service_ids'])) {
            foreach ((array)$data['service_ids'] as $service_id) {
                if (is_numeric($service_id)) {
                    $service_ids[] = $service_id;
                }
            }
        }

        $data['service_ids'] = $service_ids;
        $data['cycles'] = (isset($data['cycles']) ? $data['cycles'] : null);
        $data['date'] = (isset($data['date']) ? $data['date'] : null);
        $data['action_type'] = (isset($data['action_type']) ? $data['action_type'] : null);
        $data['action'] = (isset($data['action']) ? $data['action'] : null);
        $errors = false;

        switch ($data['action']) {
            case 'schedule_cancellation':
                // Ensure the scheduled date is in the future
                if ($data['action_type'] == 'date' &&
                    (!$data['date'] || $this->Date->cast($data['date'], 'Ymd') < $this->Date->cast(date('c'), 'Ymd'))
                ) {
                    $errors = ['error' => ['date' => Language::_('AdminClients.!error.future_cancel_date', true)]];
                } else {
                    // Update the services
                    $vars = [
                        'date_canceled' => ($data['action_type'] == 'term' ? 'end_of_term' : $data['date'])
                    ];

                    // Cancel or uncancel each service
                    foreach ($data['service_ids'] as $service_id) {
                        if ($data['action_type'] == 'none') {
                            $this->Services->unCancel($service_id);
                        } else {
                            $this->Services->cancel($service_id, $vars);
                        }

                        if (($errors = $this->Services->errors())) {
                            break;
                        }
                    }
                }
                break;
            case 'invoice_renewal':
                foreach ($data['service_ids'] as $service_id) {
                    Loader::loadModels($this, ['Services', 'Invoices']);

                    // Get service
                    $service = $this->Services->get($service_id);
                    if (!$service) {
                        break;
                    }

                    // Determine whether invoices for this service remain unpaid
                    $unpaid_invoices = $this->Invoices->getAllWithService($service->id, $service->client_id, 'open');

                    // Disallow renew if the current service has not been paid
                    if (!empty($unpaid_invoices)) {
                        $errors = ['error' => ['cycles' => Language::_('AdminClients.!error.invoices_renew_service', true)]];
                        break;
                    }

                    // Create the invoice for these renewing services
                    $this->Invoices->createRenewalFromService($service_id, $data['cycles']);

                    if (($errors = $this->Invoices->errors())) {
                        return $errors;
                    }
                }
                break;
            case 'push_to_client':
                foreach ($data['service_ids'] as $service_id) {
                    Loader::loadModels($this, ['Services', 'Invoices']);

                    // Get service
                    $service = $this->Services->get($service_id);
                    if (!$service) {
                        break;
                    }

                    // Move service
                    $this->Services->move($service->id, $this->post['client_id']);

                    if (($errors = $this->Services->errors())) {
                        return $errors;
                    }
                }
                break;
        }

        return $errors;
    }

    /**
     * AJAX Fetch clients when searching
     * @see AdminTickets::add()
     */
    public function getClients()
    {
        if (!isset($this->Form)) {
            $this->helpers(['Form']);
        }

        if (!isset($this->Clients)) {
            $this->uses(['Clients']);
        }

        // Ensure there is post data
        if (!$this->isAjax() || empty($this->post['search'])) {
            header($this->server_protocol . ' 401 Unauthorized');
            exit();
        }

        $search = $this->post['search'];
        $clients = $this->Form->collapseObjectArray(
            $this->Clients->search($search),
            ['id_code', 'first_name', 'last_name'],
            'id',
            ' '
        );

        echo json_encode(['clients' => $clients]);

        return false;
    }

    /**
     * Service count
     */
    public function serviceCount()
    {
        $this->uses(['Services', 'PluginManager']);

        $client_id = isset($this->get[0]) ? $this->get[0] : null;
        $status = isset($this->get[1]) ? $this->get[1] : 'active';

        $filters = [];
        // Exclude domains, if the domain manager plugin is installed
        if ($this->PluginManager->isInstalled('domains', Configure::get('Blesta.company_id'))) {
            $filters['type'] = 'services';
        }

        echo $this->Services->getStatusCount($client_id, $status, false, $filters);
        return false;
    }

    /**
     * Delivers selected invoices
     *
     * @param array $invoice_ids An array of invoice IDs to be delivered
     * @param string $status The current status of these invoices
     * @param array $vars An array of additional input, i.e., the delivery method
     * @param stdClass $client The client to whom the invoices belongs
     */
    private function deliverInvoices(array $invoice_ids, $status, array $vars, stdClass $client)
    {
        $this->components(['InvoiceDelivery']);

        // Set the companies hostname
        $hostname = isset(Configure::get('Blesta.company')->hostname)
            ? Configure::get('Blesta.company')->hostname
            : '';

        // Set the email template to use
        $email_template = 'invoice_delivery_unpaid';
        if ($status == 'closed') {
            $email_template = 'invoice_delivery_paid';
        }

        // Set the options for these invoices, but use the tags set in InvoiceDelivery::deliverInvoices()
        $options = [
            'email_template' => $email_template,
            'base_client_url' => $this->Html->safe($hostname . $this->client_uri)
        ];
        $this->InvoiceDelivery->deliverInvoices(
            $invoice_ids,
            $vars['action'],
            $vars[$vars['action']] ?? null,
            $this->Session->read('blesta_staff_id'),
            $options
        );

        if (($errors = $this->InvoiceDelivery->errors())) {
            // Error
            $this->flashMessage('error', $errors);
        } else {
            // Success
            $this->flashMessage('message', Language::_('AdminClients.!success.invoices_delivered', true));

            // Add a new delivery method record for each invoice and mark them sent
            foreach ($invoice_ids as $invoice_id) {
                $delivery_id = $this->Invoices->addDelivery(
                    $invoice_id,
                    ['method' => $vars['action']],
                    $client->id
                );

                // Mark the invoice delivered by the given action method
                // unless it is paper--it needs to be manually printed first
                if ($delivery_id && $vars['action'] != 'paper') {
                    $this->Invoices->delivered($delivery_id);
                }
            }
        }
    }

    /**
     * Voids selected invoices
     *
     * @param array $invoice_ids A list of ids for the invoices that are being voided
     * @param stdClass $client An stdClass object representing the client
     */
    private function voidInvoices(array $invoice_ids, stdClass $client)
    {
        $unvoided_invoices = [];
        $voided_invoices = [];

        $vars = ['status' => 'void'];
        // Void all selected invoices
        foreach ($invoice_ids as $invoice_id) {
            // Skip invalid invoices
            if (empty($invoice_id) || !($invoice = $this->Invoices->get($invoice_id))
                || $invoice->client_id != $client->id
            ) {
                continue;
            }

            // Void the invoice
            $this->Invoices->edit($invoice_id, $vars);

            // Keep track of which invoices have been voided and which have not
            if (($errors = $this->Invoices->errors())) {
                $unvoided_invoices[] = $invoice->id_code;
            } else {
                $voided_invoices[] = $invoice->id_code;
            }
        }

        // Construct a message for all unvoided invoices
        $message = ['unvoided' => [], 'voided' => []];
        $message_type = 'message';
        if (!empty($unvoided_invoices)) {
            $message_type = 'error';

            $message['unvoided'][] = Language::_(
                'AdminClients.!error.invoices_not_voided',
                true,
                implode(', ', $unvoided_invoices)
            );
        }

        // Add all voided invoices to the message
        if (!empty($voided_invoices)) {
            if ($message_type == 'error') {
                $message_type = 'notice';
            }

            $message['voided'][] = Language::_(
                'AdminClients.!success.invoices_voided',
                true,
                implode(', ', $voided_invoices)
            );
        }

        $this->flashMessage($message_type, $message);
    }

    /**
     * List invoices or perform action (deliver or void) on selected invoices
     */
    public function invoices()
    {
        $this->uses(['Invoices', 'Contacts', 'Messages']);

        // Ensure valid client id
        if (!isset($this->get[0]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            $this->redirect($this->base_uri . 'clients/');
        }

        // Set filters from post input
        $post_filters = [];
        if (isset($this->post['filters'])) {
            $post_filters = $this->post['filters'];
            unset($this->post['filters']);

            foreach($post_filters as $filter => $value) {
                if (empty($value)) {
                    unset($post_filters[$filter]);
                }
            }
        }

        // Get current page of results
        $status = (isset($this->get[1]) ? $this->get[1] : 'open');
        $page = (isset($this->get[2]) ? (int)$this->get[2] : 1);

        // Perform an action on the selected invoices
        if (!empty($this->post)
            && !empty($this->post['invoice_id'])
            && is_array($this->post['invoice_id'])
        ) {
            if (isset($this->post['action'])) {
                if ($this->post['action'] != 'void') {
                    // Send invoices through email, 'postalmethods', or 'interfax' to users associated
                    // with them (admins and customer)
                    $this->deliverInvoices($this->post['invoice_id'], $status, $this->post, $client);
                } else {
                    // Void selected invoices
                    $this->voidInvoices($this->post['invoice_id'], $client);
                }
            }
            // Redirect to the main client view instead of the invoice page
            $this->redirect($this->base_uri . 'clients/view/' . $client->id . '/');
        }

        // Get invoices for this client
        if ($status == 'recurring') {
            $sort = (isset($this->get['sort']) ? $this->get['sort'] : 'id');
            $order = (isset($this->get['order']) ? $this->get['order'] : 'desc');

            $invoices = $this->Invoices->getRecurringList($client->id, $page, [$sort => $order], $post_filters);
            $total_results = $this->Invoices->getRecurringListCount($client->id, $post_filters);
        } else {
            $sort = (isset($this->get['sort']) ? $this->get['sort'] : 'date_due');
            $order = (isset($this->get['order']) ? $this->get['order'] : 'desc');

            $invoices = $this->Invoices->getList($client->id, $status, $page, [$sort => $order], $post_filters);
            $total_results = $this->Invoices->getListCount($client->id, $status, $post_filters);
        }

        // Set the number of invoices of each type
        $status_count = [
            'open' => $this->Invoices->getStatusCount($client->id, 'open', $post_filters),
            'closed' => $this->Invoices->getStatusCount($client->id, 'closed', $post_filters),
            'draft' => $this->Invoices->getStatusCount($client->id, 'draft', $post_filters),
            'void' => $this->Invoices->getStatusCount($client->id, 'void', $post_filters),
            'recurring' => $this->Invoices->getRecurringCount($client->id, $post_filters),
            'pending' => $this->Invoices->getStatusCount($client->id, 'pending', $post_filters)
        ];

        // Set the delivery methods
        $delivery_methods = $this->Invoices->getDeliveryMethods($client->id);
        foreach ($delivery_methods as &$method) {
            $method = Language::_('AdminClients.invoices.action_deliver', true, $method);
        }

        // Set messengers as delivery methods
        $message_types = $this->Messages->getTypes();
        foreach ($message_types as &$messenger) {
            $messenger = Language::_('AdminClients.invoices.action_deliver', true, $messenger);
        }

        // Set the available invoice actions
        $delivery_methods = array_merge($delivery_methods, $message_types);
        $invoice_actions = $delivery_methods;

        if ($status == 'open') {
            $invoice_actions['void'] = Language::_('AdminClients.invoices.action_void', true);
        }

        // Set the input field filters for the widget
        $invoice_filters = new InvoiceFilters();
        $this->set(
            'filters',
            $invoice_filters->getFilters(
                ['language' => Configure::get('Blesta.language'), 'company_id' => Configure::get('Blesta.company_id')],
                $post_filters
            )
        );

        $this->set('filter_vars', $post_filters);
        $this->set('deliverable_invoice_statuses', ['open', 'closed', 'void']);
        $this->set('status', $status);
        $this->set('invoice_actions', $invoice_actions);
        $this->set('client', $client);
        $this->set('contact_fax', $this->Contacts->getNumbers($client->contact_id, 'fax'));
        $this->set('contact_mobile', $this->Contacts->getNumbers($client->contact_id, 'mobile'));
        $this->set('invoices', $invoices);
        $this->set('status_count', $status_count);
        $this->set('widget_state', isset($this->widgets_state['invoices']) ? $this->widgets_state['invoices'] : null);
        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination'),
            [
                'total_results' => $total_results,
                'uri' => $this->base_uri . 'clients/invoices/' . $client->id . '/' . $status . '/[p]/',
                'params' => ['sort' => $sort, 'order' => $order]
            ]
        );
        $this->setPagination($this->get, $settings);

        if ($this->isAjax()) {
            return $this->renderAjaxWidgetIfAsync(
                isset($this->get['whole_widget']) ? null : (isset($this->get[2]) || isset($this->get['sort']))
            );
        }
        return $this->renderClientView($this->controller . '_' . $this->action);
    }

    /**
     * Invoice count
     */
    public function invoiceCount()
    {
        $this->uses(['Invoices']);

        $client_id = isset($this->get[0]) ? $this->get[0] : null;
        $status = isset($this->get[1]) ? $this->get[1] : 'active';

        echo $this->Invoices->getStatusCount($client_id, $status);
        return false;
    }

    /**
     * Validates if an invoice has pending services before voiding them
     */
    public function validateInvoices()
    {
        $this->uses(['Invoices', 'Services']);

        // Ensure there is post data
        if (!$this->isAjax() || empty($this->post['invoice_id'])) {
            header($this->server_protocol . ' 401 Unauthorized');
            exit();
        }

        $continue = true;

        // Set array of invoices
        $invoice_ids = [];
        if (!empty($this->post['invoice_id']) && is_array($this->post['invoice_id'])) {
            $invoice_ids = (array) $this->post['invoice_id'];
        }
        if (!empty($this->post['invoice_id']) && is_numeric($this->post['invoice_id'])) {
            $invoice_ids = [$this->post['invoice_id']];
        }

        // Check for invoices associated to pending services
        $invoices = [];
        foreach ($invoice_ids as $invoice_id) {
            $lines = $this->Invoices->getLineItems($invoice_id);
            foreach ($lines as $line) {
                if (!empty($line->service_id)) {
                    $service = $this->Services->get($line->service_id);

                    if ($service->status == 'pending') {
                        $continue = false;
                        break 2;
                    }
                }
            }
        }

        echo json_encode(['continue' => $continue]);

        return false;
    }

    /**
     * AJAX request for all transactions an invoice has applied
     */
    public function invoiceApplied()
    {
        $this->uses(['Invoices', 'Transactions']);

        if (!isset($this->get[0]) || !isset($this->get[1]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            header($this->server_protocol . ' 401 Unauthorized');
            exit();
        }

        $invoice = $this->Invoices->get($this->get[1]);

        // Ensure the invoice belongs to the client and this is an ajax request
        if (!$this->isAjax() || !$invoice || $invoice->client_id != $this->get[0]) {
            header($this->server_protocol . ' 401 Unauthorized');
            exit();
        }


        $vars = [
            'client' => $client,
            'applied' => $this->Transactions->getApplied(null, $this->get[1]),
            // Holds the name of all of the transaction types
            'transaction_types' => $this->Transactions->transactionTypeNames()
        ];

        // Send the template
        echo $this->partial('admin_clients_invoiceapplied', $vars);

        // Render without layout
        return false;
    }

    /**
     * Delivers selected quotations
     *
     * @param array $quotation_ids An array of quotation IDs to deliver
     * @param string $status The current status of these quotations
     * @param array $vars An array of additional input, i.e., the delivery method
     * @param stdClass $client The client to whom the quotations belongs
     */
    private function deliverQuotations(array $quotation_ids, $status, array $vars, stdClass $client)
    {
        $this->components(['QuotationDelivery']);

        // Set the companies hostname
        $hostname = isset(Configure::get('Blesta.company')->hostname)
            ? Configure::get('Blesta.company')->hostname
            : '';

        // Set the email template to use
        $email_template = 'quotation_delivery';
        if ($status == 'approved') {
            $email_template = 'quotation_approved';
        }

        // Set the options for these quotations, but use the tags set in QuotationDelivery::deliverQuotations()
        $options = [
            'email_template' => $email_template,
            'base_client_url' => $this->Html->safe($hostname . $this->client_uri)
        ];
        $this->QuotationDelivery->deliverQuotations(
            $quotation_ids,
            $vars['action'],
            $vars[$vars['action']] ?? null,
            $this->Session->read('blesta_staff_id'),
            $options
        );

        if (($errors = $this->QuotationDelivery->errors())) {
            // Error
            $this->flashMessage('error', $errors);
        } else {
            // Success
            $this->flashMessage('message', Language::_('AdminClients.!success.quotations_delivered', true));
        }
    }

    /**
     * Voids selected invoices
     *
     * @param array $invoice_ids A list of ids for the invoices that are being voided
     * @param stdClass $client An stdClass object representing the client
     */
    private function updateQuotationsStatus(array $quotation_ids, $status, stdClass $client)
    {
        $this->uses(['Quotations']);

        if (array_key_exists($status, $this->Quotations->getStatuses())) {
            $quotation_ids = array_diff($quotation_ids, ['all']);
            foreach ($quotation_ids as $quotation_id) {
                // Skip invalid quotations
                if (empty($quotation_id) || !($quotation = $this->Quotations->get($quotation_id))
                    || $quotation->client_id != $client->id
                ) {
                    continue;
                }

                // Update the quotation
                $this->Quotations->updateStatus($quotation_id, $status);
            }

            // Add all updated quotations to the message
            $message_type = 'message';
            $message['status'][] = Language::_(
                'AdminClients.!success.quotations_status_updated',
                true,
                implode(', ', $quotation_ids)
            );
        } else {
            $message_type = 'error';
            $message['status'][] = Language::_(
                'AdminClients.!error.quotation_invalid_status',
                true
            );
        }

        $this->flashMessage($message_type, $message);
    }

    /**
     * List quotations or perform action (deliver or void) on selected quotations
     */
    public function quotations()
    {
        $this->uses(['Quotations', 'Invoices', 'Contacts', 'Messages']);

        // Ensure valid client id
        if (!isset($this->get[0]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            $this->redirect($this->base_uri . 'clients/');
        }

        // Set filters from post input
        $post_filters = [];
        if (isset($this->post['filters'])) {
            $post_filters = $this->post['filters'];
            unset($this->post['filters']);

            foreach($post_filters as $filter => $value) {
                if (empty($value)) {
                    unset($post_filters[$filter]);
                }
            }
        }

        // Get current page of results
        $status = (isset($this->get[1]) ? $this->get[1] : 'approved');
        $page = (isset($this->get[2]) ? (int)$this->get[2] : 1);
        $sort = (isset($this->get['sort']) ? $this->get['sort'] : 'date_created');
        $order = (isset($this->get['order']) ? $this->get['order'] : 'desc');

        // Perform an action on the selected quotations
        if (!empty($this->post)
            && !empty($this->post['quotation_id'])
            && is_array($this->post['quotation_id'])
        ) {
            if (isset($this->post['action'])) {
                if ($this->post['action'] != 'status') {
                    $this->deliverQuotations($this->post['quotation_id'], $status, $this->post, $client);
                } else {
                    $this->updateQuotationsStatus($this->post['quotation_id'], ($this->post['status'] ?? null), $client);
                }
            }

            // Redirect to the main client view instead of the quotation page
            $this->redirect($this->base_uri . 'clients/view/' . $client->id . '/');
        }

        // Get quotations for the company
        $quotations = $this->Quotations->getList($client->id, $status, $page, [$sort => $order], $post_filters);
        $total_results = $this->Quotations->getListCount($client->id, $status, $post_filters);

        // Set the number of quotations of each type
        $status_count = [
            'approved' => $this->Quotations->getStatusCount($client->id, 'approved', $post_filters),
            'pending' => $this->Quotations->getStatusCount($client->id, 'pending', $post_filters),
            'draft' => $this->Quotations->getStatusCount($client->id, 'draft', $post_filters),
            'invoiced' => $this->Quotations->getStatusCount($client->id, 'invoiced', $post_filters),
            'expired' => $this->Quotations->getStatusCount($client->id, 'expired', $post_filters),
            'dead' => $this->Quotations->getStatusCount($client->id, 'dead', $post_filters),
            'lost' => $this->Quotations->getStatusCount($client->id, 'lost', $post_filters)
        ];

        // Set the delivery methods
        $delivery_methods = $this->Invoices->getDeliveryMethods($client->id);
        foreach ($delivery_methods as &$method) {
            $method = Language::_('AdminClients.invoices.action_deliver', true, $method);
        }

        // Set messengers as delivery methods
        $message_types = $this->Messages->getTypes();
        foreach ($message_types as &$messenger) {
            $messenger = Language::_('AdminClients.invoices.action_deliver', true, $messenger);
        }

        // Quotations does not support paper delivery
        unset($delivery_methods['paper']);

        // Set the available quotation actions
        $delivery_methods = array_merge($delivery_methods, $message_types);
        $quotation_actions = $delivery_methods;

        // Add update status action
        $quotation_actions['status'] = Language::_('AdminClients.quotations.action.status', true);

        // Set the input field filters for the widget
        $quotations_filters = new QuotationFilters();
        $this->set(
            'filters',
            $quotations_filters->getFilters(
                ['language' => Configure::get('Blesta.language'), 'company_id' => Configure::get('Blesta.company_id')],
                $post_filters
            )
        );

        $this->set('filter_vars', $post_filters);
        $this->set('deliverable_quotation_statuses', ['open', 'closed', 'void']);
        $this->set('status', $status);
        $this->set('quotation_actions', $quotation_actions);
        $this->set('quotation_statuses', $this->Quotations->getStatuses());
        $this->set('client', $client);
        $this->set('contact_fax', $this->Contacts->getNumbers($client->contact_id, 'fax'));
        $this->set('contact_mobile', $this->Contacts->getNumbers($client->contact_id, 'mobile'));
        $this->set('quotations', $quotations);
        $this->set('status_count', $status_count);
        $this->set('widget_state', $this->widgets_state['quotations'] ?? null);
        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination'),
            [
                'total_results' => $total_results,
                'uri' => $this->base_uri . 'clients/quotations/' . $client->id . '/' . $status . '/[p]/',
                'params' => ['sort' => $sort, 'order' => $order]
            ]
        );
        $this->setPagination($this->get, $settings);

        if ($this->isAjax()) {
            return $this->renderAjaxWidgetIfAsync(
                isset($this->get['whole_widget']) ? null : (isset($this->get[2]) || isset($this->get['sort']))
            );
        }
        return $this->renderClientView($this->controller . '_' . $this->action);
    }

    /**
     * Quotation count
     */
    public function quotationCount()
    {
        $this->uses(['Quotations']);

        $client_id = isset($this->get[0]) ? $this->get[0] : null;
        $status = isset($this->get[1]) ? $this->get[1] : 'draft';

        echo $this->Quotations->getStatusCount($client_id, $status);

        return false;
    }


    /**
     * AJAX request for all invoices associated to a quotation
     */
    public function quotationInvoices()
    {
        $this->uses(['Clients', 'Quotations']);

        if (!isset($this->get[0]) || !isset($this->get[1]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            header($this->server_protocol . ' 401 Unauthorized');
            exit();
        }

        $quotation = $this->Quotations->get($this->get[1]);

        // Ensure the quotation belongs to the client and this is an ajax request
        if (!$this->isAjax() || !$quotation || $quotation->client_id != $this->get[0]) {
            header($this->server_protocol . ' 401 Unauthorized');
            exit();
        }

        $vars = [
            'client' => $client,
            'invoices' => $this->Quotations->getInvoices($this->get[1])
        ];

        // Send the template
        echo $this->partial('admin_clients_quotationinvoices', $vars);

        // Render without layout
        return false;
    }

    /**
     * List transactions
     */
    public function transactions()
    {
        $this->uses(['Transactions']);

        if (!isset($this->get[0]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            $this->redirect($this->base_uri . 'clients');
        }

        // Set filters from post input
        $post_filters = [];
        if (isset($this->post['filters'])) {
            $post_filters = $this->post['filters'];
            unset($this->post['filters']);

            foreach($post_filters as $filter => $value) {
                if (empty($value)) {
                    unset($post_filters[$filter]);
                }
            }
        }

        // Set the number of transactions of each type
        $status_count = [
            'approved' => $this->Transactions->getStatusCount($client->id, 'approved', $post_filters),
            'declined' => $this->Transactions->getStatusCount($client->id, 'declined', $post_filters),
            'void' => $this->Transactions->getStatusCount($client->id, 'void', $post_filters),
            'error' => $this->Transactions->getStatusCount($client->id, 'error', $post_filters),
            'pending' => $this->Transactions->getStatusCount($client->id, 'pending', $post_filters),
            'refunded' => $this->Transactions->getStatusCount($client->id, 'refunded', $post_filters),
            'returned' => $this->Transactions->getStatusCount($client->id, 'returned', $post_filters)
        ];

        $status = (isset($this->get[1]) ? $this->get[1] : 'approved');
        $page = (isset($this->get[2]) ? (int)$this->get[2] : 1);
        $sort = (isset($this->get['sort']) ? $this->get['sort'] : 'date_added');
        $order = (isset($this->get['order']) ? $this->get['order'] : 'desc');

        // Get transactions for this client
        $transactions = $this->Transactions->getList($client->id, $status, $page, [$sort => $order], $post_filters);
        $total_results = $this->Transactions->getListCount($client->id, $status, $post_filters);

        // Set the input field filters for the widget
        $transaction_filters = new TransactionFilters();
        $this->set(
            'filters',
            $transaction_filters->getFilters(
                [
                    'language' => Configure::get('Blesta.language'),
                    'company_id' => Configure::get('Blesta.company_id'),
                    'client_id' => $client->id
                ],
                $post_filters
            )
        );

        $this->set('filter_vars', $post_filters);
        $this->set('transactions', $transactions);
        $this->set('client', $client);
        $this->set('status', $status);
        $this->set('status_count', $status_count);
        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));
        $this->set(
            'widget_state',
            isset($this->widgets_state['transactions'])
            ? $this->widgets_state['transactions']
            : null
        );
        // Holds the name of all of the transaction types
        $this->set('transaction_types', $this->Transactions->transactionTypeNames());
        // Holds the name of all of the transaction status values
        $this->set('transaction_status', $this->Transactions->transactionStatusNames());

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination'),
            [
                'total_results' => $total_results,
                'uri' => $this->base_uri . 'clients/transactions/' . $client->id . '/' . $status . '/[p]/',
                'params' => ['sort' => $sort, 'order' => $order],
            ]
        );
        $this->setPagination($this->get, $settings);

        if ($this->isAjax()) {
            return $this->renderAjaxWidgetIfAsync(
                isset($this->get['whole_widget']) ? null : (isset($this->get[2]) || isset($this->get['sort']))
            );
        }
        return $this->renderClientView($this->controller . '_' . $this->action);
    }

    /**
     * Transaction count
     */
    public function transactionCount()
    {
        $this->uses(['Transactions']);

        $client_id = isset($this->get[0]) ? $this->get[0] : null;
        $status = isset($this->get[1]) ? $this->get[1] : 'approved';

        echo $this->Transactions->getStatusCount($client_id, $status);
        return false;
    }

    /**
     * AJAX request for all invoices a transaction has been applied to
     */
    public function transactionApplied()
    {
        $this->uses(['Transactions']);

        if (!isset($this->get[0]) || !isset($this->get[1]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            header($this->server_protocol . ' 401 Unauthorized');
            exit();
        }

        $transaction = $this->Transactions->get($this->get[1]);

        // Ensure the transaction belongs to the client and this is an ajax request
        if (!$this->isAjax() || !$transaction || $transaction->client_id != $this->get[0]) {
            header($this->server_protocol . ' 401 Unauthorized');
            exit();
        }

        $vars = [
            'client' => $client,
            'applied' => $this->Transactions->getApplied($this->get[1]),
            'transaction' => $transaction
        ];

        // Send the template
        echo $this->partial('admin_clients_transactionapplied', $vars);

        // Render without layout
        return false;
    }

    /**
     * View mail log
     */
    public function emails()
    {
        $this->uses(['Emails', 'Staff']);

        // Get client
        if (!isset($this->get[0]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            $this->redirect($this->base_uri . 'clients/');
        }

        // Set current page of results
        $page = (isset($this->get[1]) ? (int)$this->get[1] : 1);
        $sort = (isset($this->get['sort']) ? $this->get['sort'] : 'date_sent');
        $order = (isset($this->get['order']) ? $this->get['order'] : 'desc');

        $logs = $this->Clients->getMailLogList($client->id, $page, [$sort => $order]);

        // Format CC addresses, if available
        if ($logs) {
            // Fetch email signatures
            $this->uses(['Emails']);
            $email_signatures = $this->Emails->getAllSignatures($this->company_id);
            $signatures = [];
            foreach ($email_signatures as $signature) {
                $signatures[] = $signature->text;
            }

            for ($i = 0, $num_logs = count($logs); $i < $num_logs; $i++) {
                // Convert email HTML to text if necessary
                $logs[$i]->body_text = $this->getTextFromHtml($logs[$i]->body_html, $logs[$i]->body_text, $signatures);

                // Format all CC addresses from CSV to array
                $cc_addresses = $logs[$i]->cc_address;
                $logs[$i]->cc_address = [];
                foreach (explode(',', $cc_addresses) as $address) {
                    if (!empty($address)) {
                        $logs[$i]->cc_address[] = $address;
                    }
                }
            }
        }

        $this->set('logs', $logs);
        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));
        $this->set('client', $client);

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination'),
            [
                'total_results' => $this->Clients->getMailLogListCount($client->id),
                'uri' => $this->base_uri . 'clients/emails/' . $client->id . '/[p]/',
                'params' => ['sort' => $sort, 'order' => $order],
            ]
        );
        $this->setPagination($this->get, $settings);

        if ($this->isAjax()) {
            return $this->renderAjaxWidgetIfAsync(isset($this->get[1]) || isset($this->get['sort']));
        }
        return $this->renderClientView($this->controller . '_' . $this->action);
    }

    /**
     * Converts the HTML given to text iff no text is currently set
     *
     * @param string $html The current HTML
     * @param string $text The current text (optional)
     * @param mixed $remove_text A string value, or numerically-indexed array of strings to remove from $text
     *  before attempting the conversion (optional)
     * @return string The updated text
     */
    private function getTextFromHtml($html, $text = '', $remove_text = '')
    {
        if (!isset($this->StringHelper)) {
            $this->StringHelper = $this->DataStructure->create('String');
        }

        $text  = $this->StringHelper->removeFromText($text, $remove_text);
        if (empty($text) && !empty($html)) {
            $text = $this->StringHelper->htmlToText($html);
        }

        return $text;
    }

    /**
     * List notes
     */
    public function notes()
    {
        // Redirect if invalid client ID
        if (!isset($this->get[0]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            $this->redirect($this->base_uri . 'clients');
        }

        // Get page and sort order
        $page = (isset($this->get[1]) ? (int)$this->get[1] : 1);
        $sort = (isset($this->get['sort']) ? $this->get['sort'] : 'date_added');
        $order = (isset($this->get['order']) ? $this->get['order'] : 'desc');

        $this->set('client', $client);
        $this->set('notes', $this->Clients->getNoteList($client->id, $page, [$sort => $order]));
        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination'),
            [
                'total_results' => $this->Clients->getNoteListCount($client->id),
                'uri' => $this->base_uri . 'clients/notes/' . $client->id . '/[p]/',
                'params' => ['sort' => $sort, 'order' => $order],
            ]
        );
        $this->setPagination($this->get, $settings);

        // Render the request if ajax
        if ($this->isAjax()) {
            return $this->renderAjaxWidgetIfAsync(
                isset($this->get['whole_widget']) ? null : (isset($this->get[1]) || isset($this->get['sort']))
            );
        }
        return $this->renderClientView($this->controller . '_' . $this->action);
    }

    /**
     * Add note
     */
    public function addNote()
    {
        if (!isset($this->get[0]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            $this->redirect($this->base_uri . 'clients/');
        }

        $vars = new stdClass();

        if (!empty($this->post)) {
            // Set unset checkboxes
            if (!isset($this->post['stickied'])) {
                $this->post['stickied'] = 0;
            }

            $this->Clients->addNote($client->id, $this->Session->read('blesta_staff_id'), $this->post);

            if (($errors = $this->Clients->errors())) {
                // Error
                $this->setMessage('error', $errors);
                $vars = (object)$this->post;
            } else {
                // Success
                $this->flashMessage('message', Language::_('AdminClients.!success.note_added', true));
                $this->redirect($this->base_uri . 'clients/view/' . $client->id . '/');
            }
        }

        $this->set('vars', $vars);
        $this->view($this->view->fetch('admin_clients_addnote'));
    }

    /**
     * Edit note
     */
    public function editNote()
    {
        // Ensure the note given belongs to this client
        if (!isset($this->get[0])
            || !($client = $this->Clients->get((int)$this->get[0])) || !isset($this->get[1])
            || !($note = $this->Clients->getNote((int)$this->get[1]))
            || ($client->id != $note->client_id)
        ) {
            $this->redirect($this->base_uri . 'clients/view/');
        }

        $vars = $note;

        if (!empty($this->post)) {
            // Set unset checkboxes
            if (!isset($this->post['stickied'])) {
                $this->post['stickied'] = 0;
            }

            $this->Clients->editNote($note->id, $this->post);

            if (($errors = $this->Clients->errors())) {
                // Error
                $this->setMessage('error', $errors);
                $vars = (object)$this->post;
            } else {
                // Success
                $this->flashMessage('message', Language::_('AdminClients.!success.note_updated', true));
                $this->redirect($this->base_uri . 'clients/view/' . $client->id . '/');
            }
        }

        $this->set('vars', $vars);
        $this->view($this->view->fetch('admin_clients_editnote'));
    }

    /**
     * Delete Note
     */
    public function deleteNote()
    {
        // Get client and note, ensuring they exist
        if (!isset($this->get[0])
            || !isset($this->get[1])
            || !($client = $this->Clients->get((int)$this->get[0]))
            || !($note = $this->Clients->getNote((int)$this->get[1]))
            || ($client->id != $note->client_id)
        ) {
            $this->redirect($this->base_uri . 'clients/');
        }

        // Delete the note
        $this->Clients->deleteNote($note->id);
        $this->flashMessage('message', Language::_('AdminClients.!success.note_deleted', true));

        $this->redirect($this->base_uri . 'clients/view/' . $client->id . '/');
    }

    /**
     * Displays/removes sticky notes
     */
    public function stickyNotes()
    {
        // Ensure a valid client has been given
        if (!isset($this->get[0]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            exit;
        }

        // Remove a note from being stickied
        if (isset($this->get[1])) {
            $note = $this->Clients->getNote((int)$this->get[1]);

            // Ensure the note belongs to this client
            if (empty($note) || ($note->client_id != $client->id)) {
                exit;
            }

            $this->Clients->unstickyNote($note->id);

            $sticky_notes = $this->Clients->getAllStickyNotes($client->id, Configure::get('Blesta.sticky_notes_max'));
            $sticky_note_vars = [
                'notes' => $sticky_notes,
                'number_notes_to_show' => Configure::get('Blesta.sticky_notes_to_show'),
                'show_more' => (!empty($this->post['show_more']) ? $this->post['show_more'] : 'false')
            ];
            $response = new stdClass();

            // Set a view for sticky notes
            if (!empty($sticky_notes)) {
                $response->view = $this->partial('admin_clients_stickynote_list', $sticky_note_vars);
            }

            // JSON encode the AJAX response
            $this->outputAsJson($response);
            return false;
        }

        $this->set('notes', $this->Clients->getAllStickyNotes($client->id, Configure::get('Blesta.sticky_notes_max')));
        $this->view($this->view->fetch('admin_clients_stickynotes'));
    }

    /**
     * Prompts to download a vCard of the client's address information
     */
    public function vCard()
    {
        // Ensure a client ID is given
        if (!isset($this->get[0]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            $this->redirect($this->base_uri . 'clients/');
        }

        $this->components(['Vcard']);
        $this->uses(['Contacts']);

        $data = [
            'first_name' => $client->first_name,
            'last_name' => $client->last_name,
            'company' => $client->company,
            'title' => $client->title,
            'email' => $client->email
        ];

        if ($client->company != '') {
            $data['work_address'] = $client->address1 . ' ' . $client->address2;
            $data['work_city'] = $client->city;
            $data['work_state'] = $client->state;
            $data['work_postal_code'] = $client->zip;
            $data['work_country'] = $client->country;
        } else {
            $data['home_address'] = $client->address1 . ' ' . $client->address2;
            $data['home_city'] = $client->city;
            $data['home_state'] = $client->state;
            $data['home_postal_code'] = $client->zip;
            $data['home_country'] = $client->country;
        }

        // Get any phone/fax numbers
        $contact_numbers = $this->Contacts->getNumbers($client->contact_id);

        // Set any contact numbers (only the first of a specific type found)
        foreach ($contact_numbers as $contact_number) {
            switch ($contact_number->location) {
                case 'home':
                    // Set home phone number
                    if (!isset($data['home_tel']) && $contact_number->type == 'phone') {
                        $data['home_tel'] = $contact_number->number;
                    }
                    break;
                case 'work':
                    // Set work phone/fax number
                    if (!isset($data['work_tel']) && $contact_number->type == 'phone') {
                        $data['work_tel'] = $contact_number->number;
                    } elseif (!isset($data['fax_tel']) && $contact_number->type == 'fax') {
                        $data['fax_tel'] = $contact_number->number;
                    }
                    break;
                case 'mobile':
                    // Set mobile phone number
                    if (!isset($data['cell_tel']) && $contact_number->type == 'phone') {
                        $data['cell_tel'] = $contact_number->number;
                    }
                    break;
            }
        }

        $file_name = str_replace(' ', '', $client->first_name . '-' . $client->last_name);

        // Create the vCard and stream it to the browser
        $this->Vcard->create($data, true, $file_name);
        return false;
    }

    /**
     * Add New Client
     */
    public function add()
    {
        $this->uses(
            [
                'ClientGroups',
                'Companies',
                'Contacts',
                'Countries',
                'Currencies',
                'Languages',
                'States',
                'Users',
                'Taxes',
                'TaxProviders'
            ]
        );
        $this->components(['SettingsCollection']);
        $this->ArrayHelper = $this->DataStructure->create('Array');

        // Get company settings
        $company_settings = $this->SettingsCollection->fetchSettings($this->Companies, $this->company_id);

        // Set default currency, country, and language settings from this company
        $vars = new stdClass();
        $vars->country = $company_settings['country'];
        $vars->default_currency = $company_settings['default_currency'];
        $vars->language = $company_settings['language'];

        // Fetch client group force email setting
        $force_email_usernames = $this->SettingsCollection->fetchClientGroupSetting(
            isset($this->post['client_group_id']) ? $this->post['client_group_id'] : null,
            null,
            'force_email_usernames'
        );
        $force_email_usernames = (isset($force_email_usernames['value']) ? $force_email_usernames['value'] :
            (isset($company_settings['force_email_usernames']) ? $company_settings['force_email_usernames'] : 'false')
        );

        // Create a new client
        if (!empty($this->post)) {
            $vars = $this->post;
            $vars['confirm_password'] = $vars['new_password'];
            $vars['settings'] = [
                'username_type' => $vars['username_type'],
                'tax_exempt' => !empty($vars['tax_exempt']) ? $vars['tax_exempt'] : 'false',
                'tax_id' => $vars['tax_id'],
                'default_currency' => $vars['default_currency'],
                'language' => $vars['language'],
                'receive_email_marketing' => empty($vars['receive_email_marketing']) ? 'false' : 'true'
            ];
            $vars['numbers'] = $this->ArrayHelper->keyToNumeric($vars['numbers']);
            foreach ($vars as $key => $value) {
                if (substr($key, 0, 12) == 'custom_field') {
                    $vars['custom'][substr($key, 12)] = $value;
                }
            }

            // Set values for unchecked boxes
            $vars['send_registration_email'] = (empty($vars['send_registration_email']) ? 'false' : 'true');
            $vars['send_registration_message'] = (empty($vars['send_registration_message']) ? 'false' : 'true');

            // Force email usernames
            if ($force_email_usernames == 'true') {
                $vars['settings']['username_type'] = 'email';
                $vars['settings']['username'] = '';
            }

            // Attempt to validate info before the actual creation attempt
            $this->Clients->validateCreation($vars);
            if (($errors = $this->Clients->errors())) {
                $this->setMessage('error', $errors);
                $vars = (object)$this->post;
            } else {
                // Create the client
                $this->Clients->create($vars);

                if (($errors = $this->Clients->errors())) {
                    $this->setMessage('error', $errors);
                    $vars = (object)$this->post;
                } else {
                    $this->flashMessage('message', Language::_('AdminClients.!success.client_added', true));
                    $this->redirect($this->base_uri . 'clients/');
                }
            }
        }

        // Get all client groups
        $client_groups = $this->Form->collapseObjectArray(
            $this->ClientGroups->getAll($this->company_id),
            'name',
            'id'
        );

        // Set the current client group ID selected for displaying custom fields
        $client_group_id = (isset($vars->client_group_id) ? $vars->client_group_id : null);

        // Set partial for custom fields only if there are some to display
        if ($client_group_id != null) {
            $custom_fields = $this->Clients->getCustomFields($this->company_id, $client_group_id);
            // Swap key/value pairs for "Select" option custom fields (to display)
            foreach ($custom_fields as &$field) {
                if ($field->type == 'select' && is_array($field->values)) {
                    $field->values = array_flip($field->values);
                }
            }

            $partial_custom_fields = [
                'vars' => $vars,
                'custom_fields' => $custom_fields,
                'custom_field_prefix' => $this->custom_field_prefix
            ];
            $this->set('custom_fields', $this->partial('admin_clients_custom_fields', $partial_custom_fields));
        }

        // Set partial for phone numbers
        $partial_vars = [
            'numbers' => (isset($vars->numbers) ? $vars->numbers : []),
            'number_types' => $this->Contacts->getNumberTypes(),
            'number_locations' => $this->Contacts->getNumberLocations()
        ];
        $this->set('partial_phones', $this->partial('admin_clients_phones', $partial_vars));

        // Set form fields
        $this->set(
            'countries',
            $this->Form->collapseObjectArray($this->Countries->getList(), ['name', 'alt_name'], 'alpha2', ' - ')
        );
        $this->set('states', $this->Form->collapseObjectArray($this->States->getList($vars->country), 'name', 'code'));
        $this->set('currencies', $this->Currencies->getAll($this->company_id));
        $this->set(
            'languages',
            $this->Form->collapseObjectArray($this->Languages->getAll($this->company_id), 'name', 'code')
        );
        $this->set('status', $this->Clients->getStatusTypes());
        $this->set('company_settings', $company_settings);
        $this->set('client_groups', $client_groups);
        $this->set('vars', $vars);

        // Set tax specific variables
        $this->set('tax_countries', $this->TaxProviders->getAllCountries());
        $this->set('tax_exemption_enabled', $this->TaxProviders->isExemptionHandlerEnabled());

        // Set JSON-encoded password generator character options
        $this->setPasswordOptions();
    }

    /**
     * Edit Client
     */
    public function edit()
    {
        $this->uses(
            [
                'ClientGroups',
                'Contacts',
                'Countries',
                'Companies',
                'Currencies',
                'Languages',
                'States',
                'Users',
                'EmailVerifications',
                'Taxes',
                'TaxProviders'
            ]
        );
        $this->components(['SettingsCollection']);
        $this->ArrayHelper = $this->DataStructure->create('Array');

        // Get client or redirect if not given
        if (!isset($this->get[0]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            $this->redirect($this->base_uri . 'clients/');
        }

        // Get company settings
        $company_settings = $this->SettingsCollection->fetchSettings($this->Companies, $this->company_id);

        // Fetch this user
        $user = $this->Users->get($client->user_id);

        $vars = [];

        // Fetch client group force email setting
        $force_email_usernames = $this->SettingsCollection->fetchClientGroupSetting(
            $client->client_group_id,
            null,
            'force_email_usernames'
        );
        $force_email_usernames = (isset($force_email_usernames['value']) ? $force_email_usernames['value'] :
            (isset($company_settings['force_email_usernames']) ? $company_settings['force_email_usernames'] : 'false')
        );

        // Update client
        if (!empty($this->post)) {
            // Begin a new transaction
            $this->Clients->begin();

            // Update the user authentication
            $user_vars = [
                'username' => (isset($this->post['settings']['username_type'])
                    && $this->post['settings']['username_type'] == 'email')
                        ? $this->post['email']
                        : (isset($this->post['username']) ? $this->post['username'] : ''),
                'new_password' => $this->post['new_password'],
                'confirm_password' => $this->post['new_password']
            ];
            if (isset($this->post['two_factor_mode'])) {
                $user_vars['two_factor_mode'] = 'none';
            }

            // Remove the new password if not given
            if (empty($this->post['new_password'])) {
                unset($user_vars['new_password'], $user_vars['confirm_password']);
            }

            // Force email usernames
            if ($force_email_usernames == 'true') {
                $user_vars['username_type'] = 'email';
                $user_vars['username'] = $this->post['email'];
            }

            // Prevent email verification on user edit
            if ($this->post['email'] !== $client->email) {
                $user_vars['verify'] = false;
            }

            $this->Users->edit($user->id, $user_vars);
            $user_errors = $this->Users->errors();

            // Update the client
            $this->post['id_code'] = $client->id_code;
            $this->post['user_id'] = $client->user_id;
            $this->Clients->edit($client->id, $this->post);
            $client_errors = $this->Clients->errors();

            // Update the client custom fields
            $custom_field_errors = $this->addCustomFields($client->id, $this->post);

            // Update client settings
            $settings = [
                'tax_exempt' => !empty($this->post['settings']['tax_exempt'])
                    ? $this->post['settings']['tax_exempt']
                    : 'false',
                'receive_email_marketing' => !empty($this->post['receive_email_marketing'])
                    ? 'true'
                    : 'false'
            ];
            $settings_fields = [
                'username_type',
                'tax_id',
                'inv_address_to',
                'default_currency',
                'language'
            ];

            foreach ($settings_fields as $settings_field) {
                if (isset($this->post['settings'][$settings_field])) {
                    $settings[$settings_field] = $this->post['settings'][$settings_field];
                }
            }

            $this->Clients->setSettings($client->id, $settings, array_keys($settings));

            $vars = $this->post;

            // Format the phone numbers
            $vars['numbers'] = $this->ArrayHelper->keyToNumeric($this->post['numbers']);

            // Prevent email verification on contact edit
            $vars['verify'] = false;
            if ($this->post['email'] !== $client->email) {
                $this->EmailVerifications->deleteAll($client->contact_id);
            }

            // Set the ID of the staff member executing the action
            $vars['staff_id'] = $this->Session->read('blesta_staff_id');

            // Update the contact
            unset($vars['user_id']);
            $this->Contacts->edit($client->contact_id, $vars);
            $contact_errors = $this->Contacts->errors();

            $errors = array_merge(
                ($client_errors ? $client_errors : []),
                ($contact_errors ? $contact_errors : []),
                ($user_errors ? $user_errors : []),
                ($custom_field_errors ? $custom_field_errors : [])
            );

            if (!empty($errors)) {
                // Error, rollback
                $this->Clients->rollBack();

                $this->setMessage('error', $errors);
                $vars = (object)$this->post;
            } else {
                // Success, commit
                $this->Clients->commit();
                if (isset($settings['tax_id'])) {
                    $this->Clients->setSettings(
                        $client->id,
                        ['tax_id' => $settings['tax_id']],
                        ['tax_id', 'tax_exempt']
                    );
                }

                $this->flashMessage('message', Language::_('AdminClients.!success.client_updated', true));
                $this->redirect($this->base_uri . 'clients/view/' . $client->id . '/');
            }
        }

        // Set this client
        if (empty($vars)) {
            $vars = $client;

            // Set username
            $vars->username = $user->username;

            // Set client phone numbers formatted for HTML
            $vars->numbers = $this->ArrayHelper->numericToKey($this->Contacts->getNumbers($client->contact_id));

            // Set client custom field values
            $field_values = $this->Clients->getCustomFieldValues($client->id);
            foreach ($field_values as $field) {
                $vars->{$this->custom_field_prefix . $field->id} = $field->value;
            }
        }

        // Get all client contacts for which to make invoices addressable to (primary and billing contacts)
        $contacts = array_merge(
            $this->Contacts->getAll($client->id, 'primary'),
            $this->Contacts->getAll($client->id, 'billing')
        );

        // Set states and countries for drop downs
        $this->set(
            'countries',
            $this->Form->collapseObjectArray($this->Countries->getList(), ['name', 'alt_name'], 'alpha2', ' - ')
        );
        $this->set(
            'states',
            $this->Form->collapseObjectArray($this->States->getList($vars->country), 'name', 'code')
        );
        $this->set(
            'currencies',
            $this->Form->collapseObjectArray($this->Currencies->getAll($this->company_id), 'code', 'code')
        );
        $this->set(
            'languages',
            $this->Form->collapseObjectArray($this->Languages->getAll($this->company_id), 'name', 'code')
        );
        $this->set(
            'contacts',
            $this->Form->collapseObjectArray($contacts, ['first_name', 'last_name'], 'id', ' ')
        );
        $this->set(
            'client_groups',
            $this->Form->collapseObjectArray($this->ClientGroups->getAll($this->company_id), 'name', 'id')
        );
        $this->set('client', $client);
        $this->set('company_settings', $company_settings);
        $this->set('vars', $vars);

        // Set partial for custom fields
        $custom_fields = $this->Clients->getCustomFields($this->company_id, $client->client_group_id);
        // Swap key/value pairs for "Select" option custom fields (to display)
        foreach ($custom_fields as &$field) {
            if ($field->type == 'select' && is_array($field->values)) {
                $field->values = array_flip($field->values);
            }
        }

        $partial_custom_fields = [
            'vars' => $vars,
            'custom_fields' => $custom_fields,
            'custom_field_prefix' => $this->custom_field_prefix
        ];
        $this->set('custom_fields', $this->partial('admin_clients_custom_fields', $partial_custom_fields));

        // Set partial for phone numbers
        $partial_phone = [
            'numbers' => $vars->numbers,
            'number_types' => $this->Contacts->getNumberTypes(),
            'number_locations' => $this->Contacts->getNumberLocations()
        ];
        $this->set('partial_phones', $this->partial('admin_clients_phones', $partial_phone));

        // Set JSON-encoded password generator character options
        $this->setPasswordOptions();

        if ($this->isAjax()) {
            return false;
        }

        // Set tax specific variables
        $this->set('tax_countries', $this->TaxProviders->getAllCountries());
        $this->set('tax_exemption_enabled', $this->TaxProviders->isExemptionHandlerEnabled());

        $this->view($this->view->fetch('admin_clients_edit'));
    }

    /**
     * Delete Client
     */
    public function delete()
    {
        $this->uses(['Users']);

        // Only delete via ajax
        if (!$this->isAjax()) {
            $this->redirect($this->base_uri . 'clients/');
        }

        // Ensure a valid client was given
        if (!isset($this->get[0])
            || !($client = $this->Clients->get($this->get[0]))
            || ($client->company_id != $this->company_id)
        ) {
            exit;
        }

        // Attempt to delete a client
        if (!empty($this->post['client_id'])) {
            $response = (object)[];

            // Make sure that the password given matches the current admin
            if (isset($this->post['password'])
                && ($admin = $this->Staff->get($this->Session->read('blesta_staff_id')))
                && $this->Users->validatePasswordEquals($this->post['password'], $admin->user_id)
            ) {
                // Delete client
                $this->Clients->delete($this->post['client_id']);

                if (($errors = $this->Clients->errors())) {
                    // Error
                    $response->error = $this->setMessage('error', $errors, true);
                } else {
                    // Success
                    $this->flashMessage('message', Language::_('AdminClients.!success.client_deleted', true));
                    $response->redirect = $this->base_uri . 'clients/';
                }
            } else {
                // Invalid password
                $response->error = $this->setMessage('error', Language::_('AdminClients.!error.password', true), true);
            }

            // JSON encode the AJAX response
            $this->outputAsJson($response);
            return false;
        }

        // Output the client deletion confirmation modal
        $this->set('client_id', $client->id);
        $this->setMessage(
            'notice',
            Language::_('AdminClients.!notice.delete_client', true),
            false,
            ['show_close' => false]
        );

        echo $this->view->fetch('admin_clients_delete');
        return false;
    }

    /**
     * AJAX request for retrieving all custom fields for a client group
     */
    public function getCustomFields()
    {
        $this->uses(['Companies']);
        $this->components(['SettingsCollection']);

        if (!isset($this->get['group_id'])
            || ($custom_fields = $this->Clients->getCustomFields($this->company_id, (int)$this->get['group_id']))
                === false) {
            header($this->server_protocol . ' 401 Unauthorized');
            exit();
        }

        $custom_field_vars = new stdClass();

        // Get client-specific custom field values
        if (isset($this->get['client_id']) && ($client = $this->Clients->get((int)$this->get['client_id']))) {
            // Fetch the client custom field values already set for this client
            $field_values = $this->Clients->getCustomFieldValues($client->id);
            foreach ($field_values as $field) {
                $custom_field_vars->{$this->custom_field_prefix . $field->id} = $field->value;
            }
        }

        // Swap key/value pairs for "Select" option custom fields (to display)
        foreach ($custom_fields as &$field) {
            if ($field->type == 'select' && is_array($field->values)) {
                $field->values = array_flip($field->values);
            }
        }

        $vars = [
            'vars' => $custom_field_vars,
            'custom_fields' => $custom_fields,
            'custom_field_prefix' => $this->custom_field_prefix
        ];

        // Get company settings
        $company_settings = $this->SettingsCollection->fetchSettings($this->Companies, $this->company_id);

        // Fetch client group force email setting
        $force_email_usernames = $this->SettingsCollection->fetchClientGroupSetting(
            isset($this->get['group_id']) ? $this->get['group_id'] : null,
            null,
            'force_email_usernames'
        );
        $force_email_usernames = (isset($force_email_usernames['value']) ? $force_email_usernames['value'] :
            (isset($company_settings['force_email_usernames']) ? $company_settings['force_email_usernames'] : 'false')
        );

        // Send the template
        $response = new stdClass();
        $response->custom_fields = $this->partial('admin_clients_custom_fields', $vars);
        $response->force_email_usernames = $force_email_usernames;

        // JSON encode the AJAX response
        $this->outputAsJson($response);
        return false;
    }

    /**
     * Attempts to add custom fields to a client
     *
     * @param int $client_id The client ID to add custom fields for
     * @param array $vars The post data, containing custom fields
     * @return mixed An array of errors, or false if none exist
     * @see Clients::add(), Clients::edit()
     */
    private function addCustomFields($client_id, $vars = [])
    {
        // Get the client's current custom fields
        $client_custom_fields = $this->Clients->getCustomFieldValues($client_id);

        // Create a list of custom field IDs to update
        $custom_fields = $this->Clients->getCustomFields(
            $this->company_id,
            (isset($vars['client_group_id']) ? $vars['client_group_id'] : null)
        );
        $custom_field_ids = [];
        foreach ($custom_fields as $field) {
            $custom_field_ids[] = $field->id;
        }
        unset($field);

        // Build a list of given custom fields to update
        $custom_fields_set = [];
        foreach ($vars as $field => $value) {
            // Get the custom field ID from the name
            $field_id = preg_replace('/' . $this->custom_field_prefix . '/', '', $field, 1);

            // Set the custom field
            if ($field_id != $field) {
                $custom_fields_set[$field_id] = $value;
            }
        }
        unset($field, $value);

        // Set every custom field available, even if it's not given, for validation
        $deletable_fields = [];
        foreach ($custom_field_ids as $field_id) {
            // Set a temp value for validation purposes and mark it to be deleted
            if (!isset($custom_fields_set[$field_id])) {
                $custom_fields_set[$field_id] = '';
                // Set this field to be deleted
                $deletable_fields[] = $field_id;
            }
        }
        unset($field_id);

        // Attempt to add/update each custom field
        $temp_field_errors = [];
        foreach ($custom_fields_set as $field_id => $value) {
            $this->Clients->setCustomField($field_id, $client_id, $value);
            $temp_field_errors[] = $this->Clients->errors();
        }
        unset($field_id, $value);

        // Delete the fields that were not given
        foreach ($deletable_fields as $field_id) {
            $this->Clients->deleteCustomFieldValue($field_id, $client_id);
        }

        // Combine multiple custom field errors together
        $custom_field_errors = [];
        for ($i = 0, $num_errors = count($temp_field_errors); $i < $num_errors; $i++) {
            // Skip any "error" that is not an array already
            if (!is_array($temp_field_errors[$i])) {
                continue;
            }

            // Change the keys of each custom field error so we can display all of them at once
            $error_keys = array_keys($temp_field_errors[$i]);
            $temp_error = [];

            foreach ($error_keys as $key) {
                $temp_error[$key . $i] = $temp_field_errors[$i][$key];
            }

            $custom_field_errors = array_merge($custom_field_errors, $temp_error);
        }

        return (empty($custom_field_errors) ? false : $custom_field_errors);
    }

    /**
     * AJAX quick update client to set status, invoice method, auto debit status, or auto suspension status
     */
    public function quickUpdate()
    {
        // Ensure a client is given
        if (!isset($this->get[0]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            $this->redirect($this->base_uri . 'clients/');
        }

        $response = [];

        switch ($this->get[1]) {
            // Set the client's status (active/inactive, etc.)
            case 'status':
                $status_types = $this->Clients->getStatusTypes();
                $keys = array_keys($status_types);
                $num_keys = count($keys);
                for ($i = 0; $i < $num_keys; $i++) {
                    if ($keys[$i] == $client->status) {
                        $index = $keys[($i + 1) % $num_keys];
                        break;
                    }
                }
                // Update the status
                $this->Clients->edit($client->id, ['status' => $index]);

                $response = ['class_name' => 'status_box ' . $index, 'text' => $status_types[$index]];
                break;
            // Set the client's invoice delivery method
            case 'inv_method':
                $this->uses(['Invoices']);

                $delivery_methods = $this->Invoices->getDeliveryMethods($client->id);

                $keys = array_keys($delivery_methods);

                $i = 0;
                $num_methods = count($keys);
                for (; $i < $num_methods; $i++) {
                    if ($keys[$i] == $client->settings['inv_method']) {
                        break;
                    }
                }
                $index = $keys[($i + 1) % $num_methods];

                $this->Clients->setSetting($client->id, 'inv_method', $index);

                $response = ['class_name' => $index, 'text' => $delivery_methods[$index]];
                break;
            // Set whether the client should be automatically suspended for non-payment
            case 'autosuspend':
            // Set whether the client should be automatically debited when payment is due
            case 'autodebit':
                $options = ['true' => 'enable', 'false' => 'disable'];
                $keys = array_keys($options);
                $num_keys = count($keys);
                for ($i = 0; $i < $num_keys; $i++) {
                    if ($keys[$i] == $client->settings[$this->get[1]]) {
                        $index = $keys[($i + 1) % $num_keys];
                        break;
                    }
                }
                // Update the setting
                $this->Clients->setSetting($client->id, $this->get[1], $index);

                $response = [
                    'class_name' => $options[$index],
                    'text' => (
                        $index == 'true'
                            ? Language::_('AdminClients.view.setting_enabled', true)
                            : Language::_('AdminClients.view.setting_disabled', true)
                    )
                ];

                if ($this->get[1] == 'autodebit') {
                    // Get the current account set for autodebiting
                    $client_account = $this->Clients->getDebitAccount($client->id);

                    if ((!$client_account && $options[$index] == 'enable')) {
                        $response['tooltip'] = true;
                    }
                }
                if ($this->get[1] == 'autosuspend') {
                    $response['autosuspend_date'] = null;
                    if (isset($client->settings['autosuspend_date']) && $options[$index] == 'enable') {
                        $response['autosuspend_date'] = true;
                    }
                }

                break;
            // Set whether the client can be sent payment due notices
            case 'send_payment_notices':
                $options = ['true' => 'enable', 'false' => 'disable'];
                $keys = array_keys($options);
                $num_keys = count($keys);
                for ($i = 0; $i < $num_keys; $i++) {
                    if ($keys[$i] == $client->settings['send_payment_notices']) {
                        break;
                    }
                }
                $value = $keys[($i + 1) % $num_keys];

                // Update the setting
                $this->Clients->setSetting($client->id, 'send_payment_notices', $value);

                $response = [
                    'class_name' => $options[$value],
                    'text' => (
                        $value == 'true'
                            ? Language::_('AdminClients.view.setting_enabled', true)
                            : Language::_('AdminClients.view.setting_disabled', true)
                    )
                ];
                break;
            // Set the client's email verification status
            case 'email_verification':
                $options = ['verified' => 'enable', 'unverified' => 'disable'];

                $this->uses(['EmailVerifications']);

                $email_verification = $this->EmailVerifications->getByContactId($client->contact_id);
                $vars = [
                    'contact_id' => $client->contact_id,
                    'email' => $client->email
                ];

                if (isset($email_verification->verified) && isset($email_verification->id)) {
                    $index = ($email_verification->verified == 1 ? 'unverified' : 'verified');

                    if ($index == 'unverified') {
                        $this->EmailVerifications->deleteAll($client->contact_id);
                        $this->EmailVerifications->add($vars, false);
                    } elseif ($index == 'verified') {
                        $this->EmailVerifications->verify($email_verification->id);
                    }
                } else {
                    $index = 'unverified';
                    $this->EmailVerifications->add($vars);
                }

                $response = [
                    'class_name' => $options[$index],
                    'text' => (
                        $index == 'verified'
                            ? Language::_('AdminClients.view.setting_verified', true)
                            : Language::_('AdminClients.view.setting_unverified', true)
                    )
                ];
                break;
        }

        // If not an AJAX request, reload the client profile page
        if (!$this->isAjax()) {
            $this->redirect($this->base_uri . 'clients/view/' . $client->id);
        }

        echo json_encode($response);
        return false;
    }

    /**
     * Modal for viewing/setting delay suspension date
     */
    public function delaySuspension()
    {
        // Ensure a client is given
        if (!isset($this->get[0]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            $this->redirect($this->base_uri . 'clients/');
        }

        $vars = new stdClass();
        $vars->autosuspend_date = isset($client->settings['autosuspend_date'])
            ? $this->Date->cast($client->settings['autosuspend_date'], 'Y-m-d')
            : null;
        if (!empty($this->post)) {
            if (trim($this->post['autosuspend_date']) == '') {
                $this->Clients->unsetSetting($client->id, 'autosuspend_date');
            } else {
                $this->Clients->setSetting(
                    $client->id,
                    'autosuspend_date',
                    $this->Clients->dateToUtc($this->post['autosuspend_date'], 'c')
                );
            }
            $this->flashMessage('message', Language::_('AdminClients.!success.suspend_date_updated', true));
            $this->redirect($this->base_uri . 'clients/view/' . $client->id);
        }

        echo $this->partial('admin_client_delaysuspension', compact('vars'));
        return false;
    }

    /**
     * Email Client
     */
    public function email()
    {
        $this->uses(['Contacts', 'Emails', 'Logs']);

        // Get client ID
        if (!isset($this->get[0]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            $this->redirect($this->base_uri . 'clients/');
        }

        // Get an email to pre-populate with (resend an email)
        if (isset($this->get[1])) {
            $email = $this->Logs->getEmail((int)$this->get[1]);

            if ($email && $email->to_client_id == $client->id) {
                // Set vars of email to resend
                $vars = (object)[
                    'email_field' => 'email_other',
                    'recipient_other' => $email->to_address,
                    'from_name' => $email->from_name,
                    'from' => $email->from_address,
                    'subject' => $email->subject,
                    'message' => $email->body_text,
                    'html' => $email->body_html
                ];
            }
        }

        // Send the email
        if (!empty($this->post)) {
            if (isset($this->post['email_field']) && $this->post['email_field'] == 'email_selected') {
                $this->post['to'] = (isset($this->post['recipient']) ? $this->post['recipient'] : null);
            } else {
                $this->post['to'] = (isset($this->post['recipient_other']) ? $this->post['recipient_other'] : null);
            }

            // Attempt to send the email
            $this->Emails->sendCustom(
                (isset($this->post['from']) ? $this->post['from'] : null),
                (isset($this->post['from_name']) ? $this->post['from_name'] : null),
                (isset($this->post['to']) ? $this->post['to'] : null),
                (isset($this->post['subject']) ? $this->post['subject'] : null),
                ['html' => (isset($this->post['html']) ? $this->post['html'] : null), 'text' => (isset($this->post['text']) ? $this->post['text'] : null)],
                null,
                null,
                null,
                null,
                ['to_client_id' => $client->id, 'from_staff_id' => $this->Session->read('blesta_staff_id')]
            );

            if (($errors = $this->Emails->errors())) {
                $this->setMessage('error', $errors);
            } else {
                $this->flashMessage('message', Language::_('AdminClients.!success.email_sent', true));
                $this->redirect($this->base_uri . 'clients/view/' . $client->id . '/');
            }
            $vars = (object)$this->post;
        }

        // Default to use staff email as from address
        if (!isset($vars)) {
            $vars = new stdClass();

            $this->uses(['Staff']);
            $staff = $this->Staff->get($this->Session->read('blesta_staff_id'));

            if ($staff) {
                $vars->from_name = $this->Html->concat(' ', $staff->first_name, $staff->last_name);
                $vars->from = $staff->email;
            }
        }

        $this->set(
            'contacts',
            $this->Form->collapseObjectArray(
                $this->Contacts->getList($client->id),
                ['first_name', 'last_name', 'email'],
                'email',
                ' '
            )
        );
        $this->set('vars', $vars);

        // Include WYSIWYG
        $this->Javascript->setFile('blesta/ckeditor/build/ckeditor.js', 'head', VENDORWEBDIR);

        $this->view($this->view->fetch('admin_clients_email'));
    }

    /**
     * Send the password reset email to the client
     */
    public function passwordReset()
    {
        $this->uses(['Contacts', 'Emails', 'Users']);

        // Get client ID
        if (!isset($this->get[0]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            $this->redirect($this->base_uri . 'clients/');
        }

        // A password reset email can only be sent to active clients
        $active = $client->status == 'active';
        if (!$active) {
            $this->setMessage('notice', Language::_('AdminClients.!notice.passwordreset.client_inactive', true));
        }

        $contact_ids = $this->getUserContacts($client);

        // Send the password reset email
        if ($active && !empty($this->post)) {
            $sent = false;

            // Send the password reset email
            if (!empty($this->post['contact_id'])
                && is_numeric($this->post['contact_id'])
                && array_key_exists($this->post['contact_id'], (array)$contact_ids)
                && ($contact = $this->Contacts->get($this->post['contact_id']))
                && ($user = $this->Users->get(
                        $contact->contact_type == 'primary' ? $client->user_id : $contact->user_id
                    )
                )
            ) {
                // Get the company hostname
                $hostname = isset(Configure::get('Blesta.company')->hostname)
                    ? Configure::get('Blesta.company')->hostname
                    : '';
                $requestor = $this->getFromContainer('requestor');

                // Set tags
                $time = time();
                $hash = $this->Clients->systemHash('u=' . $user->id . '|t=' . $time);
                $tags = [
                    'client' => $client,
                    'contact' => $contact,
                    'ip_address' => $requestor->ip_address,
                    'password_reset_url' => $this->Html->safe(
                        $hostname . $this->client_uri . 'login/confirmreset/?sid=' .
                        rawurlencode(
                            $this->Clients->systemEncrypt(
                                'u=' . $user->id . '|t=' . $time . '|h=' . substr($hash, -16)
                            )
                        )
                    )
                ];

                // Send the password reset email to the client contact
                $sent = $this->Emails->send(
                    'reset_password',
                    $this->company_id,
                    $client->settings['language'],
                    $contact->email,
                    $tags,
                    null,
                    null,
                    null,
                    ['to_client_id' => $client->id]
                );
            }

            if ($sent) {
                $this->setMessage('message', Language::_('AdminClients.!success.passwordreset.sent', true));
            } else {
                $this->setMessage('error', Language::_('AdminClients.!error.passwordreset.failed', true));
            }
        }

        $this->set('active', $active);
        $this->set('contacts', $contact_ids);

        $this->view($this->view->fetch('admin_clients_passwordreset'));
    }

    /**
     * Retrieves a list of contacts that can login to the system
     * @see AdminClients::passwordReset
     *
     * @param stdClass $client An stdClass object representing the client Id
     * @return array An array of key/value pairs where each key is the contact ID and the value is a language definition
     */
    private function getUserContacts(stdClass $client)
    {
        // Fetch all contact types
        $contact_types = $this->Contacts->getContactTypes();
        $contact_type_ids = $this->Form->collapseObjectArray(
            $this->Contacts->getTypes($client->company_id),
            'real_name',
            'id'
        );

        $contact_types = $contact_types + $contact_type_ids;

        // Fetch all of the client's contacts and pick out the ones that have user logins
        $contact_ids = [];
        $per_page = $this->Contacts->getPerPage();
        $total = $this->Contacts->getListCount($client->id);

        for ($page = 1; $page <= ceil($total / $per_page); $page++) {
            $contacts = $this->Contacts->getList($client->id, $page);

            // Pick out the contacts that can have their password reset
            foreach ($contacts as $contact) {
                if ($contact->user_id !== null || $contact->contact_type == 'primary') {
                    $type_key = ($contact->contact_type == 'other' && $contact->contact_type_id !== null
                        ? $contact->contact_type_id
                        : $contact->contact_type
                    );

                    $type = isset($contact_types[$type_key]) ? $contact_types[$type_key] : $contact->contact_type;

                    $contact_ids[$contact->id] = Language::_(
                        'AdminClients.passwordreset.contact_id_name',
                        true,
                        $contact->first_name,
                        $contact->last_name,
                        $type,
                        $contact->email
                    );
                }
            }
        }

        return $contact_ids;
    }

    /**
     * Login as the client
     */
    public function loginAsClient()
    {

        // Get client ID
        if (!isset($this->get[0]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            $this->redirect($this->base_uri . 'clients/');
        }

        $this->Session->write('blesta_client_id', $client->id);
        $this->Session->clear('blesta_contact_id');
        $this->redirect($this->client_uri);
    }

    /**
     * Logout as the client
     */
    public function logoutAsClient()
    {
        $client_id = $this->Session->read('blesta_client_id');
        $this->Session->clear('blesta_client_id');

        if (($contact_id = $this->Session->read('blesta_contact_id'))) {
            Loader::loadModels($this, ['Contacts']);

            $contact = $this->Contacts->get($contact_id);
            $client_id = $contact->client_id ?? $client_id;

            $this->Session->clear('blesta_contact_id');
        }

        if ($client_id) {
            $this->redirect($this->base_uri . 'clients/view/' . $client_id . '/');
        }

        $this->redirect($this->base_uri . 'clients/');
    }

    /**
     * Merge clients together
     */
    public function merge()
    {
        $this->uses(['Users']);

        // Get client ID
        if (!isset($this->get[0]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            $this->redirect($this->base_uri . 'clients/');
        }

        $vars = new stdClass();

        if (!empty($this->post)) {
            #
            # TODO: merge the clients
            #
            $vars = (object)$this->post;
        }

        $this->set('vars', $vars);

        $this->view($this->view->fetch('admin_clients_merge'));
    }

    /**
     * Add Contact
     */
    public function addContact()
    {
        $this->uses(['Contacts', 'Users', 'Countries', 'States']);
        $this->components(['SettingsCollection']);
        $this->ArrayHelper = $this->DataStructure->create('Array');

        // Get client
        if (!isset($this->get[0]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            $this->redirect($this->base_uri . 'clients/');
        }

        $vars = new stdClass();

        // Set client settings
        $vars->country = $client->settings['country'];
        $vars->currency = $client->settings['default_currency'];
        $vars->language = $client->settings['language'];

        // Add contact
        $user_errors = false;
        $contact_errors = false;
        if (!empty($this->post)) {
            $this->Contacts->begin();
            $this->post['client_id'] = $client->id;

            $vars = $this->post;
            unset($vars['user_id']);

            // Set contact type to 'other' if contact type id is given
            if (isset($this->post['contact_type']) && is_numeric($this->post['contact_type'])) {
                $vars['contact_type_id'] = $this->post['contact_type'];
                $vars['contact_type'] = 'other';
            } else {
                $vars['contact_type_id'] = null;
            }

            // Format any phone numbers
            $vars['numbers'] = $this->ArrayHelper->keyToNumeric($this->post['numbers']);
            $vars['permissions'] = isset($this->post['permissions'])
                ? $this->ArrayHelper->keyToNumeric($this->post['permissions'])
                : [];

            if (!empty($vars['enable_login'])) {
                $vars['confirm_password'] = $vars['new_password'];
                $vars['user_id'] = $this->Users->add($vars);
                $user_errors = $this->Users->errors();
            }

            // Prevent email verification
            $vars['verify'] = false;

            // Set the ID of the staff member executing the action
            $vars['staff_id'] = $this->Session->read('blesta_staff_id');

            // Create the contact
            $this->Contacts->add($vars);
            $contact_errors = $this->Contacts->errors();

            $errors = array_merge(($contact_errors ? $contact_errors : []), ($user_errors ? $user_errors : []));
            if (!empty($errors)) {
                $this->Contacts->rollback();
                // Error, reset vars
                $vars = (object)$this->post;
                $this->setMessage('error', $errors);
            } else {
                $this->Contacts->commit();
                // Success
                $this->flashMessage('message', Language::_('AdminClients.!success.contact_added', true));
                $this->redirect($this->base_uri . 'clients/view/' . (isset($client->id) ? $this->Html->safe($client->id) : null) . '/');
            }
        }

        // Set all contact types besides 'primary' and 'other'
        $contact_types = $this->Contacts->getContactTypes();
        $contact_type_ids = $this->Form->collapseObjectArray(
            $this->Contacts->getTypes($this->company_id),
            'real_name',
            'id'
        );
        unset($contact_types['primary'], $contact_types['other']);

        $contact_types = $contact_types + $contact_type_ids;

        // Set states and countries for drop downs
        $this->set(
            'countries',
            $this->Form->collapseObjectArray($this->Countries->getList(), ['name', 'alt_name'], 'alpha2', ' - ')
        );
        $this->set('states', $this->Form->collapseObjectArray($this->States->getList($vars->country), 'name', 'code'));
        $this->set('contact_types', $contact_types);
        $this->set('permissions', $this->Contacts->getPermissionOptions($this->company_id));
        $this->set('vars', $vars);

        // Set partial for phone numbers
        $partial_vars = [
            'numbers' => (isset($vars->numbers) ? $vars->numbers : []),
            'number_types' => $this->Contacts->getNumberTypes(),
            'number_locations' => $this->Contacts->getNumberLocations()
        ];
        $this->set('partial_phones', $this->partial('admin_clients_phones', $partial_vars));

        // Set JSON-encoded password generator character options
        $this->setPasswordOptions();

        $this->view($this->view->fetch('admin_clients_addcontact'));
        if ($this->isAjax()) {
            return false;
        }
    }

    /**
     * Edit Contact
     */
    public function editContact()
    {
        $this->uses(['Contacts', 'Users', 'Countries', 'States', 'EmailVerifications']);
        $this->components(['SettingsCollection']);
        $this->ArrayHelper = $this->DataStructure->create('Array');

        // Get client and contact, ensure they match
        if (!isset($this->get[0])
            || !isset($this->get[1])
            || !($client = $this->Clients->get((int)$this->get[0]))
            || !($contact = $this->Contacts->get((int)$this->get[1]))
            || ($client->id != $contact->client_id)
        ) {
            $this->redirect($this->base_uri . 'clients/');
        }

        $user = false;
        if ($contact->user_id) {
            $user = $this->Users->get($contact->user_id);
        }

        $vars = [];

        // Edit contact
        $contact_errors = false;
        $user_errors = false;
        if (!empty($this->post)) {
            $this->Contacts->begin();
            $vars = $this->post;
            unset($vars['user_id']);

            // Set contact type to 'other' if contact type id is given
            if (is_numeric($this->post['contact_type'])) {
                $vars['contact_type_id'] = $this->post['contact_type'];
                $vars['contact_type'] = 'other';
            } else {
                $vars['contact_type_id'] = null;
            }

            // Prevent email verification on contact edit
            $vars['verify'] = false;
            if ($this->post['email'] !== $contact->email) {
                $this->EmailVerifications->deleteAll($contact->id);
            }

            // Format the phone numbers
            $vars['numbers'] = $this->ArrayHelper->keyToNumeric($this->post['numbers']);
            $vars['permissions'] = isset($this->post['permissions'])
                ? $this->ArrayHelper->keyToNumeric($this->post['permissions'])
                : [];

            if (!empty($vars['enable_login'])) {
                $vars['confirm_password'] = $vars['new_password'];
                if ($contact->user_id) {
                    if (empty($vars['confirm_password'])) {
                        unset($vars['confirm_password']);
                    }

                    $this->Users->edit($contact->user_id, $vars);
                } else {
                    $vars['user_id'] = $this->Users->add($vars);
                }

                $user_errors = $this->Users->errors();
            } elseif ($contact->user_id) {
                $this->Users->delete($contact->user_id);
                $vars['user_id'] = null;
            }

            // Update the contact
            $this->Contacts->edit($contact->id, $vars);
            $contact_errors = $this->Contacts->errors();

            $errors = array_merge(($contact_errors ? $contact_errors : []), ($user_errors ? $user_errors : []));
            if (!empty($errors)) {
                $this->Contacts->rollback();
                // Error, reset vars
                $vars = (object)$this->post;
                $this->setMessage('error', $errors);
            } else {
                $this->Contacts->commit();
                // Success
                $this->flashMessage('message', Language::_('AdminClients.!success.contact_updated', true));
                $this->redirect($this->base_uri . 'clients/view/' . (isset($client->id) ? $this->Html->safe($client->id) : null) . '/');
            }
        }

        // Set current contact
        if (empty($vars)) {
            $vars = (object)array_merge((array)$user, (array)$contact);

            // Set contact type if it is not a default type
            if (is_numeric($vars->contact_type_id)) {
                $vars->contact_type = $vars->contact_type_id;
            }

            // Set contact phone numbers formatted for HTML
            $vars->numbers = $this->ArrayHelper->numericToKey($this->Contacts->getNumbers($contact->id));

            $vars->permissions = $this->ArrayHelper->numericToKey($this->Contacts->getPermissions($contact->id));
        }

        // Set all contact types besides 'primary' and 'other'
        $contact_types = $this->Contacts->getContactTypes();
        $contact_type_ids = $this->Form->collapseObjectArray(
            $this->Contacts->getTypes($this->company_id),
            'real_name',
            'id'
        );
        unset($contact_types['primary'], $contact_types['other']);

        $contact_types = $contact_types + $contact_type_ids;

        // Set states and countries for drop downs
        $this->set('contact_id', $contact->id);
        $this->set('client_id', $client->id);
        $this->set(
            'countries',
            $this->Form->collapseObjectArray($this->Countries->getList(), ['name', 'alt_name'], 'alpha2', ' - ')
        );
        $this->set('states', $this->Form->collapseObjectArray($this->States->getList($vars->country), 'name', 'code'));
        $this->set('contact_types', $contact_types);
        $this->set('permissions', $this->Contacts->getPermissionOptions($this->company_id));
        $this->set('vars', $vars);
        $this->set('user', $user);

        // Set partial for phone numbers
        $partial_vars = [
            'numbers' => $vars->numbers,
            'number_types' => $this->Contacts->getNumberTypes(),
            'number_locations' => $this->Contacts->getNumberLocations()
        ];
        $this->set('partial_phones', $this->partial('admin_clients_phones', $partial_vars));

        // Set JSON-encoded password generator character options
        $this->setPasswordOptions();

        $this->view($this->view->fetch('admin_clients_editcontact'));

        if ($this->isAjax()) {
            return false;
        }
    }

    /**
     * Delete Contact
     */
    public function deleteContact()
    {
        $this->uses(['Contacts']);

        // Get client and contact
        if (!isset($this->get[0])
            || !isset($this->get[1])
            || !($client = $this->Clients->get((int)$this->get[0]))
            || !($contact = $this->Contacts->get((int)$this->get[1]))
            || ($client->id != $contact->client_id)
        ) {
            $this->redirect($this->base_uri . 'clients/');
        }

        $this->Contacts->delete($contact->id);

        if (($errors = $this->Contacts->errors())) {
            $this->flashMessage('error', $errors);
        } else {
            $this->flashMessage(
                'message',
                Language::_('AdminClients.!success.contact_deleted', true, $contact->first_name, $contact->last_name)
            );
        }

        $this->redirect($this->base_uri . 'clients/view/' . $client->id . '/');
    }

    /**
     * Manage Payment Accounts
     */
    public function accounts()
    {
        // Ensure a valid client has been given
        if (!isset($this->get[0]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            $this->redirect($this->base_uri . 'clients/');
        }

        $this->uses(['Accounts', 'Contacts']);

        // Set the default account set for autodebiting to none
        $vars = (object)['account_id' => 'none'];

        // Set an account for autodebiting
        if (!empty($this->post)) {
            // Delete the debit account if set to none, or given invalid value
            if ($this->post['account_id'] == 'none' || !is_numeric($this->post['account_id'])) {
                // Delete account, send message on success, ignore otherwise (there was nothing to delete)
                if ($this->Clients->deleteDebitAccount($client->id)) {
                    $this->setMessage('message', Language::_('AdminClients.!success.accounts_deleted', true));
                }
            } else {
                // Add the debit account
                $this->Clients->addDebitAccount($client->id, $this->post);

                if (($errors = $this->Clients->errors())) {
                    // Error, reset vars
                    $vars = (object)$this->post;
                    $this->setMessage('error', $errors);
                } else {
                    // Success, debit account added/updated
                    $this->setMessage('message', Language::_('AdminClients.!success.accounts_updated', true));
                }
            }
        }

        // Get the current account set for autodebiting
        $client_account = $this->Clients->getDebitAccount($client->id);

        // Set the primary contact accounts
        $primary_contact = $this->Contacts->getAll($client->id, 'primary');
        $accounts = [];

        if (!empty($primary_contact[0])) {
            $cc_account = $this->Accounts->getAllCc($primary_contact[0]->id);
            $ach_account = $this->Accounts->getAllAch($primary_contact[0]->id, true);

            $accounts = array_merge($cc_account, $ach_account);
        }

        // Set billing contact accounts
        $billing_contacts = $this->Contacts->getAll($client->id, 'billing');
        for ($i = 0, $num_billing_contacts = count($billing_contacts); $i < $num_billing_contacts; $i++) {
            $cc_account = $this->Accounts->getAllCc($billing_contacts[$i]->id);
            $ach_account = $this->Accounts->getAllAch($billing_contacts[$i]->id);

            $accounts = array_merge($accounts, $cc_account, $ach_account);
        }

        // Determine which account is currently set for autodebiting
        if (!empty($accounts) && $client_account) {
            for ($i = 0, $num_accounts = count($accounts); $i < $num_accounts; $i++) {
                // Account ID and account type must be identical
                if (($accounts[$i]->id == $client_account->account_id) &&
                    ($accounts[$i]->account_type == $client_account->type)) {
                    // This account is set to be autodebited
                    $vars->account_id = $accounts[$i]->id;
                    $vars->type = $accounts[$i]->account_type;
                    break;
                }
            }
        }

        $data = [
            'account_types' => $this->Accounts->getTypes(),
            'ach_types' => $this->Accounts->getAchTypes(),
            'cc_types' => $this->Accounts->getCcTypes(),
            'accounts' => $accounts,
            'client' => $client,
            'vars' => $vars
        ];

        $this->set('client', $client);
        $this->set('content', $this->partial('admin_clients_account_list', $data));

        if ($this->isAjax()) {
            return $this->renderAjaxWidgetIfAsync(false);
        }

        $this->view($this->view->fetch('admin_clients_accounts'));
    }

    /**
     * Manages the payment account types enabled for this client
     */
    public function accountTypes()
    {
        // Ensure a valid client has been given
        if (!isset($this->get[0]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            $this->redirect($this->base_uri . 'clients/');
        }

        // Update accepted payment type settings
        if (!empty($this->post)) {
            // Set empty checkboxes
            if (empty($this->post['payments_allowed_cc'])) {
                $this->post['payments_allowed_cc'] = 'false';
            }
            if (empty($this->post['payments_allowed_ach'])) {
                $this->post['payments_allowed_ach'] = 'false';
            }

            // Update settings
            $fields = ['payments_allowed_cc', 'payments_allowed_ach'];
            $this->Clients->setSettings($client->id, $this->post, $fields);

            $this->flashMessage('message', Language::_('AdminClients.!success.accounttypes_updated', true));
            $this->redirect($this->base_uri . 'clients/accounts/' . $client->id);
        }

        $this->set('client', $client);
        Language::loadLang('admin_company_billing');
        $this->set(
            'partial_payment_types',
            $this->partial(
                'partial_payment_types',
                ['vars' => $this->Form->collapseObjectArray($this->Clients->getSettings($client->id), 'value', 'key')]
            )
        );


        if ($this->isAjax()) {
            return $this->renderAjaxWidgetIfAsync(false);
        }

        $this->view($this->view->fetch('admin_clients_accounttypes'));
    }

    /**
     * Add Credit Card account
     */
    public function addCcAccount()
    {
        // Ensure a valid client has been given
        if (!isset($this->get[0]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            $this->redirect($this->base_uri . 'clients/');
        }

        $this->uses(['Accounts', 'Contacts', 'Countries', 'States']);
        $this->components(['SettingsCollection']);

        // Set default country
        $vars = new stdClass();
        $vars->country = (!empty($client->settings['country']) ? $client->settings['country'] : '');

        // Set warning if the CC payment type setting is not enabled
        if ($client->settings['payments_allowed_cc'] == 'false') {
            Language::loadLang(['navigation']);
            $this->setMessage(
                'notice',
                Language::_(
                    'AdminClients.!notice.payment_type',
                    true,
                    Language::_('AdminClients.addCcAccount.text_cc', true),
                    $this->base_uri . 'clients/accounttypes/' . $client->id . '/',
                    Language::_('Navigation.getcompany.nav_billing_acceptedtypes', true)
                ),
                false,
                ['preserve_tags' => true]
            );
        }

        // Create a CC account
        if (!empty($this->post)) {
            // Fetch the contact we're about to set the payment account for
            $temp_contact_id = (isset($this->post['contact_id']) ? $this->post['contact_id'] : 0);
            $contact = $this->Contacts->get($temp_contact_id);

            // Set contact ID to create this account for (default to the client's contact ID)
            if (($temp_contact_id == 0) || !$contact || ($contact->client_id != $client->id)) {
                $this->post['contact_id'] = $client->contact_id;
            }

            if (isset($this->post['expiration_year']) || isset($this->post['expiration_month'])) {
                // Concatenate the expiration date to the form 'yyyymm'
                $this->post['expiration'] = (
                        isset($this->post['expiration_year']) ? $this->post['expiration_year'] : ''
                    ) . (isset($this->post['expiration_month']) ? $this->post['expiration_month'] : '');
            }

            // Create the account
            $this->Accounts->addCc($this->post);

            if (($errors = $this->Accounts->errors())) {
                // Error, reset vars
                $this->post['contact_id'] = ($temp_contact_id == 0 ? 'none' : $temp_contact_id);
                $vars = (object)$this->post;
                $this->setMessage('error', $errors);
            } else {
                // Success, account created
                $this->flashMessage('message', Language::_('AdminClients.!success.addccaccount_added', true));
                $this->redirect($this->base_uri . 'clients/accounts/' . $client->id . '/');
            }
        }

        // Set the contact info partial to the view
        $this->setContactView($vars, $client);
        // Set the CC info partial to the view
        $this->setCcView($vars, $client);

        $this->view($this->view->fetch('admin_clients_addccaccount'));

        if ($this->isAjax()) {
            return false;
        }
    }

    /**
     * Add ACH account
     */
    public function addAchAccount()
    {
        // Ensure a valid client has been given
        if (!isset($this->get[0]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            $this->redirect($this->base_uri . 'clients/');
        }

        $this->uses(['Accounts', 'Contacts', 'Countries', 'States', 'GatewayManager']);
        $this->components(['SettingsCollection', 'Gateways']);

        // Set default country
        $vars = new stdClass();
        $vars->country = (!empty($client->settings['country']) ? $client->settings['country'] : '');

        // Create a ACH account
        if (!empty($this->post)) {
            // Fetch the contact we're about to set the payment account for
            $contact = $this->Contacts->get((isset($this->post['contact_id']) ? $this->post['contact_id'] : 0));

            // Set contact ID to create this account for (default to the client's contact ID)
            if ($this->post['contact_id'] == 'none'
                || !is_numeric($this->post['contact_id'])
                || !$contact
                || ($contact->client_id != $client->id)
            ) {
                $this->post['contact_id'] = $client->contact_id;
            }

            // Create the account
            $account_id = $this->Accounts->addAch($this->post);

            if (($errors = $this->Accounts->errors())) {
                // Error, reset vars
                $vars = (object)$this->post;
                $this->setMessage('error', $errors);
            } else {
                // Success, account created
                $this->flashMessage('message', Language::_('AdminClients.!success.addachaccount_added', true));

                // Check if the account must be verified
                $gateway = $this->GatewayManager->getInstalledMerchant(
                    $this->company_id,
                    $client->settings['default_currency']
                );
                if ($gateway) {
                    $gateway_obj = $this->Gateways->create($gateway->class, $gateway->type);

                    if ($gateway_obj instanceof MerchantAchVerification) {
                        $this->redirect($this->base_uri . 'clients/verifyachaccount/' . $client->id . '/' . $account_id . '/');
                    } else {
                        $this->redirect($this->base_uri . 'clients/accounts/' . $client->id . '/');
                    }
                } else {
                    $this->redirect($this->base_uri . 'clients/accounts/' . $client->id . '/');
                }
            }
        }

        // Set warning if the CC payment type setting is not enabled
        if ($client->settings['payments_allowed_ach'] == 'false') {
            Language::loadLang(['navigation']);
            $this->setMessage(
                'notice',
                Language::_(
                    'AdminClients.!notice.payment_type',
                    true,
                    Language::_('AdminClients.addAchAccount.text_ach', true),
                    $this->base_uri . 'clients/accounttypes/' . $client->id . '/',
                    Language::_('Navigation.getcompany.nav_billing_acceptedtypes', true)
                ),
                false,
                ['preserve_tags' => true]
            );
        }

        // Set the contact info partial to the view
        $this->setContactView($vars, $client);
        // Set the ACH info partial to the view
        $this->setAchView($vars, $client);

        $this->view($this->view->fetch('admin_clients_addachaccount'));

        if ($this->isAjax()) {
            return false;
        }
    }

    /**
     * Edit a CC account
     */
    public function editCcAccount()
    {
        // Ensure a valid client has been given
        if (!isset($this->get[0]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            $this->redirect($this->base_uri . 'clients/');
        }

        $this->uses(['Accounts', 'Contacts', 'Countries', 'States']);
        $this->components(['SettingsCollection']);

        // Ensure a valid CC account ID has been given and belongs to this client
        if (!isset($this->get[1]) || !($account = $this->Accounts->getCc((int)$this->get[1]))
            || $account->client_id != $client->id || $account->status != 'active') {
            $this->redirect($this->base_uri . 'clients/accounts/' . $client->id . '/');
        }

        // Set warning if the CC payment type setting is not enabled
        if ($client->settings['payments_allowed_cc'] == 'false') {
            Language::loadLang(['navigation']);
            $this->setMessage(
                'notice',
                Language::_(
                    'AdminClients.!notice.payment_type',
                    true,
                    Language::_('AdminClients.addCcAccount.text_cc', true),
                    $this->base_uri . 'clients/accounttypes/' . $client->id . '/',
                    Language::_('Navigation.getcompany.nav_billing_acceptedtypes', true)
                ),
                false,
                ['preserve_tags' => true]
            );
        }

        $vars = [];

        // Edit the CC account
        if (!empty($this->post)) {
            if (isset($this->post['expiration_year']) || isset($this->post['expiration_month'])) {
                // Concatenate the expiration date to the form 'yyyymm'
                $this->post['expiration'] = (
                        isset($this->post['expiration_year']) ? $this->post['expiration_year'] : ''
                    ) . (isset($this->post['expiration_month']) ? $this->post['expiration_month'] : '');
            }

            // Update the account
            $this->Accounts->editCc($account->id, $this->post);

            if (($errors = $this->Accounts->errors())) {
                // Error, reset vars
                $vars = (object)$this->post;
                $vars->id = $account->id;
                $vars->gateway_id = $account->gateway_id;
                $this->setMessage('error', $errors);
            } else {
                // Success, account updated
                $this->flashMessage('message', Language::_('AdminClients.!success.editccaccount_updated', true));
                $this->redirect($this->base_uri . 'clients/accounts/' . $client->id . '/');
            }
        }

        // Set current account
        if (empty($vars)) {
            $vars = $account;

            // Parse out the expiration date for the CC# (yyyymm)
            $vars->expiration_month = substr($vars->expiration, -2);
            $vars->expiration_year = substr($vars->expiration, 0, 4);
        }

        // Set the contact info partial to the view
        $this->setContactView($vars, $client, true);
        // Set the CC info partial to the view
        $this->setCcView($vars, $client, true);

        $this->view($this->view->fetch('admin_clients_editccaccount'));

        if ($this->isAjax()) {
            return false;
        }
    }

    /**
     * Edit an ACH account
     */
    public function editAchAccount()
    {
        // Ensure a valid client has been given
        if (!isset($this->get[0]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            $this->redirect($this->base_uri . 'clients/');
        }

        $this->uses(['Accounts', 'Contacts', 'Countries', 'States', 'GatewayManager']);
        $this->components(['SettingsCollection', 'Gateways']);

        // Ensure a valid ACH account ID has been given and belongs to this client
        if (!isset($this->get[1])
            || !($account = $this->Accounts->getAch((int)$this->get[1]))
            || $account->client_id != $client->id
            || $account->status != 'active'
        ) {
            $this->redirect($this->base_uri . 'clients/accounts/' . $client->id . '/');
        }

        // Set warning if the CC payment type setting is not enabled
        if ($client->settings['payments_allowed_ach'] == 'false') {
            Language::loadLang(['navigation']);
            $this->setMessage(
                'notice',
                Language::_(
                    'AdminClients.!notice.payment_type',
                    true,
                    Language::_('AdminClients.addAchAccount.text_ach', true),
                    $this->base_uri . 'clients/accounttypes/' . $client->id . '/',
                    Language::_('Navigation.getcompany.nav_billing_acceptedtypes', true)
                ),
                false,
                ['preserve_tags' => true]
            );
        }

        $vars = [];

        // Edit the ACH account
        if (!empty($this->post)) {
            // Update the account
            $this->Accounts->editAch($account->id, $this->post);

            if (($errors = $this->Accounts->errors())) {
                // Error, reset vars
                $vars = (object)$this->post;
                $vars->gateway_id = $account->gateway_id;
                $this->setMessage('error', $errors);
            } else {
                // Success, account updated
                $this->flashMessage('message', Language::_('AdminClients.!success.editachaccount_updated', true));

                // Check if the account must be verified
                $gateway = $this->GatewayManager->getInstalledMerchant(
                    $this->company_id,
                    $client->settings['default_currency']
                );
                if ($gateway) {
                    $gateway_obj = $this->Gateways->create($gateway->class, $gateway->type);

                    if ($gateway_obj instanceof MerchantAchVerification) {
                        $this->redirect($this->base_uri . 'clients/verifyachaccount/' . $client->id . '/' . $account->id . '/');
                    } else {
                        $this->redirect($this->base_uri . 'clients/accounts/' . $client->id . '/');
                    }
                } else {
                    $this->redirect($this->base_uri . 'clients/accounts/' . $client->id . '/');
                }
            }
        }

        // Set current account
        if (empty($vars)) {
            $vars = $account;
        }

        // Set the contact info partial to the view
        $this->setContactView($vars, $client, true);
        // Set the ACH info partial to the view
        $this->setAchView($vars, $client, true);

        $this->view($this->view->fetch('admin_clients_editachaccount'));

        if ($this->isAjax()) {
            return false;
        }
    }

    /**
     * Verifies an ACH payment account
     */
    public function verifyAchAccount()
    {
        // Ensure a valid client has been given
        if (!isset($this->get[0]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            $this->redirect($this->base_uri . 'clients/');
        }

        $this->uses(['Payments', 'Accounts']);

        // Ensure a valid account has been given
        if (!isset($this->get[1])
            || !($account = $this->Accounts->getAch((int) $this->get[1]))
            || ($account->client_id != $client->id)
            || ($account->status != 'unverified')
        ) {
            $this->redirect($this->base_uri . 'clients/accounts/' . $client->id . '/');
        }

        // Show notice if the ACH payment type setting is not enabled
        if ($client->settings['payments_allowed_ach'] == 'false') {
            Language::loadLang(['navigation']);
            $this->setMessage(
                'notice',
                Language::_(
                    'AdminClients.!notice.payment_type',
                    true,
                    Language::_('AdminClients.verifyAchAccount.text_ach', true),
                    $this->base_uri . 'clients/accounttypes/' . $client->id . '/',
                    Language::_('Navigation.getcompany.nav_billing_acceptedtypes', true)
                ),
                false,
                ['preserve_tags' => true]
            );
        }

        $vars = [];

        // Verify the ACH account
        if (!empty($this->post)) {
            // Update the account
            $this->Accounts->verifyAchDeposits($account->id, $this->post);

            if (($errors = $this->Accounts->errors())) {
                // Error, reset vars
                $vars = (object) $this->post;
                $vars->gateway_id = $account->gateway_id;
                $this->setMessage('error', $errors);
            } else {
                // Success, account updated
                $this->flashMessage('message', Language::_('AdminClients.!success.verifyachaccount_verified', true));
                $this->redirect($this->base_uri . 'clients/accounts/' . $client->id . '/');
            }
        }

        // Fetch the ach verification form
        $verification_form = $this->Payments->getBuildAchVerificationForm(
            $client->settings['default_currency'],
            (array) $vars
        );

        // Set current account
        if (empty($vars)) {
            $vars = $account;
        }

        $this->set('vars', $vars);
        $this->set('verification_form', $verification_form);

        $this->view($this->view->fetch('admin_clients_verifyachaccount'));

        if ($this->isAjax()) {
            return false;
        }
    }

    /**
     * Delete a CC account
     */
    public function deleteCcAccount()
    {
        // Ensure a valid client has been given
        if (!isset($this->get[0]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            $this->redirect($this->base_uri . 'clients/');
        }

        $this->uses(['Accounts']);

        // Ensure a valid CC account ID has been given and belongs to this client
        if (!isset($this->get[1])
            || !($account = $this->Accounts->getCc((int)$this->get[1]))
            || $account->client_id != $client->id
            || $account->status != 'active'
        ) {
            $this->redirect($this->base_uri . 'clients/accounts/' . $client->id . '/');
        }

        $this->Accounts->deleteCc($account->id);
        // Success, account deleted
        $this->flashMessage('message', Language::_('AdminClients.!success.deleteccaccount_deleted', true));
        $this->redirect($this->base_uri . 'clients/accounts/' . $client->id . '/');
    }

    /**
     * Delete an ACH account
     */
    public function deleteAchAccount()
    {
        // Ensure a valid client has been given
        if (!isset($this->get[0]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            $this->redirect($this->base_uri . 'clients/');
        }

        $this->uses(['Accounts']);

        // Ensure a valid ACH account ID has been given and belongs to this client
        if (!isset($this->get[1]) || !($account = $this->Accounts->getAch((int)$this->get[1]))
            || $account->client_id != $client->id || ($account->status != 'active' && $account->status != 'unverified')) {
            $this->redirect($this->base_uri . 'clients/accounts/' . $client->id . '/');
        }

        $this->Accounts->deleteAch($account->id);
        // Success, account deleted
        $this->flashMessage('message', Language::_('AdminClients.!success.deleteachaccount_deleted', true));
        $this->redirect($this->base_uri . 'clients/accounts/' . $client->id . '/');
    }

    /**
     * Renders a form to enter a passphrase for decrypting a card and returns the
     * card on success
     */
    public function showcard()
    {
        // Ensure a valid client has been given
        if (!isset($this->get[0]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            exit;
        }

        $this->uses(['Accounts', 'Contacts', 'Users']);
        $this->components(['SettingsCollection']);

        // Ensure a valid CC account ID has been given and belongs to this client
        if (!isset($this->get[1])
            || !($account = $this->Accounts->getCc((int)$this->get[1]))
            || $this->Contacts->get($account->contact_id)->client_id != $client->id
        ) {
            exit;
        }

        // Check whether a passphrase is required or not
        $temp = $this->SettingsCollection->fetchSetting(null, $this->company_id, 'private_key_passphrase');
        $passphrase_required = (isset($temp['value']) && $temp['value'] != '');
        unset($temp);

        // Set whether passphrase is required
        $this->set('passphrase_required', $passphrase_required);

        if (!empty($this->post)) {
            $response = new stdClass();
            $error = null;

            // If passphrase is required, decrypt using passphrase
            if ($passphrase_required) {
                $response->account = $this->Accounts->getCc(
                    $account->id,
                    true,
                    $this->post['passphrase'],
                    $this->Session->read('blesta_staff_id')
                );
            } else {
                // If passphrase is not required, require staff to enter account password and verify
                $user = $this->Users->get($this->Session->read('blesta_id'));
                $username = ($user ? $user->username : '');

                if ($this->Users->auth($username, ['password' => $this->post['passphrase']], 'staff')) {
                    $response->account = $this->Accounts->getCc(
                        $account->id,
                        true,
                        null,
                        $this->Session->read('blesta_staff_id')
                    );
                }
                $error = $this->Users->errors();
            }

            // If decryption was unsuccessful, display the appropriate error
            if (!isset($response->account->number) || $response->account->number === false) {
                if ($passphrase_required) {
                    $error = Language::_('AdminClients.showcard.!error.passphrase', true);
                } else {
                    $error = Language::_('AdminClients.showcard.!error.password', true);
                }
            }

            if ($error) {
                $this->setMessage('error', $error);
                $response->view = $this->view->fetch('admin_clients_showcard');
            }

            // JSON encode the AJAX response
            $this->outputAsJson($response);
            return false;
        }

        echo $this->view->fetch('admin_clients_showcard');
        return false;
    }

    /**
     * Renders a form to enter a passphrase for decrypting a bank account and routing number and returns the
     * values on success
     */
    public function showaccount()
    {
        // Ensure a valid client has been given
        if (!isset($this->get[0]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            exit;
        }

        $this->uses(['Accounts', 'Contacts', 'Users']);
        $this->components(['SettingsCollection']);

        // Ensure a valid ACH account ID has been given and belongs to this client
        if (!isset($this->get[1])
            || !($account = $this->Accounts->getAch((int)$this->get[1]))
            || $this->Contacts->get($account->contact_id)->client_id != $client->id
        ) {
            exit;
        }

        // Check whether a passphrase is required or not
        $temp = $this->SettingsCollection->fetchSetting(null, $this->company_id, 'private_key_passphrase');
        $passphrase_required = (isset($temp['value']) && $temp['value'] != '');
        unset($temp);

        // Set whether passphrase is required
        $this->set('passphrase_required', $passphrase_required);

        if (!empty($this->post)) {
            $response = new stdClass();
            $error = null;

            // If passphrase is required, decrypt using passphrase
            if ($passphrase_required) {
                $response->account = $this->Accounts->getAch(
                    $account->id,
                    true,
                    $this->post['passphrase'],
                    $this->Session->read('blesta_staff_id')
                );
            } else {
                // If passphrase is not required, require staff to enter account password and verify
                $user = $this->Users->get($this->Session->read('blesta_id'));
                $username = ($user ? $user->username : '');

                if ($this->Users->auth($username, ['password' => $this->post['passphrase']], 'staff')) {
                    $response->account = $this->Accounts->getAch(
                        $account->id,
                        true,
                        null,
                        $this->Session->read('blesta_staff_id')
                    );
                }
                $error = $this->Users->errors();
            }

            // If decryption was unsuccessful, display the appropriate error
            if (!isset($response->account->account) || $response->account->account === false) {
                if ($passphrase_required) {
                    $error = Language::_('AdminClients.showaccount.!error.passphrase', true);
                } else {
                    $error = Language::_('AdminClients.showaccount.!error.password', true);
                }
            }

            if ($error) {
                $this->setMessage('error', $error);
                $response->view = $this->view->fetch('admin_clients_showaccount');
            }

            // JSON encode the AJAX response
            $this->outputAsJson($response);
            return false;
        }

        echo $this->view->fetch('admin_clients_showaccount');
        return false;
    }

    /**
     * Processes a payment for this client
     */
    public function makePayment()
    {
        $this->uses(['Accounts', 'Contacts', 'Countries', 'Currencies', 'Invoices', 'States', 'Transactions']);
        $this->components(['SettingsCollection']);

        // Get client ID
        if (!isset($this->get[0]) || !($client = $this->Clients->get($this->get[0]))) {
            $this->redirect($this->base_uri . 'clients/');
        }

        // If an invoice ID is set, fetch the invoice and ensure it is active and belongs to this client
        if (isset($this->get[1])
            && !(($invoice = $this->Invoices->get($this->get[1]))
                && ($invoice->status == 'active' || $invoice->status == 'proforma')
                && $invoice->client_id == $client->id && $invoice->date_closed == null
            )
        ) {
            $this->redirect($this->base_uri . 'clients/view/' . $this->get[0] . '/');
        }

        // Set step based on submit button clicked
        $step = isset($this->post['step']) ? $this->post['step'] : 1;
        if (array_key_exists('submit_edit', $this->post)) {
            $step = 2;
        }

        $this->set('client', $client);

        if (isset($this->get[1]) && $invoice) {
            $this->set('invoice', $invoice);
        }

        $vars = new stdClass();
        $vars->country = (!empty($client->settings['country']) ? $client->settings['country'] : '');

        if (isset($this->post['vars'])) {
            $vars = (object)array_merge((array)unserialize(base64_decode($this->post['vars'])), (array)$vars);
            unset($this->post['vars']);
        }

        // Allow POST to override any $vars
        foreach ($this->post as $field => $value) {
            if (isset($vars->$field) || property_exists($vars, $field)) {
                unset($vars->$field);
            }
        }

        // Default the given invoice to selected, if not already set, use 'credit'
        // as the basis for reference on whether the invoice selection screen has been previously submitted
        if (isset($this->get[1]) && !empty($this->post)
            && !isset($this->post['credit']) && !isset($this->post['invoice_id'])) {
            $this->post['invoice_id'][] = $this->get[1];
        }

        // Set payment currency
        $vars->currency = $invoices['currency'] ?? $invoice->currency ?? $client->settings['default_currency'] ?? null;

        $vars = (object)array_merge($this->post, (array)$vars);

        switch ($step) {
            // Verify payment details / Store payment account
            default:
            case '1':
                if (!empty($this->post)) {
                    if (!isset($this->post['pay_with'])) {
                        $this->flashMessage('error', Language::_('AdminClients.!error.pay_with.required', true));
                        $this->redirect($this->base_uri . 'clients/makepayment/' . $this->get[0] . '/');
                    }

                    if ($this->post['pay_with'] == 'details') {
                        // Fetch the contact we're about to set the payment account for
                        $contact = $this->Contacts->get((isset($vars->contact_id) ? $vars->contact_id : 0));

                        if ($vars->contact_id == 'none' || !$contact || ($contact->client_id != $client->id)) {
                            $vars->contact_id = $client->contact_id;
                        }

                        // Attempt to save the account, then set it as the account to use
                        if (isset($this->post['save_details']) && $this->post['save_details'] == 'true') {
                            if ($this->post['payment_type'] == 'ach') {
                                $account_id = $this->Accounts->addAch((array)$vars);

                                // Assign the newly created payment account as the account to use for this payment
                                if ($account_id) {
                                    $vars->payment_account = 'ach_' . $account_id;
                                    $vars->pay_with = 'account';
                                }
                            } elseif ($this->post['payment_type'] == 'cc') {
                                if (isset($vars->expiration_year) || isset($vars->expiration_month)) {
                                    // Concatenate the expiration date to the form 'yyyymm'
                                    $vars->expiration = (
                                            isset($vars->expiration_year) ? $vars->expiration_year : ''
                                        ) . (isset($vars->expiration_month) ? $vars->expiration_month : '');
                                }

                                // will automatically determine card type
                                unset($vars->type);
                                $account_id = $this->Accounts->addCc((array)$vars);

                                // Assign the newly created payment account as the account to use for this payment
                                if ($account_id) {
                                    $vars->payment_account = 'cc_' . $account_id;
                                    $vars->pay_with = 'account';
                                }
                            }
                        } else {
                            // Verify the payment account details entered were correct, since we're not storing them
                            $vars_arr = (array)$vars;
                            if ($this->post['payment_type'] == 'ach') {
                                $this->Accounts->verifyAch($vars_arr);
                            } elseif ($this->post['payment_type'] == 'cc') {
                                if (isset($vars->expiration_year) || isset($vars->expiration_month)) {
                                    // Concatenate the expiration date to the form 'yyyymm'
                                    $vars->expiration = (
                                            isset($vars->expiration_year) ? $vars->expiration_year : ''
                                        ) . (isset($vars->expiration_month) ? $vars->expiration_month : '');
                                }

                                // will automatically determine card type
                                unset($vars->type);
                                $vars_arr = (array)$vars;
                                $this->Accounts->verifyCc($vars_arr, !isset($vars->reference_id));
                            }

                            if (isset($vars_arr['type'])) {
                                $vars->type = $vars_arr['type'];
                            }
                            unset($vars_arr);
                        }
                    }

                    if (($errors = $this->Accounts->errors())) {
                        $this->setMessage('error', $errors);
                    } else {
                        $vars->email_receipt = 'true';
                        $step = '2';
                    }
                }
                break;
            // Verify payment amounts
            case '2':
                if (!empty($this->post) && count($this->post) > 2) {
                    if (!isset($this->post['invoice_id'])) {
                        unset($vars->invoice_id);
                    }
                    if (!isset($this->post['email_receipt'])) {
                        unset($vars->email_receipt);
                    }

                    // Single invoice
                    if (isset($this->get[1]) && $invoice) {
                        $vars->currency = $invoice->currency;
                    } else {
                        $vars->currency = $this->post['currency'];
                    }

                    // Verify payment amounts, ensure that amounts entered do no exceed total due on invoice
                    if (isset($vars->invoice_id)) {
                        $apply_amounts = ['amounts' => []];
                        foreach ($vars->invoice_id as $inv_id) {
                            if (isset($vars->applyamount[$inv_id])) {
                                $apply_amounts['amounts'][] = [
                                    'invoice_id' => $inv_id,
                                    'amount' => $this->CurrencyFormat->cast(
                                        $vars->applyamount[$inv_id],
                                        $vars->currency
                                    )
                                ];
                            }
                        }

                        $this->Transactions->verifyApply($apply_amounts, false);
                    }

                    if (($errors = $this->Transactions->errors())) {
                        $this->setMessage('error', $errors);
                    } else {
                        $step = '3';
                    }
                }
                break;
            // Execute payment
            case '3':
                if (!empty($this->post)) {
                    $total = $this->CurrencyFormat->cast($vars->credit, $vars->currency);
                    $apply_amounts = [];

                    if (isset($vars->invoice_id)) {
                        foreach ($vars->invoice_id as $inv_id) {
                            // If an amount was set for the selected invoice, calculate that value
                            if (isset($vars->applyamount[$inv_id])) {
                                $apply_amounts[$inv_id] = $this->CurrencyFormat->cast(
                                    $vars->applyamount[$inv_id],
                                    $vars->currency
                                );
                                $total += $apply_amounts[$inv_id];
                            }
                        }
                    }

                    $this->uses(['Payments']);

                    $options = [
                        'invoices' => $apply_amounts,
                        'staff_id' => $this->Session->read('blesta_staff_id'),
                        'email_receipt' => isset($vars->email_receipt) ? $vars->email_receipt : 'false'
                    ];

                    if ($vars->pay_with == 'account') {
                        $account_info = null;
                        [$type, $account_id] = explode('_', $vars->payment_account, 2);
                    } else {
                        $type = $vars->payment_type;
                        $account_id = null;
                        $account_info = [
                            'first_name' => $vars->first_name,
                            'last_name' => $vars->last_name,
                            'address1' => $vars->address1,
                            'address2' => $vars->address2,
                            'city' => $vars->city,
                            'state' => $vars->state,
                            'country' => $vars->country,
                            'zip' => $vars->zip
                        ];

                        if ($type == 'ach') {
                            $account_info = $this->getAchAccountInfo($vars);
                        } elseif ($type == 'cc') {
                            $account_info = $this->getCcAccountInfo($vars);
                        }
                    }

                    // Capture payment if the funds were previously authorized, otherwise process the whole payment now
                    $transaction_id = $this->Session->read('authorized_transaction_id');
                    if ($transaction_id) {
                        // Capture the payment
                        $transaction = $this->Payments->capturePayment(
                            $client->id,
                            $transaction_id,
                            $total,
                            $options
                        );

                        $this->Session->write('authorized_transaction_id', null);
                    } else {
                        // Process the payment
                        $transaction = $this->Payments->processPayment(
                            $client->id,
                            $type,
                            $total,
                            $vars->currency,
                            $account_info,
                            $account_id,
                            $options
                        );
                    }

                    if (($errors = $this->Payments->errors())) {
                        // Unset the last4 so that the view doesn't block out non-stored payment accounts
                        unset($vars->last4);
                        $this->setMessage('error', $errors);
                        $step = '1';
                    } else {
                        $this->flashMessage(
                            'message',
                            Language::_(
                                'AdminClients.!success.makepayment_processed',
                                true,
                                $this->CurrencyFormat->format($transaction->amount, $transaction->currency),
                                $transaction->transaction_id
                            )
                        );
                        $this->redirect($this->base_uri . 'clients/view/' . $client->id . '/');
                    }
                }
                break;
        }

        switch ($step) {
            case '1':
                // Fetch the auto-debit payment account (if set), so we can identify it
                $autodebit = $this->Clients->getDebitAccount($client->id);

                // Get ACH payment types
                $ach_types = $this->Accounts->getAchTypes();
                // Get CC payment types
                $cc_types = $this->Accounts->getCcTypes();

                // Set the payment types allowed
                $transaction_types = $this->Transactions->transactionTypeNames();
                $payment_types = [];
                if ($client->settings['payments_allowed_ach'] == 'true') {
                    $payment_types['ach'] = $transaction_types['ach'];
                }
                if ($client->settings['payments_allowed_cc'] == 'true') {
                    $payment_types['cc'] = $transaction_types['cc'];
                }

                // Set available payment accounts
                $payment_accounts = [];

                // Only allow CC payment accounts if enabled
                if (isset($payment_types['cc'])) {
                    $cc = $this->Accounts->getAllCcByClient($client->id);
                    if ($cc) {
                        $payment_accounts[] = [
                            'value' => 'optgroup',
                            'name' => Language::_('AdminClients.makepayment.field_paymentaccount_cc', true)
                        ];
                    }

                    foreach ((array)$cc as $account) {
                        $is_autodebit = false;
                        if ($autodebit && $autodebit->type == 'cc' && $autodebit->account_id == $account->id) {
                            $is_autodebit = true;
                            $vars->payment_account = 'cc_' . $account->id;
                        }
                        $lang_define = ($is_autodebit
                            ? 'AdminClients.makepayment.field_paymentaccount_autodebit'
                            : 'AdminClients.makepayment.field_paymentaccount'
                        );
                        $payment_accounts['cc_' . $account->id] = Language::_(
                            $lang_define,
                            true,
                            $account->first_name,
                            $account->last_name,
                            $cc_types[$account->type],
                            $account->last4
                        );
                    }
                }

                // Only allow ACH payment accounts if enabled
                if (isset($payment_types['ach'])) {
                    $ach = $this->Accounts->getAllAchByClient($client->id);
                    if ($ach) {
                        $payment_accounts[] = [
                            'value' => 'optgroup',
                            'name' => Language::_('AdminClients.makepayment.field_paymentaccount_ach', true)
                        ];
                    }

                    foreach ((array)$ach as $account) {
                        $is_autodebit = false;
                        if ($autodebit && $autodebit->type == 'ach' && $autodebit->account_id == $account->id) {
                            $is_autodebit = true;
                            $vars->payment_account = 'ach_' . $account->id;
                        }
                        $lang_define = ($is_autodebit
                            ? 'AdminClients.makepayment.field_paymentaccount_autodebit'
                            : 'AdminClients.makepayment.field_paymentaccount'
                        );
                        $payment_accounts['ach_' . $account->id] = Language::_(
                            $lang_define,
                            true,
                            $account->first_name,
                            $account->last_name,
                            $ach_types[$account->type],
                            $account->last4
                        );
                    }
                }
                $this->set('payment_accounts', $payment_accounts);
                $this->set('require_passphrase', !empty($client->settings['private_key_passphrase']));

                // Set currency
                $vars->currency = $invoice->currency ?? $client->settings['default_currency'] ?? null;

                // Set the contact info partial to the view
                $this->setContactView($vars, $client);
                // Set the CC info partial to the view
                $this->setCcView($vars, $client, false, true);
                // Set the ACH info partial to the view
                $this->setAchView($vars, $client, false, true);

                $this->set('payment_types', $payment_types);

                if (isset($invoice)) {
                    $this->set('invoice', $invoice);
                }

                break;
            case '2':
                $this->action = $this->action . 'amount';

                if (!isset($vars->currency)) {
                    $vars->currency = $client->settings['default_currency'];
                }

                // Get all invoices open for this client (to be paid)
                $invoices = ((isset($this->get[1]) && $invoice)
                    ? [$invoice]
                    : $this->Invoices->getAll($client->id, 'open', ['date_due' => 'ASC'], $vars->currency)
                );
                $this->set(
                    'invoice_info',
                    $this->partial('admin_clients_makepaymentinvoices', ['vars' => $vars, 'invoices' => $invoices])
                );

                // All currencies available
                $this->set(
                    'currencies',
                    $this->Form->collapseObjectArray($this->Currencies->getAll($this->company_id), 'code', 'code')
                );
                break;
            case '3':
                $this->uses(['Payments']);

                $this->action = $this->action . 'confirm';
                $total = $this->CurrencyFormat->cast($vars->credit, $vars->currency);
                $invoices = [];
                $apply_amounts = [];

                if (isset($vars->invoice_id) && is_array($vars->invoice_id)) {
                    for ($i = 0, $invoice_ids = count($vars->invoice_id); $i < $invoice_ids; $i++) {
                        // If an amount was set for the selected invoice, calculate that value
                        if (isset($vars->applyamount[$vars->invoice_id[$i]])) {
                            $apply_amounts[$vars->invoice_id[$i]] = $this->CurrencyFormat->cast(
                                $vars->applyamount[$vars->invoice_id[$i]],
                                $vars->currency
                            );
                            $total += $apply_amounts[$vars->invoice_id[$i]];
                        }

                        $invoice = $this->Invoices->get($vars->invoice_id[$i]);
                        if ($invoice && $invoice->client_id == $client->id) {
                            $invoices[] = $invoice;
                        }
                    }
                }

                // Set the payment account being used if one exists
                $type = null;
                $account_id = null;
                if ($vars->pay_with == 'account') {
                    [$type, $account_id] = explode('_', $vars->payment_account, 2);

                    if ($type == 'cc') {
                        $this->set('account', $this->Accounts->getCc($account_id));
                    } elseif ($type == 'ach') {
                        $this->set('account', $this->Accounts->getAch($account_id));
                    }

                    $this->set('account_type', $type);
                    $this->set('account_id', $account_id);
                } else {
                    $type = $vars->payment_type;

                    if ($vars->payment_type == 'ach' && isset($vars->account)) {
                        $vars->last4 = substr($vars->account, -4);
                    } elseif ($vars->payment_type == 'cc' && isset($vars->number)) {
                        $vars->last4 = substr($vars->number, -4);
                    }
                    $this->set('account_type', $type);
                    $this->set('account', $vars);
                }

                // Attempt to authorize the payment. If successful, the funds will be captured after
                // confirmation. Otherwise all payment steps will be completed after submission using
                // Payments::processPayment()
                $this->set(
                    'merchant_payment_confirmation',
                    $this->buildPaymentConfirmation($type, $client->id, $account_id, $vars, $apply_amounts, $total)
                );

                $this->set('vars', $vars);
                $this->set('invoices', $invoices);
                $this->set('currency', $vars->currency);
                $this->set('apply_amounts', $apply_amounts);
                $this->set('total', $total);
                $this->set('account_types', $this->Accounts->getTypes());
                $this->set('ach_types', $this->Accounts->getAchTypes());
                $this->set('cc_types', $this->Accounts->getCcTypes());

                break;
        }

        $this->set('vars', $vars);
        $this->set('step', $step);

        if ($this->isAjax()) {
            return $this->renderAjaxWidgetIfAsync();
        }
        return $this->renderClientView($this->controller . '_' . $this->action);
    }

    /**
     * Authorizes a payment and builds the payment confirmation view for the gateway
     *
     * @param string $type The type of transaction ('cc', or 'ach')
     * @param int $client_id The ID of the client making the payment
     * @param stdClass $vars An stdClass object representing the payment account details
     * @param array $apply_amounts An array of invoice amounts to pay
     * @param float $total The total amount to pay
     * @return string The HTML for the payment confirmation
     */
    private function buildPaymentConfirmation($type, $client_id, $account_id, $vars, array $apply_amounts, $total)
    {
        // Authorize a payment transaction
        $transaction = $this->authorizePayment($type, $client_id, $account_id, $vars, $apply_amounts, $total);

        if (!empty($transaction)) {
            return $this->Payments->getBuildPaymentConfirmation($client_id, $transaction->id);
        }

        return '';
    }

    /**
     * Attempts to create a payment authorization
     *
     * @param string $type The type of transaction ('cc', or 'ach')
     * @param int $client_id The ID of the client making the payment
     * @param stdClass $vars An stdClass object representing the payment account details
     * @param array $apply_amounts An array of invoice amounts to pay
     * @param float $total The total amount to pay
     * @return stdClass|null An stdClass object representing the transaction for this authorization if successful,
     *  otherwise null
     */
    private function authorizePayment($type, $client_id, $account_id, $vars, array $apply_amounts, $total)
    {
        $transaction_id = $this->Session->read('authorized_transaction_id');
        if ($transaction_id) {
            // Void the previously authorized transaction so we don't end up with a bunch
            // of hanging transaction holding funds on the CC. Give no error if this doesn't
            // succeed, it is just a best attempt to keep records clean
            $this->Payments->voidPayment($client_id, $transaction_id);
        }

        $transaction = null;
        if ($type == 'cc') {
            $options = [
                'invoices' => $apply_amounts,
                'staff_id' => $this->Session->read('blesta_staff_id'),
                'email_receipt' => isset($vars->email_receipt) ? $vars->email_receipt : false
            ];

            if ($vars->pay_with == 'account') {
                $account_info = null;
            } else {
                $account_info = $this->getCcAccountInfo($vars);
            }

            // Attempt to authorize the payment. This may not be supported by the current merchant gateway
            $transaction = $this->Payments->authorizePayment(
                $client_id,
                $type,
                $total,
                $vars->currency,
                $account_info,
                $account_id,
                $options
            );

            $errors = $this->Payments->errors();
            if ($errors) {
                foreach ($errors as $error) {
                    if (!array_key_exists('unsupported', $error)) {
                        $this->setMessage('error', $errors);
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
     * Formats ach account info for making payments
     *
     * @param stdClass $vars A list of payment info
     * @return array A formatted list of payment info
     */
    private function getAchAccountInfo($vars)
    {
        $account_info = [
            'first_name' => $vars->first_name,
            'last_name' => $vars->last_name,
            'address1' => $vars->address1,
            'address2' => $vars->address2,
            'city' => $vars->city,
            'state' => $vars->state,
            'country' => $vars->country,
            'zip' => $vars->zip
        ];

        // Since we support gateway ach forms, we can't guarantee that these fields will be set
        $account_info['account_number'] = $vars->account ?? null;
        $account_info['routing_number'] = $vars->routing ?? null;
        $account_info['type'] = $vars->type ?? null;
        $account_info['reference_id'] = $vars->reference_id ?? null;
        $account_info['client_reference_id'] = $vars->client_reference_id ?? null;

        return $account_info;
    }

    /**
     * Formats cc account info for making payments
     *
     * @param stdClass $vars A list of payment info
     * @return array A formatted list of payment info
     */
    private function getCcAccountInfo($vars)
    {
        $account_info = [
            'first_name' => $vars->first_name,
            'last_name' => $vars->last_name,
            'address1' => $vars->address1,
            'address2' => $vars->address2,
            'city' => $vars->city,
            'state' => $vars->state,
            'country' => $vars->country,
            'zip' => $vars->zip
        ];

        // Since we support gateway cc forms, we can't guarantee that these fields will be set
        $account_info['card_number'] = $vars->number ?? null;
        $account_info['card_exp'] = isset($vars->expiration_year) && isset($vars->expiration_month)
            ? $vars->expiration_year . $vars->expiration_month
            : null;
        $account_info['card_security_code'] = $vars->security_code ?? null;
        $account_info['reference_id'] = $vars->reference_id ?? null;
        $account_info['client_reference_id'] = $vars->client_reference_id ?? null;

        return $account_info;
    }

    /**
     * Fetches a table of invoices for the given currency
     */
    public function makePaymentInvoices()
    {
        $this->uses(['Invoices']);

        if (!isset($this->get[0]) || !($client = $this->Clients->get($this->get[0], false))) {
            return false;
        }

        $vars = [];
        if (isset($this->post['currency'])) {
            $vars['invoices'] = $this->Invoices->getAll(
                $client->id,
                'open',
                ['date_due' => 'ASC'],
                $this->post['currency']
            );
        }
        $vars['vars'] = (object)$this->post;

        $this->outputAsJson(['content' => $this->partial('admin_clients_makepaymentinvoices', $vars)]);
        return false;
    }

    /**
     * Manually record a payment for this client (i.e. record payment by check)
     */
    public function recordPayment()
    {
        $this->uses(['Currencies', 'Emails', 'Invoices', 'Transactions', 'GatewayManager']);
        $this->components(['SettingsCollection']);

        // Set step based on submit button clicked
        $step = isset($this->post['step']) ? $this->post['step'] : 1;
        if (array_key_exists('submit_edit', $this->post)) {
            $step = 1;
        }

        // Get client ID
        if (!isset($this->get[0]) || !($client = $this->Clients->get($this->get[0]))) {
            $this->redirect($this->base_uri . 'clients/');
        }

        // If an invoice ID is set, fetch the invoice and ensure it is active and belongs to this client
        if (isset($this->get[1])
            && !(($invoice = $this->Invoices->get($this->get[1]))
                && ($invoice->status == 'active' || $invoice->status == 'proforma')
                && $invoice->client_id == $client->id
            )
        ) {
            $this->redirect($this->base_uri . 'clients/' . $this->get[0] . '/');
        }

        $vars = new stdClass();

        if (isset($this->post['vars'])) {
            $vars = (object)array_merge((array)unserialize(base64_decode($this->post['vars'])), (array)$vars);
            unset($this->post['vars']);
        }

        // Default the currency to the invoice's currency
        // unless a currency is provided
        if (isset($invoice) && $invoice && !isset($vars->currency)) {
            $vars->currency = $invoice->currency;
        }

        // Allow POST to override any $vars
        foreach ($this->post as $field => $value) {
            if (isset($vars->$field) || property_exists($vars, $field)) {
                unset($vars->$field);
            }
        }

        // Default the given invoice to selected, if not already set, use 'credit'
        // as the basis for reference on whether the invoice selection screen has been previously submitted
        if (isset($this->get[1]) && !isset($this->post['credit']) && !isset($this->post['invoice_id'])) {
            $this->post['invoice_id'][] = $this->get[1];
        }

        $transaction_types = $this->Transactions->transactionTypeNames();
        $vars = (object)array_merge($this->post, (array)$vars);

        switch ($step) {
            default:
            case '1':
                // Submit intends to go back to edit step 1, so do no processing of step 1 here
                if (array_key_exists('submit_edit', $this->post)) {
                    break;
                }

                if (!empty($this->post) && count($this->post) > 2) {
                    $vars->currency = $this->post['currency'];

                    // Make sure that invoices are selected when attempting to apply a credit
                    $use_credit = (isset($vars->payment_type) && $vars->payment_type == 'credit');
                    if ($use_credit) {
                        $invoices_selected = false;
                        $invoice_ids = (isset($vars->invoice_id) ? $vars->invoice_id : []);
                        foreach ($invoice_ids as $inv_id) {
                            if (isset($vars->applyamount[$inv_id])) {
                                $invoices_selected = true;
                                break;
                            }
                        }

                        // An invoice must be selected so that credits can be applied
                        if (!$invoices_selected) {
                            $this->setMessage(
                                'error',
                                Language::_('AdminClients.!error.invoice_credits.required', true)
                            );
                            break;
                        }
                    }

                    // Verify payment amounts, ensure that amounts entered do not exceed total due on invoice
                    if (isset($vars->invoice_id)) {
                        $apply_amounts = ['amounts' => []];
                        foreach ($vars->invoice_id as $inv_id) {
                            if (isset($vars->applyamount[$inv_id])) {
                                $apply_amounts['amounts'][] = [
                                    'invoice_id' => $inv_id,
                                    'amount' => $this->CurrencyFormat->cast(
                                        $vars->applyamount[$inv_id],
                                        $vars->currency
                                    )
                                ];
                            }
                        }

                        // Verify the the amount specified, or the credit amount
                        $amount = (isset($vars->amount) ? $vars->amount : 0);
                        $amount = ($use_credit ? $this->getCreditPaymentAmount($client->id, $vars) : $amount);
                        $amount = $this->CurrencyFormat->cast($amount, $vars->currency);
                        $this->Transactions->verifyApply($apply_amounts, false, $amount);
                    }

                    if (($errors = $this->Transactions->errors())) {
                        $this->setMessage('error', $errors);
                    } else {
                        $step = '2';
                    }
                }

                break;
            case '2':
                if (!empty($this->post)) {
                    if (isset($vars->status) && $vars->status != 'approved') {
                        unset($vars->invoice_id);
                        unset($vars->email_receipt);
                    }

                    // Set apply amounts for invoices if given
                    if (isset($vars->invoice_id)) {
                        $apply_amounts = ['amounts' => []];
                        foreach ($vars->invoice_id as $inv_id) {
                            if (isset($vars->applyamount[$inv_id])) {
                                $apply_amounts['amounts'][] = [
                                    'invoice_id' => $inv_id,
                                    'amount' => $this->CurrencyFormat->cast(
                                        $vars->applyamount[$inv_id],
                                        $vars->currency
                                    )
                                ];
                            }
                        }
                    }

                    // Apply credits, or record a manual payment
                    if (isset($vars->payment_type) && $vars->payment_type == 'credit') {
                        if (isset($apply_amounts) && !empty($apply_amounts['amounts'])) {
                            // Apply credits
                            $this->Transactions->applyFromCredits(
                                $client->id,
                                $vars->currency,
                                $apply_amounts['amounts']
                            );

                            if (($errors = $this->Transactions->errors())) {
                                $this->setMessage('error', $errors);
                                $step = '1';
                            } else {
                                $this->flashMessage(
                                    'message',
                                    Language::_('AdminClients.!success.recordpayment_credits', true)
                                );
                                $this->redirect($this->base_uri . 'clients/view/' . $client->id . '/');
                            }
                        }
                    } else {
                        // Translate the transaction type into the type and transaction type ID
                        // suitable for the Transactions model
                        $payment_types = $this->Form->collapseObjectArray(
                            $this->Transactions->getTypes(),
                            'id',
                            'name'
                        );

                        if (isset($payment_types[$vars->transaction_type])) {
                            $type = 'other';
                            $transaction_type_id = $payment_types[$vars->transaction_type];
                        } else {
                            $type = $vars->transaction_type;
                            $transaction_type_id = null;
                        }

                        // Build the transaction for recording
                        $transaction = [
                            'client_id' => $client->id,
                            'amount' => $vars->amount,
                            'currency' => $vars->currency,
                            'status' => $vars->status,
                            'type' => $type,
                            'transaction_type_id' => $transaction_type_id,
                            'transaction_id' => $vars->transaction_id,
                            'gateway_id' => isset($vars->gateway_id) ? $vars->gateway_id : null,
                            'date_added' => $vars->date_received
                        ];

                        // Record the transactions
                        $transaction_id = $this->Transactions->add($transaction);
                        $errors = $this->Transactions->errors();

                        // Apply transaction amounts if given
                        if (!$errors && !empty($apply_amounts)) {
                            // Apply the transaction to the selected invoices
                            $this->Transactions->apply($transaction_id, $apply_amounts);
                            $errors = $this->Transactions->errors();
                        }

                        if ($errors) {
                            $this->setMessage('error', $errors);
                            $step = '1';
                        } else {
                            $transaction = $this->Transactions->get($transaction_id);
                            $amount = $this->CurrencyFormat->format($transaction->amount, $transaction->currency);

                            // If set to email the client and the transaction is approved, send the email
                            if (isset($vars->email_receipt)
                                && $vars->email_receipt == 'true'
                                && $transaction->status == 'approved'
                            ) {
                                $tags = [
                                    'contact' => $client,
                                    'amount' => $amount,
                                    'transaction_id' => $transaction->transaction_id,
                                    'payment_type' => $transaction_types[$vars->transaction_type],
                                    'date_added' => $this->Date->cast($transaction->date_added, 'date_time')
                                ];

                                $this->Emails->send(
                                    'payment_manual_approved',
                                    $this->company_id,
                                    $client->settings['language'],
                                    $client->email,
                                    $tags,
                                    null,
                                    null,
                                    null,
                                    [
                                        'to_client_id' => $client->id,
                                        'from_staff_id' => $this->Session->read('blesta_staff_id')
                                    ]
                                );
                            }

                            $this->flashMessage(
                                'message',
                                Language::_('AdminClients.!success.recordpayment_processed', true, $amount)
                            );
                            $this->redirect($this->base_uri . 'clients/view/' . $client->id . '/');
                        }
                    }
                }

                break;
        }

        switch ($step) {
            case '1':
                if (!isset($vars->currency)) {
                    $vars->currency = $client->settings['default_currency'];
                }

                // All currencies available
                $this->set(
                    'currencies',
                    $this->Form->collapseObjectArray($this->Currencies->getAll($this->company_id), 'code', 'code')
                );
                // Get all invoices open for this client (to be paid)
                $this->set(
                    'invoice_info',
                    $this->partial(
                        'admin_clients_makepaymentinvoices',
                        [
                            'vars' => $vars,
                            'invoices' => $this->Invoices->getAll(
                                $client->id,
                                'open',
                                ['date_due' => 'ASC'],
                                $vars->currency
                            )
                        ]
                    )
                );

                if (isset($invoice)) {
                    $this->set('invoice', $invoice);
                }
                break;
            case '2':
                $this->action = $this->action . 'confirm';

                // Set the the amount specified, or the credit amount
                $amount = (isset($vars->amount) ? $vars->amount : '');
                $total = ($amount && isset($vars->currency)
                    ? $this->CurrencyFormat->cast($amount, $vars->currency)
                    : $amount
                );

                if (isset($vars->payment_type) && $vars->payment_type == 'credit') {
                    // Set the amount we're applying from the credit
                    if (isset($apply_amounts['amounts'])) {
                        $total = 0;
                        foreach ($apply_amounts['amounts'] as $amount) {
                            $total += $amount['amount'];
                        }
                    } else {
                        $total = $this->getCreditPaymentAmount($client->id, $vars);
                    }
                }
                $this->set('total', $total);
                break;
        }

        $this->set('vars', $vars);
        $this->set('step', $step);
        $this->set('transaction_types', $transaction_types);
        $this->set('nonmerchant_gateways', $this->GatewayManager->getAll($this->company_id, 'nonmerchant'));
        $this->set('merchant_gateways', $this->GatewayManager->getAll($this->company_id, 'merchant'));
        $this->set('currency', $vars->currency);
        $this->set('statuses', $this->Transactions->transactionStatusNames());
        $this->set('client', $client);
        $this->set('record_payment_fields', $this->getRecordCreditFields($client->id, $vars->currency, $vars));

        if ($this->isAjax()) {
            return $this->renderAjaxWidgetIfAsync();
        }
        return $this->renderClientView($this->controller . '_' . $this->action);
    }

    /**
     * Fetches the total credit amount available for the client in the given currency and payment type
     * @see AdminClients::recordPayment()
     *
     * @param int $client_id The ID of the client
     * @param stdClass An stdClass object representing variable input, including:
     *
     *  - currency The currency that the credit should be in
     *  - payment_type The payment type. If not a value of "credit", 0 will be returned
     * @return float The total amount of credit available
     */
    private function getCreditPaymentAmount($client_id, stdClass $vars)
    {
        if (!isset($this->Transactions)) {
            $this->uses(['Transactions']);
        }

        if (!empty($vars->currency) && isset($vars->payment_type) && $vars->payment_type == 'credit') {
            return $this->Transactions->getTotalCredit($client_id, $vars->currency);
        }

        return 0;
    }

    /**
     * AJAX Fetches a partial containing the record payment/credit fields
     * @see AdminClients::recordPayment()
     *
     * @param int $client_id The client ID
     * @param string $currency The currency to fetch credits in
     * @param stdClass $vars An stdClass object representing input vars
     * @return string A partial of the fields
     */
    public function getRecordCreditFields($client_id = null, $currency = null, $vars = null)
    {
        $return = ($client_id !== null);
        $client_id = ($client_id !== null ? $client_id : (isset($this->get[0]) ? $this->get[0] : null));
        $client = $this->Clients->get($client_id);
        $currency = ($currency !== null
            ? $currency
            : (isset($this->post['currency']) ? $this->post['currency'] : null)
        );

        // Ensure a valid client was given
        if ((!$return && !$this->isAjax()) || !$client) {
            if (!$return) {
                header($this->server_protocol . ' 401 Unauthorized');
                exit();
            }
            return $this->partial('admin_clients_recordpayment_credit', []);
        }

        if (!isset($this->Transactions)) {
            $this->uses(['Transactions']);
        }

        if (!$currency) {
            $currency = $client->settings['default_currency'];
        }

        $vars = [
            'credit' => $this->Transactions->getTotalCredit($client->id, $currency),
            'currency' => $currency,
            'vars' => (object)array_merge((array)$vars, (!empty($this->post) ? $this->post : []))
        ];

        $fields = $this->partial('admin_clients_recordpayment_credit', $vars);

        if ($return) {
            return $fields;
        }

        // JSON encode the AJAX response
        $this->outputAsJson(['content' => $fields]);
        return false;
    }


    /**
     * Sets the contact partial view
     * @see AdminClients::makePayment(), AdminClients::addAchAccount(), AdminClients::addCcAccount(),
     *  AdminClients::editAchAccount(), AdminClients::editCcAccount()
     *
     * @param stdClass $vars The input vars object for use in the view
     * @param stdClass $client The client object whose contacts to use
     * @param bool $edit True if this is an edit, false otherwise
     */
    private function setContactView(stdClass $vars, stdClass $client, $edit = false)
    {
        $contacts = [];

        if (!$edit) {
            // Set an option for no contact
            $no_contact = [
                (object)[
                    'id' => 'none',
                    'first_name' => Language::_('AdminClients.setcontactview.text_none', true),
                    'last_name' => ''
                ]
            ];

            // Set all contacts whose info can be prepopulated (primary or billing only)
            $contacts = array_merge(
                $this->Contacts->getAll($client->id, 'primary'),
                $this->Contacts->getAll($client->id, 'billing')
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
            'edit' => $edit
        ];
        $this->set('contact_info', $this->partial('admin_clients_account_contactinfo', $contact_info));
    }

    /**
     * Sets the ACH partial view
     * @see AdminClients::makePayment(), AdminClients::addAchAccount(), AdminClients::editAchAccount()
     *
     * @param stdClass $vars The input vars object for use in the view
     * @param stdClass $client The client object whose contacts to use
     * @param bool $edit True if this is an edit, false otherwise
     * @param bool $save_account True to offer an option to save these payment details, false otherwise
     */
    private function setAchView(stdClass $vars, stdClass $client, $edit = false, $save_account = false)
    {
        $this->uses(['Payments', 'GatewayManager']);
        $this->components(['Gateways']);

        // Fetch the ach form to be used with this company and currency
        $gateway_form = $this->Payments->getBuildAchForm($vars->currency ?? $client->settings['default_currency'], (array)$vars);

        // Check if the account must be verified
        $gateway = $this->GatewayManager->getInstalledMerchant($this->company_id, $client->settings['default_currency']);
        if ($gateway) {
            $gateway_obj = $this->Gateways->create($gateway->class, $gateway->type);

            $caller_function = function_exists('debug_backtrace') ?
                (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] ?? null) : null;

            if ($gateway_obj instanceof MerchantAchVerification) {
                if ($caller_function == 'addAchAccount' || $caller_function == 'editAchAccount') {
                    $this->setMessage(
                        'notice',
                        Language::_('AdminClients.!notice.ach_edit', true)
                    );
                } else {
                    $message = $this->setMessage(
                        'notice',
                        Language::_('AdminClients.!notice.ach_verification', true),
                        true
                    );
                }
            }
        }

        // Set partial for ACH info
        $ach_info = [
            'types' => $this->Accounts->getAchTypes(),
            'vars' => $vars,
            'edit' => $edit,
            'client' => $client,
            'gateway_form' => $gateway_form,
            'save_account' => $save_account,
            'message' => $message ?? ''
        ];
        $this->set('ach_info', $this->partial('admin_clients_account_achinfo', $ach_info));
    }

    /**
     * Sets the CC partial view
     * @see AdminClients::makePayment(), AdminClients::addCcAccount(), AdminClients::editCcAccount()
     *
     * @param stdClass $vars The input vars object for use in the view
     * @param stdClass $client The client object whose contacts to use
     * @param bool $edit True if this is an edit, false otherwise
     * @param bool $save_account True to offer an option to save these payment details, false otherwise
     */
    private function setCcView(stdClass $vars, stdClass $client, $edit = false, $save_account = false)
    {
        $this->uses(['Payments']);

        // Fetch the cc form to be used with this company and currency
        $gateway_form = $this->Payments->getBuildCcForm($vars->currency ?? $client->settings['default_currency']);

        // Set available credit card expiration dates
        $years = $this->Date->getYears(date('Y'), date('Y') + 10, 'Y', 'Y');

        // Set the card year in case of an old, expired, card
        if (!empty($vars->expiration_year)
            && !array_key_exists($vars->expiration_year, $years)
            && preg_match('/^[0-9]{4}$/', $vars->expiration_year)
        ) {
            $card_year = [$vars->expiration_year => $vars->expiration_year];

            if ((int)$vars->expiration_year < reset($years)) {
                $years = $card_year + $years;
            } elseif ((int)$vars->expiration_year > end($years)) {
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
            'client' => $client,
            'gateway_form' => $gateway_form,
            'save_account' => $save_account
        ];
        $this->set('cc_info', $this->partial('admin_clients_account_ccinfo', $cc_info));
    }

    /**
     * AJAX Fetches the currency amounts for the client profile sidebar
     */
    public function getCurrencyAmounts()
    {
        // Ensure a valid client was given
        if (!$this->isAjax() || !isset($this->get[0]) || !($client = $this->Clients->get($this->get[0]))) {
            header($this->server_protocol . ' 401 Unauthorized');
            exit();
        }

        $this->uses(['Currencies', 'Invoices', 'Transactions']);

        $currency_code = $client->settings['default_currency'];
        if (isset($this->get[1]) && ($currency = $this->Currencies->get($this->get[1], $this->company_id))) {
            $currency_code = $currency->code;
        }

        // Fetch the amounts
        $amounts = [
            'total_credit' => [
                'lang' => Language::_('AdminClients.getcurrencyamounts.text_total_credits', true),
                'amount' => $this->CurrencyFormat->format(
                    $this->Transactions->getTotalCredit($client->id, $currency_code),
                    $currency_code
                )
            ],
            'total_due' => [
                'lang' => Language::_('AdminClients.getcurrencyamounts.text_total_due', true),
                'amount' => $this->CurrencyFormat->format(
                    $this->Invoices->amountDue($client->id, $currency_code),
                    $currency_code
                )
            ]
        ];

        // Build the vars
        $vars = [
            'selected_currency' => $currency_code,
            'currencies' => array_unique(
                array_merge($this->Clients->usedCurrencies($client->id), [$client->settings['default_currency']])
            ),
            'amounts' => $amounts
        ];

        // Set the partial for currency amounts
        $response = $this->partial('admin_clients_getcurrencyamounts', $vars);

        // JSON encode the AJAX response
        $this->outputAsJson($response);
        return false;
    }

    /**
     * Create invoice
     */
    public function createInvoice()
    {
        $this->uses(['Currencies', 'Invoices']);
        $this->components(['SettingsCollection']);
        $this->ArrayHelper = $this->DataStructure->create('Array');

        // Ensure we have a client ID to load
        if (!isset($this->get[0]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            $this->redirect($this->base_uri . 'clients/');
        }

        $vars = [];

        // Create an invoice
        if (!empty($this->post)) {
            $vars = $this->post;
            $vars['client_id'] = $client->id;

            // Determine the status as active or draft based on the submit field
            $vars['status'] = array_key_exists('save', $vars) ? 'active' : 'draft';

            // Format the line items
            $vars['lines'] = $this->ArrayHelper->keyToNumeric($vars['lines']);

            if (!isset($vars['autodebit'])) {
                $vars['autodebit'] = '0';
            }

            // Edit if saving with an existing invoice ID
            if (isset($vars['invoice_id'])
                && $vars['invoice_id'] > 0
                && ($invoice = $this->Invoices->get($vars['invoice_id']))
                && $invoice->client_id == $client->id
            ) {
                $invoice_id = $vars['invoice_id'];
                $this->Invoices->edit($invoice_id, $vars);
            } else {
                // Attempt to save the invoice
                // Remove empty line items when saving drafts
                if (isset($vars['status']) && isset($vars['lines'])) {
                    $vars['lines'] = $this->removeEmptyLineItems($vars['status'], $vars['lines']);
                }

                $invoice_id = $this->Invoices->add($vars);
            }

            if (($errors = $this->Invoices->errors())) {
                // Error, reset vars
                $vars = (object)$this->post;
                $vars->line_items = $this->ArrayHelper->numericToKey($this->post['lines']);

                // Set line items to array of objects
                for ($i = 0, $num_lines = count($vars->line_items); $i < $num_lines; $i++) {
                    $vars->line_items[$i] = (object)$vars->line_items[$i];

                    // Convert the amount back to decimal
                    if ((isset($vars->line_items[$i]->amount) ? $vars->line_items[$i]->amount : null)) {
                        $vars->line_items[$i]->amount = $this->Invoices->currencyToDecimal(
                            $vars->line_items[$i]->amount,
                            ($vars->currency ?? null),
                            4
                        );
                    }
                }

                $this->setMessage('error', $errors);
            } else {
                // Success
                if (!$this->isAjax()) {
                    $invoice = $this->Invoices->get($invoice_id);

                    if ($vars['status'] == 'draft') {
                        $this->flashMessage(
                            'message',
                            Language::_('AdminClients.!success.draftinvoice_added', true, $invoice->id_code)
                        );
                        $this->redirect(
                            $this->base_uri . 'clients/editinvoice/' . $client->id . '/' . $invoice_id . '/'
                        );
                    } else {
                        $this->flashMessage(
                            'message',
                            Language::_('AdminClients.!success.invoice_added', true, $invoice->id_code)
                        );
                        $this->redirect($this->base_uri . 'clients/view/' . $client->id . '/');
                    }
                }
            }
        }

        // If this was an ajax request, send the response back
        if ($this->isAjax()) {
            $result = ['success' => (!isset($errors) || !$errors), 'invoice_id' => $invoice_id];

            // If successful, return the entire invoice
            if ($result['success']) {
                $result['invoice'] = $this->Invoices->get($invoice_id);
            }
            $this->outputAsJson($result);
            return false;
        }

        // Set initial default field values
        if (empty($vars)) {
            $vars = new stdClass();
            $vars->delivery = $client->settings['inv_method'];
            // Set the renew date by default
            $vars->date_due = $this->Date->modify(
                date('c'),
                '+' . $client->settings['inv_days_before_renewal'] . ' days',
                'Y-m-d',
                Configure::get('Blesta.company_timezone')
            );

            // Set default currency
            $vars->currency = $client->settings['default_currency'];
        }

        // Set the pricing periods
        $pricing_periods = $this->Invoices->getPricingPeriods();

        $this->set('client', $client);
        $this->set('delivery_methods', $this->Invoices->getDeliveryMethods($client->id));
        $this->set('periods', $pricing_periods);
        $this->set('vars', $vars);
        // Set currencies
        $this->set(
            'currencies',
            $this->Form->collapseObjectArray($this->Currencies->getAll($this->company_id), 'code', 'code')
        );

        $this->view($this->view->fetch('admin_clients_createinvoice'));

        if ($this->isAjax()) {
            return false;
        }
    }

    /**
     * Edit invoice
     */
    public function editInvoice()
    {
        $this->uses(['Currencies', 'Invoices']);
        $this->components(['SettingsCollection']);
        $this->ArrayHelper = $this->DataStructure->create('Array');

        // Ensure we have a client to load
        if (!isset($this->get[0]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            $this->redirect($this->base_uri . 'clients/');
        }

        if (!Configure::get('Caching.on')) {
            $client->settings['inv_cache'] = 'none';
        }

        // Ensure we have a invoice to load, and that it belongs to this client
        if (!isset($this->get[1])
            || !($invoice = $this->Invoices->get((int)$this->get[1]))
            || ($invoice->client_id != $client->id)
        ) {
            $this->redirect($this->base_uri . 'clients/view/' . $client->id . '/');
        }

        $vars = [];

        // Edit an invoice
        if (!empty($this->post)) {
            $vars = $this->post;
            $vars['client_id'] = $client->id;

            // Determine the status as active/proforma/draft/void based on the submit field
            if (array_key_exists('save', $vars)) {
                $vars['status'] = $invoice->status == 'proforma' ? 'proforma' : 'active';
            } elseif (array_key_exists('submit_void', $vars)) {
                $vars['status'] = 'void';
            } else {
                $vars['status'] = 'draft';
            }

            if (!isset($vars['autodebit'])) {
                $vars['autodebit'] = '0';
            }

            if (!isset($vars['recache'])) {
                $vars['recache'] = '0';
            }

            // Structure line items
            $vars['lines'] = $this->ArrayHelper->keyToNumeric($vars['lines']);

            // Remove empty line items when saving drafts
            if (isset($vars['status']) && isset($vars['lines'])) {
                $vars['lines'] = $this->removeEmptyLineItems($vars['status'], $vars['lines']);
            }

            // Edit the invoice
            $this->Invoices->edit($invoice->id, $vars);

            if (($errors = $this->Invoices->errors())) {
                // Error, reset vars
                $vars = clone $invoice;

                foreach ($this->post as $key => $value) {
                    $vars->$key = $value;
                }

                $vars->line_items = $this->ArrayHelper->numericToKey($this->post['lines']);

                // Set line items to array of objects
                for ($i = 0, $num_lines = count($vars->line_items); $i < $num_lines; $i++) {
                    $vars->line_items[$i] = (object)$vars->line_items[$i];

                    // Convert the amount back to decimal
                    if ((isset($vars->line_items[$i]->amount) ? $vars->line_items[$i]->amount : null)) {
                        $vars->line_items[$i]->amount = $this->Invoices->currencyToDecimal(
                            $vars->line_items[$i]->amount,
                            ($vars->currency ?? null),
                            4
                        );
                    }
                }

                $this->setMessage('error', $errors);
            } else {
                // Success
                if (!$this->isAjax()) {
                    // Set the success message to either invoice edited, draft edited, or draft created as invoice
                    // Assume invoice edited
                    $success_message = Language::_('AdminClients.!success.invoice_updated', true, $invoice->id_code);

                    // Check whether a draft was edited, or created as an invoice
                    switch ($vars['status']) {
                        case 'draft':
                            // Draft saved as draft
                            $success_message = Language::_(
                                'AdminClients.!success.draftinvoice_updated',
                                true,
                                $invoice->id_code
                            );
                            break;
                        case 'active':
                            if ($invoice->status == 'draft') {
                                // Draft saved as new invoice
                                $updated_invoice = $this->Invoices->get($invoice->id);
                                $success_message = Language::_(
                                    'AdminClients.!success.draftinvoice_created',
                                    true,
                                    $invoice->id_code,
                                    $updated_invoice->id_code
                                );
                            }
                            break;
                    }

                    $this->flashMessage('message', $success_message);
                    $this->redirect($this->base_uri . 'clients/view/' . $client->id . '/');
                }
            }
        }

        // If this was an ajax request, send the response back
        if ($this->isAjax()) {
            $result = ['success' => (!isset($errors) || !$errors)];

            // If successful, return the entire invoice
            if ($result['success']) {
                $result['invoice'] = $this->Invoices->get($invoice->id);
            }
            $this->outputAsJson($result);
            return false;
        }

        // Set initial invoice
        if (empty($vars)) {
            // Set recurring invoice meta data
            if ($invoice->meta) {
                foreach ($invoice->meta as $i => $meta) {
                    if ($meta->key == 'recur') {
                        $meta->value = unserialize(base64_decode($meta->value));

                        foreach ($meta->value as $key => $value) {
                            $invoice->$key = $value;
                        }

                        break;
                    }
                }
            }
            $vars = clone $invoice;

            // Extract all tax rules applied to this invoice
            $tax_rules = [];
            foreach ($vars->line_items as &$item) {
                // Set this item as not taxable
                $item->tax = 'false';

                if (!empty($item->taxes)) {
                    // Set this item as taxable
                    $item->tax = 'true';

                    foreach ($item->taxes as $tax) {
                        $tax_rules[$tax->level] = $tax->id;
                    }
                }
            }
            // If there are tax rules applied check if they'll be replaced if this invoice is updated
            if (!empty($tax_rules)) {
                $cur_tax_rules = $this->Invoices->getTaxRules($client->id);

                foreach ($cur_tax_rules as $tax) {
                    if (($client->settings['tax_exempt'] ?? 'false') == 'true'
                        && $tax->type != 'inclusive_calculated'
                    ) {
                        continue;
                    }

                    if (!isset($tax_rules[$tax->level]) || $tax_rules[$tax->level] != $tax->id) {
                        $this->setMessage(
                            'notice',
                            Language::_('AdminClients.!notice.invoice_tax_rules_differ', true)
                        );
                        break;
                    }
                }
            }

            // Set delivery methods
            $delivery_methods = $this->Invoices->getDelivery($invoice->id, false);
            $delivery_methods = $this->ArrayHelper->keyToNumeric($delivery_methods, false);
            $vars->delivery = (!empty($delivery_methods['method']) ? $delivery_methods['method'] : '');
        }

        // Format dates
        $vars->date_billed = $this->Date->cast($vars->date_billed, 'Y-m-d');
        $vars->date_due = $this->Date->cast($vars->date_due, 'Y-m-d');

        $pricing_periods = $this->Invoices->getPricingPeriods();

        $this->set('client', $client);
        $this->set('delivery_methods', $this->Invoices->getDeliveryMethods($client->id));
        $this->set('periods', $pricing_periods);
        $this->set('vars', $vars);
        $this->set('invoice', $invoice);
        $this->set(
            'currencies',
            $this->Form->collapseObjectArray($this->Currencies->getAll($this->company_id), 'code', 'code')
        );
        $this->structure->set(
            'page_title',
            Language::_('AdminClients.editinvoice.page_title', true, $client->id_code, $invoice->id_code)
        );

        $this->view($this->view->fetch('admin_clients_editinvoice'));
    }

    /**
     * Create quotation
     */
    public function createQuotation()
    {
        $this->uses(['Currencies', 'Quotations', 'Invoices']);
        $this->components(['SettingsCollection', 'Session']);
        $this->ArrayHelper = $this->DataStructure->create('Array');

        // Ensure we have a client ID to load
        if (!isset($this->get[0]) || !($client = $this->Clients->get((int) $this->get[0]))) {
            $this->redirect($this->base_uri . 'clients/');
        }

        $vars = [];

        // Create a quotation
        if (!empty($this->post)) {
            $vars = $this->post;
            $vars['client_id'] = $client->id;

            // Get staff ID
            $staff = $this->Staff->getByUserId($this->Session->read('blesta_id'));
            $vars['staff_id'] = $staff->id;

            // Determine the status based on the submit field
            if (array_key_exists('save', $vars)) {
                $vars['status'] = 'pending';
            } else {
                $vars['status'] = 'draft';
            }

            // Format the line items
            $vars['lines'] = $this->ArrayHelper->keyToNumeric($vars['lines']);

            // Edit if saving with an existing quotation ID
            if (isset($vars['quotation_id'])
                && $vars['quotation_id'] > 0
                && ($quotation = $this->Quotations->get($vars['quotation_id']))
                && $quotation->client_id == $client->id
            ) {
                $quotation_id = $vars['quotation_id'];
                $this->Quotations->edit($quotation_id, $vars);
            } else {
                // Remove empty line items when saving drafts
                if (isset($vars['status']) && isset($vars['lines'])) {
                    $vars['lines'] = $this->removeEmptyLineItems($vars['status'], $vars['lines']);
                }

                $quotation_id = $this->Quotations->add($vars);
            }

            if (($errors = $this->Quotations->errors())) {
                // Error, reset vars
                $vars = (object)$this->post;
                $vars->line_items = $this->ArrayHelper->numericToKey($this->post['lines']);

                // Set line items to array of objects
                for ($i = 0, $num_lines = count($vars->line_items); $i < $num_lines; $i++) {
                    $vars->line_items[$i] = (object)$vars->line_items[$i];

                    // Convert the amount back to decimal
                    if (($vars->line_items[$i]->amount ?? null)) {
                        $vars->line_items[$i]->amount = $this->Quotations->currencyToDecimal(
                            $vars->line_items[$i]->amount,
                            ($vars->currency ?? null),
                            4
                        );
                    }
                }

                $this->setMessage('error', $errors);
            } else {
                // Success
                if (!$this->isAjax()) {
                    $quotation = $this->Quotations->get($quotation_id);

                    if ($vars['status'] == 'draft') {
                        $this->flashMessage(
                            'message',
                            Language::_('AdminClients.!success.draftquotation_added', true, $quotation->id_code)
                        );
                        $this->redirect(
                            $this->base_uri . 'clients/editquotation/' . $client->id . '/' . $quotation_id . '/'
                        );
                    } else {
                        $this->flashMessage(
                            'message',
                            Language::_('AdminClients.!success.quotation_added', true, $quotation->id_code)
                        );
                        $this->redirect($this->base_uri . 'clients/view/' . $client->id . '/');
                    }
                }
            }
        }

        // If this was an ajax request, send the response back
        if ($this->isAjax()) {
            $result = ['success' => (!isset($errors) || !$errors), 'quotation_id' => $quotation_id];

            // If successful, return the entire quotation
            if ($result['success']) {
                $result['quotation'] = $this->Quotations->get($quotation_id);
            }
            $this->outputAsJson($result);

            return false;
        }

        // Set initial default field values
        if (empty($vars)) {
            $vars = new stdClass();

            // Set the valid dates by default
            $vars->date_expires = $this->Date->modify(
                date('c'),
                '+' . $client->settings['quotation_valid_days'] . ' days',
                'Y-m-d',
                Configure::get('Blesta.company_timezone')
            );

            // Set default currency
            $vars->currency = $client->settings['default_currency'];
        }

        // Set the pricing periods
        $pricing_periods = $this->Invoices->getPricingPeriods();

        $this->set('client', $client);
        $this->set('periods', $pricing_periods);
        $this->set('vars', $vars);

        // Set currencies
        $this->set(
            'currencies',
            $this->Form->collapseObjectArray($this->Currencies->getAll($this->company_id), 'code', 'code')
        );

        $this->view($this->view->fetch('admin_clients_createquotation'));

        if ($this->isAjax()) {
            return false;
        }
    }

    /**
     * Edit quotation
     */
    public function editQuotation()
    {
        $this->uses(['Currencies', 'Quotations', 'Invoices']);
        $this->components(['SettingsCollection', 'Session']);
        $this->ArrayHelper = $this->DataStructure->create('Array');

        // Ensure we have a client to load
        if (!isset($this->get[0]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            $this->redirect($this->base_uri . 'clients/');
        }

        // Ensure we have a quotation to load, and that it belongs to this client
        if (!isset($this->get[1])
            || !($quotation = $this->Quotations->get((int) $this->get[1]))
            || ($quotation->client_id != $client->id)
        ) {
            $this->redirect($this->base_uri . 'clients/view/' . $client->id . '/');
        }

        $vars = [];

        // Edit an quotation
        if (!empty($this->post)) {
            $vars = $this->post;
            $vars['client_id'] = $client->id;

            // Get staff ID
            $staff = $this->Staff->getByUserId($this->Session->read('blesta_id'));
            $vars['staff_id'] = $staff->id;

            // Determine the status as pending/draft/void based on the submit field
            if (array_key_exists('save', $vars)) {
                $vars['status'] = 'pending';
            } elseif (array_key_exists('submit_void', $vars)) {
                $vars['status'] = 'void';
            } else {
                $vars['status'] = 'draft';
            }

            // Structure line items
            $vars['lines'] = $this->ArrayHelper->keyToNumeric($vars['lines']);

            // Remove empty line items when saving drafts
            if (isset($vars['status']) && isset($vars['lines'])) {
                $vars['lines'] = $this->removeEmptyLineItems($vars['status'], $vars['lines']);
            }

            // Edit the quotation
            $this->Quotations->edit($quotation->id, $vars);

            if (($errors = $this->Quotations->errors())) {
                // Error, reset vars
                $vars = clone $quotation;

                foreach ($this->post as $key => $value) {
                    $vars->$key = $value;
                }

                $vars->line_items = $this->ArrayHelper->numericToKey($this->post['lines']);

                // Set line items to array of objects
                for ($i = 0, $num_lines = count($vars->line_items); $i < $num_lines; $i++) {
                    $vars->line_items[$i] = (object)$vars->line_items[$i];

                    // Convert the amount back to decimal
                    if (($vars->line_items[$i]->amount ?? null)) {
                        $vars->line_items[$i]->amount = $this->Quotations->currencyToDecimal(
                            $vars->line_items[$i]->amount,
                            ($vars->currency ?? null),
                            4
                        );
                    }
                }

                $this->setMessage('error', $errors);
            } else {
                // Success
                if (!$this->isAjax()) {
                    // Set the success message to either quotation edited, draft edited, or draft created as quotation
                    // Assume quotation edited
                    $success_message = Language::_('AdminClients.!success.quotation_updated', true, $quotation->id_code);

                    // Check whether a draft was edited, or created as a quotation
                    switch ($vars['status']) {
                        case 'draft':
                            // Draft saved as draft
                            $success_message = Language::_(
                                'AdminClients.!success.draftquotation_updated',
                                true,
                                $quotation->id_code
                            );
                            break;
                    }

                    $this->flashMessage('message', $success_message);
                    $this->redirect($this->base_uri . 'clients/view/' . $client->id . '/');
                }
            }
        }

        // If this was an ajax request, send the response back
        if ($this->isAjax()) {
            $result = ['success' => (!isset($errors) || !$errors)];

            // If successful, return the entire quotation
            if ($result['success']) {
                $result['quotation'] = $this->Quotations->get($quotation->id);
            }
            $this->outputAsJson($result);

            return false;
        }

        // Set initial quotation
        if (empty($vars)) {
            $vars = clone $quotation;

            // Extract all tax rules applied to this quotation
            $tax_rules = [];
            foreach ($vars->line_items as &$item) {
                // Set this item as not taxable
                $item->tax = 'false';

                if (!empty($item->taxes)) {
                    // Set this item as taxable
                    $item->tax = 'true';

                    foreach ($item->taxes as $tax) {
                        $tax_rules[$tax->level] = $tax->id;
                    }
                }
            }

            // If there are tax rules applied check if they'll be replaced if this quotation is updated
            if (!empty($tax_rules)) {
                $cur_tax_rules = $this->Invoices->getTaxRules($client->id);

                foreach ($cur_tax_rules as $tax) {
                    if (($client->settings['tax_exempt'] ?? 'false') == 'true'
                        && $tax->type != 'inclusive_calculated'
                    ) {
                        continue;
                    }

                    if (!isset($tax_rules[$tax->level]) || $tax_rules[$tax->level] != $tax->id) {
                        $this->setMessage(
                            'notice',
                            Language::_('AdminClients.!notice.quotation_tax_rules_differ', true)
                        );
                        break;
                    }
                }
            }
        }

        // Format dates
        $vars->date_created = $this->Date->cast($vars->date_created, 'Y-m-d');
        $vars->date_expires = $this->Date->cast($vars->date_expires, 'Y-m-d');

        // Set the pricing periods
        $pricing_periods = $this->Invoices->getPricingPeriods();

        $this->set('client', $client);
        $this->set('periods', $pricing_periods);
        $this->set('vars', $vars);
        $this->set('quotation', $quotation);
        $this->set(
            'currencies',
            $this->Form->collapseObjectArray($this->Currencies->getAll($this->company_id), 'code', 'code')
        );
        $this->structure->set(
            'page_title',
            Language::_('AdminClients.editquotation.page_title', true, $client->id_code, $quotation->id_code)
        );

        $this->view($this->view->fetch('admin_clients_editquotation'));
    }

    /**
     * Marks a quotation as approved
     */
    public function approveQuotation()
    {
        $this->uses(['Quotations']);

        // Ensure we have a client to load
        if (!isset($this->get[0]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            $this->redirect($this->base_uri . 'clients/');
        }

        // Ensure we have a quotation to load, and that it belongs to this client
        if (!isset($this->get[1])
            || !($quotation = $this->Quotations->get((int) $this->get[1]))
            || ($quotation->client_id != $client->id)
        ) {
            $this->redirect($this->base_uri . 'clients/view/' . $client->id . '/');
        }

        // Approve the quotation
        $this->Quotations->updateStatus($quotation->id, 'approved');

        if (($errors = $this->Quotations->errors())) {
            // Error, status was not valid
            $this->flashMessage('error', $errors);
        } else {
            // Success, quotation approved
            $this->flashMessage('message', Language::_('AdminClients.!success.approvequotation_approved', true, $quotation->id));
        }

        $this->redirect($this->base_uri . 'clients/view/' . $client->id . '/');
    }

    /**
     * Generates an invoice from a quotation
     */
    public function invoiceQuotation()
    {
        $this->uses(['Quotations']);
        $this->helpers(['Form']);
        $this->components(['SettingsCollection']);

        // Ensure we have a client to load
        if (!isset($this->get[0]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            $this->redirect($this->base_uri . 'clients/');
        }

        // Ensure we have a quotation to load, and that it belongs to this client
        if (!isset($this->get[1])
            || !($quotation = $this->Quotations->get((int) $this->get[1]))
            || ($quotation->client_id != $client->id)
        ) {
            $this->redirect($this->base_uri . 'clients/view/' . $client->id . '/');
        }

        // Invoice the quotation
        if (!empty($this->post)) {
            $this->Quotations->generateInvoice($quotation->id, $this->post);

            $invoices = $this->Quotations->getInvoices($quotation->id);
            $invoices_list = implode(',', $this->Form->collapseObjectArray($invoices, 'id_code', 'id_code'));

            if (($errors = $this->Quotations->errors())) {
                // Error, status was not valid
                $this->flashMessage('error', $errors);
            } else {
                // Success, quotation invoiced
                $this->flashMessage(
                    'message',
                    Language::_('AdminClients.!success.invoicequotation_invoiced', true, $quotation->id, $invoices_list)
                );
            }

            $this->redirect($this->base_uri . 'clients/view/' . $client->id . '/');
        } else {
            $inv_days_before_renewal = $this->Companies->getSetting(
                Configure::get('Blesta.company_id'),
                'inv_days_before_renewal'
            )->value;
            $vars = (object) [
                'percentage_due' => $this->Companies->getSetting(
                    Configure::get('Blesta.company_id'),
                    'quotation_deposit_percentage'
                )->value,
                'first_due_date' => $this->Date->modify(date('c'), '+1 day', 'Y-m-d'),
                'second_due_date' => $this->Date->modify(date('c'), '+' . ($inv_days_before_renewal ?? 5) . 'days', 'Y-m-d')
            ];
            $this->set('vars', $vars);

            echo $this->view->fetch('admin_clients_invoicequotation');
            return false;
        }
    }

    /**
     * Streams the given quotation to the browser
     */
    public function viewQuotation()
    {
        $this->uses(['Quotations']);

        // Ensure we have a quotation to load, and that it belongs to this client
        if (!isset($this->get[0])
            || !($client = $this->Clients->get((int) $this->get[0]))
            || !isset($this->get[1])
            || !($quotation = $this->Quotations->get((int) $this->get[1]))
            || ($quotation->client_id != $client->id)
        ) {
            $this->redirect($this->base_uri . 'clients/');
        }

        // Download the quotation in the admin's language
        $this->components(['QuotationDelivery']);
        $this->QuotationDelivery->downloadQuotations([$quotation->id], ['language' => Configure::get('Blesta.language')]);
        exit;
    }

    /**
     * Removes empty line items from an invoice so that it can be auto-saved without error, when possible
     * @see AdminClients::createInvoice(), AdminClients::editInvoice()
     *
     * @param string $status The status of the invoice. Only 'draft' line items are changed
     * @param array $lines A list of invoice line items
     * @return array A numerically-indexed array of line items given, minus those that have no description
     */
    private function removeEmptyLineItems($status, array $lines = [])
    {
        // Remove blank line items so that we can continue to save a draft
        if ($status == 'draft' && !empty($lines)) {
            foreach ($lines as $index => $line) {
                if (isset($line['description']) && empty($line['description'])) {
                    unset($lines[$index]);
                }
            }
            $lines = array_values($lines);
        }

        return $lines;
    }

    /**
     * Edit a recurring invoice
     */
    public function editRecurInvoice()
    {
        $this->uses(['Currencies', 'Invoices']);
        $this->components(['SettingsCollection']);
        $this->ArrayHelper = $this->DataStructure->create('Array');

        // Ensure we have a client to load
        if (!isset($this->get[0]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            $this->redirect($this->base_uri . 'clients/');
        }

        // Ensure we have a invoice to load, and that it belongs to this client
        if (!isset($this->get[1])
            || !($invoice = $this->Invoices->getRecurring((int)$this->get[1]))
            || ($invoice->client_id != $client->id)
        ) {
            $this->redirect($this->base_uri . 'clients/view/' . $client->id . '/');
        }

        $vars = [];

        // Edit an invoice
        if (!empty($this->post)) {
            $vars = $this->post;
            $vars['client_id'] = $client->id;
            $vars['duration'] = ($vars['duration'] == 'indefinitely' ? null : $vars['duration_time']);

            // Structure line items
            $vars['lines'] = $this->ArrayHelper->keyToNumeric($vars['lines']);

            if (!isset($vars['autodebit'])) {
                $vars['autodebit'] = '0';
            }

            // Edit the invoice
            $this->Invoices->editRecurring($invoice->id, $vars);

            if (($errors = $this->Invoices->errors())) {
                // Error, reset vars
                $vars = $invoice;

                foreach ($this->post as $key => $value) {
                    $vars->$key = $value;
                }
                $vars->line_items = $this->ArrayHelper->numericToKey($this->post['lines']);

                // Set line items to array of objects
                for ($i = 0, $num_lines = count($vars->line_items); $i < $num_lines; $i++) {
                    $vars->line_items[$i] = (object)$vars->line_items[$i];

                    // Convert the amount back to decimal
                    if ((isset($vars->line_items[$i]->amount) ? $vars->line_items[$i]->amount : null)) {
                        $vars->line_items[$i]->amount = $this->Invoices->currencyToDecimal(
                            $vars->line_items[$i]->amount,
                            ($vars->currency ?? null),
                            4
                        );
                    }
                }

                $this->setMessage('error', $errors);
            } else {
                // Success
                $this->flashMessage(
                    'message',
                    Language::_('AdminClients.!success.recurinvoice_updated', true, $invoice->id)
                );
                $this->redirect($this->base_uri . 'clients/view/' . $client->id . '/');
            }
        }

        // Set initial invoice
        if (empty($vars)) {
            $invoice->duration_time = $invoice->duration;
            $invoice->duration = ($invoice->duration > 0 ? 'times' : 'indefinitely');
            $vars = $invoice;

            // Set delivery methods
            $delivery_methods = $this->Invoices->getRecurringDelivery($invoice->id);
            $delivery_methods = $this->ArrayHelper->keyToNumeric($delivery_methods, false);
            $vars->delivery = (!empty($delivery_methods['method']) ? $delivery_methods['method'] : '');
        }

        // Format dates
        $vars->date_renews = $this->Date->cast($vars->date_renews, 'Y-m-d');

        $pricing_periods = $this->Invoices->getPricingPeriods();

        $this->set('client', $client);
        $this->set('delivery_methods', $this->Invoices->getDeliveryMethods($client->id));
        $this->set('periods', $pricing_periods);
        $this->set('vars', $vars);
        $this->set(
            'currencies',
            $this->Form->collapseObjectArray($this->Currencies->getAll($this->company_id), 'code', 'code')
        );
        $this->structure->set(
            'page_title',
            Language::_('AdminClients.editrecurinvoice.page_title', true, $client->id_code, $invoice->id)
        );

        if ($this->isAjax()) {
            return false;
        }

        $this->view($this->view->fetch('admin_clients_editrecurinvoice'));
    }

    /**
     * Sums line items and returns the sub total, total, and tax amount based on currency and company settings
     * for the given set of data and tax rules that apply to each. Outputs a JSON encoded array including:
     *
     *  - subtotal
     *      - amount The decimal format for the subtotal
     *      - amount_formatted The currency format for the subtotal
     *  - total
     *      - amount The decimal format for the total
     *      - amount_formatted The currency format for the total
     *  - total_w_tax
     *      - amount The decimal format for the total with tax
     *      - amount_formatted The currency format for the total with tax
     *  - total_paid
     *      - amount The decimal format for the total paid
     *      - amount_formatted The currency format for the total paid
     *  - total_due
     *      - amount The decimal format for the total due
     *      - amount_formatted The currency format for the total due
     *  - tax A list of tax rules, each including:
     *      - amount The decimal format for the total tax amount
     *      - amount_formatted The currency format for the total tax amount
     */
    public function calcLineTotals()
    {
        // This is an AJAX only method
        if (!$this->isAjax()) {
            return false;
        }

        $client_id = $this->get[0] ?? null;
        $type = $this->get['type'] ?? 'invoice';

        // Require a client ID
        if (!$client_id) {
            $this->redirect($this->base_uri . 'clients/');
        }

        $this->uses(['Quotations', 'Invoices']);
        $this->ArrayHelper = $this->DataStructure->create('Array');

        // Reformat lines for use by the pricing presenter
        $this->post['lines'] = $this->ArrayHelper->keyToNumeric($this->post['lines']);

        // Set the currency to format the totals into
        $currency = '';
        if (isset($this->post['currency']) && is_scalar($this->post['currency'])) {
            $currency = $this->post['currency'];
        } else {
            $this->components(['SettingsCollection']);
            $default_currency = $this->SettingsCollection->fetchClientSetting(
                $client_id,
                $this->Clients,
                'default_currency'
            );
            $currency = (!empty($default_currency) && !empty($default_currency['value'])
                ? $default_currency['value']
                : $currency
            );
        }

        // Ensure each line item has their amount/tax/quantity formatted
        foreach ($this->post['lines'] as &$line) {
            $line['qty'] = $this->Invoices->primeQuantity($line['qty'] ?? null);
            $line['tax'] = $this->Invoices->strToBool($line['tax'] ?? null);
            $line['amount'] = $this->Invoices->currencyToDecimal(($line['amount'] ?? null), $currency, 4);
        }

        // Determine the totals from the presenter
        if ($type == 'invoice') {
            $presenter = $this->Invoices->getDataPresenter($client_id, $this->post);
        } else {
            $presenter = $this->Quotations->getDataPresenter($client_id, $this->post);
        }
        $totals = $presenter->totals();

        // Update the taxes to include a formatted tax amount
        $taxes = $presenter->taxes();
        foreach ($taxes as &$tax) {
            // Change the tax amount to be the total tax amount
            $tax->amount = $tax->total;
            $tax->amount_formatted = $this->CurrencyFormat->format($tax->total, $currency);
        }

        // Calculate the amount paid/due if given
        $amount_paid = (isset($this->post['amount_paid']) && is_numeric($this->post['amount_paid'])
            ? $this->post['amount_paid']
            : 0
        );
        $total_due = max(0, ($totals->total - $amount_paid));


        // Set line totals
        $line_totals = [
            'subtotal' => [
                'amount' => $totals->subtotal,
                'amount_formatted' => $this->CurrencyFormat->format($totals->subtotal, $currency)
            ],
            'total' => [
                'amount' => $totals->total,
                'amount_formatted' => $this->CurrencyFormat->format($totals->total, $currency)
            ],
            'total_w_tax' => [
                'amount' => $totals->total_after_tax,
                'amount_formatted' => $this->CurrencyFormat->format($totals->total_after_tax, $currency)
            ],
            'total_paid' => [
                'amount' => $amount_paid,
                'amount_formatted' => $this->CurrencyFormat->format($amount_paid, $currency)
            ],
            'total_due' => [
                'amount' => $total_due,
                'amount_formatted' => $this->CurrencyFormat->format($total_due, $currency)
            ],
            'tax' => $taxes
        ];

        $this->outputAsJson($line_totals);

        return false;
    }

    /**
     * Deletes a draft invoice
     */
    public function deleteDraftInvoice()
    {
        $this->uses(['Invoices']);
        // Ensure we have a client to load
        if (!isset($this->get[0]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            $this->redirect($this->base_uri . 'clients/');
        }

        // Ensure we have a invoice to load, and that it belongs to this client
        if (!isset($this->get[1])
            || !($invoice = $this->Invoices->get((int)$this->get[1]))
            || ($invoice->client_id != $client->id)
        ) {
            $this->redirect($this->base_uri . 'clients/view/' . $client->id . '/');
        }

        // Delete the invoice
        $this->Invoices->deleteDraft($invoice->id);

        if (($errors = $this->Invoices->errors())) {
            // Error, invoice was not a draft invoice
            $this->flashMessage('error', $errors);
        } else {
            // Success, draft deleted
            $this->flashMessage('message', Language::_('AdminClients.!success.deletedraftinvoice_deleted', true));
        }

        $this->redirect($this->base_uri . 'clients/view/' . $client->id . '/');
    }

    /**
     * Delete recurring invoice
     */
    public function deleteRecurInvoice()
    {
        $this->uses(['Invoices']);

        // Ensure the invoice exists
        if (!isset($this->get[0]) || !($invoice = $this->Invoices->getRecurring($this->get[0]))
            || !($client = $this->Clients->get($invoice->client_id))) {
            $this->redirect($this->base_uri . 'clients/view/');
        }

        $this->Invoices->deleteRecurring($this->get[0]);

        $this->flashMessage('message', Language::_('AdminClients.!success.recurinvoice_deleted', true));
        $this->redirect($this->base_uri . 'clients/view/' . $invoice->client_id);
    }

    /**
     * Streams the given invoice to the browser
     */
    public function viewInvoice()
    {
        $this->uses(['Invoices']);

        // Ensure we have an invoice to load, and that it belongs to this client
        if (!isset($this->get[0])
            || !($client = $this->Clients->get((int)$this->get[0]))
            || !isset($this->get[1])
            || !($invoice = $this->Invoices->get((int)$this->get[1]))
            || ($invoice->client_id != $client->id)
        ) {
            $this->redirect($this->base_uri . 'clients/');
        }

        // Download the invoice in the admin's language
        $this->components(['InvoiceDelivery']);
        $this->InvoiceDelivery->downloadInvoices([$invoice->id], ['language' => Configure::get('Blesta.language')]);
        exit;
    }

    /**
     * Edit a Transaction
     */
    public function editTransaction()
    {
        $this->uses(['Transactions']);

        if (!isset($this->get[0]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            $this->redirect($this->base_uri . 'clients/');
        }

        // Ensure we have a transaction to load, and that it belongs to this client
        if (!isset($this->get[1])
            || !($transaction = $this->Transactions->get((int)$this->get[1]))
            || ($transaction->client_id != $client->id)
        ) {
            $this->redirect($this->base_uri . 'clients/view/' . $client->id . '/');
        }

        $vars = new stdClass();

        if (!empty($this->post)) {
            // If set to, attempt to process the void/return through the gateway
            if ($transaction->gateway_id
                && isset($this->post['status'])
                && ($this->post['status'] == 'void' || $this->post['status'] == 'refunded')
                && isset($this->post['process_remotely'])
            ) {
                $this->uses(['Payments']);

                switch ($this->post['status']) {
                    case 'void':
                        $this->Payments->voidPayment(
                            $client->id,
                            $transaction->id,
                            ['staff_id' => $this->Session->read('blesta_staff_id')]
                        );
                        break;
                    case 'refunded':
                        $this->Payments->refundPayment(
                            $client->id,
                            $transaction->id,
                            null,
                            ['staff_id' => $this->Session->read('blesta_staff_id')]
                        );
                        break;
                }

                if (($errors = $this->Payments->errors())) {
                    $this->setMessage('error', $errors);
                } else {
                    // Success
                    $this->flashMessage('message', Language::_('AdminClients.!success.edittransaction_updated', true));
                    $this->redirect($this->base_uri . 'clients/view/' . $client->id . '/');
                }
            } else {
                $this->Transactions->edit($transaction->id, $this->post, $this->Session->read('blesta_staff_id'));

                if (($errors = $this->Transactions->errors())) {
                    $this->setMessage('error', $errors);
                } else {
                    // Success
                    $this->flashMessage('message', Language::_('AdminClients.!success.edittransaction_updated', true));
                    $this->redirect($this->base_uri . 'clients/view/' . $client->id . '/');
                }
            }

            $vars = (object)$this->post;
        } else {
            $vars = $transaction;
        }

        $applied = $this->Transactions->getApplied($this->get[1]);

        if ($applied) {
            $this->setMessage('notice', Language::_('AdminClients.!notice.transactions_already_applied', true));
        }

        $this->set('transaction', $transaction);
        $this->set('applied', $applied);
        $this->set('vars', $vars);
        // Holds the name of all of the transaction types
        $this->set('transaction_types', $this->Transactions->transactionTypeNames());
        // Holds the name of all of the transaction status values
        $this->set('transaction_status', $this->Transactions->transactionStatusNames());
        $this->view($this->view->fetch('admin_clients_edittransaction'));
    }

    /**
     * Unapplies a transaction from the given invoice
     */
    public function unapplyTransaction()
    {
        $this->uses(['Transactions']);

        if (!isset($this->get[0])
            || !isset($this->get[1])
            || !($transaction = $this->Transactions->get($this->get[0]))
            || !($client = $this->Clients->get($transaction->client_id))
        ) {
            $this->redirect($this->base_uri . 'clients/');
        }

        $this->Transactions->unApply($transaction->id, [$this->get[1]]);

        if (($errors = $this->Transactions->errors())) {
            $this->flashMessage('error', $errors);
        } else {
            $this->flashMessage('message', Language::_('AdminClients.!success.transaction_unapplied', true));
        }

        $this->redirect($this->base_uri . 'clients/edittransaction/' . $client->id . '/' . $transaction->id . '/');
    }

    /**
     * Sets Restricted Packages
     */
    public function packages()
    {
        $this->uses(['Packages']);

        // Ensure we have a client to load
        if (!isset($this->get[0]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            $this->redirect($this->base_uri . 'clients/');
        }

        // Save restricted package access
        if (!empty($this->post)) {
            // Set restricted package access
            $package_ids = isset($this->post['package_ids']) ? array_values($this->post['package_ids']) : [];
            $this->Clients->setRestrictedPackages($client->id, $package_ids);

            if (($errors = $this->Clients->errors())) {
                // Error, reset vars
                $this->setMessage('error', $errors);
                $vars = (object)$this->post;
            } else {
                // Success
                $this->flashMessage('message', Language::_('AdminClients.!success.packages_restricted', true));
                $this->redirect($this->base_uri . 'clients/packages/' . $client->id . '/');
            }
        }

        // Set currently restricted package access
        if (empty($vars)) {
            $vars = new stdClass();
            $vars->package_ids = $this->Form->collapseObjectArray(
                $this->Clients->getRestrictedPackages($client->id),
                'package_id',
                'package_id'
            );
        }

        $this->set('vars', $vars);
        $this->set('packages', $this->Packages->getAll($this->company_id, ['name' => 'ASC'], 'restricted', null, ['hidden' => 1]));

        $this->view($this->view->fetch('admin_clients_packages'));
    }

    /**
     * Add a service
     */
    public function addService()
    {
        $this->uses(['Services', 'Packages', 'PackageGroups', 'ModuleManager']);
        $step = 'basic';

        // Ensure we have a client to load
        if (!isset($this->get[0]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            $this->redirect($this->base_uri . 'clients/');
        }

        // Convert group_package parameter and redirect
        if (isset($this->get['group_package'])) {
            [$this->get[1], $this->get[2]] = explode('_', $this->get['group_package']);

            $params = null;
            if (isset($this->get['parent_service_id'])) {
                $params = '/?parent_service_id=' . $this->get['parent_service_id'];
            }

            $this->redirect(
                $this->base_uri . 'clients/addservice/' . $client->id . '/'
                . $this->get[1] . '/' . $this->get[2] . $params
            );
        }

        // If package selected, request service info
        if (isset($this->get[1])
            && isset($this->get[2])
            && ($package = $this->Packages->get((int)$this->get[2]))
            && $package->company_id == $this->company_id
        ) {
            // Get the package group to use
            $package_group = $this->PackageGroups->get((int)$this->get[1]);

            $order_info = isset($this->post['order_info'])
                ? unserialize(base64_decode($this->post['order_info']))
                : null;
            unset($order_info['set_coupon']);
            $this->post = array_merge((array)$order_info, $this->post);
            unset($this->post['order_info']);

            if (isset($order_info['step'])) {
                $step = $order_info['step'];
            }

            // Submitting an edit button should take us back to the edit step
            if (array_key_exists('submit_edit', $this->post)) {
                $step = 'edit';
            }

            if (isset($this->post['step']) && $this->post['step'] == 'edit') {
                $step = $this->post['step'];
            }

            if (!empty($this->post)) {
                $step = $this->processServiceStep($step, $package, $package_group, $client);
            }

            $this->renderServiceStep($step, $package, $package_group, $client);
        } else {
            // List all packages available
            $this->listPackages($client->id);
        }

        $this->view($this->view->fetch('admin_clients_addservice'));
    }

    /**
     * Edit service
     */
    public function editService()
    {
        $this->uses(
            ['Currencies', 'Invoices', 'Services', 'ServiceChanges', 'Packages',
                'PackageOptions', 'PackageOptionConditionSets', 'ModuleManager', 'Coupons']
        );

        $this->ArrayHelper = $this->DataStructure->create('Array');

        // Ensure we have a client to load
        if (!isset($this->get[0]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            $this->redirect($this->base_uri . 'clients/');
        }

        // Ensure we have a service
        if (!isset($this->get[1])
            || !($service = $this->Services->get((int)$this->get[1]))
            || $service->client_id != $client->id
        ) {
            $this->redirect($this->base_uri . 'clients/view/' . $client->id . '/');
        }

        $package = $this->Packages->get($service->package->id);
        $module = $this->ModuleManager->initModule($service->package->module_id);
        if ($module) {
            $module->base_uri = $this->base_uri;
        }
        $statuses = $this->Services->getStatusTypes();

        $vars = (object)$this->ArrayHelper->numericToKey($service->fields, 'key', 'value');
        $vars->pricing_id = $service->pricing_id;
        $vars->qty = $service->qty;
        $vars->date_canceled = isset($service->date_canceled)
            ? $this->Date->cast($service->date_canceled, 'Y-m-d')
            : $this->Date->modify(date('c'), '+1 day', 'Y-m-d', Configure::get('Blesta.company_timezone'));

        if ($service->coupon_id && ($coupon = $this->Coupons->get($service->coupon_id))) {
            $vars->coupon_code = $coupon->code;
            $vars->coupon_code_update = $coupon->code;
            $service->coupon_code = $coupon->code;
        }

        // Determine whether a recurring coupon applies to this service
        $recurring_coupon = false;
        if ($service->coupon_id && $service->date_renews) {
            $recurring_coupon = $this->Coupons->getRecurring(
                $service->coupon_id,
                $service->package_pricing->currency,
                $service->date_renews . 'Z'
            );
        }

        // Set price override fields
        $vars->price_override = ($service->override_price !== null && $service->override_currency !== null
            ? 'true'
            : 'false'
        );
        $vars->override_price = $service->override_price;
        $vars->override_currency = $service->override_currency;
        if (empty($service->override_currency) && isset($service->package_pricing->currency)) {
            $vars->override_currency = $service->package_pricing->currency;
        }

        // Create a list of module row IDs to validate against if changed
        $module_row_ids = [];
        $module_rows = $this->ModuleManager->getRows($package->module_id);
        foreach ($module_rows as $row) {
            $module_row_ids[$row->id] = $row->id;

            // Set the current module row ID
            if ($service->module_row_id == $row->id) {
                $vars->module_row_id = $service->module_row_id;
            }
        }

        // Set whether to allow the renew date to be changed. i.e. not if the service is a one-time service
        $allow_renew_date = true;
        if (isset($service->package_pricing->period) && $service->package_pricing->period == 'onetime') {
            $allow_renew_date = false;
        }

        // If date canceled is set, set cancellation fields
        $vars->cancellation_reason = isset($service->cancellation_reason) ? $service->cancellation_reason : '';
        if ($service->date_canceled && $service->status != 'canceled') {
            $vars->action = 'schedule_cancel';
            $vars->cancel = ($service->date_canceled == $service->date_renews ? 'term' : 'date');

            //if ($vars->cancel == "date")
            //    $vars->date_canceled = $this->Date->cast($service->date_canceled, "Y-m-d");
        }

        // Retrieve all current pending service changes for this service
        $queued_changes = $this->getQueuedServiceChanges($service->id);
        // Determine whether to queue the service change or process it immediately
        $queue_service_changes = ($client->settings['process_paid_service_changes'] == 'true');

        // Detect module refresh fields
        $refresh_fields = isset($this->post['refresh_fields']) && $this->post['refresh_fields'] == 'true';

        // Add pending/in_review
        if ($service->status == 'pending' || $service->status == 'in_review') {
            if (!empty($this->post)) {
                // Set unchecked checkboxes
                if (!isset($this->post['use_module'])) {
                    $this->post['use_module'] = 'false';
                }

                // Force status of in_review to not be changed. It must be done from a plugin instead
                if ($service->status == 'in_review') {
                    $this->post['status'] = 'in_review';
                }

                // Determine whether this service is prorated to match the renew date of its parent service
                $prorate_to_parent = $service->parent_service_id
                    && ($parent_service = $this->Services->get($service->parent_service_id))
                    && $this->Services->canSyncToParent(
                        $service->package_pricing,
                        $parent_service->package_pricing,
                        $client->client_group_id
                    );

                // Reset the renew date if we are activating this service
                if (isset($this->post['status'])
                    && $this->post['status'] == 'active'
                    && $service->package_pricing->period != 'onetime'
                    && $package->prorata_day === null
                    && !$prorate_to_parent
                ) {
                    $this->post['date_renews'] = $this->Date->modify(
                        date('c'),
                        '+' . $service->package_pricing->term . ' ' . $service->package_pricing->period,
                        'c',
                        Configure::get('Blesta.company_timezone')
                    );
                }

                if (!$refresh_fields) {
                    // Always set config options so that they can be removed if no longer valid
                    $this->post['configoptions'] = (isset($this->post['configoptions'])
                        ? $this->post['configoptions']
                        : []
                    );

                    // Validate that the submitted config options are valid given the Option Logic
                    $option_logic = new OptionLogic();
                    $option_logic->setPackageOptionConditionSets(
                        $this->PackageOptionConditionSets->getAll(
                            [
                                'package_id' => $service->package_pricing->package_id,
                                'opition_ids' => $this->Form->collapseObjectArray(
                                    $this->PackageOptions->getAllByPackageId(
                                        $service->package_pricing->package_id,
                                        $service->package_pricing->term,
                                        $service->package_pricing->period,
                                        $service->package_pricing->currency
                                    ),
                                    'id',
                                    'id'
                                )
                            ],
                            ['option_id']
                        )
                    );

                    $config_options = (isset($this->post['configoptions']) ? $this->post['configoptions'] : []);
                    if (!($errors = $option_logic->validate($config_options))) {
                        // Update the pending service
                        $this->Services->edit(
                            $service->id,
                            $this->post,
                            false,
                            (isset($this->post['notify_order']) && $this->post['notify_order'] == 'true')
                        );

                        $errors = $this->Services->errors();
                    }

                    if (!empty($errors)) {
                        $this->setMessage('error', $errors);
                    } else {
                        $this->flashMessage(
                            'message',
                            Language::_(
                                'AdminClients.!success.service_'
                                . ($service->status == 'in_review' ? 'edited' : 'added'),
                                true
                            )
                        );
                        $this->redirect($this->base_uri . 'clients/view/' . $client->id);
                    }
                }

                $vars = (object)$this->post;
                $vars->notify_order = (isset($vars->notify_order) ? $vars->notify_order : 'false');
            }

            $service_fields = $module->getAdminAddFields($package, $vars);
        } else {
            // Suspend/Unsuspend/Cancel/Change Package&Module Options
            if (!$refresh_fields && !empty($this->post) && isset($this->post['section'])
                && $service->status != 'canceled') {
                // Set staff ID for logging (un)suspension
                $this->post['staff_id'] = $this->Session->read('blesta_staff_id');

                // Do not use the module if not set
                if (!isset($this->post['use_module'])) {
                    $this->post['use_module'] = 'false';
                }

                switch ($this->post['section']) {
                    case 'action':
                        switch ($this->post['action']) {
                            case 'suspend':
                                $this->Services->suspend($service->id, $this->post);
                                break;
                            case 'unsuspend':
                                $this->Services->unsuspend($service->id, $this->post);
                                break;
                            case 'cancel':
                                // Cancel right now
                                $this->post['date_canceled'] = date('c');
                                if (!isset($this->post['notify_cancel'])) {
                                    $this->post['notify_cancel'] = 'false';
                                }

                                $this->Services->cancel($service->id, $this->post);
                                break;
                            case 'schedule_cancel':
                                if (isset($this->post['cancel']) && $this->post['cancel'] == 'term') {
                                    $this->post['date_canceled'] = 'end_of_term';
                                }

                                // Remove scheduled cancellation
                                if (isset($this->post['cancel']) && $this->post['cancel'] == 'none') {
                                    $this->Services->unCancel($service->id);
                                } else {
                                    if (!isset($this->post['notify_cancel'])) {
                                        $this->post['notify_cancel'] = 'false';
                                    }

                                    // Process cancellation
                                    $this->Services->cancel($service->id, $this->post);
                                }
                                break;
                            case 'change_renew':
                                // Do not attempt to change the renew date of a one-time service
                                if ($allow_renew_date) {
                                    $data = array_merge(
                                        [
                                            'date_renews' => isset($this->post['date_renews'])
                                                ? $this->Services->dateToUtc($this->post['date_renews']) . 'Z'
                                                : $service->date_renews . 'Z',
                                            'pricing_id' => $service->pricing_id,
                                            'qty' => $service->qty,
                                            'prorate' => isset($this->post['prorate'])
                                                && $this->post['prorate'] == 'true',
                                            'use_module' => 'false'
                                        ],
                                        $this->PackageOptions->formatServiceOptions($service->options)
                                    );

                                    // Include the error message
                                    if (($errors = $this->Services->errors())) {
                                        $error = $errors;
                                    }

                                    // Determine the pricing currency
                                    if (empty($errors)) {
                                        $pricing = $service->package_pricing;
                                        if (isset($data['pricing_id'])
                                            && ($package = $this->Packages->getByPricingId($data['pricing_id']))
                                        ) {
                                            foreach ($package->pricing as $price) {
                                                if ($price->id == $data['pricing_id']) {
                                                    $pricing = $price;
                                                    break;
                                                }
                                            }
                                        }

                                        $serviceChange = $this->ServiceChanges->getPresenter($service->id, $data);
                                        $total = $serviceChange->totals()->total;

                                        // Determine whether credits are allowed
                                        $allow_credit = (isset($client->settings['client_prorate_credits'])
                                            && $client->settings['client_prorate_credits'] == 'true');
                                        $prorate = (isset($data['prorate']) && $data['prorate'] == 'true');

                                        // Don't allow proration on the service to create an invoice.
                                        // We'll handle this ourselves
                                        unset($data['prorate']);

                                        $this->Services->validateServiceEdit($service->id, $data, true);
                                        $errors = $this->Services->errors();
                                    }

                                    // Create the invoice for the service change
                                    if (empty($errors) && $prorate && $total > 0) {
                                        $invoice_data = $this->makeInvoice(
                                            $client,
                                            $serviceChange,
                                            $pricing->currency,
                                            true,
                                            $service->id
                                        );
                                        $invoice_id = $invoice_data['invoice_id'];
                                        $errors = $invoice_data['errors'];
                                    }

                                    if (empty($errors)) {
                                        $this->Services->edit($service->id, $data, true);
                                        $errors = $this->Services->errors();
                                    }

                                    // Issue a credit for the service change
                                    if (empty($errors) && $prorate && $total < 0 && $allow_credit) {
                                        $transaction_id = $this->createCredit(
                                            $client->id,
                                            abs($total),
                                            $pricing->currency
                                        );
                                    }

                                    // Include the error message
                                    if (!empty($errors)) {
                                        $error = $errors;
                                    }
                                }
                                break;
                            case 'update_coupon':
                                if (isset($this->post['coupon_code_update'])) {
                                    $data = [
                                        'coupon_id' => $this->getCouponId($this->post['coupon_code_update'])
                                    ];

                                    // Update the service immediately when not being queued, prorated,
                                    // or charging any amount
                                    $this->Services->edit($service->id, $data);

                                    // Include the error message
                                    if (($errors = $this->Services->errors())) {
                                        $error = $errors;
                                    }
                                }
                                break;
                        }
                        break;
                    default:
                    case 'information':
                        // Module row to change to must be a valid row ID
                        if (!isset($this->post['module_row_id'])
                            || !array_key_exists($this->post['module_row_id'], $module_row_ids)
                        ) {
                            break;
                        }

                        $data = ['module_row_id' => $this->post['module_row_id'], 'use_module' => 'false'];
                        $this->Services->edit($service->id, $data, true);

                        if (($errors = $this->Services->errors())) {
                            $this->setMessage('error', $errors);
                        } else {
                            $this->flashMessage('message', Language::_('AdminClients.!success.service_edited', true));
                            $this->redirect($this->base_uri . 'clients/view/' . $client->id);
                        }

                        break;
                    case 'package':
                        // Set any price overrides for this service
                        $data = $this->post;
                        if (isset($this->post['price_override']) && $this->post['price_override'] == 'true') {
                            $data['override_price'] = (!empty($this->post['override_price'])
                                ? $this->post['override_price']
                                : null
                            );
                            $data['override_currency'] = (
                                !empty($this->post['override_price']) && !empty($this->post['override_currency'])
                                    ? $this->post['override_currency']
                                    : null
                            );

                            // Cannot change package/term
                            unset($data['pricing_id']);
                        } else {
                            // Reset price overrides
                            $data['override_price'] = null;
                            $data['override_currency'] = null;
                        }

                        if (isset($this->post['coupon_code'])) {
                            $data['coupon_id'] = $this->getCouponId($this->post['coupon_code']);
                        }

                        // Always set config options so that they can be removed if no longer valid
                        $data['configoptions'] = (isset($data['configoptions']) ? $data['configoptions'] : []);

                        // Determine the pricing currency
                        $pricing = $service->package_pricing;
                        if (isset($data['pricing_id'])
                            && ($package = $this->Packages->getByPricingId($data['pricing_id']))
                        ) {
                            foreach ($package->pricing as $price) {
                                if ($price->id == $data['pricing_id']) {
                                    $pricing = $price;
                                    break;
                                }
                            }
                        }

                        // Cancel any pending service change
                        $this->cancelServiceChanges($service->id);

                        // Determine the items/totals
                        $data = array_merge($data, ['qty' => (!empty($data['qty']) ? $data['qty'] : 1)]);
                        $serviceChange = $this->ServiceChanges->getPresenter($service->id, $data);
                        $total = $serviceChange->totals()->total;

                        // Determine whether credits are allowed
                        $invoice_id = '';
                        $allow_credit = (isset($client->settings['client_prorate_credits'])
                            && $client->settings['client_prorate_credits'] == 'true');
                        $prorate = (isset($data['prorate']) && $data['prorate'] == 'true');

                        // Don't allow proration on the service to create an invoice.
                        // We'll handle this ourselves
                        unset($data['prorate']);

                        $this->Services->validateServiceEdit($service->id, $data);
                        $errors = $this->Services->errors();

                        // Validate that the submitted config options are valid given the Option Logic
                        $option_logic = new OptionLogic();
                        $option_logic->setPackageOptionConditionSets(
                            $this->PackageOptionConditionSets->getAll(
                                [
                                    'package_id' => $pricing->package_id,
                                    'opition_ids' => $this->Form->collapseObjectArray(
                                        $this->PackageOptions->getAllByPackageId(
                                            $pricing->package_id,
                                            $pricing->term,
                                            $pricing->period,
                                            $pricing->currency
                                        ),
                                        'id',
                                        'id'
                                    )
                                ],
                                ['option_id']
                            )
                        );

                        // Create the invoice for the service change
                        if (empty($errors)
                            && !($errors = $option_logic->validate($data['configoptions']))
                            && $prorate
                            && $total > 0
                        ) {
                            $invoice_data = $this->makeInvoice(
                                $client,
                                $serviceChange,
                                $pricing->currency,
                                true,
                                $service->id
                            );
                            $invoice_id = $invoice_data['invoice_id'];
                            $errors = $invoice_data['errors'];
                        }

                        if (empty($errors)) {
                            if ($queue_service_changes && $prorate && $total > 0) {
                                $result = $this->queueServiceChange($service->id, $invoice_id, $data);
                                $errors = $result['errors'];
                            } else {
                                // Update the service immediately when not being queued, prorated,
                                // or charging any amount
                                $this->Services->edit($service->id, $data);
                                $errors = $this->Services->errors();
                            }
                        }

                        // Issue a credit for the service change
                        if (empty($errors) && $prorate && $total < 0 && $allow_credit) {
                            $transaction_id = $this->createCredit($client->id, abs($total), $pricing->currency);
                        }

                        // Include the error message
                        if (!empty($errors)) {
                            $error = $errors;
                        }

                        break;
                }

                if (isset($error) || ($error = $this->Services->errors())) {
                    $this->setMessage('error', $error);
                } else {
                    $this->flashMessage('message', Language::_('AdminClients.!success.service_edited', true));
                    $this->redirect($this->base_uri . 'clients/view/' . $client->id);
                }
            }
            $vars = (object)array_merge((array)$vars, $this->post);

            $service_fields = $module->getAdminEditFields($package, $vars);
        }

        // Populate module service fields
        $input_html = new FieldsHtml($service_fields);
        $compatible_packages = $this->Packages->getCompatiblePackages(
            $package->id,
            $package->module_id,
            $service->parent_service_id ? 'addon' : 'standard'
        );

        // If no compatible packages are available, the package itself is the only compatible package
        if (empty($compatible_packages)) {
            $compatible_packages = [$package];
        }

        $terms = [];
        foreach ($compatible_packages as $pack) {
            $terms['package_' . $pack->id] = ['name' => $pack->name, 'value' => 'optgroup'];
            $terms = $terms + $this->getPackageTerms($pack, true, $pack->id != $package->id);
        }

        $actions = ['' => Language::_('AppController.select.please', true)]
            + $this->Services->getActions($service->status);
        // Remove the option to change the renew date
        if (!$allow_renew_date) {
            unset($actions['change_renew']);
        }

        // Set language for periods
        $periods = $this->Packages->getPricingPeriods();
        foreach ($this->Packages->getPricingPeriods(true) as $period => $lang) {
            $periods[$period . '_plural'] = $lang;
        }
        $this->set('periods', $periods);

        // Set the expected service renewal price
        $service->renewal_price = $this->Services->getRenewalPrice($service->id);

        // Set the child services
        $service->children = $this->Services->getAllChildren($service->id);

        $module_name = $module->getName();
        $module_row_name = $module->moduleRowName();
        $module_row_fields = $this->getModuleRowFields($module, $package->module_id);
        $currencies = $this->Currencies->getAll($this->company_id);
        $this->set(
            'form',
            $this->partial(
                'admin_clients_editservice_'
                . ($service->status == 'pending' || $service->status == 'in_review' ? 'pending' : 'basic'),
                compact(
                    'currencies',
                    'periods',
                    'service',
                    'package',
                    'module_name',
                    'input_html',
                    'actions',
                    'terms',
                    'vars',
                    'module_row_fields',
                    'module_row_name',
                    'statuses',
                    'recurring_coupon'
                )
            )
        );

        $this->set('service', $service);
        $this->set('package', $package);
        $this->set('module_name', $module->getName());
        $this->set('tabs', $this->getServiceTabs($service, $package, $module));

        // Show an activation message regarding the service in review
        $notice_messages = [];
        if ($service->status == 'in_review') {
            $notice_messages['in_review'] = [Language::_(
                'AdminClients.!notice.service_in_review',
                true,
                $statuses['in_review'],
                $statuses['pending']
            )];
        }

        // Display a notice regarding this service having queued service changes
        if (!empty($queued_changes)) {
            $notice_messages['queued_service_change'] = [
                Language::_(
                    'AdminClients.!notice.queued_service_change',
                    true
                )
            ];
        }

        // Display a message with the reason this service was suspended
        if ($service->status == 'suspended' && !empty($service->suspension_reason)) {
            $notice_messages = Language::_(
                'AdminClients.editservice.suspension_reason_note',
                true,
                $service->suspension_reason
            );
        }

        // Display a message with the reason this service was cancelled
        if ($service->date_canceled && $service->status != 'canceled' && !empty($service->cancellation_reason)) {
            $notice_messages = Language::_(
                'AdminClients.editservice.cancellation_reason_note',
                true,
                $service->cancellation_reason
            );
        }

        if (!empty($notice_messages)) {
            $this->setMessage('notice', $notice_messages);
        }

        if ($this->isAjax()) {
            return $this->renderAjaxWidgetIfAsync(isset($this->get['whole_widget']) ? null : false);
        }
        $this->view($this->view->fetch('admin_clients_editservice'));
    }

    /**
     * Retrieves a set of module row IDs and names
     *
     * @param Module $module An instance of the module
     * @param int $module_id The ID of the module whose module rows to fetch
     * @param bool $show_default Whether or not to show the default package value as the
     *  first option (optional, default false)
     * @return array A key/value array where each key is a module row ID and each value is the name of the row
     */
    private function getModuleRowFields(Module $module, $module_id, $show_default = false)
    {
        $this->uses(['ModuleManager']);

        // Fetch the module data
        $mod = $this->ModuleManager->get($module_id);
        if (empty($mod) || empty($mod->rows)) {
            return [];
        }

        // Create a set of module rows
        $module_rows = [];
        $module_row_meta_key = $module->moduleRowMetaKey();
        foreach ($mod->rows as $row) {
            if (is_object($row) && property_exists($row, 'meta') && property_exists($row->meta, $module_row_meta_key)) {
                $module_rows[$row->id] = $row->meta->{$module_row_meta_key};
            }
        }

        // Add the module rows to each module group they are assigned to
        $module_groups = [];
        foreach ($mod->groups as $group) {
            if (is_object($group) && !empty($group->rows)) {
                $module_groups[$group->id] = ['name' => $group->name, 'rows' => []];
                foreach ($group->rows as $row) {
                    if (array_key_exists($row->id, $module_rows)) {
                        $module_groups[$group->id]['rows'][$row->id] = $module_rows[$row->id];
                    }
                }
            }
        }

        // Remove all module rows used in the module groups
        foreach ($module_groups as $group) {
            foreach ($group['rows'] as $id => $name) {
                unset($module_rows[$id]);
            }
        }

        // Create the select options for all module rows not in a group
        $options = [];

        if ($show_default) {
            $options[''] = Language::_('AdminClients.addservice.auto_choose', true);
        }

        foreach ($module_rows as $id => $name) {
            $options[] = [
                'name' => $name,
                'value' => $id
            ];
        }

        // Create the select groups and options for all module rows in a group
        foreach ($module_groups as $group) {
            $options[] = [
                'name' => $group['name'],
                'value' => 'optgroup'
            ];

            foreach ($group['rows'] as $id => $name) {
                $options[] = [
                    'name' => $name,
                    'value' => $id
                ];
            }

            $options[] = [
                'name' => '',
                'value' => 'close_optgroup'
            ];
        }

        return $options;
    }

    /**
     * Edit service add-ons
     */
    public function editServiceAddons()
    {
        $this->uses(['Services', 'Packages', 'ModuleManager']);

        // Ensure we have a client to load
        if (!isset($this->get[0]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            $this->redirect($this->base_uri . 'clients/');
        }

        // Ensure we have a service
        if (!isset($this->get[1])
            || !($service = $this->Services->get((int)$this->get[1]))
            || $service->client_id != $client->id
        ) {
            $this->redirect($this->base_uri . 'clients/view/' . $client->id . '/');
        }

        // Must be an AJAX request and the service must not be canceled
        if ($service->status == 'canceled' || !$this->isAjax()) {
            $this->redirect($this->base_uri . 'clients/editservice/' . $client->id . '/' . $service->id);
        }

        $package = $this->Packages->get($service->package->id);
        $module = $this->ModuleManager->initModule($service->package->module_id);
        if ($module) {
            $module->base_uri = $this->base_uri;
        }

        // Set addon packages
        $package_options = [];
        $package_attributes = [];
        if ($service->package_group_id) {
            [$package_options, $package_attributes] = $this->listPackages(
                $client->id,
                $service->package_group_id,
                true
            );
        }

        // Set language for periods
        $periods = $this->Packages->getPricingPeriods();
        foreach ($this->Packages->getPricingPeriods(true) as $period => $lang) {
            $periods[$period . '_plural'] = $lang;
        }
        $this->set('periods', $periods);

        $this->set('client', $client);
        $this->set('tabs', $this->getServiceTabs($service, $package, $module, 'editserviceaddons'));
        $this->set('package', $package);
        $this->set('service', $service);
        $this->set('services', $this->Services->getAllChildren($service->id));
        $this->set('package_options', $package_options);
        $this->set('package_attributes', $package_attributes);

        if ($this->isAjax()) {
            return $this->renderAjaxWidgetIfAsync(null);
        }
    }

    /**
     * Retrieves the service management tabs when editing a service
     *
     * @param stdClass $service An stdClass object representing the service
     * @param stdClass $package An stdClass object representing the service's package
     * @param Module $module An instance of the module used by the service
     * @param string|null $method The method being called (i.e. the tab action, optional)
     * @param int|null $plugin_id The ID of the plugin being called (optional)
     */
    private function getServiceTabs(stdClass $service, stdClass $package, $module, $method = null, $plugin_id = null)
    {
        // Get tabs
        $tabs = [
            [
                'name' => Language::_('AdminClients.editservice.tab_basic', true),
                'attributes' => [
                    'href' => $this->base_uri . 'clients/editservice/' . $service->client_id . '/' . $service->id . '/',
                    'class' => 'ajax'
                ],
                // Default to this basic tab as being the current one
                'current' => ($method === null && $plugin_id === null)
            ]
        ];

        // If the service is not an add-on, it could potentially have add-ons and should see the add-on tab
        if ($service->parent_service_id === null) {
            $tabs[] = [
                'name' => Language::_(
                    'AdminClients.editservice.tab_addon',
                    true,
                    $this->Services->getAllChildrenCount($service->id)
                ),
                'attributes' => [
                    'href' => $this->base_uri . 'clients/editserviceaddons/'
                        . $service->client_id . '/' . $service->id . '/',
                    'class' => 'ajax'
                ],
                'current' => (strtolower($method ?? '') === 'editserviceaddons' && $plugin_id === null)
            ];
        }

        // Set tabs only if the service has not been canceled
        if ($service->status != 'canceled') {
            // Retrieve the module tabs
            $module_tabs = ($module ? $module->getAdminServiceTabs($service) : []);

            // Set each of the module tabs
            foreach ($module_tabs as $action => $name) {
                $tabs[] = [
                    'name' => $name,
                    'attributes' => [
                        'href' => $this->base_uri . 'clients/servicetab/' . $service->client_id . '/'
                            . $service->id . '/' . $action . '/',
                        'class' => 'ajax'
                    ],
                    'current' => ($plugin_id === null && strtolower($action) === strtolower($method ?? ''))
                ];
            }

            // Retrieve the plugin tabs
            foreach ($package->plugins as $plug) {
                // Skip the plugin if it is not available
                if (!($plugin = $this->getPlugin($plug->plugin_id))) {
                    continue;
                }

                foreach ($plugin->getAdminServiceTabs($service) as $action => $tab) {
                    $attributes = [
                        'href' => (!empty($tab['href'])
                            ? $tab['href']
                            : $this->base_uri . 'clients/servicetab/' . $service->client_id . '/'
                                . $service->id . '/' . $plug->plugin_id . '/' . $action . '/'
                        ),
                        'class' => 'ajax'
                    ];

                    $tabs[] = [
                        'name' => $tab['name'],
                        'attributes' => $attributes,
                        'current' => ($plug->plugin_id == $plugin_id && strtolower($action) === strtolower($method ?? ''))
                    ];
                }
            }
        }

        return $tabs;
    }

    /**
     * Retrieves a list of pending service changes queued
     *
     * @param int $service_id The ID of the service whose queued service changes to fetch
     * @return array An array of all pending service changes
     */
    private function getQueuedServiceChanges($service_id)
    {
        $this->uses(['ServiceChanges']);
        return $this->ServiceChanges->getAll('pending', $service_id);
    }

    /**
     * Cancel any pending queued service changes
     *
     * @param int $service_id The Id of the service whose pending service changes to cancel
     */
    private function cancelServiceChanges($service_id)
    {
        $this->uses(['Invoices', 'ServiceChanges', 'Transactions']);

        // Cancel any pending service changes
        $queued_changes = $this->getQueuedServiceChanges($service_id);
        foreach ($queued_changes as $change) {
            // Fetch payments applied to the invoice
            $transactions = $this->Transactions->getApplied(null, $change->invoice_id);

            // Unapply payments from the invoice
            foreach ($transactions as $transaction) {
                $this->Transactions->unapply($transaction->id, [$change->invoice_id]);
            }

            // Void the invoice
            $this->Invoices->edit($change->invoice_id, ['status' => 'void']);

            // Cancel the service change
            $this->ServiceChanges->edit($change->id, ['status' => 'canceled']);
        }
    }

    /**
     * Queue's a service change for later processing
     *
     * @param int $service_id The ID of the service being queued
     * @param int $invoice_id The ID of the invoice associated with the service change
     * @param array $vars An array of all data to queue to successfully update a service
     * @return array An array of queue info, including:
     *
     *  - service_change_id The ID of the service change, if created
     *  - errors An array of errors
     */
    private function queueServiceChange($service_id, $invoice_id, array $vars)
    {
        $this->uses(['Invoices', 'ServiceChanges']);

        // Create a new service change
        unset($vars['prorate']);
        $change_vars = ['data' => $vars];
        $change_id = $this->ServiceChanges->add($service_id, $invoice_id, $change_vars);

        return [
            'service_change_id' => $change_id,
            'errors' => $this->ServiceChanges->errors()
        ];
    }

    /**
     * Creates an invoice from the given line items
     *
     * @param stdClass $client An stdClass object representing the client
     * @param PresenterInterface $presenter An instance of the PresenterInterface
     * @param string $currency The ISO-4217 currency code
     * @param bool $deliver True to set the invoice for delivery to the client's invoice method (optional, default true)
     * @param int $service_id The ID of the service the items are for (optional)
     * @return array An key/value array containing:
     *
     *  - invoice_id The ID of the invoice, if created
     *  - errors An array of errors if the invoice could not be created
     */
    private function makeInvoice(
        stdClass $client,
        PresenterInterface $presenter,
        $currency,
        $deliver = true,
        $service_id = null
    ) {
        // Invoice and queue the service change
        $invoice_vars = [
            'client_id' => $client->id,
            'date_billed' => date('c'),
            'date_due' => date('c'),
            'currency' => $currency,
            'lines' => $this->makeLineItems($presenter, $service_id)
        ];

        // Set this invoice for delivery
        if ($deliver && isset($client->settings['inv_method'])) {
            $invoice_vars['delivery'] = [$client->settings['inv_method']];
        }

        // Create the invoice
        $invoice_id = $this->Invoices->add($invoice_vars);

        return [
            'invoice_id' => $invoice_id,
            'errors' => $this->Invoices->errors()
        ];
    }

    /**
     * Creates a set of line items from the given presenter
     * @see AdminClients::makeInvoice
     *
     * @param PresenterInterface $presenter An instance of the PresenterInterface
     * @param int $service_id The ID of the service the items are for (optional)
     * @return array An array of line items
     */
    private function makeLineItems(PresenterInterface $presenter, $service_id = null)
    {
        $items = [];

        // Setup line items from each of the presenter's items
        foreach ($presenter->items() as $item) {
            // Tax has to be deconstructed since the presenter's tax amounts
            // cannot be passed along
            $items[] = [
                'qty' => $item->qty,
                'amount' => $item->price,
                'description' => $item->description,
                'tax' => !empty($item->taxes),
                'service_id' => ($service_id ? $service_id : null)
            ];
        }

        // Add a line item for each discount amount
        foreach ($presenter->discounts() as $discount) {
            // The total discount is the negated total
            $items[] = [
                'qty' => 1,
                'amount' => (-1 * $discount->total),
                'description' => $discount->description,
                'tax' => false,
                'service_id' => ($service_id ? $service_id : null)
            ];
        }

        return $items;
    }

    /**
     * Creates an in-house credit for the client
     *
     * @param int $client_id The ID of the client to credit
     * @param float $amount The amount to credit
     * @param string $currency The ISO 4217 currency code for the credit
     * @return int $transaction_id The ID of the transaction for this credit
     */
    private function createCredit($client_id, $amount, $currency)
    {
        $this->uses(['Transactions']);

        // Apply the credit to the client account
        $vars = [
            'client_id' => $client_id,
            'amount' => $amount,
            'currency' => $currency,
            'type' => 'other'
        ];

        // Find and set the transaction type to In House Credit, if available
        $transaction_types = $this->Transactions->getTypes();
        foreach ($transaction_types as $type) {
            if ($type->name == 'in_house_credit') {
                $vars['transaction_type_id'] = $type->id;
                break;
            }
        }

        return $this->Transactions->add($vars);
    }

    /**
     * Deletes a pending service
     */
    public function deleteService()
    {
        $this->uses(['Services']);

        // Ensure we have a client to load
        if (!isset($this->post['client_id'])
            || !($client = $this->Clients->get((int)$this->post['client_id'], false))
        ) {
            $this->redirect($this->base_uri . 'clients/');
        }

        // Ensure we have a service that belongs to this client
        if (!isset($this->post['id']) || !($service = $this->Services->get((int)$this->post['id'])) ||
            $service->client_id != $client->id) {
            $this->redirect($this->base_uri . 'clients/view/' . $client->id . '/');
        }

        // Set URI to be redirected to
        $redirect_uri = (isset($this->post['redirect_uri'])
            ? $this->post['redirect_uri']
            : $this->base_uri . 'clients/view/' . $client->id . '/'
        );

        // Delete the service
        $this->Services->delete($service->id);

        if (($errors = $this->Services->errors())) {
            $this->flashMessage('error', $errors);
        } else {
            $this->flashMessage('message', Language::_('AdminClients.!success.service_deleted', true));
        }

        $this->redirect($redirect_uri);
    }

    /**
     * Service tab request
     */
    public function serviceTab()
    {
        $this->uses(['Services', 'Packages', 'ModuleManager']);

        // Ensure we have a client to load
        if (!isset($this->get[0]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            $this->redirect($this->base_uri . 'clients/');
        }

        // Ensure we have a service
        if (!isset($this->get[1])
            || !($service = $this->Services->get((int)$this->get[1]))
            || $service->client_id != $client->id
            || !isset($this->get[2])
        ) {
            $this->redirect($this->base_uri . 'clients/view/' . $client->id . '/');
        }

        $package = $this->Packages->get($service->package->id);
        $module = $this->ModuleManager->initModule($service->package->module_id);
        $module->base_uri = $this->base_uri;
        $method = null;
        $plugin_id = null;

        // If the second GET argument is a number, we must infer this to mean a plugin, not a module
        $tab_view = '';
        if (is_numeric($this->get[2])) {
            // No plugin method was given to call, or the plugin is not supported by the service
            $valid_plugins = $this->Form->collapseObjectArray($package->plugins, 'plugin_id', 'plugin_id');
            if (!isset($this->get[3]) || !array_key_exists($this->get[2], $valid_plugins)) {
                $this->redirect($this->base_uri . 'clients/editservice/' . $client->id . '/' . $service->id . '/');
            }

            // Determine the plugin/method called
            $plugin_id = $this->get[2];
            $method = $this->get[3];

            // Process and retrieve the plugin tab content
            $tab_view = $this->processPluginTab($plugin_id, $method, $service);
        } else {
            // Determine the method called
            $method = $this->get[2];

            // Process and retrieve the module tab content
            $tab_view = $this->processModuleTab($module, $method, $package, $service);
        }

        $this->set('tab_view', $tab_view);
        $this->set('service', $service);
        $this->set('package', $package);
        $this->set('tabs', $this->getServiceTabs($service, $package, $module, $method, $plugin_id));

        if ($this->isAjax()) {
            return $this->renderAjaxWidgetIfAsync(isset($this->get['whole_widget']) ? null : false);
        }
        $this->view($this->view->fetch('admin_clients_servicetab'));
    }

    /**
     * Processes and retrieves the module tab content for the given method
     *
     * @param Module $module The module instance
     * @param string $method The method on the module to call to retrieve the tab content
     * @param stdClass $package An stdClass object representing the package
     * @param stdClass $service An stdClass object representing the service being managed
     * @return string The tab content
     */
    private function processModuleTab($module, $method, stdClass $package, stdClass $service)
    {
        $content = '';

        // Get tabs
        $admin_tabs = $module->getAdminServiceTabs($service);
        $valid_method = array_key_exists(strtolower($method), array_change_key_case($admin_tabs, CASE_LOWER));

        // Load/process the tab request
        if ($valid_method && is_callable([$module, $method])) {
            // Set the module row used for this service
            $module->setModuleRow($module->getModuleRow($service->module_row_id));

            // Call the module method and set any messages to the view
            $content = $module->{$method}($package, $service, $this->get, $this->post, $this->files);
            $this->setServiceTabMessages($module->errors(), $module->getMessages());
        } else {
            // Invalid method called, redirect
            $this->redirect($this->base_uri . 'clients/editservice/' . $service->client_id . '/' . $service->id . '/');
        }

        return $content;
    }

    /**
     * Processes and retrieves the plugin tab content for the given method
     *
     * @param int $plugin_id The ID of the plugin
     * @param string $method The method on the plugin to call to retrieve the tab content
     * @param stdClass $service An stdClass object representing the service being managed
     * @return string The tab content
     */
    private function processPluginTab($plugin_id, $method, stdClass $service)
    {
        $content = '';

        if (($plugin = $this->getPlugin($plugin_id))) {
            $plugin->base_uri = $this->base_uri;

            // Get tabs
            $admin_tabs = $plugin->getAdminServiceTabs($service);
            $valid_method = array_key_exists(strtolower($method), array_change_key_case($admin_tabs, CASE_LOWER));

            // Retrieve the plugin tab content
            if ($valid_method && is_callable([$plugin, $method])) {
                // Call the plugin method and set any messages to the view
                $content = $plugin->{$method}($service, $this->get, $this->post, $this->files);
                $this->setServiceTabMessages($plugin->errors(), $plugin->getMessages());
            } else {
                // Invalid method called, redirect
                $this->redirect(
                    $this->base_uri . 'clients/editservice/' . $service->client_id . '/' . $service->id . '/'
                );
            }
        }

        return $content;
    }

    /**
     * Sets messages to the view based on the given errors and messages provided
     *
     * @param array|bool|null $errors An array of error messages (optional)
     * @param array $messages An array of any other messages keyed by type (optional)
     */
    private function setServiceTabMessages($errors = null, array $messages = null)
    {
        // Prioritize error messages over any other messages
        if (!empty($errors)) {
            $this->setMessage('error', $errors);
        } elseif (!empty($messages)) {
            // Display messages if any
            foreach ($messages as $type => $message) {
                $this->setMessage($type, $message);
            }
        } elseif (!empty($this->post)) {
            // Default to display a message after POST
            $this->setMessage('success', Language::_('AdminClients.!success.service_tab', true));
        }
    }

    /**
     * Retrieves an instance of the given plugin if it is enabled
     *
     * @param int $plugin_id The ID of the plugin
     * @return Plugin|null An instance of the plugin
     */
    private function getPlugin($plugin_id)
    {
        $this->uses(['PluginManager']);
        $this->components(['Plugins']);

        if (($plugin = $this->PluginManager->get($plugin_id)) && $plugin->enabled == '1') {
            try {
                return $this->Plugins->create($plugin->dir);
            } catch (Throwable $e) {
                // Do nothing
            }
        }

        return null;
    }

    /**
     * Service Info
     */
    public function serviceInfo()
    {
        $this->uses(['Services', 'Packages', 'ModuleManager']);

        // Ensure we have a client to load
        if (!isset($this->get[0]) || !($client = $this->Clients->get((int)$this->get[0]))) {
            $this->redirect($this->base_uri . 'clients/');
        }

        // Ensure we have a service
        if (!isset($this->get[1])
            || !($service = $this->Services->get((int)$this->get[1]))
            || $service->client_id != $client->id
        ) {
            $this->redirect($this->base_uri . 'clients/view/' . $client->id . '/');
        }
        $this->set('service', $service);

        $package = $this->Packages->get($service->package->id);
        $module = $this->ModuleManager->initModule($service->package->module_id);

        if ($module) {
            $module->base_uri = $this->base_uri;
            $module->setModuleRow($module->getModuleRow($service->module_row_id));
            $this->set('content', $module->getAdminServiceInfo($service, $package));
        }

        // Set any addon services
        $services = $this->Services->getAllChildren($service->id);
        // Set the expected service renewal price
        foreach ($services as $service) {
            $service->renewal_price = $this->Services->getRenewalPrice($service->id);
        }
        $this->set('services', $services);

        // Set language for periods
        $periods = $this->Packages->getPricingPeriods();
        foreach ($this->Packages->getPricingPeriods(true) as $period => $lang) {
            $periods[$period . '_plural'] = $lang;
        }
        $this->set('periods', $periods);
        $this->set('client', $client);

        echo $this->outputAsJson($this->view->fetch('admin_clients_serviceinfo'));
        return false;
    }

    /**
     * Fetch all packages options for the given pricing ID and optional service ID
     */
    public function packageOptions()
    {
        if (!$this->isAjax()) {
            $this->redirect($this->base_uri . 'clients/');
        }

        $this->uses(['Services', 'Packages', 'PackageOptions', 'PackageOptionConditionSets']);

        $package = $this->Packages->getByPricingId($this->get[0]);

        if (!$package) {
            return false;
        }

        $pricing = null;
        foreach ($package->pricing as $pricing) {
            if ($pricing->id == $this->get[0]) {
                break;
            }
        }

        // Set the 'new' option to 1 to indicate these config options are for a new package being added
        // Set the 'new' option to 0 to indicate these config options are for an existing service,
        // or changes to form fields (e.g. unchecking a checkbox that we do not want to display a default option for)
        $options = [
            'new' => (isset($this->get['service_id']) || isset($this->get['configoptions']) ? 0 : 1),
        ];

        $vars = [];
        $message = '';
        if (isset($this->get['service_id'])) {
            $service = $this->Services->get($this->get['service_id']);
            $options['upgrade'] = $service->package->id != $package->id;

            $service_options = $this->Services->getOptions($this->get['service_id']);
            $current_config_options = $this->PackageOptions->formatServiceOptions($service_options);
            $vars = array_merge($vars, $current_config_options);
            $options += $current_config_options;

            // If the sumbitted config options to not belong to the package associated with the
            // submitted pricing, unset them
            if (isset($this->get['configoptions'])) {
                $package_options = $this->Form->collapseObjectArray(
                    $this->PackageOptions->getByPackageId($pricing->package_id),
                    'name',
                    'id'
                );

                foreach ($this->get['configoptions'] as $option_id => $option) {
                    if (!array_key_exists($option_id, $package_options)) {
                        unset($this->get['configoptions']);
                        break;
                    }
                }
            }

            // Set warning about client limit
            if ($package->client_qty !== null && $options['upgrade']) {
                $service_count = $this->Services->getListCount(
                    $service->client_id,
                    'all',
                    true,
                    $package->id
                );

                if ($package->client_qty <= $service_count) {
                    $message = $this->setMessage('notice', Language::_('AdminClients.!notice.client_limit', true), true);
                }
            }
        }

        $vars = (object)array_merge($vars, $this->get);

        $package_options = $this->PackageOptions->getFields(
            $pricing->package_id,
            $pricing->term,
            $pricing->period,
            $pricing->currency,
            $vars,
            null,
            $options
        );

        $fields_html = new FieldsHtml($package_options);
        $option_logic = new OptionLogic();
        $option_logic->setPackageOptionConditionSets(
            $this->PackageOptionConditionSets->getAll(
                [
                    'package_id' => $pricing->package_id,
                    'opition_ids' => $this->Form->collapseObjectArray(
                        $this->PackageOptions->getAllByPackageId(
                            $pricing->package_id,
                            $pricing->term,
                            $pricing->period,
                            $pricing->currency,
                            null,
                            $options
                        ),
                        'id',
                        'id'
                    )
                ],
                ['option_id']
            )
        );
        $option_logic->setOptionContainerSelector($fields_html->getContainerSelector());
        $this->set('input_html', $fields_html);
        $this->set('option_logic_js', $option_logic->getJavascript());

        echo $this->outputAsJson($message . $this->view->fetch('admin_clients_packageoptions'));
        return false;
    }

    /**
     * Process the requested step
     *
     * @param string $step The add services step to process
     * @param stdClass $package A stdClass object representing the primary package being ordered
     * @param stdClass $package_group A stdClass object representing the package group of the
     *  primary package being ordered
     * @param stdClass $client A stdClass object representing the client for which the service is being added
     * @return string The step to render
     */
    private function processServiceStep($step, $package, $package_group, $client)
    {
        $this->uses(['PackageOptionConditionSets', 'PackageOptions']);

        // Detect module refresh fields
        $refresh_fields = isset($this->post['refresh_fields']) && $this->post['refresh_fields'] == 'true';

        $pricing = $this->Services->getPackagePricing($this->post['pricing_id'] ?? null);

        switch ($step) {
            case 'edit':
                $this->post = $this->post['item'];
                return 'basic';
            default:
            case 'basic':
                $item = [
                    'parent_service_id' => isset($this->post['parent_service_id'])
                        ? $this->post['parent_service_id']
                        : null,
                    'package_group_id' => $package_group->id,
                    'pricing_id' => $this->post['pricing_id'],
                    'module_row_id' => !empty($this->post['module_row_id']) ? $this->post['module_row_id'] : null,
                    'qty' => isset($this->post['qty']) ? $this->post['qty'] : 1,
                    'client_id' => $client->id,
                    'staff_id' => $this->Session->read('blesta_staff_id')
                ];

                // Reset notify order if not given
                if (!isset($this->post['notify_order']) || $this->post['notify_order'] != 'true') {
                    $this->post['notify_order'] = 'false';
                }

                $this->post['item'] = array_merge($this->post, $item);
                unset($this->post['item']['addon']);

                if ($refresh_fields) {
                    return 'basic';
                }

                // Validate that the submitted config options are valid given the Option Logic
                $option_logic = new OptionLogic();
                $option_logic->setPackageOptionConditionSets(
                    $this->PackageOptionConditionSets->getAll(
                        [
                            'package_id' => $pricing->package_id,
                            'opition_ids' => $this->Form->collapseObjectArray(
                                $this->PackageOptions->getAllByPackageId(
                                    $pricing->package_id,
                                    $pricing->term,
                                    $pricing->period,
                                    $pricing->currency
                                ),
                                'id',
                                'id'
                            )
                        ],
                        ['option_id']
                    )
                );

                $config_options = (isset($this->post['configoptions']) ? $this->post['configoptions'] : []);
                if (!($errors = $option_logic->validate($config_options))) {
                    // Verify fields look correct in order to proceed
                    $this->Services->validateService($package, $this->post['item']);

                    $errors = $this->Services->errors();
                }

                if (!empty($errors)) {
                    $this->setMessage('error', $errors);
                    return 'basic';
                }

                // Queue any addons
                $addons = [];
                // Display addon-step if any addons to add
                if (isset($this->post['addon']) && !empty($this->post['addon'])) {
                    foreach ($this->post['addon'] as $group => $addon) {
                        if ($addon['id'] == '') {
                            continue;
                        }

                        $addons[] = [
                            'package_group_id' => $group,
                            'package_id' => $addon['id']
                        ];
                    }
                }

                unset($this->post['addon']);
                $this->post['queue'] = $addons;

                if (!empty($this->post['queue'])) {
                    return 'addon';
                }

                // Display confirmation if no addons available
                return 'confirm';

            case 'addon':
                $addon_package = $this->Packages->get($this->post['queue'][0]['package_id']);

                $item = [
                    'parent_service_id' => null,
                    'package_group_id' => $this->post['package_group_id'],
                    'package_id' => $addon_package->id,
                    'pricing_id' => $this->post['pricing_id'],
                    'module_row_id' => !empty($this->post['module_row_id']) ? $this->post['module_row_id'] : null,
                    'qty' => isset($this->post['qty']) ? $this->post['qty'] : 1,
                    'client_id' => $client->id,
                    'staff_id' => $this->Session->read('blesta_staff_id')
                ];
                $this->post['queue'][0] = array_merge($this->post, $item);
                unset($this->post['queue'][0]['item'], $this->post['queue'][0]['queue']);

                if ($refresh_fields) {
                    return 'addon';
                }

                // Validate that the submitted config options are valid given the Option Logic
                $option_logic = new OptionLogic();
                $option_logic->setPackageOptionConditionSets(
                    $this->PackageOptionConditionSets->getAll(
                        [
                            'package_id' => $pricing->package_id,
                            'opition_ids' => $this->Form->collapseObjectArray(
                                $this->PackageOptions->getAllByPackageId(
                                    $pricing->package_id,
                                    $pricing->term,
                                    $pricing->period,
                                    $pricing->currency
                                ),
                                'id',
                                'id'
                            )
                        ],
                        ['option_id']
                    )
                );

                $config_options = (isset($this->post['configoptions']) ? $this->post['configoptions'] : []);
                if (!($errors = $option_logic->validate($config_options))) {
                    // Verify addon looks correct in order to proceed
                    $this->Services->validateService($addon_package, $this->post['queue'][0]);

                    $errors = $this->Services->errors();
                }

                if (!empty($errors)) {
                    $this->setMessage('error', $errors);
                } else {
                    $item = array_shift($this->post['queue']);
                    if (!isset($this->post['item']['addons'])) {
                        $this->post['item']['addons'] = [];
                    }
                    $this->post['item']['addons'][] = $item;
                }

                // Display confirmation if no more addons to evaluate
                if (!isset($this->post['queue']) || empty($this->post['queue'])) {
                    return 'confirm';
                }

                // Render next addon or same if error occurred
                return 'addon';

            case 'confirm':
                // Add services if not saving coupon...
                if (!array_key_exists('set_coupon', $this->post)) {
                    $this->createService(
                        [
                            'client_id' => $client->id,
                            'staff_id' => $this->Session->read('blesta_staff_id'),
                            'coupon' => isset($this->post['coupon_code']) ? $this->post['coupon_code'] : null,
                            'invoice_method' => $this->post['invoice_method'],
                            'invoice_id' => isset($this->post['invoice_id']) ? $this->post['invoice_id'] : null,
                            'notify_order' => isset($this->post['notify_order']) ? $this->post['notify_order'] : null
                        ],
                        $this->post['item']
                    );

                    if (($errors = $this->Services->errors())) {
                        $this->setMessage('error', $errors);
                    } else {
                        $this->flashMessage('message', Language::_('AdminClients.!success.service_added', true));
                        $this->redirect($this->base_uri . 'clients/view/' . $client->id . '/');
                    }
                }
                return 'confirm';
        }

        return $step;
    }

    /**
     * Create a service and its related addons and create or append an invoice for said services
     *
     * @param array $details An array of service information including:
     *
     *  - client_id The ID of the client to add the service item form
     *  - coupon An coupon code used
     *  - invoice_method 'none', 'create', 'append'
     *  - invoice_id The invoice ID to append to (if invoice_method is 'append')
     * @param array $item An array of service item info including:
     *
     *  - parent_service_id The ID of the service this service is a child of (optional)
     *  - package_group_id The ID of the package group this service was added from (optional)
     *  - pricing_id The package pricing schedule ID for this service
     *  - module_row_id The module row to add the service under (optional, default module will decide)
     *  - use_module Whether or not to use the module when provisioning
     *  - status The stauts of the service (active, canceled, pending, suspend, in_review)
     *  - addons An array of addon items each including:
     *      - package_group_id The ID of the package group this service was added from (optional)
     *      - pricing_id The package pricing schedule ID for this service
     *      - module_row_id The module row to add the service under (optional, default module will decide)
     *      - use_module Whether or not to use the module when provisioning
     *      - qty The quanity consumed by this service (optional, default 1)
     *      - configoptions An array of key/value pair where each key is a package option ID and
     *          each value is its value
     *      - * Any other service field data to pass to the module
     *  - qty The quanity consumed by this service (optional, default 1)
     *  - configoptions An array of key/value pair where each key is a package option ID and each value is its value
     *  - * Any other service field data to pass to the module
     */
    private function createService($details, $item)
    {
        $this->uses(['Clients', 'Invoices', 'Services']);

        $currency = $this->Clients->getSetting($details['client_id'], 'default_currency');
        $currency = $currency->value;
        $service_ids = [];
        $package_ids = [];
        $coupon_id = null;
        $addons = isset($item['addons']) ? $item['addons'] : [];
        unset($item['addons']);
        $items = [$item];
        foreach ($addons as $addon) {
            $items[] = $addon;
        }

        foreach ($items as $index => $item) {
            if (!($package = $this->Packages->getByPricingId($item['pricing_id']))) {
                continue;
            }

            $package_ids[$package->id] = $item['pricing_id'];

            // Set the currency to the currency of the selected base package
            if ($index == 0) {
                foreach ($package->pricing as $pricing) {
                    if ($pricing->id == $item['pricing_id']) {
                        $currency = $pricing->currency;
                        break;
                    }
                }
            }
        }

        if (isset($details['coupon'])) {
            $coupon_id = $this->getCouponId($details['coupon']);
        }

        $parent_service_id = null;
        $status = isset($items[0]['status']) ? $items[0]['status'] : 'pending';
        $item_count = count($items);
        foreach ($items as $item) {
            if (!array_key_exists('parent_service_id', $item) || $item['parent_service_id'] == null) {
                $item['parent_service_id'] = $parent_service_id;
            }

            // Unset any fields that may adversely affect the Services::add() call
            unset(
                $item['date_added'],
                $item['date_renews'],
                $item['date_last_renewed'],
                $item['date_suspended'],
                $item['date_canceled'],
                $item['notify_order'],
                $item['invoice_id'],
                $item['invoice_method']
            );

            $item['coupon_id'] = $coupon_id;
            $item['status'] = ($item_count > 1 && isset($item['parent_service_id'])
                ? ($status == 'active' ? 'pending' : $status)
                : $status
            );
            $item['client_id'] = $details['client_id'];
            $item['use_module'] = isset($item['use_module']) ? $item['use_module'] : 'false';

            $notify = isset($details['notify_order']) && $details['notify_order'] == 'true'
                && $item['status'] === 'active';
            $service_id = $this->Services->add($item, $package_ids, $notify);
            if (($errors = $this->Services->errors())) {
                // Manually roll back service additions
                foreach ($service_ids as $service_id) {
                    $this->Services->delete($service_id, false);
                }

                return;
            }

            if ($parent_service_id === null) {
                $parent_service_id = $service_id;
            }

            $service_ids[] = $service_id;
        }

        if (!empty($service_ids)) {
            if ($details['invoice_method'] == 'create') {
                $this->Invoices->createFromServices($details['client_id'], $service_ids, $currency, date('c'));
            } elseif ($details['invoice_method'] == 'append') {
                $this->Invoices->appendServices($details['invoice_id'], $service_ids);
            } else {
                $this->updateServicesRenewalForProration($service_ids);
            }
        }
    }

    /**
     * Updates the renew date for each of the given services if it meets proration criteria
     *
     * @param array $service_ids A list of services to be updated
     */
    private function updateServicesRenewalForProration(array $service_ids)
    {
        foreach ($service_ids as $service_id) {
            $service = $this->Services->get($service_id);
            $dates = $this->Packages->getProrataDates(
                $service->pricing_id,
                $service->date_added . 'Z',
                $service->date_renews . 'Z'
            );

            if ($dates) {
                $fields = ['date_last_renewed' => $dates['start_date'], 'date_renews' => $dates['end_date']];
                $this->Services->edit($service->id, $fields, true);
            }
        }
    }

    /**
     * Fetches the coupon ID for a given coupon code and package ID
     *
     * @param string $coupon_code The coupon code
     * @return mixed The coupon ID if it exists, 0 if it does not exist, or null if no coupon code was given
     */
    private function getCouponId($coupon_code)
    {
        $this->uses(['Coupons']);
        $coupon_id = null;
        $coupon_code = trim($coupon_code);

        if ($coupon_code !== '') {
            if (($coupon = $this->Coupons->getByCode($coupon_code))) {
                $coupon_id = $coupon->id;
            } else {
                $coupon_id = 0;
            }
        }

        return $coupon_id;
    }

    /**
     * Render each add service step
     *
     * @param string $step The add services step to render
     * @param stdClass $package A stdClass object representing the primary package being ordered
     * @param stdClass $package_group A stdClass object representing the package group of
     *  the primary package being ordered
     * @param stdClass $client A stdClass object representing the client for which the service is being added
     */
    private function renderServiceStep($step, $package, $package_group, $client)
    {
        $this->uses(['PackageOptions']);

        if (!isset($this->Invoices)) {
            $this->uses(['Invoices']);
        }

        $this->post['step'] = $step;

        switch ($step) {
            default:
            case 'basic':
                $terms = $this->getPackageTerms($package);

                // Default status to 'pending', use module, and notify order, which eventually occurs
                // once the service is activated
                $vars = (object)[
                    'status' => 'pending',
                    'notify_order' => 'true',
                    'use_module' => 'true'
                ];

                if (!empty($this->post)) {
                    $vars = (object)array_merge((array)$vars, $this->post);

                    // Reset use_module if not given, or do not use the module if the status is not active
                    if ((count($this->post) != 1 && !isset($this->post['use_module'])) ||
                        (isset($vars->status) && $vars->status != 'active')) {
                        $vars->use_module = $this->post['use_module'] = 'false';
                    }
                }

                $module = $this->ModuleManager->initModule($package->module_id, $this->company_id);
                if (!$module) {
                    return;
                }

                $module_row_fields = $this->getModuleRowFields($module, $package->module_id, true);
                $module_row_name = $module->moduleRowName();
                $module->base_uri = $this->base_uri;
                $service_fields = $module->getAdminAddFields($package, $vars);
                $input_html = new FieldsHtml($service_fields);

                $module_name = $module->getName();

                $this->set('package', $package);

                // Remove the In Review status from being a selectable status
                $status = $this->Services->getStatusTypes();
                unset($status['in_review']);

                $invoices = $this->Form->collapseObjectArray(
                    $this->Invoices->getAll($client->id, 'open', ['date_due' => 'desc']),
                    'id_code',
                    'id'
                );

                // Get all add-on groups (child "addon" groups for this package group)
                // And all packages in the group
                $addon_groups = $this->Packages->getAllAddonGroups($package_group->id);

                foreach ($addon_groups as &$addon_group) {
                    $addon_group->packages = $this->Packages->getAllPackagesByGroup($addon_group->id, null, ['hidden' => true]);
                }

                $parent_service_id = isset($this->get['parent_service_id']) ? $this->get['parent_service_id'] : null;

                // Set warning about client limit
                if ($package->client_qty !== null) {
                    $service_count = $this->Services->getListCount(
                        $client->id,
                        'all',
                        true,
                        $package->id
                    );

                    if ($package->client_qty <= $service_count) {
                        $this->setMessage('notice', Language::_('AdminClients.!notice.client_limit', true));
                    }
                }

                $this->set(
                    'form',
                    $this->partial(
                        'admin_clients_addservice_basic',
                        compact(
                            'package',
                            'input_html',
                            'status',
                            'module_name',
                            'module_row_fields',
                            'module_row_name',
                            'terms',
                            'invoices',
                            'addon_groups',
                            'vars',
                            'parent_service_id'
                        )
                    )
                );
                break;
            case 'addon':
                $vars = (object)$this->post['queue'][0];

                $package = $this->Packages->get($this->post['queue'][0]['package_id']);
                $terms = $this->getPackageTerms($package);

                $module = $this->ModuleManager->initModule($package->module_id, $this->company_id);
                if (!$module) {
                    return;
                }

                $module_row_fields = $this->getModuleRowFields($module, $package->module_id, true);
                $module_row_name = $module->moduleRowName();
                $module->base_uri = $this->base_uri;
                $module_name = $module->getName();

                $service_fields = $module->getAdminAddFields($package, $vars);
                $input_html = new FieldsHtml($service_fields);

                // Set continuous post data
                unset($this->post['qty']);
                $order_info = base64_encode(serialize($this->post));

                $this->set('package', $package);
                $this->set(
                    'form',
                    $this->partial(
                        'admin_clients_addservice_addon',
                        compact(
                            'package',
                            'input_html',
                            'terms',
                            'vars',
                            'module_name',
                            'module_row_fields',
                            'module_row_name',
                            'order_info'
                        )
                    )
                );
                break;
            case 'confirm':
                $vars = new stdClass();
                if (!empty($this->post)) {
                    $vars = (object)$this->post;
                }

                $service = (isset($vars->item) ? $vars->item : []);

                // Determine the currency of the selected pricing
                $currency = null;
                $pricing = null;
                if (isset($service['pricing_id'])) {
                    foreach ($package->pricing as $package_pricing) {
                        if ($package_pricing->id == $service['pricing_id']) {
                            $currency = $package_pricing->currency;
                            $pricing = $this->Services->getPackagePricing($service['pricing_id']);
                            break;
                        }
                    }
                }

                // Default to client's currency
                if (empty($currency)) {
                    $currency = $this->Clients->getSetting($client->id, 'default_currency');
                    $currency = $currency->value;
                }

                // Set the coupon for this item
                if (isset($vars->coupon_code) && ($coupon_id = $this->getCouponId($vars->coupon_code))) {
                    $service['coupon_id'] = $coupon_id;
                }

                // Set options for the builder to use to construct the presenter
                // New service is being added, include setup fees, denote this is not recurring, and prorate from now
                $options = [
                    'includeSetupFees' => true,
                    'prorateStartDate' => date('c'),
                    'recur' => false
                ];

                // Initialize line totals
                $line_totals = [
                    'subtotal' => 0,
                    'total' => 0,
                    'total_without_exclusive_tax' => 0,
                    'tax' => []
                ];

                // Get the service items and start summing line totals
                $items = $this->Services->getServiceItems($service, $options, $line_totals, $currency);

                // Get the addons for this service
                $addon_services = [];
                if (isset($service['addons'])) {
                    $addon_services = $service['addons'];
                }

                // Get the addon items
                $addons = [];
                foreach ($addon_services as $i => $addon_service) {
                    // Synchronize this addon with the parent service if set to do so and it is not
                    // already being prorated
                    $addon_pricing = null;
                    if ($pricing
                        && isset($addon_service['pricing_id'])
                        && ($addon_pricing = $this->Services->getPackagePricing($addon_service['pricing_id']))
                        && ($sync_date = $this->Services->getChildRenewDate(
                            $addon_pricing,
                            $pricing,
                            $client->client_group_id
                        ))
                    ) {
                        // Set the prorate date to the parent service's renew date
                        $options['prorateEndDate'] = $sync_date;
                    }

                    // Get the package for this addon in order to use its name
                    $addons[$i]['name'] = (isset($addon_service['package_group_id'])
                            && ($addon_package_group = $this->PackageGroups->get($addon_service['package_group_id']))
                        )
                        ? $addon_package_group->name
                        : '';

                    // Apply coupon to addons
                    if (isset($service['coupon_id'])) {
                        $addon_service['coupon_id'] = $service['coupon_id'];
                    }

                    // Get items for this addon and add its sums to the line totals
                    $addons[$i]['items'] = $this->Services->getServiceItems(
                        $addon_service,
                        $options,
                        $line_totals,
                        $currency,
                        $addon_pricing ? $addon_pricing->currency : $currency
                    );
                }

                // Format line totals
                $line_totals['subtotal'] = $this->CurrencyFormat->format($line_totals['subtotal'], $currency);
                $line_totals['total'] = $this->CurrencyFormat->format($line_totals['total'], $currency);
                $line_totals['total_without_exclusive_tax'] = $this->CurrencyFormat->format(
                    $line_totals['total_without_exclusive_tax'],
                    $currency
                );

                if (isset($line_totals['discount'])) {
                    // Format discount amount
                    $line_totals['discount'] = $this->CurrencyFormat->format($line_totals['discount'], $currency);
                }

                foreach ($line_totals['tax'] as &$tax) {
                    // Format each tax total
                    $tax = $this->CurrencyFormat->format($tax, $currency);
                }

                // Get order info and status language
                $order_info = base64_encode(serialize($this->post));
                $status = $this->Services->getStatusTypes();

                // Get the invoice this service is being appended to if any
                $invoice = null;
                if (isset($vars->invoice_id) && isset($vars->invoice_method) && $vars->invoice_method == 'append') {
                    $invoice = $this->Invoices->get($vars->invoice_id);
                }

                $this->set('package', $package);
                $this->set(
                    'form',
                    $this->partial(
                        'admin_clients_addservice_confirm',
                        compact(
                            'items',
                            'package_group',
                            'addons',
                            'order_info',
                            'line_totals',
                            'vars',
                            'status',
                            'invoice'
                        )
                    )
                );
                break;
        }

        // Set continuous post data
        $this->post['order_info'] = base64_encode(serialize($this->post));
    }

    /**
     * Returns an array of all pricing terms for the given package
     *
     * @param stdClass $package A stdClass object representing the package to fetch the terms for
     * @param bool $renew Whether these terms are being fetched for an existing service (optional, default false)
     * @param bool $upgrade Whether these package terms are being fetched for an upgrade (optional, default false)
     * @return array An array of key/value pairs where the key is the package pricing ID and
     *  the value is a string representing the price, term, and period.
     */
    private function getPackageTerms(stdClass $package, $renew = false, $upgrade = false)
    {
        $singular_periods = $this->Packages->getPricingPeriods();
        $plural_periods = $this->Packages->getPricingPeriods(true);
        $terms = [];
        if (isset($package->pricing) && !empty($package->pricing)) {
            foreach ($package->pricing as $price) {
                // Set the initial amount to use
                $amount = $price->price;
                $renew_amount = (isset($price->price_renews) ? $price->price_renews : 0);

                if ($price->period == 'onetime') {
                    $term = 'AdminClients.addservice.term_onetime';
                } else {
                    $term = 'AdminClients.addservice.term';

                    // Override the initial price with the renewal price
                    if ($renew && isset($price->price_renews) && (!$upgrade || $package->upgrades_use_renewal)) {
                        $amount = $price->price_renews;
                    } elseif (isset($price->price_renews) && $price->price != $price->price_renews) {
                        // Set the term to display both the initial price and the renewal price
                        $term = 'AdminClients.addservice.term_recurring';
                    }
                }

                $terms[$price->id] = Language::_(
                    $term,
                    true,
                    $price->term,
                    $price->term != 1 ? $plural_periods[$price->period] : $singular_periods[$price->period],
                    $this->CurrencyFormat->format($amount, $price->currency),
                    $this->CurrencyFormat->format($renew_amount, $price->currency)
                );
            }
        }
        return $terms;
    }

    /**
     * List all packages available to the client
     *
     * @param int $client_id The ID of the client whose available packages will be listed
     * @param int $parent_group_id The ID of the parent group to list packages for
     * @param bool $return True to return the package options and package attributes arrays,
     *  false to set them in the view
     * @return array An array of package options and package attributes arrays if $return is true
     */
    private function listPackages($client_id = null, $parent_group_id = null, $return = false)
    {
        $this->uses(['Packages', 'PackageGroups']);

        // Get restricted packages available to the client
        $restricted_package_ids = [];
        if ($client_id) {
            $restricted_package_ids = $this->Form->collapseObjectArray(
                $this->Clients->getRestrictedPackages($client_id),
                'package_id',
                'package_id'
            );
        }

        $packages = [];
        if ($parent_group_id) {
            $package_groups = $this->Packages->getAllAddonGroups($parent_group_id);
        } else {
            $package_groups = $this->PackageGroups->getAll($this->company_id, 'standard');
        }

        // Sort the package groups by name
        $group_names = [];
        foreach ($package_groups as $index => $group) {
            $group_names[$index] = $group->name;
        }
        array_multisort($group_names, SORT_FLAG_CASE | SORT_NATURAL, $package_groups);

        // Nest packages under a package group by type
        $package_group_names = [];
        foreach ($package_groups as $package_group) {
            // Save a list of the package group names to use in the package options later
            $package_group_names[$package_group->id] = $package_group->name;

            // Get all packages grouped by status and package group
            $temp_packages = $this->Packages->getAllPackagesByGroup($package_group->id, null, ['hidden' => true]);
            foreach ($temp_packages as $package) {
                if (!array_key_exists($package->status, $packages)) {
                    $packages[$package->status] = [];
                }
                if (!array_key_exists($package_group->id, $packages[$package->status])) {
                    $packages[$package->status][$package_group->id] = [];
                }

                $packages[$package->status][$package_group->id][] = $package;
            }
        }
        unset($package_groups, $package_group);

        // Sort packages by status
        ksort($packages);

        // Set the package options and attributes
        $package_options = [];
        $package_attributes = [];
        foreach ($packages as $status => $package_group_ids) {
            // Set the package option for the status
            $package_options[] = [
                'name' => Language::_('AdminClients.addservice.status.' . $status, true),
                'value' => $status
            ];
            $package_attributes[$status] = ['class' => $status, 'disabled' => 'disabled'];

            // Set the package options for each group in this status
            foreach ($package_group_ids as $group_id => $packages) {
                // Set the group name
                $package_options[] = [
                    'name' => $package_group_names[$group_id],
                    'value' => 'optgroup'
                ];

                // Set each package under this group
                $pack_opts = [];
                foreach ($packages as $package) {
                    $value = $group_id . '_' . $package->id;

                    $pack_opts[] = [
                        'name' => $package->name,
                        'value' => $value
                    ];

                    // Mark the package disabled if it is restricted
                    // and unavailable to the client
                    if (($client_id && $status == 'restricted'
                            && !array_key_exists($package->id, $restricted_package_ids))
                        || $status == 'inactive'
                    ) {
                        $package_attributes[$value] = ['disabled' => 'disabled'];
                    }
                }

                // Sort the packages by name
                $pack_names = [];
                $pack_values = [];
                foreach ($pack_opts as $index => $pack) {
                    $pack_names[$index] = $pack['name'];
                    $pack_values[$index] = $pack['value'];
                }

                array_multisort($pack_names, SORT_FLAG_CASE | SORT_NATURAL, $pack_values, SORT_ASC, $pack_opts);
                $package_options = array_merge($package_options, $pack_opts);
            }

            // Close the option group
            if (!empty($package_group_ids)) {
                $package_options[] = [
                    'name' => '',
                    'value' => 'close_optgroup'
                ];
            }
        }

        if ($return) {
            return [$package_options, $package_attributes];
        }

        $this->set('package_options', $package_options);
        $this->set('package_attributes', $package_attributes);
    }

    /**
     * AJAX Fetch all states belonging to a given country (json encoded ajax request)
     */
    public function getStates()
    {
        $this->uses(['States']);
        // Prepend "all" option to state listing
        $states = [];
        if (isset($this->get[0])) {
            $states = (array)$this->Form->collapseObjectArray($this->States->getList($this->get[0]), 'name', 'code');
        }

        echo json_encode($states);
        return false;
    }

    /**
     * Render the given Client view element
     *
     * @param string $view The view to render
     * @param bool $content_only True to only return the content, false to render it
     * @return mixed boolean false if this is an ajax request that can not be rendered within a structure,
     *  string containing the content to be rendered, or void if the content is rendered automatically
     */
    private function renderClientView($view, $content_only = false)
    {
        $data = $this->view->fetch($view);

        // Return data to be set
        if ($content_only) {
            return $data;
        }

        // Render data for an ajax request
        if ($this->isAjax()) {
            echo $data;
            return false;
        } else {
            // Set data to be displayed with the AdminClients::view()
            $this->view($data);
        }
    }

    /**
     * Sets a 'password_options' variable to the current view, which contains JSON-encoded
     * password options for the password generator
     */
    private function setPasswordOptions()
    {
        // Set JSON-encoded password generator character options
        // alphanumeric characters are supported by default
        $this->set(
            'password_options',
            json_encode([
                'include' => [(object)['chars' => [['A', 'Z'], ['a', 'z'], ['0', '9']]]]
            ])
        );
    }
}
