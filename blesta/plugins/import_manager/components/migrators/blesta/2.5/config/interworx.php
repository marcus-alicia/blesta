<?php
Configure::set('interworx.map', [
    'module' => 'interworx',
    'module_row_key' => 'hostn',
    'module_row_meta' => [
        (object)['key' => 'server_name', 'value' => (object)['module' => 'hostn'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'host_name', 'value' => (object)['module' => 'hostn'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'key', 'value' => (object)['module' => 'password'], 'serialized' => 0, 'encrypted' => 1],
        (object)['key' => 'use_ssl', 'value' => (object)['module' => 'usessl'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'port', 'value' => '2443', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'account_count', 'value' => (object)['module' => 'cur'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'account_limit', 'value' => (object)['module' => 'max'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'debug', 'value' => 'none', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'name_servers', 'value' => null, 'serialized' => 1, 'encrypted' => 0],
        (object)['key' => 'notes', 'value' => null, 'serialized' => 0, 'encrypted' => 0]
    ],
    'package_meta' => [
        (object)['key' => 'package', 'value' => (object)['package' => 'instantact'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'type', 'value' => 'standard', 'serialized' => 0, 'encrypted' => 0]
    ],
    'service_fields' => [
        'user1' => (object)['key' => 'interworx_domain', 'serialized' => 0, 'encrypted' => 0],
        'user2' => (object)['key' => 'interworx_username', 'serialized' => 0, 'encrypted' => 0],
        'pass' => (object)['key' => 'interworx_password', 'serialized' => 0, 'encrypted' => 1],
        'opt1' => null,
        'opt2' => null
    ],
    'package_tags' => [
        '[domain]' => '{service.interworx_domain}',
        '[username]' => '{service.interworx_username}',
        '[pass]' => '{service.interworx_password}',
        '[server]' => '{module.host_name}',
        '[term]' => '{pricing.term}'
    ]
]);
