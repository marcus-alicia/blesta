<?php
/**
 * Plesk Customer Account management
 *
 * From API RPC version 1.6.3.0 and greater
 *
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @package plesk.commands
 */
class PleskCustomerAccounts extends PleskPacket
{
    /**
     * @var The earliest version this API may be used with
     */
    private $earliest_api_version = '1.6.3.0';
    /**
     * @var The PleskApi
     */
    private $api;
    /**
     * @var The base XML container for this API
     */
    private $base_container = '/customer';

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
        $this->insert(['customer' => null]);
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
     * Creates a new customer account
     *
     * @param array $vars A list of input vars including:
     *  - name The customer's name (first and last, 1 to 60 characters in length)
     *  - login The customer's login name for the account
     *  - password The customer's login password for the account (5 to 14 characters in length)
     *  - company The customer's company name (optional, 0 to 60 characters in length)
     *  - status The current status of the customer account (optional, default 0, one of:)
     *      - 0 Active
     *      - 16 Disabled by Admin
     *  - phone The customer's phone number (optional, 0 to 30 characters in length)
     *  - fax The customer's fax number (optional, 0 to 30 characters in length)
     *  - email The customer's email address (optional)
     *  - address The customer's postal address (optional)
     *  - city The customer's city (optional, 0 to 50 characters in length)
     *  - state The customer's state (optional, should only be used for US citizens; 0 to 25 characters in length)
     *  - zipcode The customer's zip/postal code (optional, should only be used for
     *      US citizens; 0 to 10 characters in length)
     *  - country The customer's ISO 3166-1 alpha2 country code (optional)
     *  - locale The customer's language in ISO 639-1 ISO 3166-1 alpha-2 concatenated format (e.g. "en_US") (optional)
     *  - owner_id The ID of the customer account owner (optional)
     *  - owner_login The login name of the customer account owner (optional)
     *  - external_id The customer GUID in the Panel components (optional)
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

        return $this->api->submit($this->fetch(), $this->getContainer());
    }

    /**
     * Deletes a customer account
     *
     * @param array $vars An array of input params including only ONE of the following:
     *  - id The ID of the customer account to fetch
     *  - login The login name of the customer account to fetch
     *  - owner-id The ID of the customer account owner to fetch
     *  - owner-login The login of the customer account owner to fetch
     *  - guid The GUID of the customer account to fetch
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
     * Retrieves the customer account and information
     *
     * @param array $vars An array of input params including only ONE of the following:
     *  - id The ID of the customer account to fetch
     *  - login The login name of the customer account to fetch
     *  - owner-id The ID of the customer account owner to fetch
     *  - owner-login The login of the customer account owner to fetch
     *  - guid The GUID of the customer account to fetch
     * @return PleskResponse
     */
    public function get(array $vars)
    {
        // Set the container for this API request
        $this->insert(['get' => null], $this->getContainer());
        $this->setContainer($this->base_container . '/get');

        // Build the filter section
        $this->buildFilter($vars);

        // Include any customer information/stats
        $this->insert(['dataset' => ['gen_info' => null, 'stat' => null]], $this->getContainer());

        return $this->api->submit($this->fetch(), $this->getContainer());
    }

    /**
     * Updates customer account information
     *
     * @param array $vars An array of input params including:
     *  - filter An array of at least one field identifying which customer account to update:
     *      - id The customer account ID
     *      - login The customer account login name
     *      - owner_id The ID of the customer account owner
     *      - owner_login The name of the customer account owner
     *      - guid The GUID of the customer account
     *  - general An array of general information to update:
     *      - name The customer's name (first and last, 1 to 60 characters in length)
     *      - login The customer's login name for the account
     *      - password The customer's login password for the account (5 to 14 characters in length)
     *      - company The customer's company name (optional, 0 to 60 characters in length)
     *      - status The current status of the customer account (optional, one of:)
     *          - 0 Active
     *          - 16 Disabled by Admin
     *      - phone The customer's phone number (optional, 0 to 30 characters in length)
     *      - fax The customer's fax number (optional, 0 to 30 characters in length)
     *      - email The customer's email address (optional)
     *      - address The customer's postal address (optional)
     *      - city The customer's city (optional, 0 to 50 characters in length)
     *      - state The customer's state (optional, should only be used for US citizens; 0 to 25 characters in length)
     *      - zipcode The customer's zip/postal code (optional, should only be used for US citizens;
     *          0 to 10 characters in length)
     *      - country The customer's ISO 3166-1 alpha2 country code (optional)
     *      - locale The customer's language in ISO 639-1 ISO 3166-1 alpha-2 concatenated
     *          format (e.g. "en_US") (optional)
     *      - owner_id The ID of the customer account owner (optional, will be used if owner_login is also specified)
     *      - owner_login The login name of the customer account owner (optional)
     *      - external_id The customer GUID in the Panel components (optional)
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

        return $this->api->submit($this->fetch(), $this->getContainer());
    }

    /**
     * Upgrades a customer account to a reseller account
     *
     * @param array $vars A list of input params including:
     *  - filter An array of at least one field identifying which customer account to update:
     *      - id The customer account ID
     *      - login The customer account login name
     *      - owner_id The ID of the customer account owner
     *      - owner_login The name of the customer account owner
     *      - guid The GUID of the customer account
     * @return PleskResponse
     */
    public function upgrade(array $vars)
    {
        // Set the container for this API request
        $this->insert(['convert-to-reseller' => null], $this->getContainer());
        $this->setContainer($this->base_container . '/convert-to-reseller');

        // Build the filter section
        $filter = (isset($vars['filter']) ? $vars['filter'] : []);
        $this->buildFilter($filter);

        return $this->api->submit($this->fetch(), $this->getContainer());
    }

    /**
     * Builds the filter section of the XML packet
     *
     * @see PleskCustomerAccounts::get(), PleskCustomerAccounts::delete()
     * @param array $vars An array of input params including only ONE of the following:
     *  - id The ID of the customer account to fetch
     *  - login The login name of the customer account to fetch
     *  - owner-id The ID of the customer account owner to fetch
     *  - owner-login The login of the customer account owner to fetch
     *  - guid The GUID of the customer account to fetch
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
        } elseif (isset($vars['owner_id'])) {
            $this->insert(['owner-id' => $vars['owner_id']], $filter_path);
        } elseif (isset($vars['owner_login'])) {
            $this->insert(['owner-login' => $vars['owner_login']], $filter_path);
        } elseif (isset($vars['guid'])) {
            $this->insert(['guid' => $vars['guid']], $filter_path);
        }
    }

    /**
     * Builds the gen_info section of the XML packet
     *
     * @see PleskCustomerAccounts::add()
     * @param array $vars A list of input vars including:
     *  - name The customer's name (first and last, 1 to 60 characters in length)
     *  - login The customer's login name for the account
     *  - password The customer's login password for the account (5 to 14 characters in length)
     *  - company The customer's company name (optional, 0 to 60 characters in length)
     *  - status The current status of the customer account (optional, one of:)
     *      - 0 Active
     *      - 16 Disabled by Admin
     *  - phone The customer's phone number (optional, 0 to 30 characters in length)
     *  - fax The customer's fax number (optional, 0 to 30 characters in length)
     *  - email The customer's email address (optional)
     *  - address The customer's postal address (optional)
     *  - city The customer's city (optional, 0 to 50 characters in length)
     *  - state The customer's state (optional, should only be used for US citizens; 0 to 25 characters in length)
     *  - zipcode The customer's zip/postal code (optional, should only be used for US citizens;
     *      0 to 10 characters in length)
     *  - country The customer's ISO 3166-1 alpha2 country code (optional)
     *  - locale The customer's language in ISO 639-1 ISO 3166-1 alpha-2 concatenated format (e.g. "en_US") (optional)
     *  - owner_id The ID of the customer account owner (optional, will be used if owner_login is also specified)
     *  - owner_login The login name of the customer account owner (optional)
     *  - external_id The customer GUID in the Panel components (optional)
     * @param string $container The container path to insert the XML into
     */
    private function buildGenInfo(array $vars, $container)
    {
        $container = (empty($container) ? $this->getContainer() : $container);

        // Set the gen_info section
        $gen_info = ['gen_info' => []];

        // Set fields only if given
        if (isset($vars['company'])) {
            $gen_info['gen_info']['cname'] = $vars['company'];
        }
        if (isset($vars['name'])) {
            $gen_info['gen_info']['pname'] = $vars['name'];
        }
        if (isset($vars['login'])) {
            $gen_info['gen_info']['login'] = $vars['login'];
        }
        if (isset($vars['password'])) {
            $gen_info['gen_info']['passwd'] = $vars['password'];
        }
        if (isset($vars['status'])) {
            $gen_info['gen_info']['status'] = $vars['status'];
        }
        if (isset($vars['phone'])) {
            $gen_info['gen_info']['phone'] = $vars['phone'];
        }
        if (isset($vars['fax'])) {
            $gen_info['gen_info']['fax'] = $vars['fax'];
        }
        if (isset($vars['email'])) {
            $gen_info['gen_info']['email'] = $vars['email'];
        }
        if (isset($vars['address'])) {
            $gen_info['gen_info']['address'] = $vars['address'];
        }
        if (isset($vars['city'])) {
            $gen_info['gen_info']['city'] = $vars['city'];
        }
        if (isset($vars['state'])) {
            $gen_info['gen_info']['state'] = $vars['state'];
        }
        if (isset($vars['zipcode'])) {
            $gen_info['gen_info']['pcode'] = $vars['zipcode'];
        }
        if (isset($vars['country'])) {
            $gen_info['gen_info']['country'] = $vars['country'];
        }
        if (isset($vars['locale'])) {
            $gen_info['gen_info']['locale'] = $vars['locale'];
        }

        if (isset($vars['owner_id'])) {
            $gen_info['gen_info']['owner-id'] = $vars['owner_id'];
        } elseif (isset($vars['owner_login'])) {
            $gen_info['gen_info']['owner-login'] = $vars['owner_login'];
        }

        if (isset($vars['external_id'])) {
            $gen_info['gen_info']['external-id'] = $vars['external_id'];
        }

        // Add the gen_setup section
        $this->insert($gen_info, $container);
    }
}
