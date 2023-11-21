<?php
Configure::set('generic_registrar.map', [
    'module' => 'generic_domains',
    'module_row_meta' => [(object)['key' => 'name', 'value' => 'Generic Module Row', 'serialized' => 0, 'encrypted' => 0]],
    'package_meta' => [
        (object) ['key' => 'tlds', 'value' => (object) ['package' => 'tlds'], 'serialized' => 1, 'encrypted' => 0]
    ],
    'service_fields' => [
        'domain' => (object)['key' => 'domain', 'serialized' => 0, 'encrypted' => 0]
    ]
]);
