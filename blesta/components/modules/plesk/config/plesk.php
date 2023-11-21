<?php
Configure::set('Plesk.password_requirements', [
    ["A-Z"],
    ["a-z"],
    ["0-9"],
    ["!", "@", "#", "$", "%", "^", "&", "*", "?", "_", "~"]
]);
Configure::set('Plesk.password_length', 16);
Configure::set('Plesk.password_minimum_characters_per_pool', 3);
Configure::set('Plesk.email_templates', [
    'en_us' => [
        'lang' => 'en_us',
        'text' => 'Your Plesk account is now active, details below:

Domain: {service.plesk_domain}
Username: {service.plesk_username}
Password: {service.plesk_password}

To log into Plesk please visit https://{module.host_name}:8443.
Please update your name servers as soon as possible to the following:

{% for name_server in module.name_servers %}
Name server: {name_server}{% endfor %}


Thank you for your business!',
        'html' => '<p>Your Plesk account is now active, details below:</p>
<p>Domain: {service.plesk_domain}<br />Username: {service.plesk_username}<br />Password: {service.plesk_password}</p>
<p>To log into Plesk please visit https://{module.host_name}:8443.<br />Please update your name servers as soon as possible to the following:</p>
<p>{% for name_server in module.name_servers %}<br />Name server: {name_server}{% endfor %}</p>
<p><br />Thank you for your business!</p>'
    ]
]);
