<?php

/**
 * Transaction management
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Transactions extends AppModel
{
    /**
     * @var int The decimal precision for rounding float values
     */
    private $float_precision = 4;

    /**
     * Initialize Transactions
     */
    public function __construct()
    {
        parent::__construct();
        Language::loadLang(['transactions']);
    }

    /**
     * Adds a transaction to the system
     *
     * @param array $vars An array of tax info including:
     *
     *  - client_id The client ID this transaction applies to.
     *  - amount The transaction amount
     *  - currency The currency code of this transaction
     *  - type The transaction type (cc, ach, other)
     *  - transaction_type_id The transaction type ID (optional, default NULL)
     *  - account_id The account ID (optional, default NULL)
     *  - gateway_id The gateway ID (optional, default NULL)
     *  - transaction_id The gateway supplied transaction ID (optional, default NULL)
     *  - parent_transaction_id The gateway supplied parent transaction ID (optional, default null)
     *  - reference_id The gateway supplied reference ID (optional, default NULL)
     *  - message The message returned by the gateway (optional, default NULL)
     *  - status The transaction status (optional, default 'approved')
     *  - date_added The date the transaction was received (optional, defaults to current date)
     * @return int The ID of the transaction created, void on error
     */
    public function add(array $vars)
    {
        // Trigger the Transactions.addBefore event
        extract($this->executeAndParseEvent('Transactions.addBefore', ['vars' => $vars]));

        $rules = $this->getTransactionRules($vars);

        if (!isset($vars['date_added'])) {
            $vars['date_added'] = date('c');
        }
        if (isset($vars['gateway_id']) && $vars['gateway_id'] == '') {
            $vars['gateway_id'] = null;
        }

        $this->Input->setRules($rules);

        if ($this->Input->validates($vars)) {
            // Add a transaction
            $fields = [
                'client_id', 'amount', 'currency', 'type', 'transaction_type_id',
                'account_id', 'gateway_id', 'transaction_id', 'parent_transaction_id',
                'reference_id', 'message', 'status', 'date_added'
            ];
            $this->Record->insert('transactions', $vars, $fields);

            $transaction_id = $this->Record->lastInsertId();

            // Trigger the Transactions.addAfter event
            $this->executeAndParseEvent('Transactions.addAfter', compact('transaction_id'));

            return $transaction_id;
        }
    }

    /**
     * Updates a transaction
     *
     * @param int $transaction_id The transaction ID
     * @param array $vars An array of tax info including:
     *
     *  - amount The transaction amount (optional)
     *  - currency The currency code of this transaction (optional)
     *  - type The transaction type (cc, ach, other) (optional)
     *  - transaction_type_id The transaction type ID (optional, default NULL)
     *  - account_id The account ID (optional, default NULL)
     *  - gateway_id The gateway ID (optional, default NULL)
     *  - transaction_id The gateway supplied transaction ID (optional, default NULL)
     *  - parent_transaction_id The gateway supplied parent transaction ID (optional, default null)
     *  - reference_id The reference ID (optional, default NULL)
     *  - message The message returned by the gateway (optional, default NULL)
     *  - status The transaction status (optional, default 'approved')
     *  - date_added The date the transaction was received (optional, defaults to current date)
     * @param int $staff_id The ID of the staff member that made the edit for logging purposes
     * @return int The ID of the transaction created, void on error
     */
    public function edit($transaction_id, array $vars, $staff_id = null)
    {
        // Trigger the Transactions.editBefore event
        extract($this->executeAndParseEvent('Transactions.editBefore', compact('transaction_id', 'staff_id', 'vars')));

        $rules = $this->getTransactionRules($vars);
        unset($rules['client_id']);

        $this->Input->setRules($rules);

        if ($this->Input->validates($vars)) {
            $old_transaction = $this->get($transaction_id);

            // Add a transaction
            $fields = [
                'amount', 'currency', 'type', 'transaction_type_id',
                'account_id', 'gateway_id', 'transaction_id', 'parent_transaction_id',
                'reference_id', 'message', 'status'
            ];
            $this->Record->where('id', '=', $transaction_id)->update('transactions', $vars, $fields);

            // Unapply this transaction from any applied invoice if the status is no longer approved
            if (isset($vars['status']) && $vars['status'] != 'approved') {
                $this->unApply($transaction_id);
            }

            $new_transaction = $this->get($transaction_id);

            // Calculate the changes made to the contact and log those results
            $diff = array_diff_assoc((array) $old_transaction, (array) $new_transaction);

            $fields = [];
            foreach ($diff as $key => $value) {
                $fields[$key]['prev'] = $value;
                $fields[$key]['cur'] = $new_transaction->$key;
            }

            if (!empty($fields)) {
                if (!isset($this->Logs)) {
                    Loader::loadModels($this, ['Logs']);
                }
                $this->Logs->addTransaction(
                    ['transaction_id' => $transaction_id, 'fields' => $fields, 'staff_id' => $staff_id]
                );
            }

            // Trigger the Transactions.editAfter event
            $this->executeAndParseEvent(
                'Transactions.editAfter',
                ['transaction_id' => $transaction_id, 'old_transaction' => $old_transaction]
            );

            return $transaction_id;
        }
    }

    /**
     * Permanently removes a transaction from the system
     *
     * @param int $transaction_id The transaction ID to remove
     */
    public function delete($transaction_id)
    {
        if (is_numeric($transaction_id)) {
            // Unapply the transaction from any invoices
            $this->unApply($transaction_id);

            // Delete the transaction altogether
            $this->Record->from('transactions')
                ->where('id', '=', $transaction_id)
                ->delete();
        }
    }

    /**
     * Retrieves a transaction and any applied amounts
     *
     * @param int $transaction_id The ID of the transaction to fetch (that is,
     *  transactions.id NOT transactions.transaction_id)
     * @return mixed A stdClass object representing the transaction, false if it does not exist
     * @see Transactions::getByTransactionId()
     */
    public function get($transaction_id)
    {
        $this->Record = $this->getTransaction();
        $transaction = $this->Record->where('transactions.id', '=', $transaction_id)->fetch();

        // Fetch amounts credited
        if ($transaction) {
            $transaction->credited_amount = $this->getCreditedAmount($transaction->id);
        }

        return $transaction;
    }

    /**
     * Retrieves a transaction and any applied amounts
     *
     * @param int $transaction_id The ID of the transaction to fetch (that is,
     *  transactions.transaction_id NOT transactions.id)
     * @param int $client_id The ID of the client to fetch a transaction for
     * @param int $gateway_id The ID of the gateway used to process the transaction
     * @return mixed A stdClass object representing the transaction, false if it does not exist
     * @see Transactions::get()
     */
    public function getByTransactionId($transaction_id, $client_id = null, $gateway_id = null)
    {
        $this->Record = $this->getTransaction();

        if ($client_id !== null) {
            $this->Record->where('transactions.client_id', '=', $client_id);
        }

        if ($gateway_id !== null) {
            $this->Record->where('transactions.gateway_id', '=', $gateway_id);
        }

        $transaction = $this->Record->where('transactions.transaction_id', '=', $transaction_id)->fetch();

        // Fetch amounts credited
        if ($transaction) {
            $transaction->credited_amount = $this->getCreditedAmount($transaction->id);
        }

        return $transaction;
    }

    /**
     * Returns a partially built query Record object used to fetch a single transaction
     *
     * @return Record The Record object representing this partial query
     */
    private function getTransaction()
    {
        $fields = ['transactions.id', 'transactions.client_id', 'transactions.amount',
            'transactions.currency', 'transactions.type', 'transactions.transaction_type_id', 'transactions.account_id',
            'transactions.gateway_id', 'gateways.name' => 'gateway_name', 'gateways.type' => 'gateway_type',
            'transactions.reference_id', 'transactions.message', 'transactions.transaction_id',
            'transactions.parent_transaction_id', 'transactions.status', 'transactions.date_added',
            'SUM(IFNULL(transaction_applied.amount,?))' => 'applied_amount',
            'transaction_types.name' => 'type_name'
        ];

        return $this->Record->select($fields)->appendValues([0])->from('transactions')->
            leftJoin('transaction_types', 'transactions.transaction_type_id', '=', 'transaction_types.id', false)->
            leftJoin('transaction_applied', 'transactions.id', '=', 'transaction_applied.transaction_id', false)->
            leftJoin('gateways', 'gateways.id', '=', 'transactions.gateway_id', false)->
            group('transactions.id');
    }

    /**
     * Retrieves a list of all transactions in the system
     *
     * @param int $client_id The ID of the client whose transactions to retrieve (optional)
     * @param int $page The page to return results for (optional, default 1)
     * @return array An array of stdClass objects representing each transaction
     */
    public function getSimpleList($client_id = null, $page = 1)
    {
        return $this->Record->select()
            ->from('transactions')
            ->where('client_id', '=', $client_id)
            ->limit($this->getPerPage(), (max(1, $page) - 1) * $this->getPerPage())
            ->fetchAll();
    }

    /**
     * Retrieves a list of transactions and any applied amounts for the given client
     *
     * @param int $client_id The client ID (optional, default null to get transactions for all clients)
     * @param string $status The status type of the transactions to fetch
     *  (optional, default 'approved') - 'approved', 'declined', 'void', 'error', 'pending', 'returned',
     *  or 'all' for all statuses
     * @param int $page The page to return results for
     * @param array $order_by The sort and order conditions (e.g. array('sort_field'=>"ASC"), optional)
     * @param array $filters A list of parameters to filter by, including:
     *
     *  - payment_type The payment type on which to filter transactions
     *  - transaction_id The (partial) transaction number on which to filter transactions
     *  - reference_id The (partial) transaction reference on which to filter transactions
     *  - applied_status The applied status on which to filter transactions
     *  - start_date Get the transactions from this start date
     *  - end_date Get the transactions to this end date
     *  - start_amount Get the transactions from this start amount
     *  - end_amount Get the transactions to this end amount
     * @return array An array of stdClass objects representing transactions, or false if none exist
     */
    public function getList(
        $client_id = null,
        $status = 'approved',
        $page = 1,
        $order_by = ['date_added' => 'DESC'],
        array $filters = []
    ) {
        $this->Record = $this->getTransactions(array_merge(['client_id' => $client_id, 'status' => $status], $filters));

        $transactions = $this->Record->order($order_by)->
            limit($this->getPerPage(), (max(1, $page) - 1) * $this->getPerPage())->fetchAll();

        // Fetch amounts credited
        foreach ($transactions as &$trans) {
            $trans->credited_amount = $this->getCreditedAmount($trans->id);
        }

        return $transactions;
    }

    /**
     * Returns the total number of transactions for a client, useful
     * in constructing pagination for the getList() method.
     *
     * @param int $client_id The client ID (optional, default null to get transactions for all clients)
     * @param string $status The status type of the transactions to fetch
     *  (optional, default 'approved') - 'approved', 'declined', 'void', 'error', 'pending', 'returned',
     *  or 'all' for all statuses
     * @param array $filters A list of parameters to filter by, including:
     *
     *  - payment_type The payment type on which to filter transactions
     *  - transaction_id The (partial) transaction number on which to filter transactions
     *  - reference_id The (partial) transaction reference on which to filter transactions
     *  - applied_status The applied status on which to filter transactions
     *  - start_date Get the transactions from this start date
     *  - end_date Get the transactions to this end date
     *  - start_amount Get the transactions from this start amount
     *  - end_amount Get the transactions to this end amount
     * @return int The total number of transactions
     * @see Services::getList()
     */
    public function getListCount($client_id = null, $status = 'approved', array $filters = [])
    {
        $this->Record = $this->getTransactions(array_merge(['client_id' => $client_id, 'status' => $status], $filters));

        return $this->Record->numResults();
    }

    /**
     * Search transactions
     *
     * @param string $query The value to search transactions for
     * @param int $page The page number of results to fetch (optional, default 1)
     * @return array An array of transactions that match the search criteria
     */
    public function search($query, $page = 1)
    {
        $this->Record = $this->searchTransactions($query);
        return $this->Record->order(['date_added' => 'DESC'])->
            limit($this->getPerPage(), (max(1, $page) - 1) * $this->getPerPage())->fetchAll();
    }

    /**
     * Return the total number of transactions returned from Transactions::search(), useful
     * in constructing pagination
     *
     * @param string $query The value to search transactions for
     * @see Transactions::search()
     */
    public function getSearchCount($query)
    {
        $this->Record = $this->searchTransactions($query);
        return $this->Record->numResults();
    }

    /**
     * Partially constructs the query for searching transactions
     *
     * @param string $query The value to search transactions for
     * @return Record The partially constructed query Record object
     * @see Transactions::search(), Transactions::getSearchCount()
     */
    private function searchTransactions($query)
    {
        $this->Record = $this->getTransactions(['status' => 'all']);

        $this->Record->open()
            ->like(
                "REPLACE(clients.id_format, '"
                . $this->replacement_keys['clients']['ID_VALUE_TAG']
                . "', clients.id_value)",
                '%' . $query . '%',
                true,
                false
            )
            ->orLike('contacts.company', '%' . $query . '%')
            ->orLike("CONCAT_WS(' ', contacts.first_name, contacts.last_name)", '%' . $query . '%', true, false)
            ->orLike('contacts.address1', '%' . $query . '%')
            ->orLike('transactions.transaction_id', '%' . $query . '%')
            ->orLike('transactions.reference_id', '%' . $query . '%')
            ->close();

        return $this->Record;
    }

    /**
     * Partially constructs the query required by Transactions::getList() and Transactions:getListCount()
     *
     * @param array $filters A list of parameters to filter by, including:
     *
     *  - client_id The client ID (optional, default null to get transactions for all clients)
     *  - payment_type The payment type on which to filter transactions
     *  - transaction_id The (partial) transaction number on which to filter transactions
     *  - reference_id The (partial) transaction reference on which to filter transactions
     *  - applied_status The applied status on which to filter transactions
     *  - start_date Get the transactions from this start date
     *  - end_date Get the transactions to this end date
     *  - minimum_amount Get the transactions from this start amount
     *  - maximum_amount Get the transactions to this end amount
     *  - status The status type of the transactions to fetch
     *      (optional, default 'approved') - 'approved', 'declined', 'void',
     *      'error', 'pending', 'refunded', 'returned' (or 'all' for all
     *      approved/declined/void/error/pending/refunded/returned)
     * @return Record The partially constructed query Record object
     */
    private function getTransactions(array $filters = [])
    {
        if (empty($filters['status'])) {
            $filters['status'] = 'approved';
        }

        $fields = ['transactions.id', 'transactions.client_id', 'transactions.amount',
            'transactions.currency', 'transactions.type', 'transactions.transaction_type_id', 'transactions.account_id',
            'transactions.gateway_id', 'transactions.reference_id', 'transactions.message', 'transactions.transaction_id',
            'transactions.parent_transaction_id', 'transactions.status', 'transactions.date_added',
            'SUM(IFNULL(transaction_applied.amount,?))' => 'applied_amount',
            'transaction_types.name' => 'type_name', 'transaction_types.is_lang' => 'type_is_lang',
            'gateways.name' => 'gateway_name',
            'gateways.type' => 'gateway_type',
            'REPLACE(clients.id_format, ?, clients.id_value)' => 'client_id_code',
            'contacts.first_name' => 'client_first_name',
            'contacts.last_name' => 'client_last_name',
            'contacts.company' => 'client_company'
        ];

        // Filter based on company ID
        $company_id = Configure::get('Blesta.company_id');

        $this->Record->select($fields)
            ->appendValues([0, $this->replacement_keys['clients']['ID_VALUE_TAG']])
            ->from('transactions')
            ->innerJoin('clients', 'clients.id', '=', 'transactions.client_id', false)
            ->innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)
            ->on('contacts.contact_type', '=', 'primary')
            ->innerJoin('contacts', 'contacts.client_id', '=', 'clients.id', false)
            ->leftJoin('transaction_types', 'transactions.transaction_type_id', '=', 'transaction_types.id', false)
            ->leftJoin('gateways', 'transactions.gateway_id', '=', 'gateways.id', false)
            ->leftJoin('transaction_applied', 'transactions.id', '=', 'transaction_applied.transaction_id', false)
            ->where('client_groups.company_id', '=', $company_id);

        // Filter on payment type
        if (!empty($filters['payment_type'])) {
            $types = ['ach', 'cc'];

            if (in_array($filters['payment_type'], $types)) {
                $this->Record->where('transactions.type', '=', $filters['payment_type']);
            } elseif ($filters['payment_type'] == 'other') {
                $this->Record->where('transactions.type', '=', 'other')
                    ->where('transactions.transaction_type_id', '=', null);
            } else {
                $this->Record->where('transactions.type', '=', 'other')
                    ->where('transaction_types.name', '=', $filters['payment_type']);
            }
        }

        // Filter on transaction id
        if (!empty($filters['transaction_id'])) {
            $this->Record->where('transactions.transaction_id', 'LIKE', '%' . $filters['transaction_id'] . '%');
        }

        // Filter on reference id
        if (!empty($filters['reference_id'])) {
            $this->Record->where('transactions.reference_id', 'LIKE', '%' . $filters['reference_id'] . '%');
        }

        // Filter on date
        if (!empty($filters['start_date'])) {
            $this->Record->where(
                'transactions.date_added',
                '>=',
                $this->dateToUtc($filters['start_date'] . ' 00:00:00')
            );
        }

        if (!empty($filters['end_date'])) {
            $this->Record->where(
                'transactions.date_added',
                '<=',
                $this->dateToUtc($filters['end_date'] . ' 23:59:59')
            );
        }

        // Filter on amount
        if (!empty($filters['minimum_amount'])) {
            $this->Record->where(
                'transactions.amount',
                '>=',
                $filters['minimum_amount']
            );
        }

        if (!empty($filters['maximum_amount'])) {
            $this->Record->where(
                'transactions.amount',
                '<=',
                $filters['maximum_amount']
            );
        }

        // Filter on applied status
        if (!empty($filters['applied_status'])) {
            switch ($filters['applied_status']) {
                case 'fully_applied':
                    $this->Record->having('transactions.amount', '=', 'SUM(transaction_applied.amount)', false);
                    break;
                case 'partially_applied':
                    $this->Record->having('transactions.amount', '>', 'SUM(transaction_applied.amount)', false);
                    break;
                case 'not_applied':
                    $this->Record->where('transaction_applied.amount', '=', null);
                    break;
            }
        }

        // Filter on status
        if ($filters['status'] != 'all') {
            $this->Record->where('transactions.status', '=', $filters['status']);
        }

        $this->Record->group('transactions.id');

        // Get transactions for a specific client
        if (!empty($filters['client_id'])) {
            $this->Record->where('transactions.client_id', '=', $filters['client_id']);
        }

        return $this->Record;
    }

    /**
     * Returns all invoices that have been applied to this transaction. Must supply
     * either a transaction ID, invoice ID or both.
     *
     * @param int $transaction_id The ID of the transaction to fetch
     * @param int $invoice_id The ID of the invoice to filter applied amounts on
     * @return array An array of stdClass objects representing applied amounts to invoices
     */
    public function getApplied($transaction_id = null, $invoice_id = null)
    {

        // Must supply either a transaction ID, invoice ID or both
        if ($transaction_id === null && $invoice_id === null) {
            return [];
        }

        $fields = ['transactions.id', 'transactions.amount', 'transactions.currency', 'transactions.date_added',
            'transactions.type', 'transactions.transaction_type_id',
            'transaction_applied.transaction_id', 'transaction_applied.invoice_id',
            'transaction_applied.amount' => 'applied_amount', 'transaction_applied.date' => 'applied_date',
            'transaction_types.name' => 'type_name',
            'REPLACE(invoices.id_format, ?, invoices.id_value)' => 'invoice_id_code',
            'transactions.client_id', 'transactions.account_id', 'transactions.gateway_id',
            'gateways.name' => 'gateway_name', 'gateways.type' => 'gateway_type',
            'transactions.reference_id', 'transactions.message', 'transactions.status',
            // Set the transaction ID as the transaction number since transaction_id is already taken
            'transactions.transaction_id' => 'transaction_number', 'transactions.parent_transaction_id'
        ];

        $this->Record->select($fields)->appendValues([$this->replacement_keys['invoices']['ID_VALUE_TAG']])->
            from('transactions')->
            innerJoin('transaction_applied', 'transactions.id', '=', 'transaction_applied.transaction_id', false)->
            innerJoin('invoices', 'transaction_applied.invoice_id', '=', 'invoices.id', false)->
            leftJoin('transaction_types', 'transactions.transaction_type_id', '=', 'transaction_types.id', false)->
            leftJoin('gateways', 'gateways.id', '=', 'transactions.gateway_id', false)->
            order(['transaction_applied.date' => 'DESC']);

        if ($transaction_id !== null) {
            $this->Record->where('transactions.id', '=', $transaction_id);
        }

        if ($invoice_id !== null) {
            $this->Record->where('transaction_applied.invoice_id', '=', $invoice_id);
        }

        return $this->Record->fetchAll();
    }

    /**
     * Returns the amount of payment for the given transaction ID that is currently
     * available as a credit
     *
     * @param int $transaction_id The ID of the transaction to fetch a credit value for
     * @return float The amount of the transaction that is currently available as a credit
     */
    public function getCreditedAmount($transaction_id)
    {
        $fields = ['transactions.amount', 'transactions.currency',
            'SUM(IFNULL(transaction_applied.amount,?))' => 'applied_amount'
        ];
        $credits = $this->Record->select($fields)
            ->appendValues([0])
            ->from('transactions')
            ->leftJoin('transaction_applied', 'transaction_applied.transaction_id', '=', 'transactions.id', false)
            ->where('transactions.id', '=', $transaction_id)
            ->where('transactions.status', '=', 'approved')
            ->group('transactions.id')
            ->fetch();

        if ($credits) {
            return max(
                0,
                round(
                    $credits->amount - $credits->applied_amount,
                    $this->getCurrencyPrecision($credits->currency, Configure::get('Blesta.company_id'))
                )
            );
        }
        return 0;
    }

    /**
     * Returns the amount of payment for the given client ID that is currently
     * available as a credit for each currency
     *
     * @param int $client_id The ID of the client to fetch a credit value for
     * @param string $currency The ISO 4217 3-character currency code (optional)
     * @return array A list of credits by currency containing:
     *
     *  - transaction_id The transaction ID that the credit belongs to
     *  - credit The total credit for this transaction
     */
    public function getCredits($client_id, $currency = null)
    {
        $fields = [
            'transactions.id', 'transactions.currency', 'transactions.amount',
            'SUM(IFNULL(transaction_applied.amount,?))' => 'applied_amount'
        ];
        $this->Record->select($fields)
            ->appendValues([0])
            ->from('transactions')
            ->leftJoin('transaction_applied', 'transaction_applied.transaction_id', '=', 'transactions.id', false)
            ->where('transactions.client_id', '=', $client_id)
            ->where('transactions.status', '=', 'approved');

        // Filter on currency
        if ($currency) {
            $this->Record->where('transactions.currency', '=', $currency);
        }

        $transactions = $this->Record->group('transactions.id')->
            having('applied_amount', '<', 'transactions.amount', false)->
            fetchAll();

        $total_credits = [];
        $precisions = [];
        foreach ($transactions as $transaction) {
            // Create a map of currency precisions
            if (!array_key_exists($transaction->currency, (array)$precisions)) {
                $precisions[$transaction->currency] = $this->getCurrencyPrecision(
                    $transaction->currency,
                    Configure::get('Blesta.company_id')
                );
            }

            // Round the amount to the currency's precision
            $credit_amount = round(
                $transaction->amount - $transaction->applied_amount,
                $precisions[$transaction->currency]
            );

            if ($credit_amount > 0) {
                if (!isset($total_credits[$transaction->currency])) {
                    $total_credits[$transaction->currency] = [];
                }
                $total_credits[$transaction->currency][] = [
                    'transaction_id' => $transaction->id,
                    'credit' => $credit_amount
                ];
            }
        }
        return $total_credits;
    }

    /**
     * Retrieves the total credit amount available to the client in the given currency
     *
     * @param int $client_id The ID of the client to fetch a credit value for
     * @param string $currency The ISO 4217 3-character currency code
     * @return float The total credit available to the client in the given currency
     */
    public function getTotalCredit($client_id, $currency)
    {
        $credits = $this->getCredits($client_id, $currency);

        $total = 0;
        foreach ($credits as $currency_code => $amounts) {
            foreach ($amounts as $amount) {
                $total += $amount['credit'];
            }
        }

        return $total;
    }

    /**
     * Retrieves the decimal precision for the given currency
     *
     * @param string $currency The ISO 4217 3-character currency code
     * @param int $company_id The ID of the company
     * @return int The currency decimal precision
     */
    private function getCurrencyPrecision($currency, $company_id)
    {
        // Determine the currency precision to use; default to 4, the maximum precision
        if (!isset($this->Currencies)) {
            Loader::loadModels($this, ['Currencies']);
        }

        $currency = $this->Currencies->get($currency, $company_id);
        return ($currency ? $currency->precision : $this->float_precision);
    }

    /**
     * Retrieves the number of transactions given a transaction status type for the given client
     *
     * @param int $client_id The client ID (optional, default null to get transactions for all clients)
     * @param string $status The transaction status type (optional, default 'approved') - 'all' for all statuses
     * @param array $filters A list of parameters to filter by, including:
     *
     *  - payment_type The payment type on which to filter transactions
     *  - transaction_id The (partial) transaction number on which to filter transactions
     *  - reference_id The (partial) transaction reference on which to filter transactions
     *  - applied_status The applied status on which to filter transactions
     *  - start_date Get the transactions from this start date
     *  - end_date Get the transactions to this end date
     *  - start_amount Get the transactions from this start amount
     *  - end_amount Get the transactions to this end amount
     * @return int The number of transactions of type $status for $client_id
     */
    public function getStatusCount($client_id = null, $status = 'approved', array $filters = [])
    {
        return $this->getTransactions(array_merge($filters, ['client_id' => $client_id, 'status' => $status]))
            ->numResults();
    }

    /**
     * Applies a transaction to a list of invoices. Each invoice must be in the transaction's currency to be applied
     *
     * @param int transaction_id The transaction ID
     * @param array $vars An array of transaction info including:
     *
     *  - date The date in local time (Y-m-d H:i:s format) the transaction
     *      was applied (optional, default to current date/time)
     *  - amounts A numerically indexed array of amounts to apply including:
     *      - invoice_id The invoice ID
     *      - amount The amount to apply to this invoice (optional, default 0.00)
     */
    public function apply($transaction_id, array $vars)
    {
        extract($this->executeAndParseEvent(
            'Transactions.applyBefore',
            ['transaction_id' => $transaction_id, 'vars' => $vars]
        ));

        if (!isset($this->Invoices)) {
            Loader::loadModels($this, ['Invoices']);
        }

        $vars['transaction_id'] = $transaction_id;

        if (!isset($vars['date'])) {
            $vars['date'] = date('c');
        }

        // Attempt to apply a transaction to a list of invoices with the
        // given amounts for each
        if ($this->verifyApply($vars)) {
            // Add an applied transaction
            $fields = ['transaction_id', 'invoice_id', 'amount', 'date'];

            for ($i = 0, $num_amounts = count($vars['amounts']); $i < $num_amounts; $i++) {
                // Set fields
                $vars['amounts'][$i]['transaction_id'] = $transaction_id;
                $vars['amounts'][$i]['date'] = $vars['date'];

                if (!array_key_exists('amount', (array)$vars['amounts'][$i])) {
                    $vars['amounts'][$i]['amount'] = 0;
                }

                // Add applied amount or update existing applied amount
                if ($vars['amounts'][$i]['amount'] > 0) {
                    $this->Record->duplicate(
                        'amount',
                        '=',
                        "amount + '" . ((float) $vars['amounts'][$i]['amount']) . "'",
                        false,
                        false
                    )
                        ->insert('transaction_applied', $vars['amounts'][$i], $fields);
                }

                // Mark each invoice as "paid" if paid in full
                $this->Invoices->setClosed($vars['amounts'][$i]['invoice_id']);
            }

            $this->executeAndParseEvent('Transactions.applyAfter', ['vars' => $vars]);
        }
    }

    /**
     * Applies available client credits in the given currency to all open invoices, starting from the oldest.
     * If specific amounts are specified, credits will only be applied to the invoices given.
     *
     * @param int $client_id The ID of the client whose invoices to apply from credits
     * @param string $currency The ISO 4217 3-character currency code. Must be
     *  set if $amounts are specified (optional, null will apply from all currencies; default null)
     * @param array $amounts A numerically-indexed array of specific amounts to apply to given invoices:
     *
     *  - invoice_id The invoice ID
     *  - amount The amount to apply to this invoice (optional, default 0.00)
     * @return mixed Void, or an array indexed by a credit's transaction ID,
     *  containing the amounts that were actually applied, including:
     *
     *  - invoice_id The invoice ID
     *  - amount The amount that was applied to this invoice
     */
    public function applyFromCredits($client_id, $currency = null, array $amounts = [])
    {
        if (!isset($this->Invoices)) {
            Loader::loadModels($this, ['Invoices']);
        }

        // Fetch available credits
        $credits = $this->getCredits($client_id, $currency);
        if (empty($credits)) {
            return;
        }

        // Apply credits to all open invoices
        if (empty($amounts)) {
            // Fetch all invoices we can apply credits to
            $invoices = $this->Invoices->getAll($client_id, 'open', ['date_due' => 'ASC'], $currency);
            if (empty($invoices)) {
                return;
            }
        } else {
            // Currency must be set since we are assuming all the $amounts belong to the same currency
            if (empty($currency)) {
                $this->Input->setErrors([
                    'currency' => [
                        'missing' => Language::_('Transactions.!error.currency.missing', true)
                    ]
                ]);
                return;
            }

            // Fetch the associated invoices to apply credits to
            $invoices = [];
            foreach ($amounts as $amount) {
                $invoice = $this->Invoices->get((isset($amount['invoice_id']) ? $amount['invoice_id'] : null));
                if (!$invoice || $invoice->currency != $currency) {
                    $this->Input->setErrors([
                        'currency' => [
                            'invalid' => Language::_('Transactions.!error.currency.mismatch', true)
                        ]
                    ]);
                    return;
                }
                $invoices[] = $invoice;
            }
        }

        // Fetch the amounts to apply to each invoice
        $apply_amounts = $this->getCreditApplyAmounts($credits, $invoices, $amounts);

        // Apply all credits
        $last_transaction_id = null;
        $errors = null;
        foreach ($apply_amounts as $transaction_id => $trans_amounts) {
            $this->apply($transaction_id, ['amounts' => $trans_amounts]);

            if (($errors = $this->errors())) {
                break;
            }
            $last_transaction_id = $transaction_id;
        }

        // Unapply the transaction if an error occurred
        if ($errors && $last_transaction_id) {
            foreach ($apply_amounts as $transaction_id => $trans_amounts) {
                $invoice_ids = array_map(function ($value) { return $value['invoice_id']; }, $trans_amounts);
                $this->unapply($transaction_id, $invoice_ids);

                if ($last_transaction_id == $transaction_id) {
                    break;
                }
            }
        }

        return $apply_amounts;
    }

    /**
     * Creates a list of credits to be applied to invoices for each transaction
     *
     * @param array $credits A list of client credits for all currencies, containing:
     *
     *  - transaction_id The transaction ID
     *  - credit The credit available for this transaction
     * @param array $invoices An array of stdClass objects representing client invoices
     * @param array $amounts A list of specific amounts to be applied per invoice (optional)
     *
     *  - invoice_id The ID of the invoice to apply credits to
     *  - amount The amount of credit to apply to the invoice
     * @return array A list of credits set to be applied, keyed by transaction
     *  ID, with each containing a list of amounts:
     *
     *  - invoice_id The invoice ID to apply the credit to
     *  - amount The amount to apply to the invoice_id for this particular transaction
     */
    private function getCreditApplyAmounts(array $credits = [], array $invoices = [], array $amounts = [])
    {
        if (!isset($this->CurrencyFormat)) {
            Loader::loadHelpers($this, ['CurrencyFormat']);
        }
        $apply_amounts = [];

        // Group each invoice by its currency
        $currencies = [];
        foreach ($invoices as $invoice) {
            if (!isset($currencies[$invoice->currency])) {
                $currencies[$invoice->currency] = [];
            }
            $currencies[$invoice->currency][] = $invoice;
        }
        unset($invoices, $invoice);


        // Set specific amounts to apply to each invoice, if any are given. Assumed to be matching currency
        $amounts_to_apply = [];
        foreach ($amounts as $amount) {
            $amounts_to_apply[(isset($amount['invoice_id']) ? $amount['invoice_id'] : null)] = (isset($amount['amount']) ? $amount['amount'] : 0);
        }

        // Set all apply amounts for each invoice and credit
        foreach ($currencies as $currency_code => $invoices) {
            // No credits available in this currency to apply to the invoice
            if (empty($credits[$currency_code])) {
                continue;
            }

            foreach ($invoices as $invoice) {
                $invoice_credit = 0;

                // Set specific amounts to apply to this invoice, if given, from this credit
                $apply_amt = null;
                if (isset($amounts_to_apply[$invoice->id])) {
                    $apply_amt = $amounts_to_apply[$invoice->id];
                }

                foreach ($credits[$currency_code] as &$credit) {
                    // This credit has been used up
                    if ($credit['credit'] <= 0) {
                        unset($credit);
                        continue;
                    }

                    // Set invoice credit to be applied (partially or in full) if specified
                    $credit_amount = $apply_amt === null || $apply_amt > $credit['credit']
                        ? $credit['credit']
                        : $apply_amt;
                    if ($credit_amount >= ($invoice->due - $invoice_credit)) {
                        $credit_amount = $invoice->due - $invoice_credit;
                    }

                    // Set apply amount
                    if (!isset($apply_amounts[$credit['transaction_id']])) {
                        $apply_amounts[$credit['transaction_id']] = [];
                    }
                    $apply_amounts[$credit['transaction_id']][] = [
                        'invoice_id' => $invoice->id,
                        'amount' => $this->CurrencyFormat->cast($credit_amount, $currency_code)
                    ];

                    // Decrease credit available
                    $credit['credit'] -= $credit_amount;
                    $invoice_credit += $credit_amount;
                    if ($apply_amt !== null) {
                        $apply_amt -= $credit_amount;
                    }

                    // Credit covers entire invoice, or the total we're applying to it, so move on
                    if ($invoice_credit >= $invoice->due || ($apply_amt !== null && $apply_amt <= 0)) {
                        // Don't re-visit this invoice
                        unset($invoice);
                        break;
                    }
                }
            }
        }

        return $apply_amounts;
    }

    /**
     * Verifies that transaction can be applied to a list of invoices
     *
     * @param array $vars An array of transaction info including:
     *
     *  - transaction_id The transaction ID (only evaluated if $validate_trans_id is true)
     *  - date The date in UTC time (Y-m-d H:i:s format) the transaction was
     *      applied (optional, default to current date/time)
     *  - amounts A numerically indexed array of amounts to apply including:
     *      - invoice_id The invoice ID
     *      - amount The amount to apply to this invoice (optional, default 0.00)
     * @param bool $validate_trans_id True to validate the transaction ID in $vars, false otherwise
     * @param float $total The total amount of the transaction used to ensure
     *  that the sum of the apply amounts does not exceed the total transaction
     *  amount. Only used if $validate_trans_id is false (optional)
     * @return bool True if the transaction can be applied, false otherwise (sets Input errors on failure)
     */
    public function verifyApply(array &$vars, $validate_trans_id = true, $total = null)
    {
        // Determine the transaction total
        if ($validate_trans_id) {
            $total = null;

            // The total remaining transaction amount is the maximum that amounts can apply
            if (isset($vars['transaction_id']) && ($transaction = $this->get((int) $vars['transaction_id']))) {
                $total = max(0, ($transaction->amount - $transaction->applied_amount));
            }
        }

        $rules = [
            'transaction_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'transactions'],
                    'message' => $this->_('Transactions.!error.transaction_id.exists')
                ],
                'currency_matches' => [
                    'rule' => [[$this, 'validateCurrencyAmounts'], (array) (isset($vars['amounts']) ? $vars['amounts'] : [])],
                    'message' => $this->_('Transactions.!error.transaction_id.currency_matches')
                ]
            ],
            'amounts[][invoice_id]' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'invoices'],
                    'message' => $this->_('Transactions.!error.invoice_id.exists')
                ]
            ],
            'amounts[][amount]' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'is_numeric',
                    'message' => $this->_('Transactions.!error.amount.format')
                ]
            ],
            'amounts' => [
                'overage' => [
                    'rule' => [[$this, 'validateApplyAmounts'], $total],
                    'message' => $this->_('Transactions.!error.amounts.overage')
                ],
                'positive' => [
                    'rule' => [[$this, 'validatePositiveAmounts']],
                    'message' => $this->_('Transactions.!error.amounts.positive')
                ]
            ],
            'date' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'isDate',
                    'post_format' => [[$this, 'dateToUtc']],
                    'message' => $this->_('Transactions.!error.date.format')
                ]
            ]
        ];

        if (!$validate_trans_id) {
            unset($rules['transaction_id']);
        }

        $this->Input->setRules($rules);

        return $this->Input->validates($vars);
    }

    /**
     * Unapplies a transactions from one or more invoices.
     *
     * @param int $transaction_id The ID of the transaction to unapply
     * @param array $invoices A numerically indexed array of invoice IDs to
     *  unapply this transaction from, or null to unapply this transaction from any and all invoices
     */
    public function unApply($transaction_id, array $invoices = null)
    {
        if (!isset($this->Invoices)) {
            Loader::loadModels($this, ['Invoices']);
        }

        $applied_invoices = $this->getApplied($transaction_id);

        $this->Record->from('transactions')->from('transaction_applied')->
            where('transactions.id', '=', $transaction_id)->
            where('transaction_applied.transaction_id', '=', 'transactions.id', false);
        // If a list of invoice was given, unapply this transaction from those invoices only
        if (!empty($invoices)) {
            $this->Record->where('transaction_applied.invoice_id', 'in', $invoices);
        }
        $this->Record->delete(['transaction_applied.*']);

        // remove "paid" status for each invoice if no longer paid in full
        if (!empty($invoices)) {
            for ($i = 0, $total = count($invoices); $i < $total; $i++) {
                $this->Invoices->setClosed($invoices[$i]);
            }
        } else {
            if ($applied_invoices) {
                for ($i = 0, $total = count($applied_invoices); $i < $total; $i++) {
                    $this->Invoices->setClosed($applied_invoices[$i]->invoice_id);
                }
            }
        }
    }

    /**
     * Retrieves a list of all transaction types
     *
     * @return mixed An array of stdClass objects representing transaction types, or false if none exist
     */
    public function getTypes()
    {
        $transaction_types = $this->Record->select()->from('transaction_types')->fetchAll();

        // Set a real_name to the language definition, if applicable
        foreach ($transaction_types as &$payment_type) {
            if ($payment_type->is_lang == '1') {
                $payment_type->real_name = $this->_('_PaymentTypes.' . $payment_type->name, true);
            } else {
                $payment_type->real_name = $payment_type->name;
            }
        }

        return $transaction_types;
    }

    /**
     * Retrieves a single payment type
     *
     * @param int $type_id The payment type ID
     * @return mixed An array of stdClass objects representing a payment type, or false if one does not exist
     */
    public function getType($type_id)
    {
        $transaction_type = $this->Record->select()->from('transaction_types')->where('id', '=', $type_id)->fetch();

        // Set a real_name to the language definition, if applicable
        if ($transaction_type) {
            if ($transaction_type->is_lang == '1') {
                $transaction_type->real_name = $this->_('_PaymentTypes.' . $transaction_type->name, true);
            } else {
                $transaction_type->real_name = $transaction_type->name;
            }
        }

        return $transaction_type;
    }

    /**
     * Retrieves a set of key/value pairs representing transaction debit types and language
     *
     * @return array An array of key/value pairs representing transaction debit types and their names
     */
    public function getDebitTypes()
    {
        return [
            'debit' => $this->_('Transactions.debit_types.debit'),
            'credit' => $this->_('Transactions.debit_types.credit')
        ];
    }

    /**
     * Retrieves all transaction types and their name in key=>value pairs
     *
     * @return array An array of key=>value pairs representing transaction types and their names
     */
    public function transactionTypeNames()
    {
        // Standard types
        $names = [
            'ach' => $this->_('Transactions.types.ach'),
            'cc' => $this->_('Transactions.types.cc'),
            'other' => $this->_('Transactions.types.other')
        ];

        // Custom types
        $types = $this->getTypes();
        foreach ($types as $type) {
            $names[$type->name] = $type->is_lang == '1' ? $this->_('_PaymentTypes.' . $type->name) : $type->name;
        }

        return $names;
    }

    /**
     * Retrieves all transaction status values and their name in key/value pairs
     *
     * @return array An array of key/value pairs representing transaction status values
     */
    public function transactionStatusNames()
    {
        return [
            'approved' => $this->_('Transactions.status.approved'),
            'declined' => $this->_('Transactions.status.declined'),
            'void' => $this->_('Transactions.status.void'),
            'error' => $this->_('Transactions.status.error'),
            'pending' => $this->_('Transactions.status.pending'),
            'refunded' => $this->_('Transactions.status.refunded'),
            'returned' => $this->_('Transactions.status.returned')
        ];
    }

    /**
     * Adds a transaction type
     *
     * @param array $vars An array of transaction types including:
     *
     *  - name The transaction type name
     *  - type The transaction debit type ('debit' or 'credit')
     *  - is_lang Whether or not the 'name' parameter is a language definition 1 - yes, 0 - no (optional, default 0)
     * @return int The transaction type ID created, or void on error
     */
    public function addType(array $vars)
    {
        $this->Input->setRules($this->getTypeRules());

        if ($this->Input->validates($vars)) {
            //Add a transaction type
            $fields = ['name', 'type', 'is_lang'];
            $this->Record->insert('transaction_types', $vars, $fields);

            return $this->Record->lastInsertId();
        }
    }

    /**
     * Updates a transaction type
     *
     * @param int $type_id The type ID to update
     * @param array $vars An array of transaction types including:
     *
     *  - name The transaction type name
     *  - type The transaction debit type ('debit' or 'credit')
     *  - is_lang Whether or not the 'name' parameter is a language definition 1 - yes, 0 - no (optional, default 0)
     */
    public function editType($type_id, array $vars)
    {
        $rules = $this->getTypeRules();
        $rules['type_id'] = [
            'exists' => [
                'rule' => [[$this, 'validateExists'], 'id', 'transaction_types'],
                'message' => $this->_('Transactions.!error.type_id.exists')
            ]
        ];

        $this->Input->setRules($rules);

        $vars['type_id'] = $type_id;

        if ($this->Input->validates($vars)) {
            // Update a transaction type
            $fields = ['name', 'type', 'is_lang'];
            $this->Record->where('id', '=', $type_id)->update('transaction_types', $vars, $fields);
        }
    }

    /**
     * Delete a transaction type and update all affected transactions, setting their type to null
     *
     * @param int $type_id The ID for this transaction type
     */
    public function deleteType($type_id)
    {

        // Update all transactions with this now defunct transaction type ID to null
        $this->Record->where('transaction_type_id', '=', $type_id)->
            update('transactions', ['transaction_type_id' => null]);

        // Finally delete the transaction type
        $this->Record->from('transaction_types')->where('id', '=', $type_id)->delete();
    }

    /**
     * Validates a transaction's 'type' field
     *
     * @param string $type The type to check
     * @return bool True if the type is validated, false otherwise
     */
    public function validateType($type)
    {
        switch ($type) {
            case 'cc':
            case 'ach':
            case 'other':
                return true;
        }
        return false;
    }

    /**
     * Validates a transaction's 'status' field
     *
     * @param string $status The status to check
     * @return bool True if the status is validated, false otherwise
     */
    public function validateStatus($status)
    {
        switch ($status) {
            case 'approved':
            case 'declined':
            case 'void':
            case 'error':
            case 'pending':
            case 'refunded':
            case 'returned':
                return true;
        }
        return false;
    }

    /**
     * Validates whether the given amounts can be applied to the given invoices, or if the amounts
     * would exceed the amount due on the invoices. Also ensures the invoices can have amounts applied to them.
     *
     * @param array $amounts An array of apply amounts including:
     *
     *  - invoice_id The invoice ID
     *  - amount The amount to apply to this invoice (optional, default 0.00)
     * @param float $total The total amount of the transaction used to ensure
     *  that the sum of the apply amounts does not exceed the total transaction amount
     * @return bool True if the amounts given can can be applied to the
     *  invoices given, false if it exceeds the amount due or the invoice can not receive payments
     */
    public function validateApplyAmounts(array $amounts, $total = null)
    {
        if (!isset($this->Invoices)) {
            Loader::loadModels($this, ['Invoices']);
        }

        $apply_total = 0;
        $precision = $this->float_precision;
        foreach ($amounts as $apply) {
            if (!isset($apply['amount'])) {
                continue;
            }

            $apply_total += $apply['amount'];
            $invoice = $this->Invoices->get($apply['invoice_id']);

            // Override the precision with the currency's precision (all amounts should be in the same currency)
            $precision = $this->getCurrencyPrecision($invoice->currency, Configure::get('Blesta.company_id'));

            // Round the amounts to the currency's precision
            $pay_total = round($this->Invoices->getPaid($invoice->id) + $apply['amount'], $precision);
            $invoice->total = round($invoice->total, $precision);

            $active_types = ['active', 'proforma'];
            if (!$invoice || !in_array($invoice->status, $active_types) || $pay_total > $invoice->total) {
                return false;
            }
        }

        // Ensure that the available amount of the transaction does not exceed the apply amount (if total given)
        if ($total !== null && round($total, $precision) < round($apply_total, $precision)) {
            return false;
        }

        return true;
    }

    /**
     * Validates whether the invoice amounts being applied are in the transaction's currency
     *
     * @param int $transaction_id The ID of the transaction
     * @param array $amounts An array of apply amounts including:
     *
     *  - invoice_id The invoice ID
     *  - amount The amount to apply to this invoice (optional)
     * @return bool True if each invoice is in the transaction's currency, or false otherwise
     */
    public function validateCurrencyAmounts($transaction_id, array $amounts)
    {
        if (!isset($this->Invoices)) {
            Loader::loadModels($this, ['Invoices']);
        }

        if (($transaction = $this->get($transaction_id))) {
            foreach ($amounts as $apply) {
                if (!isset($apply['invoice_id'])) {
                    continue;
                }

                // Check the currencies match
                $invoice = $this->Invoices->get($apply['invoice_id']);
                if ($invoice && strtolower($invoice->currency) != strtolower($transaction->currency)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Validates whether each of the given amounts is greater than or equal to zero
     *
     * @param array $amounts An array of apply amounts including:
     *
     *  - invoice_id The invoice ID
     *  - amount The amount to apply to this invoice (optional, default 0.00)
     * @return bool True if all amounts are greater than or equal to zero, false otherwise
     */
    public function validatePositiveAmounts(array $amounts)
    {
        if (!isset($this->Invoices)) {
            Loader::loadModels($this, ['Invoices']);
        }

        foreach ($amounts as $apply) {
            if (!isset($apply['amount'])) {
                continue;
            }

            if ($apply['amount'] < 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns the rule set for adding/editing transactions
     *
     * @param array $vars An array of input data for the rules
     * @return array Transaction rules
     */
    private function getTransactionRules(array $vars = [])
    {
        $currency = (isset($vars['currency']) ? $vars['currency'] : null);

        $rules = [
            'client_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'clients'],
                    'message' => $this->_('Transactions.!error.client_id.exists')
                ]
            ],
            'amount' => [
                'format' => [
                    'if_set' => true,
                    'pre_format' => function ($amount) use ($currency) {
                        // Ensure the amount is converted to decimal based on the currency,
                        // if available
                        if (!empty($currency) && isset($amount)) {
                            $amount = $this->currencyToDecimal($amount, $currency, 4);
                        }

                        return $amount;
                    },
                    'rule' => 'is_numeric',
                    'message' => $this->_('Transactions.!error.amount.format')
                ]
            ],
            'currency' => [
                'length' => [
                    'if_set' => true,
                    'rule' => ['matches', '/^(.*){3}$/'],
                    'message' => $this->_('Transactions.!error.currency.length')
                ]
            ],
            'type' => [
                'format' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateType']],
                    'message' => $this->_('Transactions.!error.type.format')
                ]
            ],
            'transaction_type_id' => [
                'exists' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateExists'], 'id', 'transaction_types'],
                    'message' => $this->_('Transactions.!error.transaction_type_id.exists')
                ]
            ],
            'gateway_id' => [
                'exists' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateExists'], 'id', 'gateways', false],
                    'message' => $this->_('Transactions.!error.gateway_id.exists')
                ]
            ],
            'transaction_id' => [
                'length' => [
                    'if_set' => true,
                    'rule' => ['maxLength', 128],
                    'message' => $this->_('Transactions.!error.transaction_id.length')
                ]
            ],
            'parent_transaction_id' => [
                'length' => [
                    'if_set' => true,
                    'rule' => ['maxLength', 128],
                    'message' => $this->_('Transactions.!error.parent_transaction_id.length')
                ]
            ],
            'reference_id' => [
                'length' => [
                    'if_set' => true,
                    'rule' => ['maxLength', 128],
                    'message' => $this->_('Transactions.!error.reference_id.length')
                ]
            ],
            'status' => [
                'format' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateStatus']],
                    'message' => $this->_('Transactions.!error.status.format')
                ]
            ],
            'date_added' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['isDate'],
                    'message' => $this->_('Transactions.!error.date_added.format'),
                    'pre_format' => [[$this, 'dateToUtc'], 'Y-m-d H:i:s', true]
                ]
            ],
            'message' => [
                'length' => [
                    'if_set' => true,
                    'rule' => ['maxLength', 255],
                    'message' => $this->_('Transactions.!error.message.length')
                ]
            ]
        ];
        return $rules;
    }

    /**
     * Returns the rule set for adding/editing transaction types
     *
     * @return array Transaction type rules
     */
    private function getTypeRules()
    {
        $rules = [
            'name' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('Transactions.!error.name.empty')
                ],
                'length' => [
                    'rule' => ['maxLength', 32],
                    'message' => $this->_('Transactions.!error.name.length')
                ]
            ],
            'type' => [
                'valid' => [
                    'rule' => ['in_array', array_keys($this->getDebitTypes())],
                    'message' => $this->_('Transactions.!error.type.valid')
                ]
            ],
            'is_lang' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'is_numeric',
                    'message' => $this->_('Transactions.!error.is_lang.format')
                ],
                'length' => [
                    'if_set' => true,
                    'rule' => ['maxLength', 1],
                    'message' => $this->_('Transactions.!error.is_lang.length')
                ]
            ]
        ];
        return $rules;
    }
}
