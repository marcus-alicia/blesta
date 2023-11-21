<?php
/**
 * Generic Clientexec Support Departments Migrator.
 *
 * @package blesta
 * @subpackage blesta.plugins.import_manager.components.migrators.clientexec
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ClientexecSupportDepartments
{
    /**
     * ClientexecSupportDepartments constructor.
     *
     * @param Record $remote
     */
    public function __construct(Record $remote)
    {
        $this->remote = $remote;
    }

    /**
     * Get all support departments.
     *
     * @return mixed The result of the sql transaction
     */
    public function get()
    {
        return $this->remote->select()->from('departments')->getStatement()->fetchAll();
    }

    /**
     * Get all support departments.
     *
     * @param mixed $department_id
     * @return mixed The result of the sql transaction
     */
    public function getDepartmentStaff($department_id)
    {
        return $this->remote->select()->from('departments_members')->where('department_id', '=', $department_id)->where('is_group', '=', '0')->getStatement()->fetchAll();
    }
}
