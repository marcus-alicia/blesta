<?php
/**
 * SolusVM Service Options
 *
 * @package blesta
 * @subpackage blesta.components.modules.solusvm
 * @copyright Copyright (c) 2015, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class SolusvmServiceOptions
{
    /**
     * Memory swap (in MB)
     */
    const DEFAULT_MEMORY_SWAP = 512;

    /**
     * Initializes and sets the virtualization type of the VPS
     *
     * @param string $type The virtualization type. One of ('xen', 'xen hvm', 'kvm', 'openvz', default 'xen')
     */
    public function __construct($type = 'xen')
    {
        $this->virtualization_type = strtolower($type);
    }

    /**
     * Retrieves all of the available service options by filtering them out from the given fields
     *
     * @param array $vars An array of key/value pairs representing each config option
     * @param mixed $plan An stdClass object representing the plan (optional), including:
     *  - id The ID of the plan
     *  - name The name of the plan
     *  - ipv6subnets The number of IPv6 subnets
     *  - automatedbackups 1 or 0, whether automated backups are enabled
     *  - cpus Number of CPUs
     *  - ram Memory in bytes
     *  - swap Swap memory in bytes
     *  - disk Disk space in bytes
     *  - bandwidth Bandwidth limit in bytes
     * @return array An array of key/value pairs representing each of the service options set
     */
    public function getAll(array $vars, $plan = null)
    {
        $options = [];
        $plan_fields = [
            'extra_cpus' => 'cpus',
            'extra_memory' => 'ram',
            'extra_swap' => 'swap',
            'extra_disk' => 'disk',
            'extra_bandwidth' => 'bandwidth'
        ];

        // Map the local fields to API fields
        foreach ($this->getFields() as $key => $field) {
            if (array_key_exists($key, $vars)) {
                // Set the input vars as an API option
                $options[$field] = $vars[$key];
                $options[$key] = $vars[$key];

                // Update the input vars to add the input value onto the plan value
                if (array_key_exists($key, $plan_fields) && $plan &&
                    is_object($plan) && property_exists($plan, $plan_fields[$key])) {
                    $options[$field] = $this->addPlanValue($key, $vars[$key], $plan->{$plan_fields[$key]});
                }
            }
        }

        // Include any swap memory onto the memory field separated by a colon
        if (array_key_exists('custommemory', $options)) {
            // Determine the extra swap selected
            $extra_swap = (array_key_exists('extra_swap', $options) ? $options['extra_swap'] : 0);

            // Determine the plan's default swap
            $plan_swap = 0;
            if ($plan && is_object($plan) && property_exists($plan, 'swap')) {
                $plan_swap = $plan->swap;
            }

            // Format memory into 'memory:swap'
            $options['custommemory'] .= ':' . $this->getSwap($options['custommemory'], $extra_swap, $plan_swap);
        }

        return $options;
    }

    /**
     * Fetches the swap amount
     *
     * @param int $memory The amount of memory in MB
     * @param int $extra_swap The amount of selected swap in MB
     * @param int $plan_swap The amount of plan swap in B
     * @return int The swap/burst memory
     */
    private function getSwap($memory, $extra_swap, $plan_swap = 0)
    {
        $total_swap = $this->addPlanValue('extra_swap', $extra_swap, $plan_swap);

        // If no swap is known, use the default swap instead
        $total_swap = (empty($total_swap) ? SolusvmServiceOptions::DEFAULT_MEMORY_SWAP : $total_swap);

        // OpenVZ must have swap >= memory
        if ($this->virtualization_type == 'openvz' && $memory > $total_swap) {
            $total_swap = $this->addPlanValue('extra_swap', ($memory + $extra_swap), $plan_swap);
        }

        return $total_swap;
    }

    /**
     * Updates the given plan value to include the given value
     *
     * @param string $field The name of the local field
     * @param string $value The value to add to the plan value
     * @param string $plan_value The base plan value
     * @return string The value total
     */
    private function addPlanValue($field, $value, $plan_value)
    {
        $result = '';
        $plan_value = (empty($plan_value) ? 0 : (float)$plan_value);
        $multiplier = 1;
        $step = 1024;

        switch ($field) {
            case 'extra_disk':
            case 'extra_bandwidth':
                // Disk/bandwidth are entered as GB, but given as B in the plan
                $multiplier = $step;
                // No break
            case 'extra_memory':
            case 'extra_swap':
                // Convert the given value from B to MB/GB
                $multiplier *= ($step*$step);
                $result = (int)$value + (int)($plan_value / $multiplier);
                break;
            case 'extra_cpus':
                $result = (int)$value + (int)$plan_value;
                break;
        }

        return $result;
    }

    /**
     * Retrieves a list of possible service option fields
     *
     * @return array A key/value array of local service option field names and their API key name
     */
    private function getFields()
    {
        return [
            'extra_ips' => 'customextraip',
            'extra_disk' => 'customdiskspace',
            'extra_memory' => 'custommemory',
            'extra_swap' => 'customswapmemory',
            'extra_bandwidth' => 'custombandwidth',
            'extra_cpus' => 'customcpu',
            'nodegroup' => 'nodegroup',
            'template' => 'template'
        ];
    }
}
