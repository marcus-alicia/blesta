<?php

Loader::load(dirname(__FILE__) . DS . 'lib' . DS . 'upgrade_util.php');

/**
 * Handles the upgrade process to bring the current database up to the
 * requirements of the installed files.
 *
 * @package blesta
 * @subpackage blesta.components.upgrades
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrades extends Model
{
    /**
     * Setup
     *
     * @param array $db_info The database connection information (optional)
     */
    public function __construct($db_info = null)
    {
        if ($db_info !== null) {
            Configure::set('Blesta.database_info', $db_info);
        }

        parent::__construct($db_info);

        Loader::loadComponents($this, ['Input', 'Record']);
        Loader::loadModels($this, ['License', 'Staff', 'StaffGroups']);
    }

    /**
     * Returns all upgrade mappings
     *
     * @return array An array in key/value pairs where each key is the version from and each value is the version to
     */
    public function getMappings()
    {
        Configure::load('mappings', dirname(__FILE__) . DS . 'tasks' . DS);

        return Configure::get('Upgrade.mappings');
    }

    /**
     * Starts the upgrade process
     *
     * @param string $from The version to start the upgrade from
     * @param string $to The version to upgrade to, null to upgrade to latest version
     * @param callback $callback The callback to execute after each task in the upgrade process (for each version)
     */
    public function start($from, $to, $callback = null)
    {
        Loader::loadModels($this, ['Companies', 'PluginManager']);

        $this->License->fetchLicense();
        $license = $this->License->getLocalData();
        if ($license && array_key_exists('updates', (array)$license)) {
            $ver_compare = $this->compareVersions($from, $to);
            if ($ver_compare['diff'] != 'patch'
                && $license['updates'] !== false
                && $license['updates'] !== null
                && time() > strtotime($license['updates'])
                && (!isset($license['max_version']) || version_compare(BLESTA_VERSION, $license['max_version'], '>'))
            ) {
                $this->Input->setErrors(
                    [
                        'support' => [
                            'expired' => 'Upgrade can not continue without '
                            . 'valid support and updates, visit '
                            . 'www.blesta.com/support-and-updates'
                        ]
                    ]
                );
                return;
            }
        }

        // Ensure config is writable
        $config_file = CONFIGDIR . 'blesta.php';
        if (file_exists($config_file) && !is_writable($config_file)) {
            $this->Input->setErrors(
                [
                    'config' => [
                        'permission' => 'Config file must be writable: '
                            . $config_file
                    ]
                ]
            );
            return;
        }

        $upgrades = $this->getUpgrades($from, $to);
        $mappings = $this->getMappings();

        // Process each upgrade in the order given
        foreach ($upgrades as $from => $filename) {
            $class_name = Loader::toCamelCase(substr($filename, 0, -4));

            Loader::load(dirname(__FILE__) . DS . 'tasks' . DS . $filename);

            $upgrade = new $class_name();
            $this->processObject($upgrade, $callback);

            if (($errors = $this->Input->errors())) {
                return;
            }

            // Update the stored database version to this version
            $this->Record->duplicate('value', '=', $mappings[$from])
                ->insert(
                    'settings',
                    ['key' => 'database_version', 'value' => $mappings[$from]]
                );
        }

        $plugins_to_install = Configure::get('plugins_to_install');
        if ($plugins_to_install) {
            foreach ($plugins_to_install as $plugin_name) {
                $companies = $this->Companies->getAll();
                foreach ($companies as $company) {
                    $this->PluginManager->add(
                        ['dir' => $plugin_name, 'company_id' => $company->id, 'staff_group_id' => 1]
                    );
                }
            }
            Configure::free('plugins_to_install');
        }

        $this->processExtension('plugin', $callback);
        $this->processExtension('module', $callback);
        $this->processExtension('gateway', $callback);

        $groups = $this->StaffGroups->getAll();
        foreach ($groups as $group) {
            // Clear nav cache for this group
            $staff_members = $this->Staff->getAll(null, null, $group->id);
            foreach ($staff_members as $staff_member) {
                Cache::clearCache(
                    'nav_staff_group_' . $group->id,
                    $group->company_id . DS . 'nav' . DS . $staff_member->id . DS
                );
            }
        }
    }

    /**
     * Compares to semantic version numbers and returns the result
     *
     * @param string $a A semantic version number
     * @param string $b A semantic version number
     * @return array An array containing the comparison of the two versions, including:
     *     - major How major $a compares to $b (>, <, =)
     *     - minor How minor $a compares to $b (>, <, =)
     *     - patch How patch $a compares to $b (>, <, =)
     *     - pre How pre release $a compares to $b (>, <, =)
     *     - build How build meta data $a compare to $b (>, <, =)
     *     - diff How $a and $b differ (major, minor, patch, pre, build, null if versions are identical)
     *     - latest Either $a or $b, whichever is newer
     */
    public function compareVersions($a, $b)
    {
        $a_ver = $this->parseVersion($a);
        $b_ver = $this->parseVersion($b);

        $result = [
            'major' => null,
            'minor' => null,
            'patch' => null,
            'pre' => null,
            'build' => null,
            'diff' => null,
            'latest' => $a,
        ];

        $diff = null;
        foreach ($result as $key => $value) {
            if (array_key_exists($key, (array)$a_ver)) {
                if ($diff !== null) {
                    continue;
                }

                $cmp = strcmp($a_ver[$key], $b_ver[$key]);

                // Negate result as lack of pre has high priority
                if ($key == 'pre' && ($a_ver[$key] == '' || $b_ver[$key] == '')) {
                    $cmp *= -1;
                }

                if ($cmp == 0) {
                    $result[$key] = '=';
                } elseif ($cmp > 0) {
                    $result[$key] = '>';
                    $diff = $key;
                } elseif ($cmp < 0) {
                    $result[$key] = '<';
                    $diff = $key;
                }

                if ($diff && $cmp < 0) {
                    $result['latest'] = $b;
                }
            }
        }
        $result['diff'] = $diff;

        return $result;
    }

    /**
     * Parse the given semantic version number
     *
     * @param string $v The version to parse
     * @return array An array containing version info:
     *     - major
     *     - minor
     *     - patch
     *     - pre
     *     - build
     */
    public function parseVersion($v)
    {
        $version = [
            'major' => null,
            'minor' => null,
            'patch' => null,
            'pre' => null,
            'build' => null
        ];

        preg_match("/^([0-9]+)\.([0-9]+)\.([0-9]+)-?([0-9a-z-]*)\+?([0-9a-z-]*)$/i", $v, $matches);

        if (count($matches) >= 3) {
            $version['major'] = $matches[1];
            $version['minor'] = $matches[2];
            $version['patch'] = $matches[3];
        }

        if (!empty($matches[4])) {
            $version['pre'] = $matches[4];
        }
        if (!empty($matches[5])) {
            $version['build'] = $matches[5];
        }

        return $version;
    }

    /**
     * Generates a mapping of all files
     *
     * @param string $from The version to start the upgrade from
     * @param string $to The version to upgrade to, null to upgrade to latest version
     * @throws Exception Thrown if the required upgrade files are not present
     */
    public function getUpgrades($from, $to)
    {
        $mappings = $this->getMappings();

        $upgrades = [];
        foreach ($mappings as $start => $end) {
            if (version_compare($from, $start, '<=') && ($to === null || version_compare($to, $end, '>='))) {
                $filename = 'upgrade' . str_replace(['.', '-'], '_', $mappings[$from]);
                $upgrades[$from] = Loader::fromCamelCase($filename) . '.php';

                if (!file_exists(dirname(__FILE__) . DS . 'tasks' . DS . $upgrades[$from])) {
                    throw new Exception('Missing upgrade file: ' . $filename);
                }

                $from = $end;
            }
        }
        return $upgrades;
    }

    /**
     * Processes the given object, passes the callback to the object
     * by passing the current task count being executed and the total number
     * of tasks to be executed for that object.
     *
     * @param string $obj The full path to the SQL file to execute
     * @param callback $callback The callback to execute after each task in the upgrade process
     */
    public function processObject($obj, $callback = null)
    {
        $tasks = method_exists($obj, 'tasks') ? $obj->tasks() : [];
        $total_tasks = is_array($tasks) ? count($tasks) : 0;

        $i = 0;
        foreach ($tasks as $task) {
            if ($callback) {
                call_user_func($callback, $i, $total_tasks);
            }
            $obj->process($task);

            if (($errors = $obj->errors())) {
                $obj->rollback();
                $this->Input->setErrors($errors);
                return;
            }
            $i++;
        }

        // Finished
        if ($callback) {
            call_user_func($callback, $i, $total_tasks);
        }
    }

    /**
     * Processes the given SQL file, executes the given callback after each query
     * by passing the current query number being executed and the total number
     * of queries to be executed for that file.
     *
     * @param string $file The full path to the SQL file to execute
     * @param callback $callback The callback to execute after each query
     * @throws PDOExcetion if any query fails
     */
    public function processSql($file, $callback = null)
    {
        $queries = explode(";\n", file_get_contents($file));
        $query_count = count($queries);

        $i = 0;
        foreach ($queries as $query) {
            if ($callback) {
                call_user_func($callback, $i, $query_count);
            }

            // conserve memory
            array_shift($queries);

            if (trim($query) != '') {
                $this->query($query);
            }

            $i++;
        }

        // Finished
        if ($callback) {
            call_user_func($callback, $i, $query_count);
        }
    }

    /**
     * Processes the upgrade for each module installed in the system
     *
     * @param string The type of of extension to process:
     *     - gateway
     *     - module
     *     - plugin
     * @param callback $callback The callback to execute after each extension is processed
     */
    private function processExtension($type, $callback = null)
    {
        switch ($type) {
            case 'gateway':
                if (!isset($this->GatewayManager)) {
                    Loader::loadModels($this, ['GatewayManager']);
                }

                $manager = $this->GatewayManager;
                break;
            case 'module':
                if (!isset($this->ModuleManager)) {
                    Loader::loadModels($this, ['ModuleManager']);
                }

                $manager = $this->ModuleManager;
                break;
            case 'plugin':
                if (!isset($this->PluginManager)) {
                    Loader::loadModels($this, ['PluginManager']);
                }

                $manager = $this->PluginManager;
                break;
            default:
                return;
        }

        $extensions = $manager->getInstalled();
        $item_count = count($extensions);

        $i = 0;
        foreach ($extensions as $i => $extension) {
            if ($callback) {
                call_user_func($callback, $i, $item_count);
            }

            $manager->upgrade($extension->id);

            if (($errors = $manager->errors())) {
                $this->Input->setErrors(array_merge((array) $this->Input->errors(), $errors));
            }
        }

        // Finished
        if ($callback) {
            call_user_func($callback, ++$i, $item_count);
        }
    }

    /**
     * Return all errors
     *
     * @return array An array of errors
     */
    public function errors()
    {
        return $this->Input->errors();
    }
}
