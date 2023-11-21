<?php
Configure::set('pterodactyl.map', [
    'module' => 'pterodactyl',
    'module_row_key' => 'server_name',
    'module_row_meta' => [
        (object)['key' => 'server_name', 'value' => (object)['module' => 'name'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'host_name', 'value' => (object)['module' => 'hostname'], 'alternate_value' => (object)['module' => 'ipaddress'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'account_api_key', 'value' => '', 'serialized' => 0, 'encrypted' => 1],
        (object)['key' => 'application_api_key', 'value' => (object)['module' => 'password'], 'serialized' => 0, 'encrypted' => 1],
        (object)['key' => 'use_ssl', 'value' => (object)['module' => 'secure'], 'serialized' => 0, 'encrypted' => 0, 'callback' => function ($value) { return ($value == 'on' ? 'true' : 'false');}],
    ],
    'package_meta' => [
        (object)['key' => 'location_id', 'value' => (object)['package' => 'configoption5'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'nest_id', 'value' => (object)['package' => 'configoption7'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'egg_id', 'value' => (object)['package' => 'configoption8'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'dedicated_id', 'value' => (object)['package' => 'configoption6'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'port_range', 'value' => (object)['package' => 'configoption11'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'pack_id', 'value' => (object)['package' => 'configoption10'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'memory', 'value' => (object)['package' => 'configoption3'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'swap', 'value' => (object)['package' => 'configoption4'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'cpu', 'value' => (object)['package' => 'configoption1'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'disk', 'value' => (object)['package' => 'configoption2'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'io', 'value' => (object)['package' => 'configoption9'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'startup', 'value' => (object)['package' => 'configoption12'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'image', 'value' => (object)['package' => 'configoption13'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'databases', 'value' => (object)['package' => 'configoption14'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'allocations', 'value' => '1', 'serialized' => 0, 'encrypted' => 0]
    ],
    'service_fields' => [
        'id' => (object)['key' => 'external_id', 'serialized' => 0, 'encrypted' => 0],
    ]
]);
