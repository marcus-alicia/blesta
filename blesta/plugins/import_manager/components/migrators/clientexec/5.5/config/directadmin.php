<?php

Configure::set('directadmin.map', [
    'module' => 'direct_admin',
    'module_row_key' => 'server_name',
    'module_row_meta' => [
        (object) ['key' => 'server_name', 'value' => (object) ['module' => 'hostname'], 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'host_name', 'value' => (object) ['module' => 'hostname'], 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'user_name', 'value' => (object) ['module' => 'plugin_directadmin_username'], 'serialized' => 0, 'encrypted' => 1],
        (object) ['key' => 'password', 'value' => (object) ['module' => 'plugin_directadmin_password'], 'serialized' => 0, 'encrypted' => 1],
        (object) ['key' => 'use_ssl', 'value' => (object) ['module' => 'plugin_directadmin_use_ssl'], 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'account_limit', 'value' => (object) ['module' => 'domains_quota'], 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'name_servers', 'value' => (object) ['module' => 'nameservers'], 'serialized' => 1, 'encrypted' => 0],
        (object) ['key' => 'notes', 'value' => (object) ['module' => 'notes'], 'serialized' => 0, 'encrypted' => 0]
    ],
    'package_meta' => [
        (object) ['key' => 'type', 'value' => 'user', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package', 'value' => (object) ['package' => 'plugin_directadmin'], 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'ip', 'value' => null, 'serialized' => 0, 'encrypted' => 0]
    ],
    'service_fields' => [
        'domain_name' => (object) ['key' => 'direct_admin_domain', 'serialized' => 0, 'encrypted' => 0],
        'user_name' => (object) ['key' => 'direct_admin_username', 'serialized' => 0, 'encrypted' => 0],
        'password' => (object) ['key' => 'direct_admin_password', 'serialized' => 0, 'encrypted' => 1]
    ]
]);
