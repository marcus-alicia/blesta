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
class TransactionFilters implements FiltersInterface
{
    /**
     * Gets a list of input fields for filtering transactions
     *
     * @param array $options A list of options for building the filters including:
     *  - language The language for filter labels and tooltips
     *  - client True to fetch the filters for the client interface, false to fetch the filters for the admin interface
     *  - client_id The client ID to filter payment types on
     * @param array $vars A list of submitted inputs that act as defaults for filter fields including:
     *  - payment_type The payment type on which to filter transactions
     *  - transaction_id The (partial) transaction number on which to filter transactions
     *  - reference_id The (partial) transaction reference on which to filter transactions
     *  - applied_status The applied status on which to filter transactions
     * @return InputFields An object representing the list of filter input field
     */
    public function getFilters(array $options, array $vars = [])
    {
        Loader::loadComponents($this, ['Record']);
        Loader::loadModels($this, ['Transactions']);
        Loader::loadHelpers($this, ['Form']);

        // Autoload the language file
        Language::loadLang(
            'transaction_filters',
            $options['language'],
            COREDIR . 'Util' . DS . 'Filters' . DS . 'language' . DS
        );

        if (!isset($options['client'])) {
            $options['client'] = false;
        }

        $fields = new InputFields();

        // Set payment type filter
        $payment_types = $this->Transactions->transactionTypeNames();

        if (isset($options['client_id'])) {
            $client_methods = $this->Record->select(['IFNULL(transaction_types.name, transactions.type)' => 'id'])
                ->from('transactions')
                ->where('transactions.client_id', '=', $options['client_id'])
                ->leftJoin('transaction_types', 'transaction_types.id', '=', 'transactions.transaction_type_id', false)
                ->group(['id'])
                ->fetchAll();

            foreach ($client_methods as $key => $method) {
                $method->name = $payment_types[$method->id];
                $client_methods[$key] = $method;
            }

            $payment_types = $this->Form->collapseObjectArray($client_methods, 'name', 'id');
        }

        $payment_type = $fields->label(
            Language::_('Util.filters.transaction_filters.field_payment_type', true),
            'payment_type'
        );
        $payment_type->attach(
            $fields->fieldSelect(
                'filters[payment_type]',
                ['' => Language::_('Util.filters.transaction_filters.any', true)] + $payment_types,
                isset($vars['payment_type']) ? $vars['payment_type'] : null,
                ['id' => 'payment_type', 'class' => 'form-control']
            )
        );
        $fields->setField($payment_type);

        // Set number filter
        $transaction_id = $fields->label(
            Language::_('Util.filters.transaction_filters.field_transaction_id', true),
            'transaction_id'
        );
        $transaction_id->attach(
            $fields->fieldText(
                'filters[transaction_id]',
                isset($vars['transaction_id']) ? $vars['transaction_id'] : null,
                [
                    'id' => 'transaction_id',
                    'class' => 'form-control stretch',
                    'placeholder' => Language::_('Util.filters.transaction_filters.field_transaction_id', true)
                ]
            )
        );
        $fields->setField($transaction_id);

        // Set reference filter
        if (!$options['client']) {
            $reference_id = $fields->label(
                Language::_('Util.filters.transaction_filters.field_reference_id', true),
                'reference_id'
            );
            $reference_id->attach(
                $fields->fieldText(
                    'filters[reference_id]',
                    isset($vars['reference_id']) ? $vars['reference_id'] : null,
                    [
                        'id' => 'reference_id',
                        'class' => 'form-control stretch',
                        'placeholder' => Language::_('Util.filters.transaction_filters.field_reference_id', true)
                    ]
                )
            );
            $fields->setField($reference_id);
        }

        // Set applied status filter
        $applied_statuses = [
            '' => Language::_('Util.filters.transaction_filters.any', true),
            'fully_applied' => Language::_('Util.filters.transaction_filters.fully_applied', true),
            'partially_applied' => Language::_('Util.filters.transaction_filters.partially_applied', true),
            'not_applied' => Language::_('Util.filters.transaction_filters.not_applied', true)
        ];
        $applied_status = $fields->label(
            Language::_('Util.filters.transaction_filters.field_applied_status', true),
            'currency'
        );
        $applied_status->attach(
            $fields->fieldSelect(
                'filters[applied_status]',
                $applied_statuses,
                isset($vars['applied_status']) ? $vars['applied_status'] : null,
                ['id' => 'applied_status', 'class' => 'form-control']
            )
        );
        $fields->setField($applied_status);

        // Set the date filter
        if ($options['client']) {
            $start_date = $fields->label(
                Language::_('Util.filters.transaction_filters.field_start_date', true),
                'start_date'
            );
            $start_date->attach(
                $fields->fieldText(
                    'filters[start_date]',
                    isset($vars['start_date']) ? $vars['start_date'] : null,
                    [
                        'id' => 'start_date',
                        'class' => 'date form-control',
                        'placeholder' => Language::_('Util.filters.transaction_filters.field_start_date', true)
                    ]
                )
            );
            $fields->setField($start_date);

            $end_date = $fields->label(
                Language::_('Util.filters.transaction_filters.field_end_date', true),
                'end_date'
            );
            $end_date->attach(
                $fields->fieldText(
                    'filters[end_date]',
                    isset($vars['end_date']) ? $vars['end_date'] : null,
                    [
                        'id' => 'end_date',
                        'class' => 'date form-control',
                        'placeholder' => Language::_('Util.filters.transaction_filters.field_end_date', true)
                    ]
                )
            );
            $fields->setField($end_date);
        } else {
            $date = $fields->label(
                Language::_('Util.filters.transaction_filters.field_date', true),
                'date'
            );
            $date->attach(
                $fields->fieldText(
                    'filters[start_date]',
                    isset($vars['start_date']) ? $vars['start_date'] : null,
                    [
                        'id' => 'start_date',
                        'class' => 'date form-control',
                        'placeholder' => Language::_('Util.filters.transaction_filters.field_start_date', true)
                    ]
                )
            );
            $date->attach(
                $fields->fieldText(
                    'filters[end_date]',
                    isset($vars['end_date']) ? $vars['end_date'] : null,
                    [
                        'id' => 'end_date',
                        'class' => 'date form-control',
                        'placeholder' => Language::_('Util.filters.transaction_filters.field_end_date', true)
                    ]
                )
            );
            $fields->setField($date);
        }

        // Set the amount filter
        if ($options['client']) {
            $minimum_amount = $fields->label(
                Language::_('Util.filters.transaction_filters.field_minimum_amount', true),
                'minimum_amount'
            );
            $minimum_amount->attach(
                $fields->fieldText(
                    'filters[minimum_amount]',
                    isset($vars['minimum_amount']) ? $vars['minimum_amount'] : null,
                    [
                        'id' => 'minimum_amount',
                        'class' => 'form-control',
                        'placeholder' => Language::_('Util.filters.transaction_filters.field_minimum_amount', true)
                    ]
                )
            );
            $fields->setField($minimum_amount);

            $maximum_amount = $fields->label(
                Language::_('Util.filters.transaction_filters.field_maximum_amount', true),
                'maximum_amount'
            );
            $maximum_amount->attach(
                $fields->fieldText(
                    'filters[maximum_amount]',
                    isset($vars['maximum_amount']) ? $vars['maximum_amount'] : null,
                    [
                        'id' => 'maximum_amount',
                        'class' => 'form-control',
                        'placeholder' => Language::_('Util.filters.transaction_filters.field_maximum_amount', true)
                    ]
                )
            );
            $fields->setField($maximum_amount);
        } else {
            $amount = $fields->label(
                Language::_('Util.filters.transaction_filters.field_amount', true),
                'amount'
            );
            $amount->attach(
                $fields->fieldText(
                    'filters[minimum_amount]',
                    isset($vars['minimum_amount']) ? $vars['minimum_amount'] : null,
                    [
                        'id' => 'minimum_amount',
                        'class' => 'form-control small',
                        'placeholder' => Language::_('Util.filters.transaction_filters.field_minimum_amount', true)
                    ]
                )
            );
            $amount->attach(
                $fields->fieldText(
                    'filters[maximum_amount]',
                    isset($vars['maximum_amount']) ? $vars['maximum_amount'] : null,
                    [
                        'id' => 'maximum_amount',
                        'class' => 'form-control small',
                        'placeholder' => Language::_('Util.filters.transaction_filters.field_maximum_amount', true)
                    ]
                )
            );
            $fields->setField($amount);
        }

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
