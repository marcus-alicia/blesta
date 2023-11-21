<?php
$lang['Whmcs8_0.!error.host.invalid'] = "Database Host is required.";
$lang['Whmcs8_0.!error.database.invalid'] = "Database Name is required.";
$lang['Whmcs8_0.!error.user.invalid'] = "Database User is required.";
$lang['Whmcs8_0.!error.pass.invalid'] = "Database Password is required.";
$lang['Whmcs8_0.!error.key.invalid'] = "A valid AESKEY is required.";
$lang['Whmcs8_0.!error.import'] = "The import completed but the following errors ocurred:";

$lang['Whmcs8_0.!notice.gmp'] = "The 'gmp' extension was not found. This extension is recommended to improve performance and will improve the import process. You may continue without this extension but the import process may be much slower.";

$lang['Whmcs8_0.settings.host'] = "Database Host";
$lang['Whmcs8_0.settings.database'] = "Database Name";
$lang['Whmcs8_0.settings.user'] = "Database User";
$lang['Whmcs8_0.settings.pass'] = "Database Password";
$lang['Whmcs8_0.settings.key'] = "CC Encryption Hash";
$lang['Whmcs8_0.settings.key.info'] = "This is the encryption key used by WHMCS to encrypt various bits of data. It can be found in your WHMCS configuration.php file.";
$lang['Whmcs8_0.settings.balance_credit'] = "Auto balance transactions to match client credits in WHMCS";
$lang['Whmcs8_0.settings.balance_credit.info'] = "If checked, will generate a transaction or an invoice to ensure that the client credit in Blesta matches that set for the client in WHMCS. This is necessary because WHMCS does not properly account for client credits.";
$lang['Whmcs8_0.settings.enable_debug'] = "Enable Debugging";

$lang['Whmcs8_0.configuration.create_packages_true'] = "Automatically create any necessary packages during import";
$lang['Whmcs8_0.configuration.create_packages_false'] = "Manually map packages";
$lang['Whmcs8_0.configuration.local_package'] = "Local Package";
$lang['Whmcs8_0.configuration.remote_package'] = "Remote Package";
$lang['Whmcs8_0.configuration.no_local_packages'] = "You must create packages before you can map them.";

?>