<?php
Configure::set('blesta.map', [
    'module' => 'legacy_license',
    'module_row_key' => null,
    'module_row_meta' => [
        (object)['key' => 'label', 'value' => 'blesta_legacy', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'name', 'value' => 'Blesta Legacy', 'serialized' => 0, 'encrypted' => 0],
    ],
    'package_meta' => [
    ],
    'service_fields' => [
        'user1' => (object)['key' => 'host', 'serialized' => 0, 'encrypted' => 0],
        'user2' => (object)['key' => 'key', 'serialized' => 0, 'encrypted' => 0],
        'pass' => null,
        'opt1' => (object)['key' => 'last_callhome', 'serialized' => 0, 'encrypted' => 0],
        'opt2' => (object)['key' => 'version', 'serialized' => 0, 'encrypted' => 0]
    ],
    'package_tags' => [
        '[domain]' => '{service.host}',
        '[key]' => '{service.key}',
        '[version]' => '{service.version}'
    ]
]);
