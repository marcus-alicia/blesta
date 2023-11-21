<?php
Configure::set('ThesslstoreModule.email_templates', [
    'en_us' => [
        'lang' => 'en_us',
        'text' => 'You\'ve successfully completed the purchasing process for an SSL Certificate! But wait, your SSL still requires a few more steps which can be easily done at the following URL:

http://account.yourdomain.com/client/services/manage/{service.id}/tabClientGenerateCert/

OR

If you are using AutoInstall SSL then please follow the below steps:

Now that your SSL purchase is complete, it\'s time to set up and install your new SSL certificate automatically!

To use our AutoInstall SSL technology, the fastest and easiest way to get your new SSL certificate set up, please login to your cPanel/Plesk control panel, click on the AutoInstall SSL icon. Then use the following Token for the automatic installation of Store Order ID : {service.thesslstore_order_id}.


Token : {service.thesslstore_token}

You\'ll be guided through the entire process from there, and it should only take a few minutes.
 
If you experience any problems or have any questions throughout the process, please feel free to open a support ticket, we know all the ins and outs of SSL and can quickly help you with any issues. Thank you for trusting us with your web security needs.',
        'html' => '<p>You\'ve successfully completed the purchasing process for an SSL Certificate! But wait, your SSL still requires a few more steps which can be easily done at the following URL:</p>
<p>http://account.yourdomain.com/client/services/manage/{service.id}/tabClientGenerateCert/</p>
<p>OR</p>
<p>If you are using AutoInstall SSL then please follow the below steps:</p>
<p>Now that your SSL purchase is complete, it\'s time to set up and install your new SSL certificate automatically!</p>
<p>To use our AutoInstall SSL technology, the fastest and easiest way to get your new SSL certificate set up, please login to your cPanel/Plesk control panel, click on the AutoInstall SSL icon. Then use the following Token for the automatic installation of Store Order ID : {service.thesslstore_order_id}.</p>
<p><br />Token : {service.thesslstore_token}</p>
<p>You\'ll be guided through the entire process from there, and it should only take a few minutes.<br /> <br />If you experience any problems or have any questions throughout the process, please feel free to open a support ticket, we know all the ins and outs of SSL and can quickly help you with any issues. Thank you for trusting us with your web security needs.</p>'
    ]
]);
