<?php

/**
 * Tax rule management
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Taxes extends AppModel
{
    /**
     * Initialize Taxes
     */
    public function __construct()
    {
        parent::__construct();
        Language::loadLang(['taxes']);
    }

    /**
     * Adds a tax rule to the system
     *
     * @param array $vars An array of tax info including:
     *
     *  - company_id The company ID this tax rule applies to.
     *  - level The tax level this rule will be apart of.
     *  - name The name of the tax rule (optional, default NULL)
     *  - amount The tax amount (optional, default 0.00)
     *  - type The tax type (optional, default 'exclusive')
     *  - country The country this tax rule will apply to (optional, defalut NULL)
     *  - state The state this tax rule will apply to (optional, default NULL)
     *  - status The status of this tax rule (optional, default 'active')
     * @return int The ID of the tax rule created, void on error
     */
    public function add(array $vars)
    {
        $vars['status'] = (isset($vars['status']) ? $vars['status'] : 'active');
        $this->Input->setRules($this->getRules());

        if ($this->Input->validates($vars)) {
            // Add a tax rule
            $fields = ['company_id', 'level', 'name', 'amount', 'type', 'country', 'state', 'status'];
            $this->Record->insert('taxes', $vars, $fields);

            return $this->Record->lastInsertId();
        }
    }

    /**
     * Updates a tax rule
     *
     * @param int $tax_id The tax ID
     * @param array $vars An array of tax info including:
     *
     *  - company_id The company ID this tax rule applies to.
     *  - level The tax level this rule will be apart of.
     *  - name The name of the tax rule (optional, default NULL)
     *  - amount The tax amount (optional, default 0.00)
     *  - type The tax type (optional, default 'exclusive')
     *  - country The country this tax rule will apply to (optional, default NULL)
     *  - state The state tis tax rule will apply to (optional, default NULL)
     *  - status The status of this tax rule (optional, default 'active')
     * @return int The ID of the tax rule created, void on error
     */
    public function edit($tax_id, array $vars)
    {
        $rules = $this->getRules();
        $rules['tax_id'] = [
            'exists' => [
                'rule' => [[$this, 'validateExists'], 'id', 'taxes'],
                'message' => $this->_('Taxes.!error.tax_id.exists')
            ]
        ];

        $this->Input->setRules($rules);

        $vars['tax_id'] = $tax_id;

        if ($this->Input->validates($vars)) {
            // Inactivate the old tax rule and add a new tax rule
            $this->delete($tax_id);

            if (!isset($vars['status']) || $vars['status'] != 'inactive') {
                $this->add($vars);
            }
        }
    }

    /**
     * Sets a tax to inactive
     *
     * @param int $tax_id The ID of the tax rule to mark deleted (inactive)
     */
    public function delete($tax_id)
    {
        $vars = ['status' => 'inactive'];
        $this->Record->where('id', '=', $tax_id)->update('taxes', $vars, ['status']);
    }

    /**
     * Fetches all tax types
     *
     * @return array A key=>value array of tax types
     */
    public function getTaxTypes()
    {
        return [
            'inclusive_calculated' => $this->_('Taxes.getTaxTypes.inclusive_calculated'),
            'inclusive' => $this->_('Taxes.getTaxTypes.inclusive'),
            'exclusive' => $this->_('Taxes.getTaxTypes.exclusive')
        ];
    }

    /**
     * Fetches all tax levels
     *
     * @return array A key=>value array of tax levels
     */
    public function getTaxLevels()
    {
        // Tax levels 1 and 2, respectively
        return ['1' => 1, '2' => 2];
    }

    /**
     * Fetchas all status types
     *
     * @return array A key=>value array of tax statuses
     */
    public function getTaxStatus()
    {
        return [
            'active' => $this->_('Taxes.getTaxStatus.active'),
            'inactive' => $this->_('Taxes.getTaxStatus.inactive')
        ];
    }

    /**
     * Fetches a tax
     *
     * @param int $tax_id The tax ID
     * @return mixed A stdClass objects representing the tax, false if it does not exist
     */
    public function get($tax_id)
    {
        return $this->Record->select()
            ->select(['TRIM(taxes.amount)+?' => 'amount'], false)
            ->appendValues([0])
            ->from('taxes')
            ->where('id', '=', $tax_id)
            ->fetch();
    }

    /**
     * Retrieves a list of all tax rules for a particular company
     *
     * @param int $company_id The company ID
     * @return mixed An array of stdClass objects representing tax rules, or false if none exist
     */
    public function getAll($company_id)
    {
        // Get all tax rules
        $records = $this->Record->select()
            ->select(['TRIM(taxes.amount)+?' => 'amount'], false)
            ->appendValues([0])
            ->from('taxes')
            ->where('company_id', '=', $company_id)
            ->where('status', '=', 'active')
            ->order(['level' => 'asc'])
            ->fetchAll();

        $rules = [];

        // Sort tax rules by level
        foreach ($records as $record) {
            $rules['level_' . $record->level][] = $record;
        }

        return $rules;
    }

    /**
     * Validates a tax's 'type' field
     *
     * @param string $type The type to check
     * @return bool True if the type is validated, false otherwise
     */
    public function validateType($type)
    {
        switch ($type) {
            case 'exclusive':
            case 'inclusive':
            case 'inclusive_calculated':
                return true;
        }
        return false;
    }

    /**
     * Validates a tax's 'status' field
     *
     * @param string $status The status to check
     * @return bool True if the status is validated, false otherwise
     */
    public function validateStatus($status)
    {
        switch ($status) {
            case 'active':
            case 'inactive':
                return true;
        }
        return false;
    }

    /**
     * Returns the rule set for adding/editing taxes
     *
     * @return array Tax rules
     */
    private function getRules()
    {
        $rules = [
            'company_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'companies'],
                    'message' => $this->_('Taxes.!error.company_id.exists')
                ]
            ],
            'level' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'is_numeric',
                    'message' => $this->_('Taxes.!error.level.format')
                ],
                'length' => [
                    'if_set' => true,
                    'rule' => ['maxLength', 2],
                    'message' => $this->_('Taxes.!error.level.length')
                ]
            ],
            'name' => [
                'length' => [
                    'if_set' => true,
                    'rule' => ['maxLength', 64],
                    'message' => $this->_('Taxes.!error.name.length')
                ]
            ],
            'amount' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'is_numeric',
                    'message' => $this->_('Taxes.!error.amount.format')
                ],
                'positive' => [
                    'if_set' => true,
                    'rule' => function($amount) {
                        if (!is_numeric($amount)) {
                            return true; // the existing rule will handle the check for a number
                        }

                        // The amount must be positive
                        return $amount >= 0;
                    },
                    'message' => $this->_('Taxes.!error.amount.positive')
                ]
            ],
            'type' => [
                'format' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateType']],
                    'message' => $this->_('Taxes.!error.type.format')
                ]
            ],
            'country' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateExists'], 'alpha2', 'countries'],
                    'message' => $this->_('Taxes.!error.country.valid')
                ]
            ],
            'state' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateExists'], 'code', 'states'],
                    'message' => $this->_('Taxes.!error.state.valid')
                ]
            ],
            'status' => [
                'format' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateStatus']],
                    'message' => $this->_('Taxes.!error.status.format')
                ]
            ]
        ];
        return $rules;
    }
}
