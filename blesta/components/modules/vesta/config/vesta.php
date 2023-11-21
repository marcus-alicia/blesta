<?php
Configure::set('Vesta.email_templates', [
    'en_us' => [
        'lang' => 'en_us',
        'text' => 'Thank you for choosing us for your VestaCP Hosting!
        
Here are the details for your server:
VestaCP URL: https://{module.host_name}:{module.port}
Domain Name: {service.domain}
User Name: {service.username}
Password: {service.password}

Thank you for your business!',
        'html' => '<p>Thank you for choosing us for your VestaCP Hosting!<br /> <br />Here are the details for your server:<br />VestaCP URL: https://{module.host_name}:{module.port}<br />Domain Name: {service.domain}<br />User Name: {service.username}<br />Password: {service.password}</p>
<p>Thank you for your business!</p>'
    ]
]);
