<?php
/**
 * Support Manager Admin Staff controller
 *
 * @package blesta
 * @subpackage blesta.plugins.support_manager
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminStaff extends SupportManagerController
{
    /**
     * Setup
     */
    public function preAction()
    {
        parent::preAction();

        // Restore structure view location of the admin portal
        $this->structure->setDefaultView(APPDIR);
        $this->structure->setView(null, $this->orig_structure_view);
        $this->requireLogin();

        $this->uses(['SupportManager.SupportManagerStaff']);

        $this->staff_id = $this->Session->read('blesta_staff_id');

        // Load ticket language that is used in some locations
        Language::loadLang('admin_tickets', null, PLUGINDIR . 'support_manager' . DS . 'language' . DS);
    }

    /**
     * Staff listing
     */
    public function index()
    {
        $page = (isset($this->get[0]) ? (int)$this->get[0] : 1);
        $sort = (isset($this->get['sort']) ? $this->get['sort'] : 'first_name');
        $order = (isset($this->get['order']) ? $this->get['order'] : 'asc');

        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));
        $this->set(
            'staff',
            $this->SupportManagerStaff->getList($this->company_id, null, true, $page, [$sort => $order])
        );

        // Overwrite default pagination settings
        $settings = array_merge(
            Configure::get('Blesta.pagination'),
            [
                'total_results' => $this->SupportManagerStaff->getListCount($this->company_id),
                'uri' => $this->base_uri . 'plugin/support_manager/admin_staff/index/[p]/',
                'params' => ['sort'=>$sort, 'order'=>$order]
            ]
        );
        $this->setPagination($this->get, $settings);

        // Render the request if ajax
        return $this->renderAjaxWidgetIfAsync(isset($this->get[0]) || isset($this->get['sort']));
    }

    /**
     * Add a staff member
     */
    public function add()
    {
        $this->uses(['StaffGroups', 'SupportManager.SupportManagerDepartments']);

        // Get all available departments
        $departments = $this->Form->collapseObjectArray(
            $this->SupportManagerDepartments->getAll($this->company_id),
            'name',
            'id'
        );
        $department_priorities = $this->SupportManagerDepartments->getPriorities();

        if (!empty($this->post)) {
            $data = $this->post;
            $data['company_id'] = $this->company_id;

            // Set settings fields if not given
            foreach ($department_priorities as $key => $language) {
                if (empty($data['settings']['ticket_emails'][$key])) {
                    $data['settings']['ticket_emails'][$key] = 'false';
                }
                if (empty($data['settings']['mobile_ticket_emails'][$key])) {
                    $data['settings']['mobile_ticket_emails'][$key] = 'false';
                }
                if (empty($data['settings']['ticket_messenger_notifications'][$key])) {
                    $data['settings']['ticket_messenger_notifications'][$key] = 'false';
                }
            }

            $this->SupportManagerStaff->add($data);

            if (($errors = $this->SupportManagerStaff->errors())) {
                // Error, reset vars
                $vars = (object)$this->post;
                $this->setMessage('error', $errors, false, null, false);

                // Set the staff based on the staff group
                if (!empty($vars->staff_group_id)) {
                    $staff = $this->getStaffList($vars->staff_group_id, $this->company_id);
                }
            } else {
                // Success
                $this->flashMessage('message', Language::_('AdminStaff.!success.staff_added', true), null, false);
                $this->redirect($this->base_uri . 'plugin/support_manager/admin_staff/');
            }
        }

        // Set all selected departments in assigned and unset all selected departments from available
        if (isset($vars->departments) && is_array($vars->departments)) {
            $selected = [];

            foreach ($departments as $id => $name) {
                if (in_array($id, $vars->departments)) {
                    $selected[$id] = $name;
                    unset($departments[$id]);
                }
            }

            $vars->departments = $selected;
        }

        // Set schedule times
        $please_select = ['' => Language::_('AppController.select.please', true)];
        $times = ['' => Language::_('AdminStaff.text.no_time', true)] + $this->SupportManagerStaff->getTimes();
        $staff_groups = $please_select
            + $this->Form->collapseObjectArray($this->StaffGroups->getAll($this->company_id), 'name', 'id');

        $this->set('vars', (isset($vars) ? $vars : new stdClass()));
        $this->set('staff_groups', $staff_groups);
        $this->set('staff', (isset($staff) ? $staff : $please_select));
        $this->set('days', $this->SupportManagerStaff->getDays(true));
        $this->set('times', $times);
        $this->set('departments', $departments);
        $this->set('priorities', $department_priorities);
    }

    /**
     * Edit a staff member
     */
    public function edit()
    {
        // Ensure a valid staff member was given
        if (!isset($this->get[0]) || !($staff = $this->SupportManagerStaff->get($this->get[0], $this->company_id)) ||
            $staff->company_id != $this->company_id) {
            $this->redirect($this->base_uri . 'plugin/support_manager/admin_staff');
        }

        $this->uses(['SupportManager.SupportManagerDepartments']);

        // Get all available departments
        $departments = $this->Form->collapseObjectArray(
            $this->SupportManagerDepartments->getAll($this->company_id),
            'name',
            'id'
        );
        $department_priorities = $this->SupportManagerDepartments->getPriorities();
        $days = $this->SupportManagerStaff->getDays(true);

        if (!empty($this->post)) {
            // Update the staff member
            $data = $this->post;
            $data['company_id'] = $this->company_id;

            // Set settings fields if not given
            foreach ($department_priorities as $key => $language) {
                if (empty($data['settings']['ticket_emails'][$key])) {
                    $data['settings']['ticket_emails'][$key] = 'false';
                }
                if (empty($data['settings']['mobile_ticket_emails'][$key])) {
                    $data['settings']['mobile_ticket_emails'][$key] = 'false';
                }
                if (empty($data['settings']['ticket_messenger_notifications'][$key])) {
                    $data['settings']['ticket_messenger_notifications'][$key] = 'false';
                }
            }

            $this->SupportManagerStaff->edit($staff->id, $data);

            if (($errors = $this->SupportManagerStaff->errors())) {
                // Error, reset vars
                $vars = (object)$this->post;
                $this->setMessage('error', $errors, false, null, false);
            } else {
                // Success
                $this->flashMessage('message', Language::_('AdminStaff.!success.staff_updated', true), null, false);
                $this->redirect($this->base_uri . 'plugin/support_manager/admin_staff/');
            }
        }

        // Set initial staff member
        if (!isset($vars)) {
            $vars = $staff;

            // Format assigned departments to IDs
            $vars->departments = $this->Form->collapseObjectArray($vars->departments, 'id', 'id');

            // Format/order the schedules by day
            $schedules = $vars->schedules;
            $vars->schedules = [];
            foreach ($days as $day => $lang) {
                $day_schedule = [];
                foreach ($schedules as &$schedule) {
                    // Set the day
                    if ($day == $schedule->day) {
                        $day_schedule = (array)$schedule;
                        // Format the time
                        $day_schedule['start_time'] = $this->Date->cast(
                            $day_schedule['start_time'],
                            Configure::get('SupportManager.time_format')
                        );
                        $day_schedule['end_time'] = $this->Date->cast(
                            $day_schedule['end_time'],
                            Configure::get('SupportManager.time_format')
                        );
                        break;
                    }
                }
                $vars->schedules[] = $day_schedule;
            }
        }

        // Set all selected departments in assigned and unset all selected departments from available
        if (isset($vars->departments) && is_array($vars->departments)) {
            $selected = [];

            foreach ($departments as $id => $name) {
                if (in_array($id, $vars->departments)) {
                    $selected[$id] = $name;
                    unset($departments[$id]);
                }
            }

            $vars->departments = $selected;
        }

        // Set schedule times
        $times = ['' => Language::_('AdminStaff.text.no_time', true)] + $this->SupportManagerStaff->getTimes();

        $this->set('vars', $vars);
        $this->set('days', $days);
        $this->set('times', $times);
        $this->set('departments', $departments);
        $this->set('staff', $staff);
        $this->set('priorities', $department_priorities);
    }

    /**
     * Deletes the given staff member
     */
    public function delete()
    {
        // Ensure a valid staff member was given
        if (!isset($this->get[0]) || !($staff = $this->SupportManagerStaff->get($this->get[0], $this->company_id))) {
            $this->redirect($this->base_uri . 'plugin/support_manager/admin_staff/');
        }

        // Delete the staff
        $this->SupportManagerStaff->delete($staff->id, $this->company_id);
        $this->flashMessage('message', Language::_('AdminStaff.!success.staff_deleted', true), null, false);
        $this->redirect($this->base_uri . 'plugin/support_manager/admin_staff/');
    }

    /**
     * AJAX Fetch a staff member's schedule and departments
     */
    public function getSchedule()
    {
        // Ensure the staff was given
        if (!$this->isAjax() || !isset($this->get[0])
            || !($staff = $this->SupportManagerStaff->get($this->get[0], $this->company_id))) {
            header($this->server_protocol . ' 401 Unauthorized');
            exit();
        }

        // Swap the order of the days based on the calendar setting
        if (!empty($staff->schedules)) {
            $this->components(['SettingsCollection']);

            // Change the order of the schedule
            $setting = $this->SettingsCollection->fetchSetting(null, $this->company_id, 'calendar_begins');
            if (isset($setting['value']) && $setting['value'] == 'monday') {
                // Find Sunday, if set
                foreach ($staff->schedules as $index => $schedule) {
                    if ($schedule->day == 'sun') {
                        $sunday = $schedule;
                        unset($staff->schedules[$index]);
                        break;
                    }
                }

                // Move Sunday to the end of the list
                if (isset($sunday)) {
                    $staff->schedules[] = $sunday;
                    $staff->schedules = array_values($staff->schedules);
                }
            }
        }

        $vars = [
            'staff' => $staff,
            'days' => $this->SupportManagerStaff->getDays(true),
            'string' => $this->DataStructure->create('string')
        ];

        // Send the template
        echo $this->partial('admin_staff_schedule_list', $vars);

        // Render without layout
        return false;
    }

    /**
     * AJAX Fetch staff members of a given staff group
     */
    public function getStaff()
    {
        $this->uses(['Staff', 'StaffGroups']);

        // Ensure a valid staff group was given
        if (!$this->isAjax() || !isset($this->get[0]) || !($staff_group = $this->StaffGroups->get($this->get[0])) ||
            $staff_group->company_id != $this->company_id) {
            header($this->server_protocol . ' 401 Unauthorized');
            exit();
        }

        $staff_members = $this->getStaffList($staff_group->id, $staff_group->company_id);
        $this->outputAsJson($staff_members);
        return false;
    }

    /**
     * Retrieves a list of staff for a given staff group
     *
     * @param int $staff_group_id The ID of the staff group whose staff to fetch
     * @param int $company_id The ID of the staff group
     * @return array A list of staff
     */
    private function getStaffList($staff_group_id, $company_id)
    {
        $staff = $this->Staff->getAll($company_id, 'active', $staff_group_id);
        $existing_staff = $this->SupportManagerStaff->getAll($company_id);
        $staff_members = [['value' => '', 'name' => Language::_('AppController.select.please', true)]];

        foreach ($staff as $member) {
            // Ignore staff that already exist from being displayed again
            foreach ($existing_staff as $existing_member) {
                if ($existing_member->id == $member->id) {
                    continue 2;
                }
            }

            $staff_members[] = [
                'value' => $member->id,
                'name' => Language::_('AdminStaff.staff.name', true, $member->first_name, $member->last_name)
            ];
        }

        return $staff_members;
    }
}
