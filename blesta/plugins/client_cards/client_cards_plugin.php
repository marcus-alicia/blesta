<?php
/**
 * Client Cards plugin handler
 *
 * @package blesta
 * @subpackage blesta.plugins.client_cards
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ClientCardsPlugin extends Plugin
{
    /**
     * Initialize the plugin
     */
    public function __construct()
    {
        Language::loadLang('client_cards_plugin', null, dirname(__FILE__) . DS . 'language' . DS);

        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');
    }

    /**
     * Retrieves the total number of active and suspended services
     *
     * @param int $client_id The ID of the client assigned to the services
     * @return int The total number of active and suspended services
     */
    public function getServicesCount($client_id)
    {
        Loader::loadModels($this, ['Services', 'PluginManager']);

        // Exclude domains, if the domain manager plugin is installed
        $filters = [];
        if ($this->PluginManager->isInstalled('domains', Configure::get('Blesta.company_id'))) {
            $filters['type'] = 'services';
        }

        return $this->Services->getListCount($client_id, 'active', false, null, $filters)
            + $this->Services->getListCount($client_id, 'suspended', false, null, $filters);
    }

    /**
     * Retrieves the total number of active invoices/proformas
     *
     * @param int $client_id The ID of the client assigned to the invoices/proformas
     * @return int The total number of active invoices/proformas
     */
    public function getInvoicesCount($client_id)
    {
        Loader::loadModels($this, ['Invoices']);

        return $this->Invoices->getListCount($client_id);
    }

    /**
     * Returns all cards to be configured for this plugin (invoked after install() or upgrade(),
     * overwrites all existing cards)
     *
     * @return array A numerically indexed array containing:
     *
     *  - level The level this card should be displayed on (client or staff) (optional, default client)
     *  - callback A method defined by the plugin class for calculating the value of the card or fetching a custom html
     *  - callback_type The callback type, 'value' to fetch the card value or
     *      'html' to fetch the custom html code (optional, default value)
     *  - background The background color in hexadecimal or path to the background image for this card (optional)
     *  - background_type The background type, 'color' to set a hexadecimal background or
     *      'image' to set an image background (optional, default color)
     *  - label A string or language key appearing under the value as a label
     *  - link The link to which the card will be pointed to (optional)
     *  - enabled Whether this card appears on client profiles by default
     *      (1 to enable, 0 to disable) (optional, default 1)
     */
    public function getCards()
    {
        return [
            [
                'level' => 'client',
                'callback' => ['this', 'getServicesCount'],
                'callback_type' => 'value',
                'label' => 'ClientCardsPlugin.card_client.services',
                'text_color' => '#fffaeb',
                'background' => '#ffc107',
                'background_type' => 'color',
                'link' => 'services/index/active/',
                'enabled' => 1
            ],
            [
                'level' => 'client',
                'callback' => ['this', 'getInvoicesCount'],
                'callback_type' => 'value',
                'label' => 'ClientCardsPlugin.card_client.invoices',
                'text_color' => '#e4f5e7',
                'background' => '#28a746',
                'background_type' => 'color',
                'link' => 'invoices/index/open/',
                'enabled' => 1
            ]
        ];
    }
}
