<?php
Configure::set('Gogetssl.email_templates', [
    'en_us' => [
        'lang' => 'en_us',
        'text' => 'You\'ve successfully completed the purchasing process for an SSL Certificate! 

SSL Certificate: {package.gogetssl_product}
Domain: {service.gogetssl_fqdn}
Server Type: {service.gogetssl_webserver_type}

You will receive an email with more information at {service.gogetssl_approver_email}, to continue with the application of your SSL certificate.

Thank you for your business!',
        'html' => '<p>You\'ve successfully completed the purchasing process for an SSL Certificate!</p>
<p>SSL Certificate: {package.gogetssl_product}<br />Domain: {service.gogetssl_fqdn}<br />Server Type: {service.gogetssl_webserver_type}</p>
<p>You will receive an email with more information at {service.gogetssl_approver_email}, to continue with the application of your SSL certificate.</p>
<p>Thank you for your business!</p>'
    ]
]);
