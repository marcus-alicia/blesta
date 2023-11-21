<?php
/**
 * Service Invoice
 * This is an association between services and the invoices created for adding or renewing them.
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2017, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ServiceInvoices extends AppModel
{
    /**
     * Initialize language
     */
    public function __construct()
    {
        parent::__construct();
        Language::loadLang(['service_invoices']);
    }

    /**
     * Adds the association between the given service and an invoice created for its addition or renewal
     *
     * @param array $vars An array of information including:
     *
     *  - service_id The service ID
     *  - invoice_id The ID of the invoice
     *  - maximum_attempts The maximum number of times the service will be reattempted to be renewed (optional)
     */
    public function add(array $vars)
    {
        $this->Input->setRules($this->getRules($vars));

        Loader::loadModels($this, ['Services', 'Clients']);

        // Get 'service_renewal_attempts' setting for the client
        if (!isset($vars['maximum_attempts']) && ($service = $this->Services->get($vars['service_id'] ?? null))) {
            $renewal_attempts = $this->Clients->getSetting(
                    $service->client_id,
                    'service_renewal_attempts'
                ) ?? null;

            if (isset($renewal_attempts->value) && is_numeric($renewal_attempts->value)) {
                $vars['maximum_attempts'] = $renewal_attempts->value;
            }
        }

        if ($this->Input->validates($vars)) {
            $fields = ['service_id', 'invoice_id', 'failed_attempts', 'maximum_attempts'];

            // Ignore duplicate inserts by simply updating the columns of the primary key
            $this->Record->duplicate('service_id', '=', $vars['service_id'])
                ->duplicate('invoice_id', '=', $vars['invoice_id'])
                ->insert('service_invoices', $vars, $fields);
        }
    }

    /**
     * Deletes the association between the given service and invoices created for its addition or renewal
     *
     * @param int $service_id The ID of the service
     * @param int $invoice_id The ID of a particular invoice to delete for
     */
    public function delete($service_id, $invoice_id = null)
    {
        $this->Record->from('service_invoices')->where('service_id', '=', $service_id);

        if ($invoice_id) {
            $this->Record->where('invoice_id', '=', $invoice_id);
        }

        $this->Record->delete();
    }

    /**
     * Retrieves validation rules for ::add
     *
     * @param array $vars An array of input data for validation
     * @return array The input validation rules
     */
    private function getRules(array $vars)
    {
        return [
            'service_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'services'],
                    'message' => $this->_('ServiceInvoices.!error.service_id.exists')
                ]
            ],
            'invoice_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'invoices'],
                    'message' => $this->_('ServiceInvoices.!error.invoice_id.exists')
                ]
            ],
            'failed_attempts' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'is_numeric',
                    'message' => $this->_('ServiceInvoices.!error.failed_attempts.format')
                ]
            ],
            'maximum_attempts' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'is_numeric',
                    'message' => $this->_('ServiceInvoices.!error.maximum_attempts.format')
                ]
            ]
        ];
    }
}
