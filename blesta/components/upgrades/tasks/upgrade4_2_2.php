<?php
/**
 * Upgrades to version 4.2.2
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2017, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade4_2_2 extends UpgradeUtil
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
            'repairServiceOptionPricing'
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
     * Fixes the config option pricings assigned to service to make sure they are consistent with the service pricing
     * term
     *
     * @param bool $undo Whether to add or undo the change
     */
    private function repairServiceOptionPricing($undo = false)
    {
        if ($undo) {
            // Do nothing
        } else {
            // Get each service that has a config option pricing that does not match the service pricing term, along
            // with the incorrect pricing ID
            $mismatched_services = $this->Record->select(
                    [
                        'services.id',
                        'service_options.id' => 'service_option_id',
                        'service_options.option_pricing_id',
                        'package_option_pricing.option_value_id'
                    ]
                )
                ->from('services')
                ->innerJoin('service_options', 'service_options.service_id', '=', 'services.id', false)
                ->innerJoin(
                    'package_option_pricing',
                    'package_option_pricing.id',
                    '=',
                    'service_options.option_pricing_id',
                    false
                )
                ->innerJoin(
                    ['pricings' => 'option_pricings'],
                    'option_pricings.id',
                    '=',
                    'package_option_pricing.pricing_id',
                    false
                )
                ->innerJoin(
                    'package_pricing',
                    'package_pricing.id',
                    '=',
                    'services.pricing_id',
                    false
                )
                ->innerJoin(
                    ['pricings' => 'service_pricings'],
                    'service_pricings.id',
                    '=',
                    'package_pricing.pricing_id',
                    false
                )
                ->where('service_pricings.term', '!=', 'option_pricings.term', false)
                ->orWhere('service_pricings.period', '!=', 'option_pricings.period', false)
                ->orWhere('service_pricings.currency', '!=', 'option_pricings.currency', false)
                ->getStatement();

            foreach ($mismatched_services as $mismatched_service) {
                // Get all option pricings that DO match the service pricing term for the given config option
                $option_pricing = $this->Record->select('package_option_pricing.id')
                    ->from('services')
                    ->innerJoin('service_options', 'service_options.service_id', '=', 'services.id', false)
                    ->innerJoin(
                        'package_pricing',
                        'package_pricing.id',
                        '=',
                        'services.pricing_id',
                        false
                    )
                    ->innerJoin(
                        ['pricings' => 'service_pricings'],
                        'service_pricings.id',
                        '=',
                        'package_pricing.pricing_id',
                        false
                    )
                    ->on('service_pricings.currency', '=', 'option_pricings.currency', false)
                    ->on('service_pricings.term', '=', 'option_pricings.term', false)
                    ->innerJoin(
                        ['pricings' => 'option_pricings'],
                        'service_pricings.period',
                        '=',
                        'option_pricings.period',
                        false
                    )
                    ->innerJoin(
                        'package_option_pricing',
                        'package_option_pricing.pricing_id',
                        '=',
                        'option_pricings.id',
                        false
                    )
                    ->where('package_option_pricing.option_value_id', '=', $mismatched_service->option_value_id)
                    ->where('services.id', '=', $mismatched_service->id)
                    ->order(['package_option_pricing.id' => 'ASC'])
                    ->fetch();

                // Update the config option to use the correct price if the is only one possibility
                if ($option_pricing) {
                    $this->Record->where('id', '=', $mismatched_service->service_option_id)
                        ->update('service_options', ['option_pricing_id' => $option_pricing->id]);
                }
            }
        }
    }
}
