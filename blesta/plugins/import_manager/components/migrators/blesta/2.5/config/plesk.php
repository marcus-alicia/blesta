<?php
Configure::set('plesk.map', [
    'module' => 'plesk',
    'module_row_key' => 'hostip',
    'module_row_meta' => [
        (object)['key' => 'server_name', 'value' => (object)['module' => 'hostip'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'ip_address', 'value' => (object)['module' => 'hostip'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'port', 'value' => '8443', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'username', 'value' => (object)['module' => 'user'], 'serialized' => 0, 'encrypted' => 1],
        (object)['key' => 'password', 'value' => (object)['module' => 'password'], 'serialized' => 0, 'encrypted' => 1],
        (object)['key' => 'account_count', 'value' => (object)['module' => 'cur'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'account_limit', 'value' => (object)['module' => 'max'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'panel_version', 'value' => '11.5', 'serialized' => 0, 'encrypted' => 0]
    ],
    'package_meta' => [
        (object)['key' => 'plan', 'value' => (object)['package' => 'instantact'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'type', 'value' => 'standard', 'serialized' => 0, 'encrypted' => 0]
    ],
    'service_fields' => [
        'user1' => (object)['key' => 'plesk_domain', 'serialized' => 0, 'encrypted' => 0],
        'user2' => (object)['key' => 'plesk_username', 'serialized' => 0, 'encrypted' => 0],
        'pass' => (object)['key' => 'plesk_password', 'serialized' => 0, 'encrypted' => 1],
        'opt1' => null,
        'opt2' => null
    ],
    'package_tags' => [
        '[domain]' => '{service.plesk_domain}',
        '[username]' => '{service.plesk_username}',
        '[pass]' => '{service.plesk_password}',
        '[server]' => '{module.host_name}',
        '[term]' => '{pricing.term}'
    ]
]);
