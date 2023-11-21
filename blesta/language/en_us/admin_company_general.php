<?php
/**
 * Language definitions for the Admin Company General settings controller/views
 *
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
// Errors
$lang['AdminCompanyGeneral.!error.captcha_gd'] = 'The GD extension is required for generating the internal captcha.';


// Success messages
$lang['AdminCompanyGeneral.!success.localization_updated'] = 'The localization settings have been successfully updated.';
$lang['AdminCompanyGeneral.!success.encryption_updated'] = 'The encryption settings have been successfully updated.';
$lang['AdminCompanyGeneral.!success.contact_type_added'] = 'The contact type "%1$s" has been successfully added.'; // %1$s is the name of the contact type
$lang['AdminCompanyGeneral.!success.contact_type_updated'] = 'The contact type "%1$s" has been successfully updated.'; // %1$s is the name of the contact type
$lang['AdminCompanyGeneral.!success.contact_type_deleted'] = 'The contact type "%1$s" has been successfully deleted.'; // %1$s is the name of the contact type

$lang['AdminCompanyGeneral.!success.language_installed'] = 'The language %1$s has been successfully installed.'; // %1$s is the name of the language
$lang['AdminCompanyGeneral.!success.language_uninstalled'] = 'The language %1$s has been successfully uninstalled.'; // %1$s is the name of the language
$lang['AdminCompanyGeneral.!success.marketing_updated'] = 'The marketing settings have been successfully updated.';
$lang['AdminCompanyGeneral.!success.smartsearch_updated'] = 'The Smart Search settings have been successfully updated.';
$lang['AdminCompanyGeneral.!success.humanverification_updated'] = 'The human verification settings have been successfully updated.';


// Tooltips
$lang['AdminCompanyGeneral.!tooltip.language'] = 'The default language used by the system. To add more languages see Internationalization settings.';
$lang['AdminCompanyGeneral.!tooltip.client_set_lang'] = 'When checked, clients may choose their own language to use from those available in the system.';
$lang['AdminCompanyGeneral.!tooltip.calendar_begins'] = 'Set the calendar\'s weekly start day.';
$lang['AdminCompanyGeneral.!tooltip.timezone'] = 'This is the system timezone that date and time conversions will be formatted into.';
$lang['AdminCompanyGeneral.!tooltip.date_format'] = 'Sets the date format. This format is used when displaying a date. Please refer to the php manual for available options.';
$lang['AdminCompanyGeneral.!tooltip.datetime_format'] = 'Sets the date and time format. This format is used when displaying a date and time. Please refer to the php manual for available options.';
$lang['AdminCompanyGeneral.!tooltip.country'] = 'This is the country selected by default on relevant forms.';

$lang['AdminCompanyGeneral.!tooltip.captcha'] = 'This is the captcha used by default, the Internal Captcha requires the GD extension.';


// Localization
$lang['AdminCompanyGeneral.localization.page_title'] = 'Settings > Company > General > Localization';
$lang['AdminCompanyGeneral.localization.boxtitle_localization'] = 'Localization';
$lang['AdminCompanyGeneral.localization.tz_format'] = '(UTC %1$s) %2$s'; // %1$s is the UTC offset, %2$s is the timezone name

$lang['AdminCompanyGeneral.localization.field.language'] = 'Default Language';
$lang['AdminCompanyGeneral.localization.field.setlanguage'] = 'Client may set Language';
$lang['AdminCompanyGeneral.localization.field.calendar'] = 'Calendar Start Day';
$lang['AdminCompanyGeneral.localization.field.sunday'] = 'Sunday';
$lang['AdminCompanyGeneral.localization.field.monday'] = 'Monday';
$lang['AdminCompanyGeneral.localization.field.timezone'] = 'Timezone';
$lang['AdminCompanyGeneral.localization.field.dateformat'] = 'Date Format';
$lang['AdminCompanyGeneral.localization.field.datetimeformat'] = 'Date Time Format';
$lang['AdminCompanyGeneral.localization.field.country'] = 'Default Country';
$lang['AdminCompanyGeneral.localization.field.localizationsubmit'] = 'Update Settings';


// Internationalization
$lang['AdminCompanyGeneral.!notice.international_languages'] = 'A crowdsourced translation project exists at translate.blesta.com. You may contribute to, and download language translations there. To install, unzip the contents of the file to your Blesta installation directory. Then, refresh this page, and click the Install link.';
$lang['AdminCompanyGeneral.international.page_title'] = 'Settings > Company > General > Internationalization';
$lang['AdminCompanyGeneral.international.boxtitle_international'] = 'Internationalization';

$lang['AdminCompanyGeneral.international.text_language'] = 'Language';
$lang['AdminCompanyGeneral.international.text_iso'] = 'ISO 639-1, 3166-1';
$lang['AdminCompanyGeneral.international.text_options'] = 'Options';

$lang['AdminCompanyGeneral.international.option_install'] = 'Install';
$lang['AdminCompanyGeneral.international.option_uninstall'] = 'Uninstall';

$lang['AdminCompanyGeneral.international.confirm_install'] = 'Are you sure you want to install the language %1$s?'; // %1$s is the name of the language
$lang['AdminCompanyGeneral.international.confirm_uninstall'] = 'Are you sure you want to uninstall the language %1$s? This language will be uninstalled and all email templates in this language will be permanently deleted.'; // %1$s is the name of the language


// Encryption
$lang['AdminCompanyGeneral.encryption.page_title'] = 'Settings > Company > General > Encryption';
$lang['AdminCompanyGeneral.!notice.passphrase'] = 'WARNING: Setting a passphrase will prevent locally stored payment accounts from being automatically processed. You will be required to manually batch payments by entering your passphrase. For more information regarding this feature please consult the manual.';
$lang['AdminCompanyGeneral.!notice.passphrase_set'] = 'WARNING: A passphrase has been set. You are required to manually batch payments with your passphrase. Changing your passphrase to a blank passphrase will remove this requirement.';

$lang['AdminCompanyGeneral.encryption.boxtitle_encryption'] = 'Encryption';

$lang['AdminCompanyGeneral.encryption.field_current_passphrase'] = 'Current Private Key Passphrase';
$lang['AdminCompanyGeneral.encryption.field_private_key_passphrase'] = 'New Private Key Passphrase';
$lang['AdminCompanyGeneral.encryption.field_confirm_new_passphrase'] = 'Confirm Private Key Passphrase';
$lang['AdminCompanyGeneral.encryption.field_agree'] = 'I have saved this passphrase to a safe location';

$lang['AdminCompanyGeneral.encryption.field_encryptionsubmit'] = 'Update Passphrase';


// Contact Types
$lang['AdminCompanyGeneral.contacttypes.page_title'] = 'Settings > Company > General > Contact Types';
$lang['AdminCompanyGeneral.contacttypes.categorylink_addtype'] = 'Create Contact Type';
$lang['AdminCompanyGeneral.contacttypes.boxtitle_types'] = 'Contact Types';

$lang['AdminCompanyGeneral.contacttypes.heading_name'] = 'Name';
$lang['AdminCompanyGeneral.contacttypes.heading_define'] = 'Uses Language Definition';
$lang['AdminCompanyGeneral.contacttypes.heading_options'] = 'Options';

$lang['AdminCompanyGeneral.contacttypes.text_yes'] = 'Yes';
$lang['AdminCompanyGeneral.contacttypes.text_no'] = 'No';
$lang['AdminCompanyGeneral.contacttypes.option_edit'] = 'Edit';
$lang['AdminCompanyGeneral.contacttypes.option_delete'] = 'Delete';

$lang['AdminCompanyGeneral.contacttypes.modal_delete'] = 'Deleting this contact type will cause all contacts assigned to this type to be placed into the default "Billing" type. Are you sure you want to delete this contact type?';

$lang['AdminCompanyGeneral.contacttypes.no_results'] = 'There are no Contact Types.';

$lang['AdminCompanyGeneral.!contacttypes.is_lang'] = 'Only check this box if you have added a language definition for this contact type in the custom language file.';


// Add Contact Type
$lang['AdminCompanyGeneral.addcontacttype.page_title'] = 'Settings > Company > General > Create Contact Type';
$lang['AdminCompanyGeneral.addcontacttype.boxtitle_addcontacttype'] = 'Create Contact Type';

$lang['AdminCompanyGeneral.addcontacttype.field_name'] = 'Name';
$lang['AdminCompanyGeneral.addcontacttype.field_is_lang'] = 'Use Language Definition';
$lang['AdminCompanyGeneral.addcontacttype.field_contacttypesubmit'] = 'Create Contact Type';


// Edit Contact Type
$lang['AdminCompanyGeneral.editcontacttype.page_title'] = 'Settings > Company > General > Edit Contact Type';
$lang['AdminCompanyGeneral.editcontacttype.boxtitle_editcontacttype'] = 'Edit Contact Type';

$lang['AdminCompanyGeneral.editcontacttype.field_name'] = 'Name';
$lang['AdminCompanyGeneral.editcontacttype.field_is_lang'] = 'Use Language Definition';
$lang['AdminCompanyGeneral.editcontacttype.field_contacttypesubmit'] = 'Edit Contact Type';


// Marketing
$lang['AdminCompanyGeneral.marketing.boxtitle_marketing'] = 'Marketing';

$lang['AdminCompanyGeneral.marketing.field_show_receive_email_marketing'] = 'Present clients with an option to opt-in/opt-out of email marketing';
$lang['AdminCompanyGeneral.marketing.field_submit'] = 'Update Settings';


// Smart Search
$lang['AdminCompanyGeneral.smartsearch.boxtitle_smartsearch'] = 'Smart Search';

$lang['AdminCompanyGeneral.smartsearch.field_client_search'] = 'Client Search';
$lang['AdminCompanyGeneral.smartsearch.field_invoice_search'] = 'Invoice Search';
$lang['AdminCompanyGeneral.smartsearch.field_quotation_search'] = 'Quotation Search';
$lang['AdminCompanyGeneral.smartsearch.field_transaction_search'] = 'Transaction Search';
$lang['AdminCompanyGeneral.smartsearch.field_service_search'] = 'Service Search';
$lang['AdminCompanyGeneral.smartsearch.field_package_search'] = 'Package Search';
$lang['AdminCompanyGeneral.smartsearch.field_submit'] = 'Update Settings';


// Human verification
$lang['AdminCompanyGeneral.humanverification.boxtitle_humanverification'] = 'Human Verification';

$lang['AdminCompanyGeneral.humanverification.heading_captcha_provider'] = 'Captcha Provider';
$lang['AdminCompanyGeneral.humanverification.heading_enabled_forms'] = 'Enabled Forms';

$lang['AdminCompanyGeneral.humanverification.field_captcha'] = 'Captcha';
$lang['AdminCompanyGeneral.humanverification.field_captcha_none'] = 'None';
$lang['AdminCompanyGeneral.humanverification.field_captcha_recaptcha'] = 'reCaptcha';
$lang['AdminCompanyGeneral.humanverification.field_captcha_internalcaptcha'] = 'Internal Captcha';
$lang['AdminCompanyGeneral.humanverification.field_recaptcha_pub_key'] = 'reCaptcha Site Key';
$lang['AdminCompanyGeneral.humanverification.field_recaptcha_shared_key'] = 'reCaptcha Shared Key';
$lang['AdminCompanyGeneral.humanverification.field_captcha_enabled_forms_admin_login'] = 'Admin Login';
$lang['AdminCompanyGeneral.humanverification.field_captcha_enabled_forms_client_login'] = 'Client Login';
$lang['AdminCompanyGeneral.humanverification.field_captcha_enabled_forms_admin_login_reset'] = 'Admin Reset My Password';
$lang['AdminCompanyGeneral.humanverification.field_captcha_enabled_forms_client_login_reset'] = 'Client Reset My Password';
$lang['AdminCompanyGeneral.humanverification.field_captcha_enabled_forms_client_login_forgot'] = 'Client Forgot My Username';
$lang['AdminCompanyGeneral.humanverification.field_submit'] = 'Update Settings';
