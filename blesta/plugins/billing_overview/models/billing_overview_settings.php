<?php
/**
 * Billing Overview settings
 *
 * @package blesta
 * @subpackage blesta.plugins.billing_overview.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class BillingOverviewSettings extends BillingOverviewModel
{
    /**
     * @var array A list of default billing overview settings
     */
    private static $default_settings = [
        ['key' => 'revenue_today', 'value' => 1, 'order' => 1],
        ['key' => 'revenue_month', 'value' => 1, 'order' => 2],
        ['key' => 'revenue_year', 'value' => 1, 'order' => 3],
        ['key' => 'credits_today', 'value' => 0, 'order' => 4],
        ['key' => 'credits_month', 'value' => 0, 'order' => 5],
        ['key' => 'credits_year', 'value' => 0, 'order' => 6],
        ['key' => 'invoiced_today', 'value' => 1, 'order' => 7],
        ['key' => 'invoiced_today_proforma', 'value' => 0, 'order' => 8],
        ['key' => 'invoiced_month', 'value' => 1, 'order' => 9],
        ['key' => 'invoiced_month_proforma', 'value' => 0, 'order' => 10],
        ['key' => 'balance_outstanding', 'value' => 1, 'order' => 11],
        ['key' => 'balance_overdue', 'value' => 1, 'order' => 12],
        ['key' => 'scheduled_cancelation', 'value' => 1, 'order' => 13],
        ['key' => 'services_active', 'value' => 1, 'order' => 14],
        ['key' => 'services_added_today', 'value' => 1, 'order' => 15],
        ['key' => 'services_canceled_today', 'value' => 1, 'order' => 16],
        ['key' => 'graph_revenue', 'value' => 1, 'order' => 17],
        ['key' => 'graph_revenue_year', 'value' =>1, 'order' => 18],
        ['key' => 'graph_invoiced', 'value' => 1, 'order' => 19],
        ['key' => 'show_legend', 'value' => 1, 'order' => 20],
        ['key' => 'date_range', 'value' => 7, 'order' => 21]
    ];

    /**
     * Initialize
     */
    public function __construct()
    {
        parent::__construct();
        Language::loadLang('billing_overview_settings', null, PLUGINDIR . 'billing_overview' . DS . 'language' . DS);
    }

    /**
     * Adds new staff settings for the Billing Overview settings
     *
     * @param int $staff_id The ID of the staff member whose settings to update
     * @param int $company_id The ID of the company to which this staff member belongs
     * @param array $vars A numerically-indexed list of overview setting key/value pairs
     */
    public function add($staff_id, $company_id, array $vars)
    {
        $rules = [
            'staff_id'=>[
                'exists'=>[
                    'rule'=>[[$this, 'validateExists'], 'id', 'staff'],
                    'message'=>$this->_('BillingOverviewSettings.!error.staff_id.exists')
                ]
            ],
            'company_id'=>[
                'exists'=>[
                    'rule'=>[[$this, 'validateExists'], 'id', 'companies'],
                    'message'=>$this->_('BillingOverviewSettings.!error.company_id.exists')
                ]
            ],
            'settings[][key]'=>[
                'empty'=>[
                    'rule'=>'isEmpty',
                    'negate'=>true,
                    'message'=>$this->_('BillingOverviewSettings.!error.settings[][key].empty')
                ]
            ],
            'settings[][value]'=>[
                'length'=>[
                    'rule'=>['maxLength', 255],
                    'message'=>$this->_('BillingOverviewSettings.!error.settings[][value].length')
                ]
            ]
        ];

        $input = [
            'staff_id'=>$staff_id,
            'company_id'=>$company_id,
            'settings'=>$vars
        ];

        $this->Input->setRules($rules);

        if ($this->Input->validates($input)) {
            // Save each setting
            foreach ($input['settings'] as $setting) {
                $value = isset($setting['value']) ? $setting['value'] : '';
                $order = isset($setting['order']) ? $setting['order'] : null;

                // Set input settings
                $settings = ['staff_id'=>$staff_id, 'company_id'=>$company_id, 'key'=>$setting['key'], 'value'=>$value];

                $this->Record->duplicate('value', '=', $value);
                // Set order if given
                if ($order != null) {
                    $settings['order'] = $order;
                    $this->Record->duplicate('order', '=', $order);
                }

                $this->Record->insert('billing_overview_settings', $settings);
            }
        }
    }

    /**
     * Saves the default settings for the given staff member
     *
     * @param int $staff_id The ID of the staff member whose settings to update
     * @param int $company_id The ID of the company to which this staff member belongs
     */
    public function addDefault($staff_id, $company_id)
    {
        $this->add($staff_id, $company_id, self::$default_settings);
    }

    /**
     * Retrieves a list of all billing overview settings for a given staff member
     *
     * @param int $staff_id The staff ID of the staff member to get settings for
     * @param int $company_id The company ID to which the staff member belongs
     * @return array A list of billing overview settings for the given staff member
     */
    public function getSettings($staff_id, $company_id, $order_by = ['order' => 'asc'])
    {
        return $this->Record->select()->from('billing_overview_settings')->
            where('staff_id', '=', $staff_id)->where('company_id', '=', $company_id)->
            order($order_by)->
            fetchAll();
    }
}
