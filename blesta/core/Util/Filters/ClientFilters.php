<?php
namespace Blesta\Core\Util\Filters;
use Blesta\Core\Util\Input\Fields\InputFields;
use Blesta\Core\Util\Filters\Common\FiltersInterface;
use \Loader;
use \Language;

/**
 * Client Filters
 *
 * @package blesta
 * @subpackage blesta.core.Util.Filters
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ClientFilters implements FiltersInterface
{
    /**
     * Gets a list of input fields for filtering clients
     *
     * @param array $options A list of options for building the filters including:
     *  - language The language for filter labels and tooltips
     *  - company_id The company ID to filter client groups on
     * @param array $vars A list of submitted inputs that act as defaults for filter fields including:
     *  - contact_name The (partial) name of the contact for which to fetch clients
     *  - contact_email The (partial) email address of the contact for which to fetch clients
     *  - contact_company The (partial) company name of the contact for which to fetch clients
     *  - contact_country The contact country on which to filter clients
     *  - client_group_id The client group ID on which to filter clients
     *  - invoice_method The invoice delivery method on which to filter clients
     *  - last_seen_start_date The start date of the "last seen" date range on which to filter clients
     *  - last_seen_end_date The end date of the "last seen" date range on which to filter clients
     * @return InputFields An object representing the list of filter input field
     */
    public function getFilters(array $options, array $vars = [])
    {
        Loader::loadModels($this, ['Countries', 'ClientGroups', 'Invoices']);
        Loader::loadHelpers($this, ['Form']);

        // Autoload the language file
        Language::loadLang(
            'client_filters',
            $options['language'],
            COREDIR . 'Util' . DS . 'Filters' . DS . 'language' . DS
        );

        $fields = new InputFields();

        // Set the contact name filter
        $contact_name = $fields->label(
            Language::_('Util.filters.client_filters.field_contact_name', true),
            'contact_name'
        );
        $contact_name->attach(
            $fields->fieldText(
                'filters[contact_name]',
                isset($vars['contact_name']) ? $vars['contact_name'] : null,
                [
                    'id' => 'contact_name',
                    'class' => 'form-control stretch',
                    'placeholder' => Language::_('Util.filters.client_filters.field_contact_name', true)
                ]
            )
        );
        $fields->setField($contact_name);

        // Set the contact email filter
        $contact_email = $fields->label(
            Language::_('Util.filters.client_filters.field_contact_email', true),
            'contact_email'
        );
        $contact_email->attach(
            $fields->fieldText(
                'filters[contact_email]',
                isset($vars['contact_email']) ? $vars['contact_email'] : null,
                [
                    'id' => 'contact_email',
                    'class' => 'form-control stretch',
                    'placeholder' => Language::_('Util.filters.client_filters.field_contact_email', true)
                ]
            )
        );
        $fields->setField($contact_email);

        // Set the contact company filter
        $contact_company = $fields->label(
            Language::_('Util.filters.client_filters.field_contact_company', true),
            'contact_company'
        );
        $contact_company->attach(
            $fields->fieldText(
                'filters[contact_company]',
                isset($vars['contact_company']) ? $vars['contact_company'] : null,
                [
                    'id' => 'contact_company',
                    'class' => 'form-control stretch',
                    'placeholder' => Language::_('Util.filters.client_filters.field_contact_company', true)
                ]
            )
        );
        $fields->setField($contact_company);

        // Set contact country filter
        $countries = $this->Form->collapseObjectArray(
            $this->Countries->getList(),
            'name',
            'alpha2'
        );
        $contact_country = $fields->label(
            Language::_('Util.filters.client_filters.field_contact_country', true),
            'contact_country'
        );
        $contact_country->attach(
            $fields->fieldSelect(
                'filters[contact_country]',
                ['' => Language::_('Util.filters.client_filters.any', true)] + $countries,
                isset($vars['contact_country']) ? $vars['contact_country'] : null,
                ['id' => 'contact_country', 'class' => 'form-control']
            )
        );
        $fields->setField($contact_country);

        // Set client group id filter
        $client_groups = $this->Form->collapseObjectArray(
            $this->ClientGroups->getList($options['company_id']),
            'name',
            'id'
        );
        $client_group_id = $fields->label(
            Language::_('Util.filters.client_filters.field_client_group_id', true),
            'client_group_id'
        );
        $client_group_id->attach(
            $fields->fieldSelect(
                'filters[client_group_id]',
                ['' => Language::_('Util.filters.client_filters.any', true)] + $client_groups,
                isset($vars['client_group_id']) ? $vars['client_group_id'] : null,
                ['id' => 'client_group_id', 'class' => 'form-control']
            )
        );
        $fields->setField($client_group_id);

        // Set invoice delivery method filter
        $invoice_methods = $this->Invoices->getDeliveryMethods();
        $invoice_method = $fields->label(
            Language::_('Util.filters.client_filters.field_invoice_method', true),
            'invoice_method'
        );
        $invoice_method->attach(
            $fields->fieldSelect(
                'filters[invoice_method]',
                ['' => Language::_('Util.filters.client_filters.any', true)] + $invoice_methods,
                isset($vars['invoice_method']) ? $vars['invoice_method'] : null,
                ['id' => 'invoice_method', 'class' => 'form-control']
            )
        );
        $fields->setField($invoice_method);

        // Set the last seen filter
        $last_seen = $fields->label(
            Language::_('Util.filters.client_filters.field_last_seen', true),
            'last_seen'
        );
        $last_seen->attach(
            $fields->fieldText(
                'filters[last_seen_start_date]',
                isset($vars['last_seen_start_date']) ? $vars['last_seen_start_date'] : null,
                [
                    'id' => 'last_seen_start_date',
                    'class' => 'date form-control',
                    'placeholder' => Language::_('Util.filters.client_filters.field_last_seen_start_date', true)
                ]
            )
        );
        $last_seen->attach(
            $fields->fieldText(
                'filters[last_seen_end_date]',
                isset($vars['last_seen_end_date']) ? $vars['last_seen_end_date'] : null,
                [
                    'id' => 'last_seen_end_date',
                    'class' => 'date form-control',
                    'placeholder' => Language::_('Util.filters.client_filters.field_last_seen_end_date', true)
                ]
            )
        );
        $fields->setField($last_seen);

        $fields->setHtml('
            <script type="text/javascript">
                $(document).ready(function () {
                    $(this).blestaBindDatePicker();
                });
            </script>
        ');

        return $fields;
    }
}
