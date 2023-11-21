<?php
Configure::set('Teamspeak.email_templates', [
    'en_us' => [
        'lang' => 'en_us',
        'text' => 'Thank you for choosing us for your TeamSpeak Server Hosting!

Here are the details for your server:

SID: {service.teamspeak_sid}
Token: {service.teamspeak_token}
Name: {service.teamspeak_name}
Port: {service.teamspeak_port}

Thank you for your business!',
        'html' => '<p>Thank you for choosing us for your TeamSpeak Server Hosting!</p>
<p>Here are the details for your server:</p>
<p>SID: {service.teamspeak_sid}<br />Token: {service.teamspeak_token}<br />Name: {service.teamspeak_name}<br />Port: {service.teamspeak_port}</p>
<p>Thank you for your business!</p>'
    ]
]);