<?php

Configure::set('netearthone.map', [
    'module' => 'logicboxes',
    'module_row_key' => 'registrar',
    'module_row_meta' => [
        (object) ['key' => 'registrar', 'value' => 'NetEarth One', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'reseller_id', 'value' => (object) ['module' => 'plugin_netearthone_reseller_id'], 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'key', 'value' => (object) ['module' => 'plugin_netearthone_api_key'], 'serialized' => 0, 'encrypted' => 1],
        (object) ['key' => 'sandbox', 'value' => (object) ['module' => 'plugin_netearthone_use_testing_server'], 'serialized' => 0, 'encrypted' => 0]
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
