<?php
Configure::set('plesk.map', [
    'module' => 'plesk',
    'module_row_key' => 'hostname',
    'module_row_meta' => [
        (object)['key' => 'server_name', 'value' => (object)['module' => 'name'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'ip_address', 'value' => (object)['module' => 'ipaddress'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'port', 'value' => '8443', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'username', 'value' => (object)['module' => 'username'], 'serialized' => 0, 'encrypted' => 1],
        (object)['key' => 'password', 'value' => (object)['module' => 'password'], 'serialized' => 0, 'encrypted' => 1],
        (object)['key' => 'account_limit', 'value' => (object)['module' => 'maxaccounts'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'panel_version', 'value' => '', 'serialized' => 0, 'encrypted' => 0, 'callback' => function($value, $row_meta) {
            $version = '';
            if (isset($row_meta['type'])) {
                $version = abs((int)filter_var($row_meta['type'], FILTER_SANITIZE_NUMBER_INT));
            }

            return $version;
        }]
    ],
    'package_meta' => [
        (object)['key' => 'plan', 'value' => (object)['package' => 'configoption1'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'type', 'value' => 'standard', 'serialized' => 0, 'encrypted' => 0]
    ],
    'service_fields' => [
        'domain' => (object)['key' => 'plesk_domain', 'serialized' => 0, 'encrypted' => 0],
        'username' => (object)['key' => 'plesk_username', 'serialized' => 0, 'encrypted' => 0],
        'password' => (object)['key' => 'plesk_password', 'serialized' => 0, 'encrypted' => 1]
    ]
]);
