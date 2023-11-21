<?php
Configure::set('Cyberpanel.email_templates', [
    'en_us' => [
        'lang' => 'en_us',
        'text' => 'Your hosting account details are below!

Domain: {service.cyberpanel_domain}
Username: {service.cyberpanel_username}
Password: {service.cyberpanel_password}

To log into your control panel please visit https://{module.host_name}:8090.
Please update your name servers as soon as possible to the following:

{% for name_server in module.name_servers %}
Name server: {name_server}{% endfor %}

Thank you for your business!',
        'html' => '<p>Your hosting account details are below!</p>
<p>Domain: {service.cyberpanel_domain}<br />Username: {service.cyberpanel_username}<br />Password: {service.cyberpanel_password}</p>
<p>To log into your control panel please visit https://{module.host_name}:8090.<br />Please update your name servers as soon as possible to the following:</p>
<p>{% for name_server in module.name_servers %}<br />Name server: {name_server}{% endfor %}</p>
<p>Thank you for your business!</p>'
    ]
]);
