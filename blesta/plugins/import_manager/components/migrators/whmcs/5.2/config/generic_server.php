<?php
Configure::set('generic_server.map', [
    'module' => 'universal_module',
    'module_row_meta' => [
        (object)['key' => 'name', 'value' => (object)['module' => 'type'], 'serialized' => 0, 'encrypted' => 0],

        (object)['key' => 'package_field_label_0', 'value' => 'Hostname', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'package_field_name_0', 'value' => 'hostname', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'package_field_required_0', 'value' => 'true', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'package_field_type_0', 'value' => 'secret', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'package_field_encrypt_0', 'value' => 'false', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'package_field_values_0', 'value' => (object)['module' => 'hostname'], 'serialized' => 0, 'encrypted' => 0],

        (object)['key' => 'package_field_label_1', 'value' => 'Username', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'package_field_name_1', 'value' => 'username', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'package_field_required_1', 'value' => 'true', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'package_field_type_1', 'value' => 'secret', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'package_field_encrypt_1', 'value' => 'false', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'package_field_values_1', 'value' => (object)['module' => 'username'], 'serialized' => 0, 'encrypted' => 0],

        (object)['key' => 'package_field_label_2', 'value' => 'Password', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'package_field_name_2', 'value' => 'password', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'package_field_required_2', 'value' => 'true', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'package_field_type_2', 'value' => 'secret', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'package_field_encrypt_2', 'value' => 'true', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'package_field_values_2', 'value' => (object)['module' => 'password'], 'serialized' => 0, 'encrypted' => 0],

        (object)['key' => 'service_field_label_0', 'value' => 'Username', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'service_field_name_0', 'value' => 'username', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'service_field_type_0', 'value' => 'text', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'service_field_required_0', 'value' => 'true', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'service_field_encrypt_0', 'value' => 'false', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'service_field_values_0', 'value' => '', 'serialized' => 0, 'encrypted' => 0],

        (object)['key' => 'service_field_label_1', 'value' => 'Domain', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'service_field_name_1', 'value' => 'domain', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'service_field_type_1', 'value' => 'text', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'service_field_required_1', 'value' => 'true', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'service_field_encrypt_1', 'value' => 'false', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'service_field_values_1', 'value' => '', 'serialized' => 0, 'encrypted' => 0],

        (object)['key' => 'service_field_label_2', 'value' => 'Password', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'service_field_name_2', 'value' => 'password', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'service_field_type_2', 'value' => 'text', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'service_field_required_2', 'value' => 'true', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'service_field_encrypt_2', 'value' => 'true', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'service_field_values_2', 'value' => '', 'serialized' => 0, 'encrypted' => 0]
    ],
    'package_meta' => [],
    'service_fields' => [
        'domain' => (object)['key' => 'domain', 'serialized' => 0, 'encrypted' => 0],
        'username' => (object)['key' => 'username', 'serialized' => 0, 'encrypted' => 0],
        'password' => (object)['key' => 'password', 'serialized' => 0, 'encrypted' => 1]
    ]
]);
