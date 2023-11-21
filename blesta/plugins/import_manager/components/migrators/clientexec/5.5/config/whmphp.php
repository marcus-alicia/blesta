<?php

Configure::set('whmphp.map', [
    'module' => 'universal_module',
    'module_row_key' => 'username',
    'module_row_meta' => [
        (object) ['key' => 'name', 'value' => 'WHMPHP', 'serialized' => 0, 'encrypted' => 0],

        (object) ['key' => 'package_field_label_0', 'value' => 'Username', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_name_0', 'value' => 'username', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_required_0', 'value' => 'true', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_type_0', 'value' => 'secret', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_encrypt_0', 'value' => 'false', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_values_0', 'value' => (object) ['module' => 'plugin_whmphp_username'], 'serialized' => 0, 'encrypted' => 0],

        (object) ['key' => 'package_field_label_1', 'value' => 'Password', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_name_1', 'value' => 'password', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_required_1', 'value' => 'true', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_type_1', 'value' => 'secret', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_encrypt_1', 'value' => 'true', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_values_1', 'value' => (object) ['module' => 'plugin_whmphp_password'], 'serialized' => 0, 'encrypted' => 0],

        (object) ['key' => 'package_field_label_2', 'value' => 'Package Name on Server', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_name_2', 'value' => 'package_name', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_required_2', 'value' => 'true', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_type_2', 'value' => 'text', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_encrypt_2', 'value' => 'false', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_values_2', 'value' => null, 'serialized' => 0, 'encrypted' => 0],

        (object) ['key' => 'package_field_label_3', 'value' => 'Bandwidth', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_name_3', 'value' => 'bandwidth', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_required_3', 'value' => 'true', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_type_3', 'value' => 'text', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_encrypt_3', 'value' => 'false', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_values_3', 'value' => null, 'serialized' => 0, 'encrypted' => 0],

        (object) ['key' => 'package_field_label_4', 'value' => 'Disk Space', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_name_4', 'value' => 'disk_space', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_required_4', 'value' => 'true', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_type_4', 'value' => 'text', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_encrypt_4', 'value' => 'false', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_values_4', 'value' => null, 'serialized' => 0, 'encrypted' => 0],

        (object) ['key' => 'service_field_label_0', 'value' => 'Username', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'service_field_name_0', 'value' => 'username', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'service_field_type_0', 'value' => 'text', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'service_field_required_0', 'value' => 'true', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'service_field_encrypt_0', 'value' => 'false', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'service_field_values_0', 'value' => '', 'serialized' => 0, 'encrypted' => 0],

        (object) ['key' => 'service_field_label_1', 'value' => 'Domain', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'service_field_name_1', 'value' => 'domain', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'service_field_type_1', 'value' => 'text', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'service_field_required_1', 'value' => 'true', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'service_field_encrypt_1', 'value' => 'false', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'service_field_values_1', 'value' => '', 'serialized' => 0, 'encrypted' => 0],

        (object) ['key' => 'service_field_label_2', 'value' => 'Password', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'service_field_name_2', 'value' => 'password', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'service_field_type_2', 'value' => 'text', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'service_field_required_2', 'value' => 'true', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'service_field_encrypt_2', 'value' => 'true', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'service_field_values_2', 'value' => '', 'serialized' => 0, 'encrypted' => 0]
    ],
    'package_meta' => [
        (object) ['key' => 'package_name', 'value' => (object) ['package' => 'plugin_whmphp'], 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'bandwidth', 'value' => (object) ['package' => 'plugin_whmphp_acl_bandwidth'], 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'disk_space', 'value' => (object) ['package' => 'plugin_whmphp_acl_diskspace'], 'serialized' => 0, 'encrypted' => 0]
    ],
    'service_fields' => [
        'domain_name' => (object) ['key' => 'domain', 'serialized' => 0, 'encrypted' => 0],
        'user_name' => (object) ['key' => 'username', 'serialized' => 0, 'encrypted' => 0],
        'password' => (object) ['key' => 'password', 'serialized' => 0, 'encrypted' => 1]
    ]
]);
