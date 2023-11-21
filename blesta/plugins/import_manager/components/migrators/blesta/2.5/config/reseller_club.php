<?php
Configure::set('reseller_club.map', [
    'module' => 'logicboxes',
    'module_row_key' => 'resellerid',
    'module_row_meta' => [
        (object)['key' => 'registrar', 'value' => 'ResellerClub', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'reseller_id', 'value' => (object)['module' => 'resellerid'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'key', 'value' => (object)['module' => 'password'], 'serialized' => 0, 'encrypted' => 1],
        (object)['key' => 'sandbox', 'value' => (object)['module' => 'testmode'], 'serialized' => 0, 'encrypted' => 0]
    ],
    'package_meta' => [
        (object)['key' => 'ns', 'value' => [], 'serialized' => 1, 'encrypted' => 0],
        (object)['key' => 'tlds', 'value' => [], 'serialized' => 1, 'encrypted' => 0],
        (object)['key' => 'type', 'value' => 'domain', 'serialized' => 0, 'encrypted' => 0]
    ],
    'service_fields' => [
        'user1' => (object)['key' => 'domain-name', 'serialized' => 0, 'encrypted' => 0],
        'user2' => null,
        'pass' => null,
        'opt1' => null,
        'opt2' => null
    ],
    'package_tags' => [
        '[domain]' => '{service.domain-name}'
    ]
]);
