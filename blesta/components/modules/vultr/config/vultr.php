<?php
// Polling interval for how often to refresh page content after an action is performed on pages that support it
// Note: Set to number of milliseconds (1000 = 1 second)
Configure::set('Vultr.page_refresh_rate_fast', '5000');

// Polling interval for how often to refresh page content on pages that support it
// Note: Set to number of milliseconds (1000 = 1 second)
Configure::set('Vultr.page_refresh_rate', '10000');

// Email templates
Configure::set('Vultr.email_templates', [
    'en_us' => [
        'lang' => 'en_us',
        'text' => 'Thanks for choosing us for your VPS!

Your server {service.vultr_hostname} is now being spun up and you can manage it through our client area by clicking the "Manage" button next to the server on your Dashboard. The initial password can be found under the "Statistics" section. It may take a few minutes for the server to finish booting.',
        'html' => '<p>Thanks for choosing us for your VPS!</p>
<p>Your server {service.vultr_hostname} is now being spun up and you can manage it through our client area by clicking the "Manage" button next to the server on your Dashboard. The initial password can be found under the "Statistics" section. It may take a few minutes for the server to finish booting.</p>'
    ]
]);
