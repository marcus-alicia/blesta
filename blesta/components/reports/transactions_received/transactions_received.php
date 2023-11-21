<?php
/**
 * Transactions Received report
 *
 * @package blesta
 * @subpackage blesta.components.reports.transactions_received
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class TransactionsReceived implements ReportInterface
{

    /**
     * Load language
     */
    public function __construct()
    {
        Loader::loadComponents($this, ['Record', 'SettingsCollection']);
        Loader::loadModels($this, ['Transactions']);
        Loader::loadHelpers($this, ['DataStructure']);
        $this->ArrayHelper = $this->DataStructure->create('Array');

        // Load the language required by this report
        Language::loadLang('transactions_received', null, dirname(__FILE__) . DS . 'language' . DS);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return Language::_('TransactionsReceived.name', true);
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
        Loader::loadModels($this, ['GatewayManager']);

        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('options', 'default');
        $this->view->setDefaultView('components' . DS . 'reports' . DS . 'transactions_received' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $any = ['' => Language::_('TransactionsReceived.option.any', true)];

        $this->view->set('vars', (object)$vars);
        $this->view->set('statuses', array_merge($any, $this->Transactions->transactionStatusNames()));

        // Set payment types
        $types = $this->Transactions->transactionTypeNames();
        unset($types['other']);
        $this->view->set('payment_types', ($any + $types));

        // Set gateways
        $gateways = $this->GatewayManager->getAll($company_id);
        $merchant = $this->ArrayHelper->numericToKey(
            isset($gateways['merchant']) ? $gateways['merchant'] : [],
            'id',
            'name'
        );
        $nonmerchant = $this->ArrayHelper->numericToKey(
            isset($gateways['nonmerchant']) ? $gateways['nonmerchant'] : [],
            'id',
            'name'
        );
        $this->view->set('gateways', ($any + $merchant + $nonmerchant));

        return $this->view->fetch();
    }

    /**
     * {@inheritdoc}
     */
    public function getKeyInfo()
    {
        $payment_types = $this->Transactions->transactionTypeNames();
        unset($payment_types['other']);
        $income_types = $this->Transactions->getDebitTypes();

        return [
            'id' => ['name' => Language::_('TransactionsReceived.heading.id', true)],
            'transaction_id' => ['name' => Language::_('TransactionsReceived.heading.transaction_id', true)],
            'reference_id' => ['name' => Language::_('TransactionsReceived.heading.reference_id', true)],
            'client_id_code' => ['name' => Language::_('TransactionsReceived.heading.client_id_code', true)],
            'gateway_name' => ['name' => Language::_('TransactionsReceived.heading.gateway_name', true)],
            'type_name' => [
                'name' => Language::_('TransactionsReceived.heading.type_name', true),
                'format' => ['replace'],
                'options' => $payment_types
            ],
            'income_type' => [
                'name' => Language::_('TransactionsReceived.heading.income_type', true),
                'format' => ['replace'],
                'options' => $income_types
            ],
            'amount' => ['name' => Language::_('TransactionsReceived.heading.amount', true)],
            'currency' => ['name' => Language::_('TransactionsReceived.heading.currency', true)],
            'status' => [
                'name' => Language::_('TransactionsReceived.heading.status', true),
                'format' => ['replace'],
                'options' => $this->Transactions->transactionStatusNames()
            ],
            'date_added' => [
                'name' => Language::_('TransactionsReceived.heading.date_added', true),
                'format' => ['date']
            ]
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
        $timezone = array_key_exists('value', $timezone) ? $timezone['value'] : 'UTC';
        $this->Date->setTimezone($timezone, 'UTC');

        // Set filter options
        $format = 'Y-m-d H:i:s';
        $start_date = !empty($vars['start_date'])
            ? $this->Date->format($format, $vars['start_date'] . ' 00:00:00')
            : null;
        $end_date = !empty($vars['end_date'])
            ? $this->Date->format($format, $vars['end_date'] . ' 23:59:59')
            : null;
        $status = !empty($vars['status'])
            ? $vars['status']
            : null;
        $payment_type = !empty($vars['payment_type'])
            ? $vars['payment_type']
            : null;
        $gateway = !empty($vars['gateway'])
            ? $vars['gateway']
            : null;

        $fields = ['transactions.id', 'transactions.transaction_id', 'transactions.reference_id',
            'transactions.amount', 'transactions.currency', 'transactions.status', 'transactions.date_added',
            'IF(transactions.type=?,transaction_types.name,transactions.type)' => 'type_name',
            'IFNULL(transaction_types.type,?)' => 'income_type',
            'gateways.name' => 'gateway_name',
            'REPLACE(clients.id_format, ?, clients.id_value)' => 'client_id_code'
        ];
        $values = [
            'other',
            'debit', // income type
            $replacement_keys['clients']['ID_VALUE_TAG']
        ];

        $this->Record->select($fields, false)
            ->appendValues($values)
            ->from('transactions')
            ->innerJoin('clients', 'clients.id', '=', 'transactions.client_id', false)
            ->on('client_groups.company_id', '=', $company_id)
            ->innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)
            ->on('contacts.contact_type', '=', 'primary')
            ->innerJoin('contacts', 'contacts.client_id', '=', 'clients.id', false)
            ->leftJoin('transaction_types', 'transactions.transaction_type_id', '=', 'transaction_types.id', false)
            ->leftJoin('gateways', 'transactions.gateway_id', '=', 'gateways.id', false);

        // Filter
        if ($start_date) {
            $this->Record->where('transactions.date_added', '>=', $start_date);
        }
        if ($end_date) {
            $this->Record->where('transactions.date_added', '<=', $end_date);
        }
        if ($status) {
            $this->Record->where('transactions.status', '=', $status);
        }
        if ($gateway) {
            $this->Record->where('gateways.id', '=', $gateway);
        }
        if ($payment_type) {
            // Payment type may be the transaction type or a transaction type name
            if ($this->Transactions->validateType($payment_type)) {
                $this->Record->where('transactions.type', '=', $payment_type);
            } else {
                $this->Record->where('transaction_types.name', '=', $payment_type);
            }
        }

        $this->Record->group(['transactions.id'])
            ->order(['transactions.date_added' => 'ASC', 'transactions.id' => 'ASC']);

        return new IteratorIterator($this->Record->getStatement());
    }
}
