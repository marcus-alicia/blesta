<?php
/**
 * Plesk Site management
 *
 * From API RPC version 1.6.3.0 and greater
 *
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @package plesk.commands
 */
class PleskSites extends PleskPacket
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
    private $base_container = '/site';

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
        $this->insert(['site' => null]);
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
     *  - domain The domain name
     *  - htype The hosting type (e.g. virtual hosting, standard forwarding,
     *      frame forwarding, or none) (optional, default vrt_hst, one of:)
     *      - vrt_hst
     *      - std_fwd
     *      - frm_fwd
     *      - none
     *  - status The status of the site (optional, default 0, one of:)
     *      - 0 Active
     *      - 16 Disabled by Plesk Administrator
     *      - 32 Disabled by Plesk reseller
     *      - 64 Disabled by a customer
     *  - webspace An array of optional attributes specifying the webspace to assign this site to:
     *      - name The name of the subscription to which the Panel will assign a new site
     *      - id The ID of a subscription to which the Panel will assign a new site
     *      - guid The GUID of a subscription to which the Panel will assign a new site
     *  - parent An array of optional attributes specifying the parent domain to assign this site to:
     *      - site_id The ID of a parent domain to which the Panel will assign a new site, which will be a subdomain
     *      - site_name The name of a parent domain to which the Panel will assign a new site, which will be a subdomain
     *      - site_guid The GUID of a parent domain to which the Panel will assign a new site, which will be a subdomain
     *  - hosting An array of hosting properties and IP addresses (optional, required if htype is not 'none')
     *      - properties A key/value array of each property name and its value (optional)
     *      - dest_url The URL to which the user will be redirected implicitly at the attempt to visit the
     *          specified site (optional, required if htype is 'std_fwd' or 'frm_fwd')
     *      - http_code The HTTP code for rediction when you use standard forwarding (optional, for 'std_fwd' only)
     *  @return PleskResponse
     */
    public function add(array $vars)
    {
        // Set the container for this API request
        $this->insert(['add' => null], $this->getContainer());
        $this->setContainer($this->base_container . '/add');

        // Build gen_setup section
        $this->buildGenSetup($vars);

        // Build hosting section
        if (isset($vars['htype'])) {
            $hosting = array_merge((isset($vars['hosting']) ? $vars['hosting'] : []), ['htype' => $vars['htype']]);
            $this->buildHosting($hosting, $this->getContainer());
        }

        #
        # TODO: add support for prefs
        #

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
     *  - properties A key/value array of each property name and its value (optional, required for htype of 'vrt_hst')
     *  - dest_url The URL to which the user will be redirected implicitly at the attempt to
     *      visit the specified site (optional, required if htype is 'std_fwd' or 'frm_fwd')
     *  - http_code The HTTP code for rediction when you use standard
     *      forwarding (optional, for 'std_fwd' only)
     * @param string $container The XML container to set the hosting section
     *      into (optional, defaults to the current container)
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

            // Set the HTTP code, if any, for 'std_fwd'
            if ($vars['htype'] == 'std_fwd' && isset($vars['http_code'])) {
                $this->insert(['http_code' => $vars['http_code']], $container . '/hosting/std_fwd');
            }
        } else {
            // None
            $this->insert(['none' => null], $container . '/hosting');
        }
    }

    /**
     * Builds the gen_setup section of the XML packet
     *
     * @see PleskSubscriptions::add(), @see PleskSubscriptions::set()
     * @param array $vars A list of input vars including:
     *  - domain The domain name
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
     *  - webspace An array of optional attributes specifying the webspace to assign this site to:
     *      - name The name of the subscription to which the Panel will assign a new site
     *      - id The ID of a subscription to which the Panel will assign a new site
     *      - guid The GUID of a subscription to which the Panel will assign a new site
     *  - parent An array of optional attributes specifying the parent domain to assign this site to:
     *      - site_id The ID of a parent domain to which the Panel will assign a new site, which will be a subdomain
     *      - site_name The name of a parent domain to which the Panel will assign a new site, which will be a subdomain
     *      - site_guid The GUID of a parent domain to which the Panel will assign a new site, which will be a subdomain
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
                'name' => (isset($vars['domain']) ? $vars['domain'] : null),
                'htype' => 'vrt_hst',
                'status' => '0'
            ];

            // Set hosting type
            if (isset($vars['htype']) && in_array($vars['htype'], ['vrt_hst', 'std_fwd', 'frm_fwd', 'none'])) {
                $gen_setup['gen_setup']['htype'] = $vars['htype'];
            }

            // Set webspace info
            if (!empty($vars['webspace'])) {
                if (isset($vars['webspace']['name'])) {
                    $gen_setup['gen_setup']['webspace-name'] = $vars['webspace']['name'];
                }
                if (isset($vars['webspace']['id'])) {
                    $gen_setup['gen_setup']['webspace-id'] = $vars['webspace']['id'];
                }
                if (isset($vars['webspace']['guid'])) {
                    $gen_setup['gen_setup']['webspace-guid'] = $vars['webspace']['guid'];
                }
            }

            // Set parent info
            if (!empty($vars['parent'])) {
                if (isset($vars['parent']['site_id'])) {
                    $gen_setup['gen_setup']['parent-site-id'] = $vars['parent']['site_id'];
                }
                if (isset($vars['parent']['name'])) {
                    $gen_setup['gen_setup']['parent-site-name'] = $vars['parent']['site_name'];
                }
                if (isset($vars['parent']['guid'])) {
                    $gen_setup['gen_setup']['parent-site-guid'] = $vars['parent']['site_guid'];
                }
            }
        } else {
            // Set the container
            #
            # TODO: add support for other types (get/set) when those methods are implemented
            #
        }

        // Set optional fields
        if (isset($vars['status']) && in_array($vars['status'], ['0', '16', '32', '64'])) {
            $gen_setup['gen_setup']['status'] = $vars['status'];
        }

        // Add the gen_setup section
        $this->insert($gen_setup, $container);
    }
}
