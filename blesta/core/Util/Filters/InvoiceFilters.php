<?php
namespace Blesta\Core\Util\Filters;
use Blesta\Core\Util\Input\Fields\InputFields;
use Blesta\Core\Util\Filters\Common\FiltersInterface;
use \Loader;
use \Language;

/**
 * Invoice Filters
 *
 * @package blesta
 * @subpackage blesta.core.Util.Filters
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class InvoiceFilters implements FiltersInterface
{
    /**
     * Gets a list of input fields for filtering invoices
     *
     * @param array $options A list of options for building the filters including:
     *  - language The language for filter labels and tooltips
     *  - company_id The company ID to filter currencies on
     * @param array $vars A list of submitted inputs that act as defaults for filter fields including:
     *  - invoice_number The invoice ID on which to filter invoices
     *  - currency The currency code on which to filter invoices
     *  - invoice_line The (partial) description of the invoice line on which to filter invoices
     * @return InputFields An object representing the list of filter input field
     */
    public function getFilters(array $options, array $vars = [])
    {
        Loader::loadModels($this, ['Currencies']);
        Loader::loadHelpers($this, ['Form']);

        // Autoload the language file
        Language::loadLang(
            'invoice_filters',
            $options['language'],
            COREDIR . 'Util' . DS . 'Filters' . DS . 'language' . DS
        );

        $fields = new InputFields();

        // Set invoice number filter
        $invoice_number = $fields->label(
            Language::_('Util.filters.invoice_filters.field_invoice_number', true),
            'invoice_number'
        );
        $invoice_number->attach(
            $fields->fieldText(
                'filters[invoice_number]',
                isset($vars['invoice_number']) ? $vars['invoice_number'] : null,
                [
                    'id' => 'invoice_number',
                    'class' => 'form-control stretch',
                    'placeholder' => Language::_('Util.filters.invoice_filters.field_invoice_number', true)
                ]
            )
        );
        $fields->setField($invoice_number);

        // Set currency filter
        $currencies = $this->Form->collapseObjectArray(
            $this->Currencies->getAll($options['company_id']),
            'code',
            'code'
        );
        $currency = $fields->label(
            Language::_('Util.filters.invoice_filters.field_currency', true),
            'currency'
        );
        $currency->attach(
            $fields->fieldSelect(
                'filters[currency]',
                ['' => Language::_('Util.filters.invoice_filters.any', true)] + $currencies,
                isset($vars['currency']) ? $vars['currency'] : null,
                ['id' => 'currency', 'class' => 'form-control']
            )
        );
        $fields->setField($currency);

        // Set invoice line filter
        $invoice_line = $fields->label(
            Language::_('Util.filters.invoice_filters.field_invoice_line', true),
            'invoice_line'
        );
        $invoice_line->attach(
            $fields->fieldText(
                'filters[invoice_line]',
                isset($vars['invoice_line']) ? $vars['invoice_line'] : null,
                [
                    'id' => 'invoice_line',
                    'class' => 'form-control stretch',
                    'placeholder' => Language::_('Util.filters.invoice_filters.field_invoice_line', true)
                ]
            )
        );
        $fields->setField($invoice_line);

        return $fields;
    }
}
