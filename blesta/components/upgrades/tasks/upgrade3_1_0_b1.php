<?php
/**
 * Upgrades to version 3.1.0-b1
 *
 * @package blesta
 * @subpackage blesta.components.upgrades.tasks
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Upgrade3_1_0B1 extends UpgradeUtil
{

    /**
     * @var array An array of all tasks completed
     */
    private $tasks = [];

    /**
     * Setup
     */
    public function __construct()
    {
        Loader::loadComponents($this, ['Record']);
    }

    /**
     * Returns a numerically indexed array of tasks to execute for the upgrade process
     *
     * @retrun array A numerically indexed array of tasks to execute for the upgrade process
     */
    public function tasks()
    {
        return [
            'updatePackages',
            'updateEmailGroupFields',
            'addPackageOptions',
            'updatePermissions',
            'addEmailAttachments',
            'addServiceUnsuspensionEmail',
            'updateServiceSuspensionErrorEmail',
            'updateServiceUnsuspensionErrorEmail',
            'updateServiceCancellationErrorEmail',
            'addPackageGroupSort',
            'updateAutodebitSettings',
            'addServiceCreationErrorEmail',
            'updateAgingInvoicesEmail',
            'updateInvoiceCreationEmail',
            'updateServiceCreationEmail'
        ];
    }

    /**
     * Processes the given task
     *
     * @param string $task The task to process
     */
    public function process($task)
    {
        $tasks = $this->tasks();

        // Ensure task exists
        if (!in_array($task, $tasks)) {
            return;
        }

        $this->tasks[] = $task;
        $this->{$task}();
    }

    /**
     * Rolls back all tasks completed for the upgrade process
     */
    public function rollback()
    {
        // Undo all tasks
        while (($task = array_pop($this->tasks))) {
            $this->{$task}(true);
        }
    }

    /**
     * Update packages to set a single term option
     *
     * @param bool $undo True to undo the change false to perform the change
     */
    private function updatePackages($undo = false)
    {
        if ($undo) {
            $this->Record->setField('single_term', null, false)->alter('packages');
        } else {
            $this->Record->query("ALTER TABLE `packages`
                ADD `single_term` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `taxable` ;");
        }
    }

    /**
     * Adds package options
     *
     * @param bool $undo True to undo the change false to perform the change
     */
    private function addPackageOptions($undo = false)
    {
        if ($undo) {
            try {
                $this->Record->drop('package_option');
                $this->Record->drop('package_options');
                $this->Record->drop('package_option_values');
                $this->Record->drop('package_option_pricing');
                $this->Record->drop('package_option_groups');
                $this->Record->drop('package_option_group');
                $this->Record->drop('service_options');

                // Restore package_pricing
                $fields = ['package_pricing.*', 'pricings.term', 'pricings.period', 'pricings.price',
                    'pricings.setup_fee', 'pricings.cancel_fee', 'pricings.currency'];
                $package_pricings = $this->Record->select($fields)->from('package_pricing')->
                    innerJoin('pricings', 'package_pricing.pricing_id', '=', 'pricings.id', false)->
                    fetchAll();

                $this->Record->drop('package_pricing');
                $this->Record->
                    setField('id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])->
                    setField('package_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                    setField('term', ['type' => 'smallint', 'size' => 5, 'unsigned' => true, 'default' => 1])->
                    setField(
                        'period',
                        ['type' => 'enum', 'size' => "'day','week','month','year','onetime'", 'default' => 'month']
                    )->
                    setField('price', ['type' => 'decimal', 'size' => '12,4', 'default' => '0.0000'])->
                    setField('setup_fee', ['type' => 'decimal', 'size' => '12,4', 'default' => '0.0000'])->
                    setField('cancel_fee', ['type' => 'decimal', 'size' => '12,4', 'default' => '0.0000'])->
                    setField('currency', ['type' => 'char', 'size' => 3, 'default' => 'USD'])->
                    setKey(['package_id'], 'index')->
                    setKey(['id'], 'primary')->
                    create('package_pricing', true);

                foreach ($package_pricings as $pricing) {
                    $vars = [
                        'id' => $pricing->id,
                        'package_id' => $pricing->package_id,
                        'term' => $pricing->term,
                        'period' => $pricing->period,
                        'price' => $pricing->price,
                        'setup_fee' => $pricing->setup_fee,
                        'cancel_fee' => $pricing->cancel_fee,
                        'currency' => $pricing->currency
                    ];
                    $this->Record->insert('package_pricing', $vars);
                }

                $this->Record->drop('pricings');
            } catch (Exception $e) {
                // Nothing to do
            }
        } else {
            // Package Option
            $this->Record->
                setField('package_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('option_group_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setKey(['package_id', 'option_group_id'], 'primary')->
                create('package_option', true);

            // Package Options
            $this->Record->
                setField('id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])->
                setField('company_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('label', ['type' => 'varchar', 'size' => 128])->
                setField('name', ['type' => 'varchar', 'size' => 128])->
                setField(
                    'type',
                    ['type' => 'enum', 'size' => "'checkbox','radio','select','quantity'", 'default' => 'select']
                )->
                setKey(['company_id'], 'index')->
                setKey(['id'], 'primary')->
                create('package_options', true);

            // Package Option Values
            $this->Record->
                setField('id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])->
                setField('option_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('name', ['type' => 'varchar', 'size' => 128])->
                setField('value', ['type' => 'varchar', 'size' => 255, 'is_null' => true, 'default' => null])->
                setField('order', ['type' => 'smallint', 'size' => 5, 'unsigned' => true])->
                setField(
                    'min',
                    ['type' => 'int', 'size' => 10, 'unsigned' => true, 'is_null' => true, 'default' => null]
                )->
                setField(
                    'max',
                    ['type' => 'int', 'size' => 10, 'unsigned' => true, 'is_null' => true, 'default' => null]
                )->
                setField(
                    'step',
                    ['type' => 'int', 'size' => 10, 'unsigned' => true, 'is_null' => true, 'default' => null]
                )->
                setKey(['option_id'], 'index')->
                setKey(['id'], 'primary')->
                create('package_option_values', true);

            // Package Option Pricing
            $this->Record->
                setField('id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])->
                setField('option_value_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('pricing_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setKey(['option_value_id', 'pricing_id'], 'unique')->
                setKey(['id'], 'primary')->
                create('package_option_pricing', true);

            // Package Option Groups
            $this->Record->
                setField('id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])->
                setField('company_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('name', ['type' => 'varchar', 'size' => 128])->
                setField('description', ['type' => 'text', 'is_null' => true, 'default' => null])->
                setKey(['company_id'], 'index')->
                setKey(['id'], 'primary')->
                create('package_option_groups', true);

            // Package Option Group
            $this->Record->
                setField('option_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('option_group_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('order', ['type' => 'smallint', 'size' => 5, 'unsigned' => true])->
                setKey(['option_id', 'option_group_id'], 'primary')->
                create('package_option_group', true);

            // Service Options
            $this->Record->
                setField('id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])->
                setField('service_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('option_pricing_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('qty', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 1])->
                setKey(['service_id'], 'index')->
                setKey(['option_pricing_id'], 'index')->
                setKey(['id'], 'primary')->
                create('service_options', true);

            // Pricings
            $this->Record->
                setField('id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])->
                setField('company_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('term', ['type' => 'smallint', 'size' => 5, 'unsigned' => true, 'default' => 1])->
                setField(
                    'period',
                    ['type' => 'enum', 'size' => "'day','week','month','year','onetime'", 'default' => 'month']
                )->
                setField('price', ['type' => 'decimal', 'size' => '12,4', 'default' => '0.0000'])->
                setField('setup_fee', ['type' => 'decimal', 'size' => '12,4', 'default' => '0.0000'])->
                setField('cancel_fee', ['type' => 'decimal', 'size' => '12,4', 'default' => '0.0000'])->
                setField('currency', ['type' => 'char', 'size' => 3, 'default' => 'USD'])->
                setKey(['company_id'], 'index')->
                setKey(['id'], 'primary')->
                create('pricings', true);

            // Fetch package pricing
            $fields = ['package_pricing.*', 'packages.company_id'];
            $pricing = $this->Record->select($fields)->from('package_pricing')->
                innerJoin('packages', 'packages.id', '=', 'package_pricing.package_id', false)->
                fetchAll();

            $package_pricing_ids = [];
            foreach ($pricing as $price) {
                $vars = [
                    'company_id' => $price->company_id,
                    'term' => $price->term,
                    'period' => $price->period,
                    'price' => $price->price,
                    'setup_fee' => $price->setup_fee,
                    'cancel_fee' => $price->cancel_fee,
                    'currency' => $price->currency
                ];
                $this->Record->insert('pricings', $vars);

                $package_pricing_ids[] = [
                    'id' => $price->id,
                    'package_id' => $price->package_id,
                    'pricing_id' => $this->Record->lastInsertId()
                ];
            }

            // Package Pricing
            $this->Record->drop('package_pricing');
            $this->Record->
                setField('id', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto_increment' => true])->
                setField('package_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setField('pricing_id', ['type' => 'int', 'size' => 10, 'unsigned' => true])->
                setKey(['package_id', 'pricing_id'], 'unique')->
                setKey(['id'], 'primary')->
                create('package_pricing', true);

            foreach ($package_pricing_ids as $ids) {
                $this->Record->insert('package_pricing', $ids);
            }
        }
    }

    /**
     * Add new permissions, removed unused permissions
     *
     * @param bool $undo True to undo the change false to perform the change
     */
    private function updatePermissions($undo = false)
    {
        Loader::loadModels($this, ['Permissions', 'StaffGroups']);
        Loader::loadComponents($this, ['Acl']);

        if ($undo) {
            // Nothing to undo
        } else {
            $staff_groups = $this->StaffGroups->getAll();
            // Determine comparable permission access
            $staff_group_access = [];
            foreach ($staff_groups as $staff_group) {
                $staff_group_access[$staff_group->id] = [
                    'admin_system_general' => $this->Permissions->authorized(
                        'staff_group_' . $staff_group->id,
                        'admin_system_general',
                        'basic',
                        'staff',
                        $staff_group->company_id
                    ),
                    'admin_company_billing' => $this->Permissions->authorized(
                        'staff_group_' . $staff_group->id,
                        'admin_company_billing',
                        'customization',
                        'staff',
                        $staff_group->company_id
                    ),
                    'admin_company_general' => $this->Permissions->authorized(
                        'staff_group_' . $staff_group->id,
                        'admin_company_general',
                        'localization',
                        'staff',
                        $staff_group->company_id
                    ),
                    'admin_packages' => $this->Permissions->authorized(
                        'staff_group_' . $staff_group->id,
                        'admin_packages',
                        'groups',
                        'staff',
                        $staff_group->company_id
                    )
                ];
            }

            $group = $this->Permissions->getGroupByAlias('admin_settings');
            if ($group) {
                $permissions = [
                    // View contact types
                    [
                        'group_id' => $group->id,
                        'name' => 'StaffGroups.permissions.admin_company_general_contacttypes',
                        'alias' => 'admin_company_general',
                        'action' => 'contacttypes'
                    ],
                    // Add contact types
                    [
                        'group_id' => $group->id,
                        'name' => 'StaffGroups.permissions.admin_company_general_addcontacttype',
                        'alias' => 'admin_company_general',
                        'action' => 'addcontacttype'
                    ],
                    // Edit contact types
                    [
                        'group_id' => $group->id,
                        'name' => 'StaffGroups.permissions.admin_company_general_editcontacttype',
                        'alias' => 'admin_company_general',
                        'action' => 'editcontacttype'
                    ],
                    // Delete contact types
                    [
                        'group_id' => $group->id,
                        'name' => 'StaffGroups.permissions.admin_company_general_deletecontacttype',
                        'alias' => 'admin_company_general',
                        'action' => 'deletecontacttype'
                    ],
                    // Install language
                    [
                        'group_id' => $group->id,
                        'name' => 'StaffGroups.permissions.admin_company_general_installlanguage',
                        'alias' => 'admin_company_general',
                        'action' => 'installlanguage'
                    ],
                    // Uninstall language
                    [
                        'group_id' => $group->id,
                        'name' => 'StaffGroups.permissions.admin_company_general_uninstalllanguage',
                        'alias' => 'admin_company_general',
                        'action' => 'uninstalllanguage'
                    ],
                    // Company Automation
                    [
                        'group_id' => $group->id,
                        'name' => 'StaffGroups.permissions.admin_company_general_automation',
                        'alias' => 'admin_company_general',
                        'action' => 'automation'
                    ],
                    // Accepted Payment Types
                    [
                        'group_id' => $group->id,
                        'name' => 'StaffGroups.permissions.admin_company_billing_acceptedtypes',
                        'alias' => 'admin_company_billing',
                        'action' => 'acceptedtypes'
                    ],
                    // View payment types
                    [
                        'group_id' => $group->id,
                        'name' => 'StaffGroups.permissions.admin_system_general_paymenttypes',
                        'alias' => 'admin_system_general',
                        'action' => 'paymenttypes'
                    ],
                    // Add payment type
                    [
                        'group_id' => $group->id,
                        'name' => 'StaffGroups.permissions.admin_system_general_addtype',
                        'alias' => 'admin_system_general',
                        'action' => 'addtype'
                    ],
                    // Edit payment type
                    [
                        'group_id' => $group->id,
                        'name' => 'StaffGroups.permissions.admin_system_general_edittype',
                        'alias' => 'admin_system_general',
                        'action' => 'edittype'
                    ],
                    // Delete payment type
                    [
                        'group_id' => $group->id,
                        'name' => 'StaffGroups.permissions.admin_system_general_deletetype',
                        'alias' => 'admin_system_general',
                        'action' => 'deletetype'
                    ]
                ];
                foreach ($permissions as $vars) {
                    $this->Permissions->add($vars);

                    foreach ($staff_groups as $staff_group) {
                        // If staff group has access to similar item, grant access to this item
                        if ($staff_group_access[$staff_group->id][$vars['alias']]) {
                            $this->Acl->allow('staff_group_' . $staff_group->id, $vars['alias'], $vars['action']);
                        }
                    }
                }
            }

            // Package options
            $group = $this->Permissions->getGroupByAlias('admin_packages');
            if ($group) {
                $permissions = [
                    // Add Package Group
                    [
                        'group_id' => $group->id,
                        'name' => 'StaffGroups.permissions.admin_packages_addgroup',
                        'alias' => 'admin_packages',
                        'action' => 'addgroup'
                    ],
                    // Edit Package Group
                    [
                        'group_id' => $group->id,
                        'name' => 'StaffGroups.permissions.admin_packages_editgroup',
                        'alias' => 'admin_packages',
                        'action' => 'editgroup'
                    ],
                    // Delete Package Group
                    [
                        'group_id' => $group->id,
                        'name' => 'StaffGroups.permissions.admin_packages_deletegroup',
                        'alias' => 'admin_packages',
                        'action' => 'deletegroup'
                    ],
                    // Configurable Options
                    [
                        'group_id' => $group->id,
                        'name' => 'StaffGroups.permissions.admin_package_options',
                        'alias' => 'admin_package_options',
                        'action' => '*'
                    ]
                ];
                foreach ($permissions as $vars) {
                    $this->Permissions->add($vars);

                    foreach ($staff_groups as $staff_group) {
                        // If staff group has access to similar item, grant access to this item
                        if ($staff_group_access[$staff_group->id]['admin_packages']) {
                            $this->Acl->allow('staff_group_' . $staff_group->id, $vars['alias'], $vars['action']);
                        }
                    }
                }
            }

            // Remove "admin_clients/deleteinvoice" (no such resource)
            $perm = $this->Permissions->getByAlias('admin_clients', null, 'deleteinvoice');
            if ($perm) {
                $this->Permissions->delete($perm->id);
            }

            // Clear cache for each staff group
            foreach ($staff_groups as $staff_group) {
                Cache::clearCache('nav_staff_group_' . $staff_group->id, $staff_group->company_id . DS . 'nav' . DS);
            }
        }
    }

    /**
     * Updates emails table to add an additional field for whether to send attachments with the email
     *
     * @param bool $undo True to undo the change false to perform the change
     */
    private function addEmailAttachments($undo = false)
    {
        if ($undo) {
            $this->Record->query('ALTER TABLE `emails` DROP `include_attachments`;');
        } else {
            $this->Record->query("ALTER TABLE `emails`
                ADD `include_attachments` TINYINT( 1 ) NOT NULL DEFAULT '1' AFTER `email_signature_id`;");
        }
    }

    /**
     * Adds the service unsuspension email template
     *
     * @param bool $undo True to undo the change, false to perform the change
     */
    private function addServiceUnsuspensionEmail($undo = false)
    {
        if ($undo) {
            return;
        }

        Loader::loadModels($this, ['Companies', 'Emails', 'Languages']);

        // Add the service unsuspension email template group
        $this->Record->query("INSERT INTO `email_groups` (`id` , `action` , `type` , `plugin_dir` , `tags`)
            VALUES (
                NULL ,
                'service_unsuspension',
                'client',
                NULL ,
                '{contact.first_name},{contact.last_name},{package.name}'
            );");
        $email_group_id = $this->Record->lastInsertId();

        // Fetch all companies
        $companies = $this->Companies->getAll();

        foreach ($companies as $company) {
            // Fetch all languages installed for this company
            $languages = $this->Languages->getAll($company->id);

            // Add the service unsuspension email template for each installed language
            foreach ($languages as $language) {
                // Fetch the suspension email to copy fields from
                $suspension_email = $this->Emails->getByType($company->id, 'service_suspension', $language->code);

                if ($suspension_email) {
                    $vars = [
                        'email_group_id' => $email_group_id,
                        'company_id' => $company->id,
                        'lang' => $language->code,
                        'from' => $suspension_email->from,
                        'from_name' => $suspension_email->from_name,
                        'subject' => 'Service Unsuspended',
                        'text' => 'Hi {contact.first_name},

Your service, {package.name} has been unsuspended.',
                        'html' => '<p>Hi {contact.first_name},</p>
<p>Your service, {package.name} has been unsuspended.</p>',
                        'email_signature_id' => $suspension_email->email_signature_id
                    ];

                    $this->Record->insert('emails', $vars);
                }
            }
        }
    }

    /**
     * Updates the content of the Service Suspension Error email template
     *
     * @param bool $undo True to undo the change, false to perform the change
     */
    private function updateServiceSuspensionErrorEmail($undo = false)
    {
        if ($undo) {
            return;
        }

        // Update the service_suspension_error tags
        $vars = [
            'tags' => '{staff.first_name},{staff.last_name},{client.id_code},{client.first_name},{client.last_name},'
                . '{client.email},{service.name},{package.name}'
        ];
        $this->Record->where('action', '=', 'service_suspension_error')->
            update('email_groups', $vars);

        // Fetch each service_suspension_error email template (in all languages)
        $emails = $this->Record->select(['emails.*'])->from('emails')->
            on('email_groups.action', '=', 'service_suspension_error')->
            innerJoin('email_groups', 'email_groups.id', '=', 'emails.email_group_id', false)->
            fetchAll();

        // Update each template to set the new text/html content
        foreach ($emails as $email) {
            $vars = [
                'text' => 'Hi {staff.first_name},

There was an error with the {package.name} package, or its module does not support automatic suspension.

{package.name} {service.name}
--
{client.id_code}
{client.first_name} {client.last_name}
{client.email}

Manage Service (http://{admin_uri}clients/editservice/{client.id}/{service.id}/)

The service may need to be suspended manually.
{% if errors %}{% for type in errors %}{% for error in type %}
Error: {error}

{% endfor %}{% endfor %}{% endif %}',
                'html' => '<p>
	Hi {staff.first_name},<br />
	<br />
	There was an error with the {package.name} package, or its module does not support automatic suspension.<br />
	<br />
	{package.name} {service.name}<br />
	--<br />
	{client.id_code}<br />
	{client.first_name} {client.last_name}<br />
	{client.email}</p>
<p>
	<a href="http://{admin_uri}clients/editservice/{client.id}/{service.id}/">Manage Service</a><br />
	<br />
	The service may need to be suspended manually.<br />
	{% if errors %}{% for type in errors %}{% for error in type %}<br />
	Error: {error}</p>
<p>
	{% endfor %}{% endfor %}{% endif %}</p>'
            ];

            $this->Record->where('emails.id', '=', $email->id)->update('emails', $vars);
        }
    }

    /**
     * Updates the content of the Service Unsuspension Error email template
     *
     * @param bool $undo True to undo the change, false to perform the change
     */
    private function updateServiceUnsuspensionErrorEmail($undo = false)
    {
        if ($undo) {
            return;
        }

        // Update the service_unsuspension_error tags
        $vars = [
            'tags' => '{staff.first_name},{staff.last_name},{client.id_code},{client.first_name},{client.last_name},'
                . '{client.email},{service.name},{package.name}'
        ];
        $this->Record->where('action', '=', 'service_unsuspension_error')->
            update('email_groups', $vars);

        // Fetch each service_unsuspension_error email template (in all languages)
        $emails = $this->Record->select(['emails.*'])->from('emails')->
            on('email_groups.action', '=', 'service_unsuspension_error')->
            innerJoin('email_groups', 'email_groups.id', '=', 'emails.email_group_id', false)->
            fetchAll();

        // Update each template to set the new text/html content
        foreach ($emails as $email) {
            $vars = [
                'text' => 'Hi {staff.first_name},

There was an error with the {package.name} package, or its module does not support automatic unsuspension.

{package.name} {service.name}
--
{client.id_code}
{client.first_name} {client.last_name}
{client.email}

Manage Service (http://{admin_uri}clients/editservice/{client.id}/{service.id}/)

The service may need to be unsuspended manually.
{% if errors %}{% for type in errors %}{% for error in type %}
Error: {error}

{% endfor %}{% endfor %}{% endif %}',
                'html' => '<p>
	Hi {staff.first_name},<br />
	<br />
	There was an error with the {package.name} package, or its module does not support automatic unsuspension.<br />
	<br />
	{package.name} {service.name}<br />
	--<br />
	{client.id_code}<br />
	{client.first_name} {client.last_name}<br />
	{client.email}</p>
<p>
	<a href="http://{admin_uri}clients/editservice/{client.id}/{service.id}/">Manage Service</a><br />
	<br />
	The service may need to be unsuspended manually.<br />
	{% if errors %}{% for type in errors %}{% for error in type %}<br />
	Error: {error}</p>
<p>
	{% endfor %}{% endfor %}{% endif %}</p>'
            ];

            $this->Record->where('emails.id', '=', $email->id)->update('emails', $vars);
        }
    }

    /**
     * Updates the content of the Service Cancellation Error email template
     *
     * @param bool $undo True to undo the change, false to perform the change
     */
    private function updateServiceCancellationErrorEmail($undo = false)
    {
        if ($undo) {
            return;
        }

        // Update the service_cancel_error tags
        $vars = [
            'tags' => '{staff.first_name},{staff.last_name},{client.id_code},{client.first_name},{client.last_name},'
                . '{client.email},{service.name},{package.name}'
        ];
        $this->Record->where('action', '=', 'service_cancel_error')->
            update('email_groups', $vars);

        // Fetch each service_cancel_error email template (in all languages)
        $emails = $this->Record->select(['emails.*'])->from('emails')->
            on('email_groups.action', '=', 'service_cancel_error')->
            innerJoin('email_groups', 'email_groups.id', '=', 'emails.email_group_id', false)->
            fetchAll();

        // Update each template to set the new text/html content
        foreach ($emails as $email) {
            $vars = [
                'text' => 'Hi {staff.first_name},

There was an error with the {package.name} package, or its module returned an error on cancellation.

{package.name} {service.name}
--
{client.id_code}
{client.first_name} {client.last_name}
{client.email}

Manage Service (http://{admin_uri}clients/editservice/{client.id}/{service.id}/)

The service may need to be cancelled manually.
{% if errors %}{% for type in errors %}{% for error in type %}
Error: {error}

{% endfor %}{% endfor %}{% endif %}',
                'html' => '<p>
	Hi {staff.first_name},<br />
	<br />
	There was an error with the {package.name} package, or its module returned an error on cancellation.<br />
	<br />
	{package.name} {service.name}<br />
	--<br />
	{client.id_code}<br />
	{client.first_name} {client.last_name}<br />
	{client.email}</p>
<p>
	<a href="http://{admin_uri}clients/editservice/{client.id}/{service.id}/">Manage Service</a><br />
	<br />
	The service may need to be cancelled manually.<br />
	{% if errors %}{% for type in errors %}{% for error in type %}<br />
	Error: {error}</p>
<p>
	{% endfor %}{% endfor %}{% endif %}</p>'
            ];

            $this->Record->where('emails.id', '=', $email->id)->update('emails', $vars);
        }
    }

    /**
     * Updates the Aging Invoices email for report_ar
     *
     * @param bool $undo True to undo the change, false to perform the change
     */
    private function updateAgingInvoicesEmail($undo = false)
    {
        if ($undo) {
            return;
        }

        // Update the report_ar tags
        $vars = [
            'tags' => '{staff.first_name},{staff.last_name},{company.name}'
        ];
        $this->Record->where('action', '=', 'report_ar')->
            update('email_groups', $vars);

        // Fetch each report_ar email template (in all languages)
        $emails = $this->Record->select(['emails.*'])->from('emails')->
            on('email_groups.action', '=', 'report_ar')->
            innerJoin('email_groups', 'email_groups.id', '=', 'emails.email_group_id', false)->
            fetchAll();

        // Update each template to set the new text/html content
        foreach ($emails as $email) {
            $vars = [
                'subject' => 'Monthly Aging Invoices Report',
                'text' => 'Hi {staff.first_name},

An Aging Invoices Report has been generated for {company.name} and is attached to this email as a CSV file.',
                'html' => '<p>
	Hi {staff.first_name},<br />
	<br />
	An Aging Invoices Report has been generated for {company.name} and is attached to this email as a CSV file.</p>'
            ];

            $this->Record->where('emails.id', '=', $email->id)->update('emails', $vars);
        }
    }

    /**
     * Updates the Invoice Creation email for report_invoice_creation
     *
     * @param bool $undo True to undo the change, false to perform the change
     */
    private function updateInvoiceCreationEmail($undo = false)
    {
        if ($undo) {
            return;
        }

        // Update the report_invoice_creation tags
        $vars = [
            'tags' => '{staff.first_name},{staff.last_name},{company.name}'
        ];
        $this->Record->where('action', '=', 'report_invoice_creation')->
            update('email_groups', $vars);

        // Fetch each report_invoice_creation email template (in all languages)
        $emails = $this->Record->select(['emails.*'])->from('emails')->
            on('email_groups.action', '=', 'report_invoice_creation')->
            innerJoin('email_groups', 'email_groups.id', '=', 'emails.email_group_id', false)->
            fetchAll();

        // Update each template to set the new text/html content
        foreach ($emails as $email) {
            $vars = [
                'text' => 'Hi {staff.first_name},

A Daily Invoice Creation Report has been generated for {company.name} and is attached to this email as a CSV file.',
                'html' => '<p>
	Hi {staff.first_name},</p>
<p>
	A Daily Invoice Creation Report has been generated for {company.name} and is attached to this email as a CSV file.</p>'
            ];

            $this->Record->where('emails.id', '=', $email->id)->update('emails', $vars);
        }
    }

    /**
     * Adds a new email group and email for the service_creation_error
     *
     * @param bool $undo True to undo the change, false to perform the change
     */
    private function addServiceCreationErrorEmail($undo = false)
    {
        if ($undo) {
            return;
        }

        Loader::loadModels($this, ['Companies', 'Emails', 'Languages']);

        // Add the service_creation_error email group
        $this->Record->query("INSERT INTO `email_groups`
            (`id` , `action` , `type` , `notice_type` , `plugin_dir` , `tags`)
            VALUES (
                NULL,
                'service_creation_error',
                'staff',
                'to',
                NULL,
                '{staff.first_name},{staff.last_name},{client.id_code},{client.first_name},{client.last_name},"
                . "{client.email},{service.name},{package.name}'
            );");
        $email_group_id = $this->Record->lastInsertId();

        // Fetch all companies
        $companies = $this->Companies->getAll();

        foreach ($companies as $company) {
            // Fetch all languages installed for this company
            $languages = $this->Languages->getAll($company->id);

            // Add the service creation error email template for each installed language
            foreach ($languages as $language) {
                // Fetch the suspension error email to copy fields from
                $suspension_email = $this->Emails->getByType($company->id, 'service_suspension_error', $language->code);

                if ($suspension_email) {
                    $vars = [
                        'email_group_id' => $email_group_id,
                        'company_id' => $company->id,
                        'lang' => $language->code,
                        'from' => $suspension_email->from,
                        'from_name' => $suspension_email->from_name,
                        'subject' => 'Creation Error',
                        'text' => 'Hi {staff.first_name},

There was an error provisioning the following service:

{package.name} {service.name}
--
{client.id_code}
{client.first_name} {client.last_name}
{client.email}

Manage Service (http://{admin_uri}clients/editservice/{client.id}/{service.id}/)

The pending service may need to be modified so that it can be provisioned automatically or it may need to be provisioned manually.
{% if errors %}{% for type in errors %}{% for error in type %}
Error: {error}

{% endfor %}{% endfor %}{% endif %}',
                        'html' => '<p>
	Hi {staff.first_name},<br />
	<br />
	There was an error provisioning the following service:<br />
	<br />
	{package.name} {service.name}<br />
	--<br />
	{client.id_code}<br />
	{client.first_name} {client.last_name}<br />
	{client.email}</p>
<p>
	<a href="http://{admin_uri}clients/editservice/{client.id}/{service.id}/">Manage Service</a></p>
<p>
	The pending service may need to be modified so that it can be provisioned automatically or it may need to be provisioned manually.<br />
	{% if errors %}{% for type in errors %}{% for error in type %}<br />
	Error: {error}</p>
<p>
	{% endfor %}{% endfor %}{% endif %}</p>'
                    ];

                    $this->Record->insert('emails', $vars);
                }
            }
        }
    }

    /**
     * Adds package group sorting capabilities
     *
     * @param bool $undo True to undo the change, false to perform the change
     */
    private function addPackageGroupSort($undo = false)
    {
        if ($undo) {
            $this->Record->query('ALTER TABLE `package_group` DROP `order`;');
        } else {
            $this->Record->query("ALTER TABLE `package_group`
                ADD `order` SMALLINT( 5 ) NOT NULL DEFAULT '0' AFTER `package_group_id`;");
        }
    }

    /**
     * Replaces the 'autodebit_backoff' setting with an 'autodebit_attempts' setting
     *
     * @param bool $undo True to undo the change, false to perform the change
     */
    private function updateAutodebitSettings($undo = false)
    {
        if ($undo) {
            // System setting
            $this->Record->query("UPDATE `settings`
                SET `key`='autodebit_backoff', `value`='false' WHERE `key`='autodebit_attempts' AND `value`='1';");
            $this->Record->query("UPDATE `settings`
                SET `key`='autodebit_backoff', `value`='true' WHERE `key`='autodebit_attempts' AND `value`='3';");
            // Company settings
            $this->Record->query("UPDATE `company_settings`
                SET `key`='autodebit_backoff', `value`='false' WHERE `key`='autodebit_attempts' AND `value`='1';");
            $this->Record->query("UPDATE `company_settings`
                SET `key`='autodebit_backoff', `value`='true' WHERE `key`='autodebit_attempts' AND `value`='3';");
            // Client group settings
            $this->Record->query("UPDATE `client_group_settings`
                SET `key`='autodebit_backoff', `value`='false' WHERE `key`='autodebit_attempts' AND `value`='1';");
            $this->Record->query("UPDATE `client_group_settings`
                SET `key`='autodebit_backoff', `value`='true' WHERE `key`='autodebit_attempts' AND `value`='3';");

            $this->Record->query('ALTER TABLE `client_account` DROP `failed_count`;');
        } else {
            // System setting
            $this->Record->query("UPDATE `settings`
                SET `key`='autodebit_attempts', `value`='1' WHERE `key`='autodebit_backoff' AND `value`='false';");
            $this->Record->query("UPDATE `settings`
                SET `key`='autodebit_attempts', `value`='3' WHERE `key`='autodebit_backoff' AND `value`='true';");
            // Company settings
            $this->Record->query("UPDATE `company_settings`
                SET `key`='autodebit_attempts', `value`='1' WHERE `key`='autodebit_backoff' AND `value`='false';");
            $this->Record->query("UPDATE `company_settings`
                SET `key`='autodebit_attempts', `value`='3' WHERE `key`='autodebit_backoff' AND `value`='true';");
            // Client group settings
            $this->Record->query("UPDATE `client_group_settings`
                SET `key`='autodebit_attempts', `value`='1' WHERE `key`='autodebit_backoff' AND `value`='false';");
            $this->Record->query("UPDATE `client_group_settings`
                SET `key`='autodebit_attempts', `value`='3' WHERE `key`='autodebit_backoff' AND `value`='true';");

            $this->Record->query("ALTER TABLE `client_account`
                ADD `failed_count` SMALLINT( 5 ) NOT NULL DEFAULT '0' AFTER `type`;");

            // Allow staff_id to be null (allow system to create notes)
            $this->Record->query('ALTER TABLE `client_notes`
                CHANGE `staff_id` `staff_id` INT( 10 ) UNSIGNED NULL DEFAULT NULL;');
        }
    }

    /**
     * Add the notice_type column to the email_groups table
     *
     * @param bool $undo True to undo the change, false to perform the change
     */
    private function updateEmailGroupFields($undo = false)
    {
        if ($undo) {
            $this->Record->query('ALTER TABLE `email_groups` DROP `notice_type`;');
        } else {
            // Add the column
            $this->Record->query("ALTER TABLE `email_groups`
                ADD `notice_type` ENUM( 'bcc', 'to' ) NULL DEFAULT NULL AFTER `type` ;");

            // Update all core client email groups to BCC
            $this->Record->query("UPDATE `email_groups`
                SET `notice_type` = 'bcc' WHERE `type` = 'client' AND `plugin_dir` IS NULL ;");

            // Update all core staff email groups to TO, except for the password reset template
            $this->Record->query("UPDATE `email_groups`
                SET `notice_type` = 'to'
                WHERE `type` = 'staff' AND `action` != 'staff_reset_password' AND `plugin_dir` IS NULL ;");
        }
    }

    /**
     * Update the service_creation email templates to change the formatting of package pricing for
     * backward compatibility
     *
     * @param bool $undo True to undo the change, false to perform the change
     */
    private function updateServiceCreationEmail($undo = false)
    {
        if ($undo) {
            return;
        }

        Loader::loadModels($this, ['Companies', 'Emails', 'Languages']);

        // Fetch the service_creation email group
        $email_group = $this->Record->select()->from('email_groups')->where('action', '=', 'service_creation')->fetch();

        if ($email_group) {
            // Maintain backward compatibility by replacing package pricing fields with currency formatted values
            $emails = $this->Record->select()->from('emails')->
                where('email_group_id', '=', $email_group->id)->
                getStatement();

            // Update the pricing formats
            foreach ($emails as $email) {
                $find = [
                    '{service.package_pricing.price}',
                    '{service.package_pricing.setup_fee}',
                    '{service.package_pricing.cancel_fee}'
                ];
                $replace = [
                    '{service.package_pricing.price | currency_format service.package_pricing.currency}',
                    '{service.package_pricing.setup_fee | currency_format service.package_pricing.currency}',
                    '{service.package_pricing.cancel_fee | currency_format service.package_pricing.currency}'
                ];

                $vars = [
                    'text' => str_replace($find, $replace, $email->text),
                    'html' => str_replace($find, $replace, $email->html)
                ];

                if ($vars['text'] != $email->text || $vars['html'] != $email->html) {
                    $this->Record->where('id', '=', $email->id)->update('emails', $vars);
                }
            }
        }
    }
}
