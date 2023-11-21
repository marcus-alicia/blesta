<?php

Configure::set('enom.map', [
    'module' => 'enom',
    'module_row_key' => 'user',
    'module_row_meta' => [
        (object) ['key' => 'user', 'value' => (object) ['module' => 'plugin_enom_login'], 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'key', 'value' => (object) ['module' => 'plugin_enom_password'], 'serialized' => 0, 'encrypted' => 1],
        (object) ['key' => 'sandbox', 'value' => (object) ['module' => 'plugin_enom_use_testing_server'], 'serialized' => 0, 'encrypted' => 0]
    ],
    'package_meta' => [
        (object) ['key' => 'ns', 'value' => [], 'serialized' => 1, 'encrypted' => 0],
        (object) ['key' => 'tlds', 'value' => (object) ['package' => 'tlds'], 'serialized' => 1, 'encrypted' => 0],
        (object) ['key' => 'type', 'value' => 'domain', 'serialized' => 0, 'encrypted' => 0]
    ],
    'service_fields' => [
        'domain_name' => (object) ['key' => 'domain', 'serialized' => 0, 'encrypted' => 0]
    ]
]);
