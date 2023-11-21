<?php

Configure::set('internetbs.map', [
    'module' => 'universal_module',
    'module_row_meta' => [
        (object) ['key' => 'name', 'value' => (object) ['module' => 'plugin_internetbs_plugin_name'], 'serialized' => 0, 'encrypted' => 0],

        (object) ['key' => 'package_field_label_0', 'value' => 'API Key', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_name_0', 'value' => 'key', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_required_0', 'value' => 'false', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_type_0', 'value' => 'secret', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_encrypt_0', 'value' => 'false', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_values_0', 'value' => (object) ['module' => 'plugin_internetbs_api_key'], 'serialized' => 0, 'encrypted' => 0],

        (object) ['key' => 'package_field_label_1', 'value' => 'Password', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_name_1', 'value' => 'password', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_required_1', 'value' => 'false', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_type_1', 'value' => 'secret', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_encrypt_1', 'value' => 'true', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_values_1', 'value' => (object) ['module' => 'plugin_internetbs_password'], 'serialized' => 0, 'encrypted' => 0],

        (object) ['key' => 'package_field_label_2', 'value' => 'TLD', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_name_2', 'value' => 'tlds', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_required_2', 'value' => 'true', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_type_2', 'value' => 'text', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_encrypt_2', 'value' => 'false', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_values_2', 'value' => null, 'serialized' => 0, 'encrypted' => 0],

        (object) ['key' => 'service_field_label_0', 'value' => 'Domain', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'service_field_name_0', 'value' => 'domain', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'service_field_type_0', 'value' => 'text', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'service_field_required_0', 'value' => 'true', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'service_field_encrypt_0', 'value' => 'false', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'service_field_values_0', 'value' => '', 'serialized' => 0, 'encrypted' => 0]
    ],
    'package_meta' => [
        (object) ['key' => 'tlds', 'value' => (object) ['package' => 'tlds'], 'serialized' => 1, 'encrypted' => 0]
    ],
    'service_fields' => [
        'domain_name' => (object) ['key' => 'domain', 'serialized' => 0, 'encrypted' => 0]
    ]
]);
