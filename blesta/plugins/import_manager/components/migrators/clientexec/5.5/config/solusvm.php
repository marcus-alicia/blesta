<?php

Configure::set('solusvm.map', [
    'module' => 'solusvm',
    'module_row_key' => 'server_name',
    'module_row_meta' => [
        (object) ['key' => 'host', 'value' => (object) ['module' => 'hostname'], 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'user_id', 'value' => (object) ['module' => 'plugin_solusvm_id'], 'serialized' => 0, 'encrypted' => 1],
        (object) ['key' => 'key', 'value' => (object) ['module' => 'plugin_solusvm_key'], 'serialized' => 0, 'encrypted' => 1],
        (object) ['key' => 'port', 'value' => (object) ['module' => 'plugin_solusvm_port'], 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'server_name', 'value' => (object) ['module' => 'hostname'], 'serialized' => 0, 'encrypted' => 0],
    ],
    'package_meta' => [
        (object) ['key' => 'total_base_ip_addresses', 'value' => (object) ['package' => 'plugin_solusvm_package_vars_num_of_ips'], 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'type', 'value' => (object) ['package' => 'plugin_solusvm_package_vars_vm_type'], 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'node_group', 'value' => (object) ['package' => 'plugin_solusvm_package_vars_node_group'], 'serialized' => 0, 'encrypted' => 0],
        (object) ['key' => 'plan', 'value' => (object) ['package' => 'plugin_solusvm'], 'serialized' => 0, 'encrypted' => 0]
    ],
    'service_fields' => [
        'server_acct_properties' => (object) ['key' => 'solusvm_vserver_id', 'serialized' => 0, 'encrypted' => 0],
        'ip_address' => (object) ['key' => 'solusvm_main_ip_address', 'serialized' => 0, 'encrypted' => 0],
        'domain_name' => (object) ['key' => 'solusvm_hostname', 'serialized' => 0, 'encrypted' => 0],
        'user_name' => (object) ['key' => 'solusvm_username', 'serialized' => 0, 'encrypted' => 0],
        'password' => (object) ['key' => 'solusvm_password', 'serialized' => 0, 'encrypted' => 1]
    ]
]);
