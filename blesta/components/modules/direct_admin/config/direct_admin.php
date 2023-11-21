<?php
Configure::set('DirectAdmin.password_requirements', [
    ["A-Z"],
    ["a-z"],
    ["0-9"],
    ["!", "\"", "#", "$", "%", "&", "'", "(", ")", "*", "+", ",", "-", ".", "/", ":", ";", "<", "=", ">", "?", "@", "[", "]", "^", "_", "`", "{", "|", "}"]
]);
Configure::set('DirectAdmin.password_length', 12);
Configure::set('DirectAdmin.email_templates', [
    'en_us' => [
        'lang' => 'en_us',
        'text' => 'Your DirectAdmin account is now active, details below:

Domain: {service.direct_admin_domain | safe}
Username: {service.direct_admin_username | safe}
Password: {service.direct_admin_password | safe}

To log into DirectAdmin please visit https://{module.host_name | safe}:2222
Please update your name servers as soon as possible to the following:

{% for name_server in module.name_servers %}
Name server: {name_server}{% endfor %}

Thank you for your business!',
        'html' => '<p>Your DirectAdmin account is now active, details below:</p>
<p>Domain: {service.direct_admin_domain | safe}<br />Username: {service.direct_admin_username | safe}<br />Password: {service.direct_admin_password | safe}</p>
<p>To log into DirectAdmin please visit https://{module.host_name | safe}:2222<br />Please update your name servers as soon as possible to the following:</p>
<p>{% for name_server in module.name_servers %}<br />Name server: {name_server}{% endfor %}</p>
<p>Thank you for your business!</p>'
    ]
]);
