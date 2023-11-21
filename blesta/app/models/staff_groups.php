<?php

/**
 * Staff group management
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class StaffGroups extends AppModel
{
    /**
     * Initialize Staff Groups
     */
    public function __construct()
    {
        parent::__construct();
        Language::loadLang(['staff_groups']);
    }

    /**
     * Adds a new staff group
     *
     * @param array $vars An array of staff group information including:
     *
     *  - company_id The company ID
     *  - name The name of this staff group
     *  - session_lock Set whether or not the IP set for the session must match the user's IP
     *  - notices An array of email group actions representing BCC notices available to this group
     *  - permission_group A numerically indexed array of permission group IDs to allow access to
     *  - permission A numerically indexed array of permission field IDs to allow access to
     * @return int The staff group ID created, void on error
     * @see Permissions::getAll()
     */
    public function add(array $vars)
    {
        Loader::loadComponents($this, ['Acl']);

        $this->Input->setRules($this->getRules());

        if ($this->Input->validates($vars)) {
            $fields = ['company_id', 'name', 'session_lock'];
            $this->Record->insert('staff_groups', $vars, $fields);

            $staff_group_id = $this->Record->lastInsertId();
            $aro = 'staff_group_' . $staff_group_id;

            // Add the staff group to the ACL's ARO
            if (!$this->Acl->getAroByAlias($aro)) {
                $this->Acl->addAro($aro);
            }

            // Set permissions in the ACL
            $this->setPermissions($aro, $vars, $vars['company_id']);

            // Add group notices
            foreach ((isset($vars['notices']) ? $vars['notices'] : []) as $notice) {
                $this->addNotice(['staff_group_id' => $staff_group_id, 'action' => $notice]);
            }

            return $staff_group_id;
        }
    }

    /**
     * Updates a staff group
     *
     * @param int $staff_group_id The staff group ID
     * @param array $vars An array of staff group information including:
     *
     *  - company_id The company ID
     *  - name The name of this staff group
     *  - session_lock Set whether or not the IP set for the session must match the user's IP
     *  - notices An array of email group actions representing BCC notices available to this group
     *  - permission_group A numerically indexed array of permission group IDs to allow access to
     *  - permission A numerically indexed array of permission field IDs to allow access to
     * @return int The staff group ID created, void on error
     * @see Permissions::getAll()
     */
    public function edit($staff_group_id, array $vars)
    {
        Loader::loadModels($this, ['Staff']);
        Loader::loadComponents($this, ['Acl']);

        $rules = $this->getRules();
        $rules['staff_group_id'] = [
            'exists' => [
                'rule' => [[$this, 'validateExists'], 'id', 'staff_groups'],
                'message' => 'StaffGroups.!error.staff_group_id.exists'
            ]
        ];

        $this->Input->setRules($rules);

        $vars['staff_group_id'] = $staff_group_id;

        if ($this->Input->validates($vars)) {
            $fields = ['company_id', 'name', 'session_lock'];
            $this->Record->where('id', '=', $staff_group_id)->update('staff_groups', $vars, $fields);

            $aro = 'staff_group_' . $staff_group_id;

            // Add the staff group to the ACL's ARO
            if (!$this->Acl->getAroByAlias($aro)) {
                $this->Acl->addAro($aro);
            }

            // Set permissions in the ACL
            $this->setPermissions($aro, $vars, $vars['company_id']);

            // Get current group notices
            $current_notices = $this->getNotices($staff_group_id);

            // Add new group notices
            $new_notices = [];
            foreach ((isset($vars['notices']) ? $vars['notices'] : []) as $notice) {
                $this->addNotice(['staff_group_id' => $staff_group_id, 'action' => $notice]);
                $new_notices[] = $notice;
            }

            // Delete any notices that were not given, but still exist
            foreach ($current_notices as $current_notice) {
                if (!in_array($current_notice->action, $new_notices)) {
                    $this->deleteNotice($staff_group_id, $current_notice->action);
                }
            }

            // Clear nav caches for this group
            $staff_members = $this->Staff->getAll($vars['company_id'], null, $staff_group_id);
            foreach ($staff_members as $staff_member) {
                Cache::clearCache(
                    'nav_staff_group_' . $staff_group_id,
                    $vars['company_id'] . DS . 'nav' . DS . $staff_member->id . DS
                );
            }
        }
    }

    /**
     * Deletes a staff group
     *
     * @param int $staff_group_id The staff group ID
     */
    public function delete($staff_group_id)
    {
        Loader::loadComponents($this, ['Acl']);

        $staff_group = $this->get($staff_group_id);
        $num_staff = 0;
        $group_name = '';

        // Set staff group vars
        if ($staff_group) {
            $num_staff = $staff_group->num_staff;
            $group_name = $staff_group->name;
        }

        $vars = ['num_staff' => $num_staff];

        // Set rule that the staff group may not be deleted if it contains staff members
        $rules = [
            'num_staff' => [
                'zero' => [
                    'rule' => ['compares', '==', 0],
                    'message' => $this->_('StaffGroups.!error.num_staff.zero', $group_name)
                ]
            ]
        ];

        $this->Input->setRules($rules);

        if ($this->Input->validates($vars)) {
            // Begin transaction
            $this->Record->begin();

            $this->Record->from('staff_groups')->
                leftJoin('staff_group', 'staff_groups.id', '=', 'staff_group.staff_group_id', false)->
                where('staff_groups.id', '=', $staff_group_id)->
                delete(['staff_groups.*', 'staff_group.*']);

            // Add the staff group to the ACL's ARO and ACL
            $this->Acl->removeAro('staff_group_' . $staff_group_id);
            $this->Acl->removeAcl('staff_group_' . $staff_group_id);

            // Commit transaction
            $this->Record->commit();
        }
    }

    /**
     * Grant access to every permission allowed by the given plugin to the given staff group
     *
     * @param int $staff_group_id The staff group ID to grant access for
     * @param int $plugin_id The ID of the plugin to grant access to
     */
    public function grantPermission($staff_group_id, $plugin_id)
    {
        Loader::loadComponents($this, ['Acl']);
        Loader::loadModels($this, ['Permissions', 'Staff']);

        $staff_group = $this->get($staff_group_id);

        $aro = 'staff_group_' . $staff_group->id;

        $permission_groups = $this->Permissions->getAll('staff', $staff_group->company_id);
        foreach ($permission_groups as $group) {
            if ($group->plugin_id == $plugin_id) {
                $this->Acl->allow($aro, $group->alias, '*');
            }

            foreach ($group->permissions as $permission) {
                if ($permission->plugin_id == $plugin_id) {
                    $this->Acl->allow($aro, $permission->alias, $permission->action);
                }
            }
        }

        // Clear nav caches for this group
        $staff_members = $this->Staff->getAll($staff_group->company_id, null, $staff_group_id);
        foreach ($staff_members as $staff_member) {
            Cache::clearCache(
                'nav_staff_group_' . $staff_group_id,
                $staff_group->company_id . DS . 'nav' . DS . $staff_member->id . DS
            );
        }
    }

    /**
     * Clone an existing staff group to the given company
     *
     * @param int $staff_group_id The ID of the staff group to clone
     * @param int $company_id The ID of the company to assign the cloned staff group to
     * @return int The ID of the new staff group
     */
    public function cloneGroup($staff_group_id, $company_id)
    {
        Loader::loadModels($this, ['Permissions']);
        $staff_group = $this->get($staff_group_id);

        if ($staff_group) {
            $vars = [
                'company_id' => $company_id,
                'name' => $staff_group->name,
                'notices' => [],
                'permission_group' => [],
                'permission' => [],
                'session_lock' => $staff_group->session_lock
            ];

            // Set notices
            foreach ($staff_group->notices as $notice) {
                $vars['notices'][] = $notice->action;
            }

            // Set permissions
            $permissions = $this->Permissions->fromAcl(
                'staff_group_' . $staff_group->id,
                'staff',
                $staff_group->company_id
            );

            if ($permissions) {
                $vars['permission_group'] = $permissions->permission_group;
                $vars['permission'] = $permissions->permission;
            }

            // Add the group
            return $this->add($vars);
        }
    }

    /**
     * Adds a staff group notice
     *
     * @param array $vars An array of staff group information including:
     *
     *  - staff_group_id The ID of the staff group this notice will be added to
     *  - action The email group action
     */
    public function addNotice(array $vars)
    {
        $this->Input->setRules($this->getNoticeRules($vars));

        if ($this->Input->validates($vars)) {
            // Add a new notice, but allow duplicates to be added without error
            $this->Record->duplicate('action', '=', $vars['action'])->
                insert('staff_group_notices', $vars, ['staff_group_id', 'action']);
        }
    }

    /**
     * Deletes the given staff group notice
     *
     * @param int $staff_group_id The ID of the staff group the notice belongs to
     * @param string $action The email group action to remove (optional, default null to delete all notices)
     */
    public function deleteNotice($staff_group_id, $action = null)
    {
        $this->deleteGroupNotices($staff_group_id, $action);
    }

    /**
     * Deletes the staff group notices
     *
     * @param int $staff_group_id The ID of the staff group
     * @param string $action The email group action (optional)
     */
    private function deleteGroupNotices($staff_group_id, $action = null)
    {
        // Delete the notice from all staff members
        $this->Record->from('staff_notices')->
            where('staff_group_id', '=', $staff_group_id);

        if ($action) {
            $this->Record->where('action', '=', $action);
        }

        $this->Record->delete();

        // Delete the staff group notice
        $this->Record->from('staff_group_notices')->
            where('staff_group_id', '=', $staff_group_id);

        if ($action) {
            $this->Record->where('action', '=', $action);
        }

        $this->Record->delete();
    }

    /**
     * Fetches a staff group
     *
     * @param int $staff_group_id The staff group ID to fetch
     * @return mixed An array of stdClass objects representing the staff group, false if it does not exist
     */
    public function get($staff_group_id)
    {
        $fields = [
            'staff_groups.id', 'staff_groups.company_id', 'staff_groups.name',
            'staff_groups.session_lock', 'COUNT(staff.id)' => 'num_staff'
        ];

        $staff_group = $this->Record->select($fields)->from('staff_groups')->
            leftJoin('staff_group', 'staff_group.staff_group_id', '=', 'staff_groups.id', false)->
            leftJoin('staff', 'staff.id', '=', 'staff_group.staff_id', false)->
            where('staff_groups.id', '=', $staff_group_id)->group('staff_groups.id')->fetch();

        // Set staff group notices
        if ($staff_group) {
            $staff_group->notices = $this->getNotices($staff_group_id);
        }

        return $staff_group;
    }

    /**
     * Fetches all staff groups belonging to a given company, or all companies if not given
     *
     * @param int $company_id The ID of the company whose staff groups to fetch (optional, default null)
     * @return mixed An array of stdClass objects representing the staff groups of a company, or false if none exist
     */
    public function getAll($company_id = null)
    {
        $staff_groups = $this->getGroups($company_id)->fetchAll();

        // Set staff group notices
        foreach ($staff_groups as &$staff_group) {
            $staff_group->notices = $this->getNotices($staff_group->id);
        }

        return $staff_groups;
    }

    /**
     * Fetch all groups and companies this staff user is associated with
     *
     * @param int $user_id The ID of the user to fetch staff group info for
     * @return array An array of stdClass objects representing a particular staff group/staff member association
     */
    public function getUsersGroups($user_id)
    {
        $fields = [
            'staff.id' => 'staff_id', 'staff_groups.id' => 'group_id',
            'staff_groups.company_id', 'staff_groups.session_lock'
        ];
        return $this->Record->select($fields)->from('staff')->
            innerJoin('staff_group', 'staff_group.staff_id', '=', 'staff.id', false)->
            innerJoin('staff_groups', 'staff_groups.id', '=', 'staff_group.staff_group_id', false)->
            where('staff.user_id', '=', $user_id)->fetchAll();
    }

    /**
     * Fetches the staff group for the given staff member and company
     *
     * @param int $staff_id The ID of the staff member to fetch the group for
     * @param int $company_id The ID of the company to fetch the staff group for
     * @return stdClass A stdClass representation of the staff group
     */
    public function getStaffGroupByStaff($staff_id, $company_id)
    {
        $staff_group = $this->Record->select(['staff_groups.*'])->from('staff_group')->
            innerJoin('staff_groups', 'staff_groups.id', '=', 'staff_group.staff_group_id', false)->
            where('staff_group.staff_id', '=', $staff_id)->where('staff_groups.company_id', '=', $company_id)->
            fetch();

        // Set staff group notices
        if ($staff_group) {
            $staff_group->notices = $this->getNotices($staff_group->id);
        }

        return $staff_group;
    }

    /**
     * Retrieves a list of plugins installed under a particular company
     *
     * @param int $company_id The company ID
     * @param int $page The page of results to fetch (optional, default 1)
     * @param string $order_by The sort and order conditions (e.g. array('sort_field'=>"ASC"), optional)
     * @return mixed An array of stdClass objects representing staff groups, or false if none exist
     */
    public function getList($company_id, $page = 1, $order_by = ['name' => 'ASC'])
    {
        $this->Record = $this->getGroups($company_id);

        // Return the results
        $staff_groups = $this->Record->order($order_by)->
            limit($this->getPerPage(), (max(1, $page) - 1) * $this->getPerPage())->fetchAll();

        // Set staff group notices
        foreach ($staff_groups as &$staff_group) {
            $staff_group->notices = $this->getNotices($staff_group->id);
        }

        return $staff_groups;
    }

    /**
     * Returns the total number of staff groups returned from StaffGroups::getList(),
     * useful in constructing pagination for the getList() method.
     *
     * @param int $company_id The company ID
     * @return int The total number of staff groups
     * @see StaffGroups::getList()
     */
    public function getListCount($company_id)
    {
        $this->Record = $this->getGroups($company_id);

        // Return the number of results
        return $this->Record->numResults();
    }

    /**
     * Fetches all staff group notices
     *
     * @param int $staff_group_id The ID of the staff group
     * @return array A list of all staff group notices
     */
    public function getNotices($staff_group_id)
    {
        $fields = ['staff_group_notices.*', 'email_groups.notice_type'];
        return $this->Record->select($fields)->from('staff_group_notices')->
            innerJoin('email_groups', 'email_groups.action', '=', 'staff_group_notices.action', false)->
            where('staff_group_id', '=', $staff_group_id)->fetchAll();
    }

    /**
     * Partially constructs the query required by StaffGroups::getList(),
     * StaffGroups::getListCount(), and StaffGroups::getAll()
     *
     * @param int $company_id The company ID (optional, default null)
     * @return Record The partially constructed query Record object
     */
    private function getGroups($company_id = null)
    {
        $fields = [
            'staff_groups.id', 'staff_groups.company_id', 'staff_groups.name',
            'COUNT(staff.id)' => 'num_staff', 'companies.name' => 'company_name'
        ];
        $this->Record->select($fields)->from('staff_groups')->
            innerJoin('companies', 'companies.id', '=', 'staff_groups.company_id', false)->
            leftJoin('staff_group', 'staff_group.staff_group_id', '=', 'staff_groups.id', false)->
            leftJoin('staff', 'staff.id', '=', 'staff_group.staff_id', false);

        if ($company_id != null) {
            $this->Record->where('staff_groups.company_id', '=', $company_id);
        }

        $this->Record->group('staff_groups.id');

        return $this->Record;
    }

    /**
     * Removes any existing entries in the ACL for this ACO and adds "allow" entires
     * for all of the given permission groups and permission fields.
     *
     * @param string $aro The ARO to set "allow" access permissions in the ACL
     * @param array $vars An array containing the following:
     *
     *  - permission_group A numerically indexed array of permission group IDs to allow access to
     *  - permission A numerically indexed array of permission field IDs to allow access to
     * @param int $company_id The ID of the company to set permissions under
     * @see StaffGroups::add()
     * @see StaffGroups::edit()
     */
    private function setPermissions($aro, array $vars, $company_id)
    {
        // First remove any entries for this ARO in the ACL
        $this->Acl->removeAcl($aro);

        // Fetch all permissions for staff for this company
        if (!isset($this->Permissions)) {
            Loader::loadModels($this, ['Permissions']);
        }
        $permissions = $this->Permissions->getAll('staff', $company_id);

        if ($permissions) {
            foreach ($permissions as $group) {
                // Allow the permission if it was set, deny otherwise
                if (isset($vars['permission_group']) && in_array($group->id, (array) $vars['permission_group'])) {
                    $this->Acl->allow($aro, $group->alias, '*');
                } else {
                    $this->Acl->deny($aro, $group->alias, '*');
                }

                if ($group->permissions) {
                    foreach ($group->permissions as $permission) {
                        // Allow the permission if it was set, deny otherwise
                        if (isset($vars['permission']) && in_array($permission->id, (array) $vars['permission'])) {
                            $this->Acl->allow($aro, $permission->alias, $permission->action);
                        } else {
                            $this->Acl->deny($aro, $permission->alias, $permission->action);
                        }
                    }
                }
            }
        }
    }

    /**
     * Fetches the rules for adding/editing a staff group
     *
     * @return array The staff group rules
     */
    private function getRules()
    {
        $rules = [
            'company_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'companies'],
                    'message' => $this->_('StaffGroups.!error.company_id.exists')
                ]
            ],
            'name' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('StaffGroups.!error.name.empty')
                ],
                'length' => [
                    'rule' => ['maxLength', 64],
                    'message' => $this->_('StaffGroups.!error.name.length')
                ]
            ],
            'session_lock' => [
                'valid' => [
                    'rule' => ['in_array', ['1', '0']],
                    'message' => $this->_('StaffGroups.!error.session_lock.valid')
                ]
            ],
            'notices[]' => [
                'exists' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateExists'], 'action', 'email_groups'],
                    'message' => $this->_('StaffGroups.!error.action.exists')
                ]
            ]
        ];
        return $rules;
    }

    /**
     * Fetches the rules for adding/editing a staff group notice
     *
     * @param array $vars A list of input vars
     * @return array The staff group notice rules
     */
    private function getNoticeRules(array $vars)
    {
        $rules = [
            'staff_group_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'staff_groups'],
                    'message' => $this->_('StaffGroups.!error.staff_group_id.exists')
                ]
            ],
            'action' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'action', 'email_groups'],
                    'message' => $this->_('StaffGroups.!error.action.exists', (isset($vars['action']) ? $vars['action'] : null))
                ]
            ]
        ];

        return $rules;
    }
}
