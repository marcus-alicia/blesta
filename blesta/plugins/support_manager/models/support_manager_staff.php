<?php
/**
 * SupportManagerStaff model
 *
 * @package blesta
 * @subpackage blesta.plugins.support_manager
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class SupportManagerStaff extends SupportManagerModel
{
    /**
     * @var int The time interval (in minutes) of scheduled times
     */
    private $time_interval = 5;
    /**
     * @var array A list of serializable settings
     */
    private $serializable_settings = ['mobile_ticket_emails', 'ticket_emails', 'ticket_messenger_notifications'];

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        Language::loadLang('support_manager_staff', null, PLUGINDIR . 'support_manager' . DS . 'language' . DS);
    }

    /**
     * Adds a staff member to the support staff
     *
     * @param array $vars A list of input vars including:
     *  - staff_id The staff ID
     *  - company_id The company ID to assign this schedule to
     *  - departments An array of staff department IDs to assign this staff member to
     *  - schedules An array containing the days/times the staff is available for support, including:
     *      - day The day of the week (i.e. "sun", "mon", "tue", "wed", "thu", "fri", "sat")
     *      - all_day The value of 1 to set start and end times to 00:00:00 (optional)
     *      - start_time The time of day the staff member begins (required if all_day is not 1)
     *      - end_time The time of day the staff member ends (required if all_day is not 1)
     *  - settings A list of key=>value settings
     * @return mixed An stdClass object representing the newly-created staff member, or void on error
     */
    public function add(array $vars)
    {
        // Format day times if all day
        if (!empty($vars['schedules'])) {
            $vars['schedules'] = $this->formatSchedulesAllDay($vars['schedules']);
        }

        // Set rules
        $this->Input->setRules($this->getRules($vars));

        if ($this->Input->validates($vars)) {
            $this->updateStaff($vars['staff_id'], $vars['company_id'], $vars);
        }
    }

    /**
     * Updates a staff member of the support staff
     *
     * @param int $staff_id The ID of the staff member to update
     * @param array $vars A list of input vars including:
     *  - company_id The company ID to assign this schedule to
     *  - departments An array of staff department IDs to assign this staff member to
     *  - schedules An array containing the days/times the staff is available for support, including:
     *      - day The day of the week (i.e. "sun", "mon", "tue", "wed", "thu", "fri", "sat")
     *      - start_time The time of day the staff member begins
     *      - end_time The time of day the staff member ends
     *  - settings A list of key=>value settings
     * @return mixed An stdClass object representing the newly-created staff member, or void on error
     */
    public function edit($staff_id, array $vars)
    {
        // Format day times if all day
        if (!empty($vars['schedules'])) {
            $vars['schedules'] = $this->formatSchedulesAllDay($vars['schedules']);
        }

        // Set rules
        $vars['staff_id'] = $staff_id;
        $this->Input->setRules($this->getRules($vars, true));

        if ($this->Input->validates($vars)) {
            $this->updateStaff($staff_id, $vars['company_id'], $vars);
        }
    }

    /**
     * Sets the start_time and end_time of days to 00:00:00 the times encompass the entire day
     *
     * @param array $schedules A list of all the schedules
     * @return array An updated list of all the schedules
     */
    private function formatSchedulesAllDay($schedules)
    {
        foreach ($schedules as &$schedule) {
            // Time is set to all day, set the times
            if (isset($schedule['all_day']) && $schedule['all_day'] == '1') {
                $schedule['start_time'] = '00:00:00';
                $schedule['end_time'] = '00:00:00';
            }
        }
        return $schedules;
    }

    /**
     * Deletes the staff member from the support system
     *
     * @param int $staff_id The ID of the staff member to delete
     * @param int $company_id The company to which the staff member is assigned
     */
    public function delete($staff_id, $company_id)
    {
        // Start a transaction
        $this->Record->begin();

        // Delete the staff from the support system
        $this->Record->from('support_staff_schedules')
            ->leftJoin(
                'support_staff_departments',
                'support_staff_departments.staff_id',
                '=',
                'support_staff_schedules.staff_id',
                false
            )
                ->on('support_staff_settings.company_id', '=', 'support_staff_schedules.company_id', false)
            ->leftJoin(
                'support_staff_settings',
                'support_staff_settings.staff_id',
                '=',
                'support_staff_schedules.staff_id',
                false
            )
            ->where('support_staff_schedules.staff_id', '=', $staff_id)
            ->where('support_staff_schedules.company_id', '=', $company_id)
            ->delete(['support_staff_schedules.*', 'support_staff_departments.*', 'support_staff_settings.*']);

        // Remove this staff from tickets, but leave the support_replies alone
        $vars = ['staff_id' => null];
        $this->Record->where('staff_id', '=', $staff_id)->
            update('support_tickets', $vars, ['staff_id']);

        // Commit the transaction
        $this->Record->commit();
    }

    /**
     * Updates a staff member's departments, schedules, and settings
     *
     * @param int $staff_id The ID of the staff member to update
     * @param int $company_id The company ID
     * @param array $vars A list of input vars including:
     *  - departments An array of staff department IDs to assign this staff member to
     *  - schedules An array containing the days/times the staff is available for support, including:
     *      - day The day of the week (i.e. "sun", "mon", "tue", "wed", "thu", "fri", "sat")
     *      - start_time The time of day the staff member begins
     *      - end_time The time of day the staff member ends
     *  - settings A list of key=>value settings
     */
    private function updateStaff($staff_id, $company_id, array $vars)
    {
        // Begin a transaction
        $this->begin();

        // Add the staff to departments
        $this->addToDepartments($staff_id, $company_id, (isset($vars['departments']) ? $vars['departments'] : []));

        // Add the staff schedules
        $this->updateSchedule($staff_id, $company_id, (isset($vars['schedules']) ? $vars['schedules'] : []));

        // Add the staff settings
        $this->setSettings($staff_id, $company_id, (isset($vars['settings']) ? $vars['settings'] : []));

        // Commit the transaction
        $this->commit();
    }

    /**
     * Updates a staff member's schedule, removing existing schedule
     *
     * @param int $staff_id The ID of the staff member
     * @param int $company_id The ID of the company
     * @param array $schedules An array containing the days/times the staff is available for support, including:
     *  - day The day of the week (i.e. "sun", "mon", "tue", "wed", "thu", "fri", "sat")
     *  - start_time The time of day the staff member begins
     *  - end_time The time of day the staff member ends
     */
    private function updateSchedule($staff_id, $company_id, array $schedules)
    {
        // Remove current schedule for this staff
        $this->Record->from('support_staff_schedules')->
            where('staff_id', '=', $staff_id)->
            where('company_id', '=', $company_id)->
            delete();

        // Add the new staff schedule
        $fields = ['staff_id', 'company_id', 'day', 'start_time', 'end_time'];
        foreach ($schedules as $schedule) {
            if (!empty($schedule['start_time']) && !empty($schedule['end_time'])) {
                $vars = array_merge(['staff_id' => $staff_id, 'company_id' => $company_id], $schedule);
                $this->Record->insert('support_staff_schedules', $vars, $fields);
            }
        }
    }

    /**
     * Adds the staff member to the given departments, removing existing department assignments
     *
     * @param int $staff_id The ID of the staff member to add
     * @param int $company_id The ID of the company
     * @param array $departments A list of department IDs to add the staff member to
     */
    private function addToDepartments($staff_id, $company_id, array $departments)
    {
        // Remove current department assignments for this staff
        $this->Record->from('support_staff_departments')
                ->on('support_departments.company_id', '=', $company_id)
            ->innerJoin(
                'support_departments',
                'support_departments.id',
                '=',
                'support_staff_departments.department_id',
                false
            )
            ->where('support_staff_departments.staff_id', '=', $staff_id)
            ->delete(['support_staff_departments.*']);

        // Add the staff to the given departments
        foreach ($departments as $department_id) {
            $vars = ['staff_id' => $staff_id, 'department_id' => $department_id];
            $this->Record->insert('support_staff_departments', $vars);
        }
    }

    /**
     * Retrieves the scheduled days
     *
     * @param bool $order True to order the days by the calendar setting, false otherwise
     * @return array A list of days and their language
     */
    public function getDays($order = false)
    {
        $days = [
            'sun' => $this->_('SupportManagerStaff.days.sun'),
            'mon' => $this->_('SupportManagerStaff.days.mon'),
            'tue' => $this->_('SupportManagerStaff.days.tue'),
            'wed' => $this->_('SupportManagerStaff.days.wed'),
            'thu' => $this->_('SupportManagerStaff.days.thu'),
            'fri' => $this->_('SupportManagerStaff.days.fri'),
            'sat' => $this->_('SupportManagerStaff.days.sat')
        ];

        if ($order) {
            Loader::loadComponents($this, ['SettingsCollection']);

            // Swap Sunday to end of the week
            $setting = $this->SettingsCollection->fetchSetting(
                null,
                Configure::get('Blesta.company_id'),
                'calendar_begins'
            );
            if (isset($setting['value']) && $setting['value'] == 'monday') {
                $sunday = $days['sun'];
                unset($days['sun']);
                $days['sun'] = $sunday;
            }
        }

        return $days;
    }

    /**
     * Retrieves available times in 24-hour format
     *
     * @return array A list of times
     */
    public function getTimes()
    {
        $times = [];

        // Set the hour:minute:second time
        for ($i=0; $i<24; $i++) {
            for ($j=0; $j<60; $j+=$this->time_interval) {
                $time = str_pad($i, 2, 0, STR_PAD_LEFT) . ':' . str_pad($j, 2, 0, STR_PAD_LEFT) . ':00';
                $times[$time] = $time;
            }
        }

        return $times;
    }

    /**
     * Sets a support staff setting
     *
     * @param int staff_id The ID of the staff member
     * @param int $company_id The ID of the company
     * @param array $vars An array containing:
     *  - key The setting key
     *  - value The setting value
     */
    public function setSetting($staff_id, $company_id, array $vars)
    {
        $vars = array_merge($vars, ['staff_id' => $staff_id, 'company_id' => $company_id]);

        // Update the setting
        if (isset($vars['key'])) {
            $value = (isset($vars['value']) ? $vars['value'] : null);

            // Serialize known setting keys
            if (in_array($vars['key'], $this->serializable_settings) && is_array($vars['value'])) {
                $vars['value'] = serialize($value);
            }

            $this->Record->duplicate('value', '=', (isset($vars['value']) ? $vars['value'] : null))->
                insert('support_staff_settings', $vars);
        }
    }

    /**
     * Sets support staff settings
     *
     * @param int staff_id The ID of the staff member
     * @param int $company_id The ID of the company
     * @param array $settings A key=>value list of staff settings
     */
    public function setSettings($staff_id, $company_id, array $settings)
    {
        // Add all settings
        foreach ($settings as $key => $value) {
            $this->setSetting($staff_id, $company_id, ['key' => $key, 'value' => $value]);
        }
    }

    /**
     * Retrieves a staff member's settings
     *
     * @param int $staff_id The ID of the staff member
     * @param int $company_id The ID of the company to which this staff belongs
     * @return array A list of staff member settings
     */
    public function getSettings($staff_id, $company_id)
    {
        $this->ArrayHelper = $this->DataStructure->create('Array');

        $settings = $this->Record->select()->from('support_staff_settings')->
            where('company_id', '=', $company_id)->where('staff_id', '=', $staff_id)->
            fetchAll();

        // Unserialize known serialized settings
        foreach ($settings as &$setting) {
            if (in_array($setting->key, $this->serializable_settings)) {
                $setting->value = unserialize($setting->value);
            }
        }

        return $this->ArrayHelper->numericToKey($settings, 'key', 'value');
    }

    /**
     * Retrieves a staff member from the support system
     *
     * @param int $staff_id The ID of the staff member
     * @param int $company_id The ID of the company to which this staff belongs
     * @return mixed An stdClass object representing the staff member, or false if none found
     */
    public function get($staff_id, $company_id)
    {
        Loader::loadModels($this, ['SupportManager.SupportManagerDepartments']);

        // Return if the staff is not in the support system
        if ($this->validateDuplicateStaff($staff_id, $company_id)) {
            return false;
        }

        // Get the staff member from the system
        $staff = $this->Record->select(['staff.*', 'staff_groups.company_id'])->
            from('staff')->
            innerJoin('staff_group', 'staff_group.staff_id', '=', 'staff.id', false)->
                on('staff_groups.company_id', '=', $company_id)->
            innerJoin('staff_groups', 'staff_groups.id', '=', 'staff_group.staff_group_id', false)->
            where('staff.id', '=', $staff_id)->
            fetch();

        // In case the staff does not exist in the system, but exists in the support system, continue anyway
        if (!$staff) {
            $staff = (object)['id' => $staff_id, 'company_id' => $company_id];
        }

        // Get staff departments
        $staff->departments = $this->SupportManagerDepartments->getAll(
            $company_id,
            null,
            null,
            ['staff_id' => $staff_id]
        );

        // Get staff schedules
        $staff->schedules = $this->getSchedules($staff->id, $company_id);

        // Get staff settings
        $staff->settings = $this->getSettings($staff->id, $company_id);

        return $staff;
    }

    /**
     * Fetches al of the staff schedules for the given staff member
     *
     * @param int $staff_id The ID of the staff member
     * @param int $company_id The ID of the company to which this staff belongs
     * @return array A list of staff schedules
     */
    public function getSchedules($staff_id, $company_id)
    {
        return $this->Record->select()->
            from('support_staff_schedules')->
            where('staff_id', '=', $staff_id)->
            where('company_id', '=', $company_id)->
            fetchAll();
    }

    /**
     * Retrieve a list of all staff
     *
     * @param int $company_id The ID of the company to fetch staff from
     * @param int $department_id The ID of the department to fetch staff from
     * @param array $order_by A list of sort=>order options
     * @return array A list of stdClass objects representing staff
     */
    public function getAll(
        $company_id,
        $department_id = null,
        $get_settings = true,
        array $order_by = ['first_name' => 'asc']
    ) {
        $staff = $this->getStaff($company_id, $department_id)->order($order_by)->fetchAll();

        // Fetch staff settings
        if ($get_settings) {
            foreach ($staff as &$member) {
                $member->settings = $this->getSettings($member->id, $company_id);
            }
        }

        return $staff;
    }

    /**
     * Fetches a list of all staff members from the given department that is available on the
     * given day and time
     *
     * @param int $company_id The ID of the company to fetch staff from
     * @param int $department_id The ID of the department to fetch staff from
     * @param array $available_days A key=>value array of days=>times that the staff must be available
     *  e.g. array('sun' => '08:00:00', 'mon' => '10:00:00') fetches staff available
     *  Sunday at 8am UTC AND monday at 10am UTC
     * @return array A list of stdClass objects each representing a staff member
     */
    public function getAllAvailable($company_id, $department_id, array $available_days = null)
    {
        $available_staff = [];

        // Get the staff from the department
        $staff = $this->getStaff($company_id, $department_id)->getStatement();

        // Check each staff member schedules
        while (($member = $staff->fetch())) {
            // Assume staff is available
            $available = true;

            if ($available_days) {
                // Check the schedules for availability
                $schedules = $this->getSchedules($member->id, $company_id);

                foreach ($available_days as $day => $time) {
                    foreach ($schedules as $schedule) {
                        if ($schedule->day == $day) {
                            // Available all day. Skip to the next day to check
                            if ($schedule->start_time == $schedule->end_time) {
                                continue 2;
                            }

                            // Check the start/end times
                            $ticket_time = strtotime($time);
                            $start_time = strtotime($schedule->start_time);
                            $end_time = strtotime($schedule->end_time);
                            if ($start_time > $end_time) {
                                $end_time = strtotime($schedule->end_time . ' +1 day');
                            }

                            // Available on that specific day/time. Skip to the next day to check
                            if (($end_time >= $ticket_time && $start_time <= $ticket_time)) {
                                continue 2;
                            }

                            // Staff not available, skip to the next member
                            continue 3;
                        }
                    }
                    // Staff is not available
                    $available = false;
                }
            }

            // This staff member is available, get their settings
            if ($available) {
                $member->settings = $this->getSettings($member->id, $company_id);
                $available_staff[] = $member;
            }
        }

        return $available_staff;
    }

    /**
     * Retrieve a list of staff
     *
     * @param int $company_id The ID of the company to fetch staff from
     * @param int $department_id The ID of the department to fetch staff from
     * @param int $page The page number of results to fetch
     * @param array $order_by A list of sort=>order options
     * @return array A list of stdClass objects representing staff
     */
    public function getList(
        $company_id,
        $department_id = null,
        $get_settings = true,
        $page = 1,
        array $order_by = ['first_name' => 'asc']
    ) {
        $staff = $this->getStaff($company_id, $department_id)->order($order_by)->
            limit($this->getPerPage(), (max(1, $page) - 1)*$this->getPerPage())->fetchAll();

        // Fetch settings
        if ($get_settings) {
            foreach ($staff as &$member) {
                $member->settings = $this->getSettings($member->id, $company_id);
            }
        }

        return $staff;
    }

    /**
     * Retrieves the total number of staff
     *
     * @param int $company_id The ID of the company to fetch staff from
     * @param int $department_id The ID of the department to fetch staff from
     * @return int The total number of staff
     */
    public function getListCount($company_id, $department_id = null)
    {
        return $this->getStaff($company_id, $department_id)->numResults();
    }

    /**
     * Returns a Record object for fetching staff
     *
     * @param int $staff_id The ID of the staff member
     * @param int $department_id The ID of the department to fetch staff from
     * @return Record A partially-built Record object to fetch staff
     */
    private function getStaff($company_id, $department_id = null)
    {
        // Select staff info
        $this->Record = $this->Record->select(['staff.*', 'staff_groups.company_id'])->
            from('support_staff_schedules')->
            leftJoin('staff', 'staff.id', '=', 'support_staff_schedules.staff_id', false)->
            leftjoin('staff_group', 'staff_group.staff_id', '=', 'staff.id', false)->
            leftJoin('staff_groups', 'staff_groups.id', '=', 'staff_group.staff_group_id', false);

        // Filter on department
        if ($department_id) {
            $this->Record->
                    on('support_staff_departments.department_id', '=', $department_id)->
                innerJoin('support_staff_departments', 'support_staff_departments.staff_id', '=', 'staff.id', false);
        }

        return $this->Record->where('support_staff_schedules.company_id', '=', $company_id)->
            group('support_staff_schedules.staff_id');
    }

    /**
     * Retrieves a list of validation rules for adding/editing a staff member
     *
     * @param array $vars A list of input vars
     * @param bool $edit True to get the edit rules, false for the add rules
     * @return array A list of validation rules
     */
    private function getRules(array $vars, $edit = false)
    {
        $rules = [
            'staff_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'staff'],
                    'message' => $this->_('SupportManagerStaff.!error.staff_id.exists')
                ],
                'belongs' => [
                    'rule' => [[$this, 'validateStaffInCompany'], (isset($vars['company_id']) ? $vars['company_id'] : null)],
                    'message' => $this->_('SupportManagerStaff.!error.staff_id.belongs')
                ],
                'duplicate' => [
                    'rule' => [[$this, 'validateDuplicateStaff'], (isset($vars['company_id']) ? $vars['company_id'] : null)],
                    'message' => $this->_('SupportManagerStaff.!error.staff_id.duplicate')
                ]
            ],
            'company_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'companies'],
                    'message' => $this->_('SupportManagerStaff.!error.company_id.exists')
                ]
            ],
            'departments' => [
                'exists' => [
                    'rule' => [[$this, 'validateDepartmentExists'], (isset($vars['company_id']) ? $vars['company_id'] : null)],
                    'message' => $this->_('SupportManagerStaff.!error.departments.exists')
                ]
            ],
            'schedules' => [
                'minimum' => [
                    'rule' => [[$this, 'validateScheduleDaysGiven']],
                    'message' => $this->_('SupportManagerStaff.!error.schedules.minimum')
                ],
                'unique_days' => [
                    'rule' => [[$this, 'validateScheduleDays']],
                    'message' => $this->_('SupportManagerStaff.!error.schedules.unique_days')
                ]
            ],
            'schedules[][day]' => [
                'format' => [
                    'rule' => [[$this, 'validateScheduleDay']],
                    'message' => $this->_('SupportManagerStaff.!error.schedules[][day].format')
                ]
            ],
            'schedules[][start_time]' => [
                'format' => [
                    'rule' => [[$this, 'validateScheduleTime']],
                    'message' => $this->_('SupportManagerStaff.!error.schedules[][start_time].format'),
                    'post_format' => [[$this, 'dateToUtc']]
                ]
            ],
            'schedules[][end_time]' => [
                'format' => [
                    'rule' => [[$this, 'validateScheduleTime']],
                    'message' => $this->_('SupportManagerStaff.!error.schedules[][end_time].format'),
                    'post_format' => [[$this, 'dateToUtc']]
                ]
            ]
        ];

        if ($edit) {
            // Remove unnecessary rules
            unset($rules['staff_id']['duplicate']);
        }

        return $rules;
    }

    /**
     * Validates that the given staff member belongs to the given company
     *
     * @param int $staff_id The ID of the staff member
     * @param int $company_id The ID of the company
     * @return bool True if the staff member belongs to the company, false otherwise
     */
    public function validateStaffInCompany($staff_id, $company_id)
    {
        // Let another rule handle the error if no staff is given
        if (empty($staff_id)) {
            return true;
        }

        $results = $this->Record->select('staff.id')->from('staff')->
            innerJoin('staff_group', 'staff_group.staff_id', '=', 'staff.id', false)->
            innerJoin('staff_groups', 'staff_groups.id', '=', 'staff_group.staff_group_id', false)->
            where('staff_groups.company_id', '=', $company_id)->
            where('staff.id', '=', $staff_id)->
            numResults();

        return ($results > 0);
    }

    /**
     * Validates that the given staff member does NOT exist for the given company in the support system
     *
     * @param int $staff_id The ID of the staff member
     * @param int $company_id The ID of the company
     * @return bool True if the staff member does not already exist, false otherwise
     */
    public function validateDuplicateStaff($staff_id, $company_id)
    {
        $results = $this->Record->select()->from('support_staff_schedules')->
            where('staff_id', '=', $staff_id)->where('company_id', '=', $company_id)->
            group('staff_id')->numResults();

        return ($results == 0);
    }

    /**
     * Validates that every support department exists
     *
     * @param array $departments A list of department IDs
     * @return bool False if a department given does not exist, true otherwise
     */
    public function validateDepartmentExists($departments)
    {
        if (empty($departments)) {
            return true;
        }

        // Validate every department exists
        foreach ($departments as $department_id) {
            if (!$this->validateExists($department_id, 'id', 'support_departments')) {
                return false;
            }
        }
        return true;
    }

    /**
     * Validates that the gives scheduled days are unique
     *
     * @param array $schedules A list of staff schedules
     * @return bool True if no duplicate scheduled days exist, false otherwise
     */
    public function validateScheduleDays($schedules)
    {
        if (empty($schedules)) {
            return true;
        }

        $days = [];
        foreach ($schedules as $schedule) {
            if (isset($schedule['day'])) {
                $days[] = $schedule['day'];
            }
        }

        return (count($days) == count(array_unique($days)));
    }

    /**
     * Validates that the given scheduled days are valid
     *
     * @param array $schedules A list of staff schedules
     * @return bool True if the given scheduled days contains at least one valid day, false otherwise
     */
    public function validateScheduleDaysGiven($schedules)
    {
        // Must have at least one scheduled day
        if (empty($schedules)) {
            return false;
        }

        $days = [];
        $all_days = array_keys($this->getDays());
        foreach ($schedules as $schedule) {
            if (isset($schedule['day']) && in_array($schedule['day'], $all_days) &&
                isset($schedule['start_time']) && $this->validateScheduleTime($schedule['start_time'], false) &&
                isset($schedule['end_time']) && $this->validateScheduleTime($schedule['end_time'], false)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Validates that the given day is valid
     *
     * @param string $day The day to validate
     * @return bool True if the given day is valid, false otherwise
     */
    public function validateScheduleDay($day)
    {
        return in_array($day, array_keys($this->getDays()));
    }

    /**
     * Validates that the given time is in a valid format
     *
     * @param string $time The time to validate
     * @return bool True if the time is in a valid format, false otherwise
     */
    public function validateScheduleTime($time, $allow_empty = true)
    {
        if ($allow_empty && empty($time)) {
            return true;
        }

        return in_array($time, array_keys($this->getTimes()));
    }
}
