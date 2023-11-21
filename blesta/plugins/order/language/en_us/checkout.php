<?php
$lang['Checkout.!error.invalid_agree_tos'] = "You must acknowledge your agreement to the terms and conditions.";
$lang['Checkout.!error.no_payment_info'] = "You must select a method of payment in order to continue.";
$lang['Checkout.!error.not_client_owner'] = "You must be logged in as the client owner in order to continue.";
$lang['Checkout.!error.payment_authorize'] = "Payment could not be authorized for the selected payment account.  Please select a different account or try again.";

$lang['Checkout.!info.unverified_email'] = "A link was sent to the email address you provided. Please click the link in the email to verify your email address before proceeding to make payment.";
$lang['Checkout.!info.unverified_email_button'] = 'Resend Verification Email';
$lang['Checkout.!info.ach_verification'] = 'You need to verify this account before you can use it to make a payment. This process will redirect you away from the current page.';

$lang['Checkout.index.description_invoice'] = "Invoice #%1\$s"; // %1$s is the invoice number
$lang['Checkout.setcontactview.text_none'] = "None";

$lang['Checkout.getpaymentaccounts.paymentaccount_cc'] = "Credit Card Accounts";
$lang['Checkout.getpaymentaccounts.paymentaccount_ach'] = "ACH Accounts";
$lang['Checkout.getpaymentaccounts.account_name'] = "%1\$s %2\$s - %3\$s x%4\$s"; // %1$s is the account first name, %2$s is the account last name, %3$s is the account type (card type or bank account type), %4$s is the last 4 of the account


$lang['Checkout.index.field_agree_tos'] = "I have read and agree to the <a href=\"%1\$s\" target=\"_blank\">Terms and Conditions</a>"; // %1$s is the URI to the terms and conditions
$lang['Checkout.index.description_invoice'] = "Invoice #%1\$s"; // %1$s is the invoice number
$lang['Checkout.index.totals.setup_fee'] = "Setup Fee:";
$lang['Checkout.index.totals.discount'] = "Discount:";
$lang['Checkout.index.totals.subtotal'] = "Subtotal:";
$lang['Checkout.index.totals.tax'] = "%1\$s (%2\$s%%):"; // %1$s is the tax name, %2$s is the tax percentage
$lang['Checkout.index.totals.credit'] = "Credit:";
$lang['Checkout.index.totals.paid'] = "Paid:";
$lang['Checkout.index.totals.total'] = "Total Due:";

// Contact Info
$lang['Checkout.contact_info.field_first_name'] = "First Name";
$lang['Checkout.contact_info.field_last_name'] = "Last Name";
$lang['Checkout.contact_info.field_address1'] = "Address 1";
$lang['Checkout.contact_info.field_address2'] = "Address 2";
$lang['Checkout.contact_info.field_city'] = "City";
$lang['Checkout.contact_info.field_country'] = "Country";
$lang['Checkout.contact_info.field_state'] = "State";
$lang['Checkout.contact_info.field_zip'] = "Zip/Postal Code";
$lang['Checkout.contact_info.field_email'] = "Email";

// Account CC info
$lang['Checkout.cc_info.field_number'] = "Number";
$lang['Checkout.cc_info.field_security'] = "Security Code";
$lang['Checkout.cc_info.field_expiration'] = "Expiration Date";
$lang['Checkout.cc_info.field_savedetails'] = "Save Payment Details";
$lang['Checkout.cc_info.tooltip_savedetails'] = "Saved payment details can be used to make payment in the future without having to enter the details each time.";
$lang['Checkout.cc_info.tooltip_code'] = "The 3 or 4 digit security code, usually found on the back of the card.";

// Account ACH info
$lang['Checkout.ach_info.field_type'] = "Type";
$lang['Checkout.ach_info.field_accountnum'] = "Account Number";
$lang['Checkout.ach_info.field_routingnum'] = "Routing Number";
$lang['Checkout.ach_info.field_savedetails'] = "Save Payment Details";
$lang['Checkout.ach_info.tooltip_savedetails'] = "Saved payment details can be used to make payment in the future without having to enter the details each time.";

// Order complete
$lang['Checkout.complete.order_number'] = "Order #%1\$s"; // %1$s is the order number
$lang['Checkout.complete.complete_description'] = "Thank you for your order! Your order is now complete.";
$lang['Checkout.complete.almost_complete_description'] = "Your order has been received, pending your payment below.";
$lang['Checkout.complete.table_description'] = "Description";
$lang['Checkout.complete.table_qty'] = "Quantity";
$lang['Checkout.complete.table_price'] = "Unit Price";
$lang['Checkout.complete.quantity'] = "%1\$s @"; // %1\$s is the quantity value being ordered
$lang['Checkout.complete.totals.subtotal'] = "Subtotal:";
$lang['Checkout.complete.totals.tax'] = "%1\$s (%2\$s%%):"; // %1$s is the tax name, %2$s is the tax percentage
$lang['Checkout.complete.totals.total'] = "Total Due:";
$lang['Checkout.complete.totals.total_paid'] = "Total Paid:";
