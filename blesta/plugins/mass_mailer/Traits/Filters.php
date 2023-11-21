<?php
namespace Blesta\MassMailer\Traits;

use Configure;
use Record;

/**
 * MassMailer Filters trait
 *
 * @package blesta
 * @subpackage blesta.plugins.mass_mailer.traits
 * @copyright Copyright (c) 2016, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
trait Filters
{
    /**
     * @var array An array of query options for filtering results
     */
    private $query_options = [];
    /**
     * @var Record An instance of the Record object
     */
    private $db_record;
    /**
     * @var array An array of filter options
     */
    private $filters = [];

    /**
     * Set fields to select by default
     */
    public function __construct()
    {
        // Set default query options
        $this->setClientFields(['contacts.*', 'clients.status']);
        // Set comma specifically, as appending the value would do so in the wrong order
        $this->setServiceFields(
            ['GROUP_CONCAT(services.id SEPARATOR \',\')' => 'service_ids'],
            false
        );
        $this->setGroupBy(['contacts.id']);
    }

    /**
     * Retrieves a Record object for fetching all matching contacts
     *
     * @param Record A new Record instance
     * @return Record A Record object set to select contacts
     */
    protected function filter(Record $Record)
    {
        $Record->reset();
        $this->db_record = $Record;

        return $this->fetchClients();
    }

    /**
     * Sets the fields to select when retrieving clients
     *
     * @param array $fields A key/value array of fields to select when filtering clients
     * @param bool $escape True to escape the client fields, or false not to (default true)
     */
    protected function setClientFields(array $fields, $escape = true)
    {
        $this->query_options['client_fields'] = $fields;
        $this->query_options['client_fields_escape'] = $escape;
    }

    /**
     * Sets the fields to select when retrieving services
     *
     * @param array $fields A key/value array of fields to select when filtering services
     * @param bool $escape True to escape the service fields, or false not to (default true)
     */
    protected function setServiceFields(array $fields, $escape = true)
    {
        $this->query_options['service_fields'] = $fields;
        $this->query_options['service_fields_escape'] = $escape;
    }

    /**
     * Sets the fields to group on when retrieving clients
     *
     * @param array $fields The fields to group on
     */
    protected function setGroupBy(array $fields)
    {
        $this->query_options['group_by'] = $fields;
    }

    /**
     * Sets filter options
     */
    protected function setFilters(array $filters)
    {
        $this->filters = $filters;
    }

    /**
     * Determines whether services are being filtered on or not.
     * Optionally also determines whether all services are included or not
     *
     * @param bool $all Whether all services are being included or not (default false)
     * @return bool True if services are being filtered on, false otherwise
     */
    protected function filteringServices($all = false)
    {
        // Determine whether all services are included
        $include_all = true;
        if ($all) {
            $include_all = array_key_exists('include_all_services', $this->filters)
                && $this->filters['include_all_services'] === 'true';
        }

        // Determine whether any services are filtered and whether
        // all are included
        return array_key_exists('filter_services', $this->filters)
            && $this->filters['filter_services'] === 'true'
            && $include_all;
    }

    /**
     * Builds a Record object for fetching all matching contacts
     *
     * @return Record A Record object
     */
    private function fetchClients()
    {
        $Record = clone $this->db_record;
        $Record = $this->getClients($Record, Configure::get('Blesta.company_id'));

        // Conditionally filter on given service filters
        if ($this->filteringServices()) {
            $Record = $this->getServices($Record);
        }

        return $Record->group($this->query_options['group_by']);
    }

    /**
     * Partially constructs a Record object for filtering on matching services
     *
     * @param Record $Record A Record object for fetching the clients
     * @return Record A Record object
     */
    private function getServices(Record $Record)
    {
        // Determine the options to filter by
        $options = ['service_renew_date', 'service_statuses'];
        $module_options = ['module_id', 'module_rows'];
        $package_options = ['packages'];

        // Filter by either module or package options
        $filter_by_package = (isset($this->filters['service_parent_type'])
            && $this->filters['service_parent_type'] == 'module'
            ? false
            : true
        );

        $options = array_merge(
            $options,
            ($filter_by_package ? $package_options : $module_options)
        );

        // Assume client tables are already available to join with
        $Record->select(
            $this->query_options['service_fields'],
            $this->query_options['service_fields_escape']
        )
            ->innerJoin('services', 'services.client_id', '=', 'clients.id', false);

        // Filter by package or module
        if ($filter_by_package) {
            $Record->innerJoin('package_pricing', 'package_pricing.id', '=', 'services.pricing_id', false);
        } else {
            $Record->innerJoin('module_rows', 'module_rows.id', '=', 'services.module_row_id', false);
        }

        $Record = $this->filterServices($Record, $options);

        return $Record;
    }

    /**
     * Updates the Record object to filter on the current service filters
     *
     * @param Record $Record A Record object for fetching the clients
     * @param array $options An array of option names to support filtering on
     * @return Record The updated Record object
     */
    private function filterServices(Record $Record, array $options)
    {
        foreach ($options as $option) {
            if (array_key_exists($option, $this->filters) && !empty($this->filters[$option])) {
                $Record = $this->filterServiceOption($Record, $option, $this->filters[$option]);
            }
        }

        return $Record;
    }

    /**
     * Updates the Record object to filter a specific service option
     *
     * @param Record $Record A Record object for fetching the clients
     * @param string $option The name of the option to filter
     * @param mixed $value The value to filter for
     * @return Record The updated Record object
     */
    private function filterServiceOption(Record $Record, $option, $value)
    {
        switch ($option) {
            case 'service_renew_date':
                if ($this->Input->isDate($value)) {
                    $Record->where('services.date_renews', '<=', $this->dateToUtc($value));
                }
                break;
            case 'service_statuses':
                if (is_array($value)) {
                    $Record->where('services.status', 'in', $value);
                }
                break;
            case 'module_id':
                $Record->where('module_rows.module_id', '=', $value);
                break;
            case 'module_rows':
                if (is_array($value)) {
                    $Record->where('module_rows.id', 'in', $value);
                }
                break;
            case 'packages':
                if (is_array($value)) {
                    $Record->where('package_pricing.package_id', 'in', $value);
                }
                break;
        }

        return $Record;
    }

    /**
     * Partially constructs a Record object for selecting matching clients
     *
     * @param Record $Record A Record object for fetching the clients
     * @param int $company_id The ID of the company whose clients to fetch
     * @return Record A Record object
     */
    private function getClients(Record $Record, $company_id)
    {
        $Record->select(
            $this->query_options['client_fields'],
            $this->query_options['client_fields_escape']
        )
            ->from('contacts')
            ->innerJoin('clients', 'clients.id', '=', 'contacts.client_id', false)
            ->innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)
            ->where('client_groups.company_id', '=', $company_id);

        $Record = $this->filterClients($Record, $company_id);

        return $Record;
    }

    /**
     * Updates the Record object to filter on the current client filters
     *
     * @param Record $Record A Record object for fetching the clients
     * @param int $company_id The ID of the company to filter clients for
     * @return Record The updated Record object
     */
    private function filterClients(Record $Record, $company_id)
    {
        $options = [
            'client_group_ids',
            'client_statuses',
            'languages',
            'contact_types',
            'receive_email_marketing'
        ];

        // Reformat the given contact types into sets of default/custom contact types
        if (array_key_exists('contact_types', $this->filters)
            && is_array($this->filters['contact_types'])
        ) {
            $this->filters['contact_types']
                = $this->formatContactTypes($this->filters['contact_types']);
        }

        foreach ($options as $option) {
            if (array_key_exists($option, $this->filters) && !empty($this->filters[$option])) {
                $Record = $this->filterClientOption($Record, $option, $this->filters[$option]);
            }
        }

        // Add a subquery to filter on the date the client was added
        $Record = $this->filterClientAdded($Record, $company_id);

        return $Record;
    }

    /**
     * Formats the given contact types into a set of default and custom types
     *
     * @param array $contact_types An array of contact types
     * @return array An array containing:
     *  - default An array of default contact types to filter on
     *  - custom An array of custom contact types to filter on
     */
    private function formatContactTypes(array $contact_types)
    {
        $types = [
            'default' => [],
            'custom' => []
        ];

        foreach ($contact_types as $type) {
            if (is_numeric($type)) {
                $types['custom'][] = $type;
            } else {
                $types['default'][] = $type;
            }
        }

        return $types;
    }

    /**
     * Updates the Record object to filter the client account's add date
     * @see MassMailerClients::filterClients
     *
     * @param Record $Record A Record object for fetching the clients
     * @param int $company_id The ID of the company to filter clients for
     * @return Record The updated Record object
     */
    private function filterClientAdded(Record $Record, $company_id)
    {
        // Create a subquery using $this->db_record for filtering on
        // the client's account creation date (i.e. primary contact add date)
        if ((array_key_exists('client_start_date', $this->filters)
            && $this->Input->isDate($this->filters['client_start_date']))
            || (array_key_exists('client_end_date', $this->filters)
                && $this->Input->isDate($this->filters['client_end_date']))
        ) {
            $this->db_record->reset();

            $this->db_record->select(['clients.id'])
                ->from('clients')
                    ->on('contacts.contact_type', '=', 'primary')
                ->innerJoin('contacts', 'contacts.client_id', '=', 'clients.id', false)
                ->innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)
                ->where('client_groups.company_id', '=', $company_id);

            // Filter on the date the client was added
            if (array_key_exists('client_start_date', $this->filters)) {
                $this->db_record = $this->filterClientOption(
                    $this->db_record,
                    'client_start_date',
                    $this->filters['client_start_date']
                );
            }
            if (array_key_exists('client_end_date', $this->filters)) {
                $this->db_record = $this->filterClientOption(
                    $this->db_record,
                    'client_end_date',
                    $this->filters['client_end_date']
                );
            }

            // Further filter this subquery on the other client filter options
            foreach (['client_group_ids', 'client_statuses'] as $option) {
                if (array_key_exists($option, $this->filters) && !empty($this->filters[$option])) {
                    $this->db_record
                        = $this->filterClientOption($this->db_record, $option, $this->filters[$option]);
                }
            }

            // Get the subquery
            $subquery = $this->db_record->get();
            $values = $this->db_record->values;
            $this->db_record->reset();

            // Join the subquery with the primary $Record query
            $Record
                ->innerJoin(
                    [$subquery => 'primary_client'],
                    'primary_client.id',
                    '=',
                    'clients.id',
                    false
                )
                ->appendValues($values);
        }

        return $Record;
    }

    /**
     * Updates the Record object to filter a specific client option
     *
     * @param Record $Record A Record object for fetching the clients
     * @param string $option The name of the option to filter
     * @param mixed $value The value to filter for
     * @return Record The updated Record object
     */
    private function filterClientOption(Record $Record, $option, $value)
    {
        switch ($option) {
            case 'client_group_ids':
                if (is_array($value)) {
                    $Record->where('client_groups.id', 'in', $value);
                }
                break;
            case 'client_statuses':
                if (is_array($value)) {
                    $Record->where('clients.status', 'in', $value);
                }
                break;
            case 'languages':
                if (is_array($value)) {
                    $Record
                        ->on('client_settings.key', '=', 'language')
                        ->leftJoin(
                            'client_settings',
                            'client_settings.client_id',
                            '=',
                            'clients.id',
                            false
                        )
                        ->open()
                            ->where('client_settings.value', 'in', $value);

                    if (in_array(Configure::get('Blesta.language'), $value)) {
                        $Record->orWhere('client_settings.value', '=', null);
                    }

                    $Record->close();
                }
                break;
            case 'receive_email_marketing':
                // Only include users that have opted in to email marketing
                // Clone the record object
                $sql = clone $Record;
                $values = $Record->values;
                $Record->reset();
                $Record->values = $values;

                // Check Client Settings
                $sql1 = $Record->select(['value', 'client_id' => 'client_id'])->
                    select(['?' => 'level'], false)->appendValues(['client'])->
                    from('client_settings')->
                    where('client_settings.key', '=', 'receive_email_marketing')->get();
                $values = $Record->values;
                $Record->reset();
                $Record->values = $values;

                // Check Client Group Settings
                $sql2 = $Record->select(['value', 'clients.id' => 'client_id'])->
                    select(['?' => 'level'], false)->appendValues(['client_group'])->
                    from('clients')->
                    innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)->
                    innerJoin(
                        'client_group_settings',
                        'client_group_settings.client_group_id',
                        '=',
                        'client_groups.id',
                        false
                    )->
                    where('client_group_settings.key', '=', 'receive_email_marketing')->get();
                $values = $Record->values;
                $Record->reset();
                $Record->values = $values;

                // Check Company Settings
                $sql3 = $Record->select(['value', 'clients.id' => 'client_id'])->
                    select(['?' => 'level'], false)->appendValues(['company'])->
                    from('clients')->
                    innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)->
                    innerJoin(
                        'company_settings',
                        'company_settings.company_id',
                        '=',
                        'client_groups.company_id',
                        false
                    )->
                    where('company_settings.key', '=', 'receive_email_marketing')->
                    where('company_settings.inherit', '=', '1')->get();
                $values = $Record->values;
                $Record->reset();
                $Record->values = $values;

                // Group settings by client, fetching them in order of priority
                // from client, client group, then company
                $sql4 = $Record->select()->
                    from([
                        '((' . $sql1 . ') UNION (' . $sql2 . ') UNION (' . $sql3 . '))' => 'temp_marketing_settings'
                    ])->
                    group('temp_marketing_settings.client_id')->get();
                $values = $Record->values;
                $Record->reset();

                // Restore record from the clone
                $Record = $sql;
                $Record->values = $values;
                $Record->innerJoin(
                        [$sql4 => 'marketing_settings'],
                        'marketing_settings.client_id',
                        '=',
                        'clients.id',
                        false
                    )
                    ->where('marketing_settings.value', '=', 'true');
                break;
            case 'contact_types':
                if (is_array($value)) {
                    // Filter by either, or both, default and custom contact types
                    $Record->open();

                    $method = 'where';
                    if (!empty($value['default'])) {
                        $Record->where('contacts.contact_type', 'in', $value['default']);
                        $method = 'orWhere';
                    }

                    if (!empty($value['custom'])) {
                        $Record->open()
                            ->{$method}('contacts.contact_type', '=', 'other')
                            ->where('contacts.contact_type_id', 'in', $value['custom'])
                            ->close();
                    }

                    $Record->close();
                }
                break;
            case 'client_start_date':
                if ($this->Input->isDate($value)) {
                    $Record->where('contacts.date_added', '>=', $this->dateToUtc($value));
                }
                break;
            case 'client_end_date':
                if ($this->Input->isDate($value)) {
                    $Record->where('contacts.date_added', '<=', $this->dateToUtc($value));
                }
                break;
        }

        return $Record;
    }
}
