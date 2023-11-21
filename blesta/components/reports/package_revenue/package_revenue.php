<?php
/**
 * Package Revenue report
 *
 * @package blesta
 * @subpackage blesta.components.reports.package_revenue
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class PackageRevenue implements ReportInterface
{

    /**
     * Load language
     */
    public function __construct()
    {
        Loader::loadComponents($this, ['Record', 'SettingsCollection']);
        Loader::loadHelpers($this, ['DataStructure', 'Date']);
        Loader::loadModels($this, ['Packages']);
        $this->ArrayHelper = $this->DataStructure->create('Array');

        // Load the language required by this report
        Language::loadLang('package_revenue', null, dirname(__FILE__) . DS . 'language' . DS);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return Language::_('PackageRevenue.name', true);
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
        Loader::loadModels($this, ['Currencies', 'Companies']);

        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('options', 'default');
        $this->view->setDefaultView('components' . DS . 'reports' . DS . 'package_revenue' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        if (!isset($vars['start_date'])) {
            $vars['start_date'] = $this->Date->format('Y-01-01', date('c'));
        }

        if (!isset($vars['end_date'])) {
            $vars['end_date'] = $this->Date->format('Y-12-31', date('c'));
        }

        $this->view->set('vars', (object)$vars);
        $this->view->set('currencies', $this->Form->collapseObjectArray(
            $this->Currencies->getAll($company_id),
            'code',
            'code'
        ));
        $default_currency = $this->Companies->getSetting($company_id, 'default_currency');
        $this->view->set('default_currency', $default_currency ? $default_currency->value : 'USD');

        return $this->view->fetch();
    }

    /**
     * {@inheritdoc}
     */
    public function getKeyInfo()
    {
        $package_statuses = $this->Packages->getStatusTypes();

        return [
            'package_name' => ['name' => Language::_('PackageRevenue.heading.package_name', true)],
            'status' => [
                'name' => Language::_('PackageRevenue.heading.status', true),
                'format' => ['replace'],
                'options' => $package_statuses
            ],
            'module_name' => ['name' => Language::_('PackageRevenue.heading.module_name', true)],
            'service_count' => ['name' => Language::_('PackageRevenue.heading.service_count', true)],
            'package_revenue' => ['name' => Language::_('PackageRevenue.heading.package_revenue', true)],
            'tax' => ['name' => Language::_('PackageRevenue.heading.tax', true)],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAll($company_id, array $vars)
    {
        // Format dates
        $timezone = $this->SettingsCollection->fetchSetting(null, $company_id, 'timezone');
        $timezone = (array_key_exists('value', $timezone) ? $timezone['value'] : 'UTC');
        $this->Date->setTimezone($timezone, 'UTC');

        // Set filter options
        $format = 'Y-m-d H:i:s';
        $start_date = !empty($vars['start_date'])
            ? $this->Date->format($format, $vars['start_date'] . ' 00:00:00')
            : null;
        $end_date = !empty($vars['end_date'])
            ? $this->Date->format($format, $vars['end_date'] . ' 23:59:59')
            : null;
        $currency = !empty($vars['currency']) ? $vars['currency'] : 'USD';

        // Get a list of all level 1 invoice line taxes
        $this->Record->select(
                ['invoice_line_taxes.line_id', 'invoice_line_taxes.cascade', 'taxes.amount', 'taxes.type']
            )
            ->from('invoice_lines')
            ->innerJoin('invoice_line_taxes', 'invoice_line_taxes.line_id', '=', 'invoice_lines.id', false)
            ->on('taxes.level', '=', '1')
            ->innerJoin('taxes', 'taxes.id', '=', 'invoice_line_taxes.tax_id', false);
        $tax1_sub_query = $this->Record->get();
        $tax1_values = $this->Record->values;
        $this->Record->reset();

        // Get a list of all level 2 invoice line taxes
        $this->Record->select(['invoice_line_taxes.line_id', 'taxes.amount', 'taxes.type'])
            ->from('invoice_lines')
            ->innerJoin('invoice_line_taxes', 'invoice_line_taxes.line_id', '=', 'invoice_lines.id', false)
            ->on('taxes.level', '=', '2')
            ->innerJoin('taxes', 'taxes.id', '=', 'invoice_line_taxes.tax_id', false);
        $tax2_sub_query = $this->Record->get();
        $tax2_values = $this->Record->values;
        $this->Record->reset();

        $tax_amount = '(
            SUM(`invoice_lines`.`amount` * `invoice_lines`.`qty`)
            * (
                IFNULL(`level_1_taxes`.`amount`, ?)
                    / (? + IF(`level_1_taxes`.`type` = ?, IFNULL(`level_1_taxes`.`amount`, ?), ?))
            )
        )';
        // Get the income for each package by invoice line item and tax
        $fields = [
            'SUM(`invoice_lines`.`amount` * `invoice_lines`.`qty`)' => 'amount',
            $tax_amount => 'tax1',
            '(
                SUM(`invoice_lines`.`amount` * `invoice_lines`.`qty`)
                + IF(
                    `level_1_taxes`.`cascade` = ?,
                    ' . $tax_amount . ',
                    ?
                ) * IF(`level_1_taxes`.`type` = ?, ?, ?)
            )
            * (
                IFNULL(`level_2_taxes`.`amount`, ?)
                / (? + IF(`level_2_taxes`.`type` = ?, IFNULL(`level_2_taxes`.`amount`, ?), ?))
            )' => 'tax2',
            '`invoice_lines`.`service_id`'
        ];
        $this->Record->select(['packages.id' => 'package_id'])
            ->select($fields, false)
            ->appendValues([
                0, 100, "inclusive_calculated", 0, 0,
                1, 0, 100, "inclusive_calculated", 0, 0,
                0, "inclusive_calculated", -1, 1,
                0, 100, "inclusive_calculated", 0, 0
            ])
            ->from('packages')
            ->innerJoin('package_pricing', 'package_pricing.package_id', '=', 'packages.id', false)
            ->innerJoin('services', 'services.pricing_id', '=', 'package_pricing.id', false)
            ->innerJoin('invoice_lines', 'invoice_lines.service_id', '=', 'services.id', false)
            ->on('invoices.date_closed', '!=', null)
            ->on('invoices.status', '=', 'active')
            ->on('invoices.currency', '=', $currency)
            ->innerJoin('invoices', 'invoices.id', '=', 'invoice_lines.invoice_id', false)
            ->leftJoin(
                [$tax1_sub_query => 'level_1_taxes'],
                'level_1_taxes.line_id',
                '=',
                'invoice_lines.id',
                false
            )
            ->appendValues($tax1_values)
            ->leftJoin(
                [$tax2_sub_query => 'level_2_taxes'],
                'level_2_taxes.line_id',
                '=',
                'invoice_lines.id',
                false
            )
            ->appendValues($tax2_values)
            ->where('packages.company_id', '=', $company_id);

        // Filter on the date the invoice was closed
        if ($start_date) {
            $this->Record->where('invoices.date_closed', '>=', $start_date);
        }

        if ($end_date) {
            $this->Record->where('invoices.date_closed', '<=', $end_date);
        }

        $this->Record->group(['invoice_lines.service_id']);

        $invoice_sub_query = $this->Record->get();
        $invoice_values = $this->Record->values;
        $this->Record->reset();

        // Calculate the total income and tax from each package, and list them with the module name and package status
        $precision = 4;
        $this->Record->select([
                'package_names.name' => 'package_name',
                'packages.status' => 'status',
                'modules.name' => 'module_name',
            ])
            ->select(
                [
                    'COUNT(DISTINCT `package_revenue`.`service_id`)' => 'service_count',
                    'ROUND(SUM(IFNULL(`package_revenue`.`amount`, ?)), ?)' => 'package_revenue',
                    'ROUND(
                        SUM(IFNULL(`package_revenue`.`tax1`, ?)) + SUM(IFNULL(`package_revenue`.`tax2`, ?)),
                        ?
                    )' => 'tax'
                ],
                false
            )
            ->appendValues([0, $precision, 0, 0, $precision])
            ->from('packages')
            ->on('package_names.lang', '=', Configure::get('Blesta.language'))
            ->leftJoin('package_names', 'package_names.package_id', '=', 'packages.id', false)
            ->innerJoin('modules', 'modules.id', '=', 'packages.module_id', false)
            ->leftJoin(
                [$invoice_sub_query => 'package_revenue'],
                'packages.id',
                '=',
                'package_revenue.package_id',
                false
            )
            ->appendValues($invoice_values)
            ->where('packages.company_id', '=', $company_id)
            ->group(['packages.id'])
            ->order(['modules.name' => 'ASC', 'package_names.name' => 'ASC']);

        return new IteratorIterator($this->Record->getStatement());
    }
}
