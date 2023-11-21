<?php
$lang['Whmcs5_2.!error.host.invalid'] = "Database Host is required.";
$lang['Whmcs5_2.!error.database.invalid'] = "Database Name is required.";
$lang['Whmcs5_2.!error.user.invalid'] = "Database User is required.";
$lang['Whmcs5_2.!error.pass.invalid'] = "Database Password is required.";
$lang['Whmcs5_2.!error.key.invalid'] = "A valid AESKEY is required.";
$lang['Whmcs5_2.!error.import'] = "The import completed but the following errors ocurred:";

$lang['Whmcs5_2.!notice.gmp'] = "The 'gmp' extension was not found. This extension is recommended to improve performance and will improve the import process. You may continue without this extension but the import process may be much slower.";

$lang['Whmcs5_2.settings.host'] = "Database Host";
$lang['Whmcs5_2.settings.database'] = "Database Name";
$lang['Whmcs5_2.settings.user'] = "Database User";
$lang['Whmcs5_2.settings.pass'] = "Database Password";
$lang['Whmcs5_2.settings.key'] = "CC Encryption Hash";
$lang['Whmcs5_2.settings.key.info'] = "This is the encryption key used by WHMCS to encrypt various bits of data. It can be found in your WHMCS configuration.php file.";
$lang['Whmcs5_2.settings.balance_credit'] = "Auto balance transactions to match client credits in WHMCS";
$lang['Whmcs5_2.settings.balance_credit.info'] = "If checked, will generate a transaction or an invoice to ensure that the client credit in Blesta matches that set for the client in WHMCS. This is necessary because WHMCS does not properly account for client credits.";
$lang['Whmcs5_2.settings.enable_debug'] = "Enable Debugging";

$lang['Whmcs5_2.configuration.create_packages_true'] = "Automatically create any necessary packages during import";
$lang['Whmcs5_2.configuration.create_packages_false'] = "Manually map packages";
$lang['Whmcs5_2.configuration.local_package'] = "Local Package";
$lang['Whmcs5_2.configuration.remote_package'] = "Remote Package";
$lang['Whmcs5_2.configuration.no_local_packages'] = "You must create packages before you can map them.";

?>