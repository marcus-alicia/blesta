<?php
/**
 * Plesk Reseller Account management
 *
 * From API RPC version 1.6.0.0 and greater
 *
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @package plesk.commands
 */
class PleskResellerAccounts extends PleskPacket
{
    /**
     * @var The earliest version this API may be used with
     */
    private $earliest_api_version = '1.6.0.0';
    /**
     * @var The PleskApi
     */
    private $api;
    /**
     * @var The base XML container for this API
     */
    private $base_container = '/reseller';

    /**
     * Sets the API to use for communication
     *
     * @param PleskApi $api The API to use for communication
     * @param string $api_version The API RPC version
     * @param PleskPacket $packet The current plesk packet
     */
    public function __construct(PleskApi $api, $api_version)
    {
        parent::__construct($api_version);
        $this->api = $api;

        $this->buildContainer();
    }

    /**
     * Retrieves the earliest version this API command supports
     *
     * @return string The earliest API RPC version supported
     */
    public function getEarliestVersion()
    {
        return $this->earliest_api_version;
    }

    /**
     * Builds the packet container for the API command
     */
    protected function buildContainer()
    {
        $this->insert(['reseller' => null]);
        $this->setContainer($this->base_container);
    }

    /**
     * Resets the container path to the base path
     */
    public function resetContainer()
    {
        $this->setContainer($this->base_container);
    }

    /**
     * Creates a new reseller account
     *
     * @param array $vars A list of input vars including:
     *  - name The reseller's name (first and last, 1 to 60 characters in length)
     *  - login The reseller's login name for the account
     *  - password The reseller's login password for the account (5 to 14 characters in length)
     *  - company The reseller's company name (optional, 0 to 60 characters in length)
     *  - status The current status of the reseller account (optional, default 0, one of:)
     *      - 0 Active
     *      - 16 Disabled by Admin
     *  - phone The reseller's phone number (optional, 0 to 30 characters in length)
     *  - fax The reseller's fax number (optional, 0 to 30 characters in length)
     *  - email The reseller's email address (optional)
     *  - address The reseller's postal address (optional)
     *  - city The reseller's city (optional, 0 to 50 characters in length)
     *  - state The reseller's state (optional, should only be used for US citizens; 0 to 25 characters in length)
     *  - zipcode The reseller's zip/postal code (optional, should only be used for US citizens;
     *      0 to 10 characters in length)
     *  - country The reseller's ISO 3166-1 alpha2 country code (optional)
     *  - locale The reseller's language in ISO 639-1 ISO 3166-1 alpha-2 concatenated format (e.g. "en_US") (optional)
     *  - external_id The reseller GUID in the Panel components (optional)
     *  - plan An array of one of the following key/value pairs:
     *      - id The reseller service plan ID
     *      - name The reseller service plan name
     *      - guid The reseller service plan guid
     *  @return PleskResponse
     */
    public function add(array $vars)
    {
        // Set the container for this API request
        $this->insert(['add' => null], $this->getContainer());
        $this->setContainer($this->base_container . '/add');

        // Build gen_info section
        $vars['status'] = (isset($vars['status']) ? $vars['status'] : '0');
        $this->buildGenInfo($vars, $this->getContainer());

        #
        # TODO: build limits, permissions
        #

        // Set a service plan
        if (isset($vars['plan']['id'])) {
            $this->insert(['plan-id' => $vars['plan']['id']], $this->getContainer());
        } elseif (isset($vars['plan']['name'])) {
            $this->insert(['plan-name' => $vars['plan']['name']], $this->getContainer());
        } elseif (isset($vars['plan']['guid'])) {
            $this->insert(['plan-guid' => $vars['plan']['guid']], $this->getContainer());
        }

        return $this->api->submit($this->fetch(), $this->getContainer());
    }

    /**
     * Deletes a reseller account
     *
     * @param array $vars An array of input params including only ONE of the following:
     *  - filter Set one of the following to identify the account to delete:
     *      - id The ID of the reseller account to fetch
     *      - login The login name of the reseller account to fetch
     *      - owner-id The ID of the reseller account owner to fetch
     *      - owner-login The login of the reseller account owner to fetch
     *      - guid The GUID of the reseller account to fetch
     * @return PleskResponse
     */
    public function delete(array $vars)
    {
        $this->insert(['del' => null], $this->getContainer());
        $this->setContainer($this->base_container . '/del');

        // Build the filter section
        $filter = (isset($vars['filter']) ? $vars['filter'] : []);
        $this->buildFilter($filter);

        return $this->api->submit($this->fetch(), $this->getContainer());
    }

    /**
     * Retrieves the reseller account and information
     *
     * @param array $vars An array of input params including:
     *  - filter Set one of the following to fetch:
     *      - id The ID of the reseller account to fetch
     *      - login The login name of the reseller account to fetch
     *      - guid The GUID of the reseller account to fetch
     *      - all True to fetch all resellers
     *      - external_id The reseller GUID in the Panel components
     *  - settings An array of settings you would like to include (all optional)
     *      - stat True to fetch statistics
     *      - permissions True to fetch permissions
     *      - limits True to fetch limits on Plesk resources and limit policy for the account
     *      - ippool True to fetch IP pool settings
     *      - subscriptions True to fetch subscriptions owned by customers of the given reseller
     * @return PleskResponse
     */
    public function get(array $vars)
    {
        // Set the container for this API request
        $this->insert(['get' => null], $this->getContainer());
        $this->setContainer($this->base_container . '/get');

        // Build the filter section
        $filter = (isset($vars['filter']) ? $vars['filter'] : []);
        $this->buildFilter($filter);

        // Include any reseller information/stats
        $this->insert(['dataset' => ['gen_info' => null, 'stat' => null]], $this->getContainer());

        // Always fetch general information
        $vars['settings'] = (isset($vars['settings']) ? $vars['settings'] : []);
        $vars['settings'] = array_merge($vars['settings'], ['gen_info' => true]);

        // Set any of the settings to fetch
        $options = ['gen_info', 'stat', 'permissions', 'limits', 'ippool', 'subscriptions'];

        foreach ($options as $option) {
            if (array_key_exists($option, $vars['settings']) && $vars['settings'][$option]) {
                $this->insert([$option => null], $this->getContainer() . '/dataset');
            }
        }

        return $this->api->submit($this->fetch(), $this->getContainer());
    }

    /**
     * Updates reseller account information
     *
     * @param array $vars An array of input params including:
     *  - filter An array of at least one field identifying which reseller account to update:
     *      - id The ID of the reseller account to fetch
     *      - login The login name of the reseller account to fetch
     *      - guid The GUID of the reseller account to fetch
     *      - all True to fetch all resellers
     *      - external_id The reseller GUID in the Panel components
     *  - general An array of general information to update:
     *      - name The reseller's name (first and last, 1 to 60 characters in length)
     *      - login The reseller's login name for the account
     *      - password The reseller's login password for the account (5 to 14 characters in length)
     *      - company The reseller's company name (optional, 0 to 60 characters in length)
     *      - status The current status of the reseller account (optional, one of:)
     *          - 0 Active
     *          - 4 Under backup/Restore
     *          - 16 Disabled by Admin
     *          - 256 Expired
     *      - phone The reseller's phone number (optional, 0 to 30 characters in length)
     *      - fax The reseller's fax number (optional, 0 to 30 characters in length)
     *      - email The reseller's email address (optional)
     *      - address The reseller's postal address (optional)
     *      - city The reseller's city (optional, 0 to 50 characters in length)
     *      - state The reseller's state (optional, should only be used for US citizens;
     *          0 to 25 characters in length)
     *      - zipcode The reseller's zip/postal code (optional, should only be used for US citizens;
     *          0 to 10 characters in length)
     *      - country The reseller's ISO 3166-1 alpha2 country code (optional)
     *      - locale The reseller's language in ISO 639-1 ISO 3166-1 alpha-2 concatenated
     *          format (e.g. "en_US") (optional)
     *      - guid The reseller's GUID
     *      - external_id The reseller GUID in the Panel components (optional)
     * @return PleskResponse
     */
    public function set(array $vars)
    {
        // Set the container for this API request
        $this->insert(['set' => null], $this->getContainer());
        $this->setContainer($this->base_container . '/set');

        // Build the filter section
        $filter = (isset($vars['filter']) ? $vars['filter'] : []);
        $this->buildFilter($filter);

        // Build gen_info section into the values section
        $this->insert(['values' => null], $this->getContainer());
        $general = (isset($vars['general']) ? $vars['general'] : []);
        $this->buildGenInfo($general, $this->getContainer() . '/values');

        #
        # TODO: build limits, permissions into the values section
        #

        return $this->api->submit($this->fetch(), $this->getContainer());
    }

    /**
     * Changes a plan for a reseller
     *
     * @param array $vars An array of input params including:
     *  - filter An array of fields to filter the request on, one of the following:
     *      - id The reseller ID
     *      - login The reseller login
     *      - guid The GUID of a reseller
     *      - all True to apply to all resellers
     *      - external_id The GUID of a reseller in the Panel component
     *  - plan An array containing ONE of the following to identify the plan to change to:
     *      - guid The plan GUID
     *      - no_plan True to set no service plan
     * @return PleskResponse
     */
    public function changePlan(array $vars)
    {
        // Set the container for this API request
        $this->insert(['switch-subscription' => null], $this->getContainer());
        $this->setContainer($this->base_container . '/switch-subscription');

        // Build the filter section
        $filter = (isset($vars['filter']) ? $vars['filter'] : []);
        $this->buildFilter($filter);

        // Set the plan
        if (isset($vars['plan'])) {
            if (isset($vars['plan']['guid'])) {
                $this->insert(['plan-guid' => $vars['plan']['guid']], $this->getContainer());
            }
            if (isset($vars['plan']['no_plan']) && $vars['plan']['no_plan']) {
                $this->insert(['no-plan' => null], $this->getContainer());
            }
        }

        return $this->api->submit($this->fetch(), $this->getContainer());
    }

    /**
     * Builds the filter section of the XML packet
     *
     * @see PleskResellerAccounts::get(), PleskResellerAccounts::delete()
     * @param array $vars An array of input params including only ONE of the following:
     *  - id The ID of the reseller account to fetch
     *  - login The login name of the reseller account to fetch
     *  - guid The GUID of the reseller account to fetch
     *  - all True to apply this operation to all resellers
     *  - external_id The reseller GUID in the Panel components
     */
    private function buildFilter(array $vars)
    {
        // Add the filter container
        $this->insert(['filter' => null], $this->getContainer());
        $filter_path = $this->getContainer() . '/filter';

        // Specify the account to fetch
        if (isset($vars['id'])) {
            $this->insert(['id' => $vars['id']], $filter_path);
        } elseif (isset($vars['login'])) {
            $this->insert(['login' => $vars['login']], $filter_path);
        } elseif (isset($vars['guid'])) {
            $this->insert(['guid' => $vars['guid']], $filter_path);
        } elseif (isset($vars['all']) && $vars['all']) {
            $this->insert(['all' => null], $filter_path);
        } elseif (isset($vars['external_id'])) {
            $this->insert(['external-id' => $vars['external_id']], $filter_path);
        }
    }

    /**
     * Builds the gen_info section of the XML packet
     *
     * @see PleskResellerAccounts::add()
     * @param array $vars A list of input vars including:
     *  - name The reseller's name (first and last, 1 to 60 characters in length)
     *  - login The reseller's login name for the account
     *  - password The reseller's login password for the account (5 to 14 characters in length)
     *  - company The reseller's company name (optional, 0 to 60 characters in length)
     *  - status The current status of the reseller account (optional, one of:)
     *      - 0 Active
     *      - 4 Under backup/Restore
     *      - 16 Disabled by Admin
     *      - 256 Expired
     *  - phone The reseller's phone number (optional, 0 to 30 characters in length)
     *  - fax The reseller's fax number (optional, 0 to 30 characters in length)
     *  - email The reseller's email address (optional)
     *  - address The reseller's postal address (optional)
     *  - city The reseller's city (optional, 0 to 50 characters in length)
     *  - state The reseller's state (optional, should only be used for US citizens;
     *      0 to 25 characters in length)
     *  - zipcode The reseller's zip/postal code (optional, should only be used for US citizens;
     *      0 to 10 characters in length)
     *  - country The reseller's ISO 3166-1 alpha2 country code (optional)
     *  - locale The reseller's language in ISO 639-1 ISO 3166-1 alpha-2 concatenated
     *      format (e.g. "en_US") (optional)
     *  - guid The reseller's GUID
     *  - external_id The reseller GUID in the Panel components (optional)
     * @param string $container The container path to insert the XML into
     */
    private function buildGenInfo(array $vars, $container)
    {
        $container = (empty($container) ? $this->getContainer() : $container);

        // Set the gen_info section
        $gen_info = ['gen-info' => []];

        // Set fields only if given
        if (isset($vars['company'])) {
            $gen_info['gen-info']['cname'] = $vars['company'];
        }
        if (isset($vars['name'])) {
            $gen_info['gen-info']['pname'] = $vars['name'];
        }
        if (isset($vars['login'])) {
            $gen_info['gen-info']['login'] = $vars['login'];
        }
        if (isset($vars['password'])) {
            $gen_info['gen-info']['passwd'] = $vars['password'];
        }
        if (isset($vars['status'])) {
            $gen_info['gen-info']['status'] = $vars['status'];
        }
        if (isset($vars['phone'])) {
            $gen_info['gen-info']['phone'] = $vars['phone'];
        }
        if (isset($vars['fax'])) {
            $gen_info['gen-info']['fax'] = $vars['fax'];
        }
        if (isset($vars['email'])) {
            $gen_info['gen-info']['email'] = $vars['email'];
        }
        if (isset($vars['address'])) {
            $gen_info['gen-info']['address'] = $vars['address'];
        }
        if (isset($vars['city'])) {
            $gen_info['gen-info']['city'] = $vars['city'];
        }
        if (isset($vars['state'])) {
            $gen_info['gen-info']['state'] = $vars['state'];
        }
        if (isset($vars['zipcode'])) {
            $gen_info['gen-info']['pcode'] = $vars['zipcode'];
        }
        if (isset($vars['country'])) {
            $gen_info['gen-info']['country'] = $vars['country'];
        }
        if (isset($vars['locale'])) {
            $gen_info['gen-info']['locale'] = $vars['locale'];
        }
        if (isset($vars['guid'])) {
            $gen_info['gen-info']['guid'] = $vars['guid'];
        }
        if (isset($vars['external_id'])) {
            $gen_info['gen-info']['external-id'] = $vars['external_id'];
        }

        // Add the gen_setup section
        $this->insert($gen_info, $container);
    }
}
