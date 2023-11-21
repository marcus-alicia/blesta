<?php
// Controller get functions

$lang['AdminModule.getoptionalfunctions.tooltip_cancelService'] = 'Called to perform module actions on service cancellation.';
$lang['AdminModule.getoptionalfunctions.tooltip_suspendService'] = 'Called to perform module actions on service suspension.';
$lang['AdminModule.getoptionalfunctions.tooltip_unsuspendService'] = 'Called to perform module actions on service unsuspension.';
$lang['AdminModule.getoptionalfunctions.tooltip_renewService'] = 'Called to perform module actions when a service is renewed.';
$lang['AdminModule.getoptionalfunctions.tooltip_addPackage'] = 'Performs any action required to add the package on the remote server. Sets Input errors on failure, preventing the package from being added.';
$lang['AdminModule.getoptionalfunctions.tooltip_editPackage'] = 'Performs any action required to edit the package on the remote server. Sets Input errors on failure, preventing the package from being edited.';
$lang['AdminModule.getoptionalfunctions.tooltip_deletePackage'] = 'Deletes the package on the remote server. Sets Input errors on failure, preventing the package from being deleted.';
$lang['AdminModule.getoptionalfunctions.tooltip_deleteModuleRow'] = 'Deletes the module row on the remote server. Sets Input errors on failure, preventing the row from being deleted.';
$lang['AdminModule.getoptionalfunctions.tooltip_manageAddRow'] = 'Returns the rendered view of the add module row page.';
$lang['AdminModule.getoptionalfunctions.tooltip_manageEditRow'] = 'Returns the rendered view of the edit module row page.';
$lang['AdminModule.getoptionalfunctions.tooltip_upgrade'] = 'Performs migration of data from $current_version (the current installed version) to the given file set version. Sets Input errors on failure, preventing the module from being upgraded.';
$lang['AdminModule.getoptionalfunctions.tooltip_getGroupOrderOptions'] = 'Returns an array of available service delegation order methods. The module will determine how each method is defined. For example, the method "first" may be implemented such that it returns the module row with the least number of services assigned to it.';
$lang['AdminModule.getoptionalfunctions.tooltip_selectModuleRow'] = 'Determines which module row should be attempted when a service is provisioned for the given group based upon the order method set for that group.';
$lang['AdminModule.getoptionalfunctions.tooltip_getAdminServiceInfo'] = 'Fetches the HTML content to display when viewing the service info in the admin interface.';
$lang['AdminModule.getoptionalfunctions.tooltip_getClientServiceInfo'] = 'Fetches the HTML content to display when viewing the service info in the client interface.';
$lang['AdminModule.getoptionalfunctions.tooltip_checkAvailability'] = 'Checks if a domain name is available or not, returns boolean.';
$lang['AdminModule.getoptionalfunctions.tooltip_checkTransferAvailability'] = 'Checks if a domain name can be transferred, returns boolean.';
$lang['AdminModule.getoptionalfunctions.tooltip_getDomainInfo'] = 'Fetches the information of a given domain name.';
$lang['AdminModule.getoptionalfunctions.tooltip_getExpirationDate'] = 'Returns the expiration date of a given domain name.';
$lang['AdminModule.getoptionalfunctions.tooltip_getTldPricing'] = 'Returns an array containing the cost price of all available TLDs';
$lang['AdminModule.getoptionalfunctions.tooltip_registerDomain'] = 'Performs any action required to register a new domain name. Sets Input errors on failure, preventing the domain name registration.';
$lang['AdminModule.getoptionalfunctions.tooltip_renewDomain'] = 'Performs any action required to renew an existing domain name. Sets Input errors on failure, preventing the renewal of the domain name.';
$lang['AdminModule.getoptionalfunctions.tooltip_transferDomain'] = 'Performs any action required to transfer a domain name. Sets Input errors on failure, preventing the domain name from being transferred.';
$lang['AdminModule.getoptionalfunctions.tooltip_getDomainContacts'] = 'Returns an array containing all the contacts associated to the domain name.';
$lang['AdminModule.getoptionalfunctions.tooltip_getDomainIsLocked'] = 'Returns whether or not the domain is locked, returns boolean.';
$lang['AdminModule.getoptionalfunctions.tooltip_getDomainNameServers'] = 'Returns the name servers of the domain.';
$lang['AdminModule.getoptionalfunctions.tooltip_lockDomain'] = 'Performs any action required to lock the domain. Sets Input errors on failure, preventing the domain from being locked.';
$lang['AdminModule.getoptionalfunctions.tooltip_unlockDomain'] = 'Performs any action required to unlock the domain. Sets Input errors on failure, preventing the domain from being unlocked.';
$lang['AdminModule.getoptionalfunctions.tooltip_updateEppCode'] = 'Performs any action required to update the EPP or Authorization code of the domain. Sets Input errors on failure, preventing the code from being updated.';
$lang['AdminModule.getoptionalfunctions.tooltip_restoreDomain'] = 'Performs any action required to restore a domain. Sets Input errors on failure, preventing the domain from being restored.';
$lang['AdminModule.getoptionalfunctions.tooltip_setNameserverIps'] = 'Sets the name servers for a given domain name.';
$lang['AdminModule.getoptionalfunctions.tooltip_sendEppEmail'] = 'Performs any action required to send an email containing the EPP code.';
$lang['AdminModule.getoptionalfunctions.tooltip_resendTransferEmail'] = 'Resends the domain transfer verification email.';
$lang['AdminModule.getoptionalfunctions.tooltip_setDomainContacts'] = 'Updates the list of contacts associated with a domain.';
$lang['AdminModule.getoptionalfunctions.tooltip_setDomainNameservers'] = 'Assigns new name servers to the domain.';

// Page title
$lang['AdminModule.index.page_title'] = 'Extension Generator - %1$s'; // %1$s is the module name

$lang['AdminModule.index.boxtitle_extension_generator'] = 'Extension Generator - Module';


// Basic info page
$lang['AdminModule.basic.heading_basic'] = 'Basic Information';
$lang['AdminModule.basic.heading_authors'] = 'Authors';
$lang['AdminModule.basic.heading_tlds'] = 'Supported TLDs';

$lang['AdminModule.basic.description'] = 'Description';
$lang['AdminModule.basic.logo'] = 'Logo';
$lang['AdminModule.basic.module_row'] = 'Module Row Name';
$lang['AdminModule.basic.module_row_plural'] = 'Module Row Name (Plural)';
$lang['AdminModule.basic.module_group'] = 'Module Group Label';
$lang['AdminModule.basic.module_type'] = 'Module Type';
$lang['AdminModule.basic.module_type_generic'] = 'Generic';
$lang['AdminModule.basic.module_type_registrar'] = 'Registrar';
$lang['AdminModule.basic.author_name'] = 'Author Name';
$lang['AdminModule.basic.author_url'] = 'Author URL';
$lang['AdminModule.basic.text_options'] = 'Options';
$lang['AdminModule.basic.author_row_add'] = 'Add Author';
$lang['AdminModule.basic.text_remove'] = 'Remove';
$lang['AdminModule.basic.static_tlds'] = 'Statically Define TLDs';
$lang['AdminModule.basic.tlds'] = 'TLDs';
$lang['AdminModule.basic.placeholder_tlds'] = '.com,.net,.org';

$lang['AdminModule.basic.tooltip_description'] = 'The description shown in the module listing';
$lang['AdminModule.basic.tooltip_logo'] = 'The logo displayed in the module listing';
$lang['AdminModule.basic.tooltip_module_row'] = 'The term by which to refer to a single module row for this module';
$lang['AdminModule.basic.tooltip_module_row_plural'] = 'The term by which to refer to multiple module rows for this module';
$lang['AdminModule.basic.tooltip_module_group'] = 'The term by which to refer to module row groups for this module';
$lang['AdminModule.basic.tooltip_module_type'] = 'Whether the module is a generic module or a domain registrar module';
$lang['AdminModule.basic.tooltip_static_tlds'] = 'Define the TLDs supported by this module as a comma separated list (e.g. .com,.net,.org)';

$lang['AdminModule.basic.placeholder_module_row'] = 'e.g. Server';
$lang['AdminModule.basic.placeholder_module_row_plural'] = 'e.g. Servers';
$lang['AdminModule.basic.placeholder_module_group'] = 'e.g. Server Group';
$lang['AdminModule.basic.placeholder_author_name'] = 'e.g. Blesta';
$lang['AdminModule.basic.placeholder_author_url'] = 'e.g. https://blesta.com/';

$lang['AdminModule.basic.fields'] = 'Next - Module Fields';
$lang['AdminModule.basic.confirm'] = 'Next - Confirmation';


// Fields page
$lang['AdminModule.fields.heading_module_row_fields'] = 'Module Row Fields';
$lang['AdminModule.fields.heading_package_fields'] = 'Package Fields';
$lang['AdminModule.fields.heading_service_fields'] = 'Service Fields';

$lang['AdminModule.fields.name'] = 'Name';
$lang['AdminModule.fields.label'] = 'Label';
$lang['AdminModule.fields.type'] = 'Type';
$lang['AdminModule.fields.tooltip'] = 'Tooltip Text';
$lang['AdminModule.fields.name_key'] = 'Name Key';

$lang['AdminModule.fields.tooltip_name'] = 'The name of the field in the code base';
$lang['AdminModule.fields.tooltip_label'] = 'The display name of the field';
$lang['AdminModule.fields.tooltip_type'] = 'The type of field to create (checkbox, text, etc.)';
$lang['AdminModule.fields.tooltip_tooltip'] = 'The text of the tooltip to display along side the field (leave empty to have no tooltip)';
$lang['AdminModule.fields.tooltip_name_key'] = 'The field by which Blesta determines the name of a package/service/module row';

$lang['AdminModule.fields.placeholder_module_name'] = 'e.g. module_field';
$lang['AdminModule.fields.placeholder_module_label'] = 'e.g. Module Field';
$lang['AdminModule.fields.placeholder_package_name'] = 'e.g. package_field';
$lang['AdminModule.fields.placeholder_package_label'] = 'e.g. Package Field';
$lang['AdminModule.fields.placeholder_service_name'] = 'e.g. service_field';
$lang['AdminModule.fields.placeholder_service_label'] = 'e.g. Service Field';

$lang['AdminModule.fields.module_row_add'] = 'Add Module Row Field';
$lang['AdminModule.fields.package_row_add'] = 'Add Package Field';
$lang['AdminModule.fields.service_row_add'] = 'Add Service Field';

$lang['AdminModule.fields.text_options'] = 'Options';
$lang['AdminModule.fields.text_remove'] = 'Remove';

$lang['AdminModule.fields.features'] = 'Next - Additional Features';

$lang['AdminModule.fields.package_fields_epp_code_label'] = 'EPP Code';
$lang['AdminModule.fields.package_fields_epp_code_tooltip'] = 'Whether to allow users to request an EPP Code through the Blesta service interface.';
$lang['AdminModule.fields.service_fields_domain_label'] = 'Domain';

// Additional features page
$lang['AdminModule.features.heading_features'] = 'Additional Features';
$lang['AdminModule.features.heading_service_tabs'] = 'Service Management Tabs';
$lang['AdminModule.features.heading_cron_tasks'] = 'Cron Tasks';
$lang['AdminModule.features.heading_optional_functions'] = 'Optional Functions';

$lang['AdminModule.features.method_name'] = 'Method Name';
$lang['AdminModule.features.label'] = 'Label';
$lang['AdminModule.features.level'] = 'Level';
$lang['AdminModule.features.name'] = 'Name';
$lang['AdminModule.features.description'] = 'Description';
$lang['AdminModule.features.type'] = 'Type';
$lang['AdminModule.features.time'] = 'Start Time/Interval';

$lang['AdminModule.features.tooltip_method_name'] = 'The name of the method to be generated for this tab in the code base (in the form camelCaseMethodName)';
$lang['AdminModule.features.tooltip_tab_label'] = 'The display name of the service tab';
$lang['AdminModule.features.tooltip_level'] = 'The level of interface on which to display the tab (staff or client)';
$lang['AdminModule.features.tooltip_name'] = 'The name of the cron task in the code base';
$lang['AdminModule.features.tooltip_cron_label'] = 'The display name of the cron task';
$lang['AdminModule.features.tooltip_description'] = 'The description shown on the cron task list page';
$lang['AdminModule.features.tooltip_type'] = 'The type of timing to use for the cron task (time or interval)';
$lang['AdminModule.features.tooltip_time'] = 'The daily 24-hour time that this task should run (e.g. "14:25") OR The interval, in minutes, that this cron task should run';

$lang['AdminModule.features.placeholder_method_name'] = 'e.g. tabChangePassword';
$lang['AdminModule.features.placeholder_tab_label'] = 'e.g. Change Password';
$lang['AdminModule.features.placeholder_name'] = 'e.g. my_cron_task';
$lang['AdminModule.features.placeholder_cron_label'] = 'e.g. My Cron Task';
$lang['AdminModule.features.placeholder_time'] = 'e.g. 14:25 or 60';

$lang['AdminModule.features.service_tab_row_add'] = 'Add Service Management Tab';
$lang['AdminModule.features.cron_task_row_add'] = 'Add Cron Task';

$lang['AdminModule.features.text_options'] = 'Options';
$lang['AdminModule.features.text_remove'] = 'Remove';

$lang['AdminModule.features.confirm'] = 'Next - Confirmation';
