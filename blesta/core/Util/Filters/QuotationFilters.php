<?php
namespace Blesta\Core\Util\Filters;
use Blesta\Core\Util\Input\Fields\InputFields;
use Blesta\Core\Util\Filters\Common\FiltersInterface;
use \Loader;
use \Language;

/**
 * Quotation Filters
 *
 * @package blesta
 * @subpackage blesta.core.Util.Filters
 * @copyright Copyright (c) 2022, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class QuotationFilters implements FiltersInterface
{
    /**
     * Gets a list of input fields for filtering quotations
     *
     * @param array $options A list of options for building the filters including:
     *  - language The language for filter labels and tooltips
     *  - company_id The company ID to filter currencies on
     * @param array $vars A list of submitted inputs that act as defaults for filter fields including:
     *  - quotation_number The quotation ID on which to filter quotations
     *  - currency The currency code on which to filter quotations
     *  - quotation_line The (partial) description of the quotation line on which to filter quotations
     * @return InputFields An object representing the list of filter input field
     */
    public function getFilters(array $options, array $vars = [])
    {
        Loader::loadModels($this, ['Currencies']);
        Loader::loadHelpers($this, ['Form']);

        // Autoload the language file
        Language::loadLang(
            'quotation_filters',
            $options['language'],
            COREDIR . 'Util' . DS . 'Filters' . DS . 'language' . DS
        );

        $fields = new InputFields();

        // Set quotation number filter
        $quotation_number = $fields->label(
            Language::_('Util.filters.quotation_filters.field_quotation_number', true),
            'quotation_number'
        );
        $quotation_number->attach(
            $fields->fieldText(
                'filters[quotation_number]',
                $vars['quotation_number'] ?? null,
                [
                    'id' => 'quotation_number',
                    'class' => 'form-control stretch',
                    'placeholder' => Language::_('Util.filters.quotation_filters.field_quotation_number', true)
                ]
            )
        );
        $fields->setField($quotation_number);

        // Set currency filter
        $currencies = $this->Form->collapseObjectArray(
            $this->Currencies->getAll($options['company_id']),
            'code',
            'code'
        );
        $currency = $fields->label(
            Language::_('Util.filters.quotation_filters.field_currency', true),
            'currency'
        );
        $currency->attach(
            $fields->fieldSelect(
                'filters[currency]',
                ['' => Language::_('Util.filters.quotation_filters.any', true)] + $currencies,
                $vars['currency'] ?? null,
                ['id' => 'currency', 'class' => 'form-control']
            )
        );
        $fields->setField($currency);

        // Set quotation line filter
        $quotation_line = $fields->label(
            Language::_('Util.filters.quotation_filters.field_quotation_line', true),
            'quotation_line'
        );
        $quotation_line->attach(
            $fields->fieldText(
                'filters[quotation_line]',
                $vars['quotation_line'] ?? null,
                [
                    'id' => 'quotation_line',
                    'class' => 'form-control stretch',
                    'placeholder' => Language::_('Util.filters.quotation_filters.field_quotation_line', true)
                ]
            )
        );
        $fields->setField($quotation_line);

        return $fields;
    }
}
