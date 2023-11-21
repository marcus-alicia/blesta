<?php
/**
 * System Overview Users
 *
 * @package blesta
 * @subpackage blesta.plugins.system_overview.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class SystemOverviewUsers extends SystemOverviewModel
{
    /**
     * Initialize
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Retrieves a list of all recently active users
     *
     * @param int $company_id The company ID
     * @param int $limit The maximum number of users to fetch
     * @return array A list of stdClass objects representing each user online
     */
    public function getRecentUsers($company_id, $limit = 15)
    {
        $fields = ['log_users.id', 'log_users.user_id',
            'log_users.ip_address', 'log_users.company_id',
            'log_users.date_added', 'MAX(log_users.date_updated)' => 'date_logged',
            'log_users.result'
        ];
        $sql_log = $this->Record->select($fields)->
            from('log_users')->
            where('log_users.result', '=', 'success')->
            group(['log_users.user_id'])->
            get();
        $values = $this->Record->values;
        $this->Record->reset();

        $fields = ['l1.*',
            'IFNULL(staff.first_name,IFNULL(contacts.first_name,contacts2.first_name))' => 'first_name',
            'IFNULL(staff.last_name,IFNULL(contacts.last_name,contacts2.last_name))' => 'last_name',
            'IFNULL(clients.id,contacts2.client_id)' => 'client_id'
        ];

        return $this->Record->select($fields)->from(['log_users' => 'l1'])->
            appendValues($values)->
                on('l1.user_id', '=', 'online.user_id', false)->
                on('l1.date_updated', '=', 'online.date_logged', false)->
            innerJoin([$sql_log => 'online'])->
            leftJoin('staff', 'staff.user_id', '=', 'l1.user_id', false)->
            leftJoin('clients', 'clients.user_id', '=', 'l1.user_id', false)->
            on('contacts.contact_type', '=', 'primary')->
            leftJoin('contacts', 'contacts.client_id', '=', 'clients.id', false)->
            leftJoin(['contacts' => 'contacts2'], 'contacts2.user_id', '=', 'l1.user_id', false)->
            where('l1.company_id', '=', $company_id)->
            order(['l1.date_updated' => 'DESC'])->
            limit($limit)->
            fetchAll();
    }
}
