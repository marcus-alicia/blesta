<?php
// Polling interval for how often to refresh page content after an action is performed on pages that support it
// Note: Set to number of milliseconds (1000 = 1 second)
Configure::set('Multicraft.page_refresh_rate_fast', '5000');

// Polling interval for how often to refresh page content on pages that support it
// Note: Set to number of milliseconds (1000 = 1 second)
Configure::set('Multicraft.page_refresh_rate', '30000');

// Full date time format, used where applicable (e.g. chat logs)
Configure::set('Multicraft.date_time', 'M d Y h:i:s A');

// Email templates
Configure::set('Multicraft.email_templates', [
    'en_us' => [
        'lang' => 'en_us',
        'text' => 'Thanks for choosing us for your Minecraft Server!

Your server is now active and you can manage it through our client area by clicking the "Manage" button next to the server on your Dashboard.

Here are more details regarding your server:

Server Name: {service.multicraft_server_name}
Server Address: {service.multicraft_ip}:{service.multicraft_port}

You may also log into Multicraft to manage your server:

Multicraft URL: {module.panel_url}
User: {service.multicraft_login_username}
Pass: {service.multicraft_login_password}
 
Thank you for your business!',
        'html' => '<p>Thanks for choosing us for your Minecraft Server!</p>
<p>Your server is now active and you can manage it through our client area by clicking the "Manage" button next to the server on your Dashboard.</p>
<p>Here are more details regarding your server:</p>
<p>Server Name: {service.multicraft_server_name}<br />Server Address: {service.multicraft_ip}:{service.multicraft_port}</p>
<p>You may also log into Multicraft to manage your server:</p>
<p>Multicraft URL: {module.panel_url}<br />User: {service.multicraft_login_username}<br />Pass: {service.multicraft_login_password}</p>
<p>Thank you for your business!</p>'
    ]
]);
