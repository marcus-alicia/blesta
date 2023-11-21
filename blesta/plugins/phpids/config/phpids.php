<?php
// Emails
Configure::set('Phpids.install.emails', [
    [
        'action' => 'Phpids.email_alert',
        'type' => 'staff',
        'plugin_dir' => 'phpids',
        'tags' => '{item.name},{item.value},{item.uri},{item.ip},{item.user_id},{item.tags},{item.impact}',
        'from' => 'no-reply@mydomain.com',
        'from_name' => 'PHPIDS',
        'subject' => 'PHPIDS intrusion detected on Blesta',
        'text' => 'An intrusion attempt was detected using the PHPIDS plugin in Blesta. Below are the details regarding the incident:

{% for item in data %}
Name: {item.name}
Value: {item.value}
URI: {item.uri}
IP: {item.ip}
User ID: {item.user_id}
Tags: {item.tags}
Impact: {item.impact}
----
{% endfor %}',
        'html' => '<p>
An intrusion attempt was detected using the PHPIDS plugin in Blesta. Below are the details regarding the incident:<br />
<br />
{% for item in data %}<br />
<strong>Name:</strong> {item.name}<br />
<strong>Value:</strong> {item.value}<br />
<strong>URI:</strong> {item.uri}<br />
<strong>IP:</strong> {item.ip}<br />
<strong>User ID:</strong> {item.user_id}<br />
<strong>Tags:</strong> {item.tags}<br />
<strong>Impact:</strong> {item.impact}</p>
<hr />
<p>
	{% endfor %}</p>'
    ]
]);
