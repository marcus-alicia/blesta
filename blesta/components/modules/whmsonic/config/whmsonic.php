<?php
Configure::set('Whmsonic.email_templates', [
    'en_us' => [
        'lang' => 'en_us',
        'text' => 'Thank you for your order from us! Your Shoutcast account has been setup and this email contains all of the information you will need in order to begin
using your shoutcast account.

WHMSonic Panel & FTP Account:
Panel URL: http://{service.whmsonic_ip_address}:2087/
FTP Host/IP: {service.whmsonic_ip_address}
Username: {service.whmsonic_username} 
Password: {service.whmsonic_password}

Info: You can upload your MP3 files directly into the FTP account for AutoDJ. After MP3 upload, open your WHMSonic panel and reload the playlist or create a new AutoDJ. You can manage your playlist on WHMSonic panel. If you receive an error such as upload failed, that means your hosting quota limit is exceeded, you may remove some mp3 files or request more space. Your upload limits are provided under the account limits of this email.

Shoutcast Information:
Radio IP: {whmsonic_radio_ip}
Radio Password: {whmsonic_radio_password}

Account Limits:
Hosting/MP3 Quota-Upload Limit: {package.hspace} MB
FTP Account Permissions: {service.whmsonic_ftp}
Listener Limit: {package.listeners}
Bitrate Limit: {package.bitrate} Kbps
AutoDJ Permissions: {package.autodj}
Bandwidth Limit: {package.bandwidth} MB',
        'html' => '<p>Thank you for your order from us! Your Shoutcast account has been setup and this email contains all of the information you will need in order to begin<br />using your shoutcast account.</p>
<p>WHMSonic Panel & FTP Account:<br />Panel URL: http://{service.whmsonic_ip_address}:2087/<br />FTP Host/IP: {service.whmsonic_ip_address}<br />Username: {service.whmsonic_username} <br />Password: {service.whmsonic_password}</p>
<p>Info: You can upload your MP3 files directly into the FTP account for AutoDJ. After MP3 upload, open your WHMSonic panel and reload the playlist or create a new AutoDJ. You can manage your playlist on WHMSonic panel. If you receive an error such as upload failed, that means your hosting quota limit is exceeded, you may remove some mp3 files or request more space. Your upload limits are provided under the account limits of this email.</p>
<p>Shoutcast Information:<br />Radio IP: {whmsonic_radio_ip}<br />Radio Password: {whmsonic_radio_password}</p>
<p>Account Limits:<br />Hosting/MP3 Quota-Upload Limit: {package.hspace} MB<br />FTP Account Permissions: {service.whmsonic_ftp}<br />Listener Limit: {package.listeners}<br />Bitrate Limit: {package.bitrate} Kbps<br />AutoDJ Permissions: {package.autodj}<br />Bandwidth Limit: {package.bandwidth} MB</p>'
    ]
]);
