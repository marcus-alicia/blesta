<?php

// General
$lang['FormRules.general.name.format'] = 'Extension names can only contain alpha_numeric charactes, underscores, and spaces.';


// Module Basic
$lang['FormRules.modulebasic.module_row.empty'] = 'Please enter a module row name.';
$lang['FormRules.modulebasic.module_row_plural.empty'] = 'Please add a plural module row name.';
$lang['FormRules.modulebasic.module_group.empty'] = 'Please enter a module group label.';
$lang['FormRules.modulebasic.authors[][name].empty'] = 'Please enter a name for each author.';


// Module Fields
$lang['FormRules.modulefields.module_rows[][name].format'] = 'Please enter a name for each module row field in the format snake_case_name.';
$lang['FormRules.modulefields.module_rows[][label].empty'] = 'Please enter a label for each module row field.';
$lang['FormRules.modulefields.module_rows[][type].valid'] = 'One or more module rows has an invalid type.';
$lang['FormRules.modulefields.module_rows[][name_key].valid'] = 'Whether to set a module row field as the name key must be set to true or false.';

$lang['FormRules.modulefields.service_fields[][name].format'] = 'Please enter a name for each service field in the format snake_case_name.';
$lang['FormRules.modulefields.service_fields[][label].empty'] = 'Please enter a label for each service field.';
$lang['FormRules.modulefields.service_fields[][type].valid'] = 'One or more service fields has an invalid type.';
$lang['FormRules.modulefields.service_fields[][name_key].valid'] = 'Whether to set a service field as the name key must be set to true or false.';

$lang['FormRules.modulefields.package_fields[][name].format'] = 'Please enter a name for each package field in the format snake_case_name.';
$lang['FormRules.modulefields.package_fields[][label].empty'] = 'Please enter a label for each package field.';
$lang['FormRules.modulefields.package_fields[][type].valid'] = 'One or more package field has an invalid type.';
$lang['FormRules.modulefields.package_fields[][name_key].valid'] = 'Whether to set a package field as the name key must be set to true or false.';


// Module Features
$lang['FormRules.modulefeatures.service_tabs[][method_name].format'] = 'Please enter a method name for each service tab in the format camelCaseName.';
$lang['FormRules.modulefeatures.service_tabs[][label].empty'] = 'Please enter a label for each service tab.';
$lang['FormRules.modulefeatures.service_tabs[][level].valid'] = 'One or more service tabs has an invalid level.';

$lang['FormRules.modulefeatures.cron_tasks[][name].format'] = 'Please enter a method name for each cron task in the format snake_case_name.';
$lang['FormRules.modulefeatures.cron_tasks[][label].empty'] = 'Please enter a label for each cron task.';
$lang['FormRules.modulefeatures.cron_tasks[][type].valid'] = 'One or more cron tasks has an invalid type.';
$lang['FormRules.modulefeatures.cron_tasks[][time].format'] = 'Please enter either a time in the format 00:00 or a numeric interval.';


// Plugin Basic
$lang['FormRules.pluginbasic.authors[][name].empty'] = 'Please enter a name for each author.';


// Plugin Database
$lang['FormRules.plugindatabase.tables[][name].format'] = 'Please enter a method name for each table in the format snake_case_name.';
$lang['FormRules.plugindatabase.tables[][columns][][name].format'] = 'Please enter a method name for each column in the format snake_case_name.';
$lang['FormRules.plugindatabase.tables[][columns][][type].valid'] = 'One or more columns has an invalid type.';
$lang['FormRules.plugindatabase.tables[][columns][][length].empty'] = "Length must be in the format 'a','b','c' for enum columns, empty for text and datetime columns, and numeric for all others.";
$lang['FormRules.plugindatabase.tables[][columns][][nullable].valid'] = 'Nullable must be set to true or false for each column.';
$lang['FormRules.plugindatabase.tables[][columns][][primary].valid'] = 'Primary must be set to true or false for each column.';


// Plugin Integrations
$lang['FormRules.pluginintegrations.actions[][location].valid'] = 'Please enter a valid action location.';
$lang['FormRules.pluginintegrations.actions[][controller].format'] = 'Please enter a controller for each action in the format snake_case_name.';
$lang['FormRules.pluginintegrations.actions[][action].format'] = 'Please enter an action for each action in all lower-case letters.';
$lang['FormRules.pluginintegrations.actions[][name].empty'] = 'Please enter a name for each action.';

$lang['FormRules.pluginintegrations.events[][event].empty'] = 'Please enter an event name for each event.';
$lang['FormRules.pluginintegrations.events[][callback].format'] = 'Please enter a callback method for each events in the format camelCaseName.';

$lang['FormRules.pluginintegrations.cards[][level].valid'] = 'One or more cards has an invalid level.';
$lang['FormRules.pluginintegrations.cards[][callback].format'] = 'Please enter a callback method for each cards in the format camelCaseName.';
$lang['FormRules.pluginintegrations.cards[][label].empty'] = 'Please enter a label for each card.';


// Merchant basic
$lang['FormRules.merchantbasic.authors[][name].empty'] = 'Please enter a name for each author.';
$lang['FormRules.merchantbasic.currencies.format'] = 'Please enter currencies in three character format separated by commas (e.g. USD,EUR,JPY).';


// Merchant fields
$lang['FormRules.merchantfields.fields[][name].format'] = 'Please enter a name for each field in the format snake_case_name.';
$lang['FormRules.merchantfields.fields[][label].empty'] = 'Please enter a label for each field.';
$lang['FormRules.merchantfields.fields[][type].valid'] = 'One or more fields has an invalid type.';


// Merchant basic
$lang['FormRules.nonmerchantbasic.authors[][name].empty'] = 'Please enter a name for each author.';
$lang['FormRules.nonmerchantbasic.currencies.format'] = 'Please enter currencies in three character format separated by commas (e.g. USD,EUR,JPY).';


// Merchant fields
$lang['FormRules.nonmerchantfields.fields[][name].format'] = 'Please enter a name for each field in the format snake_case_name.';
$lang['FormRules.nonmerchantfields.fields[][label].empty'] = 'Please enter a label for each field.';
$lang['FormRules.nonmerchantfields.fields[][type].valid'] = 'One or more fields has an invalid type.';
