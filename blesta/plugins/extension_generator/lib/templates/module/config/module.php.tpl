<?php
////// Modules may define default content for package welcome emails here.  Define both a text version and html
////// version for each language you wish to include.  For information on writing email templates see the docs
////// at https://docs.blesta.com/display/user/Customizing+Emails
////
////// Welcome Email templates{{if:module_type:registrar}}
////Configure::set('{{class_name}}.email_templates', [
////    'en_us' => [
////        'lang' => 'en_us',
////        'text' => 'Your new domain is being processed and will be registered soon!
////
////Domain: {service.domain}
////
////Thank you for your business!',
////        'html' => '<p>Your new domain is being processed and will be registered soon!</p>
////<p>Domain: {service.domain}</p>
////<p>Thank you for your business!</p>'
////    ]
////]);{{else:module_type}}
////Configure::set('{{class_name}}.email_templates', [
////    'en_us' => [
////        'lang' => 'en_us',
////        'text' => 'Thanks for choosing us for your {{name}} Server!
////
////Your server is now active and you can manage it through our client area by clicking the "Manage" button next to the server on your Dashboard.
////
////Here are more details regarding your server:
////
////Server Name: {service.server_name}
////Server Address: {service.host_name}
////
////You may also log into {{name}} to manage your server:
////
////{{name}} URL: {module.host_name}
////User: {service.username}
////Pass: {service.password}
////
////Thank you for your business!',
////        'html' => '<p>Thanks for choosing us for your {{name}} Server!</p>
////<p>Your server is now active and you can manage it through our client area by clicking the "Manage" button next to the server on your Dashboard.</p>
////<p>Here are more details regarding your server:</p>
////<p>Server Name: {service.server_name}<br />Server Address: {service.host_name}</p>
////<p>You may also log into {{name}} to manage your server:</p>
////<p>{{name}} URL: {module.host_name}<br />User: {service.username}<br />Pass: {service.password}</p>
////<p>Thank you for your business!</p>'
////    ]
////]);
////{{endif:module_type}}
{{if:module_type:registrar}}{{if:static_tlds:1}}
// All available TLDs
Configure::set('{{class_name}}.tlds', [{{array:tlds}}
    '{{tlds.tld}}',{{array:tlds}}
]);{{endif:static_tlds}}{{endif:module_type}}
