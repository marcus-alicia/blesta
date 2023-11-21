<?php
Configure::set('Centovacast.email_templates', [
    'en_us' => [
        'lang' => 'en_us',
        'text' => 'Thank you for choosing us for your CentovaCast Server!

Login at: http://{module.hostname}:2199/login/index.php
Hostname: {service.centovacast_hostname}
Username: {service.centovacast_username}
Password: {service.centovacast_adminpassword}

Title: {service.centovacast_title}
Genre: {service.centovacast_genre}
Max Clients: {package.maxclients}
Max Bitrate: {package.maxbitrate}
Transfer Limit: {package.transferlimit}',
        'html' => '<p>Thank you for choosing us for your CentovaCast Server!</p>

<p>Login at: http://{module.hostname}:2199/login/index.php<br />
Hostname: {service.centovacast_hostname}<br />
Username: {service.centovacast_username}<br />
Password: {service.centovacast_adminpassword}</p>

<p>Title: {service.centovacast_title}<br />
Genre: {service.centovacast_genre}<br />
Max Clients: {package.maxclients}<br />
Max Bitrate: {package.maxbitrate}<br />
Transfer Limit: {package.transferlimit}</p>'
    ]
]);
