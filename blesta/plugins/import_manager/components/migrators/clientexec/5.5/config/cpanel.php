<?php

Configure::set('cpanel.map', [
    'module' => 'cpanel',
    'module_row_key' => 'hostname',
    'module_row_meta' => [
        (object) ['key' => 'host_name', 'value' => (object) ['module' => 'hostname'], 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'user_name', 'value' => (object) ['module' => 'plugin_cpanel_username'], 'serialized' => 0, 'encrypted' => 1],
        (object) ['key' => 'key', 'value' => (object) ['module' => 'plugin_cpanel_access_hash'], 'serialized' => 0, 'encrypted' => 1],
        (object) ['key' => 'use_ssl', 'value' => (object) ['module' => 'plugin_cpanel_use_ssl'], 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'account_limit', 'value' => (object) ['module' => 'domains_quota'], 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'name_servers', 'value' => (object) ['module' => 'nameservers'], 'serialized' => 1, 'encrypted' => 0],
        (object) ['key' => 'notes', 'value' => (object) ['module' => 'notes'], 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'server_name', 'value' => (object) ['module' => 'hostname'], 'serialized' => 0, 'encrypted' => 0],
    ],
    'package_meta' => [
        (object) ['key' => 'package', 'value' => (object) ['package' => 'plugin_cpanel'], 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'acl', 'value' => (object) ['package' => 'plugin_cpanel_acl-name'], 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'type', 'value' => 'standard', 'serialized' => 0, 'encrypted' => 0]
    ],
    'service_fields' => [
        'domain_name' => (object) ['key' => 'cpanel_domain', 'serialized' => 0, 'encrypted' => 0],
        'user_name' => (object) ['key' => 'cpanel_username', 'serialized' => 0, 'encrypted' => 0],
        'password' => (object) ['key' => 'cpanel_password', 'serialized' => 0, 'encrypted' => 1]
    ]
]);
