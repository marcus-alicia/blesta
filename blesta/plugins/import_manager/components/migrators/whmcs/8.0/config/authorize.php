<?php
Configure::set('authorize.map', [
    'gateway' => 'authorize_net',
    'type' => 'merchant',
    'gateway_meta' => [
        (object)['key' => 'login_id', 'value' => 'loginid', 'serialized' => 0, 'encrypted' => 1, 'decrypt' => true],
        (object)['key' => 'test_mode', 'value' => 'testmode', 'serialized' => 0, 'encrypted' => 0, 'decrypt' => true, 'callback' => function ($value) { return ($value == 'on' ? 'true' : 'false');}],
        (object)['key' => 'transaction_key', 'value' => 'transkey', 'serialized' => 0, 'encrypted' => 1, 'decrypt' => true],
        (object)['key' => 'validation_mode', 'value' => 'visible', 'serialized' => 0, 'encrypted' => 0, 'decrypt' => false, 'callback' => function ($value) { return 'none';}]
    ]
]);
