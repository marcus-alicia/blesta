<?php
Configure::set('solusvm_openvz.map', [
    'module' => 'solusvm',
    'module_row_key' => 'serverip',
    'module_row_meta' => [
        (object)['key' => 'server_name', 'value' => (object)['module' => 'serverip'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'host', 'value' => (object)['module' => 'serverip'], 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'port', 'value' => '5656', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'user_id', 'value' => (object)['module' => 'username'], 'serialized' => 0, 'encrypted' => 1],
        (object)['key' => 'key', 'value' => (object)['module' => 'key'], 'serialized' => 0, 'encrypted' => 1]
    ],
    'package_meta' => [
        (object)['key' => 'type', 'value' => 'standard', 'serialized' => 0, 'encrypted' => 0],
        (object)['key' => 'nodes', 'value' => (object)['package' => 'instantact'], 'serialized' => 1, 'encrypted' => 0, 'callback' => 'solusvm_openvz_nodes'],
        (object)['key' => 'plan', 'value' => (object)['package' => 'instantact'], 'serialized' => 0, 'encrypted' => 0, 'callback' => 'solusvm_openvz_plan'],
        (object)['key' => 'set_template', 'value' => 'client', 'serialized' => 0, 'encrypted' => 0]
    ],
    'service_fields' => [
        'user1' => (object)['key' => 'solusvm_hostname', 'serialized' => 0, 'encrypted' => 0],
        // Hybrid (user "/" pass)
        'user2' => (object)['key' => 'solusvm_username', 'serialized' => 0, 'encrypted' => 0],
        'pass' => (object)['key' => 'solusvm_root_password', 'serialized' => 0, 'encrypted' => 1],
        'opt1' => (object)['key' => 'solusvm_vserver_id', 'serialized' => 0, 'encrypted' => 0],
        'opt2' => (object)['key' => 'solusvm_main_ip_address', 'serialized' => 0, 'encrypted' => 0]
    ],
    'package_tags' => [
        '[serverip]' => '{service.solusvm_main_ip_address}',
        '[user1]' => '{service.solusvm_hostname}',
        '[user2]' => '{service.solusvm_username}',
        '[pass]' => '{service.solusvm_root_password}',
        '[opt2]' => '{service.solusvm_main_ip_address}',
        '[term]' => '{pricing.term}'
    ]
]);

function solusvm_openvz_nodes($str)
{
    $parts = explode('|', $str);
    return [$parts[0]];
}

function solusvm_openvz_plan($str)
{
    $parts = explode('|', $str);
    return $parts[1];
}
