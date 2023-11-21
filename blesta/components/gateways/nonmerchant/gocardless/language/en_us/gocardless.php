<?php

$lang['Gocardless.name'] = 'GoCardless';
$lang['Gocardless.description'] = 'Collect recurring payments from more than 30 countries, and have them paid out into your existing bank account';
$lang['Gocardless.access_token'] = 'Access Token';
$lang['Gocardless.webhook_secret'] = 'Webhook Secret';
$lang['Gocardless.pay_type'] = 'Payment Options';
$lang['Gocardless.pay_type_onetime'] = 'One time payments only';
$lang['Gocardless.pay_type_subscribe'] = 'Subscription payments only';
$lang['Gocardless.pay_type_both'] = 'One time and subscription payments when possible';
$lang['Gocardless.dev_mode'] = 'Developer Mode';
$lang['Gocardless.dev_mode_note'] = 'Enabling this option will post transactions to the GoCardless Sandbox environment. Only enable this option if you are testing with a GoCardless Sandbox account.';
$lang['Gocardless.buildprocess.submit'] = 'Pay with GoCardless';
$lang['Gocardless.buildprocess.subscription'] = 'Subscribe with GoCardless';

$lang['Gocardless.webhook'] = 'GoCardless Webhook';
$lang['Gocardless.webhook_note'] = 'Before you start using this gateway you must configure the following url as a Webhook in your GoCardless account. The Webhook Secret must be at least 40 characters long.';

// Errors
$lang['Gocardless.!error.access_token.valid'] = 'You must enter a valid Access Token.';
$lang['Gocardless.!error.webhook_secret.valid'] = 'You must enter a valid Webhook Secret at least 40 characters long.';
$lang['Gocardless.!error.dev_mode.valid'] = 'Developer mode must be set to "true" if given.';

$lang['Gocardless.!tooltip.webhook_secret'] = 'The Webhook Secret must be at least 40 characters long.';
