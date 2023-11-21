<?php

Configure::set('solusvmpro.map', [
    'module' => 'solusvm',
    'module_row_key' => 'hostname',
    'module_row_meta' => [
        (object) ['key' => 'host', 'value' => (object) ['module' => 'hostname'], 'alternate_value' => (object)['module' => 'ipaddress'], 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'user_id', 'value' => (object) ['module' => 'username'], 'serialized' => 0, 'encrypted' => 1],
        (object) ['key' => 'key', 'value' => (object) ['module' => 'password'], 'serialized' => 0, 'encrypted' => 1],
        (object) ['key' => 'port', 'value' => (object) ['module' => 'port'], 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'server_name', 'value' => (object) ['module' => 'hostname'], 'alternate_value' => (object)['module' => 'ipaddress'], 'serialized' => 0, 'encrypted' => 0],
    ],
    'package_meta' => [
        (object) ['key' => 'total_base_ip_addresses', 'value' => (object) ['package' => 'configoption8'], 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'type', 'value' => (object) ['package' => 'configoption5'], 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'node_group', 'value' => (object) ['package' => 'configoption9'], 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'plan', 'value' => (object) ['package' => 'configoption4'], 'serialized' => 0, 'encrypted' => 0]
    ],
    'service_fields' => [
        'server_acct_properties' => (object) ['key' => 'vserverid', 'serialized' => 0, 'encrypted' => 0],
        'ip_address' => (object) ['key' => 'internalip', 'serialized' => 0, 'encrypted' => 0],
        'domain_name' => (object) ['key' => 'domain', 'serialized' => 0, 'encrypted' => 0],
        'user_name' => (object) ['key' => 'username', 'serialized' => 0, 'encrypted' => 0],
        'password' => (object) ['key' => 'password', 'serialized' => 0, 'encrypted' => 1]
    ]
]);
