<?php
Configure::set('netearthone.map', [
    'module' => 'logicboxes',
    'module_row_key' => 'username',
    'module_row_meta' => [
        (object)['key' => 'registrar', 'value' => 'NetEarthOne', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'reseller_id', 'value' => (object)['module' => 'resellerid'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'key', 'value' => (object)['module' => 'apikey'], 'serialized' => 0, 'encrypted' => 1],
        (object)['key' => 'sandbox', 'value' => (object)['module' => 'testmode'], 'serialized' => 0, 'encrypted' => 0]
    ],
    'package_meta' => [
        (object)['key' => 'ns', 'value' => [], 'serialized' => 1, 'encrypted' => 0],
        (object)['key' => 'tlds', 'value' => (object)['package' => 'tlds'], 'serialized' => 1, 'encrypted' => 0],
        (object)['key' => 'type', 'value' => 'domain', 'serialized' => 0, 'encrypted' => 0]
    ],
    'service_fields' => [
        'domain' => (object)['key' => 'domain-name', 'serialized' => 0, 'encrypted' => 0]
    ]
]);
