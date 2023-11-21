<?php
/**
 * Language definitions for the Admin Company Billing settings controller/views
 *
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

// Success messages
$lang['AdminCompanyBilling.!success.invoices_updated'] = 'The Invoice and Charge settings were successfully updated!';
$lang['AdminCompanyBilling.!success.notices_updated'] = 'The Notices were successfully updated!';
$lang['AdminCompanyBilling.!success.coupon_created'] = 'The coupon has been successfully created!';
$lang['AdminCompanyBilling.!success.coupon_updated'] = 'The coupon has been successfully updated!';
$lang['AdminCompanyBilling.!success.coupon_deleted'] = 'The coupon has been successfully deleted!';
$lang['AdminCompanyBilling.!success.acceptedtypes_updated'] = 'The Accepted Payment Type settings were successfully updated!';
$lang['AdminCompanyBilling.!success.deliverymethods_updated'] = 'The Invoice Delivery settings were successfully updated!';
$lang['AdminCompanyBilling.!success.latefees_updated'] = 'The Late Fees settings were successfully updated!';
$lang['AdminCompanyBilling.!success.customization_updated'] = 'The Invoice Customization settings were successfully updated!';


// Error messages
$lang['AdminCompanyBilling.!error.inv_start.valid'] = 'The Invoice Start Value must be a number.';
$lang['AdminCompanyBilling.!error.inv_increment.valid'] = 'The Invoice Increment Value must be a number.';
$lang['AdminCompanyBilling.!error.quotation_start.valid'] = 'The Quotation Start Value must be a number.';
$lang['AdminCompanyBilling.!error.quotation_increment.valid'] = 'The Quotation Increment Value must be a number.';
$lang['AdminCompanyBilling.!error.amount.format'] = 'The fee Amount must be a number.';
$lang['AdminCompanyBilling.!error.minimum.format'] = 'The fee Minimum amount must be a number.';
$lang['AdminCompanyBilling.!error.extension_zlib'] = 'The Zlib extension is required for invoice compression.';


// Tooltips
$lang['AdminCompanyBilling.!tooltip.coupon_term_value'] = 'Terms should be entered as a CSV list (e.g. "1,3,4" meaning 1 day, 3 days, and 4 days when entered in the \'Day\' row).';
$lang['AdminCompanyBilling.!tooltip.coupon_quantity'] = 'The quantity represents the maximum number of times this coupon can be used before it is expired.';

$lang['AdminCompanyBilling.!tooltip.inv_days_before_renewal'] = 'The number of days before a service or recurring invoice renews to generate an invoice.';
$lang['AdminCompanyBilling.!tooltip.quotation_valid_days'] = 'The number of days after issuing a quote to set it as expired.';
$lang['AdminCompanyBilling.!tooltip.quotation_dead_days'] = 'The number of days after a quote expires to set it as dead.';
$lang['AdminCompanyBilling.!tooltip.quotation_deposit_percentage'] = 'The default initial deposit percentage for a quote.';
$lang['AdminCompanyBilling.!tooltip.autodebit_days_before_due'] = 'The number of days before an invoice is due to auto debit the client\'s default payment account (if the client is configured for auto debit).';
$lang['AdminCompanyBilling.!tooltip.suspend_services_days_after_due'] = 'The number of days to wait after an invoice for a service has been past due before suspending the service.';
$lang['AdminCompanyBilling.!tooltip.autodebit_attempts'] = 'The number of attempts and failures to process a payment account before that payment account is disabled from being automatically debited.';
$lang['AdminCompanyBilling.!tooltip.service_renewal_attempts'] = 'The number of attempts and failures to process a service renewal before that services is disabled from being automatically renewed.';
$lang['AdminCompanyBilling.!tooltip.cancel_service_changes_days'] = 'Queued service changes will be automatically canceled when their invoice goes unpaid for the selected number of days.';
$lang['AdminCompanyBilling.!tooltip.apply_inv_late_fees'] = 'Apply a late fee to open invoices a configured number of days after due.';
$lang['AdminCompanyBilling.!tooltip.autodebit'] = 'Enables autodebiting of a client when payment is due.';
$lang['AdminCompanyBilling.!tooltip.client_set_invoice'] = 'Check to allow the client to choose which method of invoice delivery they prefer (e.g. Paper or Email).';
$lang['AdminCompanyBilling.!tooltip.inv_suspended_services'] = 'Check to continue invoicing suspended services.';
$lang['AdminCompanyBilling.!tooltip.inv_group_services'] = 'Creates a single invoice for services that renew on the same day for a client. Unchecking will create a separate invoice for each service.';
$lang['AdminCompanyBilling.!tooltip.inv_append_descriptions'] = 'Check to append the text version of Package descriptions to service line items.';
$lang['AdminCompanyBilling.!tooltip.inv_lines_verbose_option_dates'] = 'Check to include the service date range for each configurable option invoice line item.';
$lang['AdminCompanyBilling.!tooltip.clients_cancel_services'] = 'Check to allow clients to cancel their own services.';
$lang['AdminCompanyBilling.!tooltip.clients_renew_services'] = 'Check to allow clients to renew their own services in advance.';
$lang['AdminCompanyBilling.!tooltip.synchronize_addons'] = 'When checked, newly-created addon services that match the parent\'s monthly or yearly terms will be prorated to the parent\'s renew date.';
$lang['AdminCompanyBilling.!tooltip.client_create_addons'] = 'Check to allow clients to order addons for any of their services that support them.';
$lang['AdminCompanyBilling.!tooltip.client_change_service_term'] = 'Check to allow clients to change the term of recurring services.';
$lang['AdminCompanyBilling.!tooltip.client_change_service_package'] = 'Check to allow clients to change the package of recurring services.';
$lang['AdminCompanyBilling.!tooltip.client_prorate_credits'] = 'Check to allow prorated credits for services, or service configurable options, that are downgraded.';
$lang['AdminCompanyBilling.!tooltip.auto_apply_credits'] = 'Check to allow Blesta to automatically apply credits to open invoices (oldest invoices first).';
$lang['AdminCompanyBilling.!tooltip.auto_paid_pending_services'] = 'Check to allow Blesta to automatically provision services that are both pending and have an invoice that has been paid.';
$lang['AdminCompanyBilling.!tooltip.void_invoice_canceled_service'] = 'When checked, service cancellations will also void any open invoices associated with the canceled service. If such an invoice contains line items not associated with the service, the service line items will be removed instead, and the invoice will not be voided.';
$lang['AdminCompanyBilling.!tooltip.void_inv_canceled_service_days'] = 'If the invoice associated with a cancelled service is past due, it will only be voided if it is not past due more than the selected number of days.';
$lang['AdminCompanyBilling.!tooltip.show_client_tax_id'] = 'Check to show the Tax ID field in the client interface.';
$lang['AdminCompanyBilling.!tooltip.process_paid_service_changes'] = 'If checked, service changes (i.e. upgrades/downgrades) will be queued and provisioned only after they have been paid. Otherwise, they will be provisioned immediately.';

$lang['AdminCompanyBilling.!tooltip.late_fee_total_amount'] = 'If the fee type is percentage, check this to calculate the fee based on the total amount of the invoice. If this is unchecked, the fee will be calculated based on the unpaid amount only.';

$lang['AdminCompanyBilling.!tooltip.send_payment_notices'] = 'This option sets whether clients can be sent any of the available payment notices.';
$lang['AdminCompanyBilling.!tooltip.send_cancellation_notice'] = 'This option sets whether clients can be sent service cancellation notices.';
$lang['AdminCompanyBilling.!tooltip.first_notice'] = 'The number of days before or after an invoice is due to send the first late notice email.';
$lang['AdminCompanyBilling.!tooltip.second_notice'] = 'The number of days before or after an invoice is due to send the second late notice email.';
$lang['AdminCompanyBilling.!tooltip.third_notice'] = 'The number of days before or after an invoice is due to send the third late notice email.';
$lang['AdminCompanyBilling.!tooltip.notice_pending_autodebit'] = 'The number of days before an account is auto debited to send the pending auto debit notice email.';

$lang['AdminCompanyBilling.!tooltip.inv_type'] = 'The type of invoice that is created by default. Pro forma invoices change to Standard invoices after they have been paid and closed.';
$lang['AdminCompanyBilling.!tooltip.inv_format'] = 'Available tags include: {num} - the invoice number (required); {year} - the four-digit year; {month} - the two-digit month; {day} - the two-digit day of the month.';
$lang['AdminCompanyBilling.!tooltip.inv_draft_format'] = 'Available tags include: {num} - the invoice number (required); {year} - the four-digit year;  {month} - the two-digit month; {day} - the two-digit day of the month.';
$lang['AdminCompanyBilling.!tooltip.inv_proforma_format'] = 'Available tags include: {num} - the invoice number (required); {year} - the four-digit year;  {month} - the two-digit month; {day} - the two-digit day of the month.';
$lang['AdminCompanyBilling.!tooltip.inv_start'] = 'Invoice numbers will begin (and increment) from this starting value.';
$lang['AdminCompanyBilling.!tooltip.inv_proforma_start'] = 'Invoice numbers will begin (and increment) from this starting value.';
$lang['AdminCompanyBilling.!tooltip.inv_increment'] = 'Subsequent invoice numbers will increment by this value.';
$lang['AdminCompanyBilling.!tooltip.inv_pad_size'] = 'The invoice padding size sets the minimum character length of invoice numbers.';
$lang['AdminCompanyBilling.!tooltip.inv_pad_str'] = 'Invoice numbers whose character length is fewer than the invoice padding size will be padded to the left by the given character.';

$lang['AdminCompanyBilling.!tooltip.inv_cache'] = 'Saves a cached copy of each invoice on disk.';
$lang['AdminCompanyBilling.!tooltip.inv_cache_compress'] = 'Compress the cached PDF files to save space on disk. Enabling this option can decrease performance.';

$lang['AdminCompanyBilling.!tooltip.inv_logo'] = 'Upload the logo to appear on the invoice.';
$lang['AdminCompanyBilling.!tooltip.inv_background'] = 'Upload the background to appear on the invoice.';
$lang['AdminCompanyBilling.!tooltip.inv_paper_size'] = 'The invoice paper size.';
$lang['AdminCompanyBilling.!tooltip.inv_template'] = 'The invoice template to use to render the invoice.';
$lang['AdminCompanyBilling.!tooltip.inv_mimetype'] = 'The invoice file type. Invoice templates may support various file types.';

$lang['AdminCompanyBilling.!tooltip.inv_font'] = 'For additional fonts, unpack your custom TCPDF fonts to the /vendors/tecnickcom/tcpdf/fonts/ directory within your installation.';
$lang['AdminCompanyBilling.!tooltip.inv_terms'] = 'Enter the payment terms or any other information you wish to appear on the invoice.';


// Notices
$lang['AdminCompanyBilling.!notice.group_settings'] = 'NOTE: These settings only apply to Client Groups that inherit their settings from the Company.';


// Invoices and Charge settings
$lang['AdminCompanyBilling.invoices.page_title'] = 'Settings > Company > Billing/Payment > Invoice and Charge Options';
$lang['AdminCompanyBilling.invoices.boxtitle_invoices'] = 'Invoice and Charge Options';

$lang['AdminCompanyBilling.invoices.field.inv_days_before_renewal'] = 'Invoice Days Before Renewal';
$lang['AdminCompanyBilling.invoices.field.quotation_valid_days'] = 'Quote Expiration Days';
$lang['AdminCompanyBilling.invoices.field.quotation_dead_days'] = 'Quote Dead Days After Expiration';
$lang['AdminCompanyBilling.invoices.field.quotation_deposit_percentage'] = 'Quote Default Deposit Percentage (%)';
$lang['AdminCompanyBilling.invoices.field.autodebit_days_before_due'] = 'Auto Debit Days Before Due Date';
$lang['AdminCompanyBilling.invoices.field.suspend_services_days_after_due'] = 'Suspend Services Days After Due';
$lang['AdminCompanyBilling.invoices.field.autodebit_attempts'] = 'Auto Debit Attempts';
$lang['AdminCompanyBilling.invoices.field.service_renewal_attempts'] = 'Service Renewal Attempts';
$lang['AdminCompanyBilling.invoices.field.cancel_service_changes_days'] = 'Cancel Service Changes Days After Due';
$lang['AdminCompanyBilling.invoices.field.apply_inv_late_fees'] = 'Apply Late Fee to Open Invoices After Due';
$lang['AdminCompanyBilling.invoices.field.autodebit'] = 'Enable Auto Debit';
$lang['AdminCompanyBilling.invoices.field.client_set_invoice'] = 'Allow Client to Set Invoice Method';
$lang['AdminCompanyBilling.invoices.field.inv_suspended_services'] = 'Invoice Suspended Services';
$lang['AdminCompanyBilling.invoices.field.inv_group_services'] = 'Invoice Services Together';
$lang['AdminCompanyBilling.invoices.field.inv_append_descriptions'] = 'Include Package Descriptions on Invoices';
$lang['AdminCompanyBilling.invoices.field.inv_lines_verbose_option_dates'] = 'Display the Service Date Range for Configurable Options';
$lang['AdminCompanyBilling.invoices.field.clients_cancel_services'] = 'Allow Clients to Cancel Services';
$lang['AdminCompanyBilling.invoices.field.clients_renew_services'] = 'Allow Clients to Manually Renew Services';
$lang['AdminCompanyBilling.invoices.field.synchronize_addons'] = 'Allow Addon Services to be Synchronized with their Parent Services';
$lang['AdminCompanyBilling.invoices.field.client_create_addons'] = 'Allow Clients to Create Addons for Existing Services';
$lang['AdminCompanyBilling.invoices.field.client_change_service_term'] = 'Allow Clients to Change Service Terms';
$lang['AdminCompanyBilling.invoices.field.client_change_service_package'] = 'Allow Clients to Change Service Package';
$lang['AdminCompanyBilling.invoices.field.client_prorate_credits'] = 'Allow Prorated Credits to be Issued for Service Downgrades';
$lang['AdminCompanyBilling.invoices.field.auto_apply_credits'] = 'Automatically Apply Loose Credits';
$lang['AdminCompanyBilling.invoices.field.auto_paid_pending_services'] = 'Automatically Provision Paid Pending Services';
$lang['AdminCompanyBilling.invoices.field.void_invoice_canceled_service'] = 'Void Open Invoices on Service Cancellation';
$lang['AdminCompanyBilling.invoices.field.void_inv_canceled_service_days'] = 'Void Open Invoices Days After Due';
$lang['AdminCompanyBilling.invoices.field.show_client_tax_id'] = 'Show the Tax ID Field in the Client Interface';
$lang['AdminCompanyBilling.invoices.field.process_paid_service_changes'] = 'Queue Service Changes Until Paid';
$lang['AdminCompanyBilling.invoices.field.invoicessubmit'] = 'Update Settings';

$lang['AdminCompanyBilling.invoices.text_any'] = '- Any -';
$lang['AdminCompanyBilling.invoices.text_never'] = 'Never';
$lang['AdminCompanyBilling.invoices.text_sameday'] = 'Same Day';
$lang['AdminCompanyBilling.invoices.text_day'] = '%1$s Day'; // %1$s is the number 1
$lang['AdminCompanyBilling.invoices.text_days'] = '%1$s Days'; // %1$s is a number of days that is not 1


// Notices
$lang['AdminCompanyBilling.notices.page_title'] = 'Settings > Company > Billing/Payment > Notices';
$lang['AdminCompanyBilling.notices.boxtitle_notices'] = 'Notices';

$lang['AdminCompanyBilling.notices.text_notices'] = 'Payment notices can be used as late notices, or payment reminders.';
$lang['AdminCompanyBilling.notices.text_before'] = 'Before';
$lang['AdminCompanyBilling.notices.text_after'] = 'After';
$lang['AdminCompanyBilling.notices.text_inv_duedate'] = 'Invoice Due Date';
$lang['AdminCompanyBilling.notices.text_day'] = '%1$s Day'; // %1$s is the number 1
$lang['AdminCompanyBilling.notices.text_days'] = '%1$s Days'; // %1$s is a number of days that is not 1
$lang['AdminCompanyBilling.notices.text_duedate'] = 'Due Date';
$lang['AdminCompanyBilling.notices.text_disabled'] = 'Disabled';
$lang['AdminCompanyBilling.notices.text_edit_template'] = 'Edit Email Template';

$lang['AdminCompanyBilling.notices.field.send_cancellation_notice'] = 'Allow Service Cancellation Notices to be Sent';
$lang['AdminCompanyBilling.notices.field.send_payment_notices'] = 'Allow Payment Notices to be Sent';
$lang['AdminCompanyBilling.notices.field.first_notice'] = 'First Notice';
$lang['AdminCompanyBilling.notices.field.second_notice'] = 'Second Notice';
$lang['AdminCompanyBilling.notices.field.third_notice'] = 'Third Notice';
$lang['AdminCompanyBilling.notices.field.notice_pending_autodebit'] = 'Auto-Debit Pending Notice';
$lang['AdminCompanyBilling.notices.field.noticessubmit'] = 'Update Settings';


// Coupons
$lang['AdminCompanyBilling.coupons.page_title'] = 'Settings > Company > Billing/Payment > Coupons';
$lang['AdminCompanyBilling.coupons.no_results'] = 'There are no coupons.';

$lang['AdminCompanyBilling.coupons.categorylink_addcoupon'] = 'Add Coupon';

$lang['AdminCompanyBilling.coupons.boxtitle_coupons'] = 'Coupons';

$lang['AdminCompanyBilling.coupons.text_code'] = 'Code';
$lang['AdminCompanyBilling.coupons.text_discount'] = 'Discount';
$lang['AdminCompanyBilling.coupons.text_used'] = 'Used';
$lang['AdminCompanyBilling.coupons.text_max'] = 'Max';
$lang['AdminCompanyBilling.coupons.text_start_date'] = 'Start Date';
$lang['AdminCompanyBilling.coupons.text_end_date'] = 'End Date';
$lang['AdminCompanyBilling.coupons.text_options'] = 'Options';
$lang['AdminCompanyBilling.coupons.text_currency'] = 'Currency';

$lang['AdminCompanyBilling.coupons.text_multiple'] = 'Multiple';

$lang['AdminCompanyBilling.coupons.option_edit'] = 'Edit';
$lang['AdminCompanyBilling.coupons.option_delete'] = 'Delete';

$lang['AdminCompanyBilling.coupons.confirm_delete'] = 'Are you sure you want to delete this coupon?';


// Add coupon
$lang['AdminCompanyBilling.addcoupon.page_title'] = 'Settings > Company > Billing/Payment > New Coupon';
$lang['AdminCompanyBilling.addcoupon.boxtitle_new'] = 'New Coupon';
$lang['AdminCompanyBilling.addcoupon.heading_basic'] = 'Basic';

$lang['AdminCompanyBilling.addcoupon.field_status'] = 'Enabled';
$lang['AdminCompanyBilling.addcoupon.field_recurring_no'] = 'Apply when service is added only';
$lang['AdminCompanyBilling.addcoupon.field_recurring_yes'] = 'Apply when service is added or renews';
$lang['AdminCompanyBilling.addcoupon.field_apply_package_options'] = 'Apply to Configurable Options';
$lang['AdminCompanyBilling.addcoupon.field_internal_use_only'] = 'Internal Use Only';
$lang['AdminCompanyBilling.addcoupon.field_code'] = 'Coupon Code';

$lang['AdminCompanyBilling.addcoupon.text_generate_code'] = 'Generate code';

$lang['AdminCompanyBilling.addcoupon.heading_limitations'] = 'Limitations';

$lang['AdminCompanyBilling.addcoupon.field_start_date'] = 'Start Date';
$lang['AdminCompanyBilling.addcoupon.field_end_date'] = 'End Date';
$lang['AdminCompanyBilling.addcoupon.field_max_qty'] = 'Quantity';
$lang['AdminCompanyBilling.addcoupon.field_limit_recurring_no'] = 'Limitations do not apply to renewing services';
$lang['AdminCompanyBilling.addcoupon.field_limit_recurring_yes'] = 'Limitations do apply to renewing services';

$lang['AdminCompanyBilling.addcoupon.heading_discount'] = 'Discount Options';

$lang['AdminCompanyBilling.addcoupon.categorylink_addcurrency'] = 'Add Additional Currency';

$lang['AdminCompanyBilling.addcoupon.text_currency'] = 'Currency';
$lang['AdminCompanyBilling.addcoupon.text_type'] = 'Type';
$lang['AdminCompanyBilling.addcoupon.text_value'] = 'Value';

$lang['AdminCompanyBilling.addcoupon.heading_terms'] = 'Term Limitations';
$lang['AdminCompanyBilling.addcoupon.description_terms'] = 'This coupon can only be applied to services that are ordered for one of the selected terms/periods. If none are enabled, then the term for the service will be ignored when evaluating coupon limitations.';

$lang['AdminCompanyBilling.addcoupon.text_enabled'] = 'Enabled';
$lang['AdminCompanyBilling.addcoupon.text_period'] = 'Period';
$lang['AdminCompanyBilling.addcoupon.text_terms'] = 'Terms';
$lang['AdminCompanyBilling.addcoupon.not_applicable'] = 'N/A';

$lang['AdminCompanyBilling.addcoupon.option_remove'] = 'Remove';

$lang['AdminCompanyBilling.addcoupon.heading_packages'] = 'Packages';

$lang['AdminCompanyBilling.addcoupon.field_package_group_id'] = 'Package Group Filter';
$lang['AdminCompanyBilling.addcoupon.field_couponsubmit'] = 'Create Coupon';

$lang['AdminCompanyBilling.addcoupon.text_all'] = 'All';
$lang['AdminCompanyBilling.addcoupon.text_assigned_packages'] = 'Assigned Packages';
$lang['AdminCompanyBilling.addcoupon.text_available_packages'] = 'Available Packages';


// Edit coupon
$lang['AdminCompanyBilling.editcoupon.page_title'] = 'Settings > Company > Billing/Payment > Edit Coupon';
$lang['AdminCompanyBilling.editcoupon.boxtitle_edit'] = 'Edit Coupon';

$lang['AdminCompanyBilling.editcoupon.heading_basic'] = 'Basic';

$lang['AdminCompanyBilling.editcoupon.field_recurring_no'] = 'Apply when service is added only';
$lang['AdminCompanyBilling.editcoupon.field_recurring_yes'] = 'Apply when service is added or renews';
$lang['AdminCompanyBilling.editcoupon.field_apply_package_options'] = 'Apply to Configurable Options';
$lang['AdminCompanyBilling.editcoupon.field_internal_use_only'] = 'Internal Use Only';
$lang['AdminCompanyBilling.editcoupon.field_code'] = 'Coupon Code';

$lang['AdminCompanyBilling.editcoupon.text_generate_code'] = 'Generate code';

$lang['AdminCompanyBilling.editcoupon.heading_limitations'] = 'Limitations';

$lang['AdminCompanyBilling.editcoupon.field_start_date'] = 'Start Date';
$lang['AdminCompanyBilling.editcoupon.field_end_date'] = 'End Date';
$lang['AdminCompanyBilling.editcoupon.field_max_qty'] = 'Quantity';
$lang['AdminCompanyBilling.editcoupon.field_limit_recurring_no'] = 'Limitations do not apply to renewing services';
$lang['AdminCompanyBilling.editcoupon.field_limit_recurring_yes'] = 'Limitations do apply to renewing services';

$lang['AdminCompanyBilling.editcoupon.heading_discount'] = 'Discount Options';

$lang['AdminCompanyBilling.editcoupon.categorylink_addcurrency'] = 'Add Additional Currency';

$lang['AdminCompanyBilling.editcoupon.text_currency'] = 'Currency';
$lang['AdminCompanyBilling.editcoupon.text_type'] = 'Type';
$lang['AdminCompanyBilling.editcoupon.text_value'] = 'Value';

$lang['AdminCompanyBilling.editcoupon.heading_terms'] = 'Terms';
$lang['AdminCompanyBilling.editcoupon.description_terms'] = 'This coupon can only be applied to services that are ordered for one of the selected terms/periods. If none are enabled, then the term for the service will be ignored when evaluating coupon limitations.';

$lang['AdminCompanyBilling.editcoupon.text_enabled'] = 'Enabled';
$lang['AdminCompanyBilling.editcoupon.text_period'] = 'Period';
$lang['AdminCompanyBilling.editcoupon.text_terms'] = 'Terms';
$lang['AdminCompanyBilling.editcoupon.not_applicable'] = 'N/A';

$lang['AdminCompanyBilling.editcoupon.option_remove'] = 'Remove';

$lang['AdminCompanyBilling.editcoupon.heading_packages'] = 'Packages';

$lang['AdminCompanyBilling.editcoupon.field_package_group_id'] = 'Package Group Filter';
$lang['AdminCompanyBilling.editcoupon.field_couponsubmit'] = 'Edit Coupon';

$lang['AdminCompanyBilling.editcoupon.text_all'] = 'All';
$lang['AdminCompanyBilling.editcoupon.text_assigned_packages'] = 'Assigned Packages';
$lang['AdminCompanyBilling.editcoupon.text_available_packages'] = 'Available Packages';
$lang['AdminCompanyBilling.editcoupon.text_used_qty'] = '(used %1$s)'; // %1$s is the number of used coupons


// Invoice Customization
$lang['AdminCompanyBilling.customization.page_title'] = 'Settings > Company > Billing/Payment > Invoice Customization';
$lang['AdminCompanyBilling.customization.boxtitle_customization'] = 'Invoice Customization';
$lang['AdminCompanyBilling.customization.heading_general'] = 'Basic Options';
$lang['AdminCompanyBilling.customization.heading_quotations'] = 'Quotation Options';
$lang['AdminCompanyBilling.customization.heading_cache'] = 'Cache';
$lang['AdminCompanyBilling.customization.heading_lookandfeel'] = 'Look and Feel';

$lang['AdminCompanyBilling.customization.field.inv_format'] = 'Invoice Format';
$lang['AdminCompanyBilling.customization.field.inv_draft_format'] = 'Invoice Draft Format';
$lang['AdminCompanyBilling.customization.field.inv_proforma_format'] = 'Pro Forma Invoice Format';
$lang['AdminCompanyBilling.customization.field.inv_start'] = 'Invoice Start Value';
$lang['AdminCompanyBilling.customization.field.inv_proforma_start'] = 'Pro Forma Invoice Start Value';
$lang['AdminCompanyBilling.customization.field.inv_increment'] = 'Invoice Increment Value';
$lang['AdminCompanyBilling.customization.field.inv_pad_size'] = 'Invoice Padding Size';
$lang['AdminCompanyBilling.customization.field.inv_pad_str'] = 'Invoice Padding Character';
$lang['AdminCompanyBilling.customization.field.inv_type'] = 'Invoice Type';

$lang['AdminCompanyBilling.customization.field.quotation_format'] = 'Quotation Format';
$lang['AdminCompanyBilling.customization.field.quotation_start'] = 'Quotation Start Value';
$lang['AdminCompanyBilling.customization.field.quotation_increment'] = 'Quotation Increment Value';

$lang['AdminCompanyBilling.customization.field.inv_cache'] = 'Invoice Cache Method';
$lang['AdminCompanyBilling.customization.field.inv_cache_compress'] = 'Compress PDF Invoices';

$lang['AdminCompanyBilling.customization.field.inv_logo'] = 'Logo';
$lang['AdminCompanyBilling.customization.field.inv_background'] = 'Background';
$lang['AdminCompanyBilling.customization.field.inv_terms'] = 'Terms';
$lang['AdminCompanyBilling.customization.field.inv_paper_size'] = 'Paper Size';
$lang['AdminCompanyBilling.customization.field.inv_template'] = 'Invoice Template';
$lang['AdminCompanyBilling.customization.field.inv_display'] = 'Display on Invoice';
$lang['AdminCompanyBilling.customization.field.inv_display_logo'] = 'Logo';
$lang['AdminCompanyBilling.customization.field.inv_display_company'] = 'Company Name/Address';
$lang['AdminCompanyBilling.customization.field.inv_display_paid_watermark'] = 'PAID Watermark';
$lang['AdminCompanyBilling.customization.field.inv_display_payments'] = 'Payments/Credits';
$lang['AdminCompanyBilling.customization.field.inv_display_due_date_draft'] = 'Date Due - Drafts';
$lang['AdminCompanyBilling.customization.field.inv_display_due_date_proforma'] = 'Date Due - Pro Forma';
$lang['AdminCompanyBilling.customization.field.inv_display_due_date_inv'] = 'Date Due - Standard';
$lang['AdminCompanyBilling.customization.field.inv_mimetype'] = 'Invoice File Type';
$lang['AdminCompanyBilling.customization.field.inv_font'] = 'Font Family';
$lang['AdminCompanyBilling.customization.remove'] = 'Remove';

$lang['AdminCompanyBilling.customization.field.customizationsubmit'] = 'Update Settings';


// Accepted Payment Types
$lang['AdminCompanyBilling.acceptedtypes.page_title'] = 'Settings > Company > Billing/Payment > Accepted Payment Types';
$lang['AdminCompanyBilling.acceptedtypes.boxtitle_types'] = 'Accepted Payment Types';

$lang['AdminCompanyBilling.acceptedtypes.text_description'] = 'Only the payment types selected are available for processing through gateways, or may be added as payment accounts, even if an active gateway supports the type. Unchecking a type that is already accepted will cause payments of that type to not be processed.';
$lang['AdminCompanyBilling.acceptedtypes.field_cc'] = 'Credit Card';
$lang['AdminCompanyBilling.acceptedtypes.field_ach'] = 'Automated Clearing House';
$lang['AdminCompanyBilling.acceptedtypes.client_settings'] = 'Remove Client Overrides';
$lang['AdminCompanyBilling.acceptedtypes.field_update_clients'] = 'Remove accepted payment types currently set on clients';
$lang['AdminCompanyBilling.acceptedtypes.field_typessubmit'] = 'Update Settings';
$lang['AdminCompanyBilling.acceptedtypes.tooltip_update_clients'] = 'Checking this box will remove any accepted payment type overrides set on the client if they are disabled on the company';


// Invoice Delivery Methods
$lang['AdminCompanyBilling.deliverymethods.page_title'] = 'Settings > Company > Billing/Payment > Invoice Delivery';
$lang['AdminCompanyBilling.deliverymethods.boxtitle_deliverymethods'] = 'Invoice Delivery';
$lang['AdminCompanyBilling.deliverymethods.heading_basic'] = 'Basic Options';
$lang['AdminCompanyBilling.deliverymethods.heading_interfax'] = 'InterFax';
$lang['AdminCompanyBilling.deliverymethods.interfax_desc'] = 'InterFax allows you to fax invoices over the internet. <a href="http://www.interfax.net/" target="_blank">Sign up</a> for an InterFax account and start faxing invoices today.';
$lang['AdminCompanyBilling.deliverymethods.heading_postalmethods'] = 'PostalMethods';
$lang['AdminCompanyBilling.deliverymethods.postalmethods_desc'] = 'PostalMethods prints, stuffs, and mails invoices to your customers for you. <a href="https://cp.postalmethods.com/public/agentredirect.aspx?agentid=5bcfe2fb-b897-4a26-8c91-4089e92e2a7d" target="_blank">Sign up</a> for a PostalMethods account and start mailing invoices today.';
$lang['AdminCompanyBilling.deliverymethods.field_delivery_methods'] = 'Invoice Delivery Methods';
$lang['AdminCompanyBilling.deliverymethods.field_interfax_username'] = 'Username';
$lang['AdminCompanyBilling.deliverymethods.field_interfax_password'] = 'Password';
$lang['AdminCompanyBilling.deliverymethods.field_postalmethods_apikey'] = 'API Secret Key';
$lang['AdminCompanyBilling.deliverymethods.field_postalmethods_doublesided'] = 'Double-sided Printing';
$lang['AdminCompanyBilling.deliverymethods.field_postalmethods_colored'] = 'Color Printing';
$lang['AdminCompanyBilling.deliverymethods.field_submit'] = 'Update Settings';

$lang['AdminCompanyBilling.deliverymethods.note_replyenvelope'] = 'Postal Methods will send a reply envelope with each mailing.';
$lang['AdminCompanyBilling.deliverymethods.note_apikey'] = 'Be sure to use Test Environment secret key provided by PostalMethods when testing.';
$lang['AdminCompanyBilling.deliverymethods.note_doublesided'] = 'If this option is checked, invoices sent to PostalMethods will be use printing on the front and back of the page.';
$lang['AdminCompanyBilling.deliverymethods.note_colored'] = 'Note that if this option is checked, all invoices sent to PostalMethods will be in color instead of black-and-white.';

// Late Fees
$lang['AdminCompanyBilling.latefees.boxtitle_latefees'] = 'Late Fees';
$lang['AdminCompanyBilling.latefees.heading_basic'] = 'Basic Options';
$lang['AdminCompanyBilling.latefees.text_enabled'] = 'Enabled';
$lang['AdminCompanyBilling.latefees.text_currency'] = 'Currency';
$lang['AdminCompanyBilling.latefees.text_fee_type'] = 'Fee Type';
$lang['AdminCompanyBilling.latefees.text_amount'] = 'Amount';
$lang['AdminCompanyBilling.latefees.text_minimum'] = 'Minimum';
$lang['AdminCompanyBilling.latefees.field.late_fee_total_amount'] = 'Apply to total invoice amount';
$lang['AdminCompanyBilling.latefees.field.percent'] = 'Percent';
$lang['AdminCompanyBilling.latefees.field.fixed'] = 'Fixed';
$lang['AdminCompanyBilling.latefees.field_submit'] = 'Update Settings';
