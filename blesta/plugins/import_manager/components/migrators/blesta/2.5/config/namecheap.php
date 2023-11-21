<?php
Configure::set('namecheap.map', [
    'module' => 'namecheap',
    'module_row_key' => 'api_username',
    'module_row_meta' => [
        (object)['key' => 'user', 'value' => (object)['module' => 'api_username'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'key', 'value' => (object)['module' => 'api_key'], 'serialized' => 0, 'encrypted' => 1],
        (object)['key' => 'sandbox', 'value' => (object)['module' => 'testmode'], 'serialized' => 0, 'encrypted' => 0]
    ],
    'package_meta' => [
        (object)['key' => 'type', 'value' => 'domain', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'ns', 'value' => (object)['module' => 'ns1'], 'serialized' => 1, 'encrypted' => 0]
    ],
    'service_fields' => [
        'user1' => (object)['key' => 'DomainName', 'serialized' => 0, 'encrypted' => 0],
        'user2' => null,
        'pass' => null,
        'opt1' => null,
        'opt2' => null
    ],
    'package_tags' => [
        '[domain]' => '{service.DomainName}'
    ]
]);
