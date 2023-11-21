<?php
// Sets whether or not to generate passwords with special characters (non-alpha-numeric)
// since a bug exists in SolusVM <= v1.14 that may cause the server's IP address and user password to not be updated
Configure::set('Solusvm.password.allow_special_characters', false);

// Polling interval for how often to refresh page content after an action is performed on pages that support it
// Note: Set to number of milliseconds (1000 = 1 second)
Configure::set('Solusvm.page_refresh_rate_fast', '5000');

// Polling interval for how often to refresh page content on pages that support it
// Note: Set to number of milliseconds (1000 = 1 second)
Configure::set('Solusvm.page_refresh_rate', '30000');

// Email templates
Configure::set('Solusvm.email_templates', [
    'en_us' => [
        'lang' => 'en_us',
        'text' => 'Thank you for ordering {service.solusvm_plan}, details below:

Hostname: {service.solusvm_hostname}

SolusVM Panel: https://{module.host}:{module.port}
Username: {service.solusvm_username}
Password: {service.solusvm_password}
IP Address: {service.solusvm_main_ip_address}

Thank you for your business!',
        'html' => '<p>Thank you for ordering {service.solusvm_plan}, details below:</p>
<p>Hostname: {service.solusvm_hostname}</p>
<p>SolusVM Panel: https://{module.host}:{module.port}<br />Username: {service.solusvm_username}<br />Password: {service.solusvm_password}<br />IP Address: {service.solusvm_main_ip_address}</p>
<p>Thank you for your business!</p>'
    ]
]);
