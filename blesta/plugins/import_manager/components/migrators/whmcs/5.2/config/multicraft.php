<?php
Configure::set('multicraft.map', [
    'module' => 'multicraft',
    'module_row_key' => 'server_name',
    'module_row_meta' => [
        (object)['key' => 'server_name', 'value' => (object)['module' => 'name'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'username', 'value' => (object)['module' => 'username'], 'serialized' => 0, 'encrypted' => 1],
        (object)['key' => 'key', 'value' => (object)['module' => 'accesshash'], 'serialized' => 0, 'encrypted' => 1],
        (object)['key' => 'log_all', 'value' => '0', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'panel_url', 'value' => (object)['module' => 'hostname'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'panel_api_url', 'value' => '', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'daemons', 'value' => [], 'serialized' => 1, 'encrypted' => 0],
        (object)['key' => 'ips', 'value' => [], 'serialized' => 1, 'encrypted' => 0],
        (object)['key' => 'ips_in_use', 'value' => [], 'serialized' => 1, 'encrypted' => 0]
    ],
    'package_meta' => [
        (object)['key' => 'autostart', 'value' => '1', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'create_ftp', 'value' => '0', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'default_level', 'value' => '10', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'jardir', 'value' => '', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'jarfile', 'value' => (object)['package' => 'configoption3'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'memory', 'value' => (object)['package' => 'configoption2'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'players', 'value' => (object)['package' => 'configoption1'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'server_name', 'value' => 'Minecraft Server', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'server_visibility', 'value' => '1', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'user_ftp', 'value' => '0', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'user_jar', 'value' => '0', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'user_name', 'value' => '1', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'user_schedule', 'value' => '1', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'user_visibility', 'value' => '1', 'serialized' => 0, 'encrypted' => 0]
    ],
    'service_fields' => [
        'domain' => (object)['key' => 'multicraft_server_id', 'serialized' => 0, 'encrypted' => 0],
        'username' => (object)['key' => 'multicraft_login_username', 'serialized' => 0, 'encrypted' => 0],
        'password' => (object)['key' => 'multicraft_login_password', 'serialized' => 0, 'encrypted' => 1]
    ]
]);
