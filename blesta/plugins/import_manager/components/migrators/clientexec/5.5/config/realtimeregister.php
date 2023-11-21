<?php

Configure::set('realtimeregister.map', [
    'module' => 'universal_module',
    'module_row_meta' => [
        (object) ['key' => 'name', 'value' => (object) ['module' => 'plugin_realtimeregister_plugin_name'], 'serialized' => 0, 'encrypted' => 0],

        (object) ['key' => 'package_field_label_0', 'value' => 'Dealer', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_name_0', 'value' => 'dealer', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_required_0', 'value' => 'false', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_type_0', 'value' => 'secret', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_encrypt_0', 'value' => 'false', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_values_0', 'value' => (object) ['module' => 'plugin_realtimeregister_dealer'], 'serialized' => 0, 'encrypted' => 0],

        (object) ['key' => 'package_field_label_1', 'value' => 'Password', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_name_1', 'value' => 'password', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_required_1', 'value' => 'false', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_type_1', 'value' => 'secret', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_encrypt_1', 'value' => 'true', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_values_1', 'value' => (object) ['module' => 'plugin_realtimeregister_password'], 'serialized' => 0, 'encrypted' => 0],

        (object) ['key' => 'package_field_label_2', 'value' => 'Handle', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_name_2', 'value' => 'handle', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_required_2', 'value' => 'false', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_type_2', 'value' => 'secret', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_encrypt_2', 'value' => 'true', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_values_2', 'value' => (object) ['module' => 'plugin_realtimeregister_handle'], 'serialized' => 0, 'encrypted' => 0],

        (object) ['key' => 'package_field_label_3', 'value' => 'Nameserver 1', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_name_3', 'value' => 'ns_1', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_required_3', 'value' => 'false', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_type_3', 'value' => 'text', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_encrypt_3', 'value' => 'true', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_values_3', 'value' => (object) ['module' => 'plugin_realtimeregister_ns_1'], 'serialized' => 0, 'encrypted' => 0],

        (object) ['key' => 'package_field_label_4', 'value' => 'Nameserver 2', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_name_4', 'value' => 'ns_2', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_required_4', 'value' => 'false', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_type_4', 'value' => 'text', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_encrypt_4', 'value' => 'true', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_values_4', 'value' => (object) ['module' => 'plugin_realtimeregister_ns_2'], 'serialized' => 0, 'encrypted' => 0],

        (object) ['key' => 'package_field_label_5', 'value' => 'TLD', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_name_5', 'value' => 'tlds', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_required_5', 'value' => 'true', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_type_5', 'value' => 'text', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_encrypt_5', 'value' => 'false', 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'package_field_values_5', 'value' => null, 'serialized' => 0, 'encrypted' => 0],

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
