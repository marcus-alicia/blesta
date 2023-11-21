<?php
/**
 * Upgrades to version 4.2.0-b2
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2017, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade4_2_0B2 extends UpgradeUtil
{
    /**
     * @var array An array of all tasks completed
     */
    private $tasks = [];

    /**
     * Setup
     */
    public function __construct()
    {
        Configure::load('blesta');
        Loader::loadComponents($this, ['Record']);
    }

    /**
     * Returns a numerically indexed array of tasks to execute for the upgrade process
     *
     * @retrun array A numerically indexed array of tasks to execute for the upgrade process
     */
    public function tasks()
    {
        return [
            'addStateSpecialCharacters'
        ];
    }

    /**
     * Processes the given task
     *
     * @param string $task The task to process
     */
    public function process($task)
    {
        $tasks = $this->tasks();

        // Ensure task exists
        if (!in_array($task, $tasks)) {
            return;
        }

        $this->tasks[] = $task;
        $this->{$task}();
    }

    /**
     * Rolls back all tasks completed for the upgrade process
     */
    public function rollback()
    {
        // Undo all tasks
        while (($task = array_pop($this->tasks))) {
            $this->{$task}(true);
        }
    }

    /**
     * Updates states that have ? instead of the correct special character in their name
     *
     * @param bool $undo Whether to add or undo the change
     */
    private function addStateSpecialCharacters($undo = false)
    {
        if ($undo) {
            $states = $this->getStates();

            foreach ($states as $new_name => $state) {
                $this->Record->where('country_alpha2', '=', $state['country_alpha2'])
                    ->where('code', '=', $state['code'])
                    ->where('name', '=', $new_name)
                    ->update('states', ['name' => $state['name']]);
            }
        } else {
            $states = $this->getStates();

            foreach ($states as $new_name => $state) {
                $this->Record->where('country_alpha2', '=', $state['country_alpha2'])
                    ->where('code', '=', $state['code'])
                    ->where('name', '=', $state['name'])
                    ->update('states', ['name' => $new_name]);
            }
        }
    }

    /**
     * Gets a list of states that need their name to be updated
     */
    private function getStates()
    {
        return [
            'Aragac̣otn' => ['country_alpha2' => 'AM', 'code' => 'AG', 'name' => 'Aragac?otn'],
            'Loṙi' => ['country_alpha2' => 'AM', 'code' => 'LO', 'name' => 'Lo?y'],
            'Zeničko-dobojski kanton' => ['country_alpha2' => 'BA', 'code' => '04', 'name' => 'Zeni?ko-dobojski kanton'],
            'Hercegovačko-neretvanski kanton' => ['country_alpha2' => 'BA', 'code' => '07', 'name' => 'Hercegova?ko-neretvanski kanton'],
            'Zapadnohercegovački kanton' => ['country_alpha2' => 'BA', 'code' => '08', 'name' => 'Zapadnohercegova?ki kanton'],
            'Stueng Traeng [Stœ̆ng Trêng]' => ['country_alpha2' => 'KH', 'code' => '19', 'name' => 'Stueng Traeng [Stœ?ng Trêng]'],
            'Al Jabal al Akhḑar' => ['country_alpha2' => 'LY', 'code' => 'JA', 'name' => 'Al Jabal al Akh?ar'],
            'Wādī al Ḩayāt' => ['country_alpha2' => 'LY', 'code' => 'WD', 'name' => 'Wadi al ?ayat'],
            "Ḩā'il" => ['country_alpha2' => 'SA', 'code' => '06', 'name' => "?a'il"],
            "'Asīr" => ['country_alpha2' => 'SA', 'code' => '14', 'name' => '?Asir'],
            "Dar'ā" => ['country_alpha2' => 'SY', 'code' => 'DR', 'name' => 'Dar?a'],
            'Al Ḩasakah' => ['country_alpha2' => 'SY', 'code' => 'HA', 'name' => 'Al ?asakah'],
            'Ḩimş' => ['country_alpha2' => 'SY', 'code' => 'HI', 'name' => '?ims'],
            'Ḩalab' => ['country_alpha2' => 'SY', 'code' => 'HL', 'name' => '?alab'],
            'Ḩamāh' => ['country_alpha2' => 'SY', 'code' => 'HM', 'name' => '?amah'],
            "Al Bayḑā'" => ['country_alpha2' => 'YE', 'code' => 'BA', 'name' => "Al Bay?a'"],
            'Al Ḩudaydah' => ['country_alpha2' => 'YE', 'code' => 'HU', 'name' => 'Al ?udaydah'],
            'Laḩij' => ['country_alpha2' => 'YE', 'code' => 'LA', 'name' => 'La?ij']
        ];
    }
}
