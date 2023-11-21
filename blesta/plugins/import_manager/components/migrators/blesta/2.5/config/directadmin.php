<?php
Configure::set('directadmin.map', [
    'module' => 'direct_admin',
    'module_row_key' => 'serverip',
    'module_row_meta' => [
        (object)['key' => 'server_name', 'value' => (object)['module' => 'serverip'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'host_name', 'value' => (object)['module' => 'serverip'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'user_name', 'value' => (object)['module' => 'user'], 'serialized' => 0, 'encrypted' => 1],
        (object)['key' => 'password', 'value' => (object)['module' => 'password'], 'serialized' => 0, 'encrypted' => 1],
        (object)['key' => 'use_ssl', 'value' => (object)['module' => 'usessl'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'account_limit', 'value' => (object)['module' => 'max'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'name_servers', 'value' =>null, 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'notes', 'value' => null, 'serialized' => 0, 'encrypted' => 0]
    ],
    'package_meta' => [
        (object)['key' => 'type', 'value' => 'user', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'package', 'value' => (object)['package' => 'instantact'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'ip', 'value' => (object)['module' => 'serverip'], 'serialized' => 0, 'encrypted' => 0]
    ],
    'service_fields' => [
        'user1' => (object)['key' => 'direct_admin_domain', 'serialized' => 0, 'encrypted' => 0],
        'user2' => (object)['key' => 'direct_admin_username', 'serialized' => 0, 'encrypted' => 0],
        'pass' => (object)['key' => 'direct_admin_password', 'serialized' => 0, 'encrypted' => 1],
        'opt1' => (object)['key' => 'direct_admin_email', 'serialized' => 0, 'encrypted' => 0],
        'opt2' => null
    ],
    'package_tags' => [
        '[domain]' => '{service.direct_admin_domain}',
        '[username]' => '{service.direct_admin_username}',
        '[pass]' => '{service.direct_admin_password}',
        '[serverip]' => '{package.ip}',
        '[term]' => '{pricing.term}'
    ]
]);
