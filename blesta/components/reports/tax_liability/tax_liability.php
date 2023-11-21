<?php
/**
 * Tax Liability report
 *
 * @package blesta
 * @subpackage blesta.components.reports.tax_liability
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class TaxLiability implements ReportInterface
{

    /**
     * Load language
     */
    public function __construct()
    {
        Loader::loadComponents($this, ['Record', 'SettingsCollection']);

        // Load the language required by this report
        Language::loadLang('tax_liability', null, dirname(__FILE__) . DS . 'language' . DS);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return Language::_('TaxLiability.name', true);
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
        $this->view->setDefaultView('components' . DS . 'reports' . DS . 'tax_liability' . DS);

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
            'id_code' => ['name' => Language::_('TaxLiability.heading.id_code', true)],
            'client_id_code' => ['name' => Language::_('TaxLiability.heading.client_id_code', true)],
            'subtotal' => ['name' => Language::_('TaxLiability.heading.subtotal', true)],
            'taxable_amount' => ['name' => Language::_('TaxLiability.heading.taxable_amount', true)],
            'level1_tax_rate' => ['name' => Language::_('TaxLiability.heading.level1_tax_rate', true)],
            'level1_tax_amount' => ['name' => Language::_('TaxLiability.heading.level1_tax_amount', true)],
            'level1_tax_country' => ['name' => Language::_('TaxLiability.heading.level1_tax_country', true)],
            'level1_tax_state' => ['name' => Language::_('TaxLiability.heading.level1_tax_state', true)],
            'level2_tax_rate' => ['name' => Language::_('TaxLiability.heading.level2_tax_rate', true)],
            'level2_tax_amount' => ['name' => Language::_('TaxLiability.heading.level2_tax_amount', true)],
            'level2_tax_country' => ['name' => Language::_('TaxLiability.heading.level2_tax_country', true)],
            'level2_tax_state' => ['name' => Language::_('TaxLiability.heading.level2_tax_state', true)],
            'cascade' => [
                'name' => Language::_('TaxLiability.heading.cascade', true),
                'format' => ['replace'],
                'options' => [
                    0 => Language::_('TaxLiability.options.field_cascade_false', true),
                    1 => Language::_('TaxLiability.options.field_cascade_true', true)
                ]
            ],
            'currency' => ['name' => Language::_('TaxLiability.heading.currency', true)],
            'date_closed' => [
                'name' => Language::_('TaxLiability.heading.date_closed', true),
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
        $timezone = (array_key_exists('value', $timezone) ? $timezone['value'] : 'UTC');
        $this->Date->setTimezone($timezone, 'UTC');

        $format = 'Y-m-d H:i:s';
        $start_date = null;
        $end_date = null;

        // Format date to UTC
        if (!empty($vars['start_date'])) {
            $start_date = $this->Date->format($format, $vars['start_date'] . ' 00:00:00');
        }
        if (!empty($vars['end_date'])) {
            $end_date = $this->Date->format($format, $vars['end_date'] . ' 23:59:59');
        }

        $invoice_statuses = ['active', 'proforma'];
        $precision = 4;

        // Build subqueries to fetch the level 1 and level 2 taxes
        $fields = [
            '`invoices`.`id`' => 'invoice_id',
            'MAX(`invoice_line_taxes`.`cascade`)' => 'cascade',
            'SUM(`invoice_lines`.`amount` * `invoice_lines`.`qty`)' => 'taxable_amount',
            'IF(`taxes1`.`type` = ?,
                (
                    (SUM(`invoice_lines`.`amount` * `invoice_lines`.`qty`) * IFNULL(`taxes1`.`amount`,?))
                        / (? + IFNULL(`taxes1`.`amount`,?))
                ),
                SUM(`invoice_lines`.`amount` * `invoice_lines`.`qty`) * IFNULL(`taxes1`.`amount`,?) / ?
            )' => 'level1_tax_amount',
            'IFNULL(`taxes1`.`amount`,?)' => 'level1_tax_rate',
            'IFNULL(`taxes1`.`country`,?)' => 'level1_tax_country',
            'IFNULL(`taxes1`.`state`,?)' => 'level1_tax_state',
            'IFNULL(`taxes1`.`type`,?)' => 'type'
        ];
        $values = [
            "inclusive_calculated",
            0,
            100,
            0,
            0,
            100,
            '0.0000',
            null,
            null,
            'exclusive'
        ];
        $this->Record->select($fields, false)
            ->appendValues($values)
            ->from('invoices')
            ->innerJoin('invoice_lines', 'invoice_lines.invoice_id', '=', 'invoices.id', false)
            ->innerJoin('invoice_line_taxes', 'invoice_line_taxes.line_id', '=', 'invoice_lines.id', false)
                ->on('taxes1.level', '=', 1)
            ->innerJoin(['taxes' => 'taxes1'], 'taxes1.id', '=', 'invoice_line_taxes.tax_id', false)
            ->where('invoices.status', 'in', $invoice_statuses)
            ->where('invoices.date_closed', '!=', null);

       if ($start_date) {
            $this->Record->where('invoices.date_closed', '>=', $start_date);
        }
        if ($end_date) {
            $this->Record->where('invoices.date_closed', '<=', $end_date);
        }

        $this->Record->group(['invoices.id']);

        $tax1 = $this->Record->get();
        $tax1_values = $this->Record->values;
        $this->Record->reset();

        // Tax level 2
        $fields = [
            '`invoices`.`id`' => 'invoice_id',
            'IFNULL(`taxes2`.`amount`,?)' => 'level2_tax_rate',
            'IFNULL(`taxes2`.`country`,?)' => 'level2_tax_country',
            'IFNULL(`taxes2`.`state`,?)' => 'level2_tax_state',
            'IFNULL(`taxes2`.`type`,?)' => 'type'
        ];
        $values = [
            '0.0000',
            null,
            null,
            'exclusive'
        ];
        $this->Record->select($fields, false)
            ->appendValues($values)
            ->from('invoices')
            ->innerJoin('invoice_lines', 'invoice_lines.invoice_id', '=', 'invoices.id', false)
            ->innerJoin('invoice_line_taxes', 'invoice_line_taxes.line_id', '=', 'invoice_lines.id', false)
                ->on('taxes2.level', '=', 2)
            ->innerJoin(['taxes' => 'taxes2'], 'taxes2.id', '=', 'invoice_line_taxes.tax_id', false)
            ->where('invoices.status', 'in', $invoice_statuses)
            ->where('invoices.date_closed', '!=', null);

       if ($start_date) {
            $this->Record->where('invoices.date_closed', '>=', $start_date);
        }
        if ($end_date) {
            $this->Record->where('invoices.date_closed', '<=', $end_date);
        }

        $this->Record->group(['invoices.id']);

        $tax2 = $this->Record->get();
        $tax2_values = $this->Record->values;
        $this->Record->reset();

        // Primary query
        $fields = [
            '`invoices`.`id`', '`invoices`.`subtotal`', '`invoices`.`currency`', '`invoices`.`date_closed`',
            'REPLACE(`invoices`.`id_format`, ?, `invoices`.`id_value`)' => 'id_code',
            'REPLACE(`clients`.`id_format`, ?, `clients`.`id_value`)' => 'client_id_code',
            'ROUND(`tax1`.`level1_tax_amount`, ?)' => 'level1_tax_amount',
            '`tax1`.`cascade`',
            'ROUND(IFNULL(`tax1`.`level1_tax_rate`,?), ?)' => 'level1_tax_rate',
            '`tax1`.`level1_tax_country`',
            '`tax1`.`level1_tax_state`',
            'ROUND (
                IF(
                    `tax2`.`type` = ?,
                    IF(
                        `tax1`.`cascade` > ? AND `tax1`.`type` != ?,
                        ((`tax1`.`taxable_amount` + `tax1`.`level1_tax_amount`) * IFNULL(`tax2`.`level2_tax_rate`, ?))
                            / (? + IFNULL(`tax2`.`level2_tax_rate`, ?)),
                        ((`tax1`.`taxable_amount` - `tax1`.`level1_tax_amount`) * IFNULL(`tax2`.`level2_tax_rate`, ?))
                            / (? + IFNULL(`tax2`.`level2_tax_rate`, ?))
                    ),
                    IF(
                        `tax1`.`cascade` > ? AND `tax1`.`type` != ?,
                        ((`tax1`.`taxable_amount` + `tax1`.`level1_tax_amount`) * IFNULL(`tax2`.`level2_tax_rate`, ?)) / ?,
                        (`tax1`.`taxable_amount` * IFNULL(`tax2`.`level2_tax_rate`, ?)) / ?
                    )
                ),
                ?
            )' => 'level2_tax_amount',
            'ROUND(IFNULL(`tax2`.`level2_tax_rate`,?), ?)' => 'level2_tax_rate',
            '`tax2`.`level2_tax_country`',
            '`tax2`.`level2_tax_state`',
            'ROUND(`tax1`.`taxable_amount`, ?)' => 'taxable_amount'
        ];

        $values = [
            $replacement_keys['invoices']['ID_VALUE_TAG'],
            $replacement_keys['clients']['ID_VALUE_TAG'],
            $precision,
            '0.0000',
            $precision,
            "inclusive_calculated",
            0,
            "inclusive_calculated",
            0,
            100,
            0,
            0,
            100,
            0,
            0,
            "inclusive_calculated",
            0,
            100,
            0,
            100,
            $precision,
            '0.0000',
            $precision,
            $precision
        ];

        $this->Record->select($fields, false)
            ->appendValues($values)
            ->from('invoices')
            ->innerJoin('clients', 'clients.id', '=', 'invoices.client_id', false)
                ->on('client_groups.company_id', '=', $company_id)
            ->innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)
            ->innerJoin([$tax1 => 'tax1'], 'tax1.invoice_id', '=', 'invoices.id', false)
            ->appendValues($tax1_values)
            ->leftJoin([$tax2 => 'tax2'], 'tax2.invoice_id', '=', 'invoices.id', false)
            ->appendValues($tax2_values)
            ->where('invoices.status', 'in', $invoice_statuses)
            ->where('invoices.date_closed', '!=', null);

        if ($start_date) {
            $this->Record->where('invoices.date_closed', '>=', $start_date);
        }
        if ($end_date) {
            $this->Record->where('invoices.date_closed', '<=', $end_date);
        }

        $this->Record->group(['invoices.id'])
            ->order(['invoices.date_closed' => 'ASC', 'invoices.id' => 'ASC']);

        return new IteratorIterator($this->Record->getStatement());
    }
}
