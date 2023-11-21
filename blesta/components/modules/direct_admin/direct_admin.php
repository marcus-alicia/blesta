<?php
use Blesta\Core\Util\Validate\Server;
/**
 * DirectAdmin Module
 *
 * @package blesta
 * @subpackage blesta.components.modules.direct_admin
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class DirectAdmin extends Module
{
    /**
     * Initializes the module
     */
    public function __construct()
    {
        // Load configuration required by this module
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');

        // Load components required by this module
        Loader::loadComponents($this, ['Input']);

        // Load the language required by this module
        Language::loadLang('direct_admin', null, dirname(__FILE__) . DS . 'language' . DS);
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
        if (version_compare($current_version, '2.8.0', '<')) {
            if (!isset($this->ModuleManager)) {
                Loader::loadModels($this, ['ModuleManager']);
            }

            // Update all module rows to have a port of 2222
            $modules = $this->ModuleManager->getByClass('direct_admin');
            foreach ($modules as $module) {
                $rows = $this->ModuleManager->getRows($module->id);
                foreach ($rows as $row) {
                    $meta = (array)$row->meta;
                    $meta['port'] = '2222';
                    $this->ModuleManager->editRow($row->id, $meta);
                }
            }
        }
    }

    /**
     * Returns all tabs to display to a client when managing a service whose
     * package uses this module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @return array An array of tabs in the format of method => title.
     *  Example: ['methodName' => 'Title', 'methodName2' => 'Title2']
     */
    public function getClientTabs($package)
    {
        return [
            'tabClientActions' => Language::_('DirectAdmin.tab_client_actions', true)
        ];
    }

    /**
     * Returns an array of available service deligation order methods. The module
     * will determine how each method is defined. For example, the method "first"
     * may be implemented such that it returns the module row with the least number
     * of services assigned to it.
     *
     * @return array An array of order methods in key/value paris where the key is the
     *  type to be stored for the group and value is the name for that option
     * @see Module::selectModuleRow()
     */
    public function getGroupOrderOptions()
    {
        return [
            'roundrobin' => Language::_('DirectAdmin.order_options.roundrobin', true),
            'first' => Language::_('DirectAdmin.order_options.first', true)
        ];
    }

    /**
     * Returns all fields used when adding/editing a package, including any
     * javascript to execute when the page is rendered with these fields.
     *
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containg the fields to
     *  render as well as any additional HTML markup to include
     */
    public function getPackageFields($vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        $fields = new ModuleFields();
        $fields->setHtml("
			<script type=\"text/javascript\">
				$(document).ready(function() {
					// Re-fetch module options to pull cPanel packages and ACLs
					$('.direct_admin_type').change(function() {
						fetchModuleOptions();
					});
				});
			</script>
		");

        // Fetch all packages available for the given server or server group
        $module_row = null;
        if (isset($vars->module_group) && $vars->module_group == '') {
            if (isset($vars->module_row) && $vars->module_row > 0) {
                $module_row = $this->getModuleRow($vars->module_row);
            } else {
                $rows = $this->getModuleRows();
                if (isset($rows[0])) {
                    $module_row = $rows[0];
                }
                unset($rows);
            }
        } else {
            // Fetch the 1st server from the list of servers in the selected group
            $rows = $this->getModuleRows($vars->module_group);

            if (isset($rows[0])) {
                $module_row = $rows[0];
            }
            unset($rows);
        }

        // Set the type of account (user or reseller)
        $type = $fields->label(Language::_('DirectAdmin.package_fields.type', true), 'direct_admin_type');
        $type_user = $fields->label(
            Language::_('DirectAdmin.package_fields.type_user', true),
            'direct_admin_type_user'
        );
        $type_reseller = $fields->label(
            Language::_('DirectAdmin.package_fields.type_reseller', true),
            'direct_admin_type_reseller'
        );
        $type->attach(
            $fields->fieldRadio(
                'meta[type]',
                'user',
                (isset($vars->meta['type']) ? $vars->meta['type'] : 'user') == 'user',
                ['id' => 'direct_admin_type_user', 'class' => 'direct_admin_type'],
                $type_user
            )
        );
        $type->attach(
            $fields->fieldRadio(
                'meta[type]',
                'reseller',
                (isset($vars->meta['type']) ? $vars->meta['type'] : null) == 'reseller',
                ['id' => 'direct_admin_type_reseller', 'class' => 'direct_admin_type'],
                $type_reseller
            )
        );
        $fields->setField($type);

        $packages = [];
        if ($module_row) {
            // Fetch the packages associated with this user/reseller
            $command = ((isset($vars->meta['type']) ? $vars->meta['type'] : null) == 'reseller'
                ? 'getPackagesReseller'
                : 'getPackagesUser'
            );
            $packages = $this->getDirectAdminPackages($module_row, $command);
        }

        // Set the DirectAdmin package as a selectable option
        $package = $fields->label(Language::_('DirectAdmin.package_fields.package', true), 'direct_admin_package');
        $package->attach(
            $fields->fieldSelect(
                'meta[package]',
                $packages,
                (isset($vars->meta['package']) ? $vars->meta['package'] : null),
                ['id' => 'direct_admin_package']
            )
        );
        $fields->setField($package);

        // Set the IP
        $ip = $fields->label(Language::_('DirectAdmin.package_fields.ip', true), 'direct_admin_ip');
        if ((isset($vars->meta['type']) ? $vars->meta['type'] : null) == 'reseller') {
            $reseller_ips = [
                'shared' => Language::_('DirectAdmin.package_fields.ip_shared', true),
                'assign' => Language::_('DirectAdmin.package_fields.ip_assign', true)
            ];
            $ip->attach(
                $fields->fieldSelect(
                    'meta[ip]',
                    $reseller_ips,
                    (isset($vars->meta['ip']) ? $vars->meta['ip'] : null),
                    ['id' => 'direct_admin_ip']
                )
            );
            $fields->setField($ip);
        } else {
            // Set a list of normal user IPs available for user creation.
            if ($module_row) {
                $results = (array)$this->getDirectAdminIps($module_row);
                $ip->attach(
                    $fields->fieldSelect(
                        'meta[ip]',
                        $results,
                        (isset($vars->meta['ip']) ? $vars->meta['ip'] : null),
                        ['id' => 'direct_admin_ip']
                    )
                );
                $fields->setField($ip);
            }
        }

        return $fields;
    }

    /**
     * Returns the rendered view of the manage module page
     *
     * @param mixed $module A stdClass object representing the module and its rows
     * @param array $vars An array of post data submitted to or on the manager
     *  module page (used to repopulate fields after an error)
     * @return string HTML content containing information to display when viewing the manager module page
     */
    public function manageModule($module, array &$vars)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('manage', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'direct_admin' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

        $this->view->set('module', $module);

        return $this->view->fetch();
    }

    /**
     * Returns the rendered view of the add module row page
     *
     * @param array $vars An array of post data submitted to or on the add
     *  module row page (used to repopulate fields after an error)
     * @return string HTML content containing information to display when viewing the add module row page
     */
    public function manageAddRow(array &$vars)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('add_row', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'direct_admin' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

        // Set unspecified checkboxes
        if (!empty($vars)) {
            if (empty($vars['use_ssl'])) {
                $vars['use_ssl'] = 'false';
            }
        }

        $this->view->set('vars', (object)$vars);
        return $this->view->fetch();
    }

    /**
     * Returns the rendered view of the edit module row page
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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'direct_admin' . DS);

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

        $this->view->set('vars', (object)$vars);
        return $this->view->fetch();
    }

    /**
     * Adds the module row on the remote server. Sets Input errors on failure,
     * preventing the row from being added. Returns a set of data, which may be
     * a subset of $vars, that is stored for this module row
     *
     * @param array $vars An array of module info to add
     * @return array A numerically indexed array of meta fields for the module row containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     */
    public function addModuleRow(array &$vars)
    {
        $meta_fields = ['server_name', 'host_name', 'port', 'user_name', 'password',
            'use_ssl', 'account_limit', 'account_count', 'name_servers', 'notes'];
        $encrypted_fields = ['user_name', 'password'];

        // Set unspecified checkboxes
        if (empty($vars['use_ssl'])) {
            $vars['use_ssl'] = 'false';
        }

        // Set rules to validate against
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
     * a subset of $vars, that is stored for this module row
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
        $meta_fields = ['server_name', 'host_name', 'port', 'user_name', 'password',
            'use_ssl', 'account_limit', 'account_count', 'name_servers', 'notes'];
        $encrypted_fields = ['user_name', 'password'];

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
     * Fetches the HTML content to display when viewing the service info in the
     * admin interface.
     *
     * @param stdClass $service A stdClass object representing the service
     * @param stdClass $package A stdClass object representing the service's package
     * @return string HTML content containing information to display when viewing the service info
     */
    public function getAdminServiceInfo($service, $package)
    {
        $row = $this->getModuleRow();

        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('admin_service_info', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'direct_admin' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $this->view->set('module_row', $row);
        $this->view->set('package', $package);
        $this->view->set('service', $service);
        $this->view->set('service_fields', $this->serviceFieldsToObject($service->fields));

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
        $row = $this->getModuleRow();

        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('client_service_info', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'direct_admin' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $this->view->set('module_row', $row);
        $this->view->set('package', $package);
        $this->view->set('service', $service);
        $this->view->set('service_fields', $this->serviceFieldsToObject($service->fields));

        return $this->view->fetch();
    }

    /**
     * Client Actions (reset password)
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
        $this->view = new View('tab_client_actions', 'default');
        $this->view->base_uri = $this->base_uri;
        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Load product configuration required by this module
        Configure::load('direct_admin', dirname(__FILE__) . DS . 'config' . DS);

        $password_requirements = Configure::get('DirectAdmin.password_requirements');
        $password_length = Configure::get('DirectAdmin.password_length');
        $password_options = [];
        foreach ($password_requirements as $password_requirement) {
            foreach ($password_requirement as &$characters) {
                $parts = explode('-', $characters);
                if (count($parts) > 1 && strlen($characters) > 1) {
                    $characters = [$parts[0], $parts[1]];
                }
            }
            $password_options[] = (object)['chars' => $password_requirement, 'min' => 1];
        }
        $password_options = json_encode((object)['include' => $password_options]);

        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Perform the password reset
        if (!empty($post)) {
            Loader::loadModels($this, ['Services']);
            $data = [
                'direct_admin_password' => (isset($post['direct_admin_password']) ? $post['direct_admin_password'] : null),
                'direct_admin_confirm_password' => (isset($post['direct_admin_confirm_password']) ? $post['direct_admin_confirm_password'] : null),
            ];
            $this->Services->edit($service->id, $data);

            if ($this->Services->errors()) {
                $this->Input->setErrors($this->Services->errors());
            }

            $vars = (object)$post;
        }

        $this->view->set('password_length', $password_length);
        $this->view->set('password_options', $password_options);
        $this->view->set('service_fields', $service_fields);
        $this->view->set('service_id', $service->id);
        $this->view->set('vars', (isset($vars) ? $vars : new stdClass()));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'direct_admin' . DS);
        return $this->view->fetch();
    }

    /**
     * Returns all fields to display to an admin attempting to add a service with the module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containg the fields to render
     *  as well as any additional HTML markup to include
     */
    public function getAdminAddFields($package, $vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        // Load product configuration required by this module
        Configure::load('direct_admin', dirname(__FILE__) . DS . 'config' . DS);

        $password_requirements = Configure::get('DirectAdmin.password_requirements');
        $password_length = Configure::get('DirectAdmin.password_length');
        $password_options = [];
        foreach ($password_requirements as $password_requirement) {
            foreach ($password_requirement as &$characters) {
                $parts = explode('-', $characters);
                if (count($parts) > 1 && strlen($characters) > 1) {
                    $characters = [$parts[0], $parts[1]];
                }
            }
            $password_options[] = (object)['chars' => $password_requirement, 'min' => 1];
        }
        $password_options = json_encode((object)['include' => $password_options]);

        $fields = new ModuleFields();

        // Create domain label
        $domain = $fields->label(Language::_('DirectAdmin.service_field.domain', true), 'direct_admin_domain');
        // Create domain field and attach to domain label
        $domain->attach(
            $fields->fieldText(
                'direct_admin_domain',
                (isset($vars->direct_admin_domain) ? $vars->direct_admin_domain : null),
                ['id' => 'direct_admin_domain']
            )
        );
        // Set the label as a field
        $fields->setField($domain);

        // Create username label
        $username = $fields->label(Language::_('DirectAdmin.service_field.username', true), 'direct_admin_username');
        // Create username field and attach to username label
        $username->attach(
            $fields->fieldText(
                'direct_admin_username',
                (isset($vars->direct_admin_username) ? $vars->direct_admin_username : null),
                ['id' => 'direct_admin_username']
            )
        );
        // Set the label as a field
        $fields->setField($username);

        // Create password label
        $password = $fields->label(Language::_('DirectAdmin.service_field.password', true), 'direct_admin_password');
        // Create password field and attach to password label
        $password->attach(
            $fields->fieldPassword(
                'direct_admin_password',
                [
                    'class' => 'direct_admin_password',
                    'id' => 'direct_admin_password',
                    'value' => (isset($vars->direct_admin_password) ? $vars->direct_admin_password : null)
                ]
            )
        );
        // Set the label as a field
        $fields->setField($password);
        $fields->setHtml('<a class="generate-password"
                href="#" data-options="' . $this->Html->safe($password_options) . '"
                data-length="' . $this->Html->safe($password_length) . '"
                data-base-url="' . $this->base_uri . '" data-for-class="direct_admin_password">
            <i class="fas fa-sync-alt"></i> ' . Language::_('DirectAdmin.service_field.text_generate_password', true) .
        '</a>
        <script type="text/javascript">
            $(document).ready(function () {
                $("#direct_admin_password").parent().append($(".generate-password"));
            });
        </script>
        ');

        // Create email label
        $email = $fields->label(Language::_('DirectAdmin.service_field.email', true), 'direct_admin_email');
        // Create password field and attach to password label
        $email->attach(
            $fields->fieldText(
                'direct_admin_email',
                (isset($vars->direct_admin_email) ? $vars->direct_admin_email : null),
                ['id' => 'direct_admin_email']
            )
        );
        // Set the label as a field
        $fields->setField($email);

        return $fields;
    }

    /**
     * Returns all fields to display to an admin attempting to edit a service with the module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containg the fields to render
     *  as well as any additional HTML markup to include
     */
    public function getAdminEditFields($package, $vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        // Load product configuration required by this module
        Configure::load('direct_admin', dirname(__FILE__) . DS . 'config' . DS);

        $password_requirements = Configure::get('DirectAdmin.password_requirements');
        $password_length = Configure::get('DirectAdmin.password_length');
        $password_options = [];
        foreach ($password_requirements as $password_requirement) {
            foreach ($password_requirement as &$characters) {
                $parts = explode('-', $characters);
                if (count($parts) > 1 && strlen($characters) > 1) {
                    $characters = [$parts[0], $parts[1]];
                }
            }
            $password_options[] = (object)['chars' => $password_requirement, 'min' => 1];
        }
        $password_options = json_encode((object)['include' => $password_options]);

        $fields = new ModuleFields();


        // Create password label
        $password = $fields->label(Language::_('DirectAdmin.service_field.password', true), 'direct_admin_password');
        // Create password field and attach to password label
        $password->attach(
            $fields->fieldPassword(
                'direct_admin_password',
                [
                    'class' => 'direct_admin_password',
                    'id' => 'direct_admin_password',
                    'value' => (isset($vars->direct_admin_password) ? $vars->direct_admin_password : null)
                ]
            )
        );
        // Set the label as a field
        $fields->setField($password);
        $fields->setHtml('<a class="generate-password"
                href="#" data-options="' . $this->Html->safe($password_options) . '"
                data-length="' . $this->Html->safe($password_length) . '"
                data-base-url="' . $this->base_uri . '" data-for-class="direct_admin_password">
            <i class="fas fa-sync-alt"></i> ' . Language::_('DirectAdmin.service_field.text_generate_password', true) .
        '</a>
        <script type="text/javascript">
            $(document).ready(function () {
                $("#direct_admin_password").parent().append($(".generate-password"));
            });
        </script>
        ');

        return $fields;
    }

    /**
     * Returns all fields to display to a client attempting to add a service with the module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containg the fields to render
     *  as well as any additional HTML markup to include
     */
    public function getClientAddFields($package, $vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        $fields = new ModuleFields();

        // Create domain label
        $domain = $fields->label(Language::_('DirectAdmin.service_field.domain', true), 'direct_admin_domain');
        // Create domain field and attach to domain label
        $domain->attach(
            $fields->fieldText(
                'direct_admin_domain',
                (isset($vars->direct_admin_domain) ? $vars->direct_admin_domain : ($vars->domain ?? null)),
                ['id' => 'direct_admin_domain']
            )
        );
        // Set the label as a field
        $fields->setField($domain);

        return $fields;
    }

    /**
     * Generates a password
     *
     * @return string The generated password
     */
    private function generatePassword()
    {
        // Load product configuration required by this module
        Configure::load('direct_admin', dirname(__FILE__) . DS . 'config' . DS);
        Loader::loadHelpers($this, ['DataStructure']);
        $this->DataStructureString = $this->DataStructure->create('String');

        // Fetch and format password requirements
        $password_requirements = Configure::get('DirectAdmin.password_requirements');
        $password_length = Configure::get('DirectAdmin.password_length');

        $character_pools = [];
        foreach ($password_requirements as $password_requirement) {
            $characters = $password_requirement;
            if (count($characters) == 1) {
                foreach ($password_requirement as $character_pool) {
                    $range_ends = explode('-', $character_pool);
                    if (count($range_ends) > 1) {
                        $characters = range($range_ends[0], $range_ends[1]);
                    }
                }
            }
            $character_pools[] = implode('', $characters);
        }


        // Randomly choose the characters for the password
        $password = '';
        $minimum_characters_per_pool = 1;
        foreach ($character_pools as $pool) {
            // Randomly select characters from the current pool
            $password .= $this->DataStructureString->random($minimum_characters_per_pool, $pool);
        }

        // Select remaining characters from all the pools combined
        $password .= $this->DataStructureString->random(
            $password_length - ($minimum_characters_per_pool * count($character_pools)),
            implode('', $character_pools)
        );

        // Shuffle up all the characters so they don't just appear in the order of the pools
        return str_shuffle($password);
    }

    /**
     * Generates a username from the given host name.
     *
     * @param string $host_name The host name to use to generate the username
     * @return string The username generated from the given hostname
     */
    private function generateUsername($host_name)
    {
        // Remove everything except letters and numbers from the domain
        // ensure no number appears in the beginning
        $username = ltrim(preg_replace('/[^a-z0-9]/i', '', $host_name), '0123456789');

        $length = strlen($username);
        $pool = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $pool_size = strlen($pool);

        if ($length < 5) {
            for ($i = $length; $i < 9; $i++) {
                $username .= substr($pool, mt_rand(0, $pool_size - 1), 1);
            }
            $length = strlen($username);
        }

        $username = substr($username, 0, min($length, 8));

        // Check for an existing user account
        $row = $this->getModuleRow();

        if ($row) {
            $api = $this->getApi(
                $row->meta->host_name,
                $row->meta->user_name,
                $row->meta->password,
                ($row->meta->use_ssl == 'true'),
                $row->meta->port
            );
        }

        $account_matching_characters = 3;
        try {
            $users = $api->__call('listUsers', []);
            $resellers = $api->__call('listResellers', []);

            // Username exists, create another instead
            if (isset($users['list']) && in_array($username, $users['list'])
                || isset($resellers['list']) && in_array($username, $resellers['list'])
            ) {
                for ($i = 0; $i < (int) str_repeat(9, $account_matching_characters); $i++) {
                    $new_username = substr($username, 0, -strlen($i)) . $i;
                    if ((!isset($users['list']) || !in_array($new_username, $users['list']))
                        && (!isset($resellers['list']) || !in_array($new_username, $resellers['list']))
                    ) {
                        $username = $new_username;
                        break;
                    }
                }
            }
        } catch (Exception $e) {
            return '';
        }

        return $username;
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
     * Updates the package for the service on the remote server. Sets Input
     * errors on failure, preventing the service's package from being changed.
     *
     * @param stdClass $package_from A stdClass object representing the current package
     * @param stdClass $package_to A stdClass object representing the new package
     * @param stdClass $service A stdClass object representing the current service
     * @param stdClass $parent_package A stdClass object representing the parent
     *  service's selected package (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent
     *  service of the service being changed (if the current service is an addon service)
     * @return mixed null to maintain the existing meta fields or a numerically
     *  indexed array of meta fields to be stored for this service containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
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
        if (($row = $this->getModuleRow())) {
            $api = $this->getApi(
                $row->meta->host_name,
                $row->meta->user_name,
                $row->meta->password,
                ($row->meta->use_ssl == 'true'),
                $row->meta->port
            );

            // Only request a package change if it has changed
            if ($package_from->meta->package != $package_to->meta->package) {
                // Check whether packages are being changed between user/reseller
                if ($package_to->meta->type != $package_from->meta->type) {
                    $this->Input->setErrors(
                        [
                            'change_package' => [
                                'type' => Language::_('DirectAdmin.!error.change_package.type', true)
                            ]
                        ]
                    );
                    return;
                }

                // Get the service fields
                $service_fields = $this->serviceFieldsToObject($service->fields);

                // Set the API command
                $command = 'modifyUserPackage';
                $params = ['package' => $package_to->meta->package, 'user' => $service_fields->direct_admin_username];

                $this->log(
                    $row->meta->host_name . '|' . $command,
                    serialize([$service_fields->direct_admin_username, $package_to->meta->package]),
                    'input',
                    true
                );
                $this->parseResponse($api->__call($command, $params));
            }
        }
        return null;
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
     *  service of the service being added (if the current service is an addon
     *  service service and parent service has already been provisioned)
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
        $row = $this->getModuleRow();
        $api = $this->getApi(
            $row->meta->host_name,
            $row->meta->user_name,
            $row->meta->password,
            ($row->meta->use_ssl == 'true'),
            $row->meta->port
        );

        $params = $this->getFieldsFromInput((array)$vars, $package);

        $this->validateService($package, $vars);

        if ($this->Input->errors()) {
            return;
        }

        // Only provision the service if 'use_module' is true
        if (isset($vars['use_module']) && $vars['use_module'] == 'true') {
            $masked_params = $params;
            $masked_params['password'] = '***';

            // Set the command to be used, either to create a user or reseller
            $command = 'createUser';
            if ($package->meta->type == 'reseller') {
                $command = 'createReseller';
            }

            $this->log($row->meta->host_name . '|' . $command, serialize($masked_params), 'input', true);
            unset($masked_params);

            $result = $this->parseResponse($api->__call($command, $params));

            if ($this->Input->errors()) {
                return;
            }

            // Update the number of accounts on the server
            $this->updateAccountCount($row);
        }

        // Return service fields
        return [
            [
                'key' => 'direct_admin_domain',
                'value' => $params['domain'],
                'encrypted' => 0
            ],
            [
                'key' => 'direct_admin_username',
                'value' => $params['username'],
                'encrypted' => 0
            ],
            [
                'key' => 'direct_admin_password',
                'value' => $params['passwd'],
                'encrypted' => 1
            ],
            [
                'key' => 'direct_admin_email',
                'value' => $params['email'],
                'encrypted' => 0
            ],
            [
                'key' => 'direct_admin_ip',
                'value' => $params['ip'],
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
    public function editService(
        $package,
        $service,
        array $vars = [],
        $parent_package = null,
        $parent_service = null
    ) {
        $row = $this->getModuleRow();
        $api = $this->getApi(
            $row->meta->host_name,
            $row->meta->user_name,
            $row->meta->password,
            ($row->meta->use_ssl == 'true'),
            $row->meta->port
        );

        $service_fields = isset($service->fields) ? $this->serviceFieldsToObject($service->fields) : [];

        $params = [
            'username' => isset($service_fields->direct_admin_username)
                ? $service_fields->direct_admin_username
                : '',
            'passwd' => isset($vars['direct_admin_password'])
                ? $vars['direct_admin_password']
                : (
                    isset($service_fields->direct_admin_password)
                        ? $service_fields->direct_admin_password
                        : ''
                ),
            'passwd2' => isset($vars['direct_admin_password'])
                ? $vars['direct_admin_password']
                : (
                    isset($service_fields->direct_admin_password)
                        ? $service_fields->direct_admin_password
                        : ''
                ),
        ];

        $this->validateServiceEdit($service, $vars);

        if ($this->Input->errors()) {
            return;
        }

        // Only attempt the change in directadmin if 'use_module' is true
        if (isset($vars['use_module']) && $vars['use_module'] == 'true') {
            $masked_params = $params;
            $masked_params['passwd'] = '***';
            $masked_params['passwd2'] = '***';

            $this->log($row->meta->host_name . '|' . 'changePassword', serialize($masked_params), 'input', true);
            unset($masked_params);

            $result = $this->parseResponse($api->__call('changePassword', $params));

            if ($this->Input->errors()) {
                return;
            }
        }

        // Return service fields
        return [
            [
                'key' => 'direct_admin_domain',
                'value' => isset($service_fields->direct_admin_domain) ? $service_fields->direct_admin_domain : '',
                'encrypted' => 0
            ],
            [
                'key' => 'direct_admin_username',
                'value' => isset($service_fields->direct_admin_username)
                    ? $service_fields->direct_admin_username
                    : '',
                'encrypted' => 0
            ],
            [
                'key' => 'direct_admin_password',
                'value' => $params['passwd'],
                'encrypted' => 1
            ],
            [
                'key' => 'direct_admin_email',
                'value' => isset($service_fields->direct_admin_email) ? $service_fields->direct_admin_email : '',
                'encrypted' => 0
            ],
            [
                'key' => 'direct_admin_ip',
                'value' => isset($service_fields->direct_admin_ip) ? $service_fields->direct_admin_ip : '',
                'encrypted' => 0
            ]
        ];
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
        if (($row = $this->getModuleRow())) {
            $api = $this->getApi(
                $row->meta->host_name,
                $row->meta->user_name,
                $row->meta->password,
                ($row->meta->use_ssl == 'true'),
                $row->meta->port
            );
            $service_fields = $this->serviceFieldsToObject($service->fields);
            $command = 'suspendUser';

            // Suspend the account
            $this->log(
                $row->meta->host_name . '|' . $command,
                serialize($service_fields->direct_admin_username),
                'input',
                true
            );
            $this->parseResponse($api->__call($command, ['select0' => $service_fields->direct_admin_username]));
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
        if (($row = $this->getModuleRow())) {
            $api = $this->getApi(
                $row->meta->host_name,
                $row->meta->user_name,
                $row->meta->password,
                ($row->meta->use_ssl == 'true'),
                $row->meta->port
            );
            $service_fields = $this->serviceFieldsToObject($service->fields);
            $command = 'unsuspendUser';

            // Unsuspend the account
            $this->log(
                $row->meta->host_name . '|' . $command,
                serialize($service_fields->direct_admin_username),
                'input',
                true
            );
            $this->parseResponse($api->__call($command, ['select0' => $service_fields->direct_admin_username]));
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
        if (($row = $this->getModuleRow())) {
            $api = $this->getApi(
                $row->meta->host_name,
                $row->meta->user_name,
                $row->meta->password,
                ($row->meta->use_ssl == 'true'),
                $row->meta->port
            );
            $service_fields = $this->serviceFieldsToObject($service->fields);
            $command = 'deleteUser';

            // Delete the account
            $this->log(
                $row->meta->host_name . '|' . $command,
                serialize($service_fields->direct_admin_username),
                'input',
                true
            );
            $this->parseResponse($api->__call($command, ['select0' => $service_fields->direct_admin_username]));

            // Update the number of accounts on the server
            $this->updateAccountCount($row);
        }
        return null;
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
        $this->Input->setRules($this->getServiceRules($vars));
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
        $this->Input->setRules($this->getServiceRules($vars, true));
        return $this->Input->validates($vars);
    }

    /**
     * Returns the rule set for adding/editing a service
     *
     * @param array $vars A list of input vars
     * @param bool $edit True to get the edit rules, false for the add rules
     * @return array Service rules
     */
    private function getServiceRules(array $vars = null, $edit = false)
    {
        // Validate the service fields
        $rules = [
            'direct_admin_domain' => [
                'format' => [
                    'rule' => [[$this, 'validateHostName']],
                    'message' => Language::_('DirectAdmin.!error.direct_admin_domain.format', true)
                ]
            ],
            'direct_admin_username' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['matches', '/^[a-z0-9]*$/i'],
                    'message' => Language::_('DirectAdmin.!error.direct_admin_username.format', true)
                ],
                'length' => [
                    'if_set' => true,
                    'rule' => ['betweenLength', 4, 8],
                    'message' => Language::_('DirectAdmin.!error.direct_admin_username.length', true)
                ]
            ],
            'direct_admin_password' => [
                'format' => [
                    'if_set' => $edit,
                    'rule' => function ($password) {
                        Configure::load('direct_admin', dirname(__FILE__) . DS . 'config' . DS);

                        if (strlen($password) < Configure::get('DirectAdmin.password_length')) {
                            return false;
                        }

                        $password_requirements = Configure::get('DirectAdmin.password_requirements');
                        foreach ($password_requirements as $characters) {
                            $escape_characters = [
                                '\\', '^', '$', '.', '[', ']', '|', '(',
                                ')', '?', '*', '+', '{', '}', '\'', '/'
                            ];
                            foreach ($characters as &$character) {
                                if (in_array($character, $escape_characters)) {
                                    $character = '\\' . $character;
                                }
                            }

                            $character_string = implode('|', $characters);
                            if (preg_match('/^.*[' . $character_string . ']+.*$/', $password)) {
                                continue;
                            }

                            return false;
                        }
                        return true;
                    },
                    'message' => Language::_('DirectAdmin.!error.direct_admin_password.format', true)
                ]
            ],
            'direct_admin_confirm_password' => [
                'matches' => [
                    'if_set' => true,
                    'rule' => [
                        'compares',
                        '==',
                        ($vars['direct_admin_password'] ?? '')
                    ],
                    'message' => Language::_('DirectAdmin.!error.direct_admin_password.matches', true)
                ]
            ],
            'direct_admin_email' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'isEmail',
                    'message' => Language::_('DirectAdmin.!error.direct_admin_email.format', true)
                ]
            ]
        ];

        // Unset irrelevant rules when editing a service
        if ($edit) {
            $edit_fields = ['direct_admin_password', 'direct_admin_confirm_password'];

            foreach ($rules as $field => $rule) {
                if (!in_array($field, $edit_fields)) {
                    unset($rules[$field]);
                }
            }
        }

        // Set the values that may be empty
        $empty_values = ['direct_admin_username', 'direct_admin_password', 'direct_admin_email'];

        // Remove rules on empty fields
        foreach ($empty_values as $value) {
            if (empty($vars[$value])) {
                unset($rules[$value]);
            }
        }

        return $rules;
    }

    /**
     * Validates that the given hostname is valid
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
     * Validates that at least 2 name servers are set in the given array of name servers
     *
     * @param array $name_servers An array of name servers
     * @return bool True if the array count is >=2, false otherwise
     */
    public function validateNameServerCount($name_servers)
    {
        if (is_array($name_servers) && count($name_servers) >= 2) {
            return true;
        }
        return false;
    }

    /**
     * Validates that the nameservers given are formatted correctly
     *
     * @param array $name_servers An array of name servers
     * @return bool True if every name server is formatted correctly, false otherwise
     */
    public function validateNameServers($name_servers)
    {
        if (is_array($name_servers)) {
            foreach ($name_servers as $name_server) {
                if (!$this->validateHostName($name_server)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Initializes the DirectAdminApi and returns an instance of that object with the given $host, $user, and $pass set
     *
     * @param string $host The host to the cPanel server
     * @param string $user The user to connect as
     * @param string $pass The hash-pased password to authenticate with
     * @param bool $use_ssl True to use SSL, false otherwise
     * @param string $port The port to connect on
     * @return DirectAdminApi The DirectAdminApi instance
     */
    private function getApi($host, $user, $pass, $use_ssl = false, $port = '2222')
    {
        Loader::load(dirname(__FILE__) . DS . 'apis' . DS . 'direct_admin_api.php');

        $api = new DirectAdminApi();
        $api->setUrl('http' . ($use_ssl ? 's' : '') . '://' . $host, $port);
        $api->setUser($user);
        $api->setPass($pass);

        return $api;
    }

    /**
     * Parses the response from the API into a stdClass object
     *
     * @param array $response The response from the API
     * @param bool $return_response Whether to return the response, regardless of error
     * @return stdClass A stdClass object representing the response, void if the response was an error
     */
    private function parseResponse($response, $return_response = false)
    {
        $row = $this->getModuleRow();
        $success = true;
        $invalid_response = false;

        // Check for an invalid HTML response from the module
        if (is_array($response) && count($response) == 1) {
            foreach ($response as $key => $value) {
                // Invalid response
                if (preg_match('/<html>/', $key) || preg_match('/<html>/', $value)) {
                    $invalid_response = true;
                    break;
                }
            }
        }

        // Set an internal error on no response or invalid response
        if (empty($response) || $invalid_response) {
            $this->Input->setErrors(['api' => ['internal' => Language::_('DirectAdmin.!error.api.internal', true)]]);
            $success = false;
        }

        // Set an error if given
        if (isset($response['error']) && $response['error'] == '1') {
            $error = (isset($response['text'])
                ? $response['text']
                : Language::_('DirectAdmin.!error.api.internal', true)
            );
            $this->Input->setErrors(['api' => ['error' => $error]]);
            $success = false;
        }

        // Log the response
        $this->log($row->meta->host_name, serialize($response), 'output', $success);

        // Return if any errors encountered
        if (!$success && !$return_response) {
            return;
        }

        return $response;
    }

    /**
     * Returns an array of service field to set for the service using the given input
     *
     * @param array $vars An array of key/value input pairs
     * @param stdClass $package A stdClass object representing the package for the service
     * @return array An array of key/value pairs representing service fields
     */
    private function getFieldsFromInput(array $vars, $package)
    {
        Loader::loadModels($this, ['Clients']);
        if (empty($vars['direct_admin_email'])
            && isset($vars['client_id'])
            && ($client = $this->Clients->get($vars['client_id']))
        ) {
            $vars['direct_admin_email'] = $client->email;
        }

        $domain = isset($vars['direct_admin_domain']) ? strtolower($vars['direct_admin_domain']) : null;
        $username = !empty($vars['direct_admin_username'])
            ? $vars['direct_admin_username']
            : $this->generateUsername($domain);
        $password = !empty($vars['direct_admin_password']) ? $vars['direct_admin_password'] : $this->generatePassword();
        $fields = [
            'domain' => $domain,
            'username' => $username,
            'passwd' => $password,
            'passwd2' => $password,
            'email' => isset($vars['direct_admin_email']) ? $vars['direct_admin_email'] : null,
            'ip' => isset($package->meta->ip) ? $package->meta->ip : null,
            'package' => isset($package->meta->package) ? $package->meta->package : null
        ];

        return $fields;
    }

    /**
     * Retrieves the accounts on the server
     *
     * @param stdClass $api The DirectAdmin API
     * @return mixed The number of accounts on the server, or null on error
     */
    private function getAccountCount($api, $user)
    {
        $user_type = '';

        // Get account info on this user
        try {
            // Fetch the account information
            $response = $api->__call('getUserConfig', ['user' => $user]);

            if ($response && is_array($response) && array_key_exists('usertype', $response)) {
                $user_type = $response['usertype'];
            }
        } catch (Exception $e) {
            return;
        }

        // Determine how many user accounts exist under this user
        if (in_array($user_type, ['reseller', 'admin'])) {
            try {
                $action = ($user_type == 'admin' ? 'listUsers' : 'listUsersByReseller');

                // Fetch the users on the server
                $response = $api->__call($action, []);

                // Users are set in 'list'
                $list = (isset($response['list']) ? (array) $response['list'] : []);

                return count($list);
            } catch (Exception $e) {
                // API request failed
            }
        }
    }

    /**
     * Updates the module row meta number of accounts
     *
     * @param stdClass $module_row A stdClass object representing a single server
     */
    private function updateAccountCount($module_row)
    {
        $api = $this->getApi(
            $module_row->meta->host_name,
            $module_row->meta->user_name,
            $module_row->meta->password,
            ($module_row->meta->use_ssl == 'true'),
            $module_row->meta->port
        );

        // Get the number of accounts on the server
        if (($count = $this->getAccountCount($api, $module_row->meta->user_name))) {
            // Update the module row account list
            Loader::loadModels($this, ['ModuleManager']);
            $vars = $this->ModuleManager->getRowMeta($module_row->id);

            if ($vars) {
                $vars->account_count = $count;
                $vars = (array)$vars;

                $this->ModuleManager->editRow($module_row->id, $vars);
            }
        }
    }

    /**
     * Fetches a listing of all packages configured in DirectAdmin for the given server
     *
     * @param stdClass $module_row A stdClass object representing a single server
     * @param string $command The API command to call, either getPackagesUser, or getPackagesReseller
     * @return array An array of packages in key/value pairs
     */
    private function getDirectAdminPackages($module_row, $command)
    {
        $api = $this->getApi(
            $module_row->meta->host_name,
            $module_row->meta->user_name,
            $module_row->meta->password,
            ($module_row->meta->use_ssl == 'true'),
            $module_row->meta->port
        );

        $this->log($module_row->meta->host_name . '|' . $command, null, 'input', true);

        try {
            // Fetch the packages
            $response = $api->__call($command, []);
            $this->log(
                $module_row->meta->host_name . '|' . $command,
                serialize($response),
                'output',
                !empty($response)
            );

            // Packages are set in 'list'
            $list = (isset($response['list']) ? $response['list'] : []);
            $packages = [];

            // Assign the key/value for each package
            foreach ($list as $key => $value) {
                $packages[$value] = $value;
            }

            return $packages;
        } catch (Exception $e) {
            // API request failed
            $message = $e->getMessage();
            $this->log($module_row->meta->host_name . '|' . $command, serialize($message), 'output', false);
        }
    }

    /**
     * Fetches a listing of all IPs configured in DirectAdmin
     *
     * @param stdClass $module_row A stdClass object represinting a single server
     * @return array An array of ips in key/value pairs
     */
    private function getDirectAdminIps($module_row)
    {
        $api = $this->getApi(
            $module_row->meta->host_name,
            $module_row->meta->user_name,
            $module_row->meta->password,
            ($module_row->meta->use_ssl == 'true'),
            $module_row->meta->port
        );

        $command = 'getResellerIps';
        $this->log($module_row->meta->host_name . '|' . $command, null, 'input', true);

        try {
            // Fetch the IPs
            $response = $api->__call($command, []);
            $this->log(
                $module_row->meta->host_name . '|' . $command,
                serialize($response),
                'output',
                ($response != 'error=1')
            );

            // IPs are set in 'list'
            $list = (isset($response['list']) ? $response['list'] : []);
            $ips = [];

            // Assign the key/value for each IP
            foreach ($list as $key => $value) {
                $ips[$value] = $value;
            }

            return $ips;
        } catch (Exception $e) {
            // API request failed
            $message = $e->getMessage();
            $this->log($module_row->meta->host_name . '|' . $command, serialize($message), 'output', false);
        }
    }

    /**
     * Builds and returns the rules required to add/edit a module row (e.g. server)
     *
     * @param array $vars An array of key/value data pairs
     * @return array An array of Input rules suitable for Input::setRules()
     */
    private function getRowRules($vars)
    {
        $rules = [
            'server_name' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('DirectAdmin.!error.server_name.empty', true)
                ]
            ],
            'host_name' => [
                'format' => [
                    'rule' => [[$this, 'validateHostName']],
                    'message' => Language::_('DirectAdmin.!error.host_name.format', true)
                ]
            ],
            'port' => [
                'format' => [
                    'rule' => 'is_numeric',
                    'message' => Language::_('DirectAdmin.!error.port.format', true)
                ]
            ],
            'user_name' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('DirectAdmin.!error.user_name.empty', true)
                ]
            ],
            'password' => [
                'format' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('DirectAdmin.!error.password.format', true)
                ]
            ],
            'use_ssl' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['in_array', ['true', 'false']],
                    'message' => Language::_('DirectAdmin.!error.use_ssl.format', true)
                ]
            ],
            'account_limit' => [
                'valid' => [
                    'rule' => ['matches', '/^([0-9]+)?$/'],
                    'message' => Language::_('DirectAdmin.!error.account_limit.valid', true)
                ]
            ],
            'name_servers' => [
                'count' => [
                    'rule' => [[$this, 'validateNameServerCount']],
                    'message' => Language::_('DirectAdmin.!error.name_servers.count', true)
                ],
                'valid' => [
                    'rule' => [[$this, 'validateNameServers']],
                    'message' => Language::_('DirectAdmin.!error.name_servers.valid', true)
                ]
            ]
        ];

        return $rules;
    }

    /**
     * Builds and returns rules required to be validated when adding/editing a package
     *
     * @param array $vars An array of key/value data pairs
     * @return array An array of Input rules suitable for Input::setRules()
     */
    private function getPackageRules($vars)
    {
        $rules = [
            'meta[type]' => [
                'format' => [
                    'rule' => ['matches', '/^(user|reseller)$/'],
                    // type must be user or reseller
                    'message' => Language::_('DirectAdmin.!error.meta[type].format', true),
                ]
            ],
            'meta[package]' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    // package must be given
                    'message' => Language::_('DirectAdmin.!error.meta[package].empty', true)
                ]
            ],
            'meta[ip]' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    // IP address is required
                    'message' => Language::_('DirectAdmin.!error.meta[ip].empty', true)
                ]
            ]
        ];

        return $rules;
    }
}
