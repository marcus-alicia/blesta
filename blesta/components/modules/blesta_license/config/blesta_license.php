<?php
Configure::set('BlestaLicense.email_templates', array(
    'en_us' => array(
        'lang' => 'en_us',
        'text' => 'Thanks for choosing us for your Blesta license needs.

Your {package.name} is now active and can be installed. If you have an existing installation and need to change your license key you can do so under Settings > System > General > License.
 
License Key: {service.license}
 
You can download Blesta from https://account.blesta.com/, just click on the "Downloads" section.',
        'html' => '<p>Thanks for choosing us for your Blesta license needs.</p>
<p>Your {package.name} is now active and can be installed. If you have an existing installation and need to change your license key you can do so under Settings &gt; System &gt; General &gt; License.</p>
<p>License Key: {service.license}</p>
<p>You can download Blesta from https://account.blesta.com/, just click on the "Downloads" section.</p>'
    )
));
