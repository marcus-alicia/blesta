<?php
Configure::set('Interworx.email_templates', [
    'en_us' => [
        'lang' => 'en_us',
        'text' => 'Your Interworx account is now active, details below:

Domain: {service.interworx_domain}
Username: {service.interworx_username}
Password: {service.interworx_password}

To log into Interworx, please visit https://{module.host_name}:2443/siteworx/.

Please update your name servers as soon as possible to the following:

{% for name_server in module.name_servers %}
Name server: {name_server} {% endfor %}

Thank you for your business!',
        'html' => '<p>Your Interworx account is now active, details below:</p>
<p>Domain: {service.interworx_domain}<br />Username: {service.interworx_username}<br />Password: {service.interworx_password}</p>
<p>To log into Interworx, please visit https://{module.host_name}:2443/siteworx/.</p>
<p>Please update your name servers as soon as possible to the following:</p>
<p>{% for name_server in module.name_servers %}<br />Name server: {name_server} {% endfor %}</p>
<p>Thank you for your business!</p>'
    ]
]);
