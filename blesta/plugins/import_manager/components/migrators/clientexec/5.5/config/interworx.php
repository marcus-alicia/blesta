<?php

Configure::set('interworx.map', [
    'module' => 'interworx',
    'module_row_key' => 'server_name',
    'module_row_meta' => [
        (object) ['key' => 'server_name', 'value' => (object) ['module' => 'hostname'], 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'host_name', 'value' => (object) ['module' => 'hostname'], 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'key', 'value' => (object) ['module' => 'plugin_interworx_access_key'], 'serialized' => 0, 'encrypted' => 1],
        (object) ['key' => 'use_ssl', 'value' => 1, 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'port', 'value' => '2443', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'account_limit', 'value' => (object) ['module' => 'domains_quota'], 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'debug', 'value' => 'none', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'name_servers', 'value' => (object) ['module' => 'nameservers'], 'serialized' => 1, 'encrypted' => 0],
        (object) ['key' => 'notes', 'value' => (object) ['module' => 'notes'], 'serialized' => 0, 'encrypted' => 0]
    ],
    'package_meta' => [
        (object) ['key' => 'package', 'value' => (object) ['package' => 'plugin_interworx'], 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'type', 'value' => 'standard', 'serialized' => 0, 'encrypted' => 0]
    ],
    'service_fields' => [
        'domain_name' => (object) ['key' => 'interworx_domain', 'serialized' => 0, 'encrypted' => 0],
        'user_name' => (object) ['key' => 'interworx_username', 'serialized' => 0, 'encrypted' => 0],
        'password' => (object) ['key' => 'interworx_password', 'serialized' => 0, 'encrypted' => 1]
    ]
]);
