<?php
/**
 * AgingInvoices report
 *
 * @package blesta
 * @subpackage blesta.components.reports.aging_invoices
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AgingInvoices implements ReportInterface
{

    /**
     * Load language
     */
    public function __construct()
    {
        Loader::loadComponents($this, ['Record', 'SettingsCollection']);
        Loader::loadModels($this, ['Invoices']);

        // Load the language required by this report
        Language::loadLang('aging_invoices', null, dirname(__FILE__) . DS . 'language' . DS);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return Language::_('AgingInvoices.name', true);
    }

    /**
     * {@inheritdoc}
     */
    public function getFormats()
    {
        return ['csv', 'json'];
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions($company_id, array $vars = [])
    {
        Loader::loadHelpers($this, ['Javascript']);

        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('options', 'default');
        $this->view->setDefaultView('components' . DS . 'reports' . DS . 'aging_invoices' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $this->view->set('vars', (object)$vars);

        // Set statuses
        $any = ['' => Language::_('AgingInvoices.option.any', true)];
        $this->view->set('statuses', array_merge($any, $this->Invoices->getStatuses()));

        return $this->view->fetch();
    }

    /**
     * {@inheritdoc}
     */
    public function getKeyInfo()
    {
        return [
            'id_code' => ['name' => Language::_('AgingInvoices.heading.id_code', true)],
            'client_id_code' => ['name' => Language::_('AgingInvoices.heading.client_id_code', true)],
            'client_first_name' => ['name' => Language::_('AgingInvoices.heading.client_first_name', true)],
            'client_last_name' => ['name' => Language::_('AgingInvoices.heading.client_last_name', true)],
            'client_email' => ['name' => Language::_('AgingInvoices.heading.client_email', true)],
            'client_numbers' => [
                'name' => Language::_('AgingInvoices.heading.client_numbers', true),
                'format' => [
                    function ($key, $value, $format) {
                        if ($format == 'json') {
                            $value = explode("\n", $value);
                        }

                        return $value;
                    }
                ]
            ],
            'subtotal' => ['name' => Language::_('AgingInvoices.heading.subtotal', true)],
            'total' => ['name' => Language::_('AgingInvoices.heading.total', true)],
            'paid' => ['name' => Language::_('AgingInvoices.heading.paid', true)],
            'currency' => ['name' => Language::_('AgingInvoices.heading.currency', true)],
            'status' => [
                'name' => Language::_('AgingInvoices.heading.status', true),
                'format' => ['replace'],
                'options' => $this->Invoices->getStatuses()
            ],
            'date_billed' => [
                'name' => Language::_('AgingInvoices.heading.date_billed', true),
                'format' => ['date']
            ],
            'date_due' => [
                'name' => Language::_('AgingInvoices.heading.date_due', true),
                'format' => ['date']
            ],
            'past30' => ['name' => Language::_('AgingInvoices.heading.past30', true)],
            'past60' => ['name' => Language::_('AgingInvoices.heading.past60', true)],
            'past90' => ['name' => Language::_('AgingInvoices.heading.past90', true)]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAll($company_id, array $vars)
    {
        Loader::loadHelpers($this, ['Date']);

        // Set the keys for ID codes
        $replacement_keys = Configure::get('Blesta.replacement_keys');

        // Format dates
        $timezone = $this->SettingsCollection->fetchSetting(null, $company_id, 'timezone');
        $timezone = (array_key_exists('value', $timezone) ? $timezone['value'] : 'UTC');
        $this->Date->setTimezone($timezone, 'UTC');

        $now = $this->Date->format('Y-m-d H:i:s', date('c'));

        $status = (!empty($vars['status']) ? $vars['status'] : null);
        $fields = ['invoices.subtotal', 'invoices.total', 'invoices.paid',
            'invoices.currency', 'invoices.status',
            'invoices.date_billed', 'invoices.date_due',
            'REPLACE(invoices.id_format, ?, invoices.id_value)' => 'id_code',
            'REPLACE(clients.id_format, ?, clients.id_value)' => 'client_id_code',
            'contacts.first_name' => 'client_first_name',
            'contacts.last_name' => 'client_last_name',
            'contacts.email' => 'client_email',
            'GROUP_CONCAT(contact_numbers.number SEPARATOR ?)' => 'client_numbers'
        ];
        $values = [
            $replacement_keys['invoices']['ID_VALUE_TAG'],
            $replacement_keys['clients']['ID_VALUE_TAG'],
            "\n"
        ];

        $past_due_fields = [
            'IF(DATE_ADD(invoices.date_due, INTERVAL 30 DAY) <= ?,'
            . '(IF(DATE_ADD(invoices.date_due, INTERVAL 60 DAY) <= ?,?,invoices.total-IFNULL(invoices.paid,?))),?)'
            => 'past30',
            'IF(DATE_ADD(invoices.date_due, INTERVAL 60 DAY) <= ?,'
            . '(IF(DATE_ADD(invoices.date_due, INTERVAL 90 DAY) <= ?,?,invoices.total-IFNULL(invoices.paid,?))),?)'
            => 'past60',
            'IF(DATE_ADD(invoices.date_due, INTERVAL 90 DAY) <= ?,invoices.total-IFNULL(invoices.paid,?),?)'
            => 'past90',
        ];
        $past_due_values = [
            $now, $now, null, 0, null,
            $now, $now, null, 0, null,
            $now, 0, null,
        ];

        $this->Record->select($fields, false)
            ->appendValues($values)
            ->select($past_due_fields, false)
            ->appendValues($past_due_values)
            ->from('invoices')
            ->innerJoin('clients', 'clients.id', '=', 'invoices.client_id', false)
            ->on('contacts.contact_type', '=', 'primary')
            ->innerJoin('contacts', 'contacts.client_id', '=', 'clients.id', false)
            ->on('client_groups.company_id', '=', $company_id)
            ->innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)
            ->on('contact_numbers.type', '=', 'phone')
            ->leftJoin('contact_numbers', 'contact_numbers.contact_id', '=', 'contacts.id', false)
            ->where('invoices.date_closed', '=', null);

        // Filter
        if ($status) {
            $this->Record->where('invoices.status', '=', $status);
        }

        $this->Record->group(['invoices.id'])
            ->having('DATE_ADD(invoices.date_due, INTERVAL 30 DAY)', '<=', $now, true, false)
            ->order(['clients.id' => 'ASC', 'invoices.date_due' => 'ASC']);

        return new IteratorIterator($this->Record->getStatement());
    }
}
