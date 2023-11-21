<?php
use Blesta\Core\Util\Validate\Server;
/**
 * Cpanel Module
 *
 * @package blesta
 * @subpackage blesta.components.modules.cpanel
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Cpanel extends Module
{
    /**
     * @var int The length of a cPanel api token
     */
    private static $token_length = 32;

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
        Language::loadLang('cpanel', null, dirname(__FILE__) . DS . 'language' . DS);
    }

    /**
     * Returns all tabs to display to an admin when managing a service whose
     * package uses this module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @return array An array of tabs in the format of method => title.
     *  Example: array('methodName' => "Title", 'methodName2' => "Title2")
     */
    public function getAdminTabs($package)
    {
        return [
            'tabStats' => Language::_('Cpanel.tab_stats', true)
        ];
    }

    /**
     * Returns all tabs to display to a client when managing a service whose
     * package uses this module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @return array An array of tabs in the format of method => title.
     *  Example: array('methodName' => "Title", 'methodName2' => "Title2")
     */
    public function getClientTabs($package)
    {
        return [
            'tabClientActions' => Language::_('Cpanel.tab_client_actions', true),
            'tabClientStats' => Language::_('Cpanel.tab_client_stats', true)
        ];
    }

    /**
     * Returns an array of available service deligation order methods. The module
     * will determine how each method is defined. For example, the method "first"
     * may be implemented such that it returns the module row with the least number
     * of services assigned to it.
     *
     * @return array An array of order methods in key/value paris where the key is
     *  the type to be stored for the group and value is the name for that option
     * @see Module::selectModuleRow()
     */
    public function getGroupOrderOptions()
    {
        return [
            'roundrobin' => Language::_('Cpanel.order_options.roundrobin', true),
            'first' => Language::_('Cpanel.order_options.first', true)
        ];
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
					// Set whether to show or hide the ACL option
					$('#cpanel_acl').closest('li').hide();
					$('#cpanel_account_limit').closest('li').hide();
					if ($('input[name=\"meta[type]\"]:checked').val() == 'reseller') {
						$('#cpanel_acl').closest('li').show();
						$('#cpanel_account_limit').closest('li').show();
					}
					$('input[name=\"meta[type]\"]').change(function() {
						if ($(this).val() == 'reseller') {
							$('#cpanel_acl').closest('li').show();
							$('#cpanel_account_limit').closest('li').show();
						} else {
							$('#cpanel_acl').closest('li').hide();
							$('#cpanel_account_limit').closest('li').hide();
						}
					});

					// Set whether to show or hide the Sub-Domains option
					$('#cpanel_domains_list').closest('li').hide();
					if ($('input[name=\"meta[sub_domains]\"]:checked').val() == 'enable') {
						$('#cpanel_domains_list').closest('li').show();
					}
					$('input[name=\"meta[sub_domains]\"]').change(function() {
						if ($(this).val() == 'enable') {
							$('#cpanel_domains_list').closest('li').show();
						} else {
							$('#cpanel_domains_list').closest('li').hide();
						}
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

        $packages = [];
        $acls = ['' => Language::_('Cpanel.package_fields.acl_default', true)];

        if ($module_row) {
            $packages = $this->getCpanelPackages($module_row);
            $acls = $acls + $this->getCpanelAcls($module_row);
        }

        // Set the cPanel package as a selectable option
        $package = $fields->label(Language::_('Cpanel.package_fields.package', true), 'cpanel_package');
        $package->attach(
            $fields->fieldSelect(
                'meta[package]',
                $packages,
                (isset($vars->meta['package']) ? $vars->meta['package'] : null),
                ['id' => 'cpanel_package']
            )
        );
        $fields->setField($package);

        // Set the type of account (standard or reseller)
        if ($module_row && $module_row->meta->user_name == 'root') {
            $type = $fields->label(Language::_('Cpanel.package_fields.type', true), 'cpanel_type');
            $type_standard = $fields->label(
                Language::_('Cpanel.package_fields.type_standard', true),
                'cpanel_type_standard'
            );
            $type_reseller = $fields->label(
                Language::_('Cpanel.package_fields.type_reseller', true),
                'cpanel_type_reseller'
            );
            $type->attach(
                $fields->fieldRadio(
                    'meta[type]',
                    'standard',
                    (isset($vars->meta['type']) ? $vars->meta['type'] : 'standard') == 'standard',
                    ['id' => 'cpanel_type_standard'],
                    $type_standard
                )
            );
            $type->attach(
                $fields->fieldRadio(
                    'meta[type]',
                    'reseller',
                    (isset($vars->meta['type']) ? $vars->meta['type'] : null) == 'reseller',
                    ['id' => 'cpanel_type_reseller'],
                    $type_reseller
                )
            );
            $fields->setField($type);
        } else {
            // Reseller must use the standard account type
            $type = $fields->fieldHidden('meta[type]', 'standard');
            $fields->setField($type);
        }

        // Set the cPanel package as a selectable option
        $acl = $fields->label(Language::_('Cpanel.package_fields.acl', true), 'cpanel_acl');
        $acl->attach(
            $fields->fieldSelect(
                'meta[acl]',
                $acls,
                (isset($vars->meta['acl']) ? $vars->meta['acl'] : null),
                ['id' => 'cpanel_acl']
            )
        );
        $fields->setField($acl);

        // Set the account limit for resellers
        $account_limit = $fields->label(
            Language::_('Cpanel.package_fields.account_limit', true),
            'cpanel_account_limit'
        );
        $account_limit->attach(
            $fields->fieldText(
                'meta[account_limit]',
                (isset($vars->meta['account_limit']) ? $vars->meta['account_limit'] : null),
                ['id' => 'cpanel_account_limit']
            )
        );
        $fields->setField($account_limit);

        // Set whether to use a sub_domain
        $sub_domains = $fields->label(
            Language::_('Cpanel.package_fields.sub_domains', true),
            'cpanel_sub_domains'
        );
        $sub_domains_disable = $fields->label(
            Language::_('Cpanel.package_fields.sub_domains_disable', true),
            'cpanel_sub_domains_disable'
        );
        $sub_domains_enable = $fields->label(
            Language::_('Cpanel.package_fields.sub_domains_enable', true),
            'cpanel_sub_domains_enable'
        );
        $sub_domains->attach(
            $fields->fieldRadio(
                'meta[sub_domains]',
                'disable',
                (isset($vars->meta['sub_domains']) ? $vars->meta['sub_domains'] : 'disable') == 'disable',
                ['id' => 'cpanel_sub_domains_disable'],
                $sub_domains_disable
            )
        );
        $sub_domains->attach(
            $fields->fieldRadio(
                'meta[sub_domains]',
                'enable',
                (isset($vars->meta['sub_domains']) ? $vars->meta['sub_domains'] : null) == 'enable',
                ['id' => 'cpanel_sub_domains_enable'],
                $sub_domains_enable
            )
        );
        $fields->setField($sub_domains);

        // Set the domains to be used for sub-domains accounts
        $domains_list = $fields->label(
            Language::_('Cpanel.package_fields.domains_list', true),
            'cpanel_domains_list'
        );
        $domains_list->attach(
            $fields->fieldText(
                'meta[domains_list]',
                (isset($vars->meta['domains_list']) ? $vars->meta['domains_list'] : null),
                ['id' => 'cpanel_domains_list']
            )
        );
        $tooltip = $fields->tooltip(Language::_('Cpanel.package_fields.tooltip.domains_list', true));
        $domains_list->attach($tooltip);
        $fields->setField($domains_list);

        // Set whether to use a dedicated IP
        $dedicated_ip = $fields->label(Language::_('Cpanel.package_fields.dedicated_ip', true), 'cpanel_dedicated_ip');
        $dedicated_ip->attach(
            $fields->fieldSelect(
                'meta[dedicated_ip]',
                [
                    Language::_('Cpanel.package_fields.dedicated_ip_no', true),
                    Language::_('Cpanel.package_fields.dedicated_ip_yes', true)
                ],
                (isset($vars->meta['dedicated_ip']) ? $vars->meta['dedicated_ip'] : null),
                ['id' => 'cpanel_dedicated_ip']
            )
        );
        $fields->setField($dedicated_ip);

        return $fields;
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
            // If not reseller, then no need to store ACL and Account Limit
            if ($vars['meta']['type'] != 'reseller') {
                unset($vars['meta']['acl']);
                unset($vars['meta']['account_limit']);
            }

            // If subdomains are not enabled, don't save anything for the domains list
            if (!isset($vars['meta']['sub_domains']) || $vars['meta']['sub_domains'] !== 'enable') {
                unset($vars['meta']['domains_list']);
            }

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
            // If not reseller, then no need to store ACL and Account Limit
            if ($vars['meta']['type'] != 'reseller') {
                unset($vars['meta']['acl']);
                unset($vars['meta']['account_limit']);
            }

            // If subdomains are not enabled, don't save anything for the domains list
            if (!isset($vars['meta']['sub_domains']) || $vars['meta']['sub_domains'] !== 'enable') {
                unset($vars['meta']['domains_list']);
            }

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
     * Returns the rendered view of the manage module page
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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'cpanel' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

        $this->view->set('module', $module);

        return $this->view->fetch();
    }

    /**
     * Returns the rendered view of the add module row page
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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'cpanel' . DS);

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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'cpanel' . DS);

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
        $meta_fields = ['server_name', 'host_name', 'user_name', 'key',
            'use_ssl', 'account_limit', 'name_servers', 'notes'];
        $encrypted_fields = ['user_name', 'key'];

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
                        'key'=>$key,
                        'value'=>$value,
                        'encrypted'=>in_array($key, $encrypted_fields) ? 1 : 0
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
        $meta_fields = ['server_name', 'host_name', 'user_name', 'key',
            'use_ssl', 'account_limit', 'account_count', 'name_servers', 'notes'];
        $encrypted_fields = ['user_name', 'key'];

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
                        'key'=>$key,
                        'value'=>$value,
                        'encrypted'=>in_array($key, $encrypted_fields) ? 1 : 0
                    ];
                }
            }

            return $meta;
        }
    }

    /**
     * Deletes the module row on the remote server. Sets Input errors on failure,
     * preventing the row from being deleted.
     *
     * @param stdClass $module_row The stdClass representation of the existing module row
     */
    public function deleteModuleRow($module_row)
    {
    }

    /**
     * Returns the value used to identify a particular package service which has
     * not yet been made into a service. This may be used to uniquely identify
     * an uncreated services of the same package (i.e. in an order form checkout)
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param array $vars An array of user supplied info to satisfy the request
     * @return string The value used to identify this package service
     * @see Module::getServiceName()
     */
    public function getPackageServiceName($package, array $vars = null)
    {
        $domain = $this->getDomainNameFromData($package, $vars);

        return !empty($domain) ? $domain : null;
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
        Configure::load('cpanel', dirname(__FILE__) . DS . 'config' . DS);

        // Set password options
        $password_requirements = Configure::get('Cpanel.password_requirements');
        $password_length = Configure::get('Cpanel.password_length');
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

        // Show the subdomain fields when we are adding a service, but not when managing a pending service
        $show_subdomains = (isset($vars->cpanel_domain) && isset($vars->cpanel_sub_domain))
            || !isset($vars->cpanel_domain);

        if (($package->meta->sub_domains ?? null) == 'enable' && $show_subdomains) {
            $domains = $this->getPackageAvailableDomains($package);

            // Create sub_domain label
            $sub_domain = $fields->label(Language::_('Cpanel.service_field.sub_domain', true), 'cpanel_sub_domain');
            // Create sub_domain field and attach to domain label
            $sub_domain->attach(
                $fields->fieldText(
                    'cpanel_sub_domain',
                    ($vars->cpanel_sub_domain ?? null),
                    ['id' => 'cpanel_sub_domain']
                )
            );
            // Set the label as a field
            $fields->setField($sub_domain);

            // Create domain label
            $domain = $fields->label(Language::_('Cpanel.service_field.domain', true), 'cpanel_domain');
            // Create domain field and attach to domain label
            $domain->attach(
                $fields->fieldSelect(
                    'cpanel_domain',
                    $domains,
                    ($vars->cpanel_domain ?? null),
                    ['id' => 'cpanel_domain']
                )
            );
            // Set the label as a field
            $fields->setField($domain);
        } else {
            // Create domain label
            $domain = $fields->label(Language::_('Cpanel.service_field.domain', true), 'cpanel_domain');
            // Create domain field and attach to domain label
            $domain->attach(
                $fields->fieldText(
                    'cpanel_domain',
                    ($vars->cpanel_domain ?? null),
                    ['id' => 'cpanel_domain']
                )
            );
            // Set the label as a field
            $fields->setField($domain);
        }

        // Create username label
        $username = $fields->label(Language::_('Cpanel.service_field.username', true), 'cpanel_username');
        // Create username field and attach to username label
        $username->attach(
            $fields->fieldText('cpanel_username', ($vars->cpanel_username ?? null), ['id'=>'cpanel_username'])
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('Cpanel.service_field.tooltip.username', true));
        $username->attach($tooltip);
        // Set the label as a field
        $fields->setField($username);

        // Create password label
        $password = $fields->label(Language::_('Cpanel.service_field.password', true), 'cpanel_password');
        // Create password field and attach to password label
        $password->attach(
            $fields->fieldPassword(
                'cpanel_password',
                [
                    'class' => 'cpanel_password',
                    'id' => 'cpanel_password',
                    'value' => ($vars->cpanel_password ?? null)
                ]
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('Cpanel.service_field.tooltip.password', true), ['class' => 'd-inline']);
        $password->attach($tooltip);
        // Set the label as a field
        $fields->setField($password);
        $fields->setHtml('<a class="generate-password"
                href="#" data-options="' . $this->Html->safe($password_options) . '"
                data-length="' . $this->Html->safe($password_length) . '"
                data-base-url="' . $this->base_uri . '" data-for-class="cpanel_password">
            <i class="fas fa-sync-alt"></i> ' . Language::_('Cpanel.service_field.text_generate_password', true) . '
        </a>
        <script type="text/javascript">
            $(document).ready(function () {
                $("#cpanel_password").wrap("<div></div>");
                $("#cpanel_password").removeClass("block");
                $("#cpanel_password").parent().append($(".generate-password"));
            });
        </script>
        ');

        // Confirm password label
        $confirm_password = $fields->label(
            Language::_('Cpanel.service_field.confirm_password', true),
            'cpanel_confirm_password'
        );
        // Create confirm password field and attach to password label
        $confirm_password->attach(
            $fields->fieldPassword(
                'cpanel_confirm_password',
                [
                    'class' => 'cpanel_password',
                    'id' => 'cpanel_confirm_password',
                    'value' => ($vars->cpanel_password ?? null)
                ]
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('Cpanel.service_field.tooltip.password', true));
        $confirm_password->attach($tooltip);
        // Set the label as a field
        $fields->setField($confirm_password);

        return $fields;
    }

    /**
     * Returns all fields to display to a client attempting to add a service with the module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containg the fields to render as well
     *  as any additional HTML markup to include
     */
    public function getClientAddFields($package, $vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        $fields = new ModuleFields();

        if ((isset($package->meta->sub_domains) ? $package->meta->sub_domains : null) == 'enable') {
            $domains = $this->getPackageAvailableDomains($package);

            // Create sub_domain label
            $sub_domain = $fields->label(Language::_('Cpanel.service_field.sub_domain', true), 'cpanel_sub_domain');
            // Create sub_domain field and attach to domain label
            $sub_domain->attach(
                $fields->fieldText(
                    'cpanel_sub_domain',
                    (isset($vars->cpanel_sub_domain) ? $vars->cpanel_sub_domain : null),
                    ['id' => 'cpanel_sub_domain']
                )
            );

            // Set the label as a field
            $fields->setField($sub_domain);

            // Create domain label
            $domain = $fields->label(Language::_('Cpanel.service_field.domain', true), 'cpanel_domain');
            // Create domain field and attach to domain label
            $domain->attach(
                $fields->fieldSelect(
                    'cpanel_domain',
                    $domains,
                    (isset($vars->cpanel_domain) ? $vars->cpanel_domain : null),
                    ['id' => 'cpanel_domain']
                )
            );
            // Set the label as a field
            $fields->setField($domain);
        } else {
            // Create domain label
            $domain = $fields->label(Language::_('Cpanel.service_field.domain', true), 'cpanel_domain');
            // Create domain field and attach to domain label
            $domain->attach(
                $fields->fieldText(
                    'cpanel_domain',
                    (isset($vars->cpanel_domain) ? $vars->cpanel_domain : ($vars->domain ?? null)),
                    ['id' => 'cpanel_domain']
                )
            );
            // Set the label as a field
            $fields->setField($domain);
        }

        return $fields;
    }

    /**
     * Returns all fields to display to an admin attempting to edit a service with the module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containg the fields to render as
     *  well as any additional HTML markup to include
     */
    public function getAdminEditFields($package, $vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        // Load product configuration required by this module
        Configure::load('cpanel', dirname(__FILE__) . DS . 'config' . DS);

        // Set password options
        $password_requirements = Configure::get('Cpanel.password_requirements');
        $password_length = Configure::get('Cpanel.password_length');
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
        $domain = $fields->label(Language::_('Cpanel.service_field.domain', true), 'cpanel_domain');
        // Create domain field and attach to domain label
        $domain->attach(
            $fields->fieldText('cpanel_domain', (isset($vars->cpanel_domain) ? $vars->cpanel_domain : null), ['id'=>'cpanel_domain'])
        );
        // Set the label as a field
        $fields->setField($domain);

        // Create username label
        $username = $fields->label(Language::_('Cpanel.service_field.username', true), 'cpanel_username');
        // Create username field and attach to username label
        $username->attach(
            $fields->fieldText('cpanel_username', (isset($vars->cpanel_username) ? $vars->cpanel_username : null), ['id'=>'cpanel_username'])
        );
        // Set the label as a field
        $fields->setField($username);

        // Create password label
        $password = $fields->label(Language::_('Cpanel.service_field.password', true), 'cpanel_password');
        // Create password field and attach to password label
        $password->attach(
            $fields->fieldPassword(
                'cpanel_password',
                [
                    'class' => 'cpanel_password',
                    'id' => 'cpanel_password',
                    'value' => ($vars->cpanel_password ?? null)
                ]
            )
        );
        // Set the label as a field
        $fields->setField($password);
        $fields->setHtml('<a class="generate-password"
                href="#" data-options="' . $this->Html->safe($password_options) . '"
                data-length="' . $this->Html->safe($password_length) . '"
                data-base-url="' . $this->base_uri . '" data-for-class="cpanel_password">
            <i class="fas fa-sync-alt"></i> ' . Language::_('Cpanel.service_field.text_generate_password', true) .
        '</a>
        <script type="text/javascript">
            $(document).ready(function () {
                $("#cpanel_password").parent().append($(".generate-password"));
            });
        </script>
        ');

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
        $this->Input->setRules($this->getServiceRules($vars, $package));
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
        $this->Input->setRules($this->getServiceRules($vars, $service->package, true));
        return $this->Input->validates($vars);
    }

    /**
     * Returns the rule set for adding/editing a service
     *
     * @param array $vars A list of input vars
     * @param stdClass $package The service package
     * @param bool $edit True to get the edit rules, false for the add rules
     * @return array Service rules
     */
    private function getServiceRules(array $vars = null, stdClass $package = null, $edit = false)
    {
        $rules = [
            'cpanel_domain' => [
                'format' => [
                    'rule' => [[$this, 'validateHostName']],
                    'message' => Language::_('Cpanel.!error.cpanel_domain.format', true)
                ],
                'valid' => [
                    'rule' => [
                        function ($domain, $sub_domain) use ($package) {
                            // If a subdomain was provided, the domain must be one in our defined set
                            if ($sub_domain !== null) {
                                return in_array($domain, $this->getPackageAvailableDomains($package));
                            }

                            return true;
                        },
                        ['_linked' => 'cpanel_sub_domain']
                    ],
                    'message' => Language::_('Cpanel.!error.cpanel_domain.valid', true)
                ]
            ],
            'cpanel_sub_domain' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['matches', '/^((?!-)[a-z0-9-]{1,63}(?<!-))$/i'],
                    'message' => Language::_('Cpanel.!error.cpanel_sub_domain.format', true)
                ],
                'availability' => [
                    'if_set' => true,
                    'rule' => [
                        function ($sub_domain, $domain) {
                            return !checkdnsrr($sub_domain . '.' . $domain, 'A');
                        },
                        ['_linked' => 'cpanel_domain']
                    ],
                    'message' => Language::_('Cpanel.!error.cpanel_sub_domain.availability', true)
                ]
            ],
            'cpanel_username' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['matches', '/^[a-z]([a-z0-9])*$/i'],
                    'message' => Language::_('Cpanel.!error.cpanel_username.format', true)
                ],
                'test' => [
                    'if_set' => true,
                    'rule' => ['matches', '/^(?!test)/'],
                    'message' => Language::_('Cpanel.!error.cpanel_username.test', true)
                ],
                'length' => [
                    'if_set' => true,
                    'rule' => ['betweenLength', 1, 16],
                    'message' => Language::_('Cpanel.!error.cpanel_username.length', true)
                ]
            ],
            'cpanel_password' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['isPassword', 8],
                    'message' => Language::_('Cpanel.!error.cpanel_password.valid', true),
                    'last' => true
                ],
            ],
            'cpanel_confirm_password' => [
                'matches' => [
                    'if_set' => true,
                    'rule' => ['compares', '==', ($vars['cpanel_password'] ?? '')],
                    'message' => Language::_('Cpanel.!error.cpanel_password.matches', true)
                ]
            ],
            'configoptions[dedicated_ip]' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['in_array', ['0', '1']],
                    'message' => Language::_('Cpanel.!error.configoptions[dedicated_ip].format', true)
                ]
            ],
        ];

        if (!isset($vars['cpanel_domain']) || strlen($vars['cpanel_domain']) < 4) {
            unset($rules['cpanel_domain']['test']);
        }

        // Set the values that may be empty
        $empty_values = ['cpanel_username', 'cpanel_password'];

        if ($edit) {
            // If this is an edit and no password given then don't evaluate password
            // since it won't be updated
            if (!array_key_exists('cpanel_password', $vars) || $vars['cpanel_password'] == '') {
                unset($rules['cpanel_password']);
            }

            // Validate domain if given
            $rules['cpanel_domain']['format']['if_set'] = true;

            if (isset($rules['cpanel_domain']['test'])) {
                $rules['cpanel_domain']['test']['if_set'] = true;
            }
        }

        // Remove rules on empty fields
        foreach ($empty_values as $value) {
            if (empty($vars[$value])) {
                unset($rules[$value]);
            }
        }

        return $rules;
    }

    /**
     * Retrieves the domain name from the given vars for this package
     *
     * @param stdClass $package An stdClass object representing the package
     * @param array $vars An array of input data including:
     *  - cpanel_domain The cpanel domain name
     *  - cpanel_sub_domain The cpanel sub domain (optional)
     * @return string The name of the domain name
     */
    private function getDomainNameFromData(stdClass $package, array $vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        $name = $this->formatDomain((isset($vars['cpanel_domain']) ? $vars['cpanel_domain'] : null));
        if ((isset($package->meta->sub_domains) ? $package->meta->sub_domains : null) == 'enable'
            && (isset($vars['cpanel_sub_domain']) ? $vars['cpanel_sub_domain'] : null)
        ) {
            $name = $this->formatDomain($vars['cpanel_sub_domain'] . '.' . $vars['cpanel_domain']);
        }

        return $name;
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
        $row = $this->getModuleRow();

        if (!$row) {
            $this->Input->setErrors(
                ['module_row' => ['missing' => Language::_('Cpanel.!error.module_row.missing', true)]]
            );
            return;
        }

        $api = $this->getApi($row->meta->host_name, $row->meta->user_name, $row->meta->key, $row->meta->use_ssl);

        // Generate username/password
        if (array_key_exists('cpanel_domain', $vars)) {
            Loader::loadModels($this, ['Clients']);

            // Strip "www." from beginning of domain if present
            $vars['cpanel_domain'] = $this->formatDomain($vars['cpanel_domain']);

            // Get the formatted domain name
            $domain = $this->getDomainNameFromData($package, $vars);

            // Generate a username
            if (empty($vars['cpanel_username'])) {
                $vars['cpanel_username'] = $this->generateUsername($domain);
            }

            // Generate a password
            if (empty($vars['cpanel_password'])) {
                $vars['cpanel_password'] = $this->generatePassword();
                $vars['cpanel_confirm_password'] = $vars['cpanel_password'];
            }

            // Use client's email address
            if (isset($vars['client_id']) && ($client = $this->Clients->get($vars['client_id'], false))) {
                $vars['cpanel_email'] = $client->email;
            }
        }

        $params = $this->getFieldsFromInput((array)$vars, $package);

        $this->validateService($package, $vars);

        if ($this->Input->errors()) {
            return;
        }

        // Only provision the service if 'use_module' is true
        $result = null;
        if ($vars['use_module'] == 'true') {
            $masked_params = $params;
            $masked_params['password'] = '***';
            $this->log($row->meta->host_name . '|createacct', serialize($masked_params), 'input', true);
            unset($masked_params);
            $result = $this->parseResponse($api->createacct($params));

            if ($this->Input->errors()) {
                return;
            }

            // If reseller and we have an ACL set, update the reseller's ACL
            if ($package->meta->type == 'reseller' && $package->meta->acl != '') {
                $this->log(
                    $row->meta->host_name . '|setacls',
                    serialize(['reseller' => $params['username'], 'acllist' => $package->meta->acl]),
                    'input',
                    true
                );

                $this->parseResponse(
                    $api->setacls(['reseller' => $params['username'], 'acllist' => $package->meta->acl])
                );
            }

            // If reseller and we have an Account Limit set, update the reseller's limits
            if ($package->meta->type == 'reseller' && $package->meta->account_limit != '') {
                $this->log(
                    $row->meta->host_name . '|setresellerlimits',
                    serialize([
                        'user' => $params['username'],
                        'account_limit' => $package->meta->account_limit,
                        'enable_account_limit' => true
                    ]),
                    'input',
                    true
                );

                $this->parseResponse(
                    $api->setresellerlimits([
                        'user' => $params['username'],
                        'account_limit' => $package->meta->account_limit,
                        'enable_account_limit' => true
                    ])
                );
            }

            // Update the number of accounts on the server
            $this->updateAccountCount($row);
        }

        // Return service fields
        return [
            [
                'key' => 'cpanel_domain',
                'value' => $params['domain'],
                'encrypted' => 0
            ],
            [
                'key' => 'cpanel_username',
                'value' => $params['username'],
                'encrypted' => 0
            ],
            [
                'key' => 'cpanel_password',
                'value' => $params['password'],
                'encrypted' => 1
            ],
            [
                'key' => 'cpanel_confirm_password',
                'value' => $params['password'],
                'encrypted' => 1
            ],
            [
                'key' => 'cpanel_ip',
                'value' => isset($result->result[0]->options->ip) ? $result->result[0]->options->ip : '',
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
        $row = $this->getModuleRow();
        $api = $this->getApi($row->meta->host_name, $row->meta->user_name, $row->meta->key, $row->meta->use_ssl);

        $this->validateServiceEdit($service, $vars);

        // Strip "www." from beginning of domain if present
        if (isset($vars['cpanel_domain'])) {
            $vars['cpanel_domain'] = $this->formatDomain($vars['cpanel_domain']);
        }

        if ($this->Input->errors()) {
            return;
        }

        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Remove password if not being updated
        if (isset($vars['cpanel_password']) && $vars['cpanel_password'] == '') {
            unset($vars['cpanel_password']);
        }

        // Only update the service if 'use_module' is true
        if ($vars['use_module'] == 'true') {
            // Check for fields that changed
            $delta = [];
            foreach ($vars as $key => $value) {
                if (!array_key_exists($key, (array) $service_fields) || $vars[$key] != $service_fields->$key) {
                    $delta[$key] = $value;
                }
            }

            // Update domain (if changed)
            if (isset($delta['cpanel_domain'])) {
                $params = ['domain' => $delta['cpanel_domain']];

                $this->log($row->meta->host_name . '|modifyacct', serialize($params), 'input', true);
                $result = $this->parseResponse($api->modifyacct($service_fields->cpanel_username, $params));
            }

            // Update password (if changed)
            if (isset($delta['cpanel_password'])) {
                $this->log($row->meta->host_name . '|passwd', '***', 'input', true);
                $result = $this->parseResponse(
                    $api->passwd($service_fields->cpanel_username, $delta['cpanel_password'])
                );
            }

            // Update username (if changed), do last so we can always rely on
            // $service_fields['cpanel_username'] to contain the username
            if (isset($delta['cpanel_username'])) {
                $params = ['newuser' => $delta['cpanel_username']];
                $this->log($row->meta->host_name . '|modifyacct', serialize($params), 'input', true);
                $result = $this->parseResponse($api->modifyacct($service_fields->cpanel_username, $params));
            }
        }

        // Set fields to update locally
        $fields = ['cpanel_domain', 'cpanel_username', 'cpanel_password'];
        foreach ($fields as $field) {
            if (property_exists($service_fields, $field) && isset($vars[$field])) {
                $service_fields->{$field} = $vars[$field];
            }
        }

        // Set the confirm password to the password
        $service_fields->cpanel_confirm_password = $service_fields->cpanel_password;

        // Return all the service fields
        $fields = [];
        $encrypted_fields = ['cpanel_password', 'cpanel_confirm_password'];
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
        // suspendacct / suspendreseller ($package->meta->type == "reseller")

        $row = $this->getModuleRow();

        if ($row) {
            $api = $this->getApi($row->meta->host_name, $row->meta->user_name, $row->meta->key, $row->meta->use_ssl);

            $service_fields = $this->serviceFieldsToObject($service->fields);

            if ($package->meta->type == 'reseller') {
                $this->log(
                    $row->meta->host_name . '|suspendreseller',
                    serialize($service_fields->cpanel_username),
                    'input',
                    true
                );
                $this->parseResponse($api->suspendreseller($service_fields->cpanel_username));
            } else {
                $this->log(
                    $row->meta->host_name . '|suspendacct',
                    serialize($service_fields->cpanel_username),
                    'input',
                    true
                );
                $this->parseResponse($api->suspendacct($service_fields->cpanel_username));
            }
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
        // unsuspendacct / unsuspendreseller ($package->meta->type == "reseller")

        if (($row = $this->getModuleRow())) {
            $api = $this->getApi($row->meta->host_name, $row->meta->user_name, $row->meta->key, $row->meta->use_ssl);

            $service_fields = $this->serviceFieldsToObject($service->fields);

            if ($package->meta->type == 'reseller') {
                $this->log(
                    $row->meta->host_name . '|unsuspendreseller',
                    serialize($service_fields->cpanel_username),
                    'input',
                    true
                );
                $this->parseResponse($api->unsuspendreseller($service_fields->cpanel_username));
            } else {
                $this->log(
                    $row->meta->host_name . '|unsuspendacct',
                    serialize($service_fields->cpanel_username),
                    'input',
                    true
                );
                $this->parseResponse($api->unsuspendacct($service_fields->cpanel_username));
            }
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
            $api = $this->getApi($row->meta->host_name, $row->meta->user_name, $row->meta->key, $row->meta->use_ssl);

            $service_fields = $this->serviceFieldsToObject($service->fields);

            if ($package->meta->type == 'reseller') {
                $this->log(
                    $row->meta->host_name . '|terminatereseller',
                    serialize($service_fields->cpanel_username),
                    'input',
                    true
                );
                $this->parseResponse($api->terminatereseller($service_fields->cpanel_username));
            } else {
                $this->log(
                    $row->meta->host_name . '|removeacct',
                    serialize($service_fields->cpanel_username),
                    'input',
                    true
                );
                $this->parseResponse($api->removeacct($service_fields->cpanel_username));
            }

            // Update the number of accounts on the server
            $this->updateAccountCount($row);
        }
        return null;
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
            $api = $this->getApi($row->meta->host_name, $row->meta->user_name, $row->meta->key, $row->meta->use_ssl);

            // Only request a package change if it has changed
            if ($package_from->meta->package != $package_to->meta->package) {
                $service_fields = $this->serviceFieldsToObject($service->fields);

                $this->log(
                    $row->meta->host_name . '|changepackage',
                    serialize([$service_fields->cpanel_username, $package_to->meta->package]),
                    'input',
                    true
                );

                $this->parseResponse($api->changepackage($service_fields->cpanel_username, $package_to->meta->package));
            }

            // If reseller and we have an Account Limit set, update the reseller's limits
            if ($package_to->meta->type == 'reseller' && $package_to->meta->account_limit != '') {
                $this->log(
                    $row->meta->host_name . '|setresellerlimits',
                    serialize([
                        'user' => $service_fields->cpanel_username,
                        'account_limit' => $package_to->meta->account_limit,
                        'enable_account_limit' => true
                    ]),
                    'input',
                    true
                );

                $this->parseResponse(
                    $api->setresellerlimits([
                        'user' => $service_fields->cpanel_username,
                        'account_limit' => $package_to->meta->account_limit,
                        'enable_account_limit' => true
                    ])
                );
            }
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
        $row = $this->getModuleRow();

        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('admin_service_info', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'cpanel' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Retrieve a single sign-on session for the user to log in with
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $session = $this->getUserSession($row, (isset($service_fields->cpanel_username) ? $service_fields->cpanel_username : null));

        $this->view->set('module_row', $row);
        $this->view->set('package', $package);
        $this->view->set('service', $service);
        $this->view->set('service_fields', $service_fields);
        $this->view->set('login_url', ($session && isset($session->url) ? $session->url : ''));

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
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'cpanel' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Retrieve a single sign-on session for the user to log in with
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $session = $this->getUserSession($row, (isset($service_fields->cpanel_username) ? $service_fields->cpanel_username : null));

        $this->view->set('module_row', $row);
        $this->view->set('package', $package);
        $this->view->set('service', $service);
        $this->view->set('service_fields', $service_fields);
        $this->view->set('login_url', ($session && isset($session->url) ? $session->url : ''));

        return $this->view->fetch();
    }

    /**
     * Statistics tab (bandwidth/disk usage)
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
        $this->view = new View('tab_stats', 'default');
        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $stats = $this->getStats($package, $service);

        $this->view->set('stats', $stats);
        $this->view->set('user_type', $package->meta->type);

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'cpanel' . DS);
        return $this->view->fetch();
    }

    /**
     * Client Statistics tab (bandwidth/disk usage)
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
        $this->view = new View('tab_client_stats', 'default');
        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $stats = $this->getStats($package, $service);

        $this->view->set('stats', $stats);
        $this->view->set('user_type', $package->meta->type);

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'cpanel' . DS);
        return $this->view->fetch();
    }

    /**
     * Fetches all account stats
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @return stdClass A stdClass object representing all of the stats for the account
     */
    private function getStats($package, $service)
    {
        $row = $this->getModuleRow();
        $api = $this->getApi($row->meta->host_name, $row->meta->user_name, $row->meta->key, $row->meta->use_ssl);

        $stats = new stdClass();
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Fetch account info
        $this->log(
            $row->meta->host_name . '|accountsummary',
            serialize($service_fields->cpanel_username),
            'input',
            true
        );
        $stats->account_info = $this->parseResponse($api->accountsummary($service_fields->cpanel_username));

        $stats->disk_usage = [
            'used' => null,
            'limit' => null
        ];
        $stats->bandwidth_usage = [
            'used' => null,
            'limit' => null
        ];

        // Get bandwidth/disk for reseller user
        if ($package->meta->type == 'reseller') {
            $this->log(
                $row->meta->host_name . '|resellerstats',
                serialize($service_fields->cpanel_username),
                'input',
                true
            );

            $reseller_info = $this->parseResponse($api->resellerstats($service_fields->cpanel_username));

            if (isset($reseller_info->result)) {
                $stats->disk_usage['used'] = $reseller_info->result->diskused;
                $stats->disk_usage['limit'] = $reseller_info->result->diskquota;
                $stats->disk_usage['alloc'] = $reseller_info->result->totaldiskalloc;

                $stats->bandwidth_usage['used'] = $reseller_info->result->totalbwused;
                $stats->bandwidth_usage['limit'] = $reseller_info->result->bandwidthlimit;
                $stats->bandwidth_usage['alloc'] = $reseller_info->result->totalbwalloc;
            }
        } else {
            // Get bandwidth/disk for standard user
            $params = [
                'search' => $service_fields->cpanel_username,
                'searchtype' => 'user'
            ];
            $this->log($row->meta->host_name . '|showbw', serialize($params), 'input', true);
            $bw = $this->parseResponse($api->showbw($params));

            if (isset($bw->bandwidth[0]->acct[0])) {
                $stats->bandwidth_usage['used'] = $bw->bandwidth[0]->acct[0]->totalbytes/(1024*1024);
                $stats->bandwidth_usage['limit'] = $bw->bandwidth[0]->acct[0]->limit/(1024*1024);
            }

            if (isset($stats->account_info->acct[0])) {
                $stats->disk_usage['used'] = preg_replace('/[^0-9]/', '', $stats->account_info->acct[0]->diskused);
                $stats->disk_usage['limit'] = preg_replace('/[^0-9]/', '', $stats->account_info->acct[0]->disklimit);
            }
        }

        return $stats;
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
        Configure::load('cpanel', dirname(__FILE__) . DS . 'config' . DS);

        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Set password options
        $password_requirements = Configure::get('Cpanel.password_requirements');
        $password_length = Configure::get('Cpanel.password_length');
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

        // Perform the password reset
        if (!empty($post)) {
            Loader::loadModels($this, ['Services']);
            $data = [
                'cpanel_password' => ($post['cpanel_password'] ?? null),
                'cpanel_confirm_password' => ($post['cpanel_confirm_password'] ?? null)
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
        $this->view->set('vars', ($vars ?? new stdClass()));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'cpanel' . DS);
        return $this->view->fetch();
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
     * Validates that the given sub-domain and domain combination is available
     *
     * @param string $sub_domain The sub domain
     * @param string $domain The main domain
     * @return bool True if the sub-domain is available, false otherwise
     */
    public function checkSubDomainAvailability($sub_domain, $domain)
    {
        return !checkdnsrr($sub_domain . '.' . $domain, 'A');
    }

    /**
     * Validates that at least 2 name servers are set in the given array of name servers
     *
     * @param array $name_servers An array of name servers
     * @return bool True if the array count is >= 2, false otherwise
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
     * Retrieves the accounts on the server
     *
     * @param stdClass $api The cPanel API
     * @return mixed The number of cPanel accounts on the server, or false on error
     */
    private function getAccountCount($api)
    {
        $accounts = false;

        try {
            $output = json_decode($api->listaccts());

            if (isset($output->acct) && is_array($output->acct)) {
                $accounts = count($output->acct);
            }
        } catch (Exception $e) {
            // Nothing to do
        }
        return $accounts;
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
            $module_row->meta->key,
            $module_row->meta->use_ssl
        );

        // Get the number of accounts on the server
        if (($count = $this->getAccountCount($api)) !== false) {
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
     * Validates whether or not the connection details are valid by attempting to fetch
     * the number of accounts that currently reside on the server
     *
     * @return bool True if the connection is valid, false otherwise
     */
    public function validateConnection($key, $host_name, $user_name, $use_ssl, &$account_count)
    {
        try {
            $api = $this->getApi($host_name, $user_name, $key, $use_ssl);

            $count = $this->getAccountCount($api);
            if ($count !== false) {
                $account_count = $count;
                return true;
            }
        } catch (Exception $e) {
            // Trap any errors encountered, could not validate connection
        }
        return false;
    }

    /**
     * Generates a username from the given host name
     *
     * @param string $host_name The host name to use to generate the username
     * @return string The username generated from the given hostname
     */
    private function generateUsername($host_name)
    {
        // Remove everything except letters and numbers from the domain
        $username = preg_replace('/[^a-z0-9]/i', '', $host_name);

        // Remove the 'test' string if it appears in the beginning
        if (strpos($username, 'test') === 0) {
            $username = substr($username, 4);
        }

        // Ensure no number appears in the beginning
        $username = ltrim($username, '0123456789');

        $length = strlen($username);
        $pool = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $pool_size = strlen($pool);

        if ($length < 5) {
            for ($i=$length; $i<8; $i++) {
                $username .= substr($pool, mt_rand(0, $pool_size-1), 1);
            }
            $length = strlen($username);
        }

        $username = substr($username, 0, min($length, 8));

        // Check for existing user accounts
        $account_matching_characters = 4; // [1,4]
        $accounts = $this->getUserAccounts(substr($username, 0, $account_matching_characters) . '(.*)');

        // Re-key the listings
        if (!empty($accounts)) {
            foreach ($accounts as $key => $account) {
                $accounts[$account->user] = $account;
                unset($accounts[$key]);
            }

            // Username exists, create another instead
            if (array_key_exists($username, $accounts)) {
                for ($i=0; $i<(int)str_repeat(9, $account_matching_characters); $i++) {
                    $new_username = substr($username, 0, -$account_matching_characters) . $i;
                    if (!array_key_exists($new_username, $accounts)) {
                        $username = $new_username;
                        break;
                    }
                }
            }
        }

        return $username;
    }

    /**
     * Retrieves matching user accounts
     *
     * @param string $name The account username (supports regex's)
     * @return mixed An array of stdClass objects representing each user, or null if no user exists
     */
    private function getUserAccounts($name)
    {
        $user = null;

        $row = $this->getModuleRow();
        if ($row) {
            $api = $this->getApi($row->meta->host_name, $row->meta->user_name, $row->meta->key, $row->meta->use_ssl);
        }

        try {
            if ($api) {
                $output = json_decode($api->listaccts('user', $name));

                if (isset($output->acct)) {
                    $user = $output->acct;
                }
            }
        } catch (Exception $e) {
            // Nothing to do
        }

        return $user;
    }

    /**
     * Retrieves all of the available domains for subdomain provisioning for a specific package
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @return mixed A key/value array of available domains
     */
    private function getPackageAvailableDomains(stdClass $package)
    {
        if (!empty($package->meta->domains_list)) {
            return $this->parseElementsFromCsv($package->meta->domains_list);
        }

        return [];
    }

    /**
     * Parses out the given elements from a CSV
     *
     * @param string $csv The CSV list
     * @return array An array of elements from the list
     */
    private function parseElementsFromCsv($csv)
    {
        $items = [];

        foreach (explode(',', $csv) as $item) {
            $item = strtolower(trim($item));

            // Skip any blank items
            if (empty($item)) {
                continue;
            }

            $items[$item] = $item;
        }

        return $items;
    }

    /**
     * Generates a password
     *
     * @param int $min_length The minimum character length for the password (5 or larger)
     * @param int $max_length The maximum character length for the password (14 or fewer)
     * @return string The generated password
     */
    private function generatePassword($min_length = 10, $max_length = 14)
    {
        $pool = 'abcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()';
        $pool_size = strlen($pool);
        $length = mt_rand(max($min_length, 5), min($max_length, 14));
        $password = '';

        for ($i=0; $i<$length; $i++) {
            $password .= substr($pool, mt_rand(0, $pool_size-1), 1);
        }

        return $password;
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
        // Decide whether to use a dedicated IP
        $dedicated_ip = 'n';
        if (isset($package->meta->dedicated_ip) && $package->meta->dedicated_ip == '1') {
            $dedicated_ip =  'y';
        }
        if (isset($vars['configoptions']['dedicated_ip']) && $vars['configoptions']['dedicated_ip'] == '1') {
            $dedicated_ip =  'y';
        }

        // Retrieve the formatted (sub)domain name
        $domain = $this->getDomainNameFromData($package, $vars);
        $fields = [
            'domain' => !empty($domain) ? $domain : null,
            'username' => isset($vars['cpanel_username']) ? $vars['cpanel_username']: null,
            'password' => isset($vars['cpanel_password']) ? $vars['cpanel_password'] : null,
            'plan' => $package->meta->package,
            'reseller' => ($package->meta->type == 'reseller' ? 1 : 0),
            'ip' => $dedicated_ip,
            'contactemail' => isset($vars['cpanel_email']) ? $vars['cpanel_email'] : null
        ];

        return $fields;
    }

    /**
     * Parses the response from the API into a stdClass object
     *
     * @param string $response The response from the API
     * @return stdClass A stdClass object representing the response, void if the response was an error
     */
    private function parseResponse($response)
    {
        $row = $this->getModuleRow();

        $result = json_decode($response);
        $success = true;

        // Set internal error
        if (!$result) {
            $this->Input->setErrors(['api' => ['internal' => Language::_('Cpanel.!error.api.internal', true)]]);
            $success = false;
        }

        // Only some API requests return status, so only use it if its available
        if (isset($result->status) && $result->status == 0) {
            $this->Input->setErrors(['api' => ['result' => $result->statusmsg]]);
            $success = false;
        } elseif (isset($result->result) && is_array($result->result)
            && isset($result->result[0]->status) && $result->result[0]->status == 0
        ) {
            $this->Input->setErrors(['api' => ['result' => $result->result[0]->statusmsg]]);
            $success = false;
        } elseif (isset($result->passwd) && is_array($result->passwd)
            && isset($result->passwd[0]->status) && $result->passwd[0]->status == 0
        ) {
            $this->Input->setErrors(['api' => ['result' => $result->passwd[0]->statusmsg]]);
            $success = false;
        } elseif (isset($result->cpanelresult) && !empty($result->cpanelresult->error)) {
            $this->Input->setErrors(
                [
                    'api' => [
                        'error' => (isset($result->cpanelresult->data->reason)
                            ? $result->cpanelresult->data->reason
                            : $result->cpanelresult->error
                        )
                    ]
                ]
            );
            $success = false;
        }

        $sensitive_data = ['/PassWord:.*?(\\\\n)/i'];
        $replacements = ['PassWord: *****${1}'];

        // Log the response
        $this->log($row->meta->host_name, preg_replace($sensitive_data, $replacements, $response), 'output', $success);

        // Return if any errors encountered
        if (!$success) {
            return;
        }

        return $result;
    }

    /**
     * Initializes the CpanelApi and returns an instance of that object with the given $host, $user, and $pass set
     *
     * @param string $host The host to the cPanel server
     * @param string $user The user to connect as
     * @param string $pass The hash-pased password to authenticate with
     * @return CpanelApi The CpanelApi instance
     */
    private function getApi($host, $user, $pass, $use_ssl = true)
    {
        Loader::load(dirname(__FILE__) . DS . 'apis' . DS . 'cpanel_api.php');

        $api = new CpanelApi($host);
        $api->set_user($user);

        // Determine whether this is a token or a key based on length
        if (strlen($pass) > self::$token_length) {
            $api->set_hash($pass);
        } else {
            $api->set_token($pass);
        }
        $api->set_output('json');
        $api->set_port(($use_ssl ? 2087 : 2086));
        $api->set_protocol('http' . ($use_ssl ? 's' : ''));

        return $api;
    }

    /**
     * Fetches a listing of all packages configured in cPanel for the given server
     *
     * @param stdClass $module_row A stdClass object representing a single server
     * @return array An array of packages in key/value pair
     */
    private function getCpanelPackages($module_row)
    {
        if (!isset($this->DataStructure)) {
            Loader::loadHelpers($this, ['DataStructure']);
        }
        if (!isset($this->ArrayHelper)) {
            $this->ArrayHelper = $this->DataStructure->create('Array');
        }

        $api = $this->getApi(
            $module_row->meta->host_name,
            $module_row->meta->user_name,
            $module_row->meta->key,
            $module_row->meta->use_ssl
        );
        $packages = [];

        try {
            $this->log($module_row->meta->host_name . '|listpkgs', null, 'input', true);
            $package_list = $api->listpkgs();
            $result = json_decode($package_list);

            $success = false;
            if (isset($result->package)) {
                $success = true;
                $packages = $this->ArrayHelper->numericToKey($result->package, 'name', 'name');
            }

            $this->log($module_row->meta->host_name, $package_list, 'output', $success);
        } catch (Exception $e) {
            // API request failed
        }

        return $packages;
    }

    /**
     * Fetches a listing of all ACLs configured in cPanel for the given server
     *
     * @param stdClass $module_row A stdClass object representing a single server
     * @return array An array of ACLS in key/value pair
     */
    private function getCpanelAcls($module_row)
    {
        if (!isset($this->DataStructure)) {
            Loader::loadHelpers($this, ['DataStructure']);
        }
        if (!isset($this->ArrayHelper)) {
            $this->ArrayHelper = $this->DataStructure->create('Array');
        }

        $api = $this->getApi(
            $module_row->meta->host_name,
            $module_row->meta->user_name,
            $module_row->meta->key,
            $module_row->meta->use_ssl
        );

        try {
            $keys = (array)json_decode($api->listacls())->acls;

            $acls = [];
            foreach ($keys as $key => $value) {
                $acls[$key] = $key;
            }
            return $acls;
        } catch (Exception $e) {
            // API request failed
        }

        return [];
    }

    /**
     * Creates a new user session with cPanel for the given user
     *
     * @param stdClass $module_row The module row
     * @param string $username The cPanel username to authenticate with
     * @return false|stdClass An stdClass object representing the user session data retrieved on success,
     *  otherwise false
     */
    private function getUserSession($module_row, $username)
    {
        $api = $this->getApi(
            $module_row->meta->host_name,
            $module_row->meta->user_name,
            $module_row->meta->key,
            $module_row->meta->use_ssl
        );

        try {
            $data = ['api.version' => 1, 'user' => $username, 'service' => 'cpaneld'];
            $this->log($module_row->meta->host_name . '|create_user_session', serialize($data), 'input', true);
            $response = $api->xmlapi_query('create_user_session', $data);
            $result = $this->parseResponse($response);
        } catch (Exception $e) {
            // API request failed
        }

        if (isset($result) && isset($result->data)) {
            return $result->data;
        }

        return false;
    }

    /**
     * Removes the www. from a domain name
     *
     * @param string $domain A domain name
     * @return string The domain name after the www. has been removed
     */
    private function formatDomain($domain)
    {
        return strtolower(preg_replace('/^\s*www\./i', '', $domain));
    }

    /**
     * Builds and returns the rules required to add/edit a module row (e.g. server)
     *
     * @param array $vars An array of key/value data pairs
     * @return array An array of Input rules suitable for Input::setRules()
     */
    private function getRowRules(&$vars)
    {
        $rules = [
            'server_name'=>[
                'valid'=>[
                    'rule'=>'isEmpty',
                    'negate'=>true,
                    'message'=>Language::_('Cpanel.!error.server_name_valid', true)
                ]
            ],
            'host_name'=>[
                'valid'=>[
                    'rule'=>[[$this, 'validateHostName']],
                    'message'=>Language::_('Cpanel.!error.host_name_valid', true)
                ]
            ],
            'user_name'=>[
                'valid'=>[
                    'rule'=>'isEmpty',
                    'negate'=>true,
                    'message'=>Language::_('Cpanel.!error.user_name_valid', true)
                ]
            ],
            'key'=>[
                'valid'=>[
                    'last'=>true,
                    'rule'=>'isEmpty',
                    'negate'=>true,
                    'message'=>Language::_('Cpanel.!error.remote_key_valid', true)
                ],
                'valid_connection'=>[
                    'rule' => [
                        [$this, 'validateConnection'],
                        $vars['host_name'],
                        $vars['user_name'],
                        $vars['use_ssl'],
                        &$vars['account_count']
                    ],
                    'message'=>Language::_('Cpanel.!error.remote_key_valid_connection', true)
                ]
            ],
            'account_limit'=>[
                'valid'=>[
                    'rule'=>['matches', '/^([0-9]+)?$/'],
                    'message'=>Language::_('Cpanel.!error.account_limit_valid', true)
                ]
            ],
            'name_servers'=>[
                'count'=>[
                    'rule'=>[[$this, 'validateNameServerCount']],
                    'message'=>Language::_('Cpanel.!error.name_servers_count', true)
                ],
                'valid'=>[
                    'rule'=>[[$this, 'validateNameServers']],
                    'message'=>Language::_('Cpanel.!error.name_servers_valid', true)
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
                'valid' => [
                    'rule' => ['matches', '/^(standard|reseller)$/'],
                    // type must be standard or reseller
                    'message' => Language::_('Cpanel.!error.meta[type].valid', true),
                ]
            ],
            'meta[sub_domains]' => [
                'valid' => [
                    'rule' => ['matches', '/^(disable|enable)$/'],
                    'message' => Language::_('Cpanel.!error.meta[sub_domains].valid', true),
                ]
            ],
            'meta[domains_list]' => [
                'valid' => [
                    'rule' => [
                        function ($domains_csv, $enable_subdomains) {
                            // We only validate the domains if the sub domains are enabled
                            if ($enable_subdomains !== 'enable') {
                                return true;
                            }

                            $domains = $this->parseElementsFromCsv($domains_csv);

                            // At least one domain must be set
                            if (empty($domains)) {
                                return false;
                            }

                            // The domains must be valid host names
                            foreach ($domains as $domain) {
                                if (!$this->validateHostName($domain)) {
                                    return false;
                                }
                            }

                            return true;
                        },
                        ['_linked' => 'meta[sub_domains]']
                    ],
                    'message' => Language::_('Cpanel.!error.meta[domains_list].valid', true),
                    'post_format' => function ($domains_csv) {
                        // Create a new CSV list that we've formatted
                        return implode(',', $this->parseElementsFromCsv($domains_csv));
                    }
                ]
            ],
            'meta[account_limit]' => [
                'valid' => [
                    'rule' => ['matches', '/^([0-9]+)?$/'],
                    'message' => Language::_('Cpanel.!error.meta[account_limit].valid', true),
                ]
            ],
            'meta[package]' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Cpanel.!error.meta[package].empty', true) // package must be given
                ]
            ],
            'meta[dedicated_ip]' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['in_array', ['0', '1']],
                    'message' => Language::_('Cpanel.!error.meta[dedicated_ip].format', true)
                ]
            ],
        ];

        return $rules;
    }
}
