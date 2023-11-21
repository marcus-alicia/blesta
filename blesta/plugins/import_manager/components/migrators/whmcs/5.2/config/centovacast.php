<?php
Configure::set('centovacast.map', [
    'module' => 'centovacast',
    'module_row_key' => 'hostname',
    'module_row_meta' => [
        (object)['key' => 'server_name', 'value' => (object)['module' => 'name'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'hostname', 'value' => (object)['module' => 'hostname'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'ipaddress', 'value' => (object)['module' => 'ipaddress'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'port', 'value' => (object)['module' => 'port'], 'serialized' => 0, 'encrypted' => 0, 'callback' => function ($value) { return ($value ?? 2199);}],
        (object)['key' => 'username', 'value' => (object)['module' => 'username'], 'serialized' => 0, 'encrypted' => 1],
        (object)['key' => 'password', 'value' => (object)['module' => 'password'], 'serialized' => 0, 'encrypted' => 1],
        (object)['key' => 'use_ssl', 'value' => (object)['module' => 'secure'], 'serialized' => 0, 'encrypted' => 0, 'callback' => function ($value) { return ($value == 'on' ? 'true' : 'false');}],
        (object)['key' => 'account_limit', 'value' => (object)['module' => 'maxaccounts'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'notes', 'value' => null, 'serialized' => 0, 'encrypted' => 0],
        
    ],
    'package_meta' => [
        (object)['key' => 'servertype', 'value' => (object)['package' => 'configoption1'], 'serialized' => 0, 'encrypted' => 0, 'callback' => function ($value) { return (str_contains(strtolower($value), 'shout') ? 'ShoutCast' : 'IceCast');}],
        (object)['key' => 'maxbitrate', 'value' => (object)['package' => 'configoption3'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'diskquota', 'value' => (object)['package' => 'configoption5'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'transferlimit', 'value' => (object)['package' => 'configoption4'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'maxclients', 'value' => (object)['package' => 'configoption2'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'apptypes', 'value' => '', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'usesource', 'value' => (object)['package' => 'configoption9'], 'serialized' => 0, 'encrypted' => 0, 'callback' => function ($value) { return ($value == 'Enabled' ? '1' : '0');}]
    ],
    'service_fields' => [
        'domain' => (object)['key' => 'centovacast_hostname', 'serialized' => 0, 'encrypted' => 0],
        'username' => (object)['key' => 'centovacast_username', 'serialized' => 0, 'encrypted' => 0],
        'password' => (object)['key' => 'centovacast_adminpassword', 'serialized' => 0, 'encrypted' => 1]
    ]
]);
