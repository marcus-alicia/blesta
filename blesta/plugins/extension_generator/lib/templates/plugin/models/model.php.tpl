{{array:tables}}<?php
/**
 * {{tables.name}} Management
 *{{array:authors}}
 * @link {{authors.url}} {{authors.name}}{{array:authors}}
 */
class {{tables.class_name}} extends {{class_name}}Model
{
    /**
     * Returns a list of records for the given company
     *
     * @param array $filters A list of filters for the query
     *{{array:tables.columns}}
     *  - {{tables.columns.name}}{{array:tables.columns}}
     * @param int $page The page number of results to fetch
     * @param array $order A key/value pair array of fields to order the results by
     * @return array An array of stdClass objects
     */
    public function getList(
        array $filters = [],
        $page = 1,
        array $order = [{{array:tables.columns}}{{if:tables.columns.primary:true}}'{{tables.columns.name}}'{{endif:tables.columns.primary}}{{array:tables.columns}} => 'desc']
    ) {
        $records = $this->getRecord($filters)
            ->order($order)
            ->limit($this->getPerPage(), (max(1, $page) - 1) * $this->getPerPage())
            ->fetchAll();

        return $records;
    }

    /**
     * Returns the total number of record for the given filters
     *
     * @param array $filters A list of filters for the query
     *{{array:tables.columns}}
     *  - {{tables.columns.name}}{{array:tables.columns}}
     * @return int The total number of records for the given filters
     */
    public function getListCount(array $filters = [])
    {
        return $this->getRecord($filters)->numResults();
    }

    /**
     * Returns all records in the system for the given filters
     *
     * @param array $filters A list of filters for the query
     *{{array:tables.columns}}
     *  - {{tables.columns.name}}{{array:tables.columns}}
     * @param array $order A key/value pair array of fields to order the results by
     * @return array An array of stdClass objects
     */
    public function getAll(
        array $filters = [],
        array $order = [{{array:tables.columns}}{{if:tables.columns.primary:true}}'{{tables.columns.name}}'{{endif:tables.columns.primary}}{{array:tables.columns}} => 'desc']
    ) {
        $records = $this->getRecord($filters)->order($order)->fetchAll();

        return $records;
    }

    /**
     * Fetches the record with the given identifier
     *{{array:tables.columns}}{{if:tables.columns.primary:true}}
     * @param int ${{tables.columns.name}} The identifier of the record to fetch{{endif:tables.columns.primary}}{{array:tables.columns}}
     * @return mixed A stdClass object representing the record, false if no such record exists
     */
    public function get({{array:tables.columns}}{{if:tables.columns.primary:true}}${{tables.columns.name}}{{endif:tables.columns.primary}}{{array:tables.columns}})
    {
        $record = $this->getRecord([{{array:tables.columns}}{{if:tables.columns.primary:true}}'{{tables.columns.name}}' => ${{tables.columns.name}}{{endif:tables.columns.primary}}{{array:tables.columns}}])->fetch();

        return $record;
    }

    /**
     * Add a record
     *
     * @param array $vars An array of input data including:
     *{{array:tables.columns}}
     *  - {{tables.columns.name}}{{array:tables.columns}}
     * @return int The identifier of the record that was created, void on error
     */
    public function add(array $vars)
    {
        $this->Input->setRules($this->getRules($vars));

        if ($this->Input->validates($vars)) {
            $fields = [{{array:tables.columns}}'{{tables.columns.name}}',{{array:tables.columns}}];
            $this->Record->insert('{{tables.name}}', $vars, $fields);

            return $this->Record->lastInsertId();
        }
    }

    /**
     * Edit a record
     *{{array:tables.columns}}{{if:tables.columns.primary:true}}
     * @param int ${{tables.columns.name}} The identifier of the record to edit{{endif:tables.columns.primary}}{{array:tables.columns}}
     * @param array $vars An array of input data including:
     *{{array:tables.columns}}
     *  - {{tables.columns.name}}{{array:tables.columns}}
     * @return int The identifier of the record that was updated, void on error
     */
    public function edit({{array:tables.columns}}{{if:tables.columns.primary:true}}${{tables.columns.name}}{{endif:tables.columns.primary}}{{array:tables.columns}}, array $vars)
    {
        {{array:tables.columns}}{{if:tables.columns.primary:true}}
        $vars['{{tables.columns.name}}'] = ${{tables.columns.name}};{{endif:tables.columns.primary}}{{array:tables.columns}}
        $this->Input->setRules($this->getRules($vars, true));

        if ($this->Input->validates($vars)) {
            $fields = [{{array:tables.columns}}'{{tables.columns.name}}',{{array:tables.columns}}];
            $this->Record->where({{array:tables.columns}}{{if:tables.columns.primary:true}}'{{tables.columns.name}}', '=', ${{tables.columns.name}}{{endif:tables.columns.primary}}{{array:tables.columns}})->update('{{tables.name}}', $vars, $fields);

            return {{array:tables.columns}}{{if:tables.columns.primary:true}}${{tables.columns.name}}{{endif:tables.columns.primary}}{{array:tables.columns}};
        }
    }

    /**
     * Permanently deletes the given record
     *{{array:tables.columns}}{{if:tables.columns.primary:true}}
     * @param int ${{tables.columns.name}} The identifier of the record to delete{{endif:tables.columns.primary}}{{array:tables.columns}}
     */
    public function delete({{array:tables.columns}}{{if:tables.columns.primary:true}}${{tables.columns.name}}{{endif:tables.columns.primary}}{{array:tables.columns}})
    {
        // Delete a record
        $this->Record->from('{{tables.name}}')->{{array:tables.columns}}{{if:tables.columns.primary:true}}
            where('{{tables.name}}.{{tables.columns.name}}', '=', ${{tables.columns.name}})->{{endif:tables.columns.primary}}{{array:tables.columns}}
            delete();
    }

    /**
     * Returns a partial query
     *
     * @param array $filters A list of filters for the query
     *{{array:tables.columns}}
     *  - {{tables.columns.name}}{{array:tables.columns}}
     * @return Record A partially built query
     */
    private function getRecord(array $filters = [])
    {
        $this->Record->select()->from('{{tables.name}}');{{array:tables.columns}}

        if (isset($filters['{{tables.columns.name}}'])) {
            $this->Record->where('{{tables.name}}.{{tables.columns.name}}', '=', $filters['{{tables.columns.name}}']);
        }{{array:tables.columns}}

        return $this->Record;
    }{{array:tables.columns}}{{if:tables.columns.type:ENUM}}

    /**
     * Gets a list of {{tables.columns.name}} values and their languages
     *
     * @return A list of {{tables.columns.name}} values and their languages
     */
    public function get{{tables.columns.uc_name}}Values()
    {
        return [{{array:tables.columns.values}}
            '{{tables.columns.values.value}}' => Language::_('{{tables.class_name}}.get_{{tables.columns.name}}_values.{{tables.columns.values.value}}', true),{{array:tables.columns.values}}
        ];
    }{{endif:tables.columns.type}}{{array:tables.columns}}

    /**
     * Returns all validation rules for adding/editing extensions
     *
     * @param array $vars An array of input key/value pairs
     *{{array:tables.columns}}
     *  - {{tables.columns.name}}{{array:tables.columns}}
     * @param bool $edit True if this if an edit, false otherwise
     * @return array An array of validation rules
     */
    private function getRules(array $vars, $edit = false)
    {
        $rules = [{{array:tables.columns}}
            '{{tables.columns.name}}' => [
                'valid' => [
                    'if_set' => $edit,{{if:tables.columns.type:ENUM}}
                    'rule' => ['array_key_exists', $this->get{{tables.columns.uc_name}}Values()],{{else:tables.columns.type}}
                    'rule' => true,{{endif:tables.columns.type}}
                    'message' => Language::_('{{tables.class_name}}.!error.{{tables.columns.name}}.valid', true)
                ]
            ],{{array:tables.columns}}
        ];

        return $rules;
    }
}
{{array:tables}}