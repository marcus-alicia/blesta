<?php
/**
 * Language definitions for the Admin Company Taxes settings controller/views
 *
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

// Success messages
$lang['AdminCompanyTaxes.!success.basic_updated'] = 'The Basic Tax settings were successfully updated!';
$lang['AdminCompanyTaxes.!success.taxrule_created'] = 'The tax rule has been successfully created!';
$lang['AdminCompanyTaxes.!success.taxrule_updated'] = 'The tax rule has been successfully updated!';
$lang['AdminCompanyTaxes.!success.rule_deleted'] = 'The tax rule has been successfully deleted.';

$lang['AdminCompanyTaxes.countries.all'] = '-- All --';
$lang['AdminCompanyTaxes.states.all'] = '-- All --';


// Tooltips
$lang['AdminCompanyTaxes.!tooltip.type'] = 'Inclusive will calculate tax as a part of the item prices you set and will be subtracted from the item price for tax exempt users.<br/>
Inclusive (Additive) will calculate tax in addition to the item prices you set.<br/>
Exclusive will calculate tax in addition to the item prices you set, but will not include it in the order total display.';
$lang['AdminCompanyTaxes.!tooltip.level'] = 'The tax level allows you to set the tax order if multiple tax rules apply.';
$lang['AdminCompanyTaxes.!tooltip.name'] = 'The displayed name of the tax (e.g. Sales Tax).';
$lang['AdminCompanyTaxes.!tooltip.amount'] = 'The tax amount as a percentage.';
$lang['AdminCompanyTaxes.!tooltip.country'] = 'Select the country that this tax rule applies to.';
$lang['AdminCompanyTaxes.!tooltip.state'] = 'Select the state/province that this tax rule applies to.';


// Basic Tax settings
$lang['AdminCompanyTaxes.basic.page_title'] = 'Settings > Company > Taxes > Basic Tax Settings';
$lang['AdminCompanyTaxes.basic.boxtitle_basic'] = 'Basic Tax Settings';

$lang['AdminCompanyTaxes.basic.heading_general'] = 'General Settings';
$lang['AdminCompanyTaxes.basic.field_enable_tax'] = 'Enable Tax';
$lang['AdminCompanyTaxes.basic.note_enable_tax'] = 'Check this option to enable tax for this company.';
$lang['AdminCompanyTaxes.basic.field_cascade_tax'] = 'Cascade Tax';
$lang['AdminCompanyTaxes.basic.note_cascade_tax'] = 'If enabled, tax level 1 will first be assessed on the invoice total, and tax level 2 would be assessed on this new total including tax level 1. This results in a tax on tax. Othewise tax level 1 and tax level 2 are assessed only on the pre-tax invoice total.';
$lang['AdminCompanyTaxes.basic.field_setup_fee_tax'] = 'Tax Setup Fees';
$lang['AdminCompanyTaxes.basic.note_setup_fee_tax'] = 'If enabled, any setup fees will be taxed.';
$lang['AdminCompanyTaxes.basic.field_cancelation_fee_tax'] = 'Tax Cancelation Fees';
$lang['AdminCompanyTaxes.basic.note_cancelation_fee_tax'] = 'If enabled, any cancelation fees will be taxed.';
$lang['AdminCompanyTaxes.basic.field_taxid'] = 'Tax ID/VATIN';

$lang['AdminCompanyTaxes.basic.heading_tax_provider'] = '%1$s Settings'; // %1$s is the name of the tax provider

$lang['AdminCompanyTaxes.basic.field_addsubmit'] = 'Update Settings';


// Tax Rules
$lang['AdminCompanyTaxes.rules.page_title'] = 'Settings > Company > Taxes > Tax Rules';
$lang['AdminCompanyTaxes.rules.no_results'] = 'There are no level %1$s tax rules.'; // %1$s is the tax level number

$lang['AdminCompanyTaxes.rules.categorylink_addrule'] = 'Add Tax Rule';
$lang['AdminCompanyTaxes.rules.boxtitle_rules'] = 'Tax Rules';

$lang['AdminCompanyTaxes.rules.heading_level1'] = 'Level 1 Rules';
$lang['AdminCompanyTaxes.rules.heading_level2'] = 'Level 2 Rules';

$lang['AdminCompanyTaxes.rules.text_name'] = 'Name';
$lang['AdminCompanyTaxes.rules.text_type'] = 'Type';
$lang['AdminCompanyTaxes.rules.text_amount'] = 'Amount';
$lang['AdminCompanyTaxes.rules.text_country'] = 'Country';
$lang['AdminCompanyTaxes.rules.text_state'] = 'State/Province';
$lang['AdminCompanyTaxes.rules.text_options'] = 'Options';
$lang['AdminCompanyTaxes.rules.text_all'] = 'All';
$lang['AdminCompanyTaxes.rules.option_edit'] = 'Edit';
$lang['AdminCompanyTaxes.rules.option_delete'] = 'Delete';
$lang['AdminCompanyTaxes.rules.confirm_delete'] = 'Are you sure you want to delete this tax rule?';


// Add Tax Rule
$lang['AdminCompanyTaxes.add.page_title'] = 'Settings > Company > Taxes > Add Tax Rule';
$lang['AdminCompanyTaxes.add.boxtitle_add'] = 'Add Tax Rule';

$lang['AdminCompanyTaxes.add.field.type'] = 'Tax Type';
$lang['AdminCompanyTaxes.add.field.level'] = 'Tax Level';
$lang['AdminCompanyTaxes.add.field.level1'] = 'Level 1';
$lang['AdminCompanyTaxes.add.field.level2'] = 'Level 2';
$lang['AdminCompanyTaxes.add.field.name'] = 'Name of Tax';
$lang['AdminCompanyTaxes.add.field.amount'] = 'Amount';
$lang['AdminCompanyTaxes.add.field.country'] = 'Country';
$lang['AdminCompanyTaxes.add.field.state'] = 'State/Province';

$lang['AdminCompanyTaxes.add.field.addsubmit'] = 'Create Rule';


// Edit Tax Rule
$lang['AdminCompanyTaxes.edit.page_title'] = 'Settings > Company > Taxes > Edit Tax Rule';
$lang['AdminCompanyTaxes.edit.boxtitle_edit'] = 'Edit Tax Rule';

$lang['AdminCompanyTaxes.edit.field.type'] = 'Tax Type';
$lang['AdminCompanyTaxes.edit.field.level'] = 'Tax Level';
$lang['AdminCompanyTaxes.edit.field.level1'] = 'Level 1';
$lang['AdminCompanyTaxes.edit.field.level2'] = 'Level 2';
$lang['AdminCompanyTaxes.edit.field.name'] = 'Name of Tax';
$lang['AdminCompanyTaxes.edit.field.amount'] = 'Amount';
$lang['AdminCompanyTaxes.edit.field.country'] = 'Country';
$lang['AdminCompanyTaxes.edit.field.state'] = 'State/Province';

$lang['AdminCompanyTaxes.edit.field.editsubmit'] = 'Edit Rule';
