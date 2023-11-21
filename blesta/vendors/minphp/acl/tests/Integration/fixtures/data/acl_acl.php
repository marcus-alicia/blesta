<?php
return [
    // UserGroup1 can do anything on ControllerA
    [
        'aro_id' => 'acl_aro.UserGoup1',
        'aco_id' => 'acl_aco.ControllerA',
        'action' => '*',
        'permission' => 'allow'
    ],
    // UserGroup1 can not do anything on ControllerB
    [
        'aro_id' => 'acl_aro.UserGoup1',
        'aco_id' => 'acl_aco.ControllerB',
        'action' => '*',
        'permission' => 'deny'
    ],
    // UserGroup2 can do anything on ControllerA except 'add'
    [
        'aro_id' => 'acl_aro.UserGoup2',
        'aco_id' => 'acl_aco.ControllerA',
        'action' => '*',
        'permission' => 'allow'
    ],
    [
        'aro_id' => 'acl_aro.UserGoup2',
        'aco_id' => 'acl_aco.ControllerA',
        'action' => 'add',
        'permission' => 'deny'
    ],
    // UserGroup2 can do anything on ControllerB
    [
        'aro_id' => 'acl_aro.UserGoup2',
        'aco_id' => 'acl_aco.ControllerB',
        'action' => '*',
        'permission' => 'allow'
    ]
];
