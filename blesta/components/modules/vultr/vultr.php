<?php
use Blesta\Core\Util\Validate\Server;
/**
 * Vultr Module.
 *
 * @package blesta
 * @subpackage blesta.components.modules.vultr
 * @copyright Copyright (c) 2018, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Vultr extends Module
{
    /**
     * Initializes the module.
     */
    public function __construct()
    {
        // Load components required by this module
        Loader::loadComponents($this, ['Input']);

        // Load the language required by this module
        Language::loadLang('vultr', null, dirname(__FILE__) . DS . 'language' . DS);

        // Load config
        Configure::load('vultr', dirname(__FILE__) . DS . 'config' . DS);

        // Load module config
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');
    }

    /**
     * Performs migration of data from $current_version (the current installed version)
     * to the given file set version. Sets Input errors on failure, preventing
     * the module from being upgraded.
     *
     * @param string $current_version The current installed version of this module
     */
    public function upgrade($current_version)
    {
        // Upgrade to 2.0.0
        if (version_compare($current_version, '2.0.0', '<')) {
            if (!isset($this->ModuleManager)) {
                Loader::loadModels($this, ['ModuleManager']);
            }
            if (!isset($this->Services)) {
                Loader::loadModels($this, ['Services']);
            }
            if (!isset($this->Packages)) {
                Loader::loadModels($this, ['Packages']);
            }

            // Update services and packages to the v2 API
            $modules = $this->ModuleManager->getByClass('vultr');
            foreach ($modules as $module) {
                $services = array_merge(
                    $this->Services->getAll(
                        ['date_added' => 'DESC'],
                        true,
                        ['module_id' => $module->id, 'status' => 'active']
                    ),
                    $this->Services->getAll(
                        ['date_added' => 'DESC'],
                        true,
                        ['module_id' => $module->id, 'status' => 'suspended']
                    )
                );
                $packages = $this->Packages->getAll(
                    Configure::get('Blesta.company_id'),
                    ['name' => 'ASC'],
                    null,
                    null,
                    ['module_id' => $module->id]
                );

                // Update services
                foreach ($services as $service) {
                    // Get the service fields
                    $service_fields = $this->serviceFieldsToObject($service->fields);

                    // Get remote service
                    $remote_service = $this->getRemoteService($service);
                    if (isset($remote_service->id)) {
                        $service_fields->vultr_subid = $remote_service->id;
                        $service_fields->vultr_location = $remote_service->region;
                    }

                    // Get remote snapshots
                    $service_fields->vultr_snapshots = $this->getRemoteSnapshots($service);

                    // Update service
                    $this->Services->edit($service->id, (array) $service_fields);
                    if (($errors = $this->Services->errors())) {
                        $this->Input->setErrors($errors);
                    }

                    if ($this->Input->errors()) {
                        return;
                    }
                }

                // Update packages
                $plans_map = [
                    'SSD' => 'vc2',
                    'HIGHFREQUENCY' => 'vhf',
                    'AMDHIGHPERF' => 'vhp',
                    'INTELHIGHPERF' => 'vhp',
                    'DEDICATEDOPTIMIZED' => 'vdc',
                    'CLOUDGPU' => 'vcg'
                ];
                foreach ($packages as $package) {
                    $package = $this->Packages->get($package->id);

                    // Initialize the Vultr API
                    $row = $this->getModuleRow($package->module_row);
                    $api = $this->getApi($row->meta->api_key);

                    if ($package->meta->server_type == 'server') {
                        $legacy_plans = (array) $api->legacyRequest('/plans/list');

                        if (isset($legacy_plans[$package->meta->server_plan])) {
                            $legacy_plan = (object) $legacy_plans[$package->meta->server_plan];

                            $package->meta->baremetal_plan = '';
                            $package->meta->server_plan = $plans_map[$legacy_plan->plan_type] . '-'
                                . $legacy_plan->vcpu_count . 'c-' . round($legacy_plan->ram / 1024) . 'gb'
                                . (str_contains(strtolower($legacy_plan->plan_type), 'intel') ? '-intel' : '')
                                . (str_contains(strtolower($legacy_plan->plan_type), 'amd') ? '-amd' : '');
                        }
                    } else {
                        $legacy_plans = (array) $api->legacyRequest('/plans/list_baremetal');

                        if (isset($legacy_plans[$package->meta->baremetal_plan])) {
                            $legacy_plan = (object) $legacy_plans[$package->meta->baremetal_plan];

                            $package->meta->baremetal_plan = 'vbm-' . $legacy_plan->cpu_count . 'c-'
                                . round($legacy_plan->ram / 1024) . 'gb'
                                . (str_contains(strtolower($legacy_plan->cpu_model), 'epyc') ? '-amd' : '');
                            $package->meta->server_plan = '';
                        }
                    }

                    $this->Packages->edit($package->id, ['meta' => (array) $package->meta]);
                    if (($errors = $this->Packages->errors())) {
                        $this->Input->setErrors($errors);
                    }

                    if ($this->Input->errors()) {
                        return;
                    }
                }
            }
        }
    }

    /**
     * Fetches the remote server from Vultr based on the given service object
     *
     * @param stdClass $service The object representing the service
     * @return stdClass The remote service
     */
    private function getRemoteService($service)
    {
        if (!isset($service->package->meta->server_type)) {
            return (object) [];
        }

        // Get module row
        $row = $this->getModuleRow($service->module_row_id ?? null);

        // Initialize the Vultr API
        $api = $this->getApi($row->meta->api_key);
        $api->loadCommand('vultr_instances');
        $api->loadCommand('vultr_baremetal');

        // Get the service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Get remote service by SUBID (until June 30th, 2023)
        $cache = Cache::fetchCache(
            $service->package->meta->server_type . '_subids',
            Configure::get('Blesta.company_id') . DS . 'modules' . DS . 'vultr' . DS
        );
        if ($cache) {
            $subids = unserialize(base64_decode($cache));
        }

        if (empty($subids)) {
            if ($service->package->meta->server_type == 'server') {
                $subids = (array) $api->legacyRequest('/server/list');
            } else {
                $subids = (array) $api->legacyRequest('/baremetal/list');
            }

            if (Configure::get('Caching.on') && is_writable(CACHEDIR) && !empty($subids)) {
                try {
                    Cache::writeCache(
                        $service->package->meta->server_type . '_subids',
                        base64_encode(serialize($subids)),
                        strtotime(Configure::get('Blesta.cache_length')) - time(),
                        Configure::get('Blesta.company_id') . DS . 'modules' . DS . 'vultr' . DS
                    );
                } catch (Exception $e) {
                    // Write to cache failed, so disable caching
                    Configure::set('Caching.on', false);
                }
            }
        }

        if (isset($subids[$service_fields->vultr_subid])) {
            if ($service->package->meta->server_type == 'server') {
                $vultr_api = new VultrInstances($api);
                $instance = $vultr_api->get([
                    'instance-id' => $subids[$service_fields->vultr_subid]->v2_id ?? ''
                ])->response();
            } else {
                $vultr_api = new VultrBaremetal($api);
                $instance = $vultr_api->get([
                    'baremetal-id' => $subids[$service_fields->vultr_subid]->v2_id ?? ''
                ])->response();
            }
        }

        // Get remote service, by matching the hostname and OS
        if (empty($instance->instance)) {
            if ($service->package->meta->server_type == 'server') {
                $vultr_api = new VultrInstances($api);
                $response = $vultr_api->listServers(['label' => $service_fields->vultr_hostname])->response();

                foreach ($response->instances ?? [] as $remote_instance) {
                    $os_template = explode('-', $service_fields->vultr_template, 2);

                    if (
                        $remote_instance->hostname == $service_fields->vultr_hostname
                        && (
                            ($remote_instance->os_id == ($os_template[1] ?? '') && ($os_template[0] ?? '') == 'os')
                            || ($remote_instance->app_id == ($os_template[1] ?? '') && ($os_template[0] ?? '') == 'app')
                        )
                    ){
                        $remote_service = $remote_instance;
                        break;
                    }
                }
            } else {
                $vultr_api = new VultrBaremetal($api);
                $response = $vultr_api->listBaremetal()->response();

                foreach ($response->instances ?? [] as $remote_instance) {
                    $os_template = explode('-', $service_fields->vultr_template, 2);

                    if (
                        $remote_instance->label == $service_fields->vultr_hostname
                        && (
                            ($remote_instance->os_id == ($os_template[1] ?? '') && ($os_template[0] ?? '') == 'os')
                            || ($remote_instance->app_id == ($os_template[1] ?? '') && ($os_template[0] ?? '') == 'app')
                        )
                    ){
                        $remote_service = $remote_instance;
                        break;
                    }
                }
            }
        } else {
            $remote_service = $instance->instance;
        }

        return $remote_service ?? (object) [];
    }

    /**
     * Fetches the remote snapshots from Vultr based on the given service object
     *
     * @param stdClass $service The object representing the service
     * @return array An array containing the service snapshots
     */
    private function getRemoteSnapshots($service)
    {
        // Get module row
        $row = $this->getModuleRow($service->module_row_id ?? null);

        // Initialize the Vultr API
        $api = $this->getApi($row->meta->api_key);

        // Get the service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);

        if (empty($service_fields->vultr_snapshots)) {
            return [];
        }

        // Get remote snapshots
        $snapshots = [];
        $remote_snapshots = (array) $api->legacyRequest('/snapshot/list');
        foreach ($service_fields->vultr_snapshots as $snapshot_id => $snapshot_description) {
            if (isset($remote_snapshots[$snapshot_id])) {
                $snapshots[$remote_snapshots[$snapshot_id]->v2_id] = $snapshot_description;
            }
        }

        return $snapshots;
    }

    /**
     * Returns all tabs to display to an admin when managing a service whose
     * package uses this module.
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @return array An array of tabs in the format of method => title.
     *  Example: array('methodName' => "Title", 'methodName2' => "Title2")
     */
    public function getAdminTabs($package)
    {
        $tabs = [
            'tabActions' => Language::_('Vultr.tab_actions', true),
            'tabStats' => Language::_('Vultr.tab_stats', true),
            'tabSnapshots' => Language::_('Vultr.tab_snapshots', true),
            'tabBackups' => Language::_('Vultr.tab_backups', true)
        ];

        if ($package->meta->server_type == 'baremetal') {
            unset($tabs['tabSnapshots']);
            unset($tabs['tabBackups']);
        }

        return $tabs;
    }

    /**
     * Returns all tabs to display to a client when managing a service whose
     * package uses this module.
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @return array An array of tabs in the format of method => title.
     *  Example: array('methodName' => "Title", 'methodName2' => "Title2")
     */
    public function getClientTabs($package)
    {
        $tabs = [
            'tabClientActions' => Language::_('Vultr.tab_client_actions', true),
            'tabClientStats' => Language::_('Vultr.tab_client_stats', true),
            'tabClientSnapshots' => Language::_('Vultr.tab_client_snapshots', true),
            'tabClientBackups' => Language::_('Vultr.tab_client_backups', true),
        ];

        if ($package->meta->server_type == 'baremetal') {
            unset($tabs['tabSnapshots']);
            unset($tabs['tabBackups']);
        }

        return $tabs;
    }

    /**
     * Returns all fields used when adding/editing a package, including any
     * javascript to execute when the page is rendered with these fields.
     *
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containing the fields to
     *  render as well as any additional HTML markup to include
     */
    public function getPackageFields($vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        $fields = new ModuleFields();

        $fields->setHtml("
            <script type=\"text/javascript\">
                $(document).ready(function() {
                    // Set whether to show or hide the plans option, depending on the server type
                    $('#vultr_baremetal_plan').closest('li').hide();
                    $('#vultr_instances_plan').closest('li').hide();

                    if ($('#vultr_instances_type').val() == 'server') {
                        $('#vultr_instances_plan').closest('li').show();
                        $('#vultr_baremetal_plan').closest('li').hide();
                    } else {
                        $('#vultr_baremetal_plan').closest('li').show();
                        $('#vultr_instances_plan').closest('li').hide();
                    }

                    $('#vultr_instances_type').change(function() {
                        if ($(this).val() == 'server') {
                            $('#vultr_instances_plan').closest('li').show();
                            $('#vultr_baremetal_plan').closest('li').hide();
                        } else {
                            $('#vultr_baremetal_plan').closest('li').show();
                            $('#vultr_instances_plan').closest('li').hide();
                        }
                    });

                    // Set whether to show or hide the template option
                    $('select[name=\"meta[template]\"]').closest('li').hide();
                    $('input[name=\"meta[surcharge_templates]\"]').closest('li').hide();

                    if ($('input[name=\"meta[set_template]\"]:checked').val() == 'admin') {
                        $('select[name=\"meta[template]\"]').closest('li').show();
                        $('input[name=\"meta[surcharge_templates]\"]').closest('li').hide();
                    } else {
                        $('select[name=\"meta[template]\"]').closest('li').hide();
                        $('input[name=\"meta[surcharge_templates]\"]').closest('li').show();
                    }

                    $('input[name=\"meta[set_template]\"]').change(function() {
                        if ($(this).val() == 'admin') {
                            $('select[name=\"meta[template]\"]').closest('li').show();
                            $('input[name=\"meta[surcharge_templates]\"]').closest('li').hide();
                        } else {
                            $('select[name=\"meta[template]\"]').closest('li').hide();
                            $('input[name=\"meta[surcharge_templates]\"]').closest('li').show();
                        }
                    });
                });
            </script>
        ");

        // Fetch the 1st account from the list of accounts
        $module_row = null;
        $rows = $this->getModuleRows();

        if (isset($rows[0])) {
            $module_row = $rows[0];
        }
        unset($rows);

        // Fetch all the plans available for the different server types
        $baremetal_plans = [];
        $server_plans = [];
        $server_templates = [];

        if ($module_row) {
            $baremetal_plans = $this->getBaremetalPlans($module_row);
            $server_plans = $this->getServerPlans($module_row);
            $server_templates = $this->getTemplates($module_row);
        }

        // Set the Vultr server type as a selectable option
        $server_type = $fields->label(
            Language::_('Vultr.package_fields.server_type', true),
            'vultr_instances_type'
        );
        $server_type->attach(
            $fields->fieldSelect(
                'meta[server_type]',
                $this->getServerTypes(),
                (isset($vars->meta['server_type']) ? $vars->meta['server_type'] : null),
                ['id' => 'vultr_instances_type']
            )
        );
        $fields->setField($server_type);

        // Set the Vultr bare metal plans as a selectable option
        $baremetal_plan = $fields->label(
            Language::_('Vultr.package_fields.baremetal_plan', true),
            'vultr_baremetal_plan'
        );
        $baremetal_plan->attach(
            $fields->fieldSelect(
                'meta[baremetal_plan]',
                $baremetal_plans,
                (isset($vars->meta['baremetal_plan']) ? $vars->meta['baremetal_plan'] : null),
                ['id' => 'vultr_baremetal_plan']
            )
        );
        $fields->setField($baremetal_plan);

        // Set the Vultr server plans as a selectable option
        $server_plan = $fields->label(
            Language::_('Vultr.package_fields.server_plan', true),
            'vultr_instances_plan'
        );
        $server_plan->attach(
            $fields->fieldSelect(
                'meta[server_plan]',
                $server_plans,
                (isset($vars->meta['server_plan']) ? $vars->meta['server_plan'] : null),
                ['id' => 'vultr_instances_plan']
            )
        );
        $fields->setField($server_plan);

        // Set the template options
        $template_options = $fields->label(Language::_('Vultr.package_fields.set_template', true));

        $admin_set_template_label = $fields->label(Language::_('Vultr.package_fields.admin_set_template', true));
        $template_options->attach(
            $fields->fieldRadio(
                'meta[set_template]',
                'admin',
                (isset($vars->meta['set_template']) ? $vars->meta['set_template'] : 'admin') == 'admin',
                ['id' => 'vultr_admin_set_template'],
                $admin_set_template_label
            )
        );

        $client_set_template_label = $fields->label(Language::_('Vultr.package_fields.client_set_template', true));
        $template_options->attach(
            $fields->fieldRadio(
                'meta[set_template]',
                'client',
                (isset($vars->meta['set_template']) ? $vars->meta['set_template'] : null) == 'client',
                ['id' => 'vultr_client_set_template'],
                $client_set_template_label
            )
        );

        $fields->setField($template_options);

        // Set the server templates as a selectable option
        $template = $fields->label(
            Language::_('Vultr.package_fields.template', true),
            'vultr_template'
        );
        $template->attach(
            $fields->fieldSelect(
                'meta[template]',
                $server_templates,
                (isset($vars->meta['template']) ? $vars->meta['template'] : null),
                ['id' => 'vultr_template']
            )
        );
        $fields->setField($template);

        // Set the surcharge templates permissions
        $surcharge_templates_options = $fields->label(Language::_('Vultr.package_fields.surcharge_templates', true));

        $allow_surcharge_templates_label = $fields->label(
            Language::_('Vultr.package_fields.allow_surcharge_templates', true)
        );
        $surcharge_templates_options->attach(
            $fields->fieldRadio(
                'meta[surcharge_templates]',
                'allow',
                (isset($vars->meta['surcharge_templates']) ? $vars->meta['surcharge_templates'] : 'allow') == 'allow',
                ['id' => 'vultr_allow_surcharge_templates'],
                $allow_surcharge_templates_label
            )
        );

        $disallow_surcharge_templates_label = $fields->label(
            Language::_('Vultr.package_fields.disallow_surcharge_templates', true)
        );
        $surcharge_templates_options->attach(
            $fields->fieldRadio(
                'meta[surcharge_templates]',
                'disallow',
                (isset($vars->meta['surcharge_templates']) ? $vars->meta['surcharge_templates'] : null) == 'disallow',
                ['id' => 'vultr_disallow_surcharge_templates'],
                $disallow_surcharge_templates_label
            )
        );

        $fields->setField($surcharge_templates_options);

        return $fields;
    }

    /**
     * Gets a list of available server types.
     *
     * @return array A key/value array of available server types and their language
     */
    private function getServerTypes()
    {
        return [
            'baremetal' => Language::_('Vultr.package_fields.server_type.baremetal', true),
            'server' => Language::_('Vultr.package_fields.server_type.server', true)
        ];
    }

    /**
     * Fetches a listing of all bare metal plans.
     *
     * @param stdClass $module_row A stdClass object representing a single server
     * @return array An array of plans in key/value pair
     */
    private function getBaremetalPlans($module_row)
    {
        $api = $this->getApi($module_row->meta->api_key);
        $api->loadCommand('vultr_plans');

        $plans_api = new VultrPlans($api);
        $result = $plans_api->listBaremetalPlans()->response();

        $plans = [];
        foreach ($result->plans_metal ?? [] as $plan) {
            if (empty($plan->id) || empty($plan->locations)) {
                continue;
            }

            $arch = str_contains($plan->id, 'amd') ? 'AMD' : 'Intel Xeon';
            $plans[$plan->id] = Language::_(
                'Vultr.get_baremetal_plans.plan_name',
                true,
                $arch,
                $plan->cpu_model,
                $plan->cpu_count,
                $plan->type,
                $plan->disk_count,
                $plan->disk,
                $plan->ram
            );
        }

        return $plans;
    }

    /**
     * Fetches a listing of all server plans.
     *
     * @param stdClass $module_row A stdClass object representing a single server
     * @return array An array of plans in key/value pair
     */
    private function getServerPlans($module_row)
    {
        $api = $this->getApi($module_row->meta->api_key);
        $api->loadCommand('vultr_plans');

        $plans_api = new VultrPlans($api);
        $result = $plans_api->listPlans()->response();

        $plans = [];
        foreach ($result->plans ?? [] as $plan) {
            if (empty($plan->id) || empty($plan->locations)) {
                continue;
            }

            $arch = str_contains($plan->id, 'amd') ? 'AMD' : 'Intel';
            $plans[$plan->id] = Language::_(
                'Vultr.get_server_plans.plan_name',
                true,
                $arch,
                $plan->disk,
                $plan->ram,
                $plan->vcpu_count
            );
        }

        return $plans;
    }

    /**
     * Fetches a listing of all server templates.
     *
     * @param stdClass $module_row A stdClass object representing a single server
     * @param stdClass $package A stdClass object representing the selected package
     * @param stdClass $service A stdClass object representing the service
     * @return array An array of plans in key/value pair
     */
    private function getTemplates($module_row, $package = null, $service = null)
    {
        $api = $this->getApi($module_row->meta->api_key);
        $api->loadCommand('vultr_os');
        $api->loadCommand('vultr_app');

        $os_api = new VultrOs($api);
        $app_api = new VultrApp($api);

        $result_os = $os_api->listOs()->response();
        $result_app = $app_api->listApps()->response();

        // Get available apps for a specific service
        if (!is_null($service)) {
            $api->loadCommand('vultr_instances');
            $api->loadCommand('vultr_baremetal');

            $server_api = new VultrInstances($api);
            $baremetal_api = new VultrBaremetal($api);

            // Get the service fields
            $service_fields = $this->serviceFieldsToObject($service->fields);

            if ($package->meta->server_type == 'server') {
                $params = [
                    'instance-id' => $service_fields->vultr_subid
                ];
                $result_app = $server_api->appChangeList($params)->response()->upgrades;
            } else {
                $params = [
                    'baremetal-id' => $service_fields->vultr_subid
                ];
                $result_app = $baremetal_api->appChangeList($params)->response()->upgrades;
            }
        }

        $excluded_templates = [159, 164, 180, 186];
        $templates = [];
        foreach ($result_os->os ?? [] as $os) {
            if (in_array($os->id, $excluded_templates)) {
                continue;
            }

            if ($os->family !== 'windows'
                || is_null($package)
                || ($os->family == 'windows' && ($package->meta->surcharge_templates ?? null) == 'allow')
            ) {
                $templates['os-' . $os->id] = ($os->family == 'windows')
                    ? Language::_('Vultr.get_templates.paid_template_name', true, $os->name)
                    : $os->name;
            }
        }

        $paid_apps = [35, 36, 38];
        foreach ($result_app->applications ?? [] as $app) {
            if (!in_array($app->id, $paid_apps)
                || is_null($package)
                || (in_array($app->id, $paid_apps) && ($package->meta->surcharge_templates ?? null) == 'allow')
            ) {
                $templates['app-' . $app->id] = in_array($app->id, $paid_apps)
                    ? Language::_('Vultr.get_templates.paid_template_name', true, $app->name)
                    : $app->name;
            }
        }

        return $templates;
    }

    /**
     * Fetches a listing of all the available locations.
     *
     * @param stdClass $module_row A stdClass object representing a single server
     * @param stdClass $package A stdClass object representing the selected package
     * @return array An array of plans in key/value pair
     */
    private function getLocations($module_row, $package = null)
    {
        // Fetch the locations results from the cache, if they exist
        $cache = Cache::fetchCache(
            'locations',
            Configure::get('Blesta.company_id') . DS . 'modules' . DS . 'vultr' . DS
        );

        if ($cache) {
            return unserialize(base64_decode($cache));
        }

        $api = $this->getApi($module_row->meta->api_key);
        $api->loadCommand('vultr_regions');

        $regions_api = new VultrRegions($api);
        $result = $regions_api->listRegions()->response();

        $locations = [];
        foreach ($result->regions ?? [] as $location) {
            $locations[$location->id] = $location->city . ', ' . $location->country . '. ' . $location->continent . '.';
        }

        // Check availability in the locations
        if (!is_null($package)) {
            foreach ($locations as $region_id => $location) {
                if ($package->meta->server_type == 'server') {
                    $availability = $regions_api->availabilityVc2(['region-id' => $region_id])->response();
                } else {
                    $availability = $regions_api->availabilityBaremetal(['region-id' => $region_id])->response();
                }

                if (empty($availability)) {
                    unset($locations[$region_id]);
                }

                // The Vultr API only allows a maximum of 2 request per second, we need wait
                // one 0.25 seconds before the next request.
                usleep(250000);
            }
        }

        // Save locations in cache
        if (Configure::get('Caching.on') && is_writable(CACHEDIR)) {
            try {
                Cache::writeCache(
                    'locations',
                    base64_encode(serialize($locations)),
                    strtotime(Configure::get('Blesta.cache_length')) - time(),
                    Configure::get('Blesta.company_id') . DS . 'modules' . DS . 'vultr' . DS
                );
            } catch (Exception $e) {
                // Write to cache failed, so disable caching
                Configure::set('Caching.on', false);
            }
        }

        return $locations;
    }

    /**
     * Validates input data when attempting to add a package, returns the meta
     * data to save when adding a package. Performs any action required to add
     * the package on the remote server. Sets Input errors on failure,
     * preventing the package from being added.
     *
     * @param array An array of key/value pairs used to add the package
     * @return array A numerically indexed array of meta fields to be stored for this package containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function addPackage(array $vars = null)
    {
        // Set rules to validate input data
        $this->Input->setRules($this->getPackageRules($vars));

        // Build meta data to return
        $meta = [];
        if ($this->Input->validates($vars)) {
            // Return all package meta fields
            foreach ($vars['meta'] as $key => $value) {
                $meta[] = [
                    'key' => $key,
                    'value' => $value,
                    'encrypted' => 0
                ];
            }
        }

        return $meta;
    }

    /**
     * Validates input data when attempting to edit a package, returns the meta
     * data to save when editing a package. Performs any action required to edit
     * the package on the remote server. Sets Input errors on failure,
     * preventing the package from being edited.
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param array An array of key/value pairs used to edit the package
     * @return array A numerically indexed array of meta fields to be stored for this package containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function editPackage($package, array $vars = null)
    {
        // The same as adding
        return $this->addPackage($vars);
    }

    /**
     * Returns the rendered view of the manage module page.
     *
     * @param mixed $module A stdClass object representing the module and its rows
     * @param array $vars An array of post data submitted to or on the manager module
     *  page (used to repopulate fields after an error)
     * @return string HTML content containing information to display when viewing the manager module page
     */
    public function manageModule($module, array &$vars)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('manage', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'vultr' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

        $this->view->set('module', $module);

        return $this->view->fetch();
    }

    /**
     * Returns the rendered view of the add module row page.
     *
     * @param array $vars An array of post data submitted to or on the add module
     *  row page (used to repopulate fields after an error)
     * @return string HTML content containing information to display when viewing the add module row page
     */
    public function manageAddRow(array &$vars)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('add_row', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'vultr' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

        // Set unspecified checkboxes
        if (!empty($vars)) {
            if (empty($vars['use_ssl'])) {
                $vars['use_ssl'] = 'false';
            }
        }

        $this->view->set('vars', (object) $vars);

        return $this->view->fetch();
    }

    /**
     * Returns the rendered view of the edit module row page.
     *
     * @param stdClass $module_row The stdClass representation of the existing module row
     * @param array $vars An array of post data submitted to or on the edit
     *  module row page (used to repopulate fields after an error)
     * @return string HTML content containing information to display when viewing the edit module row page
     */
    public function manageEditRow($module_row, array &$vars)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('edit_row', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'vultr' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

        if (empty($vars)) {
            $vars = $module_row->meta;
        } else {
            // Set unspecified checkboxes
            if (empty($vars['use_ssl'])) {
                $vars['use_ssl'] = 'false';
            }
        }

        $this->view->set('vars', (object) $vars);

        return $this->view->fetch();
    }

    /**
     * Adds the module row on the remote server. Sets Input errors on failure,
     * preventing the row from being added. Returns a set of data, which may be
     * a subset of $vars, that is stored for this module row.
     *
     * @param array $vars An array of module info to add
     * @return array A numerically indexed array of meta fields for the module row containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     */
    public function addModuleRow(array &$vars)
    {
        $meta_fields = ['account_name', 'api_key'];
        $encrypted_fields = ['api_key'];

        $this->Input->setRules($this->getRowRules($vars));

        // Validate module row
        if ($this->Input->validates($vars)) {
            // Build the meta data for this row
            $meta = [];
            foreach ($vars as $key => $value) {
                if (in_array($key, $meta_fields)) {
                    $meta[] = [
                        'key' => $key,
                        'value' => $value,
                        'encrypted' => in_array($key, $encrypted_fields) ? 1 : 0
                    ];
                }
            }

            return $meta;
        }
    }

    /**
     * Edits the module row on the remote server. Sets Input errors on failure,
     * preventing the row from being updated. Returns a set of data, which may be
     * a subset of $vars, that is stored for this module row.
     *
     * @param stdClass $module_row The stdClass representation of the existing module row
     * @param array $vars An array of module info to update
     * @return array A numerically indexed array of meta fields for the module row containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     */
    public function editModuleRow($module_row, array &$vars)
    {
        $meta_fields = ['account_name', 'api_key'];
        $encrypted_fields = ['api_key'];

        // Set unspecified checkboxes
        if (empty($vars['use_ssl'])) {
            $vars['use_ssl'] = 'false';
        }

        $this->Input->setRules($this->getRowRules($vars));

        // Validate module row
        if ($this->Input->validates($vars)) {
            // Build the meta data for this row
            $meta = [];
            foreach ($vars as $key => $value) {
                if (in_array($key, $meta_fields)) {
                    $meta[] = [
                        'key' => $key,
                        'value' => $value,
                        'encrypted' => in_array($key, $encrypted_fields) ? 1 : 0
                    ];
                }
            }

            return $meta;
        }
    }

    /**
     * Returns all fields to display to an admin attempting to add a service with the module.
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containg the fields to render
     *  as well as any additional HTML markup to include
     */
    public function getAdminAddFields($package, $vars = null)
    {
        // Load the required helpers
        Loader::loadHelpers($this, ['Html']);

        // Fetch the module row available for this package
        $module_row = $this->getModuleRow(($package->module_row ?? 0));

        // Get the available templates
        $templates = $this->getTemplates($module_row, $package);

        // Get the available locations
        $locations = $this->getLocations($module_row, $package);

        $fields = new ModuleFields();

        // Create subid label
        $subid = $fields->label(Language::_('Vultr.service_field.subid', true), 'vultr_subid');
        // Create subid field and attach to subid label
        $subid->attach(
            $fields->fieldText(
                'vultr_subid',
                ($vars->vultr_subid ?? ($vars->subid ?? null)),
                ['id' => 'vultr_subid']
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('Vultr.service_field.tooltip.subid', true));
        $subid->attach($tooltip);
        // Set the label as a field
        $fields->setField($subid);

        // Create hostname label
        $hostname = $fields->label(Language::_('Vultr.service_field.hostname', true), 'vultr_hostname');
        // Create hostname field and attach to hostname label
        $hostname->attach(
            $fields->fieldText(
                'vultr_hostname',
                ($vars->vultr_hostname ?? ($vars->hostname ?? null)),
                ['id' => 'vultr_hostname']
            )
        );
        // Set the label as a field
        $fields->setField($hostname);

        // Set the server location as a selectable option
        $location = $fields->label(Language::_('Vultr.service_field.location', true), 'vultr_location');
        $location->attach(
            $fields->fieldSelect(
                'vultr_location',
                $locations,
                ($vars->vultr_location ?? ($vars->location ?? null)),
                ['id' => 'vultr_location']
            )
        );
        $fields->setField($location);

        // Set the server templates as a selectable option
        if ($package->meta->set_template == 'client') {
            $template = $fields->label(Language::_('Vultr.service_field.template', true), 'vultr_template');
            $template->attach(
                $fields->fieldSelect(
                    'vultr_template',
                    $templates,
                    ($vars->vultr_template ?? ($vars->template ?? null)),
                    ['id' => 'vultr_template']
                )
            );
            $fields->setField($template);
        }

        // Set the IPv6 options
        $ipv6_options = $fields->label(Language::_('Vultr.service_field.ipv6', true));

        $enable_ipv6_label = $fields->label(Language::_('Vultr.service_field.enable_ipv6', true));
        $ipv6_options->attach(
            $fields->fieldRadio(
                'vultr_enable_ipv6',
                'enable',
                ($vars->vultr_enable_ipv6 ?? ($vars->ipv6 ?? 'enable')) == 'enable',
                ['id' => 'vultr_enable_ipv6'],
                $enable_ipv6_label
            )
        );

        $disable_ipv6_label = $fields->label(Language::_('Vultr.service_field.disable_ipv6', true));
        $ipv6_options->attach(
            $fields->fieldRadio(
                'vultr_enable_ipv6',
                'disable',
                ($vars->vultr_enable_ipv6 ?? ($vars->ipv6 ?? null)) == 'disable',
                ['id' => 'vultr_disable_ipv6'],
                $disable_ipv6_label
            )
        );

        $fields->setField($ipv6_options);

        return $fields;
    }

    /**
     * Returns all fields to display to a client attempting to add a service with the module.
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containg the fields to render as well
     *  as any additional HTML markup to include
     */
    public function getClientAddFields($package, $vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        // Fetch the module row from the package
        $module_row = $this->getModuleRow($package->module_row);

        // Get the available templates
        $templates = $this->getTemplates($module_row, $package);

        // Get the available locations
        $locations = $this->getLocations($module_row, $package);

        $fields = new ModuleFields();

        // Create hostname label
        $hostname = $fields->label(Language::_('Vultr.service_field.hostname', true), 'vultr_hostname');
        // Create hostname field and attach to hostname label
        $hostname->attach(
            $fields->fieldText(
                'vultr_hostname',
                ($vars->vultr_hostname ?? ($vars->hostname ?? null)),
                ['id' => 'vultr_hostname']
            )
        );
        // Set the label as a field
        $fields->setField($hostname);

        // Set the server location as a selectable option
        $location = $fields->label(Language::_('Vultr.service_field.location', true), 'vultr_location');
        $location->attach(
            $fields->fieldSelect(
                'vultr_location',
                $locations,
                ($vars->vultr_location ?? ($vars->location ?? null)),
                ['id' => 'vultr_location']
            )
        );
        $fields->setField($location);

        // Set the server templates as a selectable option
        if ($package->meta->set_template == 'client') {
            $template = $fields->label(Language::_('Vultr.service_field.template', true), 'vultr_template');
            $template->attach(
                $fields->fieldSelect(
                    'vultr_template',
                    $templates,
                    ($vars->vultr_template ?? ($vars->template ?? null)),
                    ['id' => 'vultr_template']
                )
            );
            $fields->setField($template);
        }

        // Set the IPv6 options
        $ipv6_options = $fields->label(Language::_('Vultr.service_field.ipv6', true));

        $enable_ipv6_label = $fields->label(Language::_('Vultr.service_field.enable_ipv6', true));
        $ipv6_options->attach(
            $fields->fieldRadio(
                'vultr_enable_ipv6',
                'enable',
                ($vars->vultr_enable_ipv6 ?? ($vars->ipv6 ?? 'enable')) == 'enable',
                ['id' => 'vultr_enable_ipv6'],
                $enable_ipv6_label
            )
        );

        $disable_ipv6_label = $fields->label(Language::_('Vultr.service_field.disable_ipv6', true));
        $ipv6_options->attach(
            $fields->fieldRadio(
                'vultr_enable_ipv6',
                'disable',
                ($vars->vultr_enable_ipv6 ?? ($vars->ipv6 ?? null)) == 'disable',
                ['id' => 'vultr_disable_ipv6'],
                $disable_ipv6_label
            )
        );

        $fields->setField($ipv6_options);

        return $fields;
    }

    /**
     * Returns all fields to display to an admin attempting to edit a service with the module.
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containg the fields to render as
     *  well as any additional HTML markup to include
     */
    public function getAdminEditFields($package, $vars = null)
    {
        // Load the required helpers
        Loader::loadHelpers($this, ['Html']);

        // Fetch the module row available for this package
        $module_row = $this->getModuleRow((isset($package->module_row) ? $package->module_row : 0));

        // Get the available templates
        $templates = $this->getTemplates($module_row, $package);

        $fields = new ModuleFields();

        // Create subid label
        $subid = $fields->label(Language::_('Vultr.service_field.subid', true), 'vultr_subid');
        // Create subid field and attach to subid label
        $subid->attach(
            $fields->fieldText(
                'vultr_subid',
                ($vars->vultr_subid ?? ($vars->subid ?? null)),
                ['id' => 'vultr_subid']
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('Vultr.service_field.tooltip.subid', true));
        $subid->attach($tooltip);
        // Set the label as a field
        $fields->setField($subid);

        // Set the server templates as a selectable option
        if ($package->meta->set_template == 'client') {
            $template = $fields->label(Language::_('Vultr.service_field.template', true), 'vultr_template');
            $template->attach(
                $fields->fieldSelect(
                    'vultr_template',
                    $templates,
                    ($vars->vultr_template ?? ($vars->template ?? null)),
                    ['id' => 'vultr_template']
                )
            );
            $fields->setField($template);
        }

        return $fields;
    }

    /**
     * Attempts to validate service info. This is the top-level error checking method. Sets Input errors on failure.
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param array $vars An array of user supplied info to satisfy the request
     * @return bool True if the service validates, false otherwise. Sets Input errors when false.
     */
    public function validateService($package, array $vars = null)
    {
        $rules = $this->getServiceRules($vars, $package, false);

        // Template must be given only if it can be set by the client
        if (isset($package->meta->set_template) && $package->meta->set_template == 'admin') {
            unset($rules['vultr_template']);
        }

        $this->Input->setRules($rules);

        return $this->Input->validates($vars);
    }

    /**
     * Attempts to validate an existing service against a set of service info updates. Sets Input errors on failure.
     *
     * @param stdClass $service A stdClass object representing the service to validate for editing
     * @param array $vars An array of user-supplied info to satisfy the request
     * @return bool True if the service update validates or false otherwise. Sets Input errors when false.
     */
    public function validateServiceEdit($service, array $vars = null)
    {
        $rules = $this->getServiceRules($vars, null, true);

        // Get the service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Template should only be checked if it has changed
        if (!isset($service_fields->vultr_template)
            || !isset($vars['vultr_template'])
            || $service_fields->vultr_template == $vars['vultr_template']
        ) {
            unset($rules['vultr_template']);
        }

        $this->Input->setRules($rules);

        return $this->Input->validates($vars);
    }

    /**
     * Returns the rule set for adding/editing a service.
     *
     * @param array $vars A list of input vars
     * @param stdClass $package A stdClass object representing the selected package
     * @param bool $edit True to get the edit rules, false for the add rules
     * @return array Service rules
     */
    private function getServiceRules(array $vars = null, $package = null, $edit = false)
    {
        $rules = [
            'vultr_hostname' => [
                'format' => [
                    'if_set' => $edit,
                    'rule' => [[$this, 'validateHostName']],
                    'message' => Language::_('Vultr.!error.vultr_hostname.format', true)
                ]
            ],
            'vultr_location' => [
                'valid' => [
                    'if_set' => $edit,
                    'rule' => [[$this, 'validateLocation']],
                    'message' => Language::_('Vultr.!error.vultr_location.valid', true)
                ]
            ],
            'vultr_template' => [
                'valid' => [
                    'if_set' => $edit,
                    'rule' => [[$this, 'validateTemplate']],
                    'message' => Language::_('Vultr.!error.vultr_template.valid', true)
                ]
            ],
            'vultr_subid' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => [
                        [$this, 'validateSubID'],
                        $package->meta->server_type ?? 'server'
                    ],
                    'message' => Language::_('Vultr.!error.vultr_subid.valid', true)
                ]
            ]
        ];

        return $rules;
    }

    /**
     * Adds the service to the remote server. Sets Input errors on failure,
     * preventing the service from being added.
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param array $vars An array of user supplied info to satisfy the request
     * @param stdClass $parent_package A stdClass object representing the parent
     *  service's selected package (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent
     *  service of the service being added (if the current service is an addon service
     *  service and parent service has already been provisioned)
     * @param string $status The status of the service being added. These include:
     *  - active
     *  - canceled
     *  - pending
     *  - suspended
     * @return array A numerically indexed array of meta fields to be stored for this service containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function addService(
        $package,
        array $vars = null,
        $parent_package = null,
        $parent_service = null,
        $status = 'pending'
    ) {
        // Get the module row
        $row = $this->getModuleRow();

        if (!$row) {
            $this->Input->setErrors(
                ['module_row' => ['missing' => Language::_('Vultr.!error.module_row.missing', true)]]
            );

            return;
        }

        // Disallow capital letters in hostname
        $vars['vultr_hostname'] = strtolower($vars['vultr_hostname']);

        // Get service parameters
        $params = $this->getFieldsFromInput((array) $vars, $package);

        // Validate service
        $this->validateService($package, $vars);

        if ($this->Input->errors()) {
            return;
        }

        // Only provision the service if 'use_module' is true
        if ($vars['use_module'] == 'true') {
            $this->log('api.vultr.com|create', serialize($params), 'input', true);

            try {
                // Initialize the Vultr API
                $api = $this->getApi($row->meta->api_key);

                if ($package->meta->server_type == 'server') {
                    $api->loadCommand('vultr_instances');
                    $vultr_api = new VultrInstances($api);
                } else {
                    $api->loadCommand('vultr_baremetal');
                    $vultr_api = new VultrBaremetal($api);
                }

                if (empty($vars['vultr_subid'])) {
                    // Create the vultr server
                    $result = $this->parseResponse($vultr_api->create($params));
                    $server = $result->instance ??  $result->bare_metal ?? (object) [];
                }
            } catch (Exception $e) {
                $this->Input->setErrors(
                    ['api' => ['internal' => Language::_('Vultr.!error.api.internal', true)]]
                );
            }

            if ($this->Input->errors()) {
                return;
            }
        }

        // Return service fields
        return [
            [
                'key' => 'vultr_hostname',
                'value' => $vars['vultr_hostname'] ?? null,
                'encrypted' => 0
            ],
            [
                'key' => 'vultr_template',
                'value' => $vars['vultr_template'] ?? null,
                'encrypted' => 0
            ],
            [
                'key' => 'vultr_location',
                'value' => $vars['vultr_location'] ?? null,
                'encrypted' => 0
            ],
            [
                'key' => 'vultr_enable_ipv6',
                'value' => $vars['vultr_enable_ipv6'] ?? null,
                'encrypted' => 0
            ],
            [
                'key' => 'vultr_password',
                'value' => $server->default_password ?? ($vars['vultr_password'] ?? null),
                'encrypted' => 1
            ],
            [
                'key' => 'vultr_subid',
                'value' => $server->id ?? ($vars['vultr_subid'] ?? null),
                'encrypted' => 0
            ],
            [
                'key' => 'vultr_snapshots',
                'value' => [],
                'encrypted' => 0
            ]
        ];
    }

    /**
     * Edits the service on the remote server. Sets Input errors on failure,
     * preventing the service from being edited.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $vars An array of user supplied info to satisfy the request
     * @param stdClass $parent_package A stdClass object representing the parent
     *  service's selected package (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent
     *  service of the service being edited (if the current service is an addon service)
     * @return array A numerically indexed array of meta fields to be stored for this service containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function editService($package, $service, array $vars = null, $parent_package = null, $parent_service = null)
    {
        // Get the module row
        $row = $this->getModuleRow();

        if (!$row) {
            $this->Input->setErrors(
                ['module_row' => ['missing' => Language::_('Vultr.!error.module_row.missing', true)]]
            );

            return;
        }

        // Get the service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Disallow capital letters in hostname
        if (isset($vars['vultr_hostname'])) {
            $vars['vultr_hostname'] = strtolower($vars['vultr_hostname']);
        }

        // Get service parameters
        $params = $this->getFieldsFromInput((array) $vars, $package);

        // Validate service
        $this->validateServiceEdit($service, $vars);

        if ($this->Input->errors()) {
            return;
        }

        // Check for fields that changed
        $delta = [];
        foreach ($vars as $key => $value) {
            if (!array_key_exists($key, (array) $service_fields) || $vars[$key] != $service_fields->$key) {
                $delta[$key] = $value;
            }
        }

        // Only edit the service if 'use_module' is true
        if ($vars['use_module'] == 'true' && !isset($delta['vultr_subid'])) {
            // Initialize the Vultr API
            $api = $this->getApi($row->meta->api_key);

            if ($package->meta->server_type == 'server') {
                $api->loadCommand('vultr_instances');
                $vultr_api = new VultrInstances($api);
            } else {
                $api->loadCommand('vultr_baremetal');
                $vultr_api = new VultrBaremetal($api);
            }

            // Update the OS and the Template of the server
            if (isset($delta['vultr_template'])) {
                // Fetch the correct template of the server
                if ($package->meta->set_template == 'admin') {
                    $template = $package->meta->template;
                } elseif ($package->meta->set_template == 'client') {
                    $template = $delta['vultr_template'] ?? null;
                }

                $template = explode('-', $template, 2);

                if (isset($template[0]) && $template[0] == 'os') {
                    $osid = $template[1] ?? null;
                    $appid = null;
                } else {
                    $osid = 186;
                    $appid = $template[1] ?? null;
                }

                // Change the server template
                if (is_null($appid)) {
                    if ($package->meta->server_type == 'server') {
                        $params = [
                            'instance-id' => $vars['vultr_subid'],
                            'os_id' => $osid
                        ];
                    } else {
                        $params = [
                            'baremetal-id' => $vars['vultr_subid'],
                            'os_id' => $osid
                        ];
                    }

                    $this->log('api.vultr.com|os_change', serialize($params), 'input', true);
                    $result = $this->parseResponse($vultr_api->osChange($params));
                } else {
                    if ($package->meta->server_type == 'server') {
                        $params = [
                            'instance-id' => $vars['vultr_subid'],
                            'app_id' => $appid
                        ];
                    } else {
                        $params = [
                            'baremetal-id' => $vars['vultr_subid'],
                            'app_id' => $appid
                        ];
                    }

                    $this->log('api.vultr.com|app_change', serialize($params), 'input', true);
                    $result = $this->parseResponse($vultr_api->appChange($params));
                }
            }

            // Only virtual machines supports automatic backups
            if (isset($vars['configoptions']['enable_backup']) && $package->meta->server_type == 'server') {
                // Enable/disable automatic backups
                $enable_backup = null;
                foreach ($service->options as $service_option) {
                    if ($service_option->option_name == 'enable_backup') {
                        $enable_backup = $service_option;
                        break;
                    }
                }

                if ($vars['configoptions']['enable_backup'] == 'enable'
                    && (!$enable_backup || $enable_backup->option_value != 'enable')
                ) {
                    // Enable daily backups
                    $params = [
                        'instance-id' => $service_fields->vultr_subid
                    ];
                    $this->log('api.vultr.com|backup_enable', serialize($params), 'input', true);
                    $this->parseResponse($vultr_api->backupEnable($params));

                    // Updated backups to be daily
                    $params['type'] = 'daily';
                    $this->log('api.vultr.com|backup_daily', serialize($params), 'input', true);
                    $this->parseResponse($vultr_api->backupSetSchedule($params));
                } elseif ($vars['configoptions']['enable_backup'] != 'enable'
                    && (!$enable_backup || $enable_backup->option_value != 'disable')
                ) {
                    // Disable daily backups
                    $params = [
                        'instance-id' => $service_fields->vultr_subid
                    ];
                    $this->log('api.vultr.com|backup_disable', serialize($params), 'input', true);
                    $this->parseResponse($vultr_api->backupDisable($params));
                }
            }

            if ($this->Input->errors()) {
                return;
            }
        }

        // Set fields to update locally
        $fields = [
            'vultr_subid',
            'vultr_template',
            'vultr_snapshots',
            'vultr_enable_ipv6'
        ];

        foreach ($fields as $field) {
            if (property_exists($service_fields, $field) && isset($vars[$field])) {
                $service_fields->{$field} = $vars[$field];
            }
        }

        // Return all the service fields
        $fields = [];
        $encrypted_fields = [];
        foreach ($service_fields as $key => $value) {
            $fields[] = ['key' => $key, 'value' => $value, 'encrypted' => (in_array($key, $encrypted_fields) ? 1 : 0)];
        }

        return $fields;
    }

    /**
     * Suspends the service on the remote server. Sets Input errors on failure,
     * preventing the service from being suspended.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param stdClass $parent_package A stdClass object representing the parent
     *  service's selected package (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent
     *  service of the service being suspended (if the current service is an addon service)
     * @return mixed null to maintain the existing meta fields or a numerically
     *  indexed array of meta fields to be stored for this service containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function suspendService($package, $service, $parent_package = null, $parent_service = null)
    {
        // Get the module row
        $row = $this->getModuleRow();

        if ($row) {
            $api = $this->getApi($row->meta->api_key);

            // Get the service fields
            $service_fields = $this->serviceFieldsToObject($service->fields);

            if ($package->meta->server_type == 'server') {
                $api->loadCommand('vultr_instances');
                $instances_api = new VultrInstances($api);
                $params = ['instance-id' => $service_fields->vultr_subid];
            } else {
                $api->loadCommand('vultr_baremetal');
                $instances_api = new VultrBaremetal($api);
                $params = ['baremetal-id' => $service_fields->vultr_subid];
            }

            $this->log('api.vultr.com|halt', serialize($params), 'input', true);
            $this->parseResponse($instances_api->halt($params));
        }

        return null;
    }

    /**
     * Unsuspends the service on the remote server. Sets Input errors on failure,
     * preventing the service from being unsuspended.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param stdClass $parent_package A stdClass object representing the parent
     *  service's selected package (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent
     *  service of the service being unsuspended (if the current service is an addon service)
     * @return mixed null to maintain the existing meta fields or a numerically
     *  indexed array of meta fields to be stored for this service containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function unsuspendService($package, $service, $parent_package = null, $parent_service = null)
    {
        // Get the module row
        $row = $this->getModuleRow();

        if ($row) {
            $api = $this->getApi($row->meta->api_key);

            // Get the service fields
            $service_fields = $this->serviceFieldsToObject($service->fields);

            if ($package->meta->server_type == 'server') {
                $api->loadCommand('vultr_instances');
                $instances_api = new VultrInstances($api);
                $params = ['instance-id' => $service_fields->vultr_subid];
            } else {
                $api->loadCommand('vultr_baremetal');
                $instances_api = new VultrBaremetal($api);
                $params = ['baremetal-id' => $service_fields->vultr_subid];
            }

            $this->log('api.vultr.com|reboot', serialize($params), 'input', true);
            $this->parseResponse($instances_api->reboot($params));
        }

        return null;
    }

    /**
     * Cancels the service on the remote server. Sets Input errors on failure,
     * preventing the service from being canceled.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param stdClass $parent_package A stdClass object representing the parent
     *  service's selected package (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent
     *  service of the service being canceled (if the current service is an addon service)
     * @return mixed null to maintain the existing meta fields or a numerically
     *  indexed array of meta fields to be stored for this service containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function cancelService($package, $service, $parent_package = null, $parent_service = null)
    {
        // Get the module row
        $row = $this->getModuleRow();

        if ($row) {
            $api = $this->getApi($row->meta->api_key);

            // Get the service fields
            $service_fields = $this->serviceFieldsToObject($service->fields);

            if ($package->meta->server_type == 'server') {
                $api->loadCommand('vultr_instances');
                $instances_api = new VultrInstances($api);
                $params = ['instance-id' => $service_fields->vultr_subid];
            } else {
                $api->loadCommand('vultr_baremetal');
                $instances_api = new VultrBaremetal($api);
                $params = ['baremetal-id' => $service_fields->vultr_subid];
            }

            $this->log('api.vultr.com|destroy', serialize($params), 'input', true);
            $this->parseResponse($instances_api->destroy($params));
        }

        return null;
    }

    /**
     * Updates the package for the service on the remote server. Sets Input
     * errors on failure, preventing the service's package from being changed.
     *
     * @param stdClass $package_from A stdClass object representing the current package.
     * @param stdClass $package_to A stdClass object representing the new package.
     * @param stdClass $service A stdClass object representing the current service.
     * @param stdClass $parent_package A stdClass object representing the parent
     *  service's selected package (if the current service is an addon service).
     * @param stdClass $parent_service A stdClass object representing the parent
     *  service of the service being changed (if the current service is an addon service).
     * @return mixed null to maintain the existing meta fields or a numerically
     *  indexed array of meta fields to be stored for this service containing:
     *  - key The key for this meta field.
     *  - value The value for this key.
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted).
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function changeServicePackage(
        $package_from,
        $package_to,
        $service,
        $parent_package = null,
        $parent_service = null
    ) {
        // Get the module row
        $row = $this->getModuleRow();

        if ($package_from->meta->server_type !== 'server') {
            return null;
        }

        if ($row) {
            $api = $this->getApi($row->meta->api_key);
            $api->loadCommand('vultr_instances');
            $vultr_api = new VultrInstances($api);

            // Get the service fields
            $service_fields = $this->serviceFieldsToObject($service->fields);

            // Upgrade plan
            $params = [
                'instance-id' => $service_fields->vultr_subid,
                'plan' => $package_to->meta->server_plan
            ];
            $this->log('api.vultr.com|upgrade_plan', serialize($params), 'input', true);
            $this->parseResponse($vultr_api->upgradePlan($params));
        }

        return null;
    }

    /**
     * Fetches the HTML content to display when viewing the service info in the
     * admin interface.
     *
     * @param stdClass $service A stdClass object representing the service
     * @param stdClass $package A stdClass object representing the service's package
     * @return string HTML content containing information to display when viewing the service info
     */
    public function getAdminServiceInfo($service, $package)
    {
        // Get the module row
        $row = $this->getModuleRow();

        // Get the service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Initialize the Vultr API
        $api = $this->getApi($row->meta->api_key);

        if ($package->meta->server_type == 'server') {
            $api->loadCommand('vultr_instances');
            $vultr_api = new VultrInstances($api);
        } else {
            $api->loadCommand('vultr_baremetal');
            $vultr_api = new VultrBaremetal($api);
        }

        // Get the server details
        $this->log('api.vultr.com|get', serialize($service_fields), 'input', true);
        if ($package->meta->server_type == 'server') {
            $params = ['instance-id' => $service_fields->vultr_subid];
            $response = $this->parseResponse($vultr_api->get($params));
            $server_details = $response->instance ?? (object) [];
        } else {
            $params = ['baremetal-id' => $service_fields->vultr_subid];
            $response = $this->parseResponse($vultr_api->get($params));
            $server_details = $response->bare_metal ?? (object) [];
        }

        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('admin_service_info', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'vultr' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Set locations
        $locations = $this->getLocations($row, $package);
        $this->view->set('locations', $locations);

        $this->view->set('module_row', $row);
        $this->view->set('package', $package);
        $this->view->set('service', $service);
        $this->view->set('service_fields', $service_fields);
        $this->view->set('server_details', ($server_details ?? new stdClass()));

        return $this->view->fetch();
    }

    /**
     * Fetches the HTML content to display when viewing the service info in the
     * client interface.
     *
     * @param stdClass $service A stdClass object representing the service
     * @param stdClass $package A stdClass object representing the service's package
     * @return string HTML content containing information to display when viewing the service info
     */
    public function getClientServiceInfo($service, $package)
    {
        // Get the module row
        $row = $this->getModuleRow();

        // Get the service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Initialize the Vultr API
        $api = $this->getApi($row->meta->api_key);

        if ($package->meta->server_type == 'server') {
            $api->loadCommand('vultr_instances');
            $vultr_api = new VultrInstances($api);
        } else {
            $api->loadCommand('vultr_baremetal');
            $vultr_api = new VultrBaremetal($api);
        }

        // Get the server details
        $this->log('api.vultr.com|get', serialize($service_fields), 'input', true);
        if ($package->meta->server_type == 'server') {
            $params = ['instance-id' => $service_fields->vultr_subid];
            $response = $this->parseResponse($vultr_api->get($params));
            $server_details = $response->instance ?? (object) [];
        } else {
            $params = ['baremetal-id' => $service_fields->vultr_subid];
            $response = $this->parseResponse($vultr_api->get($params));
            $server_details = $response->bare_metal ?? (object) [];
        }

        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('client_service_info', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'vultr' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Set locations
        $locations = $this->getLocations($row, $package);
        $this->view->set('locations', $locations);

        $this->view->set('module_row', $row);
        $this->view->set('package', $package);
        $this->view->set('service', $service);
        $this->view->set('service_fields', $service_fields);
        $this->view->set('server_details', ($server_details ?? new stdClass()));

        return $this->view->fetch();
    }

    /**
     * Actions tab.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabActions($package, $service, array $get = null, array $post = null, array $files = null)
    {
        // Get the actions tab
        return $this->getTabActions($package, $service, $post);
    }

    /**
     * Stats tab.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabStats($package, $service, array $get = null, array $post = null, array $files = null)
    {
        // Get stats tab
        return $this->getTabStats($package, $service, $post);
    }

    /**
     * Snapshots tab.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabSnapshots($package, $service, array $get = null, array $post = null, array $files = null)
    {
        // Get snapshots tab
        return $this->getTabSnapshots($package, $service, $post);
    }

    /**
     * Backups tab.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabBackups($package, $service, array $get = null, array $post = null, array $files = null)
    {
        // Get backups tab
        return $this->getTabBackups($package, $service, $post);
    }

    /**
     * Client Actions.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabClientActions($package, $service, array $get = null, array $post = null, array $files = null)
    {
        // Get the actions tab
        return $this->getTabActions($package, $service, $post, true);
    }

    /**
     * Client Stats tab.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabClientStats($package, $service, array $get = null, array $post = null, array $files = null)
    {
        // Get stats tab
        return $this->getTabStats($package, $service, $post, true);
    }

    /**
     * Client Snapshots tab.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabClientSnapshots($package, $service, array $get = null, array $post = null, array $files = null)
    {
        // Get snapshots tab
        return $this->getTabSnapshots($package, $service, $post, true);
    }

    /**
     * Client Backups tab.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabClientBackups($package, $service, array $get = null, array $post = null, array $files = null)
    {
        // Get backups tab
        return $this->getTabBackups($package, $service, $post, true);
    }

    /**
     * Actions (Admin or Client) tab.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $post Any POST parameters
     * @param bool $client Whether to use the client view template
     * @return string The string representing the contents of this tab
     */
    private function getTabActions($package, $service, array $post = null, $client = false)
    {
        // Get module row
        $row = $this->getModuleRow();

        // Set the current view
        $this->view = new View($client ? 'tab_client_actions' : 'tab_actions', 'default');
        $this->view->base_uri = $this->base_uri;

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Load the models required by this view
        Loader::loadModels($this, ['Services']);

        // Get the service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Get the available templates
        $templates = $this->getTemplates($row, $package, $service);

        // Initialize the Vultr API
        $api = $this->getApi($row->meta->api_key);
        $instance_key = '';

        if ($package->meta->server_type == 'server') {
            $api->loadCommand('vultr_instances');
            $vultr_api = new VultrInstances($api);
            $instance_key = 'instance-id';
        } else {
            $api->loadCommand('vultr_baremetal');
            $vultr_api = new VultrBaremetal($api);
            $instance_key = 'baremetal-id';
        }

        if ($package->meta->server_type == 'server') {
            $server_details = $this->parseResponse($vultr_api->listServers(['SUBID' => $service_fields->vultr_subid]));
        } else {
            $server_details = $this->parseResponse(
                $vultr_api->listBaremetal(['SUBID' => $service_fields->vultr_subid])
            );
        }

        // Set a warning about an in progress snapshot
        $server_locked = isset($server_details->server_state) && $server_details->server_state == 'locked';
        if ($server_locked) {
            $this->setMessage('notice', Language::_('Vultr.tab_actions.server_locked', true));
        }

        // Perform actions
        if (!empty($post['action'])) {
            if ($server_locked) {
                // Return error saying that the server is locked
                $this->Input->setErrors(['api' => ['error' => Language::_('Vultr.!error.api.server_locked', true)]]);
            } else {
                switch ($post['action']) {
                    case 'restart':
                        $params = [
                            $instance_key => $service_fields->vultr_subid
                        ];
                        $this->parseResponse($vultr_api->reboot($params));
                        break;
                    case 'start':
                        $params = [
                            $instance_key => $service_fields->vultr_subid
                        ];
                        $this->parseResponse($vultr_api->start($params));
                        break;
                    case 'stop':
                        $params = [
                            $instance_key => $service_fields->vultr_subid
                        ];
                        $this->parseResponse($vultr_api->halt($params));
                        break;
                    case 'reinstall':
                        $params = [
                            $instance_key => $service_fields->vultr_subid,
                            'hostname' => $service_fields->vultr_hostname
                        ];

                        if ($package->meta->server_type !== 'server') {
                            unset($params['hostname']);
                        }

                        $this->parseResponse($vultr_api->reinstall($params));
                        break;
                    case 'change_template':
                        if ($package->meta->set_template == 'client') {
                            $data = [
                                'vultr_subid' => $service_fields->vultr_subid,
                                'vultr_template' => ($post['template'] ?? null)
                            ];
                            $this->Services->edit($service->id, $data);

                            if ($this->Services->errors()) {
                                $vars = (object) $post;
                                $this->Input->setErrors($this->Services->errors());
                            }
                        }
                        break;
                    case 'enable_ipv6':
                        if ($package->meta->server_type == 'server') {
                            $params = [
                                $instance_key => $service_fields->vultr_subid
                            ];
                            $this->log('api.vultr.com|ipv6Enable', serialize($params), 'input', true);
                            $this->parseResponse($vultr_api->ipv6Enable($params));

                            $this->Services->edit($service->id, ['vultr_enable_ipv6' => 'enable']);
                            if ($this->Services->errors()) {
                                $vars = (object) $post;
                                $this->Input->setErrors($this->Services->errors());
                            }
                        }
                        break;
                    default:
                        break;
                }
            }
        }

        // Get the server details
        $params = [
            $instance_key => $service_fields->vultr_subid
        ];
        $this->log('api.vultr.com|list', serialize($params), 'input', true);

        if ($package->meta->server_type == 'server') {
            $response = $this->parseResponse($vultr_api->get($params));
            $server_details = $response->instance ?? (object) [];
        } else {
            $response = $this->parseResponse($vultr_api->get($params));
            $server_details = $response->bare_metal ?? (object) [];
        }

        $this->view->set('module_row', $row);
        $this->view->set('package', $package);
        $this->view->set('service', $service);
        $this->view->set('service_fields', $service_fields);
        $this->view->set('templates', $templates);
        $this->view->set('ipv6_enablable', ($service_fields->vultr_enable_ipv6 !== 'enable') && $package->meta->server_type == 'server');
        $this->view->set('server_details', ($server_details ?? new stdClass()));
        $this->view->set('vars', ($vars ?? new stdClass()));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'vultr' . DS);

        return $this->view->fetch();
    }

    /**
     * Stats (Admin or Client) tab.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $post Any POST parameters
     * @param bool $client Whether to use the client view template
     * @return string The string representing the contents of this tab
     */
    private function getTabStats($package, $service, array $post = null, $client = false)
    {
        // Get module row
        $row = $this->getModuleRow();

        // Set the current view
        $this->view = new View($client ? 'tab_client_stats' : 'tab_stats', 'default');
        $this->view->base_uri = $this->base_uri;

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Get the service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Initialize the Vultr API
        $api = $this->getApi($row->meta->api_key);
        $instance_key = '';

        if ($package->meta->server_type == 'server') {
            $api->loadCommand('vultr_instances');
            $vultr_api = new VultrInstances($api);
            $instance_key = 'instance-id';
        } else {
            $api->loadCommand('vultr_baremetal');
            $vultr_api = new VultrBaremetal($api);
            $instance_key = 'baremetal-id';
        }

        // Get the server details
        $params = [
            $instance_key => $service_fields->vultr_subid
        ];
        $this->log('api.vultr.com|list', serialize($params), 'input', true);

        if ($package->meta->server_type == 'server') {
            $response = $this->parseResponse($vultr_api->get($params));
            $server_details = $response->instance ?? (object) [];
        } else {
            $response = $this->parseResponse($vultr_api->get($params));
            $server_details = $response->bare_metal ?? (object) [];
        }

        $this->view->set('module_row', $row);
        $this->view->set('package', $package);
        $this->view->set('service', $service);
        $this->view->set('service_fields', $service_fields);
        $this->view->set('server_details', $server_details);

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'vultr' . DS);

        return $this->view->fetch();
    }

    /**
     * Snapshots (Admin or Client) tab.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $post Any POST parameters
     * @param bool $client Whether to use the client view template
     * @return string The string representing the contents of this tab
     */
    private function getTabSnapshots($package, $service, array $post = null, $client = false)
    {
        // Get module row
        $row = $this->getModuleRow();

        // Set the current view
        $this->view = new View($client ? 'tab_client_snapshots' : 'tab_snapshots', 'default');
        $this->view->base_uri = $this->base_uri;

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);
        Loader::loadModels($this, ['Services']);

        // Get the service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Initialize the Vultr API
        $api = $this->getApi($row->meta->api_key);
        $api->loadCommand('vultr_instances');
        $api->loadCommand('vultr_snapshot');

        $server_api = new VultrInstances($api);
        $snapshot_api = new VultrSnapshot($api);

        // Perform actions
        if (!empty($post)) {
            switch ($post['action']) {
                case 'create':
                    $params = [
                        'instance_id' => $service_fields->vultr_subid,
                        'description' => $this->Html->safe($post['description'])
                    ];
                    $this->log('api.vultr.com|create_snapshot', serialize($params), 'input', true);
                    $result = $this->parseResponse($snapshot_api->create($params));

                    if (isset($result->snapshot->id)) {
                        $service_fields->vultr_snapshots = $service_fields->vultr_snapshots + [
                            $result->snapshot->id => $this->Html->safe($post['description'])
                        ];
                        $data = [
                            'vultr_snapshots' => $service_fields->vultr_snapshots
                        ];
                        $this->Services->edit($service->id, $data);
                        if ($this->Services->errors()) {
                            $this->Input->setErrors($this->Services->errors());
                        }
                    }

                    $vars = (object) $post;
                    break;
                case 'remove':
                    $params = [
                        'snapshot-id' => $this->Html->safe($post['snapshotid'])
                    ];
                    $this->log('api.vultr.com|remove_snapshot', serialize($params), 'input', true);
                    $this->parseResponse($snapshot_api->destroy($params));

                    unset($service_fields->vultr_snapshots[$post['snapshotid']]);

                    $data = [
                        'vultr_snapshots' => $service_fields->vultr_snapshots
                    ];
                    $this->Services->edit($service->id, $data);
                    if ($this->Services->errors()) {
                        $this->Input->setErrors($this->Services->errors());
                    }

                    $vars = (object) $post;
                    break;
                case 'restore':
                    $params = [
                        'instance-id' => $service_fields->vultr_subid,
                        'snapshot_id' => $this->Html->safe($post['snapshotid'])
                    ];
                    $this->log('api.vultr.com|restore_snapshot', serialize($params), 'input', true);
                    $this->parseResponse($server_api->restoreSnapshot($params));

                    $vars = (object) $post;
                    break;
                default:
                    break;
            }
        }

        // Get server snapshots
        $snapshots = $service_fields->vultr_snapshots;
        $response = $this->parseResponse($snapshot_api->listSnapshots());

        $vultr_snapshots = [];
        foreach ($response->snapshots ?? [] as $snapshot) {
            $vultr_snapshots[$snapshot->id] = $snapshot;
        }

        foreach ($snapshots as $id => $description) {
            if (isset($vultr_snapshots[$id])) {
                $snapshots[$id] = $vultr_snapshots[$id];
            } else {
                unset($snapshots[$id]);
            }
        }

        $this->view->set('module_row', $row);
        $this->view->set('package', $package);
        $this->view->set('service', $service);
        $this->view->set('service_fields', $service_fields);
        $this->view->set('snapshots', ($snapshots ?? []));
        $this->view->set('vars', ($vars ?? new stdClass()));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'vultr' . DS);

        return $this->view->fetch();
    }

    /**
     * Backups (Admin or Client) tab.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $post Any POST parameters
     * @param bool $client Whether to use the client view template
     * @return string The string representing the contents of this tab
     */
    private function getTabBackups($package, $service, array $post = null, $client = false)
    {
        // Get module row
        $row = $this->getModuleRow();

        // Set the current view
        $this->view = new View($client ? 'tab_client_backups' : 'tab_backups', 'default');
        $this->view->base_uri = $this->base_uri;

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Get the service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Get the service configurable options
        $service_options = $this->serviceOptionsToObject($service->options);

        // Initialize the Vultr API
        $api = $this->getApi($row->meta->api_key);
        $api->loadCommand('vultr_instances');
        $api->loadCommand('vultr_backup');

        $server_api = new VultrInstances($api);
        $backup_api = new VultrBackup($api);

        // Perform actions
        if (!empty($post)) {
            switch ($post['action']) {
                case 'schedule':
                    //
                    // TODO: Add the ability of modifying the backup schedule.
                    //
                case 'restore':
                    $params = [
                        'instance-id' => $service_fields->vultr_subid,
                        'backup_id' => $this->Html->safe($post['backupid'])
                    ];
                    $this->log('api.vultr.com|restore_backup', serialize($params), 'input', true);
                    $this->parseResponse($server_api->restoreBackup($params));

                    $vars = (object) $post;
                    break;
                default:
                    break;
            }
        }

        // Get server backups
        $params = [
            'instance_id' => $service_fields->vultr_subid
        ];
        $this->log('api.vultr.com|list', serialize($params), 'input', true);
        $response = $this->parseResponse($backup_api->listBackups($params));
        $backups = (array) $response->backups ?? [];

        $this->view->set('module_row', $row);
        $this->view->set('package', $package);
        $this->view->set('service', $service);
        $this->view->set('service_fields', $service_fields);
        $this->view->set('service_options', $service_options);
        $this->view->set('backups', $backups);
        $this->view->set('vars', ($vars ?? new stdClass()));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'vultr' . DS);

        return $this->view->fetch();
    }

    /**
     * Converts numerically indexed service options arrays into an object with member variables.
     *
     * @param  array $options A numerically indexed array of stdClass objects containing key
     *  and value member variables, or an array containing 'key' and 'value' indexes
     * @return stdClass A stdClass objects with member variables
     */
    private function serviceOptionsToObject($options)
    {
        $object = [];

        if (is_array($options) && !empty($options)) {
            foreach ($options as $option) {
                $object[$option->option_name] = $option->option_value;
            }
        }

        return (object) $object;
    }

    /**
     * Validates that the given hostname is valid.
     *
     * @param string $host_name The host name to validate
     * @return bool True if the hostname is valid, false otherwise
     */
    public function validateHostName($host_name)
    {
        $validator = new Server();
        return $validator->isDomain($host_name) || $validator->isIp($host_name);
    }

    /**
     * Validates that the given location is valid.
     *
     * @param string $location The location to validate
     * @return bool True if the location is valid, false otherwise
     */
    public function validateLocation($location)
    {
        // Fetch the 1st account from the list of accounts
        $module_row = null;
        $rows = $this->getModuleRows();

        if (isset($rows[0])) {
            $module_row = $rows[0];
        }
        unset($rows);

        $valid_locations = $this->getLocations($module_row);

        return array_key_exists(trim($location), $valid_locations);
    }

    /**
     * Validates that the given template is valid.
     *
     * @param string $template The template to validate
     * @return bool True if the template is valid, false otherwise
     */
    public function validateTemplate($template)
    {
        // Fetch the 1st account from the list of accounts
        $module_row = null;
        $rows = $this->getModuleRows();

        if (isset($rows[0])) {
            $module_row = $rows[0];
        }
        unset($rows);

        $valid_templates = $this->getTemplates($module_row);

        return array_key_exists(trim($template), $valid_templates);
    }

    /**
     * Validates that the given subid is valid.
     *
     * @param string $subid The subid to validate
     * @param string $server_type The type of server used for this service
     * @return bool True if the subid is valid, false otherwise
     */
    public function validateSubID($subid, $server_type)
    {
        Loader::loadHelpers($this, ['Form']);

        if (empty($subid)) {
            return true;
        }

        // Fetch the 1st account from the list of accounts
        $module_row = null;
        $rows = $this->getModuleRows();

        if (isset($rows[0])) {
            $module_row = $rows[0];
        }
        unset($rows);

        // Initialize the Vultr API
        $api = $this->getApi($module_row->meta->api_key);

        if ($server_type == 'server') {
            $api->loadCommand('vultr_instances');
            $vultr_api = new VultrInstances($api);
            $result = $vultr_api->listServers()->response();
        } else {
            $api->loadCommand('vultr_baremetal');
            $vultr_api = new VultrBaremetal($api);
            $result = $vultr_api->listBaremetal()->response();
        }

        $servers = $this->Form->collapseObjectArray($result->instances ?? $result->bare_metals ?? [], 'hostname', 'id');

        return array_key_exists($subid, $servers);
    }

    /**
     * Validates whether or not the connection details are valid by attempting to fetch
     * the number of accounts that currently reside on the server.
     *
     * @param string $api_key The Vultr api key
     * @return bool True if the connection is valid, false otherwise
     */
    public function validateConnection($api_key)
    {
        try {
            $api = $this->getApi($api_key);
            $api->loadCommand('vultr_account');

            $account_api = new VultrAccount($api);

            $result = $account_api->info()->response();

            return isset($result->account->email);
        } catch (Exception $e) {
            // Trap any errors encountered, could not validate connection
        }

        return false;
    }

    /**
     * Returns an array of service field to set for the service using the given input.
     *
     * @param array $vars An array of key/value input pairs
     * @param stdClass $package A stdClass object representing the package for the service
     * @return array An array of key/value pairs representing service fields
     */
    private function getFieldsFromInput(array $vars, $package)
    {
        // Fetch the correct template of the server
        if ($package->meta->set_template == 'admin') {
            $template = $package->meta->template;
        } elseif ($package->meta->set_template == 'client') {
            $template = $vars['vultr_template'] ?? null;
        }

        $template = explode('-', $template, 2);

        if (isset($template[0]) && $template[0] == 'os') {
            $osid = $template[1] ?? null;
            $appid = null;
        } else {
            $osid = 186;
            $appid = $template[1] ?? null;
        }

        // Set the fields array depending on the server type
        if ($package->meta->server_type == 'server') {
            $fields = [
                'region' => $vars['vultr_location'] ?? null,
                'plan' => $package->meta->server_plan,
                'os_id' => $osid,
                'enable_ipv6' => (($vars['vultr_enable_ipv6'] ?? null) == 'enable'),
                'backups' => ($vars['configoptions']['enable_backup'] ?? null) == 'enable' ? 'enabled' : 'disabled',
                'label' => isset($vars['vultr_hostname']) ? strtolower($vars['vultr_hostname']) : null,
                'app_id' => $appid,
                'hostname' => isset($vars['vultr_hostname']) ? strtolower($vars['vultr_hostname']) : null
            ];
        } else {
            $fields = [
                'region' => $vars['vultr_location'] ?? null,
                'plan' => $package->meta->baremetal_plan,
                'os_id' => $osid,
                'enable_ipv6' => ($vars['vultr_enable_ipv6'] == 'enable'),
                'label' => isset($vars['vultr_hostname']) ? strtolower($vars['vultr_hostname']) : null,
                'app_id' => $appid,
                'hostname' => isset($vars['vultr_hostname']) ? strtolower($vars['vultr_hostname']) : null
            ];
        }

        return $fields;
    }

    /**
     * Parses the response from the API into a stdClass object.
     *
     * @param VultrResponse $response The response from the API
     * @return stdClass A stdClass object representing the response, void if the response was an error
     */
    private function parseResponse($response)
    {
        $success = true;

        if ($response->status() == 'error') {
            $errors = $response->errors();
            $this->Input->setErrors(['api' => ['error' => $errors->error]]);
            $success = false;
        }

        // Get parsed response
        $response = $response->response();

        // Log the response
        $this->log('api.vultr.com', serialize($response), 'output', $success);

        // Return if any errors encountered
        if (!$success) {
            return;
        }

        return $response;
    }

    /**
     * Initializes the VultrApi and returns an instance of that object with the given api key.
     *
     * @param string $api_key The Vultr api key
     * @return VultrApi The VultrApi instance
     */
    private function getApi($api_key)
    {
        Loader::load(dirname(__FILE__) . DS . 'apis' . DS . 'vultr_api.php');

        $api = new VultrApi($api_key);

        return $api;
    }

    /**
     * Builds and returns the rules required to add/edit a module row (e.g. server).
     *
     * @param array $vars An array of key/value data pairs
     * @return array An array of Input rules suitable for Input::setRules()
     */
    private function getRowRules(&$vars)
    {
        $rules = [
            'account_name' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Vultr.!error.account_name_valid', true)
                ]
            ],
            'api_key' => [
                'valid' => [
                    'last' => true,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Vultr.!error.api_key_valid', true)
                ],
                'valid_connection' => [
                    'rule' => [
                        [$this, 'validateConnection']
                    ],
                    'message' => Language::_('Vultr.!error.api_key_valid_connection', true)
                ]
            ]
        ];

        return $rules;
    }

    /**
     * Builds and returns rules required to be validated when adding/editing a package.
     *
     * @param array $vars An array of key/value data pairs
     * @return array An array of Input rules suitable for Input::setRules()
     */
    private function getPackageRules(&$vars)
    {
        $rules = [
            'meta[server_type]' => [
                'valid' => [
                    'rule' => ['in_array', array_keys($this->getServerTypes())],
                    'message' => Language::_('Vultr.!error.meta[server_type].valid', true),
                ]
            ],
            'meta[baremetal_plan]' => [
                'format' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Vultr.!error.meta[baremetal_plan].format', true),
                ]
            ],
            'meta[server_plan]' => [
                'format' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Vultr.!error.meta[server_plan].format', true),
                ]
            ]
        ];

        if ($vars['meta']['server_type'] == 'server') {
            unset($rules['meta[baremetal_plan]']);
        } else {
            unset($rules['meta[server_plan]']);
        }

        return $rules;
    }
}
