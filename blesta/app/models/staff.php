<?php

/**
 * Staff management
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Staff extends AppModel
{
    /**
     * Initialize Staff
     */
    public function __construct()
    {
        parent::__construct();
        Language::loadLang(['staff']);
    }

    /**
     * Add a staff member
     *
     * @param array $vars An array of staff member info including:
     *
     *  - user_id The user ID belonging to this staff member
     *  - first_name The first name of this staff member
     *  - last_name The last name of this staff member
     *  - email The email address of this staff member
     *  - email_mobile The mobile email address of this staff member (optional)
     *  - number_mobile The mobile phone number of this staff member (optional)
     *  - status The status of this staff member 'active', 'inactive' (optional, default active)
     *  - groups An array of staff group IDs this staff member belongs to
     * @return int The ID of the staff member added, void on error
     */
    public function add(array $vars)
    {
        // Trigger the Staff.addBefore event
        extract($this->executeAndParseEvent('Staff.addBefore', ['vars' => $vars]));

        $this->Input->setRules($this->getRules($vars));

        if ($this->Input->validates($vars)) {
            // Add staff
            $fields = ['user_id', 'first_name', 'last_name', 'email', 'email_mobile', 'number_mobile', 'status'];
            $this->Record->insert('staff', $vars, $fields);

            $staff_id = $this->Record->lastInsertId();

            for ($i = 0, $num_groups = count($vars['groups']); $i < $num_groups; $i++) {
                $this->assignGroup($staff_id, $vars['groups'][$i]);
            }

            // Log that the staff was created
            $log_vars = array_intersect_key($vars, array_flip($fields));
            $this->logger->info('Created Staff', array_merge($log_vars, ['id' => $staff_id]));

            // Trigger the Staff.addAfter event
            $this->executeAndParseEvent('Staff.addAfter', ['staff_id' => $staff_id, 'vars' => $vars]);

            return $staff_id;
        }
    }

    /**
     * Updates the given staff member with only the values given in $vars
     *
     * @param int $staff_id The ID of the staff member to update
     * @param array $vars An array of staff member info including:
     *
     *  - first_name The first name of this staff member (optional)
     *  - last_name The last name of this staff member (optional)
     *  - email The email address of this staff member (optional)
     *  - email_mobile The mobile email address of this staff member (optional)
     *  - number_mobile The mobile phone number of this staff member (optional)
     *  - status The status of this staff member 'active', 'inactive' (optional, default 'active')
     *  - groups An array of staff group IDs this staff member belongs to (optional)
     */
    public function edit($staff_id, array $vars)
    {
        // Trigger the Staff.editBefore event
        extract($this->executeAndParseEvent('Staff.editBefore', ['staff_id' => $staff_id, 'vars' => $vars]));

        $this->Input->setRules($this->getRules($vars, true));

        if ($this->Input->validates($vars)) {
            $staff = $this->get($staff_id);

            // Update groups if given any
            if (isset($vars['groups'])) {
                // Delete from staff_group
                $this->Record->from('staff_group')->where('staff_id', '=', $staff_id)->delete();

                // Insert into staff_group
                foreach ($vars['groups'] as $key => $id) {
                    $this->assignGroup($staff_id, $id);
                }
            }

            // Update staff
            $fields = ['first_name', 'last_name', 'email', 'email_mobile', 'number_mobile', 'status'];
            $this->Record->where('id', '=', $staff_id)->update('staff', $vars, $fields);

            // Log that the staff was updated
            $log_vars = array_intersect_key($vars, array_flip($fields));
            $this->logger->info('Updated Staff', array_merge($log_vars, ['id' => $staff_id]));

            // Trigger the Staff.editAfter event
            $this->executeAndParseEvent(
                'Staff.editAfter',
                ['staff_id' => $staff_id, 'vars' => $vars, 'old_staff' => $staff]
            );
        }
    }

    /**
     * Permanently removes the staff member from the system
     *
     * @param int $staff_id The ID of the staff member to remove from the system
     * @deprecated since version 4.1.0
     */
    public function delete($staff_id)
    {
        // Delete from staff, staff_group, and staff_settings tables
        $this->Record->from('staff')
            ->from('staff_group')
            ->from('staff_settings')
            ->where('staff.id', '=', $staff_id)
            ->where('staff.id', '=', 'staff_group.staff_id', false)
            ->where('staff_group.staff_id', '=', 'staff_settings.staff_id', false)
            ->delete(['staff.*', 'staff_group.*', 'staff_settings.*']);
    }

    /**
     * Assigns the given staff ID to the given staff group ID
     *
     * @param int $staff_id The ID of the staff member to assign to the given group
     * @param int $staff_group_id The ID of the staff group to assign to the given staff member
     */
    public function assignGroup($staff_id, $staff_group_id)
    {
        // Unassign if already exists
        $this->Record->from('staff_group')
            ->where('staff_id', '=', $staff_id)
            ->where('staff_group_id', '=', $staff_group_id)
            ->delete();

        // Assign the staff group
        $this->Record->set('staff_id', $staff_id)
            ->set('staff_group_id', $staff_group_id)
            ->insert('staff_group');
    }

    /**
     * Adds a staff email notice
     *
     * @param array $vars An array of staff notice information including:
     *
     *  - staff_group_id The ID of the staff group this notice will be added to
     *  - staff_id The ID of the staff member
     *  - action The email group action
     */
    public function addNotice(array $vars)
    {
        $this->Input->setRules($this->getNoticeRules($vars));

        if ($this->Input->validates($vars)) {
            // Add a new notice, but allow duplicates to be added without error
            $this->Record->duplicate('action', '=', $vars['action'])
                ->insert('staff_notices', $vars, ['staff_group_id', 'staff_id', 'action']);
        }
    }

    /**
     * Adds multiple staff email notices
     *
     * @param $staff_id The ID of the staff member
     * @param $staff_group_id The ID of the staff group these notices will be added to
     * @param array $actions A list of staff notices, each containing:
     *
     *  - action The email group action
     */
    public function addNotices($staff_id, $staff_group_id, array $actions)
    {
        // Set input
        $vars = [
            'staff_id' => $staff_id,
            'staff_group_id' => $staff_group_id,
            'actions' => $actions
        ];

        // Get rules
        $rules = $this->getNoticeRules($vars);
        unset($rules['action']);

        // Validate each action
        $rules['actions[]'] = [
            'exists' => [
                'if_set' => true,
                'rule' => [[$this, 'validateNoticeActionExists'], $staff_group_id],
                'message' => $this->_('Staff.!error.action[].exists')
            ]
        ];

        $this->Input->setRules($rules);

        if ($this->Input->validates($vars)) {
            // Delete all current staff notices
            $this->deleteNotice($staff_id, $staff_group_id);

            // Add a new notice, but allow duplicates to be added without error
            foreach ($vars['actions'] as $action) {
                $notice = ['staff_id' => $staff_id, 'staff_group_id' => $staff_group_id, 'action' => $action];
                $this->Record->duplicate('action', '=', $action)->
                    insert('staff_notices', $notice, ['staff_group_id', 'staff_id', 'action']);
            }
        }
    }

    /**
     * Deletes the given staff group notice
     *
     * @param int $staff_id The ID of the staff member
     * @param int $staff_group_id The ID of the staff group the notice belongs to
     * @param string $action The email group action to remove (optional, default null to delete all notices)
     */
    public function deleteNotice($staff_id, $staff_group_id, $action = null)
    {
        $this->deleteStaffNotices($staff_id, $staff_group_id, $action);
    }

    /**
     * Deletes the staff notices
     *
     * @param int $staff_id The ID of the staff member
     * @param int $staff_group_id The ID of the staff group
     * @param string $action The email group action (optional)
     */
    private function deleteStaffNotices($staff_id, $staff_group_id, $action = null)
    {
        // Delete the notice from the staff member
        $this->Record->from('staff_notices')->
            where('staff_id', '=', $staff_id)->
            where('staff_group_id', '=', $staff_group_id);

        if ($action) {
            $this->Record->where('action', '=', $action);
        }

        $this->Record->delete();
    }

    /**
     * Fetches a staff member and all associated staff settings and staff groups
     *
     * @param int $staff_id The ID of the staff member
     * @param int $company_id The ID of the company to set staff settings for
     *  (optional, if null, no settings will be set)
     * @return mixed An array of objects or false if no results.
     * @see Staff::getByUserId()
     */
    public function get($staff_id, $company_id = null)
    {
        $fields = ['staff.id', 'staff.user_id', 'staff.first_name', 'staff.last_name',
            'staff.email', 'staff.email_mobile', 'staff.number_mobile', 'staff.status', 'users.username',
            'users.two_factor_mode', 'users.two_factor_key', 'users.two_factor_pin'
        ];

        $staff = $this->Record->select($fields)->from('staff')->
            innerJoin('users', 'users.id', '=', 'staff.user_id', false)->
            where('staff.id', '=', $staff_id)->fetch();

        // Assign object properties only if staff exists
        if ($staff) {
            // Get the staff groups this staff member belongs to
            $this->Record->select(['staff_groups.id', 'staff_groups.company_id', 'staff_groups.name'])->
                from('staff')->
                innerJoin('staff_group', 'staff_group.staff_id', '=', 'staff.id', false)->
                innerJoin('staff_groups', 'staff_group.staff_group_id', '=', 'staff_groups.id', false)->
                where('staff.id', '=', $staff_id);

            // Get a single or multiple groups depending on company
            if ($company_id) {
                $staff->group = $this->Record->where('staff_groups.company_id', '=', $company_id)->fetch();
            } else {
                $staff->groups = $this->Record->fetchAll();
            }

            if ($company_id !== null) {
                $staff->settings = $this->getSettings($staff_id, $company_id);
            }

            // Set staff email notices
            $staff->notices = $this->getNotices($staff_id);
        }
        return $staff;
    }

    /**
     * Fetches a staff member and all associated staff settings and staff groups
     *
     * @param int $user_id The ID of the user
     * @param int $company_id The ID of the company to set staff settings for
     *  (optional, if null, no settings will be set)
     * @return mixed An array of objects or false if no results.
     * @see Staff::get()
     */
    public function getByUserId($user_id, $company_id = null)
    {
        $fields = ['staff.id', 'staff.user_id', 'staff.first_name', 'staff.last_name',
            'staff.email', 'staff.email_mobile', 'staff.number_mobile', 'staff.status', 'users.username',
            'users.two_factor_mode', 'users.two_factor_key', 'users.two_factor_pin'
        ];

        $staff = $this->Record->select($fields)->from('staff')->
                innerJoin('users', 'users.id', '=', 'staff.user_id', false)->
                where('staff.user_id', '=', $user_id)->fetch();

        // Assign object properties only if staff exists
        if ($staff) {
            $staff->groups = $this->Record->select(
                ['staff_groups.id', 'staff_groups.company_id', 'staff_groups.name']
            )
                ->from('staff')
                ->innerJoin('staff_group', 'staff_group.staff_id', '=', 'staff.id', false)
                ->innerJoin('staff_groups', 'staff_group.staff_group_id', '=', 'staff_groups.id', false)
                ->where('staff.id', '=', $staff->id)
                ->fetchAll();

            if ($company_id !== null) {
                $staff->settings = $this->getSettings($staff->id, $company_id);
            }

            // Set staff email notices
            $staff->notices = $this->getNotices($staff->id);
        }
        return $staff;
    }

    /**
     * Fetches all staff members that can be sent the email corresponding to the given email group action
     *
     * @param string $action The email group action representing an email type
     * @param int $company_id The ID of the company whose staff members to fetch from
     * @param string $notice_type The type of email notice to fetch (optional) one of:
     *
     *  - all Any email notice type
     *  - null Notice types that are not set
     *  - bcc BCC notice types
     *  - to To notice types
     * @param string $status The status of the staff member ("active",
     *  "inactive", or null for all; optional, default null)
     * @return array A list of stdClass objects, each representing a staff member
     */
    public function getAllByEmailAction($action, $company_id, $notice_type = 'all', $status = null)
    {
        $this->Record->select('staff_notices.staff_id')->from('staff_notices')->
            innerJoin('staff_groups', 'staff_groups.id', '=', 'staff_notices.staff_group_id', false);

        // Filter on notice type
        if ($notice_type != 'all') {
            $this->Record->on('email_groups.notice_type', '=', $notice_type)->
                innerJoin('email_groups', 'email_groups.action', '=', 'staff_notices.action', false);
        }

        $staff_id_sql = $this->Record->where('staff_groups.company_id', '=', $company_id)->
            where('staff_notices.action', '=', $action)->
            group('staff_notices.staff_id')->
            get();
        $staff_id_values = $this->Record->values;
        $this->Record->reset();

        $this->Record->values = $staff_id_values;
        $this->Record->select(['staff.*'])->from('staff')->
            innerJoin([$staff_id_sql => 'temp'], 'temp.staff_id', '=', 'staff.id', false);

        // Filter on staff member status
        if ($status) {
            $this->Record->where('staff.status', '=', $status);
        }

        return $this->Record->fetchAll();
    }

    /**
     * Fetches a list of all staff members
     *
     * @param int $company_id The company ID to fetch (optional, default null)
     * @param string $status The status of the staff member to retrieve ('active', 'inactive', default null for all)
     * @param int $page The page to return results for
     * @param string $order_by The sort and order conditions (e.g. array('sort_field'=>"ASC"), optional)
     * @return mixed An array of objects when $count is false, an integer when $count is true, or false if no results.
     */
    public function getList($company_id = null, $status = null, $page = 1, $order_by = ['id' => 'ASC'])
    {
        $this->Record = $this->getStaff($company_id, $status);

        // Return the results
        return $this->Record->order($order_by)->
            limit($this->getPerPage(), (max(1, $page) - 1) * $this->getPerPage())->fetchAll();
    }

    /**
     * Returns the total number of staff members returned from Staff::getList(),
     * useful in constructing pagination for the getList() method.
     *
     * @param int $company_id The company ID to fetch (optional, default null)
     * @param string $status The status of the staff member to retrieve ('active', 'inactive', default null for all)
     * @return int The total number of staff members
     * @see Staff::getList()
     */
    public function getListCount($company_id = null, $status = null)
    {
        $this->Record = $this->getStaff($company_id, $status);

        // Return the number of results
        return $this->Record->numResults();
    }

    /**
     * Returns a list of all staff members
     *
     * @param int $company_id The ID of the company from which to fetch staff (optional)
     * @param string $status The status of the staff to fetch ('active' or 'inactive', null for all)
     * @param int $staff_group_id The ID of the client group that the staff must belong to (optional)
     * @return array A list of stdClass objects each representing a staff member
     */
    public function getAll($company_id = null, $status = null, $staff_group_id = null)
    {
        return $this->getStaff($company_id, $status, $staff_group_id)->
            order(['first_name' => 'asc'])->fetchAll();
    }

    /**
     * Partially constructs the query required by both Staff::getList() and
     * Staff::getListCount()
     *
     * @param int $company_id The ID of the company from which to fetch staff (optional)
     * @param string $status The status of the staff to fetch ('active' or 'inactive', null for all)
     * @param int $staff_group_id The ID of the client group that the staff must belong to (optional)
     * @return Record The partially constructed query Record object
     */
    private function getStaff($company_id = null, $status = null, $staff_group_id = null)
    {
        $this->Record->select('staff.*')->from('staff_group')->
            innerJoin('staff', 'staff.id', '=', 'staff_group.staff_id', false)->
            innerJoin('staff_groups', 'staff_groups.id', '=', 'staff_group.staff_group_id', false);

        if ($company_id != null) {
            $this->Record->where('staff_groups.company_id', '=', $company_id);
        }
        if ($status != null) {
            $this->Record->where('staff.status', '=', $status);
        }
        if ($staff_group_id != null) {
            $this->Record->where('staff_groups.id', '=', $staff_group_id);
        }

        $this->Record->group('staff.id');

        return $this->Record;
    }

    /**
     * Fetches all staff group notices
     *
     * @param int $staff_id The ID of the staff member
     * @param int $staff_group_id The ID of the staff group (optional, default null for all)
     * @param bool $group_by_action True to group by the action, false otherwise (optional, default true)
     * @return array A list of all staff group notices
     */
    public function getNotices($staff_id, $staff_group_id = null, $group_by_action = true)
    {
        $this->Record->select()->from('staff_notices')->
            where('staff_id', '=', $staff_id);

        if ($staff_group_id) {
            $this->Record->where('staff_group_id', '=', $staff_group_id);
        }

        if ($group_by_action) {
            $this->Record->group('action');
        }

        return $this->Record->fetchAll();
    }

    /**
     * Adds a quicklink for a staff member
     *
     * @param int $staff_id The staff ID of this staff member
     * @param int $company_id The company ID of this staff member
     * @param array $vars An array of quicklink info including:
     *
     *  - uri The URI of the link to save
     *  - title The title of this quicklink
     *  - order The sort order of this quicklink (optional, default 0)
     */
    public function addQuickLink($staff_id, $company_id, array $vars)
    {
        $vars['staff_id'] = $staff_id;
        $vars['company_id'] = $company_id;

        // Sort order not given, set a default
        if (empty($vars['order'])) {
            $vars['order'] = 0;
        }

        $rules = [
            'staff_id' => [
                'format' => [
                    'rule' => 'is_numeric',
                    'message' => $this->_('Staff.!error.staff_id.format')
                ]
            ],
            'company_id' => [
                'format' => [
                    'rule' => 'is_numeric',
                    'message' => $this->_('Staff.!error.company_id.format')
                ]
            ],
            'uri' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('Staff.!error.uri.empty')
                ]
            ],
            'title' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('Staff.!error.title.empty')
                ]
            ],
            'order' => [
                'format' => [
                    'rule' => 'is_numeric',
                    'message' => $this->_('Staff.!error.order.format')
                ],
                'length' => [
                    'rule' => ['maxLength', 5],
                    'message' => $this->_('Staff.!error.order.length')
                ]
            ]
        ];

        $this->Input->setRules($rules);

        if ($this->Input->validates($vars)) {
            $fields = ['staff_id', 'company_id', 'uri', 'title', 'order'];

            $this->Record->duplicate('title', '=', $vars['title'])->
                insert('staff_links', $vars, $fields);
        }
    }

    /**
     * Removes a quicklink for a staff member
     *
     * @param int $staff_id The staff ID of this staff member
     * @param int $company_id The company ID of this staff member
     * @param string $uri The URI of the link to remove
     */
    public function deleteQuickLink($staff_id, $company_id, $uri)
    {
        $this->Record->from('staff_links')->where('staff_id', '=', $staff_id)->
            where('company_id', '=', $company_id)->where('uri', '=', $uri)->delete();
    }

    /**
     * Retrieves all quicklinks for a given staff member
     *
     * @param int $staff_id The staff ID of this staff member
     * @param int $company_id The company ID of this staff member
     * @return mixed An array of objects representing the quicklinks, or false if none exist
     */
    public function getQuickLinks($staff_id, $company_id)
    {
        return $this->Record->select()->from('staff_links')->where('staff_id', '=', $staff_id)->
            where('company_id', '=', $company_id)->order(['order' => 'ASC'])->fetchAll();
    }

    /**
     * Sends the notification email matching the given action to all staff members that should receive it.
     * Sets Input errors on error
     *
     * @param string $action The action that specifies the email group to use
     * @param int $company_id The company ID to send this email under
     * @param array $tags An array of replacement tags containing the
     *  key/value pairs for the replacements where the key is the tag to
     *  replace and the value is the value to replace it with (optional; A
     *  'staff' tag will automatically be set)
     * @param mixed $cc The CC address(es) to send to. A string, or an array of email addresses
     * @param mixed $bcc The BCC address(es) to send to. A string, or an array of email addresses
     * @param array $attachments A multi-dimensional array of attachments containing:
     *
     *  - path The path to the attachment on the file system
     *  - name The name of the attachment (optional, default '')
     *  - encoding The file encoding (optional, default 'base64')
     *  - type The type of attachment (optional, default 'application/octet-stream')
     * @param array $options An array of options including:
     *
     *  - to_client_id The ID of the client the message was sent to
     *  - from_staff_id The ID of the staff member the message was sent from
     *  - from The from address
     *  - from_name The from name
     *  - reply_to The reply to address
     */
    public function sendNotificationEmail(
        $action,
        $company_id,
        array $tags = null,
        $cc = null,
        $bcc = null,
        array $attachments = null,
        array $options = null
    ) {
        // Load Emails model
        Loader::loadModels($this, ['Emails']);

        // Get all active staff members that this email should be sent to
        $staff = $this->getAllByEmailAction($action, $company_id, 'to', 'active');

        foreach ($staff as $staff_member) {
            // Fetch the staff language
            $lang = $this->getSetting($staff_member->id, 'language', $company_id);
            $lang = ($lang ? $lang->value : null);

            // Set the staff member as an available tag
            if (empty($tags)) {
                $tags = [];
            }
            $tags = array_merge($tags, ['staff' => $staff_member]);


            if (isset($tags['package'])) {
                // Set package name to the translation in the recipients language
                foreach ((isset($tags['package']->names) ? $tags['package']->names : []) as $name) {
                    if ($name->lang == $lang) {
                        $tags['package']->name = $name->name;
                        break;
                    }
                }

                // Set package descriptions to their translation in the recipients language
                foreach ((isset($tags['package']->descriptions) ? $tags['package']->descriptions : []) as $description) {
                    if ($description->lang == $lang) {
                        $tags['package']->description = $description->text;
                        $tags['package']->description_html = $description->html;
                        break;
                    }
                }
            }

            // Send the email
            $this->Emails->send(
                $action,
                $company_id,
                $lang,
                $staff_member->email,
                $tags,
                $cc,
                $bcc,
                $attachments,
                $options
            );
        }
    }

    /**
     * Fetch all settings that may apply to this staff member. Settings are inherited
     * in the order of staff_settings -> company_settings -> settings
     * where "->" represents the left item inheriting (and overwriting in the
     * case of duplicates) values found in the right item.
     *
     * @param int $staff_id The staff ID to retrieve settings for
     * @param int $company_id The company ID to retrieve settings for
     * @return mixed An array of objects containg key/values for the settings, false if no records found
     */
    public function getSettings($staff_id, $company_id)
    {
        // Staff Settings
        $sql1 = $this->Record->select(['key', 'value'])
            ->from('staff_settings')
            ->where('staff_id', '=', $staff_id)
            ->get();
        $values = $this->Record->values;
        $this->Record->reset();
        $this->Record->values = $values;

        // Company Settings
        $sql2 = $this->Record->select(['key', 'value'])->from('staff')->
            innerJoin('staff_group', 'staff_group.staff_id', '=', 'staff.id', false)->
            innerJoin('staff_groups', 'staff_groups.id', '=', 'staff_group.staff_group_id', false)->
            innerJoin('company_settings', 'company_settings.company_id', '=', 'staff_groups.company_id', false)->
            where('staff.id', '=', $staff_id)->where('staff_groups.company_id', '=', $company_id)->get();
        $values = $this->Record->values;
        $this->Record->reset();
        $this->Record->values = $values;

        // System settings
        $sql3 = $this->Record->select(['key', 'value'])->from('settings')->get();
        $values = $this->Record->values;
        $this->Record->reset();
        $this->Record->values = $values;

        return $this->Record->select()
            ->from(['((' . $sql1 . ') UNION (' . $sql2 . ') UNION (' . $sql3 . '))' => 'temp'])
            ->group('temp.key')
            ->fetchAll();
    }

    /**
     * Fetch a single setting by key name
     *
     * @param int $staff_id The ID of the staff member to fetch the setting for
     * @param string $key The key name of the setting to fetch
     * @param int $company_id The ID of the company to inherit settings from
     * @return mixed An stdObject containg the key and value, false if no such key exists
     */
    public function getSetting($staff_id, $key, $company_id = null)
    {
        $sql = [];
        // Staff Settings
        $sql[] = $this->Record->select(['key', 'value'])->from('staff_settings')->
            where('staff_id', '=', $staff_id)->where('key', '=', $key)->get();
        $values = $this->Record->values;
        $this->Record->reset();
        $this->Record->values = $values;

        // Company Settings
        if ($company_id !== null) {
            $sql[] = $this->Record->select(['key', 'value'])
                ->from('staff')
                ->innerJoin('staff_group', 'staff_group.staff_id', '=', 'staff.id', false)
                ->innerJoin('staff_groups', 'staff_groups.id', '=', 'staff_group.staff_group_id', false)
                ->innerJoin('company_settings', 'company_settings.company_id', '=', 'staff_groups.company_id', false)
                ->where('staff.id', '=', $staff_id)
                ->where('company_settings.key', '=', $key)
                ->where('staff_groups.company_id', '=', $company_id)
                ->get();
            $values = $this->Record->values;
            $this->Record->reset();
            $this->Record->values = $values;
        }

        // System settings
        $sql[] = $this->Record->select(['key', 'value'])->from('settings')->where('key', '=', $key)->get();
        $values = $this->Record->values;
        $this->Record->reset();
        $this->Record->values = $values;

        return $this->Record->select()
            ->from(['((' . implode(') UNION (', $sql) . '))' => 'temp'])
            ->group('temp.key')
            ->fetch();
    }

    /**
     * Add multiple staff settings, if duplicate key update the setting
     *
     * @param int $staff_id The ID for the specified staff member
     * @param array $vars A single dimensional array of key/value pairs of settings
     */
    public function setSettings($staff_id, array $vars)
    {
        foreach ($vars as $key => $value) {
            $this->setSetting($staff_id, $key, $value);
        }
    }

    /**
     * Add a staff setting, if duplicate key update the setting
     *
     * @param int $staff_id The ID for the specified staff member
     * @param string $key The key for this staff setting
     * @param string $value The value for this staff setting
     */
    public function setSetting($staff_id, $key, $value)
    {
        if ($key == 'language' && ($staff = $this->get($staff_id))) {
            $staff_language = $this->getSetting($staff_id, $key);
            if ($staff_language && $staff_language->value != $value) {
                foreach ($staff->groups as $staff_group) {
                    // Clear nav cache for this staff member
                    Cache::clearCache(
                        'nav_staff_group_' . $staff_group->id,
                        $staff_group->company_id . DS . 'nav' . DS . $staff_id . DS
                    );
                }
            }
        }

        // Add or update a staff setting
        $this->Record->duplicate('value', '=', $value)->
            insert('staff_settings', ['key' => $key, 'staff_id' => $staff_id, 'value' => $value]);
    }

    /**
     * Delets a staff setting
     *
     * @param int $staff_id The ID for the specified staff member
     * @param string $key The key for this staff setting
     */
    public function unsetSetting($staff_id, $key)
    {
        $this->Record->from('staff_settings')->where('key', '=', $key)->
            where('staff_id', '=', $staff_id)->delete();
    }

    /**
     * Saves the current state of the clients widget boxes
     *
     * @param int $staff_id The ID of the staff member to save the widget box state for
     * @param int $company_id The ID of the company to save the widget box state for (in combination with $staff_id)
     * @param array $widgets An array of widget state information keyed by widget name including:
     *
     *  - open Whether or not the box is open (true/false)
     */
    public function saveClientsWidgetsState($staff_id, $company_id, $widgets)
    {
        $this->setSetting($staff_id, 'clientsWidgets_' . $company_id . '_state', base64_encode(serialize($widgets)));
    }

    /**
     * Fetch the current state of the clients widget boxes
     *
     * @param int $staff_id The ID of the staff member to fetch the widget box state for
     * @param int $company_id The ID of the company to fetch the widget box state for (in combination with $staff_id)
     * @return array An array of widget box state data
     */
    public function getClientsWidgetsState($staff_id, $company_id)
    {
        $state = [];
        $setting = $this->getSetting($staff_id, 'clientsWidgets_' . $company_id . '_state', $company_id);
        if ($setting) {
            $state = unserialize(base64_decode($setting->value));
        }
        return $state;
    }

    /**
     * Saves the current state of the home (dashboard) widget boxes
     *
     * @param int $staff_id The ID of the staff member to save the widget box state for
     * @param int $company_id The ID of the company to save the widget box state for (in combination with $staff_id)
     * @param array $widgets An array of widget state information keyed by widget name including:
     *
     *  - open Whether or not the box is open (true/false)
     */
    public function saveHomeWidgetsState($staff_id, $company_id, $widgets)
    {
        $this->setSetting($staff_id, 'dashboardWidgets_' . $company_id . '_state', base64_encode(serialize($widgets)));
    }

    /**
     * Fetch the current state of the home (dashboard) widget boxes
     *
     * @param int $staff_id The ID of the staff member to fetch the widget box state for
     * @param int $company_id The ID of the company to fetch the widget box state for (in combination with $staff_id)
     * @return array An array of widget box state data
     */
    public function getHomeWidgetsState($staff_id, $company_id)
    {
        $state = [];
        $setting = $this->getSetting($staff_id, 'dashboardWidgets_' . $company_id . '_state', $company_id);
        if ($setting) {
            $state = unserialize(base64_decode($setting->value));
        }
        return $state;
    }

    /**
     * Saves the current state of the billing (dashboard) widget boxes
     *
     * @param int $staff_id The ID of the staff member to save the widget box state for
     * @param int $company_id The ID of the company to save the widget box state for (in combination with $staff_id)
     * @param array $widgets An array of widget state information keyed by widget name including:
     *
     *  - open Whether or not the box is open (true/false)
     */
    public function saveBillingWidgetsState($staff_id, $company_id, $widgets)
    {
        $this->setSetting($staff_id, 'billingWidgets_' . $company_id . '_state', base64_encode(serialize($widgets)));
    }

    /**
     * Fetch the current state of the billing (dashboard) widget boxes
     *
     * @param int $staff_id The ID of the staff member to fetch the widget box state for
     * @param int $company_id The ID of the company to fetch the widget box state for (in combination with $staff_id)
     * @return array An array of widget box state data
     */
    public function getBillingWidgetsState($staff_id, $company_id)
    {
        $state = [];
        $setting = $this->getSetting($staff_id, 'billingWidgets_' . $company_id . '_state', $company_id);
        if ($setting) {
            $state = unserialize(base64_decode($setting->value));
        }
        return $state;
    }

    /**
     * Returns the rule set for adding and editing staff
     *
     * @param array $vars Input vars
     * @param bool $edit Whether the staff is being edited (optional)
     * @return array The rules
     */
    private function getRules(array $vars, $edit = false)
    {
        $rules = [
            'user_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'users'],
                    'message' => $this->_('Staff.!error.user_id.exists')
                ],
                'unique' => [
                    'rule' => [[$this, 'validateExists'], 'user_id', 'staff'],
                    'negate' => true,
                    'message' => $this->_('Staff.!error.user_id.unique', (isset($vars['user_id']) ? $vars['user_id'] : null))
                ]
            ],
            'first_name' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('Staff.!error.first_name.empty'),
                    'post_format' => ['trim']
                ]
            ],
            'last_name' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('Staff.!error.last_name.empty'),
                    'post_format' => ['trim']
                ]
            ],
            'email' => [
                'format' => [
                    'rule' => 'isEmail',
                    'message' => $this->_('Staff.!error.email.format'),
                    'post_format' => ['trim']
                ]
            ],
            'status' => [
                'format' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateStatus']],
                    'message' => $this->_('Staff.!error.status.format')
                ]
            ],
            'groups[]' => [
                'format' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'staff_groups'],
                    'message' => $this->_('Staff.!error.groups[].format')
                ]
            ],
            'groups' => [
                'unique_company' => [
                    'rule' => [[$this, 'validateUniqueCompanies'], (isset($vars['groups']) ? $vars['groups'] : null)],
                    'message' => $this->_('Staff.!error.groups.unique_company')
                ]
            ]
        ];

        if ($edit) {
            // Remove user_id constraint
            unset($rules['user_id']);

            // Remove staff groups constraint if no groups were given
            if (!isset($vars['groups'])) {
                unset($rules['groups[]']);
            }

            return $this->setRulesIfSet($rules);
        }

        return $rules;
    }

    /**
     * Fetches the rules for adding/editing a staff notice
     *
     * @param array $vars A list of input vars
     * @return array The staff notice rules
     */
    private function getNoticeRules(array $vars)
    {
        $rules = [
            'staff_group_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'staff_groups'],
                    'message' => $this->_('Staff.!error.staff_group_id.exists')
                ]
            ],
            'staff_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'staff'],
                    'message' => $this->_('Staff.!error.staff_id.exists')
                ]
            ],
            'action' => [
                'exists' => [
                    'rule' => [[$this, 'validateNoticeActionExists'], (isset($vars['staff_group_id']) ? $vars['staff_group_id'] : null)],
                    'message' => $this->_('Staff.!error.action.exists', (isset($vars['action']) ? $vars['action'] : null))
                ]
            ]
        ];

        return $rules;
    }

    /**
     * Validates the staff's 'status' field
     *
     * @param string $status The status to check
     * @return bool True if validated, false otherwise
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
     * Validates that none of the staff group IDs given belong to the same company
     *
     * @param array $group_ids An array of staff group IDs
     * @return bool True if none of the staff group IDs belong to the same company, false otherwise
     */
    public function validateUniqueCompanies($group_ids)
    {
        if (!empty($group_ids)) {
            $company_ids = [];

            // Look for more than one staff group that has the same company ID as another
            foreach ($group_ids as $group_id) {
                $staff_group = $this->Record->select('company_id')->from('staff_groups')->
                    where('id', '=', (int) $group_id)->fetch();

                if ($staff_group) {
                    // Found a duplicate company
                    if (isset($company_ids[$staff_group->company_id])) {
                        return false;
                    }

                    // Assign the company ID to our list
                    $company_ids[$staff_group->company_id] = true;
                }
            }
        }

        return true;
    }

    /**
     * Validates that the given action is available for this staff group
     *
     * @param string $action The email group action
     * @param int $staff_group_id The ID of the staff group to check
     * @return bool True if the staff group has the action available, false otherwise
     */
    public function validateNoticeActionExists($action, $staff_group_id)
    {
        $count = $this->Record->select()->from('staff_group_notices')->
            where('staff_group_id', '=', $staff_group_id)->
            where('action', '=', $action)->
            numResults();

        return ($count > 0);
    }
}
