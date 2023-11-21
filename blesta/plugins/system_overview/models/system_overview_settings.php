<?php
/**
 * System Overview settings
 *
 * @package blesta
 * @subpackage blesta.plugins.system_overview.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class SystemOverviewSettings extends SystemOverviewModel
{
    /**
     * @var array A list of default system overview settings
     */
    private static $default_settings = [
        ['key'=>'clients_active', 'value'=>1, 'order'=>1],
        ['key'=>'active_users_today', 'value'=>1, 'order'=>2],
        ['key'=>'services_active', 'value'=>1, 'order'=>3],
        ['key'=>'services_scheduled_cancellation', 'value'=>0, 'order'=>4],
        ['key'=>'recurring_invoices', 'value'=>1, 'order'=>5],
        ['key'=>'pending_orders', 'value'=>0, 'order'=>6],
        ['key'=>'open_tickets', 'value'=>0, 'order'=>7],
        ['key'=>'show_one_tab', 'value'=>0, 'order'=>8],
        ['key'=>'graph_clients', 'value'=>1, 'order'=>9],
        ['key'=>'graph_services', 'value'=>1, 'order'=>10],
        ['key'=>'show_legend', 'value'=>1, 'order'=>11],
        ['key'=>'date_range', 'value'=>7, 'order'=>12],
    ];

    /**
     * Initialize
     */
    public function __construct()
    {
        parent::__construct();
        Language::loadLang('system_overview_settings', null, PLUGINDIR . 'system_overview' . DS . 'language' . DS);
    }

    /**
     * Adds new staff settings for the System Overview settings
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
                    'message'=>$this->_('SystemOverviewSettings.!error.staff_id.exists')
                ]
            ],
            'company_id'=>[
                'exists'=>[
                    'rule'=>[[$this, 'validateExists'], 'id', 'companies'],
                    'message'=>$this->_('SystemOverviewSettings.!error.company_id.exists')
                ]
            ],
            'settings[][key]'=>[
                'empty'=>[
                    'rule'=>'isEmpty',
                    'negate'=>true,
                    'message'=>$this->_('SystemOverviewSettings.!error.settings[][key].empty')
                ]
            ],
            'settings[][value]'=>[
                'length'=>[
                    'rule'=>['maxLength', 255],
                    'message'=>$this->_('SystemOverviewSettings.!error.settings[][value].length')
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

                // Set order if given
                if ($order != null) {
                    $settings['order'] = $order;
                }

                $this->Record->duplicate('value', '=', $value)->
                    insert('system_overview_settings', $settings);
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
     * Retrieves a list of all system overview settings for a given staff member
     *
     * @param int $staff_id The staff ID of the staff member to get settings for
     * @param int $company_id The company ID to which the staff member belongs
     * @return array A list of system overview settings for the given staff member
     */
    public function getSettings($staff_id, $company_id, $order_by = ['order' => 'asc'])
    {
        return $this->Record->select()->from('system_overview_settings')->
            where('staff_id', '=', $staff_id)->where('company_id', '=', $company_id)->
            order($order_by)->
            fetchAll();
    }
}
