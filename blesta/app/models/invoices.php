<?php

use Blesta\Pricing\PricingFactory;
use Blesta\Pricing\Collection\ItemPriceCollection;

/**
 * Invoice management
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Invoices extends AppModel
{
    /**
     * Initialize Invoices
     */
    public function __construct()
    {
        parent::__construct();
        Language::loadLang(['invoices']);
    }

    /**
     * Creates a new invoice using the given data
     *
     * @param array $vars An array of invoice data including:
     *
     *  - client_id The client ID the invoice belongs to
     *  - date_billed The date the invoice goes into effect
     *  - date_due The date the invoice is due
     *  - date_closed The date the invoice was closed
     *  - date_autodebit The date the invoice should be autodebited
     *  - status 'active','draft','proforma', or 'void'
     *  - currency The ISO 4217 3-character currency code of the invoice
     *  - note_public Notes visible to the client
     *  - note_private Notes visible only to staff members
     *  - lines A numerically indexed array of line item info including:
     *      - service_id The service ID attached to this line item (optional)
     *      - description The line item description
     *      - qty The quantity for this line item (min. 1)
     *      - amount The unit cost (cost per quantity) for this line item
     *      - tax Whether or not to tax the line item
     *  - term The term for the recurring invoice as an integer 1-65535,
     *      if blank will not be considered for a recurring invoice
     *  - period The period for the recurring invoice ('day', 'week', 'month', 'year')
     *  - duration The duration of the recurring invoice ('indefinitely'
     *      for forever or 'times' for a set number of times)
     *  - duration_time The number of times an invoice should recur
     *  - recur_date_billed The date the next invoice will be created
     *  - delivery A numerically indexed array of delivery methods
     * @return int The invoice ID, void on error
     */
    public function add(array $vars)
    {
        // Trigger the Invoices.addBefore event
        extract($this->executeAndParseEvent('Invoices.addBefore', ['vars' => $vars]));

        // Fetch client settings on invoices
        Loader::loadComponents($this, ['SettingsCollection']);
        $client_settings = $this->SettingsCollection->fetchClientSettings($vars['client_id']);

        $vars = $this->getNextInvoiceVars($vars, $client_settings, true);

        // Note: there must be at least 1 line item
        $this->Input->setRules($this->getRules($vars));

        $tries = Configure::get('Blesta.transaction_deadlock_reattempts');
        do {
            $retry = false;

            try {
                return $this->makeInvoice($vars, $client_settings);
            } catch (PDOException $e) {
                // A deadlock occured (PDO error 1213, SQLState 40001)
                if ($tries > 0 && $e->getCode() == '40001' && str_contains($e->getMessage(), '1213')) {
                    $retry = true;
                }

                $this->Record->rollBack();
                $this->Record->reset();
            }

            $tries--;
        } while ($retry);

        // If we got this far, the system could not create the invoice after several attempts
        $this->Input->setErrors(['invoice_add' => ['failed' => $this->_('Invoices.!error.invoice_add.failed')]]);
    }

    /**
     * Performs a validation check on the set input rules and attempts to create an invoice
     *
     * @param array $vars An array of invoice data including:
     *
     *  - client_id The client ID the invoice belongs to
     *  - date_billed The date the invoice goes into effect
     *  - date_due The date the invoice is due
     *  - date_closed The date the invoice was closed
     *  - date_autodebit The date the invoice should be autodebited
     *  - status 'active','draft','proforma', or 'void'
     *  - currency The ISO 4217 3-character currency code of the invoice
     *  - note_public Notes visible to the client
     *  - note_private Notes visible only to staff members
     *  - lines A numerically indexed array of line item info including:
     *      - service_id The service ID attached to this line item (optional)
     *      - description The line item description
     *      - qty The quantity for this line item (min. 1)
     *      - amount The unit cost (cost per quantity) for this line item
     *      - tax Whether or not to tax the line item
     *  - term The term for the recurring invoice as an integer 1-65535,
     *      if blank will not be considered for a recurring invoice
     *  - period The period for the recurring invoice ('day', 'week', 'month', 'year')
     *  - duration The duration of the recurring invoice ('indefinitely'
     *      for forever or 'times' for a set number of times)
     *  - duration_time The number of times an invoice should recur
     *  - recur_date_billed The date the next invoice will be created
     *  - delivery A numerically indexed array of delivery methods
     * @return int The invoice ID, void on error
     */
    private function makeInvoice(array $vars, array $client_settings)
    {
        if (!isset($this->Clients)) {
            Loader::loadModels($this, ['Clients']);
        }

        if (!isset($this->Companies)) {
            Loader::loadModels($this, ['Companies']);
        }

        // Copy record so that it is not overwritten during validation
        $record = clone $this->Record;
        $this->Record->reset();
        // Start the transaction
        $this->Record->begin();

        if ($this->Input->validates($vars)) {
            // Set the record back
            $this->Record = $record;
            unset($record);

            // Assign subquery values to this record component
            $this->Record->appendValues($vars['id_value']->values);
            // Ensure the subquery value is set first because its the first value
            $vars = array_merge(['id_value' => null], $vars);
            // Add invoice
            $fields = ['id_value', 'id_format', 'client_id', 'date_billed', 'date_due', 'date_closed',
                'date_autodebit', 'autodebit', 'status', 'previous_due', 'currency', 'note_public', 'note_private'
            ];

            $this->Record->insert('invoices', $vars, $fields);

            $invoice_id = $this->Record->lastInsertId();

            // Get tax rules for this client
            $tax_rules = $this->getTaxRules($vars['client_id']);
            $num_taxes = count($tax_rules);

            // Add invoice line items
            $fields = ['invoice_id', 'service_id', 'description', 'qty', 'amount', 'order'];
            foreach ($vars['lines'] as $i => $line) {
                $line['invoice_id'] = $invoice_id;
                $line['order'] = $i;

                // Add invoice line item
                $this->Record->insert('invoice_lines', $line, $fields);

                $line_item_id = $this->Record->lastInsertId();

                // Add line item taxes, if set to taxable IFF tax is enabled
                if (
                    $client_settings['enable_tax'] == 'true'
                    && isset($line['tax']) && $line['tax']
                ) {
                    for ($j = 0; $j < $num_taxes; $j++) {
                        // Skip all but inclusive_calculated for tax exempt users
                        if (($client_settings['tax_exempt'] ?? 'false') == 'true'
                            && ($tax_rules[$j]->type != 'inclusive_calculated')
                        ) {
                            continue;
                        }

                        $this->addLineTax(
                            $line_item_id,
                            $tax_rules[$j]->id,
                            $client_settings['cascade_tax'] == 'true',
                            ($client_settings['tax_exempt'] ?? 'false') == 'true'
                                && $tax_rules[$j]->type == 'inclusive_calculated'
                        );
                    }
                }
            }

            // Add invoice delivery methods
            if (!empty($vars['delivery'])) {
                foreach ($vars['delivery'] as $key => $value) {
                    $this->addDelivery($invoice_id, ['method' => $value], $vars['client_id']);
                }
            }

            // Save recurring invoice info
            if (isset($vars['term']) && !empty($vars['term'])) {
                // If a draft, serialize and store as meta data for future editing
                if (isset($vars['status']) && $vars['status'] == 'draft') {
                    $this->setMeta(
                        $invoice_id,
                        'recur',
                        [
                            'term' => $vars['term'],
                            'period' => $vars['period'],
                            'duration' => $vars['duration'],
                            'duration_time' => $vars['duration_time'],
                            'recur_date_billed' => $vars['recur_date_billed']
                        ]
                    );
                } else {
                    // If not a draft, attempt to save as recurring
                    $vars['duration'] = ($vars['duration'] == 'indefinitely' ? null : $vars['duration_time']);
                    $vars['date_renews'] = $vars['recur_date_billed'];
                    $this->addRecurring($vars);
                }
            }

            // Commit if no errors when adding
            if (!$this->Input->errors()) {
                // Set totals/closed status
                $this->setClosed($invoice_id);

                $this->Record->commit();

                // Log that the invoice was created
                $log = $vars;
                unset($log['id_value']);
                $this->logger->info('Created Invoice', array_merge($log, ['id' => $invoice_id]));

                // Trigger the Invoices.addAfter event
                $this->executeAndParseEvent('Invoices.addAfter', compact('invoice_id'));

                return $invoice_id;
            }
        }

        // Rollback, something went wrong
        $this->Record->rollBack();
    }

    /**
     * Creates a new recurring invoice using the given data
     *
     * @param array $vars An array of invoice data including:
     *
     *  - client_id The client ID the invoice belongs to
     *  - term The term as an integer 1-65535 (optional, default 1)
     *  - period The period, 'day', 'week', 'month', 'year'
     *  - duration The number of times this invoice will recur or null to recur indefinitely
     *  - date_renews The date the next invoice will be created
     *  - currency The currency this invoice is created in
     *  - note_public Notes visible to the client
     *  - note_private Notes visible only to staff members
     *  - lines A numerically indexed array of line item info including:
     *      - description The line item description
     *      - qty The quantity for this line item (min. 1)
     *      - amount The unit cost (cost per quantity) for this line item
     *      - tax Whether or not to tax the line item
     *  - delivery A numerically indexed array of delivery methods
     * @return int The recurring invoice ID, void on error
     */
    public function addRecurring(array $vars)
    {
        // Set the rules for adding recurring invoices
        $this->Input->setRules($this->getRecurringRules($vars));

        if ($this->Input->validates($vars)) {
            // Add recurring invoice
            $fields = ['client_id', 'term', 'period', 'duration', 'currency',
                'date_renews', 'date_last_renewed', 'note_public', 'note_private', 'autodebit'
            ];
            $this->Record->insert('invoices_recur', $vars, $fields);

            $invoice_recur_id = $this->Record->lastInsertId();

            // Add line items
            $fields = ['invoice_recur_id', 'description', 'qty', 'amount', 'taxable', 'order'];
            foreach ($vars['lines'] as $i => $line) {
                $line['invoice_recur_id'] = $invoice_recur_id;
                $line['order'] = $i;

                if (isset($line['tax'])) {
                    $line['taxable'] = $this->boolToInt($line['tax']);
                }

                // Add invoice line item
                $this->Record->insert('invoice_recur_lines', $line, $fields);
            }

            // Add invoice delivery methods
            if (!empty($vars['delivery'])) {
                foreach ($vars['delivery'] as $key => $value) {
                    $this->addRecurringDelivery($invoice_recur_id, ['method' => $value], $vars['client_id']);
                }
            }

            // Log that the recurring invoice was created
            unset($vars['id_value']);
            $this->logger->info('Created Recurring Invoice', array_merge($vars, ['id' => $invoice_recur_id]));

            return $invoice_recur_id;
        }
    }

    /**
     * Sets meta data for the given invoice
     *
     * @param int $invoice_id The ID of the invoice to set meta data for
     * @param string $key The key of the invoice meta data
     * @param mixed $value The value to store for this meta field
     */
    public function setMeta($invoice_id, $key, $value)
    {
        // Delete all old meta data for this invoice and key
        $this->Record->from('invoice_meta')->
            where('invoice_id', '=', $invoice_id)->where('key', '=', $key)->delete();

        // Add the net meta data
        $this->Record->insert(
            'invoice_meta',
            ['invoice_id' => $invoice_id, 'key' => $key, 'value' => base64_encode(serialize($value))]
        );
    }

    /**
     * Deletes any meta on the given invoice ID
     *
     * @param int $invoice_id The invoice ID to unset meta data for
     * @param string $key The key to unset, null will unset all keys
     */
    public function unsetMeta($invoice_id, $key = null)
    {
        $this->Record->from('invoice_meta')->where('invoice_id', '=', $invoice_id);

        if ($key !== null) {
            $this->Record->where('key', '=', $key);
        }

        $this->Record->delete();
    }

    /**
     * Fetches the meta fields for this invoice.
     *
     * @param int $invoice_id The invoice ID to fetch meta data for
     * @param string $key The key to fetch if fetching only a single meta field, null to fetch all meta fields
     * @return mixed An array of stdClass objects if fetching all meta data,
     * a stdClass object if fetching a specific meta field, boolean false if
     * fetching a specific meta field that does not exist
     */
    public function getMeta($invoice_id, $key = null)
    {
        $this->Record->select()->from('invoice_meta')->where('invoice_id', '=', $invoice_id);

        if ($key !== null) {
            return $this->Record->where('key', '=', $key)->fetch();
        }

        return $this->Record->fetchAll();
    }

    /**
     * Adds a line item to an existing invoice
     *
     * @param int $invoice_id The ID of the invoice to add a line item to
     * @param array $vars A list of line item vars including:
     *
     *  - service_id The service ID attached to this line item
     *  - description The line item description
     *  - qty The quantity for this line item (min. 1)
     *  - amount The unit cost (cost per quantity) for this line item
     *  - tax Whether or not to tax the line item
     *  - order The order number of the line item (optional, default is the last)
     * @return int The ID of the line item created
     */
    private function addLine($invoice_id, array $vars)
    {
        $line = $vars;
        $line['invoice_id'] = $invoice_id;

        // Calculate the next line item order off of this invoice
        if (!isset($vars['order'])) {
            $order = $this->Record->select(['MAX(order)' => 'order'])->
                from('invoice_lines')->
                where('invoice_id', '=', $invoice_id)->
                fetch();

            $line['order'] = isset($order->order) ? $order->order + 1 : 0;
        }

        // Insert a new line item
        $fields = ['invoice_id', 'service_id', 'description', 'qty', 'amount', 'order'];
        $this->Record->insert('invoice_lines', $line, $fields);

        return $this->Record->lastInsertId();
    }

    /**
     * Updates an invoice using the given data. If a new line item is added, or
     * the quantity, unit cost, or tax status of an item is updated the
     * latest tax rules will be applied to this invoice.
     *
     * @param int $invoice_id The ID of the invoice to update
     * @param array $vars An array of invoice data (all optional unless noted otherwise) including:
     *
     *  - client_id The client ID the invoice belongs to
     *  - date_billed The date the invoice goes into effect
     *  - date_due The date the invoice is due
     *  - date_closed The date the invoice was closed
     *  - date_autodebit The date the invoice should be autodebited
     *  - status 'active','draft','proforma', or 'void'
     *  - currency The ISO 4217 3-character currency code of the invoice
     *  - note_public Notes visible to the client
     *  - note_private Notes visible only to staff members
     *  - lines A numerically indexed array of line item info including:
     *      - id The ID for this line item (required to update, else will add as new)
     *      - service_id The service ID attached to this line item
     *      - description The line item description (if empty, along with amount, will delete line item)
     *      - qty The quantity for this line item (min. 1)
     *      - amount The unit cost (cost per quantity) for this line item
     *          (if empty, along with description, will delete line item)
     *      - tax Whether or not to tax the line item
     *  - term If editing a draft, the term for the recurring invoice as an
     *      integer 1-65535, if blank will not be considered for a recurring invoice
     *  - period If editing a draft, the period for the recurring invoice ('day', 'week', 'month', 'year')
     *  - duration If editing a draft, the duration of the recurring invoice
     *      ('indefinitely' for forever or 'times' for a set number of times)
     *  - duration_time If editing a draft, the number of times an invoice should recur
     *  - recur_date_billed If editing a draft, the date the next invoice will be created
     *  - delivery A numerically indexed array of delivery methods
     * @return int The invoice ID, void on error
     */
    public function edit($invoice_id, array $vars)
    {
        // Trigger the Invoices.editBefore event
        extract($this->executeAndParseEvent('Invoices.editBefore', ['invoice_id' => $invoice_id, 'vars' => $vars]));

        if (!isset($this->Companies)) {
            Loader::loadModels($this, ['Clients']);
        }

        if (!isset($this->Companies)) {
            Loader::loadModels($this, ['Companies']);
        }

        if (!isset($this->SettingsCollection)) {
            Loader::loadComponents($this, ['SettingsCollection']);
        }

        // Get this current invoice
        $invoice = $this->get($invoice_id);

        // Fetch client settings on invoices
        $client_settings = $this->SettingsCollection->fetchClientSettings($invoice->client_id);

        // Fetch company settings
        $company_settings = $this->SettingsCollection->fetchSettings($this->Companies, Configure::get('Blesta.company_id'));

        if (!isset($vars['client_id'])) {
            $vars['client_id'] = $invoice->client_id;
        }
        if (!isset($vars['currency'])) {
            $vars['currency'] = $invoice->currency;
        }
        if (!isset($vars['recache'])) {
            $vars['recache'] = '1';
        }

        $vars['prev_status'] = $invoice->status;
        $vars = $this->getNextInvoiceVars($vars, $client_settings, false);

        // Copy record so that it is not overwritten during validation
        $record = clone $this->Record;
        $this->Record->reset();

        // Pull out line items that should be deleted
        $delete_items = [];
        // Check we have a numerically indexed line item array
        if (isset($vars['lines']) && (array_values($vars['lines']) === $vars['lines'])) {
            foreach ($vars['lines'] as $i => &$line) {
                if (isset($line['id']) && !empty($line['id'])) {
                    $amount = trim(isset($line['amount']) ? $line['amount'] : '');
                    $description = trim(isset($line['description']) ? $line['description'] : '');

                    // Set this item to be deleted, and remove it from validation check
                    // if amount and description are both empty
                    if (empty($description) && empty($amount)) {
                        $delete_items[] = $line;
                        unset($vars['lines'][$i]);
                    }
                }
            }
            unset($line);

            // Re-index array
            if (!empty($delete_items)) {
                $vars['lines'] = array_values($vars['lines']);
            }
        }

        $vars['id'] = $invoice_id;

        $rules = $this->getRules($vars);
        $line_rules = [
            'lines[][id]' => [
                'exists' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateExists'], 'id', 'invoice_lines', false],
                    'message' => $this->_('Invoices.!error.lines[][id].exists')
                ]
            ]
        ];

        // Invoice lines, currency, and status cannot be edited if payment has been made
        if ((isset($vars['lines']) && $this->lineItemsChanged($invoice->id, $vars))
            || (isset($vars['currency']) && $vars['currency'] != $invoice->currency)
            || (isset($vars['status']) && $vars['status'] != $invoice->status)
        ) {
            // Ensure no payments have been applied to the invoice
            $line_rules['id'] = [
                'amount_applied' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateAmountApplied']],
                    'negate' => true,
                    'message' => $this->_('Invoices.!error.id.amount_applied')
                ]
            ];
        }

        // No lines set, no descriptions required
        if (!isset($vars['lines'])) {
            $vars['lines'] = [];
            $line_rules['lines[][description]']['empty']['if_set'] = true;
        }
        // If status is proforma, but changing to active, amounts are likely
        // applied, and the amount applied rule can be ignored
        if ($invoice->status == 'proforma' && isset($vars['status']) && $vars['status'] == 'active') {
            unset($line_rules['id']);

            // Set the date billed to the current date, and the date due as well if it is not in the future
            $vars['date_billed'] = date('c');
            $vars['date_due'] = isset($vars['date_due'])
                && $this->Input->isDate($vars['date_due']) ? $vars['date_due'] : $invoice->date_due . 'Z';

            if (strtotime($vars['date_billed']) > strtotime($vars['date_due'])) {
                $vars['date_due'] = $vars['date_billed'];
            }

            // Recache the invoice when transitioning from proforma to active
            $vars['recache'] = '1';
        }

        // Set other rules to optional
        $rules['date_billed']['format']['if_set'] = true;
        $rules['date_due']['format']['if_set'] = true;
        $rules['date_due']['after_billed']['if_set'] = true;

        $rules = array_merge($rules, $line_rules);

        $update_statuses = ['draft', 'proforma'];
        // If the invoice wasn't already a draft or proforma or we're not moving from a draft or proforma
        // then we can't update the id_format or id_value
        if (!in_array($invoice->status, $update_statuses)
            || ($invoice->status == $vars['status'])
            || $vars['status'] == 'void'
        ) {
            // Do not evaluate rules for id_format and id_value because they can not be changed
            unset($rules['id_format']);
            unset($rules['id_value']);
        }

        $this->Input->setRules($rules);

        // Edit the invoice
        if ($this->Input->validates($vars)) {
            if (isset($rules['id_value'])) {
                // Set the record back
                $this->Record = $record;
                unset($record);

                // Assign subquery values to this record component
                $this->Record->appendValues($vars['id_value']->values);
                // Ensure the subquery value is set first because its the first value
                $vars = array_merge(['id_value' => null], $vars);
            }

            // Update invoice
            $fields = [
                'client_id', 'date_billed', 'date_due', 'date_closed', 'date_autodebit', 'autodebit',
                'status', 'previous_due', 'currency', 'note_public', 'note_private'
            ];
            if (isset($rules['id_format'])) {
                $fields[] = 'id_format';
            }
            if (isset($rules['id_value'])) {
                $fields[] = 'id_value';
            }

            $this->Record->where('id', '=', $invoice_id)->update('invoices', $vars, $fields);

            // Delete existing unsent invoice delivery methods and insert new
            $this->Record->from('invoice_delivery')->where('invoice_id', '=', $invoice_id)->
                where('date_sent', '=', null)->delete();

            if (!empty($vars['delivery'])
                && is_array($vars['delivery'])
                && ($num_methods = count($vars['delivery'])) > 0
            ) {
                for ($i = 0; $i < $num_methods; $i++) {
                    $this->addDelivery($invoice_id, ['method' => $vars['delivery'][$i]], $vars['client_id']);
                }
            }

            if (!empty($vars['lines'])) {
                // Get the tax rules
                $tax_rules = $this->getTaxRules($invoice->client_id);

                // Flag whether or not the invoice has been updated in such a way to
                // warrant updating the tax rules applied to the invoice
                $tax_change = $this->taxUpdateRequired($invoice_id, $vars['lines'], $delete_items);

                // Delete any line items set to be deleted
                for ($i = 0, $num_items = count($delete_items); $i < $num_items; $i++) {
                    $this->deleteLine($delete_items[$i]['id']);
                }

                // Insert and update line items and taxes
                foreach ($vars['lines'] as $i => $line) {
                    $line['invoice_id'] = $invoice_id;

                    // Add or update a line item
                    if (isset($line['id']) && !empty($line['id'])) {
                        $line_item_id = $line['id'];
                        $line['order'] = $i;

                        // Update a line item
                        $fields = ['service_id', 'description', 'qty', 'amount', 'order'];
                        $this->Record->where('id', '=', $line_item_id)->update('invoice_lines', $line, $fields);

                        if ($tax_change) {
                            // Delete the current line item tax rule
                            $this->deleteLineTax($line_item_id);
                        }
                    } else {
                        // Create a new line item
                        $line_item_id = $this->addLine($invoice_id, $line);
                    }

                    if ($tax_change) {
                        // Add line item taxes, if set to taxable IFF tax is enabled
                        if ($client_settings['enable_tax'] == 'true' && isset($line['tax']) && $line['tax']) {
                            for ($j = 0, $num_taxes = count($tax_rules); $j < $num_taxes; $j++) {
                                $this->addLineTax(
                                    $line_item_id,
                                    $tax_rules[$j]->id,
                                    $client_settings['cascade_tax'] == 'true',
                                    $client_settings['tax_exempt'] == 'true'
                                        && $tax_rules[$j]->type == 'inclusive_calculated'
                                );
                            }
                        }
                    }
                }
            }

            // If invoice was a draft save recurring invoice info
            if ($invoice->status == 'draft') {
                if (isset($vars['term']) && !empty($vars['term'])) {
                    // If a draft, serialize and store as meta data for future editing
                    if (isset($vars['status']) && $vars['status'] == 'draft') {
                        $this->setMeta(
                            $invoice_id,
                            'recur',
                            [
                                'term' => $vars['term'],
                                'period' => $vars['period'],
                                'duration' => $vars['duration'],
                                'duration_time' => $vars['duration_time'],
                                'recur_date_billed' => $vars['recur_date_billed']
                            ]
                        );
                    } else {
                        // If not a draft, attempt to save as recurring
                        $vars['duration'] = ($vars['duration'] == 'indefinitely' ? null : $vars['duration_time']);
                        $vars['date_renews'] = $vars['recur_date_billed'];
                        $this->addRecurring($vars);

                        // Remove any existing meta data, no longer needed
                        $this->unsetMeta($invoice_id);
                    }
                } else {
                    // Remove any existing meta data, no longer needed
                    $this->unsetMeta($invoice_id);
                }
            } elseif ($invoice->status == 'proforma' && isset($vars['status']) && $vars['status'] == 'active') {
                // Requeue invoice for delivery when converted from proforma to active
                $this->requeueForDelivery($invoice->id, $invoice->client_id);
            }

            if (!empty($vars['lines'])) {
                // Update totals/set closed status
                $this->setClosed($invoice_id);
            }

            // Clear invoice cache
            if (
                Configure::get('Caching.on')
                && is_writable($company_settings['uploads_dir'])
                && $company_settings['inv_cache'] !== 'none'
                && $vars['recache'] == '1'
            ) {
                $this->clearCache($invoice_id, 'json');
                $this->clearCache($invoice_id, 'pdf');
            }

            // Log that the invoice was updated
            unset($vars['id_value']);
            $this->logger->info('Updated Invoice', array_merge($vars, ['id' => $invoice_id]));

            // Trigger the Invoices.editAfter event
            $this->executeAndParseEvent(
                'Invoices.editAfter',
                ['invoice_id' => $invoice_id, 'old_invoice' => $invoice]
            );

            return $invoice_id;
        }
    }

    /**
     * Checks whether the line items for an invoice are being altered
     *
     * @param int $invoice_id The ID of the invoice to be checked
     * @param array $vars The vars being used to alter the invoice
     * @return boolean Whether the line items have been changed
     */
    private function lineItemsChanged($invoice_id, $vars)
    {
        if (!($invoice = $this->get($invoice_id)) || !isset($vars['lines'])) {
            return false;
        }

        $line_items = $invoice->line_items;
        $new_line_items = $vars['lines'];

        // If there are a differing number of line items, they have changed
        if (count($line_items) !== count($new_line_items)) {
            return true;
        }

        $temp_line_items = [];
        foreach ($line_items as $line_item) {
            $temp_line_items[$line_item->id] = (array) $line_item;
        }

        foreach ($new_line_items as $new_line_item) {
            // The line item has changed if the ID is not set or doesn't exist in the current set
            if (!array_key_exists('id', (array)$new_line_item) || !array_key_exists($new_line_item['id'], (array)$temp_line_items)) {
                return true;
            }

            // The line item has changed if any property of it has changed
            if ($temp_line_items[$new_line_item['id']]['qty'] != $new_line_item['qty']
                || $temp_line_items[$new_line_item['id']]['amount'] != $new_line_item['amount']
                || ((bool)count($temp_line_items[$new_line_item['id']]['taxes_applied']))
                    != ($new_line_item['tax'] == 'true')
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Updates a recurring invoice using the given data. If a new line item is added, or
     * the quantity, unit cost, or tax status of an item is updated the
     * latest tax rules will be applied to this invoice.
     *
     * @param int $invoice_recur_id The ID of the recurring invoice to update
     * @param array $vars An array of invoice data (all optional) including:
     *
     *  - client_id The client ID the recurring invoice belongs to
     *  - term The term as an integer 1-65535 (optional, default 1)
     *  - period The period, 'day', 'week', 'month', 'year'
     *  - duration The number of times this invoice will recur or null to recur indefinitely
     *  - date_renews The date the next invoice will be created
     *  - date_last_renewed The date the last invoice was created (optional) - not recommended to overwrite this value
     *  - currency The currency this invoice is created in
     *  - note_public Notes visible to the client
     *  - note_private Notes visible only to staff members
     *  - lines A numerically indexed array of line item info including:
     *      - id The ID for this line item (required to update, else will add as new)
     *      - description The line item description (if empty, along with amount, will delete line item)
     *      - qty The quantity for this line item (min. 1)
     *      - amount The unit cost (cost per quantity) for this line item
     *          (if empty, along with description, will delete line item)
     *      - tax Whether or not to tax the line item
     *  - delivery A numerically indexed array of delivery methods
     * @return int The recurring invoice ID, void on error
     */
    public function editRecurring($invoice_recur_id, array $vars)
    {

        // Pull out line items that should be deleted
        $delete_items = [];
        // Check we have a numerically indexed line item array
        if (isset($vars['lines']) && (array_values($vars['lines']) === $vars['lines'])) {
            foreach ($vars['lines'] as $i => &$line) {
                if (isset($line['id']) && !empty($line['id'])) {
                    $amount = trim(isset($line['amount']) ? $line['amount'] : '');
                    $description = trim(isset($line['description']) ? $line['description'] : '');

                    // Set this item to be deleted, and remove it from validation check
                    // if amount and description are both empty
                    if (empty($description) && empty($amount)) {
                        $delete_items[] = $line;
                        unset($vars['lines'][$i]);
                    }
                }
            }
            unset($line);

            // Re-index array
            if (!empty($delete_items)) {
                $vars['lines'] = array_values($vars['lines']);
            }
        }

        $this->Input->setRules($this->getRecurringRules($vars));

        if ($this->Input->validates($vars)) {
            // Update recurring invoice
            $fields = [
                'client_id','term','period','duration','date_renews',
                'date_last_renewed','note_public','note_private','autodebit'
            ];
            $this->Record->where('id', '=', $invoice_recur_id)->update('invoices_recur', $vars, $fields);

            // Delete any line items set to be deleted
            for ($i = 0, $num_items = count($delete_items); $i < $num_items; $i++) {
                $this->deleteRecurringLine($delete_items[$i]['id']);
            }

            // Insert and update line items and taxes
            foreach ($vars['lines'] as $i => $line) {
                $line['invoice_recur_id'] = $invoice_recur_id;
                $line['order'] = $i;

                if (isset($line['tax'])) {
                    $line['taxable'] = $this->boolToInt($line['tax']);
                }

                // Add or update a line item
                if (isset($line['id'])
                    && !empty($line['id'])
                    && $this->validateExists($line['id'], 'id', 'invoice_recur_lines', false)
                ) {
                    $line_item_id = $line['id'];

                    // Update a line item
                    $fields = ['description', 'qty', 'amount', 'taxable', 'order'];
                    $this->Record->where('id', '=', $line_item_id)->update('invoice_recur_lines', $line, $fields);
                } else {
                    // Insert a new line item
                    $fields = ['invoice_recur_id', 'description', 'qty', 'amount', 'taxable', 'order'];
                    $this->Record->insert('invoice_recur_lines', $line, $fields);
                }
            }

            // Delete existing invoice delivery methods and insert new
            $this->Record->from('invoice_recur_delivery')->where('invoice_recur_id', '=', $invoice_recur_id)->delete();

            if (!empty($vars['delivery'])
                && is_array($vars['delivery'])
                && ($num_methods = count($vars['delivery'])) > 0
            ) {
                for ($i = 0; $i < $num_methods; $i++) {
                    $this->addRecurringDelivery(
                        $invoice_recur_id,
                        ['method' => $vars['delivery'][$i]],
                        $vars['client_id']
                    );
                }
            }

            // Log that the recurring invoice was updated
            $this->logger->info('Updated Recurring Invoice', array_merge($vars, ['id' => $invoice_recur_id]));

            return $invoice_recur_id;
        }
    }

    /**
     * Creates a new invoice if the given recurring invoice is set to be renewed
     *
     * @param int $invoice_recur_id The recurring invoice ID
     * @param array $client_settings A list of client settings belonging to this invoice's client (optional)
     * @return bool True if any invoices were created from this recurring invoice, false otherwise
     */
    public function addFromRecurring($invoice_recur_id, array $client_settings = null)
    {
        $invoice = $this->getRecurring($invoice_recur_id);
        $created_invoice = false;

        if ($invoice) {
            // Fetch the client associated with this invoice
            Loader::loadModels($this, ['Clients', 'Companies']);
            $client = $this->Clients->get($invoice->client_id, false);
            // Get the date format for invoice descriptions
            $date_format = $this->Companies->getSetting($client->company_id, 'date_format')->value;
            $date = clone $this->Date;
            $date->setTimezone('UTC', Configure::get('Blesta.company_timezone'));

            // Get the client settings
            if (!isset($client_settings['inv_days_before_renewal']) || !isset($client_settings['timezone'])) {
                Loader::loadComponents($this, ['SettingsCollection']);
                $client_settings = $this->SettingsCollection->fetchClientSettings($invoice->client_id);
            }
            $invoice_days_before_renewal = abs((int)$client_settings['inv_days_before_renewal']);

            // Encompass the entire day
            $today_timestamp = $this->Date->toTime($this->dateToUtc($this->Date->format('Y-m-d') . ' 23:59:59', 'c'));

            // Set the next renew date
            $next_renew_date = $invoice->date_renews . 'Z';

            $invoice_day_timestamp = $this->Date->toTime($this->Date->modify(
                $next_renew_date,
                '-' . $invoice_days_before_renewal . ' days',
                'c',
                Configure::get('Blesta.company_timezone')
            ));
            $invoice_day = date('c', $invoice_day_timestamp);

            $fields = ['date_renews', 'date_last_renewed'];

            // Set invoice delivery methods
            $delivery_methods = [];
            foreach ($invoice->delivery as $delivery) {
                $delivery_methods[] = $delivery->method;
            }

            // Renew the invoice, possibly many times if it needs to be caught up
            while (($invoice->duration == null || $invoice->count < $invoice->duration)
                && $invoice_day_timestamp <= $today_timestamp
            ) {
                // Convert line items to arrays
                $start_period = $next_renew_date;
                $end_period = $this->Date->modify(
                    $start_period,
                    '+' . abs((int)$invoice->term) . ' ' . $invoice->period,
                    'c',
                    Configure::get('Blesta.company_timezone')
                );
                $line_items = [];
                foreach ($invoice->line_items as $line) {
                    // Update the line item description to include the recurring period
                    $line_item = (array)$line;
                    $line_item['description'] = Language::_(
                        'Invoices.!line_item.recurring_renew_description',
                        true,
                        $line->description,
                        $date->cast($start_period, $date_format),
                        $date->cast($end_period, $date_format)
                    );
                    $line_items[] = $line_item;
                }
                unset($start_period, $end_period);

                // Adjust date_due to match today if billing in the past
                $date_billed = date('c');
                $date_due = $next_renew_date;
                if (strtotime($next_renew_date) < strtotime(date('c'))) {
                    $date_due = date('c');
                }

                $vars = [
                    'client_id' => $invoice->client_id,
                    'date_billed' => $date_billed,
                    'date_due' => $date_due,
                    'autodebit' => $invoice->autodebit,
                    'status' => 'active',
                    'currency' => $invoice->currency,
                    'note_public' => $invoice->note_public,
                    'note_private' => $invoice->note_private,
                    'lines' => $line_items
                ];

                // Only set delivery methods if given, so as to prevent errors when creating the invoice
                if (!empty($delivery_methods)) {
                    $vars['delivery'] = $delivery_methods;
                }

                // Create a new invoice
                $invoice_id = $this->add($vars);

                // Set the next renew date for any subsequent invoice
                $next_renew_date = $this->Date->modify(
                    $next_renew_date,
                    ' +' . abs((int)$invoice->term) . ' ' . $invoice->period,
                    'c',
                    Configure::get('Blesta.company_timezone')
                );
                $invoice_day = $this->Date->modify(
                    $next_renew_date,
                    ' -' . $invoice_days_before_renewal . ' days',
                    'c',
                    Configure::get('Blesta.company_timezone')
                );
                $invoice_day_timestamp = strtotime($invoice_day);

                if (!$this->errors()) {
                    // Update the recurring invoice renew dates
                    $this->Record->where('id', '=', $invoice_recur_id)
                        ->update(
                            'invoices_recur',
                            [
                                'date_renews' => $this->dateToUtc($next_renew_date),
                                'date_last_renewed' => $this->dateToUtc($invoice->date_renews . 'Z')
                            ],
                            $fields
                        );

                    // Set a recurring invoice was created
                    $this->Record->insert(
                        'invoices_recur_created',
                        [
                            'invoice_recur_id' => $invoice_recur_id,
                            'invoice_id' => $invoice_id
                        ]
                    );

                    $created_invoice = true;
                } else {
                    break;
                }

                // Fetch the recurring invoice again for the next iteration
                $invoice = $this->getRecurring($invoice_recur_id);
            }
        }

        return $created_invoice;
    }

    /**
     * Permanently deletes a draft invoice
     *
     * @param int $invoice_id The invoice ID of the draft invoice to delete
     */
    public function deleteDraft($invoice_id)
    {
        $invoice_id = (int)$invoice_id;

        $rules = [
            'invoice_id' => [
                'draft' => [
                    'rule' => [[$this, 'validateIsDraft']],
                    'message' => $this->_('Invoices.!error.invoice_id.draft')
                ]
            ]
        ];

        $this->Input->setRules($rules);

        // Set invoice ID for validation
        $vars = ['invoice_id' => $invoice_id];

        if ($this->Input->validates($vars)) {
            // Delete the given invoice iff it's a draft invoice
            $this->unsetMeta($invoice_id);
            $this->Record->from('invoice_delivery')->where('invoice_id', '=', $invoice_id)->delete();
            $this->Record->from('invoice_lines')
                ->leftJoin('invoice_line_taxes', 'invoice_line_taxes.line_id', '=', 'invoice_lines.id', false)
                ->where('invoice_lines.invoice_id', '=', $invoice_id)
                ->delete(['invoice_line_taxes.*', 'invoice_lines.*']);
            $this->Record->from('invoices')->where('id', '=', $invoice_id)->delete();
        }
    }

    /**
     * Permanently deletes all invoices for the given client
     *
     * @param int $client_id The ID of the client whose invoices to purge
     */
    public function deleteByClient($client_id)
    {
        $tables = [
            'invoices.*', 'invoice_delivery.*', 'invoice_lines.*',
            'invoice_line_taxes.*', 'invoice_meta.*', 'invoice_values.*'
        ];

        // Delete all invoices associated with this client
        $this->Record->from('invoices')
            ->leftJoin('invoice_delivery', 'invoice_delivery.invoice_id', '=', 'invoices.id', false)
            ->leftJoin('invoice_lines', 'invoice_lines.invoice_id', '=', 'invoices.id', false)
            ->leftJoin('invoice_line_taxes', 'invoice_line_taxes.line_id', '=', 'invoice_lines.id', false)
            ->leftJoin('invoice_meta', 'invoice_meta.invoice_id', '=', 'invoices.id', false)
            ->leftJoin('invoice_values', 'invoice_values.invoice_id', '=', 'invoices.id', false)
            ->where('invoices.client_id', '=', $client_id)
            ->delete($tables);
    }

    /**
     * Permanently removes a recurring invoice from the system
     *
     * @param int $invoice_recur_id The ID of the recurring invoice to delete
     */
    public function deleteRecurring($invoice_recur_id)
    {
        // No harm, no foul. We can delete recurring invoices outright, since there are no side-effects to doing so
        $this->Record->from('invoices_recur')->where('id', '=', $invoice_recur_id)->delete();
        $this->Record->from('invoices_recur_created')->where('invoice_recur_id', '=', $invoice_recur_id)->delete();
        $this->Record->from('invoice_recur_delivery')->where('invoice_recur_id', '=', $invoice_recur_id)->delete();
        $this->Record->from('invoice_recur_lines')->where('invoice_recur_id', '=', $invoice_recur_id)->delete();
        $this->Record->from('invoice_recur_values')->where('invoice_recur_id', '=', $invoice_recur_id)->delete();

        // Log that the recurring invoice was deleted
        $this->logger->info('Deleted Recurring Invoice', ['id' => $invoice_recur_id]);
    }

    /**
     * Permanently deletes all recurring invoices for the given client
     *
     * @param int $client_id The ID of the client whose recurring invoices to purge
     */
    public function deleteRecurringByClient($client_id)
    {
        $tables = [
            'invoices_recur.*', 'invoices_recur_created.*', 'invoice_recur_delivery.*',
            'invoice_recur_lines.*', 'invoice_recur_values.*'
        ];

        // Delete all invoices associated with this client
        $this->Record->from('invoices_recur')
            ->leftJoin(
                'invoices_recur_created',
                'invoices_recur_created.invoice_recur_id',
                '=',
                'invoices_recur.id',
                false
            )
            ->leftJoin(
                'invoice_recur_delivery',
                'invoice_recur_delivery.invoice_recur_id',
                '=',
                'invoices_recur.id',
                false
            )
            ->leftJoin('invoice_recur_lines', 'invoice_recur_lines.invoice_recur_id', '=', 'invoices_recur.id', false)
            ->leftJoin('invoice_recur_values', 'invoice_recur_values.invoice_recur_id', '=', 'invoices_recur.id', false)
            ->where('invoices_recur.client_id', '=', $client_id)
            ->delete($tables);
    }

    /**
     * Permanently removes an invoice line item and its corresponding line item taxes
     *
     * @param int $line_id The line item ID
     */
    private function deleteLine($line_id)
    {
        // Delete line item
        $this->Record->from('invoice_lines')->where('id', '=', $line_id)->delete();

        // Delete line item taxes
        $this->deleteLineTax($line_id);
    }

    /**
     * Permanently removes a recurring invoice line item
     *
     * @param int $line_id The line item ID
     */
    private function deleteRecurringLine($line_id)
    {
        // Delete line item
        $this->Record->from('invoice_recur_lines')->where('id', '=', $line_id)->delete();
    }

    /**
     * Adds a new line item tax
     *
     * @param int $line_id The line item ID
     * @param int $tax_id The tax ID
     * @param bool $cascade Whether or not this tax rule should cascade over other rules
     * @param bool $subtract Whether or not this tax rule should be subtracted from the line item value
     */
    private function addLineTax($line_id, $tax_id, $cascade = false, $subtract = false)
    {
        $this->Record->insert(
            'invoice_line_taxes',
            [
                'line_id' => $line_id,
                'tax_id' => $tax_id,
                'cascade' => ($cascade ? 1 : 0),
                'subtract' => ($subtract ? 1 : 0)
            ]
        );
    }

    /**
     * Permanently removes an invoice line item's tax rule
     *
     * @param int $line_id The line item ID
     */
    private function deleteLineTax($line_id)
    {
        // Delete line item taxes
        $this->Record->from('invoice_line_taxes')->where('line_id', '=', $line_id)->delete();
    }

    /**
     * Creates an invoice from a set of services
     *
     * @param int $client_id The ID of the client to create the invoice for
     * @param array $service_ids A numerically-indexed array of service IDs to generate line items from
     * @param string $currency The currency code to use to generate the invoice
     * @param string $due_date The date the invoice is to be due
     * @param bool $allow_pro_rata True to allow the services to be priced
     *  considering the package pro rata details, or false otherwise (optional, default true)
     * @param bool $services_renew True if all of the given $service_ids are
     *  renewing services, or false if all $service_ids are new services (optional, default false)
     * @param array $service_transfers A numerically-indexed array of service IDs that are being transferred
     * @param int $term_cycles The amount of terms to bill (optional, default 1)
     * @return int $invoice_id The ID of the invoice generated
     */
    public function createFromServices(
        $client_id,
        $service_ids,
        $currency,
        $due_date,
        $allow_pro_rata = true,
        $services_renew = false,
        $service_transfers = [],
        $term_cycles = 1
    ) {
        // Trigger the Invoices.createFromServicesBefore event
        extract($this->executeAndParseEvent(
            'Invoices.createFromServicesBefore',
            [
                'client_id' => $client_id,
                'service_ids' => $service_ids,
                'currency' => $currency,
                'due_date' => $due_date,
                'allow_pro_rata' => $allow_pro_rata,
                'services_renew' => $services_renew
            ]
        ));

        if (!isset($this->Coupons)) {
            Loader::loadModels($this, ['Coupons']);
        }
        if (!isset($this->Clients)) {
            Loader::loadModels($this, ['Clients']);
        }
        if (!isset($this->ServiceInvoices)) {
            Loader::loadModels($this, ['ServiceInvoices']);
        }
        if (!isset($this->Services)) {
            Loader::loadModels($this, ['Services']);
        }
        if (!isset($this->Pricings)) {
            Loader::loadModels($this, ['Pricings']);
        }
        if (!isset($this->Packages)) {
            Loader::loadModels($this, ['Packages']);
        }
        if (!isset($this->ServiceChanges)) {
            Loader::loadModels($this, ['ServiceChanges']);
        }

        // Set the delivery method for the client
        $delivery_method = $this->Clients->getSetting($client_id, 'inv_method');
        if (isset($delivery_method->value)
            && array_key_exists($delivery_method->value, (array)$this->getDeliveryMethods($client_id))
        ) {
            $delivery_method = $delivery_method->value;
        } else {
            $delivery_method = 'email';
        }

        // Set the current price as the override price, only if the services are being invoiced for the first time
        if (!$services_renew) {
            foreach ($service_ids as $service_id) {
                $service = $this->Services->get($service_id);
                $pricing = $this->Services->getPackagePricing($service->pricing_id);
                $package = $this->Packages->getByPricingId($pricing->id);

                if ($package->override_price ?? false) {
                    $override = [
                        'override_price' => $pricing->price ?? null,
                        'override_currency' => $pricing->currency ?? null
                    ];
                    $fields = ['override_price', 'override_currency'];
                    $this->Record->where('services.id', '=', $service_id)
                        ->update('services', $override, $fields);
                }
            }
        }

        $coupons = [];
        $line_items = $this->getLinesForServices(
            $service_ids,
            $currency,
            $coupons,
            $allow_pro_rata,
            $services_renew,
            $service_transfers,
            $term_cycles
        );

        // Adjust date_due to match today if billing in the past
        $date_billed = date('c');
        if (strtotime($due_date) < strtotime(date('c'))) {
            $due_date = date('c');
        }

        // Create the invoice
        $vars = [
            'client_id' => $client_id,
            'date_billed' => $date_billed,
            'date_due' => $due_date,
            'status' => 'active',
            'currency' => $currency,
            'delivery' => [$delivery_method],
            'lines' => $line_items
        ];

        // Create the invoice
        $invoice_id = $this->add($vars);

        if ($this->Input->errors()) {
            return;
        }

        // Set the renewal price as the override price after creating the invoice
        if (!$services_renew) {
            foreach ($service_ids as $service_id) {
                $service = $this->Services->get($service_id);
                $pricing = $this->Services->getPackagePricing($service->pricing_id);
                $package = $this->Packages->getByPricingId($pricing->id);

                if (($package->override_price ?? false) && $pricing->price_renews !== $pricing->price) {
                    $override = [
                        'override_price' => $pricing->price_renews ?? $pricing->price ?? null,
                        'override_currency' => $pricing->currency ?? null
                    ];
                    $fields = ['override_price', 'override_currency'];
                    $this->Record->where('services.id', '=', $service_id)
                        ->update('services', $override, $fields);
                }
            }
        }

        // Add an association between this invoice and each service invoiced, to be used later for renewal purposes
        if ($services_renew) {
            foreach ($service_ids as $service_id) {
                $this->ServiceInvoices->add(['service_id' => $service_id, 'invoice_id' => $invoice_id]);
            }
        }

        // Increment the used quantity for all coupons used
        foreach ($coupons as $coupon_id => $coupon) {
            // Don't increment if this is a renewal and renewal limitations don't apply to this coupon
            if (!$services_renew || $coupon->limit_recurring == '1') {
                $this->Coupons->incrementUsage($coupon_id);
            }
        }

        // Trigger the Invoices.createFromServicesAfter event
        $this->executeAndParseEvent(
            'Invoices.createFromServicesAfter',
            [
                'invoice_id' => $invoice_id,
                'client_id' => $client_id,
                'service_ids' => $service_ids,
                'currency' => $currency,
                'due_date' => $due_date,
                'allow_pro_rata' => $allow_pro_rata,
                'services_renew' => $services_renew
            ]
        );

        return $invoice_id;
    }

    /**
     * Creates a renewal invoice for a service
     *
     * @param int $service_id The service ID to generate the renewal invoice
     * @param int $term_cycles The amount of terms to renew the service (optional, default 1)
     * @param int $pricing_id The pricing ID to be used the renewal of the service,
     *  if not provided service will be renewed on the current pricing
     * @return int $invoice_id The ID of the invoice generated
     */
    public function createRenewalFromService($service_id, $term_cycles = 1, $pricing_id = null)
    {
        if (!isset($this->Services)) {
            Loader::loadModels($this, ['Services']);
        }

        if (!isset($this->ServiceChanges)) {
            Loader::loadModels($this, ['ServiceChanges']);
        }

        if (!isset($this->Pricings)) {
            Loader::loadModels($this, ['Pricings']);
        }

        if (!isset($this->SettingsCollection)) {
            Loader::loadComponents($this, ['SettingsCollection']);
        }

        // Set service pricing and remove override price, only if the pricing ID has been updated
        $service = $this->Services->get($service_id);
        if ($service && !is_null($pricing_id) && $pricing_id !== $service->pricing_id) {
            $pricing = ['pricing_id' => $pricing_id, 'override_price' => null, 'override_currency' => null];
            $this->Services->edit($service_id, $pricing, true);
        }

        // Get service
        $service = $this->Services->get($service_id);
        if (!$service) {
            return;
        }

        // Check if the service is a domain
        $service_type = $this->Services->getType($service_id);

        if ($service_type == 'domain') {
            // Calculate days to the next renewal
            $current_date = new DateTime(date('c'));
            $renewal_date = new DateTime($service->date_renews);
            $days_to_renewal = $current_date->diff($renewal_date)->days ?? 0;

            // Calculate extra days to renew
            $current_date = new DateTime(date('c'));
            $future_renewal_date = new DateTime(
                date('c', strtotime('+' . (int) ($term_cycles * $service->package_pricing->term) . ' ' . $service->package_pricing->period))
            );
            $days_to_renew = $current_date->diff($future_renewal_date)->days ?? 0;
            $total_days_to_renew = $days_to_renew + $days_to_renewal;

            // If the service is a domain, check if the renewal does not exceed 10 years
            if (round($total_days_to_renew / 365) > 10) {
                $this->Input->setErrors(['domain_renew' => ['failed' => $this->_('Invoices.!error.domain_renew.failed')]]);
            }
        }
        if (($errors = $this->errors())) {
            return;
        }

        // Fetch the currency to generate the invoice in
        $client_currency = $this->SettingsCollection->fetchClientSetting(
            $service->client_id,
            null,
            'default_currency'
        );
        $client_currency = $client_currency['value'] ?? null;

        // Create the invoice for these renewing services
        $next_renewal_date = $this->Date->modify(
            date('c', strtotime($service->date_renews)),
            '+' . (int) ($term_cycles * $service->package_pricing->term) . ' ' . $service->package_pricing->period,
            'Y-m-d 00:00:00',
            Configure::get('Blesta.company_timezone')
        );

        $invoice_id = $this->createFromServices(
            $service->client_id,
            [$service->id],
            !empty($service->override_currency) ? $service->override_currency : $client_currency,
            $service->date_renews,
            false,
            true,
            [],
            (int) $term_cycles
        );

        if (($errors = $this->errors())) {
            $this->Input->setErrors($errors);

            return;
        }

        // Fetch the 'process_paid_service_changes' setting
        $process_paid_service_changes = $this->SettingsCollection->fetchClientSetting(
            $service->client_id,
            null,
            'process_paid_service_changes'
        );
        $process_paid_service_changes = $process_paid_service_changes['value'] ?? null;

        // Check if the service is scheduled for cancellation and remove it if is less than
        // the new renew date
        if (!empty($service->date_canceled) && $service->date_canceled < $next_renewal_date) {
            $service->date_canceled = null;
        }

        // Update the service, or queue the change
        if ($process_paid_service_changes == 'true' && isset($invoice_id)) {
            $dates = [
                'date_renews' => $next_renewal_date,
                'date_last_renewed' => $service->date_renews,
                'date_canceled' => $service->date_canceled
            ];
            $this->ServiceChanges->add($service->id, $invoice_id, ['data' => $dates]);
        } else {
            $dates = [
                'date_renews' => $next_renewal_date,
                'date_last_renewed' => $service->date_renews,
                'date_canceled' => $service->date_canceled
            ];
            $this->Services->edit($service->id, $dates, true);
        }

        return $invoice_id;
    }

    /**
     * Edits an invoice to append a set of service IDs as line items
     *
     * @param int $invoice_id The ID of the invoice to append to
     * @param array $service_ids A numerically-indexed array of service IDs to generate line items from
     * @return int $invoice_id The ID of the invoice updated
     */
    public function appendServices($invoice_id, $service_ids)
    {
        if (!isset($this->Services)) {
            Loader::loadModels($this, ['Services']);
        }
        if (!isset($this->Coupons)) {
            Loader::loadModels($this, ['Coupons']);
        }
        if (!isset($this->Clients)) {
            Loader::loadModels($this, ['Clients']);
        }
        if (!isset($this->Companies)) {
            Loader::loadModels($this, ['Companies']);
        }
        if (!isset($this->Transactions)) {
            Loader::loadModels($this, ['Transactions']);
        }
        if (!isset($this->Pricings)) {
            Loader::loadModels($this, ['Pricings']);
        }
        if (!isset($this->Packages)) {
            Loader::loadModels($this, ['Packages']);
        }
        if (!isset($this->ServiceChanges)) {
            Loader::loadModels($this, ['ServiceChanges']);
        }

        if (($invoice = $this->get($invoice_id))) {
            $coupons = [];

            // Fetch client settings
            Loader::loadComponents($this, ['SettingsCollection']);
            $client_settings = $this->SettingsCollection->fetchClientSettings($invoice->client_id);

            // Set the current package price as the override price
            foreach ($service_ids as $service_id) {
                $service = $this->Services->get($service_id);
                $pricing = $this->Services->getPackagePricing($service->pricing_id);
                $package = $this->Packages->getByPricingId($pricing->id);

                if ($package->override_price ?? false) {
                    $override = [
                        'override_price' => $pricing->price ?? null,
                        'override_currency' => $pricing->currency ?? null
                    ];
                    $fields = ['override_price', 'override_currency'];
                    $this->Record->where('services.id', '=', $service_id)
                        ->update('services', $override, $fields);
                }
            }

            // Get lines from services
            $line_items = $this->getLinesForServices($service_ids, $invoice->currency, $coupons);

            // Get the tax rules
            $tax_rules = $this->getTaxRules($invoice->client_id);

            foreach ($line_items as $line) {
                $line_item_id = $this->addLine($invoice_id, $line);

                // Add line item taxes, if set to taxable IFF tax is enabled
                if (isset($client_settings['enable_tax'])
                    && $client_settings['enable_tax'] == 'true'
                    && isset($line['tax'])
                    && $line['tax']
                ) {
                    for ($j = 0, $num_taxes = count($tax_rules); $j < $num_taxes; $j++) {
                        $this->addLineTax(
                            $line_item_id,
                            $tax_rules[$j]->id,
                            (isset($client_settings['cascade_tax']) && $client_settings['cascade_tax'] == 'true'),
                            (isset($client_settings['tax_exempt'])
                                && $client_settings['tax_exempt'] == 'true'
                                && $tax_rules[$j]->type == 'inclusive_calculated')
                        );
                    }
                }
            }

            if ($this->Input->errors()) {
                return;
            }

            // Increment the used quantity for all coupons used
            foreach ($coupons as $coupon_id => $coupon) {
                $this->Coupons->incrementUsage($coupon_id);
            }

            // Set the renewal override price after creating the invoice lines
            foreach ($service_ids as $service_id) {
                $service = $this->Services->get($service_id);
                $pricing = $this->Services->getPackagePricing($service->pricing_id);
                $package = $this->Packages->getByPricingId($pricing->id);

                if (($package->override_price ?? false) && $pricing->price_renews !== $pricing->price) {
                    $override = [
                        'override_price' => $pricing->price_renews ?? $pricing->price ?? null,
                        'override_currency' => $pricing->currency ?? null
                    ];
                    $fields = ['override_price', 'override_currency'];
                    $this->Record->where('services.id', '=', $service_id)
                        ->update('services', $override, $fields);
                }
            }

            // Update invoice totals/set closed
            $this->setClosed($invoice_id);

            // Update invoice cache
            $this->updateCache($invoice_id, (object) $invoice, 'json');
            $this->updateCache($invoice_id, null, 'pdf');
        }

        return $invoice_id;
    }

    /**
     * Generates the line items for a given set of service IDs.
     * May also bump service renew dates for prorated services.
     *
     * @param array $service_ids A numerically-indexed array of service IDs
     * @param string $currency The ISO 4217 3-character currency code of the invoice
     * @param array An array of stdClass objects, each representing a coupon
     * @param bool $allow_pro_rata True to allow the services to be priced
     *  considering the package pro rata details, or false otherwise (optional, default true)
     * @param bool $services_renew True if all of the given $service_ids are
     *  renewing services, or false if all $service_ids are new services (optional, default false)
     * @param array $service_transfers A numerically-indexed array of service IDs that are being transferred
     * @param int $term_cycles The amounts of terms to bill (optional, default 1)
     * @return array A list of line items
     */
    private function getLinesForServices(
        $service_ids,
        $currency,
        &$coupons,
        $allow_pro_rata = true,
        $services_renew = false,
        $service_transfers = [],
        $term_cycles = 1
    ) {
        if (!isset($this->Services)) {
            Loader::loadModels($this, ['Services']);
        }
        if (!isset($this->Packages)) {
            Loader::loadModels($this, ['Packages']);
        }
        if (!isset($this->Currencies)) {
            Loader::loadModels($this, ['Currencies']);
        }
        if (!isset($this->Coupons)) {
            Loader::loadModels($this, ['Coupons']);
        }
        if (!isset($this->Clients)) {
            Loader::loadModels($this, ['Clients']);
        }
        if (!isset($this->ClientGroups)) {
            Loader::loadModels($this, ['ClientGroups']);
        }

        $items = [];
        foreach ($service_ids as $service_id) {
            $service = $this->Services->get($service_id);

            if (!$service) {
                continue;
            }

            // Get prorata dates
            $dates = $allow_pro_rata
                ? $this->Packages->getProrataDates(
                    $service->pricing_id,
                    $service->date_added . 'Z',
                    $service->date_renews . 'Z'
                )
                : [];

            // Prorate addons to match their parents if set to do so and they are not already being prorated
            $renew_date = $this->Date->format(
                'Y-m-d',
                $this->Services->getNextRenewDate(
                    $service->date_added . 'Z',
                    $service->package_pricing->term * $term_cycles,
                    $service->package_pricing->period
                )
            );

            if ($allow_pro_rata
                && ($client = $this->Clients->get($service->client_id))
                && ($client_group = $this->ClientGroups->get($client->client_group_id))
                && ($parent_service = $this->Services->get($service->parent_service_id))
                && $this->Services->canSyncToParent(
                    $service->package_pricing,
                    $parent_service->package_pricing,
                    $client_group->id
                )
                && $renew_date != $this->Date->format('Y-m-d', $parent_service->date_renews . 'Z')
            ) {
                $dates = ['end_date' => $parent_service->date_renews . 'Z'];
            }

            // Determine the service start date and the date that coupons should apply
            $now = date('c');
            $start_date = $now;
            $apply_date = $now;
            if ($service->package_pricing->period !== 'onetime' && !empty($service->date_renews)) {
                $start_date = !empty($service->date_last_renewed) ? $service->date_last_renewed : $service->date_added;
                $apply_date = $start_date . 'Z';

                // If the service is renewing, the service renew dates would not have been updated yet,
                // via cron, and so we need to generate the service from the current renew date
                if ($services_renew) {
                    $start_date = $service->date_renews;

                    // Calculate the next renew date for the service, since this is when the coupon should apply to it
                    $apply_date = $this->Services->getNextRenewDate(
                        $service->date_renews . 'Z',
                        $service->package_pricing->term * $term_cycles,
                        $service->package_pricing->period,
                        'c'
                    );
                }

                // Append zulu identifier to indicate this is a UTC datetime stamp
                $start_date .= 'Z';
            }

            // Get the service presenter
            $options = [
                // Setup fees only apply to new services, not renewing ones
                'includeSetupFees' => !$services_renew,
                // This is a recurring service if the services are being renewed
                'recur' => $services_renew,
                // The discounts are being applied relative to the apply date
                'applyDate' => $apply_date,
                // Line items show they are billed from this date
                'startDate' => $start_date,
                // The amount of terms the recurring service will be billed
                'cycles' => $term_cycles
            ];

            // Set the prorated start date if allowing for pro rata
            if ($allow_pro_rata) {
                $options['prorateStartDate'] = $service->date_added . 'Z';
            }

            // Only prorate to a specific end date if explicitly set
            // Otherwise allow the presenter to calculate proration as necessary
            if (!empty($dates['end_date'])) {
                $options['prorateEndDate'] = $dates['end_date'];
            }

            // Use transfer price
            if (in_array($service_id, $service_transfers)) {
                $options['transfer'] = true;
            }

            // Use renewal price
            if ($services_renew) {
                $options['renewal'] = true;
            }

            $presenter = $this->Services->getPresenter($service_id, $options);

            // Setup line items from each of the presenter's items
            $tax_discount = false;
            $service_item = true;
            foreach ($presenter->items() as $item) {
                // If the line belongs to a service, fetch the package and then append the
                // package description to the invoice line
                $package_description = '';
                if ($service_item) {
                    Loader::loadModels($this, ['Services', 'Clients']);
                    $service = $this->Services->get($service_id);

                    // Get the client to which the service belongs
                    $client = $this->Clients->get($service->client_id ?? null);

                    if ($service && $client && ($client->settings['inv_append_descriptions'] ?? 'false') == 'true') {
                        $package_descriptions = $service->package->descriptions;
                        $descriptions = [];

                        foreach ($package_descriptions as $description) {
                            $descriptions[$description->lang] = $description->text;
                        }
                        unset($description);

                        // Append package description
                        if (isset($client->settings['language']) && isset($descriptions[$client->settings['language']])) {
                            $package_description = $descriptions[$client->settings['language']];
                        }
                    }

                    // Any subsequent items are for fees or options
                    $service_item = false;
                }

                // Tax has to be deconstructed since the presenter's tax amounts
                // cannot be passed along
                $items[] = [
                    'service_id' => $service_id,
                    'qty' => $item->qty,
                    'amount' => $this->Currencies->convert(
                        $item->price,
                        $service->override_currency ?? $service->package_pricing->currency,
                        $currency,
                        Configure::get('Blesta.company_id')
                    ),
                    'description' => trim(($item->description ?? '') . "\n" . $package_description),
                    'tax' => !empty($item->taxes)
                ];

                // Only tax the discounts if one of the regular items is also taxes
                if (!empty($item->taxes)) {
                    $tax_discount = true;
                }
            }

            // Add a line item for each discount amount
            foreach ($presenter->discounts() as $discount) {
                $coupons[$discount->id] = $this->Coupons->get($discount->id);
                // The total discount is the negated total. The Pricing library used by Blesta taxes discounts by
                // default. This can be overriden by using the discount_taxes setting. This setting is not used
                // by Blesta currently, but if it ever is then this tax setting should be based on it as well
                $items[] = [
                    'service_id' => $service_id,
                    'qty' => 1,
                    'amount' => (-1 * $this->Currencies->convert(
                        $discount->total,
                        $service->package_pricing->currency,
                        $currency,
                        Configure::get('Blesta.company_id')
                    )),
                    'description' => $discount->description,
                    'tax' => $tax_discount
                ];
            }

            // Bump the service to its prorated date
            if ($dates) {
                $fields = ['date_last_renewed' => $service->date_renews . 'Z', 'date_renews' => $dates['end_date']];
                $this->Services->edit($service->id, $fields, true);
            }
        }


        return $items;
    }

    /**
     * Sets the invoice to closed if the invoice has been paid in full, otherwise
     * removes any closed status previously set on the invoice. Only invoices with
     * status of 'active' can be closed.
     *
     * @param int $invoice_id The ID of the invoice to close or unclose
     * @return bool True if the invoice was closed, false otherwise
     */
    public function setClosed($invoice_id)
    {
        // Trigger the Invoices.setClosedBefore event
        extract($this->executeAndParseEvent('Invoices.setClosedBefore', ['invoice_id' => $invoice_id]));

        // Update totals
        $this->updateTotals($invoice_id);

        $invoice = $this->get($invoice_id);

        if ($invoice) {
            // Mark as closed if it is an active or proforma invoice that was paid in full and has not already
            // been marked as closed
            if ($invoice->paid >= $invoice->total && ($invoice->status == 'active' || $invoice->status == 'proforma')) {
                // Make invoice active if proforma is now paid (will also update id_format/id_value)
                if ($invoice->status == 'proforma') {
                    $vars = ['status' => 'active'];

                    // Add any existing unsent delivery methods that will be deleted when calling Invoices::edit
                    foreach ($invoice->delivery as $delivery) {
                        // Only include unsent delivery methods
                        if ($delivery->date_sent === null) {
                            if (!isset($vars['delivery'])) {
                                $vars['delivery'] = [];
                            }
                            $vars['delivery'][] = $delivery->method;
                        }
                    }

                    $this->edit($invoice->id, $vars);
                }

                // Set closed
                $this->Record->where('id', '=', $invoice_id)->where('date_closed', '=', null)->
                    update('invoices', ['date_closed' => $this->dateToUtc(date('c'))]);

                // Trigger the Invoices.setClosedAfter event
                $this->executeAndParseEvent(
                    'Invoices.setClosedAfter',
                    ['invoice_id' => $invoice_id, 'old_invoice' => $invoice]
                );

                return true;
            } else {
                // If not paid in full or not active/proforma, remove closed status
                $this->Record->where('id', '=', $invoice_id)->update('invoices', ['date_closed' => null]);
            }
        }
        return false;
    }

    /**
     * Calculates and updates the stored subtotal, total, and amount paid values for the given invoice
     *
     * @param int $invoice_id The ID of the invoice to update totals for
     */
    private function updateTotals($invoice_id)
    {
        // Ensure we have a valid invoice
        $invoice = $this->getInvoice($invoice_id)->fetch();

        if (!$invoice) {
            return;
        }

        // Fetch current totals
        $totals = $this->getTotals($invoice_id);
        $subtotal = $totals->subtotal;
        $total = $totals->total;
        $paid = $this->getPaid($invoice_id);

        // Update totals by storing amounts to the currency's decimal precision
        $precision = $this->getCurrencyPrecision($invoice->currency, Configure::get('Blesta.company_id'));
        $data = [
            'subtotal' => round($subtotal, $precision),
            'total' => round($total, $precision),
            'paid' => round($paid, $precision)
        ];
        $this->Record->where('id', '=', $invoice_id)->update('invoices', $data);
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
        return ($currency ? $currency->precision : 4);
    }

    /**
     * Fetches the given invoice
     *
     * @deprecated since v4.6.0 - The properties of some data returned by this method are deprecated:
     *  - line_items
     *      - taxes_applied
     *      - tax_subtotal
     *      - tax_total
     *      - total
     *      - total_w_tax
     *
     * @param int $invoice_id The ID of the invoice to fetch
     * @return mixed A stdClass object containing invoice information, false if no such invoice exists
     */
    public function get($invoice_id)
    {
        if (!isset($this->Transactions)) {
            Loader::loadModels($this, ['Transactions']);
        }

        if (!isset($this->Clients)) {
            Loader::loadModels($this, ['Clients']);
        }

        $this->Record = $this->getInvoice($invoice_id);
        $invoice = $this->Record->fetch();

        if ($invoice) {
            $invoice->line_items = $this->getLineItems($invoice_id);
            $invoice->delivery = $this->getDelivery($invoice_id);
            $invoice->meta = $this->getMeta($invoice_id);
            $invoice->taxes = $this->getTaxes($invoice_id);

            # @deprecated since 4.6.0 - 'tax_total' on each tax rule is deprecated
            // Update the taxes to include 'tax_total' value for backward compatibility
            $presenter = $this->getPresenter($invoice_id);
            foreach ($invoice->taxes as $tax) {
                foreach ($presenter->taxes() as $tax_item) {
                    if ($tax_item->id == $tax->id) {
                        $tax->tax_total = $tax_item->total;
                    }
                }
            }

            # @deprecated since 4.6.0 - 'tax_total' and 'tax_subtotal' on the invoice is deprecated
            // Set the 'tax_subtotal' and 'tax_total' values for backward compatibility
            $totals = $presenter->totals();
            $invoice->tax_subtotal = $invoice->tax_total = $totals->tax_amount;
        }

        return $invoice;
    }

    /**
     * Fetches the given recurring invoice
     *
     * @param int $invoice_recur_id The ID of the recurring invoice to fetch
     * @return mixed A stdClass object containing recurring invoice
     *  information, false if no such recurring invoice exists
     */
    public function getRecurring($invoice_recur_id)
    {
        $this->Record = $this->getRecurringInvoice($invoice_recur_id);
        $invoice = $this->Record->fetch();

        if ($invoice) {
            $invoice->line_items = $this->getRecurringLineItems($invoice_recur_id);
            $invoice->delivery = $this->getRecurringDelivery($invoice_recur_id);
            $invoice->taxes = $this->getRecurringTaxes($invoice_recur_id);

            $vars = ['currency' => $invoice->currency, 'lines' => []];
            foreach ($invoice->line_items as $line) {
                $line->tax = $line->taxable ? 'true' : 'false';
                $vars['lines'][] = (array)$line;
            }

            // Update the taxes to include 'total' value
            $presenter = $this->getDataPresenter($invoice->client_id, $vars);
            $totals = $presenter->totals();
            $invoice->total = $totals->total;

            # @deprecated since 4.6.0 - 'tax_total' on each tax rule is deprecated
            // Update the taxes to include 'tax_total' value for backward compatibility
            foreach ($invoice->taxes as $tax) {
                foreach ($presenter->taxes() as $tax_item) {
                    if ($tax_item->id == $tax->id) {
                        $tax->tax_total = $tax_item->total;
                    }
                }
            }
        }

        return $invoice;
    }

    /**
     * Fetches the recurring invoice record that produced the given invoice ID
     *
     * @param int $invoice_id The ID of the invoice created by a recurring invoice
     * @return mixed A stdClass object representing the recurring invoice, false if no such recurring
     * invoice exists or the invoice was not created from a recurring invoice
     */
    public function getRecurringFromInvoices($invoice_id)
    {
        $invoice = $this->Record->select(['invoices_recur_created.invoice_recur_id'])->
            from('invoices_recur_created')->
            where('invoice_id', '=', $invoice_id)->fetch();

        if ($invoice) {
            return $this->getRecurring($invoice->invoice_recur_id);
        }
        return false;
    }

    /**
     * Calculates the amount of tax for each tax rule given that applies to
     * the given line sub total (which is unit cost * quantity).
     * Also returns the line total including inclusive tax rules as well as the total with all tax rules
     *
     * @deprecated since v4.6.0 - use \Blesta\Core\Pricing\ library
     *
     * @param float $line_subtotal The subtotal (quanity * unit cost) for the line item
     * @param array $taxes An array of stdClass objects each representing a tax rule to be applied to the line subtotal
     * @return array An array containing the following:
     *
     *  - tax An array of tax rule applied amounts
     *  - tax_subtotal The tax subtotal (all inclusive taxes applied)
     *  - tax_total All taxes applied (inclusive and exclusive)
     *  - line_total The total for the line including inclusive taxes
     *  - line_total_w_tax The total for the line including all taxes (inclusive and exclusive)
     */
    public function getTaxTotals($line_subtotal, $taxes)
    {
        $tax = [];
        $tax_subtotal = 0;
        $tax_total = 0;

        foreach ($taxes as $tax_rule) {
            $level_index = ($tax_rule->level - 1);

            // If cascading tax is enabled, and this tax rule level is > 1
            // apply this tax to the line item including tax level below it
            if ($tax_rule->cascade > 0 && $tax_rule->level > 1 && isset($tax[$level_index - 1])) {
                $tax_amount = round($tax_rule->amount * ($line_subtotal + $tax[$level_index - 1]['amount']) / 100, 2);
            } else {
                // This is a normal tax, which does not apply to the tax rule below it
                $tax_amount = round($tax_rule->amount * $line_subtotal / 100, 2);
            }

            // If the tax rule is inclusive, it belongs to the total
            if ($tax_rule->type == 'inclusive') {
                $tax_subtotal += $tax_amount;
            }
            $tax_total += $tax_amount;

            // If a tax is already defined at this level, increment the values
            if (isset($tax[$level_index])) {
                $tax_amount += $tax[$level_index]['amount'];
            }

            $tax[$level_index] = [
                'id' => $tax_rule->id,
                'name' => $tax_rule->name,
                'percentage' => $tax_rule->amount,
                'amount' => $tax_amount
            ];
        }
        unset($tax_rule);

        return [
            'tax' => $tax,
            'tax_subtotal' => $tax_subtotal,
            'tax_total' => $tax_total,
            'line_total' => $line_subtotal + $tax_subtotal,
            'line_total_w_tax' => $line_subtotal + $tax_total
        ];
    }

    /**
     * Fetches all line items belonging to the given invoice
     *
     * @deprecated since v4.6.0 - The properties of some data returned by this method are deprecated:
     *  - taxes_applied
     *  - tax_subtotal
     *  - tax_total
     *  - total
     *  - total_w_tax
     *
     * @param int $invoice_id The ID of the invoice to fetch line items for
     * @return array An array of stdClass objects each representing a line item
     */
    public function getLineItems($invoice_id)
    {
        $lines = $this->getLines($invoice_id);

        // Make the line items backward compatible by adding deprecated tax property values to each line
        return $this->makeLinesBackwardCompatible($lines);
    }

    /**
     * Retrieves the line items for the given invoice
     *
     * @param int $invoice_id The invoice ID of the line items to retrieve
     * @return array An array of line items for the given invoice
     */
    private function getLines($invoice_id)
    {
        $fields = [
            'invoice_lines.id', 'invoice_lines.invoice_id', 'invoice_lines.service_id', 'invoice_lines.description',
            'invoice_lines.qty', 'invoice_lines.amount', 'invoice_lines.qty*invoice_lines.amount' => 'subtotal',
            'currencies.precision'
        ];

        // Fetch all line items belonging to the given invoice
        $lines = $this->Record->select($fields)
            ->from('invoice_lines')
            ->where('invoice_lines.invoice_id', '=', $invoice_id)
            ->innerJoin('invoices', 'invoices.id', '=', 'invoice_lines.invoice_id', false)
            ->on('currencies.company_id', '=', Configure::get('Blesta.company_id'))
            ->innerJoin('currencies', 'currencies.code', '=', 'invoices.currency', false)
            ->order(['order' => 'ASC'])
            ->fetchAll();

        // Fetch tax rules for each line item
        foreach ($lines as &$line) {
            if (substr((string)$line->amount, -2) !== '00') {
                $line->precision = 4;
            }

            $line->taxes = $this->getLineTaxes($line->id);
        }

        return $lines;
    }

    /**
     * Adds deprecated tax information to each line item
     *
     * @deprecated since v4.6.0
     *
     * @param array $lines An array of stdClass objects representing invoice line items, including:
     *  - subtotal
     *  - taxes An array of tax rules for each line item @see ::getLineTaxes
     * @return array An array of invoice line items with tax information added to each line
     */
    private function makeLinesBackwardCompatible(array $lines)
    {
        // Fetch tax rules for each line item
        foreach ($lines as &$line) {
            // calculate the total due for each line item with tax (we already have it without tax)
            $tax_amounts = $this->getTaxTotals($line->subtotal, $line->taxes);
            // Amount of each tax rule applied to the line item
            $line->taxes_applied = $tax_amounts['tax'];
            // All inclusive tax totals
            $line->tax_subtotal = $tax_amounts['tax_subtotal'];
            // All inclusive and exclusive tax totals
            $line->tax_total = $tax_amounts['tax_total'];
            // Total include only inclusive tax rules
            $line->total = $tax_amounts['line_total'];
            // Total including all taxes (inclusive and exclusive)
            $line->total_w_tax = $tax_amounts['line_total_w_tax'];
        }

        return $lines;
    }

    /**
     * Fetches all line items belonging to the given recurring invoice
     *
     * @param int $invoice_recur_id The ID of the recurring invoice to fetch line items for
     * @return array An array of stdClass objects each representing a line item
     */
    public function getRecurringLineItems($invoice_recur_id)
    {
        $fields = ['id','invoice_recur_id','description','qty','amount','qty*amount' => 'subtotal','taxable'];
        // Fetch all line items belonging to the given invoice
        return $this->Record->select($fields)->from('invoice_recur_lines')->
            where('invoice_recur_lines.invoice_recur_id', '=', $invoice_recur_id)->
            order(['order' => 'ASC'])->
            fetchAll();
    }

    /**
     * Fetches all taxes for the given invoice
     *
     * @param int $invoice_id The invoice ID
     * @return array An array of all tax rules for this invoice
     */
    private function getTaxes($invoice_id)
    {
        $fields = ['taxes.*', 'invoice_line_taxes.cascade', 'invoice_line_taxes.subtract'];

        // Taxes are retrieved regardless of the client's tax status since the invoice itself determines that
        return $this->Record->select($fields)
            ->select(['TRIM(taxes.amount)+?' => 'amount'], false)
            ->appendValues([0])
            ->from('invoices')
            ->innerJoin('invoice_lines', 'invoice_lines.invoice_id', '=', 'invoices.id', false)
            ->innerJoin('invoice_line_taxes', 'invoice_line_taxes.line_id', '=', 'invoice_lines.id', false)
            ->innerJoin('taxes', 'taxes.id', '=', 'invoice_line_taxes.tax_id', false)
            ->where('invoices.id', '=', $invoice_id)
            ->group(['taxes.id'])
            ->order(['level' => 'asc'])
            ->fetchAll();
    }

    /**
     * Fetches all taxes for the given recurring invoice
     *
     * @param int $invoice_recur_id The invoice recurrence ID
     * @return array An array of all tax rules for this recurring invoice
     */
    private function getRecurringTaxes($invoice_recur_id)
    {
        // Fetch the client ID for a taxable recurring invoice
        $fields = ['invoices_recur.client_id'];
        $taxable = $this->Record->select($fields)
            ->from('invoices_recur')
            ->innerJoin('invoice_recur_lines', 'invoice_recur_lines.invoice_recur_id', '=', 'invoices_recur.id', false)
            ->where('invoices_recur.id', '=', $invoice_recur_id)
            ->where('invoice_recur_lines.taxable', '=', 1)
            ->fetch();

        // No recurring lines are taxable, so there is no tax to be retrieved
        if (!$taxable) {
            return [];
        }

        // Determine whether the client is taxable
        Loader::loadComponents($this, ['SettingsCollection']);
        $settings = $this->SettingsCollection->fetchClientSettings($taxable->client_id);

        // Tax may not be applied to the client related to this recurring invoice, so don't return any
        if ($settings['enable_tax'] != 'true' || $settings['tax_exempt'] == 'true') {
            return [];
        }

        return $this->getTaxRules($taxable->client_id);
    }

    /**
     * Fetches all tax info attached to the line item
     *
     * @param int $invoice_line_id The ID of the invoice line item to fetch tax info for
     * @return array An array of stdClass objects each representing a tax rule
     * @see Taxes::getAll()
     */
    private function getLineTaxes($invoice_line_id)
    {
        $fields = ['taxes.*', 'invoice_line_taxes.cascade', 'invoice_line_taxes.subtract'];

        return $this->Record->select($fields)
            ->select(['TRIM(taxes.amount)+?' => 'amount'], false)
            ->appendValues([0])
            ->from('invoice_line_taxes')
            ->innerJoin('taxes', 'invoice_line_taxes.tax_id', '=', 'taxes.id', false)
            ->where('invoice_line_taxes.line_id', '=', $invoice_line_id)
            ->fetchAll();
    }

    /**
     * Fetches a list of invoices for a client
     *
     * @param int $client_id The client ID (optional, default null to get invoices for all clients)
     * @param string $status The status type of the invoices to fetch (optional, default 'open') one of the following:
     *
     *  - open Fetches all active open invoices
     *  - closed Fetches all closed invoices
     *  - past_due Fetches all active past due invoices
     *  - draft Fetches all invoices with a status of "draft"
     *  - void Fetches all invoices with a status of "void"
     *  - active Fetches all invoices with a status of "active"
     *  - proforma Fetches all invoices with a status of "proforma"
     *  - to_autodebit Fetches all invoices that are ready to be autodebited
     *      now, and which can be with an active client and payment account to do so
     *  - pending_autodebit Fetches all invoice that are set to be
     *      autodebited in the future, and which have an active client and payment account to do so with
     *  - to_print Fetches all paper invoices set to be printed
     *  - printed Fetches all paper invoices that have been set as printed
     *  - pending Fetches all active invoices that have not been billed for yet
     *  - to_deliver Fetches all invoices set to be delivered by a
     *      method other than paper (i.e. deliverable invoices not in the list of those "to_print")
     *  - all Fetches all invoices
     * @param int $page The page to return results for (optional, default 1)
     * @param array $order_by The sort and order conditions (e.g. array('sort_field'=>"ASC"), optional)
     * @param array $filters A list of parameters to filter by, including:
     *
     *  - invoice_number The invoice number on which to filter invoices
     *  - currency The currency code on which to filter invoices
     *  - invoice_line The (partial) description of the invoice line on which to filter invoices
     * @return array An array of stdClass objects containing invoice information, or false if no invoices exist
     */
    public function getList(
        $client_id = null,
        $status = 'open',
        $page = 1,
        $order_by = ['date_due' => 'ASC'],
        array $filters = []
    ) {
        // If sorting by ID code, use id code sort mode
        if (isset($order_by['id_code']) && Configure::get('Blesta.id_code_sort_mode')) {
            $temp = $order_by['id_code'];
            unset($order_by['id_code']);

            foreach ((array)Configure::get('Blesta.id_code_sort_mode') as $key) {
                $order_by[$key] = $temp;
            }
        }

        $this->Record = $this->getInvoices(array_merge(['client_id' => $client_id, 'status' => $status], $filters));

        // Return the results
        return $this->Record->order($order_by)->
            limit($this->getPerPage(), (max(1, $page) - 1) * $this->getPerPage())->fetchAll();
    }

    /**
     * Returns the total number of invoices returned from Invoices::getClientList(), useful
     * in constructing pagination for the getList() method.
     *
     * @param int $client_id The client ID (optional, default null to get invoice count for all clients)
     * @param string $status The status type of the invoices to fetch (optional, default 'open') one of the following:
     *
     *  - open Fetches all active open invoices
     *  - closed Fetches all closed invoices
     *  - past_due Fetches all active past due invoices
     *  - draft Fetches all invoices with a status of "draft"
     *  - void Fetches all invoices with a status of "void"
     *  - active Fetches all invoices with a status of "active"
     *  - proforma Fetches all invoices with a status of "proforma"
     *  - to_autodebit Fetches all invoices that are ready to be autodebited
     *      now, and which can be with an active client and payment account to do so
     *  - pending_autodebit Fetches all invoice that are set to be
     *      autodebited in the future, and which have an active client and payment account to do so with
     *  - to_print Fetches all paper invoices set to be printed
     *  - printed Fetches all paper invoices that have been set as printed
     *  - pending Fetches all active invoices that have not been billed for yet
     *  - to_deliver Fetches all invoices set to be delivered by a method
     *      other than paper (i.e. deliverable invoices not in the list of those "to_print")
     *  - all Fetches all invoices
     * @param array $filters A list of parameters to filter by, including:
     *
     *  - invoice_number The invoice number on which to filter invoices
     *  - currency The currency code on which to filter invoices
     *  - invoice_line The (partial) description of the invoice line on which to filter invoices
     * @return int The total number of invoices
     * @see Invoices::getList()
     */
    public function getListCount($client_id = null, $status = 'open', array $filters = [])
    {
        $this->Record = $this->getInvoices(
            array_merge(['client_id' => $client_id, 'status' => $status], $filters),
            [],
            true
        );

        return $this->Record->numResults();
    }

    /**
     * Fetches all invoices for a client
     *
     * @param int $client_id The client ID (optional, default null to get invoices for all clients)
     * @param string $status The status type of the invoices to fetch (optional, default 'open') one of the following:
     *
     *  - open Fetches all active open invoices
     *  - closed Fetches all closed invoices
     *  - past_due Fetches all active past due invoices
     *  - draft Fetches all invoices with a status of "draft"
     *  - void Fetches all invoices with a status of "void"
     *  - active Fetches all invoices with a status of "active"
     *  - proforma Fetches all invoices with a status of "proforma"
     *  - to_autodebit Fetches all invoices that are ready to be autodebited
     *      now, and which can be with an active client and payment account to do so
     *  - pending_autodebit Fetches all invoice that are set to be
     *      autodebited in the future, and which have an active client and payment account to do so with
     *  - to_print Fetches all paper invoices set to be printed
     *  - printed Fetches all paper invoices that have been set as printed
     *  - pending Fetches all active invoices that have not been billed for yet
     *  - to_deliver Fetches all invoices set to be delivered by a method
     *      other than paper (i.e. deliverable invoices not in the list of those "to_print")
     *  - all Fetches all invoices
     * @param array $order_by The sort and order conditions (e.g. array('sort_field'=>"ASC"), optional)
     * @param string $currency The currency code to limit results on (null = any currency)
     * @return array An array of stdClass objects containing invoice information
     */
    public function getAll($client_id = null, $status = 'open', $order_by = ['date_due' => 'ASC'], $currency = null)
    {
        $this->Record = $this->getInvoices(['client_id' => $client_id, 'status' => $status]);

        if ($currency !== null) {
            $this->Record->where('currency', '=', $currency);
        }

        return $this->Record->order($order_by)->fetchAll();
    }

    /**
     * Fetches all invoices that contain the given service
     *
     * @param int $service_id The ID of the service whose invoices to fetch
     * @param int $client_id The client ID (optional, default null to get invoices for all clients)
     * @param string $status The status type of the invoices to fetch (optional, default 'open') one of the following:
     *
     *  - open Fetches all active open invoices
     *  - closed Fetches all closed invoices
     *  - past_due Fetches all active past due invoices
     *  - draft Fetches all invoices with a status of "draft"
     *  - void Fetches all invoices with a status of "void"
     *  - active Fetches all invoices with a status of "active"
     *  - proforma Fetches all invoices with a status of "proforma"
     *  - to_autodebit Fetches all invoices that are ready to be autodebited
     *      now, and which can be with an active client and payment account to do so
     *  - pending_autodebit Fetches all invoice that are set to be
     *      autodebited in the future, and which have an active client and payment account to do so with
     *  - to_print Fetches all paper invoices set to be printed
     *  - printed Fetches all paper invoices that have been set as printed
     *  - pending Fetches all active invoices that have not been billed for yet
     *  - to_deliver Fetches all invoices set to be delivered by a
     *      method other than paper (i.e. deliverable invoices not in the list of those "to_print")
     *  - all Fetches all invoices
     * @param array $order_by The sort and order conditions (e.g. array('sort_field'=>"ASC"), optional)
     */
    public function getAllWithService(
        $service_id,
        $client_id = null,
        $status = 'open',
        $order_by = ['date_due' => 'ASC']
    ) {
        $this->Record = $this->getInvoices(['client_id' => $client_id, 'status' => $status]);

        $this->Record->innerJoin('invoice_lines', 'invoice_lines.invoice_id', '=', 'invoices.id', false)->
            where('invoice_lines.service_id', '=', $service_id);

        return $this->Record->order($order_by)->fetchAll();
    }

    /**
     * Fetches all invoices for this company that are autodebitable by their respective clients
     *
     * @param int $client_group_id The client group ID
     * @param bool $pending True to fetch all invoices that will be ready to
     *  autodebit in the future, or false to fetch all invoices ready to be
     *  autodebited (optional, default false)
     * @param string $days The number of days before invoices are to be autodebited:
     *
     *  - autodebit_days_before_due Use the autodebit days before due setting
     *  - notice_pending_autodebit Use the autodebit days before due setting plus the notice pending autodebit setting
     * @return array An array of client IDs, each containing an array of
     *  stdClass objects representing invoice information
     */
    public function getAllAutodebitableInvoices($client_group_id, $pending = false, $days = 'autodebit_days_before_due')
    {
        // Fetch all autodebitable open invoices for this company
        $type = 'to_autodebit';
        if ($pending) {
            $type = 'pending_autodebit';
        }

        Loader::loadModels($this, ['ClientGroups']);

        // Determine the number of days from invoice due date to fetch invoices for
        $options = [];
        $num_days = 0;
        switch ($days) {
            case 'notice_pending_autodebit':
                $temp_days = $this->ClientGroups->getSetting($client_group_id, $days);
                // Valid integer given
                if ($temp_days && is_numeric($temp_days->value)) {
                    $num_days += $temp_days->value;
                }
                // no break, add both values up
            case 'autodebit_days_before_due':
                $temp_days = $this->ClientGroups->getSetting($client_group_id, 'autodebit_days_before_due');
                // Valid integer given
                if ($temp_days && is_numeric($temp_days->value)) {
                    $num_days += $temp_days->value;
                }
                break;
        }

        // Set option for autodebit date to be some number of days in the future
        $options = [
            'autodebit_date' => $this->Date->modify(
                date('c'),
                '+' . $num_days . ' days',
                'c',
                Configure::get('Blesta.company_timezone')
            ),
            'client_group_id' => $client_group_id
        ];

        $this->Record = $this->getInvoices(['status' => $type], $options);
        return $this->Record->order(['invoices.client_id' => 'ASC'])->fetchAll();
    }

    /**
     * Search invoices
     *
     * @param string $query The value to search invoices for
     * @param int $page The page number of results to fetch (optional, default 1)
     * @return array An array of invoices that match the search criteria
     */
    public function search($query, $page = 1)
    {
        $this->Record = $this->searchInvoices($query);

        // Set order by clause
        $order_by = [];
        if (Configure::get('Blesta.id_code_sort_mode')) {
            foreach ((array)Configure::get('Blesta.id_code_sort_mode') as $key) {
                $order_by[$key] = 'ASC';
            }
        } else {
            $order_by = ['date_due' => 'ASC'];
        }

        return $this->Record->order($order_by)->
            limit($this->getPerPage(), (max(1, $page) - 1) * $this->getPerPage())->
            fetchAll();
    }

    /**
     * Return the total number of invoices returned from Invoices::search(), useful
     * in constructing pagination
     *
     * @param string $query The value to search invoices for
     * @see Invoices::search()
     */
    public function getSearchCount($query)
    {
        $this->Record = $this->searchInvoices($query);

        return $this->Record->numResults();
    }

    /**
     * Partially constructs the query for searching invoices
     *
     * @param string $query The value to search invoices for
     * @return Record The partially constructed query Record object
     * @see Invoices::search(), Invoices::getSearchCount()
     */
    private function searchInvoices($query)
    {
        $this->Record = $this->getInvoices(['status' => 'all']);

        $this->Record->leftJoin('invoice_lines', 'invoice_lines.invoice_id', '=', 'invoices.id', false)
            ->open()
            ->where('invoices.id', '=', $query)
            ->orLike(
                "CONVERT(REPLACE(invoices.id_format, '"
                . $this->replacement_keys['invoices']['ID_VALUE_TAG']
                . "', invoices.id_value) USING utf8)",
                '%' . $query . '%',
                true,
                false
            )
            ->orLike(
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
            ->orLike('contacts.email', '%' . $query . '%')
            ->orLike('invoice_lines.description', '%' . $query . '%')
            ->close()
            ->group(['invoices.id']);

        return $this->Record;
    }

    /**
     * Fetches all recurring invoices for a client
     *
     * @param int $client_id The client ID (optional, default null to get recurring invoices for all clients)
     * @return array An array of stdClass objects containing recurring invoice information
     */
    public function getAllRecurring($client_id = null)
    {
        return $this->getRecurringInvoices(['client_id' => $client_id])->fetchAll();
    }

    /**
     * Fetches all renewing recurring invoices. That is, where the date_renews
     * is <= current date + the maximum invoice days before renewal for the
     * current client group and the recurring invoice has not already created all
     * invoices to be created.
     *
     * @param int $client_group_id The ID of the client group whose renewing recurring invoices to fetch
     * @return array An array of stdClass objects, each representing a recurring invoice
     */
    public function getAllRenewingRecurring($client_group_id)
    {
        // Get the invoice days before renewal
        Loader::loadModels($this, ['ClientGroups']);
        $inv_days_before_renewal = $this->ClientGroups->getSetting($client_group_id, 'inv_days_before_renewal');

        // Set the date at which invoices would be created based on the
        // renew date and invoice days before renewal, and encompass the entire day
        $invoice_date = $this->Date->modify(
            date('c'),
            '+' . abs((int)$inv_days_before_renewal->value) . ' days',
            'Y-m-d 23:59:59',
            Configure::get('Blesta.company_timezone')
        );

        // Get all recurring invoices set to renew today
        $this->Record = $this->getRecurringInvoices([], false)->
            where('client_groups.id', '=', $client_group_id)->
            where('invoices_recur.date_renews', '<=', $this->dateToUtc($invoice_date))->
            where('invoices_recur.term', '>', '0')->
            group('invoices_recur.id');

        $sub_query = $this->Record->get();
        $values = $this->Record->values;
        $this->Record->reset();

        // Filter for those that have not reached their recur limit up to this date
        $this->Record->select()->from([$sub_query => 'ri'])->
            appendValues($values)->
            having('ri.duration', '=', null)->
            orHaving('ri.duration', '>', 'IFNULL(ri.count,?)', false)->
            appendValues([0]);

        return $this->Record->fetchAll();
    }

    /**
     * Fetches a list of recurring invoices for a client
     *
     * @param int $client_id The client ID (optional, default null to get recurring invoices for all clients)
     * @param int $page The page to return results for
     * @param array $order The fields and direction to order by. Key/value
     *  pairs where key is the field and value is the direction (asc/desc)
     * @param array $filters A list of parameters to filter by, including:
     *
     *  - invoice_number The invoice number on which to filter invoices
     *  - currency The currency code on which to filter invoices
     *  - invoice_line The (partial) description of the invoice line on which to filter invoices
     * @return array An array of stdClass objects containing recurring invoice information
     */
    public function getRecurringList($client_id = null, $page = 1, array $order = ['id' => 'asc'], array $filters = [])
    {
        $this->Record = $this->getRecurringInvoices(array_merge(['client_id' => $client_id], $filters));

        // If sorting by term, sort by both term and period
        if (isset($order['term'])) {
            $temp_order_by = $order;

            $order = ['period' => $order['term'], 'term' => $order['term']];

            // Sort by any other fields given as well
            foreach ($temp_order_by as $sort => $ord) {
                if ($sort == 'term') {
                    continue;
                }

                $order[$sort] = $ord;
            }
        }

        // Return the results
        return $this->Record->order($order)
            ->limit($this->getPerPage(), (max(1, $page) - 1) * $this->getPerPage())
            ->fetchAll();
    }

    /**
     * Return the total number of recurring invoices returned from Invoices::getRecurringList(), useful
     * in constructing pagination for the getList() method.
     *
     * @param int $client_id The client ID
     * @param array $filters A list of parameters to filter by, including:
     *
     *  - invoice_number The invoice number on which to filter invoices
     *  - currency The currency code on which to filter invoices
     *  - invoice_line The (partial) description of the invoice line on which to filter invoices
     * @return int The total number of recurring invoices
     * @see Invoices::getRecurringList()
     */
    public function getRecurringListCount($client_id, array $filters = [])
    {
        $this->Record = $this->getRecurringInvoices(array_merge(['client_id' => $client_id], $filters));

        // Return the number of results
        return $this->Record->numResults();
    }

    /**
     * Evaluates the given invoice, performs necessary looks ups to determine if
     * the invoice is for a recurring invoice or service. Returns the term and
     * period for the recurring invoice or service.
     *
     * @param int $invoice_id The ID of the invoice
     * @return mixed boolean false if the invoice is not for a recurring
     *  service or invoice, otherwise an array of recurring info including:
     *
     *  - amount The amount to recur
     *  - term The term to recur
     *  - period The recurring period (day, week, month, year, onetime)
     *      used in conjunction with term in order to determine the next recurring payment
     */
    public function getRecurringInfo($invoice_id)
    {
        $recurring_invoice = $this->getRecurringFromInvoices($invoice_id);

        if ($recurring_invoice) {
            return [
                'amount' => $recurring_invoice->total,
                'term' => $recurring_invoice->term,
                'period' => $recurring_invoice->period
            ];
        } elseif (($invoice = $this->get($invoice_id))) {
            Loader::loadModels($this, ['Services']);
            $service_found = false;
            $services = [];
            $recur = [];

            foreach ($invoice->line_items as $line) {
                // Only line items with a service can recur. Only look at each service once
                if ($line->service_id == '' || isset($services[$line->service_id])) {
                    continue;
                }
                $services[$line->service_id] = $line->service_id;

                // Fetch the service
                $service = $this->Services->get($line->service_id);

                if ($service) {
                    $service_found = true;

                    if (empty($recur)) {
                        $recur = [
                            'amount' => $this->Services->getRenewalPrice($service->id, $invoice->currency),
                            'term' => $service->package_pricing->term,
                            'period' => $service->package_pricing->period,
                        ];
                    } elseif ($recur['term'] == $service->package_pricing->term
                        && $recur['period'] == $service->package_pricing->period
                    ) {
                        $recur['amount'] += $this->Services->getRenewalPrice($service->id, $invoice->currency);
                    } else {
                        // Can't recur due to multiple services at difference terms and periods
                        return false;
                    }
                }
            }

            if ($service_found) {
                return $recur;
            }
        }

        return false;
    }

    /**
     * Retrieves a list of recurring invoice periods
     *
     * @return array Key=>value pairs of recurring invoice pricing periods
     */
    public function getPricingPeriods()
    {
        return [
            'day' => $this->_('Invoices.getPricingPeriods.day'),
            'week' => $this->_('Invoices.getPricingPeriods.week'),
            'month' => $this->_('Invoices.getPricingPeriods.month'),
            'year' => $this->_('Invoices.getPricingPeriods.year')
        ];
    }

    /**
     * Gets a partial record for a subquery fetching client settings
     *
     * @param string $setting The name of the setting to fetch
     * @return Record The partially constructed query Record object
     */
    private function getClientSettingSubquery($setting)
    {
        $max_records = Configure::get('Blesta.max_records');
        if (empty($max_records)) {
            // Default to 2^31 - 1
            $max_records = 2147483647;
        }

        $setting_fields = ['key', 'value', 'encrypted', 'clients.id' => 'client_id'];

        // Client Settings
        $sql1 = $this->Record->select($setting_fields)->
            from('clients')->
            on('client_settings.key', '=', $setting)->
            innerJoin('client_settings', 'client_settings.client_id', '=', 'clients.id', false)->
            order(['NULL'], false)->
            group('clients.id')->
            limit($max_records)->
            get();
        $values = $this->Record->values;
        $this->Record->reset();
        $this->Record->values = $values;

        // Client Group Settings
        $sql2 = $this->Record->select($setting_fields)->
            from('clients')->
            innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)->
            on('client_group_settings.key', '=', $setting)->
            innerJoin(
                'client_group_settings',
                'client_group_settings.client_group_id',
                '=',
                'client_groups.id',
                false
            )->
            order(['NULL'], false)->
            limit($max_records)->
            get();
        $values = $this->Record->values;
        $this->Record->reset();
        $this->Record->values = $values;

        // Company Settings
        $sql3 = $this->Record->select($setting_fields)->
            from('clients')->
            innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)->
            on('company_settings.inherit', '=', '1')->
            on('company_settings.key', '=', $setting)->
            innerJoin('company_settings', 'company_settings.company_id', '=', 'client_groups.company_id', false)->
            order(['NULL'], false)->
            limit($max_records)->
            get();
        $values = $this->Record->values;
        $this->Record->reset();
        $this->Record->values = $values;

        // System settings
        $sql4 = $this->Record->select($setting_fields)->
            from('clients')->
            on('settings.inherit', '=', '1')->
            on('settings.key', '=', $setting)->
            innerJoin('settings')->
            order(['NULL'], false)->
            limit($max_records)->
            get();
        $values = $this->Record->values;
        $this->Record->reset();
        $this->Record->values = $values;

        $this->Record->select()
            ->from([
                '((' . $sql1 . ') UNION (' . $sql2 . ') UNION (' . $sql3 . ') UNION (' . $sql4 . '))' => 'temp'
            ])
            ->group('temp.client_id');
        return $this->Record;
    }

    /**
     * Retrieves the date that the given invoice should be autodebited. This considers
     * current client settings and autodebit accounts.
     *
     * @param int $invoice_id The ID of the invoice
     * @return mixed A string representing the UTC date that this invoice
     *  will be autodebited, or false if the invoice cannot be autodebited
     */
    public function getAutodebitDate($invoice_id)
    {
        $settings_subquery = $this->getClientSettingSubquery('autodebit');
        $settings = $settings_subquery->get();
        $setting_values = $settings_subquery->values;
        $this->Record->reset();

        // Check that the client has a CC or ACH account set for autodebit (only 1 could be)
        $invoice = $this->Record->select('invoices.*')
            ->from('invoices')
            ->innerJoin(
                [$settings => 'settings'],
                'settings.client_id',
                '=',
                'invoices.client_id',
                false
            )
            ->appendValues($setting_values)
            ->on('ach_client_account.type', '=', 'ach')
            ->leftJoin(
                ['client_account' => 'ach_client_account'],
                'ach_client_account.client_id',
                '=',
                'invoices.client_id',
                false
            )
            ->on('cc_client_account.type', '=', 'cc')
            ->leftJoin(
                ['client_account' => 'cc_client_account'],
                'cc_client_account.client_id',
                '=',
                'invoices.client_id',
                false
            )
            // Check that the found CC or ACH account is active
            ->leftJoin('accounts_ach', 'accounts_ach.id', '=', 'ach_client_account.account_id', false)
            ->leftJoin('accounts_cc', 'accounts_cc.id', '=', 'cc_client_account.account_id', false)
            ->open()
            ->where('accounts_ach.status', '=', 'active')
            ->orWhere('accounts_cc.status', '=', 'active')
            ->close()
            ->where('settings.value', '=', 'true')
            ->where('invoices.status', 'in', ['active', 'proforma'])
            ->where('invoices.id', '=', $invoice_id)
            ->fetch();

        // Autodebit is enabled
        if ($invoice) {
            // An autodebit date is set on the invoice itself
            if ($invoice->date_autodebit) {
                return $invoice->date_autodebit;
            }

            // Get the autodebit days before due setting
            if (!isset($this->SettingsCollection)) {
                Loader::loadComponents($this, ['SettingsCollection']);
            }

            $autodebit_days_before_due = $this->SettingsCollection->fetchClientSetting(
                $invoice->client_id,
                null,
                'autodebit_days_before_due'
            );

            if (isset($autodebit_days_before_due['value']) && is_numeric($autodebit_days_before_due['value'])) {
                return $this->dateToUtc($this->Date->modify(
                    $invoice->date_due . 'Z',
                    '-' . $autodebit_days_before_due['value'] . ' days',
                    'c',
                    Configure::get('Blesta.company_timezone')
                ));
            }
        }

        return false;
    }

    /**
     * Partially constructs the query required by Invoices::get() and others
     *
     * @param int $invoice_id The ID of the invoice to fetch
     * @return Record The partially constructed query Record object
     */
    private function getInvoice($invoice_id)
    {
        $fields = ['invoices.*',
            'REPLACE(invoices.id_format, ?, invoices.id_value)' => 'id_code',
            'invoice_delivery.date_sent' => 'delivery_date_sent'
        ];

        // Fetch the invoices along with total due and total paid, calculate total remaining on the fly
        $this->Record->select($fields)
            ->select(['invoices.total-IFNULL(invoices.paid,0)' => 'due'], false)
            ->appendValues([$this->replacement_keys['invoices']['ID_VALUE_TAG']])
            ->from('invoices')
            ->on('invoice_delivery.date_sent', '!=', null)
            ->leftJoin('invoice_delivery', 'invoice_delivery.invoice_id', '=', 'invoices.id', false)
            ->where('invoices.id', '=', $invoice_id)
            ->group('invoices.id');

        return $this->Record;
    }

    /**
     * Partially constructs the query required by Invoices::getList() and
     * Invoices::getListCount()
     *
     * @param array $filters A list of parameters to filter by, including:
     *
     *  - client_id The client ID (optional, default null to fetch invoices for all clients)
     *  - invoice_number The invoice number on which to filter invoices
     *  - currency The currency code on which to filter invoices
     *  - invoice_line The (partial) description of the invoice line on which to filter invoices
     *  - status The status type of the invoices to fetch (optional, default 'open') one of the following:
     *      - open Fetches all active open invoices
     *      - closed Fetches all closed invoices
     *      - past_due Fetches all active past due invoices
     *      - draft Fetches all invoices with a status of "draft"
     *      - void Fetches all invoices with a status of "void"
     *      - active Fetches all invoices with a status of "active"
     *      - proforma Fetches all invoices with a status of "proforma"
     *      - to_autodebit Fetches all invoices that are ready to be
     *          autodebited now, and which can be with an active client and payment account to do so
     *      - pending_autodebit Fetches all invoice that are set to be
     *          autodebited in the future, and which have an active client and payment account to do so with
     *      - to_print Fetches all paper invoices set to be printed
     *      - printed Fetches all paper invoices that have been set as printed
     *      - pending Fetches all active invoices that have not been billed for yet
     *      - to_deliver Fetches all invoices set to be delivered by a method
     *          other than paper (i.e. deliverable invoices not in the list of those "to_print")
     *      - all Fetches all invoices
     * @param array $options A list of additional options
     *
     *  - autodebit_date The autodebit date to fetch invoices; for use with
     *      the "to_autodebit" or "pending_autodebit" statuses
     *  - client_group_id The ID of the client group to filter invoices on
     * @return Record The partially constructed query Record object
     */
    private function getInvoices(array $filters = [], array $options = [], $count = false)
    {
        if (empty($filters['status'])) {
            $filters['status'] = 'open';
        }

        if ($count) {
            $fields = ['invoices.*'];
        } else {
            $fields = ['invoices.*',
                'REPLACE(invoices.id_format, ?, invoices.id_value)' => 'id_code',
                'invoice_delivery.date_sent' => 'delivery_date_sent',
                'REPLACE(clients.id_format, ?, clients.id_value)' => 'client_id_code',
                'contacts.first_name' => 'client_first_name',
                'contacts.last_name' => 'client_last_name',
                'contacts.company' => 'client_company',
                'contacts.address1' => 'client_address1',
                'contacts.email' => 'client_email'
            ];
        }

        // Filter based on company ID
        $company_id = Configure::get('Blesta.company_id');

        // Fetch the invoices along with total due and total paid, calculate total remaining on the fly
        $this->Record->select($fields);
        if (!$count) {
            $this->Record->select(['invoices.total-IFNULL(invoices.paid,0)' => 'due'], false)
                ->appendValues(
                    [
                        $this->replacement_keys['invoices']['ID_VALUE_TAG'],
                        $this->replacement_keys['clients']['ID_VALUE_TAG']
                    ]
                );
        }

        $this->Record->from('invoices')
            ->innerJoin('clients', 'clients.id', '=', 'invoices.client_id', false)
            ->innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false);

        $advanced_statuses = ['pending_autodebit', 'to_autodebit', 'to_print', 'printed','to_deliver'];
        if (!$count || in_array($filters['status'], $advanced_statuses)) {
            $this->Record->on('contacts.contact_type', '=', 'primary')
                ->innerJoin('contacts', 'contacts.client_id', '=', 'clients.id', false);

            // Require date_sent non-null if status is something other than "to_print" or "to_deliver"
            // so that we only fetch sent delivery data
            if ($filters['status'] != 'to_print' && $filters['status'] != 'to_deliver') {
                $this->Record->on('invoice_delivery.date_sent', '!=', null);
            }

            $this->Record->leftJoin('invoice_delivery', 'invoice_delivery.invoice_id', '=', 'invoices.id', false);
        }

        // Filter on invoice number
        if (!empty($filters['invoice_number'])) {
            $this->Record->having('id_code', '=', $filters['invoice_number']);
        }

        // Filter on invoice currency
        if (!empty($filters['currency'])) {
            $this->Record->where('invoices.currency', '=', $filters['currency']);
        }

        // Filter on invoice lines content
        if (!empty($filters['invoice_line'])) {
            $this->Record->on('invoice_lines.description', 'LIKE', '%' . $filters['invoice_line'] . '%')
                ->innerJoin('invoice_lines', 'invoice_lines.invoice_id', '=', 'invoices.id', false);
        }

        // Negate for $filters['status'] = 'open' or 'closed'
        $negate = false;

        switch ($filters['status']) {
            case 'closed':
                // Get closed invoices
                $negate = true;
                // no break;
            case 'open':
                // Get open invoices

                // Check the date is open/closed
                $this->Record->where('invoices.date_closed', ($negate ? '!=' : '='), null)->
                    where('invoices.status', 'in', ['active', 'proforma'])->
                    where('invoices.date_billed', '<=', $this->dateToUtc(date('c')));
                break;
            case 'pending':
                // Get invoices pending date billed
                $this->Record->where('invoices.date_billed', '>', $this->dateToUtc(date('c')))->
                    where('invoices.status', 'in', ['active', 'proforma']);
                break;
            case 'past_due':
                // Get past due invoices

                // Check date is past due and invoice is not closed
                $this->Record->where('invoices.date_due', '<', $this->dateToUtc(date('c')))->
                    where('invoices.date_closed', '=', null)->
                    where('invoices.status', 'in', ['active', 'proforma']);
                break;
            case 'pending_autodebit':
                // Get all set to autodebit in the future
                $pending_autodebit = true;
                // no break
            case 'to_autodebit':
                // Get all invoices set to be autodebited (i.e. which are not set to be autodebited in the future)
                // and where the client is set to be autodebited and has a payment account set to do so with
                // and where the autodebit payment account is active
                $date = clone $this->Date;
                $date->setTimezone('UTC', Configure::get('Blesta.company_timezone'));

                $now = $this->dateToUtc(date('c'));
                // Set the autodebit date to use
                $autodebit_date = isset($options['autodebit_date']) ? $options['autodebit_date'] : $now;

                $record = clone $this->Record;
                $this->Record->reset();
                $settings_subquery = $this->getClientSettingSubquery('autodebit');
                $settings = $settings_subquery->get();
                $settings_values = $settings_subquery->values;
                $this->Record->reset();
                $this->Record = $record;

                // Check that the client has a CC or ACH account set for autodebit (only 1 could be)
                $this->Record->leftJoin([$settings => 'settings'], 'settings.client_id', '=', 'clients.id', false)
                    ->appendValues($settings_values)
                    ->on('ach_client_account.type', '=', 'ach')
                    ->leftJoin(
                        ['client_account' => 'ach_client_account'],
                        'ach_client_account.client_id',
                        '=',
                        'clients.id',
                        false
                    )
                    ->on('cc_client_account.type', '=', 'cc')
                    ->leftJoin(
                        ['client_account' => 'cc_client_account'],
                        'cc_client_account.client_id',
                        '=',
                        'clients.id',
                        false
                    )
                    // Check that the found CC or ACH account is active
                    ->leftJoin('accounts_ach', 'accounts_ach.id', '=', 'ach_client_account.account_id', false)
                    ->leftJoin('accounts_cc', 'accounts_cc.id', '=', 'cc_client_account.account_id', false)
                    ->open()
                    ->where('accounts_ach.status', '=', 'active')
                    ->orWhere('accounts_cc.status', '=', 'active')
                    ->close()
                    // Ensure autodebit not disabled for client account
                    ->open()
                    ->where('settings.value', '=', 'true')
                    ->orWhere('settings.value', '=', null)
                    ->close()
                    // The invoice must be active and autodebit may not be set in the future (unless pending)
                    ->where('invoices.date_closed', '=', null)
                    ->where('invoices.status', 'in', ['active', 'proforma'])
                    ->where('invoices.autodebit', '=', '1')
                    ->where('invoices.date_billed', '<=', $now);

                $autodebit_date_local = $date->cast($autodebit_date, 'c');
                $autodebit_start = $this->Date->toTime(
                    $this->Date->modify(
                        $autodebit_date_local,
                        'midnight',
                        'c',
                        Configure::get('Blesta.company_timezone')
                    )
                );
                $autodebit_end = $this->Date->toTime(
                    $this->Date->modify(
                        $autodebit_date_local,
                        'midnight +1 day -1 second',
                        'c',
                        Configure::get('Blesta.company_timezone')
                    )
                );

                // Autodebit on the autodebit date or before the end of the due date
                $this->Record
                    ->open()
                        ->open()
                            ->where('invoices.date_autodebit', '>=', $this->dateToUtc($autodebit_start))
                            ->where('invoices.date_autodebit', '<=', $this->dateToUtc($autodebit_end))
                        ->close()
                        ->open()
                            ->orWhere('invoices.date_autodebit', '=', null)
                            ->where('invoices.date_due', '<=', $this->dateToUtc($autodebit_end));

                // Pending autodebits must also be due on the given autodebit date
                if (isset($pending_autodebit) && $pending_autodebit) {
                    $this->Record
                        ->where('invoices.date_due', '>=', $this->dateToUtc($autodebit_start));
                }

                $this->Record->close()
                    ->close();
                break;
            case 'to_print':
                // Get invoices pending printing
                $this->Record->where('invoice_delivery.method', '=', 'paper')->
                    where('invoices.status', 'in', ['active', 'proforma'])->
                    where('invoices.date_billed', '<=', $this->dateToUtc(date('c')))->
                    where('invoice_delivery.date_sent', '=', null);
                break;
            case 'printed':
                // Get printed invoices
                $this->Record->where('invoice_delivery.method', '=', 'paper');
                break;
            case 'to_deliver':
                // Get invoices pending deliver
                $this->Record->where('invoice_delivery.method', '!=', 'paper')->
                    open()->
                        where('invoices.status', 'in', ['active', 'proforma'])->
                    close()->
                    where('invoices.date_billed', '<=', $this->dateToUtc(date('c')))->
                    where('invoice_delivery.method', '!=', null)->
                    where('invoice_delivery.date_sent', '=', null);
                break;
            case 'all':
                // Do not filter on status
                break;
            default:
                // Get invoices by status (active, draft, proforma, void)
                $this->Record->where('invoices.status', '=', $filters['status']);
                break;
        }

        // Filter by client group ID
        if (isset($options['client_group_id'])) {
            $this->Record->where('client_groups.id', '=', $options['client_group_id']);
        }

        // Filter by company
        $this->Record->where('client_groups.company_id', '=', $company_id);

        // Get for a specific client
        if (!empty($filters['client_id'])) {
            $this->Record->where('invoices.client_id', '=', $filters['client_id']);
        }

        if ($filters['status'] !== 'all' && $filters['status'] !== 'to_print' && $filters['status'] !== 'printed') {
            $this->Record->group('invoices.id');
        }

        return $this->Record;
    }

    /**
     * Partially constructs the query required by Invoices::getRecurring() and others
     *
     * @param int $invoice_recur_id The recurring invoice ID to fetch
     * @return Record The partially constructed query Record object
     */
    private function getRecurringInvoice($invoice_recur_id)
    {
        $count = new Record();
        $count->select(['invoice_recur_id', 'COUNT(*)' => 'count'])->from('invoices_recur_created')->
            group('invoices_recur_created.invoice_recur_id');
        $sub_query = $count->get();
        $values = $count->values;

        $this->Record->values = $values;

        $fields = [
            'invoices_recur.*',
            'IFNULL(temp_count.count,?)' => 'count',
            'SUM(invoice_recur_lines.amount*invoice_recur_lines.qty)' => 'subtotal',
            'MAX(invoice_recur_lines.taxable)' => 'taxable'
        ];

        $this->Record->select($fields)
            ->appendValues([0])
            ->from('invoices_recur')
            ->leftJoin([$sub_query => 'temp_count'], 'temp_count.invoice_recur_id', '=', 'invoices_recur.id', false)
            ->leftJoin('invoice_recur_lines', 'invoices_recur.id', '=', 'invoice_recur_lines.invoice_recur_id', false)
            ->where('invoices_recur.id', '=', $invoice_recur_id)
            ->group('invoices_recur.id');

        return $this->Record;
    }

    /**
     * Partially constructs the query required by both Invoices::getRecurringList() and
     * Invoices::getRecurringListCount()
     *
     * @param array $filters A list of parameters to filter by, including:
     *
     *  - client_id The client ID (optional, default null to fetch invoices for all clients)
     *  - invoice_number The invoice number on which to filter invoices
     *  - currency The currency code on which to filter invoices
     *  - invoice_line The (partial) description of the invoice line on which to filter invoices
     * @param bool $group True to group the query as required, false to not group at all (grouping should still be done)
     * @return Record The partially constructed query Record object
     */
    private function getRecurringInvoices(array $filters = [], $group = true)
    {
        $count = new Record();
        $count->select(['invoice_recur_id', 'COUNT(*)' => 'count'])->from('invoices_recur_created')->
            group('invoices_recur_created.invoice_recur_id');
        $sub_query = $count->get();
        $values = $count->values;

        $this->Record->values = $values;

        $fields = [
            'invoices_recur.*',
            'IFNULL(temp_count.count,?)' => 'count',
            'SUM(invoice_recur_lines.amount*invoice_recur_lines.qty)' => 'subtotal',
            'MAX(invoice_recur_lines.taxable)' => 'taxable',
            'REPLACE(clients.id_format, ?, clients.id_value)' => 'client_id_code',
            'contacts.first_name' => 'client_first_name',
            'contacts.last_name' => 'client_last_name',
            'contacts.company' => 'client_company'
        ];

        // Filter based on company ID
        $company_id = Configure::get('Blesta.company_id');

        $this->Record->select($fields)->appendValues([0, $this->replacement_keys['clients']['ID_VALUE_TAG']])->
            from('invoices_recur')->
            leftJoin([$sub_query => 'temp_count'], 'temp_count.invoice_recur_id', '=', 'invoices_recur.id', false)->
            leftJoin('invoice_recur_lines', 'invoices_recur.id', '=', 'invoice_recur_lines.invoice_recur_id', false)->
            innerJoin('clients', 'clients.id', '=', 'invoices_recur.client_id', false)->
            innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)->
            on('contacts.contact_type', '=', 'primary')->
            innerJoin('contacts', 'contacts.client_id', '=', 'clients.id', false)->
            where('client_groups.company_id', '=', $company_id);

        // Filter on client ID
        if (!empty($filters['client_id'])) {
            $this->Record->where('invoices_recur.client_id', '=', $filters['client_id']);
        }

        // Filter on invoice ID
        if (!empty($filters['invoice_number'])) {
            $this->Record->where('invoices_recur.id', '=', $filters['invoice_number']);
        }

        // Filter on invoice currency
        if (!empty($filters['currency'])) {
            $this->Record->where('invoices_recur.currency', '=', $filters['currency']);
        }

        // Filter on invoice lines content
        if (!empty($filters['invoice_line'])) {
            $this->Record->where('invoice_recur_lines.description', 'LIKE', '%' . $filters['invoice_line'] . '%');
        }

        if ($group) {
            $this->Record->group('invoices_recur.id');
        }

        return $this->Record;
    }

    /**
     * Retrieves the previous due amount for the given client in the given currency
     *
     * @param int $client_id The client ID
     * @param string $currency The ISO 4217 3-character currency code
     * @return float The previous amount due for this client
     */
    private function getPreviousDue($client_id, $currency)
    {
        // Get sum of all open invoices
        $total_due = $this->amountDue($client_id, $currency);

        // Get sum of all transactions applied for all invoices
        $amount_applied = $this->Record->select(['SUM(transaction_applied.amount)' => 'total'])
            ->from('transaction_applied')
            ->innerJoin('invoices', 'invoices.id', '=', 'transaction_applied.invoice_id', false)
            ->where('invoices.status', 'in', ['active', 'proforma'])->where('invoices.currency', '=', $currency)
            ->where('invoices.client_id', '=', $client_id)->where('invoices.date_closed', '=', null)
            ->where('invoices.date_billed', '<=', $this->dateToUtc(date('c')))
            ->group('invoices.client_id')
            ->fetch();

        if ($amount_applied) {
            return max(0, ($total_due - $amount_applied->total));
        }
        return max(0, $total_due);
    }

    /**
     * Retrieves the number of invoices given an invoice status for the given client
     *
     * @param int $client_id The client ID (optional, default null to get invoice count for company)
     * @param string $status The status type of the invoices to fetch (optional, default 'open') one of the following:
     *
     *  - open Fetches all active open invoices
     *  - closed Fetches all closed invoices
     *  - past_due Fetches all active past due invoices
     *  - draft Fetches all invoices with a status of "draft"
     *  - void Fetches all invoices with a status of "void"
     *  - active Fetches all invoices with a status of "active"
     *  - proforma Fetches all invoices with a status of "proforma"
     *  - to_print Fetches all paper invoices set to be printed
     *  - printed Fetches all paper invoices that have been set as printed
     *  - pending Fetches all active invoices that have not been billed for yet
     *  - to_deliver Fetches all invoices set to be delivered by a method
     *      other than paper (i.e. deliverable invoices not in the list of those "to_print")
     * @param array $filters A list of parameters to filter by, including:
     *
     *  - invoice_number The invoice number on which to filter invoices
     *  - currency The currency code on which to filter invoices
     *  - invoice_line The (partial) description of the invoice line on which to filter invoices
     * @return int The number of invoices of type $status for $client_id
     */
    public function getStatusCount($client_id = null, $status = 'open', array $filters = [])
    {
        return $this->getInvoices(array_merge($filters, ['client_id' => $client_id, 'status' => $status]), [], true)
            ->numResults();
    }

    /**
     * Retrieves the number of recurring invoices for the given client
     *
     * @param int $client_id The client ID (optional, default null to get recurring invoice count for company)
     * @param array $filters A list of parameters to filter by, including:
     *
     *  - invoice_number The invoice number on which to filter invoices
     *  - currency The currency code on which to filter invoices
     *  - invoice_line The (partial) description of the invoice line on which to filter invoices
     * @return int The number of recurring invoices for $client_id
     */
    public function getRecurringCount($client_id = null, array $filters = [])
    {
        return $this->getRecurringInvoices(array_merge($filters, ['client_id' => $client_id]))
            ->numResults();
    }

    /**
     * Retrieves the totals for an invoice
     *
     * @param int $invoice_id The ID of the invoice whose totals to fetch
     * @return array An array of totals including:
     *  - subtotal
     *  - total
     *  - total_without_exclusive_tax
     *  - total_after_tax
     *  - total_after_discount
     *  - tax_amonut
     *  - discount_amount
     */
    private function getTotals($invoice_id)
    {
        return $this->getPresenter($invoice_id)->totals();
    }

    /**
     * Retrieves a presenter representing a set of items and taxes for the invoice
     *
     * @param int $invoice_id The ID of the invoice whose pricing to fetch
     * @return bool|Blesta\Core\Pricing\Presenter\Type\InvoicePresenter The presenter, otherwise false
     */
    public function getPresenter($invoice_id)
    {
        Loader::loadModels($this, ['Companies']);
        Loader::loadComponents($this, ['SettingsCollection']);

        // We must have an invoice
        if (!($invoice = $this->getInvoice($invoice_id)->fetch())) {
            return false;
        }

        // Set the line items
        $invoice->line_items = $this->getLines($invoice_id);

        // Retrieve the pricing builder from the container and update the date format options
        $container = Configure::get('container');
        $container['pricing.options'] = [
            'dateFormat' => $this->Companies->getSetting(Configure::get('Blesta.company_id'), 'date_format')
                ->value,
            'dateTimeFormat' => $this->Companies->getSetting(Configure::get('Blesta.company_id'), 'datetime_format')
                ->value
        ];

        $factory = $this->getFromContainer('pricingBuilder');
        $invoiceBuilder = $factory->invoice();

        // Build the invoice presenter
        $invoiceBuilder->settings($this->SettingsCollection->fetchClientSettings($invoice->client_id));
        $invoiceBuilder->taxes($this->getTaxes($invoice_id));

        return $invoiceBuilder->build($invoice);
    }

    /**
     * Retrieves a presenter representing a set of items and taxes for invoice data
     *
     * @param int $client_id The ID of the client the invoice data is for
     * @param array $vars An array of input representing the new invoice data
     *  - date_billed The date the invoice is to be billed
     *  - date_due The date the invoice is to be due
     *  - autodebit 1 or 0, whether or not the invoice can be autodebited
     *  - status The invoice status (e.g. 'active')
     *  - currency The ISO 4217 3-character currency code
     *  - lines A numerically-indexed array of arrays, each representing a line item
     *      - service_id The ID of the service this line item correlates to, if any
     *      - description The line item description
     *      - qty The line item quantity
     *      - amount The line item unit cost
     *      - tax "true" if the line item is taxable
     * @return Blesta\Core\Pricing\Presenter\Type\InvoiceDataPresenter The presenter
     */
    public function getDataPresenter($client_id, array $vars)
    {
        // Set the client ID into the vars
        $vars['client_id'] = $client_id;

        if (!isset($this->Companies)) {
            Loader::loadModels($this, ['Companies']);
        }
        if (!isset($this->SettingsCollection)) {
            Loader::loadComponents($this, ['SettingsCollection']);
        }

        // Retrieve the pricing builder from the container and update the date format options
        $container = Configure::get('container');
        $container['pricing.options'] = [
            'dateFormat' => $this->Companies->getSetting(Configure::get('Blesta.company_id'), 'date_format')
                ->value,
            'dateTimeFormat' => $this->Companies->getSetting(Configure::get('Blesta.company_id'), 'datetime_format')
                ->value
        ];

        $factory = $this->getFromContainer('pricingBuilder');
        $invoiceBuilder = $factory->invoiceData();

        // Build the invoice presenter
        $invoiceBuilder->settings($this->SettingsCollection->fetchClientSettings($client_id));
        $invoiceBuilder->taxes($this->getTaxRules($client_id));

        return $invoiceBuilder->build($vars);
    }

    /**
     * Updates $vars with the subqueries to properly set the previous_due, id_format, and id_value fields
     * when creating an invoice or converting a draft or profroma to an active invoice
     *
     * @param array $vars An array of invoice data from Invoices::add() or Invoices::edit()
     * @param array $client_settings An array of client settings
     * @param bool $new True if this is a new invoice, false if being updated
     * @return array An array of invoice data now including the proper
     *  subqueries for setting the previous_due, id_format and id_value fields
     */
    private function getNextInvoiceVars(array $vars, array $client_settings, $new = true)
    {
        $inv_format = $client_settings['inv_format'];
        $inv_start = $client_settings['inv_start'];
        $inv_increment = $client_settings['inv_increment'];
        if (!isset($vars['status'])) {
            $vars['status'] = 'active';
        }

        if ($vars['status'] == 'draft') {
            $inv_format = $client_settings['inv_draft_format'];
        } elseif ($vars['status'] == 'proforma'
            || (
                ($new || (isset($vars['prev_status']) && in_array($vars['prev_status'], ['void', 'draft'])))
                && $client_settings['inv_type'] == 'proforma'
            )
        ) {
            $vars['status'] = 'proforma';
            $inv_format = $client_settings['inv_proforma_format'];
            $inv_start = $client_settings['inv_proforma_start'];
        }

        // Get the previous amount due
        $vars['previous_due'] = $this->getPreviousDue($vars['client_id'], $vars['currency']);
        // Set the id format accordingly, also replace the {year} tag with the appropriate year,
        // the {month} tag with the appropriate month, and the {day} tag with the appropriate day
        // to ensure the id_value is calculated appropriately on a year-by-year basis
        $tags = ['{year}', '{month}', '{day}'];
        $replacements = [$this->Date->format('Y'), $this->Date->format('m'), $this->Date->format('d')];
        $vars['id_format'] = str_ireplace($tags, $replacements, $inv_format);

        // Creates subquery to calculate the next invoice ID value on the fly
        $sub_query = new Record();

        $values = [$inv_start, $inv_increment,
            $inv_start];

        $sub_query->select(['IFNULL(GREATEST(MAX(t1.id_value),?)+?,?)'], false)->
            appendValues($values)->
            from(['invoices' => 't1'])->
            innerJoin('clients', 'clients.id', '=', 't1.client_id', false)->
            innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)->
            where('client_groups.company_id', '=', Configure::get('Blesta.company_id'))->
            where('t1.id_format', '=', $vars['id_format']);
        // run get on the query so $sub_query->values are built
        $sub_query_string = $sub_query->get();

        // Convert subquery into sub-sub query to force MySQL to create a temporary table
        // to avoid conflicts with reading/writing from the "invoices" table simultaneously
        $query = new Record();
        $query->values = $sub_query->values;
        $query->select('t11.*')->from([$sub_query_string => 't11']);

        // id_value will be calculated on the fly using a subquery
        $vars['id_value'] = $query;

        return $vars;
    }

    /**
     * Requeues an invoice to be delivered again using all methods from which it has already been delivered,
     * so long as those delivery methods are still available
     *
     * @param int $invoice_id The ID of the invoice to requeue for delivery
     * @param int $client_id The ID of the client the invoice belongs to
     */
    private function requeueForDelivery($invoice_id, $client_id)
    {
        // Fetch the delivery methods already sent
        $delivery = $this->getDelivery($invoice_id, true);

        // Save any current errors
        $errors = $this->errors();
        $errors = ($errors ? $errors : []);

        // Requeue them for delivery again, only once for each method
        $methods = [];
        foreach ($delivery as $deliver) {
            // Skip setting the invoice for delivery using the same method more than once
            if (array_key_exists($deliver->method, $methods)) {
                continue;
            }

            // Mark this delivery method used so we don't return to it again
            $methods[$deliver->method] = true;

            // Set the invoice for delivery
            $this->addDelivery($invoice_id, ['method' => $deliver->method], $client_id);
        }

        // Ignore all errors encountered from this method (e.g. if the delivery method is no longer available),
        // and reset any that were already set
        $this->Input->setErrors($errors);
    }

    /**
     * Fetches all invoice delivery methods this invoice is assigned
     *
     * @param int $invoice_id The ID of the invoice
     * @param bool $sent True to get only invoice delivery records that have
     *  been sent, or false to get only delivery records that have not been sent (optional, defaults to fetch all)
     * @return array An array of stdClass objects containing invoice delivery log information
     */
    public function getDelivery($invoice_id, $sent = null)
    {
        $this->Record->select()->from('invoice_delivery')->
            where('invoice_id', '=', $invoice_id);

        // Filter on whether the invoice has been delivered
        if ($sent) {
            $this->Record->where('date_sent', '!=', null);
        } elseif ($sent === false) {
            $this->Record->where('date_sent', '=', null);
        }

        return $this->Record->fetchAll();
    }

    /**
     * Fetches all invoice delivery records assigned to each of the given invoice IDs
     *
     * @param array $invoice_ids A list of invoice IDs (optional)
     * @param string $delivery_method The delivery method to filter by (e.g. "email"), (optional)
     * @param string $status The delivery status, either "all" for all, "unsent"
     *  for deliveries not marked sent, or "sent" for deliveries marked sent (optional, default "all")
     * @return array An array of stdClass objects containing invoice delivery log information
     */
    public function getAllDelivery($invoice_ids = null, $delivery_method = null, $status = 'all')
    {
        $this->Record->select()->from('invoice_delivery');

        if ($invoice_ids && is_array($invoice_ids)) {
            $this->Record->where('invoice_id', 'in', $invoice_ids);
        }

        if ($delivery_method) {
            $this->Record->where('method', '=', $delivery_method);
        }

        // Filter on whether the delivery has been sent already
        if (in_array($status, ['unsent', 'sent'])) {
            $operator = ($status == 'unsent' ? '=' : '!=');
            $this->Record->where('date_sent', $operator, null);
        }

        return $this->Record->fetchAll();
    }

    /**
     * Fetches all invoice delivery methods this recurring invoice is assigned
     *
     * @param int $invoice_recur_id The ID of the recurring invoice
     * @return array An array of stdClass objects containing invoice delivery log information
     */
    public function getRecurringDelivery($invoice_recur_id)
    {
        return $this->Record->select(['id', 'invoice_recur_id', 'method'])->
            from('invoice_recur_delivery')->where('invoice_recur_id', '=', $invoice_recur_id)->fetchAll();
    }

    /**
     * Adds the invoice delivery status for the given invoice
     *
     * @param int $invoice_id The ID of the invoice to update delivery status for
     * @param array $vars An array of invoice delivery information including:
     *
     *  - method The delivery method
     * @param int $client_id The ID of the client to add the delivery method under
     * @return int The invoice delivery ID, void on error
     */
    public function addDelivery($invoice_id, array $vars, $client_id)
    {
        $delivery_methods = $this->getDeliveryMethods($client_id);
        $rules = [
            'invoice_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'invoices'],
                    'message' => $this->_('Invoices.!error.invoice_id.exists')
                ]
            ],
            'method' => [
                'exists' => [
                    'rule' => ['array_key_exists', $delivery_methods],
                    'message' => $this->_('Invoices.!error.method.exists')
                ]
            ]
        ];

        $this->Input->setRules($rules);

        $vars['invoice_id'] = $invoice_id;

        if ($this->Input->validates($vars)) {
            $fields = ['invoice_id', 'method'];
            $this->Record->insert('invoice_delivery', $vars, $fields);
            return $this->Record->lastInsertId();
        }
    }


    /**
     * Adds the invoice delivery status for the given recurring invoice
     *
     * @param int $invoice_recur_id The ID of the recurring invoice to update delivery status for
     * @param array $vars An array of invoice delivery information including:
     *
     *  - method The delivery method
     * @param int $client_id The ID of the client to add the delivery method under
     * @return int The recurring invoice delivery ID, void on error
     */
    public function addRecurringDelivery($invoice_recur_id, array $vars, $client_id)
    {
        $delivery_methods = $this->getDeliveryMethods($client_id);
        $rules = [
            'invoice_recur_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'invoices_recur'],
                    'message' => $this->_('Invoices.!error.invoice_recur_id.exists')
                ]
            ],
            'method[]' => [
                'exists' => [
                    'rule' => ['in_array', $delivery_methods],
                    'message' => $this->_('Invoices.!error.method.exists')
                ]
            ]
        ];

        $this->Input->setRules($rules);

        $vars['invoice_recur_id'] = $invoice_recur_id;

        if ($this->Input->validates($vars)) {
            $fields = ['invoice_recur_id', 'method'];
            $this->Record->insert('invoice_recur_delivery', $vars, $fields);
            return $this->Record->lastInsertId();
        }
    }

    /**
     * Fetches a list of invoice deliveries for the currently active company
     *
     * @param string $method The delivery method to filter by (optional, default null for all)
     * @param int $page The page to return results for (optional, default 1)
     * @param array $order_by The sort and order conditions (e.g. array('sort_field'=>"ASC"), optional)
     */
    public function getDeliveryList($method = null, $page = 1, array $order_by = ['date_sent' => 'DESC'])
    {
        $this->Record = $this->getInvoiceDeliveries($method);

        // If sorting by ID code, use id code sort mode
        if (isset($order_by['id_code']) && Configure::get('Blesta.id_code_sort_mode')) {
            $temp = $order_by['id_code'];
            unset($order_by['id_code']);

            foreach ((array)Configure::get('Blesta.id_code_sort_mode') as $key) {
                $order_by[$key] = $temp;
            }
        }

        if ($order_by) {
            $this->Record->order($order_by);
        }

        return $this->Record->limit($this->getPerPage(), (max(1, $page) - 1) * $this->getPerPage())->fetchAll();
    }

    /**
     * Retrieves the total number of invoice deliveries for the currently active company
     *
     * @param string $method The delivery method to filter by (optional, default null for all)
     * @return int The total number of invoice deliveries
     */
    public function getDeliveryListCount($method = null)
    {
        return $this->getInvoiceDeliveries($method)->numResults();
    }

    /**
     * Partially constructs a Record object for fetching invoice deliveries
     *
     * @param string $method The invoice delivery method to filter by (optional)
     * @return Record A partially-constructed Record object for fetching invoice deliveries
     */
    private function getInvoiceDeliveries($method = null)
    {
        $fields = ['invoice_delivery.*', 'contacts.first_name', 'contacts.last_name',
            'contacts.company', 'clients.id' => 'client_id',
            'REPLACE(invoices.id_format, ?, invoices.id_value)' => 'invoice_id_code',
            'REPLACE(clients.id_format, ?, clients.id_value)' => 'client_id_code',
        ];

        $this->Record->select($fields)
            ->from('invoice_delivery')
            ->appendValues(
                [
                    $this->replacement_keys['invoices']['ID_VALUE_TAG'],
                    $this->replacement_keys['clients']['ID_VALUE_TAG']
                ]
            )
            ->innerJoin('invoices', 'invoices.id', '=', 'invoice_delivery.invoice_id', false)
            ->innerJoin('clients', 'clients.id', '=', 'invoices.client_id', false)
            ->on('contacts.contact_type', '=', 'primary')
            ->innerJoin('contacts', 'contacts.client_id', '=', 'clients.id', false)
            ->on('client_groups.company_id', '=', Configure::get('Blesta.company_id'))
            ->innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false);

        if ($method) {
            $this->Record->where('invoice_delivery.method', '=', $method);
        }
        return $this->Record;
    }

    /**
     * Fetches all invoice delivery methods that are supported or enabled for this company
     *
     * @param int $client_id The ID of the client to fetch the delivery methods for
     * @param int $client_group_id The ID of the client group to fetch the
     *  delivery methods for if $client_id is not given
     * @param bool $enabled If true, will only return delivery methods that
     *  are enabled for this company, else all supported methods are returned
     * @return array An array of delivery methods in key/value pairs
     */
    public function getDeliveryMethods($client_id = null, $client_group_id = null, $enabled = true)
    {
        $company_id = Configure::get('Blesta.company_id');
        $methods = [
            'email' => $this->_('Invoices.getDeliveryMethods.email'),
            'paper' => $this->_('Invoices.getDeliveryMethods.paper'),
            'interfax' => $this->_('Invoices.getDeliveryMethods.interfax'),
            'postalmethods' => $this->_('Invoices.getDeliveryMethods.postalmethods')
        ];

        if ($enabled) {
            if (!isset($this->SettingsCollection)) {
                Loader::loadComponents($this, ['SettingsCollection']);
            }

            // If no client ID given, fetch from the company setting
            if ($client_id != null) {
                $delivery_methods = $this->SettingsCollection->fetchClientSetting($client_id, null, 'delivery_methods');
            } elseif ($client_group_id != null) {
                $delivery_methods = $this->SettingsCollection->fetchClientGroupSetting(
                    $client_group_id,
                    null,
                    'delivery_methods'
                );
            } else {
                $delivery_methods = $this->SettingsCollection->fetchSetting(null, $company_id, 'delivery_methods');
            }

            if ($delivery_methods && isset($delivery_methods['value'])) {
                $delivery_methods = unserialize(base64_decode($delivery_methods['value']));

                if (is_array($delivery_methods)) {
                    // array_fill_keys()
                    $delivery_methods = array_combine($delivery_methods, array_fill(0, count($delivery_methods), true));
                    return array_intersect_key($methods, $delivery_methods);
                }
            }
            return [];
        }
        return $methods;
    }

    /**
     * Marks the delivery status as sent
     *
     * @param int $invoice_delivery_id The ID of the delivery item to mark as sent
     * @param int $company_id The ID of the company whose invoice to mark
     *  delivered. Invoices not belonging to the given company will be
     *  ignored (optional, default null to not check the invoice company)
     */
    public function delivered($invoice_delivery_id, $company_id = null)
    {
        // Only mark this invoice delivered if it belongs to the given company
        if ($company_id) {
            $this->Record->innerJoin('invoices', 'invoices.id', '=', 'invoice_delivery.invoice_id', false)->
                innerJoin('clients', 'clients.id', '=', 'invoices.client_id', false)->
                    on('client_groups.company_id', '=', $company_id)->
                innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false);
        }

        $this->Record->set('invoice_delivery.date_sent', $this->dateToUtc(date('c')))->
            where('invoice_delivery.id', '=', $invoice_delivery_id)->update('invoice_delivery');
    }

    /**
     * Removes the invoice delivery record
     *
     * @param int $invoice_delivery_id The ID of the delivery item to delete
     */
    public function deleteDelivery($invoice_delivery_id)
    {
        $this->Record->from('invoice_delivery')->
            where('id', '=', $invoice_delivery_id)->delete();
    }

    /**
     * Removes the recurring invoice delivery record
     *
     * @param int $invoice_delivery_id The ID of the recurring delivery item to delete
     */
    public function deleteRecurringDelivery($invoice_delivery_id)
    {
        $this->Record->from('invoice_recur_delivery')->
            where('id', '=', $invoice_delivery_id)->delete();
    }

    /**
     * Calculates the client's amount due in the given currency. This sums all
     * existing open invoices for the given currency.
     *
     * @param int $client_id The client ID to calculate on
     * @param string $currency The ISO 4217 3-character currency code
     * @param string $status The status type of the invoices whose amount due
     *  to fetch (optional, default 'open') one of the following:
     *  - open Fetches all active open invoices
     *  - closed Fetches all closed invoices
     *  - past_due Fetches all active past due invoices
     *  - draft Fetches all invoices with a status of "draft"
     *  - void Fetches all invoices with a status of "void"
     *  - active Fetches all invoices with a status of "active"
     *  - proforma Fetches all invoices with a status of "proforma"
     *  - to_autodebit Fetches all invoices that are ready to be autodebited
     *      now, and which can be with an active client and payment account to do so
     *  - pending_autodebit Fetches all invoice that are set to be
     *      autodebited in the future, and which have an active client and payment account to do so with
     *  - to_print Fetches all paper invoices set to be printed
     *  - printed Fetches all paper invoices that have been set as printed
     *  - pending Fetches all active invoices that have not been billed for yet
     *  - to_deliver Fetches all invoices set to be delivered by a method
     *       other than paper (i.e. deliverable invoices not in the list of those "to_print")
     *  - all Fetches all invoices
     * @return float The amount due
     */
    public function amountDue($client_id, $currency, $status = 'open')
    {
        $this->Record = $this->getInvoices(['client_id' => $client_id, 'status' => $status]);
        $inv_subquery = $this->Record->where('invoices.currency', '=', $currency)->get();
        $inv_values = $this->Record->values;
        $this->Record->reset();

        $fields = ['SUM(IFNULL(open_invoices.due,0))' => 'total'];
        $amount = $this->Record->appendValues($inv_values)
            ->select($fields, false)
            ->from([$inv_subquery => 'open_invoices'])
            ->fetch();

        return ($amount && $amount->total !== null ? $amount->total : 0);
    }

    /**
     * Returns an array of all currency the given client has been invoiced in
     *
     * @param int $client_id
     * @param string $status The status type of the invoices to fetch
     *  (optional, default 'active') - ['open','closed','past_due','draft',
     *  'proforma','void','active'] (or 'all' for all active/draft/proforma/void)
     * @return array An array of stdClass objects, each representing a currency in use
     */
    public function invoicedCurrencies($client_id, $status = 'active')
    {
        $this->Record->select(['invoices.currency'])->from('invoices')->
            where('client_id', '=', $client_id)->group('invoices.currency');

        switch ($status) {
            case 'closed':
                // Get closed invoices
                $negate = true;
                // no break
            case 'open':
                // Get open invoices

                // Check the date is open/closed
                $this->Record->where('invoices.date_closed', ($negate ? '!=' : '='), null)->
                    where('invoices.status', 'in', ['active', 'proforma']);
                break;
            case 'past_due':
                // Get past due invoices

                // Check date is past due and invoice is not closed
                $this->Record->where('invoices.date_due', '<', $this->dateToUtc(date('c')))->
                    where('invoices.date_closed', '=', null)->
                    where('invoices.status', 'in', ['active', 'proforma']);
                break;
            case 'all':
                // Do not filter on status
                break;
            default:
                // Get invoices by status (active, draft, proforma, void)
                $this->Record->where('invoices.status', '=', $status);
                break;
        }

        return $this->Record->fetchAll();
    }

    /**
     * Calculates the subtotal of the given invoice ID
     *
     * @deprecated since v4.6.0 - use \Blesta\Core\Pricing\
     *
     * @param int $invoice_id The ID of the invoice to calculate the subtotal of
     * @return float The subtotal of the invoice
     */
    public function getSubtotal($invoice_id)
    {
        $totals = $this->getTotals($invoice_id);

        return $totals->subtotal;
    }

    /**
     * Calculates the total (subtotal + tax) of the given invoice ID
     *
     * @deprecated since v4.6.0 - use \Blesta\Core\Pricing\
     *
     * @param int $invoice_id The ID of the invoice to calculate the total of
     * @return float The total of the invoice
     */
    public function getTotal($invoice_id)
    {
        $totals = $this->getTotals($invoice_id);

        return $totals->total;
    }

    /**
     * Creates a list of line items from the given set of items, discounts, and taxes
     * @see Invoices::getItemTotals
     *
     * @deprecated since v4.1.0
     *
     * @param array $vars A key/value array, including:
     *
     *  - items An array of stdClass items from which to create the line items, each including:
     *      - description The line item description to set
     *      - price The unit price of the line item
     *      - qty The line quantity
     *      - discounts An array of stdClass discounts applied to the line, including:
     *          - description The coupon description
     *          - amount The amount of discount
     *          - type The type of discount
     *          - total The total amount discounted from the line
     *      - taxes An array of stdClass taxes applied to the line, including:
     *          - description The tax description
     *          - amount The amount of tax
     *          - type The type of tax
     *          - total The total amount taxed from the line
     * @return array An array of line items, each including:
     *
     *  - service_id The ID of the service to which the line belongs
     *  - description The line description
     *  - qty The line quantity
     *  - amount The unit price
     *  - order The line item order relative to other line items
     *  - tax True or false, whether the item is taxable
     */
    public function makeLinesFromItems(array $vars)
    {
        $lines = [];
        $order = 0;
        $items = (isset($vars['items']) ? (array)$vars['items'] : []);

        foreach ($items as $item) {
            $taxable = !empty($item->taxes);
            $service_id = null;

            // Add the line item
            $lines[] = $this->makeLineItem($item->description, $item->qty, $item->price, $taxable, $service_id, $order);
            $order++;

            // Add a line item for each discount
            $discounts = (!empty($item->discounts) ? (array)$item->discounts : []);
            foreach ($discounts as $discount) {
                $lines[] = $this->makeLineItem(
                    $discount->description,
                    1,
                    $discount->total,
                    $taxable,
                    $service_id,
                    $order
                );
                $order++;
            }
        }

        return $lines;
    }

    /**
     * Creates an array representing a single line item
     *
     * @deprecated since v4.1.0
     *
     * @param string $description The line item description
     * @param mixed $qty The line item quantity
     * @param float $price The unit price
     * @param bool $taxable True if the item is taxable, or false otherwise
     * @param int $service_id The ID of the service the line item should be assigned to
     * @param int $order The order of the line item
     * @return An array representing a line item
     */
    private function makeLineItem($description, $qty, $price, $taxable, $service_id, $order)
    {
        return [
            'service_id' => $service_id,
            'description' => $description,
            'qty' => $qty,
            'amount' => $price,
            'order' => $order,
            'tax' => $taxable
        ];
    }

    /**
     * Retrieves a list of items and their totals
     *
     * @deprecated since 4.0.0 - Use Blesta\Core\Pricing\...
     *
     * @param array $items An array of items including:
     *
     *  - price The unit price of the item
     *  - qty The item quantity (optional, default 1)
     *  - description The item description (optional)
     * @param array $discounts An array of applicable discounts including (optional):
     *
     *  - amount The discount amount
     *  - type The discount type ('amount' or 'percent')
     *  - description The discount description (optional)
     *  - apply An array of item indexes to which the discount applies (optional, defaults to all)
     * @param array $taxes An array containing arrays of applicable taxes where each array group
     *  represents taxes to cascade on each other; including (optional):
     *
     *  - amount The tax amount
     *  - type The tax type ('exclusive' or 'inclusive')
     *  - description The tax description (optional)
     *  - apply An array of item indexes to which the tax applies (optional, defaults to all)
     * @return array An array containing:
     *
     *  - items An array of items and pricing information about each item
     *  - totals An array of pricing information about all items
     *  - discounts An array of discounts
     *  - taxes An array of taxes
     */
    public function getItemTotals(array $items, array $discounts = [], array $taxes = [])
    {
        // Create an ItemPriceCollection from the given items
        $collection = $this->makeItemCollection($items, $discounts, $taxes);

        $totals = (object)[
            'subtotal' => $collection->subtotal(),
            'total' => $collection->total(),
            'total_after_tax' => $collection->totalAfterTax(),
            'total_after_discount' => $collection->totalAfterDiscount(),
            'tax_amount' => $collection->taxAmount(),
            'discount_amount' => $collection->discountAmount(),
        ];

        return [
            'items' => $this->itemCollectionItems($collection),
            'totals' => $totals,
            'discounts' => $this->itemCollectionDiscounts($collection),
            'taxes' => $this->itemCollectionTaxes($collection)
        ];
    }

    /**
     * Retrieves a list of all item prices in a collection
     *
     * @deprecated since 4.0.0 - Use Blesta\Core\Pricing\...
     *
     * @param ItemPriceCollection $collection The collection from which to fetch all items
     * @return array An array of stdClass objects representing each item, including:
     *
     *  - description The item description
     *  - price The item unit price
     *  - qty The item quantity
     *  - subtotal The item subtotal
     *  - total The item total
     *  - total_after_tax The item total including tax
     *  - total_after_discount The item total after discount
     *  - tax_amount The total item tax
     *  - discount_amount The total item discount
     */
    private function itemCollectionItems(ItemPriceCollection $collection)
    {
        // Set item information that does not require discounts to be reset
        $all_items = [];
        foreach ($collection as $key => $item) {
            $all_items[$key] = (object)[
                'description' => $item->getDescription(),
                'price' => $item->price(),
                'qty' => $item->qty(),
                'subtotal' => $item->subtotal()
            ];
        }
        $collection->resetDiscounts();

        // Determine each total amount individually from the collection, as discounts
        // may apply to multiple items in the collection.
        // Thus, discounts must be reset at the collection level rather than at the item
        // level to avoid erroneously applying discount amounts
        foreach ($collection as $key => $item) {
            $all_items[$key]->total = $item->total();
        }
        $collection->resetDiscounts();

        foreach ($collection as $key => $item) {
            $all_items[$key]->total_after_tax = $item->totalAfterTax();
        }
        $collection->resetDiscounts();

        foreach ($collection as $key => $item) {
            $all_items[$key]->total_after_discount = $item->totalAfterDiscount();
        }
        $collection->resetDiscounts();

        foreach ($collection as $key => $item) {
            $all_items[$key]->tax_amount = $item->taxAmount();
        }
        $collection->resetDiscounts();

        foreach ($collection as $key => $item) {
            $all_items[$key]->discount_amount = $item->discountAmount();

            // Include fields for discounts and taxes per item
            $all_items[$key]->discounts = [];
            $all_items[$key]->taxes = [];
        }
        $collection->resetDiscounts();

        foreach ($collection->discounts() as $discount) {
            foreach ($collection as $key => $item) {
                if (in_array($discount, $item->discounts(), true)) {
                    // Discounts have a negative total
                    $all_items[$key]->discounts[] = (object)[
                        'description' => $discount->getDescription(),
                        'amount' => $discount->amount(),
                        'type' => $discount->type(),
                        'total' => -1 * $item->discountAmount($discount)
                    ];
                }
            }
        }
        $collection->resetDiscounts();

        foreach ($collection->taxes() as $tax) {
            foreach ($collection as $key => $item) {
                if (in_array($tax, $item->taxes(), true)) {
                    $all_items[$key]->taxes[] = (object)[
                        'description' => $tax->getDescription(),
                        'amount' => $tax->amount(),
                        'type' => $tax->type(),
                        'total' => $item->taxAmount($tax)
                    ];
                }
            }
        }
        $collection->resetDiscounts();

        return array_values($all_items);
    }

    /**
     * Retrieves a list of all discounts on a collection
     *
     * @deprecated since 4.0.0 - Use Blesta\Core\Pricing\...
     *
     * @param ItemPriceCollection $collection The collection from which to fetch all discounts
     * @return array An array of stdClass objects representing each discount, including:
     *
     * - description The discount description
     * - amount The discount amount
     * - type The discount type
     * - total The total amount actually discounted
     */
    private function itemCollectionDiscounts(ItemPriceCollection $collection)
    {
        $discount_items = [];

        foreach ($collection->discounts() as $discount) {
            $discount_items[] = (object)[
                'description' => $discount->getDescription(),
                'amount' => $discount->amount(),
                'type' => $discount->type(),
                'total' => $collection->discountAmount($discount)
            ];
        }
        $collection->resetDiscounts();

        return $discount_items;
    }

    /**
     * Retrieves a list of all taxes on a collection
     *
     * @deprecated since 4.0.0 - Use Blesta\Core\Pricing\...
     *
     * @param ItemPriceCollection $collection The collection from which to fetch all taxes
     * @return array An array of stdClass objects representing each tax, including:
     *
     *  - description The tax description
     *  - amount The tax amount
     *  - type The tax type
     *  - total The total amount actually taxed
     */
    private function itemCollectionTaxes(ItemPriceCollection $collection)
    {
        $tax_items = [];

        foreach ($collection->taxes() as $tax) {
            $tax_items[] = (object)[
                'description' => $tax->getDescription(),
                'amount' => $tax->amount(),
                'type' => $tax->type(),
                'total' => $collection->taxAmount($tax)
            ];
        }
        $collection->resetDiscounts();

        return $tax_items;
    }

    /**
     * Creates an ItemPriceCollection from the given items, discounts, and taxes
     *
     * @deprecated since 4.0.0 - Use Blesta\Core\Pricing\...
     *
     * @param array $items An array of items including:
     *
     *  - price The unit price of the item
     *  - qty The item quantity (optional, default 1)
     *  - description The item description (optional)
     * @param array $discounts An array of applicable discounts including (optional):
     *
     *  - amount The discount amount
     *  - type The discount type ('amount' or 'percent')
     *  - description The discount description (optional)
     *  - apply An array of item indexes to which the discount applies (optional, defaults to all)
     * @param array $taxes An array containing arrays of applicable taxes where each array group
     *  represents taxes to cascade on each other; including (optional):
     *
     *  - amount The tax amount
     *  - type The tax type ('exclusive' or 'inclusive')
     *  - description The tax description (optional)
     *  - apply An array of item indexes to which the tax applies (optional, defaults to all)
     * @return ItemPriceCollection An ItemPriceCollection object of all the items
     */
    private function makeItemCollection(array $items, array $discounts = [], array $taxes = [])
    {
        // Fetch tax and discount prices
        $discount_prices = $this->getDiscountPrices($discounts);
        $tax_prices = $this->getTaxPrices($taxes);

        // Create a collection to assign the items to
        $factory = $this->pricingFactory();
        $collection = $factory->itemPriceCollection();

        // Build a list of all items
        foreach ($items as $key => $item) {
            $amount = (isset($item['price']) ? $item['price'] : 0);
            $qty = (isset($item['qty']) ? $item['qty'] : 1);
            $description = (isset($item['description']) ? $item['description'] : '');

            try {
                $item_price = $factory->itemPrice($amount, $qty);
            } catch (Exception $ex) {
                // Invalid data provided
                continue;
            }

            // Set the item description
            $item_price->setDescription($description);

            // Assign discounts to the item
            foreach ($discount_prices as $discount) {
                if (empty($discount['apply']) || in_array($key, $discount['apply'])) {
                    $item_price->setDiscount($discount['price']);
                }
            }

            // Assign taxes to the item
            foreach ($tax_prices as $tax) {
                if (empty($tax['apply']) || in_array($key, $tax['apply'])) {
                    try {
                        call_user_func_array([$item_price, 'setTax'], $tax['prices']);
                    } catch (Exception $ex) {
                        // Taxes could not be included
                        continue;
                    }
                }
            }

            $collection->append($item_price);
        }

        return $collection;
    }

    /**
     * Builds a list of TaxPrice objects from the given taxes
     * @see ::makeItemCollection
     *
     * @deprecated since 4.0.0 - Use Blesta\Core\Pricing\...
     *
     * @param array $taxes An array of tax information including:
     *
     *  - amount The tax amount
     *  - type The tax type ('inclusive', 'exclusive')
     *  - description The description (optional)
     *  - apply An array of item indexes to which the tax applies (optional)
     * @return array An array containing arrays of:
     *
     *  - prices An array of TaxPrice objects
     *  - apply An array of item indexes to which the prices apply
     */
    private function getTaxPrices(array $taxes)
    {
        $pricing = [];
        $factory = $this->pricingFactory();

        foreach ($taxes as $tax_group) {
            $tax_prices = [];
            $apply = [];
            foreach ($tax_group as $price) {
                $amount = (isset($price['amount']) ? $price['amount'] : 0);
                $type = (isset($price['type']) ? $price['type'] : null);
                $description = (isset($price['description']) ? $price['description'] : '');

                // Create the discount price
                try {
                    $tax = $factory->taxPrice($amount, $type);
                } catch (Exception $ex) {
                    // Invalid data provided
                    continue;
                }

                // Set the description
                $tax->setDescription($description);

                $tax_prices[] = $tax;

                // If the taxes cascade, they all apply to the same items
                $apply = (!empty($price['apply']) ? $price['apply'] : []);
            }

            // Include the taxes in the list of tax prices
            if (!empty($tax_prices)) {
                $pricing[] = ['prices' => $tax_prices, 'apply' => $apply];
            }
        }

        return $pricing;
    }

    /**
     * Builds a list of DiscountPrice objects from the given discounts
     * @see ::makeItemCollection
     *
     * @deprecated since 4.0.0 - Use Blesta\Core\Pricing\...
     *
     * @param array $discounts An array of discount information including:
     *
     *  - amount The tax amount
     *  - type The discount type ('amount', 'percent')
     *  - description The description (optional)
     *  - apply An array of item indexes to which the discount applies (optional)
     * @return array An array containing arrays of:
     *
     *  - price A DiscountPrice object
     *  - apply An array of item indexes to which the price applies
     */
    private function getDiscountPrices(array $discounts)
    {
        $pricing = [];
        $factory = $this->pricingFactory();

        foreach ($discounts as $price) {
            $amount = (isset($price['amount']) ? $price['amount'] : 0);
            $type = (isset($price['type']) ? $price['type'] : null);
            $description = (isset($price['description']) ? $price['description'] : '');

            // Create the discount price
            try {
                $discount = $factory->discountPrice($amount, $type);
            } catch (Exception $ex) {
                // Invalid data provided
                continue;
            }

            // Set the description
            $discount->setDescription($description);

            // Include the discount in the list of discount prices
            $apply = (!empty($price['apply']) ? $price['apply'] : []);
            $pricing[] = ['price' => $discount, 'apply' => (array)$apply];
        }

        return $pricing;
    }

    /**
     * Retrieves an instance of the PricingFactory
     *
     * @deprecated since 4.0.0 - Use Blesta\Core\Pricing\...
     *
     * @return \PricingFactory The PricingFactory
     */
    private function pricingFactory()
    {
        return new PricingFactory();
    }

    /**
     * Calculates the total paid on the given invoice ID
     *
     * @param int $invoice_id The ID of the invoice to calculate the total paid on
     * @return float The total paid on the invoice
     */
    public function getPaid($invoice_id)
    {
        $total_paid = 0;

        $paid = $this->Record->select(['SUM(IFNULL(transaction_applied.amount,0))' => 'total'], false)->
            from('transaction_applied')->
            innerJoin('transactions', 'transaction_applied.transaction_id', '=', 'transactions.id', false)->
            where('transaction_applied.invoice_id', '=', $invoice_id)->
            where('transactions.status', '=', 'approved')->
            group('transaction_applied.invoice_id')->fetch();
        if ($paid) {
            $total_paid = $paid->total;
        }
        return $total_paid;
    }

    /**
     * Retrieves a list of invoice statuses and language
     *
     * @return array A key/value array of statuses and their language
     */
    public function getStatuses()
    {
        return [
            'active' => Language::_('Invoices.status.active', true),
            'proforma' => Language::_('Invoices.status.proforma', true),
            'draft' => Language::_('Invoices.status.draft', true),
            'void' => Language::_('Invoices.status.void', true)
        ];
    }

    /**
     * Fetches the available invoice types
     *
     * @return array A key/value array of invoice types
     */
    public function getTypes()
    {
        return [
            'standard' => Language::_('Invoices.types.standard', true),
            'proforma' => Language::_('Invoices.types.proforma', true)
        ];
    }

    /**
     * Fetches the available invoice cache methods
     *
     * @return array A key/value array of invoice types
     */
    public function getCacheMethods()
    {
        return [
            'none' => Language::_('Invoices.cache_methods.none', true),
            'json' => Language::_('Invoices.cache_methods.json', true),
            'json_pdf' => Language::_('Invoices.cache_methods.json_pdf', true)
        ];
    }

    /**
     * Validates the invoice 'status' field
     *
     * @param string $status The status to check
     * @return bool True if validated, false otherwise
     */
    public function validateStatus($status)
    {
        switch ($status) {
            case 'active':
            case 'proforma':
            case 'draft':
            case 'void':
                return true;
        }
        return false;
    }

    /**
     * Validates that the given invoice is a draft invoice
     *
     * @param int $invoice_id The invoice ID
     * @return bool True if the given invoice is a draft, and false otherwise
     */
    public function validateIsDraft($invoice_id)
    {
        $count = $this->Record->select('id')->from('invoices')->where('id', '=', $invoice_id)->
            where('status', '=', 'draft')->numResults();

        return ($count > 0);
    }

    /**
     * Validates that the delivery options match the available set
     *
     * @param array $methods A key=>value array of delivery methods (e.g. "email"=>true)
     * @return bool True if at least one delivery method was given, false otherwise
     */
    public function validateDeliveryMethods(array $methods = null)
    {
        $all_methods = ['email', 'paper', 'interfax', 'postalmethods'];

        if (!empty($methods)) {
            foreach ($methods as $key => $value) {
                // If a method was given that doesn't match, the value is invalid
                if (!in_array($value, $all_methods)) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Checks if the given invoice has any payments applied to it
     *
     * @param int $invoice_id The invoice ID to check
     * @return bool True if the invoice has payments applied to it, false otherwise
     */
    public function validateAmountApplied($invoice_id)
    {
        $num_payments = $this->Record->select('transaction_id')->from('transaction_applied')->
            where('invoice_id', '=', $invoice_id)->numResults();

        if ($num_payments > 0) {
            return true;
        }
        return false;
    }

    /**
     * Validates that the given date due is on or after the date billed
     *
     * @param string $date_due The date the invoice is due
     * @param string $date_billed The date the invoice is billed
     * @return bool True if the date due is on or after the date billed, false otherwise
     */
    public function validateDateDueAfterDateBilled($date_due, $date_billed)
    {
        if (!empty($date_due) && !empty($date_billed)) {
            if (strtotime($date_due) < strtotime($date_billed)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Returns the rule set for adding/editing invoices
     *
     * @param array $vars The input vars
     * @return array Invoice rules
     */
    private function getRules(array $vars)
    {
        $rules = [
            // Invoice rules
            'id_format' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('Invoices.!error.id_format.empty')
                ],
                'length' => [
                    'rule' => ['maxLength', 64],
                    'message' => $this->_('Invoices.!error.id_format.length')
                ]
            ],
            'id_value' => [
                'valid' => [
                    'rule' => [[$this, 'isInstanceOf'], 'Record'],
                    'message' => $this->_('Invoices.!error.id_value.valid')
                ]
            ],
            'client_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'clients'],
                    'message' => $this->_('Invoices.!error.client_id.exists')
                ]
            ],
            'date_billed' => [
                'format' => [
                    'rule' => 'isDate',
                    'message' => $this->_('Invoices.!error.date_billed.format'),
                    'post_format' => [[$this, 'dateToUtc']]
                ]
            ],
            'date_due' => [
                'format' => [
                    'rule' => 'isDate',
                    'message' => $this->_('Invoices.!error.date_due.format')
                ],
                'after_billed' => [
                    'rule' => [[$this, 'validateDateDueAfterDateBilled'], (isset($vars['date_billed']) ? $vars['date_billed'] : null)],
                    'message' => $this->_('Invoices.!error.date_due.after_billed'),
                    'post_format' => [[$this, 'dateToUtc']]
                ]
            ],
            'date_closed' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'isDate',
                    'message' => $this->_('Invoices.!error.date_closed.format'),
                    'post_format' => [[$this, 'dateToUtc']]
                ]
            ],
            'date_autodebit' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'isDate',
                    'message' => $this->_('Invoices.!error.date_autodebit.format'),
                    'post_format' => [[$this, 'dateToUtc']]
                ]
            ],
            'autodebit' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['in_array', ['0', '1']],
                    'message' => $this->_('Invoices.!error.autodebit.valid')
                ]
            ],
            'status' => [
                'format' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateStatus']],
                    'message' => $this->_('Invoices.!error.status.format')
                ]
            ],
            'currency' => [
                'length' => [
                    //'if_set' => true,
                    'rule' => ['matches', '/^[A-Z]{3}$/'],
                    'message' => $this->_('Invoices.!error.currency.length')
                ]
            ],
            // Invoice line item rules
            'lines[][service_id]' => [
                'exists' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateExists'], 'id', 'services'],
                    'message' => $this->_('Invoices.!error.lines[][service_id].exists')
                ]
            ],
            'lines[][description]' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('Invoices.!error.lines[][description].empty')
                ]
            ],
            'lines[][qty]' => [
                /* unnecessary error
                'format' => array(
                    'if_set' => true,
                    'rule' => "is_numeric",
                    'message' => $this->_("Invoices.!error.lines[][qty].format")
                ),
                */
                'minimum' => [
                    'pre_format' => [[$this, 'primeQuantity']],
                    'if_set' => true,
                    'rule' => 'is_scalar',
                    'message' => $this->_('Invoices.!error.lines[][qty].minimum')
                ]
            ],
            'lines[][amount]' => [
                'format' => [
                    'if_set' => true,
                    'pre_format' => [[$this, 'currencyToDecimal'], $vars['currency'], 4],
                    'rule' => 'is_numeric',
                    'message' => $this->_('Invoices.!error.lines[][amount].format')
                ]
            ],
            'lines[][tax]' => [
                'format' => [
                    'if_set' => true,
                    'pre_format' => [[$this, 'strToBool']],
                    'rule' => 'is_bool',
                    'message' => $this->_('Invoices.!error.lines[][tax].format')
                ]
            ],
            // Invoice delivery rules
            'delivery' => [
                'exists' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateDeliveryMethods']],
                    'message' => $this->_('Invoices.!error.delivery.exists')
                ]
            ]
        ];
        return $rules;
    }

    /**
     * Returns the rule set for adding/editing recurring invoices
     *
     * @param array $vars The input vars
     * @return array Invoice rules
     */
    private function getRecurringRules(array $vars)
    {
        $rules = [
            'client_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'clients'],
                    'message' => $this->_('Invoices.!error.client_id.exists')
                ]
            ],
            'term' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'is_numeric',
                    'message' => $this->_('Invoices.!error.term.format')
                ],
                'bounds' => [
                    'if_set' => true,
                    'rule' => ['between', 1, 65535],
                    'message' => $this->_('Invoices.!error.term.bounds')
                ]
            ],
            'period' => [
                'format' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validatePeriod']],
                    'message' => $this->_('Invoices.!error.period.format')
                ]
            ],
            'duration' => [
                'format' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateDuration']],
                    'message' => $this->_('Invoices.!error.duration.format')
                ]
            ],
            'currency' => [
                'length' => [
                    'rule' => ['matches', '/^[A-Z]{3}$/'],
                    'message' => $this->_('Invoices.!error.currency.length')
                ]
            ],
            'date_renews' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'isDate',
                    'message' => $this->_('Invoices.!error.date_renews.format'),
                    'post_format' => [[$this, 'dateToUtc']]
                ]
            ],
            'date_last_renewed' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'isDate',
                    'message' => $this->_('Invoices.!error.date_last_renewed.format'),
                    'post_format' => [[$this, 'dateToUtc']]
                ]
            ],
            'lines[][description]' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('Invoices.!error.lines[][description].empty')
                ]
            ],
            'lines[][qty]' => [
                'minimum' => [
                    'pre_format' => [[$this, 'primeQuantity']],
                    'if_set' => true,
                    'rule' => 'is_scalar',
                    'message' => $this->_('Invoices.!error.lines[][qty].minimum')
                ]
            ],
            'lines[][amount]' => [
                'format' => [
                    'if_set' => true,
                    'pre_format' => [[$this, 'currencyToDecimal'], $vars['currency'], 4],
                    'rule' => 'is_numeric',
                    'message' => $this->_('Invoices.!error.lines[][amount].format')
                ]
            ],
            'lines[][tax]' => [
                'format' => [
                    'if_set' => true,
                    'pre_format' => [[$this, 'strToBool']],
                    'rule' => 'is_bool',
                    'message' => $this->_('Invoices.!error.lines[][tax].format')
                ]
            ],
            // Invoice delivery rules
            'delivery' => [
                'exists' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateDeliveryMethods']],
                    'message' => $this->_('Invoices.!error.delivery.exists')
                ]
            ],
            'autodebit' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['in_array', ['0', '1']],
                    'message' => $this->_('Invoices.!error.autodebit.valid')
                ]
            ]
        ];
        return $rules;
    }

    /**
     * Validates the recurring invoice duration
     *
     * @param mixed $duration An integer idenfying the number of the times the
     *  recurring invoice should recur, null for indefinitely
     * @return bool True if the duration is valid, false otherwise
     */
    public function validateDuration($duration)
    {
        if ($duration == '') {
            return true;
        }
        if (is_numeric($duration) && $duration >= 1 && $duration <= 65535) {
            return true;
        }
        return false;
    }

    /**
     * Validates the recurring invoice period
     *
     * @param string $period The period type
     * @return bool True if validated, false otherwise
     */
    public function validatePeriod($period)
    {
        $periods = $this->getPricingPeriods();

        if (isset($periods[$period])) {
            return true;
        }
        return false;
    }

    /**
     * Checks if the given $field is a reference of $class
     *
     * @param mixed $field The field to check
     * @param mixed $class The class or instance to check against
     * @return bool True if the $field is an instance of $class, or false otherwise
     */
    public function isInstanceOf($field, $class)
    {
        return $field instanceof $class;
    }

    /**
     * Converts quantity to a float, if no qty is set, a value of 1 is assumed.
     *
     * @param mixed $qty The quantity to be primed, may be an integer, float, or fractional string
     * @return float The quanity rounded to 4 decimal places
     */
    public function primeQuantity($qty)
    {
        $qty = trim($qty);
        if ($qty === '') {
            $qty = 1;
        }
        // If qty is not a float or int, process as a fraction string
        if ((string)(float)$qty != $qty) {
            $parts = explode(' ', $qty, 2);
            $f = 0; // The index of the fractional portion of the string in $parts
            // Evaluate whole and fractional portions
            if (count($parts) > 1) {
                $f = 1;
            }

            // Parse the fraction into its parts
            $fract = explode('/', $parts[$f], 2);
            $decimal = 0;

            if (count($fract) == 2) {
                $decimal = (int)$fract[0] / max(1, (int)$fract[1]);
            }

            $qty = ($f > 0 ? (int)$parts[0] : 0) + $decimal;
        }

        $qty = (float)$qty;

        return sprintf('%.4f', $qty);
    }

    /**
     * Retrieves all active tax rules that apply to the given client
     *
     * @param int $client_id The client ID
     * @return array A numerically indexed array of stdClass objects each
     *  representing a tax rule to apply to this client
     */
    public function getTaxRules($client_id)
    {
        return $this->Record->select(['taxes.*'])
            ->select(['TRIM(taxes.amount)+?' => 'amount'], false)
            ->appendValues([0])
            ->from('clients')
            ->innerJoin('client_groups', 'clients.client_group_id', '=', 'client_groups.id', false)
            ->on('contacts.client_id', '=', 'clients.id', false)
            ->on('contacts.contact_type', '=', 'primary')
            ->innerJoin('contacts')
            ->innerJoin('taxes', 'taxes.company_id', '=', 'client_groups.company_id', false)
            ->open()
                ->open()
                    ->where('taxes.country', '=', 'contacts.country', false)
                    ->where('taxes.state', '=', 'contacts.state', false)
                ->close()
                ->open()
                    ->orWhere('taxes.country', '=', 'contacts.country', false)
                    ->where('taxes.state', '=', null)
                ->close()
                ->open()
                    ->orWhere('taxes.country', '=', null)
                    ->where('taxes.state', '=', null)
                ->close()
            ->close()
            ->where('clients.id', '=', $client_id)
            ->where('taxes.status', '=', 'active')
            ->order(['level' => 'ASC'])
            ->group('taxes.level')
            ->fetchAll();
    }

    /**
     * Retrieves all active tax rules that apply to the given company and location
     *
     * @param int $company_id The ID of the company to fetch tax rules on
     * @param string $country The ISO 3166-1 alpha2 country code to fetch tax rules on
     * @param string $state 3166-2 alpha-numeric subdivision code to fetch tax rules on
     * @return array A numerically indexed array of stdClass objects each
     *  representing a tax rule to apply to this company and location
     */
    public function getTaxRulesByLocation($company_id, $country, $state)
    {
        return $this->Record->select(['taxes.*'])
            ->select(['TRIM(taxes.amount)+?' => 'amount'], false)
            ->appendValues([0])
            ->from('taxes')
            ->open()
                ->open()
                    ->where('taxes.country', '=', $country)
                    ->where('taxes.state', '=', $state)
                ->close()
                ->open()
                    ->orWhere('taxes.country', '=', $country)
                    ->where('taxes.state', '=', null)
                ->close()
                ->open()
                    ->orWhere('taxes.country', '=', null)
                    ->where('taxes.state', '=', null)
                ->close()
            ->close()
            ->where('taxes.company_id', '=', $company_id)
            ->where('taxes.status', '=', 'active')
            ->order(['level' => 'ASC'])
            ->group('taxes.level')
            ->fetchAll();
    }

    /**
     * Identifies whether or not the given invoice with its updated line items and deleted items
     * requires tax rules to be updated when saved. This method doesn't check whether the tax
     * rules have been updated, just whether the invoice has been changed such that the updated
     * tax rules would need to be updated. There's no consequence in updating tax when
     * the tax rules have not changed.
     *
     * @param int $invoice_id The ID of the invoice to evaluate
     * @param array $lines An array of line items including:
     *
     *  - id The ID of the line item (if available)
     *  - tax Whether or not the line items is taxable (true/false)
     *  - amount The amount per quantity for the line item
     *  - qty The quantity of the line item
     * @param array $delete_items An array of items to be deleted from the invoice
     * @return bool True if the invoice has been modified in such a way to
     *  warrant updating the tax rules applied, false otherwise
     * @see Invoices::edit()
     */
    private function taxUpdateRequired($invoice_id, $lines, $delete_items)
    {
        $tax_change = false;

        $invoice = $this->get($invoice_id);
        $num_lines = is_array($lines) ? count($lines) : 0;
        $num_delete = is_array($delete_items) ? count($delete_items) : 0;

        // Ensure the invoice exists
        if (!$invoice) {
            return $tax_change;
        }

        // If any new items added or any items removed, taxes must be updated
        if (count($invoice->line_items) != $num_lines || $num_delete > 0) {
            $tax_change = true;
        } else {
            // Ensure that quantity, unit cost, and tax status remain unchanged
            for ($i = 0; $i < $num_lines; $i++) {
                if (isset($lines[$i]['id'])) {
                    for ($j = 0; $j < $num_lines; $j++) {
                        // Ensure tax status remains unchanged
                        if ($invoice->line_items[$j]->id == $lines[$i]['id']) {
                            if ((!$lines[$i]['tax'] && !empty($invoice->line_items[$j]->taxes)) ||
                                ($lines[$i]['tax'] && empty($invoice->line_items[$j]->taxes))) {
                                $tax_change = true;
                                break 2;
                            }

                            // Ensure amount and quantity remain unchanged
                            if ($lines[$i]['amount'] != $invoice->line_items[$j]->amount ||
                                $lines[$i]['qty'] != $invoice->line_items[$j]->qty) {
                                $tax_change = true;
                                break 2;
                            }
                        }
                    }
                }
            }
        }

        return $tax_change;
    }

    /**
     * Creates a Payment Hash that may be used to temporarily authenticate a
     * user's access to pay an invoice, or invoices
     *
     * @param int $client_id The client ID to create the hash for
     * @param int $invoice_id The ID of the invoice to create the hash for
     *  (if null will allow the hash to work for any invoice belonging to the client)
     * @return string A hash built based upon the parameters provided
     */
    public function createPayHash($client_id, $invoice_id)
    {
        return substr($this->systemHash('c=' . $client_id . '|i=' . $invoice_id), -16);
    }

    /**
     * Verifies the Payment Hash is valid
     *
     * @param int $client_id The client ID to verify the hash for
     * @param int $invoice_id The ID of the invoice to verify the hash for
     * @param string $hash The original hash to verify against
     * @return bool True if the hash is valid, false otherwise
     */
    public function verifyPayHash($client_id, $invoice_id, $hash)
    {
        $h = $this->systemHash('c=' . $client_id . '|i=' . $invoice_id);
        return substr($h, -16) == $hash;
    }

    /**
     * Writes an invoice on cache
     *
     * @param int $invoice_id The ID of the invoice to save on cache
     * @param mixed $data The data of the invoice to cache
     * @param string $extension The cache extension (optional, 'json' by default)
     * @param string $language The language of the invoice being saved (optional)
     * @return bool True if the invoice has been saved on cache successfully, void on error
     */
    public function writeCache($invoice_id, $data, $extension = 'json', $language = null)
    {
        if (!isset($this->Companies)) {
            Loader::loadModels($this, ['Companies']);
        }

        if (!isset($this->SettingsCollection)) {
            Loader::loadComponents($this, ['SettingsCollection']);
        }

        // Check if the provided extension is valid
        if (!in_array($extension, $this->cacheExtensions())) {
            return false;
        }

        // Set invoice language
        if (is_null($language)) {
            $language = Configure::get('Language.default');
        }

        // Check if the invoice has been cached previously
        $cached_invoice = $this->fetchCache($invoice_id, $extension, $language);

        if (!empty($cached_invoice)) {
            return true;
        }

        // Fetch company settings
        $company_id = Configure::get('Blesta.company_id');
        $company_settings = $this->SettingsCollection->fetchSettings($this->Companies, $company_id);

        // Create the cache folder if does not exists
        $cache = rtrim($company_settings['uploads_dir'], DS) . DS . $company_id
            . DS . 'invoices' . DS . md5('invoice_' . $invoice_id . $language) . '.' . $extension;
        $cache_dir = dirname($cache);

        if (!file_exists($cache_dir)) {
            mkdir($cache_dir, 0755, true);
        }

        // Compress the data
        if (
            $extension == 'pdf'
            && $company_settings['inv_cache_compress'] == 'true'
            && function_exists('gzcompress')
        ) {
            // Set the compress level to 4 as it offers the best performance/compression ratio
            $data = gzcompress($data, 4);
        }

        // Encode JSON data
        if (!is_scalar($data) && $extension == 'json') {
            $data = json_encode((object) $data, JSON_PRETTY_PRINT);
        }

        // Save output to cache file
        file_put_contents($cache, $data);

        return true;
    }

    /**
     * Updates an invoice on cache
     *
     * @param int $invoice_id The ID of the invoice to save on cache
     * @param mixed $data The data of the invoice to cache (optional, if not provided
     *  the invoice data will be cached the next time the invoice is rendered)
     * @param string $extension The cache extension (optional, 'json' by default)
     * @return bool True if the invoice has been saved on cache successfully, void on error
     */
    public function updateCache($invoice_id, $data = null, $extension = 'json')
    {
        $this->clearCache($invoice_id, $extension);

        if (!empty($data)) {
            if ($extension == 'json') {
                if (!isset($data->client)) {
                    Loader::loadModels($this, ['Clients']);
                    $data->client = $this->Clients->get($data->client_id);
                }

                if (!isset($data->billing)) {
                    Loader::loadModels($this, ['Contacts', 'Countries']);

                    // Fetch the contact to which invoices should be addressed
                    if (!($billing = $this->Contacts->get((int)$data->client->settings['inv_address_to']))
                        || $billing->client_id != $data->client_id
                    ) {
                        $billing = $this->Contacts->get($data->client->contact_id);
                    }

                    $data->billing = $billing;
                    $data->billing->country = $this->Countries->get($billing->country);
                }

                if (!isset($data->applied_transactions)) {
                    Loader::loadModels($this, ['Transactions']);
                    $data->applied_transactions = $this->Transactions->getApplied(null, $data->id);
                }

                if (!isset($data->company_settings)) {
                    $data->company_settings = $data->client->settings;
                }

                if (!isset($data->company)) {
                    Loader::loadModels($this, ['Companies']);
                    $data->company = $this->Companies->get($data->client->company_id);
                }
            }


            $this->writeCache($invoice_id, $data, $extension);
        }
    }

    /**
     * Fetch a cached invoice
     *
     * @param int $invoice_id The ID of the invoice to fetch
     * @param string $extension The cache extension (optional, 'json' by default)
     * @param string $language The language of the invoice to fetch (optional)
     * @return mixed An object containing the invoice data for JSON, a stream of binary data for PDF and false on error
     */
    public function fetchCache($invoice_id, $extension = 'json', $language = null)
    {
        if (!isset($this->Companies)) {
            Loader::loadModels($this, ['Companies']);
        }

        if (!isset($this->SettingsCollection)) {
            Loader::loadComponents($this, ['SettingsCollection']);
        }

        // Set invoice language
        if (is_null($language)) {
            $language = Configure::get('Language.default');
        }

        // Fetch the data
        $company_id = Configure::get('Blesta.company_id');
        $uploads_dir = $this->SettingsCollection->fetchSetting($this->Companies, $company_id, 'uploads_dir');
        $cache = rtrim($uploads_dir['value'], DS) . DS . $company_id
            . DS . 'invoices' . DS . md5('invoice_' . $invoice_id . $language) . '.' . $extension;

        // If exists a cached invoice saved before introducing the $language parameter, use that one instead
        $old_cache = rtrim($uploads_dir['value'], DS) . DS . $company_id
            . DS . 'invoices' . DS . md5('invoice_' . $invoice_id) . '.' . $extension;

        if (!file_exists($cache) && file_exists($old_cache)) {
            $cache = $old_cache;
        }

        // Fetch company settings
        $company_settings = $this->SettingsCollection->fetchSettings($this->Companies, $company_id);

        if (file_exists($cache)) {
            $data = file_get_contents($cache);

            if ($extension == 'pdf' && function_exists('gzuncompress')) {
                try {
                    $uncompressed_data = gzuncompress($data);

                    if ($uncompressed_data !== false) {
                        $data = $uncompressed_data;

                        if ($company_settings['inv_cache_compress'] !== 'true') {
                            // Re-save the uncompressed cache
                            $this->clearCache($invoice_id, $extension, $language);
                            $this->writeCache($invoice_id, $data, $extension, $language);
                        }
                    }
                } catch (Throwable $e) {
                    // The PDF is not compressed, nothing to do
                }
            } elseif ($extension == 'json') {
                $data = (object) json_decode($data);

                if (isset($data->client->settings)) {
                    $data->client->settings = (array) $data->client->settings;
                }

                if (isset($data->company_settings)) {
                    $data->company_settings = (array) $data->company_settings;
                }
            }

            return $data;
        } else {
            return false;
        }
    }

    /**
     * Clears the invoice cache
     *
     * @param int $invoice_id The ID of the invoice to clear
     * @param string $extension The cache extension (optional, 'json' by default)
     * @param string $language The language of the invoice to clear (optional)
     * @return bool True if the invoice cache has been deleted successfully, false otherwise
     */
    public function clearCache($invoice_id, $extension = 'json', $language = null)
    {
        Loader::loadModels($this, ['Languages']);
        $company_id = Configure::get('Blesta.company_id');
        $uploads_dir = $this->SettingsCollection->fetchSetting($this->Companies, $company_id, 'uploads_dir');

        // If exists a cached invoice saved before introducing the $language parameter, delete that one instead
        $old_cache = rtrim($uploads_dir['value'], DS) . DS . $company_id
            . DS . 'invoices' . DS . md5('invoice_' . $invoice_id) . '.' . $extension;
        if (is_file($old_cache)) {
            @unlink($old_cache);

            return true;
        }

        $cache_cleared = false;
        $languages = $this->Languages->getAll($company_id);
        foreach ($languages as $language_obj) {
            // Set invoice language
            if (!is_null($language) && $language_obj->code != $language) {
                continue;
            }

            $cache = rtrim($uploads_dir['value'], DS) . DS . $company_id
                . DS . 'invoices' . DS . md5('invoice_' . $invoice_id . $language_obj->code) . '.' . $extension;

            if (is_file($cache)) {
                @unlink($cache);

                $cache_cleared = true;
            }
        }

        return $cache_cleared;
    }

    /**
     * Returns an array of the supported cache extensions
     *
     * @return array A numerically indexed array, with the supported extensions
     */
    public function cacheExtensions()
    {
        return ['json', 'pdf'];
    }
}
