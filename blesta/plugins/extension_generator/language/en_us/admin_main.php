<?php

// Error messages
$lang['AdminMain.!error.name_taken'] = 'This name conflicts with an existing extension directory.';
$lang['AdminMain.!error.generation_failed'] = 'Unable to generate your extension.  Exception generated: %1$s'; // Where %1$s is the exeption message


// Notice messages
$lang['AdminMain.!notice.type_warning'] = 'Changing the extension type will irrevirsibly erase all settings saved for this extension.';
$lang['AdminMain.!notice.file_overwrite'] = 'The files for this extension already exist in the Blesta files and will be overwritten if you use the Blesta Directory.';
$lang['AdminMain.!notice.updating_installed_extension'] = 'This extension is currently installed. It is highly recommended that you unistall it before regenerating the files or it may result in damage to the consisistency of you database.';


// Success messages
$lang['AdminMain.!success.package_deleted'] = 'The extension has been successfully deleted';
$lang['AdminMain.!success.module_created'] = 'The new module has been successfully generated and can be found at %1$s'; // Where %1$s is the path to the created module's directory
$lang['AdminMain.!success.plugin_created'] = 'The new plugin has been successfully generated and can be found at %1$s'; // Where %1$s is the path to the created plugin's directory
$lang['AdminMain.!success.merchant_created'] = 'The new gateway has been successfully generated and can be found at %1$s'; // Where %1$s is the path to the created gateway's directory
$lang['AdminMain.!success.nonmerchant_created'] = 'The new gateway has been successfully generated and can be found at %1$s'; // Where %1$s is the path to the created gateway's directory


// Controller get functions

$lang['AdminMain.getfilelocations.module'] = 'Blesta Modules Directory';
$lang['AdminMain.getfilelocations.plugin'] = 'Blesta Plugins Directory';
$lang['AdminMain.getfilelocations.merchant'] = 'Blesta Merchant Gateway Directory';
$lang['AdminMain.getfilelocations.nonmerchant'] = 'Blesta Non-Merchant Gateway Directory';
$lang['AdminMain.getfilelocations.upload'] = 'Blesta Uploads Directory';
$lang['AdminMain.getfilelocations.custom'] = 'Custom';

// Page title
$lang['AdminMain.index.page_title'] = 'Extension Generator';

// Boxtitle
$lang['AdminMain.index.boxtitle_extension_generator'] = 'Extension Generator';

$lang['AdminMain.index.boxtitle_extensions'] = 'Extension Generator - Extensions';

$lang['AdminMain.index.extension_add'] = 'Add Extension';
$lang['AdminMain.index.heading_id'] = 'ID';
$lang['AdminMain.index.heading_name'] = 'Name';
$lang['AdminMain.index.heading_type'] = 'Type';
$lang['AdminMain.index.heading_form_type'] = 'Form Type';
$lang['AdminMain.index.heading_code_examples'] = 'Code Examples';
$lang['AdminMain.index.heading_date_updated'] = 'Date Updated';
$lang['AdminMain.index.heading_options'] = 'Options';

$lang['AdminMain.index.option_edit'] = 'Edit';
$lang['AdminMain.index.option_delete'] = 'Delete';
$lang['AdminMain.index.text_confirm_delete'] = 'Are you sure you want to delete this extension?';
$lang['AdminMain.index.extensions_no_results'] = 'There are no extensions at this time.';


// General settings page
$lang['AdminMain.general.heading_general_settings'] = 'General Settings';

$lang['AdminMain.general.name'] = 'Name';
$lang['AdminMain.general.type'] = 'Extension Type';
$lang['AdminMain.general.form_type'] = 'Form Type';
$lang['AdminMain.general.code_examples'] = 'Include Example Code';
$lang['AdminMain.general.basic_info'] = 'Next - Basic Info';

$lang['AdminMain.general.tooltip_name'] = 'The display name of the extension. This is also used for creating various code. For example, if the name is My Module, then the module directory will be my_module, and the class name will be MyModule.  For plugins do not include the word "plugin" in the name as it causes some issues with code generation.';
$lang['AdminMain.general.tooltip_type'] = 'Modules handle many service features including service provisioning on remote servers. Plugins are powerful integration capable of doing most anything in Blesta. Merchant gateways process payments while keeping clients in the Blesta interface.  Non-Merchant gateways send clients to a payment processor site to complete payment.';
$lang['AdminMain.general.tooltip_form_type'] = 'Basic to use a highly truncated version of the extension form, including only the minimum necessary to generate the extension.';
$lang['AdminMain.general.tooltip_code_examples'] = 'Check to include commented out lines of sample code for features such as Cron Tasks, Event lists and tie-ins, etc.';

$lang['AdminMain.general.placeholder_name'] = 'Extension Name';


// Confirmation page
$lang['AdminMain.confirm.heading_confirm'] = 'Confirmation';
$lang['AdminMain.confirm.text_generation'] = 'Extension settings are complete.  You may review any section by clicking on the nodes in the progress bar.  Click "Generate Extension" to finish and automatically generate the files for your extension.';

$lang['AdminMain.confirm.location'] = 'Extension Location';
$lang['AdminMain.confirm.tooltip_location'] = 'The directory in which to upload generated extension files.';
$lang['AdminMain.confirm.custom_path'] = 'Custom Path';

$lang['AdminMain.confirm.generate'] = 'Generate Extension';
