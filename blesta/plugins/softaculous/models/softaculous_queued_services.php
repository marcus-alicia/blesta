<?php
/**
 * SoftaculousQueuedServices model
 *
 * @package blesta
 * @subpackage blesta.plugins.softaculous
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class SoftaculousQueuedServices extends AppModel
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        Language::loadLang('softaculous_queued_services', null, PLUGINDIR . 'softaculous' . DS . 'language' . DS);
    }

    /**
     * Adds a queued service
     *
     * @param array $vars A list of input vars including:
     *
     *  - service_id The ID service on which the script is to be run
     *  - company_id The ID of the company to which the service belongs
     *  - errors The errors that caused this service to be queued
     *  - attempts The number of times the queued installation has been attempted
     * @return stdClass The stdClass object representing the newly-created queued script, or void on error
     */
    public function add(array $vars)
    {
        $this->Input->setRules($this->getRules($vars));

        if ($this->Input->validates($vars)) {
            $fields = ['service_id', 'company_id', 'errors', 'attempts'];
            $this->Record->insert('softaculous_queued_services', $vars, $fields);

            return $this->get($this->Record->lastInsertId());
        }
    }

    /**
     * Edits a queued service
     *
     * @param int $service_id The ID of the service for which to edit the queue
     * @param array $vars A list of input vars including:
     *
     *  - company_id The ID of the company to which the service belongs
     *  - errors The errors that caused this service to be queued
     *  - attempts The number of times the queued installation has been attempted
     * @return stdClass The stdClass object representing the edited queued script, or void on error
     */
    public function edit($service_id, array $vars)
    {
        $vars['service_id'] = $service_id;
        $this->Input->setRules($this->getRules($vars, true));

        if ($this->Input->validates($vars)) {
            $fields = ['errors', 'attempts'];

            $this->Record->where('service_id', '=', $service_id)->
                update('softaculous_queued_services', $vars, $fields);

            return $this->get($service_id);
        }
    }

    /**
     * Attempts to remove a service from the queue
     *
     * @param int $service_id The ID of the service to remove from the queue
     */
    public function delete($service_id)
    {
        $this->Record->from('softaculous_queued_services')->
            where('service_id', '=', $service_id)->
            delete();
    }

    /**
     * Fetches queued service data
     *
     * @param int $service_id The ID of the service for which to fetch queued data
     * @return mixed An stdClass object representing the service queue, or false if none exist
     */
    public function get($service_id)
    {
        $service = $this->Record->select()->
            from('softaculous_queued_services')->
            where('service_id', '=', $service_id)->
            fetch();

        return $service;
    }

    /**
     * Fetches a list of all queued services
     *
     * @param int $company_id The ID of the company whose queued services to fetch
     * @return array A list of stdClass objects, each representing a queued service
     */
    public function getAll($company_id)
    {
        return $this->Record->select()->
            from('softaculous_queued_services')->
            where('company_id', '=', $company_id)->
            fetchAll();
    }

    /**
     * Fetches a list of rules for adding/editing a queued service
     *
     * @param array $vars A list of input vars
     * @param bool $edit True to get the edit rules, false for the add rules (optional, default false)
     */
    private function getRules(array $vars, $edit = false)
    {
        $rules = [
            'service_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'services'],
                    'message' => $this->_('SoftaculousQueuedServices.!error.service_id.exists')
                ]
            ],
            'company_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'companies'],
                    'message' => $this->_('SoftaculousQueuedServices.!error.company_id.exists')
                ]
            ],
            'attempts' => [
                'valid' => [
                    'rule' => ['matches', '/^[0-9]+$/'],
                    'message' => $this->_('SoftaculousQueuedServices.!error.company_id.valid')
                ]
            ],
        ];

        if ($edit) {
            // Remove unnecessary rules
            unset($rules['company_id']);

            // Set all rules to optional
            $rules = $this->setRulesIfSet($rules);

            // Require a valid queued service ID
            $rules['service_id']['exists_softaculous'] = [
                'rule' => [[$this, 'validateExists'], 'service_id', 'softaculous_queued_services'],
                'message' => $this->_('SoftaculousQueuedServices.!error.service_id.exists_softaculous')
            ];
        }

        return $rules;
    }
}
