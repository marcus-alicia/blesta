<?php
//Errors
$lang['ThesslstoreModule.!error.api.internal'] = "Something went wrong in API Call";
$lang['ThesslstoreModule.!error.credential_already_exist'] = "Credentials already exists";
$lang['ThesslstoreModule.!error.thesslstore_reseller_name.empty'] = "Please enter Reseller Name";
$lang['ThesslstoreModule.!error.api_partner_code_live.empty'] = "Please enter LIVE API partner code";
$lang['ThesslstoreModule.!error.api_partner_code_live.valid'] = "Live API Credentials not valid!";
$lang['ThesslstoreModule.!error.api_auth_token_live.empty'] = "Please enter LIVE API Auth Token";
$lang['ThesslstoreModule.!error.api_partner_code_test.empty'] = "Please enter TEST API partner code";
$lang['ThesslstoreModule.!error.api_partner_code_test.valid'] = "TEST API Credentials not valid!";
$lang['ThesslstoreModule.!error.api_auth_token_test.empty'] = "Please enter TEST API Auth Token";
$lang['ThesslstoreModule.!error.profit_margin.empty'] = "Please Enter Your Desired Profit Margin";
$lang['ThesslstoreModule.!error.profit_margin.valid'] = "Only Numbers Allowed In  Desired Profit Margin";
$lang['ThesslstoreModule.!error.invalid_screen'] = "Invalid Action! Something went Wrong!";
$lang['ThesslstoreModule.!error.empty_package_group'] = "Opps, It appears you haven't created any package groups. You'll need to first create a group prior to importing any products. Once you've done that please return back and proceed with importation.";
$lang['ThesslstoreModule.!error.import_package_error'] = "Products already there within the selected product group.";

//Success Message
$lang['ThesslstoreModule.!success.import_package_success'] = "Products Imported Successfully";

// Cron tasks
$lang['ThesslstoreModule.getCronTasks.tss_order_sync_name'] = "SSL Order Synchronization";
$lang['ThesslstoreModule.getCronTasks.tss_order_sync_desc'] = "This cron is required to synchronize the order status and certificate expiration updates from The SSL Store.";

// Basics
$lang['ThesslstoreModule.module_row'] = "TheSSLStore Credential";
$lang['ThesslstoreModule.module_row_plural'] = "Resellers";

//Manage Module
$lang['ThesslstoreModule.add_credential_row'] = "Add Credential";
$lang['ThesslstoreModule.edit_credential_row'] = "Update Credential";
$lang['ThesslstoreModule.import_product_row'] = "Import Packages";
$lang['ThesslstoreModule.setup_price_row'] = "Setup Package Pricing";
$lang['ThesslstoreModule.replacement_order_row'] = "Symantec Replacement Orders List";
$lang['ThesslstoreModule.add_row.box_title'] = "Manage Credential";
$lang['ThesslstoreModule.add_row.basic_title'] = "Add Credential";

//Manage Credential
$lang['ThesslstoreModule.add_credential.update_success'] = "API Credential saved successfully";
$lang['ThesslstoreModule.add_row.box_title'] = "Settings";
$lang['ThesslstoreModule.reseller_price.box_title'] = "Reseller Product Pricing";
$lang['ThesslstoreModule.add_row.setup_price'] = "Update Product Pricing";
$lang['ThesslstoreModule.setup_price.view_reseller_price'] = "View Reseller Price";
$lang['ThesslstoreModule.setup_price.apply_margin'] = "Apply Profit Margin to all the Products?";
$lang['ThesslstoreModule.setup_price.margin_percentage'] = "Profit Margin(in %)";
$lang['ThesslstoreModule.setup_price.update_success'] = "Price updated successfully.";
$lang['ThesslstoreModule.add_row.manage_credential'] = "API Credential";
$lang['ThesslstoreModule.add_row.import_packages'] = "SSL Store Products Import";
$lang['ThesslstoreModule.setup_price.update_mode'] = "It seems that you have changed the \"Operation Mode\" to \"LIVE\"! If current products pricing were setup for the \"TEST\" mode then Please update it from here.";

$lang['ThesslstoreModule.row_meta.thesslstore_reseller_name'] = 'Reseller Name';
$lang['ThesslstoreModule.row_meta.api_partner_code_live'] = "Live PartnerCode";
$lang['ThesslstoreModule.row_meta.api_auth_token_live'] = "Live AuthToken";
$lang['ThesslstoreModule.row_meta.api_partner_code_test'] = "Test PartnerCode";
$lang['ThesslstoreModule.row_meta.api_auth_token_test'] = "Test AuthToken";
$lang['ThesslstoreModule.row_meta.api_mode'] = "Operation Mode";
$lang['ThesslstoreModule.row_meta.product_group'] = "Select Package Group";
$lang['ThesslstoreModule.row_meta.profit_margin'] = "Enter Desired Profit Margin";
$lang['ThesslstoreModule.row_meta.hide_changeapprover_option'] = "Hide Change Approver Email Option For Symantec DV Products?";

$lang['ThesslstoreModule.credential_row.add_btn'] = "Save";
$lang['ThesslstoreModule.import_packages_row.add_btn'] = "Import Packages";
$lang['ThesslstoreModule.change_approver_email.save_btn'] = "Save";

//Package
$lang['ThesslstoreModule.package_fields.product_code'] = "Product code";
$lang['TheSSLStore.!error.meta[thesslstore_product_code].valid'] = "Please Select Product";
$lang['TheSSLStore.!error.meta[thesslstore_vendor_name].valid'] = "Invalid Vendor Name";
$lang['TheSSLStore.!error.meta[thesslstore_is_code_signing].valid'] = "Invalid Code Signing Value";
$lang['TheSSLStore.!error.meta[thesslstore_min_san].valid'] = "Invalid Min SAN Value";
$lang['TheSSLStore.!error.meta[thesslstore_is_scan_product].valid'] = "Invalid Scan Product Value";
$lang['TheSSLStore.!error.meta[thesslstore_validation_type].valid'] = "Invalid Validation Type";

//Client Service Management - Certificate Details
$lang['ThesslstoreModule.!error.invalid_service_status'] = "Service must be active for this features";
$lang['ThesslstoreModule.success.generate_cert'] = "Certificate generation process completed successfully";
$lang['ThesslstoreModule.tab_CertDetails'] = "Certificate Details";
$lang['ThesslstoreModule.tab_client_cert_details'] = "Certificate Details";
$lang['ThesslstoreModule.tab_client_cert_details.order_status'] = "Order Status";
$lang['ThesslstoreModule.tab_client_cert_details.store_order_id'] = "Store Order ID";
$lang['ThesslstoreModule.tab_client_cert_details.token'] = "Token";
$lang['ThesslstoreModule.tab_client_cert_details.package_name'] = "Package Name";
$lang['ThesslstoreModule.tab_client_cert_details.vendor_order_id'] = "Vendor Order ID";
$lang['ThesslstoreModule.tab_client_cert_details.vendor_status'] = "Vendor Status";
$lang['ThesslstoreModule.tab_client_cert_details.ssl_start_date'] = "SSL Provisioning Date";
$lang['ThesslstoreModule.tab_client_cert_details.ssl_end_date'] = "SSL Expiry Date";
$lang['ThesslstoreModule.tab_client_cert_details.domains'] = "Domain(s)";
$lang['ThesslstoreModule.tab_client_cert_details.verification_email'] = "Verification Email";
$lang['ThesslstoreModule.tab_client_cert_details.siteseal_url'] = "Get Your Site Seal";
$lang['ThesslstoreModule.tab_client_cert_details.admin_details'] = "Admin Details";
$lang['ThesslstoreModule.tab_client_cert_details.admin_title'] = "Title";
$lang['ThesslstoreModule.tab_client_cert_details.admin_first_name'] = "First Name";
$lang['ThesslstoreModule.tab_client_cert_details.admin_last_name'] = "Last Name";
$lang['ThesslstoreModule.tab_client_cert_details.admin_email'] = "Email";
$lang['ThesslstoreModule.tab_client_cert_details.admin_phone'] = "Phone";
$lang['ThesslstoreModule.tab_client_cert_details.tech_details'] = "Technical Details";
$lang['ThesslstoreModule.tab_client_cert_details.tech_title'] = "Title";
$lang['ThesslstoreModule.tab_client_cert_details.tech_first_name'] = "First Name";
$lang['ThesslstoreModule.tab_client_cert_details.tech_last_name'] = "Last Name";
$lang['ThesslstoreModule.tab_client_cert_details.tech_email'] = "Email";
$lang['ThesslstoreModule.tab_client_cert_details.tech_phone'] = "Phone";

//Client Reissue Cert tab
$lang['ThesslstoreModule.!error.reissue_cert_invalid_certificate_status'] = "Reissue available only for Active certificate.";
$lang['ThesslstoreModule.success.reissue_cert'] = "Certificate Re-Issue process completed successfully";
$lang['ThesslstoreModule.tab_reissue_cert.heading'] = "Re-Issue Certificate";
$lang['ThesslstoreModule.tab_reissue_cert.submit'] = "Re-Issue";

//Change approver email tab field
$lang['ThesslstoreModule.tab_select_approver_email'] = "Select Approver Email";
$lang['ThesslstoreModule.tab_ChangeApproverEmail'] = "Change Approver Email";
$lang['ThesslstoreModule.!error.change_approver_email_not_available_for_order'] = "Change Approver Email feature is not available for this order.";
$lang['ThesslstoreModule.!error.change_approver_email_not_available_for_product'] = "Change Approver Email feature is not available for this certificate.";
$lang['ThesslstoreModule.!error.resend_approver_email_not_available_for_order'] = "Resend Approver Email feature is not available for this order.";
$lang['ThesslstoreModule.success.change_approver_email'] = "Approver Email changed successfully";

//Download Authfile tab field
$lang['ThesslstoreModule.tab_DownloadAuthFile'] = "Download Auth File";
$lang['ThesslstoreModule.!error.download_authfile_invalid_state'] = "Download Authfile feature is available only for Pending/Re-issue Pending orders.";
$lang['ThesslstoreModule.!error.download_authfile_not_available'] = "Download Authfile feature is not available for this order.";
//Download Certificate tab field
$lang['ThesslstoreModule.tab_DownloadCertificate'] = "Download Certificate";
$lang['ThesslstoreModule.!error.download_cert_invalid_state'] = "Download Certificate feature is available only for Active orders.";
//Management Action tab admin side
$lang['ThesslstoreModule.!error.initial_order_status'] = "This feature is not available for the initial order.";

//Client Service Management -
$lang['ThesslstoreModule.tab_GenerateCert'] = "Generate Certificate";
$lang['ThesslstoreModule.tab_generate_cert.heading_approver_email'] = "Select Approver Email";
$lang['ThesslstoreModule.tab_generate_cert.heading_step3'] = "Configuration Complete";
$lang['ThesslstoreModule.!error.generate_cert_invalid_certificate_status'] = "Generation process already completed.";
$lang['ThesslstoreModule.tab_generate_cert.heading_server'] = "Server Information";
$lang['ThesslstoreModule.service_field.thesslstore_csr'] = "Input CSR";
$lang['ThesslstoreModule.service_field.thesslstore_additional_san'] = "Additional SAN";
$lang['ThesslstoreModule.service_field.thesslstore_webserver_type'] = "Select Your Web Server";
$lang['ThesslstoreModule.service_field.thesslstore_signature_algorithm'] = "Signature Algorithm";
$lang['ThesslstoreModule.tab_generate_cert.heading_auth_method'] = "Authentication Method";
$lang['ThesslstoreModule.tab_generate_cert.heading_admin'] = "Administrative Contact Information";
$lang['ThesslstoreModule.service_field.thesslstore_admin_first_name'] = "First Name";
$lang['ThesslstoreModule.service_field.thesslstore_admin_last_name'] = "Last Name";
$lang['ThesslstoreModule.service_field.thesslstore_admin_title'] = "Title";
$lang['ThesslstoreModule.service_field.thesslstore_admin_email'] = "Email Address";
$lang['ThesslstoreModule.service_field.thesslstore_admin_phone'] = "Phone Number";
$lang['ThesslstoreModule.service_field.thesslstore_org_name'] = "Organization Name";
$lang['ThesslstoreModule.service_field.thesslstore_org_division'] = "Organization Division";
$lang['ThesslstoreModule.service_field.thesslstore_admin_address1'] = "Address 1";
$lang['ThesslstoreModule.service_field.thesslstore_admin_address2'] = "Address 2";
$lang['ThesslstoreModule.service_field.thesslstore_admin_city'] = "City";
$lang['ThesslstoreModule.service_field.thesslstore_admin_state'] = "State/Region";
$lang['ThesslstoreModule.service_field.thesslstore_admin_country'] = "Country";
$lang['ThesslstoreModule.service_field.thesslstore_admin_zip'] = "Zip Code";
$lang['ThesslstoreModule.tab_generate_cert.heading_tech'] = "Technical Contact Information";
$lang['ThesslstoreModule.service_field.thesslstore_same_as_admin'] = "Same as the Admin info above ?";
$lang['ThesslstoreModule.service_field.thesslstore_tech_first_name'] = "First Name";
$lang['ThesslstoreModule.service_field.thesslstore_tech_last_name'] = "Last Name";
$lang['ThesslstoreModule.service_field.thesslstore_tech_title'] = "Title";
$lang['ThesslstoreModule.service_field.thesslstore_tech_email'] = "Email Address";
$lang['ThesslstoreModule.service_field.thesslstore_tech_phone'] = "Phone Number";
$lang['ThesslstoreModule.please_select'] = "-- Please Select --";
$lang['ThesslstoreModule.tab_generate_cert_step1.submit'] = "Submit";

$lang['ThesslstoreModule.!error.thesslstore_csr.empty'] = "Please enter CSR";
$lang['ThesslstoreModule.!error.thesslstore_csr.valid'] = "Please enter Valid CSR";
$lang['ThesslstoreModule.!error.thesslstore_additional_san.empty'] = "Please enter minimum 1 additional SAN";
$lang['ThesslstoreModule.!error.thesslstore_webserver_type.empty'] = "Please select Web Server";
$lang['ThesslstoreModule.!error.thesslstore_auth_method.empty'] = "Please select Authentication Method";
$lang['ThesslstoreModule.!error.thesslstore_signature_algorithm.empty'] = "Please select Signature Algorithm";
$lang['ThesslstoreModule.!error.thesslstore_admin_first_name.empty'] = "Please enter Admin First Name";
$lang['ThesslstoreModule.!error.thesslstore_admin_last_name.empty'] = "Please enter Admin Last Name";
$lang['ThesslstoreModule.!error.thesslstore_admin_email.empty'] = "Please enter Admin Email";
$lang['ThesslstoreModule.!error.thesslstore_admin_phone.empty'] = "Please enter Admin Phone";
$lang['ThesslstoreModule.!error.thesslstore_org_name.empty'] = "Please enter Organization Name";
$lang['ThesslstoreModule.!error.thesslstore_org_division.empty'] = "Please enter Organization Division";
$lang['ThesslstoreModule.!error.thesslstore_admin_address1.empty'] = "Please enter Address";
$lang['ThesslstoreModule.!error.thesslstore_admin_city.empty'] = "Please enter City";
$lang['ThesslstoreModule.!error.thesslstore_admin_state.empty'] = "Please enter State/Region";
$lang['ThesslstoreModule.!error.thesslstore_admin_country.empty'] = "Please select County";
$lang['ThesslstoreModule.!error.thesslstore_admin_zip.empty'] = "Please enter ZipCode";
$lang['ThesslstoreModule.!error.thesslstore_tech_first_name.empty'] = "Please enter Technical First Name";
$lang['ThesslstoreModule.!error.thesslstore_tech_last_name.empty'] = "Please enter Technical Last Name";
$lang['ThesslstoreModule.!error.thesslstore_tech_email.empty'] = "Please enter Technical Email";
$lang['ThesslstoreModule.!error.thesslstore_tech_phone.empty'] = "Please enter Technical Phone";

$lang['ThesslstoreModule.tab_ResendApproverEmail'] = "Resend Approver Email";
$lang['ThesslstoreModule.success.resend_approver_email'] = "Approver Email sent successfully";
$lang['ThesslstoreModule.!error.resend_invalid_status'] = "Resend Approver Email is available only for pending order";

$lang['ThesslstoreModule.tab_ReissueCert'] = "Re-issue Certificate";

$lang['ThesslstoreModule.tab_AdminManagementAction'] = "Management Actions";

//Symantec Replacement Order Related
$lang['ThesslstoreModule.replacement_order.box_title'] = "Symantec Replacement Orders List";
$lang['ThesslstoreModule.replacement_order.export_csv'] = "Export to CSV";
$lang['ThesslstoreModule.row_meta.replace_date'] = "Replace By Date";
?>