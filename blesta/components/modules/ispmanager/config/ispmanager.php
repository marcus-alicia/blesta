<?php
Configure::set('Ispmanager.email_templates', [
    'en_us' => [
        'lang' => 'en_us',
        'text' => 'Your ISPmanager account is now active, details below:

Domain: {service.ispmanager_domain}
Username: {service.ispmanager_username}
Password: {service.ispmanager_password}

To log into ISPmanager please visit https://{module.host_name}:1500.
Please update your name servers as soon as possible to the following:

{% for name_server in module.name_servers %}
Name server: {name_server}{% endfor %}

Thank you for your business!',
        'html' => '<p>Your ISPmanager account is now active, details below:</p>
<p>Domain: {service.ispmanager_domain}<br />Username: {service.ispmanager_username}<br />Password: {service.ispmanager_password}</p>
<p>To log into ISPmanager please visit https://{module.host_name}:1500.<br />Please update your name servers as soon as possible to the following:</p>
<p>{% for name_server in module.name_servers %}<br />Name server: {name_server}{% endfor %}</p>
<p>Thank you for your business!</p>'
    ]
]);
