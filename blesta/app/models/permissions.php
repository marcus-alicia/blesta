<?php

/**
 * Permissions management
 *
 * Controls which options are available for setting the Access Control List's
 * Control Objects as well as offers an interface for interacting with the ACL
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Permissions extends AppModel
{
    /**
     * Loads the ACL for processing
     */
    public function __construct()
    {
        parent::__construct();
        Language::loadLang(['permissions']);
        Loader::loadComponents($this, ['Acl']);
    }

    /**
     * Fetches the given permission record from the database by ID
     *
     * @param int $id The permission ID to fetch
     * @return mixed A stdClass object representing a permission field, false if the permission field does not exist
     */
    public function get($id)
    {
        $fields = ['permissions.id', 'permissions.group_id', 'permissions.name',
            'permissions.alias', 'permissions.action', 'permissions.plugin_id', 'plugins.name' => 'plugin_name',
            'plugins.company_id' => 'company_id'
        ];
        $permission = $this->Record->select($fields)->from('permissions')->
            leftJoin('plugins', 'plugins.id', '=', 'permissions.plugin_id', false)->
            where('permissions.id', '=', $id)->fetch();

        if ($permission) {
            $permission->name_trans = ($name = Language::_($permission->name, true)) != null
                ? $name
                : $permission->name;
        }
        return $permission;
    }

    /**
     * Fetches the given permission record from the database by alias
     *
     * @param string $alias The permission alias to fetch
     * @param int $plugin_id The ID of the plugin (null if not part of a plugin) the alias belongs to
     * @param string $action The permission action to fetch (null fetches 1st match on alias)
     * @return mixed A stdClass object representing a permission field, false if the permission field does not exist
     */
    public function getByAlias($alias, $plugin_id = null, $action = null)
    {
        $fields = ['permissions.id', 'permissions.group_id', 'permissions.name',
            'permissions.alias', 'permissions.action', 'permissions.plugin_id', 'plugins.name' => 'plugin_name',
            'plugins.company_id' => 'company_id'
        ];
        $this->Record->select($fields)->from('permissions')->
            leftJoin('plugins', 'plugins.id', '=', 'permissions.plugin_id', false)->
            where('permissions.alias', '=', $alias)->
            where('permissions.plugin_id', '=', $plugin_id);
        if ($action) {
            $this->Record->where('permissions.action', '=', $action);
        }
        $permission = $this->Record->fetch();

        if ($permission) {
            $permission->name_trans = ($name = Language::_($permission->name, true)) != null
                ? $name
                : $permission->name;
        }
        return $permission;
    }

    /**
     * Fetches the given permission group record from the database by ID
     *
     * @param int $group_id The permission group ID to fetch
     * @return mixed A stdClass object representing a permission group, false if the permission group does not exist
     */
    public function getGroup($group_id)
    {
        $fields = ['permission_groups.id', 'permission_groups.name', 'permission_groups.level',
            'permission_groups.alias', 'permission_groups.plugin_id', 'plugins.name' => 'plugin_name',
            'plugins.company_id' => 'company_id'
        ];
        $group = $this->Record->select($fields)->from('permission_groups')->
                leftJoin('plugins', 'plugins.id', '=', 'permission_groups.plugin_id', false)->
                where('permission_groups.id', '=', $group_id)->fetch();

        if ($group) {
            $group->name_trans = ($name = Language::_($group->name, true)) != null ? $name : $group->name;
        }
        return $group;
    }

    /**
     * Fetches the given permission group record from the database by alias
     *
     * @param string $group_alias The permission group alias to fetch
     * @param int $plugin_id The ID of the plugin (null if not part of a plugin) the alias belongs to
     * @return mixed A stdClass object representing a permission group, false if the permission group does not exist
     */
    public function getGroupByAlias($group_alias, $plugin_id = null)
    {
        $fields = ['permission_groups.id', 'permission_groups.name', 'permission_groups.level',
            'permission_groups.alias', 'permission_groups.plugin_id', 'plugins.name' => 'plugin_name',
            'plugins.company_id' => 'company_id'
        ];
        $group = $this->Record->select($fields)->from('permission_groups')->
            leftJoin('plugins', 'plugins.id', '=', 'permission_groups.plugin_id', false)->
            where('permission_groups.alias', '=', $group_alias)->
            where('permission_groups.plugin_id', '=', $plugin_id)->fetch();

        if ($group) {
            $group->name_trans = ($name = Language::_($group->name, true)) != null ? $name : $group->name;
        }
        return $group;
    }

    /**
     * Fetches all permission fields divided under each permission category.
     *
     * @param string $level The level to fetch permissions for ('staff', or 'client')
     * @param int $company_id The company to fetch permissions for
     * @return array An array of stdClass objects each representing a
     *  permission group. Each permission group also contains an array of
     *  permission fields representing as stdClass objects
     */
    public function getAll($level, $company_id)
    {
        $fields = ['permission_groups.id', 'permission_groups.name', 'permission_groups.level',
            'permission_groups.alias', 'permission_groups.plugin_id', 'plugins.name' => 'plugin_name',
            'plugins.company_id' => 'company_id'
        ];

        $permissions = $this->Record->select($fields)->from('permission_groups')->
            on('plugins.company_id', '=', $company_id)->
            leftJoin('plugins', 'plugins.id', '=', 'permission_groups.plugin_id', false)->
            open()->
                where('permission_groups.plugin_id', '=', null)->
                orWhere('plugins.company_id', '=', $company_id)->
            close()->
            where('permission_groups.level', '=', $level)->order(['permission_groups.name' => 'asc'])->fetchAll();

        if ($permissions) {
            $fields = ['permissions.id', 'permissions.group_id', 'permissions.name',
                'permissions.alias', 'permissions.action', 'permissions.plugin_id', 'plugins.name' => 'plugin_name'];

            foreach ($permissions as &$group) {
                $group->name_trans = ($name = Language::_($group->name, true)) != null ? $name : $group->name;
                $group->permissions = $this->Record->select($fields)->from('permissions')->
                    on('plugins.company_id', '=', $company_id)->
                    leftJoin('plugins', 'plugins.id', '=', 'permissions.plugin_id', false)->
                    open()->
                        where('permissions.plugin_id', '=', null)->
                        orWhere('plugins.company_id', '=', $company_id)->
                    close()->
                    where('permissions.group_id', '=', $group->id)->
                    order(['permissions.name' => 'asc'])->
                    fetchAll();

                if ($group->permissions) {
                    foreach ($group->permissions as &$permission) {
                        $permission->name_trans = ($name = Language::_($permission->name, true)) != null
                            ? $name
                            : $permission->name;
                    }
                }
            }
        }

        return $permissions;
    }

    /**
     * Fetches a set of permission groups and permission fields that the given group has access to on the
     * provided level and company
     *
     * @param string $aro The ARO to fetch permissions for
     * @param string $level The level to fetch permissions for ('staff' or 'client')
     * @param int $company_id The ID of the company to fetch permissions for
     * @return stdClass A stdClass object containing the following:
     *
     *  - permission_group An numerically indexed array of permission group IDs that this ARO is allowed to access
     *  - permission A numerically indexed array of permission field IDs that this ARO is allowed to access
     */
    public function fromAcl($aro, $level, $company_id)
    {
        $acl = new stdClass();
        $acl->permission_group = [];
        $acl->permission = [];

        $permissions = $this->getAll($level, $company_id);

        if ($permissions) {
            foreach ($permissions as $group) {
                if ($this->Acl->check($aro, $group->alias, '*')) {
                    $acl->permission_group[] = $group->id;
                }

                if ($group->permissions) {
                    foreach ($group->permissions as $perm) {
                        if ($this->Acl->check($aro, $perm->alias, $perm->action)) {
                            $acl->permission[] = $perm->id;
                        }
                    }
                }
            }
        }

        return $acl;
    }

    /**
     * Verifies whether the given ARO is authorized to access the given ACO on the given action
     *
     * @param string $aro The ARO to verify can access the ACO
     * @param string $aco The ACO to verify can be accessed by the ARO
     * @param string $action The action permission
     * @param string $level The permission group level
     * @param int $company_id The ID of the company
     * @return bool True if the ARO is authorized to access the ACO or if the
     *  permission or permission group does not exist, false otherwise
     */
    public function authorized($aro, $aco, $action, $level, $company_id)
    {
        $action = ($action == '' ? '*' : $action);

        $group = $this->Record->select()->from('permission_groups')->
            on('plugins.company_id', '=', $company_id)->
            leftJoin('plugins', 'plugins.id', '=', 'permission_groups.plugin_id', false)->
            where('permission_groups.level', '=', $level)->
            where('permission_groups.alias', '=', $aco)->fetch();
        $permission = $this->Record->select()->from('permissions')->
            on('plugins.company_id', '=', $company_id)->
            leftJoin('plugins', 'plugins.id', '=', 'permissions.plugin_id', false)->
            where('permissions.alias', '=', $aco)->
            open()->
            where('permissions.action', '=', $action)->
            orWhere('permissions.action', '=', '*')->
            close()->
            fetch();

        if (!$group && !$permission) {
            return true;
        }

        return $this->Acl->check($aro, $aco, $action);
    }

    /**
     * Adds a new permission field to the system (also adds the permission to the ACL)
     *
     * @param array $vars An array of input fields including:
     *
     *  - group_id The ID of the permission group this permission belongs to
     *  - name The name of this permission (language definition or plain-text)
     *  - alias The ACO alias for this permission (i.e. the Class name to apply to)
     *  - action The action this ACO may control (i.e. the Method name of the alias to control access for)
     *  - plugin_id The ID of the plugin this action belongs to (if any)
     * @return int The ID of the permission record on success, void one error
     */
    public function add(array $vars)
    {
        $this->Input->setRules($this->getRules($vars));

        if ($this->Input->validates($vars)) {
            $fields = ['group_id', 'name', 'alias', 'action', 'plugin_id'];

            $this->Record->insert('permissions', $vars, $fields);
            $permission_id = $this->Record->lastInsertId();

            // Add the ACO if it doesn't already exist
            if (!$this->Acl->getAcoByAlias($vars['alias'])) {
                $this->Acl->addAco($vars['alias']);
            }

            return $permission_id;
        }
    }

    /**
     * Updates the permission field in the system (also adds the permission to the ACL if needed)
     *
     * @param int $id The ID of the permission field to update
     * @param array $vars An array of input fields including:
     *
     *  - group_id The ID of the permission group this permission belongs to
     *  - name The name of this permission (language definition or plain-text)
     *  - alias The ACO alias for this permission (i.e. the Class name to apply to)
     *  - action The action this ACO may control (i.e. the Method name of the alias to control access for)
     *  - plugin_id The ID of the plugin this action belongs to (if any)
     */
    public function edit($id, array $vars)
    {
        $this->Input->setRules($this->getRules($vars));

        if ($this->Input->validates($vars)) {
            $fields = ['group_id', 'name', 'alias', 'action', 'plugin_id'];

            $this->Record->where('id', '=', $id)->update('permissions', $vars, $fields);

            // Add the ACO if it doesn't already exist
            if (!$this->Acl->getAcoByAlias($vars['alias'])) {
                $this->Acl->addAco($vars['alias']);
            }
        }
    }

    /**
     * Deletes the permission field from the system, also removes the items from the ACL
     *
     * @param int $id The ID of the permission field to delete
     */
    public function delete($id)
    {
        $permission = $this->get($id);

        $this->Record->from('permissions')->where('id', '=', $id)->delete();

        // If company ID given, only remove ACL from select AROs
        if ($permission->plugin_id) {
            $aros = $this->Record->select('acl_aro.alias')->from('staff_groups')->
                appendValues(['staff_group_'])->
                innerJoin('acl_aro', 'acl_aro.alias', '=', 'CONCAT(?,staff_groups.id)', false)->
                where('staff_groups.company_id', '=', $permission->company_id)->fetchAll();

            foreach ($aros as $aro) {
                // Remove the entry from the ACL
                $this->Acl->removeAcl($aro->alias, $permission->alias, $permission->action);
            }
        } else {
            // Remove ACL for all AROs
            // Remove the entry from the ACL
            $this->Acl->removeAcl(null, $permission->alias, $permission->action);
        }
    }

    /**
     * Adds a new permission group to the system (also adds the permission to the ACL)
     *
     * @param array $vars An array of input fields including:
     *
     *  - name The name of this permission group (language definition or plain-text)
     *  - level The level this permission group resides on (staff or client)
     *  - alias The ACO alias for this permission group (i.e. the Class name to apply to)
     *  - plugin_id The ID of the plugin this permission group belongs to (if any)
     * @return int The ID of the permission group on success, void one error
     */
    public function addGroup(array $vars)
    {
        $this->Input->setRules($this->getGroupRules($vars));

        if ($this->Input->validates($vars)) {
            $fields = ['name', 'level', 'alias', 'plugin_id'];

            $this->Record->insert('permission_groups', $vars, $fields);
            $permission_group_id = $this->Record->lastInsertId();

            // Add the ACO if it doesn't already exist
            if (!$this->Acl->getAcoByAlias($vars['alias'])) {
                $this->Acl->addAco($vars['alias']);
            }

            return $permission_group_id;
        }
    }

    /**
     * Updates the permission group in the system (also adds the permission to the ACL if needed)
     *
     * @param int $group_id The ID of the permission group to update
     * @param array $vars An array of input fields including:
     *
     *  - name The name of this permission group (language definition or plain-text)
     *  - level The level this permission group resides on (staff or client)
     *  - alias The ACO alias for this permission group (i.e. the Class name to apply to)
     *  - plugin_id The ID of the plugin this permission group belongs to (if any)
     */
    public function editGroup($group_id, array $vars)
    {
        $this->Input->setRules($this->getGroupRules($vars));

        if ($this->Input->validates($vars)) {
            $fields = ['name', 'level', 'alias', 'plugin_id'];

            $this->Record->where('id', '=', $group_id)->update('permission_groups', $vars, $fields);

            // Add the ACO if it doesn't already exist
            if (!$this->Acl->getAcoByAlias($vars['alias'])) {
                $this->Acl->addAco($vars['alias']);
            }
        }
    }

    /**
     * Deletes the permission group from the system, also removes the items from the ACL
     *
     * @param int $group_id The ID of the permission group to delete
     */
    public function deleteGroup($group_id)
    {
        $group = $this->getGroup($group_id);

        if (!$group) {
            return;
        }

        $this->Record->from('permission_groups')->where('id', '=', $group_id)->delete();

        // If company ID given, only remove ACL from select AROs
        if ($group->plugin_id) {
            $aros = $this->Record->select('acl_aro.alias')->from('staff_groups')->
                appendValues(['staff_group_'])->
                innerJoin('acl_aro', 'acl_aro.alias', '=', 'CONCAT(?,staff_groups.id)', false)->
                where('staff_groups.company_id', '=', $group->company_id)->fetchAll();

            foreach ($aros as $aro) {
                // Remove the entry from the ACL
                $this->Acl->removeAcl($aro->alias, $group->alias, '*');
            }
        } else {
            // Remove ACL for all AROs
            // Remove the entry from the ACL
            $this->Acl->removeAcl(null, $group->alias, '*');
        }

        // Remove any permissions
        $permissions = $this->Record->select()->from('permissions')->where('group_id', '=', $group_id)->fetchAll();
        foreach ($permissions as $permission) {
            $this->delete($permission->id);
        }
    }

    /**
     * Returns add/edit rules for Permission fields
     *
     * @param array $vars An array of input vars
     * @return array An array containing the validation rules
     */
    private function getRules(array $vars)
    {
        $rules = [
            'group_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'permission_groups'],
                    'message' => $this->_('Permissions.!error.group_id.exists')
                ]
            ],
            'name' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('Permissions.!error.name.empty')
                ]
            ],
            'alias' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('Permissions.!error.alias.empty')
                ]
            ],
            'action' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('Permissions.!error.action.empty')
                ]
            ],
            'plugin_id' => [
                'exists' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateExists'], 'id', 'plugins'],
                    'message' => $this->_('Permissions.!error.plugin_id.exists')
                ]
            ]
        ];

        return $rules;
    }

    /**
     * Returns add/edit rules for Permission Groups
     *
     * @param array $vars An array of input vars
     * @return array An array containing the validation rules
     */
    private function getGroupRules(array $vars)
    {
        $rules = [
            'name' => [
                'group_empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('Permissions.!error.name.group_empty')
                ]
            ],
            'level' => [
                'group_empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('Permissions.!error.action.group_empty')
                ]
            ],
            'alias' => [
                'group_empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => $this->_('Permissions.!error.alias.group_empty')
                ]
            ],
            'plugin_id' => [
                'group_exists' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateExists'], 'id', 'plugins'],
                    'message' => $this->_('Permissions.!error.plugin_id.group_exists')
                ]
            ]
        ];

        return $rules;
    }
}
