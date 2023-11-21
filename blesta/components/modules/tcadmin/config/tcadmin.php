<?php
Configure::set('Tcadmin.email_templates', [
    'en_us' => [
        'lang' => 'en_us',
        'text' => 'Thank you for choosing us for your Game Server Hosting!

Here are the details for your server:

TCAdmin URL: https://{module.host_name}:{module.port}
Hostname: {service.hostname}
User Name: {service.user_name}
Password: {service.user_password}
RCON: {service.rcon_password}
Private Password: {service.private_password}',
        'html' => '<p>Thank you for choosing us for your Game Server Hosting!</p>
<p>Here are the details for your server:</p>
<p>
TCAdmin URL: https://{module.host_name}:{module.port}<br />
Hostname: {service.hostname}<br />
User Name: {service.user_name}<br />
Password: {service.user_password}<br />
RCON: {service.rcon_password}<br />
Private Password: {service.private_password}
</p>'
    ]
]);
