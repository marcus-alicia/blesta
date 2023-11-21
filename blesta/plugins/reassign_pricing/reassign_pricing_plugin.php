<?php
/**
 * Reassign Pricing plugin handler
 *
 * @package blesta
 * @subpackage blesta.plugins.reassign_pricing
 * @copyright Copyright (c) 2015, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ReassignPricingPlugin extends Plugin
{
    /**
     * Load language
     */
    public function __construct()
    {
        Language::loadLang('reassign_pricing_plugin', null, dirname(__FILE__) . DS . 'language' . DS);
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');
    }

    /**
     * Returns all actions to be configured for this widget
     * (invoked after install() or upgrade(), overwrites all existing actions)
     *
     * @return array A numerically indexed array containing:
     *  - action The action to register for
     *  - uri The URI to be invoked for the given action
     *  - name The name to represent the action (can be language definition)
     *  - options An array of key/value pair options for the given action
     */
    public function getActions()
    {
        return [
            // Client Profile Action Link
            [
                'action' => 'action_staff_client',
                'uri' => 'plugin/reassign_pricing/admin_main/index/',
                'name' => 'ReassignPricingPlugin.action_staff_client.index',
                'options' => [
                    'class' => 'record_payment',
                    'icon' => 'fa-random'
                ]
            ]
        ];
    }
}
