<?php
Configure::set('Cpanel.email_templates', [
    'en_us' => [
        'lang' => 'en_us',
        'text' => 'Your cPanel account is now active, details below:

Domain: {service.cpanel_domain}
Username: {service.cpanel_username}
Password: {service.cpanel_password}

To log into cPanel please visit https://{module.host_name}:2083.
Please update your name servers as soon as possible to the following:

{% for name_server in module.name_servers %}
Name server: {name_server}{% endfor %}

Thank you for your business!',
        'html' => '<p>Your cPanel account is now active, details below:</p>
<p>Domain: {service.cpanel_domain}<br />Username: {service.cpanel_username}<br />Password: {service.cpanel_password}</p>
<p>To log into cPanel please visit https://{module.host_name}:2083.<br />Please update your name servers as soon as possible to the following:</p>
<p>{% for name_server in module.name_servers %}<br />Name server: {name_server}{% endfor %}</p>
<p>Thank you for your business!</p>'
    ]
]);
Configure::set('Cpanel.password_requirements', [
    ["A-Z"],
    ["a-z"],
    ["0-9"],
    ["!", "\"", "#", "$", "%", "&", "'", "(", ")", "*", "+", ",", "-", ".", "/", ":", ";", "<", "=", ">", "?", "@", "[", "]", "^", "_", "`", "{", "|", "}"]
]);
Configure::set('Cpanel.password_length', 12);
