<?php
$lang['Util.Tax.united_kingdom_tax.tax_provider_name'] = "UK HMRC VAT";
$lang['Util.Tax.united_kingdom_tax.tax_name'] = "VAT";
$lang['Util.Tax.united_kingdom_tax.tax_id_name'] = "VAT Number";
$lang['Util.Tax.united_kingdom_tax.tax_note'] = "%1\$s (%2\$s%%)"; // %1$s is the name of the tax and %2$s the amount of the tax
$lang['Util.Tax.united_kingdom_tax.tax_deduction_note'] = "%1\$s Deduction (%2\$s%%)"; // %1$s is the name of the tax and %2$s the amount of the tax
$lang['Util.Tax.united_kingdom_tax.tax_note_domestic_reverse_charge'] = "Reverse Charge VAT Act 1994 Section 55A Applies. Client needs to account for VAT to the HMRC at the rate shown.";

$lang['Util.Tax.united_kingdom_tax.fields.country_uk'] = 'United Kingdom';
$lang['Util.Tax.united_kingdom_tax.fields.enable_uk_vat'] = 'Enable UK VAT Validation';
$lang['Util.Tax.united_kingdom_tax.fields.note_enable_uk_vat'] = 'Check this option to enable UK VAT number validation for this company.';
$lang['Util.Tax.united_kingdom_tax.fields.tax_exempt_uk_vat'] = 'Automatically set as Tax Exempt';
$lang['Util.Tax.united_kingdom_tax.fields.note_tax_exempt_uk_vat'] = 'Automatically set a client as Tax Exempt if their VAT number is valid.';
$lang['Util.Tax.united_kingdom_tax.fields.tax_intra_eu_uk_vat'] = 'Treat transactions as Intra-EU trade';
$lang['Util.Tax.united_kingdom_tax.fields.note_tax_intra_eu_uk_vat'] = 'Due to the Brexit, the United Kingdom is no longer a member of the EU, and all transactions conducted outside of the United Kingdom will be treated as foreign transactions. Enabling this option will treat all transactions made within a EU member as an Intra-EU operation and the Reverse Charge principle will be applied.';
