<?php
Configure::set('Vpsdotnet.email_templates', [
    'en_us' => [
        'lang' => 'en_us',
        'text' => 'Thank you for ordering your VPS, details below:

Hostname: {service.vpsdotnet_hostname}
IP Address: {service.vpsdotnet_primary_ip_address}
Username: root
Password: {service.vpsdotnet_password}

Thank you for your business!',
        'html' => '<p>Thank you for ordering your VPS, details below:</p>
<p>Hostname: {service.vpsdotnet_hostname}<br />IP Address: {service.vpsdotnet_primary_ip_address}<br />Username: root<br />Password: {service.vpsdotnet_password}</p>
<p>Thank you for your business!</p>'
    ]
]);
