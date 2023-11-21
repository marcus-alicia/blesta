<?php

Configure::set('plesk.map', [
    'module' => 'plesk',
    'module_row_key' => 'server_name',
    'module_row_meta' => [
        (object) ['key' => 'server_name', 'value' => (object) ['module' => 'hostname'], 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'ip_address', 'value' => (object) ['module' => 'sharedip'], 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'port', 'value' => '8443', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'username', 'value' => (object) ['module' => 'plugin_plesk_username'], 'serialized' => 0, 'encrypted' => 1],
        (object) ['key' => 'password', 'value' => (object) ['module' => 'plugin_plesk_password'], 'serialized' => 0, 'encrypted' => 1],
        (object) ['key' => 'account_limit', 'value' => (object) ['module' => 'domains_quota'], 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'panel_version', 'value' => '10', 'serialized' => 0, 'encrypted' => 0]
    ],
    'package_meta' => [
        (object) ['key' => 'plan', 'value' => (object) ['package' => 'plugin_plesk'], 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'type', 'value' => 'standard', 'serialized' => 0, 'encrypted' => 0]
    ],
    'service_fields' => [
        'domain_name' => (object) ['key' => 'plesk_domain', 'serialized' => 0, 'encrypted' => 0],
        'user_name' => (object) ['key' => 'plesk_username', 'serialized' => 0, 'encrypted' => 0],
        'password' => (object) ['key' => 'plesk_password', 'serialized' => 0, 'encrypted' => 1],
        'password' => (object) ['key' => 'plesk_confirm_password', 'serialized' => 0, 'encrypted' => 1]
    ]
]);
