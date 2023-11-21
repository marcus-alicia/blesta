<?php
/**
 * Multicraft Module actions
 *
 * @package blesta
 * @subpackage blesta.components.modules.multicraft.lib
 * @copyright Copyright (c) 2014, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class MulticraftModule
{
    /**
     * The module configuration file
     */
    private $config;

    /**
     * Initialize
     */
    public function __construct()
    {
        // Load required components
        Loader::loadComponents($this, ['Input']);
    }

    /**
     * Sets the module configuration
     *
     * @param stdClass $config The module configuration settings
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }

    /**
     * Retrieves a list of Input errors, if any
     */
    public function errors()
    {
        return $this->Input->errors();
    }

    /**
     * Fetches the module keys usable in email tags
     *
     * @return array A list of module email tags
     */
    public function getEmailTags()
    {
        return ['panel_url', 'panel_api_url', 'daemons', 'ips', 'ips_in_use'];
    }

    /**
     * Performs any necessary bootstraping actions. Sets Input errors on
     * failure, preventing the module from being added.
     *
     * @return array A numerically indexed array of meta data containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     */
    public function install()
    {
        if (!function_exists('json_encode')) {
            $this->Input->setErrors(
                ['json' => ['unavailable' => Language::_('MulticraftModule.!error.json.unavailable', true)]]
            );
        }
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
        // Fetch the module version
        $version = $this->getVersion();
        if ($version === false) {
            return;
        }

        // Upgrade if possible
        if (version_compare($version, $current_version, '>')) {
            // Update module meta fields to reformat how IPs are stored
            if (version_compare($current_version, '2.0.1', '<')) {
                $this->upgrade2_0_1();
            }
        }
    }

    /**
     * Retrieves the current module version
     *
     * @return string|bool The module version, or false if not known
     */
    private function getVersion()
    {
        if ($this->config && is_object($this->config) && isset($this->config->version)) {
            return $this->config->version;
        }

        return false;
    }

    /**
     * Adds the module row on the remote server. Sets Input errors on failure,
     * preventing the row from being added.
     *
     * @param array $vars An array of module info to add
     * @return array A numerically indexed array of meta fields for the module row containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     */
    public function addRow(array &$vars)
    {
        $meta_fields = [
            'server_name',
            'panel_url',
            'panel_api_url',
            'username',
            'key',
            'daemons',
            'ips',
            'ips_in_use',
            'log_all'
        ];
        $encrypted_fields = ['username', 'key'];

        if (!isset($vars['log_all'])) {
            $vars['log_all'] = '0';
        }

        // Remove dedicated IPs if not set to use any
        if (isset($vars['daemons']) && is_array($vars['daemons'])
            && count($vars['daemons']) == 1 && empty($vars['daemons'][0])
            && isset($vars['ips']) && is_array($vars['ips'])
            && count($vars['ips']) == 1 && empty($vars['ips'][0])
        ) {
            unset($vars['daemons'], $vars['ips']);
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
     * Validates that each of the given IP addresses matches a given daemon
     *
     * @param array $ips A numerically-indexed array of IP addresses, whose index matches the given daemons
     * @param array $daemons A numerically-indexed array of daemons, whose index matches the given IPs
     * @param array $ips_in_use A numerically-indexed array signifying
     *  whether this IP is in use, whose index matches IPs
     * @return bool True if each IP address matches a given daemon; false otherwise
     */
    public function validateIpsMatchDaemons($ips, $daemons, $ips_in_use)
    {
        // Set rule to validate IP addresses
        $range = '(?:25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9][0-9]|[0-9])';
        $ip_address_rule = '/^(?:' . $range . "\." . $range . "\." . $range . "\." . $range . ')$/';

        if (!empty($ips) && !is_array($ips)) {
            return false;
        }

        // Validate an IP is set for each daemon
        if (is_array($daemons) && is_array($ips_in_use)) {
            foreach ($daemons as $index => $daemon_id) {
                if (empty($ips[$index]['ip'])
                    || !isset($ips_in_use[$index])
                    || !preg_match($ip_address_rule, $ips[$index]['ip'])
                ) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Validates that each of the given daemons matches a given IP address
     *
     * @param array $daemons A numerically-indexed array of daemons, whose index matches the given IPs
     * @param array $ips A numerically-indexed array of IP addresses, whose index matches the given daemons
     * @param array $ips_in_use A numerically-indexed array signifying
     *  whether this IP is in use, whose index matches IPs
     * @return bool True if each daemon matches a given IP address; false otherwise
     */
    public function validateDaemonsMatchIps($daemons, $ips, $ips_in_use)
    {
        if (!empty($daemons) && !is_array($daemons)) {
            return false;
        }

        // Validate a deamon is set for each IP
        if (is_array($ips) && is_array($ips_in_use)) {
            foreach ($ips as $index => $ip) {
                if (empty($daemons[$index]) || !isset($ips_in_use[$index])
                    || !preg_match('/^[0-9]+$/', $daemons[$index])
                ) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Validates that each of the given IPs-in-use fields matches a given IP address
     *
     * @param array $ips_in_use A numerically-indexed array signifying
     *  whether this IP is in use, whose index matches IPs
     * @param array $daemons A numerically-indexed array of daemons, whose index matches the given IPs
     * @param array $ips A numerically-indexed array of IP addresses, whose index matches the given daemons
     * @return bool True if each daemon matches a given IP address; false otherwise
     */
    public function validateIpsInUseMatchIps($ips_in_use, $ips, $daemons)
    {
        if (!empty($ips_in_use) && !is_array($ips_in_use)) {
            return false;
        }

        // Validate value is set for each IP in use
        if (is_array($ips) && is_array($daemons)) {
            foreach ($ips_in_use as $index => $value) {
                if (!in_array($value, ['0', '1'])) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Builds and returns the rules required to add/edit a module row (e.g. server)
     *
     * @param array $vars An array of key/value data pairs
     * @return array An array of Input rules suitable for Input::setRules()
     */
    private function getRowRules(&$vars)
    {
        $ips_required = (isset($vars['ips']) || isset($vars['daemons']));

        return [
            'server_name' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('MulticraftModule.!error.server_name.empty', true)
                ]
            ],
            'panel_url' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('MulticraftModule.!error.panel_url.empty', true)
                ]
            ],
            'panel_api_url' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('MulticraftModule.!error.panel_api_url.empty', true)
                ]
            ],
            'username' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('MulticraftModule.!error.username.empty', true)
                ]
            ],
            'key' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('MulticraftModule.!error.key.empty', true)
                ]
            ],
            'log_all' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['in_array', ['0', '1']],
                    'message' => Language::_('MulticraftModule.!error.log_all.format', true)
                ]
            ],
            'ips' => [
                'match' => [
                    'if_set' => $ips_required,
                    'rule' => [
                        [$this, 'validateIpsMatchDaemons'],
                        (isset($vars['daemons']) ? $vars['daemons'] : []),
                        (isset($vars['ips_in_use']) ? $vars['ips_in_use'] : [])
                    ],
                    'message' => Language::_('MulticraftModule.!error.ips.match', true)
                ]
            ],
            'daemons' => [
                'match' => [
                    'if_set' => $ips_required,
                    'rule' => [
                        [$this, 'validateDaemonsMatchIps'],
                        (isset($vars['ips']) ? $vars['ips'] : []),
                        (isset($vars['ips_in_use']) ? $vars['ips_in_use'] : [])
                    ],
                    'message' => Language::_('MulticraftModule.!error.daemons.match', true)
                ]
            ],
            'ips_in_use' => [
                'match' => [
                    'rule' => [
                        [$this, 'validateIpsInUseMatchIps'],
                        (isset($vars['ips']) ? $vars['ips'] : []),
                        (isset($vars['daemons']) ? $vars['daemons'] : [])
                    ],
                    'message' => Language::_('MulticraftModule.!error.ips_in_use.match', true)
                ]
            ]
        ];
    }

    /**
     * Upgrades Multicraft to v2.0.1
     * Updates module row meta data to the new dedicated IP address format
     */
    private function upgrade2_0_1()
    {
        Loader::loadComponents($this, ['Record']);
        Loader::loadModels($this, ['ModuleManager']);

        $modules = $this->Record->select()
            ->from('modules')
            ->where('class', '=', 'multicraft')
            ->fetchAll();

        foreach ($modules as $module) {
            $rows = $this->ModuleManager->getRows($module->id);

            foreach ($rows as $row) {
                // Must have meta data
                if (empty($row->meta) || empty($row->meta->ips)) {
                    continue;
                }

                // Update the IPs to their new format
                foreach ($row->meta->ips as $index => &$ip) {
                    // Skip IPs that already in the new format
                    if (is_array($ip)) {
                        continue;
                    }

                    // We don't know the port used for the dedicated IP
                    // so try to determine it from the service that uses it
                    $daemon_id = (isset($row->meta->daemons)
                        && isset($row->meta->daemons[$index])
                        ? $row->meta->daemons[$index]
                        : null
                    );
                    $port = (isset($row->meta->ips_in_use)
                        && isset($row->meta->ips_in_use[$index])
                        && $row->meta->ips_in_use[$index] == "1"
                        ? $this->getServicePort($row->id, $ip, $daemon_id)
                        : '25565'
                    );

                    $ip = [
                        'ip' => $ip,
                        'port' => $port
                    ];
                }

                // Update the module row meta data
                $this->ModuleManager->editRow($row->id, (array)$row->meta);
            }
        }
    }

    /**
     * Retrieves the multicraft port in use for the given module and IP address
     *
     * @param int $module_row_id The ID of the module row being used
     * @param string $ip The IP address to check
     * @param int $daemon_id The ID of the daemon
     */
    private function getServicePort($module_row_id, $ip, $daemon_id)
    {
        if (!isset($this->Record)) {
            Loader::loadComponents($this, ['Record']);
        }

        if (empty($module_row_id) || empty($ip) || empty($daemon_id)) {
            return;
        }

        // Create a subquery to fetch the services that have this daemon
        $this->Record->select(['sf.service_id'])
            ->from(['service_fields' => 'sf'])
            ->innerJoin(['services' => 's'], 's.id', '=', 'sf.service_id', false)
            ->where('s.module_row_id', '=', $module_row_id)
            ->where('sf.key', '=', 'multicraft_daemon_id')
            ->where('sf.value', '=', $daemon_id)
            ->where('s.status', '!=', 'canceled');
        $subquery = $this->Record->get();
        $values = $this->Record->values;
        $this->Record->reset();

        // Fetch the most recent service using the given IP and daemon for this module row
        $service = $this->Record->select(['service_fields.service_id'])
            ->from('service_fields')
            ->innerJoin('services', 'services.id', '=', 'service_fields.service_id', false)
            ->innerJoin([$subquery => 'sd'], 'sd.service_id', '=', 'services.id', false)
            ->appendValues($values)
            ->where('services.module_row_id', '=', $module_row_id)
            ->where('service_fields.key', '=', 'multicraft_ip')
            ->where('service_fields.value', '=', $ip)
            ->where('services.status', '!=', 'canceled')
            ->group(['service_fields.service_id'])
            ->order(['service_fields.service_id' => 'desc'])
            ->fetch();

        if (!$service) {
            return;
        }

        // Fetch the configured port for the service
        $port = $this->Record->select(['service_fields.value'])
            ->from('service_fields')
            ->where('service_id', '=', $service->service_id)
            ->where('key', '=', 'multicraft_port')
            ->fetch();

        return ($port ? $port->value : null);
    }
}
