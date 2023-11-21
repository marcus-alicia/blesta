<?php
/**
 * Plesk Subscription management
 *
 * From API RPC version 1.6.3.0 and greater
 *
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @package plesk.commands
 */
class PleskSubscriptions extends PleskPacket
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
    private $base_container = '/webspace';

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
        $this->insert(['webspace' => null]);
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
     * Creates a new webspace subscription
     *
     * @param array $vars A list of input vars including:
     *  - general An array of general information including:
     *      - name The domain name
     *      - ip_address The host IP address
     *      - owner_id The Plesk user (subscriptions owner) ID (optional, will be used if owner_login is also specified)
     *      - owner_login The Plesk user login name (optional)
     *      - owner_guid The Plesk user GUID (optional)
     *      - owner_external_id The ID of a Panel user in other components or applications (optional)
     *      - htype The hosting type (e.g. virtual hosting, standard forwarding, frame forwarding, or none)
     *          (optional, default vrt_hst, one of:)
     *          - vrt_hst
     *          - std_fwd
     *          - frm_fwd
     *          - none
     *      - status The status of the site (optional, default 0, one of:)
     *          - 0 Active
     *          - 16 Disabled by Plesk Administrator
     *          - 32 Disabled by Plesk reseller
     *          - 64 Disabled by a customer
     *      - external_id Specifies a GUID of a subscription owner received from the Panel components (optional)
     *  - hosting An array of hosting properties and IP addresses (optional, required if general[htype] is not 'none')
     *      - properties A key/value array of each property name and its value (optional)
     *      - ipv4 The IPv4 address (optional, required if ipv6 is not given)
     *      - ipv6 The IPv6 address (optional, required if ipv4 is not given) - only available
     *          with API RPC v1.6.3.2 or greater
     *  - plan An array of plan information (all optional) including:
     *      - id The ID of the service plan if necessary to create the subscription to a service plan
     *          (optional, takes precedence over plan_name if both are given)
     *      - name The service plan name if it is necessary to create a subscription to a certain
     *          service plan (optional)
     *      - guid The service plan GUID if it is necessary to create a subscription to a certain
     *          service plan (optional)
     *      - external_id The ID of the service plan in the Panel components (optional)
     *  @return PleskResponse
     */
    public function add(array $vars)
    {
        // Set the container for this API request
        $this->insert(['add' => null], $this->getContainer());
        $this->setContainer($this->base_container . '/add');

        // Build gen_setup section
        $general = (isset($vars['general']) ? $vars['general'] : []);
        $this->buildGenSetup($general);

        // Build hosting section
        if (!(isset($vars['general']['htype']) && $vars['general']['htype'] == 'none')) {
            $htype = (isset($vars['general']['htype']) ? $vars['general']['htype'] : 'vrt_hst');
            $hosting = array_merge((isset($vars['hosting']) ? $vars['hosting'] : []), ['htype' => $htype]);
            $this->buildHosting($hosting, $this->getContainer());
        }

        #
        # TODO: add support for limits, prefs, performance, permissions, php-settings
        #

        // Specify the plan information
        if (isset($vars['plan']['id'])) {
            $this->insert(['plan-id' => $vars['plan']['id']], $this->getContainer());
        } elseif (isset($vars['plan']['name'])) {
            $this->insert(['plan-name' => $vars['plan']['name']], $this->getContainer());
        }

        if (isset($vars['plan']['guid'])) {
            $this->insert(['plan-guid' => $vars['plan']['guid']], $this->getContainer());
        }
        if (isset($vars['plan']['external_id'])) {
            $this->insert(['plan-external-id' => $vars['plan']['external_id']], $this->getContainer());
        }

        return $this->api->submit($this->fetch(), $this->getContainer());
    }

    /**
     * Deletes a subscription
     *
     * @param array $vars A list of subscriptions to delete, including:
     *  - ids An array of subscription IDs to delete (optional, used in preference over names)
     *  - names An array of subscription names to delete (optional)
     * @return PleskResponse
     */
    public function delete(array $vars)
    {
        // Set the container for this API request
        $this->insert(['del' => null], $this->getContainer());
        $this->setContainer($this->base_container . '/del');

        $ids = (isset($vars['ids']) ? $vars['ids'] : []);
        $names = (isset($vars['names']) ? $vars['names'] : []);

        // Add the subscription IDs or names to delete
        $this->insert(['filter' => null], $this->getContainer());
        if (!empty($ids)) {
            foreach ($ids as $id) {
                $this->insert(['id' => $id], $this->getContainer() . '/filter');
            }
        } elseif (!empty($names)) {
            foreach ($names as $name) {
                $this->insert(['name' => $name], $this->getContainer() . '/filter');
            }
        }

        return $this->api->submit($this->fetch(), $this->getContainer());
    }

    /**
     * Retrieves the service plans and information
     * An owner MUST be specified unless the API version is 1.6.3.4 or greater.
     * 1.6.3.4 allows no owner to be specified, which will receive plans from everyone
     *
     * @param array $vars An array of input params including:
     *  - id The subscription ID (optional)
     *  - owner_id The Plesk user (subscriptions owner) ID (optional, will be used if owner_login is also specified)
     *  - name The subscription name. Hosting subscriptions should provide the site name (optional)
     *  - owner_login The Plesk user login name (optional)
     *  - guid The GUID of a subscription (optional)
     *  - owner_guid The Plesk subscription owner GUID (optional)
     *  - owner_external_id The ID of a subscription owner account in the Panel component (optional)
     *  - external_id Specifies a GUID of a subscription in the Panel component (optional)
     *  - settings An array of settings you would like to include (all optional)
     *      - hosting Set to true to fetch hosting information
     *      - limits Set to true to fetch limits information
     *      - stat Set to true to fetch statistics information
     *      - prefs Set to true to fetch preferences information
     *      - disk_usage Set to true to fetch disk usage information
     *      - performance Set to true to fetch performance information
     *      - subscriptions Set to true to fetch subscriptions information
     *      - permissions Set to true to fetch permissions information
     *      - plan-items Set to true to fetch plan items information
     *      - php-settings Set to true to fetch php settings information
     * @return PleskResponse
     */
    public function get(array $vars = [])
    {
        // Set the container for this API request
        $this->insert(['get' => null], $this->getContainer());
        $this->setContainer($this->base_container . '/get');

        // Build the filter section
        $this->buildFilter($vars);

        // Set the dataset section
        $this->insert(['dataset' => null], $this->getContainer());

        // Always fetch general information
        $vars['settings'] = (isset($vars['settings']) ? $vars['settings'] : []);
        $vars['settings'] = array_merge($vars['settings'], ['gen_info' => true]);

        // Set any of the settings to fetch
        $options = ['gen_info', 'hosting', 'limits', 'stat', 'prefs', 'disk_usage',
            'performance', 'subscriptions', 'permissions', 'plan-items', 'php-settings'];

        foreach ($options as $option) {
            if (array_key_exists($option, $vars['settings']) && $vars['settings'][$option]) {
                $this->insert([$option => null], $this->getContainer() . '/dataset');
            }
        }

        return $this->api->submit($this->fetch(), $this->getContainer());
    }

    /**
     * Sets subscription paramaters for a given subscription
     *
     * @param array $vars A list of input params including the following:
     *  - filter An array of fields to filter the request on:
     *      - id The subscription/webspace ID (optional)
     *      - owner_id The ID of the owner/reseller that the subscription belong to,
     *          or that you wish to fetch (optional)
     *      - owner_login The login name of the owner/reseller that the subscription belong to,
     *          or that you wish to fetch (optional)
     *      - name The subscription name (optional)
     *      - guid The GUID of a subscription (optional)
     *      - owner_guid The GUID of a subscription owner/reseller (optional)
     *      - external_id The GUID of a subscription in the Panel component (optional)
     *      - owner_external_id The GUID of a subscription owner account in the Panel component (optional)
     *  - general An array of general fields to set (optional):
     *      - name The name of the subscription
     *      - owner_id The ID of the new subscription owner
     *      - owner_login The login name of the new subscription owner
     *      - owner_guid The GUID of the new subscription
     *      - owner_external_id The GUID of a subscription owner account in the Panel component
     *      - ip_address The IP address associated with the subscription
     *      - guid The new GUID to be assigned to the subscription
     *      - admin_as_vendor True to set whether the administrator is the provider for the subscription,
     *          false otherwise (optional, default false)
     *      - status The status of the site (optional, one of:)
     *          - 0 Active
     *          - 16 Disabled by Plesk Administrator
     *          - 32 Disabled by Plesk reseller
     *          - 64 Disabled by a customer
     *  - hosting An array of hosting properties and IP addresses (optional, only used if htype is given and not 'none')
     *      - properties A key/value array of each property name and its value (optional)
     *      - dest_url The URL to which the user will be redirected implicitly at the attempt to
     *          visit the specified site (optional, required if htype is 'std_fwd' or 'frm_fwd')
     *      - htype The hosting type (e.g. virtual hosting, standard forwarding, frame forwarding) one of:
     *          - vrt_hst
     *          - std_fwd
     *          - frm_fwd
     *      - ipv4 The IPv4 address (optional, required if ipv6 is not given)
     *      - ipv6 The IPv6 address (optional, required if ipv4 is not given) - only available
     *          with API RPC v1.6.3.2 or greater
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

        // Build the values container
        $this->insert(['values' => null], $this->getContainer());

        // Build gen_setup section in the values section
        $general = (isset($vars['general']) ? $vars['general'] : []);
        $general = array_merge(
            $general,
            (isset($vars['hosting']['htype']) ? ['htype' => $vars['hosting']['htype']] : [])
        );
        $this->buildGenSetup($general, 'set');

        #
        # TODO: build limits, prefs sections
        #

        // Build the hosting section in the values section
        if (!empty($vars['hosting']) && isset($vars['hosting']['htype'])
            && in_array($vars['hosting']['htype'], ['vrt_hst', 'std_fwd', 'frm_fwd'])
        ) {
            $this->buildHosting($vars['hosting'], $this->getContainer() . '/values');
        }

        #
        # TODO: build disk_usage, performance, permissions, and php-settings sections
        #

        return $this->api->submit($this->fetch(), $this->getContainer());
    }

    /**
     * Adds a new plan to an existing subscription
     *
     * @param array $vars An array of input params including:
     *  - filter An array of fields to filter the request on:
     *      - id The subscription/webspace ID (optional)
     *      - owner_id The ID of the owner/reseller that the subscription belong to,
     *          or that you wish to fetch (optional)
     *      - owner_login The login name of the owner/reseller that the subscription belong to,
     *          or that you wish to fetch (optional)
     *      - name The subscription name (optional)
     *      - guid The GUID of a subscription (optional)
     *      - owner_guid The GUID of a subscription owner/reseller (optional)
     *      - external_id The GUID of a subscription in the Panel component (optional)
     *      - owner_external_id The GUID of a subscription owner account in the Panel component (optional)
     *  - plan The name of the plan to add
     * @return PleskResponse
     */
    public function addPlan(array $vars)
    {
        // Set the container for this API request
        $this->insert(['add-plan-item' => null], $this->getContainer());
        $this->setContainer($this->base_container . '/add-plan-item');

        // Build the filter section
        $filter = (isset($vars['filter']) ? $vars['filter'] : []);
        $this->buildFilter($filter);

        // Set the plan-item section
        $this->insert(['plan-item' => null], $this->getContainer());

        // Add the plan to the plan-item section
        if (!empty($vars['plan'])) {
            $this->insert(['name' => $vars['plan']], $this->getContainer() . '/plan-item');
        }

        return $this->api->submit($this->fetch(), $this->getContainer());
    }

    /**
     * Removes a plan from an existing subscription
     *
     * @param array $vars An array of input params including:
     *  - filter An array of fields to filter the request on:
     *      - id The subscription/webspace ID (optional)
     *      - owner_id The ID of the owner/reseller that the subscription belong to,
     *          or that you wish to fetch (optional)
     *      - owner_login The login name of the owner/reseller that the subscription belong to,
     *          or that you wish to fetch (optional)
     *      - name The subscription name (optional)
     *      - guid The GUID of a subscription (optional)
     *      - owner_guid The GUID of a subscription owner/reseller (optional)
     *      - external_id The GUID of a subscription in the Panel component (optional)
     *      - owner_external_id The GUID of a subscription owner account in the Panel component (optional)
     *  - plan The name of the plan to remove
     * @return PleskResponse
     */
    public function removePlan(array $vars)
    {
        // Set the container for this API request
        $this->insert(['remove-plan-item' => null], $this->getContainer());
        $this->setContainer($this->base_container . '/remove-plan-item');

        // Build the filter section
        $filter = (isset($vars['filter']) ? $vars['filter'] : []);
        $this->buildFilter($filter);

        // Set the plan-item section
        $this->insert(['plan-item' => null], $this->getContainer());

        // Add the plan to the plan-item section
        if (!empty($vars['plan'])) {
            $this->insert(['name' => $vars['plan']], $this->getContainer() . '/plan-item');
        }

        return $this->api->submit($this->fetch(), $this->getContainer());
    }

    /**
     * Changes a plan for a subscription
     *
     * @param array $vars An array of input params including:
     *  - filter An array of fields to filter the request on:
     *      - id The subscription/webspace ID (optional)
     *      - owner_id The ID of the owner/reseller that the subscription belong to,
     *          or that you wish to fetch (optional)
     *      - owner_login The login name of the owner/reseller that the subscription belong to,
     *          or that you wish to fetch (optional)
     *      - name The subscription name (optional)
     *      - guid The GUID of a subscription (optional)
     *      - owner_guid The GUID of a subscription owner/reseller (optional)
     *      - external_id The GUID of a subscription in the Panel component (optional)
     *      - owner_external_id The GUID of a subscription owner account in the Panel component (optional)
     *  - plan An array containing ONE of the following to identify the plan to change to:
     *      - guid The plan GUID
     *      - external_id The ID of the service plan in the Panel components
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
            if (isset($vars['plan']['external_id'])) {
                $this->insert(['plan-external-id' => $vars['plan']['external_id']], $this->getContainer());
            }
            if (isset($vars['plan']['no_plan']) && $vars['plan']['no_plan']) {
                $this->insert(['no-plan' => null], $this->getContainer());
            }
        }

        return $this->api->submit($this->fetch(), $this->getContainer());
    }

    /**
     * Builds the hosting section of the XML packet
     *
     * @see PleskSubscriptions::add()
     * @param array $vars A list of input vars including:
     *  - htype The hosting type (e.g. virtual hosting, standard forwarding, frame forwarding, or none) (one of:)
     *      - vrt_hst
     *      - std_fwd
     *      - frm_fwd
     *  - properties A key/value array of each property name and its value
     *      (optional, required for htype of 'vrt_hst')
     *  - dest_url The URL to which the user will be redirected implicitly at the attempt to
     *      visit the specified site (optional, required if htype is 'std_fwd' or 'frm_fwd')
     *  - ipv4 The IPv4 address (optional, required if ipv6 is not given)
     *  - ipv6 The IPv6 address (optional, required if ipv4 is not given)
     * @param string $container The XML container to set the hosting section into
     *      (optional, defaults to the current container)
     */
    private function buildHosting(array $vars, $container)
    {
        $container = (!empty($container) ? $container : $this->getContainer());

        $this->insert(['hosting' => null], $container);

        // Build properties
        if ($vars['htype'] == 'vrt_hst') {
            // Set the vrt_host section
            $this->insert(['vrt_hst' => null], $container . '/hosting');

            // Add properties
            if (isset($vars['properties'])) {
                foreach ($vars['properties'] as $key => $value) {
                    $property = ['property' => ['name' => $key, 'value' => $value]];
                    $this->insert($property, $container . '/hosting/vrt_hst');
                }
            }
        } elseif (in_array($vars['htype'], ['std_fwd', 'frm_fwd'])) {
            // Set the type and dest_url
            $this->insert(
                [$vars['htype'] => ['dest_url' => (isset($vars['dest_url']) ? $vars['dest_url'] : '')]],
                $container . '/hosting'
            );
        } else {
            // None
            $this->insert(['none' => null], $container . '/hosting');
        }

        // Build IP addresses
        if (isset($vars['ipv4'])) {
            $this->insert(['ip_address' => $vars['ipv4']], $container . '/hosting/' . $vars['htype']);
        }
        if (isset($vars['ipv6'])) {
            $this->insert(['ip_address' => $vars['ipv6']], $container . '/hosting/' . $vars['htype']);
        }
    }

    /**
     * Builds the gen_setup section of the XML packet
     *
     * @see PleskSubscriptions::add(), @see PleskSubscriptions::set()
     * @param array $vars A list of input vars including:
     *  - name The domain name
     *  - ip_address The host IP address
     *  - owner_id The Plesk user (subscriptions owner) ID (optional, will be used if owner_login is also specified)
     *  - owner_login The Plesk user login name (optional)
     *  - owner_guid The Plesk user GUID (optional)
     *  - owner_external_id The ID of a Panel user in other components or applications (optional)
     *  - htype The hosting type (e.g. virtual hosting, standard forwarding, frame forwarding, or none)
     *      (optional, only for "add" type, default vrt_hst, one of:)
     *      - vrt_hst
     *      - std_fwd
     *      - frm_fwd
     *      - none
     *  - status The status of the site (optional, default 0, one of:)
     *      - 0 Active
     *      - 16 Disabled by Plesk Administrator
     *      - 32 Disabled by Plesk reseller
     *      - 64 Disabled by a customer
     *  - external_id Specifies a GUID of a subscription owner received from the Panel components (optional)
     *  - admin_as_vendor True to set whether the administrator is the provider for the subscription,
     *      false otherwise (optional, only for the "set" type)
     * @param string $type The type of subscription call ("add" or "set") (optional, default "add")
     */
    private function buildGenSetup(array $vars, $type = 'add')
    {
        $gen_setup = ['gen_setup' => []];
        $container = $this->getContainer();

        // Set gen setup for the add API call
        if ($type == 'add') {
            // Set the gen_setup section
            $gen_setup['gen_setup'] = [
                'name' => (isset($vars['name']) ? $vars['name'] : null),
                'ip_address' => (isset($vars['ip_address']) ? $vars['ip_address'] : null),
                'htype' => 'vrt_hst',
                'status' => '0'
            ];

            // Set hosting type
            if (isset($vars['htype']) && in_array($vars['htype'], ['vrt_hst', 'std_fwd', 'frm_fwd', 'none'])) {
                $gen_setup['gen_setup']['htype'] = $vars['htype'];
            }
        } else {
            // Set the container
            $container = $this->getContainer() . '/values';

            // Set gen setup for the set API call
            if (isset($vars['name'])) {
                $gen_setup['gen_setup']['name'] = $vars['name'];
            }
            if (isset($vars['guid'])) {
                $gen_setup['gen_setup']['guid'] = $vars['guid'];
            }
            if (isset($vars['ip_address'])) {
                $gen_setup['gen_setup']['ip_address'] = $vars['ip_address'];
            }
            if (isset($vars['admin_as_vendor']) && $vars['admin_as_vendor']) {
                $gen_setup['gen_setup']['admin-as-vendor'] = null;
            }
        }

        // Set optional fields
        if (isset($vars['owner_id'])) {
            $gen_setup['gen_setup']['owner-id'] = $vars['owner_id'];
        } elseif (isset($vars['owner_login'])) {
            $gen_setup['gen_setup']['owner-login'] = $vars['owner_login'];
        }

        if (isset($vars['owner_guid'])) {
            $gen_setup['gen_setup']['owner-guid'] = $vars['owner_guid'];
        }
        if (isset($vars['owner_external_id'])) {
            $gen_setup['gen_setup']['owner-external-id'] = $vars['owner_external_id'];
        }
        if (isset($vars['status']) && in_array($vars['status'], ['0', '16', '32', '64'])) {
            $gen_setup['gen_setup']['status'] = $vars['status'];
        }
        if (isset($vars['external_id'])) {
            $gen_setup['gen_setup']['external-id'] = $vars['external_id'];
        }

        // Add the gen_setup section
        $this->insert($gen_setup, $container);
    }

    /**
     * Builds the filter section of the XML packet
     *
     * @see PleskSubscriptions::set()
     * @param array $vars A list of input vars including (all optional):
     *  - id The subscription ID (optional)
     *  - owner_id The Plesk user (subscriptions owner) ID (optional, will be used if owner_login is also specified)
     *  - name The subscription name. Hosting subscriptions should provide the site name (optional)
     *  - owner_login The Plesk user login name (optional)
     *  - guid The GUID of a subscription (optional)
     *  - owner_guid The Plesk subscription owner GUID (optional)
     *  - owner_external_id The ID of a subscription owner account in the Panel component (optional)
     *  - external_id Specifies a GUID of a subscription in the Panel component (optional)
     */
    private function buildFilter(array $vars = [])
    {
        $filter = ['filter' => []];

        // Set optional fields
        if (isset($vars['id'])) {
            $filter['filter']['id'] = $vars['id'];
        }
        if (isset($vars['name'])) {
            $filter['filter']['name'] = $vars['name'];
        }

        if (isset($vars['owner_id'])) {
            $filter['filter']['owner-id'] = $vars['owner_id'];
        } elseif (isset($vars['owner_login'])) {
            $filter['filter']['owner-login'] = $vars['owner_login'];
        }

        if (isset($vars['guid'])) {
            $filter['filter']['guid'] = $vars['guid'];
        }
        if (isset($vars['owner_guid'])) {
            $filter['filter']['owner-guid'] = $vars['owner_guid'];
        }
        if (isset($vars['owner_external_id'])) {
            $filter['filter']['owner-external-id'] = $vars['owner_external_id'];
        }
        if (isset($vars['external_id'])) {
            $filter['filter']['external-id'] = $vars['external_id'];
        }

        $this->insert($filter, $this->getContainer());
    }
}
