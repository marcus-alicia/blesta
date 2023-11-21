<?php
/**
 * Language definitions for the Package Options model
 *
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

// Errors
$lang['PackageOptions.!error.company_id.exists'] = 'Invalid company ID.';
$lang['PackageOptions.!error.label.empty'] = 'Please enter a label.';
$lang['PackageOptions.!error.label.length'] = 'The label may not exceed 128 characters in length.';
$lang['PackageOptions.!error.name.empty'] = 'Please enter a name for this option.';
$lang['PackageOptions.!error.name.length'] = 'The name may not exceed 128 characters in length.';
$lang['PackageOptions.!error.type.valid'] = 'Please select an option type.';
$lang['PackageOptions.!error.values.count'] = 'There may only be one option value entry for checkbox, quantity, text, textarea, or password types.';
$lang['PackageOptions.!error.values.unique'] = 'Option values must be unique.';
$lang['PackageOptions.!error.values.select_value'] = 'At least one option value contains invalid special characters.';
$lang['PackageOptions.!error.values.active_status'] = 'At least one option value must be active.';
$lang['PackageOptions.!error.values.single_default_value'] = 'Only one value may be selected as the default value.';
$lang['PackageOptions.!error.values[][step].valid'] = 'A step value may only be set for the quantity type, and must be a value of 1 or greater.';
$lang['PackageOptions.!error.values[][min].valid'] = 'A minimum limit value may only be set for the quantity type, and must be a value of 0 or greater.';
$lang['PackageOptions.!error.values[][max].valid'] = 'The maximum limit value may only be set for the quantity type, and must be a value of 1 or greater.';
$lang['PackageOptions.!error.values[][name].empty'] = 'Please enter a name for the option value.';
$lang['PackageOptions.!error.values[][name].length'] = 'The option value name may not exceed 128 characters in length.';
$lang['PackageOptions.!error.values[][value].length'] = 'The option value may not exceed 255 characters in length.';
$lang['PackageOptions.!error.values[][value].edit_in_use'] = 'The option value may not be changed because it is currently in use by a service.';
$lang['PackageOptions.!error.values[][value].delete_in_use'] = 'The option value may not be deleted because it is currently in use by a service.';
$lang['PackageOptions.!error.values[][status].valid'] = 'Please select a valid option value status.';
$lang['PackageOptions.!error.values[][default].inactive'] = 'An inactive option value may not be set as the default value.';
$lang['PackageOptions.!error.values[][default].qty_valid'] = 'The default value quantity selected is invalid for the configured min, max, and step.';
$lang['PackageOptions.!error.values[][default].format'] = 'The default must be a number.';
$lang['PackageOptions.!error.values[][id].exists'] = 'Invalid package option value ID.';
$lang['PackageOptions.!error.groups.exists'] = 'At least one of the package option group IDs given does not exist or does not belong to the same company.';
$lang['PackageOptions.!error.option_id.exists'] = 'Invalid package option ID.';
$lang['PackageOptions.!error.hidden.valid'] = 'Whether to hide this option must be set to 0 or 1.';


// Pricing errors
$lang['PackageOptions.!error.values[][pricing][][id].exists'] = 'Invalid package option pricing ID.';
$lang['PackageOptions.!error.values[][pricing][][id].delete_in_use'] = 'The option value pricing may not be deleted because it is currently in use by a service.';
$lang['PackageOptions.!error.values[][pricing][][term].format'] = 'Term must be a number.';
$lang['PackageOptions.!error.values[][pricing][][term].length'] = 'Term length may not exceed 5 characters.';
$lang['PackageOptions.!error.values[][pricing][][term].valid'] = 'The term must be greater than 0.';
$lang['PackageOptions.!error.values[][pricing][][period].format'] = 'Invalid period type.';
$lang['PackageOptions.!error.values[][pricing][][price].format'] = 'Price must be a number.';
$lang['PackageOptions.!error.values[][pricing][][price_renews].format'] = 'Renewal price must be a number.';
$lang['PackageOptions.!error.values[][pricing][][price_renews].valid'] = 'Renewal price cannot be set for a one time period.';
$lang['PackageOptions.!error.values[][pricing][][setup_fee].format'] = 'Setup fee must be a number.';
$lang['PackageOptions.!error.values[][pricing][][currency].format'] = 'Currency code must be 3 characters.';


// Types
$lang['PackageOptions.gettypes.checkbox'] = 'Checkbox';
$lang['PackageOptions.gettypes.select'] = 'Drop-down';
$lang['PackageOptions.gettypes.quantity'] = 'Quantity';
$lang['PackageOptions.gettypes.radio'] = 'Radio';
$lang['PackageOptions.gettypes.text'] = 'Text';
$lang['PackageOptions.gettypes.textarea'] = 'Textarea';
$lang['PackageOptions.gettypes.password'] = 'Password';


// Option values
$lang['PackageOptions.getvaluestatuses.active'] = 'Active';
$lang['PackageOptions.getvaluestatuses.inactive'] = 'Inactive';


// Fields
$lang['PackageOptions.getfields.label_quantity'] = 'x %1$s %2$s'; // %1$s is the option value, %2$s is the option price
$lang['PackageOptions.getfields.label_quantity_recurring'] = 'x %1$s %2$s (renews @ %3$s)'; // %1$s is the option value, %2$s is the option initial price, %3$is is the option renewal price
$lang['PackageOptions.getfields.label_quantity_setup'] = 'x %1$s %2$s + %3$s setup'; // %1$s is the option value, %2$s is the option price, %3$s is the setup fee
$lang['PackageOptions.getfields.label_quantity_setup_recurring'] = 'x %1$s %2$s + %3$s setup (renews @ %4$s)'; // %1$s is the option value, %2$s is the option initial price, %3$s is the setup fee, %4$s is the option renewal price
$lang['PackageOptions.getfields.label_radio'] = '%1$s (%2$s)'; // %1$s is the option value, %2$s is the option price
$lang['PackageOptions.getfields.label_radio_recurring'] = '%1$s (%2$s, renews @ %3$s)'; // %1$s is the option value, %2$s is the option initial price, %3$s is the option renewal price
$lang['PackageOptions.getfields.label_radio_setup'] = '%1$s (%2$s + %3$s setup)'; // %1$s is the option value, %2$s is the option price, %3$s is the setup fee
$lang['PackageOptions.getfields.label_radio_setup_recurring'] = '%1$s (%2$s + %3$s setup, renews @ %4$s)'; // %1$s is the option value, %2$s is the option initial price, %3$s is the setup fee, %$4s is the option renewal price
$lang['PackageOptions.getfields.label_select'] = '%1$s (%2$s)'; // %1$s is the option value, %2$s is the option price
$lang['PackageOptions.getfields.label_select_recurring'] = '%1$s (%2$s, renews @ %3$s)'; // %1$s is the option value, %2$s is the option initial price, %3$s is the option renewal price
$lang['PackageOptions.getfields.label_select_setup'] = '%1$s (%2$s + %3$s setup)'; // %1$s is the option value, %2$s is the option price, %3$s is the setup fee
$lang['PackageOptions.getfields.label_select_setup_recurring'] = '%1$s (%2$s + %3$s setup, renews @ %4$s)'; // %1$s is the option value, %2$s is the option initial price, %3$s is the setup fee, %4$s is the option renewal price
$lang['PackageOptions.getfields.label_checkbox'] = '%1$s (%2$s)'; // %1$s is the option value, %2$s is the option price
$lang['PackageOptions.getfields.label_checkbox_recurring'] = '%1$s (%2$s, renews @ %3$s)'; // %1$s is the option value, %2$s is the option initial price, %3$s is the option renewal price
$lang['PackageOptions.getfields.label_checkbox_setup'] = '%1$s (%2$s + %3$s setup)'; // %1$s is the option value, %2$s is the option price, %3$s is the setup fee
$lang['PackageOptions.getfields.label_checkbox_setup_recurring'] = '%1$s (%2$s + %3$s setup, renews @ %4$s)'; // %1$s is the option value, %2$s is the option initial price, %3$s is the setup fee, %4$s is the option renewal price
$lang['PackageOptions.getfields.label_text'] = '%1$s'; // %1$s is the option price
$lang['PackageOptions.getfields.label_text_recurring'] = '%1$s (renews @ %2$s)'; // %1$s is the option initial price, %2$s is the option renewal price
$lang['PackageOptions.getfields.label_text_setup'] = '%1$s + %2$s setup'; // %1$s is the option price, %2$s is the setup fee
$lang['PackageOptions.getfields.label_text_setup_recurring'] = '%1$s + %2$s setup (renews @ %3$s)'; // %1$s is the option initial price, %2$s is the setup fee, %3$s is the option renewal price
$lang['PackageOptions.getfields.label_password'] = '%1$s'; // %1$s is the option price
$lang['PackageOptions.getfields.label_password_recurring'] = '%1$s (renews @ %2$s)'; // %1$s is the option initial price, %2$s is the option renewal price
$lang['PackageOptions.getfields.label_password_setup'] = '%1$s + %2$s setup'; // %1$s is the option price, %2$s is the setup fee
$lang['PackageOptions.getfields.label_password_setup_recurring'] = '%1$s + %2$s setup (renews @ %3$s)'; // %1$s is the option initial price, %2$s is the setup fee, %3$s is the option renewal price
$lang['PackageOptions.getfields.label_textarea'] = '%1$s'; // %1$s is the option price
$lang['PackageOptions.getfields.label_textarea_recurring'] = '%1$s (renews @ %2$s)'; // %1$s is the option initial price, %2$s is the option renewal price
$lang['PackageOptions.getfields.label_textarea_setup'] = '%1$s + %2$s setup'; // %1$s is the option price, %2$s is the setup fee
$lang['PackageOptions.getfields.label_textarea_setup_recurring'] = '%1$s + %2$s setup (renews @ %3$s)'; // %1$s is the option initial price, %2$s is the setup fee, %3$s is the option renewal price
