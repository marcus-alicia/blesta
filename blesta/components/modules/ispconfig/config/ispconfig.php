<?php
Configure::set('Ispconfig.email_templates', [
    'en_us' => [
        'lang' => 'en_us',
        'text' => 'Thanks for choosing us for your web hosting needs!

Domain: {service.ispconfig_domain}
Username: {service.ispconfig_username}
Password: {service.ispconfig_password}

To log into ISPConfig please visit https://{module.host_name}:8080/login/

To make your site live, be sure to update your name servers to the following:

{% for name_server in module.name_servers %}
Name Server: {name_server}{% endfor %}

Thank you for your business!',
        'html' => '<p>Thanks for choosing us for your web hosting needs!</p>
<p>Domain: {service.ispconfig_domain}<br />Username: {service.ispconfig_username}<br />Password: {service.ispconfig_password}</p>
<p>To log into ISPConfig please visit https://{module.host_name}:8080/login/</p>
<p>To make your site live, be sure to update your name servers to the following:</p>
<p>{% for name_server in module.name_servers %}<br />Name Server: {name_server}{% endfor %}</p>
<p>Thank you for your business!</p>'
    ]
]);
