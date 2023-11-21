<?php
Configure::set('namecheap.map', [
    'module' => 'namecheap',
    'module_row_key' => 'username',
    'module_row_meta' => [
        (object)['key' => 'user', 'value' => (object)['module' => 'username'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'key', 'value' => (object)['module' => 'password'], 'serialized' => 0, 'encrypted' => 1],
        (object)['key' => 'sandbox', 'value' => (object)['module' => 'testmode'], 'serialized' => 0, 'encrypted' => 0]
    ],
    'package_meta' => [
        (object)['key' => 'type', 'value' => 'domain', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'ns', 'value' => null, 'serialized' => 1, 'encrypted' => 0],
        (object)['key' => 'tlds', 'value' => (object)['package' => 'tlds'], 'serialized' => 1, 'encrypted' => 0]
    ],
    'service_fields' => [
        'domain' => (object)['key' => 'DomainName', 'serialized' => 0, 'encrypted' => 0]
    ]
]);
