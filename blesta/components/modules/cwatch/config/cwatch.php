<?php
Configure::set('Cwatch.email_templates', [
    'en_us' => [
        'lang' => 'en_us',
        'text' => 'Thank you for choosing us to protect your site!

Here are the details of your new account:
Name: {service.cwatch_firstname} {service.cwatch_lastname}
Email: {service.cwatch_email}
Country: {service.cwatch_country}

Thank you for your business!',
        'html' => '<p>Thank you for choosing us to protect your site!</p>
<p>Here are the details of your new account:<br />Name: {service.cwatch_firstname} {service.cwatch_lastname}<br />Email: {service.cwatch_email}<br />Country: {service.cwatch_country}</p>
<p>Thank you for your business!</p>'
    ]
]);
